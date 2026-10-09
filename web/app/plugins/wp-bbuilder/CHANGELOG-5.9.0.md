# WP BBuilder 5.9.0 - local, WooCommerce and Google analytics

- Default local reports to non-demo rows; keep demo preview separate.
- Complete calendar-day windows, equal prior periods, UTC storage bounds and WordPress timezone/DST-aware day buckets.
- Consent-gated identifiers and collection, 30-minute idle sessions, DNT/GPC handling, staff exclusion and no query-string storage.
- Local SVG charts and accessible tables rather than Google Charts/CDN scripts.
- Read WooCommerce's own revenue reporting endpoint for sales comparison, with its date/status/refund policies.
- Native read-only GA4 account connection, property selector, WordPress Dashboard widget, trends, comparisons, pages and channels.
- Per-administrator encrypted grants, nonce/state validation, server-side refresh and private report caches.
- No GA tracking tag, purchase events, OAuth client credentials or fabricated Google data are installed.
- Optional Site Kit setup link; Site Kit remains a separate connection and dashboard.

Native Google login requires a one-time Google OAuth web-application setup and this module requires an HTTPS callback. Set it up on a real HTTPS staging/live domain, not plain http://wpbb.localhost. Changing the domain or WordPress salts requires reconnection.

A normal admin load applies additive analytics indexes (schema 1.1.0). No database backup restore is required. See the coordinated update guide for installation, consent setup and staging tests.
