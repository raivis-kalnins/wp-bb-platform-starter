# WP BB Platform Starter

Reusable WordPress application platform built on Bedrock, Acorn, WooCommerce, Gutenberg/ACF, Docker/WSL2, Redis and WP-CLI.

This root README documents the **whole platform**. Storefront/theme-specific documentation stays inside the corresponding package directories.

## What the platform is

WP BB Platform keeps WordPress where WordPress is strong while adding a cleaner application structure for larger projects. It is intended for normal business websites as well as larger WooCommerce, warehouse, booking, directory, ERP/CRM/PIM and custom-data systems.

Core principles:

- WordPress Core is managed as a dependency instead of project source code.
- Composer manages WordPress, plugins and project packages.
- Bedrock-style configuration separates development, staging and production.
- Acorn adds Laravel-style services, Blade, migrations, Eloquent, console tooling and application structure.
- Gutenberg remains the primary editor.
- ACF is supported for structured content and selected block workflows.
- WooCommerce remains the commerce engine.
- Reusable business logic lives in plugins/modules, not in presentation themes.
- BBTheme is the reusable parent theme.
- Sector/storefront child themes own presentation and WooCommerce template/UI overrides.
- Large transactional data should use purpose-built tables and migrations instead of forcing everything into post meta.

## Main stack

- PHP 8.4 local Docker runtime
- WordPress 7.1.x through Roots/Bedrock
- Roots Acorn 6.x
- WooCommerce 11.x
- MariaDB 11.4
- Redis
- WP-CLI
- Mailpit
- phpMyAdmin
- Node 22 optional tooling profile
- Polylang
- UpdraftPlus
- WP BB Platform
- WP BBTheme
- WP BBuilder
- WP Theme Woo Support
- WP Payment Hub
- Home & Garden WooCommerce child theme

## Repository structure

```text
wp-bb-platform-starter/
|-- bin/                       install, WP-CLI, Acorn, deploy and release helpers
|-- config/                    Bedrock/environment configuration
|-- docker/                    PHP/Apache container setup
|-- docs/                      installation, architecture and deployment guides
|-- packages/                  project-owned Composer packages
|   |-- wp-bb-platform/
|   |-- wp-bbtheme/
|   |-- wp-bbuilder/
|   |-- wp-bbtheme-child-woo-laravel-shop/
|   |-- tfa-payment-hub/
|   `-- child-themes/
|-- web/
|   |-- wp/                    WordPress Core installed by Composer
|   `-- app/                   runtime plugins/themes/uploads
|-- composer.json
|-- docker-compose.yml
|-- docker-compose.proxy.yml
|-- setup-wsl.ps1
`-- README.md
```

Project-owned source belongs under `packages/`. Composer links or installs those packages into `web/app/`.

## Package responsibilities

### WP BB Platform

Shared application/platform functionality such as health checks, operations, CLI commands, environment validation, cache helpers and future domain services.

### WP BBTheme

Reusable parent presentation layer, standard WordPress templates, shared design foundation and optional Blade views.

### WP BBuilder

Reusable Gutenberg/ACF block framework. New blocks can render with Blade when Acorn is available while keeping PHP fallbacks where needed.

### Home & Garden child theme

`packages/wp-bbtheme-child-woo-laravel-shop/`

WooCommerce storefront presentation and integrations for the WP BB Home & Garden shop. Its documentation is intentionally separate from this root README.

The October 2026 v4.0.14 storefront fixes belong to the **child theme itself**. They are not a separate `wpbb-v414-fixes` plugin dependency.

### WP Payment Hub

WooCommerce payment integration package used by the platform.

## Local development

Recommended Windows setup:

1. Windows 11
2. WSL2 with Ubuntu
3. Docker Desktop with WSL integration
4. Git

For best performance, keep the project inside the WSL Linux filesystem, for example:

```bash
~/projects/wp-bb-platform
```

### Windows / WSL quick start

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\setup-wsl.ps1
```

### Linux / WSL quick start

```bash
chmod +x bin/*
./bin/install
```

