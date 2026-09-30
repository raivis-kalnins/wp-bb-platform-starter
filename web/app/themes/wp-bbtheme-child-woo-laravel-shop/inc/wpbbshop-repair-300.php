<?php
/** WP BB Home & Garden 3.0 database/theme state repair. No image processing. */
defined('ABSPATH') || exit;

function wpbbshop_parent_demo_product_ids_300() {
    global $wpdb;
    $ids = $wpdb->get_col("SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID WHERE p.post_type IN ('product','product_variation') AND ((pm.meta_key='_sku' AND pm.meta_value LIKE 'DEMO-BUSINESS-%') OR (pm.meta_key='_wpbb_child_woo_demo_product' AND pm.meta_value='1'))");
    return array_values(array_unique(array_filter(array_map('absint',(array)$ids))));
}

function wpbbshop_cleanup_business_demo_300() {
    $deleted = 0;
    foreach (wpbbshop_parent_demo_product_ids_300() as $id) {
        if (get_post_meta($id,'_wpbbshop_demo_product',true)==='1') { continue; }
        if (wp_delete_post($id,true)) { $deleted++; }
    }
    if (taxonomy_exists('product_cat')) {
        foreach (array('accessories','apparel','bundles','home-living','office','tech') as $slug) {
            $term=get_term_by('slug',$slug,'product_cat');
            if (!$term || is_wp_error($term)) { continue; }
            $count=(int)$term->count;
            if ($count===0) { wp_delete_term((int)$term->term_id,'product_cat'); }
        }
    }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    return $deleted;
}

function wpbbshop_clean_theme_mods_300($lv=array(),$en=array()) {
    $mods=get_theme_mods(); if (!is_array($mods)) { $mods=array(); }
    $locations=array();
    if (!empty($lv['primary'])) { $locations['primary']=(int)$lv['primary']; $locations['primary___lv']=(int)$lv['primary']; }
    if (!empty($lv['footer'])) { $locations['footer']=(int)$lv['footer']; $locations['footer___lv']=(int)$lv['footer']; }
    if (!empty($lv['service'])) { $locations['service']=(int)$lv['service']; $locations['service___lv']=(int)$lv['service']; }
    if (!empty($en['primary'])) { $locations['primary___en']=(int)$en['primary']; }
    if (!empty($en['footer'])) { $locations['footer___en']=(int)$en['footer']; }
    if (!empty($en['service'])) { $locations['service___en']=(int)$en['service']; }
    $mods['nav_menu_locations']=$locations;
    $mods['woocommerce_catalog_columns']=4;
    $mods['woocommerce_catalog_rows']=4;
    update_option('theme_mods_'.get_option('stylesheet'),$mods,false);
}

function wpbbshop_make_menu_300($name,$items,$language='lv') {
    $menu=wp_get_nav_menu_object($name);
    $id=$menu ? (int)$menu->term_id : wp_create_nav_menu($name);
    if (!$id || is_wp_error($id)) { return 0; }
    foreach ((array)wp_get_nav_menu_items($id) as $item) { wp_delete_post($item->ID,true); }
    foreach ($items as $label=>$url) {
        wp_update_nav_menu_item($id,0,array('menu-item-title'=>$label,'menu-item-url'=>$url,'menu-item-status'=>'publish','menu-item-type'=>'custom'));
    }
    if (function_exists('pll_set_term_language')) { @pll_set_term_language($id,$language); }
    return (int)$id;
}

