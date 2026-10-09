<?php
/**
 * WP BB Home & Garden v4.0.27
 * Real-photo showcase layer for demo products and Product Guides.
 *
 * Product cards use distinct photographic references rather than generated
 * illustration cards. Wikimedia Commons is used for the demo-only source set;
 * when wp-admin is available the images are cached incrementally in Media
 * Library, while the public URL remains a safe fallback until caching finishes.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V426_VERSION')) {
    define('WPBBSHOP_V426_VERSION', '4.0.27');
}

function wpbbshop_v426_commons_file_url($filename, $width = 1600) {
    $filename = trim((string) $filename);
    if ($filename === '') { return ''; }
    $width = max(720, min(2200, absint($width)));
    return esc_url_raw('https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode($filename) . '?width=' . $width);
}

/** Distinct photo for every v4.0.23/25 showcase product. */
function wpbbshop_v426_product_sources() {
    $sources = array(
        'panel-radiator-22' => array(
            'file' => '247 Home Rescue radiators.jpg',
            'license' => 'CC0 1.0',
            'credit' => '247homerescue / Wikimedia Commons',
        ),
        'underfloor-mat' => array(
            'file' => 'Public domain image - underfloor heating installation.JPG',
            'license' => 'CC0 1.0',
            'credit' => 'Kiwiev / Wikimedia Commons',
        ),
        'cultivator-1050' => array(
            'file' => 'Power tiller or cultivator.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'Wikimedia Commons contributor',
        ),
        'mini-tractor-15hp' => array(
            'file' => 'Australis 40hp compact tractor.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'ModelTMitch / Wikimedia Commons',
        ),
        'towel-radiator' => array(
            'file' => 'Towel rails radiator with hanger.jpg',
            'license' => 'CC BY 4.0',
            'credit' => 'MB SRL / Wikimedia Commons',
        ),
        'boiler-24kw' => array(
            'file' => 'Wall mounted boiler in kitchen.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'Turbojet / Wikimedia Commons',
        ),
        'pump-25-60' => array(
            'file' => 'Circulationspumpe Grundfos 2017-10.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'The RedBurn / Wikimedia Commons',
        ),
        'riding-mower-1050a' => array(
            'file' => 'Lawn mower on the grass closeup.jpg',
            'license' => 'CC BY 2.0',
            'credit' => 'Shixart1985 / Wikimedia Commons',
        ),
        'air-source-heat-pump' => array(
            'file' => 'Heat pump unit.webp',
            'license' => 'CC0 1.0',
            'credit' => 'Wikideas1 / Wikimedia Commons',
        ),
        'underfloor-manifold' => array(
            'file' => 'Heizkreisverteiler.jpg',
            'license' => 'CC BY-SA 3.0',
            'credit' => 'Tetris L / Wikimedia Commons',
        ),
        'radiator-valve-pack' => array(
            'file' => 'Danfoss thermostatic radiator valve.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'Santeri Viinamaki / Wikimedia Commons',
        ),
        'compact-tractor-package' => array(
            'file' => 'Garden machines, tractor.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'Emmanuel Ssekaggo / Wikimedia Commons',
        ),
    );

    // v4.0.27: the 500 HG-DEMO items now use department-matched real photos.
    // Two photos per department keep adjacent/featured/sale cards visually distinct
    // while still avoiding hundreds of heavy Media Library imports.
    $catalog_files = array(
        'garden-machinery' => array('Lawn mower on the grass closeup.jpg', 'Garden machines, tractor.jpg'),
        'garden-tools' => array('Garden Tools.jpg', 'Gardening tools.jpg'),
        'watering-irrigation' => array('Gardenhose.JPG', 'Lawn water hose sprinkler 2500x3300 (1).png'),
        'greenhouses' => array('GREENHOUSE.jpg', 'BIO Greenhouse.jpg'),
        'garden-furniture' => array('Outdoor furniture.jpg', 'Teak Garden Furniture Patio Set.jpg'),
        'plants-seeds' => array('Plant seedlings.jpg', 'Seed Packets.jpg'),
        'fertilisers-care' => array('Bags of compost - geograph.org.uk - 7828282.jpg', 'Fertilizer.jpg'),
        'fences-gates' => array('Garden fence and gate at Nuthurst, West Sussex, England.jpg', 'Boundary gate and fence.jpg'),
        'outdoor-storage' => array('Garden shed with furniture in Brastad 1.jpg', 'A new garden shed - geograph.org.uk - 7481143.jpg'),
        'bbq-outdoor-cooking' => array('Round open-air barbecue grill.jpg', 'Outdoor cooking area with a barbecue grill and tables for gathering in a backyard setting.jpg'),
        'power-tools' => array('CORDLESS DRILL.jpg', 'Cordless Electric Drill.jpg'),
        'hand-tools' => array('Hammer hand tool.jpg', 'Garden tools.jpg'),
        'building-materials' => array('Bricks for construction.jpg', 'BRICKS.jpg'),
        'paint-finishing' => array('Paint roller (4600214187).jpg', 'Paint cans.jpg'),
        'plumbing-heating' => array('Heating plumbing.jpg', 'Room radiator.jpg'),
        'lighting-electrical' => array('ELECTRICAL SOCKET.jpg', 'Electric Socket.jpeg'),
        'home-storage' => array('Storage Shelves (43782351190).jpg', 'A Kaleidoscope of Kitchen and Home Storage.jpg'),
        'cleaning' => array('Vacuum cleaner.jpg', 'Cleaning supplies section in japanese hardware store.jpg'),
        'workwear-safety' => array('Aa safetyhelmet 00.jpg', 'Safety gloves.jpg'),
        'pet-outdoor' => array('Dog kennel.jpg', 'A dog in kennel.jpg'),
    );
    foreach ($catalog_files as $department => $files) {
        foreach (array_values($files) as $index => $file) {
            $suffix = $index === 0 ? 'a' : 'b';
            $sources['catalog-' . $department . '-' . $suffix] = array(
                'file' => $file,
                'license' => 'See Wikimedia Commons file page',
                'credit' => 'Wikimedia Commons contributor',
            );
        }
    }
    return $sources;
}

