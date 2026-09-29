<?php
/**
 * Product UX fixes requested for IWS WooCommerce pages.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Resolve WooCommerce cart/checkout URLs without falling back to the current page.
 */
function iws_product_ux_get_woo_page_url( $type ) {
	$type     = 'checkout' === $type ? 'checkout' : 'cart';
	$fallback = 'checkout' === $type ? '/checkout/' : '/basket/';

	$valid_url = static function( $url ) {
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( '' === $url ) {
			return '';
		}
		$path = wp_parse_url( $url, PHP_URL_PATH );
		$path = is_string( $path ) ? strtolower( trim( $path, '/' ) ) : '';
		if ( '' === $path ) {
			return '';
		}
		return $url;
	};

	if ( 'checkout' === $type && function_exists( 'wc_get_checkout_url' ) ) {
		$url = $valid_url( wc_get_checkout_url() );
		if ( $url ) {
			return $url;
		}
	}

	if ( 'cart' === $type && function_exists( 'wc_get_cart_url' ) ) {
		$url = $valid_url( wc_get_cart_url() );
		if ( $url ) {
			return $url;
		}
	}

	$page_id = 'checkout' === $type ? (int) get_option( 'woocommerce_checkout_page_id' ) : (int) get_option( 'woocommerce_cart_page_id' );
	if ( $page_id > 0 ) {
		$url = $valid_url( get_permalink( $page_id ) );
		if ( $url ) {
			return $url;
		}
	}

	$slugs = 'checkout' === $type ? array( 'checkout', 'check-out' ) : array( 'basket', 'cart' );
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			$url = $valid_url( get_permalink( $page ) );
			if ( $url ) {
				return $url;
			}
		}
	}

	return home_url( $fallback );
}

/**
 * Keep WooCommerce Block product buttons consistent with archive buttons:
 * product-card "Buy" buttons should open the single product page instead of
 * using the WC Blocks AJAX add-to-cart action.
 */
add_action( 'wp_footer', function() {
	if ( is_admin() ) {
		return;
	}
	$cart_url     = iws_product_ux_get_woo_page_url( 'cart' );
	$checkout_url = iws_product_ux_get_woo_page_url( 'checkout' );
	?>
	<script id="iws-woo-link-data">
		window.iwsWooLinks = Object.assign({}, window.iwsWooLinks || {}, {
			cartUrl: <?php echo wp_json_encode( esc_url_raw( $cart_url ) ); ?>,
			checkoutUrl: <?php echo wp_json_encode( esc_url_raw( $checkout_url ) ); ?>
		});
	</script>
	<script id="iws-woo-native-cart-links">
	(function(){
		if (window.iwsWooNativeCartLinksLoaded) return;
		window.iwsWooNativeCartLinksLoaded = true;
		function normalise(url){ try { return new URL(url || '', window.location.origin).toString(); } catch(e) { return ''; } }
		function wooLink(type){
			var links = window.iwsWooLinks || {};
			var configured = normalise(type === 'checkout' ? links.checkoutUrl : links.cartUrl);
			if (configured && configured !== window.location.origin + '/') return configured;
			return normalise(type === 'checkout' ? '/checkout/' : '/basket/');
		}
		function typeFor(el){
			if (!el || !el.closest) return '';
			if (el.closest('.wc-block-cart__submit-button,.wc-block-cart__submit-container a,.wc-block-cart__submit a')) return 'checkout';
			var footer = el.closest('.wc-block-mini-cart__footer-actions');
			if (!footer) return '';
			var item = el.closest('a,button,[role="button"]') || el;
			var block = (item.getAttribute('data-block-name') || '').toLowerCase();
			var cls = (item.className || '').toString().toLowerCase();
			var txt = (item.textContent || '').toLowerCase();
			if (block.indexOf('checkout') !== -1 || cls.indexOf('checkout') !== -1 || txt.indexOf('checkout') !== -1) return 'checkout';
			if (block.indexOf('cart') !== -1 || cls.indexOf('cart') !== -1 || txt.indexOf('cart') !== -1 || txt.indexOf('basket') !== -1) return 'cart';
			return '';
		}
		var selector = '.wc-block-mini-cart__footer-actions a,.wc-block-mini-cart__footer-actions button,.wc-block-cart__submit-button,.wc-block-cart__submit-container a,.wc-block-cart__submit a';
		function apply(root){
			root = root && root.querySelectorAll ? root : document;
			var nodes = [];
			if (root.matches && root.matches(selector)) nodes.push(root);
			root.querySelectorAll(selector).forEach(function(node){ nodes.push(node); });
			nodes.forEach(function(node){
				var type = typeFor(node);
				if (!type) return;
				var href = wooLink(type);
				var link = node.matches('a') ? node : (node.closest('a') || node.querySelector('a'));
				if (!link || !href) return;
				link.setAttribute('href', href);
				link.removeAttribute('data-wp-on--click');
				link.removeAttribute('data-wp-bind--href');
			});
		}
		['pointerdown','mousedown','touchstart','contextmenu'].forEach(function(name){ document.addEventListener(name, function(e){ if (e.target && e.target.closest) apply(e.target.closest('.wc-block-mini-cart__footer-actions,.wc-block-cart') || document); }, true); });
		document.addEventListener('click', function(e){
			var clicked = e.target && e.target.closest ? e.target.closest(selector) : null;
			if (!clicked) return;
			var type = typeFor(clicked);
			if (!type) return;
			apply(clicked.closest('.wc-block-mini-cart__footer-actions,.wc-block-cart') || document);
			if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
			var href = wooLink(type);
			if (!href) return;
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			window.location.assign(href);
		}, true);
		function boot(){ apply(document); if ('MutationObserver' in window) { new MutationObserver(function(muts){ for (var i=0;i<muts.length;i++){ if (muts[i].addedNodes && muts[i].addedNodes.length) { apply(document); break; } } }).observe(document.body, { childList:true, subtree:true }); } }
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
	})();
	</script>
	<?php if ( ! is_product() && ! is_cart() && ! is_checkout() ) : ?>
	<script id="iws-wc-block-product-button-redirect">
	(function(){
		function closestProductLink(button){
			var product = button.closest('li.product, .product, .wc-block-product, .wc-block-grid__product, .wp-block-post');
			if (!product) return '';
			var link = product.querySelector('a.woocommerce-LoopProduct-link, .wc-block-components-product-image a, .wp-block-post-title a, a[href*="/product/"]');
			return link && link.href ? link.href : '';
		}
		function normaliseButton(button){
			if (!button || button.dataset.iwsRedirectReady === '1') return;
			if (!button.matches('.wc-block-components-product-button__button, .wp-block-button__link.add_to_cart_button, .add_to_cart_button.ajax_add_to_cart')) return;
			var url = button.getAttribute('href') || closestProductLink(button);
			if (!url || /[?&]add-to-cart=/.test(url)) return;
			button.dataset.iwsProductUrl = url;
			button.dataset.iwsRedirectReady = '1';
			button.classList.remove('ajax_add_to_cart');
			button.removeAttribute('data-wp-on--click');
			button.setAttribute('aria-label', button.getAttribute('aria-label') || 'View product');
		}
		function init(){
			document.querySelectorAll('.wc-block-components-product-button__button, .wp-block-button__link.add_to_cart_button, .add_to_cart_button.ajax_add_to_cart').forEach(normaliseButton);
		}
		document.addEventListener('click', function(e){
			var button = e.target.closest('.wc-block-components-product-button__button, .wp-block-button__link.add_to_cart_button, .add_to_cart_button.ajax_add_to_cart');
			if (!button) return;
			normaliseButton(button);
			if (!button.dataset.iwsProductUrl) return;
			e.preventDefault();
			e.stopImmediatePropagation();
			window.location.href = button.dataset.iwsProductUrl;
		}, true);
		document.addEventListener('DOMContentLoaded', init);
		init();
		if ('MutationObserver' in window) {
			new MutationObserver(init).observe(document.documentElement, { childList: true, subtree: true });
		}
	})();
	</script>
	<?php endif; ?>
	<?php
}, 80 );

/**
 * Stock visibility beside the buying controls.
 */
function iws_product_stock_visibility_html( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$stock_quantity = $product->get_stock_quantity();
	$is_in_stock    = $product->is_in_stock();
	$class          = $is_in_stock ? 'in-stock' : 'out-of-stock';
	$text           = $is_in_stock ? __( 'In stock', 'wp-theme-woo-support' ) : __( 'Out of stock', 'wp-theme-woo-support' );

	if ( $is_in_stock && $product->managing_stock() && null !== $stock_quantity && absint( $stock_quantity ) > 0 ) {
		$text = sprintf(
			/* translators: 1: availability text, 2: stock quantity */
			__( '%1$s (%2$s)', 'wp-theme-woo-support' ),
			$text,
			number_format_i18n( absint( $stock_quantity ) )
		);
	}

	return '<div class="woocommerce-variation-availability" role="status" aria-live="polite"><p class="stock ' . esc_attr( $class ) . '">' . esc_html( $text ) . '</p></div>';
}

add_filter( 'woocommerce_get_stock_html', function( $html, $product ) {
	if ( is_admin() || ! is_product() || ! $product instanceof WC_Product || $product->is_type( 'variable' ) ) {
		return $html;
	}

	// Single/simple products use the themed .woocommerce-variation-availability output below.
	// Suppress WooCommerce's native <span class="stock"> output to avoid duplicate stock text.
	return '';
}, 20, 2 );

add_action( 'woocommerce_before_add_to_cart_button', function() {
	global $product;

	if ( ! $product instanceof WC_Product || $product->is_type( 'variable' ) ) {
		return;
	}

	echo wp_kses_post( iws_product_stock_visibility_html( $product ) );
}, 6 );

add_action( 'wp_footer', function() {
	if ( ! is_product() ) {
		return;
	}
	?>
	<script id="iws-clear-initial-variation-selection">
	(function($){
		function clearInitialSelection(context) {
			(context || document).querySelectorAll('form.variations_form').forEach(function(form){
				if (form.dataset.iwsInitialVariationCleared === 'yes') return;
				form.dataset.iwsInitialVariationCleared = 'yes';
				form.querySelectorAll('select[name^="attribute_"]').forEach(function(select){
					if (!select.querySelector('option[value=""]')) {
						var empty = document.createElement('option');
						empty.value = '';
						empty.textContent = '';
						select.insertBefore(empty, select.firstChild);
					}
					select.value = '';
					select.selectedIndex = 0;
					select.dispatchEvent(new Event('change', { bubbles: true }));
					if ($) {
						$(select).val('').trigger('change');
					}
				});
				form.querySelectorAll('.iws-swatch.selected').forEach(function(button){
					button.classList.remove('selected');
				});
				if ($) {
					$(form).trigger('reset_data');
				}
			});
		}
		document.addEventListener('DOMContentLoaded', function(){ clearInitialSelection(document); });
		clearInitialSelection(document);
	})(window.jQuery);
	</script>
	<?php
}, 88 );

add_action( 'wp_footer', function() {
	if ( ! is_product() ) {
		return;
	}
	?>
	<script id="iws-single-product-stock-variation-sync">
	(function($){
		if (!$) return;
		function formatMoney(amount) {
			var currency = (window.wc_add_to_cart_variation_params && window.wc_add_to_cart_variation_params.currency_format_symbol) || '£';
			var decimals = (window.wc_add_to_cart_variation_params && parseInt(window.wc_add_to_cart_variation_params.currency_format_num_decimals, 10));
			if (!isFinite(decimals)) decimals = 2;
			return currency + parseFloat(amount || 0).toFixed(decimals);
		}
		function updateTopVariationPrice(variation) {
			var $total = $('#product_total_price.product-total-price').first();
			if (!$total.length || !variation) return;
			var price = parseFloat(variation.display_price || variation.display_regular_price || 0);
			if (!isFinite(price) || price <= 0) return;
			$total.prop('hidden', false).show();
			$total.find('.product-total-price_caption').text(formatMoney(price) + ' each total:');
			$total.find('.product-total-price_sum').attr('data-price', price).text(formatMoney(price));
		}
		function formatStockText(variation) {
			var qty = parseInt(variation && variation.max_qty, 10);
			if (!variation || !variation.is_in_stock || (isFinite(qty) && qty <= 0)) {
				return 'Out of stock';
			}
			var text = $('<div>').html(variation.availability_html || '').text().trim() || 'In stock';
			text = text.replace(/\s*\([\d,.]+\)\s*$/, '');
			if (isFinite(qty) && qty > 0) {
				text += ' (' + qty.toLocaleString() + ')';
			}
			return text;
		}
		function updateAvailability(form, variation) {
			var $availability = $(form).find('.woocommerce-variation-availability').first();
			if (!$availability.length || !variation) return;
			var qty = parseInt(variation.max_qty, 10);
			var inStock = !!variation.is_in_stock && (!isFinite(qty) || qty > 0);
			var stockClass = inStock ? 'in-stock' : 'out-of-stock';
			$availability.html('<p class="stock ' + stockClass + '"></p>');
			$availability.find('.stock').text(formatStockText(variation));
		}
		$('form.variations_form')
			.on('found_variation show_variation', function(event, variation){
				var form = this;
				window.setTimeout(function(){ updateAvailability(form, variation); updateTopVariationPrice(variation); }, 0);
			})
			.on('hide_variation reset_data', function(){
				$(this).find('.woocommerce-variation-availability').empty();
			});
	})(window.jQuery);
	</script>
	<?php
}, 90 );

