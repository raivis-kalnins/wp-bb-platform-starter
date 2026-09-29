(function(){
  'use strict';
  var cfg=window.WPThemeEssentials||{};
  function qs(s,c){return (c||document).querySelector(s)}
  function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))}
  function cookieSet(name,value,days){var d=new Date();d.setTime(d.getTime()+days*864e5);document.cookie=name+'='+encodeURIComponent(value)+'; expires='+d.toUTCString()+'; path=/; SameSite=Lax'}
  function readConsent(){try{var raw=localStorage.getItem('wpThemeConsent');if(raw)return JSON.parse(raw)}catch(e){} return null}
  function applyConsent(value){
    try{localStorage.setItem('wpThemeConsent',JSON.stringify(value))}catch(e){}
    cookieSet('wp_theme_consent',JSON.stringify(value),180);
    window.wpThemeConsent=value;
    if(typeof window.gtag==='function'){
      window.gtag('consent','update',{analytics_storage:value.analytics?'granted':'denied',ad_storage:value.marketing?'granted':'denied',ad_user_data:value.marketing?'granted':'denied',ad_personalization:value.marketing?'granted':'denied'});
    }
    document.dispatchEvent(new CustomEvent('wpThemeConsentChanged',{detail:value}));
  }
  function initConsent(){
    var banner=qs('[data-cookie-banner]'), dialog=qs('[data-cookie-settings-dialog]');
    if(!banner||!dialog)return;
    var consent=readConsent();
    if(!consent)banner.hidden=false;
    function open(){var current=readConsent()||{analytics:false,marketing:false};qs('[data-cookie-analytics]',dialog).checked=!!current.analytics;qs('[data-cookie-marketing]',dialog).checked=!!current.marketing;dialog.hidden=false;document.documentElement.classList.add('wp-theme-dialog-open')}
    function close(){dialog.hidden=true;document.documentElement.classList.remove('wp-theme-dialog-open')}
    qsa('[data-cookie-accept]').forEach(function(b){b.addEventListener('click',function(){applyConsent({necessary:true,analytics:true,marketing:true,updated:Date.now()});banner.hidden=true})});
    qsa('[data-cookie-reject]').forEach(function(b){b.addEventListener('click',function(){applyConsent({necessary:true,analytics:false,marketing:false,updated:Date.now()});banner.hidden=true})});
    qsa('[data-cookie-settings]').forEach(function(b){b.addEventListener('click',open)});
    qsa('[data-cookie-close]').forEach(function(b){b.addEventListener('click',close)});
    qsa('[data-cookie-save]').forEach(function(b){b.addEventListener('click',function(){applyConsent({necessary:true,analytics:!!qs('[data-cookie-analytics]',dialog).checked,marketing:!!qs('[data-cookie-marketing]',dialog).checked,updated:Date.now()});banner.hidden=true;close()})});
    dialog.addEventListener('click',function(e){if(e.target===dialog)close()});
  }
  function initForms(){
    if(!cfg.privacyUrl)return;
    qsa('form').forEach(function(form){
      if(form.matches('[role="search"], .woocommerce-checkout, .cart, .variations_form, [data-blog-search-form], .wpbb-sector-finder__form'))return;
      if(form.querySelector('.wp-theme-form-privacy'))return;
      if(!form.querySelector('input[type="email"], input[type="tel"], textarea'))return;
      var submit=form.querySelector('button[type="submit"], input[type="submit"]'); if(!submit)return;
      var p=document.createElement('p');p.className='wp-theme-form-privacy';
      var text=(cfg.labels&&cfg.labels.formNotice)||'By submitting this form, you agree that we may use the information provided to respond to your request.';
      p.appendChild(document.createTextNode(text+' '));
      var a=document.createElement('a');a.href=cfg.privacyUrl;a.textContent=(cfg.labels&&cfg.labels.privacy)||'Privacy Policy';p.appendChild(a);
      if(cfg.termsUrl){p.appendChild(document.createTextNode(' · '));var t=document.createElement('a');t.href=cfg.termsUrl;t.textContent=(cfg.labels&&cfg.labels.terms)||'Terms & Conditions';p.appendChild(t)}
      submit.parentNode.insertBefore(p,submit);
    });
  }
  var installPrompt=null;
  window.addEventListener('beforeinstallprompt',function(e){e.preventDefault();installPrompt=e;var b=qs('[data-install-native]');if(b)b.hidden=false});
  function initInstall(){
    if('serviceWorker' in navigator&&cfg.serviceWorker){window.addEventListener('load',function(){navigator.serviceWorker.register(cfg.serviceWorker,{scope:cfg.scope||'/'}).catch(function(){})})}
    var d=qs('[data-install-dialog]'); if(!d)return;
    var mode=d.getAttribute('data-install-mode')||'';
    function setMode(m){mode=m;var a=qs('[data-install-apple]',d),g=qs('[data-install-android]',d);if(a)a.hidden=m!=='apple';if(g)g.hidden=m!=='android';d.hidden=false;document.documentElement.classList.add('wp-theme-dialog-open')}
    qsa('[data-install-link]').forEach(function(a){a.addEventListener('click',function(e){var m=a.getAttribute('data-install-link');if(m){e.preventDefault();setMode(m);history.replaceState({},'',a.href)}})});
    qsa('[data-install-close]',d).forEach(function(b){b.addEventListener('click',function(){d.hidden=true;document.documentElement.classList.remove('wp-theme-dialog-open')})});
    d.addEventListener('click',function(e){if(e.target===d){d.hidden=true;document.documentElement.classList.remove('wp-theme-dialog-open')}});
    var native=qs('[data-install-native]',d);if(native)native.addEventListener('click',async function(){if(!installPrompt)return;installPrompt.prompt();try{await installPrompt.userChoice}catch(e){} installPrompt=null;native.hidden=true});
    if(mode)setMode(mode);
  }
  function init(){initConsent();initForms();initInstall()}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