/** One unique editorial photograph per Product Guide. */
function wpbbshop_v426_blog_sources() {
    return array(
        'heating-plan' => array(
            'file' => 'Air Source Heat Pump - Vailliant aroTherm Plus on a terraced house.jpg',
            'license' => 'CC0 1.0',
            'credit' => 'Southend-on-Sea Borough Council / Wikimedia Commons',
        ),
        'radiator-size' => array(
            'file' => 'Room radiator.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'Tarasna0922 / Wikimedia Commons',
        ),
        'cultivator-guide' => array(
            'file' => 'Tilling backyard for new garden.jpg',
            'license' => 'CC BY-SA 2.0',
            'credit' => 'Joe Hoover / Wikimedia Commons',
        ),
        'variation-stock' => array(
            'file' => 'A Kaleidoscope of Kitchen and Home Storage.jpg',
            'license' => 'See Wikimedia Commons file page',
            'credit' => 'Kaviya Rajendran / Wikimedia Commons',
        ),
        'underfloor-heating' => array(
            'file' => 'Dornbirn-Ebnit-underfloor heating-system-02ASD.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'Asurnipal / Wikimedia Commons',
        ),
        'garden-maintenance' => array(
            'file' => 'Person using a lawn mower in a green garden.jpg',
            'license' => 'See Wikimedia Commons file page',
            'credit' => 'Shixart1985 / Wikimedia Commons',
        ),
        'heat-pump-guide' => array(
            'file' => 'NIBE S2125 air source heat pump (rear) - Science Museum, London.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'The wub / Wikimedia Commons',
        ),
        'tractor-attachments' => array(
            'file' => 'Garden machines, tractor.jpg',
            'license' => 'CC BY-SA 4.0',
            'credit' => 'Emmanuel Ssekaggo / Wikimedia Commons',
        ),
    );
}

