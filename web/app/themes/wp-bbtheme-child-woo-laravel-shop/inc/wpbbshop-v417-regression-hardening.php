<?php
/**
 * WP BB Home & Garden v4.0.17 — regression hardening.
 *
 * Keeps every previous storefront/UK/language repair, but restores the compact
 * four-column home department layout and full-width cart/checkout shipping UI.
 * It also removes the two later broad DOM repair scripts that could repeatedly
 * touch WooCommerce fragments and make cart/checkout updates feel slow.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V417_VERSION')) {
    define('WPBBSHOP_V417_VERSION', '4.0.17');
}

add_action('wp_enqueue_scripts', function () {
    /* R5 and v4.0.16 both rewrite the same homepage geometry after load.
     * v4.0.17 owns that final geometry in one place instead. */
    wp_dequeue_script('wpbbshop-v414-r5-home-grid');
    wp_deregister_script('wpbbshop-v414-r5-home-grid');
    wp_dequeue_script('wpbbshop-v415-uk-commerce-language');
    wp_deregister_script('wpbbshop-v415-uk-commerce-language');
    wp_dequeue_script('wpbbshop-v416-final-fixes');
    wp_deregister_script('wpbbshop-v416-final-fixes');

    /* The v4.0.14 script owns home/compare UI and does not need to observe
     * cart or checkout fragment mutations. Avoid that observer on commerce
     * forms to keep recalculation fast. */
    if ((function_exists('is_cart') && is_cart()) || (function_exists('is_checkout') && is_checkout())) {
        wp_dequeue_script('wpbbshop-v414-final-ui');
        wp_deregister_script('wpbbshop-v414-final-ui');
    }

    $css = get_stylesheet_directory() . '/assets/css/v417-regression-hardening.css';
    $js  = get_stylesheet_directory() . '/assets/js/v417-regression-hardening.js';

    if (is_readable($css)) {
        wp_enqueue_style(
            'wpbbshop-v417-regression-hardening',
            get_stylesheet_directory_uri() . '/assets/css/v417-regression-hardening.css',
            array(),
            (string) filemtime($css)
        );
    }
    if (is_readable($js)) {
        wp_enqueue_script(
            'wpbbshop-v417-regression-hardening',
            get_stylesheet_directory_uri() . '/assets/js/v417-regression-hardening.js',
            array('jquery'),
            (string) filemtime($js),
            true
        );
    }
}, PHP_INT_MAX);

/* Print the critical geometry after normal enqueued styles too. This makes the
 * fix resilient when an older cache serves v410-v416 CSS out of order. */
add_action('wp_head', function () {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v417-critical">
    html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid{display:flex!important;flex-flow:row wrap!important;gap:6px!important;width:100%!important;height:auto!important;min-height:0!important;align-content:flex-start!important;justify-content:flex-start!important}
    html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid>.wpbb-v400-dept{flex:0 0 calc((100% - 18px)/4)!important;width:calc((100% - 18px)/4)!important;max-width:calc((100% - 18px)/4)!important;grid-column:auto!important;grid-row:auto!important;margin:0!important;order:0!important;transform:none!important}
    html body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping,html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping{display:grid!important;grid-template-columns:minmax(0,1fr)!important;width:100%!important}
    html body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping>th,html body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping>td,html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping>th,html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping>td{display:block!important;width:100%!important;max-width:none!important;min-width:0!important;text-align:left!important}
    html body.woocommerce-cart #shipping_method,html body.woocommerce-checkout #shipping_method{width:100%!important;max-width:none!important;min-width:100%!important}
    @media(max-width:900px){html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid>.wpbb-v400-dept{flex-basis:calc((100% - 6px)/2)!important;width:calc((100% - 6px)/2)!important;max-width:calc((100% - 6px)/2)!important}}
    @media(max-width:520px){html body.wpbbshop-theme .wpbb-v400-departments .wpbb-v400-dept-grid>.wpbb-v400-dept{flex-basis:100%!important;width:100%!important;max-width:100%!important}}
    </style>
    <?php
}, PHP_INT_MAX);

/* Keep the Home & Garden admin success notice tidy and make the native close
 * button easy to hit without overlapping the message text. */
add_action('admin_head', function () {
    if (!isset($_GET['page']) || strpos((string) wp_unslash($_GET['page']), 'wpbb') === false) { return; }
    ?>
    <style id="wpbbshop-v417-admin-notice">
      .wpbb-v400-admin .notice.is-dismissible{position:relative!important;padding:11px 48px 11px 14px!important;min-height:42px!important;box-sizing:border-box!important}
      .wpbb-v400-admin .notice.is-dismissible p{margin:0!important;line-height:20px!important}
      .wpbb-v400-admin .notice-dismiss{top:50%!important;right:7px!important;width:32px!important;height:32px!important;margin:0!important;padding:0!important;transform:translateY(-50%)!important;border-radius:7px!important}
      .wpbb-v400-admin .notice-dismiss:hover{background:#eef6f0!important}
      .wpbb-v400-admin .notice-dismiss:before{font-size:18px!important;width:20px!important;height:20px!important;line-height:20px!important;color:#173b2a!important}
    </style>
    <?php
}, PHP_INT_MAX);
