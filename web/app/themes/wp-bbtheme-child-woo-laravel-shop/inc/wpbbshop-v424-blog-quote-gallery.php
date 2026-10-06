<?php
/**
 * WP BB Home & Garden v4.0.24
 * Blog navigation/archive/admin gallery, editorial image gallery/lightbox,
 * quote mini-drawer and quote-page polish.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V424_VERSION')) {
    define('WPBBSHOP_V424_VERSION', '4.0.24');
}

function wpbbshop_v424_lang() {
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if (is_string($lang) && $lang !== '') {
            return strtolower($lang) === 'lv' ? 'lv' : 'en';
        }
    }
    return stripos((string) get_locale(), 'lv') === 0 ? 'lv' : 'en';
}

function wpbbshop_v424_t($en, $lv) {
    return wpbbshop_v424_lang() === 'lv' ? $lv : $en;
}

function wpbbshop_v424_blog_page($lang = '') {
    $lang = $lang ?: wpbbshop_v424_lang();
    $slug = $lang === 'lv' ? 'blogs' : 'blog';
    $page = get_page_by_path($slug, OBJECT, 'page');
    return $page instanceof WP_Post ? $page : null;
}

function wpbbshop_v424_blog_url($lang = '') {
    $lang = $lang ?: wpbbshop_v424_lang();
    $page = wpbbshop_v424_blog_page($lang);
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) { return $url; }
    }
    if (function_exists('pll_home_url')) {
        $home = pll_home_url($lang);
        if ($home) { return trailingslashit($home) . ($lang === 'lv' ? 'blogs/' : 'blog/'); }
    }
    return home_url('/' . ($lang === 'lv' ? 'lv/blogs/' : 'blog/'));
}

function wpbbshop_v424_upsert_blog_page($lang) {
    $lv = $lang === 'lv';
    $slug = $lv ? 'blogs' : 'blog';
    $title = $lv ? 'Blogs' : 'Blog';
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page instanceof WP_Post) {
        $id = (int) $page->ID;
        $update = array('ID'=>$id, 'post_status'=>'publish');
        if (trim((string)$page->post_content) === '') { $update['post_content'] = '[wpbbshop_blog_archive]'; }
        if ($page->post_title !== $title) { $update['post_title'] = $title; }
        wp_update_post($update);
    } else {
        $id = wp_insert_post(array(
            'post_type'=>'page','post_status'=>'publish','post_title'=>$title,'post_name'=>$slug,
            'post_content'=>'[wpbbshop_blog_archive]','comment_status'=>'closed','ping_status'=>'closed',
        ), true);
        if (is_wp_error($id) || !$id) { return 0; }
        $id = (int)$id;
    }
    if (function_exists('pll_set_post_language')) { pll_set_post_language($id, $lang); }
    return $id;
}

function wpbbshop_v424_ensure_blog_pages() {
    if ((string)get_option('wpbbshop_v424_blog_pages_version','') === WPBBSHOP_V424_VERSION) {
        $en = wpbbshop_v424_blog_page('en');
        $lv = wpbbshop_v424_blog_page('lv');
        if ($en && $lv) { return; }
    }
    $en = wpbbshop_v424_upsert_blog_page('en');
    $lv = wpbbshop_v424_upsert_blog_page('lv');
    if ($en && $lv && function_exists('pll_save_post_translations')) {
        pll_save_post_translations(array('en'=>$en,'lv'=>$lv));
    }
    update_option('wpbbshop_v424_blog_pages_version', WPBBSHOP_V424_VERSION, false);
}
add_action('init', 'wpbbshop_v424_ensure_blog_pages', 90);
add_action('admin_init', 'wpbbshop_v424_ensure_blog_pages', 120);
add_action('after_switch_theme', 'wpbbshop_v424_ensure_blog_pages', 120);


/** Refresh the already-created demo posts/products to the corrected imagery. */
function wpbbshop_v424_refresh_demo_images() {
    if ((string)get_option('wpbbshop_v424_demo_image_version','') === WPBBSHOP_V424_VERSION) { return; }
    $posts = get_posts(array(
        'post_type'=>'post','post_status'=>'any','posts_per_page'=>100,
        'meta_key'=>'_wpbbshop_v422_guide','meta_value'=>'1','suppress_filters'=>true,
    ));
    foreach ($posts as $post) {
        $urls = wpbbshop_v424_demo_gallery_urls($post->ID);
        if ($urls) { update_post_meta($post->ID,'_wpbbshop_v423_featured_image_url',esc_url_raw($urls[0])); }
    }
    if (function_exists('wpbbshop_v423_product_image_map')) {
        $map = wpbbshop_v423_product_image_map();
        $products = get_posts(array(
            'post_type'=>'product','post_status'=>'any','posts_per_page'=>200,
            'meta_key'=>'_wpbbshop_v422_demo_key','suppress_filters'=>true,
        ));
        foreach ($products as $post) {
            $key = (string)get_post_meta($post->ID,'_wpbbshop_v422_demo_key',true);
            if ($key && isset($map[$key])) {
                update_post_meta($post->ID,'_wpbbshop_v423_demo_image_url',esc_url_raw($map[$key]));
                update_post_meta($post->ID,'_wpbbshop_demo_image_url',esc_url_raw($map[$key]));
            }
        }
    }
    update_option('wpbbshop_v424_demo_image_version',WPBBSHOP_V424_VERSION,false);
}
add_action('admin_init','wpbbshop_v424_refresh_demo_images',135);
add_action('after_switch_theme','wpbbshop_v424_refresh_demo_images',135);

