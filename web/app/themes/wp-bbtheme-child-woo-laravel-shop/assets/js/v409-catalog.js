(function($){
  'use strict';
  var $form, $grid, $status, $load;
  function boot(){
    $form=$('#wpbbshop-v409-filter-form'); $grid=$('#wpbbshop-archive-products');
    $status=$('#wpbbshop-v409-result-status'); $load=$('.wpbbshop-v409-load-more');
    if(!$form.length || !$grid.length || typeof WPBBShopCatalog409==='undefined') return;
  }
  function payload(page){
    var data={action:'wpbbshop_v409_catalog',lang:WPBBShopCatalog409.lang,nonce:WPBBShopCatalog409.nonce,page:page||1};
    $.each($form.serializeArray(),function(_,item){ data[item.name]=item.value; });
    $form.find('input[type=checkbox]').each(function(){ data[this.name]=this.checked ? '1' : ''; });
    return data;
  }
  function request(page, append){
    if(!$form || !$form.length) boot();
    if(!$form.length) return;
    $form.addClass('is-loading');
    if($load.length) $load.prop('disabled',true).addClass('is-loading').find('span').text(WPBBShopCatalog409.loading);
    $.post(WPBBShopCatalog409.ajaxUrl,payload(page)).done(function(res){
      if(!res || !res.success || !res.data){ return; }
      if(append) $grid.append(res.data.html || ''); else $grid.html(res.data.html || '<div class="wpbbshop-v409-empty">'+WPBBShopCatalog409.empty+'</div>');
      if($status.length) $status.text(res.data.status || '');
      if(res.data.has_more){
        if(!$load.length){ $('.wpbbshop-v409-load-more-wrap').html('<button type="button" class="wpbbshop-v409-load-more" data-page="'+page+'"><span>'+WPBBShopCatalog409.loadMore+'</span></button>'); $load=$('.wpbbshop-v409-load-more'); }
        $load.attr('data-page',page).prop('disabled',false).removeClass('is-loading').find('span').text(WPBBShopCatalog409.loadMore);
      } else { $('.wpbbshop-v409-load-more-wrap').empty(); $load=$(); }
    }).fail(function(){ if($load.length) $load.prop('disabled',false).removeClass('is-loading').find('span').text(WPBBShopCatalog409.retry); })
      .always(function(){ $form.removeClass('is-loading'); });
  }
  $(document).on('submit','#wpbbshop-v409-filter-form',function(e){ e.preventDefault(); boot(); request(1,false); });
  $(document).on('click','.wpbbshop-v409-reset',function(){
    var form=document.getElementById('wpbbshop-v409-filter-form'); if(!form) return; form.reset();
    $('#wpbbshop-v409-filter-form input[name=min_price],#wpbbshop-v409-filter-form input[name=max_price],#wpbbshop-v409-filter-form input[name=wpbbshop_filter_search]').val('');
    $('#wpbbshop-v409-filter-form select[name=product_cat],#wpbbshop-v409-filter-form select[name=orderby]').val('');
    boot(); request(1,false);
  });
  $(document).on('click','.wpbbshop-v409-load-more',function(e){ e.preventDefault(); boot(); var page=parseInt($(this).attr('data-page')||'1',10)+1; request(page,true); });
  $(document).on('click','.wpbbshop-v409-filter-toggle',function(){ var $b=$(this),open=$form.hasClass('is-open'); $form.toggleClass('is-open',!open); $b.attr('aria-expanded',String(!open)); });
  $(boot);
})(jQuery);
