<?php
/**
 * WP BB Home & Garden 3.0.30
 * Mobile header consistency + category drawer containment.
 *
 * v3.0.29 intentionally disabled several older mobile presentation layers,
 * but those layers also contained the only complete rules for the mobile
 * category drawer. This final layer restores only the required drawer rules
 * and hardens the mobile search so the homepage cannot inherit the older
 * grey search-button treatment from v3.0.13/v3.0.15.
 */
defined('ABSPATH') || exit;

function wpbbshop_v330_mobile_header_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v330-mobile-header-css">
    @media (max-width:1049px){
      /* Keep the mobile header identical on the homepage and inner pages. */
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search{
        position:relative!important;
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:stretch!important;
        width:calc(100% - 16px)!important;
        height:46px!important;
        min-height:46px!important;
        margin:0 8px 8px!important;
        padding:0!important;
        border:1px solid #d7e0e5!important;
        border-radius:9px!important;
        background:#fff!important;
        box-shadow:none!important;
        overflow:visible!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-mobile-search-cat{
        position:relative!important;
        flex:0 0 106px!important;
        width:106px!important;
        min-width:0!important;
        height:44px!important;
        margin:0!important;
        padding:0!important;
        border:0!important;
        border-right:1px solid #e1e7eb!important;
        border-radius:8px 0 0 8px!important;
        background:#f8fafb!important;
        overflow:hidden!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-mobile-search-field{
        position:relative!important;
        display:block!important;
        flex:1 1 auto!important;
        min-width:0!important;
        width:auto!important;
        height:44px!important;
        margin:0!important;
        padding:0!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-mobile-search-field input[type=search]{
        position:static!important;
        display:block!important;
        width:100%!important;
        height:44px!important;
        min-height:44px!important;
        margin:0!important;
        padding:0 10px!important;
        border:0!important;
        border-radius:0!important;
        background:#fff!important;
        color:#173348!important;
        box-shadow:none!important;
        outline:0!important;
        font-size:11px!important;
        line-height:44px!important;
        -webkit-appearance:none!important;
        appearance:none!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit,
      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit{
        position:relative!important;
        inset:auto!important;
        top:auto!important;
        right:auto!important;
        bottom:auto!important;
        left:auto!important;
        flex:0 0 46px!important;
        display:grid!important;
        place-items:center!important;
        width:46px!important;
        min-width:46px!important;
        max-width:46px!important;
        height:44px!important;
        min-height:44px!important;
        max-height:44px!important;
        margin:0!important;
        padding:0!important;
        border:0!important;
        border-left:1px solid #278a45!important;
        border-radius:0 8px 8px 0!important;
        background:#2f9d50!important;
        background-image:none!important;
        color:#fff!important;
        box-shadow:none!important;
        text-shadow:none!important;
        transform:none!important;
        -webkit-appearance:none!important;
        appearance:none!important;
        overflow:hidden!important;
        cursor:pointer!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:hover,
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:focus-visible{
        background:#278a45!important;
        color:#fff!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:before,
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:after{
        content:none!important;
        display:none!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit .wpbbshop-green-icon{
        display:block!important;
        position:static!important;
        width:19px!important;
        height:19px!important;
        margin:0!important;
        color:#fff!important;
        stroke:currentColor!important;
        opacity:1!important;
        transform:none!important;
      }

      /* Category drawer: hidden unless explicitly opened, and contained below the green button. */
      html body.wpbbshop-theme #wpbbshop-mobile-header .ll29-mobile-navbar{
        position:relative!important;
        z-index:500!important;
        overflow:visible!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-wrap{
        position:relative!important;
        z-index:502!important;
        height:48px!important;
        min-height:48px!important;
        overflow:visible!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu{
        display:none!important;
        position:absolute!important;
        top:48px!important;
        left:0!important;
        right:auto!important;
        width:min(330px,calc(100vw - 8px))!important;
        max-width:calc(100vw - 8px)!important;
        max-height:min(66vh,520px)!important;
        margin:0!important;
        padding:7px!important;
        overflow-x:hidden!important;
        overflow-y:auto!important;
        border:1px solid #dfe5e9!important;
        border-top:0!important;
        border-radius:0 0 9px 0!important;
        background:#fff!important;
        color:#173348!important;
        box-shadow:0 18px 38px rgba(13,41,61,.20)!important;
        opacity:1!important;
        visibility:visible!important;
        transform:none!important;
        z-index:503!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-wrap.is-open>.llg-mobile-catalog-menu{
        display:block!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-panel-item{
        position:relative!important;
        display:flex!important;
        align-items:center!important;
        gap:10px!important;
        width:100%!important;
        min-height:44px!important;
        margin:0!important;
        padding:8px 10px!important;
        border:0!important;
        border-bottom:1px solid #edf1f4!important;
        border-radius:6px!important;
        background:#fff!important;
        color:#263849!important;
        font-size:12px!important;
        font-weight:700!important;
        line-height:1.25!important;
        text-decoration:none!important;
        box-sizing:border-box!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-panel-item:last-child{
        border-bottom:0!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-panel-item:hover,
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-panel-item:focus-visible{
        background:#f2f8f3!important;
        color:#237e40!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-svg,
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-svg svg,
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-thumb{
        position:static!important;
        flex:0 0 22px!important;
        display:block!important;
        width:22px!important;
        height:22px!important;
        max-width:22px!important;
        max-height:22px!important;
        margin:0!important;
        object-fit:contain!important;
        color:#237e40!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu .wpbbshop-cat-panel-item>span:last-child{
        display:block!important;
        min-width:0!important;
        margin:0!important;
      }
    }

    @media (max-width:390px){
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-mobile-search-cat{
        flex-basis:94px!important;
        width:94px!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit{
        flex-basis:42px!important;
        width:42px!important;
        min-width:42px!important;
        max-width:42px!important;
      }
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v330_mobile_header_css', PHP_INT_MAX);

/**
 * A footer copy protects the critical mobile rules from late plugin styles and
 * stale front-page CSS that may be printed after wp_head().
 */
function wpbbshop_v330_mobile_header_footer_guard() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v330-mobile-header-footer-guard">
    @media(max-width:1049px){
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit{
        position:relative!important;inset:auto!important;display:grid!important;place-items:center!important;
        background:#2f9d50!important;background-image:none!important;color:#fff!important;border-left:1px solid #278a45!important;
      }
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:before,
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit:after{content:none!important;display:none!important}
      html body.wpbbshop-theme #wpbbshop-mobile-header form.ll29-mobile-search>.llg-search-submit .wpbbshop-green-icon{display:block!important;color:#fff!important;opacity:1!important}
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-wrap{position:relative!important;overflow:visible!important}
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-menu{display:none!important;position:absolute!important;top:48px!important;left:0!important}
      html body.wpbbshop-theme #wpbbshop-mobile-header .llg-mobile-catalog-wrap.is-open>.llg-mobile-catalog-menu{display:block!important}
    }
    </style>
    <?php
}
add_action('wp_footer', 'wpbbshop_v330_mobile_header_footer_guard', PHP_INT_MAX);

/** Close a stale/open drawer when switching breakpoints and keep ARIA in sync. */
function wpbbshop_v330_mobile_header_js() {
    if (is_admin()) { return; }
    ?>
    <script id="wpbbshop-v330-mobile-header-js">
    (function(){
      function closeCatalog(){
        var wrap=document.querySelector('#wpbbshop-mobile-header .llg-mobile-catalog-wrap');
        if(!wrap)return;
        wrap.classList.remove('is-open');
        var btn=wrap.querySelector('.llg-mobile-catalog-button');
        if(btn)btn.setAttribute('aria-expanded','false');
      }
      if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded',function(){
          closeCatalog();
          window.addEventListener('resize',function(){if(window.innerWidth>=1050)closeCatalog();},{passive:true});
        },{once:true});
      }else{
        closeCatalog();
        window.addEventListener('resize',function(){if(window.innerWidth>=1050)closeCatalog();},{passive:true});
      }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'wpbbshop_v330_mobile_header_js', PHP_INT_MAX);

/** Purge common caches once after upgrading to 3.0.30 (homepage cache included). */
function wpbbshop_v330_cache_purge_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    if ((string) get_option('wpbbshop_v330_cache_purged', '') === '3.0.30') { return; }
    if (function_exists('wp_theme_purge_all_theme_cache')) { wp_theme_purge_all_theme_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    delete_transient('wc_products_onsale');
    update_option('wpbbshop_v330_cache_purged', '3.0.30', false);
}
add_action('admin_init', 'wpbbshop_v330_cache_purge_once', 1010);
