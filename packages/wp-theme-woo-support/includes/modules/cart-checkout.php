<?php
defined( 'ABSPATH' ) || exit;


/**
 * Force WooCommerce cart/checkout pages back to native legacy shortcode templates
 * when the page content contains WooCommerce Blocks. This keeps the existing
 * theme WooCommerce table/form styling working and avoids unstyled block output.
 */
function iws_is_basket_page_for_legacy_cart() {
	if ( is_cart() || is_page( array( 'basket', 'cart' ) ) ) {
		return true;
	}

	if ( ! is_singular() ) {
		return false;
	}

	global $post;

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return 'basket' === $post->post_name
		|| has_shortcode( (string) $post->post_content, 'woocommerce_cart' )
		|| has_block( 'woocommerce/cart', $post );
}

function iws_is_checkout_page_for_legacy_checkout() {
	if ( is_checkout() || is_page( 'checkout' ) ) {
		return true;
	}

	if ( ! is_singular() ) {
		return false;
	}

	global $post;

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return 'checkout' === $post->post_name
		|| has_shortcode( (string) $post->post_content, 'woocommerce_checkout' )
		|| has_block( 'woocommerce/checkout', $post );
}

add_filter( 'render_block', function( $block_content, $block ) {
	if ( is_admin() || empty( $block['blockName'] ) ) {
		return $block_content;
	}

	if ( 'woocommerce/cart' === $block['blockName'] && iws_is_basket_page_for_legacy_cart() ) {
		return do_shortcode( '[woocommerce_cart]' );
	}

	if ( 'woocommerce/checkout' === $block['blockName'] && iws_is_checkout_page_for_legacy_checkout() && ! is_order_received_page() ) {
		return do_shortcode( '[woocommerce_checkout]' );
	}

	return $block_content;
}, 9, 2 );

add_filter( 'the_content', function( $content ) {
	if ( is_admin() || ! iws_is_basket_page_for_legacy_cart() ) {
		return $content;
	}

	if ( false !== strpos( $content, '<!-- wp:woocommerce/cart' ) ) {
		return '[woocommerce_cart]';
	}

	return $content;
}, 1 );



/**
 * Basket quantity controls.
 *
 * Native WooCommerce renders a number input in the cart table, but on the
 * Basket page the theme styling can make it hard to change quantities. Add
 * +/- controls on cart/basket only and keep the max value tied to stock when
 * stock management is enabled.
 */
function iws_is_cart_quantity_control_screen() {
	return ! is_admin() && iws_is_basket_page_for_legacy_cart();
}

add_filter( 'woocommerce_quantity_input_args', function( $args, $product ) {
	if ( ! iws_is_cart_quantity_control_screen() || ! $product instanceof WC_Product ) {
		return $args;
	}

	$args['inputmode'] = 'numeric';
	$args['min_value'] = isset( $args['min_value'] ) ? max( 0, (int) $args['min_value'] ) : 0;
	$args['step']      = isset( $args['step'] ) && (float) $args['step'] > 0 ? $args['step'] : 1;

	if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
		$stock_quantity = $product->get_stock_quantity();
		if ( null !== $stock_quantity ) {
			$args['max_value'] = max( 1, (int) $stock_quantity );
		}
	} elseif ( empty( $args['max_value'] ) || $args['max_value'] < 0 ) {
		$args['max_value'] = '';
	}

	return $args;
}, 20, 2 );

add_action( 'woocommerce_before_quantity_input_field', function() {
	if ( iws_is_cart_quantity_control_screen() ) {
		echo '<button type="button" class="minus iws-cart-qty-btn" aria-label="' . esc_attr__( 'Decrease quantity', 'wp-theme-woo-support' ) . '">-</button>';
	}
}, 20 );

