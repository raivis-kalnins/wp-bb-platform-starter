<?php
/**
 * WP BB Home & Garden v4.0.14 — final theme-integrated storefront repairs.
 *
 * This is intentionally part of the child theme. No extra "storefront fixes"
 * plugin is required. It owns the last CSS/JS layer, repairs required service
 * pages, and keeps the order-tracking and information pages aligned with the
 * main storefront shell.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V414_VERSION')) {
    define('WPBBSHOP_V414_VERSION', '4.0.14');
}

add_action('wp_enqueue_scripts', function () {
    $css_file = get_stylesheet_directory() . '/assets/css/v414-final-ui.css';
    $js_file  = get_stylesheet_directory() . '/assets/js/v414-final-ui.js';

    if (is_readable($css_file)) {
        wp_enqueue_style(
            'wpbbshop-v414-final-ui',
            get_stylesheet_directory_uri() . '/assets/css/v414-final-ui.css',
            array(),
            (string) filemtime($css_file)
        );
    }

    if (is_readable($js_file)) {
        wp_enqueue_script(
            'wpbbshop-v414-final-ui',
            get_stylesheet_directory_uri() . '/assets/js/v414-final-ui.js',
            array(),
            (string) filemtime($js_file),
            true
        );
    }
}, PHP_INT_MAX);

/**
 * Rich Returns & Warranty content used by the managed Latvian/English pages.
 */
function wpbbshop_v414_returns_content($lang = 'lv') {
    $en = ($lang === 'en');

    if ($en) {
        return '<section class="llg-info-pro"><div class="llg-info-intro"><span>RETURNS &amp; WARRANTY</span><h2>Clear support after purchase</h2><p>If a product is unsuitable, damaged or develops a fault, contact WP BB Home &amp; Garden before returning it so we can confirm the correct return or warranty process.</p></div><div class="llg-info-cards"><article><h3>Returns</h3><p>Consumer return and withdrawal rights are handled according to the applicable consumer-protection rules. Contact us with the order number before sending goods back.</p></article><article><h3>Product condition</h3><p>Where possible, keep the product, accessories, manuals and packaging together. Products should be protected appropriately for return transport.</p></article><article><h3>Warranty claims</h3><p>For a suspected defect, send the order number, product details and a short description of the issue. Photos or video can help us assess the next step.</p></article><article><h3>Return transport</h3><p>The return method depends on product size, reason for return and the applicable rights. We will confirm the suitable address or collection method before dispatch.</p></article></div><div class="llg-info-band"><h3>Need help with a return?</h3><p>Contact us before sending the product so the parcel can be identified and processed correctly.</p></div></section>';
    }

    return '<section class="llg-info-pro"><div class="llg-info-intro"><span>ATGRIEŠANA UN GARANTIJA</span><h2>Skaidra palīdzība arī pēc pirkuma</h2><p>Ja prece nav piemērota, ir bojāta vai tai radusies problēma, pirms nosūtīšanas atpakaļ sazinies ar WP BB Home &amp; Garden, lai varam precizēt pareizo atgriešanas vai garantijas kārtību.</p></div><div class="llg-info-cards"><article><h3>Atgriešana</h3><p>Patērētāju atgriešanas un atteikuma tiesības piemērojam atbilstoši spēkā esošajiem patērētāju aizsardzības noteikumiem. Pirms preces nosūtīšanas sazinies ar mums un norādi pasūtījuma numuru.</p></article><article><h3>Preces stāvoklis</h3><p>Ja iespējams, saglabā preci, piederumus, instrukcijas un iepakojumu kopā. Atpakaļsūtīšanai prece jāiepako tā, lai transportēšanas laikā tā netiktu bojāta.</p></article><article><h3>Garantijas pieteikums</h3><p>Ja ir aizdomas par defektu, atsūti pasūtījuma numuru, preces informāciju un īsu problēmas aprakstu. Foto vai video var palīdzēt ātrāk vienoties par nākamo soli.</p></article><article><h3>Atpakaļnosūtīšana</h3><p>Atgriešanas veids ir atkarīgs no preces izmēra, atgriešanas iemesla un piemērojamajām tiesībām. Pirms nosūtīšanas precizēsim adresi vai saņemšanas veidu.</p></article></div><div class="llg-info-band"><h3>Vajadzīga palīdzība ar atgriešanu?</h3><p>Sazinies ar mums pirms preces nosūtīšanas, lai sūtījumu varētu pareizi identificēt un apstrādāt.</p></div></section>';
}

