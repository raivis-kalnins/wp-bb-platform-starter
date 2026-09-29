<?php
if (!defined('ABSPATH')) exit;

add_action('phpmailer_init', static function ($phpmailer): void {
    if (!defined('WP_ENV') || !in_array(WP_ENV, ['development', 'local'], true)) {
        return;
    }
    $host = getenv('MAIL_HOST') ?: 'mailpit';
    $port = (int) (getenv('MAIL_PORT') ?: 1025);
    if (!$host) return;

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->Port = $port;
    $phpmailer->SMTPAuth = false;
    $phpmailer->SMTPSecure = '';
});
