<?php
/**
 * WP BB Home & Garden v4.0.9
 * Large-catalogue shop/category UX: active-language categories, AJAX filters and load more.
 */
defined('ABSPATH') || exit;

function wpbbshop_v409_lang() {
    if (function_exists('pll_current_language')) {
        $lang = wpbbshop_v433_current_language();
        if (is_string($lang) && $lang !== '') return sanitize_key($lang);
    }
    return substr((string) get_locale(), 0, 2) === 'lv' ? 'lv' : 'en';
}

function wpbbshop_v409_t($en, $lv) {
    return wpbbshop_v409_lang() === 'lv' ? $lv : $en;
}

function wpbbshop_v409_term_matches_language($term) {
    if (!$term instanceof WP_Term || !function_exists('pll_get_term_language')) return true;
    $term_lang = pll_get_term_language($term->term_id, 'slug');
    if (!$term_lang) return true;
    return $term_lang === wpbbshop_v409_lang();
}

function wpbbshop_v409_category_terms() {
    $parent = 0;
    if (function_exists('is_product_category') && is_product_category()) {
        $obj = get_queried_object();
        if ($obj instanceof WP_Term) {
            $children = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>(int)$obj->term_id,'orderby'=>'name','order'=>'ASC'));
            if (!is_wp_error($children) && $children) {
                return array_values(array_filter($children, 'wpbbshop_v409_term_matches_language'));
            }
        }
    }
    $terms = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>$parent,'orderby'=>'name','order'=>'ASC','number'=>40));
    if (is_wp_error($terms)) return array();
    $terms = array_values(array_filter($terms, 'wpbbshop_v409_term_matches_language'));
    if (function_exists('wpbbshop_category_display_order')) $terms = wpbbshop_category_display_order($terms);
    return $terms;
}

function wpbbshop_v409_archive_title() {
    if (function_exists('is_product_category') && is_product_category()) return single_term_title('', false);
    if (function_exists('is_product_tag') && is_product_tag()) return single_term_title('', false);
    if (is_search()) return sprintf(wpbbshop_v409_t('Search: %s','Meklēšana: %s'), get_search_query());
    return wpbbshop_v409_t('Products','Preces');
}

function wpbbshop_v409_archive_description() {
    if (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
        $desc = term_description();
        if ($desc) return '<div class="wpbbshop-archive-desc">' . wp_kses_post($desc) . '</div>';
    }
    $text = wpbbshop_v409_t(
        'Home, garden, DIY and workshop products. Search by product name, SKU or ID and narrow the catalogue with fast filters.',
        'Mājas, dārza, būvniecības un darbnīcas preces. Meklē pēc nosaukuma, SKU vai ID un sašaurini katalogu ar ātriem filtriem.'
    );
    return '<div class="wpbbshop-archive-desc">' . esc_html($text) . '</div>';
}

function wpbbshop_v409_current_context() {
    $ctx = array('taxonomy'=>'','term'=>'');
    if (function_exists('is_product_category') && is_product_category()) {
        $obj = get_queried_object();
        if ($obj instanceof WP_Term) { $ctx['taxonomy']='product_cat'; $ctx['term']=$obj->slug; }
    } elseif (function_exists('is_product_tag') && is_product_tag()) {
        $obj = get_queried_object();
        if ($obj instanceof WP_Term) { $ctx['taxonomy']='product_tag'; $ctx['term']=$obj->slug; }
    }
    return $ctx;
}

function wpbbshop_v409_category_browser() {
    $terms = wpbbshop_v409_category_terms();
    if (!$terms) return '';
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    ob_start(); ?>
    <section class="wpbbshop-v409-category-browser" aria-label="<?php echo esc_attr(wpbbshop_v409_t('Product categories','Preču kategorijas')); ?>">
      <div class="wpbbshop-v409-category-head">
        <div><span><?php echo esc_html(wpbbshop_v409_t('Browse first','Sāc ar kategoriju')); ?></span><h2><?php echo esc_html(wpbbshop_v409_t('Shop by category','Iepērcies pēc kategorijas')); ?></h2></div>
        <a href="<?php echo esc_url($shop); ?>"><?php echo esc_html(wpbbshop_v409_t('All products','Visas preces')); ?> →</a>
      </div>
      <div class="wpbbshop-v409-category-grid">
        <?php foreach ($terms as $term) : $url=get_term_link($term); if (is_wp_error($url)) continue; ?>
          <a class="wpbbshop-v409-category-card<?php echo is_product_category($term->slug) ? ' is-active' : ''; ?>" href="<?php echo esc_url($url); ?>">
            <span class="wpbbshop-v409-cat-icon" aria-hidden="true">↗</span>
            <strong><?php echo esc_html($term->name); ?></strong>
            <small><?php echo esc_html(number_format_i18n((int)$term->count)); ?> <?php echo esc_html(wpbbshop_v409_t('products','preces')); ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php return ob_get_clean();
}

