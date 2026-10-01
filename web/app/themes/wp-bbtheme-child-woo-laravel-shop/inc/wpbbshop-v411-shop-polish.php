<?php
/** WP BB Home & Garden v4.0.11 shop/category polish. */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V411_VERSION')) {
    define('WPBBSHOP_V411_VERSION', '4.0.11');
}

add_action('wp_enqueue_scripts', function () {
    if (!(function_exists('is_shop') && (is_shop() || is_product_taxonomy()))) return;
    $path = get_stylesheet_directory() . '/assets/css/v411-shop-polish.css';
    wp_enqueue_style(
        'wpbbshop-v411-shop-polish',
        get_stylesheet_directory_uri() . '/assets/css/v411-shop-polish.css',
        array('wpbbshop-v409-catalog', 'wpbbshop-v410-visual'),
        file_exists($path) ? (string) filemtime($path) : WPBBSHOP_V411_VERSION
    );
}, 10001);
