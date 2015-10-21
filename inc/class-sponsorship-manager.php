<?php
/**
 * Sponsorship Manager
 */
class Sponsorship_Manager {
	/**
	 * Taxonomy of the Sponsorship Manager
	 *
	 * @var string
	 */
	public $taxonomy = 'sponsorship_campaign';

	/**
	 * @var Sponsorship_Manager
	 */
	protected static $instance;

	/**
	 * Sponsor Object
	 *
	 * @var object
	 */
	protected $sponsor;

	/**
	 * Protected Contructor
	 */
	protected function __construct() {
		add_action( 'the_post', array( $this, 'set_sponsor_from_post' ) );
		add_filter( 'the_content', array( $this, 'insert_tracking_pixel_code' ) );
	}

	public function __clone() {
		wp_die( __( "Please don't __clone ", 'sponsorship-manager' ) . __CLASS__ );
	}

	public function __wakeup() {
		wp_die( __( "Please don't __wakeup ", 'sponsorship-manager' ) . __CLASS__ );
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
		return (array) apply_filters( 'sponsorship_manager_enabled_post_types', array( 'post', 'video' ) );
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
		if ( empty( $post->ID ) ) {
			$post = get_post();
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
	 */
	public function get_sponsor_parent( $sponsor = null ) {
		if ( empty( $sponsor ) ) {
			$sponsor = $this->get_sponsor();
		}

		if ( ! empty( $sponsor->parent ) ) {
			// Retrieve sponsor parent
			// ...
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

		if ( ! empty( $info[ $name ] ) && is_int( $info[ $name] ) ) {
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
