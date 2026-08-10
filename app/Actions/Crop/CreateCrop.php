<?php

declare(strict_types=1);

namespace App\Actions\Crop;

use App\Models\Crop;
use App\Services\CropCatalogueCache;

final class CreateCrop
{
    public function __construct(private CropCatalogueCache $cropCatalogueCache) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): Crop
    {
        $crop = Crop::query()->create($data);
        $this->cropCatalogueCache->invalidate();

        return $crop;
    }
}
