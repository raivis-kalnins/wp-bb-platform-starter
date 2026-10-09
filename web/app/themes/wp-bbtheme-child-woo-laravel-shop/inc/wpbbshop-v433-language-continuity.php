<?php
/**
 * v4.0.33: request-scoped EN/LV navigation for the existing Polylang storefront.
 * No product cloning, stored-menu rewrites, preference cookies or DB migrations.
 * URL language wins; background storefront requests carry their own language.
 */
defined('ABSPATH') || exit;

function wpbbshop_v433_root() {
    return rtrim((string) get_option('home'), '/');
}

/** Same-site URL parser. Never rewrite assets, external hosts, ports or schemes. */
function wpbbshop_v433_parts($url) {
    if (!is_string($url) || $url === '' || $url[0] === '#' || preg_match('/[\x00-\x20\\\\]/', $url)) return false;
    $home = wp_parse_url(wpbbshop_v433_root());
    if (!$home || empty($home['host'])) return false;
    if (strpos($url, '//') === 0) $url = ($home['scheme'] ?? 'https') . ':' . $url;
    if ($url[0] === '/') {
        $url = ($home['scheme'] ?? 'https') . '://' . $home['host'] . (isset($home['port']) ? ':' . $home['port'] : '') . $url;
    }
    $parts = wp_parse_url($url);
    if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)
        || strtolower($parts['host'] ?? '') !== strtolower($home['host'])
        || isset($parts['user']) || isset($parts['pass'])) return false;
    $port = $parts['port'] ?? (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
    $home_port = $home['port'] ?? (($home['scheme'] ?? '') === 'https' ? 443 : 80);
    // Allow the same site's old HTTP links when migrating to HTTPS, not arbitrary ports.
    if ($port !== $home_port && !(in_array($port, array(80,443), true) && in_array($home_port, array(80,443), true))) return false;
    $base = rtrim($home['path'] ?? '', '/');
    $path = $parts['path'] ?? '/';
    if ($base && $path !== $base && strpos($path, $base . '/') !== 0) return false;
    $relative = ltrim(substr($path, strlen($base)), '/');
    if (preg_match('#(?:^|/)(?:\.\.|%2e%2e)(?:/|$)#i', $relative)) return false;
    $relative = preg_replace('#^index\.php/?#', '', $relative);
    $lang = '';
    if (preg_match('#^(en|lv)(?:/|$)#', $relative, $m)) {
        $lang = $m[1];
        $relative = ltrim(substr($relative, strlen($lang)), '/');
    }
    $relative = preg_replace('#^index\.php/?#', '', $relative);
    $parts['relative'] = trim($relative, '/');
    $parts['language'] = $lang;
    return $parts;
}

function wpbbshop_v433_shared_bridge() {
    return !function_exists('wpbbshop_polylang_woo_official_active_312') || !wpbbshop_polylang_woo_official_active_312();
}

function wpbbshop_v433_ajax_action() {
    $action = $_POST['action'] ?? ($_GET['action'] ?? '');
    return is_string($action) && ((strpos($action, 'wpbbshop_') === 0 && strpos($action, 'wpbbshop_admin_') !== 0)
        || strpos($action, 'wp_theme_woo_') === 0
        || in_array($action, array('iws_filter_products', 'iws_product_taxonomy_archive', 'wp_ajax_search'), true));
}

function wpbbshop_v433_is_frontend() {
    if ((defined('WP_CLI') && WP_CLI) || (defined('DOING_CRON') && DOING_CRON)
        || (defined('REST_REQUEST') && REST_REQUEST)) return false;
    if (!is_admin()) return true;
    return function_exists('wp_doing_ajax') && wp_doing_ajax() && wpbbshop_v433_ajax_action();
}

