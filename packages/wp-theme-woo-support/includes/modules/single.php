<?php
defined( 'ABSPATH' ) || exit;

/**
 * Replace single product title markup.
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
add_action( 'woocommerce_single_product_summary', function() {
	the_title( '<h1 class="h2 product_title entry-title">', '</h1>' );
}, 5 );

/**
 * Single product total price, reusable for the top variation price and simple products.
 */
function iws_render_single_product_total_price( WC_Product $product ) {
	$has_visible_price = function_exists( 'iws_product_has_visible_price' ) ? iws_product_has_visible_price( $product ) : ( '' !== $product->get_price() && (float) $product->get_price() > 0 );
	$base_price        = function_exists( 'iws_get_product_visible_unit_price' ) ? iws_get_product_visible_unit_price( $product ) : (float) wc_get_price_excluding_tax( $product );
	$symbol            = get_woocommerce_currency_symbol();

	if ( ! $has_visible_price ) {
		return;
	}

	if ( $product->is_type( 'variable' ) && $base_price <= 0 ) {
		$prices = $product->get_variation_prices( true );
		if ( ! empty( $prices['price'] ) ) {
			$base_price = (float) current( $prices['price'] );
		}
	}

	$price_hidden_attr = $base_price > 0 ? '' : ' hidden';
	echo '<div id="product_total_price" class="product-total-price" data-has-visible-price="1"' . $price_hidden_attr . '>';
	echo '<span class="product-total-price_caption" style="opacity:.7;">' . esc_html( $symbol . number_format( $base_price, 2 ) . ' each total:' ) . '</span> ';
	echo '<span class="product-total-price_sum price" data-price="' . esc_attr( $base_price ) . '">' . esc_html( $symbol . number_format( $base_price, 2 ) ) . '</span>';
	echo '</div>';
}

/**
 * Quantity minus button.
 */
add_action( 'woocommerce_before_quantity_input_field', function() {
	if ( is_product() ) {
		echo '<button type="button" class="minus" aria-label="' . esc_attr__( 'Decrease quantity', 'wp-theme-woo-support' ) . '">-</button>';
	}
} );

/**
 * Quantity plus button.
 */
add_action( 'woocommerce_after_quantity_input_field', function() {
	if ( is_product() ) {
		echo '<button type="button" class="plus" aria-label="' . esc_attr__( 'Increase quantity', 'wp-theme-woo-support' ) . '">+</button>';
	}
} );

/**
 * Product total price wrapper and SKU output.
 * JS should update this from external asset.
 */
add_action( 'woocommerce_after_add_to_cart_quantity', function() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$sku = $product->get_sku();

	iws_render_single_product_total_price( $product );
	echo '<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>';

	if ( $sku ) {
		echo '<div class="sku-prod"><b>' . esc_html__( 'SKU:', 'wp-theme-woo-support' ) . '</b> <span class="sku-value">' . esc_html( $sku ) . '</span></div>';
	}
}, 31 );

/**
 * Supplier logo.
 */
add_action( 'woocommerce_single_product_summary', function() {
	if ( ! function_exists( 'get_field' ) ) {
		return;
	}
	$supplier_logo      = get_field( 'supplier_logo' );
	$supplier_logo_link = get_field( 'supplier_logo_link' );

	if ( empty( $supplier_logo ) ) {
		return;
	}

	if ( ! empty( $supplier_logo_link ) ) {
		echo '<a href="' . esc_url( $supplier_logo_link ) . '" target="_blank" rel="noopener noreferrer">';
	}

	echo '<img src="' . esc_url( $supplier_logo ) . '" alt="' . esc_attr__( 'Supplier logo', 'wp-theme-woo-support' ) . '" loading="lazy" />';

	if ( ! empty( $supplier_logo_link ) ) {
		echo '</a>';
	}
}, 25 );

/**
 * Delivery info under add to cart.
 */
