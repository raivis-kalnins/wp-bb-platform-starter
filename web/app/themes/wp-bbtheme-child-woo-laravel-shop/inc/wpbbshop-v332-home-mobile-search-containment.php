<?php
/**
 * WP BB Home & Garden 3.0.32
 * Homepage responsive mobile-search containment.
 *
 * 3.0.31 corrected the homepage submit button colour, but a cached/legacy
 * homepage can still expose the stable `llg-mobile-search` class without the
 * newer `ll29-mobile-search` helper class. In that case the button is styled
 * correctly while the surrounding search box can retain older geometry.
 *
 * This layer applies the complete, known-good mobile search geometry to the
 * homepage only, using stable selectors. Inner pages are intentionally left
 * untouched because their current responsive search layout is already correct.
 */
defined('ABSPATH') || exit;

function wpbbshop_v332_home_mobile_search_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v332-home-mobile-search-css">
    @media (max-width:1049px){
      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search{
        box-sizing:border-box!important;
        position:relative!important;
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:stretch!important;
        width:calc(100% - 16px)!important;
        max-width:calc(100% - 16px)!important;
        height:46px!important;
        min-height:46px!important;
        max-height:46px!important;
        margin:0 8px 8px!important;
        padding:0!important;
        border:1px solid #d7e0e5!important;
        border-radius:9px!important;
        background:#fff!important;
        box-shadow:none!important;
        overflow:visible!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-cat,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-cat{
        box-sizing:border-box!important;
        position:relative!important;
        flex:0 0 106px!important;
        width:106px!important;
        min-width:0!important;
        max-width:106px!important;
        height:44px!important;
        min-height:44px!important;
        max-height:44px!important;
        margin:0!important;
        padding:0!important;
        border:0!important;
        border-right:1px solid #e1e7eb!important;
        border-radius:8px 0 0 8px!important;
        background:#f8fafb!important;
        overflow:hidden!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-cat select,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-cat select{
        box-sizing:border-box!important;
        display:block!important;
        width:100%!important;
        height:44px!important;
        min-height:44px!important;
        max-height:44px!important;
        margin:0!important;
        border:0!important;
        border-radius:0!important;
        box-shadow:none!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-field,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-field{
        box-sizing:border-box!important;
        position:relative!important;
        display:block!important;
        flex:1 1 auto!important;
        width:auto!important;
        min-width:0!important;
        max-width:none!important;
        height:44px!important;
        min-height:44px!important;
        max-height:44px!important;
        margin:0!important;
        padding:0!important;
        border:0!important;
        overflow:visible!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-field input[type="search"],
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-field input[type="search"]{
        box-sizing:border-box!important;
        position:static!important;
        display:block!important;
        width:100%!important;
        max-width:100%!important;
        height:44px!important;
        min-height:44px!important;
        max-height:44px!important;
        margin:0!important;
        padding:0 10px!important;
        border:0!important;
        border-radius:0!important;
        background:#fff!important;
        box-shadow:none!important;
        outline:0!important;
        line-height:44px!important;
        transform:none!important;
        -webkit-appearance:none!important;
        appearance:none!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"],
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]{
        box-sizing:border-box!important;
        position:relative!important;
        inset:auto!important;
        top:auto!important;
        right:auto!important;
        bottom:auto!important;
        left:auto!important;
        align-self:flex-start!important;
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
        background-color:#2f9d50!important;
        background-image:none!important;
        color:#fff!important;
        box-shadow:none!important;
        line-height:1!important;
        text-shadow:none!important;
        transform:none!important;
        opacity:1!important;
        overflow:hidden!important;
        -webkit-appearance:none!important;
        appearance:none!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:before,
      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:after,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:before,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:after{
        content:none!important;
        display:none!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"] .wpbbshop-green-icon,
      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"] svg,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"] .wpbbshop-green-icon,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"] svg{
        position:static!important;
        display:block!important;
        width:19px!important;
        height:19px!important;
        margin:0!important;
        color:#fff!important;
        stroke:currentColor!important;
        transform:none!important;
      }

      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>input[type="hidden"],
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>input[type="hidden"]{
        display:none!important;
      }
    }

    @media (max-width:390px){
      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-cat,
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>.llg-mobile-search-cat{
        flex-basis:94px!important;
        width:94px!important;
        max-width:94px!important;
      }
      html body.home.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"],
      html body.page-template-front-page.wpbbshop-theme #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]{
        flex-basis:42px!important;
        width:42px!important;
        min-width:42px!important;
        max-width:42px!important;
      }
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v332_home_mobile_search_css', PHP_INT_MAX);

/**
 * Re-apply the exact geometry after the DOM is available. This protects the
 * cached homepage from late plugin styles without changing inner pages.
 */
function wpbbshop_v332_home_mobile_search_runtime_guard() {
    if (is_admin() || !is_front_page()) { return; }
    ?>
    <script id="wpbbshop-v332-home-mobile-search-js">
    (function(){
      function fixHomeSearchBox(){
        if(!window.matchMedia || !window.matchMedia('(max-width:1049px)').matches){return;}
        var form=document.querySelector('#wpbbshop-mobile-header form.llg-mobile-search');
        if(!form){return;}
        var narrow=window.matchMedia('(max-width:390px)').matches;
        var cat=form.querySelector(':scope > .llg-mobile-search-cat');
        var field=form.querySelector(':scope > .llg-mobile-search-field');
        var button=form.querySelector(':scope > button[type="submit"]');

        form.style.setProperty('box-sizing','border-box','important');
        form.style.setProperty('display','flex','important');
        form.style.setProperty('flex-flow','row nowrap','important');
        form.style.setProperty('align-items','stretch','important');
        form.style.setProperty('width','calc(100% - 16px)','important');
        form.style.setProperty('max-width','calc(100% - 16px)','important');
        form.style.setProperty('height','46px','important');
        form.style.setProperty('min-height','46px','important');
        form.style.setProperty('max-height','46px','important');
        form.style.setProperty('margin','0 8px 8px','important');
        form.style.setProperty('padding','0','important');
        form.style.setProperty('border','1px solid #d7e0e5','important');
        form.style.setProperty('border-radius','9px','important');
        form.style.setProperty('background','#fff','important');

        if(cat){
          var catWidth=narrow?'94px':'106px';
          cat.style.setProperty('box-sizing','border-box','important');
          cat.style.setProperty('flex','0 0 '+catWidth,'important');
          cat.style.setProperty('width',catWidth,'important');
          cat.style.setProperty('max-width',catWidth,'important');
          cat.style.setProperty('height','44px','important');
          cat.style.setProperty('min-height','44px','important');
          cat.style.setProperty('max-height','44px','important');
        }
        if(field){
          field.style.setProperty('box-sizing','border-box','important');
          field.style.setProperty('flex','1 1 auto','important');
          field.style.setProperty('width','auto','important');
          field.style.setProperty('min-width','0','important');
          field.style.setProperty('height','44px','important');
          field.style.setProperty('min-height','44px','important');
          field.style.setProperty('max-height','44px','important');
        }
        if(button){
          var buttonWidth=narrow?'42px':'46px';
          button.style.setProperty('box-sizing','border-box','important');
          button.style.setProperty('position','relative','important');
          button.style.setProperty('inset','auto','important');
          button.style.setProperty('align-self','flex-start','important');
          button.style.setProperty('flex','0 0 '+buttonWidth,'important');
          button.style.setProperty('width',buttonWidth,'important');
          button.style.setProperty('min-width',buttonWidth,'important');
          button.style.setProperty('max-width',buttonWidth,'important');
          button.style.setProperty('height','44px','important');
          button.style.setProperty('min-height','44px','important');
          button.style.setProperty('max-height','44px','important');
          button.style.setProperty('margin','0','important');
          button.style.setProperty('padding','0','important');
          button.style.setProperty('transform','none','important');
        }
      }
      if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded',fixHomeSearchBox,{once:true});
      }else{
        fixHomeSearchBox();
      }
      window.addEventListener('pageshow',fixHomeSearchBox);
      window.addEventListener('resize',fixHomeSearchBox);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'wpbbshop_v332_home_mobile_search_runtime_guard', PHP_INT_MAX);

/** Purge common caches once after upgrading to 3.0.32. */
function wpbbshop_v332_cache_purge_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    if ((string) get_option('wpbbshop_v332_cache_purged', '') === '3.0.32') { return; }

    if (function_exists('wp_theme_purge_all_theme_cache')) { wp_theme_purge_all_theme_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    if (function_exists('rocket_clean_domain')) { rocket_clean_domain(); }
    if (function_exists('w3tc_flush_all')) { w3tc_flush_all(); }
    if (function_exists('wp_cache_clear_cache')) { wp_cache_clear_cache(); }
    if (function_exists('sg_cachepress_purge_cache')) { sg_cachepress_purge_cache(); }
    if (class_exists('autoptimizeCache') && is_callable(array('autoptimizeCache', 'clearall'))) { autoptimizeCache::clearall(); }
    if (class_exists('LiteSpeed\\Purge') && is_callable(array('LiteSpeed\\Purge', 'purge_all'))) { LiteSpeed\Purge::purge_all(); }

    delete_transient('wc_products_onsale');
    update_option('wpbbshop_v332_cache_purged', '3.0.32', false);
}
add_action('admin_init', 'wpbbshop_v332_cache_purge_once', 1016);