function wpbbshop_v433_current_language() {
    if (wpbbshop_v433_is_frontend()) {
        $request = wpbbshop_v433_parts((string) ($_SERVER['REQUEST_URI'] ?? ''));
        if ($request && $request['language']) return $request['language'];
        foreach (array($_POST['lang'] ?? '', $_GET['lang'] ?? '') as $value) {
            if (is_string($value) && in_array($value, array('en','lv'), true)) return $value;
        }
        // Only asynchronous requests may inherit a referring page's language.
        // Ordinary English URLs never inherit a stale Latvian cookie/referrer.
        if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || isset($_GET['wc-ajax'])) {
            $ref = wpbbshop_v433_parts((string) ($_SERVER['HTTP_REFERER'] ?? ''));
            if ($ref && !preg_match('#^(?:wp/)?wp-admin(?:/|$)#', $ref['relative'])) {
                if ($ref['language']) return $ref['language'];
                $query = array(); parse_str($ref['query'] ?? '', $query);
                if (isset($query['lang']) && in_array($query['lang'], array('en','lv'), true)) return $query['lang'];
            }
        }
    }
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if (in_array($lang, array('en','lv'), true)) return $lang;
    }
    $options = get_option('polylang', array());
    return isset($options['default_lang']) && $options['default_lang'] === 'lv' ? 'lv' : 'en';
}

function wpbbshop_v433_resolving() {
    return !empty($GLOBALS['wpbbshop_v433_resolving']);
}

/** Native permalinks are resolved without re-entering storefront link filters. */
function wpbbshop_v433_native_link($id) {
    $before = $GLOBALS['wpbbshop_v433_resolving'] ?? false;
    $GLOBALS['wpbbshop_v433_resolving'] = true;
    try { return get_permalink($id); }
    finally { $GLOBALS['wpbbshop_v433_resolving'] = $before; }
}

function wpbbshop_v433_home_url($lang = '') {
    $lang = in_array($lang, array('en','lv'), true) ? $lang : wpbbshop_v433_current_language();
    if (function_exists('pll_home_url')) {
        $url = pll_home_url($lang);
        if (is_string($url) && $url !== '') return $url;
    }
    $options = get_option('polylang', array());
    $default = $options['default_lang'] ?? 'en';
    return wpbbshop_v433_root() . '/' . (($lang !== $default || empty($options['hide_default'])) ? $lang . '/' : '');
}

/** Prefix only a known storefront URL; query strings/fragments are not path text. */
function wpbbshop_v433_prefix($url, $lang) {
    $parts = wpbbshop_v433_parts($url);
    if (!$parts) return $url;
    $options = get_option('polylang', array());
    $default = $options['default_lang'] ?? 'en';
    $prefix = ($lang !== $default || empty($options['hide_default'])) ? $lang . '/' : '';
    $path = $parts['relative'];
    $result = wpbbshop_v433_root() . '/' . $prefix . ($path !== '' ? $path . '/' : '');
    if (!empty($parts['query'])) {
        // Preserve order, repeated arguments, signatures and WooCommerce nonces.
        $query = preg_replace('/(^|&)lang=[^&]*(?=&|$)/', '$1lang=' . rawurlencode($lang), $parts['query']);
        $result .= '?' . $query;
    }
    if (isset($parts['fragment'])) $result .= '#' . $parts['fragment'];
    return $result;
}

function wpbbshop_v433_product_url($id, $lang = '', $fallback = '') {
    $lang = in_array($lang, array('en','lv'), true) ? $lang : wpbbshop_v433_current_language();
    $translated = function_exists('pll_get_post') ? absint(pll_get_post($id, $lang)) : 0;
    if ($translated && get_post_status($translated) === 'publish') $id = $translated;
    $url = wpbbshop_v433_native_link($id);
    return wpbbshop_v433_shared_bridge() ? wpbbshop_v433_prefix($url ?: $fallback, $lang) : ($url ?: $fallback);
}

