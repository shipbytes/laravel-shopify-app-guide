<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shopify\Adapter;
use App\Shopify\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Product search for the admin UI's "add a review" form. The app stores a product's ID
 * and title with each review; everything else about the product stays in Shopify.
 */
class ProductController extends Controller
{
    public function index(Request $request, Admin $admin): JsonResponse|Response
    {
        $shop = $request->attributes->get('shop');
        $search = trim((string) $request->query('query', ''));

        $result = $admin->query($shop, <<<'GRAPHQL'
            query SearchProducts($query: String) {
              products(first: 10, query: $query, sortKey: TITLE) {
                nodes {
                  id
                  title
                  handle
                  featuredMedia { preview { image { url(transform: { maxWidth: 80, maxHeight: 80 }) } } }
                }
              }
            }
            GRAPHQL, [
            // Shopify's search syntax; "title:*shirt*" finds "T-shirt" and "Shirt, blue".
            'query' => $search === '' ? null : "title:*{$search}*",
        ], invalidTokenResponse: $request->attributes->get('shopify')->invalidTokenResponse);

        if (! $result->ok) {
            return Adapter::response($result);
        }

        $products = array_map(fn (array $node) => [
            'id' => $node['id'],
            'title' => $node['title'],
            'handle' => $node['handle'],
            'image' => $node['featuredMedia']['preview']['image']['url'] ?? null,
        ], $result->data['products']['nodes']);

        return response()->json($products);
    }
}
