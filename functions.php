<?php

if ( ! class_exists( 'Timber' ) ) {

    $admin_url = esc_url( admin_url( 'plugins.php' ) ) ;
    $error_message = "
    <div class=\"error alert alert-danger\">
    <p>Timber not activated. Fix it here <a href=\"{$admin_url}\">{$admin_url}#timber</a></p>
    </div>";

    if ( !is_admin() ) {
        die ($error_message);
    }

    add_action( 'admin_notices', function() {
        echo ($error_message);
    });

    return;
}

use Salaros\Wordpress\Template\WordPressSite;

WordPressSite::initialize();
