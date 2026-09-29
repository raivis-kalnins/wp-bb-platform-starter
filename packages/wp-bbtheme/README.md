# WP BBTheme Core 3.8.10.24


## 3.8.10.24 WooCommerce account routes and demo content finish

- Repairs stale WooCommerce My Account page IDs and endpoint routes on cloned/demo sites.
- Redirects legacy root account endpoint URLs such as `/orders/` and `/edit-address/` to the canonical My Account route.
- Places the managed Latest thinking “View all articles” action after the article grid.
- Keeps the 3.8.10.23 Woo Support dependency basename resolver.

## 3.8.10.23 My Account URL reliability

- Resolves the WooCommerce My Account header link from a real published page instead of trusting a stale cloned WooCommerce page ID.
- Falls back to the published `my-account` page (and the active Polylang translation where available) before using WooCommerce/login fallbacks.
- Uses the same resolver for generated commerce mega-menu account links.
- Retains the 3.8.10.22 Woo Support dependency resolver, including canonical and `-master` plugin directories.

## 3.8.10.20 reliability, gallery and Events integration

- Defers ACF value reads until `acf/init`, preventing the ACF 5.11+ early-value notice in WordPress administration.
- Adds a General-tab extension point used by child themes for frontend password protection and media-repair status.
- Adds the Events child theme to the maintained suite and lets a child safely enable an optional parent content type.
- Places item-gallery thumbnail navigation inside the bottom of the main image and avoids duplicate single-item galleries.
- Keeps the parent presentation-free while retaining the shared gallery, Starter Setup, multilingual, WooCommerce and BBuilder integration layers.

## 3.8.10.9 restored managed-shell layout

The maintained suite now uses modular SCSS with a shared `fluid-font()` helper instead of `clamp()`. Active SCSS and generated child CSS contain no `!important` rules; selector order and component ownership provide the cascade instead. Responsive type uses a px + vw equation bounded by explicit viewport media queries.

The parent also distinguishes the maintained suite from bespoke children that happen to use `Template: wp-bbtheme`. A legacy child such as the Garilla Woo project keeps its own template parts, frontend shell and dependency lifecycle. Parent updates therefore do not suppress the Garilla header/footer or inject the current suite UI layer. Projects can opt into the managed shell later through the `wp_theme_use_managed_shell` filter.

Child SCSS is organised as `tokens`, `tools`, `base`, `header`, `footer`, `components`, `swiper`, `motion`, `forms`, `blog`, `quality`, `sector`, `responsive` and `features`. The self-contained build script can expand the suite fluid-font helper offline and will use native Sass automatically when the `sass` package is already available in the development environment.

## 3.8.10 parent maintenance patch

This parent-only patch fixes the remaining shared issues: full-grid FAQ sections, Blog search icon/border alignment, resilient Polylang starter translation relationships with key demo-copy localisation, a desktop mega-menu hover bridge, and duplicate featured/inline images on single Blog posts. Existing child themes do not need rebuilding for this release.

WP BBTheme is the shared functional parent for a suite of **15 child-theme presentations**. The parent owns the reusable website system — Starter Setup, BBuilder/Gutenberg integration, menus, editorial blog tools, sitemap/legal/cookie/PWA features, editor productivity and WooCommerce integration hooks — while each child theme owns its sector presentation, demo content and specialist content types.


## Shared 3.8.8 multilingual setup and responsive completion

- **Automatic Polylang Starter Setup:** after Demo Import, active Polylang installs the requested English-first language set (EN, DE, ES, FR, PL, RU, LV, LT, ET, DA, SV, NO, FI, IS), sets English as the default and assigns generated starter pages/posts to English. If Polylang is activated after the demo was imported, the same setup runs automatically on the next authorised admin request before Polylang can redirect to its setup wizard.
- **Navigation remains usable in every language:** Starter Setup copies each existing header/footer menu mapping into Polylang's per-language menu-location table only where a language-specific menu is still empty. Editors can later replace any language menu without the theme overwriting it.
- **Cleaner language UI:** the old long language-code strip is replaced by one compact flag dropdown on desktop and a dedicated dropdown inside the mobile drawer. The footer uses the same compact control.
- **Translated search shell:** Search labels, placeholders, archive filters and controls follow the active Polylang language, with English as the safe default instead of inheriting an unrelated admin/site locale.
- **Responsive/alignment polish:** desktop hamburger leakage is blocked, mobile drawer controls are separated from the desktop header, and Blog filters, proof/stat cards, contact rows, galleries and footer controls use consistent shared geometry.
- **Motion/gallery preserved:** Garilla-inspired reveal/stagger effects and the body-level full-viewport lightbox remain shared across all child themes. Woo Support is unchanged at 3.4.0.


