<?php
/**
 * v3.0.8 critical header/mobile hardening.
 * Runs after WordPress admin-bar bump styles so hidden admin bars cannot leave a white strip.
 */
defined('ABSPATH') || exit;

function wpbbshop_v308_critical_header_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v308-critical-header">
    html{margin-top:0!important;padding-top:0!important}
    body.wpbbshop-theme{margin:0!important;padding:0!important}
    body.wpbbshop-theme>.llg-header,
    body.wpbbshop-theme .llg-header{margin-top:0!important}
    body.wpbbshop-theme:not(.llg-adminbar-visible){padding-top:0!important}
    body.wpbbshop-theme.llg-adminbar-visible{padding-top:32px!important}
    body.wpbbshop-theme.llg-adminbar-visible .llg-header{margin-top:0!important}
    body.wpbbshop-theme>br,
    body.wpbbshop-theme>p:empty,
    body.wpbbshop-theme>.wp-site-blocks>br,
    body.wpbbshop-theme>.wp-site-blocks>p:empty{display:none!important;margin:0!important;padding:0!important;height:0!important;min-height:0!important}

    /* Desktop: remove the oversized empty zone between header controls and navigation. */
    body.wpbbshop-theme .llg-topbar-inner{min-height:36px!important}
    body.wpbbshop-theme .llg-mainbar-preview{
        min-height:116px!important;
        grid-template-columns:238px minmax(420px,1fr) 356px!important;
        gap:24px!important;
        padding:7px 0!important;
        align-items:center!important;
    }
    body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo,
    body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo img{
        width:224px!important;max-width:224px!important;height:auto!important;
    }
    body.wpbbshop-theme .llg-header .llg-search-extended{height:50px!important;grid-template-columns:164px minmax(0,1fr) 54px!important}
    body.wpbbshop-theme .llg-header .llg-search-select-wrap,
    body.wpbbshop-theme .llg-header .llg-search-select-wrap select,
    body.wpbbshop-theme .llg-header .llg-search-field-wrap input[type=search],
    body.wpbbshop-theme .llg-header .llg-search-extended>button{height:48px!important}
    body.wpbbshop-theme .llg-header .llg-search-select-wrap:after{top:17px!important}
    body.wpbbshop-theme .llg-header .llg-actions{width:356px!important;min-width:356px!important;gap:4px!important}
    body.wpbbshop-theme .llg-header .llg-actions>a,
    body.wpbbshop-theme .llg-header .llg-actions>button{flex:0 0 84px!important;width:84px!important;min-width:84px!important;height:64px!important;padding:4px 3px!important;gap:3px!important}
    body.wpbbshop-theme .llg-header .llg-actions .wpbbshop-green-icon{width:23px!important;height:23px!important}
    body.wpbbshop-theme .llg-nav-inner-preview{height:50px!important;grid-template-columns:238px minmax(0,1fr) 110px!important}
    body.wpbbshop-theme .llg-catalog-button,
    body.wpbbshop-theme .llg-main-nav .wpbbshop-menu,
    body.wpbbshop-theme .llg-main-nav .wpbbshop-menu li,
    body.wpbbshop-theme .llg-main-nav .wpbbshop-menu a,
    body.wpbbshop-theme .llg-nav-sale-link{height:50px!important}
    body.wpbbshop-theme .llg-catalog-menu{top:50px!important}

    @media(max-width:1180px) and (min-width:821px){
      body.wpbbshop-theme .llg-mainbar-preview{grid-template-columns:205px minmax(0,1fr) 318px!important;gap:14px!important}
      body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo img{width:198px!important;max-width:198px!important}
      body.wpbbshop-theme .llg-header .llg-actions{width:318px!important;min-width:318px!important}
      body.wpbbshop-theme .llg-header .llg-actions>a,
      body.wpbbshop-theme .llg-header .llg-actions>button{flex-basis:76px!important;width:76px!important;min-width:76px!important}
      body.wpbbshop-theme .llg-nav-inner-preview{grid-template-columns:205px minmax(0,1fr)!important}
      body.wpbbshop-theme .llg-nav-sale-link{display:none!important}
    }

    @media(max-width:820px){
      html{margin-top:0!important}
      body.wpbbshop-theme.llg-adminbar-visible{padding-top:46px!important}
      body.wpbbshop-theme .wpbbshop-container{width:calc(100% - 20px)!important;max-width:none!important}
      body.wpbbshop-theme .llg-header{position:relative!important;margin:0!important;padding:0!important}
      body.wpbbshop-theme .llg-topbar-inner{min-height:34px!important;padding:0!important}
      body.wpbbshop-theme .llg-topbar-left{width:100%!important;justify-content:flex-start!important;gap:18px!important}
      body.wpbbshop-theme .llg-topbar-left>span{font-size:10px!important}
      body.wpbbshop-theme .llg-topbar-left>span:nth-child(n+3),
      body.wpbbshop-theme .llg-topbar-right{display:none!important}

      body.wpbbshop-theme .llg-mainbar-preview{
        min-height:0!important;display:grid!important;grid-template-columns:1fr!important;
        gap:8px!important;padding:8px 0!important;text-align:left!important;align-items:center!important;
      }
      body.wpbbshop-theme .llg-header .llg-logo{justify-content:center!important;margin:0!important;line-height:0!important}
      body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo img{width:158px!important;max-width:158px!important;height:auto!important}
      body.wpbbshop-theme .llg-header .llg-search-column{grid-column:1!important;width:100%!important;gap:0!important}
      body.wpbbshop-theme .llg-header .llg-search-extended{
        width:100%!important;height:44px!important;display:grid!important;
        grid-template-columns:108px minmax(0,1fr) 44px!important;
        border-radius:8px!important;overflow:visible!important;
      }
      body.wpbbshop-theme .llg-header .llg-search-select-wrap{grid-column:auto!important;height:42px!important;border-radius:7px 0 0 7px!important;border-right:1px solid var(--ll-line)!important;border-bottom:0!important}
      body.wpbbshop-theme .llg-header .llg-search-select-wrap select{height:42px!important;padding:0 22px 0 9px!important;font-size:10px!important}
      body.wpbbshop-theme .llg-header .llg-search-select-wrap:after{right:9px!important;top:14px!important;width:6px!important;height:6px!important}
      body.wpbbshop-theme .llg-header .llg-search-field-wrap input[type=search]{height:42px!important;padding:0 9px!important;font-size:11px!important;border-radius:0!important}
      body.wpbbshop-theme .llg-header .llg-search-extended>button{width:44px!important;min-width:44px!important;height:42px!important;border-radius:0 7px 7px 0!important}
      body.wpbbshop-theme .llg-header .llg-search-extended>button .wpbbshop-green-icon{width:19px!important;height:19px!important}
      body.wpbbshop-theme .llg-header .wpbbshop-search-results{top:46px!important;left:0!important;right:0!important;max-height:60vh!important;overflow:auto!important}
      body.wpbbshop-theme .llg-popular-searches{display:none!important}

      body.wpbbshop-theme .llg-header .llg-actions{
        width:100%!important;min-width:0!important;display:grid!important;
        grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:4px!important;justify-content:stretch!important;
      }
      body.wpbbshop-theme .llg-header .llg-actions>a,
      body.wpbbshop-theme .llg-header .llg-actions>button{
        flex:none!important;width:auto!important;min-width:0!important;height:47px!important;padding:3px 1px!important;gap:1px!important;
        border:1px solid #e8edf0!important;background:#fff!important;border-radius:7px!important;
      }
      body.wpbbshop-theme .llg-header .llg-actions .wpbbshop-green-icon{width:18px!important;height:18px!important}
      body.wpbbshop-theme .llg-header .llg-actions span{font-size:8.5px!important;line-height:1!important;white-space:nowrap!important}
      body.wpbbshop-theme .llg-header .llg-actions em{top:1px!important;right:2px!important;min-width:15px!important;height:15px!important;line-height:15px!important;padding:0 3px!important;font-size:8px!important}
      body.wpbbshop-theme .llg-header .llg-actions .wpbbshop-cart-total{display:none!important}

      body.wpbbshop-theme .llg-nav-row{margin:0!important;padding:0!important}
      body.wpbbshop-theme .llg-nav-inner-preview{
        width:100%!important;max-width:none!important;height:46px!important;padding:0!important;margin:0!important;
        display:grid!important;grid-template-columns:1fr 1fr!important;align-items:stretch!important;
      }
      body.wpbbshop-theme .llg-main-nav,
      body.wpbbshop-theme .llg-nav-sale-link{display:none!important}
      body.wpbbshop-theme .llg-catalog-wrap{width:100%!important;min-width:0!important}
      body.wpbbshop-theme .llg-catalog-button,
      body.wpbbshop-theme .llg-mobile-menu-toggle{
        width:100%!important;height:46px!important;min-height:46px!important;margin:0!important;padding:0 10px!important;
        border:0!important;border-radius:0!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:7px!important;
        color:#fff!important;font-size:11px!important;font-weight:850!important;cursor:pointer!important;
      }
      body.wpbbshop-theme .llg-catalog-button{background:var(--ll-green)!important}
      body.wpbbshop-theme .llg-mobile-menu-toggle{background:#0d293d!important}
      body.wpbbshop-theme .llg-catalog-menu{top:46px!important;left:0!important;width:min(320px,calc(100vw - 10px))!important;max-height:70vh!important;overflow:auto!important}
      body.wpbbshop-theme .llg-mobile-menu-panel{top:100%!important;left:0!important;right:0!important;width:100%!important;max-height:calc(100vh - 46px)!important;overflow:auto!important}
      body.wpbbshop-theme .llg-mobile-menu-inner{width:calc(100% - 20px)!important;padding:12px 0 16px!important}
      body.wpbbshop-theme .llg-mobile-primary .wpbbshop-menu{grid-template-columns:1fr!important;margin:8px 0 10px!important}
      body.wpbbshop-theme .llg-mobile-quick-links{grid-template-columns:repeat(2,minmax(0,1fr))!important}

      body.wpbbshop-theme .llg-home{padding-top:10px!important}
    }

    @media(max-width:430px){
      body.wpbbshop-theme .llg-topbar-left>span:nth-child(n+2){display:none!important}
      body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-header .llg-logo .wpbbshop-approved-logo img{width:146px!important;max-width:146px!important}
      body.wpbbshop-theme .llg-header .llg-search-extended{grid-template-columns:96px minmax(0,1fr) 42px!important}
      body.wpbbshop-theme .llg-header .llg-search-extended>button{width:42px!important;min-width:42px!important}
      body.wpbbshop-theme .llg-header .llg-actions{grid-template-columns:repeat(4,minmax(0,1fr))!important}
      body.wpbbshop-theme .llg-header .llg-actions span{font-size:7.8px!important}
    }
    </style>
    <script id="wpbbshop-v308-head-gap-fix">document.documentElement.style.setProperty('margin-top','0px','important');</script>
    <?php
}
add_action('wp_head', 'wpbbshop_v308_critical_header_css', PHP_INT_MAX);

