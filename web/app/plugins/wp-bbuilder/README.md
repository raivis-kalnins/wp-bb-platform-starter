# WP BBuilder 5.8.0

## 5.8.0 local analytics dashboard

- Adds a first-party **WP BBuilder → Statistics** dashboard with Google Charts for visits, visitors, sessions, pages/session, countries, acquisition sources and devices.
- Tracks most visited pages and WooCommerce products without storing raw IP addresses.
- Uses hashed browser/session IDs, bot filtering, optional WordPress Consent API statistics consent and configurable data retention.
- Includes safe synthetic demo analytics data for local testing, plus demo-only and full data deletion controls.
- Adds a WordPress Dashboard summary widget.
- Includes a Laravel/Acorn Blade view namespace and Blade dashboard template with a native PHP fallback, so the same package works with or without Acorn.
- Analytics remains local to WordPress; Google Charts is used only for visualization.


## 5.6.9

- `wpbb/bootstrap-div` now supports a real background image/gradient, size, position, repeat and attachment in both the editor and front end. This lets child themes build full-width hero backgrounds with the BBuilder fluid container instead of placing a decorative `<img>` beside the copy.
- Adds `wpbb_verify_hcaptcha_token()` so child themes can protect native public forms with the same hCaptcha credentials already configured in BBuilder. Dynamic forms continue to verify hCaptcha server-side.
- Adds the `wpbb_option` filter so active child themes can safely inherit their own button/form palette instead of showing BBuilder's stored blue defaults.

## 5.6.8

- Restores the complete bundled Bootstrap 5.3.8 component, grid, utility, reboot, and JavaScript assets expected by the loader.
- Div now renders its selected semantic element and Bootstrap container class on the frontend without overriding Bootstrap's native container breakpoints.
- Booking admin uses the same provider/service metadata keys as frontend submissions, cancelled bookings free their slots, and Bookings are reachable under Settings.
- Booking submissions validate a signed server-rendered provider/service/time/email configuration instead of trusting editable hidden values.
- Legacy Group/Columns/Button conversion is opt-in via the `wpbb_enable_legacy_layout_migration` filter instead of silently rewriting existing content.
- Five-column auto layout no longer persists incorrect `col-lg-4` widths; explicit Bootstrap column classes disable the auto-grid fallback.

## 5.6.7

- Div now defaults to a Bootstrap `container`.
- Recovered equal-width rows materialise real Bootstrap responsive `col-*` classes in frontend HTML.
- Removed the `!important` auto-grid width workaround.

## 5.6.6
- Auto-distribute unsized BBuilder Columns into equal responsive grids on frontend and editor canvas.
- Keep dynamic block attribute schemas aligned between PHP and Gutenberg client registration.
- Adds a recovery path for managed demos saved with placeholder/default dynamic blocks.

## 5.6.5
- Icon Card now supports automatic text contrast for dark/light backgrounds plus an optional manual text colour override.
- Dark Icon Cards automatically use white headings, links and readable muted body text.
- Legacy Group/Columns migration runs again and repairs the malformed legacy Row closer found in older sector demo content.
- Added editor layout safeguards so nested Div/Row/Column structures remain full-width and readable in Gutenberg.


## 5.6.3 layout policy

- Native WordPress Group, Columns and Column blocks are permanently removed from the inserter. BBuilder Row + Column are the only grid primitives.
- BBuilder Bootstrap Div is presented simply as **Div** and is the neutral wrapper to use when grouping is genuinely required.
- Existing saved `core/group`, `core/columns` and `core/column` content is migrated to BBuilder Div / Row / Column on upgrade and normalised again when a post is saved.
- Native **Media & Text** remains available and stacks image first, text second on mobile.
- Added a pre-styled **Icon Card** block with Media Library image/SVG selection, inline SVG support, title, text and optional link.
- Swiper slide images can now be selected directly from the WordPress Media Library.

# WP BBuilder

## 5.6.1 admin layout correction

- Prevents editable-block names and block slugs from overlapping the Front-end editor role controls in narrow WordPress admin cards.
- Keeps the 5.6.0 translation-timing and editor-discovery changes unchanged.

## 5.6.0 translation timing and editor discovery

- Boots translated plugin components on `init`, after the text domain is loaded, to prevent WordPress 6.7+ just-in-time translation notices.
- Adds the optional **BBuilder Guide** editor sidebar with searchable groups, clearer descriptions, use-case guidance and direct block insertion.
- Enriches editor-only block keywords without renaming block types, attributes or saved markup. Existing projects and serialized block content remain compatible.

