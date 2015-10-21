<?php
/**
 * Retrieve the current sponsor
 *
 * @return object
 */
function sponsorship_get_sponsor() {
	return sponsorship_manager()->get_sponsor();
}

/**
 * Get sponsorship campaign for a post
 *
 * @param WP_Post|void Optional post object
 * @return object|void Sponsor Term or null if one isn't set.
 */
function sponsorship_get_post_sponsor( $post = null ) {
	return sponsorship_manager()->get_post_sponsor( $post );
}

/**
 * Retrieve Sponsor Parent
 *
 * @param  object $sponsor Optional sponsor. Defaults to current posts sponsor.
 * @return WP_Term
 */
function sponsorship_get_sponsor_parent( $sponsor = null ) {
	return sponsorship_manager()->get_sponsor_parent( $sponsor );
}

/**
 * Retrieve Sponsor's Image
 *
 * @param  string $name Internal name of the image (logo/featured-image).
 * @param  string $size Image Size.
 * @param  object $sponsor Optional sponsor. Defaults to current post sponsor.
 * @return string Image URL or null if not found.
 */
function sponsorship_get_sponsor_image_url( $name, $size = 'full', $sponsor = null ) {
	$image = sponsorship_manager()->get_sponsor_image( $name, $size, $sponsor );

	if ( ! empty( $image[0] ) ) {
		return $image[0];
	}
}

/**
 * Retrieve the URL to a Sponsor's External URL
 *
 * @param  object $sponsor Optional sponsor. Defaults to current post sponsor
 * @return string
 */
function sponsorship_get_sponsor_url( $sponsor = null ) {
	return sponsorship_manager()->get_sponsor_url( $sponsor );
}

/**
 * Retrieve the URL to a Sponsor's Hub
 *
 * @param  object $sponsor Optional sponsor. Defaults to current post sponsor
 * @return string
 */
function sponsorship_get_sponsor_hub_url( $sponsor = null ) {
	return sponsorship_manager()->get_sponsor_hub_url( $sponsor );
}
