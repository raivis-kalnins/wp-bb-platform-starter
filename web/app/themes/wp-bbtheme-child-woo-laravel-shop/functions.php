<?php
/** WP BB HOME & GARDEN Gutenberg Commerce theme functions. PHP 7.4 compatible: no PHP 8-only syntax/functions. */
defined('ABSPATH') || exit;

define('WPBBSHOP_VERSION', wp_get_theme()->get('Version'));

add_action('after_setup_theme', function() {
    load_theme_textdomain('wpbbshop', get_stylesheet_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', ['height'=>100, 'width'=>260, 'flex-width'=>true, 'flex-height'=>true]);
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style('assets/css/theme.css');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus([
        'primary' => __('Primary menu', 'wpbbshop'),
        'top'     => __('Top utility menu', 'wpbbshop'),
        'footer'  => __('Footer menu', 'wpbbshop'),
        'service' => __('Customer service menu', 'wpbbshop'),
    ]);
});

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('wpbbshop-theme', get_stylesheet_directory_uri() . '/assets/css/theme.css', [], WPBBSHOP_VERSION);
    wp_enqueue_script('wpbbshop-theme', get_stylesheet_directory_uri() . '/assets/js/theme.js', ['jquery'], WPBBSHOP_VERSION, true);
    wp_localize_script('wpbbshop-theme', 'WpbbshopTheme', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpbbshop_ajax'),
    ]);
}, 20);

add_action('enqueue_block_editor_assets', function() {
    wp_enqueue_style('wpbbshop-editor', get_stylesheet_directory_uri() . '/assets/css/theme.css', [], WPBBSHOP_VERSION);
});

remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
add_filter('woocommerce_show_page_title', '__return_false');
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

add_filter('body_class', function($classes) {
    $classes[] = 'wpbbshop-theme';
    if (function_exists('is_woocommerce') && is_woocommerce()) { $classes[] = 'wpbbshop-woocommerce'; }
    if (function_exists('wpbbshop_get_theme_option') && wpbbshop_get_theme_option('dark_header', '1') === '1') { $classes[] = 'wpbbshop-dark-header'; }
    if (function_exists('wpbbshop_get_theme_option') && wpbbshop_get_theme_option('sticky_header', '0') === '1') { $classes[] = 'wpbbshop-sticky-header'; }
    return $classes;
});

function wpbbshop_logo_attachment_class($attachment_id) {
    $attachment_id = absint($attachment_id);
    if (!$attachment_id) {
        return '';
    }
    $meta = wp_get_attachment_metadata($attachment_id);
    if (!empty($meta['width']) && !empty($meta['height'])) {
        $ratio = (float) $meta['width'] / max(1, (float) $meta['height']);
        // The supplied WP BB HOME & GARDEN footer logo is a square canvas with a wide logo centered in it.
        // Mark near-square source files so CSS can crop the empty canvas without distorting the mark.
        if ($ratio >= 0.82 && $ratio <= 1.22) {
            return ' wpbbshop-logo-square-source';
        }
    }
    return '';
}

function wpbbshop_logo_html() {
    $logo_id = absint(function_exists('wpbbshop_get_theme_option') ? wpbbshop_get_theme_option('logo_id', '') : 0);
    if ($logo_id) {
        $img = wp_get_attachment_image($logo_id, 'full', false, array(
            'class' => 'wpbbshop-logo-image wpbbshop-logo-image-custom',
            'loading' => 'eager',
            'decoding' => 'async',
            'alt' => get_bloginfo('name') ?: 'WP BB Home & Garden',
        ));
        if ($img) {
            return '<a class="wpbbshop-logo wpbbshop-brand-v410 is-custom" href="' . esc_url(home_url('/')) . '" aria-label="WP BB Home & Garden">' . $img . '</a>';
        }
    }
    $src = trailingslashit(get_stylesheet_directory_uri()) . 'assets/img/wpbb-home-garden-logo.svg?v=410';
    return '<a class="wpbbshop-logo wpbbshop-brand-v410" href="' . esc_url(home_url('/')) . '" aria-label="WP BB Home & Garden"><img class="wpbbshop-logo-image" src="' . esc_url($src) . '" alt="WP BB Home & Garden" width="620" height="136" loading="eager" decoding="async"></a>';
}

function wpbbshop_category_icon_html($term = null, $force_default = true) {
    if (!$force_default && $term && isset($term->term_id)) {
        $thumb_id = absint(get_term_meta($term->term_id, 'thumbnail_id', true));
        if ($thumb_id) {
            return wp_get_attachment_image($thumb_id, 'thumbnail', false, array('class' => 'wpbbshop-cat-thumb'));
        }
    }

    // Tabler outline icons, selected by WP BB HOME & GARDEN top-level category so the grid is easy to scan.
    $icons = array(
        'tool' => '<path d="M7 10h3v-3l-3.5 -3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8l3.5 3.5" />',
        'ruler-measure' => '<path d="M19.875 12c.621 0 1.125 .512 1.125 1.143v5.714c0 .631 -.504 1.143 -1.125 1.143h-15.875a1 1 0 0 1 -1 -1v-5.857c0 -.631 .504 -1.143 1.125 -1.143h15.75" /><path d="M9 12v2" /><path d="M6 12v3" /><path d="M12 12v3" /><path d="M18 12v3" /><path d="M15 12v2" /><path d="M3 3v4" /><path d="M3 5h18" /><path d="M21 3v4" />',
        'hammer' => '<path d="M11.414 10l-7.383 7.418a2.091 2.091 0 0 0 0 2.967a2.11 2.11 0 0 0 2.976 0l7.407 -7.385" /><path d="M18.121 15.293l2.586 -2.586a1 1 0 0 0 0 -1.414l-7.586 -7.586a1 1 0 0 0 -1.414 0l-2.586 2.586a1 1 0 0 0 0 1.414l7.586 7.586a1 1 0 0 0 1.414 0" />',
        'lawn-mower' => '<path d="M6 11h5.38a1 1 0 0 1 .9 .55l.72 1.45h5a1 1 0 0 1 1 1v2" /><path d="M3 4h1.13a1 1 0 0 1 1 .86l1.59 11.14" /><path d="M17 18h-8" /><path d="M9 18a2 2 0 1 1 -4 0a2 2 0 0 1 4 0" /><path d="M21 18a2 2 0 1 1 -4 0a2 2 0 0 1 4 0" />',
        'settings-cog' => '<path d="M12.003 21c-.732 .001 -1.465 -.438 -1.678 -1.317a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c.886 .215 1.325 .957 1.318 1.694" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.001 19a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M19.001 15.5v1.5" /><path d="M19.001 21v1.5" /><path d="M22.032 17.25l-1.299 .75" /><path d="M17.27 20l-1.3 .75" /><path d="M15.97 17.25l1.3 .75" /><path d="M20.733 20l1.3 .75" />',
        'package' => '<path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /><path d="M16 5.25l-8 4.5" />',
    );
    $haystack = '';
    if ($term) {
        $haystack = strtolower(remove_accents(trim((isset($term->slug) ? $term->slug : '') . ' ' . (isset($term->name) ? $term->name : ''))));
    }
    $icon = 'tool';
    if (strpos($haystack, 'specinstrument') !== false || strpos($haystack, 'mer') !== false) { $icon = 'ruler-measure'; }
    elseif (strpos($haystack, 'servis') !== false) { $icon = 'hammer'; }
    elseif (strpos($haystack, 'darz') !== false) { $icon = 'lawn-mower'; }
    elseif (strpos($haystack, 'rezerves') !== false || strpos($haystack, 'dala') !== false) { $icon = 'settings-cog'; }
    elseif (strpos($haystack, 'saimniec') !== false || strpos($haystack, 'preces') !== false) { $icon = 'package'; }

    return '<span class="wpbbshop-cat-svg wpbbshop-cat-svg--' . esc_attr($icon) . '" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $icons[$icon] . '</svg></span>';
}

function wpbbshop_header_icon_svg($type) {
    $icons = array(
        'account' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        'wishlist' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.3 5.7a5 5 0 0 0-7.1 0L12 6.9l-1.2-1.2a5 5 0 0 0-7.1 7.1L12 21l8.3-8.2a5 5 0 0 0 0-7.1Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
        'compare' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 7h13m0 0-4-4m4 4-4 4M17 17H4m0 0 4 4m-4-4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'cart' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 4h2l2.2 10.3a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 2-1.6L20 8H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" fill="currentColor"/></svg>',
    );
    return isset($icons[$type]) ? $icons[$type] : '';
}

function wpbbshop_benefit_icon_svg($type) {
    $icons = array(
        'delivery' => '<svg viewBox="0 0 48 48" fill="none"><path d="M5 14h25v20H5V14Zm25 7h7l6 7v6H30V21Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><circle cx="15" cy="37" r="4" stroke="currentColor" stroke-width="3"/><circle cx="36" cy="37" r="4" stroke="currentColor" stroke-width="3"/></svg>',
        'sale' => '<svg viewBox="0 0 48 48" fill="none"><path d="M8 24 24 8h14v14L22 38 8 24Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><circle cx="32" cy="16" r="3" fill="currentColor"/><path d="m18 30 12-12" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><circle cx="19" cy="19" r="2" fill="currentColor"/><circle cx="29" cy="29" r="2" fill="currentColor"/></svg>',
        'shield' => '<svg viewBox="0 0 48 48" fill="none"><path d="M24 5 39 11v11c0 10-6.5 17-15 21C15.5 39 9 32 9 22V11l15-6Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="m17 24 5 5 10-11" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    );
    return '<span class="wpbbshop-benefit-icon" aria-hidden="true">' . (isset($icons[$type]) ? $icons[$type] : $icons['delivery']) . '</span>';
}

function wpbbshop_translate_topbar_item($item) {
    $map = array(
        '30 days free return' => '30 dienu bezmaksas atgriešana',
        '30 DAYS FREE RETURN' => '30 dienu bezmaksas atgriešana',
        'FREE SHIPPING FOR OVER $40' => 'Bezmaksas piegāde pasūtījumiem virs 40 €',
        'BEST WORLDWIDE DELIVERY' => 'Labākā piegāde visā pasaulē',
    );
    return isset($map[$item]) ? $map[$item] : $item;
}

function wpbbshop_nav_menu($location, $fallback_items = []) {
    if (has_nav_menu($location)) {
        return wp_nav_menu(['theme_location'=>$location, 'container'=>false, 'echo'=>false, 'menu_class'=>'wpbbshop-menu wpbbshop-menu-'.$location]);
    }
    $out = '<ul class="wpbbshop-menu wpbbshop-menu-'.$location.'">';
    foreach ($fallback_items as $label=>$url) $out .= '<li><a href="'.esc_url($url).'">'.esc_html($label).'</a></li>';
    return $out.'</ul>';
}




function wpbbshop_default_options() {
    return array(
        'logo_id' => '',
        'logo_width' => '340',
        'logo_height' => '132',
        'hero_image_id' => '',
        'phone' => '',
        'email' => '',
        'address' => 'Bauskas 63 - 1a/k1, Riga, LV-1004',
        'work_hours' => '',
        'topbar_left' => '',
        'topbar_right' => '',
        'hero_title' => 'Everything for home, garden and DIY',
        'hero_subtitle' => 'A large Home & Garden catalogue with tools, outdoor living, building supplies and everyday essentials.',
        'hero_button' => 'Shop departments',
        'footer_description' => 'WP BB Home & Garden — a fast, scalable WooCommerce store for home, garden, build and DIY.',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_tiktok' => '',
        'social_youtube' => '',
        'brands' => 'YATO,DEMON,ROCKFORCE,VERKE,NEO TOOLS,MAR-POL,LONCIN',
        'enable_wishlist' => '1',
        'enable_compare' => '1',
        'archive_sidebar' => '1',
        'products_per_row' => '4',
        'single_related_limit' => '4',
        'single_recommended_limit' => '4',
        'single_related_columns' => '4',
        'accent_color' => '#178f55',
        'dark_header' => '0',
        'show_topbar' => '1',
        'sticky_header' => '1',
        'show_category_panel' => '1',
        'show_sale_badges' => '1',
        'header_welcome' => '',
        'category_panel_title' => 'Shop departments',
        'footer_payment_methods' => 'Card,Bank transfer,Cash on pickup',
        'footer_delivery_partners' => 'Omniva,Unisend,Latvijas Pasts,Courier',
        'locker_locations_unisend' => '',
        'locker_locations_latvijas_pasts' => '',
        'benefit_1' => 'Delivery across Latvia|Parcel lockers and courier',
        'benefit_2' => 'Store pickup|Free by arrangement',
        'benefit_3' => 'Secure payment|Card, bank transfer and online payment',
        'disable_product_review_form' => '1',
        'enable_kurpirkt_feed' => '1',
        'enable_salidzini_feed' => '1',
        'enable_ceno_feed' => '1',
        'comparison_feed_include_outofstock' => '0',
        'comparison_feed_delivery_cost' => '3.90',
        'comparison_feed_delivery_days' => '3',
        'comparison_feed_shop_days' => '',
        'show_kurpirkt_badge' => '1',
        'show_salidzini_badge' => '1',
        'show_ceno_badge' => '1',
    );
}

function wpbbshop_get_theme_options() {
    $defaults = wpbbshop_default_options();
    $saved = get_option('wpbbshop_theme_options', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    return wp_parse_args($saved, $defaults);
}

function wpbbshop_get_theme_option($key, $default = '') {
    if ($key === 'address' && function_exists('wpbbshop_v414_store_address')) {
        return wpbbshop_v414_store_address();
    }
    $options = wpbbshop_get_theme_options();
    return isset($options[$key]) ? $options[$key] : $default;
}

function wpbbshop_option_parts($key, $default = '') {
    $value = wpbbshop_get_theme_option($key, $default);
    $parts = array_map('trim', explode('|', $value));
    return array_filter($parts);
}

function wpbbshop_page_url($slug, $fallback = '') {
    $page = get_page_by_path($slug);
    if ($page) {
        return get_permalink($page->ID);
    }
    return $fallback ? $fallback : home_url('/' . trim($slug, '/') . '/');
}

/** Client-approved category order used by the homepage, category menu and shop sidebar. */
function wpbbshop_category_display_order($terms) {
    if (!is_array($terms)) { return $terms; }
    usort($terms, function($a, $b) {
        $rank = function($term) {
            $name = isset($term->name) ? $term->name : '';
            $slug = isset($term->slug) ? $term->slug : '';
            $haystack = strtolower(remove_accents($slug . ' ' . $name));
            $haystack = trim(preg_replace('/[^a-z0-9]+/', ' ', $haystack));
            if (strpos($haystack, 'specinstrument') !== false) { return 20; }
            if (strpos($haystack, 'servisa aprikojums') !== false || strpos($haystack, 'servisa aprik') !== false) { return 30; }
            if (strpos($haystack, 'darza tehnika') !== false) { return 40; }
            if (strpos($haystack, 'rezerves dal') !== false) { return 50; }
            if (strpos($haystack, 'saimniecibas preces') !== false || strpos($haystack, 'saimniecibas') !== false) { return 60; }
            if (preg_match('/(^| )instrumenti($| )/', $haystack)) { return 10; }
            return 500;
        };
        $a_rank = $rank($a);
        $b_rank = $rank($b);
        if ($a_rank === $b_rank) {
            return strnatcasecmp(isset($a->name) ? $a->name : '', isset($b->name) ? $b->name : '');
        }
        return $a_rank < $b_rank ? -1 : 1;
    });
    return $terms;
}

function wpbbshop_product_category_links($limit = 12) {
    if (!class_exists('WooCommerce')) {
        return '';
    }
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'number' => absint($limit),
        'parent' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
    ));
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }
    $blocked_demo_slugs = array('accessories','apparel','bundles','home-living','office','tech');
    $terms = array_values(array_filter((array)$terms, function($term) use ($blocked_demo_slugs) {
        return isset($term->slug) && !in_array($term->slug, $blocked_demo_slugs, true);
    }));
    $terms = wpbbshop_category_display_order($terms);
    $out = '';
    foreach ($terms as $term) {
        $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $img = wpbbshop_category_icon_html($term);
        $out .= '<a class="wpbbshop-cat-panel-item" href="' . esc_url(get_term_link($term)) . '">' . $img . '<span>' . esc_html($term->name) . '</span></a>';
    }
    return $out;
}


