<?php

namespace App\Services;

use App\Jobs\SendAlert;
use App\Models\Alert;
use App\Models\Event;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class IngestEvents
{
    public function handle(Source $source, array $payloads): array
    {
        return DB::transaction(function () use ($source, $payloads): array {
            $source = Source::whereKey($source->id)->lockForUpdate()->firstOrFail();
            abort_unless($source->enabled, 401);
            $result = ['accepted' => 0, 'duplicates' => 0, 'event_ids' => [], 'alerts_created' => 0];
            foreach ($payloads as $payload) {
                $hash = hash('sha256', json_encode($this->canonical($payload), JSON_THROW_ON_ERROR));
                $existing = Event::where('source_id', $source->id)->where('source_event_id', $payload['event_id'])->first();
                if ($existing) {
                    abort_unless(hash_equals($existing->payload_hash, $hash), 409, 'Event ID already used with a different payload.');
                    $result['duplicates']++;
                    $result['event_ids'][] = $existing->id;

                    continue;
                }
                $base = $source->severity_overrides[$payload['event_type']] ?? (config('watchpost.severity')[$payload['event_type']] ?? 4);
                $severity = max(1, min(5, $base, $payload['severity'] ?? 5));
                if ($payload['event_type'] === 'account.created' && ($payload['meta']['role'] ?? null) === 'super_admin') {
                    $severity = 1;
                }
                $event = Event::create([
                    'source_id' => $source->id, 'source_event_id' => $payload['event_id'], 'payload_hash' => $hash,
                    'event_type' => $payload['event_type'], 'occurred_at' => CarbonImmutable::parse($payload['timestamp'])->utc(),
                    'received_at' => now(), 'severity' => $severity, 'count' => $payload['count'] ?? 1,
                    ...array_intersect_key($payload, array_flip(['actor_ip', 'actor_email', 'actor_ua', 'actor_country', 'target', 'outcome', 'url_path', 'message'])),
                    'meta' => $this->redact($payload['meta'] ?? []),
                ]);
                if ($severity <= 2) {
                    $alert = Alert::create(['event_id' => $event->id, 'severity' => $severity]);
                    if (config('watchpost.mail_enabled')) {
                        SendAlert::dispatch($alert->id);
                    }
                    $result['alerts_created']++;
                }
                $result['accepted']++;
                $result['event_ids'][] = $event->id;
            }
            $source->update(['last_received_at' => now()]);

            return $result;
        }, 3);
    }

    private function canonical(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->canonical($item);
            }
        }

        return $value;
    }

    private function redact(array $value): array
    {
        foreach ($value as $key => &$item) {
            if (preg_match('/password|secret|token|authorization|cookie|api.?key/i', (string) $key)) {
                $item = '[REDACTED]';
            } elseif (is_array($item)) {
                $item = $this->redact($item);
            }
        }

        return $value;
    }
}
