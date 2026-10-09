(function($){
  function closePanel(){
    $('#wpbbshop-category-panel,#wpbbshop-menu-panel').attr('hidden', true);
    $('.wpbbshop-cat-toggle,.wpbbshop-menu-toggle').attr('aria-expanded','false');
    $('body').removeClass('wpbbshop-cat-open wpbbshop-super-menu-open wpbbshop-menu-open');
    if(!$('body').hasClass('wpbbshop-cart-open')) $('.wpbbshop-cart-drawer-overlay').attr('hidden', true);
  }
  function openPanel(selector, button){
    $('#wpbbshop-category-panel,#wpbbshop-menu-panel').not(selector).attr('hidden', true);
    $('.wpbbshop-cat-toggle,.wpbbshop-menu-toggle').not(button).attr('aria-expanded','false');
    $(selector).removeAttr('hidden');
    // Navigation panels have their own stacking context. Do not reuse the mini-cart overlay here.
    $(button).attr('aria-expanded','true');
    $('body').addClass('wpbbshop-super-menu-open').toggleClass('wpbbshop-cat-open', selector === '#wpbbshop-category-panel').toggleClass('wpbbshop-menu-open', selector === '#wpbbshop-menu-panel');
  }
  $(document).on('click','.wpbbshop-cat-toggle',function(e){
    e.preventDefault();
    if($('#wpbbshop-category-panel').is('[hidden]')) openPanel('#wpbbshop-category-panel', this); else closePanel();
  });
  $(document).on('click','.wpbbshop-menu-toggle',function(e){
    e.preventDefault();
    if($('#wpbbshop-menu-panel').is('[hidden]')) openPanel('#wpbbshop-menu-panel', this); else closePanel();
  });
  $(document).on('click',function(e){
    if(!$(e.target).closest('.wpbbshop-category-panel,.wpbbshop-cat-toggle,.wpbbshop-menu-toggle').length){ closePanel(); }
  });
  $(document).on('keyup',function(e){ if(e.key === 'Escape'){ closePanel(); } });
})(jQuery);

(function($){
  function closeMiniCart(){
    $('#wpbbshop-mini-cart-panel').attr('hidden', true);
    $('.wpbbshop-cart-drawer-overlay').attr('hidden', true);
    $('.wpbbshop-mini-cart-toggle').attr('aria-expanded','false');
    $('body').removeClass('wpbbshop-cart-open');
  }
  $(document).on('click', '.wpbbshop-mini-cart-toggle', function(e){
    e.preventDefault();
    var $panel = $('#wpbbshop-mini-cart-panel');
    var willOpen = $panel.is('[hidden]');
    if(willOpen){
      $panel.removeAttr('hidden');
      $('.wpbbshop-cart-drawer-overlay').removeAttr('hidden');
      $(this).attr('aria-expanded','true');
      $('body').addClass('wpbbshop-cart-open');
    } else {
      closeMiniCart();
    }
  });
  $(document).on('click', '.wpbbshop-mini-cart-close,.wpbbshop-cart-drawer-overlay', function(e){ e.preventDefault(); closeMiniCart(); });
  $(document).on('click', function(e){
    if(!$(e.target).closest('#wpbbshop-mini-cart-panel,.wpbbshop-mini-cart-toggle').length){ closeMiniCart(); }
  });
  $(document).on('keyup', function(e){ if(e.key === 'Escape'){ closeMiniCart(); } });
  $(document.body).on('added_to_cart', function(){ $('#wpbbshop-mini-cart-panel').removeAttr('hidden'); $('.wpbbshop-cart-drawer-overlay').removeAttr('hidden'); $('.wpbbshop-mini-cart-toggle').attr('aria-expanded','true'); $('body').addClass('wpbbshop-cart-open'); });

  $(document).on('click', '.wpbbshop-single-thumbs a', function(e){
    var img = $(this).attr('href');
    var $main = $('.wpbbshop-single-main-image img').first();
    if(img && $main.length){
      e.preventDefault();
      $main.attr('src', img).removeAttr('srcset sizes');
      $('.wpbbshop-single-thumbs a').removeClass('is-active');
      $(this).addClass('is-active');
    }
  });
})(jQuery);

(function($){
  $(document).on('click', '.wpbbshop-list-toggle', function(e){
    var $btn = $(this);
    var list = $btn.data('list');
    var productId = $btn.data('product-id');
    if(!list || !productId || typeof WpbbshopTheme === 'undefined') return;
    e.preventDefault();
    $btn.addClass('is-loading');
    $.post(WpbbshopTheme.ajaxUrl, {
      action: 'wpbbshop_toggle_list',
      nonce: WpbbshopTheme.nonce,
      list: list,
      product_id: productId
    }).done(function(resp){
      if(resp && resp.success){
        var active = !!resp.data.active;
        $btn.closest('.wpbbshop-native').toggleClass('is-active', active);
        $('.wpbbshop-wishlist-count').text(resp.data.wishlist_count || 0);
        $('.wpbbshop-compare-count').text(resp.data.compare_count || 0);
        $btn.attr('aria-pressed', active ? 'true' : 'false');
        $btn.find('.wpbbshop-list-label').text(resp.data.label || (active ? 'Pievienots' : 'Pievienot'));
      } else {
        window.location.href = $btn.attr('href');
      }
    }).fail(function(){
      window.location.href = $btn.attr('href');
    }).always(function(){
      $btn.removeClass('is-loading');
    });
  });
})(jQuery);


/* Legacy simple lightbox removed in V24; replaced by thumbnail lightbox below. */

/* V17: AJAX search */
(function($){
  var timer = null;
  function renderResults($form, data, query){
    var $box = $form.find('.wpbbshop-search-results');
    var items = data && data.items ? data.items : [];
    var allUrl = data && data.allUrl ? data.allUrl : ($form.attr('action') + '?s=' + encodeURIComponent(query) + '&post_type=product');
    var allLabel = data && data.allLabel ? data.allLabel : 'Skatīt visus rezultātus';
    var emptyLabel = data && data.emptyLabel ? data.emptyLabel : 'Preces netika atrastas.';
    var esc = function(v){ return $('<div>').text(v == null ? '' : String(v)).html(); };
    var html = '';
    if(items.length){
      html += '<div class="wpbbshop-search-results-list">';
      items.forEach(function(item){
        html += '<a class="wpbbshop-search-result" href="'+ esc(item.url) +'">';
        html += item.image ? '<img src="'+ esc(item.image) +'" alt="">' : '<span class="wpbbshop-search-noimg"></span>';
        html += '<span class="llg-search-result-copy">';
        html += '<span class="llg-search-result-top"><span class="llg-search-result-stock">'+ esc(item.stock || '') +'</span>' + (item.category ? '<span>'+ esc(item.category) +'</span>' : '') + '</span>';
        html += '<strong>'+ esc(item.title) +'</strong>';
        html += '<span class="llg-search-result-bottom"><small>' + (item.sku ? 'SKU: '+ esc(item.sku) : 'ID: '+ esc(item.id)) + '</small><span class="llg-search-result-prices">' + (item.regularPrice ? '<del>'+ esc(item.regularPrice) +'</del>' : '') + '<span class="llg-search-result-price">'+ esc(item.price || '') +'</span></span></span>';
        html += '</span></a>';
      });
      html += '</div>';
    }else{
      html += '<div class="wpbbshop-search-empty">'+ esc(emptyLabel) +'</div>';
    }
    html += '<a class="wpbbshop-search-all" href="'+ esc(allUrl) +'">'+ esc(allLabel) +' →</a>';
    $box.html(html).removeAttr('hidden');
  }
  $(document).on('input focus', '.wpbbshop-ajax-search input[type="search"]', function(){
    var $input = $(this), $form = $input.closest('form'), q = $.trim($input.val());
    clearTimeout(timer);
    if(q.length < 2){ $form.find('.wpbbshop-search-results').attr('hidden', true).empty(); return; }
    timer = setTimeout(function(){
      if(typeof WpbbshopTheme === 'undefined') return;
      $.get(WpbbshopTheme.ajaxUrl, { action:'wpbbshop_product_search', lang:WpbbshopTheme.lang, nonce:WpbbshopTheme.nonce, term:q, product_cat:($form.find('select[name="product_cat"]').val() || '') })
        .done(function(resp){ if(resp && resp.success){ renderResults($form, resp.data, q); } });
    }, 180);
  });
  $(document).on('click', function(e){
    if(!$(e.target).closest('.wpbbshop-ajax-search').length){ $('.wpbbshop-search-results').attr('hidden', true); }
  });
})(jQuery);

(function($){
  $(document).on('click', '.wpbbshop-mobile-filter-toggle', function(e){ e.preventDefault(); $('body').toggleClass('wpbbshop-filters-open'); $('.wpbbshop-archive-filter-col').toggleClass('is-open'); });
  $(document).on('click', '.wpbbshop-load-more', function(e){
    e.preventDefault();
    var $btn = $(this); if($btn.hasClass('is-loading')) return;
    var next = parseInt($btn.attr('data-page') || '1', 10) + 1;
    var max = parseInt($btn.attr('data-max') || '1', 10);
    $btn.addClass('is-loading').find('.wpbbshop-load-more-text').text('Ielādē...');
    $.post(WpbbshopTheme.ajaxUrl, {
      action:'wpbbshop_load_more_products', lang:WpbbshopTheme.lang, nonce:WpbbshopTheme.nonce, page:next,
      taxonomy:$btn.data('taxonomy') || '', term:$btn.data('term') || '', search:$btn.data('search') || '',
      min_price:$btn.data('min-price') || '', max_price:$btn.data('max-price') || '', onsale:$btn.data('onsale') || '', orderby:$btn.data('orderby') || ''
    }).done(function(res){
      if(res && res.success && res.data && res.data.html){
        $('#wpbbshop-archive-products').append(res.data.html);
        $btn.attr('data-page', next);
        if(next >= max || next >= parseInt(res.data.max || max, 10)){ $btn.closest('.wpbbshop-load-more-wrap').remove(); }
        else { $btn.removeClass('is-loading').find('.wpbbshop-load-more-text').text('Ielādēt vairāk preces'); }
      } else { $btn.closest('.wpbbshop-load-more-wrap').remove(); }
    }).fail(function(){ $btn.removeClass('is-loading').find('.wpbbshop-load-more-text').text('Mēģināt vēlreiz'); });
  });
})(jQuery);