function wpbbshop_mini_cart_html() {
    if (!class_exists('WooCommerce')) {
        return '';
    }
    ob_start(); ?>
    <div class="wpbbshop-cart-drawer-overlay" hidden></div>
    <aside class="wpbbshop-mini-cart-panel wpbbshop-cart-drawer" id="wpbbshop-mini-cart-panel" hidden aria-label="Grozs">
        <div class="wpbbshop-mini-cart-head"><strong>Grozs</strong><button type="button" class="wpbbshop-mini-cart-close" aria-label="Aizvērt">×</button></div>
        <div class="wpbbshop-mini-cart-content widget_shopping_cart_content">
            <?php woocommerce_mini_cart(); ?>
        </div>
    </aside>
    <?php return ob_get_clean();
}

function wpbbshop_header_html() {
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $cart = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    $wishlist_url = wpbbshop_list_url('wishlist');
    $compare_url = wpbbshop_list_url('compare');
    $wishlist_count = count(wpbbshop_get_list_items('wishlist'));
    $compare_count = count(wpbbshop_get_list_items('compare'));
    $checkout = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    $cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    $cart_total = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_total() : '';
    $category_links = wpbbshop_product_category_links(14);
    ob_start(); ?>
    <header class="wpbbshop-site-header">
        <div class="wpbbshop-container wpbbshop-mainbar">
            <div class="wpbbshop-logo-wrap"><?php echo wpbbshop_logo_html(); ?></div>
            <form role="search" method="get" class="wpbbshop-search wpbbshop-ajax-search" action="<?php echo esc_url(home_url('/')); ?>" autocomplete="off">
                <div class="wpbbshop-search-field-wrap">
                    <input type="search" name="s" placeholder="Meklēt pēc nosaukuma, SKU vai ID..." value="<?php echo esc_attr(get_search_query()); ?>" aria-label="Meklēt produktus">
                    <div class="wpbbshop-search-results" hidden></div>
                </div>
                <input type="hidden" name="post_type" value="product">
                <button type="submit" aria-label="Meklēt">⌕</button>
            </form>
            <div class="wpbbshop-actions">
                <a class="wpbbshop-action" href="<?php echo esc_url($account); ?>"><span class="wpbbshop-action-icon"><?php echo wpbbshop_header_icon_svg('account'); ?></span><span>Konts</span></a>
                <a class="wpbbshop-action" href="<?php echo esc_url($wishlist_url); ?>"><span class="wpbbshop-action-icon"><?php echo wpbbshop_header_icon_svg('wishlist'); ?></span><span>Vēlmju</span><em class="wpbbshop-list-count wpbbshop-wishlist-count"><?php echo (int)$wishlist_count; ?></em></a>
                <a class="wpbbshop-action" href="<?php echo esc_url($compare_url); ?>"><span class="wpbbshop-action-icon"><?php echo wpbbshop_header_icon_svg('compare'); ?></span><span>Salīdzināt</span><em class="wpbbshop-list-count wpbbshop-compare-count"><?php echo (int)$compare_count; ?></em></a>
                <button class="wpbbshop-action wpbbshop-cart-link wpbbshop-mini-cart-toggle" type="button" aria-expanded="false" aria-controls="wpbbshop-mini-cart-panel"><span class="wpbbshop-action-icon"><?php echo wpbbshop_header_icon_svg('cart'); ?></span><span>Grozs</span><em class="wpbbshop-cart-count"><?php echo (int)$cart_count; ?></em><strong class="wpbbshop-cart-total"><?php echo wp_kses_post($cart_total); ?></strong></button>
            </div>
            <?php echo wpbbshop_mini_cart_html(); ?>
        </div>
        <nav class="wpbbshop-nav"><div class="wpbbshop-container wpbbshop-nav-inner">
            <?php if (wpbbshop_get_theme_option('show_category_panel', '1') === '1') : ?><button class="wpbbshop-cat-toggle" type="button" aria-expanded="false" aria-controls="wpbbshop-category-panel">☰ Kategorijas</button><?php endif; ?>
            <button class="wpbbshop-menu-toggle" type="button" aria-expanded="false" aria-controls="wpbbshop-menu-panel" aria-label="Atvērt izvēlni"><span aria-hidden="true">☰</span></button>
        </div></nav>
        <div id="wpbbshop-category-panel" class="wpbbshop-category-panel wpbbshop-category-panel-cats" hidden>
          <div class="wpbbshop-container wpbbshop-category-panel-inner wpbbshop-super-menu wpbbshop-cats-only">
            <div class="wpbbshop-panel-block wpbbshop-panel-cats"><h3><?php echo esc_html(wpbbshop_get_theme_option('category_panel_title', 'Preču kategorijas')); ?></h3><div class="wpbbshop-panel-grid wpbbshop-panel-list"><?php echo $category_links ? $category_links : '<a class="wpbbshop-cat-panel-item" href="' . esc_url($shop) . '"><span class="wpbbshop-cat-svg" aria-hidden="true">☰</span><span>Visas preces</span></a>'; ?></div></div>
          </div>
        </div>
        <div id="wpbbshop-menu-panel" class="wpbbshop-category-panel wpbbshop-menu-panel" hidden>
          <div class="wpbbshop-container wpbbshop-category-panel-inner wpbbshop-super-menu wpbbshop-menu-only">
            <div class="wpbbshop-panel-block wpbbshop-panel-menu"><h3>Informācija</h3><a href="<?php echo esc_url(wpbbshop_page_url('par-mums')); ?>">Par mums</a><a href="<?php echo esc_url(wpbbshop_page_url('piegade')); ?>">Piegāde</a><a href="<?php echo esc_url(wpbbshop_page_url('apmaksa')); ?>">Apmaksa</a><a href="<?php echo esc_url(wpbbshop_page_url('kontakti')); ?>">Kontakti</a><a href="<?php echo esc_url(wpbbshop_page_url('track-your-order')); ?>">Sekot pasūtījumam</a></div>
            <div class="wpbbshop-panel-block wpbbshop-panel-menu"><h3>Veikals</h3><a href="<?php echo esc_url($shop); ?>">Visas preces</a><a href="<?php echo esc_url(add_query_arg('onsale', '1', $shop)); ?>">Akcijas preces</a><a href="<?php echo esc_url($cart); ?>">Grozs</a><a href="<?php echo esc_url($checkout); ?>">Noformēt pasūtījumu</a><a href="<?php echo esc_url($account); ?>">Mans konts</a></div>
          </div>
        </div>
    </header>
    <?php return ob_get_clean();
}
add_shortcode('wpbbshop_header', 'wpbbshop_header_html');

function wpbbshop_social_icon_svg($network) {
    $icons = array(
        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-3.4 0-5 2-5 5v3H6v4h3v8h4v-8h3.2l.8-4H13V9c0-.7.3-1 1-1z" fill="currentColor"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/></svg>',
        'tiktok' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4v10.1a4.6 4.6 0 1 1-4-4.55v3.1a1.7 1.7 0 1 0 1 1.55V4h3zm0 0c.35 2.05 1.58 3.38 4 3.9V11c-1.55-.15-2.9-.72-4-1.65V4z" fill="currentColor"/></svg>',
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8.1a3 3 0 0 0-2.1-2.1C17.1 5.5 12 5.5 12 5.5s-5.1 0-6.9.5A3 3 0 0 0 3 8.1 31 31 0 0 0 2.5 12 31 31 0 0 0 3 15.9 3 3 0 0 0 5.1 18c1.8.5 6.9.5 6.9.5s5.1 0 6.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-3.9 31 31 0 0 0-.5-3.9z" fill="currentColor"/><path d="m10 15 5-3-5-3z" fill="#090b10"/></svg>',
    );
    return isset($icons[$network]) ? $icons[$network] : '';
}

function wpbbshop_footer_socials_html() {
    $networks = array(
        'facebook' => array('label' => 'Facebook', 'url' => wpbbshop_get_theme_option('social_facebook')),
        'instagram' => array('label' => 'Instagram', 'url' => wpbbshop_get_theme_option('social_instagram')),
        'tiktok' => array('label' => 'TikTok', 'url' => wpbbshop_get_theme_option('social_tiktok')),
        'youtube' => array('label' => 'YouTube', 'url' => wpbbshop_get_theme_option('social_youtube')),
    );
    $html = '';
    foreach ($networks as $network => $item) {
        $url = esc_url($item['url']);
        if (!$url) {
            continue;
        }
        $html .= '<a class="wpbbshop-social-link wpbbshop-social-' . esc_attr($network) . '" href="' . $url . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($item['label']) . '" title="' . esc_attr($item['label']) . '">' . wpbbshop_social_icon_svg($network) . '<span class="screen-reader-text">' . esc_html($item['label']) . '</span></a>';
    }
    return $html;
}

function wpbbshop_footer_html() {
    ob_start(); ?>
    <footer class="wpbbshop-footer">
      <div class="wpbbshop-container wpbbshop-footer-grid">
        <div class="wpbbshop-footer-brand"><?php echo wpbbshop_logo_html(); ?><p><?php echo esc_html(wpbbshop_get_theme_option('footer_description')); ?></p><div class="wpbbshop-socials" aria-label="Sociālie tīkli"><?php echo wpbbshop_footer_socials_html(); ?></div></div>
        <div><h4>Kontakti</h4><p><?php if (wpbbshop_get_theme_option('phone')) : ?>☎ <?php echo esc_html(wpbbshop_get_theme_option('phone')); ?><br><?php endif; ?><?php if (wpbbshop_get_theme_option('email')) : ?>✉ <a href="mailto:<?php echo esc_attr(wpbbshop_get_theme_option('email')); ?>"><?php echo esc_html(wpbbshop_get_theme_option('email')); ?></a><br><?php endif; ?><?php if (wpbbshop_get_theme_option('work_hours')) : ?>🕘 <?php echo esc_html(wpbbshop_get_theme_option('work_hours')); ?><br><?php endif; ?>📍 <?php echo esc_html(wpbbshop_get_theme_option('address')); ?><br><a href="<?php echo esc_url(wpbbshop_get_theme_option('social_facebook')); ?>" target="_blank" rel="noopener noreferrer">Facebook: WP BB Home & Garden</a></p></div>
        <div><h4>Informācija</h4><?php echo wpbbshop_nav_menu('footer', ['Par mums'=>home_url('/par-mums/'), 'Piegāde'=>home_url('/piegade/'), 'Apmaksa'=>home_url('/apmaksa/'), 'Atgriešana un maiņa'=>home_url('/atgriesana/')]); ?></div>
        <div><h4>Klientu serviss</h4><?php echo wpbbshop_nav_menu('service', ['Kontakti'=>home_url('/kontakti/'), 'Biežāk uzdotie jautājumi'=>home_url('/biezak-uzdotie-jautajumi/'), 'Garantija'=>home_url('/garantija/'), 'Sūdzību iesniegšana'=>home_url('/sudzibu-iesniegsana/')]); ?></div>
        <div><h4>Maksājumu metodes</h4><div class="wpbbshop-payments"><?php foreach (array_filter(array_map('trim', explode(',', wpbbshop_get_theme_option('footer_payment_methods')))) as $item) { echo '<span>' . esc_html($item) . '</span>'; } ?></div><h4>Piegādes partneri</h4><div class="wpbbshop-payments"><?php foreach (array_filter(array_map('trim', explode(',', wpbbshop_get_theme_option('footer_delivery_partners')))) as $item) { echo '<span>' . esc_html($item) . '</span>'; } ?></div><?php if (function_exists('wpbbshop_compare_badges_html') && wpbbshop_compare_badges_html()) : ?><h4>Cenu salīdzināšana</h4><div class="wpbbshop-compare-badges"><?php echo wpbbshop_compare_badges_html(); ?></div><?php endif; ?></div>
      </div>
      <div class="wpbbshop-container wpbbshop-footer-bottom">© <?php echo date('Y'); ?> WP BB Home & Garden — Visas tiesības aizsargātas.</div>
    </footer>
    <?php return ob_get_clean();
}
add_shortcode('wpbbshop_footer', 'wpbbshop_footer_html');


/**
 * Native WP BB HOME & GARDEN wishlist/compare functionality.
 * No wishlist/compare plugin is required: guest users use cookies and logged-in users use user meta.
 */
