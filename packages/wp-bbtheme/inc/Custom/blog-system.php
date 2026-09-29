<?php
/**
 * Shared editorial blog experience.
 *
 * The parent owns querying, accessible markup and AJAX behaviour contracts.
 * Child themes own all visual presentation in their compiled SCSS.
 */
defined( 'ABSPATH' ) || exit;

function wp_theme_blog_archive_url() {
    $page_id = absint( get_option( 'page_for_posts' ) );
    if ( $page_id ) {
        $url = get_permalink( $page_id );
        if ( $url ) return $url;
    }
    return home_url( '/blog/' );
}

function wp_theme_blog_reading_minutes( $post_id ) {
    $content = (string) get_post_field( 'post_content', $post_id );
    $content = wp_strip_all_tags( strip_shortcodes( $content ) );
    $words = preg_split( '/\s+/u', trim( $content ), -1, PREG_SPLIT_NO_EMPTY );
    $count = is_array( $words ) ? count( $words ) : 0;
    return max( 1, (int) ceil( $count / 220 ) );
}

function wp_theme_blog_profile() {
    $profile = function_exists( 'wp_theme_get_demo_profile' ) ? (array) wp_theme_get_demo_profile() : array();
    $lang = function_exists( 'wp_theme_current_language_slug' ) ? sanitize_key( (string) wp_theme_current_language_slug() ) : '';
    if ( $lang && 'en' !== $lang && function_exists( 'wp_theme_demo_translate_starter_string' ) ) {
        foreach ( array( 'blog_eyebrow', 'blog_archive_title', 'blog_archive_intro', 'blog_heading' ) as $key ) {
            if ( isset( $profile[ $key ] ) && is_string( $profile[ $key ] ) ) {
                $profile[ $key ] = wp_theme_demo_translate_starter_string( $profile[ $key ], $lang );
            }
        }
    }
    return $profile;
}

function wp_theme_blog_archive_title() {
    $profile = wp_theme_blog_profile();
    if ( is_category() ) {
        return single_cat_title( '', false );
    }
    if ( is_tag() ) {
        return single_tag_title( '', false );
    }
    return $profile['blog_archive_title'] ?? __( 'Latest stories and useful guides.', 'wp-theme' );
}

function wp_theme_blog_archive_intro() {
    $profile = wp_theme_blog_profile();
    if ( is_category() ) {
        $description = category_description();
        if ( $description ) return wp_strip_all_tags( $description );
    }
    return $profile['blog_archive_intro'] ?? __( 'Ideas, guides and practical updates designed to help visitors make a better-informed next decision.', 'wp-theme' );
}

function wp_theme_blog_is_uncategorized_term( $term ) {
    if ( ! $term instanceof WP_Term ) return false;
    $slug = sanitize_title( $term->slug );
    $name = strtolower( trim( (string) $term->name ) );
    if ( in_array( $slug, array( 'uncategorized', 'uncategorised' ), true ) || 0 === strpos( $slug, 'uncategorized-' ) || 0 === strpos( $slug, 'uncategorised-' ) ) return true;
    if ( in_array( $name, array( 'uncategorized', 'uncategorised' ), true ) ) return true;
    if ( function_exists( 'pll_get_term' ) ) {
        $english_id = absint( pll_get_term( $term->term_id, 'en' ) );
        if ( $english_id ) {
            $english = get_term( $english_id, $term->taxonomy );
            if ( $english instanceof WP_Term ) {
                $english_slug = sanitize_title( $english->slug );
                if ( in_array( $english_slug, array( 'uncategorized', 'uncategorised' ), true ) ) return true;
            }
        }
    }
    return false;
}

function wp_theme_blog_term_label( $term ) {
    if ( ! $term instanceof WP_Term ) return '';
    $name = $term->name;
    if ( function_exists( 'wp_theme_demo_taxonomy_label' ) ) {
        $lang = function_exists( 'wp_theme_current_language_slug' ) ? wp_theme_current_language_slug() : 'en';
        // Prefer the English source term as the stable translation key.
        if ( function_exists( 'pll_get_term' ) ) {
            $english_id = absint( pll_get_term( $term->term_id, 'en' ) );
            if ( $english_id ) {
                $english = get_term( $english_id, $term->taxonomy );
                if ( $english instanceof WP_Term ) $name = $english->name;
            }
        }
        return wp_theme_demo_taxonomy_label( $name, $lang );
    }
    return $name;
}

