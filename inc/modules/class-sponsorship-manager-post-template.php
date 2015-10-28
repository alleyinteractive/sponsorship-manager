<?php

/**
 * Class represents the sponsored post for use in templates
 */
class Sponsorship_Manager_Post_Template {

	/**
	 * @var WP_Post post object
	 */
	protected $post;

	/**
	 * @var Sponsorship_Manager_Sponsorship_Campaign Campaign associated with this post
	 */
	protected $campaign;

	/**
	 * Constructor
	 *
	 * @var int|WP_Post Optional. Post ID or WP_Post object
	 * @return Sponsorship_Manager_Post_Template
	 */
	public function __construct( $post = null ) {
		$this->post = get_post( $post );
		if ( empty( $this->post ) ) {
			return;
		}

		// check if post is sponsored
		$campaign_term = get_the_terms( $this->post->ID, SPONSORSHIP_MANAGER_CAMPAIGN_TAXONOMY );
		if ( empty( $campaign_term ) ) {
			return;
		}

		// see if campaign has already been setup
		$this->campaign = sponsorship_manager()->get_campaign( $campaign_term->term_id );
		if ( empty( $this->campaign ) ) {
			$this->campaign = new Sponsorship_Manager_Campaign( $campaign_term );
			sponsorship_manager()->add_campaign( $this->campaign );
		}
	}
}
