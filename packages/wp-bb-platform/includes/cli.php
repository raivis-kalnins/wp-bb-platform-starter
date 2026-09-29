<?php
if (!defined('ABSPATH')) exit;

if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    class WPBB_Platform_CLI_Command {
        public function health($args, $assoc_args): void {
            $rows = [];
            foreach (wpbb_platform_health() as $name => $value) {
                $rows[] = ['component' => $name, 'status' => (string) $value];
            }
            WP_CLI\Utils\format_items('table', $rows, ['component', 'status']);
        }

        public function cache($args, $assoc_args): void {
            wp_cache_flush();
            WP_CLI::success('WordPress object cache flushed.');
        }

        public function operations($args, $assoc_args): void {
            $rows = [];
            foreach (wpbb_platform_operations() as $key => $op) {
                $rows[] = ['operation' => $key, 'label' => $op['label']];
            }
            WP_CLI\Utils\format_items('table', $rows, ['operation', 'label']);
        }
    }
    WP_CLI::add_command('bb', 'WPBB_Platform_CLI_Command');
}
