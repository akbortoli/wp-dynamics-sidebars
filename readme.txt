=== Dynamics Sidebars ===

Contributors: alyssonweb
Tags: sidebar, custom, dynamic, widget, different
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6NTZTQUPXP8F2

Have a custom sidebar (widget area) for every pages, posts and/or custom post types.


== Description ==

Want your pages, posts and/or custom post types to have different sidebar?
An awesome plugin that let you have a custom sidebar (widget area) for every page, post and/or custom post type.

[Documentation](https://github.com/alyssonweb/wp-dynamics-sidebars/wiki) | [Support Forum](https://github.com/alyssonweb/wp-dynamics-sidebars/issues)

**Usage**

By default it will add 'custom-sidebar' support for the following post types:

* Post
* Page

**IMPORTANT: Showing the sidebar**

***Note you can use this wherever you like to show you sidebar***

`<?php
	dynamic_sidebar( get_the_sidebar() );
?>`

Or

`<?php
	$sidebar = get_the_sidebar();
	if ( is_active_sidebar( $sidebar ) ) {
		dynamic_sidebar( $sidebar );
	}
?>`

**Adding support for custom post type**

In order to user this plugin features with your custom post type you must add a feature suppport to it.
Do it by doing this:

On you 'functions.php' file
`<?php
	add_action( 'after_setup_theme', 'theme_setup' );

	function theme_setup()
	{
		add_post_type_support( 'post_type', 'custom-sidebar' );
		// add another one here
	}
?>`

When you register your custom post type, on 'register_post_type' call.

[Function Reference register_post_type](http://codex.wordpress.org/Function_Reference/register_post_type) for more information

`<?php
	$args = array( 'supports' => array( 'custom-sidebar' ) );
	register_post_type( 'post_type', $args );
?>`

**Removing support for pages, posts and/or custom post types**

To remove support from pages, posts and/or custom post type do like so:
On you 'functions.php' file add this

`<?php
	add_action( 'after_setup_theme', 'theme_setup' );

	function theme_setup()
	{
		remove_post_type_support( 'post', 'custom-sidebar' ); // to remove from posts
		remove_post_type_support( 'page', 'custom-sidebar' ); // to remove from pages
		remove_post_type_support( 'custom post type', 'custom-sidebar' ); // to remove from ctp
	}
?>`

**Changing sidebar args**

On your 'functions.php' file just add the following code.

`<?php
	add_filter( 'ds_sidebar_args', 'my_sidebar_args', 1, 3 );

	function my_sidebar_args( $defaults, $sidebar_name, $sidebar_id ) {
		$args = array(
			'description'   => "$sidebar_name widget area",
			'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
			'after_widget'  => '</li>', 'after_widget',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>', 'after_title',
		);

		return $args;
	}
?>`

[Documentation](https://github.com/alyssonweb/wp-dynamics-sidebars/wiki) | [Support Forum](https://github.com/alyssonweb/wp-dynamics-sidebars/issues)

**Don't forget to check the 'Other Notes' tab for a list of all function and hook you can use.**

== Installation ==

**Please Note**

* Requires at least: 3.0
* Tested up to: 3.4.1

**Install**

1. Unzip the dynamics-sidebars.zip file.
1. Upload the the dynamics-sidebars folder (not just the files in it!) to your wp-contents/plugins folder. If you're using FTP, use 'binary' mode.

**Activate**

1. In your WordPress admin area, go to "Plugins" page
1. Activate the "Dynamics Sidebars" plugin.

== Screenshots ==

1. Pages/Posts/Custom Post Types Edit Page
2. Quick Edit
3. Bulk Edit

== Frequently Asked Questions ==

No FAQ yet.

== Changelog ==

= 1.0.4 =

* Fixed issue where sidebar is not registered.

= 1.0.3 =

* Filter 'ds_save_ajax_message' now have a 2 param $error, true if has error false if everything is ok

= 1.0.2 =

* CHANGED Action 'ds_construct' to 'ds_init'

= 1.0.1 =

* Api: has_sidebar()
* Action: ds_plugin_deactivate
* Action: ds_register_column
* Filter: ds_post_types
* Added register_post_type pass to 'supports' => array( 'custom-sidebar' )
* API: has_sidebar( $post_id = 0 )
* Support for add_post_type_support (add post type feature)
* Support for remove_post_type_support (remove post type feature)
* Support for post_type_supports (check if post type supports feature)
* Support for get_all_post_type_supports (get all features for a post type)
* REMOVED Constant: DS_PLUGIN_FOR_PAGES, to render or not "Sidebar" metabox for pages
* REMOVED Constant: DS_PLUGIN_FOR_POSTS, to render or not "Sidebar" metabox for posts

= 1.0.0 =

* Added: bulk edit
* Added: quick edit
* Added: select box with all registered sidebars and a text input for registering a new one
* Added: save via ajax
* Added: save via publich/update/save draft
* Added: .POT file for Internationalization (i18n)
* Added: pt_BR Translation
* Api: the_sidebar( $fallback = '', $echo = false )
* Api: get_the_sidebar( $post_id = 0 )
* Api: get_custom_sidebars()
* Api: get_all_sidebars()
* Filter: the_sidebar
* Filter: ds_save_permissions
* Filter: ds_save_ajax_message
* Filter: ds_save_ajax
* Filter: ds_sidebar_args
* Action: ds_plugin_install
* Action: ds_plugin_uninstall
* Action: ds_add_metabox
* Action: ds_render_metabox
* Action: ds_save
* Constant: DS_PLUGIN_FOR_PAGES, to render or not "Sidebar" metabox for pages
* Constant: DS_PLUGIN_FOR_POSTS, to render or not "Sidebar" metabox for posts
* Constant: DS_PLUGIN_FOR_FRONT_PAGE, to render or not "Sidebar" metabox for front page "page_on_front" (if set)
* Constant: DS_PLUGIN_FOR_POSTS_PAGE, to render or not "Sidebar" metabox for posts page "page_for_posts" (if set)

= 0.1.2 =

* Added install and uninstall functions
* Changes to 'readme.txt'
* Requires at least: 3.0
* Tested up to: 3.4.1

= 0.1 =

* First release.

== Upgrade Notice ==

Fixed issue where sidebar is not registered

== Internationalization (i18n) ==

This plugin has been translated into the languages listed below:

* pt_BR - Portuguese Brazil.

If you're interested in doing a translation into your language, please let me know.

== Api ==

**Functions**

* has_sidebar( $post_id = 0 )
* the_sidebar( $fallback = '', $echo = false )
* get_the_sidebar( $post_id = 0 )
* get_custom_sidebars()
* get_all_sidebars()


**Actions**

* ds_plugin_install
* ds_plugin_uninstall
* ds_plugin_deactivate
* ds_init
* ds_add_metabox
* ds_render_metabox
* ds_save
* ds_register_column


**Filters**

* the_sidebar
* ds_save_permissions
* ds_save_ajax_message
* ds_save_ajax
* ds_sidebar_args
* ds_post_types


**Constant**

* DS_PLUGIN_FOR_FRONT_PAGE
* DS_PLUGIN_FOR_POSTS_PAGE