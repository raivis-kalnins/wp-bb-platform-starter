<?php
/**
 * Native quote basket for simple and variable WooCommerce products.
 * Keeps the normal cart purchase flow available while offering Add to Quote.
 */
defined( 'ABSPATH' ) || exit;

function wp_theme_woo_quote_items() {
    if ( ! function_exists( 'WC' ) || ! WC()->session ) return array();
    $items = WC()->session->get( 'wp_theme_quote_items', array() );
    return is_array( $items ) ? $items : array();
}

function wp_theme_woo_quote_set_items( $items ) {
    if ( function_exists( 'WC' ) && WC()->session ) WC()->session->set( 'wp_theme_quote_items', is_array( $items ) ? $items : array() );
}

function wp_theme_woo_quote_count() {
    $count = 0;
    foreach ( wp_theme_woo_quote_items() as $item ) $count += max( 1, absint( $item['quantity'] ?? 1 ) );
    return $count;
}

function wp_theme_woo_quote_page_url() {
    $id = absint( get_option( 'wp_theme_woo_quote_page_id' ) );
    if ( $id && 'publish' === get_post_status( $id ) ) return get_permalink( $id );
    $page = get_page_by_path( 'request-a-quote' );
    return $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/request-a-quote/' );
}

function wp_theme_woo_quote_ensure_page() {
    if ( ! current_user_can( 'edit_pages' ) ) return;
    $page = get_page_by_path( 'request-a-quote' );
    if ( ! $page ) {
        $id = wp_insert_post( array(
            'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'request-a-quote',
            'post_title' => __( 'Request a Quote', 'wp-theme-woo-support' ),
            'post_content' => '[wp_theme_woo_quote_basket]',
        ) );
        if ( ! is_wp_error( $id ) && $id ) update_option( 'wp_theme_woo_quote_page_id', (int) $id, false );
    } else {
        update_option( 'wp_theme_woo_quote_page_id', (int) $page->ID, false );
    }
}
add_action( 'admin_init', 'wp_theme_woo_quote_ensure_page' );

add_action( 'init', function() {
    register_post_type( 'woo_quote_request', array(
        'labels' => array(
            'name' => __( 'Quote Requests', 'wp-theme-woo-support' ),
            'singular_name' => __( 'Quote Request', 'wp-theme-woo-support' ),
            'menu_name' => __( 'Quote Requests', 'wp-theme-woo-support' ),
        ),
        'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_menu' => 'woocommerce',
        'show_in_rest' => false, 'supports' => array( 'title' ), 'capability_type' => 'post', 'map_meta_cap' => true,
    ) );
} );

function wp_theme_woo_quote_collect_attributes( $source ) {
    $attrs = array();
    foreach ( (array) $source as $key => $value ) {
        if ( 0 !== strpos( (string) $key, 'attribute_' ) ) continue;
        if ( is_array( $value ) ) continue;
        $attrs[ sanitize_key( $key ) ] = wc_clean( wp_unslash( $value ) );
    }
    ksort( $attrs );
    return $attrs;
}

function wp_theme_woo_quote_flatten_custom_value( $key, $value, &$custom, $depth = 0 ) {
    if ( $depth > 3 || count( $custom ) >= 60 ) return;
    if ( is_array( $value ) ) {
        foreach ( $value as $sub_key => $sub_value ) {
            wp_theme_woo_quote_flatten_custom_value( $key . '_' . sanitize_key( (string) $sub_key ), $sub_value, $custom, $depth + 1 );
        }
        return;
    }
    if ( ! is_scalar( $value ) ) return;
    $clean = wc_clean( wp_unslash( (string) $value ) );
    if ( '' === trim( $clean ) ) return;
    $custom[ sanitize_key( $key ) ] = function_exists( 'mb_substr' ) ? mb_substr( $clean, 0, 500 ) : substr( $clean, 0, 500 );
}

function wp_theme_woo_quote_collect_custom_fields( $source ) {
    $custom = array();
    $skip = array( 'action','nonce','product_id','variation_id','quantity','add-to-cart','wp_http_referer','_wpnonce' );
    foreach ( (array) $source as $key => $value ) {
        $key = (string) $key;
        if ( in_array( $key, $skip, true ) || 0 === strpos( $key, 'attribute_' ) ) continue;
        if ( 'iws_single_product_custom_fields' === $key || preg_match( '/(?:custom|option|finish|size|colour|color|engraving|reference|message|addon|add-on|bundle|composite|configuration|tmcp|wapf|yith)/i', $key ) ) {
            wp_theme_woo_quote_flatten_custom_value( $key, $value, $custom );
        }
    }
    ksort( $custom );
    return $custom;
}

