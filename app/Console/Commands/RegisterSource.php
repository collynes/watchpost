<?php

namespace App\Console\Commands;

use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RegisterSource extends Command
{
    protected $signature = 'watchpost:source {id} {name} {--method=webhook}';

    protected $description = 'Register a source; webhook credentials are displayed once';

    public function handle(): int
    {
        $id = $this->argument('id');
        $method = $this->option('method');
        if (! preg_match('/^[a-z0-9][a-z0-9-]{0,99}$/', $id) || ! in_array($method, ['webhook', 'email'], true)) {
            $this->error('Use a lowercase source ID and method webhook or email.');

            return self::FAILURE;
        }
        if (Source::find($id)) {
            $this->error('Source already registered.');

            return self::FAILURE;
        }
        $token = $method === 'webhook' ? 'wpa_'.Str::random(48) : null;
        Source::create(['id' => $id, 'name' => $this->argument('name'), 'ingestion_method' => $method, 'token_hash' => $token ? hash('sha256', $token) : null]);
        $this->info('Source registered: '.$id);
        if ($token) {
            $this->line($token);
        }

        return self::SUCCESS;
    }
}
