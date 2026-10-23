<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShopController;
use Illuminate\Support\Facades\Route;

// JSON endpoints called by the admin UI with fetch(). App Bridge attaches the ID token
// as a Bearer header; the same middleware verifies it. No session, no CSRF: these
// routes run in the "api" middleware group.
Route::middleware('shopify.app-home')->group(function () {
    Route::get('/shop', ShopController::class);

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::patch('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    Route::get('/products', [ProductController::class, 'index']);

    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings', [SettingsController::class, 'update']);
});
