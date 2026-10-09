<?php
/**
 * Native, read-only GA4 reporting. Google grants and property choices are per administrator.
 * This module never installs a tracking tag or sends store/customer data to Google.
 */
if (!defined('ABSPATH')) exit;
final class WPBB_Google_Analytics {
    const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';
    const APP = 'wpbb_ga_app_v1';
    const META = '_wpbb_ga_connection_v1';
    private static $instance;
    public static function instance() { return self::$instance ?: (self::$instance = new self()); }
    private function __construct() {
        add_action('admin_menu', [$this,'menu']);
        add_action('admin_enqueue_scripts', [$this,'assets']);
        add_action('wp_dashboard_setup', [$this,'widget']);
        foreach (['save_app','connect','callback','select_property','disconnect'] as $action) add_action('admin_post_wpbb_ga_'.$action, [$this,$action]);
        add_action('wp_ajax_wpbb_ga_report', [$this,'ajax_report']);
    }
    public static function url() { return admin_url('options-general.php?page=wpbb-google-analytics'); }
    public static function redirect_uri() { return admin_url('admin-post.php?action=wpbb_ga_callback'); }
    private static function origin() { return untrailingslashit(home_url('/')); }
    public static function seal($value, $context = 'app') {
        if (!function_exists('openssl_encrypt')) return new WP_Error('crypto', 'OpenSSL is required for encrypted Google credentials.');
        try { $iv = random_bytes(12); } catch (Throwable $e) { return new WP_Error('crypto','Secure randomness is unavailable.'); }
        $key = hash('sha256', wp_salt('auth').wp_salt('secure_auth'), true); $tag = '';
        $aad = 'wpbb-ga-v1|'.get_current_blog_id().'|'.self::origin().'|'.$context;
        $cipher = openssl_encrypt(wp_json_encode($value), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        if ($cipher === false || strlen($tag)!==16) return new WP_Error('crypto','Unable to encrypt the Google connection.');
        return 'v1:'.base64_encode($iv.$tag.$cipher);
    }
    public static function unseal($value, $context = 'app') {
        if (!is_string($value) || !str_starts_with($value,'v1:') || !function_exists('openssl_decrypt')) return [];
        $raw = base64_decode(substr($value,3),true); if ($raw===false || strlen($raw)<29) return [];
        $key = hash('sha256',wp_salt('auth').wp_salt('secure_auth'),true);
        $aad = 'wpbb-ga-v1|'.get_current_blog_id().'|'.self::origin().'|'.$context;
        $plain = openssl_decrypt(substr($raw,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),$aad);
        $data = $plain===false ? null : json_decode($plain,true);
        return is_array($data) ? $data : [];
    }
    private function app() { return self::unseal(get_option(self::APP,'')); }
    public function connection() { return self::unseal(get_user_meta(get_current_user_id(), self::META, true), 'user:'.get_current_user_id()); }
    private function save_connection($connection) {
        $sealed = self::seal($connection,'user:'.get_current_user_id());
        if (is_wp_error($sealed)) return $sealed;
        update_user_meta(get_current_user_id(),self::META,$sealed); return true;
    }
    private function guard($action = '') {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Administrator permission is required.','wp-bbuilder'), '', ['response'=>403]);
        nocache_headers();
        if ($action) {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wp_die('POST required.','',['response'=>405]);
            check_admin_referer('wpbb_ga_'.$action);
        }
    }
    private function notice($message, $error = false) {
        set_transient('wpbb_ga_notice_'.get_current_blog_id().'_'.get_current_user_id(), ['message'=>$message,'error'=>$error],MINUTE_IN_SECONDS);
        wp_safe_redirect(self::url()); exit;
    }
    public function menu() { add_options_page('BBuilder Google Analytics','BBuilder Google Analytics','manage_options','wpbb-google-analytics',[$this,'settings']); }
    public function widget() {
        if (current_user_can('manage_options')) wp_add_dashboard_widget('wpbb_ga_overview','Google Analytics 4 - BBuilder', function() { $this->panel(7,true); });
    }
    public function assets($hook) {
        if (!current_user_can('manage_options') || !in_array($hook,['index.php','settings_page_wpbb-google-analytics','settings_page_wpbb-analytics'],true)) return;
        $base = WPBB_PLUGIN_URL.'assets/';
        wp_enqueue_style('wpbb-ga-admin',$base.'google-analytics-admin.css',[],'5.9.0');
        wp_enqueue_script('wpbb-analytics-charts',$base.'analytics-admin.js',[],'5.9.0',true);
        wp_enqueue_script('wpbb-ga-admin',$base.'google-analytics-admin.js',['wpbb-analytics-charts'],'5.9.0',true);
        wp_localize_script('wpbb-ga-admin','wpbbGA',['url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('wpbb_ga_report'),'settings'=>self::url()]);
    }
    private function form_start($action) {
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="wpbb_ga_'.esc_attr($action).'">'; wp_nonce_field('wpbb_ga_'.$action);
    }
    public function settings() {
        $this->guard(); $app=$this->app(); $c=$this->connection();
        $noticeKey='wpbb_ga_notice_'.get_current_blog_id().'_'.get_current_user_id(); $notice=get_transient($noticeKey); delete_transient($noticeKey);
        echo '<div class="wrap wpbb-ga-settings"><h1>BBuilder Google Analytics</h1><p>Read-only GA4 reporting in WordPress. Each administrator connects their own Google account. No passwords are collected and no tracking tag is injected.</p>';
        if ($notice) echo '<div class="notice '.(!empty($notice['error'])?'notice-error':'notice-success').'"><p>'.esc_html($notice['message']).'</p></div>';
        echo '<div class="wpbb-ga-setup"><h2>1. Choose a connection route</h2><p><strong>Native BBuilder dashboard:</strong> configure your own Google OAuth web application once, then use Connect Google account below. Reports appear here and on the WordPress dashboard.</p>';
        $kit=defined('GOOGLESITEKIT_VERSION') ? admin_url('admin.php?page=googlesitekit-dashboard') : admin_url('plugin-install.php?tab=search&s=Site%20Kit%20by%20Google&type=term');
        echo '<p><strong>No custom Google client setup:</strong> Google Site Kit is an optional alternative with its own sign-in and dashboard. Its connection is separate; BBuilder does not copy Site Kit tokens or embed its private API.</p><a class="button" href="'.esc_url($kit).'">Open / find Google Site Kit</a></div>';
        echo '<div class="wpbb-ga-setup"><h2>2. Native OAuth setup (once per site)</h2><p>In Google Cloud, enable <strong>Google Analytics Data API</strong> and <strong>Google Analytics Admin API</strong>. Configure the consent screen and create an OAuth client of type <strong>Web application</strong>. Add this exact authorized redirect URI:</p><p><code class="wpbb-ga-uri">'.esc_html(self::redirect_uri()).'</code></p>';
        echo '<p>Use your HTTPS staging/live domain. This module does not start OAuth on plain HTTP. An external OAuth app in Testing may require test users and periodic reconnection; publishing/verification is managed by Google. Changing the domain or WordPress authentication salts requires reconnecting.</p>';
        $this->form_start('save_app');
        echo '<p><label>Google OAuth client ID<br><input required class="large-text" name="client_id" autocomplete="off" value="'.esc_attr($app['client_id'] ?? '').'" placeholder="...apps.googleusercontent.com"></label></p><p><label>Client secret<br><input class="regular-text" type="password" name="client_secret" value="" autocomplete="new-password"></label> Leave blank to keep the saved secret. Secrets are never displayed again.</p>';
        submit_button('Save Google application','secondary','submit',false); echo '</form></div>';
        echo '<div class="wpbb-ga-setup"><h2>3. Connect your Google account</h2><p>Requested access: read Analytics data only. Use a Google account that can read the chosen GA4 property.</p>';
        $this->form_start('connect'); submit_button($c?'Reconnect Google account':'Connect Google account','primary','submit',false); echo '</form>';
        if ($c) {
            $this->form_start('disconnect'); echo '<p><label><input type="checkbox" name="revoke" value="1"> Also revoke this Google grant (may affect other connections using the same Google OAuth client).</label></p>'; submit_button('Disconnect my account','secondary','submit',false); echo '</form>';
            echo '<h3>4. Choose your GA4 property</h3>';
            $properties=$this->properties();
            $this->form_start('select_property');
            if (!is_wp_error($properties)) {
                echo '<select name="property" aria-label="GA4 property"><option value="">Choose property</option>';
                foreach ($properties['items'] as $p) echo '<option value="'.esc_attr($p['id']).'" '.selected($c['property']['id']??'',$p['id'],false).'>'.esc_html($p['label'].' (#'.$p['id'].')').'</option>';
                echo '</select>';
                if (!empty($properties['partial'])) echo '<p>Large account list: only the first 1,000 accounts were loaded. Enter the property ID below if it is missing.</p>';
            } else echo '<p>'.esc_html($properties->get_error_message()).'</p>';
            echo '<p><label>Or numeric GA4 property ID <input name="property_manual" inputmode="numeric" pattern="[0-9]+" value="" placeholder="123456789"></label> Not a G- measurement ID. Access is checked with Google before saving.</p>';
            submit_button('Use this property','primary','submit',false); echo '</form>';
        }
        echo '</div>'; $this->panel(30,false); echo '</div>';
    }
    public function save_app() {
        $this->guard('save_app'); $old=$this->app();
        $id=trim(sanitize_text_field(wp_unslash($_POST['client_id']??''))); $secret=trim((string)wp_unslash($_POST['client_secret']??''));
        if (!preg_match('/^[a-zA-Z0-9._-]+\.apps\.googleusercontent\.com$/D',$id)) $this->notice('Enter a valid Google OAuth web client ID.',true);
        if ($secret==='' && ($old['client_id']??'')===$id) $secret=$old['client_secret']??'';
        if ($secret==='') $this->notice('The new application needs its client secret.',true);
        $version=(($old['client_id']??'')===$id && ($old['client_secret']??'')===$secret) ? ($old['version']??wp_generate_uuid4()) : wp_generate_uuid4();
        $sealed=self::seal(['client_id'=>$id,'client_secret'=>$secret,'version'=>$version]);
        if (is_wp_error($sealed)) $this->notice($sealed->get_error_message(),true);
        update_option(self::APP,$sealed,false); $this->notice('Google application saved. Connect your account to continue.');
    }
    public function connect() {
        $this->guard('connect'); $app=$this->app();
        if (empty($app['client_secret'])) $this->notice('Save the Google web application credentials first.',true);
        if (wp_parse_url(self::redirect_uri(),PHP_URL_SCHEME)!=='https') $this->notice('Use this connection on your HTTPS staging/live domain, then register its exact callback URL in Google Cloud.',true);
        $state=bin2hex(random_bytes(32));
        set_transient('wpbb_ga_state_'.get_current_blog_id().'_'.get_current_user_id(),['hash'=>hash('sha256',$state),'redirect'=>self::redirect_uri(),'version'=>$app['version']],10*MINUTE_IN_SECONDS);
        $url='https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(['client_id'=>$app['client_id'],'redirect_uri'=>self::redirect_uri(),'response_type'=>'code','scope'=>self::SCOPE,'access_type'=>'offline','prompt'=>'consent select_account','state'=>$state], '', '&', PHP_QUERY_RFC3986);
        // Fixed, trusted Google origin; no user-controlled redirect target.
        wp_redirect($url); exit;
    }
    public static function valid_state($saved,$received,$version) {
        return is_array($saved) && is_string($received) && preg_match('/^[a-f0-9]{64}$/D',$received) && hash_equals((string)($saved['hash']??''),hash('sha256',$received)) && ($saved['redirect']??'')===self::redirect_uri() && ($saved['version']??'')===$version;
    }
    public function callback() {
        $this->guard(); header('Referrer-Policy: no-referrer'); $app=$this->app();
        $key='wpbb_ga_state_'.get_current_blog_id().'_'.get_current_user_id(); $saved=get_transient($key); delete_transient($key);
        $state=wp_unslash($_GET['state']??'');
        if (!self::valid_state($saved,$state,$app['version']??'')) $this->notice('This connection link expired or failed its security check. Start Connect Google account again.',true);
        if (isset($_GET['error'])) $this->notice('Google access was not granted. Your existing connection has not been changed.',true);
        $code=wp_unslash($_GET['code']??''); if (!is_string($code) || $code==='') $this->notice('Google did not return an authorization code.',true);
        $response=$this->token_request(['code'=>$code,'grant_type'=>'authorization_code','redirect_uri'=>self::redirect_uri()]);
        if (is_wp_error($response)) $this->notice($response->get_error_message(),true);
        if (!in_array(self::SCOPE,explode(' ',(string)($response['scope']??'')),true)) $this->notice('Read-only Analytics access was not granted. Reconnect and approve Analytics access.',true);
        // Never reuse the previous refresh token after selecting a different Google account.
        if (empty($response['refresh_token'])) $this->notice('Google returned no offline grant. Remove this app in Google account permissions, then reconnect with consent.',true);
        $result=$this->save_connection(['access_token'=>$response['access_token'],'refresh_token'=>$response['refresh_token'],'expires'=>time()+(int)($response['expires_in']??3600),'app_version'=>$app['version'],'lease'=>wp_generate_uuid4(),'property'=>[]]);
        $this->notice(is_wp_error($result)?$result->get_error_message():'Google connected. Choose your GA4 property.',is_wp_error($result));
    }
    private function token_request($body) {
        $app=$this->app(); if (empty($app['client_secret'])) return new WP_Error('configuration','Save Google application credentials and reconnect.');
        $body['client_id']=$app['client_id']; $body['client_secret']=$app['client_secret'];
        $r=wp_remote_post('https://oauth2.googleapis.com/token',['timeout'=>15,'redirection'=>0,'body'=>$body]);
        if (is_wp_error($r)) return new WP_Error('network','Google token service could not be reached. Check outbound HTTPS on your hosting.');
        $data=json_decode(wp_remote_retrieve_body($r),true);
        if (wp_remote_retrieve_response_code($r)!==200 || !is_array($data) || empty($data['access_token'])) return new WP_Error('authorization','Google authorization failed or expired. Check the client credentials, APIs and consent settings, then reconnect.');
        return $data;
    }
    private function access_token() {
        $c=$this->connection(); $app=$this->app();
        if (!$c || empty($app['version']) || ($c['app_version']??'')!==$app['version']) return new WP_Error('connect','Connect your Google account in BBuilder Google Analytics settings.');
        if ((int)($c['expires']??0)>time()+60 && !empty($c['access_token'])) return $c['access_token'];
        $r=$this->token_request(['grant_type'=>'refresh_token','refresh_token'=>$c['refresh_token']??'']);
        if (is_wp_error($r)) return $r;
        $c['access_token']=$r['access_token']; $c['expires']=time()+(int)($r['expires_in']??3600);
        if (!empty($r['refresh_token'])) $c['refresh_token']=$r['refresh_token'];
        $saved=$this->save_connection($c); return is_wp_error($saved)?$saved:$c['access_token'];
    }
    private function api($url,$body=null) {
        $host=wp_parse_url($url,PHP_URL_HOST);
        if (!in_array($host,['analyticsadmin.googleapis.com','analyticsdata.googleapis.com'],true) || wp_parse_url($url,PHP_URL_SCHEME)!=='https') return new WP_Error('endpoint','Invalid Analytics API endpoint.');
        $token=$this->access_token(); if (is_wp_error($token)) return $token;
        $args=['timeout'=>20,'redirection'=>0,'headers'=>['Authorization'=>'Bearer '.$token,'Accept'=>'application/json']];
        if ($body!==null) { $args['method']='POST'; $args['headers']['Content-Type']='application/json'; $args['body']=wp_json_encode($body); }
        $r=wp_remote_request($url,$args);
        if (is_wp_error($r)) return new WP_Error('network','Google Analytics could not be reached. No substitute figures are displayed.');
        $status=(int)wp_remote_retrieve_response_code($r); $data=json_decode(wp_remote_retrieve_body($r),true);
        if ($status!==200 || !is_array($data)) {
            $message=match($status) {
                401=>'Google authorization expired. Reconnect your Google account.',
                403=>'Google denied access. Check that both Analytics APIs are enabled and that this Google account can read the selected property.',
                404=>'This GA4 property was not found or is not accessible to your Google account.',
                429=>'Google Analytics quota was reached. Wait before refreshing.',
                default=>'Google Analytics returned an unavailable or invalid report (HTTP '.$status.'). Retry later; no figures have been invented.',
            };
            return new WP_Error('google_'.$status,$message);
        }
        return $data;
    }
    private function cache_context() {
        $c=$this->connection(); return [get_current_blog_id(),get_current_user_id(),self::origin(),$c['lease']??'',$c['property']??[]];
    }
    private function properties() {
        $key='wpbb_ga_props_'.md5(wp_json_encode($this->cache_context())); $cache=get_transient($key); if (is_array($cache)) return $cache;
        $items=[]; $token='';
        for ($page=0;$page<5;$page++) {
            $url='https://analyticsadmin.googleapis.com/v1beta/accountSummaries?'.http_build_query(array_filter(['pageSize'=>200,'pageToken'=>$token]), '', '&', PHP_QUERY_RFC3986);
            $r=$this->api($url); if (is_wp_error($r)) return $r;
            foreach (($r['accountSummaries']??[]) as $account) foreach (($account['propertySummaries']??[]) as $property) {
                if (preg_match('~^properties/(\d+)$~D',$property['property']??'',$m)) $items[]=['id'=>$m[1],'label'=>($account['displayName']??'Account').' / '.($property['displayName']??$m[1])];
            }
            $token=$r['nextPageToken']??''; if (!$token) break;
        }
        $result=['items'=>$items,'partial'=>(bool)$token]; set_transient($key,$result,5*MINUTE_IN_SECONDS); return $result;
    }
    public function select_property() {
        $this->guard('select_property');
        $id=trim((string)wp_unslash($_POST['property_manual']??'')); if ($id==='') $id=trim((string)wp_unslash($_POST['property']??''));
        if (!preg_match('/^\d{1,20}$/D',$id)) $this->notice('Use the numeric GA4 property ID, not a G- measurement ID.',true);
        $p=$this->api('https://analyticsadmin.googleapis.com/v1beta/properties/'.$id); if (is_wp_error($p)) $this->notice($p->get_error_message(),true);
        $c=$this->connection();
        if (!$c || ($p['name']??'')!=='properties/'.$id) $this->notice('Google did not confirm access to that property.',true);
        $c['property']=['id'=>$id,'name'=>$p['displayName']??$id,'timezone'=>$p['timeZone']??'','currency'=>$p['currencyCode']??''];
        $saved=$this->save_connection($c); $this->notice(is_wp_error($saved)?$saved->get_error_message():'GA4 property selected. Reports are ready to load.',is_wp_error($saved));
    }
    public function disconnect() {
        $this->guard('disconnect'); $c=$this->connection(); $revoke=!empty($_POST['revoke']); $revoked=false;
        if ($revoke && !empty($c['refresh_token'])) {
            $r=wp_remote_post('https://oauth2.googleapis.com/revoke',['timeout'=>15,'redirection'=>0,'body'=>['token'=>$c['refresh_token']]]);
            $revoked=!is_wp_error($r) && wp_remote_retrieve_response_code($r)===200;
        }
        delete_user_meta(get_current_user_id(),self::META);
        delete_transient('wpbb_ga_state_'.get_current_blog_id().'_'.get_current_user_id());
        $message='Local Google connection removed. Cached reports are no longer accessible through this connection and expire automatically.';
        if ($revoke) $message.=$revoked?' Google confirmed grant revocation.':' Google revocation could not be confirmed; remove access in your Google account permissions.';
        $this->notice($message,$revoke&&!$revoked);
    }
    public static function rows($report) {
        $rows=[]; foreach (($report['rows']??[]) as $row) {
            $item=[];
            foreach (($report['dimensionHeaders']??[]) as $i=>$h) $item[$h['name']]=$row['dimensionValues'][$i]['value']??'';
            foreach (($report['metricHeaders']??[]) as $i=>$h) $item[$h['name']]=(float)($row['metricValues'][$i]['value']??0);
            $rows[]=$item;
        }
        return $rows;
    }
    public function report($window) {
        $c=$this->connection(); $app=$this->app();
        if (!$c || ($c['app_version']??'')!==($app['version']??'')) return new WP_Error('connect','Connect your own Google account first.');
        if (empty($c['property']['id'])) return new WP_Error('property','Choose a GA4 property in BBuilder Google Analytics settings.');
        $context=$this->cache_context(); $cacheKey='wpbb_ga_report_'.md5(wp_json_encode([$context,$window])); $report=get_transient($cacheKey);
        if (is_array($report)) return $report;
        // Short failure backoff protects page reloads from repeatedly exhausting Google quotas.
        $failure=get_transient($cacheKey.'_err'); if (is_string($failure)) return new WP_Error('retry',$failure);
        $range=[['startDate'=>$window['start'],'endDate'=>$window['end']]];
        $metrics=array_map(static fn($name)=>['name'=>$name],['activeUsers','newUsers','sessions','screenPageViews','bounceRate','averageSessionDuration','ecommercePurchases','purchaseRevenue']);
        $requests=[
            ['dateRanges'=>$range,'metrics'=>$metrics,'returnPropertyQuota'=>true],
            ['dateRanges'=>[['startDate'=>$window['previous_start'],'endDate'=>$window['previous_end']]],'metrics'=>$metrics],
            ['dateRanges'=>$range,'dimensions'=>[['name'=>'date']],'metrics'=>[['name'=>'activeUsers'],['name'=>'screenPageViews']],'orderBys'=>[['dimension'=>['dimensionName'=>'date']]],'limit'=>'366'],
            ['dateRanges'=>$range,'dimensions'=>[['name'=>'pagePath']],'metrics'=>[['name'=>'screenPageViews']],'orderBys'=>[['metric'=>['metricName'=>'screenPageViews'],'desc'=>true]],'limit'=>'10'],
            ['dateRanges'=>$range,'dimensions'=>[['name'=>'sessionDefaultChannelGroup']],'metrics'=>[['name'=>'sessions']],'orderBys'=>[['metric'=>['metricName'=>'sessions'],'desc'=>true]],'limit'=>'10'],
        ];
        $base='https://analyticsdata.googleapis.com/v1beta/properties/'.$c['property']['id'];
        $r=$this->api($base.':batchRunReports',['requests'=>$requests]);
        if (is_wp_error($r)) { set_transient($cacheKey.'_err',$r->get_error_message(),MINUTE_IN_SECONDS); return $r; }
        if (count($r['reports']??[])!==5) return new WP_Error('report','Google returned an incomplete report. Try again later.');
        $results=$r['reports']; $warnings=[];
        foreach ($results as $res) {
            if (!empty($res['metadata']['subjectToThresholding'])) $warnings[]='Google applied privacy thresholds; some rows may be withheld.';
            if (!empty($res['metadata']['dataLossFromOtherRow'])) $warnings[]='Some high-cardinality data was combined into an (other) row by Google.';
            if (!empty($res['metadata']['samplingMetadatas'])) $warnings[]='Google reports sampled data for this period.';
        }
        $current=self::rows($results[0]); $previous=self::rows($results[1]);
        $report=['property'=>$c['property'],'window'=>$window,'current'=>$current[0]??[],'previous'=>$previous[0]??[],'daily'=>self::rows($results[2]),'pages'=>self::rows($results[3]),'channels'=>self::rows($results[4]),'warnings'=>array_values(array_unique($warnings)),'generated'=>gmdate('c'),'realtime'=>null,'realtime_error'=>''];
        $rtKey='wpbb_ga_rt_'.md5(wp_json_encode($context)); $realtime=get_transient($rtKey);
        if (!is_array($realtime)) { $realtime=$this->api($base.':runRealtimeReport',['metrics'=>[['name'=>'activeUsers']]]); if (!is_wp_error($realtime)) set_transient($rtKey,$realtime,MINUTE_IN_SECONDS); }
        if (is_wp_error($realtime)) $report['realtime_error']=$realtime->get_error_message();
        else { $rt=self::rows($realtime); $report['realtime']=(int)($rt[0]['activeUsers']??0); }
        // Real-time is a clearly timestamped snapshot too, not a live poll or "online now" claim.
        set_transient($cacheKey,$report,5*MINUTE_IN_SECONDS); return $report;
    }
    public function ajax_report() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message'=>'Administrator permission is required.'],403);
        check_ajax_referer('wpbb_ga_report','nonce'); nocache_headers();
        $window=WPBB_Analytics_Window::make(absint($_POST['days']??30),sanitize_text_field(wp_unslash($_POST['start']??'')),sanitize_text_field(wp_unslash($_POST['end']??'')));
        $report=$this->report($window); if (is_wp_error($report)) wp_send_json_error(['message'=>$report->get_error_message()],400);
        wp_send_json_success($report);
    }
    public function panel($period=30,$compact=false) {
        if (!current_user_can('manage_options')) return;
        $w=is_array($period)?$period:WPBB_Analytics_Window::make($period); $c=$this->connection();
        echo '<section class="wpbb-ga-panel '.($compact?'is-compact':'').'" data-days="'.esc_attr($w['days']).'" data-start="'.esc_attr(is_array($period)?$w['start']:'').'" data-end="'.esc_attr(is_array($period)?$w['end']:'').'">';
        echo '<div class="wpbb-ga-heading"><div><span class="wpbb-ga-eyebrow">GOOGLE ANALYTICS 4</span><h2>Traffic overview</h2></div><a class="button" href="'.esc_url(self::url()).'">Connection settings</a></div>';
        if (empty($c['property']['id'])) { echo '<p>Connect your Google account and select a GA4 property to show real reports here. No demo numbers are displayed.</p></section>'; return; }
        echo '<div class="wpbb-ga-toolbar"><label>Period <select class="wpbb-ga-period">';
        foreach (array_unique([7,28,30,90,$w['days']]) as $d) echo '<option value="'.esc_attr($d).'" '.selected($d,$w['days'],false).'>Last '.esc_html($d).' complete days</option>';
        echo '</select></label><button type="button" class="button wpbb-ga-refresh">Refresh report</button></div><p class="wpbb-ga-status" role="status">Loading Google Analytics...</p><div class="wpbb-ga-content" aria-live="polite"></div><p class="wpbb-ga-note">Read-only data from Google. Cache: up to 5 minutes. This connection adds no tracking tag. Ecommerce figures require an existing GA4 ecommerce implementation.</p></section>';
    }
}
