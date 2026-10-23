<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Classic webhooks: the app lifecycle and the three privacy topics every app must
 * answer. Each handler is a quick database change, so it runs inline; Shopify wants a
 * 2xx within five seconds, and anything slower belongs in a queued job.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $shop = Shop::where('shop', $request->attributes->get('shop'))->first();
        $payload = $request->json()->all();

        match ($request->attributes->get('topic')) {
            'app/uninstalled' => $this->uninstalled($shop),
            'customers/data_request' => $this->customerDataRequest($shop, $payload),
            'customers/redact' => $this->customerRedact($shop, $payload),
            'shop/redact' => $this->shopRedact($shop),
            default => Log::info('Unhandled webhook topic', ['topic' => $request->attributes->get('topic')]),
        };

        return response()->noContent();
    }

    /** The token is dead from this moment; keep the rows for shop/redact 48 hours later. */
    private function uninstalled(?Shop $shop): void
    {
        $shop?->forgetAccessToken();
        $shop?->forceFill(['uninstalled_at' => now()])->save();
    }

    /**
     * A customer asked the merchant for their data. Shopify sends the request, the
     * merchant answers it; the app's job is to make the data findable. Here the only
     * personal data is the reviewer email, so log the match for the merchant's export.
     */
    private function customerDataRequest(?Shop $shop, array $payload): void
    {
        $email = $payload['customer']['email'] ?? null;
        $count = $shop && $email ? $shop->reviews()->where('author_email', $email)->count() : 0;

        Log::info('Customer data request', [
            'shop' => $shop?->shop,
            'request_id' => $payload['data_request']['id'] ?? null,
            'reviews_matching' => $count,
        ]);
    }

    /** Strip personal data from the customer's reviews but keep the ratings. */
    private function customerRedact(?Shop $shop, array $payload): void
    {
        $email = $payload['customer']['email'] ?? null;

        if ($shop && $email) {
            $shop->reviews()->where('author_email', $email)->update([
                'author_name' => 'Anonymous',
                'author_email' => null,
            ]);
        }
    }

    /** 48 hours after uninstall: everything about the shop goes. */
    private function shopRedact(?Shop $shop): void
    {
        $shop?->delete(); // reviews and settings cascade
    }
}
