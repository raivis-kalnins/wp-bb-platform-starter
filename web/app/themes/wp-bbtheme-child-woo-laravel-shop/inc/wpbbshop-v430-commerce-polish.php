<?php
/** WP BB Home & Garden v4.0.30 — product/account/cart/quote polish. */
defined('ABSPATH') || exit;
if (!defined('WPBBSHOP_V430_VERSION')) define('WPBBSHOP_V430_VERSION', '4.0.30');
add_action('wp_enqueue_scripts', function() {
    $path = get_stylesheet_directory() . '/assets/css/v430-commerce-polish.css';
    if (!is_readable($path)) return;
    wp_enqueue_style(
        'wpbbshop-v430-commerce-polish',
        get_stylesheet_directory_uri() . '/assets/css/v430-commerce-polish.css',
        array('wpbbshop-v429-demo-safety-b2b'),
        (string) filemtime($path)
    );
}, PHP_INT_MAX);
