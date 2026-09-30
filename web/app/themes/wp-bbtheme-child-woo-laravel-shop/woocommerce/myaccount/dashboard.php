<?php
/** WP BB Home & Garden 3.0.12 account dashboard. */
defined('ABSPATH') || exit;
$current_user = wp_get_current_user();
$is_en = function_exists('wpbbshop_v312_is_en') && wpbbshop_v312_is_en();
?>
<div class="llg-dashboard-welcome">
    <span class="llg-woo-kicker">WP BB HOME & GARDEN</span>
    <h2><?php echo esc_html(sprintf($is_en ? 'Hello, %s!' : 'Sveiki, %s!', $current_user->display_name)); ?></h2>
    <p><?php echo esc_html($is_en ? 'From here you can review orders, update delivery addresses and manage your account details.' : 'Šeit vari apskatīt pasūtījumus, pārvaldīt piegādes adreses un atjaunināt sava konta informāciju.'); ?></p>
</div>
<div class="llg-dashboard-grid">
    <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><strong><?php echo esc_html($is_en ? 'Orders' : 'Pasūtījumi'); ?></strong><span><?php echo esc_html($is_en ? 'View order history and statuses' : 'Pirkumu vēsture un pasūtījumu statuss'); ?></span><b>→</b></a>
    <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>"><strong><?php echo esc_html($is_en ? 'Addresses' : 'Adreses'); ?></strong><span><?php echo esc_html($is_en ? 'Billing and delivery addresses' : 'Norēķinu un piegādes adreses'); ?></span><b>→</b></a>
    <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>"><strong><?php echo esc_html($is_en ? 'Account details' : 'Profila informācija'); ?></strong><span><?php echo esc_html($is_en ? 'Name, email and password' : 'Vārds, e-pasts un parole'); ?></span><b>→</b></a>
</div>
<?php do_action('woocommerce_account_dashboard'); ?>
