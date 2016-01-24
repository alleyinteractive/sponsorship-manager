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
	 * @var array Ad slots list defined with 'sponsorship_manager_ad_slots_list' filter
	 */
	protected $list;

	/**
	 * @var array Query args config for ad slots
	 */
	protected $query_config = null;

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
	 * @var bool Whether to skip transient when retrieving eligible posts
	 */
	protected $skip_transient = false;

	/**
	 * Retrieve Singleton Instance
	 * @param array $list Ad slots list
	 * @return Sponsorship_Manager_Ad_Slots
	 */
	public static function instance( $list = false ) {
		if ( empty( self::$instance ) ) {
			self::$instance = new self( $list );
		}
		return self::$instance;
	}

	/**
	 * Protected Contructor
	 * @param array $list Ad slots list
	 */
	protected function __construct( $list ) {
		if ( empty( $list ) ) {
			return;
		}
		$this->list = $list;

		// Add slot selection field to posts
		add_filter( 'sponsorship_manager_post_fields', array( $this, 'add_slot_targeting_field' ) );
		add_filter( 'fm_presave_alter_values', array( $this, 'set_targeting_postmeta' ), 10, 2 );

		// Dev stuff
		$this->skip_transient = apply_filters( 'sponsorship_manager_skip_ad_slot_transients', $this->skip_transient );
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
			'options' => $this->list,
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
			foreach ( $this->list as $slot_name ) {
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
	 * Get list of eligible posts
	 * @param string $slot_name
	 * @param array $args Optional WP_Query args that override initial config
	 * @return array List of post IDs
	 */
	public function get_eligible_posts( $slot_name, $args = false ) {
		// check transient
		if ( ! $this->skip_transient ) {
			$ids = get_transient( $this->transient_prefix . $slot_name );
			if ( false !== $ids ) {
				return $ids;
			}
		}
		return $this->set_eligible_posts( $slot_name, $args );
	}

	/**
	 * Sets list of eligible post IDs for an ad slot.
	 * Posts are eligible when they are targeted to the ad slot and match the params
	 * @param string $slot_name Slot name
	 * @param array $params Optional params as WP_Query arguments, may be empty
	 * @return array List of eligible post IDs
	 */
	protected function set_eligible_posts( $slot_name, $args = false ) {
		// get WP_Query args
		if ( empty( $args ) ) {
			$args = $this->build_query_args( $slot_name );
		} else {
			$args = array_merge( $this->build_query_args( $slot_name ), $args );
		}

		$query = new WP_Query( $args );
		if ( empty( $query ) || is_wp_error( $query ) ) {
			$ids = $query->posts;
		}
		set_transient( $this->transient_prefix . $slot_name, $query->posts, ( $this->transient_expiration * 60 ) );
		return $query->posts;
	}

	/**
	 * Builds WP_Query args array
	 * @param string $slot_name Slot name
	 * @param array $params Optional params as WP_Query arguments, may be empty
	 * @return array List of eligible post IDs
	 */
	protected function build_query_args( $slot_name ) {

		if ( null === $this->query_config ) {
			$this->query_config = apply_filters( 'sponsorship_manager_ad_slots_query_config', array() );
		}

		$params = ! empty( $this->query_config[ $slot_name ] ) ? $this->query_config[ $slot_name ] : array();
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

		/**
		 * @todo Make sure post has at least one term in the sponsorship_campaign taxonomy, otherwise it is not sponsored
		 */

		return apply_filters( 'sponsorship_manager_ad_slot_params', $params, $slot_name );
	}
}

/**
 * If ad slots are defined, presumably by a theme, set things up
 */
function sponsorship_manager_setup_ad_slots() {
	Sponsorship_Manager_Ad_Slots::instance( $list = apply_filters( 'sponsorship_manager_ad_slots_list', false ) );
}
add_action( 'init', 'sponsorship_manager_setup_ad_slots', 11 );

/**
 * Template tag to get list of eligible posts
 * @param string $slot_name Slot name
 * @param array $args Optional WP_Query args that override initial config
 * @return array List of IDs
 */
function sponsorship_manager_get_eligible_posts ( $slot_name, $args = false ) {
	return Sponsorship_Manager_Ad_Slots::instance()->get_eligible_posts( $slot_name, $args );
}

/**
 * Template tag to render a specific ad slot
 * @param string $slot_name Name of slot to render
 * @param array $args Optional WP_Query args that override initial config
 * @return none
 */
function sponsorship_manager_ad_slot( $slot_name, $args = false ) {
	$eligible_posts = sponsorship_manager_get_eligible_posts( $slot_name, $args );
	echo '<p>Eligible posts: ' . implode( ', ', $eligible_posts ) . '</p>';
}