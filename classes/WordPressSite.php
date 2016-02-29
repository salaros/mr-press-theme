<?php

namespace Salaros\Wordpress\Template;

use Salaros\Wordpress\Template\NavMenuTools;
use Salaros\Wordpress\Template\TwigExtensions;
use Salaros\Wordpress\Template\AdvancedCustomFields;

class WordPressSite extends \TimberSite {

    public static $THEME_NAME;
    public static $THEME_PATH;
    public static $THEME_URL;
    public static $BOWER_URL;
    public static $LANGS_PATH;
    public static $STATIC_URL;
    public static $SITE_TITLE;
    public static $SITE_SUBTITLE;
    public static $USER_CURRENT;
    public static $COMMENTER_CURRENT;
    public static $SITE_LOCALE;
    public static $SITE_LOCALE_ISO2;

    public static function initialize() {
        self::$THEME_NAME = wp_get_theme()->template;
        self::$THEME_PATH = get_stylesheet_directory();
        self::$THEME_URL = get_stylesheet_directory_uri();
        self::$BOWER_URL = self::$THEME_URL.'/vendor/bower-asset';
        self::$LANGS_PATH = self::$THEME_PATH.'/languages';
        self::$STATIC_URL = self::$THEME_URL.'/static';
        self::$USER_CURRENT = (is_user_logged_in())
            ? wp_get_current_user()
            : NULL;
        self::$COMMENTER_CURRENT = (empty(self::$USER_CURRENT))
            ? wp_get_current_commenter()
            : NULL;

        // Customize Timer/Twig views location
        \Timber::$locations = [
            self::$THEME_PATH."/views",
            self::$THEME_PATH."/page-views",
        ];

        self::$SITE_LOCALE = get_locale();
        self::$SITE_LOCALE_ISO2 = substr(self::$SITE_LOCALE, 0, 2);

        self::$SITE_TITLE = get_bloginfo('name', 'display');
        self::$SITE_SUBTITLE = get_bloginfo('description', 'display');

        // Declare theme features
        add_theme_support( 'post-formats' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );

        new WordPressSite();
    }

