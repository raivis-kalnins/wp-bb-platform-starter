<?php
/**
 * Stable site shell for every WP BBTheme child.
 *
 * Header/footer are rendered outside editable template-part storage so a stale
 * Site Editor override can never hide the navigation after Starter Setup.
 * Child themes own all visual presentation; this file owns semantic markup and
 * optional integrations only.
 */
defined( 'ABSPATH' ) || exit;

function wp_theme_is_bbtheme_project() {
    $theme = wp_get_theme();
    return 'wp-bbtheme' === $theme->get_template();
}

/**
 * Only the maintained 3.8.x suite uses the parent-managed shell. Older Garilla
 * and bespoke children keep their own header/footer/template parts unchanged.
 */
function wp_theme_uses_managed_shell() {
    $stylesheet = wp_get_theme()->get_stylesheet();
    $managed = function_exists( 'wp_theme_is_current_suite_theme' ) && wp_theme_is_current_suite_theme();
    return (bool) apply_filters( 'wp_theme_use_managed_shell', $managed, $stylesheet );
}

function wp_theme_force_bbuilder_assets( $force ) {
    return wp_theme_uses_managed_shell() ? true : $force;
}
add_filter( 'wpbb_force_frontend_assets', 'wp_theme_force_bbuilder_assets', 20 );

/** Prevent block-template database overrides from duplicating/replacing shell. */
function wp_theme_suppress_shell_template_parts( $block_content, $block ) {
    if ( ! wp_theme_uses_managed_shell() || empty( $block['blockName'] ) || 'core/template-part' !== $block['blockName'] ) {
        return $block_content;
    }
    $slug = isset( $block['attrs']['slug'] ) ? sanitize_key( $block['attrs']['slug'] ) : '';
    if ( in_array( $slug, array( 'header', 'footer' ), true ) ) {
        return '';
    }
    return $block_content;
}
add_filter( 'render_block', 'wp_theme_suppress_shell_template_parts', 100, 2 );

function wp_theme_site_logo_markup() {
    $logo_id = absint( get_theme_mod( 'custom_logo' ) );
    if ( $logo_id ) {
        $image = wp_get_attachment_image( $logo_id, 'full', false, array(
            'class' => 'wp-theme-site-logo__image',
            'loading' => 'eager',
            'decoding' => 'async',
        ) );
        if ( $image ) {
            return $image;
        }
    }
    return '<span class="wp-theme-site-logo__mark" aria-hidden="true"></span><span class="wp-theme-site-logo__text">' . esc_html( get_bloginfo( 'name' ) ?: __( 'Website', 'wp-theme' ) ) . '</span>';
}

function wp_theme_header_account_url() {
    /* Prefer a real published WooCommerce My Account page. Some demo clones can
     * retain an old/deleted page ID in woocommerce_myaccount_page_id. */
    if ( function_exists( 'wc_get_page_id' ) ) {
        $page_id = (int) wc_get_page_id( 'myaccount' );
        if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
            $url = get_permalink( $page_id );
            if ( $url ) return $url;
        }
    }
    $page = get_page_by_path( 'my-account', OBJECT, 'page' );
    if ( $page && 'publish' === $page->post_status ) {
        $page_id = (int) $page->ID;
        if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language( 'slug' );
            $translated = $lang ? (int) pll_get_post( $page_id, $lang ) : 0;
            if ( $translated > 0 && 'publish' === get_post_status( $translated ) ) $page_id = $translated;
        }
        $url = get_permalink( $page_id );
        if ( $url ) return $url;
    }
    if ( function_exists( 'wc_get_page_permalink' ) ) {
        $url = wc_get_page_permalink( 'myaccount' );
        if ( $url && $url !== home_url( '/' ) ) return $url;
    }
    return wp_login_url();
}

function wp_theme_header_cart_markup() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) return '';
    $count = (int) WC()->cart->get_cart_contents_count();
    $url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#';
    return '<a class="wp-theme-header-action wp-theme-header-cart wptws-mini-cart-trigger" data-wptws-mini-cart="1" aria-controls="wptws-mini-cart" aria-haspopup="dialog" href="' . esc_url( $url ) . '" aria-label="' . esc_attr__( 'Open shopping cart', 'wp-theme' ) . '"><span class="wp-theme-action-icon" aria-hidden="true">' . wp_theme_icon_svg( 'bag' ) . '</span><span class="wp-theme-action-label">' . esc_html__( 'Cart', 'wp-theme' ) . '</span><span class="wp-theme-cart-count">' . absint( $count ) . '</span></a>';
}