## Shared 3.8.7 language, motion and layout completion

- **Starter language bar included:** Starter Setup now enables a compact English-first language bar in the utility header. The visible preset covers EN, DE, ES, FR, PL, RU, LV, LT, ET, DA, SV, NO, FI and IS. When Polylang contains a language its code is a real frontend link; preset-only codes remain visibly documented until that language is configured in Polylang.
- **Polylang-ready shell:** the existing dropdown switcher remains available for configured Polylang languages, while the demo language bar is present even before multilingual content is fully configured. ACF Options for Polylang remains compatible with project-level theme settings.
- **Garilla-style native motion:** all child themes inherit subtle directional reveal, staggered cards/results, editorial zoom and restrained image float effects, with AJAX/MutationObserver support and `prefers-reduced-motion` handling.
- **Shared layout cleanup:** hero/action gaps, section rhythm, cards, process/stat rows, gallery overlays, Blog filter toolbar, single-post reading column, contact rows, footer newsletter and tablet/mobile drawer geometry now use one final parent-layer system.
- **Full-viewport gallery:** the dynamic gallery remains appended to `body` and owns the complete viewport/z-index stack. Woo Support remains unchanged at 3.4.0.

## Included child themes

| Theme | Purpose and included functionality |
| --- | --- |
| **Business (default)** | Professional agency/company website with full-width hero, services, work/case studies, team/about content, insights, contact forms and reusable BBuilder patterns. |
| **Medicine** | Clinic/medical website with doctors directory/filtering, appointments, Pharmacy products and a separate pharmacy-product quote workflow. |
| **Real Estate** | Property agency site with searchable/filterable property directory, sale/letting journeys, area/property content and enquiry-oriented cards. |
| **Fashion Shop** | WooCommerce fashion store with catalogue/product/cart/checkout/account layouts, product attributes and compatibility with the companion Cart + Quote module. |
| **Tech Shop** | WooCommerce technology store with catalogue/product/cart/checkout/account layouts, richer technical product presentation, variations and Cart + Quote support. |
| **Travel** | Travel-agency website with searchable trips/destinations, filters, trip cards, enquiry flows and travel-focused landing content. |
| **Hotel** | Hotel/accommodation website with room directory/filtering, room/service presentation, booking/enquiry routes and hospitality content. |
| **E-Learning** | Course website inspired by modern learning platforms: courses, lessons, video/PDF materials, quizzes/tests and structured learning content. |
| **Automotive** | New/used/rental vehicle directory, WooCommerce car-parts shop, workshop/service requests and vehicle/rental enquiry workflows. |
| **Insurance** | Insurance package catalogue with configurable WooCommerce package purchasing plus a separate quote-request route for tailored enquiries. |
| **Logistics** | Freight/delivery website for lorries, vans and logistics services, with service finder content and freight/transport quote requests. |
| **Restaurant** | Restaurant website with menu content, venue/service presentation, table reservation requests and hospitality-focused patterns. |
| **Building Services** | Trades/construction website for plumbing, electrical, development and handyman services with service routing and quote requests. |
| **Events** | WooCommerce-ready events and ticketing website with calendar/list discovery, venues and organisers, capacity-aware booking, linked ticket products, attendee records and calendar exports. |

## Shared 3.8.5 reliability and layout patch

