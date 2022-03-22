<?php
namespace NPBreland\WPUni;

require_once 'vendor/npbreland/php-university/autoload.php';

/**
 * Plugin Name: WP PHP University
 */


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
			[
				'key' => 'field_phpuni_class_days',
				'label' => 'Days',
				'name' => 'class_days',
				'type' => 'select',
                'ui' => 1,
                'choices' => [ 
                    '1' => 'Monday',
                    '2' => 'Tuesday',
                    '3' => 'Wednesday',
                    '4' => 'Thursday',
                    '5' => 'Friday',
                    '6' => 'Saturday',
                    '7' => 'Sunday',
                ],
                'multiple' => 1,
			],
			[
				'key' => 'field_phpuni_class_start_time',
				'label' => 'Start time',
				'name' => 'class_start_time',
				'type' => 'time_picker',
			],
			[
				'key' => 'field_phpuni_class_end_time',
				'label' => 'End time',
				'name' => 'class_end_time',
				'type' => 'time_picker',
			],
			[
				'key' => 'field_phpuni_class_start_date',
				'label' => 'Start date',
				'name' => 'class_start_date',
				'type' => 'date_picker',
			],
			[
				'key' => 'field_phpuni_class_end_date',
				'label' => 'End date',
				'name' => 'class_end_date',
				'type' => 'date_picker',
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

add_filter( 'acf/validate_value/name=course_code', __NAMESPACE__ . '\\validate_course_code', 10, 4 );
function validate_course_code( $valid, $value, $field, $input_name )
{
    // Bail early if value is already invalid.
    if( $valid !== true ) {
        return $valid;
    }

    $ID = get_the_ID();

    $course_with_this_code = get_course_by_code( $value );

    if ( $course_with_this_code && $course_with_this_code != $ID ) {
        $err_msg = <<<MSG
Another course has the course code $value. Please enter a unique course code.
MSG;
        return $err_msg;
    }

    return $valid;
}


add_action( 'acf/validate_save_post', __NAMESPACE__ . '\\validate_save');
function validate_save()
{
    $start_time = $_POST['acf']['field_phpuni_class_start_time'];
    $end_time = $_POST['acf']['field_phpuni_class_end_time'];

    if ( isset($start_time) && $start_time >= $end_time ) {
        $err_msg = "The end time must be greater than start time.";
        \acf_add_validation_error( 'acf[field_phpuni_class_end_time]', $err_msg );
    }

    $start_date = new \DateTime($_POST['acf']['field_phpuni_class_start_date']);
    $end_date = new \DateTime($_POST['acf']['field_phpuni_class_end_date']);

    if ( $start_date >= $end_date ) {
        $err_msg = "The end date must be greater than start date.";
        \acf_add_validation_error( 'acf[field_phpuni_class_end_date]', $err_msg );
    }

    $days = $_POST['acf']['field_phpuni_class_days'];

    if ( !in_array( $start_date->format('N'), $days ) ) {
        $err_msg = "The start date must be one of the selected days.";
        \acf_add_validation_error( 'acf[field_phpuni_class_start_date]', $err_msg );
    }

    if ( !in_array( $end_date->format('N'), $days ) ) {
        $err_msg = "The end date must be one of the selected days.";
        \acf_add_validation_error( 'acf[field_phpuni_class_end_date]', $err_msg );
    }

    $instructor_id = $_POST['acf']['field_phpuni_class_instructor'];
    $all_classes_by_them = get_classes_by_instructor( $instructor_id );

    $this_class_ID = get_the_ID();

    $other_classes = array_filter($all_classes_by_them, function ($class_ID) use ($this_class_ID) {
        return $class_ID != $this_class_ID; 
    });

}

function get_course_by_code( $code )
{
    $args = [
        'posts_per_page' => -1,
        'post_type' => 'course',
        'meta_key' => 'course_code',
        'meta_value' => $code,
    ];

    $ID = false;

    $the_query = new \WP_Query( $args );
    if ( $the_query->have_posts() ) {
        while ( $the_query->have_posts() ) {
            $the_query->the_post();
            $ID = get_the_ID();
        }
    }

    wp_reset_query();

    return $ID;
}

function get_classes_by_instructor( $user_id )
{
    $args = [
        'posts_per_page' => -1,
        'post_type' => 'class',
        'meta_key' => 'class_instructor',
        'meta_value' => $user_id,
    ];

    $IDs = [];

    $the_query = new \WP_Query( $args );
    if ( $the_query->have_posts() ) {
        while ( $the_query->have_posts() ) {
            $the_query->the_post();
            $IDs[] = get_the_ID();
        }
    }

    wp_reset_query();

    return $IDs;
}

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
