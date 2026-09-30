<?php
/**
 * WP BB HOME & GARDEN price-comparison XML feeds for KurPirkt.lv and Salidzini.lv.
 *
 * Theme-native implementation so no third-party feed plugin is required.
 * PHP 7.4 compatible.
 */
defined('ABSPATH') || exit;

function wpbbshop_compare_feed_enabled($feed) {
    $key = $feed === 'salidzini' ? 'enable_salidzini_feed' : ($feed === 'ceno' ? 'enable_ceno_feed' : 'enable_kurpirkt_feed');
    return function_exists('wpbbshop_get_theme_option') && wpbbshop_get_theme_option($key, '1') === '1';
}

function wpbbshop_compare_feed_url($feed) {
    $feed = in_array($feed, array('kurpirkt','salidzini','ceno'), true) ? $feed : 'kurpirkt';
    return home_url('/' . $feed . '.xml');
}

function wpbbshop_compare_feed_cache_dir() {
    $uploads = wp_upload_dir();
    if (!empty($uploads['error'])) {
        return '';
    }
    return trailingslashit($uploads['basedir']) . 'wpbbshop-feeds';
}

function wpbbshop_compare_feed_cache_file($feed) {
    $dir = wpbbshop_compare_feed_cache_dir();
    if (!$dir) {
        return '';
    }
    return trailingslashit($dir) . (in_array($feed, array('kurpirkt','salidzini','ceno'), true) ? $feed : 'kurpirkt') . '.xml';
}

function wpbbshop_compare_feed_ensure_cache_dir() {
    $dir = wpbbshop_compare_feed_cache_dir();
    if (!$dir) {
        return false;
    }
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        return false;
    }
    if (is_dir($dir)) {
        $index = trailingslashit($dir) . 'index.php';
        if (!file_exists($index)) {
            @file_put_contents($index, "<?php\n// Silence is golden.\n");
        }
    }
    return is_dir($dir) && is_writable($dir);
}

function wpbbshop_compare_feed_invalidate($feed = '') {
    $feeds = in_array($feed, array('kurpirkt', 'salidzini', 'ceno'), true) ? array($feed) : array('kurpirkt', 'salidzini', 'ceno');
    foreach ($feeds as $name) {
        $file = wpbbshop_compare_feed_cache_file($name);
        if ($file && file_exists($file)) {
            @unlink($file);
        }
    }
}

function wpbbshop_compare_feed_schedule_refresh() {
    if (!wp_next_scheduled('wpbbshop_compare_feed_daily_refresh')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wpbbshop_compare_feed_daily_refresh');
    }
}
add_action('init', 'wpbbshop_compare_feed_schedule_refresh', 20);

function wpbbshop_compare_feed_unschedule_refresh() {
    $timestamp = wp_next_scheduled('wpbbshop_compare_feed_daily_refresh');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wpbbshop_compare_feed_daily_refresh');
    }
}
add_action('switch_theme', 'wpbbshop_compare_feed_unschedule_refresh');

function wpbbshop_compare_feed_register_rewrites() {
    add_rewrite_tag('%wpbbshop_compare_feed%', '([^&]+)');
    add_rewrite_rule('^(kurpirkt|salidzini|ceno)\.xml$', 'index.php?wpbbshop_compare_feed=$matches[1]', 'top');
}
add_action('init', 'wpbbshop_compare_feed_register_rewrites', 9);

function wpbbshop_compare_feed_flush_rewrites_if_needed() {
    $version = '1.1.0';
    if (get_option('wpbbshop_compare_feed_rewrite_version') === $version) {
        return;
    }
    wpbbshop_compare_feed_register_rewrites();
    flush_rewrite_rules(false);
    update_option('wpbbshop_compare_feed_rewrite_version', $version, false);
}
add_action('admin_init', 'wpbbshop_compare_feed_flush_rewrites_if_needed', 75);
add_action('after_switch_theme', 'wpbbshop_compare_feed_flush_rewrites_if_needed', 75);

function wpbbshop_compare_feed_xml_escape($value) {
    $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    $value = wp_check_invalid_utf8($value, true);
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function wpbbshop_compare_feed_text($value, $max_length = 0) {
    $value = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $value)));
    if ($max_length > 0) {
        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, $max_length, 'UTF-8');
        } else {
            $value = substr($value, 0, $max_length);
        }
    }
    return $value;
}

