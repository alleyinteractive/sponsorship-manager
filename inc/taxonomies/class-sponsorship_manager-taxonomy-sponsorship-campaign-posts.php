<?php

/**
 * Taxonomy for Sponsorship Campaign Posts.
 */
class Sponsorship_Manager_Taxonomy_Sponsorship_Campaign_Posts extends Sponsorship_Manager_Taxonomy {

	/**
	 * Name of the taxonomy.
	 *
	 * @var string
	 */
	public $name = 'sponsorship_campaign_posts';

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
		$this->object_types = sponsorship_manager()->get_enabled_post_types();

		parent::__construct();
	}

	/**
	 * Creates the taxonomy.
	 */
	public function create_taxonomy() {
		register_taxonomy( $this->name, $this->object_types, array(
			'labels' => array(
				'name'                  => __( 'Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'singular_name'         => __( 'Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'search_items'          => __( 'Search Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'popular_items'         => __( 'Popular Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'all_items'             => __( 'All Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'parent_item'           => __( 'Parent Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'parent_item_colon'     => __( 'Parent Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'edit_item'             => __( 'Edit Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'view_item'             => __( 'View Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'update_item'           => __( 'Update Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'add_new_item'          => __( 'Add New Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'new_item_name'         => __( 'New Sponsorship Campaign Posts Name', 'sponsorship-manager' ),
				'add_or_remove_items'   => __( 'Add or remove Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'choose_from_most_used' => __( 'Choose from most used Sponsorship Campaign Posts', 'sponsorship-manager' ),
				'menu_name'             => __( 'Sponsorship Campaign Posts', 'sponsorship-manager' ),
			),
			'public' => false,
			'show_in_menu' => false,
			'rewrite' => false,
		) );
	}
}

$taxonomy_sponsorship_campaign_posts = new Sponsorship_Manager_Taxonomy_Sponsorship_Campaign_Posts();
