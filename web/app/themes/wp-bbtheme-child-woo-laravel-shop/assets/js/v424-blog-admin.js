(function($){'use strict';
  function init(box){
    var input=box.querySelector('[data-gallery-ids]'),preview=box.querySelector('[data-gallery-preview]'),select=box.querySelector('[data-gallery-select]'),clear=box.querySelector('[data-gallery-clear]');if(!input||!preview||!select)return;
    var frame;
    function ids(){return (input.value||'').split(',').map(function(v){return parseInt(v,10)||0;}).filter(Boolean);}
    function render(items){preview.innerHTML='';items.forEach(function(a){var span=document.createElement('span');span.dataset.id=a.id;var img=document.createElement('img');img.src=(a.sizes&&a.sizes.thumbnail&&a.sizes.thumbnail.url)||a.url;img.alt='';var b=document.createElement('button');b.type='button';b.textContent='×';b.setAttribute('aria-label','Remove');b.addEventListener('click',function(){var left=ids().filter(function(id){return id!==a.id;});input.value=left.join(',');span.remove();});span.appendChild(img);span.appendChild(b);preview.appendChild(span);});}
    select.addEventListener('click',function(e){e.preventDefault();if(frame){frame.open();return;}frame=wp.media({title:'Select article gallery',button:{text:'Use gallery'},multiple:true,library:{type:'image'}});frame.on('open',function(){var sel=frame.state().get('selection');ids().forEach(function(id){var a=wp.media.attachment(id);a.fetch();sel.add(a);});});frame.on('select',function(){var data=frame.state().get('selection').toJSON();input.value=data.map(function(a){return a.id;}).join(',');render(data);});frame.open();});
    if(clear)clear.addEventListener('click',function(e){e.preventDefault();input.value='';preview.innerHTML='';});
  }
  document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('[data-wpbb-admin-gallery]').forEach(init);});
})(jQuery);
