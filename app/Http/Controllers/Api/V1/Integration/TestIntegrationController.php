<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integration;

use App\Http\Controllers\Controller;
use App\Integrations\Contracts\FarmingInsightsProvider;
use App\Models\IntegrationAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class TestIntegrationController extends Controller
{
    public function __invoke(IntegrationAccount $integration, FarmingInsightsProvider $provider): JsonResponse
    {
        Gate::authorize('manageIntegrations');
        if ($integration->provider !== $provider->name()) {
            return response()->json(['data' => ['reachable' => false, 'latency_ms' => null, 'note' => 'This provider is not currently selected.']]);
        }

        $health = $provider->healthCheck();

        return response()->json(['data' => ['reachable' => $health->reachable, 'latency_ms' => $health->latencyMs, 'checked_at' => $health->checkedAt->toISOString()]]);
    }
}