add_action( 'woocommerce_after_quantity_input_field', function() {
	if ( iws_is_cart_quantity_control_screen() ) {
		echo '<button type="button" class="plus iws-cart-qty-btn" aria-label="' . esc_attr__( 'Increase quantity', 'wp-theme-woo-support' ) . '">+</button>';
	}
}, 20 );

add_action( 'wp_footer', function() {
	if ( ! iws_is_cart_quantity_control_screen() ) {
		return;
	}
	?>
	<script>
		document.addEventListener('click', function (event) {
			const btn = event.target.closest('.woocommerce-cart-form .iws-cart-qty-btn, .woocommerce-cart-form .plus, .woocommerce-cart-form .minus');
			if (!btn) return;

			const quantity = btn.closest('.quantity');
			const input = quantity ? quantity.querySelector('input.qty') : null;
			if (!input || input.disabled || input.readOnly) return;

			event.preventDefault();

			const current = parseFloat(input.value) || 0;
			const step = parseFloat(input.getAttribute('step')) || 1;
			const min = input.getAttribute('min') !== '' ? parseFloat(input.getAttribute('min')) : 0;
			const maxAttr = input.getAttribute('max');
			const max = maxAttr !== null && maxAttr !== '' ? parseFloat(maxAttr) : null;
			let next = btn.classList.contains('plus') ? current + step : current - step;

			next = Math.max(min, next);
			if (max !== null && !Number.isNaN(max)) {
				next = Math.min(max, next);
			}

			input.value = String(next);
			input.dispatchEvent(new Event('input', { bubbles: true }));
			input.dispatchEvent(new Event('change', { bubbles: true }));

			const form = input.closest('form.woocommerce-cart-form');
			const updateButton = form ? form.querySelector('button[name="update_cart"]') : null;
			if (updateButton) {
				updateButton.disabled = false;
				updateButton.removeAttribute('disabled');
			}
		});
	</script>
	<?php
}, 40 );


/**
 * Keep the Basket proceed-to-checkout button native and stable after cart AJAX
 * refreshes. Some Woo Blocks/interactivity code can restore an empty/current
 * URL after quantity updates, which sends the click to the home/current page.
 */
