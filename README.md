# WP BB Platform Starter v0.1.0

A reusable large-project WordPress foundation for **Windows 11 + WSL2 + Docker development** and **Linux/cPanel production**.

WP BB Platform keeps the WordPress ecosystem, Gutenberg, ACF and WooCommerce, but adds a cleaner application structure inspired by Roots/Bedrock/Sage and Laravel. The goal is to support both normal WordPress sites and much larger systems such as e-commerce, warehouse/inventory, medicine/pharmacy, booking, directories, ERP/CRM/PIM integrations and custom database applications.

> **Core idea:** keep WordPress where WordPress is strong, use Laravel/Acorn patterns where larger applications need stronger structure, and keep presentation separate from reusable business logic.

---

## Platform goals

The starter is designed around a few rules:

- WordPress Core is treated as a managed dependency rather than project source code.
- Composer manages WordPress, plugins and application packages.
- Bedrock-style configuration separates development, staging and production environments.
- Acorn brings Laravel-style Blade views, migrations, Eloquent, queues, services and console commands into WordPress.
- Gutenberg remains the primary content editor.
- ACF is supported for structured editorial fields and custom blocks.
- WooCommerce remains the commerce engine instead of rebuilding checkout, orders, tax, payments and the plugin ecosystem.
- BBTheme is the reusable parent presentation layer; sector child themes customize design without owning core business functionality.
- BBuilder provides the reusable Gutenberg/ACF block layer.
- Large transactional systems use purpose-built database tables, migrations and services instead of forcing everything into `wp_posts`, `wp_postmeta` or ACF.
- WP Admin operations expose safe, allow-listed maintenance actions instead of an unrestricted browser shell.

---

## Architecture overview

The platform combines three strengths: the WordPress ecosystem, Laravel/Acorn application tooling and Gutenberg-based editing.

```text
                         WP BB PLATFORM
                               |
              +----------------+----------------+
              |                |                |
         WordPress          Laravel         Gutenberg
          ecosystem          tools           editor
              |                |                |
           Plugins            Acorn          BBuilder
              |                |                |
         WooCommerce        Eloquent           ACF
              |            migrations           |
              +----------------+----------------+
                               |
                        BBTheme / Blade
                               |
                       Sector Child Themes
```

### What each layer does

| Layer | Responsibility |
|---|---|
| **Bedrock** | Separates WordPress Core, application files and environment configuration; manages dependencies through Composer. |
| **WordPress** | CMS, users, media, menus, REST API, plugin ecosystem and standard WordPress compatibility. |
| **Gutenberg** | Primary block/content editing experience for clients and editors. |
| **ACF** | Structured editorial fields and selected custom block workflows. |
| **Acorn** | Laravel-style container, Blade, services, migrations, Eloquent, queues and console commands inside WordPress. |
| **BB Platform** | System Health, safe Operations UI, common application services and `wp bb` CLI commands. |
| **BBuilder** | Gutenberg/ACF block framework and reusable page-building components. |
| **BBTheme** | Parent theme, shared presentation, optional Blade views and reusable design foundation. |
| **Sector child themes** | Sector-specific branding, templates, patterns and presentation overrides. |
| **Woo Support** | Project-specific WooCommerce compatibility/integration layer. |
| **Domain modules** | Warehouse, medicine, pharmacy, jobs, booking, ERP/CRM/PIM and other reusable business systems. |

---

## Runtime and Docker / WSL2 structure

Local development runs inside Docker from WSL2. PHP/Apache hosts the Bedrock application, while database, object cache, mail testing and administration tools run as separate services.

```text
Docker / WSL2
     |
PHP 8.4 + Apache
     |
Bedrock
     |
WordPress
     |
Acorn
     |
BB Platform
     |
+--------------------------+
| MariaDB 11.4             |
| Redis                    |
| Mailpit                  |
| WP-CLI                   |
| Composer                 |
| Node 22 tools profile    |
| phpMyAdmin               |
+--------------------------+
```

The main Docker services are:

| Service | Purpose |
|---|---|
| `app` | PHP 8.4 + Apache, Composer, WP-CLI and the WordPress application |
| `db` | MariaDB 11.4 |
| `redis` | Redis object cache / application cache service |
| `mailpit` | Local email capture and testing |
| `phpmyadmin` | Local database administration |
| `node` | Optional Node 22 tooling profile for theme/frontend work |

For best WSL2 performance, keep active projects in the Linux filesystem, for example:

```bash
~/projects/wp-bb-platform
```

Avoid running a large Docker WordPress project directly from `/mnt/c/...` unless there is a specific reason to do so.

---

## Repository structure

The starter repository is the orchestration/application layer.

