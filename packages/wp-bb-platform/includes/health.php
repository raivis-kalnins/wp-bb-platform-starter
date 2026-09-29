<?php
if (!defined('ABSPATH')) exit;

function wpbb_platform_health(): array {
    global $wpdb;

    $woocommerce = defined('WC_VERSION') ? WC_VERSION : null;
    $acorn = class_exists('Roots\\Acorn\\Application') ? 'available' : 'missing';
    $redis = class_exists('Redis') ? 'PHP extension loaded' : 'PHP extension missing';
    if (function_exists('wp_cache_supports') && wp_cache_supports('flush_runtime')) {
        $redis .= '; object cache active';
    } elseif (defined('WP_REDIS_HOST')) {
        $redis .= '; configured';
    }

    $hpos = 'n/a';
    if (class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil')) {
        $hpos = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ? 'enabled' : 'disabled';
    }

    return [
        'WordPress' => get_bloginfo('version'),
        'PHP' => PHP_VERSION,
        'Database' => $wpdb->db_version(),
        'Environment' => defined('WP_ENV') ? WP_ENV : wp_get_environment_type(),
        'Acorn' => $acorn,
        'WooCommerce' => $woocommerce ?: 'not active',
        'Woo HPOS' => $hpos,
        'Redis' => $redis,
        'BBTheme' => wp_get_theme('wp-bbtheme')->exists() ? wp_get_theme('wp-bbtheme')->get('Version') : 'not installed',
        'BBuilder' => defined('WPBB_VERSION') ? WPBB_VERSION : 'not active',
        'Woo Support' => defined('WP_THEME_WOO_SUPPORT_VERSION') ? WP_THEME_WOO_SUPPORT_VERSION : 'not active',
        'WP-CLI admin execution' => (defined('WPBB_ALLOW_ADMIN_CLI') && WPBB_ALLOW_ADMIN_CLI) ? 'enabled' : 'disabled',
    ];
}