/* V29: one reliable IWS-style gallery. Thumbnail click updates main image, modal has bottom thumbs and arrows. */
(function($){
  function $root(){ return $('body.single-product .wpbbshop-iws-gallery-custom').first(); }
  function $main(){ return $root().find('.wpbbshop-iws-main-img').first(); }
  function collect(){
    var items = [];
    var $r = $root();
    $r.find('.wpbbshop-iws-thumb').each(function(i){
      var $b = $(this), $im = $b.find('img').first();
      var large = $b.attr('data-large') || $b.attr('data-full') || $b.attr('data-thumb') || $im.attr('data-src') || $im.attr('src') || '';
      var full = $b.attr('data-full') || large;
      var thumb = $b.attr('data-thumb') || $im.attr('src') || large;
      if(large || full || thumb){ items.push({large:large, full:full, thumb:thumb, alt:$im.attr('alt') || ''}); }
    });
    if(!items.length){
      var $m = $main();
      var src = $m.attr('data-full') || $m.attr('data-large') || $m.attr('src') || '';
      if(src){ items.push({large:src, full:src, thumb:src, alt:$m.attr('alt') || ''}); }
    }
    return items;
  }
  function activeIndex(){
    var n = parseInt($root().attr('data-active') || '0', 10);
    if(isNaN(n)) n = 0;
    return n;
  }
  function setActive(index){
    var items = collect();
    if(!items.length) return;
    if(index < 0) index = items.length - 1;
    if(index >= items.length) index = 0;
    var item = items[index];
    var src = item.large || item.full || item.thumb;
    var $m = $main();
    if(!$m.length || !src) return;
    $root().attr('data-active', index);
    $root().find('.wpbbshop-iws-thumb').removeClass('is-active').attr('aria-pressed','false').eq(index).addClass('is-active').attr('aria-pressed','true');
    $m.stop(true, true).css({opacity:.18, visibility:'visible', display:'block'});
    $m.removeAttr('srcset sizes width height');
    // Set src immediately so cached/blocked preload can never leave a blank white box.
    $m.attr({src:src, 'data-large':item.large || src, 'data-full':item.full || src, alt:item.alt || $m.attr('alt') || ''});
    var img = new Image();
    img.onload = function(){ $m.css({opacity:1, visibility:'visible', display:'block'}); };
    img.onerror = function(){
      var fallback = item.full || item.thumb || src;
      $m.attr({src:fallback, 'data-large':fallback, 'data-full':fallback}).css({opacity:1, visibility:'visible', display:'block'});
    };
    img.src = src;
    setTimeout(function(){ $m.css({opacity:1, visibility:'visible', display:'block'}); }, 180);
    updateModal(index, false);
  }
  function ensureModal(){
    var $lb = $('.wpbbshop-lightbox-v29').first();
    if(!$lb.length){
      $lb = $('<div class="wpbbshop-lightbox-v29" hidden role="dialog" aria-modal="true" aria-label="Produkta attēli">' +
        '<button type="button" class="wpbbshop-lb-close" aria-label="Aizvērt">×</button>' +
        '<button type="button" class="wpbbshop-lb-arrow wpbbshop-lb-prev" aria-label="Iepriekšējais">‹</button>' +
        '<figure class="wpbbshop-lb-stage"><img class="wpbbshop-lb-img" alt="" /></figure>' +
        '<button type="button" class="wpbbshop-lb-arrow wpbbshop-lb-next" aria-label="Nākamais">›</button>' +
        '<div class="wpbbshop-lb-thumbs" aria-label="Attēlu sīktēli"></div>' +
      '</div>');
      $('body').append($lb);
    }
    return $lb;
  }
  function updateModal(index, open){
    var $lb = $('.wpbbshop-lightbox-v29').first();
    if(!$lb.length && !open) return;
    var items = collect();
    if(!items.length) return;
    if(index < 0) index = items.length - 1;
    if(index >= items.length) index = 0;
    var item = items[index];
    $lb = ensureModal();
    $lb.attr('data-active', index);
    $lb.find('.wpbbshop-lb-img').removeAttr('srcset sizes width height').attr({src:item.full || item.large || item.thumb, alt:item.alt || ''});
    var html = '';
    for(var i=0;i<items.length;i++){
      html += '<button type="button" class="wpbbshop-lb-thumb'+(i===index?' is-active':'')+'" data-index="'+i+'"><img src="'+(items[i].thumb || items[i].large || items[i].full)+'" alt="" /></button>';
    }
    $lb.find('.wpbbshop-lb-thumbs').html(html);
    $lb.find('.wpbbshop-lb-arrow').toggle(items.length > 1);
    if(open){
      $lb.removeAttr('hidden');
      $('body').addClass('wpbbshop-lightbox-open');
    }
  }
  function openModal(index){ setActive(index); updateModal(activeIndex(), true); }
  function closeModal(){ $('.wpbbshop-lightbox-v29').attr('hidden', true); $('body').removeClass('wpbbshop-lightbox-open'); }
  function step(delta){ openModal(activeIndex() + delta); }

  $(document).on('click.wpbbshopV29Thumb', 'body.single-product .wpbbshop-iws-thumb', function(e){
    e.preventDefault(); e.stopPropagation();
    setActive(parseInt($(this).attr('data-index') || '0', 10));
  });
  $(document).on('click.wpbbshopV29Zoom', 'body.single-product .wpbbshop-gallery-zoom, body.single-product .wpbbshop-iws-main-img', function(e){
    e.preventDefault(); e.stopPropagation();
    openModal(activeIndex());
  });
  $(document).on('click.wpbbshopV29LbThumb', '.wpbbshop-lightbox-v29 .wpbbshop-lb-thumb', function(e){ e.preventDefault(); openModal(parseInt($(this).attr('data-index') || '0', 10)); });
  $(document).on('click.wpbbshopV29Prev', '.wpbbshop-lightbox-v29 .wpbbshop-lb-prev', function(e){ e.preventDefault(); step(-1); });
  $(document).on('click.wpbbshopV29Next', '.wpbbshop-lightbox-v29 .wpbbshop-lb-next', function(e){ e.preventDefault(); step(1); });
  $(document).on('click.wpbbshopV29Close', '.wpbbshop-lightbox-v29 .wpbbshop-lb-close', function(e){ e.preventDefault(); closeModal(); });
  $(document).on('click.wpbbshopV29Backdrop', '.wpbbshop-lightbox-v29', function(e){ if(e.target === this) closeModal(); });
  $(document).on('keydown.wpbbshopV29', function(e){
    if(!$('body').hasClass('wpbbshop-lightbox-open')) return;
    if(e.key === 'Escape') closeModal();
    if(e.key === 'ArrowLeft') step(-1);
    if(e.key === 'ArrowRight') step(1);
  });
  $(function(){ if($root().length) setActive(activeIndex()); });
})(jQuery);

/* V30: robust range UI and gallery fallback for native/custom WooCommerce galleries. */
(function($){
  function clamp(n,min,max){ n=parseInt(n,10); if(isNaN(n)) n=0; return Math.max(min, Math.min(max, n)); }
  function syncRange($box, changed){
    var maxAllowed = parseInt($box.attr('data-max') || '1000', 10);
    var $rMin = $box.find('.wpbbshop-range-min'), $rMax = $box.find('.wpbbshop-range-max');
    var $nMin = $box.find('.wpbbshop-price-min-number'), $nMax = $box.find('.wpbbshop-price-max-number');
    var min = clamp(changed && changed.hasClass('wpbbshop-price-min-number') ? $nMin.val() : $rMin.val(), 0, maxAllowed);
    var max = clamp(changed && changed.hasClass('wpbbshop-price-max-number') ? $nMax.val() : $rMax.val(), 0, maxAllowed);
    if(min > max){ if(changed && (changed.hasClass('wpbbshop-range-min') || changed.hasClass('wpbbshop-price-min-number'))) max = min; else min = max; }
    $rMin.val(min); $rMax.val(max); $nMin.val(min); $nMax.val(max);
    $box.find('.wpbbshop-range-min-text').text(min); $box.find('.wpbbshop-range-max-text').text(max);
    var a = maxAllowed ? (min / maxAllowed) * 100 : 0, b = maxAllowed ? (max / maxAllowed) * 100 : 100;
    $box[0].style.setProperty('--wpbbshop-range-a', a + '%');
    $box[0].style.setProperty('--wpbbshop-range-b', b + '%');
  }
  $(document).on('input change', '.wpbbshop-range-filter input', function(){ syncRange($(this).closest('.wpbbshop-range-filter'), $(this)); });
  $(function(){ $('.wpbbshop-range-filter').each(function(){ syncRange($(this), null); }); });

  function setCustomMain($gallery, src, full, alt){
    var $main = $gallery.find('.wpbbshop-iws-main-img').first();
    if(!$main.length || !src) return;
    $main.removeAttr('srcset sizes width height').attr({src:src, 'data-large':src, 'data-full':full || src, alt:alt || $main.attr('alt') || ''});
    $gallery.find('.wpbbshop-iws-gallery-main').css('background-image', 'none');
    $main.css({display:'block', visibility:'visible', opacity:1});
  }
  $(document).off('click.wpbbshopV29Thumb').on('click.wpbbshopV30Thumb', 'body.single-product .wpbbshop-iws-thumb', function(e){
    e.preventDefault(); e.stopPropagation();
    var $btn = $(this), $gallery = $btn.closest('.wpbbshop-iws-gallery-custom');
    var $img = $btn.find('img').first();
    var src = $btn.attr('data-full') || $btn.attr('data-large') || $btn.attr('data-thumb') || $img.attr('src') || '';
    setCustomMain($gallery, src, $btn.attr('data-full') || src, $img.attr('alt') || '');
    $gallery.attr('data-active', $btn.attr('data-index') || $btn.index());
    $gallery.find('.wpbbshop-iws-thumb').removeClass('is-active').attr('aria-pressed','false');
    $btn.addClass('is-active').attr('aria-pressed','true');
  });

  $(document).on('click.wpbbshopV30NativeThumb', 'body.single-product .woocommerce-product-gallery .flex-control-thumbs img', function(e){
    var $thumb = $(this), $gallery = $thumb.closest('.woocommerce-product-gallery');
    var src = $thumb.attr('data-large_image') || $thumb.attr('data-full') || $thumb.attr('src') || '';
    if(!src) return;
    var $main = $gallery.find('.woocommerce-product-gallery__image').first().find('img').first();
    if($main.length){
      e.preventDefault();
      $main.removeAttr('srcset sizes width height').attr({src:src, 'data-large_image':src, 'data-src':src}).css({display:'block', visibility:'visible', opacity:1});
      $gallery.find('.flex-control-thumbs img').removeClass('flex-active');
      $thumb.addClass('flex-active');
    }
  });
})(jQuery);


