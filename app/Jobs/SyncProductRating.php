<?php

namespace App\Jobs;

use App\Models\Shop;
use App\Shopify\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Writes a product's approved-review average and count to two app-owned metafields,
 * where the storefront (part 6) reads them. Queued because it is an Admin API round
 * trip the merchant should not wait for, and because it runs after webhooks too.
 */
class SyncProductRating implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Shop $shop, public string $productId) {}

    public function handle(Admin $admin): void
    {
        $approved = $this->shop->reviews()
            ->where('product_id', $this->productId)
            ->where('status', 'approved');

        $count = $approved->count();
        $average = $count === 0 ? 0 : round($approved->avg('rating'), 2);

        $result = $admin->query($this->shop, <<<'GRAPHQL'
            mutation SyncProductRating($metafields: [MetafieldsSetInput!]!) {
              metafieldsSet(metafields: $metafields) {
                metafields { key value }
                userErrors { field message }
              }
            }
            GRAPHQL, [
            'metafields' => [
                [
                    'ownerId' => $this->productId,
                    'namespace' => '$app:reviewmini',
                    'key' => 'rating_average',
                    'type' => 'number_decimal',
                    'value' => number_format($average, 2, '.', ''),
                ],
                [
                    'ownerId' => $this->productId,
                    'namespace' => '$app:reviewmini',
                    'key' => 'rating_count',
                    'type' => 'number_integer',
                    'value' => (string) $count,
                ],
            ],
        ]);

        if (! $result->ok) {
            // Let the queue retry transient failures; "unauthorized" already dropped the token.
            throw new \RuntimeException("metafieldsSet failed for {$this->shop->shop}: {$result->log->code}");
        }

        if ($errors = $result->data['metafieldsSet']['userErrors']) {
            Log::warning('metafieldsSet user errors', ['shop' => $this->shop->shop, 'errors' => $errors]);
        }
    }
}
