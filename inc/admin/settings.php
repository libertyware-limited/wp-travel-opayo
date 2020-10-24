<?php

/**
 * Functions.
 *
 * @package wp-travel-opayo/inc/admin/
 */


function wp_travel_settings_opayo($args)
{
	if (!$args) {
		return;
	}
	$payment_option_opayo = (isset($args['settings']['payment_option_opayo'])) ? $args['settings']['payment_option_opayo'] : '';

	$vendor= (isset($args['settings']['opayo_vendor'])) ? $args['settings']['opayo_vendor'] : '';
	$encryption_key       = (isset($args['settings']['opayo_encryption_key'])) ? $args['settings']['opayo_encryption_key'] : '';
?>
	<table class="form-table">
		<tr>
			<th><label for="payment_option_opayo"><?php esc_html_e('Enable Stripe', 'wp-travel-pro') ?></label></th>
			<td>
				<label for="payment_option_opayo">
					<span class="show-in-frontend checkbox-default-design">
						<label data-on="ON" data-off="OFF">
							<input type="checkbox" value="yes" <?php checked('yes', $payment_option_opayo) ?> name="payment_option_opayo" id="payment_option_opayo" class="enable-payment-gateway" />
							<span class="switch">
							</span>
						</label>
					</span>
					<p class="description"><?php esc_html_e('Check to enable Stripe Checkout.', 'wp-travel-pro') ?></p>
				</label>
			</td>
		</tr>

		<tbody class="strip_tbody payment-gateway-fields">

			<tr>
				<th><label for="opayo_vendor"><?php esc_html_e('Vendor', 'wp-travel-pro') ?></label></th>
				<td>
					<input type="text" value="<?php echo esc_attr($vendor) ?>" name="opayo_vendor" id="opayo_vendor" />
				</td>
			</tr>
			<tr>
				<th><label for="opayo_encryption_key"><?php esc_html_e('Encryption Key', 'wp-travel-pro') ?></label></th>
				<td>
					<input type="password" value="<?php echo esc_attr( $encryption_key ) ?>" name="opayo_encryption_key" id="opayo_encryption_key" />
				</td>
			</tr>
		</tbody>

	</table>

<?php
}

add_action('wp_travel_payment_gateway_fields_opayo', 'wp_travel_settings_opayo');
// add_action( 'wp_travel_payment_gateway_fields', 'wp_travel_settings_opayo' );

/**
 * Save settings v4.
 *
 * @param Array $settings List of settings.
 * @param Array $settings_data List of settings data.
 */
function wp_travel_settings_opayo_savev4($settings, $settings_data)
{
	if (!$settings) {
		return;
	}
	$payment_option_opayo       = (isset($settings_data['payment_option_opayo']) && '' !== $settings_data['payment_option_opayo']) ? $settings_data['payment_option_opayo'] : '';
	$opayo_vendor = (isset($settings_data['opayo_vendor']) && '' !== $settings_data['opayo_vendor']) ? $settings_data['opayo_vendor'] : '';
	$opayo_encryption_key      = (isset($settings_data['opayo_encryption_key']) && '' !== $settings_data['opayo_encryption_key']) ? $settings_data['opayo_encryption_key'] : '';

	$settings['payment_option_opayo']       = $payment_option_opayo;
	$settings['opayo_vendor'] = $opayo_vendor;
	$settings['opayo_encryption_key']      = $opayo_encryption_key;

	return $settings;
}
add_filter('wp_travel_block_before_save_settings', 'wp_travel_settings_opayo_savev4', 10, 2);

function wp_travel_settings_opayo_save($settings)
{
	if (!$settings) {
		return;
	}
	$payment_option_opayo  = ( isset($_POST['payment_option_opayo'] ) && '' !== $_POST['payment_option_opayo'] ) ? $_POST['payment_option_opayo'] : '';

	$opayo_vendor         = (isset($_POST['opayo_vendor']) && '' !== $_POST['opayo_vendor']) ? $_POST['opayo_vendor'] : '';
	$opayo_encryption_key = (isset($_POST['opayo_encryption_key']) && '' !== $_POST['opayo_encryption_key']) ? $_POST['opayo_encryption_key'] : '';


	$settings['payment_option_opayo'] = $payment_option_opayo;
	$settings['opayo_vendor']         = $opayo_vendor;
	$settings['opayo_encryption_key'] = $opayo_encryption_key;

	return $settings;
}
add_filter('wp_travel_before_save_settings', 'wp_travel_settings_opayo_save');
