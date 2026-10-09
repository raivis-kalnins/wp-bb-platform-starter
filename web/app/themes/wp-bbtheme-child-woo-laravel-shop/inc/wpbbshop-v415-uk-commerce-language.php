<?php
/**
 * WP BB Home & Garden v4.0.15
 * UK commerce + language consistency + full-width information page repair.
 *
 * Runs after the legacy Latvia commerce layer so a GB market can use a real
 * GB shipping zone and English checkout/account/menu copy without requiring an
 * extra plugin. The Latvia market remains unchanged.
 */
defined('ABSPATH') || exit;

if (!defined('WPBBSHOP_V415_VERSION')) {
    define('WPBBSHOP_V415_VERSION', '4.0.15');
}

function wpbbshop_v415_is_english() {
    if (function_exists('wpbbshop_v312_is_en')) {
        return wpbbshop_v312_is_en();
    }
    if (function_exists('pll_current_language')) {
        $lang = wpbbshop_v433_current_language();
        if ($lang) { return $lang === 'en'; }
    }
    return substr((string) get_locale(), 0, 2) === 'en';
}

function wpbbshop_v415_is_uk_market() {
    if (function_exists('wpbbshop_v414_is_uk_store')) {
        return wpbbshop_v414_is_uk_store();
    }
    if (function_exists('wpbbshop_v408_resolved_market')) {
        return wpbbshop_v408_resolved_market() === 'gb';
    }
    $raw = (string) get_option('woocommerce_default_country', 'LV');
    return strtoupper((string) strtok($raw, ':')) === 'GB';
}

function wpbbshop_v415_contact_url($lang = '') {
    if (!$lang) { $lang = wpbbshop_v415_is_english() ? 'en' : 'lv'; }
    $slug = ($lang === 'en') ? 'contact' : 'kontakti';
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        return get_permalink($page);
    }
    return home_url('/' . $slug . '/');
}

/** Fix database/custom-menu Contact links as well as theme-rendered menus. */
add_filter('wp_nav_menu_objects', function ($items) {
    if (!is_array($items)) { return $items; }
    $is_en = wpbbshop_v415_is_english();
    $target = wpbbshop_v415_contact_url($is_en ? 'en' : 'lv');
    foreach ($items as $item) {
        if (!is_object($item)) { continue; }
        $title = strtolower(trim(wp_strip_all_tags((string) ($item->title ?? ''))));
        $url = (string) ($item->url ?? '');
        $is_contact = in_array($title, array('contact','kontakti'), true)
            || preg_match('~/((lv/)?kontakti|contact)/?$~i', (string) parse_url($url, PHP_URL_PATH));
        if ($is_contact) {
            $item->url = $target;
            $item->title = $is_en ? 'Contact' : 'Kontakti';
        }
    }
    return $items;
}, 9999);

/** Market-aware English delivery page content. */
function wpbbshop_v415_uk_delivery_content() {
    $settings = function_exists('wpbbshop_v408_market_settings') ? wpbbshop_v408_market_settings() : array();
    $cost = isset($settings['uk_delivery_cost']) ? max(0, (float) $settings['uk_delivery_cost']) : 4.95;
    $days = isset($settings['uk_delivery_days']) ? max(0, absint($settings['uk_delivery_days'])) : 3;
    $price = function_exists('wc_price') ? wp_strip_all_tags(wc_price($cost)) : '£' . number_format($cost, 2);
    $delivery_time = $days > 0 ? sprintf('%d working days', $days) : 'shown at checkout';

    return '<!-- wpbbshop-managed-page:v415-uk --><section class="llg-info-pro llg-info-pro--uk-delivery">'
        . '<div class="llg-info-intro"><span>DELIVERY &amp; PAYMENT</span><h2>Flexible delivery across the UK</h2><p>Choose the service that suits your order. Available carriers and the final delivery price are confirmed in the cart and checkout for the delivery address.</p></div>'
        . '<div class="llg-info-cards llg-info-cards--uk-delivery">'
        . '<article><h3>Click &amp; collect</h3><p>Free collection from 40 Brook Street, Northampton, NN1 2PE after the order is confirmed and a collection time is agreed.</p></article>'
        . '<article><h3>Royal Mail</h3><p>Tracked UK delivery for suitable parcels. Standard delivery is currently configured from ' . esc_html($price) . '.</p></article>'
        . '<article><h3>Evri</h3><p>Tracked standard parcel delivery across the UK. Availability depends on parcel size and weight.</p></article>'
        . '<article><h3>DPD Local</h3><p>Tracked courier delivery for suitable orders. Service availability is confirmed for the destination at checkout.</p></article>'
        . '</div>'
        . '<div class="llg-info-split"><div><h3>Payment methods</h3><ul><li>Card / online payment when the gateway is enabled</li><li>Bank transfer according to invoice</li><li>Cash on collection when available</li></ul></div>'
        . '<div><h3>Before dispatch</h3><ul><li>Typical standard delivery target: ' . esc_html($delivery_time) . '</li><li>We confirm product availability</li><li>Heavy or oversized products may require a separate delivery quote</li></ul></div></div>'
        . '</section>';
}

