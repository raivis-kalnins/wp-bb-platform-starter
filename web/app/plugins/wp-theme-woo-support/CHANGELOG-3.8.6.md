# WP Theme Woo Support 3.8.6 - retail and trade separation

- Explicit Retail + B2B, registered-only, approved-B2B-only and selected-role purchase modes.
- Preserve legacy private-store intent until the merchant saves a new mode.
- Approved trade role selection, per-role discounts, existing per-user override, variation/parent fixed trade prices and quantity tiers.
- Separate retail/trade variation-price cache context; use raw price baselines to prevent repeated cart discounts.
- Support normal retail variation/sale prices and general discount rules; trade stacking is opt-in.
- Sale-price basis, trade coupon policy, product/variation minimums and optional case-pack enforcement.
- Restrict only the configured trade-only payment gateway IDs. Do not automatically remove standard bacs, cod or cheque from retail.
- Classic and Store API cart validation hooks; retain earlier stock/price/purchasability restrictions.
- Capability/nonce checks on trade profile changes and product/variation cache invalidation.

Choose WooCommerce > B2B / Wholesale > Store mode > Retail + B2B (everyone can buy), then save. Installing alone does not remove an existing private-store restriction. WooCommerce guest checkout and enabled payment methods still apply.

Credit limit, payment terms and tax-exempt fields are informational metadata, not an invoice/credit-control/VAT validation engine. Modes control purchasing; this is not a security boundary for confidential catalogue data. Do not run Demo Lab on a live store.
