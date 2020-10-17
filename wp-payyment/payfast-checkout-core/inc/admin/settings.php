<?php
/**
 * Settings class
 */


/**
 * WP_Travel_PayFast_Settings.
 */
class WP_Travel_PayFast_Settings {

	/**
	 * Default Settings.
	 *
	 * @var array
	 */
	private $default_settings;

	/**
	 * Instance.
	 *
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * Return Instance.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::$_instance        = $this;
		$this->default_settings = array(
			'payment_option_payfast' => 'no',
			'merchant_key'           => '',
			'merchant_id'            => '',
			'passphrase'             => '',
		);
		add_action( 'wp_travel_payment_gateway_fields_payfast', array( $this, 'settings_fields' ), 25, 2 );
		add_filter( 'wp_travel_before_save_settings', array( $this, 'save_settings' ) );
	}

	/**
	 * Settings Callback.
	 *
	 * @return void
	 */
	public function settings_fields( $args ) {
		$settings = isset( $args['settings']['wp_travel_payfast_settings'] ) ? $args['settings']['wp_travel_payfast_settings'] : array();
		$settings = $this->parse_args( $settings );
		extract( $settings );
		?>
		<table class="form-table">
			<tr>
				<th><label for="payment_option_payfast"><?php esc_html_e( 'Enable PayFast', 'wp-travel-pro' ); ?></label></th>
				<td>
					<span class="show-in-frontend checkbox-default-design">
						<label data-on="ON" data-off="OFF">
							<input type="checkbox" value="yes" <?php checked( 'yes', $payment_option_payfast ); ?>
								name="wp_travel_payfast_settings[payment_option_payfast]" id="payment_option_payfast" />
							<span class="switch"></span>
						</label>
					</span>
					<p class="description"><?php esc_html_e( 'Check to enable PayFast Checkout.', 'wp-travel-pro' ); ?></p>
				</td>
			</tr>
			<tbody class="payfast_tbody">
				<tr>
					<th><label for="payfast_merchant_id"><?php esc_html_e( 'Merchant ID', 'wp-travel-pro' ); ?></label></th>
					<td>
						<input type="text" value="<?php echo esc_attr( $merchant_id ); ?>" name="wp_travel_payfast_settings[merchant_id]" id="payfast_merchant_id" />
						<p><?php printf( __( 'Please register <a href="%1$s" target="new">here</a> to get Merchant Id and Key and click <a href="%2$s" target="new">here</a> to know transaction fees.', 'wp-travel-pro' ), 'https://www.payfast.co.za/user/register', 'https://www.payfast.co.za/fees/' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="payfast_merchant_key"><?php esc_html_e( 'Merchant Key', 'wp-travel-pro' ); ?></label></th>
					<td>
						<input type="text" value="<?php echo esc_attr( $merchant_key ); ?>" name="wp_travel_payfast_settings[merchant_key]" id="payfast_merchant_key" />
					</td>
				</tr>
				<tr>
					<th><label for="payfast_passphrase"><?php esc_html_e( 'Passphrase', 'wp-travel-pro' ); ?></label></th>
					<td>
						<input type="text" value="<?php echo esc_attr( $passphrase ); ?>" name="wp_travel_payfast_settings[passphrase]" id="payfast_passphrase" />
					</td>
				</tr>
			</tbody>
		</table>

		<script type="text/javascript">
		const payfast_change = function() {
			jQuery(this).is(':checked') ? jQuery('.payfast_tbody').fadeIn() : jQuery('.payfast_tbody').fadeOut();
		}
		jQuery(document).ready(function($) {
			$('#payment_option_payfast').click(payfast_change)
			$('#payment_option_payfast').change(payfast_change)
			payfast_change.apply($('#payment_option_payfast'));
		});
		</script>
		<?php
	}

	/**
	 * Save Gallery Settings.
	 *
	 * @return void
	 */
	public function save_settings( $settings ) {
		$defaults = $this->get_default_settings();
		if ( isset( $_POST['wp_travel_payfast_settings'] ) ) {
			$payfast_settings = $_POST['wp_travel_payfast_settings'];
			foreach ( $defaults as $key => $value ) {
				if ( isset( $payfast_settings[ $key ] ) ) {
					$settings['wp_travel_payfast_settings'][ $key ] = $payfast_settings[ $key ];
					if ( 'payment_option_payfast' === $key ) {
						$settings['payment_option_payfast'] = $payfast_settings[ $key ];
					}
					continue;
				}
				$settings['wp_travel_payfast_settings'][ $key ] = $value;
			}
		}
		if ( ! isset( $payfast_settings['payment_option_payfast'] ) ) {
			$settings['payment_option_payfast'] = 'no';
		}
		return $settings;
	}

	/**
	 * Gallery Settings.
	 *
	 * @return void
	 */
	public function get_settings() {
		$wp_travel_settings = array();
		if ( function_exists( 'wp_travel_get_settings' ) ) {
			$wp_travel_settings = wp_travel_get_settings();
		}
		$settings = $this->get_default_settings();
		if ( isset( $wp_travel_settings['wp_travel_payfast_settings'] ) && is_array( $wp_travel_settings['wp_travel_payfast_settings'] ) ) {
			$settings = $this->parse_args( $wp_travel_settings['wp_travel_payfast_settings'] );
		}
		return $settings;
	}

	/**
	 * Parse Arguments.
	 *
	 * @param [type] $args
	 * @return void
	 */
	private function parse_args( $args ) {
		$defaults = $this->get_default_settings();
		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Default Settings.
	 *
	 * @return void
	 */
	private function get_default_settings() {
		return $this->default_settings;
	}
}

new WP_Travel_PayFast_Settings();
