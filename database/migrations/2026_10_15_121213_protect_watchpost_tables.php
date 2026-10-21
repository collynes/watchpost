<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach (['users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations', 'sources', 'events', 'alerts', 'digests'] as $table) {
            DB::statement('ALTER TABLE "'.$table.'" ENABLE ROW LEVEL SECURITY');
            DB::statement('REVOKE ALL ON "'.$table.'" FROM PUBLIC');
        }
        foreach (['anon', 'authenticated'] as $role) {
            if (DB::select('SELECT 1 FROM pg_roles WHERE rolname = ?', [$role])) {
                DB::statement('REVOKE ALL ON ALL TABLES IN SCHEMA watchpost FROM "'.$role.'"');
            }
        }
    }

    public function down(): void
    {
        // Keep protections enabled during rollback; table ownership allows migrations.
    }
};