function wpbbshop_v414_managed_content($type, $lang) {
    if ($type === 'delivery' && function_exists('wpbbshop_v305_info_content')) {
        return wpbbshop_v305_info_content($lang, 'delivery');
    }
    if ($type === 'terms' && function_exists('wpbbshop_v313_page_content')) {
        return wpbbshop_v313_page_content('terms', $lang);
    }
    if ($type === 'returns') {
        return wpbbshop_v414_returns_content($lang);
    }
    return '';
}

/**
 * Create a missing page, or republish an existing page without overwriting
 * editor-authored content. Empty/managed content is refreshed from the theme.
 */
function wpbbshop_v414_upsert_page($slug, $title, $content, $excerpt = '', $lang = '') {
    $page = get_page_by_path($slug, OBJECT, 'page');

    if ($page instanceof WP_Post) {
        $update = array('ID' => $page->ID);
        if ($page->post_status !== 'publish') {
            $update['post_status'] = 'publish';
        }
        $old_content = trim((string) $page->post_content);
        $managed = ($old_content === '') || strpos($old_content, 'wpbbshop-managed-page:') !== false;
        if ($managed && $content !== '' && $page->post_content !== $content) {
            $update['post_content'] = $content;
        }
        if (count($update) > 1) {
            wp_update_post($update);
        }
        $id = (int) $page->ID;
    } else {
        $id = wp_insert_post(array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_title'     => $title,
            'post_name'      => $slug,
            'post_content'   => $content,
            'post_excerpt'   => $excerpt,
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ), true);
        if (is_wp_error($id) || !$id) {
            return 0;
        }
        $id = (int) $id;
    }

    if ($lang && function_exists('pll_set_post_language')) {
        pll_set_post_language($id, $lang);
    }
    return $id;
}

function wpbbshop_v414_required_page_specs() {
    return array(
        'piegade-un-apmaksa' => array(
            'title'   => 'Piegāde un apmaksa',
            'type'    => 'delivery',
            'lang'    => 'lv',
            'excerpt' => 'Piegāde visā Latvijā, saņemšana Rīgā un ērti apmaksas veidi.',
        ),
        'delivery-payment' => array(
            'title'   => 'Delivery & payment',
            'type'    => 'delivery',
            'lang'    => 'en',
            'excerpt' => 'Delivery throughout Latvia, pickup in Riga and convenient payment methods.',
        ),
        'atgriesana-un-garantija' => array(
            'title'   => 'Atgriešana un garantija',
            'type'    => 'returns',
            'lang'    => 'lv',
            'excerpt' => 'Informācija par preču atgriešanu, garantijas pieteikumiem un atpakaļnosūtīšanu.',
        ),
        'returns-warranty' => array(
            'title'   => 'Returns & warranty',
            'type'    => 'returns',
            'lang'    => 'en',
            'excerpt' => 'Information about returns, warranty claims and return shipping.',
        ),
        'pirksanas-noteikumi' => array(
            'title'   => 'Pirkšanas noteikumi',
            'type'    => 'terms',
            'lang'    => 'lv',
            'excerpt' => 'WP BB Home & Garden pasūtīšanas, apmaksas, piegādes un atgriešanas pamatnoteikumi.',
        ),
        'terms-conditions' => array(
            'title'   => 'Terms & conditions',
            'type'    => 'terms',
            'lang'    => 'en',
            'excerpt' => 'WP BB Home & Garden order, payment, delivery and return terms.',
        ),
    );
}

function wpbbshop_v414_required_pages_need_repair() {
    if ((string) get_option('wpbbshop_v414_content_version', '') !== '4.0.14-full-3') {
        return true;
    }

    foreach (array_keys(wpbbshop_v414_required_page_specs()) as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page instanceof WP_Post || $page->post_status !== 'publish') {
            return true;
        }
    }

    $track = get_page_by_path('track-your-order', OBJECT, 'page');
    return !$track instanceof WP_Post || $track->post_status !== 'publish';
}

/**
 * Self-healing required storefront pages.
 * Runs once after this full-theme build is installed, and again only if a
 * required page is removed/unpublished later.
 */
