<?php
/**
 * WP BB Home & Garden 3.0.24
 * Final desktop header isolation.
 *
 * The storefront had accumulated several generations of desktop header rules.
 * v3.0.24 gives the desktop main bar a fresh ll24-* namespace so the homepage
 * and content/archive pages cannot be laid out differently by legacy CSS.
 */
defined('ABSPATH') || exit;

function wpbbshop_v324_header_css() {
    ?>
    <style id="wpbbshop-v324-header-css">
    @media (min-width:1050px){
      body.wpbbshop-theme .llg-desktop-shell{display:block!important}
      body.wpbbshop-theme .llg-mobile-shell{display:none!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar.ll24-mainbar,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar.ll24-mainbar *{box-sizing:border-box!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar.ll24-mainbar{
        display:grid!important;
        grid-template-columns:190px minmax(0,1fr) 320px!important;
        grid-template-rows:auto!important;
        align-items:center!important;
        column-gap:22px!important;
        width:min(calc(100% - 40px),1460px)!important;
        max-width:1460px!important;
        min-height:0!important;
        height:auto!important;
        margin:0 auto!important;
        padding:14px 0 12px!important;
        overflow:visible!important;
        background:#fff!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo{
        display:flex!important;align-items:center!important;justify-content:flex-start!important;
        width:190px!important;min-width:0!important;max-width:190px!important;
        height:72px!important;min-height:72px!important;margin:0!important;padding:0!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo .wpbbshop-logo,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo .wpbbshop-approved-logo,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo img{
        display:block!important;width:176px!important;max-width:176px!important;height:auto!important;max-height:70px!important;
        margin:0!important;padding:0!important;object-fit:contain!important;object-position:left center!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-search-area{
        display:grid!important;grid-template-rows:48px 18px!important;row-gap:5px!important;
        align-self:center!important;min-width:0!important;width:100%!important;height:71px!important;
        margin:0!important;padding:0!important;overflow:visible!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-form{
        position:relative!important;display:grid!important;grid-template-columns:160px minmax(0,1fr) 52px!important;
        grid-template-rows:48px!important;align-items:stretch!important;width:100%!important;height:48px!important;
        min-height:48px!important;margin:0!important;padding:0!important;border:1px solid #d7e0e5!important;
        border-radius:9px!important;background:#fff!important;overflow:visible!important;box-shadow:0 1px 2px rgba(13,41,61,.03)!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-category{
        position:relative!important;height:46px!important;min-width:0!important;margin:0!important;padding:0!important;
        border:0!important;border-right:1px solid #e1e7eb!important;border-radius:8px 0 0 8px!important;
        background:#f8fafb!important;overflow:hidden!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-category:after{
        content:""!important;position:absolute!important;right:14px!important;top:50%!important;width:7px!important;height:7px!important;
        border-right:2px solid #70818c!important;border-bottom:2px solid #70818c!important;
        transform:translateY(-68%) rotate(45deg)!important;pointer-events:none!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-category select{
        appearance:none!important;-webkit-appearance:none!important;display:block!important;width:100%!important;height:46px!important;
        min-height:46px!important;margin:0!important;padding:0 34px 0 14px!important;border:0!important;border-radius:0!important;
        background:transparent!important;box-shadow:none!important;outline:0!important;color:#173348!important;font-family:inherit!important;font-size:12px!important;font-weight:500!important;line-height:46px!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-field{
        position:static!important;display:block!important;height:46px!important;min-width:0!important;margin:0!important;padding:0!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-field input[type=search]{
        display:block!important;width:100%!important;height:46px!important;min-height:46px!important;margin:0!important;padding:0 15px!important;
        border:0!important;border-radius:0!important;background:#fff!important;box-shadow:none!important;outline:0!important;
        color:#263b49!important;font-family:inherit!important;font-size:12px!important;font-weight:400!important;line-height:46px!important;-webkit-appearance:none!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-button{
        position:relative!important;inset:auto!important;display:grid!important;place-items:center!important;width:52px!important;min-width:52px!important;
        max-width:52px!important;height:46px!important;min-height:46px!important;max-height:46px!important;margin:0!important;padding:0!important;
        border:0!important;border-left:1px solid #278a45!important;border-radius:0 8px 8px 0!important;background:#2f9d50!important;
        background-image:none!important;color:#fff!important;box-shadow:none!important;transform:none!important;cursor:pointer!important;line-height:1!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-button:before,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-button:after{content:none!important;display:none!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-button .wpbbshop-green-icon{
        display:block!important;width:20px!important;height:20px!important;margin:0!important;color:#fff!important;stroke:currentColor!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-form .wpbbshop-search-results{
        top:calc(100% + 8px)!important;left:0!important;right:0!important;width:auto!important;z-index:9999!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-popular{
        display:flex!important;align-items:center!important;flex-wrap:nowrap!important;gap:9px!important;width:100%!important;height:18px!important;
        min-height:18px!important;margin:0!important;padding:0 3px!important;overflow:hidden!important;color:#75858f!important;
        font-size:10px!important;line-height:18px!important;white-space:nowrap!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-popular strong{
        flex:0 0 auto!important;margin:0!important;color:#7a8790!important;font-weight:500!important;line-height:18px!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-popular a{
        flex:0 0 auto!important;margin:0!important;color:#287c43!important;font-weight:700!important;line-height:16px!important;
        text-decoration:none!important;border-bottom:1px dotted #8db69a!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions{
        display:flex!important;flex-flow:row nowrap!important;align-items:stretch!important;justify-content:stretch!important;gap:5px!important;
        width:320px!important;min-width:320px!important;max-width:320px!important;height:68px!important;min-height:68px!important;
        margin:0!important;padding:0!important;overflow:visible!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions>a,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions>button{
        position:relative!important;inset:auto!important;display:flex!important;flex:1 1 0!important;flex-direction:column!important;
        align-items:center!important;justify-content:center!important;gap:2px!important;width:auto!important;min-width:0!important;max-width:none!important;
        height:68px!important;min-height:68px!important;max-height:68px!important;margin:0!important;padding:4px 2px!important;border:0!important;
        border-radius:9px!important;background:#fff!important;color:#0d293d!important;box-shadow:none!important;transform:none!important;
        text-decoration:none!important;font-family:inherit!important;overflow:visible!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions>a:hover,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions>button:hover{background:#f3f8f4!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-green-icon{
        display:block!important;flex:0 0 auto!important;width:22px!important;height:22px!important;margin:0!important;color:#0d293d!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions span{
        display:block!important;margin:0!important;color:#0d293d!important;font-size:10px!important;font-weight:800!important;
        line-height:1.05!important;white-space:nowrap!important;text-align:center!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions em{
        position:absolute!important;top:1px!important;right:5px!important;min-width:17px!important;height:17px!important;padding:0 4px!important;
        border-radius:999px!important;background:#26994b!important;color:#fff!important;font-style:normal!important;font-size:9px!important;
        font-weight:800!important;line-height:17px!important;text-align:center!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-cart-total,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-cart-total *{
        display:block!important;max-width:100%!important;margin:0!important;color:#52636f!important;font-size:9px!important;font-weight:650!important;
        line-height:1!important;white-space:nowrap!important;word-break:normal!important;overflow-wrap:normal!important;text-align:center!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-cart-total .woocommerce-Price-amount,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-cart-total .woocommerce-Price-currencySymbol,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-cart-total bdi{display:inline!important;white-space:nowrap!important}
    }

    @media (min-width:1050px) and (max-width:1279px){
      body.wpbbshop-theme #wpbbshop-desktop-mainbar.ll24-mainbar{
        grid-template-columns:168px minmax(0,1fr) 272px!important;column-gap:14px!important;width:min(calc(100% - 28px),1460px)!important;padding:11px 0!important;
      }
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo{width:168px!important;max-width:168px!important;height:66px!important;min-height:66px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo img{width:158px!important;max-width:158px!important;max-height:64px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-form{grid-template-columns:126px minmax(0,1fr) 46px!important;grid-template-rows:44px!important;height:44px!important;min-height:44px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-category,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-category select,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-field,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-field input[type=search],
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-button{height:42px!important;min-height:42px!important;max-height:42px!important;line-height:42px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-search-button{width:46px!important;min-width:46px!important;max-width:46px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-search-area{grid-template-rows:44px 17px!important;height:66px!important;row-gap:5px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar .ll24-popular{height:17px!important;min-height:17px!important;font-size:9px!important;line-height:17px!important;gap:7px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions{width:272px!important;min-width:272px!important;max-width:272px!important;height:62px!important;min-height:62px!important;gap:3px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions>a,
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions>button{height:62px!important;min-height:62px!important;max-height:62px!important;padding:3px 1px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-green-icon{width:20px!important;height:20px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions span{font-size:9px!important}
      body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-actions .wpbbshop-cart-total{display:none!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v324_header_css', PHP_INT_MAX);

/** Purge the parent/theme cache once after activating 3.0.24. */
function wpbbshop_v324_purge_cache_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    if ((string) get_option('wpbbshop_v324_cache_purged', '') === '3.0.24') { return; }
    if (function_exists('wp_theme_purge_all_theme_cache')) { wp_theme_purge_all_theme_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    update_option('wpbbshop_v324_cache_purged', '3.0.24', false);
}
add_action('admin_init', 'wpbbshop_v324_purge_cache_once', 999);
