<?php
/** Comparable calendar periods, clean local datasets and native Woo reporting. */
if (!defined('ABSPATH')) exit;
final class WPBB_Analytics_Window {
    public static function make($days = 30, $start = '', $end = '', $now = null) {
        $tz = wp_timezone(); $today = ($now ?: new DateTimeImmutable('now', $tz))->setTimezone($tz)->setTime(0,0);
        $days = max(1, min(365, (int)$days));
        $valid = static function($value) use ($tz) {
            if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return false;
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $tz);
            return $date && $date->format('Y-m-d') === $value ? $date : false;
        };
        $a = $valid($start); $b = $valid($end);
        if (!$a || !$b || $a > $b || $b >= $today || $a->diff($b)->days > 364) {
            $b = $today->modify('-1 day'); $a = $today->modify('-' . $days . ' days');
        }
        $stop = $b->modify('+1 day'); $days = $a->diff($stop)->days;
        $previous = $a->modify('-' . $days . ' days'); $utc = new DateTimeZone('UTC');
        return [
            'days'=>$days, 'start'=>$a->format('Y-m-d'), 'end'=>$b->format('Y-m-d'),
            'previous_start'=>$previous->format('Y-m-d'), 'previous_end'=>$a->modify('-1 day')->format('Y-m-d'),
            'since'=>$a->setTimezone($utc)->format('Y-m-d H:i:s'), 'until'=>$stop->setTimezone($utc)->format('Y-m-d H:i:s'),
            'previous_since'=>$previous->setTimezone($utc)->format('Y-m-d H:i:s'), 'timezone'=>$tz->getName(),
        ];
    }
    public static function growth($current, $previous) {
        if ((float)$previous === 0.0) return (float)$current === 0.0 ? 0.0 : null;
        return round(((float)$current - (float)$previous) / abs((float)$previous) * 100, 1);
    }
    public static function growth_label($value) { return $value === null ? __('No previous baseline','wp-bbuilder') : ($value > 0 ? '+' : '') . number_format_i18n($value,1) . '%'; }
    /** Named SQL timezone tables are often absent on cPanel. Use PHP's real DST transitions. */
    public static function sql_day($window) {
        $tz = wp_timezone(); $start = strtotime($window['since'].' UTC'); $end = strtotime($window['until'].' UTC');
        $transitions = $tz->getTransitions($start,$end);
        if (!$transitions) $transitions = [['ts'=>$start,'offset'=>$tz->getOffset(new DateTimeImmutable('@'.$start))]];
        $parts = []; $last = (int)$transitions[0]['offset'];
        foreach (array_slice($transitions,1) as $entry) {
            $parts[] = "WHEN occurred_at < '" . gmdate('Y-m-d H:i:s',$entry['ts']) . "' THEN " . $last;
            $last = (int)$entry['offset'];
        }
        $offset = $parts ? '(CASE '.implode(' ',$parts).' ELSE '.$last.' END)' : (string)$last;
        return 'DATE(DATE_ADD(occurred_at, INTERVAL '.$offset.' SECOND))';
    }
}
final class WPBB_Analytics_Insights {
    public static function report($window, $demo = false) {
        global $wpdb; $table = WPBB_Analytics::table_name();
        $key = 'wpbb_local_v590_' . md5(wp_json_encode([$window,(bool)$demo]));
        $cached = get_transient($key); if (is_array($cached)) return $cached;
        $where = $wpdb->prepare('is_demo=%d AND occurred_at >= %s AND occurred_at < %s', $demo?1:0, $window['since'],$window['until']);
        $prev = $wpdb->prepare('is_demo=%d AND occurred_at >= %s AND occurred_at < %s', $demo?1:0, $window['previous_since'],$window['since']);
        $summary = $wpdb->get_row("SELECT COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors, COUNT(DISTINCT session_hash) sessions FROM {$table} WHERE {$where}", ARRAY_A);
        $previous = $wpdb->get_row("SELECT COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors, COUNT(DISTINCT session_hash) sessions FROM {$table} WHERE {$prev}", ARRAY_A);
        $error = $wpdb->last_error ? __('Local analytics could not be read. Open Analytics settings once to initialise its table, then retry.', 'wp-bbuilder') : '';
        $day = WPBB_Analytics_Window::sql_day($window);
        $rows = $wpdb->get_results("SELECT {$day} day, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE {$where} GROUP BY day ORDER BY day", ARRAY_A);
        $indexed = []; foreach ((array)$rows as $row) $indexed[$row['day']] = $row;
        $daily = []; $cursor = new DateTimeImmutable($window['start'],wp_timezone());
        for ($i=0;$i<$window['days'];$i++) { $date = $cursor->modify('+'.$i.' days')->format('Y-m-d'); $daily[] = $indexed[$date] ?? ['day'=>$date,'views'=>0,'visitors'=>0]; }
        $r = ['days'=>$window['days'],'window'=>$window,'dataset'=>$demo?'demo':'real','error'=>$error,'daily'=>$daily];
        foreach (['views','visitors','sessions'] as $keyName) $r[$keyName] = (int)($summary[$keyName] ?? 0);
        $r['previous'] = $previous ?: [];
        $r['pages_per_session'] = $r['sessions'] ? round($r['views']/$r['sessions'],2) : 0;
        $r['view_growth'] = WPBB_Analytics_Window::growth($r['views'],$previous['views'] ?? 0);
        $r['visitor_growth'] = WPBB_Analytics_Window::growth($r['visitors'],$previous['visitors'] ?? 0);
        $r['top_pages'] = $wpdb->get_results("SELECT path,MAX(title) title,MAX(object_type) object_type,COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE {$where} GROUP BY path ORDER BY views DESC LIMIT 20",ARRAY_A) ?: [];
        $r['top_products'] = $wpdb->get_results("SELECT page_id,path,MAX(title) title,COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE {$where} AND object_type='product' GROUP BY page_id,path ORDER BY views DESC LIMIT 12",ARRAY_A) ?: [];
        $r['countries'] = $wpdb->get_results("SELECT IF(country='', 'Unknown',country) country,COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE {$where} GROUP BY country ORDER BY views DESC LIMIT 15",ARRAY_A) ?: [];
        $r['referrers'] = $wpdb->get_results("SELECT IF(referrer_host='', 'Direct / internal',referrer_host) source,COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE {$where} GROUP BY referrer_host ORDER BY views DESC LIMIT 15",ARRAY_A) ?: [];
        $r['devices'] = $wpdb->get_results("SELECT IF(device='', 'unknown',device) device,COUNT(*) views FROM {$table} WHERE {$where} GROUP BY device ORDER BY views DESC",ARRAY_A) ?: [];
        $r['demo_rows'] = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_demo=1");
        $r['recent_visitors'] = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT visitor_hash) FROM {$table} WHERE is_demo=0 AND occurred_at >= %s AND occurred_at <= %s",gmdate('Y-m-d H:i:s',time()-300),gmdate('Y-m-d H:i:s')));
        $r['generated'] = current_time('mysql');
        set_transient($key,$r,MINUTE_IN_SECONDS);
        return $r;
    }
    /** Read Woo's own reporting endpoint, keeping its permissions, status/refund/date rules intact. */
    public static function commerce($window) {
        if (!function_exists('WC')) return ['error'=>__('WooCommerce is not active.','wp-bbuilder')];
        if (!current_user_can('view_woocommerce_reports') && !current_user_can('manage_woocommerce')) return ['error'=>__('Your account cannot view WooCommerce reports.','wp-bbuilder')];
        $date_type = get_option('woocommerce_date_type','date_paid');
        if (!in_array($date_type,['date_paid','date_created','date_completed'],true)) $date_type='date_paid';
        $query = function($start,$end) use ($date_type) {
            $request = new WP_REST_Request('GET','/wc-analytics/reports/revenue/stats');
            $request->set_query_params(['after'=>$start.'T00:00:00','before'=>$end.'T23:59:59','interval'=>'day','per_page'=>100,'date_type'=>$date_type]);
            $response = rest_do_request($request);
            if (is_wp_error($response)) return ['error'=>$response->get_error_message()];
            if ($response->get_status() >= 400) return ['error'=>__('WooCommerce Analytics is unavailable. Check Analytics is enabled, report permissions and the historical data import.','wp-bbuilder')];
            $data = $response->get_data();
            if (!is_array($data) || !isset($data['totals']) || !is_array($data['totals'])) return ['error'=>__('WooCommerce returned no report totals. No sales figures have been substituted.','wp-bbuilder')];
            return $data;
        };
        $current = $query($window['start'],$window['end']);
        if (isset($current['error'])) return $current;
        $previous = $query($window['previous_start'],$window['previous_end']);
        return ['totals'=>$current['totals'],'previous'=>$previous['totals'] ?? [],'previous_error'=>$previous['error'] ?? '', 'date_type'=>$date_type,'currency'=>get_woocommerce_currency(), 'excluded_statuses'=>(array)get_option('woocommerce_excluded_report_order_statuses',['pending','failed','cancelled'])];
    }
    public static function commerce_html($window) {
        $r = self::commerce($window);
        echo '<section class="wpbb-analytics-panel"><div class="wpbb-panel-title"><h2>'.esc_html__('WooCommerce sales','wp-bbuilder').'</h2><span class="wpbb-source">WooCommerce Analytics</span></div>';
        if (!empty($r['error'])) { echo '<p>'.esc_html($r['error']).'</p></section>'; return; }
        echo '<div class="wpbb-kpis">';
        foreach (['net_revenue'=>'Net sales','orders_count'=>'Orders','refunds'=>'Returns','total_sales'=>'Total sales'] as $key=>$label) {
            $value = $r['totals'][$key] ?? 0; $growth = array_key_exists($key,$r['previous']) ? WPBB_Analytics_Window::growth($value,$r['previous'][$key]) : null;
            echo '<div class="wpbb-kpi"><span>'.esc_html__($label,'wp-bbuilder').'</span><strong>'.('orders_count'===$key ? esc_html(number_format_i18n($value)) : wp_kses_post(wc_price($value,['currency'=>$r['currency']]))).'</strong><small>'.esc_html(WPBB_Analytics_Window::growth_label($growth)).'</small></div>';
        }
        echo '</div><p class="description">'.esc_html(sprintf(__('Source: the same revenue report used by WooCommerce Analytics. Date basis: %1$s. Store timezone: %2$s. Excluded statuses: %3$s.','wp-bbuilder'),$r['date_type'],$window['timezone'],implode(', ',$r['excluded_statuses']))).'</p>';
        echo '<p>'.esc_html__('Traffic is not sales. Consent, ad blockers, imported orders, status filters and refund dates can make traffic and order totals differ. No conversion rate is inferred by dividing unrelated visitor and order counts.','wp-bbuilder').'</p>';
        $url = add_query_arg(['page'=>'wc-admin','path'=>'/analytics/revenue','period'=>'custom','after'=>$window['start'],'before'=>$window['end']],admin_url('admin.php'));
        echo '<a class="button" href="'.esc_url($url).'">'.esc_html__('Compare in WooCommerce Analytics','wp-bbuilder').'</a> <a class="button" href="'.esc_url(admin_url('admin.php?page=wc-admin&path=/analytics/settings')).'">'.esc_html__('Import/status settings','wp-bbuilder').'</a></section>';
    }
}