function wp_theme_blog_current_language() {
    if ( function_exists( 'wp_theme_current_language_slug' ) ) {
        $lang = sanitize_key( (string) wp_theme_current_language_slug() );
        if ( $lang ) return $lang;
    }
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = sanitize_key( (string) pll_current_language( 'slug' ) );
        if ( $lang ) return $lang;
    }
    return '';
}

function wp_theme_blog_category_count( $term ) {
    if ( ! $term instanceof WP_Term ) return 0;
    $lang = wp_theme_blog_current_language();
    if ( ! $lang ) return max( 0, (int) $term->count );
    $query = new WP_Query( array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'fields'              => 'ids',
        'posts_per_page'      => 1,
        'lang'                => $lang,
        'category_name'       => sanitize_title( $term->slug ),
    ) );
    return max( 0, (int) $query->found_posts );
}

function wp_theme_blog_categories() {
    $args = array(
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    );
    $lang = wp_theme_blog_current_language();
    if ( $lang ) $args['lang'] = $lang;
    $terms = get_categories( $args );
    if ( ! is_array( $terms ) ) return array();

    $filtered = array_values( array_filter( $terms, static function( $term ) {
        if ( ! $term instanceof WP_Term ) return false;
        return ! wp_theme_blog_is_uncategorized_term( $term );
    } ) );

    foreach ( $filtered as $index => $term ) {
        $filtered[ $index ]->localized_count = wp_theme_blog_category_count( $term );
    }

    return array_values( array_filter( $filtered, static function( $term ) {
        return ! isset( $term->localized_count ) || $term->localized_count > 0;
    } ) );
}

function wp_theme_blog_query_args( $search = '', $category = '', $page = 1, $per_page = 6 ) {
    $args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'posts_per_page'      => max( 1, min( 12, absint( $per_page ) ) ),
        'paged'               => max( 1, absint( $page ) ),
        'orderby'             => 'date',
        'order'               => 'DESC',
    );
    if ( $search !== '' ) {
        $args['s'] = sanitize_text_field( $search );
    }
    if ( $category !== '' ) {
        $args['category_name'] = sanitize_title( $category );
    }
    $lang = wp_theme_blog_current_language();
    if ( $lang ) {
        $args['lang'] = $lang;
        $args['suppress_filters'] = false;
    }
    return $args;
}

function wp_theme_blog_category_badges( $post_id, $limit = 2 ) {
    $terms = get_the_category( $post_id );
    if ( ! $terms ) return '';
    $items = array();
    foreach ( $terms as $term ) {
        if ( wp_theme_blog_is_uncategorized_term( $term ) ) continue;
        $url = add_query_arg( 'blog_category', $term->slug, wp_theme_blog_archive_url() );
        $items[] = '<a class="wp-theme-blog-badge" href="' . esc_url( $url ) . '">' . esc_html( wp_theme_blog_term_label( $term ) ) . '</a>';
        if ( count( $items ) >= max( 1, absint( $limit ) ) ) break;
    }
    return '<div class="wp-theme-blog-badges">' . implode( '', $items ) . '</div>';
}

