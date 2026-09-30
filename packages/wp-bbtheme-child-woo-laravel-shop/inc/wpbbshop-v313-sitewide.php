<?php
/**
 * WP BB Home & Garden 3.0.13 — site-wide commerce/content hardening.
 * Fixes inherited Bootstrap widths, product/archive layouts, managed pages,
 * direct XML routes, new-arrivals routing and Latvian quote strings.
 */
defined('ABSPATH') || exit;

function wpbbshop_v313_is_en() {
    return function_exists('pll_current_language') && pll_current_language('slug') === 'en';
}
function wpbbshop_v313_t($lv, $en) { return wpbbshop_v313_is_en() ? $en : $lv; }

/** Always use the child-theme product/archive templates after parent/plugin filters. */
add_filter('template_include', function($template) {
    if (function_exists('is_product') && is_product()) {
        $custom = get_stylesheet_directory() . '/woocommerce/single-product.php';
        if (file_exists($custom)) { return $custom; }
    }
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
        $custom = get_stylesheet_directory() . '/woocommerce/archive-product.php';
        if (file_exists($custom)) { return $custom; }
    }
    $path = isset($_SERVER['REQUEST_URI']) ? (string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
    if (trim($path, '/') === '' && isset($_GET['orderby']) && sanitize_key(wp_unslash($_GET['orderby'])) === 'date') {
        $custom = get_stylesheet_directory() . '/catalog-date.php';
        if (file_exists($custom)) { return $custom; }
    }
    return $template;
}, PHP_INT_MAX);

