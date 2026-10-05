/* WP BB Home & Garden v4.0.15 — UK checkout/menu language repair. */
(function(){
  'use strict';
  var cfg = window.WpbbV415 || {};

  function ready(fn){
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, {once:true});
    else fn();
  }

  function fixContactLinks(root){
    if(!cfg.isEn || !cfg.contactUrl) return;
    (root || document).querySelectorAll('a[href]').forEach(function(a){
      try{
        var u = new URL(a.href, window.location.href);
        if(/\/(?:lv\/)?kontakti\/?$/i.test(u.pathname)) a.href = cfg.contactUrl;
      }catch(e){}
    });
  }

  function setText(selector, text){
    var el = document.querySelector(selector);
    if(el) el.textContent = text;
  }

  function fixUkCheckout(){
    if(!cfg.isUk || !cfg.isEn || !document.body.classList.contains('woocommerce-checkout')) return;
    setText('.wpbbshop-delivery-fields > h3', 'Delivery information');
    setText('label[for="wpbbshop_delivery_address"]', 'Delivery address');
    setText('label[for="wpbbshop_delivery_city"]', 'Town / city');
    setText('label[for="wpbbshop_delivery_postcode"]', 'Postcode');
    var address = document.getElementById('wpbbshop_delivery_address');
    var postcode = document.getElementById('wpbbshop_delivery_postcode');
    if(address) address.placeholder = 'House number and street';
    if(postcode) postcode.placeholder = 'NN1 2PE';
    var help = document.querySelector('.wpbbshop-courier-help');
    if(help) help.textContent = 'Enter the UK delivery address. The selected carrier and delivery price are shown in the order summary.';

    document.querySelectorAll('.woocommerce-shipping-destination').forEach(function(el){
      el.innerHTML = el.innerHTML.replace(/Shipping to\s*<strong>Latvia<\/strong>/i, 'Shipping to <strong>United Kingdom</strong>')
                               .replace(/Piegāde uz\s*<strong>Latviju<\/strong>/i, 'Shipping to <strong>United Kingdom</strong>');
    });
  }

  function run(){
    fixContactLinks(document);
    fixUkCheckout();
  }

  ready(function(){
    run();
    if(window.jQuery){
      window.jQuery(document.body).on('updated_checkout updated_cart_totals wc_fragments_refreshed', run);
    }
    if(window.MutationObserver){
      var mo = new MutationObserver(function(){ run(); });
      mo.observe(document.body,{childList:true,subtree:true});
    }
  });
})();
