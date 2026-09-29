(() => {
  'use strict';
  const root = document.documentElement;
  const body = document.body;
  const header = document.querySelector('[data-site-header]');
  if (!header) return;

  const nav = header.querySelector('[data-primary-navigation]');
  const menuToggle = header.querySelector('.wp-theme-menu-toggle');
  const overlay = header.querySelector('[data-header-overlay]');
  const searchToggle = header.querySelector('.wp-theme-search-toggle');
  const searchPanel = document.getElementById('wp-theme-header-search');
  const searchClose = header.querySelector('.wp-theme-search-close');
  const themeToggle = header.querySelector('.wp-theme-theme-toggle');
  const menuOpenIcon = menuToggle?.querySelector('.wp-theme-menu-toggle__open');
  const menuCloseIcon = menuToggle?.querySelector('.wp-theme-menu-toggle__close');
  const themeSunIcon = themeToggle?.querySelector('.wp-theme-theme-toggle__sun');
  const themeMoonIcon = themeToggle?.querySelector('.wp-theme-theme-toggle__moon');
  const desktop = () => window.matchMedia('(min-width: 992px)').matches;

  const scrollTopButton = document.querySelector('[data-scroll-top]');
  const updateScrollTop = () => {
    if (!scrollTopButton) return;
    const visible = window.scrollY > 560;
    scrollTopButton.hidden = !visible;
    scrollTopButton.classList.toggle('is-visible', visible);
  };
  if (scrollTopButton) {
    scrollTopButton.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    });
    window.addEventListener('scroll', updateScrollTop, { passive: true });
    updateScrollTop();
  }

  // Accessible single-open FAQ behaviour. Applies only inside dedicated FAQ groups.
  document.addEventListener('toggle', (event) => {
    const current = event.target;
    if (!(current instanceof HTMLDetailsElement) || !current.open) return;
    const group = current.closest('[data-accordion-single], .wp-theme-faq-section, .wp-theme-faq-list');
    if (!group) return;
    group.querySelectorAll('details[open]').forEach((item) => {
      if (item !== current) item.open = false;
    });
  }, true);

  function closeMega(except) {
    header.querySelectorAll('.menu-item-has-mega.is-mega-open, .menu-item-megamenu.is-mega-open').forEach((item) => {
      if (item === except) return;
      item.classList.remove('is-mega-open', 'is-submenu-open');
      const button = item.querySelector(':scope > .wp-theme-submenu-toggle');
      if (button) button.setAttribute('aria-expanded', 'false');
      const panel = item.querySelector(':scope > .wp-theme-mega-menu');
      if (panel) panel.setAttribute('aria-hidden', 'true');
    });
  }

  function syncMenuButton(open) {
    if (!menuToggle) return;
    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    const label = open ? (menuToggle.dataset.labelClose || 'Close menu') : (menuToggle.dataset.labelOpen || 'Open menu');
    menuToggle.setAttribute('aria-label', label);
    menuToggle.title = label;
    if (menuOpenIcon) menuOpenIcon.hidden = open;
    if (menuCloseIcon) menuCloseIcon.hidden = !open;
  }

  function closeMobileMenu() {
    body.classList.remove('wp-theme-menu-open');
    syncMenuButton(false);
    if (overlay) overlay.hidden = true;
    closeMega();
  }

  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      const open = !body.classList.contains('wp-theme-menu-open');
      body.classList.toggle('wp-theme-menu-open', open);
      syncMenuButton(open);
      if (overlay) overlay.hidden = !open;
      if (open) {
        closeSearch();
        window.setTimeout(() => nav?.querySelector('a')?.focus({ preventScroll: true }), 80);
      }
    });
  }
  if (overlay) overlay.addEventListener('click', closeMobileMenu);
  if (nav) nav.addEventListener('click', (event) => {
    const link = event.target.closest('a');
    if (link && !desktop() && !link.closest('.wp-theme-mega-menu')) closeMobileMenu();
  });

  if (nav) {
    nav.querySelectorAll('.menu-item-has-children, .menu-item-has-mega, .menu-item-megamenu').forEach((item, index) => {
      const link = item.querySelector(':scope > a');
      const panel = item.querySelector(':scope > .wp-theme-mega-menu, :scope > .sub-menu');
      if (!link || !panel) return;
      if (!panel.id) panel.id = `wp-theme-submenu-${index + 1}`;
      let button = item.querySelector(':scope > .wp-theme-submenu-toggle');
      if (!button) {
        button = document.createElement('button');
        button.type = 'button';
        button.className = 'wp-theme-submenu-toggle';
        button.innerHTML = '<span aria-hidden="true"></span><span class="screen-reader-text">Toggle submenu</span>';
        link.insertAdjacentElement('afterend', button);
      }
      button.setAttribute('aria-controls', panel.id);
      button.setAttribute('aria-expanded', 'false');
      panel.setAttribute('aria-hidden', 'true');

      let hoverCloseTimer = 0;
      const setOpen = (open) => {
        if (hoverCloseTimer) {
          window.clearTimeout(hoverCloseTimer);
          hoverCloseTimer = 0;
        }
        if (open) closeMega(item);
        item.classList.toggle('is-submenu-open', open);
        if (item.classList.contains('menu-item-has-mega') || item.classList.contains('menu-item-megamenu')) item.classList.toggle('is-mega-open', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      };
      const scheduleDesktopClose = () => {
        if (!desktop()) return;
        if (hoverCloseTimer) window.clearTimeout(hoverCloseTimer);
        hoverCloseTimer = window.setTimeout(() => setOpen(false), 220);
      };
      button.addEventListener('click', (event) => {
        event.preventDefault();
        setOpen(!item.classList.contains('is-submenu-open'));
      });
      item.addEventListener('mouseenter', () => { if (desktop()) setOpen(true); });
      item.addEventListener('mouseleave', scheduleDesktopClose);
      panel.addEventListener('mouseenter', () => { if (desktop()) setOpen(true); });
      panel.addEventListener('mouseleave', scheduleDesktopClose);
      item.addEventListener('focusin', () => { if (desktop()) setOpen(true); });
      item.addEventListener('focusout', (event) => { if (desktop() && !item.contains(event.relatedTarget)) setOpen(false); });
    });
  }

  function closeSearch() {
    if (!searchPanel) return;
    searchPanel.hidden = true;
    header.classList.remove('is-search-open');
    if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
  }
  function openSearch() {
    if (!searchPanel) return;
    closeMobileMenu();
    searchPanel.hidden = false;
    header.classList.add('is-search-open');
    if (searchToggle) searchToggle.setAttribute('aria-expanded', 'true');
    window.setTimeout(() => searchPanel.querySelector('input[type="search"], input[type="text"]')?.focus(), 40);
  }
  if (searchToggle) searchToggle.addEventListener('click', () => searchPanel && !searchPanel.hidden ? closeSearch() : openSearch());
  if (searchClose) searchClose.addEventListener('click', closeSearch);

  // Keep third-party quote widgets in a dedicated floating slot so they cannot
  // collide with the theme's scroll-to-top and WhatsApp controls.
  const findFloatingQuoteRoot = (node) => {
    let current = node instanceof Element ? node : null;
    while (current && current !== body) {
      const style = window.getComputedStyle(current);
      if (style.position === 'fixed') return current;
      current = current.parentElement;
    }
    return null;
  };
  const normalizeFloatingQuote = () => {
    const selectors = [
      '[class*="quote" i]', '[id*="quote" i]',
      '[aria-label*="quote" i]', '[title*="quote" i]',
      '[class*="wcqb" i]', '[id*="wcqb" i]'
    ].join(',');
    const candidates = new Set(document.querySelectorAll(selectors));
    document.querySelectorAll('a,button').forEach((item) => {
      if (/^my\s+quote\b/i.test((item.textContent || '').trim())) candidates.add(item);
    });
    candidates.forEach((candidate) => {
      const floatRoot = findFloatingQuoteRoot(candidate);
      if (!floatRoot || floatRoot.matches('.wp-theme-scroll-top,.wp-theme-whatsapp-float')) return;
      floatRoot.classList.add('wp-theme-quote-float');
      floatRoot.style.right = window.matchMedia('(max-width: 575.98px)').matches ? '16px' : '24px';
      floatRoot.style.bottom = window.matchMedia('(max-width: 575.98px)').matches ? '132px' : '144px';
      floatRoot.style.zIndex = '1200';
      floatRoot.style.maxWidth = window.matchMedia('(max-width: 575.98px)').matches ? 'calc(100vw - 32px)' : 'calc(100vw - 48px)';
    });
  };
  const quoteObserver = new MutationObserver(() => window.requestAnimationFrame(normalizeFloatingQuote));
  quoteObserver.observe(body, { childList: true, subtree: true });
  window.addEventListener('resize', normalizeFloatingQuote);
  window.setTimeout(normalizeFloatingQuote, 0);
  window.setTimeout(normalizeFloatingQuote, 600);

  const compactLanguageSwitchers = [];
  header.querySelectorAll('[data-language-switcher]').forEach((switcher) => {
    const button = switcher.querySelector('.wp-theme-language-switcher__toggle');
    const menu = switcher.querySelector('.wp-theme-language-switcher__menu');
    if (!button || !menu || switcher.classList.contains('is-expanded')) return;
    compactLanguageSwitchers.push({ switcher, button, menu });
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      compactLanguageSwitchers.forEach((item) => {
        if (item.switcher !== switcher) {
          item.menu.hidden = true;
          item.button.setAttribute('aria-expanded', 'false');
        }
      });
      const open = menu.hidden;
      menu.hidden = !open;
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  const closeLanguageMenus = () => compactLanguageSwitchers.forEach((item) => {
    item.menu.hidden = true;
    item.button.setAttribute('aria-expanded', 'false');
  });

  const storedMode = localStorage.getItem('wpThemeMode');
  const initialDark = storedMode ? storedMode === 'dark' : false;
  const syncThemeButton = (dark) => {
    if (!themeToggle) return;
    // The control shows the action that will happen next: moon in light mode, sun in dark mode.
    if (themeSunIcon) themeSunIcon.hidden = !dark;
    if (themeMoonIcon) themeMoonIcon.hidden = dark;
    const label = dark ? (themeToggle.dataset.labelLight || 'Switch to light mode') : (themeToggle.dataset.labelDark || 'Switch to dark mode');
    themeToggle.setAttribute('aria-label', label);
    themeToggle.title = label;
  };
  root.classList.toggle('is-dark-theme', initialDark);
  root.setAttribute('data-theme', initialDark ? 'dark' : 'light');
  syncThemeButton(initialDark);
  syncMenuButton(body.classList.contains('wp-theme-menu-open'));
  if (themeToggle) themeToggle.addEventListener('click', () => {
    const dark = !root.classList.contains('is-dark-theme');
    root.classList.toggle('is-dark-theme', dark);
    root.setAttribute('data-theme', dark ? 'dark' : 'light');
    localStorage.setItem('wpThemeMode', dark ? 'dark' : 'light');
    syncThemeButton(dark);
  });

  let lastScroll = window.scrollY;
  const updateHeader = () => {
    root.style.setProperty('--wp-theme-header-bottom', `${Math.round(header.getBoundingClientRect().bottom)}px`);
    header.classList.toggle('is-scrolled', window.scrollY > 24);
    header.classList.toggle('is-scroll-down', window.scrollY > lastScroll && window.scrollY > 180);
    lastScroll = window.scrollY;
  };
  window.addEventListener('scroll', updateHeader, { passive: true });
  updateHeader();

  document.addEventListener('click', (event) => {
    if (!header.contains(event.target)) {
      closeMega();
      closeSearch();
      closeLanguageMenus();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMega();
      closeSearch();
      closeLanguageMenus();
      closeMobileMenu();
    }
  });
  const syncResponsiveHeader = () => {
    if (menuToggle) menuToggle.hidden = desktop();
    if (desktop()) closeMobileMenu();
    updateHeader();
  };
  window.addEventListener('resize', syncResponsiveHeader);
  syncResponsiveHeader();
})();
