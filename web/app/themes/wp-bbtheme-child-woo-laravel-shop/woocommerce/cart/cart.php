<?php
/** WP BB Home & Garden 3.0.12 cart. */
defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
$is_en = function_exists('wpbbshop_v312_is_en') && wpbbshop_v312_is_en();
?>
<div class="llg-woo-page llg-cart-page wpbbshop-cart-page">
    <header class="llg-woo-page-head">
        <div>
            <span class="llg-woo-kicker">WP BB HOME & GARDEN</span>
            <h1><?php echo esc_html($is_en ? 'Shopping cart' : 'Grozs'); ?></h1>
            <p><?php echo esc_html($is_en ? 'Review your products and quantities before checkout.' : 'Pārskati preces un daudzumu pirms pasūtījuma noformēšanas.'); ?></p>
        </div>
        <a class="llg-woo-back-link" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html($is_en ? 'Continue shopping' : 'Turpināt iepirkties'); ?> <span aria-hidden="true">→</span></a>
    </header>

    <div class="llg-cart-layout">
        <form class="woocommerce-cart-form llg-cart-card" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
            <?php do_action('woocommerce_before_cart_table'); ?>
            <div class="llg-cart-table-head" aria-hidden="true">
                <span><?php echo esc_html($is_en ? 'Product' : 'Produkts'); ?></span>
                <span><?php echo esc_html($is_en ? 'Price' : 'Cena'); ?></span>
                <span><?php echo esc_html($is_en ? 'Quantity' : 'Daudzums'); ?></span>
                <span><?php echo esc_html($is_en ? 'Subtotal' : 'Summa'); ?></span>
            </div>

            <div class="llg-cart-items">
                <?php do_action('woocommerce_before_cart_contents'); ?>
                <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                    if (!$_product instanceof WC_Product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) { continue; }
                    $permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                    $unit_price = function_exists('wpbbshop_v312_price_parts') ? wpbbshop_v312_price_parts($_product, 1) : array('current'=>WC()->cart->get_product_price($_product),'regular'=>'');
                    $line_price = function_exists('wpbbshop_v312_price_parts') ? wpbbshop_v312_price_parts($_product, (int)$cart_item['quantity']) : array('current'=>WC()->cart->get_product_subtotal($_product, $cart_item['quantity']),'regular'=>'');
                ?>
                    <article class="llg-cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                        <div class="llg-cart-product">
                            <a class="llg-cart-image" href="<?php echo esc_url($permalink ?: '#'); ?>">
                                <?php echo function_exists('wpbbshop_v312_product_image_html') ? wpbbshop_v312_product_image_html($_product, 'llg-cart-product-image') : $_product->get_image('woocommerce_thumbnail'); ?>
                            </a>
                            <div class="llg-cart-copy">
                                <div class="llg-cart-stock"><?php echo esc_html($_product->is_in_stock() ? ($is_en ? 'In stock' : 'Ir noliktavā') : ($is_en ? 'Out of stock' : 'Nav noliktavā')); ?></div>
                                <?php if ($permalink) : ?><a class="llg-cart-name" href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($_product->get_name()); ?></a><?php else : ?><strong class="llg-cart-name"><?php echo esc_html($_product->get_name()); ?></strong><?php endif; ?>
                                <?php if ($_product->get_sku()) : ?><div class="llg-cart-sku">SKU: <?php echo esc_html($_product->get_sku()); ?></div><?php endif; ?>
                                <div class="llg-cart-meta"><?php echo wp_kses_post(wc_get_formatted_cart_item_data($cart_item)); ?></div>
                                <?php echo apply_filters('woocommerce_cart_item_remove_link', sprintf('<a role="button" href="%s" class="remove llg-cart-remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">%s</a>', esc_url(wc_get_cart_remove_url($cart_item_key)), esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), wp_strip_all_tags($_product->get_name()))), esc_attr($product_id), esc_attr($_product->get_sku()), esc_html($is_en ? 'Remove' : 'Noņemt')), $cart_item_key); ?>
                            </div>
                        </div>
                        <div class="llg-cart-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                            <?php if (!empty($unit_price['regular'])) : ?><del><?php echo wp_kses_post($unit_price['regular']); ?></del><?php endif; ?>
                            <strong><?php echo wp_kses_post($unit_price['current']); ?></strong>
                        </div>
                        <div class="llg-cart-qty" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                            <?php
                            $min_quantity = $_product->is_sold_individually() ? 1 : 0;
                            $max_quantity = $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity();
                            echo apply_filters('woocommerce_cart_item_quantity', woocommerce_quantity_input(array(
                                'input_name' => "cart[{$cart_item_key}][qty]",
                                'input_value' => $cart_item['quantity'],
                                'max_value' => $max_quantity,
                                'min_value' => $min_quantity,
                                'product_name' => $_product->get_name(),
                            ), $_product, false), $cart_item_key, $cart_item);
                            ?>
                        </div>
                        <div class="llg-cart-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
                            <?php if (!empty($line_price['regular'])) : ?><del><?php echo wp_kses_post($line_price['regular']); ?></del><?php endif; ?>
                            <strong><?php echo wp_kses_post($line_price['current']); ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php do_action('woocommerce_cart_contents'); ?>
                <?php do_action('woocommerce_after_cart_contents'); ?>
            </div>

            <div class="llg-cart-actions">
                <?php if (wc_coupons_enabled()) : ?>
                    <div class="coupon llg-cart-coupon">
                        <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php echo esc_attr($is_en ? 'Coupon code' : 'Kupona kods'); ?>">
                        <button type="submit" class="button llg-btn llg-btn-secondary" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php echo esc_html($is_en ? 'Apply coupon' : 'Izmantot kuponu'); ?></button>
                        <?php do_action('woocommerce_cart_coupon'); ?>
                    </div>
                <?php endif; ?>
                <button type="submit" class="button llg-btn llg-btn-ghost" name="update_cart" value="<?php esc_attr_e('Update cart', 'woocommerce'); ?>"><?php echo esc_html($is_en ? 'Update cart' : 'Atjaunināt grozu'); ?></button>
                <?php do_action('woocommerce_cart_actions'); wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
            </div>
            <?php do_action('woocommerce_after_cart_table'); ?>
        </form>

        <aside class="llg-cart-summary-card">
            <div class="llg-summary-eyebrow"><?php echo esc_html($is_en ? 'ORDER SUMMARY' : 'PASŪTĪJUMA KOPSAVILKUMS'); ?></div>
            <?php do_action('woocommerce_before_cart_collaterals'); ?>
            <div class="cart-collaterals"><?php do_action('woocommerce_cart_collaterals'); ?></div>
        </aside>
    </div>
</div>
<?php do_action('woocommerce_after_cart'); ?>
