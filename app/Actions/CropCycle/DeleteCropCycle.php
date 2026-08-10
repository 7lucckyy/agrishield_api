<?php

declare(strict_types=1);

namespace App\Actions\CropCycle;

use App\Enums\CropCycleStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\CropCycle;

final class DeleteCropCycle
{
    public function execute(CropCycle $cropCycle): void
    {
        if ($cropCycle->status !== CropCycleStatus::Planned) {
            throw new InvalidTransitionException('Only planned crop cycles may be deleted.');
        }

        $cropCycle->delete();
    }
}
