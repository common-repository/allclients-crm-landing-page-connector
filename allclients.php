<?php
/**
 * AllClients.com Landing Page Integration
 *
 * Integrates WordPress with AllClients
 *
 * @package AllClients
 */

/**
 * @wordpress-plugin
 * Plugin Name:       AllClients.com Landing Page Integration
 * Plugin URI:        https://wordpress.org/plugins/allclients/
 * Description:       Integrates WordPress with AllClients
 * Version:           1.0.2
 * Author:            AllClients
 * Author URI:        http://www.allclients.com/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Require base component classes and activation & deactivation hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-allclients-component.php';
require_once plugin_dir_path( __FILE__ ) . 'components/core/class-allclients-core.php';
require_once plugin_dir_path( __FILE__ ) . 'components/core/class-allclients-core-activator.php';
require_once plugin_dir_path( __FILE__ ) . 'components/core/class-allclients-core-deactivator.php';

/**
 * Register hooks for activation and deactivation.
 */
register_activation_hook( __FILE__, array( 'AllClients_Core_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AllClients_Core_Deactivator', 'deactivate' ) );

/**
 * Define encryption key for obscuring sensitive data
 */
if ( ! defined( 'ALLCLIENTS_KEY' ) ) {
	define( 'ALLCLIENTS_KEY', 'ev%45H^9w2zC' );
}

/**
 * Define API endpoint
 */
if ( ! defined( 'ALLCLIENTS_API_ENDPOINT' ) ) {
	define( 'ALLCLIENTS_API_ENDPOINT', 'http://www.allclients.com/api/2/' );
}

/**
 * Get plugin instance.
 *
 * @return AllClients_Core
 */
function get_allclients() {
	global $allclients_plugin;

	if ( !isset( $allclients_plugin ) ) {

		/**
		 * Initialize plugin.
		 */
		$allclients_plugin = new AllClients_Core( 'allclients', '1.0.2', plugin_dir_path( dirname( __FILE__ ) . '/components' ) );

		/**
		 * Load component definitions.
		 */
		$components = include plugin_dir_path( __FILE__ ) . 'components/allclients-components.php';

		/**
		 * Add components to core.
		 */
		foreach ( $components as $component ) {
			$allclients_plugin->add_component( $component['name'], $component['namespace'], $component['options'] );
		}

		/**
		 * Run the plugin.
		 */
		$allclients_plugin->run();

	}

	return $allclients_plugin;
}

/**
 * Begins execution of the plugin.
 */
get_allclients();
