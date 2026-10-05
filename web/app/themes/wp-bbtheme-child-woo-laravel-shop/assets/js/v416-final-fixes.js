/* WP BB Home & Garden v4.0.16 — checkout speed + final layout ownership. */
(function($){
  'use strict';
  var cfg = window.WpbbV416 || {};

  function important(el, name, value){
    if (!el || !el.style) return;
    if (el.style.getPropertyValue(name) === value && el.style.getPropertyPriority(name) === 'important') return;
    el.style.setProperty(name, value, 'important');
  }

  function fixContactLinks(root){
    if (!cfg.isEn || !cfg.contactUrl) return;
    (root || document).querySelectorAll('a[href]').forEach(function(a){
      try{
        var u = new URL(a.href, window.location.href);
        if (/\/(?:lv\/)?kontakti\/?$/i.test(u.pathname) && a.href !== cfg.contactUrl) a.href = cfg.contactUrl;
      }catch(e){}
    });
  }

  function fixUkCheckoutCopy(root){
    if (!cfg.isUk || !cfg.isEn || !document.body.classList.contains('woocommerce-checkout')) return;
    var scope = root || document;
    scope.querySelectorAll('.wpbbshop-delivery-fields h2,.wpbbshop-delivery-fields h3').forEach(function(el){
      if (el.textContent.trim() !== 'Delivery information') el.textContent = 'Delivery information';
    });
    var labels = {
      wpbbshop_delivery_address:'Delivery address',
      wpbbshop_delivery_city:'Town / city',
      wpbbshop_delivery_postcode:'Postcode'
    };
    Object.keys(labels).forEach(function(id){
      var label = document.querySelector('label[for="'+id+'"]');
      if (label && label.textContent.replace(/\s*\*\s*$/,'').trim() !== labels[id]) label.textContent = labels[id];
    });
    var address=document.getElementById('wpbbshop_delivery_address');
    var postcode=document.getElementById('wpbbshop_delivery_postcode');
    if(address && address.placeholder!=='House number and street') address.placeholder='House number and street';
    if(postcode && postcode.placeholder!=='NN1 2PE') postcode.placeholder='NN1 2PE';
    var help=document.querySelector('.wpbbshop-courier-help');
    var helpText='Enter the UK delivery address. The selected carrier and delivery price are shown in the order summary.';
    if(help && help.textContent.trim()!==helpText) help.textContent=helpText;
  }

  function shippingRows(root){
    var scope=root||document;
    return Array.prototype.filter.call(scope.querySelectorAll('tr.shipping,tr.woocommerce-shipping-totals.shipping'),function(row){
      return row.querySelector('#shipping_method,.woocommerce-shipping-methods');
    });
  }

  function fixShippingWidth(root){
    shippingRows(root).forEach(function(row){
      important(row,'display','block');
      important(row,'width','100%');
      important(row,'max-width','none');
      Array.prototype.forEach.call(row.children,function(cell){
        important(cell,'display','block');
        important(cell,'width','100%');
        important(cell,'max-width','none');
        important(cell,'min-width','0');
        important(cell,'box-sizing','border-box');
        important(cell,'text-align','left');
      });
      var list=row.querySelector('#shipping_method,.woocommerce-shipping-methods');
      if(!list) return;
      important(list,'display','grid');
      important(list,'grid-template-columns','1fr');
      important(list,'gap','8px');
      important(list,'width','100%');
      important(list,'min-width','100%');
      important(list,'max-width','none');
      important(list,'margin','0');
      important(list,'padding','0');
      Array.prototype.forEach.call(list.children,function(li){
        important(li,'display','grid');
        important(li,'grid-template-columns','20px minmax(0, 1fr)');
        important(li,'gap','8px');
        important(li,'width','100%');
        important(li,'min-width','100%');
        important(li,'max-width','none');
        important(li,'box-sizing','border-box');
        important(li,'margin','0');
        var label=li.querySelector('label');
        if(label){
          important(label,'display','block');
          important(label,'width','100%');
          important(label,'max-width','none');
          important(label,'min-width','0');
          important(label,'margin','0');
          important(label,'white-space','normal');
        }
      });
    });
  }

  function applyHomeGrid(){
    var section=document.querySelector('.wpbb-v400-departments');
    var grid=section && section.querySelector('.wpbb-v400-dept-grid');
    if(!grid) return;
    var w=window.innerWidth||document.documentElement.clientWidth||1280;
    var cols=w>980?4:(w>560?2:1);
    important(section,'width',cols===4?'min(1480px, calc(100% - 48px))':(cols===2?'calc(100% - 28px)':'calc(100% - 20px)'));
    important(section,'max-width',cols===4?'1480px':'none');
    important(section,'height','auto');
    important(section,'min-height','0');
    important(section,'content-visibility','visible');
    important(section,'contain','none');
    important(grid,'display','grid');
    important(grid,'grid-template-columns','repeat('+cols+', minmax(0, 1fr))');
    important(grid,'grid-auto-flow','row');
    important(grid,'grid-auto-rows','60px');
    important(grid,'gap',cols===1?'5px':'7px');
    important(grid,'width','100%');
    important(grid,'max-width','none');
    important(grid,'height','auto');
    important(grid,'min-height','0');
    Array.prototype.forEach.call(grid.children,function(card){
      if(!card.classList.contains('wpbb-v400-dept')) return;
      important(card,'grid-column','auto');
      important(card,'grid-row','auto');
      important(card,'width','100%');
      important(card,'max-width','none');
      important(card,'min-width','0');
      important(card,'height','60px');
      important(card,'min-height','60px');
      important(card,'margin','0');
      important(card,'transform','none');
      important(card,'order','0');
    });
  }

  function run(root){
    fixContactLinks(root||document);
    fixUkCheckoutCopy(root||document);
    fixShippingWidth(root||document);
    applyHomeGrid();
  }

  function ready(fn){
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',fn,{once:true});
    else fn();
  }

  ready(function(){
    run(document);
    window.setTimeout(function(){run(document);},120);
    window.setTimeout(function(){run(document);},700);
    if($ && $.fn){
      $(document.body).on('updated_checkout updated_cart_totals wc_fragments_refreshed country_to_state_changed',function(){
        window.requestAnimationFrame(function(){run(document);});
      });
    }
  });

  var resizeTimer;
  window.addEventListener('resize',function(){
    window.clearTimeout(resizeTimer);
    resizeTimer=window.setTimeout(applyHomeGrid,160);
  });
})(window.jQuery);