function wpbbshop_v426_source_url($scope, $key, $width = 1600) {
    $map = $scope === 'blog' ? wpbbshop_v426_blog_sources() : wpbbshop_v426_product_sources();
    if (empty($map[$key]['file'])) { return ''; }
    return wpbbshop_v426_commons_file_url($map[$key]['file'], $width);
}

function wpbbshop_v426_cached_attachment_id($scope, $key) {
    static $cache = array();
    $cache_key = $scope . ':' . $key;
    if (array_key_exists($cache_key, $cache)) { return $cache[$cache_key]; }

    $map = $scope === 'blog' ? wpbbshop_v426_blog_sources() : wpbbshop_v426_product_sources();
    $expected_file = isset($map[$key]['file']) ? (string) $map[$key]['file'] : '';
    if ($expected_file === '') { $cache[$cache_key] = 0; return 0; }

    // Several older v4.0.26 attachments can share the same logical key. Only
    // reuse the attachment when it was created from the source file that is
    // currently configured, otherwise the storefront would keep showing a
    // stale/dated image after a theme update.
    $ids = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 20,
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'DESC',
        'meta_query' => array(
            array('key' => '_wpbbshop_v426_scope', 'value' => $scope),
            array('key' => '_wpbbshop_v426_key', 'value' => $key),
        ),
        'suppress_filters' => true,
    ));
    foreach ((array) $ids as $id) {
        if ((string) get_post_meta((int) $id, '_wpbbshop_v426_source_file', true) === $expected_file) {
            $cache[$cache_key] = (int) $id;
            return $cache[$cache_key];
        }
    }
    $cache[$cache_key] = 0;
    return 0;
}

function wpbbshop_v426_cached_or_remote($scope, $key, $size = 'large', $width = 1600) {
    $attachment_id = wpbbshop_v426_cached_attachment_id($scope, $key);
    if ($attachment_id) {
        $url = wp_get_attachment_image_url($attachment_id, $size);
        if ($url) { return $url; }
    }
    return wpbbshop_v426_source_url($scope, $key, $width);
}

function wpbbshop_v426_product_key($product) {
    if (!$product || !is_a($product, 'WC_Product')) { return ''; }
    $id = $product instanceof WC_Product_Variation ? $product->get_parent_id() : $product->get_id();
    $key = (string) get_post_meta($id, '_wpbbshop_v422_demo_key', true);
    if ($key !== '') { return $key; }

    $sku = (string) $product->get_sku();
    $is_demo = (string) get_post_meta($id, '_wpbbshop_demo_product', true) === '1' || stripos($sku, 'HG-DEMO') === 0;
    if (!$is_demo) { return ''; }

    // The 500-item catalogue is generated in a fixed 20-department rotation.
    // Resolve its SKU directly so every department gets a real matching photo
    // instead of falling through to the old green schematic artwork.
    if (preg_match('/^HG-DEMO-(\d{4})$/i', $sku, $m)) {
        $number = max(1, (int) $m[1]);
        $departments = array(
            'garden-machinery','garden-tools','watering-irrigation','greenhouses','garden-furniture',
            'plants-seeds','fertilisers-care','fences-gates','outdoor-storage','bbq-outdoor-cooking',
            'power-tools','hand-tools','building-materials','paint-finishing','plumbing-heating',
            'lighting-electrical','home-storage','cleaning','workwear-safety','pet-outdoor',
        );
        $department = $departments[($number - 1) % count($departments)];
        $variant = (int) floor(($number - 1) / count($departments)) % 2 === 0 ? 'a' : 'b';
        return 'catalog-' . $department . '-' . $variant;
    }

    // Keep older local demo products photo-led too, even if they pre-date the
    // 500-item SKU convention.
    $hay = strtolower(remove_accents($product->get_name() . ' ' . $sku));
    if (preg_match('/tractor|rider|ride.on|mower|p[lļ][aā]v/', $hay)) { return 'riding-mower-1050a'; }
    if (preg_match('/cultiv|tiller|fr[eē]z|augsn/', $hay)) { return 'cultivator-1050'; }
    if (preg_match('/towel|bathroom radiator|dvie[lļ]/', $hay)) { return 'towel-radiator'; }
    if (preg_match('/radiator|heater|sild|apkures panel/', $hay)) { return 'panel-radiator-22'; }
    if (preg_match('/underfloor|floor heat|gr[iī]das apk/', $hay)) { return 'underfloor-mat'; }
    if (preg_match('/pump|s[uū]kn/', $hay)) { return 'pump-25-60'; }
    if (preg_match('/boiler|katl/', $hay)) { return 'boiler-24kw'; }
    if (preg_match('/valve|v[aā]rst/', $hay)) { return 'radiator-valve-pack'; }
    return '';
}

