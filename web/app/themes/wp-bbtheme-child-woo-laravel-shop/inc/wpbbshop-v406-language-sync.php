<?php
/**
 * WP BB Home & Garden 4.0.6 bilingual catalogue repair.
 *
 * Keeps the market/demo catalogue as one WooCommerce inventory while providing
 * real Polylang EN/LV category translation pairs and reliable language links.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V406_VERSION')) {
    define('WPBBSHOP_V406_VERSION', '4.0.6');
}

function wpbbshop_v406_language_home_url($lang) {
    $lang = in_array($lang, array('en', 'lv'), true) ? $lang : 'en';

    if (function_exists('pll_home_url')) {
        $url = pll_home_url($lang);
        if (is_string($url) && $url !== '') {
            return $url;
        }
    }

    $front_id = absint(get_option('page_on_front'));
    if ($front_id && function_exists('pll_get_post')) {
        $translated = absint(pll_get_post($front_id, $lang));
        if ($translated) {
            $url = get_permalink($translated);
            if ($url) { return $url; }
        }
    }

    $primary = function_exists('wpbbshop_v400_primary_language') ? wpbbshop_v400_primary_language() : 'en';
    return $lang === $primary ? home_url('/') : home_url('/' . $lang . '/');
}

function wpbbshop_v406_language_target_url($lang) {
    $lang = in_array($lang, array('en', 'lv'), true) ? $lang : 'en';

    if (is_front_page() || is_home()) {
        return wpbbshop_v406_language_home_url($lang);
    }

    if (is_singular() && function_exists('pll_get_post')) {
        $translated = absint(pll_get_post(get_queried_object_id(), $lang));
        if ($translated) {
            $url = get_permalink($translated);
            if ($url) { return $url; }
        }
    }

    if ((is_tax() || is_category() || is_tag()) && function_exists('pll_get_term')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $translated = absint(pll_get_term($term->term_id, $lang));
            if ($translated) {
                $url = get_term_link($translated, $term->taxonomy);
                if (!is_wp_error($url)) { return $url; }
            }
        }
    }

    return wpbbshop_v406_language_home_url($lang);
}

/* v4.0.7: language roots are owned by Polylang; no custom /en/ or /lv/ rewrite here. */

function wpbbshop_v406_language_switcher() {
    $current = function_exists('wpbbshop_v400_current_language') ? wpbbshop_v400_current_language() : 'en';
    $links = array();
    foreach (array('en' => 'EN', 'lv' => 'LV') as $slug => $label) {
        $links[] = '<a class="llg-lang-link' . ($current === $slug ? ' is-current' : '') . '" href="' . esc_url(wpbbshop_v406_language_target_url($slug)) . '" hreflang="' . esc_attr($slug) . '" lang="' . esc_attr($slug) . '">' . esc_html($label) . '</a>';
    }
    return '<span class="llg-lang-switcher">' . implode('<span class="llg-lang-sep">/</span>', $links) . '</span>';
}

function wpbbshop_v406_department_pairs() {
    if (!taxonomy_exists('product_cat') || !function_exists('wpbbshop_megastore_departments_312')) {
        return array();
    }

    $pairs = array();
    foreach ((array) wpbbshop_megastore_departments_312() as $department) {
        $base_slug = isset($department['slug']) ? sanitize_title($department['slug']) : '';
        $en_name = isset($department['en']) ? sanitize_text_field($department['en']) : '';
        $lv_name = isset($department['lv']) ? sanitize_text_field($department['lv']) : '';
        if (!$base_slug || !$en_name || !$lv_name) { continue; }

        $en = get_term_by('slug', $base_slug, 'product_cat');
        if (!$en || is_wp_error($en)) {
            $created = wp_insert_term($en_name, 'product_cat', array('slug' => $base_slug));
            if (is_wp_error($created)) { continue; }
            $en = get_term(absint($created['term_id']), 'product_cat');
        }
        if (!$en || is_wp_error($en)) { continue; }
        $en_id = absint($en->term_id);
        if (function_exists('pll_set_term_language')) { @pll_set_term_language($en_id, 'en'); }
        if ($en->name !== $en_name) { @wp_update_term($en_id, 'product_cat', array('name' => $en_name)); }

        $lv_id = 0;
        if (function_exists('pll_get_term')) {
            $lv_id = absint(pll_get_term($en_id, 'lv'));
        }

        $lv_slug = sanitize_title($lv_name);
        if (!$lv_id) {
            $existing = get_term_by('slug', $lv_slug, 'product_cat');
            if ($existing && !is_wp_error($existing) && absint($existing->term_id) !== $en_id) {
                $lv_id = absint($existing->term_id);
            }
        }
        if (!$lv_id) {
            $created = wp_insert_term($lv_name, 'product_cat', array('slug' => $lv_slug));
            if (is_wp_error($created)) {
                // A translated term may already exist with a WordPress-suffixed slug.
                $existing = get_term_by('name', $lv_name, 'product_cat');
                if ($existing && !is_wp_error($existing) && absint($existing->term_id) !== $en_id) {
                    $lv_id = absint($existing->term_id);
                }
            } else {
                $lv_id = absint($created['term_id']);
            }
        }
        if (!$lv_id) { continue; }

        $lv_term = get_term($lv_id, 'product_cat');
        if ($lv_term && !is_wp_error($lv_term) && $lv_term->name !== $lv_name) {
            @wp_update_term($lv_id, 'product_cat', array('name' => $lv_name));
        }
        if (function_exists('pll_set_term_language')) { @pll_set_term_language($lv_id, 'lv'); }
        if (function_exists('pll_save_term_translations')) {
            @pll_save_term_translations(array('en' => $en_id, 'lv' => $lv_id));
        }

        $pairs[$base_slug] = array('en' => $en_id, 'lv' => $lv_id);
    }

    delete_transient('wpbbshop_megastore_departments_312_en');
    delete_transient('wpbbshop_megastore_departments_312_lv');
    return $pairs;
}