/**
 * Prevent impossible variation combinations when custom swatches are active.
 */
add_action( 'wp_footer', function() {
	if ( ! is_product() ) {
		return;
	}
	?>
	<script id="iws-variation-swatch-validity-guard">
	(function($){
		function refreshSwatches(context){
			(context || document).querySelectorAll('.iws-variation-swatches').forEach(function(wrap){
				var cell = wrap.closest('td') || wrap.parentNode;
				var select = cell ? cell.querySelector('select[name="' + wrap.dataset.attribute_name + '"]') : null;
				if (!select) return;
				wrap.querySelectorAll('.iws-swatch').forEach(function(button){
					var option = Array.prototype.slice.call(select.options).find(function(opt){ return opt.value === button.dataset.value; });
					var disabled = !option || option.disabled;
					button.classList.toggle('is-disabled', disabled);
					button.disabled = disabled;
					button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
					if (disabled && button.classList.contains('selected')) {
						button.classList.remove('selected');
					}
				});
			});
		}
		document.addEventListener('click', function(event){
			var button = event.target.closest('.iws-swatch');
			if (!button || (!button.disabled && button.getAttribute('aria-disabled') !== 'true')) return;
			event.preventDefault();
			event.stopImmediatePropagation();
		}, true);
		document.addEventListener('DOMContentLoaded', function(){ refreshSwatches(document); });
		refreshSwatches(document);
		if ($) {
			$(document.body).on('woocommerce_update_variation_values found_variation hide_variation reset_data', function(event){
				refreshSwatches(event.target || document);
			});
		}
		if ('MutationObserver' in window) {
			new MutationObserver(function(){ refreshSwatches(document); }).observe(document.documentElement, { childList: true, subtree: true });
		}
	})(window.jQuery);
	</script>
	<?php
}, 95 );

/**
 * Runtime CSS fallback. The same rules are also added to SCSS for the Vite build.
 */
