<?php

namespace App\Http\Controllers;

use App\Services\WeatherMap\WeatherMap;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Log;

class WeatherController extends Controller
{
    private WeatherMap $weatherMap;

    public function __construct(WeatherMap $weatherMap)
    {
        $this->weatherMap = $weatherMap;
    }

    public function getWeather(string $city, bool $useCache = false): JsonResponse
    {
        $validator = Validator::make(
            ['city' => $city],
            ['city' => 'required|string|max:255']
        );

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid city parameter'], 400);
        }

        // Use the WeatherMap service to fetch weather data
        try {
            return $this->weatherMap->getWeather(city: $city, useCache: $useCache);
        } catch (Exception $exception) {
            Log::error('Error fetching weather data: ' . $exception);
            return response()->json(['error' => 'Unable to fetch weather data'], 500);
        }
    }

    public function getCachedWeather(string $city): JsonResponse
    {
        return $this->getWeather(city: $city, useCache: true);
    }
}
