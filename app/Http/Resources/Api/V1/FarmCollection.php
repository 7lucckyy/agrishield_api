<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class FarmCollection extends ResourceCollection
{
    /** @var class-string<FarmResource> */
    public $collects = FarmResource::class;

    /** @param array{farms: int, hectares: float, acres: float} $totals */
    public function withTotals(array $totals): self
    {
        return $this->additional(['meta' => ['totals' => $totals]]);
    }

    /**
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'pagination' => [
                    'current_page' => $paginated['current_page'],
                    'per_page' => $paginated['per_page'],
                    'total' => $paginated['total'],
                    'last_page' => $paginated['last_page'],
                ],
            ],
        ];
    }
}
