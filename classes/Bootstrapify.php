<?php

namespace Salaros\Wordpress\Template;

use \Timber as Timber;

class Bootstrapify {

    public static function init() {
        self::tweak_comment_form();
    }

    private static function tweak_comment_form() {
        add_filter( 'comment_form_default_fields', function ( $fields ) {

            $commenter = wp_get_current_commenter();

            $require_name_email = get_option( 'require_name_email' );
            $aria_require_name_email = ( $require_name_email ) ? ' aria-required="true"' : '';
            $require_name_email_attr = ( $require_name_email ) ? ' required' : '';

            $author_name = esc_attr( @$commenter['comment_author'] );
            $author_email = esc_attr( @$commenter['comment_author_email'] );
            $author_url = esc_attr( @$commenter['comment_author_url'] );

            $fields['author'] = sprintf(
                '<div class="form-group comment-form-author form-control-wrapper label-floating empty">
                    <label for="author" class="control-label">%s</label>
                    <input class="form-control" id="author" name="author" type="text" autocomplete="off" value="%s" size="30" %s %s />
                </div>', __('Name'), $author_url, $require_name_email_attr, $aria_require_name_email);

            $fields['email'] = sprintf(
                '<div class="form-group comment-form-email form-control-wrapper label-floating empty">
                    <label for="email" class="control-label">%s</label>
                    <input class="form-control" id="email" name="email" type="email" autocomplete="off" value="%s" size="30" %s %s />
                </div>', __('Email'), $author_url, $require_name_email_attr, $aria_require_name_email);

            $fields['url'] = sprintf(
                '<div class="form-group comment-form-email form-control-wrapper label-floating empty">
                    <label for="url" class="control-label">%s</label>
                    <input class="form-control" id="url" name="url" type="url" autocomplete="off" value="%s" size="30" />
                </div>', __('Website'), $author_url);

            // unset($fields['url']);

            return $fields;
        });

        add_filter( 'comment_form_defaults', function ( $args ) {
            $args['class_submit'] = 'btn btn-primary btn-raised'; // since WP 4.1
            $args['class_form'] = "comment-form";

            $args['comment_notes_before'] = '';
            $args['comment_notes_after'] = '';

            $args['comment_field'] = sprintf(
                '<div class="form-group comment-form-comment form-control-wrapper label-floating empty">
                    <label for="comment" class="control-label">%s</label>
                    <textarea class="form-control" id="comment" name="comment" cols="45" autocomplete="off" rows="5" required aria-required="true"></textarea>
                </div>', __('Your Comment'));

            return $args;
        });
    }
}
