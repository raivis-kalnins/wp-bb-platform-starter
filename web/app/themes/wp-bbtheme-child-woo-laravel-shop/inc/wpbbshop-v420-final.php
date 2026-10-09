<?php
/**
 * WP BB Home & Garden v4.0.20 — final Woo shipping width and Polylang menu repair.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V420_VERSION')) {
    define('WPBBSHOP_V420_VERSION', '4.0.20');
}

/* v4.0.19 assumed page-language corruption that is not present in the latest
 * Updraft database. Stop that one-time routine and own the canonical pairs here. */
if (function_exists('wpbbshop_v419_repair_managed_page_languages')) {
    remove_action('init', 'wpbbshop_v419_repair_managed_page_languages', 9999);
    remove_action('admin_init', 'wpbbshop_v419_repair_managed_page_languages', 9999);
    remove_action('after_switch_theme', 'wpbbshop_v419_repair_managed_page_languages', 9999);
}

function wpbbshop_v420_page_pairs() {
    return array(
        array('lv' => 'kontakti', 'en' => 'contact'),
        array('lv' => 'piegade-un-apmaksa', 'en' => 'delivery-payment'),
        array('lv' => 'atgriesana-un-garantija', 'en' => 'returns-warranty'),
        array('lv' => 'pirksanas-noteikumi', 'en' => 'terms-conditions'),
    );
}

/** Ensure the storefront pages are attached to exactly one language and one
 * translation group. The latest database already has these pairs; this is a
 * safe self-healing guard for restores from older backups. */
function wpbbshop_v420_repair_page_pairs() {
    if ((string) get_option('wpbbshop_v420_page_pair_repair', '') === WPBBSHOP_V420_VERSION) {
        return;
    }
    if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations')) {
        return;
    }

    foreach (wpbbshop_v420_page_pairs() as $pair) {
        $lv = get_page_by_path($pair['lv'], OBJECT, 'page');
        $en = get_page_by_path($pair['en'], OBJECT, 'page');
        if (!$lv instanceof WP_Post || !$en instanceof WP_Post) {
            continue;
        }
        pll_set_post_language((int) $lv->ID, 'lv');
        pll_set_post_language((int) $en->ID, 'en');
        pll_save_post_translations(array('lv' => (int) $lv->ID, 'en' => (int) $en->ID));
        clean_post_cache($lv->ID);
        clean_post_cache($en->ID);
    }
    update_option('wpbbshop_v420_page_pair_repair', WPBBSHOP_V420_VERSION, false);
}
add_action('init', 'wpbbshop_v420_repair_page_pairs', 10020);
add_action('admin_init', 'wpbbshop_v420_repair_page_pairs', 10020);
add_action('after_switch_theme', 'wpbbshop_v420_repair_page_pairs', 10020);

function wpbbshop_v420_menu_term_id($slug) {
    $term = get_term_by('slug', $slug, 'nav_menu');
    return $term && !is_wp_error($term) ? (int) $term->term_id : 0;
}

/**
 * Repair the database menu-language mapping created by the demo bootstrap.
 * The supplied backup maps English header/footer locations to the Latvian
 * menu IDs (23/25). Store both languages in Polylang and use EN as the normal
 * fallback because English is the configured default language.
 */
