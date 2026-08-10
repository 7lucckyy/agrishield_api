<?php

declare(strict_types=1);

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Services\CropCatalogueCache;

final class DeactivateCrop
{
    public function __construct(private CropCatalogueCache $cropCatalogueCache) {}

    public function execute(Crop $crop): void
    {
        $crop->update(['active' => false]);
        $this->cropCatalogueCache->invalidate();
    }
}
