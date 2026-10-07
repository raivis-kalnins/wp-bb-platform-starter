<?php
defined( 'ABSPATH' ) || exit;

/**
 * Marketing Intelligence for WooCommerce.
 *
 * The module intentionally stays focused on commerce intelligence instead of
 * becoming a second email CRM. It borrows the useful reporting patterns found
 * in mature CRM tools: campaign revenue, tracked visits, customer lifecycle,
 * product momentum, purchase affinity, promotions and actionable next steps.
 */

function wpbb_mi_cookie_name() {
    return 'wpbb_mi_touch';
}

function wpbb_mi_tracking_allowed() {
    $allowed = true;
    if ( function_exists( 'wp_has_consent' ) ) {
        $allowed = (bool) wp_has_consent( 'statistics' );
    }
    return (bool) apply_filters( 'wpbb_mi_tracking_allowed', $allowed );
}

function wpbb_mi_campaign_table() {
    global $wpdb;
    return $wpdb->prefix . 'wpbb_mi_campaign_daily';
}

function wpbb_mi_table_exists( $table ) {
    global $wpdb;
    static $known = array();

    if ( isset( $known[ $table ] ) ) {
        return $known[ $table ];
    }

    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    $known[ $table ] = $found === $table;
    return $known[ $table ];
}

function wpbb_mi_maybe_install_schema() {
    $schema_version = '1.0.0';
    if ( get_option( 'wpbb_mi_schema_version' ) === $schema_version && wpbb_mi_table_exists( wpbb_mi_campaign_table() ) ) {
        return;
    }

    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table = wpbb_mi_campaign_table();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        stat_date date NOT NULL,
        dimension_hash char(32) NOT NULL,
        source varchar(191) NOT NULL DEFAULT '',
        medium varchar(191) NOT NULL DEFAULT '',
        campaign varchar(191) NOT NULL DEFAULT '',
        content varchar(191) NOT NULL DEFAULT '',
        visits bigint(20) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY day_hash (stat_date, dimension_hash),
        KEY campaign (campaign(100)),
        KEY source (source(100))
    ) {$charset_collate};";

    dbDelta( $sql );
    update_option( 'wpbb_mi_schema_version', $schema_version, false );
}
add_action( 'init', 'wpbb_mi_maybe_install_schema', 1 );

function wpbb_mi_trim_dimension( $value, $length = 180 ) {
    $value = sanitize_text_field( (string) $value );
    if ( function_exists( 'mb_substr' ) ) {
        return mb_substr( $value, 0, $length );
    }
    return substr( $value, 0, $length );
}

function wpbb_mi_normalize_touch( $touch ) {
    $touch = is_array( $touch ) ? $touch : array();

    if ( empty( $touch['utm_source'] ) ) {
        if ( ! empty( $touch['gclid'] ) ) {
            $touch['utm_source'] = 'google';
        } elseif ( ! empty( $touch['fbclid'] ) ) {
            $touch['utm_source'] = 'facebook';
        } elseif ( ! empty( $touch['msclkid'] ) ) {
            $touch['utm_source'] = 'bing';
        }
    }

    if ( empty( $touch['utm_medium'] ) && ( ! empty( $touch['gclid'] ) || ! empty( $touch['fbclid'] ) || ! empty( $touch['msclkid'] ) ) ) {
        $touch['utm_medium'] = 'cpc';
    }

    if ( empty( $touch['utm_campaign'] ) && ( ! empty( $touch['gclid'] ) || ! empty( $touch['fbclid'] ) || ! empty( $touch['msclkid'] ) ) ) {
        $touch['utm_campaign'] = 'untagged-ad-click';
    }

    return $touch;
}

function wpbb_mi_record_campaign_visit( $touch ) {
    if ( ! wpbb_mi_tracking_allowed() ) {
        return;
    }

    $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    if ( $user_agent && preg_match( '/bot|crawler|spider|slurp|bingpreview|facebookexternalhit/i', $user_agent ) ) {
        return;
    }
    if ( ! wpbb_mi_table_exists( wpbb_mi_campaign_table() ) ) {
        wpbb_mi_maybe_install_schema();
    }

    $source   = wpbb_mi_trim_dimension( $touch['utm_source'] ?? '' );
    $medium   = wpbb_mi_trim_dimension( $touch['utm_medium'] ?? '' );
    $campaign = wpbb_mi_trim_dimension( $touch['utm_campaign'] ?? '' );
    $content  = wpbb_mi_trim_dimension( $touch['utm_content'] ?? '' );

    if ( '' === $source && '' === $medium && '' === $campaign ) {
        return;
    }

    $hash = md5( strtolower( $source . '|' . $medium . '|' . $campaign . '|' . $content ) );
    if ( ! empty( $_COOKIE['wpbb_mi_visit_seen'] ) && hash_equals( $hash, sanitize_text_field( wp_unslash( $_COOKIE['wpbb_mi_visit_seen'] ) ) ) ) {
        return;
    }

    global $wpdb;
    $table = wpbb_mi_campaign_table();
    $day = wp_date( 'Y-m-d' );

    $wpdb->query(
        $wpdb->prepare(
            "INSERT INTO {$table} (stat_date, dimension_hash, source, medium, campaign, content, visits)
             VALUES (%s, %s, %s, %s, %s, %s, 1)
             ON DUPLICATE KEY UPDATE visits = visits + 1",
            $day,
            $hash,
            $source,
            $medium,
            $campaign,
            $content
        )
    );

    if ( ! headers_sent() ) {
        setcookie( 'wpbb_mi_visit_seen', $hash, time() + ( 30 * MINUTE_IN_SECONDS ), COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
        $_COOKIE['wpbb_mi_visit_seen'] = $hash;
    }
}

function wpbb_mi_capture_touch() {
    if ( ! wpbb_mi_tracking_allowed() ) {
        return;
    }
    if ( is_admin() || wp_doing_ajax() || headers_sent() ) {
        return;
    }

    $keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid', 'msclkid' );
    $touch = array();
    foreach ( $keys as $key ) {
        if ( isset( $_GET[ $key ] ) ) {
            $touch[ $key ] = wpbb_mi_trim_dimension( wp_unslash( $_GET[ $key ] ) );
        }
    }

    if ( empty( $touch ) ) {
        return;
    }

    $touch = wpbb_mi_normalize_touch( $touch );
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

    wpbb_mi_record_campaign_visit( $touch );
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
    if ( ! wpbb_mi_tracking_allowed() ) {
        return;
    }
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

function wpbb_mi_order_statuses() {
    return array( 'wc-processing', 'wc-completed', 'wc-on-hold' );
}

function wpbb_mi_wc_query_statuses() {
    return array( 'processing', 'completed', 'on-hold' );
}

function wpbb_mi_each_order( $after, $before, $callback ) {
    $page = 1;
    $max_pages = 1;

    do {
        $result = wc_get_orders(
            array(
                'limit'        => 200,
                'page'         => $page,
                'paginate'     => true,
                'status'       => wpbb_mi_wc_query_statuses(),
                'date_created' => $after . '...' . $before,
                'orderby'      => 'date',
                'order'        => 'ASC',
                'return'       => 'objects',
            )
        );

        $orders = is_object( $result ) && isset( $result->orders ) ? $result->orders : array();
        $max_pages = is_object( $result ) && isset( $result->max_num_pages ) ? max( 1, (int) $result->max_num_pages ) : 1;

        foreach ( $orders as $order ) {
            if ( $order instanceof WC_Order ) {
                call_user_func( $callback, $order );
            }
        }

        $page++;
    } while ( $page <= $max_pages );
}

function wpbb_mi_get_campaign_costs() {
    $costs = get_option( 'wpbb_mi_campaign_costs', array() );
    return is_array( $costs ) ? $costs : array();
}

function wpbb_mi_get_campaign_visit_stats( $start, $end ) {
    $empty = array( 'total' => 0, 'campaigns' => array(), 'sources' => array() );
    $table = wpbb_mi_campaign_table();
    if ( ! wpbb_mi_table_exists( $table ) ) {
        return $empty;
    }

    global $wpdb;
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT source, medium, campaign, SUM(visits) AS visits
             FROM {$table}
             WHERE stat_date >= %s AND stat_date <= %s
             GROUP BY source, medium, campaign
             ORDER BY visits DESC",
            $start->format( 'Y-m-d' ),
            $end->format( 'Y-m-d' )
        ),
        ARRAY_A
    );

    $result = $empty;
    foreach ( (array) $rows as $row ) {
        $source = (string) $row['source'];
        $medium = (string) $row['medium'];
        $campaign = (string) $row['campaign'];
        $visits = (int) $row['visits'];
        $result['total'] += $visits;

        $source_key = $source ?: 'Tracked / unknown source';
        if ( ! isset( $result['sources'][ $source_key ] ) ) {
            $result['sources'][ $source_key ] = 0;
        }
        $result['sources'][ $source_key ] += $visits;

        if ( $campaign ) {
            if ( ! isset( $result['campaigns'][ $campaign ] ) ) {
                $result['campaigns'][ $campaign ] = array( 'visits' => 0, 'sources' => array(), 'mediums' => array() );
            }
            $result['campaigns'][ $campaign ]['visits'] += $visits;
            if ( $source ) {
                $result['campaigns'][ $campaign ]['sources'][ $source ] = true;
            }
            if ( $medium ) {
                $result['campaigns'][ $campaign ]['mediums'][ $medium ] = true;
            }
        }
    }

    return $result;
}

