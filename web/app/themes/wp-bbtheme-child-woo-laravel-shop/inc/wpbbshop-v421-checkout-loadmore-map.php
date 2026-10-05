<?php
/**
 * WP BB Home & Garden v4.0.21
 * Checkout shipping geometry, balanced catalogue batches and contact maps.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V421_VERSION')) {
    define('WPBBSHOP_V421_VERSION', '4.0.21');
}

/**
 * Keep native Woo loops aligned with the smart-filter archive batch.
 * 20 is divisible by both the normal four-column and wide five-column grids,
 * so load-more requests add useful complete rows instead of one orphan card.
 */
add_filter('loop_shop_per_page', function ($per_page) {
    if (is_admin()) {
        return $per_page;
    }
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag())) {
        return 20;
    }
    return $per_page;
}, 9999);

/** Append a market-aware Google Map to both Contact language pages. */
function wpbbshop_v421_contact_map_html() {
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
    $lv = ($lang === 'lv');
    $address = function_exists('wpbbshop_v414_store_address')
        ? wpbbshop_v414_store_address()
        : '40 Brook Street, Northampton, NN1 2PE';

    $query = rawurlencode($address);
    $map_src = 'https://www.google.com/maps?q=' . $query . '&output=embed';
    $map_link = 'https://www.google.com/maps/search/?api=1&query=' . $query;

    ob_start();
    ?>
    <section class="wpbbshop-v421-contact-map" aria-label="<?php echo esc_attr($lv ? 'Atrašanās vieta kartē' : 'Store location map'); ?>">
        <div class="wpbbshop-v421-contact-map-copy">
            <span><?php echo esc_html($lv ? 'ATRAŠANĀS VIETA' : 'STORE LOCATION'); ?></span>
            <h2><?php echo esc_html($lv ? 'Atrodi mūs kartē' : 'Find us on the map'); ?></h2>
            <p><?php echo esc_html($address); ?></p>
            <a href="<?php echo esc_url($map_link); ?>" target="_blank" rel="noopener noreferrer">
                <?php echo esc_html($lv ? 'Atvērt Google Maps' : 'Open in Google Maps'); ?> →
            </a>
        </div>
        <div class="wpbbshop-v421-contact-map-frame">
            <iframe
                src="<?php echo esc_url($map_src); ?>"
                title="<?php echo esc_attr($lv ? 'WP BB Home & Garden atrašanās vieta' : 'WP BB Home & Garden location'); ?>"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        </div>
    </section>
    <?php
    return trim((string) ob_get_clean());
}

add_filter('the_content', function ($content) {
    if (is_admin() || !is_singular('page') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    global $post;
    if (!$post instanceof WP_Post || !in_array($post->post_name, array('contact', 'kontakti'), true)) {
        return $content;
    }
    if (strpos($content, 'wpbbshop-v421-contact-map') !== false) {
        return $content;
    }
    return $content . wpbbshop_v421_contact_map_html();
}, 60);

add_action('wp_enqueue_scripts', function () {
    $css = get_stylesheet_directory() . '/assets/css/v421-final.css';
    if (is_readable($css)) {
        wp_enqueue_style(
            'wpbbshop-v421-final',
            get_stylesheet_directory_uri() . '/assets/css/v421-final.css',
            array('wpbbshop-v420-final'),
            (string) filemtime($css)
        );
    }

    $js = get_stylesheet_directory() . '/assets/js/v421-final.js';
    if (is_readable($js) && function_exists('is_checkout') && is_checkout()) {
        wp_enqueue_script(
            'wpbbshop-v421-final',
            get_stylesheet_directory_uri() . '/assets/js/v421-final.js',
            array('jquery'),
            (string) filemtime($js),
            true
        );
    }
}, PHP_INT_MAX);

/** Last ownership after older checkout layers and stale merged CSS. */
add_action('wp_head', function () {
    if (is_admin() || !function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    ?>
    <style id="wpbbshop-v421-checkout-critical">
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.wpbbshop-v421-shipping-row>td[colspan="2"]{display:table-cell!important;width:100%!important;max-width:none!important;min-width:100%!important;padding-left:0!important;padding-right:0!important;text-align:left!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.wpbbshop-v421-shipping-row>td[colspan="2"]:before{display:none!important;content:none!important}
      html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.wpbbshop-v421-shipping-row .wpbbshop-shipping-full,html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.wpbbshop-v421-shipping-row #shipping_method,html body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.wpbbshop-v421-shipping-row .woocommerce-shipping-methods{width:100%!important;max-width:none!important;min-width:100%!important}
    </style>
    <?php
}, PHP_INT_MAX);
