<?php
/**
 * Home & Garden megastore layer.
 * Large-catalogue UX/performance helpers + 500-product demo generator.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_GARDEN_MEGASTORE_VERSION')) {
    define('WPBBSHOP_GARDEN_MEGASTORE_VERSION', '4.0.0');
}

function wpbbshop_megastore_departments_312() {
    return array(
        array('slug'=>'garden-machinery','en'=>'Garden machinery','lv'=>'Dārza tehnika'),
        array('slug'=>'garden-tools','en'=>'Garden tools','lv'=>'Dārza instrumenti'),
        array('slug'=>'watering-irrigation','en'=>'Watering & irrigation','lv'=>'Laistīšana'),
        array('slug'=>'greenhouses','en'=>'Greenhouses','lv'=>'Siltumnīcas'),
        array('slug'=>'garden-furniture','en'=>'Garden furniture','lv'=>'Dārza mēbeles'),
        array('slug'=>'plants-seeds','en'=>'Plants, seeds & soil','lv'=>'Augi, sēklas un augsne'),
        array('slug'=>'fertilisers-care','en'=>'Fertilisers & plant care','lv'=>'Mēslojums un augu kopšana'),
        array('slug'=>'fences-gates','en'=>'Fences & gates','lv'=>'Žogi un vārti'),
        array('slug'=>'outdoor-storage','en'=>'Outdoor storage','lv'=>'Dārza noliktavas'),
        array('slug'=>'bbq-outdoor-cooking','en'=>'BBQ & outdoor cooking','lv'=>'Grili un āra virtuve'),
        array('slug'=>'power-tools','en'=>'Power tools','lv'=>'Elektroinstrumenti'),
        array('slug'=>'hand-tools','en'=>'Hand tools','lv'=>'Rokas instrumenti'),
        array('slug'=>'building-materials','en'=>'Building materials','lv'=>'Būvmateriāli'),
        array('slug'=>'paint-finishing','en'=>'Paint & finishing','lv'=>'Krāsas un apdare'),
        array('slug'=>'plumbing-heating','en'=>'Plumbing & heating','lv'=>'Santehnika un apkure'),
        array('slug'=>'lighting-electrical','en'=>'Lighting & electrical','lv'=>'Apgaismojums un elektrība'),
        array('slug'=>'home-storage','en'=>'Home & storage','lv'=>'Māja un uzglabāšana'),
        array('slug'=>'cleaning','en'=>'Cleaning','lv'=>'Uzkopšana'),
        array('slug'=>'workwear-safety','en'=>'Workwear & safety','lv'=>'Darba apģērbs un drošība'),
        array('slug'=>'pet-outdoor','en'=>'Pet & outdoor living','lv'=>'Mājdzīvnieki un āra dzīve'),
    );
}

function wpbbshop_megastore_is_en_312() {
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if ($lang) { return $lang === 'en'; }
    }
    return substr((string) get_locale(), 0, 2) !== 'lv';
}

function wpbbshop_megastore_department_terms_312() {
    $cache_key = 'wpbbshop_megastore_departments_312_' . (wpbbshop_megastore_is_en_312() ? 'en' : 'lv');
    $cached = get_transient($cache_key);
    if (is_array($cached)) { return $cached; }
    $out = array();
    foreach (wpbbshop_megastore_departments_312() as $dept) {
        $term = get_term_by('slug', $dept['slug'], 'product_cat');
        if (!$term || is_wp_error($term)) { continue; }
        $link = get_term_link($term);
        if (is_wp_error($link)) { continue; }
        $out[] = array('term'=>$term, 'link'=>$link, 'label'=>wpbbshop_megastore_is_en_312() ? $dept['en'] : $dept['lv']);
    }
    set_transient($cache_key, $out, HOUR_IN_SECONDS);
    return $out;
}

add_action('created_product_cat', function(){ delete_transient('wpbbshop_megastore_departments_312_en'); delete_transient('wpbbshop_megastore_departments_312_lv'); });
add_action('edited_product_cat', function(){ delete_transient('wpbbshop_megastore_departments_312_en'); delete_transient('wpbbshop_megastore_departments_312_lv'); });
add_action('delete_product_cat', function(){ delete_transient('wpbbshop_megastore_departments_312_en'); delete_transient('wpbbshop_megastore_departments_312_lv'); });

function wpbbshop_megastore_products_section_312($type, $title, $limit = 8, $eyebrow = '') {
    if (function_exists('wpbbshop_green_products_section')) {
        return wpbbshop_green_products_section($type, $title, $limit, $eyebrow);
    }
    return '';
}

function wpbbshop_megastore_home_312() {
    $is_en = wpbbshop_megastore_is_en_312();
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $terms = wpbbshop_megastore_department_terms_312();
    ob_start(); ?>
    <main class="llg-home llg-megastore-home">
      <section class="wpbbshop-container llg-mega-hero">
        <div class="llg-mega-hero-copy">
          <span class="llg-mega-kicker"><?php echo esc_html($is_en ? 'HOME • GARDEN • BUILD • DIY' : 'MĀJA • DĀRZS • BŪVE • DIY'); ?></span>
          <h1><?php echo esc_html($is_en ? 'Everything for a bigger home & garden project' : 'Viss lielākiem mājas un dārza projektiem'); ?></h1>
          <p><?php echo esc_html($is_en ? 'A fast, department-first WooCommerce store designed for tens of thousands of products, trade customers and everyday DIY shopping.' : 'Ātrs WooCommerce veikals desmitiem tūkstošu preču, profesionāļiem un ikdienas mājas un dārza pirkumiem.'); ?></p>
          <div class="llg-mega-hero-actions">
            <a class="llg-primary-button" href="<?php echo esc_url($shop); ?>"><?php echo esc_html($is_en ? 'Shop all products' : 'Skatīt visas preces'); ?></a>
            <a class="llg-secondary-button" href="<?php echo esc_url(add_query_arg('orderby','popularity',$shop)); ?>"><?php echo esc_html($is_en ? 'Popular now' : 'Šobrīd populāri'); ?></a>
          </div>
          <div class="llg-mega-search-tip"><?php echo esc_html($is_en ? 'Tip: search by product name, brand, SKU or barcode.' : 'Padoms: meklē pēc nosaukuma, zīmola, SKU vai svītrkoda.'); ?></div>
        </div>
        <div class="llg-mega-hero-panel">
          <strong><?php echo esc_html($is_en ? 'Built for large catalogues' : 'Paredzēts lieliem katalogiem'); ?></strong>
          <span><?php echo esc_html($is_en ? '40k+ product-ready architecture' : 'Arhitektūra 40k+ precēm'); ?></span>
          <span><?php echo esc_html($is_en ? 'Fast category and SKU search' : 'Ātra kategoriju un SKU meklēšana'); ?></span>
          <span><?php echo esc_html($is_en ? 'Stock, delivery and store availability' : 'Noliktava, piegāde un pieejamība'); ?></span>
        </div>
      </section>

      <section class="wpbbshop-container llg-department-section">
        <div class="llg-section-head"><div><span class="llg-eyebrow"><?php echo esc_html($is_en ? 'Departments' : 'Nodaļas'); ?></span><h2><?php echo esc_html($is_en ? 'Shop by department' : 'Iepērcies pēc nodaļas'); ?></h2></div><a href="<?php echo esc_url($shop); ?>"><?php echo esc_html($is_en ? 'View full catalogue' : 'Pilns katalogs'); ?> →</a></div>
        <div class="llg-department-grid">
          <?php foreach ($terms as $item): ?>
            <a class="llg-department-card" href="<?php echo esc_url($item['link']); ?>">
              <span class="llg-department-dot" aria-hidden="true"></span>
              <strong><?php echo esc_html($item['label']); ?></strong>
              <small><?php echo esc_html(number_format_i18n((int)$item['term']->count)); ?> <?php echo esc_html($is_en ? 'products' : 'preces'); ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="wpbbshop-container llg-project-strip">
        <a href="<?php echo esc_url(add_query_arg('s','lawn',$shop)); ?>"><strong><?php echo esc_html($is_en ? 'Lawn & garden care' : 'Zāliens un dārza kopšana'); ?></strong><span><?php echo esc_html($is_en ? 'Mowers, trimmers, watering and tools' : 'Pļāvēji, trimmeri, laistīšana un instrumenti'); ?></span></a>
        <a href="<?php echo esc_url(add_query_arg('s','renovation',$shop)); ?>"><strong><?php echo esc_html($is_en ? 'Renovation project' : 'Remonta projekts'); ?></strong><span><?php echo esc_html($is_en ? 'Materials, tools, paint and finishing' : 'Materiāli, instrumenti, krāsas un apdare'); ?></span></a>
        <a href="<?php echo esc_url(add_query_arg('s','outdoor',$shop)); ?>"><strong><?php echo esc_html($is_en ? 'Outdoor living' : 'Dzīve ārā'); ?></strong><span><?php echo esc_html($is_en ? 'Furniture, BBQ, storage and lighting' : 'Mēbeles, grili, noliktavas un apgaismojums'); ?></span></a>
      </section>

      <div class="wpbbshop-container llg-home-products"><?php echo wpbbshop_megastore_products_section_312('featured', $is_en?'Popular products':'Populārākās preces', 12, $is_en?'Top picks':'Ieteikumi'); ?></div>
      <div class="wpbbshop-container llg-home-products"><?php echo wpbbshop_megastore_products_section_312('sale', $is_en?'Offers & savings':'Akcijas un ietaupījumi', 8, $is_en?'Offers':'Akcijas'); ?></div>
      <div class="wpbbshop-container llg-home-products"><?php echo wpbbshop_megastore_products_section_312('recent', $is_en?'New arrivals':'Jaunākās preces', 8, $is_en?'New':'Jaunumi'); ?></div>

      <section class="wpbbshop-container llg-service-grid">
        <div><strong><?php echo esc_html($is_en?'Fast collection':'Ātra saņemšana'); ?></strong><span><?php echo esc_html($is_en?'Clear stock and pickup information.':'Skaidra noliktavas un saņemšanas informācija.'); ?></span></div>
        <div><strong><?php echo esc_html($is_en?'Delivery choice':'Piegādes izvēle'); ?></strong><span><?php echo esc_html($is_en?'Courier, parcel and store pickup options.':'Kurjers, pakomāti un saņemšana veikalā.'); ?></span></div>
        <div><strong><?php echo esc_html($is_en?'Trade-ready':'Profesionāļiem'); ?></strong><span><?php echo esc_html($is_en?'Large quantities, SKU search and repeat ordering.':'Lieli apjomi, SKU meklēšana un atkārtoti pasūtījumi.'); ?></span></div>
        <div><strong><?php echo esc_html($is_en?'Advice & guides':'Padomi un ceļveži'); ?></strong><span><?php echo esc_html($is_en?'Help customers choose the right product, not only the cheapest one.':'Palīdz izvēlēties piemērotāko preci, ne tikai lētāko.'); ?></span></div>
      </section>
    </main>
    <?php return ob_get_clean();
}
add_shortcode('wpbbshop_home', 'wpbbshop_megastore_home_312');

add_action('wp_enqueue_scripts', function(){
    $css = '
    .llg-megastore-home{background:#f6f8f5;padding-bottom:42px}.llg-mega-hero{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(280px,.75fr);gap:22px;padding-top:26px;padding-bottom:26px}.llg-mega-hero-copy,.llg-mega-hero-panel{border-radius:18px}.llg-mega-hero-copy{padding:42px;background:linear-gradient(135deg,#103b2a,#1e6a3f);color:#fff;box-shadow:0 12px 34px rgba(9,50,31,.14)}.llg-mega-kicker{display:block;font-size:12px;font-weight:900;letter-spacing:.12em;color:#b8efc8;margin-bottom:10px}.llg-mega-hero h1{font-size:clamp(34px,4vw,62px);line-height:1.02;margin:0 0 16px;max-width:900px}.llg-mega-hero p{font-size:17px;line-height:1.6;max-width:760px;color:#e8f7ed}.llg-mega-hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}.llg-secondary-button{display:inline-flex;align-items:center;padding:13px 18px;border-radius:8px;border:1px solid rgba(255,255,255,.48);color:#fff!important;text-decoration:none;font-weight:800}.llg-mega-search-tip{margin-top:22px;font-size:13px;color:#cdebd6}.llg-mega-hero-panel{padding:30px;background:#fff;border:1px solid #e0e7df;display:flex;flex-direction:column;gap:16px;justify-content:center}.llg-mega-hero-panel strong{font-size:24px;color:#163926}.llg-mega-hero-panel span{padding-left:24px;position:relative;color:#405849}.llg-mega-hero-panel span:before{content:"✓";position:absolute;left:0;color:#2f9d50;font-weight:900}.llg-department-section{padding-top:16px}.llg-department-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}.llg-department-card{min-height:92px;padding:16px;border-radius:12px;border:1px solid #e0e7df;background:#fff;text-decoration:none!important;color:#183728!important;display:flex;flex-direction:column;gap:6px;transition:.18s ease}.llg-department-card:hover{transform:translateY(-2px);border-color:#65b47a;box-shadow:0 8px 22px rgba(28,85,48,.09)}.llg-department-card strong{font-size:15px;line-height:1.25}.llg-department-card small{color:#728277}.llg-department-dot{width:22px;height:5px;border-radius:999px;background:#39a75b}.llg-project-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding-top:28px}.llg-project-strip a{display:flex;flex-direction:column;gap:7px;padding:22px;border-radius:14px;background:#163b2a;color:#fff!important;text-decoration:none!important}.llg-project-strip strong{font-size:20px}.llg-project-strip span{color:#d7ebdf}.llg-service-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:30px}.llg-service-grid>div{padding:20px;border-radius:12px;background:#fff;border:1px solid #e1e8e0}.llg-service-grid strong{display:block;color:#183728;margin-bottom:5px}.llg-service-grid span{font-size:13px;color:#65766a}.llg-product-card img{content-visibility:auto}.llg-home-products{content-visibility:auto;contain-intrinsic-size:900px}.llg-department-section{content-visibility:auto;contain-intrinsic-size:500px}@media(max-width:1100px){.llg-department-grid{grid-template-columns:repeat(4,1fr)}}@media(max-width:820px){.llg-mega-hero{grid-template-columns:1fr}.llg-mega-hero-copy{padding:28px 22px}.llg-department-grid{grid-template-columns:repeat(2,1fr)}.llg-project-strip,.llg-service-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.llg-department-grid,.llg-project-strip,.llg-service-grid{grid-template-columns:1fr}.llg-mega-hero h1{font-size:34px}}
    ';
    wp_add_inline_style('wpbbshop-green-v2', $css);
}, 99);

/* Large-catalogue defaults. */
add_filter('loop_shop_per_page', function($count){ return 24; }, 50);
add_filter('woocommerce_product_query_max_main_query_cache', function(){ return 100; });
add_filter('wp_lazy_loading_enabled', '__return_true');

