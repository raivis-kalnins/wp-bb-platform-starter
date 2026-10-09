<?php
if (!defined('ABSPATH')) exit;

final class WPBB_Blocks {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', ['WPBBuilder_Bootstrap', 'register_assets']);
        add_action('init', [$this, 'register_assets']);
        add_action('init', [$this, 'register_blocks']);
        add_filter('block_categories_all', [$this, 'register_category'], 10, 1);
        add_filter('allowed_block_types_all', [$this, 'filter_allowed_blocks'], 20, 2);
        add_action('enqueue_block_assets', [$this, 'enqueue_frontend_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_code_assets']);
        add_action('wp_ajax_wpbb_ajax_search', [$this, 'ajax_search']);
        add_action('wp_ajax_wpbb_soc_feed_preview', [$this, 'ajax_soc_feed_preview']);
        add_action('wp_ajax_nopriv_wpbb_ajax_search', [$this, 'ajax_search']);
        add_action('wp_ajax_wpbb_load_more', [$this, 'ajax_load_more']);
        add_action('wp_ajax_nopriv_wpbb_load_more', [$this, 'ajax_load_more']);
        add_action('wp_ajax_wpbb_blog_filter', [$this, 'ajax_blog_filter']);
        add_action('wp_ajax_nopriv_wpbb_blog_filter', [$this, 'ajax_blog_filter']);
        add_action('wp_ajax_wpbb_submit_booking', [$this, 'ajax_submit_booking']);
        add_action('wp_ajax_nopriv_wpbb_submit_booking', [$this, 'ajax_submit_booking']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Legacy native-layout conversion can discard WordPress block attributes
        // that do not have an exact BBuilder equivalent. Keep it available for
        // controlled migrations, but never rewrite existing content implicitly.
        if (apply_filters('wpbb_enable_legacy_layout_migration', false)) {
            add_filter('wp_insert_post_data', [$this, 'normalize_legacy_layout_blocks_on_save'], 30, 2);
            add_action('admin_init', [$this, 'migrate_legacy_layout_blocks_once'], 45);
        }
    }
    public function register_post_type() {
        register_post_type('wpbb_entry', [
            'labels' => [
                'name' => __('Form Entries', 'wp-bbuilder'),
                'singular_name' => __('Form Entry', 'wp-bbuilder'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-feedback',
            'supports' => ['title', 'custom-fields'],
        ]);
        register_post_type('wpbb_booking', [
            'labels' => [
                'name' => __('Bookings', 'wp-bbuilder'),
                'singular_name' => __('Booking', 'wp-bbuilder'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'options-general.php',
            'supports' => ['title', 'custom-fields'],
        ]);
    }

    
    private function wpbb_svg_icon($name) {
        $icons = [
            'facebook' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.7-1.6h1.6V4.8c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 4v2.3H8v3h2.7v8h2.8z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2.2A2.8 2.8 0 0 0 4.2 7v10A2.8 2.8 0 0 0 7 19.8h10a2.8 2.8 0 0 0 2.8-2.8V7A2.8 2.8 0 0 0 17 4.2H7zm10.5 1.6a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2.2A2.8 2.8 0 1 0 12 14.8 2.8 2.8 0 0 0 12 9.2z"/></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M6.94 8.5H4V20h2.94V8.5zM5.47 4A1.72 1.72 0 1 0 5.5 7.44 1.72 1.72 0 0 0 5.47 4zM20 12.9c0-3.1-1.66-4.54-3.88-4.54-1.8 0-2.6.99-3.05 1.68V8.5H10.1c.04 1 .04 11.5 0 11.5h2.97v-6.42c0-.34.02-.68.12-.92.27-.68.88-1.38 1.91-1.38 1.35 0 1.89 1.03 1.89 2.54V20H20v-7.1z"/></svg>',
            'x' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M18.24 2H21l-6.56 7.5L22 22h-5.93l-4.64-6.05L6.13 22H3.36l7.02-8.02L2 2h6.08l4.19 5.53L18.24 2zm-1.04 18h1.64L7.19 3.9H5.48L17.2 20z"/></svg>',
            'youtube' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M23 12s0-3.5-.45-5.2a2.7 2.7 0 0 0-1.9-1.9C18.9 4.4 12 4.4 12 4.4s-6.9 0-8.65.5a2.7 2.7 0 0 0-1.9 1.9C1 8.5 1 12 1 12s0 3.5.45 5.2a2.7 2.7 0 0 0 1.9 1.9c1.75.5 8.65.5 8.65.5s6.9 0 8.65-.5a2.7 2.7 0 0 0 1.9-1.9C23 15.5 23 12 23 12zM10 15.5v-7l6 3.5-6 3.5z"/></svg>',
            'tiktok' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M14 3c.4 1.8 1.5 3.3 3.2 4.2 1 .5 2 .8 3.1.8v3.2c-1.5 0-3-.4-4.3-1.1v5.8c0 3.3-2.7 6.1-6.1 6.1S3.8 19.2 3.8 15.8 6.5 9.7 9.9 9.7c.3 0 .6 0 .9.1V13a3 3 0 0 0-.9-.1 2.9 2.9 0 0 0 0 5.8 2.9 2.9 0 0 0 2.9-2.9V3H14z"/></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M16.6 14.3c-.3-.2-1.8-.9-2-.9-.3-.1-.4-.1-.6.2s-.7.9-.8 1c-.1.1-.3.2-.5.1-.3-.2-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.4-1.6-.1-.3 0-.4.1-.5l.4-.5.2-.4c.1-.1.1-.3 0-.4 0-.1-.6-1.5-.8-2-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.6.3-.2.3-.8.8-.8 1.9s.8 2.1.9 2.3c.1.2 1.6 2.4 3.8 3.4 2.3 1 2.3.7 2.8.7.4-.1 1.5-.6 1.7-1.1.2-.6.2-1 .1-1.1-.1-.1-.3-.2-.6-.3zM12 2.2A9.8 9.8 0 0 0 3.7 17.3L2.2 21.8l4.6-1.5A9.8 9.8 0 1 0 12 2.2zm0 17.8c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-2.7.9.9-2.6-.2-.3A8 8 0 1 1 12 20z"/></svg>',
            'email' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 5h18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm0 2v.5l9 5.6 9-5.6V7H3zm18 10V9.8l-8.5 5.3a1 1 0 0 1-1 0L3 9.8V17h18z"/></svg>',
        ];
        return $icons[$name] ?? '<span class="wpbb-social-icon__glyph">*</span>';
    }


    private function wpbb_compile_scoped_scss($selector, $scss) {
        $scss = trim((string) $scss);
        if ($scss === '') return '';

        $scss = preg_replace('!/\*.*?\*/!s', '', $scss);

        $vars = [];
        if (preg_match_all('/\$([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $scss, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $row) {
                $vars[$row[1]] = trim($row[2]);
            }
        }
        $scss = preg_replace('/\$[a-zA-Z0-9_-]+\s*:\s*[^;]+;/', '', $scss);
        foreach ($vars as $name => $value) {
            $scss = preg_replace('/\$' . preg_quote($name, '/') . '\b/', $value, $scss);
        }

        $scss = trim(preg_replace('/\s+/', ' ', $scss));
        if ($scss === '') return '';

        if (strpos($scss, '{') === false) {
            return $selector . '{' . trim($scss, '; ') . '}';
        }

        $leading = ltrim($scss);
        $first = substr($leading, 0, 1);
        if (
            $first === '&' ||
            $first === ':' ||
            preg_match('/^[a-z-]+\s*:/i', $leading)
        ) {
            if (strpos($leading, '&') !== false) {
                $scss = str_replace('&', $selector, $scss);
            }
            $scss = $selector . '{' . $scss . '}';
        }

        $normalize_selector = function ($parent, $selector_text) {
            $parts = array_map('trim', explode(',', (string) $selector_text));
            $selectors = [];
            foreach ($parts as $part) {
                if ($part === '') continue;
                if ($parent === '') {
                    $selectors[] = $part;
                } elseif (strpos($part, '&') !== false) {
                    $selectors[] = str_replace('&', $parent, $part);
                } else {
                    $selectors[] = trim($parent . ' ' . $part);
                }
            }
            return implode(',', $selectors);
        };

        $extract_nested_ranges = function ($source) {
            $ranges = [];
            $len = strlen($source);
            $i = 0;
            while ($i < $len) {
                while ($i < $len && ctype_space($source[$i])) $i++;
                $start = $i;
                while ($i < $len && $source[$i] !== '{' && $source[$i] !== '}') $i++;
                if ($i < $len && $source[$i] === '{') {
                    $depth = 1;
                    $i++;
                    while ($i < $len && $depth > 0) {
                        if ($source[$i] === '{') $depth++;
                        if ($source[$i] === '}') $depth--;
                        $i++;
                    }
                    $ranges[] = [$start, $i];
                } else {
                    $i++;
                }
            }
            return $ranges;
        };

        $flatten = function ($source, $parent = '') use (&$flatten, $normalize_selector, $extract_nested_ranges, $selector) {
            $css = '';
            $len = strlen($source);
            $i = 0;

            while ($i < $len) {
                while ($i < $len && ctype_space($source[$i])) $i++;
                if ($i >= $len) break;

                $sel_start = $i;
                while ($i < $len && $source[$i] !== '{' && $source[$i] !== '}') $i++;
                if ($i >= $len || $source[$i] === '}') break;

                $selector_text = trim(substr($source, $sel_start, $i - $sel_start));
                $i++;

                $depth = 1;
                $body_start = $i;
                while ($i < $len && $depth > 0) {
                    if ($source[$i] === '{') $depth++;
                    if ($source[$i] === '}') $depth--;
                    $i++;
                }

                $body = trim(substr($source, $body_start, max(0, $i - $body_start - 1)));
                if ($selector_text === '') continue;

                $full_selector = $normalize_selector($parent, $selector_text);
                if ($full_selector === '') continue;

                $ranges = $extract_nested_ranges($body);
                $plain = '';

                if (!empty($ranges)) {
                    $cursor = 0;
                    foreach ($ranges as $range) {
                        $plain .= substr($body, $cursor, $range[0] - $cursor) . ' ';
                        $cursor = $range[1];
                    }
                    $plain .= substr($body, $cursor);
                } else {
                    $plain = $body;
                }

                $plain = trim((string) $plain);
                $plain = preg_replace('/\s*;\s*/', ';', $plain);
                $plain = preg_replace('/\s*:\s*/', ':', $plain);
                $plain = trim($plain, '; ');

                if ($plain !== '') {
                    if ($parent === '' && strpos($selector_text, '&') !== false) {
                        $css .= str_replace('&', $selector, $selector_text) . '{' . $plain . '}';
                    } else {
                        $css .= $full_selector . '{' . $plain . '}';
                    }
                }

                if (strpos($body, '{') !== false) {
                    $css .= $flatten($body, $full_selector);
                }
            }

            return $css;
        };

        $result = $flatten($scss, '');
        if ($result === '') {
            $result = str_replace('&', $selector, $scss);
        }

        $result = preg_replace('/\s+/', ' ', $result);
        $result = str_replace([' {', '{ ', '; ', ': ', ', ', ' }'], ['{', '{', ';', ':', ',', '}'], $result);

        return trim($result);
    }

    private function wpbb_responsive_spacing_attributes() {
        $attributes = [];
        foreach (['padding', 'margin'] as $prefix) {
            foreach (['Default', 'Sm', 'Md', 'Lg', 'Xl', 'Xxl'] as $bp) {
                foreach (['Top', 'Right', 'Bottom', 'Left'] as $side) {
                    $attributes[$prefix . $bp . $side] = ['type' => 'string', 'default' => ''];
                }
            }
        }
        return $attributes;
    }

    private function wpbb_responsive_background_position_attributes() {
        $attributes = [];
        foreach (['Sm', 'Md', 'Lg', 'Xl', 'Xxl'] as $bp) {
            $attributes['backgroundPosition' . $bp] = ['type' => 'string', 'default' => ''];
        }
        return $attributes;
    }

    private function wpbb_sanitize_css_value($value) {
        return trim(preg_replace('/[^#(),.% 0-9a-zA-Z\-\+*\/:_]/', '', (string) $value));
    }

    private function wpbb_normalize_spacing_css_value($value, $property) {
        $value = trim((string) $value);
        if ($value === '') return '';

        $is_margin = (strpos((string) $property, 'margin') === 0);
        $lower = strtolower($value);
        if ($lower === 'auto') return $is_margin ? 'auto' : '';

        // WordPress native spacing presets are saved as var:preset|spacing|40.
        if (preg_match('/^var:preset\|spacing\|([a-z0-9_-]+)$/i', $value, $match)) {
            return 'var(--wp--preset--spacing--' . sanitize_html_class($match[1]) . ')';
        }

        // Keep CSS custom properties and calc/clamp/min/max values, while still blocking
        // negative padding. These are common outputs from the block editor spacing panel.
        if (preg_match('/^(var|calc|min|max|clamp)\(/i', $value)) {
            if (!$is_margin && preg_match('/(^|[,(\s])-\s*\d/', $value)) return '';
            return $this->wpbb_sanitize_css_value($value);
        }

        if (!preg_match('/^-?\d*\.?\d+(px|%|em|rem|vw|vh)$/i', $value)) return '';
        if (!$is_margin && strpos($value, '-') === 0) return '';

        return strtolower($value);
    }

    private function wpbb_native_spacing_value($attributes, $group, $side) {
        if (empty($attributes['style']) || !is_array($attributes['style'])) return '';
        if (empty($attributes['style']['spacing']) || !is_array($attributes['style']['spacing'])) return '';
        if (empty($attributes['style']['spacing'][$group])) return '';
        $value = $attributes['style']['spacing'][$group];
        if (is_array($value)) {
            $side_lc = strtolower($side);
            if (isset($value[$side_lc])) return (string) $value[$side_lc];
            if (isset($value[$side])) return (string) $value[$side];
            return '';
        }
        // A single CSS shorthand from native style.spacing.padding/margin.
        return $side === 'Top' ? (string) $value : '';
    }

    private function wpbb_first_non_empty_attribute($attributes, $keys) {
        foreach ($keys as $key) {
            if (isset($attributes[$key]) && $attributes[$key] !== '') {
                return $attributes[$key];
            }
        }

        if (isset($attributes['style']) && is_array($attributes['style'])) {
            if (!empty($attributes['style']['color']['background'])) {
                return $attributes['style']['color']['background'];
            }
            if (!empty($attributes['style']['background']['backgroundColor'])) {
                return $attributes['style']['background']['backgroundColor'];
            }
        }

        return '';
    }

    private function wpbb_build_background_inline($attributes, $important = false, $include_position = true) {
        $style = '';
        $important_suffix = $important ? ' !important' : '';

        if (!empty($attributes['backgroundReset'])) {
            return 'background:transparent' . $important_suffix . ';';
        }
        $background_color = $this->wpbb_first_non_empty_attribute($attributes, ['backgroundColor', 'backgroundColour', 'background_color', 'background_colour', 'bgColor', 'bgColour']);
        if ($background_color !== '') {
            $background_color = $this->wpbb_sanitize_css_value($background_color);
        }

        $background_layers = [];
        $background_gradient = $this->wpbb_first_non_empty_attribute($attributes, ['backgroundGradient', 'background_gradient']);
        if ($background_gradient !== '') {
            $background_gradient = $this->wpbb_sanitize_css_value($background_gradient);
            if ($background_gradient !== '') {
                $background_layers[] = $background_gradient;
            }
        }

        $background_image = $this->wpbb_first_non_empty_attribute($attributes, ['backgroundImageUrl', 'backgroundImage', 'background_image_url']);
        if ($background_image !== '') {
            $background_layers[] = 'url(' . esc_url_raw((string)$background_image) . ')';
        }

        // Reset is represented by one explicit transparent shorthand. This clears
        // colour and image layers without rebuilding size/position/repeat defaults.
        if (strtolower((string)$background_color) === 'transparent' && empty($background_layers)) {
            return 'background:transparent' . $important_suffix . ';';
        }

        if ($background_color !== '') {
            $style .= 'background-color:' . $background_color . $important_suffix . ';';
        }

        if (!empty($background_layers)) {
            $style .= 'background-image:' . implode(',', $background_layers) . $important_suffix . ';';
            $size = $this->wpbb_sanitize_css_value((string)($attributes['backgroundSize'] ?? 'cover'));
            $position = $this->wpbb_sanitize_css_value((string)($attributes['backgroundPosition'] ?? 'center center'));
            $repeat = preg_replace('/[^a-z-]/i', '', (string)($attributes['backgroundRepeat'] ?? 'no-repeat'));
            $attachment = preg_replace('/[^a-z-]/i', '', (string)($attributes['backgroundAttachment'] ?? 'scroll'));
            if ($size === '') $size = 'cover';
            if ($position === '') $position = 'center center';
            if (!in_array($repeat, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y', 'space', 'round'], true)) $repeat = 'no-repeat';
            if (!in_array($attachment, ['scroll', 'fixed', 'local'], true)) $attachment = 'scroll';
            $style .= 'background-size:' . $size . $important_suffix . ';';
            if ($include_position) {
                $style .= 'background-position:' . $position . $important_suffix . ';';
            }
            $style .= 'background-repeat:' . $repeat . $important_suffix . ';background-attachment:' . $attachment . $important_suffix . ';';
        }

        return $style;
    }

    private function wpbb_build_responsive_background_position_inline($attributes, &$classes) {
        $background_image = $this->wpbb_first_non_empty_attribute($attributes, ['backgroundImageUrl', 'backgroundImage', 'background_image_url']);
        if ($background_image === '') return '';

        $positions = [
            'base' => (string)($attributes['backgroundPosition'] ?? 'center center'),
            'sm' => (string)($attributes['backgroundPositionSm'] ?? ''),
            'md' => (string)($attributes['backgroundPositionMd'] ?? ''),
            'lg' => (string)($attributes['backgroundPositionLg'] ?? ''),
            'xl' => (string)($attributes['backgroundPositionXl'] ?? ''),
            'xxl' => (string)($attributes['backgroundPositionXxl'] ?? ''),
        ];

        $positions['base'] = $this->wpbb_sanitize_css_value($positions['base']);
        if ($positions['base'] === '') $positions['base'] = 'center center';

        $style = '';
        $classes[] = 'wpbb-responsive-background-position';
        foreach ($positions as $bp => $position) {
            if ($bp !== 'base') $position = $this->wpbb_sanitize_css_value($position);
            if ($position === '') continue;
            $style .= '--wpbb-background-position-' . $bp . ':' . $position . ';';
        }

        return $style;
    }

    private function wpbb_default_spacing_value($attributes, $prefix, $side) {
        $property = $prefix . '-' . strtolower($side);
        $candidate = '';
        $responsive_key = $prefix . 'Default' . $side;

        if (isset($attributes[$responsive_key]) && $attributes[$responsive_key] !== '') {
            $candidate = (string) $attributes[$responsive_key];
        }

        if ($candidate === '') {
            $candidate = $this->wpbb_native_spacing_value($attributes, $prefix, $side);
        }

        if ($candidate === '') {
            $legacy_key = $prefix . $side;
            $legacy_unit_key = $legacy_key . 'Unit';
            $number = isset($attributes[$legacy_key]) ? $attributes[$legacy_key] : null;
            if ($number !== null && $number !== '' && is_numeric($number) && floatval($number) != 0.0) {
                $unit = preg_replace('/[^a-z%]/i', '', (string) ($attributes[$legacy_unit_key] ?? 'px'));
                if ($unit === '') $unit = 'px';
                $candidate = floatval($number) . $unit;
            }
        }

        return $this->wpbb_normalize_spacing_css_value($candidate, $property);
    }

    private function wpbb_build_responsive_spacing_inline($attributes, &$classes) {
        $style = '';
        $breakpoints = [
            'Default' => 'base',
            'Sm' => 'sm',
            'Md' => 'md',
            'Lg' => 'lg',
            'Xl' => 'xl',
            'Xxl' => 'xxl',
        ];

        foreach (['padding', 'margin'] as $prefix) {
            foreach (['Top', 'Right', 'Bottom', 'Left'] as $side) {
                $property_slug = $prefix . '-' . strtolower($side);
                $values = [];

                foreach ($breakpoints as $attribute_bp => $css_bp) {
                    if ($attribute_bp === 'Default') {
                        $value = $this->wpbb_default_spacing_value($attributes, $prefix, $side);
                    } else {
                        $key = $prefix . $attribute_bp . $side;
                        $value = isset($attributes[$key])
                            ? $this->wpbb_normalize_spacing_css_value($attributes[$key], $property_slug)
                            : '';
                    }

                    if ($value !== '') $values[$css_bp] = $value;
                }

                if (empty($values)) continue;

                $classes[] = 'wpbb-spacing-' . $property_slug;
                foreach ($values as $css_bp => $value) {
                    $style .= '--wpbb-' . $property_slug . '-' . $css_bp . ':' . $value . ';';
                }
            }
        }

        return $style;
    }


    private function wpbb_class_tokens_from_value($value) {
        $tokens = preg_split('/\s+/', trim((string) $value));
        $classes = [];
        foreach ($tokens as $token) {
            $token = sanitize_html_class($token);
            if ($token !== '') $classes[] = $token;
        }
        return $classes;
    }




    public function enqueue_block_editor_code_assets() {
        $scss_settings = wp_enqueue_code_editor(['type' => 'text/x-scss']);
        // wp_enqueue_code_editor() already loads the CodeMirror assets needed by
        // wp.codeEditor. Avoid loading the much larger theme/plugin editor bundle
        // in Gutenberg, where it is unused.
        wp_enqueue_script('wpbb-editor-enhancer');
        wp_enqueue_style('wpbb-editor-admin-ui');

        // Never load the full Bootstrap reset in the editor chrome. It can
        // override WordPress' responsive sidebar and modal layout. The optional
        // preview stylesheet contains only editor-canvas-scoped utility rules.
        if (wpbb_get_option('load_bootstrap_editor_css', 0)) {
            wp_enqueue_style('wpbb-bootstrap-editor-preview');
        }

        // Front-end Bootstrap JavaScript is intentionally not loaded in
        // wp-admin. BBuilder block previews do not require it, and excluding it
        // keeps editor startup and device-preview changes lightweight.
        wp_add_inline_script('wpbb-editor-enhancer', 'window.wpbbEditorEnhancer = ' . wp_json_encode([
            'scss' => $scss_settings,
        ]) . ';', 'before');
    }

public function register_assets() {
        wp_register_script('wpbb-editor', WPBB_PLUGIN_URL . 'assets/editor.js', ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wpbb-editor-discovery'], WPBB_VERSION, true);
        wp_add_inline_script('wpbb-editor', 'window.wpbbEditor = ' . wp_json_encode([
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpbb_builder_nonce'),
            'themeUri' => trailingslashit(get_stylesheet_directory_uri()),
            'parentThemeUri' => trailingslashit(get_template_directory_uri()),
            'socFeedConnectionsUrl' => admin_url('options-general.php?page=wpbb-social-connections'),
            'socFeedPreview' => [],
            'socFeedConnectionMeta' => $this->wpbb_soc_feed_editor_connection_meta(),
        ]) . ';', 'before');
        wp_register_script('wpbb-editor-enhancer', WPBB_PLUGIN_URL . 'assets/editor-enhancer.js', ['wp-dom-ready'], WPBB_VERSION, true);
        wp_register_script('wpbb-form-view', WPBB_PLUGIN_URL . 'assets/form.js', [], WPBB_VERSION, true);
        wp_register_script('wpbb-copy-code', WPBB_PLUGIN_URL . 'assets/copy-code.js', [], WPBB_VERSION, true);
        wp_register_script('wpbb-ajax-search', WPBB_PLUGIN_URL . 'assets/ajax-search.js', [], WPBB_VERSION, true);
        wp_register_script('wpbb-content-filters', WPBB_PLUGIN_URL . 'assets/content-filters.js', [], WPBB_VERSION, true);
        wp_register_style('wpbb-datatables', 'https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css', [], '2.0.8');
        wp_register_script('wpbb-datatables', 'https://cdn.datatables.net/2.0.8/js/dataTables.js', [], '2.0.8', true);
        wp_register_script('wpbb-datatables-bs5', 'https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js', ['wpbb-datatables'], '2.0.8', true);
        wp_register_script('wpbb-table-init', WPBB_PLUGIN_URL . 'assets/table-init.js', ['wpbb-datatables-bs5'], WPBB_VERSION, true);
        wp_register_script('wpbb-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', [], '4.4.3', true);
        wp_register_script('wpbb-chart-view', WPBB_PLUGIN_URL . 'assets/chart-view.js', ['wpbb-chartjs'], WPBB_VERSION, true);
        wp_localize_script('wpbb-content-filters', 'wpbbContentFilters', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
        wp_localize_script('wpbb-form-view', 'wpbbForm', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpbb_form_nonce'),
            'error' => wpbb_get_option('default_error_message', __('Something went wrong. Please try again.', 'wp-bbuilder')),
            'validationText' => wpbb_get_option('default_validation_text', __('Please fill in all required fields correctly.', 'wp-bbuilder')),
        ]);
        wp_register_script('wpbb-booking', WPBB_PLUGIN_URL . 'assets/booking-calendar.js', [], WPBB_VERSION, true);
        wp_localize_script('wpbb-booking', 'wpbbBooking', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpbb_booking_nonce'),
            'success' => __('Booking request sent successfully.', 'wp-bbuilder'),
            'error' => __('Unable to submit booking. Please try another date.', 'wp-bbuilder'),
        ]);
        if (wpbb_get_option('load_shared_css', 1)) {
            wp_register_style('wpbb-shared', WPBB_PLUGIN_URL . 'assets/shared.css', [], WPBB_VERSION);
            wp_register_style('wpbb-editor-style', WPBB_PLUGIN_URL . 'assets/editor.css', ['wpbb-shared'], WPBB_VERSION);
        } else {
            wp_register_style('wpbb-shared', false, [], WPBB_VERSION);
            wp_register_style('wpbb-editor-style', WPBB_PLUGIN_URL . 'assets/editor.css', [], WPBB_VERSION);
        }
        wp_register_style('wpbb-responsive-spacing', WPBB_PLUGIN_URL . 'assets/responsive-spacing.css', [], WPBB_VERSION);
        wp_register_style('wpbb-editor-grid', WPBB_PLUGIN_URL . 'assets/editor-grid.css', ['wpbb-editor-style'], WPBB_VERSION);
        wp_register_style('wpbb-editor-admin-ui', WPBB_PLUGIN_URL . 'assets/editor-admin-ui.css', [], WPBB_VERSION);
        wp_register_style('wpbb-bootstrap-editor-preview', WPBB_PLUGIN_URL . 'assets/bootstrap-editor-preview.css', [], WPBB_VERSION);
        wp_register_style('wpbb-swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', [], '11.1.4');
        wp_register_script('wpbb-swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', [], '11.1.4', true);
        wp_register_script('wpbb-swiper-init', WPBB_PLUGIN_URL . 'assets/swiper-init.js', ['wpbb-swiper'], WPBB_VERSION, true);
    }

    public function register_category($categories) {
        array_unshift($categories, [
            'slug' => 'wpbb',
            'title' => __('BBuilder', 'wp-bbuilder'),
            'icon' => null,
        ]);
        return $categories;
    }

    public function register_blocks() {
        foreach (array_filter(wpbb_get_blocks_list(), function($s){ return $s !== 'row-section'; }) as $slug) {
            // Always register wpbb/soc-feed so saved block-theme templates do not
            // collapse to empty output when an older settings row has this newer
            // block missing/disabled. It can still be hidden from the inserter by
            // allowed_block_types filtering, but the renderer must exist publicly.
            if ($slug !== 'soc-feed' && !wpbb_is_block_enabled($slug)) continue;

            $args = [
                'api_version' => 3,
                'title' => $slug === 'bootstrap-div' ? __('Div', 'wp-bbuilder') : ($slug === 'icon-card' ? __('Icon Card', 'wp-bbuilder') : ucwords(str_replace('-', ' ', $slug))),
                'category' => 'wpbb',
                'icon' => $this->icon_for($slug),
                'editor_script' => 'wpbb-editor',
                'editor_style' => 'wpbb-editor-style',
                'style' => 'wpbb-shared',
                'attributes' => $this->attributes_for($slug),
                'supports' => ['anchor' => true, 'html' => false],
            ];

            if ($slug === 'alert') {
                $args['render_callback'] = [$this, 'render_alert_block'];
            } elseif ($slug === 'badge') {
                $args['render_callback'] = [$this, 'render_badge_block'];
            } elseif ($slug === 'breadcrumb') {
                $args['render_callback'] = [$this, 'render_breadcrumb_block'];
            } elseif ($slug === 'list-group') {
                $args['render_callback'] = [$this, 'render_list_group_block'];
            } elseif ($slug === 'navbar') {
                $args['render_callback'] = [$this, 'render_navbar_block'];
            } elseif ($slug === 'progress') {
                $args['render_callback'] = [$this, 'render_progress_block'];
            } elseif ($slug === 'section') {
                $args['render_callback'] = [$this, 'render_section_block'];
            } elseif ($slug === 'spinner') {
                $args['render_callback'] = [$this, 'render_spinner_block'];
            } elseif ($slug === 'dynamic-form') {
                $args['script'] = 'wpbb-form-view';
                $args['render_callback'] = [$this, 'render_dynamic_form'];
            } elseif ($slug === 'table') {
                $args['render_callback'] = [$this, 'render_table_block'];
            } elseif ($slug === 'swiper') {
                $args['style'] = ['wpbb-shared','wpbb-swiper'];
                $args['script'] = 'wpbb-swiper-init';
                $args['render_callback'] = [$this, 'render_swiper_block'];
            } elseif ($slug === 'weather') {
                $args['render_callback'] = [$this, 'render_weather_block'];
            } elseif ($slug === 'varda-dienas') {
                $args['render_callback'] = [$this, 'render_varda_dienas_block'];
            } elseif ($slug === 'ajax-search') {
                $args['script'] = 'wpbb-ajax-search';
                $args['render_callback'] = [$this, 'render_ajax_search_block'];
            } elseif ($slug === 'pricecards') {
                $args['render_callback'] = [$this, 'render_pricecards_block'];
            } elseif ($slug === 'catalogue') {
                $args['render_callback'] = [$this, 'render_catalogue_block'];
            } elseif ($slug === 'code-display') {
                $args['script'] = 'wpbb-copy-code';
                $args['render_callback'] = [$this, 'render_code_display_block'];
            } elseif ($slug === 'countdown-timer') {
                $args['script'] = 'wpbb-chart-view';
                $args['render_callback'] = [$this, 'render_countdown_timer_block'];
            } elseif ($slug === 'chart') {
                $args['script'] = 'wpbb-chart-view';
                $args['render_callback'] = [$this, 'render_chart_block'];
            } elseif ($slug === 'fun-fact') {
                $args['render_callback'] = [$this, 'render_fun_fact_block'];
            } elseif ($slug === 'mailchimp') {
                $args['render_callback'] = [$this, 'render_mailchimp_block'];
            } elseif ($slug === 'bootstrap-div') {
                $args['render_callback'] = [$this, 'render_bootstrap_div_block'];
            } elseif ($slug === 'feature-list') {
                $args['render_callback'] = [$this, 'render_feature_list_block'];
            } elseif ($slug === 'timeline') {
                $args['render_callback'] = [$this, 'render_timeline_block'];
            } elseif ($slug === 'custom-embed') {
                $args['render_callback'] = [$this, 'render_custom_embed_block'];
            } elseif ($slug === 'ai-content') {
                $args['render_callback'] = [$this, 'render_ai_content_block'];
            } elseif ($slug === 'login-register') {
                $args['render_callback'] = [$this, 'render_login_register_block'];
            } elseif ($slug === 'row') {
                $args['render_callback'] = [$this, 'render_row_block'];
            } elseif ($slug === 'column') {
                $args['render_callback'] = [$this, 'render_column_block'];
            } elseif ($slug === 'button') {
                $args['render_callback'] = [$this, 'render_button_block'];
            } elseif ($slug === 'accordion') {
                $args['render_callback'] = [$this, 'render_accordion_block'];
            } elseif ($slug === 'accordion-item') {
                $args['render_callback'] = [$this, 'render_accordion_item_block'];
            } elseif ($slug === 'row') {
                $args['render_callback'] = [$this, 'render_row_block'];
            } elseif ($slug === 'column') {
                $args['render_callback'] = [$this, 'render_column_block'];
            } elseif ($slug === 'soc-follow-block') {
                $args['render_callback'] = [$this, 'render_social_follow_block'];
            } elseif ($slug === 'soc-share') {
                $args['render_callback'] = [$this, 'render_social_share_block'];
            } elseif ($slug === 'social-feeds') {
                $args['render_callback'] = [$this, 'render_social_feeds_block'];
            } elseif ($slug === 'soc-feed') {
                $args['style'] = ['wpbb-shared','wpbb-swiper'];
                $args['script'] = 'wpbb-swiper-init';
                $args['render_callback'] = [$this, 'render_soc_feed_block'];
            } elseif ($slug === 'load-more') {
                $args['script'] = 'wpbb-content-filters';
                $args['render_callback'] = [$this, 'render_load_more_block'];
            } elseif ($slug === 'contact-links') {
                $args['render_callback'] = [$this, 'render_contact_links_block'];
            } elseif ($slug === 'events') {
                $args['render_callback'] = [$this, 'render_events_block'];
            } elseif ($slug === 'testimonials') {
                $args['style'] = ['wpbb-shared','wpbb-swiper'];
                $args['script'] = 'wpbb-swiper-init';
                $args['render_callback'] = [$this, 'render_testimonials_block'];
            } elseif ($slug === 'blog-filter') {
                $args['script'] = 'wpbb-content-filters';
                $args['render_callback'] = [$this, 'render_blog_filter_block'];
            } elseif ($slug === 'booking-calendar') {
                $args['script'] = 'wpbb-booking';
                $args['render_callback'] = [$this, 'render_booking_calendar_block'];
            } elseif ($slug === 'icon-card') {
                $args['render_callback'] = [$this, 'render_icon_card_block'];
            } else {
                $args['render_callback'] = [$this, 'render_generic_block'];
            }

            if (in_array($slug, ['row', 'column'], true)) {
                $args['style'] = ['wpbb-shared', 'wpbb-responsive-spacing'];
                $args['editor_style'] = ['wpbb-editor-style', 'wpbb-responsive-spacing', 'wpbb-editor-grid'];
            }

            if ($slug === 'column') $args['parent'] = ['wpbb/row'];
            if ($slug === 'bootstrap-div') $args['supports']['innerBlocks'] = true;
            if ($slug === 'accordion-item') $args['parent'] = ['wpbb/accordion'];
            if ($slug === 'tab-item') $args['parent'] = ['wpbb/tabs'];

            register_block_type('wpbb/' . $slug, $args);
        }
    }

    private function icon_for($slug) {
        $map = [
            'accordion' => 'menu',
            'accordion-item' => 'excerpt-view',
            'alert' => 'warning',
            'badge' => 'tag',
            'breadcrumb' => 'editor-ol',
            'button' => 'button',
            'card' => 'id',
            'cards' => 'grid-view',
            'column' => 'columns',
            'dynamic-form' => 'feedback',
            'list-group' => 'list-view',
            'navbar' => 'menu',
            'progress' => 'performance',
            'row' => 'grid-view','cta-card' => 'megaphone','cta-section' => 'cover-image','google-map' => 'location-alt','menu-option' => 'menu','sitemap' => 'networking','soc-follow-block' => 'share','soc-share' => 'share-alt2','social-feeds' => 'rss','soc-feed' => 'rss',
            'section' => 'cover-image',
            'spinner' => 'update',
            'file' => 'media-document',
            'inline-svg' => 'format-image',
            'tab-item' => 'editor-table',
            'load-more' => 'plus-alt2','contact-links' => 'phone','events' => 'calendar-alt','testimonials' => 'format-quote','blog-filter' => 'filter','booking-calendar' => 'calendar-alt','icon-card' => 'index-card',
            'tabs' => 'index-card',
            'table' => 'table-col-after',
            'swiper' => 'images-alt2','weather' => 'cloud','varda-dienas' => 'calendar-alt','ajax-search' => 'search','pricecards' => 'index-card','catalogue' => 'screenoptions','code-display' => 'editor-code','countdown-timer' => 'clock','chart' => 'chart-bar','fun-fact' => 'star-filled','mailchimp' => 'email','bootstrap-div' => 'screenoptions',
                    ];
        return $map[$slug] ?? 'screenoptions';
    }

    private function attributes_for($slug) {
        switch ($slug) {
            case 'row':
                return array_merge([
                    'gutterX' => ['type' => 'string', 'default' => 'gx-3'],
                    'gutterY' => ['type' => 'string', 'default' => 'gy-3'],
                    'align' => ['type' => 'string', 'default' => ''],
                    'autoColumns' => ['type' => 'boolean', 'default' => true],
                    'paddingClass' => ['type' => 'string', 'default' => ''],
                    'marginClass' => ['type' => 'string', 'default' => ''],
                    'backgroundClass' => ['type' => 'string', 'default' => ''],
                    'animationClass' => ['type' => 'string', 'default' => ''],
                    'displayClass' => ['type' => 'string', 'default' => ''],
                    'textUtilityClass' => ['type' => 'string', 'default' => ''],
                    'roundedClass' => ['type' => 'string', 'default' => ''],
                    'shadowClass' => ['type' => 'string', 'default' => ''],
                    'bootstrapClasses' => ['type' => 'string', 'default' => ''],
                    'customClasses' => ['type' => 'string', 'default' => ''],
                    'utilityClasses' => ['type' => 'string', 'default' => ''],
                    'spacingSm' => ['type' => 'string', 'default' => ''],'spacingMd' => ['type' => 'string', 'default' => ''],'spacingLg' => ['type' => 'string', 'default' => ''],'spacingXl' => ['type' => 'string', 'default' => ''],'spacingXxl' => ['type' => 'string', 'default' => ''],'paddingSm' => ['type' => 'string', 'default' => ''],'paddingMd' => ['type' => 'string', 'default' => ''],'paddingLg' => ['type' => 'string', 'default' => ''],'paddingXl' => ['type' => 'string', 'default' => ''],'paddingXxl' => ['type' => 'string', 'default' => ''],'marginSm' => ['type' => 'string', 'default' => ''],'marginMd' => ['type' => 'string', 'default' => ''],'marginLg' => ['type' => 'string', 'default' => ''],'marginXl' => ['type' => 'string', 'default' => ''],'marginXxl' => ['type' => 'string', 'default' => ''],'uniqueId' => ['type' => 'string', 'default' => ''],'customCss' => ['type' => 'string', 'default' => ''],'customScss' => ['type' => 'string', 'default' => ''],
                    'backgroundGradient' => ['type' => 'string', 'default' => ''],
                    'backgroundGradientType' => ['type' => 'string', 'default' => 'linear'],
                    'backgroundGradientAngle' => ['type' => 'number', 'default' => 135],
                    'backgroundGradientColor1' => ['type' => 'string', 'default' => '#2563eb'],
                    'backgroundGradientColor2' => ['type' => 'string', 'default' => '#7c3aed'],
                    'backgroundImageUrl' => ['type' => 'string', 'default' => ''],
                    'backgroundSize' => ['type' => 'string', 'default' => 'cover'],
                    'backgroundSizePreset' => ['type' => 'string', 'default' => 'cover'],
                    'backgroundSizeCustom' => ['type' => 'string', 'default' => '100% auto'],
                    'backgroundRepeat' => ['type' => 'string', 'default' => 'no-repeat'],
                    'backgroundPosition' => ['type' => 'string', 'default' => 'center center'],
                    'backgroundPositionXSide' => ['type' => 'string', 'default' => 'left'],
                    'backgroundPositionYSide' => ['type' => 'string', 'default' => 'top'],
                    'backgroundPositionXPercent' => ['type' => 'string', 'default' => ''],
                    'backgroundPositionYPercent' => ['type' => 'string', 'default' => ''],
                    'overlayColor' => ['type' => 'string', 'default' => ''],
                    'overlayOpacity' => ['type' => 'number', 'default' => 0],
                    'backgroundAttachment' => ['type' => 'string', 'default' => 'scroll'],
                    'paddingTop' => ['type' => 'number', 'default' => 0],
                    'paddingTopUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingRight' => ['type' => 'number', 'default' => 0],
                    'paddingRightUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingBottom' => ['type' => 'number', 'default' => 0],
                    'paddingBottomUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingLeft' => ['type' => 'number', 'default' => 0],
                    'paddingLeftUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginTop' => ['type' => 'number', 'default' => 0],
                    'marginTopUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginRight' => ['type' => 'number', 'default' => 0],
                    'marginRightUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginBottom' => ['type' => 'number', 'default' => 0],
                    'marginBottomUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginLeft' => ['type' => 'number', 'default' => 0],
                    'marginLeftUnit' => ['type' => 'string', 'default' => 'px'],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'backgroundReset' => ['type' => 'boolean', 'default' => false],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'customStyle' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                    'maxWidth' => ['type' => 'string', 'default' => ''],
                    'maxWidthUnit' => ['type' => 'string', 'default' => 'px'],
                    'containerClass' => ['type' => 'string', 'default' => ''],
                    'visibilityClass' => ['type' => 'string', 'default' => ''],
                    'visibilityXs' => ['type' => 'boolean', 'default' => true],
                    'visibilitySm' => ['type' => 'boolean', 'default' => true],
                    'visibilityMd' => ['type' => 'boolean', 'default' => true],
                    'visibilityLg' => ['type' => 'boolean', 'default' => true],
                    'visibilityXl' => ['type' => 'boolean', 'default' => true],
                ], $this->wpbb_responsive_spacing_attributes(), $this->wpbb_responsive_background_position_attributes());
            case 'column':
                return array_merge([
                    'xs' => ['type' => 'number', 'default' => 12],
                    'sm' => ['type' => 'number', 'default' => 0],
                    'md' => ['type' => 'number', 'default' => 0],
                    'lg' => ['type' => 'number', 'default' => 0],
                    'xl' => ['type' => 'number', 'default' => 0],
                    'xxl' => ['type' => 'number', 'default' => 0],
                    'uniqueId' => ['type' => 'string', 'default' => ''],
                    'maxWidth' => ['type' => 'string', 'default' => ''],
                    'maxWidthUnit' => ['type' => 'string', 'default' => 'px'],
                    'customCss' => ['type' => 'string', 'default' => ''],
                    'customScss' => ['type' => 'string', 'default' => ''],
                    'backgroundGradient' => ['type' => 'string', 'default' => ''],
                    'backgroundGradientType' => ['type' => 'string', 'default' => 'linear'],
                    'backgroundGradientAngle' => ['type' => 'number', 'default' => 135],
                    'backgroundGradientColor1' => ['type' => 'string', 'default' => '#2563eb'],
                    'backgroundGradientColor2' => ['type' => 'string', 'default' => '#7c3aed'],
                    'backgroundImageUrl' => ['type' => 'string', 'default' => ''],
                    'backgroundSize' => ['type' => 'string', 'default' => 'cover'],
                    'backgroundSizePreset' => ['type' => 'string', 'default' => 'cover'],
                    'backgroundSizeCustom' => ['type' => 'string', 'default' => '100% auto'],
                    'backgroundRepeat' => ['type' => 'string', 'default' => 'no-repeat'],
                    'backgroundPosition' => ['type' => 'string', 'default' => 'center center'],
                    'backgroundPositionXSide' => ['type' => 'string', 'default' => 'left'],
                    'backgroundPositionYSide' => ['type' => 'string', 'default' => 'top'],
                    'backgroundPositionXPercent' => ['type' => 'string', 'default' => ''],
                    'backgroundPositionYPercent' => ['type' => 'string', 'default' => ''],
                    'backgroundAttachment' => ['type' => 'string', 'default' => 'scroll'],
                    'overlayColor' => ['type' => 'string', 'default' => ''],
                    'overlayOpacity' => ['type' => 'number', 'default' => 0],
                    'boxShadowClass' => ['type' => 'string', 'default' => ''],
                    'boxShadowColor' => ['type' => 'string', 'default' => ''],
                    'orderClass' => ['type' => 'string', 'default' => ''],
                    'orderSm' => ['type' => 'number', 'default' => 0],
                    'orderMd' => ['type' => 'number', 'default' => 0],
                    'orderLg' => ['type' => 'number', 'default' => 0],
                    'orderXl' => ['type' => 'number', 'default' => 0],
                    'orderXxl' => ['type' => 'number', 'default' => 0],
                    'verticalAlign' => ['type' => 'string', 'default' => ''],
                    'horizontalAlign' => ['type' => 'string', 'default' => ''],
                    'visibilityClass' => ['type' => 'string', 'default' => ''],
                    'visibilityXs' => ['type' => 'boolean', 'default' => true],
                    'visibilitySm' => ['type' => 'boolean', 'default' => true],
                    'visibilityMd' => ['type' => 'boolean', 'default' => true],
                    'visibilityLg' => ['type' => 'boolean', 'default' => true],
                    'visibilityXl' => ['type' => 'boolean', 'default' => true],
                    'animationClass' => ['type' => 'string', 'default' => ''],
                    'paddingTop' => ['type' => 'number', 'default' => 0], 'paddingTopUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingRight' => ['type' => 'number', 'default' => 0], 'paddingRightUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingBottom' => ['type' => 'number', 'default' => 0], 'paddingBottomUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingLeft' => ['type' => 'number', 'default' => 0], 'paddingLeftUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginTop' => ['type' => 'number', 'default' => 0], 'marginTopUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginRight' => ['type' => 'number', 'default' => 0], 'marginRightUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginBottom' => ['type' => 'number', 'default' => 0], 'marginBottomUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginLeft' => ['type' => 'number', 'default' => 0], 'marginLeftUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingClass' => ['type' => 'string', 'default' => ''],
                    'marginClass' => ['type' => 'string', 'default' => ''],
                    'backgroundClass' => ['type' => 'string', 'default' => ''],
                    'displayClass' => ['type' => 'string', 'default' => ''],
                    'textUtilityClass' => ['type' => 'string', 'default' => ''],
                    'roundedClass' => ['type' => 'string', 'default' => ''],
                    'shadowClass' => ['type' => 'string', 'default' => ''],
                    'bootstrapClasses' => ['type' => 'string', 'default' => ''],
                    'customClasses' => ['type' => 'string', 'default' => ''],
                    'utilityClasses' => ['type' => 'string', 'default' => ''],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'backgroundReset' => ['type' => 'boolean', 'default' => false],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'customStyle' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ], $this->wpbb_responsive_spacing_attributes(), $this->wpbb_responsive_background_position_attributes());
            case 'button':
                return [
                    'text' => ['type' => 'string', 'default' => 'Button'],
                    'url' => ['type' => 'string', 'default' => '#'],
                    'btnClass' => ['type' => 'string', 'default' => 'btn btn-primary'],
                    'variant' => ['type' => 'string', 'default' => 'primary'],
                    'size' => ['type' => 'string', 'default' => ''],
                    'fullWidth' => ['type' => 'boolean', 'default' => false],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'align' => ['type' => 'string', 'default' => ''],
                    'borderRadius' => ['type' => 'string', 'default' => '12px'],
                ];
            case 'icon-card':
                return [
                    'mediaType' => ['type' => 'string', 'default' => 'image'],
                    'imageId' => ['type' => 'number', 'default' => 0],
                    'imageUrl' => ['type' => 'string', 'default' => ''],
                    'imageAlt' => ['type' => 'string', 'default' => ''],
                    'svgCode' => ['type' => 'string', 'default' => ''],
                    'title' => ['type' => 'string', 'default' => 'Card title'],
                    'text' => ['type' => 'string', 'default' => 'Add a short description.'],
                    'linkText' => ['type' => 'string', 'default' => ''],
                    'linkUrl' => ['type' => 'string', 'default' => ''],
                    'linkNewTab' => ['type' => 'boolean', 'default' => false],
                    'iconSize' => ['type' => 'string', 'default' => '56px'],
                    'imagePosition' => ['type' => 'string', 'default' => 'top'],
                    'contentAlign' => ['type' => 'string', 'default' => 'left'],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'autoTextColor' => ['type' => 'boolean', 'default' => true],
                    'borderColor' => ['type' => 'string', 'default' => ''],
                    'boxShadowClass' => ['type' => 'string', 'default' => 'shadow-sm'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'load-more':
                return [
                    'buttonText' => ['type' => 'string', 'default' => 'Load more'],
                    'buttonClass' => ['type' => 'string', 'default' => 'btn btn-primary'],
                    'buttonColor' => ['type' => 'string', 'default' => ''],
                    'visibleItems' => ['type' => 'number', 'default' => 6],
                    'loadItems' => ['type' => 'number', 'default' => 3],
                    'parentClass' => ['type' => 'string', 'default' => 'row'],
                    'itemClass' => ['type' => 'string', 'default' => 'col-md-4'],
                    'queryPostType' => ['type' => 'string', 'default' => 'post'],
                    'queryCategory' => ['type' => 'string', 'default' => ''],
                ];
            case 'contact-links':
                return [
                    'email' => ['type' => 'string', 'default' => 'hello@example.com'],
                    'phone' => ['type' => 'string', 'default' => '+37100000000'],
                    'emailIcon' => ['type' => 'string', 'default' => 'email'],
                    'phoneIcon' => ['type' => 'string', 'default' => 'whatsapp'],
                    'iconColor' => ['type' => 'string', 'default' => ''],
                    'linkColor' => ['type' => 'string', 'default' => ''],
                    'layoutClass' => ['type' => 'string', 'default' => 'd-flex flex-column gap-2'],
                ];
            case 'events':
                return [
                    'postType' => ['type' => 'string', 'default' => 'event'],
                    'postsToShow' => ['type' => 'number', 'default' => 6],
                    'taxonomy' => ['type' => 'string', 'default' => 'event_category'],
                    'showCalendar' => ['type' => 'boolean', 'default' => true],
                    'title' => ['type' => 'string', 'default' => 'Events'],
                ];
            case 'testimonials':
                return [
                    'postType' => ['type' => 'string', 'default' => 'testimonial'],
                    'postsToShow' => ['type' => 'number', 'default' => 9],
                    'slidesDesktop' => ['type' => 'number', 'default' => 3],
                    'slidesTablet' => ['type' => 'number', 'default' => 2],
                    'slidesMobile' => ['type' => 'number', 'default' => 1],
                    'showNavigation' => ['type' => 'boolean', 'default' => true],
                    'showPagination' => ['type' => 'boolean', 'default' => true],
                    'title' => ['type' => 'string', 'default' => 'Testimonials'],
                ];
            case 'blog-filter':
                return [
                    'postType' => ['type' => 'string', 'default' => 'post'],
                    'postsToShow' => ['type' => 'number', 'default' => 6],
                    'taxonomy' => ['type' => 'string', 'default' => 'category'],
                    'title' => ['type' => 'string', 'default' => 'Blog'],
                    'buttonText' => ['type' => 'string', 'default' => 'Filter'],
                    'buttonColor' => ['type' => 'string', 'default' => '#2563eb'],
                ];


            case 'booking-calendar':
                return [
                    'title' => ['type' => 'string', 'default' => 'Book an appointment'],
                    'intro' => ['type' => 'string', 'default' => 'Choose a provider, service, date and time.'],
                    'successMessage' => ['type' => 'string', 'default' => 'Thanks, your appointment request has been received.'],
                    'adminEmail' => ['type' => 'string', 'default' => get_option('admin_email')],
                    'providerPostType' => ['type' => 'string', 'default' => 'doctor'],
                    'providerLabel' => ['type' => 'string', 'default' => 'Doctor'],
                    'services' => ['type' => 'string', 'default' => 'Consultation|30|85\nFollow-up|20|55\nVideo consultation|30|75'],
                    'startTime' => ['type' => 'string', 'default' => '09:00'],
                    'endTime' => ['type' => 'string', 'default' => '17:00'],
                    'slotMinutes' => ['type' => 'number', 'default' => 30],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
case 'alert':
    return [
        'text' => ['type' => 'string', 'default' => 'Heads up! This is a fast, accessible alert block.'],
        'variant' => ['type' => 'string', 'default' => 'primary'],
        'dismissible' => ['type' => 'boolean', 'default' => false],
        'className' => ['type' => 'string', 'default' => ''],
    ];
case 'badge':
    return [
        'text' => ['type' => 'string', 'default' => 'New'],
        'variant' => ['type' => 'string', 'default' => 'primary'],
        'pill' => ['type' => 'boolean', 'default' => true],
        'className' => ['type' => 'string', 'default' => ''],
    ];
case 'breadcrumb':
    return [
        'itemsJson' => ['type' => 'string', 'default' => '[{"label":"Home","url":"/"},{"label":"Library","url":"#"},{"label":"Current page","url":""}]'],
        'className' => ['type' => 'string', 'default' => ''],
    ];
case 'list-group':
    return [
        'itemsJson' => ['type' => 'string', 'default' => '[{"text":"Fast loading","active":true},{"text":"Bootstrap components"},{"text":"Server-side rendering"}]'],
        'flush' => ['type' => 'boolean', 'default' => false],
        'numbered' => ['type' => 'boolean', 'default' => false],
        'className' => ['type' => 'string', 'default' => ''],
    ];
case 'navbar':
    return [
        'brand' => ['type' => 'string', 'default' => 'BBuilder'],
        'brandUrl' => ['type' => 'string', 'default' => '/'],
        'expand' => ['type' => 'string', 'default' => 'lg'],
        'scheme' => ['type' => 'string', 'default' => 'light'],
        'bgClass' => ['type' => 'string', 'default' => 'bg-light'],
        'itemsJson' => ['type' => 'string', 'default' => '[{"label":"Home","url":"/","active":true},{"label":"Docs","url":"#"},{"label":"Pricing","url":"#"}]'],
        'className' => ['type' => 'string', 'default' => ''],
    ];
case 'progress':
    return [
        'value' => ['type' => 'number', 'default' => 72],
        'label' => ['type' => 'string', 'default' => 'Performance'],
        'variant' => ['type' => 'string', 'default' => 'success'],
        'striped' => ['type' => 'boolean', 'default' => false],
        'animated' => ['type' => 'boolean', 'default' => false],
        'className' => ['type' => 'string', 'default' => ''],
    ];
case 'section':
    return [
        'title' => ['type' => 'string', 'default' => 'Section'],
        'lead' => ['type' => 'string', 'default' => 'Use this semantic section wrapper for hero areas, feature strips, and content bands.'],
        'containerClass' => ['type' => 'string', 'default' => 'container'],
        'backgroundClass' => ['type' => 'string', 'default' => 'py-5'],
        'backgroundColor' => ['type' => 'string', 'default' => ''],
        'backgroundReset' => ['type' => 'boolean', 'default' => false],
        'backgroundColour' => ['type' => 'string', 'default' => ''],
        'bgColor' => ['type' => 'string', 'default' => ''],
        'backgroundGradient' => ['type' => 'string', 'default' => ''],
        'backgroundGradientType' => ['type' => 'string', 'default' => 'linear'],
        'backgroundGradientAngle' => ['type' => 'number', 'default' => 135],
        'backgroundGradientColor1' => ['type' => 'string', 'default' => '#2563eb'],
        'backgroundGradientColor2' => ['type' => 'string', 'default' => '#7c3aed'],
        'backgroundImageUrl' => ['type' => 'string', 'default' => ''],
        'backgroundImage' => ['type' => 'string', 'default' => ''],
        'backgroundSize' => ['type' => 'string', 'default' => 'cover'],
        'backgroundSizePreset' => ['type' => 'string', 'default' => 'cover'],
        'backgroundSizeCustom' => ['type' => 'string', 'default' => '100% auto'],
        'backgroundRepeat' => ['type' => 'string', 'default' => 'no-repeat'],
        'backgroundPosition' => ['type' => 'string', 'default' => 'center center'],
        'backgroundPositionXSide' => ['type' => 'string', 'default' => 'left'],
        'backgroundPositionYSide' => ['type' => 'string', 'default' => 'top'],
        'backgroundPositionXPercent' => ['type' => 'string', 'default' => ''],
        'backgroundPositionYPercent' => ['type' => 'string', 'default' => ''],
        'backgroundAttachment' => ['type' => 'string', 'default' => 'scroll'],
        'overlayColor' => ['type' => 'string', 'default' => ''],
        'overlayOpacity' => ['type' => 'number', 'default' => 0],
        'className' => ['type' => 'string', 'default' => ''],
    ];
case 'spinner':
    return [
        'type' => ['type' => 'string', 'default' => 'border'],
        'variant' => ['type' => 'string', 'default' => 'primary'],
        'label' => ['type' => 'string', 'default' => 'Loading'],
        'className' => ['type' => 'string', 'default' => ''],
    ];
            case 'dynamic-form':
                return [
                    'showTitle' => ['type' => 'boolean', 'default' => true],
                    'formTitle' => ['type' => 'string', 'default' => 'Contact form'],
                    'recipient' => ['type' => 'string', 'default' => ''],
                    'subject' => ['type' => 'string', 'default' => 'Website enquiry'],
                    'successMessage' => ['type' => 'string', 'default' => 'Thanks. Your message has been sent.'],
                    'validationMode' => ['type' => 'string', 'default' => 'browser'],
                    'labelPosition' => ['type' => 'string', 'default' => 'top'],
                    'styleVariant' => ['type' => 'string', 'default' => 'soft'],
                    'gap' => ['type' => 'number', 'default' => 3],
                    'fieldsJson' => ['type' => 'string', 'default' => ''],
                    'fields' => ['type' => 'array', 'default' => []],
                    'stylePreset' => ['type' => 'string', 'default' => ''],
                    'submitText' => ['type' => 'string', 'default' => 'Submit'],
                    'buttonClass' => ['type' => 'string', 'default' => ''],
                    'formClass' => ['type' => 'string', 'default' => ''],
                    'emailSubject' => ['type' => 'string', 'default' => ''],
                    'enableSteps' => ['type' => 'boolean', 'default' => false],
                    'enableConditional' => ['type' => 'boolean', 'default' => false],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'cards':
                return [
                    'columnsMd' => ['type' => 'number', 'default' => 3],
                    'gap' => ['type' => 'number', 'default' => 3],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'weather':
                return [
                    'title' => ['type' => 'string', 'default' => 'Weather'],
                    'location' => ['type' => 'string', 'default' => 'London'],
                    'lang' => ['type' => 'string', 'default' => 'en'],
                    'apiKey' => ['type' => 'string', 'default' => ''],
                    'showTemp' => ['type' => 'boolean', 'default' => true],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'varda-dienas':
                return [
                    'title' => ['type' => 'string', 'default' => 'Name Days'],
                    'dateText' => ['type' => 'string', 'default' => ''],
                    'names' => ['type' => 'string', 'default' => ''],
                    'namesJson' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'ajax-search':
                return [
                    'title' => ['type' => 'string', 'default' => 'Meklēšana'],
                    'placeholder' => ['type' => 'string', 'default' => 'Meklēt...'],
                    'resultsLimit' => ['type' => 'number', 'default' => 10],
                    'postTypes' => ['type' => 'array', 'default' => ['post','page','product']],
                    'searchWooBy' => ['type' => 'string', 'default' => 'title'],
                    'sortBy' => ['type' => 'string', 'default' => 'relevance'],
                    'showExcerpt' => ['type' => 'boolean', 'default' => true],
                    'showPrice' => ['type' => 'boolean', 'default' => true],
                    'showButton' => ['type' => 'boolean', 'default' => true],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'pricecards':
                return [
                    'title' => ['type' => 'string', 'default' => 'Cenas'],
                    'cardsJson' => ['type' => 'string', 'default' => ''],
                    'styleVariant' => ['type' => 'string', 'default' => 'default'],
                    'showFeatured' => ['type' => 'boolean', 'default' => false],
                    'currency' => ['type' => 'string', 'default' => '€'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'catalogue':
                return [
                    'title' => ['type' => 'string', 'default' => 'Katalogs'],
                    'category' => ['type' => 'string', 'default' => ''],
                    'postsToShow' => ['type' => 'number', 'default' => 6],
                    'postType' => ['type' => 'string', 'default' => 'post'],
                    'taxonomy' => ['type' => 'string', 'default' => 'category'],
                    'sortBy' => ['type' => 'string', 'default' => 'date'],
                    'sortOrder' => ['type' => 'string', 'default' => 'DESC'],
                    'showImage' => ['type' => 'boolean', 'default' => true],
                    'showExcerpt' => ['type' => 'boolean', 'default' => true],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'code-display':
                return [
                    'title' => ['type' => 'string', 'default' => 'Code'],
                    'code' => ['type' => 'string', 'default' => ''],
                    'language' => ['type' => 'string', 'default' => 'html'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'countdown-timer':
                return [
                    'title' => ['type' => 'string', 'default' => 'Countdown'],
                    'targetDate' => ['type' => 'string', 'default' => '2030-01-01T00:00:00'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'chart':
                return [
                    'title' => ['type' => 'string', 'default' => 'Chart'],
                    'chartType' => ['type' => 'string', 'default' => 'bar'],
                    'chartDataJson' => ['type' => 'string', 'default' => '{"labels":["Jan","Feb","Mar"],"datasets":[{"label":"Sales","data":[12,19,7]}]}'],
                    'chartOptionsJson' => ['type' => 'string', 'default' => '{"responsive":true,"plugins":{"legend":{"display":true}}}'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'fun-fact':
                return [
                    'number' => ['type' => 'string', 'default' => '100+'],
                    'label' => ['type' => 'string', 'default' => 'Projects'],
                    'icon' => ['type' => 'string', 'default' => '⭐'],
                    'styleVariant' => ['type' => 'string', 'default' => 'default'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'mailchimp':
                return [
                    'title' => ['type' => 'string', 'default' => 'Subscribe'],
                    'text' => ['type' => 'string', 'default' => 'Join our newsletter'],
                    'actionUrl' => ['type' => 'string', 'default' => ''],
                    'audienceFieldName' => ['type' => 'string', 'default' => 'EMAIL'],
                    'showNameField' => ['type' => 'boolean', 'default' => false],
                    'buttonText' => ['type' => 'string', 'default' => 'Subscribe'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'swiper':
                return [
                    'slides' => ['type' => 'array', 'default' => []],
                    'slidesJson' => ['type' => 'string', 'default' => ''],
                    'slidesPerView' => ['type' => 'number', 'default' => 1],
                    'slidesTablet' => ['type' => 'number', 'default' => 1],
                    'slidesMobile' => ['type' => 'number', 'default' => 1],
                    'spaceBetween' => ['type' => 'number', 'default' => 20],
                    'speed' => ['type' => 'number', 'default' => 600],
                    'loop' => ['type' => 'boolean', 'default' => false],
                    'rewind' => ['type' => 'boolean', 'default' => true],
                    'autoplay' => ['type' => 'boolean', 'default' => false],
                    'autoplayDelay' => ['type' => 'number', 'default' => 4500],
                    'pauseOnHover' => ['type' => 'boolean', 'default' => true],
                    'centeredSlides' => ['type' => 'boolean', 'default' => false],
                    'effect' => ['type' => 'string', 'default' => 'slide'],
                    'demoStyle' => ['type' => 'string', 'default' => 'cards'],
                    'showPagination' => ['type' => 'boolean', 'default' => true],
                    'showNavigation' => ['type' => 'boolean', 'default' => true],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'accordion-item':
            case 'tab-item':
                return [
                    'title' => ['type' => 'string', 'default' => ucfirst(str_replace('-', ' ', $slug))],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'cta-card':
                return [
                    'title' => ['type' => 'string', 'default' => 'CTA Card'],
                    'titleTag' => ['type' => 'string', 'default' => 'h3'],
                    'text' => ['type' => 'string', 'default' => 'Call to action text'],
                    'buttonText' => ['type' => 'string', 'default' => 'Learn more'],
                    'buttonUrl' => ['type' => 'string', 'default' => '#'],
                    'bgColor' => ['type' => 'string', 'default' => ''],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'borderRadius' => ['type' => 'string', 'default' => ''],
                    'schemaEnable' => ['type' => 'boolean', 'default' => false],
                    'schemaType' => ['type' => 'string', 'default' => 'CreativeWork'],
                    'schemaPrice' => ['type' => 'string', 'default' => ''],
                    'currency' => ['type' => 'string', 'default' => '€'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'cta-section':
                return [
                    'title' => ['type' => 'string', 'default' => 'CTA Section'],
                    'titleTag' => ['type' => 'string', 'default' => 'h2'],
                    'text' => ['type' => 'string', 'default' => 'Call to action text'],
                    'buttonText' => ['type' => 'string', 'default' => 'Get started'],
                    'buttonUrl' => ['type' => 'string', 'default' => '#'],
                    'bgColor' => ['type' => 'string', 'default' => ''],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'borderRadius' => ['type' => 'string', 'default' => ''],
                    'backgroundImage' => ['type' => 'string', 'default' => ''],
                    'parallax' => ['type' => 'boolean', 'default' => false],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'google-map':
                return [
                    'address' => ['type' => 'string', 'default' => ''],
                    'zoom' => ['type' => 'number', 'default' => 14],
                    'height' => ['type' => 'string', 'default' => '380px'],
                    'mapFilter' => ['type' => 'string', 'default' => ''],
                    'overlayColor' => ['type' => 'string', 'default' => ''],
                    'overlayOpacity' => ['type' => 'number', 'default' => 0.2],
                    'embedUrl' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'file':
                return [
                    'title' => ['type' => 'string', 'default' => 'File'],
                    'fileUrl' => ['type' => 'string', 'default' => ''],
                    'fileName' => ['type' => 'string', 'default' => ''],
                    'buttonText' => ['type' => 'string', 'default' => 'Download file'],
                    'targetBlank' => ['type' => 'boolean', 'default' => true],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'inline-svg':
                return [
                    'title' => ['type' => 'string', 'default' => 'Inline SVG'],
                    'svgCode' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'menu-option':
                return [
                    'title' => ['type' => 'string', 'default' => 'Menu Item'],
                    'titleTag' => ['type' => 'string', 'default' => 'h4'],
                    'menuSlug' => ['type' => 'string', 'default' => ''],
                    'showMenuTitle' => ['type' => 'boolean', 'default' => false],
                    'price' => ['type' => 'string', 'default' => ''],
                    'badge' => ['type' => 'string', 'default' => ''],
                    'text' => ['type' => 'string', 'default' => ''],
                    'bgColor' => ['type' => 'string', 'default' => ''],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'borderRadius' => ['type' => 'string', 'default' => ''],
                    'schemaEnable' => ['type' => 'boolean', 'default' => false],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'sitemap':
                return [
                    'title' => ['type' => 'string', 'default' => 'Sitemap'],
                    'titleTag' => ['type' => 'string', 'default' => 'h3'],
                    'showPages' => ['type' => 'boolean', 'default' => true],
                    'showPosts' => ['type' => 'boolean', 'default' => false],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'soc-feed':
                return [
                    'platform' => ['type' => 'string', 'default' => 'instagram'],
                    'sourceMode' => ['type' => 'string', 'default' => 'instagram_api'],
                    'title' => ['type' => 'string', 'default' => 'Instagram'],
                    'headingTag' => ['type' => 'string', 'default' => 'h2'],
                    'username' => ['type' => 'string', 'default' => ''],
                    'pageId' => ['type' => 'string', 'default' => ''],
                    'accessToken' => ['type' => 'string', 'default' => ''],
                    'refreshToken' => ['type' => 'string', 'default' => ''],
                    'clientKey' => ['type' => 'string', 'default' => ''],
                    'openId' => ['type' => 'string', 'default' => ''],
                    'shortcode' => ['type' => 'string', 'default' => ''],
                    'html' => ['type' => 'string', 'default' => ''],
                    'itemsJson' => ['type' => 'string', 'default' => ''],
                    'buttonText' => ['type' => 'string', 'default' => 'Follow'],
                    'buttonUrl' => ['type' => 'string', 'default' => ''],
                    'limit' => ['type' => 'number', 'default' => 8],
                    'slidesDesktop' => ['type' => 'number', 'default' => 4],
                    'slidesTablet' => ['type' => 'number', 'default' => 2],
                    'slidesMobile' => ['type' => 'number', 'default' => 1],
                    'mobileStack' => ['type' => 'boolean', 'default' => false],
                    'mobileFeedCount' => ['type' => 'number', 'default' => 3],
                    'spaceBetween' => ['type' => 'number', 'default' => 28],
                    'loop' => ['type' => 'boolean', 'default' => false],
                    'autoplay' => ['type' => 'boolean', 'default' => false],
                    'navigation' => ['type' => 'boolean', 'default' => true],
                    'pagination' => ['type' => 'boolean', 'default' => true],
                    'centered' => ['type' => 'boolean', 'default' => true],
                    'cardRadius' => ['type' => 'string', 'default' => '20px'],
                    'imageRatio' => ['type' => 'string', 'default' => '1 / 1'],
                    'customClasses' => ['type' => 'string', 'default' => ''],
                    'useSavedConnection' => ['type' => 'boolean', 'default' => true],
                ];
            case 'soc-follow-block':
                return [
                    'title' => ['type' => 'string', 'default' => 'Follow Us'],
                    'titleTag' => ['type' => 'string', 'default' => 'span'],
                    'socialStyle' => ['type' => 'string', 'default' => 'icons'], // icons, buttons, minimal
                    'iconSize' => ['type' => 'string', 'default' => 'md'], // sm, md, lg
                    'iconShape' => ['type' => 'string', 'default' => 'rounded'], // square, rounded, circle
                    'iconBgColor' => ['type' => 'string', 'default' => ''],
                    'iconTextColor' => ['type' => 'string', 'default' => ''],
                    'showLabels' => ['type' => 'boolean', 'default' => false],
                    'facebook' => ['type' => 'string', 'default' => ''],
                    'instagram' => ['type' => 'string', 'default' => ''],
                    'linkedin' => ['type' => 'string', 'default' => ''],
                    'x' => ['type' => 'string', 'default' => ''],
                    'youtube' => ['type' => 'string', 'default' => ''],
                    'tiktok' => ['type' => 'string', 'default' => ''],
                    'pinterest' => ['type' => 'string', 'default' => ''],
                    'whatsapp' => ['type' => 'string', 'default' => ''],
                    'email' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => '']
                ];

            case 'soc-share':
                return [
                    'title' => ['type' => 'string', 'default' => 'Share'],
                    'titleTag' => ['type' => 'string', 'default' => 'span'],
                    'iconStyle' => ['type' => 'string', 'default' => 'icons'], // icons, buttons
                    'iconSize' => ['type' => 'string', 'default' => 'md'],
                    'iconShape' => ['type' => 'string', 'default' => 'rounded'],
                    'iconBgColor' => ['type' => 'string', 'default' => ''],
                    'iconColor' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => '']
                ];










            case 'row':
                return array_merge([
                    'gutterX' => ['type' => 'string', 'default' => 'gx-3'],
                    'gutterY' => ['type' => 'string', 'default' => 'gy-3'],
                    'align' => ['type' => 'string', 'default' => ''],
                    'paddingClass' => ['type' => 'string', 'default' => ''],
                    'marginClass' => ['type' => 'string', 'default' => ''],
                    'backgroundClass' => ['type' => 'string', 'default' => ''],
                    'animationClass' => ['type' => 'string', 'default' => ''],
                    'displayClass' => ['type' => 'string', 'default' => ''],
                    'textUtilityClass' => ['type' => 'string', 'default' => ''],
                    'roundedClass' => ['type' => 'string', 'default' => ''],
                    'shadowClass' => ['type' => 'string', 'default' => ''],
                    'bootstrapClasses' => ['type' => 'string', 'default' => ''],
                    'customClasses' => ['type' => 'string', 'default' => ''],
                    'utilityClasses' => ['type' => 'string', 'default' => ''],
                    'spacingSm' => ['type' => 'string', 'default' => ''],'spacingMd' => ['type' => 'string', 'default' => ''],'spacingLg' => ['type' => 'string', 'default' => ''],'spacingXl' => ['type' => 'string', 'default' => ''],'spacingXxl' => ['type' => 'string', 'default' => ''],'paddingSm' => ['type' => 'string', 'default' => ''],'paddingMd' => ['type' => 'string', 'default' => ''],'paddingLg' => ['type' => 'string', 'default' => ''],'paddingXl' => ['type' => 'string', 'default' => ''],'paddingXxl' => ['type' => 'string', 'default' => ''],'marginSm' => ['type' => 'string', 'default' => ''],'marginMd' => ['type' => 'string', 'default' => ''],'marginLg' => ['type' => 'string', 'default' => ''],'marginXl' => ['type' => 'string', 'default' => ''],'marginXxl' => ['type' => 'string', 'default' => ''],'uniqueId' => ['type' => 'string', 'default' => ''],'customCss' => ['type' => 'string', 'default' => ''],'customScss' => ['type' => 'string', 'default' => ''],
                    'paddingTop' => ['type' => 'number', 'default' => 0],
                    'paddingTopUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingRight' => ['type' => 'number', 'default' => 0],
                    'paddingRightUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingBottom' => ['type' => 'number', 'default' => 0],
                    'paddingBottomUnit' => ['type' => 'string', 'default' => 'px'],
                    'paddingLeft' => ['type' => 'number', 'default' => 0],
                    'paddingLeftUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginTop' => ['type' => 'number', 'default' => 0],
                    'marginTopUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginRight' => ['type' => 'number', 'default' => 0],
                    'marginRightUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginBottom' => ['type' => 'number', 'default' => 0],
                    'marginBottomUnit' => ['type' => 'string', 'default' => 'px'],
                    'marginLeft' => ['type' => 'number', 'default' => 0],
                    'marginLeftUnit' => ['type' => 'string', 'default' => 'px'],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'customStyle' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                    'maxWidth' => ['type' => 'string', 'default' => ''],
                    'maxWidthUnit' => ['type' => 'string', 'default' => 'px'],
                    'containerClass' => ['type' => 'string', 'default' => ''],
                    'visibilityClass' => ['type' => 'string', 'default' => ''],
                    'visibilityXs' => ['type' => 'boolean', 'default' => true],
                    'visibilitySm' => ['type' => 'boolean', 'default' => true],
                    'visibilityMd' => ['type' => 'boolean', 'default' => true],
                    'visibilityLg' => ['type' => 'boolean', 'default' => true],
                    'visibilityXl' => ['type' => 'boolean', 'default' => true],
                ], $this->wpbb_responsive_spacing_attributes());
            case 'column':
                return array_merge([
                    'xs' => ['type' => 'number', 'default' => 12],
                    'sm' => ['type' => 'number', 'default' => 0],
                    'md' => ['type' => 'number', 'default' => 0],
                    'lg' => ['type' => 'number', 'default' => 0],
                    'xl' => ['type' => 'number', 'default' => 0],
                    'xxl' => ['type' => 'number', 'default' => 0],
                    'className' => ['type' => 'string', 'default' => ''],
                ], $this->wpbb_responsive_spacing_attributes());
            case 'video':
                return [
                    'videoUrl' => ['type' => 'string', 'default' => ''],
                    'poster' => ['type' => 'string', 'default' => ''],
                    'ratioClass' => ['type' => 'string', 'default' => 'ratio ratio-16x9'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'whatsapp-chat':
                return [
                    'label' => ['type' => 'string', 'default' => 'Chat on WhatsApp'],
                    'phone' => ['type' => 'string', 'default' => ''],
                    'message' => ['type' => 'string', 'default' => 'Hi, I would like to chat.'],
                    'position' => ['type' => 'string', 'default' => 'bottom-right'],
                    'bgColor' => ['type' => 'string', 'default' => ''],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'row-section':
                return [
                    'sectionClass' => ['type' => 'string', 'default' => 'py-5'],
                    'containerClass' => ['type' => 'string', 'default' => 'container-fluid'],
                    'backgroundClass' => ['type' => 'string', 'default' => ''],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'backgroundColour' => ['type' => 'string', 'default' => ''],
                    'bgColor' => ['type' => 'string', 'default' => ''],
                    'backgroundGradient' => ['type' => 'string', 'default' => ''],
                    'backgroundImageUrl' => ['type' => 'string', 'default' => ''],
                    'backgroundImage' => ['type' => 'string', 'default' => ''],
                    'backgroundSize' => ['type' => 'string', 'default' => 'cover'],
                    'backgroundPosition' => ['type' => 'string', 'default' => 'center center'],
                    'backgroundPositionXSide' => ['type' => 'string', 'default' => 'left'],
                    'backgroundPositionYSide' => ['type' => 'string', 'default' => 'top'],
                    'backgroundPositionXPercent' => ['type' => 'string', 'default' => ''],
                    'backgroundPositionYPercent' => ['type' => 'string', 'default' => ''],
                    'backgroundAttachment' => ['type' => 'string', 'default' => 'scroll'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'table':
                return [
                    'csvText' => ['type' => 'string', 'default' => 'Name,Role\nJohn,Designer\nAnna,Developer'],
                    'csvFileName' => ['type' => 'string', 'default' => ''],
                    'delimiter' => ['type' => 'string', 'default' => ','],
                    'datatable' => ['type' => 'boolean', 'default' => true],
                    'datatableSearch' => ['type' => 'boolean', 'default' => true],
                    'datatablePaging' => ['type' => 'boolean', 'default' => true],
                    'datatableOrdering' => ['type' => 'boolean', 'default' => true],
                    'datatableInfo' => ['type' => 'boolean', 'default' => true],
                    'datatableLengthChange' => ['type' => 'boolean', 'default' => true],
                    'useFirstRowHeader' => ['type' => 'boolean', 'default' => true],
                    'tableClass' => ['type' => 'string', 'default' => 'table table-striped table-hover'],
                    'responsive' => ['type' => 'boolean', 'default' => true],
                    'small' => ['type' => 'boolean', 'default' => false],
                    'bordered' => ['type' => 'boolean', 'default' => false],
                    'className' => ['type' => 'string', 'default' => ''],
                ];

            case 'cta-card':
                return ['title'=>['type'=>'string','default'=>'CTA Card'],'titleTag'=>['type'=>'string','default'=>'h3'],'text'=>['type'=>'string','default'=>'Call to action text'],'buttonText'=>['type'=>'string','default'=>'Learn more'],'buttonUrl'=>['type'=>'string','default'=>'#'],'bgColor'=>['type'=>'string','default'=>''],'textColor'=>['type'=>'string','default'=>''],'className'=>['type'=>'string','default'=>'']];
            case 'cta-section':
                return ['title'=>['type'=>'string','default'=>'CTA Section'],'titleTag'=>['type'=>'string','default'=>'h2'],'text'=>['type'=>'string','default'=>'Call to action text'],'buttonText'=>['type'=>'string','default'=>'Get started'],'buttonUrl'=>['type'=>'string','default'=>'#'],'bgColor'=>['type'=>'string','default'=>''],'textColor'=>['type'=>'string','default'=>''],'backgroundImage'=>['type'=>'string','default'=>''],'parallax'=>['type'=>'boolean','default'=>false],'className'=>['type'=>'string','default'=>'']];
            case 'google-map':
                return [
                    'address'=>['type'=>'string','default'=>''],
                    'zoom'=>['type'=>'number','default'=>14],
                    'height'=>['type'=>'string','default'=>'380px'],
                    'mapFilter'=>['type'=>'string','default'=>''],
                    'overlayColor'=>['type'=>'string','default'=>''],
                    'overlayOpacity'=>['type'=>'number','default'=>0.2],
                    'embedUrl'=>['type'=>'string','default'=>''],
                    'className'=>['type'=>'string','default'=>'']
                ];
            case 'file':
                return [
                    'title' => ['type' => 'string', 'default' => 'File'],
                    'fileUrl' => ['type' => 'string', 'default' => ''],
                    'fileName' => ['type' => 'string', 'default' => ''],
                    'buttonText' => ['type' => 'string', 'default' => 'Download file'],
                    'targetBlank' => ['type' => 'boolean', 'default' => true],
                    'className' => ['type' => 'string', 'default' => '']
                ];
            case 'inline-svg':
                return [
                    'title' => ['type' => 'string', 'default' => 'Inline SVG'],
                    'svgCode' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => '']
                ];
            case 'menu-option':
                return [
                    'title' => ['type' => 'string', 'default' => 'Menu'],
                    'menuSlug' => ['type' => 'string', 'default' => ''],
                    'showMenuTitle' => ['type' => 'boolean', 'default' => false],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'feature-list':
                return ['title'=>['type'=>'string','default'=>'Features'],'itemsJson'=>['type'=>'string','default'=>''],'iconColor'=>['type'=>'string','default'=>'#2563eb']];
            case 'timeline':
                return ['title'=>['type'=>'string','default'=>'Timeline'],'layout'=>['type'=>'string','default'=>'vertical'],'itemsJson'=>['type'=>'string','default'=>'']];
            case 'custom-embed':
                return ['title'=>['type'=>'string','default'=>'Embed'],'embedUrl'=>['type'=>'string','default'=>''],'embedHtml'=>['type'=>'string','default'=>''],'height'=>['type'=>'string','default'=>'420px']];
            case 'ai-content':
                return ['title'=>['type'=>'string','default'=>'AI Content'],'shortDescription'=>['type'=>'string','default'=>''],'prompt'=>['type'=>'string','default'=>''],'generatedText'=>['type'=>'string','default'=>''],'provider'=>['type'=>'string','default'=>'custom-api']];
            case 'login-register':
                return ['title'=>['type'=>'string','default'=>'Account Access'],'showRegister'=>['type'=>'boolean','default'=>true],'styleVariant'=>['type'=>'string','default'=>'split']];
            case 'bootstrap-div':
                return [
                    'tagName' => ['type' => 'string', 'default' => 'div'],
                    'containerClass' => ['type' => 'string', 'default' => 'container'],
                    'maxWidth' => ['type' => 'string', 'default' => ''],
                    'maxWidthUnit' => ['type' => 'string', 'default' => 'px'],
                    'maxHeight' => ['type' => 'string', 'default' => ''],
                    'minHeight' => ['type' => 'string', 'default' => ''],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'backgroundGradient' => ['type' => 'string', 'default' => ''],
                    'backgroundImageUrl' => ['type' => 'string', 'default' => ''],
                    'backgroundImage' => ['type' => 'string', 'default' => ''],
                    'backgroundSize' => ['type' => 'string', 'default' => 'cover'],
                    'backgroundPosition' => ['type' => 'string', 'default' => 'center center'],
                    'backgroundRepeat' => ['type' => 'string', 'default' => 'no-repeat'],
                    'backgroundAttachment' => ['type' => 'string', 'default' => 'scroll'],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'borderRadius' => ['type' => 'string', 'default' => ''],
                    'padding' => ['type' => 'string', 'default' => ''],
                    'margin' => ['type' => 'string', 'default' => ''],
                    'utilityClasses' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'menu-option':
                return ['title'=>['type'=>'string','default'=>'Menu Item'],'price'=>['type'=>'string','default'=>''],'text'=>['type'=>'string','default'=>''],'bgColor'=>['type'=>'string','default'=>''],'textColor'=>['type'=>'string','default'=>''],'className'=>['type'=>'string','default'=>'']];
            case 'sitemap':
                return ['title'=>['type'=>'string','default'=>'Sitemap'],'titleTag'=>['type'=>'string','default'=>'h3'],'showPages'=>['type'=>'boolean','default'=>true],'showPosts'=>['type'=>'boolean','default'=>false],'className'=>['type'=>'string','default'=>'']];
            case 'soc-follow-block':
                return [
                    'title' => ['type' => 'string', 'default' => 'Follow Us'],
                    'titleTag' => ['type' => 'string', 'default' => 'span'],
                    'linksJson' => ['type' => 'string', 'default' => '[]'],
                    'socialStyle' => ['type' => 'string', 'default' => 'icons'],
                    'iconSize' => ['type' => 'string', 'default' => 'md'],
                    'iconShape' => ['type' => 'string', 'default' => 'rounded'],
                    'showLabels' => ['type' => 'boolean', 'default' => false],
                    'iconBgColor' => ['type' => 'string', 'default' => ''],
                    'iconColor' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'soc-share':
                return [
                    'title' => ['type' => 'string', 'default' => 'Share'],
                    'titleTag' => ['type' => 'string', 'default' => 'span'],
                    'shareUrl' => ['type' => 'string', 'default' => ''],
                    'shareTitle' => ['type' => 'string', 'default' => ''],
                    'socialStyle' => ['type' => 'string', 'default' => 'icons'],
                    'iconSize' => ['type' => 'string', 'default' => 'md'],
                    'iconShape' => ['type' => 'string', 'default' => 'rounded'],
                    'iconBgColor' => ['type' => 'string', 'default' => ''],
                    'iconColor' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'video':
                return [
                    'videoUrl' => ['type' => 'string', 'default' => ''],
                    'poster' => ['type' => 'string', 'default' => ''],
                    'ratioClass' => ['type' => 'string', 'default' => 'ratio ratio-16x9'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'row-section':
                return [
                    'sectionClass' => ['type' => 'string', 'default' => ''],
                    'backgroundClass' => ['type' => 'string', 'default' => ''],
                    'containerClass' => ['type' => 'string', 'default' => 'container-fluid'],
                    'maxWidth' => ['type' => 'string', 'default' => ''],
                    'backgroundColor' => ['type' => 'string', 'default' => ''],
                    'backgroundColour' => ['type' => 'string', 'default' => ''],
                    'bgColor' => ['type' => 'string', 'default' => ''],
                    'bgColour' => ['type' => 'string', 'default' => ''],
                    'backgroundGradient' => ['type' => 'string', 'default' => ''],
                    'backgroundImageUrl' => ['type' => 'string', 'default' => ''],
                    'backgroundImage' => ['type' => 'string', 'default' => ''],
                    'backgroundSize' => ['type' => 'string', 'default' => 'cover'],
                    'backgroundPosition' => ['type' => 'string', 'default' => 'center center'],
                    'backgroundPositionXSide' => ['type' => 'string', 'default' => 'left'],
                    'backgroundPositionYSide' => ['type' => 'string', 'default' => 'top'],
                    'backgroundPositionXPercent' => ['type' => 'string', 'default' => ''],
                    'backgroundPositionYPercent' => ['type' => 'string', 'default' => ''],
                    'backgroundAttachment' => ['type' => 'string', 'default' => 'scroll'],
                    'textColor' => ['type' => 'string', 'default' => ''],
                    'customStyle' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'tabs':
                return [
                    'className' => ['type' => 'string', 'default' => ''],
                ];
            case 'tab-item':
                return [
                    'title' => ['type' => 'string', 'default' => 'Tab'],
                    'className' => ['type' => 'string', 'default' => ''],
                ];

            default:
                return ['className' => ['type' => 'string', 'default' => '']];
        }
    }


public function render_alert_block($attributes, $content, $block) {
    $variant = sanitize_html_class($attributes['variant'] ?? 'primary');
    $text = wp_kses_post($attributes['text'] ?? '');
    $dismissible = !empty($attributes['dismissible']);
    if ($dismissible) WPBBuilder_Bootstrap::needs(['alert']);
    $classes = 'wpbb-alert alert alert-' . $variant . ($dismissible ? ' alert-dismissible fade show' : '');
    $wrapper = get_block_wrapper_attributes(['class' => $classes]);
    $button = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' : '';
    WPBBuilder_Bootstrap::enqueue_js_if_needed();
    return '<div ' . $wrapper . '>' . $text . $button . '</div>';
}

public function render_badge_block($attributes, $content, $block) {
    $variant = sanitize_html_class($attributes['variant'] ?? 'primary');
    $pill = !empty($attributes['pill']) ? ' rounded-pill' : '';
    $text = esc_html($attributes['text'] ?? '');
    $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-badge']);
    return '<div ' . $wrapper . '><span class="badge text-bg-' . $variant . $pill . '">' . $text . '</span></div>';
}

public function render_breadcrumb_block($attributes, $content, $block) {
    $items = wpbb_parse_fields_json($attributes['itemsJson'] ?? '[]');
    if (empty($items)) return '';
    $out = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        $label = esc_html($item['label'] ?? 'Item');
        $url = esc_url($item['url'] ?? '');
        if ($i === $last || $url === '') {
            $out .= '<li class="breadcrumb-item active" aria-current="page">' . $label . '</li>';
        } else {
            $out .= '<li class="breadcrumb-item"><a href="' . $url . '">' . $label . '</a></li>';
        }
    }
    $out .= '</ol></nav>';
    $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-breadcrumb']);
    return '<div ' . $wrapper . '>' . $out . '</div>';
}

public function render_list_group_block($attributes, $content, $block) {
    $items = wpbb_parse_fields_json($attributes['itemsJson'] ?? '[]');
    $tag = !empty($attributes['numbered']) ? 'ol' : 'ul';
    $classes = 'list-group' . (!empty($attributes['flush']) ? ' list-group-flush' : '') . (!empty($attributes['numbered']) ? ' list-group-numbered' : '');
    $html = '<' . $tag . ' class="' . esc_attr($classes) . '">';
    foreach ($items as $item) {
        $text = esc_html($item['text'] ?? 'Item');
        $active = !empty($item['active']) ? ' active' : '';
        $html .= '<li class="list-group-item' . $active . '">' . $text . '</li>';
    }
    $html .= '</' . $tag . '>';
    $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-list-group']);
    return '<div ' . $wrapper . '>' . $html . '</div>';
}

public function render_navbar_block($attributes, $content, $block) {
    WPBBuilder_Bootstrap::needs(['collapse','navbar']);
    WPBBuilder_Bootstrap::enqueue_js_if_needed();
    $brand = esc_html($attributes['brand'] ?? 'BBuilder');
    $brand_url = esc_url($attributes['brandUrl'] ?? '/');
    $expand = sanitize_html_class($attributes['expand'] ?? 'lg');
    $scheme = sanitize_html_class($attributes['scheme'] ?? 'light');
    $bg = sanitize_html_class($attributes['bgClass'] ?? 'bg-light');
    $items = wpbb_parse_fields_json($attributes['itemsJson'] ?? '[]');
    $id = 'wpbb-navbar-' . wp_generate_password(6, false, false);
    $links = '';
    foreach ($items as $item) {
        $label = esc_html($item['label'] ?? 'Link');
        $url = esc_url($item['url'] ?? '#');
        $active = !empty($item['active']) ? ' active' : '';
        $links .= '<li class="nav-item"><a class="nav-link' . $active . '" href="' . $url . '">' . $label . '</a></li>';
    }
    $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-navbar']);
    return '<nav ' . $wrapper . '><div class="navbar navbar-expand-' . $expand . ' navbar-' . $scheme . ' ' . $bg . ' rounded-4 px-3 py-2"><div class="container-fluid p-0"><a class="navbar-brand" href="' . $brand_url . '">' . $brand . '</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#' . esc_attr($id) . '" aria-controls="' . esc_attr($id) . '" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="' . esc_attr($id) . '"><ul class="navbar-nav ms-auto mb-2 mb-lg-0">' . $links . '</ul></div></div></div></nav>';
}

public function render_progress_block($attributes, $content, $block) {
    $value = max(0, min(100, intval($attributes['value'] ?? 0)));
    $label = esc_html($attributes['label'] ?? 'Progress');
    $variant = sanitize_html_class($attributes['variant'] ?? 'success');
    $bar_classes = 'progress-bar bg-' . $variant . (!empty($attributes['striped']) ? ' progress-bar-striped' : '') . (!empty($attributes['animated']) ? ' progress-bar-animated' : '');
    $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-progress']);
    return '<div ' . $wrapper . '><div class="d-flex justify-content-between small mb-2"><span>' . $label . '</span><strong>' . $value . '%</strong></div><div class="progress" role="progressbar" aria-label="' . $label . '" aria-valuenow="' . $value . '" aria-valuemin="0" aria-valuemax="100"><div class="' . esc_attr($bar_classes) . '" style="width:' . $value . '%">' . $value . '%</div></div></div>';
}

public function render_section_block($attributes, $content, $block) {
    $title = esc_html($attributes['title'] ?? 'Section');
    $lead = wp_kses_post($attributes['lead'] ?? '');
    $container_tokens = $this->wpbb_class_tokens_from_value($attributes['containerClass'] ?? 'container');
    $container = implode(' ', array_values(array_unique(array_filter($container_tokens))));
    if ($container === '') $container = 'container';

    $bg_tokens = $this->wpbb_class_tokens_from_value($attributes['backgroundClass'] ?? 'py-5');
    $section_classes = array_merge(['wpbb-section'], $bg_tokens);
    if (!empty($attributes['className'])) {
        $section_classes = array_merge($section_classes, $this->wpbb_class_tokens_from_value($attributes['className']));
    }

    $style = $this->wpbb_build_background_inline($attributes, true);
    $overlay = '';
    $content_style = '';
    if (!empty($attributes['overlayColor']) && !empty($attributes['overlayOpacity'])) {
        $opacity = max(0, min(1, floatval($attributes['overlayOpacity'])));
        if ($opacity > 0) {
            $style .= 'position:relative;overflow:hidden;';
            $overlay = '<div class="wpbb-block-overlay" style="position:absolute;inset:0;pointer-events:none;background:' . esc_attr((string)$attributes['overlayColor']) . ';opacity:' . $opacity . ';"></div>';
            $content_style = ' style="position:relative;z-index:1"';
        }
    }

    $wrapper = get_block_wrapper_attributes([
        'class' => implode(' ', array_values(array_unique(array_filter($section_classes)))),
        'style' => $style,
    ]);

    return '<section ' . $wrapper . '>' . $overlay . '<div class="' . esc_attr($container) . '"' . $content_style . '><div class="wpbb-section__intro mb-4"><h2 class="h3 mb-2">' . $title . '</h2><div class="text-secondary">' . $lead . '</div></div>' . $content . '</div></section>';
}

public function render_spinner_block($attributes, $content, $block) {
    $type = ($attributes['type'] ?? 'border') === 'grow' ? 'spinner-grow' : 'spinner-border';
    $variant = sanitize_html_class($attributes['variant'] ?? 'primary');
    $label = esc_html($attributes['label'] ?? 'Loading');
    $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-spinner text-' . $variant]);
    return '<div ' . $wrapper . '><div class="' . $type . '" role="status"><span class="visually-hidden">' . $label . '</span></div></div>';
}

    public function render_table_block($attributes, $content, $block) {
        $csv = trim((string) ($attributes['csvText'] ?? ''));
        if ($csv === '') return '';

        // Normalize all newline formats saved by block attributes
        $csv = str_replace(["\\\\r\\\\n", "\\\\n", "\\\\r"], "\n", $csv);
        $csv = str_replace(["\\r\\n", "\\n", "\\r"], "\n", $csv);
        $csv = str_replace(["\r\n", "\r"], "\n", $csv);

        $delimiter = !empty($attributes['delimiter']) ? (string) $attributes['delimiter'] : ',';
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $rows = [];
        $stream = fopen('php://temp', 'r+');
        if ($stream) {
            fwrite($stream, $csv);
            rewind($stream);
            while (($data = fgetcsv($stream, 0, $delimiter)) !== false) {
                if ($data === [null]) continue;
                $rows[] = array_map(function($cell) {
                    return is_string($cell) ? trim($cell) : $cell;
                }, $data);
            }
            fclose($stream);
        }
        if (empty($rows)) return '';

        $use_header = !empty($attributes['useFirstRowHeader']);
        $headers = $use_header ? array_shift($rows) : [];
        $col_count = !empty($headers) ? count($headers) : 0;
        foreach ($rows as $row) {
            $col_count = max($col_count, count((array)$row));
        }
        if ($col_count < 1) return '';

        if ($use_header && empty($headers)) {
            $headers = array_fill(0, $col_count, '');
        } elseif (!empty($headers) && count($headers) < $col_count) {
            $headers = array_pad($headers, $col_count, '');
        }

        $table_classes = trim((string) ($attributes['tableClass'] ?? ''));
        if ($table_classes === '') {
            $table_classes = 'table table-striped table-hover align-middle';
        } elseif (strpos(' ' . $table_classes . ' ', ' table ') === false) {
            $table_classes = 'table ' . $table_classes;
        }
        if (!empty($attributes['small']) && strpos($table_classes, 'table-sm') === false) {
            $table_classes .= ' table-sm';
        }
        if (!empty($attributes['bordered']) && strpos($table_classes, 'table-bordered') === false) {
            $table_classes .= ' table-bordered';
        }

        $table_html = '<table class="' . esc_attr(trim($table_classes)) . '">';
        if ($use_header) {
            $table_html .= '<thead><tr>';
            foreach ($headers as $header) {
                $table_html .= '<th scope="col">' . esc_html($header) . '</th>';
            }
            $table_html .= '</tr></thead>';
        } elseif (!empty($attributes['datatable'])) {
            $table_html .= '<thead><tr>';
            for ($i = 0; $i < $col_count; $i++) {
                $table_html .= '<th scope="col">' . esc_html('Column ' . ($i + 1)) . '</th>';
            }
            $table_html .= '</tr></thead>';
        }

        $table_html .= '<tbody>';
        foreach ($rows as $row) {
            $row = array_pad((array)$row, $col_count, '');
            $table_html .= '<tr>';
            foreach ($row as $cell) {
                $table_html .= '<td>' . esc_html($cell) . '</td>';
            }
            $table_html .= '</tr>';
        }
        $table_html .= '</tbody></table>';

        if (!empty($attributes['responsive']) && empty($attributes['datatable'])) {
            $table_html = '<div class="table-responsive">' . $table_html . '</div>';
        }

        $wrapper_args = ['class' => 'wpbb-table-block'];

        if (!empty($attributes['datatable'])) {
            wp_enqueue_style('wpbb-datatables');
            wp_enqueue_script('jquery');
            wp_enqueue_script('wpbb-datatables');
            wp_enqueue_script('wpbb-datatables-bs5');
            wp_enqueue_script('wpbb-table-init');

            $wrapper_args['data-datatable'] = '1';
            $wrapper_args['data-searching'] = !empty($attributes['datatableSearch']) ? '1' : '0';
            $wrapper_args['data-paging'] = !empty($attributes['datatablePaging']) ? '1' : '0';
            $wrapper_args['data-ordering'] = !empty($attributes['datatableOrdering']) ? '1' : '0';
            $wrapper_args['data-info'] = !empty($attributes['datatableInfo']) ? '1' : '0';
            $wrapper_args['data-lengthchange'] = !empty($attributes['datatableLengthChange']) ? '1' : '0';
        }

        $wrapper = get_block_wrapper_attributes($wrapper_args);
        return '<div ' . $wrapper . '>' . $table_html . '</div>';
    }

    public function render_swiper_block($attributes, $content, $block) {
        wp_enqueue_style('wpbb-swiper');
        wp_enqueue_script('wpbb-swiper');
        wp_enqueue_script('wpbb-swiper-init');

        $slides = (!empty($attributes['slides']) && is_array($attributes['slides'])) ? $attributes['slides'] : wpbb_parse_fields_json($attributes['slidesJson'] ?? '');
        if (!$slides) {
            $slides = [
                ['type' => 'hero', 'eyebrow' => 'New', 'title' => 'Build a stronger homepage', 'text' => 'Use structured slides, responsive controls and accessible navigation.'],
                ['type' => 'card', 'title' => 'Reusable slide', 'text' => 'Cards work well for services, products, people and stories.'],
            ];
        }

        $effect = in_array(($attributes['effect'] ?? 'slide'), ['slide', 'fade'], true) ? $attributes['effect'] : 'slide';
        $wrapper = get_block_wrapper_attributes([
            'class' => 'wpbb-swiper-block wpbb-swiper--' . sanitize_html_class($attributes['demoStyle'] ?? 'cards') . ' wpbb-swiper--effect-' . $effect,
            'data-swiper' => '1',
            'data-slides' => (string) max(1, intval($attributes['slidesPerView'] ?? 1)),
            'data-slides-tablet' => (string) max(1, intval($attributes['slidesTablet'] ?? 1)),
            'data-slides-mobile' => (string) max(1, intval($attributes['slidesMobile'] ?? 1)),
            'data-space' => (string) max(0, intval($attributes['spaceBetween'] ?? 20)),
            'data-speed' => (string) max(100, intval($attributes['speed'] ?? 600)),
            'data-loop' => !empty($attributes['loop']) ? '1' : '0',
            'data-rewind' => !empty($attributes['rewind']) ? '1' : '0',
            'data-autoplay' => !empty($attributes['autoplay']) ? '1' : '0',
            'data-autoplay-delay' => (string) max(1000, intval($attributes['autoplayDelay'] ?? 4500)),
            'data-pause-hover' => !empty($attributes['pauseOnHover']) ? '1' : '0',
            'data-centered' => !empty($attributes['centeredSlides']) ? '1' : '0',
            'data-effect' => $effect,
        ]);

        $html = '<div ' . $wrapper . '><div class="swiper"><div class="swiper-wrapper">';
        foreach ($slides as $index => $slide) {
            $type = sanitize_html_class($slide['type'] ?? 'text');
            $eyebrow = sanitize_text_field($slide['eyebrow'] ?? '');
            $title = sanitize_text_field($slide['title'] ?? '');
            $text = wp_kses_post($slide['text'] ?? '');
            $image = esc_url($slide['image'] ?? '');
            $video = esc_url($slide['video'] ?? '');
            $button_text = sanitize_text_field($slide['buttonText'] ?? $slide['button'] ?? '');
            $button_url = esc_url($slide['buttonUrl'] ?? $slide['url'] ?? '');
            $secondary_text = sanitize_text_field($slide['secondaryText'] ?? '');
            $secondary_url = esc_url($slide['secondaryUrl'] ?? '');

            $html .= '<div class="swiper-slide"><article class="wpbb-swiper-slide wpbb-swiper-slide--' . esc_attr($type) . '" data-slide-index="' . esc_attr((string) $index) . '">';
            if ($image) {
                $html .= '<div class="wpbb-swiper-slide__media"><img src="' . $image . '" alt="" loading="' . ($index === 0 ? 'eager' : 'lazy') . '" decoding="async"></div>';
            }
            $html .= '<div class="wpbb-swiper-slide__content">';
            if ($eyebrow) $html .= '<p class="wpbb-swiper-slide__eyebrow">' . esc_html($eyebrow) . '</p>';
            if ($title) $html .= '<h2 class="wpbb-swiper-slide__title">' . esc_html($title) . '</h2>';
            if ($text) $html .= '<div class="wpbb-swiper-slide__text">' . $text . '</div>';
            if ($video) $html .= '<div class="ratio ratio-16x9 wpbb-swiper-slide__video"><iframe src="' . $video . '" allowfullscreen loading="lazy" title="' . esc_attr($title ?: __('Video', 'wp-bbuilder')) . '"></iframe></div>';
            if (($button_text && $button_url) || ($secondary_text && $secondary_url)) {
                $html .= '<div class="wpbb-swiper-slide__actions">';
                if ($button_text && $button_url) $html .= '<a class="btn btn-primary" href="' . $button_url . '">' . esc_html($button_text) . '</a>';
                if ($secondary_text && $secondary_url) $html .= '<a class="btn btn-outline-secondary" href="' . $secondary_url . '">' . esc_html($secondary_text) . '</a>';
                $html .= '</div>';
            }
            $html .= '</div></article></div>';
        }
        $html .= '</div>';
        if (!empty($attributes['showPagination'])) $html .= '<div class="swiper-pagination" aria-label="' . esc_attr__('Slider pagination', 'wp-bbuilder') . '"></div>';
        if (!empty($attributes['showNavigation'])) {
            $html .= '<button class="swiper-button-prev" type="button" aria-label="' . esc_attr__('Previous slide', 'wp-bbuilder') . '"></button>';
            $html .= '<button class="swiper-button-next" type="button" aria-label="' . esc_attr__('Next slide', 'wp-bbuilder') . '"></button>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function render_dynamic_form($attributes, $content, $block) {
        $title = esc_html($attributes['formTitle'] ?? __('Contact form', 'wp-bbuilder'));
        $recipient = sanitize_email($attributes['recipient'] ?? '');
        if (!$recipient) {
            $recipient = sanitize_email(wpbb_get_option('default_recipient_email', get_option('admin_email')));
        }
        $recipient = esc_attr($recipient);
        $subject = esc_attr($attributes['emailSubject'] ?? $attributes['subject'] ?? __('New form submission', 'wp-bbuilder'));
        $success = esc_attr($attributes['successMessage'] ?? wpbb_get_option('default_success_message', __('Thank you for your submission!', 'wp-bbuilder')));
        $validation = esc_attr(wpbb_get_option('default_validation_text', __('Please fill in all required fields correctly.', 'wp-bbuilder')));
        $submit_text = esc_html($attributes['submitText'] ?? __('Submit', 'wp-bbuilder'));
        $btn_class = esc_attr($attributes['buttonClass'] ?? wpbb_get_option('button_class', 'btn btn-primary'));
        $form_class = esc_attr($attributes['formClass'] ?? wpbb_get_option('form_class', 'wpbb-form'));
        $style = sanitize_html_class($attributes['stylePreset'] ?? $attributes['styleVariant'] ?? 'default');
        $label_pos = sanitize_html_class($attributes['labelPosition'] ?? 'top');
        $gap = max(0, intval($attributes['gap'] ?? 3));
        $enable_steps = !empty($attributes['enableSteps']);
        $enable_conditional = !empty($attributes['enableConditional']);
        $fields = (!empty($attributes['fields']) && is_array($attributes['fields'])) ? $attributes['fields'] : wpbb_parse_fields_json($attributes['fieldsJson'] ?? '');
        $hcaptcha_enabled = (bool) wpbb_get_option('hcaptcha_enabled', 0);
        $recaptcha_enabled = (bool) wpbb_get_option('recaptcha_enabled', 0);
        $hcaptcha_site_key = $hcaptcha_enabled ? wpbb_get_option('hcaptcha_site_key', '') : '';
        $hcaptcha_secret_key = $hcaptcha_enabled ? wpbb_get_option('hcaptcha_secret_key', '') : '';
        $recaptcha_site_key = $recaptcha_enabled ? wpbb_get_option('recaptcha_site_key', '') : '';
        $recaptcha_secret_key = $recaptcha_enabled ? wpbb_get_option('recaptcha_secret_key', '') : '';
        $captcha_provider = ($hcaptcha_site_key && $hcaptcha_secret_key) ? 'hcaptcha' : (($recaptcha_site_key && $recaptcha_secret_key) ? 'recaptcha' : '');
        if ($captcha_provider === 'hcaptcha') {
            wp_enqueue_script('hcaptcha-api', 'https://js.hcaptcha.com/1/api.js', [], null, true);
        } elseif ($captcha_provider === 'recaptcha') {
            wp_enqueue_script('recaptcha-api', 'https://www.google.com/recaptcha/api.js', [], null, true);
        }
        $honeypot_enabled = (bool) wpbb_get_option('form_honeypot_enabled', 1);

        if (empty($fields)) {
            $fields = [
                ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true, 'width' => 6, 'breakpoint' => 'md', 'xlWidth' => '', 'placeholder' => '', 'step' => 1],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true, 'width' => 6, 'breakpoint' => 'md', 'xlWidth' => '', 'placeholder' => '', 'step' => 1],
                ['type' => 'phone', 'name' => 'phone', 'label' => 'Phone', 'required' => false, 'width' => 6, 'breakpoint' => 'md', 'xlWidth' => '', 'placeholder' => '', 'step' => 1],
                ['type' => 'select', 'name' => 'language', 'label' => 'Language', 'required' => false, 'width' => 6, 'breakpoint' => 'md', 'xlWidth' => '', 'placeholder' => 'Select language', 'options' => "English
Latvian
Russian", 'step' => 1],
                ['type' => 'textarea', 'name' => 'message', 'label' => 'Message', 'required' => true, 'width' => 12, 'breakpoint' => 'md', 'xlWidth' => '', 'placeholder' => '', 'step' => 1],
            ];
        }

        $step_total = 1;
        if ($enable_steps) {
            foreach ($fields as $field) {
                $step_total = max($step_total, max(1, intval($field['step'] ?? 1)));
            }
        }

        $honeypot_id = 'wpbb-website-' . wp_unique_id();

        ob_start(); ?>
        <div <?php echo get_block_wrapper_attributes(['class' => "wpbb-dynamic-form-wrap style-{$style} labels-{$label_pos}"]); ?>>
            <?php if (!empty($attributes['showTitle'])): ?>
                <h3 class="wpbb-form-title"><?php echo $title; ?></h3>
            <?php endif; ?>
            <form class="<?php echo $form_class; ?> wpbb-dynamic-form" data-recipient="<?php echo $recipient; ?>" data-subject="<?php echo $subject; ?>" data-success="<?php echo $success; ?>" data-validation="<?php echo $validation; ?>" data-steps="<?php echo esc_attr($enable_steps ? '1' : '0'); ?>" data-conditional="<?php echo esc_attr($enable_conditional ? '1' : '0'); ?>" data-honeypot="<?php echo esc_attr($honeypot_enabled ? '1' : '0'); ?>" data-captcha-provider="<?php echo esc_attr($captcha_provider); ?>" enctype="multipart/form-data">
                <?php if ($honeypot_enabled): ?>
                    <div class="wpbb-form-bot-field" hidden aria-hidden="true">
                        <label for="<?php echo esc_attr($honeypot_id); ?>"><?php esc_html_e('Leave this field empty', 'wp-bbuilder'); ?></label>
                        <input id="<?php echo esc_attr($honeypot_id); ?>" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                        <input type="hidden" name="started_at" value="<?php echo esc_attr(time()); ?>">
                    </div>
                <?php endif; ?>
                <?php if ($enable_steps && $step_total > 1): ?>
                    <div class="wpbb-form-steps mb-3" data-total="<?php echo esc_attr($step_total); ?>">
                        <?php for ($i = 1; $i <= $step_total; $i++): ?>
                            <button type="button" class="wpbb-form-step-pill<?php echo $i === 1 ? ' is-active' : ''; ?>" data-step-target="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></button>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
                <?php for ($step = 1; $step <= $step_total; $step++): ?>
                <div class="wpbb-form-step-panel<?php echo $step === 1 ? ' is-active' : ''; ?>" data-step="<?php echo esc_attr($step); ?>">
                    <div class="row g-<?php echo $gap; ?>">
                        <?php foreach ($fields as $field):
                            $field_step = max(1, intval($field['step'] ?? 1));
                            if ($field_step !== $step) continue;
                            $type = sanitize_key($field['type'] ?? 'text');
                            if ($type === 'step') continue;
                            $name = sanitize_key($field['name'] ?? 'field');
                            $label = esc_html($field['label'] ?? $name);
                            $required = !empty($field['required']);
                            $placeholder = esc_attr($field['placeholder'] ?? '');
                            $width = max(1, min(12, intval($field['width'] ?? 6)));
                            $xl_width = isset($field['xlWidth']) && $field['xlWidth'] !== '' ? max(1, min(12, intval($field['xlWidth']))) : 0;
                            $breakpoint = isset($field['breakpoint']) ? sanitize_key((string) $field['breakpoint']) : 'md';
                            if (!in_array($breakpoint, ['md', 'lg', 'xl'], true)) {
                                $breakpoint = 'md';
                            }
                            $field_classes = ['col-12', 'wpbb-field-col', 'wpbb-field-col--' . sanitize_html_class($type), 'wpbb-field-col--' . sanitize_html_class($name)];
                            if ($xl_width > 0) {
                                $field_classes[] = 'col-xl-' . $xl_width;
                            } else {
                                $field_classes[] = 'col-' . $breakpoint . '-' . $width;
                            }
                            $field_class_attr = implode(' ', array_map('sanitize_html_class', $field_classes));
                            $input_id = 'wpbb-' . $name . '-' . wp_unique_id();
                            $options = isset($field['options']) ? preg_split('/
|
|
/', (string) $field['options']) : [];
                            $accept = esc_attr($field['accept'] ?? '');
                            $conditional_field = sanitize_key($field['conditionalField'] ?? '');
                            $conditional_value = esc_attr($field['conditionalValue'] ?? '');
                        ?>
                        <div class="<?php echo esc_attr($field_class_attr); ?>" data-wpbb-field-name="<?php echo esc_attr($name); ?>"<?php echo ($enable_conditional && $conditional_field) ? ' data-conditional-field="' . esc_attr($conditional_field) . '" data-conditional-value="' . $conditional_value . '"' : ''; ?>>
                            <div class="wpbb-field wpbb-field--<?php echo esc_attr($type); ?>">
                                <?php if ($label_pos !== 'hidden'): ?>
                                    <label class="form-label" for="<?php echo esc_attr($input_id); ?>"><?php echo $label; ?><?php echo $required ? ' *' : ''; ?></label>
                                <?php endif; ?>

                                <?php if ($type === 'textarea'): ?>
                                    <textarea id="<?php echo esc_attr($input_id); ?>" class="form-control" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo $placeholder; ?>" rows="4" <?php echo $required ? 'required' : ''; ?>></textarea>
                                <?php elseif ($type === 'select'): ?>
                                    <select id="<?php echo esc_attr($input_id); ?>" class="form-select" name="<?php echo esc_attr($name); ?>" <?php echo $required ? 'required' : ''; ?>>
                                        <option value=""><?php echo esc_html($placeholder ?: __('Select option', 'wp-bbuilder')); ?></option>
                                        <?php foreach ($options as $option): $option = trim($option); if ($option === '') continue; ?>
                                            <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($type === 'radio' || $type === 'checkbox'): ?>
                                    <div class="wpbb-choice-group">
                                        <?php foreach ($options as $idx => $option): $option = trim($option); if ($option === '') continue; $choice_id = $input_id . '-' . $idx; ?>
                                            <label class="wpbb-choice-item" for="<?php echo esc_attr($choice_id); ?>">
                                                <input id="<?php echo esc_attr($choice_id); ?>" type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type === 'checkbox' ? $name . '[]' : $name); ?>" value="<?php echo esc_attr($option); ?>" <?php echo $required && $idx === 0 ? 'required' : ''; ?>>
                                                <span><?php echo esc_html($option); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($type === 'file'): ?>
                                    <div class="wpbb-file-drop" data-file-drop>
                                        <input id="<?php echo esc_attr($input_id); ?>" class="form-control" type="file" name="<?php echo esc_attr($name); ?>" <?php echo $accept ? 'accept="' . $accept . '"' : ''; ?> <?php echo $required ? 'required' : ''; ?>>
                                        <div class="wpbb-file-drop__label"><?php echo esc_html($placeholder ?: __('Drop file here or click to upload', 'wp-bbuilder')); ?></div>
                                        <div class="wpbb-file-drop__meta"></div>
                                    </div>
                                <?php elseif ($type === 'signature'): ?>
                                    <div class="wpbb-signature" data-signature-wrap>
                                        <canvas class="wpbb-signature__canvas" width="600" height="220"></canvas>
                                        <input id="<?php echo esc_attr($input_id); ?>" type="hidden" name="<?php echo esc_attr($name); ?>" <?php echo $required ? 'required' : ''; ?>>
                                        <div class="wpbb-signature__actions mt-2 d-flex gap-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-signature-clear><?php esc_html_e('Clear', 'wp-bbuilder'); ?></button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <input id="<?php echo esc_attr($input_id); ?>" class="form-control" type="<?php echo esc_attr($type === 'email' ? 'email' : ($type === 'phone' ? 'tel' : ($type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text')))); ?>" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo $placeholder; ?>" <?php echo $required ? 'required' : ''; ?>>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if ($step === $step_total && $captcha_provider): ?>
                        <div class="col-12 wpbb-field-col wpbb-field-col--captcha">
                            <div class="wpbb-field wpbb-field--captcha">
                                <?php if ($captcha_provider === 'hcaptcha'): ?>
                                    <div class="h-captcha" data-sitekey="<?php echo esc_attr($hcaptcha_site_key); ?>"></div>
                                <?php else: ?>
                                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
                                <?php endif; ?>
                                <input type="hidden" name="wpbb_captcha_enabled" value="1">
                                <input type="hidden" name="wpbb_captcha_provider" value="<?php echo esc_attr($captcha_provider); ?>">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endfor; ?>

                <div class="wpbb-form-message mt-3" aria-live="polite"></div>
                <div class="wpbb-form-actions mt-3 d-flex flex-wrap gap-2 align-items-center">
                <?php if ($enable_steps && $step_total > 1): ?>
                        <button type="button" class="btn btn-outline-secondary wpbb-step-prev" hidden><?php esc_html_e('Back', 'wp-bbuilder'); ?></button>
                        <button type="button" class="btn btn-outline-primary wpbb-step-next"><?php esc_html_e('Next', 'wp-bbuilder'); ?></button>
                    <?php endif; ?>
                    <button type="submit" class="<?php echo $btn_class; ?><?php echo ($enable_steps && $step_total > 1) ? ' wpbb-submit-final' : ''; ?> mt-0"<?php echo ($enable_steps && $step_total > 1) ? ' hidden' : ''; ?>><?php echo $submit_text; ?></button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    
public function enqueue_frontend_assets() {
        if (is_admin()) return;
        if (!$this->page_has_wpbb_blocks()) return;

        WPBBuilder_Bootstrap::enqueue_css();
        if (wp_style_is('wpbb-shared', 'registered')) {
            wp_enqueue_style('wpbb-shared');
        }
        if (wp_style_is('wpbb-responsive-spacing', 'registered')) {
            wp_enqueue_style('wpbb-responsive-spacing');
        }
        $inline = ':root{'
            . '--wpbb-label-color:' . wpbb_hex_color(wpbb_get_option('default_label_color', '#334155')) . ';'
            . '--wpbb-input-border:' . wpbb_hex_color(wpbb_get_option('default_input_border_color', '#cbd5e1')) . ';'
            . '--wpbb-button-bg:' . wpbb_hex_color(wpbb_get_option('default_button_bg', '#2563eb')) . ';'
            . '--wpbb-button-text:' . wpbb_hex_color(wpbb_get_option('default_button_text', '#ffffff')) . ';'
            . '}';
        if (wp_style_is('wpbb-shared', 'registered')) {
            wp_add_inline_style('wpbb-shared', $inline);
        }
    }

    private function page_has_wpbb_blocks() {
        if (is_admin()) return false;
        if ((bool) apply_filters('wpbb_force_frontend_assets', false)) return true;
        if (is_singular()) {
            global $post;
            if (!$post || empty($post->post_content)) return false;
            return strpos($post->post_content, '<!-- wp:wpbb/') !== false;
        }
        return true;
    }


    public function filter_allowed_blocks($allowed_block_types, $editor_context) {
        $allowed = [];
        foreach (wpbb_get_blocks_list() as $slug) {
            if ($slug === 'row-section') continue;
            if (wpbb_is_block_enabled($slug)) {
                $allowed[] = 'wpbb/' . $slug;
            }
        }

        foreach (wpbb_get_acf_blocks_list() as $acf_block) {
            $allowed[] = 'acf/' . $acf_block;
        }

        if (wpbb_get_option('acf_field_block_enabled', 1)) {
            $allowed[] = 'wpbb/acf-field';
        }

        $core = [
            'core/paragraph','core/heading','core/list','core/list-item','core/quote','core/separator',
            'core/spacer','core/html','core/shortcode','core/code','core/preformatted','core/details'
        ];

        // Layout policy: native Group / Columns / Column are intentionally unavailable.
        // BBuilder Row + Column are the only grid/layout primitives; use BBuilder Div
        // when a neutral wrapper is genuinely required. Media & Text stays native.
        if (!wpbb_get_option('disable_core_table', 1)) $core[] = 'core/table';
        if (!wpbb_get_option('disable_core_embed', 0)) $core[] = 'core/embed';
        if (!wpbb_get_option('disable_core_gallery', 0)) $core[] = 'core/gallery';
        if (!wpbb_get_option('disable_core_image', 0)) $core[] = 'core/image';
        if (!wpbb_get_option('disable_core_cover', 0)) $core[] = 'core/cover';
        $core[] = 'core/media-text';
        if (!wpbb_get_option('disable_core_audio', 0)) $core[] = 'core/audio';
        if (!wpbb_get_option('disable_core_file', 0)) $core[] = 'core/file';
        if (!wpbb_get_option('disable_core_buttons', 0)) $core[] = 'core/buttons';
        if (!wpbb_get_option('disable_core_button', 0)) $core[] = 'core/button';
        if (!wpbb_get_option('disable_core_query', 0)) {
            $core = array_merge($core, [
                'core/query','core/post-template','core/query-pagination','core/query-pagination-next',
                'core/query-pagination-previous','core/query-pagination-numbers','core/post-title',
                'core/post-excerpt','core/post-date','core/post-featured-image','core/post-terms','core/read-more'
            ]);
        }

        return array_values(array_unique(array_merge($allowed, $core)));
    }

    private function normalize_legacy_layout_block_tree($blocks) {
        if (!is_array($blocks)) return $blocks;
        foreach ($blocks as &$block) {
            if (!is_array($block)) continue;
            $name = $block['blockName'] ?? '';
            if (!empty($block['innerBlocks'])) {
                $block['innerBlocks'] = $this->normalize_legacy_layout_block_tree($block['innerBlocks']);
            }
            if ($name === 'core/group') {
                $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
                $block['blockName'] = 'wpbb/bootstrap-div';
                $block['attrs'] = [
                    'containerClass' => '',
                    'utilityClasses' => trim((string)($attrs['className'] ?? '')),
                    'className' => 'wpbb-migrated-group',
                ];
                $block['innerHTML'] = '';
                $block['innerContent'] = array_fill(0, count((array)($block['innerBlocks'] ?? [])), null);
            } elseif ($name === 'core/columns') {
                $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
                $count = count(array_filter((array)($block['innerBlocks'] ?? []), function($child){ return is_array($child) && (($child['blockName'] ?? '') === 'wpbb/column' || ($child['blockName'] ?? '') === 'core/column'); }));
                $equal = ($count > 0 && 12 % $count === 0) ? (int)(12 / $count) : 0;
                foreach ($block['innerBlocks'] as &$child) {
                    if (!is_array($child) || ($child['blockName'] ?? '') !== 'wpbb/column') continue;
                    if (empty($child['attrs']['md']) && $equal) $child['attrs']['md'] = $equal;
                    if (empty($child['attrs']['xs'])) $child['attrs']['xs'] = 12;
                }
                unset($child);
                $block['blockName'] = 'wpbb/row';
                $block['attrs'] = [
                    'gutterX' => 'gx-3', 'gutterY' => 'gy-3',
                    'customClasses' => trim((string)($attrs['className'] ?? '')),
                    'className' => 'wpbb-migrated-columns',
                ];
                $block['innerHTML'] = '';
                $block['innerContent'] = array_fill(0, count((array)($block['innerBlocks'] ?? [])), null);
            } elseif ($name === 'core/column') {
                $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
                $span = 0;
                if (!empty($attrs['width']) && preg_match('/([0-9.]+)%/', (string)$attrs['width'], $m)) {
                    $span = max(1, min(12, (int)round(((float)$m[1] / 100) * 12)));
                }
                $block['blockName'] = 'wpbb/column';
                $block['attrs'] = [
                    'xs' => 12,
                    'md' => $span,
                    'customClasses' => trim((string)($attrs['className'] ?? '')),
                    'className' => 'wpbb-migrated-column',
                ];
                $block['innerHTML'] = '';
                $block['innerContent'] = array_fill(0, count((array)($block['innerBlocks'] ?? [])), null);
            }
            elseif ($name === 'core/buttons') {
                $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
                $justify = (string)($attrs['layout']['justifyContent'] ?? '');
                $classes = 'd-flex flex-wrap gap-2';
                if ($justify === 'right') $classes .= ' justify-content-end';
                elseif ($justify === 'center') $classes .= ' justify-content-center';
                $block['blockName'] = 'wpbb/bootstrap-div';
                $block['attrs'] = ['containerClass' => '', 'utilityClasses' => $classes, 'className' => 'wpbb-migrated-buttons'];
                $block['innerHTML'] = '';
                $block['innerContent'] = array_fill(0, count((array)($block['innerBlocks'] ?? [])), null);
            } elseif ($name === 'core/button') {
                $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
                $html = (string)($block['innerHTML'] ?? '');
                $text = trim(wp_strip_all_tags($html));
                $url = '#';
                if (preg_match('~href=["\']([^"\']+)["\']~i', $html, $m)) $url = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $btn_class = strpos((string)($attrs['className'] ?? ''), 'is-style-outline') !== false ? 'btn btn-outline-primary' : 'btn btn-primary';
                $block['blockName'] = 'wpbb/button';
                $block['attrs'] = ['text' => $text ?: __('Button', 'wp-bbuilder'), 'url' => esc_url_raw($url), 'btnClass' => $btn_class];
                $block['innerBlocks'] = [];
                $block['innerHTML'] = '';
                $block['innerContent'] = [];
            }
        }
        unset($block);
        return $blocks;
    }

    private function repair_serialized_block_markup($content) {
        if (!is_string($content) || $content === '') return $content;

        // Old sector demos sometimes saved an H3/H4 with a heading comment that had
        // no explicit level. Gutenberg then assumes H2 and reports validation errors.
        $content = preg_replace_callback(
            '~<!--\s*wp:heading(?:\s+(\{.*?\}))?\s*-->\s*<h([1-6])([^>]*)>~s',
            static function($m) {
                $level = max(1, min(6, (int)$m[2]));
                $attrs = [];
                if (!empty($m[1])) {
                    $decoded = json_decode($m[1], true);
                    if (is_array($decoded)) $attrs = $decoded;
                }
                if ($level !== 2) $attrs['level'] = $level;
                elseif (isset($attrs['level']) && (int)$attrs['level'] === 2) unset($attrs['level']);
                $json = $attrs ? ' ' . wp_json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
                return '<!-- wp:heading' . $json . ' -->' . "\n" . '<h' . $level . $m[3] . '>';
            },
            $content
        );

        // Repair a malformed legacy demo closer seen in older sector-proof patterns.
        $content = str_replace(
            '<!-- wp:wpbb/row --></div><!-- /wp:group -->',
            '<!-- /wp:wpbb/row --></div><!-- /wp:group -->',
            $content
        );

        // The earliest BBuilder demo exporter wrote this small label as raw HTML
        // directly inside wpbb/column. Turn it into a real Paragraph block so the
        // Column has InnerBlocks only and can validate normally.
        $content = preg_replace_callback(
            '~(?<!wp-theme-partners-heading\"})<p class=["\']wp-theme-partners-heading["\']>(.*?)</p>~s',
            static function($m) {
                return '<!-- wp:paragraph {"className":"wp-theme-partners-heading"} -->' .
                    '<p class="wp-theme-partners-heading">' . $m[1] . '</p><!-- /wp:paragraph -->';
            },
            $content
        );

        // Convert the old raw proof-card wrappers to a neutral Div block. Their
        // headings/paragraphs are normalized by parse_blocks below, while the child
        // demo upgrader can promote these wrappers to Icon Cards where appropriate.
        $content = preg_replace(
            '~<div class=["\']wpbb-sector-proof-card["\']>~',
            '<!-- wp:wpbb/bootstrap-div {"utilityClasses":"wpbb-sector-proof-card"} -->',
            $content
        );
        // Only close a proof-card raw wrapper when it is directly followed by a
        // BBuilder Column closer. This avoids touching unrelated div elements.
        $content = preg_replace(
            '~</div>\s*(<!--\s*/wp:wpbb/column\s*-->)~',
            '<!-- /wp:wpbb/bootstrap-div -->$1',
            $content
        );

        return $content;
    }

    private function normalize_legacy_layout_content($content) {
        if (!is_string($content) || $content === '') return $content;
        $content = $this->repair_serialized_block_markup($content);
        if (!function_exists('parse_blocks') || !function_exists('serialize_blocks')) return $content;

        // Always parse/serialize managed block content. This is intentionally not
        // limited to native Group/Columns: the pass also repairs historical BBuilder
        // Columns whose inner content became detached after a malformed demo import.
        $blocks = parse_blocks($content);
        if (!is_array($blocks)) return $content;
        return serialize_blocks($this->normalize_legacy_layout_block_tree($blocks));
    }

    public function normalize_legacy_layout_blocks_on_save($data, $postarr) {
        if (empty($data['post_content']) || wp_is_post_revision($postarr['ID'] ?? 0)) return $data;
        $data['post_content'] = $this->normalize_legacy_layout_content($data['post_content']);
        return $data;
    }

    public function migrate_legacy_layout_blocks_once() {
        if (!current_user_can('manage_options')) return;
        $version = get_option('wpbb_layout_migration_version', '');
        if ($version === '5.6.6') return;
        $types = get_post_types(['show_ui' => true], 'names');
        $types = array_values(array_unique(array_merge($types, ['wp_template','wp_template_part'])));
        $ids = get_posts(['post_type'=>$types,'post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','suppress_filters'=>true]);
        remove_filter('wp_insert_post_data', [$this, 'normalize_legacy_layout_blocks_on_save'], 30);
        foreach ($ids as $post_id) {
            $content = (string)get_post_field('post_content', $post_id, 'raw');
            $normalized = $this->normalize_legacy_layout_content($content);
            if ($normalized !== $content) wp_update_post(['ID'=>$post_id,'post_content'=>$normalized]);
        }
        add_filter('wp_insert_post_data', [$this, 'normalize_legacy_layout_blocks_on_save'], 30, 2);
        update_option('wpbb_layout_migration_version', '5.6.6', false);
    }

    public function ajax_search() {
        $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
        $limit = isset($_GET['limit']) ? max(1, min(20, intval($_GET['limit']))) : 10;
        $mode = isset($_GET['mode']) ? sanitize_text_field(wp_unslash($_GET['mode'])) : 'title';
        $sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'relevance';
        $items = [];
        if ($term !== '') {
            $requested_types = isset($_GET['types']) ? array_filter(array_map('sanitize_key', explode(',', wp_unslash($_GET['types'])))) : ['post','page','product'];
            $post_types = array_values(array_filter($requested_types, function($type){ $object = get_post_type_object($type); return $object && !empty($object->public); }));
            if (!$post_types) $post_types = ['post','page'];
            $args = ['post_type' => $post_types, 'posts_per_page' => $limit, 's' => $mode === 'title' ? $term : '', 'post_status' => 'publish'];
            if ($sort === 'date') { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
            elseif ($sort === 'title') { $args['orderby'] = 'title'; $args['order'] = 'ASC'; }
            if ($mode === 'id' && ctype_digit($term)) {
                $args['post__in'] = [intval($term)];
            } elseif ($mode === 'sku') {
                $args['meta_query'] = [['key' => '_sku', 'value' => $term, 'compare' => 'LIKE']];
            }
            $q = new WP_Query($args);
            if ($q->have_posts()) {
                while ($q->have_posts()) {
                    $q->the_post();
                    $price = '';
                    if (function_exists('wc_get_product') && get_post_type() === 'product') {
                        $product = wc_get_product(get_the_ID());
                        if ($product) $price = wp_strip_all_tags($product->get_price_html());
                    }
                    $items[] = [
                        'title' => get_the_title(),
                        'url' => get_permalink(),
                        'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: '',
                        'type' => get_post_type(),
                        'excerpt' => wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 14),
                        'price' => $price,
                    ];
                }
                wp_reset_postdata();
            }
        }
        wp_send_json_success(['items' => $items]);
    }

    public function render_weather_block($attributes, $content, $block) {
        wp_enqueue_script('wpbb-chart-view');
        $title = esc_html($attributes['title'] ?? 'Weather');
        $location = esc_attr($attributes['location'] ?? 'London');
        $lang = esc_attr($attributes['lang'] ?? 'en');
        $apiKey = esc_attr($attributes['apiKey'] ?? wpbb_get_option('weather_api_key', ''));
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-weather card', 'data-location' => $location, 'data-lang' => $lang, 'data-api-key' => $apiKey]);
        return "<div {$wrapper}><div class=\"card-body\"><h3 class=\"card-title\">{$title}</h3><div class=\"wpbb-weather-location\">{$location}</div><div class=\"wpbb-weather-temp\">--°C</div><div class=\"wpbb-weather-note\">Loading live weather...</div></div></div>";
    }

    public function render_varda_dienas_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Name Days');
        $today_key = wp_date('m-d');
        $dateTextRaw = trim((string)($attributes['dateText'] ?? ''));
        $dateText = esc_html($dateTextRaw !== '' ? $dateTextRaw : wp_date('j F'));
        $manual_names = trim((string)($attributes['names'] ?? ''));
        $json_names = [];
        $live_names = [];
        $json_raw = (string)($attributes['namesJson'] ?? '');

        if ($json_raw !== '') {
            $decoded = json_decode($json_raw, true);
            if (is_array($decoded) && !empty($decoded[$today_key]) && is_array($decoded[$today_key])) {
                $json_names = array_map('sanitize_text_field', $decoded[$today_key]);
            }
        }

        if (empty($json_names)) {
            $json_file = WPBB_PLUGIN_DIR . 'assets/json/varda-dienas.json';
            if (file_exists($json_file)) {
                $decoded = json_decode((string) file_get_contents($json_file), true);
                if (is_array($decoded) && !empty($decoded[$today_key]) && is_array($decoded[$today_key])) {
                    $json_names = array_map('sanitize_text_field', $decoded[$today_key]);
                }
            }
        }

        $cache_key = 'wpbb_name_days_lv_' . gmdate('Ymd');
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            $live_names = $cached;
        } else {
            $response = wp_remote_get('https://nameday.abalin.net/api/V2/today?country=lv', [
                'timeout' => 10,
                'headers' => ['Accept' => 'application/json'],
            ]);

            if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode((string) wp_remote_retrieve_body($response), true);
                if (!empty($body['data']['namedays']['lv'])) {
                    $raw_names = preg_split('/\s*,\s*/', (string) $body['data']['namedays']['lv']);
                    $live_names = array_values(array_filter(array_map('sanitize_text_field', $raw_names)));
                    if (!empty($live_names)) {
                        set_transient($cache_key, $live_names, DAY_IN_SECONDS);
                    }
                }
            }
        }

        $names_list = !empty($live_names) ? $live_names : (!empty($json_names) ? $json_names : ($manual_names !== '' ? preg_split('/\s*,\s*/', $manual_names) : []));
        $names_list = array_values(array_filter(array_map('sanitize_text_field', (array) $names_list)));
        $names = !empty($names_list) ? implode(', ', $names_list) : 'No Latvian name days found for today.';
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-varda-dienas card']);
        return '<div ' . $wrapper . '><div class="card-body"><h3 class="card-title">' . $title . '</h3><div class="small text-muted">' . $dateText . '</div><div class="wpbb-varda-dienas-names">' . esc_html($names) . '</div></div></div>';
    }

    public function render_ajax_search_block($attributes, $content, $block) {
        wp_enqueue_script('wpbb-ajax-search');
        $title = esc_html($attributes['title'] ?? 'Meklēšana');
        $placeholder = esc_attr($attributes['placeholder'] ?? 'Meklēt...');
        $limit = intval($attributes['resultsLimit'] ?? 10);
        $mode = esc_attr($attributes['searchWooBy'] ?? 'title');
        $sortBy = esc_attr($attributes['sortBy'] ?? 'relevance');
        $showButton = !empty($attributes['showButton']);
        $showExcerpt = !empty($attributes['showExcerpt']);
        $showPrice = !empty($attributes['showPrice']);
        $postTypes = array_values(array_filter(array_map('sanitize_key', (array)($attributes['postTypes'] ?? ['post','page','product']))));
        $postTypes = array_values(array_filter($postTypes, function($type){ $object = get_post_type_object($type); return $object && !empty($object->public); }));
        if (!$postTypes) $postTypes = ['post','page'];
        $searchUrl = esc_url(home_url('/?s='));
        $wrapper = get_block_wrapper_attributes([
            'class' => 'wpbb-ajax-search card',
            'data-limit' => (string)$limit,
            'data-mode' => $mode,
            'data-sort' => $sortBy,
            'data-types' => implode(',', $postTypes),
            'data-show-excerpt' => $showExcerpt ? '1' : '0',
            'data-show-price' => $showPrice ? '1' : '0',
            'data-search-url' => $searchUrl,
            'data-ajax-url' => esc_url_raw(admin_url('admin-ajax.php'))
        ]);
        $button = $showButton ? '<a class="btn btn-outline-secondary wpbb-ajax-search-page-btn" href="#">Atvērt meklēšanas lapu</a>' : '';
        return "<div {$wrapper}><div class=\"card-body\"><h3 class=\"card-title\">{$title}</h3><input type=\"search\" class=\"form-control wpbb-ajax-search-input\" placeholder=\"{$placeholder}\"><div class=\"wpbb-ajax-search-results\"></div>{$button}</div></div>";
    }

    public function render_pricecards_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Pricing');
        $cards = wpbb_parse_fields_json($attributes['cardsJson'] ?? '');
        if (!$cards) $cards = [
            ['title'=>'Basic','price'=>'9','period'=>'/mo','text'=>'Short plan description','button'=>'Choose plan','featured'=>false],
            ['title'=>'Pro','price'=>'29','period'=>'/mo','text'=>'Short plan description','button'=>'Choose plan','featured'=>true]
        ];
        $variant = sanitize_html_class($attributes['styleVariant'] ?? 'default');
        $currency = esc_html($attributes['currency'] ?? '€');
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-pricecards wpbb-pricecards--' . $variant]);
        $html = "<div {$wrapper}><div class=\"row g-3\"><div class=\"col-12\"><h3>{$title}</h3></div>";
        foreach ($cards as $card) {
            $featured = !empty($card['featured']) ? ' wpbb-pricecards__featured' : '';
            $period = !empty($card['period']) ? '<span class="wpbb-pricecards-period">' . esc_html($card['period']) . '</span>' : '';
            $html .= '<div class="col-md-6 col-lg-4"><div class="card h-100' . $featured . '"><div class="card-body"><h4 class="card-title">' . esc_html($card['title'] ?? '') . '</h4><div class="wpbb-pricecards-price">' . $currency . esc_html($card['price'] ?? '') . $period . '</div><div class="card-text">' . esc_html($card['text'] ?? '') . '</div><a href="#" class="btn btn-primary">' . esc_html($card['button'] ?? 'Izvēlēties') . '</a></div></div></div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function render_catalogue_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Katalogs');
        $postsToShow = max(1, min(24, intval($attributes['postsToShow'] ?? 6)));
        $sortBy = sanitize_text_field($attributes['sortBy'] ?? 'date');
        $sortOrder = strtoupper(sanitize_text_field($attributes['sortOrder'] ?? 'DESC'));
        $showImage = !empty($attributes['showImage']);
        $showExcerpt = !empty($attributes['showExcerpt']);
        $postType = sanitize_text_field($attributes['postType'] ?? 'post');
        $taxonomy = sanitize_text_field($attributes['taxonomy'] ?? 'category');
        $args = ['post_type' => $postType, 'posts_per_page' => $postsToShow, 'post_status' => 'publish', 'orderby' => $sortBy, 'order' => $sortOrder];
        if (!empty($attributes['category'])) {
            if ($taxonomy === 'category') { $args['category_name'] = sanitize_text_field($attributes['category']); }
            else { $args['tax_query'] = [['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => sanitize_text_field($attributes['category'])]]; }
        }
        $q = new WP_Query($args);
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-catalogue']);
        $html = "<div {$wrapper}><div class=\"row g-3\"><div class=\"col-12\"><h3>{$title}</h3></div>";
        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                $html .= '<div class="col-md-6 col-lg-4"><div class="card h-100 wpbb-catalogue-card">';
                if ($showImage && $thumb) $html .= '<img class="card-img-top" src="' . esc_url($thumb) . '" alt="">';
                $html .= '<div class="card-body"><h4 class="card-title">' . esc_html(get_the_title()) . '</h4>';
                if ($showExcerpt) $html .= '<div class="card-text">' . esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 20)) . '</div>';
                $html .= '<a class="btn btn-primary" href="' . esc_url(get_permalink()) . '">Open card</a></div></div></div>';
            }
            wp_reset_postdata();
        }
        $html .= '</div></div>';
        return $html;
    }

    public function render_code_display_block($attributes, $content, $block) {
        wp_enqueue_script('wpbb-copy-code');
        $title = esc_html($attributes['title'] ?? 'Code');
        $code = esc_html($attributes['code'] ?? '');
        $lang = esc_attr($attributes['language'] ?? 'html');
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-code-display']);
        return "<div {$wrapper}><div class=\"wpbb-code-display__head\"><strong>{$title}</strong><button type=\"button\" class=\"button wpbb-copy-code-btn\" aria-label=\"Copy code\">⧉</button></div><pre class=\"wpbb-code-display__pre\"><code class=\"language-{$lang}\">{$code}</code></pre></div>";
    }

    public function render_countdown_timer_block($attributes, $content, $block) {
        wp_enqueue_script('wpbb-chart-view');
        $title = esc_html($attributes['title'] ?? 'Countdown');
        $target = esc_attr($attributes['targetDate'] ?? '2030-01-01T00:00:00');
        $variant = sanitize_html_class($attributes['styleVariant'] ?? 'default');
        $accent = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['accentColor'] ?? '#2563eb'));
        $bg = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['backgroundColor'] ?? ''));
        $text = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['textColor'] ?? ''));
        $shadow = sanitize_html_class($attributes['boxShadowClass'] ?? 'shadow-sm');
        $style = '';
        if ($bg) $style .= 'background:' . $bg . ';';
        if ($text) $style .= 'color:' . $text . ';';
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-countdown-timer wpbb-countdown-timer--' . $variant . ' card ' . $shadow, 'data-target-date' => $target, 'style' => $style]);
        $labels = [
            esc_html($attributes['labelDays'] ?? 'Days'),
            esc_html($attributes['labelHours'] ?? 'Hours'),
            esc_html($attributes['labelMinutes'] ?? 'Minutes'),
            esc_html($attributes['labelSeconds'] ?? 'Seconds')
        ];
        $segments = '';
        foreach ($labels as $lab) {
            $segments .= '<div class="wpbb-countdown-timer__segment" style="border-color:' . esc_attr($accent) . '"><strong>00</strong><span>' . $lab . '</span></div>';
        }
        return "<div {$wrapper}><div class=\"card-body\"><h3 class=\"card-title\">{$title}</h3><div class=\"wpbb-countdown-timer__value\">{$segments}</div></div></div>";
    }

    public function render_chart_block($attributes, $content, $block) {
        wp_enqueue_script('wpbb-chart-view');
        $title = esc_html($attributes['title'] ?? 'Chart');
        $type = esc_attr($attributes['chartType'] ?? 'bar');
        $json = esc_attr($attributes['chartDataJson'] ?? '');
        $opts = esc_attr($attributes['chartOptionsJson'] ?? '');
        $height = preg_replace('/[^0-9.%a-zA-Z-]/', '', (string)($attributes['height'] ?? '320px'));
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-chart card', 'data-chart-type' => $type, 'data-chart-json' => $json, 'data-chart-options' => $opts]);
        return "<div {$wrapper}><div class=\"card-body\"><h3 class=\"card-title\">{$title}</h3><div class=\"wpbb-chart__canvas\" style=\"min-height:{$height}\">Chart preview</div></div></div>";
    }

    public function render_fun_fact_block($attributes, $content, $block) {
        $number = esc_html($attributes['number'] ?? '100+');
        $label = esc_html($attributes['label'] ?? 'Projects');
        $icon = esc_html($attributes['icon'] ?? '⭐');
        $variant = sanitize_html_class($attributes['styleVariant'] ?? 'default');
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-fun-fact wpbb-fun-fact--' . $variant . ' card']);
        return "<div {$wrapper}><div class=\"card-body text-center\"><div class=\"wpbb-fun-fact__icon\">{$icon}</div><div class=\"wpbb-fun-fact__number\">{$number}</div><div class=\"wpbb-fun-fact__label\">{$label}</div></div></div>";
    }

    public function render_mailchimp_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Subscribe');
        $text = esc_html($attributes['text'] ?? 'Join our newsletter');
        $action = esc_url($attributes['actionUrl'] ?? '');
        $fieldName = esc_attr($attributes['audienceFieldName'] ?? 'EMAIL');
        $showName = !empty($attributes['showNameField']);
        $buttonText = esc_html($attributes['buttonText'] ?? 'Subscribe');
        $variant = sanitize_html_class($attributes['styleVariant'] ?? 'soft');
        $useHcaptcha = !empty($attributes['useHcaptcha']);
        $btnBg = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['submitBg'] ?? '#2563eb'));
        $btnColor = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['submitColor'] ?? '#ffffff'));
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-mailchimp card wpbb-mailchimp--' . $variant]);
        $nameField = $showName ? '<div class="col-12"><input type="text" class="form-control" name="FNAME" placeholder="Name"></div>' : '';
        $hcaptcha = $useHcaptcha ? '<div class="wpbb-captcha-note">hCaptcha enabled. Add site key/secret in plugin settings for production.</div>' : '';
        return "<div {$wrapper}><div class=\"card-body\"><h3 class=\"card-title\">{$title}</h3><p>{$text}</p><form class=\"wpbb-mailchimp-form\" method=\"post\" action=\"{$action}\" target=\"_blank\"><div class=\"row g-2\">{$nameField}<div class=\"col-12\"><div class=\"input-group\"><input type=\"email\" class=\"form-control\" name=\"{$fieldName}\" placeholder=\"Email\"><button class=\"btn btn-primary\" type=\"submit\" style=\"background:{$btnBg};border-color:{$btnBg};color:{$btnColor}\">{$buttonText}</button></div></div></div>{$hcaptcha}</form></div></div>";
    }


    private function wpbb_icon_card_contrast_text($background) {
        $hex = trim((string)$background);
        if (!preg_match('/^#([0-9a-fA-F]{6})$/', $hex, $m)) return '';
        $raw = $m[1];
        $r = hexdec(substr($raw, 0, 2));
        $g = hexdec(substr($raw, 2, 2));
        $b = hexdec(substr($raw, 4, 2));
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
        return $luminance < 0.48 ? '#ffffff' : '#111827';
    }

    public function render_icon_card_block($attributes, $content, $block) {
        $media_type = in_array(($attributes['mediaType'] ?? 'image'), ['image','svg','none'], true) ? $attributes['mediaType'] : 'image';
        $media = '';
        if ($media_type === 'svg' && !empty($attributes['svgCode'])) {
            $svg = wp_kses((string)$attributes['svgCode'], [
                'svg' => ['xmlns'=>true,'viewBox'=>true,'width'=>true,'height'=>true,'fill'=>true,'stroke'=>true,'class'=>true,'role'=>true,'aria-hidden'=>true,'focusable'=>true],
                'g' => ['fill'=>true,'stroke'=>true,'stroke-width'=>true,'transform'=>true,'class'=>true],
                'path' => ['d'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'transform'=>true,'class'=>true],
                'circle' => ['cx'=>true,'cy'=>true,'r'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true],
                'rect' => ['x'=>true,'y'=>true,'rx'=>true,'ry'=>true,'width'=>true,'height'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true],
                'polygon' => ['points'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true],
                'polyline' => ['points'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true],
                'line' => ['x1'=>true,'y1'=>true,'x2'=>true,'y2'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true],
                'ellipse' => ['cx'=>true,'cy'=>true,'rx'=>true,'ry'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true],
                'title' => [], 'desc' => []
            ]);
            if ($svg !== '') $media = '<div class="wpbb-icon-card__media wpbb-icon-card__media--svg">' . $svg . '</div>';
        } elseif ($media_type === 'image') {
            $image_id = absint($attributes['imageId'] ?? 0);
            $image_url = esc_url($attributes['imageUrl'] ?? '');
            $alt = esc_attr($attributes['imageAlt'] ?? '');
            if ($image_id) {
                $img = wp_get_attachment_image($image_id, 'medium', false, ['class'=>'wpbb-icon-card__image','alt'=>$alt]);
                if ($img) $media = '<div class="wpbb-icon-card__media">' . $img . '</div>';
            } elseif ($image_url !== '') {
                $media = '<div class="wpbb-icon-card__media"><img class="wpbb-icon-card__image" src="' . $image_url . '" alt="' . $alt . '"></div>';
            }
        }
        $title = esc_html($attributes['title'] ?? '');
        $text = wp_kses_post($attributes['text'] ?? '');
        $link_text = esc_html($attributes['linkText'] ?? '');
        $link_url = esc_url($attributes['linkUrl'] ?? '');
        $target = !empty($attributes['linkNewTab']) ? ' target="_blank" rel="noopener"' : '';
        $link = ($link_text !== '' && $link_url !== '') ? '<a class="wpbb-icon-card__link" href="' . $link_url . '"' . $target . '>' . $link_text . '</a>' : '';
        $content_align = in_array(($attributes['contentAlign'] ?? 'left'), ['left','center','right'], true) ? $attributes['contentAlign'] : 'left';
        $classes = trim('wpbb-icon-card wpbb-icon-card--align-' . $content_align . ' card h-100 ' . sanitize_html_class($attributes['imagePosition'] ?? 'top') . ' ' . trim((string)($attributes['boxShadowClass'] ?? 'shadow-sm')) . ' ' . trim((string)($attributes['className'] ?? '')));
        $style = '';
        if (!empty($attributes['backgroundColor'])) $style .= 'background:' . esc_attr($attributes['backgroundColor']) . ';';
        $resolved_text = !empty($attributes['autoTextColor']) || !array_key_exists('autoTextColor', $attributes)
            ? $this->wpbb_icon_card_contrast_text($attributes['backgroundColor'] ?? '')
            : sanitize_hex_color($attributes['textColor'] ?? '');
        if ($resolved_text) {
            $style .= 'color:' . esc_attr($resolved_text) . ';';
            if ($resolved_text === '#ffffff') $style .= '--wpbb-icon-card-muted:rgba(255,255,255,.78);';
        }
        if (!empty($attributes['borderColor'])) $style .= 'border-color:' . esc_attr($attributes['borderColor']) . ';';
        if (!empty($attributes['iconSize'])) $style .= '--wpbb-icon-card-media-size:' . esc_attr($attributes['iconSize']) . ';';
        $wrapper = get_block_wrapper_attributes(['class'=>$classes,'style'=>$style]);
        return '<article ' . $wrapper . '>' . $media . '<div class="card-body d-flex flex-column"><h3 class="wpbb-icon-card__title card-title">' . $title . '</h3><div class="wpbb-icon-card__text card-text">' . $text . '</div>' . ($link ? '<div class="wpbb-icon-card__action mt-auto pt-3">' . $link . '</div>' : '') . '</div></article>';
    }

    public function render_bootstrap_div_block($attributes, $content, $block) {
        $allowed_tags = ['div','main','section','aside','nav','header','footer'];
        $tag = sanitize_key((string)($attributes['tagName'] ?? 'div'));
        if (!in_array($tag, $allowed_tags, true)) $tag = 'div';

        $container_class = trim((string)($attributes['containerClass'] ?? 'container'));
        $allowed_containers = ['','container','container-fluid','container-sm','container-md','container-lg','container-xl','container-xxl'];
        if (!in_array($container_class, $allowed_containers, true)) $container_class = 'container';

        $classes = trim('wpbb-bootstrap-div ' . $container_class . ' ' . trim((string)($attributes['utilityClasses'] ?? '')) . ' ' . trim((string)($attributes['className'] ?? '')));
        $style = $this->wpbb_build_background_inline($attributes, false, true);
        foreach ([
            'maxWidth' => 'max-width',
            'maxHeight' => 'max-height',
            'minHeight' => 'min-height',
            'textColor' => 'color',
            'borderRadius' => 'border-radius',
            'padding' => 'padding',
            'margin' => 'margin'
        ] as $key => $css) {
            if (!empty($attributes[$key])) {
                $style .= $css . ':' . preg_replace('/[^#(),.% 0-9a-zA-Z\-]/', '', (string)$attributes[$key]) . ';';
            }
        }
        $wrapper = get_block_wrapper_attributes(['class' => $classes, 'style' => $style]);
        return "<{$tag} {$wrapper}>{$content}</{$tag}>";
    }

    private function wpbb_compile_preview_css($selector, $scss) {
        $scss = trim((string)$scss);
        if ($scss === '') return '';
        return trim(preg_replace('/\s+/', ' ', $this->wpbb_compile_scoped_scss($selector, $scss)));
    }

    private function wpbb_collect_spacing_classes($attributes) {
        $classes = [];
        foreach (['spacingSm','spacingMd','spacingLg','spacingXl','spacingXxl','paddingSm','paddingMd','paddingLg','paddingXl','paddingXxl','marginSm','marginMd','marginLg','marginXl','marginXxl'] as $k) {
            if (empty($attributes[$k])) continue;
            $classes = array_merge($classes, $this->wpbb_class_tokens_from_value($attributes[$k]));
        }
        return array_values(array_unique($classes));
    }


    private function wpbb_capture_style_tag($css) {
        $css = trim((string) $css);
        if ($css === '') return '';

        // Block render callbacks run while post_content is printed, usually after wp_head.
        // Returning the scoped style with the block guarantees row/column responsive
        // margin/padding works on the front end and in previews.
        return '<style>' . $css . '</style>';
    }


    public function render_generic_block($attributes, $content, $block) {
        $name = '';
        if (is_object($block) && !empty($block->name)) {
            $name = (string) $block->name;
        } elseif (is_array($block) && !empty($block['blockName'])) {
            $name = (string) $block['blockName'];
        }
        $slug = preg_replace('~^wpbb/~', '', $name);
        $extra = !empty($attributes['className']) ? ' ' . sanitize_html_class($attributes['className']) : '';

        switch ($slug) {
            case 'google-map':
                $address = sanitize_text_field($attributes['address'] ?? '');
                $legacy_embed = trim((string) ($attributes['embedUrl'] ?? ''));
                $height = trim((string) ($attributes['height'] ?? '380px'));
                if ($height === '') $height = '380px';
                if (preg_match('/^\d+$/', $height)) $height .= 'px';
                $height_attr = preg_replace('/[^0-9.]/', '', $height);
                if ($height_attr === '') $height_attr = '380';
                $zoom = max(1, min(21, intval($attributes['zoom'] ?? 14)));
                $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-google-map' . $extra]);

                $src = '';
                if ($address !== '') {
                    $src = 'https://www.google.com/maps?output=embed&q=' . rawurlencode($address) . '&z=' . $zoom;
                } elseif ($legacy_embed !== '') {
                    if (preg_match('~src=["\']([^"\']+)["\']~i', $legacy_embed, $m)) {
                        $src = $m[1];
                    } else {
                        $src = $legacy_embed;
                    }
                    $src = html_entity_decode($src, ENT_QUOTES, 'UTF-8');
                    $src = preg_replace('~^http://~i', 'https://', $src);
                    if (strpos($src, 'output=embed') === false) {
                        $src .= (strpos($src, '?') !== false ? '&' : '?') . 'output=embed';
                    }
                }

                if ($src === '') {
                    return '<div ' . $wrapper . '><div class="wpbb-empty-note">' . esc_html__('Add address', 'wp-bbuilder') . '</div></div>';
                }

                $overlay_color = trim((string) ($attributes['overlayColor'] ?? ''));
                $overlay_opacity = isset($attributes['overlayOpacity']) ? max(0, min(1, floatval($attributes['overlayOpacity']))) : 0;
                if ($overlay_color === '' && !empty($attributes['mapFilter']) && preg_match('/^(#|rgb|rgba|hsl|hsla)/i', trim((string) $attributes['mapFilter']))) {
                    $overlay_color = trim((string) $attributes['mapFilter']);
                    if ($overlay_opacity <= 0) $overlay_opacity = 0.2;
                }

                $html = '<div ' . $wrapper . '>';
                $html .= '<div class="wpbb-google-map__frame" style="position:relative;width:100%;min-height:' . esc_attr($height) . ';overflow:hidden;background:#f8fafc;">';
                $html .= '<iframe class="wpbb-google-map__iframe" src="' . esc_url($src) . '" title="' . esc_attr($address !== '' ? $address : __('Google map', 'wp-bbuilder')) . '" width="100%" height="' . esc_attr($height_attr) . '" style="border:0;width:100%;height:' . esc_attr($height) . ';min-height:' . esc_attr($height) . ';display:block;visibility:visible;opacity:1;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
                if ($overlay_color !== '' && $overlay_opacity > 0) {
                    $html .= '<span class="wpbb-google-map__overlay" aria-hidden="true" style="position:absolute;inset:0;pointer-events:none;background:' . esc_attr($overlay_color) . ';opacity:' . esc_attr((string) $overlay_opacity) . ';"></span>';
                }
                if ($address !== '') {
                    $html .= '<div class="wpbb-google-map__fallback" style="padding-top:8px;"><a href="' . esc_url('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address)) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open map in Google Maps', 'wp-bbuilder') . '</a></div>';
                }
                $html .= '</div></div>';
                return $html;

            case 'file':
                $file_url = esc_url($attributes['fileUrl'] ?? '');
                $file_name = trim((string) ($attributes['fileName'] ?? ''));
                $button_text = trim((string) ($attributes['buttonText'] ?? 'Download file'));
                $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-file-block' . $extra]);
                if ($file_url === '') return '<div ' . $wrapper . '><div class="wpbb-empty-note">' . esc_html__('Add file URL', 'wp-bbuilder') . '</div></div>';
                if ($file_name === '') $file_name = basename((string) wp_parse_url($file_url, PHP_URL_PATH));
                $target = !empty($attributes['targetBlank']) ? ' target="_blank" rel="noopener"' : '';
                return '<div ' . $wrapper . '><div class="wpbb-file-block__name">' . esc_html($file_name) . '</div><a class="wpbb-file-block__link btn btn-primary" href="' . esc_url($file_url) . '"' . $target . '>' . esc_html($button_text !== '' ? $button_text : 'Download file') . '</a></div>';

            case 'inline-svg':
                $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-inline-svg' . $extra]);
                $svg_code = trim((string) ($attributes['svgCode'] ?? ''));
                if ($svg_code === '') return '<div ' . $wrapper . '><div class="wpbb-empty-note">' . esc_html__('Paste SVG code', 'wp-bbuilder') . '</div></div>';
                $svg_code = wp_kses($svg_code, [
                    'svg' => ['xmlns'=>true,'viewBox'=>true,'width'=>true,'height'=>true,'fill'=>true,'stroke'=>true,'class'=>true,'role'=>true,'aria-hidden'=>true,'focusable'=>true,'style'=>true],
                    'g' => ['fill'=>true,'stroke'=>true,'stroke-width'=>true,'transform'=>true,'class'=>true,'style'=>true],
                    'path' => ['d'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'transform'=>true,'class'=>true,'style'=>true],
                    'circle' => ['cx'=>true,'cy'=>true,'r'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true,'style'=>true],
                    'rect' => ['x'=>true,'y'=>true,'rx'=>true,'ry'=>true,'width'=>true,'height'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true,'style'=>true],
                    'polygon' => ['points'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true,'style'=>true],
                    'polyline' => ['points'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true,'style'=>true],
                    'line' => ['x1'=>true,'y1'=>true,'x2'=>true,'y2'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true,'style'=>true],
                    'ellipse' => ['cx'=>true,'cy'=>true,'rx'=>true,'ry'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true,'class'=>true,'style'=>true],
                    'defs' => [], 'clipPath' => ['id'=>true], 'mask' => ['id'=>true], 'title' => [], 'desc' => [],
                    'linearGradient' => ['id'=>true,'x1'=>true,'x2'=>true,'y1'=>true,'y2'=>true], 'radialGradient' => ['id'=>true,'cx'=>true,'cy'=>true,'r'=>true],
                    'stop' => ['offset'=>true,'stop-color'=>true,'stop-opacity'=>true]
                ]);
                return '<div ' . $wrapper . '>' . $svg_code . '</div>';

            default:
                $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-generic-block wpbb-' . sanitize_html_class($slug) . $extra]);
                return '<div ' . $wrapper . '>' . $content . '</div>';
        }
    }

    public function render_row_block($attributes, $content, $block) {
        $row_classes = ['row', 'wpbb-row'];
        foreach (['gutterX','gutterY','paddingClass','marginClass','backgroundClass','animationClass','displayClass','textUtilityClass','roundedClass','shadowClass','bootstrapClasses','utilityClasses','customClasses','visibilityClass','className'] as $k) {
            if (empty($attributes[$k])) continue;
            $row_classes = array_merge($row_classes, $this->wpbb_class_tokens_from_value($attributes[$k]));
        }
        $row_classes = array_merge($row_classes, $this->wpbb_collect_spacing_classes($attributes));
        $responsive_spacing_classes = [];
        $responsive_spacing_style = $this->wpbb_build_responsive_spacing_inline($attributes, $responsive_spacing_classes);
        $row_classes = array_merge($row_classes, $responsive_spacing_classes);
        $responsive_background_classes = [];
        $responsive_background_style = $this->wpbb_build_responsive_background_position_inline($attributes, $responsive_background_classes);
        $row_classes = array_merge($row_classes, $responsive_background_classes);
        if (!empty($attributes['align'])) $row_classes[] = 'justify-content-' . sanitize_html_class((string)$attributes['align']);
        $uid = !empty($attributes['uniqueId']) ? sanitize_html_class((string)$attributes['uniqueId']) : sanitize_html_class('wpbb-row-' . wp_unique_id());
        $row_background_style = $this->wpbb_build_background_inline($attributes, false, false);
        $row_style = $row_background_style . $responsive_background_style . $responsive_spacing_style;
        if (!empty($attributes['textColor'])) $row_style .= 'color:' . preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)$attributes['textColor']) . ';';
        if (!empty($attributes['customStyle'])) $row_style .= (string)$attributes['customStyle'];

        $scssTag = !empty($attributes['customScss']) ? $this->wpbb_capture_style_tag($this->wpbb_compile_scoped_scss('#' . $uid, (string)$attributes['customScss'])) : '';

        // If a row contains several BBuilder Columns and none of them has an
        // explicit responsive width, distribute them evenly from MD upwards.
        // This keeps recovered/legacy layouts useful instead of stacking every
        // default col-12 at full width. Explicit column widths always win.
        $auto_columns = !array_key_exists('autoColumns', $attributes) || !empty($attributes['autoColumns']);
        $auto_count = 0;
        $all_unsized = true;
        if ($auto_columns && is_object($block) && !empty($block->parsed_block['innerBlocks']) && is_array($block->parsed_block['innerBlocks'])) {
            foreach ($block->parsed_block['innerBlocks'] as $child) {
                if (!is_array($child) || ($child['blockName'] ?? '') !== 'wpbb/column') continue;
                $auto_count++;
                $child_attrs = is_array($child['attrs'] ?? null) ? $child['attrs'] : [];
                foreach (['sm','md','lg','xl','xxl'] as $bp) {
                    if (!empty($child_attrs[$bp])) { $all_unsized = false; break 2; }
                }
                // xs=12 is the normal mobile default and does not disable auto-grid.
                if (!empty($child_attrs['xs']) && (int)$child_attrs['xs'] !== 12) { $all_unsized = false; break; }
                foreach (['bootstrapClasses','utilityClasses','customClasses','className'] as $class_key) {
                    $class_value = trim((string)($child_attrs[$class_key] ?? ''));
                    if ($class_value !== '' && preg_match('/(?:^|\s)col(?:-(?:sm|md|lg|xl|xxl))?(?:-(?:auto|[1-9]|1[0-2]))?(?:\s|$)/', $class_value)) {
                        $all_unsized = false;
                        break 2;
                    }
                }
            }
        }
        if ($all_unsized && $auto_count >= 2 && $auto_count <= 6) {
            $row_classes[] = 'wpbb-row--auto-grid';
            $row_classes[] = 'wpbb-row--auto-' . $auto_count;
        }

        $row_wrapper = get_block_wrapper_attributes([
            'class' => implode(' ', array_values(array_unique(array_filter($row_classes)))),
            'style' => $row_style,
            'id' => $uid
        ]);

        $processed_content = $content;
        if (is_string($processed_content) && $processed_content !== '') {
            $processed_content = do_blocks($processed_content);
            $processed_content = do_shortcode($processed_content);
        }
        $row_html = '<div ' . $row_wrapper . '>' . $processed_content . '</div>';

        if (!empty($attributes['containerClass'])) {
            $container_tokens = array_values(array_unique(array_filter($this->wpbb_class_tokens_from_value($attributes['containerClass']))));
            $container_class = implode(' ', $container_tokens);
            if ($container_class !== '') {
                $container_inline_style = '';
                if ($container_class === 'container-fluid') {
                    $container_inline_style .= 'max-width:none;width:100%;';
                }
                $container_style = $container_inline_style !== '' ? ' style="' . esc_attr($container_inline_style) . '"' : '';
                $row_html = '<div class="' . esc_attr($container_class) . '"' . $container_style . '>' . $row_html . '</div>';
            }
        }

        if (!empty($attributes['overlayColor']) && !empty($attributes['overlayOpacity'])) {
            $opacity = floatval($attributes['overlayOpacity']);
            $row_html = '<div style="position:relative;overflow:hidden;">'
                . '<div class="wpbb-block-overlay" style="position:absolute;inset:0;pointer-events:none;background:' . esc_attr((string)$attributes['overlayColor']) . ';opacity:' . $opacity . ';"></div>'
                . '<div class="wpbb-block-content" style="position:relative;z-index:1">' . $row_html . '</div>'
                . '</div>';
        }

        return $scssTag . $row_html;
    }

    public function render_column_block($attributes, $content, $block) {
        $classes = ['wpbb-column'];
        $bpMap = ['xs'=>'col','sm'=>'col-sm','md'=>'col-md','lg'=>'col-lg','xl'=>'col-xl','xxl'=>'col-xxl'];
        foreach ($bpMap as $bp => $prefix) {
            $val = isset($attributes[$bp]) ? intval($attributes[$bp]) : 0;
            if ($bp === 'xs' && $val <= 0) $val = 12;
            if ($val > 0) $classes[] = $prefix . '-' . $val;
        }
        // Add responsive order classes (order-sm-1, order-md-2, etc.)
        $orderBpMap = ['orderSm'=>'order-sm','orderMd'=>'order-md','orderLg'=>'order-lg','orderXl'=>'order-xl','orderXxl'=>'order-xxl'];
        foreach ($orderBpMap as $attr => $prefix) {
            $val = isset($attributes[$attr]) ? intval($attributes[$attr]) : 0;
            if ($val > 0 && $val <= 5) $classes[] = $prefix . '-' . $val;
        }
        foreach (['orderClass','verticalAlign','horizontalAlign','visibilityClass','animationClass','paddingClass','marginClass','backgroundClass','displayClass','textUtilityClass','roundedClass','shadowClass','boxShadowClass','bootstrapClasses','utilityClasses','customClasses','className'] as $k) {
            if (empty($attributes[$k])) continue;
            $classes = array_merge($classes, $this->wpbb_class_tokens_from_value($attributes[$k]));
        }
        $classes = array_merge($classes, $this->wpbb_collect_spacing_classes($attributes));
        $responsive_spacing_classes = [];
        $responsive_spacing_style = $this->wpbb_build_responsive_spacing_inline($attributes, $responsive_spacing_classes);
        $classes = array_merge($classes, $responsive_spacing_classes);
        $responsive_background_classes = [];
        $responsive_background_style = $this->wpbb_build_responsive_background_position_inline($attributes, $responsive_background_classes);
        $classes = array_merge($classes, $responsive_background_classes);
        if (!empty($attributes['verticalAlign']) || !empty($attributes['horizontalAlign'])) { $classes[] = 'd-flex'; $classes[] = 'flex-column'; }
        $uid = !empty($attributes['uniqueId']) ? sanitize_html_class((string)$attributes['uniqueId']) : sanitize_html_class('wpbb-col-' . wp_unique_id());
        $style = $this->wpbb_build_background_inline($attributes, false, false) . $responsive_background_style . $responsive_spacing_style;
        if (!empty($attributes['textColor'])) $style .= 'color:' . preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)$attributes['textColor']) . ';';
        if (!empty($attributes['borderRadius'])) $style .= 'border-radius:' . preg_replace('/[^0-9.%a-zA-Z-]/', '', (string)$attributes['borderRadius']) . ';';
        if (!empty($attributes['customStyle'])) $style .= (string)$attributes['customStyle'];
        $scssTag = !empty($attributes['customScss']) ? $this->wpbb_capture_style_tag($this->wpbb_compile_scoped_scss('#' . $uid, (string)$attributes['customScss'])) : '';
        $wrapper = get_block_wrapper_attributes(['class' => implode(' ', array_values(array_unique(array_filter($classes)))), 'style' => $style, 'id' => $uid]);
        $inner = $content;
        if (is_string($inner) && $inner !== '') {
            $inner = do_blocks($inner);
            $inner = do_shortcode($inner);
        }
        if (!empty($attributes['containerClass'])) {
            $container_class = implode(' ', $this->wpbb_class_tokens_from_value($attributes['containerClass']));
            $inner = '<div class="' . esc_attr($container_class) . '">' . $content . '</div>';
        }
        $overlay = '';
        if (!empty($attributes['overlayColor']) && !empty($attributes['overlayOpacity'])) {
            $overlay = '<div class="wpbb-block-overlay" style="position:absolute;inset:0;pointer-events:none;background:' . esc_attr((string)$attributes['overlayColor']) . ';opacity:' . floatval($attributes['overlayOpacity']) . ';"></div>';
            $inner = '<div class="wpbb-block-content" style="position:relative;z-index:1">' . $inner . '</div>';
            $style .= 'position:relative;overflow:hidden;';
            $wrapper = get_block_wrapper_attributes(['class' => implode(' ', array_values(array_unique(array_filter($classes)))), 'style' => $style, 'id' => $uid]);
        }
        return $scssTag . '<div ' . $wrapper . '>' . $overlay . $inner . '</div>';
    }

    public function render_social_follow_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? __('Follow Us', 'wp-bbuilder'));
        $titleTag = in_array(($attributes['titleTag'] ?? 'span'), ['h1','h2','h3','h4','h5','h6','div','p','span'], true) ? ($attributes['titleTag'] ?: 'span') : 'span';
        $style = $attributes['socialStyle'] ?? 'icons';
        $sizeMap = ['sm' => '34px', 'md' => '42px', 'lg' => '50px'];
        $iconSize = $sizeMap[$attributes['iconSize'] ?? 'md'] ?? '42px';
        $shape = $attributes['iconShape'] ?? 'rounded';
        $shapeRadius = $shape === 'circle' ? '999px' : ($shape === 'square' ? '0' : '12px');
        $showLabels = !empty($attributes['showLabels']);
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-soc-follow wpbb-soc-style-' . sanitize_html_class($style)]);
        $items = [
            'facebook' => esc_url($attributes['facebook'] ?? ''),
            'instagram' => esc_url($attributes['instagram'] ?? ''),
            'linkedin' => esc_url($attributes['linkedin'] ?? ''),
            'x' => esc_url($attributes['x'] ?? ''),
            'youtube' => esc_url($attributes['youtube'] ?? ''),
            'tiktok' => esc_url($attributes['tiktok'] ?? ''),
            'pinterest' => esc_url($attributes['pinterest'] ?? ''),
            'whatsapp' => esc_url($attributes['whatsapp'] ?? ''),
            'email' => !empty($attributes['email']) ? 'mailto:' . antispambot(sanitize_email((string)$attributes['email'])) : '',
        ];
        $labels = ['facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','x'=>'X','youtube'=>'YouTube','tiktok'=>'TikTok','pinterest'=>'Pinterest','whatsapp'=>'WhatsApp','email'=>'Email'];
        $links = '';
        foreach ($items as $key => $url) {
            if (!$url) continue;
            $bg = !empty($attributes['iconBgColor']) ? (string)$attributes['iconBgColor'] : '#0f172a';
            $fg = !empty($attributes['iconTextColor']) ? (string)$attributes['iconTextColor'] : '#ffffff';
            $icon = $this->wpbb_svg_icon($key);
            if ($style === 'buttons') {
                $links .= '<a href="' . esc_url($url) . '" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" target="_blank" rel="noopener noreferrer"><span class="wpbb-social-icon wpbb-social-icon--inline" style="width:26px;height:26px;border-radius:' . esc_attr($shapeRadius) . ';background:' . esc_attr($bg) . ';color:' . esc_attr($fg) . ';">' . $icon . '</span>' . esc_html($labels[$key]) . '</a>';
            } else {
                $links .= '<a href="' . esc_url($url) . '" class="wpbb-social-icon" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($labels[$key]) . '" style="width:' . esc_attr($iconSize) . ';height:' . esc_attr($iconSize) . ';border-radius:' . esc_attr($shapeRadius) . ';background:' . esc_attr($bg) . ';color:' . esc_attr($fg) . ';">' . $icon . '</a>';
                if ($showLabels) $links .= '<span class="wpbb-social-label">' . esc_html($labels[$key]) . '</span>';
            }
        }
        if ($links === '') return '';
        return '<div ' . $wrapper . '>' . ($title ? '<' . $titleTag . ' class="wpbb-soc-title">' . $title . '</' . $titleTag . '>' : '') . '<div class="wpbb-soc-links">' . $links . '</div></div>';
    }




    private function wpbb_soc_feed_connections() {
        $connections = get_option('wpbb_soc_feed_connections', []);
        return is_array($connections) ? $connections : [];
    }

    private function wpbb_soc_feed_connection_value($platform, $key) {
        $connections = $this->wpbb_soc_feed_connections();
        $value = $connections[$platform][$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    private function wpbb_soc_feed_value($attributes, $platform, $key) {
        $value = isset($attributes[$key]) && is_string($attributes[$key]) ? trim($attributes[$key]) : '';
        if ($value === '' && (!array_key_exists('useSavedConnection', $attributes) || !empty($attributes['useSavedConnection']))) {
            $value = $this->wpbb_soc_feed_connection_value($platform, $key);
        }
        return $value;
    }

    private function wpbb_soc_feed_legacy_instagram_token() {
        $accounts = get_option('insta_gallery_accounts', []);
        if (!is_array($accounts)) return '';
        foreach ($accounts as $account) {
            if (is_array($account) && !empty($account['access_token'])) return (string) $account['access_token'];
        }
        return '';
    }

    private function wpbb_soc_feed_legacy_tiktok_value($key) {
        $accounts = get_option('tiktok_feed_accounts', []);
        if (!is_array($accounts)) return '';
        foreach ($accounts as $account) {
            if (!is_array($account)) continue;
            $map = [
                'accessToken' => 'access_token',
                'refreshToken' => 'refresh_token',
                'openId' => 'open_id',
                'clientKey' => 'client_key',
                'accessTokenExpires' => 'access_token_expiration_date',
                'refreshTokenExpires' => 'refresh_token_expiration_date',
            ];
            $legacy_key = $map[$key] ?? $key;
            if (!empty($account[$legacy_key])) return (string) $account[$legacy_key];
        }
        return '';
    }

    private function wpbb_soc_feed_legacy_tiktok_account() {
        $accounts = get_option('tiktok_feed_accounts', []);
        if (!is_array($accounts)) return [];
        foreach ($accounts as $account) {
            if (is_array($account) && (!empty($account['open_id']) || !empty($account['access_token']) || !empty($account['refresh_token']))) {
                return $account;
            }
        }
        return [];
    }

    private function wpbb_soc_feed_tiktok_client_key_from_cache() {
        global $wpdb;
        if (!isset($wpdb) || empty($wpdb->options)) return '';
        $like = '%' . $wpdb->esc_like('qlttf_cache_feed') . '%';
        $timeout = '%' . $wpdb->esc_like('_timeout_') . '%';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s ORDER BY option_id DESC LIMIT 20", $like, $timeout), ARRAY_A);
        foreach ((array) $rows as $row) {
            $data = isset($row['option_value']) ? maybe_unserialize($row['option_value']) : null;
            if (!is_array($data)) continue;
            $found = $this->wpbb_soc_feed_array_deep_value($data, [
                ['response', 0, 'share_url'], ['response', 0, 'url'], ['data', 0, 'share_url'], ['items', 0, 'share_url'], ['videos', 0, 'share_url'],
            ]);
            if ($found !== '' && preg_match('/[?&]utm_source=([^&#]+)/', $found, $m)) {
                return sanitize_text_field(rawurldecode($m[1]));
            }
        }
        return '';
    }

    private function wpbb_soc_feed_tiktok_access_token_is_expired($skew = 300) {
        $expires = absint($this->wpbb_soc_feed_legacy_tiktok_value('accessTokenExpires'));
        if (!$expires) return false;
        return $expires <= (time() + absint($skew));
    }

    private function wpbb_soc_feed_tiktok_persist_tokens($body, $client_key = '') {
        if (!is_array($body)) return;
        $access_token = (string) ($body['access_token'] ?? ($body['data']['access_token'] ?? ''));
        $refresh_token = (string) ($body['refresh_token'] ?? ($body['data']['refresh_token'] ?? ''));
        $access_expires = absint($body['access_token_expires_in'] ?? ($body['expires_in'] ?? ($body['data']['access_token_expires_in'] ?? ($body['data']['expires_in'] ?? 0))));
        $refresh_expires = absint($body['refresh_token_expires_in'] ?? ($body['refresh_expires_in'] ?? ($body['data']['refresh_token_expires_in'] ?? ($body['data']['refresh_expires_in'] ?? 0))));
        if ($access_token === '' && $refresh_token === '') return;

        $account = $this->wpbb_soc_feed_legacy_tiktok_account();
        if (!empty($account)) {
            if ($access_token !== '') $account['access_token'] = $access_token;
            if ($refresh_token !== '') $account['refresh_token'] = $refresh_token;
            if ($access_expires > 0) $account['access_token_expiration_date'] = time() + $access_expires - 1;
            if ($refresh_expires > 0) $account['refresh_token_expiration_date'] = time() + $refresh_expires - 1;
            $accounts = get_option('tiktok_feed_accounts', []);
            if (is_array($accounts)) {
                foreach ($accounts as $idx => $existing) {
                    if (!is_array($existing)) continue;
                    if (!empty($account['open_id']) && !empty($existing['open_id']) && $existing['open_id'] === $account['open_id']) {
                        $accounts[$idx] = array_merge($existing, $account);
                        update_option('tiktok_feed_accounts', $accounts, false);
                        break;
                    }
                }
            }
        }

        $connections = get_option('wpbb_soc_feed_connections', []);
        if (!is_array($connections)) $connections = [];
        if (empty($connections['tiktok']) || !is_array($connections['tiktok'])) $connections['tiktok'] = [];
        if ($access_token !== '') $connections['tiktok']['accessToken'] = $access_token;
        if ($refresh_token !== '') $connections['tiktok']['refreshToken'] = $refresh_token;
        if ($client_key !== '') $connections['tiktok']['clientKey'] = $client_key;
        if (empty($connections['tiktok']['openId']) && !empty($account['open_id'])) $connections['tiktok']['openId'] = (string) $account['open_id'];
        if (empty($connections['tiktok']['username'])) $connections['tiktok']['username'] = $this->wpbb_soc_feed_tiktok_username([]);
        update_option('wpbb_soc_feed_connections', $connections, false);
    }

    private function wpbb_soc_feed_array_value($row, $paths) {
        foreach ($paths as $path) {
            $cursor = $row;
            foreach ((array) $path as $key) {
                if (!is_array($cursor) || !array_key_exists($key, $cursor)) { $cursor = null; break; }
                $cursor = $cursor[$key];
            }
            if (is_string($cursor) && trim($cursor) !== '') return trim($cursor);
            if (is_numeric($cursor)) return (string) $cursor;
        }
        return '';
    }

    private function wpbb_soc_feed_first_media_string($value) {
        if (is_string($value)) return trim($value);
        if (is_numeric($value)) return (string) $value;
        if (!is_array($value)) return '';

        $priority_keys = ['url', 'uri', 'src', 'source', 'media_url', 'thumbnail_url', 'cover_image_url', 'cover_url', 'display_url'];
        foreach ($priority_keys as $key) {
            if (array_key_exists($key, $value)) {
                $found = $this->wpbb_soc_feed_first_media_string($value[$key]);
                if ($found !== '') return $found;
            }
        }

        foreach ($value as $child) {
            $found = $this->wpbb_soc_feed_first_media_string($child);
            if ($found !== '') return $found;
        }
        return '';
    }

    private function wpbb_soc_feed_array_deep_value($row, $paths) {
        foreach ($paths as $path) {
            $cursor = $row;
            foreach ((array) $path as $key) {
                if (!is_array($cursor) || !array_key_exists($key, $cursor)) { $cursor = null; break; }
                $cursor = $cursor[$key];
            }
            $found = $this->wpbb_soc_feed_first_media_string($cursor);
            if ($found !== '') return $found;
        }
        return '';
    }

    private function wpbb_soc_feed_tiktok_row_media($row) {
        $paths = [
            ['cover_image_url'], ['thumbnail_url'], ['media_url'], ['image'], ['image_url'], ['display_url'],
            ['cover_image_url_list'], ['cover_image'], ['cover_url'], ['cover'], ['thumbnail'],
            ['video', 'cover'], ['video', 'cover_url'], ['video', 'cover_url_list'], ['video', 'origin_cover'], ['video', 'origin_cover_url'], ['video', 'origin_cover_url_list'], ['video', 'dynamic_cover'], ['video', 'dynamic_cover_url'], ['video', 'dynamic_cover_url_list'],
            ['video', 'cover', 'url_list'], ['video', 'origin_cover', 'url_list'], ['video', 'dynamic_cover', 'url_list'],
            ['cover_image', 'url_list'], ['image_post', 'cover', 'url_list'], ['images', 0, 'url_list'], ['contents', 0, 'cover', 'url_list'], ['statistics', 'cover_image_url'],
        ];
        foreach ($paths as $path) {
            $media = $this->wpbb_soc_feed_resolve_media_url($this->wpbb_soc_feed_array_deep_value($row, [$path]));
            if (!$this->wpbb_soc_feed_is_bad_media_url($media)) return $media;
        }
        return '';
    }

    private function wpbb_soc_feed_tiktok_row_link($row, $username = '') {
        $link = $this->wpbb_soc_feed_array_deep_value($row, [
            ['share_url'], ['shareUrl'], ['permalink'], ['link'], ['url'], ['embed_link'], ['embedLink'], ['share_info', 'share_url'], ['shareInfo', 'shareUrl'], ['video', 'share_url'],
        ]);
        if ($this->wpbb_soc_feed_is_bad_permalink($link, 'tiktok')) $link = '';
        $id = $this->wpbb_soc_feed_array_deep_value($row, [['id'], ['video_id'], ['videoId'], ['aweme_id'], ['awemeId']]);
        $username = ltrim(trim((string) $username), '@');
        if ($link === '' && $username !== '' && $id !== '' && preg_match('/^\d+$/', $id)) {
            $link = 'https://www.tiktok.com/@' . rawurlencode($username) . '/video/' . rawurlencode($id);
        }
        return $link;
    }

    private function wpbb_soc_feed_tiktok_row_caption($row) {
        return $this->wpbb_soc_feed_array_deep_value($row, [
            ['title'], ['video_description'], ['videoDescription'], ['desc'], ['description'], ['caption'], ['text'], ['share_info', 'desc'], ['shareInfo', 'desc'], ['contents', 0, 'desc'],
        ]);
    }

    private function wpbb_soc_feed_tiktok_timestamp($row) {
        $value = $this->wpbb_soc_feed_array_deep_value($row, [
            ['create_time'], ['createTime'], ['created_time'], ['createdTime'], ['created_at'], ['createdAt'], ['timestamp'], ['time'], ['date'], ['publish_time'], ['publishTime'],
        ]);
        if ($value === '') return 0;
        if (is_numeric($value)) return (int) $value;
        $ts = strtotime($value);
        return $ts ? (int) $ts : 0;
    }

    private function wpbb_soc_feed_tiktok_row_has_media($row) {
        if (!is_array($row)) return false;
        return $this->wpbb_soc_feed_tiktok_row_media($row) !== '';
    }

    private function wpbb_soc_feed_tiktok_rows_from_cache($data) {
        $rows = [];
        $seen = [];

        $add = function ($row) use (&$rows, &$seen) {
            if (!is_array($row) || !$this->wpbb_soc_feed_tiktok_row_has_media($row)) return;
            $key_source = $this->wpbb_soc_feed_array_deep_value($row, [['id'], ['video_id'], ['videoId'], ['aweme_id'], ['awemeId']]);
            if ($key_source === '') $key_source = wp_json_encode($row);
            $key = md5((string) $key_source);
            if (isset($seen[$key])) return;
            $seen[$key] = true;
            $rows[] = $row;
        };

        $candidate_paths = [
            ['response', 'data', 'videos'], ['response', 'data', 'items'], ['response', 'itemList'], ['response', 'items'], ['response', 'videos'], ['response', 'aweme_list'], ['response', 'awemeList'], ['response'],
            ['data', 'videos'], ['data', 'items'], ['data', 'itemList'], ['data'], ['itemList'], ['items'], ['videos'], ['feed'], ['aweme_list'], ['awemeList'],
        ];
        foreach ($candidate_paths as $path) {
            $cursor = $data;
            foreach ($path as $key) {
                if (!is_array($cursor) || !array_key_exists($key, $cursor)) { $cursor = null; break; }
                $cursor = $cursor[$key];
            }
            if (!is_array($cursor)) continue;
            if ($this->wpbb_soc_feed_tiktok_row_has_media($cursor)) $add($cursor);
            foreach ($cursor as $row) {
                if (is_array($row)) $add($row);
            }
        }

        $walk = function ($node, $depth = 0) use (&$walk, $add) {
            if ($depth > 5 || !is_array($node)) return;
            if ($this->wpbb_soc_feed_tiktok_row_has_media($node)) {
                $add($node);
                return;
            }
            foreach ($node as $child) {
                if (is_array($child)) $walk($child, $depth + 1);
            }
        };
        $walk($data);

        usort($rows, function ($a, $b) {
            return $this->wpbb_soc_feed_tiktok_timestamp($b) <=> $this->wpbb_soc_feed_tiktok_timestamp($a);
        });
        return $rows;
    }

    private function wpbb_soc_feed_tiktok_item_from_row($row, $username = '') {
        if (!is_array($row)) return null;
        $media = $this->wpbb_soc_feed_resolve_media_url($this->wpbb_soc_feed_tiktok_row_media($row));
        if ($this->wpbb_soc_feed_is_bad_media_url($media)) return null;
        $link = $this->wpbb_soc_feed_tiktok_row_link($row, $username);
        if ($link === '') $link = $this->wpbb_soc_feed_profile_url('tiktok', $username);
        return [
            'media_url' => $media,
            'permalink' => esc_url_raw($link),
            'caption' => wp_strip_all_tags((string) $this->wpbb_soc_feed_tiktok_row_caption($row)),
            'type' => 'video',
            'timestamp' => $this->wpbb_soc_feed_tiktok_timestamp($row),
        ];
    }

    private function wpbb_soc_feed_wpbb_cached_items($platform, $limit = 12) {
        global $wpdb;
        if (!isset($wpdb) || empty($wpdb->options)) return [];
        $platform = sanitize_key($platform);
        $prefixes = [
            'instagram' => '_transient_wpbb_soc_feed_ig_',
            'tiktok' => '_transient_wpbb_soc_feed_tiktok_',
            'facebook' => '_transient_wpbb_soc_feed_fb_',
        ];
        if (empty($prefixes[$platform])) return [];
        $like = $wpdb->esc_like($prefixes[$platform]) . '%';
        $timeout_like = $wpdb->esc_like('_transient_timeout_') . '%';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s ORDER BY option_id DESC LIMIT 50", $like, $timeout_like), ARRAY_A);
        if (empty($rows)) return [];

        $items = [];
        $seen = [];
        $username = $this->wpbb_soc_feed_connection_value($platform, 'username');
        foreach ($rows as $option_row) {
            if (!(function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache())) {
                $option_name = (string) ($option_row['option_name'] ?? '');
                $cache_key = strpos($option_name, '_transient_') === 0 ? substr($option_name, strlen('_transient_')) : '';
                $timeout = $cache_key !== '' ? (int) get_option('_transient_timeout_' . $cache_key, 0) : 0;
                if ($timeout <= (time() + 300)) {
                    if ($cache_key !== '') delete_transient($cache_key);
                    continue;
                }
            }

            $data = isset($option_row['option_value']) ? maybe_unserialize($option_row['option_value']) : null;
            if (!is_array($data) && !empty($option_row['option_name'])) {
                $data = get_option($option_row['option_name']);
                if (is_string($data)) $data = maybe_unserialize($data);
            }
            if (!is_array($data)) continue;

            if ($platform === 'tiktok') {
                $rows = $this->wpbb_soc_feed_tiktok_rows_from_cache($data);
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $item = $this->wpbb_soc_feed_tiktok_item_from_row($row, $username);
                        if (!$item) continue;
                        $key = md5((string) ($item['media_url'] ?? '') . '|' . (string) ($item['permalink'] ?? ''));
                        if (isset($seen[$key])) continue;
                        $seen[$key] = true;
                        $items[] = $item;
                        if (count($items) >= $limit) break 2;
                    }
                    continue;
                }
            }

            foreach ($data as $row) {
                if (!is_array($row)) continue;
                $media = $this->wpbb_soc_feed_resolve_media_url($row['media_url'] ?? ($row['mediaUrl'] ?? ''));
                if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
                $key = md5($media . '|' . (string) ($row['permalink'] ?? ''));
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $items[] = [
                    'media_url' => $media,
                    'permalink' => !empty($row['permalink']) ? esc_url_raw((string) $row['permalink']) : '',
                    'caption' => !empty($row['caption']) ? wp_strip_all_tags((string) $row['caption']) : '',
                    'type' => !empty($row['type']) ? sanitize_key((string) $row['type']) : ($platform === 'tiktok' ? 'video' : 'image'),
                    'timestamp' => isset($row['timestamp']) ? (int) $row['timestamp'] : 0,
                ];
                if (count($items) >= $limit) break 2;
            }
        }

        usort($items, function ($a, $b) {
            return (int) ($b['timestamp'] ?? 0) <=> (int) ($a['timestamp'] ?? 0);
        });
        return array_slice($items, 0, $limit);
    }

    private function wpbb_soc_feed_legacy_cached_items($platform, $limit = 12) {
        global $wpdb;
        if (!isset($wpdb) || empty($wpdb->options)) return [];

        $platform = sanitize_key($platform);
        $limit = max(1, min(24, absint($limit)));
        $needles = $platform === 'tiktok'
            ? ['qlttf_cache_feed', '_transient_qlttf_cache_feed_', '_site_transient_qlttf_cache_feed_']
            : ['qligg_cache_feed', '_transient_qligg_cache_feed_', '_site_transient_qligg_cache_feed_'];

        $where = [];
        $params = [];
        foreach ($needles as $needle) {
            $where[] = 'option_name LIKE %s';
            $params[] = '%' . $wpdb->esc_like($needle) . '%';
        }
        $params[] = '%' . $wpdb->esc_like('_timeout_') . '%';
        $sql = "SELECT option_name, option_value FROM {$wpdb->options} WHERE (" . implode(' OR ', $where) . ") AND option_name NOT LIKE %s ORDER BY option_id DESC LIMIT 120";
        $prepared = call_user_func_array([$wpdb, 'prepare'], array_merge([$sql], $params));
        $option_rows = $wpdb->get_results($prepared, ARRAY_A);

        if (!empty($wpdb->sitemeta)) {
            $site_where = [];
            $site_params = [];
            foreach ($needles as $needle) {
                $site_where[] = 'meta_key LIKE %s';
                $site_params[] = '%' . $wpdb->esc_like($needle) . '%';
            }
            $site_params[] = '%' . $wpdb->esc_like('_timeout_') . '%';
            $site_sql = "SELECT meta_key AS option_name, meta_value AS option_value FROM {$wpdb->sitemeta} WHERE (" . implode(' OR ', $site_where) . ") AND meta_key NOT LIKE %s ORDER BY meta_id DESC LIMIT 120";
            $site_prepared = call_user_func_array([$wpdb, 'prepare'], array_merge([$site_sql], $site_params));
            $site_rows = $wpdb->get_results($site_prepared, ARRAY_A);
            if (!empty($site_rows)) $option_rows = array_merge(is_array($option_rows) ? $option_rows : [], $site_rows);
        }

        if (empty($option_rows)) return [];

        $items = [];
        $seen = [];
        $username = $platform === 'tiktok' ? $this->wpbb_soc_feed_tiktok_username([]) : $this->wpbb_soc_feed_connection_value($platform, 'username');

        foreach ($option_rows as $option_row) {
            $data = isset($option_row['option_value']) ? maybe_unserialize($option_row['option_value']) : null;
            if (!is_array($data) && !empty($option_row['option_name'])) {
                $data = get_option($option_row['option_name']);
                if (is_string($data)) $data = maybe_unserialize($data);
            }
            if (!is_array($data)) continue;

            if ($platform === 'tiktok') {
                $rows = $this->wpbb_soc_feed_tiktok_rows_from_cache($data);
            } else {
                $rows = $data['response']['data'] ?? ($data['data'] ?? []);
                if (isset($rows['data']) && is_array($rows['data'])) $rows = $rows['data'];
            }
            if (!is_array($rows) || empty($rows)) continue;

            foreach ($rows as $row) {
                if (!is_array($row)) continue;

                if ($platform === 'instagram') {
                    $media = $this->wpbb_soc_feed_array_value($row, [ ['media','url'], ['media','thumbnail'], ['media','thumbnail_url'], ['media_url'], ['thumbnail_url'], ['display_url'], ['url'] ]);
                    $link = $this->wpbb_soc_feed_array_value($row, [ ['link'], ['permalink'], ['media','link'] ]);
                    $caption = $this->wpbb_soc_feed_array_value($row, [ ['caption','text'], ['caption'], ['title'] ]);
                    $media = $this->wpbb_soc_feed_resolve_media_url($media);
                    if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
                    $key = md5($media . '|' . (string) $link);
                    if (isset($seen[$key])) continue;
                    $seen[$key] = true;
                    $items[] = [
                        'media_url' => $media,
                        'permalink' => esc_url_raw($link),
                        'caption' => wp_strip_all_tags((string) $caption),
                        'type' => 'image',
                    ];
                } else {
                    $item = $this->wpbb_soc_feed_tiktok_item_from_row($row, $username);
                    if (!$item) continue;
                    $key = md5((string) ($item['media_url'] ?? '') . '|' . (string) ($item['permalink'] ?? ''));
                    if (isset($seen[$key])) continue;
                    $seen[$key] = true;
                    $items[] = $item;
                }

                if (count($items) >= $limit) break 2;
            }
        }

        usort($items, function ($a, $b) {
            return (int) ($b['timestamp'] ?? 0) <=> (int) ($a['timestamp'] ?? 0);
        });
        return array_slice($items, 0, $limit);
    }

    private function wpbb_soc_feed_is_bad_permalink($url, $platform) {
        $url = strtolower(trim((string) $url));
        if ($url === '') return false;
        if ($platform === 'tiktok' && strpos($url, 'tiktok.com') !== false && strpos($url, '/video/') !== false && !preg_match('#/video/\d+#', $url)) return true;
        return false;
    }

    private function wpbb_soc_feed_resolve_media_url($url) {
        $url = trim((string) $url);
        if ($url === '') return '';
        $theme_uri = trailingslashit(get_stylesheet_directory_uri());
        $parent_uri = trailingslashit(get_template_directory_uri());
        $replacements = [
            '{{GARILLA_ASSET_URI}}' => $theme_uri,
            '{{CHILD_THEME_URI}}' => $theme_uri,
            '{{STYLESHEET_URI}}' => $theme_uri,
            '{{THEME_URI}}' => $theme_uri,
            '{{PARENT_THEME_URI}}' => $parent_uri,
            '{{TEMPLATE_URI}}' => $parent_uri,
        ];
        $url = strtr($url, $replacements);
        return esc_url_raw($url);
    }

    /**
     * Read only genuinely timed social-feed transients.
     *
     * A restored/migrated database can contain an orphaned transient value
     * after its timeout row has already been removed. WordPress then treats
     * that value as non-expiring, which is unsafe for Instagram and TikTok
     * because their signed CDN image URLs expire. Reject orphaned/expired
     * values so the API is asked for fresh media URLs instead.
     */
    private function wpbb_soc_feed_get_timed_transient($cache_key) {
        $cache_key = trim((string) $cache_key);
        if ($cache_key === '') return false;

        if (function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache()) {
            $cached = get_transient($cache_key);
            return is_array($cached) ? $cached : false;
        }

        $timeout = (int) get_option('_transient_timeout_' . $cache_key, 0);
        if ($timeout <= (time() + 300)) {
            delete_transient($cache_key);
            return false;
        }

        $cached = get_transient($cache_key);
        return is_array($cached) ? $cached : false;
    }

    /**
     * Detect expired signed Instagram/Facebook and TikTok CDN URLs.
     */
    private function wpbb_soc_feed_media_url_is_expired($url, $skew = 300) {
        $url = trim((string) $url);
        if ($url === '') return true;

        $parts = wp_parse_url($url);
        if (!is_array($parts)) return false;
        $host = strtolower((string) ($parts['host'] ?? ''));
        $query = [];
        if (!empty($parts['query'])) parse_str((string) $parts['query'], $query);
        $expires = 0;

        if ((strpos($host, 'cdninstagram.com') !== false || strpos($host, 'fbcdn.net') !== false) && !empty($query['oe'])) {
            $oe = (string) $query['oe'];
            if (ctype_xdigit($oe)) $expires = hexdec($oe);
        }

        if ($expires <= 0 && (strpos($host, 'tiktokcdn') !== false || strpos($host, 'tiktokcdn-eu.com') !== false)) {
            foreach (['x-expires', 'x_expires', 'expires', 'expire'] as $key) {
                if (!empty($query[$key]) && is_numeric($query[$key])) {
                    $expires = (int) $query[$key];
                    break;
                }
            }
        }

        return $expires > 0 && $expires <= (time() + max(0, absint($skew)));
    }

    private function wpbb_soc_feed_is_bad_media_url($url) {
        $raw_url = trim((string) $url);
        if ($raw_url === '') return true;
        if ($this->wpbb_soc_feed_media_url_is_expired($raw_url)) return true;
        $url = strtolower($raw_url);
        if (strpos($url, '{{') !== false || strpos($url, '}}') !== false) return true;
        if (strpos($url, 'noop.webp') !== false) return true;
        if (strpos($url, 'musically-maliva-obj/1650691800996870') !== false) return true;
        if (strpos($url, 'www.tiktok.com/@') !== false || strpos($url, 'm.tiktok.com/@') !== false || strpos($url, 'vm.tiktok.com/') !== false || strpos($url, 'vt.tiktok.com/') !== false || strpos($url, '/video/') !== false || strpos($url, '/embed/') !== false) return true;
        if (preg_match('#/(avatar|default|placeholder)[^/]*\.(?:webp|jpg|jpeg|png|gif)$#', $url)) return true;
        return false;
    }

    private function wpbb_soc_feed_manual_items($json) {
        $json = trim((string) $json);
        if ($json === '') return [];
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return [];
        $items = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) continue;
            $media = '';
            foreach (['mediaUrl','media_url','image','imageUrl','thumbnail_url','thumbnailUrl','url'] as $key) {
                if (!empty($row[$key]) && is_string($row[$key])) { $media = trim($row[$key]); break; }
            }
            $media = $this->wpbb_soc_feed_resolve_media_url($media);
            if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
            $items[] = [
                'media_url' => $media,
                'permalink' => !empty($row['permalink']) ? esc_url_raw($row['permalink']) : (!empty($row['link']) ? esc_url_raw($row['link']) : ''),
                'caption' => !empty($row['caption']) ? sanitize_text_field($row['caption']) : (!empty($row['title']) ? sanitize_text_field($row['title']) : ''),
                'type' => !empty($row['type']) ? sanitize_key($row['type']) : '',
            ];
        }
        return $items;
    }

    private function wpbb_soc_feed_instagram_items($token, $limit = 8) {
        $token = trim((string) $token);
        $limit = max(1, min(24, absint($limit)));
        if ($token === '') $token = $this->wpbb_soc_feed_connection_value('instagram', 'accessToken');
        if ($token === '') $token = $this->wpbb_soc_feed_legacy_instagram_token();
        if ($token === '') return [];
        $cache_key = 'wpbb_soc_feed_ig_v2_' . md5($token . '|' . $limit);
        $cached = $this->wpbb_soc_feed_get_timed_transient($cache_key);
        if (is_array($cached)) return $cached;

        $url = add_query_arg([
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
            'limit' => $limit,
            'access_token' => $token,
        ], 'https://graph.instagram.com/me/media');

        $response = wp_remote_get($url, ['timeout' => 10, 'redirection' => 3]);
        if (is_wp_error($response)) return [];
        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) return [];
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (empty($body['data']) || !is_array($body['data'])) return [];

        $items = [];
        foreach ($body['data'] as $row) {
            if (!is_array($row)) continue;
            $media = '';
            if (!empty($row['media_url']) && is_string($row['media_url'])) $media = $row['media_url'];
            if (($row['media_type'] ?? '') === 'VIDEO' && !empty($row['thumbnail_url'])) $media = $row['thumbnail_url'];
            $media = $this->wpbb_soc_feed_resolve_media_url($media);
            if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
            $items[] = [
                'media_url' => $media,
                'permalink' => !empty($row['permalink']) ? esc_url_raw($row['permalink']) : '',
                'caption' => !empty($row['caption']) ? wp_strip_all_tags((string) $row['caption']) : '',
                'type' => strtolower((string) ($row['media_type'] ?? 'image')),
            ];
        }
        set_transient($cache_key, $items, HOUR_IN_SECONDS);
        return $items;
    }


    private function wpbb_soc_feed_tiktok_access_token($access_token, $refresh_token, $client_key = '', $force_refresh = false) {
        $access_token = trim((string) $access_token);
        $refresh_token = trim((string) $refresh_token);
        $client_key = trim((string) $client_key);
        if ($client_key === '') $client_key = $this->wpbb_soc_feed_tiktok_client_key_from_cache();

        if ($access_token !== '' && !$force_refresh && !$this->wpbb_soc_feed_tiktok_access_token_is_expired()) return $access_token;
        if ($refresh_token === '') return $access_token;

        $cache_key = 'wpbb_soc_feed_tiktok_token_' . md5($refresh_token . '|' . $client_key);
        $cached = get_transient($cache_key);
        if (is_string($cached) && $cached !== '' && !$force_refresh) return $cached;

        $bodies = [];
        if ($client_key !== '') {
            $response = wp_remote_post('https://open.tiktokapis.com/v2/oauth/token/', [
                'timeout' => 12,
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'client_key' => $client_key,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refresh_token,
                ],
            ]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) >= 200 && wp_remote_retrieve_response_code($response) < 300) {
                $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
                if (is_array($decoded)) $bodies[] = $decoded;
            }
        }

        // QuadLayers' old plugin refreshes TikTok tokens through this proxy using only the refresh token.
        // Keep it as a compatibility fallback for sites migrated from wp-tiktok-feed.
        $ql_response = wp_remote_post('https://tiktokfeedv2.quadlayers.com/refreshToken', [
            'timeout' => 45,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode(['refresh_token' => $refresh_token]),
        ]);
        if (!is_wp_error($ql_response) && wp_remote_retrieve_response_code($ql_response) >= 200 && wp_remote_retrieve_response_code($ql_response) < 300) {
            $decoded = json_decode((string) wp_remote_retrieve_body($ql_response), true);
            if (is_array($decoded)) $bodies[] = $decoded;
        }

        foreach ($bodies as $body) {
            $token = '';
            if (!empty($body['access_token'])) $token = (string) $body['access_token'];
            if ($token === '' && !empty($body['data']['access_token'])) $token = (string) $body['data']['access_token'];
            if ($token === '') continue;
            $expires = absint($body['access_token_expires_in'] ?? ($body['expires_in'] ?? ($body['data']['access_token_expires_in'] ?? ($body['data']['expires_in'] ?? 3600))));
            $this->wpbb_soc_feed_tiktok_persist_tokens($body, $client_key);
            set_transient($cache_key, $token, max(300, $expires - 300));
            return $token;
        }

        return $access_token;
    }

    private function wpbb_soc_feed_tiktok_username_from_url($url) {
        $url = trim((string) $url);
        if ($url === '') return '';
        if (preg_match('#tiktok\.com/@([^/?#]+)#i', $url, $m)) {
            return sanitize_text_field(rawurldecode($m[1]));
        }
        return '';
    }

    private function wpbb_soc_feed_tiktok_username($attributes = []) {
        $attributes = is_array($attributes) ? $attributes : [];
        $username = trim((string) ($attributes['username'] ?? ''));
        if ($username === '') $username = $this->wpbb_soc_feed_connection_value('tiktok', 'username');
        if ($username === '') $username = $this->wpbb_soc_feed_tiktok_username_from_url($attributes['buttonUrl'] ?? '');
        if ($username === '') $username = 'garillaprizes';
        return ltrim($username, '@');
    }

    private function wpbb_soc_feed_tiktok_plugin_shortcode($id = 0) {
        if (!shortcode_exists('tiktok-feed')) return '';
        $id = absint($id);
        return do_shortcode('[tiktok-feed id="' . $id . '"]');
    }

    private function wpbb_soc_feed_tiktok_manual_plugin_shell() {
        $feeds = get_option('tiktok_feed_feeds', []);
        $feed = (is_array($feeds) && !empty($feeds)) ? reset($feeds) : [];
        if (!is_array($feed)) $feed = [];

        $accounts = get_option('tiktok_feed_accounts', []);
        $account = (is_array($accounts) && !empty($accounts)) ? reset($accounts) : [];
        if (!is_array($account)) $account = [];

        $open_id = isset($feed['open_id']) ? (string) $feed['open_id'] : '';
        if ($open_id === '' && !empty($account['open_id'])) $open_id = (string) $account['open_id'];
        if ($open_id === '') return '';

        $defaults = [
            'id' => 0,
            'source' => 'account',
            'open_id' => $open_id,
            'region' => 'US',
            'hashtag' => 'WordPress',
            'username' => $this->wpbb_soc_feed_tiktok_username([]) ?: 'garillaprizes',
            'create_time' => 0,
            'layout' => 'carousel-vertical',
            'limit' => 12,
            'columns' => 5,
            'hide_carousel_feed' => true,
            'lazy' => true,
            'profile' => [
                'display' => false,
                'username' => '',
                'nickname' => '',
                'biography' => '',
                'link_text' => 'Follow',
                'avatar' => '',
            ],
            'video' => ['spacing' => 24, 'radius' => 24],
            'highlight' => ['id' => '', 'tag' => '', 'position' => '1, 5, 7'],
            'mask' => [
                'display' => true,
                'background' => '#000000',
                'likes_count' => true,
                'comments_count' => true,
            ],
            'box' => [
                'display' => false,
                'padding' => 1,
                'radius' => 0,
                'background' => '#fefefe',
                'text_color' => '#000000',
            ],
            'card' => [
                'display' => false,
                'radius' => 0,
                'font_size' => '12',
                'background' => '#ffffff',
                'background_hover' => '#ffffff',
                'text_color' => '#000000',
                'padding' => '5',
                'likes_count' => true,
                'max_word_count' => 10,
                'video_description' => true,
                'comments_count' => true,
                'text_align' => 'left',
            ],
            'carousel' => [
                'autoplay' => false,
                'autoplay_interval' => 3000,
                'navarrows' => true,
                'navarrows_color' => '',
                'pagination' => true,
                'pagination_color' => '',
            ],
            'modal' => [
                'display' => true,
                'profile' => true,
                'download' => false,
                'video_description' => true,
                'likes_count' => true,
                'autoplay' => true,
                'comments_count' => true,
                'date' => true,
                'controls' => true,
                'align' => 'right',
            ],
            'button' => [
                'display' => false,
                'text' => 'View on TikTok',
                'text_color' => '#ffff',
                'background' => '',
                'background_hover' => '',
            ],
            'button_load' => [
                'display' => false,
                'text_color' => '#ffff',
                'text' => 'Load more...',
                'background' => '',
                'background_hover' => '',
                'profile' => '',
            ],
        ];
        $feed = array_replace_recursive($defaults, $feed);
        $feed['open_id'] = $open_id;
        $id = absint($feed['id'] ?? 0);

        if (wp_style_is('qlttf-frontend', 'registered')) wp_enqueue_style('qlttf-frontend');
        if (wp_script_is('qlttf-frontend', 'registered')) wp_enqueue_script('qlttf-frontend');
        if (wp_style_is('qlttf-swiper', 'registered')) wp_enqueue_style('qlttf-swiper');
        if (wp_script_is('qlttf-swiper', 'registered')) wp_enqueue_script('qlttf-swiper');
        return '<div id="tiktok-feed-feed-' . esc_attr($id) . '" class="tiktok-feed-feed" data-feed="' . esc_attr(wp_json_encode($feed)) . '"><!-- <FeedContainer/> --></div>';
    }

    private function wpbb_soc_feed_tiktok_plugin_shell($attributes, $title, $headingTag, $buttonText, $buttonUrl, $customClasses) {
        $html = $this->wpbb_soc_feed_tiktok_plugin_shortcode(0);
        if (trim((string) $html) === '' || (strpos((string) $html, 'tiktok-feed-feed') === false && strpos((string) $html, 'qlttf') === false)) {
            $html = $this->wpbb_soc_feed_tiktok_manual_plugin_shell();
        }
        if (trim((string) $html) === '') return '';
        $wrapper = get_block_wrapper_attributes(['class' => trim('wpbb-soc-feed wpbb-soc-feed--tiktok wpbb-soc-feed--plugin ' . $customClasses)]);
        $heading = $title ? '<' . $headingTag . ' class="wpbb-soc-feed__title">' . $title . '</' . $headingTag . '>' : '';
        $cta = ($buttonText && $buttonUrl) ? '<p class="wpbb-soc-feed__cta"><a class="btn btn-primary" href="' . esc_url($buttonUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html($buttonText) . '</a></p>' : '';
        return '<section ' . $wrapper . '>' . $heading . '<div class="wpbb-soc-feed__embed wpbb-soc-feed__embed--tiktok-plugin">' . $html . '</div>' . $cta . '</section>';
    }

    private function wpbb_soc_feed_tiktok_items($attributes, $limit = 8) {
        $limit = max(1, min(24, absint($limit)));
        $legacy_cached_items = $this->wpbb_soc_feed_legacy_cached_items('tiktok', $limit);
        $access_value = $this->wpbb_soc_feed_value($attributes, 'tiktok', 'accessToken');
        if ($access_value === '') $access_value = $this->wpbb_soc_feed_legacy_tiktok_value('accessToken');
        $refresh_value = $this->wpbb_soc_feed_value($attributes, 'tiktok', 'refreshToken');
        if ($refresh_value === '') $refresh_value = $this->wpbb_soc_feed_legacy_tiktok_value('refreshToken');
        $access_token = $this->wpbb_soc_feed_tiktok_access_token(
            $access_value,
            $refresh_value,
            $this->wpbb_soc_feed_value($attributes, 'tiktok', 'clientKey') ?: $this->wpbb_soc_feed_legacy_tiktok_value('clientKey')
        );
        if ($access_token === '') return $legacy_cached_items;

        $cache_key = 'wpbb_soc_feed_tiktok_v2_' . md5($access_token . '|' . $limit);
        $cached = $this->wpbb_soc_feed_get_timed_transient($cache_key);
        if (is_array($cached)) return $cached;

        $request_videos = function ($token) use ($limit) {
            return wp_remote_post('https://open.tiktokapis.com/v2/video/list/?fields=id,title,video_description,duration,cover_image_url,share_url,embed_link,create_time', [
                'timeout' => 12,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'body' => wp_json_encode(['max_count' => $limit]),
            ]);
        };
        $response = $request_videos($access_token);
        if (is_wp_error($response)) return $legacy_cached_items;
        $code = wp_remote_retrieve_response_code($response);
        if (($code === 401 || $code === 403) && $refresh_value !== '') {
            $refreshed_token = $this->wpbb_soc_feed_tiktok_access_token('', $refresh_value, $this->wpbb_soc_feed_value($attributes, 'tiktok', 'clientKey') ?: $this->wpbb_soc_feed_legacy_tiktok_value('clientKey'), true);
            if ($refreshed_token !== '' && $refreshed_token !== $access_token) {
                $access_token = $refreshed_token;
                $response = $request_videos($access_token);
                if (is_wp_error($response)) return $legacy_cached_items;
                $code = wp_remote_retrieve_response_code($response);
            }
        }
        if ($code < 200 || $code >= 300) return $legacy_cached_items;
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $videos = $body['data']['videos'] ?? [];
        if (!is_array($videos)) return $legacy_cached_items;

        $items = [];
        $username = $this->wpbb_soc_feed_tiktok_username($attributes);
        usort($videos, function ($a, $b) {
            return $this->wpbb_soc_feed_tiktok_timestamp($b) <=> $this->wpbb_soc_feed_tiktok_timestamp($a);
        });
        foreach ($videos as $row) {
            $item = $this->wpbb_soc_feed_tiktok_item_from_row($row, $username);
            if (!$item) continue;
            $items[] = $item;
            if (count($items) >= $limit) break;
        }
        if (empty($items) && !empty($legacy_cached_items)) return $legacy_cached_items;
        set_transient($cache_key, $items, HOUR_IN_SECONDS);
        return $items;
    }

    private function wpbb_soc_feed_facebook_items($page_id, $token, $limit = 8) {
        $page_id = trim((string) $page_id);
        $token = trim((string) $token);
        $limit = max(1, min(24, absint($limit)));
        if ($page_id === '' || $token === '') return [];

        $cache_key = 'wpbb_soc_feed_fb_v2_' . md5($page_id . '|' . $token . '|' . $limit);
        $cached = $this->wpbb_soc_feed_get_timed_transient($cache_key);
        if (is_array($cached)) return $cached;

        $url = add_query_arg([
            'fields' => 'id,message,full_picture,permalink_url,created_time',
            'limit' => $limit,
            'access_token' => $token,
        ], 'https://graph.facebook.com/v19.0/' . rawurlencode($page_id) . '/posts');
        $response = wp_remote_get($url, ['timeout' => 12, 'redirection' => 3]);
        if (is_wp_error($response)) return [];
        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) return [];
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        if (empty($body['data']) || !is_array($body['data'])) return [];

        $items = [];
        foreach ($body['data'] as $row) {
            if (!is_array($row) || empty($row['full_picture'])) continue;
            $media = $this->wpbb_soc_feed_resolve_media_url((string) $row['full_picture']);
            if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
            $items[] = [
                'media_url' => $media,
                'permalink' => !empty($row['permalink_url']) ? esc_url_raw((string) $row['permalink_url']) : '',
                'caption' => !empty($row['message']) ? wp_strip_all_tags((string) $row['message']) : '',
                'type' => 'image',
            ];
        }
        set_transient($cache_key, $items, HOUR_IN_SECONDS);
        return $items;
    }

    private function wpbb_soc_feed_profile_url($platform, $username, $fallback = '') {
        $username = ltrim(trim((string) $username), '@');
        if ($fallback !== '') return esc_url_raw($fallback);
        if ($username === '') return '';
        if ($platform === 'tiktok') return 'https://www.tiktok.com/@' . rawurlencode($username);
        if ($platform === 'instagram') return 'https://www.instagram.com/' . rawurlencode($username) . '/';
        if ($platform === 'facebook') return 'https://www.facebook.com/' . rawurlencode($username) . '/';
        return '';
    }


    private function wpbb_soc_feed_normalize_editor_preview_items($items, $limit = 6) {
        $limit = max(1, min(24, absint($limit)));
        $out = [];
        foreach ((array) $items as $item) {
            if (!is_array($item)) continue;
            $media = $this->wpbb_soc_feed_resolve_media_url($item['media_url'] ?? ($item['mediaUrl'] ?? ''));
            if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
            $out[] = [
                'mediaUrl' => esc_url_raw($media),
                'permalink' => !empty($item['permalink']) ? esc_url_raw((string) $item['permalink']) : '',
                'caption' => !empty($item['caption']) ? wp_strip_all_tags((string) $item['caption']) : '',
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    private function wpbb_soc_feed_items_for_editor($platform, $attributes = [], $limit = 6) {
        $platform = sanitize_key($platform);
        $limit = max(1, min(24, absint($limit)));
        $attributes = is_array($attributes) ? $attributes : [];
        $attributes['platform'] = $platform;
        $attributes['limit'] = $limit;

        // Ask the connected API first. Each API method already uses a valid,
        // expiring transient, so this is cheap on normal requests and refreshes
        // signed CDN URLs before they expire. Raw database cache rows are only
        // fallbacks and are filtered for timeout/URL expiry below.
        $items = [];
        if ($platform === 'instagram') {
            $items = $this->wpbb_soc_feed_instagram_items($this->wpbb_soc_feed_value($attributes, 'instagram', 'accessToken'), $limit);
        } elseif ($platform === 'tiktok') {
            $items = $this->wpbb_soc_feed_tiktok_items($attributes, $limit);
        } elseif ($platform === 'facebook') {
            $items = $this->wpbb_soc_feed_facebook_items($this->wpbb_soc_feed_value($attributes, 'facebook', 'pageId') ?: ($attributes['username'] ?? ''), $this->wpbb_soc_feed_value($attributes, 'facebook', 'accessToken'), $limit);
        }
        if (empty($items)) {
            $items = $this->wpbb_soc_feed_wpbb_cached_items($platform, $limit);
        }
        if (empty($items)) {
            $items = $this->wpbb_soc_feed_legacy_cached_items($platform, $limit);
        }
        return $this->wpbb_soc_feed_normalize_editor_preview_items($items, $limit);
    }

    private function wpbb_soc_feed_editor_preview_items($platform, $limit = 6) {
        return $this->wpbb_soc_feed_items_for_editor($platform, [], $limit);
    }

    public function ajax_soc_feed_preview() {
        check_ajax_referer('wpbb_builder_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Not allowed.', 'wp-bbuilder')], 403);
        }

        $attributes = [];
        if (isset($_POST['attributes'])) {
            $raw = wp_unslash($_POST['attributes']);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $attributes = $decoded;
            }
        }
        $platform = sanitize_key($_POST['platform'] ?? ($attributes['platform'] ?? 'instagram'));
        if (!in_array($platform, ['instagram', 'tiktok', 'facebook'], true)) $platform = 'instagram';
        $limit = max(1, min(24, absint($_POST['limit'] ?? ($attributes['limit'] ?? 8))));
        $attributes['platform'] = $platform;
        $attributes['limit'] = $limit;

        wp_send_json_success([
            'items' => $this->wpbb_soc_feed_items_for_editor($platform, $attributes, $limit),
            'meta' => $this->wpbb_soc_feed_editor_connection_meta(),
        ]);
    }

    private function wpbb_soc_feed_editor_preview_payload() {
        return [
            'instagram' => $this->wpbb_soc_feed_editor_preview_items('instagram', 24),
            'tiktok' => $this->wpbb_soc_feed_editor_preview_items('tiktok', 24),
            'facebook' => $this->wpbb_soc_feed_editor_preview_items('facebook', 24),
        ];
    }

    private function wpbb_soc_feed_editor_connection_meta() {
        return [
            'instagram' => ['username' => $this->wpbb_soc_feed_connection_value('instagram', 'username')],
            'tiktok' => ['username' => $this->wpbb_soc_feed_connection_value('tiktok', 'username')],
            'facebook' => ['username' => $this->wpbb_soc_feed_connection_value('facebook', 'username')],
        ];
    }

    private function wpbb_soc_feed_enqueue_public_assets() {
        if (wp_style_is('wpbb-shared', 'registered')) wp_enqueue_style('wpbb-shared');
        if (wp_style_is('wpbb-swiper', 'registered')) wp_enqueue_style('wpbb-swiper');
        if (wp_script_is('wpbb-swiper', 'registered')) wp_enqueue_script('wpbb-swiper');
        if (wp_script_is('wpbb-swiper-init', 'registered')) wp_enqueue_script('wpbb-swiper-init');
    }

    private function wpbb_soc_feed_public_items($platform, $attributes, $limit) {
        // Use the exact same item resolution pipeline as the admin/editor AJAX
        // preview, then normalize it back to the public renderer format. This
        // fixes the case where the admin preview shows Instagram/TikTok items
        // but the front end returns empty because a different public-only path
        // missed the good cache/fallback data.
        $preview_items = $this->wpbb_soc_feed_items_for_editor($platform, is_array($attributes) ? $attributes : [], $limit);
        $items = [];
        foreach ((array) $preview_items as $item) {
            if (!is_array($item)) continue;
            $media = $this->wpbb_soc_feed_resolve_media_url($item['media_url'] ?? ($item['mediaUrl'] ?? ''));
            if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
            $items[] = [
                'media_url' => $media,
                'permalink' => !empty($item['permalink']) ? esc_url_raw((string) $item['permalink']) : '',
                'caption' => !empty($item['caption']) ? wp_strip_all_tags((string) $item['caption']) : '',
                'type' => $platform === 'tiktok' ? 'video' : 'image',
            ];
            if (count($items) >= $limit) break;
        }
        return $items;
    }

    public function render_soc_feed_block($attributes, $content, $block) {
        $this->wpbb_soc_feed_enqueue_public_assets();
        $platform = sanitize_key($attributes['platform'] ?? 'instagram');
        if (!in_array($platform, ['instagram', 'tiktok', 'facebook'], true)) $platform = 'instagram';
        $default_source = $platform === 'instagram' ? 'instagram_api' : ($platform === 'tiktok' ? 'tiktok_api' : 'facebook_api');
        $sourceMode = sanitize_key($attributes['sourceMode'] ?? $default_source);
        $default_title = $platform === 'tiktok' ? 'TikTok' : ($platform === 'facebook' ? 'Facebook' : 'Instagram');
        $title = esc_html($attributes['title'] ?? $default_title);
        $headingTag = in_array(($attributes['headingTag'] ?? 'h2'), ['h2','h3','h4','div'], true) ? $attributes['headingTag'] : 'h2';
        $username = trim((string) ($attributes['username'] ?? ''));
        if ($username === '') $username = $this->wpbb_soc_feed_connection_value($platform, 'username');
        if ($platform === 'tiktok' && $username === '') $username = $this->wpbb_soc_feed_tiktok_username($attributes);
        $limit = max(1, min(24, absint($attributes['limit'] ?? 8)));
        $buttonText = trim((string) ($attributes['buttonText'] ?? ''));
        $buttonUrl = $this->wpbb_soc_feed_profile_url($platform, $username, trim((string) ($attributes['buttonUrl'] ?? '')));
        $customClasses = !empty($attributes['customClasses']) ? implode(' ', $this->wpbb_class_tokens_from_value($attributes['customClasses'])) : '';

        if ($sourceMode === 'shortcode') {
            $shortcode = trim((string) ($attributes['shortcode'] ?? ''));
            if ($shortcode === '') return '';
            $wrapper = get_block_wrapper_attributes(['class' => trim('wpbb-soc-feed wpbb-soc-feed--' . $platform . ' wpbb-soc-feed--shortcode ' . $customClasses)]);
            return '<section ' . $wrapper . '>' . ($title ? '<' . $headingTag . ' class="wpbb-soc-feed__title">' . $title . '</' . $headingTag . '>' : '') . '<div class="wpbb-soc-feed__embed">' . do_shortcode($shortcode) . '</div>' . ($buttonText && $buttonUrl ? '<p class="wpbb-soc-feed__cta"><a class="btn btn-primary" href="' . esc_url($buttonUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html($buttonText) . '</a></p>' : '') . '</section>';
        }

        if ($sourceMode === 'html') {
            $html = trim((string) ($attributes['html'] ?? ''));
            if ($html === '') return '';
            $wrapper = get_block_wrapper_attributes(['class' => trim('wpbb-soc-feed wpbb-soc-feed--' . $platform . ' wpbb-soc-feed--html ' . $customClasses)]);
            return '<section ' . $wrapper . '>' . ($title ? '<' . $headingTag . ' class="wpbb-soc-feed__title">' . $title . '</' . $headingTag . '>' : '') . '<div class="wpbb-soc-feed__embed">' . wp_kses_post($html) . '</div>' . ($buttonText && $buttonUrl ? '<p class="wpbb-soc-feed__cta"><a class="btn btn-primary" href="' . esc_url($buttonUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html($buttonText) . '</a></p>' : '') . '</section>';
        }

        $items = [];
        if (in_array($sourceMode, ['instagram_api','tiktok_api','facebook_api'], true)) {
            $items = $this->wpbb_soc_feed_public_items($platform, $attributes, $limit);
        }
        if ($sourceMode === 'manual') {
            $items = $this->wpbb_soc_feed_manual_items($attributes['itemsJson'] ?? '');
        }
        if (empty($items) && $platform === 'tiktok') {
            $plugin_shell = $this->wpbb_soc_feed_tiktok_plugin_shell($attributes, $title, $headingTag, $buttonText, $buttonUrl, $customClasses);
            if ($plugin_shell !== '') return $plugin_shell;
        }
        if (empty($items)) {
            if (current_user_can('manage_options')) {
                $settings_url = esc_url(admin_url('options-general.php?page=wpbb-social-connections'));
                $wrapper = get_block_wrapper_attributes(['class' => trim('wpbb-soc-feed wpbb-soc-feed--' . $platform . ' wpbb-soc-feed--empty ' . $customClasses)]);
                return '<section ' . $wrapper . '><div class="wpbb-soc-feed__notice"><strong>' . esc_html(sprintf(__('%s feed is not connected yet.', 'wp-bbuilder'), $default_title)) . '</strong> <a class="button button-primary" href="' . $settings_url . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Connect social feed', 'wp-bbuilder') . '</a></div></section>';
            }
            return '';
        }

        $mobileStack = !empty($attributes['mobileStack']);
        $mobileFeedCount = max(1, min(12, absint($attributes['mobileFeedCount'] ?? 3)));
        $slides = '';
        $slide_index = 0;
        foreach (array_slice($items, 0, $limit) as $item) {
            $media = $this->wpbb_soc_feed_resolve_media_url($item['media_url'] ?? ($item['mediaUrl'] ?? ''));
            if ($this->wpbb_soc_feed_is_bad_media_url($media)) continue;
            $media = esc_url($media);
            $caption = trim((string) ($item['caption'] ?? ''));
            $caption_attr = $caption !== '' ? esc_attr(wp_trim_words($caption, 16, '...')) : esc_attr($title);
            $link = !empty($item['permalink']) ? esc_url($item['permalink']) : $buttonUrl;
            if ($this->wpbb_soc_feed_is_bad_permalink($link, $platform)) $link = $buttonUrl;
            $image = '<img src="' . $media . '" alt="' . $caption_attr . '" loading="lazy" decoding="async" referrerpolicy="no-referrer">';
            $card = '<article class="wpbb-soc-feed__card">' . ($link ? '<a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $image . '</a>' : $image) . '</article>';
            $mobile_class = $mobileStack && $slide_index < $mobileFeedCount ? ' wpbb-soc-feed__mobile-item' : '';
            $slides .= '<div class="swiper-slide' . $mobile_class . '">' . $card . '</div>';
            $slide_index++;
        }
        if ($slides === '') return '';

        $slidesDesktop = max(1, min(6, absint($attributes['slidesDesktop'] ?? 4)));
        $slidesTablet = max(1, min(4, absint($attributes['slidesTablet'] ?? 2)));
        $slidesMobile = max(1, min(2, absint($attributes['slidesMobile'] ?? 1)));
        $space = max(0, min(80, absint($attributes['spaceBetween'] ?? 28)));
        $basisDesktop = 'calc((100% - ' . max(0, ($slidesDesktop - 1) * $space) . 'px) / ' . $slidesDesktop . ')';
        $basisTablet = 'calc((100% - ' . max(0, ($slidesTablet - 1) * $space) . 'px) / ' . $slidesTablet . ')';
        $basisMobile = 'calc((100% - ' . max(0, ($slidesMobile - 1) * $space) . 'px) / ' . $slidesMobile . ')';
        $slide_count = substr_count($slides, 'class="swiper-slide"');
        $loop = !empty($attributes['loop']) && $slide_count > $slidesDesktop;
        $autoplay = !empty($attributes['autoplay']);
        $navigation = !array_key_exists('navigation', $attributes) || !empty($attributes['navigation']);
        $pagination = !array_key_exists('pagination', $attributes) || !empty($attributes['pagination']);
        $centered = !array_key_exists('centered', $attributes) || !empty($attributes['centered']);
        $radius = preg_match('/^[0-9.]+(px|rem|em|%)$/', (string) ($attributes['cardRadius'] ?? '20px')) ? $attributes['cardRadius'] : '20px';
        $ratio = preg_match('/^[0-9.]+\s*\/\s*[0-9.]+$/', (string) ($attributes['imageRatio'] ?? '1 / 1')) ? $attributes['imageRatio'] : '1 / 1';

        $wrapper_classes = trim('wpbb-soc-feed wpbb-soc-feed--' . $platform . ' wpbb-swiper-block ' . $customClasses);
        $wrapper = get_block_wrapper_attributes([
            'class' => $wrapper_classes,
            'data-swiper' => '1',
            'data-slides' => (string) $slidesDesktop,
            'data-slides-tablet' => (string) $slidesTablet,
            'data-slides-mobile' => (string) $slidesMobile,
            'data-mobile-stack' => $mobileStack ? '1' : '0',
            'data-mobile-feed-count' => (string) $mobileFeedCount,
            'data-space' => (string) $space,
            'data-loop' => $loop ? '1' : '0',
            'data-rewind' => $loop ? '0' : '1',
            'data-autoplay' => $autoplay ? '1' : '0',
            'data-centered' => $centered ? '1' : '0',
            'data-initial-slide' => '0',
            'style' => '--wpbb-soc-feed-radius:' . esc_attr($radius) . ';--wpbb-soc-feed-ratio:' . esc_attr($ratio) . ';--wpbb-soc-feed-gap:' . esc_attr($space) . 'px;--wpbb-soc-feed-slide-basis:' . esc_attr($basisDesktop) . ';--wpbb-soc-feed-slide-basis-tablet:' . esc_attr($basisTablet) . ';--wpbb-soc-feed-slide-basis-mobile:' . esc_attr($basisMobile) . ';',
        ]);

        $nav = $navigation ? '<button class="swiper-button-prev" type="button" aria-label="Previous"></button><button class="swiper-button-next" type="button" aria-label="Next"></button>' : '';
        $pager = $pagination ? '<div class="swiper-pagination"></div>' : '';
        $heading = $title ? '<' . $headingTag . ' class="wpbb-soc-feed__title">' . $title . '</' . $headingTag . '>' : '';
        $cta = ($buttonText && $buttonUrl) ? '<p class="wpbb-soc-feed__cta"><a class="btn btn-primary" href="' . esc_url($buttonUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html($buttonText) . '</a></p>' : '';
        return '<section ' . $wrapper . '>' . $heading . '<div class="swiper"><div class="swiper-wrapper">' . $slides . '</div>' . $nav . $pager . '</div>' . $cta . '</section>';
    }

    private function wpbb_social_feed_source_value($attributes, $platform) {
        $source = isset($attributes[$platform . 'Source']) ? trim((string) $attributes[$platform . 'Source']) : '';
        if ($source !== '') return $source;
        $acfField = isset($attributes[$platform . 'AcfField']) ? trim((string) $attributes[$platform . 'AcfField']) : '';
        if ($acfField !== '' && function_exists('get_field')) {
            $value = get_field($acfField);
            if (is_string($value) && trim($value) !== '') return trim($value);
        }
        return '';
    }

    private function wpbb_social_feed_embed_markup($platform, $method, $source) {
        $source = trim((string) $source);
        if ($source === '') return '';

        if ($method === 'shortcode') {
            return do_shortcode($source);
        }

        if ($method === 'html') {
            return wp_kses_post($source);
        }

        $embed = wp_oembed_get($source, ['width' => 1280]);
        if ($embed) return $embed;

        if ($method === 'link') {
            return '<p><a href="' . esc_url($source) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Open feed', 'wp-bbuilder') . '</a></p>';
        }

        return '';
    }

    public function render_social_feeds_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? __('Social feeds', 'wp-bbuilder'));
        $intro = wp_kses_post($attributes['intro'] ?? '');
        $layout = in_array(($attributes['layout'] ?? 'grid'), ['grid','stack'], true) ? $attributes['layout'] : 'grid';
        $columns = max(1, min(4, intval($attributes['columns'] ?? 2)));
        $cardStyle = in_array(($attributes['cardStyle'] ?? 'card'), ['card','plain'], true) ? $attributes['cardStyle'] : 'card';
        $showIcons = !empty($attributes['showPlatformIcons']);
        $openNew = !empty($attributes['openLinksInNewTab']);
        $platforms = [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'tiktok' => 'TikTok',
            'x' => 'X',
            'youtube' => 'YouTube',
        ];

        $cards = '';
        foreach ($platforms as $slug => $label) {
            if (empty($attributes[$slug . 'Enabled'])) continue;
            $method = isset($attributes[$slug . 'Method']) ? (string) $attributes[$slug . 'Method'] : 'shortcode';
            if (!in_array($method, ['shortcode','embed','html','link'], true)) $method = 'shortcode';
            $source = $this->wpbb_social_feed_source_value($attributes, $slug);
            if ($source === '') continue;
            $embed = $this->wpbb_social_feed_embed_markup($slug, $method, $source);
            if ($embed === '') continue;
            $icon = $showIcons ? '<span class="wpbb-social-feeds__icon" aria-hidden="true">' . $this->wpbb_svg_icon($slug) . '</span>' : '';
            $meta = '';
            if ($method === 'link' || $method === 'embed' || $method === 'html') {
                $meta = '<div class="wpbb-social-feeds__meta"><a href="' . esc_url($source) . '"' . ($openNew ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . ($method === 'link' ? esc_html($source) : esc_html__('Source', 'wp-bbuilder')) . '</a></div>';
            }
            $cards .= '<article class="wpbb-social-feeds__item ' . ($cardStyle === 'card' ? 'card shadow-sm' : '') . '"><div class="wpbb-social-feeds__header">' . $icon . '<div><h3 class="wpbb-social-feeds__title">' . esc_html($label) . '</h3></div></div><div class="wpbb-social-feeds__body">' . $embed . '</div>' . $meta . '</article>';
        }

        if ($cards === '') return '';

        $wrapper_classes = ['wpbb-social-feeds', 'wpbb-social-feeds--' . sanitize_html_class($layout), 'wpbb-social-feeds--cols-' . $columns];
        if (!empty($attributes['customClasses'])) $wrapper_classes[] = sanitize_html_class($attributes['customClasses']);
        $wrapper = get_block_wrapper_attributes(['class' => implode(' ', array_filter($wrapper_classes))]);
        $css = '<style>.wpbb-social-feeds{display:grid;gap:1rem}.wpbb-social-feeds__intro{margin:0 0 .5rem}.wpbb-social-feeds__grid{display:grid;gap:1rem}.wpbb-social-feeds--stack .wpbb-social-feeds__grid{grid-template-columns:1fr}.wpbb-social-feeds--grid.wpbb-social-feeds--cols-1 .wpbb-social-feeds__grid{grid-template-columns:1fr}.wpbb-social-feeds--grid.wpbb-social-feeds--cols-2 .wpbb-social-feeds__grid{grid-template-columns:repeat(2,minmax(0,1fr))}.wpbb-social-feeds--grid.wpbb-social-feeds--cols-3 .wpbb-social-feeds__grid{grid-template-columns:repeat(3,minmax(0,1fr))}.wpbb-social-feeds--grid.wpbb-social-feeds--cols-4 .wpbb-social-feeds__grid{grid-template-columns:repeat(4,minmax(0,1fr))}@media(max-width:991px){.wpbb-social-feeds__grid{grid-template-columns:1fr 1fr !important}}@media(max-width:640px){.wpbb-social-feeds__grid{grid-template-columns:1fr !important}}.wpbb-social-feeds__item{padding:1rem;border-radius:1rem;background:#fff}.wpbb-social-feeds__header{display:flex;align-items:center;gap:.65rem;margin-bottom:.75rem}.wpbb-social-feeds__title{margin:0;font-size:1rem}.wpbb-social-feeds__icon{display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:999px;background:#111827;color:#fff}.wpbb-social-feeds__body iframe{width:100%;max-width:100%}.wpbb-social-feeds__body .instagram-media,.wpbb-social-feeds__body .twitter-tweet,.wpbb-social-feeds__body .tiktok-embed,.wpbb-social-feeds__body iframe{margin-left:auto !important;margin-right:auto !important}.wpbb-social-feeds__meta{margin-top:.75rem;font-size:.875rem;opacity:.8}</style>';
        return $css . '<section ' . $wrapper . '>' . ($title ? '<div class="wpbb-social-feeds__intro-wrap"><h2 class="wpbb-social-feeds__block-title">' . $title . '</h2>' . ($intro ? '<div class="wpbb-social-feeds__intro">' . $intro . '</div>' : '') . '</div>' : '') . '<div class="wpbb-social-feeds__grid">' . $cards . '</div></section>';
    }

    public function render_social_share_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? __('Share', 'wp-bbuilder'));
        $titleTag = in_array(($attributes['titleTag'] ?? 'span'), ['h1','h2','h3','h4','h5','h6','div','p','span'], true) ? ($attributes['titleTag'] ?: 'span') : 'span';
        $style = $attributes['iconStyle'] ?? 'icons';
        $sizeMap = ['sm' => '34px', 'md' => '42px', 'lg' => '50px'];
        $iconSize = $sizeMap[$attributes['iconSize'] ?? 'md'] ?? '42px';
        $shape = $attributes['iconShape'] ?? 'rounded';
        $shapeRadius = $shape === 'circle' ? '999px' : ($shape === 'square' ? '0' : '12px');
        $shareUrl = rawurlencode(get_permalink());
        $shareTitle = rawurlencode(get_the_title());
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-soc-share wpbb-soc-style-' . sanitize_html_class($style)]);
        $items = [
            'facebook' => ['url' => 'https://www.facebook.com/sharer/sharer.php?u=' . $shareUrl, 'label' => 'Facebook'],
            'x' => ['url' => 'https://twitter.com/intent/tweet?url=' . $shareUrl . '&text=' . $shareTitle, 'label' => 'X'],
            'linkedin' => ['url' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $shareUrl, 'label' => 'LinkedIn'],
            'whatsapp' => ['url' => 'https://wa.me/?text=' . $shareTitle . '%20' . $shareUrl, 'label' => 'WhatsApp'],
            'email' => ['url' => 'mailto:?subject=' . $shareTitle . '&body=' . $shareUrl, 'label' => 'Email'],
        ];
        $links = '';
        foreach ($items as $key => $data) {
            $bg = !empty($attributes['iconBgColor']) ? (string)$attributes['iconBgColor'] : '#0f172a';
            $fg = !empty($attributes['iconColor']) ? (string)$attributes['iconColor'] : '#ffffff';
            $icon = $this->wpbb_svg_icon($key);
            if ($style === 'buttons') {
                $links .= '<a href="' . esc_url($data['url']) . '" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" target="_blank" rel="noopener noreferrer"><span class="wpbb-social-icon wpbb-social-icon--inline" style="width:26px;height:26px;border-radius:' . esc_attr($shapeRadius) . ';background:' . esc_attr($bg) . ';color:' . esc_attr($fg) . ';">' . $icon . '</span>' . esc_html($data['label']) . '</a>';
            } else {
                $links .= '<a href="' . esc_url($data['url']) . '" class="wpbb-share-link wpbb-social-icon" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr($data['label']) . '" style="width:' . esc_attr($iconSize) . ';height:' . esc_attr($iconSize) . ';border-radius:' . esc_attr($shapeRadius) . ';background:' . esc_attr($bg) . ';color:' . esc_attr($fg) . ';">' . $icon . '</a>';
            }
        }
        return '<div ' . $wrapper . '>' . ($title ? '<' . $titleTag . ' class="wpbb-share-title">' . $title . '</' . $titleTag . '>' : '') . '<div class="wpbb-share-links">' . $links . '</div></div>';
    }



    public function render_feature_list_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Features');
        $items = wpbb_parse_fields_json($attributes['itemsJson'] ?? '');
        if (!$items) $items = [['title'=>'Fast setup','text'=>'Launch quickly with reusable UI.'],['title'=>'Clear messaging','text'=>'Highlight your strongest value points.']];
        $icon_color = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['iconColor'] ?? '#2563eb'));
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-feature-list']);
        $html = '<div ' . $wrapper . '>';
        if ($title) $html .= '<h3>' . $title . '</h3>';
        $html .= '<div class="wpbb-feature-list__grid">';
        foreach ($items as $item) {
            $html .= '<div class="wpbb-feature-item"><span class="wpbb-feature-item__icon" style="color:' . esc_attr($icon_color) . '">✓</span><div><div class="wpbb-feature-item__title">' . esc_html($item['title'] ?? '') . '</div><div class="wpbb-feature-item__text">' . esc_html($item['text'] ?? '') . '</div></div></div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function render_timeline_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Timeline');
        $layout = sanitize_html_class($attributes['layout'] ?? 'vertical');
        $items = wpbb_parse_fields_json($attributes['itemsJson'] ?? '');
        if (!$items) $items = [['date'=>'2024','title'=>'Discovery','text'=>'Research and planning.'],['date'=>'2025','title'=>'Launch','text'=>'Implementation and launch.']];
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-timeline wpbb-timeline--' . $layout]);
        $html = '<div ' . $wrapper . '>';
        if ($title) $html .= '<h3>' . $title . '</h3>';
        $html .= '<div class="wpbb-timeline__items">';
        foreach ($items as $item) {
            $html .= '<div class="wpbb-timeline__item"><div class="wpbb-timeline__dot"></div><div class="wpbb-timeline__content"><div class="wpbb-timeline__date">' . esc_html($item['date'] ?? '') . '</div><div class="wpbb-timeline__title">' . esc_html($item['title'] ?? '') . '</div><div class="wpbb-timeline__text">' . esc_html($item['text'] ?? '') . '</div></div></div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function render_custom_embed_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Embed');
        $embed_url = esc_url($attributes['embedUrl'] ?? '');
        $embed_html = (string)($attributes['embedHtml'] ?? '');
        $height = preg_replace('/[^0-9.%a-zA-Z-]/', '', (string)($attributes['height'] ?? '420px'));
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-custom-embed']);
        $body = '';
        if ($embed_html !== '') {
            $body = '<div class="wpbb-custom-embed__html">' . wp_kses($embed_html, ['iframe' => ['src'=>true,'width'=>true,'height'=>true,'style'=>true,'frameborder'=>true,'allow'=>true,'allowfullscreen'=>true,'loading'=>true,'referrerpolicy'=>true], 'div'=>['class'=>true,'style'=>true]]) . '</div>';
        } elseif ($embed_url !== '') {
            $body = '<iframe class="wpbb-custom-embed__frame" src="' . $embed_url . '" style="min-height:' . esc_attr($height) . '" loading="lazy"></iframe>';
        } else {
            $body = '<div class="wpbb-custom-embed__placeholder">Add embed URL or HTML.</div>';
        }
        return '<div ' . $wrapper . '>' . ($title ? '<h3>' . $title . '</h3>' : '') . $body . '</div>';
    }

    public function render_ai_content_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'AI Content');
        $provider = esc_html($attributes['provider'] ?? 'simple-ai');
        $generated = wp_kses_post($attributes['generatedText'] ?? '');
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-ai-content']);
        return '<div ' . $wrapper . '>' . ($title ? '<h3>' . $title . '</h3>' : '') . '<div class="wpbb-ai-content__meta">Mode: ' . $provider . '</div><div class="wpbb-ai-content__help">Use keywords or a short description in the editor, then click Generate text now.</div><div class="wpbb-ai-content__body">' . ($generated ?: 'No generated content yet.') . '</div></div>';
    }

    public function render_login_register_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Account Access');
        $show_register = !empty($attributes['showRegister']);
        $variant = sanitize_html_class($attributes['styleVariant'] ?? 'split');
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-auth wpbb-auth--' . $variant]);

        $login_form = wp_login_form([
            'echo' => false,
            'remember' => true,
            'label_username' => __('Email or Username', 'wp-bbuilder'),
            'label_password' => __('Password', 'wp-bbuilder'),
            'label_log_in'   => __('Log In', 'wp-bbuilder')
        ]);

        $register_html = '';
        if ($show_register) {
            if (get_option('users_can_register')) {
                $register_html = '<div class="wpbb-auth-card p-3"><h4>' . esc_html__('Register', 'wp-bbuilder') . '</h4><p>' . esc_html__('Create an account on the default WordPress registration page.', 'wp-bbuilder') . '</p><a class="btn btn-primary" href="' . esc_url(wp_registration_url()) . '">' . esc_html__('Register', 'wp-bbuilder') . '</a></div>';
            } else {
                $register_html = '<div class="wpbb-auth-card p-3"><h4>' . esc_html__('Register', 'wp-bbuilder') . '</h4><p>' . esc_html__('User registration is currently disabled.', 'wp-bbuilder') . '</p></div>';
            }
        }

        return '<div ' . $wrapper . '>' .
            ($title ? '<h3>' . $title . '</h3>' : '') .
            '<div class="row g-3">' .
                '<div class="' . esc_attr($show_register ? 'col-md-6' : 'col-12') . '">' .
                    '<div class="wpbb-auth-card p-3"><h4>' . esc_html__('Login', 'wp-bbuilder') . '</h4>' . $login_form . '</div>' .
                '</div>' .
                ($show_register ? '<div class="col-md-6">' . $register_html . '</div>' : '') .
            '</div>' .
        '</div>';
    }

    public function render_button_block($attributes, $content, $block) {
        $text = !empty($attributes['text']) ? wp_kses_post($attributes['text']) : 'Button';
        $url = !empty($attributes['url']) ? esc_url($attributes['url']) : '#';
        $variant = sanitize_html_class($attributes['variant'] ?? 'primary');
        $size = sanitize_html_class($attributes['size'] ?? '');
        $btn_class = trim((string)($attributes['btnClass'] ?? ''));
        if ($btn_class === '') {
            $btn_class = 'btn btn-' . $variant . ($size ? ' btn-' . $size : '') . (!empty($attributes['fullWidth']) ? ' w-100' : '');
        }
        $wrap_class = 'wpbb-button-wrap';
        if (!empty($attributes['fullWidth'])) $wrap_class .= ' w-100';
        $align = sanitize_html_class($attributes['align'] ?? '');
        if ($align) $wrap_class .= ' text-' . $align;

        $style = '';
        if (!empty($attributes['backgroundColor'])) {
            $bg = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)$attributes['backgroundColor']);
            $style .= 'background:' . $bg . ';border-color:' . $bg . ';';
        }
        if (!empty($attributes['textColor'])) {
            $style .= 'color:' . preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)$attributes['textColor']) . ';';
        }
        if (!empty($attributes['borderRadius'])) {
            $style .= 'border-radius:' . preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)$attributes['borderRadius']) . ';';
        }

        $wrapper = get_block_wrapper_attributes(['class' => $wrap_class]);
        return '<div ' . $wrapper . '><a class="' . esc_attr($btn_class) . '" href="' . $url . '" style="' . esc_attr($style) . '">' . $text . '</a></div>';
    }

    public function render_accordion_block($attributes, $content, $block) {
        WPBBuilder_Bootstrap::needs(['collapse']);
        WPBBuilder_Bootstrap::enqueue_js_if_needed();
        $uid = !empty($attributes['anchor']) ? sanitize_html_class((string)$attributes['anchor']) : sanitize_html_class('wpbb-accordion-' . wp_unique_id());
        $flush = !empty($attributes['flush']) ? ' accordion-flush' : '';
        $shadow = !empty($attributes['boxShadowClass']) ? ' ' . sanitize_html_class((string)$attributes['boxShadowClass']) : '';
        $style = '';
        if (!empty($attributes['backgroundColor'])) $style .= 'background:' . preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)$attributes['backgroundColor']) . ';';
        if (!empty($attributes['borderColor'])) $style .= 'border-color:' . preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)$attributes['borderColor']) . ';';
        $wrapper = get_block_wrapper_attributes(['class' => 'accordion wpbb-accordion' . $flush . $shadow, 'id' => $uid, 'style' => $style]);
        return '<div ' . $wrapper . '>' . $content . '</div>';
    }

    public function render_accordion_item_block($attributes, $content, $block) {
        $title = esc_html($attributes['title'] ?? 'Accordion Item');
        $item_id = sanitize_html_class('wpbb-acc-item-' . wp_unique_id());
        $head_id = $item_id . '-head';
        $collapse_id = $item_id . '-collapse';
        return '<div class="accordion-item wpbb-accordion-item">'
            . '<h2 class="accordion-header" id="' . esc_attr($head_id) . '">'
            . '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' . esc_attr($collapse_id) . '" aria-expanded="false" aria-controls="' . esc_attr($collapse_id) . '">'
            . $title
            . '</button></h2>'
            . '<div id="' . esc_attr($collapse_id) . '" class="accordion-collapse collapse">'
            . '<div class="accordion-body">' . $content . '</div></div></div>';
    }




    private function wpbb_normalize_post_type($value, $fallback = 'post') {
        $value = sanitize_key((string) $value);
        return $value !== '' ? $value : $fallback;
    }

    private function wpbb_resolve_existing_post_type($preferred, $fallbacks = []) {
        $candidates = array_merge([(string) $preferred], $fallbacks);
        foreach ($candidates as $candidate) {
            $candidate = sanitize_key((string) $candidate);
            if ($candidate !== '' && post_type_exists($candidate)) return $candidate;
        }
        return 'post';
    }

    private function wpbb_render_post_card($post_id, $item_class = '') {
        $item_class = trim($item_class ?: 'col-md-4');
        $thumb = get_the_post_thumbnail($post_id, 'medium', ['class' => 'card-img-top']);
        $permalink = get_permalink($post_id);
        $title = get_the_title($post_id);
        $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($post_id) ?: get_post_field('post_content', $post_id)), 22);
        return '<article class="' . esc_attr($item_class) . ' wpbb-load-more-item"><div class="card h-100">' .
            ($thumb ? '<a href="' . esc_url($permalink) . '">' . $thumb . '</a>' : '') .
            '<div class="card-body"><h3 class="h5 card-title"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3><p class="card-text">' . esc_html($excerpt) . '</p></div></div></article>';
    }

    public function ajax_load_more() {
        $post_type = $this->wpbb_resolve_existing_post_type($_POST['postType'] ?? 'post', ['post']);
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = max(1, intval($_POST['perPage'] ?? 3));
        $category = sanitize_text_field($_POST['category'] ?? '');
        $query_args = ['post_type' => $post_type, 'post_status' => 'publish', 'paged' => $page, 'posts_per_page' => $per_page];
        if ($category !== '' && taxonomy_exists('category')) {
            $query_args['category_name'] = $category;
        }
        $query = new WP_Query($query_args);
        $item_class = sanitize_text_field($_POST['itemClass'] ?? 'col-md-4');
        $html = '';
        if ($query->have_posts()) {
            while ($query->have_posts()) { $query->the_post(); $html .= $this->wpbb_render_post_card(get_the_ID(), $item_class); }
            wp_reset_postdata();
        }
        wp_send_json_success(['html' => $html, 'max' => intval($query->max_num_pages)]);
    }

    public function ajax_blog_filter() {
        $post_type = $this->wpbb_resolve_existing_post_type($_POST['postType'] ?? 'post', ['post']);
        $taxonomy = sanitize_key($_POST['taxonomy'] ?? 'category');
        $per_page = max(1, intval($_POST['perPage'] ?? 6));
        $search = sanitize_text_field($_POST['search'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $year = intval($_POST['year'] ?? 0);
        $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
        $args = ['post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $per_page, 's' => $search];
        if ($category !== '' && taxonomy_exists($taxonomy)) {
            $args['tax_query'] = [[ 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $category ]];
        }
        if ($year > 0) {
            $args['date_query'] = [[ 'year' => $year ]];
        }
        switch ($sort) {
            case 'date_asc': $args['orderby'] = 'date'; $args['order'] = 'ASC'; break;
            case 'alpha_asc': $args['orderby'] = 'title'; $args['order'] = 'ASC'; break;
            case 'alpha_desc': $args['orderby'] = 'title'; $args['order'] = 'DESC'; break;
            default: $args['orderby'] = 'date'; $args['order'] = 'DESC';
        }
        $query = new WP_Query($args);
        $html = '<div class="row g-4">';
        if ($query->have_posts()) {
            while ($query->have_posts()) { $query->the_post(); $html .= $this->wpbb_render_post_card(get_the_ID(), 'col-md-6 col-lg-4'); }
            wp_reset_postdata();
        } else {
            $html .= '<div class="col-12"><p>No posts found.</p></div>';
        }
        $html .= '</div>';
        wp_send_json_success(['html' => $html]);
    }

    public function render_load_more_block($attributes, $content, $block) {
        wp_enqueue_script('wpbb-content-filters');
        wp_enqueue_style('wpbb-shared');
        $post_type = $this->wpbb_resolve_existing_post_type($attributes['queryPostType'] ?? 'post', ['post']);
        $visible = max(1, intval($attributes['visibleItems'] ?? 6));
        $load = max(1, intval($attributes['loadItems'] ?? 3));
        $parent_class = trim((string)($attributes['parentClass'] ?? 'row'));
        $item_class = trim((string)($attributes['itemClass'] ?? 'col-md-4'));
        $button_class = trim((string)($attributes['buttonClass'] ?? 'btn btn-primary'));
        $button_text_raw = trim((string)($attributes['buttonText'] ?? 'Load more'));
        $button_text = esc_html($button_text_raw !== '' ? $button_text_raw : 'Load more');
        $button_color = trim((string)($attributes['buttonColor'] ?? ''));
        $category = sanitize_title($attributes['queryCategory'] ?? '');
        $style = $button_color !== '' ? 'background:' . esc_attr($button_color) . ';border-color:' . esc_attr($button_color) . ';color:#ffffff;' : 'background:#2563eb;border-color:#2563eb;color:#ffffff;';
        $query_args = ['post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $visible, 'paged' => 1];
        if ($category !== '' && taxonomy_exists('category')) {
            $query_args['category_name'] = $category;
        }
        $query = new WP_Query($query_args);
        $html = '<div ' . get_block_wrapper_attributes(['class' => 'wpbb-load-more']) . '><div class="' . esc_attr($parent_class !== '' ? $parent_class : 'row') . '" data-wpbb-load-more-results>';
        if ($query->have_posts()) { while ($query->have_posts()) { $query->the_post(); $html .= $this->wpbb_render_post_card(get_the_ID(), $item_class); } wp_reset_postdata(); }
        $html .= '</div>';
        if (intval($query->found_posts) > $visible) {
            $html .= '<div class="text-center mt-4 wpbb-load-more__actions"><button type="button" class="' . esc_attr($button_class) . '" style="' . esc_attr($style) . '" data-wpbb-load-more-btn data-post-type="' . esc_attr($post_type) . '" data-category="' . esc_attr($category) . '" data-page="1" data-per-page="' . esc_attr($load) . '" data-item-class="' . esc_attr($item_class) . '" data-max="' . esc_attr($query->max_num_pages) . '">' . $button_text . '</button></div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function render_contact_links_block($attributes, $content, $block) {
        $email = sanitize_email($attributes['email'] ?? '');
        $phone = sanitize_text_field($attributes['phone'] ?? '');
        $icon_color = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['iconColor'] ?? ''));
        $link_color = preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', (string)($attributes['linkColor'] ?? ''));
        $layout = trim((string)($attributes['layoutClass'] ?? 'd-flex flex-column gap-2'));
        $style = $link_color ? 'color:' . $link_color . ';' : '';
        $icon_style = $icon_color ? 'style="color:' . esc_attr($icon_color) . '"' : '';
        $html = '<div ' . get_block_wrapper_attributes(['class' => 'wpbb-contact-links ' . $layout]) . '>';
        if ($phone !== '') {
            $html .= '<a class="wpbb-contact-links__item" href="tel:' . esc_attr(preg_replace('/[^0-9\+]/', '', $phone)) . '" style="' . esc_attr($style) . '"><span class="wpbb-contact-links__icon" ' . $icon_style . '>' . $this->wpbb_svg_icon($attributes['phoneIcon'] ?? 'whatsapp') . '</span><span>' . esc_html($phone) . '</span></a>';
        }
        if ($email !== '') {
            $html .= '<a class="wpbb-contact-links__item" href="mailto:' . esc_attr($email) . '" style="' . esc_attr($style) . '"><span class="wpbb-contact-links__icon" ' . $icon_style . '>' . $this->wpbb_svg_icon($attributes['emailIcon'] ?? 'email') . '</span><span>' . esc_html($email) . '</span></a>';
        }
        $html .= '</div>';
        return $html;
    }

    public function render_events_block($attributes, $content, $block) {
        $post_type = $this->wpbb_resolve_existing_post_type($attributes['postType'] ?? 'event', ['event','events','calendar']);
        $taxonomy = sanitize_key($attributes['taxonomy'] ?? 'event_category');
        $posts_to_show = max(1, intval($attributes['postsToShow'] ?? 6));
        $today = current_time('Ymd');
        $args = ['post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $posts_to_show, 'orderby' => 'meta_value', 'meta_key' => 'event_date', 'order' => 'ASC'];
        $query = new WP_Query($args);
        $terms = taxonomy_exists($taxonomy) ? get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]) : [];
        $html = '<div ' . get_block_wrapper_attributes(['class' => 'wpbb-events']) . '>';
        $html .= '<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><h3 class="mb-0">' . esc_html($attributes['title'] ?? 'Events') . '</h3>';
        if (!is_wp_error($terms) && !empty($terms)) { $html .= '<div class="wpbb-events__filters">'; foreach ($terms as $term) { $html .= '<a class="btn btn-outline-secondary btn-sm me-2 mb-2" href="' . esc_url(add_query_arg('event_category', $term->slug)) . '">' . esc_html($term->name) . '</a>'; } $html .= '</div>'; }
        $html .= '</div>';
        if (!empty($attributes['showCalendar'])) {
            $html .= '<div class="wpbb-events__calendar card mb-4"><div class="card-body"><div class="wpbb-events__calendar-grid">';
            for ($d = 1; $d <= 31; $d++) { $html .= '<span class="wpbb-events__calendar-day' . (intval(wp_date('j')) === $d ? ' is-today' : '') . '">' . $d . '</span>'; }
            $html .= '</div></div></div>';
        }
        $html .= '<div class="row g-4">';
        if ($query->have_posts()) {
            while ($query->have_posts()) { $query->the_post(); $event_date = get_post_meta(get_the_ID(), 'event_date', true); $display_date = $event_date ? date_i18n(get_option('date_format'), strtotime($event_date)) : get_the_date('', get_the_ID()); $html .= '<article class="col-md-6 col-lg-4"><div class="card h-100"><div class="card-body"><div class="text-muted small mb-2">' . esc_html($display_date) . '</div><h3 class="h5"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3><p>' . esc_html(wp_trim_words(wp_strip_all_tags(get_the_excerpt() ?: get_the_content()), 20)) . '</p></div></div></article>'; }
            wp_reset_postdata();
        } else {
            $html .= '<div class="col-12"><p>No events found.</p></div>';
        }
        $html .= '</div></div>';
        return $html;
    }

    public function render_testimonials_block($attributes, $content, $block) {
        wp_enqueue_style('wpbb-swiper'); wp_enqueue_script('wpbb-swiper'); wp_enqueue_script('wpbb-swiper-init');
        $post_type = $this->wpbb_resolve_existing_post_type($attributes['postType'] ?? 'testimonial', ['testimonial','testimonials']);
        $posts_to_show = max(1, intval($attributes['postsToShow'] ?? 9));
        $query = new WP_Query(['post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $posts_to_show]);
        $wrapper = get_block_wrapper_attributes(['class' => 'wpbb-testimonials', 'data-swiper' => '1', 'data-slides' => (string) intval($attributes['slidesDesktop'] ?? 3), 'data-slides-tablet' => (string) intval($attributes['slidesTablet'] ?? 2), 'data-slides-mobile' => (string) intval($attributes['slidesMobile'] ?? 1), 'data-space' => '24']);
        $html = '<div ' . $wrapper . '><h3 class="mb-4">' . esc_html($attributes['title'] ?? 'Testimonials') . '</h3><div class="swiper"><div class="swiper-wrapper">';
        if ($query->have_posts()) {
            while ($query->have_posts()) { $query->the_post(); $role = get_post_meta(get_the_ID(), 'position', true) ?: get_post_meta(get_the_ID(), 'role', true); $html .= '<div class="swiper-slide"><div class="card h-100"><div class="card-body"><blockquote class="mb-3">“' . esc_html(wp_trim_words(wp_strip_all_tags(get_the_content()), 40)) . '”</blockquote><div class="fw-semibold">' . esc_html(get_the_title()) . '</div>' . ($role ? '<div class="text-muted small">' . esc_html($role) . '</div>' : '') . '</div></div></div>'; }
            wp_reset_postdata();
        }
        $html .= '</div>' . (!empty($attributes['showPagination']) ? '<div class="swiper-pagination"></div>' : '') . (!empty($attributes['showNavigation']) ? '<div class="swiper-button-prev"></div><div class="swiper-button-next"></div>' : '') . '</div></div>';
        return $html;
    }

    public function render_blog_filter_block($attributes, $content, $block) {
        wp_enqueue_script('wpbb-content-filters');
        $post_type = $this->wpbb_resolve_existing_post_type($attributes['postType'] ?? 'post', ['post']);
        $taxonomy = sanitize_key($attributes['taxonomy'] ?? 'category');
        $posts_to_show = max(1, intval($attributes['postsToShow'] ?? 6));
        $terms = taxonomy_exists($taxonomy) ? get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]) : [];
        $years = get_posts(['post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
        $year_values = [];
        foreach ($years as $id) { $year_values[] = get_the_date('Y', $id); }
        $year_values = array_values(array_unique(array_filter($year_values)));
        rsort($year_values);
        $query = new WP_Query(['post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => $posts_to_show]);
        $html = '<div ' . get_block_wrapper_attributes(['class' => 'wpbb-blog-filter']) . '><div class="d-flex flex-wrap gap-3 align-items-end mb-4">';
        $html .= '<div><label class="form-label">Search</label><input type="search" class="form-control" data-wpbb-blog-search placeholder="Search posts"></div>';
        $html .= '<div><label class="form-label">Category</label><select class="form-select" data-wpbb-blog-category><option value="">All</option>';
        if (!is_wp_error($terms)) foreach ($terms as $term) { $html .= '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>'; }
        $html .= '</select></div>';
        $html .= '<div><label class="form-label">Year</label><select class="form-select" data-wpbb-blog-year><option value="">All</option>';
        foreach ($year_values as $year) { $html .= '<option value="' . esc_attr($year) . '">' . esc_html($year) . '</option>'; }
        $html .= '</select></div>';
        $html .= '<div><label class="form-label">Sort</label><select class="form-select" data-wpbb-blog-sort><option value="date_desc">Newest</option><option value="date_asc">Oldest</option><option value="alpha_asc">A-Z</option><option value="alpha_desc">Z-A</option></select></div>';
        $button_color = trim((string)($attributes['buttonColor'] ?? '#2563eb'));
        $button_style = $button_color !== '' ? ' style="background:' . esc_attr(preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', $button_color)) . ';border-color:' . esc_attr(preg_replace('/[^#(),.% 0-9a-zA-Z-]/', '', $button_color)) . ';"' : '';
        $html .= '<div><button type="button" class="btn btn-primary" data-wpbb-blog-submit' . $button_style . '>' . esc_html($attributes['buttonText'] ?? 'Filter') . '</button></div></div>';
        $html .= '<div data-wpbb-blog-results data-post-type="' . esc_attr($post_type) . '" data-taxonomy="' . esc_attr($taxonomy) . '" data-per-page="' . esc_attr($posts_to_show) . '"><div class="row g-4">';
        if ($query->have_posts()) { while ($query->have_posts()) { $query->the_post(); $html .= $this->wpbb_render_post_card(get_the_ID(), 'col-md-6 col-lg-4'); } wp_reset_postdata(); } else { $html .= '<div class="col-12"><p>No posts found.</p></div>'; }
        $html .= '</div></div></div>';
        return $html;
    }


    public function register_rest_routes() {
        register_rest_route('wpbb/v1', '/varda-dienas', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_get_varda_dienas'],
            'permission_callback' => '__return_true',
            'args' => [
                'date' => [
                    'description' => __('Date in YYYY-MM-DD or MM-DD format.', 'wp-bbuilder'),
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ]);

        register_rest_route('wpbb/v1', '/varda-dienas/today', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'rest_get_varda_dienas_today'],
            'permission_callback' => '__return_true',
        ]);
    }

    private function load_varda_dienas_data() {
        $file = WPBB_PLUGIN_DIR . 'assets/json/varda-dienas.json';
        if (!file_exists($file)) {
            return new WP_Error('wpbb_varda_dienas_missing', __('Vārda dienu data file not found.', 'wp-bbuilder'), ['status' => 500]);
        }

        $json = file_get_contents($file);
        $data = json_decode((string) $json, true);

        if (!is_array($data)) {
            return new WP_Error('wpbb_varda_dienas_invalid', __('Invalid vārda dienu data file.', 'wp-bbuilder'), ['status' => 500]);
        }

        return $data;
    }

    private function normalize_varda_dienas_key($raw_date = '') {
        $raw_date = trim((string) $raw_date);

        if ($raw_date === '') {
            $now = new DateTimeImmutable('now', wp_timezone());
            return $now->format('m-d');
        }

        if (preg_match('/^(\d{2})-(\d{2})$/', $raw_date, $m)) {
            return $m[1] . '-' . $m[2];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw_date, $m)) {
            return $m[2] . '-' . $m[3];
        }

        return new WP_Error('wpbb_varda_dienas_bad_date', __('Invalid date format. Use YYYY-MM-DD or MM-DD.', 'wp-bbuilder'), ['status' => 400]);
    }

    public function rest_get_varda_dienas(WP_REST_Request $request) {
        $data = $this->load_varda_dienas_data();
        if (is_wp_error($data)) {
            return $data;
        }

        $requested = $request->get_param('date');
        $key = $this->normalize_varda_dienas_key($requested);
        if (is_wp_error($key)) {
            return $key;
        }

        $now = new DateTimeImmutable('now', wp_timezone());
        $today_key = $now->format('m-d');

        return rest_ensure_response([
            'success' => true,
            'date' => $requested ? (string) $requested : $now->format('Y-m-d'),
            'key' => $key,
            'today' => $key === $today_key,
            'names' => isset($data[$key]) && is_array($data[$key]) ? array_values($data[$key]) : [],
            'count' => isset($data[$key]) && is_array($data[$key]) ? count($data[$key]) : 0,
        ]);
    }

    public function rest_get_varda_dienas_today(WP_REST_Request $request) {
        $request->set_param('date', '');
        return $this->rest_get_varda_dienas($request);
    }



    private function wpbb_booking_services($raw) {
        $services = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (empty($parts[0])) continue;
            $services[] = [
                'label' => sanitize_text_field($parts[0]),
                'duration' => max(5, absint($parts[1] ?? 30)),
                'price' => isset($parts[2]) ? (float) $parts[2] : 0,
            ];
        }
        return $services;
    }

    private function wpbb_booking_slots($start, $end, $minutes) {
        $minutes = max(5, min(240, absint($minutes)));
        $start_ts = strtotime('1970-01-01 ' . $start . ':00');
        $end_ts = strtotime('1970-01-01 ' . $end . ':00');
        if (!$start_ts || !$end_ts || $end_ts <= $start_ts) return [];
        $slots = [];
        for ($time = $start_ts; $time < $end_ts; $time += $minutes * 60) {
            $slots[] = date('H:i', $time);
        }
        return $slots;
    }

    private function wpbb_get_booked_slots($provider_id = 0, $date = '') {
        $posts = get_posts([
            'post_type' => 'wpbb_booking',
            'post_status' => ['publish','pending','private'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array_values(array_filter([
                $provider_id ? ['key'=>'provider_id','value'=>absint($provider_id),'compare'=>'=','type'=>'NUMERIC'] : null,
                $date ? ['key'=>'booking_date','value'=>sanitize_text_field($date),'compare'=>'='] : null,
            ])),
        ]);
        $slots = [];
        foreach ($posts as $post_id) {
            if (get_post_meta($post_id, 'booking_status', true) === 'cancelled') continue;
            $slot = get_post_meta($post_id, 'booking_time', true);
            $booking_date = get_post_meta($post_id, 'booking_date', true);
            $pid = absint(get_post_meta($post_id, 'provider_id', true));
            if ($booking_date && $slot) $slots[] = $pid . '|' . $booking_date . '|' . $slot;
        }
        return array_values(array_unique($slots));
    }

    public function ajax_submit_booking() {
        check_ajax_referer('wpbb_booking_nonce', 'nonce');

        $config_json = isset($_POST['booking_config']) ? (string) wp_unslash($_POST['booking_config']) : '';
        $config_nonce = isset($_POST['config_nonce']) ? sanitize_text_field(wp_unslash($_POST['config_nonce'])) : '';
        $config = $config_json !== '' ? json_decode($config_json, true) : null;
        if (!is_array($config) || !$config_nonce || !wp_verify_nonce($config_nonce, 'wpbb_booking_config_' . hash('sha256', $config_json))) {
            wp_send_json_error(['message' => __('The appointment form configuration has expired. Please reload the page and try again.', 'wp-bbuilder')], 403);
        }

        $allowed_provider_ids = array_values(array_unique(array_filter(array_map('absint', (array)($config['provider_ids'] ?? [])))));
        $allowed_services = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array)($config['service_labels'] ?? [])))));
        $allowed_slots = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array)($config['slots'] ?? [])))));

        $date = sanitize_text_field(wp_unslash($_POST['date'] ?? ''));
        $time = sanitize_text_field(wp_unslash($_POST['time'] ?? ''));
        $provider_id = absint($_POST['provider_id'] ?? 0);
        $service = sanitize_text_field(wp_unslash($_POST['service'] ?? ''));
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));

        if (!$date || !$time || !$name || !$email || !is_email($email)) {
            wp_send_json_error(['message' => __('Please complete the required appointment fields.', 'wp-bbuilder')], 400);
        }
        if ($allowed_provider_ids) {
            if (!$provider_id || !in_array($provider_id, $allowed_provider_ids, true)) {
                wp_send_json_error(['message' => __('Please choose a valid provider.', 'wp-bbuilder')], 400);
            }
        } else {
            $provider_id = 0;
        }
        if ($allowed_services) {
            if ($service === '' || !in_array($service, $allowed_services, true)) {
                wp_send_json_error(['message' => __('Please choose a valid service.', 'wp-bbuilder')], 400);
            }
        } else {
            $service = '';
        }
        if (!$allowed_slots || !in_array($time, $allowed_slots, true)) {
            wp_send_json_error(['message' => __('Please choose a valid appointment time.', 'wp-bbuilder')], 400);
        }

        $date_obj = DateTimeImmutable::createFromFormat('!Y-m-d', $date, wp_timezone());
        if (!$date_obj || $date_obj->format('Y-m-d') !== $date || $date_obj < new DateTimeImmutable('today', wp_timezone())) {
            wp_send_json_error(['message' => __('Please choose a valid future date.', 'wp-bbuilder')], 400);
        }
        $slot_key = $provider_id . '|' . $date . '|' . $time;
        if (in_array($slot_key, $this->wpbb_get_booked_slots($provider_id, $date), true)) {
            wp_send_json_error(['message' => __('That appointment time has just been booked. Please choose another slot.', 'wp-bbuilder')], 409);
        }

        $provider = $provider_id ? get_post($provider_id) : null;
        if ($provider_id && (!$provider instanceof WP_Post || $provider->post_status !== 'publish')) {
            wp_send_json_error(['message' => __('Please choose a valid provider.', 'wp-bbuilder')], 400);
        }
        $provider_name = $provider instanceof WP_Post ? $provider->post_title : __('General team', 'wp-bbuilder');
        $post_id = wp_insert_post([
            'post_type' => 'wpbb_booking',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Appointment %1$s %2$s - %3$s', 'wp-bbuilder'), $date, $time, $name),
        ], true);
        if (is_wp_error($post_id)) wp_send_json_error(['message' => $post_id->get_error_message()], 500);

        foreach ([
            'booking_date'=>$date, 'booking_time'=>$time, 'provider_id'=>$provider_id, 'provider_name'=>$provider_name,
            'service'=>$service, 'client_name'=>$name, 'client_email'=>$email, 'client_phone'=>$phone,
            'client_notes'=>$notes, 'booking_status'=>'requested'
        ] as $key=>$value) update_post_meta($post_id, $key, $value);

        $admin_email = sanitize_email((string)($config['admin_email'] ?? '')) ?: sanitize_email(get_option('admin_email'));
        $subject = sprintf(__('New appointment request: %1$s %2$s', 'wp-bbuilder'), $date, $time);
        $body = sprintf("Provider: %s\nService: %s\nDate: %s\nTime: %s\nName: %s\nEmail: %s\nPhone: %s\n\n%s", $provider_name, $service, $date, $time, $name, $email, $phone, $notes);
        if ($admin_email) wp_mail($admin_email, $subject, $body);
        wp_send_json_success(['message' => __('Appointment request received. We will confirm it shortly.', 'wp-bbuilder')]);
    }

    public function render_booking_calendar_block($attributes = [], $content = '', $block = null) {
        $provider_post_type = sanitize_key($attributes['providerPostType'] ?? 'doctor');
        $providers = post_type_exists($provider_post_type) ? get_posts([
            'post_type'=>$provider_post_type, 'post_status'=>'publish', 'posts_per_page'=>100, 'orderby'=>['menu_order'=>'ASC','title'=>'ASC']
        ]) : [];
        $services = $this->wpbb_booking_services($attributes['services'] ?? '');
        $slots = $this->wpbb_booking_slots($attributes['startTime'] ?? '09:00', $attributes['endTime'] ?? '17:00', $attributes['slotMinutes'] ?? 30);
        $booked_slots = $this->wpbb_get_booked_slots();
        $booking_config = [
            'provider_ids' => array_values(array_map(static function($provider){ return absint($provider->ID); }, $providers)),
            'service_labels' => array_values(array_map(static function($service){ return (string)$service['label']; }, $services)),
            'slots' => array_values($slots),
            'admin_email' => sanitize_email($attributes['adminEmail'] ?? '') ?: sanitize_email(get_option('admin_email')),
        ];
        $booking_config_json = wp_json_encode($booking_config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $booking_config_nonce = wp_create_nonce('wpbb_booking_config_' . hash('sha256', $booking_config_json));
        $title = esc_html($attributes['title'] ?? __('Book an appointment', 'wp-bbuilder'));
        $intro = esc_html($attributes['intro'] ?? __('Choose a provider, service, date and time.', 'wp-bbuilder'));
        $success = esc_attr($attributes['successMessage'] ?? __('Thanks, your appointment request has been received.', 'wp-bbuilder'));
        $provider_label = esc_html($attributes['providerLabel'] ?? __('Provider', 'wp-bbuilder'));
        $classes = trim('wpbb-booking card border-0 ' . ($attributes['className'] ?? ''));
        wp_enqueue_script('wpbb-booking');
        ob_start(); ?>
        <div class="<?php echo esc_attr($classes); ?>" data-booked='<?php echo esc_attr(wp_json_encode($booked_slots)); ?>' data-success="<?php echo $success; ?>">
          <div class="card-body p-4 p-lg-5"><div class="row g-5 align-items-start">
            <div class="col-12 col-lg-4"><span class="wp-theme-demo-kicker"><?php esc_html_e('Appointments', 'wp-bbuilder'); ?></span><h2 class="h3 mb-3"><?php echo $title; ?></h2><p class="text-secondary mb-4"><?php echo $intro; ?></p><div class="wpbb-booking__steps"><span><b>1</b><?php echo esc_html($provider_label); ?></span><span><b>2</b><?php esc_html_e('Date & time', 'wp-bbuilder'); ?></span><span><b>3</b><?php esc_html_e('Your details', 'wp-bbuilder'); ?></span></div></div>
            <div class="col-12 col-lg-8"><form class="wpbb-booking__form" novalidate><input type="hidden" name="booking_config" value="<?php echo esc_attr($booking_config_json); ?>"><input type="hidden" name="config_nonce" value="<?php echo esc_attr($booking_config_nonce); ?>"><div class="row g-3">
              <?php if ($providers) : ?><div class="col-12 col-md-6"><label class="form-label"><?php echo $provider_label; ?> *</label><select name="provider_id" class="form-select" required><option value=""><?php printf(esc_html__('Choose %s', 'wp-bbuilder'), strtolower($provider_label)); ?></option><?php foreach($providers as $provider): ?><option value="<?php echo absint($provider->ID); ?>"><?php echo esc_html($provider->post_title); ?></option><?php endforeach; ?></select></div><?php endif; ?>
              <?php if ($services) : ?><div class="col-12 col-md-6"><label class="form-label"><?php esc_html_e('Service', 'wp-bbuilder'); ?> *</label><select name="service" class="form-select" required><option value=""><?php esc_html_e('Choose service', 'wp-bbuilder'); ?></option><?php foreach($services as $service): $label=$service['label']; if($service['duration'])$label.=' · '.$service['duration'].' min'; if($service['price'])$label.=' · '.number_format_i18n($service['price'], 0); ?><option value="<?php echo esc_attr($service['label']); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div><?php endif; ?>
              <div class="col-12 col-md-6"><label class="form-label"><?php esc_html_e('Date', 'wp-bbuilder'); ?> *</label><input type="date" name="date" class="form-control" min="<?php echo esc_attr(wp_date('Y-m-d')); ?>" required></div>
              <div class="col-12 col-md-6"><label class="form-label"><?php esc_html_e('Time', 'wp-bbuilder'); ?> *</label><select name="time" class="form-select" required><option value=""><?php esc_html_e('Choose time', 'wp-bbuilder'); ?></option><?php foreach($slots as $slot): ?><option value="<?php echo esc_attr($slot); ?>"><?php echo esc_html($slot); ?></option><?php endforeach; ?></select></div>
              <div class="col-12 col-md-6"><label class="form-label"><?php esc_html_e('Name', 'wp-bbuilder'); ?> *</label><input type="text" name="name" class="form-control" autocomplete="name" required></div>
              <div class="col-12 col-md-6"><label class="form-label"><?php esc_html_e('Email', 'wp-bbuilder'); ?> *</label><input type="email" name="email" class="form-control" autocomplete="email" required></div>
              <div class="col-12"><label class="form-label"><?php esc_html_e('Phone', 'wp-bbuilder'); ?></label><input type="tel" name="phone" class="form-control" autocomplete="tel"></div>
              <div class="col-12"><label class="form-label"><?php esc_html_e('Notes', 'wp-bbuilder'); ?></label><textarea name="notes" class="form-control" rows="3" placeholder="<?php esc_attr_e('Anything the team should know before the appointment?', 'wp-bbuilder'); ?>"></textarea></div>
              <div class="col-12 d-flex flex-wrap align-items-center gap-3"><button type="submit" class="btn btn-primary"><?php esc_html_e('Request appointment', 'wp-bbuilder'); ?></button><div class="wpbb-booking__message small" aria-live="polite"></div></div>
            </div></form></div>
          </div></div>
        </div>
        <?php return ob_get_clean();
    }

}
