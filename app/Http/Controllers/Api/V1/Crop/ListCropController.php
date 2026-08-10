<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Crop;

use App\Actions\Crop\ListCrops;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Crop\ListCropRequest;
use App\Http\Resources\Api\V1\CropCollection;
use App\Models\Crop;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class ListCropController extends Controller
{
    public function __construct(private ListCrops $listCrops) {}

    public function __invoke(ListCropRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Crop::class);

        return (new CropCollection($this->listCrops->execute(
            $request->active(),
            $request->category(),
            $request->search(),
            $request->perPage(),
            $request->page(),
            $request->descending(),
            $request->url(),
        )))->response()->withHeaders(['Cache-Control' => 'public, max-age=3600']);
    }
}
