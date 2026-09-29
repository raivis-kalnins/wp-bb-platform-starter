<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', static function (): void {
    add_menu_page(
        'WP BB Platform',
        'BB Platform',
        'manage_options',
        'wpbb-platform',
        'wpbb_platform_render_admin',
        'dashicons-admin-tools',
        3
    );
});

add_action('admin_post_wpbb_platform_operation', static function (): void {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }
    check_admin_referer('wpbb_platform_operation');
    $operation = isset($_POST['operation']) ? sanitize_key(wp_unslash($_POST['operation'])) : '';
    $result = wpbb_platform_execute_operation($operation);
    set_transient('wpbb_platform_operation_' . get_current_user_id(), $result, 60);
    wp_safe_redirect(admin_url('admin.php?page=wpbb-platform'));
    exit;
});

function wpbb_platform_render_admin(): void {
    if (!current_user_can('manage_options')) return;
    $health = wpbb_platform_health();
    $ops = wpbb_platform_operations();
    $result = get_transient('wpbb_platform_operation_' . get_current_user_id());
    delete_transient('wpbb_platform_operation_' . get_current_user_id());
    ?>
    <div class="wrap wpbb-platform-wrap">
        <h1>WP BB Platform</h1>
        <p>Health and allow-listed operational tools for this Bedrock/Acorn WordPress project.</p>
        <style>
            .wpbb-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;max-width:1200px}.wpbb-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px}.wpbb-card h2{margin-top:0}.wpbb-health{width:100%;border-collapse:collapse}.wpbb-health td{padding:7px 0;border-bottom:1px solid #f0f0f1}.wpbb-health td:last-child{text-align:right;font-weight:600}.wpbb-actions form{display:inline-block;margin:0 8px 8px 0}.wpbb-console{background:#101517;color:#e6edf3;padding:14px;border-radius:8px;white-space:pre-wrap;overflow:auto}.wpbb-note{max-width:1000px;background:#fff8e5;border-left:4px solid #dba617;padding:12px 16px}.wpbb-ok{border-left-color:#00a32a}.wpbb-bad{border-left-color:#d63638}
        </style>
        <?php if (is_array($result)): ?>
            <div class="wpbb-note <?php echo !empty($result['ok']) ? 'wpbb-ok' : 'wpbb-bad'; ?>"><pre class="wpbb-console"><?php echo esc_html((string) ($result['output'] ?? '')); ?></pre></div>
        <?php endif; ?>
        <div class="wpbb-grid">
            <section class="wpbb-card">
                <h2>System health</h2>
                <table class="wpbb-health"><tbody>
                <?php foreach ($health as $label => $value): ?>
                    <tr><td><?php echo esc_html($label); ?></td><td><?php echo esc_html((string) $value); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </section>
            <section class="wpbb-card wpbb-actions">
                <h2>Safe operations</h2>
                <p>Only predefined operations are available. There is no arbitrary shell box.</p>
                <?php foreach ($ops as $key => $op): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('wpbb_platform_operation'); ?>
                        <input type="hidden" name="action" value="wpbb_platform_operation">
                        <input type="hidden" name="operation" value="<?php echo esc_attr($key); ?>">
                        <button class="button button-secondary"><?php echo esc_html($op['label']); ?></button>
                    </form>
                <?php endforeach; ?>
            </section>
            <section class="wpbb-card">
                <h2>Console</h2>
                <p>From WSL/local Docker:</p>
                <pre class="wpbb-console">bin/wp plugin list
bin/wp wc status
bin/artisan about
bin/artisan migrate:status
bin/wp redis status</pre>
                <p><strong>Admin command execution:</strong> <?php echo (defined('WPBB_ALLOW_ADMIN_CLI') && WPBB_ALLOW_ADMIN_CLI) ? 'enabled' : 'disabled (recommended for production)'; ?></p>
            </section>
        </div>
    </div>
    <?php
}
