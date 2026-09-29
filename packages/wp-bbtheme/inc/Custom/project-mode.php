<?php
/**
 * Project mode and starter-site dependency orchestration.
 *
 * The parent stays presentation-neutral. Child themes declare whether they are
 * a business, WooCommerce, or real-estate project and this file coordinates
 * only the dependencies and starter import workflow required by that mode.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the installed WP BBuilder plugin path without forcing projects that
 * used the historical GitHub archive directory to rename it during an update.
 */
function wp_theme_bbuilder_plugin_file() {
    $candidates = array(
        'wp-bbuilder/wp-bbuilder.php',
        'wp-bbuilder-master/wp-bbuilder.php',
    );

    if ( function_exists( 'is_plugin_active' ) ) {
        foreach ( $candidates as $candidate ) {
            if ( is_plugin_active( $candidate ) ) return $candidate;
        }
    }
    foreach ( $candidates as $candidate ) {
        if ( is_readable( WP_PLUGIN_DIR . '/' . $candidate ) ) return $candidate;
    }
    return $candidates[0];
}


/**
 * Resolve WP Theme Woo Support by its actual installed plugin basename.
 *
 * Uploaded GitHub archives commonly install as wp-theme-woo-support-master,
 * while release ZIPs use wp-theme-woo-support. WordPress activation state is
 * keyed by that directory-qualified basename, so hard-coding only one path
 * makes an already-active plugin appear missing. Prefer an active matching
 * copy, then any installed matching copy, and only fall back to the canonical
 * release path when the plugin is genuinely absent.
 */
function wp_theme_woo_support_plugin_file() {
    $canonical = 'wp-theme-woo-support/wp-theme-woo-support.php';
    $candidates = array(
        $canonical,
        'wp-theme-woo-support-master/wp-theme-woo-support.php',
    );

    if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = function_exists( 'get_plugins' ) ? get_plugins() : array();
    foreach ( $plugins as $plugin_file => $plugin_data ) {
        $file_name = basename( (string) $plugin_file );
        $name = isset( $plugin_data['Name'] ) ? trim( (string) $plugin_data['Name'] ) : '';
        $text_domain = isset( $plugin_data['TextDomain'] ) ? sanitize_key( (string) $plugin_data['TextDomain'] ) : '';

        if (
            'wp-theme-woo-support.php' === $file_name &&
            ( 'wp-theme-woo-support' === $text_domain || 'WP Theme Woo Support' === $name )
        ) {
            $candidates[] = $plugin_file;
        }
    }

    $candidates = array_values( array_unique( array_filter( $candidates ) ) );

    foreach ( $candidates as $candidate ) {
        if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $candidate ) ) {
            return $candidate;
        }
    }

    foreach ( $candidates as $candidate ) {
        if ( isset( $plugins[ $candidate ] ) || is_readable( WP_PLUGIN_DIR . '/' . $candidate ) ) {
            return $candidate;
        }
    }

    return $canonical;
}

function wp_theme_project_mode() {
	$stylesheet = wp_get_theme()->get_stylesheet();
	$detected   = array(
		'wp-bbtheme-child-woo-tech-shop' => 'woocommerce',
		'wp-bbtheme-child-woo-clouthes'  => 'woocommerce',
		'wp-bbtheme-child-woo-events'   => 'woocommerce',
		'wp-bbtheme-child-realestate'    => 'realestate',
		'wp-bbtheme-child-medicine'      => 'medicine',
		'wp-bbtheme-child'               => 'business',
	);
	if ( function_exists( 'wp_theme_is_current_suite_theme' ) && ! wp_theme_is_current_suite_theme() ) {
		$mode = 'legacy';
	} else {
		$mode = isset( $detected[ $stylesheet ] ) ? $detected[ $stylesheet ] : 'business';
	}

	return sanitize_key( apply_filters( 'wp_theme_project_mode', $mode ) );
}

