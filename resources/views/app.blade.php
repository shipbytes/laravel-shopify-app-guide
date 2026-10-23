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
</head>
<body>
    <s-page heading="ReviewMini">
        <s-section heading="Installed on {{ $shop->domain() }}">
            <s-paragraph id="shop-info">Asking the Admin API who we are…</s-paragraph>
        </s-section>
    </s-page>

    <script>
        // App Bridge intercepts same-origin fetch() calls and adds the ID token as
        // an Authorization: Bearer header, so the API route can verify them.
        fetch('/api/shop')
            .then((response) => response.json())
            .then((shop) => {
                document.getElementById('shop-info').textContent =
                    `${shop.name} (${shop.myshopifyDomain}), ${shop.plan.publicDisplayName}, prices in ${shop.currencyCode}.`;
            })
            .catch((error) => {
                document.getElementById('shop-info').textContent = `Request failed: ${error}`;
            });
    </script>
</body>
</html>
