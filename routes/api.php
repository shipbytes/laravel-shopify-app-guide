<?php

use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\WebhookController;
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

// Deliveries pushed by Shopify. Verified by HMAC, not by an ID token, and exempt from
// the per-IP rate limit: a busy shop can send bursts from the same addresses.
Route::middleware('shopify.webhook')->withoutMiddleware('throttle:api')->group(function () {
    Route::post('/webhooks', WebhookController::class);
    Route::post('/events', EventsController::class);
});
