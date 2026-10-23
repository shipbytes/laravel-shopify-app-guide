<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every delivery Shopify makes, keyed by its Shopify-Webhook-Id. Shopify retries
        // until it sees a 2xx, so the same delivery can arrive twice; the unique index is
        // what makes handling it twice harmless.
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('webhook_id')->unique();
            $table->string('topic');
            $table->string('shop');
            $table->timestamp('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
