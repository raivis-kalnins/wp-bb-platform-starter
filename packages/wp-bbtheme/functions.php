<?php
if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
    require_once get_template_directory() . '/vendor/autoload.php';
}

$bbtheme_blade_bridge = get_template_directory() . '/inc/acorn-blade.php';
if (file_exists($bbtheme_blade_bridge)) {
    require_once $bbtheme_blade_bridge;
}

function wp_theme_acf_ready() {
    // ACF 5.11+ rejects value reads before its initialization cycle. Checking
    // acf/init also remains safe while ACF is inactive or temporarily disabled.
    return function_exists('get_field') && (!function_exists('acf') || did_action('acf/init'));
}

function wp_theme_acf_get($key, $post_id = 'option', $default = '') {
    if (!wp_theme_acf_ready()) {
        return $default;
    }

    $value = get_field($key, $post_id);
    return ($value !== null && $value !== false && $value !== '') ? $value : $default;
}

function wp_theme_acf_get_fields($post_id = 'option') {
    if (!wp_theme_acf_ready() || !function_exists('get_fields')) {
        return array();
    }
    $values = get_fields($post_id);
    return is_array($values) ? $values : array();
}

/**
 * Project-level feature flags must not become language-specific when
 * ACF Options for Polylang is active. A small core-option mirror keeps setup
 * actions (notably Demo Import) reliable during admin-ajax requests where the
 * Polylang language context may differ from the settings screen.
 */
function wp_theme_global_feature_flag_option_name($key) {
    return 'wp_theme_global_flag_' . sanitize_key($key);
}

