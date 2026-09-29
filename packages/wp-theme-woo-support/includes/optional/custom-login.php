<?php

// Redirect logged-out /wp-admin/ requests to the customer My Account login page.
function redirect_wp_admin() {
	if ( is_user_logged_in() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path = parse_url( $request_uri, PHP_URL_PATH );
	$request_path = is_string( $request_path ) ? trailingslashit( $request_path ) : '';

	if ( 0 !== strpos( $request_path, '/wp-admin/' ) ) {
		return;
	}

	// Keep technical endpoints available for plugins and background requests.
	if ( false !== strpos( $request_path, '/wp-admin/admin-ajax.php' ) || false !== strpos( $request_path, '/wp-admin/admin-post.php' ) ) {
		return;
	}

	wp_safe_redirect( iws_my_account_url() );
	exit;
}
add_action( 'init', 'redirect_wp_admin', 0 );


/**
 * Prevent cached logged-out account pages being served immediately after login.
 *
 * Some page-cache layers can cache /my-account/ while the visitor is logged out.
 * Keep account/auth pages uncached so the customer sees the logged-in account
 * area immediately after authentication.
 */
function iws_is_account_auth_request() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	return false !== strpos( $request_uri, '/my-account' )
		|| false !== strpos( $request_uri, 'customer-logout' )
		|| false !== strpos( $request_uri, 'iws_customer_logout' )
		|| ! empty( $_GET['iws_customer_logout'] );
}

function iws_mark_account_auth_uncacheable() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( iws_is_account_auth_request() ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCDN' ) ) {
			define( 'DONOTCDN', true );
		}
	}
}
add_action( 'init', 'iws_mark_account_auth_uncacheable', 0 );

function iws_no_cache_account_auth_pages() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( iws_is_account_auth_request() || ( function_exists( 'is_account_page' ) && is_account_page() ) || is_page( array( 'my-account', 'b2b-login', 'b2b-dashboard' ) ) ) {
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
}
add_action( 'template_redirect', 'iws_no_cache_account_auth_pages', 0 );
add_action( 'send_headers', 'iws_no_cache_account_auth_pages', 0 );

function iws_exclude_account_urls_from_page_cache( $uris ) {
	$uris = (array) $uris;
	$uris[] = '/my-account/(.*)';
	$uris[] = '/b2b-login/(.*)';
	$uris[] = '/b2b-dashboard/(.*)';

	return array_unique( $uris );
}
add_filter( 'rocket_cache_reject_uri', 'iws_exclude_account_urls_from_page_cache', PHP_INT_MAX );
add_filter( 'wp_rocket_cache_reject_uri', 'iws_exclude_account_urls_from_page_cache', PHP_INT_MAX );

/**
 * Return the real My Account page ID even if the WooCommerce option is blank.
 *
 * The live database has an empty woocommerce_myaccount_page_id option while a
 * published page with the my-account slug exists. When that option is empty,
 * WooCommerce can generate endpoint links at the site root, for example
 * /edit-account/ instead of /my-account/edit-account/.
 */
function iws_my_account_page_id( $page_id = 0 ) {
	$page_id = absint( $page_id );

	if ( $page_id > 0 ) {
		return $page_id;
	}

	$page = get_page_by_path( 'my-account' );

	return ( $page instanceof WP_Post && 'page' === $page->post_type ) ? absint( $page->ID ) : 0;
}

function iws_filter_woocommerce_myaccount_page_id( $page_id ) {
	return iws_my_account_page_id( $page_id );
}
add_filter( 'option_woocommerce_myaccount_page_id', 'iws_filter_woocommerce_myaccount_page_id', PHP_INT_MAX );
add_filter( 'woocommerce_get_myaccount_page_id', 'iws_filter_woocommerce_myaccount_page_id', PHP_INT_MAX );

/**
 * Return the real My Account URL with a safe hard-coded fallback.
 */
function iws_my_account_url() {
	$my_account_page_id = iws_my_account_page_id();
	$my_account_url     = $my_account_page_id ? get_permalink( $my_account_page_id ) : '';

	if ( empty( $my_account_url ) && function_exists( 'wc_get_page_permalink' ) ) {
		$my_account_url = wc_get_page_permalink( 'myaccount' );
	}

	if ( empty( $my_account_url ) || trailingslashit( $my_account_url ) === trailingslashit( home_url( '/' ) ) ) {
		$my_account_url = home_url( '/my-account/' );
	}

	return trailingslashit( $my_account_url );
}

