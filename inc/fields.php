<?php


/**
 * `sponsorship-campaign-display` Fieldmanager fields.
 */
function sponsorship_manager_fm_tax_sponsorship_campaign_sponsorship_campaign_display() {
	$fm = new Fieldmanager_Group( array(
		'name' => 'sponsorship-campaign-display',
		'children' => apply_filters( 'sponsorship_manager_term_fields', array(
			'logo-primary' => new Fieldmanager_Media( __( 'Primary Logo', 'sponsorship-manager' ) ),
			'logo-secondary' => new Fieldmanager_Media( __( 'Secondary Logo', 'sponsorship-manager' ) ),
			'featured-image' => new Fieldmanager_Media( __( 'Campaign Featured Image', 'sponsorship-manager' ) ),
			'external-url' => new Fieldmanager_Link( array(
				'label' => __( 'Campaign External URL', 'sponsorship-manager' ),
				'description' => __( "e.g. sponsor's website.", 'sponsorship-manager' ),
			) ),
			'hub-type' => new Fieldmanager_Select( array(
				'label' => __( 'Campaign Hub Type', 'sponsorship-manager' ),
				'options' => array(
					'term-link' => __( 'Term archive, e.g. site.com/sponsors/campaign-name', 'sponsorship-manager' ),
					'custom' => __( 'Specify a URL for the campaign hub page', 'sponsorship-manager' ),
					'none' => __( 'Do not link to a campaign hub page', 'sponsorship-manager' ),
				),
			) ),
			'hub' => new Fieldmanager_Link( array(
				'label' => __( 'Campaign Hub URL', 'sponsorship-manager' ),
				'display_if' => array(
					'src' => 'hub-type',
					'value' => 'custom',
				),
				'description' => __( 'URL of custom custom page for this campaign.', 'sponsorship-manager' ),
			) ),
			'dfp-tracking-pixel' => new Fieldmanager_Link( array(
				'label' => __( 'DFP Tracking Pixel URL for Campaign Hub Page', 'sponsorship-manager' ),
				'description' => __( "Use this field to override default DFP pixel URL. Include 'c' parameter; cache busting will be handled on output", 'sponsorship-manager' ),
			) ),
			'tagline' => new Fieldmanager_TextField( array(
				'label' => __( 'Tagline', 'sponsorship-manager' ),
				'default_value' => __( 'Sponsor content by ', 'sponsorship-manager' ),
				'description' => __( 'Use a phrase clearly indicating that this is a paid advertisement.', 'sponsorship-manager' ),
			) ),
			'richdescription' => new Fieldmanager_RichTextArea( __( 'Campaign Description', 'sponsorship-manager' ) ),
		) ),
	) );
	$fm->add_term_form( __( 'Display Fields', 'sponsorship-manager' ), array( 'sponsorship_campaign' ) );
}
add_action( 'fm_term_sponsorship_campaign', 'sponsorship_manager_fm_tax_sponsorship_campaign_sponsorship_campaign_display' );

/**
 * `sponsorship-info` Fieldmanager fields.
 */
function sponsorship_manager_fm_sponsorship_info() {
	$fm = new Fieldmanager_Group( array(
		'name' => 'sponsorship-info',
		'children' => apply_filters( 'sponsorship_manager_post_fields', array(
			'sponsorship-campaign' => new Fieldmanager_Select( array(
				'label' => __( 'Select Campaign', 'sponsorship-manager' ),
				'first_empty' => true,
				'datasource' => new Fieldmanager_Datasource_Term( array(
					'taxonomy' => 'sponsorship_campaign',
					'taxonomy_hierarchical' => true,
					'taxonomy_hierarchical_depth' => 2,
					'taxonomy_save_to_terms' => true,
					'only_save_to_taxonomy' => true,
				) ),
			) ),
			'dfp-tracking-pixel' => new Fieldmanager_Link( array(
				'label' => __( 'DFP Tracking Pixel URL', 'sponsorship-manager' ),
				'description' => __( "Use this field to override default DFP pixel URL. Include 'c' parameter; cache busting will be handled on output.", 'sponsorship-manager' ),
			) ),
			'archiveless' => new Fieldmanager_Checkbox( __( 'Hide from standard frontend queries (Recent Posts, author archive, etc.) and RSS feeds', 'sponsorship-manager' ) ),
		) ),
	) );
	$fm->add_meta_box( __( 'Sponsorship Campaign', 'sponsorship-manager' ), sponsorship_manager()->get_enabled_post_types() );
}
foreach ( sponsorship_manager()->get_enabled_post_types() as $post_type ) {
	add_action( 'fm_post_' . $post_type, 'sponsorship_manager_fm_sponsorship_info' );
}

/**
 * Render fallback Sponsorship Campaigns meta box if taxonomy is empty
 */
function sponsorship_manager_fallback_meta_box() {
	if ( ! is_admin() ) {
		return;
	}

	$campaigns = get_terms( 'sponsorship_campaign', array(
		'get' => 'all',
		'fields' => 'count',
	) );

	if ( is_wp_error( $campaigns ) || 0 === intval( $campaigns )  ) {
		foreach ( sponsorship_manager()->get_enabled_post_types() as $post_type ) {
			remove_action( 'fm_post_' . $post_type, 'sponsorship_manager_fm_sponsorship_info' );
		}
		add_action( 'add_meta_boxes', array( sponsorship_manager(), 'fallback_meta_box' ) );
	}
}
add_action( 'init', 'sponsorship_manager_fallback_meta_box', 11 );

/**
 * hide term description field since we have a Fieldmanager_RichTextArea instead
 */
function sponsorship_manager_hide_term_description() {
	if ( apply_filters( 'sponsorship_manager_override_campaign_description', true ) ) : ?>
		<script>
			jQuery('.term-description-wrap').hide();
		</script>
	<?php endif;
}
add_action( 'sponsorship_campaign_add_form_fields', 'sponsorship_manager_hide_term_description' );
add_action( 'sponsorship_campaign_edit_form_fields', 'sponsorship_manager_hide_term_description' );
