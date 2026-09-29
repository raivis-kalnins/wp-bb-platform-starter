<?php
/**
 * Classic navigation enhancements shared by every child theme.
 *
 * Presentation deliberately stays in child themes. This file only exposes
 * menu locations, menu-level settings, semantic classes and optional mega-menu
 * content so /wp-admin/nav-menus.php remains the source of truth.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_theme_truthy' ) ) {
	function wp_theme_truthy( $value ) {
		return in_array( $value, array( true, 1, '1', 'true', 'yes', 'on' ), true );
	}
}

if ( ! function_exists( 'wp_theme_nav_menu_object_for_location' ) ) {
	function wp_theme_nav_menu_object_for_location( $location = 'wp-header-menu' ) {
		$locations = get_nav_menu_locations();
		if ( empty( $locations[ $location ] ) ) {
			return false;
		}
		return wp_get_nav_menu_object( (int) $locations[ $location ] );
	}
}

if ( ! function_exists( 'wp_theme_nav_menu_setting' ) ) {
	function wp_theme_nav_menu_setting( $name, $default = false, $location = 'wp-header-menu' ) {
		$menu = wp_theme_nav_menu_object_for_location( $location );
		if ( ! $menu ) {
			return $default;
		}

		if ( function_exists( 'wp_theme_acf_ready' ) && wp_theme_acf_ready() ) {
			$value = get_field( $name, $menu );
			if ( null !== $value && false !== $value && '' !== $value ) {
				return $value;
			}
		}

		$value = get_term_meta( $menu->term_id, '_wp_theme_menu_' . sanitize_key( $name ), true );
		return '' === $value ? $default : $value;
	}
}

if ( ! function_exists( 'wp_theme_update_nav_menu_setting' ) ) {
	function wp_theme_update_nav_menu_setting( $menu_id, $name, $value ) {
		$menu = wp_get_nav_menu_object( (int) $menu_id );
		if ( ! $menu ) {
			return;
		}

		// Always keep a core term-meta fallback. ACF reads/writes are mirrored
		// when available so projects can safely disable ACF without losing intent.
		update_term_meta( $menu->term_id, '_wp_theme_menu_' . sanitize_key( $name ), $value ? '1' : '0' );
		if ( function_exists( 'wp_theme_acf_ready' ) && wp_theme_acf_ready() && function_exists( 'update_field' ) ) {
			update_field( $name, $value ? 1 : 0, $menu );
		}
	}
}

/** Register the old Main Menu Extras controls for every child theme. */
function wp_theme_register_nav_menu_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// Older projects (including the Business child) may already ship the
	// original JSON field group. Reuse it rather than displaying duplicates.
	if ( function_exists( 'acf_get_field_groups' ) ) {
		foreach ( (array) acf_get_field_groups() as $group ) {
			if ( ! empty( $group['title'] ) && 'Main Menu Extras' === $group['title'] && 'group_wp_theme_main_menu_extras_v2' !== ( $group['key'] ?? '' ) ) {
				return;
			}
		}
	}

	$toggle = static function ( $key, $label, $name, $default = 0 ) {
		return array(
			'key'           => $key,
			'label'         => $label,
			'name'          => $name,
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => $default,
			'wrapper'       => array( 'width' => '33' ),
		);
	};

	acf_add_local_field_group( array(
		'key'      => 'group_wp_theme_main_menu_extras_v2',
		'title'    => __( 'Main Menu Extras', 'wp-theme' ),
		'fields'   => array(
			$toggle( 'field_wp_theme_menu_search_bar', __( 'Search Bar', 'wp-theme' ), 'search_bar' ),
			$toggle( 'field_wp_theme_menu_customer_account', __( 'Customer Account', 'wp-theme' ), 'customer_account' ),
			$toggle( 'field_wp_theme_menu_mini_cart', __( 'Mini Cart', 'wp-theme' ), 'mini_cart' ),
			$toggle( 'field_wp_theme_menu_wishlist', __( 'Wishlist', 'wp-theme' ), 'wishlist' ),
			$toggle( 'field_wp_theme_menu_mega_menu', __( 'Mega Menu', 'wp-theme' ), 'mega_menu' ),
			$toggle( 'field_wp_theme_menu_last_button', __( 'Last Item as Button', 'wp-theme' ), 'last_button' ),
			$toggle( 'field_wp_theme_menu_light_dark', __( 'Light / Dark Toggle', 'wp-theme' ), 'light_dark' ),
			$toggle( 'field_wp_theme_menu_language_bar', __( 'Language Bar', 'wp-theme' ), 'language_bar' ),
			$toggle( 'field_wp_theme_menu_sticky_header', __( 'Sticky Header', 'wp-theme' ), 'sticky_header', 1 ),
		),
		'location' => array(
			array(
				array(
					'param'    => 'nav_menu',
					'operator' => '==',
					'value'    => 'location/wp-header-menu',
				),
			),
		),
		'position' => 'side',
		'show_in_rest' => 0,
	) );
}
add_action( 'acf/init', 'wp_theme_register_nav_menu_acf_fields', 20 );

