<?php
/**
 * WooCommerce demo importer for the child theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_wpbb_child_woo_import_demo', 'wpbb_child_woo_ajax_import_demo' );
add_action( 'wp_theme_before_demo_import', 'wpbb_child_woo_prepare_sector_demo', 10, 1 );
add_filter( 'wp_theme_demo_commerce_home_sections', 'wpbb_child_woo_demo_home_sections', 10, 2 );
add_filter( 'wp_theme_demo_navigation_items', 'wpbb_child_woo_demo_navigation_items', 10, 2 );
add_filter( 'wp_theme_demo_import_message', 'wpbb_child_woo_demo_import_message', 10, 2 );

function wpbb_child_woo_ajax_import_demo() {
	check_ajax_referer( 'wp_theme_settings_nonce', 'nonce' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'wp-theme-woo-support' ) ), 403 );
	}
	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Product_Simple' ) ) {
		wp_send_json_error( array( 'message' => __( 'WooCommerce must be active before importing the WooCommerce demo.', 'wp-theme-woo-support' ) ), 400 );
	}
	if ( function_exists( 'wp_theme_import_demo_homepage' ) ) {
		$page_id = wp_theme_import_demo_homepage();
		$result  = is_wp_error( $page_id ) ? $page_id : array(
			'message'  => function_exists( 'wp_theme_demo_import_message' ) ? wp_theme_demo_import_message() : __( 'WooCommerce demo imported.', 'wp-theme-woo-support' ),
			'shopUrl'  => wc_get_page_permalink( 'shop' ),
			'products' => count( wpbb_child_woo_demo_product_data() ),
		);
	} else {
		$result = wpbb_child_woo_import_demo_store();
	}
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
	}
	wp_send_json_success( $result );
}

function wpbb_child_woo_import_demo_store() {
	wpbb_child_woo_demo_setup_pages();
	wpbb_child_woo_demo_setup_terms();
	$product_ids = wpbb_child_woo_demo_create_products();
	wpbb_child_woo_demo_theme_mods();
	update_option( 'woocommerce_catalog_columns', 4 );
	update_option( 'woocommerce_catalog_rows', 4 );
	update_option( 'woocommerce_shop_page_display', '' );
	update_option( 'woocommerce_category_archive_display', '' );
	update_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
	update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
	update_option( 'iws_product_filter_settings', array_merge( iws_filter_get_settings(), array(
		'enabled' => 'yes', 'instant' => 'yes', 'posts_per_page' => 8,
		'show_price' => 'yes', 'show_height' => 'no', 'show_width' => 'no', 'show_length' => 'no', 'show_attributes' => 'yes',
	) ) );
	flush_rewrite_rules( false );
	return array(
		'message' => sprintf( __( 'WooCommerce demo imported with %d demo products, galleries, variations, shop pages and menu.', 'wp-theme-woo-support' ), count( $product_ids ) ),
		'shopUrl' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		'products' => count( $product_ids ),
	);
}

function wpbb_child_woo_prepare_sector_demo( $profile ) {
	$commerce  = ! function_exists( 'wp_theme_demo_commerce_enabled' ) || wp_theme_demo_commerce_enabled( $profile );
	$profile_id = $commerce && ! empty( $profile['id'] ) ? sanitize_key( $profile['id'] ) : '';
	wpbb_child_woo_demo_remove_other_profile_products( $profile_id );

	if ( ! $commerce ) {
		return;
	}

	wpbb_child_woo_demo_setup_pages();
	wpbb_child_woo_demo_setup_terms();
	wpbb_child_woo_demo_create_products();
	wpbb_child_woo_demo_theme_mods();
	update_option( 'woocommerce_catalog_columns', 4 );
	update_option( 'woocommerce_catalog_rows', 4 );
	update_option( 'woocommerce_shop_page_display', '' );
	update_option( 'woocommerce_category_archive_display', '' );
	update_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
	update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
	update_option( 'iws_product_filter_settings', array_merge( iws_filter_get_settings(), array(
		'enabled' => 'yes', 'instant' => 'yes', 'posts_per_page' => 8,
		'show_price' => 'yes', 'show_height' => 'no', 'show_width' => 'no', 'show_length' => 'no', 'show_attributes' => 'yes',
	) ) );
	flush_rewrite_rules( false );
}

function wpbb_child_woo_demo_remove_other_profile_products( $profile_id ) {
	$ids = get_posts( array(
		'post_type' => 'product',
		'post_status' => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_key' => '_wpbb_child_woo_demo_product',
		'meta_value' => 1,
	) );
	foreach ( $ids as $id ) {
		$existing_profile = sanitize_key( (string) get_post_meta( $id, '_wp_theme_demo_product_profile', true ) );
		if ( '' === $profile_id || $existing_profile !== $profile_id ) {
			wp_delete_post( $id, true );
		}
	}
}

function wpbb_child_woo_demo_home_sections( $content, $profile ) {
	if ( function_exists( 'wp_theme_demo_commerce_enabled' ) && ! wp_theme_demo_commerce_enabled( $profile ) ) {
		return $content;
	}

	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$content .= '<!-- wp:wpbb/row {"gutterX":"gx-4","gutterY":"gy-4","containerClass":"container","customClasses":"wp-theme-sector-section wp-theme-sector-shop","anchor":"shop","uniqueId":"wpbb-sector-shop"} --><!-- wp:wpbb/column {"xs":12,"uniqueId":"wpbb-sector-shop-column"} -->';
	$content .= '<!-- wp:group {"className":"wp-theme-sector-section-heading"} --><div class="wp-block-group wp-theme-sector-section-heading"><div><!-- wp:paragraph {"className":"wp-theme-sector-eyebrow"} --><p class="wp-theme-sector-eyebrow">' . esc_html__( 'WooCommerce shop', 'wp-theme-woo-support' ) . '</p><!-- /wp:paragraph --><!-- wp:heading {"level":2} --><h2 class="wp-block-heading">' . esc_html__( 'Browse the latest products.', 'wp-theme-woo-support' ) . '</h2><!-- /wp:heading --></div><!-- wp:paragraph --><p><a href="' . esc_url( $shop_url ) . '">' . esc_html__( 'View the full shop', 'wp-theme-woo-support' ) . ' &rarr;</a></p><!-- /wp:paragraph --></div><!-- /wp:group -->';
	$content .= '<!-- wp:shortcode -->[iws_product_filter posts_per_page="8"]<!-- /wp:shortcode --><!-- wp:shortcode -->[iws_product_filter_results posts_per_page="8"]<!-- /wp:shortcode -->';
	$content .= '<!-- /wp:wpbb/column --><!-- /wp:wpbb/row -->';

	return $content;
}

function wpbb_child_woo_demo_navigation_items( $items, $profile ) {
	if ( function_exists( 'wp_theme_demo_commerce_enabled' ) && ! wp_theme_demo_commerce_enabled( $profile ) ) {
		return $items;
	}

	$shop = array(
		'key'       => 'shop',
		'title'     => __( 'Shop', 'wp-theme-woo-support' ),
		'slug'      => 'shop',
		'locations' => array( 'header', 'footer' ),
	);
	array_splice( $items, min( 1, count( $items ) ), 0, array( $shop ) );

	// Build a useful real dropdown in Appearance > Menus from the demo catalogue.
	$category_names = array();
	foreach ( wpbb_child_woo_demo_product_data() as $product ) {
		if ( ! empty( $product[2] ) ) {
			$category_names[] = $product[2];
		}
	}
	$category_names = array_slice( array_values( array_unique( $category_names ) ), 0, 4 );
	$category_items = array();
	foreach ( $category_names as $category_name ) {
		$term = get_term_by( 'name', $category_name, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		$category_items[] = array(
			'key'        => 'product-cat-' . $term->slug,
			'title'      => $term->name,
			'term_id'    => (int) $term->term_id,
			'taxonomy'   => 'product_cat',
			'parent_key' => 'shop',
			'locations'  => array( 'header' ),
		);
	}
	array_splice( $items, min( 2, count( $items ) ), 0, $category_items );

	$items[] = array( 'key' => 'my-account', 'title' => __( 'My account', 'wp-theme-woo-support' ), 'slug' => 'my-account', 'locations' => array( 'footer', 'top' ) );
	$items[] = array( 'key' => 'cart', 'title' => __( 'Cart', 'wp-theme-woo-support' ), 'slug' => 'cart', 'locations' => array( 'footer' ) );

	return $items;
}

function wpbb_child_woo_demo_import_message( $message, $profile ) {
	if ( function_exists( 'wp_theme_demo_commerce_enabled' ) && ! wp_theme_demo_commerce_enabled( $profile ) ) {
		return $message;
	}

	return $message . ' ' . sprintf( __( '%d WooCommerce demo products, shop pages, filters and load-more results were added.', 'wp-theme-woo-support' ), count( wpbb_child_woo_demo_product_data() ) );
}

function wpbb_child_woo_demo_setup_pages() {
	$pages = array(
		'shop' => array( 'Shop', '<!-- wp:woocommerce/product-catalog /-->' ),
		'cart' => array( 'Cart', '<!-- wp:woocommerce/cart /-->' ),
		'checkout' => array( 'Checkout', '<!-- wp:woocommerce/checkout /-->' ),
		'my-account' => array( 'My account', '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->' ),
	);
	foreach ( $pages as $slug => $data ) {
		$page = get_page_by_path( $slug );
		$args = array( 'post_title' => $data[0], 'post_name' => $slug, 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $data[1] );
		if ( $page instanceof WP_Post ) {
			$args['ID'] = $page->ID;
			$id = wp_update_post( $args, true );
		} else {
			$id = wp_insert_post( $args, true );
		}
		if ( ! is_wp_error( $id ) ) {
			update_option( 'woocommerce_' . str_replace( '-', '_', $slug ) . '_page_id', (int) $id );
		}
	}
}

function wpbb_child_woo_demo_setup_terms() {
	$cats = array();
	foreach ( wpbb_child_woo_demo_product_data() as $product ) {
		if ( ! empty( $product[2] ) ) {
			$cats[] = $product[2];
		}
	}
	$cats = array_unique( $cats );
	foreach ( $cats as $cat ) {
		if ( ! term_exists( $cat, 'product_cat' ) ) {
			wp_insert_term( $cat, 'product_cat' );
		}
	}
	$attributes = array( 'color' => array(), 'size' => array() );
	foreach ( wpbb_child_woo_demo_product_data() as $product ) {
		if ( empty( $product[0] ) || 'variable' !== $product[0] ) {
			continue;
		}
		$options = wpbb_child_woo_demo_variation_options( $product );
		$attributes['color'] = array_merge( $attributes['color'], $options['colors'] );
		$attributes['size']  = array_merge( $attributes['size'], $options['sizes'] );
	}
	$attributes['color'] = array_values( array_unique( $attributes['color'] ?: array( 'Blue', 'Black', 'Grey' ) ) );
	$attributes['size']  = array_values( array_unique( $attributes['size'] ?: array( 'Small', 'Medium', 'Large' ) ) );
	foreach ( $attributes as $slug => $terms ) {
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			wc_create_attribute( array( 'name' => ucfirst( $slug ), 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ) );
			delete_transient( 'wc_attribute_taxonomies' );
			register_taxonomy( $taxonomy, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
		}
		foreach ( $terms as $term ) {
			if ( ! term_exists( $term, $taxonomy ) ) {
				wp_insert_term( $term, $taxonomy );
			}
		}
	}
}

function wpbb_child_woo_demo_svg_attachment( $title, $subtitle, $index ) {
	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$slug = sanitize_title( $title . '-' . $subtitle . '-' . $index );
	$path = trailingslashit( $upload['path'] ) . $slug . '.svg';
	$url  = trailingslashit( $upload['url'] ) . $slug . '.svg';
	$colors = array( '#eff6ff', '#e2e8f0', '#dbeafe', '#f8fafc', '#e5e7eb', '#d1d5db' );
	$bg = $colors[ $index % count( $colors ) ];
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="900" viewBox="0 0 1200 900"><rect width="1200" height="900" fill="' . esc_attr( $bg ) . '"/><rect x="70" y="70" width="1060" height="760" rx="44" fill="#ffffff" stroke="#cbd5e1" stroke-width="4"/><circle cx="970" cy="210" r="72" fill="#2563eb" opacity="0.14"/><circle cx="240" cy="690" r="110" fill="#0f172a" opacity="0.08"/><text x="600" y="410" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="58" font-weight="700" fill="#0f172a">' . esc_html( $title ) . '</text><text x="600" y="485" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="30" fill="#475569">' . esc_html( $subtitle ) . '</text></svg>';
	if ( ! file_exists( $path ) ) {
		file_put_contents( $path, $svg );
	}
	$existing = get_posts( array( 'post_type' => 'attachment', 'name' => $slug, 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( $existing ) {
		wpbb_child_woo_demo_svg_metadata( (int) $existing[0], $path, $upload['basedir'] );
		return (int) $existing[0];
	}
	$id = wp_insert_attachment( array( 'post_mime_type' => 'image/svg+xml', 'post_title' => $title . ' ' . $subtitle, 'post_name' => $slug, 'post_status' => 'inherit', 'guid' => $url ), $path );
	if ( ! is_wp_error( $id ) ) {
		wpbb_child_woo_demo_svg_metadata( (int) $id, $path, $upload['basedir'] );
	}
	return is_wp_error( $id ) ? 0 : (int) $id;
}

function wpbb_child_woo_demo_svg_metadata( $attachment_id, $path, $base_dir ) {
	$relative = ltrim( str_replace( wp_normalize_path( $base_dir ), '', wp_normalize_path( $path ) ), '/' );
	update_post_meta( $attachment_id, '_wp_attachment_metadata', array(
		'width' => 1200,
		'height' => 900,
		'file' => $relative,
		'sizes' => array(),
	) );
}

function wpbb_child_woo_demo_local_attachment( $path, $title ) {
	if ( ! is_string( $path ) || ! is_readable( $path ) ) {
		return 0;
	}
	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$extension = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
	$extension = in_array( $extension, array( 'jpg', 'jpeg', 'png', 'webp' ), true ) ? $extension : 'jpg';
	$slug      = sanitize_title( 'demo-' . $title );
	$filename  = wp_unique_filename( $upload['path'], $slug . '.' . $extension );
	$target    = trailingslashit( $upload['path'] ) . $filename;
	$existing  = get_posts( array( 'post_type' => 'attachment', 'name' => $slug, 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids' ) );

	if ( $existing ) {
		return (int) $existing[0];
	}
	if ( ! copy( $path, $target ) ) {
		return 0;
	}
	$filetype = wp_check_filetype( $target );
	$id = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => $title,
		'post_name'      => $slug,
		'post_status'    => 'inherit',
		'guid'           => trailingslashit( $upload['url'] ) . $filename,
	), $target );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $target ) );

	return (int) $id;
}

function wpbb_child_woo_demo_variation_options( $product_data = array(), $profile = null ) {
	$profile = is_array( $profile ) ? $profile : ( function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array() );
	$options = array(
		'colors' => array( 'Blue', 'Black', 'Grey' ),
		'sizes'  => array( 'Small', 'Medium', 'Large' ),
	);
	$options = apply_filters( 'wp_theme_woo_demo_variation_options', $options, $product_data, $profile );
	$options = is_array( $options ) ? $options : array();

	return array(
		'colors' => array_values( array_filter( array_map( 'sanitize_text_field', isset( $options['colors'] ) ? (array) $options['colors'] : array() ) ) ),
		'sizes'  => array_values( array_filter( array_map( 'sanitize_text_field', isset( $options['sizes'] ) ? (array) $options['sizes'] : array() ) ) ),
	);
}

function wpbb_child_woo_demo_product_data() {
	$data = array(
		array( 'simple', 'Everyday Backpack', 'Accessories', 49, 'Durable everyday backpack with padded laptop storage.' ),
		array( 'simple', 'Desk Lamp', 'Home & Living', 35, 'Adjustable LED desk lamp for modern workspaces.' ),
		array( 'simple', 'Ceramic Mug Set', 'Home & Living', 24, 'Set of two premium ceramic mugs.' ),
		array( 'simple', 'Wireless Charger', 'Tech', 29, 'Fast wireless charging pad with non-slip base.' ),
		array( 'simple', 'Notebook Pack', 'Office', 18, 'Three soft-touch notebooks for planning and notes.' ),
		array( 'simple', 'Travel Bottle', 'Accessories', 22, 'Insulated bottle for hot and cold drinks.' ),
		array( 'simple', 'Cable Organiser', 'Tech', 16, 'Compact organiser for chargers and cables.' ),
		array( 'simple', 'Desk Mat', 'Office', 32, 'Large desk mat with smooth writing surface.' ),
		array( 'simple', 'Gift Bundle', 'Bundles', 89, 'Curated starter bundle with popular accessories.' ),
		array( 'simple', 'Minimal Wall Clock', 'Home & Living', 42, 'Quiet wall clock with clean modern styling.' ),
		array( 'simple', 'Laptop Stand', 'Office', 55, 'Aluminium laptop stand for better desk ergonomics.' ),
		array( 'simple', 'Bluetooth Speaker', 'Tech', 64, 'Portable speaker with balanced everyday sound.' ),
		array( 'variable', 'Classic Hoodie', 'Apparel', 59, 'Soft hoodie available in multiple colours and sizes.' ),
		array( 'variable', 'Premium T-Shirt', 'Apparel', 28, 'Premium cotton T-shirt with size and colour options.' ),
		array( 'variable', 'Canvas Tote Bag', 'Accessories', 26, 'Reusable tote bag in several colour options.' ),
		array( 'variable', 'Office Chair', 'Office', 149, 'Comfortable office chair with colour choices.' ),
		array( 'variable', 'Throw Cushion', 'Home & Living', 34, 'Decor cushion available in three colours.' ),
		array( 'variable', 'Smart Watch Strap', 'Tech', 21, 'Replacement watch strap in sizes and colours.' ),
	);

	$profile = function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array();

	return apply_filters( 'wp_theme_woo_demo_product_data', $data, $profile );
}

function wpbb_child_woo_demo_create_products() {
	$ids = array();
	$profile = function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array( 'id' => 'store' );
	$sku_prefix = strtoupper( substr( preg_replace( '/[^a-z0-9]/i', '', isset( $profile['id'] ) ? $profile['id'] : 'store' ), 0, 8 ) );
	$color_tax = wc_attribute_taxonomy_name( 'color' );
	$size_tax  = wc_attribute_taxonomy_name( 'size' );
	foreach ( wpbb_child_woo_demo_product_data() as $i => $data ) {
		list( $type, $name, $cat, $price, $desc ) = $data;
		$existing = get_page_by_path( sanitize_title( $name ), OBJECT, 'product' );
		$product = ( 'variable' === $type ) ? new WC_Product_Variable( $existing ? $existing->ID : 0 ) : new WC_Product_Simple( $existing ? $existing->ID : 0 );
		$product->set_name( $name );
		$product->set_slug( sanitize_title( $name ) );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_short_description( $desc );
		$product->set_description( '<p>' . esc_html( $desc ) . '</p><ul><li>Demo-ready product content</li><li>Gallery images included</li><li>Configured for the sample shop</li></ul>' );
		$product->set_sku( 'DEMO-' . $sku_prefix . '-' . str_pad( (string) ( $i + 1 ), 3, '0', STR_PAD_LEFT ) );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 30 + $i );
		$product->set_stock_status( 'instock' );
		if ( 'simple' === $type ) {
			$product->set_regular_price( (string) $price );
		}
		$local_image = apply_filters( 'wp_theme_woo_demo_product_image_path', '', $data, $i, $profile );
		$image_id    = $local_image ? wpbb_child_woo_demo_local_attachment( $local_image, $name ) : wpbb_child_woo_demo_svg_attachment( $name, 'Main image', $i + 1 );
		$gallery     = $local_image ? array() : array(
			wpbb_child_woo_demo_svg_attachment( $name, 'Gallery view 1', $i + 30 ),
			wpbb_child_woo_demo_svg_attachment( $name, 'Gallery view 2', $i + 60 ),
		);
		if ( $image_id ) {
			$product->set_image_id( $image_id );
		}
		$product->set_gallery_image_ids( array_filter( $gallery ) );
		$product_id = $product->save();
		wp_set_object_terms( $product_id, $cat, 'product_cat' );
		if ( 'variable' === $type ) {
			wpbb_child_woo_demo_configure_variable_product( $product_id, $price, $color_tax, $size_tax, $data, $profile );
		}
		update_post_meta( $product_id, '_wpbb_child_woo_demo_product', 1 );
		update_post_meta( $product_id, '_wp_theme_demo_product_profile', sanitize_key( isset( $profile['id'] ) ? $profile['id'] : 'store' ) );
		$ids[] = $product_id;
	}
	delete_transient( 'iws_filter_variation_attribute_options_v2' );
	delete_transient( 'iws_filter_bounds__price' );
	wc_delete_product_transients();
	return $ids;
}

function wpbb_child_woo_demo_configure_variable_product( $product_id, $base_price, $color_tax, $size_tax, $product_data = array(), $profile = null ) {
	$options = wpbb_child_woo_demo_variation_options( $product_data, $profile );
	$colors  = $options['colors'];
	$sizes   = $options['sizes'];
	if ( ! $colors || ! $sizes ) {
		return;
	}
	wp_set_object_terms( $product_id, $colors, $color_tax );
	wp_set_object_terms( $product_id, $sizes, $size_tax );
	$product = wc_get_product( $product_id );
	$attrs = array();
	foreach ( array( $color_tax => $colors, $size_tax => $sizes ) as $tax => $terms ) {
		$attr = new WC_Product_Attribute();
		$attr->set_id( wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $tax ) ) );
		$attr->set_name( $tax );
		$attr->set_options( array_map( function( $term ) use ( $tax ) { $obj = get_term_by( 'name', $term, $tax ); return $obj ? (int) $obj->term_id : 0; }, $terms ) );
		$attr->set_visible( true );
		$attr->set_variation( true );
		$attrs[] = $attr;
	}
	$product->set_attributes( $attrs );
	$product->save();
	foreach ( get_children( array( 'post_parent' => $product_id, 'post_type' => 'product_variation', 'fields' => 'ids', 'post_status' => array( 'publish', 'private' ) ) ) as $child_id ) {
		wp_delete_post( $child_id, true );
	}
	$combos = array();
	foreach ( $colors as $color ) {
		foreach ( $sizes as $size ) {
			$combos[] = array( $color, $size );
		}
	}
	foreach ( $combos as $idx => $combo ) {
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product_id );
		$variation->set_attributes( array( $color_tax => sanitize_title( $combo[0] ), $size_tax => sanitize_title( $combo[1] ) ) );
		$variation->set_regular_price( (string) $base_price );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 15 + $idx );
		$variation->set_stock_status( 'instock' );
		$variation->set_sku( get_post_meta( $product_id, '_sku', true ) . '-V' . ( $idx + 1 ) );
		$variation->save();
	}
	WC_Product_Variable::sync( $product_id );
}

function wpbb_child_woo_demo_theme_mods() {
    set_theme_mod( 'woocommerce_catalog_columns', 4 );
    set_theme_mod( 'woocommerce_catalog_rows', 4 );
}
