<?php

declare(strict_types=1);

namespace App\Actions\CropCycle;

use App\Enums\CropCycleStatus;
use App\Models\CropCycle;
use App\Models\Farm;
use Illuminate\Pagination\LengthAwarePaginator;

final class ListCropCycles
{
    /** @return LengthAwarePaginator<int, CropCycle> */
    public function execute(
        Farm $farm,
        ?CropCycleStatus $status,
        int $perPage,
        string $path,
    ): LengthAwarePaginator {
        return $farm->cropCycles()
            ->with(['farm:id,uuid', 'crop'])
            ->when($status, fn ($query, $cycleStatus) => $query->where('status', $cycleStatus))
            ->latest('planting_date')
            ->paginate($perPage)
            ->withPath($path)
            ->withQueryString();
    }
}
