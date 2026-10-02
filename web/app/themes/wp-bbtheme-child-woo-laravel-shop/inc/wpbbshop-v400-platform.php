<?php
/**
 * WP BB Home & Garden 4.0 platform integration.
 *
 * Consolidates demo tooling, bilingual defaults, comparison feeds,
 * Bedrock/Acorn diagnostics and the large-catalogue storefront.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V400_VERSION')) {
    define('WPBBSHOP_V400_VERSION', '4.0.8');
}

function wpbbshop_v400_primary_language() {
    $saved = sanitize_key((string) get_option('wpbbshop_primary_language', ''));
    if (in_array($saved, array('en', 'lv'), true)) { return $saved; }
    $pll = get_option('polylang', array());
    if (is_array($pll) && !empty($pll['default_lang']) && in_array($pll['default_lang'], array('en', 'lv'), true)) {
        return $pll['default_lang'];
    }
    return 'en';
}

function wpbbshop_v400_current_language() {
    // Respect an explicit /en/ or /lv/ prefix even when product queries are
    // language-neutral so one WooCommerce inventory can be shared safely.
    if (!empty($_SERVER['REQUEST_URI'])) {
        $path = trim((string) parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/');
        $first = $path === '' ? '' : sanitize_key((string) strtok($path, '/'));
        if (in_array($first, array('en', 'lv'), true)) { return $first; }
    }
    if (function_exists('pll_current_language')) {
        $lang = sanitize_key((string) pll_current_language('slug'));
        if (in_array($lang, array('en', 'lv'), true)) { return $lang; }
    }
    return wpbbshop_v400_primary_language();
}

function wpbbshop_v400_t($en, $lv) {
    return wpbbshop_v400_current_language() === 'lv' ? $lv : $en;
}

function wpbbshop_v400_demo_images() {
    return array(
        'drill-v46.webp','compressor.webp','welder-v43-v46.webp','generator.webp',
        'jack-v43-v46.webp','impact-wrench.webp','tool-set.webp','blower-v45-v46.webp',
        'brushcutter.webp','trimmer-head-v43-v46.webp','aluminum-head-v45-v46.webp',
        'trimmer-line-v45-v46.webp','chain-sharpener-v43-v46.webp','oil-pump-v43-v46.webp'
    );
}

/**
 * Lightweight remote demo imagery.
 *
 * Demo products intentionally do not create Media Library attachments. A small
 * deterministic set of remote images is reused across the 20 departments, so
 * the catalogue stays compact even with 500 demo products. The local bundled
 * WebP set remains a fallback when a remote URL is unavailable.
 */
function wpbbshop_v400_remote_demo_images() {
    $files = wpbbshop_v400_demo_images();
    $departments = function_exists('wpbbshop_megastore_departments_312') ? wpbbshop_megastore_departments_312() : array();
    $urls = array();
    foreach ((array) $departments as $i => $department) {
        $slug = isset($department['slug']) ? sanitize_key($department['slug']) : '';
        if (!$slug || !$files) { continue; }
        $file = $files[$i % count($files)];
        $urls[$slug] = get_stylesheet_directory_uri() . '/assets/demo-products/' . rawurlencode($file);
    }
    return $urls;
}

function wpbbshop_v400_remote_demo_image_for_index($index) {
    if (!function_exists('wpbbshop_megastore_departments_312')) { return ''; }
    $departments = wpbbshop_megastore_departments_312();
    if (!$departments) { return ''; }
    $department = $departments[$index % count($departments)];
    $slug = isset($department['slug']) ? sanitize_key($department['slug']) : '';
    $map = wpbbshop_v400_remote_demo_images();
    return isset($map[$slug]) ? esc_url_raw($map[$slug]) : '';
}

/**
 * Backfill image mapping only for this theme's generated HG-DEMO catalogue.
 * This is indexed through wc_product_meta_lookup instead of scanning all products.
 */
function wpbbshop_v400_backfill_demo_images() {
    if (!class_exists('WooCommerce')) { return 0; }
    global $wpdb;
    $lookup = $wpdb->wc_product_meta_lookup;
    if (!$lookup) { return 0; }
    $ids = $wpdb->get_results(
        "SELECT product_id, sku FROM {$lookup} WHERE sku LIKE 'HG-DEMO-%' ORDER BY sku ASC LIMIT 600",
        ARRAY_A
    );
    $images = wpbbshop_v400_demo_images();
    $changed = 0;
    foreach ((array) $ids as $row) {
        $id = absint(isset($row['product_id']) ? $row['product_id'] : 0);
        $sku = isset($row['sku']) ? (string) $row['sku'] : '';
        if (!$id || !preg_match('/^HG-DEMO-(\d{4})$/', $sku, $m)) { continue; }
        $index = max(0, ((int) $m[1]) - 1);
        $file = $images[$index % count($images)];
        if (get_post_meta($id, '_wpbbshop_demo_image', true) !== $file) {
            update_post_meta($id, '_wpbbshop_demo_image', $file);
            $changed++;
        }
        $remote = wpbbshop_v400_remote_demo_image_for_index($index);
        if ($remote && get_post_meta($id, '_wpbbshop_demo_image_url', true) !== $remote) {
            update_post_meta($id, '_wpbbshop_demo_image_url', $remote);
            $changed++;
        }
        update_post_meta($id, '_wpbbshop_demo_product', '1');
        update_post_meta($id, '_wpbbshop_feed_exclude', 'yes');
    }
    return $changed;
}

