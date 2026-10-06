<?php
/**
 * WP BB HOME & GARDEN Green Storefront v2.
 * Distinct Latvian WooCommerce storefront with an optional original demo catalogue.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_GREEN_V2')) {
    define('WPBBSHOP_GREEN_V2', '3.0.46');
}

function wpbbshop_green_icon($name, $class = '') {
    $paths = array(
        'location' => '<path d="M12 21s7-5.2 7-12a7 7 0 1 0-14 0c0 6.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
        'truck' => '<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'store' => '<path d="M4 10v10h16V10"/><path d="M3 10 5 4h14l2 6"/><path d="M8 20v-6h8v6"/><path d="M3 10c0 2 3 2 3 0 0 2 3 2 3 0 0 2 3 2 3 0 0 2 3 2 3 0 0 2 3 2 3 0 0 2 3 2 3 0"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 5 5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 4-7 8-7s7 2 8 7"/>',
        'heart' => '<path d="M20.5 5.5a5 5 0 0 0-7.1 0L12 6.9l-1.4-1.4a5 5 0 0 0-7.1 7.1L12 21l8.5-8.4a5 5 0 0 0 0-7.1Z"/>',
        'compare' => '<path d="M7 7h13l-3-3m3 3-3 3M17 17H4l3 3m-3-3 3-3"/>',
        'cart' => '<path d="M3 4h2l2 11h10l3-8H7"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'facebook' => '<path d="M14 8h3V4h-3c-3 0-5 2-5 5v3H6v4h3v5h4v-5h3l1-4h-4V9c0-.7.3-1 1-1Z" fill="currentColor" stroke="none"/>',
        'shield' => '<path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'return' => '<path d="M9 7H5v-4"/><path d="M5 7c2-3 5-4 8-3a8 8 0 1 1-5 15"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 3.1a2 2 0 0 1-.6 1.8L7.7 10a16 16 0 0 0 6.3 6.3l1.4-1.3a2 2 0 0 1 1.8-.6l3.1.5a2 2 0 0 1 1.7 2Z"/>',
        'mail' => '<path d="M4 6h16v12H4z"/><path d="m4 8 4 3 8-6"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1"/>',
        'percent' => '<circle cx="7" cy="7" r="2"/><circle cx="17" cy="17" r="2"/><path d="M18 6 6 18"/>',
    );
    if (!isset($paths[$name])) { return ''; }
    return '<svg class="wpbbshop-green-icon ' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $paths[$name] . '</svg>';
}

function wpbbshop_green_language_switcher_234() {
    if (function_exists('wpbbshop_v407_language_switcher')) { return wpbbshop_v407_language_switcher(); }
    if (function_exists('wpbbshop_v406_language_switcher')) { return wpbbshop_v406_language_switcher(); }
    $links = array();
    if (function_exists('pll_the_languages')) {
        $raw = pll_the_languages(array('raw'=>1,'hide_if_empty'=>0,'hide_if_no_translation'=>0));
        if (is_array($raw)) {
            foreach (array('en','lv') as $slug) {
                if (!empty($raw[$slug]) && is_array($raw[$slug])) {
                    $links[] = '<a class="llg-lang-link' . (!empty($raw[$slug]['current_lang']) ? ' is-current' : '') . '" href="' . esc_url($raw[$slug]['url']) . '">' . esc_html(strtoupper($slug)) . '</a>';
                }
            }
        }
    }
    if (!$links) { $links = array('<span class="llg-lang-link is-current">EN</span>','<span class="llg-lang-link">LV</span>'); }
    return '<span class="llg-lang-switcher">' . implode('<span class="llg-lang-sep">/</span>', $links) . '</span>';
}

function wpbbshop_green_category_label_309($name, $slug = '') {
    if (!(function_exists('wpbbshop_v305_is_english') && wpbbshop_v305_is_english())) { return $name; }
    $key = sanitize_title($slug ?: $name);
    $map = array(
        'instrumenti'=>'Tools', 'specinstrumenti'=>'Specialist tools', 'servisa-aprikojums'=>'Workshop equipment',
        'darza-tehnika'=>'Garden equipment', 'rezerves-dalas'=>'Spare parts', 'saimniecibas-preces'=>'Home & utility',
    );
    return isset($map[$key]) ? $map[$key] : $name;
}

function wpbbshop_green_category_links_309($limit = 12) {
    if (!class_exists('WooCommerce')) { return ''; }
    $terms = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>true,'number'=>absint($limit),'parent'=>0,'orderby'=>'name','order'=>'ASC'));
    if (is_wp_error($terms) || !$terms) { return ''; }
    $blocked = array('accessories','apparel','bundles','home-living','office','tech');
    $terms = array_values(array_filter((array)$terms, function($term) use ($blocked){ return !empty($term->slug) && !in_array($term->slug,$blocked,true); }));
    if (function_exists('pll_get_term_language') && function_exists('wpbbshop_v400_current_language')) {
        $wanted = wpbbshop_v400_current_language();
        $terms = array_values(array_filter($terms, function($term) use ($wanted){
            $lang = pll_get_term_language($term->term_id, 'slug');
            return !$lang || $lang === $wanted;
        }));
    }
    if (function_exists('wpbbshop_category_display_order')) { $terms = wpbbshop_category_display_order($terms); }
    $out='';
    foreach ($terms as $term) {
        $img = function_exists('wpbbshop_category_icon_html') ? wpbbshop_category_icon_html($term) : '';
        $out .= '<a class="wpbbshop-cat-panel-item" href="'.esc_url(get_term_link($term)).'">'.$img.'<span>'.esc_html(wpbbshop_green_category_label_309($term->name,$term->slug)).'</span></a>';
    }
    return $out;
}

function wpbbshop_green_header_html() {
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    $cart_count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    $cart_total = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_total() : '';
    $wishlist_url = function_exists('wpbbshop_list_url') ? wpbbshop_list_url('wishlist') : home_url('/');
    $compare_url = function_exists('wpbbshop_list_url') ? wpbbshop_list_url('compare') : home_url('/');
    $wishlist_count = function_exists('wpbbshop_get_list_items') ? count(wpbbshop_get_list_items('wishlist')) : 0;
    $compare_count = function_exists('wpbbshop_get_list_items') ? count(wpbbshop_get_list_items('compare')) : 0;
    $facebook = wpbbshop_get_theme_option('social_facebook', '');
    $instagram = wpbbshop_get_theme_option('social_instagram', '#');
    $phone = trim((string) wpbbshop_get_theme_option('phone', ''));
    $email = trim((string) wpbbshop_get_theme_option('email', ''));
    $is_en = function_exists('wpbbshop_v305_is_english') ? wpbbshop_v305_is_english() : false;
    $is_uk = function_exists('wpbbshop_v414_is_uk_store') && wpbbshop_v414_is_uk_store();
    $store_address = function_exists('wpbbshop_v414_store_address') ? wpbbshop_v414_store_address() : 'Bauskas 63 - 1a/k1, Riga, LV-1004';
    $delivery_label = function_exists('wpbbshop_v414_delivery_label') ? wpbbshop_v414_delivery_label($is_en) : ($is_en ? 'Delivery across Latvia' : 'Piegāde visā Latvijā');
    $home = function_exists('wpbbshop_v407_language_home_url') ? wpbbshop_v407_language_home_url($is_en ? 'en' : 'lv') : ($is_en ? home_url('/') : home_url('/lv/'));
    $search_action = $home;
    $top_categories = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'number'=>20,'orderby'=>'count','order'=>'DESC','slug'=>array('instrumenti','specinstrumenti','servisa-aprikojums','darza-tehnika','rezerves-dalas','saimniecibas-preces')));
    $keywords = $is_en ? array('trimmer head','chain saw','compressor','generator','hand tools') : array('trimmera galva','ķēdes zāģis','kompresors','ģenerators','darba rīki');
    $primary_fallback = $is_en ? array(
        'Home'=>$home,'Offers'=>add_query_arg('onsale','1',$shop),'New'=>add_query_arg('orderby','date',$shop),'Tools'=>home_url('/product-category/instrumenti/'),
        'Garden'=>home_url('/product-category/darza-tehnika/'),'Workshop'=>home_url('/product-category/servisa-aprikojums/'),'Spare parts'=>home_url('/product-category/rezerves-dalas/'),
        'Contact'=>wpbbshop_page_url('contact')
    ) : array(
        'Sākums'=>$home,'Akcijas'=>add_query_arg('onsale','1',$shop),'Jaunumi'=>add_query_arg('orderby','date',$shop),'Instrumenti'=>home_url('/product-category/instrumenti/'),
        'Dārzam'=>home_url('/product-category/darza-tehnika/'),'Darbnīcai'=>home_url('/product-category/servisa-aprikojums/'),'Rezerves daļas'=>home_url('/product-category/rezerves-dalas/'),
        'Kontakti'=>wpbbshop_page_url('kontakti')
    );
    $category_links = wpbbshop_green_category_links_309(14);
    ob_start(); ?>
    <header class="llg-header llg-preview-header">
        <div class="llg-desktop-shell">
            <div class="llg-topbar">
                <div class="wpbbshop-container llg-topbar-inner">
                    <div class="llg-topbar-left">
                        <span><?php echo wpbbshop_green_icon('location'); ?> <?php echo esc_html($store_address); ?></span>
                        <span><?php echo wpbbshop_green_icon('truck'); ?> <?php echo esc_html($delivery_label); ?></span>
                        <?php if ($phone) : ?><span><?php echo wpbbshop_green_icon('phone'); ?> <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/','',$phone)); ?>"><?php echo esc_html($phone); ?></a></span><?php endif; ?>
                        <?php if ($email) : ?><span><?php echo wpbbshop_green_icon('mail'); ?> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></span><?php endif; ?>
                    </div>
                    <div class="llg-topbar-right">
                        <a href="<?php echo esc_url($is_en ? wpbbshop_page_url('about-us') : wpbbshop_page_url('par-mums')); ?>"><?php echo $is_en ? 'About us' : 'Par mums'; ?></a>
                        <a href="<?php echo esc_url($is_en ? wpbbshop_page_url('delivery-payment') : wpbbshop_page_url('piegade-un-apmaksa')); ?>"><?php echo $is_en ? 'Delivery & payment' : 'Piegāde un apmaksa'; ?></a>
                        <a href="<?php echo esc_url(wpbbshop_page_url($is_en ? 'contact' : 'kontakti')); ?>"><?php echo $is_en ? 'Contact' : 'Kontakti'; ?></a>
                        <?php if ($facebook) : ?><a aria-label="Facebook" href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer"><?php echo wpbbshop_green_icon('facebook'); ?></a><?php endif; ?>
                        <?php if ($instagram && $instagram !== '#') : ?><a aria-label="Instagram" href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener noreferrer"><?php echo wpbbshop_green_icon('instagram'); ?></a><?php endif; ?>
                        <?php echo wpbbshop_green_language_switcher_234(); ?>
                    </div>
                </div>
            </div>
            <div id="wpbbshop-desktop-mainbar" class="wpbbshop-container ll24-mainbar">
                <div class="ll24-logo"><?php echo wpbbshop_logo_html(); ?></div>
                <div class="ll24-search-area">
                    <form role="search" method="get" class="ll24-search-form wpbbshop-ajax-search" action="<?php echo esc_url($search_action); ?>" autocomplete="off">
                        <div class="ll24-search-category"><select name="product_cat" aria-label="<?php echo esc_attr($is_en?'Category':'Kategorija'); ?>"><option value=""><?php echo $is_en ? 'All categories' : 'Visas kategorijas'; ?></option><?php if (!is_wp_error($top_categories)) : foreach ($top_categories as $term) : ?><option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html(wpbbshop_green_category_label_309($term->name,$term->slug)); ?></option><?php endforeach; endif; ?></select></div>
                        <div class="wpbbshop-search-field-wrap ll24-search-field"><input type="search" name="s" placeholder="<?php echo $is_en ? 'Search product, brand, SKU...' : 'Meklēt preci, ražotāju, SKU...'; ?>" value="<?php echo esc_attr(get_search_query()); ?>"><div class="wpbbshop-search-results" hidden></div></div>
                        <input type="hidden" name="post_type" value="product"><button class="ll24-search-button" type="submit" aria-label="<?php echo esc_attr($is_en?'Search':'Meklēt'); ?>"><?php echo wpbbshop_green_icon('search'); ?></button>
                    </form>
                    <div class="ll24-popular"><strong><?php echo $is_en ? 'Popular searches:' : 'Populāri meklējumi:'; ?></strong><?php foreach ($keywords as $kw) : ?><a href="<?php echo esc_url(add_query_arg(array('s'=>$kw,'post_type'=>'product'),$search_action)); ?>"><?php echo esc_html($kw); ?></a><?php endforeach; ?></div>
                </div>
                <nav class="ll24-actions" aria-label="<?php echo esc_attr($is_en?'Shop actions':'Veikala darbības'); ?>">
                    <a href="<?php echo esc_url($account); ?>"><?php echo wpbbshop_green_icon('user'); ?><span><?php echo $is_en ? 'Account' : 'Konts'; ?></span></a>
                    <a href="<?php echo esc_url($wishlist_url); ?>"><?php echo wpbbshop_green_icon('heart'); ?><span><?php echo $is_en ? 'Wishlist' : 'Vēlmes'; ?></span><em class="wpbbshop-wishlist-count"><?php echo (int)$wishlist_count; ?></em></a>
                    <?php if (function_exists('wp_theme_woo_support_compare_button')) : ?><button type="button" class="wpbbshop-header-compare iws-compare-open" aria-label="<?php echo esc_attr($is_en?'Compare products':'Salīdzināt preces'); ?>"><?php echo wpbbshop_green_icon('compare'); ?><span><?php echo $is_en ? 'Compare' : 'Salīdzināt'; ?></span><em class="iws-compare-count">0</em></button><?php else : ?><a href="<?php echo esc_url($compare_url); ?>"><?php echo wpbbshop_green_icon('compare'); ?><span><?php echo $is_en ? 'Compare' : 'Salīdzināt'; ?></span><em class="wpbbshop-compare-count"><?php echo (int)$compare_count; ?></em></a><?php endif; ?>
                    <button class="ll24-cart wpbbshop-mini-cart-toggle" type="button" aria-controls="wpbbshop-mini-cart-panel" aria-expanded="false"><?php echo wpbbshop_green_icon('cart'); ?><span><?php echo $is_en ? 'Cart' : 'Grozs'; ?></span><em class="wpbbshop-cart-count"><?php echo (int)$cart_count; ?></em><strong class="wpbbshop-cart-total"><?php echo wp_kses_post($cart_total); ?></strong></button>
                </nav>
            </div>
            <div class="llg-nav-row"><div class="wpbbshop-container llg-nav-inner llg-nav-inner-preview">
                <div class="llg-catalog-wrap llg-catalog-wrap-fixed"><button class="llg-catalog-button" type="button" aria-expanded="false"><?php echo wpbbshop_green_icon('menu'); ?><span><?php echo $is_en?'All categories':'Visas kategorijas'; ?></span></button><div class="llg-catalog-menu"><?php echo $category_links; ?></div></div>
                <div class="llg-main-nav"><?php echo wpbbshop_nav_menu('primary',$primary_fallback); ?></div>
                <a class="llg-nav-sale-link" href="<?php echo esc_url(add_query_arg('onsale','1',$shop)); ?>"><?php echo wpbbshop_green_icon('percent'); ?><span><?php echo $is_en?'Sale':'Akcijas'; ?></span></a>
            </div></div>
        </div>

        <div id="wpbbshop-mobile-header" class="llg-mobile-shell ll29-mobile-shell">
            <div class="llg-mobile-top"><span><?php echo wpbbshop_green_icon('location'); ?> <?php echo esc_html($store_address); ?></span><?php echo wpbbshop_green_language_switcher_234(); ?></div>
            <div class="llg-mobile-brand"><?php echo wpbbshop_logo_html(); ?></div>
            <form role="search" method="get" class="llg-mobile-search ll29-mobile-search wpbbshop-ajax-search" data-ll-search-v="3033" action="<?php echo esc_url($search_action); ?>" autocomplete="off" style="box-sizing:border-box!important;position:relative!important;display:flex!important;flex-flow:row nowrap!important;align-items:stretch!important;width:calc(100% - 16px)!important;max-width:calc(100% - 16px)!important;height:46px!important;min-height:46px!important;max-height:46px!important;margin:0 8px 8px!important;padding:0!important;border:1px solid #d7e0e5!important;border-radius:9px!important;background:#fff!important;box-shadow:none!important;overflow:visible!important">
                <div class="llg-mobile-search-cat" style="box-sizing:border-box!important;position:relative!important;flex:0 0 106px!important;width:106px!important;max-width:106px!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0!important;border:0!important;border-right:1px solid #e1e7eb!important;border-radius:8px 0 0 8px!important;background:#f8fafb!important;overflow:hidden!important"><select name="product_cat" aria-label="<?php echo esc_attr($is_en?'Category':'Kategorija'); ?>"><option value=""><?php echo $is_en?'Categories':'Kategorijas'; ?></option><?php if(!is_wp_error($top_categories)): foreach($top_categories as $term): ?><option value="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html(wpbbshop_green_category_label_309($term->name,$term->slug)); ?></option><?php endforeach; endif; ?></select></div>
                <div class="wpbbshop-search-field-wrap llg-mobile-search-field" style="box-sizing:border-box!important;position:relative!important;display:block!important;flex:1 1 auto!important;width:auto!important;min-width:0!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0!important"><input type="search" name="s" placeholder="<?php echo $is_en?'Search products...':'Meklēt preces...'; ?>" style="box-sizing:border-box!important;display:block!important;width:100%!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0 10px!important;border:0!important;border-radius:0!important;box-shadow:none!important;line-height:44px!important"><div class="wpbbshop-search-results" hidden></div></div>
                <input type="hidden" name="post_type" value="product"><button class="llg-search-submit ll31-mobile-search-submit" type="submit" style="box-sizing:border-box!important;position:relative!important;inset:auto!important;align-self:flex-start!important;flex:0 0 46px!important;display:grid!important;place-items:center!important;width:46px!important;min-width:46px!important;max-width:46px!important;height:44px!important;min-height:44px!important;max-height:44px!important;margin:0!important;padding:0!important;border:0!important;border-left:1px solid #278a45!important;border-radius:0 8px 8px 0!important;background:#2f9d50!important;background-color:#2f9d50!important;background-image:none!important;color:#fff!important;box-shadow:none!important;transform:none!important;overflow:hidden!important" aria-label="<?php echo esc_attr($is_en?'Search':'Meklēt'); ?>"><?php echo wpbbshop_green_icon('search'); ?></button>
            </form>
            <nav class="llg-mobile-actions ll29-mobile-actions" aria-label="<?php echo esc_attr($is_en?'Shop actions':'Veikala darbības'); ?>">
                <a href="<?php echo esc_url($account); ?>"><?php echo wpbbshop_green_icon('user'); ?><span><?php echo $is_en?'Account':'Konts'; ?></span></a>
                <a href="<?php echo esc_url($wishlist_url); ?>"><?php echo wpbbshop_green_icon('heart'); ?><span><?php echo $is_en?'Wishlist':'Vēlmes'; ?></span><em><?php echo (int)$wishlist_count; ?></em></a>
                <?php if (function_exists('wp_theme_woo_support_compare_button')) : ?><button type="button" class="wpbbshop-header-compare iws-compare-open" aria-label="<?php echo esc_attr($is_en?'Compare products':'Salīdzināt preces'); ?>"><?php echo wpbbshop_green_icon('compare'); ?><span><?php echo $is_en?'Compare':'Salīdzināt'; ?></span><em class="iws-compare-count">0</em></button><?php else : ?><a href="<?php echo esc_url($compare_url); ?>"><?php echo wpbbshop_green_icon('compare'); ?><span><?php echo $is_en?'Compare':'Salīdzināt'; ?></span><em><?php echo (int)$compare_count; ?></em></a><?php endif; ?>
                <button class="wpbbshop-mini-cart-toggle" type="button" aria-controls="wpbbshop-mini-cart-panel" aria-expanded="false"><?php echo wpbbshop_green_icon('cart'); ?><span><?php echo $is_en?'Cart':'Grozs'; ?></span><em><?php echo (int)$cart_count; ?></em></button>
            </nav>
            <div class="llg-mobile-navbar ll29-mobile-navbar">
                <div class="llg-mobile-catalog-wrap"><button class="llg-mobile-catalog-button" type="button" aria-expanded="false"><?php echo wpbbshop_green_icon('menu'); ?><span><?php echo $is_en?'Categories':'Visas kategorijas'; ?></span></button><div class="llg-mobile-catalog-menu"><?php echo $category_links; ?></div></div>
                <button class="llg-mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="llg-mobile-menu-panel"><?php echo wpbbshop_green_icon('menu'); ?><span><?php echo $is_en?'Menu':'Izvēlne'; ?></span></button>
            </div>
        </div>

        <div id="llg-mobile-menu-panel" class="llg-mobile-menu-panel" hidden>
            <div class="wpbbshop-container llg-mobile-menu-inner">
                <div class="llg-mobile-menu-head"><strong><?php echo $is_en?'Navigation':'Izvēlne'; ?></strong><button type="button" class="llg-mobile-menu-close" aria-label="<?php echo esc_attr($is_en?'Close menu':'Aizvērt izvēlni'); ?>">×</button></div>
                <div class="llg-mobile-primary"><?php echo wpbbshop_nav_menu('primary',$primary_fallback); ?></div>
                <div class="llg-mobile-utility">
                    <a href="<?php echo esc_url($is_en?wpbbshop_page_url('about-us'):wpbbshop_page_url('par-mums')); ?>"><?php echo $is_en?'About us':'Par mums'; ?></a>
                    <a href="<?php echo esc_url($is_en?wpbbshop_page_url('delivery-payment'):wpbbshop_page_url('piegade-un-apmaksa')); ?>"><?php echo $is_en?'Delivery & payment':'Piegāde un apmaksa'; ?></a>
                    <a href="<?php echo esc_url(wpbbshop_page_url($is_en ? 'contact' : 'kontakti')); ?>"><?php echo $is_en?'Contact':'Kontakti'; ?></a>
                </div>
            </div>
        </div>
        <?php echo function_exists('wpbbshop_mini_cart_html') ? wpbbshop_mini_cart_html() : ''; ?>
    </header>
    <?php return ob_get_clean();
}

function wpbbshop_green_term_card_image($term) {
    $thumb_id = isset($term->term_id) ? absint(get_term_meta($term->term_id, 'thumbnail_id', true)) : 0;
    if ($thumb_id) {
        return wp_get_attachment_image($thumb_id, 'woocommerce_thumbnail', false, array('loading'=>'lazy'));
    }
    return function_exists('wpbbshop_category_icon_html') ? wpbbshop_category_icon_html($term) : '';
}

function wpbbshop_green_categories($limit = 8) {
    if (!class_exists('WooCommerce')) { return ''; }
    $terms = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'number'=>absint($limit),'orderby'=>'count','order'=>'DESC'));
    if (is_wp_error($terms) || empty($terms)) { return ''; }
    if (function_exists('wpbbshop_category_display_order')) { $terms = wpbbshop_category_display_order($terms); }
    ob_start(); ?>
    <section class="llg-section llg-categories-section">
        <div class="llg-section-head"><div><span class="llg-eyebrow">Ātri atrodi vajadzīgo</span><h2>Preču kategorijas</h2></div><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><span class="llg-section-link-text">Visas preces</span><span class="llg-section-link-icon"><?php echo wpbbshop_green_icon('arrow'); ?></span></a></div>
        <div class="llg-category-grid">
            <?php foreach ($terms as $term) : ?>
                <a class="llg-category-card" href="<?php echo esc_url(get_term_link($term)); ?>">
                    <span class="llg-category-image"><?php echo wpbbshop_green_term_card_image($term); ?></span>
                    <span class="llg-category-copy"><strong><?php echo esc_html($term->name); ?></strong><small><?php echo (int) $term->count; ?> preces</small></span>
                    <span class="llg-category-arrow">›</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php return ob_get_clean();
}

function wpbbshop_green_demo_item_by_sku($sku) {
    foreach (wpbbshop_green_demo_catalog() as $item) {
        if (!empty($item['sku']) && (string) $item['sku'] === (string) $sku) { return $item; }
    }
    return null;
}

function wpbbshop_green_demo_name_309($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $name = $product->get_name();
    if (!(function_exists('wpbbshop_v305_is_english') && wpbbshop_v305_is_english())) { return $name; }
    $map = array(
        'LL-DEMO-D20'=>'Cordless drill 20V GREEN HOME D20',
        'LL-DEMO-C50'=>'Air compressor 50L 2.2kW GREEN HOME C50',
        'LL-DEMO-W200'=>'Inverter welder 200A GREEN HOME W200',
        'LL-DEMO-G3500'=>'Petrol generator 3.5kW GREEN HOME G3500',
        'LL-DEMO-J3T'=>'Hydraulic trolley jack 3T GREEN HOME J3T',
        'LL-DEMO-A1500'=>'Pneumatic impact wrench 1/2 GREEN HOME A1500',
        'LL-DEMO-S108'=>'108-piece tool set GREEN HOME S108',
        'LL-DEMO-B26'=>'2-in-1 leaf blower GREEN HOME B26',
        'LL-DEMO-BC52'=>'Petrol brush cutter 52cc GREEN HOME BC52',
        'LL-DEMO-H10'=>'Semi-automatic trimmer head M10x1.25 GREEN HOME H10',
        'LL-DEMO-HALU'=>'Universal aluminium trimmer head GREEN HOME HALU',
        'LL-DEMO-L24'=>'Trimmer line 2.4 mm × 100 m GREEN HOME L24',
        'LL-DEMO-CS85'=>'Saw-chain sharpener GREEN HOME CS85',
        'LL-DEMO-OP12'=>'12V oil change pump GREEN HOME OP12',
    );
    $sku=(string)$product->get_sku();
    return isset($map[$sku]) ? $map[$sku] : $name;
}

function wpbbshop_green_product_image_html($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }

    $sku = (string) $product->get_sku();
    $item = $sku !== '' ? wpbbshop_green_demo_item_by_sku($sku) : null;
    $image_id = absint($product->get_image_id());
    $gallery_ids = array_values(array_filter(array_map('absint', (array) $product->get_gallery_image_ids())));

    $primary_html = '';
    $secondary_html = '';
    $v425_owned = false;

    // v4.0.25 gives all WP BB demo catalogue products a cleaner local showcase
    // image before the older demo/Media Library fallbacks run.
    $v425_url = function_exists('wpbbshop_v425_demo_product_asset') ? wpbbshop_v425_demo_product_asset($product) : '';
    if ($v425_url) {
        $primary_html = '<img class="llg-product-image-primary wpbb-v425-demo-image" src="' . esc_url($v425_url) . '" alt="' . esc_attr(wpbbshop_green_demo_name_309($product)) . '" loading="lazy" decoding="async">';
        $v425_owned = true;
    }

    // Demo products use bundled theme assets directly. This keeps Refresh fast and
    // completely avoids WordPress/Imagick thumbnail generation on shared hosting.
    if ($primary_html === '' && $item && !empty($item['image'])) {
        $primary_file = basename($item['image']);
        $primary_path = trailingslashit(get_stylesheet_directory()) . 'assets/demo-products/' . $primary_file;
        if (file_exists($primary_path)) {
            $primary_url = trailingslashit(get_stylesheet_directory_uri()) . 'assets/demo-products/' . rawurlencode($primary_file);
            $primary_html = '<img class="llg-product-image-primary" src="' . esc_url($primary_url) . '" alt="' . esc_attr(wpbbshop_green_demo_name_309($product)) . '" loading="lazy" decoding="async">';
        }
        foreach ((array) (isset($item['gallery']) ? $item['gallery'] : array()) as $gallery_file) {
            $gallery_file = basename($gallery_file);
            $gallery_path = trailingslashit(get_stylesheet_directory()) . 'assets/demo-products/' . $gallery_file;
            if (!file_exists($gallery_path)) { continue; }
            $gallery_url = trailingslashit(get_stylesheet_directory_uri()) . 'assets/demo-products/' . rawurlencode($gallery_file);
            $secondary_html = '<img class="llg-product-image-secondary" src="' . esc_url($gallery_url) . '" alt="" loading="lazy" decoding="async">';
            break;
        }
    }

    // Real products continue to use their WooCommerce Media Library images.
    if ($primary_html === '') {
        if ($image_id) {
            $primary_html = wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, array(
                'class' => 'llg-product-image-primary',
                'loading' => 'lazy',
                'decoding' => 'async',
            ));
        } else {
            $demo_url = function_exists('wpbbshop_v305_demo_image_url') ? wpbbshop_v305_demo_image_url($product) : '';
            if ($demo_url && (!function_exists('wc_placeholder_img_src') || $demo_url !== wc_placeholder_img_src('woocommerce_thumbnail'))) {
                $primary_html = '<img class="llg-product-image-primary" src="' . esc_url($demo_url) . '" alt="' . esc_attr($product->get_name()) . '" loading="lazy" decoding="async">';
            } else {
                $primary_html = wc_placeholder_img('woocommerce_thumbnail', array('class' => 'llg-product-image-primary'));
            }
        }
    }
    if ($secondary_html === '' && !$v425_owned) {
        foreach ($gallery_ids as $gallery_id) {
            if ($gallery_id && $gallery_id !== $image_id) {
                $secondary_html = wp_get_attachment_image($gallery_id, 'woocommerce_thumbnail', false, array(
                    'class' => 'llg-product-image-secondary',
                    'loading' => 'lazy',
                    'decoding' => 'async',
                ));
                break;
            }
        }
    }

    $classes = 'llg-card-media';
    if ($secondary_html !== '') { $classes .= ' has-gallery'; }
    $out  = '<span class="' . esc_attr($classes) . '">';
    $out .= '<span class="llg-card-media-glow" aria-hidden="true"></span>';
    $out .= '<span class="llg-card-media-main">' . $primary_html . '</span>';
    if ($secondary_html !== '') {
        $out .= '<span class="llg-card-media-secondary" aria-hidden="true">' . $secondary_html . '</span>';
        $out .= '<span class="llg-card-media-pill">Galerija</span>';
    }
    $out .= '</span>';
    return $out;
}

function wpbbshop_green_product_card($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $id=$product->get_id(); $link=get_permalink($id); $stock=$product->is_in_stock(); $sku=$product->get_sku(); $rating=(float)$product->get_average_rating();
    $is_en=function_exists('wpbbshop_v305_is_english') && wpbbshop_v305_is_english();
    $name=wpbbshop_green_demo_name_309($product);
    ob_start(); ?>
    <article class="llg-product-card product">
        <div class="llg-card-image-wrap">
            <?php echo function_exists('wpbbshop_sale_badge') ? wpbbshop_sale_badge($product) : ''; ?>
            <?php echo function_exists('wpbbshop_product_quick_actions') ? wpbbshop_product_quick_actions($id) : ''; ?>
            <a class="llg-card-image" href="<?php echo esc_url($link); ?>"><?php echo wpbbshop_green_product_image_html($product); ?></a>
        </div>
        <div class="llg-card-body">
            <div class="llg-card-kicker"><span class="<?php echo $stock?'is-instock':'is-outstock'; ?>"><?php echo $stock?($is_en?'In stock':'Ir noliktavā'):($is_en?'Out of stock':'Nav noliktavā'); ?></span><?php if($sku): ?><small>SKU: <?php echo esc_html($sku); ?></small><?php endif; ?></div>
            <?php if($product->is_type('variable') && function_exists('wpbbshop_v422_variable_summary_html')) { echo wpbbshop_v422_variable_summary_html($product); } ?>
            <h3><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($name); ?></a></h3>
            <?php if($rating>0): ?><div class="llg-card-rating"><span>★★★★★</span><small><?php echo esc_html(number_format($rating,1)); ?></small></div><?php else: ?><div class="llg-card-rating llg-card-rating-empty"><span>★★★★★</span></div><?php endif; ?>
            <div class="llg-card-bottom"><div class="llg-card-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
            <?php if($product->is_type('variable')): ?><a class="button llg-card-cart llg-card-details" href="<?php echo esc_url($link); ?>"><?php echo $is_en?'Choose options':'Izvēlēties variantu'; ?></a><?php elseif($product->is_purchasable()&&$stock): ?><a href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-quantity="1" data-product_id="<?php echo esc_attr($id); ?>" class="button add_to_cart_button ajax_add_to_cart llg-card-cart <?php echo esc_attr($product->get_type()); ?>"><?php echo $is_en?'Add to cart':'Pievienot grozam'; ?></a><?php else: ?><a class="button llg-card-cart llg-card-details" href="<?php echo esc_url($link); ?>"><?php echo $is_en?'View product':'Skatīt preci'; ?></a><?php endif; ?></div>
        </div>
    </article>
    <?php return ob_get_clean();
}

function wpbbshop_green_products_section($type, $title, $limit = 10, $eyebrow = '') {
    if (!class_exists('WooCommerce')) { return ''; }
    $meta_query = WC()->query->get_meta_query();
    $meta_query[] = array('relation'=>'OR',array('key'=>'_wpbb_child_woo_demo_product','compare'=>'NOT EXISTS'),array('key'=>'_wpbb_child_woo_demo_product','value'=>'1','compare'=>'!='));
    $args = array('post_type'=>'product','post_status'=>'publish','posts_per_page'=>absint($limit),'meta_query'=>$meta_query,'tax_query'=>WC()->query->get_tax_query(),'suppress_filters'=>true,'lang'=>'');
    if (function_exists('wpbbshop_parent_demo_product_ids_236')) {
        $blocked_ids = wpbbshop_parent_demo_product_ids_236();
        if ($blocked_ids) { $args['post__not_in'] = $blocked_ids; }
    }
    if ($type === 'popular') { $args['meta_key'] = 'total_sales'; $args['orderby'] = array('meta_value_num'=>'DESC','date'=>'DESC'); }
    elseif ($type === 'sale') { $args['post__in'] = wc_get_product_ids_on_sale(); $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
    elseif ($type === 'featured') { $args['tax_query'][] = array('taxonomy'=>'product_visibility','field'=>'name','terms'=>array('featured'),'operator'=>'IN'); $args['orderby']='date'; $args['order']='DESC'; }
    else { $args['orderby']='date'; $args['order']='DESC'; }
    $q = new WP_Query($args);
    if (!$q->have_posts()) { return ''; }
    $shop = wc_get_page_permalink('shop');
    ob_start(); ?>
    <section class="llg-section llg-products-section">
        <div class="llg-section-head"><div><?php if ($eyebrow) : ?><span class="llg-eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?><h2><?php echo esc_html($title); ?></h2></div><a href="<?php echo esc_url($shop); ?>"><span class="llg-section-link-text"><?php echo (function_exists('wpbbshop_v305_is_english') && wpbbshop_v305_is_english()) ? 'View all' : 'Skatīt visas'; ?></span><span class="llg-section-link-icon"><?php echo wpbbshop_green_icon('arrow'); ?></span></a></div>
        <div class="llg-product-grid">
            <?php while ($q->have_posts()) : $q->the_post(); global $product; echo wpbbshop_green_product_card($product); endwhile; wp_reset_postdata(); ?>
        </div>
    </section>
    <?php return ob_get_clean();
}

function wpbbshop_green_home_shortcode() {
    $shop=function_exists('wc_get_page_permalink')?wc_get_page_permalink('shop'):home_url('/shop/');
    $is_en=function_exists('wpbbshop_v305_is_english')&&wpbbshop_v305_is_english();
    $categories=wpbbshop_green_category_links_309(10);
    $monthly_promo=function_exists('wpbbshop_v405_top_products_html') ? wpbbshop_v405_top_products_html($is_en) : (function_exists('wpbbshop_v333_monthly_promo_html') ? wpbbshop_v333_monthly_promo_html($is_en) : '');
    ob_start(); ?>
    <main class="llg-home llg-clean-home">
      <div class="wpbbshop-container llg-home-stage">
        <aside class="llg-home-categories" aria-label="<?php echo esc_attr($is_en?'Product categories':'Preču kategorijas'); ?>">
          <?php echo $categories ?: '<a class="wpbbshop-cat-panel-item" href="'.esc_url($shop).'"><span>'.($is_en?'All products':'Visas preces').'</span></a>'; ?>
          <a class="llg-cat-sale" href="<?php echo esc_url(add_query_arg('onsale','1',$shop)); ?>"><?php echo wpbbshop_green_icon('percent'); ?><span><?php echo $is_en?'Sale':'Akcijas'; ?></span></a>
        </aside>
        <?php if ($monthly_promo) : ?>
          <?php echo $monthly_promo; ?>
        <?php else : ?>
          <section class="llg-home-hero">
            <div class="llg-home-hero-copy">
              <span class="llg-home-kicker">WP BB HOME & GARDEN • RIGA</span>
              <h1><?php echo $is_en?'Garden equipment ':'Dārza tehnika '; ?><strong><?php echo $is_en?'for the new season':'jaunai sezonai'; ?></strong></h1>
              <p><?php echo $is_en?'Lawn mowers, trimmers, brush cutters, tools and spare parts for home and garden work.':'Zāles pļāvēji, trimmeri, krūmgrieži, instrumenti un rezerves daļas darbam mājās un dārzā.'; ?></p>
              <a class="llg-primary-button" href="<?php echo esc_url($shop); ?>"><?php echo $is_en?'View offers':'Skatīt piedāvājumu'; ?> <?php echo wpbbshop_green_icon('arrow'); ?></a>
            </div>
            <div class="llg-home-hero-art"><img src="<?php echo esc_url(get_stylesheet_directory_uri().'/assets/img/hero-mower.jpg'); ?>" alt="<?php echo esc_attr($is_en?'Garden equipment':'Dārza tehnika'); ?>"></div>
            <div class="llg-home-hero-trust"><span><?php echo wpbbshop_green_icon('truck'); ?> <?php echo $is_en?'Delivery across Latvia':'Piegāde visā Latvijā'; ?></span><span><?php echo wpbbshop_green_icon('location'); ?> <?php echo $is_en?'Pickup in Riga':'Saņemšana Rīgā'; ?></span><span><?php echo wpbbshop_green_icon('shield'); ?> <?php echo $is_en?'Trusted shop':'Uzticams veikals'; ?></span></div>
          </section>
        <?php endif; ?>
      </div>
      <section class="wpbbshop-container llg-benefit-strip llg-clean-benefits">
        <div><?php echo wpbbshop_green_icon('truck'); ?><span><strong><?php echo $is_en?'Fast delivery':'Ātra piegāde'; ?></strong><small><?php echo $is_en?'Omniva, Unisend, Latvijas Pasts and courier':'Omniva, Unisend, Latvijas Pasts un kurjers'; ?></small></span></div>
        <div><?php echo wpbbshop_green_icon('location'); ?><span><strong><?php echo $is_en?'Pickup in Riga':'Saņemšana Rīgā'; ?></strong><small><?php echo $is_en?'Free by arrangement':'Bez maksas pēc vienošanās'; ?></small></span></div>
        <div><?php echo wpbbshop_green_icon('cart'); ?><span><strong><?php echo $is_en?'Easy payment':'Ērta apmaksa'; ?></strong><small><?php echo $is_en?'Bank transfer, cash and online':'Pārskaitījums, skaidrā naudā un tiešsaistē'; ?></small></span></div>
        <div><?php echo wpbbshop_green_icon('shield'); ?><span><strong><?php echo $is_en?'Secure purchase':'Drošs pirkums'; ?></strong><small><?php echo $is_en?'Clear information and warranty':'Skaidra informācija un garantija'; ?></small></span></div>
      </section>
      <div class="wpbbshop-container llg-home-products"><?php echo wpbbshop_green_products_section('featured',$is_en?'Popular products':'Populārākās preces',12,$is_en?'Popular':'Populāri'); ?></div>
      <div class="wpbbshop-container llg-home-products"><?php echo wpbbshop_green_products_section('sale',$is_en?'Sale':'Akcijas',8,$is_en?'Save':'Ietaupi'); ?></div>
      <div class="wpbbshop-container llg-home-products"><?php echo wpbbshop_green_products_section('recent',$is_en?'New arrivals':'Jaunumi',8,$is_en?'Latest additions':'Jaunākie papildinājumi'); ?></div>
    </main>
    <?php return ob_get_clean();
}

function wpbbshop_green_footer_html() {
    $facebook=wpbbshop_get_theme_option('social_facebook','');
    $phone=trim((string)wpbbshop_get_theme_option('phone',''));
    $email=trim((string)wpbbshop_get_theme_option('email',''));
    $hours=trim((string)wpbbshop_get_theme_option('work_hours','P.-Pk. 9:00 - 18:00 / S. 9:00 - 14:00'));
    $is_en=function_exists('wpbbshop_v305_is_english')&&wpbbshop_v305_is_english();
    $is_uk=function_exists('wpbbshop_v414_is_uk_store')&&wpbbshop_v414_is_uk_store();
    $address=function_exists('wpbbshop_v414_store_address')?wpbbshop_v414_store_address():'Bauskas 63 - 1a/k1, Riga, LV-1004';
    $store_city=function_exists('wpbbshop_v414_store_city')?wpbbshop_v414_store_city():'Riga';
    $store_country=function_exists('wpbbshop_v414_store_country')?wpbbshop_v414_store_country($is_en):($is_en?'Latvia':'Latvija');
    $wa_number=preg_replace('/[^0-9]/','',$phone);
    $footer_fallback=$is_en ? array(
      'About us'=>wpbbshop_page_url('about-us'),'Delivery & payment'=>wpbbshop_page_url('delivery-payment'),
      'Returns & warranty'=>wpbbshop_page_url('returns-warranty'),'Terms & conditions'=>wpbbshop_page_url('terms-conditions')
    ) : array(
      'Par mums'=>wpbbshop_page_url('par-mums'),'Piegāde un apmaksa'=>wpbbshop_page_url('piegade-un-apmaksa'),
      'Atgriešana un garantija'=>wpbbshop_page_url('atgriesana-un-garantija'),'Pirkšanas noteikumi'=>wpbbshop_page_url('pirksanas-noteikumi')
    );
    $service_fallback=$is_en ? array(
      'Contact'=>wpbbshop_page_url('contact'),'My account'=>(function_exists('wc_get_page_permalink')?wc_get_page_permalink('myaccount'):home_url('/my-account/')),
      'Cart'=>(function_exists('wc_get_cart_url')?wc_get_cart_url():home_url('/cart/')),'Track order'=>wpbbshop_page_url('track-your-order')
    ) : array(
      'Kontakti'=>wpbbshop_page_url('kontakti'),'Mans konts'=>(function_exists('wc_get_page_permalink')?wc_get_page_permalink('myaccount'):home_url('/my-account/')),
      'Grozs'=>(function_exists('wc_get_cart_url')?wc_get_cart_url():home_url('/cart/')),'Pasūtījuma izsekošana'=>wpbbshop_page_url('track-your-order')
    );
    ob_start(); ?>
    <footer class="llg-footer llg-preview-footer llg-footer-fixed">
      <div class="wpbbshop-container llg-footer-main"><div class="llg-footer-grid llg-footer-grid-fixed">
        <div class="llg-footer-brand llg-footer-brand-fixed"><?php echo wpbbshop_logo_html(); ?><p><?php echo $is_en ? ($is_uk ? 'Practical products for home, work and garden. Delivery throughout the UK with pickup in Northampton.' : 'Practical products for home, work and garden. Delivery throughout Latvia with pickup in Riga.') : 'Praktiskas lietas mājai, darbam un dārzam. Piegāde visā Latvijā, saņemšana Rīgā.'; ?></p><?php if($facebook): ?><a class="llg-footer-social" href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer"><?php echo wpbbshop_green_icon('facebook'); ?> <?php echo $is_en?'Follow on Facebook':'Sekot Facebook'; ?></a><?php endif; ?></div>
        <div class="llg-footer-col"><h4><?php echo $is_en?'Information':'Informācija'; ?></h4><?php echo wpbbshop_nav_menu('footer',$footer_fallback); ?></div>
        <div class="llg-footer-col"><h4><?php echo $is_en?'Customers':'Klientiem'; ?></h4><?php echo wpbbshop_nav_menu('service',$service_fallback); ?></div>
        <div class="llg-footer-col"><h4><?php echo $is_en?'Contact':'Kontakti'; ?></h4><p class="llg-contact-list"><span><?php echo wpbbshop_green_icon('location'); ?> <?php echo esc_html($address); ?></span><?php if($phone): ?><span><?php echo wpbbshop_green_icon('phone'); ?> <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/','',$phone)); ?>"><?php echo esc_html($phone); ?></a></span><?php endif; ?><?php if($email): ?><span><?php echo wpbbshop_green_icon('mail'); ?> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></span><?php endif; ?><?php if($hours): ?><span><?php echo wpbbshop_green_icon('clock'); ?> <?php echo esc_html($hours); ?></span><?php endif; ?></p></div>
        <div class="llg-footer-col llg-footer-delivery"><h4><?php echo $is_en?'Delivery & payment':'Piegāde un apmaksa'; ?></h4><div class="llg-footer-pills"><?php foreach(array_filter(array_map('trim',explode(',',wpbbshop_get_theme_option('footer_delivery_partners','Unisend,Omniva,Latvijas Pasts,Kurjers')))) as $item): ?><span><?php echo esc_html($item); ?></span><?php endforeach; ?></div><div class="llg-footer-pills llg-payment-pills"><?php foreach(array_filter(array_map('trim',explode(',',wpbbshop_get_theme_option('footer_payment_methods','Skaidrā naudā,Bankas pārskaitījums,EveryPay / Swedbank')))) as $item): ?><span><?php echo esc_html($item); ?></span><?php endforeach; ?></div></div>
        <div class="llg-footer-col llg-footer-partners-col"><h4><?php echo $is_en?'Price comparison':'Cenu salīdzināšana'; ?></h4><?php echo function_exists('wpbbshop_v408_footer_comparison_html') ? wpbbshop_v408_footer_comparison_html($is_en) : '<div class="llg-comparison-sites"><a href="https://www.kurpirkt.lv/" target="_blank" rel="noopener noreferrer">KurPirkt.lv</a><a href="https://www.salidzini.lv/" target="_blank" rel="noopener noreferrer">Salidzini.lv</a><a href="https://ceno.lv/" target="_blank" rel="noopener noreferrer">Ceno.lv</a></div>'; ?></div>
      </div></div>
      <div class="llg-footer-bottom"><div class="wpbbshop-container"><span>© <?php echo esc_html(date('Y')); ?> WP BB Home & Garden. <?php echo $is_en?'All rights reserved.':'Visas tiesības aizsargātas.'; ?></span><span class="llg-footer-meta"><?php echo esc_html($store_city); ?> • <?php echo esc_html($store_country); ?> <span class="llg-footer-sep">•</span> <?php echo $is_en?'Developed by':'Izstrādāja'; ?> <a class="llg-developer-link" href="https://digitalpulse.click/" target="_blank" rel="noopener noreferrer">DigitalPulse.click</a></span></div></div>
    </footer>
    <?php if($wa_number): ?><a class="llg-whatsapp-fab" href="https://wa.me/<?php echo esc_attr($wa_number); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><svg viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16 3C9 3 3.4 8.4 3.4 15.1c0 2.4.7 4.7 2.1 6.7L4 29l7.5-1.9c1.4.7 3 .9 4.5.9 7 0 12.6-5.4 12.6-12.1S23 3 16 3Zm0 22.8c-1.4 0-2.8-.3-4-.9l-.9-.4-4.4 1.1 1.2-4.1-.6-.9c-1.1-1.7-1.7-3.7-1.7-5.7 0-5.5 4.7-10 10.5-10s10.5 4.5 10.5 10-4.8 10.9-10.6 10.9Zm5.8-7.4c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.8.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-2.1-1-3.5-1.9-4.9-4.3-.4-.7.4-.7 1.1-2.2.1-.2 0-.5-.1-.7-.1-.2-.8-1.8-1.1-2.5-.3-.6-.6-.5-.8-.5h-.7c-.3 0-.7.1-1 .5-.3.3-1.3 1.2-1.3 3 0 1.8 1.3 3.5 1.5 3.8.2.2 2.6 3.9 6.3 5.4 2.3 1 3.2 1.1 4.4.9 1.4-.2 1.9-.9 2.2-1.8.3-.9.3-1.6.2-1.8-.1-.2-.4-.3-.7-.4Z"/></svg></a><?php endif; ?>
    <button class="llg-scroll-top" type="button" aria-label="<?php echo esc_attr($is_en?'Back to top':'Atpakaļ uz augšu'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 14l6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    <?php return ob_get_clean();
}

// Replace the previous presentation layer while keeping all commerce/delivery logic intact.
add_shortcode('wpbbshop_header', 'wpbbshop_green_header_html');
add_shortcode('wpbbshop_home', 'wpbbshop_green_home_shortcode');
add_shortcode('wpbbshop_footer', 'wpbbshop_green_footer_html');
add_filter('body_class', function($classes){ $classes[] = 'wpbbshop-green-v2'; return $classes; }, 99);

/** Demo catalogue ***********************************************************/
function wpbbshop_green_demo_catalog() {
    return function_exists('wpbbshop_megastore_demo_catalog_310') ? wpbbshop_megastore_demo_catalog_310() : array();
}