function wpbbshop_v308_footer_spacer_cleanup_script() {
    if (is_admin()) { return; }
    ?>
    <script id="wpbbshop-v308-dom-cleanup">
    (function(){
      function emptySpacer(n){
        if(!n||n.nodeType!==1)return false;
        var t=(n.tagName||'').toLowerCase();
        if(t==='br')return true;
        if(t!=='p')return false;
        return (n.innerHTML||'').replace(/&nbsp;|&#160;/gi,'').replace(/<br\s*\/?\s*>/gi,'').replace(/\s+/g,'')==='';
      }
      function cleanAround(el){
        if(!el||!el.parentNode)return;
        var n=el.previousSibling;
        while(n){
          var p=n.previousSibling;
          if(n.nodeType===3 && !String(n.nodeValue||'').trim()){n.remove();n=p;continue;}
          if(emptySpacer(n)){n.remove();n=p;continue;}
          break;
        }
        n=el.nextSibling;
        while(n){
          var q=n.nextSibling;
          if(n.nodeType===3 && !String(n.nodeValue||'').trim()){n.remove();n=q;continue;}
          if(emptySpacer(n)){n.remove();n=q;continue;}
          break;
        }
      }
      function syncAdminBar(){
        var b=document.body,bar=document.getElementById('wpadminbar');if(!b)return;
        var visible=false;
        if(bar){try{var c=getComputedStyle(bar),r=bar.getBoundingClientRect();visible=c.display!=='none'&&c.visibility!=='hidden'&&r.height>10&&r.width>10;}catch(e){}}
        b.classList.toggle('llg-adminbar-visible',visible);
        document.documentElement.style.setProperty('margin-top','0px','important');
      }
      function run(){cleanAround(document.querySelector('.llg-header'));cleanAround(document.querySelector('.llg-footer'));syncAdminBar();}
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',run);else run();
      window.addEventListener('load',run);
      setTimeout(run,300);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'wpbbshop_v308_footer_spacer_cleanup_script', PHP_INT_MAX);