/** Blog link in the real classic primary menu. */
add_filter('wp_nav_menu_items', function($items, $args) {
    $location = isset($args->theme_location) ? (string)$args->theme_location : '';
    if ($location !== 'primary') { return $items; }
    if (preg_match('~href=["\'][^"\']*/(?:blog|blogs)/?["\']~i', $items)) { return $items; }
    $lang = wpbbshop_v424_lang();
    $label = $lang === 'lv' ? 'Blogs' : 'Blog';
    return $items . '<li class="menu-item menu-item-wpbb-blog"><a href="' . esc_url(wpbbshop_v424_blog_url($lang)) . '">' . esc_html($label) . '</a></li>';
}, 9999, 2);

/** Admin: present Posts as the store Blog instead of a generic Posts area. */
add_action('admin_menu', function() {
    global $menu, $submenu;
    if (isset($menu[5])) { $menu[5][0] = 'Blog'; }
    if (isset($submenu['edit.php'][5])) { $submenu['edit.php'][5][0] = 'All Blog Posts'; }
    if (isset($submenu['edit.php'][10])) { $submenu['edit.php'][10][0] = 'Add Blog Post'; }
}, 999);

function wpbbshop_v424_featured_image_url($post_id, $size = 'large') {
    $post_id = absint($post_id);
    if (!$post_id) { return ''; }
    if (has_post_thumbnail($post_id)) {
        $url = get_the_post_thumbnail_url($post_id, $size);
        if ($url) { return $url; }
    }
    $url = esc_url_raw((string)get_post_meta($post_id, '_wpbbshop_v423_featured_image_url', true));
    if ($url) { return $url; }
    return '';
}

function wpbbshop_v424_demo_gallery_library() {
    $base = 'https://commons.wikimedia.org/wiki/Special:FilePath/';
    return array(
        'radiator' => $base . 'Radiator.png?width=1600',
        'radiators' => $base . 'Caloriferi.png?width=1600',
        'underfloor' => $base . 'Public_domain_image_-_underfloor_heating_installation.JPG?width=1600',
        'underfloor_pipes' => $base . 'Underfloor_heating_pipes.jpg?width=1600',
        'tiller' => $base . 'Power_tiller_or_cultivator.jpg?width=1600',
        'tiller_alt' => $base . 'Norlett_5210_Garden_tiller.jpg?width=1600',
        'tractor' => $base . 'John_Deere_LX_279.jpg?width=1600',
        'mower' => $base . 'Ride-on_lawn_mower_Rhodes.jpg?width=1600',
        'heatpump' => $base . 'Air_Source_Heat_Pump_-_Vailliant_aroTherm_Plus_on_a_terraced_house.jpg?width=1600',
        'heatpump_snow' => $base . 'Ecodan_outdoor_unit_in_the_snow.jpg?width=1600',
        'heatpump_front' => $base . 'NIBE_S2125_air_source_heat_pump_(front)_-_Science_Museum,_London.jpg?width=1600',
    );
}

