<?php
/**
 * Cart / Checkout: applies the coupon's trial to subscription products in
 * the cart, validates that a subscription is present, and renders the
 * trial info in the cart totals.
 *
 * @package TrialCouponsWCS
 */

defined( 'ABSPATH' ) || exit;

class TCWCS_Cart {

	public function init() {
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_trial_to_subscriptions' ], 15, 1 );
		add_filter( 'woocommerce_coupon_is_valid',         [ $this, 'validate_cart_has_subscription' ], 10, 2 );
		add_filter( 'woocommerce_coupon_error',            [ $this, 'custom_error_message' ], 10, 3 );
		add_filter( 'woocommerce_cart_totals_coupon_label',[ $this, 'cart_coupon_label' ], 10, 2 );
		add_filter( 'woocommerce_coupon_discount_amount_html', [ $this, 'cart_discount_amount_html' ], 10, 2 );
	}

	/**
	 * Iterate every applied coupon; for each subscription_trial coupon,
	 * copy its trial length + period onto every subscription product in
	 * the cart. WooCommerce Subscriptions then treats those products as
	 * being on trial: total drops to 0.00 and the first renewal is
	 * scheduled after the trial ends.
	 *
	 * Called on woocommerce_before_calculate_totals — the cart's product
	 * objects are session-scoped clones, so modifying meta on them does
	 * NOT persist to the actual product post.
	 */
	public function apply_trial_to_subscriptions( $cart ) {
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}
		$applied = $cart->get_applied_coupons();
		if ( empty( $applied ) ) {
			return;
		}

		$trial_length = 0;
		$trial_period = '';

		// Later trial coupons win; typical stores only allow one anyway.
		foreach ( $applied as $code ) {
			$coupon = new WC_Coupon( $code );
			if ( ! $coupon->is_type( TCWCS_COUPON_TYPE ) ) {
				continue;
			}
			$length = (int) $coupon->get_meta( TCWCS_META_TRIAL_LENGTH );
			$period = (string) $coupon->get_meta( TCWCS_META_TRIAL_PERIOD );
			if ( $length > 0 && '' !== $period ) {
				$trial_length = $length;
				$trial_period = $period;
			}
		}

		if ( $trial_length <= 0 ) {
			return;
		}

		foreach ( $cart->cart_contents as $cart_item ) {
			if ( empty( $cart_item['data'] ) ) {
				continue;
			}
			$product = $cart_item['data'];
			if ( ! $this->is_subscription_product( $product ) ) {
				continue;
			}
			$product->update_meta_data( '_subscription_trial_length', $trial_length );
			$product->update_meta_data( '_subscription_trial_period', $trial_period );
		}
	}

	/**
	 * Reject the coupon at apply-time if the cart contains no subscription.
	 */
	public function validate_cart_has_subscription( $valid, $coupon ) {
		if ( ! $coupon instanceof WC_Coupon || ! $coupon->is_type( TCWCS_COUPON_TYPE ) ) {
			return $valid;
		}
		if ( ! WC()->cart ) {
			return $valid;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! empty( $item['data'] ) && $this->is_subscription_product( $item['data'] ) ) {
				return $valid;
			}
		}

		$coupon->error_code = 'tcwcs_no_subscription_in_cart';
		return false;
	}

	public function custom_error_message( $err, $err_code, $coupon ) {
		if ( isset( $coupon->error_code ) && 'tcwcs_no_subscription_in_cart' === $coupon->error_code ) {
			return __( 'This coupon can only be applied when the cart contains a subscription product.', 'trial-coupons-wcs' );
		}
		return $err;
	}

	/**
	 * Label shown in the cart / checkout totals for the applied coupon.
	 */
	public function cart_coupon_label( $label, $coupon ) {
		if ( $coupon instanceof WC_Coupon && $coupon->is_type( TCWCS_COUPON_TYPE ) ) {
			return esc_html__( 'Free trial', 'trial-coupons-wcs' ) . ' (' . esc_html( strtoupper( $coupon->get_code() ) ) . ')';
		}
		return $label;
	}

	/**
	 * Show "10 days" instead of a monetary discount next to the coupon
	 * in the cart totals — the actual price change happens because the
	 * subscription trial makes the initial total 0.00.
	 */
	public function cart_discount_amount_html( $discount_html, $coupon ) {
		if ( ! $coupon instanceof WC_Coupon || ! $coupon->is_type( TCWCS_COUPON_TYPE ) ) {
			return $discount_html;
		}
		$length = (int) $coupon->get_meta( TCWCS_META_TRIAL_LENGTH );
		$period = (string) $coupon->get_meta( TCWCS_META_TRIAL_PERIOD );

		if ( $length <= 0 || '' === $period ) {
			return esc_html__( 'Free trial period', 'trial-coupons-wcs' );
		}

		return esc_html( $this->format_trial_period( $length, $period ) );
	}

	private function is_subscription_product( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}
		if ( function_exists( 'WC_Subscriptions_Product' ) || class_exists( 'WC_Subscriptions_Product' ) ) {
			if ( WC_Subscriptions_Product::is_subscription( $product ) ) {
				return true;
			}
		}
		return $product->is_type( 'subscription' ) || $product->is_type( 'subscription_variation' );
	}

	/**
	 * Translated, pluralized "10 days" / "1 month" using WooCommerce
	 * Subscriptions' own translation catalog so the output matches the
	 * rest of the checkout UI (including German).
	 */
	private function format_trial_period( $length, $period ) {
		if ( function_exists( 'wcs_get_subscription_period_strings' ) ) {
			$strings = wcs_get_subscription_period_strings( $length, $period );
			if ( ! empty( $strings ) ) {
				return $strings;
			}
		}

		switch ( $period ) {
			case 'day':
				/* translators: %d: number of days */
				return sprintf( _n( '%d day', '%d days', $length, 'trial-coupons-wcs' ), $length );
			case 'week':
				/* translators: %d: number of weeks */
				return sprintf( _n( '%d week', '%d weeks', $length, 'trial-coupons-wcs' ), $length );
			case 'month':
				/* translators: %d: number of months */
				return sprintf( _n( '%d month', '%d months', $length, 'trial-coupons-wcs' ), $length );
			case 'year':
				/* translators: %d: number of years */
				return sprintf( _n( '%d year', '%d years', $length, 'trial-coupons-wcs' ), $length );
			default:
				return $length . ' ' . $period;
		}
	}
}
