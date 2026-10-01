<?php
/**
 * WP BB Home & Garden v4.0.10
 * Final visual ownership layer for homepage hero, departments, catalogue density and bundled branding.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V410_VERSION')) {
    define('WPBBSHOP_V410_VERSION', '4.0.10');
}

add_action('wp_enqueue_scripts', function () {
    $path = get_stylesheet_directory() . '/assets/css/v410-visual.css';
    $uri  = get_stylesheet_directory_uri() . '/assets/css/v410-visual.css';
    wp_enqueue_style(
        'wpbbshop-v410-visual',
        $uri,
        array(),
        file_exists($path) ? (string) filemtime($path) : WPBBSHOP_V410_VERSION
    );
}, 9999);

/* Bust old browser/PWA theme colour caches together with the new favicon. */
add_action('wp_head', function () {
    echo '<meta name="theme-color" content="#0f7a49">';
}, 1001);
