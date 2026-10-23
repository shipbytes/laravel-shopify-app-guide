<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            // The shop's myshopify subdomain, e.g. "example-store". The package always
            // identifies a shop this way, so the app does too.
            $table->string('shop')->unique();
            // Expiring offline access token and what is needed to renew it. The token
            // and refresh token are encrypted at rest by the model's casts.
            $table->text('access_token')->nullable();
            $table->string('scope')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
