<?php
/*
Plugin Name: Upcoming Events
Plugin URI: http://madelynvagle.me
Description: Shows a list of upcoming events
Version: 1.0
Author: Madelyn Vagle
Author URI: http://madelynvagle.me
*/

// variables 
$ue_all_imported_events = array();
$ue_names = array();
$ue_starts = array();
$ue_ends = array();
$ue_locations = array();
$ue_event_calendar = array();

// Enqueue Stylesheet
function ue_stylesheet() {
    wp_enqueue_style( 'uestyles', plugins_url('/css/uestyles.css', __FILE__ ) );
}
add_action('wp_enqueue_scripts', 'ue_stylesheet' );


// Create the event post type 
function ue_custom_post() {
    $ue_labels = array(
        'name' => esc_attr__( 'Events', 'upcoming-events' ),
        'singular_name' => esc_attr__( 'Event', 'upcoming-events' ),
        'all_items' => esc_attr__( 'All Events', 'upcoming-events' ),
        'add_new_item' => esc_attr__( 'Add New Event', 'upcoming-events' ),
        'add_new' => esc_attr__( 'New Event', 'upcoming-events' ),
        'new_item' => esc_attr__( 'New Event', 'upcoming-events' ),
        'edit_item' => esc_attr__( 'Edit Event', 'upcoming-events' ),
        'view_item' => esc_attr__( 'View Event', 'upcoming-events' ),
        'search_items' => esc_attr__( 'Search Events', 'upcoming-events' ),
        'not_found' => esc_attr__( 'That event does not exist!', 'upcoming-events' ),
        'not_found_in_trash' => esc_attr__( 'You never deleted this event', 'upcoming-events' )

    );
    $ue_args = array(
        'labels' => $ue_labels,
        'menu_icon' => 'dashicons-calendar',
        'public' => true,
        'menu_position' => 15,
        'can_export' => true,
        'show_in_nav_menus' => false,
        'has_archive' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'capability_type' => 'post',
        'taxonomies' => array(),
        'supports' => array( 'thumbnail', 'title' )
    );
    register_post_type( 'event', $ue_args );
}
add_action( 'init', 'ue_custom_post' );

// Creates meta-boxes
function ue_meta_boxes() {
    add_meta_box( 'ue_meta_box', 
    'Event Details',
    'display_event_detail_meta_box',
    'event', 'normal', 'high'
);
}
add_action( 'admin_init', 'ue_meta_boxes' );

// Displays meta-boxes
function display_event_detail_meta_box( $event_listing ) {
    // retrieve the event date and location based on the event ID
    $event_start_date = esc_html( get_post_meta( $event_listing->ID, 'event_start_date', true ) );
    $event_end_date = esc_html( get_post_meta( $event_listing->ID, 'event_end_date', true ) );
    $event_location = esc_html( get_post_meta( $event_listing->ID, 'event_location', true ) );

    // get the date if it is entered
    $event_start_date = !empty( $event_start_date ) ? $event_start_date : time();
    $event_end_date = !empty( $event_end_date ) ? $event_end_date : time();

    // set the date format
    $dateformat = get_option('date_format');
    $dateformat = 'm-d-Y'; 

    // finally it is time to display the event meta box
    ?>
    <p><label for="ue_start_date"><?php esc_attr_e( 'Start Date', 'upcoming-events' ); ?></label>
    <input id="ue_start_date" type="text" name="ue_start_date" placeholder="<?php esc_attr_e( 'Choose a start date', 'upcoming-events' ); ?>" required maxlength="10" value="<?php echo date_i18n( $dateformat, esc_attr( $event_start_date ) ); ?>" /></p>  
    
    <p><label for="ue_end_date"><?php esc_attr_e( 'End Date', 'upcoming-events' ); ?></label>
    <input id="ue_end_date" type="text" name="ue_end_date" placeholder="<?php esc_attr_e( 'Choose an end date', 'upcoming-events' ); ?>" required maxlength="10" value="<?php echo date_i18n( $dateformat, esc_attr( $event_end_date ) ); ?>" /></p>   

    <p><label for="ue_event_location"><?php esc_attr_e( 'Event Location', 'upcoming-events' ); ?></label>
    <input id="ue_event_location" type="text" name="ue_event_location" placeholder="<?php esc_attr_e( 'Example: Troutt Theater', 'upcoming-events' ); ?>" value="<?php echo esc_attr( $event_location ); ?>" /></p>
    <?php
}

// Save the meta-box entries 
function ue_add_event_fields( $event_id, $event_listing ) {
    if ( $event_listing->post_type == 'event' ) {
        if ( isset( $_POST['ue_start_date'] ) && $_POST['ue_start_date'] != '' ) {
            update_post_meta( $event_id, 'event_start_date', $_POST['ue_start_date'] );
        }
        if ( isset( $_POST['ue_end_date'] ) && $_POST['ue_end_date'] != '' ) {
            update_post_meta( $event_id, 'event_end_date', $_POST['ue_end_date'] );
        }
        if ( isset( $_POST['ue_event_location'] ) && $_POST['ue_event_location'] != '' ) {
            update_post_meta( $event_id, 'event_location', $_POST['ue_event_location'] );
        }
    }
}
add_action( 'save_post', 'ue_add_event_fields', 10, 2 );

