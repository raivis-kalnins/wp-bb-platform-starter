<?php
/** WP BB Home & Garden v4.0.12 — WP Theme Woo Support smart-filter integration. */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V412_VERSION')) define('WPBBSHOP_V412_VERSION', '4.0.12');

/** Keep the plugin in Store mode for this child theme unless explicitly saved otherwise. */
add_filter('wp_theme_woo_support_default_profile', function($profile){ return 'store'; }, PHP_INT_MAX);

/** Render Home & Garden cards inside plugin-owned AJAX filter results. */
add_filter('wp_theme_woo_support_filter_product_item_html', function($html, $product){
    if (!$product instanceof WC_Product || !function_exists('wpbbshop_green_product_card')) return $html;
    return '<li class="product iws-theme-product-item wpbbshop-bs-product-col">' . wpbbshop_green_product_card($product) . '</li>';
}, 20, 2);

/** Preserve active Polylang language in plugin AJAX filter requests. */
add_filter('gettext', function($translated, $text, $domain){
    if ($domain !== 'wp-theme-woo-support' || !function_exists('pll_current_language') || pll_current_language('slug') !== 'lv') return $translated;
    $map = array(
        'Search'=>'Meklēt','Search products'=>'Meklēt preces','Clear search'=>'Notīrīt meklēšanu',
        'Open product comparison'=>'Atvērt preču salīdzināšanu','In stock'=>'Noliktavā','On sale'=>'Akcijā',
        'Sort products'=>'Kārtot preces','Sort'=>'Kārtot','More filters'=>'Vairāk filtru','Price'=>'Cena',
        'Height'=>'Augstums','Width'=>'Platums','Length'=>'Garums','Minimum rating'=>'Minimālais vērtējums',
        'Any rating'=>'Jebkurš vērtējums','Brand'=>'Zīmols','All brands'=>'Visi zīmoli','Category'=>'Kategorija',
        'All categories'=>'Visas kategorijas','Filter'=>'Filtrēt','Reset'=>'Notīrīt','Load more'=>'Ielādēt vairāk',
        'Loading...'=>'Ielādē…','Compare products'=>'Salīdzināt preces','Add to comparison'=>'Pievienot salīdzināšanai',
        'No products found'=>'Preces nav atrastas','No products found.'=>'Preces nav atrastas.',
        'Default sorting'=>'Noklusējuma kārtošana','Popularity'=>'Popularitāte','Average rating'=>'Vidējais vērtējums',
        'Newest'=>'Jaunākās','Price: low to high'=>'Cena: augoši','Price: high to low'=>'Cena: dilstoši',
    );
    return isset($map[$text]) ? $map[$text] : $translated;
}, 50, 3);

/** Ensure filter/compare assets are considered necessary on this theme archive. */
add_filter('iws_filter_frontend_needs_assets', function($needs){
    if ((function_exists('is_front_page') && is_front_page()) || (function_exists('is_shop') && (is_shop() || is_product_taxonomy() || is_post_type_archive('product')))) return true;
    return $needs;
}, 20);

