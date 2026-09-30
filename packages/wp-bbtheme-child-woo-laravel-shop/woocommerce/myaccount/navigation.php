<?php
/** WP BB Home & Garden 3.0.12 account navigation. */
defined('ABSPATH') || exit;
$items = wc_get_account_menu_items();
$icons = array(
    'dashboard'=>'⌂','orders'=>'▤','downloads'=>'↓','edit-address'=>'⌖','payment-methods'=>'▣','edit-account'=>'○','customer-logout'=>'↗'
);
?>
<nav class="woocommerce-MyAccount-navigation llg-account-nav" aria-label="<?php esc_attr_e('Account pages', 'woocommerce'); ?>">
    <div class="llg-account-nav-title"><?php echo esc_html(function_exists('wpbbshop_v312_t') ? wpbbshop_v312_t('Mans konts','My account') : 'Mans konts'); ?></div>
    <ul>
        <?php foreach ($items as $endpoint => $label) : ?>
            <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>"><span class="llg-account-nav-icon" aria-hidden="true"><?php echo esc_html(isset($icons[$endpoint]) ? $icons[$endpoint] : '•'); ?></span><span><?php echo esc_html($label); ?></span></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