/** Direct feed routing so /kurpirkt.xml and /salidzini.xml work even before rewrite rules are flushed. */
function wpbbshop_v313_direct_feed_route() {
    if (is_admin()) { return; }
    $path = isset($_SERVER['REQUEST_URI']) ? (string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
    if (!preg_match('#/(kurpirkt|salidzini)\.xml/?$#i', $path, $m)) { return; }
    $feed = strtolower($m[1]);
    if (!function_exists('wpbbshop_compare_feed_generate') || !class_exists('WooCommerce')) {
        status_header(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'WooCommerce feed is not available.';
        exit;
    }
    $xml = wpbbshop_compare_feed_generate($feed);
    if (!is_string($xml) || $xml === '') {
        status_header(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Feed generation failed.';
        exit;
    }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex, follow', true);
    header('Cache-Control: public, max-age=900, stale-while-revalidate=3600');
    echo $xml;
    exit;
}
add_action('template_redirect', 'wpbbshop_v313_direct_feed_route', 0);

/** Managed contact + terms pages. Do not overwrite an existing editor-created page. */
function wpbbshop_v313_page_content($key, $lang) {
    $en = ($lang === 'en');
    if ($key === 'contact') {
        if (!$en && function_exists('wpbbshop_information_page_definitions') && function_exists('wpbbshop_information_page_content')) {
            $defs = wpbbshop_information_page_definitions();
            if (isset($defs['kontakti'])) { return wpbbshop_information_page_content($defs['kontakti'], 'kontakti'); }
        }
        return '<section class="llg-info-pro"><div class="llg-info-intro"><span>WP BB HOME & GARDEN • SMILTENE</span><h2>' . ($en ? 'Contact us' : 'Sazinies ar mums') . '</h2><p>' . ($en ? 'Questions about products, delivery, orders or compatibility? Send us a message and we will reply as soon as possible.' : 'Jautājumi par precēm, piegādi, pasūtījumu vai savietojamību? Nosūti ziņu, un mēs atbildēsim pēc iespējas ātrāk.') . '</p></div><div class="llg-info-cards"><article><h3>' . ($en ? 'Location' : 'Atrašanās vieta') . '</h3><p>Smiltene, LV-4729</p></article><article><h3>' . ($en ? 'Delivery' : 'Piegāde') . '</h3><p>' . ($en ? 'Delivery throughout Latvia and pickup in Smiltene by arrangement.' : 'Piegāde visā Latvijā un saņemšana Smiltenē pēc vienošanās.') . '</p></article><article><h3>' . ($en ? 'Message' : 'Ziņa') . '</h3><p>' . ($en ? 'Use the form below to contact the store.' : 'Izmanto zemāk esošo formu, lai sazinātos ar veikalu.') . '</p></article></div><div class="llg-contact-managed"><h3>' . ($en ? 'Write to us' : 'Raksti mums') . '</h3>[wpbbshop_contact_form]</div></section>';
    }
    return '<section class="llg-info-pro"><div class="llg-info-intro"><span>' . ($en ? 'PURCHASE TERMS' : 'PIRKŠANAS NOTEIKUMI') . '</span><h2>' . ($en ? 'Clear terms for online purchases' : 'Skaidri noteikumi pirkumiem internetā') . '</h2><p>' . ($en ? 'These terms describe the main order, payment, delivery, return and warranty principles for purchases from WP BB Home & Garden.' : 'Šie noteikumi apkopo galvenos pasūtīšanas, apmaksas, piegādes, atgriešanas un garantijas principus pirkumiem WP BB Home & Garden veikalā.') . '</p></div><div class="llg-info-cards"><article><h3>' . ($en ? 'Orders and prices' : 'Pasūtījumi un cenas') . '</h3><p>' . ($en ? 'The price and availability shown on the product page apply when the order is placed. We confirm any material change before fulfilment.' : 'Pasūtījuma veikšanas brīdī piemērojama preces lapā norādītā cena un pieejamība. Būtiskas izmaiņas pirms izpildes tiek saskaņotas ar klientu.') . '</p></article><article><h3>' . ($en ? 'Payment' : 'Apmaksa') . '</h3><p>' . ($en ? 'Available methods may include cash on pickup, bank transfer and enabled online payment gateways.' : 'Pieejama apmaksa skaidrā naudā saņemot, bankas pārskaitījums un aktivizētās tiešsaistes maksājumu metodes.') . '</p></article><article><h3>' . ($en ? 'Delivery' : 'Piegāde') . '</h3><p>' . ($en ? 'Parcel locker, courier and Smiltene pickup options depend on product size, weight and destination.' : 'Pakomāta, kurjera un saņemšanas Smiltenē iespējas ir atkarīgas no preces izmēra, svara un galamērķa.') . '</p></article><article><h3>' . ($en ? 'Returns and warranty' : 'Atgriešana un garantija') . '</h3><p>' . ($en ? 'Returns, complaints and warranty claims are handled in accordance with applicable Latvian law and the condition of the product.' : 'Atgriešana, pretenzijas un garantijas pieteikumi tiek izskatīti saskaņā ar piemērojamajiem Latvijas normatīvajiem aktiem un preces stāvokli.') . '</p></article></div><div class="llg-info-split"><div><h3>' . ($en ? 'Before placing an order' : 'Pirms pasūtījuma') . '</h3><ul><li>' . ($en ? 'Check product compatibility and specifications' : 'Pārbaudi preces savietojamību un specifikāciju') . '</li><li>' . ($en ? 'Choose a suitable delivery method' : 'Izvēlies piemērotu piegādes veidu') . '</li><li>' . ($en ? 'Review contact and delivery information' : 'Pārbaudi kontaktinformāciju un piegādes adresi') . '</li></ul></div><div><h3>' . ($en ? 'Need help?' : 'Nepieciešama palīdzība?') . '</h3><p>' . ($en ? 'Contact WP BB Home & Garden before purchase if you need clarification about a product, delivery or order.' : 'Ja pirms pirkuma nepieciešams precizējums par preci, piegādi vai pasūtījumu, sazinies ar WP BB Home & Garden.') . '</p></div></div></section>';
}

function wpbbshop_v313_upsert_missing_page($slug, $title, $content, $excerpt, $lang) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page) {
        if ($page->post_status !== 'publish') { wp_update_post(array('ID'=>$page->ID,'post_status'=>'publish')); }
        if (function_exists('pll_set_post_language')) { pll_set_post_language($page->ID, $lang); }
        return (int) $page->ID;
    }
    $id = wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>$slug,'post_content'=>$content,'post_excerpt'=>$excerpt,'comment_status'=>'closed','ping_status'=>'closed'), true);
    if (is_wp_error($id) || !$id) { return 0; }
    if (function_exists('pll_set_post_language')) { pll_set_post_language($id, $lang); }
    return (int) $id;
}
function wpbbshop_v313_ensure_pages() {
    if (!current_user_can('edit_theme_options')) { return; }
    $contact_lv = wpbbshop_v313_upsert_missing_page('kontakti','Kontakti',wpbbshop_v313_page_content('contact','lv'),'Sazinies ar WP BB Home & Garden par precēm, piegādi un pasūtījumiem.','lv');
    $contact_en = wpbbshop_v313_upsert_missing_page('contact','Contact',wpbbshop_v313_page_content('contact','en'),'Contact WP BB Home & Garden about products, delivery and orders.','en');
    $terms_lv = wpbbshop_v313_upsert_missing_page('pirksanas-noteikumi','Pirkšanas noteikumi',wpbbshop_v313_page_content('terms','lv'),'WP BB Home & Garden pasūtīšanas, apmaksas, piegādes un atgriešanas pamatnoteikumi.','lv');
    $terms_en = wpbbshop_v313_upsert_missing_page('terms-and-conditions','Terms & conditions',wpbbshop_v313_page_content('terms','en'),'WP BB Home & Garden order, payment, delivery and return terms.','en');
    if (function_exists('pll_save_post_translations')) {
        if ($contact_lv && $contact_en) { pll_save_post_translations(array('lv'=>$contact_lv,'en'=>$contact_en)); }
        if ($terms_lv && $terms_en) { pll_save_post_translations(array('lv'=>$terms_lv,'en'=>$terms_en)); }
    }
    if (get_option('wpbbshop_v313_rewrite_version') !== '3.0.13') {
        if (function_exists('wpbbshop_compare_feed_register_rewrites')) { wpbbshop_compare_feed_register_rewrites(); }
        flush_rewrite_rules(false);
        update_option('wpbbshop_v313_rewrite_version','3.0.13',false);
    }
}
add_action('after_switch_theme','wpbbshop_v313_ensure_pages',120);
add_action('admin_init','wpbbshop_v313_ensure_pages',120);