add_action( 'wp_head', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="iws-product-ux-fixes-css">
		.iws-products-grid li.product a.woocommerce-LoopProduct-link br{display:none!important}
		.wp-block-woocommerce-empty-mini-cart-contents-block .wc-block-mini-cart__empty-cart-wrapper p{margin-bottom:15px!important}
		.iws-swatch.is-disabled,.iws-swatch[aria-disabled="true"]{opacity:.35!important;cursor:not-allowed!important;filter:grayscale(1);pointer-events:auto!important}
		.iws-buy-stock-visibility{position:static!important;right:auto!important;display:flex!important;align-items:center;gap:6px;width:100%;max-width:100%;margin:0 0 10px!important;padding:0!important;border:0!important;border-radius:0!important;background:transparent!important;font-size:14px;line-height:1.25;clear:both;box-sizing:border-box}
		.iws-buy-stock-visibility:before{content:none!important;display:none!important}

		body.single-product .woocommerce-variation-price{display:none!important}
		body.single-product.product-type-variable .summary>.price{display:none!important}
		body.single-product .cart>.stock,body.single-product form.cart>.stock,body.single-product .summary>.stock{display:none!important}
		body.single-product .woocommerce-variation-availability{display:block!important;width:100%;max-width:100%;margin:0 0 10px!important;padding:0!important;clear:both}
		body.single-product .woocommerce-variation-availability .stock{display:flex!important;align-items:center!important;gap:7px!important;margin:0!important;padding:0!important;border:0!important;background:transparent!important;font-size:14px!important;line-height:1.25!important;color:#111827!important}
		body.single-product .woocommerce-variation-availability .stock:before{content:""!important;display:inline-block!important;width:10px!important;height:10px!important;min-width:10px!important;border-radius:999px!important;background:#16a34a!important;box-shadow:0 0 0 3px rgba(22,163,74,.14)!important}
		body.single-product .woocommerce-variation-availability .stock.out-of-stock:before{background:#dc2626!important;box-shadow:0 0 0 3px rgba(220,38,38,.14)!important}
		body.single-product .woocommerce-variation-availability .stock.available-on-backorder:before{background:#e6a910!important;box-shadow:0 0 0 3px rgba(230,169,16,.16)!important}
		.iws-product-key-specs,.iws-product-usps{display:none!important}
		body.single-product .related.products .product{display:flex;flex-direction:column;position:relative;padding-bottom:20px}body.single-product .related.products .product .iws-compare-toggle{display:none!important}body.single-product .related.products .product .price{position:relative;margin-top:auto;margin-bottom:10px}body.single-product .related.products .product .button,body.single-product .related.products .product .add_to_cart_button,body.single-product .related.products .product .product_type_simple,body.single-product .related.products .product .tfa-quote-button,body.single-product .related.products .product .wp-block-button__link{position:static;right:auto;bottom:auto;left:auto;width:100%;max-width:100%;min-height:40px;margin:0;text-align:center;box-sizing:border-box}body.single-product .related.products .product .wp-block-button{position:static;width:100%}
		.tfa-quote-button{background-color:var(--tfa-brand-color)!important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:calc(100% + 32px) center!important;background-size:16px auto!important;border-color:var(--tfa-brand-color)!important;color:#fff!important;font-weight:500!important;transition:background-position .25s ease,background-color .2s ease,border-color .2s ease,color .2s ease!important}.tfa-quote-button:hover,.tfa-quote-button:focus{background-color:var(--tfa-brand-color)!important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-position:calc(100% - 18px) center!important;color:#fff!important}.tfa-quote-button svg,.tfa-quote-button svg path{color:#fff!important;fill:none!important;stroke:currentColor!important}.iws-compare-open,.iws-compare-open--search,.iws-compare-toggle{background:#fff!important;border-color:var(--tfa-brand-color)!important;color:#111!important}.iws-compare-open svg,.iws-compare-open svg path,.iws-compare-open--search svg,.iws-compare-open--search svg path,.iws-compare-toggle svg,.iws-compare-toggle svg path{color:#111!important;fill:none!important;stroke:currentColor!important}.iws-compare-open.has-products,.iws-compare-open.is-selected,.iws-compare-open.is-active,.iws-compare-open.active,.iws-compare-open[aria-pressed="true"],.iws-compare-toggle.is-selected,.iws-compare-toggle.is-active,.iws-compare-toggle.active,.iws-compare-toggle[aria-pressed="true"]{background:var(--tfa-brand-color)!important;border-color:var(--tfa-brand-color)!important;color:#fff!important}.iws-compare-open.has-products svg,.iws-compare-open.has-products svg path,.iws-compare-open.is-selected svg,.iws-compare-open.is-selected svg path,.iws-compare-open.is-active svg,.iws-compare-open.is-active svg path,.iws-compare-open.active svg,.iws-compare-open.active svg path,.iws-compare-open[aria-pressed="true"] svg,.iws-compare-open[aria-pressed="true"] svg path,.iws-compare-toggle.is-selected svg,.iws-compare-toggle.is-selected svg path,.iws-compare-toggle.is-active svg,.iws-compare-toggle.is-active svg path,.iws-compare-toggle.active svg,.iws-compare-toggle.active svg path,.iws-compare-toggle[aria-pressed="true"] svg,.iws-compare-toggle[aria-pressed="true"] svg path{color:#fff!important;fill:none!important;stroke:currentColor!important}
		@media(max-width:767.98px){.woocommerce div.product .related.products{margin-top:10px}.woocommerce div.product .related.products ul.products{max-height:none!important;grid-template-columns:auto!important;gap:12px!important}.woocommerce div.product .related.products .product{padding:12px 12px 20px!important}.woocommerce div.product .related.products li.product img{max-height:150px!important}}

		/* iws-product-meta-alignment */
		/* Keep Woo single-product flex children in the intended visual order.
		 * Without explicit orders for tabs/related, they keep the default order:0 and appear above the gallery. */
		body.single-product div.product > .woocommerce-product-gallery,body.single-product div.product > .images,body.single-product div.product > .iws-product-gallery-slider{order:1}
		body.single-product div.product > .summary,body.single-product div.product > .entry-summary{order:2}
		body.single-product div.product > .woo-prod-delivery-info{order:3;flex:0 0 100%!important;width:100%!important;max-width:100%!important;padding-right:0;box-sizing:border-box;clear:both}
		body.single-product div.product > .woocommerce-tabs,body.single-product div.product > .wc-tabs-wrapper{order:4;flex:0 0 100%!important;width:100%!important;max-width:100%!important;clear:both}
		body.single-product div.product > .up-sells.upsells.products,body.single-product div.product > section.up-sells.upsells.products,body.single-product div.product > .upsells.products,body.single-product div.product > section.upsells.products{order:5;flex:0 0 100%!important;width:100%!important;max-width:100%!important;clear:both}
		body.single-product div.product > .related.products,body.single-product div.product > section.related.products{order:6;flex:0 0 100%!important;width:100%!important;max-width:100%!important;clear:both}
		body.single-product .product_meta{display:block!important;width:100%;margin:20px 0!important}
		body.single-product .product_meta .iws-meta-row,body.single-product .product_meta .sku_wrapper,body.single-product .product_meta .posted_in,body.single-product .product_meta .tagged_as{display:grid!important;grid-template-columns:150px minmax(0,1fr);column-gap:12px;align-items:start;width:100%!important;margin:0!important;padding:14px 0!important;border-bottom:1px solid rgba(0,0,0,.12);line-height:1.5}
		body.single-product .product_meta br{display:none!important}
		body.single-product .product_meta .iws-meta-label,body.single-product .product_meta b{position:static;display:block;width:auto;margin:0;font-weight:700!important}
		body.single-product .product_meta .iws-meta-value{display:block!important;min-width:0;white-space:normal!important;word-break:break-word}
		body.single-product .product_meta .iws-meta-value p{display:inline;margin:0}
		body.single-product .product_meta a,body.single-product .product_meta .sku{white-space:normal!important;word-break:break-word}
		body.single-product .product_meta table.woocommerce-product-attributes{display:block;width:100%;margin:0!important;border-collapse:collapse}
		body.single-product .product_meta table.woocommerce-product-attributes tbody{display:block;width:100%}
		body.single-product .product_meta table.woocommerce-product-attributes tr{display:grid!important;grid-template-columns:150px minmax(0,1fr);column-gap:12px;align-items:start;width:100%;border-bottom:1px solid rgba(0,0,0,.12)}
		body.single-product .product_meta table.woocommerce-product-attributes th,body.single-product .product_meta table.woocommerce-product-attributes td{display:block!important;width:auto!important;padding:14px 0!important;border:0!important;text-align:left;vertical-align:top}
		body.single-product .product_meta table.woocommerce-product-attributes th{padding-right:0!important;font-weight:700!important}
		body.single-product .product_meta table.woocommerce-product-attributes td{min-width:0}
		body.single-product .product_meta table.woocommerce-product-attributes td p{margin:0}
		@media(max-width:1199.98px){body.single-product div.product > .woo-prod-delivery-info{width:100%!important;max-width:100%!important;flex-basis:100%!important;padding-right:0}}
		@media(max-width:575.98px){body.single-product .product_meta .iws-meta-row,body.single-product .product_meta .sku_wrapper,body.single-product .product_meta .posted_in,body.single-product .product_meta .tagged_as,body.single-product .product_meta table.woocommerce-product-attributes tr{grid-template-columns:1fr;gap:4px 0}}
		.wc-block-product-template .product,.products .product,body.archive.woocommerce .products .product{padding-bottom:20px}
		body.single-product .related.products .price{position:relative}
		.wp-block-wp-swiper-slides.wp-bblocks-swiper .swiper-button-next,.wp-swiper .swiper-button-next,.wp_swiper__navigation .swiper-button-next{top:50%;transform:translateY(-50%);margin-top:0}

		.wc-block-product-template .product:not(:has(.price:not(.tfa-wcqb-hidden-price),.wp-block-woocommerce-product-price:not(.tfa-wcqb-hidden-price),.wc-block-components-product-price:not(.tfa-wcqb-hidden-price))) .tfa-wcqb-loop-wrap:first-of-type,.products .product:not(:has(.price:not(.tfa-wcqb-hidden-price))) .tfa-wcqb-loop-wrap:first-of-type{margin-top:auto}

		/* iws-card-button-layout-v24 */
		.wc-block-product-template .product,.products .product,body.archive.woocommerce .products .product,.woocommerce ul.products li.product,.iws-products-grid li.product,.tfa-mega-menu .product,.megamenu-modal .product{padding-bottom:20px!important;display:flex!important;flex-direction:column!important;align-items:stretch!important;position:relative!important;overflow:hidden!important;box-sizing:border-box!important}
		.wc-block-product-template .product>.woocommerce-LoopProduct-link,.products .product>.woocommerce-LoopProduct-link,.woocommerce ul.products li.product>.woocommerce-LoopProduct-link,.iws-products-grid li.product>.woocommerce-LoopProduct-link{display:flex!important;flex:1 1 auto!important;flex-direction:column!important;min-height:0!important;color:inherit!important;text-decoration:none!important}
		.wc-block-product-template .product .price,.wc-block-product-template .product .wc-block-components-product-price,.products .product .price,.woocommerce ul.products li.product .price,.iws-products-grid li.product .price,.tfa-mega-menu .product .price,.megamenu-modal .product .price{position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;margin:auto!important;padding:0!important;float:none!important;clear:both!important;transform:none!important;text-align:left!important}
		.wc-block-product-template .product .wp-block-button,.wc-block-product-template .product .wc-block-components-product-button,.products .product .wp-block-button,.products .product .wc-block-components-product-button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable,.products .product .tfa-wcqb-loop-wrap,.woocommerce ul.products li.product .tfa-wcqb-loop-wrap{position:static!important;inset:auto!important;display:block!important;width:100%!important;max-width:100%!important;margin:15px auto 0 auto!important;padding:0!important;float:none!important;clear:both!important;transform:none!important}
		.products .product .tfa-wcqb-loop-wrap,.woocommerce ul.products li.product .tfa-wcqb-loop-wrap,.wc-block-product-template .product .tfa-wcqb-loop-wrap{margin-top:8px!important}.wc-block-product-template .product .wp-block-button.wc-block-components-product-button+.tfa-wcqb-loop-wrap,.wc-block-product-template .product .wc-block-components-product-button+.tfa-wcqb-loop-wrap,.wc-block-product-template .product [data-block-name="woocommerce/product-button"]+.tfa-wcqb-loop-wrap,.products .product .wp-block-button.wc-block-components-product-button+.tfa-wcqb-loop-wrap,.products .product .wc-block-components-product-button+.tfa-wcqb-loop-wrap,.products .product [data-block-name="woocommerce/product-button"]+.tfa-wcqb-loop-wrap{margin-top:20px!important}
		.wc-block-product-template .product .wp-element-button,.wc-block-product-template .product .wp-block-button__link,.wc-block-product-template .product .wc-block-components-product-button__button,.products .product .wp-element-button,.products .product .wp-block-button__link,.products .product .wc-block-components-product-button__button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable,.products .product .tfa-wcqb-loop-wrap .tfa-quote-button,.tfa-mega-menu .product .tfa-wcqb-loop-wrap .tfa-quote-button,.megamenu-modal .product .tfa-wcqb-loop-wrap .tfa-quote-button{display:flex!important;align-items:center!important;justify-content:center!important;position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;min-height:40px!important;margin:0!important;padding:0 15px 0 15px!important;border:0!important;border-radius:0 15px 0 0!important;font-weight:500!important;line-height:1.15!important;text-align:center!important;text-decoration:none!important;white-space:normal!important;transform:none!important;box-sizing:border-box!important;overflow:hidden!important}
		.wc-block-product-template .product .wp-element-button,.wc-block-product-template .product .wp-block-button__link,.wc-block-product-template .product .wc-block-components-product-button__button,.products .product .wp-element-button,.products .product .wp-block-button__link,.products .product .wc-block-components-product-button__button,.products .product>a.button:not(.iws-compare-toggle),.products .product>.button:not(.iws-compare-toggle),.products .product>.add_to_cart_button,.products .product>.product_type_simple,.products .product>.product_type_variable{background-color:var(--tfa-green-color)!important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7 7h14l-2 8H8L6 3H3' fill='none' stroke='%23fff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='10' cy='20' r='1.6' fill='%23fff'/%3E%3Ccircle cx='18' cy='20' r='1.6' fill='%23fff'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:calc(100% + 32px) center!important;background-size:18px auto!important;color:#fff!important}
		.wc-block-product-template .product .wp-element-button:hover,.wc-block-product-template .product .wp-block-button__link:hover,.wc-block-product-template .product .wc-block-components-product-button__button:hover,.products .product .wp-element-button:hover,.products .product .wp-block-button__link:hover,.products .product .wc-block-components-product-button__button:hover,.products .product>a.button:not(.iws-compare-toggle):hover,.products .product>.button:not(.iws-compare-toggle):hover,.products .product>.add_to_cart_button:hover,.products .product>.product_type_simple:hover,.products .product>.product_type_variable:hover{background-position:calc(100% - 18px) center!important}
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button,.tfa-mega-menu .product .tfa-wcqb-loop-wrap .tfa-quote-button,.megamenu-modal .product .tfa-wcqb-loop-wrap .tfa-quote-button,.tfa-quote-button.single{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:calc(100% + 32px) center!important;background-size:16px auto!important;font-weight:500!important}
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button:hover,.tfa-mega-menu .product .tfa-wcqb-loop-wrap .tfa-quote-button:hover,.megamenu-modal .product .tfa-wcqb-loop-wrap .tfa-quote-button:hover,.tfa-quote-button.single:hover{background-position:calc(100% - 18px) center!important}
		.woocommerce span.onsale,.wc-block-components-product-sale-badge{margin-right:12px!important}

	</style>
	<?php
}, 70 );

add_action( 'wp_footer', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<script id="iws-search-compare-wrap-state">
	(function(){
		function forceCompareOpenButtonState(button){
			var countNode = button.querySelector('.iws-compare-count');
			var count = countNode ? parseInt((countNode.textContent || '0').replace(/\D+/g, ''), 10) || 0 : 0;
			var hasProducts = count > 0 || button.classList.contains('has-products');
			var isOpen = hasProducts || button.classList.contains('is-selected') || button.classList.contains('is-active') || button.classList.contains('active') || button.getAttribute('aria-pressed') === 'true';
			var bg = isOpen ? 'var(--tfa-brand-color)' : '#fff';
			var border = isOpen ? 'var(--tfa-brand-color)' : 'var(--tfa-brand-color)';
			var color = isOpen ? '#fff' : '#111';
			button.style.setProperty('background-color', bg, 'important');
			button.style.setProperty('border-color', border, 'important');
			button.style.setProperty('color', color, 'important');
			button.querySelectorAll('svg, svg path').forEach(function(icon){
				icon.style.setProperty('color', color, 'important');
				icon.style.setProperty('stroke', 'currentColor', 'important');
				icon.style.setProperty('fill', 'none', 'important');
			});
		}
		function sync(){
			document.querySelectorAll('.iws-compare-open, .iws-compare-open--search').forEach(forceCompareOpenButtonState);
		}
		document.addEventListener('DOMContentLoaded', sync);
		document.addEventListener('click', function(){ window.setTimeout(sync, 0); }, true);
		document.addEventListener('iws_compare_updated', sync);
		sync();
		if ('MutationObserver' in window) {
			new MutationObserver(sync).observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'aria-pressed'], childList: true, subtree: true });
		}
	})();
	</script>
	<?php
}, 120 );


/**
 * v25: optional product card and search polish.
 */
add_action( 'wp_footer', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="iws-product-card-polish-v25">
		:root { --iws-card-button-height: 40px; --iws-card-button-gap: 8px; --iws-card-button-font-size: 16px; }
		.wc-block-product-template .product,
		.products .product,
		.woocommerce ul.products li.product,
		.iws-products-grid li.product,
		body.archive.woocommerce .products .product,
		body.single-product .related.products li.product,
		body.single-product .upsells.products li.product,
		body.single-product .cross-sells.products li.product,
		body.single-product .wp-block-woocommerce-product-template .wp-block-post.product,
		body.single-product .wc-block-product-template .product,
		body.single-product .wc-block-product {
			display: flex;
			flex-direction: column;
			align-items: stretch;
			box-sizing: border-box;
			padding-bottom: 20px;
			position: relative;
		}
		.wc-block-product-template .product > .woocommerce-LoopProduct-link,
		.products .product > .woocommerce-LoopProduct-link,
		.woocommerce ul.products li.product > .woocommerce-LoopProduct-link,
		.iws-products-grid li.product > .woocommerce-LoopProduct-link,
		body.single-product .related.products li.product > .woocommerce-LoopProduct-link,
		body.single-product .upsells.products li.product > .woocommerce-LoopProduct-link,
		body.single-product .cross-sells.products li.product > .woocommerce-LoopProduct-link {
			display: flex;
			flex: 1 1 auto;
			flex-direction: column;
			min-height: 0;
			color: inherit;
			text-decoration: none;
		}
		.iws-products-grid li.product img {
			object-fit: cover !important;		
			height: 270px;
			object-position: center;			
			margin-right: 0;
			margin-left: 0;
			display: block;
			width: 100%;
			
		}
		.wc-block-product-template .product .price,
		.wc-block-product-template .product .wc-block-components-product-price,
		.products .product .price,
		.woocommerce ul.products li.product .price,
		.iws-products-grid li.product .price,
		body.archive.woocommerce .products .product .price,
		body.single-product .related.products .price,
		body.single-product .upsells.products .price,
		body.single-product .cross-sells.products .price,
		body.single-product .wc-block-product-template .product .price,
		body.single-product .wc-block-product-template .product .wc-block-components-product-price {
			position: relative;
			inset: auto;
			width: 100%;
			max-width: 100%;
			margin: auto 0 10px;
			padding: 0;
			float: none;
			clear: both;
			transform: none;
			text-align: left;
		}
		.woocommerce ul.products li.product .price:before,
		.iws-products-grid li.product .price:before,
		body.single-product .related.products .price:before,
		body.single-product .upsells.products .price:before,
		body.single-product .cross-sells.products .price:before {
			left: 0;
			text-align: left;
		}
		.wc-block-product-template .product .wp-block-button,
		.wc-block-product-template .product .wc-block-components-product-button,
		.products .product .wp-block-button,
		.products .product .wc-block-components-product-button,
		.products .product > a.button:not(.iws-compare-toggle),
		.products .product > .button:not(.iws-compare-toggle),
		.products .product > .add_to_cart_button,
		.products .product > .product_type_simple,
		.products .product > .product_type_variable,
		.products .product .tfa-wcqb-loop-wrap,
		.woocommerce ul.products li.product .tfa-wcqb-loop-wrap,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap {
			position: static;
			inset: auto;
			display: block;
			width: 100%;
			max-width: 100%;
			margin: 0;
			padding: 0;
			float: none;
			clear: both;
			transform: none;
			box-sizing: border-box;
		}
		.products .product .tfa-wcqb-loop-wrap,
		.woocommerce ul.products li.product .tfa-wcqb-loop-wrap,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap,
		.wc-block-product-template .product .tfa-wcqb-loop-wrap,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap {
			margin-top: var(--iws-card-button-gap);
		}
		.wc-block-product-template .product .wp-block-button.wc-block-components-product-button + .tfa-wcqb-loop-wrap,
		.wc-block-product-template .product .wc-block-components-product-button + .tfa-wcqb-loop-wrap,
		.wc-block-product-template .product [data-block-name="woocommerce/product-button"] + .tfa-wcqb-loop-wrap,
		.products .product .wp-block-button.wc-block-components-product-button + .tfa-wcqb-loop-wrap,
		.products .product .wc-block-components-product-button + .tfa-wcqb-loop-wrap,
		.products .product [data-block-name="woocommerce/product-button"] + .tfa-wcqb-loop-wrap,
		.iws-products-grid li.product .wp-block-button.wc-block-components-product-button + .tfa-wcqb-loop-wrap,
		.iws-products-grid li.product .wc-block-components-product-button + .tfa-wcqb-loop-wrap,
		.iws-products-grid li.product [data-block-name="woocommerce/product-button"] + .tfa-wcqb-loop-wrap {
			margin-top: 20px;
		}
		.wc-block-product-template .product .wp-element-button,
		.wc-block-product-template .product .wp-block-button__link,
		.wc-block-product-template .product .wc-block-components-product-button__button,
		.products .product .wp-element-button,
		.products .product .wp-block-button__link,
		.products .product .wc-block-components-product-button__button,
		.products .product > a.button:not(.iws-compare-toggle),
		.products .product > .button:not(.iws-compare-toggle),
		.products .product > .add_to_cart_button,
		.products .product > .product_type_simple,
		.products .product > .product_type_variable,
		.iws-products-grid li.product .button:not(.iws-compare-toggle),
		.iws-products-grid li.product .add_to_cart_button,
		.iws-products-grid li.product .product_type_simple,
		.iws-products-grid li.product .product_type_variable,
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .related.products li.product .button:not(.iws-compare-toggle),
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.tfa-quote-button.single {
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			inset: auto;
			width: 100%;
			max-width: 100%;
			min-height: var(--iws-card-button-height);
			margin: 0;
			padding: 0 15px 0 15px;
			border: 0;
			border-radius: 0 15px 0 0;
			font-family: inherit;
			font-size: var(--iws-card-button-font-size, 16px);
			font-weight: 500;
			line-height: 1.2;
			text-align: center;
			text-decoration: none;
			white-space: normal;
			transform: none;
			box-sizing: border-box;
			overflow: hidden;
		}
		.wc-block-product-template .product .wp-element-button,
		.wc-block-product-template .product .wp-block-button__link,
		.wc-block-product-template .product .wc-block-components-product-button__button,
		.products .product .wp-element-button,
		.products .product .wp-block-button__link,
		.products .product .wc-block-components-product-button__button,
		.products .product > a.button:not(.iws-compare-toggle),
		.products .product > .button:not(.iws-compare-toggle),
		.products .product > .add_to_cart_button,
		.products .product > .product_type_simple,
		.products .product > .product_type_variable,
		.iws-products-grid li.product .button:not(.iws-compare-toggle),
		.iws-products-grid li.product .add_to_cart_button,
		.iws-products-grid li.product .product_type_simple,
		.iws-products-grid li.product .product_type_variable,
		body.single-product .related.products li.product .button:not(.iws-compare-toggle),
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle) {
			background-color: var(--tfa-green-color, #32c523);
			color: #fff;
		}
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.tfa-quote-button.single {
			background-color: var(--tfa-brand-color);
			color: #fff;
		}
		.wc-block-product-template .product .wp-element-button:after,
		.wc-block-product-template .product .wp-block-button__link:after,
		.wc-block-product-template .product .wc-block-components-product-button__button:after,
		.products .product .wp-element-button:after,
		.products .product .wp-block-button__link:after,
		.products .product .wc-block-components-product-button__button:after,
		.products .product > a.button:not(.iws-compare-toggle):after,
		.products .product > .button:not(.iws-compare-toggle):after,
		.products .product > .add_to_cart_button:after,
		.products .product > .product_type_simple:after,
		.products .product > .product_type_variable:after,
		.iws-products-grid li.product .button:not(.iws-compare-toggle):after,
		.iws-products-grid li.product .add_to_cart_button:after,
		.iws-products-grid li.product .product_type_simple:after,
		.iws-products-grid li.product .product_type_variable:after,
		body.single-product .related.products li.product .button:not(.iws-compare-toggle):after,
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle):after,
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle):after,
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		.tfa-quote-button.single:after {
			content: '';
			position: absolute;
			top: 50%;
			right: 18px;
			width: 18px;
			height: 18px;
			background-repeat: no-repeat;
			background-position: center;
			background-size: contain;
			opacity: 0;
			transform: translate(16px, -50%);
			transition: opacity .2s ease, transform .2s ease;
			pointer-events: none;
		}
		.wc-block-product-template .product .wp-element-button:after,
		.wc-block-product-template .product .wp-block-button__link:after,
		.wc-block-product-template .product .wc-block-components-product-button__button:after,
		.products .product .wp-element-button:after,
		.products .product .wp-block-button__link:after,
		.products .product .wc-block-components-product-button__button:after,
		.products .product > a.button:not(.iws-compare-toggle):after,
		.products .product > .button:not(.iws-compare-toggle):after,
		.products .product > .add_to_cart_button:after,
		.products .product > .product_type_simple:after,
		.products .product > .product_type_variable:after,
		.iws-products-grid li.product .button:not(.iws-compare-toggle):after,
		.iws-products-grid li.product .add_to_cart_button:after,
		.iws-products-grid li.product .product_type_simple:after,
		.iws-products-grid li.product .product_type_variable:after,
		body.single-product .related.products li.product .button:not(.iws-compare-toggle):after,
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle):after,
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle):after {
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7 7h14l-2 8H8L6 3H3' fill='none' stroke='%23fff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='10' cy='20' r='1.6' fill='%23fff'/%3E%3Ccircle cx='18' cy='20' r='1.6' fill='%23fff'/%3E%3C/svg%3E");
		}
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:after,
		.tfa-quote-button.single:after {
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
			width: 16px;
			height: 16px;
		}
		.wc-block-product-template .product .wp-element-button:hover:after,
		.wc-block-product-template .product .wp-block-button__link:hover:after,
		.wc-block-product-template .product .wc-block-components-product-button__button:hover:after,
		.products .product .wp-element-button:hover:after,
		.products .product .wp-block-button__link:hover:after,
		.products .product .wc-block-components-product-button__button:hover:after,
		.products .product > a.button:not(.iws-compare-toggle):hover:after,
		.products .product > .button:not(.iws-compare-toggle):hover:after,
		.products .product > .add_to_cart_button:hover:after,
		.products .product > .product_type_simple:hover:after,
		.products .product > .product_type_variable:hover:after,
		.iws-products-grid li.product .button:not(.iws-compare-toggle):hover:after,
		.iws-products-grid li.product .add_to_cart_button:hover:after,
		.iws-products-grid li.product .product_type_simple:hover:after,
		.iws-products-grid li.product .product_type_variable:hover:after,
		body.single-product .related.products li.product .button:not(.iws-compare-toggle):hover:after,
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle):hover:after,
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle):hover:after,
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button:hover:after,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:hover:after,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:hover:after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:hover:after,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button:hover:after,
		.tfa-quote-button.single:hover:after {
			opacity: 1;
			transform: translate(0, -50%);
		}
		.iws-search-highlight {
			background: rgba(246, 196, 0, .24);
			border-radius: 3px;
			box-shadow: 0 0 0 2px rgba(246, 196, 0, .12);
		}
		.iws-search-hidden {
			display: none !important;
		}
	</style>
	<script id="iws-product-search-highlight-v25">
	(function(){
		function escapeRegExp(value){ return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
		function stripMarks(root){
			root.querySelectorAll('mark.iws-search-highlight').forEach(function(mark){
				var text = document.createTextNode(mark.textContent || '');
				mark.parentNode.replaceChild(text, mark);
			});
		}
		function highlightNode(node, query){
			if (!node || !query) return;
			var walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, {
				acceptNode: function(textNode){
					if (!textNode.nodeValue || !textNode.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
					if (textNode.parentElement && textNode.parentElement.closest('script,style,mark,a.button,button,.tfa-wcqb-loop-wrap,.wp-block-button,.wc-block-components-product-button')) return NodeFilter.FILTER_REJECT;
					return textNode.nodeValue.toLowerCase().indexOf(query.toLowerCase()) !== -1 ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
				}
			});
			var nodes = [];
			while (walker.nextNode()) nodes.push(walker.currentNode);
			var regex = new RegExp('(' + escapeRegExp(query) + ')', 'ig');
			nodes.forEach(function(textNode){
				var frag = document.createDocumentFragment();
				var parts = textNode.nodeValue.split(regex);
				parts.forEach(function(part){
					if (!part) return;
					if (part.toLowerCase() === query.toLowerCase()) {
						var mark = document.createElement('mark');
						mark.className = 'iws-search-highlight';
						mark.textContent = part;
						frag.appendChild(mark);
					} else {
						frag.appendChild(document.createTextNode(part));
					}
				});
				textNode.parentNode.replaceChild(frag, textNode);
			});
		}
		function getCards(){
			return Array.prototype.slice.call(document.querySelectorAll('.iws-products-grid li.product, .woocommerce ul.products li.product, .wc-block-product-template .product, .wp-block-woocommerce-product-template .wp-block-post.product'));
		}
		function cardText(card){
			var clone = card.cloneNode(true);
			clone.querySelectorAll('script,style,.iws-compare-toggle,.button,.wp-block-button,.wc-block-components-product-button,.tfa-wcqb-loop-wrap').forEach(function(node){ node.remove(); });
			return (clone.textContent || '').toLowerCase();
		}
		function activeSearchValue(){
			var inputs = document.querySelectorAll('.iws-search-input, .iws-filter-search input[type="search"], .iws-filter-wrapper input[type="search"], .woocommerce-product-search input[type="search"]');
			for (var i = 0; i < inputs.length; i++) {
				if (inputs[i].value && inputs[i].value.trim()) return inputs[i].value.trim();
			}
			return '';
		}
		function apply(){
			var query = activeSearchValue();
			var cards = getCards();
			cards.forEach(function(card){
				stripMarks(card);
				card.classList.remove('iws-search-hidden');
				if (!query) return;
				if (cardText(card).indexOf(query.toLowerCase()) === -1) {
					card.classList.add('iws-search-hidden');
				} else {
					highlightNode(card, query);
				}
			});
		}
		var timer;
		function schedule(){ window.clearTimeout(timer); timer = window.setTimeout(apply, 120); }
		document.addEventListener('input', function(event){
			if (event.target.matches('.iws-search-input, .iws-filter-search input[type="search"], .iws-filter-wrapper input[type="search"], .woocommerce-product-search input[type="search"]')) schedule();
		}, true);
		document.addEventListener('DOMContentLoaded', apply);
		apply();
		if ('MutationObserver' in window) {
			new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true });
		}
	})();
	</script>
	<?php
}, 130 );