function wpbbshop_v426_demo_product_asset($product) {
    $key = wpbbshop_v426_product_key($product);
    if ($key === '') { return ''; }
    $map = wpbbshop_v426_product_sources();
    if (!isset($map[$key])) { return ''; }
    return wpbbshop_v426_cached_or_remote('product', $key, 'large', strpos($key, 'catalog-') === 0 ? 1100 : 1500);
}

/**
 * Real-photo gallery for both the 500-item catalogue and the richer showcase
 * products. The first URL is the card/hero image; following URLs are genuine
 * photographic alternatives used for hover and the single-product gallery.
 */
function wpbbshop_v426_demo_product_gallery_keys($product) {
    $key = wpbbshop_v426_product_key($product);
    if ($key === '') { return array(); }

    if (preg_match('/^catalog-(.+)-(a|b)$/', $key, $m)) {
        $other = $m[2] === 'a' ? 'b' : 'a';
        return array($key, 'catalog-' . $m[1] . '-' . $other);
    }

    $map = array(
        'panel-radiator-22' => array('panel-radiator-22','towel-radiator','radiator-valve-pack'),
        'underfloor-mat' => array('underfloor-mat','underfloor-manifold','pump-25-60'),
        'cultivator-1050' => array('cultivator-1050','mini-tractor-15hp','riding-mower-1050a'),
        'mini-tractor-15hp' => array('mini-tractor-15hp','compact-tractor-package','cultivator-1050'),
        'towel-radiator' => array('towel-radiator','panel-radiator-22','radiator-valve-pack'),
        'boiler-24kw' => array('boiler-24kw','pump-25-60','panel-radiator-22'),
        'pump-25-60' => array('pump-25-60','underfloor-manifold','boiler-24kw'),
        'riding-mower-1050a' => array('riding-mower-1050a','catalog-garden-machinery-b','cultivator-1050'),
        'air-source-heat-pump' => array('air-source-heat-pump','pump-25-60','underfloor-mat'),
        'underfloor-manifold' => array('underfloor-manifold','underfloor-mat','pump-25-60'),
        'radiator-valve-pack' => array('radiator-valve-pack','panel-radiator-22','towel-radiator'),
        'compact-tractor-package' => array('compact-tractor-package','mini-tractor-15hp','cultivator-1050'),
    );
    return isset($map[$key]) ? $map[$key] : array($key);
}

function wpbbshop_v426_demo_product_gallery_urls($product) {
    $keys = wpbbshop_v426_demo_product_gallery_keys($product);
    if (!$keys) { return array(); }
    $sources = wpbbshop_v426_product_sources();
    $urls = array();
    foreach ($keys as $key) {
        if (!isset($sources[$key])) { continue; }
        $width = strpos($key, 'catalog-') === 0 ? 1100 : 1500;
        $url = wpbbshop_v426_cached_or_remote('product', $key, 'large', $width);
        if ($url && !in_array($url, $urls, true)) { $urls[] = $url; }
    }
    return $urls;
}

