<?php
/**
 * IWS Woo Product Filter.
 *
 * AJAX product filter with variation-attribute support, e.g.
 * ?attribute_height=600mm&attribute_colour=High+Visibility+Yellow
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action( 'init', 'iws_filter_register_shortcodes', 20 );
add_action( 'admin_menu', 'iws_filter_admin_menu', 60 );
add_action( 'admin_init', 'iws_filter_register_settings' );
add_action( 'wp_enqueue_scripts', 'iws_filter_enqueue_assets' );
add_action( 'wp_ajax_iws_filter_products', 'iws_filter_products_ajax' );
add_action( 'wp_ajax_nopriv_iws_filter_products', 'iws_filter_products_ajax' );
add_action( 'wp_enqueue_scripts', 'iws_filter_hotfix_assets', 101 );
add_action( 'woocommerce_after_shop_loop_item', 'iws_compare_loop_button', 6 );
add_action( 'wp_footer', 'iws_compare_modal_markup', 30 );
function iws_filter_defaults() {
	return array(
		'enabled'         => 'yes',
		'instant'         => 'yes',
		'posts_per_page'  => 8,
		'price_max'       => 10000,
		'dimension_max'   => 5000,
		'show_price'      => 'yes',
		'show_height'     => 'yes',
		'show_width'      => 'yes',
		'show_length'     => 'yes',
		'show_attributes' => 'yes',
		'show_stock'      => 'yes',
		'show_sale'       => 'yes',
		'show_rating'     => 'yes',
		'show_sort'       => 'yes',
		'show_brand'      => 'yes',
	);
}
function iws_filter_get_settings() {
	$saved = get_option( 'iws_product_filter_settings', array() );
	return wp_parse_args(
		is_array( $saved ) ? $saved : array(),
		iws_filter_defaults()
	);
}
function iws_filter_admin_menu() {
	add_submenu_page(
		'woocommerce',
		__( 'Product Filters', 'wp-theme-woo-support' ),
		__( 'Product Filters', 'wp-theme-woo-support' ),
		'manage_woocommerce',
		'iws-product-filters',
		'iws_filter_settings_page'
	);
}
function iws_filter_register_settings() {
	register_setting(
		'iws_product_filter_settings_group',
		'iws_product_filter_settings',
		'iws_filter_sanitize_settings'
	);
}
function iws_filter_sanitize_settings( $input ) {
	$out = array();
	$out['enabled']         = ! empty( $input['enabled'] ) ? 'yes' : 'no';
	$out['instant']         = ! empty( $input['instant'] ) ? 'yes' : 'no';
	$out['posts_per_page']  = max( 1, absint( $input['posts_per_page'] ?? 8 ) );
	$out['price_max']       = max( 1, absint( $input['price_max'] ?? 10000 ) );
	$out['dimension_max']   = max( 1, absint( $input['dimension_max'] ?? 5000 ) );
	$out['show_price']      = ! empty( $input['show_price'] ) ? 'yes' : 'no';
	$out['show_height']     = ! empty( $input['show_height'] ) ? 'yes' : 'no';
	$out['show_width']      = ! empty( $input['show_width'] ) ? 'yes' : 'no';
	$out['show_length']     = ! empty( $input['show_length'] ) ? 'yes' : 'no';
	$out['show_attributes'] = ! empty( $input['show_attributes'] ) ? 'yes' : 'no';
	$out['show_stock']      = ! empty( $input['show_stock'] ) ? 'yes' : 'no';
	$out['show_sale']       = ! empty( $input['show_sale'] ) ? 'yes' : 'no';
	$out['show_rating']     = ! empty( $input['show_rating'] ) ? 'yes' : 'no';
	$out['show_sort']       = ! empty( $input['show_sort'] ) ? 'yes' : 'no';
	$out['show_brand']      = ! empty( $input['show_brand'] ) ? 'yes' : 'no';
	return $out;
}
function iws_filter_settings_page() {
	$s = iws_filter_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Product Filters', 'wp-theme-woo-support' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'iws_product_filter_settings_group' ); ?>
			<div class="postbox">
				<div class="inside">
					<table class="form-table" role="presentation">
						<tr>
							<th><?php esc_html_e( 'Enable', 'wp-theme-woo-support' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="iws_product_filter_settings[enabled]" value="1" <?php checked( $s['enabled'], 'yes' ); ?>>
									<?php esc_html_e( 'Active', 'wp-theme-woo-support' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Instant AJAX', 'wp-theme-woo-support' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="iws_product_filter_settings[instant]" value="1" <?php checked( $s['instant'], 'yes' ); ?>>
									<?php esc_html_e( 'Filter immediately when search/fields change', 'wp-theme-woo-support' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Products per page', 'wp-theme-woo-support' ); ?></th>
							<td>
								<input type="number" min="1" class="small-text" name="iws_product_filter_settings[posts_per_page]" value="<?php echo esc_attr( $s['posts_per_page'] ); ?>">
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Range maximums', 'wp-theme-woo-support' ); ?></th>
							<td>
								<label>
									<?php esc_html_e( 'Price max', 'wp-theme-woo-support' ); ?>
									<input type="number" min="1" class="small-text" name="iws_product_filter_settings[price_max]" value="<?php echo esc_attr( $s['price_max'] ); ?>">
								</label>
								&nbsp;&nbsp;
								<label>
									<?php esc_html_e( 'Dimension max', 'wp-theme-woo-support' ); ?>
									<input type="number" min="1" class="small-text" name="iws_product_filter_settings[dimension_max]" value="<?php echo esc_attr( $s['dimension_max'] ); ?>">
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Visible filters', 'wp-theme-woo-support' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="iws_product_filter_settings[show_price]" value="1" <?php checked( $s['show_price'], 'yes' ); ?>>
									<?php esc_html_e( 'Price', 'wp-theme-woo-support' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="iws_product_filter_settings[show_height]" value="1" <?php checked( $s['show_height'], 'yes' ); ?>>
									<?php esc_html_e( 'Height range', 'wp-theme-woo-support' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="iws_product_filter_settings[show_width]" value="1" <?php checked( $s['show_width'], 'yes' ); ?>>
									<?php esc_html_e( 'Width range', 'wp-theme-woo-support' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="iws_product_filter_settings[show_length]" value="1" <?php checked( $s['show_length'], 'yes' ); ?>>
									<?php esc_html_e( 'Length range', 'wp-theme-woo-support' ); ?>
								</label><br>
								<label>
									<input type="checkbox" name="iws_product_filter_settings[show_attributes]" value="1" <?php checked( $s['show_attributes'], 'yes' ); ?>>
									<?php esc_html_e( 'Variation attributes, e.g. colour/type/height/speed bump length', 'wp-theme-woo-support' ); ?>
								</label>
								<br><label><input type="checkbox" name="iws_product_filter_settings[show_stock]" value="1" <?php checked( $s['show_stock'], 'yes' ); ?>> <?php esc_html_e( 'In-stock toggle', 'wp-theme-woo-support' ); ?></label>
								<br><label><input type="checkbox" name="iws_product_filter_settings[show_sale]" value="1" <?php checked( $s['show_sale'], 'yes' ); ?>> <?php esc_html_e( 'On-sale toggle', 'wp-theme-woo-support' ); ?></label>
								<br><label><input type="checkbox" name="iws_product_filter_settings[show_rating]" value="1" <?php checked( $s['show_rating'], 'yes' ); ?>> <?php esc_html_e( 'Minimum rating', 'wp-theme-woo-support' ); ?></label>
								<br><label><input type="checkbox" name="iws_product_filter_settings[show_sort]" value="1" <?php checked( $s['show_sort'], 'yes' ); ?>> <?php esc_html_e( 'Sorting', 'wp-theme-woo-support' ); ?></label>
								<br><label><input type="checkbox" name="iws_product_filter_settings[show_brand]" value="1" <?php checked( $s['show_brand'], 'yes' ); ?>> <?php esc_html_e( 'Brand taxonomy when available', 'wp-theme-woo-support' ); ?></label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Shortcodes', 'wp-theme-woo-support' ); ?></th>
							<td>
								<code>[iws_product_filter]</code><br>
								<code>[iws_product_filter_results]</code>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save filter settings', 'wp-theme-woo-support' ) ); ?>
				</div>
			</div>
		</form>
	</div>
	<?php
}
function iws_filter_register_shortcodes() {
	add_shortcode( 'iws_product_filter', 'iws_product_filter_shortcode' );
	add_shortcode( 'iws_product_filter_results', 'iws_product_filter_results_shortcode' );
}
function iws_filter_frontend_needs_assets() {
	if ( is_admin() ) {
		return false;
	}

	$needs = false;
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$needs = true;
	} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$needs = true;
	} elseif ( is_post_type_archive( 'product' ) ) {
		$needs = true;
	} elseif ( is_search() && 'product' === get_query_var( 'post_type' ) ) {
		$needs = true;
	} elseif ( is_singular() ) {
		$post_id = get_queried_object_id();
		$content = $post_id ? (string) get_post_field( 'post_content', $post_id ) : '';
		$needs   = has_shortcode( $content, 'iws_product_filter' )
			|| has_shortcode( $content, 'iws_product_filter_results' )
			|| has_block( 'woocommerce/product-collection', $content )
			|| has_block( 'woocommerce/all-products', $content );
	}

	return (bool) apply_filters( 'iws_filter_frontend_needs_assets', $needs );
}

function iws_filter_enqueue_assets() {
	if ( ! iws_filter_frontend_needs_assets() ) {
		return;
	}
	wp_enqueue_script( 'jquery' );
	$select_script = 'select2-js';
	$select_style  = 'select2-css';

	// Prefer WooCommerce's bundled SelectWoo/Select2 assets. The CDN fallback is
	// only used if another Woo version or integration has not registered them.
	if ( wp_script_is( 'selectWoo', 'registered' ) ) {
		$select_script = 'selectWoo';
		wp_enqueue_script( $select_script );
	} else {
		if ( ! wp_script_is( $select_script, 'registered' ) ) {
			wp_register_script(
				$select_script,
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
				array( 'jquery' ),
				'4.0.13',
				true
			);
		}
		wp_enqueue_script( $select_script );
	}

	if ( wp_style_is( 'select2', 'registered' ) ) {
		$select_style = 'select2';
		wp_enqueue_style( $select_style );
	} else {
		if ( ! wp_style_is( $select_style, 'registered' ) ) {
			wp_register_style(
				$select_style,
				'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
				array(),
				'4.0.13',
				'all'
			);
		}
		wp_enqueue_style( $select_style );
	}
	wp_register_style( 'iws-product-filter-final', false, array( $select_style ), '2.2.1' );
	wp_enqueue_style( 'iws-product-filter-final' );
	wp_add_inline_style( 'iws-product-filter-final', iws_filter_final_css() . iws_filter_v3_css() );
	$script_path = wp_theme_woo_support_path( 'assets/js/iws-product-filter.js' );
	$script_url  = wp_theme_woo_support_url( 'assets/js/iws-product-filter.js' );
	wp_enqueue_script(
		'iws-product-filter',
		$script_url,
		array( 'jquery', $select_script ),
		file_exists( $script_path ) ? filemtime( $script_path ) : '2.2.1',
		true
	);
	wp_localize_script(
		'iws-product-filter',
		'iwsProductFilter',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'categories' => iws_filter_category_lookup(),
			'i18n'       => array(
				'loading'  => __( 'Loading...', 'wp-theme-woo-support' ),
				'loadMore' => __( 'Load more', 'wp-theme-woo-support' ),
			),
		)
	);
}
function iws_filter_current_taxonomy_context() {
	$context = array(
		'taxonomy' => '',
		'slug'     => '',
	);
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) && ! empty( $term->slug ) ) {
			$context['taxonomy'] = 'product_cat';
			$context['slug']     = sanitize_title( $term->slug );
		}
	} elseif ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
		$term = get_queried_object();
		if ( $term && ! is_wp_error( $term ) && ! empty( $term->slug ) ) {
			$context['taxonomy'] = 'product_tag';
			$context['slug']     = sanitize_title( $term->slug );
		}
	}
	return $context;
}
function iws_filter_add_tax_constraint( &$args, $taxonomy, $slug ) {
	$slug = sanitize_title( wp_unslash( $slug ) );
	if ( ! $taxonomy || ! $slug ) {
		return;
	}
	$args['tax_query'][] = array(
		'taxonomy'         => sanitize_key( $taxonomy ),
		'field'            => 'slug',
		'terms'            => $slug,
		'include_children' => false,
	);
}
function iws_filter_category_lookup() {
	$lookup = array(
		'byId'   => array(),
		'byName' => array(),
		'bySlug' => array(),
	);
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return $lookup;
	}
	foreach ( $terms as $term ) {
		$lookup['byId'][ (string) $term->term_id ]         = $term->slug;
		$lookup['byName'][ sanitize_title( $term->name ) ] = $term->slug;
		$lookup['bySlug'][ $term->slug ]                  = $term->slug;
	}
	return $lookup;
}
function iws_filter_final_css() {
	return '.iws-filter-wrapper{position:relative;z-index:30;margin:0 0 28px;padding:14px 16px;background:#fff;border:1px solid #ececec;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.05)}.iws-filter-wrapper *,.iws-filter-results-wrap *{box-sizing:border-box}.iws-filter-wrapper input,.iws-filter-wrapper select,.iws-filter-wrapper button{pointer-events:auto!important;position:relative;z-index:2}.iws-filter-form{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:12px;align-items:end;margin:0;width:100%}.iws-filter-field{min-width:0;width:100%;max-width:100%}.iws-filter-field label{display:block;margin:0 0 6px;font-size:12px;font-weight:700;color:#111}.iws-filter-field input,.iws-filter-field select{display:block;width:100%!important;max-width:100%!important;height:42px;border:1px solid #d8d8d8;border-radius:8px;background:#fff;padding:8px 12px;font-size:14px;color:#111;box-shadow:none}.iws-filter-field input:focus,.iws-filter-field select:focus{outline:0;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb));box-shadow:0 0 0 3px rgba(246,196,0,.18)}.iws-filter-search{grid-column:1/-1;order:1}.iws-search-input-wrap{position:relative;display:block;width:100%;max-width:100%}.iws-search-input-wrap .iws-search-input,.iws-search-input-wrap input[type=search]{display:block;width:100%!important;max-width:100%!important;height:48px!important;min-height:48px!important;padding:0 58px 0 16px!important;border:1px solid #d8d8d8!important;border-radius:var(--wp-theme-radius,10px)!important;background:#fff!important;font-size:15px!important;line-height:48px!important;color:var(--wp-theme-ink,#111)!important;box-shadow:none!important;appearance:none!important;-webkit-appearance:none!important}.iws-search-input-wrap .iws-search-input:focus,.iws-search-input-wrap input[type=search]:focus{outline:0!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;box-shadow:0 0 0 3px rgba(246,196,0,.18)!important}.iws-search-input-wrap .iws-search-icon{position:absolute!important;top:50%!important;right:18px!important;width:26px!important;height:26px!important;min-width:26px!important;min-height:26px!important;transform:translateY(-50%)!important;display:flex!important;align-items:center!important;justify-content:center!important;color:#b8b8b8!important;pointer-events:none!important;z-index:6!important;line-height:1!important}.iws-search-input-wrap .iws-search-icon svg{display:block!important;width:26px!important;height:26px!important;max-width:26px!important;max-height:26px!important;overflow:visible!important}.iws-search-input-wrap .iws-search-icon svg,.iws-search-input-wrap .iws-search-icon svg circle,.iws-search-input-wrap .iws-search-icon svg line{fill:none!important;stroke:currentColor!important;stroke-width:2.25!important;stroke-linecap:round!important;stroke-linejoin:round!important}.iws-search-input-wrap input[type=search]::-webkit-search-decoration,.iws-search-input-wrap input[type=search]::-webkit-search-cancel-button,.iws-search-input-wrap input[type=search]::-webkit-search-results-button,.iws-search-input-wrap input[type=search]::-webkit-search-results-decoration{display:none!important}.iws-filter-field .select2-container{display:block;width:100%!important;max-width:100%!important;min-width:0!important}.iws-filter-field .select2-container .select2-selection--single{height:42px!important;min-height:42px!important;border:1px solid #d8d8d8!important;border-radius:var(--wp-theme-radius,10px)!important;background:#fff!important;display:flex!important;align-items:center!important;box-shadow:none!important}.iws-filter-field .select2-container .select2-selection__rendered{width:100%!important;padding:0 36px 0 12px!important;line-height:40px!important;color:var(--wp-theme-ink,#111)!important;font-size:14px!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important}.iws-filter-field .select2-container .select2-selection__arrow{height:40px!important;right:8px!important;top:1px!important}.iws-filter-field .select2-container--open .select2-selection--single,.iws-filter-field .select2-container--focus .select2-selection--single{border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;box-shadow:0 0 0 3px rgba(246,196,0,.18)!important}.iws-filter-category,.iws-filter-range,.iws-filter-attribute{grid-column:span 3}.iws-filter-range-grid{display:grid;grid-template-columns:1fr;gap:6px;width:100%;max-width:100%}.iws-filter-range-grid>input[type=number]{grid-column:1/-1;width:100%!important;max-width:100%!important}.iws-range-slider{grid-column:1/-1;display:grid;grid-template-columns:1fr;gap:2px;width:100%;max-width:100%}.iws-range-slider input[type=range]{display:block;width:100%!important;height:14px;min-height:14px;padding:0;border:0;accent-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb));margin:0;background:transparent;box-shadow:none}.iws-range-values{grid-column:1/-1;display:flex;justify-content:space-between;gap:8px;margin-top:2px;font-size:12px;font-weight:700}.iws-range-values span{display:inline-flex;min-height:22px;padding:3px 8px;border-radius:999px;background:#f5f5f5;border:1px solid #e5e5e5;color:#333}.iws-filter-actions{display:flex;gap:10px;}.iws-filter-actions button{height:38px;min-width:84px;border:0;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:800;line-height:1;cursor:pointer}.iws-filter-submit{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-filter-reset{background:#111!important;color:#fff!important}.iws-filter-more{grid-column:1/-1;order:2;border:1px solid #ededed;border-radius:10px;background:#fbfbfb;overflow:hidden}.iws-filter-more summary{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:42px;padding:10px 14px;cursor:pointer;font-size:15px;font-weight:600;color:#111;list-style:none;letter-spacing:1px}.iws-filter-more summary::-webkit-details-marker{display:none}.iws-filter-more summary:after{content:"+";display:inline-flex;letter-spacing:0;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb));color:#fff;font-size:16px;font-weight:400;line-height:1}.iws-filter-more[open] summary:after{content:"-"}.iws-filter-more__content{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:12px 14px;padding:0 14px 14px;align-items:start}.iws-filter-spinner{display:none;width:18px;height:18px;border:2px solid rgba(17,17,17,.18);border-top-color:#111;border-radius:50%;animation:iwsSpin .7s linear infinite;margin-left:4px;vertical-align:middle}.iws-filter-wrapper.iws-is-loading .iws-filter-spinner{display:inline-block}.iws-filter-wrapper.iws-is-loading .iws-filter-submit,.iws-filter-wrapper.iws-is-loading .iws-filter-reset{opacity:.7;pointer-events:none!important}@keyframes iwsSpin{to{transform:rotate(360deg)}}.iws-active-filters{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 0}.iws-filter-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;background:#f4f4f4;color:#111;font-size:12px;font-weight:700;border:1px solid #e2e2e2}.iws-filter-chip button{border:0;background:transparent;color:#111;font-weight:900;line-height:1;cursor:pointer;padding:0}.iws-results-loading{opacity:.55;pointer-events:none;transition:opacity .18s ease}.iws-filter-results-wrap{clear:both;width:100%;position:relative;z-index:1}.iws-result-count{margin:0 0 18px!important;font-size:14px;color:#222}.iws-products-grid,.woocommerce .iws-products-grid{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:24px!important;margin:0 0 24px!important;padding:0!important;list-style:none!important;float:none!important;clear:both!important}.iws-products-grid:before,.iws-products-grid:after{display:none!important;content:none!important}.iws-products-grid li.product,.woocommerce .iws-products-grid li.product{float:none!important;clear:none!important;width:auto!important;margin:0!important;padding:18px!important;list-style:none!important;background:var(--wp-theme-surface,#fff)!important;border-radius:var(--wp-theme-radius,10px)!important;display:flex!important;flex-direction:column!important;min-height:100%!important;position:relative!important;overflow:hidden!important}.iws-products-grid li.product a.woocommerce-LoopProduct-link{display:block!important;color:inherit!important;text-decoration:none!important}.iws-products-grid li.product img{width:100%!important;height:270px!important;object-fit:contain!important;object-position:center!important;background:#fff!important;margin:0 0 16px!important;display:block!important}.iws-products-grid li.product .woocommerce-loop-product__title{font-size:20px!important;line-height:1.18!important;font-weight:800!important;margin:0 0 9px!important;color:var(--wp-theme-ink,#111)!important}.iws-products-grid li.product .price{display:block!important;margin-top:auto!important;margin-bottom:0!important;color:var(--wp-theme-ink,#111)!important;font-size:18px!important;font-weight:800!important}.iws-products-grid li.product .button,.iws-products-grid li.product .add_to_cart_button,.iws-products-grid li.product .product_type_variable{display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;max-width:100%!important;min-height:38px!important;margin:10px 0 0!important;padding:10px 12px!important;background:var(--wp-theme-primary,#2563eb)!important;color:#fff!important;border:0!important;border-radius:7px!important;font-size:14px!important;font-weight:800!important;line-height:1!important;text-align:center!important;text-decoration:none!important;position:static!important;left:auto!important;right:auto!important;bottom:auto!important;transform:none!important}.iws-load-more-wrap{display:flex;justify-content:center;align-items:center;margin:28px 0 10px;width:100%}.iws-load-more-wrap .btn.btn-primary,.iws-load-more-wrap .iws-load-more{display:inline-flex;align-items:center;justify-content:center;gap:10px;border:0!important;border-radius:7px!important;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important;font-size:14px!important;font-weight:800!important;line-height:1!important;min-height:42px!important;padding:12px 30px!important;cursor:pointer;text-decoration:none!important}.iws-load-more .iws-btn-spinner{display:none;width:16px;height:16px;border:2px solid rgba(17,17,17,.2);border-top-color:#111;border-radius:50%;animation:iwsSpin .7s linear infinite}.iws-load-more[disabled]{opacity:.75;cursor:wait}.iws-load-more span.iws-load-more-text{color:#fff!important}.iws-load-more[disabled] .iws-btn-spinner{display:inline-block}.tpl__woo-search-cat .wc-block-product-categories-list,.tpl__woo-search-cat .wp-block-woocommerce-product-categories ul{display:flex!important;flex-wrap:wrap;gap:10px!important;margin:0!important;padding:0!important;list-style:none!important}.tpl__woo-search-cat .wc-block-product-categories-list li,.tpl__woo-search-cat .wp-block-woocommerce-product-categories li{margin:0!important;padding:0!important;list-style:none!important}.tpl__woo-search-cat .wc-block-product-categories-list a,.tpl__woo-search-cat .wp-block-woocommerce-product-categories a{display:inline-flex!important;align-items:center;min-height:38px;padding:8px 13px;border:1px solid #e2e2e2;border-radius:999px;background:#fff;color:var(--wp-theme-ink,#111)!important;font-size:13px;font-weight:800;text-decoration:none!important;transition:background .18s ease,border-color .18s ease,box-shadow .18s ease}.tpl__woo-search-cat .wc-block-product-categories-list a:hover,.tpl__woo-search-cat .wp-block-woocommerce-product-categories a:hover,.tpl__woo-search-cat .is-iws-active-category>a{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;box-shadow:0 5px 16px rgba(0,0,0,.08)}.tpl__woo-search-cat .wc-block-product-categories-list li>ul{display:none!important}@media(max-width:1100px){.iws-products-grid,.woocommerce .iws-products-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important}.iws-filter-category,.iws-filter-range,.iws-filter-attribute,.iws-filter-actions{grid-column:span 6}}@media(max-width:760px){.iws-products-grid,.woocommerce .iws-products-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}.iws-filter-category,.iws-filter-range,.iws-filter-attribute,.iws-filter-actions{grid-column:1/-1}.iws-filter-actions button{flex:1}}@media(max-width:520px){.iws-products-grid,.woocommerce .iws-products-grid{grid-template-columns:1fr!important}.iws-filter-range-grid,.iws-range-slider{grid-template-columns:1fr}}';
}
function iws_filter_v3_css() {
	return '.iws-filter-quick{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:10px;align-items:center}.iws-filter-toggle{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:8px 12px;border:1px solid #d8d8d8;border-radius:999px;background:#fff;font-size:13px;font-weight:700;cursor:pointer}.iws-filter-toggle input{width:18px!important;height:18px!important;min-height:18px!important;margin:0!important;accent-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))}.iws-filter-sort{margin-left:auto;min-width:210px}.iws-filter-rating,.iws-filter-brand{grid-column:span 3}.iws-filter-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:0 0 18px}.iws-filter-help{font-size:12px;color:#666}.iws-filter-field select:disabled{opacity:.55;cursor:not-allowed}@media(max-width:991px){.iws-filter-category,.iws-filter-range,.iws-filter-attribute,.iws-filter-rating,.iws-filter-brand{grid-column:span 6}.iws-filter-sort{margin-left:0;min-width:180px}}@media(max-width:575px){.iws-filter-category,.iws-filter-range,.iws-filter-attribute,.iws-filter-rating,.iws-filter-brand{grid-column:1/-1}.iws-filter-quick{display:grid;grid-template-columns:1fr 1fr}.iws-filter-sort{grid-column:1/-1;width:100%}}';
}

function iws_filter_brand_taxonomy() {
	foreach ( array( 'product_brand', 'pa_brand' ) as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy ) ) {
			return $taxonomy;
		}
	}
	return '';
}

function iws_filter_sort_options() {
	return array(
		'default'    => __( 'Default sorting', 'wp-theme-woo-support' ),
		'popularity' => __( 'Popularity', 'wp-theme-woo-support' ),
		'rating'     => __( 'Average rating', 'wp-theme-woo-support' ),
		'newest'     => __( 'Newest', 'wp-theme-woo-support' ),
		'price_asc'  => __( 'Price: low to high', 'wp-theme-woo-support' ),
		'price_desc' => __( 'Price: high to low', 'wp-theme-woo-support' ),
	);
}

function iws_filter_catalog_visibility_query() {
	$tax_query = array();
	if ( ! taxonomy_exists( 'product_visibility' ) ) {
		return $tax_query;
	}
	$exclude = array();
	foreach ( array( 'exclude-from-catalog', 'exclude-from-search' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_visibility' );
		if ( $term && ! is_wp_error( $term ) ) {
			$exclude[] = (int) $term->term_id;
		}
	}
	if ( $exclude ) {
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_id',
			'terms'    => $exclude,
			'operator' => 'NOT IN',
		);
	}
	return $tax_query;
}

function iws_product_filter_shortcode( $atts ) {
	$s = iws_filter_get_settings();
	if ( $s['enabled'] !== 'yes' ) {
		return '';
	}
	$atts = shortcode_atts(
		array(
			'posts_per_page' => $s['posts_per_page'],
		),
		$atts,
		'iws_product_filter'
	);
	$ppp           = max( 1, absint( $atts['posts_per_page'] ) );
	$price_max     = max( 1, absint( $s['price_max'] ) );
	$dimension_max = max( 1, absint( $s['dimension_max'] ) );
	$price_bounds  = iws_filter_meta_bounds( '_price', 0, $price_max );
	$height_bounds = iws_filter_meta_bounds( '_height', 0, $dimension_max );
	$width_bounds  = iws_filter_meta_bounds( '_width', 0, $dimension_max );
	$length_bounds = iws_filter_meta_bounds( '_length', 0, $dimension_max );
	ob_start();
	?>
	<div class="iws-filter-wrapper" data-instant="<?php echo esc_attr( $s['instant'] ); ?>" data-ppp="<?php echo esc_attr( $ppp ); ?>">
		<form class="iws-filter-form" method="post" action="#" autocomplete="off" novalidate>
			<input type="hidden" name="iws_filter_nonce" value="<?php echo esc_attr( wp_create_nonce( 'iws_filter_products' ) ); ?>">
			<input type="hidden" name="paged" value="1">
			<input type="hidden" name="posts_per_page" value="<?php echo esc_attr( $ppp ); ?>">
			<?php $iws_filter_context = iws_filter_current_taxonomy_context(); ?>
			<?php if ( ! empty( $iws_filter_context['taxonomy'] ) && ! empty( $iws_filter_context['slug'] ) ) : ?>
				<input type="hidden" name="iws_filter_context_taxonomy" value="<?php echo esc_attr( $iws_filter_context['taxonomy'] ); ?>">
				<input type="hidden" name="iws_filter_context_slug" value="<?php echo esc_attr( $iws_filter_context['slug'] ); ?>">
			<?php endif; ?>
			<div class="iws-filter-field iws-filter-search">
				<label><?php esc_html_e( 'Search', 'wp-theme-woo-support' ); ?></label>
				<div class="iws-search-row">
					<div class="iws-search-input-wrap">
						<input class="iws-search-input" type="search" name="product_search" data-label="<?php esc_attr_e( 'Search', 'wp-theme-woo-support' ); ?>" placeholder="<?php echo esc_attr__( 'Search products', 'wp-theme-woo-support' ); ?>">
						<button type="button" class="iws-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'wp-theme-woo-support' ); ?>" hidden><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
						<span class="iws-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img"><circle cx="10.75" cy="10.75" r="6.75"></circle><line x1="16" y1="16" x2="21" y2="21"></line></svg></span>
					</div>
					<button type="button" class="iws-compare-open iws-compare-open--search" aria-label="<?php esc_attr_e( 'Open product comparison', 'wp-theme-woo-support' ); ?>">
						<span class="iws-compare-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M5 8.5h14M5 15.5h14"></path></svg></span>
						<span class="iws-compare-count" aria-live="polite">0</span>
					</button>
				</div>
			</div>
			<div class="iws-filter-quick">
				<?php if ( $s['show_stock'] === 'yes' ) : ?>
					<label class="iws-filter-toggle"><input type="checkbox" name="in_stock" value="1" data-label="<?php esc_attr_e( 'Availability', 'wp-theme-woo-support' ); ?>"> <span><?php esc_html_e( 'In stock', 'wp-theme-woo-support' ); ?></span></label>
				<?php endif; ?>
				<?php if ( $s['show_sale'] === 'yes' ) : ?>
					<label class="iws-filter-toggle"><input type="checkbox" name="on_sale" value="1" data-label="<?php esc_attr_e( 'Offer', 'wp-theme-woo-support' ); ?>"> <span><?php esc_html_e( 'On sale', 'wp-theme-woo-support' ); ?></span></label>
				<?php endif; ?>
				<?php if ( $s['show_sort'] === 'yes' ) : ?>
					<div class="iws-filter-field iws-filter-sort"><label class="screen-reader-text" for="iws_product_orderby"><?php esc_html_e( 'Sort products', 'wp-theme-woo-support' ); ?></label><select id="iws_product_orderby" name="product_orderby" data-label="<?php esc_attr_e( 'Sort', 'wp-theme-woo-support' ); ?>">
						<?php foreach ( iws_filter_sort_options() as $sort_key => $sort_label ) : ?><option value="<?php echo esc_attr( $sort_key ); ?>"><?php echo esc_html( $sort_label ); ?></option><?php endforeach; ?>
					</select></div>
				<?php endif; ?>
			</div>
			<details class="iws-filter-more"><summary><?php esc_html_e( 'More filters', 'wp-theme-woo-support' ); ?></summary>
			<div class="iws-filter-more__content">
					<?php if ( $s['show_price'] === 'yes' ) : ?>
						<?php iws_filter_range_control( 'price', __( 'Price', 'wp-theme-woo-support' ), $price_bounds['min'], $price_bounds['max'], '0.01' ); ?>
					<?php endif; ?>
					<?php if ( $s['show_height'] === 'yes' ) : ?>
						<?php iws_filter_range_control( 'height', __( 'Height', 'wp-theme-woo-support' ), $height_bounds['min'], $height_bounds['max'], '1' ); ?>
					<?php endif; ?>
					<?php if ( $s['show_width'] === 'yes' ) : ?>
						<?php iws_filter_range_control( 'width', __( 'Width', 'wp-theme-woo-support' ), $width_bounds['min'], $width_bounds['max'], '1' ); ?>
					<?php endif; ?>
					<?php if ( $s['show_length'] === 'yes' ) : ?>
						<?php iws_filter_range_control( 'length', __( 'Length', 'wp-theme-woo-support' ), $length_bounds['min'], $length_bounds['max'], '1' ); ?>
					<?php endif; ?>
					<?php if ( $s['show_attributes'] === 'yes' ) : ?>
						<?php iws_filter_attribute_controls(); ?>
					<?php endif; ?>
					<?php if ( $s['show_rating'] === 'yes' ) : ?>
						<div class="iws-filter-field iws-filter-rating"><label><?php esc_html_e( 'Minimum rating', 'wp-theme-woo-support' ); ?></label><select name="min_rating" data-label="<?php esc_attr_e( 'Rating', 'wp-theme-woo-support' ); ?>"><option value=""><?php esc_html_e( 'Any rating', 'wp-theme-woo-support' ); ?></option><option value="4"><?php esc_html_e( '4 stars & up', 'wp-theme-woo-support' ); ?></option><option value="3"><?php esc_html_e( '3 stars & up', 'wp-theme-woo-support' ); ?></option><option value="2"><?php esc_html_e( '2 stars & up', 'wp-theme-woo-support' ); ?></option></select></div>
					<?php endif; ?>
					<?php $iws_brand_taxonomy = ( $s['show_brand'] === 'yes' ) ? iws_filter_brand_taxonomy() : ''; ?>
					<?php if ( $iws_brand_taxonomy ) : ?>
						<div class="iws-filter-field iws-filter-brand"><label><?php esc_html_e( 'Brand', 'wp-theme-woo-support' ); ?></label><select name="product_brand" data-taxonomy="<?php echo esc_attr( $iws_brand_taxonomy ); ?>" data-label="<?php esc_attr_e( 'Brand', 'wp-theme-woo-support' ); ?>"><option value=""><?php esc_html_e( 'All brands', 'wp-theme-woo-support' ); ?></option><?php $brand_terms = get_terms( array( 'taxonomy' => $iws_brand_taxonomy, 'hide_empty' => true ) ); if ( ! is_wp_error( $brand_terms ) ) { foreach ( $brand_terms as $brand_term ) { printf( '<option value="%1$s">%2$s</option>', esc_attr( $brand_term->slug ), esc_html( $brand_term->name ) ); } } ?></select></div>
					<?php endif; ?>
					<div class="iws-filter-field iws-filter-category"><label><?php esc_html_e( 'Category', 'wp-theme-woo-support' ); ?></label><select name="product_cat" data-label="<?php esc_attr_e( 'Category', 'wp-theme-woo-support' ); ?>"><option value=""><?php esc_html_e( 'All categories', 'wp-theme-woo-support' ); ?></option>
							<?php
							$terms = get_terms(
								array(
									'taxonomy'   => 'product_cat',
									'hide_empty' => true,
								)
							);
							if ( ! is_wp_error( $terms ) ) {
								foreach ( $terms as $term ) {
									printf(
										'<option value="%1$s" %2$s>%3$s</option>',
										esc_attr( $term->slug ),
										selected( $iws_filter_context['taxonomy'] === 'product_cat' && $iws_filter_context['slug'] === $term->slug, true, false ),
										esc_html( $term->name )
									);
								}
							}
							?>
						</select>
					</div>
					<div class="iws-filter-field iws-filter-actions">
						<button type="submit" class="iws-filter-submit"><?php esc_html_e( 'Filter', 'wp-theme-woo-support' ); ?></button>
						<button type="button" class="iws-filter-reset"><?php esc_html_e( 'Reset', 'wp-theme-woo-support' ); ?></button>
						<span class="iws-filter-spinner" aria-hidden="true"></span>
					</div>
				</div>
			</details>
		</form>
		<div class="iws-active-filters" aria-live="polite"></div>
	</div>
	<?php
	return ob_get_clean();
}
function iws_filter_meta_bounds( $meta_key, $fallback_min, $fallback_max ) {
	global $wpdb;
	$cache_key = 'iws_filter_bounds_' . sanitize_key( $meta_key );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && isset( $cached['min'], $cached['max'] ) ) {
		return $cached;
	}
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT MIN(CAST(pm.meta_value AS DECIMAL(20,4))) AS min_value, MAX(CAST(pm.meta_value AS DECIMAL(20,4))) AS max_value
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND pm.meta_key = %s
			AND pm.meta_value <> ''
			AND CAST(pm.meta_value AS DECIMAL(20,4)) > 0",
			$meta_key
		)
	);
	$min = ( $row && $row->min_value !== null ) ? floor( (float) $row->min_value ) : (float) $fallback_min;
	$max = ( $row && $row->max_value !== null ) ? ceil( (float) $row->max_value ) : (float) $fallback_max;
	if ( $max <= $min ) {
		$min = (float) $fallback_min;
		$max = (float) $fallback_max;
	}
	$bounds = array(
		'min' => $min,
		'max' => $max,
	);
	set_transient( $cache_key, $bounds, HOUR_IN_SECONDS );
	return $bounds;
}
function iws_filter_flush_cached_catalog_data() {
	delete_transient( 'iws_filter_variation_attribute_options_v2' );
	foreach ( array( '_price', '_height', '_width', '_length' ) as $meta_key ) {
		delete_transient( 'iws_filter_bounds_' . sanitize_key( $meta_key ) );
	}
}
add_action( 'save_post_product', 'iws_filter_flush_cached_catalog_data' );
add_action( 'save_post_product_variation', 'iws_filter_flush_cached_catalog_data' );
add_action( 'woocommerce_product_set_stock', 'iws_filter_flush_cached_catalog_data' );
add_action( 'woocommerce_variation_set_stock', 'iws_filter_flush_cached_catalog_data' );

function iws_filter_range_control( $key, $label, $min, $max, $step ) {
	if ( (float) $max <= (float) $min ) {
		return;
	}
	$min_name = 'min_' . $key;
	$max_name = 'max_' . $key;
	?>
	<div class="iws-filter-field iws-filter-range">
		<label><?php echo esc_html( $label ); ?></label>
		<div class="iws-filter-range-grid">
			<input type="number" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" name="<?php echo esc_attr( $min_name ); ?>" data-label="<?php echo esc_attr( sprintf( __( 'Min %s', 'wp-theme-woo-support' ), $label ) ); ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Min %s', 'wp-theme-woo-support' ), strtolower( $label ) ) ); ?>">
			<input type="number" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" name="<?php echo esc_attr( $max_name ); ?>" data-label="<?php echo esc_attr( sprintf( __( 'Max %s', 'wp-theme-woo-support' ), $label ) ); ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Max %s', 'wp-theme-woo-support' ), strtolower( $label ) ) ); ?>">
			<div class="iws-range-slider">
				<input class="iws-range-input" type="range" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( $min ); ?>" data-pair="<?php echo esc_attr( $min_name ); ?>" data-value-target="<?php echo esc_attr( $min_name ); ?>_value">
				<input class="iws-range-input" type="range" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( $max ); ?>" data-pair="<?php echo esc_attr( $max_name ); ?>" data-value-target="<?php echo esc_attr( $max_name ); ?>_value">
			</div>
			<div class="iws-range-values" aria-live="polite">
				<span id="<?php echo esc_attr( $min_name ); ?>_value"><?php echo esc_html( $min ); ?></span>
				<span id="<?php echo esc_attr( $max_name ); ?>_value"><?php echo esc_html( $max ); ?></span>
			</div>
		</div>
	</div>
	<?php
}
function iws_filter_attribute_controls() {
	$options = iws_filter_get_variation_attribute_options();
	if ( empty( $options ) ) {
		return;
	}
	foreach ( $options as $key => $values ) {
		if ( empty( $values ) ) {
			continue;
		}
		$taxonomy = str_replace( 'attribute_', '', $key );
		$label = taxonomy_exists( $taxonomy ) ? wc_attribute_label( $taxonomy ) : ucwords(
			str_replace( array( 'attribute_', 'pa_', '-', '_' ), array( '', '', ' ', ' ' ), $key )
		);
		echo '<div class="iws-filter-field iws-filter-attribute">';
		echo '<label>' . esc_html( $label ) . '</label>';
		echo '<select name="' . esc_attr( $key ) . '" data-label="' . esc_attr( $label ) . '">';
		echo '<option value="">' . esc_html__( 'Any', 'wp-theme-woo-support' ) . '</option>';
		foreach ( $values as $value ) {
			$display = $value;
			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					$display = $term->name;
				}
			}
			echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $display ) . '</option>';
		}
		echo '</select>';
		echo '</div>';
	}
}
function iws_filter_get_variation_attribute_options() {
	$cached = get_transient( 'iws_filter_variation_attribute_options_v2' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT DISTINCT pm.meta_key, pm.meta_value
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		WHERE p.post_type = 'product_variation'
		AND p.post_status IN ('publish','private')
		AND pm.meta_key LIKE 'attribute_%'
		AND pm.meta_value <> ''
		ORDER BY pm.meta_key ASC, pm.meta_value ASC",
		ARRAY_A
	);
	$options = array();
	foreach ( $rows as $row ) {
		$key   = sanitize_key( $row['meta_key'] );
		$value = wc_clean( wp_unslash( $row['meta_value'] ) );
		if ( $value === '' ) {
			continue;
		}
		$options[ $key ][] = $value;
	}
	foreach ( $options as $key => $values ) {
		$values           = array_values( array_unique( array_filter( $values ) ) );
		sort( $values, SORT_NATURAL | SORT_FLAG_CASE );
		$options[ $key ] = array_slice( $values, 0, 80 );
	}
	set_transient( 'iws_filter_variation_attribute_options_v2', $options, HOUR_IN_SECONDS );
	return $options;
}
function iws_product_filter_results_shortcode( $atts ) {
	$s    = iws_filter_get_settings();
	$atts = shortcode_atts(
		array(
			'posts_per_page' => $s['posts_per_page'],
		),
		$atts,
		'iws_product_filter_results'
	);
	$ppp  = max( 1, absint( $atts['posts_per_page'] ) );
	$args = iws_filter_build_query_args(
		array_merge(
			$_GET,
			array(
				'posts_per_page' => $ppp,
				'paged'          => max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 ),
			),
			iws_filter_current_taxonomy_context()['taxonomy'] ? array(
				'iws_filter_context_taxonomy' => iws_filter_current_taxonomy_context()['taxonomy'],
				'iws_filter_context_slug'     => iws_filter_current_taxonomy_context()['slug'],
			) : array()
		)
	);
	$q = new WP_Query( $args );
	ob_start();
	echo '<div class="iws-filter-results-wrap">';
	echo iws_filter_render_results( $q, $ppp, 1 );
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
}
function iws_filter_products_ajax() {
	if ( empty( $_POST['iws_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iws_filter_nonce'] ) ), 'iws_filter_products' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
	}
	$ppp    = ! empty( $_POST['posts_per_page'] ) ? max( 1, absint( $_POST['posts_per_page'] ) ) : absint( iws_filter_get_settings()['posts_per_page'] );
	$paged  = ! empty( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
	$append = ! empty( $_POST['append'] );
	$args = iws_filter_build_query_args( $_POST );
	$q    = new WP_Query( $args );
	if ( $append ) {
		$products_html  = iws_filter_render_products_only( $q );
		$load_more_html = iws_filter_render_load_more( $q, $ppp, $paged );
		$count_text     = iws_filter_count_text( $q, $ppp, $paged );
		wp_reset_postdata();
		wp_send_json_success(
			array(
				'products_html'        => $products_html,
				'load_more_html'       => $load_more_html,
				'count_text'           => wp_strip_all_tags( $count_text ),
				'available_attributes' => iws_filter_available_attribute_values( $_POST ),
			)
		);
	}
	$html = iws_filter_render_results( $q, $ppp, $paged );
	wp_reset_postdata();
	wp_send_json_success(
		array(
			'html'                 => $html,
			'available_attributes' => iws_filter_available_attribute_values( $_POST ),
		)
	);
}
function iws_filter_available_attribute_values( $source ) {
	global $wpdb;
	$source       = is_array( $source ) ? $source : array();
	$clean_source = $source;
	foreach ( $clean_source as $key => $value ) {
		if ( strpos( (string) $key, 'attribute_' ) === 0 ) {
			unset( $clean_source[ $key ] );
		}
	}
	$clean_source['posts_per_page'] = -1;
	$clean_source['paged']          = 1;
	$args                           = iws_filter_build_query_args( $clean_source );
	$args['fields']                 = 'ids';
	$args['no_found_rows']          = true;
	$args['update_post_meta_cache'] = false;
	$args['update_post_term_cache'] = false;
	$args['posts_per_page']         = -1;
	$product_ids = get_posts( $args );
	$product_ids = array_values( array_filter( array_map( 'absint', (array) $product_ids ) ) );
	if ( empty( $product_ids ) ) {
		return array();
	}
	$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
	$sql          = "SELECT DISTINCT pm.meta_key, pm.meta_value FROM {$wpdb->posts} v INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = v.ID WHERE v.post_type = 'product_variation' AND v.post_parent IN ({$placeholders}) AND pm.meta_key LIKE 'attribute_%' AND pm.meta_value <> ''";
	$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $product_ids ), ARRAY_A );
	$out          = array();
	foreach ( (array) $rows as $row ) {
		$key   = sanitize_key( $row['meta_key'] );
		$value = wc_clean( wp_unslash( $row['meta_value'] ) );
		if ( $key === '' || $value === '' ) {
			continue;
		}
		if ( ! isset( $out[ $key ] ) ) {
			$out[ $key ] = array();
		}
		$out[ $key ][] = $value;
	}
	foreach ( $out as $key => $values ) {
		$values      = array_values( array_unique( array_filter( $values ) ) );
		sort( $values, SORT_NATURAL | SORT_FLAG_CASE );
		$out[ $key ] = $values;
	}
	return $out;
}
function iws_filter_build_query_args( $source ) {
	$ppp   = ! empty( $source['posts_per_page'] ) ? absint( $source['posts_per_page'] ) : absint( iws_filter_get_settings()['posts_per_page'] );
	$paged = ! empty( $source['paged'] ) ? absint( $source['paged'] ) : 1;
	$post_in = null;
	if ( ! empty( $source['product_search'] ) ) {
		$search_ids = iws_filter_search_product_ids( sanitize_text_field( wp_unslash( $source['product_search'] ) ) );
		$post_in    = iws_filter_intersect_ids( $post_in, $search_ids );
	}
	$attribute_ids = iws_filter_variation_attribute_product_ids( $source );
	if ( is_array( $attribute_ids ) ) {
		$post_in = iws_filter_intersect_ids( $post_in, $attribute_ids );
	}
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $ppp,
		'paged'          => $paged,
		'meta_query'     => array(),
		'tax_query'      => iws_filter_catalog_visibility_query(),
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'cache_results'  => true,
	);
	if ( is_array( $post_in ) ) {
		$args['post__in'] = ! empty( $post_in ) ? array_values( array_unique( array_map( 'absint', $post_in ) ) ) : array( 0 );
	}
	if ( ! empty( $source['product_cat'] ) ) {
		iws_filter_add_tax_constraint( $args, 'product_cat', $source['product_cat'] );
	}
	if ( ! empty( $source['product_tag'] ) ) {
		iws_filter_add_tax_constraint( $args, 'product_tag', $source['product_tag'] );
	}
	if ( ! empty( $source['product_brand'] ) ) {
		$brand_taxonomy = iws_filter_brand_taxonomy();
		if ( $brand_taxonomy ) {
			iws_filter_add_tax_constraint( $args, $brand_taxonomy, $source['product_brand'] );
		}
	}
	if ( ! empty( $source['iws_filter_context_taxonomy'] ) && ! empty( $source['iws_filter_context_slug'] ) ) {
		$taxonomy = sanitize_key( wp_unslash( $source['iws_filter_context_taxonomy'] ) );
		$already_constrained = ( 'product_cat' === $taxonomy && ! empty( $source['product_cat'] ) ) || ( 'product_tag' === $taxonomy && ! empty( $source['product_tag'] ) );
		if ( ! $already_constrained && in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
			iws_filter_add_tax_constraint( $args, $taxonomy, $source['iws_filter_context_slug'] );
		}
	}
	iws_filter_add_range( $args, '_price', $source, 'min_price', 'max_price' );
	iws_filter_add_range( $args, '_length', $source, 'min_length', 'max_length' );
	iws_filter_add_range( $args, '_width', $source, 'min_width', 'max_width' );
	iws_filter_add_range( $args, '_height', $source, 'min_height', 'max_height' );
	if ( ! empty( $source['in_stock'] ) ) {
		$args['meta_query'][] = array(
			'key'   => '_stock_status',
			'value' => 'instock',
		);
	}
	if ( ! empty( $source['min_rating'] ) ) {
		$rating = max( 1, min( 5, (float) wc_format_decimal( wp_unslash( $source['min_rating'] ) ) ) );
		$args['meta_query'][] = array(
			'key'     => '_wc_average_rating',
			'value'   => $rating,
			'compare' => '>=',
			'type'    => 'DECIMAL(3,2)',
		);
	}
	if ( ! empty( $source['on_sale'] ) ) {
		$sale_ids = array_values( array_filter( array_map( 'absint', (array) wc_get_product_ids_on_sale() ) ) );
		$post_in  = iws_filter_intersect_ids( array_key_exists( 'post__in', $args ) ? $args['post__in'] : null, $sale_ids );
		$args['post__in'] = $post_in ? $post_in : array( 0 );
	}
	$sort = isset( $source['product_orderby'] ) ? sanitize_key( wp_unslash( $source['product_orderby'] ) ) : 'default';
	switch ( $sort ) {
		case 'popularity':
			$args['meta_key'] = 'total_sales';
			$args['orderby']  = array( 'meta_value_num' => 'DESC', 'title' => 'ASC' );
			break;
		case 'rating':
			$args['meta_key'] = '_wc_average_rating';
			$args['orderby']  = array( 'meta_value_num' => 'DESC', 'title' => 'ASC' );
			break;
		case 'newest':
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;
		case 'price_asc':
			$args['meta_key'] = '_price';
			$args['orderby']  = array( 'meta_value_num' => 'ASC', 'title' => 'ASC' );
			break;
		case 'price_desc':
			$args['meta_key'] = '_price';
			$args['orderby']  = array( 'meta_value_num' => 'DESC', 'title' => 'ASC' );
			break;
	}
	if ( count( $args['tax_query'] ) > 1 ) {
		$args['tax_query']['relation'] = 'AND';
	}
	if ( count( $args['meta_query'] ) > 1 ) {
		$args['meta_query']['relation'] = 'AND';
	}
	if ( empty( $args['tax_query'] ) ) {
		unset( $args['tax_query'] );
	}
	if ( empty( $args['meta_query'] ) ) {
		unset( $args['meta_query'] );
	}
	return $args;
}
function iws_filter_intersect_ids( $base, $ids ) {
	$ids = array_values( array_unique( array_map( 'absint', (array) $ids ) ) );
	if ( $base === null ) {
		return $ids;
	}
	return array_values( array_intersect( (array) $base, $ids ) );
}
function iws_filter_search_product_ids( $term ) {
	global $wpdb;
	$like = '%' . $wpdb->esc_like( $term ) . '%';
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND (p.post_title LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s OR sku.meta_value LIKE %s)",
			$like,
			$like,
			$like,
			$like
		)
	);
	$variation_parent_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT p.post_parent
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
			WHERE p.post_type = 'product_variation'
			AND p.post_parent > 0
			AND sku.meta_value LIKE %s",
			$like
		)
	);
	return array_values(
		array_unique(
			array_merge(
				array_map( 'absint', $ids ),
				array_map( 'absint', $variation_parent_ids )
			)
		)
	);
}
function iws_filter_variation_attribute_product_ids( $source ) {
	global $wpdb;
	$meta_query = array();
	foreach ( $source as $key => $value ) {
		if ( strpos( $key, 'attribute_' ) !== 0 || $value === '' ) {
			continue;
		}
		$key   = sanitize_key( $key );
		$value = wc_clean( wp_unslash( $value ) );
		$meta_query[] = array(
			'key'   => $key,
			'value' => $value,
		);
	}
	if ( empty( $meta_query ) ) {
		return null;
	}
	$sql   = "SELECT DISTINCT v.post_parent FROM {$wpdb->posts} v";
	$where = array(
		"v.post_type = 'product_variation'",
		'v.post_parent > 0',
	);
	foreach ( $meta_query as $index => $mq ) {
		$alias   = 'pm' . $index;
		$sql    .= " INNER JOIN {$wpdb->postmeta} {$alias} ON {$alias}.post_id = v.ID";
		$where[] = $wpdb->prepare( "{$alias}.meta_key = %s AND {$alias}.meta_value = %s", $mq['key'], $mq['value'] );
	}
	$sql .= ' WHERE ' . implode( ' AND ', $where );
	return array_map( 'absint', $wpdb->get_col( $sql ) );
}
function iws_filter_add_range( &$args, $key, $source, $min_key, $max_key ) {
	$min = isset( $source[ $min_key ] ) && $source[ $min_key ] !== '' ? wc_format_decimal( wp_unslash( $source[ $min_key ] ) ) : '';
	$max = isset( $source[ $max_key ] ) && $source[ $max_key ] !== '' ? wc_format_decimal( wp_unslash( $source[ $max_key ] ) ) : '';
	if ( $min === '' && $max === '' ) {
		return;
	}
	$args['meta_query'][] = array(
		'key'     => $key,
		'value'   => array(
			$min !== '' ? $min : 0,
			$max !== '' ? $max : 999999999,
		),
		'compare' => 'BETWEEN',
		'type'    => 'NUMERIC',
	);
}
function iws_filter_count_text( $q, $ppp, $paged ) {
	$total = (int) $q->found_posts;
	if ( $total < 1 ) {
		return __( 'No products found', 'wp-theme-woo-support' );
	}
	$shown_start = ( ( $paged - 1 ) * $ppp ) + 1;
	$shown_end   = min( $paged * $ppp, $total );
	return sprintf(
		__( 'Showing %1$d–%2$d of %3$d results', 'wp-theme-woo-support' ),
		(int) $shown_start,
		(int) $shown_end,
		(int) $total
	);
}
function iws_filter_render_results( $q, $ppp, $paged ) {
	ob_start();
	echo '<p class="woocommerce-result-count iws-result-count">' . esc_html( iws_filter_count_text( $q, $ppp, $paged ) ) . '</p>';
	echo iws_filter_render_products_only( $q );
	echo iws_filter_render_load_more( $q, $ppp, $paged );
	return ob_get_clean();
}
function iws_filter_render_load_more( $q, $ppp, $paged ) {
	$total     = (int) $q->found_posts;
	$shown_end = min( $paged * $ppp, $total );
	if ( $shown_end >= $total ) {
		return '';
	}
	return '<div class="iws-load-more-wrap"><button type="button" class="btn btn-primary iws-load-more" data-next-page="' . esc_attr( $paged + 1 ) . '" data-loading="' . esc_attr__( 'Loading...', 'wp-theme-woo-support' ) . '"><span class="iws-load-more-text">' . esc_html__( 'Load more', 'wp-theme-woo-support' ) . '</span><span class="iws-btn-spinner" aria-hidden="true"></span></button></div>';
}
function iws_filter_render_products_only( $q ) {
	$previous_shop_archive_flag = ! empty( $GLOBALS['iws_rendering_shop_archive_products'] );

	/*
	 * AJAX requests render via admin-ajax.php, so WordPress/WooCommerce cannot
	 * detect the main shop archive with is_shop(). Set a narrow render flag so
	 * shop-only loop additions, including the SKU badge, are still printed for
	 * Load more products. Taxonomy archives use a separate renderer and do not
	 * call this function.
	 */
	$GLOBALS['iws_rendering_shop_archive_products'] = true;

	ob_start();
	echo '<ul class="products iws-products-grid">';
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			wc_get_template_part( 'content', 'product' );
		}
	} else {
		echo '<li class="product iws-no-products">' . esc_html__( 'No products found.', 'wp-theme-woo-support' ) . '</li>';
	}
	echo '</ul>';

	if ( $previous_shop_archive_flag ) {
		$GLOBALS['iws_rendering_shop_archive_products'] = true;
	} else {
		unset( $GLOBALS['iws_rendering_shop_archive_products'] );
	}

	return ob_get_clean();
}

