<?php

use App\Http\Controllers\Storefront\ReviewController;
use Illuminate\Support\Facades\Route;

// Reached only through the app proxy: Shopify forwards https://<shop>/apps/reviews/...
// to https://<app>/storefront/... and signs the request. No session, no CSRF token;
// the signature is the authentication.
Route::prefix('storefront')->middleware(['api', 'shopify.app-proxy'])->group(function () {
    Route::get('/products/{productId}', [ReviewController::class, 'index']);
    Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
});
