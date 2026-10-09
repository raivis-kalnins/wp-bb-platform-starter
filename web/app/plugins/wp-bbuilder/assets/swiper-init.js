(function () {
  function wpbbUsesMobileStack(block) {
    return !!block && block.dataset.mobileStack === '1';
  }

  function wpbbIsMobileStackViewport(block) {
    if (!wpbbUsesMobileStack(block)) return false;
    if (window.matchMedia) return window.matchMedia('(max-width: 575.98px)').matches;
    return window.innerWidth <= 575;
  }

  function wpbbSetMobileStack(block) {
    if (!block) return;
    var el = block.querySelector('.swiper');
    if (el && el.swiper && typeof el.swiper.destroy === 'function') {
      try {
        el.swiper.destroy(true, true);
      } catch (e) {}
    }
    delete block.dataset.wpbbSwiperReady;
    block.dataset.wpbbMobileStatic = '1';
  }

  function wpbbInitSwiperBlock(block) {
    if (!window.Swiper || !block) return;
    if (wpbbIsMobileStackViewport(block)) {
      wpbbSetMobileStack(block);
      return;
    }
    delete block.dataset.wpbbMobileStatic;
    if (block.dataset.wpbbSwiperReady === '1') return;
    var el = block.querySelector('.swiper');
    if (!el || el.swiper) return;

    try {
      var slideCount = el.querySelectorAll('.swiper-slide').length;
      var desktop = parseInt(block.dataset.slides || '1', 10) || 1;
      var tablet = parseInt(block.dataset.slidesTablet || block.dataset.slides || '2', 10) || 1;
      var mobile = parseInt(block.dataset.slidesMobile || '1', 10) || 1;
      var wantsLoop = block.dataset.loop === '1';
      var safeLoop = wantsLoop && slideCount > desktop;
      var wantsCentered = block.dataset.centered === '1';
      var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var effect = block.dataset.effect === 'fade' && desktop === 1 ? 'fade' : 'slide';
      var initialSlide = parseInt(block.dataset.initialSlide || '0', 10) || 0;
      if (initialSlide < 0) initialSlide = 0;
      if (initialSlide >= slideCount) initialSlide = 0;

      block.dataset.wpbbSwiperReady = '1';
      new window.Swiper(el, {
        slidesPerView: desktop,
        slidesPerGroup: 1,
        centeredSlides: wantsCentered,
        centerInsufficientSlides: wantsCentered,
        initialSlide: initialSlide,
        spaceBetween: parseInt(block.dataset.space || '20', 10) || 0,
        speed: parseInt(block.dataset.speed || '600', 10) || 600,
        loop: safeLoop,
        rewind: !safeLoop && block.dataset.rewind !== '0',
        effect: effect,
        watchOverflow: true,
        observer: true,
        observeParents: true,
        observeSlideChildren: true,
        autoplay: block.dataset.autoplay === '1' && !reducedMotion ? { delay: parseInt(block.dataset.autoplayDelay || '4500', 10) || 4500, disableOnInteraction: false, pauseOnMouseEnter: block.dataset.pauseHover !== '0' } : false,
        breakpoints: {
          0: { slidesPerView: mobile },
          768: { slidesPerView: tablet },
          992: { slidesPerView: desktop }
        },
        pagination: block.querySelector('.swiper-pagination') ? { el: block.querySelector('.swiper-pagination'), clickable: true } : false,
        navigation: (block.querySelector('.swiper-button-next') && block.querySelector('.swiper-button-prev')) ? { nextEl: block.querySelector('.swiper-button-next'), prevEl: block.querySelector('.swiper-button-prev') } : false
      });
    } catch (e) {
      if (block) delete block.dataset.wpbbSwiperReady;
    }
  }

  function wpbbInitSwipers(root) {
    root = root || document;
    root.querySelectorAll('.wpbb-swiper-block[data-swiper="1"], .wpbb-testimonials[data-swiper="1"], .wpbb-soc-feed[data-swiper="1"]').forEach(wpbbInitSwiperBlock);
  }

  function wpbbSyncMobileStacks(root) {
    root = root || document;
    root.querySelectorAll('.wpbb-soc-feed[data-swiper="1"][data-mobile-stack="1"]').forEach(function (block) {
      if (wpbbIsMobileStackViewport(block)) {
        wpbbSetMobileStack(block);
      } else {
        delete block.dataset.wpbbMobileStatic;
        wpbbInitSwiperBlock(block);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { wpbbInitSwipers(document); });
  } else {
    wpbbInitSwipers(document);
  }

  window.wpbbInitSwipers = wpbbInitSwipers;

  var mobileStackResizeTimer = 0;
  window.addEventListener('resize', function () {
    window.clearTimeout(mobileStackResizeTimer);
    mobileStackResizeTimer = window.setTimeout(function () {
      wpbbSyncMobileStacks(document);
    }, 120);
  });

  if (window.MutationObserver) {
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (!node || node.nodeType !== 1) return;
          if (node.matches && (node.matches('.wpbb-swiper-block[data-swiper="1"]') || node.matches('.wpbb-testimonials[data-swiper="1"]') || node.matches('.wpbb-soc-feed[data-swiper="1"]'))) {
            wpbbInitSwiperBlock(node);
          } else if (node.querySelectorAll) {
            wpbbInitSwipers(node);
          }
        });
      });
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
  }
})();
