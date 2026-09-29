<?php
/** Editor productivity: clone posts/CPTs and section cloning helpers. */
defined( 'ABSPATH' ) || exit;

function wp_theme_clone_supported_post_types() {
    $objects = get_post_types( array( 'show_ui' => true ), 'objects' );
    $types = array();
    foreach ( $objects as $name => $object ) {
        if ( in_array( $name, array( 'attachment', 'revision', 'nav_menu_item', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' ), true ) ) continue;
        if ( ! post_type_supports( $name, 'editor' ) && ! in_array( $name, array( 'page', 'post' ), true ) ) continue;
        $types[] = $name;
    }
    return apply_filters( 'wp_theme_clone_supported_post_types', $types );
}

function wp_theme_clone_post_url( $post_id ) {
    return wp_nonce_url( admin_url( 'admin.php?action=wp_theme_clone_post&post=' . absint( $post_id ) ), 'wp_theme_clone_post_' . absint( $post_id ) );
}

function wp_theme_clone_post_action() {
    $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
    if ( ! $post_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'wp_theme_clone_post_' . $post_id ) ) wp_die( esc_html__( 'Invalid clone request.', 'wp-theme' ) );
    $source = get_post( $post_id );
    if ( ! $source instanceof WP_Post || ! in_array( $source->post_type, wp_theme_clone_supported_post_types(), true ) ) wp_die( esc_html__( 'This content type cannot be cloned.', 'wp-theme' ) );
    $type_object = get_post_type_object( $source->post_type );
    if ( ! $type_object || ! current_user_can( 'edit_post', $post_id ) ) wp_die( esc_html__( 'You do not have permission to clone this content.', 'wp-theme' ) );

    $new_id = wp_insert_post( array(
        'post_type' => $source->post_type,
        'post_status' => 'draft',
        'post_title' => sprintf( __( '%s - Copy', 'wp-theme' ), $source->post_title ),
        'post_content' => $source->post_content,
        'post_excerpt' => $source->post_excerpt,
        'post_author' => get_current_user_id(),
        'post_parent' => $source->post_parent,
        'menu_order' => $source->menu_order,
        'comment_status' => $source->comment_status,
        'ping_status' => $source->ping_status,
    ), true );
    if ( is_wp_error( $new_id ) ) wp_die( esc_html( $new_id->get_error_message() ) );

    $base_slug = sanitize_title( $source->post_name ?: $source->post_title );
    wp_update_post( array( 'ID' => $new_id, 'post_name' => $base_slug . '-copy-' . absint( $new_id ) ) );

    foreach ( get_post_taxonomies( $source ) as $taxonomy ) {
        $terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
        if ( ! is_wp_error( $terms ) ) wp_set_object_terms( $new_id, $terms, $taxonomy );
    }
    foreach ( get_post_meta( $post_id ) as $key => $values ) {
        if ( in_array( $key, array( '_edit_lock', '_edit_last', '_wp_old_slug' ), true ) ) continue;
        foreach ( $values as $value ) add_post_meta( $new_id, $key, maybe_unserialize( $value ) );
    }
    update_post_meta( $new_id, '_wp_theme_cloned_from', $post_id );
    wp_safe_redirect( get_edit_post_link( $new_id, 'url' ) );
    exit;
}
add_action( 'admin_action_wp_theme_clone_post', 'wp_theme_clone_post_action' );

function wp_theme_clone_row_actions( $actions, $post ) {
    if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, wp_theme_clone_supported_post_types(), true ) ) return $actions;
    $object = get_post_type_object( $post->post_type );
    if ( ! $object || ! current_user_can( 'edit_post', $post->ID ) ) return $actions;
    $actions['wp_theme_clone'] = '<a href="' . esc_url( wp_theme_clone_post_url( $post->ID ) ) . '" aria-label="' . esc_attr( sprintf( __( 'Clone %s', 'wp-theme' ), $post->post_title ) ) . '">' . esc_html__( 'Clone', 'wp-theme' ) . '</a>';
    return $actions;
}
add_filter( 'post_row_actions', 'wp_theme_clone_row_actions', 20, 2 );
add_filter( 'page_row_actions', 'wp_theme_clone_row_actions', 20, 2 );

function wp_theme_clone_admin_bar( $bar ) {
    if ( ! is_admin_bar_showing() || ! is_singular() ) return;
    $post_id = get_queried_object_id();
    $post = get_post( $post_id );
    if ( ! $post || ! in_array( $post->post_type, wp_theme_clone_supported_post_types(), true ) ) return;
    $object = get_post_type_object( $post->post_type );
    if ( ! $object || ! current_user_can( 'edit_post', $post_id ) ) return;
    $bar->add_node( array( 'id' => 'wp-theme-clone-post', 'title' => __( 'Clone as draft', 'wp-theme' ), 'href' => wp_theme_clone_post_url( $post_id ), 'parent' => 'edit' ) );
}
add_action( 'admin_bar_menu', 'wp_theme_clone_admin_bar', 90 );

