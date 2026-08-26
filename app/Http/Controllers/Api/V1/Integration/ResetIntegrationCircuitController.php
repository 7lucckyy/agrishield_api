<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integration;

use App\Actions\Audit\RecordAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\IntegrationResource;
use App\Models\IntegrationAccount;
use App\Services\Integration\CircuitBreaker;
use Illuminate\Support\Facades\Gate;

final class ResetIntegrationCircuitController extends Controller
{
    public function __invoke(IntegrationAccount $integration, CircuitBreaker $circuitBreaker, RecordAuditLog $recordAuditLog): IntegrationResource
    {
        Gate::authorize('manageIntegrations');
        $circuitBreaker->reset($integration->provider);
        $recordAuditLog->execute('integration.circuit_reset', $integration, ['after' => ['circuit_state' => 'closed']]);

        return new IntegrationResource($integration->refresh());
    }
}
