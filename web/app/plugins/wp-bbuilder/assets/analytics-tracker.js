(function () {
  'use strict';
  var cfg = window.WPBBAnalytics || {}, sent = false, memory = {}, ephemeralConsent = null;
  if (!cfg.endpoint || navigator.doNotTrack === '1' || navigator.globalPrivacyControl === true) return;
  function read(key) { try { return localStorage.getItem(key); } catch (e) { return memory[key] || null; } }
  function write(key, value) { memory[key] = value; try { localStorage.setItem(key, value); } catch (e) {} }
  function clear() { ['wpbb_visitor_v2','wpbb_session_v2','wpbb_visitor_v1','wpbb_session_v1'].forEach(function (k) { delete memory[k]; try { localStorage.removeItem(k); sessionStorage.removeItem(k); } catch (e) {} }); }
  function consent() {
    if (!cfg.respectConsent) return true;
    if (typeof window.wp_has_consent === 'function') return window.wp_has_consent('statistics') === true;
    return !!cfg.builtInConsent && (ephemeralConsent !== null ? ephemeralConsent : read('wpbb_cookie_consent') === 'accepted');
  }
  function random() {
    var a = new Uint32Array(4);
    if (window.crypto && window.crypto.getRandomValues) { window.crypto.getRandomValues(a); return Array.from(a, function(n) { return n.toString(36); }).join('-'); }
    return Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
  }
  function identity(key, age) {
    var now = Date.now(), obj; try { obj = JSON.parse(read(key)); } catch (e) {}
    if (!obj || !obj.id || !Number.isFinite(obj.time) || now - obj.time > age || obj.time > now) obj = {id:random(),time:now};
    if (key === 'wpbb_session_v2') obj.time = now;
    write(key, JSON.stringify(obj)); return obj.id;
  }
  function send() {
    if (sent || document.visibilityState === 'hidden' || !consent()) return;
    var path = window.location.pathname || '/';
    // Exclude account/payment paths that may contain personally identifying or order data.
    if (/\/(wp-admin|wp-json|wp-login\.php|my-account|mans-konts|order-pay|order-received)(\/|$)/i.test(path)) return;
    var body = JSON.stringify({visitor:identity('wpbb_visitor_v2',(cfg.retentionDays || 180)*86400000),session:identity('wpbb_session_v2',1800000),path:path,title:document.title || '',referrer:document.referrer || '',pageId:parseInt(cfg.pageId || 0,10),objectType:cfg.objectType || '',consent:consent() ? 'granted' : 'denied'});
    if (navigator.sendBeacon) { try { if (navigator.sendBeacon(cfg.endpoint,new Blob([body],{type:'application/json'}))) { sent=true; return; } } catch (e) {} }
    if (window.fetch) { sent=true; fetch(cfg.endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:body,credentials:'same-origin',keepalive:true}).catch(function () {}); }
  }
  function onConsent(event) { if (event && event.type === 'wpbb:consent-changed' && event.detail && typeof event.detail.statistics === 'boolean') ephemeralConsent = event.detail.statistics; if (!consent()) { clear(); return; } send(); }
  window.addEventListener('wpbb:consent-changed',onConsent);
  document.addEventListener('wp_listen_for_consent_change',onConsent);
  window.addEventListener('wp_listen_for_consent_change',onConsent);
  window.addEventListener('storage',onConsent);
  document.addEventListener('visibilitychange',send);
  if (document.readyState === 'complete') send(); else window.addEventListener('load',send,{once:true});
  // Activity extends the shared 30-minute session; no extra page views are emitted.
  var last = 0;
  ['pointerdown','keydown','scroll'].forEach(function (event) { window.addEventListener(event,function () { if (sent && consent() && Date.now()-last>60000) { identity('wpbb_session_v2',1800000); last=Date.now(); } },{passive:true}); });
}());
