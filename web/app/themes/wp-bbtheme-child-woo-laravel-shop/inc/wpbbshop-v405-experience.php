<?php
/** WP BB Home & Garden 4.0.5 final experience layer. */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V405_VERSION')) define('WPBBSHOP_V405_VERSION', '4.0.5');

/* Keep parent site-essential cookie/PWA/footer UI out of this bespoke store. */
add_filter('wp_theme_site_essentials_enabled', '__return_false', PHP_INT_MAX);

/* ---------- Language routing ---------- */
add_filter('query_vars', function($vars){ $vars[]='wpbbshop_lv_home'; return $vars; });
add_action('init', function(){
    add_rewrite_rule('^lv/?$', 'index.php?lang=lv&wpbbshop_lv_home=1', 'top');
    if ((string)get_option('wpbbshop_v405_rewrite_version','') !== WPBBSHOP_V405_VERSION) {
        flush_rewrite_rules(false);
        update_option('wpbbshop_v405_rewrite_version', WPBBSHOP_V405_VERSION, false);
    }
}, 8);
add_action('template_redirect', function(){
    if (!get_query_var('wpbbshop_lv_home')) return;
    status_header(200); nocache_headers();
    get_header(); echo do_shortcode('[wpbbshop_home]'); get_footer(); exit;
}, 0);

/* Do not 404 demo/single products merely because Polylang has not yet got a translation partner. */
add_action('pre_get_posts', function($q){
    if (is_admin() || !$q->is_main_query()) return;
    if ($q->get('post_type') === 'product' || $q->get('product') || $q->get('name')) {
        $q->set('lang','');
    }
}, 5);

/* Repair EN/LV homes once after update. */
add_action('admin_init', function(){
    if ((string)get_option('wpbbshop_v405_language_repair','') === WPBBSHOP_V405_VERSION) return;
    if (function_exists('wpbbshop_v400_ensure_home_translations')) wpbbshop_v400_ensure_home_translations();
    delete_option('wpbbshop_v315_feed_publish_error');
    update_option('wpbbshop_v405_language_repair', WPBBSHOP_V405_VERSION, false);
}, 7);

/* ---------- Demo image reliability ---------- */
function wpbbshop_v405_demo_image_url($product){
    if (!$product || !is_a($product,'WC_Product')) return '';
    $id=$product->get_id();
    if (get_post_meta($id,'_wpbbshop_demo_product',true)!=='1') return '';
    $file=basename((string)get_post_meta($id,'_wpbbshop_demo_image',true));
    $files=function_exists('wpbbshop_v400_demo_images') ? wpbbshop_v400_demo_images() : array();
    if (!$file || !in_array($file,$files,true)) {
        $sku=(string)$product->get_sku(); $n=0;
        if (preg_match('/HG-DEMO-(\d+)/',$sku,$m)) $n=max(0,(int)$m[1]-1);
        if ($files) $file=$files[$n % count($files)];
    }
    if (!$file) return '';
    $path=get_stylesheet_directory().'/assets/demo-products/'.$file;
    return file_exists($path) ? get_stylesheet_directory_uri().'/assets/demo-products/'.rawurlencode($file) : '';
}

add_filter('woocommerce_product_get_image', function($html,$product,$size,$attr,$placeholder){
    $url=wpbbshop_v405_demo_image_url($product);
    if (!$url) return $html;
    $name=$product->get_name();
    return '<img src="'.esc_url($url).'" alt="'.esc_attr($name).'" loading="lazy" decoding="async" class="wpbbshop-demo-product-image">';
}, PHP_INT_MAX, 5);

/* Single product template uses its own gallery renderer; give demo products a guaranteed local image. */
function wpbbshop_v405_single_demo_image($product){
    $url=wpbbshop_v405_demo_image_url($product);
    if (!$url) return '';
    return '<figure class="llg-single-main-image wpbbshop-v405-single-demo"><img src="'.esc_url($url).'" alt="'.esc_attr($product->get_name()).'" fetchpriority="high" decoding="async"></figure>';
}