    public function __construct() {
        // Register custom fields in order to manage advanced post/term etc meta from UI
        add_action( 'init', array( $this, 'init_custom_fields' ) );
        // Register actions for custom scripts, styles, menus etc
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        // Register actions for custom WP-ADMIN scripts, styles, menus etc
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
        // Register menus
        add_action( 'init', array( $this, 'register_menu' ) );
        // Twig stuff
        add_filter( 'timber_context', array( $this, 'add_to_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
        // Register custom post and taxonomies
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        // Translations
        add_action( 'after_setup_theme', array( $this, 'load_text_domain' ) );
        // Comment form
        add_action( 'init', array( $this, 'tweak_comments' ) );
        //Call TimberSite constructor
        parent::__construct();
    }

    public function register_menu() {
        /* this is where you can add your menus
         * to re-create menu and its items just remove it
         */
        if ( ! NavMenuTools::menu_exists( 'top-nav-menu' ) ) {
            $menu_items[] = [ 'title' => translate( 'Home' ), 'url' => '/' ];
            $menu_items[] = [ 'slug' => 'sample-page' ];
            $menu_items[] = [
                'title' => translate( 'Search engines' ),
                'children' => [
                    [ 'title' => translate( 'Google' ), 'url' => 'http://google.com' ],
                    [ 'title' => translate( 'Bing' ), 'url' => 'http://bing.com' ]
                ]
            ];
            NavMenuTools::create_menu( 'top-nav-menu', $menu_items );
        }
        /**/
    }

    public function init_custom_fields() {
        AdvancedCustomFields::init();
    }

    public function register_post_types() {
        //this is where you can register custom post types

		/**
		 * SAMPLES OF CUCSTOM POST TYPE REGISTRATION / DE-REGISTRATION

        if ( post_type_exists( 'test_type' ) ) {
			self::unregister_post_type('test_type');
        }

        if ( ! post_type_exists( 'events' ) ) {
            $labels = array(
                'name'                => _x( 'Events', 'List of Events', self::$THEME_NAME ),
                'singular_name'       => _x( 'Event', 'DList of Events', self::$THEME_NAME ),
                'menu_name'           => __( 'Events', self::$THEME_NAME ),
                'parent_item_colon'   => __( 'Parent Event:', self::$THEME_NAME ),
                'all_items'           => __( 'All Events', self::$THEME_NAME ),
                'view_item'           => __( 'View Event Info', self::$THEME_NAME ),
                'add_new_item'        => __( 'Add New Event', self::$THEME_NAME ),
                'add_new'             => __( 'Add New Event', self::$THEME_NAME ),
                'edit_item'           => __( 'Edit Event', self::$THEME_NAME ),
                'update_item'         => __( 'Update Event', self::$THEME_NAME ),
                'search_items'        => __( 'Search for a Event', self::$THEME_NAME ),
                'not_found'           => __( 'Not found', self::$THEME_NAME ),
                'not_found_in_trash'  => __( 'Not found in Trash', self::$THEME_NAME ),
            );
            $args = array(
                'label'               => __( 'Events', self::$THEME_NAME ),
                'description'         => __( 'List of Events', self::$THEME_NAME ),
                'labels'              => $labels,
                'supports'            => [ 'title', 'editor', 'thumbnail', 'comments', 'revisions' ],
                'taxonomies'          => [ 'post_tag' ],
                'hierarchical'        => false,
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_nav_menus'   => true,
                'show_in_admin_bar'   => true,
                'menu_position'       => 5,
                'menu_icon'           => 'dashicons-calendar-alt',
                'can_export'          => true,
                'has_archive'         => true,
                'exclude_from_search' => false,
                'publicly_queryable'  => true,
            );
            register_post_type( 'Events', $args );
        }
		*/
    }

    public function unregister_post_type( $post_type ) {
        global $wp_post_types;
        if ( isset( $wp_post_types[ $post_type ] ) ) {
            unset( $wp_post_types[ $post_type ] );
            return true;
        }
        return false;
    }

    public function register_taxonomies() {
        //this is where you can register custom taxonomies

		/**
		 * SAMPLES OF CUCSTOM TAXONOMY REGISTRATION / DE-REGISTRATION
		 *
        if ( ! taxonomy_exists('event_types') ) {

            $labels = array(
                'name'                => _x( 'Event Types', 'Event Types', self::$THEME_NAME ),
                'singular_name'       => _x( 'Event Type', 'Type of Events', self::$THEME_NAME ),
                'menu_name'           => __( 'Event Types', self::$THEME_NAME ),
                'parent_item_colon'   => __( 'Parent Event Types:', self::$THEME_NAME ),
                'all_items'           => __( 'All Event Type', self::$THEME_NAME ),
                'view_item'           => __( "View Event Type Info", self::$THEME_NAME ),
                'add_new_item'        => __( 'Add New Event Type', self::$THEME_NAME ),
                'add_new'             => __( 'Add New Event Type', self::$THEME_NAME ),
                'edit_item'           => __( 'Edit Event Type', self::$THEME_NAME ),
                'update_item'         => __( 'Update Event Type', self::$THEME_NAME ),
                'search_items'        => __( 'Search for a event type', self::$THEME_NAME ),
                'not_found'           => __( 'Not found', self::$THEME_NAME ),
                'not_found_in_trash'  => __( 'Not found in Trash', self::$THEME_NAME ),
            );

            register_taxonomy(
                'event_types',
                ['events'],
                [
                    'labels' => $labels,
                    'rewrite' => [ 'slug' => 'event_type' ],
                    'capabilities' => [],
                    'hierarchical' => true,
                ]
            );
        }
		*/
    }

    public function add_to_context( $context ) {
        $context['menu'] = new \TimberMenu('top-nav-menu');
        $context['site'] = $this;
        $context['page_url'] = 'http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $context['page_title'] = trim(wp_title('', false));

        $context['THEME_NAME'] = self::$THEME_NAME;
        $context['THEME_PATH'] = self::$THEME_PATH;
        $context['THEME_URL'] = self::$THEME_URL;
        $context['BOWER_URL'] = self::$BOWER_URL;
        $context['LANGS_PATH'] = self::$LANGS_PATH;
        $context['STATIC_URL'] = self::$STATIC_URL;
        $context['SITE_TITLE'] = self::$SITE_TITLE;
        $context['SITE_SUBTITLE'] = self::$SITE_SUBTITLE;
        $context['USER_CURRENT'] = self::$USER_CURRENT;
        $context['COMMENTER_CURRENT'] = self::$COMMENTER_CURRENT;

        return $context;
    }

    public function add_to_twig( $twig ) {
        /* this is where you can add your own fuctions to twig */
        $twig->addExtension( new \Twig_Extension_StringLoader() );
        $twig->addExtension( new TwigExtensions() );
        return $twig;
    }

    public function enqueue_scripts() {
        //this is where you can add your javascript entries
        self::enqueue_script( 'respond', self::$BOWER_URL.'/respond/dest/respond.min.js', [], '1.4.2', false, ['conditional' => 'lt IE 9'] );
        self::enqueue_script( 'html5shiv', self::$BOWER_URL.'/html5shiv/dist/html5shiv.min.js', [], '3.7.2', false, ['conditional' => 'lt IE 9'] );
        self::enqueue_script( 'bootstrap', self::$BOWER_URL.'/bootstrap/dist/js/bootstrap.min.js', ['jquery'], '3.3.5', true );
		self::enqueue_script( 'site', self::$THEME_URL.'/static/js/site.js', ['bootstrap'], '0.1.0', true );
    }

    public function enqueue_styles() {
        //this is where you can add your CSS entries
        self::enqueue_style( 'normalize', self::$BOWER_URL.'/normalize.css/normalize.css' );
        self::enqueue_style( 'bootstrap', self::$BOWER_URL.'/bootstrap/dist/css/bootstrap.min.css' );
    }

    public function admin_enqueue_scripts() {
        //this is where you can add your javascript entries for wp-admin UI
    }

    public function admin_enqueue_styles() {
        //this is where you can add your CSS entries for wp-admin UI
        self::enqueue_style( 'custom-admin-css', self::$THEME_URL.'/static/css/admin.css' );
    }

    public function load_text_domain() {
        load_theme_textdomain(self::$THEME_NAME, self::$LANGS_PATH);
    }

    public function tweak_comments() {
        add_filter( 'comment_form_default_fields', function ( $fields ) {

            $req      = get_option( 'require_name_email' );
            $aria_req = ( $req ? " aria-required='true'" : '' );

            $author_name = esc_attr( self::$COMMENTER_CURRENT['comment_author'] );
            $author_email = esc_attr( self::$COMMENTER_CURRENT['comment_author_email'] );

            $fields   =  array(
                'author' =>
                    '<div class="form-group comment-form-author form-control-wrapper">' .
				'<label for="usr">' . __('Name') . ' *</label>' .
                '<input class="form-control" id="author" name="author" type="text" autocomplete="off" value="' . $author_name . '" size="30"' . $aria_req . ' />' .
                    '</div>',

                'email'  =>

                    '<div class="form-group comment-form-email form-control-wrapper">' .
				'<label for="usr">' . __('Email') . ' *</label>' .
                '<input class="form-control" id="email" name="email" type="email" autocomplete="off" value="' . $author_email . '" size="30"' . $aria_req . ' />' .
                    '</div>',
            );

            unset($fields['url']);

            return $fields;
        });

        add_filter( 'comment_form_defaults', function ( $args ) {
            $args['comment_field'] =
                '<div class="form-group comment-form-comment form-control-wrapper">
                <textarea class="form-control empty" id="comment" name="comment" cols="45" autocomplete="off" rows="5" aria-required="true"></textarea>
                </div>';
            $args['class_submit'] = 'btn btn-success'; // since WP 4.1

            return $args;
        });

    }

    public static function dequeue_styles( $style_handles ) {
        if(!is_array($style_handles))
            return; // TODO log the error

        array_walk($style_handles, 'wp_dequeue_style');
        array_walk($style_handles, 'wp_deregister_style');
    }

    public static function dequeue_scripts( $script_handles ) {
        if(!is_array($script_handles))
            return; // TODO log the error

        array_walk($script_handles, 'wp_dequeue_script');
        array_walk($script_handles, 'wp_deregister_script');
    }

    public static function enqueue_style( $handle, $src, $deps = [], $ver = false, $media = 'all', $data = []) {
        global $wp_scripts;
        wp_register_style( $handle, $src, $deps, $ver, $media);
        if (is_array($data)) {
            foreach ($data as $key=>$value) {
                $wp_scripts->add_data( $handle, $key, $value );
            }
        }
        wp_enqueue_style( $handle );
    }

    public static function enqueue_script( $handle, $src, $deps = [], $ver = false, $in_footer = false, $data = []) {
        global $wp_scripts;
        wp_register_script( $handle, $src, $deps, $ver, $in_footer);
        if (is_array($data)) {
            foreach ($data as $key=>$value) {
                $wp_scripts->add_data( $handle, $key, $value );
            }
        }
        wp_enqueue_script( $handle );
    }
}