function wpbbshop_compare_feed_meta_first($product_id, $keys) {
    foreach ((array) $keys as $key) {
        $value = get_post_meta($product_id, $key, true);
        if (is_scalar($value) && trim((string) $value) !== '') {
            return wpbbshop_compare_feed_text($value, 200);
        }
    }
    return '';
}

function wpbbshop_compare_feed_attribute_first($product, $keys) {
    if (!$product || !is_a($product, 'WC_Product')) {
        return '';
    }
    foreach ((array) $keys as $key) {
        $candidates = array($key, sanitize_title($key));
        if (strpos($key, 'pa_') !== 0) {
            $candidates[] = 'pa_' . sanitize_title($key);
        }
        foreach (array_unique($candidates) as $candidate) {
            $value = $product->get_attribute($candidate);
            if (is_string($value) && trim($value) !== '') {
                $parts = array_map('trim', preg_split('/\s*[,|]\s*/', $value));
                return wpbbshop_compare_feed_text(reset($parts), 200);
            }
        }
    }
    return '';
}

function wpbbshop_compare_feed_taxonomy_first($product_id, $taxonomies) {
    foreach ((array) $taxonomies as $taxonomy) {
        if (!taxonomy_exists($taxonomy)) {
            continue;
        }
        $terms = wp_get_post_terms($product_id, $taxonomy, array('fields' => 'names'));
        if (!is_wp_error($terms) && !empty($terms)) {
            return wpbbshop_compare_feed_text(reset($terms), 200);
        }
    }
    return '';
}

function wpbbshop_compare_feed_brand($product) {
    $product_id = $product->get_id();
    $override = wpbbshop_compare_feed_meta_first($product_id, array('_wpbbshop_feed_brand'));
    if ($override) {
        return $override;
    }
    $brand = wpbbshop_compare_feed_taxonomy_first($product_id, array('product_brand', 'pwb-brand', 'yith_product_brand'));
    if ($brand) {
        return $brand;
    }
    $brand = wpbbshop_compare_feed_attribute_first($product, array('brand', 'zīmols', 'zimols', 'ražotājs', 'razotajs', 'manufacturer'));
    if ($brand) {
        return $brand;
    }
    return wpbbshop_compare_feed_meta_first($product_id, array('_brand', 'brand', '_manufacturer', 'manufacturer', '_product_brand'));
}

function wpbbshop_compare_feed_global_id($product) {
    $product_id = $product->get_id();
    $override = wpbbshop_compare_feed_meta_first($product_id, array('_wpbbshop_feed_ean'));
    if ($override) {
        return preg_replace('/[^0-9A-Za-z-]/', '', $override);
    }
    if (method_exists($product, 'get_global_unique_id')) {
        $global_id = $product->get_global_unique_id();
        if ($global_id) {
            return preg_replace('/[^0-9A-Za-z-]/', '', (string) $global_id);
        }
    }
    $value = wpbbshop_compare_feed_meta_first($product_id, array('_global_unique_id', '_ean', 'ean', '_gtin', 'gtin', '_barcode', 'barcode', '_upc', 'upc'));
    return preg_replace('/[^0-9A-Za-z-]/', '', $value);
}

function wpbbshop_compare_feed_model($product) {
    $value = wpbbshop_compare_feed_meta_first($product->get_id(), array('_wpbbshop_feed_model', '_model', 'model', '_product_model'));
    return $value ? $value : wpbbshop_compare_feed_attribute_first($product, array('model', 'modelis'));
}

function wpbbshop_compare_feed_mpn($product) {
    $value = wpbbshop_compare_feed_meta_first($product->get_id(), array('_wpbbshop_feed_mpn', '_mpn', 'mpn', '_manufacturer_part_number', 'manufacturer_part_number'));
    if (!$value) {
        $value = $product->get_sku();
    }
    return wpbbshop_compare_feed_text($value, 100);
}

function wpbbshop_compare_feed_color($product) {
    $value = wpbbshop_compare_feed_attribute_first($product, array('color', 'colour', 'krāsa', 'krasa'));
    if (!$value) {
        $value = wpbbshop_compare_feed_meta_first($product->get_id(), array('_color', 'color', '_colour', 'colour'));
    }
    return wpbbshop_compare_feed_text($value, 100);
}

