<?php
namespace NPBreland\WPUni;

require_once 'vendor/npbreland/php-university/autoload.php';

/**
 * Plugin Name: WP PHP University
 */


// This plugin bundles ACF, but doesn't require it if it's already installed.
if ( ! class_exists( 'ACF' ) ) {
	require_once 'vendor/advanced-custom-fields/acf.php';

	// Hide ACF admin. We only run this in the background.
	add_filter( 'acf/settings/show_admin', '__return_false' );
}

/**
 * Registers custom post types for the plugin.
 */
function setup_post_types() {
	register_post_type(
		'course',
		array(
			'public'   => true,
			'label'    => 'Courses',
			'supports' => false, // Removes editor/title
		)
	);
	register_post_type(
		'class',
		array(
			'public'   => true,
			'label'    => 'Classes',
			'supports' => false,
		)
	);
}

/**
 * Edits columns in the header for the admin post list for the "classes" custom
 * post type
 *
 * @param array $columns array of columns
 */
function class_column_list( $columns ) {
	unset( $columns['title'] );
	unset( $columns['date'] );
	$columns['course']     = 'Course';
	$columns['days_time']  = 'Days/Time';
	$columns['instructor'] = 'Instructor';
	return $columns;
}
add_filter( 'manage_class_posts_columns', __NAMESPACE__ . '\\class_column_list' );

/**
 * Adds column values for admin post list for the "classes" custom post type
 *
 * @param $column current column
 * @param $post_id current post ID
 */
function class_custom_column_values( $column, $post_id ) {
	switch ( $column ) {
		case 'instructor':
			$instructor = get_field( 'field_phpuni_class_instructor', $post_id );
			$first      = $instructor['user_firstname'];
			$last       = $instructor['user_lastname'];
            $text = "$last, $first";
			\esc_html_e( $text );
			break;
		case 'course':
			$course = get_field( 'field_phpuni_class_course', $post_id );
			\esc_html_e( $course->post_title );
			break;
		case 'days_time':
			$days = get_field( 'field_phpuni_class_days', $post_id );
			$day_str = array_reduce( $days, __NAMESPACE__ . '\\getDayAbbreviation', '' );
			$start_time = get_field( 'field_phpuni_class_start_time', $post_id );
			$end_time   = get_field( 'field_phpuni_class_end_time', $post_id );
            $text = "$day_str $start_time-$end_time";
			\esc_html_e( $text );
            break;
	}
}
add_action( 'manage_class_posts_custom_column', __NAMESPACE__ . '\\class_custom_column_values', 10, 2 );

/**
 * Looks up abbreviation for the given day number and appends it to the passed
 * in string.
 *
 * @param $str string to which abbreviation will be appended
 * @param $day_num ISO 8601 day number code to look up in the array
 * @return $str string with appended abbreviation
 */
function getDayAbbreviation( $str, $day_num ) {
	$table = array(
		'1' => 'M',
		'2' => 'Tu',
		'3' => 'W',
		'4' => 'Th',
		'5' => 'F',
		'6' => 'Sa',
		'7' => 'Su',
	);

	$str = $str . $table[ $day_num ];
	return $str;
}

/**
 * Uses ACF function to add field groups to custom post types
 */
