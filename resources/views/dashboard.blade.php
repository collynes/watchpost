@extends('layout')
@section('content')
<div class="page-heading dashboard-heading">
    <div><p class="eyebrow">OPERATIONS OVERVIEW</p><h1>Security watch<span class="heading-dot" aria-hidden="true"></span></h1><p class="muted">{{ $windowStart->setTimezone(config('watchpost.timezone'))->format('M j, H:i') }} – {{ $windowEnd->setTimezone(config('watchpost.timezone'))->format('M j, H:i') }} <span class="mono">{{ config('watchpost.timezone') }}</span></p></div>
    <div class="refresh-control"><span class="muted">Updated {{ now(config('watchpost.timezone'))->format('H:i') }}</span><a class="button secondary" href="{{ route('dashboard', request()->query()) }}">↻ <span>Refresh</span></a></div>
</div>
<div class="stats-grid compact-stats">
    <div class="card stat"><span class="stat-label">Events <span class="muted">/ {{ $range }}</span></span><div class="number">{{ number_format($eventCount) }}</div><small>Occurrences in selected range</small></div>
    <div class="card stat {{ $openCount ? 'stat-attention' : '' }}"><span class="stat-label">Open alerts<span class="tiny-dot {{ $openCount ? 'dot-red' : 'dot-neutral' }}" aria-hidden="true"></span></span><div class="number">{{ number_format($openCount) }}</div><small>Across all time ranges</small></div>
    <div class="card stat"><span class="stat-label">Connected sources</span><div class="number">{{ $sources->where('enabled', true)->count() }}</div><small>{{ $sources->count() }} registered · {{ $sources->where('enabled', false)->count() }} paused</small></div>
    <div class="card stat digest-stat" id="digest"><span class="stat-label">Latest digest</span><div class="digest-date mono">{{ $digest?->report_date ?? '—' }}</div><small>{{ $digest ? number_format((int) ($digest->snapshot['total_events'] ?? 0)).' events · '.number_format(count($digest->snapshot['alerts'] ?? [])).' alerts' : 'No report yet' }}</small><small>{{ $digest ? ($digest->sent_at ? 'Email sent' : 'Saved · email pending') : 'No report yet' }}</small></div>
</div>
<section class="visual-overview" aria-label="Event charts">
    <article class="chart-card timeline-card"><div class="chart-heading"><div><h2>Event activity</h2><p class="muted">{{ $range === '30d' ? 'Daily' : 'Hourly' }} event volume · {{ number_format($eventCount) }} total</p></div><span class="chart-key"><i></i>Events</span></div>
        <div class="timeline-chart"><div class="chart-scale"><span>{{ number_format($timeline['maximum']) }}</span><span>{{ number_format((int) round($timeline['maximum'] / 2)) }}</span><span>0</span></div><svg viewBox="0 0 720 112" preserveAspectRatio="none" role="img" aria-label="Event volume over selected time range"><defs><linearGradient id="wine-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#842c48" stop-opacity=".25"/><stop offset="100%" stop-color="#842c48" stop-opacity="0"/></linearGradient></defs><path class="chart-area" d="{{ $timeline['area'] }}"/><polyline class="chart-line" points="{{ $timeline['points'] }}"/></svg></div>
        <div class="chart-labels">@foreach($timeline['labels'] as $label)<span>{{ $label }}</span>@endforeach</div>
    </article>
    <article class="chart-card severity-chart"><div class="chart-heading"><div><h2>Severity mix</h2><p class="muted">Events by severity</p></div></div>
        <div class="severity-stack" role="img" aria-label="Severity breakdown: @foreach($severityDistribution as $item) severity {{ $item['severity'] }}: {{ $item['count'] }}; @endforeach">
            @foreach($severityDistribution as $item)
                @php($share = $eventCount > 0 ? $item['count'] / $eventCount * 100 : 0)
                <span class="stack-segment sev-fill-{{ $item['severity'] }}" style="width:{{ $share }}%"></span>
            @endforeach
        </div>
        <div class="severity-legend">@foreach($severityDistribution as $item)<div class="severity-legend-row"><span><i class="tiny-dot severity-dot-{{ $item['severity'] }}"></i> SEV {{ $item['severity'] }}</span><strong>{{ number_format($item['count']) }}</strong></div>@endforeach</div>
    </article>
    <article class="chart-card top-types-chart"><div class="chart-heading"><div><h2>Top event types</h2><p class="muted">Most frequent in this range</p></div></div>
        @forelse($topEventTypes as $type)
            @php($share = $topEventTypes->first()->total > 0 ? $type->total / $topEventTypes->first()->total * 100 : 0)
            <div class="type-row"><div class="type-label"><span class="mono">{{ $type->event_type }}</span><strong>{{ number_format($type->total) }}</strong></div><div class="type-track"><span style="width:{{ $share }}%"></span></div></div>
        @empty <p class="chart-empty">No events in this range.</p> @endforelse
    </article>
</section>
<section class="source-strip" id="sources" aria-label="Connected source activity"><div class="source-strip-heading"><h2>Sources</h2><span>Last event received</span></div><div class="source-strip-items">@forelse($sources as $source)
    @php($age = $source->last_received_at?->diffInSeconds(now()))
    @php($activity = !$source->enabled || $age === null ? 'neutral' : ($age <= 3600 ? 'green' : ($age <= 86400 ? 'amber' : 'red')))
    <a class="source-chip" href="{{ route('dashboard', array_merge(request()->except('page'), ['source'=>$source->id])) }}#event-stream"><i class="tiny-dot dot-{{ $activity }}" aria-hidden="true"></i><span>{{ $source->name }}</span><small>{{ $source->last_received_at?->diffForHumans() ?? 'No events yet' }}</small></a>