```text
wp-bb-platform-starter/
|-- bin/
|   |-- install             # Full local installer
|   |-- wp                  # WP-CLI wrapper inside Docker
|   |-- artisan             # Acorn/Laravel console wrapper
|   |-- composer            # Composer wrapper
|   |-- theme               # Theme tooling helper
|   |-- install-sector      # Install/activate packaged sector child theme
|   |-- install-acf-pro     # Install a licensed ACF Pro ZIP
|   |-- deploy-check        # Pre-deployment validation
|   `-- build-release       # Build production release ZIP
|
|-- config/
|   `-- environments/       # Bedrock environment configuration
|
|-- docker/
|   |-- Dockerfile          # PHP 8.4 Apache + WP-CLI + Composer
|   `-- apache/             # Apache virtual-host configuration
|
|-- docs/
|   |-- 01-wsl-docker-install.md
|   |-- 02-architecture.md
|   |-- 03-blade-gutenberg-acf.md
|   |-- 04-large-data-systems.md
|   |-- 05-cpanel-deployment.md
|   |-- 06-operations-and-cli.md
|   |-- 07-sectors.md
|   `-- 08-acf-pro.md
|
|-- web/
|   |-- wp/                 # WordPress Core installed by Composer
|   `-- app/
|       |-- mu-plugins/     # Platform/application bootstrap packages
|       |-- plugins/        # WordPress plugins
|       |-- themes/         # BBTheme + child themes
|       `-- uploads/        # Runtime uploads
|
|-- composer.json
|-- docker-compose.yml
|-- docker-compose.proxy.yml
|-- setup-wsl.ps1
`-- README.md
```

The complete distribution can additionally provide local project packages such as:

```text
packages/
|-- wp-bb-platform/
|-- wp-bbtheme/
|-- wp-bbtheme-child-woo-laravel-shop/
|-- wp-bbuilder/
|-- wp-theme-woo-support/
|-- wp-payment-hub/
`-- child-themes/
```

These sources are committed to the main repository and connected to `web/app/` through Composer path packages. The installed `web/app/plugins`, `web/app/mu-plugins` and `web/app/themes` directories remain ignored runtime output, so a clean clone has one authoritative copy of every project-owned theme and plugin. Public third-party plugins are pinned in `composer.lock`; private/commercial packages are supplied separately without committing licensed code.

---

## Core package responsibilities

### WP BB Platform

The platform package is responsible for shared application concerns that should not belong to a theme:

```text
BB Platform
|-- System Health
|-- Operations
|-- CLI commands
|-- environment checks
|-- cache / cron helpers
|-- deployment checks
|-- shared permissions
`-- future application services
```

Business functionality that must survive a theme change should also live in a plugin/domain module rather than a child theme.

### WP BBTheme

`wp-bbtheme` is the reusable parent presentation layer. It keeps normal WordPress theme compatibility while allowing gradual adoption of Acorn/Blade views.

Typical responsibility:

```text
wp-bbtheme/
|-- normal WordPress templates
|-- resources/views/
|   |-- layouts/
|   |-- components/
|   |-- blocks/
|   `-- pages/
|-- theme.json
|-- shared assets
`-- reusable presentation helpers
```

New code can use Blade without forcing old templates to be rewritten immediately.

### WP BBuilder

BBuilder is the structured page-building layer around Gutenberg/ACF.

A preferred rendering flow is:

```text
Gutenberg / ACF editor
          |
       block data
          |
  block/controller logic
          |
      Blade view
          |
       frontend
```

Current PHP renderers can remain as fallbacks while blocks are migrated to Blade incrementally.

### Sector child themes

Sector children remain standard WordPress child themes:

```text
wp-bbtheme
    |
    +-- medicine
    +-- logistics
    +-- real-estate
    +-- hotel
    +-- business
    +-- woo-tech-shop
    `-- other sectors
```

They should primarily contain presentation: branding and design tokens, sector styling, Blade/PHP template overrides, patterns and demo/presentation configuration. Long-lived business logic should move into dedicated modules such as `wp-bb-medicine`, `wp-bb-pharmacy` or `wp-bb-warehouse`.

---

## Data architecture for large systems

Normal editorial content can continue to use WordPress posts, taxonomies, Gutenberg and ACF. For larger transactional systems, use dedicated tables and migrations.

```text
Editorial content
-----------------
Pages
Posts
News
Marketing blocks
Simple directories
        |
WordPress + Gutenberg + ACF


Business / transactional data
-----------------------------
Warehouse stock
Stock movements
Suppliers
Purchase orders
Pharmacy inventory
Bookings
ERP mappings
Large reports
        |
Acorn migrations
        |
Custom database tables
        |
Eloquent / service layer
        |
WP Admin / REST API / CLI
```

