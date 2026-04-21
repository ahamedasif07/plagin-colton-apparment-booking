<?php

/**
 * Single Apartment Template
 * Belongs to: Appartali Booking Plugin
 */
defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    $id          = get_the_ID();
    $title       = get_the_title();
    $content     = get_the_content();
    $thumb       = get_the_post_thumbnail_url($id, 'full');

    // Meta
    $price       = (float) get_post_meta($id, '_price_per_night', true);
    $location    = get_post_meta($id, '_location',       true);
    $room_id_m   = get_post_meta($id, '_room_id',        true);
    $rating      = get_post_meta($id, '_rating',         true);
    $max_guests  = get_post_meta($id, '_max_guests',     true);
    $clean_fee   = (float) get_post_meta($id, '_cleaning_fee',  true);
    $svc_fee     = (float) get_post_meta($id, '_service_fee',   true);
    $room_type   = get_post_meta($id, '_room_type',      true);
    $amenities   = get_post_meta($id, '_amenities',      true) ?: [];

    // Gallery
    $gallery_raw = get_post_meta($id, '_gallery_images', true);
    $gallery_ids = $gallery_raw ? array_filter(explode(',', $gallery_raw)) : [];
    $gallery_urls = [];
    foreach ($gallery_ids as $gid) {
        $u = wp_get_attachment_image_url(intval($gid), 'large');
        if ($u) $gallery_urls[] = $u;
    }
    if ($thumb && ! in_array($thumb, $gallery_urls)) {
        array_unshift($gallery_urls, $thumb);
    }
    $main_img    = $gallery_urls[0] ?? '';
    $thumb_imgs  = array_slice($gallery_urls, 1, 3);

    // Host
    $host_name    = get_post_meta($id, '_host_name',          true);
    $host_img     = get_post_meta($id, '_host_image',         true);
    $host_reviews = get_post_meta($id, '_host_reviews',       true);
    $host_ratings = get_post_meta($id, '_host_ratings',       true);
    $host_years   = get_post_meta($id, '_host_years',         true);
    $host_super   = get_post_meta($id, '_host_superhost',     true);
    $host_rrate   = get_post_meta($id, '_host_response_rate', true);
    $host_rtime   = get_post_meta($id, '_host_response_time', true);
    $host_work    = get_post_meta($id, '_host_work',          true);
    $host_lang    = get_post_meta($id, '_host_language',      true);
    $host_lives   = get_post_meta($id, '_host_lives',         true);
    $host_cohost  = get_post_meta($id, '_host_cohost_name',   true);

    $amenity_labels = [
        'lock_door'  => 'Lock on bedroom door',
        'wifi'       => 'Free WiFi',
        'tv'         => 'TV',
        'luggage'    => 'Luggage dropoff allowed',
        'fridge'     => 'Refrigerator',
        'kitchen'    => 'Kitchen',
        'workspace'  => 'Dedicated workspace',
        'washer'     => 'Washer',
        'hair_dryer' => 'Hair dryer',
        'iron'       => 'Iron machine',
        'ac'         => 'Air conditioning',
        'parking'    => 'Free parking',
        'pool'       => 'Swimming pool',
        'gym'        => 'Gym access',
    ];
    $amenity_icons = [
        'lock_door'  => '🔒',
        'wifi' => '📶',
        'tv' => '📺',
        'luggage' => '🧳',
        'fridge'     => '🧊',
        'kitchen' => '🍳',
        'workspace' => '💼',
        'washer' => '🫧',
        'hair_dryer' => '💨',
        'iron' => '👔',
        'ac' => '❄️',
        'parking' => '🚗',
        'pool'       => '🏊',
        'gym' => '🏋️',
    ];
    $room_type_labels = [
        'apartment' => 'Apartment',
        'studio' => 'Studio',
        'villa' => 'Villa',
        'house' => 'House',
        'cottage' => 'Cottage',
    ];
    $room_type_label = $room_type_labels[$room_type] ?? 'Room';

    // Stars helper
    $stars_full = floor((float)$rating);
    $stars_html = str_repeat('<span class="appt-star-filled">★</span>', $stars_full)
        . str_repeat('<span class="appt-star-empty">★</span>', 5 - $stars_full);
