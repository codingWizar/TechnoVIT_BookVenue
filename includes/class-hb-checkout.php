<?php

/**
 * Class HB_Checkout
 */
class HB_Checkout{

    /**
     * @var HB_Checkout object instance
     * @access protected
     */
    static protected $_instance = null;

    /**
     * Payment method
     *
     * @var string
     */
    public $payment_method = '';

    /**
     * Constructor
     */
    function __construct(){
        //
    }

    /**
     * Create new customer for checkout if needed
     *
     * @return int
     */
    function create_customer(){
        $customer_info = array(
            'ID'            => hb_get_request( 'existing-customer-id' ),
            'title'         => hb_get_request( 'title' ),
            'first_name'    => hb_get_request( 'first_name' ),
            'last_name'     => hb_get_request( 'last_name' ),
            'address'       => hb_get_request( 'address' ),
            'city'          => hb_get_request( 'city' ),
            'state'         => hb_get_request( 'state' ),
            'postal_code'   => hb_get_request( 'postal_code' ),
            'country'       => hb_get_request( 'country' ),
            'phone'         => hb_get_request( 'phone' ),
            'email'         => hb_get_request( 'email' ),
            'fax'           => hb_get_request( 'fax' ),
        );
        $customer_id = hb_update_customer_info( $customer_info );

        // set transient for current customer in one hour
        set_transient( 'hb_current_customer_' . session_id(), $customer_id, HOUR_IN_SECONDS );
        return $this->_customer = $customer_id;
    }

    /**
     * Creates temp new booking if needed
     *
     * @return mixed|WP_Error
     * @throws Exception
     */
    function create_booking(){
        global $hb_settings;
        $customer_id = get_transient( 'hb_current_customer_' . session_id() );

        $transaction_object = hb_generate_transaction_object();

        if( ! $transaction_object ){
            hb_send_json( array(
                    'result'        => 'fail',
                    'message'       => __( 'Your cart is empty', 'tp-hotel-booking' )
                ) );
            throw new Exception( sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage</a>', 'tp-hotel-booking' ), home_url() ) );
        }
        // Insert or update the post data
        $booking_id = false;
        if( isset( $_SESSION['hb_cart'.HB_BLOG_ID]['booking_id'] ) )
            $booking_id = $_SESSION['hb_cart'.HB_BLOG_ID]['booking_id'];

        // Resume the unpaid order if its pending
        if ( $booking_id > 0 && ( $booking = HB_Booking::instance( $booking_id ) ) && $booking->post->ID && $booking->has_status( array( 'pending', 'failed' ) ) ) {
            $booking_data['ID'] = $booking_id;
            $booking_data['post_content'] = hb_get_request( 'addition_information' );
            $booking->set_booking_info( $booking_data );
        } else {
            $booking_id = hb_create_booking();
            $booking = HB_Booking::instance( $booking_id );
        }
        $tax                    = $transaction_object->tax;
        $price_including_tax    = $transaction_object->price_including_tax;
        $rooms                  = $transaction_object->rooms;

        // booking meta data
        $booking_info = array(
            '_hb_total_nights'              => $transaction_object->total_nights,
            '_hb_tax'                       => $tax,
            '_hb_price_including_tax'       => $price_including_tax ? 1 : 0,
            '_hb_sub_total'                 => $transaction_object->sub_total,
            '_hb_total'                     => $transaction_object->total,
            '_hb_advance_payment'           => $transaction_object->advance_payment,
            '_hb_advance_payment_setting'   => $hb_settings->get( 'advance_payment', 50 ),
            '_hb_currency'                  => $transaction_object->currency,
            '_hb_customer_id'               => $customer_id,
            '_hb_method'                    => $this->payment_method->slug,
            '_hb_method_title'              => $this->payment_method->title,
            '_hb_method_id'                 => $this->payment_method->method_id
        );

        if( ! empty( $transaction_object->coupon ) ){
            $booking_info['_hb_coupon'] = $transaction_object->coupon;
        }

        $booking_info = apply_filters( 'tp_hotel_booking_checkout_booking_info', $booking_info, $transaction_object );
        $booking->set_booking_info(
            $booking_info
        );

        $booking_id = $booking->update();
        if( $booking_id ){
            $prices = array();
            delete_post_meta( $booking_id, '_hb_room_id' );
            $tax = $hb_settings->get('tax');
            if( $rooms )
            {
                foreach( $rooms as $room_options ){
                    $num_of_rooms = $room_options['quantity'];
                    // insert multiple meta value
                    for( $i = 0; $i < $num_of_rooms; $i ++ ) {
                        add_post_meta( $booking_id, '_hb_room_id', $room_options['id'] );
                        // create post save item of order
                        $booking->save_room( $room_options, $booking_id );
                    }
                    // add_post_meta( $booking_id, '_hb_room_total', $room_options['sub_total'] );
                    $room = HB_Room::instance( $room_options['id'], $room_options);
                    $prices[ $room_options['id'] ] = $room_options['sub_total'];

                }
            }

            // add_post_meta( $booking_id, '_hb_room_price', $prices );
            $booking_params = apply_filters( 'hotel_booking_booking_params', $_SESSION['hb_cart'.HB_BLOG_ID]['products'] );
            add_post_meta( $booking_id, '_hb_booking_params', $booking_params );
        }
        do_action( 'hb_new_booking', $booking_id );
        return $booking_id;
    }

    /**
     * Process checkout
     *
     * @throws Exception
     */
    function process_checkout(){
        if( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ){
            return;
        }

        $payment_method = hb_get_user_payment_method( hb_get_request( 'hb-payment-method' ) );

        if( ! $payment_method ){
            throw new Exception( __( 'The payment method is not available', 'tp-hotel-booking' ) );
        }

        $customer_id = $this->create_customer();
        $this->payment_method = $payment_method;
        if( $customer_id ) {
            $booking_id = $this->create_booking();
            if( $booking_id ) {
                if (HB_Cart::instance()->needs_payment()) {
                    if( ! isset( $_SESSION['hb_cart'.HB_BLOG_ID]['booking_id']) )
                        $_SESSION['hb_cart'.HB_BLOG_ID]['booking_id'] = $booking_id;
                    $result = $payment_method->process_checkout( $booking_id, $customer_id );
                } else {
                    if ( empty($booking) ) {
                        $booking = HB_Booking::instance($booking_id);
                    }
                    // No payment was required for order
                    $booking->payment_complete();
                    HB_Cart::instance()->empty_cart();
                    $return_url = $booking->get_checkout_booking_received_url();
                    hb_send_json( array(
                        'result' 	=> 'success',
                        'redirect'  => apply_filters( 'hb_checkout_no_payment_needed_redirect', $return_url, $booking )
                    ) );
                }
            }else{
                die( __('can not create booking', 'tp-hotel-booking') );
            }
        }

        if ( ! empty( $result['result'] ) && $result['result'] == 'success' ) {

            $result = apply_filters( 'hb_payment_successful_result', $result );

            do_action( 'hb_place_order', $result );
            if ( hb_is_ajax() ) {
                hb_send_json( $result );
                exit;
            } else {
                wp_redirect( $result['redirect'] );
                exit;
            }

        }
    }

    /**
     * Get unique instance for this object
     *
     * @return HB_Checkout
     */
    static function instance(){
        if( empty( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
}