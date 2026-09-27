<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Organization;

use App\Actions\AssetFinance\CreateAssetFinanceApplication;
use App\Actions\AssetFinance\UpdateAssetFinanceApplication;
use App\Enums\AssetFinanceStatus;
use App\Enums\RepaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Organization\StoreAssetFinanceApplicationRequest;
use App\Http\Requests\Web\Organization\UpdateAssetFinanceApplicationRequest;
use App\Models\AssetFinanceApplication;
use App\Models\AssetFinanceProduct;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class AssetFinanceController extends Controller
{
    public function index(Organization $organization): View
    {
        Gate::authorize('viewAssetFinance', $organization);

        $products = AssetFinanceProduct::query()
            ->where('is_active', true)
            ->whereHas('financePartner', fn ($query) => $query->where('is_active', true))
            ->with('financePartner')
            ->orderBy('name')
            ->get();
        $farms = $organization->farms()
            ->with('activeCropCycle.crop:id,name')
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'organization_id']);
        $applications = $organization->assetFinanceApplications()
            ->with(['farm:id,uuid,name', 'applicant:id,name', 'product.financePartner'])
            ->latest()
            ->paginate(15);
        $canManage = Gate::allows('manageAssetFinance', $organization);
        $statuses = AssetFinanceStatus::cases();
        $repaymentStatuses = RepaymentStatus::cases();

        return view('organization.asset-finance.index', compact(
            'organization',
            'products',
            'farms',
            'applications',
            'canManage',
            'statuses',
            'repaymentStatuses',
        ));
    }

    public function store(StoreAssetFinanceApplicationRequest $request, Organization $organization, CreateAssetFinanceApplication $create): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $farm = Farm::query()->whereBelongsTo($organization)->where('uuid', $request->string('farm_id'))->firstOrFail();
        $product = AssetFinanceProduct::query()->findOrFail($request->integer('asset_finance_product_id'));
        $data = $request->validated();
        $data['consent_channel'] = 'organization_portal';
        $create->execute($organization, $farm, $user, $product, $data);

        return to_route('organization.asset-finance.index', $organization)
            ->with('status', 'Asset finance application recorded and ready for partner handoff.');
    }

    public function update(UpdateAssetFinanceApplicationRequest $request, Organization $organization, AssetFinanceApplication $assetFinanceApplication, UpdateAssetFinanceApplication $update): RedirectResponse
    {
        abort_unless($assetFinanceApplication->organization_id === $organization->getKey(), 404);
        /** @var User $user */
        $user = $request->user();
        $update->execute($assetFinanceApplication, $user, $request->validated());

        return to_route('organization.asset-finance.index', $organization)
            ->with('status', 'Application status updated.');
    }
}
