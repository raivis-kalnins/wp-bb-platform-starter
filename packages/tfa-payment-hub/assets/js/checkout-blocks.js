(function () {
    'use strict';

    if (!window.wc || !window.wc.wcBlocksRegistry || !window.wc.wcSettings || !window.wp || !window.wp.element) {
        return;
    }

    var settings = window.wc.wcSettings.getSetting('universal_payments_gateway_data', {});
    var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
    var decodeEntities = window.wp.htmlEntities.decodeEntities;
    var el = window.wp.element.createElement;
    var useEffect = window.wp.element.useEffect;
    var useState = window.wp.element.useState;
    var __ = window.wp.i18n.__;

    function firstKey(object) {
        var keys = object ? Object.keys(object) : [];
        return keys.length ? keys[0] : '';
    }

    function PaymentContent(props) {
        var groups = settings.groups || {};
        var initialGroup = settings.default_group && groups[settings.default_group] ? settings.default_group : firstKey(groups);
        var initialProvider = settings.default_provider && groups[initialGroup] && groups[initialGroup][settings.default_provider]
            ? settings.default_provider
            : firstKey(groups[initialGroup]);
        var groupState = useState(initialGroup);
        var group = groupState[0];
        var setGroup = groupState[1];
        var providerState = useState(initialProvider);
        var provider = providerState[0];
        var setProvider = providerState[1];
        var consentState = useState(false);
        var consent = consentState[0];
        var setConsent = consentState[1];
        var currentProviders = groups[group] || {};
        var selectedProvider = currentProviders[provider] || null;
        var responseTypes = props.emitResponse && props.emitResponse.responseTypes ? props.emitResponse.responseTypes : { SUCCESS: 'success', ERROR: 'error' };

        useEffect(function () {
            if (!currentProviders[provider]) {
                setProvider(firstKey(currentProviders));
            }
        }, [group]);

        useEffect(function () {
            if (!props.eventRegistration || !props.eventRegistration.onPaymentSetup) {
                return undefined;
            }
            var unsubscribe = props.eventRegistration.onPaymentSetup(function () {
                if (!provider || !selectedProvider) {
                    return {
                        type: responseTypes.ERROR,
                        message: __('Please choose an available payment option.', 'tfa-payment-hub')
                    };
                }
                if (selectedProvider.group === 'direct_debit' && !consent) {
                    return {
                        type: responseTypes.ERROR,
                        message: __('Please confirm the Direct Debit mandate statement.', 'tfa-payment-hub')
                    };
                }
                return {
                    type: responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            tfa_payment_group: group,
                            tfa_payment_provider: provider,
                            tfa_direct_debit_consent: consent ? 'yes' : 'no'
                        }
                    }
                };
            });
            return unsubscribe;
        }, [group, provider, consent, selectedProvider, props.eventRegistration, responseTypes]);

        var groupButtons = Object.keys(groups).map(function (groupId) {
            return el(
                'button',
                {
                    type: 'button',
                    key: groupId,
                    className: 'tfa-block-group-button' + (groupId === group ? ' is-active' : ''),
                    onClick: function () {
                        setGroup(groupId);
                        setProvider(firstKey(groups[groupId]));
                        setConsent(false);
                    }
                },
                decodeEntities((settings.group_labels || {})[groupId] || groupId)
            );
        });

        var providerOptions = Object.keys(currentProviders).map(function (providerId) {
            var item = currentProviders[providerId];
            return el(
                'label',
                { className: 'tfa-block-provider' + (providerId === provider ? ' is-active' : ''), key: providerId },
                el('input', {
                    type: 'radio',
                    name: 'tfa-block-payment-provider',
                    value: providerId,
                    checked: providerId === provider,
                    onChange: function () { setProvider(providerId); }
                }),
                item.logo_url ? el(
                    'span',
                    { className: 'tfa-block-provider-logo-wrap' },
                    el('img', { className: 'tfa-block-provider-logo', src: item.logo_url, alt: decodeEntities(item.logo_alt || item.title || '') })
                ) : null,
                el(
                    'span',
                    { className: 'tfa-block-provider-copy' },
                    el('strong', null, decodeEntities(item.title || item.name || providerId)),
                    item.description ? el('small', null, decodeEntities(item.description)) : null,
                    item.flow === 'stripe_checkout' ? el('em', null, __('Apple Pay or Google Pay is shown on Stripe Checkout when available.', 'tfa-payment-hub')) : null,
                    item.flow === 'manual' ? el('em', null, __('The order is held while the mandate is arranged.', 'tfa-payment-hub')) : null
                ),
                item.icon_url ? el(
                    'span',
                    { className: 'tfa-block-provider-icon-wrap' },
                    el('img', { className: 'tfa-block-provider-icon', src: item.icon_url, alt: decodeEntities(item.icon_alt || '') })
                ) : null
            );
        });

        return el(
            'div',
            { className: 'tfa-payment-hub-block' },
            settings.description ? el('p', { className: 'tfa-block-description' }, decodeEntities(settings.description)) : null,
            Object.keys(groups).length > 1 ? el('div', { className: 'tfa-block-groups' }, groupButtons) : null,
            el('div', { className: 'tfa-block-providers' }, providerOptions),
            group === 'direct_debit' ? el(
                'label',
                { className: 'tfa-block-consent' },
                el('input', {
                    type: 'checkbox',
                    checked: consent,
                    onChange: function (event) { setConsent(event.target.checked); }
                }),
                el('span', null, decodeEntities(settings.direct_debit_text || ''))
            ) : null
        );
    }

    function Label() {
        return el('span', null, decodeEntities(settings.title || __('Secure payment', 'tfa-payment-hub')));
    }

    registerPaymentMethod({
        name: 'universal_payments_gateway',
        label: el(Label, null),
        content: el(PaymentContent, null),
        edit: el(PaymentContent, null),
        canMakePayment: function () { return settings.available !== false; },
        ariaLabel: decodeEntities(settings.title || __('Secure payment', 'tfa-payment-hub')),
        supports: {
            features: settings.supports || ['products']
        }
    });
}());
