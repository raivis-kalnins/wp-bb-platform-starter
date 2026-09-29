(function (wp, cfg) {
  if (!wp || !cfg || !Array.isArray(cfg.patterns) || !cfg.patterns.length) return;
  const el = wp.element.createElement;
  const useState = wp.element.useState;
  const Fragment = wp.element.Fragment;
  const Button = wp.components.Button;
  const Modal = wp.components.Modal;
  const TextControl = wp.components.TextControl;

  function PatternModal(props) {
    const state = useState('');
    const term = state[0];
    const setTerm = state[1];
    const filtered = cfg.patterns.filter(function (pattern) {
      const haystack = ((pattern.title || '') + ' ' + (pattern.description || '')).toLowerCase();
      return !term || haystack.indexOf(term.toLowerCase()) !== -1;
    });
    function insertPattern(pattern) {
      const blocks = wp.blocks.parse(pattern.content || '');
      if (blocks && blocks.length) {
        wp.data.dispatch('core/block-editor').insertBlocks(blocks);
      }
      props.onRequestClose();
    }
    return el(Modal, {title: cfg.title, onRequestClose: props.onRequestClose, className: 'wp-theme-pattern-modal'},
      el(TextControl, {label: cfg.search, value: term, onChange: setTerm, className: 'wp-theme-pattern-modal__search'}),
      filtered.length ? el('div', {className: 'wp-theme-pattern-modal__grid'}, filtered.map(function (pattern) {
        return el('article', {className: 'wp-theme-pattern-card', key: pattern.name || pattern.title},
          el('h3', null, pattern.title),
          pattern.description ? el('p', null, pattern.description) : null,
          el(Button, {variant: 'primary', onClick: function () { insertPattern(pattern); }}, cfg.insert)
        );
      })) : el('p', null, cfg.empty)
    );
  }

  function Launcher() {
    const state = useState(false);
    const open = state[0];
    const setOpen = state[1];
    return el(Fragment, null,
      el(Button, {variant: 'secondary', icon: 'layout', className: 'wp-theme-pattern-launcher', onClick: function(){ setOpen(true); }}, cfg.button),
      open ? el(PatternModal, {onRequestClose: function(){ setOpen(false); }}) : null
    );
  }

  function mount() {
    if (document.querySelector('.wp-theme-pattern-launcher-root')) return true;
    const toolbar = document.querySelector('.edit-post-header-toolbar, .editor-header__toolbar');
    if (!toolbar) return false;
    const root = document.createElement('div');
    root.className = 'wp-theme-pattern-launcher-root';
    toolbar.appendChild(root);
    if (wp.element.createRoot) {
      wp.element.createRoot(root).render(el(Launcher));
    } else if (wp.element.render) {
      wp.element.render(el(Launcher), root);
    }
    return true;
  }

  if (!mount()) {
    let tries = 0;
    const timer = window.setInterval(function(){
      tries += 1;
      if (mount() || tries > 40) window.clearInterval(timer);
    }, 250);
  }
})(window.wp, window.wpThemePatternLauncher);
