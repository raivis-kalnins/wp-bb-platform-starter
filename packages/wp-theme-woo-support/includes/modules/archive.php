<?php
defined( 'ABSPATH' ) || exit;


const IWS_PRODUCT_CARD_SKU_OPTION = 'iws_show_sku_on_product_cards';

/**
 * Whether SKU badges should be shown on product cards.
 *
 * Disabled by default. Can be enabled in WooCommerce > Settings > Products > Advanced.
 */
function iws_show_sku_on_product_cards() {
	return 'yes' === get_option( IWS_PRODUCT_CARD_SKU_OPTION, 'no' );
}

add_filter( 'woocommerce_get_settings_products', function( $settings, $current_section ) {
	if ( 'advanced' !== $current_section ) {
		return $settings;
	}

	$insert = array(
		array(
			'title' => __( 'Product Cards', 'wp-theme-woo-support' ),
			'type'  => 'title',
			'desc'  => __( 'Controls small product-card details shown on shop and product taxonomy archive pages.', 'wp-theme-woo-support' ),
			'id'    => 'iws_product_card_options',
		),
		array(
			'title'   => __( 'Show SKU on product cards', 'wp-theme-woo-support' ),
			'desc'    => __( 'Show SKU badges on shop and product category/tag/brand archive product cards.', 'wp-theme-woo-support' ),
			'id'      => IWS_PRODUCT_CARD_SKU_OPTION,
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array( 'type' => 'sectionend', 'id' => 'iws_product_card_options' ),
	);

	$position = count( $settings );
	foreach ( $settings as $index => $setting ) {
		if ( isset( $setting['type'] ) && 'sectionend' === $setting['type'] ) {
			$position = $index;
			break;
		}
	}

	array_splice( $settings, $position, 0, $insert );

	return $settings;
}, 20, 2 );

/**
 * Archive button text.
 */
add_filter( 'woocommerce_product_add_to_cart_text', function() {
	return __( 'Buy', 'wp-theme-woo-support' );
} );

/**
 * Add excerpt under archive items.
 */
add_action( 'woocommerce_after_shop_loop_item_title', function() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	/*
	 * Product cards should always have a short text snippet where content exists.
	 * Some variable products do not have a WooCommerce short description filled in,
	 * but do have the main admin description. Fall back to that so related cards
	 * do not render as image + title only.
	 */
	$excerpt = $product->get_short_description();

	if ( ! $excerpt ) {
		$excerpt = get_post_field( 'post_content', $product->get_id() );
	}

	$excerpt = trim( wp_strip_all_tags( strip_shortcodes( (string) $excerpt ) ) );

	if ( $excerpt ) {
		echo '<p class="prod-desc">' . esc_html( wp_trim_words( $excerpt, 20 ) ) . '</p>';
	}
}, 5 );


/**
 * SKU badge for the main shop/products archive cards.
 *
 * Taxonomy archives use their own controlled card markup, so this is limited
 * to the main products archive to avoid conflicting with the taxonomy badge.
 */
function iws_is_main_shop_product_archive() {
	if ( is_tax() ) {
		return false;
	}

	if ( is_shop() || is_post_type_archive( 'product' ) ) {
		return true;
	}

	/*
	 * Shop load-more/filter AJAX renders products through admin-ajax.php,
	 * where is_shop() is false. The filter renderer sets this flag so
	 * AJAX-loaded shop products receive the same SKU badge as the first page.
	 */
	return ! empty( $GLOBALS['iws_rendering_shop_archive_products'] );
}

function iws_shop_loop_product_sku_badge() {
	if ( ! iws_show_sku_on_product_cards() || ! iws_is_main_shop_product_archive() ) {
		return;
	}

	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$sku = $product->get_sku();

	if ( '' === (string) $sku ) {
		return;
	}

	echo '<span class="iws-shop-product-sku sku_wrapper" title="' . esc_attr( sprintf( __( 'SKU: %s', 'wp-theme-woo-support' ), $sku ) ) . '"><span class="iws-shop-product-sku-label">' . esc_html__( 'SKU:', 'wp-theme-woo-support' ) . '</span> <span class="sku">' . esc_html( $sku ) . '</span></span>';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'iws_shop_loop_product_sku_badge', 11 );

