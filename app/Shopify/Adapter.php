<?php

namespace App\Shopify;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Translates between Laravel's request/response objects and the shapes the
 * shopify/shopify-app-php package works with. The package is framework-agnostic,
 * so every framework writes these two functions once.
 */
final class Adapter
{
    /**
     * The package wants the raw request: method, every header, the full URL including
     * the query string, and the unmodified body. Verification depends on the exact bytes
     * Shopify sent, so nothing is filtered or re-encoded here.
     *
     * One wrinkle: Laravel keeps every header as a list of values, and the package
     * expects one string per header. Join repeated values the way HTTP itself does.
     *
     * @return array{method: string, headers: array<string, string>, url: string, body: string}
     */
    public static function req(Request $request): array
    {
        return [
            'method' => $request->method(),
            'headers' => array_map(fn (array $values) => implode(', ', $values), $request->headers->all()),
            'url' => $request->fullUrl(),
            'body' => $request->getContent(),
        ];
    }

    /**
     * Every package call returns a result with a pre-built HTTP response and a log entry.
     * When a call fails, that response is what the browser (or Shopify) must receive:
     * it carries the status and headers that make App Bridge retry with a fresh token,
     * redirect to the token page, or give up. Return it as-is.
     */
    public static function response(object $result): Response
    {
        Log::info("shopify: {$result->log->code} - {$result->log->detail}");

        return response($result->response->body, $result->response->status)
            ->withHeaders((array) $result->response->headers);
    }
}
