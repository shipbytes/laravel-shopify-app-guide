<?php

namespace Tests\Feature;

use App\Jobs\SyncProductRating;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->shop = Shop::create(['shop' => 'test-shop']);
        $this->shop->forceFill(['access_token' => 'shpat_test', 'installed_at' => now()])->save();
    }

    /**
     * Shopify signs app proxy requests over the query string: parameters sorted by
     * name, joined as key=value with no separator, HMAC-SHA256 with the client secret,
     * hex encoded. The test signs the same way.
     *
     * @param  array<string, string>  $params
     */
    private function signed(string $path, array $params = []): string
    {
        $params += [
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => (string) time(),
            'path_prefix' => '/apps/reviews',
        ];
        ksort($params);

        $joined = implode('', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($params), $params));
        $params['signature'] = hash_hmac('sha256', $joined, config('shopify.api_secret'));

        return $path.'?'.http_build_query($params);
    }

    public function test_an_unsigned_request_is_rejected(): void
    {
        $this->getJson('/storefront/products/1?shop=test-shop.myshopify.com')->assertUnauthorized();
    }

    public function test_only_approved_reviews_are_listed(): void
    {
        foreach (['approved', 'pending', 'rejected'] as $status) {
            $this->shop->reviews()->create([
                'product_id' => 'gid://shopify/Product/1', 'product_title' => 'Cushion',
                'author_name' => 'Asha', 'rating' => 4, 'body' => $status, 'status' => $status,
            ]);
        }

        $this->getJson($this->signed('/storefront/products/1'))
            ->assertOk()
            ->assertJsonCount(1, 'reviews')
            ->assertJsonPath('reviews.0.body', 'approved')
            ->assertJsonMissingPath('reviews.0.author_email');
    }

    public function test_a_visitor_review_waits_for_approval_by_default(): void
    {
        $this->postJson($this->signed('/storefront/products/1/reviews'), [
            'author_name' => 'Priya', 'rating' => 5, 'body' => 'Soft and well made', 'product_title' => 'Cushion',
        ])->assertCreated()->assertJsonPath('status', 'pending');

        Queue::assertNothingPushed();
    }

    public function test_auto_approve_publishes_and_syncs_the_rating(): void
    {
        $this->shop->settings()->create(['auto_approve' => true]);

        $this->postJson($this->signed('/storefront/products/1/reviews'), [
            'author_name' => 'Priya', 'rating' => 5, 'body' => 'Soft and well made', 'product_title' => 'Cushion',
        ])->assertCreated()->assertJsonPath('status', 'approved');

        Queue::assertPushed(SyncProductRating::class);
    }
}
