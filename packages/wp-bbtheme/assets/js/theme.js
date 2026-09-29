(function(){
  function applyTheme(mode){
    var dark = mode === 'dark';
    document.documentElement.classList.toggle('is-dark-theme', dark);
    document.body && document.body.classList.toggle('is-dark-theme', dark);
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    document.querySelectorAll('[data-wp-theme-toggle]').forEach(function(btn){
      btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
    });
  }

  function getSavedTheme(){
    try { return localStorage.getItem('wpThemeMode'); } catch(e) { return null; }
  }

  function saveTheme(mode){
    try { localStorage.setItem('wpThemeMode', mode); } catch(e) {}
  }

  function bindThemeToggle(){
    document.querySelectorAll('[data-wp-theme-toggle]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var next = document.documentElement.classList.contains('is-dark-theme') ? 'light' : 'dark';
        saveTheme(next);
        applyTheme(next);
        window.dispatchEvent(new CustomEvent('wp-theme:mode-change', {detail:{mode:next}}));
      });
    });
  }

  function bindHeaderMenus(){
    document.querySelectorAll('.wp-theme-menu-shortcode--header').forEach(function(wrapper){
      var button = wrapper.querySelector('.wp-theme-menu-toggle');
      var nav = wrapper.querySelector('.wp-theme-header-nav');
      if (!nav || !button) return;

      button.addEventListener('click', function(){
        var open = wrapper.classList.toggle('is-open');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
      });

      document.addEventListener('click', function(e){
        if (!wrapper.contains(e.target)) {
          wrapper.classList.remove('is-open');
          button.setAttribute('aria-expanded', 'false');
          nav.querySelectorAll('.nav-item.is-open').forEach(function(item){ item.classList.remove('is-open'); });
        }
      });

      nav.querySelectorAll('.menu-item-has-children').forEach(function(item){
        var link = item.querySelector(':scope > a');
        if (!link) return;
        link.addEventListener('click', function(e){
          if (window.innerWidth >= 1200) return;
          e.preventDefault();
          item.classList.toggle('is-open');
        });
      });
    });
  }

  function bindStickyHeader(){
    var header = document.querySelector('.wp-theme-site-header');
    if (!header) return;
    var update = function(){
      header.classList.toggle('is-scrolled', window.scrollY > 12);
    };
    update();
    window.addEventListener('scroll', update, {passive:true});
  }

  function init(){
    var saved = getSavedTheme();
    if (saved) applyTheme(saved);
    bindThemeToggle();
    bindHeaderMenus();
    bindStickyHeader();
    document.querySelectorAll('.btn-back-home').forEach(function (el) {
      el.setAttribute('href', window.wpThemeHome || '/');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
