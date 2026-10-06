(function($){
  'use strict';

  function q(sel,root){return (root||document).querySelector(sel);} function qa(sel,root){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}

  /* Quote drawer ------------------------------------------------------ */
  function drawer(){return q('[data-quote-drawer]');}
  function overlay(){return q('[data-quote-drawer-overlay]');}
  function openDrawer(){var d=drawer(),o=overlay();if(!d)return;d.classList.add('is-open');d.setAttribute('aria-hidden','false');if(o)o.hidden=false;document.body.classList.add('wpbb-v424-drawer-open');}
  function closeDrawer(){var d=drawer(),o=overlay();if(!d)return;d.classList.remove('is-open');d.setAttribute('aria-hidden','true');if(o)o.hidden=true;document.body.classList.remove('wpbb-v424-drawer-open');}
  function bindDrawerClose(){qa('[data-quote-drawer-close]').forEach(function(b){if(b.dataset.bound)return;b.dataset.bound='1';b.addEventListener('click',closeDrawer);});}
  function refreshDrawer(openAfter){
    if(!window.WPBBShopV424||!WPBBShopV424.ajaxUrl){if(openAfter)openDrawer();return;}
    var body=new URLSearchParams();body.set('action','wpbbshop_v424_quote_drawer');body.set('nonce',WPBBShopV424.quoteNonce||'');
    fetch(WPBBShopV424.ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString()}).then(function(r){return r.json();}).then(function(res){
      if(res&&res.success&&res.data&&typeof res.data.html==='string'){
        var d=drawer();if(d){d.innerHTML=res.data.html;bindDrawerClose();}
        qa('[data-quote-count]').forEach(function(el){el.textContent=String(res.data.count||0);});
      }
      if(openAfter)openDrawer();
    }).catch(function(){if(openAfter)openDrawer();});
  }
  document.addEventListener('click',function(e){
    var floating=e.target.closest('[data-quote-floating]');
    if(floating){e.preventDefault();refreshDrawer(true);return;}
    if(e.target.closest('[data-quote-drawer-overlay]'))closeDrawer();
  });
  document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeDrawer();closeLightbox();}});
  if($){$(document.body).on('wp_theme_quote_updated',function(){refreshDrawer(true);});}
  bindDrawerClose();

  /* Blog link fallback for installations without a classic primary menu. */
  function ensureBlogLinks(){
    var url=window.WPBBShopV424&&WPBBShopV424.blogUrl; if(!url)return;
    qa('.llg-main-nav .wpbbshop-menu,.llg-mobile-primary .wpbbshop-menu').forEach(function(menu){
      if(menu.querySelector('a[href*="/blog/"],a[href*="/blogs/"]'))return;
      var li=document.createElement('li');li.className='menu-item menu-item-wpbb-blog';var a=document.createElement('a');a.href=url;a.textContent=document.documentElement.lang&&document.documentElement.lang.toLowerCase().indexOf('lv')===0?'Blogs':'Blog';li.appendChild(a);menu.appendChild(li);
    });
  }

  /* Article gallery -------------------------------------------------- */
  var modalItems=[],modalIndex=0;
  function openLightbox(items,index){
    var box=q('[data-wpbb-lightbox]');if(!box||!items.length)return;modalItems=items;modalIndex=index||0;box.hidden=false;document.body.style.overflow='hidden';updateLightbox();
  }
  function updateLightbox(){var box=q('[data-wpbb-lightbox]');if(!box||!modalItems.length)return;var item=modalItems[modalIndex];var img=q('[data-lightbox-image]',box),cap=q('[data-lightbox-caption]',box);if(img){img.src=item.url;img.alt=item.caption||'';}if(cap)cap.textContent=item.caption||'';}
  function closeLightbox(){var box=q('[data-wpbb-lightbox]');if(!box||box.hidden)return;box.hidden=true;document.body.style.overflow='';}
  function stepLightbox(dir){if(!modalItems.length)return;modalIndex=(modalIndex+dir+modalItems.length)%modalItems.length;updateLightbox();}
  function initGallery(g){
    if(g.dataset.ready==='1')return;g.dataset.ready='1';var track=q('[data-gallery-track]',g);var slides=qa('[data-gallery-slide]',g);var thumbs=qa('[data-gallery-thumb]',g);var idx=0;var counter=q('[data-gallery-index]',g);
    var items=slides.map(function(s){return{url:s.getAttribute('data-gallery-full')||q('img',s).src,caption:s.getAttribute('data-gallery-caption')||''};});
    function set(i){if(!slides.length)return;idx=(i+slides.length)%slides.length;if(track)track.style.transform='translateX(-'+(idx*100)+'%)';slides.forEach(function(s,j){s.classList.toggle('is-active',j===idx);});thumbs.forEach(function(t,j){t.classList.toggle('is-active',j===idx);});if(counter)counter.textContent=String(idx+1);if(thumbs[idx])thumbs[idx].scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'});}
    var prev=q('[data-gallery-prev]',g),next=q('[data-gallery-next]',g);if(prev)prev.addEventListener('click',function(){set(idx-1);});if(next)next.addEventListener('click',function(){set(idx+1);});
    thumbs.forEach(function(t){t.addEventListener('click',function(){set(parseInt(t.getAttribute('data-gallery-thumb'),10)||0);});});
    slides.forEach(function(s,j){s.addEventListener('click',function(){openLightbox(items,j);});});
    set(0);
  }
  qa('[data-wpbb-gallery]').forEach(initGallery);
  document.addEventListener('click',function(e){if(e.target.closest('[data-lightbox-close]'))closeLightbox();else if(e.target.closest('[data-lightbox-prev]'))stepLightbox(-1);else if(e.target.closest('[data-lightbox-next]'))stepLightbox(1);else{var box=e.target.closest('[data-wpbb-lightbox]');if(box&&e.target===box)closeLightbox();}});
  document.addEventListener('keydown',function(e){var box=q('[data-wpbb-lightbox]');if(!box||box.hidden)return;if(e.key==='ArrowLeft')stepLightbox(-1);if(e.key==='ArrowRight')stepLightbox(1);});

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ensureBlogLinks,{once:true});else ensureBlogLinks();
})(window.jQuery);