function iws_user_login_destination( $user ) {
	if ( $user instanceof WP_User && in_array( 'wholesale_customer', (array) $user->roles, true ) ) {
		return home_url( '/b2b-dashboard/' );
	}

	return iws_my_account_url();
}

/**
 * Detect the front-end My Account login form only.
 */
function iws_is_my_account_login_submission() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}

	if ( empty( $_POST['login'] ) || empty( $_POST['username'] ) || empty( $_POST['password'] ) ) {
		return false;
	}

	$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$my_account_url  = iws_my_account_url();
	$posted_redirect = '';

	if ( ! empty( $_POST['redirect'] ) ) {
		$posted_redirect = wp_validate_redirect( wp_unslash( $_POST['redirect'] ), '' );
	} elseif ( ! empty( $_POST['redirect_to'] ) ) {
		$posted_redirect = wp_validate_redirect( wp_unslash( $_POST['redirect_to'] ), '' );
	}

	return false !== strpos( $request_uri, '/my-account' ) || trailingslashit( $posted_redirect ) === trailingslashit( $my_account_url );
}

/**
 * Process the My Account login before WooCommerce's default handler.
 *
 * This avoids theme/plugin redirect conflicts that were sending the first login
 * request to the homepage, which made the customer appear logged out until they
 * submitted the form a second time.
 */
function iws_process_my_account_login_directly() {
	if ( ! iws_is_my_account_login_submission() ) {
		return;
	}

	if ( ! function_exists( 'wc_add_notice' ) ) {
		return;
	}

	/*
	 * Do not block the My Account login on WooCommerce's form nonce here. The
	 * live site is using page caching/preloading, which can serve an expired
	 * login nonce on the first page view. WordPress' own login form does not rely
	 * on a nonce; it authenticates the posted credentials and sets the auth
	 * cookie. Doing the same here prevents the false "Security check failed"
	 * first attempt while still requiring a valid username and password.
	 */
	$username = isset( $_POST['username'] ) ? trim( wp_unslash( $_POST['username'] ) ) : '';
	$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

	if ( '' === $username || '' === $password ) {
		wc_add_notice( __( 'Please enter your username/email and password.', 'woocommerce' ), 'error' );
		return;
	}

	$user = wp_signon(
		array(
			'user_login'    => $username,
			'user_password' => $password,
			'remember'      => ! empty( $_POST['rememberme'] ),
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		wc_add_notice( $user->get_error_message(), 'error' );
		return;
	}

	wp_set_current_user( $user->ID );

	if ( function_exists( 'wc_set_customer_auth_cookie' ) ) {
		wc_set_customer_auth_cookie( $user->ID );
	}

	do_action( 'woocommerce_login', $user->user_login, $user );

	wp_safe_redirect( iws_user_login_destination( $user ) );
	exit;
}
add_action( 'wp_loaded', 'iws_process_my_account_login_directly', 1 );

/**
 * Keep successful WooCommerce customer logins on the right account page.
 */
function iws_woocommerce_customer_login_redirect( $redirect, $user ) {
	return iws_user_login_destination( $user );
}
add_filter( 'woocommerce_login_redirect', 'iws_woocommerce_customer_login_redirect', PHP_INT_MAX, 2 );

/**
 * Keep wp-login.php/B2B form redirects stable too.
 */
function iws_login_redirect_for_roles( $redirect_to, $request, $user ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return $redirect_to;
	}

	if ( $user instanceof WP_User ) {
		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return admin_url();
		}
		return iws_user_login_destination( $user );
	}

	return $redirect_to;
}
add_filter( 'login_redirect', 'iws_login_redirect_for_roles', PHP_INT_MAX, 3 );

/**
 * Replace WooCommerce's /my-account/customer-logout/ endpoint URL with a query
 * based URL on /my-account/. This avoids 404s on installs where the endpoint is
 * not registered/flushed correctly.
 */
