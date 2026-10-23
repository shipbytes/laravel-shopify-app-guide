<?php

namespace App\Http\Middleware;

use App\Models\WebhookDelivery;
use App\Shopify\Adapter;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;
use Symfony\Component\HttpFoundation\Response;

/**
 * For deliveries Shopify pushes to the app: classic webhooks and Events. Both are
 * signed the same way, an HMAC of the raw body in the Shopify-Hmac-Sha256 header,
 * and both carry a Shopify-Webhook-Id the app uses to ignore repeats.
 */
class VerifyShopifyWebhook
{
    public function __construct(private ShopifyApp $shopify) {}

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->shopify->verifyWebhookReq(Adapter::req($request));

        if (! $result->ok) {
            return Adapter::response($result);
        }

        // Webhooks prefix their headers with X-; Events deliveries do not, and add
        // Shopify-Action ("delete") next to the topic ("Product").
        $topic = $request->header('X-Shopify-Topic') ?? $request->header('Shopify-Topic', '');
        $webhookId = $request->header('X-Shopify-Webhook-Id') ?? $request->header('Shopify-Webhook-Id', '');

        if ($action = $request->header('Shopify-Action')) {
            $topic = "{$topic}/{$action}";
        }

        try {
            WebhookDelivery::create([
                'webhook_id' => $webhookId,
                'topic' => $topic,
                'shop' => $result->shop,
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already handled. Tell Shopify so, or it keeps retrying.
            return response()->noContent();
        }

        $request->attributes->set('shop', $result->shop);
        $request->attributes->set('topic', $topic);

        return $next($request);
    }
}