function wpbbshop_v426_guide_key($post_id) {
    $post_id = absint($post_id);
    return $post_id ? (string) get_post_meta($post_id, '_wpbbshop_v422_guide_key', true) : '';
}

function wpbbshop_v426_guide_image_url($post_id) {
    $key = wpbbshop_v426_guide_key($post_id);
    if ($key === '') { return ''; }
    $map = wpbbshop_v426_blog_sources();
    if (!isset($map[$key])) { return ''; }
    return wpbbshop_v426_cached_or_remote('blog', $key, 'large', 1800);
}

function wpbbshop_v426_gallery_source_map() {
    return array(
        'heating-plan' => array(array('blog','heating-plan'), array('product','air-source-heat-pump'), array('product','panel-radiator-22')),
        'radiator-size' => array(array('blog','radiator-size'), array('product','panel-radiator-22'), array('product','radiator-valve-pack')),
        'cultivator-guide' => array(array('blog','cultivator-guide'), array('product','cultivator-1050'), array('product','mini-tractor-15hp')),
        'variation-stock' => array(array('blog','variation-stock'), array('product','pump-25-60'), array('product','underfloor-manifold')),
        'underfloor-heating' => array(array('blog','underfloor-heating'), array('product','underfloor-mat'), array('product','underfloor-manifold')),
        'garden-maintenance' => array(array('blog','garden-maintenance'), array('product','riding-mower-1050a'), array('product','cultivator-1050')),
        'heat-pump-guide' => array(array('blog','heat-pump-guide'), array('product','air-source-heat-pump'), array('product','pump-25-60')),
        'tractor-attachments' => array(array('blog','tractor-attachments'), array('product','compact-tractor-package'), array('product','mini-tractor-15hp')),
    );
}

function wpbbshop_v426_post_gallery_items($post_id) {
    $post_id = absint($post_id);
    if (!$post_id) { return array(); }
    $key = wpbbshop_v426_guide_key($post_id);
    $map = wpbbshop_v426_gallery_source_map();
    if ($key === '' || empty($map[$key])) { return array(); }
    $items = array();
    foreach ($map[$key] as $source) {
        $scope = $source[0]; $source_key = $source[1];
        $url = wpbbshop_v426_cached_or_remote($scope, $source_key, 'large', $scope === 'blog' ? 1800 : 1500);
        if (!$url) { continue; }
        $items[] = array(
            'url' => $url,
            'thumb' => $url,
            'caption' => get_the_title($post_id),
        );
    }
    return $items;
}

/**
 * Cache only a couple of files per admin request. The storefront is already
 * usable from the remote URL, so local caching never blocks page delivery.
 */