add_action( 'wp_enqueue_scripts', function() {
	if ( ! iws_show_sku_on_product_cards() || ! iws_is_main_shop_product_archive() ) {
		return;
	}

	wp_register_style( 'iws-shop-product-sku-badge', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-shop-product-sku-badge' );
	wp_add_inline_style(
		'iws-shop-product-sku-badge',
		'body.post-type-archive-product.woocommerce ul.products li.product,body.post-type-archive-product.woocommerce .products .product,body.post-type-archive-product.woocommerce .iws-products-grid li.product{position:relative!important}body.post-type-archive-product.woocommerce ul.products li.product>a.woocommerce-LoopProduct-link,body.post-type-archive-product.woocommerce .products .product>a.woocommerce-LoopProduct-link,body.post-type-archive-product.woocommerce .iws-products-grid li.product>a.woocommerce-LoopProduct-link{position:relative!important}body.post-type-archive-product.woocommerce ul.products li.product .iws-shop-product-sku,body.post-type-archive-product.woocommerce .products .product .iws-shop-product-sku,body.post-type-archive-product.woocommerce .iws-products-grid li.product .iws-shop-product-sku{position:absolute!important;top:14px!important;left:14px!important;right:auto!important;z-index:4!important;display:inline-flex!important;align-items:center!important;gap:3px!important;width:max-content!important;max-width:calc(100% - 78px)!important;min-width:0!important;margin:0!important;padding:3px 7px!important;border-radius:2px!important;background:#fff!important;color:#111!important;font-size:12px!important;line-height:1.2!important;font-weight:700!important;text-transform:none!important;letter-spacing:0!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;box-shadow:0 1px 3px rgba(0,0,0,.12)!important}body.post-type-archive-product.woocommerce ul.products li.product .iws-shop-product-sku .iws-shop-product-sku-label,body.post-type-archive-product.woocommerce ul.products li.product .iws-shop-product-sku .sku,body.post-type-archive-product.woocommerce .products .product .iws-shop-product-sku .iws-shop-product-sku-label,body.post-type-archive-product.woocommerce .products .product .iws-shop-product-sku .sku,body.post-type-archive-product.woocommerce .iws-products-grid li.product .iws-shop-product-sku .iws-shop-product-sku-label,body.post-type-archive-product.woocommerce .iws-products-grid li.product .iws-shop-product-sku .sku{color:#111!important;font-size:12px!important;line-height:1.2!important;font-weight:700!important}.iws-shop-product-sku .iws-shop-product-sku-label{flex:0 0 auto!important}.iws-shop-product-sku .sku{display:inline-block!important;min-width:0!important;max-width:100%!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;vertical-align:bottom!important}@media(max-width:767.98px){body.post-type-archive-product.woocommerce ul.products li.product .iws-shop-product-sku,body.post-type-archive-product.woocommerce .products .product .iws-shop-product-sku,body.post-type-archive-product.woocommerce .iws-products-grid li.product .iws-shop-product-sku{top:12px!important;left:12px!important;max-width:calc(100% - 72px)!important;padding:3px 6px!important}}@media(max-width:420px){body.post-type-archive-product.woocommerce ul.products li.product .iws-shop-product-sku,body.post-type-archive-product.woocommerce .products .product .iws-shop-product-sku,body.post-type-archive-product.woocommerce .iws-products-grid li.product .iws-shop-product-sku{top:10px!important;left:10px!important;max-width:calc(100% - 68px)!important;font-size:11px!important;padding:3px 5px!important}body.post-type-archive-product.woocommerce ul.products li.product .iws-shop-product-sku .iws-shop-product-sku-label,body.post-type-archive-product.woocommerce ul.products li.product .iws-shop-product-sku .sku,body.post-type-archive-product.woocommerce .products .product .iws-shop-product-sku .iws-shop-product-sku-label,body.post-type-archive-product.woocommerce .products .product .iws-shop-product-sku .sku,body.post-type-archive-product.woocommerce .iws-products-grid li.product .iws-shop-product-sku .iws-shop-product-sku-label,body.post-type-archive-product.woocommerce .iws-products-grid li.product .iws-shop-product-sku .sku{font-size:11px!important}}'
	);
} );

/**
 * Products per page.
 */
add_filter( 'loop_shop_per_page', function() {
	return is_shop() ? 8 : 12;
}, 20 );

/**
 * Keep shop and product taxonomy archives to two columns from LG down.
 *
 * The theme has multiple render paths (classic Woo loops, product blocks,
 * filter/load-more AJAX, and the custom taxonomy archive). This late inline
 * CSS targets all archive grid variants so cards have enough room for content
 * on laptop/tablet widths where three columns are too tight.
 */
function iws_is_product_archive_grid_page() {
	if ( is_shop() || is_post_type_archive( 'product' ) ) {
		return true;
	}

	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		return true;
	}

	if ( is_tax() ) {
		$term = get_queried_object();
		$taxonomy = ( $term && ! empty( $term->taxonomy ) ) ? (string) $term->taxonomy : '';

		return in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_brand', 'pa_brand' ), true ) || 0 === strpos( $taxonomy, 'pa_' );
	}

	return false;
}

