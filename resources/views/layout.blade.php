<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Watchpost · Security operations</title>
    <link rel="stylesheet" href="{{ asset('watchpost.css') }}?v={{ filemtime(public_path('watchpost.css')) }}">
    <script src="{{ asset('watchpost.js') }}?v={{ filemtime(public_path('watchpost.js')) }}" defer></script>
</head>
<body class="@auth workspace @endauth">
<a class="skip-link" href="#main">Skip to content</a>
@auth
<aside class="sidebar" id="sidebar" aria-label="Primary navigation">
    <a class="brand" href="{{ route('dashboard') }}" aria-label="Watchpost overview"><span class="brand-mark">◈</span><span class="sidebar-label">WATCHPOST<span class="brand-subtitle">SECURITY OPERATIONS</span></span></a>
    <span class="nav-caption sidebar-label">WORKSPACE</span>
    <nav class="side-nav">
        <a href="{{ route('dashboard') }}" class="nav-item active" aria-current="page" title="Overview"><span class="nav-icon" aria-hidden="true">▦</span><span class="sidebar-label">Overview</span></a>
        <a href="{{ route('dashboard', ['status'=>'open']) }}#alerts" class="nav-item" title="Alerts"><span class="nav-icon" aria-hidden="true">⚑</span><span class="sidebar-label">Alerts</span><span class="nav-count sidebar-label">{{ $openCount ?? 0 }}</span></a>
        <a href="#event-stream" class="nav-item" title="Event stream"><span class="nav-icon" aria-hidden="true">≋</span><span class="sidebar-label">Event stream</span></a>
        <a href="#sources" class="nav-item" title="Sources"><span class="nav-icon" aria-hidden="true">▧</span><span class="sidebar-label">Sources</span></a>
        <a href="#digest" class="nav-item" title="Daily digest"><span class="nav-icon" aria-hidden="true">▤</span><span class="sidebar-label">Daily digest</span></a>
    </nav>
    <div class="sidebar-bottom"><span class="sidebar-label muted">Single organisation<br><small>Private workspace</small></span><button class="collapse-button" id="sidebar-toggle" aria-controls="sidebar" aria-expanded="true" aria-label="Collapse navigation" title="Collapse navigation"><span aria-hidden="true">⇤</span><span class="sidebar-label">Collapse</span></button></div>
</aside>
@endauth
<header class="topbar">
    @auth
    <div class="breadcrumb"><span>Workspace</span><span aria-hidden="true">/</span><strong>Overview</strong><span class="internal-label">INTERNAL</span></div>
    <div class="operator-controls">
        <button class="theme-toggle secondary" id="theme-toggle" title="Toggle light/dark theme" aria-label="Toggle theme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="15" height="15" aria-hidden="true" class="icon-moon"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="15" height="15" aria-hidden="true" class="icon-sun"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></button>
        <a class="notification-button" href="{{ route('dashboard', ['status'=>'open']) }}#alerts" aria-label="{{ $openCount ?? 0 }} open alerts" title="Open alerts"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>@if(($openCount ?? 0)>0)<span class="notification-count">{{ $openCount > 99 ? '99+' : $openCount }}</span>@endif</a>
        <details class="operator-menu"><summary aria-label="Operator menu for {{ auth()->user()->name }}"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><span class="operator-name">{{ auth()->user()->name }}<small>Operator</small></span><span aria-hidden="true">⌄</span></summary><div class="operator-dropdown"><span class="muted">{{ auth()->user()->email }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="secondary">Sign out</button></form></div></details>
    </div>
    @else
    <span class="brand"><span class="brand-mark">◈</span> WATCHPOST</span><span class="muted">Security operations / Internal</span>
    @endauth
</header>
<main id="main">@yield('content')</main>
<script>
(function(){
    var t='light';
    try { t=localStorage.getItem('wp-theme-wine')||'light'; } catch (_) {}
    if(t!=='light' && t!=='dark'){t='light';}
    document.documentElement.setAttribute('data-theme',t);
    function apply(theme){
        document.documentElement.setAttribute('data-theme',theme);
        try { localStorage.setItem('wp-theme-wine',theme); } catch (_) {}
        var btn=document.getElementById('theme-toggle');
        if(btn){btn.setAttribute('aria-label','Switch to '+(theme==='dark'?'light':'dark')+' theme');}
    }
    apply(t);
    document.addEventListener('DOMContentLoaded',function(){
        var btn=document.getElementById('theme-toggle');
        if(btn){btn.addEventListener('click',function(){
            var cur=document.documentElement.getAttribute('data-theme')||'light';
            apply(cur==='dark'?'light':'dark');
        });}
    });
})();
</script>
</body></html>
