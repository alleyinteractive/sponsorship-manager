<?php
/**
 * Sponsorship Manager Plugin
 *
 * @package Sponsorship
 * @version 0.0.1
 */

/*
  Plugin Name: Sponsorship Manager
  Plugin URI: http://www.alleyinteractive.com/
  Description: Manage sponsored content campaigns from within WordPress
  Author: Alley Interactive
  Version: 0.0.1
  Author URI: http://www.alleyinteractive.com/
*/

/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Filesystem Path to Sponsorship Manager
 *
 * @var string
 */
define( 'SPONSORSHIP_MANAGER_PATH', dirname( __FILE__ ) );

/**
 * Slug of taxonomy representing sponsorship campaigns
 *
 * @var string
 */
define( 'SPONSORSHIP_MANAGER_CAMPAIGN_TAXONOMY', 'sponsorship_campaign' );

/**
 * wait until after theme is loaded before setting up the plugin
 */
function sponsorship_manager_setup() {
	// Load helpers
	require_once( SPONSORSHIP_MANAGER_PATH . '/inc/modules/class-sponsorship-tracking-pixel.php' );

	// Load archiveless class
	require_once( SPONSORSHIP_MANAGER_PATH . '/inc/modules/class-sponsorship-archiveless.php' );

	// Load main plugin class
	require_once( SPONSORSHIP_MANAGER_PATH . '/inc/modules/class-sponsorship-manager.php' );

	// Load campaign class
	require_once( SPONSORSHIP_MANAGER_PATH . '/inc/modules/class-sponsorship-manager-campaign.php' );

	// Load post templating class
	require_once( SPONSORSHIP_MANAGER_PATH . '/inc/modules/class-sponsorship-manager-post-template.php' );

	// Load template tags
	require_once( SPONSORSHIP_MANAGER_PATH . '/inc/template-tags.php' );

	// Load Fieldmanager Fields
	require_once( SPONSORSHIP_MANAGER_PATH . '/functions.php' );
}
add_action( 'after_setup_theme', 'sponsorship_manager_setup', 99 );