/** Known legacy page aliases; the page must exist and be published. */
function wpbbshop_v433_page_alias($slug, $lang) {
    $pairs = array(
        array('home', 'sakums'), array('about-us', 'par-mums'), array('contact', 'kontakti'),
        array('delivery-payment', 'piegade-un-apmaksa'), array('returns-warranty', 'atgriesana-un-garantija'),
        array('terms-conditions', 'pirksanas-noteikumi'), array('terms-and-conditions', 'pirksanas-noteikumi'),
        array('blog', 'blogs'), array('privacy-policy', 'privacy-policy-lv'),
    );
    foreach ($pairs as $pair) {
        if (in_array($slug, $pair, true)) {
            $page = get_page_by_path($pair[$lang === 'lv' ? 1 : 0], OBJECT, 'page');
            if ($page && $page->post_status === 'publish') return $page;
        }
    }
    return null;
}

/** Only the existing commerce/shell pages may fall back to a shared record. */
function wpbbshop_v433_shared_pages() {
    if (!wpbbshop_v433_shared_bridge()) return array();
    static $pages = null;
    if ($pages !== null) return $pages;
    $pages = array();
    foreach (array('shop','cart','checkout','myaccount') as $key) {
        $id = absint(get_option('woocommerce_' . $key . '_page_id'));
        $page = $id ? get_post($id) : null;
        if ($page && $page->post_status === 'publish') $pages[trim(get_page_uri($page), '/')] = $id;
    }
    foreach (array('request-a-quote','track-your-order','b2b-portal','about-us','par-mums') as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page && $page->post_status === 'publish') $pages[trim(get_page_uri($page), '/')] = (int) $page->ID;
    }
    return $pages;
}

/** Translate a navigation target, not arbitrary URLs or the visitor's preference. */
function wpbbshop_v433_url($url, $lang = '') {
    if (wpbbshop_v433_resolving()) return $url;
    $lang = in_array($lang, array('en','lv'), true) ? $lang : wpbbshop_v433_current_language();
    $parts = wpbbshop_v433_parts($url);
    if (!$parts) return $url;
    $path = $parts['relative'];
    if (preg_match('#^(?:(?:wp/)?wp-(?:admin|login\.php|json|cron\.php)|app|wp-content|wp-includes|feed|sitemap)(?:/|$|\.)#i', $path)
        || preg_match('/\.(?:xml|json|pdf|zip|gz|jpe?g|png|webp|svg|css|js|woff2?)(?:$)/i', $path)) return $url;
    $query = preg_replace('/(^|&)lang=[^&]*(?=&|$)/', '$1lang=' . rawurlencode($lang), $parts['query'] ?? '');
    $suffix = ($query !== '' ? '?' . $query : '') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    if ($path === '' || in_array($path, array('home','sakums'), true)) {
        $home = wpbbshop_v433_home_url($lang);
        return $suffix ? wpbbshop_v433_prefix($home . $suffix, $lang) : $home;
    }
    // Both translated products and the deliberately shared demo inventory.
    $permalinks = get_option('woocommerce_permalinks', array());
    $product_base = trim($permalinks['product_base'] ?? 'product', '/');
    $category_base = trim($permalinks['category_base'] ?? 'product-category', '/');
    $tag_base = trim($permalinks['tag_base'] ?? 'product-tag', '/');
    foreach (array($category_base ?: 'product-category'=>'product_cat', $tag_base ?: 'product-tag'=>'product_tag') as $base=>$taxonomy) {
        if (strpos($path, $base . '/') !== 0) continue;
        $tail = substr($path, strlen($base)+1);
        $pagination = '';
        if (preg_match('#(/page/[0-9]+)$#', $tail, $m)) { $pagination=$m[1]; $tail=substr($tail,0,-strlen($pagination)); }
        $term = get_term_by('slug', rawurldecode(basename($tail)), $taxonomy);
        if ($term && !is_wp_error($term)) {
            $translated = function_exists('pll_get_term') ? absint(pll_get_term($term->term_id, $lang)) : 0;
            $before=$GLOBALS['wpbbshop_v433_resolving'] ?? false; $GLOBALS['wpbbshop_v433_resolving']=true;
            try { $link = get_term_link($translated ?: $term->term_id, $taxonomy); }
            finally { $GLOBALS['wpbbshop_v433_resolving']=$before; }
            if (!is_wp_error($link)) return wpbbshop_v433_prefix(rtrim($link,'/') . $pagination . '/' . $suffix, $lang);
        }
        return wpbbshop_v433_prefix($url, $lang);
    }
    if ($product_base !== '' && strpos($product_base, '%') === false && strpos($path, $product_base . '/') === 0) {
        $product = get_page_by_path(substr($path,strlen($product_base)+1), OBJECT, 'product');
        if ($product && $product->post_status === 'publish') return wpbbshop_v433_product_url($product->ID, $lang) . $suffix;
        return wpbbshop_v433_prefix($url, $lang);
    }
    $page = wpbbshop_v433_page_alias($path, $lang);
    if (!$page) $page = get_page_by_path($path, OBJECT, array('page','post'));
    if ($page && $page->post_status === 'publish') {
        $id = function_exists('pll_get_post') ? absint(pll_get_post($page->ID, $lang)) : 0;
        if ($id && get_post_status($id) === 'publish') return wpbbshop_v433_native_link($id) . $suffix;
        if (!function_exists('pll_get_post_language') || pll_get_post_language($page->ID, 'slug') === $lang) return wpbbshop_v433_native_link($page->ID) . $suffix;
        // Existing bilingual shell pages are reused; arbitrary articles are not.
        if (in_array((int)$page->ID, wpbbshop_v433_shared_pages(), true)) return wpbbshop_v433_prefix(wpbbshop_v433_native_link($page->ID) . $suffix, $lang);
    }
    foreach (wpbbshop_v433_shared_pages() as $slug=>$id) {
        if ($path === $slug || strpos($path, $slug . '/') === 0) return wpbbshop_v433_prefix($url, $lang);
    }
    return $url;
}