function wp_theme_global_feature_flag($key, $default = false) {
    $mirror = get_option(wp_theme_global_feature_flag_option_name($key), null);
    if (null !== $mirror) {
        return in_array($mirror, array(true, 1, '1', 'true', 'yes', 'on'), true);
    }

    if (wp_theme_acf_ready()) {
        $value = get_field($key, 'option');
        if (in_array($value, array(true, 1, '1', 'true', 'yes', 'on'), true)) {
            update_option(wp_theme_global_feature_flag_option_name($key), '1', false);
            return true;
        }
    }

    // Standard ACF option key fallback for projects that enabled the flag
    // before the global mirror was introduced.
    $raw = get_option('options_' . sanitize_key($key), null);
    if (in_array($raw, array(true, 1, '1', 'true', 'yes', 'on'), true)) {
        update_option(wp_theme_global_feature_flag_option_name($key), '1', false);
        return true;
    }

    // ACF Options for Polylang stores language-specific option keys. For this
    // project-level setup flag, any previously saved truthy language value is
    // enough to prime the global mirror. Once v3.8.5 saves the field, the mirror
    // becomes the authoritative value and this compatibility query is skipped.
    global $wpdb;
    if (isset($wpdb) && isset($wpdb->options)) {
        $like = '%' . $wpdb->esc_like(sanitize_key($key)) . '%';
        $values = $wpdb->get_col($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
        foreach ((array) $values as $candidate) {
            $candidate = maybe_unserialize($candidate);
            if (in_array($candidate, array(true, 1, '1', 'true', 'yes', 'on'), true)) {
                update_option(wp_theme_global_feature_flag_option_name($key), '1', false);
                return true;
            }
        }
    }

    return (bool) $default;
}

function wp_theme_mirror_global_feature_flag($value, $post_id, $field) {
    $name = isset($field['name']) ? sanitize_key((string) $field['name']) : '';
    if ($name) {
        update_option(
            wp_theme_global_feature_flag_option_name($name),
            in_array($value, array(true, 1, '1', 'true', 'yes', 'on'), true) ? '1' : '0',
            false
        );
    }
    return $value;
}
add_filter('acf/update_value/name=theme_enable_demo_import', 'wp_theme_mirror_global_feature_flag', 20, 3);

function wp_theme_prime_global_setup_flags() {
    if (!is_admin() || wp_doing_ajax() || !current_user_can('edit_theme_options') || !wp_theme_acf_ready()) {
        return;
    }
    if (null === get_option(wp_theme_global_feature_flag_option_name('theme_enable_demo_import'), null)) {
        $value = get_field('theme_enable_demo_import', 'option');
        if (in_array($value, array(true, 1, '1', 'true', 'yes', 'on'), true)) {
            update_option(wp_theme_global_feature_flag_option_name('theme_enable_demo_import'), '1', false);
        }
    }
}
add_action('admin_init', 'wp_theme_prime_global_setup_flags', 30);


function wp_theme_optional_cpt_option_map() {
    return [
        'theme_booking' => 'theme_enable_booking_cpt',
        'event'         => 'theme_enable_event_cpt',
        'products'      => 'theme_enable_products_cpt',
        'case-study'    => 'theme_enable_case_study_cpt',
        'testimonial'   => 'theme_enable_testimonial_cpt',
    ];
}

function wp_theme_filter_optional_cpt_args($args, $post_type) {
    $map = wp_theme_optional_cpt_option_map();
    if (!isset($map[$post_type])) {
        return $args;
    }

    $is_enabled = (bool) wp_theme_acf_get($map[$post_type], 'option', 0);
    $is_enabled = (bool) apply_filters('wp_theme_optional_cpt_enabled', $is_enabled, $post_type, $map[$post_type]);
    if ($is_enabled) {
        return $args;
    }

    $args['public'] = false;
    $args['publicly_queryable'] = false;
    $args['show_ui'] = false;
    $args['show_in_menu'] = false;
    $args['show_in_admin_bar'] = false;
    $args['show_in_nav_menus'] = false;
    $args['show_in_rest'] = false;
    $args['has_archive'] = false;
    $args['rewrite'] = false;
    $args['query_var'] = false;
    $args['exclude_from_search'] = true;

    return $args;
}
add_filter('register_post_type_args', 'wp_theme_filter_optional_cpt_args', 10, 2);

function wp_theme_setup() {
    load_theme_textdomain('wp-theme', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('automatic-feed-links');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['comment-form','comment-list','gallery','caption','style','script','search-form']);

    register_nav_menus([
        'wp-header-top-menu' => __('WP Header Top Menu', 'wp-theme'),
        'wp-header-menu'     => __('WP Header Menu', 'wp-theme'),
        'wp-footer-menu'     => __('WP Footer Menu', 'wp-theme'),
    ]);
}
add_action('after_setup_theme', 'wp_theme_setup');


function wp_theme_front_page_request() {
    return is_front_page();
}

function wp_theme_current_view_content() {
    if (!is_singular()) {
        return '';
    }
    $post_id = get_queried_object_id();
    if (!$post_id) {
        return '';
    }
    return (string) get_post_field('post_content', $post_id);
}

function wp_theme_page_uses_library($library) {
    $content = wp_theme_current_view_content();
    if ($content === '') {
        return false;
    }
    $needles = [];
    if ($library === 'alpine') {
        $needles = ['x-data', 'x-show', 'x-bind', 'x-on:', 'x-cloak', 'alpine'];
    } elseif ($library === 'lightbox') {
        $needles = ['glightbox', 'lightbox', 'data-gallery', 'wpbb-gallery', 'gallery'];
    }
    foreach ($needles as $needle) {
        if (stripos($content, $needle) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * The parent is intentionally presentation-free on the front end.
 * Child themes own all visual CSS; the parent supplies PHP/JS functionality and libraries.
 */
function wp_theme_enqueue_assets() {
    // No parent presentation stylesheet is enqueued by design.
}
add_action('wp_enqueue_scripts', 'wp_theme_enqueue_assets', 20);
/**
 * Maintained suite stylesheets. Bespoke/legacy children sharing this parent are
 * intentionally excluded so parent updates do not take over their shell/CSS.
 */
function wp_theme_current_suite_stylesheets() {
    return array(
        'wp-bbtheme',
        'wp-bbtheme-child-automotive',
        'wp-bbtheme-child-building-services',
        'wp-bbtheme-child-business',
        'wp-bbtheme-child-elearning',
        'wp-bbtheme-child-hotel',
        'wp-bbtheme-child-insurance',
        'wp-bbtheme-child-logistics',
        'wp-bbtheme-child-medicine',
        'wp-bbtheme-child-realestate',
        'wp-bbtheme-child-restaurant',
        'wp-bbtheme-child-travel',
        'wp-bbtheme-child-woo-clouthes',
        'wp-bbtheme-child-woo-events',
        'wp-bbtheme-child-woo-tech-shop',
    );
}

function wp_theme_is_current_suite_theme() {
    $theme = wp_get_theme();
    $stylesheet = (string) $theme->get_stylesheet();
    $template = (string) $theme->get_template();

    if ( 'wp-bbtheme' === $stylesheet ) {
        return true;
    }
    if ( 'wp-bbtheme' !== $template ) {
        return false;
    }

    // Keep the maintained suite working even when WordPress has renamed a
    // child folder during an upload/replace. Legacy Garilla children use a
    // different text domain/version and therefore remain outside this shell.
    $theme_name = (string) $theme->get( 'Name' );
    $version = (string) $theme->get( 'Version' );
    $declared_suite_child = 0 === strpos( $theme_name, 'WP BBTheme Child' ) && $version && version_compare( $version, '3.8.0', '>=' );

    return $declared_suite_child || in_array( $stylesheet, wp_theme_current_suite_stylesheets(), true );
}





function wp_theme_disable_unused_frontend_assets() {
    if (is_admin()) {
        return;
    }

    if ((bool) wp_theme_acf_get('theme_disable_dashicons_front', 'option', 1) && !is_user_logged_in()) {
        wp_dequeue_style('dashicons');
    }

    if ((bool) wp_theme_acf_get('theme_disable_wp_embed_front', 'option', 1)) {
        wp_dequeue_script('wp-embed');
    }

    if (!wp_theme_front_page_request()) {
        return;
    }

    if ((bool) wp_theme_acf_get('theme_disable_theme_js_home', 'option', 0)) {
        wp_dequeue_script('wp-theme-child-app');
        wp_dequeue_script('wp-theme-inline');
    }

    if ((bool) wp_theme_acf_get('theme_disable_child_js_home', 'option', 0)) {
        wp_dequeue_script('wp-theme-child-app');
    }

    if ((bool) wp_theme_acf_get('theme_disable_block_css_home', 'option', 0)) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('classic-theme-styles');
    }

    if ((bool) wp_theme_acf_get('theme_disable_global_styles_home', 'option', 0)) {
        wp_dequeue_style('global-styles');
    }
}
add_action('wp_enqueue_scripts', 'wp_theme_disable_unused_frontend_assets', 999);

function wp_theme_cleanup() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
    remove_action('template_redirect', 'wp_shortlink_header', 11);
}
add_action('init', 'wp_theme_cleanup');

function wp_theme_body_classes($classes) {
    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post && !empty($post->post_name)) {
            $classes[] = sanitize_html_class($post->post_name);
        }
    }
    if (is_page()) {
        $parents = get_post_ancestors(get_the_ID());
        $id = $parents ? $parents[count($parents) - 1] : get_the_ID();
        if ($id) {
            $slug = get_post_field('post_name', $id);
            if ($slug) {
                $classes[] = 'top-parent-' . sanitize_html_class(strtolower($slug));
            }
        }
    }
    return $classes;
}
add_filter('body_class', 'wp_theme_body_classes');

