<?php
/**
 * Shared item gallery for sector CPTs and WooCommerce loop cards.
 *
 * The feature intentionally reuses the existing WooCommerce product gallery
 * and the Post Project Gallery plugin meta key when present. Other public
 * thumbnail-enabled CPTs receive a small native WordPress media gallery box.
 */
defined( 'ABSPATH' ) || exit;

function wp_theme_item_gallery_supported_post_types() {
    $supported = array();
    foreach ( get_post_types( array( 'public' => true, 'show_ui' => true ), 'objects' ) as $name => $object ) {
        if ( in_array( $name, array( 'attachment', 'page', 'post' ), true ) ) continue;
        if ( post_type_supports( $name, 'thumbnail' ) ) $supported[] = $name;
    }
    return array_values( array_unique( apply_filters( 'wp_theme_item_gallery_supported_post_types', $supported ) ) );
}

function wp_theme_item_gallery_ids( $post_id, $include_featured = true ) {
    $post_id = absint( $post_id );
    if ( ! $post_id ) return array();
    $ids = array();
    if ( $include_featured ) {
        $featured = get_post_thumbnail_id( $post_id );
        if ( $featured ) $ids[] = absint( $featured );
    }

    $post_type = get_post_type( $post_id );
    if ( 'product' === $post_type ) {
        $raw = get_post_meta( $post_id, '_product_image_gallery', true );
    } else {
        $raw = get_post_meta( $post_id, '_wp_theme_gallery_ids', true );
        if ( ! $raw ) $raw = get_post_meta( $post_id, '_wp_theme_item_gallery_ids', true );
        if ( ! $raw ) $raw = get_post_meta( $post_id, '_wpbb_child_gallery_ids', true );
        if ( ! $raw ) $raw = get_post_meta( $post_id, '_wp_theme_item_gallery', true );
        if ( ! $raw ) $raw = get_post_meta( $post_id, '_ppg_project_gallery_ids', true );
    }
    if ( is_string( $raw ) ) $raw = preg_split( '/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY );
    if ( is_array( $raw ) ) $ids = array_merge( $ids, array_map( 'absint', $raw ) );

    $ids = array_values( array_filter( array_unique( $ids ) ) );
    return apply_filters( 'wp_theme_item_gallery_ids', $ids, $post_id, $include_featured );
}

function wp_theme_item_gallery_items( $post_id, $size = 'large' ) {
    $items = array();
    foreach ( wp_theme_item_gallery_ids( $post_id, true ) as $attachment_id ) {
        $url = wp_get_attachment_image_url( $attachment_id, $size );
        if ( ! $url ) continue;
        $thumb = wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) ?: $url;
        $full = wp_get_attachment_image_url( $attachment_id, 'full' ) ?: $url;
        $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
        if ( ! $alt ) $alt = get_the_title( $post_id );
        $caption = wp_get_attachment_caption( $attachment_id );
        $items[] = array(
            'id' => absint( $attachment_id ),
            'url' => $url,
            'thumb' => $thumb,
            'full' => $full,
            'alt' => sanitize_text_field( $alt ),
            'caption' => sanitize_text_field( $caption ),
        );
    }
    return $items;
}

