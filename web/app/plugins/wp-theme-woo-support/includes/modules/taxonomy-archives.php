<?php
/**
 * Product taxonomy archive output.
 *
 * Keeps category, tag, brand and product-attribute archives on the same visual
 * system as the shop archive, with a manual breadcrumb and AJAX/fallback load more.
 */
defined( 'ABSPATH' ) || exit;

function iws_is_product_taxonomy_archive() {
	if ( ! is_tax() ) {
		return false;
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term ) {
		return false;
	}

	$taxonomy = (string) $term->taxonomy;

	return in_array( $taxonomy, array( 'product_cat', 'product_tag', 'product_brand', 'pa_brand', 'product_attribute' ), true )
		|| 0 === strpos( $taxonomy, 'pa_' );
}

function iws_products_archive_url() {
	$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';

	if ( ! $url || is_wp_error( $url ) ) {
		$url = home_url( '/products/' );
	}

	return $url;
}

function iws_product_taxonomy_breadcrumb_items( WP_Term $term ) {
	$items = array(
		array(
			'label' => __( 'Home', 'wp-theme-woo-support' ),
			'url'   => home_url( '/' ),
		),
		array(
			'label' => __( 'Products', 'wp-theme-woo-support' ),
			'url'   => iws_products_archive_url(),
		),
	);

	if ( 'product_cat' === $term->taxonomy ) {
		$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );

			if ( ! $ancestor instanceof WP_Term || is_wp_error( $ancestor ) ) {
				continue;
			}

			$items[] = array(
				'label' => $ancestor->name,
				'url'   => get_term_link( $ancestor ),
			);
		}
	}

	$items[] = array(
		'label' => $term->name,
		'url'   => '',
	);

	return $items;
}

function iws_product_taxonomy_breadcrumb() {
	$term = get_queried_object();

	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$items = iws_product_taxonomy_breadcrumb_items( $term );
	$out   = '<nav class="woocommerce-breadcrumb iws-product-taxonomy-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'woocommerce' ) . '">';

	foreach ( $items as $index => $item ) {
		if ( $index > 0 ) {
			$out .= '<span class="iws-breadcrumb-separator" aria-hidden="true"> &gt; </span>';
		}

		if ( ! empty( $item['url'] ) && $index < count( $items ) - 1 ) {
			$out .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		} else {
			$out .= '<span class="iws-breadcrumb-current">' . esc_html( $item['label'] ) . '</span>';
		}
	}

	$out .= '</nav>';

	return $out;
}

function iws_product_taxonomy_query_args( WP_Term $term, $paged = 1 ) {
	$tax_query = array(
		array(
			'taxonomy'         => $term->taxonomy,
			'field'            => 'term_id',
			'terms'            => array( (int) $term->term_id ),
			'include_children' => 'product_cat' === $term->taxonomy,
		),
	);

	return array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'paged'               => max( 1, (int) $paged ),
		'tax_query'           => $tax_query,
		'orderby'             => 'menu_order title',
		'order'               => 'ASC',
		'ignore_sticky_posts' => true,
	);
}

function iws_taxonomy_loop_product_sku() {
	global $product;

	if ( function_exists( 'iws_show_sku_on_product_cards' ) && ! iws_show_sku_on_product_cards() ) {
		return '';
	}

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$sku = $product->get_sku();

	if ( '' === (string) $sku ) {
		return '';
	}

	return '<span class="iws-product-card-sku sku_wrapper" title="' . esc_attr( sprintf( __( 'SKU: %s', 'wp-theme-woo-support' ), $sku ) ) . '"><span class="iws-product-card-sku-label">' . esc_html__( 'SKU:', 'wp-theme-woo-support' ) . '</span> <span class="sku">' . esc_html( $sku ) . '</span></span>';
}

function iws_taxonomy_loop_product_title() {
	return '<div class="h3 woocommerce-loop-product__title iws-product-card-title">' . esc_html( get_the_title() ) . '</div>';
}

function iws_taxonomy_loop_product_description( WC_Product $product ) {
	$excerpt = $product->get_short_description();

	if ( ! $excerpt ) {
		return '';
	}

	return '<p class="prod-desc">' . esc_html( wp_trim_words( wp_strip_all_tags( $excerpt ), 20 ) ) . '</p>';
}

