(function($){
  function initEditor(textarea, settings){
    if (!textarea || typeof wp === 'undefined' || !wp.codeEditor) return null;
    try { return wp.codeEditor.initialize(textarea, settings || {}); } catch (e) { return null; }
  }

  function getEditorValue(editor, $fallback){
    if (editor && editor.codemirror) return editor.codemirror.getValue();
    return $fallback.val() || '';
  }

  function setEditorValue(editor, $fallback, value){
    value = value || '';
    $fallback.val(value);
    if (editor && editor.codemirror) {
      editor.codemirror.setValue(value);
      editor.codemirror.save();
    }
  }

  function fitEditor(editor){
    if (!editor || !editor.codemirror) return;
    try {
      editor.codemirror.setSize('100%', null);
      window.requestAnimationFrame(function(){ editor.codemirror.refresh(); });
    } catch (e) {}
  }

  $(function(){
    var scssEditors = new Map();
    var cssEditors = new Map();

    $('.wpbb-code-editor--scss').each(function(){
      var editor = initEditor(this, (window.wpbbBuilder && window.wpbbBuilder.scss) || {});
      scssEditors.set(this, editor);
      fitEditor(editor);
    });

    $('.wpbb-code-editor--html').each(function(){
      initEditor(this, (window.wpbbBuilder && window.wpbbBuilder.html) || {});
    });

    $('.wpbb-code-editor--css-output').each(function(){
      var editor = initEditor(this, (window.wpbbBuilder && window.wpbbBuilder.css) || {});
      if (editor && editor.codemirror) editor.codemirror.setOption('readOnly', true);
      cssEditors.set(this, editor);
      fitEditor(editor);
    });

    $(document).on('click', '#scss-builder .wpbb-card-toggle', function(){
      window.setTimeout(function(){
        $('#scss-builder .wpbb-code-editor--scss').each(function(){ fitEditor(scssEditors.get(this)); });
        $('#scss-builder .wpbb-code-editor--css-output').each(function(){ fitEditor(cssEditors.get(this)); });
      }, 0);
    });

    $(document).on('click', '.wpbb-build-scss', function(e){
      e.preventDefault();

      var $btn = $(this);
      var $card = $btn.closest('.wpbb-card, .postbox, form, body');
      var $status = $card.find('.wpbb-build-status').first();
      var $scssField = $card.find('.wpbb-code-editor--scss').first();
      var $cssField = $card.find('.wpbb-code-editor--css-output').first();

      if (!$scssField.length) {
        $status.text('SCSS field not found.');
        return;
      }

      var scssEditor = scssEditors.get($scssField.get(0));
      var cssEditor = $cssField.length ? cssEditors.get($cssField.get(0)) : null;
      var scss = getEditorValue(scssEditor, $scssField);

      if (scssEditor && scssEditor.codemirror) scssEditor.codemirror.save();
      $scssField.val(scss);

      $btn.prop('disabled', true);
      $status.text('Building...');

      $.ajax({
        url: (window.wpbbBuilder && window.wpbbBuilder.ajaxUrl) || ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'wpbb_compile_scss',
          nonce: window.wpbbBuilder ? window.wpbbBuilder.nonce : '',
          scss: scss
        }
      }).done(function(resp){
        if (resp && resp.success) {
          var css = resp.data && resp.data.css ? resp.data.css : '';
          if ($cssField.length) setEditorValue(cssEditor, $cssField, css);
          $status.text((window.wpbbBuilder && window.wpbbBuilder.compiledText) || 'SCSS compiled successfully.');
        } else {
          var msg = (resp && resp.data && resp.data.message) ? resp.data.message : ((window.wpbbBuilder && window.wpbbBuilder.errorText) || 'Build failed.');
          $status.text(msg);
        }
      }).fail(function(xhr){
        var msg = (window.wpbbBuilder && window.wpbbBuilder.errorText) || 'Build failed.';
        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          msg = xhr.responseJSON.data.message;
        }
        $status.text(msg);
      }).always(function(){
        $btn.prop('disabled', false);
      });
    });
  });
})(jQuery);