function wp_theme_icon_svg( $name ) {
    $icons = array(
        'search' => '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 20c.7-4 3-6 6.5-6s5.8 2 6.5 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'bag' => '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M5 8h14l-1 12H6L5 8Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 9V6a3 3 0 0 1 6 0v3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>',
        'sun' => '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'moon' => '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M20 15.7A8 8 0 0 1 8.3 4a8.2 8.2 0 1 0 11.7 11.7Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        'menu' => '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'close' => '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'arrow-up' => '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m6 14 6-6 6 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 9v10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    );
    return $icons[ $name ] ?? '';
}

function wp_theme_render_site_header() {
    if ( is_admin() || ! wp_theme_uses_managed_shell() || did_action( 'wp_theme_site_header_rendered' ) ) return;
    do_action( 'wp_theme_site_header_rendered' );

    $sticky = wp_theme_truthy( wp_theme_nav_menu_setting( 'sticky_header', true ) );
    $search = wp_theme_truthy( wp_theme_nav_menu_setting( 'search_bar', true ) );
    $account = wp_theme_truthy( wp_theme_nav_menu_setting( 'customer_account', false ) );
    $cart = wp_theme_truthy( wp_theme_nav_menu_setting( 'mini_cart', false ) );
    $dark = wp_theme_truthy( wp_theme_nav_menu_setting( 'light_dark', true ) );
    $lang = function_exists( 'wp_theme_language_switcher_enabled' ) ? wp_theme_language_switcher_enabled() : wp_theme_truthy( wp_theme_nav_menu_setting( 'language_bar', true ) );
    // v3.8.10: use one clear flag dropdown in the main header instead of duplicating
    // a long language-code strip in the utility bar.
    $has_language_bar = false;
    $has_top = has_nav_menu( 'wp-header-top-menu' );
    ?>
    <header class="wp-theme-site-header<?php echo $sticky ? ' is-sticky-enabled' : ''; ?>" data-site-header>
        <?php if ( $has_top ) : ?>
            <div class="wp-theme-utility-bar">
                <div class="container">
                    <div class="wp-theme-utility-bar__inner">
                        <span class="wp-theme-utility-note"><?php echo esc_html( apply_filters( 'wp_theme_utility_note', function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'built_with_stack', 'Built with WordPress, Gutenberg and Bootstrap.' ) : __( 'Built with WordPress, Gutenberg and Bootstrap.', 'wp-theme' ) ) ); ?></span>
                        <div class="wp-theme-utility-actions">
                            <?php if ( has_nav_menu( 'wp-header-top-menu' ) ) : ?><nav class="wp-theme-top-navigation" aria-label="<?php esc_attr_e( 'Utility navigation', 'wp-theme' ); ?>"><?php echo wp_theme_demo_menu( 'wp-header-top-menu', 'wp-theme-top-menu' ); ?></nav><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="wp-theme-header-main">
            <div class="container">
                <div class="wp-theme-header-main__inner">
                    <a class="wp-theme-site-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo wp_kses_post( wp_theme_site_logo_markup() ); ?></a>
                    <nav id="wp-theme-mobile-navigation" class="wp-theme-primary-navigation header-nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'wp-theme' ); ?>" data-primary-navigation>
                        <?php echo wp_theme_demo_menu( 'wp-header-menu', 'wp-theme-primary-menu navbar-nav' ); ?>
                    </nav>
                    <div class="wp-theme-header-actions">
                        <?php if ( $search ) : ?>
                            <button class="wp-theme-header-action wp-theme-search-toggle" type="button" aria-expanded="false" aria-controls="wp-theme-header-search" aria-label="<?php echo esc_attr( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search', 'Search' ) : 'Search' ); ?>"><span class="wp-theme-action-icon"><?php echo wp_theme_icon_svg( 'search' ); ?></span><span class="screen-reader-text"><?php echo esc_html( function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search', 'Search' ) : 'Search' ); ?></span></button>
                        <?php endif; ?>
                        <?php if ( $account ) : ?><a class="wp-theme-header-action wp-theme-account-link" href="<?php echo esc_url( wp_theme_header_account_url() ); ?>"><span class="wp-theme-action-icon"><?php echo wp_theme_icon_svg( 'user' ); ?></span><span class="wp-theme-action-label"><?php esc_html_e( 'Account', 'wp-theme' ); ?></span></a><?php endif; ?>
                        <?php if ( $cart ) echo wp_theme_header_cart_markup(); ?>
                        <?php if ( $lang && function_exists( 'wp_theme_render_language_switcher' ) ) echo wp_theme_render_language_switcher( array( 'variant' => 'header' ) ); ?>
                        <?php if ( $dark ) : ?>
                            <?php $dark_label = function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'switch_dark', 'Switch to dark mode' ) : 'Switch to dark mode'; $light_label = function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'switch_light', 'Switch to light mode' ) : 'Switch to light mode'; ?>
                            <button class="wp-theme-header-action wp-theme-theme-toggle" type="button" aria-label="<?php echo esc_attr( $dark_label ); ?>" title="<?php echo esc_attr( $dark_label ); ?>" data-label-dark="<?php echo esc_attr( $dark_label ); ?>" data-label-light="<?php echo esc_attr( $light_label ); ?>"><span class="wp-theme-theme-toggle__sun" hidden><?php echo wp_theme_icon_svg( 'sun' ); ?></span><span class="wp-theme-theme-toggle__moon"><?php echo wp_theme_icon_svg( 'moon' ); ?></span></button>
                        <?php endif; ?>
                        <?php $open_menu_label = function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'open_menu', 'Open menu' ) : 'Open menu'; $close_menu_label = function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'close_menu', 'Close menu' ) : 'Close menu'; ?>
                        <button class="wp-theme-menu-toggle navbar-toggler-btn" type="button" aria-expanded="false" aria-controls="wp-theme-mobile-navigation" aria-label="<?php echo esc_attr( $open_menu_label ); ?>" data-label-open="<?php echo esc_attr( $open_menu_label ); ?>" data-label-close="<?php echo esc_attr( $close_menu_label ); ?>"><span class="wp-theme-menu-toggle__open"><?php echo wp_theme_icon_svg( 'menu' ); ?></span><span class="wp-theme-menu-toggle__close" hidden><?php echo wp_theme_icon_svg( 'close' ); ?></span><span class="screen-reader-text"><?php esc_html_e( 'Menu', 'wp-theme' ); ?></span></button>
                    </div>
                </div>
            </div>
        </div>
        <?php if ( $search ) : ?>
            <div id="wp-theme-header-search" class="wp-theme-header-search-panel" hidden>
                <div class="container">
                    <div class="wp-theme-header-search-panel__inner">
                        <?php $search_label = function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search', 'Search' ) : 'Search'; $search_placeholder = function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'search_site', 'Search the site…' ) : 'Search the site…'; $close_search_label = function_exists( 'wp_theme_ui_string' ) ? wp_theme_ui_string( 'close_search', 'Close search' ) : 'Close search'; ?>
                        <p class="wp-theme-header-search-label"><?php echo esc_html( $search_label ); ?></p>
                        <?php echo function_exists( 'wp_theme_ajax_search_markup' ) ? wp_theme_ajax_search_markup( array( 'placeholder' => $search_placeholder, 'post_types' => wp_theme_header_search_post_types(), 'limit' => 8 ) ) : ''; ?>
                        <button class="wp-theme-search-close" type="button" aria-label="<?php echo esc_attr( $close_search_label ); ?>"><?php echo wp_theme_icon_svg( 'close' ); ?><span class="screen-reader-text"><?php echo esc_html( $close_search_label ); ?></span></button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="wp-theme-header-overlay" data-header-overlay hidden></div>
    </header>
    <?php
}
add_action( 'wp_body_open', 'wp_theme_render_site_header', 8 );

