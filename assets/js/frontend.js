/**
 * Appartali Booking – Frontend JS
 */
(function($){
  'use strict';

  /* ══════════════════════════════════════
     EXPLORE ROOMS – Category Tab Filter
  ══════════════════════════════════════ */
  $(document).on('click', '#apptCategoryTabs .appt-tab-btn', function(){
    var $this     = $(this);
    var category  = $this.data('category');
    var $grid     = $('#apptRoomsGrid');

    $('#apptCategoryTabs .appt-tab-btn').removeClass('active');
    $this.addClass('active');

    $grid.css('opacity', '.4').addClass('loading');

    $.ajax({
      url:  apptData.ajaxUrl,
      type: 'POST',
      data: {
        action:   'appt_filter_rooms',
        nonce:    apptData.nonce,
        category: category,
        limit:    8,
      },
      success: function(res){
        $grid.html(res).css('opacity','1').removeClass('loading');
      },
      error: function(){
        $grid.css('opacity','1').removeClass('loading');
      }
    });
  });

  /* ══════════════════════════════════════
     SINGLE APARTMENT – Booking Widget
  ══════════════════════════════════════ */

  var widget      = $('#apptBookingWidget');
  if (!widget.length) return;

  var apartmentId = widget.data('apartment-id');
  var priceNight  = parseFloat(widget.data('price'))        || 0;
  var cleanFee    = parseFloat(widget.data('cleaning-fee')) || 0;
  var svcFee      = parseFloat(widget.data('service-fee'))  || 0;

  var $checkin    = $('#apptCheckin');
  var $checkout   = $('#apptCheckout');
  var $availMsg   = $('#apptAvailMsg');
  var $breakdown  = $('#apptBreakdown');
  var $reserveBtn = $('#apptReserveBtn');

  var checkTimer;
  var lastCheckin, lastCheckout;
  var currentBookingData = {};

  /* ── Load blocked dates and disable them ── */
  $.ajax({
    url:  apptData.ajaxUrl,
    type: 'POST',
    data: { action: 'appt_get_blocked_dates', nonce: apptData.nonce, apartment_id: apartmentId },
    success: function(res){
      try {
        var blocked = JSON.parse(res);
        if (blocked && blocked.length) {
          apptSetBlockedDates(blocked);
        }
      } catch(e){}
    }
  });

  function apptSetBlockedDates(blocked){
    /* Native date inputs don't support multi-date blocking,
       so we validate on change and show a warning */
    window.apptBlockedDates = blocked;
  }

  function isDateBlocked(dateStr){
    return window.apptBlockedDates && window.apptBlockedDates.indexOf(dateStr) !== -1;
  }

  function hasBlockedDatesInRange(checkin, checkout){
    if (!window.apptBlockedDates || !window.apptBlockedDates.length) return false;
    var start = new Date(checkin);
    var end   = new Date(checkout);
    var cur   = new Date(start);
    while (cur < end){
      var d = cur.toISOString().split('T')[0];
      if (isDateBlocked(d)) return true;
      cur.setDate(cur.getDate() + 1);
    }
    return false;
  }

  /* ── Date change handler ── */
  $checkin.on('change', function(){
    var val = $(this).val();
    // Ensure checkout is after checkin
    if ($checkout.val() && $checkout.val() <= val){
      $checkout.val('');
      hideBreakdown();
    }
    $checkout.attr('min', val ? addDays(val, 1) : '');
    triggerAvailabilityCheck();
  });

  $checkout.on('change', function(){
    triggerAvailabilityCheck();
  });

  function addDays(dateStr, n){
    var d = new Date(dateStr);
    d.setDate(d.getDate() + n);
    return d.toISOString().split('T')[0];
  }

  function triggerAvailabilityCheck(){
    clearTimeout(checkTimer);
    var ci = $checkin.val();
    var co = $checkout.val();
    if (!ci || !co) { hideBreakdown(); return; }
    if (ci === lastCheckin && co === lastCheckout) return;
    checkTimer = setTimeout(function(){ checkAvailability(ci, co); }, 350);
  }

  function checkAvailability(checkin, checkout){
    // First check against local blocked dates
    if (hasBlockedDatesInRange(checkin, checkout)){
      showUnavailable('These dates overlap with an existing confirmed booking.');
      hideBreakdown();
      return;
    }

    $availMsg.removeClass('available unavailable').html('<span style="color:#aaa">Checking availability…</span>').show();
    $reserveBtn.prop('disabled', true);

    $.ajax({
      url:  apptData.ajaxUrl,
      type: 'POST',
      data: {
        action:       'appt_check_availability',
        nonce:        apptData.nonce,
        apartment_id: apartmentId,
        checkin:      checkin,
        checkout:     checkout,
      },
      success: function(res){
        lastCheckin  = checkin;
        lastCheckout = checkout;
        if (res.success && res.data.available){
          var d = res.data;
          showAvailable(d.nights);
          updateBreakdown(d.nights, d.price_night, d.cleaning_fee, d.service_fee, d.total);
          currentBookingData = {
            nights:      d.nights,
            price_night: d.price_night,
            clean_fee:   d.cleaning_fee,
            svc_fee:     d.service_fee,
            total:       d.total,
          };
          $reserveBtn.prop('disabled', false);
        } else {
          var msg = (res.data && res.data.message) ? res.data.message : 'These dates are not available.';
          showUnavailable(msg);
          hideBreakdown();
        }
      },
      error: function(){
        $availMsg.hide();
        $reserveBtn.prop('disabled', false);
      }
    });
  }

  function showAvailable(nights){
    $availMsg.removeClass('unavailable').addClass('available')
      .html('✓ Available for ' + nights + ' night' + (nights===1?'':'s') + '!').show();
  }
  function showUnavailable(msg){
    $availMsg.removeClass('available').addClass('unavailable').html('✗ ' + msg).show();
    $reserveBtn.prop('disabled', true);
  }

  function updateBreakdown(nights, price, clean, svc, total){
    $('#apptNightsLabel').text('$' + fmt(price) + ' × ' + nights + ' night' + (nights===1?'':'s'));
    $('#apptSubtotal').text('$' + fmt(price * nights));
    $('#apptTotal').text('$' + fmt(total));
    $breakdown.slideDown(200);
  }
  function hideBreakdown(){ $breakdown.slideUp(150); $availMsg.hide(); }

  function fmt(n){ return parseFloat(n).toFixed(2); }

  /* ── Reserve Button → Open Popup ── */
  $reserveBtn.on('click', function(){
    var ci = $checkin.val();
    var co = $checkout.val();
    if (!ci || !co){
      showUnavailable('Please select check-in and check-out dates.');
      return;
    }
    if (!currentBookingData.total){
      showUnavailable('Please select valid dates first.');
      return;
    }
    openBookingPopup(ci, co);
  });

  function openBookingPopup(ci, co){
    $('#apptPopupCheckin').text(formatDate(ci));
    $('#apptPopupCheckout').text(formatDate(co));
    $('#apptPopupNights').text(currentBookingData.nights + ' night' + (currentBookingData.nights===1?'':'s'));
    $('#apptPopupTotal').text('$' + fmt(currentBookingData.total));
    $('#apptBookingModal').fadeIn(200);
    $('body').css('overflow','hidden');
    setTimeout(function(){ $('#apptGuestName').focus(); }, 250);
  }

  function formatDate(d){
    if (!d) return '—';
    var parts = d.split('-');
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[parseInt(parts[1])-1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
  }

  /* ── Close Popup ── */
  $(document).on('click', '#apptPopupClose, .appt-popup-overlay', function(e){
    if ($(e.target).is('.appt-popup-overlay') || $(e.target).is('#apptPopupClose')){
      $('#apptBookingModal').fadeOut(200);
      $('body').css('overflow','');
    }
  });
  $(document).on('keydown', function(e){
    if (e.key === 'Escape') {
      $('#apptBookingModal, #apptSuccessModal').fadeOut(200);
      $('body').css('overflow','');
    }
  });

  /* ── Submit Booking ── */
  $('#apptSubmitBooking').on('click', function(){
    var $btn       = $(this);
    var name       = $.trim($('#apptGuestName').val());
    var email      = $.trim($('#apptGuestEmail').val());
    var phone      = $.trim($('#apptGuestPhone').val());
    var specialReq = $.trim($('#apptSpecialReq').val());
    var ci         = $checkin.val();
    var co         = $checkout.val();

    /* Client-side validation */
    var err = '';
    if (!name)  err = 'Please enter your full name.';
    else if (!email) err = 'Please enter your email address.';
    else if (!isValidEmail(email)) err = 'Please enter a valid email address.';

    if (err){
      showPopupError(err);
      return;
    }

    /* Disable button + show loader */
    $btn.prop('disabled', true).find('.appt-btn-text').hide();
    $btn.find('.appt-btn-loader').show();
    $('#apptPopupError').hide();

    $.ajax({
      url:  apptData.ajaxUrl,
      type: 'POST',
      data: {
        action:       'appt_submit_booking',
        nonce:        apptData.nonce,
        apartment_id: apartmentId,
        checkin:      ci,
        checkout:     co,
        guest_name:   name,
        guest_email:  email,
        guest_phone:  phone,
        special_req:  specialReq,
      },
      success: function(res){
        $btn.prop('disabled', false).find('.appt-btn-text').show();
        $btn.find('.appt-btn-loader').hide();

        if (res.success){
          $('#apptBookingModal').fadeOut(200);
          $('#apptSuccessEmail').text(email);
          $('#apptSuccessModal').fadeIn(200);
          // Reset form
          $('#apptGuestName, #apptGuestEmail, #apptGuestPhone, #apptSpecialReq').val('');
          $checkin.val('');
          $checkout.val('');
          hideBreakdown();
          currentBookingData = {};
          $availMsg.hide();
          $reserveBtn.prop('disabled', false);
          // Update local blocked dates
          if (window.apptBlockedDates){
            var d = new Date(ci);
            var end = new Date(co);
            while (d < end){
              window.apptBlockedDates.push(d.toISOString().split('T')[0]);
              d.setDate(d.getDate()+1);
            }
          }
        } else {
          showPopupError((res.data && res.data.message) ? res.data.message : 'An error occurred. Please try again.');
        }
      },
      error: function(){
        $btn.prop('disabled', false).find('.appt-btn-text').show();
        $btn.find('.appt-btn-loader').hide();
        showPopupError('Network error. Please try again.');
      }
    });
  });

  function showPopupError(msg){
    $('#apptPopupError').text(msg).show();
    // scroll to error
    $('#apptPopupError')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function isValidEmail(e){
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
  }

})(jQuery);
