<?php
/**
 * Plugin Name: Trial Coupons for WooCommerce Subscriptions
 * Description: Adds a "Subscription Trial" coupon type that grants a configurable free trial (days / weeks / months / years) on WooCommerce Subscription products. Compatible with Smart Coupons for WooCommerce Pro (WebToffee). Drop-in replacement for the abandoned "Free Trial Coupon for Woocommerce Subscriptions" plugin — same coupon type slug and meta keys, so existing coupons keep working.
 * Version:     1.0.0
 * Author:      Joël Ambord
 * Author URI:  https://profiles.wordpress.org/joelambord/
 * Text Domain: trial-coupons-wcs
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.2
 *
 * @package TrialCouponsWCS
 */

defined( 'ABSPATH' ) || exit;

define( 'TCWCS_VERSION', '1.0.0' );
define( 'TCWCS_FILE', __FILE__ );
define( 'TCWCS_PATH', plugin_dir_path( __FILE__ ) );
define( 'TCWCS_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Coupon type slug and meta keys are intentionally reused from the legacy plugin
 * so that existing coupons in the database continue to function unchanged.
 */
define( 'TCWCS_COUPON_TYPE',       'subscription_trial' );
define( 'TCWCS_META_TRIAL_LENGTH', '_wcsc_coupon_trial_length' );
define( 'TCWCS_META_TRIAL_PERIOD', '_wcsc_coupon_trial_period' );

require_once TCWCS_PATH . 'includes/class-tcwcs-admin.php';
require_once TCWCS_PATH . 'includes/class-tcwcs-cart.php';

add_action( 'plugins_loaded', 'tcwcs_bootstrap' );
function tcwcs_bootstrap() {
	load_plugin_textdomain( 'trial-coupons-wcs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Subscriptions' ) ) {
		add_action( 'admin_notices', 'tcwcs_missing_dependency_notice' );
		return;
	}

	( new TCWCS_Admin() )->init();
	( new TCWCS_Cart() )->init();
}

function tcwcs_missing_dependency_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Trial Coupons for WooCommerce Subscriptions requires WooCommerce and WooCommerce Subscriptions to be active.', 'trial-coupons-wcs' );
	echo '</p></div>';
}

/**
 * Declare compatibility with WooCommerce HPOS (Custom Order Tables).
 * Coupons themselves are not affected by HPOS, but declaring keeps WC quiet.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );
