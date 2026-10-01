(function () {
  'use strict';

  function departmentColumns() {
    var width = window.innerWidth || document.documentElement.clientWidth || 1280;
    if (width > 1180) return 5;
    if (width > 820) return 4;
    return 2;
  }

  function enforceDepartmentGrid() {
    var grid = document.querySelector('.wpbb-v400-dept-grid');
    if (!grid) return;

    var cols = departmentColumns();
    var gap = cols === 2 ? 5 : 6;
    var usedGap = gap * (cols - 1);
    var basis = 'calc((100% - ' + usedGap + 'px) / ' + cols + ')';

    grid.style.setProperty('display', 'flex', 'important');
    grid.style.setProperty('flex-flow', 'row wrap', 'important');
    grid.style.setProperty('gap', gap + 'px', 'important');
    grid.style.setProperty('align-content', 'flex-start', 'important');
    grid.style.setProperty('justify-content', 'flex-start', 'important');
    grid.style.setProperty('min-height', '0', 'important');
    grid.style.setProperty('height', 'auto', 'important');
    grid.style.setProperty('margin', '0', 'important');

    grid.querySelectorAll(':scope > .wpbb-v400-dept').forEach(function (card) {
      card.style.setProperty('flex', '0 0 ' + basis, 'important');
      card.style.setProperty('width', basis, 'important');
      card.style.setProperty('max-width', basis, 'important');
      card.style.setProperty('grid-column', 'auto', 'important');
      card.style.setProperty('grid-row', 'auto', 'important');
      card.style.setProperty('order', '0', 'important');
      card.style.setProperty('margin', '0', 'important');
      card.style.setProperty('transform', 'none', 'important');
    });
  }

  function enforceHomeSectionFlow() {
    var home = document.querySelector('.wpbb-v400-home');
    if (!home) return;

    home.querySelectorAll('.wpbb-v400-departments, .wpbb-v400-projects, .wpbb-v400-products, .wpbb-v400-services').forEach(function (section) {
      section.style.setProperty('content-visibility', 'visible', 'important');
      section.style.setProperty('contain', 'none', 'important');
      section.style.setProperty('contain-intrinsic-size', 'auto', 'important');
      section.style.setProperty('min-height', '0', 'important');
      section.style.setProperty('height', 'auto', 'important');
    });

    var departments = home.querySelector('.wpbb-v400-departments');
    if (departments) {
      departments.style.setProperty('margin-bottom', '0', 'important');
      departments.style.setProperty('padding-bottom', '0', 'important');
    }

    var projects = home.querySelector('.wpbb-v400-projects');
    if (projects) {
      projects.style.setProperty('margin-top', window.innerWidth <= 820 ? '10px' : '12px', 'important');
      projects.style.setProperty('margin-bottom', '0', 'important');
      projects.style.setProperty('padding-top', '0', 'important');
      projects.style.setProperty('padding-bottom', '0', 'important');
    }

    home.querySelectorAll('.wpbb-v400-products').forEach(function (section) {
      var hasContent = !!section.querySelector('*') || (section.textContent || '').trim() !== '';
      if (!hasContent) {
        section.style.setProperty('display', 'none', 'important');
        section.style.setProperty('padding', '0', 'important');
        section.style.setProperty('margin', '0', 'important');
        return;
      }
      section.style.removeProperty('display');
      section.style.setProperty('margin-top', '0', 'important');
      section.style.setProperty('margin-bottom', '0', 'important');
      section.style.setProperty('padding-top', window.innerWidth <= 820 ? '20px' : '22px', 'important');
      section.style.setProperty('padding-bottom', '0', 'important');
    });
  }

  function compareSvg() {
    return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  }

  function syncCompareButtons() {
    document.querySelectorAll('.iws-compare-open--search').forEach(function (button) {
      button.setAttribute('title', button.getAttribute('aria-label') || 'Compare products');

      var icon = button.querySelector('.iws-compare-icon');
      if (!icon) {
        icon = document.createElement('span');
        icon.className = 'iws-compare-icon';
        icon.setAttribute('aria-hidden', 'true');
        button.insertBefore(icon, button.firstChild);
      }
      if (!icon.querySelector('svg') || icon.getAttribute('data-wpbb-icon') !== '414') {
        icon.innerHTML = compareSvg();
        icon.setAttribute('data-wpbb-icon', '414');
      }

      var countNode = button.querySelector('.iws-compare-count');
      var count = countNode ? parseInt((countNode.textContent || '0').replace(/[^0-9]/g, ''), 10) || 0 : 0;
      var pressed = button.getAttribute('aria-pressed') === 'true';
      var active = count > 0 || pressed || button.classList.contains('is-selected') || button.classList.contains('is-active');

      button.setAttribute('data-wpbb-has-compare', active ? '1' : '0');
      button.classList.toggle('has-products', active);
    });
  }

  var compareQueued = false;
  function queueCompareSync() {
    if (compareQueued) return;
    compareQueued = true;
    window.requestAnimationFrame(function () {
      compareQueued = false;
      syncCompareButtons();
    });
  }

  function boot() {
    enforceDepartmentGrid();
    enforceHomeSectionFlow();
    syncCompareButtons();

    if (document.body && window.MutationObserver) {
      new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var target = mutations[i].target;
          if ((target.nodeType === 1 && (target.matches('.iws-compare-count, .iws-compare-open--search') || target.querySelector && target.querySelector('.iws-compare-open--search'))) ||
              (target.parentElement && target.parentElement.closest('.iws-compare-open--search'))) {
            queueCompareSync();
            break;
          }
        }
      }).observe(document.body, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['class', 'aria-pressed'] });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }

  var resizeTimer;
  window.addEventListener('resize', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(function () {
      enforceDepartmentGrid();
      enforceHomeSectionFlow();
    }, 120);
  });

  document.addEventListener('iws:filter:loaded', queueCompareSync);
  document.addEventListener('iws:filter:updated', queueCompareSync);
  document.addEventListener('iwsProductsUpdated', queueCompareSync);
}());