/** Fix saved custom-menu URLs at render time; never rewrite language switchers. */
function wpbbshop_v433_menu_items($items) {
    if (!wpbbshop_v433_is_frontend() || !is_array($items)) return $items;
    foreach ($items as $item) {
        if (!is_object($item) || empty($item->url)) continue;
        $classes = implode(' ', (array) ($item->classes ?? array()));
        if (preg_match('/(?:lang-item|language-switch|pll-parent-menu-item)/', $classes)) continue;
        $item->url = wpbbshop_v433_url($item->url);
    }
    return $items;
}
add_filter('wp_nav_menu_objects', 'wpbbshop_v433_menu_items', 20000);

/** Also cover shortcode-generated header/logo/footer/product/search links. */
function wpbbshop_v433_html_links($html) {
    if (!wpbbshop_v433_is_frontend() || !is_string($html) || (strpos($html, 'href') === false && strpos($html, '<form') === false) || !class_exists('WP_HTML_Tag_Processor')) return $html;
    $processor = new WP_HTML_Tag_Processor($html);
    while ($processor->next_tag()) {
        $tag = $processor->get_tag();
        $classes = (string) $processor->get_attribute('class');
        if ($tag === 'FORM') {
            $action = $processor->get_attribute('action');
            if ((strtolower((string)$processor->get_attribute('role')) === 'search' || strpos($classes, 'search') !== false)
                && strtolower((string)$processor->get_attribute('method')) !== 'post'
                && is_string($action) && wpbbshop_v433_parts($action)) {
                $processor->set_attribute('action', wpbbshop_v433_prefix(wpbbshop_v433_root().'/', wpbbshop_v433_current_language()));
            }
            continue;
        }
        if ($tag !== 'A') continue;
        if ($processor->get_attribute('hreflang') !== null || $processor->get_attribute('download') !== null
            || preg_match('/(?:llg-lang-link|lang-item|language-switch)/', $classes)) continue;
        $href = $processor->get_attribute('href');
        if (is_string($href)) {
            $target = wpbbshop_v433_url($href);
            if ($target !== $href) $processor->set_attribute('href', $target);
        }
    }
    return $processor->get_updated_html();
}
add_filter('do_shortcode_tag', function($html, $tag) {
    return strpos((string)$tag, 'wpbbshop_') === 0 ? wpbbshop_v433_html_links($html) : $html;
}, 20000, 2);
add_filter('the_content', 'wpbbshop_v433_html_links', 20000);