/* ---------- Clean logo + favicon ---------- */
add_filter('get_custom_logo', function($html){ return $html; }, PHP_INT_MAX);
function wpbbshop_v405_brand_mark(){
    return '<span class="wpbb-v405-brandmark" aria-hidden="true"><svg viewBox="0 0 64 64"><path d="M12 37 32 17l20 20v16H12z" fill="#fff"/><path d="M32 17c2-9 9-14 20-13-2 10-8 15-20 16" fill="none" stroke="#b8f36b" stroke-width="5" stroke-linecap="round"/><path d="M32 20c-4-7-10-10-18-8 2 8 8 12 18 12" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round"/></svg></span>';
}
add_filter('wpbbshop_logo_markup', function($html){ return $html; });

/* ---------- Child-owned cookie consent ---------- */
function wpbbshop_v405_cookie_markup(){
    if (is_admin()) return;
    $lv=function_exists('wpbbshop_v400_current_language') && wpbbshop_v400_current_language()==='lv';
    ?>
    <div class="wpbb-v405-cookie" data-wpbb-cookie hidden>
      <div><strong><?php echo esc_html($lv?'Privātuma izvēles':'Privacy choices'); ?></strong><p><?php echo esc_html($lv?'Nepieciešamās sīkdatnes nodrošina veikala darbību. Papildu analītikas un mārketinga sīkdatnes tiek izmantotas tikai ar jūsu piekrišanu.':'Essential cookies keep the shop working. Optional analytics and marketing cookies are used only with your consent.'); ?></p></div>
      <div class="wpbb-v405-cookie-actions"><button type="button" data-wpbb-cookie-reject><?php echo esc_html($lv?'Atteikt papildu':'Reject optional'); ?></button><button class="is-primary" type="button" data-wpbb-cookie-accept><?php echo esc_html($lv?'Pieņemt':'Accept'); ?></button></div>
    </div>
    <script id="wpbb-v405-cookie-js">(function(){var k='wpbbCookieConsentV1',b=document.querySelector('[data-wpbb-cookie]');if(!b)return;try{if(!localStorage.getItem(k))b.hidden=false}catch(e){b.hidden=false}b.addEventListener('click',function(e){var v=e.target.hasAttribute('data-wpbb-cookie-accept')?'all':(e.target.hasAttribute('data-wpbb-cookie-reject')?'essential':'');if(!v)return;try{localStorage.setItem(k,v)}catch(x){}b.hidden=true;});})();</script>
    <?php
}
add_action('wp_footer','wpbbshop_v405_cookie_markup',90);