function wpbbshop_v414_ensure_required_pages() {
    if (!wpbbshop_v414_required_pages_need_repair()) {
        return;
    }

    $ids = array();
    foreach (wpbbshop_v414_required_page_specs() as $slug => $spec) {
        $ids[$slug] = wpbbshop_v414_upsert_page(
            $slug,
            $spec['title'],
            wpbbshop_v414_managed_content($spec['type'], $spec['lang']),
            $spec['excerpt'],
            $spec['lang']
        );
    }

    $track = get_page_by_path('track-your-order', OBJECT, 'page');
    if ($track instanceof WP_Post) {
        $update = array('ID' => $track->ID, 'post_status' => 'publish');
        if ($track->post_title === 'Preču atriešana' || trim((string) $track->post_title) === '') {
            $update['post_title'] = 'Sekot pasūtījumam';
        }
        if (trim((string) $track->post_content) === '') {
            $update['post_content'] = '[woocommerce_order_tracking]';
        }
        wp_update_post($update);
    } else {
        wpbbshop_v414_upsert_page(
            'track-your-order',
            'Sekot pasūtījumam',
            '[woocommerce_order_tracking]',
            'Pārbaudi pasūtījuma statusu, izmantojot pasūtījuma numuru un e-pasta adresi.'
        );
    }

    if (function_exists('pll_save_post_translations')) {
        if (!empty($ids['piegade-un-apmaksa']) && !empty($ids['delivery-payment'])) {
            pll_save_post_translations(array(
                'lv' => $ids['piegade-un-apmaksa'],
                'en' => $ids['delivery-payment'],
            ));
        }
        if (!empty($ids['atgriesana-un-garantija']) && !empty($ids['returns-warranty'])) {
            pll_save_post_translations(array(
                'lv' => $ids['atgriesana-un-garantija'],
                'en' => $ids['returns-warranty'],
            ));
        }
        if (!empty($ids['pirksanas-noteikumi']) && !empty($ids['terms-conditions'])) {
            pll_save_post_translations(array(
                'lv' => $ids['pirksanas-noteikumi'],
                'en' => $ids['terms-conditions'],
            ));
        }
    }

    flush_rewrite_rules(false);
    update_option('wpbbshop_v414_content_version', '4.0.14-full-3', false);
}
add_action('init', 'wpbbshop_v414_ensure_required_pages', 80);
add_action('admin_init', 'wpbbshop_v414_ensure_required_pages', 2000);
add_action('after_switch_theme', 'wpbbshop_v414_ensure_required_pages', 2000);

function wpbbshop_v414_legacy_page_aliases() {
    return array(
        'terms-and-conditions' => 'terms-conditions',
    );
}

function wpbbshop_v414_request_path() {
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    return trim((string) parse_url((string) $uri, PHP_URL_PATH), '/');
}

/**
 * Repair stale/local Polylang rewrite states. A request such as
 * /atgriesana-un-garantija/ can still resolve after the page has been created,
 * even if the canonical Latvian URL is /lv/atgriesana-un-garantija/.
 */
add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }

    $path = wpbbshop_v414_request_path();
    $slug = basename($path);

    // Keep the older English slug working, but make the footer slug canonical.
    $aliases = wpbbshop_v414_legacy_page_aliases();
    if (isset($aliases[$slug])) {
        $canonical = get_page_by_path($aliases[$slug], OBJECT, 'page');
        if ($canonical instanceof WP_Post && $canonical->post_status === 'publish') {
            wp_safe_redirect(get_permalink($canonical), 301);
            exit;
        }
    }

    if (!is_404()) {
        return;
    }

    $specs = wpbbshop_v414_required_page_specs();
    if (!isset($specs[$slug])) {
        return;
    }

    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        $permalink = get_permalink($page);
        $target_path = trim((string) parse_url((string) $permalink, PHP_URL_PATH), '/');
        if ($target_path !== $path) {
            wp_safe_redirect($permalink, 302);
            exit;
        }
    }

    // Last-resort virtual render: never show a 404 for a managed policy page.
    $spec = $specs[$slug];
    status_header(200);
    get_header();
    echo '<main class="wpbbshop-page-shell wpbbshop-container llg-v414-virtual-info">';
    echo '<header class="wpbbshop-page-heading wpbbshop-info-heading"><span class="wpbbshop-page-kicker">WP BB HOME &amp; GARDEN</span><h1>' . esc_html($spec['title']) . '</h1></header>';
    echo '<article class="wpbbshop-page-card"><div class="entry-content">' . do_shortcode(wpbbshop_v414_managed_content($spec['type'], $spec['lang'])) . '</div></article>';
    echo '</main>';
    get_footer();
    exit;
}, 0);

add_filter('body_class', function ($classes) {
    $info_slugs = array_merge(array_keys(wpbbshop_v414_required_page_specs()), array_keys(wpbbshop_v414_legacy_page_aliases()));
    $request_slug = basename(wpbbshop_v414_request_path());

    if (is_page($info_slugs) || in_array($request_slug, $info_slugs, true)) {
        $classes[] = 'llg-v414-managed-info';
    }
    if (is_page('track-your-order') || $request_slug === 'track-your-order') {
        $classes[] = 'llg-v414-track-order';
    }
    return $classes;
}, PHP_INT_MAX);