function acf_add_local_field_groups() {
	acf_add_local_field_group(
		array(
			'key'      => 'group_phpuni_course',
			'title'    => 'Course Details',
			'fields'   => array(
				array(
					'key'   => 'field_phpuni_course_code',
					'label' => 'Course code',
					'name'  => 'course_code',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_phpuni_course_num_credits',
					'label' => 'Number of credits',
					'name'  => 'course_num_credits',
					'type'  => 'number',
				),
				array(
					'key'       => 'field_phpuni_course_prereqs',
					'label'     => 'Prerequisite courses',
					'name'      => 'course_prereqs',
					'type'      => 'post_object',
					'post_type' => 'course',
					'multiple'  => 1,
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'course',
					),
				),
			),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_phpuni_class',
			'title'    => 'Class Details',
			'fields'   => array(
				array(
					'key'       => 'field_phpuni_class_course',
					'type'      => 'post_object',
					'post_type' => 'course',
					'label'     => 'Course',
					'name'      => 'class_course',
				),
				array(
					'key'   => 'field_phpuni_class_instructor',
					'label' => 'Instructor',
					'name'  => 'class_instructor',
					'type'  => 'user',
					'role'  => array( 'instructor' ),
				),
				array(
					'key'      => 'field_phpuni_class_days',
					'label'    => 'Days',
					'name'     => 'class_days',
					'type'     => 'select',
					'choices'  => array(
						'1' => 'Monday',
						'2' => 'Tuesday',
						'3' => 'Wednesday',
						'4' => 'Thursday',
						'5' => 'Friday',
						'6' => 'Saturday',
						'7' => 'Sunday',
					),
					'multiple' => 1,
				),
				array(
					'key'   => 'field_phpuni_class_start_time',
					'label' => 'Start time',
					'name'  => 'class_start_time',
					'type'  => 'time_picker',
				),
				array(
					'key'   => 'field_phpuni_class_end_time',
					'label' => 'End time',
					'name'  => 'class_end_time',
					'type'  => 'time_picker',
				),
				array(
					'key'   => 'field_phpuni_class_start_date',
					'label' => 'Start date',
					'name'  => 'class_start_date',
					'type'  => 'date_picker',
				),
				array(
					'key'   => 'field_phpuni_class_end_date',
					'label' => 'End date',
					'name'  => 'class_end_date',
					'type'  => 'date_picker',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'class',
					),
				),
			),
		)
	);
}

add_action( 'init', __NAMESPACE__ . '\\setup_post_types' );
add_action( 'acf/init', __NAMESPACE__ . '\\acf_add_local_field_groups' );

/**
 * Validates course code by checking that no other course uses the input code.
 *
 * @param $valid current validity status from previous filters
 * @param $value course code
 * @return $valid true if valid
 */
function validate_course_code( $valid, $value ) {
	// Bail early if value is already invalid.
	if ( true !== $valid ) {
		return $valid;
	}

	$id = get_the_ID();

	$course_with_this_code = get_course_by_code( $value )[0];

	if ( $course_with_this_code && $course_with_this_code->ID !== $id ) {
		$err_msg = <<<MSG
Another course has the course code $value. Please enter a unique course code.
MSG;
		return $err_msg;
	}

	return $valid;
}
add_filter( 'acf/validate_value/name=course_code', __NAMESPACE__ . '\\validate_course_code', 10, 2 );

add_action( 'acf/validate_save_post', __NAMESPACE__ . '\\validate_save' );

/**
 * Validates fields saved in the Class Details form
 */
