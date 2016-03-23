<?php
/*
If you would like to edit this file, copy it to your current theme's directory and edit it there.
Theme My Login will always look in your theme's directory first, before using this default template.
*/
?>
<div class="login" id="theme-my-login<?php $template->the_instance(); ?>">

    <?php
    $template_message = $template->get_action_template_message( 'resetpass' );
    if ( !empty($template_message) ) : ?>
    <div class="alert alert-info">
        <?php echo $template_message; ?>
    </div>
    <?php endif; ?>

    <?php
    $template_errors = $template->the_errors();
    if ( !empty($template_errors) ) : ?>
    <div class="alert alert-danger">
        <?php echo $template_errors; ?>
    </div>
    <?php endif; ?>

    <form name="resetpasswordform" id="resetpasswordform<?php $template->the_instance(); ?>" action="<?php $template->the_action_url( 'resetpass' ); ?>" method="post">
        <p>
            <label for="pass1<?php $template->the_instance(); ?>"><?php _e( 'New password', 'theme-my-login' ); ?></label>
            <input autocomplete="off" name="pass1" id="pass1<?php $template->the_instance(); ?>" class="input" size="20" value="" type="password" autocomplete="off" />
        </p>

        <p>
            <label for="pass2<?php $template->the_instance(); ?>"><?php _e( 'Confirm new password', 'theme-my-login' ); ?></label>
            <input autocomplete="off" name="pass2" id="pass2<?php $template->the_instance(); ?>" class="input" size="20" value="" type="password" autocomplete="off" />
        </p>

        <div id="pass-strength-result" class="hide-if-no-js"><?php _e( 'Strength indicator', 'theme-my-login' ); ?></div>

        <?php do_action( 'resetpassword_form' ); ?>

        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit<?php $template->the_instance(); ?>" value="<?php esc_attr_e( 'Reimposta la password', 'theme-my-login' ); ?>" />
            <input type="hidden" id="user_login" value="<?php echo esc_attr( $GLOBALS['rp_login'] ); ?>" autocomplete="off" />
            <input type="hidden" name="rp_key" value="<?php echo esc_attr( $GLOBALS['rp_key'] ); ?>" />
            <input type="hidden" name="instance" value="<?php $template->the_instance(); ?>" />
            <input type="hidden" name="action" value="resetpass" />
        </p>
    </form>
</div>
