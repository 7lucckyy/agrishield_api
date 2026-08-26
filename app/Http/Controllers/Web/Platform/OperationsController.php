<?php

namespace App\Http\Controllers\Web\Platform;

use App\Enums\SyncStatus;
use App\Http\Controllers\Controller;
use App\Models\IntegrationAccount;
use App\Models\SyncRun;
use Illuminate\Contracts\View\View;

class OperationsController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.operations', [
            'integrations' => IntegrationAccount::query()->withCount('farmProviderLinks')->orderBy('provider')->get(),
            'syncRuns' => SyncRun::query()->with('farm:id,uuid,name')->latest()->paginate(20),
            'stats' => [
                'running' => SyncRun::query()->where('status', SyncStatus::Running)->count(),
                'failed24h' => SyncRun::query()->where('status', SyncStatus::Failed)->where('created_at', '>=', now()->subDay())->count(),
                'success24h' => SyncRun::query()->where('status', SyncStatus::Succeeded)->where('completed_at', '>=', now()->subDay())->count(),
            ],
        ]);
    }
}
