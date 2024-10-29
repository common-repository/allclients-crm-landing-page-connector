<?php
function allclients_landing_pages_settings_include() {
	$permalink_structure = get_option( 'permalink_structure' );
	if ( is_multisite() && !is_subdomain_install() && is_main_site() ) {
		$permalink_structure = preg_replace( '|^/?blog|', '', $permalink_structure );
	}

	// Current settings
	$activated = allclients_get_component_option('landing-pages', 'activated');
	$slug_type_input = allclients_get_component_option_name('landing-pages', 'slug_type');
	$slug_type = allclients_get_component_option('landing-pages', 'slug_type');

	$slug_input = allclients_get_component_option_name('landing-pages', 'slug');
	$slug = allclients_get_component_option( 'landing-pages', 'slug', '' );

	$error_messages = array();
	$rewrite_error = false;
	$root_error    = false;
	$folder_error  = false;

	if ( empty($permalink_structure) ) {
		$rewrite_error = true;
		$error_messages[] = sprintf(
			"You must <a href=\"%s\">update your permalink structure</a> to
			something other than the default for landing pages to work.",
			admin_url( 'options-permalink.php' )
		);
	} else {
		if ( $permalink_structure === '/%postname%/' ) {
			$root_sluggable = true;
		} else {
			$root_sluggable = false;
			if ( $slug_type == 0 ) {
				$root_error       = true;
				$error_messages[] = "Invalid landing pages link structure.";
			}
		}
		if ( $slug_type == 1 ) {
			if ( empty( $slug ) ) {
				$folder_error     = true;
				$error_messages[] = 'Landing pages folder name is required.';
			} else if ( preg_match( '/[^a-zA-Z0-9-]/', $slug ) ) {
				$folder_error     = true;
				$error_messages[] = 'Landing pages folder name is invalid.';
			}
		}
	}
	?>
<h5>Landing pages link structure:</h5>
<?php if ( count($error_messages)  ) { ?>
	<div class="error">
		<p><?php echo implode( "<br>", $error_messages ); ?></p>
	</div>
<?php } ?>
<table class="form-table">
	<tr valign="top">
		<th scope="row">
			<label<?php if ($root_error) { ?> style="color: red;"<?php } ?>><input type="radio" name="<?php echo $slug_type_input ?>"<?php if ($slug_type == 0) { ?> checked<?php } ?><?php if ( !$root_sluggable || $rewrite_error ) { ?> disabled="disabled"<?php } ?> value="0" class="landing-pages-slug-type">
				<?php if ($root_sluggable) { ?>
					Site Root
				<?php } else { ?>
					<span style="font-style: italic;">Site Root</span>
				<?php } ?>
			</label>
		</th>
		<td>
			<code><?php echo get_option('home') ?>/sample-landing-page/</code>
			<?php if (!$root_sluggable) { ?>
				<span style="font-style: italic; font-size: 90%;"> (available <strong>only</strong> with <strong>Post name</strong> <a href="<?php echo admin_url( 'options-permalink.php' ); ?>">permalink structure</a>)</span>
			<?php } ?>
		</td>
	</tr>
	<tr>
		<th scope="row">
			<label<?php if ($folder_error) { ?> style="color: red;"<?php } ?>><input type="radio" name="<?php echo $slug_type_input ?>"<?php if ($slug_type == 1) { ?> checked<?php } ?><?php if ( $rewrite_error ) { ?> disabled="disabled"<?php } ?> value="1" class="landing-pages-slug-type">
				In Folder
			</label>
		</th>
		<td><code><?php echo get_option('home') ?>/<input name="<?php echo $slug_input ?>" id="landing-pages-slug" type="text"<?php if ( $rewrite_error ) { ?> disabled="disabled"<?php } ?> value="<?php echo esc_attr( $slug ); ?>" class="regular-text code" style="width: 175px;" />/sample-landing-page/</code></td>
	</tr>
</table>
	<?php if ( $rewrite_error ) { ?>
		<input type="hidden" name="<?php echo $slug_type_input; ?>" value="<?php echo $slug_type; ?>">
		<input type="hidden" name="<?php echo $slug_input; ?>" value="<?php echo $slug; ?>">
	<?php } ?>
<?php
}