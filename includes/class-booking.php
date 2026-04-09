<?php
defined( 'ABSPATH' ) || exit;

class Appartali_Booking {

    public function __construct() {
        add_action( 'wp_ajax_appt_check_availability',        [ $this, 'check_availability' ] );
        add_action( 'wp_ajax_nopriv_appt_check_availability', [ $this, 'check_availability' ] );
        add_action( 'wp_ajax_appt_submit_booking',            [ $this, 'submit_booking' ] );
        add_action( 'wp_ajax_nopriv_appt_submit_booking',     [ $this, 'submit_booking' ] );
        add_action( 'wp_ajax_appt_update_booking_status',     [ $this, 'update_booking_status' ] );
        add_action( 'wp_ajax_appt_get_blocked_dates',         [ $this, 'get_blocked_dates' ] );
        add_action( 'wp_ajax_nopriv_appt_get_blocked_dates',  [ $this, 'get_blocked_dates' ] );
    }

    /* ── Get all blocked (confirmed) date ranges for an apartment ── */
    public function get_blocked_dates() {
        check_ajax_referer( 'appt_nonce', 'nonce' );
        $apt_id = intval( $_POST['apartment_id'] ?? 0 );
        echo wp_json_encode( $this->get_confirmed_dates( $apt_id ) );
        wp_die();
    }

