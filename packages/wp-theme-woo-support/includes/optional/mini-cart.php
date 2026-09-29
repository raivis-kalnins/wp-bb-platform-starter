<?php
defined( 'ABSPATH' ) || exit;

function wp_theme_woo_support_mini_cart_drawer() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) return '';
	ob_start();
	?>
	<aside id="wptws-mini-cart" class="wptws-mini-cart" aria-label="<?php esc_attr_e( 'Shopping cart', 'wp-theme-woo-support' ); ?>" role="dialog" aria-modal="true" aria-hidden="true" tabindex="-1">
		<div class="wptws-mini-cart__head">
			<div><span class="wptws-mini-cart__eyebrow"><?php esc_html_e( 'Your selection', 'wp-theme-woo-support' ); ?></span><h2><?php esc_html_e( 'Shopping cart', 'wp-theme-woo-support' ); ?></h2></div>
			<button type="button" class="wptws-mini-cart__close" aria-label="<?php esc_attr_e( 'Close cart', 'wp-theme-woo-support' ); ?>"><span aria-hidden="true">&times;</span></button>
		</div>
		<div class="wptws-mini-cart__body">
			<?php if ( WC()->cart->is_empty() ) : ?>
				<div class="wptws-mini-cart__empty"><span class="wptws-mini-cart__empty-icon" aria-hidden="true">+</span><h3><?php esc_html_e( 'Your cart is ready when you are.', 'wp-theme-woo-support' ); ?></h3><p><?php esc_html_e( 'Browse the catalogue and add products to compare your choices here.', 'wp-theme-woo-support' ); ?></p><a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Continue shopping', 'wp-theme-woo-support' ); ?></a></div>
			<?php else : ?>
				<ul class="wptws-mini-cart__items">
					<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
						$product = isset( $cart_item['data'] ) ? $cart_item['data'] : false;
						if ( ! $product instanceof WC_Product || ! $product->exists() || empty( $cart_item['quantity'] ) ) continue;
						$url = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
						?>
						<li class="wptws-mini-cart__item">
							<div class="wptws-mini-cart__thumb"><?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?></a><?php else : echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); endif; ?></div>
							<div class="wptws-mini-cart__product">
								<?php if ( $url ) : ?><a class="wptws-mini-cart__title" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a><?php else : ?><span class="wptws-mini-cart__title"><?php echo esc_html( $product->get_name() ); ?></span><?php endif; ?>
								<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) ); ?>
								<span class="wptws-mini-cart__meta"><?php echo esc_html( $cart_item['quantity'] ); ?> &times; <?php echo wp_kses_post( WC()->cart->get_product_price( $product ) ); ?></span>
							</div>
							<a class="wptws-mini-cart__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s from cart', 'wp-theme-woo-support' ), $product->get_name() ) ); ?>"><span aria-hidden="true">&times;</span></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php if ( ! WC()->cart->is_empty() ) : ?>
		<div class="wptws-mini-cart__foot">
			<div class="wptws-mini-cart__subtotal"><span><?php esc_html_e( 'Subtotal', 'wp-theme-woo-support' ); ?></span><strong><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></strong></div>
			<p class="wptws-mini-cart__tax-note"><?php esc_html_e( 'Shipping and taxes are calculated at checkout.', 'wp-theme-woo-support' ); ?></p>
			<div class="wptws-mini-cart__buttons"><a class="button wptws-mini-cart__view" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'View cart', 'wp-theme-woo-support' ); ?></a><a class="button checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'wp-theme-woo-support' ); ?></a></div>
		</div>
		<?php endif; ?>
	</aside>
	<?php return (string) ob_get_clean();
}

function wp_theme_woo_support_render_mini_cart() {
	if ( is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) return '';
	return '<div class="wptws-mini-cart-overlay" aria-hidden="true"></div>' . wp_theme_woo_support_mini_cart_drawer();
}

add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'wp-theme-woo-support-mini-cart', wp_theme_woo_support_url( 'assets/css/mini-cart.css' ), array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_script( 'wp-theme-woo-support-mini-cart', wp_theme_woo_support_url( 'assets/js/mini-cart.js' ), array( 'jquery' ), WP_THEME_WOO_SUPPORT_VERSION, true );
} );
add_action( 'wp_footer', function() { echo wp_theme_woo_support_render_mini_cart(); }, 30 );

add_filter( 'woocommerce_add_to_cart_fragments', function( $fragments ) {
	$drawer = wp_theme_woo_support_mini_cart_drawer();
	if ( $drawer ) $fragments['.wptws-mini-cart'] = $drawer;
	if ( function_exists( 'WC' ) && WC()->cart ) {
		$count = (int) WC()->cart->get_cart_contents_count();
		$fragments['.wp-theme-cart-count'] = '<span class="wp-theme-cart-count">' . absint( $count ) . '</span>';
	}
	return $fragments;
} );
