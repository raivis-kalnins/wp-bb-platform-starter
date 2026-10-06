(function(){
  'use strict';
  function q(sel,root){return (root||document).querySelector(sel);}
  function qa(sel,root){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
  function countValue(){var el=q('[data-quote-count]');return el?parseInt(el.textContent||'0',10)||0:0;}
  function addDrawerClear(){
    var foot=q('.wpbb-v424-quote-drawer-foot');
    if(!foot || q('[data-wpbb-quote-clear]',foot) || countValue()<1) return;
    var b=document.createElement('button');
    b.type='button'; b.className='wpbb-v424-quote-clear'; b.setAttribute('data-wpbb-quote-clear','');
    b.textContent=(window.WPBBShopV425&&WPBBShopV425.clearLabel)||'Clear quote';
    foot.appendChild(b);
  }
  function clearQuote(button){
    if(!window.WPBBShopV425 || !WPBBShopV425.ajaxUrl) return;
    var old=button.textContent; button.disabled=true; button.textContent=WPBBShopV425.clearingLabel||'Clearing…';
    var data=new URLSearchParams(); data.set('action','wpbbshop_v425_clear_quote'); data.set('nonce',WPBBShopV425.clearNonce||'');
    fetch(WPBBShopV425.ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},credentials:'same-origin',body:data.toString()})
      .then(function(r){return r.json();}).then(function(res){
        if(!res || !res.success) throw new Error('clear failed');
        qa('[data-quote-count]').forEach(function(el){el.textContent='0';});
        var drawer=q('[data-quote-drawer]');
        if(drawer && res.data && res.data.html){drawer.innerHTML=res.data.html;}
        if(q('.wp-theme-quote-page')){window.location.reload();return;}
        addDrawerClear();
      }).catch(function(){button.disabled=false;button.textContent=old;});
  }
  document.addEventListener('click',function(e){var b=e.target.closest('[data-wpbb-quote-clear]');if(!b)return;e.preventDefault();clearQuote(b);});
  document.addEventListener('DOMContentLoaded',function(){addDrawerClear();window.setTimeout(addDrawerClear,250);});
  if(window.jQuery){jQuery(document.body).on('wp_theme_quote_updated',function(){window.setTimeout(addDrawerClear,220);});}
}());