add_action( 'wp_footer', function() {
	if ( ! iws_is_cart_quantity_control_screen() ) {
		return;
	}

	$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	?>
	<script id="iws-basket-checkout-url-fix">
		(function () {
			var fallbackCheckoutUrl = <?php echo wp_json_encode( esc_url_raw( $checkout_url ) ); ?>;

			function looksLikeCheckoutUrl(url) {
				return typeof url === 'string' && /\/checkout\/?(?:[?#].*)?$/i.test(url);
			}

			function getBasketCheckoutUrl(button) {
				if (!button) return fallbackCheckoutUrl || '/checkout/';
				var dataUrl = button.getAttribute('data-iws-checkout-url') || '';
				var attrUrl = button.getAttribute('href') || '';
				var propUrl = button.href || '';

				if (looksLikeCheckoutUrl(dataUrl)) return dataUrl;
				if (looksLikeCheckoutUrl(attrUrl)) return attrUrl;
				if (looksLikeCheckoutUrl(propUrl)) return propUrl;
				if (looksLikeCheckoutUrl(fallbackCheckoutUrl)) return fallbackCheckoutUrl;
				return '/checkout/';
			}

			function normaliseBasketCheckoutButton(root) {
				var scope = root && root.querySelectorAll ? root : document;
				var buttons = [];
				if (scope.matches && scope.matches('.wc-proceed-to-checkout .checkout-button, .cart_totals .checkout-button')) {
					buttons.push(scope);
				}
				scope.querySelectorAll('.wc-proceed-to-checkout .checkout-button, .cart_totals .checkout-button').forEach(function (button) {
					buttons.push(button);
				});

				buttons.forEach(function (button) {
					var checkoutUrl = getBasketCheckoutUrl(button);
					if (!button || !checkoutUrl) return;
					button.setAttribute('href', checkoutUrl);
					button.setAttribute('data-iws-checkout-url', checkoutUrl);
					button.removeAttribute('data-wp-on--click');
					button.removeAttribute('data-wp-bind--href');
				});
			}

			document.addEventListener('click', function (event) {
				var target = event.target instanceof Element ? event.target : null;
				if (!target) return;

				var button = target.closest('.wc-proceed-to-checkout .checkout-button, .cart_totals .checkout-button');
				if (!button) return;

				normaliseBasketCheckoutButton(button);
				var checkoutUrl = getBasketCheckoutUrl(button);

				// Let browser-native new-tab/window behaviour use the fixed href.
				if (event.button && event.button !== 0) return;
				if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

				event.preventDefault();
				event.stopPropagation();
				if (typeof event.stopImmediatePropagation === 'function') {
					event.stopImmediatePropagation();
				}
				window.location.href = checkoutUrl;
			}, true);

			document.addEventListener('DOMContentLoaded', function () {
				normaliseBasketCheckoutButton(document);
			});
			normaliseBasketCheckoutButton(document);

			if (window.jQuery) {
				window.jQuery(document.body).on('updated_wc_div wc_fragments_loaded wc_fragments_refreshed', function () {
					window.setTimeout(function () {
						normaliseBasketCheckoutButton(document);
					}, 1);
				});
			}

			if ('MutationObserver' in window) {
				new MutationObserver(function (mutations) {
					for (var i = 0; i < mutations.length; i++) {
						if (mutations[i].addedNodes && mutations[i].addedNodes.length) {
							normaliseBasketCheckoutButton(document);
							break;
						}
					}
				}).observe(document.body, { childList: true, subtree: true });
			}
		}());
	</script>
	<?php
}, 45 );


/**
 * Checkout: optional EORI number for Northern Ireland shipping addresses.
 */
function iws_checkout_eori_label() {
	$eori_url = 'https://www.gov.uk/eori';

	return sprintf(
		'%1$s <a class="iws-eori-info-link" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">?</a>',
		esc_html__( 'EORI Number', 'wp-theme-woo-support' ),
		esc_url( $eori_url ),
		esc_attr__( 'More information about EORI numbers', 'wp-theme-woo-support' )
	);
}

add_filter( 'woocommerce_checkout_fields', function( $fields ) {
	$fields['order']['iws_eori_number'] = array(
		'type'        => 'text',
		'label'       => iws_checkout_eori_label(),
		'required'    => false,
		'class'       => array( 'form-row-wide', 'iws-eori-field' ),
		'priority'    => 5,
		'placeholder' => esc_attr__( 'EORI number, if applicable', 'wp-theme-woo-support' ),
	);

	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['priority'] = max( 10, (int) ( $fields['order']['order_comments']['priority'] ?? 10 ) );
	}

	return $fields;
}, 20 );

add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
	if ( ! $order instanceof WC_Order || empty( $_POST['iws_eori_number'] ) ) {
		return;
	}

	$order->update_meta_data( '_iws_eori_number', sanitize_text_field( wp_unslash( $_POST['iws_eori_number'] ) ) );
}, 20, 2 );

add_action( 'woocommerce_admin_order_data_after_shipping_address', function( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$eori_number = $order->get_meta( '_iws_eori_number' );
	if ( '' === $eori_number ) {
		return;
	}

	echo '<p><strong>' . esc_html__( 'EORI Number:', 'wp-theme-woo-support' ) . '</strong> ' . esc_html( $eori_number ) . '</p>';
} );

add_filter( 'woocommerce_email_order_meta_fields', function( $fields, $sent_to_admin, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $fields;
	}

	$eori_number = $order->get_meta( '_iws_eori_number' );
	if ( '' !== $eori_number ) {
		$fields['iws_eori_number'] = array(
			'label' => esc_html__( 'EORI Number', 'wp-theme-woo-support' ),
			'value' => $eori_number,
		);
	}

	return $fields;
}, 20, 3 );