function wp_theme_woo_quote_add_item( $product_id, $variation_id, $quantity, $attributes, $custom = array() ) {
    $product_id = absint( $product_id );
    $variation_id = absint( $variation_id );
    $quantity = max( 1, wc_stock_amount( $quantity ) );
    $product = wc_get_product( $variation_id ?: $product_id );
    if ( ! $product ) return new WP_Error( 'invalid_product', __( 'That product could not be added to the quote.', 'wp-theme-woo-support' ) );

    $base = wc_get_product( $product_id );
    if ( $variation_id ) {
        if ( 'product_variation' !== get_post_type( $variation_id ) || absint( wp_get_post_parent_id( $variation_id ) ) !== $product_id ) {
            return new WP_Error( 'invalid_variation', __( 'Please select a valid product variation.', 'wp-theme-woo-support' ) );
        }
        if ( $base && $base->is_type( 'variable' ) && $attributes ) {
            try {
                $data_store = WC_Data_Store::load( 'product' );
                $matched_id = (int) $data_store->find_matching_product_variation( $base, $attributes );
                if ( $matched_id && $matched_id !== $variation_id ) {
                    return new WP_Error( 'variation_mismatch', __( 'The selected product options do not match that variation. Please choose the options again.', 'wp-theme-woo-support' ) );
                }
            } catch ( Exception $e ) {
                // WooCommerce has already supplied a concrete variation ID; keep the quote route usable if a third-party variation extension changes the matching API.
            }
        }
    } elseif ( $base && $base->is_type( 'variable' ) ) {
        return new WP_Error( 'variation_required', __( 'Please choose all product options before adding this item to your quote.', 'wp-theme-woo-support' ) );
    }

    $key = md5( wp_json_encode( array( $product_id, $variation_id, $attributes, $custom ) ) );
    $items = wp_theme_woo_quote_items();
    if ( isset( $items[ $key ] ) ) $quantity += absint( $items[ $key ]['quantity'] ?? 0 );
    $items[ $key ] = array(
        'key' => $key, 'product_id' => $product_id, 'variation_id' => $variation_id,
        'quantity' => $quantity, 'attributes' => $attributes, 'custom' => $custom, 'added' => time(),
    );
    wp_theme_woo_quote_set_items( $items );
    return $items[ $key ];
}

function wp_theme_woo_quote_ajax_add() {
    check_ajax_referer( 'wp_theme_woo_quote', 'nonce' );
    $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : ( isset( $_POST['add-to-cart'] ) ? absint( $_POST['add-to-cart'] ) : 0 );
    $variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
    $attributes = wp_theme_woo_quote_collect_attributes( $_POST );
    $custom = wp_theme_woo_quote_collect_custom_fields( $_POST );
    $raw_quantity = $_POST['quantity'] ?? 1;

    if ( is_array( $raw_quantity ) ) {
        $added = 0;
        foreach ( $raw_quantity as $child_id => $child_quantity ) {
            $child_id = absint( $child_id );
            $child_quantity = wc_stock_amount( wp_unslash( $child_quantity ) );
            if ( ! $child_id || $child_quantity <= 0 ) continue;
            $result = wp_theme_woo_quote_add_item( $child_id, 0, $child_quantity, array(), $custom );
            if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
            $added++;
        }
        if ( ! $added ) wp_send_json_error( array( 'message' => __( 'Choose at least one grouped product before adding it to your quote.', 'wp-theme-woo-support' ) ), 400 );
    } else {
        $quantity = wc_stock_amount( wp_unslash( $raw_quantity ) );
        $result = wp_theme_woo_quote_add_item( $product_id, $variation_id, $quantity, $attributes, $custom );
        if ( is_wp_error( $result ) ) wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
    }
    wp_send_json_success( array(
        'message' => __( 'Added to your quote.', 'wp-theme-woo-support' ),
        'count' => wp_theme_woo_quote_count(), 'quoteUrl' => wp_theme_woo_quote_page_url(),
    ) );
}
add_action( 'wp_ajax_wp_theme_woo_quote_add', 'wp_theme_woo_quote_ajax_add' );
add_action( 'wp_ajax_nopriv_wp_theme_woo_quote_add', 'wp_theme_woo_quote_ajax_add' );