function wpbbshop_v424_demo_gallery_urls($post_id) {
    $key = (string)get_post_meta($post_id, '_wpbbshop_v422_guide_key', true);
    $img = wpbbshop_v424_demo_gallery_library();
    $map = array(
        'heating-plan'=>array($img['heatpump'],$img['radiator'],$img['underfloor']),
        'radiator-size'=>array($img['radiator'],$img['radiators'],$img['underfloor_pipes']),
        'cultivator-guide'=>array($img['tiller'],$img['tiller_alt'],$img['tractor'],$img['mower']),
        'variant-stock'=>array($img['radiators'],$img['tiller'],$img['heatpump_front']),
        'underfloor-heating'=>array($img['underfloor'],$img['underfloor_pipes'],$img['radiator']),
        'garden-maintenance'=>array($img['mower'],$img['tractor'],$img['tiller']),
        'heat-pump-guide'=>array($img['heatpump'],$img['heatpump_snow'],$img['heatpump_front']),
        'tractor-attachments'=>array($img['tractor'],$img['mower'],$img['tiller']),
    );
    return isset($map[$key]) ? array_values(array_unique(array_filter($map[$key]))) : array();
}

function wpbbshop_v424_post_gallery_items($post_id) {
    $post_id = absint($post_id);
    $items = array();
    $ids = get_post_meta($post_id, '_wpbbshop_v424_gallery_ids', true);
    if (is_string($ids)) { $ids = array_filter(array_map('absint', preg_split('/[\s,]+/', $ids))); }
    if (is_array($ids)) {
        foreach ($ids as $id) {
            $id = absint($id); if (!$id) { continue; }
            $full = wp_get_attachment_image_url($id, 'full');
            if (!$full) { continue; }
            $thumb = wp_get_attachment_image_url($id, 'medium_large') ?: $full;
            $caption = wp_get_attachment_caption($id) ?: get_the_title($id);
            $items[] = array('url'=>$full,'thumb'=>$thumb,'caption'=>$caption,'attachment_id'=>$id);
        }
    }
    if (!$items && (string)get_post_meta($post_id,'_wpbbshop_v422_guide',true)==='1') {
        foreach (wpbbshop_v424_demo_gallery_urls($post_id) as $url) {
            $items[] = array('url'=>$url,'thumb'=>$url,'caption'=>get_the_title($post_id),'attachment_id'=>0);
        }
    }
    if (!$items) {
        $featured = wpbbshop_v424_featured_image_url($post_id,'full');
        if ($featured) { $items[] = array('url'=>$featured,'thumb'=>$featured,'caption'=>get_the_title($post_id),'attachment_id'=>0); }
    }
    return $items;
}

function wpbbshop_v424_post_gallery_html($post_id) {
    $items = wpbbshop_v424_post_gallery_items($post_id);
    if (!$items) { return ''; }
    $label = wpbbshop_v424_t('Article gallery','Raksta galerija');
    ob_start(); ?>
    <section class="wpbb-v424-gallery" data-wpbb-gallery aria-label="<?php echo esc_attr($label); ?>">
      <div class="wpbb-v424-gallery-stage">
        <div class="wpbb-v424-gallery-track" data-gallery-track>
          <?php foreach ($items as $i=>$item) : ?>
            <button type="button" class="wpbb-v424-gallery-slide<?php echo $i===0?' is-active':''; ?>" data-gallery-slide="<?php echo esc_attr($i); ?>" data-gallery-full="<?php echo esc_url($item['url']); ?>" data-gallery-caption="<?php echo esc_attr($item['caption']); ?>">
              <img src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['caption']); ?>" <?php echo $i===0?'fetchpriority="high"':'loading="lazy"'; ?> decoding="async" referrerpolicy="no-referrer">
              <span class="wpbb-v424-gallery-zoom" aria-hidden="true">⌕</span>
            </button>
          <?php endforeach; ?>
        </div>
        <?php if (count($items)>1) : ?><button type="button" class="wpbb-v424-gallery-arrow is-prev" data-gallery-prev aria-label="<?php echo esc_attr(wpbbshop_v424_t('Previous image','Iepriekšējais attēls')); ?>">‹</button><button type="button" class="wpbb-v424-gallery-arrow is-next" data-gallery-next aria-label="<?php echo esc_attr(wpbbshop_v424_t('Next image','Nākamais attēls')); ?>">›</button><?php endif; ?>
        <span class="wpbb-v424-gallery-count"><b data-gallery-index>1</b> / <?php echo esc_html(count($items)); ?></span>
      </div>
      <?php if (count($items)>1) : ?><div class="wpbb-v424-gallery-thumbs" role="tablist"><?php foreach ($items as $i=>$item) : ?><button type="button" class="wpbb-v424-gallery-thumb<?php echo $i===0?' is-active':''; ?>" data-gallery-thumb="<?php echo esc_attr($i); ?>"><img src="<?php echo esc_url($item['thumb']); ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"></button><?php endforeach; ?></div><?php endif; ?>
    </section>
    <?php return (string)ob_get_clean();
}

