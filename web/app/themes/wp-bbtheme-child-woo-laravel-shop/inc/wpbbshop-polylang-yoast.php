<?php
/**
 * WP BB HOME & GARDEN Polylang + Yoast integration.
 * Keeps the storefront bilingual (EN default + LV) and avoids duplicate SEO output.
 */
defined('ABSPATH') || exit;

function wpbbshop_polylang_active_235() {
    return function_exists('PLL') && function_exists('pll_get_post_language') && function_exists('pll_set_post_language');
}

function wpbbshop_polylang_language_model_235() {
    if (!function_exists('PLL')) { return null; }
    $pll = PLL();
    if (!is_object($pll) || empty($pll->model) || !is_object($pll->model)) { return null; }
    if (isset($pll->model->languages) && is_object($pll->model->languages)) { return $pll->model->languages; }
    return $pll->model;
}

function wpbbshop_polylang_language_slug_235($language) {
    if (is_object($language) && isset($language->slug)) { return sanitize_key((string)$language->slug); }
    if (is_array($language) && isset($language['slug'])) { return sanitize_key((string)$language['slug']); }
    return '';
}

function wpbbshop_polylang_language_term_id_235($language) {
    if (is_object($language) && isset($language->term_id)) { return absint($language->term_id); }
    if (is_array($language) && isset($language['term_id'])) { return absint($language['term_id']); }
    return 0;
}

function wpbbshop_polylang_list_235() {
    $model = wpbbshop_polylang_language_model_235();
    if (!$model) { return array(); }
    try {
        if (method_exists($model, 'get_list')) { return (array)$model->get_list(); }
        $pll = PLL();
        if (is_object($pll) && !empty($pll->model) && is_callable(array($pll->model, 'get_languages_list'))) {
            return (array)$pll->model->get_languages_list();
        }
    } catch (Throwable $e) {}
    return array();
}

function wpbbshop_polylang_ensure_language_235($slug, $name, $locale, $flag, $order) {
    foreach (wpbbshop_polylang_list_235() as $lang) {
        if (wpbbshop_polylang_language_slug_235($lang) === $slug) { return true; }
    }
    $model = wpbbshop_polylang_language_model_235();
    if (!$model) { return false; }
    $data = array(
        'name'=>$name,
        'slug'=>$slug,
        'locale'=>$locale,
        'rtl'=>false,
        'flag'=>$flag,
        'no_default_cat'=>false,
        'term_group'=>(int)$order,
    );
    try {
        if (method_exists($model, 'add')) {
            $result = $model->add($data);
        } else {
            $pll = PLL();
            $result = is_object($pll) && !empty($pll->model) && is_callable(array($pll->model, 'add_language')) ? $pll->model->add_language($data) : false;
        }
        return (bool)($result && !is_wp_error($result));
    } catch (Throwable $e) { return false; }
}

function wpbbshop_polylang_set_default_en_235() {
    $model = wpbbshop_polylang_language_model_235();
    if (!$model) { return; }
    try {
        if (method_exists($model, 'update_default')) { $model->update_default('en'); }
        else {
            $pll = PLL();
            if (is_object($pll) && !empty($pll->model) && is_callable(array($pll->model, 'update_default_lang'))) {
                $pll->model->update_default_lang('en');
            }
        }
    } catch (Throwable $e) {}
    $opts = get_option('polylang', array());
    if (!is_array($opts)) { $opts = array(); }
    $opts['default_lang'] = 'en';
    $opts['hide_default'] = true;
    $opts['media_support'] = false;
    update_option('polylang', $opts, false);
    update_option('WPLANG', 'en_GB', false);
}

