<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agronomy;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AgronomyAdviceResource;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ListAgronomyAdviceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Farm $farm): AnonymousResourceCollection
    {
        Gate::authorize('view', $farm);

        return AgronomyAdviceResource::collection(
            $farm->agronomyAdvice()->with('farm:id,uuid')->whereBelongsTo($request->user(), 'user')
                ->latest()->paginate(min(50, max(1, $request->integer('per_page', 20))))
        );
    }
}
