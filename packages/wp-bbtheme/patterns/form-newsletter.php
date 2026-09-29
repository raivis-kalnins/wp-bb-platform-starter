<?php
/**
 * Title: Form — Newsletter signup
 * Slug: wp-bbtheme/form-newsletter
 * Categories: wp-theme-forms, wp-patterns-main, wp-theme-current
 * Description: Simple newsletter signup form for content and footer placements.
 */
defined( 'ABSPATH' ) || exit;
echo function_exists( 'wp_theme_form_library_pattern_content' ) ? wp_theme_form_library_pattern_content( 'newsletter' ) : '';
