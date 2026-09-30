<?php
/**
 * Legacy 3.0.26 compatibility helpers.
 *
 * The former homepage-only topbar CSS was intentionally removed in 3.0.28.
 * Header/grid geometry is now owned by the site-wide 3.0.28 grid layer so the
 * homepage and inner pages cannot diverge.
 */
defined('ABSPATH') || exit;

/** Keep the shorter Latvian quote label for plugin-inserted UI. */
function wpbbshop_v326_quote_label_js() {
    if (is_admin()) { return; }
    ?>
    <script id="wpbbshop-v326-quote-label-js">
    document.addEventListener('DOMContentLoaded',function(){
      var lang=(document.documentElement.lang||'').toLowerCase();
      if(lang.indexOf('en')===0)return;
      function fix(root){
        var walker=document.createTreeWalker(root||document.body,NodeFilter.SHOW_TEXT),n;
        while((n=walker.nextNode())){
          var value=(n.nodeValue||'').trim();
          if(value==='Mans cenu pieprasījums'||value==='My Quote'){
            n.nodeValue=n.nodeValue.replace(value,'Cenu pieprasījums');
          }
        }
      }
      fix(document.body);
      if(window.MutationObserver){
        new MutationObserver(function(ms){
          ms.forEach(function(m){m.addedNodes.forEach(function(n){if(n.nodeType===1)fix(n);});});
        }).observe(document.body,{childList:true,subtree:true});
      }
    });
    </script>
    <?php
}
add_action('wp_footer', 'wpbbshop_v326_quote_label_js', PHP_INT_MAX);
