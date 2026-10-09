<?php
/**
 * Plugin Name: WP Theme Woo Support
 * Plugin URI:  https://github.com/The-Fuel-Agency/wp-theme-woo-support
 * Description: Modular WooCommerce support for WP BBTheme stores: catalogue filters, swatches, complex variations, Cart + Quote requests, schema and demo tooling.
 * Version:     3.8.5
 * Author:      The Fuel Agency
 * Text Domain: wp-theme-woo-support
 * Requires PHP: 8.0
 * Requires at least: 6.6
 * Tested up to: 7.1
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_THEME_WOO_SUPPORT_VERSION', '3.8.5' );
define( 'WP_THEME_WOO_SUPPORT_FILE', __FILE__ );
define( 'WP_THEME_WOO_SUPPORT_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_THEME_WOO_SUPPORT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Return the active compatibility profile.
 *
 * Portable is intentionally non-invasive. Store is the recommended profile
 * for the packaged ecommerce child themes. Legacy keeps the historical IWS
 * behaviour available for older projects that still rely on it.
 */
function wp_theme_woo_support_profile() {
	$saved = get_option( 'wp_theme_woo_support_profile', '' );
	if ( in_array( $saved, array( 'portable', 'store', 'legacy' ), true ) ) {
		return $saved;
	}

	return sanitize_key( apply_filters( 'wp_theme_woo_support_default_profile', 'portable' ) );
}

/**
 * Feature registry by compatibility profile.
 */
function wp_theme_woo_support_features() {
	$profile = wp_theme_woo_support_profile();
	$legacy  = 'legacy' === $profile;
	$store   = 'store' === $profile;

	$features = array(
		'setup'              => $legacy,
		'shortcodes'         => $store || $legacy,
		'assets'             => $store || $legacy,
		'price_visibility'   => $store || $legacy,
		'archive'            => $store || $legacy,
		'taxonomy_archives'  => $store || $legacy,
		'single_product'     => $store || $legacy,
		'gallery_slider'     => $store || $legacy,
		'cart_checkout'      => $legacy,
		'stock'              => $store || $legacy,
		'discount_rules'     => $store || $legacy,
		'product_schema'     => $store || $legacy,
		'product_filter'     => $store || $legacy,
		'variation_swatches' => $store || $legacy,
		'quote_request'      => $store || $legacy,
		'product_ux'         => $legacy,
		'product_admin'      => $store || $legacy,
		'ajax_search'        => $store || $legacy,
		'custom_login'       => $legacy,
		'mini_cart'          => $store || $legacy,
		'template_overrides' => $legacy,
		'demo_import'        => $store || $legacy,
		'marketing_intelligence' => $store || $legacy,
		'crm_intelligence'   => $store || $legacy,
		'product_sync'       => $store || $legacy,
		'b2b'                => $store || $legacy,
	);

	foreach ( $features as $feature => $enabled ) {
		$saved = get_option( 'wp_theme_woo_support_feature_' . $feature, null );
		if ( null !== $saved ) {
			$features[ $feature ] = 'yes' === $saved;
		}
	}

	return apply_filters( 'wp_theme_woo_support_features', $features, $profile );
}

function wp_theme_woo_support_feature_enabled( $feature ) {
	$features = wp_theme_woo_support_features();
	$enabled  = ! empty( $features[ $feature ] );

	return (bool) apply_filters( 'wp_theme_woo_support_feature_enabled', $enabled, $feature, $features );
}

function wp_theme_woo_support_path( $relative = '' ) {
	return WP_THEME_WOO_SUPPORT_DIR . ltrim( $relative, '/' );
}

function wp_theme_woo_support_url( $relative = '' ) {
	return WP_THEME_WOO_SUPPORT_URL . ltrim( $relative, '/' );
}

