<?php

namespace Salaros\Wordpress\Template;

use \Timber as Timber;
use \TimberSite as TimberSite;
use \TimberMenu as TimberMenu;
use \Twig_Extension_StringLoader as Twig_Extension_StringLoader;

use Salaros\Wordpress\Template\TwigExtensions;
use Salaros\Wordpress\Template\Bootstrapify;
use Salaros\Wordpress\Template\Asset_Manager;

class WordPressSite extends TimberSite {
	public $user;
	public $commenter;
	public $language_iso;

	private $site_menus;
	private $theme_features;

	private $post_types;
	private $taxonomies;

	private $twig_context;
	private $twig_locations;

	public $date_format;
	public $time_format;

	public $menu_locations;

	public $ui_toolkit;

	public $assets;

	public function __construct() {

		// Call TimberSite constructor
		parent::__construct();

		$this->assets = new Asset_Manager();

		$this->theme_features = [];
		$this->site_menus = [];

		// Set post type and taxonomy-related stuff
		$this->post_types = [];
		$this->taxonomies = [];

		$this->menu_locations = [];

		$this->ui_toolkit = 'semantic-ui';

		// Set Twig-related properties
		$this->twig_context = [];
		$this->twig_locations = [
			'view-blocks',
			'views',
			'templates',
		];

		// Set theme-related properties
		$this->theme->path = get_stylesheet_directory();
		$this->theme->langs = sprintf( '%s/languages', $this->theme->path );

		// Set parent theme-related properties
		if ( ! empty( $this->theme->parent ) ) {
			$this->theme->parent->path = get_template_directory();
			$this->theme->parent->langs = sprintf( '%s/languages', $this->theme->parent->path );
		}

		// Set URLs
		$this->theme->bower_url = sprintf( '%s/bower-asset', WP_CONTENT_URL );
		$this->theme->static_url = sprintf( '%s/static', $this->theme->link );

		$theme_option_key = str_replace( '-', '_', $this->theme->slug );
		$theme_option_key = sprintf( '%s_options', $theme_option_key );
		$this->theme->options = get_option( $theme_option_key );

		// Set user-related stuff
		$this->user = (is_user_logged_in())
			? wp_get_current_user()
			: null;
		$this->commenter = ( empty( $this->user ) )
			? wp_get_current_commenter()
			: null;

		// Set language ISO
		$this->language_iso = substr( $this->language, 0, 2 );

		$this->date_format = get_option( 'date_format' );
		$this->time_format = get_option( 'time_format' );
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

		// Disable Embeds in WordPress
		add_action( 'init', array( $this, 'remove_embeds' ) ); // TODO make it a theme option

		// Remove admin bar for non-admin users
		add_action( 'init', array( $this, 'hide_admin_bar_for_users' ) ); // TODO make it a theme option

		add_action( 'after_setup_theme', array( $this, 'register_menu_locations' ) );

		// Add UI toolkit-related locations
		$this->twig_locations[] = sprintf( '%s/templates', $this->ui_toolkit );
		$this->twig_locations[] = sprintf( '%s/blocks', $this->ui_toolkit );
		$this->twig_locations[] = sprintf( '%s/comments', $this->ui_toolkit );
		$this->twig_locations[] = sprintf( '%s/login', $this->ui_toolkit );

		// Customize Timer/Twig views location
		Timber::$dirname = $this->twig_locations;

		$this->assets->initialize();
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

	public function bootstrapify_init() {
		Bootstrapify::init();
	}

	public function add_theme_support( array $theme_features ) {
		$this->theme_features = array_merge( $this->theme_features, $theme_features );
	}

	public function register_theme_features() {
		foreach ( $this->theme_features as $theme_feature ) {
			add_theme_support( $theme_feature );
		}
	}

	public function add_menu( $menu_name, $menu_items ) {
		$this->site_menus[ $menu_name ] = $menu_items;
	}

	public function register_menus() {
		foreach ( $this->site_menus as $menu_name => $menu_items ) {
			if ( NavMenuTools::menu_exists( $menu_name ) ) {
				continue;
			}
			NavMenuTools::create_menu( $menu_name, $menu_items );
		}
	}

	public function add_to_context( array $context_entry ) {
		$this->twig_context = array_merge( $this->twig_context, $context_entry );
	}

	public function add_menu_location( $slug, $title ) {
		$this->menu_locations[ $slug ] = $title;
	}

	public function register_context( array $context ) {
		// Add site object to the context
		$context['site'] = $this;

		$context = array_merge( $this->twig_context, $context );
		return $context;
	}

			if ( is_int( $menu_name ) && intval( $menu_name ) ) {
				$menu_var_name = get_term( $menu_name )->slug;
				$this->twig_context[ $menu_var_name ] = new \TimberMenu( $menu_name );
				continue;
			}

			$menu_var_name = preg_replace( '/(\s|-)+/', '_', $menu_name );
			$menu_obj = get_term_by( 'slug', $menu_name, 'nav_menu' );
			$this->twig_context[ $menu_var_name ] = new \TimberMenu( $menu_obj->term_id );
		}

