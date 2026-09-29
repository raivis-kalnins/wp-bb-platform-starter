(function(){
  'use strict';

  var lastFocus=null;
  var dynamicBox=null;
  var pointerStart=null;

  function intValue(value){var n=parseInt(value||'0',10);return Number.isFinite(n)?n:0;}
  function indexOfThumb(thumb){return intValue(thumb&&thumb.getAttribute('data-index'));}
  function safeItems(root){
    if(!root)return[];
    var raw=root.getAttribute('data-gallery-items');
    if(raw){
      try{
        var parsed=JSON.parse(raw);
        if(Array.isArray(parsed))return parsed.filter(function(item){return item&&item.full;});
      }catch(error){}
    }
    var items=[];
    root.querySelectorAll('[data-item-gallery-thumb]').forEach(function(thumb){
      items.push({
        url:thumb.getAttribute('data-full')||'',
        full:thumb.getAttribute('data-lightbox-full')||thumb.getAttribute('data-full')||'',
        thumb:(thumb.querySelector('img')||{}).src||'',
        alt:thumb.getAttribute('data-alt')||'',
        caption:thumb.getAttribute('data-caption')||''
      });
    });
    if(!items.length){
      var main=root.querySelector('[data-item-gallery-main]');
      if(main)items.push({url:main.currentSrc||main.src,full:main.currentSrc||main.src,thumb:main.currentSrc||main.src,alt:main.alt||'',caption:''});
    }
    return items.filter(function(item){return item.full;});
  }

  function activateInline(thumb){
    var root=thumb.closest('[data-item-gallery-card],[data-item-gallery-single]');
    if(!root)return;
    var main=root.querySelector('[data-item-gallery-main]');
    if(!main)return;
    var src=thumb.getAttribute('data-full');
    if(src&&main.getAttribute('src')!==src){
      main.classList.add('is-changing');
      var done=function(){main.classList.remove('is-changing');main.removeEventListener('load',done);};
      main.addEventListener('load',done);
      main.setAttribute('src',src);main.removeAttribute('srcset');main.removeAttribute('sizes');
    }
    var alt=thumb.getAttribute('data-alt');if(alt!==null)main.setAttribute('alt',alt);
    main.setAttribute('data-index',String(indexOfThumb(thumb)));
    root.querySelectorAll('[data-item-gallery-thumb]').forEach(function(el){
      var active=el===thumb;el.classList.toggle('is-active',active);el.setAttribute('aria-current',active?'true':'false');
    });
  }

  function dynamicMarkup(){
    var wrap=document.createElement('div');
    wrap.className='wp-theme-item-lightbox';
    wrap.setAttribute('data-item-gallery-lightbox','');
    wrap.setAttribute('data-item-gallery-dynamic','');
    wrap.setAttribute('role','dialog');
    wrap.setAttribute('aria-modal','true');
    wrap.setAttribute('aria-label','Image gallery');
    wrap.setAttribute('aria-hidden','true');
    wrap.hidden=true;
    wrap.innerHTML='\
      <button type="button" class="wp-theme-item-lightbox__backdrop" data-item-gallery-close tabindex="-1" aria-label="Close gallery"></button>\
      <div class="wp-theme-item-lightbox__dialog" role="document">\
        <div class="wp-theme-item-lightbox__topbar">\
          <span class="wp-theme-item-lightbox__counter" data-item-gallery-counter></span>\
          <button type="button" class="wp-theme-item-lightbox__close" data-item-gallery-close aria-label="Close gallery"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></button>\
        </div>\
        <div class="wp-theme-item-lightbox__viewer" data-item-gallery-viewer>\
          <button type="button" class="wp-theme-item-lightbox__nav wp-theme-item-lightbox__nav--prev" data-item-gallery-prev aria-label="Previous image"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="m14 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></button>\
          <figure class="wp-theme-item-lightbox__figure"><img src="" alt="" data-item-gallery-lightbox-image><figcaption data-item-gallery-caption hidden></figcaption></figure>\
          <button type="button" class="wp-theme-item-lightbox__nav wp-theme-item-lightbox__nav--next" data-item-gallery-next aria-label="Next image"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="m10 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></button>\
        </div>\
        <div class="wp-theme-item-lightbox__thumbs" role="list" aria-label="Gallery thumbnails"></div>\
      </div>';
    document.body.appendChild(wrap);
    return wrap;
  }

  function ensureDynamicBox(){if(!dynamicBox||!document.body.contains(dynamicBox))dynamicBox=dynamicMarkup();return dynamicBox;}
  function boxItems(box){
    if(Array.isArray(box._wpThemeGalleryItems))return box._wpThemeGalleryItems;
    return Array.prototype.slice.call(box.querySelectorAll('[data-item-gallery-lightbox-thumb]')).map(function(el){return{
      full:el.getAttribute('data-full')||'',thumb:(el.querySelector('img')||{}).src||'',alt:el.getAttribute('data-alt')||'',caption:el.getAttribute('data-caption')||''
    };});
  }
  function populateDynamic(box,items){
    box._wpThemeGalleryItems=items;
    var rail=box.querySelector('.wp-theme-item-lightbox__thumbs');if(!rail)return;
    rail.textContent='';
    items.forEach(function(item,index){
      var button=document.createElement('button');button.type='button';button.className='wp-theme-item-lightbox__thumb';button.setAttribute('data-item-gallery-lightbox-thumb','');button.setAttribute('data-index',String(index));button.setAttribute('aria-label','View image '+(index+1)+' of '+items.length);
      var img=document.createElement('img');img.src=item.thumb||item.full;img.alt='';img.loading='lazy';img.draggable=false;button.appendChild(img);rail.appendChild(button);
    });
    rail.hidden=items.length<2;
    box.querySelectorAll('[data-item-gallery-prev],[data-item-gallery-next]').forEach(function(button){button.hidden=items.length<2;});
  }

  function showLightboxIndex(box,index,focusThumb){
    var items=boxItems(box);if(!items.length)return;
    if(index<0)index=items.length-1;if(index>=items.length)index=0;
    var item=items[index],image=box.querySelector('[data-item-gallery-lightbox-image]'),caption=box.querySelector('[data-item-gallery-caption]'),counter=box.querySelector('[data-item-gallery-counter]');
    if(image){
      var src=item.full||item.url||'';
      if(src&&image.getAttribute('src')!==src){image.classList.add('is-loading');image.onload=function(){image.classList.remove('is-loading');};image.onerror=function(){image.classList.remove('is-loading');};image.setAttribute('src',src);}
      image.setAttribute('alt',item.alt||'');image.draggable=false;
    }
    if(caption){var text=item.caption||'';caption.textContent=text;caption.hidden=!text;}
    if(counter)counter.textContent=(index+1)+' / '+items.length;
    var thumbs=box.querySelectorAll('[data-item-gallery-lightbox-thumb]');
    thumbs.forEach(function(el,i){var active=i===index;el.classList.toggle('is-active',active);el.setAttribute('aria-current',active?'true':'false');});
    box.setAttribute('data-current-index',String(index));
    var activeThumb=thumbs[index];if(focusThumb&&activeThumb)activeThumb.focus({preventScroll:true});
    if(activeThumb&&activeThumb.scrollIntoView)activeThumb.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'nearest',inline:'center'});
  }

  function staticBoxFor(root){
    if(!root)return null;
    var sibling=root.nextElementSibling;
    if(sibling&&sibling.matches('[data-item-gallery-lightbox]'))return sibling;
    var parent=root.parentElement;
    if(parent){
      var boxes=parent.querySelectorAll(':scope > [data-item-gallery-lightbox]');
      if(boxes.length===1)return boxes[0];
    }
    return null;
  }

  function openRootGallery(root,index){
    var items=safeItems(root);if(!items.length)return false;
    var box=ensureDynamicBox();populateDynamic(box,items);
    lastFocus=document.activeElement;
    box.hidden=false;box.setAttribute('aria-hidden','false');document.documentElement.classList.add('wp-theme-gallery-open');document.body.classList.add('wp-theme-gallery-open');
    showLightboxIndex(box,Number.isFinite(index)?index:0,false);
    var close=box.querySelector('.wp-theme-item-lightbox__close');if(close)window.setTimeout(function(){close.focus({preventScroll:true});},0);
    return true;
  }

  function closeLightbox(box){
    if(!box)return;box.hidden=true;box.setAttribute('aria-hidden','true');document.documentElement.classList.remove('wp-theme-gallery-open');document.body.classList.remove('wp-theme-gallery-open');
    if(lastFocus&&typeof lastFocus.focus==='function'){try{lastFocus.focus({preventScroll:true});}catch(error){lastFocus.focus();}}lastFocus=null;
  }
  function move(box,delta){var i=intValue(box.getAttribute('data-current-index'));showLightboxIndex(box,i+delta,false);}

  function visualGalleryItems(img){
    var scope=img.closest('.wp-block-gallery,.wpbb-swiper--gallery,.wp-theme-gallery-section');if(!scope)return null;
    var images=Array.prototype.slice.call(scope.querySelectorAll('img')).filter(function(node){return node.src&&node.offsetParent!==null;});
    var unique=[];var seen={};
    images.forEach(function(node){
      var link=node.closest('a');var full=link&&/\.(avif|gif|jpe?g|png|webp)(\?.*)?$/i.test(link.href||'')?link.href:(node.currentSrc||node.src);
      if(!full||seen[full])return;seen[full]=true;
      var caption='';var figure=node.closest('figure');var cap=figure&&figure.querySelector('figcaption');if(cap)caption=cap.textContent.trim();
      if(!caption){var slide=node.closest('.wpbb-swiper-slide');var title=slide&&slide.querySelector('.wpbb-swiper-slide__title');if(title)caption=title.textContent.trim();}
      unique.push({full:full,thumb:node.currentSrc||node.src,alt:node.alt||'',caption:caption,node:node});
    });
    if(unique.length<1)return null;
    return {items:unique,index:Math.max(0,unique.findIndex(function(item){return item.node===img;}))};
  }
  function openVisualGallery(img){
    var group=visualGalleryItems(img);if(!group)return false;
    var box=ensureDynamicBox();populateDynamic(box,group.items);
    lastFocus=document.activeElement;box.hidden=false;box.setAttribute('aria-hidden','false');document.documentElement.classList.add('wp-theme-gallery-open');document.body.classList.add('wp-theme-gallery-open');showLightboxIndex(box,group.index,false);
    var close=box.querySelector('.wp-theme-item-lightbox__close');if(close)window.setTimeout(function(){close.focus({preventScroll:true});},0);return true;
  }

  /* Capture visual gallery clicks before Swiper/link handlers can consume them. */
  document.addEventListener('click',function(e){
    var target=e.target&&e.target.closest?e.target:null;
    if(!target)return;
    var cardMain=target.closest('[data-item-gallery-card] [data-item-gallery-main]');
    if(cardMain){
      var cardRoot=cardMain.closest('[data-item-gallery-card]');
      if(cardRoot&&safeItems(cardRoot).length>1){
        e.preventDefault();e.stopPropagation();if(typeof e.stopImmediatePropagation==='function')e.stopImmediatePropagation();
        openRootGallery(cardRoot,intValue(cardMain.getAttribute('data-index')));return;
      }
    }
    var image=target.closest('.wp-theme-gallery-section img,.wpbb-swiper--gallery img,.wp-block-gallery img');
    if(!image){
      var visualCard=target.closest('.wp-theme-gallery-section .wpbb-swiper-slide,.wpbb-swiper--gallery .wpbb-swiper-slide,.wp-block-gallery figure');
      if(visualCard)image=visualCard.querySelector('img');
    }
    if(!image||image.closest('[data-item-gallery-lightbox]'))return;
    if(!visualGalleryItems(image))return;
    e.preventDefault();
    e.stopPropagation();
    if(typeof e.stopImmediatePropagation==='function')e.stopImmediatePropagation();
    openVisualGallery(image);
  },true);

  document.addEventListener('click',function(e){
    var closer=e.target.closest('[data-item-gallery-close]');if(closer){e.preventDefault();closeLightbox(closer.closest('[data-item-gallery-lightbox]'));return;}
    var prev=e.target.closest('[data-item-gallery-prev]');if(prev){e.preventDefault();move(prev.closest('[data-item-gallery-lightbox]'),-1);return;}
    var next=e.target.closest('[data-item-gallery-next]');if(next){e.preventDefault();move(next.closest('[data-item-gallery-lightbox]'),1);return;}
    var lbThumb=e.target.closest('[data-item-gallery-lightbox-thumb]');if(lbThumb){e.preventDefault();var box=lbThumb.closest('[data-item-gallery-lightbox]');if(box)showLightboxIndex(box,indexOfThumb(lbThumb),false);return;}

    var opener=e.target.closest('[data-item-gallery-open]');
    if(opener){e.preventDefault();e.stopPropagation();var single=opener.closest('[data-item-gallery-single]');if(single){var singleMain=single.querySelector('[data-item-gallery-main]');openRootGallery(single,singleMain?intValue(singleMain.getAttribute('data-index')):0);}return;}

    var cardOpen=e.target.closest('[data-item-gallery-card-open]');
    if(cardOpen){e.preventDefault();e.stopPropagation();var card=cardOpen.closest('[data-item-gallery-card]');if(card){var cardMain=card.querySelector('[data-item-gallery-main]');openRootGallery(card,cardMain?intValue(cardMain.getAttribute('data-index')):0);}return;}

    var thumb=e.target.closest('[data-item-gallery-thumb]');
    if(thumb){
      e.preventDefault();e.stopPropagation();activateInline(thumb);
      var root=thumb.closest('[data-item-gallery-card],[data-item-gallery-single]');
      if(root&&root.matches('[data-item-gallery-card]'))openRootGallery(root,indexOfThumb(thumb));
      return;
    }

  });

  document.addEventListener('keydown',function(e){
    var box=document.querySelector('[data-item-gallery-lightbox]:not([hidden])');
    if(box){
      if(e.key==='Escape'){e.preventDefault();closeLightbox(box);return;}
      if(e.key==='ArrowLeft'){e.preventDefault();move(box,-1);return;}
      if(e.key==='ArrowRight'){e.preventDefault();move(box,1);return;}
      if(e.key==='Tab'){
        var focusable=Array.prototype.slice.call(box.querySelectorAll('button:not([disabled]):not([hidden]),a[href],[tabindex]:not([tabindex="-1"])')).filter(function(el){return el.offsetParent!==null;});
        if(focusable.length){var first=focusable[0],last=focusable[focusable.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}
      }
      return;
    }
    if(e.key!=='Enter'&&e.key!==' ')return;
    var cardOpen=e.target.closest('[data-item-gallery-card-open]');if(cardOpen){e.preventDefault();e.stopPropagation();var card=cardOpen.closest('[data-item-gallery-card]');if(card){var main=card.querySelector('[data-item-gallery-main]');openRootGallery(card,main?intValue(main.getAttribute('data-index')):0);}return;}
    var thumb=e.target.closest('[data-item-gallery-thumb]');if(thumb){e.preventDefault();e.stopPropagation();activateInline(thumb);var root=thumb.closest('[data-item-gallery-card],[data-item-gallery-single]');if(root&&root.matches('[data-item-gallery-card]'))openRootGallery(root,indexOfThumb(thumb));}
  });

  document.addEventListener('pointerdown',function(e){var viewer=e.target.closest('[data-item-gallery-viewer],.wp-theme-item-lightbox__viewer');if(!viewer||!viewer.closest('[data-item-gallery-lightbox]:not([hidden])'))return;pointerStart={x:e.clientX,y:e.clientY};},{passive:true});
  document.addEventListener('pointerup',function(e){
    if(!pointerStart)return;var box=e.target.closest('[data-item-gallery-lightbox]:not([hidden])');if(!box){pointerStart=null;return;}
    var dx=e.clientX-pointerStart.x,dy=e.clientY-pointerStart.y;pointerStart=null;if(Math.abs(dx)<54||Math.abs(dx)<Math.abs(dy)*1.25)return;move(box,dx<0?1:-1);
  },{passive:true});
})();
