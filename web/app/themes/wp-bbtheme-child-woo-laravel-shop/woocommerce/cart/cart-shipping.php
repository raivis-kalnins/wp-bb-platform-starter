<?php
/**
 * Shipping Methods — WP BB full-width summary override.
 *
 * Keeps WooCommerce's rate selection semantics but renders the shipping block
 * in a single colspan cell so cart and checkout sidebars cannot reserve an
 * empty second column beside the delivery methods.
 *
 * @package WooCommerce\Templates
 * @version 9.9.0-wpbb
 */

defined('ABSPATH') || exit;

$formatted_destination    = isset($formatted_destination) ? $formatted_destination : WC()->countries->get_formatted_address($package['destination'], ', ');
$has_calculated_shipping  = !empty($has_calculated_shipping);
$show_shipping_calculator = !empty($show_shipping_calculator);
$calculator_text          = '';
?>
<tr class="woocommerce-shipping-totals shipping wpbbshop-shipping-full-row">
    <td colspan="2" data-title="<?php echo esc_attr($package_name); ?>">
        <div class="wpbbshop-shipping-full">
            <strong class="wpbbshop-shipping-title"><?php echo wp_kses_post($package_name); ?></strong>

            <?php if (!empty($available_methods)) : ?>
                <ul id="shipping_method" class="woocommerce-shipping-methods">
                    <?php foreach ($available_methods as $method) :
                        $rate_id = method_exists($method, 'get_id') ? $method->get_id() : $method->id;
                        $input_id = 'shipping_method_' . esc_attr($index . '_' . sanitize_title($rate_id));
                        ?>
                        <li>
                            <input
                                type="radio"
                                name="shipping_method[<?php echo esc_attr($index); ?>]"
                                data-index="<?php echo esc_attr($index); ?>"
                                id="<?php echo esc_attr($input_id); ?>"
                                value="<?php echo esc_attr($rate_id); ?>"
                                class="shipping_method"
                                <?php checked($rate_id, $chosen_method); ?>
                            />
                            <label for="<?php echo esc_attr($input_id); ?>"><?php echo wp_kses_post(wc_cart_totals_shipping_method_label($method)); ?></label>
                            <?php do_action('woocommerce_after_shipping_rate', $method, $index); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (is_cart()) : ?>
                    <p class="woocommerce-shipping-destination">
                        <?php
                        if ($formatted_destination) {
                            printf(
                                /* translators: %s shipping destination. */
                                esc_html__('Shipping to %s.', 'woocommerce'),
                                '<strong>' . esc_html($formatted_destination) . '</strong>'
                            );
                            $calculator_text = esc_html__('Change address', 'woocommerce');
                        } else {
                            echo wp_kses_post(apply_filters('woocommerce_shipping_estimate_html', __('Shipping options will be updated during checkout.', 'woocommerce')));
                        }
                        ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($show_package_details)) : ?>
                    <p class="woocommerce-shipping-contents"><small><?php echo esc_html($package_details); ?></small></p>
                <?php endif; ?>

                <?php if ($show_shipping_calculator && is_cart()) : ?>
                    <?php woocommerce_shipping_calculator($calculator_text); ?>
                <?php endif; ?>
            <?php elseif (!$has_calculated_shipping || !$formatted_destination) : ?>
                <?php if (is_cart() && 'no' === get_option('woocommerce_enable_shipping_calc')) : ?>
                    <?php echo wp_kses_post(apply_filters('woocommerce_shipping_not_enabled_on_cart_html', __('Shipping costs are calculated during checkout.', 'woocommerce'))); ?>
                <?php else : ?>
                    <?php echo wp_kses_post(apply_filters('woocommerce_shipping_may_be_available_html', __('Enter your address to view shipping options.', 'woocommerce'))); ?>
                <?php endif; ?>
            <?php elseif (!is_cart()) : ?>
                <?php echo wp_kses_post(apply_filters('woocommerce_no_shipping_available_html', __('There are no shipping options available. Please check your address or contact us for help.', 'woocommerce'))); ?>
            <?php else : ?>
                <?php echo wp_kses_post(apply_filters('woocommerce_cart_no_shipping_available_html', __('There are no shipping options available for this address. Please check the address or contact us for help.', 'woocommerce'))); ?>
            <?php endif; ?>
        </div>
    </td>
</tr>