?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>

    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php wp_head(); ?>
    </head>

    <body <?php body_class('appt-single-body'); ?>>

        <?php do_action('wp_body_open'); ?>

        <div class="appt-single-page">

            <div class="appt-single-inner">

                <!-- Breadcrumb -->
                <div class="appt-breadcrumb">
                    <a href="<?= esc_url(home_url('/')) ?>">← <?= esc_html($title) ?></a>
                    <span class="appt-breadcrumb-sub"><?= esc_html($location) ?></span>
                </div>

                <!-- ── MAIN LAYOUT ── -->
                <div class="appt-single-layout">

                    <!-- LEFT: Images + Info -->
                    <div class="appt-single-left">

                        <!-- Main Image (unchanged) -->
                        <div class="appt-main-image-wrap">
                            <?php if ($main_img): ?>
                                <img src="<?= esc_url($main_img) ?>" alt="<?= esc_attr($title) ?>" class="appt-main-img"
                                    id="apptMainImg">
                            <?php else: ?>
                                <div class="appt-no-img-placeholder">No Image Available</div>
                            <?php endif; ?>
                        </div>

                        <!-- Thumbnail Gallery — same markup, only overflow:hidden → scroll added via wrapper -->
                        <?php if (! empty($thumb_imgs)): ?>
                            <div style="position:relative; margin-bottom:24px;">

                                <!-- Prev arrow -->
                                <button onclick="apptThumbScroll(-1)" style="position:absolute;left:-14px;top:50%;transform:translateY(-50%);z-index:9;background:rgba(0,0,0,0.6);border:1px solid rgba(255,255,255,0.15);color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:17px;line-height:1;padding:0;display:flex;align-items:center;justify-content:center;" aria-label="Prev">&#8249;</button>

                                <!-- The existing .appt-thumb-gallery div — flex-wrap changed to nowrap + overflow hidden via inline -->
                                <div class="appt-thumb-gallery" id="apptThumbGallery" style="flex-wrap:nowrap !important; overflow:hidden; scroll-behavior:smooth;">
                                    <?php foreach ($gallery_urls as $i => $u): ?>
                                        <img src="<?= esc_url($u) ?>" alt="Gallery"
                                             class="appt-thumb <?= $i === 0 ? 'active' : '' ?>"
                                             data-index="<?= $i ?>"
                                             onclick="apptGoTo(<?= $i ?>);">
                                    <?php endforeach; ?>
                                </div>

                                <!-- Next arrow -->
                                <button onclick="apptThumbScroll(1)" style="position:absolute;right:-14px;top:50%;transform:translateY(-50%);z-index:9;background:rgba(0,0,0,0.6);border:1px solid rgba(255,255,255,0.15);color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:17px;line-height:1;padding:0;display:flex;align-items:center;justify-content:center;" aria-label="Next">&#8250;</button>

                            </div>

                            <script>
                            (function(){
                                var urls    = <?= json_encode(array_values($gallery_urls)) ?>;
                                var total   = urls.length;
                                var current = 0;

                                /* Change main image */
                                window.apptGoTo = function(index) {
                                    if (index < 0) index = total - 1;
                                    if (index >= total) index = 0;
                                    current = index;

                                    var img = document.getElementById('apptMainImg');
                                    if (img) {
                                        img.style.opacity = '0.4';
                                        setTimeout(function(){
                                            img.src = urls[current];
                                            img.style.transition = 'opacity 0.25s';
                                            img.style.opacity = '1';
                                        }, 120);
                                    }

                                    /* active thumb */
                                    document.querySelectorAll('#apptThumbGallery .appt-thumb').forEach(function(t){
                                        t.classList.remove('active');
                                    });
                                    var active = document.querySelector('#apptThumbGallery .appt-thumb[data-index="'+current+'"]');
                                    if (active) {
                                        active.classList.add('active');
                                        var g = document.getElementById('apptThumbGallery');
                                        g.scrollLeft = active.offsetLeft - g.offsetWidth/2 + active.offsetWidth/2;
                                    }
                                };

                                /* Scroll thumbnail strip left / right */
                                window.apptThumbScroll = function(dir) {
                                    var g = document.getElementById('apptThumbGallery');
                                    g.scrollLeft += dir * 220;
                                };

                                /* Swipe on main image */
                                var wrap = document.querySelector('.appt-main-image-wrap');
                                if (wrap) {
                                    var sx = 0;
                                    wrap.addEventListener('touchstart', function(e){ sx = e.changedTouches[0].screenX; }, {passive:true});
                                    wrap.addEventListener('touchend',   function(e){
                                        var dx = sx - e.changedTouches[0].screenX;
                                        if (Math.abs(dx) > 40) apptGoTo(dx > 0 ? current+1 : current-1);
                                    }, {passive:true});
                                }
                            })();
                            </script>
                        <?php endif; ?>

                        <!-- Room Title -->
                        <div class="appt-room-title-block">
                            <h1 class="appt-room-main-title"><?= esc_html($title) ?></h1>
                            <?php if ($room_id_m): ?>
                                <span class="appt-room-id-label">Room id: <?= esc_html($room_id_m) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Guest Favorite Badge -->
                        <?php if ($rating >= 4.5): ?>
                            <div class="appt-guest-fav-row">
                                <div class="appt-guest-fav-badge">
                                    <div class="appt-gf-icon">🏅</div>
                                    <div class="appt-gf-text">
                                        <span class="appt-gf-title">Guest<br>favorite</span>
                                        <span class="appt-gf-sub">One of the most loved homes<br>on Appartali, according to
                                            guests</span>
                                    </div>
                                    <div class="appt-gf-rating">
                                        <span class="appt-gf-num"><?= esc_html($rating) ?></span>
                                        <?= $stars_html ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Host Info -->
                        <?php if ($host_name): ?>
                            <div class="appt-host-intro">
                                <?php if ($host_img): ?>
                                    <img src="<?= esc_url($host_img) ?>" alt="<?= esc_attr($host_name) ?>" class="appt-host-avatar">
                                <?php endif; ?>
                                <div>
                                    <div class="appt-host-stay-label">Stay with <?= esc_html($host_name) ?></div>
                                    <?php if ($host_super === '1'): ?>
                                        <span class="appt-superhost-badge">⭐ Superhost</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- About this place -->
                        <?php if ($content): ?>
                            <div class="appt-about-section">
                                <h3>About this place</h3>
                                <div class="appt-about-text"><?= wp_kses_post($content) ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Room Details -->
                        <div class="appt-room-details-row">
                            <h3>About room</h3>
                            <div class="appt-room-detail-grid">
                                <div class="appt-rd-item"><span class="appt-rd-icon">🏠</span><span>Room in a
                                        <?= esc_html($room_type_label) ?></span></div>
                                <?php if ($max_guests): ?>
                                    <div class="appt-rd-item"><span class="appt-rd-icon">👥</span><span>Up to
                                            <?= esc_html($max_guests) ?> guests</span></div>
                                <?php endif; ?>
                                <div class="appt-rd-item"><span class="appt-rd-icon">🔑</span><span>Your own room in a home,
                                        plus access to shared spaces</span></div>
                                <?php if ($host_super === '1'): ?>
                                    <div class="appt-rd-item"><span
                                            class="appt-rd-icon">⭐</span><span><?= esc_html($host_name) ?> is a superhost – an
                                            experienced, highly rated host</span></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <?php if (! empty($amenities)): ?>
                            <div class="appt-amenities-section">
                                <h3>What this place offers</h3>
                                <div class="appt-amenities-grid">
                                    <?php foreach ($amenities as $a):
                                        $icon  = $amenity_icons[$a]  ?? '✓';
                                        $label = $amenity_labels[$a] ?? $a;
                                    ?>
                                        <div class="appt-amenity-item">
                                            <span class="appt-amenity-icon"><?= $icon ?></span>
                                            <span><?= esc_html($label) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Meet your host -->
                        <?php if ($host_name): ?>
                            <div class="appt-meet-host-section">
                                <h3>Meet your host</h3>
                                <div class="appt-meet-host-card">
                                    <div class="appt-mh-avatar-col">
                                        <?php if ($host_img): ?>
                                            <img src="<?= esc_url($host_img) ?>" alt="<?= esc_attr($host_name) ?>"
                                                class="appt-mh-avatar">
                                        <?php endif; ?>
                                        <div class="appt-mh-name"><?= esc_html($host_name) ?></div>
                                        <?php if ($host_super === '1'): ?>
                                            <span class="appt-superhost-badge">⭐ Superhost</span>
                                        <?php endif; ?>
                                        <div class="appt-mh-stats">
                                            <?php if ($host_reviews): ?><span><?= esc_html($host_reviews) ?><small>Reviews</small></span><?php endif; ?>
                                            <?php if ($host_ratings): ?><span><?= esc_html($host_ratings) ?><small>Ratings</small></span><?php endif; ?>
                                            <?php if ($host_years):   ?><span><?= esc_html($host_years) ?><small>Yrs
                                                        hosting</small></span><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="appt-mh-details-col">
                                        <?php if ($host_super === '1'): ?>
                                            <div class="appt-mh-super-desc">
                                                <strong><?= esc_html($host_name) ?> is a Superhost</strong><br>
                                                <span>Superhosts are experienced, highly rated hosts who are committed to providing
                                                    great stays for guests.</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($host_cohost): ?>
                                            <div class="appt-mh-detail-row"><span class="appt-mh-dl">👤
                                                    Co-Host:</span><span><?= esc_html($host_cohost) ?></span></div>
                                        <?php endif; ?>
                                        <div class="appt-mh-detail-title">Host Details</div>
                                        <?php if ($host_rrate): ?>
                                            <div class="appt-mh-detail-row"><span class="appt-mh-dl">📊 Response
                                                    Rate:</span><span><?= esc_html($host_rrate) ?>%</span></div>
                                        <?php endif; ?>
                                        <?php if ($host_rtime): ?>
                                            <div class="appt-mh-detail-row"><span class="appt-mh-dl">⏱ Response
                                                    Time:</span><span><?= esc_html($host_rtime) ?></span></div>
                                        <?php endif; ?>
                                        <?php if ($host_work): ?>
                                            <div class="appt-mh-detail-row"><span class="appt-mh-dl">💼 My
                                                    work:</span><span><?= esc_html($host_work) ?></span></div>
                                        <?php endif; ?>
                                        <?php if ($host_lang): ?>
                                            <div class="appt-mh-detail-row"><span class="appt-mh-dl">🗣
                                                    Language:</span><span><?= esc_html($host_lang) ?></span></div>
                                        <?php endif; ?>
                                        <?php if ($host_lives): ?>
                                            <div class="appt-mh-detail-row"><span class="appt-mh-dl">📍 Lives
                                                    in:</span><span><?= esc_html($host_lives) ?></span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div><!-- /.appt-single-left -->

                    <!-- RIGHT: Booking Widget -->
                    <div class="appt-single-right">
                        <div class="appt-booking-widget" id="apptBookingWidget" data-apartment-id="<?= intval($id) ?>"
                            data-price="<?= esc_attr($price) ?>" data-cleaning-fee="<?= esc_attr($clean_fee) ?>"
                            data-service-fee="<?= esc_attr($svc_fee) ?>">

                            <div class="appt-bw-price">
                                <span class="appt-bw-amount">$<?= number_format($price, 2) ?></span>
                                <span class="appt-bw-per">per night</span>
                            </div>

                            <div class="appt-bw-dates">
                                <div class="appt-bw-date-group">
                                    <label>Check In</label>
                                    <input type="date" id="apptCheckin" name="checkin" class="appt-date-input"
                                        min="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="appt-bw-date-group">
                                    <label>Check Out</label>
                                    <input type="date" id="apptCheckout" name="checkout" class="appt-date-input"
                                        min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                </div>
                            </div>

                            <div class="appt-bw-avail-msg" id="apptAvailMsg" style="display:none;"></div>

                            <button class="appt-reserve-btn" id="apptReserveBtn">Reserve</button>

                            <p class="appt-bw-note">You won't be charged yet</p>

                            <div class="appt-bw-breakdown" id="apptBreakdown" style="display:none;">
                                <div class="appt-bw-line"><span id="apptNightsLabel">$<?= number_format($price, 2) ?> × 0
                                        nights</span><span id="apptSubtotal">$0.00</span></div>
                                <div class="appt-bw-line"><span>Cleaning
                                        fee</span><span>$<?= number_format($clean_fee, 2) ?></span></div>
                                <div class="appt-bw-line"><span>Appartali service
                                        fee</span><span>$<?= number_format($svc_fee, 2) ?></span></div>
                                <div class="appt-bw-total">
                                    <span>Total before taxes</span>
                                    <span id="apptTotal">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.appt-single-right -->

                </div><!-- /.appt-single-layout -->
            </div><!-- /.appt-single-inner -->

        </div><!-- /.appt-single-page -->

        <!-- ══════════════════════════════════
     BOOKING POPUP MODAL
