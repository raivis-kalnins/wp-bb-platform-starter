# WP BBTheme Child Woo Home & Garden Shop 3.1.3

Installable WooCommerce child theme for the WP BBTheme platform, aimed at large Home & Garden / DIY catalogues.

## Main changes in 3.1.3

- Fixed the fatal missing megastore include: `inc/wpbbshop-garden-megastore.php` is included in the package.
- Renamed the legacy project namespace/files/classes/JS handles to the neutral `wpbbshop` namespace and removed the old visible branding.
- Removed old branded raster logo/screenshot assets; the theme uses WordPress Custom Logo or the built-in Home & Garden demo wordmark.
- English is the primary/default language (`en`, `en_GB`); Latvian (`lv`) is second.
- Added a lightweight Polylang + WooCommerce compatibility bridge for translated Woo core pages and URLs.
- Hides only the Polylang-for-WooCommerce upsell notice when the official add-on is not installed.
- Includes 20 Home & Garden / DIY departments and a 500-product demo generator.
- Demo generator avoids creating hundreds of Media Library attachments.
- Archive pages default to 24 products and use pagination for large catalogues.
- Fast AJAX search prioritises title/SKU and supports common barcode meta fields.
- Comparison XML endpoints: `/kurpirkt.xml`, `/salidzini.xml`, `/ceno.xml`.
- Ceno is handled as a full third feed, including static-file/htaccess fallback publishing.
- Legacy feed setup now looks up only legacy demo SKUs instead of scanning the entire WooCommerce catalogue.

## Demo catalogue

WordPress Admin → Appearance → **Home & Garden Demo** → create/refresh 500 demo products.

Generated demo products use `_wpbbshop_demo_product=1` and are excluded from comparison feeds by default. Replace demo content with real catalogue data before production.

## Languages

- Main/default: English (`en`, `en_GB`)
- Second: Latvian (`lv`)

With Polylang and `hide_default` enabled, English uses the site root and Latvian uses `/lv/`.

The theme-side bridge is deliberately lightweight. For advanced multilingual product/variation/stock synchronisation, the official Polylang for WooCommerce add-on remains the safer production option.

## 40k+ product notes

For large catalogues use Redis object cache, WooCommerce HPOS for orders, real server cron, page/object caching, indexed Woo attributes/custom tables where appropriate, and generate comparison feeds outside normal frontend requests.