// Removed the old the old product-card inline CSS inline CSS block.
// Product-card and single-product button styling is now handled in the theme SCSS,
// so this runtime footer style no longer overrides compiled styles with broad !important rules.


/* v37: runtime fallback for single quote button font size, after plugin CSS. */
add_action( 'wp_footer', function() {
	?>
	<style id="iws-single-quote-button-font-size-v37">
		button.btn.btn-primary.tfa-quote-button.single,
		a.btn.btn-primary.tfa-quote-button.single,
		.tfa-quote-button.single,
		body.single-product .summary .tfa-quote-button.single,
		body.single-product .single_variation_wrap .tfa-quote-button.single {
			font-size: 16px !important;
		}
	</style>
	<?php
}, 140 );


/* v39: runtime fallback for all product/card quote and add-to-cart button font size, after older inline CSS. */
add_action( 'wp_footer', function() {
	?>
	<style id="iws-product-button-font-size-v39">
		.wc-block-product-template .product .wp-element-button,
		.wc-block-product-template .product .wp-block-button__link,
		.wc-block-product-template .product .wc-block-components-product-button__button,
		.products .product .wp-element-button,
		.products .product .wp-block-button__link,
		.products .product .wc-block-components-product-button__button,
		.products .product > a.button:not(.iws-compare-toggle),
		.products .product > .button:not(.iws-compare-toggle),
		.products .product > .add_to_cart_button,
		.products .product > .product_type_simple,
		.products .product > .product_type_variable,
		.iws-products-grid li.product .button:not(.iws-compare-toggle),
		.iws-products-grid li.product .add_to_cart_button,
		.iws-products-grid li.product .product_type_simple,
		.iws-products-grid li.product .product_type_variable,
		.products .product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .related.products li.product .button:not(.iws-compare-toggle),
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.tfa-mega-menu .product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.megamenu-modal .product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body .megamenu-modal .wc-block-product-template .product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.tfa-mega-menu .product .tfa-quote-button,
		.megamenu-modal .product .tfa-quote-button,
		body .megamenu-modal .wc-block-product-template .product .tfa-quote-button,
		button.btn.btn-primary.tfa-quote-button.single,
		a.btn.btn-primary.tfa-quote-button.single,
		.tfa-quote-button.single,
		body.single-product .summary .tfa-quote-button.single,
		body.single-product .single_variation_wrap .tfa-quote-button.single {
			font-size: 16px !important;
		}
	</style>
	<?php
}, 150 );

/* v40: final runtime fallback for every WooCommerce product button font size. */
add_action( 'wp_footer', function() {
	?>
	<style id="iws-all-woocommerce-product-buttons-font-size-v40">
		body.single-product .woocommerce div.product form.cart .button,
		body.single-product .woocommerce div.product form.cart button,
		body.single-product .woocommerce div.product form.cart button.single_add_to_cart_button,
		body.single-product .woocommerce div.product form.cart .single_add_to_cart_button,
		body.single-product .woocommerce div.product form.cart .tfa-quote-button.single,
		body.single-product .single_variation_wrap .button,
		body.single-product .single_variation_wrap button,
		body.single-product .single_variation_wrap .single_add_to_cart_button,
		body.single-product .single_variation_wrap .tfa-quote-button.single,
		body.single-product .summary .button,
		body.single-product .summary button,
		body.single-product .summary .single_add_to_cart_button,
		body.single-product .summary .tfa-quote-button.single,
		body.single-product .related.products li.product .button,
		body.single-product .related.products li.product .add_to_cart_button,
		body.single-product .related.products li.product .product_type_simple,
		body.single-product .related.products li.product .product_type_variable,
		body.single-product .related.products li.product .tfa-quote-button,
		body.single-product .upsells.products li.product .button,
		body.single-product .upsells.products li.product .add_to_cart_button,
		body.single-product .upsells.products li.product .product_type_simple,
		body.single-product .upsells.products li.product .product_type_variable,
		body.single-product .upsells.products li.product .tfa-quote-button,
		body.single-product .cross-sells.products li.product .button,
		body.single-product .cross-sells.products li.product .add_to_cart_button,
		body.single-product .cross-sells.products li.product .product_type_simple,
		body.single-product .cross-sells.products li.product .product_type_variable,
		body.single-product .cross-sells.products li.product .tfa-quote-button,
		body .woocommerce ul.products li.product .button,
		body .woocommerce ul.products li.product .add_to_cart_button,
		body .woocommerce ul.products li.product .product_type_simple,
		body .woocommerce ul.products li.product .product_type_variable,
		body .woocommerce ul.products li.product .tfa-quote-button,
		body .products .product .button,
		body .products .product .add_to_cart_button,
		body .products .product .product_type_simple,
		body .products .product .product_type_variable,
		body .products .product .tfa-quote-button,
		body .iws-products-grid li.product .button,
		body .iws-products-grid li.product .add_to_cart_button,
		body .iws-products-grid li.product .product_type_simple,
		body .iws-products-grid li.product .product_type_variable,
		body .iws-products-grid li.product .tfa-quote-button,
		body .wc-block-product-template .product .wp-element-button,
		body .wc-block-product-template .product .wp-block-button__link,
		body .wc-block-product-template .product .wc-block-components-product-button__button,
		body .wc-block-product-template .product .button,
		body .wc-block-product-template .product .tfa-quote-button,
		body .wc-block-product .wp-element-button,
		body .wc-block-product .wp-block-button__link,
		body .wc-block-product .wc-block-components-product-button__button,
		body .wc-block-product .button,
		body .wc-block-product .tfa-quote-button,
		body .tfa-mega-menu .product .button,
		body .tfa-mega-menu .product .wp-element-button,
		body .tfa-mega-menu .product .wp-block-button__link,
		body .tfa-mega-menu .product .wc-block-components-product-button__button,
		body .tfa-mega-menu .product .tfa-quote-button,
		body .megamenu-modal .product .button,
		body .megamenu-modal .product .wp-element-button,
		body .megamenu-modal .product .wp-block-button__link,
		body .megamenu-modal .product .wc-block-components-product-button__button,
		body .megamenu-modal .product .tfa-quote-button,
		body button.btn.btn-primary.tfa-quote-button.single,
		body a.btn.btn-primary.tfa-quote-button.single,
		body .tfa-quote-button.single {
			font-size: 16px !important;
		}
	</style>
	<?php
}, 999 );


