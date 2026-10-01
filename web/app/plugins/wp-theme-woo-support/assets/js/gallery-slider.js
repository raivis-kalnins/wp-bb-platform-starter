(function () {
  function ready(callback) {
    if (document.readyState !== 'loading') callback();
    else document.addEventListener('DOMContentLoaded', callback);
  }

  ready(function () {
    document.querySelectorAll('.iws-product-gallery-slider').forEach(function (gallery) {
      const settings = window.iwsGallerySliderSettings || {};
      const position = gallery.dataset.thumbPosition || 'left';
      const visible = parseInt(gallery.dataset.thumbsVisible || '6', 10);
      const mainEl = gallery.querySelector('.iws-gallery-main');
      const thumbsEl = gallery.querySelector('.iws-gallery-thumbs');
      const slides = Array.from(gallery.querySelectorAll('.iws-product-gallery-slider__slide'));
      const thumbs = Array.from(gallery.querySelectorAll('.iws-product-gallery-slider__thumb'));
      const prev = gallery.querySelector('.iws-product-gallery-slider__arrow--prev');
      const next = gallery.querySelector('.iws-product-gallery-slider__arrow--next');
      const thumbPrev = gallery.querySelector('.iws-product-gallery-slider__thumb-arrow--prev');
      const thumbNext = gallery.querySelector('.iws-product-gallery-slider__thumb-arrow--next');
      const zoomTrigger = gallery.querySelector('.iws-gallery-zoom-trigger');
      const reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const lowMemory = !!(navigator.deviceMemory && navigator.deviceMemory <= 4);
      const performanceMode = !!settings.performance && (reducedMotion || lowMemory);
      let active = 0;
      let mainSwiper = null;
      let thumbsSwiper = null;
      let pointerZoomActive = false;
      let isSyncingState = false;

      const initialSlides = slides.map(function (slide) {
        const img = slide.querySelector('img');
        const link = slide.querySelector('a');
        return {
          full: slide.dataset.full || (link ? link.href : ''),
          src: img ? (img.currentSrc || img.src) : '',
          srcset: img ? (img.getAttribute('srcset') || '') : '',
          sizes: img ? (img.getAttribute('sizes') || '') : '',
          alt: img ? (img.getAttribute('alt') || '') : '',
          thumb: thumbs[parseInt(slide.dataset.index || '0', 10)] ? thumbs[parseInt(slide.dataset.index || '0', 10)].innerHTML : ''
        };
      });

      if (performanceMode) gallery.classList.add('is-performance-mode');

      function isMobileLayout() {
        return window.matchMedia('(max-width: 768px)').matches;
      }
      function isDesktopVertical() {
        return !isMobileLayout() && (position === 'left' || position === 'right');
      }
      function getGap() {
        return parseInt(getComputedStyle(gallery).getPropertyValue('--iws-gallery-gap'), 10) || 10;
      }
      function maxHeight() {
        return parseInt(settings.maxHeight || getComputedStyle(gallery).getPropertyValue('--iws-gallery-max-height') || '540', 10) || 540;
      }
      function activeSlide() {
        return slides[active] || slides[0];
      }
      function isVideo(slide) {
        return slide && slide.dataset.type === 'video';
      }
      function syncMainHeight() {
        if (!mainEl) return;
        if (isMobileLayout()) {
          mainEl.style.height = '';
          mainEl.style.maxHeight = '';
          return;
        }
        const cap = maxHeight();
        if (settings.heightMode === 'fixed') {
          mainEl.style.height = cap + 'px';
          mainEl.style.maxHeight = cap + 'px';
          return;
        }
        const slide = activeSlide();
        if (isVideo(slide)) {
          mainEl.style.height = Math.min(cap, Math.max(360, Math.round(mainEl.clientWidth * 0.5625))) + 'px';
          mainEl.style.maxHeight = cap + 'px';
          return;
        }
        const width = parseInt(slide && slide.dataset.width || '0', 10);
        const height = parseInt(slide && slide.dataset.height || '0', 10);
        const containerWidth = mainEl.clientWidth || mainEl.getBoundingClientRect().width || 600;
        const ratioHeight = width && height ? Math.round(containerWidth * (height / width)) : cap;
        mainEl.style.height = Math.min(cap, Math.max(320, ratioHeight)) + 'px';
        mainEl.style.maxHeight = cap + 'px';
      }
      function syncThumbWrapSize() {
        const wrap = gallery.querySelector('.iws-product-gallery-slider__thumbs-wrap');
        if (!wrap) return;
        if (isMobileLayout()) {
          wrap.style.height = '';
          wrap.style.maxHeight = '';
          return;
        }
        if (isDesktopVertical() && mainEl) {
          const mainHeight = Math.min(maxHeight(), Math.max(280, Math.round(mainEl.getBoundingClientRect().height || maxHeight())));
          wrap.style.height = mainHeight + 'px';
          wrap.style.maxHeight = mainHeight + 'px';
        } else {
          wrap.style.height = '';
          wrap.style.maxHeight = '';
        }
      }
      function updateLayoutSizes(options) {
        const opts = options || {};
        syncMainHeight();
        syncThumbWrapSize();
        if (!opts.skipSwiperUpdate) {
          if (mainSwiper && mainSwiper.update) mainSwiper.update();
          if (thumbsSwiper && thumbsSwiper.update) thumbsSwiper.update();
        }
        updateThumbArrowState();
      }
      function updateThumbsMode() {
        updateLayoutSizes({ skipSwiperUpdate: true });
        if (!thumbsSwiper) return;
        const vertical = isDesktopVertical();
        try {
          if (thumbsSwiper.changeDirection) thumbsSwiper.changeDirection(vertical ? 'vertical' : 'horizontal', false);
          thumbsSwiper.params.slidesPerView = vertical ? visible : 'auto';
          thumbsSwiper.params.freeMode = { enabled: true, momentum: !performanceMode, sticky: false };
          thumbsSwiper.params.mousewheel = { forceToAxis: true, sensitivity: vertical ? 0.65 : 0.45 };
          if (thumbsSwiper.updateSize) thumbsSwiper.updateSize();
          if (thumbsSwiper.updateSlides) thumbsSwiper.updateSlides();
          thumbsSwiper.update();
          if (thumbsSwiper.slideTo) thumbsSwiper.slideTo(active, 0);
          updateThumbArrowState();
        } catch (e) {}
      }
      function zoomElement(slide) {
        return slide ? (slide.querySelector('.iws-product-gallery-slider__zoom-target') || slide.querySelector('a') || slide) : null;
      }
      function destroyZoom(slide) {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.trigger) return;
        const target = zoomElement(slide);
        if (!target) return;
        try { window.jQuery(target).trigger('zoom.destroy'); } catch (e) {}
      }
      function initWooZoom(slide) {
        if (!settings.wooZoom || performanceMode || isVideo(slide) || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.zoom) return;
        const target = zoomElement(slide);
        const full = slide && (slide.dataset.full || (target && (target.dataset.large_image || target.href)));
        if (!target || !full || isMobileLayout()) return;
        destroyZoom(slide);
        window.jQuery(target).zoom({ url: full, touch: false, magnify: 1 });
      }
      function lazyLoadAround(index) {
        [index, index + 1, index - 1].forEach(function (i) {
          if (i < 0 || i >= slides.length) return;
          const img = slides[i].querySelector('img');
          if (!img) return;
          const src = img.getAttribute('data-iws-src');
          const srcset = img.getAttribute('data-iws-srcset');
          if (src && !img.src) img.src = src;
          if (srcset && !img.getAttribute('srcset')) img.setAttribute('srcset', srcset);
        });
      }
      function stopVideos() {
        slides.forEach(function (slide) {
          const iframe = slide.querySelector('iframe');
          const video = slide.querySelector('video');
          if (iframe) iframe.remove();
          if (video) { try { video.pause(); } catch (e) {} }
          slide.classList.remove('is-video-playing');
        });
      }
      function playVideo(slide) {
        if (!slide || !isVideo(slide)) return;
        stopVideos();
        const url = slide.dataset.video || slide.dataset.full || '';
        if (!url) return;
        slide.classList.add('is-video-playing');
        if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(url)) {
          const video = document.createElement('video');
          video.controls = true;
          video.autoplay = true;
          video.playsInline = true;
          video.src = url;
          slide.appendChild(video);
        } else {
          const iframe = document.createElement('iframe');
          iframe.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
          iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
          iframe.allowFullscreen = true;
          iframe.loading = 'lazy';
          slide.appendChild(iframe);
        }
      }
      function syncState(index) {
        if (isSyncingState) return;
        isSyncingState = true;
        active = Math.max(0, Math.min(index, slides.length - 1));
        stopVideos();
        lazyLoadAround(active);
        slides.forEach(function (slide, i) {
          const on = i === active;
          slide.classList.toggle('is-active', on);
          slide.setAttribute('aria-hidden', on ? 'false' : 'true');
          destroyZoom(slide);
          if (on) initWooZoom(slide);
        });
        thumbs.forEach(function (thumb, i) {
          const on = i === active;
          thumb.classList.toggle('is-active', on);
          thumb.setAttribute('aria-selected', on ? 'true' : 'false');
          if (on && !mainSwiper) thumb.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
        updateLayoutSizes({ skipSwiperUpdate: true });
        isSyncingState = false;
      }
      function go(index) {
        if (!slides.length) return;
        const doLoop = !!settings.loop && !performanceMode;
        if (doLoop) index = (index + slides.length) % slides.length;
        else index = Math.max(0, Math.min(index, slides.length - 1));
        if (mainSwiper) {
          const current = typeof mainSwiper.realIndex === 'number' ? mainSwiper.realIndex : mainSwiper.activeIndex;
          if (current === index) {
            syncState(index);
            return;
          }
          if (mainSwiper.slideToLoop && doLoop) mainSwiper.slideToLoop(index);
          else mainSwiper.slideTo(index);
          return;
        }
        syncState(index);
      }

      if (settings.useSwiper && window.Swiper && mainEl && thumbsEl) {
        gallery.classList.add('is-swiper-active');
        const gap = getGap();
        thumbsSwiper = new window.Swiper(thumbsEl, {
          direction: isDesktopVertical() ? 'vertical' : 'horizontal',
          slidesPerView: isDesktopVertical() ? visible : 'auto',
          spaceBetween: gap,
          watchSlidesProgress: true,
          freeMode: { enabled: true, momentum: !performanceMode, sticky: false },
          grabCursor: true,
          mousewheel: { forceToAxis: true, sensitivity: 0.65 },
          touchRatio: performanceMode ? 0.9 : 1.18,
          threshold: 3,
          slideToClickedSlide: true,
          navigation: { prevEl: thumbPrev, nextEl: thumbNext },
          breakpoints: {
            0: { direction: 'horizontal', slidesPerView: 'auto', freeMode: { enabled: true, momentum: !performanceMode } },
            769: { direction: (position === 'left' || position === 'right') ? 'vertical' : 'horizontal', slidesPerView: (position === 'left' || position === 'right') ? visible : 'auto' }
          }
        });
        mainSwiper = new window.Swiper(mainEl, {
          loop: !!settings.loop && !performanceMode,
          speed: performanceMode ? Math.min(parseInt(settings.speed || '250', 10), 200) : parseInt(settings.speed || '250', 10),
          slidesPerView: 1,
          spaceBetween: 0,
          autoHeight: false,
          grabCursor: true,
          resistanceRatio: 0.65,
          touchRatio: performanceMode ? 0.9 : 1.08,
          threshold: 4,
          keyboard: { enabled: true, onlyInViewport: true },
          navigation: { prevEl: prev, nextEl: next },
          thumbs: { swiper: thumbsSwiper },
          on: {
            init: function (swiper) { syncState(swiper.realIndex || 0); },
            slideChange: function (swiper) { if (!isSyncingState) syncState(swiper.realIndex || swiper.activeIndex || 0); }
          }
        });
        thumbs.forEach(function (thumb) { thumb.addEventListener('click', function (event) {
          event.preventDefault();
          go(parseInt(thumb.dataset.index || '0', 10));
        }); });
        updateThumbsMode();
        if (thumbsSwiper.on) thumbsSwiper.on('update resize transitionEnd sliderMove setTranslate touchEnd reachBeginning reachEnd fromEdge', updateThumbArrowState);
        setTimeout(updateThumbArrowState, 80);
        window.addEventListener('resize', debounce(function () { updateThumbsMode(); }, 120));
        window.addEventListener('orientationchange', function () { setTimeout(updateThumbsMode, 180); });
      } else {
        gallery.classList.add('is-fallback-active');
        thumbs.forEach(function (thumb) { thumb.addEventListener('click', function () { go(parseInt(thumb.dataset.index || '0', 10)); }); });
        if (prev) prev.addEventListener('click', function () { go(active - 1); });
        if (next) next.addEventListener('click', function () { go(active + 1); });
        addSwipe(mainEl, function () { go(active + 1); }, function () { go(active - 1); });
        if (thumbsEl) thumbsEl.addEventListener('scroll', updateThumbArrowState, { passive: true });
        window.addEventListener('resize', debounce(updateLayoutSizes, 120));
        go(0);
        setTimeout(updateThumbArrowState, 80);
      }

      function addSwipe(el, onNext, onPrev) {
        if (!el) return;
        let startX = 0, startY = 0, startTime = 0;
        el.addEventListener('touchstart', function (event) {
          if (!event.touches || !event.touches.length) return;
          startX = event.touches[0].clientX;
          startY = event.touches[0].clientY;
          startTime = Date.now();
        }, { passive: true });
        el.addEventListener('touchend', function (event) {
          if (!event.changedTouches || !event.changedTouches.length) return;
          const dx = event.changedTouches[0].clientX - startX;
          const dy = event.changedTouches[0].clientY - startY;
          if (Math.abs(dx) < 34 || Math.abs(dx) < Math.abs(dy) || Date.now() - startTime > 750) return;
          if (dx < 0) onNext(); else onPrev();
        }, { passive: true });
      }
      function debounce(fn, wait) {
        let timer = null;
        return function () {
          clearTimeout(timer);
          timer = setTimeout(fn, wait);
        };
      }
      function setArrowState(button, disabled) {
        if (!button) return;
        button.classList.toggle('is-hidden', !!disabled);
        button.disabled = !!disabled;
        button.setAttribute('aria-hidden', disabled ? 'true' : 'false');
        button.setAttribute('tabindex', disabled ? '-1' : '0');
      }
      function updateThumbArrowState() {
        if (!thumbPrev || !thumbNext) return;
        if (thumbsSwiper && !thumbsSwiper.destroyed) {
          try {
            if (thumbsSwiper.updateSize) thumbsSwiper.updateSize();
            if (thumbsSwiper.updateSlides) thumbsSwiper.updateSlides();
          } catch (e) {}
          const vertical = isDesktopVertical();
          const wrapper = thumbsEl ? thumbsEl.querySelector('.swiper-wrapper') : null;
          const domOverflow = thumbsEl && wrapper ? (vertical ? (wrapper.scrollHeight > thumbsEl.clientHeight + 2) : (wrapper.scrollWidth > thumbsEl.clientWidth + 2)) : false;
          const start = typeof thumbsSwiper.minTranslate === 'function' ? thumbsSwiper.minTranslate() : 0;
          const end = typeof thumbsSwiper.maxTranslate === 'function' ? thumbsSwiper.maxTranslate() : start;
          const current = typeof thumbsSwiper.getTranslate === 'function' ? thumbsSwiper.getTranslate() : (thumbsSwiper.translate || 0);
          const hasOverflow = domOverflow || Math.abs(start - end) > 2;
          const atStart = typeof thumbsSwiper.isBeginning === 'boolean' ? thumbsSwiper.isBeginning : current >= start - 2;
          const atEnd = typeof thumbsSwiper.isEnd === 'boolean' ? thumbsSwiper.isEnd : current <= end + 2;
          setArrowState(thumbPrev, !hasOverflow || atStart);
          setArrowState(thumbNext, !hasOverflow || atEnd);
          return;
        }
        if (!thumbsEl) {
          setArrowState(thumbPrev, true);
          setArrowState(thumbNext, true);
          return;
        }
        const vertical = isDesktopVertical();
        const maxScroll = vertical ? (thumbsEl.scrollHeight - thumbsEl.clientHeight) : (thumbsEl.scrollWidth - thumbsEl.clientWidth);
        const current = vertical ? thumbsEl.scrollTop : thumbsEl.scrollLeft;
        const hasOverflow = maxScroll > 2;
        setArrowState(thumbPrev, !hasOverflow || current <= 2);
        setArrowState(thumbNext, !hasOverflow || current >= maxScroll - 2);
      }
      function scrollThumbs(direction) {
        const amount = (parseInt(getComputedStyle(gallery).getPropertyValue('--iws-gallery-thumb-width'), 10) || 72) + getGap();
        const vertical = isDesktopVertical();
        if (thumbsSwiper && !thumbsSwiper.destroyed) {
          if (thumbsSwiper.updateSize) thumbsSwiper.updateSize();
          if (thumbsSwiper.updateSlides) thumbsSwiper.updateSlides();
          const current = typeof thumbsSwiper.getTranslate === 'function' ? thumbsSwiper.getTranslate() : (thumbsSwiper.translate || 0);
          const start = typeof thumbsSwiper.minTranslate === 'function' ? thumbsSwiper.minTranslate() : 0;
          const end = typeof thumbsSwiper.maxTranslate === 'function' ? thumbsSwiper.maxTranslate() : current;
          let target = current - (direction * amount);
          target = Math.max(end, Math.min(start, target));
          if (Math.abs(target - current) > 1 && thumbsSwiper.translateTo) thumbsSwiper.translateTo(target, 220, true, true);
          else if (direction < 0 && thumbsSwiper.slidePrev) thumbsSwiper.slidePrev(220);
          else if (direction > 0 && thumbsSwiper.slideNext) thumbsSwiper.slideNext(220);
          setTimeout(updateThumbArrowState, 40);
          setTimeout(updateThumbArrowState, 260);
          return;
        }
        if (!thumbsEl) return;
        if (vertical) thumbsEl.scrollBy({ top: direction * amount, behavior: 'smooth' });
        else thumbsEl.scrollBy({ left: direction * amount, behavior: 'smooth' });
        setTimeout(updateThumbArrowState, 240);
      }
      if (thumbPrev) thumbPrev.addEventListener('click', function () { scrollThumbs(-1); });
      if (thumbNext) thumbNext.addEventListener('click', function () { scrollThumbs(1); });

      gallery.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft') go(active - 1);
        if (event.key === 'ArrowRight') go(active + 1);
      });

      function lightboxMedia(slide) {
        if (isVideo(slide)) {
          const url = slide.dataset.video || slide.dataset.full || '';
          if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(url)) return '<video class="iws-gallery-lightbox__video" controls autoplay playsinline src="' + escapeAttr(url) + '"></video>';
          return '<iframe class="iws-gallery-lightbox__video" src="' + escapeAttr(url + (url.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1') + '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
        }
        const img = slide.querySelector('img');
        const src = slide.dataset.full || (slide.querySelector('a') ? slide.querySelector('a').href : '');
        return '<img class="iws-gallery-lightbox__image" src="' + escapeAttr(src) + '" alt="' + escapeAttr(img ? (img.getAttribute('alt') || '') : '') + '">';
      }
      function escapeAttr(value) {
        return String(value || '').replace(/[&<>"]/g, function (char) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char]; });
      }
      function openLightbox(startIndex) {
        if (!settings.lightbox || !slides.length) return;
        let lightboxIndex = typeof startIndex === 'number' ? startIndex : active;
        const overlay = document.createElement('div');
        overlay.className = 'iws-gallery-lightbox';
        overlay.innerHTML = '<button type="button" class="iws-gallery-lightbox__close" aria-label="' + (settings.closeLabel || 'Close gallery') + '">×</button><button type="button" class="iws-gallery-lightbox__nav iws-gallery-lightbox__nav--prev" aria-label="Previous image">‹</button><figure class="iws-gallery-lightbox__figure"><div class="iws-gallery-lightbox__media"></div><figcaption class="iws-gallery-lightbox__count"></figcaption></figure><button type="button" class="iws-gallery-lightbox__nav iws-gallery-lightbox__nav--next" aria-label="Next image">›</button><div class="iws-gallery-lightbox__thumbs"></div>';
        const media = overlay.querySelector('.iws-gallery-lightbox__media');
        const count = overlay.querySelector('.iws-gallery-lightbox__count');
        const thumbWrap = overlay.querySelector('.iws-gallery-lightbox__thumbs');
        slides.forEach(function (slide, i) {
          const img = slide.querySelector('img');
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'iws-gallery-lightbox__thumb' + (isVideo(slide) ? ' is-video-thumb' : '');
          button.innerHTML = isVideo(slide) ? '<span class="iws-product-gallery-slider__video-thumb"></span>' : (img ? '<img loading="lazy" src="' + (img.currentSrc || img.src) + '" alt="">' : '');
          button.addEventListener('click', function () { setLightbox(i); });
          thumbWrap.appendChild(button);
        });
        function setLightbox(index) {
          const doLoop = !!settings.loop && !performanceMode;
          if (doLoop) index = (index + slides.length) % slides.length;
          else index = Math.max(0, Math.min(index, slides.length - 1));
          lightboxIndex = index;
          media.innerHTML = lightboxMedia(slides[lightboxIndex]);
          count.textContent = (lightboxIndex + 1) + ' / ' + slides.length;
          Array.from(thumbWrap.children).forEach(function (thumb, i) { thumb.classList.toggle('is-active', i === lightboxIndex); });
          const activeThumb = thumbWrap.children[lightboxIndex];
          if (activeThumb) activeThumb.scrollIntoView({ block: 'nearest', inline: 'center' });
        }
        function close() { overlay.remove(); document.body.classList.remove('iws-gallery-lightbox-open'); document.removeEventListener('keydown', onKeydown); }
        function onKeydown(event) {
          if (event.key === 'Escape') close();
          if (event.key === 'ArrowLeft') setLightbox(lightboxIndex - 1);
          if (event.key === 'ArrowRight') setLightbox(lightboxIndex + 1);
        }
        addSwipe(overlay.querySelector('.iws-gallery-lightbox__figure'), function () { setLightbox(lightboxIndex + 1); }, function () { setLightbox(lightboxIndex - 1); });
        overlay.querySelector('.iws-gallery-lightbox__close').addEventListener('click', close);
        overlay.querySelector('.iws-gallery-lightbox__nav--prev').addEventListener('click', function () { setLightbox(lightboxIndex - 1); });
        overlay.querySelector('.iws-gallery-lightbox__nav--next').addEventListener('click', function () { setLightbox(lightboxIndex + 1); });
        overlay.addEventListener('click', function (event) { if (event.target === overlay) close(); });
        document.addEventListener('keydown', onKeydown);
        document.body.appendChild(overlay);
        document.body.classList.add('iws-gallery-lightbox-open');
        setLightbox(lightboxIndex);
      }

      function applyHoverFollowZoom() {
        if (!settings.hoverZoom || performanceMode || isMobileLayout()) return;
        slides.forEach(function (slide) {
          const target = zoomElement(slide);
          const img = slide.querySelector('.iws-product-gallery-slider__image');
          if (!target || !img || isVideo(slide)) return;
          target.addEventListener('pointermove', function (event) {
            if (pointerZoomActive || slide !== activeSlide()) return;
            const rect = target.getBoundingClientRect();
            const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
            const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100));
            img.style.transformOrigin = x + '% ' + y + '%';
          });
          target.addEventListener('pointerenter', function () { pointerZoomActive = false; });
          target.addEventListener('pointerleave', function () { img.style.transformOrigin = 'center center'; });
        });
      }

      function applyVariationImage(variation) {
        if (!variation || !variation.image || !slides.length) return;
        const image = variation.image;
        const firstSlide = slides.find(function (slide) { return !isVideo(slide); }) || slides[0];
        const firstLink = firstSlide.querySelector('a');
        const firstImg = firstSlide.querySelector('img');
        const firstThumbImg = thumbs[0] ? thumbs[0].querySelector('img') : null;
        const full = image.full_src || image.src || '';
        const src = image.src || image.full_src || '';
        destroyZoom(firstSlide);
        firstSlide.dataset.full = full;
        firstSlide.dataset.width = image.full_src_w || firstSlide.dataset.width || '';
        firstSlide.dataset.height = image.full_src_h || firstSlide.dataset.height || '';
        if (firstLink) { firstLink.href = full; firstLink.dataset.large_image = full; }
        if (firstImg) {
          firstImg.src = src;
          firstImg.removeAttribute('srcset');
          if (image.srcset) firstImg.setAttribute('srcset', image.srcset);
          if (image.sizes) firstImg.setAttribute('sizes', image.sizes);
          firstImg.dataset.src = full;
          firstImg.dataset.large_image = full;
          if (image.full_src_w) firstImg.dataset.large_image_width = image.full_src_w;
          if (image.full_src_h) firstImg.dataset.large_image_height = image.full_src_h;
          if (image.alt) firstImg.alt = image.alt;
        }
        if (firstThumbImg) {
          firstThumbImg.src = image.gallery_thumbnail_src || image.thumb_src || src;
          firstThumbImg.removeAttribute('srcset');
          if (image.alt) firstThumbImg.alt = image.alt;
        }
        go(0);
      }
      function resetVariationImage() {
        if (!initialSlides[0] || !slides.length) return;
        const data = initialSlides[0];
        const firstSlide = slides.find(function (slide) { return !isVideo(slide); }) || slides[0];
        const firstLink = firstSlide.querySelector('a');
        const firstImg = firstSlide.querySelector('img');
        destroyZoom(firstSlide);
        firstSlide.dataset.full = data.full;
        if (firstLink) { firstLink.href = data.full; firstLink.dataset.large_image = data.full; }
        if (firstImg) {
          firstImg.src = data.src;
          if (data.srcset) firstImg.setAttribute('srcset', data.srcset); else firstImg.removeAttribute('srcset');
          if (data.sizes) firstImg.setAttribute('sizes', data.sizes); else firstImg.removeAttribute('sizes');
          firstImg.dataset.large_image = data.full;
          firstImg.alt = data.alt;
        }
        if (thumbs[0] && data.thumb) thumbs[0].innerHTML = data.thumb;
        go(0);
      }
      if (window.jQuery) {
        const form = window.jQuery('form.variations_form').first();
        form.on('found_variation', function (event, variation) { applyVariationImage(variation); });
        form.on('reset_data hide_variation', resetVariationImage);
      }

      if (settings.lightbox) {
        if (zoomTrigger) zoomTrigger.addEventListener('click', function () { openLightbox(active); });
        slides.forEach(function (slide, index) {
          const link = slide.querySelector('a');
          if (link) link.addEventListener('click', function (event) { event.preventDefault(); openLightbox(index); });
          const videoButton = slide.querySelector('.iws-product-gallery-slider__video-button');
          if (videoButton) videoButton.addEventListener('click', function () { openLightbox(index); });
        });
      } else {
        slides.forEach(function (slide) {
          const videoButton = slide.querySelector('.iws-product-gallery-slider__video-button');
          if (videoButton) videoButton.addEventListener('click', function () { playVideo(slide); });
        });
      }

      applyHoverFollowZoom();
      syncState(0);
    });
  });
})();
