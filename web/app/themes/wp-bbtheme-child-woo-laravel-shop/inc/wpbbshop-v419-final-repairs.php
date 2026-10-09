<?php
/**
 * WP BB Home & Garden v4.0.19 — shipping width, managed-page language repair,
 * readable department typography and Latvian hero hardening.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V419_VERSION')) {
    define('WPBBSHOP_V419_VERSION', '4.0.19');
}

function wpbbshop_v419_current_language() {
    if (function_exists('pll_current_language')) {
        $lang = wpbbshop_v433_current_language();
        if (is_string($lang) && $lang !== '') {
            return strtolower($lang);
        }
    }
    if (isset($_GET['lang'])) {
        $lang = sanitize_key(wp_unslash($_GET['lang']));
        if (in_array($lang, array('en', 'lv'), true)) {
            return $lang;
        }
    }
    $path = isset($_SERVER['REQUEST_URI']) ? trim((string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/') : '';
    if ($path === 'lv' || strpos($path, 'lv/') === 0) {
        return 'lv';
    }
    return 'en';
}

add_filter('body_class', function ($classes) {
    $classes[] = 'wpbbshop-lang-' . wpbbshop_v419_current_language();
    return array_values(array_unique($classes));
}, PHP_INT_MAX);

/**
 * The supplied 2026-10-02 database has several Latvian managed pages attached
 * to the English Polylang language term. Rebuild only these known page pairs.
 */
function wpbbshop_v419_repair_managed_page_languages() {
    if ((string) get_option('wpbbshop_v419_language_repair', '') === WPBBSHOP_V419_VERSION) {
        return;
    }
    if (!taxonomy_exists('language')) {
        return;
    }

    $pairs = array(
        array('piegade-un-apmaksa', 'delivery-payment'),
        array('atgriesana-un-garantija', 'returns-warranty'),
        array('pirksanas-noteikumi', 'terms-conditions'),
    );

    $changed = false;
    foreach ($pairs as $pair) {
        $lv_page = get_page_by_path($pair[0], OBJECT, 'page');
        $en_page = get_page_by_path($pair[1], OBJECT, 'page');
        if (!$lv_page instanceof WP_Post || !$en_page instanceof WP_Post) {
            continue;
        }

        /* Remove corrupted/obsolete Polylang relationships from these pages
         * before saving the canonical translation pair again. */
        foreach (array($lv_page->ID, $en_page->ID) as $page_id) {
            if (taxonomy_exists('term_language')) {
                wp_delete_object_term_relationships($page_id, 'term_language');
            }
            if (taxonomy_exists('term_translations')) {
                wp_delete_object_term_relationships($page_id, 'term_translations');
            }
            if (taxonomy_exists('post_translations')) {
                wp_delete_object_term_relationships($page_id, 'post_translations');
            }
        }

        /* Use Polylang's API first; wp_set_object_terms is a safe fallback for
         * databases where an old relationship prevents the API from replacing
         * the language term cleanly. */
        if (function_exists('pll_set_post_language')) {
            pll_set_post_language($lv_page->ID, 'lv');
            pll_set_post_language($en_page->ID, 'en');
        }
        wp_set_object_terms($lv_page->ID, 'lv', 'language', false);
        wp_set_object_terms($en_page->ID, 'en', 'language', false);

        if (function_exists('pll_save_post_translations')) {
            pll_save_post_translations(array(
                'lv' => (int) $lv_page->ID,
                'en' => (int) $en_page->ID,
            ));
        }
        clean_post_cache($lv_page->ID);
        clean_post_cache($en_page->ID);
        $changed = true;
    }

    if ($changed) {
        delete_option('rewrite_rules');
        flush_rewrite_rules(false);
    }
    update_option('wpbbshop_v419_language_repair', WPBBSHOP_V419_VERSION, false);
}
add_action('init', 'wpbbshop_v419_repair_managed_page_languages', 9999);
add_action('admin_init', 'wpbbshop_v419_repair_managed_page_languages', 9999);
add_action('after_switch_theme', 'wpbbshop_v419_repair_managed_page_languages', 9999);

/**
 * Backward-compatible Latvian policy URLs. English is the site's default
 * Polylang language, so an old root-level Latvian URL must resolve to the
 * canonical /lv/... page rather than silently changing the UI to English.
 */