function wpbbshop_list_url($list) {
    return add_query_arg(array('wpbbshop_list' => sanitize_key($list)), home_url('/'));
}

function wpbbshop_get_list_items($list) {
    $list = sanitize_key($list);
    if (!in_array($list, array('wishlist', 'compare'), true)) {
        return array();
    }
    $key = 'wpbbshop_' . $list;
    $ids = array();
    if (is_user_logged_in()) {
        $stored = get_user_meta(get_current_user_id(), $key, true);
        if (is_array($stored)) {
            $ids = $stored;
        } elseif (is_string($stored) && $stored !== '') {
            $ids = explode(',', $stored);
        }
    } elseif (isset($_COOKIE[$key])) {
        $ids = explode(',', sanitize_text_field(wp_unslash($_COOKIE[$key])));
    }
    $ids = array_values(array_unique(array_filter(array_map('absint', $ids))));
    return $ids;
}

function wpbbshop_set_list_items($list, $ids) {
    $list = sanitize_key($list);
    if (!in_array($list, array('wishlist', 'compare'), true)) {
        return;
    }
    $key = 'wpbbshop_' . $list;
    $ids = array_values(array_unique(array_filter(array_map('absint', (array) $ids))));
    if ($list === 'compare') {
        $ids = array_slice($ids, -4); // keep compare focused and fast
    }
    if (is_user_logged_in()) {
        update_user_meta(get_current_user_id(), $key, $ids);
    }
    setcookie($key, implode(',', $ids), time() + MONTH_IN_SECONDS * 6, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
    $_COOKIE[$key] = implode(',', $ids);
}

function wpbbshop_is_in_list($list, $product_id) {
    return in_array(absint($product_id), wpbbshop_get_list_items($list), true);
}

function wpbbshop_toggle_list_item($list, $product_id) {
    $ids = wpbbshop_get_list_items($list);
    $product_id = absint($product_id);
    if (!$product_id) {
        return array('ids' => $ids, 'active' => false);
    }
    $active = false;
    if (in_array($product_id, $ids, true)) {
        $ids = array_values(array_diff($ids, array($product_id)));
    } else {
        $ids[] = $product_id;
        $active = true;
    }
    wpbbshop_set_list_items($list, $ids);
    return array('ids' => $ids, 'active' => $active);
}

function wpbbshop_native_action_button($list, $product_id, $label, $icon) {
    $product_id = absint($product_id);
    if (!$product_id || !wpbbshop_get_theme_option('enable_' . $list, '1')) {
        return '';
    }
    $active = wpbbshop_is_in_list($list, $product_id);
    $url = wp_nonce_url(add_query_arg(array('wpbbshop_action' => 'toggle_' . $list, 'product_id' => $product_id), home_url('/')), 'wpbbshop_toggle_' . $list . '_' . $product_id);
    return '<div class="wpbbshop-native wpbbshop-native-' . esc_attr($list) . ' ' . ($active ? 'is-active' : '') . '"><a href="' . esc_url($url) . '" class="wpbbshop-list-toggle" data-list="' . esc_attr($list) . '" data-product-id="' . esc_attr($product_id) . '" rel="nofollow"><span class="wpbbshop-list-icon">' . esc_html($icon) . '</span><span class="wpbbshop-list-label">' . esc_html($label) . '</span></a></div>';
}

function wpbbshop_yith_wishlist_button($product_id) {
    return wpbbshop_native_action_button('wishlist', $product_id, __('Pievienot vēlmēm', 'wpbbshop'), '♡');
}

function wpbbshop_yith_compare_button($product_id) {
    if (function_exists('wp_theme_woo_support_compare_button') && function_exists('wc_get_product')) {
        $product = wc_get_product(absint($product_id));
        if ($product instanceof WC_Product) {
            return wp_theme_woo_support_compare_button($product);
        }
    }
    return wpbbshop_native_action_button('compare', $product_id, __('Salīdzināt', 'wpbbshop'), '⇄');
}

function wpbbshop_product_quick_actions($product_id) {
    $wishlist = wpbbshop_yith_wishlist_button($product_id);
    $compare = wpbbshop_yith_compare_button($product_id);
    if (!$wishlist && !$compare) {
        return '';
    }
    return '<div class="wpbbshop-product-actions-top">' . $wishlist . $compare . '</div>';
}


function wpbbshop_sale_badge($product) {
    if (wpbbshop_get_theme_option('show_sale_badges', '1') !== '1' || !$product || !is_a($product, 'WC_Product') || !$product->is_on_sale()) {
        return '';
    }
    $regular = (float) $product->get_regular_price();
    $sale = (float) $product->get_sale_price();
    if ($regular > 0 && $sale > 0 && $sale < $regular) {
        $percent = round((($regular - $sale) / $regular) * 100);
        return '<span class="wpbbshop-sale-badge">-' . esc_html($percent) . '%</span>';
    }
    return '<span class="wpbbshop-sale-badge">Izpārdošana</span>';
}

function wpbbshop_product_card($product) {
    if (!$product || !is_a($product, 'WC_Product')) return '';
    $id = $product->get_id();
    $link = get_permalink($id);
    $cats = wc_get_product_category_list($id, ', ');
    ob_start(); ?>
    <article class="wpbbshop-product-card product">
      <?php echo wpbbshop_sale_badge($product); ?>
      <?php echo wpbbshop_product_quick_actions($id); ?>
      <a class="wpbbshop-product-image" href="<?php echo esc_url($link); ?>"><?php echo $product->get_image('woocommerce_thumbnail'); ?></a>
      <div class="wpbbshop-product-meta"><?php echo wp_kses_post($cats); ?></div>
      <h3 class="wpbbshop-product-title"><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
      <div class="wpbbshop-product-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
      <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-quantity="1" data-product_id="<?php echo esc_attr($id); ?>" class="button add_to_cart_button ajax_add_to_cart wpbbshop-add-cart <?php echo esc_attr($product->get_type()); ?>">Pievienot grozam</a>
    </article>
    <?php return ob_get_clean();
}

function wpbbshop_products_shortcode($atts) {
    if (!class_exists('WooCommerce')) return '<p>WooCommerce nav aktīvs.</p>';
    $atts = shortcode_atts(['type'=>'recent', 'limit'=>8, 'columns'=>4, 'title'=>'', 'class'=>''], $atts, 'wpbbshop_products');
    $args = ['post_type'=>'product', 'post_status'=>'publish', 'posts_per_page'=>(int)$atts['limit'], 'meta_query'=>WC()->query->get_meta_query(), 'tax_query'=>WC()->query->get_tax_query()];
    if ($atts['type']==='popular') { $args['meta_key']='total_sales'; $args['orderby']='meta_value_num'; $args['order']='DESC'; }
    elseif ($atts['type']==='sale') { $args['post__in']=wc_get_product_ids_on_sale(); }
    else { $args['orderby']='date'; $args['order']='DESC'; }
    $q = new WP_Query($args);
    ob_start(); ?>
    <section class="wpbbshop-products-section <?php echo esc_attr($atts['class']); ?>" style="--wpbbshop-cols:<?php echo (int)$atts['columns']; ?>">
      <?php if($atts['title']): ?><div class="wpbbshop-section-head"><h2><?php echo esc_html($atts['title']); ?></h2><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><span class="llg-section-link-text">Skatīt visus</span><span class="llg-section-link-icon">›</span></a></div><?php endif; ?>
      <div class="wpbbshop-product-grid">
      <?php while($q->have_posts()): $q->the_post(); global $product; echo wpbbshop_product_card($product); endwhile; wp_reset_postdata(); ?>
      </div>
    </section>
    <?php return ob_get_clean();
}
add_shortcode('wpbbshop_products', 'wpbbshop_products_shortcode');

function wpbbshop_categories_shortcode($atts) {
    if (!class_exists('WooCommerce')) return '';
    $terms = get_terms(['taxonomy'=>'product_cat', 'hide_empty'=>true, 'number'=>6, 'parent'=>0]);
    if (is_wp_error($terms) || empty($terms)) return '';
    $terms = wpbbshop_category_display_order($terms);
    $out = '<section class="wpbbshop-cat-grid">';
    foreach ($terms as $term) {
        $out .= '<a class="wpbbshop-cat-card" href="' . esc_url(get_term_link($term)) . '">' . wpbbshop_category_icon_html($term) . '<strong>' . esc_html($term->name) . '</strong><span>Skatīt vairāk ›</span></a>';
    }
    $out .= '</section>';
    return str_replace(array('<br>', '<br/>', '<br />'), '', $out);
}
add_shortcode('wpbbshop_categories', 'wpbbshop_categories_shortcode');

function wpbbshop_home_shortcode() {
    ob_start(); ?>
    <main class="wpbbshop-home">
      <section class="wpbbshop-hero wpbbshop-container">
        <div class="wpbbshop-hero-copy"><h1><?php echo esc_html(wpbbshop_get_theme_option('hero_title', 'Profesionāli instrumenti darbam un dārzam')); ?></h1><p><?php echo esc_html(wpbbshop_get_theme_option('hero_subtitle', 'Kvalitatīvi, uz kuru vari paļauties. Aprīkojums profesionāļiem un entuziastiem ar ātru piegādi visā Latvijā.')); ?></p><a class="wpbbshop-btn" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php echo esc_html(wpbbshop_get_theme_option('hero_button', 'Apskatīt piedāvājumu')); ?></a></div>
        <div class="wpbbshop-hero-art"><?php $hero_img = absint(wpbbshop_get_theme_option('hero_image_id', '')); if ($hero_img) { echo wp_get_attachment_image($hero_img, 'large'); } else { echo '<span>WP BB HOME & GARDEN</span>'; } ?></div>
      </section>
      <div class="wpbbshop-container"><?php echo do_shortcode('[wpbbshop_categories]'); ?></div>
      <div class="wpbbshop-container"><?php echo do_shortcode('[wpbbshop_products type="popular" title="Populārākās preces" limit="8" columns="4" class="popular"]'); ?></div>
      <div class="wpbbshop-container"><?php echo do_shortcode('[wpbbshop_products type="recent" title="Jaunākie produkti" limit="8" columns="4" class="newest"]'); ?></div>
      <section class="wpbbshop-container wpbbshop-brands"><h2>Uzticami zīmoli</h2><div><?php foreach (array_filter(array_map('trim', explode(',', wpbbshop_get_theme_option('brands')))) as $brand) { echo '<span>' . esc_html($brand) . '</span>'; } ?></div></section>
      <section class="wpbbshop-container wpbbshop-benefits"><?php foreach (array('benefit_1'=>'delivery','benefit_2'=>'sale','benefit_3'=>'shield') as $bkey=>$icon) { $parts = array_map('trim', explode('|', wpbbshop_get_theme_option($bkey))); echo '<div>' . wpbbshop_benefit_icon_svg($icon) . '<strong>' . esc_html(isset($parts[0]) ? $parts[0] : '') . '</strong><p>' . esc_html(isset($parts[1]) ? $parts[1] : '') . '</p></div>'; } ?></section>
    </main><?php return ob_get_clean();
}
add_shortcode('wpbbshop_home', 'wpbbshop_home_shortcode');


// Add YITH Wishlist / Compare buttons to regular WooCommerce loops as well.
add_action('woocommerce_before_shop_loop_item_title', function() {
    global $product;
    if ($product && is_a($product, 'WC_Product')) {
        echo wpbbshop_product_quick_actions($product->get_id());
    }
}, 6);

// WooCommerce loop polish: replace default loop add-to-cart text with full width Latvian button.
add_filter('woocommerce_product_add_to_cart_text', function(){ return __('Pievienot grozam', 'wpbbshop'); });
add_filter('woocommerce_product_single_add_to_cart_text', function(){ return __('Pievienot grozam', 'wpbbshop'); });


// Single product additions: clean YITH actions after add-to-cart and remove duplicate auto positions.
add_action('woocommerce_single_product_summary', function() {
    global $product;
    if ($product && is_a($product, 'WC_Product')) {
        echo '<div class="wpbbshop-single-actions">' . wpbbshop_yith_wishlist_button($product->get_id()) . wpbbshop_yith_compare_button($product->get_id()) . '</div>';
    }
}, 35);

add_filter('woocommerce_sale_flash', function($html, $post, $product) {
    return wpbbshop_sale_badge($product);
}, 10, 3);

// Cart fragments for header count.
add_filter('woocommerce_add_to_cart_fragments', function($fragments){
    if (function_exists('WC') && WC()->cart) {
        $fragments['.wpbbshop-cart-count'] = '<em class="wpbbshop-cart-count">'.WC()->cart->get_cart_contents_count().'</em>';
        $fragments['.wpbbshop-cart-total'] = '<strong class="wpbbshop-cart-total">'.WC()->cart->get_cart_total().'</strong>';
        ob_start();
        woocommerce_mini_cart();
        $fragments['.wpbbshop-mini-cart-content'] = '<div class="wpbbshop-mini-cart-content widget_shopping_cart_content">' . ob_get_clean() . '</div>';
    }
    return $fragments;
});

// AJAX product search endpoint: supports product title, SKU, and numeric product ID.
add_action('wp_ajax_wpbbshop_product_search', 'wpbbshop_ajax_product_search');
add_action('wp_ajax_nopriv_wpbbshop_product_search', 'wpbbshop_ajax_product_search');
function wpbbshop_ajax_product_search() {
    check_ajax_referer('wpbbshop_ajax', 'nonce');
    $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
    $out = array();
    if ($term !== '' && class_exists('WooCommerce')) {
        $ids = array();
        if (ctype_digit($term)) {
            $maybe_id = absint($term);
            if (get_post_type($maybe_id) === 'product') {
                $ids[] = $maybe_id;
            }
        }
        global $wpdb;
        $sku_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s LIMIT 12",
            '%' . $wpdb->esc_like($term) . '%'
        ));
        $ids = array_merge($ids, array_map('absint', (array) $sku_ids));
        $q = new WP_Query(array(
            'post_type' => 'product',
            's' => $term,
            'posts_per_page' => 8,
            'post_status' => 'publish',
            'fields' => 'ids',
        ));
        if ($q->posts) {
            $ids = array_merge($ids, array_map('absint', $q->posts));
        }
        $ids = array_slice(array_values(array_unique(array_filter($ids))), 0, 8);
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if (!$product || $product->get_status() !== 'publish') { continue; }
            $out[] = array(
                'id' => $id,
                'title' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => wp_strip_all_tags($product->get_price_html()),
                'url' => get_permalink($id),
                'image' => get_the_post_thumbnail_url($id, 'thumbnail'),
            );
        }
    }
    wp_send_json_success(array(
        'items' => $out,
        'allUrl' => add_query_arg(array('s' => rawurlencode($term), 'post_type' => 'product'), home_url('/')),
    ));
}

