<?php
/**
 * Trial Coupons module — admin side.
 *
 * Registers the "Subscription Trial" coupon type, its meta box fields,
 * saving, and a validation exception in WooCommerce Subscriptions so
 * WCS accepts the custom type on subscription carts.
 *
 * @package SubscriptionsToolkitForWC
 */

defined( 'ABSPATH' ) || exit;

class WCST_Trial_Coupons_Admin {

	public function init() {
		add_filter( 'woocommerce_coupon_discount_types',              [ $this, 'register_discount_type' ] );
		add_action( 'woocommerce_coupon_options',                     [ $this, 'render_fields' ], 20, 2 );
		add_action( 'woocommerce_coupon_options_save',                [ $this, 'save_fields' ], 10, 2 );
		add_filter( 'woocommerce_subscriptions_validate_coupon_type', [ $this, 'bypass_wcs_validation' ], 5, 3 );
		add_action( 'admin_enqueue_scripts',                          [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Register the coupon type. Smart Coupons for WooCommerce Pro
	 * (WebToffee) reads this filter, so the new type shows up in its UI
	 * automatically.
	 */
	public function register_discount_type( $types ) {
		$types[ WCST_TRIAL_COUPON_TYPE ] = __( 'Subscription Trial', 'wc-subs-toolkit' );
		return $types;
	}

	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, [ 'shop_coupon', 'edit-shop_coupon' ], true ) ) {
			return;
		}
		wp_enqueue_script(
			'wcst-trial-coupons-admin',
			WCST_URL . 'assets/js/trial-coupons-admin.js',
			[ 'jquery' ],
			WCST_VERSION,
			true
		);
	}

	/**
	 * Render trial length + period fields inside the coupon meta box.
	 */
	public function render_fields( $coupon_id, $coupon = null ) {
		if ( ! $coupon instanceof WC_Coupon ) {
			$coupon = new WC_Coupon( $coupon_id );
		}

		$length = $coupon->get_meta( WCST_TRIAL_META_LENGTH );
		$period = $coupon->get_meta( WCST_TRIAL_META_PERIOD );
		if ( '' === $period ) {
			$period = 'day';
		}

		$periods = function_exists( 'wcs_get_available_time_periods' )
			? wcs_get_available_time_periods()
			: [
				'day'   => __( 'day', 'wc-subs-toolkit' ),
				'week'  => __( 'week', 'wc-subs-toolkit' ),
				'month' => __( 'month', 'wc-subs-toolkit' ),
				'year'  => __( 'year', 'wc-subs-toolkit' ),
			];
		?>
		<p class="form-field subscription_coupon_trial_length_field">
			<label for="<?php echo esc_attr( WCST_TRIAL_META_LENGTH ); ?>">
				<?php esc_html_e( 'Free trial', 'wc-subs-toolkit' ); ?>
			</label>
			<span class="wrap">
				<input type="number"
				       min="0"
				       step="1"
				       id="<?php echo esc_attr( WCST_TRIAL_META_LENGTH ); ?>"
				       name="<?php echo esc_attr( WCST_TRIAL_META_LENGTH ); ?>"
				       class="wc_input_subscription_trial_length"
				       style="margin-right:10px;"
				       value="<?php echo esc_attr( $length ); ?>" />

				<label for="<?php echo esc_attr( WCST_TRIAL_META_PERIOD ); ?>" style="display:none" class="wcs_hidden_label">
					<?php esc_html_e( 'Subscription trial period', 'wc-subs-toolkit' ); ?>
				</label>

				<select id="<?php echo esc_attr( WCST_TRIAL_META_PERIOD ); ?>"
				        name="<?php echo esc_attr( WCST_TRIAL_META_PERIOD ); ?>"
				        class="wc_input_subscription_trial_period last">
					<?php foreach ( $periods as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $period ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</span>
			<?php
			if ( function_exists( 'wcs_help_tip' ) ) {
				echo wcs_help_tip( esc_html__( 'Length of the free trial granted when this coupon is applied. The customer pays 0.00 at checkout and the first recurring payment is delayed by this period.', 'wc-subs-toolkit' ) );
			}
			?>
		</p>
		<?php
	}

	/**
	 * Persist the trial length + period on the coupon.
	 * Uses update_meta_data so repeated saves cannot duplicate rows.
	 */
	public function save_fields( $coupon_id, $coupon = null ) {
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! $coupon instanceof WC_Coupon ) {
			$coupon = new WC_Coupon( $coupon_id );
		}

		$length = isset( $_POST[ WCST_TRIAL_META_LENGTH ] )
			? absint( wp_unslash( $_POST[ WCST_TRIAL_META_LENGTH ] ) )
			: 0;

		$period = isset( $_POST[ WCST_TRIAL_META_PERIOD ] )
			? sanitize_key( wp_unslash( $_POST[ WCST_TRIAL_META_PERIOD ] ) )
			: 'day';

		$valid_periods = function_exists( 'wcs_get_available_time_periods' )
			? array_keys( wcs_get_available_time_periods() )
			: [ 'day', 'week', 'month', 'year' ];
		if ( ! in_array( $period, $valid_periods, true ) ) {
			$period = 'day';
		}

		$coupon->update_meta_data( WCST_TRIAL_META_LENGTH, $length );
		$coupon->update_meta_data( WCST_TRIAL_META_PERIOD, $period );
		$coupon->save();
	}

	/**
	 * Tell WooCommerce Subscriptions that this coupon type is allowed on
	 * subscription carts. Returning false short-circuits the "invalid type"
	 * error WCS would otherwise raise for unknown coupon types.
	 */
	public function bypass_wcs_validation( $is_valid_type, $coupon, $original_valid ) {
		if ( $coupon instanceof WC_Coupon && $coupon->is_type( WCST_TRIAL_COUPON_TYPE ) ) {
			return false;
		}
		return $is_valid_type;
	}
}
