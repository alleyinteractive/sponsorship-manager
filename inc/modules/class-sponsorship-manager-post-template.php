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
	 * @var string Post meta key for post-specific sponsorship data
	 */
	protected $sponsorship_info_key = 'sponsorship-info';

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
		$this->sponsorship_info = (array) get_post_meta( $this->post->ID, $this->sponsorship_info_key, true );
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

	/**
	 * Get a key from the sponsorship data for this particular post (not the sponsor or campaign)
	 * @param $key
	 * @return mixed|null Value for the key or null
	 */
	public function get_sponsorship( $key ) {
		return isset( $this->sponsorship_info[ $key ] ) ? $this->sponsorship_info[ $key ] : null;
	}

	/**
	 * Renders a script tag that fires the DFP tracking pixel with a new, unique cachebusting parameter
	 *
	 * @return none
	 */
	public function insert_tracking_pixel() {
		$dfp_pixel_url = $this->get_sponsorship( 'dfp-tracking-pixel' );
		if ( empty( $dfp_pixel_url ) ) {
			return;
		}
		sponsorship_manager_insert_tracking_pixel( $dfp_pixel_url );
	}
}
