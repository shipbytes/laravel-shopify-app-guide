<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Shopify\App\Types\TokenExchangeAccessToken;

class Shop extends Model
{
    protected $fillable = ['shop'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'installed_at' => 'datetime',
            'uninstalled_at' => 'datetime',
        ];
    }

    /** The full myshopify domain, e.g. "example-store.myshopify.com". */
    public function domain(): string
    {
        return "{$this->shop}.myshopify.com";
    }

    /**
     * The stored token in the shape the package accepts, or null when the shop has none
     * (never installed, uninstalled, or the token was dropped after Shopify rejected it).
     */
    public function accessToken(): ?TokenExchangeAccessToken
    {
        if ($this->access_token === null) {
            return null;
        }

        return new TokenExchangeAccessToken(
            accessMode: 'offline',
            shop: $this->shop,
            token: $this->access_token,
            expires: $this->access_token_expires_at?->toIso8601String(),
            scope: $this->scope ?? '',
            refreshToken: $this->refresh_token,
            refreshTokenExpires: $this->refresh_token_expires_at?->toIso8601String(),
            user: null,
        );
    }

    /** Save a token the package returned, from a first exchange or a refresh. */
    public function storeAccessToken(TokenExchangeAccessToken $token): void
    {
        $this->forceFill([
            'access_token' => $token->token,
            'scope' => $token->scope,
            'refresh_token' => $token->refreshToken,
            'access_token_expires_at' => $token->expires,
            'refresh_token_expires_at' => $token->refreshTokenExpires,
            'installed_at' => $this->installed_at ?? now(),
            'uninstalled_at' => null,
        ])->save();
    }

    /** Drop the token so the next App Home request exchanges a fresh one. */
    public function forgetAccessToken(): void
    {
        $this->forceFill([
            'access_token' => null,
            'scope' => null,
            'refresh_token' => null,
            'access_token_expires_at' => null,
            'refresh_token_expires_at' => null,
        ])->save();
    }
}
