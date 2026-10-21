<?php

namespace Tests\Feature;

use App\Jobs\SendDigest;
use App\Mail\DailyDigest;
use App\Models\Digest;
use App\Models\Event;
use App\Models\Source;
use App\Services\BuildDigest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DigestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_digest_uses_local_day_boundaries_counts_occurrences_and_is_immutable(): void
    {
        config(['watchpost.timezone' => 'Europe/Amsterdam']);
        $source = Source::factory()->create();
        Event::factory()->create(['source_id' => $source->id, 'occurred_at' => '2026-03-28 23:00:00', 'count' => 3]);
        Event::factory()->create(['source_id' => $source->id, 'occurred_at' => '2026-03-29 22:00:00', 'count' => 7]);
        $digest = app(BuildDigest::class)->handle('2026-03-29');
        $this->assertSame(3, $digest->snapshot['total_events']);
        $this->assertSame('2026-03-28 23:00:00', $digest->window_start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-29 22:00:00', $digest->window_end->format('Y-m-d H:i:s'));
        Event::factory()->create(['source_id' => $source->id, 'occurred_at' => '2026-03-29 12:00:00']);
        $this->assertSame(3, app(BuildDigest::class)->handle('2026-03-29')->snapshot['total_events']);
        $this->assertDatabaseCount('digests', 1);
    }

    public function test_delivery_is_disabled_by_default(): void
    {
        Queue::fake([SendDigest::class]);
        config(['watchpost.mail_enabled' => false]);
        $this->artisan('watchpost:digest', ['--date' => '2026-01-01', '--send' => true])->assertSuccessful();
        Queue::assertNothingPushed();
        $this->assertDatabaseCount('digests', 1);
    }

    public function test_sent_digest_is_not_emailed_again(): void
    {
        Mail::fake();
        config(['watchpost.mail_enabled' => true, 'watchpost.recipients' => ['operator@example.com']]);
        $digest = Digest::factory()->create();
        $job = new SendDigest($digest->id);
        $job->handle();
        $job->handle();
        Mail::assertSent(DailyDigest::class, 1);
        $this->assertNotNull($digest->fresh()->sent_at);
    }

    public function test_email_escapes_untrusted_messages(): void
    {
        $digest = Digest::factory()->make(['snapshot' => ['sources' => [], 'metrics' => [], 'total_events' => 1, 'alerts' => [['severity' => 2, 'source_id' => 'source', 'event_type' => 'auth.lockout', 'actor_ip' => '192.0.2.1', 'message' => '<script>alert(1)</script>']]]]);
        $html = (new DailyDigest($digest))->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_rejects_incomplete_or_invalid_calendar_days(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15T12:00:00Z'));
        $this->artisan('watchpost:digest', ['--date' => '2026-10-15'])->assertFailed();
        $this->assertDatabaseCount('digests', 0);
    }
}
