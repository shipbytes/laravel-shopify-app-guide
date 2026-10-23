<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained()->cascadeOnDelete();
            // Publish storefront reviews without a merchant looking at them first.
            $table->boolean('auto_approve')->default(false);
            // Heading of the review block on the storefront.
            $table->string('widget_title')->default('Customer reviews');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
