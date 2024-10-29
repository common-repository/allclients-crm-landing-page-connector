<?php

/**
 * Add link(s) to plugin screen
 *
 * @param array  $actions
 * @param string $plugin_file
 * @param array  $plugin_data
 *
 * @return array
 */
function allclients_core_action_links( $actions, $plugin_file, $plugin_data ) {
	if ( $plugin_data['Name'] === 'AllClients CRM Landing Page Connector' && array_key_exists( 'deactivate', $actions ) ) {
		return array_merge( $actions, array(
			'settings' => '<a href="' . admin_url( 'options-general.php?page=allclients' ) . '">Settings</a>',
		) );
	}
	return $actions;
}

function allclients_core_settings_api_test() {
	$key = isset( $_POST['key'] ) ? $_POST['key'] : '';
	$account_id = isset( $_POST['accountid'] ) ? $_POST['accountid'] : '';

	try {
		if ( !$api = get_allclients()->get_api() ) {
			throw new Exception( 'API not instantiated.' );
		}

		// API key
		if ( empty( $key ) ) {
			throw new Exception( 'API key is required.' );
		}
		$api->set_key( $key );

		// API account id
		if ( empty( $account_id ) ) {
			throw new Exception( 'API account ID is required.' );
		}
		$api->set_account_id( $account_id );

		// Test the API
		if (!$api->test()) {
			wp_send_json_error( $api->get_last_error() );
		} else {
			wp_send_json_success( 'Success!' );
		}

	} catch (Exception $e) {
		wp_send_json_error( $e->getMessage() );
	}
}

function allclients_core_settings_api_save() {
	if(isset($_GET['settings-updated']) && $_GET['settings-updated']) {
		flush_rewrite_rules();
	}
}

function allclients_core_settings_clear_cache() {
	try {
		if ( !$api = get_allclients()->get_api() ) {
			throw new Exception( 'API not instantiated.' );
		}

		// Set token to current time
		$cache_token = time();
		set_user_setting( allclients_get_option_name( 'api_cache_token' ), $cache_token );
		$api->set_cache_token( $cache_token );

		wp_send_json_success( 'Cache cleared! ' );

	} catch (Exception $e) {
		wp_send_json_error( $e->getMessage() );
	}
}


function allclients_core_settings_page() {
	$components = allclients_components_array();
	$connected = get_allclients()->get_api()->test();
	?>
	<div class="wrap">
		<h2>AllClients Plugin Settings</h2>
		<form method="post" action="options.php" id="allclients-settings">
			<?php settings_fields( 'allclients' ); ?>
			<h3>API Settings</h3>
			<?php do_settings_sections( 'allclients' ); ?>
			<input type="hidden" name="<?php echo allclients_get_option_name( 'api_key' ); ?>" id="api-key" value="<?php echo esc_attr( allclients_decrypt( allclients_get_option('api_key') ) ); ?>">
			<input type="hidden" name="api-connected" value="<?php echo $connected ? 1 : 0; ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Account ID:</th>
					<td><input type="text" name="<?php echo allclients_get_option_name( 'api_accountid' ); ?>" id="api-accountid" size="40" value="<?php echo esc_attr( allclients_get_option('api_accountid') ); ?>"></td>
				</tr>
				<tr valign="top">
					<th scope="row">API Key:</th>
					<td><input type="text" name="<?php echo allclients_get_option_name( 'api_key' ); ?>-input" id="api-key-input" size="40" value=""></td>
				</tr>
				<tr>
					<th scope="row">&nbsp;</th>
					<td><p>
							<input type="button" name="api-test" class="button btn-api-test" value="Test Connection">
							<input type="button" name="api-cache" class="button btn-api-cache" value="Clear Cache"><span class="api-test-message"></span></p>
					</td>
				</tr>
			</table>

			<?php if ( count( $components ) ) { ?>
				<h3>Components</h3>
				<table class="form-table">
				<?php foreach ( $components as $component) { ?>
					<tr valign="top">
						<th scope="row"><?php echo esc_html( $component['title'] ); ?>:</th>
						<td><input type="checkbox" name="<?php echo allclients_get_component_option_name($component['name'], 'activated') ?>"<?php if (allclients_get_component_option($component['name'], 'activated')) { ?> checked<?php } ?> value="1" class="component-activator"> Enabled</td>
					</tr>
					<?php $component_settings = allclients_component_settings_include( $component['name'] ); ?>
					<?php if ( !empty( $component_settings ) ) { ?>
						<tr valign="top">
							<td colspan="2" class="allclients-component" id="<?php echo allclients_get_component_option_name($component['name'], 'settings') ?>">
								<?php echo $component_settings ?>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
				</table>
			<?php } ?>

			<?php submit_button(); ?>
		</form>
	</div>
<?php
}