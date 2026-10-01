# WP BBTheme Child Woo Home & Garden Shop 4.0.13

# WP BB Home & Garden Child Theme v4.0.11

Large bilingual WooCommerce child theme for the WP BB Platform, designed for Home & Garden / DIY catalogues from small demos to very large product sets.

## v4.0.11 visual + catalogue polish

- Keeps the restored v4.0.10 homepage visual system: larger hero headline, better hero image crop, bundled WP BB Home & Garden logo/favicon assets, and compact 20-department grid.
- Makes the Shop hero badges easier to read with high-contrast green pills and check icons.
- Keeps category-first browsing but increases category-card text readability.
- Rebuilds the desktop filter area into two aligned rows: search/category/price/sort first, quick filters/actions second.
- Quick filters are now clear pill controls for **In stock**, **On sale**, and **Variations**.
- The Variations filter uses WooCommerce's `product_type=variable` taxonomy rather than expensive post-meta scanning.
- Apply/Reset buttons are the same height and aligned to the right on desktop.
- Mobile filters remain collapsible and stack cleanly.
- The centred green AJAX **Load more products** button remains the catalogue pagination model.
- Existing indexed WooCommerce price/stock/SKU/popularity filtering remains in place.

## Language URLs

With English first:

- English: `/`
- Latvian: `/lv/`

With Latvian first:

- Latvian: `/`
- English: `/en/`

## Market feeds

Market selection is independent from storefront language.

Latvia:
- KurPirkt
- Salidzini
- Ceno

United Kingdom:
- Google Merchant / Shopping
- PriceRunner UK
- PriceSpy UK
- idealo UK
- Kelkoo UK

## Large catalogue notes

For 100k+ products:

- keep product and category pages server-rendered for SEO;
- use AJAX only for archive filters and load-more interactions;
- use WooCommerce lookup tables and indexed taxonomies for filters;
- keep Redis available but fail-fast;
- use real cron/queues for feeds and imports;
- consider OpenSearch/Elasticsearch/Meilisearch or a dedicated catalogue index when fuzzy search and faceting exceed what MySQL/Woo lookup tables should handle.


## 4.0.12 smart Woo Support integration

- Uses WP Theme Woo Support 3.5.0 for the RackGroup-style smart AJAX filter engine.
- Search, stock, sale, sort, category, brand, rating, price/dimensions and Woo attribute filters live in the reusable plugin.
- Product compare now opens the plugin comparison modal from Home & Garden cards and the header.
- Custom Home & Garden product cards are preserved inside plugin AJAX results.
- Header logo is enlarged on desktop without shrinking the search/actions area.


## 4.0.13 UI polish

- Forces WP Theme Woo Support filter, range, compare and AJAX buttons into the Home & Garden green palette.
- Prevents plugin result add-to-cart buttons from reverting to default blue.
- Reduces the homepage hero H1 size/weight and prevents the action buttons from overlapping.
- Rebalances the mower image crop and hero card spacing.
- Makes the 20-department area a compact fixed-row grid without large vertical gaps.
- Tightens project cards and homepage section spacing while preserving responsive layouts.