/* V31: final gallery takeover. Uses data-main/data-full with thumbnail fallback and a single modal. */
(function(){
  function ready(fn){ if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  function q(sel, ctx){ return (ctx || document).querySelector(sel); }
  function qa(sel, ctx){ return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }
  function gallery(){ return q('body.single-product .wpbbshop-gallery-v31'); }
  function items(){
    var g = gallery(); if(!g) return [];
    var arr = qa('.wpbbshop-iws-thumb', g).map(function(btn){
      var img = q('img', btn);
      var thumb = btn.getAttribute('data-thumb') || (img ? img.getAttribute('src') : '') || '';
      var main = btn.getAttribute('data-main') || btn.getAttribute('data-large') || btn.getAttribute('data-full') || thumb;
      var full = btn.getAttribute('data-full') || main || thumb;
      return {btn:btn, thumb:thumb, main:main || thumb || full, full:full || main || thumb, alt:(img ? img.getAttribute('alt') : '') || ''};
    }).filter(function(it){ return it.main || it.full || it.thumb; });
    if(!arr.length){
      var m = q('.wpbbshop-iws-main-img', g);
      if(m){ arr.push({btn:null, thumb:m.getAttribute('src'), main:m.getAttribute('data-main') || m.getAttribute('src'), full:m.getAttribute('data-full') || m.getAttribute('src'), alt:m.getAttribute('alt') || ''}); }
    }
    return arr;
  }
  function setActive(index, openModal){
    var g = gallery(); if(!g) return;
    var arr = items(); if(!arr.length) return;
    if(index < 0) index = arr.length - 1; if(index >= arr.length) index = 0;
    var it = arr[index];
    var m = q('.wpbbshop-iws-main-img', g); if(!m) return;
    g.setAttribute('data-active', String(index));
    qa('.wpbbshop-iws-thumb', g).forEach(function(b, i){ b.classList.toggle('is-active', i === index); b.setAttribute('aria-pressed', i === index ? 'true' : 'false'); });
    var candidates = [it.main, it.full, it.thumb].filter(Boolean);
    var src = candidates[0];
    m.removeAttribute('srcset'); m.removeAttribute('sizes'); m.removeAttribute('width'); m.removeAttribute('height');
    m.style.display = 'block'; m.style.visibility = 'visible'; m.style.opacity = '1';
    m.setAttribute('src', src); m.setAttribute('data-main', it.main || src); m.setAttribute('data-full', it.full || src); m.setAttribute('alt', it.alt || m.getAttribute('alt') || '');
    // If selected image fails, walk through full/thumb fallbacks instead of leaving white box.
    var attempt = 0;
    m.onerror = function(){ attempt++; if(candidates[attempt]){ m.setAttribute('src', candidates[attempt]); } else { m.onerror = null; } };
    if(openModal) updateModal(index, true); else updateModal(index, false);
  }
  function modal(){
    var lb = q('.wpbbshop-lightbox-v31');
    if(!lb){
      lb = document.createElement('div');
      lb.className = 'wpbbshop-lightbox-v31'; lb.hidden = true; lb.setAttribute('role','dialog'); lb.setAttribute('aria-modal','true');
      lb.innerHTML = '<button type="button" class="wpbbshop-lb-close" aria-label="Aizvērt">×</button><button type="button" class="wpbbshop-lb-arrow wpbbshop-lb-prev" aria-label="Iepriekšējais">‹</button><figure class="wpbbshop-lb-stage"><img class="wpbbshop-lb-img" alt=""></figure><button type="button" class="wpbbshop-lb-arrow wpbbshop-lb-next" aria-label="Nākamais">›</button><div class="wpbbshop-lb-thumbs"></div>';
      document.body.appendChild(lb);
    }
    return lb;
  }
  function updateModal(index, open){
    var arr = items(); if(!arr.length) return;
    if(index < 0) index = arr.length - 1; if(index >= arr.length) index = 0;
    var lb = modal(), it = arr[index], img = q('.wpbbshop-lb-img', lb), thumbs = q('.wpbbshop-lb-thumbs', lb);
    lb.setAttribute('data-active', String(index));
    img.removeAttribute('srcset'); img.removeAttribute('sizes'); img.setAttribute('src', it.full || it.main || it.thumb); img.setAttribute('alt', it.alt || '');
    thumbs.innerHTML = arr.map(function(x, i){ return '<button type="button" class="wpbbshop-lb-thumb '+(i===index?'is-active':'')+'" data-index="'+i+'"><img src="'+(x.thumb || x.main || x.full)+'" alt=""></button>'; }).join('');
    qa('.wpbbshop-lb-arrow', lb).forEach(function(a){ a.style.display = arr.length > 1 ? 'flex' : 'none'; });
    if(open){ lb.hidden = false; document.body.classList.add('wpbbshop-lightbox-open'); }
  }
  function current(){ var g = gallery(); var n = g ? parseInt(g.getAttribute('data-active') || '0', 10) : 0; return isNaN(n) ? 0 : n; }
  function close(){ var lb = q('.wpbbshop-lightbox-v31'); if(lb) lb.hidden = true; document.body.classList.remove('wpbbshop-lightbox-open'); }
  ready(function(){ setActive(current(), false); });
  document.addEventListener('click', function(e){
    var thumb = e.target.closest && e.target.closest('body.single-product .wpbbshop-gallery-v31 .wpbbshop-iws-thumb');
    if(thumb){ e.preventDefault(); e.stopImmediatePropagation(); setActive(parseInt(thumb.getAttribute('data-index') || '0', 10), false); return; }
    var zoom = e.target.closest && e.target.closest('body.single-product .wpbbshop-gallery-v31 .wpbbshop-gallery-zoom, body.single-product .wpbbshop-gallery-v31 .wpbbshop-iws-main-img');
    if(zoom){ e.preventDefault(); e.stopImmediatePropagation(); setActive(current(), true); return; }
    var lbThumb = e.target.closest && e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-thumb');
    if(lbThumb){ e.preventDefault(); setActive(parseInt(lbThumb.getAttribute('data-index') || '0', 10), true); return; }
    if(e.target.closest && e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-prev')){ e.preventDefault(); setActive(current()-1, true); return; }
    if(e.target.closest && e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-next')){ e.preventDefault(); setActive(current()+1, true); return; }
    if(e.target.closest && e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-close')){ e.preventDefault(); close(); return; }
    if(e.target.classList && e.target.classList.contains('wpbbshop-lightbox-v31')) close();
  }, true);
  document.addEventListener('keydown', function(e){ if(!document.body.classList.contains('wpbbshop-lightbox-open')) return; if(e.key === 'Escape') close(); if(e.key === 'ArrowLeft') setActive(current()-1, true); if(e.key === 'ArrowRight') setActive(current()+1, true); });
})();

/* V32: hard override custom gallery and range control cleanup */
(function(){
  function qs(s,c){return (c||document).querySelector(s)}
  function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))}
  function ready(f){if(document.readyState!=='loading')f();else document.addEventListener('DOMContentLoaded',f)}
  function gal(){return qs('body.single-product .wpbbshop-gallery-v31')}
  function getItems(){var g=gal(); if(!g) return []; return qsa('.wpbbshop-iws-thumb',g).map(function(b,i){var im=qs('img',b); var thumb=b.getAttribute('data-thumb')||(im&&im.src)||''; var full=b.getAttribute('data-full')||b.getAttribute('data-main')||b.getAttribute('data-large')||thumb; var main=b.getAttribute('data-main')||full||thumb; return {idx:i,btn:b,thumb:thumb,main:main,full:full,alt:(im&&im.alt)||''};}).filter(function(x){return x.main||x.full||x.thumb});}
  function preload(src, ok, fail){var im=new Image(); im.onload=function(){ok(src)}; im.onerror=function(){if(fail)fail(src)}; im.src=src;}
  function setMain(n, open){var g=gal(); if(!g) return; var arr=getItems(); if(!arr.length){var m=qs('.wpbbshop-iws-main-img',g); if(m&&open) openModal(0); return;} if(n<0)n=arr.length-1; if(n>=arr.length)n=0; var it=arr[n]; var main=qs('.wpbbshop-iws-main-img',g); if(!main) return; g.setAttribute('data-active',String(n)); qsa('.wpbbshop-iws-thumb',g).forEach(function(b,i){b.classList.toggle('is-active',i===n);b.setAttribute('aria-pressed',i===n?'true':'false')}); var list=[it.full,it.main,it.thumb].filter(Boolean); function apply(src){main.removeAttribute('srcset');main.removeAttribute('sizes');main.removeAttribute('width');main.removeAttribute('height');main.style.cssText+=';display:block!important;visibility:visible!important;opacity:1!important;';main.src=src;main.setAttribute('data-full',it.full||src);main.setAttribute('data-main',it.main||src);if(it.alt)main.alt=it.alt;if(open)openModal(n);} var i=0; (function tryIt(){if(!list[i]) return; preload(list[i],apply,function(){i++;tryIt()});})();}
  function ensureModal(){var lb=qs('.wpbbshop-lightbox-v31'); if(lb) return lb; lb=document.createElement('div'); lb.className='wpbbshop-lightbox-v31'; lb.hidden=true; lb.innerHTML='<button type="button" class="wpbbshop-lb-close" aria-label="Aizvērt">×</button><button type="button" class="wpbbshop-lb-arrow wpbbshop-lb-prev" aria-label="Iepriekšējais">‹</button><figure class="wpbbshop-lb-stage"><img class="wpbbshop-lb-img" alt=""></figure><button type="button" class="wpbbshop-lb-arrow wpbbshop-lb-next" aria-label="Nākamais">›</button><div class="wpbbshop-lb-thumbs" aria-label="Attēlu navigācija"></div>'; document.body.appendChild(lb); return lb;}
  function current(){var g=gal();var n=g?parseInt(g.getAttribute('data-active')||'0',10):0;return isNaN(n)?0:n}
  function openModal(n){var arr=getItems(); if(!arr.length)return; if(n<0)n=arr.length-1;if(n>=arr.length)n=0;var it=arr[n],lb=ensureModal(),img=qs('.wpbbshop-lb-img',lb),ths=qs('.wpbbshop-lb-thumbs',lb);lb.setAttribute('data-active',String(n));img.removeAttribute('srcset');img.removeAttribute('sizes');img.src=it.full||it.main||it.thumb;img.alt=it.alt||'';ths.innerHTML=arr.map(function(x,i){return '<button type="button" class="wpbbshop-lb-thumb '+(i===n?'is-active':'')+'" data-index="'+i+'"><img src="'+(x.thumb||x.main||x.full)+'" alt=""></button>'}).join('');lb.hidden=false;document.body.classList.add('wpbbshop-lightbox-open');}
  function close(){var lb=qs('.wpbbshop-lightbox-v31');if(lb)lb.hidden=true;document.body.classList.remove('wpbbshop-lightbox-open')}
  ready(function(){setMain(current(),false);qsa('body.single-product .woocommerce-product-gallery .flex-control-thumbs').forEach(function(el){el.style.display='none'});});
  document.addEventListener('click',function(e){var t=e.target.closest&&e.target.closest('body.single-product .wpbbshop-gallery-v31 .wpbbshop-iws-thumb');if(t){e.preventDefault();e.stopImmediatePropagation();setMain(parseInt(t.getAttribute('data-index')||'0',10),false);return}var z=e.target.closest&&e.target.closest('body.single-product .wpbbshop-gallery-v31 .wpbbshop-gallery-zoom, body.single-product .wpbbshop-gallery-v31 .wpbbshop-iws-main-img');if(z){e.preventDefault();e.stopImmediatePropagation();openModal(current());return}var mt=e.target.closest&&e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-thumb');if(mt){e.preventDefault();setMain(parseInt(mt.getAttribute('data-index')||'0',10),true);return}if(e.target.closest&&e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-prev')){e.preventDefault();setMain(current()-1,true);return}if(e.target.closest&&e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-next')){e.preventDefault();setMain(current()+1,true);return}if(e.target.closest&&e.target.closest('.wpbbshop-lightbox-v31 .wpbbshop-lb-close')){e.preventDefault();close();return}if(e.target.classList&&e.target.classList.contains('wpbbshop-lightbox-v31'))close();},true);
  document.addEventListener('keydown',function(e){if(!document.body.classList.contains('wpbbshop-lightbox-open'))return;if(e.key==='Escape')close();if(e.key==='ArrowLeft')setMain(current()-1,true);if(e.key==='ArrowRight')setMain(current()+1,true)});
})();

(function(){
  function sync(box){if(!box)return;var max=parseFloat(box.getAttribute('data-max')||1000)||1000;var rmin=box.querySelector('.wpbbshop-range-min'),rmax=box.querySelector('.wpbbshop-range-max'),nmin=box.querySelector('.wpbbshop-price-min-number'),nmax=box.querySelector('.wpbbshop-price-max-number');if(!rmin||!rmax)return;var a=parseFloat(rmin.value||0),b=parseFloat(rmax.value||max);if(a>b){var tmp=a;a=b;b=tmp}rmin.value=a;rmax.value=b;if(nmin)nmin.value=a;if(nmax)nmax.value=b;var pa=Math.max(0,Math.min(100,(a/max)*100)),pb=Math.max(0,Math.min(100,(b/max)*100));box.style.setProperty('--wpbbshop-range-a',pa+'%');box.style.setProperty('--wpbbshop-range-b',pb+'%');var tmin=box.querySelector('.wpbbshop-range-min-text'),tmax=box.querySelector('.wpbbshop-range-max-text');if(tmin)tmin.textContent=Math.round(a);if(tmax)tmax.textContent=Math.round(b)}
  document.addEventListener('input',function(e){var box=e.target.closest&&e.target.closest('.wpbbshop-range-filter');if(!box)return;if(e.target.classList.contains('wpbbshop-price-min-number')){var r=box.querySelector('.wpbbshop-range-min');if(r)r.value=e.target.value}if(e.target.classList.contains('wpbbshop-price-max-number')){var r2=box.querySelector('.wpbbshop-range-max');if(r2)r2.value=e.target.value}sync(box)});
  document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.wpbbshop-range-filter').forEach(sync)});
})();

/* V33: isolated single-product gallery. Ignores old Woo/Flex/IWS gallery handlers. */
(function(){
  function qs(s,c){return (c||document).querySelector(s);}
  function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s));}
  function ready(fn){if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn);}
  function gallery(){return qs('body.single-product .wpbbshop-product-gallery-v33');}
  function items(){var g=gallery(); if(!g) return []; return qsa('.wpbbshop-v33-thumb', g).map(function(btn, i){var im=qs('img', btn); return {index:i, btn:btn, main:btn.getAttribute('data-main') || (im && im.src) || '', full:btn.getAttribute('data-full') || btn.getAttribute('data-main') || (im && im.src) || '', thumb:(im && im.src) || '', alt:(im && im.alt) || ''};}).filter(function(x){return x.main || x.full || x.thumb;});}
  function current(){var g=gallery(); var n=g ? parseInt(g.getAttribute('data-active') || '0', 10) : 0; return isNaN(n) ? 0 : n;}
  function normalize(n, arr){if(!arr.length) return 0; if(n < 0) return arr.length - 1; if(n >= arr.length) return 0; return n;}
  function setActive(n, openModal){var g=gallery(); if(!g) return; var arr=items(); if(!arr.length) return; n=normalize(n, arr); var it=arr[n]; var main=qs('.wpbbshop-v33-main-img', g); if(!main) return; g.setAttribute('data-active', String(n)); qsa('.wpbbshop-v33-thumb', g).forEach(function(b, i){b.classList.toggle('is-active', i === n); b.setAttribute('aria-pressed', i === n ? 'true' : 'false');}); var src=it.main || it.full || it.thumb; main.removeAttribute('srcset'); main.removeAttribute('sizes'); main.removeAttribute('width'); main.removeAttribute('height'); main.style.display='block'; main.style.visibility='visible'; main.style.opacity='1'; main.src=src; main.setAttribute('data-full', it.full || src); if(it.alt) main.alt=it.alt; if(openModal) updateModal(n, true);}
  function modal(){var lb=qs('.wpbbshop-lightbox-v33'); if(lb) return lb; lb=document.createElement('div'); lb.className='wpbbshop-lightbox-v33'; lb.hidden=true; lb.setAttribute('role','dialog'); lb.setAttribute('aria-modal','true'); lb.innerHTML='<button type="button" class="wpbbshop-v33-close" aria-label="Aizvērt">×</button><button type="button" class="wpbbshop-v33-arrow wpbbshop-v33-prev" aria-label="Iepriekšējais">‹</button><figure class="wpbbshop-v33-stage"><img class="wpbbshop-v33-modal-img" alt=""></figure><button type="button" class="wpbbshop-v33-arrow wpbbshop-v33-next" aria-label="Nākamais">›</button><div class="wpbbshop-v33-modal-thumbs"></div>'; document.body.appendChild(lb); return lb;}
  function updateModal(n, open){var arr=items(); if(!arr.length) return; n=normalize(n, arr); var it=arr[n], lb=modal(), img=qs('.wpbbshop-v33-modal-img', lb), thumbs=qs('.wpbbshop-v33-modal-thumbs', lb); lb.setAttribute('data-active', String(n)); img.removeAttribute('srcset'); img.removeAttribute('sizes'); img.src=it.full || it.main || it.thumb; img.alt=it.alt || ''; thumbs.innerHTML=arr.map(function(x, i){return '<button type="button" class="wpbbshop-v33-modal-thumb '+(i===n?'is-active':'')+'" data-index="'+i+'"><img src="'+(x.thumb || x.main || x.full)+'" alt=""></button>';}).join(''); if(open){lb.hidden=false; document.body.classList.add('wpbbshop-v33-lightbox-open');}}
  function close(){var lb=qs('.wpbbshop-lightbox-v33'); if(lb) lb.hidden=true; document.body.classList.remove('wpbbshop-v33-lightbox-open');}
  ready(function(){var g=gallery(); if(!g) return; qsa('body.single-product .woocommerce-product-gallery, body.single-product .flex-control-nav, body.single-product .flex-control-thumbs').forEach(function(el){el.style.display='none';}); setActive(current(), false);});
  document.addEventListener('click', function(e){var t=e.target.closest && e.target.closest('body.single-product .wpbbshop-product-gallery-v33 .wpbbshop-v33-thumb'); if(t){e.preventDefault(); e.stopImmediatePropagation(); setActive(parseInt(t.getAttribute('data-index') || '0', 10), false); return;} var z=e.target.closest && e.target.closest('body.single-product .wpbbshop-product-gallery-v33 .wpbbshop-v33-zoom, body.single-product .wpbbshop-product-gallery-v33 .wpbbshop-v33-main-img'); if(z){e.preventDefault(); e.stopImmediatePropagation(); updateModal(current(), true); return;} var mt=e.target.closest && e.target.closest('.wpbbshop-lightbox-v33 .wpbbshop-v33-modal-thumb'); if(mt){e.preventDefault(); e.stopImmediatePropagation(); setActive(parseInt(mt.getAttribute('data-index') || '0', 10), true); return;} if(e.target.closest && e.target.closest('.wpbbshop-lightbox-v33 .wpbbshop-v33-prev')){e.preventDefault(); e.stopImmediatePropagation(); setActive(current()-1, true); return;} if(e.target.closest && e.target.closest('.wpbbshop-lightbox-v33 .wpbbshop-v33-next')){e.preventDefault(); e.stopImmediatePropagation(); setActive(current()+1, true); return;} if(e.target.closest && e.target.closest('.wpbbshop-lightbox-v33 .wpbbshop-v33-close')){e.preventDefault(); e.stopImmediatePropagation(); close(); return;} if(e.target.classList && e.target.classList.contains('wpbbshop-lightbox-v33')) close();}, true);
  document.addEventListener('keydown', function(e){if(!document.body.classList.contains('wpbbshop-v33-lightbox-open')) return; if(e.key === 'Escape') close(); if(e.key === 'ArrowLeft') setActive(current()-1, true); if(e.key === 'ArrowRight') setActive(current()+1, true);});
})();

