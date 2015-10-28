<?php
/**
 * Quickly determine if a post is sponsored
 *
 * @var int|WP_Post Optional. Post ID or WP_Post object
 * @return boolean Presence of a sponsorship campaign for this post
 */
function sponsorship_post_is_sponsored( $post = null ) {
	$post = get_post( $post );
	if ( empty( $post ) ) {
		return false;
	}
	return has_term( '', SPONSORSHIP_MANAGER_CAMPAIGN_TAXONOMY, $post );
}
