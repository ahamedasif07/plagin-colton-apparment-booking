<?php
defined( 'ABSPATH' ) || exit;

class Appartali_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
    }

    public function add_menu() {
        add_menu_page(
            'Appartali Bookings',
            'Bookings',
            'manage_options',
            'appt-bookings',
            [ $this, 'render_bookings_page' ],
            'dashicons-calendar-alt',
            26
        );
        add_submenu_page(
            'appt-bookings',
            'All Bookings',
            'All Bookings',
            'manage_options',
            'appt-bookings',
            [ $this, 'render_bookings_page' ]
        );
        add_submenu_page(
            'appt-bookings',
            'Booking Settings',
            'Settings',
            'manage_options',
            'appt-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /* ── Bookings list page ── */
    public function render_bookings_page() {
        global $wpdb;
        $t = $wpdb->prefix . 'appt_bookings';

        /* Filters */
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search        = isset($_GET['s'])      ? sanitize_text_field($_GET['s'])      : '';
        $per_page      = 20;
        $paged         = max(1, intval($_GET['paged'] ?? 1));
        $offset        = ($paged - 1) * $per_page;

        $where = 'WHERE 1=1';
        if ( $status_filter ) $where .= $wpdb->prepare(' AND status = %s', $status_filter);
        if ( $search )        $where .= $wpdb->prepare(' AND (guest_name LIKE %s OR guest_email LIKE %s)', "%{$search}%", "%{$search}%");

        $total    = $wpdb->get_var("SELECT COUNT(*) FROM {$t} {$where}");
        $bookings = $wpdb->get_results("SELECT * FROM {$t} {$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}");
        $pages    = ceil($total / $per_page);

        $status_colors = [
            'pending'   => '#f5c842',
            'confirmed' => '#22c55e',
            'cancelled' => '#ef4444',
        ];
        ?>
        <div class="wrap appt-admin-wrap">
            <h1 class="wp-heading-inline">🏠 Appartali Bookings</h1>
            <span class="appt-total-badge"><?= intval($total) ?> total</span>

            <!-- Filters -->
            <div class="appt-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="appt-bookings">
                    <input type="text" name="s" value="<?= esc_attr($search) ?>" placeholder="Search by name or email..." class="appt-search-input">
                    <select name="status" class="appt-filter-select">
                        <option value="">All Statuses</option>
                        <?php foreach (['pending','confirmed','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= selected($status_filter,$s,false) ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="appt-btn-filter">Filter</button>
                    <a href="<?= admin_url('admin.php?page=appt-bookings') ?>" class="appt-btn-reset">Reset</a>
                </form>
            </div>

            <?php if ( empty($bookings) ): ?>
                <div class="appt-empty-state">No bookings found.</div>
            <?php else: ?>

            <table class="appt-bookings-table widefat">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Property</th>
                        <th>Guest</th>
                        <th>Contact</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Nights</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $b): 
                    $apt_title = get_the_title($b->apartment_id) ?: '(deleted)';
                    $apt_link  = get_edit_post_link($b->apartment_id);
                    $color     = $status_colors[$b->status] ?? '#aaa';
                ?>
                    <tr class="appt-booking-row" data-id="<?= intval($b->id) ?>">
                        <td><strong>#<?= intval($b->id) ?></strong></td>
                        <td>
                            <?php if ($apt_link): ?>
                                <a href="<?= esc_url($apt_link) ?>" target="_blank"><?= esc_html($apt_title) ?></a>
                            <?php else: echo esc_html($apt_title); endif; ?>
                        </td>
                        <td><?= esc_html($b->guest_name) ?></td>
                        <td>
                            <a href="mailto:<?= esc_attr($b->guest_email) ?>"><?= esc_html($b->guest_email) ?></a>
                            <?php if ($b->guest_phone): ?>
                                <br><small><?= esc_html($b->guest_phone) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= esc_html($b->checkin_date) ?></td>
                        <td><?= esc_html($b->checkout_date) ?></td>
                        <td><?= intval($b->nights) ?></td>
                        <td><strong>$<?= number_format($b->total_price,2) ?></strong></td>
                        <td>
                            <span class="appt-status-badge" style="background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>55;">
                                <?= ucfirst(esc_html($b->status)) ?>
                            </span>
                        </td>
                        <td><small><?= date('M j, Y', strtotime($b->created_at)) ?></small></td>
                        <td>
                            <div class="appt-action-buttons">
                                <?php if ($b->status !== 'confirmed'): ?>
                                    <button class="appt-action-btn appt-btn-confirm" data-id="<?= $b->id ?>" data-status="confirmed">Confirm</button>
                                <?php endif; ?>
                                <?php if ($b->status !== 'pending'): ?>
                                    <button class="appt-action-btn appt-btn-pending" data-id="<?= $b->id ?>" data-status="pending">Pending</button>
                                <?php endif; ?>
                                <?php if ($b->status !== 'cancelled'): ?>
                                    <button class="appt-action-btn appt-btn-cancel" data-id="<?= $b->id ?>" data-status="cancelled">Cancel</button>
                                <?php endif; ?>
                                <button class="appt-action-btn appt-btn-view-details" data-booking='<?= esc_attr(json_encode([
                                    'id'       => $b->id,
                                    'apt'      => $apt_title,
                                    'name'     => $b->guest_name,
                                    'email'    => $b->guest_email,
                                    'phone'    => $b->guest_phone,
                                    'checkin'  => $b->checkin_date,
                                    'checkout' => $b->checkout_date,
                                    'nights'   => $b->nights,
                                    'total'    => $b->total_price,
                                    'status'   => $b->status,
                                    'req'      => $b->special_req,
                                    'date'     => $b->created_at,
                                ])) ?>'>View</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="appt-pagination">
                <?php for ($i=1;$i<=$pages;$i++): ?>
                    <a href="<?= add_query_arg(['paged'=>$i,'status'=>$status_filter,'s'=>$search], admin_url('admin.php?page=appt-bookings')) ?>"
                       class="appt-page-btn <?= $i===$paged?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Booking Detail Modal -->
        <div id="apptBookingModal" class="appt-modal" style="display:none;">
            <div class="appt-modal-overlay"></div>
            <div class="appt-modal-content">
                <button class="appt-modal-close">✕</button>
                <h2>Booking Details</h2>
                <div id="apptBookingModalBody"></div>
            </div>
        </div>

        <!-- Toast -->
        <div id="apptToast" class="appt-toast" style="display:none;"></div>
        <?php
    }

    /* ── Settings page ── */
    public function render_settings_page() {
        if ( isset($_POST['appt_settings_nonce']) && wp_verify_nonce($_POST['appt_settings_nonce'], 'appt_save_settings') ) {
            update_option('appt_admin_email', sanitize_email($_POST['appt_admin_email'] ?? ''));
            update_option('appt_currency', sanitize_text_field($_POST['appt_currency'] ?? '$'));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $admin_email = get_option('appt_admin_email', get_option('admin_email'));
        $currency    = get_option('appt_currency', '$');
        ?>
        <div class="wrap appt-admin-wrap">
            <h1>⚙️ Appartali Settings</h1>
            <form method="post">
                <?php wp_nonce_field('appt_save_settings','appt_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Notification Email</th>
                        <td><input type="email" name="appt_admin_email" value="<?= esc_attr($admin_email) ?>" class="regular-text">
                        <p class="description">Admin email for new booking notifications.</p></td>
                    </tr>
                    <tr>
                        <th>Currency Symbol</th>
                        <td><input type="text" name="appt_currency" value="<?= esc_attr($currency) ?>" class="small-text"></td>
                    </tr>
                </table>
                <p>
                    <strong>Shortcode:</strong> Use <code>[explore_rooms]</code> to display the room listing grid.<br>
                    <strong>Options:</strong> <code>[explore_rooms limit="8"]</code>
                </p>
                <?php submit_button('Save Settings', 'primary', 'submit', true); ?>
            </form>
        </div>
        <?php
    }
}
