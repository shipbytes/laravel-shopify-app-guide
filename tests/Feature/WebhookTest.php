<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::create(['shop' => 'test-shop']);
        $this->shop->forceFill(['access_token' => 'shpat_test', 'installed_at' => now()])->save();
        $this->shop->reviews()->create([
            'product_id' => 'gid://shopify/Product/1', 'product_title' => 'Cushion',
            'author_name' => 'Asha', 'author_email' => 'asha@example.com', 'rating' => 5, 'body' => 'Lovely', 'status' => 'approved',
        ]);
    }

    /**
     * Shopify signs the raw body with the client secret; the test signs the same way.
     *
     * @param  array<string, mixed>  $payload
     */
    private function deliver(string $path, string $topic, array $payload, string $webhookId = 'delivery-1', array $extraHeaders = []): TestResponse
    {
        $body = json_encode($payload);
        $hmac = base64_encode(hash_hmac('sha256', $body, config('shopify.api_secret'), true));

        return $this->call('POST', $path, [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'X-Shopify-Hmac-Sha256' => $hmac,
            'X-Shopify-Shop-Domain' => 'test-shop.myshopify.com',
            'X-Shopify-Topic' => $topic,
            'X-Shopify-Webhook-Id' => $webhookId,
            ...$extraHeaders,
        ]), $body);
    }

    public function test_a_delivery_with_a_bad_signature_is_rejected(): void
    {
        $this->postJson('/api/webhooks', ['shop_domain' => 'test-shop.myshopify.com'], [
            'X-Shopify-Hmac-Sha256' => base64_encode('nope'),
            'X-Shopify-Shop-Domain' => 'test-shop.myshopify.com',
            'X-Shopify-Topic' => 'app/uninstalled',
            'X-Shopify-Webhook-Id' => 'x',
        ])->assertUnauthorized();

        $this->assertNotNull($this->shop->fresh()->access_token);
    }

    public function test_app_uninstalled_drops_the_token_but_keeps_the_data(): void
    {
        $this->deliver('/api/webhooks', 'app/uninstalled', ['domain' => 'test-shop.myshopify.com'])->assertNoContent();

        $shop = $this->shop->fresh();
        $this->assertNull($shop->access_token);
        $this->assertNotNull($shop->uninstalled_at);
        $this->assertSame(1, $shop->reviews()->count());
    }

    public function test_a_repeated_delivery_is_acknowledged_without_being_handled_twice(): void
    {
        $this->deliver('/api/webhooks', 'app/uninstalled', [], 'same-id')->assertNoContent();
        $this->shop->forceFill(['access_token' => 'shpat_new'])->save();

        $this->deliver('/api/webhooks', 'app/uninstalled', [], 'same-id')->assertNoContent();

        $this->assertSame('shpat_new', $this->shop->fresh()->access_token);
    }

    public function test_customers_redact_anonymises_their_reviews(): void
    {
        $this->deliver('/api/webhooks', 'customers/redact', [
            'shop_domain' => 'test-shop.myshopify.com',
            'customer' => ['id' => 1, 'email' => 'asha@example.com'],
        ])->assertNoContent();

        $this->assertDatabaseHas('reviews', ['author_name' => 'Anonymous', 'author_email' => null, 'rating' => 5]);
    }

    public function test_shop_redact_removes_everything(): void
    {
        $this->deliver('/api/webhooks', 'shop/redact', ['shop_domain' => 'test-shop.myshopify.com'])->assertNoContent();

        $this->assertDatabaseMissing('shops', ['shop' => 'test-shop']);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_product_delete_event_removes_its_reviews(): void
    {
        $this->call('POST', '/api/events', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'Shopify-Hmac-Sha256' => base64_encode(hash_hmac('sha256', $body = json_encode([
                'topic' => 'Product', 'action' => 'delete', 'handle' => 'product-deleted',
                'data' => ['product' => null],
                'query_variables' => ['productId' => 'gid://shopify/Product/1'],
            ]), config('shopify.api_secret'), true)),
            'Shopify-Shop-Domain' => 'test-shop.myshopify.com',
            'Shopify-Topic' => 'Product',
            'Shopify-Action' => 'delete',
            'Shopify-Webhook-Id' => 'event-1',
        ]), $body)->assertNoContent();

        $this->assertDatabaseCount('reviews', 0);
        $this->assertDatabaseHas('webhook_deliveries', ['topic' => 'Product/delete']);
    }
}