add_action( 'wp_enqueue_scripts', function() {
	if ( ! iws_is_product_archive_grid_page() ) {
		return;
	}

	wp_register_style( 'iws-product-archive-responsive-grid', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-product-archive-responsive-grid' );
	wp_add_inline_style(
		'iws-product-archive-responsive-grid',
		'@media(max-width:1199.98px){body.post-type-archive-product.woocommerce ul.products,body.post-type-archive-product.woocommerce .wc-block-product-template,body.post-type-archive-product.woocommerce .iws-products-grid,body.tax-product_cat.woocommerce ul.products,body.tax-product_tag.woocommerce ul.products,body.tax-product_brand.woocommerce ul.products,body.tax-pa_brand.woocommerce ul.products,body[class*="tax-pa_"].woocommerce ul.products,body.tax-product_cat.woocommerce .wc-block-product-template,body.tax-product_tag.woocommerce .wc-block-product-template,body.tax-product_brand.woocommerce .wc-block-product-template,body.tax-pa_brand.woocommerce .wc-block-product-template,body[class*="tax-pa_"].woocommerce .wc-block-product-template,.iws-product-taxonomy-archive ul.products,.iws-product-taxonomy-archive .iws-taxonomy-products-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:20px!important;column-gap:20px!important;row-gap:24px!important}.woocommerce-page.post-type-archive-product ul.products[class*=columns-] li.product,.woocommerce.post-type-archive-product ul.products[class*=columns-] li.product,body.post-type-archive-product.woocommerce ul.products li.product,body.post-type-archive-product.woocommerce .wc-block-product-template .product,body.post-type-archive-product.woocommerce .iws-products-grid li.product,body.tax-product_cat.woocommerce ul.products li.product,body.tax-product_tag.woocommerce ul.products li.product,body.tax-product_brand.woocommerce ul.products li.product,body.tax-pa_brand.woocommerce ul.products li.product,body[class*="tax-pa_"].woocommerce ul.products li.product,body.tax-product_cat.woocommerce .wc-block-product-template .product,body.tax-product_tag.woocommerce .wc-block-product-template .product,body.tax-product_brand.woocommerce .wc-block-product-template .product,body.tax-pa_brand.woocommerce .wc-block-product-template .product,body[class*="tax-pa_"].woocommerce .wc-block-product-template .product,.iws-product-taxonomy-archive ul.products li.product,.iws-product-taxonomy-archive .iws-taxonomy-products-grid li.product{width:100%!important;max-width:none!important;min-width:0!important;float:none!important;clear:none!important;margin:0!important;box-sizing:border-box!important}}@media(max-width:767.98px){body.post-type-archive-product.woocommerce ul.products,body.post-type-archive-product.woocommerce .wc-block-product-template,body.post-type-archive-product.woocommerce .iws-products-grid,body.tax-product_cat.woocommerce ul.products,body.tax-product_tag.woocommerce ul.products,body.tax-product_brand.woocommerce ul.products,body.tax-pa_brand.woocommerce ul.products,body[class*="tax-pa_"].woocommerce ul.products,body.tax-product_cat.woocommerce .wc-block-product-template,body.tax-product_tag.woocommerce .wc-block-product-template,body.tax-product_brand.woocommerce .wc-block-product-template,body.tax-pa_brand.woocommerce .wc-block-product-template,body[class*="tax-pa_"].woocommerce .wc-block-product-template,.iws-product-taxonomy-archive ul.products,.iws-product-taxonomy-archive .iws-taxonomy-products-grid{grid-template-columns:auto!important;}}'
	);
}, 99 );



add_action( 'wp_enqueue_scripts', function() {
	if ( iws_show_sku_on_product_cards() || ! iws_is_product_archive_grid_page() ) {
		return;
	}

	wp_register_style( 'iws-product-card-sku-hidden', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-product-card-sku-hidden' );
	wp_add_inline_style(
		'iws-product-card-sku-hidden',
		'.iws-product-card-sku,.iws-shop-product-sku{display:none!important}'
	);
}, 100 );
