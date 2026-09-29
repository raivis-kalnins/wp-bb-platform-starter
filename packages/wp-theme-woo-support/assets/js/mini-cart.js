(function ($) {
	'use strict';
	var lastFocus = null;

	function getDrawer() { return document.querySelector('.wptws-mini-cart'); }
	function focusables(drawer) {
		return drawer ? Array.prototype.slice.call(drawer.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')) : [];
	}
	function setOpen(open) {
		var drawer = getDrawer();
		if (!drawer) return;
		document.documentElement.classList.toggle('wptws-mini-cart-open', open);
		document.body.classList.toggle('wptws-mini-cart-open', open);
		drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
		if (open) {
			lastFocus = document.activeElement;
			window.setTimeout(function () { var close = drawer.querySelector('.wptws-mini-cart__close'); if (close) close.focus(); }, 80);
		} else if (lastFocus && typeof lastFocus.focus === 'function') {
			lastFocus.focus();
		}
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-wptws-mini-cart], .wptws-mini-cart-trigger, .wp-theme-demo-cart, .wpbb-mini-cart-trigger');
		if (trigger && getDrawer()) { event.preventDefault(); setOpen(true); return; }
		if (event.target.closest('.wptws-mini-cart__close') || event.target.classList.contains('wptws-mini-cart-overlay')) { event.preventDefault(); setOpen(false); }
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') { setOpen(false); return; }
		if (event.key !== 'Tab' || !document.documentElement.classList.contains('wptws-mini-cart-open')) return;
		var drawer = getDrawer(), items = focusables(drawer);
		if (!items.length) return;
		var first = items[0], last = items[items.length - 1];
		if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
		else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
	});

	if ($ && $.fn) {
		$(document.body).on('added_to_cart', function () { setOpen(true); });
	}
}(window.jQuery));