/**
 * Expose old menu settings as stable classes instead of printing inline JS.
 * This is faster, CSP-friendly and lets every child decide the actual design.
 */
function wp_theme_menu_body_classes( $classes ) {
	$map = array(
		'search_bar'       => 'wp-nav-menu__search_bar',
		'customer_account' => 'wp-nav-menu__account',
		'mini_cart'        => 'wp-nav-menu__cart',
		'wishlist'         => 'wp-nav-menu__wishlist',
		'light_dark'       => 'wp-nav-menu__light-dark',
		'language_bar'     => 'wp-nav-menu__lang',
		'sticky_header'    => 'wp-nav-menu__sticky-header',
	);
	foreach ( $map as $field => $class ) {
		if ( wp_theme_truthy( wp_theme_nav_menu_setting( $field, false ) ) ) {
			$classes[] = $class;
		}
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'wp_theme_menu_body_classes' );

function wp_theme_menu_args_classes( $args ) {
	if ( empty( $args['theme_location'] ) || 'wp-header-menu' !== $args['theme_location'] ) {
		return $args;
	}
	$classes = preg_split( '/\s+/', isset( $args['menu_class'] ) ? (string) $args['menu_class'] : '' );
	$classes = array_filter( $classes );
	if ( wp_theme_truthy( wp_theme_nav_menu_setting( 'mega_menu', false ) ) ) {
		$classes[] = 'wp-nav-menu__megamenu';
	}
	if ( wp_theme_truthy( wp_theme_nav_menu_setting( 'last_button', false ) ) ) {
		$classes[] = 'wp-nav-menu__lastnavbtn';
	}
	$args['menu_class'] = implode( ' ', array_unique( $classes ) );
	return $args;
}
add_filter( 'wp_nav_menu_args', 'wp_theme_menu_args_classes' );

/** Safely preserve legacy per-item mega menu/image data when it exists. */
function wp_theme_menu_item_objects( $items, $args ) {
	if ( empty( $args->theme_location ) || 'wp-header-menu' !== $args->theme_location ) {
		return $items;
	}
	foreach ( $items as $item ) {
		$acf_ready    = function_exists( 'wp_theme_acf_ready' ) && wp_theme_acf_ready();
		$menu_img     = $acf_ready ? get_field( 'menu_img', $item ) : '';
		$mega_post_id = $acf_ready ? absint( get_field( 'mega_post_id', $item ) ) : 0;
		if ( ! $menu_img ) {
			$menu_img = absint( get_post_meta( $item->ID, '_wp_theme_menu_img', true ) );
		}
		if ( ! $mega_post_id ) {
			$mega_post_id = absint( get_post_meta( $item->ID, '_wp_theme_mega_post_id', true ) );
		}
		if ( ! $mega_post_id ) {
			$mega_post_id = absint( get_post_meta( $item->ID, 'mega_post_id', true ) );
		}
		if ( ! $menu_img && isset( $item->menu_img ) ) {
			$menu_img = $item->menu_img;
		}
		if ( ! $mega_post_id && isset( $item->mega_post_id ) ) {
			$mega_post_id = absint( $item->mega_post_id );
		}
		$image_url = is_array( $menu_img ) ? ( $menu_img['url'] ?? '' ) : wp_get_attachment_image_url( absint( $menu_img ), 'large' );
		if ( $image_url ) {
			$item->classes[] = 'menu-item-has-image';
			$item->wp_theme_menu_image = esc_url_raw( $image_url );
		}
		if ( $mega_post_id && 'publish' === get_post_status( $mega_post_id ) ) {
			$item->classes[] = 'menu-item-has-mega';
			$item->classes[] = 'menu-item-megamenu';
			$item->classes[] = 'megamenu';
			$item->wp_theme_mega_post_id = $mega_post_id;
		}
	}
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'wp_theme_menu_item_objects', 10, 2 );

function wp_theme_menu_item_link_attributes( $atts, $item, $args ) {
	if ( ! empty( $item->wp_theme_menu_image ) ) {
		$atts['data-menu-image'] = $item->wp_theme_menu_image;
	}
	if ( ! empty( $item->wp_theme_mega_post_id ) ) {
		$atts['aria-haspopup'] = 'true';
		$atts['data-mega-menu'] = (string) absint( $item->wp_theme_mega_post_id );
		$atts['aria-expanded'] = 'false';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'wp_theme_menu_item_link_attributes', 10, 3 );

function wp_theme_menu_item_mega_markup( $item_output, $item, $depth, $args ) {
	if ( 0 !== (int) $depth || empty( $item->wp_theme_mega_post_id ) || empty( $args->theme_location ) || 'wp-header-menu' !== $args->theme_location ) {
		return $item_output;
	}
	$content = get_post_field( 'post_content', $item->wp_theme_mega_post_id );
	if ( ! $content ) {
		return $item_output;
	}
	return $item_output . '<div class="wp-theme-mega-menu megamenu-modal tfa-mega-menu enterprise-megamenu" aria-hidden="true" data-mega-panel="' . absint( $item->wp_theme_mega_post_id ) . '">' . do_blocks( $content ) . '</div>';
}
add_filter( 'walker_nav_menu_start_el', 'wp_theme_menu_item_mega_markup', 10, 4 );

/**
 * Core-only fallback for projects that do not run ACF.
 * ACF remains the preferred editor when present, matching older projects.
 */
function wp_theme_nav_menu_native_settings_box() {
	if ( function_exists( 'acf' ) || function_exists( 'get_field' ) ) {
		return;
	}
	add_meta_box(
		'wp-theme-menu-extras',
		__( 'Main Menu Extras', 'wp-theme' ),
		'wp_theme_nav_menu_native_settings_box_render',
		'nav-menus',
		'side',
		'default'
	);
}
add_action( 'load-nav-menus.php', 'wp_theme_nav_menu_native_settings_box' );

function wp_theme_nav_menu_native_settings_box_render() {
	global $nav_menu_selected_id;
	$menu_id = absint( $nav_menu_selected_id );
	if ( ! $menu_id ) {
		echo '<p>' . esc_html__( 'Create or select a menu to configure header extras.', 'wp-theme' ) . '</p>';
		return;
	}
	wp_nonce_field( 'wp_theme_menu_extras_' . $menu_id, 'wp_theme_menu_extras_nonce' );
	$fields = array(
		'search_bar'       => __( 'Search Bar', 'wp-theme' ),
		'customer_account' => __( 'Customer Account', 'wp-theme' ),
		'mini_cart'        => __( 'Mini Cart', 'wp-theme' ),
		'wishlist'         => __( 'Wishlist', 'wp-theme' ),
		'mega_menu'        => __( 'Mega Menu', 'wp-theme' ),
		'last_button'      => __( 'Last Item as Button', 'wp-theme' ),
		'light_dark'       => __( 'Light / Dark Toggle', 'wp-theme' ),
		'language_bar'     => __( 'Language Bar', 'wp-theme' ),
		'sticky_header'    => __( 'Sticky Header', 'wp-theme' ),
	);
	echo '<div class="wp-theme-menu-extras-fields">';
	foreach ( $fields as $name => $label ) {
		$checked = wp_theme_truthy( get_term_meta( $menu_id, '_wp_theme_menu_' . $name, true ) );
		echo '<p><label><input type="checkbox" name="wp_theme_menu_setting[' . esc_attr( $name ) . ']" value="1" ' . checked( $checked, true, false ) . '> ' . esc_html( $label ) . '</label></p>';
	}
	echo '<p class="description">' . esc_html__( 'These settings apply when this menu is assigned to WP Header Menu.', 'wp-theme' ) . '</p></div>';
}

function wp_theme_nav_menu_native_settings_save( $menu_id ) {
	if ( empty( $_POST['wp_theme_menu_extras_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_theme_menu_extras_nonce'] ) ), 'wp_theme_menu_extras_' . absint( $menu_id ) ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$submitted = isset( $_POST['wp_theme_menu_setting'] ) && is_array( $_POST['wp_theme_menu_setting'] ) ? wp_unslash( $_POST['wp_theme_menu_setting'] ) : array();
	foreach ( array( 'search_bar', 'customer_account', 'mini_cart', 'wishlist', 'mega_menu', 'last_button', 'light_dark', 'language_bar', 'sticky_header' ) as $name ) {
		update_term_meta( absint( $menu_id ), '_wp_theme_menu_' . $name, ! empty( $submitted[ $name ] ) ? '1' : '0' );
	}
}
add_action( 'wp_update_nav_menu', 'wp_theme_nav_menu_native_settings_save', 20, 1 );

function wp_theme_nav_menu_native_item_fields( $item_id, $item ) {
	if ( function_exists( 'acf' ) || function_exists( 'get_field' ) ) {
		return;
	}
	$image_id = absint( get_post_meta( $item_id, '_wp_theme_menu_img', true ) );
	$mega_id  = absint( get_post_meta( $item_id, '_wp_theme_mega_post_id', true ) );
	?>
	<p class="description description-wide"><label><?php esc_html_e( 'Menu image attachment ID', 'wp-theme' ); ?><br><input class="widefat" type="number" min="0" name="wp_theme_menu_img[<?php echo absint( $item_id ); ?>]" value="<?php echo esc_attr( $image_id ); ?>"></label></p>
	<p class="description description-wide"><label><?php esc_html_e( 'Mega menu content post ID', 'wp-theme' ); ?><br><input class="widefat" type="number" min="0" name="wp_theme_mega_post[<?php echo absint( $item_id ); ?>]" value="<?php echo esc_attr( $mega_id ); ?>"></label></p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'wp_theme_nav_menu_native_item_fields', 10, 2 );

function wp_theme_nav_menu_native_item_fields_save( $menu_id, $menu_item_db_id ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) { return; }
	$image = isset( $_POST['wp_theme_menu_img'][ $menu_item_db_id ] ) ? absint( $_POST['wp_theme_menu_img'][ $menu_item_db_id ] ) : 0;
	$mega  = isset( $_POST['wp_theme_mega_post'][ $menu_item_db_id ] ) ? absint( $_POST['wp_theme_mega_post'][ $menu_item_db_id ] ) : 0;
	update_post_meta( $menu_item_db_id, '_wp_theme_menu_img', $image );
	update_post_meta( $menu_item_db_id, '_wp_theme_mega_post_id', $mega );
	update_post_meta( $menu_item_db_id, 'mega_post_id', $mega );
}
add_action( 'wp_update_nav_menu_item', 'wp_theme_nav_menu_native_item_fields_save', 20, 2 );

/** Gutenberg-editable mega-menu content used by classic menu items. */
function wp_theme_register_megamenu_cpt_v3() {
    if (post_type_exists('megamenu')) {
        return;
    }
    register_post_type('megamenu', array(
        'labels' => array(
            'name' => __('Mega Menus', 'wp-theme'),
            'singular_name' => __('Mega Menu', 'wp-theme'),
            'add_new_item' => __('Add Mega Menu', 'wp-theme'),
            'edit_item' => __('Edit Mega Menu', 'wp-theme'),
        ),
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => 'themes.php',
        'show_in_rest' => true,
        'supports' => array('title','editor','revisions'),
        'menu_icon' => 'dashicons-layout',
    ));
    register_taxonomy('megamenu-cat', array('megamenu'), array(
        'labels' => array('name'=>__('Mega Menu Categories','wp-theme'),'singular_name'=>__('Mega Menu Category','wp-theme')),
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'show_admin_column' => true,
    ));
}
add_action('init', 'wp_theme_register_megamenu_cpt_v3', 8);

function wp_theme_nav_menu_megamenu_selector($item_id, $item) {
    $posts = get_posts(array('post_type'=>'megamenu','post_status'=>'publish','posts_per_page'=>100,'orderby'=>'title','order'=>'ASC'));
    if (!$posts) return;
    $selected = absint(get_post_meta($item_id, '_wp_theme_mega_post_id', true));
    if (!$selected) $selected = absint(get_post_meta($item_id, 'mega_post_id', true));
    if (!$selected && function_exists('get_field')) {
        $selected = ( function_exists( 'wp_theme_acf_ready' ) && wp_theme_acf_ready() ) ? absint( get_field( 'mega_post_id', $item ) ) : 0;
    }
    echo '<p class="description description-wide wp-theme-mega-menu-selector"><label>' . esc_html__('Mega menu block layout', 'wp-theme') . '<br><select class="widefat" name="wp_theme_mega_layout[' . absint($item_id) . ']"><option value="0">' . esc_html__('None', 'wp-theme') . '</option>';
    foreach ($posts as $post) {
        echo '<option value="' . absint($post->ID) . '" ' . selected($selected, $post->ID, false) . '>' . esc_html($post->post_title) . '</option>';
    }
    echo '</select></label><span class="description">' . esc_html__('Create layouts under Appearance → Mega Menus using Gutenberg/BBuilder blocks.', 'wp-theme') . '</span></p>';
}
add_action('wp_nav_menu_item_custom_fields', 'wp_theme_nav_menu_megamenu_selector', 20, 2);

function wp_theme_nav_menu_megamenu_selector_save($menu_id, $menu_item_db_id) {
    if (!current_user_can('edit_theme_options')) return;
    if (!isset($_POST['wp_theme_mega_layout'][$menu_item_db_id])) return;
    $mega = absint(wp_unslash($_POST['wp_theme_mega_layout'][$menu_item_db_id]));
    update_post_meta($menu_item_db_id, '_wp_theme_mega_post_id', $mega);
    update_post_meta($menu_item_db_id, 'mega_post_id', $mega);
    if (function_exists('update_field')) {
        update_field('mega_post_id', $mega, $menu_item_db_id);
    }
}
add_action('wp_update_nav_menu_item', 'wp_theme_nav_menu_megamenu_selector_save', 30, 2);

/**
 * Mega-menu layouts are normal Gutenberg content, so let Polylang translate
 * them just like pages when the free plugin is active.
 */
function wp_theme_megamenu_polylang_post_types( $post_types, $is_settings ) {
    $post_types['megamenu'] = 'megamenu';
    return $post_types;
}
add_filter( 'pll_get_post_types', 'wp_theme_megamenu_polylang_post_types', 10, 2 );
