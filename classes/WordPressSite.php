<?php

namespace Salaros\Wordpress\Template;

use \Timber as Timber;
use \TimberSite as TimberSite;
use \TimberMenu as TimberMenu;
use \Twig_Extension_StringLoader as Twig_Extension_StringLoader;

use Salaros\Wordpress\Template\TwigExtensions;
use Salaros\Wordpress\Template\AdvancedCustomFields;
use Salaros\Wordpress\Template\Bootstrapify;

class WordPressSite extends TimberSite
{
    public $user;
    public $commenter;
    public $language_ISO;

    private $site_menus;
    private $theme_features;

    private $scripts;
    private $styles;
    private $scripts_admin;
    private $styles_admin;
    private $scripts_remove;
    private $styles_remove;

    private $post_types;
    private $taxonomies;

    private $twig_context;
    private $twig_locations;

    public $date_format;
    public $time_format;

    public $menu_locations;

    public function __construct() {

        // Call TimberSite constructor
        parent::__construct();

        $this->theme_features = [];
        $this->site_menus = [];

        // Set parent theme-related properties
        $this->scripts = [];
        $this->styles = [];
        $this->scripts_admin = [];
        $this->styles_admin = [];
        $this->scripts_remove = [];
        $this->styles_remove = [];

        // Set post type and taxonomy-related stuff
        $this->post_types = [];
        $this->taxonomies = [];

        $this->menu_locations = [];

        // Set Twig-related properties
        $this->twig_context = [];
        $this->twig_locations = [
            'view-blocks',
            'views',
            'templates',
        ];

        // Set theme-related properties
        $this->theme->path = get_stylesheet_directory();
        $this->theme->langs = sprintf("%s/languages", $this->theme->path);

        // Set parent theme-related properties
        if ( !empty($this->theme->parent) ) {
            $this->theme->parent->path = get_template_directory();
            $this->theme->parent->langs = sprintf("%s/languages", $this->theme->parent->path);
        }

        // Set URLs
        $this->theme->bower_url = sprintf("%s/bower-asset", WP_CONTENT_URL);
        $this->theme->static_url = sprintf("%s/static", $this->theme->link);

        $theme_option_key = str_replace('-', '_', $this->theme->slug);
        $theme_option_key = sprintf('%s_options', $theme_option_key);
        $this->theme->options = get_option($theme_option_key);

        // Set user-related stuff
        $this->user = (is_user_logged_in())
            ? wp_get_current_user()
            : null;
        $this->commenter = (empty($this->currentUser))
            ? wp_get_current_commenter()
            : null;

        // Set language ISO
        $this->language_ISO = substr($this->language, 0, 2);

        $this->date_format = get_option('date_format');
        $this->time_format = get_option('time_format');
    }

    /**
     * Processes all added items
     *
     * @return  void
     *
     */
    public function initialize() {
        // Comment form
        add_action( 'init', array( $this, 'bootstrapify_init' ) );

        // Register custom fields in order to manage advanced post/term etc meta from UI
        add_action( 'init', array( $this, 'advanced_custom_fields_init' ) );

        // Register actions for custom scripts, styles, menus etc
        add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
        add_filter( 'script_loader_tag', array( $this, 'process_script_tag' ), 11, 2 );

        // Deregister scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles' ) );

        // Register actions for custom WP-ADMIN scripts, styles, menus etc
        add_action( 'wp_enqueue_scripts', array( $this, 'deregister_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'deregister_styles' ) );

        // Register menus
        add_action( 'init', array( $this, 'register_menus' ) );

        // Comment form
        add_action( 'init', array( $this, 'register_theme_features' ) );

        // Twig stuff
        add_filter( 'timber_context', array( $this, 'register_context' ) );
        add_filter( 'get_twig', array( $this, 'add_to_twig' ) );

        // Register custom post and taxonomies
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );

        // Translations
        add_action( 'after_setup_theme', array( $this, 'load_text_domain' ) );

        // Remove Emoji detection (for it's poor implementation)
        add_action( 'init', array( $this, 'remove_emoji_detection' ) ); // TODO make it a theme option

        // Remove CSS <link id="xxx" attrib for PageSpeed compatibility
        // https://blog.codecentric.de/en/2011/10/wordpress-and-mod_pagespeed-why-combine_css-does-not-work
        add_filter( 'style_loader_tag', array( $this, 'process_style_tags' ) ); // TODO make it a theme option

        // Remove admin bar for non-admin users
        add_action('init', array( $this, 'hide_admin_bar_for_users' ) ); // TODO make it a theme option

        add_action( 'after_setup_theme', array( $this, 'register_menu_locations' ) );

        // Customize Timer/Twig views location
        Timber::$dirname = $this->twig_locations;
    }

    /**
     * Remove admin bar for non-admin users
     *
     * @return  void
     *
     */
    public function hide_admin_bar_for_users() {
        add_filter( 'show_admin_bar', '__return_false' );
    }

    public function register_menu_locations() {
        foreach ( $this->menu_locations as $slug => $title ) {
            register_nav_menu( $slug, $title );
        }
    }

    public function process_script_tag($tag, $handle) {
        $processed_tag = apply_filters( sprintf( '%s_loaded', $handle ), $tag );
        return $processed_tag ?: $tag;
    }

    public function process_style_tags($link) {
        return preg_replace("/id='.*-css'/", "", $link);
    }

    public function bootstrapify_init() {
        Bootstrapify::init();
    }

    public function advanced_custom_fields_init() {
        AdvancedCustomFields::init();
    }

    public function add_script( $handle, $src = '', array $deps = [], $ver = false, $in_footer = false, array $data = []) {
        $this->scripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'in_footer' => $in_footer,
            'data' => $data,
        ];
    }