/* V34: fully isolated gallery and range control alignment hardfix */
(function(){
  function qs(s,c){return (c||document).querySelector(s)}
  function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))}
  function ready(fn){if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn)}
  function galleries(){return qsa('body.single-product .wpbbshop-v34-gallery')}
  function getItems(g){
    var data=qs('.wpbbshop-v34-data',g);
    if(data){try{var parsed=JSON.parse(data.textContent||'[]');if(parsed && parsed.length)return parsed.map(function(x,i){return {index:i,main:x.main||x.full||x.thumb,full:x.full||x.main||x.thumb,thumb:x.thumb||x.main||x.full,alt:x.alt||''}})}catch(e){}}
    return qsa('.wpbbshop-v34-thumb',g).map(function(b,i){var im=qs('img',b);return{index:i,main:b.getAttribute('data-main')||(im&&im.src)||'',full:b.getAttribute('data-full')||b.getAttribute('data-main')||(im&&im.src)||'',thumb:b.getAttribute('data-thumb')||(im&&im.src)||'',alt:(im&&im.alt)||''}}).filter(function(x){return x.main||x.full||x.thumb})
  }
  function normalize(n, arr){if(!arr.length)return 0;if(n<0)return arr.length-1;if(n>=arr.length)return 0;return n}
  function forceImage(img, src, alt){
    if(!img||!src)return;
    img.removeAttribute('srcset'); img.removeAttribute('sizes'); img.removeAttribute('width'); img.removeAttribute('height');
    img.style.setProperty('display','block','important'); img.style.setProperty('visibility','visible','important'); img.style.setProperty('opacity','1','important');
    img.style.setProperty('max-width','100%','important'); img.style.setProperty('max-height','100%','important'); img.style.setProperty('width','auto','important'); img.style.setProperty('height','auto','important');
    img.style.setProperty('object-fit','contain','important'); img.style.setProperty('object-position','center','important');
    img.src=src; if(alt) img.alt=alt;
  }
  function setActive(g,n,open){
    var arr=getItems(g); if(!arr.length)return; n=normalize(n,arr); var it=arr[n], src=it.main||it.full||it.thumb;
    g.setAttribute('data-active',String(n));
    qsa('.wpbbshop-v34-thumb',g).forEach(function(b,i){b.classList.toggle('is-active',i===n);b.setAttribute('aria-pressed',i===n?'true':'false');b.style.borderColor=i===n?'#2f8b49':'#dfe7f1'});
    var main=qs('.wpbbshop-v34-main',g), img=qs('.wpbbshop-v34-main-img',g);
    if(main){main.style.backgroundImage='url("'+src.replace(/"/g,'%22')+'")';main.setAttribute('data-bg',src)}
    forceImage(img,src,it.alt); if(img) img.setAttribute('data-full',it.full||src);
    if(open) openModal(g,n);
  }
  function modal(){
    var lb=qs('.wpbbshop-v34-lightbox'); if(lb)return lb;
    lb=document.createElement('div'); lb.className='wpbbshop-v34-lightbox'; lb.hidden=true; lb.innerHTML='<button type="button" class="wpbbshop-v34-close" aria-label="Aizvērt">×</button><button type="button" class="wpbbshop-v34-arrow wpbbshop-v34-prev" aria-label="Iepriekšējais">‹</button><figure class="wpbbshop-v34-stage"><img class="wpbbshop-v34-modal-img" alt=""></figure><button type="button" class="wpbbshop-v34-arrow wpbbshop-v34-next" aria-label="Nākamais">›</button><div class="wpbbshop-v34-modal-thumbs"></div>';
    document.body.appendChild(lb); return lb;
  }
  function currentGallery(){return qs('body.single-product .wpbbshop-v34-gallery.is-modal-source')||qs('body.single-product .wpbbshop-v34-gallery')}
  function openModal(g,n){
    if(!g)return; galleries().forEach(function(x){x.classList.remove('is-modal-source')}); g.classList.add('is-modal-source');
    var arr=getItems(g); if(!arr.length)return; n=normalize(n,arr); var it=arr[n], lb=modal(), img=qs('.wpbbshop-v34-modal-img',lb), thumbs=qs('.wpbbshop-v34-modal-thumbs',lb);
    lb.setAttribute('data-active',String(n)); forceImage(img,it.full||it.main||it.thumb,it.alt);
    thumbs.innerHTML=arr.map(function(x,i){return '<button type="button" class="wpbbshop-v34-modal-thumb '+(i===n?'is-active':'')+'" data-index="'+i+'"><img src="'+(x.thumb||x.main||x.full)+'" alt=""></button>'}).join('');
    lb.hidden=false; document.body.classList.add('wpbbshop-v34-open');
  }
  function close(){var lb=qs('.wpbbshop-v34-lightbox'); if(lb)lb.hidden=true; document.body.classList.remove('wpbbshop-v34-open')}
  ready(function(){galleries().forEach(function(g){setActive(g,parseInt(g.getAttribute('data-active')||'0',10)||0,false)})});
  document.addEventListener('click',function(e){
    var th=e.target.closest&&e.target.closest('body.single-product .wpbbshop-v34-gallery .wpbbshop-v34-thumb'); if(th){e.preventDefault();e.stopImmediatePropagation();setActive(th.closest('.wpbbshop-v34-gallery'),parseInt(th.getAttribute('data-index')||'0',10)||0,false);return}
    var z=e.target.closest&&e.target.closest('body.single-product .wpbbshop-v34-gallery .wpbbshop-v34-zoom, body.single-product .wpbbshop-v34-gallery .wpbbshop-v34-main-img'); if(z){e.preventDefault();e.stopImmediatePropagation();var g=z.closest('.wpbbshop-v34-gallery');openModal(g,parseInt(g.getAttribute('data-active')||'0',10)||0);return}
    var mt=e.target.closest&&e.target.closest('.wpbbshop-v34-lightbox .wpbbshop-v34-modal-thumb'); if(mt){e.preventDefault();e.stopImmediatePropagation();setActive(currentGallery(),parseInt(mt.getAttribute('data-index')||'0',10)||0,true);return}
    if(e.target.closest&&e.target.closest('.wpbbshop-v34-lightbox .wpbbshop-v34-prev')){e.preventDefault();var g=currentGallery(),n=parseInt((modal()).getAttribute('data-active')||g.getAttribute('data-active')||'0',10)||0;setActive(g,n-1,true);return}
    if(e.target.closest&&e.target.closest('.wpbbshop-v34-lightbox .wpbbshop-v34-next')){e.preventDefault();var g2=currentGallery(),n2=parseInt((modal()).getAttribute('data-active')||g2.getAttribute('data-active')||'0',10)||0;setActive(g2,n2+1,true);return}
    if(e.target.closest&&e.target.closest('.wpbbshop-v34-lightbox .wpbbshop-v34-close')){e.preventDefault();close();return}
    if(e.target.classList&&e.target.classList.contains('wpbbshop-v34-lightbox'))close();
  },true);
  document.addEventListener('keydown',function(e){if(!document.body.classList.contains('wpbbshop-v34-open'))return;var g=currentGallery();if(e.key==='Escape')close();if(e.key==='ArrowLeft'){e.preventDefault();setActive(g,(parseInt(g.getAttribute('data-active')||'0',10)||0)-1,true)}if(e.key==='ArrowRight'){e.preventDefault();setActive(g,(parseInt(g.getAttribute('data-active')||'0',10)||0)+1,true)}});
})();