function validate_save() {
	$start_time = wp_unslash( $_POST['acf']['field_phpuni_class_start_time'] );
	$end_time   = wp_unslash( $_POST['acf']['field_phpuni_class_end_time'] );

	if ( isset( $start_time ) && $start_time >= $end_time ) {
		$err_msg = 'The end time must be after the start time.';
		acf_add_validation_error( 'acf[field_phpuni_class_end_time]', $err_msg );
	}

	$start_date_input = wp_unslash( $_POST['acf']['field_phpuni_class_start_date'] );
	$end_date_input   = wp_unslash( $_POST['acf']['field_phpuni_class_end_date'] );

	if ( ! $start_date_input || ! $end_date_input ) {
		return;
	}

	$start_date = new \DateTime( $start_date_input );
	$end_date   = new \DateTime( $end_date_input );

	if ( $start_date >= $end_date ) {
		$err_msg = 'The end date must be after the start date.';
		acf_add_validation_error( 'acf[field_phpuni_class_end_date]', $err_msg );
	}

	$days = wp_unslash( $_POST['acf']['field_phpuni_class_days'] );

	if ( ! $days ) {
		return;
	}

	if ( ! in_array( $start_date->format( 'N' ), $days, true ) ) {
		$err_msg = 'The start date must be one of the selected days.';
		acf_add_validation_error( 'acf[field_phpuni_class_start_date]', $err_msg );
	}

	if ( ! in_array( $end_date->format( 'N' ), $days, true ) ) {
		$err_msg = 'The end date must be one of the selected days.';
		acf_add_validation_error( 'acf[field_phpuni_class_end_date]', $err_msg );
	}

	$instructor_id       = wp_unslash( $_POST['acf']['field_phpuni_class_instructor'] );
	$all_classes_by_them = get_classes_by_instructor( $instructor_id );

	$this_class_id = get_the_ID();

	$other_classes = array_filter(
		$all_classes_by_them,
		function ( $class ) use ( $this_class_id ) {
			return $class->ID !== $this_class_id;
		}
	);

	$other_class_times = array();
	foreach ( $other_classes as $other ) {
		$other_days = \get_field( 'field_phpuni_class_days', $other->ID );

		if ( ! is_array( $other_days ) ) {
			continue;
		}

		$other_start = \get_field( 'field_phpuni_class_start_time', $other->ID, false );
		$other_end   = \get_field( 'field_phpuni_class_end_time', $other->ID, false );

		if ( ! $other_start || ! $other_end ) {
			continue;
		}

		$start_pieces = explode( ':', $other_start );
		$start_hour   = intval( $start_pieces[0] );
		$start_minute = intval( $start_pieces[1] );

		$end_pieces = explode( ':', $other_end );
		$end_hour   = intval( $end_pieces[0] );
		$end_minute = intval( $end_pieces[1] );

		foreach ( $other_days as $day_num ) {

			if ( ! in_array( $day_num, $days, true ) ) {
				continue;
			}

			$day_num             = intval( $day_num );
			$other_class_times[] = new \NPBreland\PHPUni\ClassTime(
				$day_num,
				$start_hour,
				$start_minute,
				$end_hour,
				$end_minute
			);
		}
	}

	$start_pieces = explode( ':', $start_time );
	$start_hour   = intval( $start_pieces[0] );
	$start_minute = intval( $start_pieces[1] );

	$end_pieces = explode( ':', $end_time );
	$end_hour   = intval( $end_pieces[0] );
	$end_minute = intval( $end_pieces[1] );

	foreach ( $days as $day_num ) {
		$day_num    = intval( $day_num );
		$class_time = new \NPBreland\PHPUni\ClassTime(
			$day_num,
			$start_hour,
			$start_minute,
			$end_hour,
			$end_minute
		);

		foreach ( $other_class_times as $other ) {
			if ( $class_time->overlaps( $other ) ) {
				$err_msg = <<<MSG
The given days and time would overlap at least one other class by the instructor.
MSG;
				acf_add_validation_error( 'acf[field_phpuni_class_start_time]', $err_msg );
			}
		}
	}

}

/**
 * Gets courses by code (should be one at most)
 *
 * @param $code course code
 * @return array WP_Post[]
 */
function get_course_by_code( $code ) {
	$args = array(
		'posts_per_page' => -1,
		'post_type'      => 'course',
		'meta_key'       => 'course_code',
		'meta_value'     => $code,
	);

	return \get_posts( $args );
}

/**
 * Gets classes taught by the instructor
 *
 * @param int $user_id instructor user ID
 * @return array $posts WP_Post[]
 */
function get_classes_by_instructor( $user_id ) {
	$args = array(
		'posts_per_page' => -1,
		'post_type'      => 'class',
		'meta_key'       => 'class_instructor',
		'meta_value'     => $user_id,
	);

	return \get_posts( $args );
}

/**
 * Adds custom roles for the plugin
 */
function add_roles() {
	add_role( 'instructor', 'Instructor', get_role( 'author' )->capabilities );
	add_role( 'student', 'Student', get_role( 'contributor' )->capabilities );
}

/**
 * Removes custom roles defined by the plugin
 */
function remove_roles() {
	remove_role( 'instructor' );
	remove_role( 'student' );
}

/**
 * Activation steps for the plugin
 */
function activate() {
	add_roles();
	flush_rewrite_rules();
}

/**
 * Deactivation steps for the plugin
 */
function deactivate() {
	 unregister_post_type( 'course' );
	unregister_post_type( 'class' );
	remove_roles();
	flush_rewrite_rules();
}

/**
 * Uninstall steps for the plugin
 */
function uninstall() {
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\\uninstall' );
