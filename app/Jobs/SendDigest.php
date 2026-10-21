<?php

namespace App\Jobs;

use App\Mail\DailyDigest;
use App\Models\Digest;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDigest implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public function __construct(public string $digestId) {}

    public function uniqueId(): string
    {
        return $this->digestId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        if (! config('watchpost.mail_enabled') || ! config('watchpost.recipients')) {
            return;
        }
        $digest = Digest::findOrFail($this->digestId);
        if ($digest->sent_at) {
            return;
        }
        Mail::to(config('watchpost.recipients'))->send(new DailyDigest($digest));
        $digest->update(['sent_at' => now()]);
    }
}