function wpbbshop_v409_filter_panel() {
    if (!class_exists('WooCommerce')) return '';
    $terms = wpbbshop_v409_category_terms();
    $ctx = wpbbshop_v409_current_context();
    $selected_cat = isset($_GET['product_cat']) ? sanitize_title(wp_unslash($_GET['product_cat'])) : ($ctx['taxonomy']==='product_cat' ? $ctx['term'] : '');
    $search = isset($_GET['wpbbshop_filter_search']) ? sanitize_text_field(wp_unslash($_GET['wpbbshop_filter_search'])) : '';
    $min = isset($_GET['min_price']) ? wc_format_decimal(wp_unslash($_GET['min_price'])) : '';
    $max = isset($_GET['max_price']) ? wc_format_decimal(wp_unslash($_GET['max_price'])) : '';
    $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';
    ob_start(); ?>
    <div class="wpbbshop-v409-filter-shell">
      <button type="button" class="wpbbshop-v409-filter-toggle" aria-expanded="false" aria-controls="wpbbshop-v409-filter-form"><?php echo esc_html(wpbbshop_v409_t('Filters','Filtri')); ?></button>
      <form id="wpbbshop-v409-filter-form" class="wpbbshop-v409-filter-form" method="get" action="">
        <div class="wpbbshop-v409-field wpbbshop-v409-search-field">
          <label for="wpbbshop-v409-search"><?php echo esc_html(wpbbshop_v409_t('Search','Meklēt')); ?></label>
          <input id="wpbbshop-v409-search" type="search" name="wpbbshop_filter_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr(wpbbshop_v409_t('Product, SKU or ID…','Prece, SKU vai ID…')); ?>">
        </div>
        <div class="wpbbshop-v409-field wpbbshop-v409-category-field">
          <label for="wpbbshop-v409-category"><?php echo esc_html(wpbbshop_v409_t('Category','Kategorija')); ?></label>
          <select id="wpbbshop-v409-category" name="product_cat">
            <option value=""><?php echo esc_html(wpbbshop_v409_t('All categories','Visas kategorijas')); ?></option>
            <?php foreach ($terms as $term) : ?><option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_cat,$term->slug); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="wpbbshop-v409-field wpbbshop-v409-price-field">
          <label><?php echo esc_html(wpbbshop_v409_t('Price','Cena')); ?></label>
          <div><input type="number" min="0" step="0.01" name="min_price" value="<?php echo esc_attr($min); ?>" placeholder="Min"><span>–</span><input type="number" min="0" step="0.01" name="max_price" value="<?php echo esc_attr($max); ?>" placeholder="Max"></div>
        </div>
        <div class="wpbbshop-v409-field wpbbshop-v409-sort-field">
          <label for="wpbbshop-v409-orderby"><?php echo esc_html(wpbbshop_v409_t('Sort','Kārtot')); ?></label>
          <select id="wpbbshop-v409-orderby" name="orderby">
            <option value="" <?php selected($orderby,''); ?>><?php echo esc_html(wpbbshop_v409_t('Default','Noklusējums')); ?></option>
            <option value="date" <?php selected($orderby,'date'); ?>><?php echo esc_html(wpbbshop_v409_t('Newest','Jaunākās')); ?></option>
            <option value="popularity" <?php selected($orderby,'popularity'); ?>><?php echo esc_html(wpbbshop_v409_t('Popular','Populārākās')); ?></option>
            <option value="price" <?php selected($orderby,'price'); ?>><?php echo esc_html(wpbbshop_v409_t('Price: low to high','Cena: augoši')); ?></option>
            <option value="price-desc" <?php selected($orderby,'price-desc'); ?>><?php echo esc_html(wpbbshop_v409_t('Price: high to low','Cena: dilstoši')); ?></option>
          </select>
        </div>
        <div class="wpbbshop-v409-checks">
          <span class="wpbbshop-v409-checks-title"><?php echo esc_html(wpbbshop_v409_t('Quick filters','Ātrie filtri')); ?></span>
          <label><input type="checkbox" name="instock" value="1" <?php checked(isset($_GET['instock'])); ?>> <span><?php echo esc_html(wpbbshop_v409_t('In stock','Noliktavā')); ?></span></label>
          <label><input type="checkbox" name="onsale" value="1" <?php checked(isset($_GET['onsale'])); ?>> <span><?php echo esc_html(wpbbshop_v409_t('On sale','Akcijā')); ?></span></label>
          <label><input type="checkbox" name="variable" value="1" <?php checked(isset($_GET['variable'])); ?>> <span><?php echo esc_html(wpbbshop_v409_t('Variations','Variācijas')); ?></span></label>
        </div>
        <input type="hidden" name="context_taxonomy" value="<?php echo esc_attr($ctx['taxonomy']); ?>">
        <input type="hidden" name="context_term" value="<?php echo esc_attr($ctx['term']); ?>">
        <div class="wpbbshop-v409-actions">
          <button type="submit" class="wpbbshop-v409-apply"><?php echo esc_html(wpbbshop_v409_t('Apply filters','Pielietot filtrus')); ?></button>
          <button type="button" class="wpbbshop-v409-reset"><?php echo esc_html(wpbbshop_v409_t('Reset','Notīrīt')); ?></button>
        </div>
      </form>
    </div>
    <?php return ob_get_clean();
}