/* WP BB HOME & GARDEN V35: final isolated gallery controller */
(function(){
  'use strict';
  function qs(s,c){return (c||document).querySelector(s)}
  function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))}
  function ready(fn){if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',fn);else fn()}
  function items(g){
    var data = qs('.wpbbshop-v35-data', g);
    if (data) { try { var arr=JSON.parse(data.textContent||'[]'); if (arr && arr.length) return arr; } catch(e){} }
    return qsa('.wpbbshop-v35-thumb', g).map(function(b){var im=qs('img',b);return {main:b.getAttribute('data-main')||(im&&im.src)||'',full:b.getAttribute('data-full')||b.getAttribute('data-main')||(im&&im.src)||'',thumb:b.getAttribute('data-thumb')||(im&&im.src)||'',alt:(im&&im.alt)||''};}).filter(function(x){return x.main||x.full||x.thumb});
  }
  function norm(n, arr){if(!arr.length)return 0; n=parseInt(n,10)||0; if(n<0)n=arr.length-1; if(n>=arr.length)n=0; return n;}
  function setActive(g,n,modalOpen){if(!g)return; var arr=items(g); if(!arr.length)return; n=norm(n,arr); var it=arr[n]; var src=it.main||it.full||it.thumb; var img=qs('.wpbbshop-v35-img',g); var main=qs('.wpbbshop-v35-main',g); g.setAttribute('data-active',String(n)); qsa('.wpbbshop-v35-thumb',g).forEach(function(b,i){b.classList.toggle('is-active',i===n);b.setAttribute('aria-pressed',i===n?'true':'false')}); if(main) main.style.backgroundImage='url("'+src.replace(/"/g,'%22')+'")'; if(img){img.removeAttribute('srcset');img.removeAttribute('sizes');img.removeAttribute('width');img.removeAttribute('height');img.style.display='block';img.style.visibility='visible';img.style.opacity='1';img.src=src;img.setAttribute('data-full',it.full||src); if(it.alt) img.alt=it.alt;} if(modalOpen) openModal(g,n);}
  function modal(){var lb=qs('.wpbbshop-v35-lightbox'); if(lb)return lb; lb=document.createElement('div'); lb.className='wpbbshop-v35-lightbox'; lb.hidden=true; lb.innerHTML='<button type="button" class="wpbbshop-v35-close" aria-label="Aizvērt">×</button><button type="button" class="wpbbshop-v35-arrow wpbbshop-v35-prev" aria-label="Iepriekšējais">‹</button><figure class="wpbbshop-v35-stage"><img class="wpbbshop-v35-modal-img" alt=""></figure><button type="button" class="wpbbshop-v35-arrow wpbbshop-v35-next" aria-label="Nākamais">›</button><div class="wpbbshop-v35-modal-thumbs"></div>'; document.body.appendChild(lb); return lb;}
  function openModal(g,n){var arr=items(g); if(!arr.length)return; n=norm(n,arr); setActive(g,n,false); var it=arr[n], lb=modal(), img=qs('.wpbbshop-v35-modal-img',lb), thumbs=qs('.wpbbshop-v35-modal-thumbs',lb); lb.setAttribute('data-active',String(n)); lb._gallery=g; img.src=it.full||it.main||it.thumb; img.alt=it.alt||''; thumbs.innerHTML=arr.map(function(x,i){return '<button type="button" class="wpbbshop-v35-modal-thumb '+(i===n?'is-active':'')+'" data-index="'+i+'"><img src="'+(x.thumb||x.main||x.full)+'" alt=""></button>'}).join(''); lb.hidden=false; document.body.classList.add('wpbbshop-v35-open');}
  function close(){var lb=qs('.wpbbshop-v35-lightbox'); if(lb)lb.hidden=true; document.body.classList.remove('wpbbshop-v35-open');}
  function sourceGallery(){var lb=qs('.wpbbshop-v35-lightbox'); return (lb && lb._gallery) || qs('body.single-product .wpbbshop-v35-gallery');}
  ready(function(){qsa('body.single-product .wpbbshop-v35-gallery').forEach(function(g){setActive(g,parseInt(g.getAttribute('data-active')||'0',10)||0,false)});});
  document.addEventListener('click',function(e){
    var t=e.target.closest&&e.target.closest('body.single-product .wpbbshop-v35-thumb'); if(t){e.preventDefault();e.stopImmediatePropagation();setActive(t.closest('.wpbbshop-v35-gallery'),t.getAttribute('data-index'),false);return;}
    var z=e.target.closest&&e.target.closest('body.single-product .wpbbshop-v35-zoom, body.single-product .wpbbshop-v35-img, body.single-product .wpbbshop-v35-main'); if(z){e.preventDefault();e.stopImmediatePropagation();var g=z.closest('.wpbbshop-v35-gallery');openModal(g,parseInt(g.getAttribute('data-active')||'0',10)||0);return;}
    var mt=e.target.closest&&e.target.closest('.wpbbshop-v35-modal-thumb'); if(mt){e.preventDefault();e.stopImmediatePropagation();setActive(sourceGallery(),mt.getAttribute('data-index'),true);return;}
    if(e.target.closest&&e.target.closest('.wpbbshop-v35-prev')){e.preventDefault();var g1=sourceGallery();setActive(g1,(parseInt(g1.getAttribute('data-active')||'0',10)||0)-1,true);return;}
    if(e.target.closest&&e.target.closest('.wpbbshop-v35-next')){e.preventDefault();var g2=sourceGallery();setActive(g2,(parseInt(g2.getAttribute('data-active')||'0',10)||0)+1,true);return;}
    if(e.target.closest&&e.target.closest('.wpbbshop-v35-close')){e.preventDefault();close();return;}
    if(e.target.classList&&e.target.classList.contains('wpbbshop-v35-lightbox')) close();
  },true);
  document.addEventListener('keydown',function(e){if(!document.body.classList.contains('wpbbshop-v35-open'))return;var g=sourceGallery();if(e.key==='Escape')close();if(e.key==='ArrowLeft'){e.preventDefault();setActive(g,(parseInt(g.getAttribute('data-active')||'0',10)||0)-1,true)}if(e.key==='ArrowRight'){e.preventDefault();setActive(g,(parseInt(g.getAttribute('data-active')||'0',10)||0)+1,true)}});
})();

