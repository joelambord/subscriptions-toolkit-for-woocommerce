=== Subscriptions Toolkit for WooCommerce ===
Contributors: joelambord
Tags: woocommerce, subscriptions, coupons, free trial, retention
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.2
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A growing toolkit of enhancements for WooCommerce Subscriptions. Ships with a "Subscription Trial" coupon type today; retention and other subscription-flow improvements are planned.

== Description ==

Subscriptions Toolkit for WooCommerce is an umbrella plugin for WooCommerce Subscriptions add-ons. It ships as a set of self-contained modules that can grow over time. The first module is a **Subscription Trial** coupon: applying it drops the initial cart total to 0.00 and pushes the first recurring payment out by the configured trial length. The customer sees "15 days" (for example) next to the coupon in the cart and "First renewal: Oct 9, 2026" in the totals.

Planned modules:

* **Retention** — win-back offers, cancellation-flow discounts, pause-instead-of-cancel.
* **Dunning** — smarter retry & customer messaging for failed renewals.
* More to come.

== Compatibility ==

Payment gateway: The plugin only changes the *initial* recurring total to 0.00 via the standard WooCommerce Subscriptions trial mechanism (`_subscription_trial_length` / `_subscription_trial_period` on the cart item). It does not touch the tokenization, SCA, or pre-authorization flow of the payment gateway. That means gateways that place a temporary hold ("security authorization" / "zero-amount authorization" / "verification charge") on the customer's card at sign-up — such as Stripe, Payrexx, Braintree, Mollie, and other well-behaved subscription gateways — continue to work exactly as configured: the card is still verified and stored during checkout, so the first recurring charge after the trial can be processed without further customer action, and any pre-auth hold defined by the gateway is still placed. The trial only affects the amount collected today, not whether the payment method is validated.

Other coupon extensions: Compatible with many other plugins like Smart Coupons for WooCommerce (Pro) by WebToffee. The new coupon type registers via the standard `woocommerce_coupon_discount_types` filter that WebToffee's UI reads.

Cache plugins: Compatible with WP Rocket, W3 Total Cache, LiteSpeed Cache, WP Super Cache and other page/object cache plugins. All plugin logic runs on the cart and checkout pages, which cache plugins already exclude from full-page HTML caching by default (WooCommerce sets `DONOTCACHEPAGE`). No additional cache-exclusion rules or nonces need to be configured. Object caches (Redis / Memcached) are respected — the plugin uses standard WordPress metadata APIs.

== Requirements ==

* WooCommerce 8.0+
* WooCommerce Subscriptions 5.0+

== How the Trial Coupon module works ==

1. Admin creates a coupon and picks discount type "Subscription Trial".
2. Admin enters trial length + period (e.g. 15, days).
3. Customer applies the coupon in the cart while at least one subscription product is present.
4. The plugin hooks WooCommerce Subscriptions' trial getters (`woocommerce_subscriptions_product_trial_length` / `_period`), reading the coupon's trial values directly from post meta. The trial is therefore returned wherever WCS asks for it and survives checkout retries after cancelled or failed payments.
5. WooCommerce Subscriptions recalculates: the initial cart total becomes 0.00 and the first renewal is pushed out by the trial length.

== Changelog ==

= 1.1.4 =
* Initial public release.
* Trial Coupons module: registers a "Subscription Trial" coupon type. When applied, the initial cart total drops to 0.00 and the first recurring payment is pushed out by the configured trial length. Compatible with Smart Coupons for WooCommerce (Pro) by WebToffee — the type registers via the standard `woocommerce_coupon_discount_types` filter.
* Robust against gateway retry flows: the trial is re-applied via WCS's own trial getters (`woocommerce_subscriptions_product_trial_length` / `_period`) reading straight from post meta, so a cancelled or failed payment followed by a checkout retry keeps the trial intact.
* German translations bundled (de_DE, de_CH, de_AT). Rewrites the ungrammatical WooCommerce Subscriptions German phrase "mit ein 15-Tage kostenlose Testphase" into the correct dative form "mit 15 Tagen kostenloser Testphase".
* Optional debug logging via WooCommerce's logger (source `wcst-trial-coupons`), enabled by `define( 'WCST_DEBUG', true );` in wp-config.php or automatically when `WP_DEBUG` is on.
