<?php

$context = Timber::get_context();
$context['post'] = new TimberPost();
$templates = array( 'archive.twig', 'index.twig' );
Timber::render( $templates, $context );
