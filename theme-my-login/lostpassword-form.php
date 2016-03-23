<?php

global $context;

$context['template_messages'] = $template->get_action_template_message( 'lostpassword' );
$context['template_the_errors'] = $template->get_errors();
$context['template_id'] = $template->get_option( 'instance' );

$context['user_login'] = $template->get_posted_value( 'user_login' );
$context['action_links'] = $template->get_action_links( array( 'lostpassword' => false ) );

$context['lostpassword_url'] = $template->get_action_url( 'lostpassword' );
$context['lostpassword_redirect_url'] = $template->get_redirect_url( 'lostpassword' );

/*
$redirect_to = get_query_var('redirect_to');
$refer_url = wp_get_referer();
$context['redirect_to'] = ( empty( $redirect_to ) )
    ? $redirect_to
    : ( empty( $refer_url ) && isset( $context['redirect_url'] ) ) ? $context['redirect_url'] : $refer_url;
*/

Timber::render("lostpassword.twig", $context);