function wpbbshop_v400_set_primary_language($lang) {
    $lang = in_array($lang, array('en','lv'), true) ? $lang : 'en';
    update_option('wpbbshop_primary_language', $lang, false);

    if (function_exists('wpbbshop_polylang_ensure_language_235')) {
        wpbbshop_polylang_ensure_language_235('en','English','en_GB','gb',0);
        wpbbshop_polylang_ensure_language_235('lv','Latviešu','lv','lv',1);
    }

    if (function_exists('PLL')) {
        try {
            $pll = PLL();
            if (is_object($pll) && !empty($pll->model)) {
                $model = isset($pll->model->languages) && is_object($pll->model->languages) ? $pll->model->languages : $pll->model;
                if (method_exists($model, 'update_default')) {
                    $model->update_default($lang);
                } elseif (is_callable(array($pll->model, 'update_default_lang'))) {
                    $pll->model->update_default_lang($lang);
                }
            }
        } catch (Throwable $e) {}
    }

    $opts = get_option('polylang', array());
    if (!is_array($opts)) { $opts = array(); }
    $opts['default_lang'] = $lang;
    $opts['hide_default'] = true;
    $opts['media_support'] = false;
    update_option('polylang', $opts, false);
    update_option('WPLANG', $lang === 'lv' ? 'lv' : 'en_GB', false);

    wpbbshop_v400_ensure_home_translations($lang);
    do_action('wpbbshop_primary_language_changed', $lang);
    return $lang;
}

function wpbbshop_v400_ensure_home_translations($primary = '') {
    if (!function_exists('pll_set_post_language') || !function_exists('pll_save_post_translations')) { return; }
    $primary = in_array($primary, array('en','lv'), true) ? $primary : wpbbshop_v400_primary_language();
    $front_id = absint(get_option('page_on_front'));
    $content = '[wpbbshop_home]';

    if ($front_id) {
        $front = get_post($front_id);
        if ($front instanceof WP_Post && trim((string) $front->post_content) !== '') {
            $content = $front->post_content;
        }
    }

    $en_id = $front_id && function_exists('pll_get_post') ? absint(pll_get_post($front_id, 'en')) : 0;
    $lv_id = $front_id && function_exists('pll_get_post') ? absint(pll_get_post($front_id, 'lv')) : 0;

    if (!$en_id && $front_id) {
        $front_lang = function_exists('pll_get_post_language') ? pll_get_post_language($front_id, 'slug') : '';
        if ($front_lang === 'en' || $front_lang === '') {
            $en_id = $front_id;
            @pll_set_post_language($en_id, 'en');
        }
    }

    if (!$en_id) {
        $existing = get_page_by_path('home');
        $en_id = $existing ? absint($existing->ID) : absint(wp_insert_post(array(
            'post_type'=>'page','post_status'=>'publish','post_title'=>'Home','post_name'=>'home','post_content'=>$content
        )));
        if ($en_id) { @pll_set_post_language($en_id, 'en'); }
    }

    if (!$lv_id) {
        $existing = get_page_by_path('sakums');
        $lv_id = $existing ? absint($existing->ID) : absint(wp_insert_post(array(
            'post_type'=>'page','post_status'=>'publish','post_title'=>'Sākums','post_name'=>'sakums','post_content'=>$content
        )));
        if ($lv_id) { @pll_set_post_language($lv_id, 'lv'); }
    }

    if ($en_id && $lv_id) {
        @pll_save_post_translations(array('en'=>$en_id, 'lv'=>$lv_id));
        update_option('show_on_front', 'page', false);
        update_option('page_on_front', $primary === 'lv' ? $lv_id : $en_id, false);
    }
}

/**
 * v4 no longer needs physical XML files in the Bedrock document root.
 * Requests are served through WordPress and cached under uploads/wpbbshop-feeds.
 */