Example warehouse structure:

```text
wp_bb_warehouses
wp_bb_inventory
wp_bb_inventory_movements
wp_bb_suppliers
wp_bb_purchase_orders
wp_bb_purchase_order_items
wp_bb_batches
```

This keeps WordPress useful as the CMS/admin shell without turning `wp_postmeta` into the database for every business problem.

---

## WooCommerce architecture

WooCommerce remains responsible for mature commerce functionality:

```text
WooCommerce
|-- products
|-- customers
|-- orders
|-- checkout
|-- payments
|-- tax
|-- coupons
`-- commerce extensions
```

Large business modules can extend it without replacing it:

```text
BB Warehouse
|-- warehouses
|-- inventory ledger
|-- suppliers
|-- purchase orders
|-- batches / serials
|-- reservations
|-- reorder rules
`-- ERP mappings
```

The two layers communicate through services, WooCommerce APIs/hooks, background jobs and custom tables where appropriate.

---

## Fast local installation

### Windows 11 requirements

Recommended prerequisites are Windows 11, WSL2 with Ubuntu, Docker Desktop with WSL integration and Git.

If the repository was extracted or cloned on Windows, PowerShell can copy/setup the project inside WSL:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\setup-wsl.ps1
```

### Install inside WSL

```bash
cd ~/projects/wp-bb-platform
./bin/install
```

The installer validates Docker/Compose, creates `.env` when needed, generates database passwords and WordPress salts, builds the PHP container, starts MariaDB/Redis/Mailpit/phpMyAdmin, runs Composer as the host user, creates the writable WordPress/Acorn directories, installs WordPress through WP-CLI, activates the managed plugins and the Home & Garden child theme, and configures permalinks and Redis.

Project-owned theme/plugin source belongs under `packages/`, not the ignored `web/app/` install tree. Composer currently installs the parent theme, Home & Garden child theme, BBuilder, Woo Support and WP Payment Hub from those tracked source packages. WooCommerce, Redis Object Cache, Polylang, ACF Options for Polylang and UpdraftPlus are reproducible public Composer dependencies.

---

## Local URLs

With the default Compose configuration:

| Service | URL |
|---|---|
| Website | `http://localhost:8080` |
| WP Admin | `http://localhost:8080/wp/wp-admin/` |
| Mailpit | `http://localhost:8025` |
| phpMyAdmin | `http://localhost:8081` |

The optional `docker-compose.proxy.yml` connects the app to the shared `rproxy_reverse-proxy` network and exposes virtual hosts such as:

```text
wpbb.localhost
db.wpbb.localhost
```

If the shared proxy is configured for local TLS, the same architecture can use project-style HTTPS hostnames, for example:

```text
https://project.localhost
https://db.project.localhost
https://mailpit.localhost
```

The exact hostnames are controlled by environment/proxy configuration rather than hard-coded into WordPress.

---

## WP-CLI, Acorn and the Operations service

The platform intentionally gives both developers and administrators access to the same safe operation layer.

```text
                    Operation Service
                      /           \
                     /             \
              WP Admin UI         WP-CLI
                                      |
                               wp bb health
                               wp bb operations
                               wp bb migrate
                               wp bb cache
                               wp bb deploy-check
```

Useful commands include:

```bash
bin/wp core version
bin/wp plugin list
bin/wp theme list
bin/wp bb health
bin/wp bb operations

bin/artisan about
bin/artisan migrate:status
```

The WP Admin contains a **BB Platform** area for system health and allow-listed maintenance operations. An unrestricted operating-system shell is intentionally not exposed in WP Admin. Browser-triggered CLI execution should remain allow-listed. `WPBB_ALLOW_ADMIN_CLI=true` should only be enabled in a trusted environment when explicitly required.

---

## Gutenberg + ACF + Blade

The project does not require choosing between Gutenberg, ACF and PHP/Blade templates. Each has a different job.

```text
Gutenberg
   |
   +-- native blocks
   +-- BBuilder blocks
   `-- ACF blocks
            |
         data layer
            |
      Blade preferred
            |
      PHP fallback
