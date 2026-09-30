<?php
/**
 * WP BB Home & Garden 3.0.5 final storefront repairs.
 * Small runtime layer for bilingual information pages, search and sale filtering.
 */
defined('ABSPATH') || exit;

function wpbbshop_v305_is_english() {
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if ($lang) { return $lang === 'en'; }
    }
    return substr((string) get_locale(), 0, 2) !== 'lv';
}

/** Sale links must always show discounted products only. */
function wpbbshop_v305_force_sale_query($query) {
    if (is_admin() || empty($_GET['onsale']) || !class_exists('WooCommerce')) { return; }
    $sale_ids = wc_get_product_ids_on_sale();
    $query->set('post__in', !empty($sale_ids) ? array_values(array_unique(array_map('absint', $sale_ids))) : array(0));
}
add_action('woocommerce_product_query', 'wpbbshop_v305_force_sale_query', 999);
add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query->is_main_query() || empty($_GET['onsale']) || !class_exists('WooCommerce')) { return; }
    if ($query->get('post_type') === 'product' || (function_exists('is_shop') && (is_shop() || is_product_taxonomy()))) {
        wpbbshop_v305_force_sale_query($query);
    }
}, 999);

function wpbbshop_v305_demo_image_url($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $remote = esc_url_raw((string) get_post_meta($product->get_id(), '_wpbbshop_demo_image_url', true));
    if ($remote && preg_match('#^https?://#i', $remote)) { return $remote; }
    if (function_exists('wpbbshop_v315_demo_asset_url')) {
        $forced_demo = wpbbshop_v315_demo_asset_url($product);
        if ($forced_demo) { return $forced_demo; }
    }
    $image_id = $product->get_image_id();
    if ($image_id) {
        $url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
        if ($url) { return $url; }
    }
    $map = array(
        'LL-DEMO-D20'=>'drill-v46.webp','LL-DEMO-C50'=>'compressor.webp','LL-DEMO-W200'=>'welder-v43-v46.webp',
        'LL-DEMO-G3500'=>'generator.webp','LL-DEMO-J3T'=>'jack-v43-v46.webp','LL-DEMO-A1500'=>'impact-wrench.webp',
        'LL-DEMO-S108'=>'tool-set.webp','LL-DEMO-B26'=>'blower-v45-v46.webp','LL-DEMO-BC52'=>'brushcutter.webp',
        'LL-DEMO-H10'=>'trimmer-head-v43-v46.webp','LL-DEMO-HALU'=>'aluminum-head-v45-v46.webp','LL-DEMO-L24'=>'trimmer-line-v45-v46.webp',
        'LL-DEMO-CS85'=>'chain-sharpener-v43-v46.webp','LL-DEMO-OP12'=>'oil-pump-v43-v46.webp',
    );
    $sku = (string) $product->get_sku();
    if (!empty($map[$sku])) {
        $path = get_stylesheet_directory() . '/assets/demo-products/' . $map[$sku];
        if (file_exists($path)) { return get_stylesheet_directory_uri() . '/assets/demo-products/' . $map[$sku]; }
    }
    $demo_image = sanitize_file_name((string) get_post_meta($product->get_id(), '_wpbbshop_demo_image', true));
    if ($demo_image !== '') {
        $demo_path = get_stylesheet_directory() . '/assets/demo-products/' . $demo_image;
        if (file_exists($demo_path)) { return get_stylesheet_directory_uri() . '/assets/demo-products/' . rawurlencode($demo_image); }
    }
    return function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('woocommerce_thumbnail') : '';
}

/** Ensure all standard WooCommerce cards/widgets can display demo remote images. */
function wpbbshop_v400_filter_woocommerce_product_image($html, $product, $size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true) {
    if (!$product || !is_a($product, 'WC_Product')) { return $html; }
    if (get_post_meta($product->get_id(), '_wpbbshop_demo_product', true) !== '1') { return $html; }
    $url = wpbbshop_v305_demo_image_url($product);
    if (!$url || strpos($url, 'woocommerce-placeholder') !== false) { return $html; }
    $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wpbbshop-demo-remote-image';
    if (is_array($attr) && !empty($attr['class'])) { $classes .= ' ' . sanitize_html_class($attr['class']); }
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr($product->get_name()) . '" class="' . esc_attr($classes) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer" width="720" height="720">';
}
add_filter('woocommerce_product_get_image', 'wpbbshop_v400_filter_woocommerce_product_image', 50, 5);

