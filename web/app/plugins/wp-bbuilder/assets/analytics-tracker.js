(function () {
  'use strict';
  var cfg = window.WPBBAnalytics || {};
  if (!cfg.endpoint) return;

  function token(storage, key) {
    try {
      var v = storage.getItem(key);
      if (v) return v;
      if (window.crypto && crypto.getRandomValues) {
        var a = new Uint32Array(4); crypto.getRandomValues(a); v = Array.prototype.map.call(a, function (n) { return n.toString(36); }).join('-');
      } else { v = String(Date.now()) + '-' + String(Math.random()).slice(2); }
      storage.setItem(key, v); return v;
    } catch (e) { return String(Date.now()) + '-' + String(Math.random()).slice(2); }
  }

  var payload = {
    visitor: token(window.localStorage, 'wpbb_visitor_v1'),
    session: token(window.sessionStorage, 'wpbb_session_v1'),
    path: window.location.pathname || '/',
    title: document.title || '',
    referrer: document.referrer || '',
    pageId: parseInt(cfg.pageId || 0, 10),
    objectType: cfg.objectType || ''
  };

  function send() {
    var body = JSON.stringify(payload);
    if (navigator.sendBeacon) {
      try {
        var blob = new Blob([body], { type: 'application/json' });
        if (navigator.sendBeacon(cfg.endpoint, blob)) return;
      } catch (e) {}
    }
    if (window.fetch) fetch(cfg.endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, credentials: 'same-origin', keepalive: true }).catch(function () {});
  }

  if (document.readyState === 'complete') send();
  else window.addEventListener('load', send, { once: true });
}());