/* v41: final runtime fallback for every WooCommerce product button font weight. */
add_action( 'wp_footer', function() {
	?>
	<style id="iws-all-woocommerce-product-buttons-font-weight-v41">
		body.single-product .woocommerce div.product form.cart .button,
		body.single-product .woocommerce div.product form.cart button,
		body.single-product .woocommerce div.product form.cart button.single_add_to_cart_button,
		body.single-product .woocommerce div.product form.cart .single_add_to_cart_button,
		body.single-product .woocommerce div.product form.cart .tfa-quote-button.single,
		body.single-product .single_variation_wrap .button,
		body.single-product .single_variation_wrap button,
		body.single-product .single_variation_wrap .single_add_to_cart_button,
		body.single-product .single_variation_wrap .tfa-quote-button.single,
		body.single-product .summary .button,
		body.single-product .summary button,
		body.single-product .summary .single_add_to_cart_button,
		body.single-product .summary .tfa-quote-button.single,
		body.single-product .related.products li.product .button,
		body.single-product .related.products li.product .add_to_cart_button,
		body.single-product .related.products li.product .product_type_simple,
		body.single-product .related.products li.product .product_type_variable,
		body.single-product .related.products li.product .tfa-quote-button,
		body.single-product .upsells.products li.product .button,
		body.single-product .upsells.products li.product .add_to_cart_button,
		body.single-product .upsells.products li.product .product_type_simple,
		body.single-product .upsells.products li.product .product_type_variable,
		body.single-product .upsells.products li.product .tfa-quote-button,
		body.single-product .cross-sells.products li.product .button,
		body.single-product .cross-sells.products li.product .add_to_cart_button,
		body.single-product .cross-sells.products li.product .product_type_simple,
		body.single-product .cross-sells.products li.product .product_type_variable,
		body.single-product .cross-sells.products li.product .tfa-quote-button,
		body .woocommerce ul.products li.product .button,
		body .woocommerce ul.products li.product .add_to_cart_button,
		body .woocommerce ul.products li.product .product_type_simple,
		body .woocommerce ul.products li.product .product_type_variable,
		body .woocommerce ul.products li.product .tfa-quote-button,
		body .products .product .button,
		body .products .product .add_to_cart_button,
		body .products .product .product_type_simple,
		body .products .product .product_type_variable,
		body .products .product .tfa-quote-button,
		body .iws-products-grid li.product .button,
		body .iws-products-grid li.product .add_to_cart_button,
		body .iws-products-grid li.product .product_type_simple,
		body .iws-products-grid li.product .product_type_variable,
		body .iws-products-grid li.product .tfa-quote-button,
		body .iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body .wc-block-product-template .product .wp-element-button,
		body .wc-block-product-template .product .wp-block-button__link,
		body .wc-block-product-template .product .wc-block-components-product-button__button,
		body .wc-block-product-template .product .button,
		body .wc-block-product-template .product .tfa-quote-button,
		body .wc-block-product .wp-element-button,
		body .wc-block-product .wp-block-button__link,
		body .wc-block-product .wc-block-components-product-button__button,
		body .wc-block-product .button,
		body .wc-block-product .tfa-quote-button,
		body .tfa-mega-menu .product .button,
		body .tfa-mega-menu .product .wp-element-button,
		body .tfa-mega-menu .product .wp-block-button__link,
		body .tfa-mega-menu .product .wc-block-components-product-button__button,
		body .tfa-mega-menu .product .tfa-quote-button,
		body .megamenu-modal .product .button,
		body .megamenu-modal .product .wp-element-button,
		body .megamenu-modal .product .wp-block-button__link,
		body .megamenu-modal .product .wc-block-components-product-button__button,
		body .megamenu-modal .product .tfa-quote-button,
		body button.btn.btn-primary.tfa-quote-button.single,
		body a.btn.btn-primary.tfa-quote-button.single,
		body .tfa-quote-button.single {
			font-weight: 500 !important;
		}
	</style>
	<?php
}, 1000 );


/**
 * v43: final Woo product button icon/layout cleanup.
 * Keeps Woo mini-cart drawer styling untouched; removes duplicated background icons and uses one pseudo-icon.
 */
add_action( 'wp_head', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="iws-product-button-icon-v43">
		:root{--iws-product-button-height:40px;--iws-product-button-radius:0 15px 0 0;}
		.woocommerce ul.products li.product,.products .product,.iws-products-grid li.product,.wc-block-product-template .product,.wc-block-product,.tfa-mega-menu .product,.megamenu-modal .product,body.single-product .related.products li.product,body.single-product .upsells.products li.product,body.single-product .cross-sells.products li.product{display:flex;flex-direction:column;align-items:stretch;padding-bottom:20px;box-sizing:border-box;}
		.woocommerce ul.products li.product>a.woocommerce-LoopProduct-link,.products .product>a.woocommerce-LoopProduct-link,.iws-products-grid li.product>a.woocommerce-LoopProduct-link{display:flex;flex:1 1 auto;flex-direction:column;min-height:0;}
		.woocommerce ul.products li.product .price,.products .product .price,.iws-products-grid li.product .price,.wc-block-product-template .product .price,.wc-block-product-template .product .wc-block-components-product-price,body.single-product .related.products .price,body.single-product .upsells.products .price,body.single-product .cross-sells.products .price{position:relative;inset:auto;margin:auto 0 10px;padding:0;width:100%;max-width:100%;transform:none;}
		.woocommerce ul.products li.product .tfa-wcqb-loop-wrap,.products .product .tfa-wcqb-loop-wrap,.iws-products-grid li.product .tfa-wcqb-loop-wrap,.wc-block-product-template .product .tfa-wcqb-loop-wrap,.wc-block-product .tfa-wcqb-loop-wrap,body.single-product .related.products li.product .tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap{width:100%;max-width:100%;margin-top:10px;}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle),.woocommerce ul.products li.product .add_to_cart_button,.woocommerce ul.products li.product .product_type_simple,.woocommerce ul.products li.product .product_type_variable,.products .product .button:not(.iws-compare-toggle),.products .product .add_to_cart_button,.products .product .product_type_simple,.products .product .product_type_variable,.iws-products-grid li.product .button:not(.iws-compare-toggle),.iws-products-grid li.product .add_to_cart_button,.iws-products-grid li.product .product_type_simple,.iws-products-grid li.product .product_type_variable,.wc-block-product-template .product .wp-element-button,.wc-block-product-template .product .wp-block-button__link,.wc-block-product-template .product .wc-block-components-product-button__button,.wc-block-product .wp-element-button,.wc-block-product .wp-block-button__link,.wc-block-product .wc-block-components-product-button__button,body.single-product .related.products li.product .button:not(.iws-compare-toggle),body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle),.tfa-wcqb-loop-wrap .tfa-quote-button,.tfa-quote-button.loop,.tfa-quote-button.single{display:flex;align-items:center;justify-content:center;position:relative;inset:auto;width:100%;max-width:100%;min-height:var(--iws-product-button-height);margin:0;padding:0 15px 0 15px;border:0;border-radius:var(--iws-product-button-radius);font-family:inherit;font-size:16px;font-weight:500;line-height:1.2;text-align:center;text-decoration:none;white-space:normal;box-sizing:border-box;overflow:hidden;background-repeat:no-repeat;background-image:none!important;transform:none;}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle)::after,.woocommerce ul.products li.product .add_to_cart_button::after,.woocommerce ul.products li.product .product_type_simple::after,.woocommerce ul.products li.product .product_type_variable::after,.products .product .button:not(.iws-compare-toggle)::after,.products .product .add_to_cart_button::after,.products .product .product_type_simple::after,.products .product .product_type_variable::after,.iws-products-grid li.product .button:not(.iws-compare-toggle)::after,.iws-products-grid li.product .add_to_cart_button::after,.iws-products-grid li.product .product_type_simple::after,.iws-products-grid li.product .product_type_variable::after,.wc-block-product-template .product .wp-element-button::after,.wc-block-product-template .product .wp-block-button__link::after,.wc-block-product-template .product .wc-block-components-product-button__button::after,.wc-block-product .wp-element-button::after,.wc-block-product .wp-block-button__link::after,.wc-block-product .wc-block-components-product-button__button::after,.tfa-wcqb-loop-wrap .tfa-quote-button::after,.tfa-quote-button.loop::after,.tfa-quote-button.single::after{content:"";position:absolute;top:50%;right:18px;width:18px;height:18px;background-repeat:no-repeat;background-position:center;background-size:contain;transform:translate(42px,-50%);transition:transform .22s ease;pointer-events:none;}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle):hover::after,.woocommerce ul.products li.product .add_to_cart_button:hover::after,.woocommerce ul.products li.product .product_type_simple:hover::after,.woocommerce ul.products li.product .product_type_variable:hover::after,.products .product .button:not(.iws-compare-toggle):hover::after,.products .product .add_to_cart_button:hover::after,.products .product .product_type_simple:hover::after,.products .product .product_type_variable:hover::after,.iws-products-grid li.product .button:not(.iws-compare-toggle):hover::after,.iws-products-grid li.product .add_to_cart_button:hover::after,.iws-products-grid li.product .product_type_simple:hover::after,.iws-products-grid li.product .product_type_variable:hover::after,.wc-block-product-template .product .wp-element-button:hover::after,.wc-block-product-template .product .wp-block-button__link:hover::after,.wc-block-product-template .product .wc-block-components-product-button__button:hover::after,.wc-block-product .wp-element-button:hover::after,.wc-block-product .wp-block-button__link:hover::after,.wc-block-product .wc-block-components-product-button__button:hover::after,.tfa-wcqb-loop-wrap .tfa-quote-button:hover::after,.tfa-quote-button.loop:hover::after,.tfa-quote-button.single:hover::after{transform:translate(0,-50%);}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle)::after,.woocommerce ul.products li.product .add_to_cart_button::after,.woocommerce ul.products li.product .product_type_simple::after,.woocommerce ul.products li.product .product_type_variable::after,.products .product .button:not(.iws-compare-toggle)::after,.products .product .add_to_cart_button::after,.products .product .product_type_simple::after,.products .product .product_type_variable::after,.iws-products-grid li.product .button:not(.iws-compare-toggle)::after,.iws-products-grid li.product .add_to_cart_button::after,.iws-products-grid li.product .product_type_simple::after,.iws-products-grid li.product .product_type_variable::after,.wc-block-product-template .product .wp-element-button::after,.wc-block-product-template .product .wp-block-button__link::after,.wc-block-product-template .product .wc-block-components-product-button__button::after,.wc-block-product .wp-element-button::after,.wc-block-product .wp-block-button__link::after,.wc-block-product .wc-block-components-product-button__button::after{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7 7h14l-2 8H8L6 3H3' fill='none' stroke='%23fff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='10' cy='20' r='1.6' fill='%23fff'/%3E%3Ccircle cx='18' cy='20' r='1.6' fill='%23fff'/%3E%3C/svg%3E");}
		.tfa-wcqb-loop-wrap .tfa-quote-button::after,.tfa-quote-button.loop::after,.tfa-quote-button.single::after{width:16px;height:16px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");}
	</style>
	<?php
}, 1002 );


/**
 * v44: final icon cleanup.
 * Do not touch Woo mini-cart drawer. Remove all background icon duplication and add icons via ::after only.
 */
