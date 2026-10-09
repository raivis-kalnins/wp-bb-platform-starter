<?php
/**
 * WP BB Home & Garden v4.0.25
 * Local showcase imagery, English mini-cart polish and stale quote cleanup.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V425_VERSION')) {
    define('WPBBSHOP_V425_VERSION', '4.0.25');
}

function wpbbshop_v425_lang() {
    if (function_exists('pll_current_language')) {
        $lang = wpbbshop_v433_current_language();
        if (is_string($lang) && $lang !== '') {
            return strtolower($lang) === 'lv' ? 'lv' : 'en';
        }
    }
    return stripos((string) get_locale(), 'lv') === 0 ? 'lv' : 'en';
}

function wpbbshop_v425_t($en, $lv) {
    return wpbbshop_v425_lang() === 'lv' ? $lv : $en;
}

function wpbbshop_v425_asset_url($relative) {
    $relative = ltrim((string) $relative, '/');
    $path = get_stylesheet_directory() . '/' . $relative;
    return is_readable($path) ? get_stylesheet_directory_uri() . '/' . $relative : '';
}

function wpbbshop_v425_product_assets() {
    $base = 'assets/showcase-v425/product-';
    return array(
        'panel-radiator-22'       => $base . 'panel-radiator-22.jpg',
        'underfloor-mat'          => $base . 'underfloor-mat.jpg',
        'cultivator-1050'         => $base . 'cultivator-1050.jpg',
        'mini-tractor-15hp'       => $base . 'mini-tractor-15hp.jpg',
        'towel-radiator'          => $base . 'towel-radiator.jpg',
        'boiler-24kw'             => $base . 'boiler-24kw.jpg',
        'pump-25-60'              => $base . 'pump-25-60.jpg',
        'riding-mower-1050a'      => $base . 'riding-mower-1050a.jpg',
        'air-source-heat-pump'    => $base . 'air-source-heat-pump.jpg',
        'underfloor-manifold'     => $base . 'underfloor-manifold.jpg',
        'radiator-valve-pack'     => $base . 'radiator-valve-pack.jpg',
        'compact-tractor-package' => $base . 'compact-tractor-package.jpg',
    );
}

function wpbbshop_v425_blog_assets() {
    $base = 'assets/showcase-v425/blog-';
    return array(
        'heating-plan'         => $base . 'heating-plan.jpg',
        'radiator-size'        => $base . 'radiator-size.jpg',
        'cultivator-guide'     => $base . 'cultivator-guide.jpg',
        'variation-stock'        => $base . 'variant-stock.jpg',
        'underfloor-heating'   => $base . 'underfloor-heating.jpg',
        'garden-maintenance'   => $base . 'garden-maintenance.jpg',
        'heat-pump-guide'      => $base . 'heat-pump-guide.jpg',
        'tractor-attachments'  => $base . 'tractor-attachments.jpg',
    );
}

function wpbbshop_v425_demo_product_asset($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    if (function_exists('wpbbshop_v426_demo_product_asset')) {
        $v426 = wpbbshop_v426_demo_product_asset($product);
        if ($v426) { return $v426; }
    }
    $id = $product->get_id();
    $parent_id = $product instanceof WC_Product_Variation ? $product->get_parent_id() : $id;
    $key = (string) get_post_meta($parent_id, '_wpbbshop_v422_demo_key', true);
    $map = wpbbshop_v425_product_assets();
    if ($key !== '' && isset($map[$key])) {
        return wpbbshop_v425_asset_url($map[$key]);
    }

    $sku = (string) $product->get_sku();
    $is_demo = (string) get_post_meta($parent_id, '_wpbbshop_demo_product', true) === '1' || stripos($sku, 'HG-DEMO') === 0;
    if (!$is_demo) { return ''; }

    $hay = strtolower(remove_accents($product->get_name() . ' ' . $sku));
    $pick = '';
    if (preg_match('/tractor|rider|ride.on|mower|pļāv/', $hay)) { $pick = 'riding-mower-1050a'; }
    elseif (preg_match('/cultiv|tiller|fr[eē]z|augsn/', $hay)) { $pick = 'cultivator-1050'; }
    elseif (preg_match('/radiator|heater|sild|apkures panel/', $hay)) { $pick = 'panel-radiator-22'; }
    elseif (preg_match('/underfloor|floor heat|gr[iī]das apk/', $hay)) { $pick = 'underfloor-mat'; }
    elseif (preg_match('/pump|s[uū]kn/', $hay)) { $pick = 'pump-25-60'; }
    elseif (preg_match('/boiler|katl/', $hay)) { $pick = 'boiler-24kw'; }
    elseif (preg_match('/valve|v[aā]rst/', $hay)) { $pick = 'radiator-valve-pack'; }
    else {
        $choices = array('cultivator-1050','mini-tractor-15hp','riding-mower-1050a','panel-radiator-22','underfloor-mat','pump-25-60');
        $pick = $choices[abs(crc32($hay)) % count($choices)];
    }
    return isset($map[$pick]) ? wpbbshop_v425_asset_url($map[$pick]) : '';
}

/* Last image owner for demo catalogue cards. */
add_filter('woocommerce_product_get_image', function($html, $product, $size, $attr, $placeholder) {
    $url = wpbbshop_v425_demo_product_asset($product);
    if (!$url) { return $html; }
    $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wpbb-v425-demo-image';
    if (is_array($attr) && !empty($attr['class'])) { $classes .= ' ' . sanitize_html_class($attr['class']); }
    $loading = is_array($attr) && !empty($attr['loading']) ? (string) $attr['loading'] : 'lazy';
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr($product->get_name()) . '" class="' . esc_attr($classes) . '" loading="' . esc_attr($loading) . '" decoding="async">';
}, PHP_INT_MAX, 5);

