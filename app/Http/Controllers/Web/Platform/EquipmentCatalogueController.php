<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Platform\UpdateEquipmentProductRequest;
use App\Models\AssetFinanceProduct;
use Illuminate\Http\RedirectResponse;

final class EquipmentCatalogueController extends Controller
{
    public function update(UpdateEquipmentProductRequest $request, AssetFinanceProduct $assetFinanceProduct): RedirectResponse
    {
        $assetFinanceProduct->update([
            'is_active' => $request->boolean('is_active'),
            'metadata' => [
                ...($assetFinanceProduct->metadata ?? []),
                'acquisition_options' => $request->validated('acquisition_options'),
            ],
        ]);

        return to_route('platform.equipment.index')->with('status', 'Equipment catalogue updated.');
    }
}
