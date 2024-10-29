<?php

/**
 * API class.
 *
 * Used to facilitate communication with the API.
 *
 * @package    AllClients
 * @subpackage AllClients/includes
 */
class AllClients_API {

	/**
	 * @var string API endpoint
	 */
	private $endpoint;

	/**
	 * @var int Default API timeout
	 */
	private $timeout = 30;

	/**
	 * @var string API key
	 */
	private $key;

	/**
	 * @var int Account ID
	 */
	private $account;

	/**
	 * @var array Allowed functions
	 */
	private $allowed_functions;

	/**
	 * @var AllClients_API_Method[]
	 */
	private $methods;

	/**
	 * @var string Last error message
	 */
	private $last_error;

	/**
	 * @var string Cache token to append to keys
	 */
	private $cache_token;

	/**
	 * Instantiate API class.
	 *
	 * @param string $endpoint
	 * @param string $key
	 * @param int    $account_id
	 * @param array  $allow_functions
	 */
	public function __construct( $endpoint = null, $key = null, $account_id = null, $allow_functions = array() )
	{
		if ( !is_null( $endpoint ) ) {
			$this->set_endpoint( $endpoint );
		}
		if ( !is_null( $key  )) {
			$this->set_key( $key );
		}
		if ( !is_null( $account_id ) ) {
			$this->set_account_id( $account_id );
		}
		$this->methods = array();
		$this->allowed_functions = array();
		foreach ($allow_functions as $function_name) {
			$this->allow_function($function_name);
		}
	}

	/**
	 * Set API endpoint
	 *
	 * @param string $endpoint
	 */
	public function set_endpoint( $endpoint ) {
		$this->endpoint = $endpoint;
	}

	/**
	 * Set API key
	 *
	 * @param string $key
	 */
	public function set_key( $key ) {
		$this->key = $key;
	}

	/**
	 * Set API account ID.
	 *
	 * @param int $account_id
	 */
	public function set_account_id( $account_id ) {
		$this->account = (int) $account_id;
	}

	/**
	 * Get API account ID
	 *
	 * @return int
	 */
	public function get_account_id() {
		return $this->account;
	}

	/**
	 * Test API with current configuration.
	 *
	 * @return boolean
	 */
	public function test() {
		$this->get( 'GetCategories', array(), 7200, array( 'validate' => false ) );
		return empty( $this->last_error );
	}

	/**
	 * Get last API error message.
	 *
	 * @return bool|string
	 */
	public function get_last_error() {
		return !empty( $this->last_error ) ? $this->last_error : false;
	}

	/**
	 * Set cache token, which is appended to cache keys
	 *
	 * @param string $cache_token
	 */
	public function set_cache_token($cache_token) {
		$this->cache_token = $cache_token;
	}

	/**
	 * Allow API function to be called.
	 *
	 * @param string   $function_name
	 * @param int|null $default_cache
	 * @param array    $default_options
	 *
	 * @return AllClients_API
	 */
	public function allow_function( $function_name, $default_cache = null, array $default_options = array() ) {
		$this->allowed_functions[$function_name] = array(
			'cache'   => $default_cache,
			'options' => $default_options,
		);
		return $this;
	}

	/**
	 * Add API method.
	 *
	 * @param AllClients_API_Method $method
	 *
	 * @throws Exception
	 */
	public function add_method( AllClients_API_Method $method ) {
		if ( ! $method->get_name() ) {
			throw new Exception( 'AllClients_API_Method::add_method requires a method name.' );
		}
		$this->methods[$method->get_name()] = $method;
	}

	/**
	 * Add API methods.
	 *
	 * @param AllClients_API_Method[] $methods
	 *
	 * @throws Exception
	 */
	public function add_methods( array $methods ) {
		foreach ( $methods as $method ) {
			$this->add_method( $method );
		}
	}

	/**
	 * Get API method by name.
	 *
	 * @param string $method_name
	 *
	 * @return AllClients_API_Method|null
	 */
	public function get_method( $method_name ) {
		return ( array_key_exists( $method_name, $this->methods )
			? $this->methods[$method_name]
			: null
		);
	}

	/**
	 * Checks for valid configuration
	 *
	 * @return bool
	 */
	public function configured() {
		return (
			filter_var( $this->endpoint, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED ) &&
			filter_var( $this->account, FILTER_VALIDATE_INT ) && $this->account > 0 &&
			is_string( $this->key ) && preg_match( '/[A-F0-9]{32}$/', $this->key )
		);
	}

	/**
	 * Call API method.
	 *
	 * @param    string    $method_name
	 * @param    array     $data
	 * @param    int|null  cache_ttl
	 * @param    array     $options
	 *
	 * @return array|false
	 * @throws Exception
	 */
	public function method( $method_name, array $data = array(), $cache_ttl = null, $options = array() ) {
		if ( ! $method = $this->get_method( $method_name ) ) {
			throw new Exception( sprintf( 'Method name `%s` does not exist.', $method_name ) );
		}

		/**
		 * If cache ttl not specified use the defined ttl, if any
		 */
		if (is_null( $cache_ttl ) ) {
			$cache_ttl = $method->get_cache_ttl();
		}

		/**
		 * Merge options
		 */
		$options = array_merge_recursive( $method->get_options(), $options );

		return $this->get( $method_name, $data, $cache_ttl, $options );
	}