/* WP BB HOME & GARDEN V36: IWS-style takeover for native WooCommerce block/gallery markup */
(function(){
  'use strict';
  function qs(s,c){return (c||document).querySelector(s);}
  function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s));}
  function ready(fn){if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn);}else{fn();}}
  function cleanUrl(v){return (v||'').replace(/&amp;/g,'&');}
  function readItems(g){
    var seen={}, items=[];
    qsa('.woocommerce-product-gallery__image img',g).forEach(function(img){
      var full=cleanUrl(img.getAttribute('data-large_image')||img.getAttribute('data-src')||img.closest('a')&&img.closest('a').href||img.currentSrc||img.src);
      var main=cleanUrl(img.getAttribute('data-src')||img.getAttribute('data-large_image')||img.currentSrc||img.src||full);
      var thumb=cleanUrl(img.getAttribute('data-thumb')||img.currentSrc||img.src||main);
      if(!full && !main && !thumb) return;
      var key=full||main||thumb; if(seen[key]) return; seen[key]=true;
      items.push({main:main||full||thumb, full:full||main||thumb, thumb:thumb||main||full, alt:img.alt||''});
    });
    if(!items.length){
      qsa('ol.flex-control-thumbs img',g).forEach(function(img){
        var src=cleanUrl(img.currentSrc||img.src); if(!src||seen[src]) return; seen[src]=true; items.push({main:src,full:src,thumb:src,alt:img.alt||''});
      });
    }
    return items;
  }
  function escAttr(s){return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}
  function make(g){
    if(!g || g.getAttribute('data-wpbbshop-v36')==='1') return;
    var items=readItems(g); if(!items.length) return;
    g.setAttribute('data-wpbbshop-v36','1');
    var html='<div class="wpbbshop-woo-fixed-gallery" data-active="0"><button type="button" class="wpbbshop-woo-zoom" aria-label="Atvērt lielo attēlu"></button><div class="wpbbshop-woo-fixed-main"><img class="wpbbshop-woo-fixed-img" src="'+escAttr(items[0].main)+'" data-full="'+escAttr(items[0].full)+'" alt="'+escAttr(items[0].alt)+'"></div><div class="wpbbshop-woo-fixed-thumbs" role="list" aria-label="Produkta attēli">';
    items.forEach(function(it,i){html+='<button type="button" class="wpbbshop-woo-fixed-thumb '+(i===0?'is-active':'')+'" data-index="'+i+'" data-main="'+escAttr(it.main)+'" data-full="'+escAttr(it.full)+'" data-thumb="'+escAttr(it.thumb)+'" aria-pressed="'+(i===0?'true':'false')+'"><img src="'+escAttr(it.thumb)+'" alt="'+escAttr(it.alt)+'"></button>';});
    html+='</div><script type="application/json" class="wpbbshop-woo-gallery-data">'+JSON.stringify(items).replace(/</g,'\\u003c')+'</script></div>';
    g.insertAdjacentHTML('afterbegin',html);
  }
  function allGalleries(){return qsa('body.single-product .woocommerce-product-gallery');}
  function itemsFromFixed(f){
    var data=qs('.wpbbshop-woo-gallery-data',f); if(data){try{var arr=JSON.parse(data.textContent||'[]'); if(arr&&arr.length) return arr;}catch(e){}}
    return qsa('.wpbbshop-woo-fixed-thumb',f).map(function(b){var im=qs('img',b);return {main:b.getAttribute('data-main')||(im&&im.src)||'',full:b.getAttribute('data-full')||b.getAttribute('data-main')||(im&&im.src)||'',thumb:b.getAttribute('data-thumb')||(im&&im.src)||'',alt:(im&&im.alt)||''};});
  }
  function norm(n,arr){n=parseInt(n,10)||0;if(!arr.length)return 0;if(n<0)n=arr.length-1;if(n>=arr.length)n=0;return n;}
  function setActive(f,n,open){if(!f)return;var arr=itemsFromFixed(f); if(!arr.length)return; n=norm(n,arr); var it=arr[n], src=it.main||it.full||it.thumb; f.setAttribute('data-active',String(n)); qsa('.wpbbshop-woo-fixed-thumb',f).forEach(function(b,i){b.classList.toggle('is-active',i===n);b.setAttribute('aria-pressed',i===n?'true':'false');}); var img=qs('.wpbbshop-woo-fixed-img',f); if(img){img.removeAttribute('srcset');img.removeAttribute('sizes');img.style.display='block';img.style.visibility='visible';img.style.opacity='1';img.src=src;img.setAttribute('data-full',it.full||src); if(it.alt) img.alt=it.alt;} if(open) openModal(f,n);}
  function modal(){var lb=qs('.wpbbshop-woo-lightbox'); if(lb)return lb; lb=document.createElement('div'); lb.className='wpbbshop-woo-lightbox'; lb.hidden=true; lb.innerHTML='<button type="button" class="wpbbshop-woo-close" aria-label="Aizvērt">×</button><button type="button" class="wpbbshop-woo-arrow wpbbshop-woo-prev" aria-label="Iepriekšējais">‹</button><figure class="wpbbshop-woo-stage"><img class="wpbbshop-woo-modal-img" alt=""></figure><button type="button" class="wpbbshop-woo-arrow wpbbshop-woo-next" aria-label="Nākamais">›</button><div class="wpbbshop-woo-modal-thumbs"></div>'; document.body.appendChild(lb); return lb;}
  function openModal(f,n){var arr=itemsFromFixed(f); if(!arr.length)return; n=norm(n,arr); setActive(f,n,false); var it=arr[n], lb=modal(), img=qs('.wpbbshop-woo-modal-img',lb), thumbs=qs('.wpbbshop-woo-modal-thumbs',lb); lb._gallery=f; lb.setAttribute('data-active',String(n)); img.src=it.full||it.main||it.thumb; img.alt=it.alt||''; thumbs.innerHTML=arr.map(function(x,i){return '<button type="button" class="wpbbshop-woo-modal-thumb '+(i===n?'is-active':'')+'" data-index="'+i+'"><img src="'+escAttr(x.thumb||x.main||x.full)+'" alt="'+escAttr(x.alt||'')+'"></button>';}).join(''); lb.hidden=false; document.body.classList.add('wpbbshop-woo-lightbox-open');}
  function close(){var lb=qs('.wpbbshop-woo-lightbox'); if(lb)lb.hidden=true; document.body.classList.remove('wpbbshop-woo-lightbox-open');}
  function source(){var lb=qs('.wpbbshop-woo-lightbox'); return (lb&&lb._gallery)||qs('body.single-product .wpbbshop-woo-fixed-gallery');}
  ready(function(){allGalleries().forEach(make); qsa('body.single-product .wpbbshop-woo-fixed-gallery').forEach(function(f){setActive(f,0,false);});});
  window.addEventListener('load',function(){allGalleries().forEach(make);});
  document.addEventListener('click',function(e){
    var th=e.target.closest&&e.target.closest('body.single-product .wpbbshop-woo-fixed-thumb'); if(th){e.preventDefault();e.stopImmediatePropagation();setActive(th.closest('.wpbbshop-woo-fixed-gallery'),th.getAttribute('data-index'),false);return;}
    var z=e.target.closest&&e.target.closest('body.single-product .wpbbshop-woo-zoom, body.single-product .wpbbshop-woo-fixed-main'); if(z){e.preventDefault();e.stopImmediatePropagation();var f=z.closest('.wpbbshop-woo-fixed-gallery');openModal(f,parseInt(f.getAttribute('data-active')||'0',10)||0);return;}
    var mt=e.target.closest&&e.target.closest('.wpbbshop-woo-modal-thumb'); if(mt){e.preventDefault();e.stopImmediatePropagation();setActive(source(),mt.getAttribute('data-index'),true);return;}
    if(e.target.closest&&e.target.closest('.wpbbshop-woo-prev')){e.preventDefault();var f1=source();setActive(f1,(parseInt(f1.getAttribute('data-active')||'0',10)||0)-1,true);return;}
    if(e.target.closest&&e.target.closest('.wpbbshop-woo-next')){e.preventDefault();var f2=source();setActive(f2,(parseInt(f2.getAttribute('data-active')||'0',10)||0)+1,true);return;}
    if(e.target.closest&&e.target.closest('.wpbbshop-woo-close')){e.preventDefault();close();return;}
    if(e.target.classList&&e.target.classList.contains('wpbbshop-woo-lightbox')) close();
  },true);
  document.addEventListener('keydown',function(e){if(!document.body.classList.contains('wpbbshop-woo-lightbox-open'))return;var f=source();if(e.key==='Escape')close();if(e.key==='ArrowLeft'){e.preventDefault();setActive(f,(parseInt(f.getAttribute('data-active')||'0',10)||0)-1,true);}if(e.key==='ArrowRight'){e.preventDefault();setActive(f,(parseInt(f.getAttribute('data-active')||'0',10)||0)+1,true);}});
})();

/* V38 isolated gallery controller */
(function(){
  'use strict';
  function qs(s,c){return (c||document).querySelector(s)}
  function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))}
  function ready(fn){if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',fn);else fn()}
  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;')}
  function data(g){var d=qs('.m38gallery-data',g); if(d){try{var a=JSON.parse(d.textContent||'[]'); if(a&&a.length)return a}catch(e){}} return qsa('.m38gallery-thumb',g).map(function(b){var im=qs('img',b);return {main:b.getAttribute('data-main')||(im&&im.src)||'',full:b.getAttribute('data-full')||b.getAttribute('data-main')||(im&&im.src)||'',thumb:b.getAttribute('data-thumb')||(im&&im.src)||'',alt:(im&&im.alt)||''}})}
  function norm(i,a){i=parseInt(i,10)||0;if(!a.length)return 0;if(i<0)i=a.length-1;if(i>=a.length)i=0;return i}
  function setActive(g,i,open){if(!g)return;var a=data(g);if(!a.length)return;i=norm(i,a);var it=a[i]||{},src=it.main||it.full||it.thumb;g.setAttribute('data-active',String(i));var stage=qs('.m38gallery-stage',g),img=qs('.m38gallery-img',g);if(stage&&src)stage.style.backgroundImage='url("'+src.replace(/"/g,'%22')+'")';if(img&&src){img.removeAttribute('srcset');img.removeAttribute('sizes');img.style.display='block';img.style.visibility='visible';img.style.opacity='1';img.src=src;img.setAttribute('data-full',it.full||src);if(it.alt)img.alt=it.alt;}qsa('.m38gallery-thumb',g).forEach(function(b,n){b.classList.toggle('is-active',n===i);b.setAttribute('aria-pressed',n===i?'true':'false')});if(open)openModal(g,i)}
  function modal(){var lb=qs('.m38gallery-lightbox'); if(lb)return lb; lb=document.createElement('div'); lb.className='m38gallery-lightbox'; lb.hidden=true; lb.innerHTML='<button type="button" class="m38close" aria-label="Aizvērt">×</button><button type="button" class="m38arrow m38prev" aria-label="Iepriekšējais">‹</button><figure class="m38stage"><img class="m38modal-img" alt=""></figure><button type="button" class="m38arrow m38next" aria-label="Nākamais">›</button><div class="m38modal-thumbs"></div>'; document.body.appendChild(lb); return lb; }
  function openModal(g,i){var a=data(g);if(!a.length)return;i=norm(i,a);setActive(g,i,false);var it=a[i]||{},lb=modal(),img=qs('.m38modal-img',lb),thumbs=qs('.m38modal-thumbs',lb);lb._gallery=g;lb.setAttribute('data-active',String(i));img.src=it.full||it.main||it.thumb;img.alt=it.alt||'';thumbs.innerHTML=a.map(function(x,n){return '<button type="button" class="m38modal-thumb '+(n===i?'is-active':'')+'" data-index="'+n+'"><img src="'+esc(x.thumb||x.main||x.full)+'" alt=""></button>'}).join('');lb.hidden=false;document.body.classList.add('m38gallery-open')}
  function close(){var lb=qs('.m38gallery-lightbox'); if(lb)lb.hidden=true; document.body.classList.remove('m38gallery-open')}
  function source(){var lb=qs('.m38gallery-lightbox'); return (lb&&lb._gallery)||qs('body.single-product .m38gallery')}
  ready(function(){qsa('body.single-product .m38gallery').forEach(function(g){setActive(g,0,false)})});
  document.addEventListener('click',function(e){var t=e.target.closest&&e.target.closest('body.single-product .m38gallery-thumb'); if(t){e.preventDefault();e.stopImmediatePropagation();setActive(t.closest('.m38gallery'),t.getAttribute('data-index'),false);return}var z=e.target.closest&&e.target.closest('body.single-product .m38gallery-zoom, body.single-product .m38gallery-stage'); if(z){e.preventDefault();e.stopImmediatePropagation();var g=z.closest('.m38gallery');openModal(g,g.getAttribute('data-active')||0);return}var mt=e.target.closest&&e.target.closest('.m38modal-thumb'); if(mt){e.preventDefault();e.stopImmediatePropagation();setActive(source(),mt.getAttribute('data-index'),true);return}if(e.target.closest&&e.target.closest('.m38prev')){e.preventDefault();var g1=source();setActive(g1,(parseInt(g1.getAttribute('data-active')||'0',10)||0)-1,true);return}if(e.target.closest&&e.target.closest('.m38next')){e.preventDefault();var g2=source();setActive(g2,(parseInt(g2.getAttribute('data-active')||'0',10)||0)+1,true);return}if(e.target.closest&&e.target.closest('.m38close')){e.preventDefault();close();return}if(e.target.classList&&e.target.classList.contains('m38gallery-lightbox'))close()},true);
  document.addEventListener('keydown',function(e){if(!document.body.classList.contains('m38gallery-open'))return;var g=source();if(e.key==='Escape')close();if(e.key==='ArrowLeft'){e.preventDefault();setActive(g,(parseInt(g.getAttribute('data-active')||'0',10)||0)-1,true)}if(e.key==='ArrowRight'){e.preventDefault();setActive(g,(parseInt(g.getAttribute('data-active')||'0',10)||0)+1,true)}});
})();


