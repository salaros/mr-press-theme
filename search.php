<?php
/**
 * Search results page
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

$templates = array( 'search.twig', 'archive.twig', 'index.twig' );
$context = Timber::get_context();

$context['title'] = sprintf(__("Search Results for &#8220;%s&#8221;"), get_search_query());
$context['posts'] = Timber::get_posts();

Timber::render( $templates, $context );
