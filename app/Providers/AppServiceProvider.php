<?php

namespace App\Providers;

use App\Enums\GlobalRole;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Integrations\Fake\FakeFarmerVoiceProvider;
use App\Integrations\Fake\FakeInsightsProvider;
use App\Integrations\OpenAI\OpenAIFarmerVoiceProvider;
use App\Integrations\Satyukt\SatyuktInsightsProvider;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        $this->app->bind(FarmerVoiceProvider::class, function (Application $application): FarmerVoiceProvider {
            return match ((string) config('voice-assistance.provider')) {
                'fake' => $application->make(FakeFarmerVoiceProvider::class),
                'openai' => $application->make(OpenAIFarmerVoiceProvider::class),
                default => throw new InvalidArgumentException('Unknown voice assistance provider: '.config('voice-assistance.provider')),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewDetailedHealth', fn (User $user): bool => $user->hasRole(GlobalRole::PlatformAdmin->value));
        Gate::define('manageIntegrations', fn (User $user): bool => $user->hasRole(GlobalRole::PlatformAdmin->value));
        Gate::define('accessPlatform', fn (User $user): bool => $user->hasRole(GlobalRole::PlatformAdmin->value));

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

        RateLimiter::for('sync', function (Request $request): Limit {
            $farm = $request->route('farm');
            $key = is_object($farm) && method_exists($farm, 'getRouteKey')
                ? $farm->getRouteKey()
                : $farm;

            return Limit::perHour(6)->by((string) ($key ?? $request->ip()));
        });

        RateLimiter::for('diagnosis', fn (Request $request): Limit => Limit::perHour(10)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('voice-assistance', fn (Request $request): Limit => Limit::perHour(12)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
