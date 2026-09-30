<?php
/**
 * v3.0.11 — mobile search + action-row hard fix.
 *
 * This layer is intentionally loaded after every earlier header/mobile layer.
 * It avoids parent-theme/button rendering differences by drawing the search
 * magnifier in CSS and positioning the submit button absolutely.
 */
defined('ABSPATH') || exit;

function wpbbshop_v311_mobile_search_alignment_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v311-mobile-search-alignment-css">
    /* Search button: do not allow parent/browser button rules to compress it. */
    body.wpbbshop-theme .llg-search-extended,
    body.wpbbshop-theme .llg-mobile-search{
      position:relative!important;
      overflow:visible!important;
    }

    body.wpbbshop-theme .llg-search-extended{
      grid-template-columns:180px minmax(0,1fr)!important;
      padding-right:58px!important;
    }
    body.wpbbshop-theme .llg-search-extended > .llg-search-submit{
      position:absolute!important;
      top:0!important;
      right:0!important;
      bottom:0!important;
      left:auto!important;
      width:58px!important;
      min-width:58px!important;
      max-width:58px!important;
      height:52px!important;
      min-height:52px!important;
      padding:0!important;
      margin:0!important;
      border:0!important;
      border-radius:0 8px 8px 0!important;
      background:#2e994e!important;
      color:#fff!important;
      box-shadow:none!important;
      appearance:none!important;
      -webkit-appearance:none!important;
      display:block!important;
      transform:none!important;
      line-height:1!important;
      z-index:8!important;
    }

    /* Stable magnifier drawn in CSS; independent of SVG/plugin styles. */
    body.wpbbshop-theme .llg-search-submit .wpbbshop-green-icon{display:none!important}
    body.wpbbshop-theme .llg-search-submit:before{
      content:""!important;
      position:absolute!important;
      width:15px!important;
      height:15px!important;
      border:2px solid #fff!important;
      border-radius:50%!important;
      left:50%!important;
      top:50%!important;
      transform:translate(-58%,-58%)!important;
      box-sizing:border-box!important;
    }
    body.wpbbshop-theme .llg-search-submit:after{
      content:""!important;
      position:absolute!important;
      width:8px!important;
      height:2px!important;
      border:0!important;
      border-radius:2px!important;
      background:#fff!important;
      left:50%!important;
      top:50%!important;
      transform:translate(4px,5px) rotate(45deg)!important;
      transform-origin:left center!important;
    }
    body.wpbbshop-theme .llg-search-submit:hover,
    body.wpbbshop-theme .llg-search-submit:focus-visible{background:#237b3e!important}
    body.wpbbshop-theme .llg-search-submit:focus-visible{outline:3px solid rgba(46,153,78,.22)!important;outline-offset:2px!important}

    @media(max-width:820px){
      /* Keep mobile search as one compact line: category | query | green button. */
      body.wpbbshop-theme .llg-mobile-shell{width:100%!important;max-width:100%!important;overflow:visible!important}
      body.wpbbshop-theme .llg-mobile-search{
        box-sizing:border-box!important;
        display:grid!important;
        grid-template-columns:112px minmax(0,1fr)!important;
        width:calc(100% - 16px)!important;
        max-width:none!important;
        height:46px!important;
        min-height:46px!important;
        margin:0 8px 10px!important;
        padding:0 46px 0 0!important;
        border:1px solid #d6e0e6!important;
        border-radius:9px!important;
        background:#fff!important;
        box-shadow:0 1px 2px rgba(13,41,61,.03)!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-mobile-search-cat{height:44px!important;min-width:0!important;border-right:1px solid #dfe6ea!important}
      body.wpbbshop-theme .llg-mobile-search-cat select{height:44px!important;font-size:11px!important;padding-left:10px!important}
      body.wpbbshop-theme .llg-mobile-search-field{height:44px!important;min-width:0!important}
      body.wpbbshop-theme .llg-mobile-search input[type=search]{
        width:100%!important;height:44px!important;min-height:44px!important;padding:0 10px!important;
        font-size:12px!important;line-height:44px!important;border:0!important;border-radius:0!important;box-shadow:none!important;
      }
      body.wpbbshop-theme .llg-mobile-search > .llg-search-submit{
        position:absolute!important;
        top:0!important;
        right:0!important;
        bottom:0!important;
        left:auto!important;
        width:46px!important;
        min-width:46px!important;
        max-width:46px!important;
        height:44px!important;
        min-height:44px!important;
        border-radius:0 8px 8px 0!important;
        z-index:10!important;
      }
      body.wpbbshop-theme .llg-mobile-search .wpbbshop-search-results{
        top:50px!important;
        left:-113px!important;
        right:-47px!important;
        width:auto!important;
        max-width:none!important;
        z-index:400!important;
      }

      /* Force the four account actions into one aligned row. */
      body.wpbbshop-theme .llg-mobile-actions{
        box-sizing:border-box!important;
        display:flex!important;
        flex-flow:row nowrap!important;
        align-items:stretch!important;
        justify-content:stretch!important;
        width:calc(100% - 16px)!important;
        max-width:none!important;
        margin:0 8px 10px!important;
        padding:0!important;
        gap:6px!important;
        grid-template-columns:none!important;
      }
      body.wpbbshop-theme .llg-mobile-actions > a,
      body.wpbbshop-theme .llg-mobile-actions > button{
        box-sizing:border-box!important;
        flex:1 1 25%!important;
        width:auto!important;
        min-width:0!important;
        max-width:none!important;
        height:58px!important;
        min-height:58px!important;
        margin:0!important;
        padding:6px 2px!important;
        border:1px solid #e0e8ed!important;
        border-radius:9px!important;
        background:#fff!important;
        display:flex!important;
        flex-direction:column!important;
        align-items:center!important;
        justify-content:center!important;
        gap:3px!important;
        box-shadow:0 1px 2px rgba(13,41,61,.025)!important;
        overflow:visible!important;
      }
      body.wpbbshop-theme .llg-mobile-actions .wpbbshop-green-icon{width:21px!important;height:21px!important;flex:0 0 auto!important}
      body.wpbbshop-theme .llg-mobile-actions span{font-size:9px!important;line-height:1.05!important;white-space:nowrap!important;text-align:center!important}
      body.wpbbshop-theme .llg-mobile-actions em{top:3px!important;right:6px!important}

      /* Keep the navigation buttons flush and exactly half width. */
      body.wpbbshop-theme .llg-mobile-navbar{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;width:100%!important}
      body.wpbbshop-theme .llg-mobile-catalog-wrap,
      body.wpbbshop-theme .llg-mobile-menu-toggle{min-width:0!important;width:100%!important;max-width:none!important}
    }

    @media(max-width:430px){
      body.wpbbshop-theme .llg-mobile-search{grid-template-columns:104px minmax(0,1fr)!important}
      body.wpbbshop-theme .llg-mobile-search .wpbbshop-search-results{left:-105px!important}
      body.wpbbshop-theme .llg-mobile-actions{gap:4px!important}
      body.wpbbshop-theme .llg-mobile-actions > a,
      body.wpbbshop-theme .llg-mobile-actions > button{height:54px!important;min-height:54px!important;padding-left:1px!important;padding-right:1px!important}
      body.wpbbshop-theme .llg-mobile-actions span{font-size:8px!important}
      body.wpbbshop-theme .llg-mobile-actions .wpbbshop-green-icon{width:19px!important;height:19px!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v311_mobile_search_alignment_css', PHP_INT_MAX);
