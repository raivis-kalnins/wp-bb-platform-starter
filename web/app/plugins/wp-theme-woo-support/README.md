# WP Theme Woo Support 3.8.0

Reusable WooCommerce functionality for the WP BBTheme suite. Ecommerce behaviour is kept in this companion plugin so sector child themes can share robust catalogue, product, variation, cart/account and quote workflows without duplicating business logic.

## Profiles

- **Portable** — safe default; declares Woo support without changing storefront output.
- **Store (recommended)** — enables the modern catalogue, filters, product UX, variations/swatches, mini-cart, demo importer and **Cart + Quote** workflow while avoiding the most invasive legacy overrides.
- **Legacy** — preserves older IWS/WP BBTheme Woo behaviour, including legacy checkout/template tools, while also supporting the new quote basket.

Configure profiles and individual modules under **WooCommerce → Settings → Theme Support**.

## Cart + Quote workflow (3.4)

The `quote_request` feature is enabled by default for Store and Legacy profiles and is designed to coexist with normal WooCommerce purchasing.

- Keeps the standard **Add to Cart** button available.
- Adds a separate **Add to Quote** button on single products.
- Adds quote actions on product loops; variable/grouped products route visitors to the product page to choose options first.
- Supports **simple, variable and grouped products**, selected attributes, quantities, existing IWS single-product custom fields and common nested add-on/composite option fields.
- Stores the quote list in the WooCommerce customer session, separately from the cart.
- Automatically creates a **Request a Quote** page using `[wp_theme_woo_quote_basket]`.
- Allows visitors to update quantities or remove products before submitting.
- Stores submitted requests in **WooCommerce → Quote Requests** and emails the site administrator.
- Includes name/email/company/phone/message fields and Privacy Policy / Terms & Conditions consent links.
- Continues to support the existing product-level **disable normal add to cart** setting for quote-only products.

The variable-product implementation reads the chosen WooCommerce variation ID and submitted `attribute_*` values, validates the selected variation where WooCommerce can resolve it, and preserves common add-on/composite configuration fields. Quote therefore does not collapse a complex configured product into a generic parent-product request.

## Store functionality

- AJAX product filtering with search, category, brand, price/dimension/variation attributes, stock, sale status, minimum rating, sorting, active filters, URL history and load more.
- Variation swatches, AJAX search, mini-cart, product gallery, stock/discount helpers and structured data.
- Quantity controls and live product total presentation on supported single-product layouts.
- Sector-aware demo products and modern Woo Cart/Checkout block page setup.
- HPOS and Cart/Checkout block compatibility declarations.

## Menu integration

Store demo imports create a real WooCommerce Shop page menu item and product-category children where appropriate, plus account/cart utility links. Quote requests remain a separate customer journey and do not modify cart contents.

## Compatibility

Requires WooCommerce for store features. Targets WordPress 6.6+ and PHP 8.0+. HPOS and Cart/Checkout block compatibility are declared by the plugin.


## 3.5.0 archive integration

The smart product filter and comparison engine can now be embedded by custom child themes without using the legacy IWS shortcode names directly:

- `wp_theme_woo_support_filter_markup()`
- `wp_theme_woo_support_filter_results_markup()`
- `wp_theme_woo_support_compare_button()`
- `wp_theme_woo_support_filter_product_item_html` filter for custom product-card rendering

Store profile defaults to 24 products per request. On catalogues above 10,000 published products the expensive dynamic attribute-availability scan is skipped automatically; the cached filter options remain available. Parent-product SKU search uses WooCommerce's `wc_product_meta_lookup` table.


## 3.6.0 Marketing Intelligence

- Adds **WooCommerce → Marketing Intelligence**.
- Tracks first/last-touch UTM parameters and stores attribution on WooCommerce orders.
- Adds revenue, order, AOV, customer, refund and period-over-period KPI cards.
- Adds Google Charts for revenue trends, orders, source mix and top-product revenue.
- Adds campaign/source performance tables and a tracked campaign URL builder.
- Adds automatic recommendations for revenue decline/growth, attribution coverage, AOV and refund rate.
- Works inside `wp-theme-woo-support`; no FluentCRM dependency is required.

## 3.7.0 Marketing Intelligence expansion

The Marketing Intelligence screen now adds deeper CRM-style commerce insight while remaining fully inside `wp-theme-woo-support`:

- Aggregate UTM/ad-click visit tracking with no stored visitor IP address.
- Consent-aware tracking when the WordPress Consent API is available, plus basic bot filtering for campaign visits.
- Campaign visit-to-order conversion, optional manual campaign spend, ROAS and CPA.
- First-touch versus last-touch revenue attribution.
- Source momentum versus the previous comparison period.
- New versus returning order mix using WooCommerce Analytics lookup data when available.
- Customer lifecycle cards for one-time, repeat, loyal, VIP, active, at-risk and lapsed customers.
- Top customers for the selected period plus top lifetime customers.
- Product momentum showing the largest revenue increases and declines.
- Frequently-bought-together product affinity for bundle and cross-sell ideas.
- Coupon performance and marketing-medium performance tables.
- Expanded automatic recommendations covering retention, reactivation, campaign efficiency, product momentum and product affinity.
- Campaign URL builder now supports source, medium, campaign, content and term.
- Order processing is paged in batches instead of loading an unlimited order result in one query.


## 3.8.0 commerce operations suite

- B2B module now loads with the Store profile while remaining internally disabled until configured. It adds product/variation trade prices, minimum quantities, case packs, client discount/credit/payment-term fields and a B2B Demo Lab that can create disposable test clients and a portal page.
- Demo importer prefers bundled photographic media and only falls back to generated SVG artwork when the demo-media assets are missing.
- Local CRM Intelligence adds customer segmentation, repeat-rate/AOV metrics, predicted reorder dates, cohort/revenue charts, action recommendations and CSV segment export without depending on FluentCRM.
- Product Sync provides native JSON/XML/CSV/Woo REST import profiles, SKU/external-ID upserts, image sideloading, stock/price-only fast mode, scheduled WP-Cron jobs, secure external cron URLs and CSV/JSON/XML product export.
- A live DummyJSON draft-import profile can be created from the Product Sync screen for safe testing; the public test endpoint is intended only for development/demo data.
