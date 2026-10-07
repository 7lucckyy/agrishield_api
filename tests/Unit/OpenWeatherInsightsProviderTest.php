<?php

declare(strict_types=1);

use App\Exceptions\Provider\ProviderRateLimited;
use App\Integrations\OpenWeather\OpenWeatherInsightsProvider;
use App\Models\Farm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('farming.providers.openweather', [
        'base_url' => 'https://weather.test',
        'api_key' => 'test-key',
        'connect_timeout' => 1,
        'read_timeout' => 2,
        'cache_seconds' => 60,
    ]);
    Cache::flush();
});

test('it normalizes OpenWeather forecast data without exposing provider payload as fake data', function (): void {
    Http::fake([
        'weather.test/forecast*' => Http::response([
            'list' => [
                [
                    'dt' => 1_759_312_800,
                    'main' => ['temp' => 28.0, 'humidity' => 71],
                    'rain' => ['3h' => 1.2],
                    'pop' => 0.4,
                    'wind' => ['speed' => 3.2, 'deg' => 180],
                    'clouds' => ['all' => 65],
                    'weather' => [['id' => 501]],
                ],
                [
                    'dt' => 1_759_323_600,
                    'main' => ['temp' => 31.0, 'humidity' => 61],
                    'pop' => 0.1,
                    'wind' => ['speed' => 4.2, 'deg' => 190],
                    'clouds' => ['all' => 45],
                    'weather' => [['id' => 800]],
                ],
            ],
        ]),
    ]);
    $farm = Farm::factory()->create([
        'centroid_latitude' => 6.5244,
        'centroid_longitude' => 3.3792,
    ]);

    $weather = app(OpenWeatherInsightsProvider::class)->fetchWeather($farm);

    expect($weather->source)->toBe('openweather')
        ->and($weather->days)->toHaveCount(1)
        ->and($weather->days[0]->temperatureMin)->toBe(28.0)
        ->and($weather->days[0]->temperatureMax)->toBe(31.0)
        ->and($weather->days[0]->rainfall)->toBe(1.2)
        ->and($weather->days[0]->humidity)->toBe(66.0);
    Http::assertSentCount(1);
});

test('it surfaces an OpenWeather rate limit instead of falling back to fake results', function (): void {
    Http::fake(['weather.test/forecast*' => Http::response([], 429)]);
    $farm = Farm::factory()->create([
        'centroid_latitude' => 6.5244,
        'centroid_longitude' => 3.3792,
    ]);

    expect(fn (): mixed => app(OpenWeatherInsightsProvider::class)->fetchWeather($farm))
        ->toThrow(ProviderRateLimited::class);
});