foreach (array('woocommerce_get_cart_url','woocommerce_get_checkout_url','woocommerce_get_myaccount_page_permalink','woocommerce_get_shop_page_permalink','woocommerce_get_checkout_page_permalink','woocommerce_get_cart_page_permalink','woocommerce_get_endpoint_url','woocommerce_product_add_to_cart_url','woocommerce_cart_item_permalink','woocommerce_loop_product_link','woocommerce_breadcrumb_home_url') as $hook) {
    add_filter($hook, function($url) { return wpbbshop_v433_is_frontend() ? wpbbshop_v433_url($url) : $url; }, 20000);
}
add_filter('woocommerce_get_breadcrumb', function($crumbs) {
    if (!wpbbshop_v433_is_frontend()) return $crumbs;
    foreach ($crumbs as &$crumb) { if (!empty($crumb[1])) $crumb[1]=wpbbshop_v433_url($crumb[1]); } unset($crumb);
    return $crumbs;
}, 20000);
add_filter('term_link', function($url, $term) {
    return wpbbshop_v433_is_frontend() && !wpbbshop_v433_resolving() && in_array($term->taxonomy, array('product_cat','product_tag'),true)
        ? wpbbshop_v433_url($url) : $url;
}, 20000, 2);
add_filter('get_search_form', function($html) {
    $html = wpbbshop_v433_html_links($html);
    if (!wpbbshop_v433_is_frontend() || !is_string($html) || !class_exists('WP_HTML_Tag_Processor')) return $html;
    $processor = new WP_HTML_Tag_Processor($html);
    while ($processor->next_tag('INPUT')) {
        if ($processor->get_attribute('name') === 'lang') $processor->set_attribute('value', wpbbshop_v433_current_language());
    }
    return $processor->get_updated_html();
}, 20000);

/** Resolve /lv/cart/, etc. against shared pages without duplicating checkout. */
function wpbbshop_v433_shared_request($vars) {
    if (!wpbbshop_v433_is_frontend() || !wpbbshop_v433_shared_bridge() || !is_array($vars)) return $vars;
    $parts = wpbbshop_v433_parts((string)($_SERVER['REQUEST_URI'] ?? ''));
    if (!$parts || !$parts['language']) return $vars;
    foreach (wpbbshop_v433_shared_pages() as $slug=>$id) {
        if ($parts['relative'] !== $slug && strpos($parts['relative'], $slug . '/') !== 0) continue;
        $target = function_exists('pll_get_post') ? absint(pll_get_post($id, $parts['language'])) : 0;
        if ($target && $target !== $id) return $vars; // Native translated pages stay native.
        $tail = trim(substr($parts['relative'],strlen($slug)), '/');
        $endpoints = function_exists('WC') && WC()->query ? WC()->query->get_query_vars() : array();
        if ($tail !== '') {
            if ((int)get_option('woocommerce_shop_page_id') === $id && preg_match('#^page/([1-9][0-9]*)$#',$tail,$m)) $vars['paged']=(int)$m[1];
            else {
                $bits=explode('/',$tail,2); $endpoint=array_search($bits[0],$endpoints,true);
                if ($endpoint===false) return $vars; // Preserve real 404s.
                $vars[$endpoint]=$bits[1] ?? '';
            }
        }
        unset($vars['error'],$vars['name'],$vars['pagename'],$vars['p'],$vars['attachment']);
        $vars['page_id']=$id; $vars['lang']='';
        $GLOBALS['wpbbshop_v433_shared_page_id']=$id;
        return $vars;
    }
    return $vars;
}
add_filter('request','wpbbshop_v433_shared_request',20000);
add_action('pre_get_posts',function($query){
    if (wpbbshop_v433_is_frontend() && $query->is_main_query() && !empty($GLOBALS['wpbbshop_v433_shared_page_id'])) $query->set('lang','');
},1);

