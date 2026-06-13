<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'minority-report',
        'laravel' => app()->version(),
    ]);
});