add_action('template_redirect', function () {
    if (is_admin() || !function_exists('pll_home_url')) {
        return;
    }
    $path = isset($_SERVER['REQUEST_URI']) ? trim((string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/') : '';
    if ($path === '' || strpos($path, '/') !== false) {
        return;
    }
    $lv_slugs = array('piegade-un-apmaksa', 'atgriesana-un-garantija', 'pirksanas-noteikumi', 'kontakti');
    if (!in_array($path, $lv_slugs, true)) {
        return;
    }

    $lv_home = pll_home_url('lv');
    if (!is_string($lv_home) || $lv_home === '') {
        return;
    }
    $target = trailingslashit(untrailingslashit($lv_home) . '/' . $path);
    $target_path = trim((string) parse_url($target, PHP_URL_PATH), '/');
    if ($target_path !== $path) {
        wp_safe_redirect($target, 302);
        exit;
    }
}, -20);

/** Always prefer the known managed pair when building EN/LV switch targets. */
add_filter('pll_translation_url', function ($url, $lang = '') {
    if (!is_singular('page')) {
        return $url;
    }
    $slug = (string) get_post_field('post_name', get_queried_object_id());
    $map = array(
        'piegade-un-apmaksa' => array('lv' => 'piegade-un-apmaksa', 'en' => 'delivery-payment'),
        'delivery-payment' => array('lv' => 'piegade-un-apmaksa', 'en' => 'delivery-payment'),
        'atgriesana-un-garantija' => array('lv' => 'atgriesana-un-garantija', 'en' => 'returns-warranty'),
        'returns-warranty' => array('lv' => 'atgriesana-un-garantija', 'en' => 'returns-warranty'),
        'pirksanas-noteikumi' => array('lv' => 'pirksanas-noteikumi', 'en' => 'terms-conditions'),
        'terms-conditions' => array('lv' => 'pirksanas-noteikumi', 'en' => 'terms-conditions'),
    );
    if (!isset($map[$slug][$lang])) {
        return $url;
    }
    $target = get_page_by_path($map[$slug][$lang], OBJECT, 'page');
    if ($target instanceof WP_Post) {
        $permalink = get_permalink($target);
        if ($permalink) {
            return $permalink;
        }
    }
    return $url;
}, 9999, 2);

add_action('wp_enqueue_scripts', function () {
    $css = get_stylesheet_directory() . '/assets/css/v419-final-repairs.css';
    if (is_readable($css)) {
        wp_enqueue_style(
            'wpbbshop-v419-final-repairs',
            get_stylesheet_directory_uri() . '/assets/css/v419-final-repairs.css',
            array('wpbbshop-v418-git-master-restore'),
            (string) filemtime($css)
        );
    }
}, PHP_INT_MAX);

/* Final critical ownership also survives stale merged-theme CSS caches. */
add_action('wp_head', function () {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v419-critical">
      html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept strong{font-size:17px!important;font-weight:500!important;line-height:1.1!important}
      html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept small{font-size:13px!important;line-height:1.05!important}
      html body.woocommerce-cart .llg-cart-summary-card table.shop_table tbody{display:block!important;width:100%!important}
      html body.woocommerce-cart .llg-cart-summary-card table.shop_table tbody>tr{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;width:100%!important}
      html body.woocommerce-cart .llg-cart-summary-card table.shop_table tbody>tr.woocommerce-shipping-totals.shipping{display:block!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table tfoot{display:block!important;width:100%!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table tfoot>tr{display:grid!important;grid-template-columns:minmax(0,1fr) auto!important;width:100%!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table tfoot>tr.woocommerce-shipping-totals.shipping{display:block!important}
      html body.woocommerce-cart tr.woocommerce-shipping-totals.shipping>td,html body.woocommerce-checkout tr.woocommerce-shipping-totals.shipping>td{display:block!important;width:100%!important;max-width:none!important}
      html body.woocommerce-cart #shipping_method,html body.woocommerce-checkout #shipping_method{display:grid!important;grid-template-columns:minmax(0,1fr)!important;width:100%!important;max-width:none!important}
      html body.woocommerce-cart #shipping_method>li,html body.woocommerce-checkout #shipping_method>li{width:100%!important;max-width:none!important;box-sizing:border-box!important}
    </style>
    <?php
}, PHP_INT_MAX);