// Changes the title placeholder 
add_filter( 'enter_title_here', 'ue_title_change' );
function ue_title_change( $title ) {
    $screen = get_current_screen();

    if( 'event' == $screen->post_type ) {
        $title = 'Enter event name here';
    }
    return $title;
}

// Updates the event admin interface
function ue_custom_columns( $columns ) {
    $columns['ue_start_date_column'] = 'Start Date';
    $columns['ue_end_date_column'] = 'End Date';
    $columns['ue_location_column'] = 'Location';
    unset( $columns['comments'] );
    unset( $columns['date'] );
    return $columns;
}
add_filter( 'manage_edit-event_columns', 'ue_custom_columns');

// Populates the custom columns
function ue_populate_columns( $column, $event_id ) {
    if( 'ue_start_date_column' == $column ) {
        $column_start_date = esc_html( get_post_meta( $event_id, 'event_start_date', true ) );
        if( !empty($column_start_date) ) {
            echo $column_start_date;
        }
    }
    if ( 'ue_end_date_column' == $column ) {
        $column_end_date = esc_html( get_post_meta( $event_id , 'event_end_date', true ) );
        if( !empty($column_end_date) ) {
            echo $column_end_date;
        }
    }
    if ( 'ue_location_column' == $column ) {
        $column_location = esc_html( get_post_meta( $event_id, 'event_location', true ) );
        if( !empty($column_location) ) {
            echo $column_location;
        }
    }
}
add_action( 'manage_event_posts_custom_column', 'ue_populate_columns', 10, 2 );

// Allow columns to be sorted
function ue_sort_columns( $columns ) {
    $columns['ue_start_date_column'] = 'event_start_date';
    $columns['ue_end_date_column'] = 'event_end_date';

    return $columns;
}
add_filter( 'manage_edit-event_sortable_columns', 'ue_sort_columns' );

// Orders the columns 
function ue_column_orderby( $vars ) {
    if( !is_admin() ) {
        return $vars;
    }
    if( isset( $vars['orderby'] ) && 'ue_start_date_column' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array( 'meta_key' => 'ue_start_date', 'orderby' => 'meta_value' ) );
    }
    elseif( isset( $vars['orderby'] ) && 'ue_end_date_column' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array( 'meta_key' => 'ue_end_date', 'orderby' => 'meta_value' ) );
    }
    return $vars;
}
add_filter( 'request', 'ue_column_orderby' );

// Register the activation hook 
function ue_register_activation_hook() {
    ue_custom_post();
}
register_activation_hook( __FILE__, 'ue_register_activation_hook' );

// Registers the deactivation hook 
function ue_register_deactivation_hook() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ue_register_deactivation_hook' );

  function ue_create_event_calendar() {
    $ue_all_imported_events = array();
    $ue_names = array();
    $ue_starts = array();
    $ue_ends = array();
    $ue_locations = array();
    $ue_event_calendar = array();
    
     // imports the event information
     $import_args = array(
        'numberposts' => -1,
        'post_type' => 'event',
        'post_status' => 'publish'
     ); 
    $imported_events = get_posts( $import_args );
    global $post;
    $ue_imported_event = $post;

     foreach( $imported_events as $ie ) {
        //print_r($imported_events);
         setup_postdata($ie);
         $ue_start = get_post_meta($ie->ID, 'event_start_date', true);
         $ue_end = get_post_meta($ie->ID, 'event_end_date', true);
         $ue_loc = get_post_meta($ie->ID, 'event_location', true);
         $ue_name = $ie->post_title;
         $ue_single_event = array(
            'import_start_date' => $ue_start,
            'import_end_date' => $ue_end,
            'import_location' => $ue_loc,
            'import_name' => $ue_name
        );
         //print_r($ue_single_event);
        $ue_all_imported_events[$ue_name] = $ue_single_event;
        $post = $ue_imported_event;
        setup_postdata($post);
       // print_r($ue_all_imported_events);
    }

    // Tests the end dates of each imported event 
   $event_c = count($ue_all_imported_events);
   for( $i = 0; $i < $event_c; $i++ ) {
        $ue_end = $ue_all_imported_events[$i]['import_end_date'];
        $ue_newEnd = str_replace('-', '/', $ue_end);
        $ue_timestamp = strtotime($ue_newEnd);
        $ue_compare = date('m-d-Y', $ue_timestamp);
        $ue_current_date = date('m-d-Y');
        if($ue_compare > $ue_current_date) {
            $ue_event_calendar[] = $ue_all_imported_events[$i];
        } else {
        }
    }

     // Sorts the event calendar 
   $ue_start_dates = array();
    foreach ($ue_event_calendar as $event) {
        $ue_start_dates[] = $event['event_start_date'];
    }
    array_multisort($ue_start_dates, SORT_ASC, $ue_event_calendar);

    // Creates individual arrays 
    foreach( $ue_event_calendar as $event) {
        $ue_names[] = $event['import_name'];
        $ue_starts[] = $event['import_start_date'];
        $ue_ends[] = $event['import_end_date'];
        $ue_locations[] = $event['import_location'];
    }
    $num_events = count($ue_event_calendar); 
    
} 
?>