<?php

use App\Http\Controllers\Web\Auth\SessionController;
use App\Http\Controllers\Web\Organization\AdvisoryController as OrganizationAdvisoryController;
use App\Http\Controllers\Web\Organization\DashboardController as OrganizationDashboardController;
use App\Http\Controllers\Web\Organization\FarmController as OrganizationFarmController;
use App\Http\Controllers\Web\Organization\TeamController as OrganizationTeamController;
use App\Http\Controllers\Web\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Web\Platform\OperationsController as PlatformOperationsController;
use App\Http\Controllers\Web\Platform\OrganizationController as PlatformOrganizationController;
use App\Http\Controllers\Web\Platform\UserController as PlatformUserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/about', 'website.about')->name('about');
Route::view('/solutions', 'website.solutions')->name('solutions');
Route::view('/impact', 'website.impact')->name('impact');
Route::view('/partners', 'website.partners')->name('partners');
Route::view('/team', 'website.team')->name('team');
Route::view('/contact', 'website.contact')->name('contact');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('platform')->name('platform.')->middleware(['auth', 'can:accessPlatform'])->group(function (): void {
    Route::get('/', PlatformDashboardController::class)->name('dashboard');
    Route::get('/organizations', [PlatformOrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/{organization}', [PlatformOrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/users', PlatformUserController::class)->name('users');
    Route::get('/operations', PlatformOperationsController::class)->name('operations');
});

Route::prefix('organization/{organization}')->name('organization.')->middleware('auth')->scopeBindings()->group(function (): void {
    Route::get('/', OrganizationDashboardController::class)->name('dashboard');
    Route::get('/farms', [OrganizationFarmController::class, 'index'])->name('farms.index');
    Route::get('/farms/{farm}', [OrganizationFarmController::class, 'show'])->name('farms.show');
    Route::get('/team', OrganizationTeamController::class)->name('team');
    Route::get('/advisories', OrganizationAdvisoryController::class)->name('advisories');
});
