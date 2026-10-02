(function () {
  'use strict';

  function departmentColumns() {
    var width = window.innerWidth || document.documentElement.clientWidth || 1280;
    if (width > 820) return 4;
    if (width > 480) return 2;
    return 1;
  }

  function enforceDepartmentGrid() {
    var grid = document.querySelector('.wpbb-v400-dept-grid');
    if (!grid) return;

    var cols = departmentColumns();
    var gap = cols === 1 ? 0 : 10;
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
      if (!icon.querySelector('svg') || icon.getAttribute('data-wpbb-icon') !== '414-final4') {
        icon.innerHTML = compareSvg();
        icon.setAttribute('data-wpbb-icon', '414-final4');
      }

      var countNode = button.querySelector('.iws-compare-count');
      var count = countNode ? parseInt((countNode.textContent || '0').replace(/[^0-9]/g, ''), 10) || 0 : 0;
      var pressed = button.getAttribute('aria-pressed') === 'true';
      var active = count > 0 || pressed || button.classList.contains('is-selected') || button.classList.contains('is-active') || button.classList.contains('active');
      var green = '#0f7a49';
      var ink = '#173426';
      var fg = active ? '#ffffff' : green;

      button.setAttribute('data-wpbb-has-compare', active ? '1' : '0');
      button.classList.toggle('has-products', active);

      /* Woo Support 3.5 writes background-color inline with !important after
         compare updates. CSS cannot beat that, so the theme owns the final
         inline state too. */
      button.style.setProperty('background-color', active ? green : '#ffffff', 'important');
      button.style.setProperty('background-image', 'none', 'important');
      button.style.setProperty('border-color', green, 'important');
      button.style.setProperty('color', active ? '#ffffff' : ink, 'important');

      icon.style.setProperty('display', 'inline-flex', 'important');
      icon.style.setProperty('align-items', 'center', 'important');
      icon.style.setProperty('justify-content', 'center', 'important');
      icon.style.setProperty('width', '22px', 'important');
      icon.style.setProperty('height', '22px', 'important');
      icon.style.setProperty('color', fg, 'important');
      icon.style.setProperty('opacity', '1', 'important');
      icon.style.setProperty('visibility', 'visible', 'important');

      icon.querySelectorAll('svg, path, line, polyline').forEach(function (node) {
        node.style.setProperty('color', fg, 'important');
        node.style.setProperty('stroke', fg, 'important');
        node.style.setProperty('fill', 'none', 'important');
        node.style.setProperty('opacity', '1', 'important');
        node.style.setProperty('visibility', 'visible', 'important');
      });

      if (countNode) {
        countNode.style.setProperty('display', active ? 'inline-flex' : 'none', 'important');
        countNode.style.setProperty('background-color', active ? '#0b603a' : green, 'important');
        countNode.style.setProperty('color', '#ffffff', 'important');
        countNode.style.setProperty('border-color', '#ffffff', 'important');
      }
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

  function settleCompareState() {
    queueCompareSync();
    window.setTimeout(syncCompareButtons, 0);
    window.setTimeout(syncCompareButtons, 60);
    window.setTimeout(syncCompareButtons, 180);
  }

  document.addEventListener('click', function (event) {
    if (event.target && event.target.closest && event.target.closest('.iws-compare-toggle, .iws-compare-open--search, .iws-compare-remove, .iws-compare-clear-all')) {
      settleCompareState();
    }
  }, true);
  document.addEventListener('iws_compare_updated', settleCompareState);
  document.addEventListener('iws:filter:loaded', settleCompareState);
  document.addEventListener('iws:filter:updated', settleCompareState);
  document.addEventListener('iwsProductsUpdated', settleCompareState);
  window.addEventListener('storage', function (event) {
    if (!event.key || event.key.toLowerCase().indexOf('compare') !== -1) {
      settleCompareState();
    }
  });
}());
