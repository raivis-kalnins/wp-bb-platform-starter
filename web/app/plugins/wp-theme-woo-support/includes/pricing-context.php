<?php
/** Request-local object state. Never writes calculated customer prices to products. */
defined('ABSPATH') || exit;
final class IWS_Pricing_Context {
    private static $state;
    private static function map() { if (!self::$state) self::$state = new WeakMap(); return self::$state; }
    public static function base($product) {
        $map = self::map();
        if (!isset($map[$product])) $map[$product] = ['base' => $product->get_price('edit'), 'final' => null];
        return $map[$product]['base'];
    }
    public static function is_final($product) {
        $map = self::map();
        return isset($map[$product]) && $map[$product]['final'] !== null && (string)$map[$product]['final'] === (string)$product->get_price('edit');
    }
    public static function set($product, $price) {
        self::base($product);
        $product->set_price($price);
        $map = self::map(); $state = $map[$product]; $state['final'] = $product->get_price('edit'); $map[$product] = $state;
    }
}

/** WC's simple-product sale HTML otherwise uses the unadjusted sale field, not get_price(). */
function iws_customer_price_html($html, $product) {
    if ((is_admin() && !wp_doing_ajax()) || !$product instanceof WC_Product || $product->is_type('variable') || $product->is_type('grouped')) return $html;
    $trade = function_exists('iws_b2b_is_enabled') && iws_b2b_is_enabled() && iws_b2b_current_user_is_wholesale();
    $rules = class_exists('IWS_Woo_Discount_Rules') && 'yes' === IWS_Woo_Discount_Rules::get_settings()['enabled'];
    if (!$trade && !$rules) return $html;
    $raw = $product->get_price('edit'); $price = $product->get_price();
    if ('' === $raw || '' === $price || !is_numeric($price) || (float)$raw === (float)$price) return $html;
    $regular = $product->get_regular_price('edit');
    $base = max((float)$raw, (float)$regular);
    $current_display = wc_get_price_to_display($product, ['price'=>(float)$price]);
    $base_display = wc_get_price_to_display($product, ['price'=>$base]);
    return ($base > (float)$price ? wc_format_sale_price($base_display, $current_display) : wc_price($current_display)) . $product->get_price_suffix();
}
add_filter('woocommerce_get_price_html', 'iws_customer_price_html', 15, 2);
