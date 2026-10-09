<?php
/**
 * IWS B2B / Wholesale customer tools.
 *
 * Optional lightweight B2B layer for WooCommerce. It is not intended to replace
 * a full enterprise plugin, but it gives this theme controlled wholesale roles,
 * registration, approval, B2B dashboard styling, B2B prices, quantity tiers,
 * custom information tables, and minimum order rules.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/b2b-commerce.php';

add_action( 'init', 'iws_b2b_register_roles' );
add_action( 'init', 'iws_b2b_add_account_endpoint' );
add_action( 'init', 'iws_b2b_register_shortcodes' );
add_filter( 'query_vars', 'iws_b2b_query_vars' );
add_filter( 'the_content', 'iws_b2b_portal_page_content', 20 );
add_filter( 'woocommerce_account_menu_items', 'iws_b2b_account_menu_items', 40 );
add_action( 'woocommerce_account_b2b-dashboard_endpoint', 'iws_b2b_dashboard_endpoint' );
add_action( 'woocommerce_before_calculate_totals', 'iws_b2b_apply_cart_discount', 20 );
add_action( 'woocommerce_check_cart_items', 'iws_b2b_validate_order_rules' );
add_action( 'woocommerce_before_cart', 'iws_b2b_cart_notice' );
add_action( 'woocommerce_before_checkout_form', 'iws_b2b_cart_notice' );
add_action( 'admin_menu', 'iws_b2b_admin_menu', 70 );
add_action( 'admin_init', 'iws_b2b_register_settings' );
add_action( 'admin_post_iws_b2b_approve_user', 'iws_b2b_admin_approve_user' );
add_action( 'admin_post_iws_b2b_reject_user', 'iws_b2b_admin_reject_user' );
add_action( 'wp_enqueue_scripts', 'iws_b2b_frontend_styles', 99 );
add_action( 'wp_loaded', 'iws_b2b_handle_registration' );
add_action( 'woocommerce_single_product_summary', 'iws_b2b_render_price_tools', 21 );
add_filter( 'woocommerce_get_price_html', 'iws_b2b_price_html', 20, 2 );
add_filter( 'woocommerce_product_get_price', 'iws_b2b_filter_product_price', 20, 2 );
add_filter( 'woocommerce_product_variation_get_price', 'iws_b2b_filter_product_price', 20, 2 );
add_filter( 'woocommerce_variation_prices_price', 'iws_b2b_filter_variation_price', 20, 3 );
add_filter( 'woocommerce_checkout_fields', 'iws_b2b_checkout_fields' );
add_action( 'woocommerce_checkout_create_order', 'iws_b2b_save_checkout_fields', 20, 2 );
add_filter( 'woocommerce_available_payment_gateways', 'iws_b2b_filter_payment_gateways' );
add_filter( 'woocommerce_login_redirect', 'iws_b2b_login_redirect', 10, 2 );

function iws_b2b_defaults() {
	return array(
		'enabled'                    => 'no',
		'enable_dashboard'           => 'yes',
		'enable_registration'        => 'yes',
		'registration_approval'      => 'manual',
		'auto_assign_wholesale_role' => 'yes',
		'enable_min_products'        => 'yes',
		'min_products'               => 10,
		'enable_min_order_value'     => 'no',
		'min_order_value'            => 0,
		'enable_discount'            => 'no',
		'discount_type'              => 'percent',
		'discount_percent'           => 0,
		'discount_amount'            => 0,
		'tier_rules'                 => "25|5\n50|8\n100|12",
		'enable_tiered_pricing'      => 'yes',
		'show_retail_prices'         => 'yes',
		'private_store_mode'         => 'no',
		'hide_prices_logged_out'     => 'no',
		'enable_custom_info_table'   => 'yes',
		'custom_info_table'          => "MSRP|Shown as retail price\nShipping|Calculated at checkout\nLead time|Contact us for large orders",
		'enable_po_gateway'          => 'yes',
		'show_cart_notice'           => 'yes',
		'dashboard_title'            => __( 'B2B Dashboard', 'wp-theme-woo-support' ),
		'dashboard_intro'            => __( 'Welcome to your business account. Review wholesale prices, order rules, and account information from one place.', 'wp-theme-woo-support' ),
		'registration_title'         => __( 'Apply for a trade account', 'wp-theme-woo-support' ),
		'registration_intro'         => __( 'Complete the form below and our team will review your business account request.', 'wp-theme-woo-support' ),
		'login_title'                => __( 'Trade account login', 'wp-theme-woo-support' ),
		'min_products_message'       => __( 'Wholesale orders require at least {min} products in the cart. You currently have {qty}.', 'wp-theme-woo-support' ),
		'min_value_message'          => __( 'Wholesale orders require a minimum order value of {min}. Your current total is {total}.', 'wp-theme-woo-support' ),
		'discount_message'           => __( 'Your wholesale discount is active.', 'wp-theme-woo-support' ),
		'redirect_after_login'       => 'yes',
		'registration_fields'        => "company|Company name|text|yes|yes|yes|\nvat_number|VAT number|text|no|yes|yes|\nbusiness_license|Business license / certificate|file|no|no|no|\nphone|Business phone|tel|yes|yes|yes|\nbusiness_type|Business type|select|no|no|no|Distributor,Reseller,Installer,End user",
	);
}

function iws_b2b_get_settings() {
	$saved = get_option( 'iws_b2b_settings', array() );
	$saved = is_array($saved) ? $saved : [];
    // Preserve old private-store intent; merchants explicitly choose mixed retail/B2B.
    if (!isset($saved['store_audience']) && ('yes' === ($saved['private_store_mode'] ?? 'no') || 'yes' === ($saved['hide_prices_logged_out'] ?? 'no'))) $saved['store_audience'] = 'registered';
    return wp_parse_args($saved, array_merge(iws_b2b_defaults(), iws_b2b_commerce_defaults()));
}

function iws_b2b_is_enabled() {
	$settings = iws_b2b_get_settings();
	return 'yes' === $settings['enabled'];
}

function iws_b2b_register_roles() {
	if ( ! get_role( 'wholesale_customer' ) ) {
		add_role( 'wholesale_customer', __( 'Wholesale Customer', 'wp-theme-woo-support' ), array( 'read' => true, 'customer' => true ) );
	}
	if ( ! get_role( 'pending_wholesale_customer' ) ) {
		add_role( 'pending_wholesale_customer', __( 'Pending Wholesale Customer', 'wp-theme-woo-support' ), array( 'read' => true ) );
	}
}

function iws_b2b_current_user_is_wholesale( $user_id = 0 ) {
	$user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();
	return iws_b2b_user_is_trade($user);
}

function iws_b2b_current_user_is_pending( $user_id = 0 ) {
	$user = $user_id ? get_user_by( 'id', $user_id ) : wp_get_current_user();
	return $user && $user->exists() && in_array( 'pending_wholesale_customer', (array) $user->roles, true );
}

function iws_b2b_add_account_endpoint() {
	add_rewrite_endpoint( 'b2b-dashboard', EP_ROOT | EP_PAGES );
}

function iws_b2b_query_vars( $vars ) {
	$vars[] = 'b2b-dashboard';
	return $vars;
}

function iws_b2b_register_shortcodes() {
	add_shortcode( 'iws_b2b_registration', 'iws_b2b_registration_shortcode' );
	add_shortcode( 'iws_b2b_dashboard', 'iws_b2b_dashboard_shortcode' );
	add_shortcode( 'iws_b2b_home_promo', 'iws_b2b_home_promo_shortcode' );
}

/**
 * Public B2B portal URL helper for themes and blocks.
 */
function iws_b2b_portal_url() {
	$page = get_page_by_path( 'b2b-portal' );
	if ( $page && 'trash' !== get_post_status( $page ) ) {
		return get_permalink( $page );
	}
	if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
		return wc_get_account_endpoint_url( 'b2b-dashboard' );
	}
	return home_url( '/b2b-portal/' );
}

/**
 * Reusable homepage/business-account promotion. Store themes can render this
 * without duplicating B2B account state or pricing-rule knowledge.
 */
