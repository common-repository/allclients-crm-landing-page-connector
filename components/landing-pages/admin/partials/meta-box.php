<?php
/**
 * @param WP_Post $post
 */
function allclients_landing_pages_meta_box_callback( $post ) {
	// Get folders from API
	$folders = allclients_api_get_landing_page_folders();
	$homepage = allclients_api_get_landing_page_homepage();

	// Get existing metadata fields
	$landing_page_id          = get_post_meta( $post->ID, 'landing_page_id', true );
	$landing_page_name        = get_post_meta( $post->ID, 'landing_page_name', true );
	$landing_page_folder      = get_post_meta( $post->ID, 'landing_page_folder', true );
	$landing_page_type        = get_post_meta( $post->ID, 'landing_page_type', true );
	$account_id               = get_post_meta( $post->ID, 'account_id', true );

	// New if account id not yet set
	$is_new = empty( $account_id );
	$homepage_enabled = !$homepage || $homepage->ID == $post->ID;

	if ($landing_page_type !== 'homepage' || !$homepage_enabled) {
		$landing_page_type = 'normal';
	}

	// Add a nonce field to hook submit
	wp_nonce_field( 'landing_page_meta_box', 'landing_page_meta_box_nonce' );
	print('<style>');
	printf('   body.post-type-%s #post-body-content { display: none; }', allclients_api_get_landing_page_type('type'));
	printf('   body.post-type-%s #side-sortables { display: none; }', allclients_api_get_landing_page_type('type'));
	print '</style>';
	// Start table output
	echo '<table class="form-table">';

	// If there are folders output the select box
	if ( count($folders) > 0 ) {

		// Folder values
		$all_folder_value    = "";
		$all_folder_text     = 'All Landing Pages';
		$not_in_folder_value = '\\';
		$not_in_folder_text  = 'Not in a Folder';
		$current_folder      = $not_in_folder_value;

		$pages = allclients_api_get_landing_pages( $current_folder );
		if ( !$is_new  && $landing_page_folder != $current_folder ) {
			$folder_pages = allclients_api_get_landing_pages( $landing_page_folder );
			foreach ($folder_pages as $page) {
				if ( $page['webformid'] == $landing_page_id) {
					$current_folder = $landing_page_folder;
					$pages = $folder_pages;
					break;
				}
			}
		}

		echo '<tr valign="top"><th scope="row">';
		echo '<div class="allclients-spinner" id="spin-folder-select" style="float: right"></div>';
		echo '<label for="landing_page_folder">Folder:</label>';
		echo '</th><td width="100%">';
		echo '<select name="landing_page_folder">';
		echo '<option value="' . esc_attr( $not_in_folder_value ) . '"' . ($current_folder === $not_in_folder_value ? ' selected' : '' ) . '>' . $not_in_folder_text . '</option>';
		echo '<option value="' . esc_attr( $all_folder_value ) . '"' . ($current_folder === $all_folder_value ? ' selected' : '' ) . '>' . $all_folder_text . '</option>';
		echo '<option disabled>--------------</option>';
		foreach ( $folders as $folder) {
			echo '<option value="' . esc_attr( $folder['name'] ) . '"' . ($current_folder === $folder['name'] ? ' selected' : '' ) . '>' . esc_html( $folder['name'] ) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';
	} else {
		$current_folder = '';
		$pages = allclients_api_get_landing_pages( $current_folder );
	}

	// Page select
	$no_pages_text = esc_html__( 'No landing pages on file.' );
	$select_pages_text = esc_html__( 'Choose a Landing Page:' );

	echo '<tr valign="top"><th scope="row" nowrap>';
	echo '<div class="allclients-spinner" id="spin-page-select" style="float: right"></div>';
	echo '<label for="landing_page_id">Landing Page:</label>';
	echo '</th><td width="100%">';
	echo '<select name="landing_page_id" data-none="' . $no_pages_text . '" data-select="' . $select_pages_text . '">';
	if ( count( $pages ) === 0 ) {
		echo '<option value="">' . $no_pages_text . '</option>';
	} else {
		echo '<option value="">' . $select_pages_text . '</option>';
		foreach ( $pages as $page ) {
			echo '<option value="' . $page['webformid'] . '"' . ( $landing_page_id == $page['webformid'] ? ' selected' : '' ). '>' . esc_html( $page['name'] ) . '</option>';
		}
	}
	echo '</select>';
	echo '</td></tr>';

	// Type
	echo '<tr valign="top"><th scope="row" nowrap>Type:</th>';
	echo '<td width="100%">';
	echo '<input type="radio" name="landing_page_type" id="landing_page_type_normal" value="normal"'.($landing_page_type === 'normal' ? ' checked="checked"' : '').'>';
	echo '<label for="landing_page_type_normal">Normal Page</label>';
	echo '&nbsp;&nbsp;&nbsp;';
	echo '<input type="radio" name="landing_page_type"' . ( !$homepage_enabled ? ' disabled' : null ) . ' id="landing_page_type_homepage" value="homepage"'.($landing_page_type === 'homepage' ? ' checked="checked"' : '').'>';
	echo '<label for="landing_page_type_homepage"' . ( !$homepage_enabled ? ' disabled' : null ) . '>Homepage</label>';
	echo '</td></tr>';
	
	echo '</table>';
	
	echo '<div class="landing-actions"></div>';
	echo '<div class="landing-permalink"></div>';
	
	// Hidden fields for landing page folder and name
	echo '<input type="hidden" name="landing_page_name" value="' . esc_attr($landing_page_name) . '">';
}

function allclients_landing_pages_meta_box_save( $post_id) {

	// Check if our nonce is set.
	if ( !isset( $_POST['landing_page_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['landing_page_meta_box_nonce'], 'landing_page_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	// Get landing page id
	if ( !isset( $_POST['landing_page_id'] ) ) {
		return;
	}
	$landing_page_id   = sanitize_text_field( $_POST['landing_page_id'] );
	$landing_page_name = sanitize_text_field( $_POST['landing_page_name'] );
	$landing_page_type = sanitize_text_field( $_POST['landing_page_type'] );
	
	// Get landing page folder
	if ( !isset( $_POST['landing_page_folder'] ) ) {
		$landing_page_folder = '';
	} else {
		$landing_page_folder = sanitize_text_field( $_POST['landing_page_folder'] );
	}

	// Account id from API configuration
	$account_id = get_allclients()->get_api()->get_account_id();

	update_post_meta( $post_id, 'landing_page_id', $landing_page_id );
	update_post_meta( $post_id, 'landing_page_folder', $landing_page_folder );
	update_post_meta( $post_id, 'landing_page_name', $landing_page_name );
	update_post_meta( $post_id, 'landing_page_type', $landing_page_type );
	update_post_meta( $post_id, 'account_id', $account_id );
}
