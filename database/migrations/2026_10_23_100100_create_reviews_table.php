<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            // Shopify's global ID, e.g. "gid://shopify/Product/8501234567890".
            $table->string('product_id');
            $table->string('product_title');
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->timestamps();

            $table->index(['shop_id', 'product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