function wp_theme_project_mode_label( $mode = '' ) {
	$mode = $mode ? sanitize_key( $mode ) : wp_theme_project_mode();
	$labels = array(
		'business'    => __( 'Business / general website', 'wp-theme' ),
		'woocommerce' => __( 'WooCommerce store', 'wp-theme' ),
		'realestate'  => __( 'Real estate website', 'wp-theme' ),
		'medicine'    => __( 'Medical / doctors directory', 'wp-theme' ),
		'legacy'      => __( 'Legacy / bespoke child theme', 'wp-theme' ),
	);

	return isset( $labels[ $mode ] ) ? $labels[ $mode ] : ucfirst( $mode );
}

function wp_theme_project_dependencies( $mode = '' ) {
	$mode = $mode ? sanitize_key( $mode ) : wp_theme_project_mode();
	if ( 'legacy' === $mode ) return apply_filters( 'wp_theme_project_dependencies', array(), $mode );
	$dependencies = array(
		wp_theme_bbuilder_plugin_file() => array(
			'name'        => __( 'WP BBuilder', 'wp-theme' ),
			'repository'  => '',
			'description' => __( 'Required for the starter layouts and WP BBuilder blocks used by these themes.', 'wp-theme' ),
		),
	);


	if ( 'woocommerce' === $mode ) {
		$dependencies['woocommerce/woocommerce.php'] = array(
			'name'        => __( 'WooCommerce', 'wp-theme' ),
			'repository'  => 'woocommerce',
			'description' => __( 'Required ecommerce engine.', 'wp-theme' ),
		);
		$dependencies[ wp_theme_woo_support_plugin_file() ] = array(
			'name'        => __( 'WP Theme Woo Support', 'wp-theme' ),
			'repository'  => '',
			'description' => __( 'Required store integration, filters, demo products, swatches and product UX layer.', 'wp-theme' ),
		);
	}

	return apply_filters( 'wp_theme_project_dependencies', $dependencies, $mode );
}

function wp_theme_project_load_plugin_api() {
	if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
}

function wp_theme_project_plugin_state( $plugin_file ) {
	wp_theme_project_load_plugin_api();
	$plugins = get_plugins();
	return array(
		'installed' => isset( $plugins[ $plugin_file ] ) || is_readable( WP_PLUGIN_DIR . '/' . $plugin_file ),
		'active'    => is_plugin_active( $plugin_file ),
	);
}

function wp_theme_project_dependencies_ready( $mode = '' ) {
	foreach ( wp_theme_project_dependencies( $mode ) as $plugin_file => $dependency ) {
		$state = wp_theme_project_plugin_state( $plugin_file );
		if ( empty( $state['active'] ) ) {
			return false;
		}
	}
	return true;
}

function wp_theme_project_dependency_action_url( $plugin_file, $dependency ) {
	$state = wp_theme_project_plugin_state( $plugin_file );
	if ( ! empty( $state['active'] ) ) {
		return '';
	}
	if ( ! empty( $state['installed'] ) ) {
		return wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin_file ) ),
			'activate-plugin_' . $plugin_file
		);
	}
	if ( ! empty( $dependency['repository'] ) ) {
		$slug = sanitize_key( $dependency['repository'] );
		return wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
	}
	return self_admin_url( 'plugin-install.php?tab=upload' );
}

function wp_theme_project_woo_transition_state( $mode = '' ) {
	wp_theme_project_load_plugin_api();
	$mode = $mode ? sanitize_key( $mode ) : wp_theme_project_mode();
	$woo_active = is_plugin_active( 'woocommerce/woocommerce.php' );
	$support_file = wp_theme_woo_support_plugin_file();
	$support_active = is_plugin_active( $support_file );
	$needs_store = 'woocommerce' === $mode && ( ! $woo_active || ! $support_active );
	$needs_non_store = in_array( $mode, array( 'business', 'realestate', 'medicine' ), true ) && ( $woo_active || $support_active );
	return array(
		'mode' => $mode,
		'woo_active' => $woo_active,
		'support_active' => $support_active,
		'needs_confirmation' => $needs_store || $needs_non_store,
		'action' => $needs_store ? 'enable' : ( $needs_non_store ? 'disable' : '' ),
	);
}

