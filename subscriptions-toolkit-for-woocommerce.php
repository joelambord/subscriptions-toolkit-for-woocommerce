<?php
/**
 * Plugin Name: Subscriptions Toolkit for WooCommerce
 * Description: A toolkit of enhancements for WooCommerce Subscriptions. First module: a "Subscription Trial" coupon type that grants a configurable free trial (days / weeks / months / years) on subscription products. Compatible with Smart Coupons for WooCommerce Pro (WebToffee). Further modules (retention, dunning, ...) will be added over time.
 * Version:     1.1.2
 * Author:      Joël Ambord
 * Author URI:  https://profiles.wordpress.org/joelambord/
 * Text Domain: wc-subs-toolkit
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.2
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SubscriptionsToolkitForWC
 */

defined( 'ABSPATH' ) || exit;

define( 'WCST_VERSION', '1.1.2' );
define( 'WCST_FILE', __FILE__ );
define( 'WCST_PATH', plugin_dir_path( __FILE__ ) );
define( 'WCST_URL',  plugin_dir_url( __FILE__ ) );

/**
 * DB-facing identifiers for the trial coupon module.
 *
 * These values are INTENTIONALLY unchanged from the legacy plugin
 * "woo-subscription-trial-coupon" (and its first replacement release):
 * the coupon type slug and the two meta keys must stay identical so
 * existing coupons in the database keep working after switching plugins
 * or renaming this one.
 */
define( 'WCST_TRIAL_COUPON_TYPE', 'subscription_trial' );
define( 'WCST_TRIAL_META_LENGTH', '_wcsc_coupon_trial_length' );
define( 'WCST_TRIAL_META_PERIOD', '_wcsc_coupon_trial_period' );

require_once WCST_PATH . 'includes/trial-coupons/class-trial-coupons-admin.php';
require_once WCST_PATH . 'includes/trial-coupons/class-trial-coupons-cart.php';

add_action( 'plugins_loaded', 'wcst_bootstrap' );
function wcst_bootstrap() {
	load_plugin_textdomain( 'wc-subs-toolkit', false, dirname( plugin_basename( WCST_FILE ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Subscriptions' ) ) {
		add_action( 'admin_notices', 'wcst_missing_dependency_notice' );
		return;
	}

	// Trial Coupons module.
	( new WCST_Trial_Coupons_Admin() )->init();
	( new WCST_Trial_Coupons_Cart() )->init();

	// Future modules load here (retention, dunning, ...).
}

function wcst_missing_dependency_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Subscriptions Toolkit for WooCommerce requires WooCommerce and WooCommerce Subscriptions to be active.', 'wc-subs-toolkit' );
	echo '</p></div>';
}

/**
 * Declare compatibility with WooCommerce HPOS (Custom Order Tables)
 * and Cart / Checkout Blocks. Coupons themselves are not affected by
 * HPOS, but declaring keeps WooCommerce quiet on the plugins screen.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WCST_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WCST_FILE, true );
	}
} );
