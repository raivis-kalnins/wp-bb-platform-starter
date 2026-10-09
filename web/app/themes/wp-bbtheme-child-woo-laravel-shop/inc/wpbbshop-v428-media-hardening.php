<?php
/**
 * WP BB Home & Garden v4.0.28
 * Reliable local photographic demo media + Product Guide image hardening.
 *
 * The v4.0.27 Wikimedia layer remains available for rich galleries, but cards
 * no longer depend on a remote host or previously cached generated artwork.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V428_VERSION')) {
    define('WPBBSHOP_V428_VERSION', '4.0.28');
}

/** Real product photos already bundled with the demo package. */
function wpbbshop_v428_real_product_files() {
    return array(
        'brushcutter.webp',
        'blower-v45-v46.webp',
        'welder-v43-v46.webp',
        'trimmer-head-v43-v46.webp',
        'oil-pump-v43-v46.webp',
        'generator.webp',
        'drill-v46.webp',
        'trimmer-line-v45-v46.webp',
        'chain-sharpener-v43-v46.webp',
        'tool-set.webp',
        'aluminum-head-v45-v46.webp',
        'compressor.webp',
        'jack-v43-v46.webp',
    );
}

function wpbbshop_v428_asset_url($file) {
    $file = basename((string) $file);
    if ($file === '') { return ''; }
    $path = trailingslashit(get_stylesheet_directory()) . 'assets/demo-products/' . $file;
    if (!is_readable($path)) { return ''; }
    return trailingslashit(get_stylesheet_directory_uri()) . 'assets/demo-products/' . rawurlencode($file);
}

/**
 * Stable photo selection for every bundled/demo product.
 * Existing merchant products with real Media Library images are untouched.
 */
function wpbbshop_v428_demo_product_asset($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $sku = (string) $product->get_sku();
    $is_demo = strpos($sku, 'HG-DEMO-') === 0 || strpos($sku, 'LL-DEMO-') === 0 || get_post_meta($product->get_id(), '_wpbbshop_v422_demo_key', true);
    if (!$is_demo) { return ''; }
    $files = wpbbshop_v428_real_product_files();
    if (!$files) { return ''; }
    $seed = $sku !== '' ? $sku : (string) $product->get_id();
    $index = abs(crc32($seed)) % count($files);
    return wpbbshop_v428_asset_url($files[$index]);
}

function wpbbshop_v428_demo_product_gallery($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return array(); }
    $sku = (string) $product->get_sku();
    $is_demo = strpos($sku, 'HG-DEMO-') === 0 || strpos($sku, 'LL-DEMO-') === 0 || get_post_meta($product->get_id(), '_wpbbshop_v422_demo_key', true);
    if (!$is_demo) { return array(); }
    $files = wpbbshop_v428_real_product_files();
    if (!$files) { return array(); }
    $seed = $sku !== '' ? $sku : (string) $product->get_id();
    $base = abs(crc32($seed)) % count($files);
    $urls = array();
    foreach (array(0, 5, 9) as $offset) {
        $url = wpbbshop_v428_asset_url($files[($base + $offset) % count($files)]);
        if ($url && !in_array($url, $urls, true)) { $urls[] = $url; }
    }
    return $urls;
}

/** Each Product Guide gets a different local photographic image. */
function wpbbshop_v428_guide_files() {
    return array(
        'heating-plan' => 'compressor.webp',
        'radiator-size' => 'drill-v46.webp',
        'cultivator-guide' => 'brushcutter.webp',
        'variation-stock' => 'tool-set.webp',
        'underfloor-heating' => 'oil-pump-v43-v46.webp',
        'garden-maintenance' => 'blower-v45-v46.webp',
        'heat-pump-guide' => 'welder-v43-v46.webp',
        'tractor-attachments' => 'generator.webp',
    );
}

