<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Farm;

use App\Actions\Farm\DeleteFarm;
use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class DeleteFarmController extends Controller
{
    public function __construct(private DeleteFarm $deleteFarm) {}

    public function __invoke(Farm $farm): Response
    {
        Gate::authorize('delete', $farm);
        $this->deleteFarm->execute($farm);

        return response()->noContent();
    }
}
