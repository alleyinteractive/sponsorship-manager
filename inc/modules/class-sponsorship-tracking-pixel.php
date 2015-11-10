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
	}

	/**
	 * Build url for type ( taxonomy or post type ) from config
	 *
	 * @param string $type Taxonomy or post type to retrieve from config
	 * @param int|string $id Numeric ID of post or term
	 */
	public function get_url( $type, $id ) {
		if ( empty( $this->config[ $type ] ) || empty( $id ) ) {
			return;
		}

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
	 * Define tracking pixel function without remove other JS functionality added to the sponsorshipManagerPlugin global
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
			return;
		}

		?>
			<script>sponsorshipManagerPlugin.insertPixel( <?php echo wp_json_encode( $pixel_url ); ?>, <?php echo wp_json_encode( $param ); ?> );</script>
		<?php
	}
}
