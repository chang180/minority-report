<?php

use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home/Welcome'))->name('home');

Route::get('/demo', [VerificationController::class, 'index'])->name('demo.verifications.index');
Route::post('/demo/verifications', [VerificationController::class, 'store'])->name('demo.verifications.store');
Route::get('/demo/verifications/{verification}', [VerificationController::class, 'show'])->name('demo.verifications.show');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/settings/profile', fn () => Inertia::render('settings/Profile'))->name('profile.edit');
    Route::get('/settings/password', fn () => Inertia::render('settings/Password'))->name('password.edit');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', fn () => redirect()->route('dashboard'))->name('index');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'minority-report',
        'laravel' => app()->version(),
    ]);
});
