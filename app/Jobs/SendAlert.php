<?php

namespace App\Jobs;

use App\Mail\SecurityAlert;
use App\Models\Alert;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Mail;

class SendAlert implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 100;

    public int $maxExceptions = 3;

    public int $uniqueFor = 86400;

    public function __construct(public string $alertId) {}

    public function uniqueId(): string
    {
        return $this->alertId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function middleware(): array
    {
        return [(new RateLimited('alert-mail'))->releaseAfter(60)];
    }

    public function handle(): void
    {
        if (! config('watchpost.mail_enabled') || ! config('watchpost.recipients')) {
            return;
        }
        $alert = Alert::findOrFail($this->alertId);
        if ($alert->notified_at) {
            return;
        }
        $event = Event::findOrFail($alert->event_id);
        Mail::to(config('watchpost.recipients'))->send(new SecurityAlert($alert, $event));
        $alert->update(['notified_at' => now()]);
    }
}
