@extends('layout')
@section('content')
<section class="login"><p class="muted">OPERATOR ACCESS</p><h1>Welcome to Watchpost</h1><p class="muted">Your security events, in one place.</p>
@foreach($errors->all() as $error)<p class="error">{{ $error }}</p>@endforeach
<form method="post" action="{{ route('login.submit') }}">@csrf<label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required><button style="width:100%;margin-top:24px">Sign in</button></form>
<p class="muted">Access is managed by your Watchpost administrator.</p></section>
@endsection
