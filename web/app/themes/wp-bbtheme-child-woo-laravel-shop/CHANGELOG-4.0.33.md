# 4.0.33 - EN/LV language continuity

Based on the supplied active 4.0.32 theme, not the older 3.1.3 package source.

- Consolidates language detection around the current URL and storefront AJAX
  request instead of allowing a shared product's post language to choose the UI.
- Localizes menu, logo, search, wishlist, product, taxonomy and WooCommerce links.
- Prefers existing Polylang product/page/term translations. Shared inventory keeps
  a language-prefixed storefront URL without creating duplicate stock records.
- Preserves query values, fragments, checkout endpoints and nonces when rewriting
  known local links. External/admin/asset URLs and language switchers are excluded.
- Adds scoped language context for theme/Woo-support jQuery and fetch AJAX calls.
- Resolves the existing shared shop/cart/checkout/account/shell pages and prevents
  narrow canonical redirects from removing their explicit language prefix.
- Corrects legacy policy redirects without changing the stored translation map.
- Keeps the shared-commerce fallback out of the official Polylang Woo integration.

No database migration, product cloning, permanent preference cookie or design
change is included. Existing untranslated content is not machine-translated.

Back up and replace/overlay the same child-theme directory, then save permalinks
once, purge caches and hard-refresh. In the platform repository also update the
matching packages/ source folder so a later Composer install cannot restore old
code. Follow the separate source kit README before making a production release.

Validation: 89 isolated PHP assertions + 23 JavaScript transport assertions, PHP
and JavaScript syntax checks. The local hostname and cPanel server were not
accessible for a live WordPress/browser/checkout test; perform the acceptance
checks in the hosting guide before production use.
