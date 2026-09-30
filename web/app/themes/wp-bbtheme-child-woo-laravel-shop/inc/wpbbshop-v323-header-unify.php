<?php
/**
 * WP BB Home & Garden 3.0.23
 * Keep one desktop header geometry on every route.
 *
 * IMPORTANT: no page-specific header CSS here. The shared v3.0.21 header layer
 * is the single source of truth for homepage, archives, products and pages.
 */
defined('ABSPATH') || exit;

/** Purge parent/theme caches once after upgrading to 3.0.23. */
function wpbbshop_v323_purge_cache_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    $done = (string) get_option('wpbbshop_v323_cache_purged', '');
    if ($done === '3.0.23') { return; }

    if (function_exists('wp_theme_purge_all_theme_cache')) {
        wp_theme_purge_all_theme_cache();
    } elseif (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    delete_transient('wc_products_onsale');
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients();
    }
    update_option('wpbbshop_v323_cache_purged', '3.0.23', false);
}
add_action('admin_init', 'wpbbshop_v323_purge_cache_once', 999);
