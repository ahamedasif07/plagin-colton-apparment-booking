<?php
defined('ABSPATH') || exit;

class Appartali_CPT
{

    public function __construct()
    {
        add_action('init',                   [$this, 'register_cpt']);
        add_action('add_meta_boxes',         [$this, 'add_meta_boxes']);
        add_action('save_post_apartment',    [$this, 'save_meta']);
        add_action('admin_enqueue_scripts',  [$this, 'enqueue_media']);
    }

    /* ── CPT + Taxonomy ── */
    public function register_cpt()
    {
        register_post_type('apartment', [
            'labels'       => [
                'name'               => 'Apartments',
                'singular_name'      => 'Apartment',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Apartment',
                'edit_item'          => 'Edit Apartment',
                'view_item'          => 'View Apartment',
                'all_items'          => 'All Apartments',
                'search_items'       => 'Search Apartments',
                'menu_name'          => 'Apartments',
            ],
            'public'        => true,
            'has_archive'   => true,
            'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
            'menu_icon'     => 'dashicons-building',
            'rewrite'       => ['slug' => 'apartment'],
            'show_in_rest'  => true,
        ]);

        register_taxonomy('apartment_category', 'apartment', [
            'labels'            => [
                'name'          => 'Room Categories',
                'singular_name' => 'Room Category',
                'menu_name'     => 'Room Categories',
            ],
            'hierarchical'      => true,
            'show_admin_column' => true,
            'rewrite'           => ['slug' => 'apartment-category'],
        ]);
    }