```

The BBTheme parent supports Blade examples such as:

```text
resources/views/blocks/hero.blade.php
resources/views/blocks/gallery.blade.php
resources/views/pages/blade-page.blade.php
```

BBuilder ACF Hero/Gallery blocks can prefer Blade when Acorn is available and fall back to the existing PHP renderer when it is not. This allows gradual migration rather than a disruptive theme rewrite.

---

## ACF Pro

ACF Pro is commercial software and is intentionally not redistributed by this starter. Install your licensed ZIP with:

```bash
bin/install-acf-pro /path/to/advanced-custom-fields-pro.zip
```

For an automatic clean install, place the licensed file at `packages/private/advanced-custom-fields-pro.zip` before running `bin/install`. The private package directory is excluded from Git.

The platform and BBuilder continue to run without ACF Pro; ACF-specific features are enabled when a compatible licensed installation is present.

---

## Sector themes

List packaged sectors:

```bash
bin/install-sector
```

Install/activate a sector child theme:

```bash
bin/install-sector medicine
bin/install-sector logistics
bin/install-sector woo-tech-shop
```

The normal WordPress parent/child-theme model remains intact. A child can override normal templates and, where configured, add/override Blade views under `resources/views/`.

---

## Development workflow

```text
Windows 11
    |
WSL2 / Ubuntu
    |
Docker local project
    |
Git / feature branches
    |
Gutenberg + theme/plugin development
    |
WP-CLI / Acorn migrations / tests
    |
deploy-check
    |
production release build
    |
Linux / cPanel / reseller hosting
```

Use source control for code. Runtime uploads and environment secrets should not be treated as source code.

---

## Production release and cPanel / reseller deployment

Before building a production package:

```bash
bin/deploy-check
bin/build-release
```

The release ZIP is written to `releases/`.

The production server does **not** need Docker. Docker is the development/runtime standard for local work; cPanel/Hosting.com production can run the built Bedrock application directly with PHP, MariaDB, Composer/WP-CLI deployment tooling and the correct public document root.

Recommended deployment concept:

```text
Local WSL2 / Docker
        |
       Git
        |
production build
        |
release package
        |
cPanel account
        |
Bedrock application
        |
web/  <-- public document root
```

See `docs/05-cpanel-deployment.md` for the server layout and Hosting.com/cPanel notes.

---

## Performance direction

For large WooCommerce and data-heavy systems, the intended architecture is:

```text
CDN / LiteSpeed / reverse proxy
             |
         page cache
             |
      WordPress / PHP
             |
      Redis object cache
             |
          MariaDB
             |
     indexed custom tables
```

Heavy tasks such as imports, warehouse synchronization, reports, feeds, email batches and ERP/CRM synchronization should be moved away from normal page requests and handled through background jobs, queues or scheduled tasks.

---

## Security and production rules

For production projects, keep secrets in environment configuration rather than Git, keep dependencies version-controlled through Composer where practical, disable arbitrary theme/plugin file editing, never expose an unrestricted shell from WP Admin, use capability checks for administration operations, test migrations on staging, and back up files/database before deployment.

Medical, pharmacy and other regulated data should be treated separately from normal CMS content. Use purpose-built secure systems and appropriate compliance controls when legal or clinical requirements demand them.

---

## Planned domain modules

The platform foundation is intended to support separate reusable modules such as:

```text
wp-bb-warehouse
wp-bb-medicine
wp-bb-pharmacy
wp-bb-jobs
wp-bb-booking
wp-bb-erp
wp-bb-crm
wp-bb-pim
```

These modules should own their data model, migrations, permissions, services, APIs and admin screens. Sector themes should remain presentation-focused so a design change cannot remove business data or functionality.

---

## Documentation

| Document | Purpose |
|---|---|
| `docs/01-wsl-docker-install.md` | Windows 11 / WSL2 / Docker setup |
| `docs/02-architecture.md` | Platform architecture |
| `docs/03-blade-gutenberg-acf.md` | Blade, Gutenberg and ACF integration |
| `docs/04-large-data-systems.md` | Custom tables and large business systems |
| `docs/05-cpanel-deployment.md` | cPanel / Hosting.com production deployment |
| `docs/06-operations-and-cli.md` | BB Platform operations and CLI |
| `docs/07-sectors.md` | Sector child themes |
| `docs/08-acf-pro.md` | Licensed ACF Pro installation |

---

## Current version targets

```text
PHP              8.4 local Docker image
WordPress        7.1.2
Acorn            6.3+
WooCommerce      11.1.2
MariaDB          11.4
Redis            7
Node             22 (tools profile)
WP-CLI           2.12.0
```

Version pins will evolve. Test upgrades locally and on staging before production.

---

## Long-term direction

WP BB Platform is intended to become a reusable agency/development foundation rather than a single website theme.

```text
Simple WordPress site
        |
WP BB Platform

Large WooCommerce site
        |
WP BB Platform + WooCommerce + Woo Support

Sector website
        |
WP BB Platform + BBTheme + sector child theme

Large custom business system
        |
WP BB Platform + Acorn + custom tables + domain modules

Mobile / external application
        |
WP REST/custom API + shared business services
```

The long-term goal is to keep the editing experience familiar to WordPress users while giving developers a stronger structure for large, maintainable systems.
