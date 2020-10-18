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

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use \Libertyware\TravelSystem\Wordpress\WordpressApiProvider;
use \Libertyware\TravelSystem\Payments\PaymentsProvider;


$container = new League\Container\Container();

$container->delegate( ( new League\Container\ReflectionContainer() )->cacheResolutions() );
$container->inflector( ContainerAwareInterface::class )->invokeMethod( 'setContainer', [ $container ] );

$container->addServiceProvider( new PaymentsProvider() );
$container->addServiceProvider( new WordpressApiProvider() );

/** @var Libertyware\TravelSystem\Payments\Init::class $init */
$init = $container->get( \Libertyware\TravelSystem\Payments\Init::class );

if ( isset( $init ) ) {
	register_activation_hook( __FILE__, $init->activate );
	register_deactivation_hook( __FILE__, $init->deactivate );
} else {
	exit;
}
