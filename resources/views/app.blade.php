<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ReviewMini</title>
    {{-- App Bridge reads the client ID from this meta tag. --}}
    <meta name="shopify-api-key" content="{{ config('shopify.api_key') }}">
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <script src="https://cdn.shopify.com/shopifycloud/polaris-2.js"></script>
    @vite('resources/js/main.tsx')
</head>
<body>
    <div id="app"></div>
</body>
</html>
