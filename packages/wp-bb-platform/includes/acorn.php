<?php
if (!defined('ABSPATH')) exit;

add_action('after_setup_theme', static function (): void {
    if (!class_exists('Roots\\Acorn\\Application')) {
        return;
    }

    try {
        if (!function_exists('app') || !app()->bound('events')) {
            Roots\Acorn\Application::configure()->boot();
        }
    } catch (Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WP BB Platform: Acorn boot failed: ' . $e->getMessage());
        }
    }
}, 0);
