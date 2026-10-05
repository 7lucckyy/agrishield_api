<?php

use App\Http\Controllers\Api\V1\Advisory\AcknowledgeAdvisoryController;
use App\Http\Controllers\Api\V1\Advisory\ListAdvisoryController;
use App\Http\Controllers\Api\V1\Advisory\ShowAdvisoryController;
use App\Http\Controllers\Api\V1\Advisory\StoreAdvisoryController;
use App\Http\Controllers\Api\V1\AssetFinance\ListAssetFinanceApplicationController;
use App\Http\Controllers\Api\V1\AssetFinance\ListAssetFinanceProductController;
use App\Http\Controllers\Api\V1\AssetFinance\ListFarmerAssetFinanceApplicationController;
use App\Http\Controllers\Api\V1\AssetFinance\ListFarmerAssetFinanceProductController;
use App\Http\Controllers\Api\V1\AssetFinance\ShowAssetFinanceApplicationController;
use App\Http\Controllers\Api\V1\AssetFinance\StoreAssetFinanceApplicationController;
use App\Http\Controllers\Api\V1\AssetFinance\StoreFarmerAssetFinanceApplicationController;
use App\Http\Controllers\Api\V1\AssetFinance\UpdateAssetFinanceApplicationController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\RevokeAllTokensController;
use App\Http\Controllers\Api\V1\Crop\DeactivateCropController;
use App\Http\Controllers\Api\V1\Crop\ListCropController;
use App\Http\Controllers\Api\V1\Crop\ShowCropController;
use App\Http\Controllers\Api\V1\Crop\StoreCropController;
use App\Http\Controllers\Api\V1\Crop\UpdateCropController;
use App\Http\Controllers\Api\V1\CropCycle\DeleteCropCycleController;
use App\Http\Controllers\Api\V1\CropCycle\ListCropCycleController;
use App\Http\Controllers\Api\V1\CropCycle\ShowCropCycleController;
use App\Http\Controllers\Api\V1\CropCycle\StoreCropCycleController;
use App\Http\Controllers\Api\V1\CropCycle\UpdateCropCycleController;
use App\Http\Controllers\Api\V1\Diagnosis\ListDiagnosisRequestController;
use App\Http\Controllers\Api\V1\Diagnosis\ShowDiagnosisRequestController;
use App\Http\Controllers\Api\V1\Diagnosis\StoreDiagnosisRequestController;
use App\Http\Controllers\Api\V1\Diagnosis\UpdateDiagnosisRequestController;
use App\Http\Controllers\Api\V1\Farm\DeleteFarmController;
use App\Http\Controllers\Api\V1\Farm\ListFarmController;
use App\Http\Controllers\Api\V1\Farm\ShowFarmController;
use App\Http\Controllers\Api\V1\Farm\StoreFarmController;
use App\Http\Controllers\Api\V1\Farm\UpdateFarmController;
use App\Http\Controllers\Api\V1\FarmSection\DeleteFarmSectionController;
use App\Http\Controllers\Api\V1\FarmSection\ListFarmSectionController;
use App\Http\Controllers\Api\V1\FarmSection\StoreFarmSectionController;
use App\Http\Controllers\Api\V1\FarmSection\UpdateFarmSectionController;
use App\Http\Controllers\Api\V1\Health\ShowDetailedHealthController;
use App\Http\Controllers\Api\V1\Health\ShowHealthController;
use App\Http\Controllers\Api\V1\Insight\ListSatelliteObservationController;
use App\Http\Controllers\Api\V1\Insight\ShowSoilHealthController;
use App\Http\Controllers\Api\V1\Insight\ShowWeatherController;
use App\Http\Controllers\Api\V1\Insight\SummarizeSatelliteObservationController;
use App\Http\Controllers\Api\V1\Integration\ListIntegrationController;
use App\Http\Controllers\Api\V1\Integration\ResetIntegrationCircuitController;
use App\Http\Controllers\Api\V1\Integration\TestIntegrationController;
use App\Http\Controllers\Api\V1\Integration\UpdateIntegrationController;
use App\Http\Controllers\Api\V1\Media\ShowDiagnosisImageController;
use App\Http\Controllers\Api\V1\Media\ShowObservationMapController;
use App\Http\Controllers\Api\V1\Organization\ListOrganizationController;
use App\Http\Controllers\Api\V1\Organization\ListOrganizationFarmController;
use App\Http\Controllers\Api\V1\Organization\ListOrganizationMemberController;
use App\Http\Controllers\Api\V1\Organization\RemoveOrganizationMemberController;
use App\Http\Controllers\Api\V1\Organization\RotateReferralCodeController;
use App\Http\Controllers\Api\V1\Organization\ShowOrganizationController;
use App\Http\Controllers\Api\V1\Organization\ShowOrganizationOverviewController;
use App\Http\Controllers\Api\V1\Organization\StoreOrganizationController;
use App\Http\Controllers\Api\V1\Organization\UpdateOrganizationController;
use App\Http\Controllers\Api\V1\Organization\UpdateOrganizationMemberController;
use App\Http\Controllers\Api\V1\Profile\ShowProfileController;
use App\Http\Controllers\Api\V1\Profile\UpdatePasswordController;
use App\Http\Controllers\Api\V1\Profile\UpdateProfileController;
use App\Http\Controllers\Api\V1\Sync\ListFarmSyncRunController;
use App\Http\Controllers\Api\V1\Sync\TriggerFarmSyncController;
use App\Http\Controllers\Api\V1\VoiceAssistance\ListVoiceAssistanceController;
use App\Http\Controllers\Api\V1\VoiceAssistance\ShowVoiceAssistanceController;
use App\Http\Controllers\Api\V1\VoiceAssistance\StoreVoiceAssistanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', ShowHealthController::class)->name('health.show');

    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/auth/register', RegisterController::class);
        Route::post('/auth/login', LoginController::class);
        Route::post('/auth/password/forgot', ForgotPasswordController::class);
        Route::post('/auth/password/reset', ResetPasswordController::class);
    });

    Route::middleware(['auth:sanctum', 'active', 'throttle:general'])->group(function (): void {
        Route::post('/auth/logout', LogoutController::class);
        Route::post('/auth/tokens/revoke-all', RevokeAllTokensController::class);

        Route::get('/asset-finance/products', ListFarmerAssetFinanceProductController::class)
            ->name('asset-finance.products.index');
        Route::get('/asset-finance/applications', ListFarmerAssetFinanceApplicationController::class)
            ->name('asset-finance.applications.index');
        Route::post('/asset-finance/applications', StoreFarmerAssetFinanceApplicationController::class)
            ->middleware('throttle:writes')->name('asset-finance.applications.store');

        Route::get('/me', ShowProfileController::class);
        Route::patch('/me', UpdateProfileController::class);
        Route::patch('/me/password', UpdatePasswordController::class);
        Route::get('/voice-assistance', ListVoiceAssistanceController::class)->name('voice-assistance.index');
        Route::post('/voice-assistance', StoreVoiceAssistanceController::class)->middleware('throttle:voice-assistance')->name('voice-assistance.store');
        Route::get('/voice-assistance/{voiceAssistanceRequest}', ShowVoiceAssistanceController::class)->name('voice-assistance.show');
        Route::get('/health/detailed', ShowDetailedHealthController::class)->name('health.detailed');
        Route::get('/integrations', ListIntegrationController::class)->name('integrations.index');
        Route::patch('/integrations/{integration}', UpdateIntegrationController::class)->middleware('throttle:writes')->name('integrations.update');
        Route::post('/integrations/{integration}/test', TestIntegrationController::class)->middleware('throttle:writes')->name('integrations.test');
        Route::post('/integrations/{integration}/circuit/reset', ResetIntegrationCircuitController::class)->middleware('throttle:writes')->name('integrations.circuit.reset');

        Route::get('/farms', ListFarmController::class)->middleware('abilities:farms:read')->name('farms.index');
        Route::post('/farms', StoreFarmController::class)->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.store');
        Route::get('/farms/{farm}', ShowFarmController::class)->middleware('abilities:farms:read')->name('farms.show');
        Route::patch('/farms/{farm}', UpdateFarmController::class)->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.update');
        Route::delete('/farms/{farm}', DeleteFarmController::class)->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.destroy');
        Route::post('/farms/{farm}/sync', TriggerFarmSyncController::class)
            ->middleware(['abilities:farms:write', 'throttle:sync'])->name('farms.sync.store');
        Route::get('/farms/{farm}/sync-runs', ListFarmSyncRunController::class)
            ->middleware('abilities:farms:read')->name('farms.sync-runs.index');

        Route::get('/farms/{farm}/sections', ListFarmSectionController::class)
            ->middleware('abilities:farms:read')->name('farms.sections.index');
        Route::post('/farms/{farm}/sections', StoreFarmSectionController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.sections.store');
        Route::patch('/farms/{farm}/sections/{farmSection}', UpdateFarmSectionController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->scopeBindings()->name('farms.sections.update');
        Route::delete('/farms/{farm}/sections/{farmSection}', DeleteFarmSectionController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->scopeBindings()->name('farms.sections.destroy');

        Route::get('/farms/{farm}/soil-health', ShowSoilHealthController::class)
            ->middleware('abilities:farms:read')->name('farms.soil-health.show');
        Route::get('/farms/{farm}/weather', ShowWeatherController::class)
            ->middleware('abilities:farms:read')->name('farms.weather.show');
        Route::get('/farms/{farm}/satellite-observations', ListSatelliteObservationController::class)
            ->middleware('abilities:farms:read')->name('farms.satellite-observations.index');
        Route::get('/farms/{farm}/satellite-observations/summary', SummarizeSatelliteObservationController::class)
            ->middleware('abilities:farms:read')->name('farms.satellite-observations.summary');
        Route::get('/farms/{farm}/advisories', ListAdvisoryController::class)
            ->middleware('abilities:farms:read')->name('farms.advisories.index');
        Route::post('/farms/{farm}/advisories', StoreAdvisoryController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.advisories.store');
        Route::get('/farms/{farm}/advisories/{advisory}', ShowAdvisoryController::class)
            ->middleware('abilities:farms:read')->scopeBindings()->name('farms.advisories.show');
        Route::post('/farms/{farm}/advisories/{advisory}/acknowledge', AcknowledgeAdvisoryController::class)
            ->middleware(['abilities:farms:read', 'throttle:writes'])->scopeBindings()->name('farms.advisories.acknowledge');

        Route::get('/farms/{farm}/diagnosis-requests', ListDiagnosisRequestController::class)
            ->middleware('abilities:farms:read')->name('farms.diagnosis-requests.index');
        Route::post('/farms/{farm}/diagnosis-requests', StoreDiagnosisRequestController::class)
            ->middleware(['abilities:farms:write', 'throttle:diagnosis'])->name('farms.diagnosis-requests.store');
        Route::get('/farms/{farm}/diagnosis-requests/{diagnosis}', ShowDiagnosisRequestController::class)
            ->middleware('abilities:farms:read')->scopeBindings()->name('farms.diagnosis-requests.show');
        Route::patch('/farms/{farm}/diagnosis-requests/{diagnosis}', UpdateDiagnosisRequestController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->scopeBindings()->name('farms.diagnosis-requests.update');
        Route::get('/media/diagnosis/{diagnosis}/image', ShowDiagnosisImageController::class)
            ->middleware('signed')->name('media.diagnosis.image');
        Route::get('/media/obs/{observation}/map', ShowObservationMapController::class)
            ->name('media.observations.map');

        Route::get('/farms/{farm}/crop-cycles', ListCropCycleController::class)
            ->middleware('abilities:farms:read')->scopeBindings()->name('farms.crop-cycles.index');
        Route::post('/farms/{farm}/crop-cycles', StoreCropCycleController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->scopeBindings()->name('farms.crop-cycles.store');
        Route::get('/farms/{farm}/crop-cycles/{cropCycle}', ShowCropCycleController::class)
            ->middleware('abilities:farms:read')->scopeBindings()->name('farms.crop-cycles.show');
        Route::patch('/farms/{farm}/crop-cycles/{cropCycle}', UpdateCropCycleController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->scopeBindings()->name('farms.crop-cycles.update');
        Route::delete('/farms/{farm}/crop-cycles/{cropCycle}', DeleteCropCycleController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->scopeBindings()->name('farms.crop-cycles.destroy');

        Route::get('/organizations/{organization}/farms', ListOrganizationFarmController::class)
            ->middleware('abilities:farms:read')->name('organizations.farms.index');
        Route::get('/organizations/{organization}/overview', ShowOrganizationOverviewController::class)
            ->middleware('abilities:farms:read')->name('organizations.overview.show');
        Route::get('/organizations/{organization}/asset-finance/products', ListAssetFinanceProductController::class)
            ->middleware('abilities:farms:read')->name('organizations.asset-finance.products.index');
        Route::get('/organizations/{organization}/asset-finance/applications', ListAssetFinanceApplicationController::class)
            ->middleware('abilities:farms:read')->name('organizations.asset-finance.applications.index');
        Route::post('/organizations/{organization}/asset-finance/applications', StoreAssetFinanceApplicationController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->name('organizations.asset-finance.applications.store');
        Route::get('/organizations/{organization}/asset-finance/applications/{assetFinanceApplication}', ShowAssetFinanceApplicationController::class)
            ->middleware('abilities:farms:read')->scopeBindings()->name('organizations.asset-finance.applications.show');
        Route::patch('/organizations/{organization}/asset-finance/applications/{assetFinanceApplication}', UpdateAssetFinanceApplicationController::class)
            ->middleware(['abilities:farms:write', 'throttle:writes'])->scopeBindings()->name('organizations.asset-finance.applications.update');
        Route::get('/organizations', ListOrganizationController::class)->name('organizations.index');
        Route::post('/organizations', StoreOrganizationController::class)->middleware('throttle:writes')->name('organizations.store');
        Route::get('/organizations/{organization}', ShowOrganizationController::class)->name('organizations.show');
        Route::patch('/organizations/{organization}', UpdateOrganizationController::class)->middleware('throttle:writes')->name('organizations.update');
        Route::post('/organizations/{organization}/referral-code/rotate', RotateReferralCodeController::class)
            ->middleware('throttle:writes')->name('organizations.referral.rotate');
        Route::get('/organizations/{organization}/members', ListOrganizationMemberController::class)
            ->name('organizations.members.index');
        Route::patch('/organizations/{organization}/members/{user}', UpdateOrganizationMemberController::class)
            ->middleware('throttle:writes')->scopeBindings()->name('organizations.members.update');
        Route::delete('/organizations/{organization}/members/{user}', RemoveOrganizationMemberController::class)
            ->middleware('throttle:writes')->scopeBindings()->name('organizations.members.destroy');

        Route::get('/crops', ListCropController::class)->name('crops.index');
        Route::post('/crops', StoreCropController::class)->name('crops.store');
        Route::get('/crops/{crop}', ShowCropController::class)->name('crops.show');
        Route::patch('/crops/{crop}', UpdateCropController::class)->name('crops.update');
        Route::delete('/crops/{crop}', DeactivateCropController::class)->name('crops.destroy');
    });
});
