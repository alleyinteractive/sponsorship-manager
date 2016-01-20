<?php
/**
 * Class to manage DFP tracking pixel integration
 */

class Sponsorship_Tracking_Pixel {

	/**
	 * @var array Config data from theme
	 */
	protected $config;

	/**
	 * @var Whether we are on the post-new.php screen
	 */
	protected $is_post_new = false;


	/**
	 * @var Pixel URL template per https://support.google.com/dfp_premium/answer/2623168?rd=1
	 */
	protected $pixel_template = 'http://pubads.g.doubleclick.net/gampad/ad?iu=/%s/%s&c=123&sz=%s&t=%s';

	function __construct() {
		/**
		 * Get DFP tracking pixel config from theme
		 *
		 * @param bool|array False or array of config data
		 */
		$this->config = apply_filters( 'sponsorship_manager_tracking_pixel_config', null );

		add_action( 'wp_head', array( $this, 'define_js' ) );
		add_action( 'load-post.php', array( $this, 'display_targeting_info' ) );
		add_action( 'load-post-new.php', array( $this, 'display_targeting_info' ) );
	}

	/**
	 * Build url for type ( taxonomy or post type ) from config. Do not call this directly, as it may have been overridden
	 *
	 * @param string $type Taxonomy or post type to retrieve from config
	 * @param int|string $id Numeric ID of post or term
	 */
	public function get_url( $type, $id ) {
		if ( empty( $this->config[ $type ] ) || empty( $id ) ) {
			return;
		}

		// see https://support.google.com/dfp_premium/answer/2623168?rd=1
		$network = $this->get_config( 'network' );
		$unit = $this->get_config( $type, 'unit' );
		$size = $this->get_config( $type, 'size' );
		$key = $this->get_config( $type, 'key' );
		$value = strval( $id );

		if ( empty( $network ) || empty( $unit ) || empty( $size ) || empty( $key ) || empty( $value ) ) {
			return;
		}

		// double urlencode per https://support.google.com/dfp_premium/answer/2623168?rd=1
		$key_value = urlencode( urlencode( $key ) . '=' . urlencode( $value ) );
		return sprintf( $this->pixel_template, $network, $unit, $size, $key_value );
	}

	/**
	 * get value from the DFP config object
	 *
	 * @param string $key Primary key
	 * @param string $secondary_key Optional. Secondary key
	 */
	protected function get_config( $key, $secondary_key = null ) {
		if ( empty( $secondary_key ) ) {
			return ( ! empty( $this->config[ $key ] ) ) ? $this->config[ $key ] : '';
		} else {
			return ( ! empty( $this->config[ $key ][ $secondary_key ] ) ) ? $this->config[ $key ][ $secondary_key ] : '';
		}
	}

	/**
	 * Define tracking pixel function without removing other JS functionality
	 * that might have been added to the sponsorshipManagerPlugin global
	 */
	public function define_js() {
		?>
		<script>
			var sponsorshipManagerPlugin = sponsorshipManagerPlugin || {};
			sponsorshipManagerPlugin.insertPixel = function( pixelUrl, param ) {
				// make a new, unique cachebuster paramater for the pixel URL
				var regex = new RegExp( '\\?.*' + param + '=([\\d]+)' );
				pixelUrl = pixelUrl.replace( regex, function(match, oldC) {
					var newC = Date.now().toString() + Math.floor( Math.random() * 1000 ).toString();
					return match.replace( oldC, newC );
				} );
				var sponsorshipPixel = document.createElement( 'img' );
				sponsorshipPixel.src = pixelUrl;
				sponsorshipPixel.className = 'sponsorship-manager-tracking-pixel';
				sponsorshipPixel.style.position = 'absolute';
				// append to body to fire tracking pixel
				if ( document.body ) {
					document.body.appendChild( sponsorshipPixel );
				}
			};
		</script>
	<?php }

	/**
	 * Print pixel URL in console to aid in debugging when user is logged in
	 * and 'sponsorship_manager_tracking_pixel_when_logged_in' filter is false
	 *
	 * @param string Pixel URL
	 */
	protected function console_log_pixel_url( $pixel_url ) {
		?>
		<script>
			if ( typeof console !== 'undefined' && typeof console.log !== 'undefined' ) {
				<?php // the only escaping we need is to avoid breaking the JS string ?>
				console.log( 'Tracking pixel <?php echo addslashes( $pixel_url ); ?> not inserted' );
			}
		</script>
	<?php }