- **Demo Import state is global and reliable:** the importer now mirrors its enable/disable flag outside language-localised ACF option storage, so ACF Options for Polylang cannot make an enabled setup tool appear disabled during `admin-ajax.php` requests. Existing enabled values are detected and migrated automatically.
- **Real Polylang header switcher:** when Polylang is active, the header language switcher is enabled by default independently of legacy menu metadata. Theme Settings includes an explicit global on/off switch and a direct Polylang Languages setup link. English remains first/default, followed by the configured German, Spanish, French, Polish, Russian, Baltic and Nordic languages.
- **Cleaner generated layout:** hero button groups have real gaps, process labels no longer overlap cards, stat/case blocks remove stray decorative pictograms, contact rows share one baseline, archive search/results align, and shared section spacing is tighter and more consistent.
- **Gallery presentation repaired:** editorial gallery captions are restored as overlays on photographic cards instead of leaving empty white card bottoms; clicking either the image or the gallery card opens the shared viewer.
- **True full-screen lightbox:** the viewer now forces `100vw × 100dvh` with a body-level maximum z-index stack and full-height image stage, avoiding clipping by transformed Swiper/card ancestors.

## Shared 3.8.4 shell, multilingual and lightbox completion

- **True viewport lightbox:** generic BBuilder/Gutenberg galleries and sector/CPT item galleries now open in one body-level viewer that owns `100vw × 100dvh`, sits above sticky headers/menus and supports keyboard, thumbnail and swipe navigation.
- **Header state correction:** desktop/mobile menu icons are mutually exclusive; light mode shows the moon action and dark mode shows the sun action. Header controls share one optical SVG baseline.
- **English-first Polylang shell:** visible Search/Menu/theme-control text follows Polylang’s active language rather than the site-wide WordPress locale, with English as the safe default. The language switcher supports English, German, Spanish, French, Polish, Russian, Baltic and Nordic languages enabled by the project.
- **ACF Options for Polylang compatibility:** Theme Settings adds multilingual guidance plus header-language display/mobile options. Standard ACF option calls are retained so language-aware option plugins can manage per-language values without theme-specific key suffixes.
- **Editorial/layout cleanup:** article TOC moves into a compact reading-width band, case metrics and proof blocks drop legacy Unicode ornaments, contact cards align their labels/values, footer/blog controls share consistent geometry and section rhythm is tightened.

## Shared 3.8.3 gallery and responsive stability update

- **Surface-based dark mode:** hero, section, card, form, menu, legal, cookie and editorial colours now follow their actual surface instead of forcing white headings globally. This removes the unreadable white-on-white and dark-on-dark combinations seen after switching colour mode.
- **Shared control alignment:** search, theme, menu and close controls use one optical size and centred SVG geometry; buttons, form controls and card icons share consistent baselines across all child themes.
- **Improved item gallery:** single sector items, directory/CPT list thumbnails and WooCommerce loop galleries can open the same accessible full-screen viewer with previous/next controls, keyboard navigation, touch swipe, image counter, caption and thumbnail rail. BBuilder/Gutenberg editorial galleries use the same viewer.
- **Business visual refresh:** the default Business child uses a calmer graphite/slate palette, tighter radii and neutral grey surfaces instead of the bright blue starter look.
- **Blog archive guardrails:** featured archive cards have bounded editorial media heights so a single image cannot expand into an oversized poster-like column.

## Shared 3.8.1 additions

- **Multi-image item gallery:** public sector CPTs with Featured Image support receive an Item Gallery media box. If an item has two or more images, directory/list cards show compact thumbnail previews with a modal gallery action and single views show a larger thumbnail gallery/lightbox. WooCommerce catalogue cards reuse the native Product gallery; the system also reads existing Post Project Gallery metadata for migration compatibility.
- **Per-project favicon and mobile app icon:** each child theme ships its own sector-coloured favicon, 180px Apple touch icon and 192/512px install icons. A WordPress Site Icon still takes priority when a project supplies its own brand artwork.
- **Native motion system inspired by the Garilla interaction model:** scroll reveals, directional hero/media transitions, staggered cards and very subtle floating editorial media. The existing animation settings control duration/delay/mobile behaviour and `prefers-reduced-motion` is respected.
- **Forms demo and pattern library:** `/forms/` includes nine ready BBuilder forms — contact, quote, booking, callback, service request, product enquiry, event/course registration, detailed application and newsletter — with shared responsive styling, focus states, file controls and legal/privacy integration. The same forms are available individually under the **Forms** pattern category.

