<?php
/**
 * Get sponsorship campaign for a post
 * @param $id Optional post ID
 * @return WP_Term object or false if
 */
function sponsorship_get_term( $id = null ) {
	// get term from sponsorship_campaign taxonomy
}

/**
 * Get parent campaign for a post
 * @param $id Optional post ID
 * @return WP_Term object or false
 */
function sponsorship_get_parent_term( $id = null ) {
	// if post has a sponsorship_campaign AND that campaign is a child term, return parent term
	// otherwise, return false
}

/**
 * Prints a <script> tag that fires a DFP tracking pixel with a unique cachebusting parameter
 * @param $id Optional post ID
 * @return none
 */
function sponsorship_fire_tracking_pixel( $id = null ) {
	// get $dfp_pixel_url from post meta for $id, then something like...
?>
	<script>
		var sponsorshipPixelUrl = <?php echo wp_json_encode( $dfp_pixel_url ); ?>;
		// do some stuff to make sure the `c` URL param is a newly generated random number
		var sponsorshipPixel = document.createElement( 'img' );
		sponsorshipPixel.src = sponsorshipPixelUrl;
		if ( document.body ) {
			document.body.appendChild( sponsorshipPixel );
		}
	</script>
<?php
}