function theme_content_after_add_to_cart() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$delivery_info = function_exists( 'get_field' ) ? get_field( 'woo-prod-delivery-info', 'option' ) : get_option( 'options_woo-prod-delivery-info', array() );

	if ( function_exists( 'get_field' ) && get_field( 'iws_override_woo_delivery_info', $product->get_id() ) ) {
		$product_delivery_info = get_field( 'iws_product_delivery_info', $product->get_id() );
		if ( is_array( $product_delivery_info ) && ! empty( $product_delivery_info ) ) {
			$delivery_info = $product_delivery_info;
		}
	}

	if ( empty( $delivery_info ) || ! is_array( $delivery_info ) ) {
		return;
	}

	echo '<div class="woo-prod-delivery-info">';

	foreach ( $delivery_info as $item ) {
		$icon  = $item['woo_delivery_icon'] ?? '';
		$title = $item['woo_delivery_title'] ?? '';
		$desc  = $item['woo_delivery_desc'] ?? '';

		echo '<div class="item">';

		if ( $icon ) {
			echo '<div class="woo-delivery-icon-wrapper">';
			echo '<img class="woo-delivery-icon" src="' . esc_url( $icon ) . '" alt="" width="100" height="100" loading="lazy" />';
			echo '</div>';
		}

		if ( $title ) {
			echo '<h3 class="woo-delivery-title">' . wp_kses_post( $title ) . '</h3>';
		}

		if ( $desc ) {
			echo '<p class="woo-delivery-desc">' . wp_kses_post( $desc ) . '</p>';
		}

		echo '</div>';
	}

	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'theme_content_after_add_to_cart', 60 );
/**
 * Single product custom field and add-to-cart controls.
 */
if ( ! function_exists( 'iws_is_single_add_to_cart_disabled' ) ) {
	function iws_is_single_add_to_cart_disabled( $product_id ) {
		return 'yes' === get_post_meta( $product_id, '_iws_disable_single_add_to_cart', true );
	}
}

/*
 * Do not mark the product as not purchasable when only the normal Add to cart
 * button is disabled. Quote plugins commonly use WooCommerce's purchasable
 * state and/or the single product cart form to decide whether to render their
 * global or per-product Add to quote button.
 *
 * The normal Add to cart button is hidden below instead, and direct WooCommerce
 * add-to-cart requests are blocked by validation. This keeps quote buttons and
 * quote functionality available when enabled globally or on the single product.
 */


/**
 * Hide normal add-to-cart buttons everywhere for products marked as cart-disabled.
 * Keeps quote buttons available if the quote plugin is enabled for the product.
 */
add_filter( 'woocommerce_loop_add_to_cart_link', function( $html, $product, $args ) {
	if ( $product instanceof WC_Product && iws_is_single_add_to_cart_disabled( $product->get_id() ) ) {
		return '';
	}

	return $html;
}, 30, 3 );

add_filter( 'woocommerce_add_to_cart_validation', function( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
	$base_product_id = $variation_id ? wp_get_post_parent_id( $variation_id ) : $product_id;
	$base_product_id = $base_product_id ? $base_product_id : $product_id;

	if ( $base_product_id && iws_is_single_add_to_cart_disabled( $base_product_id ) ) {
		wc_add_notice( __( 'This product is available by quote only. Please use the quote button.', 'wp-theme-woo-support' ), 'error' );
		return false;
	}

	return $passed;
}, 20, 5 );

add_filter( 'woocommerce_blocks_product_grid_item_html', function( $html, $data, $product ) {
	if ( $product instanceof WC_Product && iws_is_single_add_to_cart_disabled( $product->get_id() ) ) {
		$html = preg_replace_callback(
			'#<a[^>]+(?:add_to_cart_button|wp-block-button__link)[^>]*>.*?</a>#is',
			function( $matches ) {
				$link = $matches[0];
				if ( preg_match( '/(?:quote|tfa-quote-button|tfa-wc-quote-button|tfa-add-to-quote|add-to-quote|add_to_quote_button|theme-auto-quote-btn|get-quote-btn)/i', $link ) ) {
					return $link;
				}
				return '';
			},
			(string) $html
		);
	}

	return $html;
}, 30, 3 );

add_action( 'wp_head', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style>
		body .product.iws-cart-disabled .add_to_cart_button:not(.tfa-quote-button):not(.tfa-wc-quote-button):not(.tfa-add-to-quote):not(.add-to-quote):not(.add_to_quote_button):not(.theme-auto-quote-btn):not(.get-quote-btn),
		body .product.iws-cart-disabled .ajax_add_to_cart:not(.tfa-quote-button):not(.tfa-wc-quote-button):not(.tfa-add-to-quote):not(.add-to-quote):not(.add_to_quote_button):not(.theme-auto-quote-btn):not(.get-quote-btn),
		body .product.iws-cart-disabled a[href*="add-to-cart"]:not(.tfa-quote-button):not(.tfa-wc-quote-button):not(.tfa-add-to-quote):not(.add-to-quote):not(.add_to_quote_button):not(.theme-auto-quote-btn):not(.get-quote-btn),
		body.single-product.iws-cart-disabled-product form.cart .single_add_to_cart_button:not(.tfa-quote-button):not(.tfa-wc-quote-button):not(.tfa-add-to-quote):not(.add-to-quote):not(.add_to_quote_button):not(.theme-auto-quote-btn):not(.get-quote-btn):not([name*="quote"]),
		body.single-product.iws-cart-disabled-product form.cart button[name="add-to-cart"],
		body.single-product.iws-cart-disabled-product form.cart input[name="add-to-cart"]{display:none!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important}
	</style>
	<?php
}, 40 );