function wpbb_mi_lookup_tables_ready() {
    global $wpdb;
    return wpbb_mi_table_exists( $wpdb->prefix . 'wc_order_stats' ) && wpbb_mi_table_exists( $wpdb->prefix . 'wc_customer_lookup' );
}

function wpbb_mi_lookup_status_sql() {
    $statuses = wpbb_mi_order_statuses();
    return array( $statuses, implode( ',', array_fill( 0, count( $statuses ), '%s' ) ) );
}

function wpbb_mi_new_returning_summary( $start, $end ) {
    $summary = array(
        'new'       => array( 'orders' => 0, 'revenue' => 0.0 ),
        'returning' => array( 'orders' => 0, 'revenue' => 0.0 ),
    );

    if ( ! wpbb_mi_lookup_tables_ready() ) {
        return $summary;
    }

    global $wpdb;
    list( $statuses, $status_sql ) = wpbb_mi_lookup_status_sql();
    $table = $wpdb->prefix . 'wc_order_stats';
    $sql = "SELECT returning_customer, COUNT(order_id) AS orders_count, SUM(total_sales) AS revenue
            FROM {$table}
            WHERE date_created >= %s AND date_created <= %s
              AND status IN ({$status_sql})
            GROUP BY returning_customer";
    $args = array_merge( array( $start->format( 'Y-m-d H:i:s' ), $end->format( 'Y-m-d H:i:s' ) ), $statuses );
    $rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );

    foreach ( (array) $rows as $row ) {
        $key = ! empty( $row['returning_customer'] ) ? 'returning' : 'new';
        $summary[ $key ]['orders'] += (int) $row['orders_count'];
        $summary[ $key ]['revenue'] += (float) $row['revenue'];
    }

    return $summary;
}

function wpbb_mi_customer_lifecycle() {
    $empty = array(
        'available'       => false,
        'customers'       => 0,
        'one_time'        => 0,
        'repeat'          => 0,
        'loyal'           => 0,
        'vip'             => 0,
        'active_60'       => 0,
        'at_risk'         => 0,
        'lapsed'          => 0,
        'top_customers'   => array(),
    );

    if ( ! wpbb_mi_lookup_tables_ready() ) {
        return $empty;
    }

    global $wpdb;
    list( $statuses, $status_sql ) = wpbb_mi_lookup_status_sql();
    $orders = $wpdb->prefix . 'wc_order_stats';
    $customers = $wpdb->prefix . 'wc_customer_lookup';
    $now = current_time( 'mysql' );

    $subquery = "SELECT customer_id,
                        COUNT(order_id) AS orders_count,
                        SUM(total_sales) AS lifetime_value,
                        MIN(date_created) AS first_order,
                        MAX(date_created) AS last_order
                 FROM {$orders}
                 WHERE customer_id > 0 AND status IN ({$status_sql})
                 GROUP BY customer_id";

    $summary_sql = "SELECT COUNT(*) AS customers,
                           SUM(CASE WHEN orders_count = 1 THEN 1 ELSE 0 END) AS one_time,
                           SUM(CASE WHEN orders_count >= 2 THEN 1 ELSE 0 END) AS repeat_customers,
                           SUM(CASE WHEN orders_count >= 3 THEN 1 ELSE 0 END) AS loyal,
                           SUM(CASE WHEN orders_count >= 5 THEN 1 ELSE 0 END) AS vip,
                           SUM(CASE WHEN last_order >= DATE_SUB(%s, INTERVAL 60 DAY) THEN 1 ELSE 0 END) AS active_60,
                           SUM(CASE WHEN last_order < DATE_SUB(%s, INTERVAL 60 DAY) AND last_order >= DATE_SUB(%s, INTERVAL 180 DAY) THEN 1 ELSE 0 END) AS at_risk,
                           SUM(CASE WHEN last_order < DATE_SUB(%s, INTERVAL 180 DAY) THEN 1 ELSE 0 END) AS lapsed
                    FROM ({$subquery}) life";
    $summary_args = array_merge( array( $now, $now, $now, $now ), $statuses );
    $row = $wpdb->get_row( $wpdb->prepare( $summary_sql, ...$summary_args ), ARRAY_A );

    if ( ! $row ) {
        return $empty;
    }

    $top_sql = "SELECT c.customer_id, c.email, c.first_name, c.last_name,
                       life.orders_count, life.lifetime_value, life.first_order, life.last_order
                FROM ({$subquery}) life
                INNER JOIN {$customers} c ON c.customer_id = life.customer_id
                ORDER BY life.lifetime_value DESC
                LIMIT 10";
    $top_rows = $wpdb->get_results( $wpdb->prepare( $top_sql, ...$statuses ), ARRAY_A );

    return array(
        'available'     => true,
        'customers'     => (int) $row['customers'],
        'one_time'      => (int) $row['one_time'],
        'repeat'        => (int) $row['repeat_customers'],
        'loyal'         => (int) $row['loyal'],
        'vip'           => (int) $row['vip'],
        'active_60'     => (int) $row['active_60'],
        'at_risk'       => (int) $row['at_risk'],
        'lapsed'        => (int) $row['lapsed'],
        'top_customers' => is_array( $top_rows ) ? $top_rows : array(),
    );
}

