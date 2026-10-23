<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the storefront block reads and writes. Liquid gives it the numeric product ID;
 * the app stores the global ID, so the two are joined here.
 */
class ReviewController extends Controller
{
    public function index(Request $request, int $productId): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $reviews = $shop->reviews()
            ->where('product_id', $this->gid($productId))
            ->where('status', 'approved')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($review) => [
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'author_name' => $review->author_name,
                'created_at' => $review->created_at->toFormattedDateString(),
            ]);

        return response()->json(['reviews' => $reviews]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
            'product_title' => ['required', 'string', 'max:255'],
        ]);

        $review = $shop->reviews()->create([
            ...$data,
            'product_id' => $this->gid($productId),
            'status' => $shop->settings->auto_approve ? 'approved' : 'pending',
        ]);

        if ($review->status === 'approved') {
            SyncProductRating::dispatch($shop, $review->product_id);
        }

        return response()->json(['status' => $review->status], 201);
    }

    private function gid(int $productId): string
    {
        return "gid://shopify/Product/{$productId}";
    }
}
