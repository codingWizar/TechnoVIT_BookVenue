<?php
/**
 * Common function for admin side
 */

function hb_dropdown_room_capacities( $args = array() ){
    $args = wp_parse_args(
        $args,
        array(
            'echo'  => true
        )
    );
    ob_start();
    wp_dropdown_categories(
        array(
            'taxonomy'      => 'hb_room_capacity',
            'hide_empty'    => false
        )
    );
    $output = ob_get_clean();
    if( $args['echo'] ){
        echo $output;
    }
    return $output;
}

function hb_dropdown_room_types( $args = array() ){
    $args = wp_parse_args(
        $args,
        array(
            'echo'  => true
        )
    );
    ob_start();
    wp_dropdown_categories(
        array(
            'taxonomy'      => 'hb_room_type',
            'hide_empty'    => false
        )
    );
    $output = ob_get_clean();
    if( $args['echo'] ){
        echo $output;
    }
    return $output;
}

/**
 * Define default tabs for settings
 *
 * @return mixed
 */
function hb_admin_settings_tabs(){
    $tabs = array(
        'hotel_info'    => __( 'Hotel Information', 'tp-hotel-booking' ),
        'payments'      => __( 'Payments', 'tp-hotel-booking' ),
        'other'         => __( 'Other Settings', 'tp-hotel-booking' ),
    );
    return apply_filters( 'hb_admin_settings_tabs', $tabs );
}

/**
 * Callback handler for Hotel Information tab content
 */
function hb_admin_settings_tab_hotel_info(){
    TP_Hotel_Booking::instance()->_include( 'includes/admin/views/settings/hotel-info.php' );
}

/**
 * Callback handler for Hotel Information tab content
 */
function hb_admin_settings_tab_payments(){
    echo 'Payment Gateways';
}

/**
 * Callback handler for Hotel Information tab content
 */
function hb_admin_settings_tab_other(){
    echo 'Other settings here';
}

function hb_admin_settings_tab_content( $selected ){
    if( is_callable( "hb_admin_settings_tab_{$selected}" ) ) {
        call_user_func_array( "hb_admin_settings_tab_{$selected}", array() );
    }
}
add_action( 'hb_admin_settings_tab_before', 'hb_admin_settings_tab_content' );

function hb_add_meta_boxes(){
    HB_Meta_Box::instance(
        'room_settings',
        array(
            'title' => __( 'Room Settings', 'tp-hotel-booking' ),
            'post_type' => 'hb_room'
        ),
        array()
    )->add_field(
        array(
            'name'      => 'num_of_rooms',
            'label'     => __( 'Number of rooms', 'tp-hotel-booking' ),
            'type'      => 'number',
            'std'       => '100',
            'desc'      => __( 'The number of rooms', 'tp-hotel-booking' ),
            'min'       => 1,
            'max'       => 100
        )
    )->add_field(
        array(
            'name'      => 'room_type',
            'label'     => __( 'Room type', 'tp-hotel-booking' ),
            'type'      => 'html',
            'content'   => hb_dropdown_room_types(
                array(
                    'echo'      => false,
                    'name'      => 'room_type',
                    'std'       => ''
                )
            )
        ),
        array(
            'name'      => 'num_of_adults',
            'label'     => __( 'Number of adults', 'tp-hotel-booking' ),
            'type'      => 'select'
        ),
        array(
            'name'      => 'max_child_per_room',
            'label'     => __( 'Max child per room', 'tp-hotel-booking' ),
            'type'      => 'number',
            'std'       => 0,
            'min'       => 0,
            'max'       => 100
        )
    );
}
add_action( 'init', 'hb_add_meta_boxes', 50 );