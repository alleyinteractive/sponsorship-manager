<?php

/**
 * Adapted from https://github.com/alleyinteractive/archiveless
 */

class Sponsorship_Manager_Archiveless {

	private static $instance;

	public $status = 'archiveless';

	protected $meta_key = 'sponsorship-info';

	protected $archiveless_meta_key = 'archiveless';

	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Sponsorship_Manager_Archiveless;
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Register all actions and filters.
	 */
	public function setup() {
		add_action( 'init', array( $this, 'register_post_status' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'fool_edit_form' ) );
			add_action( 'parse_query', array( $this, 'allow_archiveless_when_publish' ), 10, 1 );
		} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'parse_query', array( $this, 'allow_archiveless_when_publish' ), 10, 1 );
		} else {
			add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		}
	}

	/**
	 * Register the custom post status.
	 */
	public function register_post_status() {
		/**
		 * Filters the arguments passed to `register_post_status()`.
		 *
		 * @see register_post_status().
		 */
		register_post_status( $this->status, apply_filters( 'sponsorship_manager_archiveless_post_status_args', array(
			'label'                     => __( 'Hidden from Archives', 'archiveless' ),
			'label_count'               => _n_noop( 'Hidden from Archives <span class="count">(%s)</span>', 'Hidden from Archives <span class="count">(%s)</span>', 'sponsorship-manager' ),
			'exclude_from_search'       => ! ( defined( 'WP_CLI' ) && WP_CLI ),
			'public'                    => true,
			'publicly_queryable'        => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
		) ) );
	}

	/**
	 * Set the custom post status when post data is being inserted.
	 *
	 * WordPress, unfortunately, doesn't provide a great way to _manage_ custom
	 * post statuses. While we can register and use them just fine, there are
	 * areas of the Admin where statuses are hard-coded. This method is part of
	 * this plugin's trickery to provide a seamless integration.
	 *
	 * @param  array $data Slashed post data to be inserted into the database.
	 * @param  array $postarr Raw post data used to generate `$data`. This
	 *                        contains, amongst other things, the post ID.
	 * @return array $data, potentially with a new status.
	 */
	public function wp_insert_post_data( $data, $postarr ) {
		// replace 'publish' with custom status when published post has "hide from..." box checked
		if ( 'publish' === $data['post_status'] ) {
			if ( ! empty( $_POST[ $this->meta_key ][ $this->archiveless_meta_key ] ) && '1' === $_POST[ $this->meta_key ][ $this->archiveless_meta_key ] ) {
				$data['post_status'] = $this->status;
			} elseif ( ! empty( $postarr['ID'] ) ) {
				if ( ! empty( $postarr[ $this->meta_key ][ $this->archiveless_meta_key ] ) && '1' === $postarr[ $this->meta_key ][ $this->archiveless_meta_key ] ) {
					$data['post_status'] = $this->status;
				}
			}
		}
		return $data;
	}

	/**
	 * Fool the edit screen into thinking that an archiveless post status is
	 * actually 'publish'. This lets WordPress use its hard-coded post statuses
	 * seamlessly.
	 */
	public function fool_edit_form() {
		global $post;
		if ( $this->status === $post->post_status ) {
			$post->post_status = 'publish';
		}
	}

	/**
	 * In wp-admin and WP_CLI, if a WP_Query allows 'publish', make sure it also allows 'archiveless'
	 * @param WP_Query $query
	 * @return none
	 */
	public function allow_archiveless_when_publish( $query ) {
		$status = $query->get( 'post_status' );
		if ( 'any' === $status ) {
			return;
		} elseif ( is_string( $status ) ) {
			$status = explode( ',', $status );
		}
		if ( in_array( 'publish', $status, true ) && ! in_array( $this->status, $status, true ) ) {
			$status[] = $this->status;
			$query->set( 'post_status', $status );
		}
	}

	/**
	 * Hide archiveless posts from applicable queries.
	 * We do this by _removing_ the archiveless status
	 * when it's not wanted, becaues WP defaults to querying all public statuses
	 *
	 * @param  string $where MySQL WHERE clause.
	 * @param  WP_Query $query Current WP_Query object.
	 * @return string WHERE clause, potentially with 'archiveless' post status
	 *                      removed.
	 */
	public function posts_where( $where, $query ) {
		global $wpdb;

		// make sure archiveless status is there in the first place
		if ( false === strpos( $where, " OR {$wpdb->posts}.post_status = '{$this->status}'" ) ) {
			return $where;
		}

		// default to hiding archiveless posts except for...
		$hide_archiveless = true;

		// show archiveless when NOT a main query or a feed...
		if ( ! $query->is_main_query() && ! $query->is_feed() ) {
			$hide_archiveless = false;
		} elseif ( $query->is_main_query() && ( $query->is_singular() || $query->is_tax( SPONSORSHIP_MANAGER_CAMPAIGN_TAXONOMY ) ) ) {
			// show archiveless when main query for singular or campaign archive
			$hide_archiveless = false;
		}

		/**
		 * Granular determination of hiding archiveless posts for a specific WP_Query
		 *
		 * @param bool $hide Return `true` to hide archiveless posts for `$query`, or `false` to show them.
		 * @param WP_Query $query Current query object
		 */
		$hide_archiveless = apply_filters( 'sponsorship_manager_hide_archiveless', $hide_archiveless, $query );

		// remove archiveless status from SQL query
		if ( $hide_archiveless ) {
			$where = str_replace( " OR {$wpdb->posts}.post_status = '{$this->status}'", '', $where );
		}

		return $where;
	}
}

// go go go
Sponsorship_Manager_Archiveless::instance();
