<?php
/**
 * WP BB Home & Garden 4.0.4 stability layer.
 *
 * Keeps this bespoke commerce child theme isolated from the parent managed
 * shell and removes old maintenance routines that can block Redis or frontend
 * requests on a large catalogue.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V404_VERSION')) {
    define('WPBBSHOP_V404_VERSION', '4.0.4');
}

/** This child renders its own [wpbbshop_header] and [wpbbshop_footer]. */
add_filter('wp_theme_use_managed_shell', function($managed, $stylesheet) {
    if ((string) $stylesheet === 'wp-bbtheme-child-woo-laravel-shop' || get_stylesheet() === 'wp-bbtheme-child-woo-laravel-shop') {
        return false;
    }
    return $managed;
}, PHP_INT_MAX, 2);

/** Extra guard when the parent registered its callbacks before this filter. */
add_action('after_setup_theme', function() {
    remove_action('wp_body_open', 'wp_theme_render_site_header', 8);
    remove_action('wp_footer', 'wp_theme_render_site_footer', 5);
}, PHP_INT_MAX);

/**
 * Some third-party admin code calls remove_menu_page() before WordPress has
 * populated $menu. WordPress core then foreach()'s null. Initialise the globals
 * for admin requests so those premature callbacks fail harmlessly instead.
 */
if (is_admin()) {
    global $menu, $submenu;
    if (!is_array($menu)) {
        $menu = array();
    }
    if (!is_array($submenu)) {
        $submenu = array();
    }
}

/**
 * Old visual patch modules used a full persistent-object-cache flush as a
 * one-time upgrade mechanism. On Redis with a larger catalogue that can block
 * long enough to hit PHP's execution limit. Versioned assets/transients already
 * invalidate the child theme safely, so remove these legacy flush callbacks.
 */
$wpbbshop_v404_legacy_cache_hooks = array(
    'wpbbshop_v322_purge_cache_once' => 999,
    'wpbbshop_v323_purge_cache_once' => 999,
    'wpbbshop_v324_purge_cache_once' => 999,
    'wpbbshop_v327_purge_cache_once' => 1001,
    'wpbbshop_v328_cache_purge_once' => 1002,
    'wpbbshop_v329_cache_purge_once' => 1005,
    'wpbbshop_v330_cache_purge_once' => 1010,
    'wpbbshop_v331_cache_purge_once' => 1015,
    'wpbbshop_v332_cache_purge_once' => 1016,
    'wpbbshop_v333_cache_purge_once' => 1017,
);
foreach ($wpbbshop_v404_legacy_cache_hooks as $callback => $priority) {
    remove_action('admin_init', $callback, $priority);
}
unset($wpbbshop_v404_legacy_cache_hooks, $callback, $priority);

/**
 * Do not make a slow Omniva network request inside checkout/admin rendering.
 * When its transient is empty, schedule a one-off cron refresh and return fast.
 */
function wpbbshop_v404_defer_omniva_request($preempt, $args, $url) {
    if (strpos((string) $url, 'omniva.ee/locations.json') === false) {
        return $preempt;
    }
    if (function_exists('wp_doing_cron') && wp_doing_cron()) {
        return $preempt;
    }
    if (!wp_next_scheduled('wpbbshop_v404_refresh_omniva_lockers')) {
        wp_schedule_single_event(time() + 5, 'wpbbshop_v404_refresh_omniva_lockers');
    }
    return new WP_Error('wpbbshop_omniva_deferred', 'Omniva locker refresh scheduled in the background.');
}
add_filter('pre_http_request', 'wpbbshop_v404_defer_omniva_request', 5, 3);

add_action('wpbbshop_v404_refresh_omniva_lockers', function() {
    delete_transient('wpbbshop_omniva_lv_lockers_v2');
    if (function_exists('wpbbshop_omniva_locker_locations')) {
        wpbbshop_omniva_locker_locations();
    }
});

/** Limit the external request even during cron. */
add_filter('http_request_args', function($args, $url) {
    if (strpos((string) $url, 'omniva.ee/locations.json') !== false) {
        $args['timeout'] = 5;
        $args['redirection'] = 2;
    }
    return $args;
}, 20, 2);

/** One cheap migration marker; deliberately never calls wp_cache_flush(). */
function wpbbshop_v404_stability_migrate() {
    if ((string) get_option('wpbbshop_v404_stability_version', '') === WPBBSHOP_V404_VERSION) {
        return;
    }
    delete_option('wpbbshop_v315_feed_publish_error');
    update_option('wpbbshop_v315_feed_version', WPBBSHOP_V404_VERSION, false);
    update_option('wpbbshop_v404_stability_version', WPBBSHOP_V404_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v404_stability_migrate', 5);
