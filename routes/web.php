<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'login')->middleware('guest')->name('login');
Route::post('/login', [DashboardController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [DashboardController::class, 'logout'])->name('logout');
    Route::patch('/alerts/{alert}', [DashboardController::class, 'updateAlert'])->name('alerts.update');
});
