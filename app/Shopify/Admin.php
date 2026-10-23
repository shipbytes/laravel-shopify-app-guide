<?php

namespace App\Shopify;

use App\Models\Shop;
use RuntimeException;
use Shopify\App\ShopifyApp;
use Shopify\App\Types\GQLResult;

/**
 * Admin API calls on behalf of a shop. Wraps the package's GraphQL method with the two
 * things every call needs: a token that is fresh enough, and the housekeeping when
 * Shopify rejects it.
 */
final class Admin
{
    public function __construct(private ShopifyApp $shopify) {}

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>|null  $invalidTokenResponse  from the verify result when
     *                                                           answering an App Home request; null in jobs
     */
    public function query(Shop $shop, string $query, array $variables = [], ?array $invalidTokenResponse = null): GQLResult
    {
        $this->ensureFreshToken($shop);

        $result = $this->shopify->adminGraphQLRequest(
            $query,
            shop: $shop->shop,
            accessToken: $shop->access_token,
            apiVersion: config('shopify.api_version'),
            invalidTokenResponse: $invalidTokenResponse,
            variables: $variables ?: null,
        );

        // Shopify no longer accepts the token: the app was uninstalled or the token was
        // revoked. Drop it so the next App Home visit exchanges a new one.
        if (! $result->ok && $result->log->code === 'unauthorized') {
            $shop->forgetAccessToken();
        }

        return $result;
    }

    /**
     * Inside a request the middleware already did this. In a queued job nobody has, and
     * an access token lives for an hour, so a job that runs later must refresh first.
     */
    private function ensureFreshToken(Shop $shop): void
    {
        $token = $shop->accessToken()
            ?? throw new RuntimeException("Shop {$shop->shop} has no access token; the app is not installed.");

        $refresh = $this->shopify->refreshTokenExchangedAccessToken($token);

        if (! $refresh->ok) {
            $shop->forgetAccessToken();
            throw new RuntimeException("Could not refresh the access token for {$shop->shop}: {$refresh->log->code}");
        }

        if ($refresh->accessToken !== null) {
            $shop->storeAccessToken($refresh->accessToken);
        }
    }
}
