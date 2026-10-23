<?php

namespace Tests\Feature;

use App\Models\Shop;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppHomeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Shopify signs ID tokens with the app's client secret (HS256), so a test can mint
     * one the package accepts. The claims mirror what the admin sends.
     */
    private function idToken(string $shop = 'test-shop', ?string $secret = null): string
    {
        $now = time();

        return JWT::encode([
            'iss' => "https://{$shop}.myshopify.com/admin",
            'dest' => "https://{$shop}.myshopify.com",
            'aud' => config('shopify.api_key'),
            'sub' => '1234567890',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => uniqid(),
            'sid' => 'session-id',
        ], $secret ?? config('shopify.api_secret'), 'HS256');
    }

    private function installedShop(string $shop = 'test-shop'): Shop
    {
        $model = Shop::create(['shop' => $shop]);
        $model->forceFill([
            'access_token' => 'shpat_test',
            'scope' => 'write_products',
            'refresh_token' => 'refresh_test',
            'access_token_expires_at' => now()->addMinutes(30),
            'refresh_token_expires_at' => now()->addDays(60),
            'installed_at' => now(),
        ])->save();

        return $model;
    }

    public function test_a_request_without_a_token_is_sent_to_the_token_page(): void
    {
        $response = $this->get('/?shop=test-shop.myshopify.com&embedded=1');

        $response->assertRedirect();
        $this->assertStringContainsString('/auth/patch-id-token', $response->headers->get('Location'));
    }

    public function test_the_app_home_renders_for_a_verified_shop(): void
    {
        $this->installedShop();

        $response = $this->get('/?shop=test-shop.myshopify.com&id_token='.$this->idToken());

        $response->assertOk();
        $response->assertSee('Installed on test-shop.myshopify.com');
        $this->assertStringContainsString(
            'frame-ancestors https://test-shop.myshopify.com',
            $response->headers->get('Content-Security-Policy'),
        );
    }

    public function test_a_token_signed_with_the_wrong_secret_is_rejected(): void
    {
        $this->installedShop();

        $response = $this->getJson('/api/shop', [
            'Authorization' => 'Bearer '.$this->idToken(secret: 'not-the-secret-but-also-32-bytes-long!'),
        ]);

        $response->assertUnauthorized();
        $response->assertHeader('X-Shopify-Retry-Invalid-Session-Request', '1');
    }
}