    public function get_confirmed_dates( $apt_id ) {
        global $wpdb;
        $t = $wpdb->prefix . 'appt_bookings';
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT checkin_date, checkout_date FROM {$t}
             WHERE apartment_id = %d AND status = 'confirmed'",
            $apt_id
        ) );
        $blocked = [];
        foreach ( $rows as $r ) {
            $start = new DateTime( $r->checkin_date );
            $end   = new DateTime( $r->checkout_date );
            $end->modify( '-1 day' ); // checkout day is free
            $interval = new DateInterval('P1D');
            $range    = new DatePeriod( $start, $interval, $end );
            foreach ( $range as $d ) {
                $blocked[] = $d->format('Y-m-d');
            }
            $blocked[] = $end->format('Y-m-d');
        }
        return array_unique( $blocked );
    }

    /* ── Check if a date range is available ── */
    public function check_availability() {
        check_ajax_referer( 'appt_nonce', 'nonce' );
        $apt_id  = intval( $_POST['apartment_id'] ?? 0 );
        $checkin = sanitize_text_field( $_POST['checkin']  ?? '' );
        $checkout= sanitize_text_field( $_POST['checkout'] ?? '' );

        if ( ! $apt_id || ! $checkin || ! $checkout ) {
            wp_send_json_error( [ 'message' => 'Invalid parameters.' ] );
        }

        $available = $this->is_available( $apt_id, $checkin, $checkout );
        if ( $available ) {
            $nights     = ( new DateTime($checkin) )->diff( new DateTime($checkout) )->days;
            $price      = (float) get_post_meta( $apt_id, '_price_per_night',  true );
            $clean_fee  = (float) get_post_meta( $apt_id, '_cleaning_fee',     true );
            $svc_fee    = (float) get_post_meta( $apt_id, '_service_fee',       true );
            $subtotal   = $price * $nights;
            $total      = $subtotal + $clean_fee + $svc_fee;
            wp_send_json_success( [
                'available'    => true,
                'nights'       => $nights,
                'price_night'  => $price,
                'cleaning_fee' => $clean_fee,
                'service_fee'  => $svc_fee,
                'subtotal'     => $subtotal,
                'total'        => $total,
            ] );
        } else {
            wp_send_json_success( [ 'available' => false, 'message' => 'These dates are not available.' ] );
        }
    }

    public function is_available( $apt_id, $checkin, $checkout ) {
        global $wpdb;
        $t = $wpdb->prefix . 'appt_bookings';
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$t}
             WHERE apartment_id = %d
               AND status       = 'confirmed'
               AND checkin_date  < %s
               AND checkout_date > %s",
            $apt_id, $checkout, $checkin
        ) );
        return intval($count) === 0;
    }

    /* ── Submit booking ── */
    public function submit_booking() {
        check_ajax_referer( 'appt_nonce', 'nonce' );

        $apt_id      = intval( $_POST['apartment_id'] ?? 0 );
        $checkin     = sanitize_text_field( $_POST['checkin']  ?? '' );
        $checkout    = sanitize_text_field( $_POST['checkout'] ?? '' );
        $guest_name  = sanitize_text_field( $_POST['guest_name']  ?? '' );
        $guest_email = sanitize_email(      $_POST['guest_email'] ?? '' );
        $guest_phone = sanitize_text_field( $_POST['guest_phone'] ?? '' );
        $special_req = sanitize_textarea_field( $_POST['special_req'] ?? '' );

        /* Validation */
        if ( ! $apt_id || ! $checkin || ! $checkout || ! $guest_name || ! $guest_email ) {
            wp_send_json_error( [ 'message' => 'Please fill all required fields.' ] );
        }
        if ( ! is_email($guest_email) ) {
            wp_send_json_error( [ 'message' => 'Invalid email address.' ] );
        }
        if ( strtotime($checkout) <= strtotime($checkin) ) {
            wp_send_json_error( [ 'message' => 'Check-out must be after check-in.' ] );
        }

        /* Re-check availability */
        if ( ! $this->is_available( $apt_id, $checkin, $checkout ) ) {
            wp_send_json_error( [ 'message' => 'Sorry, these dates are no longer available.' ] );
        }

        /* Price calculation */
        $nights    = ( new DateTime($checkin) )->diff( new DateTime($checkout) )->days;
        $price     = (float) get_post_meta( $apt_id, '_price_per_night', true );
        $clean_fee = (float) get_post_meta( $apt_id, '_cleaning_fee',    true );
        $svc_fee   = (float) get_post_meta( $apt_id, '_service_fee',      true );
        $total     = ($price * $nights) + $clean_fee + $svc_fee;

        /* Insert booking */
        global $wpdb;
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'appt_bookings',
            [
                'apartment_id'  => $apt_id,
                'guest_name'    => $guest_name,
                'guest_email'   => $guest_email,
                'guest_phone'   => $guest_phone,
                'checkin_date'  => $checkin,
                'checkout_date' => $checkout,
                'nights'        => $nights,
                'price_night'   => $price,
                'cleaning_fee'  => $clean_fee,
                'service_fee'   => $svc_fee,
                'total_price'   => $total,
                'special_req'   => $special_req,
                'status'        => 'pending',
            ],
            [ '%d','%s','%s','%s','%s','%s','%d','%f','%f','%f','%f','%s','%s' ]
        );

        if ( ! $inserted ) {
            wp_send_json_error( [ 'message' => 'Booking could not be saved. Please try again.' ] );
        }

        $booking_id = $wpdb->insert_id;
        $apt_title  = get_the_title( $apt_id );

        /* Send emails */
        $email = new Appartali_Email();
        $email->send_guest_pending( $guest_email, $guest_name, $apt_title, $checkin, $checkout, $nights, $total, $booking_id );
        $email->send_admin_new_booking( $guest_name, $guest_email, $guest_phone, $apt_title, $checkin, $checkout, $nights, $total, $booking_id, $special_req );

        wp_send_json_success( [
            'message'    => 'Booking submitted! You will receive a confirmation email shortly.',
            'booking_id' => $booking_id,
        ] );
    }

    /* ── Admin: update booking status ── */
    public function update_booking_status() {
        check_ajax_referer( 'appt_admin_nonce', 'nonce' );
        if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');

        $booking_id = intval( $_POST['booking_id'] ?? 0 );
        $status     = sanitize_text_field( $_POST['status'] ?? '' );

        $allowed = [ 'pending', 'confirmed', 'cancelled' ];
        if ( ! in_array($status, $allowed) ) {
            wp_send_json_error( [ 'message' => 'Invalid status.' ] );
        }

        global $wpdb;
        $t = $wpdb->prefix . 'appt_bookings';
        $booking = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $booking_id) );
        if ( ! $booking ) {
            wp_send_json_error( [ 'message' => 'Booking not found.' ] );
        }

        $wpdb->update( $t, ['status' => $status], ['id' => $booking_id], ['%s'], ['%d'] );

        /* Notify guest on status change */
        $email = new Appartali_Email();
        if ( $status === 'confirmed' ) {
            $email->send_guest_confirmed( $booking->guest_email, $booking->guest_name, get_the_title($booking->apartment_id), $booking->checkin_date, $booking->checkout_date, $booking->total_price, $booking_id );
        } elseif ( $status === 'cancelled' ) {
            $email->send_guest_cancelled( $booking->guest_email, $booking->guest_name, get_the_title($booking->apartment_id), $booking_id );
        }

        wp_send_json_success( [ 'message' => 'Status updated to ' . $status ] );
    }
}
