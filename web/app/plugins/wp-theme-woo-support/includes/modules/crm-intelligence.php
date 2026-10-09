<?php
/**
 * WP BB local CRM intelligence.
 * Customer lifecycle, RFM, reorder timing, product affinity and cohort metrics
 * calculated directly from WooCommerce orders with no external CRM dependency.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'wpbb_crm_admin_menu', 75 );
add_action( 'admin_enqueue_scripts', 'wpbb_crm_admin_assets' );
add_action( 'admin_post_wpbb_crm_export', 'wpbb_crm_export' );

function wpbb_crm_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __( 'CRM Intelligence', 'wp-theme-woo-support' ),
        __( 'CRM Intelligence', 'wp-theme-woo-support' ),
        'manage_woocommerce',
        'wpbb-crm-intelligence',
        'wpbb_crm_render_page'
    );
}

function wpbb_crm_admin_assets( $hook ) {
    if ( strpos( (string) $hook, 'wpbb-crm-intelligence' ) === false ) return;
    wp_enqueue_script( 'google-charts', 'https://www.gstatic.com/charts/loader.js', array(), null, true );
    wp_enqueue_script( 'wpbb-crm-intelligence', wp_theme_woo_support_url( 'assets/js/crm-intelligence.js' ), array( 'google-charts' ), WP_THEME_WOO_SUPPORT_VERSION, true );
    wp_enqueue_style( 'wpbb-crm-intelligence', wp_theme_woo_support_url( 'assets/css/crm-intelligence.css' ), array(), WP_THEME_WOO_SUPPORT_VERSION );
}

function wpbb_crm_segment( $orders, $revenue, $days_since, $vip_threshold ) {
    if ( $orders <= 1 && $days_since <= 60 ) return 'New';
    if ( $revenue >= $vip_threshold && $orders >= 3 ) return 'VIP';
    if ( $days_since > 180 ) return 'Lapsed';
    if ( $days_since > 90 ) return 'At risk';
    if ( $orders >= 5 ) return 'Loyal';
    if ( $orders >= 2 ) return 'Repeat';
    return 'Active';
}

function wpbb_crm_recency_score( $days ) {
    if ( $days <= 30 ) return 5;
    if ( $days <= 60 ) return 4;
    if ( $days <= 90 ) return 3;
    if ( $days <= 180 ) return 2;
    return 1;
}
function wpbb_crm_frequency_score( $orders ) {
    if ( $orders >= 8 ) return 5;
    if ( $orders >= 5 ) return 4;
    if ( $orders >= 3 ) return 3;
    if ( $orders >= 2 ) return 2;
    return 1;
}
function wpbb_crm_monetary_score( $revenue, $vip ) {
    $vip = max( 1, (float) $vip );
    if ( $revenue >= $vip ) return 5;
    if ( $revenue >= $vip * .75 ) return 4;
    if ( $revenue >= $vip * .5 ) return 3;
    if ( $revenue >= $vip * .25 ) return 2;
    return 1;
}

function wpbb_crm_paid_statuses() {
    if ( function_exists( 'wc_get_is_paid_statuses' ) ) {
        return array_map( static function( $status ) { return 'wc-' . $status; }, wc_get_is_paid_statuses() );
    }
    return array( 'wc-processing', 'wc-completed' );
}

function wpbb_crm_collect( $days = 365 ) {
    $days = max( 30, min( 1095, absint( $days ) ) );
    $cache_key = 'wpbb_crm_v2_' . md5( (string) $days );
    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) ) return $cached;

    $after = ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( '-' . $days . ' days' )->format( 'Y-m-d H:i:s' );
    $customers = array();
    $products = array();
    $monthly = array();
    $total_orders = 0;
    $revenue = 0.0;
    $units = 0;
    $page = 1;
    $max_pages = 100;

    do {
        $batch = wc_get_orders( array(
            'limit' => 100,
            'page' => $page,
            'paginate' => true,
            'status' => wpbb_crm_paid_statuses(),
            'date_created' => '>=' . $after,
            'orderby' => 'date',
            'order' => 'ASC',
            'return' => 'objects',
        ) );
        $orders = isset( $batch->orders ) ? $batch->orders : array();

        foreach ( $orders as $order ) {
            if ( ! $order instanceof WC_Order ) continue;
            $total_orders++;
            $total = (float) $order->get_total();
            $revenue += $total;
            $created = $order->get_date_created();
            $date = $created ? $created->date( 'Y-m-d H:i:s' ) : '';
            $month = $created ? $created->date( 'Y-m' ) : 'unknown';
            $email = strtolower( trim( (string) $order->get_billing_email() ) );
            if ( ! $email && $order->get_customer_id() ) $email = 'user-' . (int) $order->get_customer_id();
            if ( ! $email ) continue;

            if ( ! isset( $customers[ $email ] ) ) {
                $customers[ $email ] = array(
                    'email' => strpos( $email, 'user-' ) === 0 ? '' : $email,
                    'name' => trim( $order->get_formatted_billing_full_name() ),
                    'orders' => 0,
                    'revenue' => 0.0,
                    'dates' => array(),
                    'first' => $date,
                    'last' => $date,
                    'products' => array(),
                );
            }
            $c =& $customers[ $email ];
            $c['orders']++;
            $c['revenue'] += $total;
            if ( $date ) {
                $c['dates'][] = $date;
                $c['last'] = $date;
                if ( ! $c['first'] ) $c['first'] = $date;
            }

            foreach ( $order->get_items() as $item ) {
                if ( ! $item instanceof WC_Order_Item_Product ) continue;
                $name = $item->get_name();
                $qty = max( 0, (int) $item->get_quantity() );
                $line = (float) $item->get_total();
                $units += $qty;
                $c['products'][ $name ] = ( $c['products'][ $name ] ?? 0 ) + $qty;
                $pid = $item->get_product_id();
                $key = $pid ? 'id:' . $pid : 'name:' . $name;
                if ( ! isset( $products[ $key ] ) ) $products[ $key ] = array( 'product_id'=>$pid, 'name'=>$name, 'qty'=>0, 'revenue'=>0.0, 'customers'=>array() );
                $products[ $key ]['qty'] += $qty;
                $products[ $key ]['revenue'] += $line;
                $products[ $key ]['customers'][ $email ] = 1;
            }

            if ( ! isset( $monthly[ $month ] ) ) $monthly[ $month ] = array( 'orders'=>0, 'revenue'=>0.0, 'new'=>0, 'customers'=>array() );
            $monthly[ $month ]['orders']++;
            $monthly[ $month ]['revenue'] += $total;
            $monthly[ $month ]['customers'][ $email ] = 1;
        }

        $page++;
        $max = isset( $batch->max_num_pages ) ? (int) $batch->max_num_pages : 1;
    } while ( ! empty( $orders ) && $page <= $max_pages && $page <= $max );

    $revenues = array_map( static function( $c ) { return (float) $c['revenue']; }, $customers );
    sort( $revenues );
    $vip_threshold = $revenues ? max( 500.0, (float) $revenues[ (int) floor( ( count( $revenues ) - 1 ) * .9 ) ] ) : 500.0;
    $segments = array();
    $segment_revenue = array();
    $repeat = 0;
    $active_90 = 0;
    $churn_risk = 0;
    $interval_sum = 0.0;
    $interval_customers = 0;
    $cohorts = array();
    $now = time();

    foreach ( $customers as $email => &$c ) {
        sort( $c['dates'] );
        $days_since = $c['last'] ? max( 0, (int) floor( ( $now - strtotime( $c['last'] ) ) / DAY_IN_SECONDS ) ) : 9999;
        $c['days_since'] = $days_since;
        $c['aov'] = $c['orders'] ? $c['revenue'] / $c['orders'] : 0;
        $intervals = array();
        for ( $i = 1; $i < count( $c['dates'] ); $i++ ) {
            $intervals[] = max( 1, (int) round( ( strtotime( $c['dates'][ $i ] ) - strtotime( $c['dates'][ $i - 1 ] ) ) / DAY_IN_SECONDS ) );
        }
        $avg_interval = $intervals ? array_sum( $intervals ) / count( $intervals ) : 0;
        $c['avg_interval'] = $avg_interval;
        if ( $avg_interval ) { $interval_sum += $avg_interval; $interval_customers++; }
        $c['next_order'] = $avg_interval && $c['last'] ? wp_date( 'Y-m-d', strtotime( $c['last'] ) + (int) round( $avg_interval * DAY_IN_SECONDS ) ) : '—';
        $c['segment'] = wpbb_crm_segment( $c['orders'], $c['revenue'], $days_since, $vip_threshold );
        $c['r_score'] = wpbb_crm_recency_score( $days_since );
        $c['f_score'] = wpbb_crm_frequency_score( $c['orders'] );
        $c['m_score'] = wpbb_crm_monetary_score( $c['revenue'], $vip_threshold );
        $c['rfm'] = $c['r_score'] . $c['f_score'] . $c['m_score'];
        if ( $c['orders'] > 1 ) $repeat++;
        if ( $days_since <= 90 ) $active_90++;
        if ( in_array( $c['segment'], array( 'At risk', 'Lapsed' ), true ) ) $churn_risk++;
        $segments[ $c['segment'] ] = ( $segments[ $c['segment'] ] ?? 0 ) + 1;
        $segment_revenue[ $c['segment'] ] = ( $segment_revenue[ $c['segment'] ] ?? 0 ) + $c['revenue'];
        $first_month = $c['first'] ? substr( $c['first'], 0, 7 ) : '';
        if ( $first_month ) {
            if ( isset( $monthly[ $first_month ] ) ) $monthly[ $first_month ]['new']++;
            if ( ! isset( $cohorts[ $first_month ] ) ) $cohorts[ $first_month ] = array( 'customers'=>0, 'repeat'=>0, 'revenue'=>0.0 );
            $cohorts[ $first_month ]['customers']++;
            $cohorts[ $first_month ]['revenue'] += $c['revenue'];
            if ( $c['orders'] > 1 ) $cohorts[ $first_month ]['repeat']++;
        }
        arsort( $c['products'] );
        $c['favorite_product'] = array_key_first( $c['products'] ) ?: '—';
    }
    unset( $c );

    foreach ( $monthly as &$m ) $m['unique_customers'] = count( $m['customers'] );
    unset( $m );
    foreach ( $cohorts as &$cohort ) $cohort['repeat_rate'] = $cohort['customers'] ? ( $cohort['repeat'] / $cohort['customers'] ) * 100 : 0;
    unset( $cohort );
    foreach ( $products as &$p ) { $p['customer_count'] = count( $p['customers'] ); unset( $p['customers'] ); }
    unset( $p );

    uasort( $customers, static function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    uasort( $products, static function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    ksort( $monthly );
    ksort( $cohorts );

    $customer_count = count( $customers );
    $data = array(
        'days' => $days,
        'customers' => $customers,
        'total_customers' => $customer_count,
        'orders' => $total_orders,
        'units' => $units,
        'revenue' => $revenue,
        'aov' => $total_orders ? $revenue / $total_orders : 0,
        'customer_value' => $customer_count ? $revenue / $customer_count : 0,
        'repeat_rate' => $customer_count ? ( $repeat / $customer_count ) * 100 : 0,
        'active_90' => $active_90,
        'churn_risk' => $churn_risk,
        'avg_reorder_days' => $interval_customers ? $interval_sum / $interval_customers : 0,
        'vip_threshold' => $vip_threshold,
        'segments' => $segments,
        'segment_revenue' => $segment_revenue,
        'monthly' => $monthly,
        'cohorts' => $cohorts,
        'products' => array_slice( $products, 0, 20, true ),
    );
    set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );
    return $data;
}

function wpbb_crm_recommendations( $s ) {
    $out = array();
    $at = (int) ( $s['segments']['At risk'] ?? 0 );
    $lap = (int) ( $s['segments']['Lapsed'] ?? 0 );
    $vip = (int) ( $s['segments']['VIP'] ?? 0 );
    if ( $at ) $out[] = sprintf( 'Run a 30–60 day win-back campaign for %d at-risk customers before they become lapsed.', $at );
    if ( $lap ) $out[] = sprintf( 'Create a reactivation offer for %d lapsed customers and exclude recent purchasers.', $lap );
    if ( $vip ) $out[] = sprintf( 'Protect %d VIP customers with early access, priority support or account-manager outreach.', $vip );
    if ( ( $s['repeat_rate'] ?? 0 ) < 25 ) $out[] = 'Repeat purchase rate is below 25%. Add post-purchase cross-sell and replenishment reminders.';
    if ( ! empty( $s['avg_reorder_days'] ) ) $out[] = 'Use the average reorder interval (' . round( $s['avg_reorder_days'] ) . ' days) as a baseline for replenishment and reminder campaigns.';
    if ( ! empty( $s['products'] ) ) {
        $top = reset( $s['products'] );
        if ( ! empty( $top['name'] ) ) $out[] = 'Your strongest product by attributed customer revenue is “' . $top['name'] . '”. Build bundles, ads and email content around its related purchases.';
    }
    if ( ! $out ) $out[] = 'Customer mix is healthy. Keep testing retention offers and compare segment revenue month over month.';
    return $out;
}

function wpbb_crm_export() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Denied' );
    check_admin_referer( 'wpbb_crm_export' );
    $s = wpbb_crm_collect( absint( $_GET['days'] ?? 365 ) );
    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=wpbb-crm-customers-' . gmdate( 'Ymd-His' ) . '.csv' );
    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'email','name','segment','rfm','orders','revenue','aov','last_order','days_since','avg_interval_days','predicted_next_order','favorite_product' ) );
    foreach ( $s['customers'] as $c ) {
        fputcsv( $out, array( $c['email'],$c['name'],$c['segment'],$c['rfm'],$c['orders'],$c['revenue'],$c['aov'],$c['last'],$c['days_since'],round($c['avg_interval'],1),$c['next_order'],$c['favorite_product'] ) );
    }
    fclose( $out );
    exit;
}

function wpbb_crm_render_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;
    $days = max( 30, min( 1095, absint( $_GET['days'] ?? 365 ) ) );
    $s = wpbb_crm_collect( $days );

    $segment_chart = array( array( 'Segment', 'Customers', 'Revenue' ) );
    foreach ( $s['segments'] as $seg => $count ) $segment_chart[] = array( $seg, (int) $count, (float) ( $s['segment_revenue'][ $seg ] ?? 0 ) );
    $monthly_chart = array( array( 'Month', 'Orders', 'New customers', 'Revenue' ) );
    foreach ( $s['monthly'] as $m => $v ) $monthly_chart[] = array( $m, (int) $v['orders'], (int) $v['new'], (float) $v['revenue'] );
    $product_chart = array( array( 'Product', 'Revenue' ) );
    foreach ( array_slice( $s['products'], 0, 10, true ) as $p ) $product_chart[] = array( wp_html_excerpt( $p['name'], 34, '…' ), (float) $p['revenue'] );
    $cohort_chart = array( array( 'Cohort', 'Customers', 'Repeat %' ) );
    foreach ( array_slice( $s['cohorts'], -12, null, true ) as $m => $c ) $cohort_chart[] = array( $m, (int) $c['customers'], (float) $c['repeat_rate'] );

    wp_localize_script( 'wpbb-crm-intelligence', 'WPBBCRM', array(
        'segments' => $segment_chart,
        'monthly' => $monthly_chart,
        'products' => $product_chart,
        'cohorts' => $cohort_chart,
        'currency' => get_woocommerce_currency_symbol(),
    ) );
    $top = array_slice( $s['customers'], 0, 100, true );
    ?>
    <div class="wrap wpbb-crm">
        <div class="wpbb-crm-head"><div><p class="eyebrow">WP BB LOCAL CRM</p><h1>CRM Intelligence</h1><p>First-party customer lifecycle, RFM, reorder and product intelligence calculated from WooCommerce orders.</p></div><form method="get"><input type="hidden" name="page" value="wpbb-crm-intelligence"><select name="days" onchange="this.form.submit()"><?php foreach ( array(30,90,180,365,730,1095) as $d ) : ?><option value="<?php echo esc_attr($d); ?>" <?php selected($days,$d); ?>><?php echo esc_html($d); ?> days</option><?php endforeach; ?></select></form></div>
        <div class="wpbb-crm-kpis">
            <div><span>Revenue</span><strong><?php echo wp_kses_post(wc_price($s['revenue'])); ?></strong></div>
            <div><span>Customers</span><strong><?php echo esc_html(number_format_i18n($s['total_customers'])); ?></strong><small><?php echo esc_html(number_format_i18n($s['active_90'])); ?> active ≤90d</small></div>
            <div><span>Orders</span><strong><?php echo esc_html(number_format_i18n($s['orders'])); ?></strong><small><?php echo esc_html(number_format_i18n($s['units'])); ?> units</small></div>
            <div><span>Average order</span><strong><?php echo wp_kses_post(wc_price($s['aov'])); ?></strong></div>
            <div><span>Customer value</span><strong><?php echo wp_kses_post(wc_price($s['customer_value'])); ?></strong></div>
            <div><span>Repeat rate</span><strong><?php echo esc_html(number_format_i18n($s['repeat_rate'],1)); ?>%</strong></div>
            <div><span>Avg reorder</span><strong><?php echo $s['avg_reorder_days'] ? esc_html(number_format_i18n($s['avg_reorder_days'],0) . 'd') : '—'; ?></strong></div>
            <div><span>Churn risk</span><strong><?php echo esc_html(number_format_i18n($s['churn_risk'])); ?></strong><small>at risk + lapsed</small></div>
        </div>

        <div class="wpbb-crm-grid"><section class="card"><h2>Customer segments & revenue</h2><div id="wpbb-crm-segments" class="chart"></div></section><section class="card"><h2>Customer acquisition & revenue</h2><div id="wpbb-crm-monthly" class="chart"></div></section></div>
        <div class="wpbb-crm-grid"><section class="card"><h2>Top products by customer revenue</h2><div id="wpbb-crm-products" class="chart"></div></section><section class="card"><h2>Acquisition cohorts & repeat rate</h2><div id="wpbb-crm-cohorts" class="chart"></div></section></div>

        <section class="card"><div class="card-head"><h2>Action centre</h2><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wpbb_crm_export&days='.$days),'wpbb_crm_export')); ?>">Export customer segments CSV</a></div><div class="actions"><?php foreach ( wpbb_crm_recommendations($s) as $r ) : ?><div><span>→</span><p><?php echo esc_html($r); ?></p></div><?php endforeach; ?></div></section>

        <div class="wpbb-crm-grid">
            <section class="card"><h2>Product performance</h2><div class="table-wrap"><table class="widefat striped"><thead><tr><th>Product</th><th>Units</th><th>Customers</th><th>Revenue</th></tr></thead><tbody><?php foreach ( array_slice($s['products'],0,12,true) as $p ) : ?><tr><td><strong><?php echo esc_html($p['name']); ?></strong></td><td><?php echo esc_html(number_format_i18n($p['qty'])); ?></td><td><?php echo esc_html(number_format_i18n($p['customer_count'])); ?></td><td><?php echo wp_kses_post(wc_price($p['revenue'])); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
            <section class="card"><h2>Customer cohorts</h2><div class="table-wrap"><table class="widefat striped"><thead><tr><th>First-order month</th><th>Customers</th><th>Repeat</th><th>Repeat rate</th><th>Revenue</th></tr></thead><tbody><?php foreach ( array_reverse(array_slice($s['cohorts'],-12,null,true),true) as $month=>$c ) : ?><tr><td><strong><?php echo esc_html($month); ?></strong></td><td><?php echo esc_html(number_format_i18n($c['customers'])); ?></td><td><?php echo esc_html(number_format_i18n($c['repeat'])); ?></td><td><?php echo esc_html(number_format_i18n($c['repeat_rate'],1)); ?>%</td><td><?php echo wp_kses_post(wc_price($c['revenue'])); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
        </div>

        <section class="card"><h2>Customer intelligence</h2><p class="subtle">RFM = Recency / Frequency / Monetary score. 555 represents a recent, frequent, high-value customer.</p><div class="table-wrap"><table class="widefat striped"><thead><tr><th>Customer</th><th>Segment</th><th>RFM</th><th>Orders</th><th>Revenue</th><th>AOV</th><th>Last order</th><th>Predicted next</th><th>Favourite product</th></tr></thead><tbody><?php foreach ( $top as $c ) : ?><tr><td><strong><?php echo esc_html($c['name'] ?: $c['email']); ?></strong><?php if($c['email']): ?><br><small><?php echo esc_html($c['email']); ?></small><?php endif; ?></td><td><span class="segment segment-<?php echo esc_attr(sanitize_html_class(strtolower(str_replace(' ','-',$c['segment'])))); ?>"><?php echo esc_html($c['segment']); ?></span></td><td><code><?php echo esc_html($c['rfm']); ?></code></td><td><?php echo esc_html($c['orders']); ?></td><td><?php echo wp_kses_post(wc_price($c['revenue'])); ?></td><td><?php echo wp_kses_post(wc_price($c['aov'])); ?></td><td><?php echo esc_html($c['last'] ?: '—'); ?><br><small><?php echo esc_html($c['days_since']); ?> days ago</small></td><td><?php echo esc_html($c['next_order']); ?></td><td><?php echo esc_html($c['favorite_product']); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    </div>
    <?php
}
