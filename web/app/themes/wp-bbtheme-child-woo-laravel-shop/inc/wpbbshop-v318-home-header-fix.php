<?php
/**
 * WP BB Home & Garden 3.0.18
 * Homepage-only desktop header alignment repair.
 */
defined('ABSPATH') || exit;

function wpbbshop_v318_home_header_css() {
    ?>
    <style id="wpbbshop-v318-home-header-css">
    @media (min-width:821px){
      body.home.wpbbshop-theme .llg-mainbar-preview,
      body.page-template-front-page.wpbbshop-theme .llg-mainbar-preview{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:240px minmax(0,1fr) 352px!important;
        align-items:center!important;
        column-gap:24px!important;
        row-gap:0!important;
        min-height:0!important;
        height:auto!important;
        padding:12px 0 10px!important;
      }
      body.home.wpbbshop-theme .llg-mainbar-preview>* ,
      body.page-template-front-page.wpbbshop-theme .llg-mainbar-preview>*{
        min-width:0!important;
      }
      body.home.wpbbshop-theme .llg-logo,
      body.page-template-front-page.wpbbshop-theme .llg-logo{
        align-self:center!important;
        justify-self:start!important;
        margin:0!important;
      }
      body.home.wpbbshop-theme .llg-logo .wpbbshop-logo,
      body.page-template-front-page.wpbbshop-theme .llg-logo .wpbbshop-logo{
        width:220px!important;
        max-width:220px!important;
      }
      body.home.wpbbshop-theme .llg-search-column,
      body.page-template-front-page.wpbbshop-theme .llg-search-column{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-rows:50px 20px!important;
        align-content:center!important;
        align-self:center!important;
        justify-self:stretch!important;
        gap:6px!important;
        width:100%!important;
        margin:0!important;
        padding:0!important;
      }
      body.home.wpbbshop-theme .llg-search-extended,
      body.page-template-front-page.wpbbshop-theme .llg-search-extended{
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
      body.home.wpbbshop-theme .llg-search-select-wrap,
      body.page-template-front-page.wpbbshop-theme .llg-search-select-wrap{
        box-sizing:border-box!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        border-right:1px solid #e1e7eb!important;
        border-radius:8px 0 0 8px!important;
        background:#f8fafb!important;
        overflow:hidden!important;
      }
      body.home.wpbbshop-theme .llg-search-select-wrap select,
      body.page-template-front-page.wpbbshop-theme .llg-search-select-wrap select{
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
      body.home.wpbbshop-theme .llg-search-field-wrap,
      body.page-template-front-page.wpbbshop-theme .llg-search-field-wrap{
        box-sizing:border-box!important;
        position:relative!important;
        min-width:0!important;
        height:48px!important;
        min-height:48px!important;
        margin:0!important;
        padding:0!important;
      }
      body.home.wpbbshop-theme .llg-search-field-wrap input[type=search],
      body.page-template-front-page.wpbbshop-theme .llg-search-field-wrap input[type=search]{
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
      body.home.wpbbshop-theme .llg-search-submit,
      body.home.wpbbshop-theme .llg-search-extended>button,
      body.page-template-front-page.wpbbshop-theme .llg-search-submit,
      body.page-template-front-page.wpbbshop-theme .llg-search-extended>button{
        box-sizing:border-box!important;
        position:relative!important;
        inset:auto!important;
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
        color:#fff!important;
        box-shadow:none!important;
        transform:none!important;
        display:grid!important;
        place-items:center!important;
        line-height:1!important;
        overflow:hidden!important;
      }
      body.home.wpbbshop-theme .llg-search-submit .wpbbshop-green-icon,
      body.page-template-front-page.wpbbshop-theme .llg-search-submit .wpbbshop-green-icon{
        display:block!important;
        position:static!important;
        width:21px!important;
        height:21px!important;
        margin:0!important;
        color:#fff!important;
        stroke:currentColor!important;
        transform:none!important;
      }
      body.home.wpbbshop-theme .llg-search-submit:before,
      body.home.wpbbshop-theme .llg-search-submit:after,
      body.page-template-front-page.wpbbshop-theme .llg-search-submit:before,
      body.page-template-front-page.wpbbshop-theme .llg-search-submit:after{
        content:none!important;
        display:none!important;
      }
      body.home.wpbbshop-theme .llg-popular-searches,
      body.page-template-front-page.wpbbshop-theme .llg-popular-searches{
        box-sizing:border-box!important;
        display:flex!important;
        align-items:center!important;
        gap:12px!important;
        width:100%!important;
        min-height:20px!important;
        height:20px!important;
        margin:0!important;
        padding:0 4px!important;
        overflow:hidden!important;
        white-space:nowrap!important;
        line-height:20px!important;
      }
      body.home.wpbbshop-theme .llg-actions,
      body.page-template-front-page.wpbbshop-theme .llg-actions{
        box-sizing:border-box!important;
        width:352px!important;
        min-width:352px!important;
        height:76px!important;
        min-height:76px!important;
        margin:0!important;
        align-self:center!important;
        justify-self:end!important;
        align-items:center!important;
        justify-content:flex-end!important;
        gap:4px!important;
      }
      body.home.wpbbshop-theme .llg-actions>a,
      body.home.wpbbshop-theme .llg-actions>button,
      body.page-template-front-page.wpbbshop-theme .llg-actions>a,
      body.page-template-front-page.wpbbshop-theme .llg-actions>button{
        box-sizing:border-box!important;
        height:70px!important;
        min-height:70px!important;
        margin:0!important;
        padding:4px!important;
        justify-content:center!important;
      }
      body.home.wpbbshop-theme .llg-header .wpbbshop-search-results,
      body.page-template-front-page.wpbbshop-theme .llg-header .wpbbshop-search-results{
        top:54px!important;
        left:-165px!important;
        right:-53px!important;
      }
    }

    @media (min-width:821px) and (max-width:1280px){
      body.home.wpbbshop-theme .llg-mainbar-preview,
      body.page-template-front-page.wpbbshop-theme .llg-mainbar-preview{
        grid-template-columns:205px minmax(0,1fr) 320px!important;
        column-gap:16px!important;
      }
      body.home.wpbbshop-theme .llg-logo .wpbbshop-logo,
      body.page-template-front-page.wpbbshop-theme .llg-logo .wpbbshop-logo{
        width:198px!important;
        max-width:198px!important;
      }
      body.home.wpbbshop-theme .llg-actions,
      body.page-template-front-page.wpbbshop-theme .llg-actions{
        width:320px!important;
        min-width:320px!important;
      }
      body.home.wpbbshop-theme .llg-actions>a,
      body.home.wpbbshop-theme .llg-actions>button,
      body.page-template-front-page.wpbbshop-theme .llg-actions>a,
      body.page-template-front-page.wpbbshop-theme .llg-actions>button{
        flex:0 0 76px!important;
        width:76px!important;
        min-width:76px!important;
      }
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v318_home_header_css', PHP_INT_MAX);
