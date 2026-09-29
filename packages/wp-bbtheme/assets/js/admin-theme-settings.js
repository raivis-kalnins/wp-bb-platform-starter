
document.addEventListener('DOMContentLoaded', function () {
  const body = document.body;

  function getFieldInput(dataName, selector) {
    const field = document.querySelector('.acf-field[data-name="' + dataName + '"]');
    if (!field) return null;
    return field.querySelector(selector || 'input, select, textarea');
  }

  function initTypographyUnits() {
    const header = document.querySelector('.wp-theme-size-header');
    if (header && !header.dataset.enhanced) {
      header.innerHTML = '<span>Type</span><span><strong>🖥</strong>&nbsp;Desktop</span><span><strong>📱</strong>&nbsp;Tablet</span><span><strong>📲</strong>&nbsp;Mobile</span>';
      header.dataset.enhanced = '1';
    }

    document.querySelectorAll('.wp-theme-size-row input[type="text"]').forEach((input) => {
      if (input.dataset.unitsReady) return;
      input.dataset.unitsReady = '1';

      const wrap = document.createElement('div');
      wrap.className = 'wp-theme-unit-wrap';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);

      const select = document.createElement('select');
      select.className = 'wp-theme-unit-select';
      ['px', 'rem', 'em'].forEach((unit) => {
        const opt = document.createElement('option');
        opt.value = unit;
        opt.textContent = unit;
        select.appendChild(opt);
      });

      const match = String(input.value || '').trim().match(/^([0-9]*\.?[0-9]+)\s*(px|rem|em)$/i);
      if (match) {
        input.value = match[1];
        select.value = match[2].toLowerCase();
      } else {
        select.value = 'px';
      }

      select.addEventListener('change', function () {
        input.value = (input.value || '').replace(/[^0-9.]/g, '') + select.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });

      input.addEventListener('input', function () {
        const num = (input.value || '').replace(/[^0-9.]/g, '');
        input.value = num + select.value;
      });

      wrap.appendChild(select);
    });
  }


function initAnimationUI() {
  const animationSelect = getFieldInput('theme_anim_default_class', 'select');
  const durationInput = getFieldInput('theme_anim_duration', 'input');
  const delayInput = getFieldInput('theme_anim_delay', 'input');
  const repeatInput = getFieldInput('theme_anim_repeat', 'select');
  const previewTextInput = getFieldInput('theme_anim_preview_text', 'input');
  const previewSelect = document.getElementById('bbtheme-preview-select');
  const previewTrigger = document.getElementById('bbtheme-preview-trigger');
  const previewBox = document.getElementById('bbtheme-preview-box');
  const searchInput = document.getElementById('bbtheme-animation-search');
  const codeSnippet = document.getElementById('bbtheme-animation-code-snippet');
  const tabs = document.querySelectorAll('.bbtheme-animation-tabs .nav-tab');
  const panels = document.querySelectorAll('.bbtheme-tab-panel');
  const registry = (window.BBThemeAdminSettings && window.BBThemeAdminSettings.animationRegistry) || [];

  function showTab(hash) {
    tabs.forEach((tab) => tab.classList.toggle('nav-tab-active', tab.getAttribute('href') === hash));
    panels.forEach((panel) => panel.classList.toggle('is-active', '#' + panel.id === hash));
  }

  if (previewSelect && previewSelect.options.length === 0 && Array.isArray(registry) && registry.length) {
    const groups = {};
    registry.forEach((item) => {
      const group = item.group || 'Other';
      if (!groups[group]) groups[group] = [];
      groups[group].push(item);
    });
    Object.keys(groups).forEach((group) => {
      const optgroup = document.createElement('optgroup');
      optgroup.label = group;
      groups[group].forEach((item) => {
        const opt = document.createElement('option');
        opt.value = item.class || '';
        opt.textContent = item.label || item.class || '';
        optgroup.appendChild(opt);
      });
      previewSelect.appendChild(optgroup);
    });
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', function (event) {
      event.preventDefault();
      const hash = tab.getAttribute('href');
      history.replaceState(null, '', hash);
      showTab(hash);
    });
  });

  function replayPreview(animationClass) {
    if (!previewBox) return;
    const className = animationClass || ((previewSelect && previewSelect.value) || (animationSelect && animationSelect.value) || 'animate__fadeInUp');
    previewBox.className = 'wp-theme-animation-preview-box animate__animated';
    previewBox.style.setProperty('--animate-duration', durationInput && durationInput.value ? durationInput.value : '1s');
    previewBox.style.setProperty('--animate-delay', delayInput && delayInput.value ? delayInput.value : '0s');
    previewBox.style.setProperty('--animate-repeat', repeatInput && repeatInput.value ? repeatInput.value : '1');
    const text = previewTextInput && previewTextInput.value ? previewTextInput.value : (window.BBThemeAdminSettings?.strings?.defaultPreviewText || 'Animation preview');
    previewBox.innerHTML = '<strong>' + text + '</strong><span>' + className + '</span>';
    void previewBox.offsetWidth;
    previewBox.classList.add(className);
    if (codeSnippet) codeSnippet.textContent = '<div class="animate__animated ' + className + '">...</div>';
  }

  document.querySelectorAll('.bbtheme-preview-row').forEach((button) => {
    if (button.dataset.bound) return;
    button.dataset.bound = '1';
    button.addEventListener('click', function () {
      const animationClass = button.getAttribute('data-animation') || '';
      if (previewSelect) previewSelect.value = animationClass;
      replayPreview(animationClass);
      showTab('#bbtheme-tab-preview');
    });
  });

  document.querySelectorAll('.bbtheme-use-animation').forEach((button) => {
    if (button.dataset.bound) return;
    button.dataset.bound = '1';
    button.addEventListener('click', function () {
      const className = button.getAttribute('data-class') || '';
      if (animationSelect) animationSelect.value = className;
      replayPreview(className);
    });
  });

  document.querySelectorAll('.bbtheme-copy-class').forEach((button) => {
    if (button.dataset.bound) return;
    button.dataset.bound = '1';
    button.addEventListener('click', async function () {
      const className = button.getAttribute('data-class') || '';
      try {
        await navigator.clipboard.writeText(className);
        button.textContent = 'Copied';
        setTimeout(() => { button.textContent = 'Copy class'; }, 1200);
      } catch (error) {
        window.prompt('Copy class name:', className);
      }
    });
  });

  if (previewSelect) previewSelect.addEventListener('change', function () { replayPreview(previewSelect.value); });
  [animationSelect, durationInput, delayInput, repeatInput, previewTextInput].forEach((node) => {
    if (!node) return;
    node.addEventListener('change', function () { replayPreview(); });
    node.addEventListener('input', function () { replayPreview(); });
  });
  if (previewTrigger) previewTrigger.addEventListener('click', function () { replayPreview(); });

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const term = searchInput.value.trim().toLowerCase();
      document.querySelectorAll('.bbtheme-animation-table tbody tr').forEach((row) => {
        const haystack = row.getAttribute('data-search') || '';
        row.style.display = !term || haystack.indexOf(term) !== -1 ? '' : 'none';
      });
    });
  }

  if (window.location.hash && document.querySelector(window.location.hash)) {
    showTab(window.location.hash);
  } else {
    showTab('#bbtheme-tab-general');
  }
  replayPreview();

  const nativePreview = document.querySelector('[data-bbtheme-native-preview]');
  if (nativePreview) {
    const object = nativePreview.querySelector('.bbtheme-native-preview__object');
    const code = nativePreview.querySelector('[data-native-motion-code]');
    nativePreview.querySelectorAll('[data-native-motion]').forEach((button) => {
      button.addEventListener('click', function () {
        const preset = button.getAttribute('data-native-motion') || 'motion-fade-up';
        nativePreview.querySelectorAll('[data-native-motion]').forEach((item) => item.classList.toggle('is-selected', item === button));
        if (code) code.textContent = preset;
        if (!object) return;
        object.className = 'bbtheme-native-preview__object ' + preset;
        void object.offsetWidth;
        window.requestAnimationFrame(() => object.classList.add('is-visible'));
      });
    });
  }
}

  function initMediaLibraryImport() {
    const toggleBtn = document.getElementById('wp-theme-toggle-free-images');
    const panel = document.getElementById('wp-theme-free-images-panel');
    const query = document.getElementById('wp-theme-media-query');
    const searchBtn = document.getElementById('wp-theme-media-search');
    const providerCheckboxes = document.querySelectorAll('.wp-theme-media-provider');
    const results = document.getElementById('wp-theme-media-results');
    const status = document.getElementById('wp-theme-media-status');

    if (!toggleBtn || !panel || !query || !searchBtn) return;

    function setStatus(message, type) {
      if (!status) return;
      status.textContent = message || '';
      status.className = 'wp-theme-media-status' + (type ? ' is-' + type : '');
    }

    function updateQuickLinks() {
      const term = encodeURIComponent(query.value.trim());
      [
        ['wp-theme-quick-unsplash', 'https://unsplash.com/s/photos/'],
        ['wp-theme-quick-pexels', 'https://www.pexels.com/search/'],
        ['wp-theme-quick-pixabay', 'https://pixabay.com/images/search/'],
        ['wp-theme-quick-giphy', 'https://giphy.com/search/'],
        ['wp-theme-quick-burst', 'https://burst.shopify.com/photos/search?utf8=%E2%9C%93&q='],
        ['wp-theme-quick-stocksnap', 'https://stocksnap.io/search/'],
        ['wp-theme-quick-kaboom', 'https://kaboompics.com/gallery?search='],
        ['wp-theme-quick-gratisography', 'https://gratisography.com/?s='],
        ['wp-theme-quick-picjumbo', 'https://picjumbo.com/?s='],
        ['wp-theme-quick-lifeofpix', 'https://www.lifeofpix.com/?s='],
        ['wp-theme-quick-freepik', 'https://www.freepik.com/search?format=search&query=']
      ].forEach(([id, base]) => {
        const el = document.getElementById(id);
        if (el) el.href = base + term;
      });
    }

    toggleBtn.addEventListener('click', function () {
      if (panel.hasAttribute('hidden')) panel.removeAttribute('hidden');
      else panel.setAttribute('hidden', 'hidden');
    });

    function renderResults(items) {
      if (!results) return;
      results.innerHTML = '';
      if (!items.length) {
        setStatus('No images found.', 'info');
        return;
      }
      setStatus('', '');
      items.forEach((item) => {
        const card = document.createElement('article');
        card.className = 'wp-theme-media-card';
        card.innerHTML = `
          <div class="wp-theme-media-thumb"><img src="${item.thumbnail || item.url}" alt="${item.title || ''}"></div>
          <div class="wp-theme-media-copy">
            <strong>${item.title || 'Untitled image'}</strong>
            <p>${item.provider || ''}${item.creator ? ' · by ' + item.creator : ''}</p>
            <p><code>${item.license || ''}</code></p>
            <div class="wp-theme-media-actions">
              <a class="button button-secondary" href="${item.foreign_landing_url || item.url}" target="_blank" rel="noopener">Source</a>
              <button type="button" class="button button-primary" data-import-image>Import</button>
            </div>
          </div>`;
        card.querySelector('[data-import-image]').addEventListener('click', function () {
          importImage(item, card);
        });
        results.appendChild(card);
      });
    }

    function searchImages() {
      updateQuickLinks();
      const providers = Array.from(providerCheckboxes).filter((node) => node.checked).map((node) => node.value);
      if (!query.value.trim()) return setStatus('Enter a search term first.', 'error');
      if (!providers.length) return setStatus('Select at least one provider.', 'error');
      setStatus('Searching images…', 'loading');
      if (results) results.innerHTML = '';

      const formData = new FormData();
      formData.append('action', 'wp_theme_free_image_search');
      formData.append('nonce', window.BBThemeAdminSettings?.nonce || '');
      formData.append('query', query.value.trim());
      providers.forEach((provider) => formData.append('providers[]', provider));

      fetch(window.BBThemeAdminSettings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
        .then((response) => response.json())
        .then((payload) => {
          if (!payload.success) throw new Error((payload.data && payload.data.message) || 'Could not search images.');
          renderResults((payload.data && payload.data.results) || []);
        })
        .catch((error) => setStatus(error.message || 'Could not search images.', 'error'));
    }


    const optimizeIdInput = document.getElementById('wp-theme-optimize-attachment-id');
    const optimizeBtn = document.getElementById('wp-theme-optimize-attachment');

    function optimizeExistingAttachment() {
      const id = optimizeIdInput ? parseInt(optimizeIdInput.value || '0', 10) : 0;
      if (!id) {
        setStatus('Enter a valid attachment ID.', 'error');
        return;
      }
      setStatus('Optimizing attachment…', 'loading');

      const formData = new FormData();
      formData.append('action', 'wp_theme_optimize_existing_attachment');
      formData.append('nonce', window.BBThemeAdminSettings?.nonce || '');
      formData.append('attachment_id', String(id));

      fetch(window.BBThemeAdminSettings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
        .then((response) => response.json())
        .then((payload) => {
          if (!payload.success) throw new Error((payload.data && payload.data.message) || 'Could not optimize attachment.');
          setStatus(payload.data.message || 'Attachment optimized.', 'success');
        })
        .catch((error) => {
          setStatus(error.message || 'Could not optimize attachment.', 'error');
        });
    }

    function importImage(item, card) {
      const button = card.querySelector('[data-import-image]');
      button.disabled = true;
      button.textContent = 'Importing image…';
      const formData = new FormData();
      formData.append('action', 'wp_theme_free_image_import');
      formData.append('nonce', window.BBThemeAdminSettings?.nonce || '');
      formData.append('image_url', item.url || '');
      formData.append('title', item.title || '');
      formData.append('creator', item.creator || '');
      formData.append('license', item.license || '');
      formData.append('source_url', item.foreign_landing_url || '');
      formData.append('provider', item.provider || '');

      fetch(window.BBThemeAdminSettings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: formData })
        .then((response) => response.json())
        .then((payload) => {
          if (!payload.success) throw new Error((payload.data && payload.data.message) || 'Could not import image.');
          button.textContent = 'Imported';
          button.classList.remove('button-primary');
          button.classList.add('button-secondary');
          setStatus(payload.data.message || 'Imported to Media Library.', 'success');
        })
        .catch((error) => {
          button.disabled = false;
          button.textContent = 'Import';
          setStatus(error.message || 'Could not import image.', 'error');
        });
    }

    searchBtn.addEventListener('click', searchImages);
    if (optimizeBtn) optimizeBtn.addEventListener('click', optimizeExistingAttachment);
    query.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        searchImages();
      }
    });
    query.addEventListener('input', updateQuickLinks);
    updateQuickLinks();
  }

  if (body.classList.contains('settings_page_wp-theme-settings')) {
    initTypographyUnits();
    initAnimationUI();
  }
  if (body.classList.contains('upload-php')) initMediaLibraryImport();
});