/* WP BB HOME & GARDEN V39 gallery hover-zoom and background duplicate cleanup */
(function(){
  function ready(fn){ if(document.readyState!=='loading') fn(); else document.addEventListener('DOMContentLoaded',fn); }
  function qs(s,c){ return (c||document).querySelector(s); }
  function qsa(s,c){ return Array.prototype.slice.call((c||document).querySelectorAll(s)); }
  ready(function(){
    qsa('body.single-product .m38gallery').forEach(function(g){
      var stage=qs('.m38gallery-stage',g), img=qs('.m38gallery-img',g);
      if(!stage||!img) return;
      stage.style.backgroundImage='none';
      img.style.display='block'; img.style.visibility='visible'; img.style.opacity='1';
      function cleanup(){ stage.style.backgroundImage='none'; qsa('.zoomImg', stage).forEach(function(z){z.remove();}); }
      cleanup(); setTimeout(cleanup,200); setTimeout(cleanup,800);
      stage.addEventListener('mousemove',function(e){
        var r=stage.getBoundingClientRect();
        if(!r.width||!r.height) return;
        var x=((e.clientX-r.left)/r.width)*100, y=((e.clientY-r.top)/r.height)*100;
        x=Math.max(0,Math.min(100,x)); y=Math.max(0,Math.min(100,y));
        img.style.transformOrigin=x+'% '+y+'%';
        stage.classList.add('is-zooming');
      });
      stage.addEventListener('mouseleave',function(){
        stage.classList.remove('is-zooming');
        img.style.transformOrigin='center center';
      });
      g.addEventListener('click',function(){ setTimeout(cleanup,30); setTimeout(cleanup,200); },true);
    });
  });
})();

/* WP BB HOME & GARDEN 2026-09 checkout customer type + delivery field switching */
(function($){
  'use strict';
  function toggleWrap(id, show){
    var el=document.getElementById(id);
    if(!el) return;
    el.classList.toggle('wpbbshop-is-hidden', !show);
    el.querySelectorAll('input,select,textarea').forEach(function(input){
      input.disabled=!show;
    });
  }
  function selectedShipping(){
    var input=document.querySelector('input[name^="shipping_method"]:checked');
    if(!input){ input=document.querySelector('input[name^="shipping_method"]'); }
    return input ? String(input.value||'') : '';
  }
  function shippingBase(value){
    return String(value||'').split(':')[0];
  }
  function lockerProvider(method){
    var map={
      'wpbbshop_omniva':'omniva',
      'wpbbshop_unisend_parcel':'unisend',
      'wpbbshop_latvijas_pasts':'latvijas-pasts'
    };
    return map[shippingBase(method)]||'';
  }
  function isParcel(method){
    return ['wpbbshop_omniva','wpbbshop_unisend_parcel','wpbbshop_latvijas_pasts','wpbbshop_parcel'].indexOf(shippingBase(method))!==-1;
  }
  function isCourier(method){
    return ['wpbbshop_unisend_courier','wpbbshop_oversize_courier','wpbbshop_courier','wpbbshop_royal_mail','wpbbshop_evri','wpbbshop_dpd'].indexOf(shippingBase(method))!==-1;
  }
  function fillLockers(method){
    var provider=document.getElementById('wpbbshop_locker_provider');
    var list=document.getElementById('wpbbshop-locker-options');
    if(!provider||!list) return;
    var selected=lockerProvider(method);
    if(selected){ provider.value=selected; }
    var source=(window.WpbbshopTheme&&WpbbshopTheme.lockers&&WpbbshopTheme.lockers[provider.value])||[];
    list.innerHTML='';
    source.forEach(function(label){
      var option=document.createElement('option');
      option.value=label;
      list.appendChild(option);
    });
  }
  function updateWpbbshopCheckoutFields(){
    var type=document.getElementById('billing_customer_type');
    var company=type && type.value==='company';
    ['billing_first_name_field','billing_last_name_field'].forEach(function(id){ toggleWrap(id,!company); });
    ['billing_company_field','billing_company_id_field','billing_legal_address_field'].forEach(function(id){ toggleWrap(id,!!company); });

    var shipping=selectedShipping();
    var parcel=isParcel(shipping);
    var courier=isCourier(shipping);
    var parcelBox=document.querySelector('.wpbbshop-parcel-fields');
    var courierBox=document.querySelector('.wpbbshop-courier-fields');
    var section=document.querySelector('.wpbbshop-delivery-fields');
    if(parcelBox){ parcelBox.classList.toggle('wpbbshop-is-hidden',!parcel); parcelBox.querySelectorAll('input,select').forEach(function(el){ el.disabled=!parcel; }); }
    if(courierBox){ courierBox.classList.toggle('wpbbshop-is-hidden',!courier); courierBox.querySelectorAll('input,select').forEach(function(el){ el.disabled=!courier; }); }
    if(section){ section.classList.toggle('wpbbshop-is-hidden', !(parcel||courier)); }
    fillLockers(shipping);
  }
  $(function(){ updateWpbbshopCheckoutFields(); });
  $(document.body).on('updated_checkout', updateWpbbshopCheckoutFields);
  $(document).on('change','#billing_customer_type,input[name^="shipping_method"]',function(){
    updateWpbbshopCheckoutFields();
    if(this && this.id==='billing_customer_type' && document.body.classList.contains('woocommerce-checkout')){
      $(document.body).trigger('update_checkout');
    }
  });
})(jQuery);

/* WP BB HOME & GARDEN 1.4.4: keep the cart shipping calculator fixed to Latvia. */
(function($){
  'use strict';
  function forceLatviaCalculator(){
    /* The legacy storefront was Latvia-only. Do not rewrite the shipping
       calculator when the active market is the UK; doing so can trigger
       repeated WooCommerce cart recalculations and slow page updates. */
    if(document.body && document.body.classList.contains('wpbb-v415-market-uk')) return;
    var country=document.getElementById('calc_shipping_country');
    if(!country) return;
    var changed=String(country.value||'').toUpperCase()!=='LV';
    country.value='LV';
    if(changed){ $(country).trigger('change'); }
  }
  $(forceLatviaCalculator);
  $(document.body).on('updated_wc_div updated_cart_totals country_to_state_changed', forceLatviaCalculator);
  $(document).on('click','.shipping-calculator-button',function(){ setTimeout(forceLatviaCalculator,0); });
})(jQuery);

