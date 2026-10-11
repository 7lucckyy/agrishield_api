<?php

declare(strict_types=1);

namespace App\Services\Agronomy;

use App\Models\Farm;

final class FarmAgronomyContextBuilder
{
    /** @return array<string, string|float|null> */
    public function build(Farm $farm): array
    {
        $farm->loadMissing('activeCropCycle.crop');
        $cycle = $farm->activeCropCycle;

        return [
            'farm_name' => $farm->name,
            'state' => $farm->state,
            'locality' => $farm->locality,
            'area_hectares' => $farm->area_hectares === null ? null : (float) $farm->area_hectares,
            'crop' => $cycle?->crop?->name,
            'growth_stage' => $cycle?->growth_stage,
            'irrigation_context' => $cycle?->irrigation_context?->value,
            'planting_date' => $cycle?->planting_date?->toDateString(),
        ];
    }
}