function wp_theme_woo_support_require( $relative ) {
	$path = wp_theme_woo_support_path( $relative );
	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

add_action( 'after_setup_theme', function() {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}, 20 );

add_action( 'after_setup_theme', function() {
	load_plugin_textdomain( 'wp-theme-woo-support', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	wp_theme_woo_support_require( 'includes/settings.php' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$modules = array(
		'setup'              => 'setup.php',
		'shortcodes'         => 'iws-shortcodes.php',
		'assets'             => 'assets.php',
		'price_visibility'   => 'price-visibility.php',
		'archive'            => 'archive.php',
		'taxonomy_archives'  => 'taxonomy-archives.php',
		'single_product'     => 'single.php',
		'gallery_slider'     => 'gallery-slider.php',
		'cart_checkout'      => 'cart-checkout.php',
		'stock'              => 'stock.php',
		'discount_rules'     => 'discount-rules.php',
		'product_schema'     => 'woo-schema.php',
		'product_filter'     => 'woo-filter.php',
		'variation_swatches' => 'variation-swatches.php',
		'quote_request'      => 'quote-request.php',
		'product_ux'         => 'product-ux-fixes.php',
		'b2b'                => 'b2b.php',
		'marketing_intelligence' => 'marketing-intelligence.php',
		'crm_intelligence'   => 'crm-intelligence.php',
		'product_sync'       => 'product-sync.php',
	);

	foreach ( $modules as $feature => $file ) {
		if ( wp_theme_woo_support_feature_enabled( $feature ) ) {
			wp_theme_woo_support_require( 'includes/modules/' . $file );
		}
	}

	if ( is_admin() && wp_theme_woo_support_feature_enabled( 'product_admin' ) ) {
		wp_theme_woo_support_require( 'includes/modules/admin.php' );
	}

	if ( wp_theme_woo_support_feature_enabled( 'custom_login' ) ) {
		wp_theme_woo_support_require( 'includes/optional/custom-login.php' );
	}

	if ( wp_theme_woo_support_feature_enabled( 'ajax_search' ) && ! function_exists( 'wp_ajax_search_allowed_post_types' ) ) {
		wp_theme_woo_support_require( 'includes/optional/ajax-search-block.php' );
	}

	if ( wp_theme_woo_support_feature_enabled( 'mini_cart' ) ) {
		wp_theme_woo_support_require( 'includes/optional/mini-cart.php' );
	}

	if ( wp_theme_woo_support_feature_enabled( 'demo_import' ) ) {
		wp_theme_woo_support_require( 'includes/modules/demo-import.php' );
	}
}, 5 );

/**
 * Plugin template overrides are opt-in and never take precedence over a theme's
 * own WooCommerce override.
 */
add_filter( 'woocommerce_locate_template', function( $template, $template_name ) {
	if ( ! wp_theme_woo_support_feature_enabled( 'template_overrides' ) ) {
		return $template;
	}

	$normalized = wp_normalize_path( $template );
	$theme_dirs = array_filter( array_unique( array(
		wp_normalize_path( get_stylesheet_directory() ),
		wp_normalize_path( get_template_directory() ),
	) ) );

	foreach ( $theme_dirs as $theme_dir ) {
		if ( 0 === strpos( $normalized, trailingslashit( $theme_dir ) ) ) {
			return $template;
		}
	}

	$candidate = wp_theme_woo_support_path( 'templates/woocommerce/' . ltrim( $template_name, '/' ) );

	return is_readable( $candidate ) ? $candidate : $template;
}, 20, 2 );

add_action( 'wp_enqueue_scripts', function() {
	if ( ! wp_theme_woo_support_feature_enabled( 'template_overrides' ) ) {
		return;
	}

	$is_template_screen = ( function_exists( 'is_account_page' ) && is_account_page() )
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() );

	if ( $is_template_screen ) {
		wp_enqueue_style(
			'wp-theme-woo-support-templates',
			wp_theme_woo_support_url( 'assets/css/templates.css' ),
			array(),
			WP_THEME_WOO_SUPPORT_VERSION
		);
	}
} );
