<?php

namespace Tests\Feature;

use App\Models\Source;
use App\Services\BuildDigest;
use App\Services\IngestEvents;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgresSmokeTest extends TestCase
{
    public function test_hosted_postgres_accepts_ingestion_and_digests_inside_a_rolled_back_transaction(): void
    {
        if (getenv('WATCHPOST_POSTGRES_SMOKE') !== '1' || DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Explicit PostgreSQL smoke opt-in required.');
        }
        $this->assertSame('watchpost', DB::selectOne('select current_schema() as name')->name);
        DB::beginTransaction();
        try {
            $source = Source::factory()->create(['token_hash' => hash('sha256', random_bytes(32))]);
            $payload = ['schema_version' => '1', 'source_id' => $source->id, 'event_id' => 'pg-smoke', 'event_type' => 'auth.lockout', 'timestamp' => '2001-01-01T12:00:00Z'];
            $result = app(IngestEvents::class)->handle($source, [$payload]);
            $this->assertSame(1, $result['alerts_created']);
            $this->assertSame(1, app(IngestEvents::class)->handle($source, [$payload])['duplicates']);
            $digest = app(BuildDigest::class)->handle('2001-01-01');
            $this->assertSame(1, $digest->snapshot['total_events']);
            $this->assertSame($digest->id, app(BuildDigest::class)->handle('2001-01-01')->id);
            $this->assertFalse(DB::selectOne("select has_schema_privilege('anon','watchpost','USAGE') as allowed")->allowed);
            $this->assertFalse(DB::selectOne("select has_schema_privilege('authenticated','watchpost','USAGE') as allowed")->allowed);
        } finally {
            DB::rollBack();
        }
    }
}
