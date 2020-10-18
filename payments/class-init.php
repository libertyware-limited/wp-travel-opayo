<?php declare(strict_types=1);

/**
 * @package wp-travel-opayo
 */
namespace Libertyware\TravelSystem\Payments;

final class Init {

	/**
	 * @varCore\Interfaces\IActivate
	 */
	public $activate;

	/**
	 * @var Core\Interfaces\IDeactivate
	 */
	public $deactivate;

	/**
	 * Construct.
	 *
	 * @param Core\Interfaces\IActivate $activate
	 * @param Core\Interfaces\IDeactivate $deactivate
	 */
	public function __construct(
		Core\Interfaces\IActivate $activate,
		Core\Interfaces\IDeactivate $deactivate
	) {
		$this->activate   = $activate;
		$this->deactivate = $deactivate;
	}

	public function get_services() {
		return array();
	}

	public function register_services() {
		foreach ( $this->get_services() as $class ) {
			$service = $this->instantiate( $class );
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	private function instantiate( $class ) {
		return new $class();
	}

	public function activate() {
		$this->activate::activate();
	}

	public function deactivate() {
		$this->deactivate::deactivate();
	}
}
