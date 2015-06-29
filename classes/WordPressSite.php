<?php

namespace Salaros\Wordpress\Template;

use Salaros\Wordpress\Template\NavMenuTools;

class WordPressSite extends \TimberSite {

    public static $THEME_NAME;
    public static $THEME_PATH;
    public static $THEME_URL;
    public static $BOWER_URL;
    public static $LANGS_PATH;

    public static function initialize() {
        self::$THEME_NAME = wp_get_theme();
        self::$THEME_PATH = get_stylesheet_directory();
        self::$THEME_URL = get_stylesheet_directory_uri();
        self::$BOWER_URL = self::$THEME_URL.'/vendor/bower-asset';
        self::$LANGS_PATH = self::$THEME_PATH.'/languages';

        // Declare theme features
        add_theme_support( 'post-formats' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'menus' );

        new WordPressSite();
    }

    public function __construct() {
        // Register actions for custom scripts, styles, menus etc
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'init', array( $this, 'register_menu' ) );
        // Twig stuff
        add_filter( 'timber_context', array( $this, 'add_to_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );
        // Register custom post and taxonomies
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        parent::__construct();
    }

    function register_menu() {
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

    public function register_post_types() {
        //this is where you can register custom post types
    }

    public function register_taxonomies() {
        //this is where you can register custom taxonomies
    }

    public function add_to_context( $context ) {
        $context['menu'] = new \TimberMenu('top-nav-menu');
        $context['site'] = $this;
        return $context;
    }

    public function add_to_twig( $twig ) {
        /* this is where you can add your own fuctions to twig */
        $twig->addExtension( new \Twig_Extension_StringLoader() );
        $twig->addFilter( 'translate', new \Twig_Filter_Function( 'get_translation' ) );
        return $twig;
    }

    public function enqueue_scripts() {
        //this is where you can add your javascript entries
        self::enqueue_script( 'respond', self::$BOWER_URL.'/respond/dest/respond.min.js', [], '1.4.2', false, ['conditional' => 'lt IE 9'] );
        self::enqueue_script( 'html5shiv', self::$BOWER_URL.'/html5shiv/dist/html5shiv.min.js', [], '3.7.2', false, ['conditional' => 'lt IE 9'] );
        self::enqueue_script( 'bootstrap', self::$BOWER_URL.'/bootstrap/dist/js/bootstrap.min.js', ['jquery'], '3.3.5', true );
    }

    public function enqueue_styles() {
        //this is where you can add your CSS entries
        self::enqueue_style( 'bootstrap', self::$BOWER_URL.'/bootstrap/dist/css/bootstrap.min.css' );
        self::enqueue_style( 'normalize', self::$BOWER_URL.'/normalize.css/normalize.css' );
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

/**
 * Translate function used as Twig filter for gettext-driven translations
 * @param  string $label           The label to translate
 * @param  string [$domain = null] Domain of the translation, fallbacks on theme name if null
 * @return string Translated label
 */
function get_translation($label, $domain = null) {

    if(empty($domain))
        $domain = WordPressSite::THEME_NAME;

    $translation = __($label, $domain);
    return ($translation !== $label)
        ? $translation
        : __($label);
}

/**
 * Returns WP_Post object (or something else) having the given slug
 * @param  string $page_slug            The slug
 * @param  string [$post_type = 'page'] Post type of the item (post, page etc)
 * @param  string [$output = OBJECT]    Tells the funcion the preferred type for the returned object
 * @return WP_Post The object with the given slug, casted to the type specified by output parameter (default is WP_Post )
 */
function get_page_by_slug($page_slug, $post_type = 'page', $output = OBJECT ) {
    global $wpdb;
    $sql = "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type= %s AND post_status = 'publish'" ;
    $page = $wpdb->get_var( $wpdb->prepare($sql, $page_slug, $post_type) );
    return ( $page )
        ? get_post($page, $output)
        : null;
}

/**
 * Returns the URL of the page, post or whatever having the given slug
 * @param  string $page_slug            The slug
 * @param  string [$post_type = 'page'] Post type of the item (post, page etc)
 * @param  string [$output = OBJECT]    Tells the funcion the preferred type for the returned object
 * @return WP_Post The URL of the page
 */
function get_page_url_by_slug($page_slug, $post_type = 'page', $output = OBJECT ) {
    return get_permalink(get_page_by_slug($page_slug));
}
