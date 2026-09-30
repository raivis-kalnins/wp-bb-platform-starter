<?php
/**
 * WP BB Home & Garden 3.0.19
 * Homepage desktop header reset.
 *
 * Replaces the accumulated front-page header overrides with a single compact
 * geometry. The important part here is that the mainbar height is derived from
 * the logo/search content instead of the old 132/136px minimums.
 */
defined('ABSPATH') || exit;

function wpbbshop_v319_home_header_css() {
    ?>
    <style id="wpbbshop-v319-home-header-css">
    @media (min-width:821px){
      body.home.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:224px minmax(0,1fr) 340px!important;
        column-gap:24px!important;
        row-gap:0!important;
        align-items:center!important;
        width:min(calc(100% - 28px),1460px)!important;
        min-height:0!important;
        height:116px!important;
        margin-left:auto!important;
        margin-right:auto!important;
        padding:10px 0!important;
      }

      body.home.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview>*,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview>*{
        min-width:0!important;
        margin-top:0!important;
        margin-bottom:0!important;
      }

      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo{
        display:flex!important;
        align-items:center!important;
        justify-content:flex-start!important;
        align-self:center!important;
        height:96px!important;
        margin:0!important;
        padding:0!important;
        overflow:hidden!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-logo,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-approved-logo,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-logo,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-approved-logo{
        display:block!important;
        width:208px!important;
        max-width:208px!important;
        height:auto!important;
        margin:0!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo img,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo img{
        display:block!important;
        width:208px!important;
        max-width:208px!important;
        max-height:96px!important;
        height:auto!important;
        object-fit:contain!important;
        object-position:left center!important;
      }

      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-column,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-column{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-rows:50px auto!important;
        align-content:center!important;
        align-self:center!important;
        gap:7px!important;
        width:100%!important;
        height:78px!important;
        min-height:78px!important;
        margin:0!important;
        padding:0!important;
        overflow:visible!important;
      }

      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-extended,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-extended{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:164px minmax(0,1fr) 54px!important;
        align-items:center!important;
        width:100%!important;
        height:50px!important;
        min-height:50px!important;
        margin:0!important;
        padding:0!important;
        border:1px solid #d4dde3!important;
        border-radius:9px!important;
        background:#fff!important;
        overflow:visible!important;
        box-shadow:none!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap{
        box-sizing:border-box!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        border-right:1px solid #e1e7eb!important;
        border-radius:8px 0 0 8px!important;
        background:#f8fafb!important;
        overflow:hidden!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap select,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap select{
        box-sizing:border-box!important;
        width:100%!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        border:0!important;
        border-radius:8px 0 0 8px!important;
        background:transparent!important;
        box-shadow:none!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap:after,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap:after{
        top:16px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap{
        box-sizing:border-box!important;
        position:relative!important;
        min-width:0!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap input[type=search],
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap input[type=search]{
        box-sizing:border-box!important;
        display:block!important;
        width:100%!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0 16px!important;
        border:0!important;
        border-radius:0!important;
        outline:0!important;
        background:#fff!important;
        box-shadow:none!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button{
        box-sizing:border-box!important;
        position:relative!important;
        inset:auto!important;
        display:grid!important;
        place-items:center!important;
        align-self:center!important;
        justify-self:end!important;
        width:53px!important;
        min-width:53px!important;
        max-width:53px!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0!important;
        border:0!important;
        border-left:1px solid #278a45!important;
        border-radius:0 8px 8px 0!important;
        background:#2f9d50!important;
        background-image:none!important;
        color:#fff!important;
        line-height:1!important;
        box-shadow:none!important;
        transform:none!important;
        overflow:hidden!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-submit .wpbbshop-green-icon,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-submit .wpbbshop-green-icon{
        display:block!important;
        position:static!important;
        width:21px!important;
        height:21px!important;
        margin:0!important;
        color:#fff!important;
        stroke:currentColor!important;
        transform:none!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-submit:before,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-submit:after,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-submit:before,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-submit:after{
        content:none!important;
        display:none!important;
      }

      body.home.wpbbshop-theme .llg-desktop-shell .llg-popular-searches,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-popular-searches{
        box-sizing:border-box!important;
        display:flex!important;
        align-items:center!important;
        gap:11px!important;
        width:100%!important;
        min-height:18px!important;
        height:auto!important;
        margin:0!important;
        padding:0 4px!important;
        overflow:visible!important;
        white-space:nowrap!important;
        font-size:11px!important;
        line-height:18px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-popular-searches strong,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-popular-searches a,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-popular-searches strong,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-popular-searches a{
        line-height:18px!important;
        margin:0!important;
      }

      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:repeat(4,78px)!important;
        justify-content:end!important;
        align-items:center!important;
        align-self:center!important;
        width:340px!important;
        min-width:340px!important;
        height:76px!important;
        min-height:76px!important;
        margin:0!important;
        padding:0!important;
        gap:5px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions>a,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions>button,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions>a,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions>button{
        box-sizing:border-box!important;
        width:78px!important;
        min-width:78px!important;
        max-width:78px!important;
        height:68px!important;
        min-height:68px!important;
        margin:0!important;
        padding:4px 2px!important;
        display:flex!important;
        flex-direction:column!important;
        align-items:center!important;
        justify-content:center!important;
        gap:3px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-green-icon,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-green-icon{
        width:23px!important;
        height:23px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions span,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions span{
        font-size:11px!important;
        line-height:1.05!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-cart-total,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-cart-total{
        font-size:9px!important;
        line-height:1!important;
      }

      body.home.wpbbshop-theme .llg-header .wpbbshop-search-results,
      body.page-template-front-page.wpbbshop-theme .llg-header .wpbbshop-search-results{
        top:54px!important;
        left:-165px!important;
        right:-53px!important;
      }
    }

    @media (min-width:821px) and (max-width:1280px){
      body.home.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview{
        grid-template-columns:188px minmax(0,1fr) 300px!important;
        column-gap:16px!important;
        height:108px!important;
        padding:8px 0!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo{height:88px!important}
      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-logo,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-approved-logo,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-logo img,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-logo,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-approved-logo,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-logo img{
        width:184px!important;
        max-width:184px!important;
        max-height:88px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-extended,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-extended{
        grid-template-columns:145px minmax(0,1fr) 50px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button{
        width:49px!important;
        min-width:49px!important;
        max-width:49px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions{
        grid-template-columns:repeat(4,70px)!important;
        width:300px!important;
        min-width:300px!important;
        gap:4px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions>a,
      body.home.wpbbshop-theme .llg-desktop-shell .llg-actions>button,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions>a,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-actions>button{
        width:70px!important;
        min-width:70px!important;
        max-width:70px!important;
      }
      body.home.wpbbshop-theme .llg-desktop-shell .llg-popular-searches,
      body.page-template-front-page.wpbbshop-theme .llg-desktop-shell .llg-popular-searches{
        gap:8px!important;
        font-size:10px!important;
      }
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v319_home_header_css', PHP_INT_MAX);
