<?php

/**
 * AllClient landing pages component.
 *
 * @package    AllClients
 * @subpackage AllClients/landing-pages
 */
class AllClients_Landing_Pages extends AllClients_Component {

	/**
	 * @var array Post type configuration.
	 */
	protected $post_type;

	/**
	 * {@inheritdoc}
	 */
	protected function set_options( array $options = array() ) {

		/**
		 * Post type options.
		 */
		if ( isset( $options['post_type'] ) ) {
			$this->post_type = $options['post_type'];
			unset( $options['post_type'] );
		}

		parent::set_options( $options );
	}

	/**
	 * {@inheritdoc}
	 */
	public function register_settings() {
		register_setting( $this->get_plugin_name(), $this->get_option_name( 'slug_type' ) );
		register_setting( $this->get_plugin_name(), $this->get_option_name( 'slug' ) );
	}

	/**
	 * Add landing page post type
	 */
	public function add_post_type() {

		$post_type     = $this->post_type['type'];
		$name          = $this->post_type['name'];
		$singular_name = $this->post_type['singular_name'];
		$menu_icon     = $this->post_type['menu_icon'];

		if ( post_type_exists( $post_type ) ) {
			return;
		}

		$slug_type = (int) $this->get_option( 'slug_type' );
		if ( !$slug_type ) {
			$rewrite = true;
		} else {
			$rewrite = array(
				'slug' => $this->get_option( 'slug' ),
				'with_front' => false,
				'feeds' => false,
				'pages' => false,
				'ep_mask' => EP_NONE,
			);
		}

		register_post_type( $post_type, array(
			'public'    => true,
			'labels'    => array(
				'name'          => $name,
				'singular_name' => $singular_name,
				'add_new_item'  => __( 'Add New ' . $singular_name ),
				'view_item'     => __( 'View ' . $singular_name ),
				'edit_item'     => __( 'Edit ' . $singular_name ),
			),
			'hierarchical' => false,
			'has_archive' => false,
			'supports'  => array( 'title' ),
			'menu_icon' => $menu_icon,
			'rewrite' => $rewrite,
		) );

	}

	/**
	 * Get landing page post type configuration
	 * 
	 * @return array
	 */
	public function get_post_type() {
		return $this->post_type;
	}
	
	/**
	 * Remove custom post type slug
	 *
	 * @param string  $url
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public function custom_post_type_link( $url, $post = null ) {

		/**
		 * Ensure valid, published, post object
		 */
		if ( !gettype($post) == 'post'  || $post->post_status != 'publish') {
			return $url;
		}


		/**
		 * Modify permalink for custom post type
		 */
		if ( $post->post_type === $this->post_type['type'] ) {
			$url = str_replace( "/{$post->post_type}/", "/", $url );
		}

