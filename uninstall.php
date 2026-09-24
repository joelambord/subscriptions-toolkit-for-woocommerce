<?php
/**
 * Runs when the plugin is deleted via the WP admin.
 *
 * We intentionally do NOT remove the two coupon meta keys
 * (_wcsc_coupon_trial_length / _wcsc_coupon_trial_period). They are shared
 * with the legacy plugin and users may want to re-install either one without
 * losing existing coupon configuration.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
