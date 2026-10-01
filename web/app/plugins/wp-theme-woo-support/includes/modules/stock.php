<?php
defined( 'ABSPATH' ) || exit;

/**
 * Variable products: variation stock text.
 */
add_filter( 'woocommerce_available_variation', function( $data, $product, $variation ) {
	if ( ! $variation instanceof WC_Product_Variation ) {
		return $data;
	}

	if ( ! $variation->is_in_stock() ) {
		$data['availability_html'] = '<p class="stock out-of-stock">' .
			esc_html__( 'Out of stock', 'wp-theme-woo-support' ) .
			'</p>';
		return $data;
	}

	$qty = $variation->managing_stock() ? $variation->get_stock_quantity() : null;

	$data['availability_html'] = '<p class="stock in-stock">' .
		(
			null !== $qty && $qty > 0
				? sprintf( esc_html__( 'In stock (%d available)', 'wp-theme-woo-support' ), (int) $qty )
				: esc_html__( 'In stock', 'wp-theme-woo-support' )
		) .
		'</p>';

	return $data;
}, 10, 3 );

/**
 * Simple products: stock text on single product.
 */
add_filter( 'woocommerce_get_availability_text', function( $availability, $product ) {
	if ( ! $product || is_admin() ) {
		return $availability;
	}

	if ( $product->is_type( 'simple' ) ) {
		if ( ! $product->is_in_stock() ) {
			return __( 'Out of stock', 'wp-theme-woo-support' );
		}

		if ( $product->managing_stock() ) {
			$qty = $product->get_stock_quantity();

			if ( null !== $qty && $qty > 0 ) {
				return sprintf(
					__( 'In stock (%d available)', 'wp-theme-woo-support' ),
					(int) $qty
				);
			}
		}

		return __( 'In stock', 'wp-theme-woo-support' );
	}

	return $availability;
}, 10, 2 );
