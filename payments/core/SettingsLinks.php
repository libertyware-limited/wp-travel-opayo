<?php

/**
 * @package wp-travel-opayo
 */

namespace Libertyware\TravelSystem\Payments\Core;

use \Libertyware\TravelSystem\Payments\ConfigService;
use \Libertyware\TravelSystem\Wordpress\API\SettingsAPI;

class SettingsLinks {

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
		$plugin_name = $this->config->get_plugin() . '/' . $this->config->get_plugin() . '.php';
		add_filter(
			"plugin_action_links_$plugin_name",
			array(
				$this,
				'settings_link',
			)
		);
	}

	public function settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=wp-travel-opayo">Settings</a>';
		array_push( $links, $settings_link );
		return $links;
	}
}
