# WP Theme Woo Support 3.4.0

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
