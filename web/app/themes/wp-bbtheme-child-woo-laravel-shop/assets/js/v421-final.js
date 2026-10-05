(function ($) {
  'use strict';

  function normalizeCheckoutShipping() {
    if (!document.body || !document.body.classList.contains('woocommerce-checkout')) return;

    var rows = document.querySelectorAll('.woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping, .woocommerce-checkout-review-order-table tr.wpbbshop-shipping-full-row');
    Array.prototype.forEach.call(rows, function (row) {
      var cells = Array.prototype.slice.call(row.children || []);
      var th = cells.find(function (cell) { return cell.tagName === 'TH'; });
      var td = cells.find(function (cell) { return cell.tagName === 'TD'; });
      if (!td) return;

      var headingText = th ? (th.textContent || '').trim() : '';
      if (!headingText) headingText = td.getAttribute('data-title') || '';
      if (!headingText) headingText = document.documentElement.lang && document.documentElement.lang.indexOf('lv') === 0 ? 'Piegāde' : 'Delivery';

      if (th && th.parentNode === row) th.parentNode.removeChild(th);

      td.colSpan = 2;
      row.classList.add('wpbbshop-v421-shipping-row');
      row.style.setProperty('display', 'table-row', 'important');
      td.style.setProperty('display', 'table-cell', 'important');
      td.style.setProperty('width', '100%', 'important');
      td.style.setProperty('min-width', '100%', 'important');
      td.style.setProperty('max-width', 'none', 'important');
      td.style.setProperty('padding-left', '0', 'important');
      td.style.setProperty('padding-right', '0', 'important');
      td.style.setProperty('text-align', 'left', 'important');

      var existingTitle = td.querySelector('.wpbbshop-shipping-title, .wpbbshop-v421-shipping-heading');
      if (!existingTitle) {
        var title = document.createElement('strong');
        title.className = 'wpbbshop-v421-shipping-heading';
        title.textContent = headingText;
        td.insertBefore(title, td.firstChild);
      }

      var full = td.querySelector('.wpbbshop-shipping-full') || td;
      full.style.setProperty('width', '100%', 'important');
      full.style.setProperty('min-width', '100%', 'important');
      full.style.setProperty('max-width', 'none', 'important');

      var list = td.querySelector('#shipping_method, .woocommerce-shipping-methods');
      if (list) {
        list.style.setProperty('width', '100%', 'important');
        list.style.setProperty('min-width', '100%', 'important');
        list.style.setProperty('max-width', 'none', 'important');
        Array.prototype.forEach.call(list.children, function (item) {
          if (!item || !item.style) return;
          item.style.setProperty('width', '100%', 'important');
          item.style.setProperty('max-width', 'none', 'important');
        });
      }
    });
  }

  function run() {
    normalizeCheckoutShipping();
    window.setTimeout(normalizeCheckoutShipping, 40);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run, { once: true });
  } else {
    run();
  }

  if ($) {
    $(document.body).on('updated_checkout payment_method_selected', run);
  }
}(window.jQuery));
