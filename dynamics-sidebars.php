<?php if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Plugin Name: Dynamics Sidebars
 * Description: Create a custom widget area (sidebar) for pages, posts and custom post types.
 * Author: Alysson Bortoli
 * Author Name: Alysson Bortoli
 * Author URI: http://twitter.com/akbortoli
 * Version: 1.0.4
 * License: GPLv2 or later
 */

/*  Copyright 2012  Alysson Bortoli  (email : alysson.web@gmail.com)

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
if ( ! defined( 'DS_PLUGIN_FOR_FRONT_PAGE' ) )
	define( 'DS_PLUGIN_FOR_FRONT_PAGE', true );

// should render on page for posts
if ( ! defined( 'DS_PLUGIN_FOR_POSTS_PAGE' ) )
	define( 'DS_PLUGIN_FOR_POSTS_PAGE', true );

// define theme localization domain
define( 'DS_PLUGIN_I18N_DOMAIN', 'dynamic-sidebars' );

// custom field name
define( 'DS_PLUGIN_CUSTOM_FIELD', 'dynamic_sidebar' );

// ------------------------------------------------------------

include_once( 'includes/api.php' );
include_once( 'includes/class-dynamic-sidebars.php' );

// ------------------------------------------------------------

$dynamic_sidebars_class = Dynamic_Sidebars::get_instance();