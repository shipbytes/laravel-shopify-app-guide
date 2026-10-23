<?php

use App\Http\Controllers\AppHomeController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// The token-refresh page must stay outside the App Home middleware: it is where
// requests without a token are sent to get one.
Route::get('/auth/patch-id-token', [AuthController::class, 'patchIdToken']);

Route::middleware('shopify.app-home')->group(function () {
    Route::get('/', AppHomeController::class)->name('app.home');
});