function wpbbshop_green_demo_attachment($filename, $title) {
    $existing = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_wpbbshop_demo_asset',
        'meta_value'     => $filename,
    ));
    if ($existing) { return absint($existing[0]); }

    $source = trailingslashit(get_stylesheet_directory()) . 'assets/demo-products/' . basename($filename);
    if (!file_exists($source)) { return 0; }

    $contents = @file_get_contents($source);
    if ($contents === false) { return 0; }

    $bits = wp_upload_bits('wpbbshop-' . basename($filename), null, $contents);
    if (!empty($bits['error'])) { return 0; }

    $filetype = wp_check_filetype($bits['file']);
    $attachment_id = wp_insert_attachment(array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_text_field($title),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ), $bits['file']);
    if (is_wp_error($attachment_id) || !$attachment_id) { return 0; }

    // Avoid wp_generate_attachment_metadata() here. On low-resource shared hosting
    // Imagick can exceed the 30-second PHP limit while creating many WooCommerce
    // sub-sizes during demo import. Basic metadata is enough for the original image.
    $size = @getimagesize($bits['file']);
    if (is_array($size) && !empty($size[0]) && !empty($size[1])) {
        $meta = array(
            'width'  => absint($size[0]),
            'height' => absint($size[1]),
            'file'   => function_exists('_wp_relative_upload_path') ? _wp_relative_upload_path($bits['file']) : basename($bits['file']),
            'sizes'  => array(),
        );
        wp_update_attachment_metadata($attachment_id, $meta);
    }
    update_post_meta($attachment_id, '_wpbbshop_demo_asset', $filename);
    return absint($attachment_id);
}

