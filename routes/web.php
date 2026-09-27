<?php

use App\Http\Controllers\Web\Auth\SessionController;
use App\Http\Controllers\Web\Organization\AdvisoryController as OrganizationAdvisoryController;
use App\Http\Controllers\Web\Organization\AssetFinanceController as OrganizationAssetFinanceController;
use App\Http\Controllers\Web\Organization\DashboardController as OrganizationDashboardController;
use App\Http\Controllers\Web\Organization\FarmController as OrganizationFarmController;
use App\Http\Controllers\Web\Organization\ShowVoiceAudioController;
use App\Http\Controllers\Web\Organization\TeamController as OrganizationTeamController;
use App\Http\Controllers\Web\Organization\VoiceAssistanceController as OrganizationVoiceAssistanceController;
use App\Http\Controllers\Web\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Web\Platform\OperationsController as PlatformOperationsController;
use App\Http\Controllers\Web\Platform\OrganizationController as PlatformOrganizationController;
use App\Http\Controllers\Web\Platform\UserController as PlatformUserController;
use App\Http\Controllers\Web\PublicMetadataController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/about', 'website.about')->name('about');
Route::view('/solutions', 'website.solutions')->name('solutions');
Route::view('/impact', 'website.impact')->name('impact');
Route::view('/partners', 'website.partners')->name('partners');
Route::view('/team', 'website.team')->name('team');
Route::view('/contact', 'website.contact')->name('contact');
Route::view('/field-voice', 'website.field-voice')->name('field-voice');
Route::get('/robots.txt', [PublicMetadataController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [PublicMetadataController::class, 'sitemap'])->name('sitemap');
Route::get('/.well-known/security.txt', [PublicMetadataController::class, 'security'])->name('security');

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
    Route::post('/farms/{farm}/crop-screenings', [OrganizationFarmController::class, 'storeDiagnosis'])->middleware('throttle:diagnosis')->name('farms.diagnoses.store');
    Route::get('/team', OrganizationTeamController::class)->name('team');
    Route::get('/advisories', OrganizationAdvisoryController::class)->name('advisories');
    Route::get('/asset-access', [OrganizationAssetFinanceController::class, 'index'])->name('asset-finance.index');
    Route::post('/asset-access', [OrganizationAssetFinanceController::class, 'store'])->middleware('throttle:writes')->name('asset-finance.store');
    Route::patch('/asset-access/{assetFinanceApplication}', [OrganizationAssetFinanceController::class, 'update'])->middleware('throttle:writes')->name('asset-finance.update');
    Route::get('/field-voice', [OrganizationVoiceAssistanceController::class, 'index'])->name('voice-assistance.index');
    Route::post('/field-voice', [OrganizationVoiceAssistanceController::class, 'store'])->middleware('throttle:voice-assistance')->name('voice-assistance.store');
    Route::get('/field-voice/{voiceAssistanceRequest}/audio', ShowVoiceAudioController::class)->name('voice-assistance.audio');
});
