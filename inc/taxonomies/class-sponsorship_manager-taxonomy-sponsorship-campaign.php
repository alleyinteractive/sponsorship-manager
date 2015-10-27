<?php

/**
 * Taxonomy for Sponsorship Campaigns.
 */
class Sponsorship_Manager_Taxonomy_Sponsorship_Campaign extends Sponsorship_Manager_Taxonomy {

	/**
	 * Name of the taxonomy.
	 *
	 * @var string
	 */
	public $name = 'sponsorship_campaign';

	/**
	 * Object types for this taxonomy
	 *
	 * @var array
	 */
	public $object_types;


	/**
	 * Build the taxonomy object.
	 */
	public function __construct() {
		$this->object_types = array('post');

		parent::__construct();
	}

	/**
	 * Creates the taxonomy.
	 */
	public function create_taxonomy() {
		register_taxonomy( $this->name, $this->object_types, array(
			'labels' => array(
				'name'                  => __( 'Sponsorship Campaigns', 'sponsorship-manager' ),
				'singular_name'         => __( 'Sponsorship Campaign', 'sponsorship-manager' ),
				'search_items'          => __( 'Search Sponsorship Campaigns', 'sponsorship-manager' ),
				'popular_items'         => __( 'Popular Sponsorship Campaigns', 'sponsorship-manager' ),
				'all_items'             => __( 'All Sponsorship Campaigns', 'sponsorship-manager' ),
				'parent_item'           => __( 'Parent Sponsorship Campaign', 'sponsorship-manager' ),
				'parent_item_colon'     => __( 'Parent Sponsorship Campaign', 'sponsorship-manager' ),
				'edit_item'             => __( 'Edit Sponsorship Campaign', 'sponsorship-manager' ),
				'view_item'             => __( 'View Sponsorship Campaign', 'sponsorship-manager' ),
				'update_item'           => __( 'Update Sponsorship Campaign', 'sponsorship-manager' ),
				'add_new_item'          => __( 'Add New Sponsorship Campaign', 'sponsorship-manager' ),
				'new_item_name'         => __( 'New Sponsorship Campaign Name', 'sponsorship-manager' ),
				'add_or_remove_items'   => __( 'Add or remove Sponsorship Campaigns', 'sponsorship-manager' ),
				'choose_from_most_used' => __( 'Choose from most used Sponsorship Campaigns', 'sponsorship-manager' ),
				'menu_name'             => __( 'Sponsorship Campaigns', 'sponsorship-manager' ),
			),
			'hierarchical' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud' => false,
			'show_in_quick_edit' => true,
			'meta_box_cb' => false,
			'rewrite' => array(
				'slug' => 'sponsor',
			),
		) );
	}
}

$taxonomy_sponsorship_campaign = new Sponsorship_Manager_Taxonomy_Sponsorship_Campaign();
