<?php
/**
 * WP BB Platform visual cPanel installer.
 * Copy this file to PROJECT/web/setup.php for a fresh cPanel deployment.
 * It writes .env and runs packages/wp-bb-platform/cpanel-setup/cpanel-install.sh.
 */
declare(strict_types=1);
session_start();
@set_time_limit(0);
@ini_set('display_errors', '0');

$project = dirname(__DIR__);
$setupDir = $project . '/packages/wp-bb-platform/cpanel-setup';
$keyFile = $project . '/.wpbb-setup-key';
$lockFile = $project . '/.wpbb-setup-complete';
$envFile = $project . '/.env';

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function p(string $k, string $d=''): string { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function eq(string $v): string { return "'" . str_replace(["\\","'","\r","\n"],["\\\\","\\'",'',''],$v) . "'"; }
function secret(): string { return base64_encode(random_bytes(48)); }
function command_ok(): bool { $d=array_map('trim',explode(',',(string)ini_get('disable_functions'))); return function_exists('proc_open') && !in_array('proc_open',$d,true); }
function dbtest(string $host,string $name,string $user,string $pass): array {
    if (!extension_loaded('pdo_mysql')) return [false,'PDO MySQL extension is not enabled.'];
    try { $pdo=new PDO('mysql:host='.$host.';dbname='.$name.';charset=utf8mb4',$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); $pdo->query('SELECT 1'); return [true,'Database connection successful.']; }
    catch(Throwable $e){ return [false,'Database connection failed: '.$e->getMessage()]; }
}
function run_install(string $project,string $script): array {
    if (!command_ok()) return [false,'Browser process execution is disabled. Use cPanel Terminal: bash packages/wp-bb-platform/cpanel-setup/cpanel-install.sh'];
    $desc=[1=>['pipe','w'],2=>['pipe','w']]; $proc=@proc_open('bash '.escapeshellarg($script),$desc,$pipes,$project,['COMPOSER_HOME'=>$project.'/.composer-home']);
    if(!is_resource($proc)) return [false,'Could not start installer.'];
    $out=stream_get_contents($pipes[1]); fclose($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[2]); $code=proc_close($proc);
    return [$code===0,trim($out."\n".$err)];
}

function bootstrap_wordpress(string $project,string $title,string $adminUser,string $adminPass,string $adminEmail): array {
    $wpLoad=$project.'/web/wp/wp-load.php';
    if(!is_file($wpLoad)) return [false,'WordPress core is not installed yet.'];
    try {
        require_once $wpLoad;
        if (!function_exists('is_blog_installed')) require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        if (!is_blog_installed()) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $result=wp_install($title,$adminUser,$adminEmail,true,'',$adminPass,'');
            if (is_wp_error($result)) return [false,$result->get_error_message()];
        }
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        foreach (['updraftplus/updraftplus.php','advanced-custom-fields-pro/acf.php'] as $plugin) {
            if (is_file(WP_PLUGIN_DIR.'/'.$plugin) && !is_plugin_active($plugin)) {
                $r=activate_plugin($plugin,'',false,true);
                if (is_wp_error($r)) error_log('WP BB setup activation warning for '.$plugin.': '.$r->get_error_message());
            }
        }
        flush_rewrite_rules(false);
        return [true,'Temporary WordPress admin is ready. Use it to restore the Updraft database.'];
    } catch (Throwable $e) { return [false,'WordPress bootstrap failed: '.$e->getMessage()]; }
}