function wpbbshop_v400_feed_request() {
    if (is_admin()) { return; }
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = (string) parse_url((string) $uri, PHP_URL_PATH);
    if (!preg_match('#/(kurpirkt|salidzini|ceno)\.xml/?$#i', $path, $match)) { return; }
    $feed = strtolower($match[1]);

    if (!function_exists('wpbbshop_compare_feed_enabled') || !wpbbshop_compare_feed_enabled($feed)) {
        status_header(404);
        nocache_headers();
        exit;
    }
    if (!class_exists('WooCommerce') || !function_exists('wpbbshop_compare_feed_generate')) {
        status_header(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'WooCommerce feed is not available.';
        exit;
    }

    $file = function_exists('wpbbshop_compare_feed_cache_file') ? wpbbshop_compare_feed_cache_file($feed) : '';
    $xml = '';
    if ($file && file_exists($file) && (time() - (int) @filemtime($file)) < 6 * HOUR_IN_SECONDS) {
        $xml = (string) @file_get_contents($file);
    }
    if ($xml === '') {
        $xml = wpbbshop_compare_feed_generate($feed);
    }
    if (!is_string($xml) || trim($xml) === '') {
        status_header(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Feed generation failed.';
        exit;
    }

    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('Content-Disposition: inline; filename="' . $feed . '.xml"');
    header('X-WPBB-Feed: dynamic-v4');
    header('X-Robots-Tag: noindex, follow', true);
    header('Cache-Control: public, max-age=900, stale-while-revalidate=3600');
    echo $xml;
    exit;
}
add_action('parse_request', 'wpbbshop_v400_feed_request', -100);

/**
 * Remove the obsolete v3 notice on every admin request. v4 feeds never require
 * writable XML files in the document root or theme-managed .htaccess changes.
 */
function wpbbshop_v400_clear_legacy_feed_notice() {
    delete_option('wpbbshop_v315_feed_publish_error');
}
add_action('after_setup_theme', 'wpbbshop_v400_clear_legacy_feed_notice', 1);
add_action('init', 'wpbbshop_v400_clear_legacy_feed_notice', 1);
add_action('admin_init', 'wpbbshop_v400_clear_legacy_feed_notice', 1);

function wpbbshop_v400_refresh_managed_pages() {
    if (function_exists('wpbbshop_v305_ensure_bilingual_pages')) {
        delete_option('wpbbshop_v305_pages');
        wpbbshop_v305_ensure_bilingual_pages();
    }

    if (function_exists('wpbbshop_v313_page_content')) {
        $pages = array(
            'kontakti'=>array('contact','lv'),
            'contact'=>array('contact','en'),
            'pirksanas-noteikumi'=>array('terms','lv'),
            'terms-and-conditions'=>array('terms','en'),
        );
        foreach ($pages as $slug=>$spec) {
            $page = get_page_by_path($slug, OBJECT, 'page');
            if (!$page instanceof WP_Post) { continue; }
            wp_update_post(array(
                'ID'=>$page->ID,
                'post_content'=>wpbbshop_v313_page_content($spec[0], $spec[1]),
            ));
        }
    }
}

function wpbbshop_v400_migrate() {
    if (!current_user_can('edit_theme_options')) { return; }
    if ((string) get_option('wpbbshop_v400_migration', '') === WPBBSHOP_V400_VERSION) {
        delete_option('wpbbshop_v315_feed_publish_error');
        return;
    }

    $theme_options = get_option('wpbbshop_theme_options', array());
    if (!is_array($theme_options)) { $theme_options = array(); }
    $theme_options['address'] = 'Bauskas 63 - 1a/k1, Riga, LV-1004';
    $theme_options['accent_color'] = '#178f55';
    $theme_options['enable_kurpirkt_feed'] = '1';
    $theme_options['enable_salidzini_feed'] = '1';
    $theme_options['enable_ceno_feed'] = '1';
    $theme_options['show_category_panel'] = '1';
    $theme_options['show_sale_badges'] = '1';
    $theme_options['hero_title'] = 'Everything for home, garden, build and DIY';
    $theme_options['hero_subtitle'] = 'A fast large-catalogue store for projects, professionals and everyday home improvement.';
    $theme_options['hero_button'] = 'Shop departments';
    $theme_options['footer_description'] = 'WP BB Home & Garden — tools, garden, building materials and home improvement with delivery across Latvia.';
    update_option('wpbbshop_theme_options', $theme_options, false);

    $primary = wpbbshop_v400_primary_language();
    update_option('wpbbshop_primary_language', $primary, false);
    wpbbshop_v400_set_primary_language($primary);
    wpbbshop_v400_backfill_demo_images();
    wpbbshop_v400_refresh_managed_pages();

    delete_option('wpbbshop_v315_feed_publish_error');
    update_option('wpbbshop_v315_feed_version', WPBBSHOP_V400_VERSION, false);

    if (function_exists('wpbbshop_compare_feed_ensure_cache_dir')) {
        wpbbshop_compare_feed_ensure_cache_dir();
    }
    if (function_exists('wpbbshop_compare_feed_register_rewrites')) {
        wpbbshop_compare_feed_register_rewrites();
        flush_rewrite_rules(false);
    }

    update_option('wpbbshop_v400_migration', WPBBSHOP_V400_VERSION, false);
}
add_action('admin_init', 'wpbbshop_v400_migrate', 2000);
add_action('after_switch_theme', 'wpbbshop_v400_migrate', 2000);

/** One Home & Garden admin destination instead of two competing demo pages. */
add_action('admin_menu', function() {
    remove_submenu_page('themes.php', 'wpbbshop-garden-demo-312');
    remove_submenu_page('themes.php', 'wpbbshop-green-demo');
    add_theme_page(
        'WP BB Home & Garden',
        'Home & Garden',
        'manage_options',
        'wpbbshop-platform',
        'wpbbshop_v400_admin_page'
    );
}, 999);

function wpbbshop_v400_admin_url($tab = 'demo', $args = array()) {
    return add_query_arg(array_merge(array('page'=>'wpbbshop-platform','tab'=>$tab), $args), admin_url('themes.php'));
}

function wpbbshop_v400_plugin_active_fragment($fragment) {
    $active = (array) get_option('active_plugins', array());
    foreach ($active as $plugin) {
        if (strpos((string) $plugin, $fragment) !== false) { return true; }
    }
    return false;
}

function wpbbshop_v400_count_demo_products() {
    if (!class_exists('WooCommerce')) { return 0; }
    global $wpdb;
    return (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID=pm.post_id
         WHERE pm.meta_key='_wpbbshop_demo_product' AND pm.meta_value='1'
           AND p.post_type='product' AND p.post_status NOT IN ('trash','auto-draft')"
    );
}

function wpbbshop_v400_status_data() {
    $product_count = 0;
    if (post_type_exists('product')) {
        $counts = wp_count_posts('product');
        $product_count = isset($counts->publish) ? (int) $counts->publish : 0;
    }
    $hpos = false;
    if (class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil')) {
        try { $hpos = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(); } catch (Throwable $e) {}
    }
    return array(
        'products'=>$product_count,
        'demoProducts'=>wpbbshop_v400_count_demo_products(),
        'language'=>wpbbshop_v400_primary_language(),
        'woo'=>class_exists('WooCommerce'),
        'polylang'=>function_exists('PLL'),
        'acf'=>function_exists('acf'),
        'bbuilder'=>wpbbshop_v400_plugin_active_fragment('wp-bbuilder'),
        'wooSupport'=>wpbbshop_v400_plugin_active_fragment('wp-theme-woo-support'),
        'updraft'=>wpbbshop_v400_plugin_active_fragment('updraftplus'),
        'acorn'=>class_exists('Roots\\Acorn\\Application') || function_exists('wp_bb_blade'),
        'objectCache'=>function_exists('wp_using_ext_object_cache') ? wp_using_ext_object_cache() : false,
        'hpos'=>$hpos,
        'feeds'=>array(
            'kurpirkt'=>function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('kurpirkt') : home_url('/?wpbbshop_compare_feed=kurpirkt'),
            'salidzini'=>function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('salidzini') : home_url('/?wpbbshop_compare_feed=salidzini'),
            'ceno'=>function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url('ceno') : home_url('/?wpbbshop_compare_feed=ceno'),
        ),
    );
}

add_action('rest_api_init', function() {
    register_rest_route('wpbb/v1', '/catalog-status', array(
        'methods'=>'GET',
        'callback'=>function(){ return rest_ensure_response(wpbbshop_v400_status_data()); },
        'permission_callback'=>function(){ return current_user_can('manage_options'); },
    ));
});

function wpbbshop_v400_blade_templates() {
    $roots = array(
        'Child theme'=>get_stylesheet_directory() . '/resources/views',
        'Parent theme'=>get_template_directory() . '/resources/views',
    );
    $out = array();
    foreach ($roots as $label=>$root) {
        if (!is_dir($root)) { continue; }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile() || substr($file->getFilename(), -10) !== '.blade.php') { continue; }
            $out[] = array('source'=>$label, 'path'=>str_replace($root . '/', '', $file->getPathname()));
        }
    }
    return $out;
}