function wpbbshop_polylang_assign_storefront_en_235() {
    if (!wpbbshop_polylang_active_235()) { return; }
    $ids = array();
    $front = absint(get_option('page_on_front'));
    if ($front) { $ids[] = $front; }
    foreach (array('woocommerce_shop_page_id','woocommerce_cart_page_id','woocommerce_checkout_page_id','woocommerce_myaccount_page_id','woocommerce_terms_page_id') as $key) {
        $id = absint(get_option($key)); if ($id) { $ids[] = $id; }
    }
    foreach (array('demo-homepage','par-mums','piegade','piegade-un-apmaksa','apmaksa','atgriesana','atgriesana-un-garantija','kontakti','pirksanas-noteikumi','biezak-uzdotie-jautajumi','garantija','sudzibu-iesniegsana','track-your-order') as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page instanceof WP_Post) { $ids[] = $page->ID; }
    }
    $ids = array_unique(array_filter(array_map('absint', $ids)));
    foreach ($ids as $id) { @pll_set_post_language($id, 'en'); }
    // Large-catalog safe: do not rewrite every product/term language on admin_init.
    // Only assign the theme's own lightweight demo products; real catalogue language ownership stays with Polylang/import tools.
    $demo_ids = get_posts(array('post_type'=>'product','post_status'=>'any','posts_per_page'=>500,'fields'=>'ids','meta_key'=>'_wpbbshop_demo_product','meta_value'=>'1','no_found_rows'=>true,'suppress_filters'=>true));
    foreach ((array)$demo_ids as $id) { @pll_set_post_language((int)$id, 'en'); }
}

function wpbbshop_polylang_delete_extra_languages_235() {
    $model = wpbbshop_polylang_language_model_235();
    if (!$model) { return array(); }
    $removed = array();
    foreach (wpbbshop_polylang_list_235() as $lang) {
        $slug = wpbbshop_polylang_language_slug_235($lang);
        $term_id = wpbbshop_polylang_language_term_id_235($lang);
        if (!$slug || in_array($slug, array('lv','en'), true) || !$term_id) { continue; }
        $ok = false;
        try {
            // Polylang 3.7+ API.
            if (method_exists($model, 'delete')) { $ok = (bool)$model->delete($term_id); }
            else {
                $pll = PLL();
                if (is_object($pll) && !empty($pll->model) && is_callable(array($pll->model, 'delete_language'))) {
                    $pll->model->delete_language($term_id);
                    $ok = true;
                }
            }
        } catch (Throwable $e) { $ok = false; }
        // Last-resort cleanup for stale language terms left by older starter imports.
        if (!$ok && taxonomy_exists('language')) {
            $deleted = wp_delete_term($term_id, 'language');
            $ok = !is_wp_error($deleted) && false !== $deleted;
        }
        if ($ok) { $removed[] = $slug; }
    }
    try {
        $pll = PLL();
        if (is_object($pll) && !empty($pll->model) && method_exists($pll->model, 'clean_languages_cache')) { $pll->model->clean_languages_cache(); }
    } catch (Throwable $e) {}
    return $removed;
}

function wpbbshop_polylang_trim_menu_maps_235() {
    $opts = get_option('polylang', array());
    if (!is_array($opts)) { $opts = array(); }
    if (!empty($opts['nav_menus']) && is_array($opts['nav_menus'])) {
        foreach ($opts['nav_menus'] as $theme_key => $locations) {
            if (!is_array($locations)) { continue; }
            foreach ($locations as $location => $map) {
                if (is_array($map)) {
                    $opts['nav_menus'][$theme_key][$location] = array_intersect_key($map, array('lv'=>true,'en'=>true));
                }
            }
        }
    }
    $opts['default_lang'] = 'en';
    $opts['hide_default'] = true;
    $opts['media_support'] = false;
    update_option('polylang', $opts, false);
}

function wpbbshop_polylang_sync_lv_en_235($force = false) {
    if (!wpbbshop_polylang_active_235()) { return array('configured'=>false,'reason'=>'polylang_inactive'); }
    $slugs = array();
    foreach (wpbbshop_polylang_list_235() as $lang) { $slug = wpbbshop_polylang_language_slug_235($lang); if ($slug) { $slugs[] = $slug; } }
    sort($slugs);
    if (!$force && $slugs === array('en','lv') && get_option('wpbbshop_polylang_sync_235') === 'done') {
        return array('configured'=>true,'removed'=>array());
    }
    wpbbshop_polylang_ensure_language_235('en','English','en_GB','gb',0);
    wpbbshop_polylang_ensure_language_235('lv','Latviešu','lv','lv',1);
    wpbbshop_polylang_set_default_en_235();
    wpbbshop_polylang_assign_storefront_en_235();
    $removed = wpbbshop_polylang_delete_extra_languages_235();
    wpbbshop_polylang_trim_menu_maps_235();
    update_option('wp_theme_language_switcher_enabled', '1', false);
    update_option('wp_theme_demo_language_bar_enabled', '1', false);
    update_option('wpbbshop_polylang_sync_235', 'done', false);
    return array('configured'=>true,'removed'=>$removed);
}