add_action( 'wp_enqueue_scripts', function() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	wp_register_style( 'iws-checkout-eori', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-checkout-eori' );
	wp_add_inline_style(
		'iws-checkout-eori',
		'.iws-eori-field{display:none}.iws-eori-field.iws-eori-field--visible{display:block}.iws-eori-info-link{display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;margin-left:6px;border-radius:50%;background:var(--tfa-teal-color,#348b9c);color:#fff!important;font-size:11px;line-height:16px;text-decoration:none;vertical-align:middle}.iws-eori-info-link:hover,.iws-eori-info-link:focus{color:#fff!important;text-decoration:none}'
	);
} );

add_action( 'wp_footer', function() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	?>
	<script id="iws-checkout-eori-toggle">
		(function () {
			function valueOf(selector) {
				var field = document.querySelector(selector);
				return field && field.value ? String(field.value).trim() : '';
			}

			function useShippingAddress() {
				var checkbox = document.querySelector('#ship-to-different-address-checkbox');
				return !!(checkbox && checkbox.checked);
			}

			function isNorthernIrelandAddress(prefix) {
				var country = valueOf('#' + prefix + '_country').toUpperCase();
				var state = valueOf('#' + prefix + '_state').toUpperCase();
				var postcode = valueOf('#' + prefix + '_postcode').replace(/\s+/g, '').toUpperCase();

				if (country === 'XI') return true;
				if (country && country !== 'GB' && country !== 'UK') return false;
				if (state === 'NIR' || state === 'NI' || state.indexOf('NORTHERN IRELAND') !== -1) return true;
				return postcode.indexOf('BT') === 0;
			}

			function updateEoriField() {
				var wrapper = document.querySelector('#iws_eori_number_field');
				var input = document.querySelector('#iws_eori_number');
				if (!wrapper) return;

				var prefix = useShippingAddress() ? 'shipping' : 'billing';
				var visible = isNorthernIrelandAddress(prefix);
				wrapper.classList.toggle('iws-eori-field--visible', visible);
				wrapper.setAttribute('aria-hidden', visible ? 'false' : 'true');

				if (input && !visible) {
					input.value = '';
				}
			}

			document.addEventListener('change', function (event) {
				if (event.target && /^(billing|shipping)_(country|state|postcode)$/.test(event.target.id || '') || (event.target && event.target.id === 'ship-to-different-address-checkbox')) {
					updateEoriField();
				}
			});

			document.addEventListener('input', function (event) {
				if (event.target && /^(billing|shipping)_(state|postcode)$/.test(event.target.id || '')) {
					updateEoriField();
				}
			});

			document.addEventListener('DOMContentLoaded', updateEoriField);
			updateEoriField();

			if (window.jQuery) {
				window.jQuery(document.body).on('updated_checkout country_to_state_changed', updateEoriField);
			}
		}());
	</script>
	<?php
}, 50 );

/**
 * Checkout: show product thumbnails in order review.
 */
add_filter( 'woocommerce_cart_item_name', function( $name, $cart_item ) {
	if ( ! is_checkout() || empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
		return $name;
	}

	$thumbnail = $cart_item['data']->get_image( array( 70, 70 ), array( 'class' => 'alignleft' ) );

	return $thumbnail . $name;
}, 9999, 2 );

/**
 * Small order fee.
 */
// add_action( 'woocommerce_cart_calculate_fees', function( $cart ) {
// 	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
// 		return;
// 	}

// 	$minimum_amount = 200;
// 	$fee            = 25;

// 	if ( $cart->get_subtotal() < $minimum_amount ) {
// 		$cart->add_fee( __( 'Small Order Fee', 'wp-theme-woo-support' ), $fee );
// 	}
// }, 20 );

/**
 * Replace cart shipping method label for flat_rate:8.
 */
