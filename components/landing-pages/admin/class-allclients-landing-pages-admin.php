<?php

/**
 * Landing pages admin functionality.
 *
 * @package    AllClients
 * @subpackage AllClients/landing-pages
 */
class AllClients_Landing_Pages_Admin {

	/**
	 * @var string The plugin name
	 */
	private $plugin_name;

	/**
	 * @var string The current version of this plugin.
	 */
	private $version;

	/**
	 * @var array Post type configuration.
	 */
	private $post_type;

	/**
	 * @param string $plugin_name Plugin name
	 * @param string $version     Plugin version
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Set post type configuration.
	 *
	 * @param array $post_type
	 */
	public function set_post_type(array $post_type) {
		$this->post_type = $post_type;
	}

	/**
	 * Add meta data box to landing page post form and save hook.
	 */
	public function add_meta_box() {
		$post_type = $this->post_type['type'];
		add_meta_box( $post_type . '_select', 'Configure Landing Page', 'allclients_landing_pages_meta_box_callback', $post_type, 'normal' );
		add_action( 'save_post', 'allclients_landing_pages_meta_box_save' );
	}

	/**
	 * Add ajax actions to fetch landing pages over XHR.
	 */
	public function add_get_pages_action() {
		add_action( 'wp_ajax_allclients_get_landing_pages', 'allclients_ajax_get_landing_pages' );
		add_action( 'wp_ajax_allclients_get_landing_page', 'allclients_ajax_get_landing_page' );
		add_action( 'wp_ajax_allclients_update_landing_pages', 'allclients_ajax_update_landing_pages' );
	}

	/**
	 * Update names and titles admin banner
	 */
	public function update_notice() {
		wp_enqueue_script( $this->plugin_name . '-landing-admin-update', plugin_dir_url( __FILE__ ) . 'js/update.js', array( 'jquery' ), $this->version, false );
		echo '
			<div class="notice" id="landing-page-update">
				<p>
					<div style="float: left; display: none; padding-right: 5px;"><img src="'.admin_url() . 'images/wpspin_light.gif" alt=""></div>
					<span class="message"><a href="#">Click here</a> to update titles and names from the API.</span>
				</p>
			</div>
		';
	}

	/**
	 * Admin column headings for custom post type
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function cpt_columns_head( $defaults ) {
		$post_type = $this->post_type['type'];
		return array(
			'cb'                 => $defaults['cb'],
			'title'              => $defaults['title'],
			$post_type . '_name' => 'Name',
			$post_type . '_type' => 'Type',
			$post_type . '_link' => 'Link',
			'date'               => $defaults['date'],
		);
	}
	
	/**
	 * Admin columns for custom post type
	 *
	 * @param string $column_name
	 * @param int    $post_ID
	 *
	 * @return array
	 */
	public function cpt_columns_content( $column_name, $post_ID ) {
		$post_type = $this->post_type['type'];
		switch ($column_name) {
			case $post_type.'_name':
				if (!get_post_meta( $post_ID, 'landing_page_name', true )) {
					echo get_the_title( $post_ID );
				} else {
					echo get_post_meta( $post_ID, 'landing_page_name', true );
				}
				break;
			case $post_type.'_type':
				switch (get_post_meta( $post_ID, 'landing_page_type', true )) {
					case 'homepage':
						echo '<strong>Homepage</strong>';
						break;
					default:
					case 'normal':
						echo 'Page';
						break;
				}
				break;
			case $post_type.'_link':
				if ( get_post_meta( $post_ID, 'landing_page_type', true ) === 'homepage' ) {
					$link = home_url();
				} else {
					$link = get_permalink( $post_ID );
				}
				printf('<a href="%s">%s</a>', $link, url_shorten($link));
				break;
		}
	}

	/**
	 * Register the JavaScript for the post type screens, and remove auto-save option.
	 */
	public function enqueue_scripts() {
		$post_type = $this->post_type['type'];
		wp_enqueue_script( $this->plugin_name . '-landing-admin-scripts', plugin_dir_url( __FILE__ ) . 'js/settings.js', array( 'jquery' ), $this->version, false );
		if ( $post_type === get_post_type() ) {
			wp_enqueue_script( $this->plugin_name . '-landing-admin-meta-box', plugin_dir_url( __FILE__ ) . 'js/meta-box.js', array( 'jquery' ), $this->version, false );
			wp_dequeue_script( 'autosave' );
		}
	}

}
