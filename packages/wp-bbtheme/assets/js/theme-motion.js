(function(){
  'use strict';

  function motionConfig(){
    var defaults={enabled:true,duration:'760ms',delay:'0ms',disableOnMobile:false,respectReducedMotion:true};
    var supplied=window.WPThemeMotion||{};
    Object.keys(supplied).forEach(function(key){defaults[key]=supplied[key];});
    return defaults;
  }
  function truthy(value){return value===true||value===1||value==='1'||value==='true'||value==='yes'||value==='on';}
  function falsy(value){return value===false||value===0||value==='0'||value==='false'||value==='no'||value==='off';}
  function prefersReduced(){return window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;}
  function isMobile(){return window.matchMedia&&window.matchMedia('(max-width: 767px)').matches;}

  var observer=null;
  var root=document.documentElement;
  var targets=[
    ['.motion-fade-up,.wp-theme-section-shell,.wp-theme-blog-browser,.wp-theme-related-posts,.wp-theme-article-body,.wp-theme-history-section,.wp-theme-home-cta,.wp-theme-contact-section,.wp-theme-sitemap-section,.wp-theme-forms-showcase','fade-up'],
    ['.motion-fade-left,.wp-theme-section-heading,.wp-theme-inner-hero .wpbb-column:first-child,.wp-theme-blog-hero .container,.wp-theme-article-head__inner,.wp-theme-sector-media-text>.wpbb-column:last-child,.wp-theme-contact-details','fade-left'],
    ['.motion-fade-right,.wp-theme-sector-media-text>.wpbb-column:first-child,.wp-theme-about-page-intro .wp-theme-sector-media-text__media,.wp-theme-article-media,.wp-theme-contact-map','fade-right'],
    ['.motion-scale-in,.motion-zoom-soft,.wp-theme-gallery-section .wpbb-swiper-block,.wp-theme-item-gallery--single','zoom-soft'],
    ['.wpbb-swiper--hero .wpbb-swiper-slide__content','fade-left'],
    ['.wpbb-swiper--hero .wpbb-swiper-slide__media','fade-right'],
    ['.wp-theme-article-toc,.wp-theme-article-author,.wp-theme-article-navigation,.wp-theme-article-next-step,.wp-theme-footer-newsletter','fade-up']
  ];
  var staggerGroups=[
    '.wp-theme-sector-services>.wpbb-column',
    '.wp-theme-sector-industries>.wpbb-column',
    '.wp-theme-sector-process-grid>.wpbb-column',
    '.wp-theme-sector-proof>.wpbb-column',
    '.wp-theme-case-grid>.wpbb-column',
    '.wp-theme-gallery-section .wpbb-swiper-slide',
    '.wp-theme-blog-list>.wp-theme-blog-list-card',
    '.wp-theme-related-posts__grid>.wp-theme-blog-list-card',
    '.wpbb-sector-results>*',
    '.wp-theme-sitemap-grid>*',
    '.wp-theme-contact-details>*',
    '.woocommerce ul.products>li.product',
    '.products>.product',
    '.wp-theme-form-pattern',
    '.wp-theme-footer-main .row>[class*="col-"]'
  ];
  var floatTargets=[
    '.wp-theme-about-section .wp-theme-sector-media-text__media img',
    '.wp-theme-about-page-intro .wp-theme-sector-media-text__media img',
    '.wp-theme-contact-map img'
  ];

  function skip(element){
    if(!element||!element.classList)return true;
    return !!element.closest('.wp-admin,.wp-theme-item-lightbox,[data-cookie-settings-dialog],[data-install-dialog],.wp-theme-site-header');
  }
  function addTarget(element,type){
    if(skip(element)||element.dataset.wpThemeMotionBound==='1')return;
    element.dataset.wpThemeMotionBound='1';
    element.dataset.wpThemeMotion=element.dataset.wpThemeMotion||type||'fade-up';
    element.classList.add('wp-theme-motion-item');
  }
  function collect(){
    targets.forEach(function(group){document.querySelectorAll(group[0]).forEach(function(element){addTarget(element,group[1]);});});
    document.querySelectorAll('[data-wp-theme-motion]').forEach(function(element){addTarget(element,element.getAttribute('data-wp-theme-motion')||'fade-up');});
    staggerGroups.forEach(function(selector){
      document.querySelectorAll(selector).forEach(function(element,index){
        addTarget(element,element.dataset.wpThemeMotion||'fade-up');
        element.style.setProperty('--wp-theme-motion-delay',Math.min(index*70,560)+'ms');
      });
    });
    document.querySelectorAll(floatTargets.join(',')).forEach(function(element,index){
      if(skip(element)||element.dataset.wpThemeFloatBound==='1')return;
      element.dataset.wpThemeFloatBound='1';
      element.classList.add('wp-theme-motion-float');
      element.style.setProperty('--wp-theme-float-delay',(index%4)*180+'ms');
    });
    return Array.prototype.slice.call(document.querySelectorAll('.wp-theme-motion-item:not(.is-visible)'));
  }
  function revealAll(items){items.forEach(function(element){element.classList.add('is-visible');});}
  function observe(items){if(!observer){revealAll(items);return;}items.forEach(function(element){observer.observe(element);});}
  function collectAndObserve(){observe(collect());}

  function init(){
    var cfg=motionConfig();
    if(falsy(cfg.enabled))return;
    var respect=!falsy(cfg.respectReducedMotion);
    if((respect&&prefersReduced())||(truthy(cfg.disableOnMobile)&&isMobile())){
      root.classList.add('wp-theme-motion-static');
      return;
    }
    root.style.setProperty('--wp-theme-motion-duration',cfg.duration||'760ms');
    root.style.setProperty('--wp-theme-motion-base-delay',cfg.delay||'0ms');
    if(document.body)document.body.classList.add('wp-theme-motion-active');
    root.classList.add('wp-theme-motion-ready');

    var items=collect();
    if(!items.length)return;
    if(!('IntersectionObserver' in window)){revealAll(items);return;}
    observer=new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(!entry.isIntersecting)return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    },{rootMargin:'0px 0px -8% 0px',threshold:0.12});
    observe(items);

    if('MutationObserver' in window){
      new MutationObserver(function(){window.requestAnimationFrame(collectAndObserve);}).observe(document.body,{childList:true,subtree:true});
    }
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
  else init();
})();