/** Keep generated English managed pages aligned with the selected UK market. */
function wpbbshop_v415_refresh_uk_managed_pages() {
    if (!wpbbshop_v415_is_uk_market()) { return; }
    $version = '4.0.15-uk-pages-1';
    if ((string) get_option('wpbbshop_v415_pages_version', '') === $version) { return; }

    $pages = array(
        'delivery-payment' => wpbbshop_v415_uk_delivery_content(),
        'contact' => function_exists('wpbbshop_v313_page_content') ? '<!-- wpbbshop-managed-page:v415-uk -->' . wpbbshop_v313_page_content('contact', 'en') : '',
    );

    foreach ($pages as $slug => $new_content) {
        if ($new_content === '') { continue; }
        $page = get_page_by_path($slug, OBJECT, 'page');
        if (!$page instanceof WP_Post) { continue; }
        $old = (string) $page->post_content;
        $looks_managed = trim($old) === ''
            || strpos($old, 'wpbbshop-managed-page:') !== false
            || strpos($old, 'llg-info-pro') !== false
            || strpos($old, 'throughout Latvia') !== false
            || strpos($old, 'Bauskas 63') !== false;
        if ($looks_managed && $old !== $new_content) {
            wp_update_post(array('ID' => $page->ID, 'post_content' => $new_content, 'post_status' => 'publish'));
        }
    }

    update_option('wpbbshop_v415_pages_version', $version, false);
}
add_action('init', 'wpbbshop_v415_refresh_uk_managed_pages', 120);
add_action('admin_init', 'wpbbshop_v415_refresh_uk_managed_pages', 2500);
add_action('after_switch_theme', 'wpbbshop_v415_refresh_uk_managed_pages', 2500);

/** Last-resort frontend rendering also stays market-aware even before DB refresh. */
add_filter('the_content', function ($content) {
    if (is_admin() || !wpbbshop_v415_is_uk_market() || !wpbbshop_v415_is_english() || !is_page()) {
        return $content;
    }
    $slug = get_post_field('post_name', get_queried_object_id());
    if ($slug === 'delivery-payment') {
        return wpbbshop_v415_uk_delivery_content();
    }
    if ($slug === 'contact' && function_exists('wpbbshop_v313_page_content')) {
        return '<!-- wpbbshop-managed-page:v415-uk -->' . wpbbshop_v313_page_content('contact', 'en');
    }
    return $content;
}, 9999);

/* -------------------------------------------------------------------------
 * UK shipping methods.
 * ---------------------------------------------------------------------- */
function wpbbshop_v415_standard_uk_delivery_cost() {
    $settings = function_exists('wpbbshop_v408_market_settings') ? wpbbshop_v408_market_settings() : array();
    return isset($settings['uk_delivery_cost']) ? max(0, (float) $settings['uk_delivery_cost']) : 4.95;
}