// Run once late in admin after Polylang and widgets have initialized. It is lightweight and does no media work.
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) { return; }
    wpbbshop_polylang_sync_lv_en_235(false);
}, 999);

// Keep only the two intended language columns even during the one request in which stale terms are being removed.
function wpbbshop_polylang_admin_columns_235($columns) {
    if (!is_array($columns)) { return $columns; }
    foreach (array_keys($columns) as $key) {
        if (0 === strpos((string)$key, 'language_') && !in_array($key, array('language_lv','language_en'), true)) { unset($columns[$key]); }
    }
    return $columns;
}
foreach (array('page','post','product') as $pt) {
    add_filter('manage_' . $pt . '_posts_columns', 'wpbbshop_polylang_admin_columns_235', 999);
}

// Restrict the Polylang admin-bar language list to All + LV + EN while stale starter languages are being cleaned.
add_filter('pll_admin_languages_filter', function($items, $all_items) {
    if (!is_array($items)) { return $items; }
    return array_values(array_filter($items, function($item) {
        $slug = '';
        if (is_object($item) && isset($item->slug)) { $slug = sanitize_key((string)$item->slug); }
        elseif (is_array($item) && isset($item['slug'])) { $slug = sanitize_key((string)$item['slug']); }
        return in_array($slug, array('all','lv','en'), true);
    }));
}, 999, 2);

/** Yoast SEO ***************************************************************/
function wpbbshop_yoast_active_235() {
    return defined('WPSEO_VERSION') || class_exists('WPSEO_Options') || class_exists('Yoast\\WP\\SEO\\Main');
}

function wpbbshop_seo_breadcrumbs_235() {
    if (wpbbshop_yoast_active_235() && function_exists('yoast_breadcrumb')) {
        return yoast_breadcrumb('<nav class="wpbbshop-breadcrumbs yoast-breadcrumbs" aria-label="Breadcrumbs">','</nav>', false);
    }
    if (function_exists('woocommerce_breadcrumb') && (function_exists('is_woocommerce') && is_woocommerce())) {
        ob_start();
        woocommerce_breadcrumb(array('wrap_before'=>'<nav class="wpbbshop-breadcrumbs" aria-label="Breadcrumbs">','wrap_after'=>'</nav>'));
        return ob_get_clean();
    }
    return '';
}

// Yoast owns description/canonical/OpenGraph/schema. The theme only supplies a description fallback when Yoast has none.
add_filter('wpseo_metadesc', function($description) {
    if (is_string($description) && trim($description) !== '') { return $description; }
    if (function_exists('is_product') && is_product()) {
        global $product;
        if ($product instanceof WC_Product) {
            $text = wp_strip_all_tags($product->get_short_description());
            if (!$text) { $text = wp_strip_all_tags($product->get_description()); }
            return wp_trim_words($text, 28, '…');
        }
    }
    if (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term && !empty($term->description)) { return wp_trim_words(wp_strip_all_tags($term->description), 28, '…'); }
    }
    if (is_page() && function_exists('wpbbshop_information_page_definitions')) {
        $post = get_post();
        $defs = wpbbshop_information_page_definitions();
        if ($post && isset($defs[$post->post_name]['lead'])) { return wp_strip_all_tags($defs[$post->post_name]['lead']); }
    }
    return $description;
}, 20);

// If an individual product has no Yoast social image, use its WooCommerce featured image.
add_filter('wpseo_opengraph_image', function($url) {
    if ($url) { return $url; }
    if (function_exists('is_product') && is_product()) {
        global $product;
        if ($product instanceof WC_Product && $product->get_image_id()) {
            $src = wp_get_attachment_image_url($product->get_image_id(), 'full');
            if ($src) { return $src; }
        }
    }
    return $url;
}, 20);

