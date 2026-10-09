<?php
/** Retail and trade are separate audiences; enabling trade never implies B2B-only. */
defined('ABSPATH') || exit;

function iws_b2b_commerce_defaults() {
    return [
        'store_audience' => 'mixed', 'allowed_roles' => ['customer','wholesale_customer'],
        'allow_guests' => 'yes', 'wholesale_roles' => ['wholesale_customer'],
        'role_discounts' => [], 'sale_price_policy' => 'current',
        'allow_coupons' => 'yes', 'stack_discount_rules' => 'no',
        'enforce_case_packs' => 'no', 'trade_only_gateways' => ['purchase_order','invoice'],
    ];
}
function iws_b2b_user_is_trade($user) {
    if (!$user || !$user->exists() || in_array('pending_wholesale_customer', (array)$user->roles, true)) return false;
    $s = iws_b2b_get_settings();
    return (bool)array_intersect((array)$user->roles, (array)$s['wholesale_roles']);
}
function iws_b2b_can_purchase($user = null) {
    if (!iws_b2b_is_enabled()) return true;
    $s = iws_b2b_get_settings(); $user = $user ?: wp_get_current_user();
    switch ($s['store_audience']) {
        case 'wholesale': return iws_b2b_user_is_trade($user);
        case 'registered': return $user && $user->exists();
        case 'roles':
            return $user && $user->exists() ? (bool)array_intersect((array)$user->roles, (array)$s['allowed_roles']) : 'yes' === $s['allow_guests'];
        default: return true;
    }
}
function iws_b2b_purchase_message() {
    return iws_b2b_frontend_text('Purchasing is restricted for this account. Sign in with an eligible account or contact the store.', 'Pirkumi šim kontam ir ierobežoti. Pieslēdzieties ar atbilstošu kontu vai sazinieties ar veikalu.');
}
function iws_b2b_product_purchasable($purchasable, $product) {
    if (is_admin() && !wp_doing_ajax()) return $purchasable;
    return $purchasable && iws_b2b_can_purchase(); // Never override stock, missing prices or other plugins.
}
add_filter('woocommerce_is_purchasable', 'iws_b2b_product_purchasable', 50, 2);
add_filter('woocommerce_variation_is_purchasable', 'iws_b2b_product_purchasable', 50, 2);
add_filter('woocommerce_add_to_cart_validation', function($passed) {
    if (!iws_b2b_can_purchase()) { wc_add_notice(iws_b2b_purchase_message(), 'error'); return false; }
    return $passed;
}, 50);
add_action('woocommerce_check_cart_items', function() {
    if (WC()->cart && !WC()->cart->is_empty() && !iws_b2b_can_purchase()) wc_add_notice(iws_b2b_purchase_message(), 'error');
}, 5);
add_action('woocommerce_store_api_cart_errors', function($errors, $cart) {
    if (!$cart || $cart->is_empty()) return;
    if (!iws_b2b_can_purchase()) $errors->add('iws_b2b_purchase_restricted', iws_b2b_purchase_message());
    foreach (iws_b2b_rule_errors($cart) as $i => $message) $errors->add('iws_b2b_rule_' . $i, $message);
}, 20, 2);

function iws_b2b_rules_may_stack() {
    return !iws_b2b_is_enabled() || !iws_b2b_current_user_is_wholesale() || 'yes' === iws_b2b_get_settings()['stack_discount_rules'];
}
function iws_b2b_specific_price($product) {
    $price = $product->get_meta('_iws_b2b_wholesale_price', true);
    if ('' === $price && $product->is_type('variation')) {
        $parent = wc_get_product($product->get_parent_id());
        if ($parent) $price = $parent->get_meta('_iws_b2b_wholesale_price', true);
    }
    return $price;
}
function iws_b2b_resolve_price($price, $product, $qty = 1) {
    if ('' === $price || null === $price || !is_numeric($price)) return $price;
    $s = iws_b2b_get_settings();
    if ('exclude' === $s['sale_price_policy'] && $product->is_on_sale()) return $price;
    $specific = iws_b2b_specific_price($product);
    if ('' !== $specific && is_numeric($specific)) return max(0, (float)$specific);
    $base = (float)$price;
    if ('regular' === $s['sale_price_policy'] && '' !== $product->get_regular_price('edit')) $base = (float)$product->get_regular_price('edit');
    $discount = iws_b2b_discount_for_qty(max(1, (int)$qty));
    // Fixed amount and quantity percent are alternatives: choose the better price, never compound.
    $amount = 'yes' === $s['enable_discount'] && 'amount' === $s['discount_type'] ? (float)$s['discount_amount'] : 0;
    return round(max(0, min($base - $amount, $base * (1 - $discount / 100))), wc_get_price_decimals());
}
function iws_b2b_variation_context($hash, $product = null, $display = false) {
    $s = iws_b2b_get_settings();
    $hash['iws_b2b_v386'] = [
        'enabled' => iws_b2b_is_enabled(), 'trade' => iws_b2b_current_user_is_wholesale(),
        'discount' => iws_b2b_get_base_discount(), 'purchase' => iws_b2b_can_purchase(),
        'settings' => md5(wp_json_encode($s)),
    ];
    return $hash;
}
add_filter('woocommerce_get_variation_prices_hash', 'iws_b2b_variation_context', 30, 3);
add_filter('woocommerce_coupon_is_valid', function($valid) {
    if (iws_b2b_is_enabled() && iws_b2b_current_user_is_wholesale() && 'yes' !== iws_b2b_get_settings()['allow_coupons']) return false;
    return $valid;
}, 30);

