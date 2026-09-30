<?php
/**
 * v3.0.9: isolated mobile header + real English storefront root + root-gap hardening.
 */
defined('ABSPATH') || exit;

function wpbbshop_v309_ensure_english_home() {
    if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations')) { return; }
    $front_id = absint(get_option('page_on_front'));
    if (!$front_id || get_post_type($front_id) !== 'page') { return; }
    @pll_set_post_language($front_id, 'en');
    $en_id = function_exists('pll_get_post') ? absint(pll_get_post($front_id, 'en')) : 0;
    if (!$en_id) {
        $existing = get_page_by_path('home-en', OBJECT, 'page');
        if ($existing instanceof WP_Post) {
            $en_id = (int)$existing->ID;
            wp_update_post(array('ID'=>$en_id,'post_status'=>'publish','post_title'=>'WP BB Home & Garden','post_content'=>'<!-- wp:shortcode -->[wpbbshop_home]<!-- /wp:shortcode -->'));
        } else {
            $en_id = wp_insert_post(array(
                'post_type'=>'page','post_status'=>'publish','post_title'=>'WP BB Home & Garden','post_name'=>'home-en',
                'post_content'=>'<!-- wp:shortcode -->[wpbbshop_home]<!-- /wp:shortcode -->',
            ));
        }
    }
    if ($en_id && !is_wp_error($en_id)) {
        @pll_set_post_language($en_id, 'en');
        @pll_save_post_translations(array('lv'=>$front_id,'en'=>(int)$en_id));
        update_post_meta($en_id, '_wp_theme_demo_homepage', 1);
        update_post_meta($en_id, '_wp_theme_demo_profile', 'wpbbshop');
    }
}
add_action('after_switch_theme', 'wpbbshop_v309_ensure_english_home', 90);
add_action('admin_init', function(){ if (current_user_can('manage_options')) { wpbbshop_v309_ensure_english_home(); } }, 1700);

/* If an older database still resolves /en/ to the parent theme Journal index, render the store homepage anyway. */
add_filter('template_include', function($template) {
    if (is_admin() || !function_exists('pll_current_language') || pll_current_language('slug') !== 'en') { return $template; }
    $path = isset($_SERVER['REQUEST_URI']) ? (string)parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
    $path = trim((string)$path, '/');
    if ($path === 'en' || (is_home() && !is_paged())) {
        $front = get_stylesheet_directory() . '/front-page.php';
        if (file_exists($front)) { return $front; }
    }
    return $template;
}, PHP_INT_MAX);