function wpbbshop_v400_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'demo';
    if (!in_array($tab, array('demo','languages','feeds','templates','performance'), true)) { $tab = 'demo'; }
    $lang = wpbbshop_v400_current_language();
    $lv = $lang === 'lv';
    $notice = isset($_GET['wpbb_notice']) ? sanitize_text_field(wp_unslash($_GET['wpbb_notice'])) : '';
    $tabs = array(
        'demo'=>$lv ? 'Demo katalogs' : 'Demo catalogue',
        'languages'=>$lv ? 'Valodas' : 'Languages',
        'feeds'=>'XML feeds',
        'templates'=>'Laravel / Svelte',
        'performance'=>$lv ? 'Veiktspēja' : 'Performance',
    );
    ?>
    <div class="wrap wpbb-v400-admin">
      <div class="wpbb-v400-admin-hero">
        <div><span class="wpbb-v400-kicker">WP BB PLATFORM</span><h1>Home &amp; Garden</h1><p><?php echo esc_html($lv ? 'Viena vieta demo katalogam, valodām, XML plūsmām, Laravel/Blade un kataloga veiktspējai.' : 'One place for the demo catalogue, languages, XML feeds, Laravel/Blade and catalogue performance.'); ?></p></div>
        <div class="wpbb-v400-admin-brand"><span>WP</span><strong>BB</strong></div>
      </div>
      <?php if ($notice) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html($notice); ?></p></div><?php endif; ?>
      <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $slug=>$label) : ?><a class="nav-tab <?php echo $tab===$slug?'nav-tab-active':''; ?>" href="<?php echo esc_url(wpbbshop_v400_admin_url($slug)); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?>
      </nav>
      <div class="wpbb-v400-panel">
      <?php if ($tab === 'demo') :
          $count = wpbbshop_v400_count_demo_products(); ?>
        <h2><?php echo esc_html($lv ? '500 preču Home & Garden demo' : '500-product Home & Garden demo'); ?></h2>
        <p><?php echo esc_html($lv ? 'Atjauno vienu un to pašu HG-DEMO katalogu bez dublikātiem. Demo preces izmanto vieglus attālinātus nodaļu attēlus ar tēmas WebP rezerves variantu, tāpēc Media Library imports un Imagick nav vajadzīgi.' : 'Refresh the same HG-DEMO catalogue without duplicates. Demo products use lightweight remote department images with bundled WebP fallback, so Media Library imports and Imagick are not required.'); ?></p>
        <div class="wpbb-v400-metric"><strong><?php echo number_format_i18n($count); ?></strong><span><?php echo esc_html($lv ? 'demo preces šobrīd' : 'demo products currently'); ?></span></div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="wpbbshop_v400_seed">
          <?php wp_nonce_field('wpbbshop_v400_seed'); ?>
          <?php submit_button($lv ? 'Izveidot / atjaunot 500 demo preces' : 'Create / refresh 500 demo products', 'primary', 'submit', false); ?>
        </form>
        <form class="wpbb-v400-danger-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js($lv ? 'Dzēst tikai tēmas demo preces?' : 'Delete only theme demo products?'); ?>');">
          <input type="hidden" name="action" value="wpbbshop_v400_remove_demo">
          <?php wp_nonce_field('wpbbshop_v400_remove_demo'); ?>
          <?php submit_button($lv ? 'Dzēst demo katalogu' : 'Remove demo catalogue', 'secondary', 'submit', false); ?>
        </form>
      <?php elseif ($tab === 'languages') : ?>
        <h2><?php echo esc_html($lv ? 'Angļu un latviešu valoda' : 'English and Latvian'); ?></h2>
        <p><?php echo esc_html($lv ? 'Noklusējuma valodu vari pārslēgt jebkurā laikā. Tēmas publiskā saskarne izmanto aktīvo Polylang valodu.' : 'You can switch the default language at any time. The public theme UI follows the active Polylang language.'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="wpbbshop_v400_language">
          <?php wp_nonce_field('wpbbshop_v400_language'); ?>
          <label class="wpbb-v400-choice"><input type="radio" name="primary_language" value="en" <?php checked(wpbbshop_v400_primary_language(),'en'); ?>> English first</label>
          <label class="wpbb-v400-choice"><input type="radio" name="primary_language" value="lv" <?php checked(wpbbshop_v400_primary_language(),'lv'); ?>> Latviešu pirmā</label>
          <?php submit_button($lv ? 'Saglabāt valodu' : 'Save language'); ?>
        </form>
        <p class="description"><?php echo esc_html($lv ? 'Sarežģītai daudzvalodu produktu/variāciju noliktavas sinhronizācijai ieteicams oficiālais Polylang for WooCommerce papildinājums.' : 'For complex multilingual product/variation stock synchronisation, the official Polylang for WooCommerce add-on remains recommended.'); ?></p>
      <?php elseif ($tab === 'feeds') : ?>
        <?php
          $market = function_exists('wpbbshop_v408_resolved_market') ? wpbbshop_v408_resolved_market() : 'lv';
          $base_country = function_exists('wpbbshop_v408_base_country') ? wpbbshop_v408_base_country() : 'LV';
          $market_settings = function_exists('wpbbshop_v408_market_settings') ? wpbbshop_v408_market_settings() : array();
          $services = function_exists('wpbbshop_v408_services') ? wpbbshop_v408_services() : array();
        ?>
        <h2><?php echo esc_html($lv ? 'Cenu salīdzināšanas un iepirkšanās plūsmas' : 'Comparison & shopping feeds'); ?></h2>
        <p><?php echo esc_html($lv ? 'Tirgus ir neatkarīgs no vietnes valodas. Auto režīms izmanto WooCommerce bāzes valsti; vari paturēt latviešu valodu arī UK veikalam.' : 'Market selection is independent from site language. Auto mode follows the WooCommerce base country, so a UK shop can still keep Latvian content.'); ?></p>
        <div class="wpbb-v400-status-row"><span><?php echo esc_html($lv ? 'Aktīvais tirgus' : 'Active market'); ?> · Woo <?php echo esc_html($base_country); ?></span><strong><?php echo esc_html($market === 'gb' ? 'UNITED KINGDOM' : 'LATVIA'); ?></strong></div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:18px">
          <input type="hidden" name="action" value="wpbbshop_v408_market_feeds">
          <?php wp_nonce_field('wpbbshop_v408_market_feeds'); ?>
          <div class="wpbb-v400-feed-market-grid">
            <section class="wpbb-v400-feed-market-card">
              <h3><?php echo esc_html($lv ? 'Tirgus režīms' : 'Market mode'); ?></h3>
              <label class="wpbb-v400-choice"><input type="radio" name="wpbbshop_market[market_mode]" value="auto" <?php checked(isset($market_settings['market_mode'])?$market_settings['market_mode']:'auto','auto'); ?>> <?php echo esc_html($lv ? 'Auto — WooCommerce bāzes valsts' : 'Auto — WooCommerce base country'); ?></label>
              <label class="wpbb-v400-choice"><input type="radio" name="wpbbshop_market[market_mode]" value="lv" <?php checked(isset($market_settings['market_mode'])?$market_settings['market_mode']:'auto','lv'); ?>> Latvia</label>
              <label class="wpbb-v400-choice"><input type="radio" name="wpbbshop_market[market_mode]" value="gb" <?php checked(isset($market_settings['market_mode'])?$market_settings['market_mode']:'auto','gb'); ?>> United Kingdom</label>
            </section>
            <section class="wpbb-v400-feed-market-card">
              <h3><?php echo esc_html($lv ? 'UK piegādes noklusējumi' : 'UK delivery defaults'); ?></h3>
              <label><?php echo esc_html($lv ? 'Piegādes cena' : 'Delivery cost'); ?><input type="text" name="wpbbshop_market[uk_delivery_cost]" value="<?php echo esc_attr(isset($market_settings['uk_delivery_cost'])?$market_settings['uk_delivery_cost']:'4.95'); ?>"></label>
              <label><?php echo esc_html($lv ? 'Piegādes dienas' : 'Delivery days'); ?><input type="number" min="0" name="wpbbshop_market[uk_delivery_days]" value="<?php echo esc_attr(isset($market_settings['uk_delivery_days'])?$market_settings['uk_delivery_days']:'3'); ?>"></label>
              <label>idealo delivery provider<input type="text" name="wpbbshop_market[idealo_delivery_provider]" value="<?php echo esc_attr(isset($market_settings['idealo_delivery_provider'])?$market_settings['idealo_delivery_provider']:'dpd'); ?>" placeholder="dpd"></label>
            </section>
          </div>
          <h3 style="margin-top:22px">Latvia</h3>
          <div class="wpbb-v400-feed-list">
          <?php foreach (array('kurpirkt','salidzini','ceno') as $feed) :
              $meta = isset($services['lv'][$feed]) ? $services['lv'][$feed] : array('name'=>ucfirst($feed),'format'=>'XML');
              $enabled = function_exists('wpbbshop_compare_feed_enabled') && wpbbshop_compare_feed_enabled($feed);
              $url = function_exists('wpbbshop_compare_feed_url') ? wpbbshop_compare_feed_url($feed) : home_url('/?wpbbshop_compare_feed='.$feed); ?>
            <div class="<?php echo $market==='lv'?'is-active-market':'is-inactive-market'; ?>"><strong><?php echo esc_html($meta['name']); ?></strong><span class="wpbb-v400-feed-state"><?php echo esc_html($enabled ? 'ON' : 'OFF'); ?></span><code><?php echo esc_html($url); ?></code><a class="button button-small" target="_blank" href="<?php echo esc_url($url); ?>">Open</a></div>
          <?php endforeach; ?>
          </div>
          <h3 style="margin-top:22px">United Kingdom</h3>
          <div class="wpbb-v400-feed-list wpbb-v408-uk-feeds">
          <?php foreach (array('google','pricerunner','pricespy','idealo','kelkoo') as $feed) :
              $meta = isset($services['gb'][$feed]) ? $services['gb'][$feed] : array('name'=>ucfirst($feed),'format'=>'XML');
              $checked = !empty($market_settings['enable_'.$feed]);
              $enabled = function_exists('wpbbshop_v408_uk_service_enabled') && wpbbshop_v408_uk_service_enabled($feed);
              $url = function_exists('wpbbshop_v408_market_feed_url') ? wpbbshop_v408_market_feed_url($feed) : ''; ?>
            <div class="<?php echo $market==='gb'?'is-active-market':'is-inactive-market'; ?>"><label class="wpbb-v408-feed-toggle"><input type="checkbox" name="wpbbshop_market[enable_<?php echo esc_attr($feed); ?>]" value="1" <?php checked($checked); ?>><strong><?php echo esc_html($meta['name']); ?></strong></label><span class="wpbb-v400-feed-state"><?php echo esc_html($enabled ? 'ON' : 'OFF'); ?></span><code><?php echo esc_html($url); ?></code><a class="button button-small" target="_blank" href="<?php echo esc_url($url); ?>">Open <?php echo esc_html($meta['format']); ?></a></div>
          <?php endforeach; ?>
          </div>
          <?php submit_button($lv ? 'Saglabāt tirgu un plūsmas' : 'Save market & feeds'); ?>
        </form>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="wpbbshop_v408_regenerate_market_feeds">
          <?php wp_nonce_field('wpbbshop_v408_regenerate_market_feeds'); ?>
          <?php submit_button($lv ? 'Atjaunot aktīvā tirgus plūsmas' : 'Regenerate active market feeds', 'secondary'); ?>
        </form>
        <p class="description"><?php echo esc_html($lv ? 'Demo preces paliek izslēgtas. Google/Kelkoo izmanto Google Merchant XML; PriceSpy izmanto Google/Prisjakt XML; PriceRunner ir TSV; idealo ir CSV. Valoda un tirgus ir neatkarīgi.' : 'Demo products stay excluded. Google/Kelkoo use Google Merchant XML; PriceSpy uses Google/Prisjakt XML; PriceRunner uses TSV; idealo uses CSV. Language and market remain independent.'); ?></p>
        <p class="description"><?php echo esc_html($lv ? 'Google gadījumā oficiālo WooCommerce Google Listings & Ads papildinājumu vari izmantot API sinhronizācijai; tēmas XML paliek kā faila plūsmas variants. PriceRunner/idealo partnera konts var prasīt konkrētu lauku kartējumu pirms produkcijas palaišanas.' : 'For Google you can still use the official WooCommerce Google Listings & Ads extension for API sync; the theme XML remains available as a file-feed option. PriceRunner/idealo onboarding can require account-specific field mapping before production use.'); ?></p>
      <?php elseif ($tab === 'templates') :
          $templates = wpbbshop_v400_blade_templates(); ?>
        <h2>Laravel / Acorn / Blade</h2>
        <div class="wpbb-v400-status-row"><span>Acorn / <code>wp_bb_blade()</code></span><strong><?php echo function_exists('wp_bb_blade') ? 'READY' : 'PHP FALLBACK'; ?></strong></div>
        <p><?php echo esc_html($lv ? 'Child tēma var pārdefinēt Blade skatus resources/views mapē. Esošie PHP šabloni paliek kā drošs fallback.' : 'The child theme can override Blade views under resources/views. Existing PHP templates remain as a safe fallback.'); ?></p>
        <table class="widefat striped"><thead><tr><th>Source</th><th>Blade template</th></tr></thead><tbody>
        <?php foreach ($templates as $tpl) : ?><tr><td><?php echo esc_html($tpl['source']); ?></td><td><code><?php echo esc_html($tpl['path']); ?></code></td></tr><?php endforeach; ?>
        </tbody></table>
        <h2 style="margin-top:28px">Svelte catalogue status island</h2>
        <p><?php echo esc_html($lv ? 'Iekļauts Svelte avota komponents un aktīvs bezatkarību administrācijas statusa widget ar to pašu REST API.' : 'A Svelte source component is included, plus an active dependency-free admin status widget using the same REST API.'); ?></p>
        <div id="wpbbshop-svelte-status" class="wpbb-v400-svelte" data-loading="<?php echo esc_attr($lv ? 'Ielādē statusu…' : 'Loading status…'); ?>"></div>
        <p><code>resources/svelte/CatalogStatus.svelte</code></p>
      <?php else :
          $status = wpbbshop_v400_status_data(); ?>
        <h2><?php echo esc_html($lv ? 'Kataloga un platformas statuss' : 'Catalogue and platform status'); ?></h2>
        <div class="wpbb-v400-status-grid">
          <?php
          $cards = array(
            array('Products', number_format_i18n($status['products'])),
            array('Demo products', number_format_i18n($status['demoProducts'])),
            array('Object cache', $status['objectCache'] ? 'ON' : 'OFF'),
            array('Woo HPOS', $status['hpos'] ? 'ON' : 'OFF'),
            array('Acorn / Blade', $status['acorn'] ? 'READY' : 'OPTIONAL'),
            array('PHP', PHP_VERSION),
          );
          foreach ($cards as $card) : ?><div><span><?php echo esc_html($card[0]); ?></span><strong><?php echo esc_html($card[1]); ?></strong></div><?php endforeach; ?>
        </div>
        <h3>Compatibility</h3>
        <ul class="wpbb-v400-checks">
          <li>WooCommerce: <?php echo $status['woo']?'✓':'—'; ?></li>
          <li>Polylang: <?php echo $status['polylang']?'✓':'—'; ?></li>
          <li>ACF Pro/API: <?php echo $status['acf']?'✓':'—'; ?></li>
          <li>WP BBuilder: <?php echo $status['bbuilder']?'✓':'—'; ?></li>
          <li>Woo Support: <?php echo $status['wooSupport']?'✓':'—'; ?></li>
          <li>UpdraftPlus: <?php echo $status['updraft']?'✓':'—'; ?></li>
        </ul>
      <?php endif; ?>
      </div>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function() {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'wpbbshop-platform') { return; }
    wp_enqueue_style('wpbbshop-v400-admin', get_stylesheet_directory_uri().'/assets/css/v400-admin.css', array(), WPBBSHOP_V400_VERSION);
    wp_enqueue_script('wpbbshop-v400-status', get_stylesheet_directory_uri().'/assets/js/v400-admin-status.js', array(), WPBBSHOP_V400_VERSION, true);
    wp_localize_script('wpbbshop-v400-status', 'WPBBShopV400', array(
        'restUrl'=>esc_url_raw(rest_url('wpbb/v1/catalog-status')),
        'nonce'=>wp_create_nonce('wp_rest'),
        'lang'=>wpbbshop_v400_current_language(),
    ));
}, 999);

