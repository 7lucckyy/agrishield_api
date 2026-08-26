<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Health;

use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\IntegrationAccount;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class ShowDetailedHealthController extends Controller
{
    public function __invoke(Request $request, FarmingInsightsProvider $provider): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        Gate::authorize('viewDetailedHealth');

        $databaseReachable = $this->databaseReachable();
        $cacheReachable = $this->cacheReachable();
        $providerHealth = $provider->healthCheck();
        $lastSuccessful = SyncRun::query()->where('status', SyncStatus::Succeeded)
            ->selectRaw('sync_type, MAX(completed_at) AS completed_at')
            ->groupBy('sync_type')
            ->pluck('completed_at', 'sync_type');

        return response()->json(['data' => [
            'database' => ['reachable' => $databaseReachable],
            'cache' => ['reachable' => $cacheReachable],
            'queue' => [
                'depth' => Schema::hasTable('jobs') ? DB::table('jobs')->selectRaw('queue, COUNT(*) AS aggregate')->groupBy('queue')->pluck('aggregate', 'queue') : [],
                'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            ],
            'provider' => [
                'name' => $provider->name(),
                'reachable' => $providerHealth->reachable,
                'latency_ms' => $providerHealth->latencyMs,
                'circuit' => IntegrationAccount::query()->where('provider', $provider->name())->value('circuit_state'),
            ],
            'last_successful_sync' => $lastSuccessful,
        ]]);
    }

    private function databaseReachable(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function cacheReachable(): bool
    {
        try {
            $key = 'health:'.uniqid();
            Cache::put($key, true, 5);
            $reachable = Cache::get($key) === true;
            Cache::forget($key);

            return $reachable;
        } catch (Throwable) {
            return false;
        }
    }
}
