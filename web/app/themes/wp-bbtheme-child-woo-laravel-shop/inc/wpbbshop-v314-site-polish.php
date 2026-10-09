<?php
/**
 * WP BB Home & Garden 3.0.14 — final content, account, product-gallery and XML hardening.
 */
defined('ABSPATH') || exit;

function wpbbshop_v314_is_en() {
    if (function_exists('pll_current_language')) {
        $lang = wpbbshop_v433_current_language();
        if ($lang) { return $lang === 'en'; }
    }
    return substr((string) get_locale(), 0, 2) !== 'lv';
}

function wpbbshop_v314_request_path() {
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = (string) parse_url((string) $uri, PHP_URL_PATH);
    return trim($path, '/');
}

/**
 * Serve comparison feeds before the normal WordPress 404/template flow.
 * This deliberately does not depend on rewrite rules.
 */
function wpbbshop_v314_direct_xml_feed($wp = null) {
    if (is_admin()) { return; }
    $path = wpbbshop_v314_request_path();
    if (!preg_match('#^(?:en/)?(kurpirkt|salidzini|ceno)\.xml$#i', $path, $m)) { return; }
    $feed = strtolower($m[1]);
    if (!class_exists('WooCommerce') || !function_exists('wpbbshop_compare_feed_build')) {
        status_header(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'WooCommerce feed is not available.';
        exit;
    }
    $xml = wpbbshop_compare_feed_build($feed);
    if (!is_string($xml) || trim($xml) === '') {
        status_header(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Feed generation failed.';
        exit;
    }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, follow', true);
    header('Cache-Control: public, max-age=900, stale-while-revalidate=3600');
    if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'HEAD') {
        echo $xml;
    }
    exit;
}
add_action('parse_request', 'wpbbshop_v314_direct_xml_feed', 0);

/** Make sure the comparison feeds stay enabled after theme upgrades. */
function wpbbshop_v314_enable_feeds() {
    $opts = get_option('wpbbshop_theme_options', array());
    if (!is_array($opts)) { $opts = array(); }
    $changed = false;
    foreach (array('enable_kurpirkt_feed','enable_salidzini_feed') as $key) {
        if (!isset($opts[$key]) || $opts[$key] !== '1') { $opts[$key] = '1'; $changed = true; }
    }
    if ($changed) { update_option('wpbbshop_theme_options', $opts, false); }
    if (function_exists('wpbbshop_compare_feed_invalidate')) { wpbbshop_compare_feed_invalidate(); }
}
add_action('after_switch_theme', 'wpbbshop_v314_enable_feeds', 150);
add_action('admin_init', function() {
    if (current_user_can('edit_theme_options') && get_option('wpbbshop_v314_feed_ready') !== '1') {
        wpbbshop_v314_enable_feeds();
        if (function_exists('wpbbshop_compare_feed_register_rewrites')) { wpbbshop_compare_feed_register_rewrites(); }
        flush_rewrite_rules(false);
        update_option('wpbbshop_v314_feed_ready', '1', false);
    }
}, 150);

/**
 * Virtual fallback for managed pages. This means a missing DB page no longer produces a public 404.
 */
function wpbbshop_v314_virtual_page_data($path) {
    $map = array(
        'kontakti' => array('Kontakti', 'contact', 'lv'),
        'contact' => array('Contact', 'contact', 'en'),
        'pirksanas-noteikumi' => array('Pirkšanas noteikumi', 'terms', 'lv'),
        'terms-and-conditions' => array('Terms & conditions', 'terms', 'en'),
    );
    if (!isset($map[$path])) { return false; }
    $row = $map[$path];
    $content = function_exists('wpbbshop_v313_page_content') ? wpbbshop_v313_page_content($row[1], $row[2]) : '';
    return array('title'=>$row[0], 'content'=>$content, 'lang'=>$row[2]);
}

function wpbbshop_v314_render_virtual_page() {
    if (is_admin() || !is_404()) { return; }
    $data = wpbbshop_v314_virtual_page_data(wpbbshop_v314_request_path());
    if (!$data) { return; }
    status_header(200);
    get_header();
    echo '<main class="wpbbshop-page-shell wpbbshop-container llg-v314-virtual-page">';
    echo '<header class="wpbbshop-page-heading wpbbshop-info-heading"><span class="wpbbshop-page-kicker">WP BB HOME & GARDEN</span><h1>'.esc_html($data['title']).'</h1></header>';
    echo '<article class="wpbbshop-page-card"><div class="entry-content">'.do_shortcode($data['content']).'</div></article>';
    echo '</main>';
    get_footer();
    exit;
}
add_action('template_redirect', 'wpbbshop_v314_render_virtual_page', 4);

add_filter('body_class', function($classes) {
    $managed = array('kontakti','contact','par-mums','about-us','piegade-un-apmaksa','delivery-payment','pirksanas-noteikumi','terms-and-conditions');
    if (is_page($managed) || in_array(wpbbshop_v314_request_path(), $managed, true)) {
        $classes[] = 'llg-v314-info-page';
    }
    return $classes;
}, PHP_INT_MAX);

/** Keep managed information page content current only when it is absent/managed, never overwrite custom editor content. */
function wpbbshop_v314_refresh_managed_pages() {
    if (!current_user_can('edit_theme_options')) { return; }
    $defs = array(
        'kontakti' => array('Kontakti','contact','lv'),
        'contact' => array('Contact','contact','en'),
        'pirksanas-noteikumi' => array('Pirkšanas noteikumi','terms','lv'),
        'terms-and-conditions' => array('Terms & conditions','terms','en'),
    );
    foreach ($defs as $slug => $cfg) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        $content = function_exists('wpbbshop_v313_page_content') ? wpbbshop_v313_page_content($cfg[1], $cfg[2]) : '';
        if (!$page) {
            $id = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>$cfg[0],'post_name'=>$slug,'post_content'=>$content,'comment_status'=>'closed','ping_status'=>'closed'), true);
            if (!is_wp_error($id) && $id && function_exists('pll_set_post_language')) { pll_set_post_language($id, $cfg[2]); }
            continue;
        }
        $is_managed = strpos((string) $page->post_content, 'wpbbshop-managed-page:') !== false || trim((string) $page->post_content) === '';
        if ($is_managed && $content !== '' && $page->post_content !== $content) {
            wp_update_post(array('ID'=>$page->ID,'post_content'=>$content,'post_status'=>'publish'));
        }
    }
}
add_action('admin_init', 'wpbbshop_v314_refresh_managed_pages', 155);

