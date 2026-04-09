<?php
/**
 * Plugin Name: Appartali Booking
 * Plugin URI:  https://appartali.com
 * Description: Complete apartment booking system — custom post types, shortcodes, booking management, email notifications.
 * Version:     1.0.0
 * Author:      Appartali
 * Text Domain: appartali-booking
 * License:     GPL-2.0+
 */

defined( 'ABSPATH' ) || exit;

define( 'APPT_VERSION', '1.0.0' );
define( 'APPT_PATH',    plugin_dir_path( __FILE__ ) );
define( 'APPT_URL',     plugin_dir_url( __FILE__ ) );

/* ── load includes ── */
foreach ( [ 'class-cpt', 'class-shortcode', 'class-booking', 'class-email', 'class-admin' ] as $f ) {
    require_once APPT_PATH . "includes/{$f}.php";
}

add_action( 'plugins_loaded', function () {
    new Appartali_CPT();
    new Appartali_Shortcode();
    new Appartali_Booking();
    new Appartali_Admin();
} );

/* ── frontend assets ── */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(  'appt-frontend', APPT_URL . 'assets/css/frontend.css',  [], APPT_VERSION );
    wp_enqueue_script( 'appt-frontend', APPT_URL . 'assets/js/frontend.js', ['jquery'], APPT_VERSION, true );
    wp_localize_script( 'appt-frontend', 'apptData', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'appt_nonce' ),
    ] );
} );

/* ── admin assets ── */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    wp_enqueue_style(  'appt-admin', APPT_URL . 'assets/admin/admin.css',  [], APPT_VERSION );
    wp_enqueue_script( 'appt-admin', APPT_URL . 'assets/admin/admin.js', ['jquery'], APPT_VERSION, true );
    wp_localize_script( 'appt-admin', 'apptAdmin', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'appt_admin_nonce' ),
    ] );
} );

/* ── single apartment template ── */
add_filter( 'template_include', function ( $tpl ) {
    if ( is_singular( 'apartment' ) ) {
        $custom = APPT_PATH . 'templates/single-apartment.php';
        if ( file_exists( $custom ) ) return $custom;
    }
    return $tpl;
} );

/* ── activation: create bookings table ── */
register_activation_hook( __FILE__, 'appt_activate' );
function appt_activate() {
    global $wpdb;
    $t   = $wpdb->prefix . 'appt_bookings';
    $col = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$t} (
        id            bigint(20)     NOT NULL AUTO_INCREMENT,
        apartment_id  bigint(20)     NOT NULL,
        guest_name    varchar(255)   NOT NULL,
        guest_email   varchar(255)   NOT NULL,
        guest_phone   varchar(50)    DEFAULT '',
        checkin_date  date           NOT NULL,
        checkout_date date           NOT NULL,
        nights        int(11)        DEFAULT 0,
        price_night   decimal(10,2)  DEFAULT 0.00,
        cleaning_fee  decimal(10,2)  DEFAULT 0.00,
        service_fee   decimal(10,2)  DEFAULT 0.00,
        total_price   decimal(10,2)  DEFAULT 0.00,
        special_req   text,
        status        varchar(30)    DEFAULT 'pending',
        created_at    datetime       DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$col};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    flush_rewrite_rules();
}

/* ── deactivation ── */
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