/** Narrow canonical exception for successfully resolved shared inventory/pages. */
function wpbbshop_v433_shared_view() {
    if (!wpbbshop_v433_is_frontend() || !wpbbshop_v433_shared_bridge() || is_404()) return false;
    $parts=wpbbshop_v433_parts((string)($_SERVER['REQUEST_URI'] ?? ''));
    if (!$parts || !$parts['language']) return false;
    if (!empty($GLOBALS['wpbbshop_v433_shared_page_id'])) return true;
    if (function_exists('is_product') && is_product()) {
        $id=get_queried_object_id();
        $translated=function_exists('pll_get_post') ? absint(pll_get_post($id,$parts['language'])) : 0;
        $actual=function_exists('pll_get_post_language') ? pll_get_post_language($id,'slug') : '';
        return $actual!==$parts['language'] && (!$translated || $translated===$id);
    }
    return false;
}
add_filter('redirect_canonical',function($target,$requested){
    if (!$target || !wpbbshop_v433_shared_view()) return $target;
    $from=wpbbshop_v433_parts($requested); $to=wpbbshop_v433_parts($target);
    if ($from && $to && $from['relative']===$to['relative']) {
        $fixed=wpbbshop_v433_prefix($target,$from['language']);
        return rtrim($fixed,'/')===rtrim($requested,'/') ? false : $fixed;
    }
    return $target;
},20000,2);
add_action('template_redirect',function(){
    if (!wpbbshop_v433_shared_view()) return;
    // Some Polylang versions run their own canonical callback outside core's
    // redirect_canonical filter. Remove only that callback for this shared view.
    global $wp_filter;
    if (empty($wp_filter['template_redirect']->callbacks)) return;
    foreach ($wp_filter['template_redirect']->callbacks as $priority=>$callbacks) {
        foreach ($callbacks as $callback) {
            $fn=$callback['function'];
            if (is_array($fn) && is_object($fn[0]) && $fn[1]==='check_canonical_url' && strpos(get_class($fn[0]),'PLL_')===0) {
                remove_action('template_redirect',$fn,$priority);
            }
        }
    }
},-100);

add_filter('locale',function($locale){
    if (!wpbbshop_v433_is_frontend()) return $locale;
    $lang=wpbbshop_v433_current_language();
    if ($lang==='lv') return 'lv';
    return strpos($locale,'en_')===0 ? $locale : 'en_GB';
},20000);
add_filter('language_attributes',function($attributes){
    if (!wpbbshop_v433_is_frontend()) return $attributes;
    $lang=wpbbshop_v433_current_language()==='lv' ? 'lv' : 'en-GB';
    return preg_replace('/\b((?:xml:)?lang)=("|\')[^"\']*\2/', '$1="'.$lang.'"', $attributes);
},20000);

add_action('wp_enqueue_scripts',function(){
    if (!wpbbshop_v433_is_frontend()) return;
    wp_enqueue_script('wpbbshop-language-continuity',get_stylesheet_directory_uri().'/assets/js/v433-language-continuity.js',array('jquery'),'4.0.33',false);
    wp_localize_script('wpbbshop-language-continuity','WpbbLanguage433',array('lang'=>wpbbshop_v433_current_language()));
},1);
