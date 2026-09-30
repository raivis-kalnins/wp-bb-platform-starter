<?php
/** WP BB Home & Garden My Account wrapper. */
defined('ABSPATH') || exit;
get_header(); ?>
<main class="llg-commerce-shell llg-account-shell">
  <div class="wpbbshop-container">
    <header class="llg-commerce-heading"><span>WP BB HOME & GARDEN</span><h1><?php echo is_user_logged_in() ? esc_html__('Mans konts','wpbbshop') : esc_html__('Klienta konts','wpbbshop'); ?></h1><p><?php echo is_user_logged_in() ? esc_html__('Pārvaldi pasūtījumus, adreses un konta informāciju.','wpbbshop') : esc_html__('Pieslēdzies vai izveido kontu ērtākai iepirkšanai.','wpbbshop'); ?></p></header>
    <div class="llg-commerce-card"><?php echo do_shortcode('[woocommerce_my_account]'); ?></div>
  </div>
</main>
<?php get_footer();
