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
    const DB_VERSION = '1.0.0';
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
            KEY is_demo (is_demo)
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
        if (is_user_logged_in() && current_user_can('edit_posts')) return;
        if ($this->must_have_consent() && function_exists('wp_has_consent') && !wp_has_consent('statistics')) return;

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
        ]);
    }

    private function must_have_consent() {
        return (bool) wpbb_get_option('local_analytics_respect_consent', 1);
    }

    private function is_bot($ua) {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|monitor|uptime|headless|phantom|selenium|curl|wget/i', (string) $ua);
    }

    private function country_from_headers() {
        $keys = ['HTTP_CF_IPCOUNTRY','HTTP_X_COUNTRY_CODE','HTTP_X_APPENGINE_COUNTRY','GEOIP_COUNTRY_CODE','HTTP_CLOUDFRONT_VIEWER_COUNTRY'];
        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) continue;
            $country = strtoupper(sanitize_text_field(wp_unslash($_SERVER[$key])));
            if (preg_match('/^[A-Z]{2}$/', $country) && $country !== 'XX') return $country;
        }
        return '';
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
        $visitor = isset($data['visitor']) ? sanitize_text_field($data['visitor']) : '';
        $session = isset($data['session']) ? sanitize_text_field($data['session']) : '';
        if ($visitor === '' || $session === '' || strlen($visitor) > 128 || strlen($session) > 128) {
            return new WP_REST_Response(['stored' => false], 200);
        }

        $path = $this->clean_path($data['path'] ?? '/');
        if (strpos($path, '/wp-admin') === 0 || strpos($path, '/wp-json') === 0 || strpos($path, '/wp-login') === 0) {
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
            'SELECT id FROM ' . self::table_name() . ' WHERE session_hash=%s AND path=%s AND occurred_at >= %s LIMIT 1',
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
        add_menu_page(
            __('WP BBuilder Analytics', 'wp-bbuilder'),
            __('WP BBuilder', 'wp-bbuilder'),
            'manage_options',
            'wpbb-analytics',
            [$this, 'render_dashboard'],
            'dashicons-chart-area',
            58
        );
        add_submenu_page('wpbb-analytics', __('Statistics', 'wp-bbuilder'), __('Statistics', 'wp-bbuilder'), 'manage_options', 'wpbb-analytics', [$this, 'render_dashboard']);
        add_submenu_page('wpbb-analytics', __('Analytics settings', 'wp-bbuilder'), __('Analytics settings', 'wp-bbuilder'), 'manage_options', 'wpbb-analytics-settings', [$this, 'render_settings']);
    }

    public function admin_assets($hook) {
        if (strpos((string) $hook, 'wpbb-analytics') === false) return;
        wp_enqueue_style('wpbb-analytics-admin', WPBB_PLUGIN_URL . 'assets/analytics-admin.css', [], WPBB_VERSION);
        wp_enqueue_script('google-charts', 'https://www.gstatic.com/charts/loader.js', [], null, true);
        wp_enqueue_script('wpbb-analytics-admin', WPBB_PLUGIN_URL . 'assets/analytics-admin.js', ['google-charts'], WPBB_VERSION, true);
    }

    private function range_days() {
        $days = isset($_GET['range']) ? absint($_GET['range']) : 30;
        return in_array($days, [7,30,90,180,365], true) ? $days : 30;
    }

    private function where_range($days) {
        return gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
    }

    public function get_report($days = 30) {
        global $wpdb;
        $table = self::table_name();
        $since = $this->where_range($days);
        $prev_since = gmdate('Y-m-d H:i:s', time() - ($days * 2 * DAY_IN_SECONDS));
        $now = current_time('mysql', true);

        $summary = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors, COUNT(DISTINCT session_hash) sessions FROM {$table} WHERE occurred_at >= %s AND occurred_at <= %s",
            $since, $now
        ), ARRAY_A);
        $prev = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE occurred_at >= %s AND occurred_at < %s",
            $prev_since, $since
        ), ARRAY_A);
        $views = (int) ($summary['views'] ?? 0);
        $visitors = (int) ($summary['visitors'] ?? 0);
        $sessions = (int) ($summary['sessions'] ?? 0);
        $prev_views = (int) ($prev['views'] ?? 0);
        $prev_visitors = (int) ($prev['visitors'] ?? 0);

        $daily = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(occurred_at) day, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE occurred_at >= %s GROUP BY DATE(occurred_at) ORDER BY day ASC",
            $since
        ), ARRAY_A);
        $top_pages = $wpdb->get_results($wpdb->prepare(
            "SELECT path, MAX(title) title, MAX(object_type) object_type, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE occurred_at >= %s GROUP BY path ORDER BY views DESC LIMIT 20",
            $since
        ), ARRAY_A);
        $top_products = $wpdb->get_results($wpdb->prepare(
            "SELECT page_id, path, MAX(title) title, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE occurred_at >= %s AND object_type='product' GROUP BY page_id, path ORDER BY views DESC LIMIT 12",
            $since
        ), ARRAY_A);
        $countries = $wpdb->get_results($wpdb->prepare(
            "SELECT IF(country='', 'Unknown', country) country, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE occurred_at >= %s GROUP BY country ORDER BY views DESC LIMIT 15",
            $since
        ), ARRAY_A);
        $referrers = $wpdb->get_results($wpdb->prepare(
            "SELECT IF(referrer_host='', 'Direct / internal', referrer_host) source, COUNT(*) views, COUNT(DISTINCT visitor_hash) visitors FROM {$table} WHERE occurred_at >= %s GROUP BY referrer_host ORDER BY views DESC LIMIT 15",
            $since
        ), ARRAY_A);
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT IF(device='', 'unknown', device) device, COUNT(*) views FROM {$table} WHERE occurred_at >= %s GROUP BY device ORDER BY views DESC",
            $since
        ), ARRAY_A);

        $trend = function($now_value, $prev_value) {
            if ($prev_value <= 0) return $now_value > 0 ? 100.0 : 0.0;
            return round((($now_value - $prev_value) / $prev_value) * 100, 1);
        };

        return [
            'days' => $days,
            'views' => $views,
            'visitors' => $visitors,
            'sessions' => $sessions,
            'pages_per_session' => $sessions ? round($views / $sessions, 2) : 0,
            'view_growth' => $trend($views, $prev_views),
            'visitor_growth' => $trend($visitors, $prev_visitors),
            'daily' => $daily,
            'top_pages' => $top_pages,
            'top_products' => $top_products,
            'countries' => $countries,
            'referrers' => $referrers,
            'devices' => $devices,
            'generated' => current_time('mysql'),
        ];
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) return;
        $data = $this->get_report($this->range_days());
        $data['settings_url'] = admin_url('admin.php?page=wpbb-analytics-settings');
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
            <div class="wpbb-analytics-head"><div><span class="wpbb-analytics-kicker">WP BB PLATFORM</span><h1><?php esc_html_e('Local analytics settings', 'wp-bbuilder'); ?></h1><p><?php esc_html_e('First-party statistics with no raw IP storage. Country data is used only when your host/CDN already supplies a country header.', 'wp-bbuilder'); ?></p></div><a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=wpbb-analytics')); ?>"><?php esc_html_e('Open statistics', 'wp-bbuilder'); ?></a></div>
            <?php if (!empty($_GET['updated'])): ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e('Analytics settings saved.', 'wp-bbuilder'); ?></p></div><?php endif; ?>
            <div class="wpbb-analytics-panel">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="wpbb_analytics_save_settings">
                    <?php wp_nonce_field('wpbb_analytics_save_settings'); ?>
                    <table class="form-table" role="presentation">
                        <tr><th><?php esc_html_e('Local analytics', 'wp-bbuilder'); ?></th><td><label><input type="checkbox" name="local_analytics_enabled" value="1" <?php checked($enabled, 1); ?>> <?php esc_html_e('Collect privacy-first page-view statistics', 'wp-bbuilder'); ?></label></td></tr>
                        <tr><th><?php esc_html_e('Consent', 'wp-bbuilder'); ?></th><td><label><input type="checkbox" name="local_analytics_respect_consent" value="1" <?php checked($consent, 1); ?>> <?php esc_html_e('Respect the WordPress Consent API statistics category when available', 'wp-bbuilder'); ?></label></td></tr>
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
        wp_safe_redirect(add_query_arg(['page' => 'wpbb-analytics-settings', 'updated' => '1'], admin_url('admin.php')));
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
        wp_safe_redirect(admin_url('admin.php?page=wpbb-analytics&range=90&demo=1'));
        exit;
    }

    public function clear_demo() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Not allowed.', 'wp-bbuilder'));
        check_admin_referer('wpbb_analytics_clear_demo');
        global $wpdb;
        $wpdb->delete(self::table_name(), ['is_demo' => 1], ['%d']);
        wp_safe_redirect(admin_url('admin.php?page=wpbb-analytics-settings&demo_removed=1'));
        exit;
    }

    public function clear_all() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Not allowed.', 'wp-bbuilder'));
        check_admin_referer('wpbb_analytics_clear_all');
        global $wpdb;
        $table = self::table_name();
        $wpdb->query("TRUNCATE TABLE {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        wp_safe_redirect(admin_url('admin.php?page=wpbb-analytics-settings&cleared=1'));
        exit;
    }

    public function cleanup() {
        global $wpdb;
        $days = max(30, min(730, (int) wpbb_get_option('local_analytics_retention_days', 180)));
        $cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare("DELETE FROM " . self::table_name() . " WHERE occurred_at < %s", $cutoff));
    }

    public function dashboard_widget() {
        if (!current_user_can('manage_options')) return;
        wp_add_dashboard_widget('wpbb-analytics-summary', __('WP BBuilder Analytics', 'wp-bbuilder'), [$this, 'render_dashboard_widget']);
    }

    public function render_dashboard_widget() {
        $r = $this->get_report(7);
        echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:4px 0 12px">';
        foreach ([__('Views','wp-bbuilder') => $r['views'], __('Visitors','wp-bbuilder') => $r['visitors'], __('Sessions','wp-bbuilder') => $r['sessions']] as $label => $value) {
            echo '<div style="padding:12px;background:#f6f7f7;border-radius:8px"><strong style="display:block;font-size:20px">' . esc_html(number_format_i18n($value)) . '</strong><span>' . esc_html($label) . '</span></div>';
        }
        echo '</div>';
        if (!empty($r['top_pages'][0])) echo '<p><strong>' . esc_html__('Top page:', 'wp-bbuilder') . '</strong> ' . esc_html($r['top_pages'][0]['title'] ?: $r['top_pages'][0]['path']) . '</p>';
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=wpbb-analytics')) . '">' . esc_html__('Open analytics', 'wp-bbuilder') . '</a></p>';
    }
}
