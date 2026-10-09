<?php
/** WP BB Home & Garden v4.0.31 — mini-cart, quote drawer and header category polish. */
defined('ABSPATH') || exit;
if (!defined('WPBBSHOP_V431_VERSION')) define('WPBBSHOP_V431_VERSION', '4.0.31');

add_action('wp_enqueue_scripts', function() {
    $path = get_stylesheet_directory() . '/assets/css/v431-drawer-search-polish.css';
    if (!is_readable($path)) return;
    wp_enqueue_style(
        'wpbbshop-v431-drawer-search-polish',
        get_stylesheet_directory_uri() . '/assets/css/v431-drawer-search-polish.css',
        array('wpbbshop-v430-commerce-polish'),
        (string) filemtime($path)
    );
}, PHP_INT_MAX);