function wpbbshop_green_find_demo_product_id($item) {
    $sku = isset($item['sku']) ? sanitize_text_field((string) $item['sku']) : '';
    if ($sku !== '') {
        $by_sku = wc_get_product_id_by_sku($sku);
        if ($by_sku) { return absint($by_sku); }
        $by_key = get_posts(array(
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_wpbbshop_demo_key',
            'meta_value'     => $sku,
        ));
        if (!empty($by_key)) { return absint($by_key[0]); }
    }
    return 0;
}

function wpbbshop_green_seed_demo_catalog() {
    if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) { return new WP_Error('woocommerce_missing', 'WooCommerce nav aktīvs.'); }
    $category_ids = array();
    foreach (array_values(array_unique(array_map(function($item){ return isset($item['category']) ? $item['category'] : ''; }, wpbbshop_green_demo_catalog()))) as $category) {
        if ($category === '') { continue; }
        $term = term_exists($category, 'product_cat');
        if (!$term) { $term = wp_insert_term($category, 'product_cat'); }
        if (!is_wp_error($term)) { $category_ids[$category] = absint(is_array($term) ? $term['term_id'] : $term); }
    }
    $created = 0; $updated = 0;
    foreach (wpbbshop_green_demo_catalog() as $item) {
        $demo_key = sanitize_text_field((string) $item['sku']);
        $id = wpbbshop_green_find_demo_product_id($item);
        $product = $id ? wc_get_product($id) : new WC_Product_Simple();
        if (!$product || !is_a($product, 'WC_Product')) { continue; }
        $is_new = !$id;
        $product->set_name($item['name']);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        if ($demo_key !== '') {
            try {
                if (!$id || $product->get_sku() !== $demo_key) {
                    $product->set_sku($demo_key);
                }
            } catch (Exception $e) {
                if (!$id || $product->get_sku() !== $demo_key) { continue; }
            }
        }
        $product->set_regular_price($item['regular']);
        $product->set_sale_price($item['sale']);
        $product->set_manage_stock(true);
        $product->set_stock_quantity(absint($item['stock']));
        $product->set_stock_status('instock');
        $product->set_short_description($item['short']);
        $product->set_description($item['description']);
        $product->set_featured(!empty($item['featured']));
        if (isset($category_ids[$item['category']])) { $product->set_category_ids(array($category_ids[$item['category']])); }
        $image_id = wpbbshop_green_demo_attachment($item['image'], $item['name']);
        if ($image_id) { $product->set_image_id($image_id); }
        $gallery_ids = array();
        foreach ((array) (isset($item['gallery']) ? $item['gallery'] : array()) as $gallery_file) {
            $gallery_id = wpbbshop_green_demo_attachment($gallery_file, $item['name'] . ' — galerija');
            if ($gallery_id && $gallery_id !== $image_id) { $gallery_ids[] = $gallery_id; }
        }
        $product->set_gallery_image_ids(array_values(array_unique($gallery_ids)));
        try {
            $saved_id = $product->save();
        } catch (Exception $e) {
            continue;
        }
        if (!$saved_id) { continue; }
        update_post_meta($saved_id, '_wpbbshop_demo_product', '1');
        update_post_meta($saved_id, '_wpbbshop_demo_key', $demo_key);
        update_post_meta($saved_id, '_wpbbshop_feed_brand', 'GREEN HOME');
        update_post_meta($saved_id, '_wpbbshop_feed_model', $item['model']);
        update_post_meta($saved_id, '_wpbbshop_feed_mpn', $item['sku']);
        update_post_meta($saved_id, '_wpbbshop_feed_exclude', 'yes');
        $is_new ? $created++ : $updated++;
        if ($image_id && isset($category_ids[$item['category']]) && !get_term_meta($category_ids[$item['category']], 'thumbnail_id', true)) {
            update_term_meta($category_ids[$item['category']], 'thumbnail_id', $image_id);
        }
    }
    update_option('wpbbshop_green_demo_seeded', WPBBSHOP_GREEN_V2, false);
    if (function_exists('wpbbshop_compare_feed_invalidate')) { wpbbshop_compare_feed_invalidate(); }
    return array('created'=>$created,'updated'=>$updated);
}