function wp_theme_skip_link() {
    echo '<a class="skip-link screen-reader-text skip-link" href="#wp-theme-main">' . esc_html__('Skip to content', 'wp-theme') . '</a>';
}
add_action('wp_body_open', 'wp_theme_skip_link');

function wp_theme_pattern_categories() {
    register_block_pattern_category('wp-patterns-main', ['label' => __('WP Patterns', 'wp-theme')]);
    register_block_pattern_category('wp-patterns-main-core', ['label' => __('WP Core Patterns', 'wp-theme')]);
    register_block_pattern_category('wp-theme-current', ['label' => __('Current Theme', 'wp-theme')]);
    register_block_pattern_category('wp-theme-forms', ['label' => __('Forms', 'wp-theme')]);
}
add_action('init', 'wp_theme_pattern_categories');

function wp_theme_breadcrumbs_shortcode() {
    if (function_exists('yoast_breadcrumb')) {
        return yoast_breadcrumb('<nav class="wp-theme-breadcrumbs" aria-label="Breadcrumbs">', '</nav>', false);
    }
    if (function_exists('bcn_display')) {
        ob_start();
        echo '<nav class="wp-theme-breadcrumbs" aria-label="Breadcrumbs">';
        bcn_display();
        echo '</nav>';
        return ob_get_clean();
    }
    $items = ['<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'wp-theme') . '</a>'];
    if (is_category() || is_singular('post')) {
        $items[] = esc_html__('Blog', 'wp-theme');
    }
    if (is_singular()) {
        $items[] = esc_html(get_the_title());
    } elseif (is_archive()) {
        $items[] = esc_html(post_type_archive_title('', false) ?: get_the_archive_title());
    } elseif (is_search()) {
        $items[] = sprintf(esc_html__('Search: %s', 'wp-theme'), get_search_query());
    }
    return '<nav class="wp-theme-breadcrumbs" aria-label="Breadcrumbs">' . implode(' &rsaquo; ', array_filter($items)) . '</nav>';
}
add_shortcode('wp_theme_breadcrumbs', 'wp_theme_breadcrumbs_shortcode');

function wp_theme_more_posts_intro_shortcode() {
    $text = '';
    if (function_exists('wp_theme_acf_get')) {
        $text = wp_theme_acf_get('single_blog_post_more_posts_intro', get_the_ID(), '');
    }
    $blog_url = get_post_type_archive_link('post') ?: home_url('/blog/');
    $label = get_locale() === 'lv' ? 'Uz blogu' : 'Back to Blog';
    return '<div class="wp-theme-meta"><a class="wp-block-button__link" href="' . esc_url($blog_url) . '">' . esc_html($label) . '</a></div>' . wp_kses_post($text);
}
add_shortcode('single_blog_post_more_posts_intro', 'wp_theme_more_posts_intro_shortcode');

function wp_theme_article_intro_shortcode() {
    if (!function_exists('wp_theme_acf_get')) {
        return '';
    }
    return wp_kses_post(wp_theme_acf_get('single_blog_post_intro', get_the_ID(), ''));
}
add_shortcode('single_blog_post_intro', 'wp_theme_article_intro_shortcode');

function wp_theme_add_svg_upload($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'wp_theme_add_svg_upload');

add_image_size('haizdesign-admin-post-featured-image', 120, 120, false);
function wp_theme_add_thumbnail_column($columns) {
    $columns['haizdesign_thumb'] = __('Featured Image', 'wp-theme');
    return $columns;
}
add_filter('manage_posts_columns', 'wp_theme_add_thumbnail_column', 2);
add_filter('manage_pages_columns', 'wp_theme_add_thumbnail_column', 2);
function wp_theme_show_thumbnail_column($column, $post_id) {
    if ($column === 'haizdesign_thumb') {
        if (function_exists('the_post_thumbnail')) {
            echo get_the_post_thumbnail($post_id, 'haizdesign-admin-post-featured-image');
        }
    }
}
add_action('manage_posts_custom_column', 'wp_theme_show_thumbnail_column', 5, 2);
add_action('manage_pages_custom_column', 'wp_theme_show_thumbnail_column', 5, 2);

function wp_theme_disable_comments() {
    foreach (['post', 'page'] as $type) {
        if (post_type_supports($type, 'comments')) {
            remove_post_type_support($type, 'comments');
        }
    }
    remove_menu_page('edit-comments.php');
}
add_action('admin_init', 'wp_theme_disable_comments');

function wp_theme_comments_redirect() {
    if (is_singular(['post', 'page']) && (comments_open() || get_comments_number())) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}
add_action('template_redirect', 'wp_theme_comments_redirect');


function wp_theme_login_logo() {
    if (!wp_theme_acf_ready()) {
        return;
    }

    $enabled = wp_theme_acf_get('theme_login_logo_enabled', 'option', 0);
    $logo = wp_theme_acf_get('theme_login_logo', 'option', '');

    if (empty($enabled) || empty($logo)) {
        return;
    }

    $logo_url = '';
    if (is_array($logo)) {
        if (!empty($logo['url'])) {
            $logo_url = $logo['url'];
        } elseif (!empty($logo['ID'])) {
            $logo_url = wp_get_attachment_image_url((int) $logo['ID'], 'full');
        } elseif (!empty($logo['id'])) {
            $logo_url = wp_get_attachment_image_url((int) $logo['id'], 'full');
        }
    } elseif (is_numeric($logo)) {
        $logo_url = wp_get_attachment_image_url((int) $logo, 'full');
    } elseif (is_string($logo)) {
        $logo_url = $logo;
    }

    if (!$logo_url) {
        return;
    }

    $width  = function_exists('wp_theme_acf_get') ? wp_theme_acf_get('theme_login_logo_width', 'option', '160') : '160';
    $height = function_exists('wp_theme_acf_get') ? wp_theme_acf_get('theme_login_logo_height', 'option', '80') : '80';

    echo '<style id="wp-theme-login-logo">
        body.login{background:#f6f7fb;}
        .login #login{width:min(92vw,380px);padding-top:5vh;}
        .login #login h1{margin-bottom:18px;}
        .login #login h1 a,
        .login h1 a{
            background: none !important;
            background-image: url(' . esc_url($logo_url) . ') !important;
            background-position: center center !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            width:' . absint($width) . 'px !important;
            height:' . absint($height) . 'px !important;
            max-width:100% !important;
            display:block !important;
            text-indent:-9999px !important;
            overflow:hidden !important;
            padding-bottom:0 !important;
            margin:0 auto !important;
        }
        .login form{
            border:1px solid #d9dde3;
            border-radius:16px;
            box-shadow:0 10px 28px rgba(17,24,39,.08);
        }
        .login #backtoblog,.login #nav{text-align:center;}
    </style>';
}
add_action('login_enqueue_scripts', 'wp_theme_login_logo', 99);

add_filter('login_headertext', function () {
    return get_bloginfo('name');
});

add_filter('login_headerurl', function () {
    return home_url('/');
});

add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (!function_exists('acf_add_options_page')) {
        echo '<div class="notice notice-warning"><p><strong>WP BBTheme:</strong> ACF Pro is required for Theme Styles options.</p></div>';
    }
});

