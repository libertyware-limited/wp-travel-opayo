<?php

/**
 * @package wp-travel-opayo
 */

namespace Libertyware\TravelSystem\Payments\Core;

use Libertyware\TravelSystem\Payments\Core\Interfaces\IDeactivate;

class Deactivate implements IDeactivate {
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
