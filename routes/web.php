<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// App Home: the page Shopify loads in the admin iframe. Part 1 only proves the loop works;
// part 2 verifies the request and installs the shop before rendering anything.
Route::get('/', function (Request $request) {
    // Shopify sends ?shop= when it opens the app. The CSP below lets only that shop's
    // admin and admin.shopify.com frame this page; anything else is refused.
    $shop = $request->query('shop', '');
    abort_unless(preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/', $shop), 400, 'Missing or invalid shop');

    return response()
        ->view('app')
        ->header('Content-Security-Policy', "frame-ancestors https://{$shop} https://admin.shopify.com");
});
