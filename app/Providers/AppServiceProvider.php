<?php

namespace App\Providers;

use App\Enums\GlobalRole;
use App\Integrations\Contracts\CropDiagnosisProvider;
use App\Integrations\Contracts\FarmerVoiceProvider;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Integrations\Contracts\VoiceTranscriptionProvider;
use App\Integrations\Fake\FakeFarmerVoiceProvider;
use App\Integrations\Fake\FakeInsightsProvider;
use App\Integrations\NAtlas\NAtlasFarmerVoiceProvider;
use App\Integrations\NAtlas\NAtlasVoiceTranscriptionProvider;
use App\Integrations\OpenAI\OpenAICropDiagnosisProvider;
use App\Integrations\OpenAI\OpenAIVoiceTranscriptionProvider;
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
        $this->app->bind(VoiceTranscriptionProvider::class, function (Application $application): VoiceTranscriptionProvider {
            return match ((string) config('voice-assistance.transcription_provider')) {
                'n_atlas' => $application->make(NAtlasVoiceTranscriptionProvider::class),
                'openai' => $application->make(OpenAIVoiceTranscriptionProvider::class),
                default => throw new InvalidArgumentException('Unknown voice transcription provider: '.config('voice-assistance.transcription_provider')),
            };
        });

        $this->app->bind(FarmingInsightsProvider::class, function (Application $application): FarmingInsightsProvider {
            if ($application->isProduction()) {
                return match ((string) config('farming.provider')) {
                    'satyukt' => $application->make(SatyuktInsightsProvider::class),
                    default => throw new InvalidArgumentException('Unknown production farming provider: '.config('farming.provider')),
                };
            }

            return match ((string) config('farming.provider')) {
                'fake' => $application->make(FakeInsightsProvider::class),
                'satyukt' => $application->make(SatyuktInsightsProvider::class),
                default => throw new InvalidArgumentException('Unknown farming provider: '.config('farming.provider')),
            };
        });

        $this->app->bind(CropDiagnosisProvider::class, function (Application $application): CropDiagnosisProvider {
            if ($application->isProduction()) {
                return match ((string) config('diagnosis.provider')) {
                    'openai' => $application->make(OpenAICropDiagnosisProvider::class),
                    'satyukt' => $application->make(SatyuktInsightsProvider::class),
                    default => throw new InvalidArgumentException('Unknown production diagnosis provider: '.config('diagnosis.provider')),
                };
            }

            return match ((string) config('diagnosis.provider')) {
                'fake' => $application->make(FakeInsightsProvider::class),
                'openai' => $application->make(OpenAICropDiagnosisProvider::class),
                'satyukt' => $application->make(SatyuktInsightsProvider::class),
                default => throw new InvalidArgumentException('Unknown crop diagnosis provider: '.config('diagnosis.provider')),
            };
        });

        $this->app->bind(FarmerVoiceProvider::class, function (Application $application): FarmerVoiceProvider {
            if ($application->isProduction()) {
                return match ((string) config('voice-assistance.provider')) {
                    'n_atlas' => $application->make(NAtlasFarmerVoiceProvider::class),
                    default => throw new InvalidArgumentException('Unknown production voice assistance provider: '.config('voice-assistance.provider')),
                };
            }

            return match ((string) config('voice-assistance.provider')) {
                'fake' => $application->make(FakeFarmerVoiceProvider::class),
                'n_atlas' => $application->make(NAtlasFarmerVoiceProvider::class),
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
