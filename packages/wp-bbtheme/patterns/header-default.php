<?php
/**
 * Title: Header Default
 * Slug: header-default
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: true
 */
$site_name = get_bloginfo( 'name' ) ?: __( 'WP BBTheme', 'wp-theme' );
$commerce = function_exists( 'wp_theme_demo_commerce_enabled' ) && wp_theme_demo_commerce_enabled();
$menu_option = static function( $name, $default = false ) {
    if ( ! function_exists( 'wp_theme_nav_menu_setting' ) ) return (bool) $default;
    $value = wp_theme_nav_menu_setting( $name, $default );
    return function_exists( 'wp_theme_truthy' ) ? wp_theme_truthy( $value ) : (bool) $value;
};
$show_search = $menu_option( 'search_bar', true );
$show_account = $menu_option( 'customer_account', $commerce );
$show_cart = $menu_option( 'mini_cart', $commerce );
$show_mode = $menu_option( 'light_dark', true );
$show_lang = $menu_option( 'language_bar', true ) && function_exists( 'wp_theme_polylang_active' ) && wp_theme_polylang_active();
$sticky = $menu_option( 'sticky_header', true );
$account_url = $commerce && function_exists( 'wp_theme_header_account_url' ) ? wp_theme_header_account_url() : wp_login_url();
$cart_url = $commerce && function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
$cart_count = $commerce && function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>
<!-- wp:html -->
<header class="wp-theme-site-header<?php echo $sticky ? ' is-sticky-enabled' : ''; ?>" data-site-header>
<?php if ( has_nav_menu( 'wp-header-top-menu' ) ) : ?>
  <div class="wp-theme-utility-bar"><div class="container"><nav aria-label="<?php esc_attr_e( 'Utility menu', 'wp-theme' ); ?>"><?php echo wp_theme_demo_menu( 'wp-header-top-menu', 'wp-theme-top-menu' ); ?></nav></div></div>
<?php endif; ?>
  <div class="wp-theme-header-main">
    <div class="container">
      <div class="row align-items-center g-0">
        <div class="col-auto wp-theme-header-brand">
          <a class="wp-theme-site-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $site_name ); ?>"><span class="wp-theme-site-logo"><?php echo wp_theme_demo_logo( 'dark' ); ?></span></a>
        </div>
        <div class="col wp-theme-header-nav-col">
          <nav id="wp-theme-primary-navigation" class="wp-theme-primary-navigation" aria-label="<?php esc_attr_e( 'Primary navigation', 'wp-theme' ); ?>"><?php echo wp_theme_demo_menu( 'wp-header-menu', 'wp-theme-primary-menu' ); ?></nav>
        </div>
        <div class="col-auto">
          <div class="wp-theme-header-actions">
            <?php if ( $show_search ) : ?><button class="wp-theme-header-action" type="button" data-search-toggle aria-controls="wp-theme-header-search" aria-expanded="false" aria-label="<?php esc_attr_e( 'Open search', 'wp-theme' ); ?>"><span aria-hidden="true">⌕</span></button><?php endif; ?>
            <?php if ( $show_account ) : ?><a class="wp-theme-header-action" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'Account', 'wp-theme' ); ?>"><span aria-hidden="true">○</span></a><?php endif; ?>
            <?php if ( $show_cart && $cart_url ) : ?><a class="wp-theme-header-action wp-theme-cart-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'wp-theme' ); ?>"><span aria-hidden="true">◇</span><?php if ( $cart_count ) : ?><span class="wp-theme-cart-count"><?php echo absint( $cart_count ); ?></span><?php endif; ?></a><?php endif; ?>
            <?php if ( $show_lang && function_exists( 'wp_theme_render_language_switcher' ) ) echo wp_theme_render_language_switcher(); ?>
            <?php if ( $show_mode ) : ?><button class="wp-theme-header-action" type="button" data-theme-toggle aria-label="<?php esc_attr_e( 'Toggle light and dark theme', 'wp-theme' ); ?>"><span aria-hidden="true">◐</span></button><?php endif; ?>
            <button class="wp-theme-menu-toggle" type="button" data-menu-toggle aria-controls="wp-theme-primary-navigation" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle menu', 'wp-theme' ); ?>"><span></span><span></span><span></span></button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php if ( $show_search ) : ?><div id="wp-theme-header-search" class="wp-theme-header-search-panel" hidden><div class="container"><?php echo function_exists( 'wp_theme_ajax_search_markup' ) ? wp_theme_ajax_search_markup( array( 'post_types' => wp_theme_header_search_post_types() ) ) : get_search_form( false ); ?></div></div><?php endif; ?>
</header>
<!-- /wp:html -->
