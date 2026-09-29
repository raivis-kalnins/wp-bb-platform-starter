<?php
/** WP BBTheme 3.8.10.24 — cloned WooCommerce account routing repair. */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_theme_v381024_myaccount_page_id' ) ) {
    function wp_theme_v381024_myaccount_page_id( $create = false ) {
        $id = function_exists( 'wc_get_page_id' ) ? absint( wc_get_page_id( 'myaccount' ) ) : 0;
        if ( $id && 'publish' === get_post_status( $id ) ) return $id;

        $page = get_page_by_path( 'my-account', OBJECT, 'page' );
        if ( $page instanceof WP_Post && 'publish' === $page->post_status ) return (int) $page->ID;

        $candidates = get_posts( array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            's'              => 'account',
        ) );
        foreach ( $candidates as $candidate ) {
            $content = (string) $candidate->post_content;
            if ( false !== strpos( $content, '[woocommerce_my_account' ) || false !== strpos( $content, 'woocommerce/my-account' ) ) return (int) $candidate->ID;
        }

        if ( ! $create || ! current_user_can( 'manage_options' ) ) return 0;
        $id = wp_insert_post( array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => __( 'My account', 'wp-theme' ),
            'post_name'    => 'my-account',
            'post_content' => '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->',
        ), true );
        return is_wp_error( $id ) ? 0 : absint( $id );
    }
}

if ( ! function_exists( 'wp_theme_v381024_current_myaccount_url' ) ) {
    function wp_theme_v381024_current_myaccount_url() {
        $id = wp_theme_v381024_myaccount_page_id( false );
        if ( ! $id ) return home_url( '/my-account/' );
        if ( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post' ) ) {
            $lang = sanitize_key( (string) pll_current_language( 'slug' ) );
            $translated = $lang ? absint( pll_get_post( $id, $lang ) ) : 0;
            if ( $translated && 'publish' === get_post_status( $translated ) ) $id = $translated;
        }
        return get_permalink( $id ) ?: home_url( '/my-account/' );
    }
}

if ( ! function_exists( 'wp_theme_v381024_repair_wc_account_routes' ) ) {
    function wp_theme_v381024_repair_wc_account_routes() {
        if ( ! function_exists( 'wc_get_page_id' ) && ! class_exists( 'WooCommerce' ) ) return;
        if ( is_admin() && ! current_user_can( 'manage_options' ) ) return;

        $page_id = wp_theme_v381024_myaccount_page_id( true );
        if ( ! $page_id ) return;

        $changed = false;
        if ( absint( get_option( 'woocommerce_myaccount_page_id' ) ) !== $page_id ) {
            update_option( 'woocommerce_myaccount_page_id', $page_id );
            $changed = true;
        }

        $defaults = array(
            'orders'          => 'orders',
            'downloads'       => 'downloads',
            'edit-address'    => 'edit-address',
            'payment-methods' => 'payment-methods',
            'edit-account'    => 'edit-account',
            'lost-password'   => 'lost-password',
        );
        foreach ( $defaults as $key => $default ) {
            $option = 'woocommerce_myaccount_' . str_replace( '-', '_', $key ) . '_endpoint';
            if ( '' === trim( (string) get_option( $option, '' ) ) ) {
                update_option( $option, $default );
                $changed = true;
            }
        }

        $marker = (string) get_option( 'wp_theme_v381024_wc_account_routes', '' );
        if ( $changed || '3.8.10.24' !== $marker ) {
            flush_rewrite_rules( false );
            update_option( 'wp_theme_v381024_wc_account_routes', '3.8.10.24', false );
        }
    }
    add_action( 'admin_init', 'wp_theme_v381024_repair_wc_account_routes', 45 );
    add_action( 'wp_theme_after_demo_import', 'wp_theme_v381024_repair_wc_account_routes', 45 );
}

if ( ! function_exists( 'wp_theme_v381024_account_endpoint_url' ) ) {
    function wp_theme_v381024_account_endpoint_url( $url, $endpoint, $value, $permalink ) {
        $account_endpoints = array( 'orders','downloads','edit-address','payment-methods','edit-account','lost-password','view-order','add-payment-method','delete-payment-method','set-default-payment-method' );
        if ( ! in_array( $endpoint, $account_endpoints, true ) ) return $url;
        $base = wp_theme_v381024_current_myaccount_url();
        if ( ! $base ) return $url;

        $slug = trim( (string) $endpoint, '/' );
        $value = trim( (string) $value, '/' );
        if ( get_option( 'permalink_structure' ) ) {
            $path = trailingslashit( $base ) . $slug . '/';
            if ( '' !== $value ) $path .= rawurlencode( $value ) . '/';
            return user_trailingslashit( $path );
        }
        return add_query_arg( $slug, '' !== $value ? $value : 1, $base );
    }
    add_filter( 'woocommerce_get_endpoint_url', 'wp_theme_v381024_account_endpoint_url', 9999, 4 );
}

if ( ! function_exists( 'wp_theme_v381024_redirect_legacy_account_endpoints' ) ) {
    function wp_theme_v381024_redirect_legacy_account_endpoints() {
        if ( is_admin() || ! is_404() ) return;
        global $wp;
        $request = trim( (string) ( $wp->request ?? '' ), '/' );
        if ( '' === $request ) return;
        $segments = array_values( array_filter( explode( '/', $request ), 'strlen' ) );
        if ( ! $segments ) return;

        $known = array( 'orders','downloads','edit-address','payment-methods','edit-account','lost-password','view-order' );
        $hit = -1;
        foreach ( $segments as $i => $segment ) {
            if ( in_array( sanitize_title( $segment ), $known, true ) ) { $hit = $i; break; }
        }
        if ( $hit < 0 ) return;
        $endpoint = sanitize_title( $segments[ $hit ] );
        $value = isset( $segments[ $hit + 1 ] ) ? sanitize_text_field( $segments[ $hit + 1 ] ) : '';
        $target = wp_theme_v381024_account_endpoint_url( '', $endpoint, $value, '' );
        if ( $target ) wp_safe_redirect( $target, 301 );
        exit;
    }
    add_action( 'template_redirect', 'wp_theme_v381024_redirect_legacy_account_endpoints', 2 );
}
