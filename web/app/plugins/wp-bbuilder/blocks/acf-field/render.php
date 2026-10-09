<?php
/**
 * Server-side render template for the ACF Field block.
 *
 * @var array    $attributes Block attributes.
 * @var WP_Block $block      Block instance and context.
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'wpbb_render_acf_field_block' ) ) {
    echo wpbb_render_acf_field_block( $attributes, $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
