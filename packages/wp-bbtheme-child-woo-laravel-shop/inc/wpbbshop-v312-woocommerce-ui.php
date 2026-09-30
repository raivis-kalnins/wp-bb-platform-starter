<?php
/**
 * WP BB Home & Garden 3.0.12 — WooCommerce cart / checkout / account UI helpers.
 * Keeps checkout/cart business logic in WooCommerce; this layer only supplies
 * safe presentation helpers and bundled demo image fallbacks.
 */
defined('ABSPATH') || exit;

function wpbbshop_v312_is_en() {
    if (function_exists('pll_current_language')) {
        return pll_current_language('slug') === 'en';
    }
    return false;
}

function wpbbshop_v312_t($lv, $en) {
    return wpbbshop_v312_is_en() ? $en : $lv;
}

function wpbbshop_v312_product_image_url($product, $size = 'woocommerce_thumbnail') {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $image_id = $product->get_image_id();
    if ($image_id) {
        $url = wp_get_attachment_image_url($image_id, $size);
        if ($url) { return $url; }
    }
    if (function_exists('wpbbshop_v305_demo_image_url')) {
        return wpbbshop_v305_demo_image_url($product);
    }
    return function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src($size) : '';
}

function wpbbshop_v312_product_image_html($product, $class = '', $size = 'woocommerce_thumbnail') {
    $url = wpbbshop_v312_product_image_url($product, $size);
    if (!$url) { return ''; }
    return sprintf(
        '<img src="%s" alt="%s" class="%s" loading="lazy" decoding="async">',
        esc_url($url),
        esc_attr($product->get_name()),
        esc_attr($class)
    );
}

function wpbbshop_v312_price_parts($product, $qty = 1) {
    $qty = max(1, (int) $qty);
    $current = (float) wc_get_price_to_display($product, array('qty' => $qty));
    $regular = $product->get_regular_price();
    $regular_value = ($regular !== '') ? (float) $regular : 0.0;
    if ($regular_value > 0) {
        // Preserve WooCommerce display-tax behaviour by scaling the displayed unit price.
        $display_unit_regular = (float) wc_get_price_to_display($product, array('price' => $regular_value, 'qty' => 1));
        $regular_value = $display_unit_regular * $qty;
    }
    return array(
        'current' => wc_price($current),
        'regular' => ($product->is_on_sale() && $regular_value > $current) ? wc_price($regular_value) : '',
    );
}

// Make demo product thumbnails reliable in carts/widgets even when an old attachment was removed.
add_filter('woocommerce_cart_item_thumbnail', function($html, $cart_item, $cart_item_key) {
    if (empty($cart_item['data']) || !is_a($cart_item['data'], 'WC_Product')) { return $html; }
    $product = $cart_item['data'];
    if ($product->get_image_id()) { return $html; }
    $fallback = wpbbshop_v312_product_image_html($product, 'llg-fallback-product-image');
    return $fallback ?: $html;
}, 50, 3);

// Remove accidental paragraph spacing from Woo wrappers on the three transaction screens only.
add_filter('the_content', function($content) {
    if (!function_exists('is_cart')) { return $content; }
    if (!(is_cart() || is_checkout() || is_account_page())) { return $content; }
    $content = preg_replace('#<p>(?:\s|&nbsp;|<br\s*/?>)*</p>#i', '', $content);
    return is_string($content) ? $content : '';
}, 1000);
