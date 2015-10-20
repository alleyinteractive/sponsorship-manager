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
	 * Protected Contructor
	 */
	protected function __construct() {

	}

	public function __clone() {
		wp_die( __( "Please don't __clone ", 'foodrepublic' ) . __CLASS__ );
	}

	public function __wakeup() {
		wp_die( __( "Please don't __wakeup ", 'foodrepublic' ) . __CLASS__ );
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
			$sponsor = $this->get_post_sponsor();
		}

		if ( ! empty( $sponsor->parent ) ) {
			// Retrieve sponsor parent
			// ...
		}
	}

	/**
	 * Retrieve the sponsor's information from its post meta
	 *
	 * @return array
	 */
	public function get_sponsor_info( $sponsor = null ) {
		if ( empty( $sponsor ) ) {
			$sponsor = $this->get_post_sponsor();
		} elseif ( ! is_object( $sponsor ) ) {
			$sponsor = get_term( $sponsor, 'sponsor' );
		}

		if ( ! empty( $sponsor->term_id ) ) {
			return fm_get_term_meta( $sponsor->term_id, $sponsor->taxonomy, 'sponsorship-campaign-display', true );
		}
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
	 * Retrieve the sponsor's URL
	 *
	 * @param  object|int Optional sponsor Object or ID. Defaults to current post sponsor
	 * @return string|null Sponsor URL or null if not found.
	 */
	public function get_sponsor_url( $sponsor = null ) {
		$info = $this->get_sponsor_info( $sponsor );
		return ( ! empty( $info['url'] ) ) ? $info['url'] : null;
	}

	/**
	 * Retrieve the tracking pixel URL
	 *
	 * @param  object|int Optional sponsor Object or ID. Defaults to current post sponsor
	 * @return string|null Tracking pixel URL or null if not found.
	 */
	public function get_sponsor_tracking_pixel( $sponsor = null ) {
		$info = $this->get_sponsor_info( $sponsor );
		if ( ! empty( $info['tracking_pixel_url'] ) ) {
			return add_query_arg( array( 'c' => wp_rand() ), $info['tracking_pixel_url'] );
		}
	}

	/**
	 * Insert the sponsored content tracking pixel
	 *
	 * @param string $content Post content
	 * @param object $sponsor Optional sponsor. Defaults to the current post's sponsor
	 */
	public function insert_tracking_pixel_code( $content, $sponsor = null ) {
		$dfp_pixel_url = $this->get_sponsor_tracking_pixel( $sponsor );

		ob_start();
		?>
		<script>
			var sponsorshipPixelUrl = <?php echo wp_json_encode( $dfp_pixel_url ); ?>;
			var sponsorshipPixel = document.createElement( 'img' );
			sponsorshipPixel.src = sponsorshipPixelUrl;
			if ( document.body ) {
				document.body.appendChild( sponsorshipPixel );
			}
		</script>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
	 * Retrieve enabled post types for Sponsorship Manager
	 *
	 * @return array
	 */
	public function get_enabled_post_types() {
		return (array) apply_filters( 'sponsorship_manager_enabled_post_types', array( 'post', 'video' ) );
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