/** Richer AJAX search response. */
remove_action('wp_ajax_wpbbshop_product_search', 'wpbbshop_ajax_product_search');
remove_action('wp_ajax_nopriv_wpbbshop_product_search', 'wpbbshop_ajax_product_search');
add_action('wp_ajax_wpbbshop_product_search', 'wpbbshop_v305_ajax_product_search');
add_action('wp_ajax_nopriv_wpbbshop_product_search', 'wpbbshop_v305_ajax_product_search');
function wpbbshop_v305_ajax_product_search() {
    check_ajax_referer('wpbbshop_ajax', 'nonce');
    $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
    $items = array();
    if ($term !== '' && class_exists('WooCommerce')) {
        global $wpdb;
        $ids = array();
        if (ctype_digit($term) && get_post_type(absint($term)) === 'product') { $ids[] = absint($term); }
        $sku_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s LIMIT 12",
            '%' . $wpdb->esc_like($term) . '%'
        ));
        $ids = array_merge($ids, array_map('absint', (array) $sku_ids));
        $q = new WP_Query(array('post_type'=>'product','post_status'=>'publish','s'=>$term,'posts_per_page'=>12,'fields'=>'ids','no_found_rows'=>true));
        $ids = array_slice(array_values(array_unique(array_merge($ids, array_map('absint', (array) $q->posts)))), 0, 8);
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if (!$product || $product->get_status() !== 'publish' || strpos((string)$product->get_sku(), 'DEMO-BUSINESS-') === 0) { continue; }
            $terms = get_the_terms($id, 'product_cat');
            $category = (!is_wp_error($terms) && !empty($terms)) ? $terms[0]->name : '';
            $items[] = array(
                'id'=>$id,
                'title'=>$product->get_name(),
                'sku'=>$product->get_sku(),
                'price'=>html_entity_decode(wp_strip_all_tags(wc_price((float) $product->get_price())), ENT_QUOTES, get_bloginfo('charset')),
                'regularPrice'=>($product->is_on_sale() && $product->get_regular_price() !== '') ? html_entity_decode(wp_strip_all_tags(wc_price((float) $product->get_regular_price())), ENT_QUOTES, get_bloginfo('charset')) : '',
                'url'=>get_permalink($id),
                'image'=>wpbbshop_v305_demo_image_url($product),
                'category'=>$category,
                'stock'=>$product->is_in_stock() ? (wpbbshop_v305_is_english() ? 'In stock' : 'Ir noliktavā') : (wpbbshop_v305_is_english() ? 'Out of stock' : 'Nav noliktavā'),
                'sale'=>$product->is_on_sale(),
            );
        }
    }
    wp_send_json_success(array(
        'items'=>$items,
        'allUrl'=>add_query_arg(array('s'=>$term,'post_type'=>'product'), wpbbshop_v305_is_english() ? home_url('/') : home_url('/lv/')),
        'allLabel'=>wpbbshop_v305_is_english() ? 'View all results' : 'Skatīt visus rezultātus',
        'emptyLabel'=>wpbbshop_v305_is_english() ? 'No products found.' : 'Preces netika atrastas.',
    ));
}

