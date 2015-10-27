<?php
/**
 * Sponsorship Manager
 *
 * @package Sponsorship
 */
class Sponsorship_Manager {
	/**
	 * Taxonomy of the Sponsorship Manager
	 *
	 * @var string
	 */
	public $taxonomy = 'sponsorship_campaign';

	/**
	 * Singleton Instance
	 *
	 * @var Sponsorship_Manager
	 */
	protected static $instance;

	/**
	 * Current Sponsor Term Object
	 *
	 * @var object
	 */
	protected $sponsor;

	/**
	 * Term for hiding posts from recent posts
	 *
	 * @var string
	 */
	protected $term_hidden_from_loop = '_hidden_from_loop';

	/**
	 * Term slug for hiding post from feed
	 *
	 * @var string
	 */
	protected $term_hidden_from_feed = '_hidden_from_feed';

	/**
	 * Default post types that can be sponsored
	 *
	 * @var array
	 */
	protected $default_post_types = array( 'post' );

	/**
	 * Protected Contructor
	 */
	protected function __construct() {
		add_action( 'the_post', array( $this, 'set_sponsor_from_post' ) );
		add_filter( 'the_content', array( $this, 'insert_tracking_pixel_code' ) );

		// Hide posts from the loop/feed
		add_filter( 'pre_get_posts', array( $this, 'hide_campaign_posts' ) );
		add_action( 'save_post', array( $this, 'hide_posts_on_save' ), 99, 2 );
	}

	public function __clone() {
		wp_die( esc_html__( "Please don't __clone ", 'sponsorship-manager' ) . __CLASS__ );
	}

	public function __wakeup() {
		wp_die( esc_html__( "Please don't __wakeup ", 'sponsorship-manager' ) . __CLASS__ );
	}

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
	 * Retrieve enabled post types for Sponsorship Manager
	 *
	 * @return array
	 */
	public function get_enabled_post_types() {
		return (array) apply_filters( 'sponsorship_manager_enabled_post_types', $this->default_post_types );
	}

	/**
	 * Set the current sponsor
	 *
	 * @param object
	 * @return void
	 */
	public function set_sponsor( $sponsor = null ) {
		if ( ! empty( $sponsor->taxonomy ) && $this->taxonomy === $sponsor->taxonomy ) {
			$this->sponsor = $sponsor;
		} else {
			$this->sponsor = null;
		}
	}

	/**
	 * Retrieve the current sponsor
	 *
	 * @return object|void
	 */
	public function get_sponsor() {
		return $this->sponsor;
	}

	/**
	 * Set the current sponsor to a post's sponsor
	 *
	 * @return object
	 */
	public function set_sponsor_from_post( $post = null ) {
		$this->set_sponsor( $this->get_post_sponsor( $post ) );
		return $this->get_sponsor();
	}

	/**
	 * Retrieve the post's sponsor
	 *
	 * @param WP_Post|void Optional post object
	 * @return object|void Sponsor Term or null if one isn't set.
	 */
	public function get_post_sponsor( $post = null ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( absint( $post ) );
		} else if ( empty( $post ) ) {
			$post = get_post();
		}

		if ( 'WP_Post' !== get_class( $post ) ) {
			return null;
		}

		$sponsor = get_the_terms( $post->ID, $this->taxonomy );