function wpbb_mi_percent_change( $current, $previous ) {
    $current = (float) $current;
    $previous = (float) $previous;
    if ( $previous > 0 ) {
        return ( ( $current - $previous ) / $previous ) * 100;
    }
    return $current > 0 ? 100.0 : 0.0;
}

function wpbb_mi_collect_stats( $days = 30 ) {
    static $request_cache = array();

    $days = max( 7, min( 365, absint( $days ) ) );
    if ( isset( $request_cache[ $days ] ) ) {
        return $request_cache[ $days ];
    }

    $tz = wp_timezone();
    $end = new DateTimeImmutable( 'now', $tz );
    $start = $end->modify( '-' . ( $days - 1 ) . ' days' )->setTime( 0, 0, 0 );
    $prev_end = $start->modify( '-1 second' );
    $prev_start = $prev_end->modify( '-' . ( $days - 1 ) . ' days' )->setTime( 0, 0, 0 );

    $daily = array();
    for ( $i = 0; $i < $days; $i++ ) {
        $key = $start->modify( '+' . $i . ' days' )->format( 'Y-m-d' );
        $daily[ $key ] = array( 'orders' => 0, 'revenue' => 0.0 );
    }

    $revenue = 0.0;
    $refunds = 0.0;
    $orders_count = 0;
    $customers = array();
    $sources = array();
    $first_sources = array();
    $campaigns = array();
    $mediums = array();
    $products = array();
    $coupons = array();
    $pairs = array();

    wpbb_mi_each_order(
        $start->format( 'Y-m-d H:i:s' ),
        $end->format( 'Y-m-d H:i:s' ),
        function( $order ) use ( &$revenue, &$refunds, &$orders_count, &$customers, &$sources, &$first_sources, &$campaigns, &$mediums, &$products, &$coupons, &$pairs, &$daily ) {
            $orders_count++;
            $total = (float) $order->get_total();
            $revenue += $total;
            $refunds += abs( (float) $order->get_total_refunded() );

            $created = $order->get_date_created();
            $date_key = $created ? $created->date( 'Y-m-d' ) : '';
            if ( isset( $daily[ $date_key ] ) ) {
                $daily[ $date_key ]['orders']++;
                $daily[ $date_key ]['revenue'] += $total;
            }

            $email = strtolower( trim( (string) $order->get_billing_email() ) );
            if ( $email ) {
                if ( ! isset( $customers[ $email ] ) ) {
                    $customers[ $email ] = array(
                        'name'    => trim( $order->get_formatted_billing_full_name() ),
                        'email'   => $email,
                        'orders'  => 0,
                        'revenue' => 0.0,
                        'last'    => '',
                    );
                }
                $customers[ $email ]['orders']++;
                $customers[ $email ]['revenue'] += $total;
                if ( $created ) {
                    $customers[ $email ]['last'] = $created->date( 'Y-m-d H:i:s' );
                }
            }

            $source = trim( (string) $order->get_meta( '_wpbb_mi_last_utm_source' ) );
            $first_source = trim( (string) $order->get_meta( '_wpbb_mi_first_utm_source' ) );
            $medium = trim( (string) $order->get_meta( '_wpbb_mi_last_utm_medium' ) );
            $campaign = trim( (string) $order->get_meta( '_wpbb_mi_last_utm_campaign' ) );

            $source = $source ?: 'Direct / unattributed';
            $first_source = $first_source ?: 'Direct / unattributed';
            $medium_key = $medium ?: '(none)';

            if ( ! isset( $sources[ $source ] ) ) {
                $sources[ $source ] = array( 'orders' => 0, 'revenue' => 0.0, 'visits' => 0 );
            }
            $sources[ $source ]['orders']++;
            $sources[ $source ]['revenue'] += $total;

            if ( ! isset( $first_sources[ $first_source ] ) ) {
                $first_sources[ $first_source ] = array( 'orders' => 0, 'revenue' => 0.0 );
            }
            $first_sources[ $first_source ]['orders']++;
            $first_sources[ $first_source ]['revenue'] += $total;

            if ( ! isset( $mediums[ $medium_key ] ) ) {
                $mediums[ $medium_key ] = array( 'orders' => 0, 'revenue' => 0.0 );
            }
            $mediums[ $medium_key ]['orders']++;
            $mediums[ $medium_key ]['revenue'] += $total;

            if ( $campaign ) {
                if ( ! isset( $campaigns[ $campaign ] ) ) {
                    $campaigns[ $campaign ] = array(
                        'orders'  => 0,
                        'revenue' => 0.0,
                        'visits'  => 0,
                        'sources' => array(),
                        'mediums' => array(),
                    );
                }
                $campaigns[ $campaign ]['orders']++;
                $campaigns[ $campaign ]['revenue'] += $total;
                if ( $source && 'Direct / unattributed' !== $source ) {
                    $campaigns[ $campaign ]['sources'][ $source ] = true;
                }
                if ( $medium ) {
                    $campaigns[ $campaign ]['mediums'][ $medium ] = true;
                }
            }

            $order_product_ids = array();
            $order_product_names = array();
            foreach ( $order->get_items() as $item ) {
                if ( ! $item instanceof WC_Order_Item_Product ) {
                    continue;
                }
                $pid = (int) $item->get_product_id();
                if ( ! $pid ) {
                    continue;
                }
                $name = $item->get_name();
                if ( ! isset( $products[ $pid ] ) ) {
                    $products[ $pid ] = array( 'name' => $name, 'qty' => 0, 'revenue' => 0.0 );
                }
                $products[ $pid ]['qty'] += (int) $item->get_quantity();
                $products[ $pid ]['revenue'] += (float) $item->get_total();
                $order_product_ids[ $pid ] = $pid;
                $order_product_names[ $pid ] = $name;
            }

            $pair_ids = array_slice( array_values( $order_product_ids ), 0, 12 );
            sort( $pair_ids, SORT_NUMERIC );
            $pair_count = count( $pair_ids );
            for ( $i = 0; $i < $pair_count; $i++ ) {
                for ( $j = $i + 1; $j < $pair_count; $j++ ) {
                    $pair_key = $pair_ids[ $i ] . ':' . $pair_ids[ $j ];
                    if ( ! isset( $pairs[ $pair_key ] ) ) {
                        $pairs[ $pair_key ] = array(
                            'a'     => $order_product_names[ $pair_ids[ $i ] ] ?? get_the_title( $pair_ids[ $i ] ),
                            'b'     => $order_product_names[ $pair_ids[ $j ] ] ?? get_the_title( $pair_ids[ $j ] ),
                            'count' => 0,
                        );
                    }
                    $pairs[ $pair_key ]['count']++;
                }
            }

            foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
                if ( ! $coupon_item instanceof WC_Order_Item_Coupon ) {
                    continue;
                }
                $code = strtolower( trim( (string) $coupon_item->get_code() ) );
                if ( ! $code ) {
                    continue;
                }
                if ( ! isset( $coupons[ $code ] ) ) {
                    $coupons[ $code ] = array( 'orders' => 0, 'discount' => 0.0, 'revenue' => 0.0 );
                }
                $coupons[ $code ]['orders']++;
                $coupons[ $code ]['discount'] += (float) $coupon_item->get_discount() + (float) $coupon_item->get_discount_tax();
                $coupons[ $code ]['revenue'] += $total;
            }
        }
    );

    $prev_revenue = 0.0;
    $prev_orders = 0;
    $previous_products = array();
    $previous_sources = array();

    wpbb_mi_each_order(
        $prev_start->format( 'Y-m-d H:i:s' ),
        $prev_end->format( 'Y-m-d H:i:s' ),
        function( $order ) use ( &$prev_revenue, &$prev_orders, &$previous_products, &$previous_sources ) {
            $prev_orders++;
            $total = (float) $order->get_total();
            $prev_revenue += $total;

            $source = trim( (string) $order->get_meta( '_wpbb_mi_last_utm_source' ) );
            $source = $source ?: 'Direct / unattributed';
            if ( ! isset( $previous_sources[ $source ] ) ) {
                $previous_sources[ $source ] = array( 'orders' => 0, 'revenue' => 0.0 );
            }
            $previous_sources[ $source ]['orders']++;
            $previous_sources[ $source ]['revenue'] += $total;

            foreach ( $order->get_items() as $item ) {
                if ( ! $item instanceof WC_Order_Item_Product ) {
                    continue;
                }
                $pid = (int) $item->get_product_id();
                if ( ! $pid ) {
                    continue;
                }
                if ( ! isset( $previous_products[ $pid ] ) ) {
                    $previous_products[ $pid ] = array( 'name' => $item->get_name(), 'qty' => 0, 'revenue' => 0.0 );
                }
                $previous_products[ $pid ]['qty'] += (int) $item->get_quantity();
                $previous_products[ $pid ]['revenue'] += (float) $item->get_total();
            }
        }
    );

    $visit_stats = wpbb_mi_get_campaign_visit_stats( $start, $end );
    foreach ( $visit_stats['sources'] as $source => $visits ) {
        if ( ! isset( $sources[ $source ] ) ) {
            $sources[ $source ] = array( 'orders' => 0, 'revenue' => 0.0, 'visits' => 0 );
        }
        $sources[ $source ]['visits'] += (int) $visits;
    }

    foreach ( $visit_stats['campaigns'] as $campaign => $row ) {
        if ( ! isset( $campaigns[ $campaign ] ) ) {
            $campaigns[ $campaign ] = array( 'orders' => 0, 'revenue' => 0.0, 'visits' => 0, 'sources' => array(), 'mediums' => array() );
        }
        $campaigns[ $campaign ]['visits'] += (int) $row['visits'];
        $campaigns[ $campaign ]['sources'] = array_replace( $campaigns[ $campaign ]['sources'], $row['sources'] );
        $campaigns[ $campaign ]['mediums'] = array_replace( $campaigns[ $campaign ]['mediums'], $row['mediums'] );
    }

    $costs = wpbb_mi_get_campaign_costs();
    foreach ( $campaigns as $campaign => &$row ) {
        $row['source'] = implode( ', ', array_keys( $row['sources'] ) );
        $row['medium'] = implode( ', ', array_keys( $row['mediums'] ) );
        $row['spend'] = isset( $costs[ $campaign ] ) ? (float) $costs[ $campaign ] : 0.0;
        $row['conversion'] = $row['visits'] > 0 ? ( $row['orders'] / $row['visits'] ) * 100 : 0.0;
        $row['roas'] = $row['spend'] > 0 ? $row['revenue'] / $row['spend'] : 0.0;
        $row['cpa'] = $row['orders'] > 0 && $row['spend'] > 0 ? $row['spend'] / $row['orders'] : 0.0;
        unset( $row['sources'], $row['mediums'] );
    }
    unset( $row );

    foreach ( $sources as $source => &$row ) {
        $previous = $previous_sources[ $source ]['revenue'] ?? 0.0;
        $row['previous_revenue'] = $previous;
        $row['growth'] = wpbb_mi_percent_change( $row['revenue'], $previous );
        $row['conversion'] = $row['visits'] > 0 ? ( $row['orders'] / $row['visits'] ) * 100 : 0.0;
    }
    unset( $row );

    uasort( $sources, function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    uasort( $first_sources, function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    uasort( $mediums, function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    uasort( $campaigns, function( $a, $b ) {
        if ( (float) $a['revenue'] === (float) $b['revenue'] ) {
            return $b['visits'] <=> $a['visits'];
        }
        return $b['revenue'] <=> $a['revenue'];
    } );
    uasort( $products, function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    uasort( $customers, function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    uasort( $coupons, function( $a, $b ) { return $b['revenue'] <=> $a['revenue']; } );
    uasort( $pairs, function( $a, $b ) { return $b['count'] <=> $a['count']; } );

    $product_momentum = array();
    $product_ids = array_unique( array_merge( array_keys( $products ), array_keys( $previous_products ) ) );
    foreach ( $product_ids as $pid ) {
        $current = $products[ $pid ]['revenue'] ?? 0.0;
        $previous = $previous_products[ $pid ]['revenue'] ?? 0.0;
        $product_momentum[ $pid ] = array(
            'name'     => $products[ $pid ]['name'] ?? ( $previous_products[ $pid ]['name'] ?? get_the_title( $pid ) ),
            'current'  => $current,
            'previous' => $previous,
            'delta'    => $current - $previous,
            'growth'   => wpbb_mi_percent_change( $current, $previous ),
        );
    }

    $rising_products = array_values( array_filter( $product_momentum, function( $row ) { return $row['delta'] > 0; } ) );
    $falling_products = array_values( array_filter( $product_momentum, function( $row ) { return $row['delta'] < 0; } ) );
    usort( $rising_products, function( $a, $b ) { return $b['delta'] <=> $a['delta']; } );
    usort( $falling_products, function( $a, $b ) { return $a['delta'] <=> $b['delta']; } );

    $growth = wpbb_mi_percent_change( $revenue, $prev_revenue );
    $order_growth = wpbb_mi_percent_change( $orders_count, $prev_orders );
    $aov = $orders_count ? $revenue / $orders_count : 0.0;
    $refund_rate = $revenue > 0 ? ( $refunds / $revenue ) * 100 : 0.0;

    $customer_count = count( $customers );
    $repeat_customers = 0;
    foreach ( $customers as $customer ) {
        if ( $customer['orders'] >= 2 ) {
            $repeat_customers++;
        }
    }
    $repeat_customer_rate = $customer_count > 0 ? ( $repeat_customers / $customer_count ) * 100 : 0.0;

    $new_returning = wpbb_mi_new_returning_summary( $start, $end );
    $lifecycle = wpbb_mi_customer_lifecycle();

    $recommendations = array();
    if ( $growth < -10 ) {
        $recommendations[] = array( 'high', 'Revenue is down ' . abs( round( $growth, 1 ) ) . '% versus the previous period. Re-engage recent buyers and focus paid activity on products that are still converting.' );
    }
    if ( $growth > 10 ) {
        $recommendations[] = array( 'good', 'Revenue is up ' . round( $growth, 1 ) . '%. Protect the momentum by repeating the strongest source and campaign before increasing budget.' );
    }
    if ( empty( $campaigns ) ) {
        $recommendations[] = array( 'medium', 'Campaign attribution is empty. Use the Campaign Link Builder for social, email and paid links so visits and order revenue can be measured together.' );
    }
    $direct = $sources['Direct / unattributed']['revenue'] ?? 0.0;
    if ( $revenue > 0 && $direct / $revenue > 0.5 ) {
        $recommendations[] = array( 'medium', 'More than half of revenue is unattributed. Standardise UTM links for Facebook, Instagram, TikTok, Google Ads and newsletters.' );
    }
    if ( $customer_count >= 10 && $repeat_customer_rate < 20 ) {
        $recommendations[] = array( 'medium', 'Repeat customer rate in this period is below 20%. Test a second-purchase offer, post-purchase follow-up or loyalty incentive.' );
    }
    if ( ! empty( $lifecycle['at_risk'] ) ) {
        $recommendations[] = array( 'medium', number_format_i18n( $lifecycle['at_risk'] ) . ' customers are in the 60-180 day at-risk window. A targeted reactivation campaign is likely more efficient than broad acquisition.' );
    }
    if ( $aov > 0 && $aov < 50 ) {
        $recommendations[] = array( 'medium', 'Average order value is relatively low. Test bundles, quantity breaks, free-shipping thresholds and related-product offers.' );
    }
    if ( $refund_rate > 8 ) {
        $recommendations[] = array( 'high', 'Refund value is above 8% of gross revenue. Review the highest-refund products, delivery expectations and product descriptions.' );
    }

    foreach ( $campaigns as $campaign_name => $campaign_row ) {
        if ( $campaign_row['spend'] > 0 && $campaign_row['roas'] > 3 ) {
            $recommendations[] = array( 'good', 'Campaign "' . $campaign_name . '" is above 3x ROAS. Consider scaling it gradually while watching conversion and CPA.' );
            break;
        }
    }
    foreach ( $campaigns as $campaign_name => $campaign_row ) {
        if ( $campaign_row['spend'] > 0 && $campaign_row['revenue'] > 0 && $campaign_row['roas'] < 1 ) {
            $recommendations[] = array( 'high', 'Campaign "' . $campaign_name . '" is below 1x ROAS. Review targeting, landing-page relevance and spend before adding more budget.' );
            break;
        }
    }
    if ( ! empty( $rising_products[0] ) && $rising_products[0]['delta'] > max( 1, $revenue * 0.03 ) ) {
        $recommendations[] = array( 'good', 'Product momentum is strongest for "' . $rising_products[0]['name'] . '". Feature it in social content, remarketing and related-product placements while demand is rising.' );
    }
    if ( ! empty( $pairs[0] ) && $pairs[0]['count'] >= 3 ) {
        $recommendations[] = array( 'good', 'Customers frequently buy "' . $pairs[0]['a'] . '" with "' . $pairs[0]['b'] . '". Test a bundle or cross-sell placement for this pair.' );
    }
    if ( empty( $recommendations ) ) {
        $recommendations[] = array( 'good', 'Performance is stable. Keep campaign tracking consistent and test one change at a time so the effect remains measurable.' );
    }

    $request_cache[ $days ] = compact(
        'days', 'start', 'end', 'prev_start', 'prev_end', 'daily', 'revenue', 'refunds', 'orders_count', 'customers',
        'sources', 'first_sources', 'campaigns', 'mediums', 'products', 'coupons', 'pairs', 'prev_revenue', 'prev_orders',
        'previous_products', 'previous_sources', 'rising_products', 'falling_products', 'growth', 'order_growth', 'aov',
        'refund_rate', 'customer_count', 'repeat_customers', 'repeat_customer_rate', 'new_returning', 'lifecycle',
        'visit_stats', 'recommendations'
    );

    return $request_cache[ $days ];
}

function wpbb_mi_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __( 'Marketing Intelligence', 'wp-theme-woo-support' ),
        __( 'Marketing Intelligence', 'wp-theme-woo-support' ),
        'manage_woocommerce',
        'wpbb-marketing-intelligence',
        'wpbb_mi_render_page'
    );
}
add_action( 'admin_menu', 'wpbb_mi_admin_menu', 60 );

function wpbb_mi_admin_assets( $hook ) {
    if ( 'woocommerce_page_wpbb-marketing-intelligence' !== $hook ) {
        return;
    }

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
    foreach ( array_slice( $s['sources'], 0, 8, true ) as $name => $row ) {
        if ( $row['revenue'] > 0 ) {
            $source_revenue[] = array( $name, round( $row['revenue'], 2 ) );
        }
    }

    $product_revenue = array( array( 'Product', 'Revenue' ) );
    foreach ( array_slice( $s['products'], 0, 8, true ) as $row ) {
        $product_revenue[] = array( wp_trim_words( $row['name'], 7, '...' ), round( $row['revenue'], 2 ) );
    }

    $customer_mix = array(
        array( 'Customer type', 'Orders' ),
        array( 'New', (int) $s['new_returning']['new']['orders'] ),
        array( 'Returning', (int) $s['new_returning']['returning']['orders'] ),
    );

    $campaign_funnel = array( array( 'Campaign', 'Tracked visits', 'Orders' ) );
    foreach ( array_slice( $s['campaigns'], 0, 8, true ) as $name => $row ) {
        $campaign_funnel[] = array( wp_trim_words( $name, 5, '...' ), (int) $row['visits'], (int) $row['orders'] );
    }

    wp_localize_script(
        'wpbb-mi-admin',
        'WPBBWooMarketing',
        array(
            'revenueTrend'   => $revenue_trend,
            'ordersTrend'    => $orders_trend,
            'sourceRevenue'  => $source_revenue,
            'productRevenue' => $product_revenue,
            'customerMix'    => $customer_mix,
            'campaignFunnel' => $campaign_funnel,
            'currencySymbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'wpbb_mi_admin_assets' );

function wpbb_mi_save_campaign_costs() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You do not have permission to manage campaign costs.', 'wp-theme-woo-support' ) );
    }

    check_admin_referer( 'wpbb_mi_save_campaign_costs' );

    $names = isset( $_POST['campaign_name'] ) && is_array( $_POST['campaign_name'] ) ? wp_unslash( $_POST['campaign_name'] ) : array();
    $values = isset( $_POST['campaign_cost'] ) && is_array( $_POST['campaign_cost'] ) ? wp_unslash( $_POST['campaign_cost'] ) : array();
    $costs = wpbb_mi_get_campaign_costs();

    foreach ( $names as $index => $name ) {
        $name = wpbb_mi_trim_dimension( $name );
        if ( '' === $name ) {
            continue;
        }
        $raw = $values[ $index ] ?? '';
        $value = (float) wc_format_decimal( $raw );
        if ( $value > 0 ) {
            $costs[ $name ] = $value;
        } else {
            unset( $costs[ $name ] );
        }
    }

    update_option( 'wpbb_mi_campaign_costs', $costs, false );
    $days = isset( $_POST['days'] ) ? max( 7, min( 365, absint( $_POST['days'] ) ) ) : 30;
    $url = add_query_arg(
        array(
            'page'  => 'wpbb-marketing-intelligence',
            'days'  => $days,
            'saved' => 'costs',
        ),
        admin_url( 'admin.php' )
    );
    wp_safe_redirect( $url );
    exit;
}
add_action( 'admin_post_wpbb_mi_save_campaign_costs', 'wpbb_mi_save_campaign_costs' );

function wpbb_mi_delta( $value ) {
    $class = $value > 0 ? 'up' : ( $value < 0 ? 'down' : 'flat' );
    $arrow = $value > 0 ? '&uarr;' : ( $value < 0 ? '&darr;' : '&rarr;' );
    return '<span class="wpbb-mi-delta ' . esc_attr( $class ) . '">' . $arrow . ' ' . esc_html( number_format_i18n( abs( $value ), 1 ) . '%' ) . '</span>';
}

function wpbb_mi_source_attribution_rows( $s ) {
    $names = array_unique( array_merge( array_keys( $s['first_sources'] ), array_keys( $s['sources'] ) ) );
    $rows = array();
    foreach ( $names as $name ) {
        $rows[ $name ] = array(
            'first'  => $s['first_sources'][ $name ]['revenue'] ?? 0.0,
            'last'   => $s['sources'][ $name ]['revenue'] ?? 0.0,
            'orders' => $s['sources'][ $name ]['orders'] ?? 0,
            'visits' => $s['sources'][ $name ]['visits'] ?? 0,
        );
    }
    uasort( $rows, function( $a, $b ) { return max( $b['first'], $b['last'] ) <=> max( $a['first'], $a['last'] ); } );
    return $rows;
}

function wpbb_mi_render_customer_lifecycle( $lifecycle ) {
    if ( empty( $lifecycle['available'] ) ) {
        echo '<p class="description">WooCommerce Analytics lookup tables are not available yet. Customer lifecycle cards will appear after WooCommerce analytics data is generated.</p>';
        return;
    }
    ?>
    <div class="wpbb-mi-mini-kpis">
        <div><span>All customers</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['customers'] ) ); ?></strong></div>
        <div><span>One-time</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['one_time'] ) ); ?></strong></div>
        <div><span>Repeat 2+</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['repeat'] ) ); ?></strong></div>
        <div><span>Loyal 3+</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['loyal'] ) ); ?></strong></div>
        <div><span>VIP 5+</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['vip'] ) ); ?></strong></div>
        <div><span>Active 60d</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['active_60'] ) ); ?></strong></div>
        <div><span>At risk 60-180d</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['at_risk'] ) ); ?></strong></div>
        <div><span>Lapsed 180d+</span><strong><?php echo esc_html( number_format_i18n( $lifecycle['lapsed'] ) ); ?></strong></div>
    </div>
    <?php
}

