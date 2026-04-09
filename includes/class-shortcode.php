<?php
defined( 'ABSPATH' ) || exit;

class Appartali_Shortcode {

    public function __construct() {
        add_shortcode( 'explore_rooms', [ $this, 'render' ] );
    }

    /**
     * [explore_rooms limit="8" category=""]
     */
    public function render( $atts ) {
        $atts = shortcode_atts( [
            'limit'    => 8,
            'category' => '',
            'columns'  => 4,
        ], $atts, 'explore_rooms' );

        ob_start();

        /* ── Category Tabs ── */
        $categories = [
            ''          => 'All Category',
            'apartment' => 'Apartment',
            'studio'    => 'Studio',
            'villa'     => 'Villa',
            'house'     => 'House',
            'cottage'   => 'Cottage',
        ];
        ?>
        <section class="appt-explore-section">
            <h2 class="appt-explore-title">Explore Amazing Rooms</h2>

            <div class="appt-category-tabs" id="apptCategoryTabs">
                <?php foreach ( $categories as $val => $label ) : ?>
                    <button class="appt-tab-btn <?= $val === '' ? 'active' : '' ?>"
                            data-category="<?= esc_attr($val) ?>">
                        <?= esc_html($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="appt-rooms-grid" id="apptRoomsGrid">
                <?php $this->render_cards( $atts['limit'], '' ); ?>
            </div>

            <div class="appt-browse-more-wrap">
                <a href="<?= esc_url( get_post_type_archive_link('apartment') ?: '#' ) ?>"
                   class="appt-browse-btn">Browse More</a>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Render room cards (also called via AJAX)
     */
    public function render_cards( $limit = 8, $category = '' ) {
        $query_args = [
            'post_type'      => 'apartment',
            'posts_per_page' => intval($limit),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        if ( $category !== '' ) {
            $query_args['meta_query'] = [
                [
                    'key'     => '_room_type',
                    'value'   => sanitize_text_field($category),
                    'compare' => '=',
                ]
            ];
        }
        $loop = new WP_Query( $query_args );

        if ( ! $loop->have_posts() ) {
            echo '<p class="appt-no-rooms">No rooms found.</p>';
            wp_reset_postdata();
            return;
        }

        while ( $loop->have_posts() ) : $loop->the_post();
            $id            = get_the_ID();
            $thumb         = get_the_post_thumbnail_url( $id, 'medium_large' );
            $price         = get_post_meta( $id, '_price_per_night', true );
            $location      = get_post_meta( $id, '_location', true );
            $host_name     = get_post_meta( $id, '_host_name', true );
            $room_id       = get_post_meta( $id, '_room_id', true );
            $rating        = get_post_meta( $id, '_rating', true );
            $permalink     = get_permalink( $id );
            ?>
            <a href="<?= esc_url($permalink) ?>" class="appt-room-card">
                <div class="appt-room-img-wrap">
                    <?php if ( $thumb ) : ?>
                        <img src="<?= esc_url($thumb) ?>" alt="<?= esc_attr(get_the_title()) ?>" class="appt-room-img" loading="lazy">
                    <?php else : ?>
                        <div class="appt-room-img appt-no-img">
                            <span>No Image</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="appt-room-info">
                    <div class="appt-room-left">
                        <span class="appt-room-location"><?= esc_html($location ?: get_the_title()) ?></span>
                        <?php if ( $host_name ) : ?>
                            <span class="appt-room-host">Stay with <?= esc_html($host_name) ?></span>
                        <?php endif; ?>
                        <span class="appt-room-price">$<?= esc_html(number_format((float)$price,2)) ?> Per Night</span>
                    </div>
                    <div class="appt-room-right">
                        <?php if ( $rating ) : ?>
                            <span class="appt-room-rating"><span class="appt-star">★</span><?= esc_html($rating) ?></span>
                        <?php endif; ?>
                        <?php if ( $room_id ) : ?>
                            <span class="appt-room-id">Room id: <?= esc_html($room_id) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endwhile;
        wp_reset_postdata();
    }
}

/* ── AJAX filter by category ── */
add_action( 'wp_ajax_appt_filter_rooms',        'appt_ajax_filter_rooms' );
add_action( 'wp_ajax_nopriv_appt_filter_rooms', 'appt_ajax_filter_rooms' );
function appt_ajax_filter_rooms() {
    check_ajax_referer( 'appt_nonce', 'nonce' );
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $limit    = isset($_POST['limit'])    ? intval($_POST['limit'])                 : 8;
    ob_start();
    $sc = new Appartali_Shortcode();
    $sc->render_cards( $limit, $category );
    echo ob_get_clean();
    wp_die();
}
