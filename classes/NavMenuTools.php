<?php

namespace Salaros\Wordpress\Template;

use Salaros\Wordpress\Template\TwigExtensions;

class NavMenuTools {

	public static function menu_exists( $menu_name ) {
		$menu_obj = wp_get_nav_menu_object( $menu_name );
		return ( $menu_obj && $menu_obj->term_id > 0 );
	}

	public static function create_menu( $menu_name, $menu_items ) {

		if ( self::menu_exists( $menu_name ) ) {
			return false; // TODO log the error
		}

		$menu_id = wp_create_nav_menu( $menu_name );
		if ( $menu_id <= 0 ) {
			return false; // TODO log the error
		}

		foreach ( $menu_items as $menu_item ) {

			$item = self::create_menu_item_obj( $menu_item );
			$item_id = wp_update_nav_menu_item( $menu_id, 0, $item );
			if ( $item_id <= 0 ) {
				continue; // TODO log the error
			}

			if ( isset( $menu_item['data'] ) && is_array( $menu_item['data'] ) ) {
				foreach ( $menu_item['data'] as $meta_key => $meta_value ) {
					update_post_meta( $item_id, '_menu_item_' . $meta_key, $meta_value );
				}
			}

			if ( ! array_key_exists( 'children', $menu_item ) ) {
				continue;
			}

			// create child items
			foreach ( $menu_item['children'] as $child_item ) {
				$subitem = self::create_menu_item_obj( $child_item, $item_id );
				$subitem_id = wp_update_nav_menu_item( $menu_id, 0, $subitem );
				if ( $subitem_id <= 0 ) {
					continue; // TODO log the error
				}
			}
		}

		return true;
	}

	public static function create_menu_item_obj( $menu_item, $parent_item_id = -1 ) {

		if ( array_key_exists( 'slug', $menu_item ) ) {
			$page = TwigExtensions::get_page_by_slug( $menu_item['slug'] );
			$menu_item['id'] = $page->ID;
			$menu_item['title'] = $page->title;
		}

		$item = [
			'menu-item-title' => @$menu_item['title'],
			'menu-item-classes' => @$menu_item['classes'],
			'menu-item-status' => 'publish',
		];

		$item['menu-item-url'] = '#';
		if ( array_key_exists( 'id', $menu_item ) ) {
			$item['menu-item-object-id'] = $menu_item['id'];
			$item['menu-item-object'] = 'page';
			$item['menu-item-type'] = 'post_type';
		} elseif ( array_key_exists( 'url', $menu_item ) ) {
			$item['menu-item-url'] = $menu_item['url'];
		}

		if ( $parent_item_id > 0 ) {
			$item['menu-item-parent-id'] = $parent_item_id;
		}

		return $item;
	}
}
