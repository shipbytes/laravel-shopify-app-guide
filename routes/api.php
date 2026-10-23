<?php

use App\Http\Controllers\Api\ShopController;
use Illuminate\Support\Facades\Route;

// JSON endpoints called by the admin UI with fetch(). App Bridge attaches the ID token
// as a Bearer header; the same middleware verifies it. No session, no CSRF: these
// routes run in the "api" middleware group.
Route::middleware('shopify.app-home')->group(function () {
    Route::get('/shop', ShopController::class);
});
