<?php
/**
 * Controlador REST API para Productos
 *
 * Maneja los puntos finales de la API REST para productos
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del controlador REST para productos
 *
 * @since 1.0.0
 */
class WP_POS_Products_REST_Controller {

    /**
     * Namespace de la API
     *
     * @since 1.0.0
     * @access protected
     * @var string
     */
    protected $namespace = 'wp-pos/v1';

    /**
     * Ruta base del recurso
     *
     * @since 1.0.0
     * @access protected
     * @var string
     */
    protected $rest_base = 'products';

    /**
     * Controlador de productos
     *
     * @since 1.0.0
     * @access protected
     * @var WP_POS_Products_Controller
     */
    protected $products_controller;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->products_controller = WP_POS_Products_Controller::get_instance();
    }

    /**
     * Registrar rutas REST
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // Ruta para obtener todos los productos o buscar
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => $this->get_collection_params(),
                ),
            )
        );

        // Ruta para obtener un producto especu00edfico
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'id' => array(
                            'description' => __('ID u00fanico del producto.', 'wp-pos'),
                            'type'        => 'integer',
                            'required'    => true,
                        ),
                    ),
                ),
            )
        );

        // Ruta para actualizar el stock de un producto
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)/stock',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_stock'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                    'args'                => array(
                        'id' => array(
                            'description' => __('ID u00fanico del producto.', 'wp-pos'),
                            'type'        => 'integer',
                            'required'    => true,
                        ),
                        'quantity' => array(
                            'description' => __('Cantidad de stock.', 'wp-pos'),
                            'type'        => 'integer',
                            'required'    => true,
                        ),
                        'operation' => array(
                            'description' => __('Tipo de operaciu00f3n (set, add, subtract).', 'wp-pos'),
                            'type'        => 'string',
                            'enum'        => array('set', 'add', 'subtract'),
                            'default'     => 'set',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Comprobar permisos para obtener lista de productos
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return true|WP_Error Verdadero si tiene permisos, WP_Error si no
     */
    public function get_items_permissions_check($request) {
        if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('Lo sentimos, no tienes permisos para ver productos.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Comprobar permisos para obtener un producto
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return true|WP_Error Verdadero si tiene permisos, WP_Error si no
     */
    public function get_item_permissions_check($request) {
        if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('Lo sentimos, no tienes permisos para ver este producto.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Comprobar permisos para actualizar un producto
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return true|WP_Error Verdadero si tiene permisos, WP_Error si no
     */
    public function update_item_permissions_check($request) {
        if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('Lo sentimos, no tienes permisos para actualizar productos.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Obtener lista de productos
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function get_items($request) {
        $args = array();
        
        // Procesar paru00e1metros
        if (!empty($request['search'])) {
            $args['s'] = sanitize_text_field($request['search']);
        }
        
        if (!empty($request['per_page'])) {
            $args['posts_per_page'] = absint($request['per_page']);
        }
        
        if (!empty($request['page'])) {
            $args['paged'] = absint($request['page']);
        }
        
        if (!empty($request['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => absint($request['category']),
                ),
            );
        }
        
        // Obtener productos
        $products = $this->products_controller->get_products($args);
        
        return rest_ensure_response($products);
    }

    /**
     * Obtener un producto especu00edfico
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function get_item($request) {
        $product_id = $request['id'];
        $product = $this->products_controller->get_product($product_id);
        
        if (!$product) {
            return new WP_Error(
                'wp_pos_rest_product_not_found',
                __('Producto no encontrado.', 'wp-pos'),
                array('status' => 404)
            );
        }
        
        return rest_ensure_response($product);
    }

    /**
     * Actualizar stock de un producto
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function update_stock($request) {
        $product_id = $request['id'];
        $quantity = $request['quantity'];
        $operation = $request['operation'];
        
        $result = $this->products_controller->update_product_stock($product_id, $quantity, $operation);
        
        if (!$result) {
            return new WP_Error(
                'wp_pos_rest_stock_update_failed',
                __('No se pudo actualizar el stock del producto.', 'wp-pos'),
                array('status' => 400)
            );
        }
        
        // Obtener producto actualizado
        $product = $this->products_controller->get_product($product_id);
        
        return rest_ensure_response($product);
    }

    /**
     * Obtener los paru00e1metros de colecciu00f3n para la API
     *
     * @since 1.0.0
     * @return array
     */
    public function get_collection_params() {
        return array(
            'page'     => array(
                'description'        => __('Nu00famero de pu00e1gina actual de la colecciu00f3n.', 'wp-pos'),
                'type'               => 'integer',
                'default'            => 1,
                'sanitize_callback'  => 'absint',
                'validate_callback'  => 'rest_validate_request_arg',
                'minimum'            => 1,
            ),
            'per_page' => array(
                'description'        => __('Mu00e1ximo nu00famero de elementos a devolver por pu00e1gina.', 'wp-pos'),
                'type'               => 'integer',
                'default'            => 10,
                'minimum'            => 1,
                'maximum'            => 100,
                'sanitize_callback'  => 'absint',
                'validate_callback'  => 'rest_validate_request_arg',
            ),
            'search'   => array(
                'description'        => __('Tu00e9rmino de bu00fasqueda.', 'wp-pos'),
                'type'               => 'string',
                'sanitize_callback'  => 'sanitize_text_field',
                'validate_callback'  => 'rest_validate_request_arg',
            ),
            'category' => array(
                'description'        => __('Filtrar por categoru00eda de producto.', 'wp-pos'),
                'type'               => 'integer',
                'sanitize_callback'  => 'absint',
                'validate_callback'  => 'rest_validate_request_arg',
            ),
        );
    }
}
