<?php

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::get('/auth/google/link', [GoogleAuthController::class, 'linkGoogleAccount']);

require __DIR__.'/auth.php';
