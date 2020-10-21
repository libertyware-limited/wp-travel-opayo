<?php

/**
 * @package wp-travel-opayo
 */

namespace Libertyware\TravelSystem\WPTravel;

use \Libertyware\TravelSystem\Wordpress\API\SettingsAPI;

class PaymentGatewayHooks {

	private SettingsAPI $wordpress_settings;

	public function __construct(
		SettingsAPI $wordpress_settings
	) {
		$this->wordpress_settings = $wordpress_settings;
	}

	public function register() {

	}

}
