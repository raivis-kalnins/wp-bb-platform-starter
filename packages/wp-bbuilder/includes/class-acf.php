<?php
if (!defined('ABSPATH')) exit;

$acf_field_support_file = WPBB_PLUGIN_DIR . 'includes/acf-field-block.php';
if (file_exists($acf_field_support_file)) {
    require_once $acf_field_support_file;
}

final class WPBB_ACF {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Register the generic dynamic field block on init so existing content
        // remains valid even when ACF is temporarily unavailable or the block is
        // hidden from the inserter in BBuilder settings.
        add_action('init', [$this, 'register_field_block'], 20);
        add_action('acf/init', [$this, 'register_blocks']);
        add_action('acf/init', [$this, 'register_field_groups']);
    }

    private function get_block_dirs() {
        return [
            'hero' => WPBB_PLUGIN_DIR . 'acf-blocks/hero',
            'gallery' => WPBB_PLUGIN_DIR . 'acf-blocks/gallery',
        ];
    }

    /**
     * Register the read-only ACF Field block from block metadata.
     */
    public function register_field_block() {
        if (!function_exists('register_block_type')) return;

        $dir = WPBB_PLUGIN_DIR . 'blocks/acf-field';
        if (!is_dir($dir) || !file_exists($dir . '/block.json')) return;

        if (class_exists('WP_Block_Type_Registry')) {
            $registry = WP_Block_Type_Registry::get_instance();
            if ($registry && $registry->is_registered('wpbb/acf-field')) return;
        }

        $registered = register_block_type($dir);
        if (!$registered) return;

        $config = 'window.wpbbAcfFieldSettings = ' . wp_json_encode([
            'allowOptions' => (bool) wpbb_get_option('acf_field_allow_options', 1),
            'acfAvailable' => function_exists('wpbb_acf_field_support_available') && wpbb_acf_field_support_available(),
            'inserterEnabled' => (bool) wpbb_get_option('acf_field_block_enabled', 1),
            'pluginVersion' => defined('WPBB_VERSION') ? WPBB_VERSION : '',
        ]) . ';';

        $handles = [];
        if (is_object($registered)) {
            if (!empty($registered->editor_script_handles) && is_array($registered->editor_script_handles)) {
                $handles = $registered->editor_script_handles;
            } elseif (!empty($registered->editor_script) && is_string($registered->editor_script)) {
                $handles[] = $registered->editor_script;
            }
        }

        if (empty($handles)) $handles[] = 'wpbb-acf-field-editor-script';

        foreach (array_unique($handles) as $handle) {
            if (wp_script_is($handle, 'registered')) {
                wp_add_inline_script($handle, $config, 'before');
            }
        }
    }

    public function register_blocks() {
        if (!function_exists('acf_register_block_type')) return;

        $defs = [
            'hero' => ['name' => 'wpbb-hero', 'title' => 'Hero', 'icon' => 'cover-image'],
            'gallery' => ['name' => 'wpbb-gallery', 'title' => 'Gallery', 'icon' => 'format-gallery'],
        ];

        foreach ($this->get_block_dirs() as $slug => $dir) {
            if (!is_dir($dir) || empty($defs[$slug])) continue;
            $def = $defs[$slug];
            acf_register_block_type([
                'name' => $def['name'],
                'title' => __($def['title'], 'wp-bbuilder'),
                'description' => sprintf(__('ACF %s block', 'wp-bbuilder'), $def['title']),
                'category' => 'wpbb',
                'icon' => $def['icon'],
                'mode' => 'preview',
                'render_callback' => [$this, 'render_block'],
                'supports' => ['align' => ['wide', 'full'], 'anchor' => true, 'jsx' => true],
                'enqueue_style' => WPBB_PLUGIN_URL . 'assets/shared.css',
            ]);
        }
    }

    public function render_block($block, $content = '', $is_preview = false, $post_id = 0, $wp_block = null, $context = []) {
        $name = isset($block['name']) ? (string) $block['name'] : '';
        $slug = str_replace(['acf/wpbb-', 'wpbb-'], '', $name);
        $dirs = $this->get_block_dirs();
        if (!$slug || empty($dirs[$slug])) return;

        if (function_exists('wp_bb_blade') && function_exists('get_fields')) {
            $fields = get_fields();
            $html = wp_bb_blade('blocks.' . str_replace('-', '_', $slug), [
                'fields' => is_array($fields) ? $fields : [],
                'block' => $block,
                'content' => $content,
                'is_preview' => (bool) $is_preview,
                'post_id' => $post_id,
                'wp_block' => $wp_block,
                'context' => $context,
            ], false);
            if (false !== $html) {
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                return;
            }
        }

        $fallback = $dirs[$slug] . '/render.php';
        if (is_readable($fallback)) include $fallback;
    }

    public function register_field_groups() {
        if (!function_exists('acf_add_local_field_group')) return;

        foreach ($this->get_block_dirs() as $dir) {
            $fields_file = $dir . '/fields.php';
            if (file_exists($fields_file)) {
                include $fields_file;
            }
        }
    }
}
