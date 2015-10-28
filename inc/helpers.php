<?php
/**
 * Helper functions, this file gets loaded first!
 */

/**
 * Helper function for tracking pixel
 *
 * @param string $pixel URL of tracking pixel
 */
function sponsorship_manager_insert_tracking_pixel( $pixel ) {
	if ( empty( $pixel ) ) {
		return;
	}
?>
	<script>
		var sponsorshipPixelUrl = <?php echo wp_json_encode( $pixel ); ?>;

		// make a new, unique cachebuster paramater for the pixel URL
		sponsorshipPixelUrl = sponsorshipPixelUrl.replace( /\?.*c=([\d]+)/, function(match, oldC) {
			var newC = Date.now().toString() + Math.floor( Math.random() * 1000 ).toString();
			return match.replace( oldC, newC );
		} );
		var sponsorshipPixel = document.createElement( 'img' );
		sponsorshipPixel.src = sponsorshipPixelUrl;
		// append to body to fire tracking pixel
		if ( document.body ) {
			document.body.appendChild( sponsorshipPixel );
		}
	</script>
<?php }
