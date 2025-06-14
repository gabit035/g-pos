<?php
require_once __DIR__ . '/../controllers/class-pos-services-controller.php';

/**
 * Controlador REST API para Servicios
 *
 * @package WP-POS
 * @subpackage Services
 * @since 1.0.0
 */
if (!defined('ABSPATH')) exit;

class WP_POS_Services_REST_Controller {
    protected $namespace = 'wp-pos/v1';
    protected $rest_base = 'services';
    protected $controller;

    public function __construct() {
        $this->controller = WP_POS_Services_Controller::get_instance();
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'get_items'),
                    'permission_callback' => '__return_true',
                    'args' => array('search' => array('type' => 'string')),
                ),
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'create_item'),
                    'permission_callback' => '__return_true',
                    'args' => array(
                        'name' => array('required' => true),
                        'description' => array('required' => false),
                        'purchase_price' => array('required' => true),
                        'sale_price' => array('required' => true),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'get_item'),
                    'permission_callback' => function() { return current_user_can('view_pos'); },
                    'args' => array('id' => array('required' => true, 'type' => 'integer')),
                ),
            )
        );
    }

    public function get_items($request) {
        $args = array();
        if (isset($request['search'])) {
            $args['search'] = sanitize_text_field($request['search']);
        }
        $items = $this->controller->get_services($args);
        return rest_ensure_response($items);
    }

    public function get_item($request) {
        $item = $this->controller->get_service($request['id']);
        if (!$item) {
            return new WP_Error('rest_service_not_found', __('Servicio no encontrado.', 'wp-pos'), array('status' => 404));
        }
        return rest_ensure_response($item);
    }

    public function create_item($request) {
        $data = $request->get_json_params();
        $id = $this->controller->create_service($data);
        if (!$id) {
            return new WP_Error('rest_service_create_failed', __('No se pudo crear el servicio.', 'wp-pos'), array('status' => 500));
        }
        $item = $this->controller->get_service($id);
        return rest_ensure_response($item);
    }
}
