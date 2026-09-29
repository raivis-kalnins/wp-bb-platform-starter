# WP BBuilder admin helpers

## Block Editor layout

Row and Column retain the original BBuilder controls and IDs. A small editor-only stylesheet applies a 12-column CSS grid to direct Column children. The stylesheet is scoped to `.editor-styles-wrapper` and does not style the WordPress editor shell.

The editor preview follows WordPress Desktop, Tablet, and Mobile device changes through one shared data-store subscription. Each Row or Column can still be previewed manually with its existing BBuilder breakpoint buttons.

## Inspector controls

BBuilder’s Inspector rules are scoped to `.block-editor-block-inspector`. Custom spacing inputs also use WordPress component classes. The stylesheet does not target interface skeleton, admin navigation, modal, or viewport-level selectors.

## Bootstrap preview

The full Bootstrap CSS reset and Bootstrap JavaScript are not loaded in the Block Editor. The optional editor CSS setting now loads only a canvas-scoped utility preview file.

## ACF Field block

The ACF Field picker uses an authenticated REST endpoint that returns field definitions only. Saved values are previewed through WordPress server-side block rendering with source-specific permission checks.

## Dashboard status

Administrators see a native WordPress dashboard widget with plugin, block, ACF, and Row editor status. No separate dashboard assets are loaded.