function iws_b2b_product_rule($product, $key) {
    $value = $product->get_meta($key, true);
    if ('' === $value && $product->is_type('variation')) {
        $parent = wc_get_product($product->get_parent_id());
        if ($parent) $value = $parent->get_meta($key, true);
    }
    return absint($value);
}
function iws_b2b_rule_errors($cart, $product_only = false) {
    if (!iws_b2b_is_enabled() || !iws_b2b_current_user_is_wholesale() || !$cart) return [];
    $s = iws_b2b_get_settings(); $errors = []; $qty = 0;
    foreach ($cart->get_cart() as $item) {
        $product = $item['data'] ?? null; if (!$product instanceof WC_Product) continue;
        $count = (float)($item['quantity'] ?? 0); $qty += $count;
        $min = iws_b2b_product_rule($product, '_iws_b2b_min_qty');
        if ($min && $count < $min) $errors[] = sprintf(__('%1$s requires at least %2$d units for B2B orders.', 'wp-theme-woo-support'), $product->get_name(), $min);
        $pack = iws_b2b_product_rule($product, '_iws_b2b_case_pack');
        if ('yes' === $s['enforce_case_packs'] && $pack > 1 && abs(fmod($count, $pack)) > 0.00001) $errors[] = sprintf(__('%1$s must be ordered in multiples of %2$d for B2B orders.', 'wp-theme-woo-support'), $product->get_name(), $pack);
    }
    if (!$product_only) {
        if ('yes' === $s['enable_min_products'] && $qty > 0 && $qty < (int)$s['min_products']) $errors[] = iws_b2b_minimum_message($s, $qty);
        $subtotal = (float)$cart->get_cart_contents_total(); // after discounts/coupons, before shipping/tax.
        if ('yes' === $s['enable_min_order_value'] && $subtotal < (float)$s['min_order_value']) $errors[] = str_replace(['{min}','{total}'], [wp_strip_all_tags(wc_price($s['min_order_value'])),wp_strip_all_tags(wc_price($subtotal))], wp_strip_all_tags($s['min_value_message']));
    }
    return array_values(array_unique($errors));
}
function iws_b2b_sanitize_commerce($input, $out) {
    $out['store_audience'] = in_array($input['store_audience'] ?? '', ['mixed','registered','wholesale','roles'], true) ? $input['store_audience'] : 'mixed';
    $roles = array_keys(wp_roles()->roles);
    foreach (['allowed_roles','wholesale_roles'] as $key) $out[$key] = array_values(array_intersect($roles, array_map('sanitize_key', (array)($input[$key] ?? []))));
    $out['wholesale_roles'] = array_values(array_diff($out['wholesale_roles'], ['pending_wholesale_customer']));
    foreach (['allow_guests','allow_coupons','stack_discount_rules','enforce_case_packs'] as $key) $out[$key] = !empty($input[$key]) ? 'yes' : 'no';
    $out['sale_price_policy'] = in_array($input['sale_price_policy'] ?? '', ['current','regular','exclude'], true) ? $input['sale_price_policy'] : 'current';
    $out['role_discounts'] = [];
    foreach ((array)($input['role_discounts'] ?? []) as $role => $value) if (in_array($role, $out['wholesale_roles'], true) && '' !== trim((string)$value) && is_numeric(wc_format_decimal($value))) $out['role_discounts'][$role] = min(100, max(0, (float)wc_format_decimal($value)));
    $gateways = preg_split('/[\s,]+/', (string)($input['trade_only_gateways'] ?? 'purchase_order,invoice'));
    $out['trade_only_gateways'] = array_values(array_filter(array_unique(array_map('sanitize_key', $gateways))));
    // The explicit audience is authoritative. Do not leave legacy private flags hiding mixed retail prices.
    $out['private_store_mode'] = 'mixed' === $out['store_audience'] ? 'no' : 'yes';
    $out['hide_prices_logged_out'] = 'no';
    return $out;
}
function iws_b2b_commerce_settings_html($s) { ?>
<h2><?php esc_html_e('Who can buy? Retail and B2B access', 'wp-theme-woo-support'); ?></h2>
<p><?php esc_html_e('Retail customers keep normal prices, variations, sale prices and coupons. Only the roles selected as trade accounts receive B2B prices and order minimums. Existing private-store restrictions are preserved until you change this mode.', 'wp-theme-woo-support'); ?></p>
<table class="form-table" role="presentation">
<tr><th><label for="iws-store-audience"><?php esc_html_e('Store mode', 'wp-theme-woo-support'); ?></label></th><td><select id="iws-store-audience" name="iws_b2b_settings[store_audience]">
<?php foreach (['mixed'=>'Retail + B2B (everyone can buy)','registered'=>'Registered customers only','wholesale'=>'Approved B2B roles only','roles'=>'Selected roles and optional guests'] as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($s['store_audience'],$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?>
</select><p class="description">Recommended: Retail + B2B. Guest checkout is also subject to WooCommerce Accounts &amp; Privacy settings.</p></td></tr>
<tr><th>Selected-role mode</th><td><label><input type="checkbox" name="iws_b2b_settings[allow_guests]" value="1" <?php checked($s['allow_guests'],'yes'); ?>> Allow guests in selected-role mode</label></td></tr>
<tr><th>Role controls</th><td><table class="widefat striped"><thead><tr><th>Role</th><th>Can buy in selected-role mode</th><th>Approved trade role</th><th>Trade discount %</th></tr></thead><tbody>
<?php foreach (wp_roles()->roles as $role=>$details): ?><tr><td><?php echo esc_html(translate_user_role($details['name'])); ?></td><td><input aria-label="<?php echo esc_attr('Allow purchases: '.$details['name']); ?>" type="checkbox" name="iws_b2b_settings[allowed_roles][]" value="<?php echo esc_attr($role); ?>" <?php checked(in_array($role,(array)$s['allowed_roles'],true)); ?>></td><td><?php if ('pending_wholesale_customer' !== $role): ?><input aria-label="<?php echo esc_attr('Trade pricing: '.$details['name']); ?>" type="checkbox" name="iws_b2b_settings[wholesale_roles][]" value="<?php echo esc_attr($role); ?>" <?php checked(in_array($role,(array)$s['wholesale_roles'],true)); ?>><?php else: ?>Pending: no trade prices<?php endif; ?></td><td><input aria-label="<?php echo esc_attr('Trade discount: '.$details['name']); ?>" type="number" step="0.01" min="0" max="100" class="small-text" name="iws_b2b_settings[role_discounts][<?php echo esc_attr($role); ?>]" value="<?php echo esc_attr($s['role_discounts'][$role] ?? ''); ?>"></td></tr><?php endforeach; ?>
</tbody></table><p class="description">Marking a role as trade approves everyone in that role. Keep Customer unselected for normal retail accounts. A per-user discount overrides role discounts; otherwise the highest matching role discount applies, then the global default.</p></td></tr>
<tr><th>Sale-price policy</th><td><select name="iws_b2b_settings[sale_price_policy]"><?php foreach (['current'=>'Discount the current retail/sale price','regular'=>'Discount the regular price','exclude'=>'Keep sale items at their retail sale price'] as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>" <?php selected($s['sale_price_policy'],$key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td></tr>
<tr><th>Discount combinations</th><td><label><input type="checkbox" name="iws_b2b_settings[allow_coupons]" value="1" <?php checked($s['allow_coupons'],'yes'); ?>> Allow WooCommerce coupons for B2B</label><br><label><input type="checkbox" name="iws_b2b_settings[stack_discount_rules]" value="1" <?php checked($s['stack_discount_rules'],'yes'); ?>> Also apply this platform's Discount Rules to trade prices</label><p class="description">Unselected by default to avoid stacking wholesale and retail promotions. External pricing plugins need separate compatibility testing.</p></td></tr>
<tr><th>Case packs</th><td><label><input type="checkbox" name="iws_b2b_settings[enforce_case_packs]" value="1" <?php checked($s['enforce_case_packs'],'yes'); ?>> Enforce product case/pack multiples for trade accounts only</label></td></tr>
<tr><th>Trade-only payment IDs</th><td><input class="regular-text" name="iws_b2b_settings[trade_only_gateways]" value="<?php echo esc_attr(implode(',',(array)$s['trade_only_gateways'])); ?>"><p class="description">Only these installed gateway IDs are hidden from retail customers when the Invoice / PO option is on. Default: purchase_order,invoice. Normal bank transfer (bacs), cash on delivery (cod) and cheque remain available. This does not install an invoice gateway or grant credit.</p></td></tr>
</table>
<?php }