function wpbbshop_v420_repair_menu_language_map() {
    if ((string) get_option('wpbbshop_v420_menu_language_repair', '') === WPBBSHOP_V420_VERSION) {
        return;
    }

    $lv_primary = wpbbshop_v420_menu_term_id('wp-bb-home-garden-lv-galvena');
    $lv_footer  = wpbbshop_v420_menu_term_id('wp-bb-home-garden-lv-kajenes');
    $lv_service = wpbbshop_v420_menu_term_id('wp-bb-home-garden-lv-klientiem');
    $en_primary = wpbbshop_v420_menu_term_id('wp-bb-home-garden-en-main');
    $en_footer  = wpbbshop_v420_menu_term_id('wp-bb-home-garden-en-footer');
    $en_service = wpbbshop_v420_menu_term_id('wp-bb-home-garden-en-customer');

    if (!$lv_primary || !$lv_footer || !$en_primary || !$en_footer) {
        return;
    }

    $locations = array(
        'primary'            => $en_primary,
        'footer'             => $en_footer,
        'service'            => $en_service ?: $en_footer,
        'top'                => 0,
        'wp-header-top-menu' => $en_primary,
        'wp-header-menu'     => 0,
        'wp-footer-menu'     => $en_footer,
    );

    foreach (array('wp-bbtheme-child-woo-laravel-shop', 'wp-bbtheme') as $stylesheet) {
        $key = 'theme_mods_' . $stylesheet;
        $mods = get_option($key, array());
        if (!is_array($mods)) {
            $mods = array();
        }
        $existing = isset($mods['nav_menu_locations']) && is_array($mods['nav_menu_locations']) ? $mods['nav_menu_locations'] : array();
        $mods['nav_menu_locations'] = array_merge($existing, $locations);
        update_option($key, $mods, false);
    }

    $pll = get_option('polylang', array());
    if (is_array($pll)) {
        if (!isset($pll['nav_menus']) || !is_array($pll['nav_menus'])) {
            $pll['nav_menus'] = array();
        }
        $shared = array(
            'primary' => array('en' => $en_primary, 'lv' => $lv_primary),
            'footer' => array('en' => $en_footer, 'lv' => $lv_footer),
            'service' => array('en' => $en_service ?: $en_footer, 'lv' => $lv_service ?: $lv_footer),
            'wp-header-top-menu' => array('en' => $en_primary, 'lv' => $lv_primary),
            'wp-footer-menu' => array('en' => $en_footer, 'lv' => $lv_footer),
        );
        $pll['nav_menus']['wp-bbtheme'] = $shared;
        $pll['nav_menus']['wp-bbtheme-child-woo-laravel-shop'] = $shared;
        update_option('polylang', $pll, false);
    }

    update_option('wpbbshop_v420_menu_language_repair', WPBBSHOP_V420_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v420_repair_menu_language_map', 10030);
add_action('after_switch_theme', 'wpbbshop_v420_repair_menu_language_map', 10030);

/** Use the correct menu immediately, even before the one-time DB repair runs. */
add_filter('theme_mod_nav_menu_locations', function ($locations) {
    if (!is_array($locations)) {
        $locations = array();
    }
    $lang = function_exists('pll_current_language') ? wpbbshop_v433_current_language() : '';
    $is_lv = ($lang === 'lv');
    $primary = wpbbshop_v420_menu_term_id($is_lv ? 'wp-bb-home-garden-lv-galvena' : 'wp-bb-home-garden-en-main');
    $footer  = wpbbshop_v420_menu_term_id($is_lv ? 'wp-bb-home-garden-lv-kajenes' : 'wp-bb-home-garden-en-footer');
    $service = wpbbshop_v420_menu_term_id($is_lv ? 'wp-bb-home-garden-lv-klientiem' : 'wp-bb-home-garden-en-customer');
    if ($primary) {
        $locations['primary'] = $primary;
        $locations['wp-header-top-menu'] = $primary;
    }
    if ($footer) {
        $locations['footer'] = $footer;
        $locations['wp-footer-menu'] = $footer;
    }
    if ($service) {
        $locations['service'] = $service;
    }
    return $locations;
}, 10000);

/** Canonicalise the known policy/contact links in custom menu items. */
add_filter('wp_nav_menu_objects', function ($items) {
    if (!is_array($items)) {
        return $items;
    }
    $lang = function_exists('pll_current_language') ? wpbbshop_v433_current_language() : '';
    $lang = ($lang === 'lv') ? 'lv' : 'en';
    $map = array(
        'contact' => array('lv' => 'kontakti', 'en' => 'contact'),
        'kontakti' => array('lv' => 'kontakti', 'en' => 'contact'),
        'delivery-payment' => array('lv' => 'piegade-un-apmaksa', 'en' => 'delivery-payment'),
        'piegade-un-apmaksa' => array('lv' => 'piegade-un-apmaksa', 'en' => 'delivery-payment'),
        'returns-warranty' => array('lv' => 'atgriesana-un-garantija', 'en' => 'returns-warranty'),
        'atgriesana-un-garantija' => array('lv' => 'atgriesana-un-garantija', 'en' => 'returns-warranty'),
        'terms-conditions' => array('lv' => 'pirksanas-noteikumi', 'en' => 'terms-conditions'),
        'terms-and-conditions' => array('lv' => 'pirksanas-noteikumi', 'en' => 'terms-conditions'),
        'pirksanas-noteikumi' => array('lv' => 'pirksanas-noteikumi', 'en' => 'terms-conditions'),
    );
    foreach ($items as $item) {
        if (!is_object($item) || empty($item->url)) {
            continue;
        }
        $path = trim((string) parse_url((string) $item->url, PHP_URL_PATH), '/');
        $slug = basename($path);
        if (!isset($map[$slug][$lang])) {
            continue;
        }
        $page = get_page_by_path($map[$slug][$lang], OBJECT, 'page');
        if ($page instanceof WP_Post) {
            $item->url = get_permalink($page);
        }
    }
    return $items;
}, 10000);

/** Root-level Latvian aliases belong under /lv/ when English is the hidden
 * default language. Redirect those old bookmarks to the real LV permalink. */
add_action('template_redirect', function () {
    if (is_admin() || !function_exists('pll_get_post_language')) {
        return;
    }
    $path = isset($_SERVER['REQUEST_URI']) ? trim((string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/') : '';
    if ($path === '' || strpos($path, '/') !== false) {
        return;
    }
    $lv_slugs = array('kontakti', 'piegade-un-apmaksa', 'atgriesana-un-garantija', 'pirksanas-noteikumi');
    if (!in_array($path, $lv_slugs, true)) {
        return;
    }
    $page = get_page_by_path($path, OBJECT, 'page');
    if (!$page instanceof WP_Post || pll_get_post_language($page->ID, 'slug') !== 'lv') {
        return;
    }
    $target = get_permalink($page);
    if ($target) {
        $target_path = trim((string) parse_url($target, PHP_URL_PATH), '/');
        if ($target_path !== $path) {
            wp_safe_redirect($target, 302);
            exit;
        }
    }
}, -30);

add_action('wp_enqueue_scripts', function () {
    $file = get_stylesheet_directory() . '/assets/css/v420-final.css';
    if (is_readable($file)) {
        wp_enqueue_style(
            'wpbbshop-v420-final',
            get_stylesheet_directory_uri() . '/assets/css/v420-final.css',
            array('wpbbshop-v419-final-repairs'),
            (string) filemtime($file)
        );
    }
    $js = get_stylesheet_directory() . '/assets/js/v420-final.js';
    if (is_readable($js) && (function_exists('is_cart') && (is_cart() || is_checkout()))) {
        wp_enqueue_script(
            'wpbbshop-v420-final',
            get_stylesheet_directory_uri() . '/assets/js/v420-final.js',
            array('jquery'),
            (string) filemtime($js),
            true
        );
    }
}, PHP_INT_MAX);

/* Critical ownership after all legacy layers and merged-cache CSS. */
add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <style id="wpbbshop-v420-critical">
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table>tfoot{display:table-footer-group!important;width:auto!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table>tfoot>tr,html body.woocommerce-checkout .woocommerce-checkout-review-order-table>tfoot>tr.wpbbshop-shipping-full-row{display:table-row!important;width:auto!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table>tfoot>tr>th,html body.woocommerce-checkout .woocommerce-checkout-review-order-table>tfoot>tr>td,html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.wpbbshop-shipping-full-row>td{display:table-cell!important;width:auto!important;max-width:none!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.wpbbshop-shipping-full-row>td[colspan="2"]{text-align:left!important;padding-left:0!important;padding-right:0!important}
      html body.woocommerce-cart .llg-cart-summary-card table.shop_table{display:table!important;width:100%!important}
      html body.woocommerce-cart .llg-cart-summary-card table.shop_table>tbody{display:table-row-group!important}
      html body.woocommerce-cart .llg-cart-summary-card table.shop_table>tbody>tr,html body.woocommerce-cart .llg-cart-summary-card tr.wpbbshop-shipping-full-row{display:table-row!important;width:auto!important}
      html body.woocommerce-cart .llg-cart-summary-card table.shop_table>tbody>tr>th,html body.woocommerce-cart .llg-cart-summary-card table.shop_table>tbody>tr>td,html body.woocommerce-cart .llg-cart-summary-card tr.wpbbshop-shipping-full-row>td{display:table-cell!important;width:auto!important;max-width:none!important}
      html body .wpbbshop-shipping-full,html body .wpbbshop-shipping-full #shipping_method,html body .wpbbshop-shipping-full .woocommerce-shipping-methods{width:100%!important;max-width:none!important;min-width:0!important}
      html body .wpbbshop-shipping-full #shipping_method>li,html body .wpbbshop-shipping-full .woocommerce-shipping-methods>li{width:100%!important;max-width:none!important;box-sizing:border-box!important}
    </style>
    <?php
}, PHP_INT_MAX);
