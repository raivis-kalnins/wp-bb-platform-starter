# WP BB Platform Starter

Bedrock + Acorn WordPress development platform for **WP BBTheme**, **BBuilder**, WooCommerce and larger custom WordPress systems.

## Included stack

- PHP 8.4 container
- WordPress 7.1.x via Roots/Bedrock
- Roots Acorn 6.x
- WooCommerce
- Polylang
- Redis object cache
- MariaDB 11.4
- WP-CLI
- Mailpit
- phpMyAdmin
- WP BB Platform packages and the WooCommerce Home & Garden child theme

## Requirements

For the recommended local setup on Windows:

1. Windows 11 with WSL2 enabled
2. Docker Desktop with WSL2 integration enabled
3. Git
4. PowerShell

Linux can run the same Docker stack directly.

## Quick start — Windows / WSL2

Clone the repository, open PowerShell in the project folder and run:

```powershell
./setup-wsl.ps1
```

The script copies the project into the WSL Linux filesystem and runs `bin/install` there for better Docker performance.

## Quick start — Linux / WSL

```bash
chmod +x bin/*
./bin/install
```

The installer will:

- create `.env` from `.env.example` when needed
- generate secure database/password salts
- build and start the Docker services
- install Composer dependencies
- install WordPress when the database is empty
- activate WooCommerce, Redis, Polylang, UpdraftPlus and the WP BB packages
- activate the `wp-bbtheme-child-woo-laravel-shop` theme
- enable pretty permalinks
- enable the Redis object cache

Default local services:

- Store: `http://localhost:8080`
- WordPress admin: `http://localhost:8080/wp/wp-admin/`
- Mailpit: `http://localhost:8025`
- phpMyAdmin: `http://localhost:8081`

When using the shared reverse-proxy compose file, the normal development hostname is `wpbb.localhost`.

## Useful commands

```bash
bin/wp plugin list
bin/wp theme list
bin/wp cache flush
bin/wp rewrite flush
bin/artisan about
bin/deploy-check
bin/build-release
```

## Redis

Redis settings live in `config/application.php`. The project already defines the host, port and a site-specific prefix. The separate `redis-bedrock-snippet.php` file is only a reference snippet and should not replace this README.

## Licensed packages

ACF Pro is intentionally not committed publicly. Put the licensed ZIP at:

```text
packages/private/advanced-custom-fields-pro.zip
```

Then run:

```bash
bin/install-acf-pro packages/private/advanced-custom-fields-pro.zip
```

## Project layout

```text
config/                         Bedrock configuration
web/                            WordPress web root
web/app/plugins/                WordPress plugins
web/app/themes/                 WordPress themes
packages/                       Local Composer packages
bin/                            Install, WP-CLI, deploy and release helpers
docker/                         PHP/Apache container configuration
docs/                           Deployment and architecture documentation
```

## Storefront fixes export

The `wpbb-v414-fixes` plugin supplied with the October 2026 fixes addresses:

- large blank gaps on the home page caused by stale `content-visibility` intrinsic sizes
- shop/category filter range styling
- compare icon colour/style consistency
- creation and repair of Delivery & Payment, Returns & Warranty and Terms pages
- order-tracking page alignment

After adding the plugin folder to `web/app/plugins/`, activate it and flush rewrite rules once.
