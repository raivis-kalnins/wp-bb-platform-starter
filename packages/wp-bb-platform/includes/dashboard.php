<?php
if (!defined('ABSPATH')) exit;

/**
 * Main WordPress dashboard summary.
 * Uses BBuilder local analytics when available and WooCommerce's own orders.
 */
add_action('wp_dashboard_setup', static function (): void {
    if (!current_user_can('manage_options')) return;
    wp_add_dashboard_widget(
        'wpbb_platform_dashboard',
        'WP BB Platform — site overview',
        'wpbb_platform_render_dashboard_widget'
    );
}, 20);

function wpbb_platform_dashboard_analytics(): array {
    $data = ['views' => null, 'visitors' => null, 'sessions' => null, 'start' => '', 'end' => ''];
    if (!class_exists('WPBB_Analytics')) return $data;
    try {
        $analytics = WPBB_Analytics::instance();
        if (!method_exists($analytics, 'get_report')) return $data;
        $report = $analytics->get_report(7, false);
        if (!is_array($report) || !empty($report['error'])) return $data;
        foreach (['views','visitors','sessions'] as $key) $data[$key] = isset($report[$key]) ? (int)$report[$key] : 0;
        if (!empty($report['window']['start'])) $data['start'] = (string)$report['window']['start'];
        if (!empty($report['window']['end'])) $data['end'] = (string)$report['window']['end'];
    } catch (Throwable $e) {}
    return $data;
}

function wpbb_platform_dashboard_commerce(): array {
    $data = ['orders' => null, 'sales' => null, 'currency' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : ''];
    if (!class_exists('WooCommerce') || !function_exists('wc_get_orders')) return $data;
    try {
        $after = (new DateTimeImmutable('now', wp_timezone()))->modify('-7 days')->format('Y-m-d H:i:s');
        $orders = wc_get_orders([
            'limit' => -1,
            'return' => 'objects',
            'date_created' => '>=' . $after,
            'status' => ['wc-processing','wc-completed','wc-on-hold'],
        ]);
        $total = 0.0;
        foreach ($orders as $order) $total += (float)$order->get_total();
        $data['orders'] = count($orders);
        $data['sales'] = $total;
    } catch (Throwable $e) {}
    return $data;
}

function wpbb_platform_render_dashboard_widget(): void {
    $a = wpbb_platform_dashboard_analytics();
    $c = wpbb_platform_dashboard_commerce();
    echo '<style>.wpbb-dash-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:10px 0 14px}.wpbb-dash-kpi{padding:12px;border:1px solid #e1e5e8;border-radius:10px;background:#f8fafb}.wpbb-dash-kpi strong{display:block;font-size:21px;line-height:1.15;color:#12354c}.wpbb-dash-kpi span{font-size:12px;color:#5a6b76}.wpbb-dash-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}@media(max-width:600px){.wpbb-dash-kpis{grid-template-columns:1fr 1fr}}</style>';
    echo '<p><strong>Last 7 days</strong>' . ($a['start'] && $a['end'] ? ' <span style="color:#646970">' . esc_html($a['start'] . ' → ' . $a['end']) . '</span>' : '') . '</p>';
    echo '<div class="wpbb-dash-kpis">';
    $metrics = [
        'Page views' => $a['views'],
        'Visitors' => $a['visitors'],
        'Sessions' => $a['sessions'],
        'Orders' => $c['orders'],
        'Sales' => $c['sales'] === null ? null : $c['currency'] . number_format_i18n((float)$c['sales'], 2),
    ];
    foreach ($metrics as $label => $value) {
        echo '<div class="wpbb-dash-kpi"><strong>' . esc_html($value === null ? '—' : (string)$value) . '</strong><span>' . esc_html($label) . '</span></div>';
    }
    echo '</div><div class="wpbb-dash-row">';
    if (class_exists('WPBB_Analytics')) echo '<a class="button button-primary" href="' . esc_url(admin_url('options-general.php?page=wpbb-analytics')) . '">Website statistics</a>';
    if (class_exists('WooCommerce')) echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=wc-admin&path=/analytics/overview')) . '">WooCommerce analytics</a>';
    echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=wpbb-platform-blade')) . '">Blade & Laravel</a>';
    echo '</div>';
}
