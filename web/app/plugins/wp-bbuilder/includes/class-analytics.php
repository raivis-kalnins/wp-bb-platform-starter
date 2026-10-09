<?php
if (!defined('ABSPATH')) exit;

/**
 * Privacy-first local analytics for WP BBuilder.
 *
 * Stores coarse, first-party visit events in a dedicated WordPress table.
 * No raw IP addresses, full user agents, query strings, or form data are stored.
 * Country is read only from trusted proxy/CDN country headers when available.
 */
final class WPBB_Analytics {
    private static $instance = null;
    const DB_VERSION = '1.1.0';
    const CRON_HOOK = 'wpbb_analytics_cleanup';

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_rest']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_tracker'], 99);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_dashboard_setup', [$this, 'dashboard_widget']);
        add_action('admin_bar_menu', [$this, 'admin_bar_menu'], 88);
        add_action('admin_head', [$this, 'admin_bar_styles']);
        add_action('wp_head', [$this, 'admin_bar_styles']);
        add_action('admin_init', [$this, 'maybe_install']);
        add_action('admin_init', [$this, 'maybe_register_blade_namespace']);
        add_action('admin_post_wpbb_analytics_save_settings', [$this, 'save_settings']);
        add_action('admin_post_wpbb_analytics_generate_demo', [$this, 'generate_demo']);
        add_action('admin_post_wpbb_analytics_clear_demo', [$this, 'clear_demo']);
        add_action('admin_post_wpbb_analytics_clear_all', [$this, 'clear_all']);
        add_action(self::CRON_HOOK, [$this, 'cleanup']);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        while ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
        }
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'wpbb_analytics';
    }

    public static function install() {
        global $wpdb;
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            occurred_at datetime NOT NULL,
            visitor_hash char(64) NOT NULL,
            session_hash char(64) NOT NULL,
            path varchar(512) NOT NULL,
            page_id bigint(20) unsigned NOT NULL DEFAULT 0,
            object_type varchar(32) NOT NULL DEFAULT '',
            title varchar(255) NOT NULL DEFAULT '',
            referrer_host varchar(191) NOT NULL DEFAULT '',
            country char(2) NOT NULL DEFAULT '',
            device varchar(20) NOT NULL DEFAULT '',
            is_demo tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY occurred_at (occurred_at),
            KEY visitor_hash (visitor_hash),
            KEY session_hash (session_hash),
            KEY page_id (page_id),
            KEY object_type (object_type),
            KEY country (country),
            KEY is_demo (is_demo),
            KEY dataset_date (is_demo, occurred_at),
            KEY session_date (session_hash, occurred_at)
        ) {$charset};";
        dbDelta($sql);
        update_option('wpbb_analytics_db_version', self::DB_VERSION, false);
    }

    public function maybe_install() {
        if (get_option('wpbb_analytics_db_version') !== self::DB_VERSION) self::install();
    }

    public function register_rest() {
        register_rest_route('wpbb/v1', '/analytics/visit', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_track'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function enqueue_tracker() {
        if (!wpbb_get_option('local_analytics_enabled', 1)) return;
        if (is_admin() || is_feed() || wp_doing_ajax()) return;
        if (function_exists('is_account_page') && is_account_page()) return;
        if (function_exists('is_order_received_page') && is_order_received_page()) return;
        if (function_exists('is_checkout_pay_page') && is_checkout_pay_page()) return;
        if (is_user_logged_in() && current_user_can('edit_posts')) return;
        // The browser waits for consent, including consent granted after this page loads.

        wp_enqueue_script(
            'wpbb-local-analytics',
            WPBB_PLUGIN_URL . 'assets/analytics-tracker.js',
            [],
            WPBB_VERSION,
            true
        );

        $page_id = function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
        $object_type = '';
        if (function_exists('is_product') && is_product()) $object_type = 'product';
        elseif (is_singular('post')) $object_type = 'post';
        elseif (is_page()) $object_type = 'page';
        elseif (is_category() || is_tag() || is_tax()) $object_type = 'archive';
        elseif (is_front_page()) $object_type = 'home';

        wp_localize_script('wpbb-local-analytics', 'WPBBAnalytics', [
            'endpoint' => esc_url_raw(rest_url('wpbb/v1/analytics/visit')),
            'pageId' => $page_id,
            'objectType' => $object_type,
            'respectConsent' => $this->must_have_consent() ? 1 : 0,
            'builtInConsent' => (bool)wpbb_get_option('cookie_consent_enabled',0),
            'retentionDays' => max(30,min(730,(int)wpbb_get_option('local_analytics_retention_days',180))),
        ]);
    }

    private function must_have_consent() {
        return (bool) wpbb_get_option('local_analytics_respect_consent', 1);
    }

    private function is_bot($ua) {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|monitor|uptime|headless|phantom|selenium|curl|wget/i', (string) $ua);
    }

    private function country_from_headers() {
        // Opt in only after the origin is protected and the proxy overwrites client-supplied headers.
        $key = (string)apply_filters('wpbb_analytics_trusted_country_header', '');
        if (!$key || empty($_SERVER[$key]) || !is_string($_SERVER[$key])) return '';
        $country = strtoupper(sanitize_text_field(wp_unslash($_SERVER[$key])));
        return preg_match('/^[A-Z]{2}$/D',$country) && !in_array($country,['XX','T1'],true) ? $country : '';
    }

    private function device_from_ua($ua) {
        $ua = strtolower((string) $ua);
        if (strpos($ua, 'ipad') !== false || strpos($ua, 'tablet') !== false) return 'tablet';
        if (strpos($ua, 'mobi') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'android') !== false) return 'mobile';
        return 'desktop';
    }

    private function clean_path($value) {
        $path = wp_parse_url((string) $value, PHP_URL_PATH);
        if (!$path) $path = '/';
        $path = '/' . ltrim($path, '/');
        return substr(sanitize_text_field($path), 0, 512);
    }

    public function rest_track(WP_REST_Request $request) {
        if (!wpbb_get_option('local_analytics_enabled', 1)) return new WP_REST_Response(['stored' => false], 200);
        if ($this->must_have_consent() && function_exists('wp_has_consent') && !wp_has_consent('statistics')) return new WP_REST_Response(['stored' => false], 200);

        if (($_SERVER['HTTP_DNT'] ?? '') === '1' || ($_SERVER['HTTP_SEC_GPC'] ?? '') === '1') return new WP_REST_Response(['stored'=>false],200);
        $auth_user = function_exists('wp_validate_auth_cookie') ? wp_validate_auth_cookie('', 'logged_in') : 0;
        if ($auth_user && user_can($auth_user,'edit_posts')) return new WP_REST_Response(['stored'=>false],200);

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
        if ($this->is_bot($ua)) return new WP_REST_Response(['stored' => false], 200);
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_ORIGIN'])) : '';
        if ($origin) {
            $origin_host = strtolower((string) wp_parse_url($origin, PHP_URL_HOST));
            $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
            if ($origin_host && $home_host && $origin_host !== $home_host) return new WP_REST_Response(['stored' => false], 200);
        }

        $data = $request->get_json_params();
        if (!is_array($data)) $data = [];
        if ($this->must_have_consent() && ($data['consent'] ?? '') !== 'granted') return new WP_REST_Response(['stored'=>false],200);
        $visitor = isset($data['visitor']) ? sanitize_text_field($data['visitor']) : '';
        $session = isset($data['session']) ? sanitize_text_field($data['session']) : '';
        if ($visitor === '' || $session === '' || strlen($visitor) > 128 || strlen($session) > 128) {
            return new WP_REST_Response(['stored' => false], 200);
        }

        $path = $this->clean_path($data['path'] ?? '/');
        if (preg_match('~/(wp-admin|wp-json|wp-login\.php|my-account|mans-konts|order-pay|order-received)(/|$)~i',$path)) {
            return new WP_REST_Response(['stored' => false], 200);
        }

        $ref_host = '';
        if (!empty($data['referrer'])) {
            $ref_host = strtolower((string) wp_parse_url(esc_url_raw($data['referrer']), PHP_URL_HOST));
            $own = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
            if ($ref_host === $own) $ref_host = '';
        }

        global $wpdb;
        $salt = wp_salt('auth');
        $visitor_hash = hash_hmac('sha256', $visitor, $salt);
        $session_hash = hash_hmac('sha256', $session, $salt);
        $recent_cutoff = gmdate('Y-m-d H:i:s', time() - 30);
        $duplicate = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . self::table_name() . ' WHERE is_demo=0 AND session_hash=%s AND path=%s AND occurred_at >= %s LIMIT 1',
            $session_hash,
            $path,
            $recent_cutoff
        ));
        if ($duplicate) return new WP_REST_Response(['stored' => false, 'duplicate' => true], 200);
        $ok = $wpdb->insert(self::table_name(), [
            'occurred_at' => current_time('mysql', true),
            'visitor_hash' => $visitor_hash,
            'session_hash' => $session_hash,
            'path' => $path,
            'page_id' => absint($data['pageId'] ?? 0),
            'object_type' => substr(sanitize_key($data['objectType'] ?? ''), 0, 32),
            'title' => substr(sanitize_text_field($data['title'] ?? ''), 0, 255),
            'referrer_host' => substr(sanitize_text_field($ref_host), 0, 191),
            'country' => $this->country_from_headers(),
            'device' => $this->device_from_ua($ua),
            'is_demo' => 0,
        ], ['%s','%s','%s','%s','%d','%s','%s','%s','%s','%s','%d']);

        return new WP_REST_Response(['stored' => (bool) $ok], 200);
    }

    public function admin_menu() {
        // Keep BBuilder administration together under WordPress Settings.
        // Analytics used to create its own top-level menu, which split one
        // plugin across two admin destinations and made the sidebar noisier.
        add_options_page(
            __('WP BBuilder Statistics', 'wp-bbuilder'),
            __('BBuilder Statistics', 'wp-bbuilder'),
            'manage_options',
            'wpbb-analytics',
            [$this, 'render_dashboard']
        );
        add_options_page(
            __('WP BBuilder Analytics Settings', 'wp-bbuilder'),
            __('BBuilder Analytics', 'wp-bbuilder'),
            'manage_options',
            'wpbb-analytics-settings',
            [$this, 'render_settings']
        );
    }

    public function admin_assets($hook) {
        if (strpos((string) $hook, 'wpbb-analytics') === false) return;
        wp_enqueue_style('wpbb-analytics-admin', WPBB_PLUGIN_URL . 'assets/analytics-admin.css', [], WPBB_VERSION);
        wp_enqueue_script('wpbb-analytics-admin', WPBB_PLUGIN_URL . 'assets/analytics-admin.js', [], WPBB_VERSION, true);
    }

    private function range_days() {
        $days = isset($_GET['range']) ? absint($_GET['range']) : 30;
        return in_array($days, [7,30,90,180,365], true) ? $days : 30;
    }

    private function where_range($days) {
        return gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
    }

    public function get_report($days = 30, $demo = false, $window = null) {
        return WPBB_Analytics_Insights::report($window ?: WPBB_Analytics_Window::make($days), $demo);
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) return;
        $window = WPBB_Analytics_Window::make($this->range_days(), sanitize_text_field(wp_unslash($_GET['start'] ?? '')), sanitize_text_field(wp_unslash($_GET['end'] ?? '')));
        $data = $this->get_report($window['days'], 'demo' === ($_GET['dataset'] ?? ''), $window);
        if (!empty($data['error'])) { echo '<div class="wrap"><h1>Website statistics</h1><div class="notice notice-error"><p>'.esc_html($data['error']).'</p></div></div>'; return; }
        $data['settings_url'] = admin_url('options-general.php?page=wpbb-analytics-settings');
        $data['full_settings_url'] = admin_url('options-general.php?page=wpbb-settings');
        $data['nonce'] = wp_create_nonce('wpbb_analytics_admin');
        $this->render_view('analytics-dashboard', $data);
    }

    private function render_view($name, array $data) {
        $blade = WPBB_PLUGIN_DIR . 'views/' . $name . '.blade.php';
        $fallback = WPBB_PLUGIN_DIR . 'views/' . $name . '.php';
        if (function_exists('view') && is_readable($blade)) {
            try {
                $factory = view();
                if (is_object($factory) && method_exists($factory, 'addNamespace')) {
                    $factory->addNamespace('wpbb', WPBB_PLUGIN_DIR . 'views');
                    echo view('wpbb::' . $name, $data)->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    return;
                }
            } catch (Throwable $e) {
                // Fall through to the native PHP template on non-Acorn installs.
            }
        }
        if (is_readable($fallback)) {
            extract($data, EXTR_SKIP);
            include $fallback;
        }
    }

    public function maybe_register_blade_namespace() {
        if (!function_exists('view')) return;
        try {
            $factory = view();
            if (is_object($factory) && method_exists($factory, 'addNamespace')) $factory->addNamespace('wpbb', WPBB_PLUGIN_DIR . 'views');
        } catch (Throwable $e) {}
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) return;
        $enabled = (int) wpbb_get_option('local_analytics_enabled', 1);
        $consent = (int) wpbb_get_option('local_analytics_respect_consent', 1);
        $retention = (int) wpbb_get_option('local_analytics_retention_days', 180);
        $retention = max(30, min(730, $retention));
        ?>
        <div class="wrap wpbb-analytics-wrap">
            <div class="wpbb-analytics-head"><div><span class="wpbb-analytics-kicker">WP BB PLATFORM</span><h1><?php esc_html_e('Local analytics settings', 'wp-bbuilder'); ?></h1><p><?php esc_html_e('First-party statistics with no raw IP storage. Country is unknown unless a trusted proxy header is explicitly configured.', 'wp-bbuilder'); ?></p></div><a class="button button-secondary" href="<?php echo esc_url(admin_url('options-general.php?page=wpbb-analytics')); ?>"><?php esc_html_e('Open statistics', 'wp-bbuilder'); ?></a></div>
            <?php if (!empty($_GET['updated'])): ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Analytics settings saved.', 'wp-bbuilder'); ?></p></div><?php endif; ?>
            <div class="wpbb-analytics-panel">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="wpbb_analytics_save_settings">
                    <?php wp_nonce_field('wpbb_analytics_save_settings'); ?>
                    <table class="form-table" role="presentation">
                        <tr><th><?php esc_html_e('Local analytics', 'wp-bbuilder'); ?></th><td><label><input type="checkbox" name="local_analytics_enabled" value="1" <?php checked($enabled, 1); ?>> <?php esc_html_e('Collect privacy-first page-view statistics', 'wp-bbuilder'); ?></label></td></tr>
                        <tr><th><?php esc_html_e('Consent', 'wp-bbuilder'); ?></th><td><label><input type="checkbox" name="local_analytics_respect_consent" value="1" <?php checked($consent, 1); ?>> <?php esc_html_e('Require statistics consent before identifiers are created or visits are sent (WordPress Consent API or BBuilder cookie banner)', 'wp-bbuilder'); ?></label></td></tr>
                        <tr><th><label for="wpbb-retention"><?php esc_html_e('Retention', 'wp-bbuilder'); ?></label></th><td><input id="wpbb-retention" type="number" min="30" max="730" name="local_analytics_retention_days" value="<?php echo esc_attr($retention); ?>"> <?php esc_html_e('days', 'wp-bbuilder'); ?></td></tr>
                    </table>
                    <?php submit_button(__('Save analytics settings', 'wp-bbuilder')); ?>
                </form>
            </div>
            <div class="wpbb-analytics-grid wpbb-analytics-grid--2">
                <div class="wpbb-analytics-panel"><h2><?php esc_html_e('Demo data', 'wp-bbuilder'); ?></h2><p><?php esc_html_e('Generate safe synthetic visits so the dashboard can be evaluated immediately on a local development site.', 'wp-bbuilder'); ?></p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="wpbb_analytics_generate_demo"><?php wp_nonce_field('wpbb_analytics_generate_demo'); ?><?php submit_button(__('Generate analytics demo', 'wp-bbuilder'), 'secondary', 'submit', false); ?></form> <form style="display:inline-block;margin-left:8px" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="wpbb_analytics_clear_demo"><?php wp_nonce_field('wpbb_analytics_clear_demo'); ?><?php submit_button(__('Remove demo rows', 'wp-bbuilder'), 'secondary', 'submit', false); ?></form></div>
                <div class="wpbb-analytics-panel"><h2><?php esc_html_e('Data controls', 'wp-bbuilder'); ?></h2><p><?php esc_html_e('Delete all locally collected analytics rows. This cannot be undone.', 'wp-bbuilder'); ?></p><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Delete all WP BBuilder analytics data?');"><input type="hidden" name="action" value="wpbb_analytics_clear_all"><?php wp_nonce_field('wpbb_analytics_clear_all'); ?><?php submit_button(__('Delete all analytics', 'wp-bbuilder'), 'delete', 'submit', false); ?></form><p style="margin-top:16px"><a href="<?php echo esc_url(admin_url('options-general.php?page=wpbb-settings')); ?>"><?php esc_html_e('Open all BBuilder settings →', 'wp-bbuilder'); ?></a></p></div>
            </div>
        </div>
        <?php
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Not allowed.', 'wp-bbuilder'));
        check_admin_referer('wpbb_analytics_save_settings');
        $opts = get_option('wpbb_settings', []);
        if (!is_array($opts)) $opts = [];
        $opts['local_analytics_enabled'] = !empty($_POST['local_analytics_enabled']) ? 1 : 0;
        $opts['local_analytics_respect_consent'] = !empty($_POST['local_analytics_respect_consent']) ? 1 : 0;
        $opts['local_analytics_retention_days'] = max(30, min(730, absint($_POST['local_analytics_retention_days'] ?? 180)));
        update_option('wpbb_settings', $opts, false);
        wp_safe_redirect(add_query_arg(['page' => 'wpbb-analytics-settings', 'updated' => '1'], admin_url('options-general.php')));
        exit;
    }

    public function generate_demo() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Not allowed.', 'wp-bbuilder'));
        check_admin_referer('wpbb_analytics_generate_demo');
        global $wpdb;
        $table = self::table_name();
        $paths = [
            ['/', 'Home', 'home', 0],
            ['/shop/', 'Shop', 'page', 0],
            ['/product/garden-mower-pro/', 'Garden Mower Pro', 'product', 101],
            ['/product/heat-pump-12kw/', 'Heat Pump 12kW', 'product', 102],
            ['/product/cordless-drill-18v/', 'Cordless Drill 18V', 'product', 103],
            ['/product/greenhouse-premium/', 'Greenhouse Premium', 'product', 104],
            ['/blog/how-to-plan-garden-heating/', 'How to plan efficient garden heating', 'post', 201],
            ['/blog/choosing-a-heat-pump/', 'Choosing the right heat pump', 'post', 202],
            ['/contact/', 'Contact', 'page', 301],
        ];
        $countries = ['GB','GB','GB','LV','DE','IE','NL','FR','SE',''];
        $refs = ['', '', '', 'google.com', 'google.com', 'facebook.com', 'instagram.com', 'bing.com', 'newsletter.local'];
        $devices = ['desktop','desktop','desktop','mobile','mobile','mobile','tablet'];
        $rows = 1100;
        for ($i = 0; $i < $rows; $i++) {
            $p = $paths[wp_rand(0, count($paths)-1)];
            $ago = wp_rand(0, 89 * DAY_IN_SECONDS);
            $visitor_seed = 'demo-visitor-' . wp_rand(1, 260);
            $session_seed = $visitor_seed . '-session-' . wp_rand(1, 5);
            $wpdb->insert($table, [
                'occurred_at' => gmdate('Y-m-d H:i:s', time() - $ago),
                'visitor_hash' => hash('sha256', $visitor_seed),
                'session_hash' => hash('sha256', $session_seed),
                'path' => $p[0], 'page_id' => $p[3], 'object_type' => $p[2], 'title' => $p[1],
                'referrer_host' => $refs[wp_rand(0, count($refs)-1)],
                'country' => $countries[wp_rand(0, count($countries)-1)],
                'device' => $devices[wp_rand(0, count($devices)-1)],
                'is_demo' => 1,
            ]);
        }
        wp_safe_redirect(admin_url('options-general.php?page=wpbb-analytics&range=90&dataset=demo'));
        exit;
    }

    public function clear_demo() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Not allowed.', 'wp-bbuilder'));
        check_admin_referer('wpbb_analytics_clear_demo');
        global $wpdb;
        $wpdb->delete(self::table_name(), ['is_demo' => 1], ['%d']);
        wp_safe_redirect(admin_url('options-general.php?page=wpbb-analytics-settings&demo_removed=1'));
        exit;
    }

    public function clear_all() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Not allowed.', 'wp-bbuilder'));
        check_admin_referer('wpbb_analytics_clear_all');
        global $wpdb;
        $table = self::table_name();
        $wpdb->query("TRUNCATE TABLE {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        wp_safe_redirect(admin_url('options-general.php?page=wpbb-analytics-settings&cleared=1'));
        exit;
    }

    public function cleanup() {
        global $wpdb;
        $days = max(30, min(730, (int) wpbb_get_option('local_analytics_retention_days', 180)));
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare("DELETE FROM " . self::table_name() . " WHERE occurred_at < %s", $cutoff));
    }

    public function admin_bar_menu($wp_admin_bar) {
        if (!is_admin_bar_showing() || !current_user_can('manage_options')) return;

        $cache_key = 'wpbb_analytics_adminbar_v2';
        $r = get_transient($cache_key);
        if (!is_array($r)) {
            $report = $this->get_report(7);
            $r = [
                'views' => (int) ($report['views'] ?? 0),
                'visitors' => (int) ($report['visitors'] ?? 0),
                'sessions' => (int) ($report['sessions'] ?? 0),
                'top' => !empty($report['top_pages'][0]) ? (string) ($report['top_pages'][0]['title'] ?: $report['top_pages'][0]['path']) : '',
            ];
            set_transient($cache_key, $r, MINUTE_IN_SECONDS);
        }

        $title = '<span class="wpbb-ab-analytics-icon dashicons dashicons-chart-area" aria-hidden="true"></span><span class="wpbb-ab-analytics-label">' . esc_html__('Website statistics', 'wp-bbuilder') . '</span><span class="wpbb-ab-analytics-count">' . esc_html(number_format_i18n($r['views'])) . '</span>';
        $wp_admin_bar->add_node([
            'id' => 'wpbb-website-statistics',
            'title' => $title,
            'href' => admin_url('options-general.php?page=wpbb-analytics'),
            'meta' => ['class' => 'wpbb-website-statistics'],
        ]);
        $wp_admin_bar->add_node([
            'id' => 'wpbb-website-statistics-summary',
            'parent' => 'wpbb-website-statistics',
            'title' => '<span class="wpbb-ab-summary-title">' . esc_html__('Last 7 days', 'wp-bbuilder') . '</span><span class="wpbb-ab-summary-grid"><span><b>' . esc_html(number_format_i18n($r['views'])) . '</b>' . esc_html__('Views', 'wp-bbuilder') . '</span><span><b>' . esc_html(number_format_i18n($r['visitors'])) . '</b>' . esc_html__('Visitors', 'wp-bbuilder') . '</span><span><b>' . esc_html(number_format_i18n($r['sessions'])) . '</b>' . esc_html__('Sessions', 'wp-bbuilder') . '</span></span>',
            'href' => admin_url('options-general.php?page=wpbb-analytics'),
            'meta' => ['class' => 'wpbb-analytics-summary-node'],
        ]);
        if (!empty($r['top'])) {
            $wp_admin_bar->add_node([
                'id' => 'wpbb-website-statistics-top',
                'parent' => 'wpbb-website-statistics',
                'title' => esc_html__('Top page:', 'wp-bbuilder') . ' <strong>' . esc_html(wp_html_excerpt($r['top'], 38, '…')) . '</strong>',
                'href' => admin_url('options-general.php?page=wpbb-analytics'),
            ]);
        }
        $wp_admin_bar->add_node([
            'id' => 'wpbb-website-statistics-open',
            'parent' => 'wpbb-website-statistics',
            'title' => esc_html__('Open full statistics →', 'wp-bbuilder'),
            'href' => admin_url('options-general.php?page=wpbb-analytics'),
        ]);
        $wp_admin_bar->add_node([
            'id' => 'wpbb-website-statistics-settings',
            'parent' => 'wpbb-website-statistics',
            'title' => esc_html__('Analytics settings', 'wp-bbuilder'),
            'href' => admin_url('options-general.php?page=wpbb-analytics-settings'),
        ]);
    }

    public function admin_bar_styles() {
        if (!is_admin_bar_showing() || !current_user_can('manage_options')) return;
        ?>
        <style id="wpbb-analytics-adminbar-style">
        #wpadminbar #wp-admin-bar-wpbb-website-statistics>.ab-item{display:flex;align-items:center;gap:5px}
        #wpadminbar .wpbb-ab-analytics-icon{font:normal 18px/1 dashicons!important;width:18px;height:18px;margin:0!important;padding:0!important;top:auto!important}
        #wpadminbar .wpbb-ab-analytics-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:18px;padding:0 6px;border-radius:999px;background:rgba(255,255,255,.14);font-size:10px;font-weight:700;line-height:18px}
        #wpadminbar #wp-admin-bar-wpbb-website-statistics .ab-sub-wrapper{min-width:310px}
        #wpadminbar #wp-admin-bar-wpbb-website-statistics-summary>.ab-item{height:auto!important;white-space:normal!important;padding-top:10px!important;padding-bottom:10px!important}
        #wpadminbar .wpbb-ab-summary-title{display:block;color:#a7aaad;font-size:11px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px}
        #wpadminbar .wpbb-ab-summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
        #wpadminbar .wpbb-ab-summary-grid>span{display:flex;flex-direction:column;min-width:0;padding:7px 8px;border-radius:6px;background:rgba(255,255,255,.06);font-size:10px;color:#c3c4c7}
        #wpadminbar .wpbb-ab-summary-grid b{display:block;color:#fff;font-size:15px;line-height:1.2;margin-bottom:2px}
        </style>
        <?php
    }

    public function dashboard_widget() {
        if (!current_user_can('manage_options')) return;
        wp_add_dashboard_widget('wpbb-analytics-summary', __('WP BBuilder Analytics', 'wp-bbuilder'), [$this, 'render_dashboard_widget']);
    }

    public function render_dashboard_widget() {
        $r = $this->get_report(7);
        echo '<p><strong>'.esc_html__('Local traffic - real visits only','wp-bbuilder').'</strong><br>'.esc_html($r['window']['start'].' to '.$r['window']['end'].' ('.$r['window']['timezone'].')').'</p>';
        echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:4px 0 12px">';
        foreach ([__('Views','wp-bbuilder') => $r['views'], __('Visitors','wp-bbuilder') => $r['visitors'], __('Sessions','wp-bbuilder') => $r['sessions']] as $label => $value) {
            echo '<div style="padding:12px;background:#f6f7f7;border-radius:8px"><strong style="display:block;font-size:20px">' . esc_html(number_format_i18n($value)) . '</strong><span>' . esc_html($label) . '</span></div>';
        }
        echo '</div>';
        if (!empty($r['top_pages'][0])) echo '<p><strong>' . esc_html__('Top page:', 'wp-bbuilder') . '</strong> ' . esc_html($r['top_pages'][0]['title'] ?: $r['top_pages'][0]['path']) . '</p>';
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('options-general.php?page=wpbb-analytics')) . '">' . esc_html__('Open analytics', 'wp-bbuilder') . '</a></p>';
    }
}
