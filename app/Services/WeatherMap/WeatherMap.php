<?php

namespace App\Services\WeatherMap;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherMap
{
    private readonly string $apiKey;
    private readonly string $endPoint;

    public function __construct()
    {
        $this->apiKey = config('weathermap.API_KEY');
        $this->endPoint = config('weathermap.ENDPOINT');
    }

    public function getWeather(string $city, bool $useCache = true): JsonResponse
    {
        $cacheKey = 'weather_city_' . strtolower($city);

        // Check cache first
        if ($useCache && $cached = Cache::get($cacheKey)) {
            return $this->formatResponse($cached, 'cache', 200);
        }

        // Fetch from API
        $response = Http::get("{$this->endPoint}/weather", [
            'q' => $city,
            'appid' => $this->apiKey,
        ]);

        if (!$response->successful()) {
            return $this->handleError($response->status());
        }

        $data = $response->json();

        $cod = $data['cod'] ?? 500;

        return match ($cod) {
            200 => $this->handleSuccess($data, $cacheKey, $useCache),
            401 => $this->error('Invalid API key', 401),
            404 => $this->error('City not found', 404),
            429 => $this->error('Too Many Attempts.', 429),
            default => $this->error('Unexpected error', $cod)
        };
    }

    /**
     * Handle successful weather response
     */
    private function handleSuccess(array $data, string $cacheKey, bool $useCache): JsonResponse
    {
        $result = [
            'city' => $data['name'] ?? null,
            'temperature' => $data['main']['temp'] ?? null,
            'weather description' => $data['weather'][0]['description'] ?? null,
            'timestamp' => $data['dt'] ?? null,
            'datetime' => isset($data['dt']) ? Carbon::createFromTimestamp($data['dt'])->toDateTimeString() : null,
            'retrieved_at' => Carbon::now()->toDateTimeString(),
        ];

        if ($useCache) {
            Cache::put($cacheKey, $result, now()->plus(minutes: 10));
            return $this->formatResponse($result, 'cache', 200);
        }

        return $this->formatResponse($result, 'external', 200);
    }

    /**
     * Format JSON response with source
     */
    private function formatResponse(array $data, string $source, int $status = 200): JsonResponse
    {
        return response()->json(array_merge($data, ['source' => $source]), $status);
    }

    /**
     * Generic error response
     */
    private function error(string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }

    /**
     * Handle non-success HTTP status codes
     */
    private function handleError(int $status): JsonResponse
    {
        return match ($status) {
            401 => $this->error('Invalid API key', 401),
            404 => $this->error('City not found', 404),
            429 => $this->error('Too Many Attempts.', 429),
            default => $this->error('Unexpected API error', $status)
        };
    }
}