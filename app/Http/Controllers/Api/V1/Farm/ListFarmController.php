<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Farm;

use App\Actions\Farm\ListFarms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Farm\ListFarmRequest;
use App\Http\Resources\Api\V1\FarmCollection;
use App\Models\User;

final class ListFarmController extends Controller
{
    public function __construct(private ListFarms $listFarms) {}

    public function __invoke(ListFarmRequest $request): FarmCollection
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->listFarms->execute(
            $user,
            $request->filters(),
            $request->sort(),
            $request->perPage(),
            $request->url(),
        );

        return (new FarmCollection($result->farms))->withTotals($result->totals);
    }
}