function wpbbshop_sync_menus_300() {
    $shop=function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $lv_primary=array('Sākums'=>home_url('/'),'Akcijas'=>add_query_arg('onsale','1',$shop),'Jaunumi'=>add_query_arg('orderby','date',$shop),'Instrumenti'=>home_url('/product-category/instrumenti/'),'Dārzam'=>home_url('/product-category/darza-tehnika/'),'Darbnīcai'=>home_url('/product-category/servisa-aprikojums/'),'Rezerves daļas'=>home_url('/product-category/rezerves-dalas/'),'Kontakti'=>wpbbshop_page_url('kontakti'));
    $lv_footer=array('Par mums'=>wpbbshop_page_url('par-mums'),'Piegāde un apmaksa'=>wpbbshop_page_url('piegade-un-apmaksa'),'Atgriešana un garantija'=>wpbbshop_page_url('atgriesana-un-garantija'),'Pirkšanas noteikumi'=>wpbbshop_page_url('pirksanas-noteikumi'));
    $lv_service=array('Kontakti'=>wpbbshop_page_url('kontakti'),'Mans konts'=>(function_exists('wc_get_page_permalink')?wc_get_page_permalink('myaccount'):home_url('/my-account/')),'Grozs'=>(function_exists('wc_get_cart_url')?wc_get_cart_url():home_url('/cart/')));
    $en_primary=array('Home'=>home_url('/'),'Offers'=>add_query_arg('onsale','1',$shop),'New'=>add_query_arg('orderby','date',$shop),'Tools'=>home_url('/product-category/instrumenti/'),'Garden'=>home_url('/product-category/darza-tehnika/'),'Workshop'=>home_url('/product-category/servisa-aprikojums/'),'Spare parts'=>home_url('/product-category/rezerves-dalas/'),'Contact'=>wpbbshop_page_url('kontakti'));
    $en_footer=array('About us'=>wpbbshop_page_url('par-mums'),'Delivery & payment'=>wpbbshop_page_url('piegade-un-apmaksa'),'Returns & warranty'=>wpbbshop_page_url('atgriesana-un-garantija'),'Terms'=>wpbbshop_page_url('pirksanas-noteikumi'));
    $en_service=array('Contact'=>wpbbshop_page_url('kontakti'),'My account'=>(function_exists('wc_get_page_permalink')?wc_get_page_permalink('myaccount'):home_url('/my-account/')),'Cart'=>(function_exists('wc_get_cart_url')?wc_get_cart_url():home_url('/cart/')));
    $lv=array('primary'=>wpbbshop_make_menu_300('WP BB Home & Garden LV — Galvenā',$lv_primary,'lv'),'footer'=>wpbbshop_make_menu_300('WP BB Home & Garden LV — Kājenes',$lv_footer,'lv'),'service'=>wpbbshop_make_menu_300('WP BB Home & Garden LV — Klientiem',$lv_service,'lv'));
    $en=array('primary'=>wpbbshop_make_menu_300('WP BB Home & Garden EN — Main',$en_primary,'en'),'footer'=>wpbbshop_make_menu_300('WP BB Home & Garden EN — Footer',$en_footer,'en'),'service'=>wpbbshop_make_menu_300('WP BB Home & Garden EN — Customer',$en_service,'en'));
    if (function_exists('pll_save_term_translations')) {
        foreach (array('primary','footer','service') as $k) { if ($lv[$k] && $en[$k]) { @pll_save_term_translations(array('lv'=>$lv[$k],'en'=>$en[$k])); } }
    }
    wpbbshop_clean_theme_mods_300($lv,$en);
    return array('lv'=>$lv,'en'=>$en);
}

function wpbbshop_clean_polylang_state_300($menus=array()) {
    $opts=get_option('polylang',array()); if (!is_array($opts)) { $opts=array(); }
    $opts['default_lang']='en'; $opts['hide_default']=true; $opts['media_support']=false; $opts['browser']=false; $opts['domains']=array('lv'=>'','en'=>'');
    $theme=get_option('stylesheet');
    $opts['nav_menus']=array($theme=>array(
        'primary'=>array('lv'=>(int)($menus['lv']['primary']??0),'en'=>(int)($menus['en']['primary']??0)),
        'footer'=>array('lv'=>(int)($menus['lv']['footer']??0),'en'=>(int)($menus['en']['footer']??0)),
        'service'=>array('lv'=>(int)($menus['lv']['service']??0),'en'=>(int)($menus['en']['service']??0)),
    ));
    update_option('polylang',$opts,false); update_option('WPLANG','en_GB',false);
    update_option('wp_theme_demo_polylang_setup_version','wpbbshop-3.0.0',false);
    update_option('wp_theme_demo_polylang_setup',array('default'=>'en','created'=>array(),'profile'=>'wpbbshop','assigned'=>0,'translations'=>0,'terms_assigned'=>0,'menu_locations'=>6,'language_menus'=>6,'term_translations'=>3,'time'=>time()),false);
    delete_transient('pll_languages_list');
}

function wpbbshop_apply_clean_options_300() {
    $o=get_option('wpbbshop_theme_options',array()); if (!is_array($o)) { $o=array(); }
    $o['logo_id']=''; $o['logo_width']='250'; $o['logo_height']='96'; $o['archive_sidebar']='0'; $o['products_per_row']='4'; $o['show_category_panel']='0'; $o['dark_header']='0'; $o['show_topbar']='1'; $o['sticky_header']='0';
    update_option('wpbbshop_theme_options',$o,false);
    update_option('woocommerce_coming_soon','no',false); update_option('woocommerce_store_pages_only','no',false);
}

