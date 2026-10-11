<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Platform;

use App\Actions\AssetFinance\UpdateAssetFinanceApplication;
use App\Enums\FinancePartnerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Platform\UpdateEquipmentRequestRequest;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceProduct;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EquipmentRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $applications = AssetFinanceApplication::query()
            ->whereNull('organization_id')
            ->whereHas('product.financePartner', fn ($query) => $query->where('type', FinancePartnerType::EquipmentDesk))
            ->with(['farm:id,uuid,name', 'applicant:id,name,phone', 'product:id,finance_partner_id,name,category', 'events' => fn ($query) => $query->latest()->limit(5)])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();
        $products = AssetFinanceProduct::query()
            ->whereHas('financePartner', fn ($query) => $query->where('type', FinancePartnerType::EquipmentDesk))
            ->orderBy('name')->get();

        return view('platform.equipment', compact('applications', 'products', 'status'));
    }

    public function update(UpdateEquipmentRequestRequest $request, AssetFinanceApplication $assetFinanceApplication, UpdateAssetFinanceApplication $update): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $update->execute($assetFinanceApplication, $user, $request->safe()->only(['status', 'decision_note']));

        return to_route('platform.equipment.index')->with('status', 'Equipment request updated.');
    }
}