/** Latvian front-end strings for the quote/cart helper supplied by the parent/plugin. */
add_filter('gettext', function($translated, $text, $domain) {
    if (is_admin() || wpbbshop_v313_is_en()) { return $translated; }
    $map = array(
        'My Quote' => 'Cenu pieprasījums',
        'Add to Quote' => 'Pievienot cenu pieprasījumam',
        'Request a Quote' => 'Pieprasīt cenu',
        'Your selection' => 'Jūsu izvēle',
        'Shopping cart' => 'Izvēlētās preces',
        'Your cart is ready when you are.' => 'Jūsu izvēlētās preces ir gatavas.',
        'Browse the catalogue and add products to compare your choices here.' => 'Pievienojiet preces, lai sagatavotu cenu pieprasījumu.',
        'Continue shopping' => 'Turpināt iepirkties',
        'View cart' => 'Apskatīt grozu',
        'Checkout' => 'Noformēt pasūtījumu',
    );
    return isset($map[$text]) ? $map[$text] : $translated;
}, 9999, 3);

/** Nicer wishlist/compare route before the legacy anonymous renderer runs. */
function wpbbshop_v313_render_list_route() {
    if (!isset($_GET['wpbbshop_list']) || !class_exists('WooCommerce')) { return; }
    $list = sanitize_key(wp_unslash($_GET['wpbbshop_list']));
    if (!in_array($list, array('wishlist','compare'), true)) { return; }
    status_header(200);
    get_header();
    $products = function_exists('wpbbshop_get_products_from_ids') ? wpbbshop_get_products_from_ids(wpbbshop_get_list_items($list)) : array();
    $is_compare = ($list === 'compare');
    $title = $is_compare ? wpbbshop_v313_t('Salīdzināt preces','Compare products') : wpbbshop_v313_t('Vēlmju saraksts','Wishlist');
    echo '<main class="llg-v313-catalog-page"><div class="wpbbshop-container"><section class="llg-v313-page-hero"><span>WP BB HOME & GARDEN</span><h1>'.esc_html($title).'</h1><p>'.esc_html(wpbbshop_v313_t('Saglabātās preces no šīs ierīces vai konta.','Products saved on this device or account.')).'</p></section>';
    if (!$products) {
        echo '<section class="llg-v313-empty"><h2>'.esc_html(wpbbshop_v313_t('Saraksts ir tukšs','The list is empty')).'</h2><p>'.esc_html(wpbbshop_v313_t('Pievieno preces no veikala, lai tās parādītos šeit.','Add products from the shop and they will appear here.')).'</p><a class="llg-v313-btn" href="'.esc_url(wc_get_page_permalink('shop')).'">'.esc_html(wpbbshop_v313_t('Skatīt preces','Browse products')).'</a></section>';
    } elseif ($is_compare) {
        echo '<div class="llg-v313-compare">';
        foreach ($products as $product) { echo function_exists('wpbbshop_green_product_card') ? wpbbshop_green_product_card($product) : wpbbshop_product_card($product); }
        echo '</div>';
    } else {
        echo '<div class="wpbbshop-bootstrap-products llg-v313-list-grid">';
        foreach ($products as $product) { echo '<div class="wpbbshop-bs-product-col">'.(function_exists('wpbbshop_green_product_card') ? wpbbshop_green_product_card($product) : wpbbshop_product_card($product)).'</div>'; }
        echo '</div>';
    }
    echo '</div></main>';
    get_footer();
    exit;
}
add_action('template_redirect','wpbbshop_v313_render_list_route',1);

