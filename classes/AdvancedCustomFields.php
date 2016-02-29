<?php

namespace Salaros\Wordpress\Template;

class AdvancedCustomFields {

    public static function init() {

        if( ! function_exists('register_field_group') )
            return;

        register_field_group(array (
            'id' => 'acf_test-group',
            'title' => 'Test group',
            'fields' => array (
                array (
					'key' => 'field_1000000000000',
                    'label' => 'Test bool',
                    'name' => 'test_bool',
                    'type' => 'true_false',
                    'message' => '',
                    'default_value' => 0,
                )
            ),
            'options' => array (
                'position' => 'normal',
                'layout' => 'no_box',
                'hide_on_screen' => array (
                ),
            ),
            'menu_order' => 0,
        ));
    }
}