function wpbbshop_green_remove_demo_assets() {
    $ids = get_posts(array(
        'post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>-1,'fields'=>'ids',
        'meta_key'=>'_wpbbshop_demo_asset','meta_compare'=>'EXISTS',
    ));
    $deleted = 0;
    foreach ((array) $ids as $id) {
        if (wp_delete_attachment(absint($id), true)) { $deleted++; }
    }
    return $deleted;
}

function wpbbshop_green_remove_demo_catalog() {
    $ids = get_posts(array('post_type'=>'product','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_wpbbshop_demo_product','meta_value'=>'1'));
    $deleted = 0;
    foreach ($ids as $id) { if (wp_delete_post($id, true)) { $deleted++; } }
    delete_option('wpbbshop_green_demo_seeded');
    if (function_exists('wpbbshop_compare_feed_invalidate')) { wpbbshop_compare_feed_invalidate(); }
    return $deleted;
}

function wpbbshop_green_migrate_v2() {
    $done = get_option('wpbbshop_green_version', '');
    if ($done === WPBBSHOP_GREEN_V2) { return; }
    $opts = get_option('wpbbshop_theme_options', array());
    if (!is_array($opts)) { $opts = array(); }
    $opts['accent_color'] = '#2f8b49';
    $opts['dark_header'] = '0';
    $opts['show_topbar'] = '1';
    $opts['sticky_header'] = '1';
    $opts['logo_id'] = '';
    $opts['logo_width'] = '300';
    $opts['logo_height'] = '86';
    $opts['hero_title'] = 'Praktiskas lietas darbam, dārzam un mājai';
    $opts['hero_subtitle'] = 'Instrumenti, tehnika un noderīgi piederumi ar ērtu piegādi visā Latvijā un saņemšanu Rīgā.';
    $opts['hero_button'] = 'Apskatīt piedāvājumu';
    update_option('wpbbshop_theme_options', $opts, false);
    update_option('wpbbshop_green_version', WPBBSHOP_GREEN_V2, false);
}
add_action('after_switch_theme', 'wpbbshop_green_migrate_v2', 20);
add_action('init', 'wpbbshop_green_migrate_v2', 99);
add_action('admin_init', function(){
    // Keep admin loads lightweight. Demo catalogue import is manual only.
    wpbbshop_green_migrate_v2();
}, 30);