/** Add page/body markers used by the last CSS layer. */
add_filter('body_class', function($classes) {
    if (isset($_GET['orderby']) && sanitize_key(wp_unslash($_GET['orderby'])) === 'date') { $classes[] = 'llg-v313-new-arrivals'; }
    if (is_page(array('kontakti','contact','pirksanas-noteikumi','terms-and-conditions'))) { $classes[] = 'llg-v313-managed-info'; }
    return $classes;
}, 999);

/** Final layout layer. Loaded after old parent/theme rules. */
function wpbbshop_v313_css() {
    if (is_admin()) { return; }
    ?>
    <style id="wpbbshop-v313-sitewide-css">
    body.wpbbshop-theme .wpbbshop-container,
    body.wpbbshop-theme .wpbbshop-container.container,
    body.wpbbshop-theme .llg-single-container,
    body.wpbbshop-theme .llg-account-shell>.wpbbshop-container,
    body.wpbbshop-theme .llg-woo-page{
      width:min(1480px,calc(100% - 48px))!important;max-width:1480px!important;margin-left:auto!important;margin-right:auto!important;padding-left:0!important;padding-right:0!important;transform:none!important;
    }
    body.wpbbshop-theme .llg-archive-page,body.wpbbshop-theme .llg-commerce-shell,body.wpbbshop-theme .wpbbshop-page-shell,body.wpbbshop-theme .wpbbshop-search-page,body.wpbbshop-theme .llg-v313-catalog-page{margin-left:0!important;padding-left:0!important;width:100%!important;max-width:none!important}

    /* Header search: quiet light-grey, centered icon at the far right. */
    body.wpbbshop-theme .llg-search-extended{padding-right:54px!important;grid-template-columns:175px minmax(0,1fr)!important;position:relative!important;overflow:visible!important}
    body.wpbbshop-theme .llg-search-submit,body.wpbbshop-theme .llg-search-extended>.llg-search-submit,body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{
      position:absolute!important;right:0!important;top:0!important;bottom:0!important;left:auto!important;width:54px!important;min-width:54px!important;max-width:54px!important;height:52px!important;min-height:52px!important;margin:0!important;padding:0!important;border:0!important;border-left:1px solid #d7e0e5!important;border-radius:0 9px 9px 0!important;background:#eef2f4!important;color:#4d5d69!important;box-shadow:none!important;display:block!important;appearance:none!important;-webkit-appearance:none!important;
    }
    body.wpbbshop-theme .llg-search-submit:hover,body.wpbbshop-theme .llg-search-submit:focus-visible{background:#e3e9ec!important;color:#243846!important}
    body.wpbbshop-theme .llg-search-submit .wpbbshop-green-icon{display:none!important}
    body.wpbbshop-theme .llg-search-submit:before{content:""!important;position:absolute!important;width:16px!important;height:16px!important;border:2px solid currentColor!important;border-radius:50%!important;left:50%!important;top:50%!important;transform:translate(-58%,-58%)!important;box-sizing:border-box!important}
    body.wpbbshop-theme .llg-search-submit:after{content:""!important;position:absolute!important;width:8px!important;height:2px!important;background:currentColor!important;border:0!important;border-radius:2px!important;left:50%!important;top:50%!important;transform:translate(4px,5px) rotate(45deg)!important;transform-origin:left center!important}
    body.wpbbshop-theme .wpbbshop-search-results{padding:6px!important}
    body.wpbbshop-theme .wpbbshop-search-results-list{display:flex!important;flex-direction:column!important;grid-template-columns:none!important;gap:0!important;width:100%!important}
    body.wpbbshop-theme .wpbbshop-search-result{display:grid!important;grid-template-columns:72px minmax(0,1fr)!important;width:100%!important;gap:12px!important;padding:10px!important;border:0!important;border-bottom:1px solid #edf1f3!important;border-radius:0!important;background:#fff!important}
    body.wpbbshop-theme .wpbbshop-search-result:first-child{border-radius:9px 9px 0 0!important}body.wpbbshop-theme .wpbbshop-search-result:last-child{border-bottom:0!important}

    /* Category/archive pages: no Bootstrap .container side gap and consistent cards. */
    body.wpbbshop-theme .llg-archive-page{padding:28px 0 54px!important;background:#f5f7f9!important}
    body.wpbbshop-theme .llg-archive-page .wpbbshop-container{width:min(1480px,calc(100% - 48px))!important;max-width:1480px!important}
    body.wpbbshop-theme .wpbbshop-archive-layout{display:block!important;margin-top:18px!important}
    body.wpbbshop-theme .wpbbshop-archive-filter-col{display:none!important}
    body.wpbbshop-theme .wpbbshop-archive-products-col{width:100%!important;max-width:none!important;margin:0!important;padding:0!important;float:none!important}
    body.wpbbshop-theme .wpbbshop-bootstrap-products,body.wpbbshop-theme .llg-v313-list-grid{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:18px!important;width:100%!important;margin:0!important;padding:0!important}
    body.wpbbshop-theme .wpbbshop-bs-product-col{width:100%!important;min-width:0!important;max-width:none!important;margin:0!important;padding:0!important;float:none!important}
    body.wpbbshop-theme .llg-archive-page .llg-product-card{height:100%!important;min-width:0!important}
    body.wpbbshop-theme .llg-archive-page .llg-card-image{height:230px!important}
    body.wpbbshop-theme .wpbbshop-archive-toolbar{width:100%!important;margin:0 0 18px!important}

    /* Single product: one title, reliable image, balanced two-column layout. */
    body.single-product.wpbbshop-theme .llg-single-page{padding:30px 0 60px!important;background:#f5f7f9!important}
    body.single-product.wpbbshop-theme .llg-single-product-card{display:grid!important;grid-template-columns:minmax(0,1.08fr) minmax(390px,.92fr)!important;gap:28px!important;width:100%!important;margin:0!important;align-items:start!important}
    body.single-product.wpbbshop-theme .llg-single-gallery-panel,body.single-product.wpbbshop-theme .llg-single-summary-panel{width:100%!important;max-width:none!important;min-width:0!important}
    body.single-product.wpbbshop-theme .m38gallery-stage{height:520px!important;display:flex!important;align-items:center!important;justify-content:center!important;background:#fff!important;border-radius:12px!important;overflow:hidden!important}
    body.single-product.wpbbshop-theme .m38gallery-stage img{width:100%!important;height:100%!important;object-fit:contain!important;object-position:center!important;padding:14px!important}
    body.single-product.wpbbshop-theme .m38gallery-thumbs{display:flex!important;gap:8px!important;margin-top:10px!important;overflow-x:auto!important}
    body.single-product.wpbbshop-theme .m38gallery-thumb{flex:0 0 74px!important;width:74px!important;height:74px!important;border:1px solid #dfe7ec!important;border-radius:9px!important;background:#fff!important;padding:4px!important}
    body.single-product.wpbbshop-theme .m38gallery-thumb img{width:100%!important;height:100%!important;object-fit:contain!important}
    body.single-product.wpbbshop-theme .llg-single-summary-panel .product_title{font-size:clamp(30px,3vw,44px)!important;max-width:900px!important}
    body.single-product.wpbbshop-theme .llg-single-buybox{margin-top:18px!important}
    body.single-product.wpbbshop-theme .llg-single-tabs,body.single-product.wpbbshop-theme .wpbbshop-single-products-section{width:100%!important;max-width:none!important}
    body.single-product.wpbbshop-theme .woocommerce-tabs .panel{padding:24px!important;background:#fff!important;border:1px solid #e0e8ed!important;border-radius:0 12px 12px 12px!important}

    /* Account: neutralise WooCommerce float/width defaults. */
    body.woocommerce-account .llg-account-shell>.wpbbshop-container{width:min(1480px,calc(100% - 48px))!important;max-width:1480px!important}
    body.woocommerce-account .llg-myaccount-layout{display:grid!important;grid-template-columns:270px minmax(0,1fr)!important;gap:24px!important;width:100%!important;max-width:none!important}
    body.woocommerce-account .woocommerce-MyAccount-navigation,body.woocommerce-account .woocommerce-MyAccount-content{float:none!important;width:100%!important;max-width:none!important;margin:0!important}
    body.woocommerce-account .llg-myaccount-nav-card,body.woocommerce-account .llg-myaccount-content-card{width:100%!important;max-width:none!important;min-width:0!important}
    body.woocommerce-account .llg-myaccount-content-card>*{max-width:none!important}
    body.woocommerce-account .llg-dashboard-welcome p{max-width:900px!important}

    /* Search / wishlist / info pages share the archive proportions. */
    body.wpbbshop-theme .wpbbshop-search-page,body.wpbbshop-theme .llg-v313-catalog-page{background:#f5f7f9!important;padding:28px 0 56px!important;min-height:55vh!important}
    body.wpbbshop-theme .wpbbshop-search-hero,body.wpbbshop-theme .llg-v313-page-hero{margin:0 0 18px!important;padding:26px 28px!important;border:1px solid #dfe7ec!important;border-radius:14px!important;background:linear-gradient(120deg,#fff,#eef7f0)!important;box-shadow:0 7px 20px rgba(18,51,74,.04)!important}
    body.wpbbshop-theme .wpbbshop-search-hero h1,body.wpbbshop-theme .llg-v313-page-hero h1{margin:0!important;color:#12334a!important;font-size:38px!important;line-height:1.08!important}
    body.wpbbshop-theme .llg-v313-page-hero>span{display:block!important;margin-bottom:6px!important;color:#2b9748!important;font-size:10px!important;font-weight:900!important;letter-spacing:.12em!important}
    body.wpbbshop-theme .llg-v313-page-hero p{margin:8px 0 0!important;color:#687985!important}
    body.wpbbshop-theme .llg-v313-empty{padding:36px!important;border:1px solid #dfe7ec!important;border-radius:14px!important;background:#fff!important;text-align:center!important}
    body.wpbbshop-theme .llg-v313-btn{display:inline-flex!important;min-height:44px!important;align-items:center!important;padding:0 18px!important;border-radius:8px!important;background:#2b9748!important;color:#fff!important;font-weight:800!important;margin-top:10px!important}
    body.wpbbshop-theme .llg-v313-compare{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:18px!important}

    /* Generic managed pages use the same content width and card rhythm. */
    body.wpbbshop-theme .wpbbshop-page-shell{width:min(1480px,calc(100% - 48px))!important;max-width:1480px!important;padding-top:28px!important;padding-bottom:56px!important}
    body.wpbbshop-theme .wpbbshop-page-heading{margin:0 0 18px!important;padding:24px 28px!important;border:1px solid #dfe7ec!important;border-radius:14px!important;background:linear-gradient(120deg,#fff,#eef7f0)!important}
    body.wpbbshop-theme .wpbbshop-page-heading h1{margin:0!important;font-size:38px!important;line-height:1.08!important;color:#12334a!important}
    body.wpbbshop-theme .wpbbshop-page-card{padding:28px!important;border:1px solid #dfe7ec!important;border-radius:14px!important;background:#fff!important;box-shadow:0 8px 28px rgba(18,51,74,.04)!important}
    body.wpbbshop-theme .llg-contact-managed{margin-top:20px!important;padding:22px!important;border:1px solid #e0e8ed!important;border-radius:12px!important;background:#fff!important}

    @media(max-width:1180px){
      body.wpbbshop-theme .wpbbshop-bootstrap-products,body.wpbbshop-theme .llg-v313-list-grid,body.wpbbshop-theme .llg-v313-compare{grid-template-columns:repeat(3,minmax(0,1fr))!important}
      body.single-product.wpbbshop-theme .llg-single-product-card{grid-template-columns:1fr!important}
      body.single-product.wpbbshop-theme .m38gallery-stage{height:460px!important}
    }
    @media(max-width:820px){
      body.wpbbshop-theme .wpbbshop-container,body.wpbbshop-theme .wpbbshop-container.container,body.wpbbshop-theme .llg-single-container,body.wpbbshop-theme .llg-account-shell>.wpbbshop-container,body.wpbbshop-theme .llg-woo-page,body.wpbbshop-theme .wpbbshop-page-shell{width:calc(100% - 20px)!important;max-width:none!important}
      body.wpbbshop-theme .llg-mobile-search{padding-right:46px!important;grid-template-columns:112px minmax(0,1fr)!important}
      body.wpbbshop-theme .llg-mobile-search>.llg-search-submit{width:46px!important;min-width:46px!important;max-width:46px!important;height:44px!important;min-height:44px!important;border-radius:0 8px 8px 0!important}
      body.wpbbshop-theme .wpbbshop-bootstrap-products,body.wpbbshop-theme .llg-v313-list-grid,body.wpbbshop-theme .llg-v313-compare{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px!important}
      body.single-product.wpbbshop-theme .m38gallery-stage{height:360px!important}
      body.woocommerce-account .llg-myaccount-layout{grid-template-columns:1fr!important}
      body.woocommerce-account .woocommerce-MyAccount-navigation ul{display:flex!important;gap:5px!important;overflow-x:auto!important;white-space:nowrap!important}
      body.woocommerce-account .woocommerce-MyAccount-navigation li{flex:0 0 auto!important}
      body.wpbbshop-theme .wpbbshop-search-hero h1,body.wpbbshop-theme .llg-v313-page-hero h1,body.wpbbshop-theme .wpbbshop-page-heading h1{font-size:30px!important}
    }
    @media(max-width:540px){
      body.wpbbshop-theme .wpbbshop-bootstrap-products,body.wpbbshop-theme .llg-v313-list-grid,body.wpbbshop-theme .llg-v313-compare{grid-template-columns:1fr!important}
      body.single-product.wpbbshop-theme .m38gallery-stage{height:300px!important}
      body.single-product.wpbbshop-theme .llg-single-summary-panel,body.single-product.wpbbshop-theme .llg-single-gallery-panel{padding:16px!important}
    }
    </style>
    <?php
}
add_action('wp_head','wpbbshop_v313_css',PHP_INT_MAX);
