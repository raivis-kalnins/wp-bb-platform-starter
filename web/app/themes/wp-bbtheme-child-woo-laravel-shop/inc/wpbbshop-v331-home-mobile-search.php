<?php
/**
 * WP BB Home & Garden 3.0.31
 * Homepage mobile search-button hardening.
 *
 * The front page can be served from an older full-page cache that predates the
 * ll29 helper class. These rules intentionally target the stable mobile-header
 * structure instead of a versioned helper class, so homepage and inner pages
 * render the same green submit button even when legacy header markup is present.
 */
defined('ABSPATH') || exit;

function wpbbshop_v331_mobile_search_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v331-home-mobile-search-css">
    @media (max-width:1049px){
      html #wpbbshop-mobile-header form.llg-mobile-search,
      html #wpbbshop-mobile-header form.wpbbshop-ajax-search.llg-mobile-search{
        position:relative!important;
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:stretch!important;
      }
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"],
      html #wpbbshop-mobile-header form.llg-mobile-search>.llg-search-submit,
      html #wpbbshop-mobile-header form.llg-mobile-search>.ll31-mobile-search-submit{
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
        background-color:#2f9d50!important;
        background-image:none!important;
        color:#fff!important;
        box-shadow:none!important;
        text-shadow:none!important;
        transform:none!important;
        opacity:1!important;
        -webkit-appearance:none!important;
        appearance:none!important;
        overflow:hidden!important;
      }
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:hover,
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:focus-visible{
        background:#278a45!important;
        background-color:#278a45!important;
        color:#fff!important;
      }
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:before,
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]:after{
        content:none!important;
        display:none!important;
      }
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"] .wpbbshop-green-icon,
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"] svg{
        position:static!important;
        display:block!important;
        width:19px!important;
        height:19px!important;
        margin:0!important;
        color:#fff!important;
        stroke:currentColor!important;
        opacity:1!important;
        visibility:visible!important;
        transform:none!important;
      }
    }
    @media (max-width:390px){
      html #wpbbshop-mobile-header form.llg-mobile-search>button[type="submit"]{
        flex-basis:42px!important;
        width:42px!important;
        min-width:42px!important;
        max-width:42px!important;
      }
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v331_mobile_search_css', PHP_INT_MAX);

/**
 * Apply the critical properties directly as inline !important declarations too.
 * This handles homepage-only legacy/plugin rules with unusually high specificity.
 */
function wpbbshop_v331_mobile_search_runtime_guard() {
    if (is_admin()) { return; }
    ?>
    <script id="wpbbshop-v331-home-mobile-search-js">
    (function(){
      function fixMobileSearch(){
        var buttons=document.querySelectorAll('#wpbbshop-mobile-header form.llg-mobile-search > button[type="submit"]');
        for(var i=0;i<buttons.length;i++){
          var b=buttons[i];
          b.classList.add('ll31-mobile-search-submit');
          b.style.setProperty('position','relative','important');
          b.style.setProperty('inset','auto','important');
          b.style.setProperty('display','grid','important');
          b.style.setProperty('place-items','center','important');
          b.style.setProperty('background','#2f9d50','important');
          b.style.setProperty('background-color','#2f9d50','important');
          b.style.setProperty('background-image','none','important');
          b.style.setProperty('color','#fff','important');
          b.style.setProperty('border-left','1px solid #278a45','important');
          b.style.setProperty('box-shadow','none','important');
          b.style.setProperty('opacity','1','important');
          var icon=b.querySelector('.wpbbshop-green-icon,svg');
          if(icon){
            icon.style.setProperty('display','block','important');
            icon.style.setProperty('color','#fff','important');
            icon.style.setProperty('opacity','1','important');
            icon.style.setProperty('visibility','visible','important');
          }
        }
      }
      if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded',fixMobileSearch,{once:true});
      }else{
        fixMobileSearch();
      }
      window.addEventListener('pageshow',fixMobileSearch);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'wpbbshop_v331_mobile_search_runtime_guard', PHP_INT_MAX);

/**
 * Clear common WordPress/page-cache layers once after the update. The homepage
 * was the only URL observed retaining the older search-button presentation.
 */
function wpbbshop_v331_cache_purge_once() {
    if (!is_admin() || !current_user_can('manage_options')) { return; }
    if ((string) get_option('wpbbshop_v331_cache_purged', '') === '3.0.31') { return; }

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
    update_option('wpbbshop_v331_cache_purged', '3.0.31', false);
}
add_action('admin_init', 'wpbbshop_v331_cache_purge_once', 1015);