/** Native WordPress media-library gallery selector for posts. */
add_action('add_meta_boxes_post', function() {
    add_meta_box('wpbbshop-v424-gallery', 'WP BB Article Gallery', function($post) {
        wp_nonce_field('wpbbshop_v424_gallery_save','wpbbshop_v424_gallery_nonce');
        $ids = get_post_meta($post->ID,'_wpbbshop_v424_gallery_ids',true);
        if (!is_array($ids)) { $ids = array_filter(array_map('absint', preg_split('/[\s,]+/', (string)$ids))); }
        echo '<div class="wpbb-v424-admin-gallery" data-wpbb-admin-gallery>';
        echo '<input type="hidden" name="wpbbshop_v424_gallery_ids" value="' . esc_attr(implode(',',array_map('absint',$ids))) . '" data-gallery-ids>';
        echo '<div class="wpbb-v424-admin-gallery-preview" data-gallery-preview>';
        foreach ($ids as $id) { echo '<span data-id="'.esc_attr($id).'">'.wp_get_attachment_image($id,'thumbnail').'<button type="button" aria-label="Remove">×</button></span>'; }
        echo '</div><p><button type="button" class="button button-primary" data-gallery-select>Select / edit gallery</button> <button type="button" class="button" data-gallery-clear>Clear gallery</button></p>';
        echo '<p class="description">Choose multiple Media Library images. They appear as a slider with thumbnails and a modal/lightbox on the article page. Demo guide posts keep their built-in image gallery until you choose your own images here.</p></div>';
    }, 'post', 'side', 'default');
});

add_action('save_post_post', function($post_id) {
    if (!isset($_POST['wpbbshop_v424_gallery_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpbbshop_v424_gallery_nonce'])),'wpbbshop_v424_gallery_save')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post',$post_id)) { return; }
    $raw = isset($_POST['wpbbshop_v424_gallery_ids']) ? sanitize_text_field(wp_unslash($_POST['wpbbshop_v424_gallery_ids'])) : '';
    $ids = array_values(array_unique(array_filter(array_map('absint', preg_split('/[\s,]+/', $raw)))));
    if ($ids) { update_post_meta($post_id,'_wpbbshop_v424_gallery_ids',$ids); }
    else { delete_post_meta($post_id,'_wpbbshop_v424_gallery_ids'); }
}, 20);

add_filter('manage_posts_columns', function($cols) {
    if (!isset($cols['wpbb_gallery'])) { $cols['wpbb_gallery'] = 'Gallery'; }
    return $cols;
});
add_action('manage_posts_custom_column', function($column,$post_id) {
    if ($column !== 'wpbb_gallery') { return; }
    $items = wpbbshop_v424_post_gallery_items($post_id);
    if (!$items) { echo '—'; return; }
    echo '<span title="'.esc_attr(count($items).' gallery images').'">'.esc_html(count($items)).' images</span>';
}, 10, 2);

