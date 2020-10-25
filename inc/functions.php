<?php

/**
 * Functions.
 *
 * @package wp-travel-paypal/inc/
 */

// Functions.
/**
 * Return
 */
function wp_travel_gateway_opayo($gateways)
{
	if (!$gateways) {
		return;
	}
	$gateways['opayo'] = __('OPayO Checkout', 'wp-travel');
	return $gateways;
}

function wp_travel_opayo_addons($addons)
{
	$addons['opayo'] = __('OPayO Checkout', 'wp-travel');
	return $addons;
}

function wp_travel_opayo_add_vars($args, $settings)
{
	if (!$args) {
		return;
	}
	$publishable_key = isset($settings['opayo_publishable_key']) ? $settings['opayo_publishable_key'] : '';
	if (wp_travel_test_mode()) {
		$publishable_key = isset($settings['opayo_vendor']) ? $settings['opayo_vendor'] : '';
	}

	$args['payment']['opayo_publishable_key'] = $publishable_key;
	return $args;
}
