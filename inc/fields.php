<?php

/* begin fm:sponsorship-campaign-info */
/**
 * `sponsorship-campaign-info` Fieldmanager fields.
 */
function sponsorship_manager_fm_sponsorship_campaign_info() {
	$fm = new Fieldmanager_Group( array(
		'name' => 'sponsorship-campaign-info',
		'children' => array(
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
				'description' => __( "Include 'c' parameter; cache busting will be handled on output", 'sponsorship-manager' ),
			) ),
			'hide-from-recent-posts' => new Fieldmanager_Checkbox( array(
				'label' => __( 'Hide from Recent Posts queries', 'sponsorship-manager' ),
				'checked_value' => true,
				'default_value' => true,
			) ),
			'hide-from-feeds' => new Fieldmanager_Checkbox( array(
				'label' => __( 'Hide from feed queries (RSS, etc.)', 'sponsorship-manager' ),
				'checked_value' => true,
				'default_value' => true,
			) ),
		),
	) );
	$fm->add_meta_box( __( 'Sponsorship Campaign', 'sponsorship-manager' ), sponsorship_manager()->get_enabled_post_types() );
}
foreach ( sponsorship_manager()->get_enabled_post_types() as $post_type ) {
	add_action( 'fm_post_' . $post_type, 'sponsorship_manager_fm_sponsorship_campaign_info' );
}
/* end fm:sponsorship-campaign-info */

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
			'external-url' => new Fieldmanager_Link( __( 'Campaign External URL', 'sponsorship-manager' ) ),
			'description' => new Fieldmanager_RichTextArea( __( 'Campaign Description', 'sponsorship-manager' ) ),
		),
	) );
	$fm->add_term_form( __( 'Display Fields', 'sponsorship-manager' ), array( 'sponsorship_campaign' ) );
}
add_action( 'fm_term_sponsorship_campaign', 'sponsorship_manager_fm_tax_sponsorship_campaign_sponsorship_campaign_display' );
/* end fm:tax_sponsorship_campaign:sponsorship-campaign-display */
