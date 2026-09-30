(function ($) {
  'use strict';

  if (window.iwsProductFilterBound) {
    return;
  }
  window.iwsProductFilterBound = true;

  var settings = window.iwsProductFilter || {};
  var ajaxUrl = settings.ajaxUrl || window.ajaxurl || '/wp-admin/admin-ajax.php';
  var categories = settings.categories || { byId: {}, byName: {}, bySlug: {} };
  var i18n = settings.i18n || {};
  var timer = null;
  var controller = null;
  var requestSeq = 0;
  var suppressEvents = false;

  var externalCategorySelector = [
    '.product-categories-69f10cdd7d431-select',
    'select[class*="product-categories-"][class*="-select"]',
    '.tpl__woo-search-cat select',
    '.wp-block-woocommerce-product-categories select',
    '.wc-block-product-categories select'
  ].join(',');

  function getForm() {
    return document.querySelector('.iws-filter-form');
  }

  function getResults() {
    return document.querySelector('.iws-filter-results-wrap');
  }

  function removeLegacyLoadMore(context) {
    (context || document).querySelectorAll('#loadMore, .loadMore, #load-more-js').forEach(function (node) {
      if (!node.closest || node.closest('.iws-filter-results-wrap') || node.id === 'load-more-js') {
        node.remove();
      }
    });
  }

  function normaliseText(text) {
    return String(text || '')
      .toLowerCase()
      .replace(/&/g, 'and')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function isFilterInput(el) {
    return !!(el && el.closest && el.closest('.iws-filter-form'));
  }

  function isExternalCategorySelect(el) {
    return !!(el && el.matches && el.matches(externalCategorySelector) && !el.closest('.iws-filter-form'));
  }

  function getExternalCategorySelects() {
    return Array.prototype.slice.call(document.querySelectorAll(externalCategorySelector)).filter(function (select) {
      return !select.closest('.iws-filter-form');
    });
  }

  function triggerSelect2Update(select) {
    if (!select || !window.jQuery) {
      return;
    }
    var $select = window.jQuery(select);
    if ($select.data('select2') || $select.hasClass('select2-hidden-accessible')) {
      $select.trigger('change.select2');
    }
  }

  function setSelectValue(select, value) {
    if (!select) {
      return;
    }
    select.value = value || '';
    triggerSelect2Update(select);
  }

  function slugFromUrl(value) {
    var slug = '';
    if (!value) {
      return slug;
    }

    value = String(value).trim();

    try {
      var url = new URL(value, window.location.href);
      slug = url.searchParams.get('product_cat') || url.searchParams.get('product-category') || url.searchParams.get('product-category-id') || '';

      if (!slug) {
        var parts = url.pathname.replace(/\/$/, '').split('/').filter(Boolean);
        slug = parts.pop() || '';
      }
    } catch (err) {
      if (/^[a-z0-9_-]+$/i.test(value)) {
        slug = value;
      }
    }

    return slug;
  }

  function categorySlugFrom(value, text) {
    var raw = String(value || '').trim();
    var label = String(text || '').trim();
    var slug = '';

    if (raw === '' || raw === '0' || raw === '#') {
      return '';
    }

    if (categories.byId && categories.byId[String(raw)]) {
      return categories.byId[String(raw)];
    }

    if (categories.bySlug && categories.bySlug[raw]) {
      return categories.bySlug[raw];
    }

    slug = slugFromUrl(raw);
    if (slug) {
      if (categories.byId && categories.byId[String(slug)]) {
        return categories.byId[String(slug)];
      }
      if (categories.bySlug && categories.bySlug[slug]) {
        return categories.bySlug[slug];
      }
      if (categories.byName && categories.byName[normaliseText(slug)]) {
        return categories.byName[normaliseText(slug)];
      }
      return slug;
    }

    if (label && categories.byName && categories.byName[normaliseText(label)]) {
      return categories.byName[normaliseText(label)];
    }

    if (raw && categories.byName && categories.byName[normaliseText(raw)]) {
      return categories.byName[normaliseText(raw)];
    }

    return raw;
  }

  function slugFromCategorySelect(select) {
    if (!select) {
      return '';
    }
    var option = select.options[select.selectedIndex];
    return categorySlugFrom(select.value || (option ? option.value : ''), option ? option.textContent : '');
  }

  function optionMatchesSlug(option, slug) {
    if (!option) {
      return false;
    }
    return categorySlugFrom(option.value, option.textContent) === slug;
  }

  function syncExternalCategorySelects(slug) {
    suppressEvents = true;
    getExternalCategorySelects().forEach(function (select) {
      var optionToUse = null;

      if (!slug) {
        optionToUse = Array.prototype.slice.call(select.options).filter(function (option) {
          return option.value === '' || option.value === '0';
        })[0] || select.options[0] || null;
      } else {
        optionToUse = Array.prototype.slice.call(select.options).filter(function (option) {
          return optionMatchesSlug(option, slug);
        })[0] || null;
      }

      if (optionToUse) {
        select.value = optionToUse.value;
      } else if (!slug) {
        select.value = '';
      }

      triggerSelect2Update(select);
    });
    suppressEvents = false;
  }

  function rangeControlForField(field) {
    if (!field || !field.name || field.type === 'range') {
      return null;
    }

    var form = field.closest ? field.closest('.iws-filter-form') : getForm();
    if (!form) {
      return null;
    }

    return form.querySelector('.iws-range-input[data-pair="' + field.name + '"]');
  }

  function isInactiveRangeValue(field, value) {
    var range = rangeControlForField(field);
    if (!range) {
      return false;
    }

    value = String(value == null ? field.value : value).trim();
    if (value === '') {
      return true;
    }

    var pair = field.name || range.getAttribute('data-pair') || '';
    var defaultValue = pair.indexOf('max_') === 0 ? range.max : range.min;

    return String(parseFloat(value)) === String(parseFloat(defaultValue));
  }

  function cleanPublicFilterParams(params, form) {
    // Used for browser URL only. Remove technical/private fields and empty/default filters.
    ['iws_filter_nonce', 'posts_per_page', 'paged', 'action', 'append'].forEach(function (key) {
      params.delete(key);
    });

    Array.from(params.keys()).forEach(function (key) {
      var value = params.get(key);
      var field = form ? form.querySelector('[name="' + key + '"]') : null;

      if (value === null || String(value).trim() === '') {
        params.delete(key);
        return;
      }

      if (field && isInactiveRangeValue(field, value)) {
        params.delete(key);
      }
    });

    return params;
  }

  function cleanAjaxFilterParams(params, form) {
    // Used for AJAX. Keep nonce, posts_per_page and paged so submit/search/load-more work.
    params.delete('append');

    Array.from(params.keys()).forEach(function (key) {
      var value = params.get(key);
      var field = form ? form.querySelector('[name="' + key + '"]') : null;

      if (['iws_filter_nonce', 'posts_per_page', 'paged', 'action'].indexOf(key) !== -1) {
        return;
      }

      if (value === null || String(value).trim() === '') {
        params.delete(key);
        return;
      }

      if (field && isInactiveRangeValue(field, value)) {
        params.delete(key);
      }
    });

    return params;
  }

  function requestParams(form) {
    var params = new URLSearchParams(new FormData(form));
    cleanAjaxFilterParams(params, form);
    params.set('action', 'iws_filter_products');
    return params;
  }

  function setLoading(form, state) {
    var wrapper = form ? form.closest('.iws-filter-wrapper') : document.querySelector('.iws-filter-wrapper');
    var results = getResults();

    if (wrapper) {
      wrapper.classList.toggle('iws-is-loading', !!state);
    }
    if (results) {
      results.classList.toggle('iws-results-loading', !!state);
    }
  }

  function updateChips(form) {
    var wrapper = form ? form.closest('.iws-filter-wrapper') : null;
    var box = wrapper ? wrapper.querySelector('.iws-active-filters') : null;
    if (!box) {
      return;
    }

    box.innerHTML = '';
    form.querySelectorAll('input, select').forEach(function (field) {
      if (!field.name || ['iws_filter_nonce', 'paged', 'posts_per_page'].indexOf(field.name) !== -1 || field.type === 'range') {
        return;
      }

      if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
        return;
      }

      var value = field.value;
      if (!value || isInactiveRangeValue(field, value)) {
        return;
      }

      var label = field.dataset.label || field.name.replace(/_/g, ' ');
      if (field.tagName === 'SELECT' && field.selectedOptions.length) {
        value = field.selectedOptions[0].textContent;
      }

      var chip = document.createElement('span');
      chip.className = 'iws-filter-chip';

      var chipText = document.createElement('span');
      chipText.textContent = label + ': ' + value;

      var clear = document.createElement('button');
      clear.type = 'button';
      clear.setAttribute('aria-label', 'Remove filter');
      clear.dataset.clear = field.name;
      clear.textContent = '×';

      chip.appendChild(chipText);
      chip.appendChild(clear);
      box.appendChild(chip);
    });
  }

  function updateUrl(form) {
    try {
      var params = cleanPublicFilterParams(new URLSearchParams(new FormData(form)), form);
      var url = new URL(window.location.href);
      url.search = '';
      params.forEach(function (value, key) {
        url.searchParams.set(key, value);
      });
      window.history.replaceState({}, '', url.toString());
    } catch (err) {}
  }

  function setAvailableAttributes(data) {
    var form = getForm();
    if (!form || !data) {
      return;
    }

    form.querySelectorAll('select[name^="attribute_"]').forEach(function (select) {
      var allowed = data[select.name] || [];
      var limited = allowed.length > 0;
      var currentValue = select.value;

      Array.prototype.slice.call(select.options).forEach(function (option) {
        if (!option.value) {
          option.disabled = false;
          return;
        }
        option.disabled = limited && allowed.indexOf(option.value) === -1 && option.value !== currentValue;
      });

      triggerSelect2Update(select);
    });
  }

  function notifyUpdated(append) {
    document.dispatchEvent(new CustomEvent('iwsProductsUpdated', { detail: { append: !!append } }));
    document.dispatchEvent(new CustomEvent('iws:select2:init', { detail: { context: document } }));
    if (window.jQuery) {
      window.jQuery(document.body).trigger('updated_wc_div').trigger('iws_products_updated');
    }
  }

  function ajaxRequest(form, append) {
    var results = getResults();
    if (!form || !results) {
      return false;
    }

    if (controller) {
      try {
        controller.abort();
      } catch (err) {}
    }

    controller = window.AbortController ? new AbortController() : null;
    var seq = ++requestSeq;
    var params = requestParams(form);
    var requestedPage = params.get('paged') || '1';

    if (append) {
      params.set('append', '1');
    }

    setLoading(form, true);

    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString(),
      signal: controller ? controller.signal : undefined
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (res) {
        if (seq !== requestSeq || !res || !res.success || !res.data) {
          return;
        }

        if (append) {
          var tmp = document.createElement('div');
          tmp.innerHTML = res.data.products_html || '';
          var targetGrid = results.querySelector('.iws-products-grid');
          var newGrid = tmp.querySelector('.iws-products-grid');
          var firstAdded = null;

          removeLegacyLoadMore(tmp);

          if (targetGrid && newGrid) {
            Array.prototype.slice.call(newGrid.children).forEach(function (child) {
              if (child.classList && child.classList.contains('iws-no-products')) {
                return;
              }
              if (!firstAdded) {
                firstAdded = child;
              }
              targetGrid.appendChild(child);
            });
          }

          var oldLoadMore = results.querySelector('.iws-load-more-wrap');
          if (oldLoadMore) {
            oldLoadMore.remove();
          }
          if (res.data.load_more_html) {
            results.insertAdjacentHTML('beforeend', res.data.load_more_html);
          }

          var count = results.querySelector('.iws-result-count');
          if (count && res.data.count_text) {
            count.textContent = res.data.count_text;
          }

          var pageInput = form.querySelector('[name="paged"]');
          if (pageInput) {
            pageInput.value = requestedPage;
          }

          if (firstAdded && firstAdded.scrollIntoView) {
            firstAdded.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        } else {
          results.innerHTML = res.data.html || '';
        }

        removeLegacyLoadMore(results);
        updateChips(form);
        updateUrl(form);
        syncExternalCategorySelects((form.querySelector('[name="product_cat"]') || {}).value || '');
        setAvailableAttributes(res.data.available_attributes);
        notifyUpdated(append);
      })
      .catch(function (err) {
        if (!err || err.name !== 'AbortError') {
          if (window.console && window.console.warn) {
            window.console.warn('IWS product filter ajax failed', err);
          }
        }
      })
      .finally(function () {
        setLoading(form, false);
        document.querySelectorAll('.iws-load-more').forEach(function (button) {
          button.disabled = false;
          var text = button.querySelector('.iws-load-more-text');
          if (text) {
            text.textContent = i18n.loadMore || 'Load more';
          }
        });
      });

    return true;
  }

  function submitFilter(form) {
    form = form || getForm();
    if (!form) {
      return;
    }

    var pageInput = form.querySelector('[name="paged"]');
    if (pageInput) {
      pageInput.value = '1';
    }

    ajaxRequest(form, false);
  }

  function scheduleSubmit(form, delay) {
    var wrapper = form ? form.closest('.iws-filter-wrapper') : null;
    if (!wrapper || wrapper.dataset.instant !== 'yes') {
      return;
    }

    clearTimeout(timer);
    timer = window.setTimeout(function () {
      submitFilter(form);
    }, typeof delay === 'number' ? delay : 300);
  }

  function syncRange(input) {
    var form = input.closest('form');
    var pair = input.dataset.pair;
    if (!form || !pair) {
      return;
    }

    var target = form.querySelector('[name="' + pair + '"]');
    if (target) {
      target.value = input.value;
    }

    var label = input.dataset.valueTarget ? document.getElementById(input.dataset.valueTarget) : null;
    if (label) {
      label.textContent = input.value;
    }
  }

  function clearField(field) {
    if (!field) {
      return;
    }

    if (field.type === 'checkbox' || field.type === 'radio') {
      field.checked = false;
    } else {
      field.value = '';
    }
    triggerSelect2Update(field);
  }

  function resetFormState(form) {
    form.reset();

    form.querySelectorAll('input[type="number"], input[type="search"], input[type="checkbox"], input[type="radio"], select').forEach(clearField);
    form.querySelectorAll('.iws-range-input').forEach(function (range) {
      var pair = range.getAttribute('data-pair') || '';
      range.value = pair.indexOf('max_') === 0 ? range.max : range.min;

      var target = pair ? form.querySelector('[name="' + pair + '"]') : null;
      if (target) {
        target.value = '';
      }

      var label = range.dataset.valueTarget ? document.getElementById(range.dataset.valueTarget) : null;
      if (label) {
        label.textContent = range.value;
      }
    });

    var pageInput = form.querySelector('[name="paged"]');
    if (pageInput) {
      pageInput.value = '1';
    }

    setActiveCategory('', null);
    syncExternalCategorySelects('');
    updateChips(form);
  }

  function setActiveCategory(slug, clickedLink) {
    document.querySelectorAll('.tpl__woo-search-cat li, .wp-block-woocommerce-product-categories li, .wc-block-product-categories-list li').forEach(function (li) {
      li.classList.remove('is-iws-active-category');
    });

    if (clickedLink) {
      var li = clickedLink.closest('li');
      if (li) {
        li.classList.add('is-iws-active-category');
      }
      return;
    }

    if (!slug) {
      return;
    }

    document.querySelectorAll('.tpl__woo-search-cat a, .wp-block-woocommerce-product-categories a, .wc-block-product-categories-list a').forEach(function (link) {
      if (categorySlugFrom(link.getAttribute('href'), link.textContent) === slug) {
        var li = link.closest('li');
        if (li) {
          li.classList.add('is-iws-active-category');
        }
      }
    });
  }

  function applyCategory(slug, clickedLink) {
    var form = getForm();
    var select = form ? form.querySelector('[name="product_cat"]') : null;
    if (!form || !select) {
      return false;
    }

    suppressEvents = true;
    setSelectValue(select, slug || '');
    suppressEvents = false;

    syncExternalCategorySelects(slug || '');
    setActiveCategory(slug || '', clickedLink || null);
    submitFilter(form);
    return true;
  }

  function syncCategoryFromInternal(select) {
    var slug = select ? select.value || '' : '';
    syncExternalCategorySelects(slug);
    setActiveCategory(slug, null);
  }

  function hydrateFromUrl() {
    var form = getForm();
    if (!form) {
      return;
    }

    var urlWasNormalised = false;

    try {
      var url = new URL(window.location.href);
      url.searchParams.forEach(function (value, key) {
        var field = form.querySelector('[name="' + key + '"]');
        if (!field) {
          return;
        }

        if (isInactiveRangeValue(field, value)) {
          field.value = '';
          urlWasNormalised = true;
          return;
        }

        if (field.tagName === 'SELECT') {
          setSelectValue(field, value);
        } else {
          field.value = value;
        }
      });
    } catch (err) {}

    var cat = form.querySelector('[name="product_cat"]');
    if (cat) {
      syncExternalCategorySelects(cat.value || '');
      setActiveCategory(cat.value || '', null);
    }

    updateChips(form);
    if (urlWasNormalised) {
      updateUrl(form);
    }
  }

  function initSelect2(context) {
    context = context || document;

    if (window.IWSSelect2 && typeof window.IWSSelect2.init === 'function') {
      window.IWSSelect2.init(context);
      return;
    }

    if (!$.fn.select2) {
      return;
    }

    $(context)
      .find('.iws-filter-form select, ' + externalCategorySelector)
      .addBack('.iws-filter-form select, ' + externalCategorySelector)
      .each(function () {
        var $select = $(this);
        if ($select.data('select2') || $select.hasClass('select2-hidden-accessible')) {
          return;
        }
        $select.select2({
          width: '100%',
          allowClear: !$select.prop('required'),
          placeholder: $select.find('option:first').text() || 'Select an option',
          minimumResultsForSearch: $select.find('option').length > 10 ? 0 : -1
        });
      });
  }

  function handleInput(target) {
    var form = target && target.closest ? target.closest('.iws-filter-form') : null;
    if (!form || suppressEvents) {
      return;
    }

    if (target.classList && target.classList.contains('iws-range-input')) {
      syncRange(target);
    }

    scheduleSubmit(form, target.matches && target.matches('[name="product_search"]') ? 350 : 250);
  }

  function handleChange(target, event) {
    if (!target || suppressEvents) {
      return false;
    }

    if (isFilterInput(target)) {
      var form = target.closest('.iws-filter-form');
      if (target.name === 'product_cat') {
        syncCategoryFromInternal(target);
      }
      scheduleSubmit(form, 60);
      return true;
    }

    if (isExternalCategorySelect(target)) {
      var slug = slugFromCategorySelect(target);
      if (applyCategory(slug, null)) {
        if (event) {
          event.preventDefault();
          event.stopPropagation();
          if (event.stopImmediatePropagation) {
            event.stopImmediatePropagation();
          }
        }
        return false;
      }
    }

    return true;
  }

  document.addEventListener(
    'input',
    function (event) {
      handleInput(event.target);
    },
    true
  );

  document.addEventListener(
    'keyup',
    function (event) {
      if (event.target && event.target.matches && event.target.matches('.iws-filter-form [name="product_search"]')) {
        handleInput(event.target);
      }
    },
    true
  );

  document.addEventListener(
    'change',
    function (event) {
      return handleChange(event.target, event);
    },
    true
  );

  $(document).on('change.iwsProductFilter', '.iws-filter-form :input, ' + externalCategorySelector, function (event) {
    return handleChange(event.currentTarget || this, event);
  });

  document.addEventListener(
    'submit',
    function (event) {
      var form = event.target.closest ? event.target.closest('.iws-filter-form') : null;
      if (!form) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      if (event.stopImmediatePropagation) {
        event.stopImmediatePropagation();
      }
      submitFilter(form);
      return false;
    },
    true
  );

  document.addEventListener(
    'click',
    function (event) {
      var clear = event.target.closest ? event.target.closest('[data-clear]') : null;
      if (clear) {
        event.preventDefault();
        var formForClear = clear.closest('.iws-filter-wrapper').querySelector('.iws-filter-form');
        var field = formForClear ? formForClear.querySelector('[name="' + clear.dataset.clear + '"]') : null;
        clearField(field);
        if (field && field.name === 'product_cat') {
          syncExternalCategorySelects('');
          setActiveCategory('', null);
        }
        submitFilter(formForClear);
        return false;
      }

      var reset = event.target.closest ? event.target.closest('.iws-filter-reset') : null;
      if (reset) {
        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) {
          event.stopImmediatePropagation();
        }
        var resetFormEl = reset.closest('.iws-filter-form');
        if (resetFormEl) {
          resetFormState(resetFormEl);
          submitFilter(resetFormEl);
        }
        return false;
      }

      var catLink = event.target.closest ? event.target.closest('.tpl__woo-search-cat .wp-block-woocommerce-product-categories a, .tpl__woo-search-cat .wc-block-product-categories-list a, .wp-block-woocommerce-product-categories a, .wc-block-product-categories-list a') : null;
      if (catLink && !catLink.closest('.iws-filter-form')) {
        var slug = categorySlugFrom(catLink.getAttribute('href'), catLink.textContent);
        if (applyCategory(slug, catLink)) {
          event.preventDefault();
          event.stopPropagation();
          if (event.stopImmediatePropagation) {
            event.stopImmediatePropagation();
          }
          return false;
        }
      }

      var more = event.target.closest ? event.target.closest('.iws-load-more') : null;
      if (more) {
        event.preventDefault();
        event.stopPropagation();
        var form = getForm();
        if (!form) {
          return false;
        }
        var pageInput = form.querySelector('[name="paged"]');
        if (pageInput) {
          pageInput.value = more.getAttribute('data-next-page') || '1';
        }
        more.disabled = true;
        var text = more.querySelector('.iws-load-more-text');
        if (text) {
          text.textContent = more.getAttribute('data-loading') || i18n.loading || 'Loading...';
        }
        ajaxRequest(form, true);
        return false;
      }
    },
    true
  );

  document.addEventListener('iwsProductsUpdated', function (event) {
    initSelect2(document);
    if (event.detail && event.detail.append) {
      return;
    }
    var form = getForm();
    if (form) {
      syncExternalCategorySelects((form.querySelector('[name="product_cat"]') || {}).value || '');
      updateChips(form);
    }
  });

  function boot() {
    document.querySelectorAll('.wc-block-product-categories__button').forEach(function (button) {
      button.remove();
    });
    initSelect2(document);
    removeLegacyLoadMore(document);
    hydrateFromUrl();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(jQuery);

(function () {
  'use strict';

  var storageKey = 'iwsCompareProducts';

  function readStore() {
    try {
      var raw = window.localStorage ? window.localStorage.getItem(storageKey) : '';
      var data = raw ? JSON.parse(raw) : {};
      return data && typeof data === 'object' ? data : {};
    } catch (err) {
      return {};
    }
  }

  function writeStore(data) {
    try {
      if (window.localStorage) {
        window.localStorage.setItem(storageKey, JSON.stringify(data || {}));
      }
    } catch (err) {}
  }

  function decodeEntities(value) {
    var text = String(value == null ? '' : value);
    if (!text || text.indexOf('&') === -1) return text;
    var textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  }

  function esc(value) {
    return decodeEntities(value).replace(/[&<>'"]/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char];
    });
  }

  function cleanupFilterMarkup(scope) {
    (scope || document).querySelectorAll('.iws-filter-form').forEach(function (form) {
      Array.prototype.slice.call(form.childNodes).forEach(function (node) {
        if (node.nodeType === 3 && !String(node.nodeValue || '').trim()) {
          node.parentNode.removeChild(node);
        }
        if (node.nodeType === 1) {
          var tag = node.tagName ? node.tagName.toLowerCase() : '';
          if (tag === 'br' || (tag === 'p' && !String(node.textContent || '').trim() && !node.children.length)) {
            node.parentNode.removeChild(node);
          }
        }
      });
      form.querySelectorAll('br,p:empty,.wp-block-spacer:empty').forEach(function (node) {
        if (node.parentNode) node.parentNode.removeChild(node);
      });
    });
  }

  function colorFor(value) {
    var v = decodeEntities(value).toLowerCase().trim();
    var found = '';
    var map = { black: '#111111', white: '#ffffff', red: '#d32f2f', blue: '#1976d2', green: '#2e7d32', yellow: '#fbc02d', orange: '#f57c00', grey: '#9e9e9e', gray: '#9e9e9e', silver: '#c0c0c0', brown: '#795548', purple: '#7b1fa2', bluealt: '#2563eb', clear: '#f7f7f7' };
    Object.keys(map).some(function (key) {
      if (v.indexOf(key) !== -1) { found = map[key]; return true; }
      return false;
    });
    if (found) return found;
    return /^#[0-9a-f]{3,6}$/i.test(v) || /^rgb/.test(v) ? v : '';
  }


  function getProductData(id) {
    var node = document.querySelector('.iws-compare-product-data[data-product-id="' + id + '"]');
    if (!node) {
      return null;
    }
    try {
      return JSON.parse(node.textContent || '{}');
    } catch (err) {
      return null;
    }
  }

  function pairList(pairs) {
    if (!pairs || !pairs.length) {
      return '<span class="iws-compare-empty">-</span>';
    }
    return '<ul class="iws-compare-pairs">' + pairs.map(function (pair) {
      return '<li><strong>' + esc(pair.label) + ':</strong> ' + esc(pair.value) + '</li>';
    }).join('') + '</ul>';
  }

  function swatchList(pairs) {
    if (!pairs || !pairs.length) return '';
    return '<div class="iws-compare-swatches">' + pairs.map(function (pair) {
      var label = esc(pair.label || '');
      var value = decodeEntities(pair.value || '');
      var color = /colou?r|color|finish/i.test(pair.label || '') ? colorFor(value) : '';
      if (color) {
        return '<span class="iws-compare-swatch iws-compare-swatch--color" title="' + label + ': ' + esc(value) + '" style="background:' + esc(color) + '">' + esc(value) + '</span>';
      }
      return '<span class="iws-compare-swatch" title="' + label + '">' + esc(value) + '</span>';
    }).join('') + '</div>';
  }

  function variationList(product) {
    var variations = product && product.variations ? product.variations : [];
    if (!variations.length) {
      return '<span class="iws-compare-empty">-</span>';
    }
    return '<details class="iws-compare-variations"><summary>' + variations.length + ' variation' + (variations.length === 1 ? '' : 's') + '</summary><div class="iws-compare-variation-list">' + variations.map(function (variation, index) {
      var attrs = variation.attributes || [];
      var meta = [];
      if (variation.sku) meta.push('SKU: ' + esc(variation.sku));
      if (variation.price) meta.push(esc(variation.price));
      if (variation.dimensions) meta.push(esc(variation.dimensions));
      if (variation.weight) meta.push(esc(variation.weight));
      if (variation.stock) meta.push(esc(variation.stock));
      return '<div class="iws-compare-variation"><div class="iws-compare-variation-title">Variation ' + (index + 1) + '</div>' + swatchList(attrs) + pairList(attrs) + (meta.length ? '<small>' + meta.join(' | ') + '</small>' : '') + '</div>';
    }).join('') + '</div></details>';
  }

  function syncUi() {
    var store = readStore();
    var ids = Object.keys(store);
    document.querySelectorAll('.iws-compare-toggle[data-product-id]').forEach(function (button) {
      var active = !!store[button.getAttribute('data-product-id')];
      button.classList.toggle('is-selected', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      button.setAttribute('aria-label', active ? 'Remove from comparison' : 'Add to comparison');
    });
    document.querySelectorAll('.iws-compare-count').forEach(function (count) {
      count.textContent = String(ids.length);
    });
    document.querySelectorAll('.iws-compare-open').forEach(function (button) {
      button.classList.toggle('has-products', ids.length > 0);
      if (button.classList.contains('iws-compare-open--search')) {
        button.style.setProperty('position', 'relative', 'important');
        button.style.setProperty('overflow', 'visible', 'important');
        button.style.setProperty('background-color', ids.length > 0 ? '#fff' : 'var(--wp-brand-color)', 'important');
        button.style.setProperty('border-color', 'var(--wp-brand-color)', 'important');
        var count = button.querySelector('.iws-compare-count');
        if (count) {
          count.style.setProperty('position', 'absolute', 'important');
          count.style.setProperty('top', '-9px', 'important');
          count.style.setProperty('right', '-9px', 'important');
          count.style.setProperty('left', 'auto', 'important');
          count.style.setProperty('bottom', 'auto', 'important');
          count.style.setProperty('display', ids.length > 0 ? 'inline-flex' : 'none', 'important');
          count.style.setProperty('align-items', 'center', 'important');
          count.style.setProperty('justify-content', 'center', 'important');
        }
      }
    });
  }

  function renderModal() {
    var modal = document.querySelector('.iws-compare-modal');
    var body = modal ? modal.querySelector('.iws-compare-modal__body') : null;
    if (!body) return;
    var products = Object.keys(readStore()).map(function (id) { return readStore()[id]; }).filter(Boolean);
    if (!products.length) {
      body.innerHTML = '<p class="iws-compare-empty-state">No products selected yet. Use the compare icon on product images to add products.</p>';
      return;
    }
    var rows = [
      ['Type', function (p) { return esc(p.type || '-'); }],
      ['SKU', function (p) { return esc(p.sku || '-'); }],
      ['Price', function (p) { return esc(p.price || '-'); }],
      ['Categories', function (p) { return esc((p.categories || []).join(', ') || '-'); }],
      ['Stock', function (p) { return esc(p.stock || '-'); }],
      ['Dimensions', function (p) { return esc(p.dimensions || '-'); }],
      ['Weight', function (p) { return esc(p.weight || '-'); }],
      ['Attributes', function (p) { return pairList(p.attributes || []); }],
      ['Variations', function (p) { return variationList(p); }]
    ];
    var html = '<div class="iws-compare-tools"><button type="button" class="iws-compare-clear-all">Clear all</button></div><div class="iws-compare-table-wrap"><table class="iws-compare-table"><thead><tr><th>Product</th>';
    html += products.map(function (p) {
      return '<th><button type="button" class="iws-compare-remove" data-product-id="' + esc(p.id) + '" aria-label="Remove ' + esc(p.title) + '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"></path></svg></button><a href="' + esc(p.url) + '"><img src="' + esc(p.image) + '" alt=""><span>' + esc(p.title) + '</span></a></th>';
    }).join('');
    html += '</tr></thead><tbody>';
    rows.forEach(function (row) {
      html += '<tr><th scope="row">' + esc(row[0]) + '</th>' + products.map(function (p) { return '<td>' + row[1](p) + '</td>'; }).join('') + '</tr>';
    });
    body.innerHTML = html + '</tbody></table></div>';
  }

  function openModal() {
    var modal = document.querySelector('.iws-compare-modal');
    if (!modal) return;
    renderModal();
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('iws-compare-modal-open');
  }

  function closeModal() {
    var modal = document.querySelector('.iws-compare-modal');
    if (!modal) return;
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('iws-compare-modal-open');
  }

  function syncSearchClear() {
    cleanupFilterMarkup(document);
    document.querySelectorAll('.iws-filter-form').forEach(function (form) {
      var input = form.querySelector('[name="product_search"]');
      var clear = form.querySelector('.iws-search-clear');
      if (input && clear) {
        var hasValue = !!String(input.value || '').trim();
        var wrap = clear.closest ? clear.closest('.iws-search-input-wrap') : null;
        clear.hidden = !hasValue;
        clear.classList.toggle('is-hidden', !hasValue);
        clear.setAttribute('aria-hidden', hasValue ? 'false' : 'true');
        clear.tabIndex = hasValue ? 0 : -1;
        if (wrap) {
          wrap.classList.toggle('has-search-value', hasValue);
        }
      }
    });
  }

  document.addEventListener('input', function (event) {
    if (event.target && event.target.matches && event.target.matches('.iws-filter-form [name="product_search"]')) {
      syncSearchClear();
    }
  }, true);

  document.addEventListener('click', function (event) {
    var clear = event.target.closest && event.target.closest('.iws-search-clear');
    if (clear) {
      event.preventDefault();
      var form = clear.closest('.iws-filter-form');
      var input = form ? form.querySelector('[name="product_search"]') : null;
      if (input) {
        input.value = '';
        syncSearchClear();
        if (form.requestSubmit) form.requestSubmit();
        else form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
      }
      return;
    }

    var toggle = event.target.closest && event.target.closest('.iws-compare-toggle[data-product-id]');
    if (toggle) {
      event.preventDefault();
      event.stopPropagation();
      if (event.stopImmediatePropagation) event.stopImmediatePropagation();
      var id = toggle.getAttribute('data-product-id');
      var store = readStore();
      if (store[id]) delete store[id];
      else {
        var product = getProductData(id);
        if (product) store[id] = product;
      }
      writeStore(store);
      syncUi();
      return;
    }

    if (event.target.closest && event.target.closest('.iws-compare-open')) {
      event.preventDefault();
      openModal();
      return;
    }

    if (event.target.closest && event.target.closest('[data-iws-compare-close]')) {
      event.preventDefault();
      closeModal();
      return;
    }

    var remove = event.target.closest && event.target.closest('.iws-compare-remove[data-product-id]');
    if (remove) {
      event.preventDefault();
      var removeStore = readStore();
      delete removeStore[remove.getAttribute('data-product-id')];
      writeStore(removeStore);
      syncUi();
      renderModal();
      return;
    }

    if (event.target.closest && event.target.closest('.iws-compare-clear-all')) {
      event.preventDefault();
      writeStore({});
      syncUi();
      renderModal();
    }
  }, true);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeModal();
  });

  document.addEventListener('iwsProductsUpdated', function () {
    syncUi();
    syncSearchClear();
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { cleanupFilterMarkup(document); syncUi(); syncSearchClear(); });
  } else {
    syncUi();
    syncSearchClear();
  }
})();
