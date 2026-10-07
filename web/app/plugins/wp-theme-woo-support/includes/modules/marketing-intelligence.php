<?php
defined( 'ABSPATH' ) || exit;

/**
 * Marketing Intelligence for WooCommerce.
 * Tracks first/last-touch campaign parameters, attributes orders and exposes a
 * rich WooCommerce admin dashboard with trends, campaign/source performance,
 * product revenue and practical recommendations.
 */

function wpbb_mi_cookie_name() {
    return 'wpbb_mi_touch';
}

function wpbb_mi_capture_touch() {
    if ( is_admin() || wp_doing_ajax() || headers_sent() ) {
        return;
    }

    $keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'msclkid' );
    $touch = array();
    foreach ( $keys as $key ) {
        if ( isset( $_GET[ $key ] ) ) {
            $touch[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
        }
    }

    if ( empty( $touch ) ) {
        return;
    }

    $touch['landing'] = esc_url_raw( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) );
    $touch['referrer'] = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
    $touch['time'] = time();

    $encoded = rawurlencode( wp_json_encode( $touch ) );
    setcookie( wpbb_mi_cookie_name(), $encoded, time() + MONTH_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
    $_COOKIE[ wpbb_mi_cookie_name() ] = $encoded;

    if ( empty( $_COOKIE['wpbb_mi_first_touch'] ) ) {
        setcookie( 'wpbb_mi_first_touch', $encoded, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
        $_COOKIE['wpbb_mi_first_touch'] = $encoded;
    }
}
add_action( 'template_redirect', 'wpbb_mi_capture_touch', 1 );

function wpbb_mi_decode_cookie( $name ) {
    if ( empty( $_COOKIE[ $name ] ) ) {
        return array();
    }
    $raw = rawurldecode( wp_unslash( $_COOKIE[ $name ] ) );
    $data = json_decode( $raw, true );
    return is_array( $data ) ? $data : array();
}

function wpbb_mi_store_order_attribution( $order, $data = array() ) {
    if ( ! $order instanceof WC_Order ) {
        return;
    }
    $first = wpbb_mi_decode_cookie( 'wpbb_mi_first_touch' );
    $last  = wpbb_mi_decode_cookie( wpbb_mi_cookie_name() );
    foreach ( array( 'first' => $first, 'last' => $last ) as $prefix => $touch ) {
        foreach ( $touch as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $order->update_meta_data( '_wpbb_mi_' . $prefix . '_' . sanitize_key( $key ), sanitize_text_field( (string) $value ) );
            }
        }
    }
}
add_action( 'woocommerce_checkout_create_order', 'wpbb_mi_store_order_attribution', 20, 2 );

function wpbb_mi_order_query( $after, $before ) {
    return wc_get_orders( array(
        'limit'        => -1,
        'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
        'date_created' => $after . '...' . $before,
        'return'       => 'objects',
    ) );
}

