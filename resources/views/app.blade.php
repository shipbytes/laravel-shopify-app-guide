<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ReviewMini</title>
  {{-- App Bridge first, with the client ID; Polaris 2 after it, so it can read the admin's theme. --}}
  <meta name="shopify-api-key" content="{{ config('shopify.api_key') }}">
  <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
  <script src="https://cdn.shopify.com/shopifycloud/polaris-2.js"></script>
</head>
<body>
  <s-page heading="ReviewMini">
    <s-section heading="It works">
      <s-paragraph>ReviewMini is running inside the Shopify admin. The real app starts in part 2.</s-paragraph>
    </s-section>
  </s-page>
</body>
</html>
