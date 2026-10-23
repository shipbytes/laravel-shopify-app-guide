<?php

return [
    // Client ID and secret of the app in the Dev Dashboard. The CLI injects both into the
    // dev server's environment; .env carries them for everything else (queue workers, tests).
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),

    // Admin GraphQL API version. Pinned, bumped deliberately each quarter.
    'api_version' => env('SHOPIFY_API_VERSION', '2026-10'),

    // The route App Bridge uses to obtain a fresh ID token for a navigation that arrived
    // without one. Must match the route registered in routes/web.php.
    'patch_id_token_path' => '/auth/patch-id-token',
];