function wpbbshop_v409_render_card($product) {
    if (!$product || !is_a($product,'WC_Product')) return '';
    $card = function_exists('wpbbshop_green_product_card') ? wpbbshop_green_product_card($product) : (function_exists('wpbbshop_product_card') ? wpbbshop_product_card($product) : '');
    return '<div class="wpbbshop-bs-product-col">' . $card . '</div>';
}

function wpbbshop_v409_build_query_args($request, $page = 1) {
    $per_page = absint(wpbbshop_get_theme_option('archive_per_page','20'));
    if ($per_page < 20) $per_page = 20;
    if ($per_page > 48) $per_page = 48;
    $page = max(1, absint($page));
    $meta_query = class_exists('WC') ? WC()->query->get_meta_query() : array();
    $tax_query = class_exists('WC') ? WC()->query->get_tax_query() : array();
    $cat = !empty($request['product_cat']) ? sanitize_title($request['product_cat']) : '';
    $context_tax = !empty($request['context_taxonomy']) ? sanitize_key($request['context_taxonomy']) : '';
    $context_term = !empty($request['context_term']) ? sanitize_title($request['context_term']) : '';
    if ($cat) {
        $tax_query[] = array('taxonomy'=>'product_cat','field'=>'slug','terms'=>$cat);
    } elseif ($context_tax && $context_term && in_array($context_tax,array('product_cat','product_tag'),true)) {
        $tax_query[] = array('taxonomy'=>$context_tax,'field'=>'slug','terms'=>$context_term);
    }
    if (!empty($request['variable'])) {
        $tax_query[] = array('taxonomy'=>'product_type','field'=>'slug','terms'=>array('variable'));
    }
    $args = array(
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>$per_page+1,
        'offset'=>($page-1)*$per_page,'no_found_rows'=>true,'ignore_sticky_posts'=>true,
        'meta_query'=>$meta_query,'tax_query'=>$tax_query,'lang'=>'',
        'wpbbshop_v409_catalog'=>1,
        'wpbbshop_v409_search'=>!empty($request['wpbbshop_filter_search']) ? sanitize_text_field($request['wpbbshop_filter_search']) : '',
        'wpbbshop_v409_min_price'=>isset($request['min_price']) && $request['min_price'] !== '' ? (float)$request['min_price'] : '',
        'wpbbshop_v409_max_price'=>isset($request['max_price']) && $request['max_price'] !== '' ? (float)$request['max_price'] : '',
        'wpbbshop_v409_instock'=>!empty($request['instock']) ? 1 : 0,
        'wpbbshop_v409_onsale'=>!empty($request['onsale']) ? 1 : 0,
        'wpbbshop_v409_orderby'=>!empty($request['orderby']) ? sanitize_key($request['orderby']) : '',
    );
    return array($args,$per_page);
}

