<?php
/**
 * WP BB Home & Garden v4.0.29
 * Protected demo workflow, unique Product Guide photography and B2B home promo.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V429_VERSION')) {
    define('WPBBSHOP_V429_VERSION', '4.0.29');
}

/** Demo tools are opt-in and auto-lock after every destructive/seed action. */
function wpbbshop_v429_demo_tools_enabled() {
    $options = get_option('wpbbshop_theme_options', array());
    return is_array($options) && isset($options['demo_tools_enabled']) && (string) $options['demo_tools_enabled'] === '1';
}

function wpbbshop_v429_lock_demo_tools() {
    $options = get_option('wpbbshop_theme_options', array());
    $options = is_array($options) ? $options : array();
    $options['demo_tools_enabled'] = '0';
    update_option('wpbbshop_theme_options', $options, false);
}

/**
 * Topic-specific real photography for Product Guides.
 *
 * v4.0.25 bundled illustrated placeholders for offline demos. The v4.0.26+
 * media layer already owns a curated Wikimedia Commons photo source per guide
 * and progressively caches those files into Media Library. v4.0.29 makes that
 * real-photo layer authoritative so stale local placeholder art can never win.
 */
function wpbbshop_v429_guide_image_url($post_id) {
    $post_id = absint($post_id);
    if (!$post_id || (string) get_post_meta($post_id, '_wpbbshop_v422_guide', true) !== '1') {
        return '';
    }

    $key = sanitize_key((string) get_post_meta($post_id, '_wpbbshop_v422_guide_key', true));
    if ($key === '') {
        return '';
    }

    if (function_exists('wpbbshop_v426_blog_sources')) {
        $sources = wpbbshop_v426_blog_sources();
        if (empty($sources[$key])) {
            return '';
        }
    }

    // Prefer the locally cached Media Library copy. Until the cache job has
    // completed, use the matching Commons photograph directly rather than the
    // old green illustration card.
    if (function_exists('wpbbshop_v426_cached_or_remote')) {
        return (string) wpbbshop_v426_cached_or_remote('blog', $key, 'large', 1600);
    }
    if (function_exists('wpbbshop_v426_source_url')) {
        return (string) wpbbshop_v426_source_url('blog', $key, 1600);
    }

    return '';
}

/**
 * Old demo refreshes created repeated copies of the same guide key. Keep one
 * per language/key and move only theme-owned duplicates to Trash.
 */