/** Product tabs fallback: keep only the selected panel visible even when inherited Woo CSS/JS is missing. */
function wpbbshop_v314_footer_js() {
    if (is_admin()) { return; }
    ?>
    <script id="wpbbshop-v314-ui-js">
    (function(){
      function ready(fn){if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn,{once:true});}else{fn();}}
      ready(function(){
        document.querySelectorAll('.llg-single-tabs .wc-tabs-wrapper').forEach(function(wrap){
          var links=Array.prototype.slice.call(wrap.querySelectorAll('.wc-tabs a'));
          var panels=Array.prototype.slice.call(wrap.querySelectorAll('.woocommerce-Tabs-panel'));
          if(!links.length||!panels.length)return;
          function activate(link){
            var target=(link.getAttribute('href')||'').replace(/^.*#/,'');
            links.forEach(function(a){var li=a.closest('li');if(li)li.classList.toggle('active',a===link);a.setAttribute('aria-selected',a===link?'true':'false');});
            panels.forEach(function(p){var show=p.id===target;p.hidden=!show;p.style.display=show?'block':'none';});
          }
          links.forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();activate(a);});});
          activate(links.find(function(a){return a.closest('li')&&a.closest('li').classList.contains('active');})||links[0]);
        });

        // Latvian quote helper copy can be injected by a plugin after gettext has already run.
        if(!document.documentElement.lang.toLowerCase().startsWith('en')){
          var map={'Your selection':'Jūsu izvēle','Shopping cart':'Izvēlētās preces','Your cart is ready when you are.':'Jūsu izvēlētās preces ir gatavas.','Continue shopping':'Turpināt iepirkties','My Quote':'Cenu pieprasījums','Add to Quote':'Pievienot cenu pieprasījumam'};
          function translate(root){
            var walker=document.createTreeWalker(root||document.body,NodeFilter.SHOW_TEXT);var n;
            while((n=walker.nextNode())){var v=(n.nodeValue||'').trim();if(map[v])n.nodeValue=n.nodeValue.replace(v,map[v]);}
          }
          translate(document.body);
          if(window.MutationObserver){new MutationObserver(function(ms){ms.forEach(function(m){m.addedNodes.forEach(function(n){if(n.nodeType===1)translate(n);});});}).observe(document.body,{childList:true,subtree:true});}
        }
      });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'wpbbshop_v314_footer_js', PHP_INT_MAX);

