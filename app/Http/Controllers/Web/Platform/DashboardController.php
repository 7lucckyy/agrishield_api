<?php

namespace App\Http\Controllers\Web\Platform;

use App\Enums\FarmStatus;
use App\Enums\OrganizationStatus;
use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Farm;
use App\Models\Organization;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.dashboard', [
            'metrics' => [
                'organizations' => Organization::query()->where('status', OrganizationStatus::Active)->count(),
                'farms' => Farm::query()->where('status', FarmStatus::Active)->count(),
                'hectares' => (float) Farm::query()->where('status', FarmStatus::Active)->sum('area_hectares'),
                'users' => User::query()->count(),
            ],
            'attention' => [
                'failedSyncs' => SyncRun::query()->where('status', SyncStatus::Failed)->count(),
                'unregisteredFarms' => Farm::query()->where('provider_status', '!=', 'registered')->count(),
                'criticalAdvisories' => Advisory::query()->where('severity', 'critical')->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()))->count(),
            ],
            'organizations' => Organization::query()->withCount(['farms', 'users'])->latest()->limit(5)->get(),
            'syncRuns' => SyncRun::query()->with('farm:id,uuid,name')->latest()->limit(6)->get(),
        ]);
    }
}
