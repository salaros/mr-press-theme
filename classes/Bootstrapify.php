<?php

namespace Salaros\Wordpress\Template;

use \Timber as Timber;

class Bootstrapify {

	public static function init() {
		self::tweak_comment_form();
	}

	private static function tweak_comment_form() {

		// Override the appearance of author, email and URL fields
		add_filter( 'comment_form_default_fields', function ( $fields ) {

			$commenter = wp_get_current_commenter();
			$context = [
				'require_name_email' => get_option( 'require_name_email' ),
				'author_name' => esc_attr( @$commenter['comment_author'] ),
				'author_email' => esc_attr( @$commenter['comment_author_email'] ),
				'author_url' => esc_attr( @$commenter['comment_author_url'] ),
			];

			$fields['author'] = Timber::compile( 'comment-form-author.twig', $context );
			$fields['email'] = Timber::compile( 'comment-form-email.twig', $context );
			$fields['url'] = Timber::compile( 'comment-form-url.twig', $context );

			// TODO make it an option
			// unset($fields['url']);

			return $fields;
		});

		// Remove useless form content, override submit button, comment text field etc
		add_filter( 'comment_form_defaults', function ( $args ) {

			$args['title_reply'] = '';
			$args['class_form'] = 'comment-form';

			$args['submit_button'] = Timber::compile( 'comment-form-submit.twig' );
			$args['comment_notes_before'] = Timber::compile( 'comment-form-notes-before.twig' );
			$args['comment_notes_after'] = Timber::compile( 'comment-form-notes-after.twig' );
			$args['comment_field'] = Timber::compile( 'comment-form-textarea.twig' );

			return $args;
		});

		// Move comment text field to the bottom of the form
		add_filter( 'comment_form_fields', function ( $fields ) {
			$comment_field_bak = $fields['comment'];
			unset( $fields['comment'] );
			$fields['comment'] = $comment_field_bak;
			return $fields;
		} );

	}
}