function iws_b2b_home_promo_html( $args = array() ) {
	if ( ! iws_b2b_is_enabled() ) {
		return '';
	}

	$settings = iws_b2b_get_settings();
	$args = wp_parse_args( $args, array(
		'kicker' => iws_b2b_frontend_text( 'B2B & TRADE', 'B2B UN TIRDZNIECĪBAI' ),
		'title'  => iws_b2b_frontend_text( 'Business pricing for trade and project orders', 'Biznesa cenas profesionāļiem un projektu pasūtījumiem' ),
		'copy'   => iws_b2b_frontend_text( 'Apply for a trade account to access business pricing, quantity rules, account terms and a dedicated B2B portal.', 'Piesakies uzņēmuma kontam, lai saņemtu biznesa cenas, apjoma noteikumus, norēķinu nosacījumus un atsevišķu B2B portālu.' ),
	) );

	$portal_url = iws_b2b_portal_url();
	$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	if ( iws_b2b_current_user_is_wholesale() ) {
		$cta = iws_b2b_frontend_text( 'Open B2B dashboard', 'Atvērt B2B paneli' );
	} elseif ( iws_b2b_current_user_is_pending() ) {
		$cta = iws_b2b_frontend_text( 'View trade application', 'Skatīt B2B pieteikumu' );
	} else {
		$cta = 'yes' === $settings['enable_registration']
			? iws_b2b_frontend_text( 'Apply for a trade account', 'Pieteikties uzņēmuma kontam' )
			: iws_b2b_frontend_text( 'B2B account login', 'B2B konta pieslēgšanās' );
	}

	$discount = iws_b2b_get_base_discount();
	$discount_text = $discount > 0
		? sprintf( iws_b2b_frontend_text( 'From %s%% account discount', 'Konta atlaide no %s%%' ), wc_format_decimal( $discount, 0 ) )
		: iws_b2b_frontend_text( 'Customer-specific trade prices', 'Individuālas biznesa cenas' );
	$minimum_text = ( 'yes' === $settings['enable_min_products'] && (int) $settings['min_products'] > 0 )
		? sprintf( iws_b2b_frontend_text( 'Order rules from %d items', 'Pasūtījuma noteikumi no %d precēm' ), (int) $settings['min_products'] )
		: iws_b2b_frontend_text( 'Volume and case-pack ordering', 'Apjoma un iepakojumu pasūtījumi' );

	ob_start();
	?>
	<section class="iws-b2b-home-promo" aria-label="<?php echo esc_attr( iws_b2b_frontend_text( 'Business accounts', 'Uzņēmumu konti' ) ); ?>">
		<div class="iws-b2b-home-promo__copy">
			<span class="iws-b2b-home-promo__kicker"><?php echo esc_html( $args['kicker'] ); ?></span>
			<h2><?php echo esc_html( $args['title'] ); ?></h2>
			<p><?php echo esc_html( $args['copy'] ); ?></p>
			<div class="iws-b2b-home-promo__actions">
				<a class="iws-b2b-home-promo__primary" href="<?php echo esc_url( $portal_url ); ?>"><?php echo esc_html( $cta ); ?></a>
				<a class="iws-b2b-home-promo__secondary" href="<?php echo esc_url( $shop_url ); ?>"><?php echo esc_html( iws_b2b_frontend_text( 'Browse products', 'Skatīt preces' ) ); ?></a>
			</div>
		</div>
		<div class="iws-b2b-home-promo__benefits">
			<div><strong><?php echo esc_html( $discount_text ); ?></strong><span><?php echo esc_html( iws_b2b_frontend_text( 'Trade pricing', 'Biznesa cenas' ) ); ?></span></div>
			<div><strong><?php echo esc_html( $minimum_text ); ?></strong><span><?php echo esc_html( iws_b2b_frontend_text( 'Volume ordering', 'Apjoma pasūtījumi' ) ); ?></span></div>
			<div><strong><?php echo esc_html( iws_b2b_frontend_text( 'Portal, terms & order history', 'Portāls, nosacījumi un pasūtījumu vēsture' ) ); ?></strong><span><?php echo esc_html( iws_b2b_frontend_text( 'Business account', 'Uzņēmuma konts' ) ); ?></span></div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

function iws_b2b_home_promo_shortcode( $atts = array() ) {
	$atts = shortcode_atts( array( 'title' => '', 'copy' => '' ), $atts, 'iws_b2b_home_promo' );
	$args = array();
	if ( trim( (string) $atts['title'] ) !== '' ) { $args['title'] = sanitize_text_field( $atts['title'] ); }
	if ( trim( (string) $atts['copy'] ) !== '' ) { $args['copy'] = sanitize_text_field( $atts['copy'] ); }
	return iws_b2b_home_promo_html( $args );
}

function iws_b2b_portal_page_content( $content ) {
	if ( is_admin() || ! is_singular() || ! iws_b2b_is_enabled() ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post || 'b2b-dashboard' !== $post->post_name ) {
		return $content;
	}

	return iws_b2b_render_portal();
}

function iws_b2b_dashboard_shortcode() {
	if ( ! iws_b2b_is_enabled() ) {
		return '';
	}
	return iws_b2b_render_portal();
}

function iws_b2b_registration_shortcode() {
	if ( ! iws_b2b_is_enabled() ) {
		return '';
	}
	return iws_b2b_render_registration_form();
}


/**
 * Let the normal WooCommerce My Account registration form optionally create a
 * pending/approved trade account. This keeps personal and B2B registration in
 * one familiar screen while retaining the dedicated B2B application form.
 */
function iws_b2b_frontend_language_slug() {
    if (function_exists('wpbbshop_v433_current_language')) {
        $context = wpbbshop_v433_current_language();
        if (in_array($context, ['lv','en'], true)) return $context;
    }

	/* Prefer the language attached to the queried post/page. This is more
	 * reliable than determine_locale() on Polylang sites where the WordPress
	 * admin/user locale can stay English while the public page is Latvian. */
	$queried_id = function_exists( 'get_queried_object_id' ) ? absint( get_queried_object_id() ) : 0;
	if ( $queried_id && function_exists( 'pll_get_post_language' ) ) {
		$lang = sanitize_key( (string) pll_get_post_language( $queried_id, 'slug' ) );
		if ( in_array( $lang, array( 'lv', 'en' ), true ) ) return $lang;
	}

	$query_lang = sanitize_key( (string) get_query_var( 'lang', '' ) );
	if ( in_array( $query_lang, array( 'lv', 'en' ), true ) ) return $query_lang;

	global $polylang;
	if ( isset( $polylang->curlang->slug ) ) {
		$lang = sanitize_key( (string) $polylang->curlang->slug );
		if ( in_array( $lang, array( 'lv', 'en' ), true ) ) return $lang;
	}

	/* URL fallback is intentionally checked before pll_current_language().
	 * Some cached/homepage render paths initialise Polylang late, while the
	 * canonical /lv/ path is already unambiguous. */
	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path = (string) wp_parse_url( $request_path, PHP_URL_PATH );
	if ( preg_match( '#(?:^|/)lv(?:/|$)#i', trim( $request_path, '/' ) ) ) return 'lv';
	if ( preg_match( '#(?:^|/)en(?:/|$)#i', trim( $request_path, '/' ) ) ) return 'en';

	if ( function_exists( 'pll_current_language' ) ) {
		$lang = sanitize_key( (string) pll_current_language( 'slug' ) );
		if ( in_array( $lang, array( 'lv', 'en' ), true ) ) return $lang;
	}

	if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		$lang = sanitize_key( (string) ICL_LANGUAGE_CODE );
		if ( in_array( $lang, array( 'lv', 'en' ), true ) ) return $lang;
	}

	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	$lang = 0 === strpos( strtolower( (string) $locale ), 'lv' ) ? 'lv' : 'en';
	return sanitize_key( (string) apply_filters( 'iws_b2b_frontend_language_slug', $lang ) );
}

function iws_b2b_frontend_is_lv() {
	return 'lv' === iws_b2b_frontend_language_slug();
}

function iws_b2b_frontend_text( $en, $lv ) {
	return iws_b2b_frontend_is_lv() ? $lv : $en;
}

function iws_b2b_account_registration_available() {
	$settings = iws_b2b_get_settings();
	return iws_b2b_is_enabled() && 'yes' === $settings['enable_registration'];
}

function iws_b2b_standard_registration_requested() {
	return ! empty( $_POST['iws_b2b_account'] ) && 'business' === sanitize_key( wp_unslash( $_POST['iws_b2b_account'] ) );
}

function iws_b2b_account_registration_form_tag() {
	if ( iws_b2b_account_registration_available() ) {
		echo ' enctype="multipart/form-data"';
	}
}
add_action( 'woocommerce_register_form_tag', 'iws_b2b_account_registration_form_tag' );

function iws_b2b_standard_field_label( $key, $fallback ) {
	$labels = array(
		'company' => array( 'Company name', 'Uzņēmuma nosaukums' ),
		'vat_number' => array( 'VAT number', 'PVN numurs' ),
		'business_license' => array( 'Business licence / certificate', 'Uzņēmuma licence / sertifikāts' ),
		'phone' => array( 'Business phone', 'Uzņēmuma tālrunis' ),
		'business_type' => array( 'Business type', 'Uzņēmuma veids' ),
	);
	if ( isset( $labels[ $key ] ) ) {
		return iws_b2b_frontend_text( $labels[ $key ][0], $labels[ $key ][1] );
	}
	return $fallback;
}


function iws_b2b_standard_option_label( $option ) {
	if ( ! iws_b2b_frontend_is_lv() ) return $option;
	$map = array(
		'Distributor' => 'Izplatītājs',
		'Reseller' => 'Tālākpārdevējs',
		'Installer' => 'Montāžas uzņēmums',
		'End user' => 'Gala lietotājs',
	);
	return $map[ $option ] ?? $option;
}

