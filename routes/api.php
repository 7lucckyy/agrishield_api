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

        Route::get('/crops', ListCropController::class)->name('crops.index');
        Route::post('/crops', StoreCropController::class)->name('crops.store');
        Route::get('/crops/{crop}', ShowCropController::class)->name('crops.show');
        Route::patch('/crops/{crop}', UpdateCropController::class)->name('crops.update');
        Route::delete('/crops/{crop}', DeactivateCropController::class)->name('crops.destroy');
    });
});
