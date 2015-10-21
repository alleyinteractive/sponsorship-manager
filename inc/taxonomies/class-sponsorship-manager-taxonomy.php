<?php

/**
 * Abstract class for taxonomy classes
 */
abstract class Sponsorship_Manager_Taxonomy {

	/**
	 * Name of the taxonomy
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * Object types for this taxonomy
	 *
	 * @var array
	 */
	public $object_types = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Set object types from the manager
		$this->object_types = sponsorship_manager()->get_enabled_post_types();

		// Create the taxonomy
		add_action( 'init', array( $this, 'create_taxonomy' ) );
	}

	/**
	 * Create the taxonomy.
	 */
	abstract public function create_taxonomy();

}