function wpbbshop_compare_feed_is_used($product) {
    // Comparison services require demo/display items to be marked as used/demo when such products are intentionally included.
    if (get_post_meta($product->get_id(), '_wpbbshop_demo_product', true) === '1') {
        return true;
    }
    $override = get_post_meta($product->get_id(), '_wpbbshop_feed_used', true);
    if ($override === 'yes') {
        return true;
    }
    $condition = strtolower(wpbbshop_compare_feed_meta_first($product->get_id(), array('_condition', 'condition', '_product_condition')));
    if (!$condition) {
        $condition = strtolower(wpbbshop_compare_feed_attribute_first($product, array('condition', 'stāvoklis', 'stavoklis')));
    }
    foreach (array('used', 'lietot', 'refurb', 'atjaun', 'demo', 'display', 'bojāt', 'bojat') as $needle) {
        if ($condition && strpos($condition, $needle) !== false) {
            return true;
        }
    }
    return false;
}

function wpbbshop_compare_feed_product_for_price($product) {
    if (!$product || !is_a($product, 'WC_Product')) {
        return false;
    }
    if (!$product->is_type('variable')) {
        return $product;
    }
    $best = false;
    $best_price = null;
    foreach ((array) $product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation || !$variation->exists() || !$variation->is_purchasable() || !$variation->is_in_stock() || $variation->get_price() === '') {
            continue;
        }
        $price = (float) wc_get_price_including_tax($variation, array('price' => (float) $variation->get_price()));
        if ($best === false || $price < $best_price) {
            $best = $variation;
            $best_price = $price;
        }
    }
    return $best ? $best : $product;
}

function wpbbshop_compare_feed_price($product) {
    $priced_product = wpbbshop_compare_feed_product_for_price($product);
    if (!$priced_product || $priced_product->get_price() === '') {
        return '';
    }
    $price = wc_get_price_including_tax($priced_product, array('price' => (float) $priced_product->get_price()));
    return $price > 0 ? number_format((float) $price, 2, '.', '') : '';
}

function wpbbshop_compare_feed_category_data($product_id) {
    $terms = wp_get_post_terms($product_id, 'product_cat');
    if (is_wp_error($terms) || empty($terms)) {
        return array('name' => '', 'full' => '', 'link' => '');
    }
    usort($terms, function($a, $b) {
        $a_depth = count(get_ancestors($a->term_id, 'product_cat'));
        $b_depth = count(get_ancestors($b->term_id, 'product_cat'));
        if ($a_depth === $b_depth) {
            return strnatcasecmp($a->name, $b->name);
        }
        return $a_depth > $b_depth ? -1 : 1;
    });
    $term = reset($terms);
    $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat'));
    $names = array();
    foreach ($ancestors as $ancestor_id) {
        $ancestor = get_term($ancestor_id, 'product_cat');
        if ($ancestor && !is_wp_error($ancestor)) {
            $names[] = wpbbshop_compare_feed_text($ancestor->name, 200);
        }
    }
    $names[] = wpbbshop_compare_feed_text($term->name, 200);
    $link = get_term_link($term);
    return array(
        'name' => wpbbshop_compare_feed_text($term->name, 200),
        'full' => wpbbshop_compare_feed_text(implode(' > ', $names), 200),
        'link' => is_wp_error($link) ? '' : $link,
    );
}

function wpbbshop_compare_feed_image($product) {
    if (function_exists('wpbbshop_v315_demo_asset_url')) {
        $demo_url = wpbbshop_v315_demo_asset_url($product);
        if ($demo_url) { return (string) $demo_url; }
    }
    $image_id = $product->get_image_id();
    if (!$image_id && $product->is_type('variable')) {
        $priced_product = wpbbshop_compare_feed_product_for_price($product);
        if ($priced_product && is_a($priced_product, 'WC_Product')) {
            $image_id = $priced_product->get_image_id();
        }
    }
    if ($image_id) {
        $url = wp_get_attachment_image_url($image_id, 'full');
        if ($url) { return (string) $url; }
    }
    // Demo/catalogue products may intentionally use theme-bundled imagery instead of Media Library attachments.
    if (function_exists('wpbbshop_v305_demo_image_url')) {
        $url = wpbbshop_v305_demo_image_url($product);
        if ($url) { return (string) $url; }
    }
    return '';
}

