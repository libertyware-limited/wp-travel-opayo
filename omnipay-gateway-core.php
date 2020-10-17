<?php
/**
 * Modules core file.
 *
 * @package libertyware-omnipay-gateway-core
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

if ( file_exists (dirname(__FILE__) . 'vendor/autoload.php') ) {
  require_once dirname(__FILE__) . 'vendor/autoload.php';
}

?>