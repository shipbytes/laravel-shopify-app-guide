<?php

use App\Http\Middleware\AuthenticateAppHome;
use App\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TLS ends at the tunnel (and later at the load balancer); the app itself speaks
        // plain HTTP. Trusting the forwarded headers keeps generated URLs on https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'shopify.app-home' => AuthenticateAppHome::class,
            'shopify.webhook' => VerifyShopifyWebhook::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