function wpbbshop_compare_feed_stock_quantity($product) {
    if (!$product->managing_stock()) {
        return '';
    }
    $qty = $product->get_stock_quantity();
    if ($qty === null) {
        return '';
    }
    return (string) max(0, (int) $qty);
}

function wpbbshop_compare_feed_is_standard_delivery($product) {
    if (get_post_meta($product->get_id(), '_wpbbshop_shipping_quote', true) === 'yes') {
        return false;
    }
    if (get_post_meta($product->get_id(), '_wpbbshop_no_parcel', true) === 'yes') {
        return false;
    }
    return true;
}

function wpbbshop_compare_feed_product_data($product) {
    if (!$product || !is_a($product, 'WC_Product') || !$product->exists()) {
        return false;
    }
    if (get_post_meta($product->get_id(), '_wpbbshop_feed_exclude', true) === 'yes') {
        return false;
    }
    $sku = (string) $product->get_sku();
    if ($sku !== '' && strpos($sku, 'DEMO-BUSINESS-') === 0) {
        return false;
    }
    if (!$product->is_visible() || !$product->is_purchasable()) {
        return false;
    }
    if (wpbbshop_get_theme_option('comparison_feed_include_outofstock', '0') !== '1' && !$product->is_in_stock()) {
        return false;
    }
    $price = wpbbshop_compare_feed_price($product);
    if ($price === '') {
        return false;
    }
    $category = wpbbshop_compare_feed_category_data($product->get_id());
    $name = wpbbshop_compare_feed_text($product->get_name(), 200);
    if (!$name) {
        return false;
    }
    return array(
        'id' => $product->get_id(),
        'name' => $name,
        'link' => get_permalink($product->get_id()),
        'price' => $price,
        'image' => wpbbshop_compare_feed_image($product),
        'brand' => wpbbshop_compare_feed_brand($product),
        'model' => wpbbshop_compare_feed_model($product),
        'mpn' => wpbbshop_compare_feed_mpn($product),
        'ean' => wpbbshop_compare_feed_global_id($product),
        'color' => wpbbshop_compare_feed_color($product),
        'category' => $category['name'],
        'category_full' => $category['full'],
        'category_link' => $category['link'],
        'in_stock' => wpbbshop_compare_feed_stock_quantity($product),
        'used' => wpbbshop_compare_feed_is_used($product) ? '1' : '0',
        'standard_delivery' => wpbbshop_compare_feed_is_standard_delivery($product),
    );
}

function wpbbshop_compare_feed_product_ids() {
    if (!function_exists('wc_get_products')) {
        return array();
    }
    $ids = array();
    $page = 1;
    do {
        $results = wc_get_products(array(
            'status' => 'publish',
            'limit' => 250,
            'page' => $page,
            'paginate' => true,
            'return' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        ));
        if (!is_object($results) || empty($results->products)) {
            break;
        }
        foreach ($results->products as $id) {
            $ids[] = absint($id);
        }
        $page++;
    } while ($page <= (int) $results->max_num_pages);
    return array_values(array_unique(array_filter($ids)));
}

function wpbbshop_compare_feed_add_element(&$xml, $tag, $value, $required = false) {
    if (!$required && ($value === '' || $value === null)) {
        return;
    }
    $xml .= '    <' . $tag . '>' . wpbbshop_compare_feed_xml_escape($value) . '</' . $tag . ">\n";
}