/* ---------- TOP products (replaces Latvian-only monthly 3 picks) ---------- */
if (!defined('WPBBSHOP_TOP_OPTION')) define('WPBBSHOP_TOP_OPTION','wpbbshop_top_products_v405');
function wpbbshop_v405_top_settings(){
    $saved=get_option(WPBBSHOP_TOP_OPTION,array()); if(!is_array($saved))$saved=array();
    return wp_parse_args($saved,array('enabled'=>'1','title_en'=>'TOP products','title_lv'=>'TOP preces','products'=>array()));
}
function wpbbshop_v405_top_ids(){
    $s=wpbbshop_v405_top_settings(); $ids=array_values(array_unique(array_filter(array_map('absint',(array)$s['products']))));
    if (!$ids && function_exists('wc_get_product_ids_on_sale')) $ids=array_slice(array_map('absint',(array)wc_get_product_ids_on_sale()),0,12);
    return array_slice($ids,0,20);
}
add_action('admin_init', function(){ register_setting('wpbbshop_top_group',WPBBSHOP_TOP_OPTION,array('sanitize_callback'=>function($in){
    $in=is_array($in)?$in:array(); $raw=isset($in['products'])?(array)$in['products']:array();
    return array('enabled'=>!empty($in['enabled'])?'1':'0','title_en'=>sanitize_text_field($in['title_en']??'TOP products'),'title_lv'=>sanitize_text_field($in['title_lv']??'TOP preces'),'products'=>array_slice(array_values(array_unique(array_filter(array_map('absint',$raw)))),0,20));
})); });
add_action('admin_menu', function(){
    remove_submenu_page('themes.php','wpbbshop-monthly-picks');
    add_theme_page('TOP products','TOP products','manage_options','wpbbshop-top-products','wpbbshop_v405_top_admin');
},9999);
function wpbbshop_v405_top_admin(){
    if(!current_user_can('manage_options'))return; $s=wpbbshop_v405_top_settings();
    $products=function_exists('wc_get_products')?wc_get_products(array('status'=>'publish','limit'=>80,'orderby'=>'date','order'=>'DESC')):array();
    ?>
    <div class="wrap wpbb-v405-top-admin"><h1>TOP products</h1><p>Select up to 20 products. The storefront shows them as a horizontal swipe/slider row on desktop and mobile.</p>
    <form method="post" action="options.php"><?php settings_fields('wpbbshop_top_group'); ?>
    <p><label><input type="checkbox" name="<?php echo esc_attr(WPBBSHOP_TOP_OPTION); ?>[enabled]" value="1" <?php checked($s['enabled'],'1'); ?>> Enable TOP products slider</label></p>
    <table class="form-table"><tr><th>English title</th><td><input class="regular-text" name="<?php echo esc_attr(WPBBSHOP_TOP_OPTION); ?>[title_en]" value="<?php echo esc_attr($s['title_en']); ?>"></td></tr><tr><th>Latvian title</th><td><input class="regular-text" name="<?php echo esc_attr(WPBBSHOP_TOP_OPTION); ?>[title_lv]" value="<?php echo esc_attr($s['title_lv']); ?>"></td></tr></table>
    <h2>Products</h2><div class="wpbb-v405-top-selects">
    <?php for($i=0;$i<12;$i++): $selected=isset($s['products'][$i])?absint($s['products'][$i]):0; ?>
      <label>TOP <?php echo $i+1; ?><select name="<?php echo esc_attr(WPBBSHOP_TOP_OPTION); ?>[products][]"><option value="">— Select product —</option><?php foreach($products as $p): ?><option value="<?php echo esc_attr($p->get_id()); ?>" <?php selected($selected,$p->get_id()); ?>><?php echo esc_html($p->get_name().' — '.$p->get_sku()); ?></option><?php endforeach; ?></select></label>
    <?php endfor; ?></div><?php submit_button('Save TOP products'); ?></form></div>
    <style>.wpbb-v405-top-selects{display:grid;grid-template-columns:repeat(3,minmax(260px,1fr));gap:14px;max-width:1200px}.wpbb-v405-top-selects label{display:grid;gap:6px;font-weight:700}.wpbb-v405-top-selects select{width:100%}@media(max-width:900px){.wpbb-v405-top-selects{grid-template-columns:1fr}}</style>
    <?php
}
function wpbbshop_v405_top_products_html($is_en=true){
    $s=wpbbshop_v405_top_settings(); if($s['enabled']!=='1'||!class_exists('WooCommerce'))return '';
    $ids=wpbbshop_v405_top_ids(); if(!$ids)return '';
    $title=$is_en?$s['title_en']:$s['title_lv']; ob_start(); ?>
    <section class="wpbb-v405-top"><div class="wpbb-v405-top-head"><span>TOP</span><h2><?php echo esc_html($title); ?></h2><div class="wpbb-v405-arrows"><button type="button" data-top-prev aria-label="Previous">‹</button><button type="button" data-top-next aria-label="Next">›</button></div></div><div class="wpbb-v405-top-track" data-top-track>
    <?php foreach($ids as $id): $p=wc_get_product($id); if(!$p)continue; ?><article class="wpbb-v405-top-card"><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo wp_kses_post($p->get_image('woocommerce_thumbnail')); ?><strong><?php echo esc_html($p->get_name()); ?></strong><span><?php echo wp_kses_post($p->get_price_html()); ?></span></a></article><?php endforeach; ?>
    </div></section><script>(function(){document.querySelectorAll('.wpbb-v405-top').forEach(function(s){var t=s.querySelector('[data-top-track]');if(!t)return;var d=function(x){t.scrollBy({left:x*t.clientWidth*.75,behavior:'smooth'})};var p=s.querySelector('[data-top-prev]'),n=s.querySelector('[data-top-next]');if(p)p.onclick=function(){d(-1)};if(n)n.onclick=function(){d(1)};});})();</script>
    <?php return ob_get_clean();
}

