<?php
/** WP BB Home & Garden 3.0.12 mini cart drawer. */
defined('ABSPATH') || exit;
$is_en = function_exists('wpbbshop_v312_is_en') && wpbbshop_v312_is_en();
do_action('woocommerce_before_mini_cart');
?>
<?php if (WC()->cart && !WC()->cart->is_empty()) : ?>
    <ul class="woocommerce-mini-cart cart_list product_list_widget llg-mini-cart-list">
        <?php do_action('woocommerce_before_mini_cart_contents'); ?>
        <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
            $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
            if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key)) { continue; }
            $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
            $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
            $price = function_exists('wpbbshop_v312_price_parts') ? wpbbshop_v312_price_parts($_product, 1) : array('current'=>WC()->cart->get_product_price($_product),'regular'=>'');
        ?>
            <li class="woocommerce-mini-cart-item mini_cart_item llg-mini-cart-item">
                <a class="llg-mini-cart-thumb" href="<?php echo esc_url($product_permalink ?: '#'); ?>"><?php echo function_exists('wpbbshop_v312_product_image_html') ? wpbbshop_v312_product_image_html($_product, 'llg-mini-cart-image') : $_product->get_image('woocommerce_thumbnail'); ?></a>
                <div class="llg-mini-cart-copy">
                    <a class="llg-mini-cart-title" href="<?php echo esc_url($product_permalink ?: '#'); ?>"><?php echo wp_kses_post($product_name); ?></a>
                    <?php if ($_product->get_sku()) : ?><span class="llg-mini-cart-sku">SKU: <?php echo esc_html($_product->get_sku()); ?></span><?php endif; ?>
                    <div class="llg-mini-cart-price">
                        <span><?php echo esc_html((int)$cart_item['quantity']); ?> ×</span>
                        <?php if (!empty($price['regular'])) : ?><del><?php echo wp_kses_post($price['regular']); ?></del><?php endif; ?>
                        <strong><?php echo wp_kses_post($price['current']); ?></strong>
                    </div>
                </div>
                <?php echo apply_filters('woocommerce_cart_item_remove_link', sprintf('<a role="button" href="%s" class="remove remove_from_cart_button llg-mini-cart-remove" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">×</a>', esc_url(wc_get_cart_remove_url($cart_item_key)), esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), wp_strip_all_tags($product_name))), esc_attr($product_id), esc_attr($cart_item_key), esc_attr($_product->get_sku())), $cart_item_key); ?>
            </li>
        <?php endforeach; ?>
        <?php do_action('woocommerce_mini_cart_contents'); ?>
    </ul>
    <div class="llg-mini-cart-summary">
        <p class="woocommerce-mini-cart__total total"><span><?php echo esc_html($is_en ? 'Subtotal' : 'Starpsumma'); ?></span><strong><?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?></strong></p>
        <?php do_action('woocommerce_widget_shopping_cart_before_buttons'); ?>
        <p class="woocommerce-mini-cart__buttons buttons llg-mini-cart-buttons">
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="button wc-forward llg-mini-cart-view"><?php echo esc_html($is_en ? 'View cart' : 'Apskatīt grozu'); ?></a>
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="button checkout wc-forward llg-mini-cart-checkout"><?php echo esc_html($is_en ? 'Checkout' : 'Veikt pasūtījumu'); ?></a>
        </p>
        <?php do_action('woocommerce_widget_shopping_cart_after_buttons'); ?>
    </div>
<?php else : ?>
    <div class="llg-mini-cart-empty">
        <div class="llg-mini-cart-empty-icon" aria-hidden="true">♡</div>
        <strong><?php echo esc_html($is_en ? 'Your cart is empty' : 'Grozs ir tukšs'); ?></strong>
        <p><?php echo esc_html($is_en ? 'Add a product and it will appear here.' : 'Pievieno preci, un tā parādīsies šeit.'); ?></p>
        <a class="button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html($is_en ? 'Browse products' : 'Skatīt preces'); ?></a>
    </div>
<?php endif; ?>
<?php do_action('woocommerce_after_mini_cart'); ?>