/**
 * Attach HG-DEMO products to both translated department terms without creating
 * duplicate products. Direct INSERT IGNORE keeps this fast for 500 demo items.
 */
function wpbbshop_v406_sync_demo_category_relationships($pairs = array()) {
    if (!$pairs) { $pairs = wpbbshop_v406_department_pairs(); }
    if (!$pairs || !class_exists('WooCommerce')) { return 0; }

    global $wpdb;
    $lookup = $wpdb->wc_product_meta_lookup;
    if (!$lookup) { return 0; }

    $rows = $wpdb->get_results(
        "SELECT product_id, sku FROM {$lookup} WHERE sku LIKE 'HG-DEMO-%' ORDER BY sku ASC LIMIT 600",
        ARRAY_A
    );
    if (!$rows) { return 0; }

    $departments = array_values((array) wpbbshop_megastore_departments_312());
    $tt_ids = array();
    $insert_values = array();

    foreach ($rows as $row) {
        $product_id = absint(isset($row['product_id']) ? $row['product_id'] : 0);
        $sku = isset($row['sku']) ? (string) $row['sku'] : '';
        if (!$product_id || !preg_match('/^HG-DEMO-(\d{4})$/', $sku, $match) || !$departments) { continue; }
        $index = max(0, ((int) $match[1]) - 1);
        $department = $departments[$index % count($departments)];
        $slug = isset($department['slug']) ? sanitize_title($department['slug']) : '';
        if (!$slug || empty($pairs[$slug])) { continue; }

        foreach (array('en', 'lv') as $lang) {
            $term_id = absint($pairs[$slug][$lang] ?? 0);
            if (!$term_id) { continue; }
            $tt_id = absint($wpdb->get_var($wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat' LIMIT 1",
                $term_id
            )));
            if (!$tt_id) { continue; }
            $tt_ids[$tt_id] = $tt_id;
            $insert_values[] = '(' . $product_id . ',' . $tt_id . ',0)';
        }
    }

    $inserted = 0;
    foreach (array_chunk($insert_values, 250) as $chunk) {
        if (!$chunk) { continue; }
        $result = $wpdb->query(
            "INSERT IGNORE INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES " . implode(',', $chunk)
        );
        if (is_int($result) && $result > 0) { $inserted += $result; }
    }

    if ($tt_ids) {
        wp_update_term_count_now(array_values($tt_ids), 'product_cat');
    }
    clean_taxonomy_cache('product_cat');
    return $inserted;
}

function wpbbshop_v406_repair_bilingual_catalogue($force = false) {
    if (!function_exists('pll_set_term_language') || !function_exists('pll_save_term_translations')) {
        return array('categories' => 0, 'relationships' => 0);
    }
    if (!$force && (string) get_option('wpbbshop_v406_language_catalogue_repair', '') === WPBBSHOP_V406_VERSION) {
        return array('categories' => 0, 'relationships' => 0);
    }

    if (function_exists('wpbbshop_v400_ensure_home_translations')) {
        wpbbshop_v400_ensure_home_translations(function_exists('wpbbshop_v400_primary_language') ? wpbbshop_v400_primary_language() : 'en');
    }
    $pairs = wpbbshop_v406_department_pairs();
    $relationships = wpbbshop_v406_sync_demo_category_relationships($pairs);

    delete_option('rewrite_rules');
    flush_rewrite_rules(false);
    update_option('wpbbshop_v406_language_catalogue_repair', WPBBSHOP_V406_VERSION, false);
    return array('categories' => count($pairs), 'relationships' => $relationships);
}

add_action('admin_init', function() {
    if (!current_user_can('manage_options')) { return; }
    wpbbshop_v406_repair_bilingual_catalogue(false);
}, 25);

/* Shared demo catalogue: allow the same inventory to render under EN or LV UI. */
add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query()) { return; }
    if ($query->is_post_type_archive('product') || $query->is_tax('product_cat') || $query->is_tax('product_tag')) {
        $query->set('lang', '');
    }
}, 4);

/* Keep default-language changes complete: homes, categories, URLs and rewrites. */
add_action('wpbbshop_primary_language_changed', function() {
    wpbbshop_v406_repair_bilingual_catalogue(true);
}, 10);
