<?php if ( ! defined( 'ABSPATH' ) ) wp_die();

define('_CS_NONCE', 'cs-save-nonce');

if ( ! class_exists( 'Custom_Sidebar' ) ) :

    /**
     * Custom Sidebar Class
     *
     * @package WordPress
     * @subpackage Custom_Sidebar
     */
    class Custom_Sidebar
    {

        /**
         * Current Plugin Version
         *
         * @access private
         * @var string
         */
        private $version;

        // ------------------------------------------------------------

        /**
         * Class Name
         *
         * @access protected
         * @var string
         */
        protected $class;

        // ------------------------------------------------------------

        /**
         * Plugin Path
         *
         * @access protected
         * @var string
         */
        protected $plugin_path;

        /**
         * Plugin File
         *
         * @access protected
         * @var string
         */
        protected $plugin_file;

        // ------------------------------------------------------------

        /**
         * Constructor
         *
         * @access public
         * @return void
         */
        public function __construct()
        {
            // current plugin version
            $this->version = '1.0.7';

            // plugin class
            $this->class = __CLASS__;

            // plugin path
            $this->plugin_path = realpath( dirname( __FILE__ ) . '/../' );

            // plugin file
            $this->plugin_file = realpath( dirname( __FILE__ ) . '/../dynamics-sidebars.php' );

            // Initialize plugin
            $this->init();
        }

        // ------------------------------------------------------------

        /**
         * Initialization
         *
         * Init this plugin
         *
         * @access public
         * @return object
         */
        protected function init()
        {
            // hook: cs_pre_init
            do_action( 'cs_pre_init', $this );

            // Hook up actions
            add_action( 'init', array( &$this, 'register_sidebars' ), 11 );
            add_action( 'save_post', array( &$this, 'save' ) );

            if ( CS_PLUGIN_COLUMN ) {
                add_action( 'init', array( &$this, 'register_column' ), 100 );
                add_action( 'add_meta_boxes', array( &$this, 'add_metabox' ), 12 );
            }

            // Check if in the admin area
            if ( is_admin() ) {
                add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_script_style' ) );
                add_action( 'bulk_edit_custom_box', array( &$this, 'bulk_edit_custom_box' ), 10, 2 );
                add_action( 'quick_edit_custom_box', array( &$this, 'quick_edit_custom_box' ), 10, 2 );
                add_action( 'wp_ajax_cs_save_post', array( &$this, 'save_ajax' ) );
                add_action( 'wp_ajax_cs_update_select', array( &$this, 'update_select' ) );
            }

            // Add post feature if not added yet
            if ( post_type_exists( 'post' ) && CS_PLUGIN_SUPPORT_FOR_POSTS ) {
                add_post_type_support( 'post', 'custom-sidebar' );
            }

            // Add page feature if not added yet
            if ( post_type_exists( 'page' ) && CS_PLUGIN_SUPPORT_FOR_PAGES ) {
                add_post_type_support( 'page', 'custom-sidebar' );
            }

            // hook: cs_init
            do_action( 'cs_init', $this );

            return $this;
        }

        // ------------------------------------------------------------

        /**
         * Install
         *
         * Check if the wordpress version is 3.0.0 > is installed, if not deactive the plugin
         *
         * @access public
         * @return object
         */
        public function plugin_install()
        {
            if ( version_compare( get_bloginfo( 'version' ), '3.0', '<' ) ) {
                // Deactivate plugin
                deactivate_plugins( 'dynamics-sidebars/dynamics-sidebars.php', true );
                wp_die( sprintf( __( 'Please update your WordPress version to at least 3.0 in order to be able to use this plugin. Your are using WordPress version: %s', _CS_PLUGIN_I18N_DOMAIN ), get_bloginfo( 'version' ) ) );
            }

            // hook: cs_plugin_install
            do_action( 'cs_plugin_install', $this );

            return $this;
        }

        // ------------------------------------------------------------

        /**
         * Deactivate
         *
         * Deactivate the Custom Sidebar plugin
         *
         * @access public
         * @return object
         */
        public function plugin_deactivate()
        {
            // I don't think it's a good idea to delete db data when user deactivate a plugin
            // you should only do that when uninstalling a plugin

            // hook: cs_plugin_deactivate
            do_action( 'cs_plugin_deactivate', $this );

            return $this;
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
                // hook: cs_pre_register_column
                do_action( 'cs_pre_register_column' );

                // so plugins and developers can hook up to it
                $register_column = apply_filters( 'cs_register_column', true );

                // should register new column
                if ( $register_column ) {

                    // Get all post types
                    $post_types = apply_filters( 'cs_post_types', get_post_types() );

                    foreach ( $post_types as $post_type ) {
                        if ( post_type_supports( $post_type, 'custom-sidebar' ) ) {
                            add_filter( "manage_{$post_type}_posts_columns", array( &$this, 'manage_posts_columns' ) );

                            if ( 'page' == $post_type ) {
                                add_action( 'manage_pages_custom_column', array( &$this, 'render_post_columns' ), 10, 2 );
                            }
                            if ( 'post' == $post_type ) {
                                add_action( 'manage_posts_custom_column', array( &$this, 'render_post_columns' ), 10, 2 );
                            }
                        }
                    }

                    // hook: cs_register_column
                    do_action( 'cs_register_column' );
                }
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
            // Check if in the admin area
            if ( is_admin() ) {
                global $post_id;

                // hook: cs_add_metabox
                do_action( 'cs_pre_add_metabox' );

                // Get all post types
                $post_types = apply_filters( 'cs_post_types', get_post_types() );

                $page_on_front  = absint( get_option( 'page_on_front' ) );
                $page_for_posts = absint( get_option( 'page_for_posts' ) );

                foreach ( $post_types as $post_type ) {
                    if ( 'page' == $post_type ) {
                        if ( ! CS_PLUGIN_FOR_POSTS_PAGE && $page_for_posts == $post_id ) {
                            continue;
                        } elseif ( ! CS_PLUGIN_FOR_FRONT_PAGE && $page_on_front == $post_id ) {
                            continue;
                        }
                    }

                    if ( post_type_supports( $post_type, 'custom-sidebar' ) ) {
                        add_meta_box( 'custom-sidebar-metabox', __( 'Sidebar', _CS_PLUGIN_I18N_DOMAIN ), array( &$this, 'render_metabox' ), $post_type, 'side', 'default' );
                    }
                }

                // hook: cs_add_metabox
                do_action( 'cs_add_metabox' );
            }
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

            if ( ! $post_id ) {
                return;
            }

            // hook: cs_pre_render_metabox
            do_action( 'cs_pre_render_metabox' );

            $selected = apply_filters( 'cs_selected_sidebar', get_the_sidebar( $post_id ) );
            $sidebars = apply_filters( 'cs_registered_sidebars', $wp_registered_sidebars );
            ?>
            <div id="custom-sidebar-box">
                <?php
                // hook: cs_pre_html_metabox
                do_action('cs_pre_html_metabox');
                ?>
                <div id="custom-sidebar-message" style="display: none;"></div>
                <div id="custom-sidebar-error" style="display: none;"></div>

                <select id="custom-sidebar-select" name="<?php echo CS_PLUGIN_CUSTOM_FIELD; ?>_select">
                    <option value=""><?php echo esc_html( __( '&mdash; None &mdash;', _CS_PLUGIN_I18N_DOMAIN ) ); ?></option>
                    <?php foreach ( $sidebars as $s ) : ?>
                        <option value="<?php echo esc_attr( $s[ 'name' ] ); ?>" <?php selected( $selected, $s[ 'name' ] ); ?>><?php echo $s[ 'name' ]; ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="custom-sidebar-add-container">
                    <?php
                    // hook: cs_pre_html_metabox
                    do_action('cs_pre_html_metabox_add');
                    ?>
                    <input type="text" id="custom-sidebar-text" name="<?php echo CS_PLUGIN_CUSTOM_FIELD; ?>_text" value="" style="display: none;" />
                    <a href="#" class="button" id="custom-sidebar-add"><?php _e( 'New', _CS_PLUGIN_I18N_DOMAIN ); ?></a>
                    <a href="#" class="button" id="custom-sidebar-cancel" style="display: none;"><?php _e( 'Cancel', _CS_PLUGIN_I18N_DOMAIN ); ?></a>
                    <?php
                    // hook: cs_html_metabox_add
                    do_action('cs_html_metabox_add');
                    ?>
                </div>

                <br class="clear"/>
                <a href="#" class="button-primary" id="custom-sidebar-save"><?php _e( 'Save', _CS_PLUGIN_I18N_DOMAIN ); ?></a>

                <?php
                // hook: cs_html_metabox
                do_action('cs_html_metabox');
                ?>
            </div>
            <?php
            // hook: cs_render_metabox
            do_action( 'cs_render_metabox' );
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
            // hook: cs_pre_save
            do_action( 'cs_pre_save', $post_id, $post, get_current_user_id() );

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
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return $post_id;
            }

            // verify if this came from the our screen and with proper authorization,
            // because save_post can be triggered at other times
            if ( ! wp_verify_nonce( $_wpnonce, $nonce ) ){
                return $post_id;
            }

            // Check permissions
            if ( 'page' == $post->post_type ) {
                if ( ! current_user_can( 'edit_page', $post_id ) ) {
                    return $post_id;
                }
            } elseif ( 'post' == $post->post_type ) {
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return $post_id;
                }
            } else {
                // check custom permissions
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return $post_id;
                }

                $continue = apply_filters( 'cs_save_permissions', true, $post_id, $post, get_current_user_id() );
                if ( ! $continue ) {
                    return $post_id;
                }
            }

            // OK, we're authenticated: we need to find and save the data
            $this->save_sidebar( $post_id );

            // hook: cs_save
            do_action( 'cs_save', $post_id, $post, get_current_user_id() );

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
            // hook: cs_pre_save_ajax
            do_action('cs_pre_save_ajax');

            $_nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;
            $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

            $die_error = array(
                'error' => true
                , 'message' => 'You don\'t have permission to access this page.'
            );

            $response = array(
                'error' => false
                , 'message' => ''
            );
        
        // verify this came from the our screen and with proper authorization,
        if ( ! wp_verify_nonce( $_nonce, _CS_NONCE ) ) {
            wp_die(json_encode( $die_error, JSON_FORCE_OBJECT ));
        }

        // get post
        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_die(json_encode( $die_error, JSON_FORCE_OBJECT ));
        }

        // Check permissions
        if ( 'page' == $post->post_type ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                $response['error'] = true;
                $response['message'] = apply_filters( 'cs_save_ajax_message', __( 'You do not have permission to edit this page.', _CS_PLUGIN_I18N_DOMAIN ), true );
            }
        } elseif ( 'post' == $post->post_type ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                $response['error'] = true;
                $response['message'] = apply_filters( 'cs_save_ajax_message', __( 'You do not have permission to edit this post.', _CS_PLUGIN_I18N_DOMAIN ), true );
            }
        } else {
            // check custom permissions
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                $response['error'] = true;
                $response['message'] = apply_filters( 'cs_save_ajax_message', sprintf( __( 'You do not have permission to edit this %s.', _CS_PLUGIN_I18N_DOMAIN ), $post->post_type ), true );
            }

            $continue = apply_filters( 'cs_save_permissions', true, $post_id, $post, get_current_user_id() );
            if ( ! $continue ) {
                $response['error'] = true;
                $response['message'] = apply_filters( 'cs_save_ajax_message', sprintf( __( 'You do not have permission to edit this %s.', _CS_PLUGIN_I18N_DOMAIN ), $post->post_type ), true );
            }
        }

        $response = apply_filters( 'cs_save_ajax', $response, $post_id );

        // OK, we're authenticated: we need to find and save the data
        if ( ! $response['error'] ) {
            $response = $this->save_sidebar( $post_id );
        }

        // Return success message
        if ( ! $response['error'] ) {
            $response['message'] = apply_filters( 'cs_save_ajax_message', __( 'Sidebar updated.', _CS_PLUGIN_I18N_DOMAIN ), false );
        } elseif ($response['error'] && empty($response['message'])) {
            $response['message'] = apply_filters( 'cs_save_ajax_message', __( 'Sorry an error occurred.', _CS_PLUGIN_I18N_DOMAIN ), true );
        }

        // hook: cs_save_ajax
        do_action( 'cs_save_ajax', $response, $post_id );

        wp_die(json_encode( $response, JSON_FORCE_OBJECT ));
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

            // hook: cs_pre_update_select
            do_action('cs_pre_update_select');

            // verify this came from the our screen and with proper authorization,
            $_nonce = isset( $_POST[ 'nonce' ] ) ? $_POST[ 'nonce' ] : null;
            if ( ! wp_verify_nonce( $_nonce, _CS_NONCE ) ) {
                wp_die();
            }

            $post_id = isset( $_POST[ 'post_id' ] ) ? absint( $_POST[ 'post_id' ] ) : 0;

            $selected = apply_filters( 'cs_selected_sidebar', get_the_sidebar( $post_id ) );
            $sidebars = apply_filters( 'cs_registered_sidebars', $wp_registered_sidebars );
            ?>
            <option value=""><?php echo esc_html( __( '&mdash; None &mdash;', _CS_PLUGIN_I18N_DOMAIN ) ); ?></option>
            <?php foreach ( $sidebars as $s ) : ?>
            <option value="<?php echo esc_attr( $s[ 'name' ] ); ?>" <?php selected( $selected, $s[ 'name' ] ); ?>><?php echo $s[ 'name' ]; ?></option>
        <?php endforeach; ?>
            <?php
            do_action('cs_update_select');
            wp_die();
            ?>
        <?php
        }

        // ------------------------------------------------------------

        /**
         * Save/Update Sidebar
         *
         * Save custom field 'custom_sidebar'
         *
         * @access public
         * @return array
         */
        public function save_sidebar( $post_id )
        {
            $response = array(
                'error' => false
                , 'message' => ''
            );

            if ( ! $post_id ) {
                $response['error'] = true;
                return $response;
            }

            $old = get_the_sidebar( $post_id );

            if ( ! isset( $_REQUEST['bulk_edit'] ) ) {
                $select = isset( $_POST[ CS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ? wp_filter_nohtml_kses( trim( $_POST[ CS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ) : '';
                $text   = isset( $_POST[ CS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ? wp_filter_nohtml_kses( trim( $_POST[ CS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ) : '';
            } else {
                $select = isset( $_REQUEST[ CS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ? wp_filter_nohtml_kses( trim( $_REQUEST[ CS_PLUGIN_CUSTOM_FIELD . '_select' ] ) ) : '';
                $text   = isset( $_REQUEST[ CS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ? wp_filter_nohtml_kses( trim( $_REQUEST[ CS_PLUGIN_CUSTOM_FIELD . '_text' ] ) ) : '';
            }

            $custom_sidebar = $select;
            if ( ! empty($text) ) {
                $custom_sidebar = $text;
            }

            // same value
            if ( $old == $custom_sidebar ) {
                return $response;
            }

            // no change
            if ( -1 == $custom_sidebar ) {
                return $response;
        }

            // Create new
            if ( '' != $custom_sidebar && empty( $old ) ) {
                update_post_meta( $post_id, CS_PLUGIN_CUSTOM_FIELD, $custom_sidebar );
            }
            // Update
            else if ( '' != $custom_sidebar && $old ) {
                update_post_meta( $post_id, CS_PLUGIN_CUSTOM_FIELD, $custom_sidebar, $old );
            }
            // Should we delete the old data?
            else if ( '' == $custom_sidebar && $old ) {
                delete_post_meta( $post_id, CS_PLUGIN_CUSTOM_FIELD, $old );
            }

            return $response;
        }

        // ------------------------------------------------------------

        /**
         * Register Sidebars
         *
         * Register all custom sidebars
         *
         * @access public
         * @return void
         */
        public function register_sidebars()
        {
            global $wp_registered_sidebars;

            $sidebars = apply_filters( 'cs_registered_sidebars', $wp_registered_sidebars );

            // Get all registered sidebars
            $registered_sidebars = array(
                'names' => array()
                , 'ids' => array()
            );
            foreach ( $sidebars as $sidebar ) {
                $registered_sidebars['names'][] = $sidebar['name'];
                $registered_sidebars['ids'][]   = $sidebar['id'];
            }

            // Get all custom sidebars
            $sidebars = get_custom_sidebars();
            foreach ( $sidebars as $sidebar ) {
                $name = esc_html( $sidebar->name );
                $id   = sanitize_title( $sidebar->name );

                // Check if sidebar already exists
                if ( in_array( $id, $registered_sidebars['ids'] ) || in_array( $name, $registered_sidebars['names'] ) ) {
                    continue;
                }

                // sidebar args
                $defaults = array(
                    'description'   => sprintf( __( '%s widget area', _CS_PLUGIN_I18N_DOMAIN ), ucfirst( $sidebar->name ) )
                    , 'before_widget' => '<li id="%1$s" class="widget-container %2$s">'
                    , 'after_widget'  => '</li>'
                    , 'before_title'  => '<h3 class="widget-title">'
                    , 'after_title'   => '</h3>'
                );

                // let plugin and developers change it
                $args = apply_filters( 'cs_sidebar_args', $defaults, $name, $id );

                // replace
                $sidebar_args = wp_parse_args( $args, $defaults );

                // register sidebars
                register_sidebar(
                    array(
                        'name'          => $name
                        , 'id'            => $id
                        , 'description'   => $sidebar_args[ 'description' ]
                        , 'before_widget' => $sidebar_args[ 'before_widget' ]
                        , 'after_widget'  => $sidebar_args[ 'after_widget' ]
                        , 'before_title'  => $sidebar_args[ 'before_title' ]
                        , 'after_title'   => $sidebar_args[ 'after_title' ]
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
            wp_enqueue_script( 'cs-plugin-script', plugins_url( $this->plugin_path . '/js/script.min.js', _CS_PLUGIN_I18N_DOMAIN ), array( 'jquery' ), null, true );
            wp_localize_script( 'cs-plugin-script', '_ds', array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' )
                    , 'post_id' => $post_id
                    , 'nonce'   => wp_create_nonce( _CS_NONCE )
                )
            );

            // style
            wp_register_style( 'cs-plugin-styles', plugins_url( $this->plugin_path . '/css/style.min.css', _CS_PLUGIN_I18N_DOMAIN ), false, null );
            wp_enqueue_style( 'cs-plugin-styles' );
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
            $columns['custom_sidebar'] = __( 'Sidebar', _CS_PLUGIN_I18N_DOMAIN );
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
                case 'custom_sidebar':
                    $sidebar = get_the_sidebar( $post_id, 'custom_sidebar', true );
                    if ( $sidebar ) {
                        echo '<a href="' . admin_url( '/widgets.php' ) . '">' . esc_html( $sidebar ) . '</a>';
                    } else {
                        echo __( 'None', _CS_PLUGIN_I18N_DOMAIN );
                    }
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
            global $wp_registered_sidebars;

            if ( $column_name != 'custom_sidebar' ) {
                return;
            }

            // hook: cs_pre_render_bulk_metabox
            do_action( 'cs_pre_render_bulk_metabox' );

            // $selected = apply_filters( 'cs_selected_sidebar', get_the_sidebar( $post_id ) );
            $sidebars = apply_filters( 'cs_registered_sidebars', $wp_registered_sidebars );
            ?>
            <fieldset class="inline-edit-col-right">
                <div id="custom-sidebar-box-bulk">
                    <div class="inline-edit-col">
                        <label>
                            <?php
                            // hook: cs_pre_html_bulk_metabox
                            do_action('cs_pre_html_bulk_metabox');
                            ?>
                            <span class="title"><?php _e( 'Sidebar', _CS_PLUGIN_I18N_DOMAIN ); ?></span>

                            <select id="custom-sidebar-select" name="<?php echo CS_PLUGIN_CUSTOM_FIELD; ?>_select">
                                <option value="-1"><?php echo esc_html( __( '&mdash; No Change &mdash;', _CS_PLUGIN_I18N_DOMAIN ) ); ?></option>
                                <option value=""><?php echo esc_html( __( '&mdash; None &mdash;', _CS_PLUGIN_I18N_DOMAIN ) ); ?></option>
                                <?php foreach ( $sidebars as $s ) : ?>
                                    <option value="<?php echo esc_attr( $s[ 'name' ] ); ?>"><?php echo $s[ 'name' ]; ?></option>
                                <?php endforeach; ?>
                            </select>

                            <div class="custom-sidebar-add-container">
                                <input type="text" id="custom-sidebar-text" name="<?php echo CS_PLUGIN_CUSTOM_FIELD; ?>_text" value="" style="display: none;" />
                                <a href="#" class="button" id="custom-sidebar-add"><?php _e( 'New', _CS_PLUGIN_I18N_DOMAIN ); ?></a>
                                <a href="#" class="button" id="custom-sidebar-cancel" style="display: none;"><?php _e( 'Cancel', _CS_PLUGIN_I18N_DOMAIN ); ?></a>
                            </div>

                            <?php
                            // hook: cs_html_bulk_metabox
                            do_action('cs_html_bulk_metabox');
                            ?>
                        </label>
                    </div>
                </div>
            </fieldset>
            <?php
            // hook: cs_render_bulk_metabox
            do_action( 'cs_render_bulk_metabox' );
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

            if ( $column_name != 'custom_sidebar' ) {
                return;
            }

            // hook: cs_pre_render_quickedit_metabox
            do_action( 'cs_pre_render_quickedit_metabox' );

            // $selected = get_the_sidebar( $post->ID );
            $sidebars = apply_filters( 'cs_registered_sidebars', $wp_registered_sidebars );
            ?>
            <fieldset class="inline-edit-col-right">
                <div id="custom-sidebar-box-quickedit">
                    <div class="inline-edit-col">
                        <label>
                            <?php
                            // hook: cs_pre_html_quickedit_metabox
                            do_action('cs_pre_html_quickedit_metabox');
                            ?>
                            <span class="title"><?php _e( 'Sidebar', _CS_PLUGIN_I18N_DOMAIN ); ?></span>

                            <select id="custom-sidebar-select" name="<?php echo CS_PLUGIN_CUSTOM_FIELD; ?>_select">
                                <option value="-1"><?php echo esc_html( __( '&mdash; No Change &mdash;', _CS_PLUGIN_I18N_DOMAIN ) ); ?></option>
                                <option value=""><?php echo esc_html( __( '&mdash; None &mdash;', _CS_PLUGIN_I18N_DOMAIN ) ); ?></option>
                                <?php foreach ( $sidebars as $s ) : ?>
                                    <option value="<?php echo esc_attr( $s[ 'name' ] ); ?>"><?php echo $s[ 'name' ]; ?></option>
                                <?php endforeach; ?>
                            </select>

                            <div class="custom-sidebar-add-container">
                                <input type="text" id="custom-sidebar-text" name="<?php echo CS_PLUGIN_CUSTOM_FIELD; ?>_text" value="" style="display: none;" />
                                <a href="#" class="button" id="custom-sidebar-add"><?php _e( 'New', _CS_PLUGIN_I18N_DOMAIN ); ?></a>
                                <a href="#" class="button" id="custom-sidebar-cancel" style="display: none;"><?php _e( 'Cancel', _CS_PLUGIN_I18N_DOMAIN ); ?></a>
                            </div>

                            <?php
                            // hook: cs_html_quickedit_metabox
                            do_action('cs_html_quickedit_metabox');
                            ?>
                        </label>
                    </div>
                </div>
            </fieldset>
            <?php
            // hook: cs_render_quickedit_metabox
            do_action( 'cs_render_quickedit_metabox' );
        }
    }

endif;