function wpbbshop_compare_feed_build($feed) {
    $feed = in_array($feed, array('kurpirkt','salidzini','ceno'), true) ? $feed : 'kurpirkt';
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root>\n";
    $delivery_cost = trim((string) wpbbshop_get_theme_option('comparison_feed_delivery_cost', '3.90'));
    $delivery_days = trim((string) wpbbshop_get_theme_option('comparison_feed_delivery_days', '3'));
    $shop_days = trim((string) wpbbshop_get_theme_option('comparison_feed_shop_days', ''));

    foreach (wpbbshop_compare_feed_product_ids() as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product || $product->is_type('variation')) {
            continue;
        }
        $data = wpbbshop_compare_feed_product_data($product);
        if (!$data) {
            continue;
        }
        $xml .= "  <item>\n";
        wpbbshop_compare_feed_add_element($xml, 'name', $data['name'], true);
        wpbbshop_compare_feed_add_element($xml, 'link', $data['link'], true);
        wpbbshop_compare_feed_add_element($xml, 'price', $data['price'], true);

        if ($feed === 'kurpirkt') {
            wpbbshop_compare_feed_add_element($xml, 'image', $data['image'], true);
            wpbbshop_compare_feed_add_element($xml, 'manufacturer', $data['brand'], true);
            wpbbshop_compare_feed_add_element($xml, 'category', $data['category'], true);
            wpbbshop_compare_feed_add_element($xml, 'category_full', $data['category_full'], true);
            wpbbshop_compare_feed_add_element($xml, 'category_link', $data['category_link']);
            wpbbshop_compare_feed_add_element($xml, 'in_stock', $data['in_stock']);
            if ($data['standard_delivery'] && $delivery_cost !== '') {
                wpbbshop_compare_feed_add_element($xml, 'delivery_cost_riga', number_format((float) $delivery_cost, 2, '.', ''));
            }
            wpbbshop_compare_feed_add_element($xml, 'used', $data['used'], true);
        } elseif ($feed === 'salidzini') {
            wpbbshop_compare_feed_add_element($xml, 'category_full', $data['category_full']);
            wpbbshop_compare_feed_add_element($xml, 'category_link', $data['category_link']);
            wpbbshop_compare_feed_add_element($xml, 'image', $data['image'], true);
            wpbbshop_compare_feed_add_element($xml, 'in_stock', $data['in_stock']);
            wpbbshop_compare_feed_add_element($xml, 'brand', $data['brand']);
            wpbbshop_compare_feed_add_element($xml, 'model', $data['model']);
            wpbbshop_compare_feed_add_element($xml, 'color', $data['color']);
            wpbbshop_compare_feed_add_element($xml, 'mpn', $data['mpn']);
            wpbbshop_compare_feed_add_element($xml, 'ean', $data['ean']);
            if ($data['standard_delivery'] && $delivery_cost !== '') {
                wpbbshop_compare_feed_add_element($xml, 'delivery_latvija', number_format((float) $delivery_cost, 2, '.', ''));
            }
            if ($data['standard_delivery'] && $delivery_days !== '') {
                wpbbshop_compare_feed_add_element($xml, 'delivery_days_latvija', max(0, absint($delivery_days)));
            }
            if ($shop_days !== '') {
                wpbbshop_compare_feed_add_element($xml, 'delivery_days_shop', max(0, absint($shop_days)));
            }
            wpbbshop_compare_feed_add_element($xml, 'used', $data['used'], true);
            wpbbshop_compare_feed_add_element($xml, 'adult', 'no', true);
        } else {
            // Ceno.lv official sample fields. Prices are EUR incl. VAT.
            wpbbshop_compare_feed_add_element($xml, 'image', $data['image'], true);
            wpbbshop_compare_feed_add_element($xml, 'manufacturer', $data['brand']);
            wpbbshop_compare_feed_add_element($xml, 'model', $data['model']);
            wpbbshop_compare_feed_add_element($xml, 'category', $data['category']);
            wpbbshop_compare_feed_add_element($xml, 'category_full', $data['category_full']);
            wpbbshop_compare_feed_add_element($xml, 'category_link', $data['category_link']);
            wpbbshop_compare_feed_add_element($xml, 'in_stock', $data['in_stock']);
            wpbbshop_compare_feed_add_element($xml, 'ean', $data['ean']);
            wpbbshop_compare_feed_add_element($xml, 'used', $data['used'], true);
            if ($data['standard_delivery'] && $delivery_cost !== '') {
                wpbbshop_compare_feed_add_element($xml, 'delivery_cost_riga', number_format((float) $delivery_cost, 2, '.', ''));
                wpbbshop_compare_feed_add_element($xml, 'delivery_latvija', number_format((float) $delivery_cost, 2, '.', ''));
            }
            if ($data['standard_delivery'] && $delivery_days !== '') {
                wpbbshop_compare_feed_add_element($xml, 'delivery_days_riga', max(0, absint($delivery_days)));
                wpbbshop_compare_feed_add_element($xml, 'delivery_days_latvija', max(0, absint($delivery_days)));
            }
        }
        $xml .= "  </item>\n";
    }
    $xml .= "</root>\n";
    return $xml;
}