/* V7: WP BBuilder / Bootstrap-aware WooCommerce polish. */
add_filter('body_class', function($classes) {
    if (class_exists('WP_BBuilder') || defined('WPBB_VERSION')) {
        $classes[] = 'wpbbshop-wpbb-active';
    }
    if (function_exists('is_product_category') && is_product_category()) {
        $classes[] = 'wpbbshop-product-category-page';
    }
    return $classes;
}, 20);

add_action('wp_enqueue_scripts', function() {
    if (class_exists('WPBBuilder_Bootstrap')) {
        WPBBuilder_Bootstrap::enqueue_css();
        if (method_exists('WPBBuilder_Bootstrap', 'enqueue_js_if_needed')) {
            WPBBuilder_Bootstrap::enqueue_js_if_needed();
        }
    }
}, 5);

function wpbbshop_wc_archive_title() {
    if (function_exists('is_product_category') && is_product_category()) {
        return single_term_title('', false);
    }
    if (function_exists('is_product_tag') && is_product_tag()) {
        return single_term_title('', false);
    }
    if (is_search()) {
        return sprintf(__('Meklēšana: %s', 'wpbbshop'), get_search_query());
    }
    return __('Preces', 'wpbbshop');
}

function wpbbshop_wc_archive_description() {
    if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
        $desc = term_description();
        if ($desc) {
            return '<div class="wpbbshop-archive-desc">' . wp_kses_post($desc) . '</div>';
        }
    }
    return '<div class="wpbbshop-archive-desc">Profesionāli instrumenti, dārza tehnika un servisa aprīkojums ar ātru piegādi visā Latvijā.</div>';
}

function wpbbshop_wc_filter_url($args = array()) {
    $url = remove_query_arg(array('paged'));
    foreach ($args as $key => $value) {
        if ($value === null || $value === '') {
            $url = remove_query_arg($key, $url);
        } else {
            $url = add_query_arg($key, $value, $url);
        }
    }
    return $url;
}

function wpbbshop_wc_archive_sidebar() {
    if (!class_exists('WooCommerce')) {
        return '';
    }
    $shop_url = wc_get_page_permalink('shop');
    $terms = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'orderby'=>'name','order'=>'ASC'));
    if (!is_wp_error($terms)) { $terms = wpbbshop_category_display_order($terms); }
    $current_min = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? max(0, absint(wp_unslash($_GET['min_price']))) : 0;
    $current_max = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? max(0, absint(wp_unslash($_GET['max_price']))) : 0;
    $range_max = 1000;
    global $wpdb;
    $db_max = (int) ceil((float) $wpdb->get_var("SELECT MAX(CAST(meta_value AS DECIMAL(10,2))) FROM {$wpdb->postmeta} WHERE meta_key = '_price' AND meta_value != ''"));
    if ($db_max > 0) { $range_max = max(100, (int) (ceil($db_max / 50) * 50)); }
    if (!$current_max) { $current_max = $range_max; }
    ob_start(); ?>
    <aside class="wpbbshop-archive-sidebar card border-0 shadow-sm">
        <div class="wpbbshop-filter-block">
            <h3>Kategorijas</h3>
            <a class="wpbbshop-filter-link <?php echo is_shop() ? 'is-active' : ''; ?>" href="<?php echo esc_url($shop_url); ?>">Visas preces</a>
            <?php if (!is_wp_error($terms)) : foreach ($terms as $term) : ?>
                <a class="wpbbshop-filter-link <?php echo (is_product_category($term->slug) ? 'is-active' : ''); ?>" href="<?php echo esc_url(get_term_link($term)); ?>">
                    <span><?php echo esc_html($term->name); ?></span><em><?php echo (int) $term->count; ?></em>
                </a>
            <?php endforeach; endif; ?>
        </div>
        <form class="wpbbshop-filter-block wpbbshop-price-filter" method="get" action="">
            <h3>Filtrs</h3>
            <label>Meklēt pēc nosaukuma, SKU vai ID<input type="search" name="wpbbshop_filter_search" value="<?php echo isset($_GET['wpbbshop_filter_search']) ? esc_attr(wp_unslash($_GET['wpbbshop_filter_search'])) : ''; ?>" placeholder="Piem., M83254 vai 123"></label>
            <div class="wpbbshop-range-filter" data-max="<?php echo esc_attr($range_max); ?>">
                <div class="wpbbshop-range-head"><span>Cenu diapazons</span><strong><em class="wpbbshop-range-min-text"><?php echo esc_html($current_min); ?></em> € - <em class="wpbbshop-range-max-text"><?php echo esc_html($current_max); ?></em> €</strong></div>
                <div class="wpbbshop-range-sliders">
                    <input class="wpbbshop-range-min" type="range" min="0" max="<?php echo esc_attr($range_max); ?>" step="1" value="<?php echo esc_attr($current_min); ?>">
                    <input class="wpbbshop-range-max" type="range" min="0" max="<?php echo esc_attr($range_max); ?>" step="1" value="<?php echo esc_attr($current_max); ?>">
                </div>
                <div class="wpbbshop-range-fields">
                    <label>Min. cena<input class="wpbbshop-price-min-number" type="number" step="1" min="0" name="min_price" value="<?php echo esc_attr($current_min); ?>" placeholder="0"></label>
                    <label>Max. cena<input class="wpbbshop-price-max-number" type="number" step="1" min="0" name="max_price" value="<?php echo esc_attr($current_max); ?>" placeholder="<?php echo esc_attr($range_max); ?>"></label>
                </div>
            </div>
            <label>Kārtot
                <select name="orderby">
                    <option value="" <?php selected(isset($_GET['orderby']) ? wp_unslash($_GET['orderby']) : '', ''); ?>>Noklusējums</option>
                    <option value="date" <?php selected(isset($_GET['orderby']) ? wp_unslash($_GET['orderby']) : '', 'date'); ?>>Jaunākās</option>
                    <option value="price" <?php selected(isset($_GET['orderby']) ? wp_unslash($_GET['orderby']) : '', 'price'); ?>>Cena: zemākā</option>
                    <option value="price-desc" <?php selected(isset($_GET['orderby']) ? wp_unslash($_GET['orderby']) : '', 'price-desc'); ?>>Cena: augstākā</option>
                    <option value="popularity" <?php selected(isset($_GET['orderby']) ? wp_unslash($_GET['orderby']) : '', 'popularity'); ?>>Populārākās</option>
                </select>
            </label>
            <label class="wpbbshop-check"><input type="checkbox" name="onsale" value="1" <?php checked(isset($_GET['onsale'])); ?>> Tikai akcijas preces</label>
            <label class="wpbbshop-check"><input type="checkbox" name="instock" value="1" <?php checked(isset($_GET['instock'])); ?>> Tikai noliktavā</label>
            <?php foreach ($_GET as $key => $value) : if (in_array($key, array('wpbbshop_filter_search','min_price','max_price','onsale','instock','orderby','paged'), true) || is_array($value)) continue; ?>
                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(wp_unslash($value)); ?>">
            <?php endforeach; ?>
            <button type="submit">Pielietot filtru</button>
            <a class="wpbbshop-filter-reset" href="<?php echo esc_url(strtok($_SERVER['REQUEST_URI'], '?')); ?>">Notīrīt</a>
        </form>
        <div class="wpbbshop-filter-block">
            <h3>Ātrie filtri</h3>
            <div class="wpbbshop-filter-quick-grid">
                <a class="wpbbshop-filter-chip <?php echo isset($_GET['onsale']) ? 'is-active' : ''; ?>" href="<?php echo esc_url(wpbbshop_wc_filter_url(array('onsale'=>'1'))); ?>">Akcijas</a>
                <a class="wpbbshop-filter-chip <?php echo isset($_GET['instock']) ? 'is-active' : ''; ?>" href="<?php echo esc_url(wpbbshop_wc_filter_url(array('instock'=>'1'))); ?>">Noliktavā</a>
                <a class="wpbbshop-filter-chip" href="<?php echo esc_url(wpbbshop_wc_filter_url(array('orderby'=>'date'))); ?>">Jaunumi</a>
                <a class="wpbbshop-filter-chip" href="<?php echo esc_url(wpbbshop_wc_filter_url(array('orderby'=>'popularity'))); ?>">Top</a>
            </div>
        </div>
        <div class="wpbbshop-filter-block wpbbshop-trust-mini">
            <h3>WP BB HOME & GARDEN priekšrocības</h3>
            <p>🚚 Ātra piegāde Latvijā</p><p>🛡 14 dienu atgriešana</p><p>☎ +371 22413214</p>
        </div>
    </aside>
    <?php return ob_get_clean();
}

add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query() || !class_exists('WooCommerce')) {
        return;
    }
    if (!(function_exists('is_shop') && (is_shop() || is_product_category() || is_product_tag() || $query->is_post_type_archive('product')))) {
        return;
    }
    $meta_query = (array) $query->get('meta_query');
    if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
        $meta_query[] = array('key'=>'_price','value'=>floatval(wp_unslash($_GET['min_price'])),'compare'=>'>=','type'=>'NUMERIC');
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
        $meta_query[] = array('key'=>'_price','value'=>floatval(wp_unslash($_GET['max_price'])),'compare'=>'<=','type'=>'NUMERIC');
    }
    if (isset($_GET['onsale'])) {
        $sale_ids = wc_get_product_ids_on_sale();
        $query->set('post__in', !empty($sale_ids) ? $sale_ids : array(0));
    }
    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }
}, 20);

/* V8: Native WP BB HOME & GARDEN wishlist/compare, dynamic WooCommerce theme settings, and IWS-style admin controls. */
add_action('admin_menu', function() {
    add_theme_page(__('WP BB HOME & GARDEN Theme Settings', 'wpbbshop'), __('WP BB HOME & GARDEN Theme Settings', 'wpbbshop'), 'manage_options', 'wpbbshop-theme-settings', 'wpbbshop_theme_settings_page');
});

add_action('admin_init', function() {
    register_setting('wpbbshop_theme_settings', 'wpbbshop_theme_options', array('sanitize_callback' => 'wpbbshop_sanitize_theme_options'));
});

function wpbbshop_sanitize_theme_options($input) {
    $defaults = wpbbshop_default_options();
    $out = array();
    $checkbox_keys = array('enable_wishlist','enable_compare','archive_sidebar','dark_header','show_topbar','sticky_header','show_category_panel','show_sale_badges','disable_product_review_form','enable_kurpirkt_feed','enable_salidzini_feed','enable_ceno_feed','comparison_feed_include_outofstock','show_kurpirkt_badge','show_salidzini_badge','show_ceno_badge');
    foreach ($defaults as $key => $default) {
        $value = in_array($key, $checkbox_keys, true) ? (isset($input[$key]) ? $input[$key] : '') : (isset($input[$key]) ? $input[$key] : $default);
        if (in_array($key, array('logo_id','hero_image_id'), true)) {
            $out[$key] = (string) absint($value);
        } elseif ($key === 'logo_width') {
            $out[$key] = (string) max(120, min(520, absint($value)));
        } elseif ($key === 'logo_height') {
            $out[$key] = (string) max(70, min(180, absint($value)));
        } elseif (in_array($key, $checkbox_keys, true)) {
            $out[$key] = $value ? '1' : '0';
        } elseif ($key === 'comparison_feed_delivery_cost') {
            $decimal = function_exists('wc_format_decimal') ? wc_format_decimal($value) : preg_replace('/[^0-9.,]/', '', (string) $value);
            $decimal = str_replace(',', '.', (string) $decimal);
            $out[$key] = $decimal === '' ? '' : number_format(max(0, (float) $decimal), 2, '.', '');
        } elseif (in_array($key, array('comparison_feed_delivery_days','comparison_feed_shop_days'), true)) {
            $out[$key] = trim((string) $value) === '' ? '' : (string) min(30, absint($value));
        } elseif (in_array($key, array('products_per_row','single_related_limit','single_recommended_limit','single_related_columns'), true)) {
            $out[$key] = (string) max(2, min(6, absint($value)));
        } elseif (in_array($key, array('social_facebook','social_instagram','social_tiktok','social_youtube'), true)) {
            $out[$key] = esc_url_raw($value);
        } elseif ($key === 'accent_color') {
            $out[$key] = sanitize_hex_color($value) ? sanitize_hex_color($value) : $default;
        } else {
            $out[$key] = sanitize_textarea_field($value);
        }
    }
    return $out;
}

function wpbbshop_theme_settings_field($key, $label, $type = 'text', $help = '') {
    $options = wpbbshop_get_theme_options();
    $value = isset($options[$key]) ? $options[$key] : '';
    echo '<tr><th scope="row"><label for="wpbbshop_' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td>';
    if ($type === 'checkbox') {
        echo '<label><input type="checkbox" id="wpbbshop_' . esc_attr($key) . '" name="wpbbshop_theme_options[' . esc_attr($key) . ']" value="1" ' . checked($value, '1', false) . '> ' . esc_html__('Enabled', 'wpbbshop') . '</label>';
    } elseif ($type === 'media') {
        $preview = $value ? wp_get_attachment_image(absint($value), 'medium', false, array('style' => 'max-width:260px;max-height:120px;width:auto;height:auto;display:block;margin:0 0 10px;background:#0b0f17;padding:8px;border-radius:8px;object-fit:contain;')) : '';
        echo '<div class="wpbbshop-media-field">' . $preview . '<input type="hidden" id="wpbbshop_' . esc_attr($key) . '" name="wpbbshop_theme_options[' . esc_attr($key) . ']" value="' . esc_attr($value) . '"><button type="button" class="button wpbbshop-media-upload" data-target="wpbbshop_' . esc_attr($key) . '">' . esc_html__('Choose from Media Library', 'wpbbshop') . '</button> <button type="button" class="button wpbbshop-media-remove" data-target="wpbbshop_' . esc_attr($key) . '">' . esc_html__('Remove', 'wpbbshop') . '</button></div>';
    } elseif ($type === 'textarea') {
        echo '<textarea id="wpbbshop_' . esc_attr($key) . '" name="wpbbshop_theme_options[' . esc_attr($key) . ']" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
    } elseif ($type === 'color') {
        echo '<input type="color" id="wpbbshop_' . esc_attr($key) . '" name="wpbbshop_theme_options[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
    } elseif ($type === 'number') {
        $min = in_array($key, array('logo_width','logo_height'), true) ? (($key === 'logo_height') ? 70 : 120) : 2;
        $max = in_array($key, array('logo_width','logo_height'), true) ? (($key === 'logo_height') ? 180 : 520) : 6;
        echo '<input type="number" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" id="wpbbshop_' . esc_attr($key) . '" name="wpbbshop_theme_options[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="small-text">';
    } else {
        echo '<input type="text" id="wpbbshop_' . esc_attr($key) . '" name="wpbbshop_theme_options[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="regular-text">';
    }
    if ($help) {
        echo '<p class="description">' . esc_html($help) . '</p>';
    }
    echo '</td></tr>';
}

