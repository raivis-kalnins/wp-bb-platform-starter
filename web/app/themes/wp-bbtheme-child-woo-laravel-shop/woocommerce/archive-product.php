<?php
/** WP BB Home & Garden v4.0.9 large-catalogue archive. */
defined('ABSPATH') || exit;
get_header();
?>
<main class="wpbbshop-archive-page wpbbshop-archive-v409 llg-archive-page">
  <div class="wpbbshop-container">
    <?php woocommerce_output_all_notices(); ?>
    <section class="wpbbshop-archive-hero llg-archive-hero card border-0 shadow-sm">
      <div>
        <?php echo function_exists('wpbbshop_seo_breadcrumbs_235') ? wpbbshop_seo_breadcrumbs_235() : ''; ?>
        <h1><?php echo esc_html(function_exists('wpbbshop_v409_archive_title') ? wpbbshop_v409_archive_title() : woocommerce_page_title(false)); ?></h1>
        <?php echo function_exists('wpbbshop_v409_archive_description') ? wpbbshop_v409_archive_description() : ''; ?>
      </div>
      <div class="wpbbshop-archive-hero-badges">
        <span><?php echo esc_html(wpbbshop_v409_t('Fast search','Ātra meklēšana')); ?></span>
        <span><?php echo esc_html(wpbbshop_v409_t('Category first','Kategorijas vispirms')); ?></span>
        <span><?php echo esc_html(wpbbshop_v409_t('Large catalogue ready','Gatavs lielam katalogam')); ?></span>
      </div>
    </section>

    <?php echo function_exists('wpbbshop_v409_category_browser') ? wpbbshop_v409_category_browser() : ''; ?>

    <?php $wpbbshop_smart_filter = function_exists('wp_theme_woo_support_filter_markup') && function_exists('wp_theme_woo_support_filter_results_markup'); ?>
    <?php if ($wpbbshop_smart_filter) : ?>
      <section class="wpbbshop-v412-smart-filter" aria-label="<?php echo esc_attr(wpbbshop_v409_t('Product filters and comparison','Preču filtri un salīdzināšana')); ?>">
        <?php echo wp_theme_woo_support_filter_markup(array('posts_per_page'=>24)); ?>
      </section>
      <section class="wpbbshop-archive-products-col wpbbshop-v412-plugin-results">
        <?php echo wp_theme_woo_support_filter_results_markup(array('posts_per_page'=>24)); ?>
      </section>
    <?php else : ?>
      <?php echo function_exists('wpbbshop_v409_filter_panel') ? wpbbshop_v409_filter_panel() : ''; ?>

    <section class="wpbbshop-archive-products-col">
      <div class="wpbbshop-v409-result-row">
        <div id="wpbbshop-v409-result-status" class="wpbbshop-v409-result-status"><?php woocommerce_result_count(); ?></div>
      </div>
      <?php if (woocommerce_product_loop()) : ?>
        <div class="wpbbshop-bootstrap-products" id="wpbbshop-archive-products">
          <?php while (have_posts()) : the_post(); global $product; echo function_exists('wpbbshop_v409_render_card') ? wpbbshop_v409_render_card($product) : ''; endwhile; ?>
        </div>
        <?php echo function_exists('wpbbshop_v409_load_more_button') ? wpbbshop_v409_load_more_button() : ''; ?>
      <?php else : ?>
        <div class="wpbbshop-v409-empty"><?php echo esc_html(wpbbshop_v409_t('No products matched this view.','Šim skatam neatbilst neviena prece.')); ?></div>
      <?php endif; ?>
    </section>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