    public function add_admin_script( $handle, $src = '', array $deps = [], $ver = false, $in_footer = false, array $data = []) {
        $this->scripts_admin[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'in_footer' => $in_footer,
            'data' => $data,
        ];
    }

    /**
     * Iterates throught a list of scripts and equeues them using WordPress functions
     *
     * @param array $scripts List of scripts
     *
     * @return  void
     *
     */
    private function register_scripts_generic(array $scripts) {
        global $wp_scripts;

        foreach($scripts as $handle => $script ) {
            if ( !empty( $script['src'] ) ) {
                wp_register_script( $handle, $script['src'], $script['deps'], $script['ver'], $script['in_footer']);
                if ( is_array( $script['data'] ) ) {
                    foreach ( $script['data'] as $key=>$value ) {
                        $wp_scripts->add_data( $handle, $key, $value );
                    }
                }
            }
            wp_enqueue_script( $handle );
        }
    }

    /**
     * Register and enqueue a custom scripts
     *
     * @param   array $scripts List of scripts
     *
     * @return  void
     *
     */
    public function register_scripts() {
        $this->register_scripts_generic( $this->scripts );
    }

    /**
     * Register and enqueue a custom scripts in the WordPress admin UI, excluding edit.php
     *
     * @return  void
     *
     */
    public function register_admin_scripts() {
        $this->register_scripts_generic( $this->scripts_admin );
    }

