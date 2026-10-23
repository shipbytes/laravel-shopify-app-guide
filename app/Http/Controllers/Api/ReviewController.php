<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductRating;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $reviews = $shop->reviews()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('product_id'), fn ($q, $id) => $q->where('product_id', $id))
            ->latest()
            ->paginate(20);

        return response()->json($reviews);
    }

    public function store(Request $request): JsonResponse
    {
        $shop = $request->attributes->get('shop');

        $data = $request->validate([
            'product_id' => ['required', 'string', 'starts_with:gid://shopify/Product/'],
            'product_title' => ['required', 'string', 'max:255'],
            'author_name' => ['required', 'string', 'max:100'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:3000'],
            'status' => ['sometimes', Rule::in(Review::STATUSES)],
        ]);

        $review = $shop->reviews()->create($data + ['status' => 'approved']);

        SyncProductRating::dispatch($shop, $review->product_id);

        return response()->json($review, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        // Scoped lookup: a review ID from another shop is a 404, not someone else's data.
        $review = $shop->reviews()->findOrFail($id);

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(Review::STATUSES)],
            'rating' => ['sometimes', 'integer', 'between:1,5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'body' => ['sometimes', 'string', 'max:3000'],
        ]);

        $review->update($data);

        if ($review->wasChanged(['status', 'rating'])) {
            SyncProductRating::dispatch($shop, $review->product_id);
        }

        return response()->json($review);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $shop = $request->attributes->get('shop');
        $review = $shop->reviews()->findOrFail($id);

        $review->delete();

        SyncProductRating::dispatch($shop, $review->product_id);

        return response()->json(status: 204);
    }
}