function wp_theme_blog_card_html( $post_id, $featured = false ) {
    $post_id = absint( $post_id );
    if ( ! $post_id ) return '';
    $title = get_the_title( $post_id );
    $url = get_permalink( $post_id );
    $excerpt = get_the_excerpt( $post_id );
    if ( ! $excerpt ) {
        $excerpt = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 28, '…' );
    }
    $image = get_the_post_thumbnail( $post_id, $featured ? 'large' : 'medium_large', array(
        'class'    => 'wp-theme-blog-list-card__image',
        'loading'  => 'lazy',
        'decoding' => 'async',
    ) );
    if ( ! $image ) {
        $image = '<span class="wp-theme-blog-list-card__placeholder" aria-hidden="true"></span>';
    }

    $classes = 'wp-theme-blog-list-card motion-fade-up';
    if ( $featured ) $classes .= ' is-featured';
    $reading = sprintf( _n( '%d min read', '%d min read', wp_theme_blog_reading_minutes( $post_id ), 'wp-theme' ), wp_theme_blog_reading_minutes( $post_id ) );
    $author_id = (int) get_post_field( 'post_author', $post_id );
    $author = get_the_author_meta( 'display_name', $author_id );

    return '<article class="' . esc_attr( $classes ) . '">' .
        '<a class="wp-theme-blog-list-card__media" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( sprintf( __( 'Read %s', 'wp-theme' ), $title ) ) . '">' . $image . '</a>' .
        '<div class="wp-theme-blog-list-card__content">' .
            wp_theme_blog_category_badges( $post_id ) .
            '<div class="wp-theme-blog-list-card__meta"><span>' . esc_html( $author ) . '</span><span aria-hidden="true">·</span><time datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( get_the_date( get_option( 'date_format' ), $post_id ) ) . '</time><span aria-hidden="true">·</span><span>' . esc_html( $reading ) . '</span></div>' .
            '<h2 class="wp-theme-blog-list-card__title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h2>' .
            '<p class="wp-theme-blog-list-card__excerpt">' . esc_html( wp_trim_words( $excerpt, $featured ? 34 : 22, '…' ) ) . '</p>' .
            '<a class="wp-theme-blog-list-card__link" href="' . esc_url( $url ) . '">' . esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'read_article', 'Read article' ) : __( 'Read article', 'wp-theme' ) ) . '<span aria-hidden="true"> →</span></a>' .
        '</div>' .
    '</article>';
}

function wp_theme_blog_cards_html( WP_Query $query, $page = 1 ) {
    if ( ! $query->have_posts() ) return '';
    $html = '';
    $index = 0;
    while ( $query->have_posts() ) {
        $query->the_post();
        $featured = 1 === absint( $page ) && 0 === $index;
        $html .= wp_theme_blog_card_html( get_the_ID(), $featured );
        $index++;
    }
    wp_reset_postdata();
    return $html;
}

function wp_theme_blog_count_label( $total ) {
    $total = absint( $total );
    if ( function_exists( 'wp_theme_ui_string' ) ) {
        $noun = 1 === $total ? wp_theme_ui_string( 'article', 'article' ) : wp_theme_ui_string( 'articles', 'articles' );
        return sprintf( '%d %s', $total, $noun );
    }
    return sprintf( _n( '%d article', '%d articles', $total, 'wp-theme' ), $total );
}

