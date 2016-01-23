<?php
/**
 * Manages "ad slots" for use in templates
 */

class Sponsorship_Manager_Ad_Slots {

	/**
	 * Singleton Instance
	 *
	 * @var Sponsorship_Manager_Ad_Slots
	 */
	protected static $instance;

	/**
	 * @var array Received ad slot configuration
	 */
	protected $config;

	/**
	 * @var array Eligible posts for each slot
	 */
	protected $eligible_posts = array();

	/**
	 * @var string Transient prefix
	 */
	protected $transient_prefix = 'sponsorship_manager_eligible_posts_';

	/**
	 * @var int Transient expiration in minutes
	 */
	protected $transient_expiration = 15;

	/**
	 * @var int Maximum eligible posts per slot
	 */
	protected $max_elibible_posts = 50;

	/**
	 * @var string Post meta key prefix for targeting
	 */
	protected $postmeta_key_prefix = 'sponsorship_manager_targeted_to_';

	/**
	 * Retrieve Singleton Instance
	 * @param array $config Ad slot configuration
	 * @return Sponsorship_Manager_Ad_Slots
	 */
	public static function instance( $config ) {
		if ( empty( self::$instance ) ) {
			self::$instance = new self( $config );
		}
		return self::$instance;
	}

	/**
	 * Protected Contructor
	 * @param array $config Ad slot configuration
	 */
	protected function __construct( $config ) {
		if ( empty( $config ) ) {
			return;
		}
		$this->config = $config;

		// Add slot selection field to posts
		add_filter( 'sponsorship_manager_post_fields', array( $this, 'add_slot_targeting_field' ) );
		add_filter( 'fm_presave_alter_values', array( $this, 'set_targeting_postmeta' ), 10, 2 );

		// Get list of post IDs targeted to each field, filtered by configuration params
		foreach ( $this->config as $slot_name => $params ) {
			$this->eligible_posts[ $slot_name ] = $this->get_eligible_posts( $slot_name, $params );
		}
		unset( $params );
	}

	/**
	 * If slot targeting is enabled, add a slot selection field for posts
	 * @param array $fields Fieldmanager fields array
	 * @return array Fieldmanager fields array
	 */
	public function add_slot_targeting_field( $fields ) {
		/**
		 * @todo only show slots for which post is eligible
		 */

		$fields['ad_slot'] = new Fieldmanager_Checkboxes( array(
			'label' => __( 'Sponsorship Manager Ad Slots', 'sponsorship-manager' ),
			'description' => __( 'Select slots to target this post to', 'sponsorship-manager' ),
			'options' => array_keys( $this->config ),
			'multiple' => true,
		) );
		return $fields;
	}

	/**
	 * Set or delete post meta according to targeted ad slots
	 * @param array $values Fieldmanager data
	 * @param Fieldmanager_Field $field Fieldmanager 'sponsorship-info' field
	 * @return array $values Fieldmanager data
	 */
	public function set_targeting_postmeta( $values, $field ) {
		if ( 'sponsorship-info' === $field->name ) {
			foreach ( $this->config as $slot_name => $value ) {
				error_log(print_r($values,true));
				if ( empty( $values[0]['ad_slot'] ) || ! in_array( $slot_name, $values[0]['ad_slot'], true ) ) {
					delete_post_meta( get_the_ID(), $this->postmeta_key_prefix . $slot_name );
				} else {
					update_post_meta( get_the_ID(), $this->postmeta_key_prefix . $slot_name, 1 );
				}
			}
		}
		return $values;
	}

	/**
	 * Get list of eligible post IDs for an ad slot.
	 * Posts are eligible when they are targeted to the ad slot and match the params
	 * @param string $slot_name Slot name
	 * @param array $params Optional params as WP_Query arguments, may be empty
	 * @return array List of eligible post IDs
	 */
	protected function get_eligible_posts( $slot_name, $params = null ) {

		// check transient
		$ids = get_transient( $this->transient_prefix . $slot_name );
		if ( false !== $ids ) {
			return $ids;
		}

		// get posts
		$params = $this->build_query_args( $slot_name, $params );
		$ids = new WP_Query( $params );
		if ( empty( $ids ) || is_wp_error( $ids ) ) {
			$ids = array();
		}
		set_transient( $this->transient_prefix . $slot_name, $ids, ( $this->transient_expiration * 60 ) );
		return $ids;
	}

	/**
	 * Builds WP_Query args array
	 * @param string $slot_name Slot name
	 * @param array $params Optional params as WP_Query arguments, may be empty
	 * @return array List of eligible post IDs
	 */
	protected function build_query_args( $slot_name, $params ) {
		/**
		 * @todo Use hidden taxonomy instead of postmeta for query performance
		 */
		$slot_meta = array(
			'key' => $this->postmeta_key_prefix . $slot_name,
			'compare' => 'EXISTS',
		);

		// straight meta query if no other params are passed
		if ( empty( $params ) ) {
			$params = array( 'meta_query' => array( $slot_meta ) );
		}
		// create new meta_query array
		elseif ( empty( $params[ 'meta_query' ] ) ) {
			$params['meta_query'] = array( $slot_meta );
		}
		// append to existing meta_query array
		else {
			$params['meta_query'][] = $slot_meta;
		}

		// if we have meta_key/meta_value params, move into meta_query array
		if ( ! empty( $params['meta_key'] ) && ( ! empty( $params['meta_value'] ) || ! empty( $params['meta_value_num'] ) ) ) {
			$params_meta = array(
				'key'	=> $params['meta_key'],
				'value'	=> empty( $params['meta_value_num'] ) ? $params['meta_value'] : $params['meta_value_num'],
				'type'	=> empty( $params['meta_value_num'] ) ? 'CHAR' : 'NUMERIC',
			);

			if ( ! empty( $params['meta_compare'] ) ) {
				$params_meta['compare'] = $params['meta_compare'];
			}

			$params['meta_query'][] = $params_meta;
		}

		// no post_type is specified, use all the enabled ones
		if ( empty( $params['post_type'] ) ) {
			$params['post_type'] = sponsorship_manager()->get_enabled_post_types();
		}

		$params['fields'] = 'ids';
		$params['posts_per_page'] = $this->max_elibible_posts;
		$params['ignore_sticky_posts'] = true;

		return apply_filters( 'sponsorship_manager_ad_slot_params', $params, $slot_name );
	}
}

/**
 * If ad slots are configued, presumably by a theme, set things up
 */
function sponsorship_manager_setup_ad_slots() {
	if ( $config = apply_filters( 'sponsorship_manager_ad_slots_config', false ) ) {
		Sponsorship_Manager_Ad_Slots::instance( $config );
	}
}
add_action( 'init', 'sponsorship_manager_setup_ad_slots', 11 );

/**
 * Template tag to render a specific ad slot
 * @param string $slot_name Name of slot to render
 * @return none
 */
function sponsorship_manager_ad_slot( $slot_name ) {

}