<?php
defined('ABSPATH') || exit;

class Appartali_Shortcode
{

    public function __construct()
    {
        add_shortcode('explore_rooms', [$this, 'render']);
    }

    public function render($atts)
    {
        $atts = shortcode_atts([
            'limit'    => 8,
            'category' => '',
            'columns'  => 4,
        ], $atts, 'explore_rooms');

        ob_start();

        $categories = [
            ''  => 'All Category',
            '1' => '1 Room',
            '2' => '2 Rooms',
            '3' => '3 Rooms',
            '4' => '4 Rooms',
            '5' => '5 Rooms',
            '6' => '6 Rooms',
            '7' => '7 Rooms',
            '8' => '8 Rooms',
        ];
?>
        <section class="appt-explore-section">
            <h2 class="appt-explore-title">Explore Amazing Rooms</h2>

            <div class="appt-category-tabs" id="apptCategoryTabs">
                <?php foreach ($categories as $val => $label) : ?>
                    <button class="appt-tab-btn <?= $val === '' ? 'active' : '' ?>" data-category="<?= esc_attr($val) ?>">
                        <?= esc_html($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="appt-rooms-grid" id="apptRoomsGrid">
                <?php $this->render_cards($atts['limit'], ''); ?>
            </div>

            <div class="appt-browse-more-wrap">
                <a href="<?= esc_url(get_post_type_archive_link('apartment') ?: '#') ?>" class="appt-browse-btn">Browse More</a>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function render_cards($limit = 8, $category = '')
    {
        $query_args = [
            'post_type'      => 'apartment',
            'posts_per_page' => intval($limit),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // FIX: use '_room_number' which is the actual saved meta key from class-cpt.php
        if ($category !== '') {
            $query_args['meta_query'] = [
                [
                    'key'     => '_room_number',
                    'value'   => sanitize_text_field($category),
                    'compare' => '=',
                ]
            ];
        }

        $loop = new WP_Query($query_args);

        if (! $loop->have_posts()) {
            echo '<p class="appt-no-rooms">No rooms found matching your criteria.</p>';
            wp_reset_postdata();
            return;
        }

        while ($loop->have_posts()) : $loop->the_post();
            $id            = get_the_ID();
            $title         = get_the_title();
            $thumb         = get_the_post_thumbnail_url($id, 'medium_large');
            $price         = get_post_meta($id, '_price_per_night', true);
            $location      = get_post_meta($id, '_location', true);
            $host_name     = get_post_meta($id, '_host_name', true);
            $room_id       = get_post_meta($id, '_room_id', true);
            $rating        = get_post_meta($id, '_rating', true);
            $room_number   = get_post_meta($id, '_room_number', true);
            $max_guests    = get_post_meta($id, '_max_guests', true);
            $permalink     = get_permalink($id);

            // Display name: use title (apartment name)
            $display_name  = $title ?: $location;
        ?>
            <a href="<?= esc_url($permalink) ?>" class="appt-room-card">
                <div class="appt-room-img-wrap">
                    <?php if ($thumb) : ?>
                        <img src="<?= esc_url($thumb) ?>" alt="<?= esc_attr($title) ?>" class="appt-room-img" loading="lazy">
                    <?php else : ?>
                        <div class="appt-room-img appt-no-img">
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="appt-room-info">
                    <!-- Top row: name + rating -->
                    <div class="appt-room-top-row">
                        <span class="appt-room-name"><?= esc_html($display_name) ?></span>
                        <?php if ($rating) : ?>
                            <span class="appt-room-rating"><span
                                    class="appt-star">★</span><?= esc_html(number_format((float)$rating, 1)) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Host -->
                    <?php if ($host_name) : ?>
                        <span class="appt-room-host">Stay with <?= esc_html($host_name) ?></span>
                    <?php endif; ?>

                    <!-- Features row: rooms, bathrooms, guests -->
                    <div class="appt-room-features">
                        <?php if ($room_number) : ?>
                            <span class="appt-feat-item">🛏 <?= esc_html($room_number) ?> Room<?= $room_number > 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                        <span class="appt-feat-item">🚿 1 Bath</span>
                        <?php if ($max_guests) : ?>
                            <span class="appt-feat-item">👥 <?= esc_html($max_guests) ?> Guests</span>
                        <?php endif; ?>
                        <?php if ($room_id) : ?>
                            <span class="appt-feat-item">🔑 ID: <?= esc_html($room_id) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Price -->
                    <span class="appt-room-price"><strong>$<?= esc_html(number_format((float)$price, 2)) ?></strong> / Night</span>
                </div>
            </a>
<?php endwhile;
        wp_reset_postdata();
    }
}

add_action('wp_ajax_appt_filter_rooms',        'appt_ajax_filter_rooms');
add_action('wp_ajax_nopriv_appt_filter_rooms', 'appt_ajax_filter_rooms');

function appt_ajax_filter_rooms()
{
    check_ajax_referer('appt_nonce', 'nonce');

    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $limit    = isset($_POST['limit'])    ? intval($_POST['limit'])                  : 8;

    ob_start();
    $sc = new Appartali_Shortcode();
    $sc->render_cards($limit, $category);
    echo ob_get_clean();

    wp_die();
}
