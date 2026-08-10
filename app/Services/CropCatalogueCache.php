<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Crop;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;

final class CropCatalogueCache
{
    private const string VERSION_KEY = 'crops:catalogue:version';

    private const string CACHE_KEY_PREFIX = 'crops:catalogue:v';

    private const int TTL_SECONDS = 86400;

    public function __construct(private Repository $cache) {}

    /** @return Collection<int, Crop> */
    public function activeCrops(): Collection
    {
        $version = $this->cache->rememberForever(self::VERSION_KEY, fn (): int => 1);

        return $this->cache->remember(
            self::CACHE_KEY_PREFIX.$version,
            self::TTL_SECONDS,
            fn (): Collection => Crop::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        );
    }

    public function invalidate(): void
    {
        $this->cache->rememberForever(self::VERSION_KEY, fn (): int => 1);
        $this->cache->increment(self::VERSION_KEY);
    }
}
