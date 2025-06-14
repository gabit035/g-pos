<?php
/**
 * Controlador de Servicios para WP-POS
 *
 * Gestiona servicios sin stock.
 *
 * @package WP-POS
 * @subpackage Services
 */
if (!defined('ABSPATH')) exit;

class WP_POS_Services_Controller {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function get_services($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'pos_services';
        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            return $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table WHERE name LIKE %s", $like),
                ARRAY_A
            );
        }
        return $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
    }

    public function get_service($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pos_services';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", absint($id)),
            ARRAY_A
        );
    }

    public function create_service($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pos_services';
        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'purchase_price' => floatval($data['purchase_price']),
            'sale_price' => floatval($data['sale_price']),
        ));
        if (false === $result) {
            // Log DB error and return WP_Error for REST
            error_log('WP-POS Service insert error: ' . $wpdb->last_error);
            return new WP_Error('service_insert_failed', $wpdb->last_error, array('status' => 500));
        }
        return $wpdb->insert_id;
    }
}
