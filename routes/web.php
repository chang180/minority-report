<?php

use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VerificationController::class, 'index'])->name('verification.index');
Route::post('/verifications', [VerificationController::class, 'store'])->name('verification.store');
Route::get('/verifications/{verification}', [VerificationController::class, 'show'])->name('verification.show');

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'minority-report',
        'laravel' => app()->version(),
    ]);
});