(function(){
  function initAdminScssCompiler(){
    var input = document.querySelector('[data-wpbb-admin-scss-input]');
    var hiddenScss = document.querySelector('[data-wpbb-admin-scss-hidden]');
    var hiddenCss = document.querySelector('[data-wpbb-admin-css-hidden]');
    var preview = document.querySelector('[data-wpbb-admin-css-preview]');
    var buildBtn = document.querySelector('[data-wpbb-admin-scss-build]');
    var status = document.querySelector('[data-wpbb-admin-scss-status]');
    if (!input || !buildBtn || !hiddenScss || !hiddenCss || !preview || !window.wpbbBuilder) return;

    function getEditorValue(textarea){
      try {
        if (textarea.nextSibling && textarea.nextSibling.CodeMirror) {
          return textarea.nextSibling.CodeMirror.getValue();
        }
        if (textarea.CodeMirror) {
          return textarea.CodeMirror.getValue();
        }
      } catch(e){}
      return textarea.value || '';
    }

    function setEditorValue(textarea, value){
      try {
        if (textarea.nextSibling && textarea.nextSibling.CodeMirror) {
          textarea.nextSibling.CodeMirror.setValue(value);
        } else if (textarea.CodeMirror) {
          textarea.CodeMirror.setValue(value);
        } else {
          textarea.value = value;
        }
      } catch(e) {
        textarea.value = value;
      }
    }

    buildBtn.addEventListener('click', function(){
      var scss = getEditorValue(input);
      hiddenScss.value = scss;
      status.textContent = 'Building...';

      var payload = new URLSearchParams();
      payload.append('action', 'wpbb_compile_scss');
      payload.append('nonce', window.wpbbBuilder.nonce || '');
      payload.append('scss', scss);

      fetch(window.wpbbBuilder.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        credentials: 'same-origin',
        body: payload.toString()
      })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (res && res.success) {
          var css = (res.data && res.data.css) ? res.data.css : '';
          hiddenCss.value = css;
          preview.value = css;
          setEditorValue(preview, css);
          status.textContent = window.wpbbBuilder.compiledText || 'SCSS compiled successfully.';
        } else {
          status.textContent = (res && res.data && res.data.message) ? res.data.message : (window.wpbbBuilder.errorText || 'Build failed.');
        }
      })
      .catch(function(){
        status.textContent = window.wpbbBuilder.errorText || 'Build failed.';
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminScssCompiler);
  } else {
    initAdminScssCompiler();
  }
})();


(function($){
  $(function(){
    var $scss = $('[data-wpbb-admin-scss-input]');
    var $css = $('[data-wpbb-admin-css-preview]');
    if ($scss.length && !$scss.hasClass('wpbb-code-editor--scss')) $scss.addClass('wpbb-code-editor--scss');
    if ($css.length && !$css.hasClass('wpbb-code-editor--css-output')) $css.addClass('wpbb-code-editor--css-output');
  });
})(jQuery);

