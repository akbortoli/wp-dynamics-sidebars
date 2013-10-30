<?php

// if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    wp_die();
}

global $wpdb;

/**
 * @deprecated @since v1.0.7
 */
$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", 'dynamic_sidebar' );
$wpdb->query( $query );

/**
 * @since v1.0.7
 */
$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", 'custom_sidebar' );
$wpdb->query( $query );

do_action( 'cs_plugin_uninstall' );