══════════════════════════════════ -->
        <div id="apptBookingModal" class="appt-popup-overlay" style="display:none;" role="dialog" aria-modal="true"
            aria-label="Booking Form">
            <div class="appt-popup-box">
                <button class="appt-popup-close" id="apptPopupClose" aria-label="Close">✕</button>
                <div class="appt-popup-header">
                    <h2>Complete Your Booking</h2>
                    <p><?= esc_html($title) ?> · <span id="apptPopupLocation"><?= esc_html($location) ?></span></p>
                </div>

                <!-- Booking Summary -->
                <div class="appt-popup-summary">
                    <div class="appt-ps-item"><span>Check-In</span><strong id="apptPopupCheckin">—</strong></div>
                    <div class="appt-ps-item"><span>Check-Out</span><strong id="apptPopupCheckout">—</strong></div>
                    <div class="appt-ps-item"><span>Nights</span><strong id="apptPopupNights">—</strong></div>
                    <div class="appt-ps-item appt-ps-total"><span>Total</span><strong id="apptPopupTotal">—</strong></div>
                </div>

                <!-- Guest Form -->
                <div class="appt-popup-form">
                    <div class="appt-form-row">
                        <div class="appt-form-group">
                            <label for="apptGuestName">Full Name <span class="appt-req">*</span></label>
                            <input type="text" id="apptGuestName" placeholder="John Smith" required>
                        </div>
                        <div class="appt-form-group">
                            <label for="apptGuestEmail">Email Address <span class="appt-req">*</span></label>
                            <input type="email" id="apptGuestEmail" placeholder="john@example.com" required>
                        </div>
                    </div>
                    <div class="appt-form-row">
                        <div class="appt-form-group">
                            <label for="apptGuestPhone">Phone Number</label>
                            <input type="tel" id="apptGuestPhone" placeholder="+1 234 567 8900">
                        </div>
                        <div class="appt-form-group"></div>
                    </div>
                    <div class="appt-form-group appt-form-full">
                        <label for="apptSpecialReq">Special Requests <span class="appt-opt">(optional)</span></label>
                        <textarea id="apptSpecialReq" rows="3"
                            placeholder="Any special requests or notes for the host..."></textarea>
                    </div>
                </div>

                <div class="appt-popup-error" id="apptPopupError" style="display:none;"></div>

                <button class="appt-submit-booking-btn" id="apptSubmitBooking">
                    <span class="appt-btn-text">Confirm Booking</span>
                    <span class="appt-btn-loader" style="display:none;">⟳ Processing…</span>
                </button>
            </div>
        </div>

        <!-- Success Modal -->
        <div id="apptSuccessModal" class="appt-popup-overlay" style="display:none;">
            <div class="appt-popup-box appt-success-box">
                <div class="appt-success-icon">🎉</div>
                <h2>Booking Request Sent!</h2>
                <p>Thank you! Your booking is currently <strong>pending</strong> review. You will receive a confirmation
                    email at <strong id="apptSuccessEmail"></strong> once it's approved.</p>
                <button class="appt-reserve-btn"
                    onclick="document.getElementById('apptSuccessModal').style.display='none'">Done</button>
            </div>
        </div>

    <?php
endwhile;
wp_footer();
    ?>
    </body>

    </html>