![Version](https://img.shields.io/badge/version-5.6.8-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-green)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)
![License](https://img.shields.io/badge/license-GPLv2-blue)

WP BBuilder is a modular Gutenberg block builder for WordPress with Bootstrap-friendly layouts, dynamic server-side rendering, optional Advanced Custom Fields integration, forms, data blocks, and admin-only editing helpers.

Version 5.6.1 keeps the original Row and Column controls—including responsive width selectors, quick presets, Unique IDs, class controls, spacing, backgrounds, and custom SCSS—while repairing native Swiper slide storage, project-wide shared asset loading, and project-aware AJAX-search post-type selection. SMTP testing still clearly distinguishes a temporary successful test from the saved SMTP state used by frontend forms.

## 5.5.2 contact-form and compatibility repair

- Swiper blocks save native slide arrays so Starter Setup and Gutenberg no longer corrupt nested JSON during post storage.
- Legacy `slidesJson` blocks continue to render and can be edited/migrated.
- The parent suite can force BBuilder/Bootstrap shared assets on global shell and CPT pages without requiring a BBuilder block in the queried post body.
- AJAX Search can receive a project-aware list of public post types (for example products, properties and doctors) and uses the site's actual `admin-ajax.php` URL.

## Installation

1. Upload the ZIP through **Plugins → Add New → Upload Plugin** or copy the `wp-bbuilder` folder to `/wp-content/plugins/`.
2. Activate **WP BBuilder**.
3. Open **Settings → BBuilder**.
4. Review enabled blocks, Bootstrap loading, ACF integration, and optional admin helpers.
5. Add blocks from the **BBuilder** category in the Block Editor.

## Row and Column editor

The Row and Column blocks continue to save Bootstrap-compatible classes on the front end. Their editor preview now uses an editor-only 12-column grid so the layout remains inside the normal WordPress content canvas.

Editor behavior:

- WordPress Desktop, Tablet, and Mobile preview changes are synchronized with Row and Column previews.
- Manual BBuilder preview buttons remain available in the original Inspector controls.
- Four `3/12` columns remain in one row.
- Twelve `1/12` columns remain in one row; the next column wraps.
- Columns stretch to the height of the other columns on the same grid row.
- Inner block areas can fill the available column height.
- Horizontal and vertical editor gaps follow the saved `gx-*` and `gy-*` values with compact preview spacing.
- Unique Row and Column IDs and their on-canvas labels are retained.
- Frontend Bootstrap classes and rendering are unchanged.

The full Bootstrap stylesheet and Bootstrap JavaScript are not loaded into the WordPress editor chrome. When **Load Bootstrap CSS in editor** is enabled, BBuilder loads a small canvas-scoped preview subset instead. This prevents Bootstrap resets from interfering with the native WordPress sidebar, mobile navigation, dialogs, and editor shell.

## ACF Field block

The dynamic **ACF Field** block displays a saved Advanced Custom Fields value in both the Block Editor and on the front end. Advanced Custom Fields 6.1 or newer is required for field output.

### Sources

- Current post, page, or Query Loop item
- ACF Options
- Current taxonomy term
- Current user or current post author

The Options source can be disabled under **Settings → BBuilder → ACF integration**.

### Display modes

- Automatic
- Text or list
- Image
- Link or button
- Embed
- Icon

The block supports common ACF text, number, email, URL, date/time, choice, true/false, WYSIWYG, image, file, link, oEmbed, icon, map, post object, relationship, taxonomy, and user values.

Repeater, Flexible Content, Gallery, Group, Clone, Tab, Accordion, Message, and Password fields are intentionally excluded from the generic picker because they require purpose-built templates.

### Editor and permissions

- The field picker returns field definitions, not stored values.
- Editor previews use WordPress server-side block rendering.
- Post, term, user, and Options sources are checked against the current user’s permissions during REST previews.
- Frontend output is escaped or allow-listed for its selected display mode.
- Existing ACF Field blocks remain registered when the inserter setting is disabled, preventing saved content from becoming invalid.

## ACF settings and dashboard information

Under **Settings → BBuilder → ACF integration** you can:

- show or hide the ACF Field block in the inserter;
- allow or disable the ACF Options source;
- see the installed ACF version and integration status;
- review available sources and bundled ACF blocks.

Administrators also receive a compact **WP BBuilder status** dashboard widget showing the plugin version, enabled block count, ACF status, ACF Field status, and Row editor status. It uses native WordPress dashboard markup and loads no separate dashboard assets.

## Included blocks

### Layout and structure

- Row
- Column
- Section
- Bootstrap Div
- Swiper
- Tabs and Tab Item
- Accordion and Accordion Item

### Content and UI

- Button
- Card and Cards
- CTA Card and CTA Section
- Alert
- Badge
- Breadcrumb
- List Group
- Navbar
- Progress
- Spinner
- Feature List
- Timeline
- Code Display
- Countdown Timer
- Fun Fact
- Table
- Video
- Custom Embed
- Inline SVG
- File

### Forms, data, and integrations

- Dynamic Form
- Mailchimp
- Login / Register
- Ajax Search
- Load More
- Blog Filter
- Catalogue
- Events
- Testimonials
- Price Cards
- Contact Links
- Booking Calendar
- Chart
- Google Map
- Weather
- Name Days
- Sitemap
- AI Content
- Social Follow, Share, and Feed blocks

### ACF-authored blocks

- ACF Hero
- ACF Gallery
- ACF Field

## Admin-side styling

BBuilder Inspector adjustments are deliberately scoped to `.block-editor-block-inspector` and use WordPress component classes wherever custom inputs are necessary. The plugin does not apply layout rules to `.interface-interface-skeleton__sidebar`, `.admin-ui-navigable-region`, editor modals, or viewport-level admin containers.

Custom spacing value and unit inputs carry WordPress component classes and remain visible within narrow Inspector sidebars. Open and closed `PanelBody` titles remain 100% of their available panel width without fixed positioning or viewport-sized overlays.

## Performance approach

- Frontend assets remain modular.
- Full Bootstrap resets are excluded from wp-admin.
- Bootstrap JavaScript is not loaded for editor previews.
- One shared WordPress device-preview subscription updates all Row and Column instances.
- ACF field definitions are cached per request URL in the editor.
- Dashboard status uses native markup and no extra scripts or styles.

## Developer filters

The ACF Field integration exposes these filters:

- `wpbb_acf_field_picker_groups`
- `wpbb_acf_field_value`
- `wpbb_acf_field_resolved_source`
- `wpbb_acf_field_output`

## Documentation included

- `docs/AVAILABLE-BLOCKS.md`
- `docs/ADMIN-HELPERS.md`
- block-specific `README.txt` files where available

## Changelog

### 5.5.0

- Expanded the `wpbb/swiper` block with desktop/tablet/mobile slides-per-view, autoplay delay, pause-on-hover, rewind, centered slides and effect controls.
- Added structured hero/editorial slide data with eyebrow, title, text, image/video and primary/secondary actions.
- Improved Swiper initialization for reduced motion and responsive settings while keeping slider assets block-driven.
- Supports the v3 BBTheme pattern system and Bootstrap container/grid demo architecture.

### 5.4.25

- Compatibility-reviewed for the WP BBTheme 2.0 project-mode suite.
- Retains per-block Bootstrap component loading and the existing editor-safe asset strategy.
- No store or real-estate project logic was moved into BBuilder, avoiding cross-project conflicts.
- Package metadata refreshed for current WordPress testing.


### 5.4.16

- Restored `flex-wrap: wrap` for direct Columns inside Rows.
- Column content can stack vertically again while responsive grid spans remain unchanged.
- Removed the editor-only `nowrap` override introduced in 5.4.15.
- Nested Rows and frontend Bootstrap widths remain unchanged.


### 5.4.15

- Replaced the broad Row/Column wrapping behavior with a direct-child rule.
- Columns now keep `flex-wrap: nowrap`, preventing internal content from collapsing or wrapping beside sibling content.
- Responsive `col-*` widths and the editor 12-column grid remain in control of Column sizing.
- Nested Rows and Columns remain independent.


### 5.4.8

- Fixed responsive Column previews so every Column in a Row uses the same selected editor breakpoint while retaining its own independent XS–XXL width values.
- Mixed-width Columns now preview correctly together when switching XS, SM, MD, LG, XL, or XXL.

- Center alignment preview now centers native WordPress blocks and BBuilder blocks inside Columns.
- Row Center and End alignment now position incomplete 12-column groups correctly in the editor.

- Inspector sidebar cleanup prevents a stale white editor column after the sidebar is closed at compact preview widths.
- PanelBody headings stay full width without negative margins or open-state jumping.

- Restored the original Row and Column Inspector system, responsive width controls, quick presets, Unique IDs, and on-canvas labels.
- Added a lightweight WordPress Desktop / Tablet / Mobile preview bridge.
- Added an editor-only 12-column grid with correct wrapping, compact gaps, and equal-height columns per grid row.
- Kept Row and Column previews inside the WordPress content canvas.
- Replaced full Bootstrap editor loading with a canvas-scoped preview subset.
- Stopped loading Bootstrap JavaScript in wp-admin block previews.
- Added WordPress-native, sidebar-safe Inspector styling without editor-shell overrides.
- Added the dynamic ACF Field block with post, Options, term, and user sources.
- Added compact ACF settings and a native dashboard status widget.
- Updated documentation and package metadata.

### 5.3.24

- Previous stable plugin baseline used for this update.

## License

GPLv2 or later.


### 5.4.9
- Corrected Inspector panel title alignment so titles remain left-aligned in open and closed states.
- Replaced alignment Dashicons with cleaner purpose-built alignment glyphs.
- Improved alignment button spacing, hover, and pressed states while retaining WordPress component buttons.

### 5.4.14
- Reduced opened Inspector panel content padding from WordPress's default 16px to 10px.
- Kept PanelBody title buttons full width and in the same position in open and closed states.
- Applied compact padding only around the opened panel content, not around the title button.

### 5.4.14
- Prevented frontend Bootstrap `row` and generated `col-*` classes from controlling editor-only Row/Column geometry.
- Nested Rows now stretch across the full parent Column editor track.
- Custom Button classes keep their theme-provided padding and radius in the editor preview.
- Button alignment now uses a full-width flex wrapper for reliable start, center, and end positioning.

### 5.4.14
- Fixed nested Row shrink-wrapping in the block editor when theme/page classes apply fixed, fit-content, or flex widths.
- Corrected the editor grid label selector and reinforced independent 12-column placement for mixed spans such as 3 + 1 + 3 + 1 + 3.


### 5.4.14
- Restored generated `col-*` responsive class names in the editor for child-theme preview selectors.
- Kept `data-wpbb-span` as the sole editor width authority, preventing Bootstrap/theme width collisions.
- Preserved independent per-Column responsive spans and frontend output.

### 5.4.18
- Added Row and Column background-position values for XS, SM, MD, LG, XL, and XXL Bootstrap breakpoints.
- Added inherited responsive previews and arbitrary X/Y CSS position values in the block inspector.
- Removed `!important` from Row and Column inline background declarations so theme/custom CSS can override them.

### 5.4.17
- Set `.wpbb-fie-block` to `width: 100%` so inline-edit wrappers fill their Column.
- Added `max-width: 100%`, `min-width: 0`, and border-box sizing to prevent overflow while preserving responsive Column widths.


## Earlier release notes

### 5.5.6 editor title alignment
- Overrides Gutenberg/global-style `max-width: 880px` and the automatic left margin on the main page/post title.
- Loads the override inside Gutenberg's editor iframe and leaves the title's remaining core layout styles unchanged.
- Existing 12/12 single-column editor-width behavior and Dynamic Form recipient fixes are preserved.
## 5.5.6

- Merged the Dynamic Form recipient fallback fixes from the supplied `wp-bbuilder-fixed.zip` (5.5.2) into the upgraded 5.5.3 editor-layout build.
- Empty or invalid per-block recipient values now fall back to WP BBuilder's configured default recipient/admin email both when rendering and when processing submissions.
- Preserves the newer 5.5.3 submission behavior plus the backend editor fixes for single 12/12 rows and left-aligned post/page titles.

## 5.5.3

- Block editor: single 12/12-column rows now automatically use the full available editor canvas instead of inheriting an unnecessary constrained content width.
- WordPress page/post title wrapper is aligned back to the normal left edge with the requested `max-width: fit-content` rule.

## 5.5.2
- Registered full Dynamic Form block attributes so imported contact forms preserve fields and presentation reliably.

## 5.6.3

- Icon Card alignment: choose Left, Center or Right. The selected alignment applies consistently to media/icon, title, body text and optional link in both editor and frontend.


## 5.8.2 admin analytics placement

- Website Statistics and Analytics Settings now live under **Settings** beside the normal BBuilder settings instead of creating a separate top-level WP BBuilder menu.
- Analytics header buttons keep a clean white outline/white text focus and hover state on the green header rather than inheriting the WordPress blue focus treatment.
