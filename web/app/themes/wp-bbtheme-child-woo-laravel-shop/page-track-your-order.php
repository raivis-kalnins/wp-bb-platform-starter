<?php
/** WP BB HOME & GARDEN order tracking page. */
defined('ABSPATH') || exit;

$is_en = false;
if (function_exists('pll_current_language')) {
    $lang = pll_current_language('slug');
    $is_en = ($lang === 'en');
} else {
    $is_en = substr((string) get_locale(), 0, 2) === 'en';
}

get_header(); ?>
<main class="wpbbshop-track-page wpbbshop-container">
  <?php if (function_exists('wpbbshop_seo_breadcrumbs_235')) { echo wpbbshop_seo_breadcrumbs_235(); } ?>
  <section class="wpbbshop-track-card">
    <div class="wpbbshop-track-copy">
      <span class="wpbbshop-eyebrow">WP BB HOME &amp; GARDEN</span>
      <h1><?php echo esc_html($is_en ? 'Track your order' : 'Sekot pasūtījumam'); ?></h1>
      <p><?php echo esc_html($is_en ? 'Enter your order number and email address to check the current order status.' : 'Ievadi pasūtījuma numuru un e-pasta adresi, lai pārbaudītu pasūtījuma statusu.'); ?></p>
    </div>
    <div class="wpbbshop-track-form">
      <?php echo do_shortcode('[woocommerce_order_tracking]'); ?>
    </div>
  </section>
</main>
<?php get_footer();
