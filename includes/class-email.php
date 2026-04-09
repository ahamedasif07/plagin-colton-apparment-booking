<?php
defined( 'ABSPATH' ) || exit;

class Appartali_Email {

    private function site_name() { return get_bloginfo('name') ?: 'Appartali'; }
    private function admin_email() { return get_option('admin_email'); }

    private function wrap( $title, $body ) {
        $site = $this->site_name();
        return "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
  body{margin:0;padding:0;background:#0a0a0a;font-family:Arial,sans-serif;color:#e5e5e5;}
  .wrap{max-width:600px;margin:30px auto;background:#111;border-radius:12px;overflow:hidden;border:1px solid #2a2a2a;}
  .header{background:#1a1a1a;padding:24px 32px;border-bottom:1px solid #2a2a2a;}
  .header h1{margin:0;color:#f5c842;font-size:22px;font-weight:700;letter-spacing:.5px;}
  .header p{margin:4px 0 0;color:#999;font-size:13px;}
  .body{padding:32px;}
  .body h2{color:#fff;font-size:18px;margin:0 0 16px;}
  .info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #222;}
  .info-row:last-child{border-bottom:none;}
  .info-label{color:#999;font-size:13px;}
  .info-value{color:#fff;font-size:13px;font-weight:600;text-align:right;}
  .badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;}
  .badge-pending{background:#f5c84222;color:#f5c842;border:1px solid #f5c84255;}
  .badge-confirmed{background:#22c55e22;color:#22c55e;border:1px solid #22c55e55;}
  .badge-cancelled{background:#ef444422;color:#ef4444;border:1px solid #ef444455;}
  .total-row{background:#1a1a1a;border-radius:8px;padding:14px 16px;margin-top:16px;display:flex;justify-content:space-between;}
  .total-label{color:#aaa;font-size:14px;}
  .total-value{color:#f5c842;font-size:18px;font-weight:700;}
  .footer{background:#0d0d0d;padding:16px 32px;text-align:center;color:#555;font-size:12px;border-top:1px solid #2a2a2a;}
  .btn{display:inline-block;padding:12px 28px;background:#f5c842;color:#111;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;margin-top:20px;}
</style>
</head>
<body>
<div class='wrap'>
  <div class='header'>
    <h1>🏠 {$site}</h1>
    <p>{$title}</p>
  </div>
  <div class='body'>{$body}</div>
  <div class='footer'>© " . date('Y') . " {$site} · All Rights Reserved</div>
</div>
</body>
</html>";
    }

    /* ── Guest: booking received (pending) ── */
    public function send_guest_pending( $email, $name, $apt, $checkin, $checkout, $nights, $total, $booking_id ) {
        $subject = "Booking Request Received – #{$booking_id}";
        $body = "
<h2>Hi {$name}! 👋</h2>
<p>Thank you for your booking request. It is currently <span class='badge badge-pending'>Pending</span> review by our host.</p>
<br>
<div class='info-row'><span class='info-label'>Property</span><span class='info-value'>{$apt}</span></div>
<div class='info-row'><span class='info-label'>Check-In</span><span class='info-value'>{$checkin}</span></div>
<div class='info-row'><span class='info-label'>Check-Out</span><span class='info-value'>{$checkout}</span></div>
<div class='info-row'><span class='info-label'>Nights</span><span class='info-value'>{$nights}</span></div>
<div class='info-row'><span class='info-label'>Booking ID</span><span class='info-value'>#{$booking_id}</span></div>
<div class='total-row'><span class='total-label'>Total (estimated)</span><span class='total-value'>\${$total}</span></div>
<p style='margin-top:20px;color:#aaa;font-size:13px;'>You will receive another email once your booking is confirmed. If you have questions, reply to this email.</p>";
        $this->send( $email, $subject, $this->wrap("Booking Request Received", $body) );
    }

    /* ── Guest: booking confirmed ── */
    public function send_guest_confirmed( $email, $name, $apt, $checkin, $checkout, $total, $booking_id ) {
        $subject = "✅ Booking Confirmed – #{$booking_id}";
        $body = "
<h2>Great news, {$name}! 🎉</h2>
<p>Your booking has been <span class='badge badge-confirmed'>Confirmed</span>!</p>
<br>
<div class='info-row'><span class='info-label'>Property</span><span class='info-value'>{$apt}</span></div>
<div class='info-row'><span class='info-label'>Check-In</span><span class='info-value'>{$checkin}</span></div>
<div class='info-row'><span class='info-label'>Check-Out</span><span class='info-value'>{$checkout}</span></div>
<div class='info-row'><span class='info-label'>Booking ID</span><span class='info-value'>#{$booking_id}</span></div>
<div class='total-row'><span class='total-label'>Total Paid</span><span class='total-value'>\${$total}</span></div>
<p style='margin-top:20px;color:#aaa;font-size:13px;'>We look forward to welcoming you. Please feel free to contact us with any questions.</p>";
        $this->send( $email, $subject, $this->wrap("Booking Confirmed", $body) );
    }

    /* ── Guest: booking cancelled ── */
    public function send_guest_cancelled( $email, $name, $apt, $booking_id ) {
        $subject = "Booking Cancelled – #{$booking_id}";
        $body = "
<h2>Hi {$name},</h2>
<p>Your booking <strong>#{$booking_id}</strong> for <strong>{$apt}</strong> has been <span class='badge badge-cancelled'>Cancelled</span>.</p>
<p style='color:#aaa;font-size:13px;'>If you believe this is a mistake or need assistance, please contact us.</p>";
        $this->send( $email, $subject, $this->wrap("Booking Cancelled", $body) );
    }

    /* ── Admin: new booking notification ── */
    public function send_admin_new_booking( $guest_name, $guest_email, $guest_phone, $apt, $checkin, $checkout, $nights, $total, $booking_id, $special_req ) {
        $subject = "🏠 New Booking Request #{$booking_id} – {$apt}";
        $admin_url = admin_url('admin.php?page=appt-bookings');
        $body = "
<h2>New Booking Request</h2>
<p>A new booking request requires your attention.</p>
<br>
<div class='info-row'><span class='info-label'>Booking ID</span><span class='info-value'>#{$booking_id}</span></div>
<div class='info-row'><span class='info-label'>Property</span><span class='info-value'>{$apt}</span></div>
<div class='info-row'><span class='info-label'>Guest Name</span><span class='info-value'>{$guest_name}</span></div>
<div class='info-row'><span class='info-label'>Guest Email</span><span class='info-value'>{$guest_email}</span></div>
<div class='info-row'><span class='info-label'>Guest Phone</span><span class='info-value'>{$guest_phone}</span></div>
<div class='info-row'><span class='info-label'>Check-In</span><span class='info-value'>{$checkin}</span></div>
<div class='info-row'><span class='info-label'>Check-Out</span><span class='info-value'>{$checkout}</span></div>
<div class='info-row'><span class='info-label'>Nights</span><span class='info-value'>{$nights}</span></div>
" . ($special_req ? "<div class='info-row'><span class='info-label'>Special Requests</span><span class='info-value'>{$special_req}</span></div>" : "") . "
<div class='total-row'><span class='total-label'>Total</span><span class='total-value'>\${$total}</span></div>
<a href='{$admin_url}' class='btn'>Manage Bookings →</a>";
        $this->send( $this->admin_email(), $subject, $this->wrap("New Booking Request", $body) );
    }

    /* ── Internal send ── */
    private function send( $to, $subject, $html ) {
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        wp_mail( $to, $subject, $html, $headers );
    }
}