function wpbbshop_v425_single_demo_image($product) {
    $url = wpbbshop_v425_demo_product_asset($product);
    if (!$url) { return ''; }
    return '<figure class="llg-single-main-image wpbb-v423-single-demo wpbb-v425-single-demo"><img src="' . esc_url($url) . '" alt="' . esc_attr($product->get_name()) . '" fetchpriority="high" decoding="async"></figure>';
}

/** Import a bundled showcase JPG once so demo posts have real Featured Images in wp-admin. */
function wpbbshop_v425_attachment_for_asset($relative, $title = '') {
    $relative = ltrim((string) $relative, '/');
    $source = get_stylesheet_directory() . '/' . $relative;
    if (!is_readable($source)) { return 0; }

    $existing = get_posts(array(
        'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1,
        'fields' => 'ids', 'meta_key' => '_wpbbshop_v425_asset', 'meta_value' => $relative,
        'suppress_filters' => true,
    ));
    if ($existing) { return (int) $existing[0]; }

    if (!function_exists('wp_upload_bits')) { require_once ABSPATH . 'wp-admin/includes/file.php'; }
    $bits = wp_upload_bits('wpbb-' . basename($relative), null, file_get_contents($source));
    if (!empty($bits['error']) || empty($bits['file'])) { return 0; }
    $type = wp_check_filetype(basename($bits['file']), null);
    $attachment_id = wp_insert_attachment(array(
        'post_mime_type' => $type['type'] ?: 'image/jpeg',
        'post_title' => sanitize_text_field($title ?: pathinfo($relative, PATHINFO_FILENAME)),
        'post_content' => '', 'post_status' => 'inherit',
    ), $bits['file']);
    if (is_wp_error($attachment_id) || !$attachment_id) { return 0; }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachment_id, $bits['file']);
    if ($metadata) { wp_update_attachment_metadata($attachment_id, $metadata); }
    update_post_meta($attachment_id, '_wpbbshop_v425_asset', $relative);
    return (int) $attachment_id;
}

