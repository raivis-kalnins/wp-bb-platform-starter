(function($){
  'use strict';
  function setStatus(button,message,isError){
    var scope=button.closest('form.cart,.product');
    var el=scope.find('[data-quote-status]').first();
    if(!el.length){ el=$('<span class="wp-theme-quote-status" data-quote-status aria-live="polite"></span>'); button.after(el); }
    el.text(message||'').toggleClass('is-error',!!isError);
  }
  function updateCount(count){ $('[data-quote-count]').text(count); }
  function send(data,button){
    data.append('action','wp_theme_woo_quote_add'); data.append('nonce',wpThemeWooQuote.nonce);
    button.prop('disabled',true).addClass('is-loading'); setStatus(button,'Adding…',false);
    $.ajax({url:wpThemeWooQuote.ajaxUrl,type:'POST',data:data,processData:false,contentType:false}).done(function(res){
      if(res&&res.success){ setStatus(button,res.data.message||'Added to quote.',false); updateCount(res.data.count||0); $(document.body).trigger('wp_theme_quote_updated',[res.data]); }
      else setStatus(button,(res&&res.data&&res.data.message)||'Could not add this item.',true);
    }).fail(function(xhr){ var msg=(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message)||'Could not add this item.'; setStatus(button,msg,true); }).always(function(){button.prop('disabled',false).removeClass('is-loading');});
  }
  $(document).on('click','[data-quote-single]',function(e){
    e.preventDefault(); var button=$(this), form=button.closest('form.cart'); if(!form.length)return;
    var variation=form.find('input[name="variation_id"]'); if(variation.length && (!variation.val() || variation.val()==='0')){ setStatus(button,wpThemeWooQuote.variationMessage,true); return; }
    var data=new FormData(form[0]); if(!data.get('product_id')) data.set('product_id',button.data('product-id')||data.get('add-to-cart')||''); send(data,button);
  });
  $(document).on('click','[data-quote-loop]',function(e){ e.preventDefault(); var button=$(this), data=new FormData(); data.set('product_id',button.data('product-id')); data.set('quantity','1'); send(data,button); });
})(jQuery);
