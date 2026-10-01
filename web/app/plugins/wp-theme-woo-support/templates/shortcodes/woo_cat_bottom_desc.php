<?php
/**
 * WooCommerce Product Category bottom description [woo_cat_bottom_desc]
 */
function woo_cat_bottom_desc_shortcode() {
	$term_id = get_queried_object()->term_id;
	$category_bottom_desc = get_fields('term_'.$term_id)['category_bottom_desc'] ?? '';
	$woo_cat_bottom_desc = '<div class="category-bottom-desc">'.$category_bottom_desc.'</div>';
	return $woo_cat_bottom_desc;
}
add_shortcode('woo_cat_bottom_desc','woo_cat_bottom_desc_shortcode');
