<?php

use App\Http\Controllers\AdminAlignerController;
use App\Http\Controllers\AdminDemoController;
use App\Http\Controllers\AdminGroundingController;
use App\Http\Controllers\AuthVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProviderSettingsController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Home/Welcome');
})->name('home');

Route::get('/demo', [VerificationController::class, 'index'])->name('demo.verifications.index');
Route::post('/demo/verifications', [VerificationController::class, 'store'])->name('demo.verifications.store');
Route::get('/demo/verifications/{verification}', [VerificationController::class, 'show'])->name('demo.verifications.show');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/about', fn () => Inertia::render('About/Index'))->name('about');

    // Settings
    Route::get('/settings/profile', fn () => Inertia::render('settings/Profile'))->name('profile.edit');
    Route::get('/settings/password', fn () => Inertia::render('settings/Password'))->name('password.edit');
    Route::get('/settings/providers', [ProviderSettingsController::class, 'show'])->name('settings.providers');
    Route::put('/settings/providers/preset', [ProviderSettingsController::class, 'updatePreset'])->name('settings.providers.preset.update');
    Route::post('/settings/providers/custom', [ProviderSettingsController::class, 'storeCustom'])->name('settings.providers.custom.store');
    Route::put('/settings/providers/custom/{customProvider}', [ProviderSettingsController::class, 'updateCustom'])->name('settings.providers.custom.update');
    Route::delete('/settings/providers/custom/{customProvider}', [ProviderSettingsController::class, 'destroyCustom'])->name('settings.providers.custom.destroy');
    Route::put('/settings/providers/slots', [ProviderSettingsController::class, 'updateSlots'])->name('settings.providers.slots.update');
});

Route::middleware(['auth', 'verified', 'verification.long'])->group(function (): void {
    // Authenticated verifications
    Route::get('/verifications', [AuthVerificationController::class, 'index'])->name('verifications.index');
    Route::get('/verifications/create', [AuthVerificationController::class, 'create'])->name('verifications.create');
    Route::post('/verifications', [AuthVerificationController::class, 'store'])->name('verifications.store');
    Route::delete('/verifications', [AuthVerificationController::class, 'destroyAll'])->name('verifications.destroyAll');
    Route::get('/verifications/{verification}', [AuthVerificationController::class, 'show'])->name('verifications.show');
    Route::delete('/verifications/{verification}', [AuthVerificationController::class, 'destroy'])->name('verifications.destroy');
    Route::get('/verifications/{verification}/status', [AuthVerificationController::class, 'status'])->name('verifications.status');
    Route::post('/verifications/{verification}/replay', [AuthVerificationController::class, 'replay'])->name('verifications.replay');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', fn () => redirect()->route('dashboard'))->name('index');
    Route::get('/demo', [AdminDemoController::class, 'show'])->name('demo.show');
    Route::put('/demo', [AdminDemoController::class, 'update'])->name('demo.update');
    Route::get('/grounding', [AdminGroundingController::class, 'show'])->name('grounding.show');
    Route::put('/grounding', [AdminGroundingController::class, 'update'])->name('grounding.update');
    Route::get('/aligner', [AdminAlignerController::class, 'show'])->name('aligner.show');
    Route::put('/aligner', [AdminAlignerController::class, 'update'])->name('aligner.update');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'minority-report',
        'laravel' => app()->version(),
    ]);
});