add_filter( 'post_class', function( $classes, $class, $post_id ) {
	if ( 'product' === get_post_type( $post_id ) && iws_is_single_add_to_cart_disabled( $post_id ) ) {
		$classes[] = 'iws-cart-disabled';
	}
	return $classes;
}, 20, 3 );

add_filter( 'gettext', function( $translated, $text, $domain ) {
	if ( is_admin() || ! is_product() || 'Sorry, this product cannot be purchased.' !== $text ) {
		return $translated;
	}

	$product_id = get_queried_object_id();
	if ( $product_id && iws_is_single_add_to_cart_disabled( $product_id ) ) {
		return __( 'This product is available by quote only. Please add it to your quote request or contact us for more information.', 'wp-theme-woo-support' );
	}

	return $translated;
}, 20, 3 );

add_filter( 'body_class', function( $classes ) {
	if ( is_product() ) {
		$product_id = get_queried_object_id();
		if ( $product_id && iws_is_single_add_to_cart_disabled( $product_id ) ) {
			$classes[] = 'iws-cart-disabled-product';
		}
	}
	return $classes;
}, 20 );

add_action( 'wp_footer', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<script>
	(function(){
		function isQuoteButton(el){
			return !!(el && (el.matches('.tfa-quote-button,.tfa-wc-quote-button,.tfa-add-to-quote,.add-to-quote,.add_to_quote_button,.theme-auto-quote-btn,.get-quote-btn') || /quote/i.test(el.getAttribute('name') || '') || /quote/i.test(el.getAttribute('class') || '')));
		}
		function hideElement(el){
			el.style.setProperty('display','none','important');
			el.style.setProperty('visibility','hidden','important');
			el.style.setProperty('opacity','0','important');
			el.style.setProperty('pointer-events','none','important');
		}
		function hideCartButtons(){
			if(document.body && document.body.classList.contains('iws-cart-disabled-product')){
				document.querySelectorAll('form.cart .single_add_to_cart_button, form.cart button[name="add-to-cart"], form.cart input[name="add-to-cart"]').forEach(function(el){ if(!isQuoteButton(el)){ hideElement(el); } });
			}
			document.querySelectorAll('.product.iws-cart-disabled .add_to_cart_button, .product.iws-cart-disabled .ajax_add_to_cart, .product.iws-cart-disabled a[href*="add-to-cart"]').forEach(function(el){ if(!isQuoteButton(el)){ hideElement(el); } });
		}
		document.addEventListener('DOMContentLoaded', hideCartButtons);
		hideCartButtons();
		if('MutationObserver' in window){new MutationObserver(hideCartButtons).observe(document.documentElement,{childList:true,subtree:true});}
	})();
	</script>
	<?php
}, 50 );

if ( ! function_exists( 'iws_get_single_product_custom_text_fields' ) ) {
	function iws_get_single_product_custom_text_fields( $product_id ) {
		$stored = get_post_meta( $product_id, '_iws_single_product_custom_fields', true );
		$source = array();

		if ( is_array( $stored ) ) {
			$source = $stored;
		} else {
			$legacy = get_post_meta( $product_id, '_iws_single_product_custom_text_fields', true );
			if ( ! empty( $legacy ) ) {
				$labels = preg_split( '/\r\n|\r|\n/', (string) $legacy );
				foreach ( $labels as $index => $label ) {
					$source[] = array(
						'label'       => $label,
						'placeholder' => 0 === $index ? 'Minimum length 1400mm' : '',
					);
				}
			}
		}

		if ( empty( $source ) ) {
			return array();
		}

		$fields = array();
		$used   = array();

		foreach ( $source as $field ) {
			if ( is_string( $field ) ) {
				$label       = $field;
				$placeholder = '';
			} else {
				$label       = isset( $field['label'] ) ? $field['label'] : '';
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
			}

			$label       = trim( wp_strip_all_tags( (string) $label ) );
			$placeholder = trim( wp_strip_all_tags( (string) $placeholder ) );

			if ( '' === $label ) {
				continue;
			}

			$key  = sanitize_title( $label );
			$key  = $key ? $key : 'field';
			$base = $key;
			$i    = 2;

			while ( in_array( $key, $used, true ) ) {
				$key = $base . '-' . $i;
				$i++;
			}

			$used[]   = $key;
			$fields[] = array(
				'key'         => $key,
				'label'       => $label,
				'placeholder' => $placeholder,
				'class'       => 'iws-custom-field-' . sanitize_html_class( $key ),
			);
		}

		return $fields;
	}
}