function wpbbshop_theme_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap wpbbshop-admin-wrap">
        <h1><?php esc_html_e('WP BB HOME & GARDEN Theme Settings', 'wpbbshop'); ?></h1>
        <p><?php esc_html_e('Dynamic WooCommerce theme settings used by the header, footer, product cards, archive filters, wishlist and compare.', 'wpbbshop'); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('wpbbshop_theme_settings'); ?>
            <h2><?php esc_html_e('Store identity', 'wpbbshop'); ?></h2>
            <table class="form-table" role="presentation">
                <?php wpbbshop_theme_settings_field('logo_id', 'Header/Footer logo', 'media', 'Choose one logo from the Media Library. This logo is used in both header and footer.'); ?>
                <?php wpbbshop_theme_settings_field('logo_width', 'Logo width px', 'number', 'Recommended: 240-320. Used in both header and footer.'); ?>
                <?php wpbbshop_theme_settings_field('logo_height', 'Logo crop height px', 'number', 'Recommended: 118. The logo can be visually larger but cropped vertically to keep the header clean.'); ?>
                <?php wpbbshop_theme_settings_field('phone', 'Phone'); ?>
                <?php wpbbshop_theme_settings_field('email', 'Email'); ?>
                <?php wpbbshop_theme_settings_field('address', 'Address'); ?>
                <?php wpbbshop_theme_settings_field('work_hours', 'Working hours'); ?>
                <?php wpbbshop_theme_settings_field('footer_description', 'Footer description', 'textarea'); ?>
                <?php wpbbshop_theme_settings_field('social_facebook', 'Facebook URL', 'text', 'Used by the clickable footer social icon.'); ?>
                <?php wpbbshop_theme_settings_field('social_instagram', 'Instagram URL', 'text', 'Used by the clickable footer social icon.'); ?>
                <?php wpbbshop_theme_settings_field('social_tiktok', 'TikTok URL', 'text', 'Used by the clickable footer social icon.'); ?>
                <?php wpbbshop_theme_settings_field('social_youtube', 'YouTube URL', 'text', 'Optional. Leave blank to hide the YouTube icon.'); ?>
                <?php wpbbshop_theme_settings_field('footer_payment_methods', 'Payment method badges', 'textarea', 'Comma separated.'); ?>
                <?php wpbbshop_theme_settings_field('footer_delivery_partners', 'Delivery partner badges', 'textarea', 'Comma separated.'); ?>
                <?php wpbbshop_theme_settings_field('locker_locations_unisend', 'Unisend locker locations', 'textarea', 'Optional: one exact locker per line. Customers can select these at checkout.'); ?>
                <?php wpbbshop_theme_settings_field('locker_locations_latvijas_pasts', 'Latvijas Pasts locker locations', 'textarea', 'Optional: one exact locker per line. Customers can select these at checkout.'); ?>
            </table>
            <h2><?php esc_html_e('Homepage and header', 'wpbbshop'); ?></h2>
            <table class="form-table" role="presentation">
                <?php wpbbshop_theme_settings_field('topbar_left', 'Top bar left messages', 'textarea', 'Separate items with |'); ?>
                <?php wpbbshop_theme_settings_field('topbar_right', 'Top bar right messages', 'textarea', 'Separate items with |'); ?>
                <?php wpbbshop_theme_settings_field('header_welcome', 'Header welcome text'); ?>
                <?php wpbbshop_theme_settings_field('category_panel_title', 'Category popup title'); ?>
                <?php wpbbshop_theme_settings_field('hero_image_id', 'Hero image', 'media', 'Optional hero image from Media Library.'); ?>
                <?php wpbbshop_theme_settings_field('hero_title', 'Hero title'); ?>
                <?php wpbbshop_theme_settings_field('hero_subtitle', 'Hero subtitle', 'textarea'); ?>
                <?php wpbbshop_theme_settings_field('hero_button', 'Hero button label'); ?>
                <?php wpbbshop_theme_settings_field('brands', 'Brands', 'textarea', 'Comma separated brand labels.'); ?>
                <?php wpbbshop_theme_settings_field('benefit_1', 'Benefit card 1', 'textarea', 'Format: Title|Subtitle'); ?>
                <?php wpbbshop_theme_settings_field('benefit_2', 'Benefit card 2', 'textarea', 'Format: Title|Subtitle'); ?>
                <?php wpbbshop_theme_settings_field('benefit_3', 'Benefit card 3', 'textarea', 'Format: Title|Subtitle'); ?>
            </table>
            <h2><?php esc_html_e('WooCommerce functionality', 'wpbbshop'); ?></h2>
            <table class="form-table" role="presentation">
                <?php wpbbshop_theme_settings_field('enable_wishlist', 'Native wishlist', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('enable_compare', 'Native compare', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('archive_sidebar', 'Shop/category filter sidebar', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('products_per_row', 'Products per row desktop', 'number'); ?>
                <?php wpbbshop_theme_settings_field('single_related_limit', 'Related products limit', 'number'); ?>
                <?php wpbbshop_theme_settings_field('single_recommended_limit', 'Recommended products limit', 'number'); ?>
                <?php wpbbshop_theme_settings_field('single_related_columns', 'Single product related/recommended columns', 'number'); ?>
                <?php wpbbshop_theme_settings_field('disable_product_review_form', 'Disable product review comment form', 'checkbox', 'Default enabled. Hides the product review/comment textarea and review form on single product pages.'); ?>
            </table>
            <h2><?php esc_html_e('Price comparison feeds', 'wpbbshop'); ?></h2>
            <?php if (isset($_GET['wpbbshop_feeds_regenerated'])) : ?><div class="notice notice-success inline"><p>XML plūsmas ir atjaunotas.</p></div><?php endif; ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row">XML feed URLs</th><td><p><strong>KurPirkt.lv:</strong> <code><?php echo esc_html(function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('kurpirkt') : home_url('/?wpbbshop_compare_feed=kurpirkt')); ?></code></p><p><strong>Salidzini.lv:</strong> <code><?php echo esc_html(function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('salidzini') : home_url('/?wpbbshop_compare_feed=salidzini')); ?></code></p><p><strong>Ceno.lv:</strong> <code><?php echo esc_html(function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('ceno') : home_url('/?wpbbshop_compare_feed=ceno')); ?></code></p><p class="description">Submit the matching public URL to each comparison service. Feeds are UTF-8 XML and refresh automatically.</p></td></tr>
                <?php wpbbshop_theme_settings_field('enable_kurpirkt_feed', 'Enable KurPirkt.lv XML feed', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('enable_salidzini_feed', 'Enable Salidzini.lv XML feed', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('enable_ceno_feed', 'Enable Ceno.lv XML feed', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('comparison_feed_include_outofstock', 'Include out-of-stock products', 'checkbox', 'Default is off. Usually only currently purchasable products should be exported.'); ?>
                <?php wpbbshop_theme_settings_field('comparison_feed_delivery_cost', 'Standard delivery cost (EUR)', 'text', 'Used for parcel-deliverable products. Current WP BB HOME & GARDEN standard parcel price is 3.90 EUR. Oversized/quote products omit this value.'); ?>
                <?php wpbbshop_theme_settings_field('comparison_feed_delivery_days', 'Maximum standard delivery days', 'text', 'Salidzini.lv requests the maximum guaranteed delivery days. Default: 3. Leave blank if you do not want to publish this value.'); ?>
                <?php wpbbshop_theme_settings_field('comparison_feed_shop_days', 'Warehouse pickup days', 'text', 'Optional Salidzini.lv value. 0 means available for pickup today. Leave blank unless this is guaranteed.'); ?>
                <?php wpbbshop_theme_settings_field('show_kurpirkt_badge', 'Show KurPirkt.lv cooperation badge', 'checkbox', 'Displayed in the site footer to satisfy the cooperation-link requirement.'); ?>
                <?php wpbbshop_theme_settings_field('show_salidzini_badge', 'Show Salidzini.lv cooperation badge', 'checkbox', 'Displayed in the site footer to satisfy the cooperation-link requirement.'); ?>
                <?php wpbbshop_theme_settings_field('show_ceno_badge', 'Show Ceno.lv cooperation badge', 'checkbox', 'Ceno.lv asks participating stores to place its banner on the site.'); ?>
                <tr><th scope="row">Refresh XML now</th><td><a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbbshop_regenerate_comparison_feeds'), 'wpbbshop_regenerate_comparison_feeds')); ?>">Regenerate feeds</a><p class="description">Feeds also refresh automatically every day and after product changes. Public requests use a short cache for fast responses.</p></td></tr>
            </table>
            <h2><?php esc_html_e('Style', 'wpbbshop'); ?></h2>
            <table class="form-table" role="presentation">
                <?php wpbbshop_theme_settings_field('accent_color', 'Accent color', 'color'); ?>
                <?php wpbbshop_theme_settings_field('dark_header', 'Dark IWS-style header', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('show_topbar', 'Show top service bar', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('sticky_header', 'Sticky header', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('show_category_panel', 'Show category mega popup', 'checkbox'); ?>
                <?php wpbbshop_theme_settings_field('show_sale_badges', 'Show discount badges', 'checkbox'); ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'appearance_page_wpbbshop-theme-settings') {
        return;
    }
    wp_enqueue_media();
    wp_add_inline_script('jquery-core', "jQuery(function($){var frame;$(document).on('click','.wpbbshop-media-upload',function(e){e.preventDefault();var target=$('#'+$(this).data('target'));frame=wp.media({title:'Choose WP BB HOME & GARDEN logo',button:{text:'Use this logo'},multiple:false});frame.on('select',function(){var a=frame.state().get('selection').first().toJSON();target.val(a.id);var img=a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url;target.closest('.wpbbshop-media-field').find('img').remove();target.before('<img src=\"'+img+'\" style=\"max-width:260px;max-height:120px;width:auto;height:auto;display:block;margin:0 0 10px;background:#0b0f17;padding:8px;border-radius:8px;object-fit:contain;\" />');});frame.open();});$(document).on('click','.wpbbshop-media-remove',function(e){e.preventDefault();var target=$('#'+$(this).data('target'));target.val('');target.closest('.wpbbshop-media-field').find('img').remove();});});");
});

add_action('template_redirect', function() {
    if (isset($_GET['wpbbshop_action'], $_GET['product_id']) && strpos(sanitize_key(wp_unslash($_GET['wpbbshop_action'])), 'toggle_') === 0) {
        $action = sanitize_key(wp_unslash($_GET['wpbbshop_action']));
        $list = str_replace('toggle_', '', $action);
        $product_id = absint(wp_unslash($_GET['product_id']));
        if (in_array($list, array('wishlist', 'compare'), true) && wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'wpbbshop_toggle_' . $list . '_' . $product_id)) {
            wpbbshop_toggle_list_item($list, $product_id);
        }
        wp_safe_redirect(wp_get_referer() ? wp_get_referer() : home_url('/'));
        exit;
    }
    if (isset($_GET['wpbbshop_list'])) {
        $list = sanitize_key(wp_unslash($_GET['wpbbshop_list']));
        if (in_array($list, array('wishlist', 'compare'), true)) {
            wpbbshop_render_list_page($list);
            exit;
        }
    }
});

add_action('wp_ajax_wpbbshop_toggle_list', 'wpbbshop_ajax_toggle_list');
add_action('wp_ajax_nopriv_wpbbshop_toggle_list', 'wpbbshop_ajax_toggle_list');
function wpbbshop_ajax_toggle_list() {
    check_ajax_referer('wpbbshop_ajax', 'nonce');
    $list = isset($_POST['list']) ? sanitize_key(wp_unslash($_POST['list'])) : '';
    $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
    if (!in_array($list, array('wishlist', 'compare'), true) || !$product_id) {
        wp_send_json_error(array('message' => __('Invalid request', 'wpbbshop')));
    }
    $result = wpbbshop_toggle_list_item($list, $product_id);
    wp_send_json_success(array(
        'list' => $list,
        'product_id' => $product_id,
        'active' => (bool) $result['active'],
        'count' => count($result['ids']),
        'wishlist_count' => count(wpbbshop_get_list_items('wishlist')),
        'compare_count' => count(wpbbshop_get_list_items('compare')),
        'label' => $result['active'] ? __('Pievienots', 'wpbbshop') : __('Pievienot', 'wpbbshop'),
    ));
}

function wpbbshop_get_products_from_ids($ids) {
    $products = array();
    foreach ((array) $ids as $id) {
        $product = wc_get_product(absint($id));
        if ($product && $product->get_status() === 'publish') {
            $products[] = $product;
        }
    }
    return $products;
}

function wpbbshop_render_list_page($list) {
    status_header(200);
    get_header();
    $ids = wpbbshop_get_list_items($list);
    $products = wpbbshop_get_products_from_ids($ids);
    $title = $list === 'compare' ? __('Salīdzināt preces', 'wpbbshop') : __('Vēlmju saraksts', 'wpbbshop');
    ?>
    <main class="wpbbshop-list-page">
        <div class="wpbbshop-container">
            <section class="wpbbshop-archive-hero card border-0 shadow-sm">
                <div><h1><?php echo esc_html($title); ?></h1><div class="wpbbshop-archive-desc"><?php echo esc_html__('Saglabātās preces no šīs ierīces vai konta.', 'wpbbshop'); ?></div></div>
                <a class="wpbbshop-btn" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('Turpināt iepirkties', 'wpbbshop'); ?></a>
            </section>
            <?php if (empty($products)) : ?>
                <div class="wpbbshop-empty-list card border-0 shadow-sm"><h2><?php esc_html_e('Saraksts ir tukšs', 'wpbbshop'); ?></h2><p><?php esc_html_e('Pievieno preces no veikala, lai tās parādītos šeit.', 'wpbbshop'); ?></p></div>
            <?php elseif ($list === 'compare') : ?>
                <div class="wpbbshop-compare-table-wrap card border-0 shadow-sm">
                    <table class="wpbbshop-compare-table">
                        <thead><tr><th><?php esc_html_e('Prece', 'wpbbshop'); ?></th><?php foreach ($products as $product) : ?><th><?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?><a href="<?php echo esc_url(get_permalink($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a></th><?php endforeach; ?></tr></thead>
                        <tbody>
                            <tr><th><?php esc_html_e('Cena', 'wpbbshop'); ?></th><?php foreach ($products as $product) : ?><td><?php echo wp_kses_post($product->get_price_html()); ?></td><?php endforeach; ?></tr>
                            <tr><th><?php esc_html_e('Pieejamība', 'wpbbshop'); ?></th><?php foreach ($products as $product) : ?><td><?php echo $product->is_in_stock() ? esc_html__('Ir noliktavā', 'wpbbshop') : esc_html__('Nav noliktavā', 'wpbbshop'); ?></td><?php endforeach; ?></tr>
                            <tr><th><?php esc_html_e('SKU', 'wpbbshop'); ?></th><?php foreach ($products as $product) : ?><td><?php echo esc_html($product->get_sku() ? $product->get_sku() : '-'); ?></td><?php endforeach; ?></tr>
                            <tr><th><?php esc_html_e('Darbība', 'wpbbshop'); ?></th><?php foreach ($products as $product) : ?><td><a class="button wpbbshop-add-cart" href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-product_id="<?php echo esc_attr($product->get_id()); ?>"><?php esc_html_e('Pievienot grozam', 'wpbbshop'); ?></a><?php echo wpbbshop_yith_compare_button($product->get_id()); ?></td><?php endforeach; ?></tr>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="wpbbshop-product-grid wpbbshop-list-grid" style="--wpbbshop-cols:<?php echo esc_attr(wpbbshop_get_theme_option('products_per_row', '4')); ?>">
                    <?php foreach ($products as $product) { echo wpbbshop_product_card($product); } ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php
    get_footer();
}



/* V16: Per-product related/recommended product controls and render helpers. */
function wpbbshop_parse_product_ids_csv($value) {
    $ids = array();
    foreach (preg_split('/[,\s]+/', (string) $value) as $part) {
        $id = absint($part);
        if ($id > 0) { $ids[] = $id; }
    }
    return array_values(array_unique($ids));
}

add_action('add_meta_boxes', function() {
    add_meta_box('wpbbshop_product_links', __('WP BB HOME & GARDEN related/recommended products', 'wpbbshop'), 'wpbbshop_product_links_metabox', 'product', 'side', 'default');
});

function wpbbshop_product_links_metabox($post) {
    wp_nonce_field('wpbbshop_product_links_save', 'wpbbshop_product_links_nonce');
    $related = get_post_meta($post->ID, '_wpbbshop_related_product_ids', true);
    $recommended = get_post_meta($post->ID, '_wpbbshop_recommended_product_ids', true);
    echo '<p><label for="wpbbshop_recommended_product_ids"><strong>' . esc_html__('Recommended product IDs', 'wpbbshop') . '</strong></label></p>';
    echo '<input type="text" id="wpbbshop_recommended_product_ids" name="wpbbshop_recommended_product_ids" value="' . esc_attr($recommended) . '" style="width:100%;" placeholder="123,456,789">';
    echo '<p class="description">' . esc_html__('Comma-separated product IDs. If empty, WooCommerce upsells are used.', 'wpbbshop') . '</p>';
    echo '<hr>';
    echo '<p><label for="wpbbshop_related_product_ids"><strong>' . esc_html__('Related product IDs', 'wpbbshop') . '</strong></label></p>';
    echo '<input type="text" id="wpbbshop_related_product_ids" name="wpbbshop_related_product_ids" value="' . esc_attr($related) . '" style="width:100%;" placeholder="123,456,789">';
    echo '<p class="description">' . esc_html__('Comma-separated product IDs. If empty, WooCommerce related products are used.', 'wpbbshop') . '</p>';
}

add_action('save_post_product', function($post_id) {
    if (!isset($_POST['wpbbshop_product_links_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpbbshop_product_links_nonce'])), 'wpbbshop_product_links_save')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }
    foreach (array('wpbbshop_related_product_ids' => '_wpbbshop_related_product_ids', 'wpbbshop_recommended_product_ids' => '_wpbbshop_recommended_product_ids') as $field => $meta) {
        $raw = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
        $ids = wpbbshop_parse_product_ids_csv($raw);
        update_post_meta($post_id, $meta, implode(',', $ids));
    }
});

function wpbbshop_render_single_product_section($product_id, $mode = 'related') {
    if (!class_exists('WooCommerce')) { return; }
    $product_id = absint($product_id);
    $is_recommended = ($mode === 'recommended');
    $limit_key = $is_recommended ? 'single_recommended_limit' : 'single_related_limit';
    $limit = max(1, absint(wpbbshop_get_theme_option($limit_key, '4')));
    $columns = max(2, min(6, absint(wpbbshop_get_theme_option('single_related_columns', '4'))));
    $meta_key = $is_recommended ? '_wpbbshop_recommended_product_ids' : '_wpbbshop_related_product_ids';
    $title = $is_recommended ? __('Ieteicamās preces', 'wpbbshop') : __('Saistītie produkti', 'wpbbshop');
    $ids = wpbbshop_parse_product_ids_csv(get_post_meta($product_id, $meta_key, true));
    if (!$ids && $is_recommended) {
        $p = wc_get_product($product_id);
        $ids = $p ? array_map('absint', $p->get_upsell_ids()) : array();
    }
    if (!$ids && !$is_recommended && function_exists('wc_get_related_products')) {
        $ids = wc_get_related_products($product_id, $limit, array($product_id));
    }
    $ids = array_slice(array_values(array_filter(array_unique(array_map('absint', $ids)))), 0, $limit);
    if (!$ids) { return; }
    $q = new WP_Query(array('post_type' => 'product', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => $limit));
    if (!$q->have_posts()) { wp_reset_postdata(); return; }
    echo '<section class="wpbbshop-single-products-section wpbbshop-single-' . esc_attr($mode) . ' card border-0 shadow-sm mx-auto mt-4">';
    echo '<div class="wpbbshop-section-head"><h2>' . esc_html($title) . '</h2></div>';
    echo '<div class="wpbbshop-product-grid wpbbshop-single-products-grid" style="--wpbbshop-cols:' . esc_attr($columns) . '">';
    while ($q->have_posts()) { $q->the_post(); global $product; if ($product) { echo function_exists('wpbbshop_green_product_card') ? wpbbshop_green_product_card($product) : wpbbshop_product_card($product); } }
    echo '</div></section>';
    wp_reset_postdata();
}

add_action('wp_head', function() {
    $accent = wpbbshop_get_theme_option('accent_color', '#2f8b49');
    echo '<style id="wpbbshop-dynamic-settings">:root{--wpbbshop-red:' . esc_html($accent) . ';}.wpbbshop-product-grid{--wpbbshop-cols:' . esc_html(wpbbshop_get_theme_option('products_per_row', '4')) . ';}</style>';
}, 30);

add_filter('woocommerce_output_related_products_args', function($args) {
    $limit = absint(wpbbshop_get_theme_option('single_related_limit', '4'));
    $args['posts_per_page'] = $limit ? $limit : 4;
    $args['columns'] = min(4, max(2, $limit));
    return $args;
});
add_action('wp_enqueue_scripts', function() {
    if (function_exists('is_product') && is_product()) {
        if (wp_script_is('zoom', 'registered')) { wp_enqueue_script('zoom'); }
        if (wp_script_is('flexslider', 'registered')) { wp_enqueue_script('flexslider'); }
        if (wp_script_is('photoswipe', 'registered')) { wp_enqueue_script('photoswipe'); }
        if (wp_script_is('photoswipe-ui-default', 'registered')) { wp_enqueue_script('photoswipe-ui-default'); }
        if (wp_style_is('photoswipe', 'registered')) { wp_enqueue_style('photoswipe'); }
        if (wp_style_is('photoswipe-default-skin', 'registered')) { wp_enqueue_style('photoswipe-default-skin'); }
    }
}, 25);


/**
 * V18: force clean WooCommerce core pages and legacy shortcodes.
 * Keeps checkout URL as /checkout/ instead of /checkout-2/ and gives order tracking a themed page.
 */
function wpbbshop_ensure_wc_core_pages_v18() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    $pages = array(
        'cart' => array('option' => 'woocommerce_cart_page_id', 'slug' => 'cart', 'title' => 'Grozs', 'content' => '[woocommerce_cart]'),
        'checkout' => array('option' => 'woocommerce_checkout_page_id', 'slug' => 'checkout', 'title' => 'Noformēt pasūtījumu', 'content' => '[woocommerce_checkout]'),
        'myaccount' => array('option' => 'woocommerce_myaccount_page_id', 'slug' => 'my-account', 'title' => 'Mans konts', 'content' => '[woocommerce_my_account]'),
    );
    foreach ($pages as $data) {
        $page = get_page_by_path($data['slug']);
        $assigned_id = absint(get_option($data['option']));
        if (!$page && $data['slug'] === 'checkout' && $assigned_id) {
            $assigned = get_post($assigned_id);
            if ($assigned && $assigned->post_type === 'page') {
                wp_update_post(array('ID' => $assigned_id, 'post_name' => 'checkout', 'post_title' => $data['title'], 'post_content' => $data['content']));
                $page = get_post($assigned_id);
            }
        }
        if (!$page) {
            $new_id = wp_insert_post(array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $data['title'],
                'post_name' => $data['slug'],
                'post_content' => $data['content'],
            ));
            if ($new_id && !is_wp_error($new_id)) {
                update_option($data['option'], $new_id);
            }
        } else {
            update_option($data['option'], $page->ID);
            if (strpos((string)$page->post_content, 'woocommerce_') === false || strpos((string)$page->post_content, '<!-- wp:woocommerce/') !== false) {
                wp_update_post(array('ID' => $page->ID, 'post_content' => $data['content']));
            }
        }
    }
    if (!get_page_by_path('track-your-order')) {
        wp_insert_post(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => 'Sekot pasūtījumam',
            'post_name' => 'track-your-order',
            'post_content' => '[woocommerce_order_tracking]',
        ));
    }
}
add_action('after_switch_theme', 'wpbbshop_ensure_wc_core_pages_v18');
add_action('admin_init', 'wpbbshop_ensure_wc_core_pages_v18');

