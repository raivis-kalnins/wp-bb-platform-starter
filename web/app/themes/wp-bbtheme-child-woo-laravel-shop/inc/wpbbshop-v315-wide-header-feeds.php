<?php
/**
 * WP BB Home & Garden 3.0.15 — full-width storefront shell, compact desktop header,
 * hero alignment and static comparison-feed publishing.
 */
defined('ABSPATH') || exit;

function wpbbshop_v315_is_demo_sku($sku) {
    return is_string($sku) && strpos($sku, 'LL-DEMO-') === 0;
}

/**
 * The current demo catalogue is the catalogue visible on the storefront.
 * Publish it to the comparison feeds when the user explicitly uses this build,
 * while keeping unrelated DEMO-BUSINESS starter products blocked.
 */
function wpbbshop_v315_prepare_feed_products() {
    if (!class_exists('WooCommerce') || !function_exists('wc_get_products')) { return; }
    global $wpdb;
    $lookup = $wpdb->wc_product_meta_lookup;
    $ids = $wpdb->get_col(
        "SELECT product_id FROM {$lookup} WHERE sku LIKE 'LL-DEMO-%' LIMIT 1000"
    );
    foreach ((array) $ids as $id) {
        $product = wc_get_product($id);
        if (!$product) { continue; }
        $sku = (string) $product->get_sku();
        if (!wpbbshop_v315_is_demo_sku($sku)) { continue; }
        update_post_meta($id, '_wpbbshop_demo_product', '1');
        update_post_meta($id, '_wpbbshop_feed_exclude', 'no');
        update_post_meta($id, '_wpbbshop_feed_used', 'yes');
        if (get_post_meta($id, '_wpbbshop_feed_brand', true) === '') {
            update_post_meta($id, '_wpbbshop_feed_brand', 'GREEN HOME');
        }
        if (get_post_meta($id, '_wpbbshop_feed_model', true) === '') {
            update_post_meta($id, '_wpbbshop_feed_model', preg_replace('/^LL-DEMO-/', '', $sku));
        }
    }
    if (function_exists('wpbbshop_compare_feed_invalidate')) {
        wpbbshop_compare_feed_invalidate();
    }
}

