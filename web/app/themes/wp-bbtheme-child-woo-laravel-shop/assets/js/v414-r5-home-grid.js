(function () {
  'use strict';

  function colsForViewport() {
    var width = window.innerWidth || document.documentElement.clientWidth || 1280;
    if (width > 820) return 4;
    if (width > 480) return 2;
    return 1;
  }

  function applyGrid() {
    var section = document.querySelector('.wpbb-v400-departments');
    var grid = section && section.querySelector('.wpbb-v400-dept-grid');
    if (!grid) return;

    var cols = colsForViewport();
    var sectionWidth = cols === 4 ? 'min(var(--wpbb-v4-shell, 1480px), calc(100% - 48px))' : 'calc(100% - 20px)';

    section.style.setProperty('width', sectionWidth, 'important');
    section.style.setProperty('max-width', cols === 4 ? 'var(--wpbb-v4-shell, 1480px)' : 'none', 'important');
    section.style.setProperty('margin-left', 'auto', 'important');
    section.style.setProperty('margin-right', 'auto', 'important');
    section.style.setProperty('min-height', '0', 'important');
    section.style.setProperty('height', 'auto', 'important');
    section.style.setProperty('contain', 'none', 'important');
    section.style.setProperty('content-visibility', 'visible', 'important');

    grid.style.setProperty('display', 'grid', 'important');
    grid.style.setProperty('grid-template-columns', 'repeat(' + cols + ', minmax(0, 1fr))', 'important');
    grid.style.setProperty('grid-auto-flow', 'row', 'important');
    grid.style.setProperty('grid-auto-rows', '58px', 'important');
    grid.style.setProperty('gap', cols === 1 ? '5px' : '6px', 'important');
    grid.style.setProperty('width', '100%', 'important');
    grid.style.setProperty('max-width', 'none', 'important');
    grid.style.setProperty('min-width', '0', 'important');
    grid.style.setProperty('min-height', '0', 'important');
    grid.style.setProperty('height', 'auto', 'important');
    grid.style.setProperty('margin', '20px 0', 'important');
    grid.style.setProperty('padding', '0', 'important');
    grid.style.setProperty('align-content', 'start', 'important');
    grid.style.setProperty('justify-content', 'stretch', 'important');

    Array.prototype.forEach.call(grid.children, function (card) {
      if (!card.classList || !card.classList.contains('wpbb-v400-dept')) return;
      card.style.setProperty('display', 'grid', 'important');
      card.style.setProperty('grid-column', 'auto', 'important');
      card.style.setProperty('grid-row', 'auto', 'important');
      card.style.setProperty('flex', 'none', 'important');
      card.style.setProperty('width', 'auto', 'important');
      card.style.setProperty('max-width', 'none', 'important');
      card.style.setProperty('min-width', '0', 'important');
      card.style.setProperty('min-height', '58px', 'important');
      card.style.setProperty('height', '58px', 'important');
      card.style.setProperty('margin', '0', 'important');
      card.style.setProperty('transform', 'none', 'important');
      card.style.setProperty('order', '0', 'important');
    });

    var projects = document.querySelector('.wpbb-v400-projects');
    if (projects) {
      projects.style.setProperty('margin-top', cols === 1 ? '8px' : '10px', 'important');
    }
  }

  function settle() {
    applyGrid();
    window.setTimeout(applyGrid, 40);
    window.setTimeout(applyGrid, 180);
    window.setTimeout(applyGrid, 650);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', settle, { once: true });
  } else {
    settle();
  }

  window.addEventListener('load', settle, { once: true });

  var resizeTimer;
  window.addEventListener('resize', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(applyGrid, 180);
  });
}());
