<?php

if ( 'posts' === get_option( 'show_on_front' ) ) {
    require_once( 'home.php' );
} else {
    $context = Timber::get_context();
    $context['post'] = new TimberPost();

    $templates = array( 'front-page.twig' );
    Timber::render( $templates, $context );
}
