<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareDatabase extends Command
{
    protected $signature = 'watchpost:prepare-database';

    protected $description = 'Create the private Watchpost PostgreSQL schema before migrations';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->info('No schema setup needed for this database driver.');

            return self::SUCCESS;
        }
        if (config('database.connections.pgsql.search_path') !== 'watchpost') {
            $this->error('DB_SCHEMA must be watchpost.');

            return self::FAILURE;
        }
        DB::statement('CREATE SCHEMA IF NOT EXISTS watchpost');
        DB::statement('REVOKE ALL ON SCHEMA watchpost FROM PUBLIC');
        foreach (['anon', 'authenticated'] as $role) {
            if (DB::select('SELECT 1 FROM pg_roles WHERE rolname = ?', [$role])) {
                DB::statement('REVOKE ALL ON SCHEMA watchpost FROM "'.$role.'"');
                DB::statement('ALTER DEFAULT PRIVILEGES IN SCHEMA watchpost REVOKE ALL ON TABLES FROM "'.$role.'"');
            }
        }
        $this->info('Private watchpost schema is ready.');

        return self::SUCCESS;
    }
}
