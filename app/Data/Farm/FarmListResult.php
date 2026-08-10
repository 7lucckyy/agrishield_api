<?php

declare(strict_types=1);

namespace App\Data\Farm;

use App\Models\Farm;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class FarmListResult
{
    /**
     * @param  LengthAwarePaginator<int, Farm>  $farms
     * @param  array{farms: int, hectares: float, acres: float}  $totals
     */
    public function __construct(
        public LengthAwarePaginator $farms,
        public array $totals,
    ) {}
}
