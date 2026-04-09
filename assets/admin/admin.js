/**
 * Appartali Booking – Admin JS
 */
(function($){
  'use strict';

  /* ── Status update (Confirm / Pending / Cancel) ── */
  $(document).on('click', '.appt-action-btn[data-status]', function(){
    var $btn      = $(this);
    var bookingId = $btn.data('id');
    var newStatus = $btn.data('status');
    var $row      = $btn.closest('.appt-booking-row');

    if (newStatus === 'cancelled'){
      if (!confirm('Cancel booking #' + bookingId + '? The guest will be notified by email.')) return;
    }
    if (newStatus === 'confirmed'){
      if (!confirm('Confirm booking #' + bookingId + '? The guest will be notified by email and these dates will be blocked.')) return;
    }

    $btn.prop('disabled', true).text('…');

    $.ajax({
      url:  apptAdmin.ajaxUrl,
      type: 'POST',
      data: {
        action:     'appt_update_booking_status',
        nonce:      apptAdmin.nonce,
        booking_id: bookingId,
        status:     newStatus,
      },
      success: function(res){
        if (res.success){
          apptToast(res.data.message, 'success');
          // Reload row after short delay
          setTimeout(function(){ location.reload(); }, 800);
        } else {
          apptToast((res.data && res.data.message) ? res.data.message : 'Update failed.', 'error');
          $btn.prop('disabled', false).text($btn.data('label') || newStatus);
        }
      },
      error: function(){
        apptToast('Network error. Please try again.', 'error');
        $btn.prop('disabled', false);
      }
    });
  });

  /* ── View Details modal ── */
  $(document).on('click', '.appt-btn-view-details', function(){
    var booking = $(this).data('booking');
    if (typeof booking === 'string'){
      try { booking = JSON.parse(booking); } catch(e){ return; }
    }

    var statusColors = { pending:'#f59e0b', confirmed:'#22c55e', cancelled:'#ef4444' };
    var color = statusColors[booking.status] || '#888';

    var html = [
      row('Booking ID',  '#' + booking.id),
      row('Property',    booking.apt),
      row('Guest Name',  booking.name),
      row('Email',       '<a href="mailto:'+booking.email+'">'+booking.email+'</a>'),
      row('Phone',       booking.phone || '—'),
      row('Check-In',    booking.checkin),
      row('Check-Out',   booking.checkout),
      row('Nights',      booking.nights),
      row('Status',      '<span style="background:'+color+'22;color:'+color+';border:1px solid '+color+'55;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;">'+booking.status+'</span>'),
      row('Special Req', booking.req || '—'),
      row('Booked On',   booking.date, false, true),
      '<div class="appt-detail-row appt-detail-total">'+
        '<span class="appt-detail-label">Total</span>'+
        '<span class="appt-detail-value">$' + parseFloat(booking.total).toFixed(2) + '</span>'+
      '</div>',
    ].join('');

    $('#apptBookingModalBody').html(html);
    $('#apptBookingModal').show();
  });

  function row(label, value, isMuted, isDate){
    return '<div class="appt-detail-row">'+
      '<span class="appt-detail-label">'+label+'</span>'+
      '<span class="appt-detail-value">'+(value||'—')+'</span>'+
    '</div>';
  }

  /* ── Close modal ── */
  $(document).on('click', '.appt-modal-close, .appt-modal-overlay', function(e){
    if ($(e.target).is('.appt-modal-overlay') || $(e.target).is('.appt-modal-close')){
      $('#apptBookingModal').hide();
    }
  });
  $(document).on('keydown', function(e){
    if (e.key === 'Escape') $('#apptBookingModal').hide();
  });

  /* ── Toast helper ── */
  function apptToast(msg, type){
    var $t = $('#apptToast');
    $t.text(msg).removeClass('error');
    if (type === 'error') $t.addClass('error');
    $t.stop(true).fadeIn(200).delay(3000).fadeOut(400);
  }

})(jQuery);