add_action( 'wp_head', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="iws-product-button-icon-v44">
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle),
		.woocommerce ul.products li.product .add_to_cart_button,
		.woocommerce ul.products li.product .product_type_simple,
		.woocommerce ul.products li.product .product_type_variable,
		.products .product .button:not(.iws-compare-toggle),
		.products .product .add_to_cart_button,
		.products .product .product_type_simple,
		.products .product .product_type_variable,
		.iws-products-grid li.product .button:not(.iws-compare-toggle),
		.iws-products-grid li.product .add_to_cart_button,
		.iws-products-grid li.product .product_type_simple,
		.iws-products-grid li.product .product_type_variable,
		.wc-block-product-template .product .wp-element-button,
		.wc-block-product-template .product .wp-block-button__link,
		.wc-block-product-template .product .wc-block-components-product-button__button,
		.wc-block-product .wp-element-button,
		.wc-block-product .wp-block-button__link,
		.wc-block-product .wc-block-components-product-button__button,
		.tfa-wcqb-loop-wrap .tfa-quote-button,
		.tfa-quote-button.loop,
		.tfa-quote-button.single {
			background-image: none !important;
			background-repeat: no-repeat !important;
			background-position: center !important;
			font-size: 16px !important;
			font-weight: 500 !important;
			position: relative !important;
			overflow: hidden !important;
		}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle)::after,
		.woocommerce ul.products li.product .add_to_cart_button::after,
		.woocommerce ul.products li.product .product_type_simple::after,
		.woocommerce ul.products li.product .product_type_variable::after,
		.products .product .button:not(.iws-compare-toggle)::after,
		.products .product .add_to_cart_button::after,
		.products .product .product_type_simple::after,
		.products .product .product_type_variable::after,
		.iws-products-grid li.product .button:not(.iws-compare-toggle)::after,
		.iws-products-grid li.product .add_to_cart_button::after,
		.iws-products-grid li.product .product_type_simple::after,
		.iws-products-grid li.product .product_type_variable::after,
		.wc-block-product-template .product .wp-element-button::after,
		.wc-block-product-template .product .wp-block-button__link::after,
		.wc-block-product-template .product .wc-block-components-product-button__button::after,
		.wc-block-product .wp-element-button::after,
		.wc-block-product .wp-block-button__link::after,
		.wc-block-product .wc-block-components-product-button__button::after,
		.tfa-wcqb-loop-wrap .tfa-quote-button::after,
		.iws-products-grid li.product .tfa-quote-button::after,
		.tfa-quote-button.loop::after,
		.tfa-quote-button.single::after {
			content: "" !important;
			position: absolute !important;
			top: 50% !important;
			right: 10% !important;
			width: 18px !important;
			height: 18px !important;
			background-repeat: no-repeat !important;
			background-position: center !important;
			background-size: contain !important;
			transform: translate(42px, -50%) !important;
			transition: transform .22s ease !important;
			pointer-events: none !important;
		}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle)::after,
		.woocommerce ul.products li.product .add_to_cart_button::after,
		.woocommerce ul.products li.product .product_type_simple::after,
		.woocommerce ul.products li.product .product_type_variable::after,
		.products .product .button:not(.iws-compare-toggle)::after,
		.products .product .add_to_cart_button::after,
		.products .product .product_type_simple::after,
		.products .product .product_type_variable::after,
		.iws-products-grid li.product .button:not(.iws-compare-toggle)::after,
		.iws-products-grid li.product .add_to_cart_button::after,
		.iws-products-grid li.product .product_type_simple::after,
		.iws-products-grid li.product .product_type_variable::after,
		.wc-block-product-template .product .wp-element-button::after,
		.wc-block-product-template .product .wp-block-button__link::after,
		.wc-block-product-template .product .wc-block-components-product-button__button::after,
		.wc-block-product .wp-element-button::after,
		.wc-block-product .wp-block-button__link::after,
		.wc-block-product .wc-block-components-product-button__button::after {
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7 7h14l-2 8H8L6 3H3' fill='none' stroke='%23fff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3Ccircle cx='10' cy='20' r='1.6' fill='%23fff'/%3E%3Ccircle cx='18' cy='20' r='1.6' fill='%23fff'/%3E%3C/svg%3E") !important;
		}
		.tfa-wcqb-loop-wrap .tfa-quote-button::after,
		.iws-products-grid li.product .tfa-quote-button::after,
		.tfa-quote-button.loop::after,
		.tfa-quote-button.single::after {
			width: 16px !important;
			height: 16px !important;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
		}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle):hover::after,
		.woocommerce ul.products li.product .add_to_cart_button:hover::after,
		.woocommerce ul.products li.product .product_type_simple:hover::after,
		.woocommerce ul.products li.product .product_type_variable:hover::after,
		.products .product .button:not(.iws-compare-toggle):hover::after,
		.products .product .add_to_cart_button:hover::after,
		.products .product .product_type_simple:hover::after,
		.products .product .product_type_variable:hover::after,
		.iws-products-grid li.product .button:not(.iws-compare-toggle):hover::after,
		.iws-products-grid li.product .add_to_cart_button:hover::after,
		.iws-products-grid li.product .product_type_simple:hover::after,
		.iws-products-grid li.product .product_type_variable:hover::after,
		.wc-block-product-template .product .wp-element-button:hover::after,
		.wc-block-product-template .product .wp-block-button__link:hover::after,
		.wc-block-product-template .product .wc-block-components-product-button__button:hover::after,
		.wc-block-product .wp-element-button:hover::after,
		.wc-block-product .wp-block-button__link:hover::after,
		.wc-block-product .wc-block-components-product-button__button:hover::after,
		.tfa-wcqb-loop-wrap .tfa-quote-button:hover::after,
		.iws-products-grid li.product .tfa-quote-button:hover::after,
		.tfa-quote-button.loop:hover::after,
		.tfa-quote-button.single:hover::after {
			transform: translate(0, -50%) !important;
		}
	</style>
	<?php
}, 9999 );


add_action( 'wp_head', function () {
	?>
	<style id="iws-product-card-polish-v45">
		.wc-block-product .wp-element-button::after,
		.wc-block-product .wp-block-button__link::after,
		.wc-block-product .wc-block-components-product-button__button::after,
		.wc-block-product-template .product .wp-element-button::after,
		.wc-block-product-template .product .wp-block-button__link::after,
		.wc-block-product-template .product .wc-block-components-product-button__button::after{content:none!important;display:none!important;background-image:none!important}
		body.single-product .related.products li.product,body.single-product .upsells.products li.product,body.single-product .cross-sells.products li.product{display:flex!important;flex-direction:column!important;align-items:stretch!important;box-sizing:border-box!important;padding-bottom:20px!important;position:relative!important}
		body.single-product .related.products li.product>a.woocommerce-LoopProduct-link,body.single-product .upsells.products li.product>a.woocommerce-LoopProduct-link,body.single-product .cross-sells.products li.product>a.woocommerce-LoopProduct-link{display:flex!important;flex:1 1 auto!important;flex-direction:column!important;min-height:0!important;color:inherit!important;text-decoration:none!important}
		body.single-product .related.products li.product .price,body.single-product .upsells.products li.product .price,body.single-product .cross-sells.products li.product .price{position:relative!important;inset:auto!important;width:100%!important;max-width:100%!important;margin:auto !important;padding:0!important;transform:none!important;text-align:left!important}
		body.single-product .related.products li.product .button:not(.iws-compare-toggle),body.single-product .related.products li.product .add_to_cart_button,body.single-product .related.products li.product .product_type_simple,body.single-product .related.products li.product .product_type_variable,body.single-product .related.products li.product .tfa-wcqb-loop-wrap,body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),body.single-product .upsells.products li.product .add_to_cart_button,body.single-product .upsells.products li.product .product_type_simple,body.single-product .upsells.products li.product .product_type_variable,body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle),body.single-product .cross-sells.products li.product .add_to_cart_button,body.single-product .cross-sells.products li.product .product_type_simple,body.single-product .cross-sells.products li.product .product_type_variable,body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button{width:100%!important;max-width:100%!important;box-sizing:border-box!important;position:relative!important;inset:auto!important;transform:none!important}
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap{margin-top:8px!important}
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button{position:relative!important;overflow:hidden!important;padding-right:44px!important}
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button::after{content:""!important;position:absolute!important;top:50%!important;right:10%!important;width:16px!important;height:16px!important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:center!important;background-size:contain!important;transform:translate(42px,-50%)!important;transition:transform .22s ease!important;pointer-events:none!important}
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:hover::after,.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:focus::after{transform:translate(0,-50%)!important}
	</style>
	<?php
}, 999 );


add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <style id="iws-product-card-load-more-v46">
    
/* v46: load-more quote-only cards bottom aligned */
.woocommerce ul.products li.product,.products .product,.iws-products-grid li.product,.wc-block-product-template .product,.wc-block-product,body.single-product .related.products li.product,body.single-product .upsells.products li.product,body.single-product .cross-sells.products li.product{display:flex!important;flex-direction:column!important;align-items:stretch!important;padding-bottom:20px!important;box-sizing:border-box!important;}
.woocommerce ul.products li.product>a.woocommerce-LoopProduct-link,.products .product>a.woocommerce-LoopProduct-link,.iws-products-grid li.product>a.woocommerce-LoopProduct-link{display:flex!important;flex:1 1 auto!important;flex-direction:column!important;min-height:0!important;}
.woocommerce ul.products li.product>.tfa-wcqb-loop-wrap,.products .product>.tfa-wcqb-loop-wrap,.iws-products-grid li.product>.tfa-wcqb-loop-wrap,.wc-block-product-template .product>.tfa-wcqb-loop-wrap,.wc-block-product>.tfa-wcqb-loop-wrap,body.single-product .related.products li.product>.tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product>.tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product>.tfa-wcqb-loop-wrap{display:block!important;align-self:stretch!important;width:100%!important;max-width:100%!important;margin-top:auto!important;margin-bottom:0!important;padding:0!important;box-sizing:border-box!important;}
.woocommerce ul.products li.product>.tfa-wcqb-loop-wrap .tfa-quote-button,.products .product>.tfa-wcqb-loop-wrap .tfa-quote-button,.iws-products-grid li.product>.tfa-wcqb-loop-wrap .tfa-quote-button,.wc-block-product-template .product>.tfa-wcqb-loop-wrap .tfa-quote-button,.wc-block-product>.tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .related.products li.product>.tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .upsells.products li.product>.tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .cross-sells.products li.product>.tfa-wcqb-loop-wrap .tfa-quote-button{display:flex!important;align-items:center!important;justify-content:center!important;width:100%!important;max-width:100%!important;min-height:40px!important;margin:0!important;padding:0 15px 0 15px!important;box-sizing:border-box!important;font-size:16px!important;font-weight:500!important;line-height:1.2!important;text-align:center!important;position:relative!important;overflow:hidden!important;}
.woocommerce ul.products li.product>a.woocommerce-LoopProduct-link+.tfa-wcqb-loop-wrap,.products .product>a.woocommerce-LoopProduct-link+.tfa-wcqb-loop-wrap,.iws-products-grid li.product>a.woocommerce-LoopProduct-link+.tfa-wcqb-loop-wrap{margin-top:auto!important;}

    </style>
    <?php
}, 99);


add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <style id="iws-archive-quote-bottom-v47">
body.archive.woocommerce .products .product,body.archive.woocommerce .iws-products-grid li.product,.woocommerce-page.archive .products .product,.woocommerce-page.archive .iws-products-grid li.product{position:relative!important;padding-bottom:20px!important;box-sizing:border-box!important}
body.archive.woocommerce .products .product .tfa-wcqb-loop-wrap,body.archive.woocommerce .iws-products-grid li.product .tfa-wcqb-loop-wrap,.woocommerce-page.archive .products .product .tfa-wcqb-loop-wrap,.woocommerce-page.archive .iws-products-grid li.product .tfa-wcqb-loop-wrap{position:static!important;display:block!important;width:100%!important;max-width:100%!important;margin:0!important;padding:0!important}
body.archive.woocommerce .products .product .tfa-quote-button,body.archive.woocommerce .iws-products-grid li.product .tfa-quote-button,.woocommerce-page.archive .products .product .tfa-quote-button,.woocommerce-page.archive .iws-products-grid li.product .tfa-quote-button{width:calc(100% - 40px)!important;max-width:calc(100% - 40px)!important;position:absolute!important;left:20px!important;right:20px!important;bottom:20px!important;margin:0!important;box-sizing:border-box!important}
body.archive.woocommerce .products .product:has(.tfa-quote-button) .button:not(.iws-compare-toggle):not(.tfa-quote-button),body.archive.woocommerce .products .product:has(.tfa-quote-button) .add_to_cart_button,body.archive.woocommerce .products .product:has(.tfa-quote-button) .product_type_simple,body.archive.woocommerce .products .product:has(.tfa-quote-button) .product_type_variable,body.archive.woocommerce .iws-products-grid li.product:has(.tfa-quote-button) .button:not(.iws-compare-toggle):not(.tfa-quote-button),body.archive.woocommerce .iws-products-grid li.product:has(.tfa-quote-button) .add_to_cart_button,body.archive.woocommerce .iws-products-grid li.product:has(.tfa-quote-button) .product_type_simple,body.archive.woocommerce .iws-products-grid li.product:has(.tfa-quote-button) .product_type_variable{position:absolute!important;left:20px!important;right:20px!important;bottom:68px!important;width:calc(100% - 40px)!important;max-width:calc(100% - 40px)!important;margin:0!important}
body.archive.woocommerce .products .product:has(.button:not(.iws-compare-toggle):not(.tfa-quote-button)):has(.tfa-quote-button),body.archive.woocommerce .iws-products-grid li.product:has(.button:not(.iws-compare-toggle):not(.tfa-quote-button)):has(.tfa-quote-button){padding-bottom:118px!important}
    

/* v49: direct WC block quote wrapper spacing */
.wc-block-product-template .product > .tfa-wcqb-loop-wrap,
.wc-block-product > .tfa-wcqb-loop-wrap{margin-top:20px!important;}
</style>
    <?php
}, 99999);