add_action('woocommerce_shipping_init', function () {
    if (!class_exists('WC_Shipping_Method') || class_exists('WC_Shipping_Wpbbshop_UK_Base')) { return; }

    abstract class WC_Shipping_Wpbbshop_UK_Base extends WC_Shipping_Method {
        protected $wpbb_default_title = '';
        protected $wpbb_default_cost = 0;

        protected function wpbb_setup($id, $title, $description, $instance_id, $default_cost) {
            $this->id = $id;
            $this->instance_id = absint($instance_id);
            $this->method_title = $title;
            $this->method_description = $description;
            $this->wpbb_default_title = $title;
            $this->wpbb_default_cost = (float) $default_cost;
            $this->supports = array('shipping-zones','instance-settings','instance-settings-modal');
            $this->instance_form_fields = array(
                'title' => array('title' => 'Method title', 'type' => 'text', 'default' => $title),
                'cost' => array('title' => 'Cost', 'type' => 'price', 'default' => number_format((float) $default_cost, 2, '.', '')),
            );
            $this->init_settings();
            $this->enabled = 'yes';
            $this->title = (string) $this->get_option('title', $title);
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        public function calculate_shipping($package = array()) {
            $raw = $this->get_option('cost', $this->wpbb_default_cost);
            $cost = max(0, (float) wc_format_decimal($raw));
            $this->add_rate(array(
                'id' => $this->get_rate_id(),
                'label' => $this->title ?: $this->wpbb_default_title,
                'cost' => $cost,
                'package' => $package,
            ));
        }
    }

    class WC_Shipping_Wpbbshop_UK_Pickup extends WC_Shipping_Wpbbshop_UK_Base {
        public function __construct($instance_id = 0) {
            $this->wpbb_setup('wpbbshop_uk_pickup', 'Click & collect — Northampton', 'Free collection from 40 Brook Street, Northampton, NN1 2PE.', $instance_id, 0);
        }
    }
    class WC_Shipping_Wpbbshop_Royal_Mail extends WC_Shipping_Wpbbshop_UK_Base {
        public function __construct($instance_id = 0) {
            $this->wpbb_setup('wpbbshop_royal_mail', 'Royal Mail Tracked 48', 'Tracked Royal Mail delivery for suitable UK parcels.', $instance_id, wpbbshop_v415_standard_uk_delivery_cost());
        }
    }
    class WC_Shipping_Wpbbshop_Evri extends WC_Shipping_Wpbbshop_UK_Base {
        public function __construct($instance_id = 0) {
            $this->wpbb_setup('wpbbshop_evri', 'Evri Standard', 'Tracked Evri delivery for suitable UK parcels.', $instance_id, wpbbshop_v415_standard_uk_delivery_cost());
        }
    }
    class WC_Shipping_Wpbbshop_DPD extends WC_Shipping_Wpbbshop_UK_Base {
        public function __construct($instance_id = 0) {
            $this->wpbb_setup('wpbbshop_dpd', 'DPD Local', 'Tracked DPD Local courier delivery in the UK.', $instance_id, wpbbshop_v415_standard_uk_delivery_cost());
        }
    }
});

add_filter('woocommerce_shipping_methods', function ($methods) {
    $methods['wpbbshop_uk_pickup'] = 'WC_Shipping_Wpbbshop_UK_Pickup';
    $methods['wpbbshop_royal_mail'] = 'WC_Shipping_Wpbbshop_Royal_Mail';
    $methods['wpbbshop_evri'] = 'WC_Shipping_Wpbbshop_Evri';
    $methods['wpbbshop_dpd'] = 'WC_Shipping_Wpbbshop_DPD';
    return $methods;
}, 9999);

function wpbbshop_v415_ensure_uk_zone() {
    if (!wpbbshop_v415_is_uk_market() || !class_exists('WC_Shipping_Zones') || !class_exists('WC_Shipping_Zone')) { return; }
    $version = '4.0.15-uk-zone-1';

    // Keep WooCommerce country settings in sync with the selected storefront market.
    update_option('woocommerce_allowed_countries', 'specific', false);
    update_option('woocommerce_specific_allowed_countries', array('GB'), false);
    update_option('woocommerce_ship_to_countries', 'specific', false);
    update_option('woocommerce_specific_ship_to_countries', array('GB'), false);

    if ((string) get_option('wpbbshop_v415_uk_zone_version', '') === $version) { return; }

    $zone = null;
    foreach ((array) WC_Shipping_Zones::get_zones() as $zone_data) {
        foreach ((array) ($zone_data['zone_locations'] ?? array()) as $location) {
            if (isset($location->type, $location->code) && $location->type === 'country' && strtoupper((string) $location->code) === 'GB') {
                $zone = new WC_Shipping_Zone((int) $zone_data['zone_id']);
                break 2;
            }
        }
    }
    if (!$zone) {
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name('United Kingdom');
        $zone->set_zone_order(0);
        $zone->add_location('GB', 'country');
        $zone->save();
    }

    $wanted = array('wpbbshop_uk_pickup','wpbbshop_royal_mail','wpbbshop_evri','wpbbshop_dpd');
    $present = array();
    foreach ((array) $zone->get_shipping_methods(true) as $method) {
        if (!empty($method->id)) { $present[$method->id] = true; }
    }
    foreach ($wanted as $method_id) {
        if (empty($present[$method_id])) { $zone->add_shipping_method($method_id); }
    }

    update_option('wpbbshop_v415_uk_zone_version', $version, false);
    if (function_exists('wc_delete_product_transients')) { wc_delete_product_transients(); }
}
add_action('woocommerce_init', 'wpbbshop_v415_ensure_uk_zone', 80);
add_action('admin_init', 'wpbbshop_v415_ensure_uk_zone', 9999);
add_action('after_switch_theme', 'wpbbshop_v415_ensure_uk_zone', 9999);

/** Neutralise the legacy Latvia-only runtime enforcement when the GB market is selected. */
function wpbbshop_v415_release_latvia_only_runtime() {
    if (!wpbbshop_v415_is_uk_market()) { return; }
    remove_filter('woocommerce_countries_allowed_countries', 'wpbbshop_latvia_only_country_list', 999);
    remove_filter('woocommerce_countries_shipping_countries', 'wpbbshop_latvia_only_country_list', 999);
    remove_filter('woocommerce_customer_get_billing_country', 'wpbbshop_force_latvia_customer_country', 999);
    remove_filter('woocommerce_customer_get_shipping_country', 'wpbbshop_force_latvia_customer_country', 999);
    remove_action('woocommerce_before_cart', 'wpbbshop_force_latvia_customer_session', 1);
    remove_action('woocommerce_before_checkout_form', 'wpbbshop_force_latvia_customer_session', 1);
    remove_action('admin_init', 'wpbbshop_ensure_latvia_only_shipping_settings', 68);
    remove_action('after_switch_theme', 'wpbbshop_ensure_latvia_only_shipping_settings', 68);
}
add_action('init', 'wpbbshop_v415_release_latvia_only_runtime', 1);

add_filter('woocommerce_countries_allowed_countries', function ($countries) {
    if (!wpbbshop_v415_is_uk_market()) { return $countries; }
    $label = is_array($countries) && isset($countries['GB']) ? $countries['GB'] : 'United Kingdom';
    return array('GB' => $label);
}, 10000);
add_filter('woocommerce_countries_shipping_countries', function ($countries) {
    if (!wpbbshop_v415_is_uk_market()) { return $countries; }
    $label = is_array($countries) && isset($countries['GB']) ? $countries['GB'] : 'United Kingdom';
    return array('GB' => $label);
}, 10000);
add_filter('woocommerce_customer_get_billing_country', function ($country) {
    return wpbbshop_v415_is_uk_market() ? 'GB' : $country;
}, 10000);
add_filter('woocommerce_customer_get_shipping_country', function ($country) {
    return wpbbshop_v415_is_uk_market() ? 'GB' : $country;
}, 10000);
add_filter('woocommerce_cart_shipping_packages', function ($packages) {
    if (!wpbbshop_v415_is_uk_market()) { return $packages; }
    foreach ((array) $packages as $index => $package) {
        if (!is_array($package)) { continue; }
        if (empty($package['destination']) || !is_array($package['destination'])) { $package['destination'] = array(); }
        $package['destination']['country'] = 'GB';
        $packages[$index] = $package;
    }
    return $packages;
}, 10000);

function wpbbshop_v415_force_uk_customer_session() {
    if (!wpbbshop_v415_is_uk_market() || !function_exists('WC') || !WC()->customer) { return; }
    WC()->customer->set_billing_country('GB');
    WC()->customer->set_shipping_country('GB');
}
add_action('woocommerce_before_cart', 'wpbbshop_v415_force_uk_customer_session', 10000);
add_action('woocommerce_before_checkout_form', 'wpbbshop_v415_force_uk_customer_session', 10000);

add_filter('woocommerce_shipping_package_name', function ($name) {
    if (wpbbshop_v415_is_english()) { return 'Delivery'; }
    return $name;
}, 9999);

/** English/GB checkout field labels override the legacy Latvian field setup. */
add_filter('woocommerce_checkout_fields', function ($fields) {
    if (!wpbbshop_v415_is_uk_market() || !wpbbshop_v415_is_english()) { return $fields; }
    if (!isset($fields['billing'])) { $fields['billing'] = array(); }

    $fields['billing']['billing_customer_type'] = array(
        'type' => 'select', 'label' => 'Customer type', 'required' => true, 'priority' => 5,
        'options' => array('physical' => 'Individual', 'company' => 'Company'), 'class' => array('form-row-wide')
    );
    $labels = array(
        'billing_first_name' => 'First name',
        'billing_last_name' => 'Last name',
        'billing_company' => 'Company name',
        'billing_phone' => 'Phone number',
        'billing_email' => 'Email address',
    );
    foreach ($labels as $key => $label) {
        if (isset($fields['billing'][$key])) { $fields['billing'][$key]['label'] = $label; }
    }
    $fields['billing']['billing_company_id'] = array('type'=>'text','label'=>'Company / VAT number','required'=>false,'priority'=>31,'class'=>array('form-row-wide','wpbbshop-company-field'));
    $fields['billing']['billing_legal_address'] = array('type'=>'textarea','label'=>'Registered address','required'=>false,'priority'=>32,'class'=>array('form-row-wide','wpbbshop-company-field'),'custom_attributes'=>array('rows'=>2));
    $fields['billing']['billing_country'] = array('type'=>'hidden','required'=>true,'priority'=>39,'default'=>'GB','class'=>array('wpbbshop-country-fixed'));
    if (isset($fields['shipping']['shipping_country'])) {
        $fields['shipping']['shipping_country']['type'] = 'hidden';
        $fields['shipping']['shipping_country']['default'] = 'GB';
    }
    return $fields;
}, 10000);

add_filter('woocommerce_checkout_posted_data', function ($data) {
    if (wpbbshop_v415_is_uk_market()) {
        $data['billing_country'] = 'GB';
        $data['shipping_country'] = 'GB';
    }
    return $data;
}, 10000);

function wpbbshop_v415_is_uk_pickup_method($method) {
    if (function_exists('wpbbshop_shipping_method_base_id')) { $method = wpbbshop_shipping_method_base_id($method); }
    return $method === 'wpbbshop_uk_pickup';
}

add_action('woocommerce_after_checkout_validation', function ($data, $errors) {
    if (!wpbbshop_v415_is_uk_market()) { return; }

    // The older Latvia checkout layer validates the same fields at priority 10.
    // Replace any Latvian validation copy before adding UK-specific delivery errors.
    if (wpbbshop_v415_is_english() && is_object($errors) && method_exists($errors, 'get_error_codes')) {
        $english = array(
            'billing_company' => 'Please enter the company name.',
            'billing_legal_address' => 'Please enter the registered address.',
            'billing_first_name' => 'Please enter your first name.',
            'billing_last_name' => 'Please enter your last name.',
        );
        foreach ($english as $code => $message) {
            if (in_array($code, (array) $errors->get_error_codes(), true)) {
                $errors->remove($code);
                $errors->add($code, $message);
            }
        }
    }

    $method = '';
    if (!empty($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
        $method = wc_clean(reset($_POST['shipping_method']));
    }
    if (wpbbshop_v415_is_uk_pickup_method($method)) { return; }
    if (empty($_POST['wpbbshop_delivery_address'])) { $errors->add('wpbbshop_delivery_address_uk', 'Please enter the delivery address.'); }
    if (empty($_POST['wpbbshop_delivery_city'])) { $errors->add('wpbbshop_delivery_city_uk', 'Please enter the town or city.'); }
    if (empty($_POST['wpbbshop_delivery_postcode'])) { $errors->add('wpbbshop_delivery_postcode_uk', 'Please enter the postcode.'); }
}, 10000, 2);

add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!wpbbshop_v415_is_uk_market()) { return; }
    $order->set_billing_country('GB');
    $order->set_shipping_country('GB');
    $method = '';
    if (!empty($_POST['shipping_method']) && is_array($_POST['shipping_method'])) { $method = wc_clean(reset($_POST['shipping_method'])); }
    if (!wpbbshop_v415_is_uk_pickup_method($method)) {
        $order->set_shipping_address_1(isset($_POST['wpbbshop_delivery_address']) ? sanitize_text_field(wp_unslash($_POST['wpbbshop_delivery_address'])) : '');
        $order->set_shipping_city(isset($_POST['wpbbshop_delivery_city']) ? sanitize_text_field(wp_unslash($_POST['wpbbshop_delivery_city'])) : '');
        $order->set_shipping_postcode(isset($_POST['wpbbshop_delivery_postcode']) ? sanitize_text_field(wp_unslash($_POST['wpbbshop_delivery_postcode'])) : '');
    }
}, 10000, 2);