function iws_b2b_account_registration_fields() {
	if ( ! iws_b2b_account_registration_available() ) return;
	$checked = iws_b2b_standard_registration_requested() || 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' );
	?>
	<div class="iws-b2b-account-choice">
		<label class="iws-b2b-account-choice__label" for="iws_b2b_account">
			<input type="checkbox" id="iws_b2b_account" name="iws_b2b_account" value="business" <?php checked( $checked ); ?>>
			<span><strong><?php echo esc_html( iws_b2b_frontend_text( 'Register as a B2B / trade customer', 'Reģistrēties kā B2B / uzņēmuma klientam' ) ); ?></strong><small><?php echo esc_html( iws_b2b_frontend_text( 'Request business pricing, volume terms and access to the trade portal.', 'Piesakies biznesa cenām, apjoma nosacījumiem un B2B portālam.' ) ); ?></small></span>
		</label>
		<div class="iws-b2b-account-fields" data-iws-b2b-account-fields <?php echo $checked ? '' : ' hidden'; ?>>
			<?php foreach ( iws_b2b_parse_custom_fields() as $field ) :
				if ( 'file' === $field['type'] ) continue;
				$key = 'iws_b2b_' . $field['key'];
				$value = isset( $_POST[ $key ] ) && ! is_array( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
				$label = iws_b2b_standard_field_label( $field['key'], $field['label'] );
			?>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide iws-b2b-account-field">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?><?php if ( $field['required'] ) : ?> <span class="required">*</span><?php endif; ?></label>
				<?php if ( 'select' === $field['type'] ) : ?>
				<select class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"><option value=""><?php echo esc_html( iws_b2b_frontend_text( 'Select an option', 'Izvēlieties' ) ); ?></option><?php foreach ( $field['options'] as $option ) : ?><option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( iws_b2b_standard_option_label( $option ) ); ?></option><?php endforeach; ?></select>
				<?php elseif ( 'textarea' === $field['type'] ) : ?>
				<textarea class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php elseif ( 'checkbox' === $field['type'] ) : ?>
				<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="yes" <?php checked( $value, 'yes' ); ?>>
				<?php else : ?>
				<input type="<?php echo esc_attr( in_array( $field['type'], array( 'email', 'tel', 'number', 'date' ), true ) ? $field['type'] : 'text' ); ?>" class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php endif; ?>
			</p>
			<?php endforeach; ?>
			<p class="iws-b2b-account-note"><?php echo esc_html( iws_b2b_frontend_text( 'Business registrations may require approval before trade prices become active.', 'B2B reģistrācijai var būt nepieciešams apstiprinājums, pirms tiek aktivizētas biznesa cenas.' ) ); ?></p>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_register_form', 'iws_b2b_account_registration_fields', 18 );

function iws_b2b_account_registration_errors( $errors, $username, $email ) {
	if ( ! iws_b2b_standard_registration_requested() ) return $errors;
	if ( ! iws_b2b_account_registration_available() ) {
		$errors->add( 'iws_b2b_disabled', iws_b2b_frontend_text( 'Business registration is currently unavailable.', 'B2B reģistrācija pašlaik nav pieejama.' ) );
		return $errors;
	}
	foreach ( iws_b2b_parse_custom_fields() as $field ) {
		if ( ! $field['required'] || 'file' === $field['type'] ) continue;
		$key = 'iws_b2b_' . $field['key'];
		$value = isset( $_POST[ $key ] ) ? trim( (string) wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '' === $value ) {
			$errors->add( 'iws_b2b_' . $field['key'], sprintf( iws_b2b_frontend_text( '%s is required for a business account.', '%s ir obligāts B2B kontam.' ), iws_b2b_standard_field_label( $field['key'], $field['label'] ) ) );
		}
	}
	return $errors;
}
add_filter( 'woocommerce_registration_errors', 'iws_b2b_account_registration_errors', 20, 3 );

function iws_b2b_account_created_customer( $customer_id ) {
	if ( ! iws_b2b_standard_registration_requested() || ! iws_b2b_account_registration_available() ) return;
	$settings = iws_b2b_get_settings();
	$status = 'manual' === $settings['registration_approval'] ? 'pending' : 'approved';
	$role = ( 'approved' === $status && 'yes' === $settings['auto_assign_wholesale_role'] ) ? 'wholesale_customer' : 'pending_wholesale_customer';
	$user = new WP_User( $customer_id );
	$user->set_role( $role );
	update_user_meta( $customer_id, 'iws_b2b_status', $status );
	update_user_meta( $customer_id, 'iws_b2b_application_date', current_time( 'mysql' ) );
	foreach ( iws_b2b_parse_custom_fields() as $field ) {
		$key = 'iws_b2b_' . $field['key'];
		if ( 'file' === $field['type'] ) continue;
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		update_user_meta( $customer_id, $key, $value );
		if ( $field['billing'] && '' !== $value ) {
			$billing_key = 'company' === $field['key'] ? 'billing_company' : ( 'phone' === $field['key'] ? 'billing_phone' : '' );
			if ( $billing_key ) update_user_meta( $customer_id, $billing_key, $value );
		}
	}
	wc_add_notice( 'approved' === $status ? iws_b2b_frontend_text( 'Your business account is active.', 'Jūsu B2B konts ir aktīvs.' ) : iws_b2b_frontend_text( 'Your business application was received and is waiting for approval.', 'Jūsu B2B pieteikums ir saņemts un gaida apstiprinājumu.' ), 'success' );
}
add_action( 'woocommerce_created_customer', 'iws_b2b_account_created_customer', 30 );

/* Woo normally hides registration when personal registration is disabled. For
 * a submitted B2B request only, let Woo process the registration securely. */
add_filter( 'option_woocommerce_enable_myaccount_registration', function( $value ) {
	if ( ! is_admin() && iws_b2b_standard_registration_requested() && iws_b2b_account_registration_available() ) return 'yes';
	return $value;
}, 30 );


/** Compact trade-account state for custom single-product templates. */
function iws_b2b_product_context_html( $product = null ) {
	if ( ! iws_b2b_is_enabled() ) return '';
	if ( ! $product instanceof WC_Product ) {
		global $product;
	}
	if ( ! $product instanceof WC_Product ) return '';

	$portal = iws_b2b_portal_url();
	$min_qty = absint( $product->get_meta( '_iws_b2b_min_qty', true ) );
	$case_pack = absint( $product->get_meta( '_iws_b2b_case_pack', true ) );
	$settings = iws_b2b_get_settings();
	$title = iws_b2b_frontend_text( 'Trade pricing available', 'Pieejamas B2B cenas' );
	$copy = iws_b2b_frontend_text( 'Apply for a business account to access trade pricing and volume terms.', 'Piesakies uzņēmuma kontam, lai saņemtu B2B cenas un apjoma nosacījumus.' );
	$cta = iws_b2b_frontend_text( 'Apply for B2B', 'Pieteikties B2B' );
	$class = 'is-guest';
	$facts = array();

	if ( iws_b2b_current_user_is_wholesale() ) {
		$title = iws_b2b_frontend_text( 'Your B2B pricing is active', 'Jūsu B2B cenas ir aktīvas' );
		$copy = iws_b2b_frontend_text( 'This product uses your approved trade pricing and order rules.', 'Šai precei tiek piemērotas jūsu apstiprinātās biznesa cenas un pasūtījuma nosacījumi.' );
		$cta = iws_b2b_frontend_text( 'Open B2B dashboard', 'Atvērt B2B paneli' );
		$class = 'is-active';
		$discount = iws_b2b_get_base_discount();
		if ( $discount > 0 ) $facts[] = sprintf( iws_b2b_frontend_text( '%s%% account discount', '%s%% konta atlaide' ), wc_format_decimal( $discount, 0 ) );
		if ( $min_qty ) $facts[] = sprintf( iws_b2b_frontend_text( 'Minimum %d units', 'Minimums %d gab.' ), $min_qty );
		if ( $case_pack ) $facts[] = sprintf( iws_b2b_frontend_text( 'Pack size %d', 'Iepakojumā %d gab.' ), $case_pack );
	} elseif ( iws_b2b_current_user_is_pending() ) {
		$title = iws_b2b_frontend_text( 'B2B application pending', 'B2B pieteikums gaida apstiprinājumu' );
		$copy = iws_b2b_frontend_text( 'Your application has been received. Trade pricing will activate after approval.', 'Jūsu pieteikums ir saņemts. B2B cenas tiks aktivizētas pēc apstiprināšanas.' );
		$cta = iws_b2b_frontend_text( 'View account', 'Skatīt kontu' );
		$class = 'is-pending';
	}

	ob_start();
	?>
	<section class="iws-b2b-product-box <?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( iws_b2b_frontend_text( 'B2B pricing', 'B2B cenas' ) ); ?>">
		<div><span class="iws-b2b-product-box__kicker"><?php echo esc_html( iws_b2b_frontend_text( 'B2B / TRADE', 'B2B / TIRDZNIECĪBA' ) ); ?></span><strong><?php echo esc_html( $title ); ?></strong><p><?php echo esc_html( $copy ); ?></p><?php if ( $facts ) : ?><div class="iws-b2b-product-box__facts"><?php foreach ( $facts as $fact ) : ?><span><?php echo esc_html( $fact ); ?></span><?php endforeach; ?></div><?php endif; ?></div>
		<a class="iws-b2b-product-box__cta" href="<?php echo esc_url( $portal ); ?>"><?php echo esc_html( $cta ); ?></a>
	</section>
	<?php
	return ob_get_clean();
}

function iws_b2b_account_menu_items( $items ) {
	$settings = iws_b2b_get_settings();
	if ( ! iws_b2b_is_enabled() || 'yes' !== $settings['enable_dashboard'] || ! iws_b2b_current_user_is_wholesale() ) {
		return $items;
	}

	$new_items = array();
	foreach ( $items as $key => $label ) {
		$new_items[ $key ] = $label;
		if ( 'dashboard' === $key ) {
			$new_items['b2b-dashboard'] = __( 'B2B Dashboard', 'wp-theme-woo-support' );
		}
	}
	return $new_items;
}

function iws_b2b_dashboard_endpoint() {
	echo iws_b2b_render_portal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function iws_b2b_render_portal() {
	$settings = iws_b2b_get_settings();
	ob_start();
	?>
	<div class="iws-b2b-portal woocommerce">
		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="iws-b2b-auth-grid">
				<div class="iws-b2b-auth-card iws-b2b-login-card">
					<h2><?php echo esc_html( $settings['login_title'] ); ?></h2>
					<?php woocommerce_login_form( array( 'redirect' => esc_url( wc_get_account_endpoint_url( 'b2b-dashboard' ) ) ) ); ?>
				</div>
				<?php if ( 'yes' === $settings['enable_registration'] ) : ?>
					<div class="iws-b2b-auth-card iws-b2b-register-card">
						<?php echo iws_b2b_render_registration_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</div>
		<?php elseif ( iws_b2b_current_user_is_wholesale() ) : ?>
			<?php echo iws_b2b_dashboard_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php elseif ( iws_b2b_current_user_is_pending() ) : ?>
			<div class="iws-b2b-message-card">
				<h2><?php esc_html_e( 'Application pending', 'wp-theme-woo-support' ); ?></h2>
				<p><?php esc_html_e( 'Your trade account request has been received and is waiting for approval.', 'wp-theme-woo-support' ); ?></p>
			</div>
		<?php else : ?>
			<div class="iws-b2b-auth-grid iws-b2b-auth-grid--single">
				<div class="iws-b2b-auth-card iws-b2b-register-card">
					<?php echo iws_b2b_render_registration_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function iws_b2b_dashboard_html() {
	$settings         = iws_b2b_get_settings();
	$min_enabled      = 'yes' === $settings['enable_min_products'];
	$discount_enabled = 'yes' === $settings['enable_discount'];
	$discount_label   = iws_b2b_discount_label( iws_b2b_get_base_discount(), $settings );
	$orders_url       = wc_get_account_endpoint_url( 'orders' );
	$edit_url         = wc_get_account_endpoint_url( 'edit-account' );
	$shop_url         = wc_get_page_permalink( 'shop' );
	ob_start();
	?>
	<div class="iws-b2b-dashboard">
		<section class="iws-b2b-hero">
			<div>
				<p class="iws-b2b-kicker"><?php esc_html_e( 'Business account', 'wp-theme-woo-support' ); ?></p>
				<h2><?php echo esc_html( $settings['dashboard_title'] ); ?></h2>
				<div class="iws-b2b-copy"><?php echo wp_kses_post( wpautop( $settings['dashboard_intro'] ) ); ?></div>
			</div>
			<a class="iws-b2b-hero-btn" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop wholesale', 'wp-theme-woo-support' ); ?></a>
		</section>

		<div class="iws-b2b-cards">
			<div class="iws-b2b-card">
				<span><?php esc_html_e( 'Minimum products', 'wp-theme-woo-support' ); ?></span>
				<strong><?php echo $min_enabled ? esc_html( absint( $settings['min_products'] ) ) : esc_html__( 'Off', 'wp-theme-woo-support' ); ?></strong>
				<p><?php esc_html_e( 'Required total quantity before checkout for wholesale users.', 'wp-theme-woo-support' ); ?></p>
			</div>
			<div class="iws-b2b-card">
				<span><?php esc_html_e( 'Wholesale discount', 'wp-theme-woo-support' ); ?></span>
				<strong><?php echo $discount_enabled ? esc_html( $discount_label ) : esc_html__( 'Off', 'wp-theme-woo-support' ); ?></strong>
				<p><?php esc_html_e( 'Base business pricing. Quantity tiers may increase this discount.', 'wp-theme-woo-support' ); ?></p>
			</div>
			<div class="iws-b2b-card">
				<span><?php esc_html_e( 'Tiered pricing', 'wp-theme-woo-support' ); ?></span>
				<strong><?php echo 'yes' === $settings['enable_tiered_pricing'] ? esc_html__( 'On', 'wp-theme-woo-support' ) : esc_html__( 'Off', 'wp-theme-woo-support' ); ?></strong>
				<p><?php esc_html_e( 'Higher quantity brackets can unlock larger discounts.', 'wp-theme-woo-support' ); ?></p>
			</div>
		</div>

		<?php echo iws_b2b_tier_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo iws_b2b_custom_info_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="iws-b2b-actions">
			<a class="button" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop products', 'wp-theme-woo-support' ); ?></a>
			<a class="button" href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'View orders', 'wp-theme-woo-support' ); ?></a>
			<a class="button" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Account details', 'wp-theme-woo-support' ); ?></a>
			<a class="button iws-b2b-logout" href="<?php echo esc_url( wc_logout_url() ); ?>"><?php esc_html_e( 'Log out', 'wp-theme-woo-support' ); ?></a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

function iws_b2b_parse_custom_fields() {
	$settings = iws_b2b_get_settings();
	$lines    = preg_split( '/\r\n|\r|\n/', (string) $settings['registration_fields'] );
	$fields   = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line ) );
		$key   = sanitize_key( $parts[0] ?? '' );
		if ( ! $key ) {
			continue;
		}
		$type = sanitize_key( $parts[2] ?? 'text' );
		if ( ! in_array( $type, array( 'text', 'textarea', 'email', 'tel', 'number', 'date', 'select', 'checkbox', 'file' ), true ) ) {
			$type = 'text';
		}
		$fields[] = array(
			'key'      => $key,
			'label'    => sanitize_text_field( $parts[1] ?? ucwords( str_replace( '_', ' ', $key ) ) ),
			'type'     => $type,
			'required' => 'yes' === strtolower( $parts[3] ?? 'no' ),
			'billing'  => 'yes' === strtolower( $parts[4] ?? 'no' ),
			'checkout' => 'yes' === strtolower( $parts[5] ?? 'no' ),
			'options'  => array_filter( array_map( 'trim', explode( ',', $parts[6] ?? '' ) ) ),
		);
	}
	return $fields;
}

function iws_b2b_render_registration_form() {
	$settings = iws_b2b_get_settings();
	$fields   = iws_b2b_parse_custom_fields();
	ob_start();
	?>
	<form class="iws-b2b-registration-form" method="post" enctype="multipart/form-data">
		<h2><?php echo esc_html( $settings['registration_title'] ); ?></h2>
		<div class="iws-b2b-copy"><?php echo wp_kses_post( wpautop( $settings['registration_intro'] ) ); ?></div>
		<?php wp_nonce_field( 'iws_b2b_register', 'iws_b2b_register_nonce' ); ?>
		<input type="hidden" name="iws_b2b_register" value="1">
		<p class="form-row form-row-wide">
			<label for="iws_b2b_email"><?php esc_html_e( 'Email address', 'wp-theme-woo-support' ); ?> <span class="required">*</span></label>
			<input type="email" class="input-text" name="email" id="iws_b2b_email" required>
		</p>
		<p class="form-row form-row-wide iws-b2b-password-field">
			<label for="iws_b2b_password"><?php esc_html_e( 'Password', 'wp-theme-woo-support' ); ?> <span class="required">*</span></label>
			<span class="iws-b2b-password-wrap">
				<input type="password" class="input-text" name="password" id="iws_b2b_password" required>
				<button type="button" class="iws-b2b-show-password" aria-label="<?php esc_attr_e( 'Show password', 'wp-theme-woo-support' ); ?>" aria-pressed="false"></button>
			</span>
		</p>
		<?php foreach ( $fields as $field ) : ?>
			<?php iws_b2b_render_field_input( $field ); ?>
		<?php endforeach; ?>
		<p class="form-row iws-b2b-submit-row">
			<button type="submit" class="button iws-b2b-submit"><?php esc_html_e( 'Submit application', 'wp-theme-woo-support' ); ?></button>
		</p>
	</form>
	<?php
	return ob_get_clean();
}

function iws_b2b_render_field_input( $field, $value = '' ) {
	$key      = 'iws_b2b_' . $field['key'];
	$required = $field['required'] ? ' required' : '';
	?>
	<p class="form-row form-row-wide iws-b2b-field iws-b2b-field--<?php echo esc_attr( $field['type'] ); ?>">
		<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $field['required'] ? ' <span class="required">*</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
		<?php if ( 'textarea' === $field['type'] ) : ?>
			<textarea class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( $required ); ?>><?php echo esc_textarea( $value ); ?></textarea>
		<?php elseif ( 'select' === $field['type'] ) : ?>
			<select class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"<?php echo esc_attr( $required ); ?>>
				<option value=""><?php esc_html_e( 'Select an option', 'wp-theme-woo-support' ); ?></option>
				<?php foreach ( $field['options'] as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( iws_b2b_standard_option_label( $option ) ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php elseif ( 'checkbox' === $field['type'] ) : ?>
			<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="yes"<?php echo esc_attr( $required ); ?>>
		<?php else : ?>
			<input type="<?php echo esc_attr( $field['type'] ); ?>" class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"<?php echo esc_attr( $required ); ?>>
		<?php endif; ?>
	</p>
	<?php
}

function iws_b2b_handle_registration() {
	if ( empty( $_POST['iws_b2b_register'] ) || ! iws_b2b_is_enabled() ) {
		return;
	}
	if ( empty( $_POST['iws_b2b_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iws_b2b_register_nonce'] ) ), 'iws_b2b_register' ) ) {
		wc_add_notice( __( 'Security check failed. Please try again.', 'wp-theme-woo-support' ), 'error' );
		return;
	}

	$settings = iws_b2b_get_settings();
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$password = (string) wp_unslash( $_POST['password'] ?? '' );
	if ( ! is_email( $email ) || email_exists( $email ) || '' === $password ) {
		wc_add_notice( __( 'Please enter a valid new email address and password.', 'wp-theme-woo-support' ), 'error' );
		return;
	}

	$fields = iws_b2b_parse_custom_fields();
	foreach ( $fields as $field ) {
		$key = 'iws_b2b_' . $field['key'];
		if ( $field['required'] ) {
			if ( 'file' === $field['type'] ) {
				if ( empty( $_FILES[ $key ]['name'] ) ) {
					wc_add_notice( sprintf( __( '%s is required.', 'wp-theme-woo-support' ), $field['label'] ), 'error' );
					return;
				}
			} elseif ( empty( $_POST[ $key ] ) ) {
				wc_add_notice( sprintf( __( '%s is required.', 'wp-theme-woo-support' ), $field['label'] ), 'error' );
				return;
			}
		}
	}

	$status = 'manual' === $settings['registration_approval'] ? 'pending' : 'approved';
	$role   = 'approved' === $status && 'yes' === $settings['auto_assign_wholesale_role'] ? 'wholesale_customer' : 'pending_wholesale_customer';
	$user_id = wp_create_user( $email, $password, $email );
	if ( is_wp_error( $user_id ) ) {
		wc_add_notice( $user_id->get_error_message(), 'error' );
		return;
	}

	$user = new WP_User( $user_id );
	$user->set_role( $role );
	update_user_meta( $user_id, 'iws_b2b_status', $status );
	update_user_meta( $user_id, 'iws_b2b_application_date', current_time( 'mysql' ) );

	foreach ( $fields as $field ) {
		$key = 'iws_b2b_' . $field['key'];
		if ( 'file' === $field['type'] && ! empty( $_FILES[ $key ]['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$upload = wp_handle_upload( $_FILES[ $key ], array( 'test_form' => false ) );
			if ( empty( $upload['error'] ) && ! empty( $upload['url'] ) ) {
				update_user_meta( $user_id, $key, esc_url_raw( $upload['url'] ) );
			}
		} else {
			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
			update_user_meta( $user_id, $key, $value );
		}
	}

	if ( 'approved' === $status ) {
		wc_add_notice( __( 'Your business account is active. You can now log in.', 'wp-theme-woo-support' ), 'success' );
	} else {
		wc_add_notice( __( 'Your application has been received and is waiting for approval.', 'wp-theme-woo-support' ), 'success' );
	}
}

function iws_b2b_admin_menu() {
	add_submenu_page( 'woocommerce', __( 'B2B Wholesale', 'wp-theme-woo-support' ), __( 'B2B Wholesale', 'wp-theme-woo-support' ), 'manage_woocommerce', 'iws-b2b-wholesale', 'iws_b2b_settings_page' );
}

function iws_b2b_register_settings() {
	register_setting( 'iws_b2b_settings_group', 'iws_b2b_settings', 'iws_b2b_sanitize_settings' );
}

function iws_b2b_sanitize_settings( $input ) {
	$defaults = iws_b2b_defaults();
	$input    = is_array( $input ) ? $input : array();
	$out      = array();
	$checkboxes = array( 'enabled', 'enable_dashboard', 'enable_registration', 'auto_assign_wholesale_role', 'enable_min_products', 'enable_min_order_value', 'enable_discount', 'enable_tiered_pricing', 'show_retail_prices', 'private_store_mode', 'hide_prices_logged_out', 'enable_custom_info_table', 'enable_po_gateway', 'show_cart_notice', 'redirect_after_login' );
	foreach ( $checkboxes as $key ) {
		$out[ $key ] = ! empty( $input[ $key ] ) ? 'yes' : 'no';
	}
	$out['registration_approval'] = in_array( $input['registration_approval'] ?? 'manual', array( 'manual', 'automatic' ), true ) ? $input['registration_approval'] : 'manual';
	$out['discount_type']         = in_array( $input['discount_type'] ?? 'percent', array( 'percent', 'amount' ), true ) ? $input['discount_type'] : 'percent';
	$out['min_products']          = max( 1, absint( $input['min_products'] ?? $defaults['min_products'] ) );
	$out['min_order_value']       = max( 0, (float) wc_format_decimal( $input['min_order_value'] ?? 0 ) );
	$out['discount_percent']      = min( 100, max( 0, (float) wc_format_decimal( $input['discount_percent'] ?? 0 ) ) );
	$out['discount_amount']       = max( 0, (float) wc_format_decimal( $input['discount_amount'] ?? 0 ) );
	foreach ( array( 'dashboard_title', 'dashboard_intro', 'registration_title', 'registration_intro', 'login_title', 'min_products_message', 'min_value_message', 'discount_message' ) as $key ) {
		$out[ $key ] = wp_kses_post( $input[ $key ] ?? $defaults[ $key ] );
	}
	$out['tier_rules']          = sanitize_textarea_field( $input['tier_rules'] ?? $defaults['tier_rules'] );
	$out['custom_info_table']   = wp_kses_post( $input['custom_info_table'] ?? $defaults['custom_info_table'] );
	$out['registration_fields'] = sanitize_textarea_field( $input['registration_fields'] ?? $defaults['registration_fields'] );
	return iws_b2b_sanitize_commerce($input, $out);
}

function iws_b2b_settings_page() {
	$s       = iws_b2b_get_settings();
	$pending = get_users( array( 'role__in' => array( 'pending_wholesale_customer' ), 'number' => 20, 'fields' => array( 'ID', 'user_email', 'display_name' ) ) );
	?>
	<div class="wrap iws-b2b-admin">
		<h1><?php esc_html_e( 'B2B Wholesale', 'wp-theme-woo-support' ); ?></h1>
		<p><?php esc_html_e( 'Optional wholesale tools for business customers. Enable only the features you need.', 'wp-theme-woo-support' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'iws_b2b_settings_group' ); ?>
			<div class="postbox"><div class="inside">
				<h2><?php esc_html_e( 'General', 'wp-theme-woo-support' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Enable B2B system', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enabled]" value="1" <?php checked( $s['enabled'], 'yes' ); ?>> <?php esc_html_e( 'Turn on B2B/Wholesale features', 'wp-theme-woo-support' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'My Account dashboard', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_dashboard]" value="1" <?php checked( $s['enable_dashboard'], 'yes' ); ?>> <?php esc_html_e( 'Show B2B dashboard for wholesale customers', 'wp-theme-woo-support' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Dashboard text', 'wp-theme-woo-support' ); ?></th><td><input type="text" class="regular-text" name="iws_b2b_settings[dashboard_title]" value="<?php echo esc_attr( $s['dashboard_title'] ); ?>"><br><br><textarea class="large-text" rows="3" name="iws_b2b_settings[dashboard_intro]"><?php echo esc_textarea( $s['dashboard_intro'] ); ?></textarea></td></tr>
				</table>
                <?php iws_b2b_commerce_settings_html($s); ?>
				<h2><?php esc_html_e( 'Business registration', 'wp-theme-woo-support' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Registration form', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_registration]" value="1" <?php checked( $s['enable_registration'], 'yes' ); ?>> <?php esc_html_e( 'Enable B2B registration form and shortcode', 'wp-theme-woo-support' ); ?></label><p class="description">[iws_b2b_registration] or page slug <code>b2b-dashboard</code></p></td></tr>
					<tr><th><?php esc_html_e( 'Approval mode', 'wp-theme-woo-support' ); ?></th><td><select name="iws_b2b_settings[registration_approval]"><option value="manual" <?php selected( $s['registration_approval'], 'manual' ); ?>><?php esc_html_e( 'Manual approval', 'wp-theme-woo-support' ); ?></option><option value="automatic" <?php selected( $s['registration_approval'], 'automatic' ); ?>><?php esc_html_e( 'Automatic approval', 'wp-theme-woo-support' ); ?></option></select> <label><input type="checkbox" name="iws_b2b_settings[auto_assign_wholesale_role]" value="1" <?php checked( $s['auto_assign_wholesale_role'], 'yes' ); ?>> <?php esc_html_e( 'Assign Wholesale Customer role after approval', 'wp-theme-woo-support' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Registration copy', 'wp-theme-woo-support' ); ?></th><td><input type="text" class="regular-text" name="iws_b2b_settings[registration_title]" value="<?php echo esc_attr( $s['registration_title'] ); ?>"><br><br><textarea class="large-text" rows="3" name="iws_b2b_settings[registration_intro]"><?php echo esc_textarea( $s['registration_intro'] ); ?></textarea></td></tr>
					<tr><th><?php esc_html_e( 'Custom fields', 'wp-theme-woo-support' ); ?></th><td><textarea class="large-text code" rows="8" name="iws_b2b_settings[registration_fields]"><?php echo esc_textarea( $s['registration_fields'] ); ?></textarea><p class="description"><?php esc_html_e( 'One field per line: key|Label|type|required|billing|checkout|options. Types: text, textarea, email, tel, number, date, select, checkbox, file.', 'wp-theme-woo-support' ); ?></p></td></tr>
				</table>
				<h2><?php esc_html_e( 'Pricing and order rules', 'wp-theme-woo-support' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Wholesale discount', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_discount]" value="1" <?php checked( $s['enable_discount'], 'yes' ); ?>> <?php esc_html_e( 'Apply B2B price discount', 'wp-theme-woo-support' ); ?></label><br><select name="iws_b2b_settings[discount_type]"><option value="percent" <?php selected( $s['discount_type'], 'percent' ); ?>><?php esc_html_e( 'Percentage', 'wp-theme-woo-support' ); ?></option><option value="amount" <?php selected( $s['discount_type'], 'amount' ); ?>><?php esc_html_e( 'Fixed amount', 'wp-theme-woo-support' ); ?></option></select> <input type="number" min="0" max="100" step="0.01" class="small-text" name="iws_b2b_settings[discount_percent]" value="<?php echo esc_attr( $s['discount_percent'] ); ?>"> % <input type="number" min="0" step="0.01" class="small-text" name="iws_b2b_settings[discount_amount]" value="<?php echo esc_attr( $s['discount_amount'] ); ?>"> <?php echo esc_html( get_woocommerce_currency_symbol() ); ?> <p class="description"><?php esc_html_e( 'This changes product, cart, and checkout prices for wholesale customers.', 'wp-theme-woo-support' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Show retail + wholesale', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[show_retail_prices]" value="1" <?php checked( $s['show_retail_prices'], 'yes' ); ?>> <?php esc_html_e( 'Show both retail and wholesale prices to B2B users', 'wp-theme-woo-support' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Tiered pricing', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_tiered_pricing]" value="1" <?php checked( $s['enable_tiered_pricing'], 'yes' ); ?>> <?php esc_html_e( 'Enable quantity discounts', 'wp-theme-woo-support' ); ?></label><br><textarea class="large-text code" rows="5" name="iws_b2b_settings[tier_rules]"><?php echo esc_textarea( $s['tier_rules'] ); ?></textarea><p class="description"><?php esc_html_e( 'One rule per line: minimum quantity|discount percent. Example: 25|5 means 5% discount when a cart line quantity is 25+.', 'wp-theme-woo-support' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Minimum products rule', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_min_products]" value="1" <?php checked( $s['enable_min_products'], 'yes' ); ?>> <?php esc_html_e( 'Require minimum total product quantity', 'wp-theme-woo-support' ); ?></label><br><input type="number" min="1" class="small-text" name="iws_b2b_settings[min_products]" value="<?php echo esc_attr( $s['min_products'] ); ?>"> <?php esc_html_e( 'products minimum', 'wp-theme-woo-support' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Minimum order value', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_min_order_value]" value="1" <?php checked( $s['enable_min_order_value'], 'yes' ); ?>> <?php esc_html_e( 'Require minimum order value', 'wp-theme-woo-support' ); ?></label><br><input type="number" min="0" step="0.01" class="small-text" name="iws_b2b_settings[min_order_value]" value="<?php echo esc_attr( $s['min_order_value'] ); ?>"></td></tr>
				</table>
				<h2><?php esc_html_e( 'Visibility, tables, and payment', 'wp-theme-woo-support' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Custom information table', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_custom_info_table]" value="1" <?php checked( $s['enable_custom_info_table'], 'yes' ); ?>> <?php esc_html_e( 'Show B2B information table', 'wp-theme-woo-support' ); ?></label><br><textarea class="large-text code" rows="5" name="iws_b2b_settings[custom_info_table]"><?php echo esc_textarea( $s['custom_info_table'] ); ?></textarea><p class="description"><?php esc_html_e( 'One row per line: label|value. Values can include safe HTML.', 'wp-theme-woo-support' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Invoice / PO gateway', 'wp-theme-woo-support' ); ?></th><td><label><input type="checkbox" name="iws_b2b_settings[enable_po_gateway]" value="1" <?php checked( $s['enable_po_gateway'], 'yes' ); ?>> <?php esc_html_e( 'Enable compatible invoice / purchase order payment methods for B2B users only when available.', 'wp-theme-woo-support' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Messages', 'wp-theme-woo-support' ); ?></th><td><label><?php esc_html_e( 'Minimum products message', 'wp-theme-woo-support' ); ?></label><input type="text" class="large-text" name="iws_b2b_settings[min_products_message]" value="<?php echo esc_attr( $s['min_products_message'] ); ?>"><label><?php esc_html_e( 'Minimum value message', 'wp-theme-woo-support' ); ?></label><input type="text" class="large-text" name="iws_b2b_settings[min_value_message]" value="<?php echo esc_attr( $s['min_value_message'] ); ?>"><label><?php esc_html_e( 'Discount notice', 'wp-theme-woo-support' ); ?></label><input type="text" class="large-text" name="iws_b2b_settings[discount_message]" value="<?php echo esc_attr( $s['discount_message'] ); ?>"><p class="description"><?php esc_html_e( 'Placeholders: {min}, {qty}, {total}', 'wp-theme-woo-support' ); ?></p></td></tr>
				</table>
				<?php submit_button( __( 'Save B2B settings', 'wp-theme-woo-support' ) ); ?>
			</div></div>
		</form>
		<div class="postbox"><div class="inside"><h2><?php esc_html_e( 'Pending applications', 'wp-theme-woo-support' ); ?></h2><?php iws_b2b_pending_applications_table( $pending ); ?></div></div>
	</div>
	<?php
}

function iws_b2b_pending_applications_table( $pending ) {
	if ( empty( $pending ) ) {
		echo '<p>' . esc_html__( 'No pending wholesale applications.', 'wp-theme-woo-support' ) . '</p>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'User', 'wp-theme-woo-support' ) . '</th><th>' . esc_html__( 'Application data', 'wp-theme-woo-support' ) . '</th><th>' . esc_html__( 'Actions', 'wp-theme-woo-support' ) . '</th></tr></thead><tbody>';
	foreach ( $pending as $user ) {
		$approve = wp_nonce_url( admin_url( 'admin-post.php?action=iws_b2b_approve_user&user_id=' . absint( $user->ID ) ), 'iws_b2b_approve_user_' . $user->ID );
		$reject  = wp_nonce_url( admin_url( 'admin-post.php?action=iws_b2b_reject_user&user_id=' . absint( $user->ID ) ), 'iws_b2b_reject_user_' . $user->ID );
		$meta    = array();
		foreach ( iws_b2b_parse_custom_fields() as $field ) {
			$value = get_user_meta( $user->ID, 'iws_b2b_' . $field['key'], true );
			if ( $value ) {
				$meta[] = '<strong>' . esc_html( $field['label'] ) . ':</strong> ' . ( 'file' === $field['type'] ? '<a href="' . esc_url( $value ) . '" target="_blank">' . esc_html__( 'View file', 'wp-theme-woo-support' ) . '</a>' : esc_html( $value ) );
			}
		}
		echo '<tr><td><strong>' . esc_html( $user->display_name ?: $user->user_email ) . '</strong><br>' . esc_html( $user->user_email ) . '</td><td>' . wp_kses_post( implode( '<br>', $meta ) ) . '</td><td><a class="button button-primary" href="' . esc_url( $approve ) . '">' . esc_html__( 'Approve', 'wp-theme-woo-support' ) . '</a> <a class="button" href="' . esc_url( $reject ) . '">' . esc_html__( 'Reject', 'wp-theme-woo-support' ) . '</a></td></tr>';
	}
	echo '</tbody></table>';
}

function iws_b2b_admin_approve_user() {
	$user_id = absint( $_GET['user_id'] ?? 0 );
	if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'iws_b2b_approve_user_' . $user_id ) ) {
		wp_die( esc_html__( 'Not allowed.', 'wp-theme-woo-support' ) );
	}
	$user = new WP_User( $user_id );
	$user->set_role( 'wholesale_customer' );
	update_user_meta( $user_id, 'iws_b2b_status', 'approved' );
	wp_safe_redirect( admin_url( 'admin.php?page=iws-b2b-wholesale' ) );
	exit;
}

function iws_b2b_admin_reject_user() {
	$user_id = absint( $_GET['user_id'] ?? 0 );
	if ( ! current_user_can( 'manage_woocommerce' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'iws_b2b_reject_user_' . $user_id ) ) {
		wp_die( esc_html__( 'Not allowed.', 'wp-theme-woo-support' ) );
	}
	$user = new WP_User( $user_id );
	$user->set_role( 'customer' );
	update_user_meta( $user_id, 'iws_b2b_status', 'rejected' );
	wp_safe_redirect( admin_url( 'admin.php?page=iws-b2b-wholesale' ) );
	exit;
}

function iws_b2b_parse_tiers() {
	$settings = iws_b2b_get_settings();
	$lines    = preg_split( '/\r\n|\r|\n/', (string) $settings['tier_rules'] );
	$tiers    = array();
	foreach ( $lines as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		$qty   = absint( $parts[0] ?? 0 );
		$disc  = min( 100, max( 0, (float) ( $parts[1] ?? 0 ) ) );
		if ( $qty > 0 && $disc > 0 ) {
			$tiers[] = array( 'qty' => $qty, 'discount' => $disc );
		}
	}
	usort( $tiers, function( $a, $b ) { return $a['qty'] <=> $b['qty']; } );
	return $tiers;
}

function iws_b2b_get_base_discount( $user_id = 0 ) {
	$settings = iws_b2b_get_settings();
	if ( ! iws_b2b_is_enabled() || 'yes' !== $settings['enable_discount'] ) {
		return 0;
	}
	$user_id = $user_id ?: get_current_user_id();
	$user_discount = get_user_meta( $user_id, 'iws_b2b_discount_percent', true );
	if ( '' !== $user_discount ) {
		return min( 100, max( 0, (float) $user_discount ) );
	}
	$user = get_user_by('id', $user_id);
    $role_discounts = [];
    foreach ((array)($user ? $user->roles : []) as $role) if (isset($settings['role_discounts'][$role])) $role_discounts[] = (float)$settings['role_discounts'][$role];
    if ($role_discounts) return min(100, max(0, max($role_discounts)));
    return 'percent' === $settings['discount_type'] ? min( 100, max( 0, (float) $settings['discount_percent'] ) ) : 0;
}

function iws_b2b_discount_label( $discount, $settings = null ) {
	$settings = $settings ?: iws_b2b_get_settings();
	if ( 'amount' === $settings['discount_type'] && (float) $settings['discount_amount'] > 0 ) {
		return wc_price( (float) $settings['discount_amount'] ) . ' ' . __( 'off each product', 'wp-theme-woo-support' );
	}
	return wc_format_decimal( $discount, 2 ) . '%';
}

function iws_b2b_discount_for_qty( $qty ) {
	$settings = iws_b2b_get_settings();
	$discount = iws_b2b_get_base_discount();
	if ( 'yes' !== $settings['enable_tiered_pricing'] ) {
		return $discount;
	}
	foreach ( iws_b2b_parse_tiers() as $tier ) {
		if ( $qty >= $tier['qty'] ) {
			$discount = max( $discount, (float) $tier['discount'] );
		}
	}
	return $discount;
}

function iws_b2b_filter_product_price($price, $product) {
    if (is_admin() && !wp_doing_ajax()) return $price;
    if (!iws_b2b_is_enabled() || !iws_b2b_current_user_is_wholesale() || !$product instanceof WC_Product) return $price;
    if (IWS_Pricing_Context::is_final($product)) return $price;
    return iws_b2b_resolve_price($price, $product, 1);
}

function iws_b2b_filter_variation_price( $price, $variation, $product ) {
	return iws_b2b_filter_product_price( $price, $variation );
}

function iws_b2b_price_html( $price_html, $product ) {
	$settings = iws_b2b_get_settings();
	if ( ! iws_b2b_is_enabled() ) {
		return $price_html;
	}
	if (!iws_b2b_can_purchase()) {
		return '<span class="iws-b2b-login-price">' . esc_html__( 'Login to view trade prices', 'wp-theme-woo-support' ) . '</span>';
	}
	if ( ! iws_b2b_current_user_is_wholesale() || 'yes' !== $settings['show_retail_prices'] || 'yes' !== $settings['enable_discount'] || ! $product instanceof WC_Product ) {
		return $price_html;
	}
	$raw = $product->get_regular_price();
	if ( '' === $raw ) {
		$raw = $product->get_price( 'edit' );
	}
	if ( '' === $raw ) {
		return $price_html;
	}
	$retail = wc_price( (float) $raw );
	return '<span class="iws-b2b-price"><span class="iws-b2b-price__retail">' . esc_html__( 'Retail:', 'wp-theme-woo-support' ) . ' <del>' . wp_kses_post( $retail ) . '</del></span><span class="iws-b2b-price__trade">' . esc_html__( 'Wholesale:', 'wp-theme-woo-support' ) . ' ' . wp_kses_post( $price_html ) . '</span></span>';
}

function iws_b2b_apply_cart_discount($cart) {
    if (is_admin() && !wp_doing_ajax()) return;
    if (!iws_b2b_is_enabled() || !iws_b2b_current_user_is_wholesale() || !$cart) return;
    foreach ($cart->get_cart() as $item) {
        $product = $item['data'] ?? null;
        if (!$product instanceof WC_Product) continue;
        $base = IWS_Pricing_Context::base($product);
        IWS_Pricing_Context::set($product, iws_b2b_resolve_price($base, $product, (int)($item['quantity'] ?? 1)));
    }
}

function iws_b2b_cart_quantity() {
	if ( ! WC()->cart ) {
		return 0;
	}
	$qty = 0;
	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$qty += absint( $cart_item['quantity'] ?? 0 );
	}
	return $qty;
}

function iws_b2b_validate_order_rules() {
    foreach (iws_b2b_rule_errors(WC()->cart) as $message) if (!wc_has_notice($message, 'error')) wc_add_notice($message, 'error');
}

function iws_b2b_minimum_message( $settings, $qty ) {
	$message = str_replace( array( '{min}', '{qty}' ), array( absint( $settings['min_products'] ), absint( $qty ) ), $settings['min_products_message'] );
	return wp_strip_all_tags( $message );
}

function iws_b2b_cart_notice() {
	$settings = iws_b2b_get_settings();
	if ( ! iws_b2b_is_enabled() || 'yes' !== $settings['show_cart_notice'] || ! iws_b2b_current_user_is_wholesale() || ! WC()->cart ) {
		return;
	}
	$qty = iws_b2b_cart_quantity();
	if ( 'yes' === $settings['enable_min_products'] && $qty > 0 && $qty < absint( $settings['min_products'] ) ) {
		wc_print_notice( iws_b2b_minimum_message( $settings, $qty ), 'notice' );
	}
	if ( 'yes' === $settings['enable_discount'] && iws_b2b_get_base_discount() > 0 ) {
		wc_print_notice( wp_strip_all_tags( $settings['discount_message'] ), 'notice' );
	}
}

function iws_b2b_tier_table_html() {
	$settings = iws_b2b_get_settings();
	$tiers    = iws_b2b_parse_tiers();
	if ( 'yes' !== $settings['enable_tiered_pricing'] || empty( $tiers ) || ! iws_b2b_current_user_is_wholesale() ) {
		return '';
	}
	ob_start();
	?>
	<div class="iws-b2b-tier-table" data-iws-b2b-tiers="<?php echo esc_attr( wp_json_encode( $tiers ) ); ?>">
		<h3><?php esc_html_e( 'Quantity price breaks', 'wp-theme-woo-support' ); ?></h3>
		<div class="iws-b2b-tier-progress"><span style="width:0%"></span></div>
		<table><thead><tr><th><?php esc_html_e( 'Quantity', 'wp-theme-woo-support' ); ?></th><th><?php esc_html_e( 'Discount', 'wp-theme-woo-support' ); ?></th></tr></thead><tbody>
		<?php foreach ( $tiers as $tier ) : ?>
			<tr data-tier-qty="<?php echo esc_attr( $tier['qty'] ); ?>"><td><?php echo esc_html( $tier['qty'] . '+' ); ?></td><td><?php echo esc_html( wc_format_decimal( $tier['discount'], 2 ) . '%' ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
	return ob_get_clean();
}

function iws_b2b_custom_info_table_html() {
	$settings = iws_b2b_get_settings();
	if ( 'yes' !== $settings['enable_custom_info_table'] || ! iws_b2b_current_user_is_wholesale() ) {
		return '';
	}
	$lines = preg_split( '/\r\n|\r|\n/', (string) $settings['custom_info_table'] );
	$rows  = array();
	foreach ( $lines as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( ! empty( $parts[0] ) && isset( $parts[1] ) ) {
			$rows[] = $parts;
		}
	}
	if ( empty( $rows ) ) {
		return '';
	}
	ob_start();
	?>
	<div class="iws-b2b-info-table"><h3><?php esc_html_e( 'Trade information', 'wp-theme-woo-support' ); ?></h3><table><tbody>
	<?php foreach ( $rows as $row ) : ?>
		<tr><th><?php echo esc_html( $row[0] ); ?></th><td><?php echo wp_kses_post( $row[1] ); ?></td></tr>
	<?php endforeach; ?>
	</tbody></table></div>
	<?php
	return ob_get_clean();
}

function iws_b2b_render_price_tools() {
	if ( ! iws_b2b_is_enabled() || ! iws_b2b_current_user_is_wholesale() ) {
		return;
	}
	echo iws_b2b_tier_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo iws_b2b_custom_info_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function iws_b2b_checkout_fields( $fields ) {
	if ( ! iws_b2b_is_enabled() || ! iws_b2b_current_user_is_wholesale() ) {
		return $fields;
	}
	foreach ( iws_b2b_parse_custom_fields() as $field ) {
		if ( ! $field['checkout'] ) {
			continue;
		}
		$key = 'iws_b2b_' . $field['key'];
		$fields['billing'][ $key ] = array(
			'type'     => 'select' === $field['type'] ? 'select' : ( 'textarea' === $field['type'] ? 'textarea' : 'text' ),
			'label'    => $field['label'],
			'required' => $field['required'],
			'options'  => 'select' === $field['type'] ? array_combine( $field['options'], $field['options'] ) : array(),
			'priority' => 120,
		);
	}
	return $fields;
}

function iws_b2b_save_checkout_fields( $order, $data ) {
	foreach ( iws_b2b_parse_custom_fields() as $field ) {
		$key = 'iws_b2b_' . $field['key'];
		if ( ! empty( $_POST[ $key ] ) ) {
			$order->update_meta_data( '_' . $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
}

function iws_b2b_filter_payment_gateways( $gateways ) {
	$settings = iws_b2b_get_settings();
	if ( ! iws_b2b_is_enabled() || 'yes' !== $settings['enable_po_gateway'] || iws_b2b_current_user_is_wholesale() ) {
		return $gateways;
	}
	foreach ((array)$settings['trade_only_gateways'] as $gateway_id) {
		if ( isset( $gateways[ $gateway_id ] ) ) {
			unset( $gateways[ $gateway_id ] );
		}
	}
	return $gateways;
}

function iws_b2b_login_redirect( $redirect, $user ) {
	$settings = iws_b2b_get_settings();
	if ( ! iws_b2b_is_enabled() || 'yes' !== $settings['redirect_after_login'] || ! $user || empty( $user->roles ) ) {
		return $redirect;
	}
	if ( iws_b2b_user_is_trade($user) && 'yes' === $settings['enable_dashboard'] ) {
		return wc_get_account_endpoint_url( 'b2b-dashboard' );
	}
	return $redirect;
}

function iws_b2b_frontend_styles() {
	if ( is_admin() || ! iws_b2b_is_enabled() ) {
		return;
	}
	$css = '
	.page .iws-b2b-portal{max-width:1180px;margin:0 auto;padding:40px 20px}.iws-b2b-auth-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:28px;align-items:start}.iws-b2b-auth-grid--single{grid-template-columns:minmax(0,680px);justify-content:center}.iws-b2b-auth-card,.iws-b2b-message-card{background:#fff;border:1px solid #e8edf4;border-radius:14px;box-shadow:0 16px 45px rgba(0,0,0,.08);padding:30px}.iws-b2b-auth-card h2,.iws-b2b-message-card h2{margin:0 0 18px;color:var(--wp-brand-color);font-weight:700}.iws-b2b-auth-card .form-row{display:grid;gap:6px;margin:0 0 16px}.iws-b2b-auth-card label{font-weight:600;color:#111}.iws-b2b-auth-card input.input-text,.iws-b2b-auth-card textarea,.iws-b2b-auth-card select{width:100%;min-height:44px;border:1px solid #ccd4df;border-radius:6px;padding:10px 12px}.iws-b2b-auth-card button,.iws-b2b-submit{min-height:44px;border:0;border-radius:6px;background:var(--wp-brand-color);color:#fff;font-weight:700;padding:11px 20px}.iws-b2b-dashboard{display:grid;gap:22px}.iws-b2b-hero{display:flex;justify-content:space-between;gap:20px;align-items:center;padding:28px;border-radius:16px;background:linear-gradient(135deg,var(--wp-brand-color),#0d2744);color:#fff}.iws-b2b-kicker{margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#fff}.iws-b2b-hero h2{margin:0 0 8px;font-size:32px;line-height:1.15;font-weight:700;color:#fff}.iws-b2b-copy p{margin:0;color:inherit}.iws-b2b-hero-btn,.iws-b2b-actions .button{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:11px 18px;border-radius:7px;background:#fff;color:var(--wp-brand-color);text-decoration:none;font-weight:700}.iws-b2b-actions .button{background:var(--wp-brand-color);color:#fff}.iws-b2b-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.iws-b2b-card{padding:20px;border:1px solid #e5e9ef;border-radius:14px;background:#fff;box-shadow:0 8px 26px rgba(0,0,0,.04)}.iws-b2b-card span{display:block;margin-bottom:8px;font-size:12px;font-weight:700;text-transform:uppercase;color:#606a76}.iws-b2b-card strong{display:block;margin-bottom:7px;font-size:26px;line-height:1;font-weight:700;color:#111}.iws-b2b-card p{margin:0;color:#555}.iws-b2b-actions{display:flex;flex-wrap:wrap;gap:10px}.iws-b2b-price{display:grid;gap:2px}.iws-b2b-price__retail{font-size:.9em;color:#626b75}.iws-b2b-price__trade{font-weight:700;color:var(--wp-brand-color)}.iws-b2b-login-price{display:inline-flex;padding:6px 10px;border-radius:999px;background:#f0f4f8;color:var(--wp-brand-color);font-weight:700}.iws-b2b-tier-table,.iws-b2b-info-table{background:#fff;border:1px solid #e5e9ef;border-radius:14px;padding:20px;box-shadow:0 8px 26px rgba(0,0,0,.04)}.iws-b2b-tier-table h3,.iws-b2b-info-table h3{margin:0 0 14px;color:#111;font-weight:700}.iws-b2b-tier-table table,.iws-b2b-info-table table{width:100%;border-collapse:collapse}.iws-b2b-tier-table th,.iws-b2b-tier-table td,.iws-b2b-info-table th,.iws-b2b-info-table td{padding:12px;border-bottom:1px solid #edf0f4;text-align:left}.iws-b2b-tier-table tr.is-active{background:rgba(0,93,171,.08)}.iws-b2b-tier-progress{height:8px;border-radius:999px;background:#eef2f6;overflow:hidden;margin:0 0 14px}.iws-b2b-tier-progress span{display:block;height:100%;border-radius:inherit;background:var(--wp-brand-color);transition:width .25s ease}.iws-b2b-field--checkbox{display:flex!important;grid-template-columns:auto 1fr;align-items:center;gap:10px}.iws-b2b-field--checkbox label{order:2}.iws-b2b-field--checkbox input{order:1}.woocommerce-account .iws-b2b-portal{padding:0}.woocommerce form.login,.woocommerce-form-login{border:0!important;padding:0!important;margin:0!important}.woocommerce form.login .button{background:var(--wp-brand-color);color:#fff;border:0;border-radius:6px;font-weight:700}.woocommerce form.login input.input-text{min-height:44px;border:1px solid #ccd4df;border-radius:6px;padding:10px 12px}@media(max-width:900px){.iws-b2b-auth-grid,.iws-b2b-cards{grid-template-columns:1fr}.iws-b2b-hero{display:grid}.iws-b2b-portal{padding:24px 16px}}';
	$js  = "document.addEventListener('input',function(e){if(!e.target.matches('form.cart input.qty'))return;document.querySelectorAll('.iws-b2b-tier-table').forEach(function(table){var tiers=JSON.parse(table.getAttribute('data-iws-b2b-tiers')||'[]'),qty=parseInt(e.target.value||'0',10),active=0;tiers.forEach(function(t,i){if(qty>=parseInt(t.qty,10))active=i+1;});table.querySelectorAll('tbody tr').forEach(function(row,i){row.classList.toggle('is-active',i<active);});var bar=table.querySelector('.iws-b2b-tier-progress span');if(bar){bar.style.width=tiers.length?Math.min(100,(active/tiers.length)*100)+'%':'0%';}});});document.addEventListener('change',function(e){if(!e.target.matches('#iws_b2b_account'))return;var box=document.querySelector('[data-iws-b2b-account-fields]');if(!box)return;box.hidden=!e.target.checked;});document.addEventListener('DOMContentLoaded',function(){var c=document.getElementById('iws_b2b_account'),box=document.querySelector('[data-iws-b2b-account-fields]');if(c&&box)box.hidden=!c.checked;});";
	wp_register_style( 'iws-b2b-wholesale', false, array(), '1.1.0' );
	wp_enqueue_style( 'iws-b2b-wholesale' );
	wp_add_inline_style( 'iws-b2b-wholesale', $css );
	wp_register_script( 'iws-b2b-wholesale', false, array(), '1.1.0', true );
	wp_enqueue_script( 'iws-b2b-wholesale' );
	wp_add_inline_script( 'iws-b2b-wholesale', $js );

	$fix_css = '.iws-b2b-account-choice{margin:18px 0 6px;padding:15px;border:1px solid #d7e5dc;border-radius:10px;background:#f6fbf8}.iws-b2b-account-choice__label{display:flex!important;align-items:flex-start;gap:10px;cursor:pointer}.iws-b2b-account-choice__label input{margin-top:4px}.iws-b2b-account-choice__label strong,.iws-b2b-account-choice__label small{display:block}.iws-b2b-account-choice__label strong{color:#143e2a}.iws-b2b-account-choice__label small{margin-top:3px;color:#64748b;font-weight:400}.iws-b2b-account-fields{display:grid;gap:0;margin-top:15px;padding-top:14px;border-top:1px solid #dce8e1}.iws-b2b-account-fields[hidden]{display:none!important}.iws-b2b-account-field{margin:0 0 13px}.iws-b2b-account-field select,.iws-b2b-account-field textarea{width:100%;min-height:44px;border:1px solid #ccd4df;padding:10px 12px}.iws-b2b-account-note{margin:4px 0 0;color:#68766f;font-size:12px;line-height:1.45}.iws-b2b-product-box{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:18px 0;padding:16px 18px;border:1px solid #d7e5dc;border-radius:12px;background:#f6fbf8}.iws-b2b-product-box__kicker{display:block;margin-bottom:4px;color:#168653;font-size:10px;font-weight:900;letter-spacing:.11em}.iws-b2b-product-box strong{display:block;color:#12394d;font-size:15px}.iws-b2b-product-box p{margin:4px 0 0;color:#66766f;font-size:12px;line-height:1.45}.iws-b2b-product-box__facts{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}.iws-b2b-product-box__facts span{padding:5px 8px;border-radius:999px;background:#e5f6ec;color:#14643f;font-size:10px;font-weight:800}.iws-b2b-product-box__cta{flex:0 0 auto;display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:8px 13px;border:1px solid #168653;border-radius:8px;background:#168653;color:#fff!important;text-decoration:none;font-size:12px;font-weight:850}.iws-b2b-product-box.is-pending{background:#fffaf0;border-color:#ead8ae}.iws-b2b-product-box.is-pending .iws-b2b-product-box__kicker{color:#9a6808}@media(max-width:640px){.iws-b2b-product-box{align-items:flex-start;flex-direction:column}.iws-b2b-product-box__cta{width:100%}}.iws-b2b-registration-form h2{line-height:1.25}.iws-b2b-submit-row{margin-top:26px!important}.iws-b2b-auth-card input.input-text,.iws-b2b-auth-card textarea,.iws-b2b-auth-card select,.woocommerce form.login input.input-text,.woocommerce-form-login input.input-text{border-radius:0!important}.iws-b2b-auth-card button,.iws-b2b-submit,.woocommerce form.login .button,.woocommerce-form-login .button{border-radius:0!important}.iws-b2b-actions .iws-b2b-logout{background:#111!important;color:#fff!important}.iws-b2b-password-wrap,.woocommerce form .password-input{position:relative!important;display:block!important;width:100%}.iws-b2b-password-wrap input,.woocommerce form .password-input input{padding-right:48px!important}.iws-b2b-show-password,.woocommerce form .show-password-input{position:absolute!important;top:50%!important;right:12px!important;left:auto!important;transform:translateY(-50%)!important;width:24px!important;height:24px!important;min-width:24px!important;min-height:24px!important;padding:0!important;margin:0!important;border:0!important;border-radius:0!important;background-color:transparent!important;background-repeat:no-repeat!important;background-position:center!important;background-size:20px 20px!important;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2398a2ad\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpath d=\'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z\'/%3E%3Ccircle cx=\'12\' cy=\'12\' r=\'3\'/%3E%3C/svg%3E")!important;color:transparent!important;font-size:0!important;line-height:1!important;text-indent:-9999px!important;opacity:1!important;display:block!important;cursor:pointer!important;z-index:4!important}.woocommerce-account .woocommerce form.login,.woocommerce-account .woocommerce-form-login{border:1px solid #d8dde5!important;border-radius:0!important;padding:26px!important;background:#fff!important;box-shadow:none!important}.iws-b2b-auth-card .woocommerce-form-login{border:0!important;padding:0!important;margin:0!important;background:transparent!important}@media(max-width:767px){.woocommerce-account .woocommerce form.login,.woocommerce-account .woocommerce-form-login{margin-bottom:30px!important}}';
	wp_add_inline_style( 'iws-b2b-wholesale', $fix_css );
	$home_css = '.iws-b2b-home-promo{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(340px,.9fr);gap:30px;align-items:center;padding:34px;border-radius:18px;background:linear-gradient(135deg,#0d4634,#0f7a50);color:#fff;box-shadow:0 14px 36px rgba(15,70,52,.12)}.iws-b2b-home-promo__kicker{display:inline-block;margin:0 0 10px;padding:6px 10px;border-radius:999px;background:#d8ff70;color:#153522;font-size:11px;font-weight:800;letter-spacing:.12em}.iws-b2b-home-promo h2{margin:0 0 10px;color:#fff;font-size:clamp(26px,3vw,40px);line-height:1.08}.iws-b2b-home-promo p{margin:0;color:#def5e9;max-width:720px}.iws-b2b-home-promo__actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}.iws-b2b-home-promo__primary,.iws-b2b-home-promo__secondary{display:inline-flex;min-height:44px;align-items:center;justify-content:center;padding:10px 16px;border:1px solid #fff;border-radius:8px;text-decoration:none;font-weight:800}.iws-b2b-home-promo__primary,.iws-b2b-home-promo__primary:link,.iws-b2b-home-promo__primary:visited{background:#d8ff70!important;border-color:#d8ff70!important;color:#153522!important}.iws-b2b-home-promo__secondary,.iws-b2b-home-promo__secondary:link,.iws-b2b-home-promo__secondary:visited{background:transparent!important;color:#fff!important}.iws-b2b-home-promo__primary:hover,.iws-b2b-home-promo__primary:focus{background:#fff!important;border-color:#fff!important;color:#174534!important}.iws-b2b-home-promo__secondary:hover,.iws-b2b-home-promo__secondary:focus{background:#fff;color:#174534}.iws-b2b-home-promo__benefits{display:grid;gap:10px}.iws-b2b-home-promo__benefits>div{padding:15px 17px;border:1px solid rgba(255,255,255,.24);border-radius:12px;background:rgba(255,255,255,.08)}.iws-b2b-home-promo__benefits strong,.iws-b2b-home-promo__benefits span{display:block}.iws-b2b-home-promo__benefits strong{color:#fff;font-size:16px}.iws-b2b-home-promo__benefits span{margin-top:3px;color:#cde9dc;font-size:12px;text-transform:uppercase;letter-spacing:.05em}@media(max-width:900px){.iws-b2b-home-promo{grid-template-columns:1fr;padding:26px}}';
	wp_add_inline_style( 'iws-b2b-wholesale', $home_css );
	$fix_js = "document.addEventListener('click',function(e){var btn=e.target.closest('.iws-b2b-show-password,.woocommerce form .show-password-input');if(!btn)return;var wrap=btn.closest('.iws-b2b-password-wrap,.password-input')||btn.parentNode;var input=wrap?wrap.querySelector('input[type=\\\"password\\\"],input[type=\\\"text\\\"]'):null;if(!input)return;e.preventDefault();var showing=input.type==='text';input.type=showing?'password':'text';btn.setAttribute('aria-pressed',showing?'false':'true');btn.setAttribute('aria-label',showing?'Show password':'Hide password');});";
	wp_add_inline_script( 'iws-b2b-wholesale', $fix_js );

}


/* ---- 3.8 B2B demo lab, product trade pricing and account intelligence ---- */
add_action( 'woocommerce_product_options_pricing', 'iws_b2b_product_pricing_fields' );
add_action( 'woocommerce_process_product_meta', 'iws_b2b_save_product_pricing_fields' );
add_action( 'woocommerce_variation_options_pricing', 'iws_b2b_variation_pricing_field', 20, 3 );
add_action( 'woocommerce_save_product_variation', 'iws_b2b_save_variation_pricing_field', 20, 2 );
add_action( 'show_user_profile', 'iws_b2b_user_trade_fields' );
add_action( 'edit_user_profile', 'iws_b2b_user_trade_fields' );
add_action( 'personal_options_update', 'iws_b2b_save_user_trade_fields' );
add_action( 'edit_user_profile_update', 'iws_b2b_save_user_trade_fields' );
add_action( 'admin_menu', 'iws_b2b_demo_lab_menu', 72 );
add_action( 'admin_post_iws_b2b_create_demo', 'iws_b2b_create_demo' );
add_action( 'admin_post_iws_b2b_reset_demo', 'iws_b2b_reset_demo' );
add_action( 'woocommerce_check_cart_items', 'iws_b2b_validate_product_minimums', 30 );

function iws_b2b_product_pricing_fields(){
    woocommerce_wp_text_input(array('id'=>'_iws_b2b_wholesale_price','label'=>__('B2B wholesale price','wp-theme-woo-support'),'data_type'=>'price','description'=>__('Optional fixed trade price. When set it takes priority over the global percentage discount.','wp-theme-woo-support'),'desc_tip'=>true));
    woocommerce_wp_text_input(array('id'=>'_iws_b2b_min_qty','label'=>__('B2B minimum quantity','wp-theme-woo-support'),'type'=>'number','custom_attributes'=>array('min'=>'1','step'=>'1'),'description'=>__('Optional minimum quantity for wholesale customers.','wp-theme-woo-support'),'desc_tip'=>true));
    woocommerce_wp_text_input(array('id'=>'_iws_b2b_case_pack','label'=>__('B2B case / pack size','wp-theme-woo-support'),'type'=>'number','custom_attributes'=>array('min'=>'1','step'=>'1'),'description'=>__('Shown in the B2B portal and quick-order guidance.','wp-theme-woo-support'),'desc_tip'=>true));
}
function iws_b2b_save_product_pricing_fields($post_id) {
    if (!current_user_can('edit_post',$post_id)) return; // Woo verifies the product form nonce before this action.
    foreach (['_iws_b2b_wholesale_price','_iws_b2b_min_qty','_iws_b2b_case_pack'] as $key) {
        if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) continue;
        $raw=trim((string)wp_unslash($_POST[$key]));
        $value=$raw===''?'':($key==='_iws_b2b_wholesale_price'?wc_format_decimal(max(0,(float)wc_format_decimal($raw))):absint($raw));
        update_post_meta($post_id,$key,$value);
    }
    wc_delete_product_transients($post_id);
}
function iws_b2b_variation_pricing_field($loop,$variation_data,$variation) {
    foreach (['_iws_b2b_wholesale_price'=>'B2B price','_iws_b2b_min_qty'=>'B2B minimum quantity','_iws_b2b_case_pack'=>'B2B case / pack size'] as $key=>$label) {
        $args=['id'=>$key.'_'.$variation->ID,'name'=>$key.'['.$variation->ID.']','value'=>get_post_meta($variation->ID,$key,true),'label'=>__($label,'wp-theme-woo-support'),'wrapper_class'=>'form-row form-row-full','description'=>'Leave blank to inherit the parent product value.','desc_tip'=>true];
        if ($key==='_iws_b2b_wholesale_price') $args['data_type']='price';
        else { $args['type']='number'; $args['custom_attributes']=['min'=>'0','step'=>'1']; }
        woocommerce_wp_text_input($args);
    }
}
function iws_b2b_save_variation_pricing_field($variation_id,$i) {
    if (!current_user_can('edit_post',$variation_id)) return; // Woo validates the variation save request.
    foreach (['_iws_b2b_wholesale_price','_iws_b2b_min_qty','_iws_b2b_case_pack'] as $key) {
        if (!isset($_POST[$key][$variation_id]) || !is_scalar($_POST[$key][$variation_id])) continue;
        $raw=trim((string)wp_unslash($_POST[$key][$variation_id]));
        update_post_meta($variation_id,$key,$raw===''?'':($key==='_iws_b2b_wholesale_price'?wc_format_decimal(max(0,(float)wc_format_decimal($raw))):absint($raw)));
    }
    wc_delete_product_transients($variation_id); $parent=wp_get_post_parent_id($variation_id); if ($parent) wc_delete_product_transients($parent);
}
function iws_b2b_user_trade_fields($user){if(!current_user_can('manage_woocommerce') || !current_user_can('edit_user',$user->ID))return;wp_nonce_field('iws_b2b_user_'.$user->ID,'iws_b2b_user_nonce');?><h2><?php esc_html_e('B2B account','wp-theme-woo-support');?></h2><p>Discount % affects pricing. Credit limit, payment terms and the tax-exempt flag below are informational records only: this module does not enforce credit, issue invoices or validate/apply a VAT exemption.</p><table class="form-table"><tr><th><label for="iws_b2b_discount_percent">Discount %</label></th><td><input type="number" step="0.01" min="0" max="100" name="iws_b2b_discount_percent" value="<?php echo esc_attr(get_user_meta($user->ID,'iws_b2b_discount_percent',true));?>"></td></tr><tr><th>Credit limit</th><td><input type="number" step="0.01" min="0" name="iws_b2b_credit_limit" value="<?php echo esc_attr(get_user_meta($user->ID,'iws_b2b_credit_limit',true));?>"></td></tr><tr><th>Payment terms</th><td><input type="text" name="iws_b2b_payment_terms" value="<?php echo esc_attr(get_user_meta($user->ID,'iws_b2b_payment_terms',true));?>" placeholder="Net 30"></td></tr><tr><th>Tax exempt</th><td><label><input type="checkbox" name="iws_b2b_tax_exempt" value="yes" <?php checked(get_user_meta($user->ID,'iws_b2b_tax_exempt',true),'yes');?>> Approved tax-exempt account</label></td></tr></table><?php }
function iws_b2b_save_user_trade_fields($user_id) {
    if (!current_user_can('manage_woocommerce') || !current_user_can('edit_user',$user_id) || empty($_POST['iws_b2b_user_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['iws_b2b_user_nonce'])),'iws_b2b_user_'.$user_id)) return;
    $discount=trim((string)wp_unslash($_POST['iws_b2b_discount_percent']??''));
    update_user_meta($user_id,'iws_b2b_discount_percent',$discount===''?'':wc_format_decimal(min(100,max(0,(float)wc_format_decimal($discount)))));
    update_user_meta($user_id,'iws_b2b_credit_limit',wc_format_decimal(max(0,(float)wc_format_decimal($_POST['iws_b2b_credit_limit']??''))));
    update_user_meta($user_id,'iws_b2b_payment_terms',sanitize_text_field(wp_unslash($_POST['iws_b2b_payment_terms']??'')));
    update_user_meta($user_id,'iws_b2b_tax_exempt',isset($_POST['iws_b2b_tax_exempt'])?'yes':'no');
}
function iws_b2b_validate_product_minimums() {
    foreach (iws_b2b_rule_errors(WC()->cart, true) as $message) if (!wc_has_notice($message, 'error')) wc_add_notice($message, 'error');
}
function iws_b2b_demo_lab_menu(){add_submenu_page('woocommerce',__('B2B Demo Lab','wp-theme-woo-support'),__('B2B Demo Lab','wp-theme-woo-support'),'manage_woocommerce','wpbb-b2b-demo','iws_b2b_demo_lab_page');}
function iws_b2b_create_demo(){if(!current_user_can('manage_woocommerce'))wp_die('Denied');check_admin_referer('iws_b2b_create_demo');iws_b2b_register_roles();$clients=array(array('wpbb_trade_bronze','Bronze Trade Ltd',5,2500,'Net 14'),array('wpbb_trade_silver','Silver Installers Ltd',10,7500,'Net 30'),array('wpbb_trade_gold','Gold Projects Ltd',15,20000,'Net 45'));foreach($clients as$c){$email=$c[0].'@example.test';$user=get_user_by('login',$c[0]);if(!$user){$id=wp_create_user($c[0],wp_generate_password(24,true,true),$email);if(is_wp_error($id))continue;$user=get_user_by('id',$id);} $user->set_role('wholesale_customer');update_user_meta($user->ID,'iws_b2b_company',$c[1]);update_user_meta($user->ID,'iws_b2b_discount_percent',$c[2]);update_user_meta($user->ID,'iws_b2b_credit_limit',$c[3]);update_user_meta($user->ID,'iws_b2b_payment_terms',$c[4]);update_user_meta($user->ID,'_wpbb_b2b_demo','1');}
$products=wc_get_products(array('limit'=>12,'status'=>array('publish','draft')));foreach($products as$i=>$p){$retail=(float)$p->get_regular_price();if($retail>0){update_post_meta($p->get_id(),'_iws_b2b_wholesale_price',wc_format_decimal($retail*(.82+($i%3)*.03)));update_post_meta($p->get_id(),'_iws_b2b_min_qty',($i%3)+2);update_post_meta($p->get_id(),'_iws_b2b_case_pack',($i%4+1)*2);update_post_meta($p->get_id(),'_wpbb_b2b_demo','1');}}
$page=get_page_by_path('b2b-portal');if(!$page){$page_id=wp_insert_post(array('post_title'=>'B2B Portal','post_name'=>'b2b-portal','post_type'=>'page','post_status'=>'publish','post_content'=>'[iws_b2b_dashboard]'));if($page_id)update_post_meta($page_id,'_wpbb_b2b_demo','1');}$settings=iws_b2b_get_settings();$settings['enabled']='yes';$settings['enable_discount']='yes';if((float)$settings['discount_percent']<=0)$settings['discount_percent']=5;update_option('iws_b2b_settings',$settings,false);wp_safe_redirect(admin_url('admin.php?page=wpbb-b2b-demo&created=1'));exit;}
function iws_b2b_reset_demo(){if(!current_user_can('manage_woocommerce'))wp_die('Denied');check_admin_referer('iws_b2b_reset_demo');if(!function_exists('wp_delete_user'))require_once ABSPATH.'wp-admin/includes/user.php';$users=get_users(array('meta_key'=>'_wpbb_b2b_demo','meta_value'=>'1'));foreach($users as$u)wp_delete_user($u->ID);$products=get_posts(array('post_type'=>'product','post_status'=>'any','fields'=>'ids','posts_per_page'=>100,'meta_key'=>'_wpbb_b2b_demo','meta_value'=>'1'));foreach($products as$id){delete_post_meta($id,'_iws_b2b_wholesale_price');delete_post_meta($id,'_iws_b2b_min_qty');delete_post_meta($id,'_iws_b2b_case_pack');delete_post_meta($id,'_wpbb_b2b_demo');}$pages=get_posts(array('post_type'=>'page','post_status'=>'any','fields'=>'ids','posts_per_page'=>20,'meta_key'=>'_wpbb_b2b_demo','meta_value'=>'1'));foreach($pages as$id)wp_delete_post($id,true);wp_safe_redirect(admin_url('admin.php?page=wpbb-b2b-demo&reset=1'));exit;}
function iws_b2b_demo_lab_page(){if(!current_user_can('manage_woocommerce'))return;$checks=array('B2B module loaded'=>function_exists('iws_b2b_get_settings'),'Wholesale role exists'=>(bool)get_role('wholesale_customer'),'B2B enabled'=>iws_b2b_is_enabled(),'Portal page'=>(bool)get_page_by_path('b2b-portal'));$demo_users=get_users(array('meta_key'=>'_wpbb_b2b_demo','meta_value'=>'1'));$demo_products=get_posts(array('post_type'=>'product','post_status'=>'any','fields'=>'ids','posts_per_page'=>30,'meta_key'=>'_wpbb_b2b_demo','meta_value'=>'1'));?><div class="wrap"><h1>B2B Portal & Pricing — Demo Lab</h1><p>Create safe local/demo B2B clients, product-specific trade prices, minimum quantities and a B2B Portal page. This is separate from live customers.</p><div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;max-width:1300px"><div class="postbox"><div class="inside"><h2>Health checks</h2><table class="widefat striped"><?php foreach($checks as$k=>$ok):?><tr><th><?php echo esc_html($k);?></th><td><?php echo $ok?'✅ Ready':'⚠ Needs setup';?></td></tr><?php endforeach;?></table><p><strong>Demo clients:</strong> <?php echo count($demo_users);?> &nbsp; <strong>Demo-priced products:</strong> <?php echo count($demo_products);?></p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="iws_b2b_create_demo"><?php wp_nonce_field('iws_b2b_create_demo');?><?php submit_button('Create / refresh B2B demo','primary','submit',false);?></form> <form style="display:inline-block" method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="iws_b2b_reset_demo"><?php wp_nonce_field('iws_b2b_reset_demo');?><button class="button">Remove B2B demo data</button></form></div></div><div class="postbox"><div class="inside"><h2>Demo client tiers</h2><table class="widefat striped"><tr><th>Client</th><th>Discount</th><th>Credit</th><th>Terms</th></tr><?php foreach($demo_users as$u):?><tr><td><?php echo esc_html(get_user_meta($u->ID,'iws_b2b_company',true));?></td><td><?php echo esc_html(get_user_meta($u->ID,'iws_b2b_discount_percent',true));?>%</td><td><?php echo wp_kses_post(wc_price((float)get_user_meta($u->ID,'iws_b2b_credit_limit',true)));?></td><td><?php echo esc_html(get_user_meta($u->ID,'iws_b2b_payment_terms',true));?></td></tr><?php endforeach;?></table><p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=iws-b2b-wholesale'));?>">Open B2B settings</a> <?php if($page=get_page_by_path('b2b-portal')):?><a class="button" target="_blank" href="<?php echo esc_url(get_permalink($page));?>">Open B2B portal</a><?php endif;?></p></div></div></div></div><?php }

add_filter('option_page_capability_iws_b2b_settings_group', function () { return 'manage_woocommerce'; });
