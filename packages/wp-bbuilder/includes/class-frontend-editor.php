<?php
if (!defined('ABSPATH')) exit;

final class WPBB_Frontend_Editor {
    private static $instance = null;
    private $block_map = null;
    private $block_offsets = [];

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public static function supported_blocks() {
        return apply_filters('wpbb_frontend_editor_supported_blocks', [
            'core/paragraph'    => __('Paragraph', 'wp-bbuilder'),
            'core/heading'      => __('Heading', 'wp-bbuilder'),
            'core/verse'        => __('Verse', 'wp-bbuilder'),
            'core/preformatted' => __('Preformatted', 'wp-bbuilder'),
            'core/list-item'    => __('List item', 'wp-bbuilder'),
        ]);
    }

    private function __construct() {
        add_action('init', [$this, 'register_assets']);
        add_action('init', [$this, 'maybe_hide_admin_bar']);
        add_action('admin_init', [$this, 'maybe_redirect_restricted_user'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('render_block', [$this, 'wrap_editable_block'], 10, 2);
        add_action('wp_footer', [$this, 'render_toolbar']);
        add_action('wp_footer', [$this, 'render_restricted_user_menu']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function is_enabled() {
        return !empty(wpbb_get_option('frontend_editor_enabled', 0));
    }

    public function editable_blocks() {
        $saved = wpbb_get_option('frontend_editor_editable_blocks', ['core/paragraph','core/heading']);
        $supported = array_keys(self::supported_blocks());
        $blocks = is_array($saved) ? $saved : ['core/paragraph','core/heading'];
        $blocks = array_values(array_intersect($blocks, $supported));
        return $blocks;
    }

    public function restricted_roles() {
        $saved = wpbb_get_option('frontend_editor_restricted_roles', []);
        return is_array($saved) ? array_values(array_filter(array_map('sanitize_key', $saved))) : [];
    }

    public function is_user_restricted() {
        if (!$this->is_enabled() || !is_user_logged_in()) return false;
        $restricted = $this->restricted_roles();
        if (empty($restricted)) return false;
        $user = wp_get_current_user();
        return !empty(array_intersect((array) $user->roles, $restricted));
    }

    public function maybe_hide_admin_bar() {
        if ($this->is_user_restricted()) show_admin_bar(false);
    }

    public function maybe_redirect_restricted_user() {
        if (!$this->is_user_restricted()) return;
        if (wp_doing_ajax()) return;
        if (isset($_SERVER['SCRIPT_FILENAME']) && 'admin-post.php' === basename(sanitize_text_field(wp_unslash($_SERVER['SCRIPT_FILENAME'])))) return;
        wp_safe_redirect(home_url('/'));
        exit;
    }

    public function register_assets() {
        if (!function_exists('wp_register_script_module')) return;
        wp_register_script_module(
            'wp-bbuilder/frontend-editor',
            WPBB_PLUGIN_URL . 'assets/frontend-editor.js',
            ['@wordpress/interactivity'],
            WPBB_VERSION
        );
    }

    private function can_render() {
        if (!$this->is_enabled()) return false;
        if (!function_exists('wp_enqueue_script_module') || !function_exists('wp_interactivity_state')) return false;
        if (!is_singular() || !is_user_logged_in()) return false;
        $post_id = get_queried_object_id();
        if (!$post_id || !current_user_can('edit_post', $post_id)) return false;
        return !empty($this->editable_blocks());
    }

    public function enqueue_assets() {
        if (!$this->can_render()) return;
        wp_enqueue_script_module('wp-bbuilder/frontend-editor');
        wp_enqueue_style('wpbb-frontend-editor', WPBB_PLUGIN_URL . 'assets/frontend-editor.css', [], WPBB_VERSION);
        wp_interactivity_state('wp-bbuilder-frontend-editor', [
            'postId'    => get_queried_object_id(),
            'restNonce' => wp_create_nonce('wp_rest'),
            'endpoint'  => rest_url('wpbb/v1/frontend-editor/update-block'),
            'isEditing' => false,
            'isSaving'  => false,
            'message'   => '',
        ]);
    }

    private function get_post_block_map() {
        if ($this->block_map !== null) return $this->block_map;
        $this->block_map = [];
        $post_id = get_queried_object_id();
        $post = $post_id ? get_post($post_id) : null;
        if (!$post) return $this->block_map;
        $blocks = parse_blocks($post->post_content);
        $flat = [];
        $this->flatten_blocks($blocks, $flat);
        $editable = $this->editable_blocks();
        foreach ($flat as $index => $entry) {
            $block = $entry['block'];
            if (empty($block['blockName']) || !in_array($block['blockName'], $editable, true)) continue;
            $sig = $block['blockName'] . '::' . trim((string) ($block['innerHTML'] ?? ''));
            if (!isset($this->block_map[$sig])) $this->block_map[$sig] = [];
            $this->block_map[$sig][] = $index;
        }
        return $this->block_map;
    }

    private function next_block_index($block) {
        if (empty($block['blockName'])) return null;
        $sig = $block['blockName'] . '::' . trim((string) ($block['innerHTML'] ?? ''));
        $map = $this->get_post_block_map();
        if (empty($map[$sig])) return null;
        $offset = isset($this->block_offsets[$sig]) ? (int) $this->block_offsets[$sig] : 0;
        $this->block_offsets[$sig] = $offset + 1;
        return $map[$sig][$offset] ?? null;
    }

    public function wrap_editable_block($block_content, $block) {
        if (!$this->can_render()) return $block_content;
        if (empty($block['blockName']) || !in_array($block['blockName'], $this->editable_blocks(), true)) return $block_content;
        $flat_index = $this->next_block_index($block);
        if ($flat_index === null) return $block_content;
        $context = esc_attr(wp_json_encode(['blockIndex' => $flat_index]));
        return sprintf(
            '<div class="wpbb-fie-block" data-wp-interactive="wp-bbuilder-frontend-editor" data-wp-context=\'%s\' data-wp-on--click="actions.editBlock" data-wp-class--wpbb-fie-active="context.active" data-wp-watch="callbacks.syncEditable">%s</div>',
            $context,
            $block_content
        );
    }

    public function render_toolbar() {
        if (!$this->can_render()) return;
        ?>
        <div data-wp-interactive="wp-bbuilder-frontend-editor" class="wpbb-fie-toolbar-wrap">
            <div class="wpbb-fie-savebar" data-wp-bind--hidden="!state.isEditing" aria-label="<?php esc_attr_e('BBuilder front-end editor actions', 'wp-bbuilder'); ?>">
                <span class="wpbb-fie-toolbar-label"><?php esc_html_e('Editing block', 'wp-bbuilder'); ?></span>
                <button type="button" class="wpbb-fie-btn wpbb-fie-btn-save" data-wp-on--click="actions.save" data-wp-bind--disabled="state.isSaving"><?php esc_html_e('Save', 'wp-bbuilder'); ?></button>
                <button type="button" class="wpbb-fie-btn wpbb-fie-btn-cancel" data-wp-on--click="actions.cancel" data-wp-bind--disabled="state.isSaving"><?php esc_html_e('Cancel', 'wp-bbuilder'); ?></button>
                <span class="wpbb-fie-message" data-wp-text="state.message"></span>
            </div>

            <div class="wpbb-fie-inline-toolbar" data-wp-bind--hidden="!state.isEditing" role="toolbar" aria-label="<?php esc_attr_e('BBuilder inline formatting', 'wp-bbuilder'); ?>">
                <button type="button" class="wpbb-fie-tool-btn" data-wpbb-command="bold" data-wp-on--click="actions.formatInline" aria-label="<?php esc_attr_e('Bold', 'wp-bbuilder'); ?>"><strong>B</strong></button>
                <button type="button" class="wpbb-fie-tool-btn" data-wpbb-command="italic" data-wp-on--click="actions.formatInline" aria-label="<?php esc_attr_e('Italic', 'wp-bbuilder'); ?>"><em>I</em></button>
                <button type="button" class="wpbb-fie-tool-btn" data-wpbb-command="underline" data-wp-on--click="actions.formatInline" aria-label="<?php esc_attr_e('Underline', 'wp-bbuilder'); ?>"><span style="text-decoration:underline">U</span></button>
                <span class="wpbb-fie-tool-divider" aria-hidden="true"></span>
                <button type="button" class="wpbb-fie-tool-btn wpbb-fie-align-btn" data-wpbb-align="" data-wp-on--click="actions.setAlignment" aria-label="<?php esc_attr_e('Default alignment', 'wp-bbuilder'); ?>"><?php esc_html_e('Default', 'wp-bbuilder'); ?></button>
                <button type="button" class="wpbb-fie-tool-btn wpbb-fie-align-btn" data-wpbb-align="left" data-wp-on--click="actions.setAlignment" aria-label="<?php esc_attr_e('Align left', 'wp-bbuilder'); ?>">&#8676;</button>
                <button type="button" class="wpbb-fie-tool-btn wpbb-fie-align-btn" data-wpbb-align="center" data-wp-on--click="actions.setAlignment" aria-label="<?php esc_attr_e('Align center', 'wp-bbuilder'); ?>">&#8596;</button>
                <button type="button" class="wpbb-fie-tool-btn wpbb-fie-align-btn" data-wpbb-align="right" data-wp-on--click="actions.setAlignment" aria-label="<?php esc_attr_e('Align right', 'wp-bbuilder'); ?>">&#8677;</button>
            </div>
        </div>
        <?php
    }

    public function render_restricted_user_menu() {
        if (!$this->is_user_restricted()) return;
        wp_enqueue_style('wpbb-frontend-editor', WPBB_PLUGIN_URL . 'assets/frontend-editor.css', [], WPBB_VERSION);
        ?>
        <details class="wpbb-fie-user-fab">
            <summary class="wpbb-fie-fab-toggle" title="<?php esc_attr_e('Account', 'wp-bbuilder'); ?>">&#9881;</summary>
            <div class="wpbb-fie-fab-menu">
                <span class="wpbb-fie-fab-greeting"><?php echo esc_html(sprintf(__('Hi, %s', 'wp-bbuilder'), wp_get_current_user()->display_name)); ?></span>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="wpbb-fie-fab-logout"><?php esc_html_e('Log out', 'wp-bbuilder'); ?></a>
            </div>
        </details>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route('wpbb/v1', '/frontend-editor/update-block', [
            'methods' => 'POST',
            'callback' => [$this, 'update_block'],
            'permission_callback' => function ($request) {
                if (!$this->is_enabled()) return false;
                $post_id = absint($request->get_param('postId'));
                return $post_id && current_user_can('edit_post', $post_id);
            },
            'args' => [
                'postId' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                'blockIndex' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                'newContent' => ['required' => false, 'type' => 'string', 'sanitize_callback' => 'wp_kses_post'],
                'newHTML'    => ['required' => false, 'type' => 'string', 'sanitize_callback' => 'wp_kses_post'],
            ],
        ]);
    }

    public function update_block($request) {
        $post_id = absint($request->get_param('postId'));
        $block_index = absint($request->get_param('blockIndex'));
        $new_content = (string) $request->get_param('newContent');
        $post = get_post($post_id);
        if (!$post) return new WP_Error('not_found', __('Post not found.', 'wp-bbuilder'), ['status' => 404]);

        $blocks = parse_blocks($post->post_content);
        $flat = [];
        $this->flatten_blocks($blocks, $flat);
        if (!isset($flat[$block_index])) return new WP_Error('invalid_block', __('Block index out of range.', 'wp-bbuilder'), ['status' => 400]);

        $target = $flat[$block_index];
        if (empty($target['block']['blockName']) || !in_array($target['block']['blockName'], $this->editable_blocks(), true)) {
            return new WP_Error('not_editable', __('This block type is not editable.', 'wp-bbuilder'), ['status' => 400]);
        }

        $new_html = (string) $request->get_param('newHTML');
        $old_inner = (string) ($target['ref']['innerHTML'] ?? '');
        $trimmed = trim($old_inner);
        $old_text = wp_strip_all_tags($trimmed);

        if ($new_html !== '' && $this->is_safe_replacement_html($trimmed, $new_html)) {
            $new_inner = "\n" . trim($new_html) . "\n";
            $this->sync_alignment_attribute($target['ref'], $new_html);
        } elseif (preg_match('/^(<[^>]+>)(.*?)(<\/[^>]+>)$/s', $trimmed, $m)) {
            $new_inner = "\n" . $m[1] . $new_content . $m[3] . "\n";
        } else {
            $new_inner = "\n" . $new_content . "\n";
        }

        $target['ref']['innerHTML'] = $new_inner;
        $target['ref']['innerContent'] = [$new_inner];
        $updated_content = serialize_blocks($blocks);
        $result = wp_update_post(['ID' => $post_id, 'post_content' => $updated_content], true);
        if (is_wp_error($result)) return $result;

        $user = wp_get_current_user();
        $block_type = str_replace('core/', '', (string) $target['block']['blockName']);
        $old_short = mb_strimwidth(wp_strip_all_tags($old_text), 0, 80, '...');
        $new_short = mb_strimwidth(wp_strip_all_tags($new_content), 0, 80, '...');
        wp_insert_comment([
            'comment_post_ID' => $post_id,
            'comment_content' => sprintf('Edited %s from BBuilder front-end editor: "%s" -> "%s"', $block_type, $old_short, $new_short),
            'comment_type' => 'note',
            'user_id' => $user->ID,
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_approved' => 1,
            'comment_parent' => 0,
        ]);

        return ['success' => true];
    }

    private function is_safe_replacement_html($old_html, $new_html) {
        $old_tag = '';
        $new_tag = '';
        if (preg_match('/^<([a-z0-9]+)\b[^>]*>.*<\/\1>$/is', trim($old_html), $m)) {
            $old_tag = strtolower($m[1]);
        }
        if (preg_match('/^<([a-z0-9]+)\b[^>]*>.*<\/\1>$/is', trim($new_html), $m)) {
            $new_tag = strtolower($m[1]);
        }
        return $old_tag && $new_tag && $old_tag === $new_tag;
    }

    private function sync_alignment_attribute(&$block, $html) {
        if (!isset($block['attrs']) || !is_array($block['attrs'])) {
            $block['attrs'] = [];
        }

        if (preg_match('/has-text-align-(left|center|right)/', $html, $m)) {
            $block['attrs']['align'] = $m[1];
            return;
        }

        unset($block['attrs']['align']);
    }

    private function flatten_blocks(&$blocks, &$flat) {
        foreach ($blocks as &$block) {
            $flat[] = ['block' => $block, 'ref' => &$block];
            if (!empty($block['innerBlocks'])) {
                $this->flatten_blocks($block['innerBlocks'], $flat);
            }
        }
    }
}
