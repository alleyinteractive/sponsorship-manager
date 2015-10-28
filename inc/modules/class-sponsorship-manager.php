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
	 * Protected Contructor
	 */
	protected function __construct() {

	}

	public function __clone() {
		wp_die( esc_html__( "Please don't __clone ", 'sponsorship-manager' ) . __CLASS__ );
	}

	public function __wakeup() {
		wp_die( esc_html__( "Please don't __wakeup ", 'sponsorship-manager' ) . __CLASS__ );
	}

	/**
	 * Campaign getter
	 * @var int|object $campaign_term Term ID or object
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
	 * @var Sponsorship_Manager_Campaign $campaign Campaign object
	 * @return null
	 */
	public function add_campaign( $campaign ) {
		$this->campaigns[ $campaign->get_id() ] = $campaign;
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
