<?php
/**
 * WooCommerce site-wide schema:
 * - Product JSON-LD for products already present in the current response
 * - WebSite SearchAction JSON-LD
 *
 * Add to child theme functions.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output WebSite schema with SearchAction.
 * Uses validator-safe query-input string format.
 */
add_action( 'wp_head', 'tfa_output_website_search_schema', 20 );
function tfa_output_website_search_schema() {
	if ( is_admin() ) {
		return;
	}

	$home_url   = trailingslashit( home_url( '/' ) );
	$search_url = home_url( '/?s={search_term_string}&post_type=product' );

	$data = array(
		'@context' => 'https://schema.org',
		'@type'    => 'WebSite',
		'url'      => $home_url,
		'name'     => get_bloginfo( 'name' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => $search_url,
			'query-input' => 'required name=search_term_string',
		),
	);

	echo '<script type="application/ld+json">' .
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) .
		'</script>' . "\n";
}

/**
 * Output Product schema for all products visible on WooCommerce pages.
 */
add_action( 'wp_footer', 'tfa_output_woocommerce_product_schema_graph', 99 );
function tfa_output_woocommerce_product_schema_graph() {
	if ( is_admin() || ! function_exists( 'is_woocommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	// Limit to Woo/product-related pages only.
	if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_product() && ! is_product_taxonomy() ) {
		return;
	}

	$product_ids = tfa_collect_visible_product_ids();

	if ( empty( $product_ids ) ) {
		return;
	}

	$graph = array();

	foreach ( $product_ids as $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}

		$item = tfa_build_product_schema_item( $product );

		if ( ! empty( $item ) ) {
			$graph[] = $item;
		}
	}

	if ( empty( $graph ) ) {
		return;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">' .
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) .
		'</script>' . "\n";
}

/**
 * Collect product IDs from current view.
 */
function tfa_collect_visible_product_ids() {
	$product_ids = array();

	global $wp_query;

	// Main queried posts (shop, archive, taxonomy, some block contexts).
	if ( ! empty( $wp_query ) && ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) ) {
		foreach ( $wp_query->posts as $post ) {
			if ( isset( $post->post_type ) && 'product' === $post->post_type ) {
				$product_ids[] = (int) $post->ID;
			}
		}
	}

	// Single product page. Related products are already described by their own
	// cards when rendered, so avoid extra product queries and duplicate schema.
	if ( function_exists( 'is_product' ) && is_product() ) {
		$main_product = wc_get_product( get_the_ID() );

		if ( $main_product ) {
			$main_id       = (int) $main_product->get_id();
			$product_ids[] = $main_id;

		}
	}

	$product_ids = array_values(
		array_unique(
			array_filter(
				array_map( 'absint', $product_ids )
			)
		)
	);

	return $product_ids;
}

/**
 * Build one Product schema node.
 */
function tfa_build_product_schema_item( WC_Product $product ) {
	$product_id = $product->get_id();
	$url        = get_permalink( $product_id );

	if ( ! $url ) {
		return array();
	}

	$item = array(
		'@type' => 'Product',
		'@id'   => trailingslashit( $url ) . '#product',
		'name'  => wp_strip_all_tags( $product->get_name() ),
		'url'   => $url,
	);

	// Description.
	$description = $product->get_short_description();
	if ( empty( $description ) ) {
		$description = $product->get_description();
	}
	if ( ! empty( $description ) ) {
		$item['description'] = wp_strip_all_tags( $description );
	}

	// Image.
	$image_id = $product->get_image_id();
	if ( $image_id ) {
		$image_url = wp_get_attachment_url( $image_id );
		if ( $image_url ) {
			$item['image'] = array( $image_url );
		}
	}

	// SKU.
	$sku = $product->get_sku();
	if ( ! empty( $sku ) ) {
		$item['sku'] = $sku;
	}

	// Brand from pa_brand attribute if available.
	if ( taxonomy_exists( 'pa_brand' ) ) {
		$brand_terms = get_the_terms( $product_id, 'pa_brand' );
		if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
			$item['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand_terms[0]->name,
			);
		}
	}

	// Optional MPN.
	$mpn = get_post_meta( $product_id, '_mpn', true );
	if ( ! empty( $mpn ) ) {
		$item['mpn'] = $mpn;
	}

	// Optional GTIN13.
	$gtin13 = get_post_meta( $product_id, '_gtin13', true );
	if ( ! empty( $gtin13 ) ) {
		$gtin13 = preg_replace( '/\D+/', '', $gtin13 );
		if ( ! empty( $gtin13 ) ) {
			$item['gtin13'] = $gtin13;
		}
	}

	// Aggregate rating from WooCommerce reviews.
	$rating_count = (int) $product->get_rating_count();
	$review_count = (int) $product->get_review_count();
	$avg_rating   = (float) $product->get_average_rating();

	if ( $rating_count > 0 && $avg_rating > 0 ) {
		$item['aggregateRating'] = array(
			'@type'       => 'AggregateRating',
			'ratingValue' => $avg_rating,
			'reviewCount' => max( $review_count, $rating_count ),
		);
	}

	$availability = $product->is_in_stock()
		? 'https://schema.org/InStock'
		: 'https://schema.org/OutOfStock';

	$currency = get_woocommerce_currency();

	// Variable products.
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );

		if ( ! empty( $prices['price'] ) ) {
			$values = array_values( $prices['price'] );

			$item['offers'] = array(
				'@type'         => 'AggregateOffer',
				'priceCurrency' => $currency,
				'lowPrice'      => wc_format_decimal( min( $values ), wc_get_price_decimals() ),
				'highPrice'     => wc_format_decimal( max( $values ), wc_get_price_decimals() ),
				'offerCount'    => count( $product->get_children() ),
				'availability'  => $availability,
				'url'           => $url,
			);
		}
	} else {
		$price = $product->get_price();

		if ( '' !== $price && null !== $price ) {
			$item['offers'] = array(
				'@type'         => 'Offer',
				'priceCurrency' => $currency,
				'price'         => wc_format_decimal( $price, wc_get_price_decimals() ),
				'availability'  => $availability,
				'url'           => $url,
			);
		}
	}

	return $item;
}