function wpbb_mi_render_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    $days = isset( $_GET['days'] ) ? max( 7, min( 365, absint( $_GET['days'] ) ) ) : 30;
    $s = wpbb_mi_collect_stats( $days );
    $base = admin_url( 'admin.php?page=wpbb-marketing-intelligence' );
    $attribution_rows = wpbb_mi_source_attribution_rows( $s );
    $costs = wpbb_mi_get_campaign_costs();
    ?>
    <div class="wrap wpbb-mi-wrap">
        <div class="wpbb-mi-head">
            <div>
                <h1>WooCommerce Marketing Intelligence</h1>
                <p>Revenue, customer lifecycle, campaign efficiency, product momentum and recommendations inside WP Theme Woo Support.</p>
            </div>
            <div class="wpbb-mi-range">
                <?php foreach ( array( 7, 30, 90, 365 ) as $d ) : ?>
                    <a class="<?php echo $days === $d ? 'active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'days', $d, $base ) ); ?>"><?php echo esc_html( $d ); ?>d</a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ( isset( $_GET['saved'] ) && 'costs' === $_GET['saved'] ) : ?>
            <div class="notice notice-success is-dismissible"><p>Campaign costs saved. ROAS and CPA have been recalculated.</p></div>
        <?php endif; ?>

        <nav class="wpbb-mi-jump" aria-label="Marketing Intelligence sections">
            <a href="#wpbb-mi-overview">Overview</a>
            <a href="#wpbb-mi-customers">Customers</a>
            <a href="#wpbb-mi-campaigns">Campaigns</a>
            <a href="#wpbb-mi-products">Products</a>
            <a href="#wpbb-mi-promotions">Promotions</a>
        </nav>

        <section id="wpbb-mi-overview">
            <div class="wpbb-mi-grid wpbb-mi-kpis">
                <div class="wpbb-mi-card"><span>Revenue</span><strong><?php echo wp_kses_post( wc_price( $s['revenue'] ) ); ?></strong><?php echo wp_kses_post( wpbb_mi_delta( $s['growth'] ) ); ?></div>
                <div class="wpbb-mi-card"><span>Orders</span><strong><?php echo esc_html( number_format_i18n( $s['orders_count'] ) ); ?></strong><?php echo wp_kses_post( wpbb_mi_delta( $s['order_growth'] ) ); ?></div>
                <div class="wpbb-mi-card"><span>Average order value</span><strong><?php echo wp_kses_post( wc_price( $s['aov'] ) ); ?></strong><small>selected period</small></div>
                <div class="wpbb-mi-card"><span>Customers</span><strong><?php echo esc_html( number_format_i18n( $s['customer_count'] ) ); ?></strong><small>unique billing emails</small></div>
                <div class="wpbb-mi-card"><span>Repeat customers</span><strong><?php echo esc_html( number_format_i18n( $s['repeat_customer_rate'], 1 ) . '%' ); ?></strong><small>2+ orders in period</small></div>
                <div class="wpbb-mi-card"><span>Tracked visits</span><strong><?php echo esc_html( number_format_i18n( $s['visit_stats']['total'] ) ); ?></strong><small>UTM/ad-click landings</small></div>
                <div class="wpbb-mi-card"><span>Refunded</span><strong><?php echo wp_kses_post( wc_price( $s['refunds'] ) ); ?></strong><small><?php echo esc_html( number_format_i18n( $s['refund_rate'], 1 ) . '% of revenue' ); ?></small></div>
            </div>

            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel"><h2>Revenue trend</h2><div id="wpbb-mi-revenue-chart" class="wpbb-mi-chart"></div></section>
                <section class="wpbb-mi-panel"><h2>Orders and revenue</h2><div id="wpbb-mi-orders-chart" class="wpbb-mi-chart"></div></section>
            </div>
            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel"><h2>Revenue by marketing source</h2><div id="wpbb-mi-source-chart" class="wpbb-mi-chart"></div></section>
                <section class="wpbb-mi-panel"><h2>Top products by revenue</h2><div id="wpbb-mi-products-chart" class="wpbb-mi-chart"></div></section>
            </div>

            <section class="wpbb-mi-panel wpbb-mi-action-panel">
                <div class="wpbb-mi-panel-head"><div><h2>Marketing action center</h2><p>Data-driven next steps based on the selected period and customer lifecycle.</p></div><span class="wpbb-mi-pill">Auto recommendations</span></div>
                <div class="wpbb-mi-recs">
                    <?php foreach ( $s['recommendations'] as $rec ) : ?>
                        <div class="wpbb-mi-rec <?php echo esc_attr( $rec[0] ); ?>">
                            <b><?php echo 'good' === $rec[0] ? 'Opportunity' : ( 'high' === $rec[0] ? 'Priority' : 'Improve' ); ?></b>
                            <span><?php echo esc_html( $rec[1] ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </section>

        <section id="wpbb-mi-customers" class="wpbb-mi-section">
            <div class="wpbb-mi-section-title"><div><span>Customer intelligence</span><h2>Lifecycle, retention and customer value</h2></div><p>CRM-style commerce insight from WooCommerce data without requiring a separate CRM plugin.</p></div>
            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel"><h2>New vs returning orders</h2><div id="wpbb-mi-customer-chart" class="wpbb-mi-chart"></div></section>
                <section class="wpbb-mi-panel"><h2>Lifetime customer lifecycle</h2><?php wpbb_mi_render_customer_lifecycle( $s['lifecycle'] ); ?></section>
            </div>

            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel">
                    <h2>Top customers in selected period</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Customer</th><th>Orders</th><th>Revenue</th><th>AOV</th><th>Last order</th></tr></thead><tbody>
                        <?php if ( $s['customers'] ) : foreach ( array_slice( $s['customers'], 0, 12, true ) as $customer ) : ?>
                            <tr><td><strong><?php echo esc_html( $customer['name'] ?: $customer['email'] ); ?></strong><small class="wpbb-mi-subline"><?php echo esc_html( $customer['email'] ); ?></small></td><td><?php echo esc_html( number_format_i18n( $customer['orders'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $customer['revenue'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $customer['orders'] ? $customer['revenue'] / $customer['orders'] : 0 ) ); ?></td><td><?php echo esc_html( $customer['last'] ? wp_date( get_option( 'date_format' ), strtotime( $customer['last'] ) ) : '-' ); ?></td></tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="5">No customer orders in this period.</td></tr>
                        <?php endif; ?>
                    </tbody></table></div>
                </section>
                <section class="wpbb-mi-panel">
                    <h2>Top lifetime customers</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Customer</th><th>Paid orders</th><th>Lifetime value</th><th>Last order</th></tr></thead><tbody>
                        <?php if ( ! empty( $s['lifecycle']['top_customers'] ) ) : foreach ( $s['lifecycle']['top_customers'] as $customer ) : ?>
                            <tr><td><strong><?php echo esc_html( trim( $customer['first_name'] . ' ' . $customer['last_name'] ) ?: $customer['email'] ); ?></strong><small class="wpbb-mi-subline"><?php echo esc_html( $customer['email'] ); ?></small></td><td><?php echo esc_html( number_format_i18n( $customer['orders_count'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $customer['lifetime_value'] ) ); ?></td><td><?php echo esc_html( $customer['last_order'] ? wp_date( get_option( 'date_format' ), strtotime( $customer['last_order'] ) ) : '-' ); ?></td></tr>
                        <?php endforeach; else : ?>
                            <tr><td colspan="4">Lifetime customer data is not available yet.</td></tr>
                        <?php endif; ?>
                    </tbody></table></div>
                </section>
            </div>
        </section>

        <section id="wpbb-mi-campaigns" class="wpbb-mi-section">
            <div class="wpbb-mi-section-title"><div><span>Campaign intelligence</span><h2>Visits, conversions, revenue and ROAS</h2></div><p>Tracked UTM/ad-click landings are aggregated only; this module does not store visitor IP addresses.</p></div>
            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel"><h2>Tracked visits to orders</h2><div id="wpbb-mi-campaign-chart" class="wpbb-mi-chart"></div></section>
                <section class="wpbb-mi-panel">
                    <h2>Campaign Link Builder</h2>
                    <p>Create consistent links for social, email and paid campaigns. Landing visits will be counted automatically.</p>
                    <form class="wpbb-mi-builder" onsubmit="return false;">
                        <label>Destination URL<input id="wpbb-mi-url" type="url" value="<?php echo esc_attr( home_url( '/' ) ); ?>"></label>
                        <div class="wpbb-mi-builder-row"><label>Source<input id="wpbb-mi-source" type="text" placeholder="facebook, instagram, newsletter"></label><label>Medium<input id="wpbb-mi-medium" type="text" placeholder="social, email, cpc"></label></div>
                        <label>Campaign<input id="wpbb-mi-campaign" type="text" placeholder="spring-sale"></label>
                        <div class="wpbb-mi-builder-row"><label>Content<input id="wpbb-mi-content" type="text" placeholder="video-a, hero-button"></label><label>Term<input id="wpbb-mi-term" type="text" placeholder="optional keyword"></label></div>
                        <button type="button" class="button button-primary" id="wpbb-mi-build">Build tracked URL</button>
                        <div class="wpbb-mi-built"><input id="wpbb-mi-result" readonly><button type="button" class="button wpbb-mi-copy" data-copy="">Copy</button></div>
                    </form>
                </section>
            </div>

            <section class="wpbb-mi-panel">
                <div class="wpbb-mi-panel-head"><div><h2>Campaign performance and cost</h2><p>Add optional spend to calculate ROAS and cost per acquisition.</p></div><span class="wpbb-mi-pill">Manual spend</span></div>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="wpbb_mi_save_costs">
                    <input type="hidden" name="days" value="<?php echo esc_attr( $days ); ?>">
                    <?php wp_nonce_field( 'wpbb_mi_save_campaign_costs' ); ?>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Campaign</th><th>Source / medium</th><th>Visits</th><th>Orders</th><th>Conversion</th><th>Revenue</th><th>Spend</th><th>ROAS</th><th>CPA</th></tr></thead><tbody>
                        <?php if ( $s['campaigns'] ) : $cost_index = 0; foreach ( array_slice( $s['campaigns'], 0, 30, true ) as $name => $row ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $name ); ?></strong><input type="hidden" name="campaign_name[<?php echo esc_attr( $cost_index ); ?>]" value="<?php echo esc_attr( $name ); ?>"></td>
                                <td><?php echo esc_html( trim( $row['source'] . ( $row['medium'] ? ' / ' . $row['medium'] : '' ) ) ?: '-' ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( $row['visits'] ) ); ?></td>
                                <td><?php echo esc_html( number_format_i18n( $row['orders'] ) ); ?></td>
                                <td><?php echo esc_html( $row['visits'] ? number_format_i18n( $row['conversion'], 1 ) . '%' : '-' ); ?></td>
                                <td><?php echo wp_kses_post( wc_price( $row['revenue'] ) ); ?></td>
                                <td><input class="wpbb-mi-cost" type="number" min="0" step="0.01" name="campaign_cost[<?php echo esc_attr( $cost_index ); ?>]" value="<?php echo esc_attr( isset( $costs[ $name ] ) ? number_format( (float) $costs[ $name ], wc_get_price_decimals(), ".", "" ) : '' ); ?>" placeholder="0.00"></td>
                                <td><?php echo esc_html( $row['spend'] > 0 ? number_format_i18n( $row['roas'], 2 ) . 'x' : '-' ); ?></td>
                                <td><?php echo $row['cpa'] > 0 ? wp_kses_post( wc_price( $row['cpa'] ) ) : '-'; ?></td>
                            </tr>
                        <?php $cost_index++; endforeach; else : ?>
                            <tr><td colspan="9">No tracked campaigns yet. Build a UTM link and use it in a campaign to start collecting data.</td></tr>
                        <?php endif; ?>
                    </tbody></table></div>
                    <?php if ( $s['campaigns'] ) : ?><p class="wpbb-mi-form-actions"><button type="submit" class="button button-primary">Save campaign costs</button></p><?php endif; ?>
                </form>
            </section>

            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel">
                    <h2>First-touch vs last-touch revenue</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Source</th><th>First-touch revenue</th><th>Last-touch revenue</th><th>Orders</th><th>Tracked visits</th></tr></thead><tbody>
                        <?php foreach ( array_slice( $attribution_rows, 0, 20, true ) as $name => $row ) : ?>
                            <tr><td><strong><?php echo esc_html( $name ); ?></strong></td><td><?php echo wp_kses_post( wc_price( $row['first'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['last'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['orders'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['visits'] ) ); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                </section>
                <section class="wpbb-mi-panel">
                    <h2>Marketing source momentum</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Source</th><th>Orders</th><th>Revenue</th><th>Previous</th><th>Change</th><th>Visits</th><th>Conversion</th></tr></thead><tbody>
                        <?php foreach ( array_slice( $s['sources'], 0, 20, true ) as $name => $row ) : ?>
                            <tr><td><strong><?php echo esc_html( $name ); ?></strong></td><td><?php echo esc_html( number_format_i18n( $row['orders'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['revenue'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['previous_revenue'] ) ); ?></td><td><?php echo wp_kses_post( wpbb_mi_delta( $row['growth'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['visits'] ) ); ?></td><td><?php echo esc_html( $row['visits'] ? number_format_i18n( $row['conversion'], 1 ) . '%' : '-' ); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                </section>
            </div>
        </section>

        <section id="wpbb-mi-products" class="wpbb-mi-section">
            <div class="wpbb-mi-section-title"><div><span>Merchandising intelligence</span><h2>Product momentum and purchase affinity</h2></div><p>Use rising/falling demand and products bought together to guide ads, bundles and cross-sells.</p></div>
            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel">
                    <h2>Fastest rising products</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Product</th><th>Revenue</th><th>Previous</th><th>Change</th></tr></thead><tbody>
                        <?php if ( $s['rising_products'] ) : foreach ( array_slice( $s['rising_products'], 0, 10 ) as $row ) : ?>
                            <tr><td><strong><?php echo esc_html( $row['name'] ); ?></strong></td><td><?php echo wp_kses_post( wc_price( $row['current'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['previous'] ) ); ?></td><td><?php echo wp_kses_post( wpbb_mi_delta( $row['growth'] ) ); ?></td></tr>
                        <?php endforeach; else : ?><tr><td colspan="4">No rising products detected yet.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </section>
                <section class="wpbb-mi-panel">
                    <h2>Largest product declines</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Product</th><th>Revenue</th><th>Previous</th><th>Change</th></tr></thead><tbody>
                        <?php if ( $s['falling_products'] ) : foreach ( array_slice( $s['falling_products'], 0, 10 ) as $row ) : ?>
                            <tr><td><strong><?php echo esc_html( $row['name'] ); ?></strong></td><td><?php echo wp_kses_post( wc_price( $row['current'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['previous'] ) ); ?></td><td><?php echo wp_kses_post( wpbb_mi_delta( $row['growth'] ) ); ?></td></tr>
                        <?php endforeach; else : ?><tr><td colspan="4">No product declines detected yet.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </section>
            </div>

            <section class="wpbb-mi-panel">
                <h2>Frequently bought together</h2>
                <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Product A</th><th>Product B</th><th>Orders together</th><th>Suggested use</th></tr></thead><tbody>
                    <?php if ( $s['pairs'] ) : foreach ( array_slice( $s['pairs'], 0, 15, true ) as $row ) : ?>
                        <tr><td><strong><?php echo esc_html( $row['a'] ); ?></strong></td><td><strong><?php echo esc_html( $row['b'] ); ?></strong></td><td><?php echo esc_html( number_format_i18n( $row['count'] ) ); ?></td><td>Bundle, cross-sell or remarketing creative</td></tr>
                    <?php endforeach; else : ?><tr><td colspan="4">Not enough multi-product orders to calculate affinity yet.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
        </section>

        <section id="wpbb-mi-promotions" class="wpbb-mi-section">
            <div class="wpbb-mi-section-title"><div><span>Promotion intelligence</span><h2>Coupon and medium performance</h2></div><p>See which promotional mechanics are associated with revenue and where discounting may need review.</p></div>
            <div class="wpbb-mi-grid wpbb-mi-two">
                <section class="wpbb-mi-panel">
                    <h2>Coupon performance</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Coupon</th><th>Orders</th><th>Discount</th><th>Associated order revenue</th></tr></thead><tbody>
                        <?php if ( $s['coupons'] ) : foreach ( array_slice( $s['coupons'], 0, 20, true ) as $code => $row ) : ?>
                            <tr><td><strong><?php echo esc_html( $code ); ?></strong></td><td><?php echo esc_html( number_format_i18n( $row['orders'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['discount'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['revenue'] ) ); ?></td></tr>
                        <?php endforeach; else : ?><tr><td colspan="4">No coupon usage in this period.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </section>
                <section class="wpbb-mi-panel">
                    <h2>Marketing medium performance</h2>
                    <div class="wpbb-mi-table-scroll"><table class="widefat striped"><thead><tr><th>Medium</th><th>Orders</th><th>Revenue</th><th>Share</th></tr></thead><tbody>
                        <?php foreach ( array_slice( $s['mediums'], 0, 20, true ) as $name => $row ) : $share = $s['revenue'] > 0 ? ( $row['revenue'] / $s['revenue'] ) * 100 : 0; ?>
                            <tr><td><strong><?php echo esc_html( $name ); ?></strong></td><td><?php echo esc_html( number_format_i18n( $row['orders'] ) ); ?></td><td><?php echo wp_kses_post( wc_price( $row['revenue'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( $share, 1 ) . '%' ); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div>
                </section>
            </div>
        </section>
    </div>
    <?php
}
