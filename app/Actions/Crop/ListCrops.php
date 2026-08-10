<?php

declare(strict_types=1);

namespace App\Actions\Crop;

use App\Enums\CropCategory;
use App\Models\Crop;
use App\Services\CropCatalogueCache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class ListCrops
{
    public function __construct(private CropCatalogueCache $cropCatalogueCache) {}

    /** @return LengthAwarePaginator<int, Crop> */
    public function execute(
        bool $active,
        ?CropCategory $category,
        ?string $search,
        int $perPage,
        int $page,
        bool $descending,
        string $path,
    ): LengthAwarePaginator {
        $crops = $active
            ? $this->cropCatalogueCache->activeCrops()
            : Crop::query()->where('active', false)->orderBy('name')->get();

        if ($category !== null) {
            $crops = $crops
                ->filter(fn (Crop $crop): bool => $crop->category === $category)
                ->values();
        }

        if ($search !== null) {
            $needle = Str::lower($search);
            $crops = $crops
                ->filter(fn (Crop $crop): bool => Str::contains(
                    Str::lower(implode(' ', array_filter([
                        $crop->name,
                        $crop->scientific_name,
                        $crop->code,
                    ]))),
                    $needle,
                ))
                ->values();
        }

        $crops = ($descending ? $crops->sortByDesc('name') : $crops->sortBy('name'))->values();

        return new LengthAwarePaginator(
            $crops->forPage($page, $perPage)->values(),
            $crops->count(),
            $perPage,
            $page,
            ['path' => $path, 'pageName' => 'page'],
        );
    }
}
