<?php
/**
 * Minimal hybrid PHP header used by classic/legacy templates.
 * The shared visual header is rendered by wp_theme_render_site_header() on wp_body_open.
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
