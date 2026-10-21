<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Watchpost digest</title></head>
<body style="font-family:Arial,sans-serif;color:#172b3a;max-width:720px;margin:32px auto">
<h1>Watchpost Security Digest</h1>
<p>{{ $digest->report_date }} · {{ $digest->timezone }} · Previous calendar day</p>
<p><strong>{{ $digest->snapshot['total_events'] }} events</strong> · {{ count($digest->snapshot['alerts']) }} unresolved alerts from this day</p>
<h2>Sources</h2><table cellpadding="10"><tr><th>Application</th><th>Events</th><th>SEV 1–2 occurrences</th></tr>
@foreach($digest->snapshot['sources'] as $source)<tr><td>{{ $source['name'] }}</td><td>{{ $source['events'] }}</td><td>{{ $source['high_severity_events'] }}</td></tr>@endforeach
</table><h2>Activity</h2><ul>
@forelse($digest->snapshot['metrics'] as $type=>$count)<li>{{ $type }}: {{ $count }}</li>@empty<li>No events recorded for this day.</li>@endforelse
</ul><h2>Alerts</h2>
@forelse($digest->snapshot['alerts'] as $alert)<p><strong>SEV {{ $alert['severity'] }} · {{ $alert['source_id'] }}</strong><br>{{ $alert['event_type'] }} · {{ $alert['actor_ip'] }}<br>{{ $alert['message'] }}</p>@empty<p>No unresolved high-severity alerts from this day.</p>@endforelse
</body></html>
