<?php

if ( !class_exists( 'Timber' ) && php_sapi_name() !== 'cli') {

    $admin_url = esc_url( admin_url( 'plugins.php' ) ) ;
    $error_message = "
    <div class=\"error notice notice-error alert alert-danger\">
    <p>Timber not activated. Fix it here <a href=\"{$admin_url}\">{$admin_url}#timber</a></p>
    </div>";

    if ( is_admin() ) {
        add_action( 'admin_notices', function() use($error_message) {
            echo ($error_message);
        });
    } else {
        echo ($error_message);
    }

    return;
}
