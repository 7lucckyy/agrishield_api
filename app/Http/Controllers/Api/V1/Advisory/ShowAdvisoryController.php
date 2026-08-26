<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Advisory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdvisoryResource;
use App\Models\Advisory;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowAdvisoryController extends Controller
{
    public function __invoke(Request $request, Farm $farm, Advisory $advisory): AdvisoryResource
    {
        Gate::authorize('view', $farm);
        /** @var User $user */
        $user = $request->user();
        $advisory->load(['acknowledgements' => function (Relation $relation) use ($user): void {
            $relation->getQuery()->whereBelongsTo($user);
        }]);

        return new AdvisoryResource($advisory);
    }
}
