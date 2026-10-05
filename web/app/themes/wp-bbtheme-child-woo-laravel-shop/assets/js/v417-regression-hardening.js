/* WP BB Home & Garden v4.0.17 — small targeted runtime hardening only. */
(function($){
  'use strict';

  function important(el,name,value){
    if(!el || !el.style) return;
    el.style.setProperty(name,value,'important');
  }

  function fixHomeDepartments(){
    var section=document.querySelector('.wpbb-v400-departments');
    var grid=section && section.querySelector('.wpbb-v400-dept-grid');
    if(!grid) return;
    var width=window.innerWidth||document.documentElement.clientWidth||1280;
    var cols=width>900?4:(width>520?2:1);
    var gap=cols===1?5:6;
    var basis=cols===1?'100%':'calc((100% - '+(gap*(cols-1))+'px) / '+cols+')';

    important(section,'width',cols===4?'min(1480px, calc(100% - 48px))':(cols===2?'calc(100% - 28px)':'calc(100% - 20px)'));
    important(section,'max-width',cols===4?'1480px':'none');
    important(section,'height','auto');
    important(section,'min-height','0');
    important(section,'contain','none');
    important(section,'content-visibility','visible');

    important(grid,'display','flex');
    important(grid,'flex-flow','row wrap');
    important(grid,'gap',gap+'px');
    important(grid,'width','100%');
    important(grid,'max-width','none');
    important(grid,'height','auto');
    important(grid,'min-height','0');
    important(grid,'align-content','flex-start');
    important(grid,'justify-content','flex-start');

    Array.prototype.forEach.call(grid.children,function(card){
      if(!card.classList || !card.classList.contains('wpbb-v400-dept')) return;
      important(card,'flex','0 0 '+basis);
      important(card,'width',basis);
      important(card,'max-width',basis);
      important(card,'min-width','0');
      important(card,'height','58px');
      important(card,'min-height','58px');
      important(card,'grid-column','auto');
      important(card,'grid-row','auto');
      important(card,'margin','0');
      important(card,'order','0');
      important(card,'transform','none');
    });
  }

  function shippingRows(root){
    var scope=root||document;
    return Array.prototype.filter.call(scope.querySelectorAll('tr.shipping,tr.woocommerce-shipping-totals.shipping'),function(row){
      return !!row.querySelector('#shipping_method,.woocommerce-shipping-methods');
    });
  }

  function fixShippingWidth(root){
    shippingRows(root).forEach(function(row){
      important(row,'display','grid');
      important(row,'grid-template-columns','minmax(0, 1fr)');
      important(row,'width','100%');
      important(row,'max-width','none');
      important(row,'min-width','0');
      Array.prototype.forEach.call(row.children,function(cell){
        important(cell,'display','block');
        important(cell,'grid-column','1');
        important(cell,'width','100%');
        important(cell,'max-width','none');
        important(cell,'min-width','0');
        important(cell,'box-sizing','border-box');
        important(cell,'text-align','left');
      });

      var list=row.querySelector('#shipping_method,.woocommerce-shipping-methods');
      if(!list) return;
      var parent=list.parentElement;
      while(parent && parent!==row){
        important(parent,'width','100%');
        important(parent,'max-width','none');
        important(parent,'min-width','0');
        important(parent,'box-sizing','border-box');
        parent=parent.parentElement;
      }
      important(list,'display','grid');
      important(list,'grid-template-columns','minmax(0, 1fr)');
      important(list,'gap','8px');
      important(list,'width','100%');
      important(list,'max-width','none');
      important(list,'min-width','100%');
      important(list,'margin','0');
      important(list,'padding','0');
      Array.prototype.forEach.call(list.children,function(li){
        important(li,'display','grid');
        important(li,'grid-template-columns','20px minmax(0, 1fr)');
        important(li,'gap','8px');
        important(li,'width','100%');
        important(li,'max-width','none');
        important(li,'min-width','0');
        important(li,'margin','0');
        important(li,'box-sizing','border-box');
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

  function removeEmptyWooBands(){
    document.querySelectorAll('.woocommerce-notices-wrapper,.woocommerce-form-login-toggle,.woocommerce-form-coupon-toggle').forEach(function(el){
      if((el.textContent||'').trim()==='' && !el.querySelector('a,button,input,form,.woocommerce-message,.woocommerce-error,.woocommerce-info')){
        important(el,'display','none');
        important(el,'height','0');
        important(el,'min-height','0');
        important(el,'margin','0');
        important(el,'padding','0');
        important(el,'border','0');
      }
    });
  }

  function run(root){
    fixHomeDepartments();
    fixShippingWidth(root||document);
    removeEmptyWooBands();
  }

  function ready(fn){
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',fn,{once:true});
    else fn();
  }

  ready(function(){
    run(document);
    if($ && $.fn){
      $(document.body).on('updated_cart_totals updated_wc_div updated_checkout',function(){
        window.requestAnimationFrame(function(){
          fixShippingWidth(document);
          removeEmptyWooBands();
        });
      });
    }
  });

  var resizeTimer;
  window.addEventListener('resize',function(){
    window.clearTimeout(resizeTimer);
    resizeTimer=window.setTimeout(fixHomeDepartments,140);
  });
})(window.jQuery);
