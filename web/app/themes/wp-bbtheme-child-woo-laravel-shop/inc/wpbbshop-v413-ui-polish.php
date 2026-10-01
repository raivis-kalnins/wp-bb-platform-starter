<?php
/** WP BB Home & Garden v4.0.13 — final green filter + compact homepage polish. */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V413_VERSION')) {
    define('WPBBSHOP_V413_VERSION', '4.0.13');
}

add_action('wp_enqueue_scripts', function () {
    $file = get_stylesheet_directory() . '/assets/css/v413-ui-polish.css';
    if (!is_readable($file)) { return; }
    wp_enqueue_style(
        'wpbbshop-v413-ui-polish',
        get_stylesheet_directory_uri() . '/assets/css/v413-ui-polish.css',
        array(),
        (string) filemtime($file)
    );
}, PHP_INT_MAX);