add_action('template_redirect', function() {
    if (is_page('checkout-2')) {
        wp_safe_redirect(home_url('/checkout/'), 301);
        exit;
    }
});

// Prefer the clean /checkout/ URL even if WooCommerce still has an old assigned page cached.
add_filter('woocommerce_get_checkout_url', function($url) {
    $page = get_page_by_path('checkout');
    return $page ? get_permalink($page->ID) : home_url('/checkout/');
}, 20);

// Replace WooCommerce Checkout/Cart blocks in page content with legacy shortcodes so templates stay usable.
add_filter('render_block', function($block_content, $block) {
    if (empty($block['blockName'])) {
        return $block_content;
    }
    if ($block['blockName'] === 'woocommerce/checkout') {
        return do_shortcode('[woocommerce_checkout]');
    }
    if ($block['blockName'] === 'woocommerce/cart') {
        return do_shortcode('[woocommerce_cart]');
    }
    return $block_content;
}, 10, 2);


/* V19: force dedicated WooCommerce page templates even when core page assignment/content was changed. */
add_filter('template_include', function($template) {
    if (function_exists('is_cart') && is_cart()) {
        $custom = get_stylesheet_directory() . '/page-cart.php';
        if (file_exists($custom)) { return $custom; }
    }
    if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url()) {
        $custom = get_stylesheet_directory() . '/page-checkout.php';
        if (file_exists($custom)) { return $custom; }
    }
    if (is_page('track-your-order')) {
        $custom = get_stylesheet_directory() . '/page-track-your-order.php';
        if (file_exists($custom)) { return $custom; }
    }
    return $template;
}, 99);

add_filter('the_content', function($content) {
    if (strpos($content, 'wpbbshop-cat-grid') !== false || strpos($content, '[wpbbshop_categories') !== false) {
        $content = preg_replace('/<br\s*\/?>\s*(?=<a class="wpbbshop-cat-card")/i', '', $content);
        $content = preg_replace('/(<\/a>)\s*<br\s*\/?>/i', '$1', $content);
    }
    return $content;
}, 20);

/* V27: BBuilder Bootstrap-aware shop filters/load-more and archive helpers. */
add_action('wp', function() {
    if (class_exists('WPBBuilder_Bootstrap')) {
        WPBBuilder_Bootstrap::needs(array('buttons','forms','card','pagination','list-group','offcanvas','modal','helpers','images'));
    }
});

function wpbbshop_v27_archive_term_context() {
    $data = array('taxonomy'=>'', 'term'=>'', 'search'=>'');
    if (is_product_category()) {
        $obj = get_queried_object();
        if ($obj && !is_wp_error($obj)) { $data['taxonomy'] = 'product_cat'; $data['term'] = $obj->slug; }
    } elseif (is_product_tag()) {
        $obj = get_queried_object();
        if ($obj && !is_wp_error($obj)) { $data['taxonomy'] = 'product_tag'; $data['term'] = $obj->slug; }
    }
    if (is_search()) { $data['search'] = get_search_query(); }
    return $data;
}

