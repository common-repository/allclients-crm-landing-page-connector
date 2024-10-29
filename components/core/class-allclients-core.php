<?php

/**
 * The core plugin component.
 *
 * @package    AllClients
 * @subpackage AllClients/core
 */
class AllClients_Core extends AllClients_Component {

	/**
	 * @var string The string used to uniquely identify the plugin.
	 */
	protected $plugin_name;

	/**
	 * @var string The path of the plugin.
	 */
	protected $plugin_path;

	/**
	 * @var string The current version of the plugin.
	 */
	protected $plugin_version;

	/**
	 * @var AllClients_API The API instance
	 */
	protected $api;

	/**
	 * @var AllClients_Component[] Plugin components
	 */
	protected $components;

	/**
	 * Overrides component constructor to instantiate plugin core component.
	 *
	 * @param string $plugin_name        Plugin name.
	 * @param string $plugin_version     Plugin version.
	 * @param string $plugin_path        Plugin path.
	 */
	public function __construct($plugin_name, $plugin_version, $plugin_path) {

		/**
		 * Core plugin information.
		 */
		$this->plugin_name    = $plugin_name;
		$this->plugin_path    = $plugin_path;
		$this->plugin_version = $plugin_version;

		/**
		 * Core component initialization.
		 */
		$this->name = 'core';
		$this->init( array(
			'title' => 'Core',
		) );
	}

	/**
	 * Note: get_plugin_ methods are overridden from component class
	 * since that information is in core component
	 */

	/**
	 * {@inheritdoc}
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_plugin_version() {
		return $this->plugin_version;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_plugin_path() {
		return $this->plugin_path;
	}

	/**
	 * Activate WordPress hooks for the core component and sub-components.
	 */
	public function run() {

		/**
		 * Activate core hooks.
		 */
		$this->loader->run();

		/**
		 * Activate component hooks.
		 */
		foreach ( $this->components as $component ) {
			if ( $component->activated() ) {
				$component->run();
			}
		}

	}

	/**
	 * Add component by component name.
	 *
	 * @param string    $name      Component name, used for file names.
	 * @param string    $namespace Component namespace.
	 * @param array     $options   Component options.
	 *
	 * @return boolean    true if successfully added
	 */
	public function add_component($name, $namespace, array $options = array()) {

		/**
		 * Component class file and class name.
		 */
		$class_file = 'class-' . $this->get_plugin_name() . '-' . $name . '.php';
		$class_name = $this->get_plugin_namespace() . '_' . $namespace;
		require_once $this->plugin_path . 'components/' . $name . '/' . $class_file;

		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		// Add component
		$this->components[] = new $class_name( $this, $name, $options );
		return true;
	}

	/**
	 * Get components.
	 *
	 * @return AllClients_Component[]
	 */
	public function get_components() {
		return $this->components;
	}

	/**
	 * Get component by name
	 *
	 * @param string $component_name
	 *
	 * @return AllClients_Component|false
	 */
	public function get_component( $component_name ) {
		foreach ( $this->components as $component ) {
			if ( $component->get_name() === $component_name ) {
				return $component;
			}
		}
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register_settings() {

		/**
		 * API settings
		 */
		register_setting( $this->plugin_name, $this->get_option_name( 'api_key' ), 'allclients_encrypt' );
		register_setting( $this->plugin_name, $this->get_option_name( 'api_accountid' ) );
		register_setting( $this->plugin_name, $this->get_option_name( 'api_cache_token' ) );

		/**
		 * Component settings
		 */
		foreach ( $this->get_components() as $component ) {
			register_setting( $this->plugin_name, $component->get_option_name( 'activated' ) );
			$component->register_settings();
		}

	}

	/**
	 * Set the API instance, adding methods specific to component
	 *
	 * @param AllClients_API $api
	 */
	public function set_api( AllClients_API $api ) {
		$this->api = $api;
	}

	/**
	 * Get API instance
	 *
	 * @return    AllClients_API    The API instance
	 */
	public function get_api() {

		/**
		 * Instantiate API
		 */
		if ( ! isset( $this->api ) ) {

			/**
			 * Include the API class and apply settings
			 */
			require_once $this->plugin_path . 'includes/class-' . $this->get_plugin_name() . '-api.php';
			require_once $this->plugin_path . 'includes/class-' . $this->get_plugin_name() . '-api-method.php';

			$api = new AllClients_API();
			$api->set_endpoint( ALLCLIENTS_API_ENDPOINT );
			$api->set_key( allclients_decrypt( $this->get_option( 'api_key' ) ) );
			$api->set_account_id( $this->get_option( 'api_accountid' ) );
			$api->set_cache_token( $this->get_option( 'api_cache_token' ) );

			/**
			 * Apply core API methods
			 */
			$this->add_api_methods($api);

			/**
			 * Apply component API methods
			 */
			foreach ( $this->components as $component ) {
				$component->add_api_methods($api);
			}

			$this->api = $api;

		}
		return $this->api;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function define_admin_hooks() {

		/**
		 * Require settings dependencies.
		 */
		require_once $this->path . 'settings/class-' . $this->get_plugin_name() . '-core-settings.php';
		require_once $this->path . 'settings/partials/settings-page.php';

		/**
		 * Add core settings callbacks.
		 */
		$plugin_settings = new AllClients_Core_Settings( $this );
		$this->loader->add_action( 'admin_menu', $plugin_settings, 'add_settings_link' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'add_settings_api' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'add_plugin_action_links' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_settings, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_settings, 'enqueue_scripts' );

	}

}
