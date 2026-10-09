(function($){
    $(function(){
        $('.wpbb-card').each(function(i,el){
            var $card = $(el);
            var $h2 = $card.find('h2').first();
            if(!$h2.length) return;
            // wrap body if not wrapped
            var $next = $h2.next();
            if(!$next.hasClass('wpbb-card-body')){
                var $wrap = $('<div class="wpbb-card-body"></div>');
                var $siblings = $h2.nextAll();
                $siblings.wrapAll($wrap);
            }
            var id = $card.attr('id') || ('wpbb-card-' + i);
            $card.attr('id', id);
            var $btn = $('<button type="button" class="wpbb-card-toggle" aria-expanded="true">▾</button>');
            $h2.append($btn);
            // restore collapsed state
            try{
                var stored = localStorage.getItem('wpbb_admin_card_collapsed_' + id);
                if(stored === '1'){
                    $card.addClass('collapsed');
                    $btn.attr('aria-expanded','false').text('▸');
                }
            }catch(e){}
            $btn.on('click', function(e){
                e.preventDefault();
                $card.toggleClass('collapsed');
                var collapsed = $card.hasClass('collapsed');
                $btn.attr('aria-expanded', !collapsed).text(collapsed ? '▸' : '▾');
                try{ localStorage.setItem('wpbb_admin_card_collapsed_' + id, collapsed ? '1' : '0'); }catch(e){}
            });
        });
    });
})(jQuery);
