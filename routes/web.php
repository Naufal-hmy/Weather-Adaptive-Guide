<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GuideController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [GuideController::class, 'index'])
        ->name('dashboard');

    Route::get('/smart-map', [GuideController::class, 'smartMap'])
        ->name('smart-map');

    Route::get('/api/nearby-recommendations', [GuideController::class, 'getNearbyRecommendations'])
        ->name('api.nearby-recommendations');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('admin/destinations', \App\Http\Controllers\Admin\DestinationController::class)->except(['create', 'show', 'edit']);
    Route::post('admin/weather/{city}', [GuideController::class, 'updateWeather'])->name('admin.weather.update');
});

require __DIR__.'/auth.php';