/**
 * Theme switching never silently toggles WooCommerce.
 * WP BBuilder may be activated because it is the suite renderer, while a
 * Woo/non-Woo state change is staged behind an explicit admin confirmation.
 */
function wp_theme_project_prepare_dependencies_after_switch() {
	if ( ! is_admin() ) {
		return;
	}
	if ( 'legacy' === wp_theme_project_mode() ) {
		delete_transient( 'wp_theme_project_pending_transition' );
		delete_transient( 'wp_theme_demo_refresh_notice' );
		return;
	}
	wp_theme_project_load_plugin_api();
	$builder_file = wp_theme_bbuilder_plugin_file();
	$builder = wp_theme_project_plugin_state( $builder_file );
	if ( ! empty( $builder['installed'] ) && empty( $builder['active'] ) ) {
		$result = activate_plugin( $builder_file, '', false, true );
		if ( is_wp_error( $result ) ) {
			set_transient( 'wp_theme_project_mode_notice', array( 'type' => 'error', 'message' => $result->get_error_message() ), 120 );
		}
	}

	$state = wp_theme_project_woo_transition_state();
	if ( ! empty( $state['needs_confirmation'] ) ) {
		set_transient( 'wp_theme_project_pending_transition', array(
			'action' => $state['action'],
			'mode' => $state['mode'],
			'theme' => wp_get_theme()->get_stylesheet(),
		), DAY_IN_SECONDS );
	} else {
		delete_transient( 'wp_theme_project_pending_transition' );
	}

	$profile = function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array();
	$current_demo = sanitize_key( (string) get_option( 'wp_theme_active_demo_profile', '' ) );
	$next_demo = sanitize_key( (string) ( $profile['id'] ?? '' ) );
	if ( $current_demo && $next_demo && $current_demo !== $next_demo ) {
		set_transient( 'wp_theme_demo_refresh_notice', array( 'from' => $current_demo, 'to' => $next_demo ), DAY_IN_SECONDS );
	}
}
add_action( 'after_switch_theme', 'wp_theme_project_prepare_dependencies_after_switch', 20 );

function wp_theme_project_transition_url( $action ) {
	return wp_nonce_url(
		admin_url( 'admin-post.php?action=wp_theme_apply_project_plugins&transition=' . rawurlencode( sanitize_key( $action ) ) ),
		'wp_theme_apply_project_plugins_' . sanitize_key( $action )
	);
}

