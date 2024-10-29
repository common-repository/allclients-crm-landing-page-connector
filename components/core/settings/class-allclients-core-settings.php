<?php

/**
 * Plugin core settings hooks in wp-admin
 *
 * @package    AllClients
 * @subpackage AllClients/core
 */
class AllClients_Core_Settings {

	/**
	 * @var string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * @var string The current version of this plugin.
	 */
	private $version;

	/**
	 * @var AllClients_Core Plugin core.
	 */
	private $core;

	/**
	 * Instantiate core settings.
	 *
	 * @param AllClients_Core $core
	 */
	public function __construct( AllClients_Core $core ) {
		$this->plugin_name = $core->get_plugin_name();
		$this->version     = $core->get_plugin_version();
		$this->core        = $core;
	}

	/**
	 * Add navigation link to settings
	 */
	public function add_settings_link() {
		$hook = add_options_page( 'AllClients Plugin Settings', 'AllClients Plugin', 'administrator', $this->plugin_name, 'allclients_core_settings_page' );
		add_action( 'load-'.$hook, 'allclients_core_settings_api_save' );
	}

	/**
	 * Add settings link to plugin page
	 */
	public function add_plugin_action_links() {
		add_filter( 'plugin_action_links', 'allclients_core_action_links', 10, 4 );
	}

	/**
	 * Add ajax action to test API settings and clear cache
	 */
	public function add_settings_api() {
		add_action( 'wp_ajax_allclients_api_test', 'allclients_core_settings_api_test' );
		add_action( 'wp_ajax_allclients_clear_cache', 'allclients_core_settings_clear_cache' );
	}

	/**
	 * Register the stylesheets for the Dashboard.
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name . '-common', plugin_dir_url( __FILE__ ) . 'css/common.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-settings', plugin_dir_url( __FILE__ ) . 'css/settings.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the dashboard.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name . '-settings', plugin_dir_url( __FILE__ ) . 'js/settings.js', array( 'jquery' ), $this->version, false );
	}

}
