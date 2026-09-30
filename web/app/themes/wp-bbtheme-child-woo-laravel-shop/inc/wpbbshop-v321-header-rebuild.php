<?php
/**
 * WP BB Home & Garden 3.0.23
 * Isolated responsive header rebuild.
 *
 * Desktop header uses unique llh-* classes so legacy llg-* header rules cannot
 * resize/reflow the search and shop actions. Mobile keeps the established
 * functional markup but gets one final responsive geometry layer.
 */
defined('ABSPATH') || exit;

function wpbbshop_v321_header_css() {
    ?>
    <style id="wpbbshop-v321-header-css">
    /* ---------- shared ---------- */
    body.wpbbshop-theme .llh-mainbar,
    body.wpbbshop-theme .llh-mainbar *{box-sizing:border-box}
    body.wpbbshop-theme .llh-mainbar{width:min(calc(100% - 28px),1460px);margin:0 auto;background:#fff}
    body.wpbbshop-theme .llh-logo{min-width:0}
    body.wpbbshop-theme .llh-logo .wpbbshop-logo,
    body.wpbbshop-theme .llh-logo .wpbbshop-approved-logo,
    body.wpbbshop-theme .llh-logo img{display:block;height:auto!important;object-fit:contain!important;object-position:left center!important;margin:0!important}

    body.wpbbshop-theme .llh-search-area{min-width:0}
    body.wpbbshop-theme .llh-search-form{position:relative;display:grid;align-items:stretch;width:100%;padding:0;margin:0;border:1px solid #d7e0e5;border-radius:9px;background:#fff;overflow:visible;box-shadow:0 1px 2px rgba(13,41,61,.03)}
    body.wpbbshop-theme .llh-search-category{position:relative;min-width:0;border-right:1px solid #e1e7eb;background:#f8fafb;border-radius:8px 0 0 8px;overflow:hidden}
    body.wpbbshop-theme .llh-search-category:after{content:"";position:absolute;right:14px;top:50%;width:7px;height:7px;border-right:2px solid #70818c;border-bottom:2px solid #70818c;transform:translateY(-68%) rotate(45deg);pointer-events:none}
    body.wpbbshop-theme .llh-search-category select{appearance:none;-webkit-appearance:none;display:block;width:100%;height:100%;margin:0;padding:0 34px 0 15px;border:0!important;border-radius:0!important;background:transparent!important;box-shadow:none!important;outline:0;color:#173348;font-family:inherit;font-weight:500}
    body.wpbbshop-theme .llh-search-field{position:static!important;min-width:0;height:100%;margin:0;padding:0}
    body.wpbbshop-theme .llh-search-field input[type=search]{display:block;width:100%!important;height:100%!important;min-height:0!important;margin:0!important;padding:0 16px!important;border:0!important;border-radius:0!important;background:#fff!important;box-shadow:none!important;outline:0!important;color:#263b49!important;font-family:inherit!important;font-weight:400!important;-webkit-appearance:none!important}
    body.wpbbshop-theme .llh-search-field input[type=search]::-webkit-search-cancel-button{display:none}
    body.wpbbshop-theme .llh-search-button{position:relative!important;inset:auto!important;display:grid!important;place-items:center!important;width:100%!important;height:100%!important;min-width:0!important;min-height:0!important;margin:0!important;padding:0!important;border:0!important;border-left:1px solid #278a45!important;border-radius:0 8px 8px 0!important;background:#2f9d50!important;background-image:none!important;color:#fff!important;box-shadow:none!important;transform:none!important;cursor:pointer!important;line-height:1!important}
    body.wpbbshop-theme .llh-search-button:hover,
    body.wpbbshop-theme .llh-search-button:focus-visible{background:#267f43!important;color:#fff!important;outline:0!important}
    body.wpbbshop-theme .llh-search-button:before,
    body.wpbbshop-theme .llh-search-button:after{content:none!important;display:none!important}
    body.wpbbshop-theme .llh-search-button .wpbbshop-green-icon{display:block!important;width:21px!important;height:21px!important;margin:0!important;color:#fff!important;stroke:currentColor!important;transform:none!important}
    body.wpbbshop-theme .llh-search-form .wpbbshop-search-results{top:calc(100% + 8px)!important;left:0!important;right:0!important;width:auto!important;max-height:min(520px,65vh)!important;overflow:auto!important;z-index:1000!important}

    body.wpbbshop-theme .llh-popular{display:flex;align-items:center;flex-wrap:nowrap;gap:11px;min-width:0;margin:0;padding:0 4px;color:#788792;white-space:nowrap;overflow:hidden}
    body.wpbbshop-theme .llh-popular strong{flex:0 0 auto;margin:0;font-weight:500;color:#7a8790}
    body.wpbbshop-theme .llh-popular a{flex:0 0 auto;margin:0;color:#287c43;font-weight:700;text-decoration:none;border-bottom:1px dotted #8db69a;line-height:1.2}

    body.wpbbshop-theme .llh-actions{min-width:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));align-items:stretch;margin:0;padding:0}
    body.wpbbshop-theme .llh-actions>a,
    body.wpbbshop-theme .llh-actions>button{position:relative!important;inset:auto!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;min-width:0!important;width:100%!important;margin:0!important;padding:5px 2px!important;border:0!important;border-radius:9px!important;background:#fff!important;color:#0d293d!important;box-shadow:none!important;transform:none!important;text-decoration:none!important;cursor:pointer!important;font-family:inherit!important}
    body.wpbbshop-theme .llh-actions>a:hover,
    body.wpbbshop-theme .llh-actions>button:hover{background:#f3f8f4!important}
    body.wpbbshop-theme .llh-actions .wpbbshop-green-icon{display:block!important;flex:0 0 auto!important;margin:0!important;color:#0d293d!important}
    body.wpbbshop-theme .llh-actions span{display:block!important;margin:0!important;color:#0d293d!important;font-weight:800!important;white-space:nowrap!important;text-align:center!important}
    body.wpbbshop-theme .llh-actions em{position:absolute!important;top:1px!important;right:5px!important;min-width:18px!important;height:18px!important;padding:0 4px!important;border-radius:999px!important;background:#26994b!important;color:#fff!important;font-style:normal!important;font-weight:800!important;line-height:18px!important;text-align:center!important}
    body.wpbbshop-theme .llh-actions .wpbbshop-cart-total{display:block!important;max-width:100%!important;margin:1px 0 0!important;color:#52636f!important;font-weight:600!important;line-height:1!important;white-space:nowrap!important;text-align:center!important}
    body.wpbbshop-theme .llh-actions .wpbbshop-cart-total *{white-space:nowrap!important}
    body.wpbbshop-theme .llh-actions .wpbbshop-cart-total .woocommerce-Price-amount,body.wpbbshop-theme .llh-actions .wpbbshop-cart-total .woocommerce-Price-currencySymbol,body.wpbbshop-theme .llh-actions .wpbbshop-cart-total bdi{display:inline!important;white-space:nowrap!important;word-break:normal!important}

    /* Keep section links on one line; prevents the separate arrow seen on home. */
    body.wpbbshop-theme .llg-section-head>a{display:inline-flex!important;align-items:center!important;justify-content:flex-end!important;flex-wrap:nowrap!important;gap:6px!important;white-space:nowrap!important;line-height:1.2!important;min-width:max-content!important}
    body.wpbbshop-theme .llg-section-head>a .wpbbshop-green-icon{display:block!important;flex:0 0 16px!important;width:16px!important;height:16px!important;margin:0!important}

    /* ---------- wide desktop ---------- */
    @media (min-width:1280px){
      body.wpbbshop-theme .llg-desktop-shell{display:block!important}
      body.wpbbshop-theme .llg-mobile-shell{display:none!important}
      body.wpbbshop-theme .llh-mainbar{display:grid;grid-template-columns:220px minmax(520px,1fr) 316px;gap:20px;align-items:center;padding:12px 0 10px;min-height:100px}
      body.wpbbshop-theme .llh-logo img{width:180px!important;max-width:180px!important;max-height:78px!important}
      body.wpbbshop-theme .llh-search-area{display:grid;grid-template-rows:50px 18px;gap:5px;align-self:center}
      body.wpbbshop-theme .llh-search-form{grid-template-columns:164px minmax(0,1fr) 54px;height:50px}
      body.wpbbshop-theme .llh-search-category select,
      body.wpbbshop-theme .llh-search-field input[type=search]{font-size:13px!important;line-height:48px!important}
      body.wpbbshop-theme .llh-popular{height:18px;font-size:10px;line-height:18px}
      body.wpbbshop-theme .llh-actions{grid-template-columns:repeat(4,76px);gap:4px;justify-content:end}
      body.wpbbshop-theme .llh-actions>a,
      body.wpbbshop-theme .llh-actions>button{height:64px!important;min-height:64px!important;gap:2px!important}
      body.wpbbshop-theme .llh-actions .wpbbshop-green-icon{width:23px!important;height:23px!important}
      body.wpbbshop-theme .llh-actions span{font-size:10.5px!important;line-height:1.05!important}
      body.wpbbshop-theme .llh-actions .wpbbshop-cart-total{font-size:9px!important;white-space:nowrap!important;word-break:normal!important;overflow-wrap:normal!important}
    }

    /* ---------- compact desktop ---------- */
    @media (min-width:1050px) and (max-width:1279px){
      body.wpbbshop-theme .llg-desktop-shell{display:block!important}
      body.wpbbshop-theme .llg-mobile-shell{display:none!important}
      body.wpbbshop-theme .llh-mainbar{display:grid;grid-template-columns:178px minmax(330px,1fr) 268px;gap:16px;align-items:center;padding:12px 0;min-height:90px}
      body.wpbbshop-theme .llh-logo img{width:170px!important;max-width:170px!important;max-height:76px!important}
      body.wpbbshop-theme .llh-search-area{display:grid;grid-template-rows:46px 18px;gap:5px}
      body.wpbbshop-theme .llh-search-form{grid-template-columns:132px minmax(0,1fr) 48px;height:46px}
      body.wpbbshop-theme .llh-search-category select,
      body.wpbbshop-theme .llh-search-field input[type=search]{font-size:11px!important;line-height:44px!important;padding-left:11px!important}
      body.wpbbshop-theme .llh-popular{height:18px;gap:7px;font-size:9px;line-height:18px}
      body.wpbbshop-theme .llh-actions{grid-template-columns:repeat(4,64px);gap:4px;justify-content:end}
      body.wpbbshop-theme .llh-actions>a,
      body.wpbbshop-theme .llh-actions>button{height:60px!important;min-height:60px!important;gap:2px!important}
      body.wpbbshop-theme .llh-actions .wpbbshop-green-icon{width:20px!important;height:20px!important}
      body.wpbbshop-theme .llh-actions span{font-size:9px!important;line-height:1!important}
      body.wpbbshop-theme .llh-actions .wpbbshop-cart-total{display:none!important}
      body.wpbbshop-theme .llh-actions em{top:0!important;right:2px!important;min-width:16px!important;height:16px!important;font-size:8px!important;line-height:16px!important}
    }

    /* ---------- tablet + mobile ---------- */
    @media (max-width:1049px){
      body.wpbbshop-theme .llg-desktop-shell{display:none!important}
      body.wpbbshop-theme .llg-mobile-shell{display:block!important;width:100%!important;max-width:none!important;margin:0!important;padding:0!important;background:#fff!important;overflow:visible!important}
      body.wpbbshop-theme .llg-mobile-top{box-sizing:border-box!important;display:flex!important;align-items:center!important;justify-content:space-between!important;min-height:38px!important;padding:0 12px!important;background:#10364f!important;color:#fff!important;font-size:10px!important}
      body.wpbbshop-theme .llg-mobile-top .wpbbshop-green-icon{width:15px!important;height:15px!important;color:#57cf70!important}
      body.wpbbshop-theme .llg-mobile-brand{box-sizing:border-box!important;display:flex!important;align-items:center!important;justify-content:center!important;min-height:92px!important;padding:12px 12px 8px!important;background:#fff!important}
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-logo,
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-mobile-brand img{display:block!important;width:176px!important;max-width:176px!important;height:auto!important;max-height:78px!important;margin:0 auto!important;object-fit:contain!important}

      body.wpbbshop-theme .llg-mobile-search{box-sizing:border-box!important;position:relative!important;display:grid!important;grid-template-columns:118px minmax(0,1fr) 46px!important;align-items:stretch!important;width:calc(100% - 24px)!important;height:46px!important;min-height:46px!important;margin:0 12px 10px!important;padding:0!important;border:1px solid #d7e0e5!important;border-radius:8px!important;background:#fff!important;overflow:visible!important;box-shadow:none!important}
      body.wpbbshop-theme .llg-mobile-search-cat{height:44px!important;min-width:0!important;border:0!important;border-right:1px solid #e1e7eb!important;background:#f8fafb!important;border-radius:7px 0 0 7px!important;overflow:hidden!important}
      body.wpbbshop-theme .llg-mobile-search-cat select{display:block!important;width:100%!important;height:44px!important;margin:0!important;padding:0 25px 0 9px!important;border:0!important;border-radius:0!important;background:transparent!important;box-shadow:none!important;color:#183346!important;font-size:10px!important;line-height:44px!important}
      body.wpbbshop-theme .llg-mobile-search-field{position:static!important;height:44px!important;min-width:0!important;margin:0!important;padding:0!important}
      body.wpbbshop-theme .llg-mobile-search input[type=search]{display:block!important;width:100%!important;height:44px!important;min-height:44px!important;margin:0!important;padding:0 10px!important;border:0!important;border-radius:0!important;background:#fff!important;box-shadow:none!important;outline:0!important;color:#173348!important;font-size:11px!important;line-height:44px!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{position:relative!important;inset:auto!important;display:grid!important;place-items:center!important;width:46px!important;min-width:46px!important;max-width:46px!important;height:44px!important;min-height:44px!important;margin:0!important;padding:0!important;border:0!important;border-left:1px solid #278a45!important;border-radius:0 7px 7px 0!important;background:#2f9d50!important;background-image:none!important;color:#fff!important;box-shadow:none!important;transform:none!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit:before,
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit:after{content:none!important;display:none!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit .wpbbshop-green-icon{display:block!important;width:19px!important;height:19px!important;margin:0!important;color:#fff!important}
      body.wpbbshop-theme .llg-mobile-search .wpbbshop-search-results{top:52px!important;left:0!important;right:0!important;width:auto!important;max-height:60vh!important;overflow:auto!important;z-index:1000!important}

      body.wpbbshop-theme .llg-mobile-actions{box-sizing:border-box!important;display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:6px!important;width:100%!important;margin:0!important;padding:0 12px 12px!important}
      body.wpbbshop-theme .llg-mobile-actions>a,
      body.wpbbshop-theme .llg-mobile-actions>button{box-sizing:border-box!important;position:relative!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;width:100%!important;min-width:0!important;height:56px!important;min-height:56px!important;margin:0!important;padding:4px 1px!important;border:1px solid #e2e9ed!important;border-radius:8px!important;background:#fff!important;color:#0d293d!important;box-shadow:none!important;text-decoration:none!important;gap:2px!important}
      body.wpbbshop-theme .llg-mobile-actions .wpbbshop-green-icon{display:block!important;width:20px!important;height:20px!important;margin:0!important}
      body.wpbbshop-theme .llg-mobile-actions span{display:block!important;font-size:9px!important;font-weight:800!important;line-height:1!important;white-space:nowrap!important;text-align:center!important}
      body.wpbbshop-theme .llg-mobile-actions em{position:absolute!important;top:3px!important;right:5px!important;min-width:16px!important;height:16px!important;padding:0 4px!important;border-radius:999px!important;background:#26994b!important;color:#fff!important;font-style:normal!important;font-size:8px!important;font-weight:800!important;line-height:16px!important;text-align:center!important}

      body.wpbbshop-theme .llg-mobile-navbar{box-sizing:border-box!important;display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;width:100%!important;height:48px!important;margin:0!important;padding:0!important;background:#10364f!important}
      body.wpbbshop-theme .llg-mobile-catalog-wrap{position:relative!important;min-width:0!important;height:48px!important}
      body.wpbbshop-theme .llg-mobile-catalog-button,
      body.wpbbshop-theme .llg-mobile-menu-toggle{box-sizing:border-box!important;display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;min-width:0!important;height:48px!important;min-height:48px!important;margin:0!important;padding:0 10px!important;border:0!important;border-radius:0!important;color:#fff!important;gap:7px!important;font-size:11px!important;font-weight:850!important;line-height:1!important}
      body.wpbbshop-theme .llg-mobile-catalog-button{background:#2f9d50!important}
      body.wpbbshop-theme .llg-mobile-menu-toggle{background:#10364f!important}
      body.wpbbshop-theme .llg-mobile-catalog-button .wpbbshop-green-icon,
      body.wpbbshop-theme .llg-mobile-menu-toggle .wpbbshop-green-icon{width:18px!important;height:18px!important;color:#fff!important}
      body.wpbbshop-theme .llg-mobile-catalog-menu{top:48px!important;left:0!important;width:min(310px,100vw)!important;max-height:65vh!important;overflow:auto!important;z-index:1000!important}
    }

    @media (max-width:520px){
      body.wpbbshop-theme .llg-mobile-brand{min-height:84px!important;padding-top:10px!important}
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-logo,
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-mobile-brand img{width:158px!important;max-width:158px!important;max-height:68px!important}
      body.wpbbshop-theme .llg-mobile-search{grid-template-columns:100px minmax(0,1fr) 44px!important;width:calc(100% - 16px)!important;margin-left:8px!important;margin-right:8px!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{width:44px!important;min-width:44px!important;max-width:44px!important}
      body.wpbbshop-theme .llg-mobile-actions{gap:4px!important;padding-left:8px!important;padding-right:8px!important}
      body.wpbbshop-theme .llg-mobile-actions>a,
      body.wpbbshop-theme .llg-mobile-actions>button{height:54px!important;min-height:54px!important}
      body.wpbbshop-theme .llg-mobile-actions span{font-size:8px!important}
      body.wpbbshop-theme .llg-mobile-actions .wpbbshop-green-icon{width:19px!important;height:19px!important}
      body.wpbbshop-theme .llg-section-head{align-items:center!important;gap:10px!important}
      body.wpbbshop-theme .llg-section-head h2{font-size:22px!important}
      body.wpbbshop-theme .llg-section-head>a{font-size:11px!important;gap:4px!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v321_header_css', PHP_INT_MAX);