		if ( ! empty( $sponsor ) ) {
			return array_shift( $sponsor );
		}
	}

	/**
	 * Retrieve parent sponsor
	 *
	 * @param object $sponsor Optional sponsor object. Defaults to current post's sponsor
	 * @return object Sponsor's parent term
	 */
	public function get_sponsor_parent( $sponsor = null ) {
		if ( empty( $sponsor ) ) {
			$sponsor = $this->get_sponsor();
		}

		if ( ! empty( $sponsor->parent ) ) {
			// Retrieve sponsor parent
			return get_term( $sponsor->parent, $this->taxonomy );
		} else {
			return null;
		}
	}

	/**
	 * Retrieve the sponsorship campaign from its taxonomy
	 *
	 * @return array
	 */
	protected function get_sponsor_info( $sponsor = null ) {
		if ( empty( $sponsor ) ) {
			$sponsor = $this->get_sponsor();
		} elseif ( ! is_object( $sponsor ) ) {
			$sponsor = get_term( $sponsor, 'sponsor' );
		}

		if ( ! empty( $sponsor->term_id ) ) {
			return fm_get_term_meta( $sponsor->term_id, $sponsor->taxonomy, 'sponsorship-campaign-display', true );
		} else {
			return null;
		}
	}

	/**
	 * Retrieve Sponsorship Campaign information from the current post
	 *
	 * @return array
	 */
	protected function get_post_sponsor_info( $post = null ) {
		if ( null === $post ) {
			$post = get_post();
		}

		return get_post_meta( $post->ID, 'sponsorship-campaign-info', true );
	}

	/**
	 * Retrieve the sponsor's image from its stored information
	 *
	 * @param  string $image Image name to retrieve.
	 * @param  string $size Image size.
	 * @param  object|int $sponsor Optional sponsor Object or ID. Defaults to current post sponsor
	 * @return array Attachment Information
	 */
	public function get_sponsor_image( $name, $size = 'full', $sponsor = null ) {
		$info = $this->get_sponsor_info( $sponsor );

		if ( ! empty( $info[ $name ] ) && is_int( $info[ $name ] ) ) {
			$attachment = wp_get_attachment_image_src( $info[ $name ], $size );

			if ( ! empty( $attachment ) ) {
				return $attachment;
			}
		}
	}

	/**
	 * Retrieve the external sponsor's URL
	 *
	 * @param  object|int Optional sponsor Object or ID. Defaults to current post sponsor
	 * @return string|null Sponsor's external URL or null if not found.
	 */
	public function get_sponsor_url( $sponsor = null ) {
		$info = $this->get_sponsor_info( $sponsor );
		return ( ! empty( $info['external-url'] ) ) ? $info['external-url'] : null;
	}

	/**
	 *  Retrieve the sponsor's hub
	 *
	 * @param  object|int Optional sponsor Object or ID. Defaults to current post sponsor
	 * @return string|null Sponsor's hub URL or null if not found.
	 */
	public function get_sponsor_hub_url( $sponsor = null ) {
		if ( empty( $sponsor->term_id ) ) {
			$sponsor = $this->get_sponsor();

			if ( empty( $sponsor->term_id ) ) {
				return;
			}
		}

		if ( function_exists( 'wpcom_vip_get_term_link' ) ) {
			return wpcom_vip_get_term_link( $sponsor );
		}
		return get_term_link( $sponsor );
	}

	/**
	 * Retrieve the tracking pixel URL
	 *
	 * @param  object|int Optional sponsor Object or ID. Defaults to current post sponsor
	 * @return string|null Tracking pixel URL or null if not found.
	 */
	public function get_sponsor_tracking_pixel( $sponsor = null ) {
		$info = $this->get_post_sponsor_info( $sponsor );
		if ( ! empty( $info['dfp-tracking-pixel'] ) ) {
			return add_query_arg( array( 'c' => wp_rand() ), $info['dfp-tracking-pixel'] );
		}
	}

	/**
	 * Insert the sponsored content tracking pixel
	 *
	 * @param string $content Post content
	 */
	public function insert_tracking_pixel_code( $content ) {
		$dfp_pixel_url = $this->get_sponsor_tracking_pixel();
		if ( empty( $dfp_pixel_url ) ) {
			return $content;
		}
		ob_start();
		?>
		<script>
			var sponsorshipPixelUrl = <?php echo wp_json_encode( $dfp_pixel_url ); ?>;
			var sponsorshipPixel = document.createElement( 'img' );
			sponsorshipPixel.src = sponsorshipPixelUrl;
			if ( document.body ) {
				console.log(sponsorshipPixel);
				document.body.appendChild( sponsorshipPixel );
			}
		</script>
		<?php
		$content .= ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Create campaign post term if it doesn't exist
	 *
	 * @param  string $slug Term slug
	 * @param  string $taxonomy Taxonomy name. Defaults to 'sponsorship_campaign_posts'
	 * @return object
	 */
	protected function get_or_create_term( $slug, $taxonomy = 'sponsorship_campaign_posts' ) {
		if ( function_exists( 'wpcom_vip_get_term_by' ) ) {
			$term = wpcom_vip_get_term_by( 'slug', $slug, $taxonomy );
		} else {
			$term = get_term_by( 'slug', $slug, $taxonomy );
		}

		if ( ! empty( $term ) ) {
			return $term;
		}

		$term_data = wp_insert_term( $slug, $taxonomy );
		if ( is_wp_error( $term_data ) || empty( $term_data['term_id'] ) ) {
			return false;
		}
		return ( ! empty( $term_data['term_id'] ) ) ? get_term( $term_data['term_id'], $taxonomy ) : false;
	}
	/**
	 * Mark a post as hidden with the sponsorship campaign posts taxonomy
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function hide_posts_on_save( $post_id, WP_Post $post ) {
		if ( 'publish' !== $post->post_status || ! in_array( $post->post_type, $this->get_enabled_post_types() ) ) {
			return;
		}

		// Default hidden status
		$hidden_from_loop = $hidden_from_feed = false;

		// Check if the post is assigned to a campaign (if not, don't hide it at all)
		if ( has_term( '', $this->taxonomy, $post ) ) {
			$sponsor_info = $this->get_post_sponsor_info( $post );
			$hidden_from_loop = ( ! empty( $sponsor_info['hide-from-recent-posts'] ) && '1' === $sponsor_info['hide-from-recent-posts'] );
			$hidden_from_feed = ( ! empty( $sponsor_info['hide-from-feeds'] ) && '1' === $sponsor_info['hide-from-feeds'] );
		}

		// Build the terms for this post
		$terms = array();
		if ( $hidden_from_loop ) {
			$terms[] = $this->get_or_create_term( $this->term_hidden_from_loop )->term_id;
		}
		if ( $hidden_from_feed ) {
			$terms[] = $this->get_or_create_term( $this->term_hidden_from_feed )->term_id;
		}

		wp_set_object_terms( $post->ID, $terms, 'sponsorship_campaign_posts', false );
	}

	/**
	 * Omit campaign posts from the main loop
	 *
	 * @param WP_Query $query
	 */
	public function hide_campaign_posts( WP_Query $query ) {
		if ( ! $query->is_main_query() || ( ! $query->is_archive() && ! $query->is_home() && ! $query->is_feed() ) ) {
			return $query;
		}

		// Term to hide posts from main loop
		if ( $query->is_archive() || $query->is_home() ) {
			$hidden_posts_term = $this->get_or_create_term( $this->term_hidden_from_loop );
		} elseif ( $query->is_feed() ) {
			$hidden_posts_term = $this->get_or_create_term( $this->term_hidden_from_feed );
		} else {
			return;
		}

		$tax_query = array(
			'taxonomy' => 'sponsorship_campaign_posts',
			'terms'    => array( $hidden_posts_term->term_id ),
			'operator' => 'NOT IN',
		);

		$query->tax_query->queries[] = $tax_query;
		$query->query_vars['tax_query'] = $query->tax_query->queries;
		return $query;
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

// Start automatically
sponsorship_manager();
