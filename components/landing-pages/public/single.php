<?php
while ( have_posts() ) : the_post();

	try {
		$api = get_allclients()->get_api();

		// Get id from post meta
		$landing_page_id = get_post_meta( $post->ID, 'landing_page_id', true );
		if ( !$landing_page_id ) {
			throw new Exception( 'Landing page not selected.' );
		}

		// Lookup landing page
		if ( !$landing_page = allclients_api_get_landing_page_html( $landing_page_id )) {
			throw new Exception( 'Landing page not found.' );
		}
		echo $landing_page;

	} catch ( Exception $e ) {
		echo 'Error: ' . $e->getMessage();
	}

endwhile;
