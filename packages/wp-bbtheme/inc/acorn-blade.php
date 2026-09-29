<?php
if (!defined('ABSPATH')) exit;

/**
 * Register child and parent Blade view directories when Acorn is available.
 * Existing PHP/block templates continue to work when Acorn is unavailable.
 */
add_action('after_setup_theme', static function (): void {
    if (!function_exists('app')) {
        return;
    }

    try {
        $factory = app('view');
        if (!$factory || !method_exists($factory, 'getFinder')) {
            return;
        }
        $finder = $factory->getFinder();
        $parent = get_template_directory() . '/resources/views';
        $child = get_stylesheet_directory() . '/resources/views';

        if (is_dir($parent)) {
            if (method_exists($finder, 'prependLocation')) {
                $finder->prependLocation($parent);
            } elseif (method_exists($finder, 'addLocation')) {
                $finder->addLocation($parent);
            }
        }

        if ($child !== $parent && is_dir($child)) {
            if (method_exists($finder, 'prependLocation')) {
                $finder->prependLocation($child);
            } elseif (method_exists($finder, 'addLocation')) {
                $finder->addLocation($child);
            }
        }
    } catch (Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP BBTheme Blade view registration failed: ' . $e->getMessage());
        }
    }
}, 20);

if (!function_exists('wp_bb_blade')) {
    /**
     * Render a Blade view through Acorn. Returns false when Blade is unavailable
     * or the requested view does not exist, allowing safe PHP fallbacks.
     *
     * @return string|false
     */
    function wp_bb_blade(string $view_name, array $data = [], bool $echo = true) {
        if (!function_exists('view')) {
            return false;
        }
        try {
            $view = view($view_name, $data);
            if (method_exists($view, 'exists') && !$view->exists()) {
                return false;
            }
            $html = $view->render();
            if ($echo) {
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return $html;
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WP BBTheme Blade render failed for ' . $view_name . ': ' . $e->getMessage());
            }
            return false;
        }
    }
}