@empty <span class="muted">No sources registered.</span> @endforelse</div></section>
<form class="filter-panel dashboard-filters" method="get" action="{{ route('dashboard') }}" aria-label="Filter dashboard">
    <div class="filters-heading"><div><h2>Filters</h2><p class="muted">Range shapes activity; source, severity, and search refine results; status filters alerts.</p></div><a class="reset-filters" href="{{ route('dashboard') }}">Reset</a></div>
    <div class="filter-controls">
        <label class="filter-control"><span>Date range</span><select name="range" id="range-select"><option value="1h" @selected($range === '1h')>Last hour</option><option value="24h" @selected($range === '24h')>Last 24 hours</option><option value="7d" @selected($range === '7d')>Last 7 days</option><option value="30d" @selected($range === '30d')>Last 30 days</option><option value="custom" @selected($range === 'custom')>Custom dates</option></select></label>
        <label class="filter-control"><span>Source</span><select name="source"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source->id }}" @selected(($filters['source'] ?? '') === $source->id)>{{ $source->name }}</option>@endforeach</select></label>
        <label class="filter-control"><span>Severity</span><select name="severity"><option value="">All severities</option>@foreach(range(1,5) as $severity)<option value="{{ $severity }}" @selected(($filters['severity'] ?? '') == $severity)>SEV {{ $severity }}</option>@endforeach</select></label>
        <label class="filter-control"><span>Alert status</span><select name="status">@foreach(['open','acknowledged','resolved'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? 'open') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
        <label class="filter-control search-filter"><span>Search events</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Event, IP, email, message" maxlength="120"></label>
        <div class="filter-dates" id="custom-date-fields" @if($range !== 'custom') hidden @endif>
            <label class="filter-control"><span>From</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
            <label class="filter-control"><span>To</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        </div>
        <button class="filter-submit">Apply filters <span aria-hidden="true">→</span></button>
    </div>
</form>
<section class="alerts-section" id="alerts" aria-labelledby="alerts-heading"><div class="section-heading"><div><h2 id="alerts-heading">{{ ucfirst($filters['status'] ?? 'open') }} alerts <span class="count-label">{{ $alerts->count() }}</span></h2><p class="muted">Open alerts stay visible across dates; history follows the selected range.</p></div></div>
<div class="alert-list">@forelse($alerts as $alert)<article class="alert-card severity-{{ $alert->severity }}"><div class="alert-main"><div class="alert-title"><span class="badge sev{{ $alert->severity }}">@if($alert->severity === 1)<span class="tiny-dot dot-red pulse" aria-hidden="true"></span>@endif SEV {{ $alert->severity }}</span><strong class="mono">{{ $alert->event_type }}</strong><span class="urgency-label">{{ $alert->severity === 1 ? 'Critical' : 'High' }}</span></div><p class="alert-message">{{ $alert->message ?: 'No summary provided.' }}</p><div class="alert-meta"><span>{{ $alert->source_id }}</span><span class="mono">{{ $alert->actor_ip ?? 'IP unavailable' }}</span><span>{{ $alert->created_at->diffForHumans() }}</span></div></div>@if($alert->status !== 'resolved')<div class="actions">@foreach(['acknowledged'=>'Acknowledge','resolved'=>'Resolve'] as $status=>$label)@if($status !== $alert->status)<form method="post" action="{{ route('alerts.update',$alert) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $status }}"><button class="{{ $status === 'resolved' ? 'secondary' : 'primary' }}">{{ $label }}</button></form>@endif @endforeach</div>@else<span class="muted">Resolved</span>@endif</article>@empty<div class="empty">No {{ $filters['status'] ?? 'open' }} alerts match these filters.</div>@endforelse</div>
</section>
<section class="event-section" id="event-stream" aria-labelledby="events-heading"><div class="section-heading"><div><h2 id="events-heading">Event stream <span class="count-label">{{ $events->total() }}</span></h2><p class="muted">Latest matching events · {{ $events->perPage() }} per page</p></div><span class="stream-label"><span class="tiny-dot dot-neutral" aria-hidden="true"></span>Stored events</span></div><div class="table-scroll"><table><thead><tr><th>Severity</th><th>Event / source</th><th>Actor</th><th>Count</th><th>Occurred <span class="muted">({{ config('watchpost.timezone') }})</span></th></tr></thead><tbody>@forelse($events as $event)<tr class="event-row {{ $event->severity === 1 ? 'critical-row' : '' }}"><td><span class="badge sev{{ $event->severity }}">@if($event->severity === 1)<span class="tiny-dot dot-red pulse" aria-hidden="true"></span>@endif SEV {{ $event->severity }}</span></td><td><strong class="mono event-type">{{ $event->event_type }}</strong><small>{{ $event->source_id }}</small><div class="event-message">{{ $event->message }}</div></td><td class="actor-cell"><span class="mono">{{ $event->actor_ip ?? '—' }}</span><small>{{ $event->actor_email }}</small></td><td class="mono">{{ $event->count }}</td><td class="mono timestamp">{{ $event->occurred_at->setTimezone(config('watchpost.timezone'))->format('Y-m-d') }}<small class="mono">{{ $event->occurred_at->setTimezone(config('watchpost.timezone'))->format('H:i:s') }}</small></td></tr>@empty<tr><td colspan="5" class="empty">No events match these filters.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $events->links() }}</div></section>
<footer class="page-footer"><span>WATCHPOST <span class="muted">/ Internal security operations</span></span><span>Times in {{ config('watchpost.timezone') }}</span></footer>
@endsection
