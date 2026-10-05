<?php
/**
 * WP BB Home & Garden v4.0.16
 * Final cart/checkout width, UK checkout performance, homepage 4-column grid,
 * information-page width and language consistency repairs.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V416_VERSION')) {
    define('WPBBSHOP_V416_VERSION', '4.0.16');
}

/* The v4.0.15 zone migration worked, but it was registered on every Woo init.
 * Replace it with a once-per-version migration that returns before any zone scan. */
remove_action('woocommerce_init', 'wpbbshop_v415_ensure_uk_zone', 80);
remove_action('admin_init', 'wpbbshop_v415_ensure_uk_zone', 9999);
remove_action('after_switch_theme', 'wpbbshop_v415_ensure_uk_zone', 9999);

function wpbbshop_v416_ensure_uk_zone_once() {
    if (!function_exists('wpbbshop_v415_is_uk_market') || !wpbbshop_v415_is_uk_market()) { return; }
    if (!class_exists('WC_Shipping_Zones') || !class_exists('WC_Shipping_Zone')) { return; }

    $version = '4.0.16-uk-zone-1';
    if ((string) get_option('wpbbshop_v416_uk_zone_version', '') === $version) { return; }

    $wanted_options = array(
        'woocommerce_allowed_countries' => 'specific',
        'woocommerce_specific_allowed_countries' => array('GB'),
        'woocommerce_ship_to_countries' => 'specific',
        'woocommerce_specific_ship_to_countries' => array('GB'),
    );
    foreach ($wanted_options as $key => $value) {
        if (get_option($key) !== $value) { update_option($key, $value, false); }
    }

    $zone = null;
    foreach ((array) WC_Shipping_Zones::get_zones() as $zone_data) {
        foreach ((array) ($zone_data['zone_locations'] ?? array()) as $location) {
            if (isset($location->type, $location->code) && $location->type === 'country' && strtoupper((string) $location->code) === 'GB') {
                $zone = new WC_Shipping_Zone((int) $zone_data['zone_id']);
                break 2;
            }
        }
    }
    if (!$zone) {
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name('United Kingdom');
        $zone->set_zone_order(0);
        $zone->add_location('GB', 'country');
        $zone->save();
    }

    $wanted = array('wpbbshop_uk_pickup','wpbbshop_royal_mail','wpbbshop_evri','wpbbshop_dpd');
    $present = array();
    foreach ((array) $zone->get_shipping_methods(true) as $method) {
        if (!empty($method->id)) { $present[(string) $method->id] = true; }
    }
    foreach ($wanted as $method_id) {
        if (empty($present[$method_id])) { $zone->add_shipping_method($method_id); }
    }

    update_option('wpbbshop_v416_uk_zone_version', $version, false);
    if (class_exists('WC_Cache_Helper')) { WC_Cache_Helper::invalidate_cache_group('shipping'); }
}
add_action('woocommerce_init', 'wpbbshop_v416_ensure_uk_zone_once', 80);
add_action('admin_init', 'wpbbshop_v416_ensure_uk_zone_once', 9999);
add_action('after_switch_theme', 'wpbbshop_v416_ensure_uk_zone_once', 9999);

/* Force known information/policy pages into the wide storefront shell. */
add_filter('body_class', function ($classes) {
    $slugs = array(
        'delivery-payment','piegade-un-apmaksa','returns-warranty','atgriesana-un-garantija',
        'terms-conditions','terms-and-conditions','pirksanas-noteikumi','contact','kontakti','about-us','par-mums'
    );
    $request = trim((string) parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH), '/');
    $slug = basename($request);
    if (is_page($slugs) || in_array($slug, $slugs, true)) { $classes[] = 'wpbb-v416-wide-info'; }
    if (is_front_page()) { $classes[] = 'wpbb-v416-home'; }
    return $classes;
}, PHP_INT_MAX);

/* Load one final cache-busting layer. Dequeue the v4.0.15 JS because its broad
 * MutationObserver could repeatedly touch checkout fragments and make cart /
 * checkout feel slow. v4.0.16 listens to WooCommerce's own update events. */
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_script('wpbbshop-v415-uk-commerce-language');
    wp_deregister_script('wpbbshop-v415-uk-commerce-language');

    $css = get_stylesheet_directory() . '/assets/css/v416-final-fixes.css';
    $js  = get_stylesheet_directory() . '/assets/js/v416-final-fixes.js';

    if (is_readable($css)) {
        wp_enqueue_style(
            'wpbbshop-v416-final-fixes',
            get_stylesheet_directory_uri() . '/assets/css/v416-final-fixes.css',
            array('wpbbshop-v415-uk-commerce-language','wpbbshop-v414-r5-home-grid'),
            (string) filemtime($css)
        );
    }
    if (is_readable($js)) {
        wp_enqueue_script(
            'wpbbshop-v416-final-fixes',
            get_stylesheet_directory_uri() . '/assets/js/v416-final-fixes.js',
            array('jquery'),
            (string) filemtime($js),
            true
        );
        wp_localize_script('wpbbshop-v416-final-fixes', 'WpbbV416', array(
            'isUk' => function_exists('wpbbshop_v415_is_uk_market') ? wpbbshop_v415_is_uk_market() : false,
            'isEn' => function_exists('wpbbshop_v415_is_english') ? wpbbshop_v415_is_english() : false,
            'contactUrl' => function_exists('wpbbshop_v415_contact_url') ? wpbbshop_v415_contact_url('en') : home_url('/contact/'),
        ));
    }
}, PHP_INT_MAX);
