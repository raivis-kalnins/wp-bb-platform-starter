<?php
/**
 * WP BB Home & Garden 3.0.20
 * Clean site-wide desktop header geometry.
 *
 * Supersedes the homepage-only 3.0.19 reset. The important rule is that the
 * desktop mainbar owns exactly three non-overlapping tracks: logo, search and
 * one non-wrapping action row. No fixed mainbar height is used.
 */
defined('ABSPATH') || exit;

function wpbbshop_v320_header_clean_css() {
    ?>
    <style id="wpbbshop-v320-header-clean-css">
    @media (min-width:821px){
      body.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:220px minmax(420px,1fr) max-content!important;
        grid-template-rows:auto!important;
        column-gap:28px!important;
        row-gap:0!important;
        align-items:center!important;
        width:min(calc(100% - 28px),1460px)!important;
        min-height:0!important;
        height:auto!important;
        margin:0 auto!important;
        padding:14px 0 12px!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview>*{
        box-sizing:border-box!important;
        min-width:0!important;
        grid-row:1!important;
        margin:0!important;
      }

      body.wpbbshop-theme .llg-desktop-shell .llg-logo{
        grid-column:1!important;
        display:flex!important;
        align-items:center!important;
        justify-content:flex-start!important;
        align-self:center!important;
        width:220px!important;
        height:auto!important;
        min-height:0!important;
        padding:0!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-logo,
      body.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-desktop-shell .llg-logo img{
        display:block!important;
        width:210px!important;
        max-width:210px!important;
        height:auto!important;
        max-height:96px!important;
        margin:0!important;
        object-fit:contain!important;
        object-position:left center!important;
      }

      body.wpbbshop-theme .llg-desktop-shell .llg-search-column{
        grid-column:2!important;
        align-self:center!important;
        justify-self:stretch!important;
        display:flex!important;
        flex-direction:column!important;
        justify-content:center!important;
        gap:6px!important;
        width:100%!important;
        min-width:0!important;
        height:auto!important;
        min-height:0!important;
        padding:0!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:164px minmax(0,1fr) 54px!important;
        align-items:stretch!important;
        width:100%!important;
        height:50px!important;
        min-height:50px!important;
        margin:0!important;
        padding:0!important;
        border:1px solid #d7e0e5!important;
        border-radius:9px!important;
        background:#fff!important;
        box-shadow:none!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap{
        position:relative!important;
        height:48px!important;
        min-height:48px!important;
        border:0!important;
        border-right:1px solid #e0e6ea!important;
        border-radius:8px 0 0 8px!important;
        background:#f8fafb!important;
        overflow:hidden!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap select{
        display:block!important;
        width:100%!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0 32px 0 15px!important;
        border:0!important;
        border-radius:8px 0 0 8px!important;
        background:transparent!important;
        box-shadow:none!important;
        color:#183346!important;
        font-size:13px!important;
        line-height:48px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap:after{
        top:17px!important;
        right:14px!important;
        width:7px!important;
        height:7px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap{
        position:relative!important;
        min-width:0!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap input[type=search]{
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
        color:#263b49!important;
        font-size:13px!important;
        line-height:48px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button{
        box-sizing:border-box!important;
        position:relative!important;
        inset:auto!important;
        display:grid!important;
        place-items:center!important;
        align-self:stretch!important;
        justify-self:stretch!important;
        width:54px!important;
        min-width:54px!important;
        max-width:54px!important;
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
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:hover,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:focus-visible{
        background:#267f43!important;
        color:#fff!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit .wpbbshop-green-icon,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button .wpbbshop-green-icon{
        position:static!important;
        display:block!important;
        width:21px!important;
        height:21px!important;
        margin:0!important;
        color:#fff!important;
        stroke:currentColor!important;
        transform:none!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:before,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit:after{
        content:none!important;
        display:none!important;
      }

      body.wpbbshop-theme .llg-desktop-shell .llg-popular-searches{
        display:flex!important;
        align-items:center!important;
        flex-wrap:nowrap!important;
        gap:10px!important;
        width:100%!important;
        min-width:0!important;
        min-height:18px!important;
        height:18px!important;
        margin:0!important;
        padding:0 4px!important;
        overflow:hidden!important;
        white-space:nowrap!important;
        color:#788792!important;
        font-size:10.5px!important;
        line-height:18px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-popular-searches strong,
      body.wpbbshop-theme .llg-desktop-shell .llg-popular-searches a{
        display:inline-block!important;
        flex:0 0 auto!important;
        margin:0!important;
        line-height:18px!important;
      }

      body.wpbbshop-theme .llg-desktop-shell .llg-actions{
        grid-column:3!important;
        grid-row:1!important;
        position:static!important;
        display:flex!important;
        flex-direction:row!important;
        flex-wrap:nowrap!important;
        align-items:center!important;
        justify-content:flex-end!important;
        justify-self:end!important;
        align-self:center!important;
        gap:7px!important;
        width:auto!important;
        min-width:0!important;
        max-width:none!important;
        height:auto!important;
        min-height:0!important;
        margin:0!important;
        padding:0!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-actions>a,
      body.wpbbshop-theme .llg-desktop-shell .llg-actions>button{
        box-sizing:border-box!important;
        position:relative!important;
        inset:auto!important;
        flex:0 0 74px!important;
        display:flex!important;
        flex-direction:column!important;
        align-items:center!important;
        justify-content:center!important;
        width:74px!important;
        min-width:74px!important;
        max-width:74px!important;
        height:70px!important;
        min-height:70px!important;
        margin:0!important;
        padding:5px 2px!important;
        gap:3px!important;
        border:0!important;
        border-radius:9px!important;
        background:#fff!important;
        color:#0d293d!important;
        transform:none!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-actions>a:hover,
      body.wpbbshop-theme .llg-desktop-shell .llg-actions>button:hover{
        background:#f3f8f4!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-green-icon{
        width:23px!important;
        height:23px!important;
        flex:0 0 auto!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-actions span{
        display:block!important;
        margin:0!important;
        color:#0d293d!important;
        font-size:10.5px!important;
        font-weight:800!important;
        line-height:1.05!important;
        white-space:nowrap!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-actions em{
        top:2px!important;
        right:4px!important;
        min-width:18px!important;
        height:18px!important;
        padding:0 4px!important;
        font-size:9px!important;
        line-height:18px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-cart-total{
        display:block!important;
        max-width:72px!important;
        margin:0!important;
        color:#536571!important;
        font-size:9px!important;
        font-weight:600!important;
        line-height:1!important;
        white-space:nowrap!important;
      }

      body.wpbbshop-theme .llg-desktop-shell .llg-nav-row,
      body.wpbbshop-theme .llg-desktop-shell .llg-nav-inner-preview{
        clear:both!important;
      }
    }

    @media (min-width:821px) and (max-width:1100px){
      body.wpbbshop-theme .llg-desktop-shell .llg-mainbar-preview{
        grid-template-columns:172px minmax(300px,1fr) max-content!important;
        column-gap:14px!important;
        padding:10px 0!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-logo{width:172px!important}
      body.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-logo,
      body.wpbbshop-theme .llg-desktop-shell .llg-logo .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-desktop-shell .llg-logo img{
        width:164px!important;
        max-width:164px!important;
        max-height:82px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended{
        grid-template-columns:132px minmax(0,1fr) 48px!important;
        height:46px!important;
        min-height:46px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap select,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap input[type=search],
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button{
        height:44px!important;
        min-height:44px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-submit,
      body.wpbbshop-theme .llg-desktop-shell .llg-search-extended>button{
        width:48px!important;
        min-width:48px!important;
        max-width:48px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-search-select-wrap select{font-size:11px!important;padding-left:10px!important}
      body.wpbbshop-theme .llg-desktop-shell .llg-search-field-wrap input[type=search]{font-size:11px!important;padding:0 10px!important}
      body.wpbbshop-theme .llg-desktop-shell .llg-popular-searches{display:none!important}
      body.wpbbshop-theme .llg-desktop-shell .llg-actions{gap:2px!important}
      body.wpbbshop-theme .llg-desktop-shell .llg-actions>a,
      body.wpbbshop-theme .llg-desktop-shell .llg-actions>button{
        flex-basis:60px!important;
        width:60px!important;
        min-width:60px!important;
        max-width:60px!important;
        height:60px!important;
        min-height:60px!important;
      }
      body.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-green-icon{width:20px!important;height:20px!important}
      body.wpbbshop-theme .llg-desktop-shell .llg-actions span{font-size:9px!important}
      body.wpbbshop-theme .llg-desktop-shell .llg-actions .wpbbshop-cart-total{display:none!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v320_header_clean_css', PHP_INT_MAX);
