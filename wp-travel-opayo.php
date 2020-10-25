<?php

/*
Plugin Name: Wp Travel OPayO
Plugin URI: https://libertyware.io
Description: SagePay for WP Travel.
Version: 0.1.0
Author: Libertyware Limited
Author URI: https://libertyware.io
License: MIT
*/


// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;
use Omnipay\Common\ItemBag;
use Omnipay\SagePay\Extend\Item;


// WP Travel OPayO Checkout core.
if (!class_exists('WP_Travel_OPayO_Checkout_Core')) :
	/**
	 * Core Class
	 */
	class WP_Travel_OPayO_Checkout_Core
	{

		const WP_TRAVEL_OPAYO_HANDLE = 'wp_travel_opayo_';
		/**
		 * ABSPATH
		 *
		 * @var string $abspath
		 */
		protected static $abspath;

		/**
		 * Plugin File Path
		 *
		 * @var string $plugin_file
		 */
		protected static $plugin_file;

		/**
		 * Plugin File URL
		 *
		 * @var string $plugin_url
		 */
		protected static $plugin_url;

		/**
		 * Plugin Version
		 *
		 * @var string $version
		 */
		protected static $version;

		/**
		 * The single instance of the class.
		 *
		 * @var WP Travel OPayO Checkout Core
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main WP_Travel_OPayO_Checkout_Core Instance.
		 * Ensures only one instance of WP_Travel_OPayO_Checkout_Core is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @see WP_Travel_OPayO_Checkout_Core()
		 * @return WP_Travel_OPayO_Checkout_Core - Main instance.
		 */
		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Init core.
		 *
		 * @param array $plugin_data Plagin data.
		 */
		public static function init($plugin_data)
		{
			self::$abspath     = plugin_dir_path(__FILE__);
			self::$plugin_file = __FILE__;
			self::$plugin_url  = plugin_dir_url(__FILE__);
			self::$version     = $plugin_data['version'];

			self::includes();

			add_action('wp_enqueue_scripts', array('WP_Travel_OPayO_Checkout_Core', 'frontend_assets'), 20);
			add_action('admin_enqueue_scripts', array('WP_Travel_OPayO_Checkout_Core', 'admin_assets'), 20);

			// Payment Gateway list.
			add_filter('wp_travel_payment_gateway_lists', 'wp_travel_gateway_opayo');
			add_filter('wp_travel_premium_addons_list', 'wp_travel_opayo_addons');

			if (self::is_enabled()) {
				add_filter('wp_travel_frontend_data', 'wp_travel_opayo_add_vars', 10, 2);
			}

			add_action('wp_travel_action_after_payment_info_field', array( 'WP_Travel_OPayO_Checkout_Core', 'add_extra_fields'));
			add_action('wp_travel_dashboard_booking_after_detail', array( 'WP_Travel_OPayO_Checkout_Core', 'add_extra_fields'), 15, 2);


			if (self::uses_opayo_checkout()) {
				add_action('wp_travel_after_frontend_booking_save', array('WP_Travel_OPayO_Checkout_Core', 'process'), 20);
				add_action('wp_travel_after_partial_payment_complete', array('WP_Travel_OPayO_Checkout_Core', 'process'), 20);
			}
			isset($_SESSION['used-opayo']) && $_SESSION['used-opayo'] && add_filter('wp_travel_booked_message', 'wp_travel_opayo_booking_message', 20);
		}


		public static function wp_travel_opayo_booking_message( $message ) {
			unset( $_SESSION['used-opayo'] );
			var_dump( $message );
			$message = esc_html__( "We've received your booking and payment details. We'll contact you soon.", 'wp-travel-pro' );
			return $message;
		}

		/**
		 * Determine if booking used express checkout.
		 */
		private static function uses_opayo_checkout()
		{
			return isset($_POST['wp_travel_booking_option']) && 'booking_with_payment' === $_POST['wp_travel_booking_option'] && 'POST' === $_SERVER['REQUEST_METHOD'] && array_key_exists('wp_travel_payment_gateway', $_REQUEST) && 'opayo' === $_REQUEST['wp_travel_payment_gateway'];
		}

		/**
		 * Determine if OPayO checkout is enabled.
		 */
		private static function is_enabled()
		{
			$settings = function_exists('wp_travel_get_settings') ? wp_travel_get_settings() : array();
			return array_key_exists('payment_option_opayo', $settings) && 'yes' === $settings['payment_option_opayo'];
		}

		/**
		 * Determing is OPayO checkout is disabled.
		 */
		private static function is_disabled()
		{
			return !self::is_enabled();
		}

		/**
		 * OPayO Frontend assets.
		 */
		public static function frontend_assets()
		{

			if (wp_travel_can_load_payment_scripts() && self::is_enabled()) {

				$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
				// Styles.
				wp_enqueue_style('wp-travel-opayo-custom', self::$plugin_url . 'assets/css/custom.css', '', self::$version);

				$dependencies   = array('jquery', 'wp-travel-payment-frontend-script');
				$dependencies[] = 'wp-travel-payment-frontend-script';

				wp_enqueue_script('wp_travel_opayo_checkout_js', 'https://js.opayo.com/v3/', array('jquery'), self::$version, true);
				wp_register_script('wp-travel-opayo-script', self::$plugin_url . 'assets/js/wp-travel-opayo.js', $dependencies, self::$version, true);
				wp_localize_script(
					'wp-travel-opayo-script',
					'_wpTravelOPayOL10n',
					array(
						'brandLogo' => plugin_dir_url(__FILE__) . 'assets/img/opayo.png',
					)
				);
				wp_enqueue_script('wp-travel-opayo-script');
			}
		}

		/**
		 * Admin assets.
		 */
		public static function admin_assets()
		{

			$suffix         = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$screen         = get_current_screen();
			$allowed_screen = array('edit-itineraries', WP_TRAVEL_POST_TYPE, 'itineraries_page_settings', 'itinerary-booking', 'itinerary-booking_page_settings');

			$settings               = function_exists('wp_travel_get_settings') ? wp_travel_get_settings() : array();
			$wp_travel_react_switch = isset($settings['wp_travel_switch_to_react']) && 'yes' === $settings['wp_travel_switch_to_react'];
			if ($wp_travel_react_switch) {
				$screen = get_current_screen();
				// settings_screen.
				if ('itinerary-booking_page_settings2' == $screen->id) {
					$deps                   = include_once sprintf('%sbuild/index.asset.php', plugin_dir_path(__FILE__));
					$deps['dependencies'][] = 'jquery';
					wp_enqueue_script(self::WP_TRAVEL_OPAYO_HANDLE . 'admin-settings', plugin_dir_url(__FILE__) . 'build/index.js', $deps['dependencies'], $deps['version'], true);
				}
			}
		}






		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @return void
		 */
		public static function includes()
		{
			include sprintf('%sinc/functions.php', self::$abspath);
			if (self::is_request('admin')) {
				include sprintf('%sinc/admin/settings.php', self::$abspath);
			}
		}

		/**
		 * Sets up payment options
		 *
		 * @param string $booking_id ID of booking.
		 * @return void
		 */
		public static function process($booking_id, $complete_partial_payment = false )
		{

			if (self::is_disabled()) {
				return;
			}

			if (!self::uses_opayo_checkout()) {
				return;
			}

			if (!$booking_id) {
				return;
			}
			if (!isset($_POST['wp_travel_book_now'])) {
				return;
			}

			do_action('wt_before_payment_process', $booking_id);

			$settings = wp_travel_get_settings();

			$is_test_mode = $settings['wt_test_mode'] === 'yes';

			global $wt_cart;
			$items          = $wt_cart->getItems();
			$cart_amounts   = $wt_cart->get_total();
			$cart_total     = $cart_amounts['cart_total'];

			$card = new CreditCard( self::get_buyer_details( $booking_id ) );


			$gateway = OmniPay::create( 'SagePay\Form' )->initialize(
				array(
					'vendor'        => $settings['opayo_vendor'],
					'testMode'      => $is_test_mode,
					'encryptionKey' => $settings['opayo_encryption_key'],
					'disableUtf8Decode' => true,
				)
			);
			$return_url = self::get_return_url( $booking_id );

			$request  = $gateway->purchase(
				array(
					'currency'      => $settings['currency'] ? $settings['currency'] : 'GBP',
					'card'          => $card,
					'amount'        => $cart_total,
					'transactionId' => $booking_id,
					'clientIp'      => $_SERVER['HTTP_CLIENT_IP'] || $_SERVER['HTTP_X_FORWARDED_FOR'] || $_SERVER['REMOTE_ADDR'],
					'description'   => self::get_trip_title( $booking_id ),
					'returnUrl'     => add_query_arg(
						array(
							'booking_id' => $booking_id,
							'booked'     => true,
							'status'     => 'success',
							'order_id'   => $booking_id,
						),
						$return_url
					),
					'failureUrl'    => add_query_arg(
						array(
							'booking_id' => $booking_id,
							'booked'     => true,
							'status'     => 'cancel',
						),
						$return_url
					),
				)
			);
			$_SESSION['used-opayo'] = true;
			$response = $request->send();
			$response->redirect();


			// update_post_meta($payment_id, 'wp_travel_payment_gateway', $payment_method);

			// wp_travel_update_payment_status($booking_id, $amount, 'paid', $detail, sprintf('_%s_args', $payment_method), $payment_id);
			// $_SESSION['used-opayo'] = true;
			// do_action('wp_travel_after_successful_payment', $booking_id);
		}

		private static function get_trip_title( $booking_id ) {
			$trip_id = get_post_meta( (int) $booking_id, 'wp_travel_post_id', true );
			$post = get_post( $trip_id );
			return $post->post_title;

		}

		private static function get_return_url( $booking_id ) {
			$trip_id = get_post_meta( (int) $booking_id, 'wp_travel_post_id', true );
			$url     = '';
			if ( function_exists( 'wp_travel_thankyou_page_url' ) ) {
				$url = wp_travel_thankyou_page_url( (int) $trip_id );
			}
			return $url;
		}

		private static function get_buyer_details( $booking_id ) {
			$booking_data = get_post_meta( (int) $booking_id, 'order_data', true );

			$args = array();
			$key  = self::get_form_index_key( $booking_id );
			if ( $key ) :

				// Buyer Details.
				$args['firstName'] = isset( $booking_data['wp_travel_fname_traveller'][ $key ][0] ) ? $booking_data['wp_travel_fname_traveller'][ $key ][0] : '';
				$args['lastName']  = isset( $booking_data['wp_travel_lname_traveller'][ $key ][0] ) ? $booking_data['wp_travel_lname_traveller'][ $key ][0] : '';
				$args['email']      = isset( $booking_data['wp_travel_email_traveller'][ $key ][0] ) ? $booking_data['wp_travel_email_traveller'][ $key ][0] : '';
				$args['billingAddress1']    = isset( $booking_data['wp_travel_address'] ) ? $booking_data['wp_travel_address'] : '';
				$args['billingCity']       = isset( $booking_data['billing_city'] ) ? $booking_data['billing_city'] : '';
				$args['billingPostcode']       = isset( $booking_data['billing_postal'] ) ? $booking_data['billing_postal'] : '';
				$args['billingCountry']    = isset( $booking_data['wp_travel_country'] ) ? $booking_data['wp_travel_country'] : '';
				$args['shippingAddress1']    = isset( $booking_data['wp_travel_address'] ) ? $booking_data['wp_travel_address'] : '';
				$args['shippingCity']       = isset( $booking_data['billing_city'] ) ? $booking_data['billing_city'] : '';
				$args['shippingPostcode']       = isset( $booking_data['billing_postal'] ) ? $booking_data['billing_postal'] : '';
				$args['shippingCountry']    = isset( $booking_data['wp_travel_country'] ) ? $booking_data['wp_travel_country'] : '';
			endif;
			return $args;
		}


		private static function get_form_index_key( $booking_id ) {
			$order_details = get_post_meta( (int) $booking_id, 'order_items_data', true ); // Multiple Trips.

			$index = is_array( $order_details ) && count( $order_details ) > 0 ? array_keys( $order_details )[0] : null;
			return $index;
		}



		/**
		 * What type of request is this?
		 *
		 * @param  string $type admin, ajax, cron or frontend.
		 * @return bool
		 */
		private static function is_request($type)
		{
			switch ($type) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined('DOING_AJAX');
				case 'cron':
					return defined('DOING_CRON');
				case 'frontend':
					return (!is_admin() || defined('DOING_AJAX')) && !defined('Doing_cRON');
			}
		}
	}
endif;


$opayo_init = WP_Travel_OPayO_Checkout_Core::init(array('version' => '0.0.1'));
$opayo      = WP_Travel_OPayO_Checkout_Core::instance();
