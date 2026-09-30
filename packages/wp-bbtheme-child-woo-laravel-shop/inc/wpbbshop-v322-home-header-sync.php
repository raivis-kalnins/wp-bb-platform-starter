<?php
/**
 * WP BB Home & Garden 3.0.22
 * Homepage header sync + cache reset.
 *
 * The product-category header already renders correctly. This layer only
 * normalizes the front-page desktop header to the same geometry and clears
 * the parent theme's cached front page when the theme version changes.
 */
defined('ABSPATH') || exit;

function wpbbshop_v322_home_header_css() {
    ?>
    <style id="wpbbshop-v322-home-header-css">
    @media (min-width:1280px){
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar{
        box-sizing:border-box!important;
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:center!important;
        justify-content:flex-start!important;
        gap:24px!important;
        width:min(calc(100% - 40px),1460px)!important;
        max-width:1460px!important;
        min-height:0!important;
        height:auto!important;
        margin:0 auto!important;
        padding:14px 0 13px!important;
        overflow:visible!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-logo{
        box-sizing:border-box!important;
        flex:0 0 220px!important;
        width:220px!important;
        min-width:220px!important;
        max-width:220px!important;
        height:auto!important;
        min-height:0!important;
        display:flex!important;
        align-items:center!important;
        justify-content:flex-start!important;
        align-self:center!important;
        margin:0!important;
        padding:0!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-logo .wpbbshop-logo,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-logo .wpbbshop-approved-logo,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-logo img{
        display:block!important;
        width:210px!important;
        max-width:210px!important;
        height:auto!important;
        max-height:86px!important;
        margin:0!important;
        object-fit:contain!important;
        object-position:left center!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-search-area{
        box-sizing:border-box!important;
        flex:1 1 0!important;
        width:auto!important;
        min-width:0!important;
        max-width:none!important;
        height:auto!important;
        min-height:0!important;
        display:flex!important;
        flex-direction:column!important;
        align-items:stretch!important;
        justify-content:center!important;
        align-self:center!important;
        gap:7px!important;
        margin:0!important;
        padding:0!important;
        overflow:visible!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-form{
        box-sizing:border-box!important;
        position:relative!important;
        display:grid!important;
        grid-template-columns:164px minmax(0,1fr) 54px!important;
        grid-template-rows:50px!important;
        align-items:stretch!important;
        width:100%!important;
        height:50px!important;
        min-height:50px!important;
        max-height:50px!important;
        margin:0!important;
        padding:0!important;
        border:1px solid #d7e0e5!important;
        border-radius:9px!important;
        background:#fff!important;
        overflow:visible!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-category,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-field,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-button{
        box-sizing:border-box!important;
        height:48px!important;
        min-height:48px!important;
        max-height:48px!important;
        margin:0!important;
        top:auto!important;
        bottom:auto!important;
        transform:none!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-category select,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-field input[type=search]{
        box-sizing:border-box!important;
        height:48px!important;
        min-height:48px!important;
        max-height:48px!important;
        line-height:48px!important;
        margin:0!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-button{
        display:grid!important;
        place-items:center!important;
        width:54px!important;
        min-width:54px!important;
        max-width:54px!important;
        padding:0!important;
        border:0!important;
        border-left:1px solid #278a45!important;
        border-radius:0 8px 8px 0!important;
        background:#2f9d50!important;
        color:#fff!important;
        box-shadow:none!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-button .wpbbshop-green-icon{
        display:block!important;
        width:21px!important;
        height:21px!important;
        margin:0!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-popular{
        box-sizing:border-box!important;
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:center!important;
        gap:10px!important;
        width:100%!important;
        height:18px!important;
        min-height:18px!important;
        max-height:18px!important;
        margin:0!important;
        padding:0 4px!important;
        overflow:hidden!important;
        white-space:nowrap!important;
        font-size:10.5px!important;
        line-height:18px!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-popular strong,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-popular a{
        flex:0 0 auto!important;
        line-height:18px!important;
        margin:0!important;
        padding:0!important;
        white-space:nowrap!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions{
        box-sizing:border-box!important;
        flex:0 0 352px!important;
        width:352px!important;
        min-width:352px!important;
        max-width:352px!important;
        height:70px!important;
        min-height:70px!important;
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:center!important;
        justify-content:space-between!important;
        align-self:center!important;
        gap:8px!important;
        margin:0!important;
        padding:0!important;
        overflow:visible!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions>a,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions>button{
        box-sizing:border-box!important;
        position:relative!important;
        flex:0 0 82px!important;
        width:82px!important;
        min-width:82px!important;
        max-width:82px!important;
        height:70px!important;
        min-height:70px!important;
        max-height:70px!important;
        display:flex!important;
        flex-direction:column!important;
        align-items:center!important;
        justify-content:center!important;
        gap:3px!important;
        margin:0!important;
        padding:5px 3px!important;
        overflow:visible!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions span{
        display:block!important;
        margin:0!important;
        line-height:1.05!important;
        white-space:nowrap!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions .wpbbshop-cart-total{
        display:inline-flex!important;
        align-items:center!important;
        justify-content:center!important;
        width:auto!important;
        max-width:none!important;
        margin:1px 0 0!important;
        line-height:1!important;
        white-space:nowrap!important;
        flex-wrap:nowrap!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions .wpbbshop-cart-total *,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions .wpbbshop-cart-total bdi{
        display:inline!important;
        white-space:nowrap!important;
      }
    }

    @media (min-width:1050px) and (max-width:1279px){
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar{
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:center!important;
        gap:16px!important;
        width:min(calc(100% - 28px),1460px)!important;
        min-height:0!important;
        height:auto!important;
        margin:0 auto!important;
        padding:12px 0!important;
        overflow:visible!important;
      }
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-logo{flex:0 0 178px!important;width:178px!important;min-width:178px!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-logo img{width:170px!important;max-width:170px!important;max-height:76px!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-search-area{flex:1 1 0!important;min-width:0!important;display:flex!important;flex-direction:column!important;gap:5px!important;height:auto!important;margin:0!important;padding:0!important;overflow:visible!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-form{display:grid!important;grid-template-columns:132px minmax(0,1fr) 48px!important;grid-template-rows:46px!important;width:100%!important;height:46px!important;min-height:46px!important;margin:0!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-category,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-field,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-button,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-category select,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-field input[type=search]{height:44px!important;min-height:44px!important;max-height:44px!important;line-height:44px!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-search-button{width:48px!important;min-width:48px!important;max-width:48px!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar .llh-popular{display:flex!important;height:18px!important;min-height:18px!important;line-height:18px!important;gap:7px!important;font-size:9px!important;margin:0!important;padding:0 3px!important;overflow:hidden!important;white-space:nowrap!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions{flex:0 0 268px!important;width:268px!important;min-width:268px!important;max-width:268px!important;height:60px!important;display:flex!important;flex-flow:row nowrap!important;align-items:center!important;justify-content:space-between!important;gap:4px!important;margin:0!important;padding:0!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions>a,
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions>button{flex:0 0 64px!important;width:64px!important;min-width:64px!important;max-width:64px!important;height:60px!important;min-height:60px!important;max-height:60px!important;margin:0!important;padding:4px 1px!important}
      body.home.wpbbshop-theme #wpbbshop-desktop-mainbar>.llh-actions .wpbbshop-cart-total{display:none!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v322_home_header_css', PHP_INT_MAX);

/** Purge the parent theme cache once when 3.0.22 is first loaded in wp-admin. */
function wpbbshop_v322_purge_cache_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    $done = (string) get_option('wpbbshop_v322_cache_purged', '');
    if ($done === '3.0.22') { return; }

    if (function_exists('wp_theme_purge_all_theme_cache')) {
        wp_theme_purge_all_theme_cache();
    } elseif (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    delete_transient('wc_products_onsale');
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    update_option('wpbbshop_v322_cache_purged', '3.0.22', false);
}
add_action('admin_init', 'wpbbshop_v322_purge_cache_once', 999);
