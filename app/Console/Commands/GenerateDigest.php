<?php

namespace App\Console\Commands;

use App\Jobs\SendDigest;
use App\Services\BuildDigest;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateDigest extends Command
{
    protected $signature = 'watchpost:digest {--date= : Calendar day YYYY-MM-DD} {--send : Queue email delivery}';

    protected $description = 'Create an immutable digest for the previous local calendar day';

    public function handle(BuildDigest $builder): int
    {
        $date = $this->option('date') ?: CarbonImmutable::now(config('watchpost.timezone'))->subDay()->toDateString();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! CarbonImmutable::hasFormat($date, 'Y-m-d') || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)) || $date >= CarbonImmutable::now(config('watchpost.timezone'))->toDateString()) {
            $this->error('Provide a valid, completed calendar day (YYYY-MM-DD).');

            return self::FAILURE;
        }
        $digest = $builder->handle($date);
        $this->info('Digest stored: '.$digest->id.' ('.$digest->snapshot['total_events'].' events)');
        if ($this->option('send')) {
            if (! config('watchpost.mail_enabled') || ! config('watchpost.recipients')) {
                $this->warn('Email disabled or recipients missing; snapshot retained.');

                return self::SUCCESS;
            }
            SendDigest::dispatch($digest->id);
            $this->info('Delivery queued.');
        }

        return self::SUCCESS;
    }
}