function wp_theme_item_gallery_card_inner( $post_id, $size = 'large', $max_thumbs = 4 ) {
    $items = wp_theme_item_gallery_items( $post_id, $size );
    if ( ! $items ) return '<span class="wp-theme-item-gallery__placeholder" aria-hidden="true"></span>';
    $first = $items[0];
    $count = count( $items );
    $payload = array_map( static function( $item ) {
        return array(
            'url'     => $item['url'],
            'thumb'   => $item['thumb'],
            'full'    => $item['full'],
            'alt'     => $item['alt'],
            'caption' => $item['caption'],
        );
    }, $items );
    $json = esc_attr( wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
    $html = '<span class="wp-theme-item-gallery-card" data-item-gallery-card data-gallery-count="' . absint( $count ) . '" data-gallery-items="' . $json . '">';
    $html .= '<img class="wp-theme-item-gallery-card__main" data-item-gallery-main data-index="0" src="' . esc_url( $first['url'] ) . '" alt="' . esc_attr( $first['alt'] ) . '" loading="lazy">';
    if ( $count > 1 ) {
        $html .= '<span class="wp-theme-item-gallery-card__open" role="button" tabindex="0" data-item-gallery-card-open aria-label="' . esc_attr( sprintf( __( 'Open image gallery with %d images', 'wp-theme' ), $count ) ) . '"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span>' . absint( $count ) . '</span></span>';
        $html .= '<span class="wp-theme-item-gallery-card__thumbs" aria-label="' . esc_attr__( 'Image gallery', 'wp-theme' ) . '">';
        foreach ( array_slice( $items, 0, max( 2, absint( $max_thumbs ) ) ) as $index => $item ) {
            $html .= '<span class="wp-theme-item-gallery-card__thumb' . ( 0 === $index ? ' is-active' : '' ) . '" role="button" tabindex="0" aria-label="' . esc_attr( sprintf( __( 'Show image %d', 'wp-theme' ), $index + 1 ) ) . '" data-item-gallery-thumb data-index="' . absint( $index ) . '" data-full="' . esc_url( $item['url'] ) . '" data-lightbox-full="' . esc_url( $item['full'] ) . '" data-alt="' . esc_attr( $item['alt'] ) . '" data-caption="' . esc_attr( $item['caption'] ) . '"><img src="' . esc_url( $item['thumb'] ) . '" alt="" loading="lazy"></span>';
        }
        if ( $count > $max_thumbs ) $html .= '<span class="wp-theme-item-gallery-card__more" role="button" tabindex="0" data-item-gallery-card-open aria-label="' . esc_attr( sprintf( __( 'Open all %d gallery images', 'wp-theme' ), $count ) ) . '">+' . esc_html( $count - $max_thumbs ) . '</span>';
        $html .= '</span>';
    }
    return $html . '</span>';
}

function wp_theme_item_gallery_card_link( $post_id, $class = 'wpbb-sector-card__media', $size = 'large' ) {
    return '<a class="' . esc_attr( $class ) . ' wp-theme-item-gallery-link" href="' . esc_url( get_permalink( $post_id ) ) . '">' . wp_theme_item_gallery_card_inner( $post_id, $size ) . '</a>';
}

function wp_theme_item_gallery_single_markup( $post_id ) {
    $items = wp_theme_item_gallery_items( $post_id, 'large' );
    if ( count( $items ) < 2 ) return '';
    $first = $items[0];
    $count = count( $items );
    ob_start();
    ?>
    <?php $gallery_payload = esc_attr( wp_json_encode( array_map( static function( $item ) { return array( 'url'=>$item['url'], 'thumb'=>$item['thumb'], 'full'=>$item['full'], 'alt'=>$item['alt'], 'caption'=>$item['caption'] ); }, $items ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?>
    <section class="wp-theme-item-gallery wp-theme-item-gallery--single" data-item-gallery-single data-gallery-count="<?php echo absint( $count ); ?>" data-gallery-items="<?php echo $gallery_payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
        <div class="wp-theme-item-gallery__stage">
            <button type="button" class="wp-theme-item-gallery__stage-button" data-item-gallery-open aria-label="<?php echo esc_attr( sprintf( __( 'Open image gallery with %d images', 'wp-theme' ), $count ) ); ?>">
                <img src="<?php echo esc_url( $first['url'] ); ?>" alt="<?php echo esc_attr( $first['alt'] ); ?>" data-item-gallery-main data-index="0">
                <span class="wp-theme-item-gallery__stage-action" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3M16 21h3a2 2 0 0 0 2-2v-3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 14l2.6-2.7 2.1 2.1 1.8-1.8L18 15H8Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    <?php esc_html_e( 'View gallery', 'wp-theme' ); ?>
                    <span><?php echo absint( $count ); ?></span>
                </span>
            </button>
            <div class="wp-theme-item-gallery__thumbs wp-theme-item-gallery__thumbs--overlay" role="list" aria-label="<?php esc_attr_e( 'More images', 'wp-theme' ); ?>">
                <?php foreach ( $items as $index => $item ) : ?>
                    <button type="button" class="wp-theme-item-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-item-gallery-thumb data-index="<?php echo absint( $index ); ?>" data-full="<?php echo esc_url( $item['url'] ); ?>" data-lightbox-full="<?php echo esc_url( $item['full'] ); ?>" data-alt="<?php echo esc_attr( $item['alt'] ); ?>" data-caption="<?php echo esc_attr( $item['caption'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Show image %d of %d', 'wp-theme' ), $index + 1, $count ) ); ?>">
                        <img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <div class="wp-theme-item-lightbox" data-item-gallery-lightbox hidden aria-hidden="true" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Image gallery', 'wp-theme' ); ?>">
        <button type="button" class="wp-theme-item-lightbox__backdrop" data-item-gallery-close tabindex="-1" aria-label="<?php esc_attr_e( 'Close gallery', 'wp-theme' ); ?>"></button>
        <div class="wp-theme-item-lightbox__dialog" role="document">
            <div class="wp-theme-item-lightbox__topbar">
                <span class="wp-theme-item-lightbox__counter" data-item-gallery-counter>1 / <?php echo absint( $count ); ?></span>
                <button type="button" class="wp-theme-item-lightbox__close" data-item-gallery-close aria-label="<?php esc_attr_e( 'Close gallery', 'wp-theme' ); ?>">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="wp-theme-item-lightbox__viewer">
                <button type="button" class="wp-theme-item-lightbox__nav wp-theme-item-lightbox__nav--prev" data-item-gallery-prev aria-label="<?php esc_attr_e( 'Previous image', 'wp-theme' ); ?>">
                    <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="m14 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <figure class="wp-theme-item-lightbox__figure">
                    <img src="<?php echo esc_url( $first['full'] ); ?>" alt="<?php echo esc_attr( $first['alt'] ); ?>" data-item-gallery-lightbox-image>
                    <figcaption data-item-gallery-caption<?php echo empty( $first['caption'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $first['caption'] ); ?></figcaption>
                </figure>
                <button type="button" class="wp-theme-item-lightbox__nav wp-theme-item-lightbox__nav--next" data-item-gallery-next aria-label="<?php esc_attr_e( 'Next image', 'wp-theme' ); ?>">
                    <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="m10 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <div class="wp-theme-item-lightbox__thumbs" role="list" aria-label="<?php esc_attr_e( 'Gallery thumbnails', 'wp-theme' ); ?>">
                <?php foreach ( $items as $index => $item ) : ?>
                    <button type="button" class="wp-theme-item-lightbox__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-item-gallery-lightbox-thumb data-index="<?php echo absint( $index ); ?>" data-full="<?php echo esc_url( $item['full'] ); ?>" data-alt="<?php echo esc_attr( $item['alt'] ); ?>" data-caption="<?php echo esc_attr( $item['caption'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View image %d of %d', 'wp-theme' ), $index + 1, $count ) ); ?>">
                        <img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function wp_theme_item_gallery_prepend_single( $content ) {
    if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) return $content;
    $post_id = get_the_ID();
    if ( ! $post_id || 'product' === get_post_type( $post_id ) ) return $content;
    if ( ! in_array( get_post_type( $post_id ), wp_theme_item_gallery_supported_post_types(), true ) ) return $content;
    if ( false !== strpos( $content, 'wp-theme-item-gallery--single' ) || false !== strpos( $content, 'wpbb-child-sector-gallery' ) ) return $content;
    $gallery = wp_theme_item_gallery_single_markup( $post_id );
    return $gallery ? $gallery . $content : $content;
}
add_filter( 'the_content', 'wp_theme_item_gallery_prepend_single', 99 );

function wp_theme_item_gallery_add_meta_boxes() {
    foreach ( wp_theme_item_gallery_supported_post_types() as $post_type ) {
        if ( 'product' === $post_type ) continue; // WooCommerce already provides a Product gallery.
        add_meta_box( 'wp_theme_item_gallery', __( 'Item Gallery', 'wp-theme' ), 'wp_theme_item_gallery_meta_box', $post_type, 'normal', 'high' );
    }
}
add_action( 'add_meta_boxes', 'wp_theme_item_gallery_add_meta_boxes', 20 );

function wp_theme_item_gallery_meta_box( $post ) {
    wp_nonce_field( 'wp_theme_save_item_gallery', 'wp_theme_item_gallery_nonce' );
    $ids = wp_theme_item_gallery_ids( $post->ID, false );
    ?>
    <p><?php esc_html_e( 'Select additional images for this item. The Featured Image stays first. When two or more images are available, list cards show thumbnails and the single item view shows a larger thumbnail gallery.', 'wp-theme' ); ?></p>
    <input type="hidden" id="wp-theme-item-gallery-ids" name="wp_theme_item_gallery_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
    <div class="wp-theme-item-gallery-admin" data-item-gallery-admin>
        <?php foreach ( $ids as $id ) : $thumb = wp_get_attachment_image_url( $id, 'thumbnail' ); if ( ! $thumb ) continue; ?>
            <span class="wp-theme-item-gallery-admin__thumb" data-id="<?php echo esc_attr( $id ); ?>"><img src="<?php echo esc_url( $thumb ); ?>" alt=""><button type="button" data-gallery-remove aria-label="<?php esc_attr_e( 'Remove image', 'wp-theme' ); ?>">&times;</button></span>
        <?php endforeach; ?>
    </div>
    <p><button type="button" class="button button-primary" data-gallery-select><?php esc_html_e( 'Select / Edit Gallery Images', 'wp-theme' ); ?></button> <button type="button" class="button" data-gallery-clear><?php esc_html_e( 'Clear gallery', 'wp-theme' ); ?></button></p>
    <?php
}

function wp_theme_item_gallery_save_meta( $post_id ) {
    if ( ! isset( $_POST['wp_theme_item_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_theme_item_gallery_nonce'] ) ), 'wp_theme_save_item_gallery' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    $raw = isset( $_POST['wp_theme_item_gallery_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_theme_item_gallery_ids'] ) ) : '';
    $ids = array_values( array_filter( array_unique( array_map( 'absint', preg_split( '/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY ) ) ) ) );
    if ( $ids ) update_post_meta( $post_id, '_wp_theme_gallery_ids', implode( ',', $ids ) );
    else delete_post_meta( $post_id, '_wp_theme_gallery_ids' );
}
add_action( 'save_post', 'wp_theme_item_gallery_save_meta', 20 );

function wp_theme_item_gallery_admin_assets( $hook ) {
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) return;
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, wp_theme_item_gallery_supported_post_types(), true ) || 'product' === $screen->post_type ) return;
    wp_enqueue_media();
    $base = get_template_directory();
    wp_enqueue_style( 'wp-theme-item-gallery-admin', get_template_directory_uri() . '/assets/css/item-gallery-admin.css', array(), filemtime( $base . '/assets/css/item-gallery-admin.css' ) );
    wp_enqueue_script( 'wp-theme-item-gallery-admin', get_template_directory_uri() . '/assets/js/item-gallery-admin.js', array( 'jquery' ), filemtime( $base . '/assets/js/item-gallery-admin.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'wp_theme_item_gallery_admin_assets' );

function wp_theme_item_gallery_front_assets() {
    if ( is_admin() ) return;
    $base = get_template_directory();
    wp_enqueue_script( 'wp-theme-item-gallery', get_template_directory_uri() . '/assets/js/item-gallery.js', array(), filemtime( $base . '/assets/js/item-gallery.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'wp_theme_item_gallery_front_assets', 58 );

function wp_theme_item_gallery_enable_woocommerce_loop() {
    if ( ! function_exists( 'woocommerce_template_loop_product_thumbnail' ) ) return;
    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
    add_action( 'woocommerce_before_shop_loop_item_title', 'wp_theme_item_gallery_woocommerce_loop_media', 10 );
}
add_action( 'wp', 'wp_theme_item_gallery_enable_woocommerce_loop', 20 );

function wp_theme_item_gallery_woocommerce_loop_media() {
    global $product;
    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) return;
    $items = wp_theme_item_gallery_items( $product->get_id(), 'woocommerce_thumbnail' );
    if ( count( $items ) <= 1 ) {
        echo woocommerce_get_product_thumbnail(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }
    echo '<span class="wp-theme-woo-loop-gallery">' . wp_theme_item_gallery_card_inner( $product->get_id(), 'woocommerce_thumbnail', 4 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
