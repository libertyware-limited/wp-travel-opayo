<?php

/**
 * @package wp-travel-opayo
 */

namespace Libertyware\TravelSystem\Payments\Core;

use \Libertyware\TravelSystem\Payments\ConfigService;
use \Libertyware\TravelSystem\Wordpress\API\SettingsAPI;

class Enqueue {

	private ConfigService $config;
	private SettingsAPI $wordpress_settings;

	public function __construct(
		ConfigService $config,
		SettingsAPI $wordpress_settings
	) {
		$this->config             = $config;
		$this->wordpress_settings = $wordpress_settings;
	}


	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue() {

	}
}
