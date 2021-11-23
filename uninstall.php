<?php
// Avoid direct access
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


delete_option('strack_settings');

// Remove scheduled auto optimizer
wp_clear_scheduled_hook('strack_optimize');
?>