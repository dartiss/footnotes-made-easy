<?php
/**
* Uninstaller
*
* Uninstall the plugin by removing any options from the database
*
* @package	simple-footnotes
* @since	1.0
*/

// If the uninstall was not called by WordPress, exit

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }

// Delete any saved data

delete_option( 'swas_footnote_options' );
