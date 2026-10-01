<?php
/** Generic WP BB HOME & GARDEN page template with WooCommerce core page fallbacks. */
defined('ABSPATH') || exit;
if (function_exists('is_cart') && is_cart()) { get_template_part('page', 'cart'); return; }
if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url()) { get_template_part('page', 'checkout'); return; }
get_header(); ?>
<main class="wpbbshop-page-shell wpbbshop-container">
<?php if (function_exists('wpbbshop_seo_breadcrumbs_235')) { echo wpbbshop_seo_breadcrumbs_235(); } ?>
<?php while (have_posts()) : the_post();
    $slug = get_post_field('post_name', get_the_ID());
    $is_info = (function_exists('wpbbshop_information_page_definitions') && array_key_exists($slug, wpbbshop_information_page_definitions())) || in_array($slug, array('piegade-un-apmaksa','delivery-payment','atgriesana-un-garantija','returns-warranty','pirksanas-noteikumi','terms-and-conditions','terms-conditions','about-us'), true);
?>
  <header class="wpbbshop-page-heading<?php echo $is_info ? ' wpbbshop-info-heading' : ''; ?>">
    <?php if ($is_info) : ?><span class="wpbbshop-page-kicker">WP BB HOME & GARDEN</span><?php endif; ?>
    <h1><?php the_title(); ?></h1>
  </header>
  <article <?php post_class('wpbbshop-page-card'); ?>>
    <div class="entry-content"><?php the_content(); ?></div>
  </article>
<?php endwhile; ?>
</main>
<?php get_footer();
