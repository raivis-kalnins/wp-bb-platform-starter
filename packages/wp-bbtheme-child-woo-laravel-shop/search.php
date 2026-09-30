<?php
/** WP BB Home & Garden product search page. */
defined('ABSPATH') || exit;
get_header();
$term = get_search_query();
$is_en = function_exists('wpbbshop_v313_is_en') && wpbbshop_v313_is_en();
$ids = array();
if ($term !== '' && class_exists('WooCommerce')) {
    if (ctype_digit($term) && get_post_type(absint($term)) === 'product') { $ids[] = absint($term); }
    global $wpdb;
    $like = '%' . $wpdb->esc_like($term) . '%';
    $sku_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s LIMIT 80", $like));
    $ids = array_merge($ids, array_map('absint', (array)$sku_ids));
    $text_q = new WP_Query(array('post_type'=>'product','s'=>$term,'posts_per_page'=>80,'post_status'=>'publish','fields'=>'ids','suppress_filters'=>true,'lang'=>''));
    if ($text_q->posts) { $ids = array_merge($ids, array_map('absint',$text_q->posts)); }
    wp_reset_postdata();
}
$ids = array_values(array_unique(array_filter($ids)));
?>
<main class="wpbbshop-search-page llg-v313-catalog-page"><div class="wpbbshop-container">
  <section class="wpbbshop-search-hero llg-v313-page-hero"><span>WP BB HOME & GARDEN</span><h1><?php echo esc_html(($is_en ? 'Search: ' : 'Meklēšana: ') . $term); ?></h1><p><?php echo esc_html($is_en ? 'Results by product name, SKU and product ID.' : 'Rezultāti pēc produkta nosaukuma, SKU un produkta ID.'); ?></p></section>
  <?php if ($ids) : ?><div class="wpbbshop-bootstrap-products"><?php foreach ($ids as $id) { $product = wc_get_product($id); if ($product && $product->get_status()==='publish') { echo '<div class="wpbbshop-bs-product-col">'.(function_exists('wpbbshop_green_product_card') ? wpbbshop_green_product_card($product) : wpbbshop_product_card($product)).'</div>'; } } ?></div>
  <?php else : ?><section class="llg-v313-empty"><h2><?php echo esc_html($is_en ? 'No products found' : 'Preces nav atrastas'); ?></h2><p><?php echo esc_html($is_en ? 'Try another product name, SKU or browse the categories.' : 'Pamēģini citu preces nosaukumu, SKU vai apskati kategorijas.'); ?></p><a class="llg-v313-btn" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php echo esc_html($is_en ? 'Browse products' : 'Skatīt preces'); ?></a></section><?php endif; ?>
</div></main>
<?php get_footer();