function wpbbshop_v428_guide_image_url($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || get_post_meta($post_id, '_wpbbshop_v422_guide', true) !== '1') { return ''; }
    $key = (string) get_post_meta($post_id, '_wpbbshop_v422_guide_key', true);
    if (function_exists('wpbbshop_v426_blog_sources') && function_exists('wpbbshop_v426_cached_or_remote')) {
        $sources = wpbbshop_v426_blog_sources();
        if (isset($sources[$key])) {
            $real = wpbbshop_v426_cached_or_remote('blog', $key, 'large', 1800);
            if ($real) { return $real; }
        }
    }
    $map = wpbbshop_v428_guide_files();
    return isset($map[$key]) ? wpbbshop_v428_asset_url($map[$key]) : '';
}

/** Repair stale/duplicated demo guide keys left by older refreshes. */
function wpbbshop_v428_repair_guide_keys() {
    if (!current_user_can('manage_options') || !function_exists('wpbbshop_v422_post_specs')) { return; }
    if (get_option('wpbbshop_v428_media_repair') === WPBBSHOP_V428_VERSION) { return; }
    $specs = wpbbshop_v422_post_specs();
    $keys = array_keys($specs);
    $ids = get_posts(array(
        'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids',
        'meta_key' => '_wpbbshop_v422_guide', 'meta_value' => '1', 'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => true,
    ));
    $used = array('en' => array(), 'lv' => array());
    $position = array('en' => 0, 'lv' => 0);
    foreach ($ids as $id) {
        $id = (int) $id;
        $lang = (string) get_post_meta($id, '_wpbbshop_v422_lang', true);
        $lang = $lang === 'lv' ? 'lv' : 'en';
        $current = (string) get_post_meta($id, '_wpbbshop_v422_guide_key', true);
        $title = get_the_title($id);
        $matched = '';
        foreach ($specs as $key => $spec) {
            if (!empty($spec[$lang]['title']) && trim(wp_strip_all_tags($spec[$lang]['title'])) === trim(wp_strip_all_tags($title))) {
                $matched = $key; break;
            }
        }
        if ($matched === '' && in_array($current, $keys, true) && empty($used[$lang][$current])) { $matched = $current; }
        if ($matched === '' || !empty($used[$lang][$matched])) {
            foreach ($keys as $key) { if (empty($used[$lang][$key])) { $matched = $key; break; } }
        }
        if ($matched !== '') {
            update_post_meta($id, '_wpbbshop_v422_guide_key', $matched);
            update_post_meta($id, '_wpbbshop_v422_order', array_search($matched, $keys, true));
            $used[$lang][$matched] = 1;
        }
        $position[$lang]++;
    }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    update_option('wpbbshop_v428_media_repair', WPBBSHOP_V428_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v428_repair_guide_keys', 220);
add_action('after_switch_theme', 'wpbbshop_v428_repair_guide_keys', 220);

/** Last product-image filter wins over older generated demo art. */
add_filter('woocommerce_product_get_image', function($html, $product, $size, $attr, $placeholder) {
    $url = wpbbshop_v428_demo_product_asset($product);
    if (!$url) { return $html; }
    $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wpbb-v428-photo-product';
    if (is_array($attr) && !empty($attr['class'])) { $classes .= ' ' . sanitize_html_class($attr['class']); }
    $loading = is_array($attr) && !empty($attr['loading']) ? (string) $attr['loading'] : 'lazy';
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr($product->get_name()) . '" class="' . esc_attr($classes) . '" loading="' . esc_attr($loading) . '" decoding="async">';
}, PHP_INT_MAX, 5);

/** Also replace WordPress thumbnail HTML for bundled Product Guides. */
add_filter('post_thumbnail_html', function($html, $post_id, $post_thumbnail_id, $size, $attr) {
    $url = wpbbshop_v428_guide_image_url($post_id);
    if (!$url) { return $html; }
    $alt = get_the_title($post_id);
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" class="wp-post-image wpbb-v428-guide-photo" loading="lazy" decoding="async">';
}, PHP_INT_MAX, 5);

add_action('wp_enqueue_scripts', function() {
    $css = get_stylesheet_directory() . '/assets/css/v428-media-hardening.css';
    if (is_readable($css)) {
        wp_enqueue_style('wpbbshop-v428-media-hardening', get_stylesheet_directory_uri() . '/assets/css/v428-media-hardening.css', array('wpbbshop-v426-realistic-media'), (string) filemtime($css));
    }
}, PHP_INT_MAX);