/** Final visual ownership: larger readable header brand + green smart filter. */
add_action('wp_head', function(){
    if (is_admin()) return; ?>
<style id="wpbbshop-v412-final-css">
@media (min-width:1280px){
 body.wpbbshop-theme #wpbbshop-desktop-mainbar.ll24-mainbar{grid-template-columns:260px minmax(0,1fr) 320px!important;column-gap:24px!important;padding-top:14px!important;padding-bottom:14px!important}
 body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo{width:260px!important;max-width:260px!important;height:82px!important;min-height:82px!important}
 body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo .wpbbshop-brand-v410{width:250px!important;max-width:250px!important}
 body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo .wpbbshop-brand-v410 img,
 body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo img{width:250px!important;max-width:250px!important;max-height:80px!important}
}
@media (min-width:1050px) and (max-width:1279px){
 body.wpbbshop-theme #wpbbshop-desktop-mainbar.ll24-mainbar{grid-template-columns:220px minmax(0,1fr) 292px!important}
 body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo{width:220px!important;max-width:220px!important;height:76px!important}
 body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo .wpbbshop-brand-v410,
 body.wpbbshop-theme #wpbbshop-desktop-mainbar>.ll24-logo img{width:210px!important;max-width:210px!important;max-height:74px!important}
}
body.wpbbshop-theme .ll24-actions>button.wpbbshop-header-compare,
body.wpbbshop-theme .llg-mobile-actions>button.wpbbshop-header-compare{border:0;background:#fff;color:inherit;cursor:pointer;font:inherit}
body.wpbbshop-theme .ll24-actions>button.wpbbshop-header-compare{position:relative;min-width:78px;height:72px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;border-radius:9px}
body.wpbbshop-theme .ll24-actions>button.wpbbshop-header-compare:hover{background:#eef8f0}
body.wpbbshop-theme .ll24-actions>button.wpbbshop-header-compare em{position:absolute;top:4px;right:9px;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#0f7a49;color:#fff;font-style:normal;font-size:9px;line-height:18px}
body.wpbbshop-theme .wpbbshop-v412-smart-filter{margin:12px 0 14px}
body.wpbbshop-theme .wpbbshop-v412-smart-filter .iws-filter-wrapper{margin:0!important;padding:14px!important;border:1px solid #dce7df!important;border-radius:12px!important;box-shadow:0 5px 18px rgba(18,51,74,.035)!important;background:#fff!important}
body.wpbbshop-theme .wpbbshop-v412-smart-filter .iws-search-input-wrap input[type=search]{border-color:#d6e2da!important}
body.wpbbshop-theme .wpbbshop-v412-smart-filter .iws-filter-more{border-color:#dce7df!important;background:#f8fbf8!important}
body.wpbbshop-theme .wpbbshop-v412-smart-filter .iws-filter-more summary{font-size:13px!important;font-weight:800!important;letter-spacing:0!important;color:#12354c!important}
body.wpbbshop-theme .wpbbshop-v412-smart-filter .iws-filter-submit,
body.wpbbshop-theme .wpbbshop-v412-smart-filter .iws-load-more{background:#0f7a49!important;border-color:#0f7a49!important;color:#fff!important;border-radius:8px!important}
body.wpbbshop-theme .wpbbshop-v412-smart-filter .iws-filter-reset{border-radius:8px!important}
body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-result-count{font-size:11px!important;color:#5d6c64!important;margin:8px 0 10px!important}
body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-products-grid{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:12px!important;margin:0!important;padding:0!important;list-style:none!important}
body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-theme-product-item{margin:0!important;padding:0!important;background:transparent!important;border:0!important;border-radius:0!important;overflow:visible!important}
body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-theme-product-item>.llg-product-card{height:100%!important}
body.wpbbshop-theme .iws-compare-toggle{z-index:7!important;width:32px!important;height:32px!important;border:1px solid #d7e5db!important;border-radius:50%!important;background:#fff!important;color:#12354c!important;padding:0!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;cursor:pointer!important}
body.wpbbshop-theme .iws-compare-toggle.is-selected{background:#0f7a49!important;border-color:#0f7a49!important;color:#fff!important}
body.wpbbshop-theme .iws-compare-toggle svg{width:17px!important;height:17px!important} body.wpbbshop-theme .iws-compare-toggle svg path{fill:none!important;stroke:currentColor!important;stroke-width:2!important}
body.wpbbshop-theme .iws-load-more-wrap{text-align:center!important;padding-top:18px!important}
body.wpbbshop-theme .iws-load-more{min-width:280px!important;min-height:50px!important;background:linear-gradient(180deg,#1b9d5e,#0f7a49)!important;color:#fff!important;border:0!important;border-radius:10px!important;font-weight:800!important;box-shadow:0 10px 24px rgba(15,122,73,.18)!important}
@media(min-width:1450px){body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-products-grid{grid-template-columns:repeat(5,minmax(0,1fr))!important}}
@media(max-width:1180px){body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-products-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media(max-width:820px){body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-products-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:9px!important}.wpbbshop-v412-smart-filter .iws-filter-more__content{grid-template-columns:1fr 1fr!important}}
@media(max-width:540px){body.wpbbshop-theme .wpbbshop-v412-plugin-results .iws-products-grid{grid-template-columns:1fr!important}.wpbbshop-v412-smart-filter .iws-filter-more__content{grid-template-columns:1fr!important}}
</style>
<?php }, PHP_INT_MAX);