add_filter( 'woocommerce_cart_shipping_method_full_label', function( $label, $method ) {
	if ( isset( $method->id ) && 'flat_rate:8' === $method->id ) {
		return 'P.O.A <small style="display:block;line-height:1em;">' .
			esc_html__( 'Please get in touch to discuss your order', 'wp-theme-woo-support' ) .
			'</small>';
	}

	return $label;
}, 10, 2 );

/**
 * Replace shipping text on order/emails/thank you page.
 */
add_filter( 'woocommerce_order_shipping_to_display', function( $shipping, $order ) {
	foreach ( $order->get_shipping_methods() as $shipping_method ) {
		if ( 'flat_rate' === $shipping_method->get_method_id() && 8 === (int) $shipping_method->get_instance_id() ) {
			return esc_html__( 'Shipping P.O.A. Please get in touch to discuss your order', 'wp-theme-woo-support' );
		}
	}

	return $shipping;
}, 10, 2 );

/**
 * Disable shipping calculation on cart page.
 */
add_filter( 'woocommerce_cart_ready_to_calc_shipping', function( $show_shipping ) {
	return is_cart() ? false : $show_shipping;
} );

/**
 * Ship to different address unchecked by default.
 */
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

/**
 * Cart shipping message row.
 */
add_action( 'wp_footer', function() {
	if ( ! is_cart() ) {
		return;
	}
	?>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const cartTotalsTable = document.querySelector('.cart_totals table');
			if (!cartTotalsTable || cartTotalsTable.querySelector('.shipping-checkout-message')) return;

			const row = document.createElement('tr');
			row.className = 'shipping-checkout-message';
			row.innerHTML = '<th>Shipping</th><td>Shipping costs will be calculated during checkout.</td>';
			cartTotalsTable.appendChild(row);
		});
	</script>
	<?php
} );

/**
 * Legacy cart/checkout layout fallback.
 *
 * The theme's older saved cart/checkout pages use native WooCommerce shortcode
 * markup wrapped in Bootstrap/AREOI grid classes. If Woo's page detection or the
 * block wrapper misses those pages, the rows/columns lose alignment. These
 * scoped rules recreate the legacy grid without touching product archives.
 */