/** Blog archive shortcode used by managed EN/LV Blog pages. */
add_shortcode('wpbbshop_blog_archive', function() {
    $lang = wpbbshop_v424_lang(); $lv = $lang === 'lv';
    $page = max(1, get_query_var('paged') ? absint(get_query_var('paged')) : (isset($_GET['blog_page']) ? absint($_GET['blog_page']) : 1));
    $args = array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>12,'paged'=>$page,'ignore_sticky_posts'=>true);
    if (function_exists('pll_current_language')) { $args['lang'] = $lang; }
    $q = new WP_Query($args);
    ob_start(); ?>
    <section class="wpbb-v424-blog-index">
      <header class="wpbb-v424-blog-index-hero"><span><?php echo esc_html($lv?'WP BB CEĻVEŽI':'WP BB GUIDES'); ?></span><h1><?php echo esc_html($lv?'Idejas, padomi un preču ceļveži':'Ideas, advice and product guides'); ?></h1><p><?php echo esc_html($lv?'Praktiski raksti par dārza tehniku, apkuri, instrumentiem un gudrāku preču izvēli.':'Practical articles about garden machinery, heating, tools and smarter product choices.'); ?></p></header>
      <?php if ($q->have_posts()) : ?><div class="wpbb-v424-blog-grid"><?php while($q->have_posts()) : $q->the_post(); $id=get_the_ID(); $img=wpbbshop_v424_featured_image_url($id); $mins=function_exists('wpbbshop_v423_read_minutes')?wpbbshop_v423_read_minutes($id):3; ?>
        <article class="wpbb-v424-blog-card"><a href="<?php the_permalink(); ?>"><span class="wpbb-v424-blog-card-image"><?php if($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php endif; ?></span><span class="wpbb-v424-blog-card-meta"><?php echo esc_html(get_the_date()); ?> · <?php echo esc_html(sprintf($lv?'%d min lasīšanai':'%d min read',$mins)); ?></span><h2><?php the_title(); ?></h2><p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()),22)); ?></p><b><?php echo esc_html($lv?'Lasīt ceļvedi →':'Read guide →'); ?></b></a></article>
      <?php endwhile; ?></div><?php else : ?><div class="wpbb-v424-blog-empty"><?php echo esc_html($lv?'Raksti drīzumā.':'Articles coming soon.'); ?></div><?php endif; ?>
    </section>
    <?php wp_reset_postdata(); return (string)ob_get_clean();
});

/** Quote drawer HTML uses the native Woo Support quote session. */
function wpbbshop_v424_quote_drawer_body() {
    if (!function_exists('wp_theme_woo_quote_items')) { return ''; }
    $items = wp_theme_woo_quote_items();
    $count = function_exists('wp_theme_woo_quote_count') ? wp_theme_woo_quote_count() : count($items);
    $url = function_exists('wp_theme_woo_quote_page_url') ? wp_theme_woo_quote_page_url() : home_url('/request-a-quote/');
    ob_start(); ?>
      <div class="wpbb-v424-quote-drawer-head"><div><span><?php echo esc_html(wpbbshop_v424_t('PRODUCT ENQUIRY','PREČU PIEPRASĪJUMS')); ?></span><h2><?php echo esc_html(sprintf(wpbbshop_v424_t('Your Quote (%d)','Tavs pieprasījums (%d)'),$count)); ?></h2></div><button type="button" data-quote-drawer-close aria-label="<?php echo esc_attr(wpbbshop_v424_t('Close','Aizvērt')); ?>">×</button></div>
      <div class="wpbb-v424-quote-drawer-content">
      <?php if (!$items) : ?>
        <div class="wpbb-v424-quote-empty"><strong><?php echo esc_html(wpbbshop_v424_t('Your quote list is empty','Pieprasījumu saraksts ir tukšs')); ?></strong><p><?php echo esc_html(wpbbshop_v424_t('Add products or selected variations and they will appear here.','Pievieno preces vai izvēlētos variantus, un tie parādīsies šeit.')); ?></p></div>
      <?php else : foreach ($items as $item) : $product=function_exists('wp_theme_woo_quote_item_product')?wp_theme_woo_quote_item_product($item):null; if(!$product)continue; $parent_id=absint($item['product_id']??$product->get_id()); ?>
        <article class="wpbb-v424-mini-quote-item"><a class="wpbb-v424-mini-quote-image" href="<?php echo esc_url(get_permalink($parent_id)); ?>"><?php echo $product->get_image('woocommerce_thumbnail'); ?></a><div><h3><a href="<?php echo esc_url(get_permalink($parent_id)); ?>"><?php echo esc_html($product->get_name()); ?></a></h3><?php if(function_exists('wp_theme_woo_quote_item_meta'))echo wp_kses_post(wp_theme_woo_quote_item_meta($item)); ?><span><?php echo esc_html(sprintf(wpbbshop_v424_t('Qty %d','Daudzums %d'),max(1,absint($item['quantity']??1)))); ?></span><?php if($product->get_price_html()): ?><strong><?php echo wp_kses_post($product->get_price_html()); ?></strong><?php endif; ?></div></article>
      <?php endforeach; endif; ?>
      </div>
      <div class="wpbb-v424-quote-drawer-foot"><a class="wpbb-v424-quote-primary" href="<?php echo esc_url($url); ?>"><?php echo esc_html(wpbbshop_v424_t('View quote & send request','Skatīt un nosūtīt pieprasījumu')); ?> →</a><button type="button" class="wpbb-v424-quote-secondary" data-quote-drawer-close><?php echo esc_html(wpbbshop_v424_t('Continue shopping','Turpināt iepirkties')); ?></button></div>
    <?php return (string)ob_get_clean();
}

