<?php
/** WP BB Home & Garden 3.0.12 checkout. */
defined('ABSPATH') || exit;
$is_en = function_exists('wpbbshop_v312_is_en') && wpbbshop_v312_is_en();
do_action('woocommerce_before_checkout_form', $checkout);
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}
?>
<div class="llg-woo-page llg-checkout-page wpbbshop-checkout-page">
    <header class="llg-woo-page-head">
        <div>
            <span class="llg-woo-kicker">WP BB HOME & GARDEN</span>
            <h1><?php echo esc_html($is_en ? 'Checkout' : 'Noformēt pasūtījumu'); ?></h1>
            <p><?php echo esc_html($is_en ? 'Enter delivery details and review the order before placing it.' : 'Aizpildi piegādes informāciju un pārbaudi pasūtījuma kopsavilkumu.'); ?></p>
        </div>
        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="llg-woo-back-link"><span aria-hidden="true">←</span> <?php echo esc_html($is_en ? 'Back to cart' : 'Atpakaļ uz grozu'); ?></a>
    </header>

    <form name="checkout" method="post" class="checkout woocommerce-checkout llg-checkout-form wpbbshop-checkout-form" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Checkout', 'woocommerce'); ?>">
        <div class="llg-checkout-grid">
            <section class="llg-checkout-main">
                <?php if ($checkout->get_checkout_fields()) : ?>
                    <?php do_action('woocommerce_checkout_before_customer_details'); ?>
                    <div class="llg-checkout-card" id="customer_details">
                        <div class="llg-checkout-card-head">
                            <span class="llg-checkout-step">1</span>
                            <div><h2><?php echo esc_html($is_en ? 'Billing & delivery details' : 'Norēķinu un piegādes informācija'); ?></h2><p><?php echo esc_html($is_en ? 'Fields marked with * are required.' : 'Lauki ar * ir obligāti.'); ?></p></div>
                        </div>
                        <div class="llg-checkout-fields"><?php do_action('woocommerce_checkout_billing'); ?><?php do_action('woocommerce_checkout_shipping'); ?></div>
                    </div>
                    <?php do_action('woocommerce_checkout_after_customer_details'); ?>
                <?php endif; ?>
            </section>

            <aside class="llg-checkout-sidebar">
                <div class="llg-checkout-card llg-order-card">
                    <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
                    <div class="llg-checkout-card-head">
                        <span class="llg-checkout-step">2</span>
                        <div><h2 id="order_review_heading"><?php echo esc_html($is_en ? 'Your order' : 'Jūsu pasūtījums'); ?></h2><p><?php echo esc_html($is_en ? 'Products, delivery and payment.' : 'Preces, piegāde un apmaksa.'); ?></p></div>
                    </div>
                    <?php do_action('woocommerce_checkout_before_order_review'); ?>
                    <div id="order_review" class="woocommerce-checkout-review-order"><?php do_action('woocommerce_checkout_order_review'); ?></div>
                    <?php do_action('woocommerce_checkout_after_order_review'); ?>
                </div>
            </aside>
        </div>
    </form>
</div>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