document.addEventListener('DOMContentLoaded', function () {
  const demoBtn = document.getElementById('wp-theme-import-demo-homepage');
  const demoStatus = document.getElementById('wp-theme-demo-import-status');
  if (!demoBtn) return;

  demoBtn.addEventListener('click', function () {
    demoBtn.disabled = true;
    if (demoStatus) {
      demoStatus.textContent = (window.BBThemeAdminSettings?.strings?.demoImportText) || 'Importing demo homepage…';
    }

    const formData = new FormData();
    formData.append('action', 'wp_theme_import_demo_homepage');
    formData.append('nonce', window.BBThemeAdminSettings?.nonce || '');

    fetch(window.BBThemeAdminSettings.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then((response) => response.json())
      .then((payload) => {
        if (!payload.success) {
          throw new Error((payload.data && payload.data.message) || 'Import failed.');
        }
        let text = (payload.data && payload.data.message) || (window.BBThemeAdminSettings?.strings?.demoImportedText) || 'Demo homepage imported.';
        if (payload.data && payload.data.editUrl) {
          text += ' Edit: ' + payload.data.editUrl;
        }
        if (payload.data && payload.data.viewUrl) {
          text += ' View: ' + payload.data.viewUrl;
        }
        if (demoStatus) demoStatus.textContent = text;
      })
      .catch((error) => {
        if (demoStatus) demoStatus.textContent = error.message || 'Import failed.';
        demoBtn.disabled = false;
      });
  });
});
