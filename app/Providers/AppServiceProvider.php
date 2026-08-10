<?php

namespace App\Providers;

use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Integrations\Fake\FakeInsightsProvider;
use App\Integrations\Satyukt\SatyuktInsightsProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FarmingInsightsProvider::class, function (Application $application): FarmingInsightsProvider {
            return match ((string) config('farming.provider')) {
                'fake' => $application->make(FakeInsightsProvider::class),
                'satyukt' => $application->make(SatyuktInsightsProvider::class),
                default => throw new InvalidArgumentException('Unknown farming provider: '.config('farming.provider')),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function (): Password {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });

        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip()));

        RateLimiter::for('general', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('writes', fn (Request $request): Limit => Limit::perMinute(30)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
