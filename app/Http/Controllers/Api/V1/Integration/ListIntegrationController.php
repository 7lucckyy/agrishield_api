<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Integration;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\IntegrationResource;
use App\Models\IntegrationAccount;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ListIntegrationController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        Gate::authorize('manageIntegrations');

        return IntegrationResource::collection(IntegrationAccount::query()->orderBy('provider')->get());
    }
}