/* WP BB HOME & GARDEN Green Storefront 2.0: mobile/touch catalogue toggle. */
document.addEventListener('DOMContentLoaded', function () {
  var wrap = document.querySelector('.llg-catalog-wrap');
  if (!wrap) return;
  var button = wrap.querySelector('.llg-catalog-button');
  if (!button) return;
  button.addEventListener('click', function (event) {
    event.preventDefault();
    var open = wrap.classList.toggle('is-open');
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  document.addEventListener('click', function (event) {
    if (!wrap.contains(event.target)) {
      wrap.classList.remove('is-open');
      button.setAttribute('aria-expanded', 'false');
    }
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      wrap.classList.remove('is-open');
      button.setAttribute('aria-expanded', 'false');
    }
  });
});


/* 3.0.3: contain third-party chat icons; never hide body/html/ancestors. */
(function(){
  function normalizeChatIcons(){
    var q='a[href*="wa.me"] svg,a[href*="api.whatsapp.com"] svg,a[href*="whatsapp.com/send"] svg,[class*="whatsapp"] svg,[id*="whatsapp"] svg,[class*="joinchat"] svg,[id*="qlwapp"] svg,[class*="ht-ctc"] svg';
    document.querySelectorAll(q).forEach(function(svg){
      svg.style.setProperty('width','32px','important');
      svg.style.setProperty('height','32px','important');
      svg.style.setProperty('max-width','32px','important');
      svg.style.setProperty('max-height','32px','important');
    });
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',normalizeChatIcons);}else{normalizeChatIcons();}
  window.addEventListener('load',normalizeChatIcons);
  setTimeout(normalizeChatIcons,700);
})();


/* 3.0.4: clean scroll-to-top + safe replacement for malformed WhatsApp widgets. */
(function(){
  function ready(fn){if(document.readyState!=='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
  ready(function(){
    var topButton=document.querySelector('.llg-scroll-top');
    function updateTop(){if(!topButton)return;topButton.classList.toggle('is-visible',(window.pageYOffset||document.documentElement.scrollTop||0)>420);}
    if(topButton){
      updateTop();
      window.addEventListener('scroll',updateTop,{passive:true});
      topButton.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});
    }

    function hideThirdPartyWhatsApp(){
      var direct=['#qlwapp','.qlwapp','.joinchat','.ht-ctc','.ht-ctc-chat','#ht-ctc-chat','.wa__btn_popup','.whatsapp_chat_support','.whatsapp-widget','.whatsapp-chat','[data-id="whatsapp"]'];
      document.querySelectorAll(direct.join(',')).forEach(function(el){if(!el.classList.contains('llg-whatsapp-fab'))el.style.setProperty('display','none','important');});
      document.querySelectorAll('a[href*="wa.me"],a[href*="api.whatsapp.com"],a[href*="whatsapp.com/send"]').forEach(function(anchor){
        if(anchor.classList.contains('llg-whatsapp-fab'))return;
        var el=anchor;
        for(var i=0;i<6;i++){
          var parent=el.parentElement;
          if(!parent||parent===document.body||parent===document.documentElement)break;
          var token=((parent.id||'')+' '+(typeof parent.className==='string'?parent.className:'')).toLowerCase();
          var pos=''; try{pos=window.getComputedStyle(parent).position;}catch(e){}
          if(/whatsapp|joinchat|qlwapp|ht-ctc|wa__|chat-support|chat_widget/.test(token)||pos==='fixed'){
            parent.style.setProperty('display','none','important');
            return;
          }
          el=parent;
        }
        anchor.style.setProperty('display','none','important');
      });
    }
    hideThirdPartyWhatsApp();
    window.addEventListener('load',hideThirdPartyWhatsApp);
    setTimeout(hideThirdPartyWhatsApp,700);
    setTimeout(hideThirdPartyWhatsApp,1800);
  });
})();


/* 3.0.5 safe floating-widget cleanup. */
(function(){
  function ready(fn){if(document.readyState!=='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
  function clean(){
    document.querySelectorAll('.wp-theme-quote-floating').forEach(function(el){el.classList.add('llg-quote-left');});
    document.querySelectorAll('a[href*="wa.me"],a[href*="api.whatsapp.com"],a[href*="whatsapp.com/send"]').forEach(function(anchor){
      if(anchor.classList.contains('llg-whatsapp-fab')) return;
      var node=anchor;
      for(var i=0;i<5;i++){
        var parent=node.parentElement;
        if(!parent||parent===document.body||parent===document.documentElement||parent.tagName==='FOOTER') break;
        var text=(parent.textContent||'').replace(/\s+/g,' ').trim().toLowerCase();
        var token=((parent.id||'')+' '+(typeof parent.className==='string'?parent.className:'')).toLowerCase();
        if(text.indexOf('usually replies soon')!==-1 || /whatsapp|joinchat|qlwapp|ht-ctc|wa__/.test(token)){
          parent.classList.add('llg-thirdparty-whatsapp-hidden');
          return;
        }
        node=parent;
      }
      anchor.classList.add('llg-thirdparty-whatsapp-hidden');
    });
  }
  ready(clean);
  window.addEventListener('load',clean);
  setTimeout(clean,800);
  setTimeout(clean,1800);
})();


/* 3.0.6: remove false top/bottom gaps without hiding page containers. */
(function(){
  function ready(fn){if(document.readyState!=='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
  function adminBarState(){
    var body=document.body, bar=document.getElementById('wpadminbar');
    if(!body) return;
    var visible=false;
    if(bar){
      try{
        var cs=window.getComputedStyle(bar), r=bar.getBoundingClientRect();
        visible=cs.display!=='none' && cs.visibility!=='hidden' && r.height>10 && r.width>10;
      }catch(e){}
    }
    body.classList.toggle('llg-adminbar-visible',visible);
    document.documentElement.style.setProperty('margin-top','0px','important');
  }
  function cleanAfterFooter(){
    var footer=document.querySelector('.llg-footer');
    if(!footer || !footer.parentElement) return;
    var node=footer.nextElementSibling;
    while(node){
      var next=node.nextElementSibling;
      var tag=(node.tagName||'').toLowerCase();
      var cls=(typeof node.className==='string'?node.className:'').toLowerCase();
      var id=(node.id||'').toLowerCase();
      var text=(node.textContent||'').replace(/\s+/g,' ').trim().toLowerCase();
      var allowed=node.classList && (node.classList.contains('llg-whatsapp-fab') || node.classList.contains('llg-scroll-top') || node.classList.contains('wp-theme-quote-floating'));
      var hasWa=false;
      try{hasWa=!!node.querySelector('a[href*="wa.me"],a[href*="api.whatsapp.com"],a[href*="whatsapp.com/send"]');}catch(e){}
      var widgetLike=/whatsapp|joinchat|qlwapp|ht-ctc|wa__|chat-support|chat_widget/.test(cls+' '+id) || /usually replies soon|start whatsapp chat|hi, how can we help|hi, i would like to chat/.test(text) || hasWa;
      if(!allowed && tag!=='script' && tag!=='style' && tag!=='link' && tag!=='template' && widgetLike){
        node.classList.add('llg-after-footer-widget-hidden');
        node.setAttribute('aria-hidden','true');
      }
      node=next;
    }
  }
  function run(){adminBarState();cleanAfterFooter();}
  ready(run);
  window.addEventListener('load',run);
  setTimeout(run,500);
  setTimeout(run,1600);
})();

/* 3.0.7: remove empty template-part spacer nodes and provide a reliable mobile menu. */
(function(){
  function ready(fn){if(document.readyState!=='loading'){fn();}else{document.addEventListener('DOMContentLoaded',fn);}}
  function isEmptySpacer(node){
    if(!node || node.nodeType!==1) return false;
    var tag=(node.tagName||'').toLowerCase();
    if(tag==='br') return true;
    if(tag!=='p') return false;
    var html=(node.innerHTML||'').replace(/&nbsp;|&#160;/gi,'').replace(/<br\s*\/?\s*>/gi,'').replace(/\s+/g,'');
    return html==='';
  }
  function cleanTemplateParts(){
    document.querySelectorAll('header.wp-block-template-part,footer.wp-block-template-part').forEach(function(part){
      Array.prototype.slice.call(part.children||[]).forEach(function(child){if(isEmptySpacer(child)){child.remove();}});
      var children=Array.prototype.slice.call(part.children||[]).filter(function(child){return !isEmptySpacer(child) && !/^(script|style|link|template)$/i.test(child.tagName||'');});
      if(children.length===1){
        var child=children[0];
        if((part.tagName==='HEADER' && child.classList.contains('llg-header')) || (part.tagName==='FOOTER' && child.classList.contains('llg-footer'))){
          part.parentNode.insertBefore(child,part);part.remove();
        }
      }
    });
  }
  function closeMobileMenu(){
    var panel=document.getElementById('llg-mobile-menu-panel');
    var toggle=document.querySelector('.llg-mobile-menu-toggle');
    if(panel) panel.setAttribute('hidden','hidden');
    if(toggle) toggle.setAttribute('aria-expanded','false');
    if(document.body) document.body.classList.remove('llg-mobile-menu-open');
  }
  function initMobileMenu(){
    var panel=document.getElementById('llg-mobile-menu-panel');
    var toggle=document.querySelector('.llg-mobile-menu-toggle');
    if(!panel||!toggle) return;
    toggle.addEventListener('click',function(e){
      e.preventDefault();e.stopPropagation();
      var opening=panel.hasAttribute('hidden');
      if(opening){
        panel.removeAttribute('hidden');toggle.setAttribute('aria-expanded','true');document.body.classList.add('llg-mobile-menu-open');
        var catalog=document.querySelector('.llg-catalog-wrap');if(catalog){catalog.classList.remove('is-open');var cb=catalog.querySelector('.llg-catalog-button');if(cb)cb.setAttribute('aria-expanded','false');}
      }else{closeMobileMenu();}
    });
    panel.addEventListener('click',function(e){e.stopPropagation();});
    var close=panel.querySelector('.llg-mobile-menu-close');if(close)close.addEventListener('click',function(e){e.preventDefault();closeMobileMenu();});
    panel.querySelectorAll('a').forEach(function(a){a.addEventListener('click',closeMobileMenu);});
    document.addEventListener('click',function(e){if(!panel.hasAttribute('hidden')&&!toggle.contains(e.target)&&!panel.contains(e.target)){closeMobileMenu();}});
    document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMobileMenu();});
    window.addEventListener('resize',function(){if(window.innerWidth>820)closeMobileMenu();},{passive:true});
  }
  ready(function(){cleanTemplateParts();initMobileMenu();});
  window.addEventListener('load',cleanTemplateParts);
  setTimeout(cleanTemplateParts,500);
})();

/* 3.0.33: final mobile search geometry guard, including cached front-page markup. */
(function () {
  'use strict';
  function ll3033FixMobileSearch() {
    if (!window.matchMedia || !window.matchMedia('(max-width:1049px)').matches) { return; }
    var form = document.querySelector('#wpbbshop-mobile-header form.llg-mobile-search');
    if (!form) { return; }
    var cat = form.querySelector('.llg-mobile-search-cat');
    var field = form.querySelector('.llg-mobile-search-field');
    var input = field ? field.querySelector('input[type="search"]') : null;
    var button = form.querySelector('button[type="submit"]');
    var narrow = window.matchMedia('(max-width:390px)').matches;
    var catWidth = narrow ? '94px' : '106px';
    var buttonWidth = narrow ? '42px' : '46px';

    form.style.setProperty('box-sizing', 'border-box', 'important');
    form.style.setProperty('position', 'relative', 'important');
    form.style.setProperty('display', 'flex', 'important');
    form.style.setProperty('flex-flow', 'row nowrap', 'important');
    form.style.setProperty('align-items', 'stretch', 'important');
    form.style.setProperty('width', 'calc(100% - 16px)', 'important');
    form.style.setProperty('max-width', 'calc(100% - 16px)', 'important');
    form.style.setProperty('height', '46px', 'important');
    form.style.setProperty('min-height', '46px', 'important');
    form.style.setProperty('max-height', '46px', 'important');
    form.style.setProperty('margin', '0 8px 8px', 'important');
    form.style.setProperty('padding', '0', 'important');
    form.style.setProperty('border', '1px solid #d7e0e5', 'important');
    form.style.setProperty('border-radius', '9px', 'important');
    form.style.setProperty('background', '#fff', 'important');

    if (cat) {
      cat.style.setProperty('box-sizing', 'border-box', 'important');
      cat.style.setProperty('flex', '0 0 ' + catWidth, 'important');
      cat.style.setProperty('width', catWidth, 'important');
      cat.style.setProperty('max-width', catWidth, 'important');
      cat.style.setProperty('height', '44px', 'important');
      cat.style.setProperty('min-height', '44px', 'important');
      cat.style.setProperty('max-height', '44px', 'important');
    }
    if (field) {
      field.style.setProperty('box-sizing', 'border-box', 'important');
      field.style.setProperty('display', 'block', 'important');
      field.style.setProperty('flex', '1 1 auto', 'important');
      field.style.setProperty('width', 'auto', 'important');
      field.style.setProperty('min-width', '0', 'important');
      field.style.setProperty('height', '44px', 'important');
      field.style.setProperty('min-height', '44px', 'important');
      field.style.setProperty('max-height', '44px', 'important');
    }
    if (input) {
      input.style.setProperty('box-sizing', 'border-box', 'important');
      input.style.setProperty('display', 'block', 'important');
      input.style.setProperty('width', '100%', 'important');
      input.style.setProperty('height', '44px', 'important');
      input.style.setProperty('min-height', '44px', 'important');
      input.style.setProperty('max-height', '44px', 'important');
      input.style.setProperty('margin', '0', 'important');
      input.style.setProperty('padding', '0 10px', 'important');
    }
    if (button) {
      button.style.setProperty('box-sizing', 'border-box', 'important');
      button.style.setProperty('position', 'relative', 'important');
      button.style.setProperty('inset', 'auto', 'important');
      button.style.setProperty('align-self', 'flex-start', 'important');
      button.style.setProperty('flex', '0 0 ' + buttonWidth, 'important');
      button.style.setProperty('display', 'grid', 'important');
      button.style.setProperty('place-items', 'center', 'important');
      button.style.setProperty('width', buttonWidth, 'important');
      button.style.setProperty('min-width', buttonWidth, 'important');
      button.style.setProperty('max-width', buttonWidth, 'important');
      button.style.setProperty('height', '44px', 'important');
      button.style.setProperty('min-height', '44px', 'important');
      button.style.setProperty('max-height', '44px', 'important');
      button.style.setProperty('margin', '0', 'important');
      button.style.setProperty('padding', '0', 'important');
      button.style.setProperty('border', '0', 'important');
      button.style.setProperty('border-left', '1px solid #278a45', 'important');
      button.style.setProperty('border-radius', '0 8px 8px 0', 'important');
      button.style.setProperty('background', '#2f9d50', 'important');
      button.style.setProperty('background-image', 'none', 'important');
      button.style.setProperty('color', '#fff', 'important');
      button.style.setProperty('box-shadow', 'none', 'important');
      button.style.setProperty('transform', 'none', 'important');
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ll3033FixMobileSearch, { once: true });
  } else {
    ll3033FixMobileSearch();
  }
  window.addEventListener('pageshow', ll3033FixMobileSearch);
  window.addEventListener('resize', ll3033FixMobileSearch);
}());
