<?php

if ( !function_exists( 'allclients_settings_href' )) {
	/**
	 * Get link to plugin settings page
	 *
	 * @return string
	 */
	function allclients_settings_href() {
		return 'options-general.php?page=' . get_allclients()->get_plugin_name();
	}
}

if ( !function_exists( 'allclients_components_array' )) {
	/**
	 * Get array of components
	 *
	 * @return array
	 */
	function allclients_components_array() {
		$components = array();
		foreach ( get_allclients()->get_components() as $component ) {
			$components[] = array(
				'name'      => $component->get_name(),
				'title'     => $component->get_title(),
				'activated' => $component->activated()
			);
		}

		return $components;
	}
}

if ( !function_exists( 'allclients_component_settings_include' )) {
	/**
	 * Get component settings include
	 *
	 * @param string $component_name
	 *
	 * @return string|false
	 */
	function allclients_component_settings_include( $component_name ) {
		if (!$component = get_allclients()->get_component( $component_name ) ) {
			return false;
		}
		return $component->get_settings_include();
	}
}

if ( !function_exists( 'allclients_encrypt' )) {
	/**
	 * Encrypt string with plugin encryption key
	 *
	 * @param string $input_string
	 *
	 * @return bool
	 */
	function allclients_encrypt( $input_string ) {
		$key = constant('ALLCLIENTS_KEY' );
		if ( empty( $key ) || ! function_exists( 'mcrypt_get_iv_size' ) ) {
			return $input_string;
		}

		/**
		 * WP settings API calls sanitize callback twice when a setting first configured by user,
		 * which will double encode the key.
		 */
		global $allclients_encrypt_cache;
		if ( !isset( $allclients_encrypt_cache ) ) {
			$allclients_encrypt_cache = array();
		} else {
			if ( in_array( $input_string, $allclients_encrypt_cache ) ) {
				return $input_string;
			}
		}

		$iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
		$iv	  = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
		$h_key   = hash( 'sha256', $key, true );

		$encrypted = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $h_key, $input_string, MCRYPT_MODE_ECB, $iv ) );
		$allclients_encrypt_cache[] = $encrypted;
		return $encrypted;
	}
}

if ( !function_exists( 'allclients_decrypt' )) {
	/**
	 * Decrypt string with plugin encryption key
	 *
	 * @param string $encrypted_input_string
	 *
	 * @return bool
	 */
	function allclients_decrypt( $encrypted_input_string ) {
		$key = constant('ALLCLIENTS_KEY' );
		if ( empty( $key ) || ! function_exists( 'mcrypt_get_iv_size' ) ) {
			return $encrypted_input_string;
		}
		$iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
		$iv      = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
		$h_key   = hash( 'sha256', $key, true );

		return trim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $h_key, base64_decode( $encrypted_input_string ), MCRYPT_MODE_ECB, $iv ) );
	}
}

if ( !function_exists( 'allclients_get_option' )) {
	/**
	 * @see AllClients_Component::get_option
	 *
	 * @param string $option Name of option to retrieve. Expected to not be SQL-escaped.
	 * @param mixed $default Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed Value set for the option.
	 */
	function allclients_get_option( $option, $default = false ) {
		return get_allclients()->get_option( $option, $default );
	}
}

if ( !function_exists( 'allclients_get_option_name' )) {
	/**
	 * @see AllClients_Component::get_option_name
	 *
	 * @param string $option
	 *
	 * @return string
	 */
	function allclients_get_option_name( $option ) {
		return get_allclients()->get_option_name( $option );
	}
}

if ( !function_exists( 'allclients_get_component_option' )) {
	/**
	 * @see AllClients_Component::get_option
	 *
	 * @param string $component Component name of option to retrieve.
	 * @param string $option Name of option to retrieve. Expected to not be SQL-escaped.
	 * @param mixed $default Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed Value set for the option.
	 */
	function allclients_get_component_option( $component, $option, $default = false ) {
		if ( ! $component = get_allclients()->get_component( $component ) ) {
			return false;
		}
		return $component->get_option( $option, $default );
	}
}

if ( !function_exists( 'allclients_get_component_option_name' )) {
	/**
	 * @see AllClients_Component::get_component_option_name
	 *
	 * @param string $component
	 * @param string $option
	 *
	 * @return string
	 */
	function allclients_get_component_option_name( $component, $option ) {
		if ( ! $component = get_allclients()->get_component( $component ) ) {
			return false;
		}
		return $component->get_option_name( $option );
	}
}

if ( !function_exists( 'allclients_api_test' )) {
	/**
	 * Test API configuration
	 *
	 * @return bool
	 */
	function allclients_api_test() {
		return get_allclients()
			->get_api()
			->test();
	}
}

if ( !function_exists( 'allclients_api_last_error' )) {
	/**
	 * Get last error message from API
	 *
	 * @return string|bool
	 */
	function allclients_api_last_error() {
		return get_allclients()
			->get_api()
			->get_last_error();
	}
}
