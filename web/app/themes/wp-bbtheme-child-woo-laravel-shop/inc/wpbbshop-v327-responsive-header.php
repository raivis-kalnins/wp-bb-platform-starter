<?php
/**
 * WP BB Home & Garden 3.0.27
 * Final responsive/mobile header geometry.
 *
 * This layer is intentionally registered last and repeats the critical mobile
 * rules in wp_footer. Earlier versions accumulated several mobile breakpoint
 * rules; this file is the final source of truth below 1050px.
 */
defined('ABSPATH') || exit;

function wpbbshop_v327_responsive_header_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v327-responsive-header-css">
    @media (max-width:1049px){
      body.wpbbshop-theme .llg-header,
      body.wpbbshop-theme .llg-header *{box-sizing:border-box!important}
      body.wpbbshop-theme .llg-desktop-shell{display:none!important}
      body.wpbbshop-theme .llg-mobile-shell{
        display:block!important;width:100%!important;max-width:100%!important;
        margin:0!important;padding:0!important;background:#fff!important;overflow:visible!important
      }
      body.wpbbshop-theme .llg-mobile-top{
        display:flex!important;align-items:center!important;justify-content:space-between!important;
        width:100%!important;min-height:36px!important;margin:0!important;padding:0 12px!important;
        background:#10364f!important;color:#fff!important;font-size:10px!important;line-height:1!important
      }
      body.wpbbshop-theme .llg-mobile-top>span:first-child{display:flex!important;align-items:center!important;gap:7px!important;white-space:nowrap!important}
      body.wpbbshop-theme .llg-mobile-top .llg-lang-switcher{margin-left:auto!important;display:flex!important;align-items:center!important;gap:5px!important}
      body.wpbbshop-theme .llg-mobile-top .llg-lang-link{color:#fff!important;font-size:10px!important;font-weight:850!important;line-height:1!important}
      body.wpbbshop-theme .llg-mobile-top .wpbbshop-green-icon{width:14px!important;height:14px!important;color:#62d36f!important}

      body.wpbbshop-theme .llg-mobile-brand{
        display:flex!important;align-items:center!important;justify-content:center!important;
        width:100%!important;min-height:82px!important;margin:0!important;padding:10px 12px 8px!important;
        background:#fff!important;line-height:0!important
      }
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-logo,
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-mobile-brand img{
        display:block!important;width:160px!important;max-width:160px!important;height:auto!important;max-height:66px!important;
        margin:0 auto!important;padding:0!important;object-fit:contain!important
      }

      /* Search is always one row: category | input | green submit. */
      body.wpbbshop-theme .llg-mobile-search{
        position:relative!important;display:grid!important;grid-template-columns:108px minmax(0,1fr) 46px!important;
        align-items:stretch!important;width:calc(100% - 20px)!important;height:46px!important;min-height:46px!important;
        margin:0 10px 10px!important;padding:0!important;border:1px solid #d7e0e5!important;border-radius:9px!important;
        background:#fff!important;box-shadow:0 1px 2px rgba(13,41,61,.04)!important;overflow:visible!important
      }
      body.wpbbshop-theme .llg-mobile-search-cat{
        position:relative!important;display:block!important;height:44px!important;min-width:0!important;margin:0!important;padding:0!important;
        border:0!important;border-right:1px solid #e1e7eb!important;border-radius:8px 0 0 8px!important;background:#f8fafb!important;overflow:hidden!important
      }
      body.wpbbshop-theme .llg-mobile-search-cat:after{
        content:""!important;position:absolute!important;right:10px!important;top:17px!important;width:6px!important;height:6px!important;
        border-right:2px solid #758896!important;border-bottom:2px solid #758896!important;transform:rotate(45deg)!important;pointer-events:none!important
      }
      body.wpbbshop-theme .llg-mobile-search-cat select{
        appearance:none!important;-webkit-appearance:none!important;display:block!important;width:100%!important;height:44px!important;min-height:44px!important;
        margin:0!important;padding:0 25px 0 9px!important;border:0!important;border-radius:0!important;background:transparent!important;
        color:#173348!important;box-shadow:none!important;outline:0!important;font-size:10px!important;line-height:44px!important
      }
      body.wpbbshop-theme .llg-mobile-search-field{
        position:relative!important;display:block!important;height:44px!important;min-width:0!important;margin:0!important;padding:0!important
      }
      body.wpbbshop-theme .llg-mobile-search input[type=search]{
        display:block!important;width:100%!important;height:44px!important;min-height:44px!important;margin:0!important;padding:0 10px!important;
        border:0!important;border-radius:0!important;background:#fff!important;color:#173348!important;box-shadow:none!important;outline:0!important;
        font-size:11px!important;line-height:44px!important;-webkit-appearance:none!important
      }
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{
        position:relative!important;inset:auto!important;display:grid!important;place-items:center!important;
        width:46px!important;min-width:46px!important;max-width:46px!important;height:44px!important;min-height:44px!important;max-height:44px!important;
        margin:0!important;padding:0!important;border:0!important;border-left:1px solid #278a45!important;border-radius:0 8px 8px 0!important;
        background:#2f9d50!important;background-image:none!important;color:#fff!important;box-shadow:none!important;transform:none!important;overflow:hidden!important
      }
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit:before,
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit:after{content:none!important;display:none!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit .wpbbshop-green-icon{
        display:block!important;width:19px!important;height:19px!important;margin:0!important;color:#fff!important;stroke:currentColor!important
      }
      body.wpbbshop-theme .llg-mobile-search .wpbbshop-search-results{
        position:absolute!important;top:51px!important;left:0!important;right:0!important;width:auto!important;max-height:62vh!important;
        overflow:auto!important;z-index:5000!important
      }

      /* Four equal action cards on one row. Never fall back to 2x2. */
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions{
        display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;grid-auto-flow:column!important;
        align-items:stretch!important;width:calc(100% - 20px)!important;max-width:none!important;margin:0 10px 10px!important;
        padding:0!important;gap:6px!important;overflow:visible!important
      }
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions>a,
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions>button{
        position:relative!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;
        width:100%!important;min-width:0!important;max-width:none!important;height:56px!important;min-height:56px!important;margin:0!important;padding:4px 1px!important;
        border:1px solid #e1e8ed!important;border-radius:9px!important;background:#fff!important;color:#0d293d!important;box-shadow:0 1px 2px rgba(13,41,61,.025)!important;
        gap:2px!important;text-decoration:none!important;overflow:visible!important
      }
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions .wpbbshop-green-icon{
        display:block!important;flex:0 0 auto!important;width:20px!important;height:20px!important;margin:0!important;color:#0d293d!important
      }
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions span{
        display:block!important;max-width:100%!important;margin:0!important;font-size:8.5px!important;font-weight:850!important;
        line-height:1!important;white-space:nowrap!important;text-align:center!important;overflow:hidden!important;text-overflow:ellipsis!important
      }
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions em{
        position:absolute!important;top:3px!important;right:5px!important;display:block!important;min-width:16px!important;height:16px!important;padding:0 4px!important;
        border-radius:999px!important;background:#26994b!important;color:#fff!important;font-style:normal!important;font-size:8px!important;font-weight:850!important;line-height:16px!important;text-align:center!important
      }
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions .wpbbshop-cart-total{display:none!important}

      body.wpbbshop-theme .llg-mobile-navbar{
        display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;align-items:stretch!important;
        width:100%!important;height:48px!important;min-height:48px!important;margin:0!important;padding:0!important;background:#10364f!important
      }
      body.wpbbshop-theme .llg-mobile-catalog-wrap{position:relative!important;width:100%!important;min-width:0!important;height:48px!important;margin:0!important}
      body.wpbbshop-theme .llg-mobile-catalog-button,
      body.wpbbshop-theme .llg-mobile-menu-toggle{
        display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;min-width:0!important;max-width:none!important;
        height:48px!important;min-height:48px!important;margin:0!important;padding:0 8px!important;border:0!important;border-radius:0!important;
        color:#fff!important;gap:7px!important;font-size:10.5px!important;font-weight:850!important;line-height:1!important;box-shadow:none!important
      }
      body.wpbbshop-theme .llg-mobile-catalog-button{background:#2f9d50!important}
      body.wpbbshop-theme .llg-mobile-menu-toggle{background:#10364f!important}
      body.wpbbshop-theme .llg-mobile-catalog-button .wpbbshop-green-icon,
      body.wpbbshop-theme .llg-mobile-menu-toggle .wpbbshop-green-icon{display:block!important;width:17px!important;height:17px!important;color:#fff!important}

      /* Keep the first content section visually attached to the mobile header. */
      body.wpbbshop-theme .llg-home{padding-top:16px!important}
      body.wpbbshop-theme .llg-home .wpbbshop-container{max-width:100%!important}
    }

    @media (max-width:520px){
      body.wpbbshop-theme .llg-mobile-brand{min-height:76px!important;padding:8px 10px 6px!important}
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-logo,
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-mobile-brand img{width:148px!important;max-width:148px!important;max-height:60px!important}
      body.wpbbshop-theme .llg-mobile-search{grid-template-columns:98px minmax(0,1fr) 44px!important;width:calc(100% - 16px)!important;margin-left:8px!important;margin-right:8px!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{width:44px!important;min-width:44px!important;max-width:44px!important}
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions{width:calc(100% - 16px)!important;margin-left:8px!important;margin-right:8px!important;gap:4px!important}
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions>a,
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions>button{height:52px!important;min-height:52px!important;padding-left:0!important;padding-right:0!important}
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions span{font-size:7.75px!important}
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions .wpbbshop-green-icon{width:18px!important;height:18px!important}
    }

    @media (max-width:360px){
      body.wpbbshop-theme .llg-mobile-search{grid-template-columns:88px minmax(0,1fr) 42px!important}
      body.wpbbshop-theme .llg-mobile-search-cat select{font-size:9px!important;padding-left:7px!important}
      body.wpbbshop-theme .llg-mobile-search input[type=search]{font-size:10px!important;padding-left:7px!important;padding-right:7px!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{width:42px!important;min-width:42px!important;max-width:42px!important}
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions{gap:3px!important}
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions span{font-size:7px!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v327_responsive_header_css', PHP_INT_MAX);

/* Repeat only the cascade-critical declarations after all plugin/theme head CSS. */
function wpbbshop_v327_responsive_header_footer_guard() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v327-responsive-header-guard">
    @media(max-width:1049px){
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;grid-auto-flow:column!important;width:calc(100% - 20px)!important;margin:0 10px 10px!important;padding:0!important;gap:6px!important}
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions>a,
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions>button{width:100%!important;min-width:0!important;height:56px!important;min-height:56px!important;margin:0!important}
      body.wpbbshop-theme .llg-mobile-search{display:grid!important;grid-template-columns:108px minmax(0,1fr) 46px!important;padding:0!important;overflow:visible!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{position:relative!important;inset:auto!important;width:46px!important;min-width:46px!important;height:44px!important;background:#2f9d50!important;color:#fff!important;transform:none!important}
      body.wpbbshop-theme .llg-mobile-navbar{display:grid!important;grid-template-columns:1fr 1fr!important}
    }
    @media(max-width:520px){
      body.wpbbshop-theme .llg-header .llg-mobile-shell .llg-mobile-actions{width:calc(100% - 16px)!important;margin-left:8px!important;margin-right:8px!important;gap:4px!important}
      body.wpbbshop-theme .llg-mobile-search{grid-template-columns:98px minmax(0,1fr) 44px!important;width:calc(100% - 16px)!important;margin-left:8px!important;margin-right:8px!important}
    }
    </style>
    <?php
}
add_action('wp_footer', 'wpbbshop_v327_responsive_header_footer_guard', PHP_INT_MAX);

function wpbbshop_v327_purge_cache_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    if ((string)get_option('wpbbshop_v327_cache_purged', '') === '3.0.27') { return; }
    if (function_exists('wp_theme_purge_all_theme_cache')) { wp_theme_purge_all_theme_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    update_option('wpbbshop_v327_cache_purged', '3.0.27', false);
}
add_action('admin_init', 'wpbbshop_v327_purge_cache_once', 1001);
