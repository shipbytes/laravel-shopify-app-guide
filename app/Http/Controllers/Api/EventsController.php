<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Events deliveries. The payload names the topic, the action and the subscription
 * handle, carries the data the subscription's GraphQL query asked for, and lists the
 * query variables. For a delete the data is null; the ID is in query_variables.
 */
class EventsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $shop = Shop::where('shop', $request->attributes->get('shop'))->first();
        $event = $request->json()->all();

        match ($event['handle'] ?? null) {
            'product-deleted' => $this->productDeleted($shop, $event['query_variables']['productId'] ?? null),
            default => Log::info('Unhandled event', ['handle' => $event['handle'] ?? null, 'action' => $event['action'] ?? null]),
        };

        return response()->noContent();
    }

    /** Reviews of a product that no longer exists have nowhere to show; drop them. */
    private function productDeleted(?Shop $shop, ?string $productId): void
    {
        if ($shop && $productId) {
            $shop->reviews()->where('product_id', $productId)->delete();
        }
    }
}
