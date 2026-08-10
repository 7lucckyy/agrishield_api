<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
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
use App\Http\Controllers\Api\V1\Farm\DeleteFarmController;
use App\Http\Controllers\Api\V1\Farm\ListFarmController;
use App\Http\Controllers\Api\V1\Farm\ShowFarmController;
use App\Http\Controllers\Api\V1\Farm\StoreFarmController;
use App\Http\Controllers\Api\V1\Farm\UpdateFarmController;
use App\Http\Controllers\Api\V1\Organization\ListOrganizationFarmController;
use App\Http\Controllers\Api\V1\Organization\ListOrganizationMemberController;
use App\Http\Controllers\Api\V1\Organization\RemoveOrganizationMemberController;
use App\Http\Controllers\Api\V1\Organization\UpdateOrganizationMemberController;
use App\Http\Controllers\Api\V1\Profile\ShowProfileController;
use App\Http\Controllers\Api\V1\Profile\UpdatePasswordController;
use App\Http\Controllers\Api\V1\Profile\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('/auth/register', RegisterController::class);
        Route::post('/auth/login', LoginController::class);
    });

    Route::middleware(['auth:sanctum', 'throttle:general'])->group(function (): void {
        Route::post('/auth/logout', LogoutController::class);
        Route::post('/auth/tokens/revoke-all', RevokeAllTokensController::class);

        Route::get('/me', ShowProfileController::class);
        Route::patch('/me', UpdateProfileController::class);
        Route::patch('/me/password', UpdatePasswordController::class);

        Route::get('/farms', ListFarmController::class)->middleware('abilities:farms:read')->name('farms.index');
        Route::post('/farms', StoreFarmController::class)->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.store');
        Route::get('/farms/{farm}', ShowFarmController::class)->middleware('abilities:farms:read')->name('farms.show');
        Route::patch('/farms/{farm}', UpdateFarmController::class)->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.update');
        Route::delete('/farms/{farm}', DeleteFarmController::class)->middleware(['abilities:farms:write', 'throttle:writes'])->name('farms.destroy');

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