    /* ── Media Uploader ── */
    public function enqueue_media($hook)
    {
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            wp_enqueue_media();
        }
    }

    /* ── Meta Boxes ── */
    public function add_meta_boxes()
    {
        $boxes = [
            ['appt_details',   'Apartment Details',  'render_details',   'normal',  'high'],
            ['appt_gallery',   'Gallery Images',     'render_gallery',   'normal',  'default'],
            ['appt_amenities', 'Amenities',          'render_amenities', 'normal',  'default'],
            ['appt_host',      'Host Information',   'render_host',      'normal',  'default'],
        ];
        foreach ($boxes as $b) {
            add_meta_box($b[0], $b[1], [$this, $b[2]], 'apartment', $b[3], $b[4]);
        }
    }

    /* ── Details Box ── */
    public function render_details($post)
    {
        wp_nonce_field('appt_save_meta', 'appt_meta_nonce');
        $fields = [
            '_price_per_night' => ['Price Per Night ($)', 'number', 'step="0.01"'],
            '_location'        => ['Location (e.g. Paris, France)', 'text', ''],
            '_room_id'         => ['Room ID', 'text', ''],
            '_rating'          => ['Rating (0–5)', 'number', 'step="0.01" min="0" max="5"'],
            '_max_guests'      => ['Max Guests', 'number', 'min="1"'],
            '_cleaning_fee'    => ['Cleaning Fee ($)', 'number', 'step="0.01"'],
            '_service_fee'     => ['Appartali Service Fee ($)', 'number', 'step="0.01"'],
        ];
        echo '<table class="form-table">';
        foreach ($fields as $key => $cfg) {
            $val = esc_attr(get_post_meta($post->ID, $key, true));
            echo "<tr><th>{$cfg[0]}</th><td><input type='{$cfg[1]}' name='" . ltrim($key, '_') . "' value='{$val}' {$cfg[2]} class='regular-text'></td></tr>";
        }
        /* Room number */
        /**
         * Render Room Number selection in the admin meta box
         */
        $rn = get_post_meta($post->ID, '_room_number', true);

        echo '<tr><th>Room Number</th><td><select name="room_number">';

        // Define the range of rooms you want to display (e.g., 1 to 10)
        $room_options = [
            '1' => '1 Room',
            '2' => '2 Rooms',
            '3' => '3 Rooms',
            '4' => '4 Rooms',
            '5' => '5 Rooms',
            '6' => '6 Rooms',
            '7' => '7 Rooms',
            '8' => '8 Rooms'
        ];

        foreach ($room_options as $v => $l) {
            echo "<option value='$v'" . selected($rn, $v, false) . ">$l</option>";
        }

        echo '</select></td></tr></table>';
    }

    /* ── Gallery Box ── */
    public function render_gallery($post)
    {
        $raw  = get_post_meta($post->ID, '_gallery_images', true);
        $ids  = $raw ? explode(',', $raw) : [];
?>
        <div id="appt-gallery-wrap" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
            <?php foreach ($ids as $id):
                $url = wp_get_attachment_image_url(intval($id), 'thumbnail');
                if ($url): ?>
                    <div class="appt-gal-item" style="position:relative;">
                        <img src="<?= esc_url($url) ?>" style="width:90px;height:70px;object-fit:cover;border-radius:4px;">
                        <button type="button" class="appt-remove-img button" data-id="<?= intval($id) ?>"
                            style="position:absolute;top:2px;right:2px;padding:0 4px;font-size:11px;line-height:18px;">✕</button>
                    </div>
            <?php endif;
            endforeach; ?>
        </div>
        <input type="hidden" name="gallery_images" id="appt_gallery_ids" value="<?= esc_attr($raw) ?>">
        <button type="button" class="button button-secondary" id="appt_add_gallery">+ Add Images</button>
        <script>
            jQuery(function($) {
                var frame;
                $('#appt_add_gallery').on('click', function() {
                    if (frame) {
                        frame.open();
                        return;
                    }
                    frame = wp.media({
                        title: 'Select Gallery Images',
                        multiple: true
                    });
                    frame.on('select', function() {
                        var att = frame.state().get('selection').toJSON();
                        var ids = $('#appt_gallery_ids').val() ? $('#appt_gallery_ids').val().split(',') : [];
                        att.forEach(function(a) {
                            if (ids.indexOf(String(a.id)) === -1) {
                                ids.push(a.id);
                                $('#appt-gallery-wrap').append(
                                    '<div class="appt-gal-item" style="position:relative;"><img src="' +
                                    a.sizes.thumbnail.url +
                                    '" style="width:90px;height:70px;object-fit:cover;border-radius:4px;"><button type="button" class="appt-remove-img button" data-id="' +
                                    a.id +
                                    '" style="position:absolute;top:2px;right:2px;padding:0 4px;font-size:11px;line-height:18px;">✕</button></div>'
                                );
                            }
                        });
                        $('#appt_gallery_ids').val(ids.join(','));
                    });
                    frame.open();
                });
                $(document).on('click', '.appt-remove-img', function() {
                    var id = $(this).data('id');
                    var ids = $('#appt_gallery_ids').val().split(',').filter(function(i) {
                        return String(i) !== String(id);
                    });
                    $('#appt_gallery_ids').val(ids.join(','));
                    $(this).closest('.appt-gal-item').remove();
                });
            });
        </script>
    <?php
    }

    /* ── Amenities Box ── */
    public function render_amenities($post)
    {
        $saved = get_post_meta($post->ID, '_amenities', true) ?: [];
        $list  = [
            'lock_door'  => '🔒 Lock on bedroom door',
            'wifi'       => '📶 Free WiFi',
            'tv'         => '📺 TV',
            'luggage'    => '🧳 Luggage dropoff allowed',
            'fridge'     => '🧊 Refrigerator',
            'kitchen'    => '🍳 Kitchen',
            'workspace'  => '💼 Dedicated workspace',
            'washer'     => '🫧 Washer',
            'hair_dryer' => '💨 Hair dryer',
            'iron'       => '👔 Iron machine',
            'ac'         => '❄️ Air conditioning',
            'parking'    => '🚗 Free parking',
            'pool'       => '🏊 Swimming pool',
            'gym'        => '🏋️ Gym access',
        ];
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">';
        foreach ($list as $k => $l) {
            $chk = in_array($k, $saved) ? 'checked' : '';
            echo "<label><input type='checkbox' name='amenities[]' value='$k' $chk> $l</label>";
        }
        echo '</div>';
    }

    /* ── Host Box ── */
    public function render_host($post)
    {
        $f = [
            '_host_name'          => ['Host Name', 'text'],
            '_host_image'         => ['Host Profile Image URL', 'text'],
            '_host_reviews'       => ['Host Reviews Count', 'number'],
            '_host_ratings'       => ['Host Ratings Count', 'number'],
            '_host_years'         => ['Years Hosting', 'number'],
            '_host_response_rate' => ['Response Rate (%)', 'text'],
            '_host_response_time' => ['Response Time', 'text'],
            '_host_work'          => ['My Work', 'text'],
            '_host_language'      => ['Language(s)', 'text'],
            '_host_lives'         => ['Lives In', 'text'],
            '_host_cohost_name'   => ['Co-Host Name (optional)', 'text'],
        ];
        $super = get_post_meta($post->ID, '_host_superhost', true);
        echo '<table class="form-table">';
        foreach ($f as $k => $cfg) {
            $v = esc_attr(get_post_meta($post->ID, $k, true));
            $name = ltrim($k, '_');
            echo "<tr><th>{$cfg[0]}</th><td>";
            if ($k === '_host_image') {
                echo "<input type='text' name='{$name}' id='host_img_field' value='{$v}' class='regular-text'>
                      <button type='button' class='button' id='appt_host_img_btn'>Upload</button>";
            } else {
                echo "<input type='{$cfg[1]}' name='{$name}' value='{$v}' class='regular-text'>";
            }
            echo "</td></tr>";
        }
        echo "<tr><th>Superhost</th><td><input type='checkbox' name='host_superhost' value='1'" . checked($super, '1', false) . "> Mark as Superhost</td></tr>";
        echo '</table>';
    ?>
        <script>
            jQuery(function($) {
                $('#appt_host_img_btn').on('click', function() {
                    var u = wp.media({
                        title: 'Select Host Image',
                        multiple: false
                    });
                    u.on('select', function() {
                        $('#host_img_field').val(u.state().get('selection').first().toJSON().url);
                    });
                    u.open();
                });
            });
        </script>
<?php
    }

    /* ── Save Meta ── */
    public function save_meta($post_id)
    {
        if (! isset($_POST['appt_meta_nonce']) || ! wp_verify_nonce($_POST['appt_meta_nonce'], 'appt_save_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (! current_user_can('edit_post', $post_id)) return;

        $text_fields = [
            'price_per_night',
            'location',
            'room_id',
            'rating',
            'room_type',
            'max_guests',
            'cleaning_fee',
            'service_fee',
            'gallery_images',
            'host_name',
            'host_image',
            'host_reviews',
            'host_ratings',
            'host_years',
            'host_response_rate',
            'host_response_time',
            'host_work',
            'host_language',
            'host_lives',
            'host_cohost_name',
        ];
        foreach ($text_fields as $f) {
            if (isset($_POST[$f])) {
                update_post_meta($post_id, '_' . $f, sanitize_text_field($_POST[$f]));
            }
        }
        update_post_meta($post_id, '_host_superhost', isset($_POST['host_superhost']) ? '1' : '0');
        $amenities = isset($_POST['amenities']) ? array_map('sanitize_text_field', (array)$_POST['amenities']) : [];
        update_post_meta($post_id, '_amenities', $amenities);
    }
}
