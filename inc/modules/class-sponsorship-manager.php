<?php

/**
 * Main class to run the plugin
 */
class Sponsorship_Manager {

	/**
	 * Taxonomy of the Sponsorship Manager
	 *
	 * @var string
	 */
	public $taxonomy = SPONSORSHIP_MANAGER_CAMPAIGN_TAXONOMY;

	/**
	 * Singleton Instance
	 *
	 * @var Sponsorship_Manager
	 */
	protected static $instance;

	/**
	 * @var array Sponsorship_Manager_Campaign objects that have been set up already
	 */
	protected $campaigns = array();

	/**
	 * Retrieve Singleton Instance
	 *
	 * @return Sponsorship_Manager
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * @var array Post types that the plugin is enabled for
	 */
	protected $post_types = array( 'post' );

	/**
	 * Protected Contructor
	 */
	protected function __construct() {
		/**
		 * Add or remove post types that Sponsorhip Manager is enabled for
		 *
		 * @param array List of post types, defaults to array( 'post' )
		 */
		$this->post_types = apply_filters( 'sponsorship_manager_enabled_post_types', $this->post_types );

		// load tracking pixel class
		$this->tracking_pixel = new Sponsorship_Tracking_Pixel();
	}

	public function __clone() {
		wp_die( esc_html__( "Please don't __clone ", 'sponsorship-manager' ) . __CLASS__ );
	}

	public function __wakeup() {
		wp_die( esc_html__( "Please don't __wakeup ", 'sponsorship-manager' ) . __CLASS__ );
	}

	/**
	 * Retrieve enabled post types for Sponsorship Manager
	 *
	 * @return array
	 */
	public function get_enabled_post_types() {
		return $this->post_types;
	}

	/**
	 * Campaign getter
	 * @param int|object $campaign_term Term ID or object
	 * @return null|Sponsorship_Manager_Campaign Campaign object if exists, or null
	 */
	public function get_campaign( $campaign_term ) {
		if ( ! empty( $campaign_term->term_id ) ) {
			$campaign_term = $campaign_term->term_id;
		}
		if ( ! empty( $this->campaigns[ $campaign_term ] ) ) {
			return $this->campaigns[ $campaign_term ];
		}
	}

	/**
	 * Add a campaign to the list that have already been created
	 * @param Sponsorship_Manager_Campaign $campaign Campaign object
	 * @return null
	 */
	public function add_campaign( $campaign ) {
		$this->campaigns[ $campaign->get_id() ] = $campaign;
	}

	/**
	 * Create a fallback meta box if there are no terms in the sponsorship_campaign taxonomy
	 */
	public function fallback_meta_box() {
		add_meta_box(
			'sponsorship-campaign-fallback',
			__( 'Sponsorship Campaigns', 'sponsorship-manager' ),
			array( $this, 'render_fallback_meta_box' ),
			'page'
		);
	}

	/**
	 * Content of fallback meta box
	 */
	public function render_fallback_meta_box() {
		echo '<p>' . esc_html__( 'No Sponsorship Campaigns are available.', 'sponsorship-manager' ) . '</p>';
		/*
		 * to do: custom capabilities for sponsorship_campaign taxonomy
		 */
		if ( current_user_can( 'manage_categories' ) ) {
			echo '<p>' . sprintf( __( 'You can add one <a href="%s">here</a>.', 'sponsorship-manager' ), admin_url( 'edit-tags.php?taxonomy=sponsorship_campaign' ) ) . '</p>';
		}
	}
}

/**
 * Sponsorship Manager Helper
 *
 * @return Sponsorship_Manager
 */
function sponsorship_manager() {
	return Sponsorship_Manager::instance();
}

// go go go
sponsorship_manager();
