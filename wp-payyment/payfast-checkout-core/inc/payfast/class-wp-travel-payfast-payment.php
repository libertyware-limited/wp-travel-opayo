<?php
/**
 * Payfast Payment Class.
 *
 * @package WP_Travel_PayFast_Checkout_Core
 */

if ( ! class_exists( 'WP_Travel_PayFast_Payment' ) ) :

	/**
	 * Payfast Payment Handler.
	 */
	class WP_Travel_PayFast_Payment {

		/**
		 * Defaults Args.
		 *
		 * @var $defaults
		 */
		private $defaults_vars;

		/**
		 * Holds Booking Id.
		 *
		 * @var $booking_id
		 */
		private $booking_id;

		/**
		 * Complete Partial Payment.
		 *
		 * @var $is_complete_partial_payment
		 */
		private $is_complete_partial_payment;

		/**
		 * Constructor.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $complete_partial_payment Is Complete Partial Payment.
		 */
		public function __construct( $booking_id = null, $complete_partial_payment = false ) {
			$this->booking_id                  = $booking_id;
			$this->is_complete_partial_payment = $complete_partial_payment;
			$this->defaults_vars               = array();
		}

		/**
		 * Get Return URL.
		 */
		public function get_return_url() {
			$trip_id = get_post_meta( (int) $this->booking_id, 'wp_travel_post_id', true );
			$url     = '';
			if ( function_exists( 'wp_travel_thankyou_page_url' ) ) {
				$url = wp_travel_thankyou_page_url( (int) $trip_id );
			}
			return $url;
		}

		/**
		 * Merchant's Details Vars.
		 */
		private function get_merchant_details() {
			$args     = array();
			$settings = $this->get_payment_settings();
			extract( $settings ); // $merchant_id, $merchant_key, $passphrase
			$return_url = $this->get_return_url();
			// Merchant's Details.
			$args['merchant_id']  = $merchant_id;
			$args['merchant_key'] = $merchant_key;
			$args['return_url']   = add_query_arg(
				array(
					'booking_id' => $this->booking_id,
					'booked'     => true,
					'status'     => 'success',
					'order_id'   => $this->booking_id,
				),
				$return_url
			);
			$args['cancel_url']   = add_query_arg(
				array(
					'booking_id' => $this->booking_id,
					'booked'     => true,
					'status'     => 'cancel',
				),
				$return_url
			);
			$args['notify_url']   = esc_url( add_query_arg( 'payfast_listener', 'ITN', home_url( 'index.php' ) ) );
			return $args;
		}

		/**
		 * Buyer's Details Vars.
		 */
		private function get_buyer_details() {
			$booking_data = get_post_meta( (int) $this->booking_id, 'order_data', true );

			$args = array();
			$key  = $this->get_form_index_key();
			if ( $key ) :
				// Buyer Details.
				$args['name_first']    = isset( $booking_data['wp_travel_fname_traveller'][ $key ][0] ) ? $booking_data['wp_travel_fname_traveller'][ $key ][0] : '';
				$args['name_last']     = isset( $booking_data['wp_travel_lname_traveller'][ $key ][0] ) ? $booking_data['wp_travel_lname_traveller'][ $key ][0] : '';
				$args['email_address'] = isset( $booking_data['wp_travel_email_traveller'][ $key ][0] ) ? $booking_data['wp_travel_email_traveller'][ $key ][0] : '';
			endif;
			return $args;
		}

		private function get_signature() {
			$settings = $this->get_payment_settings();
			$arg1     = $this->get_merchant_details();
			$arg2     = $this->get_buyer_details();
			$arg3     = $this->get_transaction_details();
			$arg4     = $this->get_custom_args();
			$args     = array_merge( $arg1, $arg2, $arg3, $arg4 );

			$pre_signature_string = '';
			foreach ( $args as $key => $value ) {
				$pre_signature_string .= $key . '=' . urlencode( stripslashes( trim( $value ) ) ) . '&';
			}
			$pre_signature_string = substr( $pre_signature_string, 0, -1 );
			if ( ! empty( $settings['passphrase'] ) ) {
				$pre_signature_string .= '&passphrase=' . urlencode( $settings['passphrase'] );
			}
			return array( 'signature' => md5( $pre_signature_string ) );
		}

		/**
		 * Transaction Details.
		 */
		private function get_transaction_details() {
			global $wt_cart;
			$total_price = $wt_cart->get_total();
			$payment_mode = isset( $_POST['wp_travel_payment_mode'] ) ? $_POST['wp_travel_payment_mode'] : 'full';

			$complete_partial_payment = $this->is_complete_partial_payment;

			$arg     = array();
			$trip_id = get_post_meta( (int) $this->booking_id, 'wp_travel_post_id', true );
			if ( $complete_partial_payment ) {
				$amount = isset( $_POST['amount'] ) ? $_POST['amount'] : 0;
				$arg['amount'] = $amount;
			} else {
				if ( 'partial' === $payment_mode ) {
					$arg['amount'] = $total_price['total_partial'];
				} else {
					$arg['amount'] = $total_price['total'];
				}
			}
			$arg['item_name'] = preg_replace( '/(&#x?\d+;)/', ' ', get_the_title( (int) $trip_id ) );

			return $arg;
		}

		public function get_payment_api_url( $ssl_check = false ) {
			return $this->get_payment_host() . '/eng/process';
		}

		public function get_payment_host( $ssl_check = false ) {
			if ( is_ssl() || ! $ssl_check ) {
				$protocol = 'https://';
			} else {
				$protocol = 'http://';
			}

			if ( wp_travel_test_mode() ) {
				$host = $protocol . 'sandbox.payfast.co.za';
			} else {
				$host = $protocol . 'www.payfast.co.za';
			}

			return $host;
		}

		/**
		 * Checkout Form Index Key.
		 */
		private function get_form_index_key() {
			$order_details = get_post_meta( (int) $this->booking_id, 'order_items_data', true ); // Multiple Trips.

			$index = is_array( $order_details ) && count( $order_details ) > 0 ? array_keys( $order_details )[0] : null;
			return $index;
		}

		public function get_payment_args() {
			$arg1 = $this->get_merchant_details();
			$arg2 = $this->get_buyer_details();
			$arg3 = $this->get_transaction_details();
			$arg4 = $this->get_custom_args();
			$arg5 = $this->get_signature();
			return array_merge( $arg1, $arg2, $arg3, $arg4, $arg5 );
		}

		public function get_custom_args() {
			$arg                = array();
			$arg['custom_int1'] = (int) $this->booking_id;
			return $arg;
		}

		public function get_payment_form() {
			$args     = $this->get_payment_args();
			$url_keys = array( 'return_url', 'cancel_url', 'notify_url' );
			$api_url  = $this->get_payment_api_url();
			?>
			<form id="wp_travel_payfast_payment_form" method="post" action="<?php echo esc_url( $api_url ); ?>">
				<h3><?php esc_html_e( 'You will redirect shortly ...', 'wp-travel-pro' ); ?></h3>
				<?php
				foreach ( $args as $key => $value ) :
					if ( array_key_exists( $key, $url_keys ) ) {
						echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_url( $value ) . '">';
						continue;
					}
					echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
				endforeach;
				?>
				<input type="submit" value="Pay"/>
			</form>
			<script>
				var form = document.getElementById( 'wp_travel_payfast_payment_form' );
				form.querySelector('input[type=submit]').style.display = 'none';
				form.submit();
			</script>
			<?php
		}

		/**
		 * Payment Settings.
		 */
		private function get_payment_settings() {
			$settings_instance = WP_Travel_PayFast_Settings::instance();
			$settings          = $settings_instance->get_settings();
			return $settings;
		}
	}

endif;