if (!is_file($keyFile)) { @file_put_contents($keyFile, bin2hex(random_bytes(24))."\n", LOCK_EX); @chmod($keyFile,0600); }
$expectedKey = is_file($keyFile) ? trim((string)file_get_contents($keyFile)) : '';
$key = (string)($_GET['key'] ?? $_POST['setup_key'] ?? '');
if ($expectedKey === '' || $key === '' || !hash_equals($expectedKey,$key)) {
    http_response_code(403);
    ?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>WP BB Setup</title><style>body{font-family:system-ui;background:#eef3f7;color:#17324a}.b{max-width:680px;margin:10vh auto;background:#fff;padding:30px;border-radius:18px;box-shadow:0 16px 50px #1020301c}code{background:#edf2f5;padding:3px 6px;border-radius:6px}</style></head><body><div class="b"><h1>WP BB setup is protected</h1><p>In cPanel File Manager open <code><?php echo h($keyFile); ?></code>, copy the key, then open:</p><p><code><?php echo h((isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'example.com').'/setup.php?key=YOUR_KEY'); ?></code></p></div></body></html><?php exit;
}
if (is_file($lockFile)) { echo '<!doctype html><meta charset="utf-8"><style>body{font:16px system-ui;max-width:720px;margin:10vh auto;padding:24px}</style><h1>Setup complete</h1><p>The installer is locked. <a href="/">Website</a> · <a href="/wp/wp-admin/">WordPress admin</a></p>'; exit; }

$messages=[];
$defaultUrl='https://'.($_SERVER['HTTP_HOST']??'example.com');
$site=p('site_url',$defaultUrl); $env=p('environment','staging'); $host=p('db_host','localhost'); $name=p('db_name'); $user=p('db_user'); $pass=p('db_password'); $prefix=preg_replace('/[^A-Za-z0-9_]/','',p('db_prefix','wp_')) ?: 'wp_'; $siteTitle=p('site_title','WP BB Project'); $adminUser=p('admin_user','wpbb_admin'); $adminEmail=p('admin_email','admin@'.($_SERVER['HTTP_HOST']??'example.com')); $adminPass=p('admin_password');
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['csrf']??'',(string)($_POST['csrf']??''))) $messages[]=['bad','Security token expired. Reload and retry.'];
  else {
    $action=p('action');
    if ($action==='test') { [$ok,$m]=dbtest($host,$name,$user,$pass); $messages[]=[$ok?'ok':'bad',$m]; }
    if ($action==='save' || $action==='install') {
      if (!preg_match('~^https://[A-Za-z0-9.-]+(?::\d+)?(?:/.*)?$~',$site)) $messages[]=['bad','Site URL must start with https://'];
      elseif ($name===''||$user===''||$pass==='') $messages[]=['bad','Database name, user and password are required.'];
      else { [$ok,$m]=dbtest($host,$name,$user,$pass); if(!$ok)$messages[]=['bad',$m]; else {
        $u=rtrim($site,'/'); $e="WP_ENV=".eq($env)."\nWP_ENVIRONMENT_TYPE=".eq($env)."\nWP_HOME=".eq($u)."\nWP_SITEURL=".eq($u.'/wp')."\n\nDB_NAME=".eq($name)."\nDB_USER=".eq($user)."\nDB_PASSWORD=".eq($pass)."\nDB_HOST=".eq($host)."\nDB_PREFIX=".eq($prefix)."\n\n";
        foreach(['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $s) $e.=$s.'='.eq(secret())."\n";
        $e.="\nDISABLE_WP_CRON=false\nWP_DEBUG_LOG=false\nWPBB_ALLOW_ADMIN_CLI=false\nWPBB_ALLOW_FILE_MODS=true\n";
        if(@file_put_contents($envFile,$e,LOCK_EX)===false)$messages[]=['bad','Could not write .env. Check project-folder permissions.']; else { @chmod($envFile,0600); $messages[]=['ok','.env created and database verified.'];
          if($action==='install'){ [$iok,$out]=run_install($project,$setupDir.'/cpanel-install.sh'); $messages[]=[$iok?'ok':'bad',$iok?'Platform files installed.':'Installation did not finish.']; if($out!=='')$messages[]=['log',$out]; if($iok&&is_file($project.'/web/wp/wp-load.php')&&is_file($project.'/vendor/autoload.php')){ if($adminPass===''){ $messages[]=['bad','Enter a temporary WordPress admin password before the final install step.']; } else { [$wok,$wm]=bootstrap_wordpress($project,$siteTitle,$adminUser,$adminPass,$adminEmail); $messages[]=[$wok?'ok':'bad',$wm]; if($wok){ @file_put_contents($lockFile,date(DATE_ATOM)."\n"); @chmod($lockFile,0600); $messages[]=['ok','Installer locked. Log in to WordPress admin → UpdraftPlus → Rescan local folder → restore DATABASE only.']; } } }}
        }
      }}
    }
  }
}
$_SESSION['csrf']=bin2hex(random_bytes(24));
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>WP BB Platform setup</title><style>*{box-sizing:border-box}body{margin:0;background:#eef3f7;color:#17324a;font-family:system-ui}.w{max-width:860px;margin:32px auto;padding:0 16px}.c{background:#fff;border:1px solid #dce5eb;border-radius:18px;overflow:hidden;box-shadow:0 16px 50px #10203018}.h{padding:26px 28px;background:linear-gradient(135deg,#0c314b,#125273);color:#fff}.h h1{margin:0 0 6px}.b{padding:26px 28px}.g{display:grid;grid-template-columns:1fr 1fr;gap:15px}.f{grid-column:1/-1}label{display:block;font-size:13px;font-weight:700;margin-bottom:5px}input,select{width:100%;padding:11px 12px;border:1px solid #c8d5de;border-radius:9px}.a{display:flex;gap:9px;flex-wrap:wrap;margin-top:20px}.btn{border:0;border-radius:9px;padding:11px 16px;font-weight:700;cursor:pointer}.pri{background:#087849;color:#fff}.sec{background:#e8f1f6;color:#17324a}.m{padding:12px 14px;border-radius:10px;margin:0 0 12px}.ok{background:#e8f7ef;color:#11603e}.bad{background:#fff0ee;color:#8b2e24}.log{background:#102634;color:#e8f3f7;white-space:pre-wrap;max-height:300px;overflow:auto;padding:14px;border-radius:10px;font:12px monospace}.n{margin-top:20px;background:#f4f9fc;border-left:4px solid #1e7da9;padding:14px}@media(max-width:650px){.g{grid-template-columns:1fr}.f{grid-column:auto}}</style></head><body><div class="w"><div class="c"><div class="h"><h1>WP BB Platform setup</h1><p>Reusable browser installer for cPanel.</p></div><div class="b">
<?php foreach($messages as [$t,$m]): if($t==='log'): ?><pre class="log"><?php echo h($m); ?></pre><?php else: ?><div class="m <?php echo h($t); ?>"><?php echo h($m); ?></div><?php endif; endforeach; ?>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']); ?>"><input type="hidden" name="setup_key" value="<?php echo h($key); ?>"><div class="g"><div class="f"><label>Site URL</label><input name="site_url" value="<?php echo h($site); ?>" required></div><div><label>Site title</label><input name="site_title" value="<?php echo h($siteTitle); ?>" required></div><div><label>Temporary admin user</label><input name="admin_user" value="<?php echo h($adminUser); ?>" required></div><div><label>Temporary admin email</label><input type="email" name="admin_email" value="<?php echo h($adminEmail); ?>" required></div><div><label>Temporary admin password</label><input type="password" name="admin_password" value="<?php echo h($adminPass); ?>" placeholder="Use a strong temporary password"></div><div><label>Environment</label><select name="environment"><option value="staging" <?php echo $env==='staging'?'selected':''; ?>>Staging / test</option><option value="production" <?php echo $env==='production'?'selected':''; ?>>Production</option></select></div><div><label>Database host</label><input name="db_host" value="<?php echo h($host); ?>"></div><div><label>Database name</label><input name="db_name" value="<?php echo h($name); ?>" required></div><div><label>Database user</label><input name="db_user" value="<?php echo h($user); ?>" required></div><div><label>Database password</label><input type="password" name="db_password" value="<?php echo h($pass); ?>" required></div><div><label>Table prefix</label><input name="db_prefix" value="<?php echo h($prefix); ?>"></div></div><div class="a"><button class="btn sec" name="action" value="test">1. Test database</button><button class="btn sec" name="action" value="save">2. Save configuration</button><button class="btn pri" name="action" value="install">3. Install platform</button></div></form><div class="n"><strong>Automatic first-install extras:</strong> Composer/WordPress/vendor install, licensed ACF Pro when its private ZIP is present, Updraft database backup copied into the local Updraft folder, and normal wp-admin plugin/theme install/update/remove enabled. After install use UpdraftPlus to restore <strong>database only</strong>.</div></div></div></div></body></html>
