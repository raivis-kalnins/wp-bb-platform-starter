<?php
if (!defined('ABSPATH')) exit;


function wpbb_default_text($text) {
    return did_action('init') ? __($text, 'wp-bbuilder') : $text;
}

function wpbb_defaults() {
    return [
        'enabled_blocks' => [
            'accordion' => 1,'accordion-item' => 1,'alert' => 1,'badge' => 1,'breadcrumb' => 1,'button' => 1,'card' => 1,'cards' => 1,'column' => 1,
            'cta-card' => 1,'cta-section' => 1,'dynamic-form' => 1,'google-map' => 1,'list-group' => 1,'menu-option' => 1,'navbar' => 1,'progress' => 1,
            'row' => 1,'section' => 1,'sitemap' => 1,'soc-follow-block' => 1,'soc-share' => 1,'social-feeds' => 1,'soc-feed' => 1,'spinner' => 1,
            'tab-item' => 1,'table' => 1,'tabs' => 1,'video' => 1,'file' => 1,'inline-svg' => 1,'swiper' => 1,
            'weather' => 1,'varda-dienas' => 1,'ajax-search' => 1,'pricecards' => 1,'catalogue' => 1,
            'code-display' => 1,'countdown-timer' => 1,'chart' => 1,'fun-fact' => 1,'mailchimp' => 1,'bootstrap-div' => 1,
            'feature-list' => 1,'timeline' => 1,'custom-embed' => 1,'ai-content' => 1,'login-register' => 1,
            'load-more' => 1,'contact-links' => 1,'events' => 1,'testimonials' => 1,'blog-filter' => 1,'booking-calendar' => 1,'icon-card' => 1,
        ],
        'disable_core_group' => 1,'disable_core_columns' => 1,'disable_core_column' => 1,
        'disable_core_table' => 1,'disable_core_embed' => 0,'disable_core_gallery' => 0,
        'disable_core_image' => 0,'disable_core_cover' => 0,'disable_core_media_text' => 0,'disable_core_audio' => 0,'disable_core_file' => 0,
        'disable_core_buttons' => 0,'disable_core_button' => 0,'disable_core_query' => 0,
        'load_bootstrap_css' => 1,'load_bootstrap_js' => 1,'load_shared_css' => 1,'load_bootstrap_editor_css' => 0,'force_bootstrap_enqueue' => 0,'show_bootstrap_debug' => 0,'show_quick_edit_toggle' => 0,'quick_edit_new_tab' => 1,'frontend_editor_enabled' => 0,'frontend_editor_editable_blocks' => ['core/paragraph','core/heading'],'frontend_editor_restricted_roles' => [],'save_entries' => 1,'show_entries_menu' => 0,
        'acf_field_block_enabled' => 1,'acf_field_allow_options' => 1,
        'bootstrap_css_mode' => 'auto',
        'bootstrap_css_components' => [],
        'bootstrap_js_mode' => 'auto',
        'bootstrap_js_components' => [],
        'default_recipient_email' => get_option('admin_email'),
        'default_success_message' => wpbb_default_text('Thank you for your submission!'),
        'default_error_message' => wpbb_default_text('Something went wrong. Please try again.'),
        'default_validation_text' => wpbb_default_text('Please fill in all required fields correctly.'),
        'button_class' => 'btn btn-primary','form_class' => 'wpbb-form','admin_max_width' => '1400px',
        'hcaptcha_enabled' => 0,'recaptcha_enabled' => 0,'hcaptcha_site_key' => '','hcaptcha_secret_key' => '','recaptcha_site_key' => '','recaptcha_secret_key' => '',
        'smtp_enabled' => 0,
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_from_email' => '',
        'smtp_from_name' => '',
        'form_honeypot_enabled' => 1,
        'form_min_submit_time' => '3',
        'ordering_enabled' => 1,
        'ordering_default_enabled' => 1,
        'ordering_post_types' => ['page' => 1, 'post' => 1, 'product' => 1],
        'form_spam_message' => wpbb_default_text('Your submission was blocked as spam. Please try again.'),
        'default_label_color' => '#334155','default_input_border_color' => '#cbd5e1','default_button_bg' => '#2563eb','default_button_text' => '#ffffff',
        'bootstrap_optimize_frontend' => 1,'bootstrap_enable_utilities' => 1,'bootstrap_allow_custom_classes' => 1,'aggregate_inline_block_css' => 1,'admin_scss' => '','admin_compiled_css' => '',
        'whatsapp_enabled' => 0,
        'whatsapp_profile_name' => 'WhatsApp',
        'whatsapp_phone' => '',
        'whatsapp_message' => 'Hi, I would like to chat.',
        'whatsapp_position' => 'bottom-right',
        'whatsapp_bg' => '#25D366',
        'whatsapp_text' => '#ffffff',
        'cookie_consent_enabled' => 0,
        'cookie_consent_text' => 'We use cookies to improve your experience.',
        'cookie_accept_text' => 'Accept',
        'cookie_reject_text' => 'Reject',
        'cookie_policy_url' => '',
        'cookie_position' => 'bottom',
        'cookie_bg' => '#111827',
        'cookie_text_color' => '#ffffff',
        'cookie_button_bg' => '#2563eb',
        'cookie_button_text' => '#ffffff',
        'google_analytics_enabled' => 0,
        'google_analytics_head' => '',
        'custom_scss' => '',
        'compiled_css' => '',
        'meta_header_code' => '',
        'global_footer_code' => '',
        'weather_api_key' => '',
        'weather_units' => 'metric',
        'page_redirect_rules' => '[]',
        'admin_spellcheck_enabled' => 0,
        'admin_spellcheck_language' => 'en',
        'redirect_wp_admin_home' => 0,
        'enable_custom_login_slug' => 0,
        'custom_login_slug' => 'wp-admin',
    ];
}
function wpbb_get_option($key, $default = null) {
    $opts = get_option('wpbb_settings', []);
    $defaults = wpbb_defaults();
    $value = isset($opts[$key]) ? $opts[$key] : ($defaults[$key] ?? $default);

    /**
     * Allow the active child theme to adapt presentation-only defaults (for
     * example button/consent colours) without copying credentials or changing
     * the saved BBuilder settings. Security-sensitive values such as hCaptcha
     * keys still come from the same option store unless a site deliberately
     * adds its own filter.
     */
    return apply_filters('wpbb_option', $value, $key, $default, $opts, $defaults);
}
function wpbb_is_block_enabled($slug) {
    // Critical dynamic template blocks must remain registered on the front end.
    // Existing sites can have an older saved enabled_blocks option that misses
    // newer slugs; if wpbb/soc-feed is not registered, self-closing template
    // blocks render as empty HTML even though the admin preview works.
    if ($slug === 'soc-feed') return true;
    $enabled = wpbb_get_option('enabled_blocks', []);
    $defaults = wpbb_defaults();
    $default_enabled = !empty($defaults['enabled_blocks'][$slug]);
    if (empty($enabled)) return $default_enabled || empty($defaults['enabled_blocks']);
    return array_key_exists($slug, $enabled) ? !empty($enabled[$slug]) : $default_enabled;
}
function wpbb_get_blocks_list() {
    return ['accordion','accordion-item','alert','badge','breadcrumb','button','card','cards','column','cta-card','cta-section','dynamic-form','google-map','list-group','menu-option','navbar','progress','row','section','sitemap','soc-follow-block','soc-share','social-feeds','soc-feed','spinner','tab-item','table','tabs','video','file','inline-svg','swiper','weather','varda-dienas','ajax-search','pricecards','catalogue','code-display','countdown-timer','chart','fun-fact','mailchimp','bootstrap-div','feature-list','timeline','custom-embed','ai-content','login-register','load-more','contact-links','events','testimonials','blog-filter','booking-calendar','icon-card'];
}
function wpbb_get_acf_blocks_list() { return ['wpbb-hero','wpbb-gallery']; }
function wpbb_parse_fields_json($json) {
    $decoded = json_decode((string) $json, true);
    return is_array($decoded) ? $decoded : [];
}
function wpbb_hex_color($value, $fallback = '#000000') {
    $value = sanitize_hex_color($value);
    return $value ?: $fallback;
}


