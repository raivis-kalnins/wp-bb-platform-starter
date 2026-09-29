<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bbtheme_enqueue_animation_assets')) {
    function bbtheme_enqueue_animation_assets() {
        $settings = bbtheme_get_animation_settings();
        if (empty($settings['enabled'])) {
            return;
        }

        if (!in_array(($settings['engine'] ?? 'native'), ['animate','both'], true)) {
            return;
        }

        wp_enqueue_style(
            'bbtheme-animate-css',
            'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
            [],
            '4.1.1'
        );
    }
}
add_action('wp_enqueue_scripts', 'bbtheme_enqueue_animation_assets', 25);

if (!function_exists('bbtheme_output_animation_variables')) {
    function bbtheme_output_animation_variables() {
        // Child themes own motion presentation; parent outputs no custom CSS.
    }
}
add_action('wp_head', 'bbtheme_output_animation_variables', 99);


if (!function_exists('bbtheme_enqueue_optional_motion_assets')) {
    function bbtheme_enqueue_optional_motion_assets() {
        if (!function_exists('wp_theme_style_tokens')) {
            return;
        }

        $tokens = wp_theme_style_tokens();

        if (!empty($tokens['theme_motion_enable_lottie'])) {
            wp_enqueue_script(
                'bbtheme-dotlottie-player',
                'https://unpkg.com/@lottiefiles/dotlottie-wc@latest/dist/dotlottie-wc.js',
                [],
                null,
                true
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'bbtheme_enqueue_optional_motion_assets', 30);

if (!function_exists('bbtheme_enqueue_native_motion_assets')) {
    function bbtheme_enqueue_native_motion_assets() {
        $settings = bbtheme_get_animation_settings();
        if (empty($settings['enabled']) || !in_array(($settings['engine'] ?? 'native'), ['native','both'], true)) return;
        $base = get_template_directory();
        $js = $base . '/assets/js/theme-motion.js';
        wp_enqueue_script('wp-theme-native-motion', get_template_directory_uri() . '/assets/js/theme-motion.js', [], file_exists($js) ? filemtime($js) : wp_get_theme()->get('Version'), true);
        wp_localize_script('wp-theme-native-motion', 'WPThemeMotion', [
            'enabled' => !empty($settings['enabled']),
            'duration' => $settings['default_duration'] ?? '760ms',
            'delay' => $settings['default_delay'] ?? '0ms',
            'disableOnMobile' => !empty($settings['disable_on_mobile']),
            'respectReducedMotion' => !empty($settings['respect_reduced_motion']),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'bbtheme_enqueue_native_motion_assets', 26);
