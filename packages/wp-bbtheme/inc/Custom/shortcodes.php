<?php
if (!defined('ABSPATH')) exit;

function shortcode_needLogin() {
    if (is_user_logged_in()) {
        return '';
    }
    if (class_exists('WooCommerce')) {
        return '<div class="container login-form" style="margin-bottom:70px;">' . do_shortcode('[woocommerce_my_account]') . '</div>';
    }
    return '<div class="container login-form" style="margin-bottom:70px;">' . esc_html__('Please log in to continue.', 'wp-theme') . '</div>';
}
add_shortcode('need_login', 'shortcode_needLogin');

$component_dir = get_template_directory() . '/templates/components/shortcodes/';
$includes = [
    'custom-hero.php'         => true,
    'avatar_cpt.php'          => true,
];
foreach ($includes as $file => $enabled) {
    $path = $component_dir . $file;
    if ($enabled && file_exists($path)) {
        include_once $path;
    }
}



/**
 * Search/404 frontend shells. Markup lives in the parent as functionality;
 * maintained child themes own all visual presentation.
 */
function wp_theme_public_ui_text( $text ) {
    return function_exists( 'wp_theme_runtime_translate' ) ? wp_theme_runtime_translate( $text ) : $text;
}

function wp_theme_public_search_form( $class_name = '' ) {
    $label = wp_theme_public_ui_text( 'Search' );
    $placeholder = wp_theme_public_ui_text( 'Search the site…' );
    return '<form role="search" method="get" class="wp-theme-public-search-form ' . esc_attr( $class_name ) . '" action="' . esc_url( home_url( '/' ) ) . '">' .
        '<label class="screen-reader-text" for="wp-theme-public-search-input">' . esc_html( $label ) . '</label>' .
        '<div class="wp-theme-public-search-form__field"><input id="wp-theme-public-search-input" type="search" name="s" value="' . esc_attr( get_search_query() ) . '" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off">' .
        '<button type="submit" aria-label="' . esc_attr( $label ) . '"><svg viewBox="0 0 24 24" width="19" height="19" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span class="screen-reader-text">' . esc_html( $label ) . '</span></button></div></form>';
}

function wp_theme_search_page_shortcode() {
    if ( ! is_search() ) return '';
    global $wp_query;
    $term = get_search_query();
    $count = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
    $title = wp_theme_public_ui_text( 'Search results' );
    $lead = $term ? sprintf( wp_theme_public_ui_text( 'Results for “%s”.' ), $term ) : wp_theme_public_ui_text( 'Find pages, articles and products across the site.' );

    ob_start();
    ?>
    <main id="wp-theme-main" class="wp-theme-search-page">
        <section class="wp-theme-search-page__hero">
            <div class="container">
                <p class="wp-theme-sector-eyebrow"><?php echo esc_html( wp_theme_public_ui_text( 'Search' ) ); ?></p>
                <h1><?php echo esc_html( $title ); ?></h1>
                <p class="wp-theme-search-page__lead"><?php echo esc_html( $lead ); ?></p>
                <?php echo wp_theme_public_search_form( 'wp-theme-search-page__form' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>
        <section class="wp-theme-search-page__body">
            <div class="container">
                <div class="wp-theme-search-page__summary"><strong><?php echo esc_html( sprintf( _n( '%d result', '%d results', $count, 'wp-theme' ), $count ) ); ?></strong></div>
                <?php if ( have_posts() ) : ?>
                    <div class="wp-theme-search-results-grid">
                        <?php while ( have_posts() ) : the_post(); ?>
                            <article <?php post_class( 'wp-theme-search-result-card' ); ?>>
                                <?php if ( has_post_thumbnail() ) : ?><a class="wp-theme-search-result-card__media" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?></a><?php endif; ?>
                                <div class="wp-theme-search-result-card__body">
                                    <div class="wp-theme-search-result-card__meta"><span><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ?? ucfirst( get_post_type() ) ); ?></span><?php if ( 'post' === get_post_type() ) : ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time><?php endif; ?></div>
                                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                    <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '…' ) ); ?></p>
                                    <a class="wp-theme-search-result-card__link" href="<?php the_permalink(); ?>"><?php echo esc_html( wp_theme_public_ui_text( 'View result' ) ); ?> <span aria-hidden="true">→</span></a>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    <nav class="wp-theme-search-pagination" aria-label="<?php echo esc_attr( wp_theme_public_ui_text( 'Search results pages' ) ); ?>"><?php echo wp_kses_post( paginate_links( array( 'type' => 'list', 'prev_text' => '←', 'next_text' => '→' ) ) ); ?></nav>
                <?php else : ?>
                    <div class="wp-theme-search-empty"><h2><?php echo esc_html( wp_theme_public_ui_text( 'No matching results' ) ); ?></h2><p><?php echo esc_html( wp_theme_public_ui_text( 'Try a shorter phrase or browse the main navigation.' ) ); ?></p><a class="wp-theme-search-empty__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( wp_theme_public_ui_text( 'Back home' ) ); ?></a></div>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wp_theme_search_page', 'wp_theme_search_page_shortcode' );

function wp_theme_404_page_shortcode() {
    if ( ! is_404() ) return '';
    ob_start();
    ?>
    <main id="wp-theme-main" class="wp-theme-error-page">
        <section class="wp-theme-error-page__hero">
            <div class="container">
                <span class="wp-theme-error-page__code">404</span>
                <p class="wp-theme-sector-eyebrow"><?php echo esc_html( wp_theme_public_ui_text( 'Page not found' ) ); ?></p>
                <h1><?php echo esc_html( wp_theme_public_ui_text( 'That page is no longer here.' ) ); ?></h1>
                <p class="wp-theme-error-page__lead"><?php echo esc_html( wp_theme_public_ui_text( 'Use the search below or return to the homepage to keep exploring.' ) ); ?></p>
                <div class="wp-theme-error-page__actions"><a class="wp-theme-error-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( wp_theme_public_ui_text( 'Back home' ) ); ?></a></div>
                <?php echo wp_theme_public_search_form( 'wp-theme-error-page__search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>
    </main>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wp_theme_404_page', 'wp_theme_404_page_shortcode' );
