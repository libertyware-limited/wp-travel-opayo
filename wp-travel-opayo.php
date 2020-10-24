<?php

/**
 * WP Travel OPayO Checkout Core Class.
 *
 * @package wp-travel-opayo
 * @category Core
 * @author WEN Solutions
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use Omnipay\Omnipay;

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

			add_action('wp_travel_action_after_payment_info_field', array(__CLASS__, 'add_extra_fields'));
			add_action('wp_travel_dashboard_booking_after_detail', array(__CLASS__, 'add_extra_fields'), 15, 2);


			if (self::uses_opayo_checkout()) {
				add_action('wp_travel_after_frontend_booking_save', array('WP_Travel_OPayO_Checkout_Core', 'process'), 20);
				add_action('wp_travel_after_partial_payment_complete', array('WP_Travel_OPayO_Checkout_Core', 'process'), 20);
			}
			isset($_SESSION['used-opayo']) && $_SESSION['used-opayo'] && add_filter('wp_travel_booked_message', 'wp_travel_opayo_booking_message', 20);
		}

		public static function add_extra_fields($booking_id = null, $details = null)
		{
?>
			<div id="card-errors"></div>
<?php
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

			$gateway = OmniPay::create( 'SagePay\Form' )->initialize(
				array(
					'vendor'        => $settings['opayo_vendor'],
					'testMode'      => $is_test_mode,
					'encryptionKey' => $settings['opayo_encryption_key'],
				)
			);

			if ($is_test_mod) {
				if (method_exists($gateway, 'setDeveloperMode')) {
					$gateway->setDeveloperMode(TRUE);
				} else {
					$gateway->setTestMode(TRUE);
				}
			}

			$response = $gateway->authorize(
				array(
						'returnUrl' => 'https://example.com/success',
						'failureUrl' => 'https://example.com/failure',
				)
			);

			$url = $response->isSuccessful();

			global $wt_cart;
			$items       = $wt_cart->getItems();
			$cart_amounts = $wt_cart->get_total();

			if ($amount) {
				$amount = number_format($amount / 100, 2, '.', '');
			}
			$payment_id     = get_post_meta($booking_id, 'wp_travel_payment_id', true);
			$payment_method = 'opayo';
			update_post_meta($payment_id, 'wp_travel_payment_gateway', $payment_method);

			wp_travel_update_payment_status($booking_id, $amount, 'paid', $detail, sprintf('_%s_args', $payment_method), $payment_id);
			$_SESSION['used-opayo'] = true;
			do_action('wp_travel_after_successful_payment', $booking_id);
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