function wp_theme_woo_quote_product_button() {
    global $product;
    if ( ! $product instanceof WC_Product ) return;
    echo '<button type="button" class="button alt wp-quote-button wp-theme-add-to-quote" data-quote-single data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Add to Quote', 'wp-theme-woo-support' ) . '</button><span class="wp-theme-quote-status" data-quote-status aria-live="polite"></span>';
}
add_action( 'woocommerce_after_add_to_cart_button', 'wp_theme_woo_quote_product_button', 30 );

add_filter( 'woocommerce_loop_add_to_cart_link', function( $html, $product, $args ) {
    if ( ! $product instanceof WC_Product ) return $html;
    if ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
        $quote = '<a class="button wp-theme-loop-quote" href="' . esc_url( get_permalink( $product->get_id() ) ) . '">' . esc_html__( 'Select options for quote', 'wp-theme-woo-support' ) . '</a>';
    } else {
        $quote = '<button type="button" class="button wp-theme-loop-quote wp-theme-add-to-quote" data-quote-loop data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Add to Quote', 'wp-theme-woo-support' ) . '</button>';
    }
    return '<div class="wp-theme-product-actions">' . $html . $quote . '</div>';
}, 45, 3 );

function wp_theme_woo_quote_item_product( $item ) {
    return wc_get_product( absint( $item['variation_id'] ?? 0 ) ?: absint( $item['product_id'] ?? 0 ) );
}

function wp_theme_woo_quote_item_meta( $item ) {
    $parts = array();
    foreach ( (array) ( $item['attributes'] ?? array() ) as $key => $value ) {
        $taxonomy = str_replace( 'attribute_', '', $key );
        $label = wc_attribute_label( $taxonomy );
        if ( taxonomy_exists( $taxonomy ) ) {
            $term = get_term_by( 'slug', $value, $taxonomy );
            if ( $term ) $value = $term->name;
        }
        $parts[] = '<span><strong>' . esc_html( $label ?: ucfirst( str_replace( array( 'pa_', '-', '_' ), array( '', ' ', ' ' ), $taxonomy ) ) ) . ':</strong> ' . esc_html( $value ) . '</span>';
    }
    foreach ( (array) ( $item['custom'] ?? array() ) as $key => $value ) {
        $parts[] = '<span><strong>' . esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $key ) ) ) . ':</strong> ' . esc_html( is_scalar( $value ) ? (string) $value : '' ) . '</span>';
    }
    return $parts ? '<div class="wp-theme-quote-item__meta">' . implode( '', $parts ) . '</div>' : '';
}