	/**
	 * @param string $function_name The API method name
	 * @param array $data Request data to post
	 * @param int|null $cache_ttl Cache time to live
	 *
	 * @param array $options Override defaults options
	 *
	 * @return array|false
	 */
	public function get( $function_name, $data = array(), $cache_ttl = null, $options = array() ) {

		// Clear last API error
		$this->last_error = null;

		// Get API url
		$url = $this->url( $function_name, !isset( $options['validate'] ) ? true : $options['validate'] );

		// API request
		$request = array(
			'method' => 'POST',
			'timeout' => isset($options['timeout']) ? $options['timeout'] : $this->timeout,
			'headers' => array(),
			'body' => array_merge_recursive(array(
				'accountid' => (int) $this->account,
				'apikey' => $this->key,
			), $data),
			'cookies' => array(),
		);

		// Cache response if TTL specified
		if ( filter_var( $cache_ttl, FILTER_VALIDATE_INT )  && $cache_ttl > 0) {
			$cache_hash = array( $url, $request, $this->cache_token );
			$cache_id   = __CLASS__ . '_api_' . substr( md5( serialize( $cache_hash ) ), 0, 12 );
			$response_xml = wp_cache_get( $cache_id, '');
			if ( $response_xml === false ) {
				// Post API request and cache response
				$response_xml = wp_remote_post($url, $request);
				wp_cache_set( $cache_id, $response_xml, '', $cache_ttl );
			}
		} else {
			// Post API request
			$response_xml = wp_remote_post($url, $request);
		}

		// Fail on WordPress http error
		if ( is_wp_error( $response_xml ) ) {
			$this->last_error = $response_xml->get_error_message();
			return false;
		}

		// Default expected return format to XML
		$format = isset( $options['format'] ) ? $options['format'] : 'xml';
		if ( $format === 'html' ) {
			if ( $response_xml['headers']['content-type'] === 'text/html' ) {
				return $response_xml['body'];
			}
		}

		// Parse API response as XML
		$xmlDoc = new DOMDocument();
		if ( false === $xmlDoc->loadXML( $response_xml['body'] ) ) {
			$this->last_error = 'API response not parsed as XML.';
			return false;
		}

		// Get results node
		$results = $xmlDoc->getElementsByTagName( 'results' );
		if ( $results->length !== 1) {
			$this->last_error = 'API response results node not returned.';
			return false;
		}
		$results = $results->item( 0 );

		// Check for error response
		if ( $results->childNodes->length === 1 && $xmlDoc->getElementsByTagName( 'error' )->length === 1 ) {
			$this->last_error = $xmlDoc->getElementsByTagName( 'error' )->item( 0 )->nodeValue;
			return 0;
		}

		// Convert XML to array and return
		$results = $results->firstChild;

		// Return raw results
		if ( isset( $options['raw'] ) && $options['raw'] === true ) {
			return $results;
		}

		// XML array response parsing
		if ( array_key_exists( 'array', $options )) {
			$array = $this->xml_to_array( $results, $options['array'] );
			if ( ! empty( $array ) && array_key_exists( $options['array'], $array ) ) {
				return $array[$options['array']];
			} else {
				return array();
			}
		}

		return $this->xml_to_array( $results );
	}

	/**
	 * Get API function url by function name
	 *
	 * @param string  $function_name
	 * @param boolean $validate
	 *
	 * @return string
	 */
	private function url( $function_name, $validate = true ) {

		// Verify function is allowed
		if ( $validate === true && !$this->get_method( $function_name ) ) {
			_doing_it_wrong(__CLASS__ . "." . __METHOD__ . $function_name, __('Function is not allowed.'), '1.0.2' );
		}

		return sprintf( '%s/%s.aspx', rtrim( $this->endpoint, '/' ), $function_name );
	}

	/**
	 * Convert XML response to array.
	 * 
	 * @param DOMDocument|DOMNode $node
	 * @param string $item_node
	 *
	 * @return string
	 */
	private function xml_to_array( $node, $item_node = null ) {
		$result = array();

		if ($node->hasAttributes()) {
			$attributes = $node->attributes;
			foreach ($attributes as $attr) {
				$result['@attributes'][$attr->name] = $attr->value;
			}
		}
		if ($node->hasChildNodes()) {
			$children = $node->childNodes;
			if ( $children->length == 1 ) {
				$child = $children->item(0);
				if ( $child->nodeType == XML_TEXT_NODE ) {
					$result['_value'] = $child->nodeValue;
					return count( $result ) == 1
						? $result['_value']
						: $result;
				}
			}
			$groups = array();
			foreach ($children as $child) {
				if (!isset($result[$child->nodeName])) {
					if ( $child->nodeName === $item_node ) {
						$result[ $child->nodeName ] = array( $this->xml_to_array( $child, $item_node ) );
						$groups[$child->nodeName] = 1;
					} else {
						$result[ $child->nodeName ] = $this->xml_to_array( $child, $item_node );
					}
				} else {
					if (!isset($groups[$child->nodeName])) {
						$result[$child->nodeName] = array( $result[$child->nodeName] );
						$groups[$child->nodeName] = 1;
					}
					$result[$child->nodeName][] = $this->xml_to_array( $child, $item_node );
				}
			}
		}

		return $result;
	}

}