function wp_theme_blog_archive_shortcode() {
    $initial_category = '';
    if ( is_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) $initial_category = $term->slug;
    } elseif ( isset( $_GET['blog_category'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $initial_category = sanitize_title( wp_unslash( $_GET['blog_category'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }
    $initial_search = isset( $_GET['blog_search'] ) ? sanitize_text_field( wp_unslash( $_GET['blog_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $per_page = 6;
    $query = new WP_Query( wp_theme_blog_query_args( $initial_search, $initial_category, 1, $per_page ) );
    $categories = wp_theme_blog_categories();
    $profile = wp_theme_blog_profile();
    $eyebrow = $profile['blog_eyebrow'] ?? __( 'Journal', 'wp-theme' );
    $cards = wp_theme_blog_cards_html( $query, 1 );
    $has_more = $query->max_num_pages > 1;

    ob_start();
    ?>
    <main id="wp-theme-main" class="wp-theme-blog-experience" data-blog-app data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_theme_blog_filter' ) ); ?>" data-per-page="<?php echo esc_attr( $per_page ); ?>">
        <section class="wp-theme-blog-hero">
            <div class="container">
                <p class="wp-theme-sector-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
                <h1><?php echo esc_html( wp_theme_blog_archive_title() ); ?></h1>
                <p class="wp-theme-sector-lead"><?php echo esc_html( wp_theme_blog_archive_intro() ); ?></p>
            </div>
        </section>
        <section class="wp-theme-blog-browser">
            <div class="container">
                <div class="wp-theme-blog-toolbar">
                    <form class="wp-theme-blog-search" data-blog-search-form role="search">
                        <label class="screen-reader-text" for="wp-theme-blog-search-input"><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search_articles', 'Search articles' ) : 'Search articles' ); ?></label>
                        <div class="wp-theme-blog-search__field">
                            <input id="wp-theme-blog-search-input" type="search" name="blog_search" value="<?php echo esc_attr( $initial_search ); ?>" placeholder="<?php echo esc_attr( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search_articles', 'Search articles' ) : 'Search articles' ); ?>" autocomplete="off">
                            <button type="submit" class="wp-theme-blog-search__submit" aria-label="<?php echo esc_attr( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search', 'Search' ) : 'Search' ); ?>"><span class="wp-theme-blog-search__icon" aria-hidden="true"><?php echo wp_theme_icon_svg( 'search' ); ?></span><span class="screen-reader-text"><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search', 'Search' ) : 'Search' ); ?></span></button>
                        </div>
                    </form>
                    <div class="wp-theme-blog-toolbar__meta">
                        <button type="button" class="wp-theme-blog-reset" data-blog-reset<?php echo ( $initial_search || $initial_category ) ? '' : ' hidden'; ?>><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'reset', 'Reset' ) : 'Reset' ); ?></button>
                        <div class="wp-theme-blog-result-count" data-blog-count aria-live="polite"><?php echo esc_html( wp_theme_blog_count_label( $query->found_posts ) ); ?></div>
                    </div>
                </div>
                <div class="wp-theme-blog-filters" data-blog-filters aria-label="<?php echo esc_attr( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'filter_articles', 'Filter articles by category' ) : 'Filter articles by category' ); ?>">
                    <button type="button" class="wp-theme-blog-filter<?php echo $initial_category === '' ? ' is-active' : ''; ?>" data-blog-category="" aria-pressed="<?php echo $initial_category === '' ? 'true' : 'false'; ?>"><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'all', 'All' ) : 'All' ); ?></button>
                    <?php foreach ( $categories as $category ) : ?>
                        <button type="button" class="wp-theme-blog-filter<?php echo $initial_category === $category->slug ? ' is-active' : ''; ?>" data-blog-category="<?php echo esc_attr( $category->slug ); ?>" aria-pressed="<?php echo $initial_category === $category->slug ? 'true' : 'false'; ?>">
                            <span><?php echo esc_html( wp_theme_blog_term_label( $category ) ); ?></span><span class="wp-theme-blog-filter__count"><?php echo absint( $category->localized_count ?? $category->count ); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="wp-theme-blog-results" data-blog-results aria-live="polite" aria-busy="false">
                    <div class="wp-theme-blog-list" data-blog-list><?php echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    <div class="wp-theme-blog-empty" data-blog-empty<?php echo $cards ? ' hidden' : ''; ?>>
                        <h2><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'no_articles', 'No articles found.' ) : 'No articles found.' ); ?></h2>
                        <p><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'try_another_search', 'Try another search term or clear the category filter.' ) : 'Try another search term or clear the category filter.' ); ?></p>
                    </div>
                    <div class="wp-theme-blog-load-more-wrap" data-blog-load-wrap<?php echo $has_more ? '' : ' hidden'; ?>>
                        <button type="button" class="wp-theme-blog-load-more" data-blog-load-more data-page="2"><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'load_more', 'Load more' ) : __( 'Load more', 'wp-theme' ) ); ?></button>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wp_theme_blog_archive', 'wp_theme_blog_archive_shortcode' );