function wpbbshop_repair_all_300() {
    if (function_exists('wpbbshop_ensure_wc_core_pages_v18')) { wpbbshop_ensure_wc_core_pages_v18(); }
    if (function_exists('wpbbshop_ensure_information_pages')) { wpbbshop_ensure_information_pages(); }
    $page=get_page_by_path('demo-homepage',OBJECT,'page');
    $args=array('post_title'=>'Home & Garden Demo','post_name'=>'demo-homepage','post_status'=>'publish','post_type'=>'page','post_content'=>'<!-- wp:shortcode -->[wpbbshop_home]<!-- /wp:shortcode -->');
    if ($page instanceof WP_Post) { $args['ID']=$page->ID; $front=wp_update_post($args,true); } else { $front=wp_insert_post($args,true); }
    if (!is_wp_error($front)) { update_option('show_on_front','page'); update_option('page_on_front',(int)$front); update_post_meta($front,'_wp_theme_demo_homepage',1); update_post_meta($front,'_wp_theme_demo_profile','wpbbshop'); if (function_exists('pll_set_post_language')) { @pll_set_post_language((int)$front,'en'); } }
    wpbbshop_apply_clean_options_300();
    $deleted=wpbbshop_cleanup_business_demo_300();
    if (function_exists('wpbbshop_polylang_sync_lv_en_235')) { wpbbshop_polylang_sync_lv_en_235(true); }
    if (function_exists('wpbbshop_green_seed_demo_products_lightweight')) { $seed=wpbbshop_green_seed_demo_products_lightweight(); } else { $seed=array('created'=>0,'updated'=>0); }
    $menus=wpbbshop_sync_menus_300();
    wpbbshop_clean_polylang_state_300($menus);
    if (function_exists('wpbbshop_v305_ensure_bilingual_pages')) { wpbbshop_v305_ensure_bilingual_pages(); }
    update_option('wp_theme_active_demo_profile','wpbbshop',false); update_option('wp_theme_demo_import_version','3.0.5',false); update_option('wpbbshop_repair_300','done',false);
    flush_rewrite_rules(false);
    return array('front'=>$front,'deleted'=>$deleted,'seed'=>$seed,'menus'=>$menus);
}

/* Stop parent demo automation and deprecated starter helpers. */
function wpbbshop_disable_parent_setup_300() {
    remove_action('wp_theme_before_demo_import','wpbb_child_woo_prepare_sector_demo',10);
    remove_action('wp_theme_after_demo_import','wp_theme_setup_demo_polylang_languages',5);
    remove_action('admin_init','wp_theme_maybe_setup_demo_polylang_languages',20);
    remove_action('admin_init','wp_theme_maybe_bind_demo_polylang_menu_locations',25);
}
add_action('after_setup_theme','wpbbshop_disable_parent_setup_300',PHP_INT_MAX);
add_action('admin_init','wpbbshop_disable_parent_setup_300',1);
add_filter('deprecated_function_trigger_error',function($trigger,$function){ return $function==='get_page_by_title' ? false : $trigger; },10,2);

/* Run one database-only repair after installing 3.0. No media/Imagick. */
/* v4 supersedes the destructive 3.0 repair pass; helpers remain for backwards compatibility. */

/* Hide parent demo products from every front-end query even before cleanup. */
add_action('pre_get_posts',function($q){ if (is_admin() || !($q instanceof WP_Query)) return; $pt=$q->get('post_type'); $product=$pt==='product'||(is_array($pt)&&in_array('product',$pt,true))||$q->is_post_type_archive('product')||$q->is_tax('product_cat')||$q->is_tax('product_tag'); if (!$product) return; $blocked=wpbbshop_parent_demo_product_ids_300(); if ($blocked) $q->set('post__not_in',array_values(array_unique(array_merge((array)$q->get('post__not_in'),$blocked)))); },999);

/* Starter Setup / Repair UI retired in 3.0.41. Keep the compatibility repair helpers above,
 * but do not expose a duplicate Repair / Sync screen in Appearance. */
add_action('admin_menu', function(){
    remove_submenu_page('themes.php', 'wp-theme-starter-setup');
}, 9999);
