<?php

global $context;

$context['template_messages'] = $template->get_action_template_message( 'login' );
$context['template_the_errors'] = $template->get_errors();
$context['template_id'] = $template->get_option( 'instance' );

$context['log'] = $template->get_posted_value( 'log' );
$context['action_links'] = $template->get_action_links( array( 'login' => false ) );

$context['login_url'] = $template->get_action_url( 'login' );
$context['login_redirect_url'] = $template->get_redirect_url( 'login' );

/*
$redirect_to = get_query_var('redirect_to');
$refer_url = wp_get_referer();
$context['redirect_to'] = ( empty( $redirect_to ) )
    ? $redirect_to
    : ( empty( $refer_url ) && isset( $context['redirect_url'] ) ) ? $context['redirect_url'] : $refer_url;
*/

Timber::render("login.twig", $context);
