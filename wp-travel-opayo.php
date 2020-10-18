<?php declare(strict_types=1);

/**
 * Plugin Name: WP Travel OPayO
 * Plugin URI: https://libertyware.io/liberty-travel-system/OPayO
 * Description: Liberty Travel System Payment ported to WP Travel
 * Version: 0.0.1
 * Author: Libertyware Limited
 * Author URI: https://libertyware.io
 * Requires at least: 5.5.1
 * Requires PHP: 7.0
 *
 * Text Domain: Liberty Travel System
 *
 * @package wp-travel-OPayO
 * @category Core
 * @author Libertyware Limited
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
} else {
	exit;
}

use League\Container\ServiceProvider\AbstractServiceProvider;

class OPayOProvider extends AbstractServiceProvider {

	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored.
	 *
	 * @var array
	 */
	protected $provides = array(
		Libertyware\TravelSystem\Payments\Init::class,
		'plugin_path',
		'plugin_url',
	);

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 */
	public function register() {

		$container = $this->getLeagueContainer();
		$container->add( Libertyware\TravelSystem\Payments\Init::class );
		$container->add(
			Libertyware\TravelSystem\Payments\Interfaces\IActivate::class,
			Libertyware\TravelSystem\Payments\Core\Activate::class
		);
		$container->add(
			Libertyware\TravelSystem\Payments\Interfaces\IDeactivate::class,
			Libertyware\TravelSystem\Payments\Core\Deactivate::class
		);

		$container->add( Libertyware\TravelSystem\PaymentsPayments\Init::class )
			->addArguments(
				array(
					Libertyware\TravelSystem\Payments\Core\Activate::class,
					Libertyware\TravelSystem\Payments\Core\Deactivate::class,
				)
			);

		$container->add( 'plugin_path', plugin_dir_path( __FILE__ ) );
		$container->add( 'plugin_url', plugin_dir_url( __FILE__ ) );
	}
}






$container = new League\Container\Container();

$container->addServiceProvider( new OPayOProvider() );

$container->delegate( ( new League\Container\ReflectionContainer() )->cacheResolutions() );

/** @var Libertyware\TravelSystem\Payments\Init::class $init */
$init = $container->get( \Libertyware\TravelSystem\Payments\Init::class );

if ( isset( $init ) ) {
	register_activation_hook( __FILE__, $init->activate );
	register_deactivation_hook( __FILE__, $init->deactivate );
} else {
	exit;
}