(function(){
  function initRedirectBuilder(){
    var wrapper = document.querySelector('[data-wpbb-redirects-builder]');
    if (!wrapper) return;
    var input = wrapper.querySelector('[data-wpbb-redirects-input]');
    var rows = wrapper.querySelector('[data-wpbb-redirects-rows]');
    var addBtn = wrapper.querySelector('[data-wpbb-add-redirect]');
    if (!input || !rows || !addBtn) return;

    function parse(){
      try {
        var data = JSON.parse(input.value || '[]');
        return Array.isArray(data) ? data : [];
      } catch(e){ return []; }
    }
    function sync(){
      var data = [];
      rows.querySelectorAll('[data-wpbb-redirect-row]').forEach(function(row){
        var from = row.querySelector('[data-wpbb-redirect-from]').value || '';
        var to = row.querySelector('[data-wpbb-redirect-to]').value || '';
        var code = row.querySelector('[data-wpbb-redirect-code]').value || '301';
        if (!from.trim() || !to.trim()) return;
        data.push({from: from.trim(), to: to.trim(), code: code});
      });
      input.value = JSON.stringify(data);
    }
    function row(rule){
      var el = document.createElement('div');
      el.className = 'wpbb-repeatable__row wpbb-repeatable__row--redirect';
      el.setAttribute('data-wpbb-redirect-row','1');
      el.innerHTML =
        '<div class="wpbb-repeatable__field">' +
          '<label class="wpbb-repeatable__label">Old</label>' +
          '<input type="text" placeholder="/old-page" data-wpbb-redirect-from>' +
        '</div>' +
        '<div class="wpbb-repeatable__field">' +
          '<label class="wpbb-repeatable__label">To</label>' +
          '<input type="text" placeholder="/new-page" data-wpbb-redirect-to>' +
        '</div>' +
        '<div class="wpbb-repeatable__field wpbb-repeatable__field--code">' +
          '<label class="wpbb-repeatable__label">Type</label>' +
          '<select data-wpbb-redirect-code><option value="301">301 Permanent</option><option value="302">302 Temporary</option></select>' +
        '</div>' +
        '<div class="wpbb-repeatable__actions">' +
          '<button type="button" class="button-link-delete" data-wpbb-remove-redirect>Remove</button>' +
        '</div>';
      el.querySelector('[data-wpbb-redirect-from]').value = rule && rule.from ? rule.from : '';
      el.querySelector('[data-wpbb-redirect-to]').value = rule && rule.to ? rule.to : '';
      el.querySelector('[data-wpbb-redirect-code]').value = rule && rule.code ? String(rule.code) : '301';
      el.addEventListener('input', sync);
      el.addEventListener('change', sync);
      el.querySelector('[data-wpbb-remove-redirect]').addEventListener('click', function(){ el.remove(); sync(); });
      rows.appendChild(el);
    }
    var data = parse();
    if (!data.length) row({}); else data.forEach(row);
    addBtn.addEventListener('click', function(){ row({}); sync(); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initRedirectBuilder); else initRedirectBuilder();
})();

(function(){
  function initConditionalSettings(){
    var panels = document.querySelectorAll('[data-wpbb-conditional-field]');
    if (!panels.length) return;
    panels.forEach(function(panel){
      var selector = panel.getAttribute('data-wpbb-conditional-field');
      var field = selector ? document.querySelector(selector) : null;
      if (!field) return;
      function sync(){
        panel.style.display = field.checked ? '' : 'none';
      }
      field.addEventListener('change', sync);
      sync();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initConditionalSettings); else initConditionalSettings();
})();

(function(){
  function initSmtpTest(){
    var button = document.querySelector('[data-wpbb-smtp-test]');
    var status = document.querySelector('[data-wpbb-smtp-test-status]');
    var recipient = document.querySelector('[data-wpbb-smtp-test-recipient]');
    if (!button || !status || !recipient || !window.wpbbBuilder) return;

    function field(name){
      return document.querySelector('[data-wpbb-smtp-field="' + name + '"]');
    }

    function value(name){
      var control = field(name);
      return control ? control.value : '';
    }

    function setStatus(message, type){
      status.textContent = message || '';
      status.classList.remove('is-success', 'is-error', 'is-loading');
      if (type) status.classList.add('is-' + type);
    }

    button.addEventListener('click', function(){
      var email = (recipient.value || '').trim();
      if (!email || !recipient.checkValidity()) {
        recipient.reportValidity();
        return;
      }

      button.disabled = true;
      setStatus(window.wpbbBuilder.smtpSendingText || 'Sending test email...', 'loading');

      var payload = new URLSearchParams();
      payload.append('action', 'wpbb_test_smtp');
      payload.append('nonce', window.wpbbBuilder.nonce || '');
      payload.append('recipient', email);
      ['host', 'port', 'encryption', 'username', 'password', 'from_email', 'from_name'].forEach(function(name){
        payload.append(name, value(name));
      });

      fetch(window.wpbbBuilder.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
        body: payload.toString()
      })
      .then(function(response){
        return response.json().catch(function(){
          throw new Error('The server returned an invalid response.');
        });
      })
      .then(function(response){
        if (!response || !response.success) {
          var errorMessage = response && response.data && response.data.message
            ? response.data.message
            : (window.wpbbBuilder.smtpErrorText || 'The SMTP test email could not be sent.');
          throw new Error(errorMessage);
        }
        var successMessage = response.data && response.data.message ? response.data.message : 'Test email sent.';
        var enabled = document.getElementById('wpbb-smtp-enabled');
        if (enabled && !enabled.checked) {
          successMessage += ' ' + (window.wpbbBuilder.smtpDisabledText || 'SMTP is still disabled for real site emails. Enable it and save the settings.');
        }
        setStatus(successMessage, 'success');
      })
      .catch(function(error){
        setStatus(error && error.message ? error.message : (window.wpbbBuilder.smtpErrorText || 'The SMTP test email could not be sent.'), 'error');
      })
      .finally(function(){
        button.disabled = false;
      });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initSmtpTest); else initSmtpTest();
})();
