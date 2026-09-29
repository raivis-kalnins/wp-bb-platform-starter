<?php
defined( 'ABSPATH' ) || exit;

function wp_theme_sector_directory_configs() {
    $configs = apply_filters( 'wp_theme_sector_directory_configs', array() );
    return is_array( $configs ) ? $configs : array();
}

function wp_theme_sector_directory_config( $context ) {
    $context = sanitize_key( $context );
    $configs = wp_theme_sector_directory_configs();
    return isset( $configs[ $context ] ) && is_array( $configs[ $context ] ) ? $configs[ $context ] : array();
}

function wp_theme_sector_directory_request( $context, $source = null ) {
    $config = wp_theme_sector_directory_config( $context );
    if ( ! $config ) return array();
    $source = is_array( $source ) ? $source : $_REQUEST;
    $request = array(
        'keyword' => sanitize_text_field( wp_unslash( $source['keyword'] ?? '' ) ),
        'sort'    => sanitize_key( wp_unslash( $source['sort'] ?? ( $config['default_sort'] ?? 'featured' ) ) ),
    );
    foreach ( (array) ( $config['filters'] ?? array() ) as $filter ) {
        $key = sanitize_key( $filter['key'] ?? '' );
        if ( ! $key ) continue;
        $type = sanitize_key( $filter['type'] ?? 'text' );
        $raw = $source[ $key ] ?? '';
        if ( in_array( $type, array( 'meta_min','meta_max','number' ), true ) ) {
            $request[ $key ] = '' === $raw ? '' : (float) $raw;
        } elseif ( 'date' === $type ) {
            $request[ $key ] = sanitize_text_field( wp_unslash( $raw ) );
        } else {
            $request[ $key ] = sanitize_text_field( wp_unslash( $raw ) );
        }
    }
    return $request;
}

function wp_theme_sector_directory_query_args( $context, $request, $limit = 8 ) {
    $config = wp_theme_sector_directory_config( $context );
    if ( ! $config || empty( $config['post_type'] ) ) return array( 'post_type' => 'post', 'post__in' => array( 0 ) );
    $args = array(
        'post_type'      => sanitize_key( $config['post_type'] ),
        'post_status'    => 'publish',
        'posts_per_page' => max( 1, min( 48, absint( $limit ) ) ),
        'paged'          => max( 1, absint( $request['paged'] ?? 1 ) ),
    );
    if ( ! empty( $request['keyword'] ) ) $args['s'] = $request['keyword'];

    $tax_query = array();
    $meta_query = array();
    foreach ( (array) ( $config['filters'] ?? array() ) as $filter ) {
        $key = sanitize_key( $filter['key'] ?? '' );
        if ( ! $key || ! isset( $request[ $key ] ) || '' === (string) $request[ $key ] ) continue;
        $value = $request[ $key ];
        $type = sanitize_key( $filter['type'] ?? '' );
        if ( 'taxonomy' === $type && ! empty( $filter['taxonomy'] ) ) {
            $tax_query[] = array( 'taxonomy'=>sanitize_key( $filter['taxonomy'] ), 'field'=>'slug', 'terms'=>sanitize_title( $value ) );
        } elseif ( 'meta_select' === $type && ! empty( $filter['meta_key'] ) ) {
            $meta_query[] = array( 'key'=>sanitize_key( $filter['meta_key'] ), 'value'=>sanitize_text_field( (string) $value ), 'compare'=>'=' );
        } elseif ( 'meta_min' === $type && ! empty( $filter['meta_key'] ) ) {
            $meta_query[] = array( 'key'=>sanitize_key( $filter['meta_key'] ), 'value'=>(float)$value, 'compare'=>'>=', 'type'=>'NUMERIC' );
        } elseif ( 'meta_max' === $type && ! empty( $filter['meta_key'] ) ) {
            $meta_query[] = array( 'key'=>sanitize_key( $filter['meta_key'] ), 'value'=>(float)$value, 'compare'=>'<=', 'type'=>'NUMERIC' );
        } elseif ( 'date' === $type && ! empty( $filter['meta_key'] ) ) {
            $meta_query[] = array( 'key'=>sanitize_key( $filter['meta_key'] ), 'value'=>sanitize_text_field( (string) $value ), 'compare'=>'>=', 'type'=>'DATE' );
        }
    }
    if ( $tax_query ) $args['tax_query'] = count( $tax_query ) > 1 ? array_merge( array( 'relation'=>'AND' ), $tax_query ) : $tax_query;
    if ( $meta_query ) $args['meta_query'] = count( $meta_query ) > 1 ? array_merge( array( 'relation'=>'AND' ), $meta_query ) : $meta_query;

    $sort_key = sanitize_key( $request['sort'] ?? ( $config['default_sort'] ?? 'featured' ) );
    $sorts = (array) ( $config['sorts'] ?? array() );
    $sort = $sorts[ $sort_key ] ?? ( $sorts[ $config['default_sort'] ?? '' ] ?? array() );
    foreach ( array( 'orderby','order','meta_key' ) as $key ) if ( isset( $sort[ $key ] ) ) $args[ $key ] = $sort[ $key ];
    return apply_filters( 'wp_theme_sector_directory_query_args', $args, $context, $request, $config );
}