function iws_taxonomy_loop_product_image( WC_Product $product ) {
	$image = $product->get_image( 'woocommerce_thumbnail' );

	if ( ! $image ) {
		$image = wc_placeholder_img( 'woocommerce_thumbnail' );
	}

	return '<span class="iws-product-card-image-wrap">' . iws_taxonomy_loop_product_sku() . $image . '</span>';
}

function iws_taxonomy_loop_product_buttons() {
	$removed_link_close = has_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close' );

	if ( false !== $removed_link_close ) {
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', $removed_link_close );
	}

	ob_start();
	do_action( 'woocommerce_after_shop_loop_item' );
	$buttons = ob_get_clean();

	// Taxonomy archive cards should not show the compare icon. The compare
	// button can be injected by global WooCommerce loop hooks, so strip it
	// from this controlled card output while leaving quote/add-to-cart intact.
	$buttons = preg_replace( '/<button\b[^>]*\biws-compare-toggle\b[^>]*>.*?<\/button>/is', '', $buttons );
	$buttons = preg_replace( '/<script\b[^>]*\biws-compare-product-data\b[^>]*>.*?<\/script>/is', '', $buttons );
	$buttons = preg_replace( '/<a\b[^>]*class=["\'][^"\']*woocommerce-LoopProduct-link[^"\']*["\'][^>]*>\s*<\/a>/is', '', $buttons );
	$buttons = preg_replace( '/<p>\s*<\/p>/is', '', $buttons );

	if ( false !== $removed_link_close ) {
		add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', $removed_link_close );
	}

	return $buttons;
}

function iws_taxonomy_loop_product_card() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$product_link = apply_filters( 'woocommerce_loop_product_link', get_the_permalink(), $product );
	?>
	<li <?php wc_product_class( 'iws-taxonomy-product-card', $product ); ?>>
		<a href="<?php echo esc_url( $product_link ); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link iws-product-card-link">
			<?php echo iws_taxonomy_loop_product_image( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo iws_taxonomy_loop_product_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo iws_taxonomy_loop_product_description( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<?php echo iws_taxonomy_loop_product_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</li>
	<?php
}

function iws_product_taxonomy_products_html( WP_Query $query ) {
	ob_start();

	echo '<ul class="products iws-products-grid iws-taxonomy-products-grid">';

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			iws_taxonomy_loop_product_card();
		}
	} else {
		echo '<li class="product iws-no-products">' . esc_html__( 'No products found.', 'wp-theme-woo-support' ) . '</li>';
	}

	echo '</ul>';

	wp_reset_postdata();

	return ob_get_clean();
}

function iws_product_taxonomy_load_more_html( WP_Query $query, WP_Term $term, $paged ) {
	if ( $query->max_num_pages <= $paged ) {
		return '';
	}

	$next_page = $paged + 1;
	$next_url  = get_pagenum_link( $next_page );

	return '<div class="iws-load-more-wrap iws-taxonomy-load-more-wrap"><a class="btn btn-primary iws-taxonomy-load-more" href="' . esc_url( $next_url ) . '" data-taxonomy="' . esc_attr( $term->taxonomy ) . '" data-term="' . esc_attr( $term->slug ) . '" data-next-page="' . esc_attr( $next_page ) . '" data-max-pages="' . esc_attr( $query->max_num_pages ) . '" data-loading="' . esc_attr__( 'Loading...', 'wp-theme-woo-support' ) . '" style="color:#fff!important;"><span class="iws-load-more-text" style="color:#fff!important;">' . esc_html__( 'Load more', 'wp-theme-woo-support' ) . '</span><span class="iws-btn-spinner" aria-hidden="true"></span></a></div>';
}