function wp_theme_pattern_library_files( $with_content = false ) {
    $roots = array_unique( array( get_template_directory() . '/patterns', get_stylesheet_directory() . '/patterns' ) );
    $items = array();
    foreach ( $roots as $root ) {
        if ( ! is_dir( $root ) ) continue;
        foreach ( glob( trailingslashit( $root ) . '*.php' ) ?: array() as $file ) {
            if ( 0 === strpos( basename( $file ), '_' ) ) continue;
            $data = get_file_data( $file, array( 'title' => 'Title', 'slug' => 'Slug', 'categories' => 'Categories', 'description' => 'Description', 'block_types' => 'Block Types' ) );
            if ( empty( $data['title'] ) || false !== strpos( (string) $data['block_types'], 'core/template-part' ) ) continue;
            $item = array( 'title' => $data['title'], 'slug' => $data['slug'], 'categories' => $data['categories'], 'description' => $data['description'], 'source' => 0 === strpos( wp_normalize_path( $file ), wp_normalize_path( get_stylesheet_directory() ) ) && get_stylesheet_directory() !== get_template_directory() ? __( 'Child theme', 'wp-theme' ) : __( 'Shared', 'wp-theme' ) );
            if ( $with_content ) {
                ob_start();
                include $file;
                $item['content'] = (string) ob_get_clean();
            }
            $items[] = $item;
        }
    }
    usort( $items, static function( $a, $b ) { return strcasecmp( $a['title'], $b['title'] ); } );
    return $items;
}

function wp_theme_pattern_library_payload() {
    $payload = array();
    foreach ( wp_theme_pattern_library_files( true ) as $pattern ) {
        if ( empty( $pattern['content'] ) ) continue;
        $payload[] = array(
            'title' => (string) $pattern['title'],
            'slug' => (string) $pattern['slug'],
            'description' => (string) $pattern['description'],
            'source' => (string) $pattern['source'],
            'content' => (string) $pattern['content'],
        );
    }
    return $payload;
}

function wp_theme_register_pattern_library_page() {
    add_theme_page( __( 'Pattern Library', 'wp-theme' ), __( 'Pattern Library', 'wp-theme' ), 'edit_pages', 'wp-theme-pattern-library', 'wp_theme_pattern_library_page' );
}
add_action( 'admin_menu', 'wp_theme_register_pattern_library_page' );

function wp_theme_pattern_library_page() {
    if ( ! current_user_can( 'edit_pages' ) ) return;
    $patterns = wp_theme_pattern_library_files();
    $new_page = admin_url( 'post-new.php?post_type=page&wp_theme_open_patterns=1' );
    ?>
    <div class="wrap wp-theme-pattern-library-admin"><div class="wp-theme-pattern-library-hero"><div><p><?php esc_html_e( 'WP BBTheme', 'wp-theme' ); ?></p><h1><?php esc_html_e( 'Pattern Library', 'wp-theme' ); ?></h1><span><?php esc_html_e( 'Reusable BBuilder and Gutenberg sections from the parent and active child theme.', 'wp-theme' ); ?></span></div><a class="button button-primary button-hero" href="<?php echo esc_url( $new_page ); ?>"><?php esc_html_e( 'Create page with patterns', 'wp-theme' ); ?></a></div>
        <div class="wp-theme-pattern-library-grid">
        <?php foreach ( $patterns as $pattern ) : ?><article class="wp-theme-pattern-library-card"><div class="wp-theme-pattern-library-card__meta"><span><?php echo esc_html( $pattern['source'] ); ?></span><?php if ( $pattern['categories'] ) : ?><span><?php echo esc_html( $pattern['categories'] ); ?></span><?php endif; ?></div><h2><?php echo esc_html( $pattern['title'] ); ?></h2><p><?php echo esc_html( $pattern['description'] ?: __( 'Reusable section ready to insert from the editor Patterns panel.', 'wp-theme' ) ); ?></p><code><?php echo esc_html( $pattern['slug'] ); ?></code></article><?php endforeach; ?>
        </div>
    </div>
    <style>.wp-theme-pattern-library-admin{max-width:1280px}.wp-theme-pattern-library-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin:28px 0;padding:30px;border:1px solid #dcdcde;border-radius:18px;background:#fff}.wp-theme-pattern-library-hero p{margin:0 0 8px;color:#3858e9;font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.wp-theme-pattern-library-hero h1{margin:0 0 8px;font-size:34px}.wp-theme-pattern-library-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.wp-theme-pattern-library-card{padding:22px;border:1px solid #dcdcde;border-radius:14px;background:#fff}.wp-theme-pattern-library-card h2{margin:14px 0 8px;font-size:18px}.wp-theme-pattern-library-card p{min-height:44px;color:#50575e}.wp-theme-pattern-library-card__meta{display:flex;gap:7px;flex-wrap:wrap}.wp-theme-pattern-library-card__meta span{padding:4px 8px;border-radius:999px;background:#f0f0f1;font-size:11px;font-weight:700}.wp-theme-pattern-library-card code{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}@media(max-width:960px){.wp-theme-pattern-library-grid{grid-template-columns:1fr 1fr}}@media(max-width:620px){.wp-theme-pattern-library-hero{align-items:flex-start;flex-direction:column}.wp-theme-pattern-library-grid{grid-template-columns:1fr}}</style>
    <?php
}
