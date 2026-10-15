<?php
if (!defined('ABSPATH')) exit;

function wpbb_platform_blade_roots(): array {
    $roots = [];
    $candidates = [
        'Platform views' => WPBB_PLATFORM_DIR . 'resources/views',
        'Active theme views' => get_stylesheet_directory() . '/resources/views',
        'Parent theme views' => get_template_directory() . '/resources/views',
    ];
    if (defined('WPBB_PLUGIN_DIR')) $candidates['BBuilder views'] = WPBB_PLUGIN_DIR . 'views';
    foreach ($candidates as $label => $dir) {
        $real = realpath($dir);
        if ($real && is_dir($real)) $roots[$label] = $real;
    }
    return apply_filters('wpbb_platform_blade_roots', $roots);
}

function wpbb_platform_blade_files(): array {
    $out = [];
    foreach (wpbb_platform_blade_roots() as $label => $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) continue;
            $path = $file->getRealPath();
            $rel = ltrim(str_replace('\\', '/', substr($path, strlen($root))), '/');
            $key = hash('sha256', $root . '|' . $rel);
            $out[$key] = ['label' => $label, 'root' => $root, 'relative' => $rel, 'path' => $path];
        }
    }
    uasort($out, static fn($a,$b) => strcmp($a['label'].$a['relative'], $b['label'].$b['relative']));
    return $out;
}

function wpbb_platform_blade_resolve(string $id): ?array {
    $files = wpbb_platform_blade_files();
    return $files[$id] ?? null;
}

add_action('admin_menu', static function (): void {
    add_submenu_page('wpbb-platform', 'Blade & Laravel', 'Blade & Laravel', 'manage_options', 'wpbb-platform-blade', 'wpbb_platform_render_blade_admin');
}, 20);

add_action('admin_post_wpbb_platform_blade_save', static function (): void {
    if (!current_user_can('manage_options')) wp_die('Insufficient permissions.');
    check_admin_referer('wpbb_platform_blade_save');
    $id = sanitize_text_field(wp_unslash($_POST['template_id'] ?? ''));
    $file = wpbb_platform_blade_resolve($id);
    if (!$file || !is_writable($file['path'])) {
        set_transient('wpbb_blade_notice_' . get_current_user_id(), ['error','Template is not writable or no longer exists.'], 60);
    } else {
        $content = (string)wp_unslash($_POST['template_content'] ?? '');
        $ok = file_put_contents($file['path'], $content, LOCK_EX) !== false;
        set_transient('wpbb_blade_notice_' . get_current_user_id(), [$ok?'success':'error', $ok?'Blade template saved.':'Could not save the Blade template.'], 60);
    }
    wp_safe_redirect(admin_url('admin.php?page=wpbb-platform-blade&template=' . rawurlencode($id)));
    exit;
});

add_action('admin_post_wpbb_platform_blade_clear', static function (): void {
    if (!current_user_can('manage_options')) wp_die('Insufficient permissions.');
    check_admin_referer('wpbb_platform_blade_clear');
    $message = 'Blade cache cleared where available.';
    try {
        if (function_exists('app')) {
            $app = app();
            if ($app && method_exists($app, 'make')) {
                $finder = $app->make('view');
                if (is_object($finder) && method_exists($finder, 'flushFinderCache')) $finder->flushFinderCache();
            }
        }
    } catch (Throwable $e) { $message = 'Cache clear completed with a warning: ' . $e->getMessage(); }
    set_transient('wpbb_blade_notice_' . get_current_user_id(), ['success',$message], 60);
    wp_safe_redirect(admin_url('admin.php?page=wpbb-platform-blade'));
    exit;
});

