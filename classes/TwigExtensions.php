<?php

namespace Salaros\Wordpress\Template;

class TwigExtensions extends \Twig_Extension
{
    private static $default_text_domain;

    public static function set_default_text_domain($domain) {
        self::$default_text_domain = $domain;
    }

    public function getName()
    {
        return 'wordpress';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction( 'slugToPage', array($this, 'get_page_by_slug') ),
            new \Twig_SimpleFunction( 'slugToUrl', array($this, 'get_page_url_by_slug') ),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter( 'translate',  array($this, 'get_translation') ),
        );
    }

    /**
    * Translate function used as Twig filter for gettext-driven translations
    * @param  string $label           The label to translate
    * @param  string [$domain= null]  Domain of the translation, fallbacks on default text domain if null
    * @return string                  Translated label
    */
    public static function get_translation($label, $domain = null) {

        if(empty($domain))
            $domain = self::$default_text_domain;

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
    public static function get_page_by_slug($page_slug, $post_type = 'page', $output = OBJECT ) {
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
    public function get_page_url_by_slug($page_slug, $post_type = 'page', $output = OBJECT ) {
        return get_permalink(get_page_by_slug($page_slug));
    }
}
