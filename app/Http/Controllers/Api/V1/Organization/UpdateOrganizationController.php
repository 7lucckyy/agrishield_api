<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Audit\RecordAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Organization\UpdateOrganizationRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;

final class UpdateOrganizationController extends Controller
{
    public function __invoke(UpdateOrganizationRequest $request, Organization $organization, RecordAuditLog $recordAuditLog): OrganizationResource
    {
        $before = $organization->only(array_keys($request->validated()));
        $organization->update($request->validated());
        $recordAuditLog->execute('organization.updated', $organization, ['before' => $before, 'after' => $organization->only(array_keys($before))]);

        return new OrganizationResource($organization);
    }
}
