<?php

declare(strict_types=1);

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Services\CropCatalogueCache;

final class UpdateCrop
{
    public function __construct(private CropCatalogueCache $cropCatalogueCache) {}

    /** @param array<string, mixed> $data */
    public function execute(Crop $crop, array $data): Crop
    {
        $crop->update($data);
        $this->cropCatalogueCache->invalidate();

        return $crop->refresh();
    }
}
