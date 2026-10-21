<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Digest;
use App\Models\Event;
use App\Models\Source;
use Carbon\CarbonImmutable;

class BuildDigest
{
    public function handle(string $date): Digest
    {
        $timezone = config('watchpost.timezone');
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);
        $end = $start->addDay();
        $events = Event::where('occurred_at', '>=', $start->utc())->where('occurred_at', '<', $end->utc())->get();
        $sources = Source::orderBy('name')->get()->map(function (Source $source) use ($events): array {
            $items = $events->where('source_id', $source->id);

            return ['id' => $source->id, 'name' => $source->name, 'events' => $items->sum('count'), 'high_severity_events' => $items->where('severity', '<=', 2)->sum('count')];
        })->all();
        $metrics = $events->groupBy('event_type')->map(fn ($items) => $items->sum('count'))->all();
        $alerts = Alert::whereIn('event_id', $events->pluck('id'))->whereIn('status', ['open', 'acknowledged'])->orderBy('severity')->get()->map(function (Alert $alert) use ($events): array {
            $event = $events->firstWhere('id', $alert->event_id);

            return ['id' => $alert->id, 'severity' => $alert->severity, 'source_id' => $event->source_id, 'event_type' => $event->event_type, 'message' => $event->message, 'actor_ip' => $event->actor_ip, 'status' => $alert->status];
        })->all();

        return Digest::firstOrCreate(['report_date' => $date, 'timezone' => $timezone], [
            'window_start' => $start->utc(), 'window_end' => $end->utc(),
            'snapshot' => ['sources' => $sources, 'metrics' => $metrics, 'alerts' => $alerts, 'total_events' => $events->sum('count')],
        ]);
    }
}
