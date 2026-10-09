<?php
/**
 * WP BB Home & Garden 4.0.7 Polylang + permalink repair.
 *
 * Polylang owns page/language routing. The theme only provides lightweight
 * WooCommerce fallbacks for the shared demo inventory and translated taxonomy.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V407_VERSION')) define('WPBBSHOP_V407_VERSION', '4.0.7');

function wpbbshop_v407_polylang_native() { return true; }

function wpbbshop_v407_language_home_url($lang) {
    $lang = in_array($lang, array('en','lv'), true) ? $lang : 'en';
    if (function_exists('pll_home_url')) {
        $url = pll_home_url($lang);
        if (is_string($url) && $url !== '') return $url;
    }
    $primary = function_exists('wpbbshop_v400_primary_language') ? wpbbshop_v400_primary_language() : 'en';
    return $lang === $primary ? home_url('/') : home_url('/'.$lang.'/');
}

function wpbbshop_v407_localize_store_url($url, $lang = '') {
    return wpbbshop_v433_url($url, $lang);
}

function wpbbshop_v407_category_url($english_slug, $lang = '') {
    $lang = in_array($lang, array('en','lv'), true) ? $lang : (function_exists('wpbbshop_v400_current_language') ? wpbbshop_v400_current_language() : 'en');
    $term = get_term_by('slug', sanitize_title($english_slug), 'product_cat');
    if (!$term || is_wp_error($term)) return wpbbshop_v407_language_home_url($lang);
    if ($lang === 'lv' && function_exists('pll_get_term')) {
        $translated = absint(pll_get_term($term->term_id, 'lv'));
        if ($translated) {
            $candidate = get_term($translated, 'product_cat');
            if ($candidate && !is_wp_error($candidate)) $term = $candidate;
        }
    }
    $url = get_term_link($term);
    return is_wp_error($url) ? wpbbshop_v407_language_home_url($lang) : wpbbshop_v407_localize_store_url($url, $lang);
}

function wpbbshop_v407_product_url($product_id, $lang = '') {
    return wpbbshop_v433_product_url(absint($product_id), $lang);
}

function wpbbshop_v407_language_target_url($lang) {
    $lang = in_array($lang, array('en','lv'), true) ? $lang : 'en';
    if (is_front_page() || is_home()) return wpbbshop_v407_language_home_url($lang);
    if (function_exists('is_product') && is_product()) return wpbbshop_v407_product_url(get_queried_object_id(), $lang);
    if (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            if (function_exists('pll_get_term')) {
                $tid = absint(pll_get_term($term->term_id, $lang));
                if ($tid) {
                    $translated = get_term($tid, 'product_cat');
                    if ($translated && !is_wp_error($translated)) $term = $translated;
                }
            }
            $url = get_term_link($term);
            if (!is_wp_error($url)) return wpbbshop_v407_localize_store_url($url, $lang);
        }
    }
    if (is_singular() && function_exists('pll_get_post')) {
        $translated = absint(pll_get_post(get_queried_object_id(), $lang));
        if ($translated) {
            $url = get_permalink($translated);
            if ($url) return $url;
        }
    }
    return wpbbshop_v407_language_home_url($lang);
}

function wpbbshop_v407_language_switcher() {
    $current = function_exists('wpbbshop_v400_current_language') ? wpbbshop_v400_current_language() : 'en';
    $out = array();
    foreach (array('en'=>'EN','lv'=>'LV') as $lang=>$label) {
        $out[] = '<a class="llg-lang-link'.($current===$lang?' is-current':'').'" href="'.esc_url(wpbbshop_v407_language_target_url($lang)).'" hreflang="'.esc_attr($lang).'" lang="'.esc_attr($lang).'">'.esc_html($label).'</a>';
    }
    return '<span class="llg-lang-switcher">'.implode('<span class="llg-lang-sep">/</span>', $out).'</span>';
}

/* Shared demo products: allow a language-prefixed product URL without cloning stock. */
add_action('init', function() {
    if (!wpbbshop_v433_shared_bridge()) return;
    add_rewrite_rule('^(en|lv)/product/([^/]+)/?$', 'index.php?post_type=product&product=$matches[2]&lang=$matches[1]', 'top');
    add_rewrite_rule('^(en|lv)/product-category/(.+?)/?$', 'index.php?product_cat=$matches[2]&lang=$matches[1]', 'top');
}, 6);

add_action('pre_get_posts', function($query) {
    if (is_admin() || !wpbbshop_v433_shared_bridge() || !$query->is_main_query()) return;
    if ($query->get('product') || $query->get('post_type') === 'product') {
        // Keep the UI language from the request but do not filter the one shared
        // demo inventory by the language assigned to the product post itself.
        $query->set('lang', '');
    }
}, 2);

/* Make generated product links stay in the language currently being viewed. */
add_filter('post_type_link', function($url, $post) {
    if (!wpbbshop_v433_is_frontend() || !$post || $post->post_type !== 'product' || wpbbshop_v433_resolving()) return $url;
    $lang = function_exists('wpbbshop_v400_current_language') ? wpbbshop_v400_current_language() : 'en';
    return wpbbshop_v433_product_url($post->ID, $lang, $url);
}, 80, 2);

/* Clean one-time repair: front-page pair + categories + hard rewrite flush. */
function wpbbshop_v407_repair() {
    if (!current_user_can('manage_options')) return;
    if ((string)get_option('wpbbshop_v407_repair','') === WPBBSHOP_V407_VERSION) return;

    $primary = function_exists('wpbbshop_v400_primary_language') ? wpbbshop_v400_primary_language() : 'en';
    if (function_exists('wpbbshop_v400_ensure_home_translations')) wpbbshop_v400_ensure_home_translations($primary);
    if (function_exists('wpbbshop_v406_repair_bilingual_catalogue')) wpbbshop_v406_repair_bilingual_catalogue(true);

    delete_transient('wpbbshop_megastore_departments_312_en');
    delete_transient('wpbbshop_megastore_departments_312_lv');
    delete_option('rewrite_rules');
    flush_rewrite_rules(true);
    update_option('wpbbshop_v407_repair', WPBBSHOP_V407_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v407_repair', 40);
add_action('after_switch_theme', 'wpbbshop_v407_repair', 40);
