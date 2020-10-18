<?php

/**
 * @package wp-travel-opayo
 */

namespace Libertyware\TravelSystem\Payments\Core;

use Libertyware\TravelSystem\Payments\Core\Interfaces\IActivate;

class Activate implements IActivate {
	public static function activate() {
		flush_rewrite_rules();
	}
}
