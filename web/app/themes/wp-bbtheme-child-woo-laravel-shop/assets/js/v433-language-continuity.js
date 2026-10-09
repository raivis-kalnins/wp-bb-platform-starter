/* v4.0.33: carry language only on this theme's same-origin storefront AJAX.
 * No cookies, redirects or interception of external/payment/REST requests.
 * The request body (including FormData uploads and security nonces) is untouched.
 */
(function (window, $) {
    'use strict';
    var config = window.WpbbLanguage433 || {};
    var lang = config.lang === 'lv' ? 'lv' : 'en';
    function field(data, key) {
        if (!data) return '';
        if (typeof data.get === 'function') return data.get(key) || '';
        if (typeof data === 'string') return new URLSearchParams(data).get(key) || '';
        return typeof data === 'object' && typeof data[key] === 'string' ? data[key] : '';
    }
    function storefrontAction(action) {
        return typeof action === 'string' && (
            (action.indexOf('wpbbshop_') === 0 && action.indexOf('wpbbshop_admin_') !== 0) ||
            action.indexOf('wp_theme_woo_') === 0 ||
            ['iws_filter_products', 'iws_product_taxonomy_archive', 'wp_ajax_search'].indexOf(action) !== -1
        );
    }
    function localizedUrl(raw, data) {
        try {
            var url = new URL(raw, window.location.href);
            if (url.origin !== window.location.origin || !/\/wp-admin\/admin-ajax\.php$/.test(url.pathname)) return null;
            if (!storefrontAction(field(data, 'action') || url.searchParams.get('action'))) return null;
            // An explicit per-request language has priority (e.g. an EN preview).
            var requested = field(data, 'lang') || url.searchParams.get('lang');
            url.searchParams.set('lang', requested === 'en' || requested === 'lv' ? requested : lang);
            return url.href;
        } catch (ignore) { return null; }
    }
    if ($ && typeof $.ajaxPrefilter === 'function') {
        $.ajaxPrefilter(function (options) {
            var url = localizedUrl(options.url, options.data);
            if (url) options.url = url;
        });
    }
    if (typeof window.fetch === 'function') {
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            var raw = typeof input === 'string' || input instanceof URL ? String(input) : (input && input.url);
            var url = raw && localizedUrl(raw, init && init.body);
            if (!url) return originalFetch.apply(this, arguments);
            // Preserve Request headers, method, credentials, signal and body.
            // We deliberately do not consume an existing Request body to sniff it.
            var target = typeof window.Request === 'function' && input instanceof window.Request ? new window.Request(url, input) : url;
            return originalFetch.call(this, target, init);
        };
    }
})(window, window.jQuery);
