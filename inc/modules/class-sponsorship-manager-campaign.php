<?php
/**
 *  Sponsorship Campaign class
 */
class Sponsorship_Manager_Campaign {

	/**
	 * @var string Sponsorship campaign taxonomy
	 */
	protected $taxonomy = SPONSORSHIP_MANAGER_CAMPAIGN_TAXONOMY;

	/**
	 * @var object WP term that represents the sponsorship campaign
	 */
	protected $term;

	/**
	 * @var object Parent campaign term if applicable
	 */
	protected $parent;

	/**
	 * @var array List of campaign term meta keys that refer to attachments
	 */
	protected $image_meta_keys = array( 'logo-primary', 'logo-secondary', 'featured-image' );

	/**
	 * Constructor
	 * @param object $term WP term object
	 */
	public function __construct( $term ) {
		// setup term and parent
		$this->term = $term;
		$this->term->metadata = $this->setup_term_meta( $this->term );
		if ( $this->term->parent ) {
			if ( function_exists( 'wpcom_vip_get_term_by' ) ) {
				$this->parent = wpcom_vip_get_term_by( 'id', $this->term->parent, $this->taxonomy );
			} else {
				$this->parent = get_term( $this->term->parent, $this->taxonomy );
			}
			$this->parent->metadata = $this->setup_term_meta( $this->parent, true );
		}
	}

	/**
	 * setup term meta including filters
	 * @filter 'sponsorship_manager_campaign_display_meta'
	 * @param object $term Term object
	 * @param bool $is_parent Optional. Use true if this is a parent term
	 * @return array Array of term metadata after filter
	 */
	protected function setup_term_meta( $term, $is_parent = false ) {
		$metadata = (array) fm_get_term_meta( $term->term_id, $term->taxonomy, 'sponsorship-campaign-display', true );

		if ( empty( $metadata['hub]'] ) ) {
			if ( function_exists( 'wpcom_vip_get_term_link' ) ) {
				$metadata['hub'] = wpcom_vip_get_term_link( $term, $term->taxonomy );
			} else {
				$metadata['hub'] = get_term_link( $term );
			}
		}

		if ( empty( $metadata['dfp-tracking-pixel'] ) ) {
			$metadata['dfp-tracking-pixel'] = sponsorship_manager()->tracking_pixel->get_url( $term->taxonomy, $term->term_id );
		}

		// replace DFP macro with number that gets randomized before logging pixel impression
		$metadata['dfp-tracking-pixel'] = str_replace( '%%CACHEBUSTER%%', '123', $metadata['dfp-tracking-pixel'] );

		/**
		 * Filter sponsorship-campaign-display Fieldmanager metadata
		 *
		 * @param array $metadata Array retrieved from the sponsorship-campaign-display field
		 * @param object $term The term we are currently getting information about
		 * @param bool $is_parent False by default, true if this is a parent campaign
		 */
		return apply_filters( 'sponsorship_manager_campaign_display_meta', $metadata, $term, $is_parent );
	}

	/**
	 * Get id for term
	 * @return int Term id
	 */
	public function get_id() {
		return $this->term->term_id;
	}

	/**
	 * Get campaign data & metadata by key, checking first in term itself then in metadata
	 *
	 * @param string $key Name of key to look for
	 * @param bool $parent Optional. Defaults to false, use true to get data from parent campaign
	 * @param string $img_size Optional. If $key is an image, specify an image size or default to 'full'
	 * @return mixed|null Value of key or null if not found
	 */
	public function get( $key, $parent = false, $img_size = 'full' ) {
		// make sure the term we're looking for is there
		$term = ! $parent ? $this->term : $this->parent;
		if ( empty( $term ) ) {
			return null;
		}

		// get correct key
		$key = strval( $key );
		/**
		 * This plugin hides the WordPress default term 'description' field and uses a Fieldmanager_RichTextArea
		 * By default, requests for the 'description' field will be overridden by this custom field
		 *
		 * @param bool true Default to overriding meta key 'description' with 'richdescription'
		 */
		if ( 'description' === $key && apply_filters( 'sponsorship_manager_override_campaign_description', true ) ) {
			$key = 'richdescription';
		}

		// look for the key first in the term itself, then in metadata
		if ( isset( $term->$key ) ) {
			return $term->$key;
		} elseif ( isset( $term->metadata[ $key ] ) ) {
			// if this is an image field, return URL and dimensions array
			if ( in_array( $key, $this->image_meta_keys, true ) ) {
				if ( ! empty( $term->metadata[ $key ] ) ) {
					return wp_get_attachment_image_src( $term->metadata[ $key ], $img_size );
				} else {
					return null;
				}
			}
			// if not an image, just return the value
			return $term->metadata[ $key ];
		} else {
			return null;
		}
	}
	/**
	 * Renders a script tag that fires the DFP tracking pixel with a new, unique cachebusting parameter
	 *
	 * @return none
	 */
	public function insert_tracking_pixel() {
		sponsorship_manager()->tracking_pixel->insert_tracking_pixel( $this->get( 'dfp-tracking-pixel' ) );
	}
}
