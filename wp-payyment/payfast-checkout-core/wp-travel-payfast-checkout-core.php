<?php
/**
 * Modules core file.
 *
 * @package wp-travel-payfast-checkout-core
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WP Travel PayFast Checkout Core.
if ( ! class_exists( 'WP_Travel_PayFast_Checkout_Core' ) ) :
	/**
	 * WP Travel PayFast Checkout Core.
	 */
	class WP_Travel_PayFast_Checkout_Core {

		/**
		 * Absolute path to core
		 *
		 * @var string
		 */
		protected static $abspath;

		/**
		 * Plugin File.
		 *
		 * @var [type]
		 */
		protected static $plugin_file;

		/**
		 * Plugin Version.
		 *
		 * @var string
		 */
		protected static $version;

		/**
		 * The single instance of the class.
		 *
		 * @var WP_Travel_PayFast_Checkout_Core
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main WP_Travel_PayFast_Checkout_Core Instance.
		 * Ensures only one instance of WP_Travel_PayFast_Checkout_Core is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @see WP_Travel_PayFast_Checkout_Core()
		 * @return WP_Travel_PayFast_Checkout_Core - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Init core.
		 *
		 * @param Array $params Core class init paramerters.
		 */
		public static function init( $params ) {
			self::$abspath     = $params['abspath'] . 'inc/modules/payfast-checkout-core/';
			self::$plugin_file = __FILE__;
			self::$version     = $params['version'];
			self::includes();
			self::init_hooks();
		}

		/**
		 * Includes required files.
		 */
		public static function includes() {
			include_once self::$abspath . 'inc/admin/settings.php';
			include_once self::$abspath . 'inc/payfast/class-wp-travel-payfast-payment.php';
			include_once self::$abspath . 'inc/functions.php';
		}

		/**
		 * Init Hooks.
		 */
		public static function init_hooks() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
			if ( self::uses_payfast_checkout() ) {
				add_action( 'wp_travel_after_frontend_booking_save', array( __CLASS__, 'process' ), 20, 1 );
				add_action( 'wp_travel_before_partial_payment_complete', array( __CLASS__, 'process' ), 10, 2 );
			}

			// General Notices.
			add_filter( 'wp_travel_display_general_admin_notices', array( __CLASS__, 'wp_travel_display_payfast_notices' ), 20 );
			add_action( 'wp_travel_general_admin_notice', array( __CLASS__, 'wp_travel_payfast_notices' ), 20 );

			if ( isset( $_SESSION['used-payfast'] ) && $_SESSION['used-payfast'] ) {
				add_filter( 'wp_travel_booked_message', array( __CLASS__, 'booking_message' ), 20 );
			}

		}

		/**
		 * Process After Checkout Form Submit.
		 *
		 * @param int     $booking_id Booking ID.
		 * @param boolean $complete_partial_payment Complete Payment Process.
		 * @return void
		 */
		public static function process( $booking_id, $complete_partial_payment = false ) {
			if ( ! $booking_id || self::is_disabled() || ! self::uses_payfast_checkout() ) {
				return;
			}

			if ( ! $complete_partial_payment ) {
				do_action( 'wt_before_payment_process', $booking_id );
			}
			self::web_redirect( $booking_id, $complete_partial_payment );
			exit;
		}

		/**
		 * Redirect Using Script.
		 *
		 * @since 1.0.1
		 */
		public static function web_redirect( $booking_id, $complete_partial_payment ) {

			header( 'Content-type: text/html; charset=utf-8' );
			$pf   = new WP_Travel_PayFast_Payment( $booking_id, $complete_partial_payment );
			$form = $pf->get_payment_form();
			?>
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="UTF-8">
				<meta name="viewport" content="width=device-width, initial-scale=1.0">
				<meta http-equiv="X-UA-Compatible" content="ie=edge">
				<title><?php _e( 'Payfast Payment', 'wp-travel-pro' ); ?></title>
			</head>
			<body>
				<?php echo $form; ?>
			</body>
			</html>
			<?php
			exit;
		}

		/**
		 * Display admin notices in case of api credentials not found.
		 *
		 * @param $display
		 */
		public static function wp_travel_display_payfast_notices( $display ) {
			$settings = wp_travel_get_settings();
			if ( $settings['currency'] !== 'ZAR' && self::is_enabled() && ! $display ) {
				$display = true;
			}
			return $display;
		}

		/**
		 * Adds Notice if currency not matched.
		 */
		public static function wp_travel_payfast_notices() {
			$settings = wp_travel_get_settings();

			if ( $settings['currency'] != 'ZAR' && self::is_enabled() ) {
				$message = sprintf( __( 'PayFast works only with South Afircan Rand (ZAR).', 'wp-travel-pro' ) );
				printf( '<li ><p>%1$s</p></li>', $message );
			}
		}

		/**
		 * Chekcs if this payment is disabled.
		 *
		 * @return boolean
		 */
		public static function is_disabled() {
			return ! self::is_enabled();
		}

		/**
		 * Checks if this payent is enabled.
		 *
		 * @return boolean
		 */
		private static function is_enabled() {
			$settings = wp_travel_get_settings();
			return isset( $settings['wp_travel_payfast_settings'] ) && array_key_exists( 'payment_option_payfast', $settings['wp_travel_payfast_settings'] ) && 'yes' === $settings['wp_travel_payfast_settings']['payment_option_payfast'];
		}


		/**
		 * If current booking uses PayFast.
		 *
		 * @return boolean
		 */
		public static function uses_payfast_checkout() {
			return isset( $_POST['wp_travel_booking_option'] ) && 'booking_with_payment' === $_POST['wp_travel_booking_option'] && 'POST' === $_SERVER['REQUEST_METHOD'] && array_key_exists( 'wp_travel_payment_gateway', $_REQUEST ) && 'payfast' === $_REQUEST['wp_travel_payment_gateway'];
		}

		/**
		 * Booking Message After booking.
		 *
		 * @return string
		 */
		public static function booking_message() {
			unset( $_SESSION['used-payfast'] );
			$message = esc_html__( "We've received your booking and payment details. We'll contact you soon.", 'wp-travel-pro' );
			return $message;
		}


		/**
		 * Register/Enqueue Scripts.
		 */
		public static function assets() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			if ( wp_travel_can_load_payment_scripts() && self::is_enabled() ) {
				wp_enqueue_script( 'payfast-view-js', plugin_dir_url( __FILE__ ) . 'assets/js/wp-travel-payfast-checkout-view' . $suffix . '.js', array( 'jquery' ), '1.0.0', true );
			}
		}

		/**
		 * What type of request is this?
		 *
		 * @param  string $type admin, ajax, cron or frontend.
		 * @return bool
		 */
		private static function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'cron':
					return defined( 'DOING_CRON' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}


	}
endif;
