<?php
/**
 * Template Name: BB Blade Page
 */
if (!defined('ABSPATH')) exit;
if (function_exists('wp_bb_blade') && false !== wp_bb_blade('pages.blade-page')) {
    return;
}
get_header();
while (have_posts()) {
    the_post();
    echo '<main class="site-main container py-5"><h1>' . esc_html(get_the_title()) . '</h1><div class="entry-content">';
    the_content();
    echo '</div></main>';
}
get_footer();
