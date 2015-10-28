<?php
/**
 * Helper functions, this file gets loaded first!
 */

/**
 * Helper function for tracking pixel
 *
 * @param string $pixel URL of tracking pixel
 * @param string $param Key of cachebusting parameter, defaults to 'c' for DFP
 */
function sponsorship_manager_insert_tracking_pixel( $pixel, $param = 'c' ) {
	// allow a replacement pixel URL for debugging dev environments
	if ( $dev_pixel = apply_filters( 'sponsorship_manager_override_pixel_url', false, $pixel, $param ) ) {
		$pixel = $dev_pixel;
	}

	if ( empty( $pixel ) ) {
		return;
	}

	// cases where we don't want to trigger a pixel
	$trigger_for_logged_in = is_user_logged_in() && apply_filters( 'sponsorship_manager_tracking_pixel_when_logged_in', false );
	if ( is_admin() || is_preview() || $trigger_for_logged_in ) {
		return;
	}

	?>
		<script>sponsorshipManagerPlugin.insertPixel( <?php echo wp_json_encode( $pixel ); ?>, <?php echo wp_json_encode( $param ); ?> );</script>
	<?php
}
