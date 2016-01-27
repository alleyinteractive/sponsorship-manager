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
	 * @var string Slot name WP_Query var
	 */
	protected $query_var = 'sponsorship_ad_slot';

	/**
	 * @var string Shortcode name
	 */
	protected $shortcode_name = 'sponsorship-ad-slot';

	/**
	 * @var bool Whether to skip transient when retrieving eligible posts
	 */
	protected $skip_transient = false;

	/**
	 * @var bool In-browser dev mode, also disables transient if true
	 */
	protected $dev_mode = false;

	/**
	 * @var string Query string param to use dev mode
	 */
	protected $dev_query_param = 'smconsole';

	/**
	 * @var array Slots rendered on this request
	 */
	protected $slots_rendered = array();


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

		// Dev stuff
		$this->skip_transient = apply_filters( 'sponsorship_manager_skip_ad_slot_transients', $this->skip_transient );
		if ( ! empty( $_SERVER['QUERY_STRING'] ) && 1 === preg_match( '/(?:^|&)' . $this->dev_query_param . '(?:$|=|&)/', $_SERVER['QUERY_STRING'] ) ) {
			$this->dev_mode = true;
			$this->skip_transient = true;
		}

		// Add slot selection field to posts
		add_filter( 'sponsorship_manager_post_fields', array( $this, 'add_slot_targeting_field' ) );
		add_filter( 'fm_presave_alter_values', array( $this, 'set_targeting_postmeta' ), 10, 2 );

		// slot shortcode
		add_shortcode( $this->shortcode_name, array( $this, 'slot_shortcode' ) );

		// handle AJAX request for ad slot
		add_rewrite_tag('%sponsorship_ad_slot%', '([A-Za-z0-9\-_]+)');
		add_rewrite_rule( '^sponsorship-manager/([A-Za-z0-9\-_]+)/(\d+)/?', 'index.php?sponsorship_ad_slot=$matches[1]&p=$matches[2]', 'top' );
		add_action( 'parse_query', array( $this, 'do_api_request' ) );
	}

	/**
	 * Test if ad slot is active
	 * @param string $slot_name Slot name
	 * @return bool
	 */
	public function slot_is_active( $slot_name ) {
		return in_array( $slot_name, $this->list, true );
	}

	/**
	 * If slot targeting is enabled, add a slot selection field for posts
	 * @param array $fields Fieldmanager fields array
	 * @return array Fieldmanager fields array
	 */
	public function add_slot_targeting_field( $fields ) {
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
		if ( ! $this->slot_is_active( $slot_name ) ) {
			return array();
		}

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
			$ids = array();
		} else {
			$ids = $query->posts;
		}
		set_transient( $this->transient_prefix . $slot_name, $ids, ( $this->transient_expiration * 60 ) );
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

	/**
	 * Template tag to render a specific ad slot
	 * @param string $slot_name Name of slot to render
	 * @param array $args Optional WP_Query args that override initial config
	 * @return string HTML/JS for ad slot
	 */
	public function render_ad_slot( $slot_name, $args ) {
		if ( ! $this->slot_is_active( $slot_name ) ) {
			return '';
		}

		$posts = $this->get_eligible_posts( $slot_name, $args );
		$posts = apply_filters( 'sponsorship_manager_slot_posts', $posts, $slot_name, $args );
		if ( empty( $posts ) ) {
			return '';
		}

		if ( ! isset( $this->slots_rendered[ $slot_name ] ) ) {
			$this->slots_rendered[ $slot_name ] = 0;
		}

		// build markup
		$container_id = 'sponsorship-ad-slot-' . esc_attr( $slot_name ) . '-container-'. esc_attr( $this->slots_rendered[ $slot_name ] );
		$slot_markup[] = '<div id="' . esc_attr( $container_id ) . '" class="sponsorship-ad-slot slot-' . esc_attr( $slot_name ) . '"></div>';
		$slot_markup[] = '<script>';
		$slot_markup[] = "\t" . '(function( $ ) {';
		$slot_markup[] = "\t\t" . 'var eligibleIds = ' . wp_json_encode( $posts ) . ';';
		$slot_markup[] = "\t\t" . 'var idx = Math.floor( Math.random() * eligibleIds.length );';
		$slot_markup[] = "\t\t" . 'var $target = $(' . wp_json_encode( '#' . $container_id ) . ');';
		$slot_markup[] = "\t\t" . 'if ( ! $target.length ) {';

		if ( $this->dev_mode ) {
			$slot_markup[] = "\t\t\t" . 'console.log(' . wp_json_encode( '#' . $container_id . ' not found' ) .');';
		}

		$slot_markup[] = "\t\t\t" . 'return;';
		$slot_markup[] = "\t\t" . '}';
		$slot_markup[] = "\t\t" . 'var requestData = {';
		$slot_markup[] = "\t\t\t" . 'url: '		. wp_json_encode( home_url( '/sponsorship-manager/' . $slot_name . '/' ) ). ' + eligibleIds[ idx ] + "/"';
		if ( $this->dev_mode ) {
			// add a comma after requestData.url
			$slot_markup_last = array_pop( $slot_markup );
			$slot_markup_last .= ',';
			$slot_markup[] = $slot_markup_last;
			$slot_markup[] = "\t\t\t" . 'slot: '	. wp_json_encode( $slot_name ) . ',';
			$slot_markup[] = "\t\t\t" . 'render: '	. wp_json_encode( $this->slots_rendered[ $slot_name ] ) . ',';
			$slot_markup[] = "\t\t\t" . 'targetId: '. wp_json_encode( $container_id );
		}
		$slot_markup[] = "\t\t" . '};';
		$slot_markup[] = "\t\t" . '$.get( requestData.url, function( res ) {';
		$slot_markup[] = "\t\t\t" . 'if ( res.success ) {';
		$slot_markup[] = "\t\t\t\t" . '$target.html( res.data.content );';

		if ( $this->dev_mode ) {
			$slot_markup[] = "\t\t\t\t" . 'console.log( "' . __( 'Sponsorship Manager AJAX success', 'sponsorship-manager' ). '", requestData, res );';
			$slot_markup[] = "\t\t\t" . '} else {';
			$slot_markup[] = "\t\t\t\t" . 'console.log( "' . __( 'Sponsorship Manager AJAX error', 'sponsorship-manager' ). '", requestData, res );';
		}

		$slot_markup[] = "\t\t\t" . '}';

		if ( $this->dev_mode ) {
			$slot_markup[] = "\t\t" . '} ).fail( function() {';
			$slot_markup[] = "\t\t\t" . 'console.log( "' . __( 'Sponsorship Manager AJAX failed', 'sponsorship-manager' ). '", requestData );';
		}

		$slot_markup[] = "\t\t" . '} );';
		$slot_markup[] = "\t" . '} )( jQuery );';
		$slot_markup[] = '</script>';

		$this->slots_rendered[ $slot_name ]++;

		return implode( "\n", $slot_markup );
	}

	/**
	 * Handle API request for Slot + ID and reply with JSON
	 * @param WP_Query $query Passed by reference
	 * @return none
	 */
	public function do_api_request( $query ) {
		// must be main query and our API query var
		if ( ! $query->is_main_query() || empty( $slot_name = $query->get( 'sponsorship_ad_slot') ) ) {
			return;
		}

		// post must exist and be targeted to the slot
		$post = get_post( $query->get( 'p' ) );
		if ( empty( $post ) ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Post ID `%s` not found', 'sponsorship-manager' ), $query->get( 'p' ) ) ) );
		}
		$targeted = get_post_meta( $post->ID, $this->postmeta_key_prefix . $slot_name, true );
		if ( empty( $targeted ) ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Post ID `%s` not targeted to slot %s', 'sponsorship-manager' ), $post->ID, $slot_name ) ) );
		}

		// slot must have template filters applied
		if ( ! has_filter( 'sponsorship_manager_slot_template_' . $slot_name ) ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'No template filters added to sponsorship_manager_slot_template_%s', 'sponsorship-manager' ), $slot_name ) ) );
		}

		$template_response = apply_filters( 'sponsorship_manager_slot_template_' . $slot_name, '', $slot_name, $post );
		if ( empty( $template_response ) ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'Empty response for post ID `%s` and slot %s', 'sponsorship-manager' ), $post->ID, $slot_name ) ) );
		} elseif ( is_string( $template_response ) ) {
			wp_send_json_success( array( 'content' => wp_kses_post( $template_response ) ) );
		} else {
			wp_send_json( $template_response );
		}
	}

	/**
	 * Handle ad slot shortcode
	 * @param array $atts Shortcode attributes
	 *			string		'slot' Required slot name
	 *			int|string	'campaign' Term ID or slug for sponsorship_campaign taxonomy; may be a parent campaign
	 * @return string HTML output for ad slot
	 */
	function slot_shortcode( $atts ) {

		extract( shortcode_atts(
			array(
				'slot' => '',
				'campaign' => '',
				'post' => '',
			), $atts, $this->shortcode_name )
		);

		// valid slot is required
		if ( empty( $slot ) || ! $this->slot_is_active( $slot ) ) {
			return '';
		}
		// build optional query args with campaign or post atts
		$args = array();
		if ( ! empty( $campaign ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'sponsorship_campaign',
				'field' => is_numeric( $campaign ) ? 'term_id' : 'slug',
				'terms' => is_numeric( $campaign ) ? intval( $campaign ) : $campaign,
				'include_children' => true,
			);
		}

		if ( ! empty( $post ) ) {
			$args['p'] = intval( $post );
		}

		return $this->render_ad_slot( $slot, $args );
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
	echo Sponsorship_Manager_Ad_Slots::instance()->render_ad_slot( $slot_name, $args );
}