/** 2.3.4 Starter Setup helpers ************************************************/
function wpbbshop_green_sync_languages_lv_en() {
    if (!function_exists('PLL')) { return array('configured'=>false, 'reason'=>'polylang_inactive'); }
    $pll = PLL();
    if (!is_object($pll) || empty($pll->model) || !is_object($pll->model)) { return array('configured'=>false, 'reason'=>'model_unavailable'); }

    $language_model = isset($pll->model->languages) && is_object($pll->model->languages) ? $pll->model->languages : $pll->model;
    $modern = method_exists($language_model, 'get_list') && method_exists($language_model, 'add');
    $legacy = is_callable(array($pll->model, 'get_languages_list')) && is_callable(array($pll->model, 'add_language'));
    if (!$modern && !$legacy) { return array('configured'=>false, 'reason'=>'api_unavailable'); }

    try { $list = $modern ? $language_model->get_list() : $pll->model->get_languages_list(); }
    catch (Throwable $e) { return array('configured'=>false, 'reason'=>'list_failed'); }

    $defs = array(
        'en' => array('name'=>'English','locale'=>'en_GB','flag'=>'gb','term_group'=>0),
        'lv' => array('name'=>'Latviešu','locale'=>'lv','flag'=>'lv','term_group'=>1),
    );
    $by_slug = array();
    foreach ((array)$list as $lang) {
        $slug = sanitize_key(is_object($lang) ? (isset($lang->slug) ? $lang->slug : '') : (isset($lang['slug']) ? $lang['slug'] : ''));
        if ($slug) { $by_slug[$slug] = $lang; }
    }

    foreach ($defs as $slug=>$def) {
        if (isset($by_slug[$slug])) { continue; }
        $data = array('name'=>$def['name'],'slug'=>$slug,'locale'=>$def['locale'],'rtl'=>false,'flag'=>$def['flag'],'no_default_cat'=>false,'term_group'=>$def['term_group']);
        try { $created = $modern ? $language_model->add($data) : $pll->model->add_language($data); }
        catch (Throwable $e) { $created = false; }
        if ($created && !is_wp_error($created)) { $by_slug[$slug] = $created; }
    }

    // Latvian is the project default before any other languages are removed.
    try {
        if ($modern && method_exists($language_model, 'update_default')) { $language_model->update_default('en'); }
        elseif (is_callable(array($pll->model, 'update_default_lang'))) { $pll->model->update_default_lang('en'); }
    } catch (Throwable $e) {}

    // Refresh once, then use Polylang's own deletion API so translation/menu state is cleaned correctly.
    try { $list = $modern ? $language_model->get_list() : $pll->model->get_languages_list(); } catch (Throwable $e) { $list = array(); }
    $removed = array();
    foreach ((array)$list as $lang) {
        $slug = sanitize_key(is_object($lang) ? (isset($lang->slug) ? $lang->slug : '') : '');
        $term_id = absint(is_object($lang) && isset($lang->term_id) ? $lang->term_id : 0);
        if (!$slug || in_array($slug, array('lv','en'), true) || !$term_id) { continue; }
        try {
            $ok = $modern && method_exists($language_model, 'delete') ? $language_model->delete($term_id) : (is_callable(array($pll->model,'delete_language')) ? $pll->model->delete_language($term_id) : false);
            if ($ok) { $removed[] = $slug; }
        } catch (Throwable $e) {}
    }

    $opts = get_option('polylang', array());
    if (!is_array($opts)) { $opts = array(); }
    $opts['default_lang'] = 'en';
    $opts['hide_default'] = true;
    $opts['media_support'] = false;
    if (!empty($opts['nav_menus']) && is_array($opts['nav_menus'])) {
        foreach ($opts['nav_menus'] as $theme_key=>$locations) {
            if (!is_array($locations)) { continue; }
            foreach ($locations as $location=>$map) {
                if (is_array($map)) {
                    $opts['nav_menus'][$theme_key][$location] = array_intersect_key($map, array('lv'=>true,'en'=>true));
                }
            }
        }
    }
    update_option('polylang', $opts, false);
    update_option('WPLANG', 'en_GB', false);
    update_option('wp_theme_language_switcher_enabled', '1', false);
    update_option('wp_theme_demo_language_bar_enabled', '1', false);
    update_option('wp_theme_demo_polylang_setup_version', 'wpbbshop-2.3.4', false);
    update_option('wpbbshop_lv_en_sync_234', gmdate('c'), false);
    return array('configured'=>true,'removed'=>$removed);
}

