<?php if ( ! defined( 'ABSPATH' ) ) wp_die();

/**
 * Dynamics Sidebars
 * Api Functions
 *
 */

if ( ! function_exists( 'the_sidebar' ) ) :

/**
 * Display or retrieve the current post sidebar.
 *
 * @uses get_the_sidebar()
 * @param string $fallback fallback sidebar id or name
 * @param string $echo if set to TRUE value will be ECHOed, if set to FALSE value will be RETURNed
 * @return string
 */
function the_sidebar( $fallback = '', $echo = false )
{
	$sidebar = get_the_sidebar();

	if ( empty( $sidebar ) && ! empty( $fallback ) )
		$sidebar = $fallback;

	if ( $echo )
		echo $sidebar;
	else
		return $sidebar;
}

endif;

// ------------------------------------------------------------

if ( ! function_exists( 'get_the_sidebar' ) ) :

/**
 * Retrieve post sidebar.
 *
 * @param int $post_id (Optional) Post ID.
 * @return string
 */
function get_the_sidebar( $post_id = 0 )
{
	$post = &get_post( $post_id );

	$page_on_front  = absint( get_option( 'page_on_front' ) );
	$page_for_posts = absint( get_option( 'page_for_posts' ) );
	
	if ( ! post_type_supports( $post->post_type, 'custom-sidebar' ) ) {
		return apply_filters( 'the_sidebar', '', $post_id );
	} elseif ( ! DS_PLUGIN_FOR_FRONT_PAGE && $page_on_front == $post->ID ) {
		return apply_filters( 'the_sidebar', '', $post_id );
	} elseif ( ! DS_PLUGIN_FOR_POSTS_PAGE && $page_for_posts == $post->ID ) {
		return apply_filters( 'the_sidebar', '', $post_id );
	} else {
		$sidebar = get_post_meta( $post->ID, 'dynamic_sidebar', true );
		return apply_filters( 'the_sidebar', $sidebar, $post_id );
	}
}

endif;

// ------------------------------------------------------------

if ( ! function_exists( 'has_sidebar' ) ) :

/**
 * Check if post has a sidebar..
 *
 * @uses get_the_sidebar()
 * @param int $post_id (Optional) Post ID.
 * @return bool
 */
function has_sidebar( $post_id = 0 )
{
	$sidebar = get_the_sidebar( $post_id );
	if ( ! $sidebar || empty( $sidebar ) ) {
		return false;
	}

	return true;
}

endif;

// ------------------------------------------------------------

if ( ! function_exists( 'get_custom_sidebars' ) ) :

/**
 * Get custom sidebars
 * 
 * Get all custom sidebars (custom only)
 *
 * @access public
 * @return object
 */
function get_custom_sidebars()
{
	global $wpdb;
	$query = $wpdb->prepare( "SELECT post_id, meta_value as name FROM $wpdb->postmeta WHERE meta_key = '%s' GROUP BY meta_value", DS_PLUGIN_CUSTOM_FIELD );
	return $wpdb->get_results( $query, OBJECT );
}

endif;

// ------------------------------------------------------------

if ( ! function_exists( 'get_all_sidebars' ) ) :

/**
 * Get all sidebars
 * 
 * Gel all registered sidebars
 *
 * @access public
 * @return array
 */
function get_all_sidebars()
{
	global $wp_registered_sidebars;
	return $wp_registered_sidebars;
}

endif;