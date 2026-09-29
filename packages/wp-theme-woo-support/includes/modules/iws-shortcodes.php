<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'wpbb_child_woo_get_field' ) ) {
    function wpbb_child_woo_get_field( $key, $post_id = null, $default = '' ) {
        if ( function_exists( 'get_field' ) ) {
            $value = get_field( $key, $post_id ?: get_the_ID() );
            return ( null !== $value && false !== $value && '' !== $value ) ? $value : $default;
        }
        return $default;
    }
}

if ( ! function_exists( 'shortcode_product_reviews' ) ) {
    function shortcode_product_reviews() {
        $enabled = wpbb_child_woo_get_field( 'product_reviews', get_the_ID(), true );
        if ( ! $enabled ) {
            return '';
        }
        return do_blocks( '<!-- wp:woocommerce/product-reviews --><div class="wp-block-woocommerce-product-reviews"><!-- wp:woocommerce/product-reviews-title /--><!-- wp:woocommerce/product-review-template /--><!-- wp:woocommerce/product-reviews-pagination /--><!-- wp:woocommerce/product-review-form /--></div><!-- /wp:woocommerce/product-reviews -->' );
    }
    add_shortcode( 'product_reviews', 'shortcode_product_reviews' );
}

foreach ( array(
    wp_theme_woo_support_path( 'templates/shortcodes/product_tabs.php' ),
    wp_theme_woo_support_path( 'templates/shortcodes/product_faq.php' ),
    wp_theme_woo_support_path( 'templates/shortcodes/woo_cat_bottom_desc.php' ),
) as $wpbb_child_woo_shortcode_file ) {
    if ( file_exists( $wpbb_child_woo_shortcode_file ) ) {
        require_once $wpbb_child_woo_shortcode_file;
    }
}