function wpbb_mi_collect_stats( $days = 30 ) {
    $days = max( 7, min( 365, absint( $days ) ) );
    $tz = wp_timezone();
    $end = new DateTimeImmutable( 'now', $tz );
    $start = $end->modify( '-' . ( $days - 1 ) . ' days' )->setTime( 0, 0, 0 );
    $prev_end = $start->modify( '-1 second' );
    $prev_start = $prev_end->modify( '-' . ( $days - 1 ) . ' days' )->setTime( 0, 0, 0 );

    $orders = wpbb_mi_order_query( $start->format( 'Y-m-d H:i:s' ), $end->format( 'Y-m-d H:i:s' ) );
    $previous = wpbb_mi_order_query( $prev_start->format( 'Y-m-d H:i:s' ), $prev_end->format( 'Y-m-d H:i:s' ) );

    $daily = array();
    for ( $i = 0; $i < $days; $i++ ) {
        $k = $start->modify( '+' . $i . ' days' )->format( 'Y-m-d' );
        $daily[ $k ] = array( 'orders' => 0, 'revenue' => 0.0 );
    }

    $revenue = 0.0; $refunds = 0.0; $customers = array(); $sources = array(); $campaigns = array(); $products = array();
    foreach ( $orders as $order ) {
        $total = (float) $order->get_total();
        $revenue += $total;
        $refunds += abs( (float) $order->get_total_refunded() );
        $created = $order->get_date_created();
        $key = $created ? $created->date_i18n( 'Y-m-d' ) : '';
        if ( isset( $daily[ $key ] ) ) {
            $daily[ $key ]['orders']++;
            $daily[ $key ]['revenue'] += $total;
        }
        $email = strtolower( trim( (string) $order->get_billing_email() ) );
        if ( $email ) { $customers[ $email ] = true; }
        $source = (string) $order->get_meta( '_wpbb_mi_last_utm_source' );
        $campaign = (string) $order->get_meta( '_wpbb_mi_last_utm_campaign' );
        if ( ! $source ) { $source = 'Direct / unattributed'; }
        if ( ! isset( $sources[ $source ] ) ) { $sources[ $source ] = array( 'orders' => 0, 'revenue' => 0.0 ); }
        $sources[ $source ]['orders']++; $sources[ $source ]['revenue'] += $total;
        if ( $campaign ) {
            if ( ! isset( $campaigns[ $campaign ] ) ) { $campaigns[ $campaign ] = array( 'orders' => 0, 'revenue' => 0.0, 'source' => $source ); }
            $campaigns[ $campaign ]['orders']++; $campaigns[ $campaign ]['revenue'] += $total;
        }
        foreach ( $order->get_items() as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) { continue; }
            $pid = $item->get_product_id();
            $name = $item->get_name();
            if ( ! isset( $products[ $pid ] ) ) { $products[ $pid ] = array( 'name' => $name, 'qty' => 0, 'revenue' => 0.0 ); }
            $products[ $pid ]['qty'] += (int) $item->get_quantity();
            $products[ $pid ]['revenue'] += (float) $item->get_total();
        }
    }

    $prev_revenue = 0.0;
    foreach ( $previous as $order ) { $prev_revenue += (float) $order->get_total(); }
    $orders_count = count( $orders ); $prev_orders = count( $previous );
    uasort( $sources, fn($a,$b) => $b['revenue'] <=> $a['revenue'] );
    uasort( $campaigns, fn($a,$b) => $b['revenue'] <=> $a['revenue'] );
    uasort( $products, fn($a,$b) => $b['revenue'] <=> $a['revenue'] );

    $growth = $prev_revenue > 0 ? ( ( $revenue - $prev_revenue ) / $prev_revenue ) * 100 : ( $revenue > 0 ? 100 : 0 );
    $order_growth = $prev_orders > 0 ? ( ( $orders_count - $prev_orders ) / $prev_orders ) * 100 : ( $orders_count > 0 ? 100 : 0 );
    $aov = $orders_count ? $revenue / $orders_count : 0;

    $recommendations = array();
    if ( $growth < -10 ) $recommendations[] = array( 'high', 'Revenue is down ' . abs( round( $growth, 1 ) ) . '% vs the previous period. Re-activate recent buyers and run a focused offer on top products.' );
    if ( $growth > 10 ) $recommendations[] = array( 'good', 'Revenue is up ' . round( $growth, 1 ) . '%. Protect the momentum: repeat the strongest source/campaign and increase budget carefully.' );
    if ( empty( $campaigns ) ) $recommendations[] = array( 'medium', 'Campaign attribution is empty. Use the Campaign Link Builder below for social, email and paid campaigns so revenue can be tied to activity.' );
    $direct = $sources['Direct / unattributed']['revenue'] ?? 0;
    if ( $revenue > 0 && $direct / $revenue > 0.5 ) $recommendations[] = array( 'medium', 'More than half of revenue is unattributed. Standardise UTM links for Facebook, Instagram, TikTok, Google Ads and newsletters.' );
    if ( $aov > 0 && $aov < 50 ) $recommendations[] = array( 'medium', 'Average order value is relatively low. Test bundles, quantity breaks, free-shipping thresholds and related-product offers.' );
    if ( $refunds > 0 && $revenue > 0 && $refunds / $revenue > 0.08 ) $recommendations[] = array( 'high', 'Refund value is above 8% of gross revenue. Review the highest-refund products, delivery expectations and product descriptions.' );
    if ( empty( $recommendations ) ) $recommendations[] = array( 'good', 'Performance is stable. Keep campaign tracking consistent and test one change at a time so you can measure its effect.' );

    return compact( 'days','start','end','orders','previous','daily','revenue','refunds','customers','sources','campaigns','products','growth','order_growth','aov','recommendations' );
}

