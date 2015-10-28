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

		if ( function_exists( 'wpcom_vip_get_term_link' ) ) {
			$metadata['hub'] = wpcom_vip_get_term_link( $term );
		} else {
			$metadata['hub'] = get_term_link( $term );
		}


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
	 * @param mixed|null Value of key or null if not found
	 */
	public function get( $key, $parent = false ) {
		// make sure the term we're looking for is there
		$term = ! $parent ? $this->term : $this->parent;
		if ( empty( $term ) ) {
			return null;
		}

		// look for the key first in the term itself, then in metadata
		$key = strval( $key );
		if ( isset( $term->$key ) ) {
			return $term->$key;
		} elseif ( isset( $term->metadata[ $key ] ) ) {
			return $term->metadata[ $key ];
		} else {
			return null;
		}
	}
}