function wp_theme_woo_quote_handle_page_actions() {
    if ( is_admin() || empty( $_REQUEST['wp_theme_quote_action'] ) ) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $action = sanitize_key( wp_unslash( $_REQUEST['wp_theme_quote_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( 'remove' === $action ) {
        $key = isset( $_GET['quote_item'] ) ? sanitize_text_field( wp_unslash( $_GET['quote_item'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'wp_theme_quote_remove_' . $key ) ) return;
        $items = wp_theme_woo_quote_items(); unset( $items[ $key ] ); wp_theme_woo_quote_set_items( $items );
        wp_safe_redirect( wp_theme_woo_quote_page_url() ); exit;
    }
    if ( 'update' === $action && isset( $_POST['wp_theme_quote_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_theme_quote_nonce'] ) ), 'wp_theme_quote_update' ) ) {
        $items = wp_theme_woo_quote_items();
        foreach ( (array) ( $_POST['quote_qty'] ?? array() ) as $key => $qty ) {
            $key = sanitize_text_field( $key ); if ( ! isset( $items[ $key ] ) ) continue;
            $qty = max( 0, absint( $qty ) ); if ( 0 === $qty ) unset( $items[ $key ] ); else $items[ $key ]['quantity'] = $qty;
        }
        wp_theme_woo_quote_set_items( $items ); wp_safe_redirect( wp_theme_woo_quote_page_url() ); exit;
    }
    if ( 'submit' === $action && isset( $_POST['wp_theme_quote_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_theme_quote_nonce'] ) ), 'wp_theme_quote_submit' ) ) {
        $items = wp_theme_woo_quote_items(); if ( ! $items ) return;
        $name = sanitize_text_field( wp_unslash( $_POST['quote_name'] ?? '' ) );
        $email = sanitize_email( wp_unslash( $_POST['quote_email'] ?? '' ) );
        if ( ! $name || ! is_email( $email ) ) { wc_add_notice( __( 'Please enter your name and a valid email address.', 'wp-theme-woo-support' ), 'error' ); return; }
        if ( empty( $_POST['quote_privacy'] ) ) { wc_add_notice( __( 'Please confirm you have read the privacy information.', 'wp-theme-woo-support' ), 'error' ); return; }
        $company = sanitize_text_field( wp_unslash( $_POST['quote_company'] ?? '' ) );
        $phone = sanitize_text_field( wp_unslash( $_POST['quote_phone'] ?? '' ) );
        $message = sanitize_textarea_field( wp_unslash( $_POST['quote_message'] ?? '' ) );
        $id = wp_insert_post( array( 'post_type' => 'woo_quote_request', 'post_status' => 'publish', 'post_title' => sprintf( __( 'Quote — %1$s — %2$s', 'wp-theme-woo-support' ), $name, wp_date( 'Y-m-d H:i' ) ) ) );
        if ( ! is_wp_error( $id ) && $id ) {
            update_post_meta( $id, '_quote_name', $name ); update_post_meta( $id, '_quote_email', $email ); update_post_meta( $id, '_quote_company', $company ); update_post_meta( $id, '_quote_phone', $phone ); update_post_meta( $id, '_quote_message', $message ); update_post_meta( $id, '_quote_items', $items );
            $lines = array( 'New quote request', 'Name: ' . $name, 'Email: ' . $email, 'Company: ' . $company, 'Phone: ' . $phone, '', 'Items:' );
            foreach ( $items as $item ) { $p = wp_theme_woo_quote_item_product( $item ); if ( $p ) $lines[] = '- ' . $p->get_name() . ' × ' . absint( $item['quantity'] ); }
            if ( $message ) { $lines[] = ''; $lines[] = 'Message: ' . $message; }
            wp_mail( get_option( 'admin_email' ), sprintf( __( 'New product quote request from %s', 'wp-theme-woo-support' ), $name ), implode( "\n", $lines ), array( 'Reply-To: ' . $name . ' <' . $email . '>' ) );
            wp_theme_woo_quote_set_items( array() );
            wp_safe_redirect( add_query_arg( 'quote_submitted', '1', wp_theme_woo_quote_page_url() ) ); exit;
        }
    }
}
add_action( 'template_redirect', 'wp_theme_woo_quote_handle_page_actions', 20 );