function wpbbshop_v425_refresh_demo_media() {
    if (!current_user_can('manage_options')) { return; }
    if ((string) get_option('wpbbshop_v425_media_version', '') === WPBBSHOP_V425_VERSION) { return; }

    $blog_map = wpbbshop_v425_blog_assets();
    $gallery_map = array(
        'heating-plan'        => array('blog-heating-plan.jpg','product-air-source-heat-pump.jpg','product-panel-radiator-22.jpg'),
        'radiator-size'       => array('blog-radiator-size.jpg','product-panel-radiator-22.jpg','product-radiator-valve-pack.jpg'),
        'cultivator-guide'    => array('blog-cultivator-guide.jpg','product-cultivator-1050.jpg','product-mini-tractor-15hp.jpg'),
        'variation-stock'       => array('blog-variant-stock.jpg','product-radiator-valve-pack.jpg','product-underfloor-manifold.jpg'),
        'underfloor-heating'  => array('blog-underfloor-heating.jpg','product-underfloor-mat.jpg','product-underfloor-manifold.jpg'),
        'garden-maintenance'  => array('blog-garden-maintenance.jpg','product-riding-mower-1050a.jpg','product-cultivator-1050.jpg'),
        'heat-pump-guide'     => array('blog-heat-pump-guide.jpg','product-air-source-heat-pump.jpg','product-pump-25-60.jpg'),
        'tractor-attachments' => array('blog-tractor-attachments.jpg','product-mini-tractor-15hp.jpg','product-compact-tractor-package.jpg'),
    );

    $posts = get_posts(array(
        'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => 100,
        'meta_key' => '_wpbbshop_v422_guide', 'meta_value' => '1', 'suppress_filters' => true,
    ));
    foreach ($posts as $post) {
        $key = (string) get_post_meta($post->ID, '_wpbbshop_v422_guide_key', true);
        if (!$key || empty($blog_map[$key])) { continue; }
        $url = wpbbshop_v425_asset_url($blog_map[$key]);
        if ($url) { update_post_meta($post->ID, '_wpbbshop_v423_featured_image_url', esc_url_raw($url)); }
        $feature_id = wpbbshop_v425_attachment_for_asset($blog_map[$key], get_the_title($post));
        if ($feature_id) { set_post_thumbnail($post->ID, $feature_id); }

        $gallery_ids = array();
        foreach ((array) ($gallery_map[$key] ?? array()) as $file) {
            $relative = strpos($file, 'blog-') === 0 ? 'assets/showcase-v425/' . $file : 'assets/showcase-v425/' . $file;
            $aid = wpbbshop_v425_attachment_for_asset($relative, get_the_title($post));
            if ($aid) { $gallery_ids[] = $aid; }
        }
        if ($gallery_ids) { update_post_meta($post->ID, '_wpbbshop_v424_gallery_ids', array_values(array_unique($gallery_ids))); }
    }

    $product_map = wpbbshop_v425_product_assets();
    $products = get_posts(array(
        'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => 250,
        'meta_key' => '_wpbbshop_v422_demo_key', 'suppress_filters' => true,
    ));
    foreach ($products as $post) {
        $key = (string) get_post_meta($post->ID, '_wpbbshop_v422_demo_key', true);
        if (!$key || empty($product_map[$key])) { continue; }
        $url = wpbbshop_v425_asset_url($product_map[$key]);
        if ($url) {
            update_post_meta($post->ID, '_wpbbshop_v423_demo_image_url', esc_url_raw($url));
            update_post_meta($post->ID, '_wpbbshop_demo_image_url', esc_url_raw($url));
        }
        $aid = wpbbshop_v425_attachment_for_asset($product_map[$key], get_the_title($post));
        if ($aid) { set_post_thumbnail($post->ID, $aid); }
    }

    update_option('wpbbshop_v425_media_version', WPBBSHOP_V425_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v425_refresh_demo_media', 180);
add_action('after_switch_theme', 'wpbbshop_v425_refresh_demo_media', 180);

/** Remove deleted/unpublished products from the native quote basket. */
function wpbbshop_v425_prune_quote_items() {
    if (!function_exists('wp_theme_woo_quote_items') || !function_exists('wp_theme_woo_quote_set_items') || !function_exists('WC') || !WC()->session) { return; }
    $items = wp_theme_woo_quote_items();
    if (!$items || !is_array($items)) { return; }
    $clean = array();
    foreach ($items as $key => $item) {
        $product_id = absint($item['product_id'] ?? 0);
        $variation_id = absint($item['variation_id'] ?? 0);
        $lookup_id = $variation_id ?: $product_id;
        if (!$lookup_id || get_post_status($lookup_id) !== 'publish') { continue; }
        if ($variation_id && (!$product_id || get_post_status($product_id) !== 'publish')) { continue; }
        $product = wc_get_product($lookup_id);
        if (!$product) { continue; }
        $qty = max(0, absint($item['quantity'] ?? 1));
        if ($qty < 1) { continue; }
        $item['quantity'] = $qty;
        $clean[$key] = $item;
    }
    if (count($clean) !== count($items)) { wp_theme_woo_quote_set_items($clean); }
}
add_action('wp_loaded', 'wpbbshop_v425_prune_quote_items', 35);
add_action('template_redirect', 'wpbbshop_v425_prune_quote_items', 1);

function wpbbshop_v425_clear_quote() {
    check_ajax_referer('wpbbshop_v425_quote_clear', 'nonce');
    if (function_exists('wp_theme_woo_quote_set_items')) { wp_theme_woo_quote_set_items(array()); }
    $html = function_exists('wpbbshop_v424_quote_drawer_body') ? wpbbshop_v424_quote_drawer_body() : '';
    wp_send_json_success(array('count' => 0, 'html' => $html));
}
add_action('wp_ajax_wpbbshop_v425_clear_quote', 'wpbbshop_v425_clear_quote');
add_action('wp_ajax_nopriv_wpbbshop_v425_clear_quote', 'wpbbshop_v425_clear_quote');

/** Add an explicit clear action to the full quote page without modifying the support plugin. */
add_filter('do_shortcode_tag', function($output, $tag) {
    if ($tag !== 'wp_theme_woo_quote_basket' || !function_exists('wp_theme_woo_quote_count') || wp_theme_woo_quote_count() < 1) { return $output; }
    if (strpos($output, 'data-wpbb-quote-clear') !== false) { return $output; }
    $button = '<div class="wpbb-v425-quote-tools"><button type="button" class="wpbb-v425-quote-clear" data-wpbb-quote-clear>' . esc_html(wpbbshop_v425_t('Clear quote list', 'Notīrīt pieprasījumu')) . '</button></div>';
    $needle = '<div class="wp-theme-quote-layout">';
    return strpos($output, $needle) !== false ? str_replace($needle, $button . $needle, $output) : $button . $output;
}, 120, 2);

add_action('wp_enqueue_scripts', function() {
    $css = get_stylesheet_directory() . '/assets/css/v425-content-cart-quote.css';
    $js  = get_stylesheet_directory() . '/assets/js/v425-content-cart-quote.js';
    if (is_readable($css)) {
        wp_enqueue_style('wpbbshop-v425-content-cart-quote', get_stylesheet_directory_uri() . '/assets/css/v425-content-cart-quote.css', array('wpbbshop-v424-blog-quote-gallery'), (string) filemtime($css));
    }
    if (is_readable($js)) {
        wp_enqueue_script('wpbbshop-v425-content-cart-quote', get_stylesheet_directory_uri() . '/assets/js/v425-content-cart-quote.js', array('jquery'), (string) filemtime($js), true);
        wp_localize_script('wpbbshop-v425-content-cart-quote', 'WPBBShopV425', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'clearNonce' => wp_create_nonce('wpbbshop_v425_quote_clear'),
            'clearLabel' => wpbbshop_v425_t('Clear quote', 'Notīrīt pieprasījumu'),
            'clearingLabel' => wpbbshop_v425_t('Clearing…', 'Notīra…'),
        ));
    }
}, PHP_INT_MAX);

function wpbbshop_v425_guide_image_url($post_id) {
    $post_id = absint($post_id);
    if (!$post_id) { return ''; }
    if (function_exists('wpbbshop_v429_guide_image_url')) {
        $v429 = wpbbshop_v429_guide_image_url($post_id);
        if ($v429) { return $v429; }
    }
    if (function_exists('wpbbshop_v428_guide_image_url')) {
        $v428 = wpbbshop_v428_guide_image_url($post_id);
        if ($v428) { return $v428; }
    }
    if (function_exists('wpbbshop_v426_guide_image_url')) {
        $v426 = wpbbshop_v426_guide_image_url($post_id);
        if ($v426) { return $v426; }
    }
    if (has_post_thumbnail($post_id)) {
        $thumb = get_the_post_thumbnail_url($post_id, 'large');
        if ($thumb) { return $thumb; }
    }
    $key = (string) get_post_meta($post_id, '_wpbbshop_v422_guide_key', true);
    $map = wpbbshop_v425_blog_assets();
    if ($key && isset($map[$key])) { return wpbbshop_v425_asset_url($map[$key]); }
    $stored = esc_url_raw((string) get_post_meta($post_id, '_wpbbshop_v423_featured_image_url', true));
    return $stored;
}

function wpbbshop_v425_post_gallery_items($post_id) {
    $post_id = absint($post_id);
    if (!$post_id) { return array(); }
    if (function_exists('wpbbshop_v426_post_gallery_items')) {
        $v426 = wpbbshop_v426_post_gallery_items($post_id);
        if ($v426) { return $v426; }
    }
    $items = array();
    $ids = get_post_meta($post_id, '_wpbbshop_v424_gallery_ids', true);
    if (is_string($ids)) { $ids = array_filter(array_map('absint', preg_split('/[\s,]+/', $ids))); }
    if (is_array($ids) && $ids) {
        foreach ($ids as $id) {
            $id = absint($id); if (!$id) { continue; }
            $full = wp_get_attachment_image_url($id, 'full'); if (!$full) { continue; }
            $items[] = array(
                'url' => $full,
                'thumb' => wp_get_attachment_image_url($id, 'medium_large') ?: $full,
                'caption' => wp_get_attachment_caption($id) ?: get_the_title($id),
            );
        }
        if ($items) { return $items; }
    }

    $key = (string) get_post_meta($post_id, '_wpbbshop_v422_guide_key', true);
    $product = wpbbshop_v425_product_assets();
    $blog = wpbbshop_v425_blog_assets();
    $map = array(
        'heating-plan'        => array($blog['heating-plan'] ?? '', $product['air-source-heat-pump'] ?? '', $product['panel-radiator-22'] ?? ''),
        'radiator-size'       => array($blog['radiator-size'] ?? '', $product['panel-radiator-22'] ?? '', $product['radiator-valve-pack'] ?? ''),
        'cultivator-guide'    => array($blog['cultivator-guide'] ?? '', $product['cultivator-1050'] ?? '', $product['mini-tractor-15hp'] ?? ''),
        'variation-stock'       => array($blog['variation-stock'] ?? '', $product['radiator-valve-pack'] ?? '', $product['underfloor-manifold'] ?? ''),
        'underfloor-heating'  => array($blog['underfloor-heating'] ?? '', $product['underfloor-mat'] ?? '', $product['underfloor-manifold'] ?? ''),
        'garden-maintenance'  => array($blog['garden-maintenance'] ?? '', $product['riding-mower-1050a'] ?? '', $product['cultivator-1050'] ?? ''),
        'heat-pump-guide'     => array($blog['heat-pump-guide'] ?? '', $product['air-source-heat-pump'] ?? '', $product['pump-25-60'] ?? ''),
        'tractor-attachments' => array($blog['tractor-attachments'] ?? '', $product['mini-tractor-15hp'] ?? '', $product['compact-tractor-package'] ?? ''),
    );
    foreach ((array) ($map[$key] ?? array()) as $relative) {
        $url = wpbbshop_v425_asset_url($relative);
        if ($url) { $items[] = array('url' => $url, 'thumb' => $url, 'caption' => get_the_title($post_id)); }
    }
    if (!$items) {
        $url = wpbbshop_v425_guide_image_url($post_id);
        if ($url) { $items[] = array('url'=>$url,'thumb'=>$url,'caption'=>get_the_title($post_id)); }
    }
    return $items;
}

function wpbbshop_v425_post_gallery_html($post_id) {
    $items = wpbbshop_v425_post_gallery_items($post_id);
    if (!$items) { return ''; }
    $label = wpbbshop_v425_t('Article gallery', 'Raksta galerija');
    ob_start(); ?>
    <section class="wpbb-v424-gallery wpbb-v425-gallery" data-wpbb-gallery aria-label="<?php echo esc_attr($label); ?>">
      <div class="wpbb-v424-gallery-stage">
        <div class="wpbb-v424-gallery-track" data-gallery-track>
          <?php foreach ($items as $i => $item) : ?>
            <button type="button" class="wpbb-v424-gallery-slide<?php echo $i===0?' is-active':''; ?>" data-gallery-slide="<?php echo esc_attr($i); ?>" data-gallery-full="<?php echo esc_url($item['url']); ?>" data-gallery-caption="<?php echo esc_attr($item['caption']); ?>">
              <img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['caption']); ?>" <?php echo $i===0?'fetchpriority="high"':'loading="lazy"'; ?> decoding="async">
              <span class="wpbb-v424-gallery-zoom" aria-hidden="true">⌕</span>
            </button>
          <?php endforeach; ?>
        </div>
        <?php if (count($items) > 1) : ?><button type="button" class="wpbb-v424-gallery-arrow is-prev" data-gallery-prev aria-label="<?php echo esc_attr(wpbbshop_v425_t('Previous image','Iepriekšējais attēls')); ?>">‹</button><button type="button" class="wpbb-v424-gallery-arrow is-next" data-gallery-next aria-label="<?php echo esc_attr(wpbbshop_v425_t('Next image','Nākamais attēls')); ?>">›</button><?php endif; ?>
        <span class="wpbb-v424-gallery-count"><b data-gallery-index>1</b> / <?php echo esc_html(count($items)); ?></span>
      </div>
      <?php if (count($items) > 1) : ?><div class="wpbb-v424-gallery-thumbs" role="tablist"><?php foreach ($items as $i=>$item) : ?><button type="button" class="wpbb-v424-gallery-thumb<?php echo $i===0?' is-active':''; ?>" data-gallery-thumb="<?php echo esc_attr($i); ?>"><img src="<?php echo esc_url($item['thumb']); ?>" alt="" loading="lazy" decoding="async"></button><?php endforeach; ?></div><?php endif; ?>
    </section>
    <?php return (string) ob_get_clean();
}
