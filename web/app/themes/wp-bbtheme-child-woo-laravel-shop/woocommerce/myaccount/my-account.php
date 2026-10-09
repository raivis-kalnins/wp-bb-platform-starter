<?php
/**
 * WP BB Home & Garden My Account wrapper.
 * Keeps WooCommerce endpoint logic intact while enforcing a stable two-column layout.
 */
defined('ABSPATH') || exit;
?>
<div class="llg-myaccount-layout">
    <aside class="llg-myaccount-nav-card" aria-label="<?php echo esc_attr__('Account navigation', 'woocommerce'); ?>">
        <h2 class="llg-myaccount-nav-title"><?php echo esc_html(function_exists('pll_current_language') && wpbbshop_v433_current_language() === 'en' ? 'My account' : 'Mans konts'); ?></h2>
        <?php do_action('woocommerce_account_navigation'); ?>
    </aside>
    <section class="llg-myaccount-content-card">
        <div class="woocommerce-MyAccount-content">
            <?php do_action('woocommerce_account_content'); ?>
        </div>
    </section>
</div>
