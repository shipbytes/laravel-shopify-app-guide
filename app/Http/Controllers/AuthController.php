<?php

namespace App\Http\Controllers;

use App\Shopify\Adapter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Shopify\App\ShopifyApp;

class AuthController extends Controller
{
    /**
     * The token-refresh page. When a navigation inside the admin iframe reaches the app
     * without an ID token, the middleware redirects here; the page the package renders
     * asks App Bridge for a fresh token and retries the original URL with it.
     */
    public function patchIdToken(Request $request, ShopifyApp $shopify): Response
    {
        return Adapter::response($shopify->appHomePatchIdToken(Adapter::req($request)));
    }
}
