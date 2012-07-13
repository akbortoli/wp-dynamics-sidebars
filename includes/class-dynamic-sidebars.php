<?php if ( ! defined( 'ABSPATH' ) ) wp_die();

if ( ! class_exists( 'Dynamic_Sidebars' ) ) :

/**
 * Dynamic Sidebars Object
 *
 * @package WordPress
 * @subpackage Dynamic_Sidebars
 */
class Dynamic_Sidebars
{
	/**
	 * Instance
	 * 
	 * @access private
	 * @var object
	 */
	private static $instance;

	// ------------------------------------------------------------
	
	/**
	 * Class Name
	 * 
	 * @access private
	 * @var string
	 */
	private static $class = 'Dynamic_Sidebars';

	// ------------------------------------------------------------
	
	/**
	 * Plugin Path
	 * 
	 * @access private
	 * @return string
	 */
	private static $plugin_path;

	// ------------------------------------------------------------

	/**
	 * Constructor
	 * 
	 * @access public
	 * @return void
	 */
	private function __construct()
	{
		// plugin path
		self::$plugin_path = realpath( dirname( __FILE__ ) . '/../dynamics-sidebars.php' );

		// load text domain
		load_plugin_textdomain( DS_PLUGIN_I18N_DOMAIN, null, str_replace( 'includes', 'languages', dirname( plugin_basename( __FILE__ ) ) ) );

		// install hook
		register_activation_hook( self::$plugin_path, array( &$this, 'plugin_install' ) );

		// deactive hook
		register_deactivation_hook( self::$plugin_path, array( &$this, 'plugin_deactivate' ) );
		
		// Add post feature if not added yet
		if ( post_type_exists( 'post' ) && ! post_type_supports( 'post', 'custom-sidebar' ) )
			add_post_type_support( 'post', 'custom-sidebar' );

		// Add page feature if not added yet
		if ( post_type_exists( 'page' ) && ! post_type_supports( 'page', 'custom-sidebar' ) )
			add_post_type_support( 'page', 'custom-sidebar' );

		// Hook up actions
		add_action( 'init', array( &$this, 'register_sidebars' ), 11 );

		if ( is_admin() ) {
			add_action( 'init', array( &$this, 'register_column' ), 100 );
			add_action( 'add_meta_boxes', array( &$this, 'add_metabox' ), 12 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_script_style' ) );
			add_action( 'bulk_edit_custom_box', array( &$this, 'bulk_edit_custom_box' ), 10, 2 );
			add_action( 'quick_edit_custom_box', array( &$this, 'quick_edit_custom_box' ), 10, 2 );
			add_action( 'wp_ajax_ds_save_post', array( &$this, 'save_ajax' ) );
			add_action( 'wp_ajax_ds_update_select', array( &$this, 'update_select' ) );
		}

		add_action( 'save_post', array( &$this, 'save' ) );

		// hook
		do_action( 'ds_init', &$this );
	}

	// ------------------------------------------------------------

	/**
	 * Get Instance (Singleton)
	 * 
	 * @access public
	 * @return object
	 */
	public function get_instance()
	{
		if ( empty( self::$instance ) ) {
			self::$instance = new self::$class;
		}
		return self::$instance;
	}

	// ------------------------------------------------------------

	/**
	 * Install
	 *
	 * Check if the wordpress version is 3.0.0 > if not deactive the plugin
	 * 
	 * @access public
	 * @return bool
	 */
	public function plugin_install()
	{
		if ( version_compare( get_bloginfo( 'version' ), '3.0', '<' ) ) {
			// Deactivate plugin
			deactivate_plugins( 'dynamics-sidebars/dynamics-sidebars.php', true );
			wp_die( sprintf( __( 'Please update you Wordpress version to at least 3.0 to use this plugin. Your are using wordpress version: %s' ), get_bloginfo( 'version' ) ) );
		}

		// hook
		do_action( 'ds_plugin_install' );
		return true;
	}

	// ------------------------------------------------------------

	/**
	 * Deactivate
	 *
	 * Deactivate the Dynamics Sidebars plugin
	 * Should delete all data?
	 * 
	 * @access public
	 * @return bool
	 */
	public function plugin_deactivate()
	{
		// hook
		do_action( 'ds_plugin_deactivate' );
		return true;
	}

	// ------------------------------------------------------------

	/**
	 * Register Column
	 *
	 * @access public
	 * @return void
	 */
	public function register_column()
	{
		// Check if in the admin area
		if ( is_admin() ) {
			// Get all post types
			$post_types = apply_filters( 'ds_post_types', get_post_types() );

			foreach ( $post_types as $post_type ) {
				if ( post_type_supports( $post_type, 'custom-sidebar' ) ) {
					add_filter( "manage_{$post_type}_posts_columns", array( &$this, 'manage_posts_columns' ) );

					if ( 'page' == $post_type )
						add_action( 'manage_pages_custom_column', array( &$this, 'render_post_columns' ), 10, 2 );
					else
						add_action( 'manage_posts_custom_column', array( &$this, 'render_post_columns' ), 10, 2 );
				}
			}

			do_action( 'ds_register_column' );
		}
	}

	// ------------------------------------------------------------

	/**
	 * Add Metabox
	 * 
	 * Add the dynamic sidebar metabox
	 *
	 * @access public
	 * @return void
	 */
	public function add_metabox()
	{
		global $post_id;

		// Get all post types
		$post_types = apply_filters( 'ds_post_types', get_post_types() );

		$page_on_front  = absint( get_option( 'page_on_front' ) );
		$page_for_posts = absint( get_option( 'page_for_posts' ) );

		foreach ( $post_types as $post_type ) {
			if ( 'page' == $post_type ) {
				if ( ! DS_PLUGIN_FOR_POSTS_PAGE && $page_for_posts == $post_id )
					continue;
				elseif ( ! DS_PLUGIN_FOR_FRONT_PAGE && $page_on_front == $post_id )
					continue;
			}

			if ( post_type_supports( $post_type, 'custom-sidebar' ) ) {
				add_meta_box( 'dynamic-sidebar-metabox', __( 'Sidebar', DS_PLUGIN_I18N_DOMAIN ), array( &$this, 'render_metabox' ), $post_type, 'side', 'default' );
			}
		}

		// hook
		do_action( 'ds_add_metabox', $post_type );
	}

	// ------------------------------------------------------------

	/**
	 * Display Metabox
	 * 
	 * Display the HTML content for the dynamic sidebar metabox
	 *
	 * @access public
	 * @param $post Current post data
	 * @return void
	 */
	public function render_metabox()
	{
		global $post_id, $wp_registered_sidebars;

		if ( ! $post_id )
			return;

		$selected = get_the_sidebar( $post_id );
		$total_sidebars = count( $wp_registered_sidebars );
	?>
		<div id="dynamic-sidebar-box">
			<div id="dynamic-sidebar-message" style="display:none;"></div>
			<div id="dynamic-sidebar-error" style="display:none;"></div>
			<select id="dynamic-sidebar-select" name="<?php echo DS_PLUGIN_CUSTOM_FIELD; ?>_select">
				<option value=""><?php echo esc_html( __( '&mdash; None &mdash;', DS_PLUGIN_I18N_DOMAIN ) ); ?></option>
				<?php foreach ( $wp_registered_sidebars as $s ) : ?>
					<option value="<?php echo esc_attr( $s[ 'name' ] ); ?>" <?php selected( $selected, $s[ 'name' ] ); ?>><?php echo $s[ 'name' ]; ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" id="dynamic-sidebar-text" name="<?php echo DS_PLUGIN_CUSTOM_FIELD; ?>_text" value="" style="display: none;" />
			<a href="#" class="button" id="dynamic-sidebar-add"><?php _e( 'New', DS_PLUGIN_I18N_DOMAIN ); ?></a>
			<a href="#" class="button" id="dynamic-sidebar-cancel" style="display: none;"><?php _e( 'Cancel', DS_PLUGIN_I18N_DOMAIN ); ?></a>
			<br class="clear"/>
			<a href="#" class="button-primary" id="dynamic-sidebar-save"><?php _e( 'Save', DS_PLUGIN_I18N_DOMAIN ); ?></a>
		</div>
	<?php
		// hook
		do_action( 'ds_render_metabox' );
	}

	// ------------------------------------------------------------

	/**
	 * Save
	 * 
	 * Save the dynamic sidebar data to database
	 *
	 * @uses save_sidebar()
	 * @access public
	 * @param $post_id Current POST id
	 * @param $post Current POST object
	 * @return void
	 */
	public function save( $post_id, $post = false )
	{
		$post = get_post( $post_id );

		if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'inline-save' ) {
			$nonce    = 'inlineeditnonce';
			$_wpnonce = isset( $_POST['_inline_edit'] ) ? $_POST['_inline_edit'] : null;
		} elseif ( isset( $_REQUEST[ 'bulk_edit' ] ) ) {
			$nonce    = 'bulk-posts';
			$_wpnonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : null;
		} else {
			$nonce    = 'update-' . $post->post_type . '_' . $post_id;
			$_wpnonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : null;
		}

		// verify if this is an auto save routine. 
		// If our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! wp_verify_nonce( $_wpnonce, $nonce ) )
			return $post_id;

		// Check permissions
		if ( 'page' == $post->post_type ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		} elseif ( 'post' == $post->post_type ) {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		} else {
			// check custom permissions
			$continue = apply_filters( 'ds_save_permissions', true, $post_id, $post );
			if ( ! $continue )
				return $post_id;
		}

		// OK, we're authenticated: we need to find and save the data
		$this->save_sidebar( $post_id );

		// hook
		do_action( 'ds_save', $post_id, $post );

		return $post_id;
	}

