<?php
/**
 * Theme-native WooCommerce gallery slider.
 */
defined( 'ABSPATH' ) || exit;

const IWS_GALLERY_SLIDER_OPTION_PREFIX = 'iws_gallery_slider_';

function iws_gallery_slider_get_option( $key, $default = '' ) {
	$value = get_option( IWS_GALLERY_SLIDER_OPTION_PREFIX . $key, $default );
	return ( '' === $value || null === $value ) ? $default : $value;
}

function iws_gallery_slider_is_enabled() {
	return 'yes' === iws_gallery_slider_get_option( 'enabled', 'yes' );
}

add_action( 'after_setup_theme', function() {
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}, 20 );

add_filter( 'woocommerce_get_sections_products', function( $sections ) {
	$sections['iws_gallery_slider'] = __( 'Gallery Slider', 'wp' );
	return $sections;
} );

add_filter( 'woocommerce_get_settings_products', function( $settings, $current_section ) {
	if ( 'iws_gallery_slider' !== $current_section ) {
		return $settings;
	}

	return array(
		array(
			'title' => __( 'Product Gallery Slider', 'wp' ),
			'type'  => 'title',
			'desc'  => __( 'Theme-native replacement for gallery slider plugins. Uses the existing site Swiper when present and falls back safely when not present.', 'wp' ),
			'id'    => 'iws_gallery_slider_options',
		),
		array(
			'title'   => __( 'Enable gallery slider', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'enabled',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Gallery layout', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'thumb_position',
			'default' => 'left',
			'type'    => 'select',
			'options' => array(
				'left'   => __( 'Small thumbnails left, large image right', 'wp' ),
				'right'  => __( 'Large image left, small thumbnails right', 'wp' ),
				'bottom' => __( 'Thumbnails bottom', 'wp' ),
			),
		),
		array(
			'title'   => __( 'Image height mode', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'height_mode',
			'default' => 'dynamic',
			'type'    => 'select',
			'options' => array(
				'dynamic' => __( 'Dynamic by image ratio, capped by max height', 'wp' ),
				'fixed'   => __( 'Fixed max-height box', 'wp' ),
			),
		),
		array(
			'title'             => __( 'Desktop max image height', 'wp' ),
			'id'                => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'max_height',
			'default'           => 540,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 320, 'max' => 900, 'step' => 10 ),
		),
		array(
			'title'             => __( 'Vertical thumbnails width', 'wp' ),
			'id'                => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'thumb_width',
			'default'           => 72,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 48, 'max' => 140, 'step' => 1 ),
		),
		array(
			'title'             => __( 'Thumbnail gap', 'wp' ),
			'id'                => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'thumb_gap',
			'default'           => 10,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 0, 'max' => 40, 'step' => 1 ),
		),
		array(
			'title'             => __( 'Thumbnails visible', 'wp' ),
			'id'                => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'thumbs_visible',
			'default'           => 6,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 2, 'max' => 12, 'step' => 1 ),
		),
		array(
			'title'   => __( 'Show arrows', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'arrows',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Use Swiper when available', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'swiper',
			'default' => 'yes',
			'type'    => 'checkbox',
			'desc'    => __( 'No CDN is loaded. This uses window.Swiper if your theme already loads it.', 'wp' ),
		),
		array(
			'title'   => __( 'Performance mode', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'performance_mode',
			'default' => 'yes',
			'type'    => 'checkbox',
			'desc'    => __( 'Auto-reduces heavy effects on low-memory devices or when reduced motion is enabled.', 'wp' ),
		),
		array(
			'title'   => __( 'Use WooCommerce zoom', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'woo_zoom',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Hover-follow zoom polish', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'hover_follow_zoom',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Enable lightbox', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'lightbox',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Infinite loop', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'loop',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Enable gallery video support', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'video_support',
			'default' => 'yes',
			'type'    => 'checkbox',
			'desc'    => __( 'Reads video URLs from the product custom fields below. Supports YouTube, Vimeo, and direct mp4/webm links.', 'wp' ),
		),
		array(
			'title'   => __( 'Video custom fields', 'wp' ),
			'id'      => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'video_meta_keys',
			'default' => '_iws_product_gallery_videos,product_video_url,_product_video_url,_wcgs_product_video',
			'type'    => 'text',
			'desc'    => __( 'Comma-separated product meta keys. Each field can contain one URL or multiple URLs separated by commas/new lines.', 'wp' ),
		),
		array(
			'title'             => __( 'Slider speed', 'wp' ),
			'id'                => IWS_GALLERY_SLIDER_OPTION_PREFIX . 'speed',
			'default'           => 250,
			'type'              => 'number',
			'custom_attributes' => array( 'min' => 100, 'max' => 2000, 'step' => 50 ),
		),
		array( 'type' => 'sectionend', 'id' => 'iws_gallery_slider_options' ),
	);
}, 10, 2 );

add_action( 'wp', function() {
	if ( ! iws_gallery_slider_is_enabled() || ! is_product() ) {
		return;
	}
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
	add_action( 'woocommerce_before_single_product_summary', 'iws_gallery_slider_render', 20 );
} );

add_action( 'wp_enqueue_scripts', function() {
	if ( ! iws_gallery_slider_is_enabled() || ! is_product() ) {
		return;
	}

	$theme_version = WP_THEME_WOO_SUPPORT_VERSION;
	if ( 'yes' === iws_gallery_slider_get_option( 'woo_zoom', 'yes' ) ) {
		wp_enqueue_script( 'zoom' );
	}

	wp_enqueue_style( 'iws-gallery-slider', wp_theme_woo_support_url( 'assets/css/gallery-slider.css' ), array(), $theme_version );
	wp_enqueue_script( 'iws-gallery-slider', wp_theme_woo_support_url( 'assets/js/gallery-slider.js' ), array( 'jquery' ), $theme_version, true );

	wp_localize_script( 'iws-gallery-slider', 'iwsGallerySliderSettings', array(
		'loop'        => 'yes' === iws_gallery_slider_get_option( 'loop', 'yes' ),
		'speed'       => absint( iws_gallery_slider_get_option( 'speed', 250 ) ),
		'lightbox'    => 'yes' === iws_gallery_slider_get_option( 'lightbox', 'yes' ),
		'wooZoom'     => 'yes' === iws_gallery_slider_get_option( 'woo_zoom', 'yes' ),
		'useSwiper'   => 'yes' === iws_gallery_slider_get_option( 'swiper', 'yes' ),
		'heightMode'  => sanitize_key( iws_gallery_slider_get_option( 'height_mode', 'dynamic' ) ),
		'maxHeight'   => max( 320, min( 900, absint( iws_gallery_slider_get_option( 'max_height', 540 ) ) ) ),
		'hoverZoom'   => 'yes' === iws_gallery_slider_get_option( 'hover_follow_zoom', 'yes' ),
		'performance' => 'yes' === iws_gallery_slider_get_option( 'performance_mode', 'yes' ),
		'closeLabel'  => __( 'Close gallery', 'wp' ),
	) );
}, 35 );

function iws_gallery_slider_get_image_ids( WC_Product $product ) {
	$image_ids = array();
	if ( $product->get_image_id() ) {
		$image_ids[] = $product->get_image_id();
	}
	$image_ids = array_merge( $image_ids, $product->get_gallery_image_ids() );
	return array_values( array_unique( array_filter( array_map( 'absint', $image_ids ) ) ) );
}

function iws_gallery_slider_extract_video_urls( WC_Product $product ) {
	if ( 'yes' !== iws_gallery_slider_get_option( 'video_support', 'yes' ) ) {
		return array();
	}

	$keys = array_filter( array_map( 'trim', explode( ',', iws_gallery_slider_get_option( 'video_meta_keys', '_iws_product_gallery_videos,product_video_url,_product_video_url,_wcgs_product_video' ) ) ) );
	$urls = array();

	foreach ( $keys as $key ) {
		$value = get_post_meta( $product->get_id(), $key, true );
		if ( empty( $value ) ) {
			continue;
		}
		if ( is_array( $value ) ) {
			$parts = $value;
		} else {
			$parts = preg_split( '/[\r\n,]+/', (string) $value );
		}
		foreach ( $parts as $part ) {
			$url = esc_url_raw( trim( (string) $part ) );
			if ( $url ) {
				$urls[] = $url;
			}
		}
	}

	return array_values( array_unique( $urls ) );
}

function iws_gallery_slider_video_embed_url( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$host = $host ? strtolower( $host ) : '';
	if ( false !== strpos( $host, 'youtube.com' ) ) {
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		if ( ! empty( $query['v'] ) ) {
			return 'https://www.youtube.com/embed/' . rawurlencode( $query['v'] );
		}
	}
	if ( false !== strpos( $host, 'youtu.be' ) ) {
		$id = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		if ( $id ) {
			return 'https://www.youtube.com/embed/' . rawurlencode( $id );
		}
	}
	if ( false !== strpos( $host, 'vimeo.com' ) ) {
		$id = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		if ( $id ) {
			return 'https://player.vimeo.com/video/' . rawurlencode( basename( $id ) );
		}
	}
	return $url;
}

function iws_gallery_slider_render() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$image_ids = iws_gallery_slider_get_image_ids( $product );
	$videos    = iws_gallery_slider_extract_video_urls( $product );
	if ( count( $image_ids ) <= 1 && empty( $videos ) ) {
		woocommerce_show_product_images();
		return;
	}

	$thumb_position = sanitize_key( iws_gallery_slider_get_option( 'thumb_position', 'left' ) );
	if ( ! in_array( $thumb_position, array( 'left', 'right', 'bottom' ), true ) ) {
		$thumb_position = 'left';
	}

	$thumbs_visible = max( 2, min( 12, absint( iws_gallery_slider_get_option( 'thumbs_visible', 6 ) ) ) );
	$thumb_width    = max( 48, min( 140, absint( iws_gallery_slider_get_option( 'thumb_width', 72 ) ) ) );
	$thumb_gap      = max( 0, min( 40, absint( iws_gallery_slider_get_option( 'thumb_gap', 10 ) ) ) );
	$max_height     = max( 320, min( 900, absint( iws_gallery_slider_get_option( 'max_height', 540 ) ) ) );
	$height_mode    = sanitize_key( iws_gallery_slider_get_option( 'height_mode', 'dynamic' ) );
	$arrows_enabled = 'yes' === iws_gallery_slider_get_option( 'arrows', 'yes' );
	$woo_zoom       = 'yes' === iws_gallery_slider_get_option( 'woo_zoom', 'yes' );
	$lightbox       = 'yes' === iws_gallery_slider_get_option( 'lightbox', 'yes' );

	$items = array();
	foreach ( $image_ids as $image_id ) {
		$items[] = array( 'type' => 'image', 'id' => absint( $image_id ) );
	}
	foreach ( $videos as $video_url ) {
		$items[] = array( 'type' => 'video', 'url' => $video_url, 'embed' => iws_gallery_slider_video_embed_url( $video_url ) );
	}

	$classes = array(
		'iws-product-gallery-slider',
		'woocommerce-product-gallery',
		'iws-product-gallery-slider--thumbs-' . $thumb_position,
		'iws-product-gallery-slider--height-' . ( 'fixed' === $height_mode ? 'fixed' : 'dynamic' ),
		$woo_zoom ? 'has-woo-zoom' : 'no-zoom',
		$lightbox ? 'has-lightbox' : 'no-lightbox',
	);

	printf(
		'<div class="%1$s" data-thumbs-visible="%2$d" data-thumb-position="%3$s" style="--iws-gallery-thumb-width:%4$dpx;--iws-gallery-gap:%5$dpx;--iws-gallery-thumbs:%2$d;--iws-gallery-max-height:%6$dpx;">',
		esc_attr( implode( ' ', $classes ) ),
		absint( $thumbs_visible ),
		esc_attr( $thumb_position ),
		absint( $thumb_width ),
		absint( $thumb_gap ),
		absint( $max_height )
	);

	echo '<div class="iws-product-gallery-slider__thumbs-wrap">';
	echo '<button type="button" class="iws-product-gallery-slider__thumb-arrow iws-product-gallery-slider__thumb-arrow--prev" aria-label="' . esc_attr__( 'Previous thumbnails', 'wp' ) . '"></button>';
	echo '<div class="iws-product-gallery-slider__thumbs swiper iws-gallery-thumbs" role="tablist" aria-label="' . esc_attr__( 'Product gallery thumbnails', 'wp' ) . '"><div class="swiper-wrapper">';
	foreach ( $items as $index => $item ) {
		if ( 'video' === $item['type'] ) {
			$thumb = '<span class="iws-product-gallery-slider__video-thumb" aria-hidden="true"></span>';
		} else {
			$thumb = wp_get_attachment_image( $item['id'], 'woocommerce_gallery_thumbnail', false, array( 'loading' => 'lazy' ) );
		}
		printf(
			'<button type="button" class="iws-product-gallery-slider__thumb swiper-slide%1$s%6$s" data-index="%2$d" role="tab" aria-selected="%3$s" aria-label="%4$s">%5$s</button>',
			0 === $index ? ' is-active' : '',
			absint( $index ),
			0 === $index ? 'true' : 'false',
			esc_attr( sprintf( 'video' === $item['type'] ? __( 'Select product video %d', 'wp' ) : __( 'Select product image %d', 'wp' ), $index + 1 ) ),
			$thumb,
			'video' === $item['type'] ? ' is-video-thumb' : ''
		);
	}
	echo '</div></div>';
	echo '<button type="button" class="iws-product-gallery-slider__thumb-arrow iws-product-gallery-slider__thumb-arrow--next" aria-label="' . esc_attr__( 'Next thumbnails', 'wp' ) . '"></button>';
	echo '</div>';

	echo '<div class="iws-product-gallery-slider__main swiper iws-gallery-main" role="region" aria-label="' . esc_attr__( 'Product image gallery', 'wp' ) . '">';
	if ( $lightbox ) {
		echo '<button type="button" class="wcgs-lightbox top_right iws-gallery-zoom-trigger" aria-label="' . esc_attr__( 'Open fullscreen product gallery', 'wp' ) . '"><span class="sp_wgs-lightbox"><span class="sp_wgs-icon-search" aria-hidden="true"></span></span></button>';
	}
	echo '<div class="swiper-wrapper">';
	foreach ( $items as $index => $item ) {
		if ( 'video' === $item['type'] ) {
			printf(
				'<div class="iws-product-gallery-slider__slide swiper-slide woocommerce-product-gallery__image iws-product-gallery-slider__slide--video%1$s" data-index="%2$d" data-type="video" data-video="%3$s" data-full="%3$s" aria-hidden="%4$s"><button type="button" class="iws-product-gallery-slider__video-button" aria-label="%5$s"><span class="iws-product-gallery-slider__video-play"></span></button></div>',
				0 === $index ? ' is-active' : '',
				absint( $index ),
				esc_url( $item['embed'] ),
				0 === $index ? 'false' : 'true',
				esc_attr__( 'Play product video', 'wp' )
			);
			continue;
		}

		$full_src = wp_get_attachment_image_url( $item['id'], 'full' );
		$full     = wp_get_attachment_image_src( $item['id'], 'full' );
		$image    = wp_get_attachment_image( $item['id'], 'woocommerce_single', false, array(
			'class'                   => 'iws-product-gallery-slider__image wp-post-image',
			'loading'                 => 0 === $index ? 'eager' : 'lazy',
			'fetchpriority'           => 0 === $index ? 'high' : 'auto',
			'data-src'                => esc_url( $full_src ),
			'data-large_image'        => esc_url( $full_src ),
			'data-large_image_width'  => $full ? absint( $full[1] ) : 0,
			'data-large_image_height' => $full ? absint( $full[2] ) : 0,
		) );

		printf(
			'<div class="iws-product-gallery-slider__slide swiper-slide woocommerce-product-gallery__image%1$s" data-index="%2$d" data-type="image" data-full="%3$s" data-width="%7$d" data-height="%8$d" aria-hidden="%4$s"><a class="iws-product-gallery-slider__zoom-target" href="%3$s" data-large_image="%3$s" aria-label="%5$s">%6$s</a></div>',
			0 === $index ? ' is-active' : '',
			absint( $index ),
			esc_url( $full_src ),
			0 === $index ? 'false' : 'true',
			esc_attr( sprintf( __( 'View product image %d', 'wp' ), $index + 1 ) ),
			$image,
			$full ? absint( $full[1] ) : 0,
			$full ? absint( $full[2] ) : 0
		);
	}
	echo '</div>';
	if ( $arrows_enabled ) {
		echo '<button type="button" class="iws-product-gallery-slider__arrow iws-product-gallery-slider__arrow--prev" aria-label="' . esc_attr__( 'Previous product image', 'wp' ) . '">‹</button>';
		echo '<button type="button" class="iws-product-gallery-slider__arrow iws-product-gallery-slider__arrow--next" aria-label="' . esc_attr__( 'Next product image', 'wp' ) . '">›</button>';
	}
	echo '</div></div>';
}