function wpbbshop_v315_atomic_write($file, $contents) {
    $dir = dirname($file);
    if (!is_dir($dir) || !is_writable($dir)) { return false; }
    $tmp = $file . '.tmp-' . wp_generate_password(8, false, false);
    if (@file_put_contents($tmp, $contents, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    @chmod($tmp, 0644);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * Refresh cached XML payloads under uploads/wpbbshop-feeds.
 * Public /kurpirkt.xml, /salidzini.xml and /ceno.xml requests are routed through
 * WordPress; this compatibility function never writes the document root or .htaccess.
 */
function wpbbshop_v315_publish_static_feeds() {
    static $running = false;
    if ($running || !class_exists('WooCommerce') || !function_exists('wpbbshop_compare_feed_build')) { return false; }
    $running = true;
    $ok = function_exists('wpbbshop_compare_feed_ensure_cache_dir') ? wpbbshop_compare_feed_ensure_cache_dir() : false;
    foreach (array('kurpirkt','salidzini','ceno') as $feed) {
        if (function_exists('wpbbshop_compare_feed_enabled') && !wpbbshop_compare_feed_enabled($feed)) { continue; }
        $xml = wpbbshop_compare_feed_build($feed);
        $cache_file = function_exists('wpbbshop_compare_feed_cache_file') ? wpbbshop_compare_feed_cache_file($feed) : '';
        if (!is_string($xml) || trim($xml) === '' || !$cache_file || !wpbbshop_v315_atomic_write($cache_file, $xml)) {
            $ok = false;
        }
    }
    $running = false;
    return $ok;
}

/** Legacy compatibility: v4 serves feeds through WordPress and never edits root .htaccess. */
function wpbbshop_v315_install_feed_htaccess_rules() {
    return true;
}

function wpbbshop_v315_feed_upgrade() {
    if (!current_user_can('edit_theme_options')) { return; }
    $version = (string) get_option('wpbbshop_v315_feed_version', '');
    if ($version !== '4.0.4') {
        wpbbshop_v315_prepare_feed_products();
        wpbbshop_v315_publish_static_feeds();
        delete_option('wpbbshop_v315_feed_publish_error');
        if (function_exists('wpbbshop_compare_feed_register_rewrites')) {
            wpbbshop_compare_feed_register_rewrites();
            flush_rewrite_rules(false);
        }
        update_option('wpbbshop_v315_feed_version', '4.0.4', false);
    }
}
add_action('after_switch_theme', 'wpbbshop_v315_feed_upgrade', 220);
add_action('admin_init', 'wpbbshop_v315_feed_upgrade', 220);



/** Refresh the actual public XML files immediately after catalogue changes. */
function wpbbshop_v315_queue_static_feed_refresh() {
    if (function_exists('wpbbshop_compare_feed_queue_refresh')) {
        wpbbshop_compare_feed_queue_refresh();
    } elseif (function_exists('wpbbshop_compare_feed_invalidate')) {
        wpbbshop_compare_feed_invalidate();
    }
}
add_action('save_post_product', 'wpbbshop_v315_queue_static_feed_refresh', 200);
add_action('woocommerce_update_product', 'wpbbshop_v315_queue_static_feed_refresh', 200);
add_action('woocommerce_update_product_variation', 'wpbbshop_v315_queue_static_feed_refresh', 200);
add_action('woocommerce_product_set_stock', 'wpbbshop_v315_queue_static_feed_refresh', 200);
add_action('woocommerce_variation_set_stock', 'wpbbshop_v315_queue_static_feed_refresh', 200);
add_action('update_option_wpbbshop_theme_options', 'wpbbshop_v315_queue_static_feed_refresh', 200);

/**
 * Prefer bundled imagery for LL-DEMO products even if an old import left a stale
 * attachment ID on the product. This also keeps XML product images consistent.
 */
function wpbbshop_v315_demo_asset_url($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $sku = (string) $product->get_sku();
    if (function_exists('wpbbshop_green_demo_item_by_sku')) {
        $item = wpbbshop_green_demo_item_by_sku($sku);
        if ($item && !empty($item['image'])) {
            $file = basename($item['image']);
            $path = get_stylesheet_directory() . '/assets/demo-products/' . $file;
            if (file_exists($path)) { return get_stylesheet_directory_uri() . '/assets/demo-products/' . rawurlencode($file); }
        }
    }
    return '';
}

/** Replace stale product thumbnails everywhere for the bundled demo catalogue. */
add_filter('woocommerce_product_get_image_id', function($image_id, $product) {
    // Keep image_id untouched: URL-level renderers below use the bundled asset.
    return $image_id;
}, PHP_INT_MAX, 2);

/** Late CSS wins over WPBBuilder/Bootstrap and the older theme repair layers. */
function wpbbshop_v315_css() {
    ?>
    <style id="wpbbshop-v315-final-layout-css">
    :root{--ll-v315-shell:1480px;--ll-v315-gutter:24px}

    /* One storefront width everywhere. */
    body.wpbbshop-theme .llg-archive-page>.wpbbshop-container,
    body.wpbbshop-theme .llg-single-container,
    body.wpbbshop-theme .llg-account-shell>.wpbbshop-container,
    body.wpbbshop-theme .llg-commerce-shell>.wpbbshop-container,
    body.wpbbshop-theme .wpbbshop-page-shell,
    body.wpbbshop-theme .wpbbshop-search-page>.wpbbshop-container,
    body.wpbbshop-theme .llg-v313-catalog-page>.wpbbshop-container,
    body.wpbbshop-theme .wpbbshop-cart-page,
    body.wpbbshop-theme .wpbbshop-checkout-page,
    body.wpbbshop-theme .llg-woo-page{
      width:min(var(--ll-v315-shell),calc(100% - (var(--ll-v315-gutter) * 2)))!important;
      max-width:var(--ll-v315-shell)!important;
      margin-left:auto!important;margin-right:auto!important;
      padding-left:0!important;padding-right:0!important;
    }
    body.wpbbshop-theme .wpbbshop-page-shell .entry-content,
    body.wpbbshop-theme .wpbbshop-page-shell .wpbbshop-page-card,
    body.woocommerce-account .llg-myaccount-layout,
    body.single-product .llg-single-product-card,
    body.wpbbshop-theme .wpbbshop-archive-products-col{width:100%!important;max-width:none!important}
    body.wpbbshop-theme .wpbbshop-page-card{margin-left:0!important;margin-right:0!important}
    body.wpbbshop-theme .wpbbshop-page-card>.entry-content{margin-left:0!important;margin-right:0!important}

    /* Compact desktop header: remove the empty band under popular searches. */
    @media(min-width:821px){
      body.wpbbshop-theme .llg-mainbar-preview{
        min-height:0!important;height:auto!important;
        grid-template-columns:250px minmax(520px,1fr) 340px!important;
        gap:24px!important;align-items:center!important;
        padding:10px 0 9px!important;
      }
      body.wpbbshop-theme .llg-logo .wpbbshop-logo{width:226px!important;max-width:226px!important}
      body.wpbbshop-theme .llg-logo .wpbbshop-logo img{max-height:104px!important;object-position:left center!important}
      body.wpbbshop-theme .llg-search-column{gap:6px!important;align-self:center!important;justify-self:stretch!important}
      body.wpbbshop-theme .llg-search-extended{height:50px!important;grid-template-columns:164px minmax(0,1fr) 56px!important;border-radius:9px!important;padding-right:0!important;overflow:visible!important}
      body.wpbbshop-theme .llg-search-select-wrap select,
      body.wpbbshop-theme .llg-search-field-wrap input[type=search]{height:48px!important}
      body.wpbbshop-theme .llg-search-select-wrap:after{top:13px!important}
      body.wpbbshop-theme .llg-search-submit,
      body.wpbbshop-theme .llg-search-extended>button{
        position:relative!important;inset:auto!important;transform:none!important;
        width:56px!important;min-width:56px!important;max-width:56px!important;
        height:48px!important;min-height:48px!important;
        margin:0!important;padding:0!important;
        display:grid!important;place-items:center!important;
        border:0!important;border-left:1px solid #d8e0e5!important;
        border-radius:0 8px 8px 0!important;
        background:#f1f4f5!important;color:#3d5261!important;
        box-shadow:none!important;line-height:1!important;
      }
      body.wpbbshop-theme .llg-search-submit:hover,
      body.wpbbshop-theme .llg-search-submit:focus{background:#e8edef!important;color:#1f7b3a!important}
      body.wpbbshop-theme .llg-search-submit .wpbbshop-green-icon,
      body.wpbbshop-theme .llg-search-extended>button .wpbbshop-green-icon{
        width:21px!important;height:21px!important;margin:0!important;display:block!important;position:static!important;transform:none!important
      }
      body.wpbbshop-theme .llg-search-submit:before,body.wpbbshop-theme .llg-search-submit:after{content:none!important;display:none!important}
      body.wpbbshop-theme .llg-popular-searches{min-height:20px!important;padding:0 4px!important;margin:0!important;line-height:1.25!important}
      body.wpbbshop-theme .llg-actions{width:340px!important;min-width:340px!important;align-self:center!important;gap:4px!important}
      body.wpbbshop-theme .llg-actions>a,body.wpbbshop-theme .llg-actions>button{
        height:70px!important;min-height:70px!important;padding:4px 4px!important;justify-content:center!important;gap:3px!important
      }
      body.wpbbshop-theme .llg-actions .wpbbshop-green-icon{width:24px!important;height:24px!important}
      body.wpbbshop-theme .llg-actions em{top:3px!important;right:8px!important}
      body.wpbbshop-theme .llg-actions .wpbbshop-cart-total{font-size:10px!important;line-height:1!important}
      body.wpbbshop-theme .llg-header .wpbbshop-search-results{top:56px!important;left:-165px!important;right:-55px!important}
    }

    /* Homepage hero keeps the category column, artwork and trust list on one baseline. */
    body.wpbbshop-theme .llg-home-stage{align-items:stretch!important;gap:18px!important}
    body.wpbbshop-theme .llg-home-hero{height:100%!important;min-height:420px!important;align-self:stretch!important}
    body.wpbbshop-theme .llg-home-hero-copy{padding-top:38px!important;padding-bottom:38px!important;justify-content:center!important}
    body.wpbbshop-theme .llg-home-hero-art{height:100%!important;min-height:420px!important}
    body.wpbbshop-theme .llg-home-hero-art img{height:100%!important;min-height:420px!important;object-position:center center!important}
    body.wpbbshop-theme .llg-home-hero-trust{top:50%!important;right:24px!important;transform:translateY(-50%)!important;min-width:220px!important}
    body.wpbbshop-theme .llg-clean-benefits{align-items:stretch!important}
    body.wpbbshop-theme .llg-clean-benefits>div{height:100%!important}

    /* Archive/info/account/single page rhythm now matches the homepage grid. */
    body.wpbbshop-theme .llg-archive-page,
    body.wpbbshop-theme .llg-v313-catalog-page,
    body.wpbbshop-theme .wpbbshop-search-page,
    body.wpbbshop-theme .llg-commerce-shell,
    body.wpbbshop-theme .wpbbshop-page-shell,
    body.single-product.wpbbshop-theme .llg-single-page{padding-top:24px!important;padding-bottom:48px!important}
    body.wpbbshop-theme .wpbbshop-archive-hero,
    body.wpbbshop-theme .wpbbshop-page-heading,
    body.wpbbshop-theme .wpbbshop-search-hero,
    body.wpbbshop-theme .llg-v313-page-hero{width:100%!important;max-width:none!important;margin-left:0!important;margin-right:0!important}
    body.wpbbshop-theme .wpbbshop-page-card{padding:24px!important}
    body.woocommerce-account .llg-myaccount-layout{grid-template-columns:270px minmax(0,1fr)!important;gap:22px!important}
    body.woocommerce-account .llg-myaccount-content-card{min-width:0!important;padding:24px!important}

    /* Product page gets the same wide shell instead of a short centered island. */
    body.single-product.wpbbshop-theme .llg-single-product-card{grid-template-columns:minmax(0,1.05fr) minmax(430px,.95fr)!important;gap:24px!important}
    body.single-product.wpbbshop-theme .llg-single-gallery-panel,
    body.single-product.wpbbshop-theme .llg-single-summary-panel{min-width:0!important;max-width:none!important}
    body.single-product.wpbbshop-theme .llg-single-summary-panel{padding:24px!important}
    body.single-product.wpbbshop-theme .llg-single-buybox{padding:16px!important;border:1px solid #dfe7ec!important;background:#f8faf9!important;border-radius:10px!important}

    @media(max-width:1180px){
      body.single-product.wpbbshop-theme .llg-single-product-card{grid-template-columns:1fr!important}
      body.woocommerce-account .llg-myaccount-layout{grid-template-columns:230px minmax(0,1fr)!important}
    }
    @media(max-width:820px){
      :root{--ll-v315-gutter:10px}
      body.wpbbshop-theme .llg-archive-page>.wpbbshop-container,
      body.wpbbshop-theme .llg-single-container,
      body.wpbbshop-theme .llg-account-shell>.wpbbshop-container,
      body.wpbbshop-theme .llg-commerce-shell>.wpbbshop-container,
      body.wpbbshop-theme .wpbbshop-page-shell,
      body.wpbbshop-theme .wpbbshop-search-page>.wpbbshop-container,
      body.wpbbshop-theme .llg-v313-catalog-page>.wpbbshop-container,
      body.wpbbshop-theme .wpbbshop-cart-page,
      body.wpbbshop-theme .wpbbshop-checkout-page,
      body.wpbbshop-theme .llg-woo-page{width:calc(100% - 20px)!important;max-width:none!important}
      body.woocommerce-account .llg-myaccount-layout{grid-template-columns:1fr!important}
      body.single-product.wpbbshop-theme .llg-single-summary-panel{padding:18px!important}
      body.wpbbshop-theme .llg-home-hero-trust{right:12px!important;min-width:0!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v315_css', PHP_INT_MAX);

/**
 * Override the v38 gallery just before rendering by marking LL-DEMO products as
 * demo products. This makes the existing gallery renderer prefer the SKU asset.
 */
add_action('wp', function() {
    if (!function_exists('is_product') || !is_product() || !function_exists('wc_get_product')) { return; }
    $product = wc_get_product(get_queried_object_id());
    if (!$product) { return; }
    if (wpbbshop_v315_is_demo_sku((string) $product->get_sku()) && get_post_meta($product->get_id(), '_wpbbshop_demo_product', true) !== '1') {
        update_post_meta($product->get_id(), '_wpbbshop_demo_product', '1');
    }
}, 1);
