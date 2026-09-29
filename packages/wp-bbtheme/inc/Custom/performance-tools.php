<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('wp_theme_perf_get')) {
    function wp_theme_perf_get($key, $default = '') {
        return function_exists('get_field') ? wp_theme_acf_get($key, 'option', $default) : $default;
    }
}

if (!function_exists('wp_theme_performance_actions_markup')) {
    function wp_theme_performance_actions_markup() {
        $purge_url = wp_nonce_url(
            admin_url('admin-post.php?action=wp_theme_purge_cache'),
            'wp_theme_purge_cache'
        );

        return '<p><a class="button button-secondary wp-theme-clean-cache-button" data-wp-theme-clean-cache href="' . esc_url($purge_url) . '"><span class="dashicons dashicons-update wp-theme-cache-button-icon" aria-hidden="true"></span><span class="wp-theme-cache-button-label" aria-live="polite">' . esc_html__('Clean Cache', 'wp-theme') . '</span></a> <a class="button button-secondary" href="' . esc_url(admin_url('admin-post.php?action=wp_theme_generate_critical_css')) . '">' . esc_html__('Generate Critical CSS', 'wp-theme') . '</a> <a class="button button-secondary" href="' . esc_url(admin_url('admin-post.php?action=wp_theme_optimize_options')) . '">' . esc_html__('Optimize Options', 'wp-theme') . '</a></p>';
    }
}

function wp_theme_perf_output_cache_headers() {
    if (is_admin() || is_user_logged_in() || !wp_theme_perf_get('perf_cache_headers', false)) {
        return;
    }
    if (!headers_sent()) {
        header('Cache-Control: public, max-age=300, stale-while-revalidate=60');
    }
}
add_action('send_headers', 'wp_theme_perf_output_cache_headers');

function wp_theme_perf_safe_minify_buffer($html) {
    if (stripos($html, '<pre') !== false || stripos($html, '<textarea') !== false) {
        return $html;
    }
    $html = preg_replace('/>\s+</', '><', $html);
    $html = preg_replace('/\s{2,}/', ' ', $html);
    return trim($html);
}
function wp_theme_perf_maybe_start_buffer() {
    if (is_admin() || is_user_logged_in() || !wp_theme_perf_get('perf_html_minify', false)) {
        return;
    }
    ob_start('wp_theme_perf_safe_minify_buffer');
}
add_action('template_redirect', 'wp_theme_perf_maybe_start_buffer', 0);

function wp_theme_generate_critical_css_file() {
    $css = ":root{--wp-theme-critical:1}.wp-theme-site-header{position:sticky;top:0;z-index:1000}.wp-theme-header-nav .dropdown-menu{display:none}.wp-theme-demo-homepage .wp-theme-demo-card,.wp-theme-demo-homepage .wp-theme-demo-stat{border-radius:20px}";
    $target = get_stylesheet_directory() . '/assets/css/critical-auto.css';
    wp_mkdir_p(dirname($target));
    file_put_contents($target, $css);
}

function wp_theme_perf_enqueue_critical_css() {
    $file = get_stylesheet_directory() . '/assets/css/critical-auto.css';
    if (wp_theme_perf_get('perf_auto_critical_css', false) && file_exists($file)) {
        wp_enqueue_style('wp-theme-critical-auto', get_stylesheet_directory_uri() . '/assets/css/critical-auto.css', [], filemtime($file));
    }
}
add_action('wp_enqueue_scripts', 'wp_theme_perf_enqueue_critical_css', 5);

function wp_theme_perf_cache_key($suffix) {
    $lang = function_exists('pll_current_language') ? pll_current_language('slug') : 'default';
    return 'wp_theme_' . $lang . '_' . md5($suffix);
}

function wp_theme_purge_all_theme_cache() {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wp_theme_%' OR option_name LIKE '_transient_timeout_wp_theme_%'");
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    do_action('wp_theme_cache_purged');
}

function wp_theme_perf_admin_action_guard($nonce_action = '') {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Not allowed', 'wp-theme'), '', ['response' => 403]);
    }

    if ($nonce_action !== '') {
        check_admin_referer($nonce_action);
    }
}

