(function($){
  function init(box){
    var $box=$(box),$field=$box.closest('.inside').find('#wp-theme-item-gallery-ids'),frame;
    function ids(){return String($field.val()||'').split(',').map(function(v){return parseInt(v,10)||0}).filter(Boolean)}
    function render(list){$field.val(list.join(','));var $wrap=$box.empty();list.forEach(function(id){wp.media.attachment(id).fetch().then(function(){var a=wp.media.attachment(id).toJSON(),url=(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail.url:a.url);$wrap.append('<span class="wp-theme-item-gallery-admin__thumb" data-id="'+id+'"><img src="'+url+'" alt=""><button type="button" data-gallery-remove aria-label="Remove image">&times;</button></span>')})})}
    $box.closest('.inside').on('click','[data-gallery-select]',function(e){e.preventDefault();if(!frame){frame=wp.media({title:'Select gallery images',button:{text:'Use selected images'},multiple:true});frame.on('select',function(){render(frame.state().get('selection').map(function(a){return a.id}))})}var current=ids(),selection=frame.state().get('selection');selection.reset();current.forEach(function(id){selection.add(wp.media.attachment(id))});frame.open()});
    $box.closest('.inside').on('click','[data-gallery-remove]',function(e){e.preventDefault();var id=parseInt($(this).closest('[data-id]').attr('data-id'),10);render(ids().filter(function(v){return v!==id}))});
    $box.closest('.inside').on('click','[data-gallery-clear]',function(e){e.preventDefault();render([])});
  }
  $(function(){$('[data-item-gallery-admin]').each(function(){init(this)})});
})(jQuery);
