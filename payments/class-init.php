<?php declare(strict_types=1);

/**
 * @package wp-travel-opayo
 */
namespace Libertyware\TravelSystem\Payments;

final class Init {

	/**
	 * @varCore\Interfaces\IActivate
	 */
	public $activate;

	/**
	 * @var Core\Interfaces\IDeactivate
	 */
	public $deactivate;

	/**
	 * Construct.
	 *
	 * @param Core\Interfaces\IActivate $activate
	 * @param Core\Interfaces\IDeactivate $deactivate
	 */
	public function __construct(
		Core\Interfaces\IActivate $activate,
		Core\Interfaces\IDeactivate $deactivate
	) {
		$this->activate   = $activate;
		$this->deactivate = $deactivate;
	}

	public function activate() {
		$this->activate::activate();
	}

	public function deactivate() {
		$this->deactivate::deactivate();
	}

	public function register() {
		// add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		// add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		if ( $this->uses_opayo_checkout() ) {
			add_action( 'wp_travel_after_frontend_booking_save', array( $this, 'process' ), 20, 1 );
			add_action( 'wp_travel_before_partial_payment_complete', array( $this, 'process' ), 10, 2 );
		}

		// General Notices.
		add_filter( 'wp_travel_display_general_admin_notices', array( $this, 'wp_travel_display_opayo_notices' ), 20 );
		add_action( 'wp_travel_general_admin_notice', array( $this, 'wp_travel_opayo_notices' ), 20 );

		if ( isset( $_SESSION['used-opayo'] ) && $_SESSION['used-opayo'] ) {
			add_filter( 'wp_travel_booked_message', array( $this, 'booking_message' ), 20 );
		}

		add_filter( 'wp_travel_payment_gateway_lists', array( $this, 'wp_travel_opayo_gateway' ) );

		add_filter( 'wp_travel_premium_addons_list', array( $this, 'wp_travel_opayo_addons' ) );
	}

	public function wp_travel_opayo_gateway( $gateways ) {
		if ( ! $gateways ) {
			return;
		}
		$gateways['opayo'] = __( 'opayo Checkout', 'wp-travel-pro' );
		return $gateways;
	}

	public function wp_travel_opayo_addons( $addons ) {
		$addons['opayo'] = __( 'opayo Checkout', 'wp-travel-pro' );
		return $addons;
	}
	public function uses_opayo_checkout() {
		return isset( $_POST['wp_travel_booking_option'] ) && 'booking_with_payment' === $_POST['wp_travel_booking_option'] && 'POST' === $_SERVER['REQUEST_METHOD'] && array_key_exists( 'wp_travel_payment_gateway', $_REQUEST ) && 'opayo' === $_REQUEST['wp_travel_payment_gateway'];
	}

	public function booking_message() {
		unset( $_SESSION['used-opayo'] );
		$message = esc_html__( "We've received your booking and payment details. We'll contact you soon.", 'wp-travel-pro' );
		return $message;
	}

	private function is_enabled() {
		$settings = wp_travel_get_settings();
		return isset( $settings['wp_travel_opayo_settings'] ) && array_key_exists( 'payment_option_opayo', $settings['wp_travel_opayo_settings'] ) && 'yes' === $settings['wp_travel_opayo_settings']['payment_option_opayo'];
	}
}
