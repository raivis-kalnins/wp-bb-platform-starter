<?php
if (!defined('ABSPATH')) exit;

final class WPBB_SMTP {
    private static $instance = null;
    private $test_settings = null;
    private $test_error = null;
    private $is_testing = false;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('phpmailer_init', [$this, 'configure_phpmailer'], 20);
        add_action('wp_mail_failed', [$this, 'capture_mail_error']);
    }

    public function configure_phpmailer($phpmailer) {
        $settings = is_array($this->test_settings) ? $this->test_settings : $this->get_saved_settings();
        if (empty($settings['enabled']) || empty($settings['host'])) {
            return;
        }

        $host = $this->normalize_host($settings['host']);
        if ($host === '') {
            return;
        }

        $encryption = in_array($settings['encryption'], ['none', 'ssl', 'tls'], true) ? $settings['encryption'] : 'tls';
        $port = (int) $settings['port'];
        if ($port < 1 || $port > 65535) {
            $port = $this->default_port($encryption);
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $host;
        $phpmailer->Port = $port;
        $phpmailer->SMTPAuth = $settings['username'] !== '';
        $phpmailer->Username = $settings['username'];
        $phpmailer->Password = $settings['password'];
        $phpmailer->SMTPSecure = $encryption === 'none' ? '' : $encryption;
        $phpmailer->SMTPAutoTLS = 'tls' === $encryption;
        $phpmailer->Timeout = 20;
        $phpmailer->Timelimit = 20;
        $phpmailer->SMTPKeepAlive = false;
        $phpmailer->CharSet = get_bloginfo('charset') ?: 'UTF-8';

        if (!empty($settings['from_email']) && is_email($settings['from_email'])) {
            try {
                $phpmailer->setFrom($settings['from_email'], $settings['from_name'] ?: get_bloginfo('name'), false);
            } catch (Throwable $e) {
                if ($this->is_testing) {
                    $this->test_error = new WP_Error('wpbb_smtp_from_failed', $e->getMessage());
                }
            }
        }
    }

    public function capture_mail_error($error) {
        if ($this->is_testing && is_wp_error($error)) {
            $this->test_error = $error;
        }
    }

    public function send_test($recipient, array $settings) {
        $recipient = sanitize_email($recipient);
        if (!$recipient || !is_email($recipient)) {
            return new WP_Error('wpbb_smtp_invalid_recipient', __('Enter a valid test recipient email address.', 'wp-bbuilder'));
        }

        $settings = $this->sanitize_runtime_settings($settings);
        if ($settings['host'] === '') {
            return new WP_Error('wpbb_smtp_missing_host', __('SMTP host is required.', 'wp-bbuilder'));
        }
        if ($settings['port'] < 1 || $settings['port'] > 65535) {
            return new WP_Error('wpbb_smtp_invalid_port', __('SMTP port must be between 1 and 65535.', 'wp-bbuilder'));
        }
        if ($settings['username'] !== '' && $settings['password'] === '') {
            return new WP_Error('wpbb_smtp_missing_password', __('SMTP password is required when a username is set.', 'wp-bbuilder'));
        }
        if ($settings['from_email'] !== '' && !is_email($settings['from_email'])) {
            return new WP_Error('wpbb_smtp_invalid_from', __('Enter a valid From email address.', 'wp-bbuilder'));
        }

        $settings['enabled'] = 1;
        $this->test_settings = $settings;
        $this->test_error = null;
        $this->is_testing = true;

        $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(__('[%s] SMTP test email', 'wp-bbuilder'), $site_name);
        $message = sprintf(
            '<h2>%s</h2><p>%s</p><p><strong>%s:</strong> %s:%d (%s)</p><p><strong>%s:</strong> %s</p>',
            esc_html__('SMTP is working', 'wp-bbuilder'),
            esc_html__('This test was sent through the SMTP settings currently entered in WP BBuilder.', 'wp-bbuilder'),
            esc_html__('Server', 'wp-bbuilder'),
            esc_html($settings['host']),
            (int) $settings['port'],
            esc_html(strtoupper($settings['encryption'])),
            esc_html__('Sent at', 'wp-bbuilder'),
            esc_html(wp_date('Y-m-d H:i:s'))
        );

        $sent = wp_mail($recipient, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
        $error = $this->test_error;

        $this->test_settings = null;
        $this->test_error = null;
        $this->is_testing = false;

        if (!$sent) {
            if (is_wp_error($error)) {
                $message = trim($error->get_error_message());
                if ($message !== '') {
                    return new WP_Error('wpbb_smtp_test_failed', $message, $error->get_error_data());
                }
            }
            return new WP_Error('wpbb_smtp_test_failed', __('WordPress could not send the SMTP test email. Check the host, port, encryption, username, password and From address.', 'wp-bbuilder'));
        }

        return true;
    }

    public function get_saved_settings() {
        return $this->sanitize_runtime_settings([
            'enabled' => wpbb_get_option('smtp_enabled', 0),
            'host' => wpbb_get_option('smtp_host', ''),
            'port' => wpbb_get_option('smtp_port', '587'),
            'encryption' => wpbb_get_option('smtp_encryption', 'tls'),
            'username' => wpbb_get_option('smtp_username', ''),
            'password' => wpbb_get_option('smtp_password', ''),
            'from_email' => wpbb_get_option('smtp_from_email', ''),
            'from_name' => wpbb_get_option('smtp_from_name', ''),
        ]);
    }

    private function sanitize_runtime_settings(array $settings) {
        $encryption = sanitize_key((string) ($settings['encryption'] ?? 'tls'));
        if (!in_array($encryption, ['none', 'ssl', 'tls'], true)) {
            $encryption = 'tls';
        }

        $port = (int) ($settings['port'] ?? 0);
        if ($port === 0) {
            $port = $this->default_port($encryption);
        }

        $from_email = sanitize_email((string) ($settings['from_email'] ?? ''));
        $username_email = sanitize_email((string) ($settings['username'] ?? ''));
        if ($from_email === '' && $username_email !== '') {
            $from_email = $username_email;
        }
        if ($from_email === '') {
            $from_email = sanitize_email((string) get_option('admin_email'));
        }
        $from_name = sanitize_text_field((string) ($settings['from_name'] ?? ''));
        if ($from_name === '') {
            $from_name = sanitize_text_field((string) get_bloginfo('name'));
        }

        return [
            'enabled' => !empty($settings['enabled']) ? 1 : 0,
            'host' => $this->normalize_host((string) ($settings['host'] ?? '')),
            'port' => $port,
            'encryption' => $encryption,
            'username' => trim((string) ($settings['username'] ?? '')),
            'password' => (string) ($settings['password'] ?? ''),
            'from_email' => $from_email,
            'from_name' => $from_name,
        ];
    }

    private function normalize_host($host) {
        $host = trim(sanitize_text_field((string) $host));
        $host = preg_replace('#^(?:smtp|smtps|ssl|tls)://#i', '', $host);
        return trim((string) $host, " \t\n\r\0\x0B/");
    }

    private function default_port($encryption) {
        if ($encryption === 'ssl') {
            return 465;
        }
        if ($encryption === 'none') {
            return 25;
        }
        return 587;
    }
}
