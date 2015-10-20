<?php

/* begin fm:tax_sponsorship_campaign:sponsorship-campaign-display */
/**
 * `sponsorship-campaign-display` Fieldmanager fields.
 */
function sponsorship_manager_fm_tax_sponsorship_campaign_sponsorship_campaign_display() {
	$fm = new Fieldmanager_Group( array(
		'name' => 'sponsorship-campaign-display',
		'children' => array(
			'logo' => new Fieldmanager_Media( __( 'Campaign Logo', 'sponsorship-manager' ) ),
			'featured-image' => new Fieldmanager_Media( __( 'Campaign Featured Image', 'sponsorship-manager' ) ),
			'description' => new Fieldmanager_RichTextArea( __( 'Campaign Description', 'sponsorship-manager' ) ),
		),
	) );
	$fm->add_term_form( __( 'Display Fields', 'sponsorship-manager' ), array( 'sponsorship_campaign' ) );
}
add_action( 'fm_term_sponsorship_campaign', 'sponsorship_manager_fm_tax_sponsorship_campaign_sponsorship_campaign_display' );
/* end fm:tax_sponsorship_campaign:sponsorship-campaign-display */

/* begin fm:sponsorship-campaign-post-fields */
/**
 * `sponsorship-campaign-post-fields` Fieldmanager fields.
 */
function sponsorship_manager_fm_sponsorship_campaign_post_fields() {
	$fm = new Fieldmanager_Group( array(
		'name' => 'sponsorship-campaign-post-fields',
		'children' => array(
			'sponsorship-campaign' => new Fieldmanager_Select( array(
				'title' => __( 'Select Campaign', 'sponsorship-manager' ),
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
				'title' => __( 'DFP Tracking Pixel URL', 'sponsorship-manager' ),
				'description' => __( "Include 'c' parameter; cache busting will be handled on output", 'sponsorship-manager' ),
			) ),
			'hide-from-recent-posts' => new Fieldmanager_Checkbox( array(
				'title' => __( 'Hide from Recent Posts queries', 'sponsorship-manager' ),
				'checked_value' => true,
				'default_value' => true,
			) ),
			'hide-from-feeds' => new Fieldmanager_Checkbox( array(
				'title' => __( 'Hide from feed queries (RSS, etc)', 'sponsorship-manager' ),
				'checked_value' => true,
				'default_value' => true,
			) ),
		),
	) );
	$fm->add_meta_box( __( 'Sponsorship Campaign Fields', 'sponsorship-manager' ), array( '' ) );
}
/* end fm:sponsorship-campaign-post-fields */