add_filter('posts_clauses', function($clauses, $query) {
    if (!$query->get('wpbbshop_v409_catalog')) return $clauses;
    global $wpdb;
    $lookup = $wpdb->wc_product_meta_lookup;
    if (strpos($clauses['join'], ' wpbb409_lookup ') === false) {
        $clauses['join'] .= " LEFT JOIN {$lookup} wpbb409_lookup ON wpbb409_lookup.product_id = {$wpdb->posts}.ID ";
    }
    $search = (string)$query->get('wpbbshop_v409_search');
    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $clauses['where'] .= $wpdb->prepare(
            " AND ( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR wpbb409_lookup.sku LIKE %s" . (ctype_digit($search) ? " OR {$wpdb->posts}.ID=" . absint($search) : '') . ")",
            $like,$like,$like,$like
        );
    }
    $min = $query->get('wpbbshop_v409_min_price');
    $max = $query->get('wpbbshop_v409_max_price');
    if ($min !== '') $clauses['where'] .= $wpdb->prepare(' AND wpbb409_lookup.max_price >= %f', (float)$min);
    if ($max !== '') $clauses['where'] .= $wpdb->prepare(' AND wpbb409_lookup.min_price <= %f', (float)$max);
    if ($query->get('wpbbshop_v409_instock')) $clauses['where'] .= " AND wpbb409_lookup.stock_status='instock'";
    if ($query->get('wpbbshop_v409_onsale')) $clauses['where'] .= ' AND wpbb409_lookup.onsale=1';
    $orderby = (string)$query->get('wpbbshop_v409_orderby');
    if ($orderby === 'price') $clauses['orderby'] = 'wpbb409_lookup.min_price ASC, ' . $wpdb->posts . '.ID DESC';
    elseif ($orderby === 'price-desc') $clauses['orderby'] = 'wpbb409_lookup.max_price DESC, ' . $wpdb->posts . '.ID DESC';
    elseif ($orderby === 'popularity') $clauses['orderby'] = 'wpbb409_lookup.total_sales DESC, ' . $wpdb->posts . '.ID DESC';
    elseif ($orderby === 'date') $clauses['orderby'] = $wpdb->posts . '.post_date DESC';
    else $clauses['orderby'] = $wpdb->posts . '.menu_order ASC, ' . $wpdb->posts . '.post_date DESC';
    return $clauses;
}, 30, 2);

add_action('wp_ajax_wpbbshop_v409_catalog','wpbbshop_v409_ajax_catalog');
add_action('wp_ajax_nopriv_wpbbshop_v409_catalog','wpbbshop_v409_ajax_catalog');
function wpbbshop_v409_ajax_catalog() {
    check_ajax_referer('wpbbshop_v409_catalog','nonce');
    if (!class_exists('WooCommerce')) wp_send_json_error(array('message'=>'WooCommerce unavailable.'));
    $request = wp_unslash($_POST);
    $page = isset($request['page']) ? absint($request['page']) : 1;
    list($args,$per_page) = wpbbshop_v409_build_query_args($request,$page);
    $q = new WP_Query($args);
    $posts = $q->posts;
    $has_more = count($posts) > $per_page;
    if ($has_more) $posts = array_slice($posts,0,$per_page);
    $html='';
    foreach ($posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product) $html .= wpbbshop_v409_render_card($product);
    }
    $shown = (($page-1)*$per_page) + count($posts);
    $status = $has_more
        ? sprintf(wpbbshop_v409_t('%s products loaded — more available','Ielādētas %s preces — ir vēl'), number_format_i18n($shown))
        : sprintf(wpbbshop_v409_t('%s products loaded','Ielādētas %s preces'), number_format_i18n($shown));
    wp_send_json_success(array('html'=>$html,'page'=>$page,'has_more'=>$has_more,'status'=>$status));
}

function wpbbshop_v409_load_more_button() {
    global $wp_query;
    if (!$wp_query || empty($wp_query->max_num_pages) || (int)$wp_query->max_num_pages < 2) return '';
    return '<div class="wpbbshop-v409-load-more-wrap"><button type="button" class="wpbbshop-v409-load-more" data-page="1"><span>' . esc_html(wpbbshop_v409_t('Load more products','Ielādēt vairāk preču')) . '</span></button></div>';
}

add_action('wp_enqueue_scripts', function(){
    if (!(function_exists('is_shop') && (is_shop() || is_product_taxonomy()))) return;
    $base = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    wp_enqueue_style('wpbbshop-v409-catalog',$uri.'/assets/css/v409-catalog.css',array(),file_exists($base.'/assets/css/v409-catalog.css')?(string)filemtime($base.'/assets/css/v409-catalog.css'):'4.0.9');
    wp_enqueue_script('wpbbshop-v409-catalog',$uri.'/assets/js/v409-catalog.js',array('jquery'),file_exists($base.'/assets/js/v409-catalog.js')?(string)filemtime($base.'/assets/js/v409-catalog.js'):'4.0.9',true);
    wp_localize_script('wpbbshop-v409-catalog','WPBBShopCatalog409',array(
        'lang'=>wpbbshop_v433_current_language(),
        'ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('wpbbshop_v409_catalog'),
        'loadMore'=>wpbbshop_v409_t('Load more products','Ielādēt vairāk preču'),
        'loading'=>wpbbshop_v409_t('Loading…','Ielādē…'),
        'retry'=>wpbbshop_v409_t('Try again','Mēģināt vēlreiz'),
        'empty'=>wpbbshop_v409_t('No products matched these filters.','Neviena prece neatbilst filtriem.'),
    ));
}, 120);
