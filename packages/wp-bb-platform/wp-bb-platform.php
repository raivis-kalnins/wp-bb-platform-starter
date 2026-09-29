<?php
/**
 * Plugin Name: WP BB Platform
 * Description: Platform health, safe operations UI, WP-CLI commands and Acorn bootstrap for WP BB projects.
 * Version: 0.1.0
 * Requires PHP: 8.3
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPBB_PLATFORM_VERSION', '0.1.0');
define('WPBB_PLATFORM_DIR', plugin_dir_path(__FILE__));

define('WPBB_PLATFORM_URL', plugin_dir_url(__FILE__));

require_once WPBB_PLATFORM_DIR . 'includes/acorn.php';
require_once WPBB_PLATFORM_DIR . 'includes/health.php';
require_once WPBB_PLATFORM_DIR . 'includes/mail.php';
require_once WPBB_PLATFORM_DIR . 'includes/operations.php';
require_once WPBB_PLATFORM_DIR . 'includes/admin.php';
require_once WPBB_PLATFORM_DIR . 'includes/cli.php';
