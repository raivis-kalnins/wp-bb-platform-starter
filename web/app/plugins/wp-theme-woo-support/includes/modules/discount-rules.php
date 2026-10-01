<?php
/**
 * Lightweight WooCommerce Discount Rules in theme.
 *
 * Plugin-owned discount rules with WooCommerce-only loading:
 * - WooCommerce > Discount Rules admin page
 * - Product/customer/level/global discounts
 * - Product price and targeted cart subtotal discounts
 * - Price display on blocks/shop/archive/single/cart/checkout/order totals via WooCommerce prices/totals
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

if ( ! class_exists( 'IWS_Woo_Discount_Rules' ) ) {
	final class IWS_Woo_Discount_Rules {
		const OPTION = 'iws_woo_discount_rules';
		const NONCE  = 'iws_woo_discount_rules_nonce';
		const USER_LEVEL_META = '_iws_discount_level';
		const ORDER_ITEM_RULE_META = '_iws_discount_rules_applied';

		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 99 );
			add_action( 'admin_post_iws_save_discount_rules', array( __CLASS__, 'save_rules' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
			add_action( 'show_user_profile', array( __CLASS__, 'user_profile_field' ) );
			add_action( 'edit_user_profile', array( __CLASS__, 'user_profile_field' ) );
			add_action( 'personal_options_update', array( __CLASS__, 'save_user_profile_field' ) );
			add_action( 'edit_user_profile_update', array( __CLASS__, 'save_user_profile_field' ) );
			add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_product_discounts' ), 9999 );
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'apply_cart_discounts' ), 9999 );
			add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'price_html' ), 20, 2 );
			add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'cart_item_price_html' ), 20, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'cart_item_subtotal_html' ), 20, 3 );
			add_filter( 'woocommerce_checkout_cart_item_quantity', array( __CLASS__, 'checkout_item_discount_html' ), 20, 3 );
			add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'runtime_product_price' ), 20, 2 );
			add_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'runtime_product_price' ), 20, 2 );
			add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'store_order_item_discount_meta' ), 20, 4 );
		}

		public static function admin_menu() {
			add_submenu_page( 'woocommerce', __( 'Discount Rules', 'iws' ), __( 'Discount Rules', 'iws' ), 'manage_woocommerce', 'iws-discount-rules', array( __CLASS__, 'render_admin_page' ) );
		}

		public static function admin_assets( $hook ) {
			if ( 'woocommerce_page_iws-discount-rules' !== $hook ) {
				return;
			}
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'select2' );
		}

		public static function defaults() {
			return array(
				'enabled' => 'yes',
				'conflict_mode' => 'stack',
				'debug' => 'no',
				'levels'  => array(
					'silver' => array( 'label' => 'Silver', 'discount' => '0', 'type' => 'percentage' ),
					'gold'   => array( 'label' => 'Gold', 'discount' => '0', 'type' => 'percentage' ),
					'vip'    => array( 'label' => 'VIP', 'discount' => '0', 'type' => 'percentage' ),
				),
				'rules'   => array(),
			);
		}

		public static function get_settings() {
			$settings = get_option( self::OPTION, array() );
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}
			$settings = wp_parse_args( $settings, self::defaults() );
			$settings['levels'] = wp_parse_args( isset( $settings['levels'] ) && is_array( $settings['levels'] ) ? $settings['levels'] : array(), self::defaults()['levels'] );
			return $settings;
		}

		public static function get_rules() {
			$settings = self::get_settings();
			$rules    = isset( $settings['rules'] ) && is_array( $settings['rules'] ) ? $settings['rules'] : array();
			usort( $rules, function ( $a, $b ) { return (int) ( $a['priority'] ?? 10 ) <=> (int) ( $b['priority'] ?? 10 ); } );
			return $rules;
		}

		public static function save_rules() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to manage discount rules.', 'iws' ) );
			}
			check_admin_referer( self::NONCE );

			$raw_levels = isset( $_POST['levels'] ) && is_array( $_POST['levels'] ) ? wp_unslash( $_POST['levels'] ) : array();
			$levels = array();
			foreach ( array( 'silver', 'gold', 'vip' ) as $key ) {
				$levels[ $key ] = array(
					'label'    => sanitize_text_field( $raw_levels[ $key ]['label'] ?? ucfirst( $key ) ),
					'discount' => wc_format_decimal( $raw_levels[ $key ]['discount'] ?? 0 ),
					'type'     => in_array( $raw_levels[ $key ]['type'] ?? 'percentage', array( 'percentage', 'fixed' ), true ) ? $raw_levels[ $key ]['type'] : 'percentage',
				);
			}

			$raw_rules = isset( $_POST['rules'] ) && is_array( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : array();
			$rules = array();
			foreach ( $raw_rules as $raw_rule ) {
				$title = sanitize_text_field( $raw_rule['title'] ?? '' );
				$value = wc_format_decimal( $raw_rule['value'] ?? 0 );
				if ( '' === $title || (float) $value <= 0 ) {
					continue;
				}
				$rules[] = array(
					'id'           => sanitize_key( $raw_rule['id'] ?? uniqid( 'rule_', false ) ),
					'enabled'      => ! empty( $raw_rule['enabled'] ) ? 'yes' : 'no',
					'title'        => $title,
					'priority'     => absint( $raw_rule['priority'] ?? 10 ),
					'target'       => in_array( $raw_rule['target'] ?? 'product', array( 'product', 'cart' ), true ) ? $raw_rule['target'] : 'product',
					'discount'     => in_array( $raw_rule['discount'] ?? 'percentage', array( 'percentage', 'fixed' ), true ) ? $raw_rule['discount'] : 'percentage',
					'value'        => $value,
					'apply_to'     => in_array( $raw_rule['apply_to'] ?? 'all', array( 'all', 'products', 'categories' ), true ) ? $raw_rule['apply_to'] : 'all',
					'product_ids'  => self::clean_ids( $raw_rule['product_ids'] ?? array() ),
					'category_ids' => self::clean_ids( $raw_rule['category_ids'] ?? array() ),
					'customer_type'=> in_array( $raw_rule['customer_type'] ?? 'all', array( 'all', 'users', 'levels' ), true ) ? $raw_rule['customer_type'] : 'all',
					'user_ids'     => self::clean_ids( $raw_rule['user_ids'] ?? array() ),
					'user_levels'  => self::clean_levels( $raw_rule['user_levels'] ?? array() ),
					'min_qty'      => absint( $raw_rule['min_qty'] ?? 0 ),
					'max_qty'      => absint( $raw_rule['max_qty'] ?? 0 ),
					'min_subtotal' => wc_format_decimal( $raw_rule['min_subtotal'] ?? 0 ),
					'from_date'    => sanitize_text_field( $raw_rule['from_date'] ?? '' ),
					'to_date'      => sanitize_text_field( $raw_rule['to_date'] ?? '' ),
					'exclude_sale' => ! empty( $raw_rule['exclude_sale'] ) ? 'yes' : 'no',
					'show_badge'   => ! empty( $raw_rule['show_badge'] ) ? 'yes' : 'no',
				);
			}

			update_option( self::OPTION, array( 'enabled' => ! empty( $_POST['enabled'] ) ? 'yes' : 'no', 'conflict_mode' => in_array( $_POST['conflict_mode'] ?? 'stack', array( 'stack', 'first', 'best' ), true ) ? sanitize_key( $_POST['conflict_mode'] ) : 'stack', 'debug' => ! empty( $_POST['debug'] ) ? 'yes' : 'no', 'levels' => $levels, 'rules' => $rules ), false );
			wp_safe_redirect( add_query_arg( array( 'page' => 'iws-discount-rules', 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		private static function clean_ids( $value ) {
			if ( is_string( $value ) ) {
				$value = explode( ',', $value );
			}
			$value = is_array( $value ) ? $value : array();
			return implode( ',', array_unique( array_filter( array_map( 'absint', $value ) ) ) );
		}

		private static function clean_levels( $value ) {
			$value = is_array( $value ) ? $value : array( $value );
			$allowed = array( 'silver', 'gold', 'vip' );
			return implode( ',', array_intersect( array_map( 'sanitize_key', $value ), $allowed ) );
		}

		public static function render_admin_page() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
			$settings = self::get_settings();
			$rules = self::get_rules();
			if ( empty( $rules ) ) { $rules[] = self::blank_rule(); }
			?>
			<div class="wrap iws-discount-rules-wrap">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Discount Rules', 'iws' ); ?></h1>
				<button type="button" class="page-title-action" id="iws-add-discount-rule-top"><?php esc_html_e( 'Add rule', 'iws' ); ?></button>
				<hr class="wp-header-end">
				<?php if ( isset( $_GET['updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Discount rules saved.', 'iws' ); ?></p></div><?php endif; ?>
				<p class="description"><?php esc_html_e( 'Discounts can be global, product-specific, selected-user, or customer-level based. Product discounts update price display and checkout totals.', 'iws' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="iws_save_discount_rules" /><?php wp_nonce_field( self::NONCE ); ?>
					<div class="postbox iws-settings-box"><h2 class="hndle"><span><?php esc_html_e( 'General settings', 'iws' ); ?></span></h2><div class="inside"><table class="form-table" role="presentation"><tr><th scope="row"><?php esc_html_e( 'Enable discount rules', 'iws' ); ?></th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'], 'yes' ); ?> /> <?php esc_html_e( 'Active', 'iws' ); ?></label></td></tr><tr><th scope="row"><?php esc_html_e( 'Multiple matching rules', 'iws' ); ?></th><td><select name="conflict_mode"><option value="stack" <?php selected( $settings['conflict_mode'], 'stack' ); ?>><?php esc_html_e( 'Stack all matching discounts', 'iws' ); ?></option><option value="first" <?php selected( $settings['conflict_mode'], 'first' ); ?>><?php esc_html_e( 'Use first matching rule by priority', 'iws' ); ?></option><option value="best" <?php selected( $settings['conflict_mode'], 'best' ); ?>><?php esc_html_e( 'Use best discount only', 'iws' ); ?></option></select><p class="description"><?php esc_html_e( 'Use first/best if you want to avoid accidental double discounts.', 'iws' ); ?></p></td></tr><tr><th scope="row"><?php esc_html_e( 'Debug notes', 'iws' ); ?></th><td><label><input type="checkbox" name="debug" value="1" <?php checked( $settings['debug'], 'yes' ); ?> /> <?php esc_html_e( 'Show applied rule names under discounted cart/checkout lines for administrators only', 'iws' ); ?></label></td></tr></table></div></div>
					<div class="postbox iws-settings-box"><h2 class="hndle"><span><?php esc_html_e( 'Customer discount levels', 'iws' ); ?></span></h2><div class="inside"><p class="description"><?php esc_html_e( 'Set the 3 customer levels here, then assign a level on each user profile or target levels inside rules.', 'iws' ); ?></p><table class="widefat striped iws-level-table"><thead><tr><th><?php esc_html_e( 'Level key', 'iws' ); ?></th><th><?php esc_html_e( 'Label', 'iws' ); ?></th><th><?php esc_html_e( 'Discount', 'iws' ); ?></th><th><?php esc_html_e( 'Type', 'iws' ); ?></th></tr></thead><tbody><?php foreach ( array( 'silver', 'gold', 'vip' ) as $level_key ) : $level = $settings['levels'][ $level_key ]; ?><tr><td><code><?php echo esc_html( $level_key ); ?></code></td><td><input class="regular-text" type="text" name="levels[<?php echo esc_attr( $level_key ); ?>][label]" value="<?php echo esc_attr( $level['label'] ); ?>" /></td><td><input class="small-text" type="number" step="0.01" min="0" name="levels[<?php echo esc_attr( $level_key ); ?>][discount]" value="<?php echo esc_attr( $level['discount'] ); ?>" /></td><td><select name="levels[<?php echo esc_attr( $level_key ); ?>][type]"><option value="percentage" <?php selected( $level['type'], 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'iws' ); ?></option><option value="fixed" <?php selected( $level['type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'iws' ); ?></option></select></td></tr><?php endforeach; ?></tbody></table></div></div>
					<div id="iws-discount-rules" class="iws-discount-rules-list"><?php foreach ( $rules as $index => $rule ) : self::render_rule_row( $index, $rule, $settings ); endforeach; ?></div>
					<p><button type="button" class="button button-secondary" id="iws-add-discount-rule"><?php esc_html_e( 'Add rule', 'iws' ); ?></button></p><?php submit_button( __( 'Save discount rules', 'iws' ), 'primary', 'submit', true ); ?>
				</form>
			</div>
			<style>
			.iws-discount-rules-wrap .description{margin:12px 0 18px}.iws-settings-box,.iws-discount-rule{max-width:1180px}.iws-settings-box{margin-top:16px}.iws-discount-rule{margin:0 0 16px;border:1px solid #c3c4c7;background:#fff}.iws-discount-rule .hndle,.iws-settings-box .hndle{display:flex;align-items:center;justify-content:space-between;margin:0;padding:12px 16px;border-bottom:1px solid #c3c4c7;font-size:14px;line-height:1.4}.iws-rule-badge{display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;background:#f0f0f1;color:#50575e;font-size:12px;font-weight:400}.iws-discount-rule .inside{margin:0;padding:0 16px 16px}.iws-discount-rule .form-table{margin-top:0}.iws-discount-rule .form-table th{width:185px;padding:16px 10px 16px 0}.iws-discount-rule .form-table td{padding:11px 10px}.iws-discount-rule .regular-text,.iws-discount-rule select,.iws-discount-rule .wc-product-search,.iws-discount-rule .wc-customer-search{max-width:460px;width:100%}.iws-discount-rule .small-text{width:96px}.iws-rule-actions{padding-top:8px;border-top:1px solid #dcdcde}.iws-rule-row-split{display:flex;gap:16px;align-items:center;flex-wrap:wrap}.iws-rule-row-split label{margin-right:8px}.iws-remove-rule{color:#b32d2e!important;border-color:#b32d2e!important}.iws-remove-rule:hover{background:#b32d2e!important;color:#fff!important}.iws-level-table input.regular-text{width:100%;max-width:260px}.iws-multi-choice{min-width:260px}.iws-help{display:block;margin-top:6px;color:#646970}@media(max-width:782px){.iws-discount-rule .form-table th{width:auto;padding-bottom:0}.iws-discount-rule .form-table td{padding-left:0}.iws-discount-rule .regular-text,.iws-discount-rule select{max-width:100%}}
			</style>
			<script>
			(function($){
				function initSelects(scope){
					if($.fn.selectWoo){
						$(scope).find('.wc-enhanced-select').filter(':not(.enhanced)').selectWoo({width:'resolve'}).addClass('enhanced');
						$(scope).find('.wc-product-search, .wc-customer-search').filter(':not(.enhanced)').each(function(){
							var $el=$(this); if($el.data('select2')){return;} $el.addClass('enhanced');
						});
					}
				}
				const wrap=document.getElementById('iws-discount-rules');
				function addRule(){ if(!wrap){return;} const first=wrap.querySelector('.iws-discount-rule'); if(!first){return;} const clone=first.cloneNode(true); const index=Date.now(); clone.querySelectorAll('[name]').forEach(function(el){ el.name=el.name.replace(/rules\[[^\]]+\]/,'rules['+index+']'); if(el.type==='checkbox'){el.checked=el.name.indexOf('[enabled]')!==-1;} else if(el.tagName==='SELECT'){ $(el).removeClass('enhanced').removeAttr('data-select2-id').find('option').prop('selected',false); el.selectedIndex=0; } else if(el.name.indexOf('[id]')===-1){el.value='';} }); clone.querySelectorAll('[id]').forEach(function(el){el.removeAttribute('id');}); const id=clone.querySelector('input[name$="[id]"]'); if(id){id.value='rule_'+index;} const title=clone.querySelector('.iws-rule-title'); if(title){title.textContent='<?php echo esc_js( __( 'New discount rule', 'iws' ) ); ?>';} $(clone).find('.select2,.selectWoo').remove(); wrap.appendChild(clone); initSelects(clone); clone.scrollIntoView({behavior:'smooth',block:'start'}); }
				$('#iws-add-discount-rule,#iws-add-discount-rule-top').on('click',addRule);
				$(wrap).on('click','.iws-remove-rule',function(e){e.preventDefault(); if(wrap.querySelectorAll('.iws-discount-rule').length>1){this.closest('.iws-discount-rule').remove();}});
				initSelects(document);
			})(jQuery);
			</script>
			<?php
		}

		private static function blank_rule() {
			return array( 'id' => uniqid( 'rule_', false ), 'enabled' => 'yes', 'title' => '', 'priority' => 10, 'target' => 'product', 'discount' => 'percentage', 'value' => '', 'apply_to' => 'all', 'product_ids' => '', 'category_ids' => '', 'customer_type' => 'all', 'user_ids' => '', 'user_levels' => '', 'min_qty' => '', 'max_qty' => '', 'min_subtotal' => '', 'from_date' => '', 'to_date' => '', 'exclude_sale' => 'no', 'show_badge' => 'yes' );
		}

		private static function render_rule_row( $index, $rule, $settings ) {
			$rule = wp_parse_args( $rule, self::blank_rule() );
			$name = 'rules[' . esc_attr( $index ) . ']';
			$title = $rule['title'] ? $rule['title'] : __( 'Discount rule', 'iws' );
			?>
			<div class="postbox iws-discount-rule"><h2 class="hndle"><span class="iws-rule-title"><?php echo esc_html( $title ); ?></span><span class="iws-rule-badge"><?php echo 'yes' === $rule['enabled'] ? esc_html__( 'Enabled', 'iws' ) : esc_html__( 'Disabled', 'iws' ); ?></span></h2><div class="inside"><input type="hidden" name="<?php echo $name; ?>[id]" value="<?php echo esc_attr( $rule['id'] ); ?>" />
			<table class="form-table" role="presentation"><tbody>
			<tr><th scope="row"><?php esc_html_e( 'Status', 'iws' ); ?></th><td><label><input type="checkbox" name="<?php echo $name; ?>[enabled]" value="1" <?php checked( $rule['enabled'], 'yes' ); ?> /> <?php esc_html_e( 'Enable this rule', 'iws' ); ?></label></td></tr>
			<tr><th scope="row"><label><?php esc_html_e( 'Rule title', 'iws' ); ?></label></th><td><input class="regular-text" type="text" name="<?php echo $name; ?>[title]" value="<?php echo esc_attr( $rule['title'] ); ?>" placeholder="<?php esc_attr_e( '10% for Gold customers', 'iws' ); ?>" /></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Discount', 'iws' ); ?></th><td><div class="iws-rule-row-split"><label><?php esc_html_e( 'Type', 'iws' ); ?> <select name="<?php echo $name; ?>[discount]"><option value="percentage" <?php selected( $rule['discount'], 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'iws' ); ?></option><option value="fixed" <?php selected( $rule['discount'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'iws' ); ?></option></select></label><label><?php esc_html_e( 'Value', 'iws' ); ?> <input class="small-text" type="number" step="0.01" min="0" name="<?php echo $name; ?>[value]" value="<?php echo esc_attr( $rule['value'] ); ?>" /></label></div></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Products', 'iws' ); ?></th><td><div class="iws-rule-row-split"><label><?php esc_html_e( 'Target', 'iws' ); ?> <select name="<?php echo $name; ?>[target]"><option value="product" <?php selected( $rule['target'], 'product' ); ?>><?php esc_html_e( 'Product price', 'iws' ); ?></option><option value="cart" <?php selected( $rule['target'], 'cart' ); ?>><?php esc_html_e( 'Cart subtotal', 'iws' ); ?></option></select></label><label><?php esc_html_e( 'Apply to', 'iws' ); ?> <select name="<?php echo $name; ?>[apply_to]"><option value="all" <?php selected( $rule['apply_to'], 'all' ); ?>><?php esc_html_e( 'All products', 'iws' ); ?></option><option value="products" <?php selected( $rule['apply_to'], 'products' ); ?>><?php esc_html_e( 'Selected products', 'iws' ); ?></option><option value="categories" <?php selected( $rule['apply_to'], 'categories' ); ?>><?php esc_html_e( 'Selected categories', 'iws' ); ?></option></select></label></div></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Select products', 'iws' ); ?></th><td><?php self::product_select( $name . '[product_ids]', $rule['product_ids'] ); ?><span class="iws-help"><?php esc_html_e( 'Start typing product name or SKU. This replaces manual product ID typing.', 'iws' ); ?></span></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Categories', 'iws' ); ?></th><td><?php self::category_select( $name . '[category_ids]', $rule['category_ids'] ); ?></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Customers', 'iws' ); ?></th><td><div class="iws-rule-row-split"><label><?php esc_html_e( 'Customer scope', 'iws' ); ?> <select name="<?php echo $name; ?>[customer_type]"><option value="all" <?php selected( $rule['customer_type'], 'all' ); ?>><?php esc_html_e( 'Everyone / global', 'iws' ); ?></option><option value="users" <?php selected( $rule['customer_type'], 'users' ); ?>><?php esc_html_e( 'Selected users', 'iws' ); ?></option><option value="levels" <?php selected( $rule['customer_type'], 'levels' ); ?>><?php esc_html_e( 'Customer levels', 'iws' ); ?></option></select></label></div><div style="margin-top:10px"><?php self::customer_select( $name . '[user_ids]', $rule['user_ids'] ); ?></div><div style="margin-top:10px"><?php self::level_select( $name . '[user_levels]', $rule['user_levels'], $settings['levels'] ); ?></div></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Conditions', 'iws' ); ?></th><td><div class="iws-rule-row-split"><label><?php esc_html_e( 'Min qty', 'iws' ); ?> <input class="small-text" type="number" min="0" name="<?php echo $name; ?>[min_qty]" value="<?php echo esc_attr( $rule['min_qty'] ); ?>" /></label><label><?php esc_html_e( 'Max qty', 'iws' ); ?> <input class="small-text" type="number" min="0" name="<?php echo $name; ?>[max_qty]" value="<?php echo esc_attr( $rule['max_qty'] ); ?>" /></label><label><?php esc_html_e( 'Min subtotal', 'iws' ); ?> <input class="small-text" type="number" step="0.01" min="0" name="<?php echo $name; ?>[min_subtotal]" value="<?php echo esc_attr( $rule['min_subtotal'] ); ?>" /></label></div></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Schedule', 'iws' ); ?></th><td><div class="iws-rule-row-split"><label><?php esc_html_e( 'From', 'iws' ); ?> <input type="date" name="<?php echo $name; ?>[from_date]" value="<?php echo esc_attr( $rule['from_date'] ); ?>" /></label><label><?php esc_html_e( 'To', 'iws' ); ?> <input type="date" name="<?php echo $name; ?>[to_date]" value="<?php echo esc_attr( $rule['to_date'] ); ?>" /></label><label><input type="checkbox" name="<?php echo $name; ?>[exclude_sale]" value="1" <?php checked( $rule['exclude_sale'], 'yes' ); ?> /> <?php esc_html_e( 'Exclude sale items', 'iws' ); ?></label><label><input type="checkbox" name="<?php echo $name; ?>[show_badge]" value="1" <?php checked( $rule['show_badge'], 'yes' ); ?> /> <?php esc_html_e( 'Show discount on product/cart/checkout', 'iws' ); ?></label></div></td></tr>
			<tr><th scope="row"><label><?php esc_html_e( 'Priority', 'iws' ); ?></label></th><td><input class="small-text" type="number" name="<?php echo $name; ?>[priority]" value="<?php echo esc_attr( $rule['priority'] ); ?>" min="0" /> <p class="description"><?php esc_html_e( 'Lower numbers run first.', 'iws' ); ?></p></td></tr>
			</tbody></table><p class="iws-rule-actions"><button type="button" class="button button-secondary iws-remove-rule"><?php esc_html_e( 'Remove rule', 'iws' ); ?></button></p></div></div>
			<?php
		}

		private static function ids_array( $csv ) { return array_filter( array_map( 'absint', explode( ',', (string) $csv ) ) ); }

		private static function product_select( $name, $csv ) {
			$ids = self::ids_array( $csv );
			echo '<select class="wc-product-search iws-multi-choice" multiple="multiple" name="' . esc_attr( $name ) . '[]" data-placeholder="' . esc_attr__( 'Search for a product...', 'woocommerce' ) . '" data-action="woocommerce_json_search_products_and_variations">';
			foreach ( $ids as $id ) { $product = wc_get_product( $id ); if ( $product ) { echo '<option value="' . esc_attr( $id ) . '" selected="selected">' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>'; } }
			echo '</select>';
		}

		private static function category_select( $name, $csv ) {
			$selected = self::ids_array( $csv );
			wp_dropdown_categories( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'name' => $name . '[]', 'id' => '', 'class' => 'wc-enhanced-select iws-multi-choice', 'hierarchical' => true, 'multiple' => true, 'selected' => $selected ) );
		}

		private static function customer_select( $name, $csv ) {
			$ids = self::ids_array( $csv );
			echo '<select class="wc-customer-search iws-multi-choice" multiple="multiple" name="' . esc_attr( $name ) . '[]" data-placeholder="' . esc_attr__( 'Search for a customer...', 'woocommerce' ) . '" data-allow_clear="true">';
			foreach ( $ids as $id ) { $user = get_userdata( $id ); if ( $user ) { echo '<option value="' . esc_attr( $id ) . '" selected="selected">' . esc_html( $user->display_name . ' (#' . $id . ' - ' . $user->user_email . ')' ) . '</option>'; } }
			echo '</select>';
		}

		private static function level_select( $name, $csv, $levels ) {
			$selected = array_filter( array_map( 'sanitize_key', explode( ',', (string) $csv ) ) );
			echo '<select class="wc-enhanced-select iws-multi-choice" multiple="multiple" name="' . esc_attr( $name ) . '[]" data-placeholder="' . esc_attr__( 'Choose levels', 'iws' ) . '">';
			foreach ( array( 'silver', 'gold', 'vip' ) as $key ) { echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selected, true ), true, false ) . '>' . esc_html( $levels[ $key ]['label'] . ' (' . $key . ')' ) . '</option>'; }
			echo '</select>';
		}

		public static function user_profile_field( $user ) {
			if ( ! current_user_can( 'edit_user', $user->ID ) || ! current_user_can( 'manage_woocommerce' ) ) { return; }
			$settings = self::get_settings();
			$current = get_user_meta( $user->ID, self::USER_LEVEL_META, true );
			?><h2><?php esc_html_e( 'WooCommerce discount level', 'iws' ); ?></h2><table class="form-table"><tr><th><label for="iws_discount_level"><?php esc_html_e( 'Discount level', 'iws' ); ?></label></th><td><select name="iws_discount_level" id="iws_discount_level"><option value=""><?php esc_html_e( 'No level', 'iws' ); ?></option><?php foreach ( array( 'silver', 'gold', 'vip' ) as $key ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $settings['levels'][ $key ]['label'] ); ?></option><?php endforeach; ?></select></td></tr></table><?php
		}

		public static function save_user_profile_field( $user_id ) {
			if ( ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( 'manage_woocommerce' ) ) { return; }
			$level = sanitize_key( $_POST['iws_discount_level'] ?? '' );
			if ( in_array( $level, array( 'silver', 'gold', 'vip' ), true ) ) { update_user_meta( $user_id, self::USER_LEVEL_META, $level ); } else { delete_user_meta( $user_id, self::USER_LEVEL_META ); }
		}

		public static function runtime_product_price( $price, $product ) { return $price; }

		public static function apply_product_discounts( $cart ) {
			if ( is_admin() && ! wp_doing_ajax() ) { return; }
			if ( empty( $cart ) || 'yes' !== self::get_settings()['enabled'] ) { return; }
			foreach ( $cart->get_cart() as $cart_item_key => &$cart_item ) {
				if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) { continue; }
				$product = $cart_item['data'];
				$active_price = (float) $product->get_price( 'edit' );
				if ( $active_price <= 0 && $product->is_on_sale() && '' !== $product->get_sale_price( 'edit' ) ) {
					$active_price = (float) $product->get_sale_price( 'edit' );
				}
				if ( $active_price <= 0 ) {
					$active_price = (float) $product->get_regular_price( 'edit' );
				}

				$regular_price = (float) $product->get_regular_price( 'edit' );
				$cart_item['iws_original_price'] = $regular_price > $active_price ? $regular_price : $active_price;

				$new_price = self::discounted_price_for_product( $product, $active_price, (int) ( $cart_item['quantity'] ?? 1 ), $cart );
				$product->set_price( max( 0, wc_format_decimal( $new_price ) ) );
			}
			unset( $cart_item );
		}

		public static function apply_cart_discounts( $cart ) {
			if ( is_admin() && ! wp_doing_ajax() ) { return; }
			$settings = self::get_settings();
			if ( empty( $cart ) || 'yes' !== $settings['enabled'] ) { return; }
			$mode = $settings['conflict_mode'] ?? 'stack';
			$matches = array();

			foreach ( self::get_rules() as $rule ) {
				if ( 'cart' !== ( $rule['target'] ?? '' ) || ! self::rule_matches_cart( $rule, $cart ) ) { continue; }
				$base_amount = self::cart_rule_base_amount( $rule, $cart );
				if ( $base_amount <= 0 ) { continue; }
				$discount = 'percentage' === ( $rule['discount'] ?? 'percentage' ) ? ( $base_amount * (float) $rule['value'] / 100 ) : (float) $rule['value'];
				$discount = min( max( 0, (float) $discount ), $base_amount );
				if ( $discount <= 0 ) { continue; }
				$matches[] = array( 'rule' => $rule, 'discount' => $discount, 'base' => $base_amount );
				if ( 'first' === $mode ) { break; }
			}

			if ( 'best' === $mode && $matches ) {
				usort( $matches, function( $a, $b ) { return $b['discount'] <=> $a['discount']; } );
				$matches = array( $matches[0] );
			}

			foreach ( $matches as $match ) {
				$title = $match['rule']['title'];
				if ( 'yes' === ( $settings['debug'] ?? 'no' ) && current_user_can( 'manage_woocommerce' ) ) {
					$title .= ' (' . __( 'discount rule', 'iws' ) . ')';
				}
				$cart->add_fee( $title, -1 * (float) $match['discount'], false );
			}
		}

		public static function price_html( $price_html, $product ) {
			if ( is_admin() || 'yes' !== self::get_settings()['enabled'] || ! is_a( $product, 'WC_Product' ) ) { return $price_html; }
			$base = (float) $product->get_price( 'edit' );
			if ( $base <= 0 ) { return $price_html; }
			$new = self::discounted_price_for_product( $product, $base, 1, null );
			if ( $new < $base ) { return wc_format_sale_price( wc_price( $base ), wc_price( $new ) ) . $product->get_price_suffix(); }
			return $price_html;
		}

		public static function cart_item_price_html( $price_html, $cart_item, $cart_item_key ) {
			return self::cart_line_discount_html( $price_html, $cart_item, false );
		}

		public static function cart_item_subtotal_html( $subtotal_html, $cart_item, $cart_item_key ) {
			return self::cart_line_discount_html( $subtotal_html, $cart_item, true );
		}

		private static function cart_line_discount_html( $html, $cart_item, $is_subtotal ) {
			if ( is_admin() || 'yes' !== self::get_settings()['enabled'] || empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) { return $html; }
			$discount = self::cart_item_discount_data( $cart_item );
			if ( ! $discount ) { return $html; }
			$from = $is_subtotal ? $discount['original'] * $discount['qty'] : $discount['original'];
			$to   = $is_subtotal ? $discount['current'] * $discount['qty'] : $discount['current'];
			return wc_format_sale_price( wc_price( $from ), wc_price( $to ) ) . '<br><small class="iws-discount-note">' . $discount['note'] . '</small>';
		}

		public static function checkout_item_discount_html( $quantity_html, $cart_item, $cart_item_key ) {
			if ( ! is_checkout() || is_order_received_page() ) { return $quantity_html; }
			$discount = self::cart_item_discount_data( $cart_item );
			if ( ! $discount ) { return $quantity_html; }
			$from = $discount['original'] * $discount['qty'];
			$to   = $discount['current'] * $discount['qty'];
			return $quantity_html . '<div class="iws-checkout-discount-info"><small>' . wc_format_sale_price( wc_price( $from ), wc_price( $to ) ) . '<br><span class="iws-discount-note">' . $discount['note'] . '</span></small></div>';
		}

		private static function cart_item_discount_data( $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) { return false; }
			$product  = $cart_item['data'];
			$qty      = max( 1, (int) ( $cart_item['quantity'] ?? 1 ) );
			$current  = (float) $product->get_price();
			$original = isset( $cart_item['iws_original_price'] ) ? (float) $cart_item['iws_original_price'] : 0;
			$regular  = (float) $product->get_regular_price( 'edit' );

			if ( $regular > $current ) {
				$original = max( $original, $regular );
			}
			if ( $original <= 0 ) {
				$original = (float) $product->get_price( 'edit' );
			}
			if ( $original <= 0 || $current <= 0 || $current >= $original ) { return false; }

			$cart = function_exists( 'WC' ) && WC() ? WC()->cart : null;
			if ( ! self::product_has_visible_discount( $product, $qty, $cart ) ) { return false; }

			return array(
				'original' => $original,
				'current'  => $current,
				'qty'      => $qty,
				'note'     => self::discount_note_html( $product, $qty, $cart, $original, $current ),
			);
		}

		private static function discounted_price_for_product( $product, $base, $qty, $cart ) {
			$result = self::calculate_product_discount( $product, $base, $qty, $cart );
			return $result['price'];
		}

		private static function calculate_product_discount( $product, $base, $qty, $cart ) {
			$new = $base;
			$matches = array();
			$settings = self::get_settings();
			$mode = $settings['conflict_mode'] ?? 'stack';

			foreach ( self::get_rules() as $rule ) {
				if ( 'product' !== ( $rule['target'] ?? 'product' ) || ! self::rule_matches_product( $rule, $product, $qty, $cart ) ) { continue; }
				$candidate = self::discounted_amount( 'stack' === $mode ? $new : $base, $rule );
				$matches[] = array( 'rule' => $rule, 'price' => max( 0, (float) $candidate ) );
				if ( 'stack' === $mode ) { $new = max( 0, (float) $candidate ); }
				if ( 'first' === $mode ) { $new = max( 0, (float) $candidate ); break; }
			}

			if ( 'best' === $mode && $matches ) {
				$prices = array();
				foreach ( $matches as $match ) { $prices[] = (float) $match['price']; }
				$new = max( 0, min( $prices ) );
			}

			$before_level = $new;
			$new = self::apply_current_user_level_discount( $new );
			if ( $new < $before_level ) {
				$matches[] = array( 'rule' => array( 'title' => __( 'Customer level discount', 'iws' ), 'show_badge' => 'yes' ), 'price' => $new );
			}

			return array( 'price' => max( 0, (float) $new ), 'rules' => $matches );
		}

		private static function apply_current_user_level_discount( $amount ) {
			$user_id = get_current_user_id();
			if ( ! $user_id ) { return $amount; }
			$level_key = get_user_meta( $user_id, self::USER_LEVEL_META, true );
			if ( ! in_array( $level_key, array( 'silver', 'gold', 'vip' ), true ) ) { return $amount; }
			$settings = self::get_settings();
			$level = $settings['levels'][ $level_key ] ?? array();
			$value = (float) ( $level['discount'] ?? 0 );
			if ( $value <= 0 ) { return $amount; }
			$rule = array( 'discount' => $level['type'] ?? 'percentage', 'value' => $value );
			return self::discounted_amount( $amount, $rule );
		}
		private static function cart_rule_base_amount( $rule, $cart ) {
			if ( ! $cart ) { return 0; }
			if ( 'all' === ( $rule['apply_to'] ?? 'all' ) ) { return (float) $cart->get_subtotal(); }
			$amount = 0;
			foreach ( $cart->get_cart() as $cart_item ) {
				if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) { continue; }
				if ( self::rule_matches_cart_item( $rule, $cart_item, $cart ) ) {
					$line = isset( $cart_item['line_subtotal'] ) ? (float) $cart_item['line_subtotal'] : ( (float) $cart_item['data']->get_price( 'edit' ) * (int) ( $cart_item['quantity'] ?? 1 ) );
					$amount += $line;
				}
			}
			return $amount;
		}

		private static function discount_note_html( $product, $qty, $cart, $original = null, $current = null ) {
			$settings = self::get_settings();
			$original = null === $original ? (float) $product->get_regular_price() : (float) $original;
			$current  = null === $current ? (float) $product->get_price() : (float) $current;
			$saved    = max( 0, ( $original - $current ) * max( 1, (int) $qty ) );
			$save_text = $saved > 0 ? sprintf( __( 'Save %s', 'iws' ), wp_strip_all_tags( wc_price( $saved ) ) ) : __( 'Discount applied', 'iws' );

			if ( 'yes' !== ( $settings['debug'] ?? 'no' ) || ! current_user_can( 'manage_woocommerce' ) ) {
				return esc_html( $save_text );
			}
			$base = (float) $product->get_regular_price();
			if ( $base <= 0 ) { $base = (float) $product->get_price( 'edit' ); }
			$result = self::calculate_product_discount( $product, $base, $qty, $cart );
			$names = array();
			foreach ( $result['rules'] as $match ) {
				$name = isset( $match['rule']['title'] ) ? sanitize_text_field( $match['rule']['title'] ) : '';
				if ( $name ) { $names[] = $name; }
			}
			return $names ? esc_html( sprintf( __( '%1$s - %2$s', 'iws' ), $save_text, implode( ', ', array_unique( $names ) ) ) ) : esc_html( $save_text );
		}

		public static function store_order_item_discount_meta( $item, $cart_item_key, $values, $order ) {
			if ( empty( $values['data'] ) || ! is_a( $values['data'], 'WC_Product' ) ) { return; }
			$product = $values['data'];
			$original = isset( $values['iws_original_price'] ) ? (float) $values['iws_original_price'] : (float) $product->get_regular_price();
			$regular = (float) $product->get_regular_price( 'edit' );
			$current = (float) $product->get_price();
			if ( $regular > $current ) { $original = max( $original, $regular ); }
			if ( $original <= 0 || $current >= $original ) { return; }
			$saved = max( 0, ( $original - $current ) * max( 1, (int) ( $values['quantity'] ?? 1 ) ) );
			$item->add_meta_data( __( 'Discount', 'iws' ), sprintf( __( 'Original %1$s, discounted %2$s, saved %3$s', 'iws' ), wp_strip_all_tags( wc_price( $original ) ), wp_strip_all_tags( wc_price( $current ) ), wp_strip_all_tags( wc_price( $saved ) ) ), true );
		}

		private static function product_has_visible_discount( $product, $qty, $cart ) {
			$current = (float) $product->get_price();
			$regular = (float) $product->get_regular_price( 'edit' );
			if ( $regular > 0 && $current > 0 && $regular > $current ) { return true; }

			foreach ( self::get_rules() as $rule ) {
				if ( 'yes' !== ( $rule['show_badge'] ?? 'yes' ) ) { continue; }
				if ( 'product' === ( $rule['target'] ?? 'product' ) && self::rule_matches_product( $rule, $product, $qty, $cart ) ) { return true; }
			}
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$level_key = get_user_meta( $user_id, self::USER_LEVEL_META, true );
				$settings = self::get_settings();
				if ( isset( $settings['levels'][ $level_key ] ) && (float) ( $settings['levels'][ $level_key ]['discount'] ?? 0 ) > 0 ) { return true; }
			}
			return false;
		}

		private static function rule_matches_cart_item( $rule, $cart_item, $cart ) { return self::rule_matches_product( $rule, $cart_item['data'], (int) ( $cart_item['quantity'] ?? 1 ), $cart ); }
		private static function rule_matches_cart( $rule, $cart ) {
			if ( ! self::rule_is_active( $rule ) || ! self::customer_matches( $rule ) ) { return false; }
			$qty = $cart ? (int) $cart->get_cart_contents_count() : 0;
			if ( ! self::qty_matches( $rule, $qty ) ) { return false; }
			$min_subtotal = (float) ( $rule['min_subtotal'] ?? 0 );
			if ( $min_subtotal > 0 && $cart && (float) $cart->get_subtotal() < $min_subtotal ) { return false; }
			return true;
		}

		private static function rule_matches_product( $rule, $product, $qty, $cart ) {
			if ( ! self::rule_is_active( $rule ) || ! self::qty_matches( $rule, $qty ) || ! self::customer_matches( $rule ) ) { return false; }
			$min_subtotal = (float) ( $rule['min_subtotal'] ?? 0 );
			if ( $min_subtotal > 0 && $cart && (float) $cart->get_subtotal() < $min_subtotal ) { return false; }
			if ( 'yes' === ( $rule['exclude_sale'] ?? 'no' ) && $product->is_on_sale() ) { return false; }
			$apply_to = $rule['apply_to'] ?? 'all';
			if ( 'all' === $apply_to ) { return true; }
			$product_id = $product->get_id(); $parent_id = $product->get_parent_id();
			if ( 'products' === $apply_to ) { $ids = self::ids_array( $rule['product_ids'] ?? '' ); return in_array( $product_id, $ids, true ) || ( $parent_id && in_array( $parent_id, $ids, true ) ); }
			if ( 'categories' === $apply_to ) { $ids = self::ids_array( $rule['category_ids'] ?? '' ); $check_id = $parent_id ? $parent_id : $product_id; return ! empty( $ids ) && has_term( $ids, 'product_cat', $check_id ); }
			return false;
		}

		private static function customer_matches( $rule ) {
			$type = $rule['customer_type'] ?? 'all';
			if ( 'all' === $type ) { return true; }
			$user_id = get_current_user_id();
			if ( ! $user_id ) { return false; }
			if ( 'users' === $type ) { return in_array( $user_id, self::ids_array( $rule['user_ids'] ?? '' ), true ); }
			if ( 'levels' === $type ) { $level = get_user_meta( $user_id, self::USER_LEVEL_META, true ); $levels = array_filter( array_map( 'sanitize_key', explode( ',', (string) ( $rule['user_levels'] ?? '' ) ) ) ); return $level && in_array( $level, $levels, true ); }
			return false;
		}

		private static function rule_is_active( $rule ) {
			if ( 'yes' !== ( $rule['enabled'] ?? 'no' ) ) { return false; }
			$today = current_time( 'Y-m-d' );
			if ( ! empty( $rule['from_date'] ) && $today < $rule['from_date'] ) { return false; }
			if ( ! empty( $rule['to_date'] ) && $today > $rule['to_date'] ) { return false; }
			return true;
		}
		private static function qty_matches( $rule, $qty ) { $min = (int) ( $rule['min_qty'] ?? 0 ); $max = (int) ( $rule['max_qty'] ?? 0 ); if ( $min > 0 && $qty < $min ) { return false; } if ( $max > 0 && $qty > $max ) { return false; } return true; }
		private static function discounted_amount( $amount, $rule ) { $value = (float) ( $rule['value'] ?? 0 ); if ( $value <= 0 ) { return $amount; } if ( 'percentage' === ( $rule['discount'] ?? 'percentage' ) ) { return $amount - ( $amount * min( 100, $value ) / 100 ); } return $amount - $value; }
	}
	IWS_Woo_Discount_Rules::init();
}
