<?php
/**
 * Functions.
 *
 * @package wp-travel-paypal/inc/
 */

add_filter( 'wp_travel_payment_gateway_lists', 'wp_travel_payfast_gateway' );
/**
 * Add the Gateway.
 *
 * @param array $gateways Gateways List.
 */
function wp_travel_payfast_gateway( $gateways ) {
	if ( ! $gateways ) {
		return;
	}
	$gateways['payfast'] = __( 'PayFast Checkout', 'wp-travel-pro' );
	return $gateways;
}

add_filter( 'wp_travel_premium_addons_list', 'wp_travel_payfast_addons' );
/**
 * Adds to addon List.
 *
 * @param array $addons Addons List.
 * @return array
 */
function wp_travel_payfast_addons( $addons ) {
	$addons['payfast'] = __( 'PayFast Checkout', 'wp-travel-pro' );
	return $addons;
}

add_action( 'wp_travel_payfast_verify_itn', 'wp_travel_payfast_verify_itn_cb' );
/**
 * Payfast ITN Verification Callback.
 *
 * @return void
 */
function wp_travel_payfast_verify_itn_cb() {
	header( 'HTTP/1.0 200 OK' );
	flush();

	include_once plugin_dir_path( __FILE__ ) . 'payfast/payfast-itn-listener.php';
	global $wt_cart;
	$pf_payment         = new WP_Travel_PayFast_Payment();
	$pf_host            = $pf_payment->get_payment_host(); // Gets Host.
	$wp_travel_settings = wp_travel_get_settings();
	$pf_pass_phrase     = ! empty( $wp_travel_settings['wp_travel_payfast_settings']['passphrase'] ) ? $wp_travel_settings['wp_travel_payfast_settings']['passphrase'] : null;

	$pf_listener = new Payfast_ITN_Listener( $pf_host, $pf_pass_phrase );

	// if ( $pf_listener->has_error() === false ) {
		if ( $pf_listener->get_status() == 'COMPLETE' ) {
			$pfdata = $pf_listener->get_data();
			// Update booking status and Payment args.
			$booking_id = isset( $_POST['custom_int1'] ) ? (int) $_POST['custom_int1'] : 0;
			update_post_meta( $booking_id, 'wp_travel_booking_status', 'booked' );
			$payment_id = get_post_meta( $booking_id, 'wp_travel_payment_id', true );

			$payment_ids = array();
			// get previous payment ids.
			$payfast_args = get_post_meta( $payment_id, '_payfast_args', true );

			// Update order as required
			if ( '' !== $payfast_args ) { // Partial Payment.
				if ( isset( $payfast_args['pf_payment_id'] ) && (int) $_POST['pf_payment_id'] !== (int) $payfast_args['pf_payment_id'] ) :

					if ( is_string( $payment_id ) && '' !== $payment_id ) {
						$payment_ids[] = $payment_id;
					} else {
						$payment_ids = $payment_id;
					}

					// insert new payment id and update meta.
					$title          = 'Payment - #' . $booking_id;
					$post_array     = array(
						'post_title'   => $title,
						'post_content' => '',
						'post_status'  => 'publish',
						'post_slug'    => uniqid(),
						'post_type'    => 'wp-travel-payment',
					);
					$new_payment_id = wp_insert_post( $post_array );
					$payment_ids[]  = $new_payment_id;
					update_post_meta( $booking_id, 'wp_travel_payment_id', $payment_ids );

					$payment_method = 'payfast';
					$amount         = $pfdata['amount_gross'];
					$detail         = $pfdata;

					update_post_meta( $new_payment_id, 'wp_travel_payment_gateway', $payment_method );

					update_post_meta( $new_payment_id, 'wp_travel_payment_amount', $amount );
					update_post_meta( $new_payment_id, 'wp_travel_payment_status', 'paid' );
					update_post_meta( $new_payment_id, 'wp_travel_payment_mode', 'partial' );

					wp_travel_update_payment_status( $booking_id, $amount, 'paid', $detail, sprintf( '_%s_args', $payment_method ), $new_payment_id );
				endif;
			} else { // New Payment.
				update_post_meta( $payment_id, '_payfast_args', $pf_listener->get_data() );
				update_post_meta( $payment_id, 'wp_travel_payment_status', 'paid' );
				update_post_meta( $payment_id, 'wp_travel_payment_amount', $pfdata['amount_gross'] );
				do_action( 'wp_travel_after_successful_payment', $booking_id );
			}
		}
	// }
}

add_action( 'init', 'wp_travel_payfast_listen_itn' );
/**
 * Listen for a $_GET request from our PayPal IPN.
 * This would also do the "set-up" for an "alternate purchase verification"
 */
function wp_travel_payfast_listen_itn() {
	if ( isset( $_GET['payfast_listener'] )
		&& $_GET['payfast_listener'] == 'ITN'
		|| isset( $_GET['test'] )
		&& $_GET['test'] == true ) {
		do_action( 'wp_travel_payfast_verify_itn' );
	}
}