		return $url;
	}

	/**
	 * Add custom post type into query
	 *
	 * @param WP_Query $query
	 */
	public function custom_pre_get_posts( $query ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		/**
		 * Only the query that gets information about the page itself
		 */
		if ( ! $query->is_main_query() ) {
			return;
		}

		/**
		 * Only query posts
		 */
		$page_name = $query->get( 'pagename' );
		$post_name = $query->get( 'name' );
		if ( ! empty( $page_name ) ) {
			return;
		}

		/**
		 * Return if request not in root
		 */
		if ( strstr( $post_name, '/' ) !== false ) {
			return;
		}

		if (is_home()) {
			if ($homepage = $this->query_homepage()) {
				$query->set( $this->post_type['type'], $homepage->post_name );
				$query->set( 'post_type', $homepage->post_type );
				$query->is_single = true;
				$query->is_page   = false;
			}
		} else {
			/**
			 * Fetch the post type
			 */
			$post_query = $wpdb->prepare( 'SELECT post_type FROM ' . $wpdb->posts . ' WHERE post_name = %s LIMIT 1', $post_name );
			$post_type  = $wpdb->get_var( $post_query );
	
			/**
			 * Modify request query for custom post type
			 */
			if ( $post_type === $this->post_type['type'] ) {
				$query->set( $this->post_type['type'], $post_name );
				$query->set( 'post_type', $post_type );
				$query->is_single = true;
				$query->is_page   = false;
			}
		}
	}

	/**
	 * Get landing page set as homepage
	 * 
	 * @return WP_Post|null
	 */
	public function query_homepage() {
		$args = array(
			'post_type'  => $this->post_type['type'],
			'meta_key'   => 'landing_page_type',
			'meta_value' => 'homepage',
		);
		$query = new WP_Query( $args );
		$posts = $query->get_posts();
		return count($posts) === 1 ? $posts[0] : null;
	}

	/**
	 * Update post names and titles from API
	 * 
	 * @return int number of updates
	 */
	public function update_page_data() {
		$updates = 0;
		$pages = allclients_api_get_landing_pages('');
		$query = new WP_Query( $args = array( 'post_type'  => $this->post_type['type'] ) );
		foreach ( $query->get_posts() as $post ) {
			/** @var WP_Post $post */
			$page_id    = get_post_meta( $post->ID, 'landing_page_id', true );
			$account_id = get_post_meta( $post->ID, 'account_id', true );
			$page_name  = get_post_meta( $post->ID, 'landing_page_name', true );
			$page = null;
			for ($i = 0; $i < count($pages); $i++) {
				if ($pages[$i]['webformid'] == $page_id && $pages[$i]['accountid'] == $account_id) {
					$page = $pages[$i];
					break;
				}
			}
			if (!$page) {
				continue; // Flag as not found or removed?
			}
			if (strcmp($page['name'], $page_name) !== 0) {
				update_post_meta( $post->ID, 'landing_page_name', $page['name'] );
				$updates++;
			}
			$page_content = allclients_api_get_landing_page_html( $page_id );
			if ( preg_match( '/<title>([^>]*)<\/title>/si', $page_content, $matches ) && isset( $matches[1] ) ) {
				$title = trim( $matches[1] );
			} else {
				$title = '';
			}
			if (strcmp($title, $post->post_title) !== 0) {
				wp_update_post(array(
					'ID'         => $post->ID,
					'post_title' => $title,
				));
				$updates++;
			}
		}
		return $updates;
	}

	/**
	 * {@inheritdoc}
	 */
	public function add_api_methods(AllClients_API $api) {
		$name          = strtolower( $this->post_type['name'] );
		$singular_name = strtolower( $this->post_type['singular_name'] );
		$api->add_methods( array(
			new AllClients_API_Method( 'GetWebFormFolders', 'Get ' . $singular_name . ' folders.', 60, array(), array( 'array' => 'folder' ) ),
			new AllClients_API_Method( 'GetWebForms', 'Get ' . $name . '.', 60, array( 'folder' ), array( 'array' => 'webform' ) ),
			new AllClients_API_Method( 'GetWebFormDetails', 'Get ' . $singular_name . ' details.', 60, array( 'webformid' ) ),
			new AllClients_API_Method( 'GetWebFormHTML', 'Get ' . $singular_name . ' HTML.', 60, array( 'webformid' ), array( 'format' => 'html' ) ),
		) );
	}

	/**
	 * Filter post by type and change template if landing page.
	 *
	 * @param string $template    Filename to post template
	 *
	 * @return string
	 */
	public function template_include( $template ) {

		/**
		 * Get post type and return current template selection if not landing page
		 */
		$post_id = get_the_ID();
		if ( get_post_type( $post_id ) !== $this->post_type['type'] ) {
			return $template;
		}

		/**
		 * Check if custom theme in user folder or return plugin default.
		 */
		if ( $theme_file = locate_template( $this->get_plugin_name() . '_template/' . $this->get_name() . '/single.php')) {
			return $theme_file;
		} else {
			return $this->path . 'public/single.php';
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function get_settings_include() {
		ob_start();
		allclients_landing_pages_settings_include();
		return ob_get_clean();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function define_admin_hooks() {

		/**
		 * Require dependencies.
		 */
		require_once $this->path . 'admin/class-' . $this->get_plugin_name() . '-' . $this->get_name() . '-admin.php';
		require_once $this->path . 'admin/partials/meta-box.php';
		require_once $this->path . 'admin/partials/settings-include.php';

		/**
		 * Load landing pages admin.
		 */
		$plugin_admin = new AllClients_Landing_Pages_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_admin->set_post_type( $this->post_type );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'add_meta_box' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'add_get_pages_action' );
		$this->loader->add_filter( 'manage_'.$this->post_type['type'].'_posts_columns', $plugin_admin, 'cpt_columns_head' );
		$this->loader->add_filter( 'manage_posts_custom_column', $plugin_admin, 'cpt_columns_content', 10, 2 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_filter( 'admin_notices', $plugin_admin, 'update_notice' );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function define_public_hooks() {

		/**
		 * Hook into template loader, so when landing page post type is requested the plugin responds.
		 */
		$this->loader->add_filter( 'template_include', $this, 'template_include' );

		/**
		 * Add the landing pages post type and hooks for scrubbing custom post type slug
		 */
		$this->loader->add_action( 'init', $this, 'add_post_type' );

		if ( ! (int) $this->get_option( 'slug_type' ) ) {
			$this->loader->add_filter( 'post_type_link', $this, 'custom_post_type_link', 10, 2 );
			$this->loader->add_action( 'pre_get_posts', $this, 'custom_pre_get_posts' );
		}

	}

}
