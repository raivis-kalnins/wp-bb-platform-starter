<?php
/**
 * WP BB Home & Garden 3.0.29
 * Header stability + checkout delivery/newsletter polish.
 *
 * Earlier builds accumulated multiple mobile header CSS layers. This module
 * removes their presentation hooks and becomes the single responsive source
 * of truth while preserving their non-CSS functionality.
 */
defined('ABSPATH') || exit;

/* Disable obsolete presentation layers. Keep language/setup/JS helpers. */
foreach (array(
    'wpbbshop_v308_critical_header_css',
    'wpbbshop_v309_critical_css',
    'wpbbshop_v311_mobile_search_alignment_css',
    'wpbbshop_v317_account_search_css',
    'wpbbshop_v321_header_css',
    'wpbbshop_v327_responsive_header_css',
) as $callback) {
    remove_action('wp_head', $callback, PHP_INT_MAX);
}
remove_action('wp_footer', 'wpbbshop_v327_responsive_header_footer_guard', PHP_INT_MAX);

function wpbbshop_v329_is_en() {
    if (function_exists('pll_current_language')) {
        $lang = wpbbshop_v433_current_language();
        if ($lang) { return $lang === 'en'; }
    }
    return substr((string) get_locale(), 0, 2) !== 'lv';
}

/* Brand the newsletter checkout option correctly and translate it. */
add_filter('gettext', function($translated, $text, $domain) {
    if ($domain === 'wp-newslatter-campaigns' && $text === 'Send me WordPress newsletter updates by email.') {
        return wpbbshop_v329_is_en()
            ? 'Receive WP BB Home & Garden news by email.'
            : 'Saņemt WP BB Home & Garden jaunumus e-pastā.';
    }
    return $translated;
}, 30, 3);