function wpbbshop_v27_archive_load_more_button($query = null) {
    global $wp_query;
    if (!$query) { $query = $wp_query; }
    if (!$query || empty($query->max_num_pages) || (int)$query->max_num_pages < 2) { return ''; }
    $ctx = wpbbshop_v27_archive_term_context();
    $min = isset($_GET['min_price']) ? wc_clean(wp_unslash($_GET['min_price'])) : '';
    $max = isset($_GET['max_price']) ? wc_clean(wp_unslash($_GET['max_price'])) : '';
    $onsale = isset($_GET['onsale']) ? '1' : '';
    $orderby = isset($_GET['orderby']) ? wc_clean(wp_unslash($_GET['orderby'])) : '';
    return '<div class="wpbbshop-load-more-wrap"><button type="button" class="wpbbshop-load-more btn btn-danger" data-page="1" data-max="' . esc_attr((int)$query->max_num_pages) . '" data-taxonomy="' . esc_attr($ctx['taxonomy']) . '" data-term="' . esc_attr($ctx['term']) . '" data-search="' . esc_attr($ctx['search']) . '" data-min-price="' . esc_attr($min) . '" data-max-price="' . esc_attr($max) . '" data-onsale="' . esc_attr($onsale) . '" data-orderby="' . esc_attr($orderby) . '"><span class="wpbbshop-load-more-text">Ielādēt vairāk preces</span></button></div>';
}

function wpbbshop_v27_build_product_query_args($page, $request) {
    $ppp = absint(wpbbshop_get_theme_option('archive_per_page', '12'));
    if (!$ppp) { $ppp = 12; }
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $ppp,
        'paged' => max(1, absint($page)),
        'meta_query' => class_exists('WC') ? WC()->query->get_meta_query() : array(),
        'tax_query' => class_exists('WC') ? WC()->query->get_tax_query() : array(),
    );
    if (!empty($request['taxonomy']) && !empty($request['term'])) {
        $tax = sanitize_key($request['taxonomy']);
        if (in_array($tax, array('product_cat','product_tag'), true)) {
            $args['tax_query'][] = array('taxonomy'=>$tax, 'field'=>'slug', 'terms'=>sanitize_title($request['term']));
        }
    }
    if (!empty($request['search'])) {
        $args['s'] = sanitize_text_field($request['search']);
    }
    if (isset($request['min_price']) && $request['min_price'] !== '') {
        $args['meta_query'][] = array('key'=>'_price','value'=>floatval($request['min_price']),'compare'=>'>=','type'=>'NUMERIC');
    }
    if (isset($request['max_price']) && $request['max_price'] !== '') {
        $args['meta_query'][] = array('key'=>'_price','value'=>floatval($request['max_price']),'compare'=>'<=','type'=>'NUMERIC');
    }
    if (!empty($request['onsale'])) {
        $sale_ids = wc_get_product_ids_on_sale();
        $args['post__in'] = !empty($sale_ids) ? $sale_ids : array(0);
    }
    $orderby = isset($request['orderby']) ? sanitize_key($request['orderby']) : '';
    if ($orderby === 'price') { $args['meta_key'] = '_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'ASC'; }
    elseif ($orderby === 'price-desc') { $args['meta_key'] = '_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; }
    elseif ($orderby === 'popularity') { $args['meta_key'] = 'total_sales'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'DESC'; }
    elseif ($orderby === 'date') { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
    return $args;
}

add_action('wp_ajax_wpbbshop_load_more_products', 'wpbbshop_v27_load_more_products');
add_action('wp_ajax_nopriv_wpbbshop_load_more_products', 'wpbbshop_v27_load_more_products');
function wpbbshop_v27_load_more_products() {
    check_ajax_referer('wpbbshop_ajax', 'nonce');
    if (!class_exists('WooCommerce')) { wp_send_json_error(array('message'=>'WooCommerce nav aktīvs.')); }
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $args = wpbbshop_v27_build_product_query_args($page, wp_unslash($_POST));
    $q = new WP_Query($args);
    ob_start();
    if ($q->have_posts()) {
        while ($q->have_posts()) { $q->the_post(); global $product; echo '<div class="col-6 col-md-4 col-xl-3 wpbbshop-bs-product-col">' . wpbbshop_product_card($product) . '</div>'; }
        wp_reset_postdata();
    }
    $html = ob_get_clean();
    wp_send_json_success(array('html'=>$html, 'page'=>$page, 'max'=>(int)$q->max_num_pages));
}


/* V28: force IWS/BBuilder archive filters and apply real query filters on shop/category pages. */
add_filter('template_include', function($template) {
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
        $custom = get_stylesheet_directory() . '/woocommerce/archive-product.php';
        if (file_exists($custom)) { return $custom; }
    }
    return $template;
}, 120);

add_action('pre_get_posts', function($q) {
    if (is_admin() || !$q->is_main_query() || !class_exists('WooCommerce')) { return; }
    if (!(function_exists('is_shop') && (is_shop() || is_product_taxonomy()))) { return; }
    $meta_query = (array) $q->get('meta_query');
    $tax_query = (array) $q->get('tax_query');
    if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
        $meta_query[] = array('key'=>'_price','value'=>floatval(wp_unslash($_GET['min_price'])),'compare'=>'>=','type'=>'NUMERIC');
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
        $meta_query[] = array('key'=>'_price','value'=>floatval(wp_unslash($_GET['max_price'])),'compare'=>'<=','type'=>'NUMERIC');
    }
    if (isset($_GET['instock'])) {
        $meta_query[] = array('key'=>'_stock_status','value'=>'instock','compare'=>'=');
    }
    if (isset($_GET['onsale'])) {
        $sale_ids = wc_get_product_ids_on_sale();
        $q->set('post__in', !empty($sale_ids) ? $sale_ids : array(0));
    }
    if (isset($_GET['wpbbshop_filter_search']) && trim(wp_unslash($_GET['wpbbshop_filter_search'])) !== '') {
        $term = sanitize_text_field(wp_unslash($_GET['wpbbshop_filter_search']));
        $q->set('s', $term);
        add_filter('posts_search', 'wpbbshop_v28_sku_id_archive_search', 10, 2);
    }
    $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';
    if ($orderby === 'price') { $q->set('meta_key','_price'); $q->set('orderby','meta_value_num'); $q->set('order','ASC'); }
    elseif ($orderby === 'price-desc') { $q->set('meta_key','_price'); $q->set('orderby','meta_value_num'); $q->set('order','DESC'); }
    elseif ($orderby === 'popularity') { $q->set('meta_key','total_sales'); $q->set('orderby','meta_value_num'); $q->set('order','DESC'); }
    elseif ($orderby === 'date') { $q->set('orderby','date'); $q->set('order','DESC'); }
    if (!empty($meta_query)) { $q->set('meta_query', $meta_query); }
    if (!empty($tax_query)) { $q->set('tax_query', $tax_query); }
}, 20);

function wpbbshop_v28_sku_id_archive_search($search, $wp_query) {
    global $wpdb;
    if (is_admin() || !$wp_query->is_main_query()) { return $search; }
    if (!(function_exists('is_shop') && (is_shop() || is_product_taxonomy()))) { return $search; }
    if (!isset($_GET['wpbbshop_filter_search'])) { return $search; }
    $term = sanitize_text_field(wp_unslash($_GET['wpbbshop_filter_search']));
    if ($term === '') { return $search; }
    $like = '%' . $wpdb->esc_like($term) . '%';
    $ids = array();
    if (ctype_digit($term)) { $ids[] = absint($term); }
    $sku_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s", $like));
    $ids = array_unique(array_filter(array_merge($ids, array_map('absint', $sku_ids))));
    if (empty($ids)) { return $search; }
    $id_list = implode(',', array_map('absint', $ids));
    $search = preg_replace('/\)\s*$/', " OR {$wpdb->posts}.ID IN ({$id_list}))", $search);
    return $search;
}

add_action('wp_footer', function(){ if (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) { remove_filter('posts_search', 'wpbbshop_v28_sku_id_archive_search', 10); } }, 1);


/* V38: real IWS-style gallery renderer for WooCommerce block gallery and PHP single template. */
if (!function_exists('wpbbshop_v38_gallery_items')) {
    function wpbbshop_v38_gallery_items($product) {
        $items = array();
        if (!$product || !is_a($product, 'WC_Product')) { return $items; }
        $demo_map = array(
            'LL-DEMO-D20'=>'drill.png','LL-DEMO-C50'=>'compressor.webp','LL-DEMO-W200'=>'welder.png','LL-DEMO-G3500'=>'generator.webp',
            'LL-DEMO-J3T'=>'jack.png','LL-DEMO-A1500'=>'impact-wrench.webp','LL-DEMO-S108'=>'tool-set.webp','LL-DEMO-B26'=>'blower.png',
            'LL-DEMO-BC52'=>'brushcutter.webp','LL-DEMO-H10'=>'trimmer-head.png','LL-DEMO-HALU'=>'aluminum-head.png','LL-DEMO-L24'=>'trimmer-line.png',
            'LL-DEMO-CS85'=>'chain-sharpener.png','LL-DEMO-OP12'=>'oil-pump.png'
        );
        $sku = (string) $product->get_sku();
        // Demo products use the bundled image set, including any realistic demo gallery files.
        if (get_post_meta($product->get_id(), '_wpbbshop_demo_product', true) === '1' && function_exists('wpbbshop_green_demo_item_by_sku')) {
            $item = wpbbshop_green_demo_item_by_sku($sku);
            if ($item && !empty($item['image'])) {
                $files = array_merge(array($item['image']), (array) (isset($item['gallery']) ? $item['gallery'] : array()));
                foreach ($files as $file) {
                    $file = basename($file);
                    $path = get_stylesheet_directory() . '/assets/demo-products/' . $file;
                    if (!file_exists($path)) { continue; }
                    $url = get_stylesheet_directory_uri() . '/assets/demo-products/' . rawurlencode($file);
                    $items[] = array('thumb'=>$url, 'main'=>$url, 'full'=>$url, 'alt'=>$product->get_name());
                }
                if ($items) { return $items; }
            }
        }
        $ids = array();
        $main_id = $product->get_image_id();
        if ($main_id) { $ids[] = $main_id; }
        foreach ((array) $product->get_gallery_image_ids() as $gid) {
            $gid = absint($gid);
            if ($gid && !in_array($gid, $ids, true)) { $ids[] = $gid; }
        }
        if (empty($ids)) {
            if (!empty($demo_map[$sku])) {
                $path = get_stylesheet_directory() . '/assets/demo-products/' . $demo_map[$sku];
                if (file_exists($path)) {
                    $url = get_stylesheet_directory_uri() . '/assets/demo-products/' . $demo_map[$sku];
                    return array(array('thumb'=>$url, 'main'=>$url, 'full'=>$url, 'alt'=>$product->get_name()));
                }
            }
            $ph = wc_placeholder_img_src('woocommerce_single');
            return array(array('thumb'=>$ph, 'main'=>$ph, 'full'=>$ph, 'alt'=>$product->get_name()));
        }
        foreach ($ids as $id) {
            $thumb = wp_get_attachment_image_url($id, 'woocommerce_gallery_thumbnail');
            $medium = wp_get_attachment_image_url($id, 'medium_large');
            $single = wp_get_attachment_image_url($id, 'woocommerce_single');
            $large = wp_get_attachment_image_url($id, 'large');
            $full = wp_get_attachment_image_url($id, 'full');
            $main = $single ? $single : ($large ? $large : ($medium ? $medium : ($full ? $full : $thumb)));
            if (!$main) { continue; }
            $modal = $full ? $full : ($large ? $large : $main);
            if (!$thumb) { $thumb = $main; }
            $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
            if (!$alt) { $alt = $product->get_name(); }
            $items[] = array('thumb'=>$thumb, 'main'=>$main, 'full'=>$modal, 'alt'=>$alt);
        }
        return $items;
    }
}

if (!function_exists('wpbbshop_render_v38_product_gallery')) {
    function wpbbshop_render_v38_product_gallery($product = null) {
        if (!$product || !is_a($product, 'WC_Product')) {
            $product = isset($GLOBALS['product']) && is_a($GLOBALS['product'], 'WC_Product') ? $GLOBALS['product'] : wc_get_product(get_the_ID());
        }
        if (!$product || !is_a($product, 'WC_Product')) { return ''; }
        $items = wpbbshop_v38_gallery_items($product);
        if (empty($items)) { return ''; }
        $uid = 'm38gallery-' . absint($product->get_id()) . '-' . wp_rand(100, 999);
        $first = $items[0];
        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="m38gallery" data-active="0">
            <button type="button" class="m38gallery-zoom" aria-label="Atvērt lielo attēlu"><span>⌕</span></button>
            <div class="m38gallery-stage">
                <img class="m38gallery-img" src="<?php echo esc_url($first['main']); ?>" data-full="<?php echo esc_url($first['full']); ?>" alt="<?php echo esc_attr($first['alt']); ?>" loading="eager" decoding="async" fetchpriority="high">
            </div>
            <div class="m38gallery-thumbs" role="list" aria-label="Produkta attēli">
                <?php foreach ($items as $i => $item) : ?>
                    <button type="button" class="m38gallery-thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($i); ?>" data-main="<?php echo esc_url($item['main']); ?>" data-full="<?php echo esc_url($item['full']); ?>" data-thumb="<?php echo esc_url($item['thumb']); ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                        <img src="<?php echo esc_url($item['thumb']); ?>" alt="<?php echo esc_attr($item['alt']); ?>" loading="lazy" decoding="async">
                    </button>
                <?php endforeach; ?>
            </div>
            <script type="application/json" class="m38gallery-data"><?php echo wp_json_encode($items); ?></script>
        </div>
        <?php
        return ob_get_clean();
    }
}

add_filter('render_block', function($block_content, $block) {
    if (!is_product() || empty($block['blockName']) || $block['blockName'] !== 'woocommerce/product-image-gallery') {
        return $block_content;
    }
    $product = wc_get_product(get_the_ID());
    $gallery = wpbbshop_render_v38_product_gallery($product);
    return $gallery ? '<div class="wp-block-woocommerce-product-image-gallery wpbbshop-v38-block-gallery">' . $gallery . '</div>' : $block_content;
}, 50, 2);