function wpbbshop_v305_info_content($lang, $type) {
    $en = $lang === 'en';
    if ($type === 'about') {
        if ($en) {
            return '<section class="llg-info-pro"><div class="llg-info-intro"><span>WP BB HOME & GARDEN • RIGA</span><h2>Useful products for home, garden and work</h2><p>WP BB Home & Garden is an online store based in Riga, Latvia. We focus on practical tools, garden equipment, workshop products and spare parts that are easy to understand, order and receive anywhere in Latvia.</p></div><div class="llg-info-cards"><article><h3>Practical range</h3><p>Tools, garden machinery, workshop equipment and commonly needed accessories in one catalogue.</p></article><article><h3>Clear shopping</h3><p>Transparent prices, stock information, delivery options and product details before you place an order.</p></article><article><h3>Delivery across Latvia</h3><p>Parcel lockers and courier delivery are available, with local pickup in Riga by arrangement.</p></article><article><h3>Help before purchase</h3><p>If you are unsure about compatibility or delivery, contact us before ordering and we will help clarify the details.</p></article></div><div class="llg-info-band"><h3>From Riga to customers throughout Latvia</h3><p>Our goal is simple: make useful products easier to find and buy without unnecessary complexity.</p></div></section>';
        }
        return '<section class="llg-info-pro"><div class="llg-info-intro"><span>WP BB HOME & GARDEN • RIGA</span><h2>Praktiskas lietas mājai, dārzam un darbam</h2><p>WP BB Home & Garden ir Rīgas interneta veikals, kurā apkopojam praktiskus instrumentus, dārza tehniku, darbnīcas aprīkojumu un rezerves daļas. Mums svarīgi, lai preces būtu viegli atrast, saprast un pasūtīt ar ērtu piegādi visā Latvijā.</p></div><div class="llg-info-cards"><article><h3>Praktisks sortiments</h3><p>Instrumenti, dārza tehnika, servisa aprīkojums un ikdienā nepieciešami piederumi vienuviet.</p></article><article><h3>Skaidra iepirkšanās</h3><p>Pirms pirkuma redzama cena, pieejamība, piegādes iespējas un būtiskākā informācija par preci.</p></article><article><h3>Piegāde visā Latvijā</h3><p>Pakomāti un kurjers visā Latvijā, kā arī saņemšana Rīgā pēc iepriekšējas vienošanās.</p></article><article><h3>Palīdzība pirms pirkuma</h3><p>Ja nepieciešams precizēt savietojamību, piegādi vai komplektāciju, sazinies ar mums pirms pasūtījuma.</p></article></div><div class="llg-info-band"><h3>No Rīgas klientiem visā Latvijā</h3><p>Mūsu mērķis ir vienkāršs — palīdzēt ātrāk atrast un iegādāties noderīgas lietas bez liekas sarežģīšanas.</p></div></section>';
    }
    if ($en) {
        return '<section class="llg-info-pro"><div class="llg-info-intro"><span>DELIVERY & PAYMENT</span><h2>Flexible delivery throughout Latvia</h2><p>Choose the delivery method that suits the product and your location. Exact availability is shown or confirmed when the order is processed.</p></div><div class="llg-info-cards"><article><h3>Pickup in Riga</h3><p>Free pickup in Riga after the order is confirmed and a collection time is agreed.</p></article><article><h3>Parcel lockers</h3><p>Omniva, Unisend and Latvijas Pasts parcel locker delivery — standard price €3.90 for suitable parcels.</p></article><article><h3>Courier</h3><p>Unisend courier delivery up to 30 kg — €10.00. Delivery conditions depend on the product dimensions and destination.</p></article><article><h3>Oversized products</h3><p>For oversized or non-standard goods, delivery price is agreed separately before dispatch.</p></article></div><div class="llg-info-split"><div><h3>Payment methods</h3><ul><li>Cash on pickup in Riga</li><li>Bank transfer according to invoice</li><li>EveryPay / Swedbank online payment when the gateway is enabled</li></ul></div><div><h3>Before dispatch</h3><ul><li>We confirm product availability</li><li>We confirm the suitable delivery method</li><li>For non-standard delivery we agree the price before shipping</li></ul></div></div></section>';
    }
    return '<section class="llg-info-pro"><div class="llg-info-intro"><span>PIEGĀDE UN APMAKSA</span><h2>Ērta piegāde visā Latvijā</h2><p>Izvēlies precei un atrašanās vietai piemērotāko saņemšanas veidu. Precīza pieejamība un nosacījumi tiek parādīti vai precizēti pasūtījuma apstrādes laikā.</p></div><div class="llg-info-cards"><article><h3>Saņemšana Rīgā</h3><p>Bezmaksas saņemšana Rīgā pēc pasūtījuma apstiprināšanas un saņemšanas laika saskaņošanas.</p></article><article><h3>Pakomāti</h3><p>Omniva, Unisend un Latvijas Pasts pakomātu piegāde — standarta cena 3,90 € piemērotiem sūtījumiem.</p></article><article><h3>Kurjers</h3><p>Unisend kurjers sūtījumiem līdz 30 kg — 10,00 €. Piegādes iespēja atkarīga no preces izmēra un galamērķa.</p></article><article><h3>Lielgabarīta preces</h3><p>Nestandarta vai lielgabarīta precēm piegādes cenu saskaņojam atsevišķi pirms nosūtīšanas.</p></article></div><div class="llg-info-split"><div><h3>Apmaksas veidi</h3><ul><li>Skaidrā naudā, saņemot preci Rīgā</li><li>Bankas pārskaitījums pēc rēķina</li><li>EveryPay / Swedbank tiešsaistes maksājums, kad maksājumu vārteja ir aktivizēta</li></ul></div><div><h3>Pirms nosūtīšanas</h3><ul><li>Apstiprinām preces pieejamību</li><li>Precizējam piemērotāko piegādes veidu</li><li>Nestandarta piegādei cenu saskaņojam pirms nosūtīšanas</li></ul></div></div></section>';
}

