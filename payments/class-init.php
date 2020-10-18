<?php declare(strict_types=1);

/**
 * @package wp-travel-opayo
 */
namespace Libertyware\TravelSystem\Payments;

final class Init {

	/**
	 * @var \Libertyware\TravelSystem\Payments\Core\Interfaces\IActivate
	 */
	public $activate;

	/**
	 * @var \Libertyware\TravelSystem\Payments\Core\Interfaces\IDeactivate
	 */
	public $deactivate;

	/**
	 * Construct.
	 *
	 * @param \Libertyware\TravelSystem\Payments\Core\Interfaces\IActivate $activate
	 * @param \Libertyware\TravelSystem\Payments\Core\Interfaces\IDeactivate $deactivate
	 */
	public function __construct(
		\Libertyware\TravelSystem\Payments\Core\Interfaces\IActivate $activate,
		\Libertyware\TravelSystem\Payments\Core\Interfaces\IDeactivate $deactivate
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
