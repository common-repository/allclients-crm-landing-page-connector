<?php

/**
 * Abstract plugin component class.
 *
 * @package    AllClients
 * @subpackage AllClients/includes
 */
abstract class AllClients_Component {

	/**
	 * @var AllClients_Core Plugin core
	 */
	protected $core;

	/**
	 * @var string The string used to uniquely identify the component.
	 */
	protected $name;

	/**
	 * @var string Component title.
	 */
	protected $title;

	/**
	 * @var array Component options.
	 */
	protected $options;

	/**
	 * @var string The path to the component.
	 */
	protected $path;

	/**
	 * @var string The current version of the component.
	 */
	protected $version;

	/**
	 * @var AllClients_Loader Maintains and registers all hooks for the plugin component.
	 */
	protected $loader;

	/**
	 * Instantiate a plugin component.
	 *
	 * @param AllClients_Core $core       Plugin core component.
	 * @param string          $name       Component name.
	 * @param array           $options    Component options.
	 */
	public function __construct(AllClients_Core $core, $name, array $options = array() ) {
		$this->core = $core;
		$this->name = $name;
		$this->init( $options );
	}

	/**
	 * Initialize component.
	 *
	 * @param array $options Component options.
	 */
	protected function init( array $options = array() ) {

		/**
		 * Set component path.
		 */
		$this->path = $this->get_plugin_path() . 'components/' . $this->name . '/';

		/**
		 * Set component options.
		 */
		$this->set_options( $options );

		/**
		 * If component version not set, use the plugin version.
		 */
		if ( empty( $this->version ) ) {
			$this->version = $this->get_plugin_version();
		}

		$this->load_dependencies();
		$this->loader->add_action( 'admin_init', $this, 'register_settings' );
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Set component options.
	 *
	 * @param array $options The component options.
	 */
	protected function set_options( array $options = array() ) {

		/**
		 * Component title.
		 */
		if ( isset( $options['title'] ) ) {
			$this->title = $options['title'];
			unset( $options['title'] );
		}

		/**
		 * Store remaining options in array.
		 */
		$this->options = $options;

	}

	/**
	 * Load the required dependencies.
	 */
	protected function load_dependencies() {

		/**
		 * Require component API file, which contains API convenience methods specific to the component.
		 */
		require_once $this->path . $this->get_plugin_name() . '-' . $this->name . '-api.php';

		/**
		 * Create WordPress action & filter loader for component
		 */
		require_once $this->get_plugin_path() . 'includes/class-' . $this->get_plugin_name() . '-loader.php';

		/**
		 * Initialize loader
		 */
		$loader_class = $this->get_plugin_namespace() . '_Loader';
		$this->loader = new $loader_class();
	}

	/**
	 * Called on API initialization to add allowed API methods.
	 *
	 * @param AllClients_API $api
	 */
	public function add_api_methods(AllClients_API $api) {}

	/**
	 * Register all of the hooks related to the dashboard functionality.
	 */
	protected function define_admin_hooks() {}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 */
	protected function define_public_hooks() {}

	/**
	 * Activate WordPress hooks for the component.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Retrieve the name of the plugin.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->core->get_plugin_name();
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string The version number of the plugin.
	 */
	public function get_plugin_version() {
		return $this->core->get_plugin_path();
	}

	/**
	 * The path of the plugin.
	 *
	 * @return string The name of the plugin.
	 */
	public function get_plugin_path() {
		return $this->core->get_plugin_path();
	}

	/**
	 * @return string
	 */
	public function get_plugin_namespace() {
		return 'AllClients';
	}

	/**
	 * Retrieve the name of the component.
	 *
	 * @return string The name of the component.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Retrieve the title of the component.
	 *
	 * @return string The title of the component.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Retrieve the version number of the component.
	 *
	 * @return string The version number of the component.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return AllClients_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Is component activated
	 *
	 * @return bool
	 */
	public function activated() {
		return $this->get_option( 'activated' );
	}

	/**
	 * Register component settings
	 */
	public function register_settings() {}

	/**
	 * Gets WP option including plugin name.
	 *
	 * @param string $option Name of option to retrieve. Expected to not be SQL-escaped.
	 * @param mixed $default Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed Value set for the option.
	 */
	public function get_option( $option, $default = false ) {
		return get_option( $this->get_option_name($option), $default );
	}

	/**
	 * Get sanitized option name prefixed by plugin name.
	 *
	 * @param string $option
	 *
	 * @return string
	 */
	public function get_option_name( $option ) {
		$name = str_replace( '-', '_', $this->get_plugin_name() );
		if ( $this->name !== 'core' ) {
			$name .= '_' . str_replace( '-', '_', $this->get_name() );
		}
		return $name . '_' . $option;
	}

	/**
	 * Get component settings partial
	 *
	 * @return null|string
	 */
	public function get_settings_include() {
		return null;
	}

}
