document.addEventListener('DOMContentLoaded', function () {
  const tabs = document.querySelectorAll('.bbtheme-animation-tabs .nav-tab');
  const panels = document.querySelectorAll('.bbtheme-tab-panel');
  const previewSelect = document.getElementById('bbtheme-preview-select');
  const previewTrigger = document.getElementById('bbtheme-preview-trigger');
  const previewBox = document.getElementById('bbtheme-preview-box');
  const defaultDuration = document.getElementById('bbtheme-default-duration');
  const defaultDelay = document.getElementById('bbtheme-default-delay');
  const defaultRepeat = document.getElementById('bbtheme-default-repeat');
  const previewTextInput = document.getElementById('bbtheme-preview-text');
  const searchInput = document.getElementById('bbtheme-animation-search');

  function showTab(hash) {
    tabs.forEach((tab) => {
      tab.classList.toggle('nav-tab-active', tab.getAttribute('href') === hash);
    });
    panels.forEach((panel) => {
      panel.classList.toggle('is-active', '#' + panel.id === hash);
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

  if (window.location.hash && document.querySelector(window.location.hash)) {
    showTab(window.location.hash);
  }

  function replayPreview(animationClass) {
    if (!previewBox) return;

    const className = animationClass || (previewSelect ? previewSelect.value : '');
    const allClasses = previewBox.className.split(/\s+/).filter((item) => item.indexOf('animate__') !== 0 || item === 'animate__animated');
    previewBox.className = allClasses.join(' ').trim();
    previewBox.classList.add('animate__animated');

    if (className) {
      previewBox.classList.add(className);
    }

    previewBox.style.setProperty('--animate-duration', (defaultDuration && defaultDuration.value) ? defaultDuration.value : '1s');
    previewBox.style.setProperty('--animate-delay', (defaultDelay && defaultDelay.value) ? defaultDelay.value : '0s');
    previewBox.style.setProperty('--animate-repeat', (defaultRepeat && defaultRepeat.value) ? defaultRepeat.value : '1');

    const label = previewTextInput && previewTextInput.value ? previewTextInput.value : (window.bbthemeAnimationAdmin?.defaultPreviewText || 'Animation preview');
    previewBox.innerHTML = '<strong>' + label + '</strong><span>' + (className || '') + '</span>';

    void previewBox.offsetWidth;
    if (className) {
      previewBox.classList.remove(className);
      void previewBox.offsetWidth;
      previewBox.classList.add(className);
    }
  }

  if (previewSelect) {
    previewSelect.addEventListener('change', function () {
      replayPreview(previewSelect.value);
    });
  }

  [defaultDuration, defaultDelay, defaultRepeat, previewTextInput].forEach((input) => {
    if (!input) return;
    input.addEventListener('input', function () {
      replayPreview(previewSelect ? previewSelect.value : '');
    });
  });

  if (previewTrigger) {
    previewTrigger.addEventListener('click', function () {
      replayPreview(previewSelect ? previewSelect.value : '');
    });
  }

  document.querySelectorAll('.bbtheme-preview-row').forEach((button) => {
    button.addEventListener('click', function () {
      const animationClass = button.getAttribute('data-animation');
      if (previewSelect) {
        previewSelect.value = animationClass;
      }
      replayPreview(animationClass);
      showTab('#bbtheme-tab-general');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('.bbtheme-copy-class').forEach((button) => {
    button.addEventListener('click', async function () {
      const className = button.getAttribute('data-class') || '';
      try {
        await navigator.clipboard.writeText(className);
        button.classList.add('is-copied');
        button.textContent = window.bbthemeAnimationAdmin?.copiedLabel || 'Copied';
        setTimeout(function () {
          button.classList.remove('is-copied');
          button.textContent = window.bbthemeAnimationAdmin?.copyLabel || 'Copy class';
        }, 1400);
      } catch (error) {
        window.prompt('Copy class name:', className);
      }
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const term = searchInput.value.trim().toLowerCase();
      document.querySelectorAll('.bbtheme-animation-table tbody tr').forEach((row) => {
        const haystack = row.getAttribute('data-search') || '';
        row.style.display = !term || haystack.indexOf(term) !== -1 ? '' : 'none';
      });
    });
  }


  const nativePreview = document.querySelector('[data-bbtheme-native-preview]');
  if (nativePreview) {
    const nativeObject = nativePreview.querySelector('.bbtheme-native-preview__object');
    const nativeCode = nativePreview.querySelector('[data-native-motion-code]');
    nativePreview.querySelectorAll('[data-native-motion]').forEach(function (button) {
      button.addEventListener('click', function () {
        const motion = button.getAttribute('data-native-motion') || 'motion-fade-up';
        nativePreview.querySelectorAll('[data-native-motion]').forEach(function (item) { item.classList.toggle('is-selected', item === button); });
        if (nativeCode) nativeCode.textContent = motion;
        if (!nativeObject) return;
        nativeObject.className = 'bbtheme-native-preview__object ' + motion;
        void nativeObject.offsetWidth;
        window.requestAnimationFrame(function () { nativeObject.classList.add('is-visible'); });
      });
    });
  }
  replayPreview(previewSelect ? previewSelect.value : '');
});
