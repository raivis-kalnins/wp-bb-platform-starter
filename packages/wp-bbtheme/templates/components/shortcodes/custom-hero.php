<?php
/**
 * Register ACF-backed custom hero shortcodes after ACF has initialized.
 *
 * Older releases read get_fields( 'option' ) while the theme bootstrap file was
 * loading. ACF 5.11+ explicitly rejects value reads before acf/init, and
 * WordPress 6.7 surfaces the resulting notice. The data read now happens on
 * init, after ACF's initialization cycle, and does nothing when ACF is absent.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_theme_custom_hero_image_url' ) ) {
    function wp_theme_custom_hero_image_url( $value ) {
        if ( is_array( $value ) ) {
            if ( ! empty( $value['url'] ) ) {
                return esc_url_raw( $value['url'] );
            }
            $attachment_id = absint( $value['ID'] ?? $value['id'] ?? 0 );
            return $attachment_id ? (string) wp_get_attachment_image_url( $attachment_id, 'full' ) : '';
        }
        if ( is_numeric( $value ) ) {
            return (string) wp_get_attachment_image_url( absint( $value ), 'full' );
        }
        return is_string( $value ) ? esc_url_raw( $value ) : '';
    }
}

if ( ! function_exists( 'wp_theme_register_custom_hero_shortcodes' ) ) {
    function wp_theme_register_custom_hero_shortcodes() {
        if ( ! function_exists( 'wp_theme_acf_ready' ) || ! wp_theme_acf_ready() ) {
            return;
        }

        $hero_items = wp_theme_acf_get( 'custom_heros', 'option', array() );
        if ( ! is_array( $hero_items ) ) {
            return;
        }

        foreach ( $hero_items as $hero ) {
            if ( ! is_array( $hero ) ) {
                continue;
            }

            $hero_id = sanitize_key( (string) ( $hero['hero_id'] ?? '' ) );
            if ( '' === $hero_id ) {
                continue;
            }

            $background = wp_theme_custom_hero_image_url( $hero['hero_sec_background'] ?? '' );
            $caption    = trim( (string) ( $hero['hero_sec_caption'] ?? '' ) );
            $text       = trim( (string) ( $hero['hero_sec_txt'] ?? '' ) );
            $button     = is_array( $hero['hero_sec_button'] ?? null ) ? $hero['hero_sec_button'] : array();
            $button_url = esc_url_raw( (string) ( $button['url'] ?? '' ) );
            $button_text = trim( (string) ( $button['title'] ?? '' ) );

            $heading = $caption
                ? '<h1 class="wp-block-heading" style="color:white">' . esc_html( $caption ) . '</h1>'
                : '<!-- wp:post-title {"level":1,"className":"h1","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} /-->';
            $copy = $text ? '<p>' . wp_kses_post( $text ) . '</p>' : '';
            $cta  = ( $button_url && $button_text )
                ? '<a href="' . esc_url( $button_url ) . '" class="btn btn-primary">' . esc_html( $button_text ) . '</a>'
                : '';

            $style = $background
                ? ' style="position:relative;background:transparent url(' . esc_url( $background ) . ') center / cover no-repeat"'
                : ' style="position:relative"';
            $markup = '<div class="wp-block-hero-section-block"' . $style . '>'
                . '<div class="wp-block-hero-section-block__content container"><div class="section-wrapper">'
                . '<div class="wp-block-hero-section-block__content">' . $heading . $copy . $cta
                . '<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div></div>'
                . '</div></div></div>';

            add_shortcode(
                'custom_hero_' . $hero_id,
                static function() use ( $markup ) {
                    return $markup;
                }
            );
        }
    }
}
add_action( 'init', 'wp_theme_register_custom_hero_shortcodes', 20 );