function iws_product_taxonomy_archive_shortcode() {
	if ( ! iws_is_product_taxonomy_archive() ) {
		return '';
	}

	$GLOBALS['iws_product_taxonomy_archive_rendered'] = true;

	$term  = get_queried_object();
	$paged = max( 1, get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : absint( get_query_var( 'page' ) ) );
	$query = new WP_Query( iws_product_taxonomy_query_args( $term, $paged ) );

	ob_start();
	?>
	<div class="woocommerce iws-product-taxonomy-archive" data-taxonomy="<?php echo esc_attr( $term->taxonomy ); ?>" data-term="<?php echo esc_attr( $term->slug ); ?>">
		<?php echo iws_product_taxonomy_breadcrumb(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide iws-product-taxonomy-separator" />
		<h1 class="h2 iws-product-taxonomy-title"><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
		<?php if ( term_description( $term->term_id, $term->taxonomy ) ) : ?>
			<div class="iws-product-taxonomy-description"><?php echo wp_kses_post( term_description( $term->term_id, $term->taxonomy ) ); ?></div>
		<?php endif; ?>
		<p class="woocommerce-result-count iws-result-count">
			<?php echo esc_html( iws_filter_count_text( $query, 12, $paged ) ); ?>
		</p>
		<div class="iws-taxonomy-products-wrap">
			<?php echo iws_product_taxonomy_products_html( $query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php echo iws_product_taxonomy_load_more_html( $query, $term, $paged ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php

	return ob_get_clean();
}
add_shortcode( 'iws_product_taxonomy_archive', 'iws_product_taxonomy_archive_shortcode' );

/**
 * Force the custom taxonomy archive output even when WordPress is rendering an
 * older saved Site Editor template from the database.
 *
 * Saved Site Editor templates can keep rendering a Product Collection block
 * after a filesystem taxonomy template changes. Replace only that block on
 * product taxonomy archives with the controlled archive markup.
 */
function iws_product_taxonomy_block_is_product_collection( $block ) {
	if ( ! is_array( $block ) ) {
		return false;
	}

	$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
	$attrs      = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
	$class_name = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';
	$namespace  = isset( $attrs['__woocommerceNamespace'] ) ? (string) $attrs['__woocommerceNamespace'] : '';

	if ( 'woocommerce/product-collection' === $block_name || 'woocommerce/product-template' === $block_name ) {
		return true;
	}

	if ( 'core/query' === $block_name ) {
		if ( false !== strpos( $class_name, 'wp-block-woocommerce-product-collection' ) ) {
			return true;
		}

		if ( isset( $attrs['query'] ) && is_array( $attrs['query'] ) && 'product' === ( $attrs['query']['postType'] ?? '' ) ) {
			return true;
		}
	}

	return 0 === strpos( $namespace, 'woocommerce/product-query' );
}

function iws_product_taxonomy_replace_saved_collection_block( $block_content, $block ) {
	if ( is_admin() || wp_doing_ajax() || ! iws_is_product_taxonomy_archive() ) {
		return $block_content;
	}

	// Do not replace WooCommerce Product Collection blocks while rendering the
	// header Mega Menu CPT. Those menus can contain their own hand-picked
	// featured product block, and it must remain independent of the taxonomy
	// archive product grid.
	if ( ! empty( $GLOBALS['iws_rendering_megamenu_content'] ) ) {
		return $block_content;
	}

	if ( ! iws_product_taxonomy_block_is_product_collection( $block ) ) {
		return $block_content;
	}

	if ( ! empty( $GLOBALS['iws_product_taxonomy_archive_rendered'] ) ) {
		return '';
	}

	return iws_product_taxonomy_archive_shortcode();
}
add_filter( 'render_block', 'iws_product_taxonomy_replace_saved_collection_block', 9, 2 );


function iws_product_taxonomy_archive_ajax() {
	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
	$term_slug = isset( $_GET['term'] ) ? sanitize_title( wp_unslash( $_GET['term'] ) ) : '';
	$page = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;

	if ( '' === $taxonomy || '' === $term_slug || ! taxonomy_exists( $taxonomy ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid taxonomy.', 'wp-theme-woo-support' ) ), 400 );
	}

	$term = get_term_by( 'slug', $term_slug, $taxonomy );

	if ( ! $term instanceof WP_Term ) {
		wp_send_json_error( array( 'message' => __( 'Invalid term.', 'wp-theme-woo-support' ) ), 404 );
	}

	$query = new WP_Query( iws_product_taxonomy_query_args( $term, $page ) );

	wp_send_json_success(
		array(
			'products_html'  => iws_product_taxonomy_products_html( $query ),
			'load_more_html' => iws_product_taxonomy_load_more_html( $query, $term, $page ),
			'count_text'     => iws_filter_count_text( $query, 12, $page ),
		)
	);
}
add_action( 'wp_ajax_iws_product_taxonomy_archive', 'iws_product_taxonomy_archive_ajax' );
add_action( 'wp_ajax_nopriv_iws_product_taxonomy_archive', 'iws_product_taxonomy_archive_ajax' );

add_action( 'wp_enqueue_scripts', function() {
	if ( ! iws_is_product_taxonomy_archive() ) {
		return;
	}

	wp_register_style( 'iws-product-taxonomy-archive', false, array(), WP_THEME_WOO_SUPPORT_VERSION );
	wp_enqueue_style( 'iws-product-taxonomy-archive' );
	wp_add_inline_style(
		'iws-product-taxonomy-archive',
		'.tax-product_cat .wp-block-woocommerce-product-collection,.tax-product_tag .wp-block-woocommerce-product-collection,.tax-product_brand .wp-block-woocommerce-product-collection,.tax-pa_brand .wp-block-woocommerce-product-collection,body[class*="tax-pa_"] .wp-block-woocommerce-product-collection,.iws-product-taxonomy-archive .wp-block-woocommerce-product-collection{margin-top:0!important}.tax-product_cat .iws-compare-toggle,.tax-product_tag .iws-compare-toggle,.tax-product_brand .iws-compare-toggle,.tax-pa_brand .iws-compare-toggle,body[class*="tax-pa_"] .iws-compare-toggle,.iws-product-taxonomy-archive .iws-compare-toggle,.iws-product-taxonomy-archive .iws-compare-product-data{display:none!important}.iws-product-taxonomy-archive{position:relative}.iws-product-taxonomy-breadcrumb{display:flex;flex-wrap:wrap;gap:0;align-items:center;margin:0 0 14px;font-size:14px;line-height:1.4;color:#111}.iws-product-taxonomy-breadcrumb a{color:#111;text-decoration:none}.iws-product-taxonomy-breadcrumb a:hover{color:var(--wp-brand-color);text-decoration:none}.iws-breadcrumb-separator{margin:0 8px;color:#666}.iws-product-taxonomy-separator{margin:0 0 30px!important;border:0;border-top:1px solid rgba(0,0,0,.14);background:transparent!important;color:transparent!important}.iws-product-taxonomy-title{margin:0 0 18px!important;color:var(--wp-brand-color);font-weight:500}.iws-product-taxonomy-description{max-width:880px;margin:0 0 5px}.iws-product-taxonomy-description p{margin:0 0 12px}.iws-taxonomy-products-grid li.product.iws-taxonomy-product-card{display:flex!important;flex-direction:column!important}.iws-taxonomy-products-grid .iws-product-card-link{display:flex!important;flex-direction:column!important;flex:1 1 auto!important;text-decoration:none!important;color:inherit!important}.iws-taxonomy-products-grid .iws-product-card-image-wrap{order:1!important;position:relative!important;display:block!important;width:100%!important;margin:0 0 14px!important}.iws-taxonomy-products-grid .iws-product-card-image-wrap img{display:block!important;width:100%!important;height:auto!important;margin:0!important;background:#fff!important}.iws-taxonomy-products-grid .iws-product-card-link img{background:#fff!important}.iws-taxonomy-products-grid .iws-product-card-title,.iws-taxonomy-products-grid .woocommerce-loop-product__title.h3{order:2!important;display:block!important;font-size:20px!important;line-height:1.18!important;font-weight:800!important;margin:0 0 9px!important;color:#111!important}.iws-taxonomy-products-grid .prod-desc{order:3!important;display:block!important;margin:0 0 24px!important}.iws-taxonomy-products-grid .iws-product-card-sku{position:absolute!important;top:8px!important;right:8px!important;z-index:3!important;display:inline-flex!important;align-items:center!important;gap:3px!important;width:max-content!important;max-width:calc(100% - 16px)!important;min-width:0!important;margin:0!important;padding:3px 7px!important;border-radius:2px!important;background:#fff!important;color:#111!important;font-size:12px!important;line-height:1.2!important;font-weight:700!important;text-transform:none!important;letter-spacing:0!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;box-shadow:0 1px 3px rgba(0,0,0,.12)!important}.iws-taxonomy-products-grid .iws-product-card-sku .iws-product-card-sku-label,.iws-taxonomy-products-grid .iws-product-card-sku .sku{color:#111!important;font-size:12px!important;line-height:1.2!important;font-weight:700!important}.iws-taxonomy-products-grid .iws-product-card-sku .iws-product-card-sku-label{flex:0 0 auto!important}.iws-taxonomy-products-grid .iws-product-card-sku .sku{display:inline-block!important;min-width:0!important;max-width:100%!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;vertical-align:bottom!important}.iws-taxonomy-products-grid .product>a img,.iws-taxonomy-products-grid li.product img{background:#fff!important}.iws-taxonomy-products-grid li.product>p:empty,.iws-taxonomy-products-grid li.product p[style*="display: none"]{display:none!important}.iws-taxonomy-load-more-wrap{display:flex;justify-content:center;width:100%;margin:34px 0 44px!important}.iws-taxonomy-load-more{display:inline-flex!important;align-items:center;justify-content:center;min-width:148px;min-height:42px;padding:11px 24px!important;text-decoration:none!important;color:#fff!important;background-color:var(--wp-brand-color)!important;border-color:var(--wp-brand-color)!important}.iws-taxonomy-load-more,.iws-taxonomy-load-more:visited,.iws-taxonomy-load-more:hover,.iws-taxonomy-load-more:focus,.iws-taxonomy-load-more:active,.iws-taxonomy-load-more *,.iws-taxonomy-load-more span,.iws-taxonomy-load-more .iws-load-more-text{color:#fff!important}.iws-taxonomy-load-more:hover,.iws-taxonomy-load-more:focus{color:#fff!important;background-color:var(--wp-brand-color)!important;border-color:var(--wp-brand-color)!important}.iws-taxonomy-load-more.is-loading{cursor:wait;opacity:.72;pointer-events:none}.iws-taxonomy-products-grid li.product .button,.iws-taxonomy-products-grid li.product .add_to_cart_button,.iws-taxonomy-products-grid li.product .product_type_simple,.iws-taxonomy-products-grid li.product .product_type_variable,.iws-taxonomy-products-grid li.product .wp-wcqb-loop-wrap .wp-quote-button{display:flex!important;align-items:center;justify-content:center;min-height:40px;padding:0 15px!important;border-radius:0 15px 0 0!important;line-height:1.2;text-align:center}'
	);

	wp_register_script( 'iws-product-taxonomy-archive', '', array(), WP_THEME_WOO_SUPPORT_VERSION, true );
	wp_enqueue_script( 'iws-product-taxonomy-archive' );
	wp_add_inline_script(
		'iws-product-taxonomy-archive',
		'(function(){document.addEventListener("click",function(event){var btn=event.target.closest&&event.target.closest(".iws-taxonomy-load-more");if(!btn){return;}event.preventDefault();var archive=btn.closest(".iws-product-taxonomy-archive");var wrap=archive&&archive.querySelector(".iws-taxonomy-products-wrap");var list=wrap&&wrap.querySelector("ul.products");if(!archive||!wrap||!list){window.location.href=btn.href;return;}var page=parseInt(btn.getAttribute("data-next-page")||"1",10);var max=parseInt(btn.getAttribute("data-max-pages")||"1",10);var taxonomy=btn.getAttribute("data-taxonomy")||archive.getAttribute("data-taxonomy")||"";var term=btn.getAttribute("data-term")||archive.getAttribute("data-term")||"";var text=btn.querySelector(".iws-load-more-text");var original=text?text.textContent:btn.textContent;btn.classList.add("is-loading");btn.setAttribute("aria-busy","true");if(text){text.textContent=btn.getAttribute("data-loading")||"Loading...";}var url="' . esc_js( admin_url( 'admin-ajax.php' ) ) . '?action=iws_product_taxonomy_archive&taxonomy="+encodeURIComponent(taxonomy)+"&term="+encodeURIComponent(term)+"&page="+encodeURIComponent(page);fetch(url,{credentials:"same-origin",headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(response){if(!response.ok){throw new Error("Request failed");}return response.json();}).then(function(json){if(!json||!json.success||!json.data){throw new Error("Invalid response");}var doc=new DOMParser().parseFromString(json.data.products_html||"","text/html");var nextList=doc.querySelector("ul.products");if(nextList){Array.prototype.slice.call(nextList.children).forEach(function(item){list.appendChild(document.importNode(item,true));});}var count=archive.querySelector(".iws-result-count");if(count&&json.data.count_text){count.textContent=json.data.count_text;}var holder=archive.querySelector(".iws-taxonomy-load-more-wrap");if(json.data.load_more_html&&page<max){var moreDoc=new DOMParser().parseFromString(json.data.load_more_html,"text/html");var newHolder=moreDoc.querySelector(".iws-taxonomy-load-more-wrap");if(holder&&newHolder){holder.replaceWith(document.importNode(newHolder,true));}}else if(holder){holder.remove();}document.dispatchEvent(new CustomEvent("iws:taxonomy-load-more:loaded",{detail:{archive:archive,page:page}}));}).catch(function(){window.location.href=btn.href;}).finally(function(){btn.classList.remove("is-loading");btn.removeAttribute("aria-busy");if(text){text.textContent=original;}});},true);})();'
	);

	wp_add_inline_script(
		'iws-product-taxonomy-archive',
		'(function(){function normalize(scope){scope=scope||document;var cards=scope.querySelectorAll?scope.querySelectorAll(".iws-product-taxonomy-archive li.product,.iws-taxonomy-products-grid li.product"):[];Array.prototype.forEach.call(cards,function(card){Array.prototype.forEach.call(card.querySelectorAll(".iws-compare-toggle,.iws-compare-product-data"),function(node){var parent=node.parentElement;node.remove();if(parent&&parent.tagName&&parent.tagName.toLowerCase()==="a"&&!parent.textContent.trim()&&!parent.querySelector("img,button,svg")){parent.remove();}});var keepWrap=null;Array.prototype.forEach.call(card.querySelectorAll(".wp-wcqb-loop-wrap"),function(wrap){if(!keepWrap){keepWrap=wrap;wrap.setAttribute("data-wp-wcqb-injected","1");return;}wrap.remove();});var keepButton=keepWrap?keepWrap.querySelector(".wp-quote-button.loop,.wp-quote-button"):null;Array.prototype.forEach.call(card.querySelectorAll(".wp-quote-button.loop,.wp-quote-button"),function(button){if(keepWrap&&keepWrap.contains(button)){if(!keepButton){keepButton=button;}else if(button!==keepButton){button.remove();}return;}if(keepButton){var parent=button.closest(".wp-wcqb-loop-wrap");if(parent){parent.remove();}else{button.remove();}return;}keepButton=button;});});}function schedule(scope){window.clearTimeout(window.iwsTaxonomyQuoteNormalizeTimer);window.iwsTaxonomyQuoteNormalizeTimer=window.setTimeout(function(){normalize(scope||document);},40);}document.addEventListener("DOMContentLoaded",function(){normalize(document);});document.addEventListener("iws:taxonomy-load-more:loaded",function(event){normalize(event.detail&&event.detail.archive?event.detail.archive:document);window.setTimeout(function(){normalize(event.detail&&event.detail.archive?event.detail.archive:document);},250);});document.addEventListener("click",function(event){if(event.target.closest&&event.target.closest(".wp-quote-button")){schedule(document);}},true);if("MutationObserver" in window){document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".iws-product-taxonomy-archive").forEach(function(archive){new MutationObserver(function(){schedule(archive);}).observe(archive,{childList:true,subtree:true});});});}normalize(document);})();'
	);
}, 60 );


add_action( 'wp_footer', function() {
	if ( ! iws_is_product_taxonomy_archive() ) {
		return;
	}
	?>
	<style id="iws-taxonomy-load-more-colour-fallback">
		body.tax-product_cat .iws-taxonomy-load-more,
		body.tax-product_tag .iws-taxonomy-load-more,
		body.tax-product_brand .iws-taxonomy-load-more,
		body.tax-pa_brand .iws-taxonomy-load-more,
		body[class*="tax-pa_"] .iws-taxonomy-load-more,
		.iws-product-taxonomy-archive .iws-taxonomy-load-more,
		body.tax-product_cat .iws-taxonomy-load-more *,
		body.tax-product_tag .iws-taxonomy-load-more *,
		body.tax-product_brand .iws-taxonomy-load-more *,
		body.tax-pa_brand .iws-taxonomy-load-more *,
		body[class*="tax-pa_"] .iws-taxonomy-load-more *,
		.iws-product-taxonomy-archive .iws-taxonomy-load-more * {
			color: #fff !important;
		}
	</style>
	<?php
}, 999 );
