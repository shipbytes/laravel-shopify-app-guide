<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shopify\App\ShopifyApp;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One ShopifyApp instance for the whole request. It verifies requests from Shopify,
        // exchanges ID tokens for access tokens and talks to the Admin API.
        $this->app->singleton(ShopifyApp::class, fn () => new ShopifyApp(
            clientId: config('shopify.api_key'),
            clientSecret: config('shopify.api_secret'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