add_action('wp_footer', function() {
    if (is_admin() || !function_exists('wp_theme_woo_quote_items')) { return; }
    echo '<div class="wpbb-v424-quote-overlay" data-quote-drawer-overlay hidden></div><aside class="wpbb-v424-quote-drawer" data-quote-drawer aria-hidden="true">'.wpbbshop_v424_quote_drawer_body().'</aside>';
    echo '<div class="wpbb-v424-lightbox" data-wpbb-lightbox hidden><button type="button" class="wpbb-v424-lightbox-close" data-lightbox-close aria-label="Close">×</button><button type="button" class="wpbb-v424-lightbox-arrow is-prev" data-lightbox-prev aria-label="Previous">‹</button><figure><img data-lightbox-image alt=""><figcaption data-lightbox-caption></figcaption></figure><button type="button" class="wpbb-v424-lightbox-arrow is-next" data-lightbox-next aria-label="Next">›</button></div>';
}, 80);

add_action('wp_ajax_wpbbshop_v424_quote_drawer', function(){
    check_ajax_referer('wpbbshop_v424_quote_drawer','nonce');
    wp_send_json_success(array('html'=>wpbbshop_v424_quote_drawer_body(),'count'=>function_exists('wp_theme_woo_quote_count')?wp_theme_woo_quote_count():0));
});
add_action('wp_ajax_nopriv_wpbbshop_v424_quote_drawer', function(){
    check_ajax_referer('wpbbshop_v424_quote_drawer','nonce');
    wp_send_json_success(array('html'=>wpbbshop_v424_quote_drawer_body(),'count'=>function_exists('wp_theme_woo_quote_count')?wp_theme_woo_quote_count():0));
});

add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook,array('post.php','post-new.php'),true) || get_current_screen()->post_type !== 'post') { return; }
    wp_enqueue_media();
    $js=get_stylesheet_directory().'/assets/js/v424-blog-admin.js';
    if(is_readable($js))wp_enqueue_script('wpbbshop-v424-blog-admin',get_stylesheet_directory_uri().'/assets/js/v424-blog-admin.js',array('jquery'),(string)filemtime($js),true);
    $css=get_stylesheet_directory().'/assets/css/v424-admin.css';
    if(is_readable($css))wp_enqueue_style('wpbbshop-v424-admin',get_stylesheet_directory_uri().'/assets/css/v424-admin.css',array(),(string)filemtime($css));
});

add_action('wp_enqueue_scripts', function() {
    $css=get_stylesheet_directory().'/assets/css/v424-blog-quote-gallery.css';
    $js=get_stylesheet_directory().'/assets/js/v424-blog-quote-gallery.js';
    if(is_readable($css))wp_enqueue_style('wpbbshop-v424-blog-quote-gallery',get_stylesheet_directory_uri().'/assets/css/v424-blog-quote-gallery.css',array('wpbbshop-v423-blog-showcase-polish'),(string)filemtime($css));
    if(is_readable($js)){
        wp_enqueue_script('wpbbshop-v424-blog-quote-gallery',get_stylesheet_directory_uri().'/assets/js/v424-blog-quote-gallery.js',array('jquery'),(string)filemtime($js),true);
        wp_localize_script('wpbbshop-v424-blog-quote-gallery','WPBBShopV424',array('ajaxUrl'=>admin_url('admin-ajax.php'),'quoteNonce'=>wp_create_nonce('wpbbshop_v424_quote_drawer'),'blogUrl'=>wpbbshop_v424_blog_url()));
    }
}, PHP_INT_MAX);
