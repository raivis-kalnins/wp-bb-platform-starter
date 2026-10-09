(function(){
  function init(){
    var grid = document.querySelector('.wpbb-admin-grid');
    if(!grid) return;
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.wpbb-card'));
    if(!cards.length) return;
    var key = 'wpbb-admin-card-order';

    cards.forEach(function(card, index){
      if(!card.id) card.id = 'wpbb-card-' + index;
      card.removeAttribute('draggable');
      card.classList.add('wpbb-card-sortable');

      var existingHandle = Array.prototype.filter.call(card.children, function(child){ return child.classList && child.classList.contains('wpbb-card-drag-handle'); })[0];
      if(!existingHandle){
        var handle = document.createElement('button');
        handle.type = 'button';
        handle.className = 'wpbb-card-drag-handle';
        handle.setAttribute('draggable', 'true');
        handle.setAttribute('aria-label', 'Drag to reorder this settings box');
        handle.setAttribute('title', 'Drag to reorder');
        handle.innerHTML = '<span class="dashicons dashicons-move" aria-hidden="true"></span>';
        card.insertBefore(handle, card.firstChild);
      }
    });

    function saveOrder(){
      var ids = Array.prototype.map.call(grid.querySelectorAll('.wpbb-card'), function(c){ return c.id; });
      try { localStorage.setItem(key, JSON.stringify(ids)); } catch(e){}
    }

    function restoreOrder(){
      try {
        var ids = JSON.parse(localStorage.getItem(key) || '[]');
        ids.forEach(function(id){
          var el = document.getElementById(id);
          if(el) grid.appendChild(el);
        });
      } catch(e){}
    }

    function clearDropClasses(){
      Array.prototype.forEach.call(grid.querySelectorAll('.wpbb-card'), function(c){ c.classList.remove('drop-before','drop-after'); });
    }

    var dragged = null;
    grid.addEventListener('dragstart', function(e){
      var handle = e.target.closest('.wpbb-card-drag-handle');
      if(!handle){
        dragged = null;
        return;
      }
      var card = handle.closest('.wpbb-card');
      if(!card) return;
      dragged = card;
      card.classList.add('is-dragging');
      if(e.dataTransfer){
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.id || '');
        try { e.dataTransfer.setDragImage(card, Math.min(40, card.offsetWidth / 2), 20); } catch(err){}
      }
    });
    grid.addEventListener('dragend', function(e){
      var card = dragged || (e.target.closest ? e.target.closest('.wpbb-card') : null);
      if(card) card.classList.remove('is-dragging');
      dragged = null;
      clearDropClasses();
      saveOrder();
    });
    grid.addEventListener('dragover', function(e){
      if(!dragged) return;
      e.preventDefault();
      var target = e.target.closest('.wpbb-card');
      if(!target || target === dragged) return;
      clearDropClasses();
      var rect = target.getBoundingClientRect();
      var before = e.clientY < rect.top + rect.height / 2;
      target.classList.add(before ? 'drop-before' : 'drop-after');
      if(e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    });
    grid.addEventListener('drop', function(e){
      if(!dragged) return;
      e.preventDefault();
      var target = e.target.closest('.wpbb-card');
      if(!target || target === dragged) return;
      var rect = target.getBoundingClientRect();
      var before = e.clientY < rect.top + rect.height / 2;
      target.classList.remove('drop-before','drop-after');
      if(before){
        grid.insertBefore(dragged, target);
      } else {
        grid.insertBefore(dragged, target.nextSibling);
      }
      saveOrder();
    });

    restoreOrder();
  }
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
