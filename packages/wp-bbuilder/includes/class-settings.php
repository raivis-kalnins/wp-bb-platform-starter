<?php
if (!defined('ABSPATH')) exit;
final class WPBB_Settings {
    private static $instance = null;
    public static function instance() { if (self::$instance === null) self::$instance = new self(); return self::$instance; }
    private function __construct() { add_action('admin_init', [$this, 'register_settings']); }
    public function register_settings() {
        register_setting('wpbb_settings_group', 'wpbb_settings', ['type'=>'array','default'=>wpbb_defaults(),'sanitize_callback'=>[$this,'sanitize']]);
    }
    public function sanitize($input) {
        $input = is_array($input) ? $input : [];
        $stored = get_option('wpbb_settings', []);
        $stored = is_array($stored) ? $stored : [];
        $out = wpbb_defaults();
        $enabled = [];
        foreach (wpbb_get_blocks_list() as $slug) $enabled[$slug] = !empty($input['enabled_blocks'][$slug]) ? 1 : 0;
        $out['enabled_blocks'] = $enabled;
        // Layout policy: native Group/Columns/Column stay disabled; BBuilder owns layout.
        $input['disable_core_group'] = 1;
        $input['disable_core_columns'] = 1;
        $input['disable_core_column'] = 1;
        foreach (['disable_core_group','disable_core_columns','disable_core_column','disable_core_table','disable_core_embed','disable_core_gallery','disable_core_image','disable_core_cover','disable_core_media_text','disable_core_buttons','disable_core_button','disable_core_query','load_bootstrap_css','load_bootstrap_js','load_shared_css','load_bootstrap_editor_css','force_bootstrap_enqueue','acf_field_block_enabled','acf_field_allow_options','show_bootstrap_debug','show_quick_edit_toggle','quick_edit_new_tab','frontend_editor_enabled','save_entries','bootstrap_optimize_frontend','bootstrap_enable_utilities','bootstrap_allow_custom_classes','cookie_consent_enabled','google_analytics_enabled','admin_spellcheck_enabled','smtp_enabled','form_honeypot_enabled','hcaptcha_enabled','recaptcha_enabled','whatsapp_enabled','ordering_enabled','ordering_default_enabled','redirect_wp_admin_home','enable_custom_login_slug'] as $flag) {
            $out[$flag] = !empty($input[$flag]) ? 1 : 0;
        }

        $out['bootstrap_css_mode'] = in_array(($input['bootstrap_css_mode'] ?? 'auto'), ['auto','full','custom','grid','utilities'], true) ? $input['bootstrap_css_mode'] : 'auto';
        $out['bootstrap_js_mode'] = in_array(($input['bootstrap_js_mode'] ?? 'auto'), ['auto','full','custom'], true) ? $input['bootstrap_js_mode'] : 'auto';
        $out['smtp_encryption'] = in_array(($input['smtp_encryption'] ?? 'tls'), ['none','ssl','tls'], true) ? $input['smtp_encryption'] : 'tls';

        $allowed_bootstrap_css_components = array_keys(WPBBuilder_Bootstrap::get_css_component_choices());
        $allowed_bootstrap_js_components = array_keys(WPBBuilder_Bootstrap::get_js_component_choices());
        $out['bootstrap_css_components'] = [];
        $out['bootstrap_js_components'] = [];
        if (!empty($input['bootstrap_css_components']) && is_array($input['bootstrap_css_components'])) {
            foreach ($input['bootstrap_css_components'] as $component) {
                $component = sanitize_key($component);
                if (in_array($component, $allowed_bootstrap_css_components, true)) $out['bootstrap_css_components'][] = $component;
            }
            $out['bootstrap_css_components'] = array_values(array_unique($out['bootstrap_css_components']));
        }
        if (!empty($input['bootstrap_js_components']) && is_array($input['bootstrap_js_components'])) {
            foreach ($input['bootstrap_js_components'] as $component) {
                $component = sanitize_key($component);
                if (in_array($component, $allowed_bootstrap_js_components, true)) $out['bootstrap_js_components'][] = $component;
            }
            $out['bootstrap_js_components'] = array_values(array_unique($out['bootstrap_js_components']));
        }



        $allowed_frontend_blocks = class_exists('WPBB_Frontend_Editor') ? array_keys(WPBB_Frontend_Editor::supported_blocks()) : ['core/paragraph','core/heading'];
        $out['frontend_editor_editable_blocks'] = [];
        if (!empty($input['frontend_editor_editable_blocks']) && is_array($input['frontend_editor_editable_blocks'])) {
            foreach ($input['frontend_editor_editable_blocks'] as $block_name) {
                $block_name = sanitize_text_field((string) $block_name);
                if (in_array($block_name, $allowed_frontend_blocks, true)) {
                    $out['frontend_editor_editable_blocks'][] = $block_name;
                }
            }
            $out['frontend_editor_editable_blocks'] = array_values(array_unique($out['frontend_editor_editable_blocks']));
        } else {
            $out['frontend_editor_editable_blocks'] = $out['frontend_editor_enabled'] ? [] : (wpbb_defaults()['frontend_editor_editable_blocks'] ?? ['core/paragraph','core/heading']);
        }

        $out['frontend_editor_restricted_roles'] = [];
        $valid_roles = function_exists('wp_roles') ? array_keys(wp_roles()->roles) : [];
        if (!empty($input['frontend_editor_restricted_roles']) && is_array($input['frontend_editor_restricted_roles'])) {
            foreach ($input['frontend_editor_restricted_roles'] as $role_slug) {
                $role_slug = sanitize_key((string) $role_slug);
                if ($role_slug !== 'administrator' && in_array($role_slug, $valid_roles, true)) {
                    $out['frontend_editor_restricted_roles'][] = $role_slug;
                }
            }
            $out['frontend_editor_restricted_roles'] = array_values(array_unique($out['frontend_editor_restricted_roles']));
        }

        $out['ordering_post_types'] = [];
        $available_ordering_types = get_post_types(['show_ui' => true], 'names');
        if (!empty($input['ordering_post_types']) && is_array($input['ordering_post_types'])) {
            foreach ($input['ordering_post_types'] as $post_type => $enabled) {
                $post_type = sanitize_key($post_type);
                if (in_array($post_type, $available_ordering_types, true) && !empty($enabled)) {
                    $out['ordering_post_types'][$post_type] = 1;
                }
            }
        }
        if (empty($out['ordering_post_types']) && !empty($out['ordering_default_enabled'])) {
            foreach (['page', 'post', 'product'] as $default_post_type) {
                if (in_array($default_post_type, $available_ordering_types, true)) {
                    $out['ordering_post_types'][$default_post_type] = 1;
                }
            }
        }

        $supported_spellcheck_languages = array_keys(WPBB_Spellcheck::get_supported_languages());
        $out['admin_spellcheck_language'] = in_array(($input['admin_spellcheck_language'] ?? 'en'), $supported_spellcheck_languages, true) ? $input['admin_spellcheck_language'] : 'en';

        foreach (['default_success_message','default_error_message','default_validation_text','button_class','form_class','admin_max_width','hcaptcha_site_key','hcaptcha_secret_key','recaptcha_site_key','recaptcha_secret_key','smtp_host','smtp_username','smtp_from_name','form_spam_message','whatsapp_profile_name','whatsapp_phone','whatsapp_message','whatsapp_position','cookie_consent_text','cookie_accept_text','cookie_reject_text','cookie_position','custom_login_slug'] as $field) {
            $out[$field] = sanitize_text_field($input[$field] ?? ($out[$field] ?? ''));
        }
        $out['custom_login_slug'] = sanitize_title(trim((string) ($out['custom_login_slug'] ?? 'wp-admin'), '/ '));
        if ($out['custom_login_slug'] === '') $out['custom_login_slug'] = 'wp-admin';

        $out['default_recipient_email'] = sanitize_email($input['default_recipient_email'] ?? ($out['default_recipient_email'] ?? ''));
        $out['smtp_from_email'] = sanitize_email($input['smtp_from_email'] ?? ($out['smtp_from_email'] ?? ''));
        $out['cookie_policy_url'] = esc_url_raw($input['cookie_policy_url'] ?? ($out['cookie_policy_url'] ?? ''));
        $smtp_port = (int) preg_replace('/[^0-9]/', '', (string) ($input['smtp_port'] ?? ($out['smtp_port'] ?? '587')));
        if ($smtp_port < 1 || $smtp_port > 65535) {
            $smtp_port = $out['smtp_encryption'] === 'ssl' ? 465 : ($out['smtp_encryption'] === 'none' ? 25 : 587);
        }
        $out['smtp_port'] = (string) $smtp_port;
        $out['form_min_submit_time'] = preg_replace('/[^0-9]/', '', (string) ($input['form_min_submit_time'] ?? ($out['form_min_submit_time'] ?? '3')));
        $new_smtp_password = isset($input['smtp_password']) ? (string) wp_unslash($input['smtp_password']) : '';
        if (!empty($input['smtp_clear_password'])) {
            $out['smtp_password'] = '';
        } elseif ($new_smtp_password !== '') {
            $out['smtp_password'] = $new_smtp_password;
        } else {
            $out['smtp_password'] = (string) ($stored['smtp_password'] ?? '');
        }

        if (!empty($out['smtp_enabled']) && trim((string) $out['smtp_host']) === '') {
            add_settings_error('wpbb_settings', 'wpbb_smtp_host_required', __('SMTP is enabled, but the SMTP host is empty.', 'wp-bbuilder'), 'error');
        }
        if (!empty($out['smtp_enabled']) && $out['smtp_username'] !== '' && $out['smtp_password'] === '') {
            add_settings_error('wpbb_settings', 'wpbb_smtp_password_required', __('SMTP authentication has a username but no password.', 'wp-bbuilder'), 'error');
        }

        foreach (['google_analytics_head','custom_scss','compiled_css','admin_scss','admin_compiled_css','meta_header_code','global_footer_code','page_redirect_rules'] as $field) {
            $out[$field] = isset($input[$field]) ? (string) wp_unslash($input[$field]) : ($out[$field] ?? '');
        }
        foreach (['default_label_color','default_input_border_color','default_button_bg','default_button_text','whatsapp_bg','whatsapp_text','cookie_bg','cookie_text_color','cookie_button_bg','cookie_button_text'] as $field) {
            $out[$field] = sanitize_hex_color($input[$field] ?? $out[$field]) ?: $out[$field];
        }
        $out['show_entries_menu'] = 0;
        return $out;
    }
}
