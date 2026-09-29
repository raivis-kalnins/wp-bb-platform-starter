<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('wp_theme_login_slug_enabled')) {
    function wp_theme_login_slug_enabled() {
        return (bool) wp_theme_acf_get('theme_enable_custom_login_slug', 'option', 0);
    }
}

if (!function_exists('wp_theme_get_login_slug')) {
    function wp_theme_get_login_slug() {
        $slug = (string) wp_theme_acf_get('theme_custom_login_slug', 'option', 'tfa-admin');
        $slug = trim($slug, "/ \t\n\r\0\x0B");
        $slug = sanitize_title($slug);
        return $slug ?: 'tfa-admin';
    }
}

if (!function_exists('wp_theme_custom_login_url')) {
    function wp_theme_custom_login_url() {
        return home_url('/' . wp_theme_get_login_slug() . '/');
    }
}

if (!function_exists('wp_theme_is_direct_wp_login_request')) {
    function wp_theme_is_direct_wp_login_request() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        return strpos($request_uri, 'wp-login.php') !== false;
    }
}

if (!function_exists('wp_theme_is_custom_login_request')) {
    function wp_theme_is_custom_login_request() {
        $request_path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
        $request_path = trim((string) $request_path, '/');
        return $request_path === trim((string) wp_parse_url(wp_theme_custom_login_url(), PHP_URL_PATH), '/');
    }
}

add_filter('login_url', function ($login_url, $redirect, $force_reauth) {
    if (!wp_theme_login_slug_enabled()) {
        return $login_url;
    }
    $url = wp_theme_custom_login_url();
    if ($redirect) {
        $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
    }
    if ($force_reauth) {
        $url = add_query_arg('reauth', '1', $url);
    }
    return $url;
}, 20, 3);

add_filter('lostpassword_url', function ($lostpassword_url, $redirect) {
    if (!wp_theme_login_slug_enabled()) {
        return $lostpassword_url;
    }
    $url = add_query_arg('action', 'lostpassword', wp_theme_custom_login_url());
    if ($redirect) {
        $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
    }
    return $url;
}, 20, 2);

add_filter('site_url', function ($url, $path, $scheme, $blog_id) {
    if (!wp_theme_login_slug_enabled()) {
        return $url;
    }
    if (is_string($path) && strpos($path, 'wp-login.php') !== false) {
        $custom = wp_theme_custom_login_url();
        $query = wp_parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $custom = $custom . (strpos($custom, '?') === false ? '?' : '&') . $query;
        }
        return $custom;
    }
    return $url;
}, 20, 4);

add_action('init', function () {
    if (is_user_logged_in()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $request_path = trim($request_path, '/');
    $redirect_wp_admin = (bool) wp_theme_acf_get('theme_redirect_wp_admin_home', 'option', 0);
    $custom_login = wp_theme_login_slug_enabled();

    if ($request_path !== '' && strpos($request_path, 'wp-admin') === 0) {
        $allowed = [
            'wp-admin/admin-ajax.php',
            'wp-admin/admin-post.php',
            'wp-admin/async-upload.php',
        ];
        if (!in_array($request_path, $allowed, true) && ($redirect_wp_admin || $custom_login) && !wp_doing_ajax()) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }

    if (!$custom_login) {
        return;
    }

    if (wp_theme_is_custom_login_request()) {
        require_once ABSPATH . 'wp-login.php';
        exit;
    }

    if (wp_theme_is_direct_wp_login_request()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}, 1);
