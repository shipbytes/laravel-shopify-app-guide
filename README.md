# ReviewMini

An embedded Shopify app built with Laravel 13, React 19, TypeScript and Polaris 2, written
for the guide [Building a Shopify app with Laravel](https://shipbytes.dev/blog/building-a-shopify-app-with-laravel/)
on shipbytes.dev. Merchants collect and moderate product reviews in the admin; a theme app
extension shows them on the storefront.

Each commit on `main` is one part of the guide:

1. Project, Shopify CLI, app record and tunnel
2. Authentication with ID tokens and expiring access tokens
3. Reviews, settings, product search and the rating metafield job
4. Admin UI with React, TypeScript and Polaris 2
5. Webhooks, Events and the privacy topics
6. App proxy, theme app extension and the end-to-end check

## Running it

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
shopify app config link        # creates or links the app record
shopify app dev --store <your-dev-store>.myshopify.com
```

`shopify app dev` pulls the client ID and secret into `.env`, starts the web server, the
queue worker and the Vite watcher, opens a tunnel and installs the app on the store.
`shopify app deploy` publishes the configuration in `shopify.app.toml` (scopes, webhooks,
the Events subscription, the metafield definitions, the app proxy) and the theme extension.

Tests: `php artisan test`.

MIT licensed.