function wpbbshop_green_cleanup_parent_demo() {
    if (!class_exists('WooCommerce')) { return 0; }
    $ids = get_posts(array(
        'post_type'=>'product','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids',
        'meta_key'=>'_wpbb_child_woo_demo_product','meta_value'=>'1',
    ));
    $deleted = 0;
    foreach ($ids as $id) {
        if (get_post_meta($id, '_wpbbshop_demo_product', true) === '1') { continue; }
        if (wp_delete_post($id, true)) { $deleted++; }
    }
    foreach (array('accessories','apparel','bundles','home-living','office','tech') as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term && !is_wp_error($term) && (int)$term->count === 0) { wp_delete_term($term->term_id, 'product_cat'); }
    }
    return $deleted;
}

function wpbbshop_green_seed_demo_products_lightweight() {
    if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) { return array('created'=>0,'updated'=>0); }
    $category_ids = array();
    foreach (array_values(array_unique(array_map(function($item){ return isset($item['category']) ? $item['category'] : ''; }, wpbbshop_green_demo_catalog()))) as $category) {
        if ($category === '') { continue; }
        $term = term_exists($category, 'product_cat');
        if (!$term) { $term = wp_insert_term($category, 'product_cat'); }
        if (!is_wp_error($term)) {
            $tid = absint(is_array($term) ? $term['term_id'] : $term);
            $category_ids[$category] = $tid;
            if (function_exists('pll_set_term_language')) { @pll_set_term_language($tid, 'en'); }
        }
    }
    $created=0; $updated=0;
    foreach (wpbbshop_green_demo_catalog() as $item) {
        $sku = sanitize_text_field((string)$item['sku']);
        $id = wc_get_product_id_by_sku($sku);
        $is_new = !$id;
        if (!$id) {
            $p = new WC_Product_Simple();
            try { $p->set_sku($sku); } catch (Exception $e) { continue; }
        } else { $p = wc_get_product($id); }
        if (!$p || !is_a($p,'WC_Product')) { continue; }
        $p->set_name($item['name']);
        $p->set_status('publish');
        $p->set_catalog_visibility('visible');
        $p->set_regular_price($item['regular']);
        $p->set_sale_price($item['sale']);
        $p->set_manage_stock(true);
        $p->set_stock_quantity(absint($item['stock']));
        $p->set_stock_status('instock');
        $p->set_short_description($item['short']);
        $p->set_description($item['description']);
        $p->set_featured(!empty($item['featured']));
        if (isset($category_ids[$item['category']])) { $p->set_category_ids(array($category_ids[$item['category']])); }
        // Deliberately do not create/update attachments here. Demo cards read the bundled theme image by SKU.
        $p->set_image_id(0);
        $p->set_gallery_image_ids(array());
        try { $saved_id=$p->save(); } catch (Exception $e) { continue; }
        if (!$saved_id) { continue; }
        update_post_meta($saved_id,'_wpbbshop_demo_product','1');
        update_post_meta($saved_id,'_wpbbshop_demo_key',$sku);
        update_post_meta($saved_id,'_wpbbshop_demo_image',isset($item['image']) ? sanitize_file_name($item['image']) : '');
        update_post_meta($saved_id,'_wpbbshop_feed_brand',isset($item['brand']) ? sanitize_text_field($item['brand']) : 'GREEN GARDEN');
        update_post_meta($saved_id,'_wpbbshop_feed_model',$item['model']);
        update_post_meta($saved_id,'_wpbbshop_feed_mpn',$sku);
        update_post_meta($saved_id,'_wpbbshop_feed_exclude','yes');
        if (function_exists('pll_set_post_language')) { @pll_set_post_language($saved_id,'en'); }
        if ($is_new) { $created++; } else { $updated++; }
    }
    update_option('wpbbshop_light_demo_seed_234', gmdate('c'), false);
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    return array('created'=>$created,'updated'=>$updated);
}