add_action('admin_post_wpbbshop_v400_seed', function() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }
    check_admin_referer('wpbbshop_v400_seed');
    $result = function_exists('wpbbshop_megastore_seed_500_312') ? wpbbshop_megastore_seed_500_312() : new WP_Error('missing','Demo engine missing.');
    wpbbshop_v400_backfill_demo_images();
    $message = is_wp_error($result)
        ? $result->get_error_message()
        : sprintf('Demo catalogue ready: %d created, %d updated.', absint($result['created']), absint($result['updated']));
    wp_safe_redirect(wpbbshop_v400_admin_url('demo', array('wpbb_notice'=>$message)));
    exit;
});

add_action('admin_post_wpbbshop_v400_remove_demo', function() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }
    check_admin_referer('wpbbshop_v400_remove_demo');
    $deleted = function_exists('wpbbshop_green_remove_demo_catalog') ? wpbbshop_green_remove_demo_catalog() : 0;
    wp_safe_redirect(wpbbshop_v400_admin_url('demo', array('wpbb_notice'=>sprintf('Removed %d demo products.', absint($deleted)))));
    exit;
});

add_action('admin_post_wpbbshop_v400_language', function() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }
    check_admin_referer('wpbbshop_v400_language');
    $lang = isset($_POST['primary_language']) ? sanitize_key(wp_unslash($_POST['primary_language'])) : 'en';
    $lang = wpbbshop_v400_set_primary_language($lang);
    wp_safe_redirect(wpbbshop_v400_admin_url('languages', array('wpbb_notice'=>$lang === 'lv' ? 'Latviešu is now the primary language.' : 'English is now the primary language.')));
    exit;
});

