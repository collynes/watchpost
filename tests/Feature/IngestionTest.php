<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Source;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class IngestionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function payload(Source $source, array $overrides = []): array
    {
        return array_replace(['schema_version' => '1', 'event_id' => 'event-001', 'source_id' => $source->id, 'event_type' => 'auth.lockout', 'timestamp' => '2026-01-01T12:00:00Z'], $overrides);
    }

    public function test_returns_401_without_a_source_credential(): void
    {
        $this->postJson('/ingest/v1/events', [])->assertUnauthorized();
        $this->assertDatabaseCount('events', 0);
    }

    public function test_persists_a_high_severity_event_and_alert_and_redacts_metadata(): void
    {
        $source = Source::factory()->create();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events', $this->payload($source, ['severity' => 5, 'meta' => ['password' => 'secret', 'nested' => ['access_token' => 'secret']]]))->assertCreated()->assertJsonPath('accepted', 1)->assertJsonPath('alerts_created', 1);
        $this->assertDatabaseHas('events', ['severity' => 2, 'source_event_id' => 'event-001']);
        $this->assertDatabaseCount('alerts', 1);
        $this->assertSame('[REDACTED]', Event::first()->meta['nested']['access_token']);
        $this->assertNotNull($source->fresh()->last_received_at);
    }

    public function test_retries_do_not_duplicate_even_critical_events(): void
    {
        $source = Source::factory()->create();
        $payload = $this->payload($source, ['severity' => 1]);
        $first = $this->withToken('test-source-token')->postJson('/ingest/v1/events', $payload)->assertCreated();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events', $payload)->assertOk()->assertJsonPath('duplicates', 1)->assertJsonPath('event_ids.0', $first->json('event_ids.0'));
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_conflicting_retry_rolls_back_the_entire_batch(): void
    {
        $source = Source::factory()->create();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events', $this->payload($source))->assertCreated();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events/batch', ['events' => [$this->payload($source, ['event_id' => 'event-002']), $this->payload($source, ['count' => 2])]])->assertConflict();
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_rejects_a_different_source_and_invalid_events_without_partial_writes(): void
    {
        $source = Source::factory()->create();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events/batch', ['events' => [$this->payload($source), $this->payload($source, ['source_id' => 'other', 'count' => 0])]])->assertUnprocessable()->assertJsonValidationErrors(['events.1.source_id', 'events.1.count']);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_disabled_source_is_unauthorized(): void
    {
        $source = Source::factory()->create(['enabled' => false]);
        $this->withToken('test-source-token')->postJson('/ingest/v1/events', $this->payload($source))->assertUnauthorized();
        $this->assertDatabaseCount('events', 0);
    }

    public function test_super_admin_creation_escalates_but_normal_activity_does_not_create_alerts(): void
    {
        $source = Source::factory()->create();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events/batch', ['events' => [
            $this->payload($source, ['event_type' => 'auth.login_success']),
            $this->payload($source, ['event_id' => 'event-002', 'event_type' => 'account.created', 'meta' => ['role' => 'super_admin']]),
        ]])->assertCreated()->assertJsonPath('alerts_created', 1);
        $this->assertDatabaseHas('events', ['source_event_id' => 'event-001', 'severity' => 5]);
        $this->assertDatabaseHas('events', ['source_event_id' => 'event-002', 'severity' => 1]);
    }

    public function test_requires_stable_event_id_and_utc_timestamp(): void
    {
        $source = Source::factory()->create();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events', $this->payload($source, ['event_id' => null, 'timestamp' => '2026-01-01 12:00:00']))->assertUnprocessable()->assertJsonValidationErrors(['events.0.event_id', 'events.0.timestamp']);
        $this->assertDatabaseCount('events', 0);
    }
}