function wpbbshop_v429_cleanup_duplicate_guides() {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }
    if ((string) get_option('wpbbshop_v429_guide_cleanup', '') === WPBBSHOP_V429_VERSION) {
        return;
    }

    $ids = get_posts(array(
        'post_type'        => 'post',
        'post_status'      => array('publish', 'draft', 'pending', 'private', 'future'),
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'meta_key'         => '_wpbbshop_v422_guide',
        'meta_value'       => '1',
        'orderby'          => 'ID',
        'order'            => 'DESC',
        'suppress_filters' => true,
    ));

    $kept = array();
    foreach ((array) $ids as $id) {
        $id = absint($id);
        if (!$id) {
            continue;
        }
        $key = sanitize_key((string) get_post_meta($id, '_wpbbshop_v422_guide_key', true));
        if ($key === '') {
            continue;
        }
        $lang = sanitize_key((string) get_post_meta($id, '_wpbbshop_v422_lang', true));
        $lang = $lang === 'lv' ? 'lv' : 'en';
        $bucket = $lang . ':' . $key;

        if (!isset($kept[$bucket])) {
            $kept[$bucket] = $id;
            $image = wpbbshop_v429_guide_image_url($id);
            if ($image) {
                update_post_meta($id, '_wpbbshop_v423_featured_image_url', esc_url_raw($image));
            }
            continue;
        }

        // This meta flag belongs only to the theme demo importer, so real posts
        // without it can never be touched by this maintenance pass.
        wp_trash_post($id);
    }

    update_option('wpbbshop_v429_guide_cleanup', WPBBSHOP_V429_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v429_cleanup_duplicate_guides', 300);
add_action('after_switch_theme', 'wpbbshop_v429_cleanup_duplicate_guides', 300);

// Retire older standalone demo screens so there is exactly one Demo workspace.
add_action('admin_menu', function() {
    remove_submenu_page('themes.php', 'wpbbshop-green-demo');
    remove_submenu_page('themes.php', 'wpbbshop-garden-demo-312');
}, 9999);

/** Ensure archive/single featured-image output uses the same unique local photo. */
add_filter('post_thumbnail_html', function($html, $post_id, $post_thumbnail_id, $size, $attr) {
    $url = wpbbshop_v429_guide_image_url($post_id);
    if (!$url) {
        return $html;
    }
    $alt = get_the_title($post_id);
    $class = 'wp-post-image wpbb-v429-guide-photo';
    if (is_array($attr) && !empty($attr['class'])) {
        $class .= ' ' . sanitize_text_field((string) $attr['class']);
    }
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" loading="lazy" decoding="async">';
}, PHP_INT_MAX, 5);

/** Homepage B2B section driven by the reusable Woo Support B2B module. */
function wpbbshop_v429_b2b_home_html() {
    $options = function_exists('wpbbshop_get_theme_options') ? wpbbshop_get_theme_options() : array();
    if (isset($options['show_b2b_home_section']) && (string) $options['show_b2b_home_section'] !== '1') {
        return '';
    }
    if (!function_exists('iws_b2b_is_enabled') || !iws_b2b_is_enabled()) {
        return '';
    }

    $lv = function_exists('wpbbshop_v400_current_language') && wpbbshop_v400_current_language() === 'lv';
    if (function_exists('iws_b2b_home_promo_html')) {
        $promo = iws_b2b_home_promo_html(array(
            'kicker' => $lv ? 'B2B UN TIRDZNIECĪBAI' : 'B2B & TRADE',
            'title'  => $lv ? 'Biznesa cenas profesionāļiem un projektu pasūtījumiem' : 'Business pricing for trade and project orders',
            'copy'   => $lv ? 'Piesakies uzņēmuma kontam, lai saņemtu biznesa cenas, apjoma noteikumus, norēķinu nosacījumus un atsevišķu B2B portālu.' : 'Apply for a trade account to access business pricing, volume rules, account terms and a dedicated B2B portal.',
        ));
    } else {
        $portal = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('b2b-dashboard') : home_url('/my-account/');
        $promo = '<section class="iws-b2b-home-promo"><div class="iws-b2b-home-promo__copy"><span class="iws-b2b-home-promo__kicker">B2B &amp; TRADE</span><h2>' . esc_html($lv ? 'Biznesa konti profesionāļiem' : 'Business accounts for professionals') . '</h2><p>' . esc_html($lv ? 'Piekļūsti biznesa cenām un apjoma pasūtījumiem.' : 'Access trade pricing and volume ordering.') . '</p><div class="iws-b2b-home-promo__actions"><a class="iws-b2b-home-promo__primary" href="' . esc_url($portal) . '">' . esc_html($lv ? 'Atvērt B2B' : 'Open B2B') . '</a></div></div></section>';
    }

    if ($promo === '') {
        return '';
    }
    return '<div class="wpbb-v400-shell wpbb-v429-b2b-home">' . $promo . '</div>';
}

add_filter('do_shortcode_tag', function($output, $tag) {
    if ($tag !== 'wpbbshop_home' || strpos($output, 'wpbb-v429-b2b-home') !== false) {
        return $output;
    }
    $b2b = wpbbshop_v429_b2b_home_html();
    if ($b2b === '') {
        return $output;
    }

    foreach (array('<section class="wpbb-v400-shell wpbb-v422-guides"', '<section class="wpbb-v400-shell wpbb-v400-services"') as $needle) {
        $pos = strpos($output, $needle);
        if ($pos !== false) {
            return substr($output, 0, $pos) . $b2b . substr($output, $pos);
        }
    }
    return $output . $b2b;
}, 150, 2);

add_action('wp_enqueue_scripts', function() {
    $path = get_stylesheet_directory() . '/assets/css/v429-demo-safety-b2b.css';
    if (is_readable($path)) {
        wp_enqueue_style(
            'wpbbshop-v429-demo-safety-b2b',
            get_stylesheet_directory_uri() . '/assets/css/v429-demo-safety-b2b.css',
            array('wpbbshop-v428-media-hardening'),
            (string) filemtime($path)
        );
    }
}, PHP_INT_MAX);

add_action('admin_enqueue_scripts', function() {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (!in_array($page, array('wpbbshop-platform', 'wpbbshop-theme-settings'), true)) {
        return;
    }
    $path = get_stylesheet_directory() . '/assets/css/v429-admin.css';
    if (is_readable($path)) {
        wp_enqueue_style(
            'wpbbshop-v429-admin',
            get_stylesheet_directory_uri() . '/assets/css/v429-admin.css',
            array(),
            (string) filemtime($path)
        );
    }
}, PHP_INT_MAX);
