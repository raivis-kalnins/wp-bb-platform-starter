<?php
/** WPBB installable mobile storefront, 4.0.37. */
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    add_rewrite_rule('^wpbb-app\.webmanifest$', 'index.php?wpbb_app_manifest=1', 'top');
    add_rewrite_rule('^wpbb-service-worker\.js$', 'index.php?wpbb_app_worker=1', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'wpbb_app_manifest';
    $vars[] = 'wpbb_app_worker';
    return $vars;
});

add_action('template_redirect', function () {
    if (get_query_var('wpbb_app_manifest')) {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        $asset = get_stylesheet_directory_uri() . '/assets/img/';
        echo wp_json_encode(array(
            'name' => get_bloginfo('name'),
            'short_name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'id' => home_url('/'),
            'start_url' => home_url('/'),
            'scope' => wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#09283c',
            'icons' => array(
                array('src' => $asset . 'site-icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
                array('src' => $asset . 'site-icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
            ),
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (get_query_var('wpbb_app_worker')) {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Cache-Control: no-store');
        echo "self.addEventListener('install',()=>self.skipWaiting());self.addEventListener('activate',e=>e.waitUntil(self.clients.claim()));";
        exit;
    }
}, 0);

add_action('wp_head', function () {
    echo '<link rel="manifest" href="' . esc_url(home_url('/wpbb-app.webmanifest')) . '">' . "\n";
    echo '<meta name="theme-color" content="#09283c"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url(get_stylesheet_directory_uri() . '/assets/img/apple-touch-icon.png') . '">';
}, 2);

function wpbbshop_mobile_install_buttons_html($is_en = null) {
    if ($is_en === null) {
        $is_en = function_exists('wpbbshop_v305_is_english') && wpbbshop_v305_is_english();
    }
    $apple_label = $is_en ? 'Apple app' : 'Apple lietotne';
    $android_label = $is_en ? 'Android app' : 'Android lietotne';
    $apple_svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.67 12.94c.02 2.3 2.01 3.06 2.03 3.07-.02.05-.31 1.08-1.02 2.14-.61.92-1.25 1.84-2.24 1.86-.97.02-1.28-.58-2.39-.58-1.12 0-1.46.56-2.37.6-.95.04-1.67-.95-2.29-1.86-1.27-1.84-2.24-5.2-.94-7.45.64-1.12 1.79-1.83 3.04-1.85.95-.02 1.84.64 2.39.64.55 0 1.58-.79 2.66-.67.45.02 1.71.18 2.52 1.37-.07.04-1.5.88-1.49 2.73zm-1.87-5.86c.51-.62.85-1.48.76-2.34-.74.03-1.64.49-2.17 1.1-.47.54-.89 1.41-.78 2.24.82.06 1.66-.41 2.19-1z"/></svg>';
    $android_svg = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.6 9.48l1.43-2.48a.75.75 0 10-1.3-.75l-1.47 2.55A9.09 9.09 0 0012 7.75c-1.51 0-2.95.37-4.27 1.05L6.26 6.25a.75.75 0 10-1.3.75l1.43 2.48A7.04 7.04 0 003.5 15.5c0 .41.34.75.75.75h15.5a.75.75 0 00.75-.75 7.04 7.04 0 00-2.9-6.02zM9 13a1 1 0 110-2 1 1 0 010 2zm6 0a1 1 0 110-2 1 1 0 010 2z"/></svg>';
    return '<span class="wpbb-footer-apps" aria-label="' . esc_attr($is_en ? 'Install mobile app' : 'Instalēt mobilo lietotni') . '">' .
        '<button class="wpbb-footer-app" type="button" data-wpbb-install="ios">' . $apple_svg . '<span>' . esc_html($apple_label) . '</span></button>' .
        '<button class="wpbb-footer-app" type="button" data-wpbb-install="android">' . $android_svg . '<span>' . esc_html($android_label) . '</span></button>' .
        '</span>';
}

add_action('wp_footer', function () {
    if (is_admin()) return;
    $is_en = function_exists('wpbbshop_v305_is_english') && wpbbshop_v305_is_english();
    $ios_help = $is_en ? 'On iPhone/iPad: open this site in Safari, tap Share, then Add to Home Screen.' : 'iPhone/iPad: atveriet šo lapu Safari, pieskarieties Kopīgot un izvēlieties “Pievienot sākuma ekrānam”.';
    $android_help = $is_en ? 'On Android: open the browser menu and choose Install app.' : 'Android: pārlūka izvēlnē izvēlieties “Instalēt lietotni”.';
    ?>
    <style id="wpbb-footer-app-style">
      .wpbb-footer-apps{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
      .wpbb-footer-app{appearance:none;display:inline-flex;align-items:center;gap:6px;height:28px;padding:0 9px;border:1px solid rgba(255,255,255,.18);border-radius:7px;background:rgba(255,255,255,.06);color:#d9e6ee;font:600 10px/1 inherit;cursor:pointer;transition:background .16s ease,border-color .16s ease,transform .16s ease}
      .wpbb-footer-app svg{width:13px;height:13px;fill:currentColor;flex:0 0 auto}
      .wpbb-footer-app:hover,.wpbb-footer-app:focus-visible{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.35);color:#fff;outline:none;transform:translateY(-1px)}
      .wpbb-footer-app:active{transform:none}
      .wpbb-app-toast{position:fixed;left:50%;bottom:18px;z-index:999999;max-width:min(420px,calc(100vw - 28px));transform:translate(-50%,18px);opacity:0;pointer-events:none;padding:10px 14px;border-radius:9px;background:#09283c;color:#fff;box-shadow:0 8px 28px rgba(0,0,0,.28);font-size:12px;line-height:1.4;transition:opacity .18s ease,transform .18s ease}
      .wpbb-app-toast.is-visible{opacity:1;transform:translate(-50%,0)}
      @media(max-width:760px){.llg-footer-bottom>.wpbbshop-container{gap:7px!important}.wpbb-footer-apps{order:3;width:100%;justify-content:flex-start}.wpbb-footer-app{height:30px;font-size:10px}}
    </style>
    <div class="wpbb-app-toast" data-wpbb-app-toast role="status" aria-live="polite"></div>
    <script>
    (function(){
      var pending=null, timer=null;
      window.addEventListener('beforeinstallprompt',function(e){e.preventDefault();pending=e;});
      function toast(msg){var el=document.querySelector('[data-wpbb-app-toast]');if(!el)return;el.textContent=msg;el.classList.add('is-visible');clearTimeout(timer);timer=setTimeout(function(){el.classList.remove('is-visible');},5000);}
      document.addEventListener('click',async function(e){
        var b=e.target.closest('[data-wpbb-install]'); if(!b)return;
        if(b.dataset.wpbbInstall==='android' && pending){pending.prompt();try{await pending.userChoice;}catch(err){}pending=null;return;}
        toast(b.dataset.wpbbInstall==='ios' ? <?php echo wp_json_encode($ios_help); ?> : <?php echo wp_json_encode($android_help); ?>);
      });
      if('serviceWorker' in navigator && location.protocol==='https:'){navigator.serviceWorker.register(<?php echo wp_json_encode(home_url('/wpbb-service-worker.js')); ?>).catch(function(){});}
    })();
    </script>
    <?php
}, 1000);
