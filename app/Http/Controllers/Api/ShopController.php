<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shopify\Adapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Shopify\App\ShopifyApp;

/**
 * Proof that the stored access token works: one Admin API query, made on behalf of
 * the shop that the middleware verified.
 */
class ShopController extends Controller
{
    public function __invoke(Request $request, ShopifyApp $shopify): JsonResponse|Response
    {
        $shop = $request->attributes->get('shop');
        $verified = $request->attributes->get('shopify');

        $result = $shopify->adminGraphQLRequest(
            '{ shop { name myshopifyDomain currencyCode plan { publicDisplayName partnerDevelopment } } }',
            shop: $shop->shop,
            accessToken: $shop->access_token,
            apiVersion: config('shopify.api_version'),
            // Tells the package this call answers a live App Home request, so a rejected
            // token can be turned into a "retry with a fresh token" response.
            invalidTokenResponse: $verified->invalidTokenResponse,
        );

        if (! $result->ok) {
            // Shopify no longer accepts the token (the app was uninstalled, or the token
            // was revoked). Drop it; the retried request exchanges a new one.
            if ($result->log->code === 'unauthorized') {
                $shop->forgetAccessToken();
            }

            return Adapter::response($result);
        }

        return response()->json($result->data['shop']);
    }
}
