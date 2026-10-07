<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Organization;

use App\Actions\Audit\RecordAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Organization\StoreOrganizationRequest;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class StoreOrganizationController extends Controller
{
    public function __invoke(StoreOrganizationRequest $request, RecordAuditLog $recordAuditLog): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] ??= Str::slug($data['name']).'-'.Str::lower(Str::random(6));
        $organization = Organization::query()->create($data);
        $recordAuditLog->execute('organization.created', $organization, ['after' => $organization->only(['name', 'slug', 'status'])]);

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }
}