add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <style id="iws-product-card-v50-icon-related-fixes">
.wc-block-product .wp-element-button:not(.tfa-quote-button)::after,.wc-block-product .wp-block-button__link:not(.tfa-quote-button)::after,.wc-block-product .wc-block-components-product-button__button:not(.tfa-quote-button)::after,.wc-block-product-template .product .wp-element-button:not(.tfa-quote-button)::after,.wc-block-product-template .product .wp-block-button__link:not(.tfa-quote-button)::after,.wc-block-product-template .product .wc-block-components-product-button__button:not(.tfa-quote-button)::after{content:none!important;display:none!important;background-image:none!important}
.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button,.woocommerce .iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button{position:relative!important;overflow:hidden!important;background-image:none!important}
.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button::after,.woocommerce .iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button::after{content:""!important;display:block!important;position:absolute!important;top:50%!important;right:10%!important;width:16px!important;height:16px!important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:center!important;background-size:contain!important;transform:translate(42px,-50%)!important;transition:transform .22s ease!important;pointer-events:none!important}
.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:hover::after,.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:focus::after,.woocommerce .iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:hover::after,.woocommerce .iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button:focus::after{transform:translate(0,-50%)!important}
body.single-product .related.products li.product,body.single-product .upsells.products li.product,body.single-product .cross-sells.products li.product{position:relative!important;display:flex!important;flex-direction:column!important;align-items:stretch!important;box-sizing:border-box!important;padding-bottom:20px!important}
body.single-product .related.products li.product>.tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product>.tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product>.tfa-wcqb-loop-wrap{width:100%!important;max-width:100%!important;align-self:stretch!important;margin-top:auto!important;margin-bottom:0!important;padding:0!important}
body.single-product .related.products li.product>.tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .upsells.products li.product>.tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .cross-sells.products li.product>.tfa-wcqb-loop-wrap .tfa-quote-button,body.single-product .related.products li.product .button:not(.iws-compare-toggle),body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle){width:100%!important;max-width:100%!important;min-height:40px!important;box-sizing:border-box!important}
    /* home WC block quote buttons use one arrow */

.wc-block-product>.tfa-wcqb-loop-wrap .tfa-quote-button,.wc-block-product-template .product>.tfa-wcqb-loop-wrap .tfa-quote-button{position:relative!important;overflow:hidden!important;background-image:none!important}
.wc-block-product>.tfa-wcqb-loop-wrap .tfa-quote-button::after,.wc-block-product-template .product>.tfa-wcqb-loop-wrap .tfa-quote-button::after{content:""!important;display:block!important;position:absolute!important;top:50%!important;right:10%!important;width:16px!important;height:16px!important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:center!important;background-size:contain!important;transform:translate(42px,-50%)!important;transition:transform .22s ease!important;pointer-events:none!important}
.wc-block-product>.tfa-wcqb-loop-wrap .tfa-quote-button:hover::after,.wc-block-product>.tfa-wcqb-loop-wrap .tfa-quote-button:focus::after,.wc-block-product-template .product>.tfa-wcqb-loop-wrap .tfa-quote-button:hover::after,.wc-block-product-template .product>.tfa-wcqb-loop-wrap .tfa-quote-button:focus::after{transform:translate(0,-50%)!important}

.wc-block-product .wc-block-components-product-button .wc-block-components-product-button__button:not(.tfa-quote-button),.wc-block-product-template .product .wc-block-components-product-button .wc-block-components-product-button__button:not(.tfa-quote-button){position:relative!important;overflow:hidden!important;background-image:none!important}
.wc-block-product .wc-block-components-product-button .wc-block-components-product-button__button:not(.tfa-quote-button)::after,.wc-block-product-template .product .wc-block-components-product-button .wc-block-components-product-button__button:not(.tfa-quote-button)::after{content:""!important;display:block!important;position:absolute!important;top:50%!important;right:10%!important;width:17px!important;height:17px!important;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M6.2 6h15l-1.7 8.5H8L6.2 6zM6.2 6 5.7 3.5H3M9 20a1.4 1.4 0 1 0 0-2.8A1.4 1.4 0 0 0 9 20zm9 0a1.4 1.4 0 1 0 0-2.8A1.4 1.4 0 0 0 18 20z' fill='none' stroke='%23fff' stroke-width='2.1' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:center!important;background-size:contain!important;transform:translate(42px,-50%)!important;transition:transform .22s ease!important;pointer-events:none!important}
.wc-block-product .wc-block-components-product-button .wc-block-components-product-button__button:not(.tfa-quote-button):hover::after,.wc-block-product .wc-block-components-product-button .wc-block-components-product-button__button:not(.tfa-quote-button):focus::after,.wc-blockProduct-template .product .wc-block-componentsProductButton .wc-block-componentsProductButton__button:not(.tfa-quote-button):hover::after,.wc-blockProduct-template .product .wc-block-componentsProductButton .wc-block-componentsProductButton__button:not(.tfa-quote-button):focus::after{transform:translate(0,-50%)!important}

</style>
    <?php
}, 100000);

/**
 * v45: final product-card action spacing and mini-cart footer click guard.
 */
add_action( 'wp_head', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="iws-product-card-actions-v45">
		.woocommerce ul.products li.product .tfa-wcqb-loop-wrap,.products .product .tfa-wcqb-loop-wrap,.iws-products-grid li.product .tfa-wcqb-loop-wrap,.wc-block-product-template .product .tfa-wcqb-loop-wrap,.wc-block-product .tfa-wcqb-loop-wrap,.wp-block-post.product .tfa-wcqb-loop-wrap,body.single-product .related.products li.product .tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap{width:100%;max-width:100%}
		.woocommerce ul.products li.product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,.products .product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,.iws-products-grid li.product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,.wc-block-product-template .product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,.wc-block-product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,.wp-block-post.product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,body.single-product .related.products li.product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product.tfa-wcqb-quote-only-card .tfa-wcqb-loop-wrap{margin-top:auto!important}
		.woocommerce ul.products li.product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,.products .product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,.iws-products-grid li.product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,.wc-block-product-template .product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,.wc-block-product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,.wp-block-post.product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,body.single-product .related.products li.product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,body.single-product .upsells.products li.product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap,body.single-product .cross-sells.products li.product.tfa-wcqb-has-native-button .tfa-wcqb-loop-wrap{margin-top:8px!important}
		.woocommerce ul.products li.product .woocommerce-loop-product__title+.tfa-wcqb-loop-wrap,.woocommerce ul.products li.product .woocommerce-loop-product__title+.button,.products .product .woocommerce-loop-product__title+.tfa-wcqb-loop-wrap,.products .product .woocommerce-loop-product__title+.button,.iws-products-grid li.product .woocommerce-loop-product__title+.tfa-wcqb-loop-wrap,.iws-products-grid li.product .woocommerce-loop-product__title+.button{margin-top:16px!important}
		.wc-block-components-drawer .wp-block-woocommerce-mini-cart-cart-button-block,.wc-block-components-drawer .wp-block-woocommerce-mini-cart-checkout-button-block,.wc-block-components-drawer .wc-block-mini-cart__footer-actions,.wc-block-mini-cart__drawer .wp-block-woocommerce-mini-cart-cart-button-block,.wc-block-mini-cart__drawer .wp-block-woocommerce-mini-cart-checkout-button-block,.wc-block-mini-cart__drawer .wc-block-mini-cart__footer-actions{position:relative;z-index:10020;pointer-events:auto}.wc-block-components-drawer .wp-block-woocommerce-mini-cart-cart-button-block a,.wc-block-components-drawer .wp-block-woocommerce-mini-cart-checkout-button-block a,.wc-block-components-drawer .wc-block-mini-cart__footer-actions a,.wc-block-mini-cart__drawer .wp-block-woocommerce-mini-cart-cart-button-block a,.wc-block-mini-cart__drawer .wp-block-woocommerce-mini-cart-checkout-button-block a,.wc-block-mini-cart__drawer .wc-block-mini-cart__footer-actions a{position:relative;z-index:10021;pointer-events:auto}
	</style>
	<?php
}, 1005 );


/**
 * v51: final quote button spacing fixes.
 * Removes the older iws-products-grid quote-button right padding and aligns quote arrow positions.
 */
add_action( 'wp_footer', function() {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="iws-quote-button-spacing-v51">
		.iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body .iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		.woocommerce .iws-products-grid li.product .tfa-wcqb-loop-wrap .tfa-quote-button {
			padding-right: 15px !important;
		}
		.wc-block-product > .tfa-wcqb-loop-wrap .tfa-quote-button::after,
		.wc-block-product-template .product > .tfa-wcqb-loop-wrap .tfa-quote-button::after {
			right: 20px !important;
		}
		.woocommerce ul.products li.product .button:not(.iws-compare-toggle)::after,
		.woocommerce ul.products li.product .add_to_cart_button::after,
		.woocommerce ul.products li.product .product_type_simple::after,
		.woocommerce ul.products li.product .product_type_variable::after,
		.products .product .button:not(.iws-compare-toggle)::after,
		.products .product .add_to_cart_button::after,
		.products .product .product_type_simple::after,
		.products .product .product_type_variable::after,
		.iws-products-grid li.product .button:not(.iws-compare-toggle)::after,
		.iws-products-grid li.product .add_to_cart_button::after,
		.iws-products-grid li.product .product_type_simple::after,
		.iws-products-grid li.product .product_type_variable::after,
		.wc-block-product-template .product .wp-element-button::after,
		.wc-block-product-template .product .wp-block-button__link::after,
		.wc-block-product-template .product .wc-block-components-product-button__button::after,
		.wc-block-product .wp-element-button::after,
		.wc-block-product .wp-block-button__link::after,
		.wc-block-product .wc-block-components-product-button__button::after,
		.tfa-wcqb-loop-wrap .tfa-quote-button::after,
		.iws-products-grid li.product .tfa-quote-button::after,
		.tfa-quote-button.loop::after,
		.tfa-quote-button.single::after {
			right: 30px !important;
		}
		.wc-block-product > .tfa-wcqb-loop-wrap .tfa-quote-button::after,
		.wc-block-product-template .product > .tfa-wcqb-loop-wrap .tfa-quote-button::after {
			right: 20px !important;
		}
	</style>
	<?php
}, 100001 );


/**
 * v47: keep single-product recommended/custom option product cards on the default card layout.
 * Hides rich description/logo content injected into product loops and re-aligns prices/buttons.
 */
