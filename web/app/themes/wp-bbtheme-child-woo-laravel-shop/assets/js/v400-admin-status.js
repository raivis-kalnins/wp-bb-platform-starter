(function(){
  function yes(v){ return v ? 'ON' : 'OFF'; }
  function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];}); }
  function mount(){
    var root=document.getElementById('wpbbshop-svelte-status');
    if(!root || !window.WPBBShopV400){ return; }
    root.textContent=root.getAttribute('data-loading') || 'Loading status...';
    fetch(WPBBShopV400.restUrl,{headers:{'X-WP-Nonce':WPBBShopV400.nonce},credentials:'same-origin'})
      .then(function(r){ if(!r.ok){ throw new Error('HTTP '+r.status); } return r.json(); })
      .then(function(d){
        var lv=WPBBShopV400.lang==='lv';
        root.innerHTML='<div class="wpbb-v400-svelte-grid">'+
          '<div><strong>'+esc(d.products)+'</strong><span>'+(lv?'Produkti':'Products')+'</span></div>'+
          '<div><strong>'+esc(d.demoProducts)+'</strong><span>'+(lv?'Demo preces':'Demo products')+'</span></div>'+
          '<div><strong>'+yes(d.objectCache)+'</strong><span>Object cache</span></div>'+
          '<div><strong>'+yes(d.hpos)+'</strong><span>Woo HPOS</span></div>'+
          '<div><strong>'+yes(d.acorn)+'</strong><span>Acorn / Blade</span></div>'+
          '<div><strong>'+yes(d.polylang)+'</strong><span>Polylang</span></div>'+
          '<div><strong>'+yes(d.bbuilder)+'</strong><span>WP BBuilder</span></div>'+
          '<div><strong>'+yes(d.wooSupport)+'</strong><span>Woo Support</span></div>'+
        '</div>';
      })
      .catch(function(err){ root.textContent='Status request failed: '+err.message; });
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',mount);}else{mount();}
})();