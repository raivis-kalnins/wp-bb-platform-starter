<?php
/**
 * WP BB Home & Garden 3.0.16
 * Final product quantity/gallery, homepage search, mini-cart and shipping layout polish.
 */
defined('ABSPATH') || exit;

function wpbbshop_v316_css() {
    ?>
    <style id="wpbbshop-v316-product-cart-polish-css">
    /* Homepage search styling moved to v3.0.18 to avoid front-page-only cascade conflicts. */

    /* Single product: same wide storefront rhythm as the rest of the shop. */
    body.single-product.wpbbshop-theme .llg-single-container{width:min(1480px,calc(100% - 48px))!important;max-width:1480px!important;margin-inline:auto!important}
    body.single-product.wpbbshop-theme .llg-single-product-card{gap:24px!important}
    body.single-product.wpbbshop-theme .llg-single-summary-panel{padding:28px!important}
    body.single-product.wpbbshop-theme .llg-single-buybox{padding:18px!important;background:#fff!important;border:1px solid #dce6e1!important;box-shadow:inset 0 0 0 1px rgba(47,157,80,.025)!important}
    body.single-product.wpbbshop-theme .llg-single-buybox form.cart{display:flex!important;align-items:center!important;gap:12px!important;row-gap:12px!important}

    /* Product quantity: green +/- controls and a clean number field. */
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity{
      display:inline-grid!important;grid-template-columns:40px 70px 40px!important;align-items:stretch!important;
      gap:0!important;width:150px!important;min-width:150px!important;height:48px!important;margin:0!important;
      border:1px solid #bcd6c5!important;border-radius:9px!important;overflow:hidden!important;background:#fff!important
    }
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .screen-reader-text{position:absolute!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity input.qty{
      grid-column:2!important;width:70px!important;min-width:70px!important;height:46px!important;min-height:46px!important;
      margin:0!important;padding:0 6px!important;border:0!important;border-left:1px solid #d6e4da!important;border-right:1px solid #d6e4da!important;
      border-radius:0!important;background:#fff!important;color:#17364b!important;text-align:center!important;font-size:15px!important;font-weight:850!important;box-shadow:none!important;
      appearance:textfield!important;-moz-appearance:textfield!important
    }
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity input.qty::-webkit-inner-spin-button,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity input.qty::-webkit-outer-spin-button{-webkit-appearance:none!important;margin:0!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity button,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .minus,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .plus,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .llg-qty-step{
      display:flex!important;align-items:center!important;justify-content:center!important;width:40px!important;min-width:40px!important;height:46px!important;min-height:46px!important;
      margin:0!important;padding:0!important;border:0!important;border-radius:0!important;background:#edf8f0!important;color:#237e40!important;
      font-size:20px!important;font-weight:900!important;line-height:1!important;box-shadow:none!important;cursor:pointer!important
    }
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity button:first-of-type,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .minus{grid-column:1!important;grid-row:1!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity button:last-of-type,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .plus{grid-column:3!important;grid-row:1!important}
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity button:hover,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity button:focus-visible,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .minus:hover,
    body.single-product.wpbbshop-theme .llg-single-buybox .quantity .plus:hover{background:#2f9d50!important;color:#fff!important;outline:0!important}

    /* Product gallery button: center the magnifier; modal arrows stay on the viewport sides. */
    body.single-product.wpbbshop-theme .m38gallery-zoom{
      top:16px!important;right:16px!important;width:42px!important;height:42px!important;padding:0!important;margin:0!important;
      display:grid!important;place-items:center!important;border:1px solid #dbe4e9!important;background:#fff!important;color:#526574!important;
      border-radius:50%!important;box-shadow:0 6px 18px rgba(14,45,66,.09)!important;line-height:1!important
    }
    body.single-product.wpbbshop-theme .m38gallery-zoom span{display:none!important}
    body.single-product.wpbbshop-theme .m38gallery-zoom:before{content:""!important;position:absolute!important;left:50%!important;top:50%!important;width:13px!important;height:13px!important;margin:-8px 0 0 -8px!important;border:2px solid currentColor!important;border-radius:50%!important;box-sizing:border-box!important}
    body.single-product.wpbbshop-theme .m38gallery-zoom:after{content:""!important;position:absolute!important;left:50%!important;top:50%!important;width:7px!important;height:2px!important;margin:5px 0 0 3px!important;background:currentColor!important;border-radius:2px!important;transform:rotate(45deg)!important;transform-origin:center!important}
    .m38gallery-lightbox{display:flex!important;align-items:center!important;justify-content:center!important;padding:74px 78px 104px!important}
    .m38gallery-lightbox[hidden]{display:none!important}
    .m38gallery-lightbox .m38stage{display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;height:100%!important;max-height:calc(100dvh - 178px)!important;margin:0!important;grid-column:auto!important;grid-row:auto!important}
    .m38gallery-lightbox .m38close{position:absolute!important;top:22px!important;right:22px!important;z-index:4!important;display:grid!important;place-items:center!important;width:46px!important;height:46px!important;padding:0!important;font-size:29px!important}
    .m38gallery-lightbox .m38arrow{position:absolute!important;top:50%!important;z-index:4!important;width:48px!important;height:48px!important;margin:0!important;transform:translateY(-50%)!important;display:grid!important;place-items:center!important;padding:0!important;font-size:31px!important}
    .m38gallery-lightbox .m38prev{left:22px!important;right:auto!important;grid-column:auto!important;grid-row:auto!important}
    .m38gallery-lightbox .m38next{right:22px!important;left:auto!important;grid-column:auto!important;grid-row:auto!important}
    .m38gallery-lightbox .m38modal-thumbs{position:absolute!important;left:50%!important;bottom:22px!important;transform:translateX(-50%)!important;width:min(900px,calc(100% - 140px))!important;grid-column:auto!important;grid-row:auto!important}

    /* Mini cart: neutralize plugin floats/legacy product-list rules and keep every row aligned. */
    body.wpbbshop-theme .wpbbshop-mini-cart-panel{width:min(430px,100vw)!important}
    body.wpbbshop-theme .wpbbshop-mini-cart-content,
    body.wpbbshop-theme .wpbbshop-mini-cart-content .widget_shopping_cart_content{width:100%!important;min-width:0!important;box-sizing:border-box!important}
    body.wpbbshop-theme .wpbbshop-mini-cart-content .widget_shopping_cart_content{display:flex!important;flex:1 1 auto!important;min-height:100%!important;flex-direction:column!important}
    body.wpbbshop-theme .llg-mini-cart-list,
    body.wpbbshop-theme .llg-mini-cart-list.product_list_widget{display:block!important;width:100%!important;margin:0!important;padding:0!important;list-style:none!important}
    body.wpbbshop-theme .llg-mini-cart-list .llg-mini-cart-item{
      position:relative!important;display:grid!important;grid-template-columns:80px minmax(0,1fr)!important;gap:13px!important;align-items:center!important;
      width:100%!important;min-width:0!important;margin:0!important;padding:17px 34px 17px 0!important;border-bottom:1px solid #edf1f3!important;box-sizing:border-box!important;clear:both!important
    }
    body.wpbbshop-theme .llg-mini-cart-list .llg-mini-cart-item:before,
    body.wpbbshop-theme .llg-mini-cart-list .llg-mini-cart-item:after{display:none!important;content:none!important}
    body.wpbbshop-theme .llg-mini-cart-list .llg-mini-cart-thumb{float:none!important;display:flex!important;width:80px!important;height:80px!important;margin:0!important;padding:6px!important;box-sizing:border-box!important}
    body.wpbbshop-theme .llg-mini-cart-list .llg-mini-cart-thumb img,
    body.wpbbshop-theme .llg-mini-cart-list.product_list_widget li img{float:none!important;display:block!important;width:100%!important;max-width:100%!important;height:100%!important;margin:0!important;object-fit:contain!important}
    body.wpbbshop-theme .llg-mini-cart-copy{display:flex!important;flex-direction:column!important;align-items:flex-start!important;justify-content:center!important;min-width:0!important;width:100%!important}
    body.wpbbshop-theme .llg-mini-cart-title{width:100%!important;margin:0 0 4px!important;white-space:normal!important;overflow-wrap:anywhere!important}
    body.wpbbshop-theme .llg-mini-cart-sku{margin:0 0 6px!important}
    body.wpbbshop-theme .llg-mini-cart-price{display:flex!important;width:100%!important;align-items:baseline!important;justify-content:flex-start!important;gap:5px!important;margin:0!important}
    body.wpbbshop-theme .llg-mini-cart-remove{position:absolute!important;right:0!important;top:17px!important;float:none!important;margin:0!important}
    body.wpbbshop-theme .llg-mini-cart-summary{width:100%!important;margin-top:auto!important}
    body.wpbbshop-theme .llg-mini-cart-buttons{width:100%!important}
    body.wpbbshop-theme .llg-mini-cart-buttons .button{width:100%!important;box-sizing:border-box!important}

    /* Cart + checkout delivery choices occupy the whole summary width. */
    body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping,
    body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping{
      display:flex!important;flex-direction:column!important;width:100%!important;max-width:none!important;box-sizing:border-box!important
    }
    body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping>th,
    body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping>td,
    body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping>th,
    body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping>td{
      display:block!important;width:100%!important;max-width:none!important;box-sizing:border-box!important;text-align:left!important
    }
    body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping>th,
    body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping>th{padding-bottom:7px!important;border-bottom:0!important}
    body.woocommerce-cart .llg-cart-summary-card tr.woocommerce-shipping-totals.shipping>td,
    body.woocommerce-checkout .woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals.shipping>td{padding-top:0!important}
    body.woocommerce-cart .llg-cart-summary-card ul#shipping_method,
    body.woocommerce-cart .llg-cart-summary-card .woocommerce-shipping-methods,
    body.woocommerce-checkout ul#shipping_method,
    body.woocommerce-checkout .woocommerce-shipping-methods{display:grid!important;grid-template-columns:1fr!important;width:100%!important;max-width:none!important;gap:8px!important;margin:0!important;padding:0!important}
    body.woocommerce-cart .llg-cart-summary-card ul#shipping_method li,
    body.woocommerce-cart .llg-cart-summary-card .woocommerce-shipping-methods li,
    body.woocommerce-checkout ul#shipping_method li,
    body.woocommerce-checkout .woocommerce-shipping-methods li{
      display:grid!important;grid-template-columns:20px minmax(0,1fr)!important;width:100%!important;max-width:none!important;
      margin:0!important;padding:11px 12px!important;box-sizing:border-box!important;border:1px solid #dde7e2!important;border-radius:9px!important;background:#fbfdfb!important
    }
    body.woocommerce-cart .llg-cart-summary-card ul#shipping_method li label,
    body.woocommerce-checkout ul#shipping_method li label{display:block!important;width:100%!important;margin:0!important;white-space:normal!important;line-height:1.4!important}

    @media(max-width:720px){
      body.single-product.wpbbshop-theme .llg-single-container{width:calc(100% - 20px)!important}
      body.single-product.wpbbshop-theme .llg-single-buybox .quantity{grid-template-columns:38px 62px 38px!important;width:138px!important;min-width:138px!important}
      body.single-product.wpbbshop-theme .llg-single-buybox .quantity input.qty{width:62px!important;min-width:62px!important}
      body.single-product.wpbbshop-theme .llg-single-buybox .quantity button,body.single-product.wpbbshop-theme .llg-single-buybox .quantity .minus,body.single-product.wpbbshop-theme .llg-single-buybox .quantity .plus,body.single-product.wpbbshop-theme .llg-single-buybox .quantity .llg-qty-step{width:38px!important;min-width:38px!important}
      .m38gallery-lightbox{padding:68px 14px 100px!important}.m38gallery-lightbox .m38prev{left:10px!important}.m38gallery-lightbox .m38next{right:10px!important}.m38gallery-lightbox .m38close{top:12px!important;right:12px!important}.m38gallery-lightbox .m38modal-thumbs{bottom:14px!important;width:calc(100% - 28px)!important}
      body.wpbbshop-theme .llg-mini-cart-list .llg-mini-cart-item{grid-template-columns:72px minmax(0,1fr)!important;padding-right:32px!important}
      body.wpbbshop-theme .llg-mini-cart-list .llg-mini-cart-thumb{width:72px!important;height:72px!important}
    }
    </style>
    <?php
}
add_action('wp_head', 'wpbbshop_v316_css', PHP_INT_MAX);

/** Add quantity +/- only when no quantity buttons are provided by Woo/plugins. */
function wpbbshop_v316_js() {
    if (is_admin()) { return; }
    ?>
    <script id="wpbbshop-v316-product-cart-polish-js">
    (function(){
      function ready(fn){if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',fn,{once:true});}else{fn();}}
      function enhanceQty(root){
        (root||document).querySelectorAll('body.single-product .llg-single-buybox .quantity').forEach(function(q){
          var input=q.querySelector('input.qty'); if(!input)return;
          var buttons=q.querySelectorAll('button');
          if(buttons.length){
            buttons.forEach(function(b){var t=(b.textContent||'').trim(); if(t==='-'||b.classList.contains('minus'))b.classList.add('llg-qty-minus'); if(t==='+'||b.classList.contains('plus'))b.classList.add('llg-qty-plus');});
            return;
          }
          var minus=document.createElement('button'); minus.type='button'; minus.className='llg-qty-step llg-qty-minus'; minus.setAttribute('aria-label','Samazināt daudzumu'); minus.textContent='-'; minus.dataset.llgQty='minus';
          var plus=document.createElement('button'); plus.type='button'; plus.className='llg-qty-step llg-qty-plus'; plus.setAttribute('aria-label','Palielināt daudzumu'); plus.textContent='+'; plus.dataset.llgQty='plus';
          q.insertBefore(minus,input); q.appendChild(plus);
        });
      }
      function step(btn){
        var q=btn.closest('.quantity'), input=q&&q.querySelector('input.qty'); if(!input)return;
        var step=parseFloat(input.getAttribute('step'))||1, min=input.getAttribute('min')!==null?parseFloat(input.getAttribute('min')):0, max=input.getAttribute('max')!==null&&input.getAttribute('max')!==''?parseFloat(input.getAttribute('max')):Infinity;
        var val=parseFloat(input.value); if(!isFinite(val))val=min||1;
        val += btn.dataset.llgQty==='minus' ? -step : step; val=Math.max(min,Math.min(max,val));
        input.value=String(val); input.dispatchEvent(new Event('change',{bubbles:true}));
      }
      ready(function(){enhanceQty(document);});
      document.addEventListener('click',function(e){var b=e.target.closest&&e.target.closest('[data-llg-qty]'); if(!b)return; e.preventDefault(); step(b);});
      if(window.jQuery){jQuery(document.body).on('updated_wc_div wc_fragments_loaded wc_fragments_refreshed',function(){enhanceQty(document);});}
    })();
    </script>
    <?php
}
add_action('wp_footer', 'wpbbshop_v316_js', PHP_INT_MAX);
