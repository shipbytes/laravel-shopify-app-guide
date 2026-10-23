<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Shopify\Adapter;
use Closure;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects every route the embedded admin UI calls: the App Home page itself and the
 * JSON endpoints its fetch() calls hit. After this middleware, the route can trust
 * $request->attributes->get('shop') and knows the shop holds a usable access token.
 */
class AuthenticateAppHome
{
    public function __construct(private ShopifyApp $shopify) {}

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Is this really Shopify? The page request carries an id_token query
        //    parameter, fetch() calls carry an Authorization: Bearer header; the package
        //    checks both. A missing or stale token gets a response that tells App Bridge
        //    how to recover (redirect to the token page, or retry with a fresh token).
        $result = $this->shopify->verifyAppHomeReq(
            Adapter::req($request),
            appHomePatchIdTokenPath: config('shopify.patch_id_token_path'),
        );

        if (! $result->ok) {
            return Adapter::response($result);
        }

        // 2. Do we hold an access token for this shop, and is it still good?
        $shop = Shop::firstOrCreate(['shop' => $result->shop]);
        $token = $shop->accessToken();

        if ($token !== null) {
            // The package knows when a token needs refreshing; call it every time and
            // save whatever comes back. A refresh that fails means the refresh token has
            // expired too (90 days without a visit), so fall through to a new exchange.
            $refresh = $this->shopify->refreshTokenExchangedAccessToken($token);

            if (! $refresh->ok) {
                $shop->forgetAccessToken();
                $token = null;
            } elseif ($refresh->accessToken !== null) {
                $shop->storeAccessToken($refresh->accessToken);
            }
        }

        if ($token === null) {
            // 3. No token: trade the ID token for an offline access token. This is the
            //    whole install flow, no OAuth redirect, no consent screen on a dev store.
            $exchange = $this->shopify->exchangeUsingTokenExchange(
                accessMode: 'offline',
                idToken: $result->idToken,
                invalidTokenResponse: $result->invalidTokenResponse,
            );

            if (! $exchange->ok) {
                return Adapter::response($exchange);
            }

            $shop->storeAccessToken($exchange->accessToken);
        }

        $request->attributes->set('shop', $shop);
        $request->attributes->set('shopify', $result);

        $response = $next($request);

        // 4. The verify result carries the headers an embedded page needs (the
        //    Content-Security-Policy that allows the admin to frame it). Copy them on.
        foreach ((array) $result->response->headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
