## v4.0.26 realistic product and blog imagery

- Replaces the v4.0.25 generated showcase artwork with distinct real photographs for every bundled showcase product.
- Gives every bundled Product Guide its own editorial photograph instead of reusing the product-card art.
- Uses a clean white retail-product media surface closer to large Home & Garden stores: neutral background, contained product photo, no fake green illustration panel/glow.
- Keeps Product Guide cards photo-led with consistent crops and higher-quality editorial presentation.
- Uses remote Wikimedia Commons source URLs immediately, then caches two images per wp-admin request into the local WordPress Media Library to avoid long blocking imports.
- Keeps real WooCommerce product images untouched; the mapping only applies to bundled demo/showcase content.
- Image source/licence information is documented in `IMAGE-CREDITS.md`.


## v4.0.19 final repairs

- Cart and checkout delivery methods now structurally span the full order-summary width; no empty table column remains beside Royal Mail, Evri, DPD or Click & collect.
- Homepage department title text is larger at 17px / 500 weight; product counts are 13px on desktop.
- Repairs Polylang language/translation relationships for the Latvian and English delivery, returns and terms page pairs.
- Old root-level Latvian policy URLs redirect to their canonical `/lv/` pages instead of falling into the English default language.
- Latvian homepage hero gets additional safe height and adjusted typography so the longer headline, buttons and trust chips do not overlap.
- Retains the Git-master R5 four-column homepage department grid and all v4.0.18 fixes.

# WP BBTheme Child Woo Home & Garden Shop — v4.0.16 Full Fixed Theme

`wp-bbtheme-child-woo-laravel-shop` is the Home & Garden / DIY WooCommerce child theme used by the WP BB Platform. This ZIP is the **complete child theme**, not a patch and not a separate storefront-fixes plugin.

The current repairs are loaded directly by the child theme. v4.0.16 adds `inc/wpbbshop-v416-final-fixes.php`, `assets/css/v416-final-fixes.css` and `assets/js/v416-final-fixes.js` on top of the earlier integrated storefront layers.

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

- Exact 4-column desktop layout with compact, readable cards.
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

No separate storefront-fixes plugin is required. All repairs through v4.0.16 are part of this child theme.

The existing WP BB Platform/WooCommerce integrations used by the project are unchanged. In the standard platform build, `wp-theme-woo-support` supplies the shared smart-filter engine while this child theme owns the Home & Garden presentation and final child-theme styling.

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


## R5 homepage category grid

- Desktop homepage departments are forced to exactly four equal-width columns.
- All 20 categories fill a compact 4 x 5 grid with no unused right-side column.
- Category cards are shorter and denser while keeping names and product counts readable.
- A separate cache-busting theme asset layer prevents older v4.0.14 CSS/JS from restoring the 3-column layout.

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

## 4.0.14 full-fixed R4

- Homepage departments are four equal, full-width columns on desktop with larger readable cards.
- UK market storefront identity is market-aware: `40 Brook Street, Northampton, NN1 2PE` is shown when the resolved WooCommerce market is GB.
- UK header/footer/home labels use Northampton / United Kingdom rather than Riga / Latvia where those labels describe the active storefront.


## 4.0.16 cart / checkout / home-grid final fixes

- Cart and checkout shipping choices now expand across the complete order-summary width instead of leaving an empty right-hand column.
- Replaces the v4.0.15 broad checkout MutationObserver with WooCommerce update-event hooks, preventing repeated DOM rewrites and reducing cart/checkout loading overhead.
- UK checkout skips Latvia parcel-locker data/API work and renders the delivery information labels in English at source.
- Reasserts the homepage department list as an exact four-column desktop grid with compact, readable cards and no missing fourth-column gap.
- Information/policy pages use the full storefront content shell.
- Keeps the existing UK address, Royal Mail / Evri / DPD / Click & collect methods, English Contact routing, My Account language fixes and comparison/filter repairs.
- No extra fix plugin is required; all repairs are part of the child theme.

## 4.0.15 UK commerce and language fixes

- Adds a United Kingdom WooCommerce shipping zone when the UK market is active.
- Adds built-in Click & collect Northampton, Royal Mail Tracked 48, Evri Standard and DPD Local shipping methods with editable WooCommerce instance costs.
- Stops the legacy Latvia-only country/session enforcement when the UK market is active.
- Makes cart and checkout shipping methods occupy the full order-summary width.
- Uses English checkout/account/payment labels on the English storefront.
- Fixes English Contact navigation so it resolves to `/contact/` instead of Latvian `/lv/kontakti/`.
- Expands managed information pages to the full storefront grid width.
- Makes the UK Delivery & payment page market-aware and uses the Northampton address.
- Compacts My Account navigation and removes inherited blank gaps between menu items.


## v4.0.17 regression hardening

- Restores the compact four-column homepage department layout without empty slots.
- Restores full-width UK delivery choices in cart and checkout summaries.
- Prevents the legacy Latvia shipping-calculator JavaScript from rewriting UK sessions.
- Removes later duplicate DOM-repair scripts that could slow cart/checkout updates.
- Keeps v4.0.14-v4.0.16 policy-page, UK address, menu, account-language, compare and 404 fixes intact.
- Tidies the Home & Garden admin success notice dismiss control.

### 4.0.18
- Restores the exact Git `master/web/app/themes/...` R5 four-column homepage department grid.
- Prevents `wpautop` line breaks from becoming department-grid nodes.
- Stops the v4.0.17 flex runtime layer from overriding the Git grid after page load.
- Adds a WooCommerce cart shipping template override using one full-width `colspan` cell, so cart and checkout delivery choices fill the complete summary sidebar.

## 4.0.20 final checkout and bilingual menu repair