/** Final CSS loaded after parent, WooCommerce, BBuilder and all previous child-theme compatibility layers. */
function wpbbshop_v314_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v314-final-css">
    /* One centered storefront width for information/account/product/archive pages. */
    body.wpbbshop-theme .wpbbshop-page-shell,
    body.wpbbshop-theme .llg-account-shell>.wpbbshop-container,
    body.wpbbshop-theme .llg-single-container,
    body.wpbbshop-theme .llg-archive-page>.wpbbshop-container{
      box-sizing:border-box!important;width:min(1240px,calc(100% - 40px))!important;max-width:1240px!important;margin-left:auto!important;margin-right:auto!important;padding-left:0!important;padding-right:0!important;float:none!important;transform:none!important;
    }
    body.wpbbshop-theme .wpbbshop-page-shell{margin-top:30px!important;margin-bottom:56px!important;padding-top:0!important;padding-bottom:0!important}
    body.wpbbshop-theme .wpbbshop-page-heading{margin:0 0 18px!important}
    body.wpbbshop-theme .wpbbshop-page-card{width:100%!important;max-width:none!important;margin:0!important;padding:30px!important;box-sizing:border-box!important}
    body.llg-v314-info-page .wpbbshop-page-card{padding:30px!important}
    body.llg-v314-info-page .entry-content{width:100%!important;max-width:none!important;margin:0 auto!important}
    body.llg-v314-info-page .entry-content>.wpbb-row,
    body.llg-v314-info-page .entry-content>.wp-block-group,
    body.llg-v314-info-page .entry-content>.llg-info-pro{width:100%!important;max-width:none!important;margin-left:auto!important;margin-right:auto!important}
    body.llg-v314-info-page .wpbb-row{margin-left:0!important;margin-right:0!important;--bs-gutter-x:0!important}
    body.llg-v314-info-page .wpbb-column{min-width:0!important;max-width:none!important;margin:0!important}
    body.llg-v314-info-page .wpbbshop-info-row>.wpbb-column{width:100%!important;flex:0 0 100%!important;padding:0!important}
    body.llg-v314-info-page .wpbbshop-contact-row{display:grid!important;grid-template-columns:minmax(0,.86fr) minmax(0,1.14fr)!important;gap:26px!important;align-items:start!important}
    body.llg-v314-info-page .wpbbshop-contact-row>.wpbb-column{width:100%!important;flex:0 0 auto!important;padding:0!important}
    body.llg-v314-info-page .wpbbshop-contact-cards{display:grid!important;grid-template-columns:1fr 1fr!important;gap:12px!important}
    body.llg-v314-info-page .wpbbshop-contact-card{margin:0!important;padding:18px!important;border:1px solid #e1e8ec!important;border-radius:12px!important;background:#fff!important}
    body.llg-v314-info-page .wpbbshop-contact-form-wrap{width:100%!important;margin:0!important;padding:22px!important;border:1px solid #e1e8ec!important;border-radius:14px!important;background:#fff!important;box-shadow:0 8px 26px rgba(18,51,74,.05)!important}
    body.llg-v314-info-page .wpbbshop-contact-form-grid{display:grid!important;grid-template-columns:1fr 1fr!important;gap:16px!important}
    body.llg-v314-info-page .wpbbshop-contact-field--full{grid-column:1/-1!important}
    body.llg-v314-info-page .wpbbshop-contact-form .form-control{width:100%!important;min-height:46px!important;border:1px solid #ccd8df!important;border-radius:8px!important;background:#fff!important;box-shadow:none!important;padding:10px 12px!important}
    body.llg-v314-info-page .wpbbshop-contact-form textarea.form-control{min-height:150px!important}
    body.llg-v314-info-page .wpbbshop-contact-form-actions{display:flex!important;justify-content:flex-start!important;margin-top:16px!important}
    body.llg-v314-info-page .wpbbshop-contact-submit,
    body.llg-v314-info-page .wpbbshop-contact-submit.btn,
    body.llg-v314-info-page .wpbbshop-contact-submit.btn-primary{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:46px!important;padding:0 24px!important;border:0!important;border-radius:8px!important;background:#2b9748!important;background-image:none!important;color:#fff!important;font-weight:800!important;box-shadow:none!important}
    body.llg-v314-info-page .wpbbshop-contact-submit:hover{background:#237d3c!important;color:#fff!important}

    /* My Account: output now has an explicit layout wrapper, so old Woo floats cannot collapse the content. */
    body.woocommerce-account .llg-commerce-shell{padding:30px 0 58px!important;background:#f5f7f9!important}
    body.woocommerce-account .llg-commerce-card{padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}
    body.woocommerce-account .llg-commerce-card>.woocommerce{display:block!important;width:100%!important;max-width:none!important;margin:0!important}
    body.woocommerce-account .llg-myaccount-layout{display:grid!important;grid-template-columns:260px minmax(0,1fr)!important;gap:24px!important;align-items:start!important;width:100%!important;max-width:none!important;margin:0!important}
    body.woocommerce-account .llg-myaccount-nav-card,
    body.woocommerce-account .llg-myaccount-content-card{box-sizing:border-box!important;width:100%!important;min-width:0!important;max-width:none!important;border:1px solid #dfe7ec!important;border-radius:14px!important;background:#fff!important;box-shadow:0 8px 28px rgba(18,51,74,.05)!important}
    body.woocommerce-account .llg-myaccount-nav-card{padding:16px!important}
    body.woocommerce-account .llg-myaccount-nav-title{margin:2px 8px 12px!important;font-size:18px!important;color:#12334a!important}
    body.woocommerce-account .woocommerce-MyAccount-navigation{float:none!important;width:100%!important;max-width:none!important;margin:0!important}
    body.woocommerce-account .woocommerce-MyAccount-navigation ul{display:block!important;width:100%!important;margin:0!important;padding:0!important;border:0!important;background:transparent!important;list-style:none!important}
    body.woocommerce-account .woocommerce-MyAccount-navigation li{display:block!important;width:100%!important;margin:0 0 6px!important}
    body.woocommerce-account .woocommerce-MyAccount-navigation a{display:flex!important;align-items:center!important;width:100%!important;min-height:46px!important;padding:0 14px!important;border-radius:9px!important;background:#f7f9fa!important;color:#536473!important;font-weight:750!important;text-decoration:none!important}
    body.woocommerce-account .woocommerce-MyAccount-navigation .is-active a,
    body.woocommerce-account .woocommerce-MyAccount-navigation a:hover{background:#2b9748!important;color:#fff!important}
    body.woocommerce-account .llg-myaccount-content-card{padding:28px!important}
    body.woocommerce-account .woocommerce-MyAccount-content{float:none!important;width:100%!important;min-width:0!important;max-width:none!important;margin:0!important;padding:0!important}
    body.woocommerce-account .woocommerce-MyAccount-content>*{max-width:none!important}
    body.woocommerce-account .woocommerce-MyAccount-content p{max-width:900px!important;line-height:1.65!important}
    body.woocommerce-account .woocommerce-MyAccount-content form{width:100%!important;max-width:900px!important}
    body.woocommerce-account .woocommerce-MyAccount-content .form-row{float:none!important;width:100%!important;max-width:none!important;margin:0 0 15px!important}
    body.woocommerce-account .woocommerce-MyAccount-content input.input-text,
    body.woocommerce-account .woocommerce-MyAccount-content input[type=email],
    body.woocommerce-account .woocommerce-MyAccount-content input[type=password],
    body.woocommerce-account .woocommerce-MyAccount-content input[type=text],
    body.woocommerce-account .woocommerce-MyAccount-content select,
    body.woocommerce-account .woocommerce-MyAccount-content textarea{width:100%!important;min-height:46px!important;padding:10px 12px!important;border:1px solid #ccd8df!important;border-radius:8px!important;background:#fff!important;box-shadow:none!important}
    body.woocommerce-account .woocommerce-MyAccount-content fieldset{margin:22px 0 0!important;padding:20px!important;border:1px solid #e0e8ed!important;border-radius:12px!important;background:#f8fafb!important}
    body.woocommerce-account .woocommerce-MyAccount-content legend{float:none!important;width:auto!important;padding:0 8px!important;color:#12334a!important;font-size:18px!important;font-weight:800!important}
    body.woocommerce-account .woocommerce-MyAccount-content button.button{min-height:44px!important;padding:0 20px!important;border:0!important;border-radius:8px!important;background:#2b9748!important;color:#fff!important;font-weight:800!important}
    body.woocommerce-account:not(.logged-in) .llg-commerce-card>.woocommerce .u-columns{display:grid!important;grid-template-columns:1fr 1fr!important;gap:24px!important;width:100%!important}
    body.woocommerce-account:not(.logged-in) .llg-commerce-card>.woocommerce .u-column1,
    body.woocommerce-account:not(.logged-in) .llg-commerce-card>.woocommerce .u-column2{float:none!important;width:100%!important;max-width:none!important;margin:0!important;padding:26px!important;border:1px solid #dfe7ec!important;border-radius:14px!important;background:#fff!important}

    /* Product page spacing, pricing and buy controls. */
    body.single-product.wpbbshop-theme .llg-single-page{padding:30px 0 58px!important}
    body.single-product.wpbbshop-theme .llg-single-product-card{gap:24px!important}
    body.single-product.wpbbshop-theme .llg-single-gallery-panel{padding:18px!important}
    body.single-product.wpbbshop-theme .llg-single-summary-panel{padding:32px!important}
    body.single-product.wpbbshop-theme .llg-single-price{display:flex!important;align-items:baseline!important;flex-wrap:wrap!important;gap:10px!important;margin:10px 0 18px!important;color:#237d3c!important;font-size:32px!important;line-height:1.1!important}
    body.single-product.wpbbshop-theme .llg-single-price del{order:2!important;color:#8a969f!important;font-size:15px!important;font-weight:500!important;opacity:1!important}
    body.single-product.wpbbshop-theme .llg-single-price ins{order:1!important;color:#237d3c!important;text-decoration:none!important;font-weight:900!important}
    body.single-product.wpbbshop-theme .llg-single-buybox{margin-top:18px!important;padding:20px!important;border:1px solid #dbe8df!important;border-radius:13px!important;background:#f7fbf8!important;box-shadow:none!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .stock{margin:0 0 12px!important;color:#237d3c!important;font-weight:800!important}
    body.single-product.wpbbshop-theme .llg-single-buybox form.cart{display:flex!important;align-items:center!important;gap:10px!important;flex-wrap:wrap!important;width:100%!important;margin:0!important;padding:0!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity{display:flex!important;align-items:center!important;gap:0!important;margin:0!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity input.qty{box-sizing:border-box!important;width:70px!important;height:46px!important;min-height:46px!important;margin:0!important;padding:0 8px!important;border:1px solid #cbd8df!important;border-radius:8px!important;background:#fff!important;text-align:center!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .single_add_to_cart_button,
    body.single-product.wpbbshop-theme .llg-single-buybox .wp-theme-add-to-quote{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:46px!important;padding:0 18px!important;border:0!important;border-radius:8px!important;font-weight:850!important;white-space:nowrap!important;box-shadow:none!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .single_add_to_cart_button{background:#2b9748!important;color:#fff!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .wp-theme-add-to-quote{background:#12334a!important;color:#fff!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .woocommerce-Price-amount,
    body.single-product.wpbbshop-theme .llg-single-buybox .product-total,
    body.single-product.wpbbshop-theme .llg-single-buybox .single-product-total{color:#12334a!important;font-weight:800!important}
    body.single-product.wpbbshop-theme .llg-single-meta-row{margin-top:20px!important;padding-top:18px!important}
    body.single-product.wpbbshop-theme .llg-single-tabs{margin-top:24px!important;padding:0!important;overflow:hidden!important}
    body.single-product.wpbbshop-theme .llg-single-tabs .woocommerce-tabs{width:100%!important}
    body.single-product.wpbbshop-theme .llg-single-tabs .wc-tabs{display:flex!important;gap:6px!important;margin:0!important;padding:14px 16px 0!important;list-style:none!important;border:0!important}
    body.single-product.wpbbshop-theme .llg-single-tabs .wc-tabs li{margin:0!important;padding:0!important;border:0!important;background:transparent!important}
    body.single-product.wpbbshop-theme .llg-single-tabs .wc-tabs a{display:inline-flex!important;min-height:40px!important;align-items:center!important;padding:0 14px!important;border-radius:8px 8px 0 0!important;background:#eef3f5!important;color:#12334a!important;font-weight:800!important;text-decoration:none!important}
    body.single-product.wpbbshop-theme .llg-single-tabs .wc-tabs li.active a{background:#12334a!important;color:#fff!important}
    body.single-product.wpbbshop-theme .llg-single-tabs .woocommerce-Tabs-panel{margin:0!important;padding:26px!important;border:0!important;border-top:1px solid #e0e8ed!important;border-radius:0!important;background:#fff!important}
    body.single-product.wpbbshop-theme .wpbbshop-single-products-section{margin:24px 0 0!important;padding:22px!important;border:1px solid #dfe7ec!important;border-radius:14px!important;background:#fff!important;box-shadow:0 8px 28px rgba(18,51,74,.04)!important}
    body.single-product.wpbbshop-theme .wpbbshop-single-products-section .wpbbshop-section-head h2{margin:0 0 16px!important;color:#12334a!important;font-size:24px!important}
    body.single-product.wpbbshop-theme .wpbbshop-single-products-grid{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:16px!important}

    /* Gallery zoom/lightbox. */
    body.single-product.wpbbshop-theme .m38gallery-zoom{display:flex!important;align-items:center!important;justify-content:center!important;color:#51616d!important;background:#fff!important}
    body.single-product.wpbbshop-theme .m38gallery-zoom span{font-size:0!important}
    body.single-product.wpbbshop-theme .m38gallery-zoom:before{content:"";width:14px;height:14px;border:2px solid currentColor;border-radius:50%;box-sizing:border-box}
    body.single-product.wpbbshop-theme .m38gallery-zoom:after{content:"";position:absolute;width:7px;height:2px;background:currentColor;transform:translate(6px,6px) rotate(45deg);border-radius:2px}
    .m38gallery-lightbox[hidden]{display:none!important}
    .m38gallery-lightbox{position:fixed!important;inset:0!important;z-index:1000000!important;display:grid!important;grid-template-columns:64px minmax(0,1fr) 64px!important;grid-template-rows:minmax(0,1fr) auto!important;align-items:center!important;gap:12px!important;padding:28px!important;background:rgba(7,24,36,.93)!important;backdrop-filter:blur(5px)!important}
    .m38gallery-lightbox .m38stage{grid-column:2!important;grid-row:1!important;display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;height:100%!important;max-height:calc(100vh - 150px)!important;margin:0!important}
    .m38gallery-lightbox .m38modal-img{display:block!important;max-width:100%!important;max-height:100%!important;width:auto!important;height:auto!important;object-fit:contain!important;border-radius:10px!important;background:#fff!important;box-shadow:0 20px 70px rgba(0,0,0,.35)!important}
    .m38gallery-lightbox .m38close{position:absolute!important;top:18px!important;right:20px!important;width:44px!important;height:44px!important;border:0!important;border-radius:50%!important;background:#fff!important;color:#12334a!important;font-size:28px!important;line-height:1!important;cursor:pointer!important}
    .m38gallery-lightbox .m38arrow{width:48px!important;height:48px!important;border:1px solid rgba(255,255,255,.3)!important;border-radius:50%!important;background:rgba(255,255,255,.12)!important;color:#fff!important;font-size:34px!important;line-height:1!important;cursor:pointer!important}
    .m38gallery-lightbox .m38prev{grid-column:1!important;grid-row:1!important}.m38gallery-lightbox .m38next{grid-column:3!important;grid-row:1!important}
    .m38gallery-lightbox .m38modal-thumbs{grid-column:1/-1!important;grid-row:2!important;display:flex!important;justify-content:center!important;gap:8px!important;overflow-x:auto!important;max-width:900px!important;margin:0 auto!important}
    .m38gallery-lightbox .m38modal-thumb{flex:0 0 68px!important;width:68px!important;height:60px!important;padding:4px!important;border:2px solid transparent!important;border-radius:8px!important;background:#fff!important;cursor:pointer!important}
    .m38gallery-lightbox .m38modal-thumb.is-active{border-color:#5ad967!important}.m38gallery-lightbox .m38modal-thumb img{width:100%!important;height:100%!important;object-fit:contain!important}
    body.m38gallery-open{overflow:hidden!important}

    /* Archive/category grid aligned with the same 1240px content width. */
    body.wpbbshop-theme .llg-archive-page{padding:28px 0 54px!important}
    body.wpbbshop-theme .llg-archive-page .wpbbshop-container{width:min(1240px,calc(100% - 40px))!important;max-width:1240px!important;margin-left:auto!important;margin-right:auto!important}
    body.wpbbshop-theme .wpbbshop-bootstrap-products{grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:16px!important}

    @media(max-width:1024px){
      body.wpbbshop-theme .wpbbshop-page-shell,body.wpbbshop-theme .llg-account-shell>.wpbbshop-container,body.wpbbshop-theme .llg-single-container,body.wpbbshop-theme .llg-archive-page>.wpbbshop-container{width:calc(100% - 28px)!important}
      body.llg-v314-info-page .wpbbshop-contact-row{grid-template-columns:1fr!important}
      body.woocommerce-account .llg-myaccount-layout{grid-template-columns:220px minmax(0,1fr)!important}
      body.single-product.wpbbshop-theme .wpbbshop-single-products-grid,body.wpbbshop-theme .wpbbshop-bootstrap-products{grid-template-columns:repeat(3,minmax(0,1fr))!important}
    }
    @media(max-width:820px){
      body.wpbbshop-theme .wpbbshop-page-shell,body.wpbbshop-theme .llg-account-shell>.wpbbshop-container,body.wpbbshop-theme .llg-single-container,body.wpbbshop-theme .llg-archive-page>.wpbbshop-container{width:calc(100% - 20px)!important}
      body.llg-v314-info-page .wpbbshop-page-card{padding:18px!important}
      body.llg-v314-info-page .wpbbshop-contact-form-grid{grid-template-columns:1fr!important}
      body.llg-v314-info-page .wpbbshop-contact-field--full{grid-column:auto!important}
      body.woocommerce-account .llg-myaccount-layout{grid-template-columns:1fr!important}
      body.woocommerce-account .woocommerce-MyAccount-navigation ul{display:flex!important;gap:6px!important;overflow-x:auto!important;white-space:nowrap!important;padding-bottom:4px!important}
      body.woocommerce-account .woocommerce-MyAccount-navigation li{flex:0 0 auto!important;width:auto!important;margin:0!important}
      body.woocommerce-account .woocommerce-MyAccount-navigation a{width:auto!important;padding:0 13px!important}
      body.woocommerce-account:not(.logged-in) .llg-commerce-card>.woocommerce .u-columns{grid-template-columns:1fr!important}
      body.single-product.wpbbshop-theme .llg-single-summary-panel{padding:22px!important}
      body.single-product.wpbbshop-theme .wpbbshop-single-products-grid,body.wpbbshop-theme .wpbbshop-bootstrap-products{grid-template-columns:repeat(2,minmax(0,1fr))!important}
      .m38gallery-lightbox{grid-template-columns:44px minmax(0,1fr) 44px!important;padding:16px 8px!important}.m38gallery-lightbox .m38arrow{width:40px!important;height:40px!important}
    }
    @media(max-width:540px){
      body.single-product.wpbbshop-theme .llg-single-summary-panel,body.single-product.wpbbshop-theme .llg-single-gallery-panel{padding:15px!important}
      body.single-product.wpbbshop-theme .llg-single-buybox{padding:15px!important}
      body.single-product.wpbbshop-theme .llg-single-buybox form.cart{align-items:stretch!important}
      body.single-product.wpbbshop-theme .llg-single-buybox .single_add_to_cart_button,body.single-product.wpbbshop-theme .llg-single-buybox .wp-theme-add-to-quote{flex:1 1 100%!important;width:100%!important}
      body.single-product.wpbbshop-theme .wpbbshop-single-products-grid,body.wpbbshop-theme .wpbbshop-bootstrap-products{grid-template-columns:1fr!important}
      .m38gallery-lightbox{grid-template-columns:1fr!important;grid-template-rows:44px minmax(0,1fr) auto 44px!important}.m38gallery-lightbox .m38stage{grid-column:1!important;grid-row:2!important}.m38gallery-lightbox .m38prev{grid-column:1!important;grid-row:4!important;justify-self:start!important}.m38gallery-lightbox .m38next{grid-column:1!important;grid-row:4!important;justify-self:end!important}.m38gallery-lightbox .m38modal-thumbs{grid-column:1!important;grid-row:3!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v314_css', PHP_INT_MAX);