add_filter('wpseo_twitter_image', function($url) {
    if ($url) { return $url; }
    if (function_exists('is_product') && is_product()) {
        global $product;
        if ($product instanceof WC_Product && $product->get_image_id()) {
            $src = wp_get_attachment_image_url($product->get_image_id(), 'full');
            if ($src) { return $src; }
        }
    }
    return $url;
}, 20);

// Keep Yoast's schema for products and organisation intact; do not print competing theme schema.
add_filter('wpseo_breadcrumb_separator', function() { return '›'; });


/** Polylang + WooCommerce fallback compatibility *****************************
 *
 * This is intentionally a lightweight theme-side bridge. It keeps WooCommerce
 * core pages language-aware when Polylang is active, while leaving complex
 * product/variation/stock synchronisation to the official Polylang for
 * WooCommerce add-on when that plugin is installed.
 */
function wpbbshop_polylang_woo_official_active_312() {
    return defined('PLLWC_VERSION') || class_exists('PLLWC') || class_exists('Polylang_Woocommerce');
}

function wpbbshop_polylang_current_slug_312() {
    if (!function_exists('pll_current_language')) { return ''; }
    $slug = pll_current_language('slug');
    return is_string($slug) ? sanitize_key($slug) : '';
}

function wpbbshop_polylang_translate_woo_page_id_312($page_id) {
    $page_id = absint($page_id);
    if (!$page_id || !function_exists('pll_get_post')) { return $page_id; }
    $lang = wpbbshop_polylang_current_slug_312();
    if (!$lang) { return $page_id; }
    $translated = absint(pll_get_post($page_id, $lang));
    return $translated ?: $page_id;
}

// WooCommerce obtains its special page IDs through these filters. Translating
// them here keeps Shop, Cart, Checkout, My Account and Terms links in the
// current Polylang language when translated pages exist.
foreach (array('shop', 'cart', 'checkout', 'myaccount', 'terms') as $wpbbshop_wc_page_312) {
    add_filter(
        'woocommerce_get_' . $wpbbshop_wc_page_312 . '_page_id',
        'wpbbshop_polylang_translate_woo_page_id_312',
        20
    );
}
unset($wpbbshop_wc_page_312);

// Keep the standard WooCommerce URLs language-aware as a second safety net.
add_filter('woocommerce_get_cart_url', function($url) {
    if (!function_exists('pll_get_post') || !function_exists('wc_get_page_id')) { return $url; }
    $id = wpbbshop_polylang_translate_woo_page_id_312(wc_get_page_id('cart'));
    return $id > 0 ? get_permalink($id) : $url;
}, 20);

add_filter('woocommerce_get_checkout_url', function($url) {
    if (!function_exists('pll_get_post') || !function_exists('wc_get_page_id')) { return $url; }
    $id = wpbbshop_polylang_translate_woo_page_id_312(wc_get_page_id('checkout'));
    return $id > 0 ? get_permalink($id) : $url;
}, 20);

// The free Polylang plugin displays an upsell warning when WooCommerce is
// active. This project provides a deliberate theme-side compatibility bridge,
// so hide only that specific admin notice. Other Polylang notices remain.
function wpbbshop_hide_polylang_woo_upsell_312() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    if (!class_exists('WooCommerce') || !function_exists('PLL')) { return; }
    if (wpbbshop_polylang_woo_official_active_312()) { return; }
    ?>
    <style id="wpbbshop-polylang-woo-notice-css">
      .notice:has(a[href*="polylang.pro/pricing/polylang-for-woocommerce"]),
      .updated:has(a[href*="polylang.pro/pricing/polylang-for-woocommerce"]),
      .error:has(a[href*="polylang.pro/pricing/polylang-for-woocommerce"]) { display:none !important; }
    </style>
    <script id="wpbbshop-polylang-woo-notice-js">
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.notice, .updated, .error').forEach(function (node) {
        var link = node.querySelector('a[href*="polylang.pro/pricing/polylang-for-woocommerce"]');
        if (link && /Polylang for WooCommerce|using Polylang with WooCommerce/i.test(node.textContent || '')) {
          node.remove();
        }
      });
    });
    </script>
    <?php
}
add_action('admin_head', 'wpbbshop_hide_polylang_woo_upsell_312', 999);
