<?php

/**
 * Plugin Name: WP PHP University
 */

namespace WPUni;

// This plugin bundles ACF, but doesn't require it if it's already installed.
if ( !class_exists('ACF') ) {
    require_once 'vendor/advanced-custom-fields/acf.php';

    // Hide ACF admin. We only run this in the background.
    add_filter( 'acf/settings/show_admin', '__return_false' );
}

function setup_post_types()
{
    register_post_type( 'course' , [
        'public' => true,
        'label' => 'Courses',
    ] );
    register_post_type( 'class' , [
        'public' => true,
        'label' => 'Classes',
    ] );
}

function acf_add_local_field_groups()
{
    acf_add_local_field_group([
		'key' => 'group_phpuni_course',
		'title' => 'Course Details',
		'fields' => [
			[
				'key' => 'field_phpuni_course_code',
				'label' => 'Course code',
				'name' => 'course_code',
				'type' => 'text',
            ],
			[
				'key' => 'field_phpuni_course_num_credits',
				'label' => 'Number of credits',
				'name' => 'course_num_credits',
				'type' => 'number',
			],
			[
				'key' => 'field_phpuni_course_prereqs',
				'label' => 'Prerequisite courses',
				'name' => 'course_prereqs',
				'type' => 'post_object',
				'post_type' => 'course',
				'multiple' => 1,
			],
		],
		'location' => [
			[
				[
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'course',
				],
			],
		],
	]);

    acf_add_local_field_group([
		'key' => 'group_phpuni_class',
		'title' => 'Class Details',
		'fields' => [
			[
				'key' => 'field_phpuni_class_course',
				'type' => 'post_object',
				'post_type' => 'course',
				'label' => 'Course',
				'name' => 'class_course',
            ],
			[
				'key' => 'field_phpuni_class_instructor',
				'label' => 'Instructor',
				'name' => 'class_instructor',
				'type' => 'user',
                'role' => [ 'instructor' ]
			],
		],
		'location' => [
			[
				[
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'class',
				],
			],
		],
	]);
}

add_action( 'init', __NAMESPACE__ . '\\setup_post_types' );
add_action( 'acf/init', __NAMESPACE__ . '\\acf_add_local_field_groups');

function add_roles()
{
    add_role( 'instructor', 'Instructor', get_role( 'author' )->capabilities );
    add_role( 'student', 'Student', get_role( 'contributor' )->capabilities );
}

function remove_roles()
{
    remove_role( 'instructor' );
    remove_role( 'student' );
}

function activate()
{
    add_roles();
    flush_rewrite_rules();
}

function deactivate()
{
    unregister_post_type( 'course' );
    unregister_post_type( 'class' );
    remove_roles();
    flush_rewrite_rules();
}

function uninstall()
{

}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );
register_deactivation_hook( __FILE__,  __NAMESPACE__ . '\\deactivate' );
register_uninstall_hook( __FILE__, __NAMESPACE__. '\\uninstall' );