- Restores true table semantics in cart/checkout totals so the existing `colspan="2"` shipping cell spans the complete order-summary width instead of inheriting only the first checkout column.
- Keeps every Royal Mail / Evri / DPD / Click & collect carrier card at 100% of the delivery block width.
- Stops the v4.0.19 language-repair routine that incorrectly treated the latest database as if its managed page language assignments were corrupt.
- Reasserts the canonical LV/EN Contact, Delivery & payment, Returns & warranty and Terms page pairs through Polylang.
- Repairs the demo bootstrap menu-language mapping: English now uses the EN Main/Footer/Customer menus and Latvian uses the LV menu set.
- Canonicalises custom policy/contact menu URLs to the active language, including `/lv/...` for Latvian while English remains the hidden-default root language.
- Preserves the approved four-column homepage department grid and 17px/13px category typography.

## 4.0.21 checkout, catalogue and Contact repairs

- Checkout shipping is normalised after every WooCommerce checkout refresh into one real two-column-spanning cell, so Click & collect, Royal Mail, Evri and DPD use the complete order-summary width.
- Smart-filter and native archive batches use 20 products: this is divisible by both the four-column and wide five-column desktop grids. A 25-product demo category now loads 20 initially and 5 on the final Load more click instead of 24 + 1.
- English and Latvian Contact pages append a responsive Google Map using the active storefront address, including 40 Brook Street, Northampton, NN1 2PE for the UK market.
- Existing v4.0.20 language/menu/database repairs and the approved four-column homepage department grid remain unchanged.


## 4.0.22 — bilingual product guides, richer demos and variation availability

- Adds six bilingual English/Latvian **Product Guide** demo posts covering heating systems, radiator variants, cultivators/mini tractors, variation stock, underfloor heating and garden-machinery maintenance.
- Adds a compact horizontal **Product Guides** slider near the bottom of the v4 homepage, immediately before the final service/CTA strip. The slider switches content with the active EN/LV storefront language.
- Adds extra Home & Garden demo products for heating and garden machinery, including panel radiators, electric underfloor heating, a 1050-style cultivator, compact mini tractor, towel radiator, condensing boiler, circulation pump and ride-on mower.
- Adds variable demo products with deliberately different per-variation stock states so in-stock, low-stock and unavailable options can all be tested.
- Variable product pages now show an **Options & availability** matrix with every variation's attributes, SKU, price and stock state, plus a button that selects that variation in the normal WooCommerce form.
- Product cards show the number of variations and how many are currently available; variable products use **Choose options / Izvēlēties variantu** rather than pretending they can be added directly without a selection.
- On local/development sites the additional demo content self-seeds once for administrators. A manual **Appearance → Demo Blog & Variations** screen can refresh it safely without duplicates.
- The normal **Create / refresh demo catalogue** action re-enables the showcase after demo removal; the normal demo removal action also removes the v4.0.22 showcase content.

## v4.0.23 realistic demos and editorial guides

- Replaces generic/reused demo artwork with product-relevant heating, radiator, underfloor-heating, cultivator, ride-on mower, tractor, boiler and heat-pump imagery.
- Expands the demo catalogue with more variable heating and garden-machinery products and per-variation price/SKU/stock states.
- Moves the complete variation availability matrix out of the WooCommerce purchase form so it no longer overlaps quantity/add-to-cart controls.
- Adds richer bilingual Product Guide demo posts and a photo-led homepage guide slider.
- Rebuilds single blog posts with a large featured image, editorial header, author information, reading time, sticky help/author panel, previous/next posts and related guides.
- Demo content can be refreshed from **Appearance → Demo Blog & Variations** without creating duplicates.


## 4.0.24 Blog, gallery and quote UX

- Adds **Blog / Blogs** to the storefront primary navigation and presents WordPress Posts as **Blog** in wp-admin.
- Creates bilingual managed Blog landing pages with a photo-led article grid.
- Adds a native Media Library **WP BB Article Gallery** selector to every blog post.
- Article galleries use a responsive slider with thumbnails, previous/next controls, keyboard navigation and a full-screen image modal/lightbox.
- Demo guides use corrected, relevant machinery/heating imagery rather than generic tool placeholders; a manually selected Featured Image or Gallery always takes priority.
- The floating **My Quote** control now opens a WooCommerce-style quote mini-drawer first instead of immediately navigating away.
- Quote drawer content is read from the existing `wp-theme-woo-support` quote session and refreshes automatically after Add to Quote.
- Request-a-Quote forms, item cards and submit/update buttons receive the same Home & Garden green/navy visual system as cart and checkout.

## 4.0.25 local showcase images, cart language and quote cleanup

- English mini-cart drawer now shows **Cart** rather than the hard-coded Latvian **Grozs** label; close labels follow the active language as well.
- Empty-cart **Browse products / Skatīt preces** buttons get proper left/right padding and a minimum readable width.
- Adds a fully bundled local showcase image set for the new heating, garden-machinery and variable demo products, avoiding the repeated/ugly remote demo imagery.
- Demo Product Guide posts now use different editorial images per topic on the homepage slider, Blog archive, previous/next cards and article galleries.
- Demo posts receive real Media Library Featured Images on the next admin request, so wp-admin also shows proper featured thumbnails.
- Existing demo guide galleries are refreshed with three local images per article while still allowing editors to replace the gallery from the normal Media Library selector.
- Older HG-DEMO catalogue items without a custom product image get a cleaner local category-appropriate showcase image instead of falling back to mismatched placeholders.
- Deleted/unpublished products are automatically pruned from the quote session so the floating quote count cannot stay stuck on a product that no longer exists.
- Adds **Clear quote** controls to the quote page and quote mini-drawer for explicitly emptying the full quote list.