function iws_compare_clean_text( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = wp_specialchars_decode( html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) ), ENT_QUOTES );
	$value = preg_replace( '/\s+/', ' ', $value );
	return trim( $value );
}
function iws_compare_price_text( WC_Product $product ) {
	$price = $product->get_price_html();
	$price = iws_compare_clean_text( $price );
	$price = str_replace( array( '&pound;', '&ndash;', '&mdash;' ), array( '£', '–', '—' ), $price );
	return $price;
}
function iws_compare_product_dimensions( WC_Product $product ) {
	$dimensions = wc_format_dimensions( $product->get_dimensions( false ) );
	return $dimensions && $dimensions !== __( 'N/A', 'woocommerce' ) ? $dimensions : '';
}
function iws_compare_attribute_value_label( $taxonomy, $value ) {
	$value = (string) $value;
	if ( taxonomy_exists( $taxonomy ) ) {
		$term = get_term_by( 'slug', $value, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			return $term->name;
		}
	}
	return wc_clean( rawurldecode( $value ) );
}
function iws_compare_product_attributes( WC_Product $product ) {
	$out = array();
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute || ! $attribute->get_visible() ) {
			continue;
		}
		$label = wc_attribute_label( $attribute->get_name(), $product );
		if ( $attribute->is_taxonomy() ) {
			$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
		} else {
			$values = $attribute->get_options();
		}
		$values = array_values( array_filter( array_map( 'iws_compare_clean_text', (array) $values ) ) );
		if ( $label && $values ) {
			$out[] = array( 'label' => $label, 'value' => implode( ', ', $values ) );
		}
	}
	return $out;
}
function iws_compare_variations_payload( WC_Product $product ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return array();
	}
	$out      = array();
	$children = $product->get_children();
	$limit    = min( 40, max( 1, absint( apply_filters( 'iws_compare_variation_limit', 16, $product ) ) ) );
	foreach ( array_slice( $children, 0, $limit ) as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof WC_Product_Variation ) {
			continue;
		}
		$attrs = array();
		foreach ( $variation->get_attributes() as $key => $value ) {
			if ( $value === '' ) {
				continue;
			}
			$taxonomy = str_replace( 'attribute_', '', $key );
			$attrs[]  = array(
				'label' => wc_attribute_label( $taxonomy, $product ),
				'value' => iws_compare_attribute_value_label( $taxonomy, $value ),
			);
		}
		$stock_options = wc_get_product_stock_status_options();
		$out[] = array(
			'id'         => $variation_id,
			'sku'        => $variation->get_sku(),
			'price'      => iws_compare_price_text( $variation ),
			'attributes' => $attrs,
			'dimensions' => iws_compare_product_dimensions( $variation ),
			'weight'     => $variation->get_weight(),
			'stock'      => wc_get_stock_html( $variation ) ? iws_compare_clean_text( wc_get_stock_html( $variation ) ) : ( $stock_options[ $variation->get_stock_status() ] ?? $variation->get_stock_status() ),
		);
	}
	if ( count( $children ) > $limit ) {
		$out[] = array(
			'id'         => 0,
			'sku'        => '',
			'price'      => '',
			'attributes' => array( array( 'label' => __( 'More variations', 'wp-theme-woo-support' ), 'value' => sprintf( __( '%d more variations not shown', 'wp-theme-woo-support' ), count( $children ) - $limit ) ) ),
			'dimensions' => '',
			'weight'     => '',
			'stock'      => '',
		);
	}
	return $out;
}
function iws_compare_product_payload( WC_Product $product ) {
	$image_id      = $product->get_image_id();
	$image         = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
	$cats          = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
	$stock_options = wc_get_product_stock_status_options();
	$product_types = wc_get_product_types();
	return array(
		'id'         => $product->get_id(),
		'title'      => $product->get_name(),
		'url'        => get_permalink( $product->get_id() ),
		'image'      => $image,
		'type'       => ( $product_types[ $product->get_type() ] ?? ucfirst( $product->get_type() ) ),
		'sku'        => $product->get_sku(),
		'price'      => iws_compare_price_text( $product ),
		'categories' => is_wp_error( $cats ) ? array() : array_values( $cats ),
		'dimensions' => iws_compare_product_dimensions( $product ),
		'weight'     => $product->get_weight(),
		'short_desc' => iws_compare_clean_text( wp_trim_words( $product->get_short_description(), 24 ) ),
		'stock'      => wc_get_stock_html( $product ) ? iws_compare_clean_text( wc_get_stock_html( $product ) ) : ( $stock_options[ $product->get_stock_status() ] ?? $product->get_stock_status() ),
		'attributes' => iws_compare_product_attributes( $product ),
		'variations' => iws_compare_variations_payload( $product ),
	);
}
function iws_compare_loop_button() {
	if ( ! iws_filter_frontend_needs_assets() ) {
		return;
	}
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	if ( function_exists( 'is_product' ) && is_product() ) {
		return;
	}
	$GLOBALS['iws_compare_has_products'] = true;
	$payload = iws_compare_product_payload( $product );
	echo '<button type="button" class="iws-compare-toggle" data-product-id="' . esc_attr( $product->get_id() ) . '" aria-label="' . esc_attr__( 'Add to comparison', 'wp-theme-woo-support' ) . '" aria-pressed="false"><span class="iws-compare-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M5 8.5h14M5 15.5h14"/></svg></span></button>';
	echo '<script type="application/json" class="iws-compare-product-data" data-product-id="' . esc_attr( $product->get_id() ) . '">' . wp_json_encode( $payload ) . '</script>';
}
function iws_compare_modal_markup() {
	if ( is_admin() || empty( $GLOBALS['iws_compare_has_products'] ) ) {
		return;
	}
	?>
	<div class="iws-compare-modal" hidden aria-hidden="true">
		<div class="iws-compare-modal__backdrop" data-iws-compare-close></div>
		<div class="iws-compare-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="iws-compare-title">
			<div class="iws-compare-modal__head">
				<h2 id="iws-compare-title"><?php esc_html_e( 'Compare products', 'wp-theme-woo-support' ); ?></h2>
				<button type="button" class="iws-compare-modal__close" data-iws-compare-close aria-label="<?php esc_attr_e( 'Close comparison', 'wp-theme-woo-support' ); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
			</div>
			<div class="iws-compare-modal__body"></div>
		</div>
	</div>
	<?php
}

