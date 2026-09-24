=== Subscriptions Toolkit for WooCommerce ===
Contributors: joelambord
Tags: woocommerce, subscriptions, coupons, free trial, retention
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 10.2
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A growing toolkit of enhancements for WooCommerce Subscriptions. Ships with a "Subscription Trial" coupon type today; retention and other subscription-flow improvements are planned.

== Description ==

Subscriptions Toolkit for WooCommerce is an umbrella plugin for WooCommerce Subscriptions add-ons. It ships as a set of self-contained modules that can grow over time. The first module is a **Subscription Trial** coupon: applying it drops the initial cart total to 0.00 and pushes the first recurring payment out by the configured trial length. The customer sees "15 days" (for example) next to the coupon in the cart and "First renewal: Oct 9, 2026" in the totals.

Planned modules:

* **Retention** — win-back offers, cancellation-flow discounts, pause-instead-of-cancel.
* **Dunning** — smarter retry & customer messaging for failed renewals.
* More to come.

The trial coupon module was originally released as the standalone plugin *Trial Coupons for WooCommerce Subscriptions*, and is a drop-in replacement for the abandoned *Free Trial Coupon for Woocommerce Subscriptions* (`woo-subscription-trial-coupon`). The coupon type slug (`subscription_trial`) and the two meta keys (`_wcsc_coupon_trial_length`, `_wcsc_coupon_trial_period`) are intentionally identical to the legacy plugin, so existing coupons in the database keep working across all three iterations.

== Compatibility ==

Payment gateway: The plugin only changes the *initial* recurring total to 0.00 via the standard WooCommerce Subscriptions trial mechanism (`_subscription_trial_length` / `_subscription_trial_period` on the cart item). It does not touch the tokenization, SCA, or pre-authorization flow of the payment gateway. That means gateways that place a temporary hold ("security authorization" / "zero-amount authorization" / "verification charge") on the customer's card at sign-up — such as Stripe, Payrexx, Braintree, Mollie, and other well-behaved subscription gateways — continue to work exactly as configured: the card is still verified and stored during checkout, so the first recurring charge after the trial can be processed without further customer action, and any pre-auth hold defined by the gateway is still placed. The trial only affects the amount collected today, not whether the payment method is validated.

Other coupon extensions: Compatible with many other plugins like Smart Coupons for WooCommerce (Pro) by WebToffee. The new coupon type registers via the standard `woocommerce_coupon_discount_types` filter that WebToffee's UI reads.

== Requirements ==

* WooCommerce 8.0+
* WooCommerce Subscriptions 5.0+

== How the Trial Coupon module works ==

1. Admin creates a coupon and picks discount type "Subscription Trial".
2. Admin enters trial length + period (e.g. 15, days).
3. Customer applies the coupon in the cart while at least one subscription product is present.
4. The plugin writes `_subscription_trial_length` / `_subscription_trial_period` onto each subscription cart item (session-only, not on the actual product post) — both during `woocommerce_before_calculate_totals` and during `woocommerce_get_cart_item_from_session`, so the trial survives checkout retries after failed payments.
5. WooCommerce Subscriptions recalculates: initial total becomes 0.00 and the renewal date is pushed out by the trial length.

== Changelog ==

= 1.1.2 =
* Fix: the trial-getter filter added in 1.1.1 was still silently skipped in the checkout-retry flow after a failed / cancelled payment because of two timing bugs. The per-request memoization cached the empty result of an early call (before session was hydrated) for the entire request, and the "product is in cart" guard returned false whenever WCS asked for the trial before cart contents were populated. Both are fixed: an empty lookup is never cached, applied coupon codes are read from `WC()->cart` first and fall back to `WC()->session`, and the in-cart guard is removed — the trial coupon can only be applied when a subscription is in the cart in the first place, so the coupon's presence in the session is enough of a signal.
* Filter priority raised to 999 so we have the last word after any WCS-internal filter that might otherwise reset the trial back to 0.
* Optional debug logging via WooCommerce's logger (source `wcst-trial-coupons`), enabled by `define( 'WCST_DEBUG', true );` in wp-config.php or automatically when `WP_DEBUG` is on. Logs each trial-length override so it is obvious from *WooCommerce → Status → Logs* whether the filter is firing.

= 1.1.1 =
* Definitive fix for the "trial gone after failed / cancelled payment" case. The previous meta-injection approach was still bypassed on some checkout-retry code paths where WCS re-reads product data from the database. The plugin now hooks WCS's own trial getters (`woocommerce_subscriptions_product_trial_length` and `_period`) directly: whenever WCS asks the product for its trial, it receives the coupon's value — regardless of the product's own meta state, and without any window where the trial could be lost. The previous meta-injection hooks stay in place as a secondary defense for third-party code that reads the product meta directly.

= 1.1.0 =
* Renamed to **Subscriptions Toolkit for WooCommerce** — the plugin is now an umbrella for multiple WooCommerce Subscriptions modules. First module is Trial Coupons (previously the whole plugin). Retention and further modules will follow.
* Refactored into a modular directory layout (`includes/trial-coupons/`).
* Text domain changed to `wc-subs-toolkit`. German translations (de_DE, de_CH, de_AT) bundled under the new domain.
* DB-facing identifiers unchanged: coupon type slug `subscription_trial`, meta keys `_wcsc_coupon_trial_length` / `_wcsc_coupon_trial_period`. Existing coupons keep working without migration.

= 1.0.2 =
* Fix: trial was lost from the checkout after a failed or cancelled payment. The applied coupon still appeared in the totals but the subscription was recalculated at full price and the renewal date reverted to today + one period. The trial is now re-injected during cart session rehydration (`woocommerce_get_cart_item_from_session`) and a one-shot totals recalculation is triggered after the cart is loaded, so the checkout retry shows 0.00 today and the correct pushed-out renewal date.
* Trial application priority lowered to 5 on `woocommerce_before_calculate_totals` so it runs before WCS's own price filters, not after.
* Coupon detection now falls back to reading the trial meta directly when the discount type check fails — some WCS code paths hydrate coupons without their discount_type initialised.

= 1.0.1 =
* German translation bundled (de_DE, de_CH, de_AT) so "Free trial", coupon labels and error messages are localized out of the box.
* Fix: cart totals now always show the number ("15 Tage" / "15 days") instead of just the unit — no longer relies on the WooCommerce Subscriptions catalog whose German plural drops the `%s` placeholder.
* Fix: rewrites the ungrammatical German phrase generated by WooCommerce Subscriptions ("mit ein 15-Tage kostenlose Testphase") into the correct dative form ("mit 15 Tagen kostenloser Testphase") on subscription product price strings.

= 1.0.0 =
* Initial release as *Trial Coupons for WooCommerce Subscriptions*.
