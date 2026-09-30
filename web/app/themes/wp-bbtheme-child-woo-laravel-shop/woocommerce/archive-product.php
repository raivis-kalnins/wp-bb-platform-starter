<?php
/**
 * WP BB HOME & GARDEN WooCommerce archive/category template.
 * Uses the green storefront cards and avoids empty sidebar columns.
 */
defined('ABSPATH') || exit;
get_header();
$sidebar_enabled = wpbbshop_get_theme_option('archive_sidebar', '1') === '1';
$sidebar_html = $sidebar_enabled && function_exists('wpbbshop_wc_archive_sidebar') ? trim((string) wpbbshop_wc_archive_sidebar()) : '';
$show_sidebar = $sidebar_enabled && $sidebar_html !== '';
?>
<main class="wpbbshop-archive-page wpbbshop-archive-v27 llg-archive-page">
    <div class="wpbbshop-container">
        <?php woocommerce_output_all_notices(); ?>
        <section class="wpbbshop-archive-hero llg-archive-hero card border-0 shadow-sm">
            <div>
                <?php echo function_exists('wpbbshop_seo_breadcrumbs_235') ? wpbbshop_seo_breadcrumbs_235() : ''; ?>
                <h1><?php echo esc_html(wpbbshop_wc_archive_title()); ?></h1>
                <?php echo wpbbshop_wc_archive_description(); ?>
            </div>
            <div class="wpbbshop-archive-hero-badges">
                <span>Ātra piegāde</span>
                <span>Oficiālie zīmoli</span>
                <span>14 dienu atgriešana</span>
            </div>
        </section>

        <div class="wpbbshop-archive-layout <?php echo $show_sidebar ? '' : 'wpbbshop-no-sidebar'; ?>">
            <?php if ($show_sidebar) : ?>
            <aside class="wpbbshop-archive-filter-col">
                <?php echo $sidebar_html; ?>
            </aside>
            <?php endif; ?>

            <section class="wpbbshop-archive-products-col">
                <div class="wpbbshop-archive-toolbar card border-0 shadow-sm d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
                    <div class="wpbbshop-result-count"><?php woocommerce_result_count(); ?></div>
                    <div class="wpbbshop-ordering"><?php woocommerce_catalog_ordering(); ?></div>
                </div>

                <?php if (woocommerce_product_loop()) : ?>
                    <div class="wpbbshop-bootstrap-products" id="wpbbshop-archive-products">
                    <?php while (have_posts()) : the_post(); global $product; ?>
                        <div class="wpbbshop-bs-product-col">
                            <?php
                            if (function_exists('wpbbshop_green_product_card')) {
                                echo wpbbshop_green_product_card($product);
                            } else {
                                echo wpbbshop_product_card($product);
                            }
                            ?>
                        </div>
                    <?php endwhile; ?>
                    </div>
                    <?php if (function_exists('wpbbshop_v27_archive_load_more_button')) { echo wpbbshop_v27_archive_load_more_button(); } ?>
                    <div class="wpbbshop-archive-pagination"><?php woocommerce_pagination(); ?></div>
                <?php else : ?>
                    <div class="wpbbshop-no-products card border-0 shadow-sm"><?php wc_get_template('loop/no-products-found.php'); ?></div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
<?php get_footer(); ?>
