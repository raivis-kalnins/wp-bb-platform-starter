<?php
/** WP BB Home & Garden new-arrivals catalogue for /?orderby=date. */
defined('ABSPATH') || exit;
get_header();
$is_en = function_exists('wpbbshop_v313_is_en') && wpbbshop_v313_is_en();
$paged = max(1, get_query_var('paged') ? absint(get_query_var('paged')) : (isset($_GET['product-page']) ? absint($_GET['product-page']) : 1));
$q = new WP_Query(array(
    'post_type'=>'product','post_status'=>'publish','posts_per_page'=>12,'paged'=>$paged,
    'orderby'=>'date','order'=>'DESC','suppress_filters'=>true,'lang'=>'',
    'meta_query'=>class_exists('WC') ? WC()->query->get_meta_query() : array(),
    'tax_query'=>class_exists('WC') ? WC()->query->get_tax_query() : array(),
));
?>
<main class="llg-v313-catalog-page">
  <div class="wpbbshop-container">
    <section class="llg-v313-page-hero"><span>WP BB HOME & GARDEN</span><h1><?php echo esc_html($is_en ? 'New arrivals' : 'Jaunumi'); ?></h1><p><?php echo esc_html($is_en ? 'The latest products added to the WP BB Home & Garden catalogue.' : 'Jaunākās preces, kas pievienotas WP BB Home & Garden katalogam.'); ?></p></section>
    <?php if ($q->have_posts()) : ?>
      <div class="wpbbshop-bootstrap-products">
        <?php while ($q->have_posts()) : $q->the_post(); global $product; if ($product) : ?><div class="wpbbshop-bs-product-col"><?php echo function_exists('wpbbshop_green_product_card') ? wpbbshop_green_product_card($product) : wpbbshop_product_card($product); ?></div><?php endif; endwhile; ?>
      </div>
      <?php if ($q->max_num_pages > 1) : ?><nav class="wpbbshop-archive-pagination"><?php echo paginate_links(array('total'=>$q->max_num_pages,'current'=>$paged,'format'=>'?orderby=date&product-page=%#%')); ?></nav><?php endif; ?>
    <?php else : ?>
      <section class="llg-v313-empty"><h2><?php echo esc_html($is_en ? 'No products found' : 'Preces nav atrastas'); ?></h2></section>
    <?php endif; wp_reset_postdata(); ?>
  </div>
</main>
<?php get_footer();
