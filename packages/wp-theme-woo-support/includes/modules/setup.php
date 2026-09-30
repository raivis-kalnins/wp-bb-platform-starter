<?php
defined( 'ABSPATH' ) || exit;

/**
 * Theme support
 */
add_action( 'after_setup_theme', function() {
	add_theme_support( 'woocommerce' );
} );

/**
 * Hide default Woo page title.
 */
add_filter( 'woocommerce_show_page_title', '__return_false' );

/**
 * Remove breadcrumbs.
 */
add_action( 'init', function() {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
} );

/**
 * Hide default block patterns item in editor.
 */
add_action( 'enqueue_block_editor_assets', function() {
	wp_add_inline_style(
		'wp-block-editor',
		'#patterns-navigation-item{display:none!important}.woocommerce .products .product .variation-function-added{display:none!important;}'
	);
} );

/**
 * Auto-send invoice for pending orders.
 */
add_action( 'woocommerce_checkout_order_processed', function( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( $order && $order->has_status( 'pending' ) && isset( WC()->mailer()->emails['WC_Email_Customer_Invoice'] ) ) {
		WC()->mailer()->emails['WC_Email_Customer_Invoice']->trigger( $order_id );
	}
}, 20 );

/**
 * Keep every Load more control consistent with the primary expanding-arrow
 * button style used across the theme.
 */
add_action( 'wp_enqueue_scripts', function() {
	wp_register_style( 'iws-load-more-button-polish', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-load-more-button-polish' );

	$arrow = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E";
	$css   = '
		.iws-load-more-wrap,
		.iws-taxonomy-load-more-wrap,
		.iws-archive-load-more,
		.iws-blog-category-pagination.iws-archive-load-more{
			display:flex!important;
			justify-content:center!important;
			align-items:center!important;
			width:100%!important;
		}
		.iws-load-more-wrap .btn.btn-primary,
		.iws-load-more-wrap .iws-load-more,
		.iws-load-more-wrap .iws-taxonomy-load-more,
		.iws-taxonomy-load-more,
		.iws-archive-load-more .wp-block-query-pagination-next,
		.iws-archive-load-more .wp-block-query-pagination-previous,
		.iws-archive-load-more .iws-ajax-load-more-button,
		#loadMore.loadMore,
		#loadMore.btn,
		.loadMore.btn{
			display:inline-flex!important;
			align-items:center!important;
			justify-content:center!important;
			gap:10px!important;
			width:fit-content!important;
			max-width:100%!important;
			min-width:148px!important;
			min-height:39px!important;
			margin-left:auto!important;
			margin-right:auto!important;
			padding:10px 15px!important;
			border:0!important;
			border-color:var(--wp-brand-color)!important;
			border-radius:0 15px 0 0!important;
			background-color:var(--wp-brand-color)!important;
			background-image:url("' . $arrow . '")!important;
			background-repeat:no-repeat!important;
			background-position:calc(100% + 32px) center!important;
			background-size:16px auto!important;
			color:var(--wp-white-color,#fff)!important;
			font-weight:500!important;
			line-height:1!important;
			text-align:center!important;
			text-decoration:none!important;
			white-space:nowrap!important;
			cursor:pointer!important;
			transition:background-position .25s ease,padding .25s ease,background-color .2s ease,border-color .2s ease,color .2s ease,opacity .2s ease!important;
		}
		.iws-load-more-wrap .btn.btn-primary:hover,
		.iws-load-more-wrap .btn.btn-primary:focus,
		.iws-load-more-wrap .iws-load-more:hover,
		.iws-load-more-wrap .iws-load-more:focus,
		.iws-load-more-wrap .iws-taxonomy-load-more:hover,
		.iws-load-more-wrap .iws-taxonomy-load-more:focus,
		.iws-taxonomy-load-more:hover,
		.iws-taxonomy-load-more:focus,
		.iws-archive-load-more .wp-block-query-pagination-next:hover,
		.iws-archive-load-more .wp-block-query-pagination-next:focus,
		.iws-archive-load-more .wp-block-query-pagination-previous:hover,
		.iws-archive-load-more .wp-block-query-pagination-previous:focus,
		.iws-archive-load-more .iws-ajax-load-more-button:hover,
		.iws-archive-load-more .iws-ajax-load-more-button:focus,
		#loadMore.loadMore:hover,
		#loadMore.loadMore:focus,
		#loadMore.btn:hover,
		#loadMore.btn:focus,
		.loadMore.btn:hover,
		.loadMore.btn:focus{
			padding-right:50px!important;
			background-color:var(--wp-brand-color)!important;
			background-position:calc(100% - 18px) center!important;
			border-color:var(--wp-brand-color)!important;
			color:var(--wp-white-color,#fff)!important;
		}
		.iws-load-more-wrap .btn.btn-primary *,
		.iws-load-more-wrap .iws-load-more *,
		.iws-taxonomy-load-more *,
		.iws-archive-load-more .wp-block-query-pagination-next *,
		.iws-archive-load-more .wp-block-query-pagination-previous *,
		.iws-archive-load-more .iws-ajax-load-more-button *{
			color:var(--wp-white-color,#fff)!important;
		}
		.iws-load-more.is-loading,
		.iws-load-more[disabled],
		.iws-taxonomy-load-more.is-loading,
		.iws-archive-load-more .iws-ajax-load-more-button.is-loading{
			cursor:wait!important;
			opacity:.72!important;
			pointer-events:none!important;
		}
	';

	wp_add_inline_style( 'iws-load-more-button-polish', $css );
}, 999 );
