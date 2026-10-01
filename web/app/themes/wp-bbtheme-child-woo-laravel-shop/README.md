# WP BBTheme Child Woo Home & Garden Shop — v4.0.14 Full Fixed Theme

`wp-bbtheme-child-woo-laravel-shop` is the Home & Garden / DIY WooCommerce child theme used by the WP BB Platform. This ZIP is the **complete child theme**, not a patch and not a separate storefront-fixes plugin.

The v4.0.14 repairs are loaded directly by the child theme from `inc/wpbbshop-v414-final-ui.php`, `assets/css/v414-final-ui.css` and `assets/js/v414-final-ui.js`.

## What this theme provides

- Bilingual Home & Garden / DIY WooCommerce storefront for Latvian and English sites.
- Large-catalogue homepage with hero, department-first navigation, project cards, featured/sale/recent products and service information.
- WooCommerce shop and product-category layouts with AJAX catalogue filtering and load-more support.
- Product search by name, SKU and catalogue data.
- Product comparison integration and reusable catalogue controls.
- Custom product, cart, checkout, mini-cart, My Account and order-tracking presentation.
- Polylang-aware language and permalink handling.
- Latvia comparison-feed support for KurPirkt, Salidzini and Ceno, plus the wider market-feed layer used by WP BB Platform.
- Redis/cache-aware compatibility layers and large-catalogue safeguards.
- Responsive desktop/tablet/mobile header, navigation, search and catalogue layouts.
- Theme-managed information pages and fallbacks for delivery/payment, returns/warranty and purchase terms.

## v4.0.14 full-theme fixes included

### Homepage spacing and alignment

The homepage no longer leaves the very large blank bands that were visible between:

1. **Departments**
2. the three project cards (**Garden & outdoor / Build & renovate / Home & workshop**)
3. **Featured products** and the following product sections.

The underlying cause was older `content-visibility:auto` / `contain-intrinsic-size` rules reserving 600–900px placeholder heights after the homepage cards had been made much more compact. v4.0.14 now forces these sections back into normal document flow in both CSS and a small runtime safeguard.

All main homepage blocks use the same `wpbb-v400-shell` width, so the department grid, project cards and product sections share one fixed left/right alignment.

### Department grid

- Dense 5-column desktop layout.
- No inherited staggered/spanning positions.
- Compact fixed-height category cards.
- Responsive 4-column and 2-column layouts at smaller widths.
- Runtime safeguard prevents older cached CSS from reintroducing gaps or staggered placement.

### Shop/category filter UI

- Home & Garden green styling for smart-filter controls.
- Clear min/max range controls and accessible handles.
- Improved **More filters** +/- affordance.
- Compare button uses a clear bidirectional compare symbol and consistent active/count states.
- Product-card compare controls match the same visual language.
- Filter/search controls remain responsive on mobile.

### Missing policy pages / 404 repair

The theme now self-heals and publishes these required pages when missing:

- `piegade-un-apmaksa` — Piegāde un apmaksa
- `delivery-payment` — Delivery & payment
- `atgriesana-un-garantija` — Atgriešana un garantija
- `returns-warranty` — Returns & warranty
- `pirksanas-noteikumi` — Pirkšanas noteikumi
- `terms-and-conditions` — Terms & conditions
- `track-your-order` — order tracking page

Latvian/English policy pages are paired with Polylang when Polylang is available. If a stale local rewrite still produces a 404, the theme redirects to the canonical permalink or renders a safe virtual fallback instead of showing the 404 screen.

### Track your order

`/track-your-order/` now uses the same centred storefront shell as the rest of the site. The form is a balanced two-column card on desktop and a clean one-column form on mobile. Old WooCommerce float/width styles are explicitly neutralised.

The old page-creation typo (`Preču atriešana`) is also repaired to **Sekot pasūtījumam**.

## Installation

This is a child theme and expects the parent theme folder to be installed as:

`wp-bbtheme`

Install the ZIP through **Appearance → Themes → Add New → Upload Theme**, or extract the folder into:

`wp-content/themes/wp-bbtheme-child-woo-laravel-shop/`

On Bedrock installations the normal location is:

`web/app/themes/wp-bbtheme-child-woo-laravel-shop/`

Activate **WP BBTheme Child Woo Home & Garden Shop** after the parent theme is available.

### Important

No separate **WP BB v4.0.14 Storefront Fixes** plugin is required. The fixes are part of this child theme.

The existing WP BB Platform/WooCommerce integrations used by the project are unchanged. In the standard platform build, `wp-theme-woo-support` supplies the shared smart-filter engine while this child theme owns the Home & Garden presentation and final v4.0.14 styling.

## Main theme files

- `functions.php` — child-theme setup, WooCommerce integrations and compatibility layers.
- `inc/wpbbshop-v400-platform.php` — v4 storefront/homepage platform layer.
- `inc/wpbbshop-v412-woo-support-integration.php` — Home & Garden integration with shared Woo support features.
- `inc/wpbbshop-v413-ui-polish.php` — preceding visual polish layer.
- `inc/wpbbshop-v414-final-ui.php` — final v4.0.14 theme-owned repairs and required-page recovery.
- `assets/css/v414-final-ui.css` — final homepage/filter/policy/tracking alignment rules.
- `assets/js/v414-final-ui.js` — department-grid and homepage-flow cache safeguard.
- `woocommerce/` — WooCommerce template overrides.
- `page-track-your-order.php` — themed order tracking page.

## Language URLs

The theme works with either language configured as the Polylang default.

Typical English-first setup:

- English: `/`
- Latvian: `/lv/`

Typical Latvian-first setup:

- Latvian: `/`
- English: `/en/`

Do not hard-code `/lv/` or `/en/` links in templates; theme helpers and Polylang should produce the active canonical URL.

## Large catalogue notes

For large WooCommerce catalogues:

- keep product/category pages server-rendered for SEO;
- use AJAX for archive filters and incremental loading rather than replacing core URLs;
- prefer WooCommerce lookup tables and indexed taxonomies for price, stock and attribute filtering;
- keep Redis available but fail-safe;
- run feeds/imports from real cron or queues where appropriate;
- for very large fuzzy-search/faceting workloads, consider OpenSearch, Elasticsearch, Meilisearch or a dedicated catalogue index instead of pushing all search work through MySQL.

## v4.0.14 verification checklist

After activating/updating the theme, clear page/cache/CDN caches and verify:

- Homepage department rows have no blank vertical bands.
- The three project cards begin directly below the department grid.
- Featured products begin directly below the project cards with a normal section gap.
- Shop/category filter range controls are visible and usable.
- Compare icon/state is consistent on the filter and product cards.
- `/lv/piegade-un-apmaksa/` resolves.
- `/atgriesana-un-garantija/` resolves or redirects to its canonical language URL.
- `/lv/pirksanas-noteikumi/` resolves.
- `/track-your-order/` is centred and aligned on desktop and mobile.

## Version

Theme version: **4.0.14**  
Build: **full fixed child-theme package**

## 4.0.14 final-3 screenshot repairs

- Uses `/terms-conditions/` as the canonical English Terms & Conditions page because that is the URL used by the storefront footer; the older `/terms-and-conditions/` URL redirects to it.
- Rebuilds the 404 presentation as a proper storefront card with responsive search and navigation actions instead of raw browser form controls.
- Reduces the desktop hero title/height so the left hero panel remains balanced with the image.
- Forces homepage department cards into a continuous flex row layout (5 / 4 / 2 columns), eliminating inherited grid placement that could create alternating blank cells.
- Removes stale intrinsic-height spacing between departments, project cards and product sections.
- Keeps the smart-filter compare icon visible with a real inline SVG and automatically turns the compare button green whenever its count is greater than zero.


## v4.0.14 final-4 storefront corrections

- Enlarges the homepage department/category cards to a readable 4-column desktop grid that uses the full storefront shell width.
- Keeps category cards consecutive with no inherited staggered/empty grid cells.
- Tightens alignment between hero, departments, project cards and product sections.
- Fixes the Woo Support compare search button after selection by overriding its conflicting inline `!important` background state from the theme JavaScript layer.
- Keeps the compare icon visible: green on white when empty, white on green when one or more products are selected.
