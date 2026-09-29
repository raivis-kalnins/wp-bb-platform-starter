# WP BB Platform Starter v0.1.0

A reusable large-project WordPress foundation for Windows 11 + WSL2 development and Linux/cPanel production.

## What is included

- Bedrock-style separated WordPress core and environment configuration.
- WordPress 7.1.2 target.
- Acorn 6.x / Laravel 13 components for Blade, migrations, Eloquent, queues and `wp acorn` commands.
- WooCommerce 11.1.2.
- Redis object-cache plugin and local Redis service.
- WP-CLI inside the Docker PHP container.
- WP BB Platform MU plugin with System Health, safe Operations UI and `wp bb` CLI commands.
- Your WP BBTheme parent, extended with optional Acorn/Blade views while keeping existing WordPress templates working.
- Your WP BBuilder, extended so ACF Hero/Gallery blocks prefer Blade views and fall back to their existing PHP renderers.
- Your WP Theme Woo Support plugin.
- All supplied sector child-theme ZIPs under `packages/child-themes/`.
- MariaDB 11.4, Redis, Mailpit and phpMyAdmin for local development.
- WSL2 helper, sector installer, ACF Pro installer, deploy checks and production release builder.

## Important design rule

Presentation lives in the parent/child themes. Reusable business functionality belongs in plugins/modules. Large transactional datasets should use purpose-built tables and migrations instead of forcing everything into `wp_posts`/`wp_postmeta` or ACF.

## Fast local installation

Recommended project location inside Ubuntu/WSL:

```bash
~/projects/wp-bb-platform
```

Then:

```bash
cp .env.example .env
./bin/install
```

The installer builds the container, installs Composer dependencies, installs WordPress, activates WooCommerce, Redis, BBuilder and Woo Support, and activates the BBTheme parent.

Open:

```text
Site:       http://localhost:8080
WP Admin:   http://localhost:8080/wp/wp-admin/
Mailpit:    http://localhost:8025
phpMyAdmin: http://localhost:8081
```

## Windows 11 helper

If you extracted the project on Windows, from PowerShell run:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\setup-wsl.ps1
```

The helper copies the project into `~/projects/wp-bb-platform` inside WSL before installation. Development directly under `/mnt/c/...` is discouraged for performance-sensitive Docker projects.

## WP-CLI and Acorn

```bash
bin/wp plugin list
bin/wp theme list
bin/wp bb health
bin/wp bb operations
bin/artisan about
bin/artisan migrate:status
```

The WP Admin also contains **BB Platform** with health and allow-listed operations. Shell execution from wp-admin is disabled by default. Set `WPBB_ALLOW_ADMIN_CLI=true` only in a trusted environment when you explicitly need it.

## Sector themes

List packaged sectors:

```bash
bin/install-sector
```

Install one:

```bash
bin/install-sector medicine
bin/install-sector logistics
bin/install-sector woo-tech-shop
```

Child themes stay compatible with the normal WordPress parent-theme model. New child-theme presentation can optionally override Blade views by adding `resources/views/...`.

## ACF Pro

ACF Pro is commercial software and is intentionally not included in this distribution. Install your licensed ZIP with:

```bash
bin/install-acf-pro /path/to/advanced-custom-fields-pro.zip
```

BBuilder continues to run without ACF; ACF-specific blocks/features become available when a compatible ACF installation is active.

## Blade example

The parent theme adds:

```text
resources/views/blocks/hero.blade.php
resources/views/blocks/gallery.blade.php
resources/views/pages/blade-page.blade.php
```

BBuilder ACF Hero/Gallery blocks prefer the Blade view when Acorn is running. If Blade is unavailable, the existing PHP renderer is used. This allows gradual migration instead of breaking existing sites.

## Build a production release

After testing locally:

```bash
bin/deploy-check
bin/build-release
```

The release ZIP is written to `releases/`.

See `docs/05-cpanel-deployment.md` for the cPanel/Hosting.com deployment layout.

## Profiles planned after v0.1

The foundation is ready for separate domain modules such as:

- `wp-bb-warehouse`
- `wp-bb-medicine`
- `wp-bb-pharmacy`
- `wp-bb-jobs`
- `wp-bb-booking`
- ERP/CRM/PIM integrations

Those should use migrations + custom tables/Eloquent where data volume or integrity requires it, while Gutenberg/ACF remains ideal for editorial content.
