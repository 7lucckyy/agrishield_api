<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Crop;

use App\Actions\Crop\DeactivateCrop;
use App\Http\Controllers\Controller;
use App\Models\Crop;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class DeactivateCropController extends Controller
{
    public function __construct(private DeactivateCrop $deactivateCrop) {}

    public function __invoke(Crop $crop): Response
    {
        Gate::authorize('delete', $crop);
        $this->deactivateCrop->execute($crop);

        return response()->noContent();
    }
}
