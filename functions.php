<?php

if ( ! class_exists( 'Timber' ) ) {
    add_action( 'admin_notices', function() {
        $admin_url = esc_url( admin_url( 'plugins.php' ) ) ;
        $error_message = "<div class=\"error alert-danger\"><p>Timber not activated. Fix it here <a href=\"{$admin_url}\">{$admin_url}#timber</a></p></div>";
        echo $error_message;
    });
    return;
}

require_once("vendor/autoload.php");

use Salaros\Wordpress\Template\WordPressSite;

WordPressSite::initialize();
