<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('alert-mail', fn (object $job) => Limit::perMinute(10)->by('watchpost-alerts'));
        RateLimiter::for('ingestion', fn (Request $request) => Limit::perMinute(60)->by(hash('sha256', $request->bearerToken() ?? $request->ip())));
    }
}
