<?php

namespace Tests\Feature;

use App\Jobs\SendAlert;
use App\Mail\SecurityAlert;
use App\Models\Alert;
use App\Models\Source;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AlertDeliveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_ingestion_queues_critical_email_when_enabled(): void
    {
        Queue::fake([SendAlert::class]);
        config(['watchpost.mail_enabled' => true]);
        $source = Source::factory()->create();
        $this->withToken('test-source-token')->postJson('/ingest/v1/events', ['schema_version' => '1', 'source_id' => $source->id, 'event_id' => 'critical-1', 'event_type' => 'auth.lockout', 'timestamp' => '2026-01-01T00:00:00Z'])->assertCreated();
        Queue::assertPushed(SendAlert::class, fn (SendAlert $job) => $job->alertId === Alert::first()->id);
    }

    public function test_delivered_alert_is_not_sent_again(): void
    {
        Mail::fake();
        config(['watchpost.mail_enabled' => true, 'watchpost.recipients' => ['operator@example.com']]);
        $alert = Alert::factory()->create();
        $job = new SendAlert($alert->id);
        $job->handle();
        $job->handle();
        Mail::assertSent(SecurityAlert::class, 1);
        $this->assertNotNull($alert->fresh()->notified_at);
    }
}
