<?php

namespace Tests\Feature;

use App\Jobs\SyncProductRating;
use App\Models\Shop;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReviewApiTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->shop = Shop::create(['shop' => 'test-shop']);
        $this->shop->forceFill([
            'access_token' => 'shpat_test',
            'scope' => 'write_products',
            'refresh_token' => 'refresh_test',
            'access_token_expires_at' => now()->addMinutes(30),
            'refresh_token_expires_at' => now()->addDays(60),
            'installed_at' => now(),
        ])->save();
    }

    /** @return array<string, string> */
    private function authHeaders(string $shop = 'test-shop'): array
    {
        $now = time();

        $token = JWT::encode([
            'iss' => "https://{$shop}.myshopify.com/admin",
            'dest' => "https://{$shop}.myshopify.com",
            'aud' => config('shopify.api_key'),
            'sub' => '1234567890',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => uniqid(),
            'sid' => 'session-id',
        ], config('shopify.api_secret'), 'HS256');

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_a_review_is_created_and_the_rating_sync_is_queued(): void
    {
        $response = $this->postJson('/api/reviews', [
            'product_id' => 'gid://shopify/Product/1',
            'product_title' => 'Cushion cover',
            'author_name' => 'Asha',
            'rating' => 5,
            'body' => 'Lovely print.',
        ], $this->authHeaders());

        $response->assertCreated()->assertJsonPath('status', 'approved');
        $this->assertDatabaseHas('reviews', ['shop_id' => $this->shop->id, 'rating' => 5]);
        Queue::assertPushed(SyncProductRating::class, fn ($job) => $job->productId === 'gid://shopify/Product/1');
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->postJson('/api/reviews', ['rating' => 9], $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'author_name', 'rating', 'body']);
    }

    public function test_reviews_are_scoped_to_the_requesting_shop(): void
    {
        $other = Shop::create(['shop' => 'other-shop']);
        $review = $other->reviews()->create([
            'product_id' => 'gid://shopify/Product/1', 'product_title' => 'X',
            'author_name' => 'Someone', 'rating' => 1, 'body' => 'Not yours', 'status' => 'approved',
        ]);

        $this->getJson('/api/reviews', $this->authHeaders())->assertOk()->assertJsonPath('total', 0);
        $this->patchJson("/api/reviews/{$review->id}", ['status' => 'rejected'], $this->authHeaders())->assertNotFound();
    }

    public function test_changing_the_status_requeues_the_rating_sync(): void
    {
        $review = $this->shop->reviews()->create([
            'product_id' => 'gid://shopify/Product/1', 'product_title' => 'X',
            'author_name' => 'Asha', 'rating' => 5, 'body' => 'Lovely', 'status' => 'pending',
        ]);

        $this->patchJson("/api/reviews/{$review->id}", ['status' => 'approved'], $this->authHeaders())
            ->assertOk()->assertJsonPath('status', 'approved');

        Queue::assertPushed(SyncProductRating::class, 1);
    }

    public function test_settings_have_defaults_and_can_be_saved(): void
    {
        $this->getJson('/api/settings', $this->authHeaders())
            ->assertOk()->assertJsonPath('auto_approve', false);

        $this->putJson('/api/settings', ['auto_approve' => true, 'widget_title' => 'What customers say'], $this->authHeaders())
            ->assertOk()->assertJsonPath('widget_title', 'What customers say');

        $this->assertDatabaseHas('shop_settings', ['shop_id' => $this->shop->id, 'auto_approve' => true]);
    }
}