## Shared 3.8 features

- **Full-width homepage hero** for the suite plus a reusable BBuilder/Swiper partner-logo slider pattern.
- **Site Map**, **Terms and Conditions** and **Privacy Policy** pages seeded by the shared parent. Existing manually edited legal pages are not overwritten.
- Integrated **cookie-consent banner** with essential/analytics/marketing choices, a footer “Manage cookies” action and legal links automatically added beside relevant form submissions.
- **Install on Apple** and **Install on Android** footer links. `?install=apple` and `?install=android` open platform-specific home-screen instructions; supported browsers can use the native PWA install prompt.
- Improved editorial archive with AJAX category filters, live search, Reset and Load More; improved single posts with reading progress, table of contents, sharing, author panel, previous/next links and related articles.
- Editor productivity: **New Page**, **Templates**, **Add section** and **Clone section** toolbar actions, plus a parent/child **Pattern Library** screen.
- Built-in **Clone** action for Pages, Posts and public editor-enabled CPTs, including content, taxonomies and metadata, saved safely as a new draft.
- Companion **WP Theme Woo Support 3.4.1** adds normal Cart and a separate Add to Quote route for WooCommerce simple, variable and grouped products while preserving selected variations and compatible complex add-on/composite options.

## Starter Setup

Run **Appearance → Starter Setup** after switching child themes when you want the managed starter pages/content refreshed. The setup creates/updates managed sector pages, assigns Header/Utility/Footer menus and editable mega menus, and ensures Site Map / Terms / Privacy pages exist. It does not intentionally delete unrelated user content. On upgrades, the essential Site Map, Privacy Policy and Terms pages are also checked once on the next authorised admin visit, so an in-place theme update does not depend on an `after_switch_theme` event.

For an existing production site, theme updates can be installed without refreshing Starter Setup. This preserves the site's current page content while still receiving shared CSS/JS/PHP improvements.

## Pattern Library and faster building

Use **Appearance → Pattern Library** to see only the reusable pattern files supplied by the parent and active child theme. In the block editor, **Templates** and **Add section** open the suite’s custom parent/child pattern library only; **Clone section** duplicates the selected top-level section directly in the editor. The list-table **Clone** action duplicates complete Pages, Posts and supported custom post types as drafts.

## Ecommerce and quotes

WooCommerce child themes remain compatible with WooCommerce core Cart/Checkout/My Account and legacy template surfaces. Install/update the companion **WP Theme Woo Support 3.4.1** package when you need the separate **Add to Quote** list alongside normal purchasing, including quantities and selected variation attributes.

## Build and compatibility

The suite targets WordPress 6.6+ and PHP 8.0+. Child themes compile their own presentation assets; the parent also supplies a late shared UI-foundation layer for cross-theme contrast, dark-mode safety, gallery behaviour and control alignment; sector palette/presentation remains owned by each child theme.
## 3.8.6 presentation refinement

The shared parent now owns the final responsive geometry for all sector themes: balanced full-width heroes, consistent section rhythm, card/icon sizing, process/stat/case layouts, gallery cards, Blog archive toolbar, footer newsletter alignment and a single tablet/mobile drawer. The item viewer is a true 100vw × 100dvh lightbox with a top-level z-index so Swiper/transformed containers cannot clip it.


## Legacy child compatibility

Parent-owned site essentials (cookie consent, automatic form privacy text, install/PWA dialogs and generated legal pages) are enabled only for the maintained WP BBTheme suite. Bespoke legacy children such as Garilla retain their own newsletter, cookie/privacy and PWA presentation without parent UI injection.

A bespoke child can explicitly opt in with the `wp_theme_site_essentials_enabled` filter.


## 3.8.10.21
Theme preview artwork refreshed for sharper WordPress Appearance previews; package documentation reduced to this README.


## 3.8.10.22
Fixed project dependency detection for WP Theme Woo Support when WordPress installs or updates the plugin from a GitHub-style archive directory such as `wp-theme-woo-support-master`. Starter Setup and admin dependency notices now resolve the plugin's real installed basename, so an active Woo Support plugin is no longer reported as missing and enable/disable actions target the correct installed copy.
