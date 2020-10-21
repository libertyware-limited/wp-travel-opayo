<?php declare(strict_types=1);

/**
 * @package wp-travel-opayo
 */
namespace Libertyware\TravelSystem\Payments\Pages;

use \Libertyware\TravelSystem\Payments as Payments;
use \Libertyware\TravelSystem\Payments\Core\Interfaces\IPage;
use \Libertyware\TravelSystem\Wordpress\API as WordpressApi;
use \Cocur\Slugify\SlugifyInterface;

class Admin implements IPage {

	private Payments\ConfigService $config;
	private WordpressApi\SettingsAPI $settings_api;
	private SlugifyInterface $slugify;

	private array $pages;
	private array $subpages;

	public function __construct(
		Payments\ConfigService $config,
		WordpressApi\SettingsAPI $wordpress_settings,
		SlugifyInterface $slugify
	) {
		$this->config       = $config;
		$this->settings_api = $wordpress_settings;
		$this->slugify      = $slugify;
		$this->pages        = array(
			array(
				'page_title' => $this->config->get_plugin(),
				'menu_title' => 'Liberty - LTS',
				'capability' => 'manage_options',
				'menu_slug'  => $this->slugify->slugify( $this->config->get_plugin() ),
				'callback'   => function() {
					echo '<h1>OPayO ported</h1>';
				},
				'icon_url'   => '',
				'position'   => 110,
			),
		);
		$this->subpages     = array(
			array(
				'parent_slug' => $this->slugify->slugify( $this->config->get_plugin() ),
				'page_title'  => 'Payments',
				'menu_title'  => 'Payments',
				'capability'  => 'manage_options',
				'menu_slug'   => $this->slugify->slugify( $this->config->get_plugin() . 'payments' ),
				'callback'    => function() {
					echo '<h1>Payments page</h1>';
				}
			),
		);
	}

	public function register() {
		// $this->settings_api->add_pages( $this->pages )->with_subpage('Settings')->add_subpages( $this->subpages )->register();
		add_action( '4', array( $this, 'settings_fields' ), 25, 2 );
		add_filter( 'wp_travel_block_before_save_settings', array( $this, 'save_settings_v4' ), 12, 2 );
		add_filter( 'wp_travel_before_save_settings', array( $this, 'save_settings' ) );
	}


	/**
	 * Save Settings v4.
	 *
	 * @return void
	 */
	public function save_settings_V4( $settings, $settings_data ) {
		$defaults = $this->get_default_settings();
		if ( isset( $settings_data['wp_travel_opayo_settings'] ) ) {
			$opayo_settings = $settings_data['wp_travel_opayo_settings'];
			foreach ( $defaults as $key => $value ) {
				if ( isset( $opayo_settings[ $key ] ) ) {
					$settings['wp_travel_opayo_settings'][ $key ] = $opayo_settings[ $key ];
					if ( 'payment_option_opayo' === $key ) {
						$settings['payment_option_opayo'] = $opayo_settings[ $key ];
					}
					continue;
				}
				$settings['wp_travel_opayo_settings'][ $key ] = $value;
			}
		}
		if ( ! isset( $opayo_settings['payment_option_opayo'] ) ) {
			$settings['payment_option_opayo'] = 'no';
		}
		return $settings;
	}

	/**
	 * Save Settings.
	 *
	 * @return void
	 */
	public function save_settings( $settings ) {
		$defaults = $this->get_default_settings();
		if ( isset( $_POST['wp_travel_opayo_settings'] ) ) {
			$opayo_settings = $_POST['wp_travel_opayo_settings'];
			foreach ( $defaults as $key => $value ) {
				if ( isset( $opayo_settings[ $key ] ) ) {
					$settings['wp_travel_opayo_settings'][ $key ] = $opayo_settings[ $key ];
					if ( 'payment_option_opayo' === $key ) {
						$settings['payment_option_opayo'] = $opayo_settings[ $key ];
					}
					continue;
				}
				$settings['wp_travel_opayo_settings'][ $key ] = $value;
			}
		}
		if ( ! isset( $opayo_settings['payment_option_opayo'] ) ) {
			$settings['payment_option_opayo'] = 'no';
		}
		return $settings;
	}

	/**
	 * Settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$wp_travel_settings = array();
		if ( function_exists( 'wp_travel_get_settings' ) ) {
			$wp_travel_settings = wp_travel_get_settings();
		}
		$settings = $this->get_default_settings();
		if ( isset( $wp_travel_settings['wp_travel_opayo_settings'] ) && is_array( $wp_travel_settings['wp_travel_opayo_settings'] ) ) {
			$settings = $this->parse_args( $wp_travel_settings['wp_travel_opayo_settings'] );
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


	public function settings_fields( $args ) {
		echo 'JORDAN';
	}

}