/** Language-consistent WooCommerce account menu. */
add_filter('woocommerce_account_menu_items', function ($items) {
    $en = wpbbshop_v415_is_english();
    $labels = $en ? array(
        'dashboard'=>'Dashboard','orders'=>'Orders','downloads'=>'Downloads','edit-address'=>'Addresses',
        'payment-methods'=>'Payment methods','edit-account'=>'Account details','customer-logout'=>'Log out'
    ) : array(
        'dashboard'=>'Pārskats','orders'=>'Pasūtījumi','downloads'=>'Lejupielādes','edit-address'=>'Adreses',
        'payment-methods'=>'Maksājumu veidi','edit-account'=>'Konta informācija','customer-logout'=>'Izrakstīties'
    );
    foreach ($items as $key => $label) {
        if (isset($labels[$key])) { $items[$key] = $labels[$key]; }
    }
    return $items;
}, 9999);

/** English payment titles/descriptions in the UK storefront. */
add_filter('woocommerce_gateway_title', function ($title, $gateway_id) {
    if (!wpbbshop_v415_is_uk_market() || !wpbbshop_v415_is_english()) { return $title; }
    if ($gateway_id === 'cod') { return 'Cash on collection'; }
    if ($gateway_id === 'bacs') { return 'Bank transfer'; }
    if (function_exists('wpbbshop_gateway_is_everypay') && wpbbshop_gateway_is_everypay($gateway_id)) { return 'Secure online payment'; }
    return $title;
}, 9999, 2);