function wp_theme_render_site_footer() {
    if ( is_admin() || ! wp_theme_uses_managed_shell() || did_action( 'wp_theme_site_footer_rendered' ) ) return;
    do_action( 'wp_theme_site_footer_rendered' );
    $profile = function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array();
    $footer_text = ! empty( $profile['footer_text'] ) ? $profile['footer_text'] : get_bloginfo( 'description' );
    ?>
    <footer class="wp-theme-site-footer" data-site-footer>
        <div class="wp-theme-footer-newsletter-band">
            <div class="container"><?php echo function_exists( 'wp_theme_newsletter_markup' ) ? wp_theme_newsletter_markup() : ''; ?></div>
        </div>
        <div class="wp-theme-footer-main">
            <div class="container">
                <div class="row g-4 g-lg-5">
                    <div class="col-12 col-lg-4">
                        <a class="wp-theme-footer-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo wp_kses_post( wp_theme_site_logo_markup() ); ?></a>
                        <?php if ( $footer_text ) : ?><p class="wp-theme-footer-intro"><?php echo esc_html( $footer_text ); ?></p><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-7 col-lg-5">
                        <p class="wp-theme-footer-heading"><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Explore') : 'Explore' ); ?></p>
                        <nav class="wp-theme-footer-navigation" aria-label="<?php esc_attr_e( 'Footer navigation', 'wp-theme' ); ?>"><?php echo wp_theme_demo_menu( 'wp-footer-menu', 'wp-theme-footer-menu' ); ?></nav>
                    </div>
                    <div class="col-12 col-md-5 col-lg-3">
                        <p class="wp-theme-footer-heading"><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Contact') : 'Contact' ); ?></p>
                        <div class="wp-theme-footer-contact">
                            <a href="<?php echo esc_url( wp_theme_demo_page_url( 'contact' ) ); ?>"><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Contact the team') : 'Contact the team' ); ?></a>
                            <?php if ( function_exists( 'wp_theme_polylang_active' ) && wp_theme_polylang_active() ) echo wp_theme_render_language_switcher( array( 'variant' => 'footer' ) ); ?>
                        </div>
                        <?php if ( function_exists( 'wp_theme_essential_page_url' ) ) : ?>
                            <div class="wp-theme-footer-utility" aria-label="<?php esc_attr_e( 'Website information', 'wp-theme' ); ?>">
                                <a href="<?php echo esc_url( wp_theme_essential_page_url( 'site-map' ) ); ?>"><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Site Map') : 'Site Map' ); ?></a>
                                <a href="<?php echo esc_url( wp_theme_essential_page_url( 'privacy-policy' ) ); ?>"><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Privacy Policy') : 'Privacy Policy' ); ?></a>
                                <a href="<?php echo esc_url( wp_theme_essential_page_url( 'terms-and-conditions' ) ); ?>"><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Terms & Conditions') : 'Terms & Conditions' ); ?></a>
                                <button type="button" data-cookie-settings><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Manage cookies') : 'Manage cookies' ); ?></button>
                            </div>
                            <div class="wp-theme-footer-install" aria-label="<?php esc_attr_e( 'Install website app', 'wp-theme' ); ?>">
                                <a data-install-link="apple" href="<?php echo esc_url( add_query_arg( 'install', 'apple', home_url( '/' ) ) ); ?>"><img src="<?php echo esc_url( wp_theme_pwa_icon_url( 32 ) ); ?>" alt="" width="24" height="24"><span><small><?php esc_html_e( 'Add to home screen', 'wp-theme' ); ?></small><?php esc_html_e( 'Apple', 'wp-theme' ); ?></span></a>
                                <a data-install-link="android" href="<?php echo esc_url( add_query_arg( 'install', 'android', home_url( '/' ) ) ); ?>"><img src="<?php echo esc_url( wp_theme_pwa_icon_url( 32 ) ); ?>" alt="" width="24" height="24"><span><small><?php esc_html_e( 'Install website app', 'wp-theme' ); ?></small><?php esc_html_e( 'Android', 'wp-theme' ); ?></span></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="wp-theme-footer-bottom"><div class="container"><div class="wp-theme-footer-bottom__inner"><span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span><span><?php echo esc_html( function_exists('wp_theme_runtime_translate') ? wp_theme_runtime_translate('Powered by WordPress + Gutenberg') : 'Powered by WordPress + Gutenberg' ); ?></span></div></div></div>
    </footer>
    <button class="wp-theme-scroll-top" type="button" data-scroll-top aria-label="<?php esc_attr_e( 'Back to top', 'wp-theme' ); ?>" hidden><?php echo wp_theme_icon_svg( 'arrow-up' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Back to top', 'wp-theme' ); ?></span></button>
    <?php
}
add_action( 'wp_footer', 'wp_theme_render_site_footer', 5 );

function wp_theme_enqueue_site_shell_script() {
    if ( ! wp_theme_uses_managed_shell() ) return;
    $file = get_template_directory() . '/assets/js/site-shell.js';
    wp_enqueue_script( 'wp-theme-site-shell', get_template_directory_uri() . '/assets/js/site-shell.js', array(), file_exists( $file ) ? (string) filemtime( $file ) : null, true );
}
add_action( 'wp_enqueue_scripts', 'wp_theme_enqueue_site_shell_script', 40 );
