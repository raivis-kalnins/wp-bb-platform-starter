# Deploy to cPanel / reseller hosting

Local development uses Docker; production does not need Docker.

## Recommended server layout

```text
/home/ACCOUNT/apps/example/
    composer.json
    composer.lock
    config/
    packages/
    vendor/
    web/
        index.php
        wp-config.php
        wp/
        app/

Domain document root -> /home/ACCOUNT/apps/example/web
```

The key requirement is that the public document root points to `web/`, not to the Bedrock project root.

## Deployment sequence

1. Run `bin/deploy-check` locally.
2. Run `bin/build-release`.
3. Create a MariaDB database/user in cPanel.
4. Upload/extract the release outside the public document root where possible.
5. Point the domain document root to the release's `web/` directory.
6. Create production `.env` from `.env.example` and use production database credentials/HTTPS URLs.
7. Run `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` over SSH if the release was not built with vendor included.
8. Run `wp --path=/home/ACCOUNT/apps/example/web/wp core is-installed` and the required database migrations/updates.
9. Run `wp acorn migrate --force` for domain modules that use Acorn migrations.
10. Flush rewrite/object caches and verify cron.

## Cron

For a serious WooCommerce site, set `DISABLE_WP_CRON=true` and add a real cPanel cron, for example every five minutes:

```bash
cd /home/ACCOUNT/apps/example && /usr/local/bin/wp --path=web/wp cron event run --due-now >/dev/null 2>&1
```

Confirm the actual WP-CLI path on the hosting account with `which wp`.

## Redis

Shared cPanel/reseller plans do not always expose Redis. The platform works without Redis; disable/remove the Redis cache plugin on production if the account does not provide a compatible Redis service.

## Primary-domain limitation

If cPanel does not allow the primary domain document root to point at the Bedrock `web/` directory, use an addon/subdomain with configurable document root or ask the hosting provider to change it. Avoid exposing the whole project root under `public_html`.
