jQuery(function ($) {
  if (typeof WPThemeOrdering === 'undefined') return;

  const $table = $('#the-list');
  if (!$table.length) return;

  $('body').addClass('wp-theme-ordering-active');

  const saveNotice = $('<div class="notice notice-success inline" style="display:none"><p></p></div>');
  $('.wrap h1').first().after(saveNotice);

  function setNotice(message, type) {
    if (!message) {
      saveNotice.hide();
      return;
    }
    saveNotice.removeClass('notice-success notice-error notice-warning').addClass('notice notice-' + type + ' inline');
    saveNotice.find('p').text(message);
    saveNotice.show();
  }

  $table.sortable({
    items: '> tr[type!=hidden], > tr',
    axis: 'y',
    handle: '.wp-theme-order-handle, .check-column, .column-title .row-title',
    helper: function (e, ui) {
      ui.children().each(function () {
        $(this).width($(this).width());
      });
      return ui;
    },
    start: function (event, ui) {
      ui.item.addClass('wp-theme-order-saving');
      setNotice(WPThemeOrdering.messages.saving, 'warning');
    },
    stop: function (event, ui) {
      ui.item.removeClass('wp-theme-order-saving');
    },
    update: function () {
      const ids = [];
      $table.children('tr').each(function () {
        const match = (this.id || '').match(/^post-(\d+)$/);
        if (match) ids.push(match[1]);
      });
      $.post(WPThemeOrdering.ajaxUrl, {
        action: 'wp_theme_save_menu_order',
        nonce: WPThemeOrdering.nonce,
        post_type: WPThemeOrdering.postType,
        ids: ids
      }).done(function (response) {
        if (response && response.success) {
          setNotice(WPThemeOrdering.messages.saved, 'success');
        } else {
          setNotice((response && response.data && response.data.message) || WPThemeOrdering.messages.error, 'error');
        }
      }).fail(function () {
        setNotice(WPThemeOrdering.messages.error, 'error');
      });
    }
  });
});