function wpbb_mi_admin_menu() {
    add_submenu_page( 'woocommerce', __( 'Marketing Intelligence', 'wp-theme-woo-support' ), __( 'Marketing Intelligence', 'wp-theme-woo-support' ), 'manage_woocommerce', 'wpbb-marketing-intelligence', 'wpbb_mi_render_page' );
}
add_action( 'admin_menu', 'wpbb_mi_admin_menu', 60 );

function wpbb_mi_admin_assets( $hook ) {
    if ( 'woocommerce_page_wpbb-marketing-intelligence' !== $hook ) return;
    wp_enqueue_style( 'wpbb-mi-admin', wp_theme_woo_support_url( 'assets/css/marketing-intelligence.css' ), array(), WP_THEME_WOO_SUPPORT_VERSION );
    wp_enqueue_script( 'google-charts', 'https://www.gstatic.com/charts/loader.js', array(), null, true );
    wp_enqueue_script( 'wpbb-mi-admin', wp_theme_woo_support_url( 'assets/js/marketing-intelligence.js' ), array( 'google-charts' ), WP_THEME_WOO_SUPPORT_VERSION, true );

    $days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
    $s = wpbb_mi_collect_stats( $days );
    $revenue_trend = array( array( 'Date', 'Revenue' ) );
    $orders_trend = array( array( 'Date', 'Orders', 'Revenue' ) );
    foreach ( $s['daily'] as $date => $row ) {
        $label = wp_date( 'M j', strtotime( $date ) );
        $revenue_trend[] = array( $label, round( $row['revenue'], 2 ) );
        $orders_trend[] = array( $label, (int) $row['orders'], round( $row['revenue'], 2 ) );
    }
    $source_revenue = array( array( 'Source', 'Revenue' ) );
    foreach ( array_slice( $s['sources'], 0, 8, true ) as $name => $row ) $source_revenue[] = array( $name, round( $row['revenue'], 2 ) );
    $product_revenue = array( array( 'Product', 'Revenue' ) );
    foreach ( array_slice( $s['products'], 0, 8, true ) as $row ) $product_revenue[] = array( wp_trim_words( $row['name'], 7, '…' ), round( $row['revenue'], 2 ) );
    wp_localize_script( 'wpbb-mi-admin', 'WPBBWooMarketing', array(
        'revenueTrend' => $revenue_trend,
        'ordersTrend' => $orders_trend,
        'sourceRevenue' => $source_revenue,
        'productRevenue' => $product_revenue,
        'currencySymbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'wpbb_mi_admin_assets' );

function wpbb_mi_delta( $value ) {
    $class = $value > 0 ? 'up' : ( $value < 0 ? 'down' : 'flat' );
    $arrow = $value > 0 ? '↑' : ( $value < 0 ? '↓' : '→' );
    return '<span class="wpbb-mi-delta ' . esc_attr( $class ) . '">' . esc_html( $arrow . ' ' . number_format_i18n( abs( $value ), 1 ) . '%' ) . '</span>';
}

function wpbb_mi_render_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;
    $days = isset( $_GET['days'] ) ? max( 7, min( 365, absint( $_GET['days'] ) ) ) : 30;
    $s = wpbb_mi_collect_stats( $days );
    $orders = count( $s['orders'] );
    $currency = get_woocommerce_currency_symbol();
    $base = admin_url( 'admin.php?page=wpbb-marketing-intelligence' );
    ?>
    <div class="wrap wpbb-mi-wrap">
      <div class="wpbb-mi-head"><div><h1>WooCommerce Marketing Intelligence</h1><p>Revenue, campaign attribution, product performance and recommendations — inside WP Theme Woo Support.</p></div><div class="wpbb-mi-range"><?php foreach ( array( 7,30,90,365 ) as $d ) : ?><a class="<?php echo $days === $d ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'days', $d, $base ) ); ?>"><?php echo esc_html( $d ); ?>d</a><?php endforeach; ?></div></div>
      <div class="wpbb-mi-grid wpbb-mi-kpis">
        <div class="wpbb-mi-card"><span>Revenue</span><strong><?php echo wp_kses_post( wc_price( $s['revenue'] ) ); ?></strong><?php echo wp_kses_post( wpbb_mi_delta( $s['growth'] ) ); ?></div>
        <div class="wpbb-mi-card"><span>Orders</span><strong><?php echo esc_html( number_format_i18n( $orders ) ); ?></strong><?php echo wp_kses_post( wpbb_mi_delta( $s['order_growth'] ) ); ?></div>
        <div class="wpbb-mi-card"><span>Average order value</span><strong><?php echo wp_kses_post( wc_price( $s['aov'] ) ); ?></strong><small>for selected period</small></div>
        <div class="wpbb-mi-card"><span>Customers</span><strong><?php echo esc_html( number_format_i18n( count( $s['customers'] ) ) ); ?></strong><small>unique billing emails</small></div>
        <div class="wpbb-mi-card"><span>Refunded</span><strong><?php echo wp_kses_post( wc_price( $s['refunds'] ) ); ?></strong><small>refunded order value</small></div>
      </div>
      <div class="wpbb-mi-grid wpbb-mi-two"><section class="wpbb-mi-panel"><h2>Revenue trend</h2><div id="wpbb-mi-revenue-chart" class="wpbb-mi-chart"></div></section><section class="wpbb-mi-panel"><h2>Orders & revenue</h2><div id="wpbb-mi-orders-chart" class="wpbb-mi-chart"></div></section></div>
      <div class="wpbb-mi-grid wpbb-mi-two"><section class="wpbb-mi-panel"><h2>Revenue by marketing source</h2><div id="wpbb-mi-source-chart" class="wpbb-mi-chart"></div></section><section class="wpbb-mi-panel"><h2>Top products by revenue</h2><div id="wpbb-mi-products-chart" class="wpbb-mi-chart"></div></section></div>
      <section class="wpbb-mi-panel"><h2>Recommendations</h2><div class="wpbb-mi-recs"><?php foreach ( $s['recommendations'] as $rec ) : ?><div class="wpbb-mi-rec <?php echo esc_attr( $rec[0] ); ?>"><b><?php echo 'good' === $rec[0] ? 'Opportunity' : ( 'high' === $rec[0] ? 'Priority' : 'Improve' ); ?></b><span><?php echo esc_html( $rec[1] ); ?></span></div><?php endforeach; ?></div></section>
      <div class="wpbb-mi-grid wpbb-mi-two">
        <section class="wpbb-mi-panel"><h2>Campaign performance</h2><table class="widefat striped"><thead><tr><th>Campaign</th><th>Source</th><th>Orders</th><th>Revenue</th></tr></thead><tbody><?php if ( $s['campaigns'] ) : foreach ( array_slice( $s['campaigns'], 0, 20, true ) as $name => $row ) : ?><tr><td><strong><?php echo esc_html( $name ); ?></strong></td><td><?php echo esc_html( $row['source'] ); ?></td><td><?php echo esc_html( $row['orders'] ); ?></td><td><?php echo wp_kses_post( wc_price( $row['revenue'] ) ); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4">No tracked campaigns yet.</td></tr><?php endif; ?></tbody></table></section>
        <section class="wpbb-mi-panel"><h2>Campaign Link Builder</h2><p>Create trackable links for social, email and paid campaigns.</p><form class="wpbb-mi-builder" onsubmit="return false;"><label>Destination URL<input id="wpbb-mi-url" type="url" value="<?php echo esc_attr( home_url( '/' ) ); ?>"></label><label>Source<input id="wpbb-mi-source" type="text" placeholder="facebook, instagram, newsletter"></label><label>Medium<input id="wpbb-mi-medium" type="text" placeholder="social, email, cpc"></label><label>Campaign<input id="wpbb-mi-campaign" type="text" placeholder="spring-sale"></label><button type="button" class="button button-primary" id="wpbb-mi-build">Build tracked URL</button><div class="wpbb-mi-built"><input id="wpbb-mi-result" readonly><button type="button" class="button wpbb-mi-copy" data-copy="">Copy</button></div></form></section>
      </div>
      <section class="wpbb-mi-panel"><h2>Marketing source detail</h2><table class="widefat striped"><thead><tr><th>Source</th><th>Orders</th><th>Revenue</th><th>Share</th></tr></thead><tbody><?php foreach ( array_slice( $s['sources'], 0, 25, true ) as $name => $row ) : $share = $s['revenue'] > 0 ? ( $row['revenue'] / $s['revenue'] ) * 100 : 0; ?><tr><td><strong><?php echo esc_html( $name ); ?></strong></td><td><?php echo esc_html( $row['orders'] ); ?></td><td><?php echo wp_kses_post( wc_price( $row['revenue'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $share, 1 ) . '%' ); ?></td></tr><?php endforeach; ?></tbody></table></section>
    </div>
    <?php
}
