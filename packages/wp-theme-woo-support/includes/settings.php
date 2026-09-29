<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_get_settings_pages', function( $pages ) {
	if ( class_exists( 'WC_Settings_Page' ) && ! class_exists( 'WP_Theme_Woo_Support_Settings' ) ) {
		class WP_Theme_Woo_Support_Settings extends WC_Settings_Page {
			public function __construct() {
				$this->id    = 'wp_theme_woo_support';
				$this->label = __( 'Theme Support', 'wp-theme-woo-support' );
				parent::__construct();
			}

			public function get_settings() {
				$settings = array(
					array(
						'title' => __( 'WooCommerce theme support', 'wp-theme-woo-support' ),
						'type'  => 'title',
						'desc'  => __( 'Portable keeps optional modules off. Store enables the modern catalogue, filters, demo importer, product tools and Cart + Quote workflow without legacy checkout/login overrides. Legacy preserves older IWS project behaviour. Individual features below override the profile.', 'wp-theme-woo-support' ),
						'id'    => 'wp_theme_woo_support_options',
					),
					array(
						'title'   => __( 'Compatibility profile', 'wp-theme-woo-support' ),
						'id'      => 'wp_theme_woo_support_profile',
						'type'    => 'select',
						'default' => wp_theme_woo_support_profile(),
						'options' => array(
							'portable' => __( 'Portable (safe defaults)', 'wp-theme-woo-support' ),
							'store'    => __( 'Store (recommended)', 'wp-theme-woo-support' ),
							'legacy'   => __( 'Legacy IWS / WP BBTheme Woo', 'wp-theme-woo-support' ),
						),
					),
				);

				$labels = array(
					'setup' => 'Legacy setup, titles and email behaviour', 'shortcodes' => 'Product shortcodes', 'assets' => 'Legacy asset loading',
					'price_visibility' => 'Hide empty and zero prices', 'archive' => 'Product archive enhancements', 'taxonomy_archives' => 'Product taxonomy archives',
					'single_product' => 'Single-product enhancements', 'gallery_slider' => 'Product gallery slider', 'cart_checkout' => 'Legacy cart and checkout enhancements',
					'stock' => 'Stock display enhancements', 'discount_rules' => 'Discount rules', 'product_schema' => 'Product schema graph',
					'product_filter' => 'AJAX product filter and compare', 'variation_swatches' => 'Variation swatches', 'quote_request' => 'Cart + product quote basket', 'product_ux' => 'Legacy product UI fixes',
					'product_admin' => 'Product admin fields', 'ajax_search' => 'AJAX search block', 'custom_login' => 'Legacy account and login routing',
					'mini_cart' => 'Mini-cart drawer', 'template_overrides' => 'Plugin WooCommerce templates', 'demo_import' => 'Woo demo importer', 'b2b' => 'B2B portal and pricing',
				);

				$current = wp_theme_woo_support_features();
				foreach ( $labels as $key => $label ) {
					$settings[] = array(
						'title'   => __( $label, 'wp-theme-woo-support' ),
						'id'      => 'wp_theme_woo_support_feature_' . $key,
						'type'    => 'checkbox',
						'default' => ! empty( $current[ $key ] ) ? 'yes' : 'no',
					);
				}

				$settings[] = array( 'type' => 'sectionend', 'id' => 'wp_theme_woo_support_options' );
				return $settings;
			}
		}

		$pages[] = new WP_Theme_Woo_Support_Settings();
	}

	return $pages;
} );
