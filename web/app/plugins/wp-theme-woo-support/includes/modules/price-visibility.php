<?php
defined( 'ABSPATH' ) || exit;

/**
 * Price visibility helpers.
 * Hide product prices when a simple product has no usable price or a zero price,
 * and hide variable product prices when every variation is missing a price or is zero.
 */
function iws_product_has_visible_price( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	if ( $product->is_type( 'variable' ) ) {
		$variation_prices = $product->get_variation_prices( true );
		$prices           = isset( $variation_prices['price'] ) && is_array( $variation_prices['price'] ) ? $variation_prices['price'] : array();

		foreach ( $prices as $price ) {
			if ( '' !== $price && null !== $price && (float) $price > 0 ) {
				return true;
			}
		}

		return false;
	}

	if ( $product->is_type( 'grouped' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( iws_product_has_visible_price( $child ) ) {
				return true;
			}
		}

		return false;
	}

	$price = $product->get_price();

	return '' !== $price && null !== $price && (float) $price > 0;
}

/**
 * Return the best starting unit price for the single-product total price widget.
 */
function iws_get_product_visible_unit_price( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return 0.0;
	}

	if ( $product->is_type( 'variable' ) ) {
		$variation_prices = $product->get_variation_prices( true );
		$prices           = isset( $variation_prices['price'] ) && is_array( $variation_prices['price'] ) ? $variation_prices['price'] : array();
		$valid_prices      = array();

		foreach ( $prices as $price ) {
			if ( '' !== $price && null !== $price && (float) $price > 0 ) {
				$valid_prices[] = (float) $price;
			}
		}

		return $valid_prices ? min( $valid_prices ) : 0.0;
	}

	$price = wc_get_price_excluding_tax( $product );

	return ( '' !== $price && null !== $price && (float) $price > 0 ) ? (float) $price : 0.0;
}

/**
 * Hide classic loop/single price HTML when price is empty or zero.
 * WooCommerce product-price blocks also use WooCommerce price HTML internally,
 * so this prevents empty/zero prices from rendering in block loops as well.
 */
add_filter( 'woocommerce_get_price_html', function( $price_html, $product ) {
	if ( ! iws_product_has_visible_price( $product ) ) {
		return '';
	}

	return $price_html;
}, 999, 2 );

/**
 * Hide zero-price variation HTML in variation JSON used on product pages.
 */
add_filter( 'woocommerce_available_variation', function( $data, $product, $variation ) {
	if ( $variation instanceof WC_Product_Variation && ! iws_product_has_visible_price( $variation ) ) {
		$data['price_html']    = '';
		$data['display_price'] = 0;
	}

	return $data;
}, 999, 3 );

/**
 * Remove left-over empty Woo product-price block wrappers.
 */
add_filter( 'render_block', function( $block_content, $block ) {
	$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

	if ( 'woocommerce/product-price' !== $block_name ) {
		return $block_content;
	}

	$text = trim( wp_strip_all_tags( $block_content ) );

	if ( '' === $text || preg_match( '/^(?:[[:space:]]|&nbsp;|0|0[.,]0+|£0(?:[.,]0{1,2})?)$/u', $text ) ) {
		return '';
	}

	return $block_content;
}, 999, 2 );
