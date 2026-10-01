<?php
defined( 'ABSPATH' ) || exit;

/**
 * Helpers
 */
function theme_has_cart_checkout_content() {
	if ( ! is_singular() ) {
		return false;
	}

	global $post;

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$content = (string) $post->post_content;

	return has_shortcode( $content, 'woocommerce_cart' )
		|| has_shortcode( $content, 'woocommerce_checkout' )
		|| has_block( 'woocommerce/cart', $post )
		|| has_block( 'woocommerce/checkout', $post );
}

function theme_is_woo_cart_checkout_screen() {
	return is_cart()
		|| is_checkout()
		|| is_page( array( 'cart', 'basket', 'checkout' ) )
		|| theme_has_cart_checkout_content();
}

function theme_is_woo_screen() {
	return is_woocommerce() || theme_is_woo_cart_checkout_screen() || is_account_page() || is_product();
}

/**
 * Disable classic Woo CSS except cart/checkout pages, including the custom Basket page.
 */
add_filter( 'woocommerce_enqueue_styles', function( $styles ) {
	return theme_is_woo_cart_checkout_screen() ? $styles : array();
} );

/**
 * Some shortcode based cart/checkout pages do not trigger Woo's normal screen
 * detection when the URL/page slug is custom. Keep the native Woo legacy CSS
 * present so the basket and checkout grids keep the old styling.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! theme_is_woo_cart_checkout_screen() ) {
		return;
	}

	foreach ( array( 'woocommerce-layout', 'woocommerce-general', 'woocommerce-smallscreen' ) as $handle ) {
		if ( wp_style_is( $handle, 'registered' ) && ! wp_style_is( $handle, 'enqueued' ) ) {
			wp_enqueue_style( $handle );
		}
	}
}, 15 );

/**
 * Load block CSS only where Woo blocks exist.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! is_singular() ) {
		return;
	}

	global $post;

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$has_woo_blocks =
		has_block( 'woocommerce/all-products', $post ) ||
		has_block( 'woocommerce/product-collection', $post ) ||
		has_block( 'woocommerce/product-template', $post ) ||
		has_block( 'woocommerce/mini-cart', $post );

	if ( $has_woo_blocks ) {
		$theme_wc_blocks_css = wp_theme_woo_support_path( 'assets/css/woocommerce-blocks.min.css' );

		if ( file_exists( $theme_wc_blocks_css ) ) {
			wp_enqueue_style(
				'theme-woocommerce-blocks',
				wp_theme_woo_support_url( 'assets/css/woocommerce-blocks.min.css' ),
				array(),
				filemtime( $theme_wc_blocks_css )
			);
		}
	}
}, 20 );

/**
 * Load single-product JS only on product pages.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! is_product() ) {
		return;
	}

	wp_enqueue_script(
		'theme-woo-single',
		wp_theme_woo_support_url( 'assets/js/woo-single.min.js' ),
		array( 'jquery', 'wc-add-to-cart-variation' ),
		WP_THEME_WOO_SUPPORT_VERSION,
		true
	);

	wp_localize_script(
		'theme-woo-single',
		'themeWooSingle',
		array(
			'currencySymbol' => get_woocommerce_currency_symbol(),
		)
	);
}, 30 );


/**
 * Front-end guard for hidden/zero prices in classic Woo loops, single product totals,
 * and WooCommerce product-price blocks.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! theme_is_woo_screen() ) {
		return;
	}
	wp_register_style( 'iws-price-visibility', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-price-visibility' );
	wp_add_inline_style(
		'iws-price-visibility',
		'#product_total_price[hidden],#product_total_price.is-hidden-price,.price:empty,.woocommerce-Price-amount:empty,.wp-block-woocommerce-product-price:empty,[data-block-name="woocommerce/product-price"]:empty,.wc-block-components-product-price:empty{display:none!important}'
	);

	wp_register_script( 'iws-price-visibility', '', array( 'jquery' ), WP_THEME_WOO_SUPPORT_VERSION, true );
	wp_enqueue_script( 'iws-price-visibility' );
	wp_add_inline_script(
		'iws-price-visibility',
		'(function($){function isZeroPriceText(text){text=(text||"").replace(/\u00a0/g," ").trim();if(!text){return true;}var cleaned=text.replace(/sale|from|each|total|inc\.? vat|excl\.? vat|including vat|excluding vat/gi,"").replace(/[£$€¥₹,\s]/g,"").trim();if(!cleaned){return true;}return /^0+(?:[.,]0+)?$/.test(cleaned);}function hideBadPrice($el){if(!$el.length){return;}var text=$el.text();if(isZeroPriceText(text)){$el.attr("hidden","hidden").addClass("is-hidden-price").hide();}}function scanPrices(){["#product_total_price",".woocommerce ul.products li.product .price",".products .product .price",".wp-block-woocommerce-product-price","[data-block-name=\\"woocommerce/product-price\\"]",".wc-block-components-product-price"].forEach(function(sel){$(sel).each(function(){hideBadPrice($(this));});});}$(scanPrices);$(document).on("wc_fragments_refreshed updated_wc_div updated_cart_totals found_variation show_variation hide_variation",function(){setTimeout(scanPrices,30);});})(jQuery);'
	);
}, 35 );


/**
 * Shop loop product description spacing.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! theme_is_woo_screen() ) {
		return;
	}
	wp_register_style( 'iws-shop-loop-spacing-fixes', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-shop-loop-spacing-fixes' );
	wp_add_inline_style(
		'iws-shop-loop-spacing-fixes',
		'.wc-block-product-template .product .prod-desc,.products .product .prod-desc{margin-bottom:74px!important}'
	);
}, 45 );

/**
 * Dequeue selected Woo scripts on non-Woo pages.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( theme_is_woo_screen() ) {
		return;
	}

	wp_dequeue_script( 'wc-single-product' );
	wp_dequeue_script( 'wc-price-slider' );
	wp_dequeue_script( 'wc-cart-fragments' );
}, 99 );

/**
 * Disable cart fragments where not needed.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( theme_is_woo_cart_checkout_screen() || is_product() ) {
		return;
	}

	wp_dequeue_script( 'wc-cart-fragments' );
}, 100 );
/**
 * Basket page mini-cart button alignment fix.
 * The basket page header cart trigger is positioned differently from the rest
 * of the site, so do not vertically translate it there.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( ! is_page( 'basket' ) ) {
		return;
	}

	wp_register_style( 'iws-basket-mini-cart-button-fix', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-basket-mini-cart-button-fix' );
	wp_add_inline_style(
		'iws-basket-mini-cart-button-fix',
		'body.page .wc-block-mini-cart__button{transform:unset!important;}'
	);
}, 46 );