The installer prepares `.env`, generates secrets, builds and starts Docker services, installs Composer dependencies, installs/configures WordPress when needed, activates managed plugins and the Home & Garden child theme, configures permalinks and enables Redis.

## Default local services

```text
Website:      http://localhost:8080
WP Admin:     http://localhost:8080/wp/wp-admin/
Mailpit:      http://localhost:8025
phpMyAdmin:   http://localhost:8081
```

With the shared reverse proxy, the project can use hostnames such as:

```text
wpbb.localhost
db.wpbb.localhost
```

## Useful commands

```bash
bin/wp core version
bin/wp plugin list
bin/wp theme list
bin/wp cache flush
bin/wp rewrite flush
bin/wp bb health
bin/wp bb operations
bin/artisan about
bin/artisan migrate:status
bin/deploy-check
bin/build-release
```

## WooCommerce architecture

WooCommerce owns mature commerce concerns:

- products
- customers
- cart/checkout
- orders
- payments
- coupons
- tax
- commerce extensions

Larger operational systems can extend WooCommerce through dedicated domain modules and custom tables rather than replacing it.

## Large-data architecture

Use WordPress/Gutenberg/ACF for editorial content. Use custom tables and Acorn migrations for transactional data such as:

- warehouse inventory
- stock movements
- suppliers
- purchase orders
- bookings
- ERP/CRM/PIM mappings
- large operational reports

Example table direction:

```text
wp_bb_warehouses
wp_bb_inventory
wp_bb_inventory_movements
wp_bb_suppliers
wp_bb_purchase_orders
wp_bb_purchase_order_items
wp_bb_batches
```

## Gutenberg + ACF + Blade

The platform is designed so these tools complement each other:

```text
Gutenberg / ACF
      |
   block data
      |
controller/service logic
      |
 Blade preferred
      |
 PHP fallback
```

This allows gradual migration instead of requiring a full theme rewrite.

## ACF Pro

ACF Pro is commercial software and is not redistributed publicly.

Place a licensed ZIP at:

```text
packages/private/advanced-custom-fields-pro.zip
```

Then run:

```bash
bin/install-acf-pro packages/private/advanced-custom-fields-pro.zip
```

## Production deployment

Docker is primarily for local development. Production can run the built Bedrock application directly on Linux/cPanel or similar hosting.

Typical flow:

```text
WSL2 / Docker development
        |
       Git
        |
deploy-check
        |
build-release
        |
production release package
        |
Linux / cPanel
```

Before deployment:

```bash
bin/deploy-check
bin/build-release
```

See `docs/05-cpanel-deployment.md` for deployment details.

## Performance direction

For larger WooCommerce/data projects:

```text
CDN / reverse proxy / page cache
              |
         WordPress/PHP
              |
       Redis object cache
              |
           MariaDB
              |
      indexed custom tables
```

Long-running imports, feeds, email batches and integrations should be moved away from normal page requests and handled by jobs, queues or scheduled tasks.

## Security rules

- Keep secrets in environment configuration, not Git.
- Keep project dependencies reproducible through Composer where practical.
- Disable arbitrary browser-based code editing on production.
- Do not expose unrestricted shell access in WP Admin.
- Use capability checks for admin operations.
- Test migrations and upgrades on staging.
- Back up files and database before deployment.

## Documentation

```text
docs/01-wsl-docker-install.md
docs/02-architecture.md
docs/03-blade-gutenberg-acf.md
docs/04-large-data-systems.md
docs/05-cpanel-deployment.md
docs/06-operations-and-cli.md
docs/07-sectors.md
docs/08-acf-pro.md
```

## Long-term direction

WP BB Platform is intended to be a reusable agency/development foundation rather than a single website theme.

```text
Simple website
  -> WP BB Platform

WooCommerce project
  -> WP BB Platform + WooCommerce + Woo Support

Sector website
  -> WP BB Platform + BBTheme + sector child theme

Large custom business system
  -> WP BB Platform + Acorn + custom tables + domain modules

External/mobile application
  -> REST/custom API + shared business services
```