/* Keep emoji assets off the storefront. */
add_action('init', function(){
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
});

/* Faster live search: title + indexed Woo SKU lookup + selected barcode meta. */
function wpbbshop_megastore_ajax_search_312() {
    check_ajax_referer('wpbbshop_ajax', 'nonce');
    $term = isset($_GET['term']) ? trim(sanitize_text_field(wp_unslash($_GET['term']))) : '';
    $out = array();
    if ($term === '' || !class_exists('WooCommerce')) { wp_send_json_success($out); }
    global $wpdb;
    $like = '%' . $wpdb->esc_like($term) . '%';
    $lookup = $wpdb->wc_product_meta_lookup;
    $ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT p.ID
         FROM {$wpdb->posts} p
         LEFT JOIN {$lookup} l ON l.product_id = p.ID
         LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key IN ('_ean','ean','_gtin','gtin','_barcode','barcode','_global_unique_id')
         WHERE p.post_type = 'product' AND p.post_status = 'publish'
           AND (p.post_title LIKE %s OR l.sku LIKE %s OR pm.meta_value LIKE %s)
         ORDER BY CASE WHEN l.sku = %s THEN 0 WHEN p.post_title LIKE %s THEN 1 ELSE 2 END, p.post_title ASC
         LIMIT 10",
        $like, $like, $like, $term, $term . '%'
    ));
    foreach ((array)$ids as $id) {
        $product = wc_get_product((int)$id);
        if (!$product) { continue; }
        $image = function_exists('wpbbshop_green_product_image_html') ? wpbbshop_green_product_image_html($product) : $product->get_image('woocommerce_thumbnail');
        $out[] = array(
            'id'=>(int)$id,
            'name'=>$product->get_name(),
            'url'=>get_permalink($id),
            'price'=>wp_strip_all_tags($product->get_price_html()),
            'sku'=>$product->get_sku(),
            'image'=>$image,
        );
    }
    wp_send_json_success($out);
}
add_action('init', function(){
    remove_action('wp_ajax_wpbbshop_product_search', 'wpbbshop_ajax_product_search');
    remove_action('wp_ajax_nopriv_wpbbshop_product_search', 'wpbbshop_ajax_product_search');
    add_action('wp_ajax_wpbbshop_product_search', 'wpbbshop_megastore_ajax_search_312');
    add_action('wp_ajax_nopriv_wpbbshop_product_search', 'wpbbshop_megastore_ajax_search_312');
}, 100);