add_filter('woocommerce_no_available_payment_methods_message', function ($message) {
    if (wpbbshop_v415_is_uk_market() && wpbbshop_v415_is_english()) {
        return 'No payment method is currently available for this order. Please contact WP BB Home & Garden for help.';
    }
    return $message;
}, 9999);

add_filter('woocommerce_gateway_description', function ($description, $gateway_id) {
    if (!wpbbshop_v415_is_uk_market() || !wpbbshop_v415_is_english()) { return $description; }
    if ($gateway_id === 'cod') { return 'Available for Click & collect in Northampton.'; }
    if ($gateway_id === 'bacs') { return 'Pay by bank transfer using the order/invoice reference.'; }
    if (function_exists('wpbbshop_gateway_is_everypay') && wpbbshop_gateway_is_everypay($gateway_id)) { return 'Secure online card or bank payment through the enabled payment gateway.'; }
    return $description;
}, 9999, 2);

add_filter('woocommerce_available_payment_gateways', function ($gateways) {
    if (!wpbbshop_v415_is_uk_market() || !is_array($gateways)) { return $gateways; }
    $method = function_exists('wpbbshop_current_shipping_method') ? wpbbshop_current_shipping_method() : '';
    if (wpbbshop_v415_is_uk_pickup_method($method)) {
        if (!isset($gateways['cod']) && function_exists('WC') && WC()->payment_gateways()) {
            $all = WC()->payment_gateways()->payment_gateways();
            if (isset($all['cod']) && isset($all['cod']->enabled) && $all['cod']->enabled === 'yes') { $gateways['cod'] = $all['cod']; }
        }
    } elseif (isset($gateways['cod'])) {
        unset($gateways['cod']);
    }
    return $gateways;
}, 9999);