function wpbbshop_compare_feed_generate($feed) {
    if (function_exists('wpbbshop_megastore_feed_generate_310')) {
        return wpbbshop_megastore_feed_generate_310($feed);
    }
    $feed = in_array($feed, array('kurpirkt','salidzini','ceno'), true) ? $feed : 'kurpirkt';
    if (!wpbbshop_compare_feed_enabled($feed) || !class_exists('WooCommerce')) { return false; }
    return wpbbshop_compare_feed_build($feed);
}

function wpbbshop_compare_feed_refresh_all() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    foreach (array('kurpirkt', 'salidzini', 'ceno') as $feed) {
        if (wpbbshop_compare_feed_enabled($feed)) {
            wpbbshop_compare_feed_generate($feed);
        }
    }
}
add_action('wpbbshop_compare_feed_daily_refresh', 'wpbbshop_compare_feed_refresh_all');

function wpbbshop_compare_feed_maybe_refresh_after_product_change($post_id) {
    if (wp_is_post_revision($post_id) || get_post_type($post_id) !== 'product') {
        return;
    }
    wpbbshop_compare_feed_invalidate();
    if (!wp_next_scheduled('wpbbshop_compare_feed_delayed_refresh')) {
        wp_schedule_single_event(time() + 90, 'wpbbshop_compare_feed_delayed_refresh');
    }
}
add_action('save_post_product', 'wpbbshop_compare_feed_maybe_refresh_after_product_change', 30);
add_action('before_delete_post', function($post_id) {
    if (get_post_type($post_id) === 'product') {
        wpbbshop_compare_feed_invalidate();
    }
}, 30);
add_action('wpbbshop_compare_feed_delayed_refresh', 'wpbbshop_compare_feed_refresh_all');

function wpbbshop_compare_feed_queue_refresh() {
    wpbbshop_compare_feed_invalidate();
    if (!wp_next_scheduled('wpbbshop_compare_feed_delayed_refresh')) {
        wp_schedule_single_event(time() + 90, 'wpbbshop_compare_feed_delayed_refresh');
    }
}

add_action('woocommerce_update_product', 'wpbbshop_compare_feed_queue_refresh', 30);
add_action('woocommerce_update_product_variation', 'wpbbshop_compare_feed_queue_refresh', 30);
add_action('woocommerce_product_set_stock', 'wpbbshop_compare_feed_queue_refresh', 30);
add_action('woocommerce_variation_set_stock', 'wpbbshop_compare_feed_queue_refresh', 30);
add_action('update_option_wpbbshop_theme_options', function($old_value, $new_value) {
    if ($old_value !== $new_value) {
        wpbbshop_compare_feed_queue_refresh();
    }
}, 30, 2);

