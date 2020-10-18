<?php declare(strict_types=1);

/**
 * @package wp-travel-opayo
 */
namespace Libertyware\TravelSystem\Payments;

class ConfigService {

	private string $plugin_path;
	private string $plugin_url;
	private string $plugin;

	public function __construct( string $plugin_path, string $plugin_url, string $plugin ) {
		$this->plugin_path = $plugin_path;
		$this->plugin_url  = $plugin_url;
		$this->plugin      = $plugin;
	}

	public function get_plugin_path(): string {
		return $this->plugin_path;
	}

	public function get_plugin_url(): string {
		return $this->plugin_url;
	}

	public function get_plugin(): string {
		return $this->plugin;
	}

}
