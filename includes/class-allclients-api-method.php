<?php

/**
 * API method class.
 *
 * @package    AllClients
 * @subpackage AllClients/includes
 */
class AllClients_API_Method {

	/**
	 * @var string Method name.
	 */
	private $name;

	/**
	 * @var string|null Method description.
	 */
	private $description;

	/**
	 * @var int|null Cache time to live.
	 */
	private $cache_ttl;

	/**
	 * @var array Method parameters
	 */
	private $parameters;

	/**
	 * @var array Method options
	 */
	private $options;

	/**
	 * Instantiate API method class.
	 *
	 * @param string $name
	 * @param string $description
	 * @param int    $cache_ttl
	 * @param array  $parameters
	 * @param array  $options
	 */
	public function __construct( $name = null, $description = null, $cache_ttl = null, $parameters = array(), $options = array() )
	{
		$this->set_name( $name );
		$this->set_description( $description );
		$this->set_cache_ttl( $cache_ttl );
		$this->set_parameters( $parameters );
		$this->set_options( $options );
	}

	/**
	 * Set method name.
	 *
	 * @param string $endpoint
	 */
	public function set_name( $endpoint ) {
		$this->name = $endpoint;
	}

	/**
	 * Get method name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Set cache time to live
	 *
	 * @param string $cache_ttl
	 */
	public function set_cache_ttl( $cache_ttl ) {
		$this->cache_ttl = $cache_ttl;
	}

	/**
	 * Get cache time to live
	 *
	 * @return string
	 */
	public function get_cache_ttl() {
		return $this->cache_ttl;
	}

	/**
	 * Set method description.
	 *
	 * @param string $key
	 */
	public function set_description( $key ) {
		$this->description = $key;
	}

	/**
	 * Get method description.
	 *
	 * @return null|string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Set method parameters.
	 *
	 * @param array $parameters
	 */
	public function set_parameters( array $parameters = array() ) {
		$this->parameters = $parameters;
	}

	/**
	 * Get method parameters
	 *
	 * @return array
	 */
	public function get_parameters() {
		return $this->parameters;
	}

	/**
	 * Set method options.
	 *
	 * @param array $options
	 */
	public function set_options( array $options = array() ) {
		$this->options = $options;
	}

	/**
	 * Get method options
	 *
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

}
