<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Shopify\Adapter;
use Closure;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requests that reach the app through the app proxy: a visitor's browser calls
 * /apps/reviews/... on the storefront, Shopify forwards it here with a signature over
 * the query string. The shop and, when a customer is logged in, their ID are trusted;
 * nothing else in the request is.
 */
class VerifyAppProxy
{
    public function __construct(private ShopifyApp $shopify) {}

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->shopify->verifyAppProxyReq(Adapter::req($request));

        if (! $result->ok) {
            return Adapter::response($result);
        }

        $shop = Shop::where('shop', $result->shop)->whereNotNull('access_token')->first();

        if ($shop === null) {
            return response()->json(['message' => 'App not installed'], 404);
        }

        $request->attributes->set('shop', $shop);
        $request->attributes->set('customerId', $result->loggedInCustomerId);

        return $next($request);
    }
}