function wpbbshop_v305_upsert_page($slug, $title, $content, $excerpt, $lang) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    $data = array('post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>$slug,'post_content'=>$content,'post_excerpt'=>$excerpt,'comment_status'=>'closed','ping_status'=>'closed');
    if ($page) { $data['ID'] = $page->ID; $id = wp_update_post($data, true); }
    else { $id = wp_insert_post($data, true); }
    if (is_wp_error($id) || !$id) { return 0; }
    if (function_exists('pll_set_post_language')) { pll_set_post_language($id, $lang); }
    return absint($id);
}

function wpbbshop_v305_ensure_bilingual_pages() {
    if (!current_user_can('edit_theme_options')) { return; }
    $version = '3.0.5';
    if (get_option('wpbbshop_v305_pages') === $version && get_page_by_path('par-mums') && get_page_by_path('piegade-un-apmaksa')) { return; }
    $about_lv = wpbbshop_v305_upsert_page('par-mums','Par mums',wpbbshop_v305_info_content('lv','about'),'Praktiskas lietas mājai, dārzam un darbam no Rīgas visai Latvijai.','lv');
    $about_en = wpbbshop_v305_upsert_page('about-us','About us',wpbbshop_v305_info_content('en','about'),'Useful products for home, garden and work, delivered throughout Latvia.','en');
    $delivery_lv = wpbbshop_v305_upsert_page('piegade-un-apmaksa','Piegāde un apmaksa',wpbbshop_v305_info_content('lv','delivery'),'Piegāde visā Latvijā, saņemšana Rīgā un ērti apmaksas veidi.','lv');
    $delivery_en = wpbbshop_v305_upsert_page('delivery-payment','Delivery & payment',wpbbshop_v305_info_content('en','delivery'),'Delivery throughout Latvia, pickup in Riga and convenient payment methods.','en');
    if (function_exists('pll_save_post_translations')) {
        if ($about_lv && $about_en) { pll_save_post_translations(array('lv'=>$about_lv,'en'=>$about_en)); }
        if ($delivery_lv && $delivery_en) { pll_save_post_translations(array('lv'=>$delivery_lv,'en'=>$delivery_en)); }
    }
    update_option('wpbbshop_v305_pages', $version, false);
}
add_action('admin_init', 'wpbbshop_v305_ensure_bilingual_pages', 45);
add_action('after_switch_theme', 'wpbbshop_v305_ensure_bilingual_pages', 45);

add_filter('body_class', function($classes) {
    if (is_page(array('par-mums','about-us','piegade-un-apmaksa','delivery-payment'))) { $classes[] = 'llg-pro-info-page'; }
    return $classes;
}, 90);

/** Small repair pass: correct page/menu URLs and clear sale/search transients without touching orders. */
function wpbbshop_v305_runtime_repair() {
    update_option('woocommerce_coming_soon', 'no', false);
    update_option('woocommerce_store_pages_only', 'no', false);
    delete_transient('wc_products_onsale');
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
}
add_action('after_switch_theme', 'wpbbshop_v305_runtime_repair', 80);