	/**
	 * Helper function for tracking pixel
	 *
	 * @param string $pixel_url URL of tracking pixel
	 * @param string $param Key of cachebusting parameter, defaults to 'c' for DFP
	 */
	function insert_tracking_pixel( $pixel_url, $param = 'c' ) {
		// allow a replacement pixel URL for debugging dev environments
		if ( $dev_pixel = apply_filters( 'sponsorship_manager_override_pixel_url', false, $pixel_url, $param ) ) {
			$pixel_url = $dev_pixel;
		}

		if ( empty( $pixel_url ) ) {
			return;
		}

		// cases where we don't want to trigger a pixel
		$trigger_for_logged_in = is_user_logged_in() && apply_filters( 'sponsorship_manager_tracking_pixel_when_logged_in', false );
		if ( is_admin() || is_preview() || ! $trigger_for_logged_in ) {
			$this->console_log_pixel_url( $pixel_url );
			return;
		}

		?>
			<script>sponsorshipManagerPlugin.insertPixel( <?php echo wp_json_encode( $pixel_url ); ?>, <?php echo wp_json_encode( $param ); ?> );</script>
		<?php
	}

	/**
	 * Setup action to render targeting info when adding/editing a post
	 */
	public function display_targeting_info() {
		$this->is_post_new = 'load-post-new.php' === current_filter();
		add_filter( 'fm_element_markup_end', array( $this, 'render_targeting_info' ), 10, 2 );
	}

	/**
	 * Display DFP targeting setup info when adding/editing a post
	 * @param string $html HTML output
	 * @param objct $field Fieldmanager_Field object
	 * @return string HTML ouput
	 */
	public function render_targeting_info( $html, $field ) {
		// check field
		$post_type = get_post_type();
		if ( 'sponsorship-info' !== $field->name || ! in_array( $post_type, sponsorship_manager()->get_enabled_post_types(), true ) ) {
			return $html;
		}


		$targeting_info = '<div class="sponsorship-manager targeting-info">' .
			'<h4>' . esc_html__( 'DFP Targeting Info', 'sponsorship-manager' ) . '</h4>';

		// creating a new post
		if ( $this->is_post_new ) {
			$targeting_info .= '<p>' . esc_html__( 'Targeting info will be available after selecting a Sponsorship Campaign and saving.', 'sponsorship-manager' ) . '</p>';
		}
		// editing a post that does not have a sponsor
		elseif ( ! sponsorship_post_is_sponsored() ) {
			return $html;
		}
		// post is sponsored in WP but DFP is not configured
		elseif ( empty( $this->config[ $post_type ] ) ) {
			$targeting_info .= '<p>' . esc_html__( 'Targeting info is not available for this post type.', 'sponsorship-manager' ) . '</p>';
		}
		// post is sponsored and DFP is configured
		else {
			$targeting_info .= '<p>' . esc_html__( 'Ad Unit: ', 'sponsorship-manager' ) . esc_html( $this->config[ $post_type ]['unit'] ) . '<br>';
			$targeting_info .= esc_html__( 'Creative Size: ', 'sponsorship-manager' ) . esc_html( $this->config[ $post_type ]['size'] ) . '<br>';
			$targeting_info .= esc_html__( 'Key: ', 'sponsorship-manager' ) . esc_html( $this->config[ $post_type ]['key'] ) . '<br>';
			$targeting_info .= esc_html__( 'Value: ', 'sponsorship-manager' ) . intval( get_the_ID() ) . '</p>';

			$sponsorship = new Sponsorship_Manager_Post_Template();
			$targeting_info .= '<p>' . esc_html__( 'Tracking Pixel URL: ', 'sponsorship-manager' ) . esc_url( $sponsorship->get_post_sponsorship( 'dfp-tracking-pixel' ) ) . '</p>';
		}

		$targeting_info .= '</div><!-- /.targeting-info -->';

		return $html . "\n" . $targeting_info;
	}
}
