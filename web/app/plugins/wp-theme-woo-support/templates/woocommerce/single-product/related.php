<?php
/**
 * Single Product Related
 * @version 10.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize an ACF product relationship/post object field into product IDs.
 * Supports IDs, WP_Post objects and WC_Product objects.
 */
if ( ! function_exists( 'iws_related_template_normalize_product_ids' ) ) {
	function iws_related_template_normalize_product_ids( $products ) {
		if ( empty( $products ) ) {
			return array();
		}

		if ( ! is_array( $products ) ) {
			$products = array( $products );
		}

		$product_ids = array();

		foreach ( $products as $item ) {
			$product_id = 0;

			if ( $item instanceof WC_Product ) {
				$product_id = $item->get_id();
			} elseif ( is_object( $item ) && isset( $item->ID ) ) {
				$product_id = $item->ID;
			} elseif ( is_array( $item ) && isset( $item['ID'] ) ) {
				$product_id = $item['ID'];
			} else {
				$product_id = $item;
			}

			$product_id = absint( $product_id );

			if ( $product_id && 'product' === get_post_type( $product_id ) ) {
				$product_ids[] = $product_id;
			}
		}

		return array_values( array_unique( array_filter( $product_ids ) ) );
	}
}

/**
 * The Recommended products control has existed with different values/labels.
 * Treat any custom/manual value as custom, but keep empty/auto/off values automatic.
 */
if ( ! function_exists( 'iws_related_template_is_custom_mode' ) ) {
	function iws_related_template_is_custom_mode( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_array( $value ) ) {
			$value = $value['value'] ?? $value['label'] ?? reset( $value );
		}

		$value = strtolower( trim( (string) $value ) );
		$value = str_replace( array( '-', '_' ), ' ', $value );

		if ( '' === $value || in_array( $value, array( '0', 'false', 'no', 'off', 'auto', 'automatic' ), true ) ) {
			return false;
		}

		return in_array( $value, array( '1', 'true', 'yes', 'on', 'custom', 'custom opt', 'manual', 'acf recommended products' ), true );
	}
}

/**
 * Get custom recommended product IDs from the current product.
 */
if ( ! function_exists( 'iws_related_template_get_custom_product_ids' ) ) {
	function iws_related_template_get_custom_product_ids( $product_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}

		$mode_field_names = array(
			'rec_prod_btn',
			'recommended_products',
			'iws_recommended_products_mode',
			'recommended_products_mode',
		);

		$is_custom = false;

		foreach ( $mode_field_names as $field_name ) {
			$field_value = get_field( $field_name, $product_id );

			if ( iws_related_template_is_custom_mode( $field_value ) ) {
				$is_custom = true;
				break;
			}
		}

		if ( ! $is_custom ) {
			return array();
		}

		$product_field_names = array(
			'rec_products',
			'acf_recommended_products',
			'iws_recommended_products',
			'recommended_product_items',
			'custom_recommended_products',
		);

		foreach ( $product_field_names as $field_name ) {
			$product_ids = iws_related_template_normalize_product_ids( get_field( $field_name, $product_id ) );

			if ( ! empty( $product_ids ) ) {
				return $product_ids;
			}
		}

		return array();
	}
}

$current_product_id = get_the_ID();
$custom_product_ids = iws_related_template_get_custom_product_ids( $current_product_id );
$home_url           = get_home_url();
$w                  = function_exists( 'get_fields' ) ? ( get_fields( 'option' ) ?: array() ) : array();
$related_title      = $w['related_title'] ?? '';
$related_desc       = $w['related_desc'] ?? '';
$heading            = apply_filters( 'woocommerce_product_related_products_heading', __( (string) $related_title, 'woocommerce' ) );
?>
<section class="related products">
	<?php if ( $heading ) : ?>
		<h2 class="woo-related_title"><?php echo esc_html( $heading ); ?></h2>
		<?php if ( $related_desc ) : ?>
			<p class="woo-related_desc"><?php echo wp_kses_post( $related_desc ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( ! empty( $custom_product_ids ) ) : ?>
		<?php woocommerce_product_loop_start(); ?>
			<?php
			foreach ( $custom_product_ids as $product_id ) :
				$post_object = get_post( $product_id );

				if ( ! $post_object ) {
					continue;
				}

				$GLOBALS['post']    = $post_object;
				$GLOBALS['product'] = wc_get_product( $product_id );

				if ( ! $GLOBALS['product'] ) {
					continue;
				}

				setup_postdata( $post_object );
				wc_get_template_part( 'content', 'product' );
			endforeach;
			wp_reset_postdata();
			?>
		<?php woocommerce_product_loop_end(); ?>
	<?php else : ?>
		<?php woocommerce_product_loop_start(); ?>
			<?php
			foreach ( $related_products as $related_product ) :
				if ( ! $related_product instanceof WC_Product ) {
					continue;
				}

				$post_object = get_post( $related_product->get_id() );

				if ( ! $post_object ) {
					continue;
				}

				setup_postdata( $GLOBALS['post'] =& $post_object );
				wc_get_template_part( 'content', 'product' );
			endforeach;
			wp_reset_postdata();
			?>
		<?php woocommerce_product_loop_end(); ?>
		<a class="btn areoi-has-url position-relative btn-primary" href="<?php echo esc_url( trailingslashit( $home_url ) . 'products/' ); ?>"><?php esc_html_e( 'Discover More', 'woocommerce' ); ?></a>
	<?php endif; ?>
</section>