function iws_customer_logout_url( $url, $endpoint, $value, $permalink ) {
	if ( 'customer-logout' === $endpoint ) {
		return wp_nonce_url( add_query_arg( 'iws_customer_logout', '1', iws_my_account_url() ), 'customer-logout' );
	}

	$account_endpoints = array(
		'dashboard',
		'orders',
		'view-order',
		'downloads',
		'edit-account',
		'edit-address',
		'payment-methods',
		'add-payment-method',
		'lost-password',
		'b2b-dashboard',
	);

	if ( ! in_array( $endpoint, $account_endpoints, true ) ) {
		return $url;
	}

	$my_account_url = iws_my_account_url();

	// WooCommerce is expected to build these beneath /my-account/. If another
	// plugin/theme/database option makes them root-level, rebuild them here.
	if ( false !== strpos( trailingslashit( $url ), trailingslashit( $my_account_url ) ) ) {
		return $url;
	}

	if ( 'dashboard' === $endpoint ) {
		return $my_account_url;
	}

	$endpoint_url = trailingslashit( $my_account_url ) . trailingslashit( $endpoint );

	if ( '' !== (string) $value ) {
		$endpoint_url .= trailingslashit( rawurlencode( (string) $value ) );
	}

	return $endpoint_url;
}
add_filter( 'woocommerce_get_endpoint_url', 'iws_customer_logout_url', PHP_INT_MAX, 4 );

/**
 * Redirect accidental root-level WooCommerce account endpoints back under
 * /my-account/ so old cached links like /edit-account/ or /orders/ do not 404.
 */
function iws_redirect_root_account_endpoints_to_my_account() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	$request_path = trim( (string) $request_path, '/' );

	if ( '' === $request_path || false !== strpos( $request_path, 'my-account/' ) ) {
		return;
	}

	$parts    = explode( '/', $request_path );
	$endpoint = sanitize_title( $parts[0] );

	$account_endpoints = array(
		'orders',
		'view-order',
		'downloads',
		'edit-account',
		'edit-address',
		'payment-methods',
		'add-payment-method',
		'lost-password',
	);

	if ( ! in_array( $endpoint, $account_endpoints, true ) ) {
		return;
	}

	$target = trailingslashit( iws_my_account_url() ) . $endpoint . '/';

	if ( ! empty( $parts[1] ) ) {
		$target .= implode( '/', array_map( 'rawurlencode', array_slice( $parts, 1 ) ) ) . '/';
	}

	wp_safe_redirect( $target, 301 );
	exit;
}
add_action( 'template_redirect', 'iws_redirect_root_account_endpoints_to_my_account', 0 );

function iws_woocommerce_logout_url( $logout_url, $redirect = '' ) {
	return wp_nonce_url( add_query_arg( 'iws_customer_logout', '1', iws_my_account_url() ), 'customer-logout' );
}
add_filter( 'woocommerce_logout_url', 'iws_woocommerce_logout_url', PHP_INT_MAX, 2 );

/**
 * Directly handle both possible logout URLs and always return to /my-account/.
 */
function iws_handle_customer_logout_directly() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$is_logout   = ! empty( $_GET['iws_customer_logout'] ) || false !== strpos( $request_uri, 'customer-logout' );

	if ( ! $is_logout ) {
		return;
	}

	if ( is_user_logged_in() ) {
		wp_logout();
	}

	wp_safe_redirect( iws_my_account_url() );
	exit;
}
add_action( 'wp_loaded', 'iws_handle_customer_logout_directly', 0 );
add_action( 'template_redirect', 'iws_handle_customer_logout_directly', 0 );

function iws_my_account_logout_redirect_url( $redirect = '' ) {
	return iws_my_account_url();
}
add_filter( 'woocommerce_logout_default_redirect_url', 'iws_my_account_logout_redirect_url', PHP_INT_MAX );

function iws_wp_logout_redirect_to_my_account( $redirect_to, $requested_redirect_to, $user ) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return $redirect_to;
	}

	return iws_my_account_url();
}
add_filter( 'logout_redirect', 'iws_wp_logout_redirect_to_my_account', PHP_INT_MAX, 3 );

// Hide WordPress Login Errors (Security)
function custom_login_errors() {
	return 'Invalid login credentials.';
}
add_filter('login_errors', 'custom_login_errors');

// B2B - b2b-login page + [b2b_login_form]
add_action('init', function() {

    if ( function_exists('register_block_type') ) {
        register_block_type('custom/b2b-login', array(
            'render_callback' => 'render_b2b_login_block'
        ));
    }
});

// B2B Login Form Shortcode
function b2b_login_form_shortcode() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}


    // Already logged in
    if ( is_user_logged_in() ) {
        return '<p>You are already logged in.</p>';
    }

    // Login form arguments
    $args = array(
        'form_id'        => 'b2b-login-form',
        'label_username' => 'Email or Username',
        'label_password' => 'Password',
        'label_log_in'   => 'Login',
        'remember'       => true,
        'redirect'       => home_url('/b2b-dashboard/')
    );

    // Output the form HTML
    ob_start();
    ?>
    <div class="b2b-login-container">
        <?php wp_login_form($args); ?>
        <div class="b2b-login-links" style="margin-top:15px; text-align:center;">
            <a href="<?php echo wp_lostpassword_url( home_url('/b2b-login/') ); ?>">Forgot Password?</a> |
            <a href="<?php echo home_url('/b2b-register/'); ?>">Register</a>
        </div>
    </div>
    <?php
    return ob_get_clean(); // Return output buffer content
}
add_shortcode('b2b_login_form', 'b2b_login_form_shortcode');

