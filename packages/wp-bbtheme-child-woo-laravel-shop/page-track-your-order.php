<?php
/** WP BB HOME & GARDEN V18 order tracking page. */
defined('ABSPATH') || exit;
get_header(); ?>
<main class="wpbbshop-track-page wpbbshop-container">
  <section class="wpbbshop-track-card">
    <div class="wpbbshop-track-copy">
      <span class="wpbbshop-eyebrow">WP BB HOME & GARDEN</span>
      <h1><?php esc_html_e('Sekot pasūtījumam', 'wpbbshop'); ?></h1>
      <p><?php esc_html_e('Ievadi pasūtījuma numuru un e-pasta adresi, lai pārbaudītu pasūtījuma statusu.', 'wpbbshop'); ?></p>
    </div>
    <div class="wpbbshop-track-form">
      <?php echo do_shortcode('[woocommerce_order_tracking]'); ?>
    </div>
  </section>
</main>
<?php get_footer();
