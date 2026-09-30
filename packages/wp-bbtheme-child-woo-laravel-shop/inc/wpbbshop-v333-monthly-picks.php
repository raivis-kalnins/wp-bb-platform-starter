<?php
/**
 * WP BB Home & Garden 3.0.46
 * Monthly "3 picks" homepage promotion.
 *
 * The promotion replaces the large seasonal homepage advert only during the
 * configured month. Three WooCommerce products can be changed from
 * Appearance -> 3 izvēles mēneša piedāvājums. The public block only promotes
 * products that currently have an active WooCommerce sale price.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_MONTHLY_PICKS_OPTION')) {
    define('WPBBSHOP_MONTHLY_PICKS_OPTION', 'wpbbshop_monthly_picks_v333');
}

function wpbbshop_v333_monthly_current_month() {
    return wp_date('Y-m', current_time('timestamp'));
}

function wpbbshop_v333_month_name($month, $case_name = 'dative', $is_en = false) {
    $number = (int) substr((string) $month, 5, 2);
    if ($number < 1 || $number > 12) { $number = (int) wp_date('n', current_time('timestamp')); }

    if ($is_en) {
        $names = array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December');
        return $names[$number];
    }

    if ($case_name === 'locative') {
        $names = array(1=>'janvari',2=>'februari',3=>'marta',4=>'aprili',5=>'maija',6=>'junija',7=>'julija',8=>'augusta',9=>'septembri',10=>'oktobri',11=>'novembri',12=>'decembri');
    } else {
        $names = array(1=>'janvarim',2=>'februarim',3=>'martam',4=>'aprilim',5=>'maijam',6=>'junijam',7=>'julijam',8=>'augustam',9=>'septembrim',10=>'oktobrim',11=>'novembrim',12=>'decembrim');
    }

    // Restore Latvian diacritics without relying on locale packages.
    $replace = array(
        'janvari'=>'janvārī','februari'=>'februārī','marta'=>'martā','aprili'=>'aprīlī','maija'=>'maijā','junija'=>'jūnijā','julija'=>'jūlijā','augusta'=>'augustā','septembri'=>'septembrī','oktobri'=>'oktobrī','novembri'=>'novembrī','decembri'=>'decembrī',
        'janvarim'=>'janvārim','februarim'=>'februārim','martam'=>'martam','aprilim'=>'aprīlim','maijam'=>'maijam','junijam'=>'jūnijam','julijam'=>'jūlijam','augustam'=>'augustam','septembrim'=>'septembrim','oktobrim'=>'oktobrim','novembrim'=>'novembrim','decembrim'=>'decembrim',
    );
    return isset($replace[$names[$number]]) ? $replace[$names[$number]] : $names[$number];
}

function wpbbshop_v333_monthly_default_ids() {
    $ids = array();
    if (!function_exists('wc_get_product_id_by_sku')) { return $ids; }

    $preferred = array('LL-DEMO-H10','LL-DEMO-G3500','LL-DEMO-A1500');
    foreach ($preferred as $sku) {
        $id = absint(wc_get_product_id_by_sku($sku));
        if ($id && !in_array($id, $ids, true)) { $ids[] = $id; }
    }

    if (count($ids) < 3 && function_exists('wc_get_product_ids_on_sale')) {
        foreach ((array) wc_get_product_ids_on_sale() as $id) {
            $id = absint($id);
            if ($id && !in_array($id, $ids, true)) { $ids[] = $id; }
            if (count($ids) >= 3) { break; }
        }
    }
    return array_slice($ids, 0, 3);
}

function wpbbshop_v333_monthly_defaults() {
    $ids = wpbbshop_v333_monthly_default_ids();
    return array(
        'enabled' => '1',
        'month' => wpbbshop_v333_monthly_current_month(),
        'custom_title' => '',
        'product_1' => isset($ids[0]) ? (string) absint($ids[0]) : '',
        'product_2' => isset($ids[1]) ? (string) absint($ids[1]) : '',
        'product_3' => isset($ids[2]) ? (string) absint($ids[2]) : '',
    );
}

function wpbbshop_v333_monthly_get_settings() {
    $saved = get_option(WPBBSHOP_MONTHLY_PICKS_OPTION, array());
    if (!is_array($saved)) { $saved = array(); }
    return wp_parse_args($saved, wpbbshop_v333_monthly_defaults());
}

function wpbbshop_v333_monthly_seed_once() {
    if (!class_exists('WooCommerce')) { return; }
    if (get_option(WPBBSHOP_MONTHLY_PICKS_OPTION, false) !== false) { return; }
    update_option(WPBBSHOP_MONTHLY_PICKS_OPTION, wpbbshop_v333_monthly_defaults(), false);
}
add_action('init', 'wpbbshop_v333_monthly_seed_once', 40);

function wpbbshop_v333_monthly_sanitize($input) {
    $input = is_array($input) ? $input : array();
    $out = array();
    $out['enabled'] = !empty($input['enabled']) ? '1' : '0';

    $month = isset($input['month']) ? sanitize_text_field(wp_unslash($input['month'])) : '';
    if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])$/', $month)) {
        $month = wpbbshop_v333_monthly_current_month();
    }
    $out['month'] = $month;
    $out['custom_title'] = isset($input['custom_title']) ? sanitize_text_field(wp_unslash($input['custom_title'])) : '';

    $used = array();
    for ($i = 1; $i <= 3; $i++) {
        $key = 'product_' . $i;
        $id = isset($input[$key]) ? absint($input[$key]) : 0;
        if ($id && in_array($id, $used, true)) { $id = 0; }
        if ($id && get_post_type($id) !== 'product') { $id = 0; }
        if ($id) { $used[] = $id; }
        $out[$key] = $id ? (string) $id : '';
    }
    return $out;
}

add_action('admin_init', function() {
    register_setting('wpbbshop_monthly_picks_group', WPBBSHOP_MONTHLY_PICKS_OPTION, array('sanitize_callback'=>'wpbbshop_v333_monthly_sanitize'));
});

add_action('admin_menu', function() {
    add_theme_page('3 izvēles mēneša piedāvājums', '3 izvēles mēneša piedāvājums', 'manage_options', 'wpbbshop-monthly-picks', 'wpbbshop_v333_monthly_admin_page');
}, 30);

function wpbbshop_v333_monthly_product_select($key, $label, $settings) {
    $id = !empty($settings[$key]) ? absint($settings[$key]) : 0;
    $product = $id && function_exists('wc_get_product') ? wc_get_product($id) : false;
    $edit = $product ? get_edit_post_link($id, '') : '';
    $step = preg_replace('/[^0-9]+/', '', $label);

    echo '<div class="llmp-product-card">';
    echo '<div class="llmp-product-head"><div><h3>' . esc_html($label) . '</h3><p>Izvēlies preci, kurai WooCommerce jau ir aktīva atlaide.</p></div><span class="llmp-product-badge">' . esc_html($step) . '</span></div>';
    echo '<label class="screen-reader-text" for="ll-monthly-' . esc_attr($key) . '">' . esc_html($label) . '</label>';
    echo '<select id="ll-monthly-' . esc_attr($key) . '" class="wc-product-search llmp-product-search" style="width:100%;max-width:100%;" name="' . esc_attr(WPBBSHOP_MONTHLY_PICKS_OPTION) . '[' . esc_attr($key) . ']" data-placeholder="Meklēt preci..." data-action="woocommerce_json_search_products" data-allow_clear="true">';
    if ($product) {
        echo '<option value="' . esc_attr($id) . '" selected="selected">' . esc_html(wp_strip_all_tags($product->get_formatted_name())) . '</option>';
    }
    echo '</select>';

    echo '<div class="llmp-product-meta">';
    if ($product) {
        echo '<div class="llmp-product-name">' . esc_html(wp_strip_all_tags($product->get_formatted_name())) . '</div>';
        if ($product->is_on_sale()) {
            echo '<div class="llmp-notice is-success"><strong>Aktīva atlaide:</strong> ' . wp_kses_post($product->get_price_html()) . '</div>';
        } else {
            echo '<div class="llmp-notice is-warning"><strong>Nav aktīvas atlaides.</strong> Publiskajā blokā šī prece netiks rādīta, kamēr WooCommerce akcija nebūs aktīva.</div>';
        }
        if ($edit) {
            echo '<p><a class="button button-secondary" href="' . esc_url($edit) . '">Atvērt preci un iestatīt akcijas cenu / datumus</a></p>';
        }
    } else {
        echo '<div class="llmp-empty">Nav izvēlēta prece. Meklē pēc nosaukuma vai SKU.</div>';
    }
    echo '</div>';
    echo '</div>';
}

function wpbbshop_v333_monthly_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    $settings = wpbbshop_v333_monthly_get_settings();
    $preview_title = !empty($settings['custom_title']) ? $settings['custom_title'] : '3 izvēles ' . wpbbshop_v333_month_name($settings['month'], 'dative', false) . ' iesaka';
    ?>
    <div class="wrap llmp-admin-wrap">
      <style>
        .llmp-admin-wrap{max-width:1280px;margin:18px 20px 0 2px}
        .llmp-shell{display:flex;flex-direction:column;gap:20px}
        .llmp-hero{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:20px;padding:28px;border:1px solid #dbe6de;border-radius:22px;background:linear-gradient(135deg,#123d26 0%,#1c643a 56%,#2a8b4a 100%);color:#fff;box-shadow:0 18px 50px rgba(18,51,74,.08)}
        .llmp-hero h1{margin:6px 0 10px;font-size:34px;line-height:1.05;font-weight:800;color:#fff}
        .llmp-kicker{display:inline-flex;align-items:center;min-height:28px;padding:0 10px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.14);font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
        .llmp-hero p{margin:0;max-width:780px;font-size:15px;line-height:1.6;color:rgba(255,255,255,.92)}
        .llmp-preview-card{display:flex;flex-direction:column;justify-content:space-between;padding:18px 18px 16px;border-radius:18px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(4px)}
        .llmp-preview-card strong{font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:#dbffcd}
        .llmp-preview-card span{display:block;margin-top:8px;font-size:21px;line-height:1.2;font-weight:750;color:#fff}
        .llmp-preview-card small{display:block;margin-top:10px;color:rgba(255,255,255,.76)}
        .llmp-grid{display:grid;grid-template-columns:320px minmax(0,1fr);gap:20px}
        .llmp-panel{background:#fff;border:1px solid #d7e3db;border-radius:20px;padding:22px;box-shadow:0 12px 32px rgba(18,51,74,.05)}
        .llmp-panel h2{margin:0 0 6px;font-size:21px;line-height:1.2}
        .llmp-panel-intro{margin:0 0 18px;color:#5d6b76}
        .llmp-field{display:flex;flex-direction:column;gap:7px;margin-bottom:16px}
        .llmp-field:last-child{margin-bottom:0}
        .llmp-field>label,.llmp-panel legend{font-weight:700;color:#142836}
        .llmp-field input[type=text],.llmp-field input[type=month]{width:100%;max-width:100%;min-height:46px;padding:0 14px;border:1px solid #cfd9df;border-radius:12px;background:#fff}
        .llmp-field .description{margin:0;color:#6d7b86}
        .llmp-switch{display:flex;align-items:flex-start;gap:12px;padding:14px 14px 12px;border:1px solid #dce8e0;border-radius:16px;background:#f8fbf8}
        .llmp-switch input{margin-top:3px}
        .llmp-switch strong{display:block;margin-bottom:2px}
        .llmp-tips{display:grid;gap:10px;margin-top:16px}
        .llmp-tip{padding:12px 14px;border-radius:14px;background:#f7faf8;border:1px solid #e2ebe5;color:#38505e}
        .llmp-tip strong{display:block;margin-bottom:4px;color:#183143}
        .llmp-products-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
        .llmp-product-card{display:flex;flex-direction:column;gap:14px;padding:18px;border:1px solid #dde7e1;border-radius:18px;background:linear-gradient(180deg,#fff 0%,#f7faf8 100%)}
        .llmp-product-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
        .llmp-product-head h3{margin:0 0 3px;font-size:18px}
        .llmp-product-head p{margin:0;color:#6d7b86;line-height:1.45}
        .llmp-product-badge{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 10px;border-radius:999px;background:#e8f6eb;color:#20733d;font-size:12px;font-weight:800}
        .llmp-product-search{width:100%!important}
        .llmp-product-meta{display:flex;flex-direction:column;gap:12px}
        .llmp-product-name{font-weight:700;color:#182d3e}
        .llmp-empty{padding:12px 14px;border:1px dashed #cdd8d2;border-radius:12px;background:#fff;color:#687783}
        .llmp-notice{padding:12px 14px;border-radius:14px;line-height:1.5}
        .llmp-notice.is-success{background:#eef9f1;border:1px solid #cde8d5;color:#1c6f39}
        .llmp-notice.is-warning{background:#fff8ea;border:1px solid #f0dfb8;color:#8a5a00}
        .llmp-actions{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:18px}
        .llmp-actions p{margin:0;color:#64727e}
        .llmp-admin-wrap .button-primary{min-height:46px;padding:0 18px;border-radius:12px;background:#2b9748;border-color:#2b9748}
        .llmp-admin-wrap .button-primary:hover{background:#237e3d;border-color:#237e3d}
        .llmp-admin-wrap .select2-container{max-width:100%!important}
        .llmp-admin-wrap .select2-container .select2-selection--single,.llmp-admin-wrap .select2-container .select2-selection--multiple{min-height:44px;border-color:#cfd9df;border-radius:12px}
        .llmp-admin-wrap .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:42px;padding-left:14px}
        .llmp-admin-wrap .select2-container--default .select2-selection--single .select2-selection__arrow{height:42px;right:8px}.llmp-admin-wrap .notice{margin:0 0 16px!important;padding:10px 40px 10px 14px!important;border-radius:12px!important;background:#fff!important;box-shadow:0 6px 18px rgba(18,51,74,.05)!important}.llmp-admin-wrap .notice p{margin:0!important;color:#1b3343!important;font-weight:600!important;line-height:1.45!important}.llmp-admin-wrap .notice-success{background:#f0faf3!important;border-left:4px solid #248a45!important}.llmp-admin-wrap .notice-success p{color:#173c25!important}.llmp-admin-wrap .notice-warning{background:#fff8e8!important;border-left:4px solid #d59a18!important}.llmp-admin-wrap .notice-warning p{color:#6a4900!important}.llmp-admin-wrap .notice-error{background:#fff0f1!important;border-left:4px solid #c63e49!important}.llmp-admin-wrap .notice-error p{color:#7a1f28!important}.llmp-admin-wrap .notice-dismiss:before{color:#415a68!important}
        @media (max-width:1100px){.llmp-grid{grid-template-columns:1fr}.llmp-products-grid{grid-template-columns:1fr 1fr}.llmp-hero{grid-template-columns:1fr}}
        @media (max-width:700px){.llmp-admin-wrap{margin-right:10px}.llmp-hero{padding:20px}.llmp-hero h1{font-size:28px}.llmp-panel{padding:18px}.llmp-products-grid{grid-template-columns:1fr}.llmp-actions{flex-direction:column;align-items:flex-start}}
      </style>
      <div class="llmp-shell">
        <div class="llmp-hero">
          <div>
            <span class="llmp-kicker">Sākumlapa • akcijas izcēlums</span>
            <h1>3 izvēles mēneša piedāvājums</h1>
            <p>Vienkārši izvēlies mēnesi un 3 akcijas preces. Bloks sākumlapā automātiski parādīsies tikai izvēlētajā mēnesī un paslēpsies, kad mēnesis beigsies.</p>
          </div>
          <div class="llmp-preview-card">
            <div>
              <strong>Publiskais virsraksts</strong>
              <span><?php echo esc_html($preview_title); ?></span>
            </div>
            <small>Ja virsrakstu atstāsi tukšu, tas tiks ģenerēts automātiski.</small>
          </div>
        </div>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
          <?php settings_fields('wpbbshop_monthly_picks_group'); ?>
          <div class="llmp-grid">
            <section class="llmp-panel">
              <h2>Pamata iestatījumi</h2>
              <p class="llmp-panel-intro">Šeit nosaki, kad bloku rādīt un kāds būs tā virsraksts.</p>
              <div class="llmp-field">
                <div class="llmp-switch">
                  <input id="ll-monthly-enabled" type="checkbox" name="<?php echo esc_attr(WPBBSHOP_MONTHLY_PICKS_OPTION); ?>[enabled]" value="1" <?php checked($settings['enabled'], '1'); ?>>
                  <div>
                    <label for="ll-monthly-enabled"><strong>Ieslēgt mēneša bloku</strong></label>
                    <div>Rādīt “3 izvēles” reklāmas bloku sākumlapā.</div>
                  </div>
                </div>
              </div>
              <div class="llmp-field">
                <label for="ll-monthly-month">Mēnesis</label>
                <input id="ll-monthly-month" type="month" name="<?php echo esc_attr(WPBBSHOP_MONTHLY_PICKS_OPTION); ?>[month]" value="<?php echo esc_attr($settings['month']); ?>">
                <p class="description">Kad šis mēnesis beigsies, bloks automātiski pazudīs.</p>
              </div>
              <div class="llmp-field">
                <label for="ll-monthly-title">Virsraksts</label>
                <input id="ll-monthly-title" type="text" name="<?php echo esc_attr(WPBBSHOP_MONTHLY_PICKS_OPTION); ?>[custom_title]" value="<?php echo esc_attr($settings['custom_title']); ?>" placeholder="<?php echo esc_attr($preview_title); ?>">
                <p class="description">Atstāj tukšu, lai virsraksts mainītos automātiski, piemēram, “3 izvēles septembrim iesaka”.</p>
              </div>
              <div class="llmp-tips">
                <div class="llmp-tip"><strong>1. Izvēlies akcijas preces</strong>Tiks parādītas tikai preces ar aktīvu WooCommerce akcijas cenu.</div>
                <div class="llmp-tip"><strong>2. Produkta atlaides datumus labo produktā</strong>Nospied uz pogas katrā preces kartītē, lai ātri atvērtu rediģēšanu.</div>
              </div>
            </section>
            <section class="llmp-panel">
              <h2>Izvēlētās 3 preces</h2>
              <p class="llmp-panel-intro">Meklē pēc nosaukuma vai SKU. Ieteicams izmantot vizuāli atšķirīgas un labi atlaistas preces.</p>
              <div class="llmp-products-grid">
                <?php wpbbshop_v333_monthly_product_select('product_1', 'Prece 1', $settings); ?>
                <?php wpbbshop_v333_monthly_product_select('product_2', 'Prece 2', $settings); ?>
                <?php wpbbshop_v333_monthly_product_select('product_3', 'Prece 3', $settings); ?>
              </div>
            </section>
          </div>
          <div class="llmp-actions">
            <p>Pēc saglabāšanas, ja vajag, notīri lapas kešu un pārlādē sākumlapu, lai redzētu izmaiņas.</p>
            <?php submit_button('Saglabāt mēneša piedāvājumu', 'primary', 'submit', false); ?>
          </div>
        </form>
      </div>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'appearance_page_wpbbshop-monthly-picks') { return; }
    if (wp_script_is('wc-enhanced-select', 'registered')) { wp_enqueue_script('wc-enhanced-select'); }
    if (wp_style_is('woocommerce_admin_styles', 'registered')) { wp_enqueue_style('woocommerce_admin_styles'); }
});

function wpbbshop_v333_monthly_selected_products($settings) {
    $products = array();
    $used = array();
    if (!function_exists('wc_get_product')) { return $products; }

    for ($i = 1; $i <= 3; $i++) {
        $id = !empty($settings['product_' . $i]) ? absint($settings['product_' . $i]) : 0;
        if (!$id || in_array($id, $used, true)) { continue; }
        $product = wc_get_product($id);
        if (!$product || $product->get_status() !== 'publish' || !$product->is_on_sale()) { continue; }
        $products[] = $product;
        $used[] = $id;
    }

    if (count($products) < 3 && function_exists('wc_get_product_ids_on_sale')) {
        foreach ((array) wc_get_product_ids_on_sale() as $id) {
            $id = absint($id);
            if (!$id || in_array($id, $used, true)) { continue; }
            $product = wc_get_product($id);
            if (!$product || $product->get_status() !== 'publish' || !$product->is_on_sale()) { continue; }
            $products[] = $product;
            $used[] = $id;
            if (count($products) >= 3) { break; }
        }
    }

    return array_slice($products, 0, 3);
}

function wpbbshop_v333_discount_percent($product) {
    if (!$product || !is_a($product, 'WC_Product') || $product->is_type('variable')) { return 0; }
    $regular = (float) $product->get_regular_price();
    $sale = (float) $product->get_sale_price();
    if ($regular <= 0 || $sale <= 0 || $sale >= $regular) { return 0; }
    return max(1, (int) round((($regular - $sale) / $regular) * 100));
}

function wpbbshop_v333_month_number($month) {
    $number = (int) substr((string) $month, 5, 2);
    if ($number < 1 || $number > 12) { $number = (int) wp_date('n', current_time('timestamp')); }
    return $number;
}

function wpbbshop_v333_monthly_season($month) {
    $number = wpbbshop_v333_month_number($month);
    if (in_array($number, array(12, 1, 2), true)) { return 'winter'; }
    if (in_array($number, array(3, 4, 5), true)) { return 'spring'; }
    if (in_array($number, array(6, 7, 8), true)) { return 'summer'; }
    return 'autumn';
}

function wpbbshop_v333_monthly_season_label($season, $is_en = false) {
    $labels = $is_en
        ? array(
            'winter' => 'Winter mood',
            'spring' => 'Spring bloom',
            'summer' => 'Midsummer mood',
            'autumn' => 'Autumn picks',
        )
        : array(
            'winter' => 'Ziemas noskaņa',
            'spring' => 'Pavasara ziedi',
            'summer' => 'Jāņu noskaņa',
            'autumn' => 'Rudens izlase',
        );
    return isset($labels[$season]) ? $labels[$season] : ($is_en ? 'Special offers' : 'Īpašie piedāvājumi');
}

function wpbbshop_v333_monthly_art_svg($season) {
    ob_start();
    switch ($season) {
        case 'winter': ?>
            <svg class="ll33-art-svg ll33-art-svg--winter" viewBox="0 0 260 170" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
              <g class="ll33-art-float ll33-art-float--slow">
                <circle class="ll33-art-soft" cx="130" cy="138" r="56"/>
                <polygon class="ll33-art-accent" points="130,38 165,84 148,84 178,120 82,120 112,84 95,84"/>
                <rect class="ll33-art-detail" x="121" y="120" width="18" height="20" rx="4"/>
                <circle class="ll33-art-highlight" cx="112" cy="88" r="4"/>
                <circle class="ll33-art-highlight" cx="130" cy="72" r="4"/>
                <circle class="ll33-art-highlight" cx="147" cy="94" r="4"/>
              </g>
              <g class="ll33-art-spin ll33-art-spin--soft">
                <path class="ll33-art-line-shape" d="M130 22 L130 38"/>
                <path class="ll33-art-line-shape" d="M118 30 L142 30"/>
                <path class="ll33-art-line-shape" d="M121 23 L139 37"/>
                <path class="ll33-art-line-shape" d="M139 23 L121 37"/>
              </g>
              <g class="ll33-art-drift ll33-art-drift--1"><circle class="ll33-art-spark" cx="72" cy="50" r="5"/></g>
              <g class="ll33-art-drift ll33-art-drift--2"><circle class="ll33-art-spark" cx="190" cy="44" r="4"/></g>
              <g class="ll33-art-drift ll33-art-drift--3"><circle class="ll33-art-spark" cx="205" cy="98" r="5"/></g>
              <g class="ll33-art-drift ll33-art-drift--4"><circle class="ll33-art-spark" cx="56" cy="108" r="4"/></g>
            </svg>
        <?php break;
        case 'spring': ?>
            <svg class="ll33-art-svg ll33-art-svg--spring" viewBox="0 0 260 170" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
              <path class="ll33-art-branch" d="M72 124 C110 98 130 84 178 58"/>
              <path class="ll33-art-branch ll33-art-branch--two" d="M98 132 C126 110 152 98 194 90"/>
              <g class="ll33-art-float ll33-art-float--slow">
                <g class="ll33-art-flower ll33-art-flower--1" transform="translate(112 88)">
                  <circle class="ll33-art-petal" cx="0" cy="-13" r="10"/><circle class="ll33-art-petal" cx="12" cy="-4" r="10"/><circle class="ll33-art-petal" cx="8" cy="11" r="10"/><circle class="ll33-art-petal" cx="-8" cy="11" r="10"/><circle class="ll33-art-petal" cx="-12" cy="-4" r="10"/><circle class="ll33-art-core" cx="0" cy="0" r="6"/>
                </g>
                <g class="ll33-art-flower ll33-art-flower--2" transform="translate(156 66)">
                  <circle class="ll33-art-petal" cx="0" cy="-11" r="9"/><circle class="ll33-art-petal" cx="11" cy="-2" r="9"/><circle class="ll33-art-petal" cx="7" cy="10" r="9"/><circle class="ll33-art-petal" cx="-7" cy="10" r="9"/><circle class="ll33-art-petal" cx="-11" cy="-2" r="9"/><circle class="ll33-art-core" cx="0" cy="0" r="5"/>
                </g>
              </g>
              <g class="ll33-art-drift ll33-art-drift--1"><ellipse class="ll33-art-petal-fall" cx="78" cy="56" rx="8" ry="5" transform="rotate(-12 78 56)"/></g>
              <g class="ll33-art-drift ll33-art-drift--2"><ellipse class="ll33-art-petal-fall" cx="188" cy="44" rx="7" ry="5" transform="rotate(24 188 44)"/></g>
              <g class="ll33-art-drift ll33-art-drift--3"><ellipse class="ll33-art-petal-fall" cx="210" cy="106" rx="8" ry="5" transform="rotate(-28 210 106)"/></g>
            </svg>
        <?php break;
        case 'summer': ?>
            <svg class="ll33-art-svg ll33-art-svg--summer" viewBox="0 0 260 170" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
              <g class="ll33-art-float ll33-art-float--slow">
                <circle class="ll33-art-ring-shape" cx="128" cy="88" r="44"/>
                <circle class="ll33-art-ring-center" cx="128" cy="88" r="16"/>
              </g>
              <g class="ll33-art-spin">
                <ellipse class="ll33-art-leaf" cx="128" cy="34" rx="11" ry="23"/>
                <ellipse class="ll33-art-leaf" cx="171" cy="49" rx="11" ry="23" transform="rotate(45 171 49)"/>
                <ellipse class="ll33-art-leaf" cx="192" cy="88" rx="11" ry="23" transform="rotate(90 192 88)"/>
                <ellipse class="ll33-art-leaf" cx="171" cy="127" rx="11" ry="23" transform="rotate(135 171 127)"/>
                <ellipse class="ll33-art-leaf" cx="128" cy="142" rx="11" ry="23"/>
                <ellipse class="ll33-art-leaf" cx="85" cy="127" rx="11" ry="23" transform="rotate(-135 85 127)"/>
                <ellipse class="ll33-art-leaf" cx="64" cy="88" rx="11" ry="23" transform="rotate(90 64 88)"/>
                <ellipse class="ll33-art-leaf" cx="85" cy="49" rx="11" ry="23" transform="rotate(-45 85 49)"/>
              </g>
              <circle class="ll33-art-sun" cx="210" cy="42" r="15"/>
              <g class="ll33-art-drift ll33-art-drift--4"><circle class="ll33-art-spark" cx="56" cy="112" r="4"/></g>
            </svg>
        <?php break;
        default: ?>
            <svg class="ll33-art-svg ll33-art-svg--autumn" viewBox="0 0 260 170" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
              <g class="ll33-art-float ll33-art-float--slow">
                <path class="ll33-art-leaf-shape ll33-art-leaf-shape--1" d="M92 56 C116 38 138 48 138 74 C138 103 115 116 92 118 C79 96 76 75 92 56 Z"/>
                <path class="ll33-art-leaf-shape ll33-art-leaf-shape--2" d="M152 52 C174 40 194 48 198 73 C203 99 187 115 166 121 C149 103 142 79 152 52 Z"/>
                <path class="ll33-art-leaf-shape ll33-art-leaf-shape--3" d="M126 76 C145 67 161 75 164 95 C167 112 156 126 139 132 C123 117 118 97 126 76 Z"/>
              </g>
              <path class="ll33-art-vein" d="M108 58 C112 78 111 95 103 114"/>
              <path class="ll33-art-vein" d="M170 56 C176 78 177 97 169 118"/>
              <path class="ll33-art-vein" d="M143 79 C146 94 145 108 140 125"/>
              <g class="ll33-art-drift ll33-art-drift--1"><circle class="ll33-art-spark" cx="64" cy="54" r="5"/></g>
              <g class="ll33-art-drift ll33-art-drift--2"><circle class="ll33-art-spark" cx="206" cy="48" r="4"/></g>
              <g class="ll33-art-drift ll33-art-drift--3"><circle class="ll33-art-spark" cx="214" cy="112" r="5"/></g>
            </svg>
        <?php break;
    }
    return ob_get_clean();
}

function wpbbshop_v333_monthly_promo_html($is_en = false) {
    if (!class_exists('WooCommerce')) { return ''; }
    $settings = wpbbshop_v333_monthly_get_settings();
    if (empty($settings['enabled']) || $settings['enabled'] !== '1') { return ''; }
    if ((string) $settings['month'] !== wpbbshop_v333_monthly_current_month()) { return ''; }

    $products = wpbbshop_v333_monthly_selected_products($settings);
    if (count($products) < 1) { return ''; }

    $month_dative = wpbbshop_v333_month_name($settings['month'], 'dative', $is_en);
    $month_locative = wpbbshop_v333_month_name($settings['month'], 'locative', $is_en);
    $season = wpbbshop_v333_monthly_season($settings['month']);
    $season_class = 'll33-season-' . $season;
    $season_label = wpbbshop_v333_monthly_season_label($season, $is_en);
    $title = !empty($settings['custom_title']) ? $settings['custom_title'] : ($is_en ? ('3 things we recommend for ' . $month_dative) : ('3 izvēles ' . $month_dative . ' iesaka'));
    if (!$is_en) { $title = preg_replace('/^\s*3\s*(?:izvēles|picks)\b/ui', '3 izvēles', $title); }
    $eyebrow = $is_en ? ('Only in ' . $month_locative) : ('Tikai ' . $month_locative);
    $sub = $is_en ? 'Three selected products with a special price for this month only.' : 'Trīs izvēlētas preces ar īpašu cenu tikai šajā mēnesī.';

    ob_start(); ?>
    <section class="ll33-monthly-promo <?php echo esc_attr($season_class); ?>" aria-label="<?php echo esc_attr($title); ?>">
      <div class="ll33-monthly-head">
        <div class="ll33-monthly-head-copy">
          <span class="ll33-monthly-kicker"><?php echo esc_html($eyebrow); ?></span>
          <h1><?php echo esc_html($title); ?></h1>
          <p><?php echo esc_html($sub); ?></p>
        </div>
        <div class="ll33-monthly-art" aria-hidden="true">
          <span class="ll33-monthly-art-pill"><?php echo esc_html($is_en ? '3 special offers' : '3 īpašie piedāvājumi'); ?></span>
          <span class="ll33-monthly-art-caption"><?php echo esc_html($season_label); ?></span>
          <span class="ll33-monthly-art-ring"></span>
          <span class="ll33-monthly-art-line"></span>
          <span class="ll33-monthly-art-line ll33-monthly-art-line--two"></span>
          <div class="ll33-monthly-art-visual"><?php echo wpbbshop_v333_monthly_art_svg($season); ?></div>
          <span class="ll33-monthly-art-dot ll33-monthly-art-dot--one"></span>
          <span class="ll33-monthly-art-dot ll33-monthly-art-dot--two"></span>
        </div>
      </div>
      <div class="ll33-monthly-grid">
        <?php foreach ($products as $product) :
            $id = $product->get_id();
            $link = get_permalink($id);
            $name = function_exists('wpbbshop_green_demo_name_309') ? wpbbshop_green_demo_name_309($product) : $product->get_name();
            $discount = wpbbshop_v333_discount_percent($product);
            ?>
          <article class="ll33-monthly-card product">
            <a class="ll33-monthly-image" href="<?php echo esc_url($link); ?>" aria-label="<?php echo esc_attr($name); ?>">
              <span class="ll33-monthly-sale"><?php echo $discount ? esc_html('-' . $discount . '%') : esc_html($is_en ? 'SALE' : 'AKCIJA'); ?></span>
              <?php echo function_exists('wpbbshop_green_product_image_html') ? wpbbshop_green_product_image_html($product) : $product->get_image('woocommerce_thumbnail'); ?>
            </a>
            <div class="ll33-monthly-copy">
              <h2><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($name); ?></a></h2>
              <div class="ll33-monthly-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
              <a class="ll33-monthly-link" href="<?php echo esc_url($link); ?>"><?php echo esc_html($is_en ? 'View product' : 'Skatīt preci'); ?> <?php echo function_exists('wpbbshop_green_icon') ? wpbbshop_green_icon('arrow') : '&rarr;'; ?></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
    return ob_get_clean();
}

/** Clear common caches once after v3.0.46 is installed. */
function wpbbshop_v333_cache_purge_once() {
    if ((string) get_option('wpbbshop_v333_cache_purged', '') === '3.0.46') { return; }
    if (is_admin() && !current_user_can('manage_options')) { return; }

    if (function_exists('wp_theme_purge_all_theme_cache')) { wp_theme_purge_all_theme_cache(); }
    if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
    if (function_exists('rocket_clean_domain')) { rocket_clean_domain(); }
    if (function_exists('w3tc_flush_all')) { w3tc_flush_all(); }
    if (function_exists('wp_cache_clear_cache')) { wp_cache_clear_cache(); }
    if (function_exists('sg_cachepress_purge_cache')) { sg_cachepress_purge_cache(); }
    if (class_exists('autoptimizeCache') && is_callable(array('autoptimizeCache', 'clearall'))) { autoptimizeCache::clearall(); }
    if (class_exists('LiteSpeed\\Purge') && is_callable(array('LiteSpeed\\Purge', 'purge_all'))) { LiteSpeed\Purge::purge_all(); }
    delete_transient('wc_products_onsale');
    update_option('wpbbshop_v333_cache_purged', '3.0.46', false);
}
add_action('admin_init', 'wpbbshop_v333_cache_purge_once', 1017);