function iws_render_single_product_custom_text_fields() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$fields = iws_get_single_product_custom_text_fields( $product->get_id() );

	if ( empty( $fields ) ) {
		return;
	}

	echo '<div class="iws-single-product-custom-fields">';
	echo '<style>.iws-single-product-custom-fields{margin:0 0 18px;display:flex;flex-direction:column;gap:14px}.iws-single-product-custom-field{margin:0!important}.iws-single-product-custom-field label{display:block;font-weight:700;margin:0 0 6px}.iws-single-product-custom-field input{width:100%;max-width:420px}</style>';

	foreach ( $fields as $field ) {
		$name  = 'iws_single_product_custom_fields[' . $field['key'] . ']';
		$value = '';

		if ( isset( $_POST['iws_single_product_custom_fields'][ $field['key'] ] ) ) {
			$value = wc_clean( wp_unslash( $_POST['iws_single_product_custom_fields'][ $field['key'] ] ) );
		}

		echo '<p class="form-row iws-single-product-custom-field ' . esc_attr( $field['class'] ) . '">';
		echo '<label for="iws-custom-field-' . esc_attr( $field['key'] ) . '">' . esc_html( $field['label'] ) . '</label>';
		echo '<input type="text" class="input-text" id="iws-custom-field-' . esc_attr( $field['key'] ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" data-iws-custom-label="' . esc_attr( $field['label'] ) . '" />';
		echo '</p>';
	}

	echo '</div>';
}
add_action( 'woocommerce_before_add_to_cart_button', 'iws_render_single_product_custom_text_fields', 20 );

add_filter( 'woocommerce_add_cart_item_data', function( $cart_item_data, $product_id, $variation_id ) {
	$fields = iws_get_single_product_custom_text_fields( $product_id );

	if ( empty( $fields ) || empty( $_POST['iws_single_product_custom_fields'] ) || ! is_array( $_POST['iws_single_product_custom_fields'] ) ) {
		return $cart_item_data;
	}

	$posted = wc_clean( wp_unslash( $_POST['iws_single_product_custom_fields'] ) );
	$values = array();

	foreach ( $fields as $field ) {
		if ( ! isset( $posted[ $field['key'] ] ) ) {
			continue;
		}

		$value = trim( (string) $posted[ $field['key'] ] );

		if ( '' === $value ) {
			continue;
		}

		$values[ $field['key'] ] = array(
			'label' => $field['label'],
			'value' => $value,
		);
	}

	if ( ! empty( $values ) ) {
		$cart_item_data['iws_single_product_custom_fields']     = $values;
		$cart_item_data['iws_single_product_custom_fields_key'] = md5( wp_json_encode( $values ) . microtime() );
	}

	return $cart_item_data;
}, 10, 3 );

