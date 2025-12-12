<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

/**
 * V1 API Routes - all routes defined here will be prefixed with /api/v1
 * This is configured in bootstrap/app.php
 * V1 versions allows for future API versions to be created without breaking existing clients
 */

Route::prefix('v1')->group(function () {
    Route::middleware(['throttle:weather-api'])->group(function () { // Apply rate limiting middleware
        Route::get('/weather/{city}', [WeatherController::class, 'getWeather']);
        Route::get('/weather/{city}/cached', [WeatherController::class, 'getCachedWeather']);
    });
});