function wpbbshop_v329_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v329-stability-css">
    /* ---------- Mobile/tablet header: one isolated layout below 1050px ---------- */
    @media (max-width:1049px){
      body.wpbbshop-theme .llg-desktop-shell{display:none!important}
      body.wpbbshop-theme #wpbbshop-mobile-header.ll29-mobile-shell,
      body.wpbbshop-theme #wpbbshop-mobile-header.ll29-mobile-shell *{box-sizing:border-box!important}
      body.wpbbshop-theme #wpbbshop-mobile-header.ll29-mobile-shell{
        display:block!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;
        background:#fff!important;color:#0d293d!important;overflow:visible!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-top{
        display:flex!important;align-items:center!important;justify-content:space-between!important;
        width:100%!important;min-height:36px!important;margin:0!important;padding:0 12px!important;
        background:#10364f!important;color:#fff!important;font-size:10px!important;line-height:1!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-top>span:first-child{
        display:flex!important;align-items:center!important;gap:7px!important;white-space:nowrap!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-top .llg-lang-switcher{
        display:flex!important;align-items:center!important;gap:5px!important;margin-left:auto!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-top .llg-lang-link{color:#fff!important;font-size:10px!important;font-weight:850!important}
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-top .wpbbshop-green-icon{width:14px!important;height:14px!important;color:#62d36f!important}

      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-brand{
        display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;
        min-height:78px!important;margin:0!important;padding:8px 12px 6px!important;background:#fff!important;line-height:0!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-brand .wpbbshop-logo,
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-brand .wpbbshop-approved-logo,
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-brand img{
        display:block!important;width:150px!important;max-width:150px!important;height:auto!important;max-height:62px!important;
        margin:0 auto!important;padding:0!important;object-fit:contain!important
      }

      /* Search: category + field + green button in one non-wrapping row. */
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search{
        position:relative!important;display:flex!important;flex-flow:row nowrap!important;align-items:stretch!important;
        width:calc(100% - 16px)!important;height:46px!important;min-height:46px!important;margin:0 8px 8px!important;padding:0!important;
        border:1px solid #d7e0e5!important;border-radius:9px!important;background:#fff!important;overflow:visible!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-search-cat{
        position:relative!important;flex:0 0 106px!important;width:106px!important;height:44px!important;min-width:0!important;
        margin:0!important;padding:0!important;border:0!important;border-right:1px solid #e1e7eb!important;
        border-radius:8px 0 0 8px!important;background:#f8fafb!important;overflow:hidden!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-search-cat:after{
        content:""!important;position:absolute!important;right:10px!important;top:17px!important;width:6px!important;height:6px!important;
        border-right:2px solid #758896!important;border-bottom:2px solid #758896!important;transform:rotate(45deg)!important;pointer-events:none!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-search-cat select{
        appearance:none!important;-webkit-appearance:none!important;display:block!important;width:100%!important;height:44px!important;
        margin:0!important;padding:0 25px 0 9px!important;border:0!important;border-radius:0!important;background:transparent!important;
        color:#173348!important;box-shadow:none!important;outline:0!important;font-size:10px!important;line-height:44px!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-search-field{
        position:relative!important;display:block!important;flex:1 1 auto!important;min-width:0!important;height:44px!important;margin:0!important;padding:0!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search input[type=search]{
        display:block!important;width:100%!important;height:44px!important;min-height:44px!important;margin:0!important;padding:0 10px!important;
        border:0!important;border-radius:0!important;background:#fff!important;color:#173348!important;box-shadow:none!important;outline:0!important;
        font-size:11px!important;line-height:44px!important;-webkit-appearance:none!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit{
        position:relative!important;inset:auto!important;flex:0 0 46px!important;display:grid!important;place-items:center!important;
        width:46px!important;min-width:46px!important;max-width:46px!important;height:44px!important;min-height:44px!important;max-height:44px!important;
        margin:0!important;padding:0!important;border:0!important;border-left:1px solid #278a45!important;border-radius:0 8px 8px 0!important;
        background:#2f9d50!important;background-image:none!important;color:#fff!important;box-shadow:none!important;transform:none!important;overflow:hidden!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:before,
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:after{content:none!important;display:none!important}
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit .wpbbshop-green-icon{
        display:block!important;width:19px!important;height:19px!important;margin:0!important;color:#fff!important;stroke:currentColor!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .wpbbshop-search-results{
        position:absolute!important;top:51px!important;left:0!important;right:0!important;width:auto!important;max-height:62vh!important;overflow:auto!important;z-index:9999!important
      }

      /* Four actions in exactly one row. Flex is used deliberately to avoid old 2x2 grid rules. */
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions{
        display:flex!important;flex-flow:row nowrap!important;align-items:stretch!important;justify-content:stretch!important;
        width:calc(100% - 16px)!important;max-width:none!important;margin:0 8px 8px!important;padding:0!important;gap:5px!important;overflow:visible!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions>a,
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions>button{
        position:relative!important;display:flex!important;flex:1 1 0!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;
        width:auto!important;min-width:0!important;max-width:none!important;height:54px!important;min-height:54px!important;margin:0!important;padding:4px 1px!important;
        border:1px solid #e1e8ed!important;border-radius:9px!important;background:#fff!important;color:#0d293d!important;box-shadow:none!important;
        gap:2px!important;text-decoration:none!important;overflow:visible!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions .wpbbshop-green-icon{
        display:block!important;flex:0 0 auto!important;width:19px!important;height:19px!important;margin:0!important;color:#0d293d!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions span{
        display:block!important;max-width:100%!important;margin:0!important;font-size:8px!important;font-weight:850!important;line-height:1!important;
        white-space:nowrap!important;text-align:center!important;overflow:hidden!important;text-overflow:ellipsis!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions em{
        position:absolute!important;top:3px!important;right:4px!important;display:block!important;min-width:15px!important;height:15px!important;padding:0 4px!important;
        border-radius:999px!important;background:#26994b!important;color:#fff!important;font-style:normal!important;font-size:8px!important;font-weight:850!important;
        line-height:15px!important;text-align:center!important
      }

      body.wpbbshop-theme #wpbbshop-mobile-header .ll29-mobile-navbar{
        display:flex!important;flex-flow:row nowrap!important;align-items:stretch!important;width:100%!important;height:48px!important;min-height:48px!important;
        margin:0!important;padding:0!important;background:#10364f!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-wrap,
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-menu-toggle{flex:1 1 50%!important;min-width:0!important;width:50%!important}
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-button,
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-menu-toggle{
        display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;height:48px!important;min-height:48px!important;
        margin:0!important;padding:0 8px!important;border:0!important;border-radius:0!important;color:#fff!important;gap:7px!important;
        font-size:10.5px!important;font-weight:850!important;line-height:1!important;box-shadow:none!important
      }
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-button{background:#2f9d50!important}
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-menu-toggle{background:#10364f!important}
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-button .wpbbshop-green-icon,
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-menu-toggle .wpbbshop-green-icon{width:17px!important;height:17px!important;color:#fff!important}
      body.wpbbshop-theme .llg-home{padding-top:12px!important}
    }

    @media (max-width:390px){
      body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-search-cat{flex-basis:94px!important;width:94px!important}
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit{flex-basis:42px!important;width:42px!important;min-width:42px!important;max-width:42px!important}
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions{gap:3px!important}
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions span{font-size:7px!important}
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions .wpbbshop-green-icon{width:18px!important;height:18px!important}
    }

    /* ---------- Account password reveal: proper eye control ---------- */
    body.woocommerce-account .password-input,body.woocommerce-account span.password-input{position:relative!important;display:block!important;width:100%!important}
    body.woocommerce-account .password-input input[type=password],body.woocommerce-account .password-input input[type=text]{width:100%!important;padding-right:50px!important}
    body.woocommerce-account .show-password-input,body.woocommerce-account button.show-password-input,body.woocommerce-account span.show-password-input{
      position:absolute!important;z-index:4!important;top:50%!important;right:7px!important;left:auto!important;bottom:auto!important;
      width:34px!important;min-width:34px!important;max-width:34px!important;height:34px!important;min-height:34px!important;margin:0!important;padding:0!important;
      border:1px solid transparent!important;border-radius:8px!important;background:transparent!important;color:#6d7d88!important;font-size:0!important;line-height:0!important;
      text-indent:-9999px!important;transform:translateY(-50%)!important;box-shadow:none!important;cursor:pointer!important;overflow:visible!important
    }
    body.woocommerce-account .show-password-input:before{
      content:""!important;display:block!important;position:absolute!important;left:50%!important;top:50%!important;width:17px!important;height:11px!important;
      border:2px solid currentColor!important;border-radius:55% 45% / 60% 60%!important;background:transparent!important;transform:translate(-50%,-50%) rotate(45deg)!important;box-sizing:border-box!important
    }
    body.woocommerce-account .show-password-input:after{
      content:""!important;display:block!important;position:absolute!important;left:50%!important;top:50%!important;width:5px!important;height:5px!important;
      border-radius:50%!important;background:currentColor!important;transform:translate(-50%,-50%)!important
    }

    /* ---------- Cart + checkout shipping methods: always full card width ---------- */
    body.woocommerce-cart tr.woocommerce-shipping-totals.shipping,
    body.woocommerce-checkout tr.woocommerce-shipping-totals.shipping{
      display:grid!important;grid-template-columns:1fr!important;width:100%!important;max-width:none!important;box-sizing:border-box!important
    }
    body.woocommerce-cart tr.woocommerce-shipping-totals.shipping>th,
    body.woocommerce-cart tr.woocommerce-shipping-totals.shipping>td,
    body.woocommerce-checkout tr.woocommerce-shipping-totals.shipping>th,
    body.woocommerce-checkout tr.woocommerce-shipping-totals.shipping>td{
      display:block!important;width:100%!important;max-width:none!important;min-width:0!important;box-sizing:border-box!important;text-align:left!important
    }
    body.woocommerce-cart ul#shipping_method,
    body.woocommerce-cart .woocommerce-shipping-methods,
    body.woocommerce-checkout ul#shipping_method,
    body.woocommerce-checkout .woocommerce-shipping-methods{
      display:flex!important;flex-direction:column!important;align-items:stretch!important;width:100%!important;max-width:none!important;
      margin:0!important;padding:0!important;gap:8px!important;list-style:none!important
    }
    body.woocommerce-cart ul#shipping_method>li,
    body.woocommerce-cart .woocommerce-shipping-methods>li,
    body.woocommerce-checkout ul#shipping_method>li,
    body.woocommerce-checkout .woocommerce-shipping-methods>li{
      display:flex!important;align-items:flex-start!important;width:100%!important;max-width:none!important;min-width:0!important;
      margin:0!important;padding:11px 12px!important;border:1px solid #dde7e2!important;border-radius:9px!important;background:#fbfdfb!important;box-sizing:border-box!important
    }
    body.woocommerce-cart ul#shipping_method>li label,
    body.woocommerce-cart .woocommerce-shipping-methods>li label,
    body.woocommerce-checkout ul#shipping_method>li label,
    body.woocommerce-checkout .woocommerce-shipping-methods>li label{
      display:block!important;flex:1 1 auto!important;width:auto!important;max-width:none!important;min-width:0!important;margin:0 0 0 8px!important;
      white-space:normal!important;line-height:1.35!important;text-align:left!important
    }
    body.woocommerce-checkout .wp-newslatter-campaigns-checkout{margin:12px 0 0!important;padding:0!important}
    body.woocommerce-checkout .wp-newslatter-campaigns-checkout label{
      display:flex!important;align-items:flex-start!important;gap:8px!important;margin:0!important;color:#556771!important;font-size:12px!important;line-height:1.4!important
    }
    body.woocommerce-checkout .wp-newslatter-campaigns-checkout input[type=checkbox]{flex:0 0 auto!important;margin-top:2px!important}
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v329_css', PHP_INT_MAX);

/* Last cascade guard for the pieces that previously regressed after plugin CSS. */
function wpbbshop_v329_footer_guard() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v329-footer-guard">
    @media(max-width:1049px){
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions{display:flex!important;flex-flow:row nowrap!important;width:calc(100% - 16px)!important;margin:0 8px 8px!important;gap:5px!important}
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions>a,
      body.wpbbshop-theme #wpbbshop-mobile-header nav.ll29-mobile-actions>button{flex:1 1 0!important;width:auto!important;min-width:0!important}
      body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search{display:flex!important;flex-flow:row nowrap!important;width:calc(100% - 16px)!important;margin:0 8px 8px!important}
      body.wpbbshop-theme #wpbbshop-mobile-header .ll29-mobile-navbar{display:flex!important;flex-flow:row nowrap!important}
    }
    </style>
    <?php
}
add_action('wp_footer', 'wpbbshop_v329_footer_guard', PHP_INT_MAX);

function wpbbshop_v329_cache_purge_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    if ((string)get_option('wpbbshop_v329_cache_purged','') === '3.0.29') { return; }
    if (function_exists('wp_theme_purge_all_theme_cache')) { wp_theme_purge_all_theme_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    update_option('wpbbshop_v329_cache_purged','3.0.29',false);
}
add_action('admin_init','wpbbshop_v329_cache_purge_once',1005);
