<?php
if (!defined('ABSPATH')) exit;

function wpbb_platform_operations(): array {
    return [
        'flush-cache' => [
            'label' => 'Flush WordPress object cache',
            'command' => ['cache', 'flush'],
            'native' => static function (): string {
                wp_cache_flush();
                return 'Object cache flushed.';
            },
        ],
        'flush-rewrites' => [
            'label' => 'Flush rewrite rules',
            'command' => ['rewrite', 'flush', '--hard'],
            'native' => static function (): string {
                flush_rewrite_rules(true);
                return 'Rewrite rules flushed.';
            },
        ],
        'delete-transients' => [
            'label' => 'Delete transients',
            'command' => ['transient', 'delete', '--all'],
        ],
        'run-cron' => [
            'label' => 'Run due cron events',
            'command' => ['cron', 'event', 'run', '--due-now'],
        ],
        'redis-status' => [
            'label' => 'Redis status',
            'command' => ['redis', 'status'],
        ],
        'redis-enable' => [
            'label' => 'Enable Redis object cache',
            'command' => ['redis', 'enable'],
        ],
        'acorn-about' => [
            'label' => 'Acorn about',
            'command' => ['acorn', 'about'],
        ],
        'acorn-migrations' => [
            'label' => 'Acorn migration status',
            'command' => ['acorn', 'migrate:status'],
        ],
        'acorn-optimize-clear' => [
            'label' => 'Clear Acorn caches',
            'command' => ['acorn', 'optimize:clear'],
        ],
    ];
}

function wpbb_platform_command_string(array $args): string {
    $bin = defined('WPBB_ADMIN_CLI_BIN') ? WPBB_ADMIN_CLI_BIN : '/usr/local/bin/wp';
    $parts = [escapeshellcmd($bin), '--path=' . escapeshellarg(ABSPATH)];
    foreach ($args as $arg) {
        $parts[] = escapeshellarg((string) $arg);
    }
    return implode(' ', $parts);
}

function wpbb_platform_run_cli(array $args): array {
    if (!defined('WPBB_ALLOW_ADMIN_CLI') || !WPBB_ALLOW_ADMIN_CLI) {
        return ['ok' => false, 'output' => 'Admin CLI execution is disabled. Run this in WSL/SSH: ' . wpbb_platform_command_string($args)];
    }
    if (!function_exists('shell_exec')) {
        return ['ok' => false, 'output' => 'shell_exec is unavailable. Run this in WSL/SSH: ' . wpbb_platform_command_string($args)];
    }

    $command = wpbb_platform_command_string($args) . ' 2>&1';
    $output = (string) shell_exec($command);
    return ['ok' => true, 'output' => trim($output) ?: 'Command completed with no output.'];
}

function wpbb_platform_execute_operation(string $operation): array {
    $operations = wpbb_platform_operations();
    if (!isset($operations[$operation])) {
        return ['ok' => false, 'output' => 'Unknown operation.'];
    }
    $item = $operations[$operation];
    if (isset($item['native']) && is_callable($item['native'])) {
        try {
            return ['ok' => true, 'output' => (string) call_user_func($item['native'])];
        } catch (Throwable $e) {
            return ['ok' => false, 'output' => $e->getMessage()];
        }
    }
    return wpbb_platform_run_cli($item['command']);
}
