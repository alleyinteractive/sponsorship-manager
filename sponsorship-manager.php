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

/**
 * Filesystem Path to Sponsorship Manager
 *
 * @var string
 */
define( 'SPONSORSHIP_MANAGER_PATH', dirname( __FILE__ ) );

// Sponsorship Manager Class
require_once( SPONSORSHIP_MANAGER_PATH . '/inc/class-sponsorship-manager.php' );

// Load Template Tags
require_once( SPONSORSHIP_MANAGER_PATH . '/inc/template-tags.php' );

// Load Fieldmanager Fields
require_once( SPONSORSHIP_MANAGER_PATH . '/functions.php' );
