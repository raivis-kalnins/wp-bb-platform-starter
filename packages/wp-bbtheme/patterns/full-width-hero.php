<?php
/**
 * Title: Full Width Hero
 * Slug: wp-theme/full-width-hero
 * Categories: wp-theme-current
 * Description: Full-width BBuilder hero slider ready for sector-specific copy and photography.
 */
$profile=function_exists('wp_theme_get_demo_profile')?wp_theme_get_demo_profile():array();
$slides=array(array('type'=>'hero','eyebrow'=>$profile['eyebrow']??'Welcome','title'=>$profile['hero_title']??'A clear headline for the website.','text'=>$profile['hero_text']??'Use this section for the primary proposition and action.','buttonText'=>$profile['primary_label']??'Get started','buttonUrl'=>$profile['primary_url']??'/contact/','image'=>$profile['hero_image']??''));
$attrs=array('slides'=>$slides,'slidesPerView'=>1,'slidesTablet'=>1,'slidesMobile'=>1,'spaceBetween'=>0,'speed'=>700,'rewind'=>true,'autoplay'=>false,'demoStyle'=>'hero','showPagination'=>true,'showNavigation'=>true); ?>
<!-- wp:wpbb/row {"containerClass":"container-fluid","customClasses":"wp-theme-sector-hero"} --><!-- wp:wpbb/column {"xs":12} --><!-- wp:wpbb/swiper <?php echo wp_json_encode($attrs,JSON_UNESCAPED_SLASHES); ?> /--><!-- /wp:wpbb/column --><!-- /wp:wpbb/row -->