function wpbb_translate_string($string, $context = 'wp-bbuilder') {
    if (function_exists('pll__')) {
        return pll__($string);
    }
    return $string;
}


if (!function_exists('wpbb_get_theme_settings_url')) {
    function wpbb_get_theme_settings_url() {
        return apply_filters('wpbb_theme_settings_url', admin_url('options-general.php?page=wp-theme-settings'));
    }
}

if (!function_exists('wpbb_get_theme_container_width')) {
    function wpbb_get_theme_container_width($default = '1400px') {
        $value = apply_filters('wpbb_theme_container_width', null);

        if (is_string($value) && trim($value) !== '') {
            $value = trim($value);
        } else {
            $candidates = [
                get_theme_mod('container_width', ''),
                get_theme_mod('container_max_width', ''),
                get_theme_mod('site_container_width', ''),
                get_option('wpbb_theme_container_width', ''),
                get_option('bbtheme_container_width', ''),
            ];

            $value = '';
            foreach ($candidates as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    $value = trim($candidate);
                    break;
                }
            }
        }

        if (!is_string($value) || trim($value) === '') {
            $value = $default;
        }

        $value = preg_replace('/[^0-9a-zA-Z\-\.\%\(\), \/]/', '', (string) $value);
        return $value !== '' ? $value : $default;
    }
}


if (!function_exists('wpbb_verify_hcaptcha_token')) {
    /**
     * Verify an hCaptcha token using the credentials saved in WP BBuilder.
     * Returns true when hCaptcha is disabled, or WP_Error on a failed challenge.
     */
    function wpbb_verify_hcaptcha_token($token, $remote_ip = '') {
        if (!wpbb_get_option('hcaptcha_enabled', 0)) return true;
        $secret = sanitize_text_field((string) wpbb_get_option('hcaptcha_secret_key', ''));
        $site_key = sanitize_text_field((string) wpbb_get_option('hcaptcha_site_key', ''));
        if ($secret === '' || $site_key === '') {
            return new WP_Error('wpbb_hcaptcha_not_configured', __('hCaptcha is enabled but its site/secret key is incomplete.', 'wp-bbuilder'));
        }
        $token = sanitize_text_field((string) $token);
        if ($token === '') return new WP_Error('wpbb_hcaptcha_missing_token', __('Please complete the hCaptcha challenge.', 'wp-bbuilder'));
        $body = ['secret' => $secret, 'sitekey' => $site_key, 'response' => $token];
        if ($remote_ip !== '') $body['remoteip'] = sanitize_text_field((string) $remote_ip);
        $response = wp_remote_post('https://hcaptcha.com/siteverify', ['timeout' => 12, 'body' => $body]);
        if (is_wp_error($response)) return $response;
        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($decoded['success'])) return new WP_Error('wpbb_hcaptcha_failed', __('hCaptcha verification failed. Please try again.', 'wp-bbuilder'));
        return true;
    }
}
