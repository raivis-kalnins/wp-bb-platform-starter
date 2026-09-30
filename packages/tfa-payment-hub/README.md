# TFA Payment Hub for WooCommerce

**Version:** 2.3.0  
**Plugin folder:** `wp-payment-hub`  
**Main file:** `universal-payment-gateway-for-woocommerce.php`  
**WooCommerce gateway ID:** `universal_payments_gateway`

TFA Payment Hub adds one payment method to WooCommerce. Inside that payment method, customers can choose an enabled bank, Direct Debit method, wallet, or hosted payment provider. The same provider selector is available in the classic checkout and the WooCommerce Checkout Block.

## What changed in 2.3.0

- Added a WordPress Media Library **Bank / provider logo** field to every bank, Direct Debit method, custom Direct Debit profile and wallet.
- Added a separate **Card / payment icon** field to every payment method.
- Displayed the selected images in both Classic Checkout and the WooCommerce Checkout Block.
- Fixed provider matching so methods such as Worldpay Direct Debit no longer lose their configuration fields because their IDs begin with a shorter bank ID.
- Preserved the two-level settings navigation and one-gateway checkout design.

## What changed in 2.2.2

- Each top-level category displays only its own second-level tabs.
- General shows Checkout, Payment choices, Processing, and Logs & security only.
- Banks & Cards, Direct Debit, and Wallets each show only the methods in that category.
- Added stronger hidden-state and accessibility handling so unrelated tab rows cannot appear together.

## What changed in 2.2.1

- Restyled second-level settings tabs to use the native WordPress tab appearance.
- Removed the boxed pill treatment around Checkout, provider, Direct Debit, and wallet tabs.
- Kept horizontal scrolling for long method lists on smaller screens.
- Preserved the existing two-level navigation and saved-tab behaviour.

## What changed in 2.2.0

- Rebuilt the settings screen with two levels of navigation.
- Added category tabs for General, Banks & Cards, Direct Debit, and Wallets.
- Added WooCommerce Cart and Checkout Block support for the single gateway and its internal provider choices.
- Added a second tab row so only one settings area or provider is shown at a time.
- Added a real Stripe Checkout integration for Apple Pay and Google Pay.
- Added Stripe session verification, signed webhook verification, amount checks, currency checks, and order matching.
- Removed technical plugin-conflict notices from the visible settings page.
- Kept the existing WooCommerce gateway ID and saved settings structure.

## Installation or upgrade

1. Back up the WordPress files and database.
2. Deactivate the previous private payment gateway plugin.
3. Upload the new ZIP in **Plugins > Add New > Upload Plugin**.
4. Activate **TFA Payment Hub for WooCommerce**.
5. Open **WooCommerce > Settings > Payments > TFA Payment Hub**.
6. Review every enabled provider and complete a test order before using Live mode.
7. Remove the old plugin folder only after confirming the new version works correctly.

The installed folder should be:

```text
/wp-content/plugins/wp-payment-hub/
```

## Settings navigation

### General

- **Checkout:** enable the gateway and set its customer-facing title and description.
- **Payment choices:** enable or hide Banks & Cards, Direct Debit, and Wallets; set the default section and method.
- **Processing:** set the hosted transaction type and optional 3-D Secure request setting.
- **Logs & security:** enable WooCommerce logging while testing or troubleshooting.

### Banks & Cards

Select a bank from the second tab row. Each bank has its own enable switch, checkout label, description, test credentials, live credentials, and connection check.

### Direct Debit

The first second-level tab contains shared Direct Debit consent text, manual order status, and customer instructions. Each Direct Debit provider then has a separate tab.

No bank account number, sort code, or IBAN is collected by this plugin. Sensitive details must be entered on a bank- or provider-hosted mandate page.

### Wallets

Each wallet or hosted provider has a separate tab. **Apple Pay & Google Pay** uses Stripe Checkout and has a dedicated setup flow.

## Apple Pay and Google Pay setup

The Apple Pay and Google Pay option creates a Stripe-hosted Checkout Session. Stripe decides which wallet to display based on the customer's device, browser, saved wallet, country, currency, and Stripe account configuration.

1. Open **Wallets > Apple Pay & Google Pay**.
2. Enable the method.
3. Set the customer label and description.
4. Select **Test / sandbox**.
5. Enter a Stripe test secret key beginning with `sk_test_`.
6. In Stripe, create a webhook endpoint using:

```text
https://example.com/?wc-api=wc_gateway_tfa_payment_hub_stripe_webhook
```

7. Subscribe the webhook to:

```text
checkout.session.completed
checkout.session.async_payment_succeeded
checkout.session.async_payment_failed
```

8. Copy the Stripe signing secret beginning with `whsec_` into **Test webhook secret**.
9. Save the settings and click **Check Apple Pay & Google Pay configuration**.
10. Complete test orders on eligible Apple Pay and Google Pay devices.
11. Add the live Stripe secret key and live webhook secret only after successful testing.
12. Change the method to **Live** and test a low-value live order.

