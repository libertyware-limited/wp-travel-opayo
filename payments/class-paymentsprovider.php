<?php

declare(strict_types=1);

/**
 * @package Liberty-Travel-System-Wordpress
 */

namespace Libertyware\TravelSystem\Payments;

use League\Container\ServiceProvider\AbstractServiceProvider;
class PaymentsProvider extends AbstractServiceProvider {

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
		Core\Interfaces\IDeactivate::class,
		Core\Interfaces\IActivate::class,
		Init::class,
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

		$container->add(
			Core\Interfaces\IDeactivate::class,
			Core\Deactivate::class
		);
		$container->add(
			Core\Interfaces\IActivate::class,
			Core\Activate::class
		);

		$container->add( Init::class )
			->addArguments(
				array(
					Core\Interfaces\IActivate::class,
					Core\Interfaces\IDeactivate::class,
				)
			);

		$container->add( 'plugin_path', plugin_dir_path( __FILE__ ) );
		$container->add( 'plugin_url', plugin_dir_url( __FILE__ ) );
		$container->add( 'plugin', plugin_basename( __FILE__ ) );

		$container->add( ConfigService::class )
			->addArguments(
				array(
					$container->get( 'plugin_path' ),
					$container->get( 'plugin_url' ),
					$container->get( 'plugin' ),
				)
			);
	}
}