function wp_theme_blog_filter_ajax() {
    check_ajax_referer( 'wp_theme_blog_filter', 'nonce' );
    $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
    $category = isset( $_POST['category'] ) ? sanitize_title( wp_unslash( $_POST['category'] ) ) : '';
    $page = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
    $per_page = isset( $_POST['per_page'] ) ? max( 1, min( 12, absint( $_POST['per_page'] ) ) ) : 6;

    $query = new WP_Query( wp_theme_blog_query_args( $search, $category, $page, $per_page ) );
    wp_send_json_success( array(
        'html'        => wp_theme_blog_cards_html( $query, $page ),
        'total'       => (int) $query->found_posts,
        'countLabel'  => wp_theme_blog_count_label( $query->found_posts ),
        'page'        => $page,
        'nextPage'    => $page + 1,
        'hasMore'     => $page < (int) $query->max_num_pages,
        'maxPages'    => (int) $query->max_num_pages,
    ) );
}
add_action( 'wp_ajax_wp_theme_blog_filter', 'wp_theme_blog_filter_ajax' );
add_action( 'wp_ajax_nopriv_wp_theme_blog_filter', 'wp_theme_blog_filter_ajax' );

function wp_theme_blog_related_posts( $post_id, $limit = 3 ) {
    $post_id = absint( $post_id );
    $limit = max( 1, min( 4, absint( $limit ) ) );
    $category_ids = wp_get_post_categories( $post_id );
    $ids = array();

    if ( $category_ids ) {
        $ids = get_posts( array(
            'fields'               => 'ids',
            'post_type'            => 'post',
            'post_status'          => 'publish',
            'posts_per_page'       => $limit,
            'post__not_in'         => array( $post_id ),
            'category__in'         => $category_ids,
            'ignore_sticky_posts'  => true,
            'orderby'              => 'date',
            'order'                => 'DESC',
            'suppress_filters'     => false,
        ) );
    }

    // Fill the row with recent articles if the current category only has one
    // or two siblings. This avoids a visually orphaned single card.
    if ( count( $ids ) < $limit ) {
        $fallback = get_posts( array(
            'fields'               => 'ids',
            'post_type'            => 'post',
            'post_status'          => 'publish',
            'posts_per_page'       => $limit - count( $ids ),
            'post__not_in'         => array_merge( array( $post_id ), $ids ),
            'ignore_sticky_posts'  => true,
            'orderby'              => 'date',
            'order'                => 'DESC',
            'suppress_filters'     => false,
        ) );
        $ids = array_values( array_unique( array_merge( $ids, $fallback ) ) );
    }

    return new WP_Query( array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'post__in'            => $ids ?: array( 0 ),
        'orderby'             => 'post__in',
        'ignore_sticky_posts' => true,
    ) );
}

