/**
 * Show/hide the trial length + period field and the standard "coupon amount"
 * field depending on the selected discount type. Also moves the trial field
 * into a more sensible position within the meta box.
 */
jQuery( function ( $ ) {
	'use strict';

	var $trialField  = $( '.subscription_coupon_trial_length_field' );
	var $amountField = $( '.coupon_amount_field' );
	var $typeSelect  = $( '#discount_type' );

	if ( ! $trialField.length || ! $typeSelect.length ) {
		return;
	}

	// Move the trial field just above "Allow free shipping" if present.
	var $shipping = $( '#general_coupon_data .free_shipping_field' );
	if ( $shipping.length ) {
		$trialField.insertBefore( $shipping );
	}

	function toggle() {
		var val = $typeSelect.val();
		if ( val === 'subscription_trial' ) {
			$trialField.show();
			$amountField.hide();
		} else if ( val === 'sign_up_fee' || val === 'sign_up_fee_percent' ) {
			$trialField.show();
			$amountField.show();
		} else {
			$trialField.hide();
			$amountField.show();
		}
	}

	$typeSelect.on( 'change', toggle );
	toggle();
} );
