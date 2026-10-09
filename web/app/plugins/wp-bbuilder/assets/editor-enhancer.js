(function(){
  if (typeof wp === 'undefined' || !wp.domReady) return;
  function initEditors(root){
    if (!root || !wp.codeEditor || !wp.codeEditor.initialize) return;
    var areas = root.querySelectorAll('.wpbb-code-editor textarea');
    Array.prototype.forEach.call(areas, function(textarea){
      if (textarea.dataset.wpbbEditorReady === '1') return;
      textarea.dataset.wpbbEditorReady = '1';
      try {
        var instance = wp.codeEditor.initialize(textarea, (window.wpbbEditorEnhancer && window.wpbbEditorEnhancer.scss) || { codemirror: { mode: 'text/x-scss', lineNumbers: true, lineWrapping: true } });
        if (instance && instance.codemirror) {
          textarea.wpbbCodeMirror = instance.codemirror;
          if (textarea.readOnly) instance.codemirror.setOption('readOnly', true);
          instance.codemirror.on('change', function(cm){
            try {
              textarea.value = cm.getValue();
              textarea.dispatchEvent(new Event('input', { bubbles: true }));
              textarea.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (err) {}
          });
        }
      } catch (e) {}
    });
  }
  function scheduleInit(root) {
    var run = function () { initEditors(root); };
    if (window.requestIdleCallback) {
      window.requestIdleCallback(run, { timeout: 300 });
    } else {
      window.setTimeout(run, 0);
    }
  }

  wp.domReady(function(){
    scheduleInit(document);
    if (typeof MutationObserver !== 'undefined') {
      var observerRoot = document.querySelector('.interface-interface-skeleton') || document.body;
      var observer = new MutationObserver(function(mutations){
        for (var mutationIndex = 0; mutationIndex < mutations.length; mutationIndex++) {
          var nodes = mutations[mutationIndex].addedNodes || [];
          for (var nodeIndex = 0; nodeIndex < nodes.length; nodeIndex++) {
            var node = nodes[nodeIndex];
            if (!node || node.nodeType !== 1) continue;
            if ((node.matches && node.matches('.wpbb-code-editor, .wpbb-code-editor textarea')) ||
                (node.querySelector && node.querySelector('.wpbb-code-editor textarea'))) {
              scheduleInit(node);
            }
          }
        }
      });
      observer.observe(observerRoot, { childList: true, subtree: true });
    }
  });
})();
