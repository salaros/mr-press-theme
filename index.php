<?php

$context = Timber::get_context();
$context['post'] = new TimberPost();

$templates = array( 'index.twig' );
Timber::render( $templates, $context );