add_action('admin_post_wpbbshop_v400_feeds', function() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied.'); }
    check_admin_referer('wpbbshop_v400_feeds');
    if (function_exists('wpbbshop_compare_feed_invalidate')) { wpbbshop_compare_feed_invalidate(); }
    if (function_exists('wpbbshop_compare_feed_refresh_all')) { wpbbshop_compare_feed_refresh_all(); }
    delete_option('wpbbshop_v315_feed_publish_error');
    wp_safe_redirect(wpbbshop_v400_admin_url('feeds', array('wpbb_notice'=>'XML feed cache regenerated.')));
    exit;
});

/** Home page v4 *******************************************************/
function wpbbshop_v400_home() {
    $is_en = wpbbshop_v400_current_language() !== 'lv';
    $is_uk = function_exists('wpbbshop_v414_is_uk_store') && wpbbshop_v414_is_uk_store();
    $store_address = function_exists('wpbbshop_v414_store_address') ? wpbbshop_v414_store_address() : 'Bauskas 63 - 1a/k1, Riga, LV-1004';
    $store_city = function_exists('wpbbshop_v414_store_city') ? wpbbshop_v414_store_city() : 'Riga';
    $delivery_label = function_exists('wpbbshop_v414_delivery_label') ? wpbbshop_v414_delivery_label($is_en) : ($is_en ? 'Delivery across Latvia' : 'Piegāde visā Latvijā');
    $pickup_label = function_exists('wpbbshop_v414_pickup_label') ? wpbbshop_v414_pickup_label($is_en) : ($is_en ? 'Pickup in Riga' : 'Saņemšana Rīgā');
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $terms = function_exists('wpbbshop_megastore_department_terms_312') ? wpbbshop_megastore_department_terms_312() : array();
    $hero_image = get_stylesheet_directory_uri() . '/assets/img/hero-mower.jpg';
    $data = array(
        'is_en'=>$is_en,
        'is_uk'=>$is_uk,
        'shop'=>$shop,
        'terms'=>$terms,
        'hero_image'=>$hero_image,
        'address'=>$store_address,
        'store_city'=>$store_city,
        'delivery_label'=>$delivery_label,
        'pickup_label'=>$pickup_label,
        'featured_html'=>function_exists('wpbbshop_megastore_products_section_312') ? wpbbshop_megastore_products_section_312('featured', $is_en?'Popular products':'Populārākās preces', 12, $is_en?'Top picks':'Ieteikumi') : '',
        'sale_html'=>function_exists('wpbbshop_megastore_products_section_312') ? wpbbshop_megastore_products_section_312('sale', $is_en?'Offers & savings':'Akcijas un ietaupījumi', 8, $is_en?'Offers':'Akcijas') : '',
        'recent_html'=>function_exists('wpbbshop_megastore_products_section_312') ? wpbbshop_megastore_products_section_312('recent', $is_en?'New arrivals':'Jaunākās preces', 8, $is_en?'New':'Jaunumi') : '',
    );

    if (function_exists('wp_bb_blade')) {
        try {
            $rendered = wp_bb_blade('home-garden.home', $data, false);
            if (is_string($rendered) && trim($rendered) !== '') {
                // The shortcode block is formatted after rendering; keep layout
                // whitespace from becoming wpautop paragraphs and grid-cell breaks.
                return str_replace(array("\r", "\n"), '', $rendered);
            }
        } catch (Throwable $e) {}
    }

    ob_start();
    ?>
    <main class="wpbb-v400-home">
      <section class="wpbb-v400-shell wpbb-v400-hero">
        <div class="wpbb-v400-hero-copy">
          <span class="wpbb-v400-eyebrow"><?php echo esc_html($is_en ? 'HOME • GARDEN • BUILD • DIY' : 'MĀJA • DĀRZS • BŪVE • DIY'); ?></span>
          <h1><?php echo esc_html($is_en ? 'Big-project range. Fast everyday shopping.' : 'Plašs sortiments lieliem projektiem. Ātra ikdienas iepirkšanās.'); ?></h1>
          <p><?php echo esc_html($is_en ? 'Department-first navigation, quick SKU search and a WooCommerce architecture prepared for 40k+ products.' : 'Nodaļu navigācija, ātra SKU meklēšana un WooCommerce arhitektūra, kas sagatavota 40k+ precēm.'); ?></p>
          <div class="wpbb-v400-hero-actions"><a class="wpbb-v400-btn is-primary" href="<?php echo esc_url($shop); ?>"><?php echo esc_html($is_en?'Shop catalogue':'Skatīt katalogu'); ?></a><a class="wpbb-v400-btn is-ghost" href="#departments"><?php echo esc_html($is_en?'Browse departments':'Skatīt nodaļas'); ?></a></div>
          <div class="wpbb-v400-trust"><span>40k+ <?php echo esc_html($is_en?'catalogue ready':'kataloga gatavība'); ?></span><span><?php echo esc_html($delivery_label); ?></span><span><?php echo esc_html($store_city); ?></span></div>
        </div>
        <div class="wpbb-v400-hero-media"><img src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($is_en?'Garden machinery and home improvement':'Dārza tehnika un mājas labiekārtošana'); ?>" fetchpriority="high" decoding="async"><div class="wpbb-v400-hero-card"><strong><?php echo esc_html($is_en?'Store pickup':'Saņemšana veikalā'); ?></strong><span><?php echo esc_html($store_address); ?></span></div></div>
      </section>

      <section id="departments" class="wpbb-v400-shell wpbb-v400-departments">
        <div class="wpbb-v400-section-head"><div><span><?php echo esc_html($is_en?'20 departments':'20 nodaļas'); ?></span><h2><?php echo esc_html($is_en?'Everything for the job, organised clearly':'Viss darbam un projektam, sakārtots saprotami'); ?></h2></div><a href="<?php echo esc_url($shop); ?>"><?php echo esc_html($is_en?'Full catalogue':'Pilns katalogs'); ?> →</a></div>
        <div class="wpbb-v400-dept-grid">
        <?php foreach ($terms as $item) : ?>
          <a class="wpbb-v400-dept" href="<?php echo esc_url($item['link']); ?>"><span class="wpbb-v400-dept-icon"><?php echo function_exists('wpbbshop_category_icon_html') ? wpbbshop_category_icon_html($item['term'], true) : '•'; ?></span><strong><?php echo esc_html($item['label']); ?></strong><small><?php echo esc_html(number_format_i18n((int)$item['term']->count).' '.($is_en?'products':'preces')); ?></small></a>
        <?php endforeach; ?>
        </div>
      </section>

      <section class="wpbb-v400-shell wpbb-v400-projects">
        <article><span>01</span><h3><?php echo esc_html($is_en?'Garden & outdoor':'Dārzs un āra vide'); ?></h3><p><?php echo esc_html($is_en?'Machinery, irrigation, furniture, plants and seasonal care.':'Tehnika, laistīšana, mēbeles, augi un sezonas kopšana.'); ?></p></article>
        <article><span>02</span><h3><?php echo esc_html($is_en?'Build & renovate':'Būvē un atjauno'); ?></h3><p><?php echo esc_html($is_en?'Materials, tools, paint, plumbing, heating and electrical.':'Materiāli, instrumenti, krāsas, santehnika, apkure un elektrība.'); ?></p></article>
        <article><span>03</span><h3><?php echo esc_html($is_en?'Home & workshop':'Māja un darbnīca'); ?></h3><p><?php echo esc_html($is_en?'Storage, cleaning, safety and practical everyday equipment.':'Uzglabāšana, uzkopšana, drošība un praktisks ikdienas aprīkojums.'); ?></p></article>
      </section>

      <section class="wpbb-v400-shell wpbb-v400-products"><?php echo $data['featured_html']; ?></section>
      <section class="wpbb-v400-shell wpbb-v400-products"><?php echo $data['sale_html']; ?></section>
      <section class="wpbb-v400-shell wpbb-v400-products"><?php echo $data['recent_html']; ?></section>

      <section class="wpbb-v400-shell wpbb-v400-services">
        <div><strong><?php echo esc_html($is_en?'Fast search':'Ātra meklēšana'); ?></strong><span><?php echo esc_html($is_en?'Name, brand, SKU and barcode.':'Nosaukums, zīmols, SKU un svītrkods.'); ?></span></div>
        <div><strong><?php echo esc_html($is_en?'Real stock':'Reāls atlikums'); ?></strong><span><?php echo esc_html($is_en?'WooCommerce stock and HPOS-ready commerce.':'WooCommerce noliktava un HPOS gatava komercija.'); ?></span></div>
        <div><strong><?php echo esc_html($is_en?'Comparison feeds':'Cenu salīdzināšana'); ?></strong><span><?php echo esc_html(function_exists('wpbbshop_v408_active_service_names') ? implode(' • ', wpbbshop_v408_active_service_names()) : 'KurPirkt.lv • Salidzini.lv • Ceno.lv'); ?></span></div>
        <div><strong><?php echo esc_html($pickup_label); ?></strong><span><?php echo esc_html($store_address); ?></span></div>
      </section>
    </main>
    <?php
    return str_replace(array("\r", "\n"), '', ob_get_clean());
}
remove_shortcode('wpbbshop_home');
add_shortcode('wpbbshop_home', 'wpbbshop_v400_home');

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('wpbbshop-v400', get_stylesheet_directory_uri().'/assets/css/v400.css', array('wpbbshop-theme'), WPBBSHOP_V400_VERSION);
}, 999);
