(function () {
  'use strict';

  function expandShippingRows() {
    var rows = document.querySelectorAll('tr.wpbbshop-shipping-full-row, tr.woocommerce-shipping-totals.shipping');
    Array.prototype.forEach.call(rows, function (row) {
      var td = row.querySelector(':scope > td');
      if (!td) return;
      if (row.classList.contains('wpbbshop-shipping-full-row')) {
        td.colSpan = 2;
      }
      row.style.setProperty('display', 'table-row', 'important');
      row.style.removeProperty('grid-template-columns');
      td.style.setProperty('display', 'table-cell', 'important');
      td.style.setProperty('width', 'auto', 'important');
      td.style.setProperty('max-width', 'none', 'important');
      td.style.setProperty('text-align', 'left', 'important');

      var full = td.querySelector('.wpbbshop-shipping-full') || td;
      full.style.setProperty('width', '100%', 'important');
      full.style.setProperty('max-width', 'none', 'important');

      var list = td.querySelector('#shipping_method, .woocommerce-shipping-methods');
      if (list) {
        list.style.setProperty('width', '100%', 'important');
        list.style.setProperty('max-width', 'none', 'important');
        Array.prototype.forEach.call(list.children, function (item) {
          if (item && item.style) {
            item.style.setProperty('width', '100%', 'important');
            item.style.setProperty('max-width', 'none', 'important');
          }
        });
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', expandShippingRows, { once: true });
  } else {
    expandShippingRows();
  }

  if (window.jQuery) {
    window.jQuery(document.body).on('updated_checkout updated_cart_totals wc_fragments_refreshed', expandShippingRows);
  }
}());
