<?php
/** WP BB Home & Garden v4.0.18 — restore Git-master home grid + full-width Woo shipping. */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V418_VERSION')) {
    define('WPBBSHOP_V418_VERSION', '4.0.18');
}

add_action('wp_enqueue_scripts', function () {
    /* v4.0.17 replaced the known-good Git R5 grid with a flex runtime layer.
     * Disable only that runtime script; keep its non-home PHP/account fixes. */
    wp_dequeue_script('wpbbshop-v417-regression-hardening');
    wp_deregister_script('wpbbshop-v417-regression-hardening');

    /* Re-run the exact Git-master R5 grid script under a new handle so the
     * v4.0.17 dequeue cannot suppress it. */
    $grid_js = get_stylesheet_directory() . '/assets/js/v414-r5-home-grid.js';
    if (is_readable($grid_js)) {
        wp_enqueue_script(
            'wpbbshop-v418-git-r5-grid',
            get_stylesheet_directory_uri() . '/assets/js/v414-r5-home-grid.js',
            array(),
            (string) filemtime($grid_js),
            true
        );
    }

    $css = get_stylesheet_directory() . '/assets/css/v418-git-master-restore.css';
    if (is_readable($css)) {
        wp_enqueue_style(
            'wpbbshop-v418-git-master-restore',
            get_stylesheet_directory_uri() . '/assets/css/v418-git-master-restore.css',
            array('wpbbshop-v414-r5-home-grid'),
            (string) filemtime($css)
        );
    }
}, PHP_INT_MAX);

/* v4.0.17 prints a flex critical rule in wp_head. Print the Git grid after it
 * at the same priority (this file is loaded later) so even stale CSS caches
 * cannot recreate the three-column/empty-right-space regression. */
add_action('wp_head', function () {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v418-critical">
      html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;grid-auto-flow:row!important;grid-auto-rows:58px!important;gap:6px!important;width:100%!important;max-width:none!important;height:auto!important;min-height:0!important;margin:20px 0!important;padding:0!important}
      html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid>.wpbb-v400-dept{flex:none!important;width:auto!important;max-width:none!important;grid-column:auto!important;grid-row:auto!important;order:0!important;transform:none!important}
      @media(max-width:820px){html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
      @media(max-width:480px){html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid{grid-template-columns:1fr!important}}
    </style>
    <?php
}, PHP_INT_MAX);