		return array_merge( $this->twig_context, $context );
	}

	public function add_to_twig( $twig ) {
		// Add the standard extension +
		$twig->addExtension( new Twig_Extension_StringLoader() );
		$twig->addExtension( new TwigExtensions() );

		return $twig;
	}

	public function add_post_type( $post_type, array $args ) {
		$this->post_types[ $post_type ] = $args;
	}

	public function register_post_types() {
		foreach ( $this->post_types as $post_type => $post_type_args ) {
			if ( ! post_type_exists( $post_type ) ) {
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
		foreach ( $post_types as $post_type ) {
			$this->remove_post_type( $post_type );
		}
	}

	public function add_taxonomy( $taxonomy, array $types, array $args ) {
		$this->taxonomies[ $taxonomy ] = [
			'types' => $types,
			'args' => $args,
		];
	}

	public function register_taxonomies() {
		foreach ( $this->taxonomies as $taxonomy => $taxonomy_data ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, $taxonomy_data['types'], $taxonomy_data['args'] );
			}
		}
	}

	public function remove_taxonomy( $taxonomy ) {
		global $wp_taxonomies;
		if ( taxonomy_exists( $taxonomy ) ) {
			unset( $wp_taxonomies[ $taxonomy ] );
			return true;
		}
		return false;
	}

	public function remove_taxonomies( array $taxonomies ) {
		foreach ( $taxonomies as $taxonomy ) {
			$this->remove_taxonomy( $taxonomy );
		}
	}

	public function get_text_domain() {
		return wp_get_theme()->get( 'TextDomain' ) ?: $this->theme->slug;
	}

	public function load_text_domain() {
		$text_domain = $this->get_text_domain();

		// set default text domain for Twig 'translate' filter
		TwigExtensions::set_default_text_domain( $text_domain );

		if ( ! is_child_theme() ) {
			load_theme_textdomain( $text_domain, $this->theme->langs );
			return;
		}

		if ( isset( $this->theme->parent->langs ) ) {
			load_theme_textdomain( $text_domain, $this->theme->parent->langs );
		}
		load_child_theme_textdomain( $text_domain, $this->theme->langs );
	}

	// Disable emoji functionality introduced in WordPress 4.2
	public function remove_emoji_detection() {
		// all actions related to emojis
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );

		// remove the DNS prefetch
		add_filter( 'emoji_svg_url', '__return_false' );

		// filter to remove TinyMCE emojis
		add_filter( 'tiny_mce_plugins', function ( $plugins ) {
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			}
			return array();
		});
	}

	// Disable so-called enhanced embeds introduced in WordPress 4.4
	public function remove_embeds() {

		// Use the wp_dequeue_script function to dequeue 'wp-embed' script
		add_action( 'wp_footer', function (){
			wp_dequeue_script( 'wp-embed' );
		});

		// Remove the REST API endpoint.
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );

		// Remove oEmbed discovery links.
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

		// Remove oEmbed-specific JavaScript from the front-end
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );

		// Turn off oEmbed auto discovery.
		add_filter( 'embed_oembed_discover', '__return_false' );

		// Remove oEmbed-specific JavaScript from the back-end
		add_filter( 'tiny_mce_plugins', function ( $plugins ) {
			return array_diff( $plugins, array( 'wpembed' ) );
		} );

		add_action('after_switch_theme', function () {
			// Remove all embeds rewrite rules.
			add_filter( 'rewrite_rules_array', function ( $rules ) {
				foreach ( $rules as $rule => $rewrite ) {
					if ( false !== strpos( $rewrite, 'embed=true' ) ) {
						unset( $rules[ $rule ] );
						break;
					}
				}

				return $rules;
			} );
			flush_rewrite_rules();
		});

		// Remove filter of the oEmbed result before any HTTP requests are made.
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );

		// Don't filter oEmbed results.
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
	}
}