	// ------------------------------------------------------------

	/**
	 * Save ajax
	 * 
	 * Save the dynamic sidebar data to database via ajax
	 *
	 * @uses save_sidebar()
	 * @access public
	 * @return void
	 */
	public function save_ajax()
	{
		$_nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		$response = array(
			'error' => false,
			'message' => '',
		);
		
		// verify this came from the our screen and with proper authorization,
		$nonce = 'ds-save-nonce';
		if ( ! wp_verify_nonce( $_nonce, $nonce ) )
			wp_die();

		// get post
		$post = get_post( $post_id );
		if ( ! $post )
			wp_die();

		// Check permissions
		if ( 'page' == $post->post_type ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				$response['error']   = true;
				$response['message'] = apply_filters( 'ds_save_ajax_message', __( 'You do not have permission to edit this page.', DS_PLUGIN_I18N_DOMAIN ), true );
			}
		} elseif ( 'post' == $post->post_type ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				$response['error']   = true;
				$response['message'] = apply_filters( 'ds_save_ajax_message', __( 'You do not have permission to edit this post.', DS_PLUGIN_I18N_DOMAIN ), true );
			}
		} else {
			// check custom permissions
			$continue = apply_filters( 'ds_save_permissions', true, $post_id, $post );
			if ( ! $continue ) {
				$response['error']   = true;
				$response['message'] = apply_filters( 'ds_save_ajax_message', sprintf( __( 'You do not have permission to edit this %s.', DS_PLUGIN_I18N_DOMAIN ), $post->post_type ), true );
			}
		}

		$response = apply_filters( 'ds_save_ajax', $response, $post_id, $post );
		$saved    = false;

		// OK, we're authenticated: we need to find and save the data
		if ( ! $response['error'] )
			$saved = $this->save_sidebar( $post_id );

		// Return success message
		if ( $saved ) {
			$response['error']   = false;
			$response['message'] = apply_filters( 'ds_save_ajax_message', __( 'Sidebar updated.', DS_PLUGIN_I18N_DOMAIN ), false );
		} else {
			$response['error']   = true;
			$response['message'] = apply_filters( 'ds_save_ajax_message', __( 'Sorry an error occurred.', DS_PLUGIN_I18N_DOMAIN ), true );
		}

		echo json_encode( $response, JSON_FORCE_OBJECT );
		wp_die();
	}

	// ------------------------------------------------------------

	/**
	 * Update Select
	 *
	 * Update sidebars select box
	 *
	 * @access public
	 * @return void
	 */
	public function update_select()
	{
		global $wp_registered_sidebars;

		// verify this came from the our screen and with proper authorization,
		$nonce = 'ds-save-nonce';
		$_nonce = isset( $_POST[ 'nonce' ] ) ? $_POST[ 'nonce' ] : null;
		if ( ! wp_verify_nonce( $_nonce, $nonce ) )
			wp_die();

		$post_id = isset( $_POST[ 'post_id' ] ) ? absint( $_POST[ 'post_id' ] ) : 0;

		$selected = get_sidebar( $post_id );
		$total_sidebars = count( $wp_registered_sidebars );
	?>
		<option value=""><?php echo esc_html( __( '&mdash; None &mdash;', DS_PLUGIN_I18N_DOMAIN ) ); ?></option>
		<?php if ( $total_sidebars > 0 ) : ?>
			<?php foreach ( $wp_registered_sidebars as $s ) : ?>
				<option value="<?php echo esc_attr( $s[ 'name' ] ); ?>" <?php selected( $selected, $s[ 'name' ] ); ?>><?php echo $s[ 'name' ]; ?></option>
			<?php endforeach; ?>
		<?php endif; ?>
	<?php
		wp_die();
	}

	// ------------------------------------------------------------

	/**
	 * Save/Update Sidebar
	 * 
	 * Save custom field 'dynamic_sidebar'
	 *
	 * @access public
	 * @return void
	 */
	public function save_sidebar( $post_id )
	{
		if ( ! $post_id )
			return false;
		
		$old = get_the_sidebar( $post_id );

		if ( ! isset( $_REQUEST['bulk_edit'] ) ) {
			$select = isset( $_POST[ DS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ? wp_filter_nohtml_kses( trim( $_POST[ DS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ) : '';
			$text   = isset( $_POST[ DS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ? wp_filter_nohtml_kses( trim( $_POST[ DS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ) : '';
		} else {
			$select = isset( $_REQUEST[ DS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ? wp_filter_nohtml_kses( trim( $_REQUEST[ DS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ) : '';
			$text   = isset( $_REQUEST[ DS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ? wp_filter_nohtml_kses( trim( $_REQUEST[ DS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ) : '';
		}

		$dynamic_sidebar = $select;
		if ( $text != '' )
			$dynamic_sidebar = $text;

		// same value
		if ( $old == $dynamic_sidebar )
			return true;

		// no change
		if ( -1 == $dynamic_sidebar )
			return true;

		// Create new
		if ( '' != $dynamic_sidebar && empty( $old ) ) {
			update_post_meta( $post_id, DS_PLUGIN_CUSTOM_FIELD, $dynamic_sidebar );
		}
		// Update
		else if ( '' != $dynamic_sidebar && $old ) {
			update_post_meta( $post_id, DS_PLUGIN_CUSTOM_FIELD, $dynamic_sidebar, $old );
		}
		// Should we delete the old data?
		else if ( '' == $dynamic_sidebar && $old ) {
			delete_post_meta( $post_id, DS_PLUGIN_CUSTOM_FIELD, $old );
		}

		return true;
	}

	// ------------------------------------------------------------

	/**
	 * Register Sidebars
	 * 
	 * Register all dynamic sidebars
	 *
	 * @access public
	 * @return void
	 */
	public function register_sidebars()
	{
		global $wp_registered_sidebars;

		// Get all registered sidebars
		$registered_sidebars = array( 'names' => array(), 'ids' => array() );
		foreach ( $wp_registered_sidebars as $wp_registered_sidebar ) {
			$registered_sidebars['names'][] = $wp_registered_sidebar['name'];
			$registered_sidebars['ids'][]   = $wp_registered_sidebar['id'];
		}

		// Get all custom sidebars
		$sidebars = get_custom_sidebars();
		foreach ( (array) $sidebars as $sidebar ) {
			$name = esc_html( $sidebar->name );
			$id   = sanitize_title( $sidebar->name );
			
			// Check if sidebar already exists
			if ( in_array( $id, $registered_sidebars['ids'] ) || in_array( $name, $registered_sidebars['names'] ) )
				continue;

			// sidebar args
			$defaults = array(
				'description'   => sprintf( __( '%s widget area', DS_PLUGIN_I18N_DOMAIN ), ucfirst( $sidebar->name ) ),
				'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
				'after_widget'  => '</li>', 'after_widget',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>', 'after_title',
			);

			// user to change
			$args = apply_filters( 'ds_sidebar_args', $defaults, $name, $id );

			// replace
			$sidebar_args = wp_parse_args( $args, $defaults );

			// register sidebars
			register_sidebar(
				array(
					'name'          => $name,
					'id'            => $id,
					'description'   => $sidebar_args[ 'description' ],
					'before_widget' => $sidebar_args[ 'before_widget' ],
					'after_widget'  => $sidebar_args[ 'after_widget' ],
					'before_title'  => $sidebar_args[ 'before_title' ],
					'after_title'   => $sidebar_args[ 'after_title' ],
				)
			);
		}
	}

	// ------------------------------------------------------------

	/**
	 * Enqueue
	 * 
	 * Enqueues Script and Style
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_script_style()
	{
		global $post_id;

		// script
		wp_enqueue_script( 'ds-plugin-script', plugins_url( 'dynamics-sidebars/js/script.min.js', 'dynamics-sidebars' ), array( 'jquery' ), null, true );
		wp_localize_script( 'ds-plugin-script', '_ds', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'post_id' => $post_id,
				'nonce'   => wp_create_nonce( 'ds-save-nonce' ),
			)
		);

		// style
		wp_register_style( 'ds-plugin-styles', plugins_url( 'dynamics-sidebars/css/style.css', 'dynamics-sidebars' ), false, null );
		wp_enqueue_style( 'ds-plugin-styles' );
	}

	// ------------------------------------------------------------
 	
 	/**
	 * Manage posts columns
	 * 
	 * Adds a new column for posts, pages, custom post types (custom)
	 *
	 * @access public
	 * @param array $columns registered columns
	 * @return array
	 */
	public function manage_posts_columns( $columns )
	{
		$columns['dynamic_sidebar'] = __( 'Sidebar', DS_PLUGIN_I18N_DOMAIN );
		return $columns;
	}

	// ------------------------------------------------------------

	/**
	 * Render post columns
	 * 
	 * Renders new column content
	 *
	 * @access public
	 * @param string $column_name column name
	 * @param int $post_id post id
	 * @return void
	 */
	public function render_post_columns( $column_name, $post_id )
	{
		switch ( $column_name ) {
			case 'dynamic_sidebar':
				$sidebar = get_the_sidebar( $post_id, 'dynamic_sidebar', true );
				if ( $sidebar )
					echo '<a href="' . admin_url( '/widgets.php' ) . '">' . esc_html( $sidebar ) . '</a>';
				else
					echo __( 'None', DS_PLUGIN_I18N_DOMAIN );
			break;
		}
	}

	// ------------------------------------------------------------

	/**
	 * Bulk edit custom box
	 *
	 * @access public
	 * @param string $column_name column name
	 * @param string $post_type post type
	 * @return void
	 */
	public function bulk_edit_custom_box( $column_name, $post_type )
	{
		global $wp_registered_sidebars, $post;

		if ( $column_name != 'dynamic_sidebar' )
			return;

		$total_sidebars = count( $wp_registered_sidebars );
	?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Sidebar', DS_PLUGIN_I18N_DOMAIN ); ?></span>
					<select id="dynamic-sidebar-select" name="<?php echo DS_PLUGIN_CUSTOM_FIELD; ?>_select">
						<option value="-1"><?php echo esc_html( __( '&mdash; No Change &mdash;', DS_PLUGIN_I18N_DOMAIN ) ); ?></option>
						<option value=""><?php echo esc_html( __( '&mdash; None &mdash;', DS_PLUGIN_I18N_DOMAIN ) ); ?></option>
						<?php foreach ( $wp_registered_sidebars as $s ) : ?>
							<option value="<?php echo esc_attr( $s[ 'name' ] ); ?>"><?php echo $s[ 'name' ]; ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" id="dynamic-sidebar-text" name="<?php echo DS_PLUGIN_CUSTOM_FIELD; ?>_text" value="" style="display: none;" />
					<a href="#" class="button" id="dynamic-sidebar-add"><?php _e( 'New', DS_PLUGIN_I18N_DOMAIN ); ?></a>
					<a href="#" class="button" id="dynamic-sidebar-cancel" style="display: none;"><?php _e( 'Cancel', DS_PLUGIN_I18N_DOMAIN ); ?></a>
				</label>
			</div>
		</fieldset>
	<?php
	}

	// ------------------------------------------------------------

	/**
	 * Quick edit custom box
	 *
	 * @access public
	 * @param string $column_name column name
	 * @param string $post_type post type
	 * @return void
	 */
	public function quick_edit_custom_box( $column_name, $post_type )
	{
		global $wp_registered_sidebars, $post;

		if ( $column_name != 'dynamic_sidebar' )
			return;

		$selected = get_the_sidebar( $post->ID );
		$total_sidebars = count( $wp_registered_sidebars );
	?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Sidebar', DS_PLUGIN_I18N_DOMAIN ); ?></span>
					<select id="dynamic-sidebar-select" name="<?php echo DS_PLUGIN_CUSTOM_FIELD; ?>_select">
						<option value=""><?php echo esc_html( __( '&mdash; None &mdash;', DS_PLUGIN_I18N_DOMAIN ) ); ?></option>
						<?php foreach ( $wp_registered_sidebars as $s ) : ?>
							<option value="<?php echo esc_attr( $s[ 'name' ] ); ?>" <?php selected( $selected, $s[ 'name' ] ); ?>><?php echo $s[ 'name' ]; ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" id="dynamic-sidebar-text" name="<?php echo DS_PLUGIN_CUSTOM_FIELD; ?>_text" value="" style="display: none;" />
					<a href="#" class="button" id="dynamic-sidebar-add"><?php _e( 'New', DS_PLUGIN_I18N_DOMAIN ); ?></a>
					<a href="#" class="button" id="dynamic-sidebar-cancel" style="display: none;"><?php _e( 'Cancel', DS_PLUGIN_I18N_DOMAIN ); ?></a>
				</label>
			</div>
		</fieldset>
	<?php
	}
}

endif;