    /**
     * Register and enqueue a custom stylesheet
     *
     * @return  void
     *
     */
    public function add_style( $handle, $src = '', array $deps = [], $ver = false, $media = 'all', array $data = []) {
        $this->styles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
            'data' => $data,
        ];
    }

    /**
     * Register and enqueue a custom stylesheet in the WordPress admin UI, excluding edit.php
     *
     * @return  void
     *
     */
    public function add_admin_style( $handle, $src = '', array $deps = [], $ver = false, $media = 'all', array $data = []) {
        $this->styles_admin[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
            'data' => $data,
        ];
    }

    private function register_styles_generic( array $styles ) {
        global $wp_scripts;

        foreach( $styles as $handle => $stylesheet) {
            wp_register_style( $handle, $stylesheet['src'], $stylesheet['deps'], $stylesheet['ver'], $stylesheet['media']);
            if ( is_array( $stylesheet['data'] ) ) {
                foreach ( $stylesheet['data'] as $key=>$value ) {
                    $wp_scripts->add_data( $handle, $key, $value );
                }
            }
            wp_enqueue_style( $handle );
        }
    }

    /**
     * Register and enqueue a custom stylesheets in the WordPress
     *
     * @return  void
     *
     */
    public function register_styles() {
        $this->register_styles_generic( $this->styles );
    }

    /**
     * Register and enqueue a custom stylesheets in the WordPress admin UI, excluding edit.php
     *
     * @return  void
     *
     */
    public function register_admin_styles() {
        $this->register_styles_generic( $this->styles_admin );
    }

    /**
     * Remove a registered scripts
     *
     * @return  void
     *
     */
    public function deregister_styles( ) {
        array_walk( $this->styles_remove, 'wp_dequeue_style' );
        array_walk( $this->styles_remove, 'wp_deregister_style' );
    }

    /**
     * Remove a registered stylesheets
     *
     * @return  void
     *
     */
    public function deregister_scripts( ) {
        array_walk( $this->scripts_remove, 'wp_dequeue_script' );
        array_walk( $this->scripts_remove, 'wp_deregister_script' );
    }

    public function add_theme_support(array $theme_features) {
        $this->theme_features = array_merge($this->theme_features, $theme_features);
    }

    public function register_theme_features() {
        foreach($this->theme_features as $theme_feature) {
            add_theme_support( $theme_feature );
        }
    }

    public function add_menu($menu_name,  $menu_items) {
        $this->site_menus[$menu_name] = $menu_items;
    }

    public function register_menus() {
        foreach($this->site_menus as $menu_name => $menu_items) {
            if ( NavMenuTools::menu_exists( $menu_name ) ) {
                continue;
            }
            NavMenuTools::create_menu( $menu_name, $menu_items );
        }
    }

    public function add_to_context(array $context_entry) {
        $this->twig_context = array_merge( $this->twig_context, $context_entry );
    }

    
    public function add_menu_location( $slug, $title ) {
        $this->menu_locations[$slug] = $title;
    }

    public function register_context(array $context) {
        global $wp;

        $this->twig_context['redirect_url'] = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

        // Add site object to the context
        $context['site'] = $this;

        // Add all the menus to the context
        foreach($this->site_menus as $menu_name => $menu_items) {
            $menu_var_name = (is_int($menu_name) && intval($menu_name) > 0)
                ? (get_term($menu_name)->slug ?: $menu_name)
                : preg_replace('/(\s|-)+/', '_', $menu_name);
            $this->twig_context[$menu_var_name] = new TimberMenu($menu_name);
        }

        return array_merge($this->twig_context, $context);
    }

    public function add_to_twig( $twig ) {
        // Add the standard extension +
        $twig->addExtension( new Twig_Extension_StringLoader() );
        $twig->addExtension( new TwigExtensions() );

        return $twig;
    }

    public function add_post_type($post_type, array $args) {
        $this->post_types[$post_type] = $args;
    }

    public function register_post_types() {
        foreach( $this->post_types as $post_type => $post_type_args ) {
            if ( !post_type_exists( $post_type ) ) {
                register_post_type( $post_type, $post_type_args );
            }
        }
    }

    public function remove_post_type( $post_type ) {
        global $wp_post_types;
        if ( isset( $wp_post_types[ $post_type ] ) ) {
            unset( $wp_post_types[ $post_type ] );
            return true;
        }
        return false;
    }

    public function remove_post_types( $post_types ) {
        foreach( $post_types as $post_type ) {
            $this->remove_post_type( $post_type );
        }
    }

    public function add_taxonomy($taxonomy, array $types, array $args) {
        $this->taxonomies[$taxonomy] = [
            'types' => $types,
            'args' => $args,
        ];
    }

    public function register_taxonomies() {
        foreach( $this->taxonomies as $taxonomy => $taxonomy_data ) {
            if ( !taxonomy_exists( $taxonomy ) ) {
                register_taxonomy( $taxonomy, $taxonomy_data['types'], $taxonomy_data['args'] );
            }
        }
    }

    public function remove_taxonomy($taxonomy) {
        global $wp_taxonomies;
        if ( taxonomy_exists( $taxonomy) ) {
            unset( $wp_taxonomies[$taxonomy]);
            return true;
        }
        return false;
    }

    public function remove_taxonomies(array $taxonomies) {
        foreach( $taxonomies as $taxonomy ) {
            $this->remove_taxonomy( $taxonomy );
        }
    }

    public function get_text_domain() {
        return wp_get_theme()->get( 'TextDomain' ) ?: $this->theme->slug;
    }

    public function load_text_domain() {
        $text_domain = $this->get_text_domain();

        // set default text domain for Twig 'translate' filter
        TwigExtensions::set_default_text_domain($text_domain);

        if ( is_child_theme() ) {
            load_theme_textdomain($text_domain, $this->theme->parent->langs);
            load_child_theme_textdomain($text_domain, $this->theme->langs);
        } else {
            load_theme_textdomain($text_domain, $this->theme->langs);
        }
    }

    public function remove_emoji_detection() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
    }
}