function wpbb_platform_render_blade_admin(): void {
    if (!current_user_can('manage_options')) return;
    $files = wpbb_platform_blade_files();
    $id = sanitize_text_field(wp_unslash($_GET['template'] ?? ''));
    $selected = $id ? wpbb_platform_blade_resolve($id) : null;
    $notice = get_transient('wpbb_blade_notice_' . get_current_user_id());
    delete_transient('wpbb_blade_notice_' . get_current_user_id());
    $acorn = class_exists('Roots\\Acorn\\Application') || function_exists('app');
    $preview_html = '';
    $preview_error = '';
    $context_json = isset($_POST['preview_context']) ? (string)wp_unslash($_POST['preview_context']) : "{\n  \"title\": \"Preview title\",\n  \"text\": \"Preview content from WP admin\"\n}";
    if ($selected && isset($_POST['wpbb_blade_preview']) && isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'wpbb_platform_blade_preview')) {
        $context = json_decode($context_json, true);
        if (!is_array($context)) {
            $preview_error = 'Preview context must be a valid JSON object.';
        } elseif (!function_exists('view')) {
            $preview_error = 'Acorn/Laravel view service is not available.';
        } else {
            try {
                $factory = view();
                if (is_object($factory) && method_exists($factory, 'file')) {
                    $preview_html = (string)$factory->file($selected['path'], $context)->render();
                } else {
                    $preview_error = 'Laravel view factory does not support file rendering.';
                }
            } catch (Throwable $e) {
                $preview_error = $e->getMessage();
            }
        }
    }
    ?>
    <div class="wrap"><h1>Blade & Laravel</h1>
      <p>Manage project Blade templates from WordPress admin. Only <code>*.blade.php</code> files inside allow-listed project view folders are exposed.</p>
      <?php if ($notice): ?><div class="notice notice-<?php echo esc_attr($notice[0]); ?> is-dismissible"><p><?php echo esc_html($notice[1]); ?></p></div><?php endif; ?>
      <div style="display:grid;grid-template-columns:minmax(260px,340px) minmax(0,1fr);gap:18px;max-width:1400px">
        <section style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px">
          <h2 style="margin-top:0">Laravel / Acorn status</h2>
          <p><strong>Acorn:</strong> <?php echo $acorn ? '<span style="color:#008a20">available</span>' : '<span style="color:#b32d2e">not available</span>'; ?></p>
          <p><strong>Blade templates found:</strong> <?php echo esc_html((string)count($files)); ?></p>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><?php wp_nonce_field('wpbb_platform_blade_clear'); ?><input type="hidden" name="action" value="wpbb_platform_blade_clear"><button class="button">Clear Blade/view cache</button></form>
          <hr><h3>Templates</h3>
          <div style="max-height:560px;overflow:auto">
          <?php foreach ($files as $key => $f): ?><p style="margin:0 0 8px"><a href="<?php echo esc_url(admin_url('admin.php?page=wpbb-platform-blade&template=' . rawurlencode($key))); ?>"><strong><?php echo esc_html($f['label']); ?></strong><br><code><?php echo esc_html($f['relative']); ?></code></a></p><?php endforeach; ?>
          <?php if (!$files): ?><p>No Blade templates found.</p><?php endif; ?>
          </div>
        </section>
        <section style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px">
          <?php if ($selected): $content = file_get_contents($selected['path']); ?>
            <h2 style="margin-top:0"><?php echo esc_html($selected['relative']); ?></h2>
            <p><code><?php echo esc_html($selected['path']); ?></code></p>
            <?php if (!is_writable($selected['path'])): ?><div class="notice notice-warning inline"><p>This file is read-only on the server.</p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <?php wp_nonce_field('wpbb_platform_blade_save'); ?><input type="hidden" name="action" value="wpbb_platform_blade_save"><input type="hidden" name="template_id" value="<?php echo esc_attr($id); ?>">
              <textarea name="template_content" spellcheck="false" style="width:100%;min-height:620px;font:13px/1.55 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;tab-size:4" <?php disabled(!is_writable($selected['path'])); ?>><?php echo esc_textarea((string)$content); ?></textarea>
              <p><button class="button button-primary" <?php disabled(!is_writable($selected['path'])); ?>>Save Blade template</button></p>
            </form>
            <hr><h3>Render preview</h3>
            <p>Run the selected Blade file through the Acorn/Laravel view engine with a small JSON data object. Preview is restricted to an administrator-selected allow-listed <code>.blade.php</code> file and displayed in a sandboxed frame.</p>
            <?php if ($preview_error): ?><div class="notice notice-error inline"><p><?php echo esc_html($preview_error); ?></p></div><?php endif; ?>
            <form method="post">
              <?php wp_nonce_field('wpbb_platform_blade_preview'); ?>
              <input type="hidden" name="wpbb_blade_preview" value="1">
              <label for="wpbb-preview-context"><strong>Preview JSON context</strong></label>
              <textarea id="wpbb-preview-context" name="preview_context" spellcheck="false" style="width:100%;min-height:130px;font:13px/1.5 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace"><?php echo esc_textarea($context_json); ?></textarea>
              <p><button class="button" <?php disabled(!$acorn); ?>>Render Blade preview</button></p>
            </form>
            <?php if ($preview_html !== ''): ?>
              <h4>Preview result</h4>
              <iframe sandbox="" srcdoc="<?php echo esc_attr($preview_html); ?>" style="width:100%;min-height:260px;border:1px solid #dcdcde;border-radius:8px;background:#fff"></iframe>
            <?php endif; ?>
          <?php else: ?><h2 style="margin-top:0">Choose a Blade template</h2><p>Select a template from the left. Theme, child-theme, BBuilder and Platform view folders are detected automatically.</p><?php endif; ?>
        </section>
      </div>
    </div>
    <?php
}