function wp_theme_blog_share_markup( $post_id ) {
    $url = rawurlencode( get_permalink( $post_id ) );
    $title = rawurlencode( get_the_title( $post_id ) );
    return '<div class="wp-theme-article-share" aria-label="' . esc_attr__( 'Share this article', 'wp-theme' ) . '">' .
        '<span class="wp-theme-article-share__label">' . esc_html__( 'Share', 'wp-theme' ) . '</span>' .
        '<a href="https://www.linkedin.com/sharing/share-offsite/?url=' . esc_attr( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Share on LinkedIn', 'wp-theme' ) . '">in</a>' .
        '<a href="https://www.facebook.com/sharer/sharer.php?u=' . esc_attr( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Share on Facebook', 'wp-theme' ) . '">f</a>' .
        '<a href="https://twitter.com/intent/tweet?url=' . esc_attr( $url ) . '&text=' . esc_attr( $title ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Share on X', 'wp-theme' ) . '">x</a>' .
        '<button type="button" data-copy-article-link data-url="' . esc_url( get_permalink( $post_id ) ) . '" aria-label="' . esc_attr__( 'Copy link', 'wp-theme' ) . '" title="' . esc_attr__( 'Copy link', 'wp-theme' ) . '"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.1.1l2-2A5 5 0 0 0 12 4l-1.1 1.1M14 11a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 20l1.1-1.1" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span class="screen-reader-text">' . esc_html__( 'Copy link', 'wp-theme' ) . '</span></button>' .
    '</div>';
}

/**
 * Prevent the same starter image from rendering twice on single posts.
 * Demo articles historically used the same source image both as the featured
 * image and as the first inline image. The featured attachment is copied into
 * uploads, so compare file basenames as well as full URLs.
 */
function wp_theme_blog_strip_duplicate_featured_image( $raw_content, $post_id ) {
    $post_id = absint( $post_id );
    if ( ! $post_id || ! $raw_content ) return $raw_content;

    $featured_id = get_post_thumbnail_id( $post_id );
    if ( ! $featured_id ) return $raw_content;

    $signatures = array();
    $featured_url = wp_get_attachment_url( $featured_id );
    if ( $featured_url ) {
        $signatures[] = untrailingslashit( strtok( $featured_url, '?' ) );
        $path = wp_parse_url( $featured_url, PHP_URL_PATH );
        if ( $path ) $signatures[] = basename( rawurldecode( $path ) );
    }
    $attached_file = (string) get_post_meta( $featured_id, '_wp_attached_file', true );
    if ( $attached_file ) $signatures[] = basename( $attached_file );
    $signatures = array_values( array_unique( array_filter( $signatures ) ) );
    if ( ! $signatures ) return $raw_content;

    $removed = false;
    $pattern = "~<!--\\s+wp:image\\b[^>]*-->.*?<img\\b[^>]*\\bsrc=[\'\"]([^\'\"]+)[\'\"][^>]*>.*?<!--\\s+/wp:image\\s+-->~is";
    $content = preg_replace_callback( $pattern, static function( $match ) use ( &$removed, $signatures ) {
        if ( $removed ) return $match[0];
        $src = html_entity_decode( (string) $match[1], ENT_QUOTES, 'UTF-8' );
        $src_clean = untrailingslashit( strtok( $src, '?' ) );
        $src_path = wp_parse_url( $src, PHP_URL_PATH );
        $src_base = $src_path ? basename( rawurldecode( $src_path ) ) : basename( $src_clean );
        foreach ( $signatures as $signature ) {
            if ( $src_clean === $signature || $src_base === $signature ) {
                $removed = true;
                return '';
            }
        }
        return $match[0];
    }, (string) $raw_content, 1 );

    return is_string( $content ) ? $content : $raw_content;
}

function wp_theme_blog_single_shortcode() {
    if ( ! is_singular( 'post' ) ) return '';
    $post_id = get_queried_object_id();
    if ( ! $post_id ) return '';

    $title = get_the_title( $post_id );
    $author_id = (int) get_post_field( 'post_author', $post_id );
    $author = get_the_author_meta( 'display_name', $author_id );
    $reading = wp_theme_blog_reading_minutes( $post_id );
    $raw_content = (string) get_post_field( 'post_content', $post_id );
    $raw_content = wp_theme_blog_strip_duplicate_featured_image( $raw_content, $post_id );
    $content = apply_filters( 'the_content', $raw_content );
    $image = get_the_post_thumbnail( $post_id, 'full', array( 'class'=>'wp-theme-article-featured-image', 'loading'=>'eager', 'decoding'=>'async' ) );
    $related = wp_theme_blog_related_posts( $post_id, 3 );
    $blog_url = wp_theme_blog_archive_url();
    $profile = wp_theme_blog_profile();
    $author_bio = trim( (string) get_the_author_meta( 'description', $author_id ) );
    if ( '' === $author_bio ) $author_bio = __( 'Articles and practical guidance from the team behind this website.', 'wp-theme' );
    $author_avatar = get_avatar( $author_id, 128, '', $author );
    $previous = get_previous_post();
    $next = get_next_post();

    ob_start();
    ?>
    <main id="wp-theme-main" class="wp-theme-article-experience" data-blog-single>
        <div class="wp-theme-article-progress" data-blog-progress aria-hidden="true"></div>
        <article class="wp-theme-article">
            <header class="wp-theme-article-head">
                <div class="container wp-theme-article-head__inner">
                    <a class="wp-theme-article-back" href="<?php echo esc_url( $blog_url ); ?>"><span aria-hidden="true">←</span> <?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'back_to_journal', 'Back to journal' ) : __( 'Back to journal', 'wp-theme' ) ); ?></a>
                    <?php echo wp_theme_blog_category_badges( $post_id, 3 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <h1><?php echo esc_html( $title ); ?></h1>
                    <div class="wp-theme-article-meta">
                        <span><?php echo esc_html( $author ); ?></span><span aria-hidden="true">·</span>
                        <time datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>"><?php echo esc_html( get_the_date( get_option( 'date_format' ), $post_id ) ); ?></time><span aria-hidden="true">·</span>
                        <span><?php echo esc_html( sprintf( _n( '%d min read', '%d min read', $reading, 'wp-theme' ), $reading ) ); ?></span>
                    </div>
                    <?php echo wp_theme_blog_share_markup( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </header>
            <?php if ( $image ) : ?><div class="wp-theme-article-media"><div class="container"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div><?php endif; ?>
            <div class="wp-theme-article-body">
                <div class="container wp-theme-article-layout wp-theme-article-layout--full">
                    <div class="wp-theme-article-content-wrap">
                        <div class="wp-theme-article-content" data-blog-article-content><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                        <section class="wp-theme-article-author" aria-label="<?php esc_attr_e( 'About the author', 'wp-theme' ); ?>">
                            <div class="wp-theme-article-author__avatar"><?php echo wp_kses_post( $author_avatar ); ?></div>
                            <div><p class="wp-theme-sector-eyebrow"><?php esc_html_e( 'Written by', 'wp-theme' ); ?></p><h3><?php echo esc_html( $author ); ?></h3><p><?php echo esc_html( $author_bio ); ?></p></div>
                        </section>
                        <?php if ( $previous || $next ) : ?>
                            <nav class="wp-theme-article-navigation" aria-label="<?php esc_attr_e( 'Article navigation', 'wp-theme' ); ?>">
                                <div><?php if ( $previous ) : ?><a rel="prev" href="<?php echo esc_url( get_permalink( $previous ) ); ?>"><small><?php esc_html_e( 'Previous article', 'wp-theme' ); ?></small><strong><?php echo esc_html( get_the_title( $previous ) ); ?></strong></a><?php endif; ?></div>
                                <div><?php if ( $next ) : ?><a rel="next" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><small><?php esc_html_e( 'Next article', 'wp-theme' ); ?></small><strong><?php echo esc_html( get_the_title( $next ) ); ?></strong></a><?php endif; ?></div>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <section class="wp-theme-related-posts">
            <div class="container">
                <div class="wp-theme-related-posts__head">
                    <div><p class="wp-theme-sector-eyebrow"><?php esc_html_e( 'Keep reading', 'wp-theme' ); ?></p><h2><?php esc_html_e( 'More useful articles', 'wp-theme' ); ?></h2></div>
                    <a class="wp-theme-related-posts__all" href="<?php echo esc_url( $blog_url ); ?>"><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'view_all_articles', 'View all articles' ) : __( 'View all articles', 'wp-theme' ) ); ?> →</a>
                </div>
                <div class="wp-theme-related-posts__grid">
                    <?php
                    if ( $related->have_posts() ) {
                        while ( $related->have_posts() ) { $related->the_post(); echo wp_theme_blog_card_html( get_the_ID(), false ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        wp_reset_postdata();
                    }
                    ?>
                </div>
                <div class="wp-theme-article-next-step">
                    <div><p class="wp-theme-sector-eyebrow"><?php esc_html_e( 'Next step', 'wp-theme' ); ?></p><h2><?php echo esc_html( $profile['cta_title'] ?? __( 'Need help with the next decision?', 'wp-theme' ) ); ?></h2></div>
                    <a href="<?php echo esc_url( wp_theme_demo_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact the team', 'wp-theme' ); ?><span class="wp-theme-link-arrow" aria-hidden="true">→</span></a>
                </div>
            </div>
        </section>
    </main>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wp_theme_blog_single', 'wp_theme_blog_single_shortcode' );
