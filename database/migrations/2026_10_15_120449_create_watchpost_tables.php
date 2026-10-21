<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('name');
            $table->string('ingestion_method')->default('webhook');
            $table->string('token_hash', 64)->nullable()->unique();
            $table->boolean('enabled')->default(true);
            $table->json('severity_overrides')->nullable();
            $table->timestampTz('last_received_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source_id', 100);
            $table->foreign('source_id')->references('id')->on('sources')->restrictOnDelete();
            $table->string('source_event_id', 128);
            $table->string('payload_hash', 64);
            $table->string('event_type', 100);
            $table->timestampTz('occurred_at');
            $table->timestampTz('received_at');
            $table->unsignedSmallInteger('severity');
            $table->unsignedInteger('count')->default(1);
            $table->string('actor_ip', 45)->nullable();
            $table->string('actor_email')->nullable();
            $table->text('actor_ua')->nullable();
            $table->string('actor_country', 2)->nullable();
            $table->string('target')->nullable();
            $table->string('outcome', 20)->nullable();
            $table->text('url_path')->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->unique(['source_id', 'source_event_id']);
            $table->index(['occurred_at', 'severity']);
            $table->index(['source_id', 'occurred_at']);
        });
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('severity');
            $table->string('status')->default('open');
            $table->timestampTz('acknowledged_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users');
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestampTz('notified_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'created_at']);
        });
        Schema::create('digests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('report_date');
            $table->string('timezone');
            $table->timestampTz('window_start');
            $table->timestampTz('window_end');
            $table->json('snapshot');
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();
            $table->unique(['report_date', 'timezone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digests');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('events');
        Schema::dropIfExists('sources');
    }
};
