<?php
/** WP BB Home & Garden 3.0.13 single product template. */
defined('ABSPATH') || exit;
get_header();
$is_en = function_exists('wpbbshop_v313_is_en') && wpbbshop_v313_is_en();
while (have_posts()) : the_post();
    global $product;
    if (!$product || !is_a($product,'WC_Product')) { $product = wc_get_product(get_the_ID()); }
    if (!$product) { continue; }
    $pid = $product->get_id();
    $cats = wc_get_product_category_list($pid, ', ');
?>
<main class="llg-single-page"><div class="wpbbshop-container llg-single-container">
  <?php woocommerce_output_all_notices(); ?>
  <div class="llg-single-breadcrumbs"><?php if (function_exists('wpbbshop_seo_breadcrumbs_235')) { echo wpbbshop_seo_breadcrumbs_235(); } else { woocommerce_breadcrumb(); } ?></div>
  <article id="product-<?php the_ID(); ?>" <?php wc_product_class('llg-single-product-card',$product); ?>>
    <section class="llg-single-gallery-panel">
      <?php if ($product->is_on_sale()) : ?><span class="llg-single-sale"><?php echo esc_html($is_en?'Sale':'Akcija'); ?></span><?php endif; ?>
      <?php echo function_exists('wpbbshop_render_v38_product_gallery') ? wpbbshop_render_v38_product_gallery($product) : $product->get_image('woocommerce_single'); ?>
    </section>
    <section class="llg-single-summary-panel">
      <div class="llg-single-status-row"><span class="llg-single-stock <?php echo $product->is_in_stock()?'is-in':'is-out'; ?>"><?php echo esc_html($product->is_in_stock()?($is_en?'In stock':'Ir noliktavā'):($is_en?'Out of stock':'Nav noliktavā')); ?></span><?php if ($product->get_sku()) : ?><span class="llg-single-sku">SKU: <?php echo esc_html($product->get_sku()); ?></span><?php endif; ?></div>
      <h1 class="product_title entry-title"><?php echo esc_html($product->get_name()); ?></h1>
      <?php if (wc_review_ratings_enabled() && $product->get_average_rating()>0) : ?><div class="llg-single-rating"><?php echo wc_get_rating_html($product->get_average_rating(),$product->get_rating_count()); ?></div><?php endif; ?>
      <div class="llg-single-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
      <?php if ($product->get_short_description()) : ?><div class="llg-single-short"><?php echo wp_kses_post(wpautop($product->get_short_description())); ?></div><?php endif; ?>
      <div class="llg-single-buybox"><?php woocommerce_template_single_add_to_cart(); ?></div>
      <div class="llg-single-meta-row"><?php if ($cats) : ?><div><strong><?php echo esc_html($is_en?'Category':'Kategorija'); ?>:</strong> <?php echo wp_kses_post($cats); ?></div><?php endif; ?><?php if (function_exists('wpbbshop_product_quick_actions')) { echo wpbbshop_product_quick_actions($pid); } ?></div>
      <div class="llg-single-trust-grid"><div><span><?php echo function_exists('wpbbshop_green_icon')?wpbbshop_green_icon('truck'):''; ?></span><strong><?php echo esc_html($is_en?'Fast delivery':'Ātra piegāde'); ?></strong><small><?php echo esc_html($is_en?'Parcel lockers and courier across Latvia':'Pakomāti un kurjers visā Latvijā'); ?></small></div><div><span><?php echo function_exists('wpbbshop_green_icon')?wpbbshop_green_icon('location'):''; ?></span><strong><?php echo esc_html($is_en?'Pickup in Smiltene':'Saņemšana Smiltenē'); ?></strong><small><?php echo esc_html($is_en?'Free by arrangement':'Bezmaksas pēc vienošanās'); ?></small></div><div><span><?php echo function_exists('wpbbshop_green_icon')?wpbbshop_green_icon('shield'):''; ?></span><strong><?php echo esc_html($is_en?'Secure purchase':'Drošs pirkums'); ?></strong><small><?php echo esc_html($is_en?'Warranty and clear terms':'Garantija un skaidri noteikumi'); ?></small></div></div>
    </section>
  </article>
  <section class="llg-single-tabs"><?php woocommerce_output_product_data_tabs(); ?></section>
  <?php if (function_exists('wpbbshop_render_single_product_section')) { wpbbshop_render_single_product_section($pid,'recommended'); wpbbshop_render_single_product_section($pid,'related'); } else { woocommerce_output_related_products(); } ?>
</div></main>
<?php endwhile; get_footer();