function wp_theme_handle_purge_cache() {
    wp_theme_perf_admin_action_guard('wp_theme_purge_cache');
    wp_theme_purge_all_theme_cache();

    $redirect = wp_get_referer() ?: admin_url();
    $redirect = remove_query_arg('wp_theme_cache', $redirect);
    wp_safe_redirect(add_query_arg('wp_theme_cache', 'cleaned', $redirect));
    exit;
}
add_action('admin_post_wp_theme_purge_cache', 'wp_theme_handle_purge_cache');

function wp_theme_ajax_purge_cache() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You are not allowed to clean the theme cache.', 'wp-theme')], 403);
    }

    check_ajax_referer('wp_theme_purge_cache_ajax', 'nonce');
    wp_theme_purge_all_theme_cache();

    wp_send_json_success([
        'message' => __('Theme Cache Clean', 'wp-theme'),
    ]);
}
add_action('wp_ajax_wp_theme_purge_cache_ajax', 'wp_theme_ajax_purge_cache');

function wp_theme_add_cache_toolbar_node($wp_admin_bar) {
    if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
        return;
    }

    $wp_admin_bar->add_node([
        'id' => 'wp-theme-cache',
        'title' => '<span class="ab-icon dashicons dashicons-update wp-theme-cache-toolbar-icon" aria-hidden="true"></span><span class="ab-label wp-theme-cache-toolbar-label" aria-live="polite">' . esc_html__('Theme Cache', 'wp-theme') . '</span>',
        'href' => wp_nonce_url(admin_url('admin-post.php?action=wp_theme_purge_cache'), 'wp_theme_purge_cache'),
        'meta' => [
            'class' => 'wp-theme-cache-toolbar',
            'title' => __('Clean Theme Cache', 'wp-theme'),
        ],
    ]);
}
add_action('admin_bar_menu', 'wp_theme_add_cache_toolbar_node', 95);

function wp_theme_enqueue_cache_toolbar_assets($hook = '') {
    if (!current_user_can('manage_options')) {
        return;
    }

    $is_settings_page = is_admin() && strpos((string) $hook, 'wp-theme-settings') !== false;
    if (!is_admin_bar_showing() && !$is_settings_page) {
        return;
    }

    $css_file = get_template_directory() . '/assets/css/admin-cache-toolbar.css';
    $js_file = get_template_directory() . '/assets/js/admin-cache-toolbar.js';

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'wp-theme-cache-toolbar',
            get_template_directory_uri() . '/assets/css/admin-cache-toolbar.css',
            ['dashicons'],
            filemtime($css_file)
        );
    }

    if (file_exists($js_file)) {
        wp_enqueue_script(
            'wp-theme-cache-toolbar',
            get_template_directory_uri() . '/assets/js/admin-cache-toolbar.js',
            [],
            filemtime($js_file),
            true
        );
        wp_localize_script('wp-theme-cache-toolbar', 'WPThemeCacheToolbar', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_theme_purge_cache_ajax'),
            'labels' => [
                'toolbarDefault' => __('Theme Cache', 'wp-theme'),
                'buttonDefault' => __('Clean Cache', 'wp-theme'),
                'cleaning' => __('Cleaning Theme Cache…', 'wp-theme'),
                'clean' => __('Theme Cache Clean', 'wp-theme'),
                'failed' => __('Cache Clean Failed', 'wp-theme'),
            ],
        ]);
    }
}
add_action('admin_enqueue_scripts', 'wp_theme_enqueue_cache_toolbar_assets', 100);
add_action('wp_enqueue_scripts', 'wp_theme_enqueue_cache_toolbar_assets', 100);

function wp_theme_cache_cleaned_admin_notice() {
    if (!current_user_can('manage_options') || sanitize_key(wp_unslash($_GET['wp_theme_cache'] ?? '')) !== 'cleaned') {
        return;
    }

    echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__('Theme Cache Clean', 'wp-theme') . '</strong></p></div>';
}
add_action('admin_notices', 'wp_theme_cache_cleaned_admin_notice');

add_action('admin_post_wp_theme_generate_critical_css', function(){ wp_theme_perf_admin_action_guard(); wp_theme_generate_critical_css_file(); wp_safe_redirect(wp_get_referer() ?: admin_url()); exit; });
add_action('admin_post_wp_theme_optimize_options', function(){ wp_theme_perf_admin_action_guard(); global $wpdb; $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()"); wp_safe_redirect(wp_get_referer() ?: admin_url()); exit; });

add_action('save_post', 'wp_theme_purge_all_theme_cache');
add_action('wp_update_nav_menu', 'wp_theme_purge_all_theme_cache');