add_action( 'wp_enqueue_scripts', function() {
	$is_cart_checkout = false;

	if ( function_exists( 'theme_is_woo_cart_checkout_screen' ) ) {
		$is_cart_checkout = theme_is_woo_cart_checkout_screen();
	} else {
		$is_cart_checkout = is_cart() || is_checkout() || is_page( array( 'cart', 'basket', 'checkout' ) );
	}

	if ( ! $is_cart_checkout ) {
		return;
	}

	wp_register_style( 'iws-legacy-cart-checkout-layout', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-legacy-cart-checkout-layout' );
	wp_add_inline_style(
		'iws-legacy-cart-checkout-layout',
		'body.woocommerce-cart .main-container>.container,body.woocommerce-checkout .main-container>.container,body.top-parent-basket .main-container>.container,body.top-parent-checkout .main-container>.container{width:100%;max-width:1320px;margin-left:auto;margin-right:auto;padding-left:15px;padding-right:15px;box-sizing:border-box}' .
		'body.woocommerce-cart .main-container .row,body.woocommerce-checkout .main-container .row,body.top-parent-basket .main-container .row,body.top-parent-checkout .main-container .row{display:flex;flex-wrap:wrap;align-items:flex-start;margin-left:-15px;margin-right:-15px;box-sizing:border-box}' .
		'body.woocommerce-cart .main-container .col,body.woocommerce-cart .main-container [class*=col-],body.woocommerce-checkout .main-container .col,body.woocommerce-checkout .main-container [class*=col-],body.top-parent-basket .main-container .col,body.top-parent-basket .main-container [class*=col-],body.top-parent-checkout .main-container .col,body.top-parent-checkout .main-container [class*=col-]{position:relative;width:100%;min-height:1px;padding-left:15px;padding-right:15px;box-sizing:border-box}' .
		'body.woocommerce-cart .main-container .col,body.woocommerce-checkout .main-container .col,body.top-parent-basket .main-container .col,body.top-parent-checkout .main-container .col{flex:1 0 0%;max-width:100%}' .
		'body.woocommerce-cart .main-container .col-12,body.woocommerce-checkout .main-container .col-12,body.top-parent-basket .main-container .col-12,body.top-parent-checkout .main-container .col-12{flex:0 0 100%;max-width:100%}' .
		'@media (min-width:992px){body.woocommerce-cart .main-container .col-lg-6,body.woocommerce-checkout .main-container .col-lg-6,body.top-parent-basket .main-container .col-lg-6,body.top-parent-checkout .main-container .col-lg-6{flex:0 0 50%;max-width:50%}}' .
		'body.woocommerce-cart .woocommerce,body.top-parent-basket .woocommerce{width:100%;max-width:100%;box-sizing:border-box}' .
		'body.woocommerce-cart .woocommerce-notices-wrapper,body.top-parent-basket .woocommerce-notices-wrapper,body.basket .woocommerce-notices-wrapper{margin-top:50px!important}' .
		'body.woocommerce-cart .woocommerce-cart-form .quantity,body.top-parent-basket .woocommerce-cart-form .quantity,body.basket .woocommerce-cart-form .quantity{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:0!important;float:none!important;margin:0!important;border-radius:0 15px 0 0!important;overflow:hidden!important;background:var(--tfa-grey-light-color,#f3f3f3)!important;white-space:nowrap!important}' .
		'body.woocommerce-cart .woocommerce-cart-form .quantity .qty,body.top-parent-basket .woocommerce-cart-form .quantity .qty,body.basket .woocommerce-cart-form .quantity .qty{width:58px!important;min-width:58px!important;height:38px!important;margin:0!important;padding:4px 6px!important;border:0!important;background:transparent!important;color:var(--tfa-black-color,#000)!important;text-align:center!important;box-shadow:none!important;appearance:textfield!important;-moz-appearance:textfield!important}' .
		'body.woocommerce-cart .woocommerce-cart-form .quantity .qty::-webkit-outer-spin-button,body.woocommerce-cart .woocommerce-cart-form .quantity .qty::-webkit-inner-spin-button,body.top-parent-basket .woocommerce-cart-form .quantity .qty::-webkit-outer-spin-button,body.top-parent-basket .woocommerce-cart-form .quantity .qty::-webkit-inner-spin-button,body.basket .woocommerce-cart-form .quantity .qty::-webkit-outer-spin-button,body.basket .woocommerce-cart-form .quantity .qty::-webkit-inner-spin-button{margin:0;-webkit-appearance:none}' .
		'body.woocommerce-cart .woocommerce-cart-form .quantity .iws-cart-qty-btn,body.woocommerce-cart .woocommerce-cart-form .quantity .plus,body.woocommerce-cart .woocommerce-cart-form .quantity .minus,body.top-parent-basket .woocommerce-cart-form .quantity .iws-cart-qty-btn,body.top-parent-basket .woocommerce-cart-form .quantity .plus,body.top-parent-basket .woocommerce-cart-form .quantity .minus,body.basket .woocommerce-cart-form .quantity .iws-cart-qty-btn,body.basket .woocommerce-cart-form .quantity .plus,body.basket .woocommerce-cart-form .quantity .minus{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:34px!important;min-width:34px!important;height:38px!important;margin:0!important;padding:0!important;border:0!important;background:transparent!important;color:var(--tfa-black-color,#000)!important;font-size:18px!important;line-height:1!important;cursor:pointer!important}' .
		'body.top-parent-basket .wp-site-blocks>.entry-content,body.basket .wp-site-blocks>.entry-content{width:100%;max-width:1320px;margin-left:auto!important;margin-right:auto!important;padding-left:15px!important;padding-right:15px!important;box-sizing:border-box}' .
		'body.top-parent-basket .wp-site-blocks>.entry-content>.wp-block-woocommerce-cart.alignwide,body.basket .wp-site-blocks>.entry-content>.wp-block-woocommerce-cart.alignwide{width:100%;max-width:100%!important;margin-left:0!important;margin-right:0!important;box-sizing:border-box}' .
		'body.top-parent-basket .wp-block-woocommerce-cart .wc-block-components-sidebar-layout,body.basket .wp-block-woocommerce-cart .wc-block-components-sidebar-layout{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(320px,420px)!important;gap:30px!important;align-items:start!important;width:100%!important;max-width:100%!important;margin:0!important;box-sizing:border-box}' .
		'body.top-parent-basket .wp-block-woocommerce-cart .wc-block-cart__main,body.basket .wp-block-woocommerce-cart .wc-block-cart__main,body.top-parent-basket .wp-block-woocommerce-cart .wc-block-cart__sidebar,body.basket .wp-block-woocommerce-cart .wc-block-cart__sidebar{width:100%!important;max-width:100%!important;float:none!important;margin:0!important;box-sizing:border-box}' .
		'@media (max-width:991.98px){body.top-parent-basket .wp-block-woocommerce-cart .wc-block-components-sidebar-layout,body.basket .wp-block-woocommerce-cart .wc-block-components-sidebar-layout{display:block!important}body.top-parent-basket .wp-block-woocommerce-cart .wc-block-cart__sidebar,body.basket .wp-block-woocommerce-cart .wc-block-cart__sidebar{margin-top:28px!important}}' .
		'body.woocommerce-cart .woocommerce-cart-form,body.top-parent-basket .woocommerce-cart-form{float:none!important;width:100%!important;max-width:none;margin:0;box-sizing:border-box}' .
		'body.woocommerce-cart .woocommerce>.woocommerce-cart-form,body.top-parent-basket .woocommerce>.woocommerce-cart-form{display:block}' .
		'body.woocommerce-cart .cart-collaterals,body.top-parent-basket .cart-collaterals{display:flex;justify-content:flex-end;width:100%!important;clear:both;float:none!important;margin-top:30px;box-sizing:border-box}' .
		'body.woocommerce-cart .cart-collaterals .cart_totals,body.top-parent-basket .cart-collaterals .cart_totals{float:none!important;width:100%!important;max-width:430px;box-sizing:border-box}' .
		'body.woocommerce-cart table.shop_table,body.top-parent-basket table.shop_table{width:100%;table-layout:auto;border-collapse:collapse}' .
		'body.woocommerce-cart table.shop_table th,body.woocommerce-cart table.shop_table td,body.top-parent-basket table.shop_table th,body.top-parent-basket table.shop_table td{vertical-align:middle}' .
		'body.woocommerce-cart .woocommerce-cart-form__cart-item .product-thumbnail,body.top-parent-basket .woocommerce-cart-form__cart-item .product-thumbnail{width:90px}' .
		'body.woocommerce-cart .woocommerce-cart-form__cart-item .product-thumbnail img,body.top-parent-basket .woocommerce-cart-form__cart-item .product-thumbnail img{max-width:70px;height:auto}' .
		'body.woocommerce-cart .wc-proceed-to-checkout .checkout-button,body.top-parent-basket .wc-proceed-to-checkout .checkout-button{display:block;width:100%;text-align:center}' .
		'body.woocommerce-checkout form.checkout.woocommerce-checkout,body.top-parent-checkout form.checkout.woocommerce-checkout{display:block;width:100%;max-width:100%;margin:0;box-sizing:border-box}' .
		'body.woocommerce-checkout form.checkout.woocommerce-checkout>.row,body.top-parent-checkout form.checkout.woocommerce-checkout>.row{display:flex!important;flex-wrap:wrap;align-items:flex-start;margin-left:-15px;margin-right:-15px}' .
		'body.woocommerce-checkout form.checkout.woocommerce-checkout>.row>.col-12,body.top-parent-checkout form.checkout.woocommerce-checkout>.row>.col-12{position:relative;width:100%;padding-left:15px;padding-right:15px;box-sizing:border-box;flex:0 0 100%;max-width:100%}' .
		'@media (min-width:992px){body.woocommerce-checkout form.checkout.woocommerce-checkout>.row>.col-lg-6,body.top-parent-checkout form.checkout.woocommerce-checkout>.row>.col-lg-6{flex:0 0 50%;max-width:50%}}' .
		'body.woocommerce-checkout .woocommerce-billing-fields__field-wrapper,body.woocommerce-checkout .woocommerce-shipping-fields__field-wrapper,body.top-parent-checkout .woocommerce-billing-fields__field-wrapper,body.top-parent-checkout .woocommerce-shipping-fields__field-wrapper{display:flex;flex-wrap:wrap;gap:0 20px}' .
		'body.woocommerce-checkout .form-row,body.top-parent-checkout .form-row{display:block;margin:0 0 14px;box-sizing:border-box}' .
		'body.woocommerce-checkout .form-row-first,body.woocommerce-checkout .form-row-last,body.top-parent-checkout .form-row-first,body.top-parent-checkout .form-row-last{width:calc(50% - 10px);flex:0 0 calc(50% - 10px);float:none!important}' .
		'body.woocommerce-checkout .form-row-wide,body.top-parent-checkout .form-row-wide{width:100%;flex:0 0 100%;float:none!important}' .
		'body.woocommerce-checkout .woocommerce-input-wrapper,body.woocommerce-checkout input.input-text,body.woocommerce-checkout textarea.input-text,body.woocommerce-checkout select,body.woocommerce-checkout .select2-container,body.top-parent-checkout .woocommerce-input-wrapper,body.top-parent-checkout input.input-text,body.top-parent-checkout textarea.input-text,body.top-parent-checkout select,body.top-parent-checkout .select2-container{display:block;width:100%!important;max-width:100%;box-sizing:border-box}' .
		'body.woocommerce-checkout #order_review,body.top-parent-checkout #order_review{width:100%;max-width:100%;box-sizing:border-box}' .
		'body.woocommerce-checkout #order_review table.shop_table,body.top-parent-checkout #order_review table.shop_table{display:table;width:100%!important;table-layout:auto;overflow:visible}' .
		'body.woocommerce-checkout #order_review table.shop_table thead,body.woocommerce-checkout #order_review table.shop_table tbody,body.woocommerce-checkout #order_review table.shop_table tfoot,body.top-parent-checkout #order_review table.shop_table thead,body.top-parent-checkout #order_review table.shop_table tbody,body.top-parent-checkout #order_review table.shop_table tfoot{display:table-row-group;width:auto}' .
		'body.woocommerce-checkout #order_review table.shop_table tr,body.top-parent-checkout #order_review table.shop_table tr{display:table-row;width:auto}' .
		'body.woocommerce-checkout #order_review table.shop_table th,body.woocommerce-checkout #order_review table.shop_table td,body.top-parent-checkout #order_review table.shop_table th,body.top-parent-checkout #order_review table.shop_table td{display:table-cell;width:auto;vertical-align:middle}' .
		'@media (max-width:767.98px){body.woocommerce-checkout .form-row-first,body.woocommerce-checkout .form-row-last,body.top-parent-checkout .form-row-first,body.top-parent-checkout .form-row-last{width:100%;flex-basis:100%}body.woocommerce-cart .cart-collaterals .cart_totals,body.top-parent-basket .cart-collaterals .cart_totals{max-width:none}}'
	);
}, 30 );
