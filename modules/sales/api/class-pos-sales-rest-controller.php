<?php
/**
 * API REST de Ventas para WP-POS
 *
 * Maneja los endpoints de la API REST para ventas
 *
 * @package WP-POS
 * @subpackage Sales
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del controlador REST de ventas
 *
 * @since 1.0.0
 */
class WP_POS_Sales_REST_Controller extends WP_REST_Controller {

    /**
     * Namespace base para la API
     *
     * @since 1.0.0
     * @access protected
     * @var string
     */
    protected $namespace = 'wp-pos/v1';

    /**
     * Ruta base para el endpoint
     *
     * @since 1.0.0
     * @access protected
     * @var string
     */
    protected $rest_base = 'sales';

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Registrar rutas de la API
     *
     * @since 1.0.0
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
                ),
            )
        );
    }

    /**
     * Comprobar permisos para obtener elementos
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return bool True si el usuario tiene permisos, false en caso contrario
     */
    public function get_items_permissions_check($request) {
        return current_user_can('manage_options') || current_user_can('manage_woocommerce');
    }

    /**
     * Obtener mu00faltiples elementos
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return WP_REST_Response Respuesta con los datos
     */
    public function get_items($request) {
        $controller = WP_POS_Sales_Controller::get_instance();
        $sales = $controller->get_sales();
        
        return rest_ensure_response($sales);
    }

    /**
     * Comprobar permisos para crear un elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return bool True si el usuario tiene permisos, false en caso contrario
     */
    public function create_item_permissions_check($request) {
        return current_user_can('manage_options') || current_user_can('manage_woocommerce');
    }

    /**
     * Crear un nuevo elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function create_item($request) {
        $controller = WP_POS_Sales_Controller::get_instance();
        $sale = $controller->process_sale($request->get_params());
        
        if (!$sale) {
            return new WP_Error(
                'wp_pos_create_sale_error',
                __('Error al crear la venta', 'wp-pos'),
                array('status' => 500)
            );
        }
        
        $response = array(
            'id' => $sale->get_id(),
            'sale_number' => $sale->get_sale_number(),
            'status' => $sale->get_status(),
            'date' => $sale->get_date(),
        );
        
        return rest_ensure_response($response);
    }

    /**
     * Comprobar permisos para obtener un elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return bool True si el usuario tiene permisos, false en caso contrario
     */
    public function get_item_permissions_check($request) {
        return current_user_can('manage_options') || current_user_can('manage_woocommerce');
    }

    /**
     * Obtener un elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function get_item($request) {
        $sale_id = $request['id'];
        $controller = WP_POS_Sales_Controller::get_instance();
        $sale = $controller->get_sale($sale_id);
        
        if (!$sale) {
            return new WP_Error(
                'wp_pos_sale_not_found',
                __('Venta no encontrada', 'wp-pos'),
                array('status' => 404)
            );
        }
        
        $response = array(
            'id' => $sale->get_id(),
            'sale_number' => $sale->get_sale_number(),
            'customer_id' => $sale->get_customer_id(),
            'date' => $sale->get_date(),
            'status' => $sale->get_status(),
            'items' => $sale->get_items(),
            'payments' => $sale->get_payments(),
        );
        
        return rest_ensure_response($response);
    }

    /**
     * Comprobar permisos para actualizar un elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return bool True si el usuario tiene permisos, false en caso contrario
     */
    public function update_item_permissions_check($request) {
        return current_user_can('manage_options') || current_user_can('manage_woocommerce');
    }

    /**
     * Actualizar un elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function update_item($request) {
        $sale_id = $request['id'];
        $controller = WP_POS_Sales_Controller::get_instance();
        $sale = $controller->update_sale($sale_id, $request->get_params());
        
        if (!$sale) {
            return new WP_Error(
                'wp_pos_update_sale_error',
                __('Error al actualizar la venta', 'wp-pos'),
                array('status' => 500)
            );
        }
        
        $response = array(
            'id' => $sale->get_id(),
            'sale_number' => $sale->get_sale_number(),
            'status' => $sale->get_status(),
            'date' => $sale->get_date(),
        );
        
        return rest_ensure_response($response);
    }

    /**
     * Comprobar permisos para eliminar un elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return bool True si el usuario tiene permisos, false en caso contrario
     */
    public function delete_item_permissions_check($request) {
        return current_user_can('manage_options') || current_user_can('manage_woocommerce');
    }

    /**
     * Eliminar un elemento
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Solicitud completa
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function delete_item($request) {
        $sale_id = $request['id'];
        $controller = WP_POS_Sales_Controller::get_instance();
        $result = $controller->delete_sale($sale_id);
        
        if (!$result) {
            return new WP_Error(
                'wp_pos_delete_sale_error',
                __('Error al eliminar la venta', 'wp-pos'),
                array('status' => 500)
            );
        }
        
        return rest_ensure_response(array(
            'deleted' => true,
            'id' => $sale_id,
        ));
    }
}

// Inicializar controlador REST
new WP_POS_Sales_REST_Controller();
