<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class CropCollection extends ResourceCollection
{
    /** @var class-string<CropResource> */
    public $collects = CropResource::class;

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
