<?php declare(strict_types=1);

/**
 * @package wp-travel-opayo
 */
namespace Libertyware\TravelSystem\Payments\Pages;

use \Libertyware\TravelSystem\Payments as Payments;
use \Libertyware\TravelSystem\Wordpress\API as WordpressApi;


class Admin {

	private Payments\ConfigService $config;
	private WordpressApi\SettingsAPI $settings_api;

	public function __construct( Payments\ConfigService $config, WordpressApi\SettingsAPI $wordpress_settings ) {
		$this->config       = $config;
		$this->settings_api = $wordpress_settings;
	}

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
	}

	public function add_admin_pages() {
		add_menu_page(
			'Liberty Travel System',
			'LTS',
			'manage_options',
			$this->config->get_plugin(),
			array(
				$this,
				'admin_index',
			),
			'payments'
		);
	}

	public function admin_index() {
		require_once $this->config->get_plugin_path() . 'template/admin/index.php';
	}

}
