<?php
/** WP BB Home & Garden My Account wrapper. */
defined('ABSPATH') || exit;
get_header(); ?>
<main class="llg-commerce-shell llg-account-shell">
  <div class="wpbbshop-container">
    <header class="llg-commerce-heading"><span>WP BB HOME & GARDEN</span><h1><?php echo esc_html(function_exists('wpbbshop_v312_t') ? (is_user_logged_in() ? wpbbshop_v312_t('Mans konts','My account') : wpbbshop_v312_t('Klienta konts','Customer account')) : (is_user_logged_in() ? 'My account' : 'Customer account')); ?></h1><p><?php echo esc_html(function_exists('wpbbshop_v312_t') ? (is_user_logged_in() ? wpbbshop_v312_t('Pārvaldi pasūtījumus, adreses un konta informāciju.','Manage orders, addresses and account information.') : wpbbshop_v312_t('Pieslēdzies vai izveido kontu ērtākai iepirkšanai.','Sign in or create an account for easier shopping.')) : (is_user_logged_in() ? 'Manage orders, addresses and account information.' : 'Sign in or create an account for easier shopping.')); ?></p></header>
    <div class="llg-commerce-card"><?php echo do_shortcode('[woocommerce_my_account]'); ?></div>
  </div>
</main>
<?php get_footer();