function wp_theme_woo_quote_basket_shortcode() {
    $items = wp_theme_woo_quote_items();
    $privacy = function_exists( 'wp_theme_essential_page_url' ) ? wp_theme_essential_page_url( 'privacy-policy' ) : home_url( '/privacy-policy/' );
    $terms = function_exists( 'wp_theme_essential_page_url' ) ? wp_theme_essential_page_url( 'terms-and-conditions' ) : home_url( '/terms-and-conditions/' );
    ob_start();
    echo '<section class="wp-theme-quote-page"><div class="container"><header class="wp-theme-quote-page__head"><p class="wp-theme-sector-eyebrow">' . esc_html__( 'Product enquiry', 'wp-theme-woo-support' ) . '</p><h1>' . esc_html__( 'Request a Quote', 'wp-theme-woo-support' ) . '</h1><p>' . esc_html__( 'Build a quote list without affecting your shopping cart. Product variations and selected options are kept with the request.', 'wp-theme-woo-support' ) . '</p></header>';
    if ( isset( $_GET['quote_submitted'] ) ) echo '<div class="woocommerce-message" role="status">' . esc_html__( 'Thank you. Your quote request has been sent.', 'wp-theme-woo-support' ) . '</div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    wc_print_notices();
    if ( ! $items ) { echo '<div class="wp-theme-quote-empty"><h2>' . esc_html__( 'Your quote list is empty.', 'wp-theme-woo-support' ) . '</h2><p>' . esc_html__( 'Browse the shop and choose Add to Quote on any product you would like us to price or discuss.', 'wp-theme-woo-support' ) . '</p><a class="button" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Browse products', 'wp-theme-woo-support' ) . '</a></div></div></section>'; return ob_get_clean(); }
    ?>
    <div class="wp-theme-quote-layout">
        <form class="wp-theme-quote-items" method="post" action="<?php echo esc_url( wp_theme_woo_quote_page_url() ); ?>">
            <input type="hidden" name="wp_theme_quote_action" value="update"><?php wp_nonce_field( 'wp_theme_quote_update', 'wp_theme_quote_nonce' ); ?>
            <div class="wp-theme-quote-items__list">
                <?php foreach ( $items as $key => $item ) : $product = wp_theme_woo_quote_item_product( $item ); if ( ! $product ) continue; $parent_id = absint( $item['product_id'] ); ?>
                    <article class="wp-theme-quote-item">
                        <a class="wp-theme-quote-item__image" href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>"><?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
                        <div class="wp-theme-quote-item__content"><h2><a href="<?php echo esc_url( get_permalink( $parent_id ) ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h2><?php echo wp_theme_woo_quote_item_meta( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><label><?php esc_html_e( 'Quantity', 'wp-theme-woo-support' ); ?><input type="number" min="0" step="1" name="quote_qty[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( absint( $item['quantity'] ) ); ?>"></label><a class="wp-theme-quote-item__remove" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wp_theme_quote_action'=>'remove', 'quote_item'=>$key ), wp_theme_woo_quote_page_url() ), 'wp_theme_quote_remove_' . $key ) ); ?>"><?php esc_html_e( 'Remove', 'wp-theme-woo-support' ); ?></a></div>
                    </article>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="button"><?php esc_html_e( 'Update quote list', 'wp-theme-woo-support' ); ?></button>
        </form>
        <form class="wp-theme-quote-request-form" method="post" action="<?php echo esc_url( wp_theme_woo_quote_page_url() ); ?>">
            <input type="hidden" name="wp_theme_quote_action" value="submit"><?php wp_nonce_field( 'wp_theme_quote_submit', 'wp_theme_quote_nonce' ); ?>
            <h2><?php esc_html_e( 'Your details', 'wp-theme-woo-support' ); ?></h2>
            <div class="wp-theme-quote-form-grid"><label><?php esc_html_e( 'Name', 'wp-theme-woo-support' ); ?> *<input required type="text" name="quote_name" autocomplete="name"></label><label><?php esc_html_e( 'Email', 'wp-theme-woo-support' ); ?> *<input required type="email" name="quote_email" autocomplete="email"></label><label><?php esc_html_e( 'Company', 'wp-theme-woo-support' ); ?><input type="text" name="quote_company" autocomplete="organization"></label><label><?php esc_html_e( 'Phone', 'wp-theme-woo-support' ); ?><input type="tel" name="quote_phone" autocomplete="tel"></label></div>
            <label><?php esc_html_e( 'Message or project details', 'wp-theme-woo-support' ); ?><textarea name="quote_message" rows="5"></textarea></label>
            <label class="wp-theme-quote-consent"><input required type="checkbox" name="quote_privacy" value="1"><span><?php echo wp_kses_post( sprintf( __( 'I have read the <a href="%1$s">Privacy Policy</a> and understand the <a href="%2$s">Terms & Conditions</a>.', 'wp-theme-woo-support' ), esc_url( $privacy ), esc_url( $terms ) ) ); ?></span></label>
            <button type="submit" class="button alt"><?php esc_html_e( 'Send quote request', 'wp-theme-woo-support' ); ?></button>
        </form>
    </div></div></section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wp_theme_woo_quote_basket', 'wp_theme_woo_quote_basket_shortcode' );

add_action( 'wp_footer', function() {
    if ( is_admin() ) return; $count = wp_theme_woo_quote_count();
    echo '<a class="wp-theme-quote-floating" href="' . esc_url( wp_theme_woo_quote_page_url() ) . '" data-quote-floating><span>' . esc_html__( 'My Quote', 'wp-theme-woo-support' ) . '</span><strong data-quote-count>' . esc_html( $count ) . '</strong></a>';
}, 35 );

add_action( 'wp_enqueue_scripts', function() {
    $css = wp_theme_woo_support_path( 'assets/css/quote-request.css' ); $js = wp_theme_woo_support_path( 'assets/js/quote-request.js' );
    wp_enqueue_style( 'wp-theme-woo-quote', wp_theme_woo_support_url( 'assets/css/quote-request.css' ), array(), file_exists( $css ) ? filemtime( $css ) : WP_THEME_WOO_SUPPORT_VERSION );
    wp_enqueue_script( 'wp-theme-woo-quote', wp_theme_woo_support_url( 'assets/js/quote-request.js' ), array( 'jquery' ), file_exists( $js ) ? filemtime( $js ) : WP_THEME_WOO_SUPPORT_VERSION, true );
    wp_localize_script( 'wp-theme-woo-quote', 'wpThemeWooQuote', array( 'ajaxUrl'=>admin_url( 'admin-ajax.php' ), 'nonce'=>wp_create_nonce( 'wp_theme_woo_quote' ), 'quoteUrl'=>wp_theme_woo_quote_page_url(), 'variationMessage'=>__( 'Please choose all product options first.', 'wp-theme-woo-support' ) ) );
}, 30 );

add_action( 'add_meta_boxes_woo_quote_request', function() {
    add_meta_box( 'wp-theme-woo-quote-details', __( 'Quote details', 'wp-theme-woo-support' ), function( $post ) {
        $fields = array(
            __( 'Name', 'wp-theme-woo-support' ) => get_post_meta( $post->ID, '_quote_name', true ),
            __( 'Email', 'wp-theme-woo-support' ) => get_post_meta( $post->ID, '_quote_email', true ),
            __( 'Company', 'wp-theme-woo-support' ) => get_post_meta( $post->ID, '_quote_company', true ),
            __( 'Phone', 'wp-theme-woo-support' ) => get_post_meta( $post->ID, '_quote_phone', true ),
        );
        echo '<table class="widefat striped"><tbody>';
        foreach ( $fields as $label => $value ) echo '<tr><th style="width:140px">' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
        echo '</tbody></table>';
        $message = get_post_meta( $post->ID, '_quote_message', true );
        if ( $message ) echo '<h3>' . esc_html__( 'Message', 'wp-theme-woo-support' ) . '</h3><p>' . nl2br( esc_html( $message ) ) . '</p>';
        $items = get_post_meta( $post->ID, '_quote_items', true );
        if ( is_array( $items ) && $items ) {
            echo '<h3>' . esc_html__( 'Requested products', 'wp-theme-woo-support' ) . '</h3><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Product', 'wp-theme-woo-support' ) . '</th><th>' . esc_html__( 'Options', 'wp-theme-woo-support' ) . '</th><th>' . esc_html__( 'Quantity', 'wp-theme-woo-support' ) . '</th></tr></thead><tbody>';
            foreach ( $items as $item ) {
                $product = wp_theme_woo_quote_item_product( $item );
                if ( ! $product ) continue;
                $meta = array();
                foreach ( (array) ( $item['attributes'] ?? array() ) as $k => $v ) $meta[] = ucfirst( str_replace( array( 'attribute_pa_', 'attribute_', '-', '_' ), array( '', '', ' ', ' ' ), $k ) ) . ': ' . $v;
                foreach ( (array) ( $item['custom'] ?? array() ) as $k => $v ) $meta[] = ucfirst( str_replace( array( '-', '_' ), ' ', $k ) ) . ': ' . ( is_scalar( $v ) ? $v : '' );
                echo '<tr><td><a href="' . esc_url( get_edit_post_link( absint( $item['product_id'] ?? 0 ) ) ) . '">' . esc_html( $product->get_name() ) . '</a></td><td>' . esc_html( implode( ' · ', $meta ) ) . '</td><td>' . esc_html( absint( $item['quantity'] ?? 1 ) ) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
    }, 'woo_quote_request', 'normal', 'high' );
} );

add_filter( 'manage_woo_quote_request_posts_columns', function( $columns ) {
    return array( 'cb'=>$columns['cb'] ?? '<input type="checkbox" />', 'title'=>__( 'Request', 'wp-theme-woo-support' ), 'quote_email'=>__( 'Email', 'wp-theme-woo-support' ), 'quote_company'=>__( 'Company', 'wp-theme-woo-support' ), 'date'=>$columns['date'] ?? __( 'Date', 'wp-theme-woo-support' ) );
} );
add_action( 'manage_woo_quote_request_posts_custom_column', function( $column, $post_id ) {
    if ( 'quote_email' === $column ) echo esc_html( get_post_meta( $post_id, '_quote_email', true ) );
    if ( 'quote_company' === $column ) echo esc_html( get_post_meta( $post_id, '_quote_company', true ) );
}, 10, 2 );
