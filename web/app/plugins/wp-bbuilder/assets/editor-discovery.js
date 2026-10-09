(function (wp, config) {
  'use strict';
  if (!wp || !config) return;

  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useMemo = wp.element.useMemo;
  var useState = wp.element.useState;
  var __ = wp.i18n.__;
  var addFilter = wp.hooks && wp.hooks.addFilter;
  var catalog = Array.isArray(config.items) ? config.items : [];
  var byName = {};

  catalog.forEach(function (item) { byName[item.name] = item; });

  /* Enrich only editor metadata. Saved block attributes and markup are untouched. */
  if (addFilter) {
    addFilter('blocks.registerBlockType', 'wpbb/editor-discovery-metadata', function (settings, name) {
      var item = byName[name];
      if (!item) return settings;
      var next = Object.assign({}, settings);
      if (!next.description) next.description = item.description;
      var keywords = Array.isArray(next.keywords) ? next.keywords.slice() : [];
      (item.keywords || []).forEach(function (keyword) {
        if (keywords.indexOf(keyword) === -1) keywords.push(keyword);
      });
      next.keywords = keywords.slice(0, 3);
      return next;
    });
  }

  if (!wp.plugins || !wp.editPost || !wp.components || !wp.blockEditor) return;

  var PluginSidebar = wp.editPost.PluginSidebar;
  var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
  var SearchControl = wp.components.SearchControl;
  var Button = wp.components.Button;
  var PanelBody = wp.components.PanelBody;
  var Notice = wp.components.Notice;

  function normalize(value) {
    return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
  }

  function canInsert(name) {
    var type = wp.blocks.getBlockType(name);
    if (!type) return false;
    var selector = wp.data.select('core/block-editor');
    return !selector || typeof selector.canInsertBlockType !== 'function' || selector.canInsertBlockType(name);
  }

  function insertBlock(item) {
    if (!canInsert(item.name)) return;
    var block = wp.blocks.createBlock(item.name);
    wp.data.dispatch('core/block-editor').insertBlocks(block);
  }

  function BlockCard(props) {
    var item = props.item;
    var available = canInsert(item.name);
    return el('article', { className: 'wpbb-discovery-card' }, [
      el('div', { className: 'wpbb-discovery-card__copy', key: 'copy' }, [
        el('h3', { key: 'title' }, item.title),
        el('p', { key: 'description' }, item.description),
        item.bestFor ? el('p', { className: 'wpbb-discovery-card__best', key: 'best' }, [
          el('strong', { key: 'label' }, __('Best for:', 'wp-bbuilder') + ' '),
          item.bestFor
        ]) : null
      ]),
      el(Button, {
        key: 'button',
        variant: 'secondary',
        size: 'compact',
        disabled: !available,
        onClick: function () { insertBlock(item); },
        className: 'wpbb-discovery-card__button'
      }, available ? config.insertLabel : __('Unavailable here', 'wp-bbuilder'))
    ]);
  }

  function Guide() {
    var state = useState('');
    var search = state[0];
    var setSearch = state[1];
    var query = normalize(search);

    var groups = useMemo(function () {
      var out = {};
      catalog.forEach(function (item) {
        var haystack = normalize([
          item.title,
          item.description,
          item.group,
          item.bestFor,
          (item.keywords || []).join(' ')
        ].join(' '));
        if (query && haystack.indexOf(query) === -1) return;
        if (!out[item.group]) out[item.group] = [];
        out[item.group].push(item);
      });
      return out;
    }, [query]);

    var names = Object.keys(groups);
    return el(Fragment, {}, [
      el('div', { className: 'wpbb-discovery-intro', key: 'intro' }, [
        el('p', { key: 'text' }, config.intro),
        el(SearchControl, {
          key: 'search',
          label: config.searchLabel,
          hideLabelFromVision: true,
          placeholder: config.searchLabel,
          value: search,
          onChange: setSearch
        })
      ]),
      names.length ? names.map(function (group) {
        return el(PanelBody, {
          title: group + ' (' + groups[group].length + ')',
          initialOpen: names.length <= 3,
          key: group,
          className: 'wpbb-discovery-group'
        }, groups[group].map(function (item) {
          return el(BlockCard, { item: item, key: item.name });
        }));
      }) : el(Notice, { status: 'info', isDismissible: false, key: 'empty' }, config.emptyLabel)
    ]);
  }

  wp.plugins.registerPlugin('wpbb-editor-discovery', {
    render: function () {
      return el(Fragment, {}, [
        el(PluginSidebarMoreMenuItem, { target: 'wpbb-editor-discovery-sidebar', key: 'menu' }, config.title),
        el(PluginSidebar, {
          name: 'wpbb-editor-discovery-sidebar',
          title: config.title,
          icon: 'screenoptions',
          key: 'sidebar'
        }, el(Guide))
      ]);
    }
  });
})(window.wp, window.wpbbEditorDiscovery);