add_filter('pre_get_document_title', function($title) {
    if (function_exists('pll_current_language') && pll_current_language('slug') === 'en') {
        $path = isset($_SERVER['REQUEST_URI']) ? trim((string)parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/') : '';
        if ($path === 'en') { return 'WP BB Home & Garden — Tools, garden and workshop equipment'; }
    }
    return $title;
}, 50);

function wpbbshop_v309_critical_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v309-critical">
    html,body{margin-block-start:0!important;padding-block-start:0!important}
    body.wpbbshop-theme{margin-top:0!important;padding-top:0!important}
    body.wpbbshop-theme .wp-site-blocks,
    body.wpbbshop-theme>.wp-site-blocks{margin-top:0!important;margin-bottom:0!important;padding-top:0!important;padding-bottom:0!important;padding-block-start:0!important;padding-block-end:0!important}
    body.wpbbshop-theme .wp-site-blocks>*:first-child{margin-block-start:0!important;margin-top:0!important}
    body.wpbbshop-theme .wp-site-blocks>*:last-child{margin-block-end:0!important;margin-bottom:0!important}
    body.wpbbshop-theme header.wp-block-template-part:first-child{margin-block-start:0!important;padding-block-start:0!important}
    body.wpbbshop-theme .llg-header{margin-block-start:0!important;margin-top:0!important}
    body.wpbbshop-theme:not(.llg-adminbar-visible){padding-top:0!important}
    body.wpbbshop-theme.llg-adminbar-visible{padding-top:32px!important}
    .llg-mobile-shell{display:none!important}

    @media(max-width:820px){
      body.wpbbshop-theme.llg-adminbar-visible{padding-top:46px!important}
      body.wpbbshop-theme .llg-desktop-shell{display:none!important}
      body.wpbbshop-theme .llg-mobile-shell{display:block!important;background:#fff!important;color:#0d293d!important;width:100%!important;margin:0!important;padding:0!important}
      body.wpbbshop-theme .llg-header{background:#fff!important;overflow:visible!important}
      body.wpbbshop-theme .llg-mobile-top{height:36px!important;min-height:36px!important;background:#10364f!important;color:#fff!important;display:flex!important;align-items:center!important;justify-content:space-between!important;padding:0 12px!important;margin:0!important;font-size:11px!important;font-weight:700!important}
      body.wpbbshop-theme .llg-mobile-top>span:first-child{display:flex!important;align-items:center!important;gap:7px!important}
      body.wpbbshop-theme .llg-mobile-top .wpbbshop-green-icon{width:15px!important;height:15px!important;color:#67d65f!important}
      body.wpbbshop-theme .llg-mobile-top .llg-lang-switcher{display:flex!important;align-items:center!important;gap:5px!important}
      body.wpbbshop-theme .llg-mobile-top .llg-lang-link{color:#fff!important;font-size:10px!important;font-weight:850!important}
      body.wpbbshop-theme .llg-mobile-brand{height:auto!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:12px 10px 9px!important;margin:0!important;line-height:0!important}
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo img{display:block!important;width:164px!important;max-width:164px!important;height:auto!important;margin:0!important}

      body.wpbbshop-theme .llg-mobile-search{box-sizing:border-box!important;display:grid!important;grid-template-columns:112px minmax(0,1fr) 46px!important;width:calc(100% - 20px)!important;height:46px!important;margin:0 10px 8px!important;padding:0!important;border:1px solid #d8e0e5!important;border-radius:8px!important;background:#fff!important;overflow:visible!important;position:relative!important}
      body.wpbbshop-theme .llg-mobile-search-cat{position:relative!important;min-width:0!important;border-right:1px solid #d8e0e5!important}
      body.wpbbshop-theme .llg-mobile-search-cat:after{content:""!important;position:absolute!important;right:10px!important;top:17px!important;width:6px!important;height:6px!important;border-right:2px solid #758896!important;border-bottom:2px solid #758896!important;transform:rotate(45deg)!important;pointer-events:none!important}
      body.wpbbshop-theme .llg-mobile-search select{appearance:none!important;-webkit-appearance:none!important;width:100%!important;height:44px!important;border:0!important;background:transparent!important;color:#102b3c!important;padding:0 24px 0 9px!important;font-size:10px!important;outline:0!important}
      body.wpbbshop-theme .llg-mobile-search-field{position:relative!important;min-width:0!important}
      body.wpbbshop-theme .llg-mobile-search input[type=search]{box-sizing:border-box!important;width:100%!important;height:44px!important;border:0!important;background:#fff!important;padding:0 10px!important;font-size:11px!important;color:#102b3c!important;outline:0!important;box-shadow:none!important}
      body.wpbbshop-theme .llg-mobile-search>button{width:46px!important;height:44px!important;min-width:46px!important;border:0!important;border-radius:0 7px 7px 0!important;background:#2e994e!important;color:#fff!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:0!important;margin:0!important}
      body.wpbbshop-theme .llg-mobile-search>button .wpbbshop-green-icon{width:20px!important;height:20px!important}
      body.wpbbshop-theme .llg-mobile-search .wpbbshop-search-results{top:50px!important;left:-113px!important;right:-47px!important;width:auto!important;max-height:60vh!important;overflow:auto!important;z-index:300!important}

      body.wpbbshop-theme .llg-mobile-actions{box-sizing:border-box!important;width:100%!important;display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:5px!important;margin:0!important;padding:0 10px 9px!important}
      body.wpbbshop-theme .llg-mobile-actions>a,
      body.wpbbshop-theme .llg-mobile-actions>button{box-sizing:border-box!important;position:relative!important;min-width:0!important;width:100%!important;height:56px!important;margin:0!important;padding:5px 2px!important;border:1px solid #e4eaee!important;border-radius:8px!important;background:#fff!important;color:#0d293d!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;gap:3px!important;font-family:inherit!important;font-size:9px!important;font-weight:800!important;line-height:1!important;text-decoration:none!important;box-shadow:none!important}
      body.wpbbshop-theme .llg-mobile-actions .wpbbshop-green-icon{width:20px!important;height:20px!important;margin:0!important}
      body.wpbbshop-theme .llg-mobile-actions span{display:block!important;white-space:nowrap!important;font-size:9px!important;line-height:1!important}
      body.wpbbshop-theme .llg-mobile-actions em{position:absolute!important;top:3px!important;right:5px!important;min-width:16px!important;height:16px!important;padding:0 4px!important;border-radius:999px!important;background:#2e994e!important;color:#fff!important;font-style:normal!important;font-size:8px!important;line-height:16px!important;text-align:center!important}

      body.wpbbshop-theme .llg-mobile-navbar{position:relative!important;display:grid!important;grid-template-columns:1fr 1fr!important;width:100%!important;height:48px!important;margin:0!important;padding:0!important;background:#10364f!important;z-index:190!important}
      body.wpbbshop-theme .llg-mobile-catalog-wrap{position:relative!important;min-width:0!important}
      body.wpbbshop-theme .llg-mobile-catalog-button,
      body.wpbbshop-theme .llg-mobile-menu-toggle{box-sizing:border-box!important;width:100%!important;height:48px!important;min-height:48px!important;margin:0!important;padding:0 12px!important;border:0!important;border-radius:0!important;color:#fff!important;display:flex!important;align-items:center!important;justify-content:center!important;gap:8px!important;font-size:11px!important;font-weight:850!important;line-height:1!important}
      body.wpbbshop-theme .llg-mobile-catalog-button{background:#2e994e!important}
      body.wpbbshop-theme .llg-mobile-menu-toggle{background:#10364f!important}
      body.wpbbshop-theme .llg-mobile-catalog-button .wpbbshop-green-icon,
      body.wpbbshop-theme .llg-mobile-menu-toggle .wpbbshop-green-icon{width:19px!important;height:19px!important}
      body.wpbbshop-theme .llg-mobile-catalog-menu{display:none!important;position:absolute!important;top:48px!important;left:0!important;width:min(330px,96vw)!important;max-height:66vh!important;overflow:auto!important;background:#fff!important;border:1px solid #dfe5e9!important;box-shadow:0 18px 38px rgba(13,41,61,.2)!important;z-index:310!important;padding:7px!important}
      body.wpbbshop-theme .llg-mobile-catalog-wrap.is-open .llg-mobile-catalog-menu{display:block!important}
      body.wpbbshop-theme .llg-mobile-catalog-menu .wpbbshop-cat-panel-item{min-height:44px!important}

      body.wpbbshop-theme .llg-mobile-menu-panel{position:absolute!important;top:100%!important;left:0!important;right:0!important;width:100%!important;z-index:305!important;background:#fff!important;box-shadow:0 20px 42px rgba(13,41,61,.22)!important;max-height:70vh!important;overflow:auto!important}
      body.wpbbshop-theme .llg-mobile-menu-panel[hidden]{display:none!important}
      body.wpbbshop-theme .llg-mobile-menu-inner{width:calc(100% - 20px)!important;max-width:none!important;margin:0 auto!important;padding:12px 0 16px!important}
      body.wpbbshop-theme .llg-mobile-primary .wpbbshop-menu{display:grid!important;grid-template-columns:1fr 1fr!important;gap:0!important;margin:8px 0 10px!important;padding:0!important}
      body.wpbbshop-theme .llg-mobile-primary .wpbbshop-menu a{min-height:44px!important;padding:9px 10px!important;font-size:12px!important}
      body.wpbbshop-theme .llg-mobile-utility{font-size:11px!important}
      body.wpbbshop-theme .llg-home{padding-top:10px!important}
    }

    @media(max-width:390px){
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo,
      body.wpbbshop-theme .llg-mobile-brand .wpbbshop-approved-logo img{width:148px!important;max-width:148px!important}
      body.wpbbshop-theme .llg-mobile-search{grid-template-columns:98px minmax(0,1fr) 44px!important;margin-left:8px!important;margin-right:8px!important;width:calc(100% - 16px)!important}
      body.wpbbshop-theme .llg-mobile-search .wpbbshop-search-results{left:-99px!important;right:-45px!important}
      body.wpbbshop-theme .llg-mobile-actions{padding-left:8px!important;padding-right:8px!important;gap:4px!important}
      body.wpbbshop-theme .llg-mobile-actions>a,body.wpbbshop-theme .llg-mobile-actions>button{height:54px!important}
      body.wpbbshop-theme .llg-mobile-actions span{font-size:8px!important}
      body.wpbbshop-theme .llg-mobile-primary .wpbbshop-menu{grid-template-columns:1fr!important}
    }
    </style>
    <?php
}
add_action('wp_head','wpbbshop_v309_critical_css',PHP_INT_MAX);

function wpbbshop_v309_mobile_js() {
    if (is_admin()) { return; }
    ?>
    <script id="wpbbshop-v309-mobile-js">
    (function(){
      function ready(fn){if(document.readyState!=='loading')fn();else document.addEventListener('DOMContentLoaded',fn);}
      ready(function(){
        var wrap=document.querySelector('.llg-mobile-catalog-wrap'),btn=wrap?wrap.querySelector('.llg-mobile-catalog-button'):null;
        if(wrap&&btn){
          btn.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();var o=wrap.classList.toggle('is-open');btn.setAttribute('aria-expanded',o?'true':'false');var panel=document.getElementById('llg-mobile-menu-panel');if(o&&panel){panel.setAttribute('hidden','hidden');var mt=document.querySelector('.llg-mobile-menu-toggle');if(mt)mt.setAttribute('aria-expanded','false');}});
          document.addEventListener('click',function(e){if(!wrap.contains(e.target)){wrap.classList.remove('is-open');btn.setAttribute('aria-expanded','false');}});
        }
        var mt=document.querySelector('.llg-mobile-menu-toggle');if(mt){mt.addEventListener('click',function(){if(wrap){wrap.classList.remove('is-open');if(btn)btn.setAttribute('aria-expanded','false');}},true);}
        document.documentElement.style.setProperty('margin-top','0px','important');
        var blocks=document.querySelector('.wp-site-blocks');if(blocks){blocks.style.setProperty('padding-top','0px','important');blocks.style.setProperty('margin-top','0px','important');}
      });
    })();
    </script>
    <?php
}
add_action('wp_footer','wpbbshop_v309_mobile_js',PHP_INT_MAX);