function wp_theme_sector_directory_tax_options( $taxonomy, $selected = '' ) {
    if ( ! taxonomy_exists( $taxonomy ) ) return '';
    $terms = get_terms( array( 'taxonomy'=>$taxonomy, 'hide_empty'=>false, 'orderby'=>'name' ) );
    if ( is_wp_error( $terms ) ) return '';
    $html = '';
    foreach ( $terms as $term ) $html .= '<option value="' . esc_attr( $term->slug ) . '"' . selected( $selected, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
    return $html;
}

function wp_theme_sector_directory_filter_field( $filter, $request ) {
    $key = sanitize_key( $filter['key'] ?? '' );
    if ( ! $key ) return '';
    $label = sanitize_text_field( $filter['label'] ?? ucfirst( str_replace( '_', ' ', $key ) ) );
    $type = sanitize_key( $filter['type'] ?? 'text' );
    $value = $request[ $key ] ?? '';
    $html = '<label class="wpbb-sector-finder__field"><span>' . esc_html( $label ) . '</span>';
    if ( 'taxonomy' === $type ) {
        $html .= '<select name="' . esc_attr( $key ) . '"><option value="">' . esc_html( $filter['all_label'] ?? __( 'Any', 'wp-theme' ) ) . '</option>' . wp_theme_sector_directory_tax_options( sanitize_key( $filter['taxonomy'] ?? '' ), $value ) . '</select>';
    } elseif ( 'meta_select' === $type ) {
        $html .= '<select name="' . esc_attr( $key ) . '"><option value="">' . esc_html( $filter['all_label'] ?? __( 'Any', 'wp-theme' ) ) . '</option>';
        foreach ( (array) ( $filter['options'] ?? array() ) as $option_value => $option_label ) $html .= '<option value="' . esc_attr( $option_value ) . '"' . selected( (string)$value, (string)$option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
        $html .= '</select>';
    } elseif ( in_array( $type, array( 'meta_min','meta_max','number' ), true ) ) {
        $html .= '<input type="number" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( $filter['min'] ?? 0 ) . '" step="' . esc_attr( $filter['step'] ?? 1 ) . '" placeholder="' . esc_attr( $filter['placeholder'] ?? '' ) . '">';
    } elseif ( 'date' === $type ) {
        $html .= '<input type="date" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
    } else {
        $html .= '<input type="text" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $filter['placeholder'] ?? '' ) . '">';
    }
    return $html . '</label>';
}

function wp_theme_sector_directory_default_card( $post_id, $context, $config ) {
    $terms = array();
    foreach ( (array) ( $config['card_taxonomies'] ?? array() ) as $taxonomy ) {
        $names = wp_get_post_terms( $post_id, sanitize_key( $taxonomy ), array( 'fields'=>'names' ) );
        if ( ! is_wp_error( $names ) ) $terms = array_merge( $terms, $names );
    }
    $meta_html = '';
    foreach ( (array) ( $config['card_meta'] ?? array() ) as $item ) {
        $value = get_post_meta( $post_id, sanitize_key( $item['key'] ?? '' ), true );
        if ( '' === (string) $value ) continue;
        $prefix = $item['prefix'] ?? '';
        $suffix = $item['suffix'] ?? '';
        if ( 'money' === ( $item['format'] ?? '' ) ) $value = ( $item['currency'] ?? '£' ) . number_format_i18n( (float) $value, (float)$value == (int)$value ? 0 : 2 );
        $meta_html .= '<span><small>' . esc_html( $item['label'] ?? '' ) . '</small><strong>' . esc_html( $prefix . $value . $suffix ) . '</strong></span>';
    }
    $media = function_exists( 'wp_theme_item_gallery_card_link' ) ? wp_theme_item_gallery_card_link( $post_id, 'wpbb-sector-card__media', 'large' ) : '<a class="wpbb-sector-card__media" href="' . esc_url( get_permalink( $post_id ) ) . '">' . get_the_post_thumbnail( $post_id, 'large', array( 'loading' => 'lazy' ) ) . '</a>';
    return '<article class="wpbb-sector-card motion-fade-up">' . $media . '<div class="wpbb-sector-card__body">' . ( $terms ? '<p class="wp-theme-sector-eyebrow">' . esc_html( implode( ' · ', array_slice( array_unique( $terms ), 0, 2 ) ) ) . '</p>' : '' ) . '<h3><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></h3><p>' . esc_html( get_the_excerpt( $post_id ) ) . '</p>' . ( $meta_html ? '<div class="wpbb-sector-card__meta">' . $meta_html . '</div>' : '' ) . '<a class="wpbb-sector-card__link" href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( $config['card_button'] ?? __( 'View details', 'wp-theme' ) ) . ' →</a></div></article>';
}

function wp_theme_sector_directory_results( $context, $request, $limit = 8 ) {
    $config = wp_theme_sector_directory_config( $context );
    $query = new WP_Query( wp_theme_sector_directory_query_args( $context, $request, $limit ) );
    $html = '<div class="wpbb-sector-finder__results-head"><strong>' . esc_html( sprintf( _n( '%d result', '%d results', $query->found_posts, 'wp-theme' ), $query->found_posts ) ) . '</strong><span>' . esc_html( $config['results_label'] ?? __( 'matching your search', 'wp-theme' ) ) . '</span></div>';
    if ( ! $query->have_posts() ) return $html . '<div class="wpbb-sector-finder__empty"><h3>' . esc_html__( 'No matching results yet.', 'wp-theme' ) . '</h3><p>' . esc_html__( 'Try removing one or two filters and search again.', 'wp-theme' ) . '</p></div>';
    $html .= '<div class="wpbb-sector-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $post_id = get_the_ID();
        if ( ! empty( $config['card_callback'] ) && is_callable( $config['card_callback'] ) ) $html .= call_user_func( $config['card_callback'], $post_id, $request, $config );
        else $html .= wp_theme_sector_directory_default_card( $post_id, $context, $config );
    }
    wp_reset_postdata();
    return $html . '</div>';
}

function wp_theme_sector_directory_render( $context, $attributes = array() ) {
    $config = wp_theme_sector_directory_config( $context );
    if ( ! $config ) return '';
    $limit = absint( $attributes['limit'] ?? ( $config['limit'] ?? 8 ) );
    $request = wp_theme_sector_directory_request( $context );
    $archive = ! empty( $config['archive_url'] ) && is_callable( $config['archive_url'] ) ? call_user_func( $config['archive_url'] ) : get_post_type_archive_link( $config['post_type'] );
    $archive = $archive ?: home_url( '/' );
    $title = sanitize_text_field( $attributes['title'] ?? '' );
    if ( ! $title ) $title = sanitize_text_field( $config['title'] ?? __( 'Find what you need', 'wp-theme' ) );
    $sorts = (array) ( $config['sorts'] ?? array() );
    ob_start();
    ?>
    <section class="wpbb-sector-finder" data-wpbb-sector-finder data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
      <div class="wpbb-sector-finder__intro"><p class="wp-theme-sector-eyebrow"><?php echo esc_html( $config['eyebrow'] ?? __( 'Search', 'wp-theme' ) ); ?></p><h2><?php echo esc_html( $title ); ?></h2><?php if ( ! empty( $config['intro'] ) ) : ?><p><?php echo esc_html( $config['intro'] ); ?></p><?php endif; ?></div>
      <form class="wpbb-sector-finder__form" method="get" action="<?php echo esc_url( $archive ); ?>" data-sector-finder-form>
        <input type="hidden" name="action" value="wpbb_sector_directory_find"><input type="hidden" name="context" value="<?php echo esc_attr( $context ); ?>"><input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wpbb_sector_directory_' . $context ) ); ?>"><input type="hidden" name="limit" value="<?php echo esc_attr( $limit ); ?>">
        <label class="wpbb-sector-finder__field wpbb-sector-finder__field--keyword"><span><?php echo esc_html( $config['keyword_label'] ?? __( 'Keyword', 'wp-theme' ) ); ?></span><input type="search" name="keyword" value="<?php echo esc_attr( $request['keyword'] ?? '' ); ?>" placeholder="<?php echo esc_attr( $config['keyword_placeholder'] ?? __( 'Search…', 'wp-theme' ) ); ?>"></label>
        <?php foreach ( (array) ( $config['filters'] ?? array() ) as $filter ) echo wp_theme_sector_directory_filter_field( $filter, $request ); ?>
        <?php if ( $sorts ) : ?><label class="wpbb-sector-finder__field"><span><?php esc_html_e( 'Sort by', 'wp-theme' ); ?></span><select name="sort"><?php foreach ( $sorts as $key=>$sort ) : ?><option value="<?php echo esc_attr( $key ); ?>"<?php selected( $request['sort'] ?? '', $key ); ?>><?php echo esc_html( $sort['label'] ?? ucfirst( $key ) ); ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <div class="wpbb-sector-finder__actions"><button class="btn btn-primary" type="submit"><?php echo esc_html( $config['button_label'] ?? __( 'Search', 'wp-theme' ) ); ?></button><button class="btn btn-outline-primary" type="reset" data-sector-finder-reset><?php esc_html_e( 'Clear', 'wp-theme' ); ?></button></div>
      </form>
      <div class="wpbb-sector-finder__results" data-sector-finder-results aria-live="polite"><?php echo wp_theme_sector_directory_results( $context, $request, $limit ); ?></div>
    </section>
    <?php
    return ob_get_clean();
}

function wp_theme_sector_directory_block_render( $html, $context, $attributes ) {
    $config = wp_theme_sector_directory_config( $context );
    return $config ? wp_theme_sector_directory_render( $context, $attributes ) : $html;
}
add_filter( 'wp_theme_sector_finder_render', 'wp_theme_sector_directory_block_render', 5, 3 );

function wp_theme_sector_directory_ajax() {
    $context = sanitize_key( wp_unslash( $_REQUEST['context'] ?? '' ) );
    $config = wp_theme_sector_directory_config( $context );
    if ( ! $config ) wp_send_json_error( array( 'message'=>__( 'Unknown finder.', 'wp-theme' ) ), 400 );
    check_ajax_referer( 'wpbb_sector_directory_' . $context, 'nonce' );
    $request = wp_theme_sector_directory_request( $context );
    $limit = max( 1, min( 48, absint( $_REQUEST['limit'] ?? ( $config['limit'] ?? 8 ) ) ) );
    wp_send_json_success( array( 'html'=>wp_theme_sector_directory_results( $context, $request, $limit ) ) );
}
add_action( 'wp_ajax_wpbb_sector_directory_find', 'wp_theme_sector_directory_ajax' );
add_action( 'wp_ajax_nopriv_wpbb_sector_directory_find', 'wp_theme_sector_directory_ajax' );

function wp_theme_sector_directory_assets() {
    if ( ! wp_theme_sector_directory_configs() ) return;
    $path = get_template_directory() . '/assets/js/sector-finder.js';
    if ( is_readable( $path ) ) wp_enqueue_script( 'wp-theme-sector-finder', get_template_directory_uri() . '/assets/js/sector-finder.js', array(), filemtime( $path ), true );
}
add_action( 'wp_enqueue_scripts', 'wp_theme_sector_directory_assets', 35 );