function wpbbshop_green_refresh_demo_catalog() {
    if (!class_exists('WooCommerce')) {
        return new WP_Error('woocommerce_missing', 'WooCommerce is required.');
    }
    if (!function_exists('wpbbshop_megastore_seed_500_312')) {
        return new WP_Error('megastore_missing', 'Home & Garden catalogue engine is not available.');
    }
    $result = wpbbshop_megastore_seed_500_312();
    if (is_wp_error($result)) { return $result; }
    return array(
        'deleted_products' => 0,
        'deleted_assets' => 0,
        'created' => isset($result['created']) ? absint($result['created']) : 0,
        'updated' => isset($result['updated']) ? absint($result['updated']) : 0,
    );
}

add_action('admin_menu', function(){
    add_theme_page('WP BB HOME & GARDEN Demo', 'WP BB HOME & GARDEN Demo', 'manage_options', 'wpbbshop-green-demo', 'wpbbshop_green_demo_admin_page');
}, 30);

function wpbbshop_green_demo_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    $notice = isset($_GET['ll_demo_notice']) ? sanitize_text_field(wp_unslash($_GET['ll_demo_notice'])) : '';
    ?>
    <div class="wrap ll-demo-admin">
      <style>
        .ll-demo-admin{max-width:1120px;margin-top:18px}.ll-demo-shell{display:grid;gap:18px}.ll-demo-hero{padding:26px 28px;border-radius:20px;background:linear-gradient(135deg,#123d26,#228145);color:#fff;box-shadow:0 18px 42px rgba(18,51,74,.09)}.ll-demo-hero h1{margin:4px 0 8px;color:#fff;font-size:32px;line-height:1.05}.ll-demo-hero p{max-width:800px;margin:0;color:rgba(255,255,255,.9);font-size:15px;line-height:1.55}.ll-demo-kicker{display:inline-flex;align-items:center;min-height:27px;padding:0 10px;border-radius:999px;background:rgba(255,255,255,.14);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.ll-demo-grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:18px}.ll-demo-card{background:#fff;border:1px solid #dbe5df;border-radius:18px;padding:22px;box-shadow:0 10px 28px rgba(18,51,74,.04)}.ll-demo-card h2{margin:0 0 7px;font-size:21px}.ll-demo-card p{margin:0 0 15px;color:#5e6c76;line-height:1.55}.ll-demo-card.is-main{border-color:#c9dfd0;background:linear-gradient(180deg,#fff,#f5faf6)}.ll-demo-refresh{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px;border-radius:15px;background:#eef8f0;border:1px solid #d2e8d7}.ll-demo-refresh strong{display:block;margin-bottom:4px;color:#174c2a}.ll-demo-refresh small{display:block;color:#5f6f78;line-height:1.45}.ll-demo-admin .button-primary{min-height:44px;padding:0 18px;border-radius:11px;background:#2b9748;border-color:#2b9748;font-weight:700}.ll-demo-admin .button-primary:hover{background:#227f3c;border-color:#227f3c}.ll-demo-danger{margin-top:16px;padding-top:16px;border-top:1px solid #e5ece8}.ll-demo-danger .button{color:#a6232c;border-color:#d6a9ad}.ll-demo-list{display:grid;gap:10px;margin:14px 0 0}.ll-demo-list span{display:flex;align-items:flex-start;gap:9px;color:#314957}.ll-demo-list span:before{content:'✓';display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#e8f6eb;color:#21783f;font-weight:800;flex:0 0 20px}.ll-demo-feed code{display:block;margin-top:6px;padding:8px 10px;white-space:normal;word-break:break-all}.ll-demo-note{margin-top:12px!important;font-size:12px}.ll-demo-version{margin-top:12px;font-size:12px;color:#82909a}.ll-demo-alert{position:relative;display:grid;grid-template-columns:34px minmax(0,1fr) 34px;align-items:center;gap:10px;margin:0;padding:12px 12px 12px 14px;border:1px solid #b8dfc2;border-radius:14px;background:#f0faf3!important;color:#173c25!important;box-shadow:0 8px 22px rgba(18,76,42,.06)}.ll-demo-alert p{margin:0!important;color:#173c25!important;font-size:14px!important;line-height:1.45!important;font-weight:650!important}.ll-demo-alert-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:#248a45;color:#fff;font-size:16px;font-weight:900}.ll-demo-alert-close{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:9px;color:#315a3d!important;text-decoration:none!important;font-size:22px;font-weight:700;line-height:1}.ll-demo-alert-close:hover,.ll-demo-alert-close:focus{background:#dff1e4;color:#173c25!important;box-shadow:none!important}.ll-demo-admin .notice,.ll-demo-admin .notice p{color:#173c25!important}.ll-demo-admin .notice-success{background:#f0faf3!important;border-left-color:#248a45!important}.ll-demo-admin .notice-warning{background:#fff8e8!important;color:#6a4900!important}.ll-demo-admin .notice-error{background:#fff0f1!important;color:#7a1f28!important}@media(max-width:850px){.ll-demo-grid{grid-template-columns:1fr}.ll-demo-refresh{align-items:flex-start;flex-direction:column}}
      </style>
      <div class="ll-demo-shell">
        <div class="ll-demo-hero">
          <span class="ll-demo-kicker">Demo Refresh</span>
          <h1>WP BB HOME & GARDEN Demo katalogs</h1>
          <p>Ar vienu pogu vari atjaunot visas WP BB HOME & GARDEN demo preces. Reālistiskie attēli un galerijas tiek ielādēti tieši no tēmas, tāpēc Refresh vairs nedarbina Imagick.</p>
        </div>
        <?php if ($notice) : ?>
          <div class="ll-demo-alert is-success" role="status" aria-live="polite">
            <span class="ll-demo-alert-icon" aria-hidden="true">✓</span>
            <p><?php echo esc_html($notice); ?></p>
            <a class="ll-demo-alert-close" href="<?php echo esc_url(remove_query_arg('ll_demo_notice')); ?>" aria-label="Aizvērt paziņojumu">×</a>
          </div>
        <?php endif; ?>
        <div class="ll-demo-grid">
          <section class="ll-demo-card is-main">
            <h2>Pilns Demo Refresh</h2>
            <p>Droši atjauno demo preču datus esošajās precēs, saglabā preču ID un izmanto tēmas iebūvētos attēlus / galerijas bez Media Library pārģenerēšanas.</p>
            <div class="ll-demo-refresh">
              <div><strong>Nomainīt visu Demo katalogu</strong><small>Atjaunos cenas, akcijas, noliktavu, kategorijas, attēlus un galerijas. Esošās preču ID paliek nemainīgas.</small></div>
              <a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbbshop_green_refresh_demo'),'wpbbshop_green_refresh_demo')); ?>" onclick="return confirm('Atjaunot visas WP BB HOME & GARDEN demo preces drošajā režīmā? Tavi īstie produkti netiks mainīti.');">Refresh visu Demo</a>
            </div>
            <div class="ll-demo-list">
              <span>Reālistiskie produktu attēli tiek ielādēti tieši no tēmas failiem.</span>
              <span>Produktu galerijas darbojas no tēmas failiem un neprasa Media Library apstrādi.</span>
              <span>Cenas, akcijas, noliktava, kategorijas un apraksti tiek atgriezti uz Demo noklusējumu.</span>
              <span>“3 izvēles” izvēlētās demo preces pēc Refresh paliek derīgas, jo preču ID netiek pārradītas.</span>
            </div>
            <div class="ll-demo-danger">
              <p><strong>Ja demo vairs nevajag:</strong></p>
              <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbbshop_green_remove_demo'),'wpbbshop_green_remove_demo')); ?>" onclick="return confirm('Dzēst visas WP BB HOME & GARDEN demo preces?');">Dzēst Demo katalogu</a>
            </div>
          </section>
          <aside class="ll-demo-card ll-demo-feed">
            <h2>XML plūsmas</h2>
            <p>Demo preces drošības dēļ paliek izslēgtas no cenu salīdzināšanas plūsmām, kamēr tās nav aizstātas ar reāliem produktiem.</p>
            <strong>KurPirkt.lv</strong><code><?php echo esc_html(function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('kurpirkt') : home_url('/?wpbbshop_compare_feed=kurpirkt')); ?></code>
            <strong>Salidzini.lv</strong><code><?php echo esc_html(function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('salidzini') : home_url('/?wpbbshop_compare_feed=salidzini')); ?></code>
            <strong>Ceno.lv</strong><code><?php echo esc_html(function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('ceno') : home_url('/?wpbbshop_compare_feed=ceno')); ?></code>
            <p class="ll-demo-note"><strong>Drošais Refresh:</strong> Media Library un Imagick netiek darbināti.</p><p class="ll-demo-note">Refresh neietekmē tavus īstos produktus, pasūtījumus vai klientu datus.</p>
            <div class="ll-demo-version">Theme v<?php echo esc_html(WPBBSHOP_GREEN_V2); ?></div>
          </aside>
        </div>
      </div>
    </div><?php
}

add_action('admin_post_wpbbshop_green_refresh_demo', function(){
    if (!current_user_can('manage_options')) { wp_die('Nav tiesību.'); }
    check_admin_referer('wpbbshop_green_refresh_demo');
    $result = wpbbshop_green_refresh_demo_catalog();
    if (is_wp_error($result)) {
        $message = $result->get_error_message();
    } else {
        $message = sprintf('Demo Refresh pabeigts drošajā režīmā: noņemtas %d novecojušas demo preces, izveidotas %d jaunas un atjaunoti produktu dati / tēmas galerijas.', $result['deleted_products'], $result['created']);
    }
    wp_safe_redirect(add_query_arg(array('page'=>'wpbbshop-platform','tab'=>'demo','wpbb_notice'=>$message), admin_url('themes.php'))); exit;
});

add_action('admin_post_wpbbshop_green_seed_demo', function(){
    if (!current_user_can('manage_options')) { wp_die('Nav tiesību.'); }
    check_admin_referer('wpbbshop_green_seed_demo');
    $result = function_exists('wpbbshop_green_seed_demo_products_lightweight') ? wpbbshop_green_seed_demo_products_lightweight() : array('created'=>0,'updated'=>0);
    $message = is_wp_error($result) ? $result->get_error_message() : sprintf('Demo katalogs atjaunots: %d jaunas, %d atjaunotas preces.', $result['created'], $result['updated']);
    wp_safe_redirect(add_query_arg(array('page'=>'wpbbshop-platform','tab'=>'demo','wpbb_notice'=>$message), admin_url('themes.php'))); exit;
});
add_action('admin_post_wpbbshop_green_remove_demo', function(){
    if (!current_user_can('manage_options')) { wp_die('Nav tiesību.'); }
    check_admin_referer('wpbbshop_green_remove_demo');
    $deleted = wpbbshop_green_remove_demo_catalog();
    wp_safe_redirect(add_query_arg(array('page'=>'wpbbshop-platform','tab'=>'demo','wpbb_notice'=>sprintf('Dzēstas %d demo preces.', $deleted)), admin_url('themes.php'))); exit;
});
