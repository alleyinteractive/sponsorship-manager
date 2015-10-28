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
	 * @var array Sponsorship info fields array
	 */
	protected $sponsorship_info;

	/**
	 * Constructor
	 *
	 * @param int|WP_Post Optional. Post ID or WP_Post object
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
		$campaign_term = $campaign_term[0];

		// see if campaign has already been setup
		$this->campaign = sponsorship_manager()->get_campaign( $campaign_term->term_id );
		if ( empty( $this->campaign ) ) {
			$this->campaign = new Sponsorship_Manager_Campaign( $campaign_term );
			sponsorship_manager()->add_campaign( $this->campaign );
		}

		// setup sponsorship info array
		$this->sponsorship_info = (array) get_post_meta( $this->post->ID, 'sponsorship-info', true );
	}

	/**
	 * get a value from the sponsorship campaign by key
	 *
	 * @param string $key Key to look for
	 * @param bool $parent Optional. Defaults to false, set to true to check parent term
	 * @param string $img_size Optional. If $key is an image field ('logo' or 'featured-image'), specify an image size or default to 'full'
	 * @return mixed|null Value of key if found, null if not found
	 */
	public function get_campaign( $key, $parent = false, $img_size = 'full' ) {
		return $this->campaign->get( $key, $parent, $img_size );
	}
}
