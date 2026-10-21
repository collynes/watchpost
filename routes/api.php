<?php

use App\Http\Controllers\IngestController;
use App\Http\Middleware\AuthenticateSource;
use Illuminate\Support\Facades\Route;

Route::middleware([AuthenticateSource::class, 'throttle:ingestion'])->group(function () {
    Route::post('ingest/v1/events', IngestController::class)->name('ingest.single');
    Route::post('ingest/v1/events/batch', IngestController::class)->name('ingest.batch');
});
