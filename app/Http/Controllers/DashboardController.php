<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Digest;
use App\Models\Event;
use App\Models\Source;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
        }
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'source' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', 'integer', 'between:1,5'],
            'status' => ['nullable', Rule::in(['open', 'acknowledged', 'resolved'])],
            'q' => ['nullable', 'string', 'max:120'],
            'range' => ['nullable', Rule::in(['1h', '24h', '7d', '30d', 'custom'])],
            'from' => ['nullable', 'date', 'required_if:range,custom', 'before_or_equal:to'],
            'to' => ['nullable', 'date', 'required_if:range,custom', 'after_or_equal:from'],
        ]);

        $timezone = config('watchpost.timezone', 'UTC');
        $range = $filters['range'] ?? '24h';
        if ($range === 'custom') {
            $windowStart = CarbonImmutable::parse($filters['from'], $timezone)->startOfDay()->utc();
            $windowEnd = CarbonImmutable::parse($filters['to'], $timezone)->endOfDay()->utc();
        } else {
            $windowEnd = now('UTC')->toImmutable();
            $windowStart = match ($range) {
                '1h' => $windowEnd->subHour(),
                '7d' => $windowEnd->subDays(7),
                '30d' => $windowEnd->subDays(30),
                default => $windowEnd->subDay(),
            };
        }

        $eventScope = fn () => Event::query()
            ->where('occurred_at', '>=', $windowStart)
            ->where('occurred_at', '<=', $windowEnd)
            ->when($filters['source'] ?? null, fn ($query, $source) => $query->where('source_id', $source))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['q'] ?? null, function ($query, $term): void {
                $query->where(function ($match) use ($term): void {
                    $pattern = '%'.mb_strtolower($term).'%';
                    $match->whereRaw('LOWER(event_type) LIKE ?', [$pattern])
                        ->orWhereRaw("LOWER(COALESCE(message, '')) LIKE ?", [$pattern])
                        ->orWhereRaw("LOWER(COALESCE(actor_ip, '')) LIKE ?", [$pattern])
                        ->orWhereRaw("LOWER(COALESCE(actor_email, '')) LIKE ?", [$pattern]);
                });
            });

        $events = $eventScope()->orderByDesc('received_at')->paginate(20)->withQueryString();
        $alertQuery = Alert::query()
            ->join('events', 'alerts.event_id', '=', 'events.id')
            ->select('alerts.*', 'events.source_id', 'events.event_type', 'events.message', 'events.actor_ip')
            ->where('alerts.status', $filters['status'] ?? 'open')
            ->when(($filters['status'] ?? 'open') !== 'open', fn ($query) => $query->whereBetween('events.occurred_at', [$windowStart, $windowEnd]))
            ->orderBy('alerts.severity')->orderByDesc('alerts.created_at')
            ->when($filters['source'] ?? null, fn ($query, $source) => $query->where('events.source_id', $source))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('alerts.severity', $severity))
            ->when($filters['q'] ?? null, function ($query, $term): void {
                $pattern = '%'.mb_strtolower($term).'%';
                $query->where(function ($match) use ($pattern): void {
                    $match->whereRaw('LOWER(events.event_type) LIKE ?', [$pattern])
                        ->orWhereRaw("LOWER(COALESCE(events.message, '')) LIKE ?", [$pattern])
                        ->orWhereRaw("LOWER(COALESCE(events.actor_ip, '')) LIKE ?", [$pattern]);
                });
            });

        $bucketExpression = DB::getDriverName() === 'pgsql'
            ? ($range === '30d' ? "date_trunc('day', occurred_at)" : ($range === '1h' ? "date_trunc('minute', occurred_at)" : "date_trunc('hour', occurred_at)"))
            : ($range === '30d' ? "strftime('%Y-%m-%d 00:00:00', occurred_at)" : ($range === '1h' ? "strftime('%Y-%m-%d %H:%M:00', occurred_at)" : "strftime('%Y-%m-%d %H:00:00', occurred_at)"));
        $timeBuckets = $eventScope()->selectRaw("$bucketExpression as bucket, SUM(count) as total")
            ->groupByRaw($bucketExpression)->orderBy('bucket')->get();
        $timeline = $this->timeline($timeBuckets, $windowStart, $windowEnd, $range === '30d', $timezone);

        $severityRows = $eventScope()->selectRaw('severity, SUM(count) as total')->groupBy('severity')->pluck('total', 'severity');
        $severityDistribution = collect(range(1, 5))->map(fn (int $severity) => [
            'severity' => $severity,
            'count' => (int) ($severityRows[$severity] ?? 0),
        ])->all();
        $topEventTypes = $eventScope()->selectRaw('event_type, SUM(count) as total')
            ->groupBy('event_type')->orderByDesc('total')->limit(5)->get();
        $totalEvents = $eventScope()->sum('count');

        return view('dashboard', [
            'sources' => Source::orderBy('name')->get(),
            'events' => $events,
            'alerts' => $alertQuery->limit(50)->get(),
            'digest' => Digest::latest('report_date')->first(),
            'eventCount' => $totalEvents,
            'openCount' => Alert::where('status', 'open')->count(),
            'filters' => $filters,
            'range' => $range,
            'windowStart' => $windowStart,
            'windowEnd' => $windowEnd,
            'timeline' => $timeline,
            'severityDistribution' => $severityDistribution,
            'topEventTypes' => $topEventTypes,
        ]);
    }

    private function timeline(Collection $buckets, CarbonImmutable $start, CarbonImmutable $end, bool $daily, string $timezone): array
    {
        $values = array_fill(0, 24, 0);
        $duration = max(1, $end->getTimestamp() - $start->getTimestamp());
        foreach ($buckets as $bucket) {
            $time = CarbonImmutable::parse($bucket->bucket, 'UTC');
            $index = min(23, max(0, (int) floor((($time->getTimestamp() - $start->getTimestamp()) / $duration) * 24)));
            $values[$index] += (int) $bucket->total;
        }

        $maximum = max(1, ...$values);
        $points = [];
        foreach ($values as $index => $value) {
            $x = round(15 + ($index * 690 / 23), 1);
            $y = round(94 - ($value / $maximum * 72), 1);
            $points[] = "$x,$y";
        }
        $pointString = implode(' ', $points);
        $area = 'M 15,108 L '.$pointString.' L 705,108 Z';
        $labels = $daily
            ? [
                $start->setTimezone($timezone)->format('M j'),
                $start->addDays(10)->setTimezone($timezone)->format('M j'),
                $start->addDays(20)->setTimezone($timezone)->format('M j'),
                $end->setTimezone($timezone)->format('M j'),
            ]
            : [
                $start->setTimezone($timezone)->format('M j H:i'),
                $start->addSeconds((int) ($duration / 3))->setTimezone($timezone)->format('M j H:i'),
                $start->addSeconds((int) ($duration * 2 / 3))->setTimezone($timezone)->format('M j H:i'),
                $end->setTimezone($timezone)->format('M j H:i'),
            ];

        return ['values' => $values, 'points' => $pointString, 'area' => $area, 'maximum' => $maximum, 'labels' => $labels];
    }

    public function updateAlert(Request $request, Alert $alert): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['acknowledged', 'resolved'])]]);
        DB::transaction(function () use ($alert, $data, $request): void {
            $alert = Alert::whereKey($alert->id)->lockForUpdate()->firstOrFail();
            abort_if($alert->status === 'resolved' && $data['status'] !== 'resolved', 409, 'Resolved alerts cannot be acknowledged.');
            if ($alert->status === $data['status']) {
                return;
            }
            $alert->update(['status' => $data['status'], $data['status'] === 'resolved' ? 'resolved_by' : 'acknowledged_by' => $request->user()->id, $data['status'] === 'resolved' ? 'resolved_at' : 'acknowledged_at' => now()]);
        });

        return redirect()->route('dashboard');
    }
}