function wpbbshop_megastore_ensure_categories_312() {
    $ids = array();
    foreach (wpbbshop_megastore_departments_312() as $dept) {
        $term = term_exists($dept['slug'], 'product_cat');
        if (!$term) {
            $term = wp_insert_term($dept['en'], 'product_cat', array('slug'=>$dept['slug']));
        }
        if (!is_wp_error($term)) {
            $tid = (int)(is_array($term) ? $term['term_id'] : $term);
            $ids[$dept['slug']] = $tid;
            if (function_exists('pll_set_term_language')) { @pll_set_term_language($tid, 'en'); }
        }
    }
    return $ids;
}

function wpbbshop_megastore_seed_500_312() {
    if (!class_exists('WC_Product_Simple')) { return new WP_Error('woocommerce_missing', 'WooCommerce is required.'); }
    $cats = wpbbshop_megastore_ensure_categories_312();
    $departments = wpbbshop_megastore_departments_312();
    $adjectives = array('Pro','Eco','Compact','Premium','Smart','Heavy Duty','Classic','Max','Plus','Essential');
    $created=0; $updated=0;
    for ($i=1; $i<=500; $i++) {
        $dept = $departments[($i-1) % count($departments)];
        $sku = sprintf('HG-DEMO-%04d', $i);
        $id = wc_get_product_id_by_sku($sku);
        $product = $id ? wc_get_product($id) : new WC_Product_Simple();
        if (!$product) { continue; }
        $is_new = !$id;
        $name = $dept['en'] . ' ' . $adjectives[($i-1) % count($adjectives)] . ' ' . (100 + $i);
        $regular = number_format(9.90 + (($i * 17) % 780) + (($i % 7) * .49), 2, '.', '');
        $sale = ($i % 6 === 0) ? number_format(max(4.90, (float)$regular * .86), 2, '.', '') : '';
        $product->set_name($name);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        if ($is_new) { try { $product->set_sku($sku); } catch (Exception $e) { continue; } }
        $product->set_regular_price($regular);
        $product->set_sale_price($sale);
        $product->set_manage_stock(true);
        $product->set_stock_quantity(3 + (($i * 7) % 68));
        $product->set_stock_status('instock');
        $product->set_featured($i <= 24);
        $product->set_short_description('Demo Home & Garden catalogue product for layout, search and large-catalogue testing.');
        $product->set_description('Demo product generated by the Home & Garden child theme. Replace with real catalogue data before production.');
        if (!empty($cats[$dept['slug']])) { $product->set_category_ids(array($cats[$dept['slug']])); }
        $product->set_image_id(0);
        try { $saved = $product->save(); } catch (Exception $e) { continue; }
        if (!$saved) { continue; }
        update_post_meta($saved, '_wpbbshop_demo_product', '1');
        update_post_meta($saved, '_wpbbshop_demo_key', $sku);
        $demo_images = array(
            'drill-v46.webp','compressor.webp','welder-v43-v46.webp','generator.webp',
            'jack-v43-v46.webp','impact-wrench.webp','tool-set.webp','blower-v45-v46.webp',
            'brushcutter.webp','trimmer-head-v43-v46.webp','aluminum-head-v45-v46.webp',
            'trimmer-line-v45-v46.webp','chain-sharpener-v43-v46.webp','oil-pump-v43-v46.webp'
        );
        update_post_meta($saved, '_wpbbshop_demo_image', $demo_images[($i - 1) % count($demo_images)]);
        if (function_exists('wpbbshop_v400_remote_demo_image_for_index')) {
            $remote_image = wpbbshop_v400_remote_demo_image_for_index($i - 1);
            if ($remote_image) { update_post_meta($saved, '_wpbbshop_demo_image_url', $remote_image); }
        }
        update_post_meta($saved, '_wpbbshop_feed_exclude', 'yes');
        update_post_meta($saved, '_wpbbshop_feed_brand', 'GREEN HOME');
        update_post_meta($saved, '_wpbbshop_feed_model', 'HG-' . (100+$i));
        update_post_meta($saved, '_wpbbshop_feed_mpn', $sku);
        if (function_exists('pll_set_post_language')) { @pll_set_post_language($saved, 'en'); }
        $is_new ? $created++ : $updated++;
    }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    delete_transient('wpbbshop_megastore_departments_312_en');
    delete_transient('wpbbshop_megastore_departments_312_lv');
    return array('created'=>$created,'updated'=>$updated);
}

add_action('admin_menu', function(){
    add_theme_page('Home & Garden Demo', 'Home & Garden Demo', 'manage_options', 'wpbbshop-garden-demo-312', function(){
        if (!current_user_can('manage_options')) { return; }
        echo '<div class="wrap"><h1>Home & Garden Demo Catalogue</h1><p>Create/update 500 lightweight demo products across 20 departments. Demo products are excluded from comparison feeds.</p>';
        if (!empty($_GET['done'])) { echo '<div class="notice notice-success"><p>Demo catalogue refreshed.</p></div>'; }
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="wpbbshop_garden_seed_312">';
        wp_nonce_field('wpbbshop_garden_seed_312');
        submit_button('Create / refresh 500 demo products');
        echo '</form></div>';
    });
});
add_action('admin_post_wpbbshop_garden_seed_312', function(){
    if (!current_user_can('manage_options')) { wp_die('Permission denied'); }
    check_admin_referer('wpbbshop_garden_seed_312');
    wpbbshop_megastore_seed_500_312();
    wp_safe_redirect(add_query_arg(array('page'=>'wpbbshop-platform','tab'=>'demo','wpbb_notice'=>'Demo catalogue refreshed.'), admin_url('themes.php')));
    exit;
});
