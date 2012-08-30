<?php

// if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	wp_die();

global $wpdb;

$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", 'dynamic_sidebar' );
$wpdb->query( $query );

do_action( 'ds_plugin_uninstall' );