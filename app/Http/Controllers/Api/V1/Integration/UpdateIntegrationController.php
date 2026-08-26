<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integration;

use App\Actions\Audit\RecordAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Integration\UpdateIntegrationRequest;
use App\Http\Resources\Api\V1\IntegrationResource;
use App\Models\IntegrationAccount;

final class UpdateIntegrationController extends Controller
{
    public function __invoke(UpdateIntegrationRequest $request, IntegrationAccount $integration, RecordAuditLog $recordAuditLog): IntegrationResource
    {
        $before = $integration->only(array_keys($request->validated()));
        $integration->update($request->validated());
        $recordAuditLog->execute('integration.updated', $integration, ['before' => $before, 'after' => $integration->only(array_keys($before))]);

        return new IntegrationResource($integration);
    }
}
