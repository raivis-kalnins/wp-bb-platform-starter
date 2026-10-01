<?php
/**
 * WP BB Home & Garden 4.0.8
 * Market-aware comparison and shopping feeds.
 *
 * Commerce market and content language are deliberately independent:
 * - LV market: KurPirkt.lv, Salidzini.lv, Ceno.lv
 * - GB market: Google Merchant, PriceRunner, PriceSpy, idealo, Kelkoo
 * - Auto mode follows the WooCommerce base country.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V408_VERSION')) {
    define('WPBBSHOP_V408_VERSION', '4.0.8');
}

function wpbbshop_v408_market_defaults() {
    return array(
        'market_mode' => 'auto',
        'enable_google' => '1',
        'enable_pricerunner' => '1',
        'enable_pricespy' => '1',
        'enable_idealo' => '1',
        'enable_kelkoo' => '1',
        'uk_delivery_cost' => '4.95',
        'uk_delivery_days' => '3',
        'idealo_delivery_provider' => 'dpd',
    );
}

function wpbbshop_v408_market_settings() {
    $saved = get_option('wpbbshop_market_feeds_v408', array());
    if (!is_array($saved)) { $saved = array(); }
    return wp_parse_args($saved, wpbbshop_v408_market_defaults());
}

function wpbbshop_v408_base_country() {
    if (function_exists('wc_get_base_location')) {
        $location = wc_get_base_location();
        if (is_array($location) && !empty($location['country'])) {
            return strtoupper(sanitize_key((string) $location['country']));
        }
    }
    $raw = (string) get_option('woocommerce_default_country', 'LV');
    $country = strtoupper((string) strtok($raw, ':'));
    return preg_match('/^[A-Z]{2}$/', $country) ? $country : 'LV';
}

function wpbbshop_v408_resolved_market() {
    $settings = wpbbshop_v408_market_settings();
    $mode = isset($settings['market_mode']) ? sanitize_key((string) $settings['market_mode']) : 'auto';
    if (in_array($mode, array('lv', 'gb'), true)) { return $mode; }
    return wpbbshop_v408_base_country() === 'GB' ? 'gb' : 'lv';
}

function wpbbshop_v408_market_label($market = '') {
    $market = $market ?: wpbbshop_v408_resolved_market();
    return $market === 'gb' ? 'United Kingdom' : 'Latvia';
}

function wpbbshop_v408_market_allows_feed($feed) {
    $feed = sanitize_key((string) $feed);
    $market = wpbbshop_v408_resolved_market();
    $lv = array('kurpirkt', 'salidzini', 'ceno');
    $gb = array('google', 'pricerunner', 'pricespy', 'idealo', 'kelkoo');
    if (in_array($feed, $lv, true)) { return $market === 'lv'; }
    if (in_array($feed, $gb, true)) { return $market === 'gb'; }
    return false;
}

function wpbbshop_v408_uk_service_enabled($service) {
    $service = sanitize_key((string) $service);
    if (!wpbbshop_v408_market_allows_feed($service)) { return false; }
    $settings = wpbbshop_v408_market_settings();
    return !empty($settings['enable_' . $service]) && $settings['enable_' . $service] === '1';
}

function wpbbshop_v408_services() {
    return array(
        'lv' => array(
            'kurpirkt' => array('name'=>'KurPirkt.lv','format'=>'XML','site'=>'https://www.kurpirkt.lv/'),
            'salidzini' => array('name'=>'Salidzini.lv','format'=>'XML','site'=>'https://www.salidzini.lv/'),
            'ceno' => array('name'=>'Ceno.lv','format'=>'XML','site'=>'https://ceno.lv/'),
        ),
        'gb' => array(
            'google' => array('name'=>'Google Merchant / Shopping','format'=>'Google XML','site'=>'https://merchants.google.com/'),
            'pricerunner' => array('name'=>'PriceRunner UK','format'=>'TSV','site'=>'https://www.pricerunner.com/'),
            'pricespy' => array('name'=>'PriceSpy UK','format'=>'Google/Prisjakt XML','site'=>'https://business.pricespy.co.uk/'),
            'idealo' => array('name'=>'idealo UK','format'=>'CSV','site'=>'https://solutions.idealo.com/'),
            'kelkoo' => array('name'=>'Kelkoo UK','format'=>'Google XML','site'=>'https://www.kelkoogroup.com/'),
        ),
    );
}

function wpbbshop_v408_active_service_names() {
    $market = wpbbshop_v408_resolved_market();
    $all = wpbbshop_v408_services();
    $names = array();
    foreach ($all[$market] as $key => $service) {
        $enabled = $market === 'lv'
            ? (function_exists('wpbbshop_compare_feed_enabled') && wpbbshop_compare_feed_enabled($key))
            : wpbbshop_v408_uk_service_enabled($key);
        if ($enabled) { $names[] = $service['name']; }
    }
    return $names;
}

function wpbbshop_v408_market_feed_extension($service) {
    if ($service === 'pricerunner') { return 'tsv'; }
    if ($service === 'idealo') { return 'csv'; }
    return 'xml';
}

function wpbbshop_v408_market_feed_slug($service) {
    $map = array(
        'google' => 'google-shopping',
        'pricerunner' => 'pricerunner',
        'pricespy' => 'pricespy',
        'idealo' => 'idealo',
        'kelkoo' => 'kelkoo',
    );
    return isset($map[$service]) ? $map[$service] : sanitize_key($service);
}

function wpbbshop_v408_market_feed_url($service, $pretty = true) {
    $service = sanitize_key((string) $service);
    if (!isset(wpbbshop_v408_services()['gb'][$service])) { return ''; }
    if (!$pretty) { return add_query_arg('wpbbshop_market_feed', $service, home_url('/')); }
    return home_url('/' . wpbbshop_v408_market_feed_slug($service) . '.' . wpbbshop_v408_market_feed_extension($service));
}

function wpbbshop_v408_register_market_rewrites() {
    add_rewrite_tag('%wpbbshop_market_feed%', '([^&]+)');
    add_rewrite_rule('^google-shopping\.xml$', 'index.php?wpbbshop_market_feed=google', 'top');
    add_rewrite_rule('^pricerunner\.tsv$', 'index.php?wpbbshop_market_feed=pricerunner', 'top');
    add_rewrite_rule('^pricespy\.xml$', 'index.php?wpbbshop_market_feed=pricespy', 'top');
    add_rewrite_rule('^idealo\.csv$', 'index.php?wpbbshop_market_feed=idealo', 'top');
    add_rewrite_rule('^kelkoo\.xml$', 'index.php?wpbbshop_market_feed=kelkoo', 'top');
}
add_action('init', 'wpbbshop_v408_register_market_rewrites', 9);

function wpbbshop_v408_maybe_flush_rewrites() {
    if (get_option('wpbbshop_v408_rewrite_version') === WPBBSHOP_V408_VERSION) { return; }
    wpbbshop_v408_register_market_rewrites();
    flush_rewrite_rules(false);
    update_option('wpbbshop_v408_rewrite_version', WPBBSHOP_V408_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v408_maybe_flush_rewrites', 80);
add_action('after_switch_theme', 'wpbbshop_v408_maybe_flush_rewrites', 80);

function wpbbshop_v408_cache_dir() {
    if (function_exists('wpbbshop_compare_feed_cache_dir')) { return wpbbshop_compare_feed_cache_dir(); }
    $uploads = wp_upload_dir();
    return empty($uploads['error']) ? trailingslashit($uploads['basedir']) . 'wpbbshop-feeds' : '';
}

function wpbbshop_v408_cache_file($service) {
    $dir = wpbbshop_v408_cache_dir();
    if (!$dir) { return ''; }
    return trailingslashit($dir) . wpbbshop_v408_market_feed_slug($service) . '.' . wpbbshop_v408_market_feed_extension($service);
}

function wpbbshop_v408_invalidate_market_feeds() {
    foreach (array_keys(wpbbshop_v408_services()['gb']) as $service) {
        $file = wpbbshop_v408_cache_file($service);
        if ($file && is_file($file)) { @unlink($file); }
    }
}

function wpbbshop_v408_write_cache($service, $contents) {
    $dir = wpbbshop_v408_cache_dir();
    if (!$dir) { return false; }
    if (!is_dir($dir) && !wp_mkdir_p($dir)) { return false; }
    if (!is_dir($dir) || !is_writable($dir)) { return false; }
    $file = wpbbshop_v408_cache_file($service);
    if (!$file) { return false; }
    $tmp = $file . '.tmp-' . wp_generate_password(8, false, false);
    if (@file_put_contents($tmp, $contents, LOCK_EX) === false) { @unlink($tmp); return false; }
    @chmod($tmp, 0644);
    if (!@rename($tmp, $file)) { @unlink($tmp); return false; }
    return true;
}

function wpbbshop_v408_product_description($product) {
    $description = wp_strip_all_tags((string) $product->get_short_description());
    if (!$description) { $description = wp_strip_all_tags((string) $product->get_description()); }
    if (!$description) { $description = $product->get_name(); }
    return function_exists('wpbbshop_compare_feed_text') ? wpbbshop_compare_feed_text($description, 5000) : wp_trim_words($description, 120, '');
}

function wpbbshop_v408_product_sku($product, $data) {
    $sku = trim((string) $product->get_sku());
    return $sku !== '' ? $sku : 'woo-' . absint($data['id']);
}

function wpbbshop_v408_feed_products() {
    if (!function_exists('wpbbshop_compare_feed_product_ids') || !function_exists('wpbbshop_compare_feed_product_data')) { return; }
    foreach (wpbbshop_compare_feed_product_ids() as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product || $product->is_type('variation')) { continue; }
        $data = wpbbshop_compare_feed_product_data($product);
        if (!$data) { continue; }
        $data['sku'] = wpbbshop_v408_product_sku($product, $data);
        $data['description'] = wpbbshop_v408_product_description($product);
        $data['availability'] = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';
        $data['stock_status'] = $product->is_in_stock() ? 'in stock' : 'out of stock';
        yield array($product, $data);
    }
}

function wpbbshop_v408_xml_cdata($value) {
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', (string) $value) . ']]>';
}

function wpbbshop_v408_google_xml($service = 'google') {
    $currency = get_woocommerce_currency() ?: 'GBP';
    $settings = wpbbshop_v408_market_settings();
    $delivery_cost = trim((string) $settings['uk_delivery_cost']);
    $title = $service === 'pricespy' ? 'PriceSpy UK product feed' : ($service === 'kelkoo' ? 'Kelkoo UK product feed' : 'Google Merchant product feed');
    $version = $service === 'pricespy' ? '3.0' : '2.0';
    $extra_ns = $service === 'pricespy' ? ' xmlns:pj="https://schema.prisjakt.nu/ns/1.0"' : '';
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<rss xmlns:g="http://base.google.com/ns/1.0"' . $extra_ns . ' version="' . $version . '">' . "\n<channel>\n";
    $xml .= '<title>' . esc_html($title) . "</title>\n";
    $xml .= '<link>' . esc_url(home_url('/')) . "</link>\n";
    $xml .= '<description>WP BB Home &amp; Garden product offers</description>' . "\n";
    foreach (wpbbshop_v408_feed_products() as $row) {
        list($product, $data) = $row;
        $xml .= "<item>\n";
        $xml .= '<g:id>' . wpbbshop_v408_xml_cdata($data['sku']) . "</g:id>\n";
        $xml .= '<g:title>' . wpbbshop_v408_xml_cdata($data['name']) . "</g:title>\n";
        $xml .= '<g:description>' . wpbbshop_v408_xml_cdata($data['description']) . "</g:description>\n";
        $xml .= '<g:link>' . wpbbshop_v408_xml_cdata($data['link']) . "</g:link>\n";
        if ($data['image']) { $xml .= '<g:image_link>' . wpbbshop_v408_xml_cdata($data['image']) . "</g:image_link>\n"; }
        $xml .= '<g:condition>' . ($data['used'] === '1' ? 'used' : 'new') . "</g:condition>\n";
        $xml .= '<g:availability>' . $data['availability'] . "</g:availability>\n";
        $xml .= '<g:price>' . esc_html($data['price'] . ' ' . $currency) . "</g:price>\n";
        if ($data['brand']) { $xml .= '<g:brand>' . wpbbshop_v408_xml_cdata($data['brand']) . "</g:brand>\n"; }
        if ($data['ean']) { $xml .= '<g:gtin>' . esc_html($data['ean']) . "</g:gtin>\n"; }
        if ($data['mpn']) { $xml .= '<g:mpn>' . wpbbshop_v408_xml_cdata($data['mpn']) . "</g:mpn>\n"; }
        if ($data['category_full']) { $xml .= '<g:product_type>' . wpbbshop_v408_xml_cdata($data['category_full']) . "</g:product_type>\n"; }
        if ($data['color']) { $xml .= '<g:color>' . wpbbshop_v408_xml_cdata($data['color']) . "</g:color>\n"; }
        if ($data['standard_delivery'] && $delivery_cost !== '') {
            $xml .= "<g:shipping><g:country>GB</g:country><g:service>Standard</g:service><g:price>" . esc_html(number_format((float) $delivery_cost, 2, '.', '') . ' ' . $currency) . "</g:price></g:shipping>\n";
        }
        $xml .= "</item>\n";
    }
    $xml .= "</channel>\n</rss>\n";
    return $xml;
}

function wpbbshop_v408_pricerunner_tsv() {
    $settings = wpbbshop_v408_market_settings();
    $currency = get_woocommerce_currency() ?: 'GBP';
    $headers = array('ProductId','ProductName','Price','Currency','StockStatus','Description','Ean','Category','ImageUrl','ProductUrl','Brand','Mpn','ShippingCost','DeliveryTime');
    $stream = fopen('php://temp', 'w+');
    fputcsv($stream, $headers, "\t", '"', '');
    foreach (wpbbshop_v408_feed_products() as $row) {
        list($product, $data) = $row;
        fputcsv($stream, array(
            $data['sku'], $data['name'], $data['price'], $currency, $data['stock_status'],
            wp_trim_words($data['description'], 30, ''), $data['ean'], $data['category_full'], $data['image'],
            $data['link'], $data['brand'], $data['mpn'],
            $data['standard_delivery'] ? number_format((float) $settings['uk_delivery_cost'], 2, '.', '') : '',
            $data['standard_delivery'] ? max(0, absint($settings['uk_delivery_days'])) . ' working days' : '',
        ), "\t", '"', '');
    }
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);
    return (string) $contents;
}

function wpbbshop_v408_idealo_csv() {
    $settings = wpbbshop_v408_market_settings();
    $provider = sanitize_key((string) $settings['idealo_delivery_provider']);
    $allowed = array('deutsche_post','dhl','dhl_go_green','dpd','fedex','gls','gls_think_green','hermes','pick_point','spedition','tnt','trans_o_flex','ups');
    if (!in_array($provider, $allowed, true)) { $provider = 'dpd'; }
    $delivery_col = 'deliveryCosts_' . $provider;
    $headers = array('sku','brand','title','categoryPath','url','eans','description','price','paymentCosts_paypal',$delivery_col,'deliveryTime','imageUrl','hans');
    $stream = fopen('php://temp', 'w+');
    fputcsv($stream, $headers, ',', '"', '');
    foreach (wpbbshop_v408_feed_products() as $row) {
        list($product, $data) = $row;
        fputcsv($stream, array(
            $data['sku'], $data['brand'], $data['name'], $data['category_full'], $data['link'], $data['ean'],
            $data['description'], $data['price'], '0.00',
            $data['standard_delivery'] ? number_format((float) $settings['uk_delivery_cost'], 2, '.', '') : '',
            $data['standard_delivery'] ? 'Delivered in ' . max(0, absint($settings['uk_delivery_days'])) . ' working days' : '',
            $data['image'], $data['mpn'],
        ), ',', '"', '');
    }
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);
    return (string) $contents;
}

function wpbbshop_v408_generate_market_feed($service) {
    $service = sanitize_key((string) $service);
    if (!wpbbshop_v408_uk_service_enabled($service) || !class_exists('WooCommerce')) { return false; }
    if (in_array($service, array('google','pricespy','kelkoo'), true)) {
        $contents = wpbbshop_v408_google_xml($service);
    } elseif ($service === 'pricerunner') {
        $contents = wpbbshop_v408_pricerunner_tsv();
    } elseif ($service === 'idealo') {
        $contents = wpbbshop_v408_idealo_csv();
    } else {
        return false;
    }
    if (is_string($contents) && $contents !== '') { wpbbshop_v408_write_cache($service, $contents); }
    return $contents;
}

function wpbbshop_v408_refresh_market_feeds() {
    if (wpbbshop_v408_resolved_market() !== 'gb') { return; }
    foreach (array_keys(wpbbshop_v408_services()['gb']) as $service) {
        if (wpbbshop_v408_uk_service_enabled($service)) { wpbbshop_v408_generate_market_feed($service); }
    }
}
add_action('wpbbshop_compare_feed_daily_refresh', 'wpbbshop_v408_refresh_market_feeds', 20);

function wpbbshop_v408_serve_market_feed() {
    $service = get_query_var('wpbbshop_market_feed');
    if (!$service && isset($_GET['wpbbshop_market_feed'])) { $service = sanitize_key(wp_unslash($_GET['wpbbshop_market_feed'])); }
    $service = sanitize_key((string) $service);
    if (!isset(wpbbshop_v408_services()['gb'][$service])) { return; }
    if (!wpbbshop_v408_uk_service_enabled($service)) { status_header(404); nocache_headers(); exit; }

    $file = wpbbshop_v408_cache_file($service);
    $max_age = 6 * HOUR_IN_SECONDS;
    if (!$file || !is_file($file) || (time() - (int) @filemtime($file)) > $max_age) {
        $contents = wpbbshop_v408_generate_market_feed($service);
    } else {
        $contents = @file_get_contents($file);
    }
    if (!is_string($contents) || $contents === '') { status_header(500); echo 'Feed generation failed.'; exit; }

    status_header(200);
    if ($service === 'pricerunner') { header('Content-Type: text/tab-separated-values; charset=UTF-8'); }
    elseif ($service === 'idealo') { header('Content-Type: text/csv; charset=UTF-8'); }
    else { header('Content-Type: application/xml; charset=UTF-8'); }
    header('X-Robots-Tag: noindex, follow', true);
    header('Cache-Control: public, max-age=900, stale-while-revalidate=3600');
    echo $contents;
    exit;
}
add_action('template_redirect', 'wpbbshop_v408_serve_market_feed', 0);

function wpbbshop_v408_sanitize_market_settings($input) {
    $defaults = wpbbshop_v408_market_defaults();
    $input = is_array($input) ? $input : array();
    $out = $defaults;
    $mode = isset($input['market_mode']) ? sanitize_key(wp_unslash($input['market_mode'])) : 'auto';
    $out['market_mode'] = in_array($mode, array('auto','lv','gb'), true) ? $mode : 'auto';
    foreach (array('google','pricerunner','pricespy','idealo','kelkoo') as $service) {
        $out['enable_' . $service] = !empty($input['enable_' . $service]) ? '1' : '0';
    }
    $out['uk_delivery_cost'] = isset($input['uk_delivery_cost']) ? (string) max(0, (float) str_replace(',', '.', sanitize_text_field(wp_unslash($input['uk_delivery_cost'])))) : $defaults['uk_delivery_cost'];
    $out['uk_delivery_days'] = isset($input['uk_delivery_days']) ? (string) max(0, absint($input['uk_delivery_days'])) : $defaults['uk_delivery_days'];
    $provider = isset($input['idealo_delivery_provider']) ? sanitize_key(wp_unslash($input['idealo_delivery_provider'])) : $defaults['idealo_delivery_provider'];
    $out['idealo_delivery_provider'] = $provider ?: 'dpd';
    return $out;
}

add_action('admin_post_wpbbshop_v408_market_feeds', function() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }
    check_admin_referer('wpbbshop_v408_market_feeds');
    $settings = wpbbshop_v408_sanitize_market_settings(isset($_POST['wpbbshop_market']) ? $_POST['wpbbshop_market'] : array());
    update_option('wpbbshop_market_feeds_v408', $settings, false);
    wpbbshop_v408_invalidate_market_feeds();
    if (function_exists('wpbbshop_compare_feed_invalidate')) { wpbbshop_compare_feed_invalidate(); }
    wpbbshop_v408_maybe_flush_rewrites();
    if (function_exists('wpbbshop_v400_admin_url')) {
        wp_safe_redirect(wpbbshop_v400_admin_url('feeds', array('wpbb_notice'=>'Market feed settings saved.')));
    } else {
        wp_safe_redirect(admin_url('themes.php'));
    }
    exit;
});

add_action('admin_post_wpbbshop_v408_regenerate_market_feeds', function() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }
    check_admin_referer('wpbbshop_v408_regenerate_market_feeds');
    wpbbshop_v408_invalidate_market_feeds();
    if (function_exists('wpbbshop_compare_feed_invalidate')) { wpbbshop_compare_feed_invalidate(); }
    if (wpbbshop_v408_resolved_market() === 'gb') {
        wpbbshop_v408_refresh_market_feeds();
    } elseif (function_exists('wpbbshop_compare_feed_refresh_all')) {
        wpbbshop_compare_feed_refresh_all();
    }
    if (function_exists('wpbbshop_v400_admin_url')) {
        wp_safe_redirect(wpbbshop_v400_admin_url('feeds', array('wpbb_notice'=>'Active market feeds regenerated.')));
    } else {
        wp_safe_redirect(admin_url('themes.php'));
    }
    exit;
});

function wpbbshop_v408_footer_comparison_html($is_en = true) {
    $market = wpbbshop_v408_resolved_market();
    $services = wpbbshop_v408_services();
    $html = '<div class="llg-comparison-sites">';
    foreach ($services[$market] as $key => $service) {
        $enabled = $market === 'lv'
            ? (function_exists('wpbbshop_compare_feed_enabled') && wpbbshop_compare_feed_enabled($key))
            : wpbbshop_v408_uk_service_enabled($key);
        if (!$enabled) { continue; }
        $html .= '<a href="' . esc_url($service['site']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($service['name']) . '</a>';
    }
    $html .= '</div><div class="llg-comparison-xml"><span>' . esc_html($is_en ? 'Product feeds' : 'Produktu plūsmas') . '</span>';
    foreach ($services[$market] as $key => $service) {
        $enabled = $market === 'lv'
            ? (function_exists('wpbbshop_compare_feed_enabled') && wpbbshop_compare_feed_enabled($key))
            : wpbbshop_v408_uk_service_enabled($key);
        if (!$enabled) { continue; }
        $url = $market === 'lv' && function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url($key) : wpbbshop_v408_market_feed_url($key);
        $html .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($service['name'] . ' ' . $service['format']) . '</a>';
    }
    return $html . '</div>';
}

add_action('save_post_product', 'wpbbshop_v408_invalidate_market_feeds', 40);
add_action('woocommerce_update_product', 'wpbbshop_v408_invalidate_market_feeds', 40);
add_action('woocommerce_update_product_variation', 'wpbbshop_v408_invalidate_market_feeds', 40);
add_action('woocommerce_product_set_stock', 'wpbbshop_v408_invalidate_market_feeds', 40);
add_action('woocommerce_variation_set_stock', 'wpbbshop_v408_invalidate_market_feeds', 40);
