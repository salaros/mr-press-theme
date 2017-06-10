<?php

namespace Salaros\Wordpress\Template;

class Asset_Manager {

	private $scripts;
	private $styles;
	private $scripts_admin;
	private $styles_admin;
	private $scripts_remove;
	private $styles_remove;

	public function __construct() {

		// Set parent theme-related properties
		$this->scripts = [];
		$this->styles = [];
		$this->scripts_admin = [];
		$this->styles_admin = [];
		$this->scripts_remove = [];
		$this->styles_remove = [];
	}

	public function initialize() {

		// Remove CSS <link id="xxx" attrib for PageSpeed compatibility
		// https://blog.codecentric.de/en/2011/10/wordpress-and-mod_pagespeed-why-combine_css-does-not-work
		add_filter( 'style_loader_tag', array( $this, 'process_style_tags' ) ); // TODO make it a theme option

		add_filter( 'script_loader_tag', array( $this, 'process_script_tag' ), 11, 2 );

		// Register actions for custom scripts, styles, menus etc
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );

		// Deregister scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles' ) );

		// Register actions for custom WP-ADMIN scripts, styles, menus etc
		add_action( 'wp_enqueue_scripts', array( $this, 'deregister_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'deregister_styles' ) );
	}

	public function process_style_tags( $link ) {
		return preg_replace( "/id='.*-css'/", '', $link );
	}

	public function process_script_tag( $tag, $handle ) {
		if ( wp_scripts()->get_data( $handle, 'defer' ) ) {
			$tag = str_replace( '></', ' defer></', $tag );
		}
		if ( wp_scripts()->get_data( $handle, 'async' ) ) {
			$tag = str_replace( '></', ' async></', $tag );
		}
		$processed_tag = apply_filters( sprintf( '%s_loaded', $handle ), $tag );
		return $processed_tag ?: $tag;
	}


	public function add_script( $handle, $src = '', array $deps = [], $ver = false, $in_footer = false, array $data = [] ) {
		$this->scripts[ $handle ] = [
			'src' => $src,
			'deps' => $deps,
			'ver' => $ver,
			'in_footer' => $in_footer,
			'data' => $data,
		];
	}

	public function add_admin_script( $handle, $src = '', array $deps = [], $ver = false, $in_footer = false, array $data = [] ) {
		$this->scripts_admin[ $handle ] = [
			'src' => $src,
			'deps' => $deps,
			'ver' => $ver,
			'in_footer' => $in_footer,
			'data' => $data,
		];
	}

	/**
	 * Iterates throught a list of scripts and equeues them using WordPress functions
	 *
	 * @param array $scripts List of scripts
	 *
	 * @return  void
	 *
	 */
	private function register_scripts_generic( array $scripts ) {
		global $wp_scripts;

		foreach ( $scripts as $handle => $script ) {
			if ( ! empty( $script['src'] ) ) {
				wp_register_script( $handle, $script['src'], $script['deps'], $script['ver'], $script['in_footer'] );
				if ( is_array( $script['data'] ) ) {
					foreach ( $script['data'] as $key => $value ) {
						$wp_scripts->add_data( $handle, $key, $value );
					}
				}
			}
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Register and enqueue a custom scripts
	 *
	 * @param   array $scripts List of scripts
	 *
	 * @return  void
	 *
	 */
	public function register_scripts() {
		$this->register_scripts_generic( $this->scripts );
	}

	/**
	 * Register and enqueue a custom scripts in the WordPress admin UI, excluding edit.php
	 *
	 * @return  void
	 *
	 */
	public function register_admin_scripts() {
		$this->register_scripts_generic( $this->scripts_admin );
	}

	/**
	 * Register and enqueue a custom stylesheet
	 *
	 * @return  void
	 *
	 */
	public function add_style( $handle, $src = '', array $deps = [], $ver = false, $media = 'all', array $data = [] ) {
		$this->styles[ $handle ] = [
			'src' => $src,
			'deps' => $deps,
			'ver' => $ver,
			'media' => $media,
			'data' => $data,
		];
	}

	/**
	 * Register and enqueue a custom stylesheet in the WordPress admin UI, excluding edit.php
	 *
	 * @return  void
	 *
	 */
	public function add_admin_style( $handle, $src = '', array $deps = [], $ver = false, $media = 'all', array $data = [] ) {
		$this->styles_admin[ $handle ] = [
			'src' => $src,
			'deps' => $deps,
			'ver' => $ver,
			'media' => $media,
			'data' => $data,
		];
	}

	private function register_styles_generic( array $styles ) {
		global $wp_scripts;

		foreach ( $styles as $handle => $stylesheet ) {
			wp_register_style( $handle, $stylesheet['src'], $stylesheet['deps'], $stylesheet['ver'], $stylesheet['media'] );
			if ( is_array( $stylesheet['data'] ) ) {
				foreach ( $stylesheet['data'] as $key => $value ) {
					$wp_scripts->add_data( $handle, $key, $value );
				}
			}
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Register and enqueue a custom stylesheets in the WordPress
	 *
	 * @return  void
	 *
	 */
	public function register_styles() {
		$this->register_styles_generic( $this->styles );
	}

	/**
	 * Register and enqueue a custom stylesheets in the WordPress admin UI, excluding edit.php
	 *
	 * @return  void
	 *
	 */
	public function register_admin_styles() {
		$this->register_styles_generic( $this->styles_admin );
	}

	/**
	 * Remove a registered scripts
	 *
	 * @return  void
	 *
	 */
	public function deregister_styles() {
		array_walk( $this->styles_remove, 'wp_dequeue_style' );
		array_walk( $this->styles_remove, 'wp_deregister_style' );
	}

	/**
	 * Remove a registered stylesheets
	 *
	 * @return  void
	 *
	 */
	public function deregister_scripts() {
		array_walk( $this->scripts_remove, 'wp_dequeue_script' );
		array_walk( $this->scripts_remove, 'wp_deregister_script' );
	}

}