/**
 * Product filter compatibility styles.
 *
 * Behaviour lives in assets/woo/js/iws-product-filter.js to avoid Gutenberg/wpautop
 * adding paragraph tags inside inline JavaScript.
 */
function iws_filter_hotfix_assets() {
	if ( ! iws_filter_frontend_needs_assets() ) {
		return;
	}
	$css='.iws-filter-wrapper{margin-top:0!important}.iws-filter-field select option:disabled{color:#aaa!important;background:#f4f4f4!important}.iws-filter-wrapper+.iws-filter-results-wrap{margin-top:0!important}.tpl__woo-search-cat{margin-bottom:16px!important}.tpl__woo-search-cat .wp-block-separator{margin-top:18px!important;margin-bottom:0!important}.tpl__woo-search-cat .wc-block-product-categories-list,.tpl__woo-search-cat .wp-block-woocommerce-product-categories ul{display:flex!important;flex-wrap:wrap!important;gap:8px!important;margin:0!important;padding:0!important;list-style:none!important;border:0!important;box-shadow:none!important;background:transparent!important}.tpl__woo-search-cat .wc-block-product-categories-list li,.tpl__woo-search-cat .wp-block-woocommerce-product-categories li{margin:0!important;padding:0!important;list-style:none!important;border:0!important;background:transparent!important;box-shadow:none!important}.tpl__woo-search-cat .wc-block-product-categories-list a,.tpl__woo-search-cat .wp-block-woocommerce-product-categories a{display:inline-flex!important;align-items:center!important;min-height:34px!important;padding:7px 12px!important;border:1px solid #d7d7d7!important;border-radius:999px!important;background:#fff!important;color:var(--wp-theme-ink,#111)!important;font-size:13px!important;font-weight:800!important;text-decoration:none!important;box-shadow:none!important;outline:0!important;transition:background .18s ease,border-color .18s ease,box-shadow .18s ease,transform .18s ease!important}.tpl__woo-search-cat .wc-block-product-categories-list a:hover,.tpl__woo-search-cat .wp-block-woocommerce-product-categories a:hover,.tpl__woo-search-cat .is-iws-active-category>a{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;box-shadow:0 5px 16px rgba(0,0,0,.08)!important;transform:translateY(-1px)!important}.woocommerce .wp-block-woocommerce-product-categories.hidden,.wp-block-woocommerce-product-categories.hidden{display:none!important;max-width:260px!important;margin:-12px 0 22px auto!important;position:relative!important;z-index:4!important}.wp-block-woocommerce-product-categories.hidden select,.wp-block-woocommerce-product-categories.hidden .select2-container{width:100%!important;min-height:36px!important}.wp-block-woocommerce-product-categories.hidden select{border:1px solid #d7d7d7!important;border-radius:var(--wp-theme-radius,10px)!important;background:#fff!important;color:var(--wp-theme-ink,#111)!important;font-size:13px!important;padding:7px 36px 7px 10px!important}@media(max-width:760px){.woocommerce .wp-block-woocommerce-product-categories.hidden,.wp-block-woocommerce-product-categories.hidden{max-width:100%!important;margin:0 0 20px!important}}';
	$css .= '.iws-filter-wrapper button,.iws-filter-wrapper summary:after,.iws-filter-wrapper .iws-search-icon,.iws-filter-wrapper .iws-search-clear,.iws-filter-wrapper .iws-compare-open--search,.iws-filter-results-wrap .iws-compare-toggle,.iws-compare-modal button{display:inline-flex!important;align-items:center!important;justify-content:center!important;text-align:center!important;vertical-align:middle!important;line-height:1!important;white-space:nowrap!important}.iws-filter-wrapper svg,.iws-filter-results-wrap svg,.iws-compare-modal svg{display:block!important;flex:0 0 auto!important}.iws-search-row{display:grid!important;grid-template-columns:minmax(0,1fr) 42px!important;align-items:center!important;column-gap:10px!important;width:100%!important}.iws-search-row .iws-search-input-wrap{grid-column:1!important;width:100%!important;min-width:0!important}.iws-search-row .iws-compare-open--search{grid-column:2!important;position:static!important;inset:auto!important;transform:none!important;margin:0!important}.iws-search-input-wrap .iws-search-input,.iws-search-input-wrap input[type=search]{padding-right:78px!important}.iws-search-input-wrap .iws-search-icon{right:15px!important;width:26px!important;height:26px!important;margin:0!important;transform:translateY(-50%)!important}.iws-search-input-wrap .iws-search-clear{right:47px!important;width:26px!important;height:26px!important;min-width:26px!important;border-radius:50%!important;padding:0!important;margin:0!important;font-size:0!important}.iws-search-clear svg{width:12px!important;height:12px!important}.iws-search-clear svg path{fill:none!important;stroke:currentColor!important;stroke-width:2.5!important;stroke-linecap:round!important}.iws-compare-open--search,.iws-products-grid li.product .iws-compare-toggle,.woocommerce ul.products li.product .iws-compare-toggle{background:#fff!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:var(--wp-theme-ink,#111)!important}.iws-compare-open--search svg path,.iws-products-grid li.product .iws-compare-toggle svg path,.woocommerce ul.products li.product .iws-compare-toggle svg path{stroke:#111!important}.iws-compare-open--search.has-products,.iws-compare-open--search.is-selected,.iws-compare-open--search.is-active,.iws-compare-open--search.active,.iws-compare-open--search[aria-pressed="true"],.iws-products-grid li.product .iws-compare-toggle.is-selected,.woocommerce ul.products li.product .iws-compare-toggle.is-selected{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important}.iws-compare-open--search.has-products svg path,.iws-compare-open--search.is-selected svg path,.iws-compare-open--search.is-active svg path,.iws-compare-open--search.active svg path,.iws-compare-open--search[aria-pressed="true"] svg path,.iws-products-grid li.product .iws-compare-toggle.is-selected svg path,.woocommerce ul.products li.product .iws-compare-toggle.is-selected svg path{stroke:#fff!important}.iws-compare-modal__close,.iws-compare-remove{background:#111!important;color:#fff!important;border:0!important;border-radius:50%!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;padding:0!important;margin:0!important;line-height:1!important;font-size:0!important}.iws-compare-modal__close svg,.iws-compare-remove svg{width:14px!important;height:14px!important}.iws-compare-modal__close svg path,.iws-compare-remove svg path{fill:none!important;stroke:#fff!important;stroke-width:2.6!important;stroke-linecap:round!important;stroke-linejoin:round!important}.iws-compare-modal__head h2,.iws-compare-modal button,.iws-compare-table th,.iws-compare-table a,.iws-compare-table strong,.iws-compare-variations summary,.iws-filter-wrapper label,.iws-filter-wrapper button,.iws-filter-chip,.iws-range-values,.iws-products-grid li.product .woocommerce-loop-product__title,.iws-products-grid li.product .price,.iws-products-grid li.product .button,.iws-products-grid li.product .add_to_cart_button,.iws-products-grid li.product .product_type_variable{font-weight:700!important}.iws-compare-table thead th{position:relative!important;padding-top:34px!important}.iws-compare-table thead th:first-child{padding-top:12px!important}.iws-compare-remove{position:absolute!important;top:10px!important;right:10px!important;float:none!important;width:24px!important;height:24px!important;min-width:24px!important}.iws-compare-clear-all{border-radius:6px!important;min-height:38px!important;padding:9px 14px!important}.iws-filter-actions{grid-column:1/-1!important;order:100!important;display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:10px!important;width:100%!important;margin-top:2px!important}.iws-filter-actions button{display:inline-flex!important;align-items:center!important;justify-content:center!important;height:38px!important;min-width:86px!important;padding:0 18px!important;font-weight:700!important}.iws-filter-more summary{display:flex!important;align-items:center!important}.iws-filter-more summary:after{font-family:Arial,sans-serif!important;font-size:16px!important;font-weight:700!important;line-height:1!important;padding:0!important;margin:0!important;color:#fff!important}.iws-filter-more[open] summary:after{font-size:18px!important}.tpl__woo-search-cat a{display:inline-flex!important;align-items:center!important;justify-content:center!important;line-height:1.2!important}.tpl__woo-search-cat a span{font-weight:600!important}.tpl__woo-search-cat a:hover,.tpl__woo-search-cat .is-iws-active-category>a{color:#fff!important}.tpl__woo-search-cat a:hover *,.tpl__woo-search-cat .is-iws-active-category>a *{color:#fff!important}@media(max-width:760px){.iws-search-row{grid-template-columns:minmax(0,1fr) 40px!important;column-gap:8px!important}.iws-search-row .iws-compare-open--search{width:40px!important;height:40px!important;min-width:40px!important}.iws-search-input-wrap .iws-search-input,.iws-search-input-wrap input[type=search]{padding-right:74px!important}.iws-search-input-wrap .iws-search-clear{right:44px!important}}';

	$css .= '.iws-filter-wrapper,.iws-filter-form,.iws-filter-more,.iws-filter-more summary,.iws-filter-more__content,.iws-search-row,.iws-search-input-wrap{white-space:normal!important}.iws-filter-form{display:grid!important;grid-template-columns:repeat(12,minmax(0,1fr))!important;gap:12px!important;align-items:start!important;line-height:normal!important}.iws-filter-form br,.iws-filter-form > p:empty,.iws-filter-form p:empty,.iws-filter-form .wp-block-spacer:empty,.iws-filter-form .wp-block-spacer{display:none!important;width:0!important;height:0!important;min-width:0!important;min-height:0!important;margin:0!important;padding:0!important;overflow:hidden!important;line-height:0!important}.iws-compare-open--search,.iws-products-grid li.product .iws-compare-toggle,.woocommerce ul.products li.product .iws-compare-toggle{background:#fff!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:var(--wp-theme-ink,#111)!important}.iws-compare-open--search svg path,.iws-products-grid li.product .iws-compare-toggle svg path,.woocommerce ul.products li.product .iws-compare-toggle svg path{stroke:#111!important}.iws-compare-open--search.has-products,.iws-compare-open--search.is-selected,.iws-compare-open--search.is-active,.iws-compare-open--search.active,.iws-compare-open--search[aria-pressed="true"],.iws-products-grid li.product .iws-compare-toggle.is-selected,.woocommerce ul.products li.product .iws-compare-toggle.is-selected{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-compare-open--search.has-products svg path,.iws-compare-open--search.is-selected svg path,.iws-compare-open--search.is-active svg path,.iws-compare-open--search.active svg path,.iws-compare-open--search[aria-pressed="true"] svg path,.iws-products-grid li.product .iws-compare-toggle.is-selected svg path,.woocommerce ul.products li.product .iws-compare-toggle.is-selected svg path{stroke:#fff!important}.iws-search-clear[hidden],.iws-search-clear.is-hidden,.iws-search-clear[aria-hidden="true"]{display:none!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important}.iws-search-clear:not([hidden]):not(.is-hidden)[aria-hidden="false"]{display:inline-flex!important;visibility:visible!important;opacity:1!important;pointer-events:auto!important}.iws-filter-more summary:after{display:inline-grid!important;place-items:center!important;width:22px!important;height:22px!important;min-width:22px!important;min-height:22px!important;padding:0!important;margin:0!important;border-radius:50%!important;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important;font-family:Arial,sans-serif!important;font-size:15px!important;font-weight:400!important;line-height:22px!important;text-align:center!important;letter-spacing:0!important;vertical-align:middle!important;transform:none!important}.iws-filter-more[open] summary:after{font-size:16px!important;font-weight:400!important;line-height:22px!important}.iws-compare-swatches{display:flex!important;flex-wrap:wrap!important;gap:5px!important;margin:0 0 6px!important}.iws-compare-swatch{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:24px!important;padding:3px 8px!important;border:1px solid #ddd!important;border-radius:999px!important;background:#f7f7f7!important;color:var(--wp-theme-ink,#111)!important;font-size:12px!important;font-weight:600!important;line-height:1.2!important}.iws-compare-swatch--color{min-width:24px!important;width:24px!important;padding:0!important;border-radius:50%!important;font-size:0!important;box-shadow:inset 0 0 0 1px rgba(0,0,0,.12)!important}.iws-compare-variation-title{margin:0 0 6px!important;font-weight:700!important}.iws-compare-variation small{word-break:normal!important}.iws-filter-actions{grid-column:1/-1!important;order:100!important;justify-content:flex-start!important;align-self:start!important;margin-top:4px!important}';

	$css .= '.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search,.iws-compare-open.iws-compare-open--search{position:relative!important;inset:auto!important;top:auto!important;right:auto!important;bottom:auto!important;left:auto!important;transform:none!important;overflow:visible!important;background:#fff!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:var(--wp-theme-ink,#111)!important;isolation:isolate!important}.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search.is-selected,.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search.is-active,.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search.active,.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search[aria-pressed="true"],.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search.is-selected,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search.is-active,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search.active,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search[aria-pressed="true"],.iws-compare-open.iws-compare-open--search.has-products,.iws-compare-open.iws-compare-open--search.is-selected,.iws-compare-open.iws-compare-open--search.is-active,.iws-compare-open.iws-compare-open--search.active,.iws-compare-open.iws-compare-open--search[aria-pressed="true"]{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search .iws-compare-count,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search .iws-compare-count,.iws-compare-open.iws-compare-open--search .iws-compare-count{position:absolute!important;top:-9px!important;right:-9px!important;left:auto!important;bottom:auto!important;z-index:5!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:20px!important;height:20px!important;padding:2px 6px!important;border-radius:999px!important;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important;border:2px solid #fff!important;font-size:12px!important;font-weight:700!important;line-height:1!important;letter-spacing:0!important;box-shadow:0 2px 8px rgba(0,0,0,.18)!important;transform:none!important}.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search:not(.has-products) .iws-compare-count,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search:not(.has-products) .iws-compare-count{display:none!important}.iws-filter-more summary:after{content:""!important;display:inline-block!important;flex:0 0 22px!important;width:22px!important;height:22px!important;min-width:22px!important;min-height:22px!important;padding:0!important;margin:0!important;border-radius:50%!important;background-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;background-repeat:no-repeat!important;background-position:center!important;background-size:12px 12px!important;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath d=\'M12 5v14M5 12h14\' fill=\'none\' stroke=\'%23fff\' stroke-width=\'2.4\' stroke-linecap=\'round\'/%3E%3C/svg%3E")!important;font-size:0!important;font-weight:400!important;line-height:0!important;text-indent:-9999px!important;overflow:hidden!important;transform:rotate(0deg)!important;transition:transform .18s ease,background-color .18s ease!important}.iws-filter-more[open] summary:after{content:""!important;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath d=\'M5 12h14\' fill=\'none\' stroke=\'%23fff\' stroke-width=\'2.4\' stroke-linecap=\'round\'/%3E%3C/svg%3E")!important;transform:rotate(180deg)!important;font-size:0!important;line-height:0!important}.iws-filter-more summary{align-items:center!important}.iws-filter-more summary>*{line-height:1.2!important}';
	$css .= '.iws-load-more-wrap .btn.btn-primary,.iws-load-more-wrap .iws-load-more{font-weight:600!important}.iws-load-more-wrap .btn.btn-primary *,.iws-load-more-wrap .iws-load-more *{font-weight:600!important}.iws-load-more .iws-btn-spinner,.iws-load-more[disabled] .iws-btn-spinner,.iws-load-more.is-loading .iws-btn-spinner{border-color:rgba(255,255,255,.35)!important;border-top-color:#fff!important}';
	$css .= '.tpl__woo-search-cat .wc-block-product-categories-list-item__image.wc-block-product-categories-list-item__image--placeholder,.tpl__woo-search-cat .wc-block-product-categories-list-item__image--placeholder{display:none!important;width:0!important;height:0!important;min-width:0!important;min-height:0!important;margin:0!important;padding:0!important;overflow:hidden!important}';


	$css .= '.iws-card-button-layout-v24{display:block!important}.wc-block-product-template .product,.products .product,body.archive.woocommerce .products .product,.woocommerce ul.products li.product,.iws-products-grid li.product{padding-bottom:20px!important;display:flex!important;flex-direction:column!important;align-items:stretch!important;position:relative!important;overflow:hidden!important;box-sizing:border-box!important}.wc-block-product-template .product>a.woocommerce-LoopProduct-link,.products .product>a.woocommerce-LoopProduct-link,.woocommerce ul.products li.product>a.woocommerce-LoopProduct-link,.iws-products-grid li.product>a.woocommerce-LoopProduct-link{display:flex!important;flex:1 1 auto!important;flex-direction:column!important;min-height:0!important;color:inherit!important;text-decoration:none!important}.wc-block-product-template .product .price,.wc-block-product-template .product .wc-block-components-product-price,.products .product .price,.woocommerce ul.products li.product .price,.iws-products-grid li.product .price{display:block!important;position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;margin:auto auto 15px auto!important;padding:0!important;float:none!important;clear:both!important;transform:none!important;text-align:left!important}.wc-block-product-template .product .wp-block-button,.wc-block-product-template .product .wc-block-components-product-button,.products .product .wp-block-button,.products .product .wc-block-components-product-button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable,.products .product .tfa-wcqb-loop-wrap,.woocommerce ul.products li.product .tfa-wcqb-loop-wrap{position:static!important;inset:auto!important;display:block!important;width:100%!important;max-width:100%!important;margin:15px auto 0 auto!important;padding:0!important;float:none!important;clear:both!important;transform:none!important}.products .product .tfa-wcqb-loop-wrap,.woocommerce ul.products li.product .tfa-wcqb-loop-wrap,.wc-block-product-template .product .tfa-wcqb-loop-wrap{margin-top:8px!important}.wc-block-product-template .product .wp-element-button,.wc-block-product-template .product .wp-block-button__link,.wc-block-product-template .product .wc-block-components-product-button__button,.products .product .wp-element-button,.products .product .wp-block-button__link,.products .product .wc-block-components-product-button__button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable,.products .product .tfa-wcqb-loop-wrap .tfa-quote-button{display:flex!important;align-items:center!important;justify-content:center!important;position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;min-height:40px!important;margin:15px auto 0 auto!important;padding:0 15px 0 15px!important;border:0!important;border-radius:0 15px 0 0!important;font-weight:500!important;line-height:1.15!important;text-align:center!important;text-decoration:none!important;white-space:normal!important;transform:none!important;box-sizing:border-box!important;overflow:hidden!important}.wc-block-product-template .product .wp-element-button,.wc-block-product-template .product .wp-block-button__link,.wc-block-product-template .product .wc-block-components-product-button__button,.products .product .wp-element-button,.products .product .wp-block-button__link,.products .product .wc-block-components-product-button__button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable{background-color:var(--tfa-green-color)!important;background-image:none!important;background-repeat:no-repeat!important;background-position:calc(100% + 32px) center!important;background-size:18px auto!important;color:#fff!important}.wc-block-product-template .product .wp-element-button:hover,.wc-block-product-template .product .wp-block-button__link:hover,.wc-block-product-template .product .wc-block-components-product-button__button:hover,.products .product .wp-element-button:hover,.products .product .wp-block-button__link:hover,.products .product .wc-block-components-product-button__button:hover,.products .product>a.button:not(.iws-compare-toggle):hover,.products .product>.button:not(.iws-compare-toggle):hover,.products .product>.add_to_cart_button:hover,.products .product>.product_type_simple:hover,.products .product>.product_type_variable:hover{background-position:calc(100% - 18px) center!important}.products .product .tfa-wcqb-loop-wrap .tfa-quote-button,.tfa-quote-button.single{background-image:none!important;background-repeat:no-repeat!important;background-position:calc(100% + 32px) center!important;background-size:16px auto!important;font-weight:500!important}.products .product .tfa-wcqb-loop-wrap .tfa-quote-button:hover,.tfa-quote-button.single:hover{background-position:calc(100% - 18px) center!important}.woocommerce span.onsale,.wc-block-components-product-sale-badge{margin-right:12px!important}';

	wp_add_inline_style( 'iws-product-filter-final', $css );}