// Add the B2B wholesale_customer Role with Capabilities
function add_wholesale_customer_role() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}


	// Check if role already exists to avoid duplicates
	if ( ! get_role('wholesale') ) {

		add_role(
			'wholesale_customer',                // Role slug
			'Wholesale Customer',               // Display name
			array(
				'read' => true,                    // Can read content
				'edit_posts' => false,             // Cannot edit posts
				'delete_posts' => false,           // Cannot delete posts
				'view_woocommerce_reports' => true // Optional WooCommerce permission
				// Add more capabilities as needed
			)
		);

	}
}
add_action('init', 'add_wholesale_customer_role');

// Redirect Based on Role After Login
function custom_login_redirect($redirect_to, $request, $user) {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return $redirect_to;
	}


	if ( isset($user->roles) && is_array($user->roles) ) {
		// Admins → WP Dashboard
		if ( in_array('administrator', $user->roles) ) {
			return admin_url();
		}
		// Wholesale/B2B users → B2B Dashboard
		if ( in_array('wholesale_customer', $user->roles) ) {
			return home_url('/b2b-dashboard/');
		}
		// Regular users → My Account
		return iws_my_account_url();
	}

	return $redirect_to;
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

// Only wholesale users can see B2B login page
function restrict_b2b_login_page() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( is_page('b2b-login') && is_user_logged_in() && !current_user_can('wholesale_customer') ) {
		wp_redirect( home_url('/my-account/') );
		exit;
	}
}
add_action('template_redirect', 'restrict_b2b_login_page');

// Only regular users see My Account login
function restrict_regular_login_page() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	if ( is_page('my-account') && is_user_logged_in() && current_user_can('wholesale_customer') ) {
		wp_redirect( home_url('/b2b-dashboard/') );
		exit;
	}
}
add_action('template_redirect', 'restrict_regular_login_page');

// Styling B2B Login Form
function b2b_login_styles() {
	if ( is_page('b2b-dashboard') ) {
		?>
		<style>
			.b2b-login-container {
				padding: 30px;
				background: #fff;
				border-radius: 8px;
				box-shadow: 0 5px 20px rgba(0,0,0,0.1);
				text-align: center;
			}
			.b2b-login-container form { display: flex; flex-direction: column; }
			.b2b-login-container input[type="text"],
			.b2b-login-container input[type="password"] { padding:10px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc; }
			.b2b-login-container input[type="submit"] { background:#0073aa; color:#fff; padding:10px; border:none; border-radius:4px; cursor:pointer; }
			.b2b-login-container input[type="submit"]:hover { background:#005177; }
			.b2b-login-links a { color:#0073aa; text-decoration:none; margin:0 5px; }
			.b2b-login-links a:hover { text-decoration:underline; }
		</style>
		<?php
	}
}
add_action('wp_head', 'b2b_login_styles');

// Add My Account custom menu items
function custom_my_account_menu_items($items) {
	// WP Admin link for admins
	if ( current_user_can('administrator') ) {
		$items['../wp-admin'] = 'WP Admin';
	}
	// B2B Dashboard for wholesale users
	if ( current_user_can('wholesale_customer') ) {
		$items['b2b-dashboard'] = 'B2B Dashboard';
	}
	// Special Offers for all logged-in users
	if ( is_user_logged_in() ) {
		$items['../products'] = 'Special Offers';
	}
	return $items;
}
add_filter('woocommerce_account_menu_items', 'custom_my_account_menu_items');

// Register Endpoints
function custom_add_my_account_endpoints() {
    add_rewrite_endpoint('wp-admin', EP_PAGES);
    add_rewrite_endpoint('b2b-dashboard', EP_PAGES);
    add_rewrite_endpoint('special-offers', EP_PAGES);
}
add_action('init', 'custom_add_my_account_endpoints');

// Hide Links from Users Without Access
add_filter('woocommerce_account_menu_items', function($items) {
    if ( !current_user_can('wholesale_customer') ) {
        unset($items['b2b-dashboard']);		
    }
    return $items;
}, 99);
