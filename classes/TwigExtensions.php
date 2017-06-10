<?php

namespace Salaros\Wordpress\Template;

class TwigExtensions extends \Twig_Extension {
	private static $default_text_domain;

	public static function set_default_text_domain( $domain ) {
		self::$default_text_domain = $domain;
	}

	public function getName() {
		return 'wordpress';
	}

	public function getFunctions() {
		return array(
			new \Twig_SimpleFunction( 'slugToPage', array( $this, 'get_page_by_slug' ) ),
			new \Twig_SimpleFunction( 'slugToUrl', array( $this, 'get_page_url_by_slug' ) ),
			new \Twig_SimpleFunction( 'slugToID', array( $this, 'get_page_id_by_slug' ) ),
			new \Twig_SimpleFunction( 'post', array( $this, 'get_timber_post' ) ),
			new \Twig_SimpleFunction( 'sidebar', array( $this, 'get_sidebar_widgets' ) ),
			new \Twig_SimpleFunction( 'url', array( $this, 'get_url' ) ),
			new \Twig_SimpleFunction( 'thumbnail_url', array( $this, 'get_thumbnail_url' ) ),
			new \Twig_SimpleFunction( 'pdf_to_image', array( $this, 'pdf_to_image_obj' ) ),
		);
	}

	public function getFilters() {
		return array(
			new \Twig_SimpleFilter( 'translate',  array( $this, 'get_translation_with_context' ) ),
			new \Twig_SimpleFilter( 'dump',  array( $this, 'get_dump_info' ) ),
		);
	}

	/**
	* Translate function used as Twig filter for gettext-driven translations
	* @param  string $label           The label to translate
	* @param  string [$domain= null]  Domain of the translation, fallbacks on default text domain if null
	* @return string                  Translated label
	*/
	public static function get_translation( $label, $domain = null ) {

		if ( empty( $domain ) ) {
			$domain = self::$default_text_domain;
		}

		$translation = __( $label, $domain );
		return ($translation !== $label)
			? $translation
			: __( $label );
	}

	public static function get_dump_info( $variable ) {
		ob_start();
		var_dump( $variable );
		$result = ob_get_clean();
		return sprintf( '<pre>%s</pre>', $result );
	}

	/**
	* Translate function used as Twig filter for gettext-driven translations
	* @param  string $label           The label to translate
	* @param  string [$context= null] Context for the translation, fallbacks on regular translation if null
	* @param  string [$domain= null]  Domain of the translation, fallbacks on default text domain if null
	* @return string                  Translated label
	*/
	public static function get_translation_with_context( $label, $context = null, $domain = null ) {

		if ( empty( $context ) ) {
			return self::get_translation( $label, $domain );
		}

		if ( empty( $domain ) ) {
			$domain = self::$default_text_domain;
		}

		$translation = _x( $label, $context, $domain );
		return ($translation !== $label)
			? $translation
			: _x( $label, $context );
	}

	/**
	* Returns WP_Post object (or something else) having the given slug
	* @param  string $page_slug            The slug
	* @param  string [$post_type = 'page'] Post type of the item (post, page etc)
	* @param  string [$output = OBJECT]    Tells the funcion the preferred type for the returned object
	* @return WP_Post The object with the given slug, casted to the type specified by output parameter (default is WP_Post )
	*/
	public static function get_page_by_slug( $page_slug, $post_type = 'page', $output = OBJECT, $post_status = 'publish' ) {
		global $wpdb;
		$sql = "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status = %s" ;
		$page = $wpdb->get_var( $wpdb->prepare( $sql, $page_slug, $post_type, $post_status ) );
		return ( $page )
			? get_post( $page, $output )
			: null;
	}

	/**
	* Returns the URL of the page, post or whatever having the given slug
	* @param  string $page_slug            The slug
	* @param  string [$post_type = 'page'] Post type of the item (post, page etc)
	* @param  string [$output = OBJECT]    Tells the funcion the preferred type for the returned object
	* @return WP_Post The URL of the page
	*/
	public function get_page_url_by_slug( $page_slug, $post_type = 'page', $output = OBJECT ) {
		return get_permalink( $this->get_page_by_slug( $page_slug ) );
	}

	public function get_page_id_by_slug( $page_slug ) {
		$page = get_page_by_path( $page_slug );
		return ($page)
			? $page->ID
			: null;
	}

	public function get_timber_post( $post_id ) {
		return new \TimberPost( $post_id );
	}

	public function get_sidebar_widgets( $sidebar_slug ) {
		return \Timber::get_widgets( $sidebar_slug );
	}

	public function get_url( $url, $protocol = null ) {
		if ( empty( $protocol ) ) {
			$protocol = is_ssl()
				? 'https'
				: 'http';
		}

		return ( preg_match( '/(http|https):\/\//i', $url ) )
			? $url
			: sprintf( '$s://%s', $protocol, $url );
	}

	public function get_thumbnail_url( $post_id ) {
		return wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );
	}

	public function pdf_to_image_obj( $pdf_path ) {
		$image_path = sprintf( '%s.jpg', $pdf_path );
		$image_url = WP_SITEURL . '/' . strstr( $image_path, 'wp-content' );
		// var_dump($image_url);die;
		if ( file_exists( $image_path ) ) {
			return new \TimberImage( $image_url );
		}

		$image = new \Imagick( $pdf_path . '[0]' );
		$image->setImageFormat( 'jpg' );
		$image->writeImage( $image_path );
		return new \TimberImage( $image_url );
	}
}

