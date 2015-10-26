<?php
/**
 * Sponsorship Manager Fields
 */

/* Begin data structures */

// Fieldmanager Fields
require_once( SPONSORSHIP_MANAGER_PATH . '/inc/fields.php' );

// Taxonomy Base Class
require_once( SPONSORSHIP_MANAGER_PATH . '/inc/taxonomies/class-sponsorship_manager-taxonomy.php' );

// Sponsorship Campaigns Taxonomy (tax:sponsorship_campaign)
require_once( SPONSORSHIP_MANAGER_PATH . '/inc/taxonomies/class-sponsorship_manager-taxonomy-sponsorship-campaign.php' );

// Sponsorship Campaign Posts Taxonomy (tax:sponsorship_campaign_posts)
require_once( SPONSORSHIP_MANAGER_PATH . '/inc/taxonomies/class-sponsorship_manager-taxonomy-sponsorship-campaign-posts.php' );

/* End data structures */
