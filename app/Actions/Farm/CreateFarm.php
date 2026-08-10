<?php

declare(strict_types=1);

namespace App\Actions\Farm;

use App\Actions\Sync\QueueFarmRegistration;
use App\Data\Farm\FarmCreationResult;
use App\Data\Geometry\ProcessedGeometry;
use App\Enums\GlobalRole;
use App\Enums\OrganizationRole;
use App\Enums\ProviderStatus;
use App\Enums\SyncTrigger;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateFarm
{
    public function __construct(private QueueFarmRegistration $queueFarmRegistration) {}

    /** @param array<string, mixed> $data */
    public function execute(User $user, array $data, ProcessedGeometry $geometry): FarmCreationResult
    {
        $organization = $this->resolveOrganization($user, Arr::get($data, 'organization_id'));

        return DB::transaction(function () use ($user, $organization, $data, $geometry): FarmCreationResult {
            $farm = new Farm;
            $farm->fill(Arr::only($data, ['name', 'locality', 'state', 'country']));
            $farm->uuid = (string) Str::uuid();
            $farm->owner()->associate($user);
            $farm->organization()->associate($organization);
            $farm->boundary_geojson = $geometry->geoJson;
            $farm->boundary_hash = $geometry->hash;
            $farm->area_hectares = $geometry->areaHectares;
            $farm->area_acres = $geometry->areaAcres;
            $farm->centroid_latitude = $geometry->centroidLatitude;
            $farm->centroid_longitude = $geometry->centroidLongitude;
            $farm->provider_status = ProviderStatus::Pending;
            $farm->save();

            $registrationRun = $this->queueFarmRegistration->execute($farm, SyncTrigger::Event, $user);
            $farm->load(['owner:id,name', 'organization:id,name', 'activeCropCycle.farm:id,uuid', 'activeCropCycle.crop']);

            return new FarmCreationResult($farm, $registrationRun);
        });
    }

    private function resolveOrganization(User $user, mixed $requestedOrganizationId): ?Organization
    {
        if ($requestedOrganizationId !== null) {
            $organization = Organization::query()->find($requestedOrganizationId);
            $isPlatformAdmin = $user->hasRole(GlobalRole::PlatformAdmin->value);
            $canCreateForOrganization = $organization !== null && ($isPlatformAdmin || $user->hasOrganizationRole(
                $organization,
                [OrganizationRole::Farmer, OrganizationRole::OrganizationAdmin],
            ));

            if (! $canCreateForOrganization) {
                throw new AuthorizationException('You cannot create a farm for that organization.');
            }

            return $organization;
        }

        $organizationId = collect($user->organizationIdsWhereRoleIn([
            OrganizationRole::Farmer,
            OrganizationRole::OrganizationAdmin,
        ]))->first();

        return $organizationId === null ? null : Organization::query()->find($organizationId);
    }
}
