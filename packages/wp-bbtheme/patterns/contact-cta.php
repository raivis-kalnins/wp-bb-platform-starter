<?php
/**
 * Title: Contact CTA
 * Slug: wp-theme/contact-cta
 * Categories: wp-theme-current
 * Description: BBuilder call-to-action section linked to the shared contact page.
 */
$url=function_exists('wp_theme_demo_page_url')?wp_theme_demo_page_url('contact'):'/contact/'; ?>
<!-- wp:wpbb/cta-section {"title":"Ready to talk through the next step?","titleTag":"h2","text":"Tell us what you need and the team will point you in the right direction.","buttonText":"Contact the team","buttonUrl":"<?php echo esc_url($url); ?>","className":"wp-theme-home-cta wp-theme-home-cta--bbuilder"} /-->