Stripe Checkout keeps card and wallet details away from WordPress. The plugin verifies the Stripe Session ID, WooCommerce order ID, amount, currency, payment status, and webhook signature before completing the order.

Stripe's official Checkout documentation states that Apple Pay and Google Pay are presented automatically when eligible. See:

- https://docs.stripe.com/payments/checkout
- https://docs.stripe.com/apple-pay
- https://docs.stripe.com/google-pay

Confirm that Stripe has approved the store's business model and products before enabling live wallet payments.

## Hosted bank or payment provider setup

For each hosted provider:

1. Open its provider tab.
2. Enable it.
3. Set the customer-facing label and description.
4. Select Test mode.
5. Enter the provider's test gateway URL, merchant/store ID, and shared secret.
6. Choose the hash encoding required by the provider.
7. Save and run the configuration check.
8. Complete the provider's sandbox or certification tests.
9. Enter live credentials and switch to Live mode only after approval.

The generic hosted bank flow follows the original IPG Connect-style request. Do not assume every bank uses the same field names or signing rules. Enable a bank only when its supplied hosted endpoint supports this flow.

## Direct Debit

### Manual Bacs

Manual Bacs places the order on hold or leaves it pending. The customer receives the configured mandate instructions. The actual mandate and collection are completed through the sponsor bank, bureau, or approved Direct Debit provider.

### Hosted Direct Debit

Hosted Direct Debit redirects the customer to an approved provider-controlled mandate page. Configure the test and live endpoint details in that provider's tab.

Use consent wording and customer communications approved by the sponsor bank or Direct Debit provider.

## Callback URLs

Generic hosted providers use:

```text
https://example.com/?wc-api=wc_gateway_tfa_payment_hub_response
https://example.com/?wc-api=wc_gateway_tfa_payment_hub_notify
```

Stripe wallets use:

```text
https://example.com/?wc-api=wc_gateway_tfa_payment_hub_stripe_return
https://example.com/?wc-api=wc_gateway_tfa_payment_hub_stripe_webhook
```

Backward-compatible callback routes from previous private versions remain registered.

## Testing checklist

- Gateway is enabled.
- Required payment category is enabled.
- Provider is enabled.
- Test mode is selected.
- Test credentials are complete.
- Configuration check passes.
- Successful payment completes the correct order.
- Failed or cancelled payment does not complete the order.
- Amount and currency match the WooCommerce order.
- Direct Debit consent is required where applicable.
- Customer confirmation and email wording are correct.
- WooCommerce logs contain no secrets.
- Live mode remains disabled until provider approval and testing are complete.

## Logging

Enable **General > Logs & security > Debug log**. Logs are available under:

```text
WooCommerce > Status > Logs
```

Select a log beginning with `wp-payment-hub`. Disable logging after troubleshooting unless it is operationally required.

## Troubleshooting

### A provider is not shown at checkout

Confirm the payment category is enabled, the provider itself is enabled, and all credentials required for the active mode are present.

### Apple Pay or Google Pay is not displayed on Stripe Checkout

Check that the Stripe key is valid, wallets are enabled in Stripe, the browser and device support the wallet, the customer has an eligible saved card, HTTPS is active, and the currency and country are supported. Stripe may display only the wallet available on that device.

### Stripe order stays pending

Check the webhook endpoint and signing secret. Review Stripe's event delivery history and the WooCommerce log. The return handler also retrieves and verifies the Checkout Session, but a working webhook is recommended for reliable order updates.

### Hosted bank callback fails

Check the merchant/store ID, shared secret, hash encoding, amount, currency, callback URLs, and the exact request/response specification supplied by the provider.

## Security

- Never store card details, wallet tokens, account numbers, sort codes, or IBANs in WordPress settings or order notes.
- Use HTTPS for checkout and callbacks.
- Use different test and live credentials.
- Protect administrator accounts with strong authentication.
- Restrict access to WooCommerce logs.
- Rotate exposed credentials immediately.
- Test all payment changes on staging before production.


## Provider logos and payment icons

Every bank, Direct Debit method, custom Direct Debit profile and wallet has two optional image controls:

- **Bank / provider logo** — a wider brand logo shown beside the method name.
- **Card / payment icon** — a compact card, mandate or wallet icon shown at the right side of the method.

Open **WooCommerce > Settings > Payments > TFA Payment Hub**, choose the payment category and provider, then use **Choose image**. Images are selected from the WordPress Media Library and appear in both Classic Checkout and the WooCommerce Checkout Block. Transparent PNG or WebP files are recommended. SVG uploads depend on the site's permitted file types.

Version 2.3.0 also fixes hidden setup fields for Direct Debit profiles whose IDs begin with a bank provider ID, including Worldpay Direct Debit, NatWest Direct Debit, Barclays Direct Debit, Lloyds Direct Debit, HSBC Direct Debit, Swedbank Direct Debit, SEB Direct Debit, Luminor Direct Debit and Revolut Direct Debit.