function wp_theme_apply_project_plugins() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( esc_html__( 'You are not allowed to change plugin state.', 'wp-theme' ) );
	}
	$transition = isset( $_GET['transition'] ) ? sanitize_key( wp_unslash( $_GET['transition'] ) ) : '';
	check_admin_referer( 'wp_theme_apply_project_plugins_' . $transition );
	wp_theme_project_load_plugin_api();
	$errors = array();

	if ( 'enable' === $transition ) {
		$support_file = wp_theme_woo_support_plugin_file();
		foreach ( array( 'woocommerce/woocommerce.php' => 'WooCommerce', $support_file => 'WP Theme Woo Support' ) as $plugin_file => $name ) {
			$state = wp_theme_project_plugin_state( $plugin_file );
			if ( empty( $state['installed'] ) ) {
				$errors[] = sprintf( __( '%s is not installed yet.', 'wp-theme' ), $name );
				continue;
			}
			if ( empty( $state['active'] ) ) {
				$result = activate_plugin( $plugin_file, '', false, true );
				if ( is_wp_error( $result ) ) $errors[] = $name . ': ' . $result->get_error_message();
			}
		}
		if ( ! $errors && is_plugin_active( $support_file ) ) {
			update_option( 'wp_theme_woo_support_profile', 'store', false );
		}
	} elseif ( 'disable' === $transition ) {
		$disable = array();
		$support_file = wp_theme_woo_support_plugin_file();
		if ( is_plugin_active( $support_file ) ) $disable[] = $support_file;
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) $disable[] = 'woocommerce/woocommerce.php';
		if ( $disable ) deactivate_plugins( $disable, true );
	}

	delete_transient( 'wp_theme_project_pending_transition' );
	set_transient( 'wp_theme_project_mode_notice', array(
		'type' => $errors ? 'error' : 'success',
		'message' => $errors ? implode( ' ', $errors ) : ( 'enable' === $transition ? __( 'WooCommerce and WP Theme Woo Support are enabled for the active store theme.', 'wp-theme' ) : __( 'WooCommerce and WP Theme Woo Support are disabled for the active non-commerce theme. No store data was deleted.', 'wp-theme' ) ),
	), 120 );
	wp_safe_redirect( admin_url( 'themes.php?page=wp-theme-starter-setup' ) );
	exit;
}
add_action( 'admin_post_wp_theme_apply_project_plugins', 'wp_theme_apply_project_plugins' );

function wp_theme_dismiss_project_transition() {
	if ( ! current_user_can( 'activate_plugins' ) ) wp_die( esc_html__( 'Permission denied.', 'wp-theme' ) );
	check_admin_referer( 'wp_theme_dismiss_project_transition' );
	delete_transient( 'wp_theme_project_pending_transition' );
	wp_safe_redirect( admin_url( 'themes.php' ) );
	exit;
}
add_action( 'admin_post_wp_theme_dismiss_project_transition', 'wp_theme_dismiss_project_transition' );

