<?php

use App\Http\Controllers\AdminDemoController;
use App\Http\Controllers\AdminGroundingController;
use App\Http\Controllers\AuthVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProviderSettingsController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home/Welcome'))->name('home');

Route::get('/demo', [VerificationController::class, 'index'])->name('demo.verifications.index');
Route::post('/demo/verifications', [VerificationController::class, 'store'])->name('demo.verifications.store');
Route::get('/demo/verifications/{verification}', [VerificationController::class, 'show'])->name('demo.verifications.show');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Settings
    Route::get('/settings/profile', fn () => Inertia::render('settings/Profile'))->name('profile.edit');
    Route::get('/settings/password', fn () => Inertia::render('settings/Password'))->name('password.edit');
    Route::get('/settings/providers', [ProviderSettingsController::class, 'show'])->name('settings.providers');
    Route::put('/settings/providers/preset', [ProviderSettingsController::class, 'updatePreset'])->name('settings.providers.preset.update');
    Route::post('/settings/providers/custom', [ProviderSettingsController::class, 'storeCustom'])->name('settings.providers.custom.store');
    Route::put('/settings/providers/custom/{customProvider}', [ProviderSettingsController::class, 'updateCustom'])->name('settings.providers.custom.update');
    Route::delete('/settings/providers/custom/{customProvider}', [ProviderSettingsController::class, 'destroyCustom'])->name('settings.providers.custom.destroy');
    Route::put('/settings/providers/slots', [ProviderSettingsController::class, 'updateSlots'])->name('settings.providers.slots.update');

    // Authenticated verifications
    Route::get('/verifications/create', [AuthVerificationController::class, 'create'])->name('verifications.create');
    Route::post('/verifications', [AuthVerificationController::class, 'store'])->name('verifications.store');
    Route::get('/verifications/{verification}', [AuthVerificationController::class, 'show'])->name('verifications.show');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', fn () => redirect()->route('dashboard'))->name('index');
    Route::get('/demo', [AdminDemoController::class, 'show'])->name('demo.show');
    Route::put('/demo', [AdminDemoController::class, 'update'])->name('demo.update');
    Route::get('/grounding', [AdminGroundingController::class, 'show'])->name('grounding.show');
    Route::put('/grounding', [AdminGroundingController::class, 'update'])->name('grounding.update');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'minority-report',
        'laravel' => app()->version(),
    ]);
});