add_filter( 'woocommerce_get_item_data', function( $item_data, $cart_item ) {
	if ( empty( $cart_item['iws_single_product_custom_fields'] ) || ! is_array( $cart_item['iws_single_product_custom_fields'] ) ) {
		return $item_data;
	}

	foreach ( $cart_item['iws_single_product_custom_fields'] as $field ) {
		$item_data[] = array(
			'name'  => $field['label'],
			'value' => $field['value'],
		);
	}

	return $item_data;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function( $item, $cart_item_key, $values, $order ) {
	if ( empty( $values['iws_single_product_custom_fields'] ) || ! is_array( $values['iws_single_product_custom_fields'] ) ) {
		return;
	}

	foreach ( $values['iws_single_product_custom_fields'] as $field ) {
		$item->add_meta_data( $field['label'], $field['value'], true );
	}
}, 10, 4 );

/**
 * Use selected ACF products when single-product Recommended Products is set to Custom.
 *
 * Without this filter WooCommerce keeps using its automatic related-products query,
 * so products chosen in ACF are saved in admin but never shown on the frontend.
 */
if ( ! function_exists( 'iws_normalize_recommended_product_ids' ) ) {
	function iws_normalize_recommended_product_ids( $value ) {
		$ids = array();

		if ( empty( $value ) ) {
			return $ids;
		}

		if ( is_numeric( $value ) ) {
			$value = array( (int) $value );
		}

		if ( $value instanceof WP_Post ) {
			$value = array( $value );
		}

		if ( $value instanceof WC_Product ) {
			$value = array( $value );
		}

		if ( ! is_array( $value ) ) {
			return $ids;
		}

		foreach ( $value as $item ) {
			$id = 0;

			if ( is_numeric( $item ) ) {
				$id = (int) $item;
			} elseif ( $item instanceof WP_Post ) {
				$id = (int) $item->ID;
			} elseif ( $item instanceof WC_Product ) {
				$id = (int) $item->get_id();
			} elseif ( is_array( $item ) ) {
				$id = isset( $item['ID'] ) ? (int) $item['ID'] : ( isset( $item['id'] ) ? (int) $item['id'] : 0 );
			}

			if ( $id > 0 && 'product' === get_post_type( $id ) ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}
}

if ( ! function_exists( 'iws_get_single_product_acf_recommended_ids' ) ) {
	function iws_get_single_product_acf_recommended_ids( $product_id ) {
		if ( ! function_exists( 'get_field' ) || ! $product_id ) {
			return array();
		}

		$mode_field_names = array(
			'iws_recommended_products_source',
			'iws_recommended_products_mode',
			'recommended_products_source',
			'recommended_products_mode',
			'recommended_products_custom_opt',
			'iws_recommended_products_custom_opt',
		);

		$mode_found = false;
		$is_custom  = false;

		foreach ( $mode_field_names as $field_name ) {
			$raw_mode = get_field( $field_name, $product_id );

			if ( null === $raw_mode || '' === $raw_mode ) {
				continue;
			}

			$mode_found = true;
			$mode      = is_bool( $raw_mode ) ? ( $raw_mode ? '1' : '0' ) : strtolower( trim( (string) $raw_mode ) );

			if ( in_array( $mode, array( '1', 'yes', 'true', 'on', 'custom', 'custom_opt', 'manual', 'selected', 'acf' ), true ) ) {
				$is_custom = true;
				break;
			}
		}

		$recommended_field_names = array(
			'iws_recommended_products',
			'acf_recommended_products',
			'recommended_products',
			'iws_acf_recommended_products',
			'product_recommended_products',
		);

		$selected_ids = array();

		foreach ( $recommended_field_names as $field_name ) {
			$selected_ids = iws_normalize_recommended_product_ids( get_field( $field_name, $product_id ) );

			if ( ! empty( $selected_ids ) ) {
				break;
			}
		}

		if ( empty( $selected_ids ) ) {
			return array();
		}

		// If a Custom/Auto selector exists, only override WooCommerce when Custom is active.
		// If there is no selector field, selected ACF products alone are enough to override.
		if ( $mode_found && ! $is_custom ) {
			return array();
		}

		return array_values( array_diff( $selected_ids, array( (int) $product_id ) ) );
	}
}

add_filter( 'woocommerce_related_products', function( $related_posts, $product_id, $args ) {
	$selected_ids = iws_get_single_product_acf_recommended_ids( $product_id );

	if ( empty( $selected_ids ) ) {
		return $related_posts;
	}

	$limit = isset( $args['limit'] ) ? (int) $args['limit'] : count( $selected_ids );
	if ( $limit > 0 ) {
		$selected_ids = array_slice( $selected_ids, 0, $limit );
	}

	return $selected_ids;
}, 20, 3 );

/**
 * Latest case studies section for single product pages.
 * Controlled by WP Options: iws_enable_latest_case_studies_on_products.
 */
if ( ! function_exists( 'iws_product_latest_case_studies_enabled' ) ) {
	function iws_product_latest_case_studies_enabled() {
		$value = null;

		if ( function_exists( 'get_field' ) ) {
			$value = get_field( 'iws_enable_latest_case_studies_on_products', 'option' );
		}

		if ( null === $value || '' === $value || false === $value ) {
			$value = get_option( 'options_iws_enable_latest_case_studies_on_products', get_option( 'iws_enable_latest_case_studies_on_products', '0' ) );
		}

		return true === $value || '1' === (string) $value || 1 === $value;
	}
}

if ( ! function_exists( 'iws_render_product_latest_case_studies' ) ) {
	function iws_render_product_latest_case_studies() {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() || ! iws_product_latest_case_studies_enabled() ) {
			return '';
		}

		$case_studies = new WP_Query( array(
			'post_type'              => 'case-study',
			'post_status'            => 'publish',
			'posts_per_page'         => 3,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		if ( ! $case_studies->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		$case_studies_url = trailingslashit( home_url( 'case-studies' ) );

		ob_start();
		?>
		<section class="related products iws-latest-case-studies" aria-labelledby="iws-latest-case-studies-title">
			<h2 id="iws-latest-case-studies-title" class="woo-related_title iws-latest-case-studies__title"><?php esc_html_e( 'Latest Case Studies', 'wp-theme-woo-support' ); ?></h2>
			<div class="iws-latest-case-studies__grid">
				<?php while ( $case_studies->have_posts() ) : $case_studies->the_post(); ?>
					<article <?php post_class( 'iws-latest-case-studies__item faux-link__element' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a class="iws-latest-case-studies__image" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
								<?php the_post_thumbnail( 'medium_large' ); ?>
							</a>
						<?php endif; ?>
						<div class="iws-latest-case-studies__content">
							<h3 class="iws-latest-case-studies__item-title h4"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<?php if ( has_excerpt() || get_the_content() ) : ?>
								<p class="iws-latest-case-studies__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '...' ) ); ?></p>
							<?php endif; ?>
							<a class="iws-latest-case-studies__learn-more btn btn-primary" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Learn More', 'wp-theme-woo-support' ); ?></a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<div class="iws-latest-case-studies__discover"><a class="btn areoi-has-url position-relative btn-primary" href="<?php echo esc_url( $case_studies_url ); ?>"><?php esc_html_e( 'Discover More', 'wp-theme-woo-support' ); ?></a></div>
		</section>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
}

add_shortcode( 'iws_latest_case_studies', 'iws_render_product_latest_case_studies' );

add_action( 'wp_head', function() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() || ! iws_product_latest_case_studies_enabled() ) {
		return;
	}
	?>
	<style>
		body.single-product .iws-latest-case-studies.related.products{margin:20px 0 60px;text-align:left;background:transparent!important;padding:0!important}body.single-product .iws-latest-case-studies .woo-related_title{margin:0 0 30px;text-align:left}body.single-product .iws-latest-case-studies__grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:30px;margin:0 0 30px;padding:0;text-align:left}body.single-product .iws-latest-case-studies__item{position:relative;display:flex;flex-direction:column;height:100%;min-height:100%;padding:20px 20px 70px;background:#f7f7f7;border-radius:10px;text-align:left;align-items:flex-start;box-sizing:border-box;overflow:hidden}body.single-product .iws-latest-case-studies__image{display:block;width:100%;margin:0 0 18px;overflow:hidden;text-align:left}body.single-product .iws-latest-case-studies__image img{display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover;margin:0}body.single-product .iws-latest-case-studies__content{display:flex;flex:1 1 auto;flex-direction:column;align-items:flex-start;width:100%;text-align:left}body.single-product .iws-latest-case-studies__item-title{margin:0 0 8px;font-size:22px!important;font-weight:700!important;line-height:1.15!important;text-align:left;color:#111!important}body.single-product .iws-latest-case-studies__item-title a{text-decoration:none;color:#111!important}body.single-product .iws-latest-case-studies__excerpt{margin:0 0 18px;text-align:left}body.single-product .iws-latest-case-studies__learn-more{position:absolute;left:20px;bottom:20px;margin-top:0;text-align:center}body.single-product .iws-latest-case-studies__discover{display:flex;justify-content:center;width:100%;text-align:center}body.single-product .iws-latest-case-studies__discover .btn{margin-left:auto!important;margin-right:auto!important}@media(max-width:991px){body.single-product .iws-latest-case-studies__grid{grid-template-columns:1fr 1fr}}@media(max-width:767px){body.single-product .iws-latest-case-studies__grid{grid-template-columns:1fr}}
	</style>
	<?php
}, 60 );