function wp_theme_project_mode_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) return;

	$notice = get_transient( 'wp_theme_project_mode_notice' );
	if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
		delete_transient( 'wp_theme_project_mode_notice' );
		$type = ! empty( $notice['type'] ) ? sanitize_html_class( $notice['type'] ) : 'info';
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
	}

	$pending = get_transient( 'wp_theme_project_pending_transition' );
	if ( is_array( $pending ) && ! empty( $pending['action'] ) ) {
		$enable = 'enable' === $pending['action'];
		$title = $enable ? __( 'Enable the WooCommerce stack for this store theme?', 'wp-theme' ) : __( 'Disable the WooCommerce stack for this non-commerce theme?', 'wp-theme' );
		$text = $enable
			? __( 'The active child theme is a store. WooCommerce and WP Theme Woo Support should be enabled before importing its demo. Nothing will be changed until you confirm.', 'wp-theme' )
			: __( 'The active child theme is not a store. You can disable WooCommerce and WP Theme Woo Support for a cleaner runtime. Products and orders remain in the database and are not deleted.', 'wp-theme' );
		$confirm = $enable ? __( 'Enable WooCommerce stack', 'wp-theme' ) : __( 'Disable WooCommerce stack', 'wp-theme' );
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=wp_theme_dismiss_project_transition' ), 'wp_theme_dismiss_project_transition' );
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( $title ) . '</strong></p><p>' . esc_html( $text ) . '</p><p><a class="button button-primary" href="' . esc_url( wp_theme_project_transition_url( $pending['action'] ) ) . '">' . esc_html( $confirm ) . '</a> <a class="button" href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Keep current plugin state', 'wp-theme' ) . '</a></p></div>';
	}

	$refresh = get_transient( 'wp_theme_demo_refresh_notice' );
	if ( is_array( $refresh ) && ! empty( $refresh['to'] ) ) {
		delete_transient( 'wp_theme_demo_refresh_notice' );
		echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Refresh the active theme demo.', 'wp-theme' ) . '</strong> ' . esc_html__( 'The previous theme demo is still assigned as the front page. Starter Setup will refresh the shared pages and assign this child theme’s own Header, Utility and Footer menus without deleting unrelated content.', 'wp-theme' ) . '</p><p><a class="button button-primary" href="' . esc_url( admin_url( 'themes.php?page=wp-theme-starter-setup' ) ) . '">' . esc_html__( 'Open Starter Setup', 'wp-theme' ) . '</a></p></div>';
	}

	$missing = array();
	foreach ( wp_theme_project_dependencies() as $plugin_file => $dependency ) {
		$state = wp_theme_project_plugin_state( $plugin_file );
		if ( ! empty( $state['active'] ) ) continue;
		// Woo plugins are controlled by the explicit transition notice above.
		if ( in_array( $plugin_file, array( 'woocommerce/woocommerce.php', wp_theme_woo_support_plugin_file() ), true ) && is_array( $pending ) ) continue;
		$missing[] = array( $plugin_file, $dependency, $state );
	}
	if ( ! $missing ) return;

	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Theme setup needs attention.', 'wp-theme' ) . '</strong> ' . esc_html( sprintf( __( '%s requires the following project dependencies:', 'wp-theme' ), wp_theme_project_mode_label() ) ) . '</p><p>';
	foreach ( $missing as $item ) {
		list( $plugin_file, $dependency, $state ) = $item;
		$url = wp_theme_project_dependency_action_url( $plugin_file, $dependency );
		$label = ! empty( $state['installed'] ) ? sprintf( __( 'Activate %s', 'wp-theme' ), $dependency['name'] ) : ( ! empty( $dependency['repository'] ) ? sprintf( __( 'Install %s', 'wp-theme' ), $dependency['name'] ) : sprintf( __( 'Upload %s', 'wp-theme' ), $dependency['name'] ) );
		echo '<a class="button button-secondary" style="margin:0 8px 6px 0" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '<a class="button" href="' . esc_url( admin_url( 'themes.php?page=wp-theme-starter-setup' ) ) . '">' . esc_html__( 'Open Starter Setup', 'wp-theme' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'wp_theme_project_mode_notice', 20 );

function wp_theme_project_setup_menu() {
	add_theme_page(
		__( 'Starter Setup', 'wp-theme' ),
		__( 'Starter Setup', 'wp-theme' ),
		'edit_theme_options',
		'wp-theme-starter-setup',
		'wp_theme_project_setup_page'
	);
}
add_action( 'admin_menu', 'wp_theme_project_setup_menu', 25 );

function wp_theme_project_setup_import() {
	if ( empty( $_POST['wp_theme_starter_import'] ) ) {
		return null;
	}
	check_admin_referer( 'wp_theme_starter_import' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return new WP_Error( 'forbidden', __( 'You are not allowed to import theme demo content.', 'wp-theme' ) );
	}
	if ( ! wp_theme_project_dependencies_ready() ) {
		return new WP_Error( 'dependencies', __( 'Activate every required dependency before importing the starter site.', 'wp-theme' ) );
	}
	if ( ! function_exists( 'wp_theme_import_demo_homepage' ) ) {
		return new WP_Error( 'importer_missing', __( 'The starter importer is unavailable.', 'wp-theme' ) );
	}
	return wp_theme_import_demo_homepage();
}

function wp_theme_project_setup_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$mode      = wp_theme_project_mode();
	$profile   = function_exists( 'wp_theme_get_demo_profile' ) ? wp_theme_get_demo_profile() : array();
	$result    = wp_theme_project_setup_import();
	$ready     = wp_theme_project_dependencies_ready( $mode );
	$view_url  = home_url( '/' );
	$edit_url  = '';
	if ( is_wp_error( $result ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
	} elseif ( $result ) {
		$view_url = get_permalink( $result );
		$edit_url = get_edit_post_link( $result, 'raw' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( function_exists( 'wp_theme_demo_import_message' ) ? wp_theme_demo_import_message() : __( 'Starter website imported.', 'wp-theme' ) ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Starter Setup', 'wp-theme' ); ?></h1>
		<p><?php echo esc_html( sprintf( __( 'Active setup: %1$s%2$s', 'wp-theme' ), wp_theme_project_mode_label( $mode ), ! empty( $profile['name'] ) ? ' — ' . $profile['name'] : '' ) ); ?></p>
		<div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:24px;max-width:1120px">
			<div class="card" style="max-width:none;padding:22px">
				<h2><?php esc_html_e( 'Project dependencies', 'wp-theme' ); ?></h2>
				<table class="widefat striped"><tbody>
				<?php foreach ( wp_theme_project_dependencies( $mode ) as $plugin_file => $dependency ) : $state = wp_theme_project_plugin_state( $plugin_file ); ?>
					<tr><td><strong><?php echo esc_html( $dependency['name'] ); ?></strong><br><small><?php echo esc_html( $dependency['description'] ); ?></small></td><td style="width:130px"><?php echo ! empty( $state['active'] ) ? '<span style="color:#18794e;font-weight:700">' . esc_html__( 'Active', 'wp-theme' ) . '</span>' : ( ! empty( $state['installed'] ) ? esc_html__( 'Installed', 'wp-theme' ) : esc_html__( 'Missing', 'wp-theme' ) ); ?></td><td style="width:170px"><?php if ( empty( $state['active'] ) ) : ?><a class="button" href="<?php echo esc_url( wp_theme_project_dependency_action_url( $plugin_file, $dependency ) ); ?>"><?php echo esc_html( ! empty( $state['installed'] ) ? __( 'Activate', 'wp-theme' ) : ( ! empty( $dependency['repository'] ) ? __( 'Install', 'wp-theme' ) : __( 'Upload ZIP', 'wp-theme' ) ) ); ?></a><?php endif; ?></td></tr>
				<?php endforeach; ?>
				</tbody></table>

				<h2 style="margin-top:28px"><?php esc_html_e( 'Starter website', 'wp-theme' ); ?></h2>
				<p><?php esc_html_e( 'Import or refresh the active child-theme demo. Existing demo items are updated where possible so the import can be safely re-run during development.', 'wp-theme' ); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'wp_theme_starter_import' ); ?>
					<input type="hidden" name="wp_theme_starter_import" value="1">
					<?php submit_button( __( 'Import / Refresh Starter Website', 'wp-theme' ), 'primary', 'submit', false, $ready ? array() : array( 'disabled' => 'disabled' ) ); ?>
					<?php if ( ! $ready ) : ?><p class="description"><?php esc_html_e( 'Complete the dependencies above before importing.', 'wp-theme' ); ?></p><?php endif; ?>
				</form>
				<?php if ( $result && ! is_wp_error( $result ) ) : ?><p><a class="button" href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View starter site', 'wp-theme' ); ?></a> <?php if ( $edit_url ) : ?><a class="button" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit homepage', 'wp-theme' ); ?></a><?php endif; ?></p><?php endif; ?>
			</div>
			<div class="card" style="max-width:none;padding:22px">
				<h2><?php esc_html_e( 'Performance defaults', 'wp-theme' ); ?></h2>
				<ul style="list-style:disc;padding-left:20px">
					<li><?php esc_html_e( 'Parent theme has no frontend presentation CSS.', 'wp-theme' ); ?></li>
					<li><?php esc_html_e( 'WP BBuilder uses per-block Bootstrap component loading in Auto mode.', 'wp-theme' ); ?></li>
					<li><?php esc_html_e( 'Woo filter assets load only on product/filter views.', 'wp-theme' ); ?></li>
					<li><?php esc_html_e( 'Optional libraries are loaded only when page content needs them.', 'wp-theme' ); ?></li>
				</ul>
				<p><a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'Customize theme', 'wp-theme' ); ?></a></p>
			</div>
		</div>
	</div>
	<?php
}
