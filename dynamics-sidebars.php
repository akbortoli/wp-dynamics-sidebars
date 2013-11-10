<?php if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Plugin Name: Dynamics Sidebars
 * Description: Create a custom widget area (sidebar) for pages, posts and custom post types.
 * Author: Alysson Bortoli
 * Author Name: Alysson Bortoli
 * Author URI: http://twitter.com/akbortoli
 * Version: 1.0.7
 * License: GPLv2 or later
 */

/*  Copyright 2012-2013  Alysson Bortoli  (email : akbortoli@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// ------------------------------------------------------------

// should render on page on front
// the home page
if ( ! defined( 'CS_PLUGIN_FOR_FRONT_PAGE' ) ) {
    define( 'CS_PLUGIN_FOR_FRONT_PAGE', true );
}

// should render on page for posts
// the blog if different from home page
if ( ! defined( 'CS_PLUGIN_FOR_POSTS_PAGE' ) ) {
    define( 'CS_PLUGIN_FOR_POSTS_PAGE', true );
}

// should add support for pages
if ( ! defined( 'CS_PLUGIN_SUPPORT_FOR_PAGES' ) ) {
    define( 'CS_PLUGIN_SUPPORT_FOR_PAGES', true );
}

// should add support for posts
if ( ! defined( 'CS_PLUGIN_SUPPORT_FOR_POSTS' ) ) {
    define( 'CS_PLUGIN_SUPPORT_FOR_POSTS', true );
}

// should display column with the current sidebar name
if ( ! defined( 'CS_PLUGIN_COLUMN' ) ) {
    define( 'CS_PLUGIN_COLUMN', true );
}

// ------------------------------------------------------------

// define theme localization text domain
define( '_CS_PLUGIN_I18N_DOMAIN', 'custom-sidebar' );

// custom field name
define( '_CS_PLUGIN_CUSTOM_FIELD', 'custom_sidebar' );

// ------------------------------------------------------------

// load text domain
load_plugin_textdomain( _CS_PLUGIN_I18N_DOMAIN, null, dirname( plugin_basename( __FILE__ ) ) );

// ------------------------------------------------------------

include_once( 'includes/api.php' );
include_once( 'includes/class-custom-sidebar.php' );

// ------------------------------------------------------------

$custom_sidebar_plugin = $dynamic_sidebars_class = new Custom_Sidebar;

// install hook
register_activation_hook( __FILE__, array( $custom_sidebar_plugin, 'plugin_install' ) );

// deactivate hook
register_deactivation_hook( __FILE__, array( $custom_sidebar_plugin, 'plugin_deactivate' ) );