function wpbbshop_v426_incremental_media_cache() {
    if (!current_user_can('manage_options')) { return; }
    if (function_exists('wpbbshop_v422_is_local_site') && !wpbbshop_v422_is_local_site()) { return; }

    $queue = array();
    foreach (wpbbshop_v426_product_sources() as $key => $spec) { $queue[] = array('scope'=>'product','key'=>$key,'spec'=>$spec); }
    foreach (wpbbshop_v426_blog_sources() as $key => $spec) { $queue[] = array('scope'=>'blog','key'=>$key,'spec'=>$spec); }

    $done = get_option('wpbbshop_v426_cached_sources', array());
    if (!is_array($done)) { $done = array(); }
    $imported_this_request = 0;

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    foreach ($queue as $item) {
        $token = $item['scope'] . ':' . $item['key'] . ':' . md5((string) $item['spec']['file']);
        if (wpbbshop_v426_cached_attachment_id($item['scope'], $item['key'])) {
            $done[$token] = 1;
            continue;
        }
        if ($imported_this_request >= 2) { break; }

        $url = wpbbshop_v426_source_url($item['scope'], $item['key'], $item['scope'] === 'blog' ? 1800 : 1500);
        if (!$url) { continue; }
        $title = ucwords(str_replace(array('-', '_'), ' ', $item['key']));
        $attachment_id = media_sideload_image($url, 0, $title, 'id');
        if (is_wp_error($attachment_id) || !$attachment_id) { continue; }

        $attachment_id = (int) $attachment_id;
        update_post_meta($attachment_id, '_wpbbshop_v426_scope', $item['scope']);
        update_post_meta($attachment_id, '_wpbbshop_v426_key', $item['key']);
        update_post_meta($attachment_id, '_wpbbshop_v426_source_file', (string) $item['spec']['file']);
        update_post_meta($attachment_id, '_wpbbshop_v426_license', (string) $item['spec']['license']);
        update_post_meta($attachment_id, '_wpbbshop_v426_credit', (string) $item['spec']['credit']);

        if ($item['scope'] === 'product') {
            $posts = get_posts(array('post_type'=>'product','post_status'=>'any','posts_per_page'=>50,'fields'=>'ids','meta_key'=>'_wpbbshop_v422_demo_key','meta_value'=>$item['key'],'suppress_filters'=>true));
            foreach ($posts as $post_id) { set_post_thumbnail((int) $post_id, $attachment_id); }
        } else {
            $posts = get_posts(array('post_type'=>'post','post_status'=>'any','posts_per_page'=>50,'fields'=>'ids','meta_key'=>'_wpbbshop_v422_guide_key','meta_value'=>$item['key'],'suppress_filters'=>true));
            foreach ($posts as $post_id) {
                set_post_thumbnail((int) $post_id, $attachment_id);
                $local_url = wp_get_attachment_image_url($attachment_id, 'large');
                if ($local_url) { update_post_meta((int) $post_id, '_wpbbshop_v423_featured_image_url', esc_url_raw($local_url)); }
            }
        }

        $done[$token] = 1;
        $imported_this_request++;
    }

    update_option('wpbbshop_v426_cached_sources', $done, false);
    if ($imported_this_request === 0) { update_option('wpbbshop_v426_media_version', WPBBSHOP_V426_VERSION, false); }
}
add_action('admin_init', 'wpbbshop_v426_incremental_media_cache', 195);
add_action('after_switch_theme', 'wpbbshop_v426_incremental_media_cache', 195);

/** The older v4.0.25 filter delegates into this function, but keep a final
 * explicit filter too for third-party loops that bypass the child card helper. */
add_filter('woocommerce_product_get_image', function($html, $product, $size, $attr, $placeholder) {
    $url = wpbbshop_v426_demo_product_asset($product);
    if (!$url) { return $html; }
    $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wpbb-v425-demo-image wpbb-v426-realistic-product';
    if (is_array($attr) && !empty($attr['class'])) { $classes .= ' ' . sanitize_html_class($attr['class']); }
    $loading = is_array($attr) && !empty($attr['loading']) ? (string) $attr['loading'] : 'lazy';
    return '<img src="' . esc_url($url) . '" alt="' . esc_attr($product->get_name()) . '" class="' . esc_attr($classes) . '" loading="' . esc_attr($loading) . '" decoding="async" referrerpolicy="no-referrer">';
}, PHP_INT_MAX, 5);

add_action('wp_enqueue_scripts', function() {
    $css = get_stylesheet_directory() . '/assets/css/v426-realistic-media.css';
    if (is_readable($css)) {
        wp_enqueue_style('wpbbshop-v426-realistic-media', get_stylesheet_directory_uri() . '/assets/css/v426-realistic-media.css', array('wpbbshop-v425-content-cart-quote'), (string) filemtime($css));
    }
}, PHP_INT_MAX);

add_action('wp_head', function() {
    if (is_admin()) { return; }
    echo '<link rel="preconnect" href="https://commons.wikimedia.org" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://upload.wikimedia.org" crossorigin>' . "\n";
}, 2);