add_action( 'wp_head', function () {
	if ( is_admin() || ! is_product() ) {
		return;
	}
	?>
	<style id="iws-single-recommended-products-default-cards-v47">
		body.single-product .related.products,
		body.single-product .upsells.products,
		body.single-product .cross-sells.products,
		body.single-product .wp-block-woocommerce-product-collection,
		body.single-product .wc-block-product-template {
			clear: both;
			width: 100%;
		}
		body.single-product .related.products ul.products,
		body.single-product .upsells.products ul.products,
		body.single-product .cross-sells.products ul.products,
		body.single-product .wc-block-product-template {
			align-items: stretch !important;
		}
		body.single-product .related.products li.product,
		body.single-product .upsells.products li.product,
		body.single-product .cross-sells.products li.product,
		body.single-product .wc-block-product-template .wc-block-product,
		body.single-product .wc-block-product-template .product {
			display: flex !important;
			flex-direction: column !important;
			align-items: stretch !important;
			height: 100% !important;
			min-height: 0 !important;
			padding: 20px !important;
			box-sizing: border-box !important;
			overflow: hidden !important;
		}
		body.single-product .related.products li.product > a.woocommerce-LoopProduct-link,
		body.single-product .upsells.products li.product > a.woocommerce-LoopProduct-link,
		body.single-product .cross-sells.products li.product > a.woocommerce-LoopProduct-link {
			display: flex !important;
			flex: 1 1 auto !important;
			flex-direction: column !important;
			min-height: 0 !important;
			text-decoration: none !important;
		}
		body.single-product .related.products li.product img,
		body.single-product .upsells.products li.product img,
		body.single-product .cross-sells.products li.product img,
		body.single-product .wc-block-product-template .wc-block-product img,
		body.single-product .wc-block-product-template .product img {
			display: block !important;
			width: 100% !important;
			height: 270px !important;
			max-height: 270px !important;
			margin: 0 0 14px !important;
			object-fit: cover !important;
			object-position: center !important;
		}
		body.single-product .related.products li.product .woocommerce-loop-product__title,
		body.single-product .upsells.products li.product .woocommerce-loop-product__title,
		body.single-product .cross-sells.products li.product .woocommerce-loop-product__title,
		body.single-product .wc-block-product-template .wp-block-post-title,
		body.single-product .wc-block-product-template .wc-block-components-product-name {
			margin: 0 0 8px !important;
			font-size: 22px !important;
			font-weight: 700 !important;
			line-height: 1.15 !important;
			color: inherit !important;
			text-align: left !important;
		}
		body.single-product .related.products li.product .woocommerce-loop-product__title a,
		body.single-product .upsells.products li.product .woocommerce-loop-product__title a,
		body.single-product .cross-sells.products li.product .woocommerce-loop-product__title a,
		body.single-product .wc-block-product-template .wp-block-post-title a,
		body.single-product .wc-block-product-template .wc-block-components-product-name a {
			color: inherit !important;
			text-decoration: none !important;
		}
		body.single-product .related.products li.product .woocommerce-loop-product__description,
		body.single-product .related.products li.product .woocommerce-product-details__short-description,
		body.single-product .related.products li.product .product-short-description,
		body.single-product .related.products li.product .product-description,
		body.single-product .related.products li.product .wp-block-post-content,
		body.single-product .related.products li.product .wc-block-components-product-summary,
		body.single-product .related.products li.product .wp-block-post-excerpt__more-text,
		body.single-product .related.products li.product .wp-block-post-excerpt__more-link,
		body.single-product .upsells.products li.product .woocommerce-loop-product__description,
		body.single-product .upsells.products li.product .woocommerce-product-details__short-description,
		body.single-product .upsells.products li.product .product-short-description,
		body.single-product .upsells.products li.product .product-description,
		body.single-product .upsells.products li.product .wp-block-post-content,
		body.single-product .upsells.products li.product .wc-block-components-product-summary,
		body.single-product .upsells.products li.product .wp-block-post-excerpt__more-text,
		body.single-product .upsells.products li.product .wp-block-post-excerpt__more-link,
		body.single-product .cross-sells.products li.product .woocommerce-loop-product__description,
		body.single-product .cross-sells.products li.product .woocommerce-product-details__short-description,
		body.single-product .cross-sells.products li.product .product-short-description,
		body.single-product .cross-sells.products li.product .product-description,
		body.single-product .cross-sells.products li.product .wp-block-post-content,
		body.single-product .cross-sells.products li.product .wc-block-components-product-summary,
		body.single-product .cross-sells.products li.product .wp-block-post-excerpt__more-text,
		body.single-product .cross-sells.products li.product .wp-block-post-excerpt__more-link {
			display: none !important;
		}
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link > p,
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link > ul,
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link > ol,
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link > div:not(.star-rating):not(.price),
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link > p,
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link > ul,
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link > ol,
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link > div:not(.star-rating):not(.price),
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link > p,
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link > ul,
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link > ol,
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link > div:not(.star-rating):not(.price) {
			display: none !important;
		}
		body.single-product .related.products li.product .wp-block-post-excerpt,
		body.single-product .upsells.products li.product .wp-block-post-excerpt,
		body.single-product .cross-sells.products li.product .wp-block-post-excerpt,
		body.single-product .wc-block-product-template .wp-block-post-excerpt {
			display: block !important;
			margin: 0 0 18px !important;
			font-size: 16px !important;
			line-height: 1.28 !important;
			color: inherit !important;
		}
		body.single-product .related.products li.product .wp-block-post-excerpt p,
		body.single-product .upsells.products li.product .wp-block-post-excerpt p,
		body.single-product .cross-sells.products li.product .wp-block-post-excerpt p,
		body.single-product .wc-block-product-template .wp-block-post-excerpt p {
			display: -webkit-box !important;
			-webkit-line-clamp: 3;
			-webkit-box-orient: vertical;
			overflow: hidden !important;
			margin: 0 !important;
		}
		body.single-product .related.products li.product .price,
		body.single-product .upsells.products li.product .price,
		body.single-product .cross-sells.products li.product .price,
		body.single-product .wc-block-product-template .wc-block-components-product-price,
		body.single-product .wc-block-product-template .price {
			display: block !important;
			width: 100% !important;
			min-height: 44px !important;
			margin: auto 0 10px !important;
			padding: 0 !important;
			text-align: left !important;
			position: relative !important;
			inset: auto !important;
			transform: none !important;
		}
		body.single-product .related.products li.product .price:before,
		body.single-product .upsells.products li.product .price:before,
		body.single-product .cross-sells.products li.product .price:before,
		body.single-product .wc-block-product-template .wc-block-components-product-price:before,
		body.single-product .wc-block-product-template .price:before {
			display: block !important;
			margin: 0 0 4px !important;
			text-align: left !important;
		}
		body.single-product .related.products li.product .price:empty,
		body.single-product .upsells.products li.product .price:empty,
		body.single-product .cross-sells.products li.product .price:empty,
		body.single-product .wc-block-product-template .wc-block-components-product-price:empty,
		body.single-product .wc-block-product-template .price:empty,
		body.single-product .related.products li.product .price:not(:has(.amount)),
		body.single-product .upsells.products li.product .price:not(:has(.amount)),
		body.single-product .cross-sells.products li.product .price:not(:has(.amount)),
		body.single-product .wc-block-product-template .wc-block-components-product-price:not(:has(.amount)),
		body.single-product .wc-block-product-template .price:not(:has(.amount)) {
			display: none !important;
			min-height: 0 !important;
			margin: auto 0 10px !important;
		}
		body.single-product .related.products li.product .price:empty:before,
		body.single-product .upsells.products li.product .price:empty:before,
		body.single-product .cross-sells.products li.product .price:empty:before,
		body.single-product .wc-block-product-template .wc-block-components-product-price:empty:before,
		body.single-product .wc-block-product-template .price:empty:before,
		body.single-product .related.products li.product .price:not(:has(.amount)):before,
		body.single-product .upsells.products li.product .price:not(:has(.amount)):before,
		body.single-product .cross-sells.products li.product .price:not(:has(.amount)):before,
		body.single-product .wc-block-product-template .wc-block-components-product-price:not(:has(.amount)):before,
		body.single-product .wc-block-product-template .price:not(:has(.amount)):before {
			content: none !important;
			display: none !important;
		}
		body.single-product .related.products li.product .button:not(.iws-compare-toggle),
		body.single-product .related.products li.product .add_to_cart_button,
		body.single-product .related.products li.product .product_type_simple,
		body.single-product .related.products li.product .product_type_variable,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .upsells.products li.product .add_to_cart_button,
		body.single-product .upsells.products li.product .product_type_simple,
		body.single-product .upsells.products li.product .product_type_variable,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .cross-sells.products li.product .add_to_cart_button,
		body.single-product .cross-sells.products li.product .product_type_simple,
		body.single-product .cross-sells.products li.product .product_type_variable,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .wc-block-product-template .wc-block-components-product-button,
		body.single-product .wc-block-product-template .wp-block-button,
		body.single-product .wc-block-product-template .wp-block-button__link,
		body.single-product .wc-block-product-template .wc-block-components-product-button__button {
			width: 100% !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
			position: relative !important;
			inset: auto !important;
			transform: none !important;
			margin-left: 0 !important;
			margin-right: 0 !important;
		}
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap,
		body.single-product .wc-block-product-template .wc-block-components-product-button,
		body.single-product .wc-block-product-template .wp-block-button {
			display: block !important;
			align-self: stretch !important;
			padding: 0 !important;
			margin-top: 8px !important;
			margin-bottom: 0 !important;
		}
		body.single-product .related.products li.product .price + .button,
		body.single-product .related.products li.product .price + .tfa-wcqb-loop-wrap,
		body.single-product .upsells.products li.product .price + .button,
		body.single-product .upsells.products li.product .price + .tfa-wcqb-loop-wrap,
		body.single-product .cross-sells.products li.product .price + .button,
		body.single-product .cross-sells.products li.product .price + .tfa-wcqb-loop-wrap {
			margin-top: 0 !important;
		}
		body.single-product .related.products li.product .button:not(.iws-compare-toggle),
		body.single-product .related.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .upsells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .cross-sells.products li.product .button:not(.iws-compare-toggle),
		body.single-product .cross-sells.products li.product .tfa-wcqb-loop-wrap .tfa-quote-button,
		body.single-product .wc-block-product-template .wp-block-button__link,
		body.single-product .wc-block-product-template .wc-block-components-product-button__button {
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
			min-height: 40px !important;
			padding: 0 15px !important;
			font-size: 16px !important;
			font-weight: 500 !important;
			line-height: 1.2 !important;
			text-align: center !important;
			text-decoration: none !important;
			border-radius: 0 15px 0 0 !important;
			overflow: hidden !important;
		}

		/* v51: hide content logos/images between the product heading and text; keep only the main thumbnail. */
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link h1:has(img),
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link h2:has(img),
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link h3:has(img),
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link h4:has(img),
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link h5:has(img),
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link h6:has(img),
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link h1:has(img),
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link h2:has(img),
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link h3:has(img),
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link h4:has(img),
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link h5:has(img),
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link h6:has(img),
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link h1:has(img),
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link h2:has(img),
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link h3:has(img),
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link h4:has(img),
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link h5:has(img),
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link h6:has(img),
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link img.alignnone,
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link img[class*="wp-image-"],
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link img.alignnone,
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link img[class*="wp-image-"],
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link img.alignnone,
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link img[class*="wp-image-"],
		body.single-product .wc-block-product-template .wp-block-post-excerpt img,
		body.single-product .wc-block-product-template .wp-block-post-content img,
		body.single-product .wc-block-product-template .wc-block-components-product-summary img {
			display: none !important;
			width: 0 !important;
			height: 0 !important;
			max-width: 0 !important;
			max-height: 0 !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link > img:first-child,
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link > img:first-child,
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link > img:first-child {
			display: block !important;
			width: 100% !important;
			height: 270px !important;
			max-height: 270px !important;
			margin: 0 0 14px !important;
			object-fit: cover !important;
			object-position: center !important;
		}

		@media (max-width: 767.98px) {
			body.single-product .related.products li.product,
			body.single-product .upsells.products li.product,
			body.single-product .cross-sells.products li.product,
			body.single-product .wc-block-product-template .wc-block-product,
			body.single-product .wc-block-product-template .product {
				padding: 12px !important;
			}
			body.single-product .related.products li.product img,
			body.single-product .upsells.products li.product img,
			body.single-product .cross-sells.products li.product img,
			body.single-product .wc-block-product-template .wc-block-product img,
			body.single-product .wc-block-product-template .product img {
				height: 150px !important;
				max-height: 150px !important;
			}
			body.single-product .related.products li.product .woocommerce-loop-product__title,
			body.single-product .upsells.products li.product .woocommerce-loop-product__title,
			body.single-product .cross-sells.products li.product .woocommerce-loop-product__title,
			body.single-product .wc-block-product-template .wp-block-post-title,
			body.single-product .wc-block-product-template .wc-block-components-product-name {
				font-size: 18px !important;
			}
		}
	</style>
	<?php
}, 10000 );

/**
 * v52: show the controlled product-card description on single-product related cards.
 * Earlier safety CSS hides raw product-content paragraphs/lists inside related cards so
 * full descriptions and logos do not break the card layout. Keep that protection, but
 * explicitly allow the theme's trimmed .prod-desc snippet to display.
 */
add_action( 'wp_head', function () {
	if ( is_admin() || ! is_product() ) {
		return;
	}
	?>
	<style id="iws-single-related-product-description-v52">
		body.single-product .related.products li.product a.woocommerce-LoopProduct-link > p.prod-desc,
		body.single-product .upsells.products li.product a.woocommerce-LoopProduct-link > p.prod-desc,
		body.single-product .cross-sells.products li.product a.woocommerce-LoopProduct-link > p.prod-desc {
			display: -webkit-box !important;
			-webkit-line-clamp: 3;
			-webkit-box-orient: vertical;
			overflow: hidden !important;
			margin: 0 0 18px !important;
			font-size: 16px !important;
			line-height: 1.28 !important;
			color: inherit !important;
			text-align: left !important;
		}
	</style>
	<?php
}, 1000 );

/**
 * v54: keep up-sell quote/select-options buttons to one hover arrow.
 * Up-sells can receive both the generic .btn-primary/areoi hover icon and the
 * quote-button pseudo icon. Remove the inherited icon layers for the quote
 * button and keep a single ::after arrow, matching related products.
 */
add_action( 'wp_footer', function () {
	if ( is_admin() || ! is_product() ) {
		return;
	}
	?>
	<style id="iws-upsells-quote-button-single-arrow-v54">
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap a.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap button.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options {
			position: relative !important;
			overflow: hidden !important;
			background-image: none !important;
			background-repeat: no-repeat !important;
			background-position: center !important;
			padding-right: 15px !important;
		}
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options::before,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap a.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options::before,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap button.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options::before {
			content: none !important;
			display: none !important;
			background-image: none !important;
		}
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options::after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap a.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options::after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap button.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options::after {
			content: "" !important;
			display: block !important;
			position: absolute !important;
			top: 50% !important;
			right: 30px !important;
			left: auto !important;
			width: 16px !important;
			height: 16px !important;
			margin: 0 !important;
			opacity: 1 !important;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M5 12h12M13 6l6 6-6 6' fill='none' stroke='%23fff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
			background-repeat: no-repeat !important;
			background-position: center !important;
			background-size: contain !important;
			transform: translate(56px, -50%) !important;
			transition: transform .22s ease !important;
			pointer-events: none !important;
		}
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options:hover::after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap .btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options:focus::after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap a.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options:hover::after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap a.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options:focus::after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap button.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options:hover::after,
		body.single-product .upsells.products li.product .tfa-wcqb-loop-wrap button.btn.btn-primary.tfa-quote-button.loop.tfa-wcqb-select-options:focus::after {
			transform: translate(0, -50%) !important;
		}
	</style>
	<?php
}, 100002 );