/* ---------- Pattern Library ---------- */
add_action('init', function(){
    if (!function_exists('register_block_pattern_category')) return;
    register_block_pattern_category('wpbbshop',array('label'=>__('Home & Garden','wpbbshop')));
    if (function_exists('register_block_pattern')) {
        register_block_pattern('wpbbshop/home-hero',array('title'=>__('Home & Garden hero','wpbbshop'),'categories'=>array('wpbbshop'),'content'=>'<!-- wp:shortcode -->[wpbbshop_home]<!-- /wp:shortcode -->'));
        register_block_pattern('wpbbshop/top-products',array('title'=>__('TOP products slider','wpbbshop'),'categories'=>array('wpbbshop'),'content'=>'<!-- wp:shortcode -->[wpbbshop_top_products]<!-- /wp:shortcode -->'));
    }
},40);
add_shortcode('wpbbshop_top_products',function(){ return wpbbshop_v405_top_products_html(function_exists('wpbbshop_v400_current_language')?wpbbshop_v400_current_language()!=='lv':true); });

/* ---------- Admin notice contrast ---------- */
add_action('admin_head', function(){ ?><style id="wpbb-v405-admin-visibility">.wpbb-v400-admin-hero+.notice,.wpbb-v400-admin .notice{margin:14px 0!important;border-left-width:5px!important;background:#fff!important;color:#102b1f!important;box-shadow:0 3px 16px rgba(0,0,0,.08)!important}.wpbb-v400-admin .notice p{color:#102b1f!important}.wpbb-v400-admin .notice-dismiss:before{color:#173b2a!important}</style><?php },999);

/* Frontend polish for cookies/TOP/product image. */
add_action('wp_head', function(){ ?><style id="wpbb-v405-experience-css">
.wpbb-v405-cookie{position:fixed;z-index:999999;left:18px;right:18px;bottom:18px;max-width:1180px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:24px;padding:18px 20px;background:#fff;border:1px solid #d9e5dc;border-radius:16px;box-shadow:0 18px 60px rgba(5,32,18,.2);color:#17352a}.wpbb-v405-cookie[hidden]{display:none!important}.wpbb-v405-cookie p{margin:4px 0 0;max-width:780px;color:#53665d}.wpbb-v405-cookie-actions{display:flex;gap:8px;flex-wrap:wrap}.wpbb-v405-cookie button{min-height:40px;padding:0 15px;border:1px solid #1e7f46;border-radius:8px;background:#fff;color:#1b633b;font-weight:800}.wpbb-v405-cookie button.is-primary{background:#1e8e4d;color:#fff}.wpbb-v405-top{margin:24px 0;padding:22px;border-radius:18px;background:linear-gradient(135deg,#123b26,#1d6b3c);color:#fff;overflow:hidden}.wpbb-v405-top-head{display:flex;align-items:center;gap:12px;margin-bottom:16px}.wpbb-v405-top-head>span{background:#d7ff7d;color:#17351f;padding:5px 9px;border-radius:999px;font-weight:900;font-size:11px}.wpbb-v405-top-head h2{margin:0;color:#fff;flex:1}.wpbb-v405-arrows{display:flex;gap:7px}.wpbb-v405-arrows button{width:38px;height:38px;border:1px solid rgba(255,255,255,.35);border-radius:50%;background:rgba(255,255,255,.12);color:#fff;font-size:24px}.wpbb-v405-top-track{display:flex;gap:14px;overflow:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding-bottom:3px}.wpbb-v405-top-track::-webkit-scrollbar{display:none}.wpbb-v405-top-card{flex:0 0 210px;scroll-snap-align:start;background:#fff;border-radius:13px;overflow:hidden}.wpbb-v405-top-card a{display:grid;gap:8px;padding:12px;color:#163629!important;text-decoration:none!important}.wpbb-v405-top-card img{width:100%;aspect-ratio:1/1;object-fit:contain;background:#f5faf6;border-radius:9px}.wpbb-v405-top-card strong{line-height:1.3}.wpbb-v405-top-card .price,.wpbb-v405-top-card span{color:#1c7b40;font-weight:900}.wpbb-v405-single-demo img{display:block;width:100%;max-height:620px;object-fit:contain;background:#f6faf7;border-radius:14px}.wpbbshop-demo-product-image{object-fit:contain}@media(max-width:720px){.wpbb-v405-cookie{align-items:flex-start;flex-direction:column}.wpbb-v405-top-card{flex-basis:72vw}}
</style><?php },999);
