<?php
if (!defined('ABSPATH')) exit;

final class WPBB_Booking_Admin {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    private function __construct() {
        add_filter('manage_wpbb_booking_posts_columns', [$this, 'columns']);
        add_action('manage_wpbb_booking_posts_custom_column', [$this, 'column_content'], 10, 2);
        add_filter('manage_edit-wpbb_booking_sortable_columns', [$this, 'sortable_columns']);
        add_action('pre_get_posts', [$this, 'sort_query']);
        add_action('add_meta_boxes_wpbb_booking', [$this, 'meta_boxes']);
        add_action('save_post_wpbb_booking', [$this, 'save'], 10, 2);
    }
    private function booking_meta($post_id, $key, $legacy_key = '') {
        $value = get_post_meta($post_id, $key, true);
        if ($value === '' && $legacy_key !== '') $value = get_post_meta($post_id, $legacy_key, true);
        return $value;
    }
    public function columns($columns) {
        return [
            'cb' => $columns['cb'] ?? '<input type="checkbox" />',
            'title' => __('Appointment', 'wp-bbuilder'),
            'wpbb_client' => __('Patient / client', 'wp-bbuilder'),
            'wpbb_provider' => __('Provider', 'wp-bbuilder'),
            'wpbb_service' => __('Service', 'wp-bbuilder'),
            'wpbb_datetime' => __('Date / time', 'wp-bbuilder'),
            'wpbb_status' => __('Status', 'wp-bbuilder'),
            'date' => __('Created', 'wp-bbuilder'),
        ];
    }
    public function column_content($column, $post_id) {
        if ($column === 'wpbb_client') {
            $name = get_post_meta($post_id, 'client_name', true);
            $email = get_post_meta($post_id, 'client_email', true);
            echo esc_html($name ?: '—');
            if ($email) echo '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        } elseif ($column === 'wpbb_provider') {
            echo esc_html($this->booking_meta($post_id, 'provider_name', 'booking_provider_name') ?: '—');
        } elseif ($column === 'wpbb_service') {
            echo esc_html($this->booking_meta($post_id, 'service', 'booking_service') ?: '—');
        } elseif ($column === 'wpbb_datetime') {
            $date = get_post_meta($post_id, 'booking_date', true);
            $time = get_post_meta($post_id, 'booking_time', true);
            echo esc_html(trim($date . ' ' . $time) ?: '—');
        } elseif ($column === 'wpbb_status') {
            $status = get_post_meta($post_id, 'booking_status', true) ?: 'requested';
            echo '<span class="wpbb-booking-status wpbb-booking-status--' . esc_attr(sanitize_html_class($status)) . '">' . esc_html(ucfirst($status)) . '</span>';
        }
    }
    public function sortable_columns($columns) {
        $columns['wpbb_datetime'] = 'wpbb_booking_date';
        return $columns;
    }
    public function sort_query($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'wpbb_booking') return;
        if ($query->get('orderby') === 'wpbb_booking_date') {
            $query->set('meta_key', 'booking_date');
            $query->set('orderby', 'meta_value');
        }
    }
    public function meta_boxes() {
        add_meta_box('wpbb-booking-details', __('Appointment details', 'wp-bbuilder'), [$this, 'render'], 'wpbb_booking', 'normal', 'high');
    }
    public function render($post) {
        wp_nonce_field('wpbb_booking_admin_save', 'wpbb_booking_admin_nonce');
        $fields = [
            'client_name' => __('Patient / client', 'wp-bbuilder'),
            'client_email' => __('Email', 'wp-bbuilder'),
            'client_phone' => __('Phone', 'wp-bbuilder'),
            'provider_name' => __('Provider', 'wp-bbuilder'),
            'service' => __('Service', 'wp-bbuilder'),
            'booking_date' => __('Date', 'wp-bbuilder'),
            'booking_time' => __('Time', 'wp-bbuilder'),
        ];
        echo '<div class="wpbb-booking-admin-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">';
        foreach ($fields as $key => $label) {
            $legacy_key = $key === 'provider_name' ? 'booking_provider_name' : ($key === 'service' ? 'booking_service' : '');
            $value = $this->booking_meta($post->ID, $key, $legacy_key);
            $type = $key === 'client_email' ? 'email' : ($key === 'booking_date' ? 'date' : ($key === 'booking_time' ? 'time' : 'text'));
            echo '<p><label><strong>' . esc_html($label) . '</strong><br><input class="widefat" type="' . esc_attr($type) . '" name="wpbb_booking[' . esc_attr($key) . ']" value="' . esc_attr($value) . '"></label></p>';
        }
        echo '<p><label><strong>' . esc_html__('Status', 'wp-bbuilder') . '</strong><br><select class="widefat" name="wpbb_booking[booking_status]">';
        $current = get_post_meta($post->ID, 'booking_status', true) ?: 'requested';
        foreach (['requested','confirmed','completed','cancelled'] as $status) echo '<option value="' . esc_attr($status) . '" ' . selected($current, $status, false) . '>' . esc_html(ucfirst($status)) . '</option>';
        echo '</select></label></p></div>';
        echo '<p><label><strong>' . esc_html__('Notes', 'wp-bbuilder') . '</strong><br><textarea class="widefat" rows="5" name="wpbb_booking[client_notes]">' . esc_textarea(get_post_meta($post->ID, 'client_notes', true)) . '</textarea></label></p>';
    }
    public function save($post_id, $post) {
        if (empty($_POST['wpbb_booking_admin_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wpbb_booking_admin_nonce'])), 'wpbb_booking_admin_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!$post || $post->post_type !== 'wpbb_booking' || !current_user_can('edit_post', $post_id)) return;
        $data = isset($_POST['wpbb_booking']) && is_array($_POST['wpbb_booking']) ? wp_unslash($_POST['wpbb_booking']) : [];
        foreach (['client_name','client_phone','provider_name','service','booking_date','booking_time'] as $key) {
            if (isset($data[$key])) update_post_meta($post_id, $key, sanitize_text_field($data[$key]));
        }
        if (isset($data['client_email'])) update_post_meta($post_id, 'client_email', sanitize_email($data['client_email']));
        if (isset($data['client_notes'])) update_post_meta($post_id, 'client_notes', sanitize_textarea_field($data['client_notes']));
        $status = isset($data['booking_status']) ? sanitize_key($data['booking_status']) : 'requested';
        if (in_array($status, ['requested','confirmed','completed','cancelled'], true)) update_post_meta($post_id, 'booking_status', $status);
    }
}
