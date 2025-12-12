<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherMapTest extends TestCase
{
    const TOKYO = 'Tokyo';
    const TEST_CITY = 'TestCity';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function getWeatherUrl(string $city, bool $cached = false): string
    {
        return $cached ? "/api/v1/weather/{$city}/cached" : "/api/v1/weather/{$city}";
    }

    public function test_required_city_parameter(): void
    {
        $this->getJson('/api/v1/weather/')->assertStatus(404); // Route requires {city}
    }

    public function test_required_city_cached_parameter(): void
    {
        $this->getJson('/api/v1/weather//cached')->assertStatus(404); // Route requires {city}
    }

    public function test_length_city_parameter(): void
    {
        $this->getJson('/api/v1/weather/' . str_repeat('a', 256))->assertStatus(400); // check {city} length validation
    }

    public function test_success_external_api_call(): void
    {
        $this->getJson($this->getWeatherUrl(self::TOKYO))->assertStatus(200)
            ->assertJsonFragment(['source' => 'external', 'city' => self::TOKYO]);
    }

    public function test_success_cached_api_call(): void
    {
        $this->getJson($this->getWeatherUrl(self::TOKYO, true))->assertStatus(200)
            ->assertJsonFragment(['source' => 'cache', 'city' => self::TOKYO]);
    }

    public function test_failed_city_external_api_call(): void
    {
        $this->getJson($this->getWeatherUrl(self::TEST_CITY))->assertStatus(404)
            ->assertJsonFragment(['message' => 'City not found']);
    }

    public function test_failed_city_cached_api_call(): void
    {
        $this->getJson($this->getWeatherUrl(self::TEST_CITY, true))->assertStatus(404)
            ->assertJsonFragment(['message' => 'City not found']);
    }

    public function test_rate_limit_call(): void
    {
        // AppServiceProvider  - weather-api - rate limit set to 10 per minute
        for ($i = 0; $i < 11; $i++) {
            $this->getJson($this->getWeatherUrl(self::TOKYO))->assertStatus($i < 10 ? 200 : 429)
            ->assertJsonFragment($i < 10 ? ['source' => 'external', 'city' => self::TOKYO] : ['message' => 'Too Many Attempts.']);
        }
    }

    public function test_fake_success_external_api_call()
    {
        Http::fake([
            '*' => Http::response([
                'cod' => 200,
                'name' => self::TOKYO,
                'main' => ['temp' => 100],
                'weather' => [['description' => 'overcast clouds']],
                'dt' => 1765551082
            ], 200)
        ]);

        $this->getJson($this->getWeatherUrl(self::TOKYO))->assertStatus(200)
            ->assertJson([
                'city' => self::TOKYO,
                'temperature' => 100,
                'weather description' => 'overcast clouds',
                'source' => 'external'
            ]);
    }
}
