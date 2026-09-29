<?php
/**
 * Title: Footer Default
 * Slug: footer-default
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: true
 */
$company = get_bloginfo( 'name' ) ?: __( 'WP BBTheme', 'wp-theme' );
$email = function_exists( 'wp_theme_acf_get' ) ? wp_theme_acf_get( 'email', 'option', get_option( 'admin_email' ) ) : get_option( 'admin_email' );
$profile = function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array();
$tagline = ! empty( $profile['footer_text'] ) ? $profile['footer_text'] : __( 'A modern Bootstrap and Gutenberg website.', 'wp-theme' );
?>
<!-- wp:html -->
<footer class="wp-theme-site-footer">
  <div class="container">
    <?php if ( function_exists( 'wp_theme_newsletter_markup' ) ) echo wp_theme_newsletter_markup(); ?>
    <div class="row g-5 wp-theme-footer-main">
      <div class="col-12 col-lg-4 wp-theme-footer-brand">
        <a class="wp-theme-footer-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo wp_theme_demo_logo( 'light' ); ?></a>
        <p><?php echo esc_html( $tagline ); ?></p>
      </div>
      <div class="col-6 col-md-4 col-lg-2"><h3><?php esc_html_e( 'Explore', 'wp-theme' ); ?></h3><?php echo wp_theme_demo_menu( 'wp-footer-menu', 'wp-theme-footer-menu' ); ?></div>
      <div class="col-6 col-md-4 col-lg-3"><h3><?php esc_html_e( 'Contact', 'wp-theme' ); ?></h3><p><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p><p><a href="<?php echo esc_url( wp_theme_demo_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Start a conversation', 'wp-theme' ); ?> →</a></p></div>
      <div class="col-12 col-md-4 col-lg-3"><h3><?php esc_html_e( 'Languages', 'wp-theme' ); ?></h3><?php echo function_exists( 'wp_theme_render_language_switcher' ) ? wp_theme_render_language_switcher( array( 'expanded' => true ) ) : '<p>' . esc_html__( 'Multilingual ready with Polylang.', 'wp-theme' ) . '</p>'; ?></div>
    </div>
    <div class="wp-theme-footer-bottom"><span>© <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( $company ); ?></span><span><?php esc_html_e( 'Bootstrap · Gutenberg · WP BBuilder', 'wp-theme' ); ?></span></div>
  </div>
</footer>
<!-- /wp:html -->