add_filter('woocommerce_product_tabs', function($tabs) {
    if (wpbbshop_get_theme_option('disable_product_review_form', '1') === '1' && isset($tabs['reviews'])) {
        unset($tabs['reviews']);
    }
    return $tabs;
}, 40);

add_filter('comments_open', function($open, $post_id) {
    if (get_post_type($post_id) === 'product' && wpbbshop_get_theme_option('disable_product_review_form', '1') === '1') {
        return false;
    }
    return $open;
}, 40, 2);

require_once get_stylesheet_directory() . '/inc/wpbbshop-commerce-fixes.php';
require_once get_stylesheet_directory() . '/inc/wpbbshop-price-comparison-feeds.php';
require_once get_stylesheet_directory() . '/inc/wpbbshop-green-v2.php';


/* 2.2.0: bundled screenshot, favicon fallback, admin cleanup and frontend language trimming */
function wpbbshop_favicon_fallback() {
    $base = trailingslashit(get_stylesheet_directory_uri()) . 'assets/img/';
    echo '<link rel="icon" type="image/svg+xml" href="' . esc_url($base . 'favicon.svg?v=410') . '">';
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url($base . 'favicon-32.png?v=410') . '">';
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($base . 'apple-touch-icon.png?v=410') . '">';
    echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url($base . 'site-icon-192.png?v=410') . '">';
}
add_action('after_setup_theme', function(){
    remove_action('wp_head', 'wp_site_icon', 99);
    remove_action('admin_head', 'wp_site_icon', 99);
    remove_action('login_head', 'wp_site_icon', 99);
}, 999);
add_action('wp_head', 'wpbbshop_favicon_fallback', 1000);
add_action('admin_head', 'wpbbshop_favicon_fallback', 1000);
add_action('login_head', 'wpbbshop_favicon_fallback', 1000);

function wpbbshop_admin_cleanup_assets() {
    $css = '.theme .theme-name#wp-bbtheme-child-woo-tech-shop-wpbbshop{font-weight:700;}';
    echo '<style id="wpbbshop-admin-cleanup">' . $css . '</style>';
    echo '<script id="wpbbshop-admin-cleanup-js">document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".notice, .update-nag, .updated, .error").forEach(function(n){var t=(n.textContent||"").toLowerCase();if(t.indexOf("business starter refreshed")>-1||t.indexOf("legacy / bespoke child theme")>-1||t.indexOf("project dependencies")>-1){n.style.display="none";}});});</script>';
}
add_action('admin_head', 'wpbbshop_admin_cleanup_assets', 99);


/* 2.3.3: keep WooCommerce catalogue visible while LV/EN content is being prepared. */
add_action('pre_get_posts', function($query){
    if (is_admin() || !$query->is_main_query()) { return; }
    if ((function_exists('is_shop') && is_shop()) || is_post_type_archive('product') || is_tax('product_cat') || is_tax('product_tag')) {
        $query->set('lang', '');
    }
}, 30);


/* 2.3.4 WP BB HOME & GARDEN SAFE STARTER SETUP */
function wpbbshop_project_mode_234($mode) { return 'woocommerce'; }
add_filter('wp_theme_project_mode', 'wpbbshop_project_mode_234', 9999);
function wpbbshop_woo_profile_234($profile) { return 'store'; }
add_filter('wp_theme_woo_support_default_profile', 'wpbbshop_woo_profile_234', 9999);
function wpbbshop_demo_profile_234($profile) {
    if (!is_array($profile)) { $profile = array(); }
    return array_merge($profile, array(
        'id'=>'wpbbshop',
        'name'=>'Home & Garden Demo',
        // The child importer below owns the shop demo. This prevents the generic Woo support importer from creating media.
        'commerce'=>false,
        'eyebrow'=>'Home & Garden • Large catalogue demo',
        'hero_title'=>'Everything for home, garden and DIY',
        'hero_text'=>'A fast green WooCommerce megastore demo designed for large catalogues.',
    ));
}
add_filter('wp_theme_demo_profile', 'wpbbshop_demo_profile_234', PHP_INT_MAX);
add_filter('wp_theme_demo_import_message', function($message, $profile=array()) {
    return 'Home & Garden Starter Setup synchronized: English primary, Latvian secondary, WooCommerce pages and lightweight demo catalogue.';
}, PHP_INT_MAX, 2);

function wpbbshop_disable_parent_starter_automation_234() {
    remove_action('wp_theme_after_demo_import', 'wp_theme_setup_demo_polylang_languages', 5);
    remove_action('admin_init', 'wp_theme_maybe_setup_demo_polylang_languages', 20);
    remove_action('admin_init', 'wp_theme_maybe_bind_demo_polylang_menu_locations', 25);
    // Prevent the generic Woo demo importer from copying/rasterising demo media if another component fires this hook.
    remove_action('wp_theme_before_demo_import', 'wpbb_child_woo_prepare_sector_demo', 10);
}
add_action('after_setup_theme', 'wpbbshop_disable_parent_starter_automation_234', PHP_INT_MAX);

function wpbbshop_sync_classic_menu_234($name, $items, $location) {
    $menu_obj = wp_get_nav_menu_object($name);
    $menu_id = $menu_obj ? (int)$menu_obj->term_id : wp_create_nav_menu($name);
    if (!$menu_id || is_wp_error($menu_id)) { return 0; }
    $existing = wp_get_nav_menu_items($menu_id);
    if ($existing) { foreach ($existing as $item) { wp_delete_post($item->ID, true); } }
    foreach ($items as $label=>$url) {
        wp_update_nav_menu_item($menu_id, 0, array(
            'menu-item-title'=>sanitize_text_field($label),'menu-item-url'=>esc_url_raw($url),
            'menu-item-status'=>'publish','menu-item-type'=>'custom',
        ));
    }
    $locations = get_theme_mod('nav_menu_locations', array());
    if (!is_array($locations)) { $locations=array(); }
    $locations[$location]=$menu_id;
    set_theme_mod('nav_menu_locations',$locations);
    return $menu_id;
}

function wpbbshop_sync_menus_234() {
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $primary = array(
        'Home'=>home_url('/'),'Offers'=>add_query_arg('onsale','1',$shop),'New arrivals'=>add_query_arg('orderby','date',$shop),
        'Garden'=>home_url('/product-category/garden-outdoor/'),'Tools'=>home_url('/product-category/tools-workshop/'),
        'Building'=>home_url('/product-category/building-renovation/'),'Home'=>home_url('/product-category/home-living/'),
        'Contact'=>wpbbshop_page_url('contact'),
    );
    $footer = array('About us'=>wpbbshop_page_url('about-us'),'Delivery & payment'=>wpbbshop_page_url('delivery-payment'),'Returns & warranty'=>wpbbshop_page_url('returns-warranty'),'Contact'=>wpbbshop_page_url('contact'));
    $primary_id = wpbbshop_sync_classic_menu_234('Home & Garden EN — Primary', $primary, 'primary');
    $footer_id = wpbbshop_sync_classic_menu_234('Home & Garden EN — Footer', $footer, 'footer');
    $service_id = wpbbshop_sync_classic_menu_234('Home & Garden EN — Customer service', array('Contact'=>wpbbshop_page_url('contact'),'My account'=>(function_exists('wc_get_page_permalink')?wc_get_page_permalink('myaccount'):home_url('/my-account/')),'Cart'=>(function_exists('wc_get_cart_url')?wc_get_cart_url():home_url('/cart/'))), 'service');
    return array('primary'=>$primary_id,'footer'=>$footer_id,'service'=>$service_id);
}

/**
 * Replace the parent Starter Setup importer before the parent functions.php is loaded.
 * No generic Business demo, no generic Woo demo images, and no Imagick work.
 */
if (!function_exists('wp_theme_import_demo_homepage')) {
    function wp_theme_import_demo_homepage() {
        if (!current_user_can('edit_theme_options')) { return new WP_Error('forbidden','Nav tiesību sinhronizēt Starter Setup.'); }
        if (function_exists('wpbbshop_repair_all_300')) { return wpbbshop_repair_all_300(); }
        return new WP_Error('repair_unavailable','WP BB Home & Garden repair layer nav ielādēts.');
    }
}

require_once get_stylesheet_directory() . '/inc/wpbbshop-polylang-yoast.php';
require_once get_stylesheet_directory() . '/inc/wpbbshop-repair-300.php';


/* 3.0.3 storefront runtime safeguards. */
add_filter('woocommerce_coming_soon_exclude', '__return_true', PHP_INT_MAX);
add_filter('pre_option_woocommerce_coming_soon', function(){ return 'no'; }, PHP_INT_MAX);
add_filter('pre_option_woocommerce_store_pages_only', function(){ return 'no'; }, PHP_INT_MAX);

add_filter('get_terms', function($terms, $taxonomies, $args, $term_query){
    if (is_admin() || is_wp_error($terms) || !is_array($terms)) { return $terms; }
    if (!in_array('product_cat', (array) $taxonomies, true)) { return $terms; }
    $blocked = array('accessories','apparel','bundles','home-living','office','tech');
    return array_values(array_filter($terms, function($term) use ($blocked){
        return !is_object($term) || empty($term->slug) || !in_array($term->slug, $blocked, true);
    }));
}, 999, 4);

add_filter('woocommerce_product_query_meta_query', function($meta_query){
    if (!is_array($meta_query)) { $meta_query = array(); }
    $meta_query[] = array('key'=>'_sku','value'=>'DEMO-BUSINESS-','compare'=>'NOT LIKE');
    return $meta_query;
}, 999);

function wpbbshop_runtime_repair_303(){
    update_option('woocommerce_coming_soon', 'no', false);
    update_option('woocommerce_store_pages_only', 'no', false);
    $opts = get_option('wpbbshop_theme_options', array());
    if (!is_array($opts)) { $opts = array(); }
    $opts['logo_id'] = '';
    $opts['logo_width'] = '280';
    $opts['logo_height'] = '110';
    update_option('wpbbshop_theme_options', $opts, false);
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    delete_transient('wc_products_onsale');
}
add_action('after_switch_theme', 'wpbbshop_runtime_repair_303', 60);


/* 3.0.5 final polish layer. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v305-final.php';
require_once get_stylesheet_directory() . '/inc/wpbbshop-v307-layout-fix.php';

/* 3.0.8 critical header/mobile gap hardening. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v308-header-mobile.php';

/* 3.0.9 isolated mobile header + English storefront root. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v309-language-mobile.php';

/* 3.0.10 search UI: clean submit icon + single-row AJAX results. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v310-search.php';

/* 3.0.11 final mobile search/alignment hardening. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v311-mobile-search-alignment.php';

/* 3.0.12 WooCommerce cart / checkout / account presentation helpers. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v312-woocommerce-ui.php';

/* 3.0.13 site-wide content/product/archive/feed/quote hardening. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v313-sitewide.php';

/* 3.0.14 centered info/account pages, product gallery/lightbox and direct XML feed hardening. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v314-site-polish.php';

/* 3.0.15 unified site width, compact header/hero alignment and static XML feeds. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v315-wide-header-feeds.php';

/* 3.0.16 product quantity/gallery, mini-cart, shipping width and homepage search polish. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v316-product-cart-polish.php';

/* 3.0.17 My Account password controls + consistent contained header search. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v317-account-search-fix.php';

/* 3.0.21 isolated responsive header rebuild. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v321-header-rebuild.php';

/* 3.0.23 one header geometry everywhere; cache purge only, no homepage-specific CSS. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v323-header-unify.php';

/* 3.0.24 isolated desktop header namespace; prevents front-page legacy CSS conflicts. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v324-home-header-isolation.php';

/* Legacy quote-label helper; homepage-only topbar CSS removed in 3.0.28. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v326-topbar-grid.php';

/* 3.0.27 final responsive/mobile header geometry. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v327-responsive-header.php';

/* 3.0.28 one site-wide grid for topbar, header, nav and homepage sections. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v328-grid-clean.php';

/* 3.0.29 single responsive header source + checkout shipping/newsletter polish. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v329-stability.php';

/* 3.0.30 mobile header parity + contained category drawer. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v330-mobile-header-fix.php';

/* 3.0.33 monthly homepage picks + final mobile search/cache hardening. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v333-monthly-picks.php';


/* 3.1.0 Home & Garden megastore layer. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-garden-megastore.php';


/* 4.0.0 unified Home & Garden platform integration. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v400-platform.php';

/* 4.0.4 stability: one shell, non-blocking cache/network maintenance, admin guard. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v404-stability.php';

/* 4.0.5 language/product/cookie/TOP/pattern experience fixes. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v405-experience.php';


/* 4.0.6 bilingual category + language switch repair. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v406-language-sync.php';


/* 4.0.7 native Polylang + Woo permalink repair. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v407-polylang-permalinks.php';

/* 4.0.8 market-aware LV/UK shopping and comparison feeds. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v408-market-feeds.php';

/* 4.0.9 scalable catalogue: language-safe category browsing, AJAX filters and load more. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v409-catalog-ajax.php';

/* 4.0.10 final visual system: hero, compact departments, shop density, forced bundled branding. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v410-visual.php';

/* 4.0.11 shop/category readability, filter alignment and variations quick-filter. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v411-shop-polish.php';

/* 4.0.12 WP Theme Woo Support smart-filter/compare integration + header visibility. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v412-woo-support-integration.php';

/* 4.0.13 final UI polish: green smart-filter controls + compact homepage hero/departments. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v413-ui-polish.php';

/* 4.0.14 final department grid + smart-filter range/compare control polish. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v414-final-ui.php';

/* 4.0.15 UK commerce, language consistency and full-width information pages. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v415-uk-commerce-language.php';

/* 4.0.16 final cart/checkout width, performance and four-column home grid repair. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v416-final-fixes.php';

/* 4.0.17 regression hardening: restore compact 4-column home departments and full-width Woo shipping. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v417-regression-hardening.php';

/* 4.0.18 restore exact Git-master R5 home grid + structural full-width Woo shipping. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v418-git-master-restore.php';

/* 4.0.19 final shipping width, LV Polylang pair repair, readable departments and LV hero. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v419-final-repairs.php';

/* 4.0.20 final Woo shipping width + Polylang storefront/menu language repair. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v420-final.php';

/* 4.0.21 checkout shipping, balanced load-more batches and Contact Google map. */
require_once get_stylesheet_directory() . '/inc/wpbbshop-v421-checkout-loadmore-map.php';