foreach (['sector-demos.php','blog-system.php','site-essentials.php','content-productivity.php','acf-theme-options.php','project-mode.php','performance-tools.php','booking.php','events.php','developer-tools.php','admin-ordering.php','tpl-helper.php','shortcodes.php','loc.php','info.php','block-types.php','wp-nav-walker.php','nav-menu-enhancements.php','integrations.php','site-shell.php','sector-finder-block.php','sector-directory.php','item-gallery.php','forms-library.php','editor-patterns.php','v381024-routing.php'] as $file) {
    $path = get_template_directory() . '/inc/Custom/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

$bbtheme_animation_bootstrap = get_template_directory() . '/inc/animations/bootstrap.php';
if (file_exists($bbtheme_animation_bootstrap)) {
    require_once $bbtheme_animation_bootstrap;
}
/**
 * Keep menu_order support on posts, pages, and public CPTs.
 * The admin drag-and-drop UI itself is handled in inc/Custom/admin-ordering.php.
 */
add_action('init', function () {
    $post_types = get_post_types([
        'public' => true,
    ], 'names');

    foreach ($post_types as $post_type) {
        if ($post_type === 'attachment') {
            continue;
        }

        add_post_type_support($post_type, 'page-attributes');
    }
}, 20);

/**
 * Demo header/footer helpers.
 */
if (!function_exists('wp_theme_demo_logo')) {
    function wp_theme_demo_logo($variant = 'dark') {
        $variant = ($variant === 'light') ? 'light' : 'dark';
        $file = $variant === 'light' ? 'demo-logo-light.png' : 'demo-logo-dark.png';
        $path = get_template_directory() . '/assets/img/' . $file;
        if (file_exists($path)) {
            return '<img class="wp-theme-demo-logo-img" src="' . esc_url(get_template_directory_uri() . '/assets/img/' . $file) . '" alt="' . esc_attr(get_bloginfo('name') ?: __('Demo WP Theme', 'wp-theme')) . '">';
        }
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            return wp_get_attachment_image($custom_logo_id, 'full', false, array('class' => 'wp-theme-demo-logo-img'));
        }
        return '<span class="wp-theme-demo-logo-text">' . esc_html__('Demo WP Theme', 'wp-theme') . '</span>';
    }
}