/** Body marker for the market-specific final CSS/JS. */
add_filter('body_class', function ($classes) {
    if (wpbbshop_v415_is_uk_market()) { $classes[] = 'wpbb-v415-market-uk'; }
    if (wpbbshop_v415_is_english()) { $classes[] = 'wpbb-v415-lang-en'; }
    return $classes;
}, 9999);

add_action('wp_enqueue_scripts', function () {
    $css = get_stylesheet_directory() . '/assets/css/v415-uk-commerce-language.css';
    $js  = get_stylesheet_directory() . '/assets/js/v415-uk-commerce-language.js';
    if (is_readable($css)) {
        wp_enqueue_style('wpbbshop-v415-uk-commerce-language', get_stylesheet_directory_uri() . '/assets/css/v415-uk-commerce-language.css', array('wpbbshop-v414-final-ui'), (string) filemtime($css));
    }
    if (is_readable($js)) {
        wp_enqueue_script('wpbbshop-v415-uk-commerce-language', get_stylesheet_directory_uri() . '/assets/js/v415-uk-commerce-language.js', array(), (string) filemtime($js), true);
        wp_localize_script('wpbbshop-v415-uk-commerce-language', 'WpbbV415', array(
            'isUk' => wpbbshop_v415_is_uk_market(),
            'isEn' => wpbbshop_v415_is_english(),
            'contactUrl' => wpbbshop_v415_contact_url('en'),
        ));
    }
}, PHP_INT_MAX);
