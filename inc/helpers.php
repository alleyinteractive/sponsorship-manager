<?php
/**
 * Helpers
 */

/**
 * Create campaign post term if it doesn't exist
 *
 * @param  string $slug Term slug
 * @param  string $taxonomy Taxonomy name. Defaults to 'sponsorship_campaign_posts'
 * @return object
 */
function sponsorship_manager_get_or_create_term( $slug, $taxonomy = 'sponsorship_campaign_posts' ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( ! empty( $term ) ) {
		return $term;
	} else {
		$term_data = wp_insert_term( $slug, $taxonomy );
		if ( is_wp_error( $term_data ) || empty( $term_data['term_id'] ) ) {
			return false;
		}
		return ( ! empty( $term_data['term_id'] ) ) ? get_term( $term_data['term_id'], $taxonomy ) : false;
	}
}

/**
 * Remove a term from a post if it has it
 *
 * @param WP_Post $post Post to remove term from
 * @param object $term Term to remove
 */
function sponsorship_manager_remove_term_from_post( WP_Post $post, $term ) {

}

/**
 * Add a term to a post
 */