function wpbbshop_compare_feed_serve() {
    $feed = get_query_var('wpbbshop_compare_feed');
    if (!in_array($feed, array('kurpirkt', 'salidzini', 'ceno'), true)) {
        return;
    }
    if (!wpbbshop_compare_feed_enabled($feed)) {
        status_header(404);
        nocache_headers();
        exit;
    }
    if (!class_exists('WooCommerce')) {
        status_header(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'WooCommerce is required.';
        exit;
    }

    $file = wpbbshop_compare_feed_cache_file($feed);
    $max_age = 6 * HOUR_IN_SECONDS;
    if (!$file || !file_exists($file) || (time() - (int) @filemtime($file)) > $max_age) {
        $xml = wpbbshop_compare_feed_generate($feed);
    } else {
        $xml = @file_get_contents($file);
    }
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
add_action('template_redirect', 'wpbbshop_compare_feed_serve', 1);

function wpbbshop_compare_feed_product_fields() {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox(array(
        'id' => '_wpbbshop_feed_exclude',
        'label' => 'Izslēgt no cenu salīdzināšanas',
        'description' => 'Neiekļaut preci KurPirkt.lv, Salidzini.lv un Ceno.lv XML plūsmās.',
    ));
    woocommerce_wp_checkbox(array(
        'id' => '_wpbbshop_feed_used',
        'label' => 'Lietota / demo / atjaunota prece',
        'description' => 'Atzīmē used=1 cenu salīdzināšanas plūsmās.',
    ));
    woocommerce_wp_text_input(array(
        'id' => '_wpbbshop_feed_brand',
        'label' => 'Feed zīmols / ražotājs',
        'description' => 'Neobligāti. Pārraksta automātiski atrasto zīmolu tikai XML plūsmās.',
        'desc_tip' => true,
    ));
    woocommerce_wp_text_input(array(
        'id' => '_wpbbshop_feed_model',
        'label' => 'Feed modelis',
        'description' => 'Neobligāti. Produkta modelis Salidzini.lv/Ceno.lv plūsmai.',
        'desc_tip' => true,
    ));
    woocommerce_wp_text_input(array(
        'id' => '_wpbbshop_feed_mpn',
        'label' => 'Feed MPN',
        'description' => 'Neobligāti. Ražotāja detaļas numurs; ja tukšs, izmanto SKU.',
        'desc_tip' => true,
    ));
    woocommerce_wp_text_input(array(
        'id' => '_wpbbshop_feed_ean',
        'label' => 'Feed EAN / GTIN',
        'description' => 'Neobligāti. Pārraksta automātiski atrasto EAN/GTIN.',
        'desc_tip' => true,
    ));
    echo '</div>';
}
add_action('woocommerce_product_options_inventory_product_data', 'wpbbshop_compare_feed_product_fields', 90);

function wpbbshop_compare_feed_save_product_fields($post_id) {
    foreach (array('_wpbbshop_feed_exclude', '_wpbbshop_feed_used') as $key) {
        update_post_meta($post_id, $key, isset($_POST[$key]) ? 'yes' : 'no');
    }
    foreach (array('_wpbbshop_feed_brand', '_wpbbshop_feed_model', '_wpbbshop_feed_mpn', '_wpbbshop_feed_ean') as $key) {
        $value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
        if ($value === '') {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }
    wpbbshop_compare_feed_invalidate();
}
add_action('woocommerce_process_product_meta', 'wpbbshop_compare_feed_save_product_fields', 90);

function wpbbshop_compare_feed_admin_regenerate() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to do this.', 'wpbbshop'));
    }
    check_admin_referer('wpbbshop_regenerate_comparison_feeds');
    wpbbshop_compare_feed_invalidate();
    wpbbshop_compare_feed_refresh_all();
    wp_safe_redirect(add_query_arg(array('page' => 'wpbbshop-theme-settings', 'wpbbshop_feeds_regenerated' => '1'), admin_url('themes.php')));
    exit;
}
add_action('admin_post_wpbbshop_regenerate_comparison_feeds', 'wpbbshop_compare_feed_admin_regenerate');

function wpbbshop_compare_badges_html() {
    $html = '';
    if (wpbbshop_get_theme_option('show_kurpirkt_badge', '1') === '1') {
        $html .= '<a class="wpbbshop-compare-badge wpbbshop-compare-badge--kurpirkt" href="https://www.kurpirkt.lv" target="_blank" rel="noopener noreferrer" title="KurPirkt.lv"><img src="https://www.kurpirkt.lv/media/kurpirkt88.gif" width="88" height="31" alt="KurPirkt.lv"></a>';
    }
    if (wpbbshop_get_theme_option('show_salidzini_badge', '1') === '1') {
        $html .= '<a class="wpbbshop-compare-badge wpbbshop-compare-badge--salidzini" href="https://www.salidzini.lv/" target="_blank" rel="noopener noreferrer" title="Salidzini.lv"><img src="https://static.salidzini.lv/images/logo_button_green.webp" width="190" height="60" alt="Salidzini.lv"></a>';
    }
    if (wpbbshop_get_theme_option('show_ceno_badge', '1') === '1') {
        $html .= '<a class="wpbbshop-compare-badge wpbbshop-compare-badge--ceno" href="https://ceno.lv/" target="_blank" rel="noopener noreferrer" title="Ceno.lv">Ceno.lv</a>';
    }
    return $html;
}
