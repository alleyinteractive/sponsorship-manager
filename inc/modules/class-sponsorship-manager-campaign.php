<?php
/**
 *  Sponsorship Campaign class
 */
class Sponsorship_Manager_Campaign {
	/**
	 * @var object WP term that represents the sponsorship campaign
	 */
	protected $term;

	/**
	 * Constructor
	 * @var object $term WP term object
	 */
	public function __constructor( $term ) {
		$this->term = $term;
	}

	/**
	 * Get id for term
	 * @return int Term id
	 */
	public function get_id() {
		return $this->term->term_id;
	}
}
