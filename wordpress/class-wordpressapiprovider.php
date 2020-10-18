<?php declare(strict_types=1);

/**
 * @package Liberty-Travel-System-Wordpress
 */

namespace Libertyware\TravelSystem\Wordpress;

use League\Container\ServiceProvider\AbstractServiceProvider;

class WordpressApiProvider extends AbstractServiceProvider {

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
		\Libertyware\TravelSystem\Wordpress\API\SettingsAPI::class,
	);

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 */
	public function register() {
		$container = $this->getLeagueContainer();
		$container->add( \Libertyware\TravelSystem\Wordpress\API\SettingsAPI::class );
	}

}