if (!function_exists('wp_theme_demo_page_url')) {
    function wp_theme_demo_page_url($slug, $fallback = '') {
        $page = get_page_by_path($slug);
        if ($page) {
            return get_permalink($page);
        }
        return $fallback ?: home_url('/' . trim($slug, '/') . '/');
    }
}

if (!function_exists('wp_theme_demo_fallback_menu')) {
    function wp_theme_demo_fallback_menu($class = 'wp-theme-demo-menu') {
        $items = array(
            array(__('Home', 'wp-theme'), home_url('/'), true),
            array(__('About', 'wp-theme'), wp_theme_demo_page_url('about'), (bool) get_page_by_path('about')),
            array(__('Blog', 'wp-theme'), get_permalink(get_option('page_for_posts')) ?: wp_theme_demo_page_url('blog'), (bool) (get_option('page_for_posts') || get_page_by_path('blog'))),
            array(__('Contact', 'wp-theme'), wp_theme_demo_page_url('contact'), (bool) get_page_by_path('contact')),
        );
        $html = '<ul class="' . esc_attr($class) . '">';
        foreach ($items as $item) {
            if (!$item[2]) {
                continue;
            }
            $html .= '<li><a href="' . esc_url($item[1]) . '">' . esc_html($item[0]) . '</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }
}

if (!function_exists('wp_theme_demo_menu')) {
    function wp_theme_demo_menu($location = 'wp-header-menu', $class = 'wp-theme-demo-menu') {
        if (has_nav_menu($location)) {
            return wp_nav_menu(array(
                'theme_location' => $location,
                'container' => false,
                'menu_class' => $class,
                'fallback_cb' => false,
                'echo' => false,
                'depth' => 4,
            ));
        }
        return wp_theme_demo_fallback_menu($class);
    }
}