add_action( 'wp_enqueue_scripts', 'iws_compare_assets', 100 );
function iws_compare_assets() {
	if ( is_admin() || ! iws_filter_frontend_needs_assets() ) {
		return;
	}
	$css = '.iws-search-input-wrap .iws-search-input,.iws-search-input-wrap input[type=search]{padding-right:126px!important}.iws-search-clear{position:absolute!important;top:50%!important;right:86px!important;width:26px!important;height:26px!important;min-width:26px!important;border:0!important;border-radius:50%!important;background:#f2f2f2!important;color:var(--wp-theme-ink,#111)!important;transform:translateY(-50%)!important;display:flex!important;align-items:center!important;justify-content:center!important;font-size:18px!important;line-height:1!important;font-weight:800!important;padding:0!important;cursor:pointer!important;z-index:8!important}.iws-search-clear[hidden]{display:none!important}.iws-compare-open--search{position:absolute!important;top:50%!important;right:48px!important;width:30px!important;height:30px!important;min-width:30px!important;border:1px solid #d8d8d8!important;border-radius:50%!important;background:#fff!important;color:var(--wp-theme-ink,#111)!important;transform:translateY(-50%)!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:0!important;cursor:pointer!important;z-index:8!important}.iws-compare-open--search.has-products,.iws-compare-open--search.is-selected,.iws-compare-open--search.is-active,.iws-compare-open--search.active,.iws-compare-open--search[aria-pressed="true"]{border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-compare-icon svg{display:block;width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.iws-compare-count{position:absolute;top:-7px;right:-7px;min-width:17px;height:17px;padding:0 4px;border-radius:999px;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb));color:#fff;font-size:10px;font-weight:900;line-height:17px;text-align:center}.iws-products-grid li.product .iws-compare-toggle,.woocommerce ul.products li.product .iws-compare-toggle{position:absolute!important;top:26px!important;right:26px!important;z-index:9!important;width:38px!important;height:38px!important;min-width:38px!important;border:1px solid rgba(0,0,0,.12)!important;border-radius:50%!important;background:#fff!important;color:var(--wp-theme-ink,#111)!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:0!important;cursor:pointer!important;box-shadow:0 6px 18px rgba(0,0,0,.12)!important;transition:background .18s ease,color .18s ease,transform .18s ease!important}.iws-products-grid li.product .iws-compare-toggle:hover,.woocommerce ul.products li.product .iws-compare-toggle:hover{transform:translateY(-1px)!important}.iws-products-grid li.product .iws-compare-toggle.is-selected,.woocommerce ul.products li.product .iws-compare-toggle.is-selected{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-compare-modal[hidden]{display:none!important}.iws-compare-modal{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:24px}.iws-compare-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.55)}.iws-compare-modal__dialog{position:relative;z-index:1;width:min(1180px,calc(100vw - 28px));max-height:calc(100vh - 48px);background:#fff;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.28);display:flex;flex-direction:column;overflow:hidden}.iws-compare-modal__head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 22px;border-bottom:1px solid #eee}.iws-compare-modal__head h2{margin:0;font-size:24px;line-height:1.2;font-weight:900;color:#111}.iws-compare-modal__close{width:34px;height:34px;border:0;border-radius:50%;background:#111;color:#fff;font-size:24px;line-height:1;cursor:pointer}.iws-compare-modal__body{overflow:auto;padding:18px 22px}.iws-compare-tools{display:flex;justify-content:flex-end;margin:0 0 12px}.iws-compare-clear-all{border:0;border-radius:7px;background:#111;color:#fff;font-size:13px;font-weight:800;padding:9px 14px;cursor:pointer}.iws-compare-table-wrap{overflow:auto}.iws-compare-table{width:100%;min-width:760px;border-collapse:collapse;background:#fff}.iws-compare-table th,.iws-compare-table td{border:1px solid #e7e7e7;padding:12px;vertical-align:top;text-align:left;font-size:13px;line-height:1.45;color:#111}.iws-compare-table thead th{background:#f8f8f8;min-width:210px}.iws-compare-table tbody th{width:150px;background:#fbfbfb;font-weight:900}.iws-compare-table img{display:block;width:90px;height:90px;object-fit:contain;background:#fff;margin:0 0 8px}.iws-compare-table a{color:#111;text-decoration:none;font-weight:900}.iws-compare-remove{float:right;width:24px;height:24px;border:0;border-radius:50%;background:#111;color:#fff;line-height:1;font-size:18px;cursor:pointer;margin:-4px -4px 6px 8px}.iws-compare-pairs{margin:0;padding:0;list-style:none}.iws-compare-pairs li{margin:0 0 4px}.iws-compare-variations summary{cursor:pointer;font-weight:900}.iws-compare-variation{padding:8px 0;border-bottom:1px solid #eee}.iws-compare-variation:last-child{border-bottom:0}.iws-compare-variation small{display:block;color:#555;margin-top:3px}.iws-compare-empty,.iws-compare-empty-state{color:#777}.iws-compare-modal-open{overflow:hidden}@media(max-width:760px){.iws-search-input-wrap .iws-search-input,.iws-search-input-wrap input[type=search]{padding-right:116px!important}.iws-search-clear{right:80px!important}.iws-compare-open--search{right:44px!important}.iws-compare-modal{padding:10px}.iws-compare-modal__dialog{max-height:calc(100vh - 20px);width:calc(100vw - 20px)}.iws-compare-modal__body{padding:12px}.iws-compare-table{min-width:680px}}';
	$css .= '.iws-filter-wrapper,.iws-filter-wrapper *,.iws-filter-results-wrap,.iws-filter-results-wrap *,.iws-compare-modal,.iws-compare-modal *{font-weight:inherit}.iws-filter-wrapper label,.iws-filter-wrapper button,.iws-filter-chip,.iws-range-values,.iws-products-grid li.product .woocommerce-loop-product__title,.iws-products-grid li.product .price,.iws-products-grid li.product .button,.iws-products-grid li.product .add_to_cart_button,.iws-products-grid li.product .product_type_variable,.iws-compare-modal h2,.iws-compare-modal button,.iws-compare-table th,.iws-compare-table a,.iws-compare-table strong,.iws-compare-variations summary{font-weight:700!important}.iws-search-row{display:flex!important;align-items:center!important;gap:10px!important;width:100%!important}.iws-search-row .iws-search-input-wrap{flex:1 1 auto!important;min-width:0!important}.iws-search-input-wrap .iws-search-input,.iws-search-input-wrap input[type=search]{padding-right:58px!important}.iws-search-clear{right:48px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;line-height:1!important;font-family:Arial,sans-serif!important;font-size:0!important}.iws-search-clear span{display:block!important;font-size:18px!important;line-height:1!important;transform:translateY(-1px)!important}.iws-compare-open--search{position:relative!important;top:auto!important;right:auto!important;transform:none!important;flex:0 0 42px!important;width:42px!important;height:42px!important;min-width:42px!important;border-radius:var(--wp-theme-radius,10px)!important;background:#fff!important;border:1px solid var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:var(--wp-theme-ink,#111)!important}.iws-compare-open--search.has-products,.iws-compare-open--search.is-selected,.iws-compare-open--search.is-active,.iws-compare-open--search.active,.iws-compare-open--search[aria-pressed="true"]{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-compare-open--search .iws-compare-count{top:-7px!important;right:-7px!important;font-weight:700!important}.iws-compare-icon svg{width:18px!important;height:18px!important}.iws-compare-icon svg path{stroke:currentColor!important;stroke-width:2.7!important;stroke-linecap:round!important;stroke-linejoin:round!important;fill:none!important}.iws-products-grid li.product .iws-compare-toggle,.woocommerce ul.products li.product .iws-compare-toggle{background:#fff!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:var(--wp-theme-ink,#111)!important}.iws-products-grid li.product .iws-compare-toggle.is-selected,.woocommerce ul.products li.product .iws-compare-toggle.is-selected{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-compare-modal__close,.iws-compare-remove{display:inline-flex!important;align-items:center!important;justify-content:center!important;font-family:Arial,sans-serif!important;line-height:1!important;padding:0!important;font-weight:700!important}.iws-compare-modal__close{font-size:22px!important}.iws-compare-remove{font-size:18px!important}.tpl__woo-search-cat .wc-block-product-categories-list,.tpl__woo-search-cat .wp-block-woocommerce-product-categories ul{gap:6px!important}.tpl__woo-search-cat .wc-block-product-categories-list a,.tpl__woo-search-cat .wp-block-woocommerce-product-categories a{min-height:30px!important;padding:5px 10px!important;font-size:12px!important;font-weight:600!important;gap:5px!important;color:var(--wp-theme-ink,#111)!important}.tpl__woo-search-cat .wc-block-product-categories-list a span,.tpl__woo-search-cat .wp-block-woocommerce-product-categories a span{font-weight:600!important}.tpl__woo-search-cat .wc-block-product-categories-list a:hover,.tpl__woo-search-cat .wp-block-woocommerce-product-categories a:hover,.tpl__woo-search-cat .is-iws-active-category>a{color:#fff!important}.tpl__woo-search-cat a:hover span,.tpl__woo-search-cat .is-iws-active-category>a span{color:#fff!important}.tpl__woo-search-cat a img:not([src]),.tpl__woo-search-cat a img[src=""],.tpl__woo-search-cat a svg:empty,.tpl__woo-search-cat a .icon:empty,.tpl__woo-search-cat a .cat-icon:empty,.tpl__woo-search-cat a .category-icon:empty{display:none!important;width:0!important;height:0!important;margin:0!important}@media(max-width:760px){.iws-search-row{gap:8px!important}.iws-compare-open--search{flex-basis:40px!important;width:40px!important;height:40px!important;min-width:40px!important}.iws-search-input-wrap .iws-search-input,.iws-search-input-wrap input[type=search]{padding-right:54px!important}.iws-search-clear{right:44px!important}}';


	$css .= '.iws-products-grid,.woocommerce .iws-products-grid{align-items:stretch!important}.iws-products-grid li.product,.woocommerce .iws-products-grid li.product{display:flex!important;flex-direction:column!important;align-items:stretch!important;height:100%!important;min-height:100%!important;padding-bottom:20px!important;overflow:hidden!important;position:relative!important;box-sizing:border-box!important}.iws-products-grid li.product>a.woocommerce-LoopProduct-link,.woocommerce .iws-products-grid li.product>a.woocommerce-LoopProduct-link{display:flex!important;flex:1 1 auto!important;flex-direction:column!important;min-height:0!important;color:inherit!important;text-decoration:none!important}.iws-products-grid li.product .price,.woocommerce .iws-products-grid li.product .price{display:block!important;order:40!important;position:static!important;inset:auto!important;width:100%!important;max-width:100%!important;margin:auto 0 12px!important;padding:0!important;float:none!important;clear:both!important;text-align:left!important;transform:none!important}.iws-products-grid li.product .button:not(.iws-compare-toggle),.iws-products-grid li.product .add_to_cart_button,.iws-products-grid li.product .product_type_simple,.iws-products-grid li.product .product_type_variable,.iws-products-grid li.product .tfa-wcqb-loop-wrap{display:block!important;order:50!important;position:static!important;inset:auto!important;transform:none!important;width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;float:none!important;clear:both!important;align-self:stretch!important;box-sizing:border-box!important}.iws-products-grid li.product .tfa-wcqb-loop-wrap{order:60!important;margin-top:8px!important}.iws-products-grid li.product .button:not(.iws-compare-toggle),.iws-products-grid li.product .add_to_cart_button,.iws-products-grid li.product .product_type_simple,.iws-products-grid li.product .product_type_variable,.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:100%!important;max-width:100%!important;min-height:40px!important;margin:15px auto 0 auto!important;padding:10px 12px!important;border:0!important;border-radius:0 15px 0 0!important;font-size:14px!important;font-weight:800!important;line-height:1.15!important;text-align:center!important;text-decoration:none!important;white-space:normal!important;position:static!important;inset:auto!important;transform:none!important;box-sizing:border-box!important}.iws-products-grid li.product .button:not(.iws-compare-toggle),.iws-products-grid li.product .add_to_cart_button,.iws-products-grid li.product .product_type_simple,.iws-products-grid li.product .product_type_variable{background:var(--wp-theme-primary,#2563eb)!important;color:#fff!important}.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}';
	$css .= '.iws-filter-results-wrap #loadMore,.iws-filter-results-wrap .loadMore,#load-more-js{display:none!important}';
	$css .= '.iws-filter-form{font-size:0!important;align-items:start!important}.iws-filter-form>*{font-size:14px!important}.iws-filter-form>br,.iws-filter-form>p:empty,.iws-filter-form>.wp-block-spacer:empty{display:none!important;width:0!important;height:0!important;min-height:0!important;margin:0!important;padding:0!important;overflow:hidden!important}.iws-filter-form .iws-filter-field,.iws-filter-form .iws-filter-more,.iws-filter-form .iws-filter-actions{min-width:0!important}.iws-compare-open--search,.iws-products-grid li.product .iws-compare-toggle,.woocommerce ul.products li.product .iws-compare-toggle{background:#fff!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:var(--wp-theme-ink,#111)!important}.iws-compare-open--search svg path,.iws-products-grid li.product .iws-compare-toggle svg path,.woocommerce ul.products li.product .iws-compare-toggle svg path{stroke:#111!important}.iws-compare-open--search.has-products,.iws-compare-open--search.is-selected,.iws-compare-open--search.is-active,.iws-compare-open--search.active,.iws-compare-open--search[aria-pressed="true"],.iws-products-grid li.product .iws-compare-toggle.is-selected,.woocommerce ul.products li.product .iws-compare-toggle.is-selected{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-compare-open--search.has-products svg path,.iws-compare-open--search.is-selected svg path,.iws-compare-open--search.is-active svg path,.iws-compare-open--search.active svg path,.iws-compare-open--search[aria-pressed="true"] svg path,.iws-products-grid li.product .iws-compare-toggle.is-selected svg path,.woocommerce ul.products li.product .iws-compare-toggle.is-selected svg path{stroke:#fff!important}.iws-search-clear[hidden],.iws-search-clear.is-hidden{display:none!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important}.iws-search-clear:not([hidden]):not(.is-hidden){display:inline-flex!important;visibility:visible!important;opacity:1!important}.iws-filter-more summary:after{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:22px!important;height:22px!important;min-width:22px!important;min-height:22px!important;padding:0!important;margin:0!important;border-radius:50%!important;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important;font-family:Arial,sans-serif!important;font-size:15px!important;font-weight:600!important;line-height:22px!important;text-align:center!important;letter-spacing:0!important}.iws-filter-more[open] summary:after{font-size:17px!important;font-weight:600!important;line-height:20px!important}.iws-filter-actions{grid-column:1/-1!important;order:100!important;justify-content:flex-start!important;align-self:start!important;margin-top:4px!important}.iws-filter-actions button{font-weight:700!important}';
	$css .= '.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search,.iws-compare-open.iws-compare-open--search{position:relative!important;inset:auto!important;top:auto!important;right:auto!important;bottom:auto!important;left:auto!important;transform:none!important;overflow:visible!important;background:#fff!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:var(--wp-theme-ink,#111)!important;isolation:isolate!important}.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search.is-selected,.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search.is-active,.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search.active,.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search[aria-pressed="true"],.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search.is-selected,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search.is-active,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search.active,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search[aria-pressed="true"],.iws-compare-open.iws-compare-open--search.has-products,.iws-compare-open.iws-compare-open--search.is-selected,.iws-compare-open.iws-compare-open--search.is-active,.iws-compare-open.iws-compare-open--search.active,.iws-compare-open.iws-compare-open--search[aria-pressed="true"]{background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;border-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important}.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search .iws-compare-count,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search .iws-compare-count,.iws-compare-open.iws-compare-open--search .iws-compare-count{position:absolute!important;top:-9px!important;right:-9px!important;left:auto!important;bottom:auto!important;z-index:5!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:20px!important;height:20px!important;padding:2px 6px!important;border-radius:999px!important;background:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;color:#fff!important;border:2px solid #fff!important;font-size:12px!important;font-weight:700!important;line-height:1!important;letter-spacing:0!important;box-shadow:0 2px 8px rgba(0,0,0,.18)!important;transform:none!important}.iws-filter-wrapper .iws-search-row button.iws-compare-open.iws-compare-open--search:not(.has-products) .iws-compare-count,.iws-filter-wrapper button.iws-compare-open.iws-compare-open--search:not(.has-products) .iws-compare-count{display:none!important}.iws-filter-more summary:after{content:""!important;display:inline-block!important;flex:0 0 22px!important;width:22px!important;height:22px!important;min-width:22px!important;min-height:22px!important;padding:0!important;margin:0!important;border-radius:50%!important;background-color:var(--wp-theme-primary,var(--tfa-brand-color,#2563eb))!important;background-repeat:no-repeat!important;background-position:center!important;background-size:12px 12px!important;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath d=\'M12 5v14M5 12h14\' fill=\'none\' stroke=\'%23fff\' stroke-width=\'2.4\' stroke-linecap=\'round\'/%3E%3C/svg%3E")!important;font-size:0!important;font-weight:400!important;line-height:0!important;text-indent:-9999px!important;overflow:hidden!important;transform:rotate(0deg)!important;transition:transform .18s ease,background-color .18s ease!important}.iws-filter-more[open] summary:after{content:""!important;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath d=\'M5 12h14\' fill=\'none\' stroke=\'%23fff\' stroke-width=\'2.4\' stroke-linecap=\'round\'/%3E%3C/svg%3E")!important;transform:rotate(180deg)!important;font-size:0!important;line-height:0!important}.iws-filter-more summary{align-items:center!important}.iws-filter-more summary>*{line-height:1.2!important}';

	$css .= '.iws-card-button-layout-v24{display:block!important}.wc-block-product-template .product,.products .product,body.archive.woocommerce .products .product,.woocommerce ul.products li.product,.iws-products-grid li.product{padding-bottom:20px!important;display:flex!important;flex-direction:column!important;align-items:stretch!important;position:relative!important;overflow:hidden!important;box-sizing:border-box!important}.wc-block-product-template .product>a.woocommerce-LoopProduct-link,.products .product>a.woocommerce-LoopProduct-link,.woocommerce ul.products li.product>a.woocommerce-LoopProduct-link,.iws-products-grid li.product>a.woocommerce-LoopProduct-link{display:flex!important;flex:1 1 auto!important;flex-direction:column!important;min-height:0!important;color:inherit!important;text-decoration:none!important}.wc-block-product-template .product .price,.wc-block-product-template .product .wc-block-components-product-price,.products .product .price,.woocommerce ul.products li.product .price,.iws-products-grid li.product .price{display:block!important;position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;margin:0 auto 15px auto!important;padding:0!important;float:none!important;clear:both!important;transform:none!important;text-align:left!important}.wc-block-product-template .product .wp-block-button,.wc-block-product-template .product .wc-block-components-product-button,.products .product .wp-block-button,.products .product .wc-block-components-product-button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable,.products .product .tfa-wcqb-loop-wrap,.woocommerce ul.products li.product .tfa-wcqb-loop-wrap{position:static!important;inset:auto!important;display:block!important;width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;float:none!important;clear:both!important;transform:none!important}.products .product .tfa-wcqb-loop-wrap,.woocommerce ul.products li.product .tfa-wcqb-loop-wrap,.wc-block-product-template .product .tfa-wcqb-loop-wrap{margin-top:8px!important}.wc-block-product-template .product .wp-element-button,.wc-block-product-template .product .wp-block-button__link,.wc-block-product-template .product .wc-block-components-product-button__button,.products .product .wp-element-button,.products .product .wp-block-button__link,.products .product .wc-block-components-product-button__button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable,.products .product .tfa-wcqb-loop-wrap .tfa-quote-button{display:flex!important;align-items:center!important;justify-content:center!important;position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;min-height:40px!important;margin:0!important;padding:0 15px 0 15px!important;border:0!important;border-radius:0 15px 0 0!important;font-weight:500!important;line-height:1.15!important;text-align:center!important;text-decoration:none!important;white-space:normal!important;transform:none!important;box-sizing:border-box!important;overflow:hidden!important}.wc-block-product-template .product .wp-element-button,.wc-block-product-template .product .wp-block-button__link,.wc-block-product-template .product .wc-block-components-product-button__button,.products .product .wp-element-button,.products .product .wp-block-button__link,.products .product .wc-block-components-product-button__button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable{background-color:var(--tfa-green-color)!important;background-image:none!important;background-repeat:no-repeat!important;background-position:calc(100% + 32px) center!important;background-size:18px auto!important;color:#fff!important}.wc-block-product-template .product .wp-element-button:hover,.wc-block-product-template .product .wp-block-button__link:hover,.wc-block-product-template .product .wc-block-components-product-button__button:hover,.products .product .wp-element-button:hover,.products .product .wp-block-button__link:hover,.products .product .wc-block-components-product-button__button:hover,.products .product>a.button:not(.iws-compare-toggle):hover,.products .product>.button:not(.iws-compare-toggle):hover,.products .product>.add_to_cart_button:hover,.products .product>.product_type_simple:hover,.products .product>.product_type_variable:hover{background-position:calc(100% - 18px) center!important}.products .product .tfa-wcqb-loop-wrap .tfa-quote-button,.tfa-quote-button.single{background-image:none!important;background-repeat:no-repeat!important;background-position:calc(100% + 32px) center!important;background-size:16px auto!important;font-weight:500!important}.products .product .tfa-wcqb-loop-wrap .tfa-quote-button:hover,.tfa-quote-button.single:hover{background-position:calc(100% - 18px) center!important}.woocommerce span.onsale,.wc-block-components-product-sale-badge{margin-right:12px!important}';

	wp_add_inline_style( 'iws-product-filter-final', $css );
}
