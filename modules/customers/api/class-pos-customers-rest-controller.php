<?php
/**
 * Controlador REST API para clientes en WP-POS
 *
 * Gestiona los endpoints de la API REST relacionados con clientes.
 *
 * @package WP-POS
 * @subpackage Customers/API
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora de REST API para clientes
 *
 * @since 1.0.0
 */
class WP_POS_Customers_REST_Controller extends WP_POS_REST_Controller {

    /**
     * Namespace para la API
     *
     * @since 1.0.0
     * @access protected
     * @var string
     */
    protected $namespace = 'wp-pos/v1';

    /**
     * Ruta base para los endpoints
     *
     * @since 1.0.0
     * @access protected
     * @var string
     */
    protected $rest_base = 'customers';

    /**
     * Instancia del controlador de clientes
     *
     * @since 1.0.0
     * @access protected
     * @var WP_POS_Customers_Controller
     */
    protected $controller;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->controller = new WP_POS_Customers_Controller();
    }

    /**
     * Registrar rutas para la API REST
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // Ruta para obtener/listar clientes
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
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
                ),
            )
        );

        // Ruta para manipular cliente individual
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __('Identificador u00fanico del cliente.', 'wp-pos'),
                        'type'        => 'integer',
                        'required'    => true,
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
                ),
            )
        );

        // Ruta para grupos de clientes
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/groups',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_groups'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_group'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                ),
            )
        );

        // Ruta para manipular grupo individual
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/groups/(?P<id>[\d]+)',
            array(
                'args' => array(
                    'id' => array(
                        'description' => __('Identificador u00fanico del grupo.', 'wp-pos'),
                        'type'        => 'integer',
                        'required'    => true,
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_group'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_group'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
                ),
            )
        );
    }

    /**
     * Verificar permisos para obtener listado de clientes
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return bool|WP_Error True si tiene permiso, WP_Error en caso contrario
     */
    public function get_items_permissions_check($request) {
        if (!current_user_can('view_pos')) {
            return new WP_Error(
                'rest_forbidden',
                __('No tienes permisos para ver clientes.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }

        return true;
    }

    /**
     * Verificar permisos para obtener un cliente
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return bool|WP_Error True si tiene permiso, WP_Error en caso contrario
     */
    public function get_item_permissions_check($request) {
        if (!current_user_can('view_pos')) {
            return new WP_Error(
                'rest_forbidden',
                __('No tienes permisos para ver clientes.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }

        return true;
    }

    /**
     * Verificar permisos para crear un cliente
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return bool|WP_Error True si tiene permiso, WP_Error en caso contrario
     */
    public function create_item_permissions_check($request) {
        if (!current_user_can('manage_pos')) {
            return new WP_Error(
                'rest_forbidden',
                __('No tienes permisos para crear clientes.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }

        return true;
    }

    /**
     * Verificar permisos para actualizar un cliente
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return bool|WP_Error True si tiene permiso, WP_Error en caso contrario
     */
    public function update_item_permissions_check($request) {
        if (!current_user_can('manage_pos')) {
            return new WP_Error(
                'rest_forbidden',
                __('No tienes permisos para actualizar clientes.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }

        return true;
    }

    /**
     * Verificar permisos para eliminar un cliente
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return bool|WP_Error True si tiene permiso, WP_Error en caso contrario
     */
    public function delete_item_permissions_check($request) {
        if (!current_user_can('manage_pos')) {
            return new WP_Error(
                'rest_forbidden',
                __('No tienes permisos para eliminar clientes.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }

        return true;
    }

    /**
     * Obtener listado de clientes
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response Respuesta con los datos
     */
    public function get_items($request) {
        // Preparar paru00e1metros de bu00fasqueda
        $args = array(
            'search'   => $request->get_param('search'),
            'group'    => $request->get_param('group'),
            'page'     => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
            'orderby'  => $request->get_param('orderby'),
            'order'    => $request->get_param('order'),
        );

        // Obtener resultados
        $result = $this->controller->search_customers($args);

        // Construir respuesta
        $response = new WP_REST_Response($result);
        $response->set_status(200);

        return $response;
    }

    /**
     * Obtener datos de un cliente especu00edfico
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta con los datos o error
     */
    public function get_item($request) {
        $customer_id = $request->get_param('id');
        $customer = $this->controller->get_customer($customer_id);

        if (!$customer) {
            return new WP_Error(
                'pos_rest_customer_not_found',
                __('Cliente no encontrado.', 'wp-pos'),
                array('status' => 404)
            );
        }

        $response = new WP_REST_Response($customer);
        $response->set_status(200);

        return $response;
    }

    /**
     * Crear un nuevo cliente
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta con los datos o error
     */
    public function create_item($request) {
        // Obtener y preparar los datos
        $data = $this->prepare_item_for_database($request);

        // Crear cliente
        $result = $this->controller->create_customer($data);

        // Verificar resultado
        if (is_wp_error($result)) {
            return $result;
        }

        // Obtener datos del cliente creado
        $customer = $this->controller->get_customer($result);

        // Construir respuesta
        $response = new WP_REST_Response($customer);
        $response->set_status(201);

        return $response;
    }

    /**
     * Actualizar un cliente existente
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta con los datos o error
     */
    public function update_item($request) {
        $customer_id = $request->get_param('id');

        // Verificar que el cliente exista
        $customer = $this->controller->get_customer($customer_id);
        if (!$customer) {
            return new WP_Error(
                'pos_rest_customer_not_found',
                __('Cliente no encontrado.', 'wp-pos'),
                array('status' => 404)
            );
        }

        // Obtener y preparar los datos
        $data = $this->prepare_item_for_database($request);

        // Actualizar cliente
        $result = $this->controller->update_customer($customer_id, $data);

        // Verificar resultado
        if (is_wp_error($result)) {
            return $result;
        }

        // Obtener datos actualizados
        $customer = $this->controller->get_customer($customer_id);

        // Construir respuesta
        $response = new WP_REST_Response($customer);
        $response->set_status(200);

        return $response;
    }

    /**
     * Eliminar un cliente
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function delete_item($request) {
        $customer_id = $request->get_param('id');

        // Verificar que el cliente exista
        $customer = $this->controller->get_customer($customer_id);
        if (!$customer) {
            return new WP_Error(
                'pos_rest_customer_not_found',
                __('Cliente no encontrado.', 'wp-pos'),
                array('status' => 404)
            );
        }

        // En WordPress no se recomienda eliminar usuarios completamente
        // En su lugar, los marcamos como eliminados para POS
        update_user_meta($customer_id, '_wp_pos_deleted', 'yes');

        // Notificar
        do_action('wp_pos_customer_deleted', $customer_id);

        // Construir respuesta
        $response = new WP_REST_Response(array(
            'deleted'  => true,
            'message'   => __('Cliente eliminado correctamente.', 'wp-pos'),
        ));
        $response->set_status(200);

        return $response;
    }

    /**
     * Obtener grupos de clientes
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response Respuesta con los datos
     */
    public function get_groups($request) {
        $groups = $this->controller->get_customer_groups();

        $response = new WP_REST_Response($groups);
        $response->set_status(200);

        return $response;
    }

    /**
     * Crear un nuevo grupo de clientes
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta con los datos o error
     */
    public function create_group($request) {
        $name = $request->get_param('name');
        $description = $request->get_param('description');

        if (empty($name)) {
            return new WP_Error(
                'pos_rest_missing_name',
                __('El nombre del grupo es obligatorio.', 'wp-pos'),
                array('status' => 400)
            );
        }

        $result = $this->controller->create_customer_group($name, $description);

        if (is_wp_error($result)) {
            return $result;
        }

        // Obtener datos del grupo creado
        $groups = $this->controller->get_customer_groups();
        $group = array_filter($groups, function($item) use ($result) {
            return $item['id'] === $result;
        });

        $group = reset($group);

        $response = new WP_REST_Response($group);
        $response->set_status(201);

        return $response;
    }

    /**
     * Actualizar un grupo de clientes
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta con los datos o error
     */
    public function update_group($request) {
        $group_id = $request->get_param('id');
        $data = array();

        if ($request->has_param('name')) {
            $data['name'] = $request->get_param('name');
        }

        if ($request->has_param('description')) {
            $data['description'] = $request->get_param('description');
        }

        $result = $this->controller->update_customer_group($group_id, $data);

        if (is_wp_error($result)) {
            return $result;
        }

        // Obtener datos actualizados
        $groups = $this->controller->get_customer_groups();
        $group = array_filter($groups, function($item) use ($group_id) {
            return $item['id'] === (int) $group_id;
        });

        $group = reset($group);

        $response = new WP_REST_Response($group);
        $response->set_status(200);

        return $response;
    }

    /**
     * Eliminar un grupo de clientes
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return WP_REST_Response|WP_Error Respuesta o error
     */
    public function delete_group($request) {
        $group_id = $request->get_param('id');
        $result = $this->controller->delete_customer_group($group_id);

        if (is_wp_error($result)) {
            return $result;
        }

        $response = new WP_REST_Response(array(
            'deleted'  => true,
            'message'   => __('Grupo eliminado correctamente.', 'wp-pos'),
        ));
        $response->set_status(200);

        return $response;
    }

    /**
     * Preparar los datos del cliente para guardar en la base de datos
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la peticiu00f3n
     * @return array Datos preparados
     */
    protected function prepare_item_for_database($request) {
        $data = array();

        // Campos bu00e1sicos
        if ($request->has_param('email')) {
            $data['email'] = $request->get_param('email');
        }

        if ($request->has_param('username')) {
            $data['username'] = $request->get_param('username');
        }

        if ($request->has_param('password')) {
            $data['password'] = $request->get_param('password');
        }

        if ($request->has_param('first_name')) {
            $data['first_name'] = $request->get_param('first_name');
        }

        if ($request->has_param('last_name')) {
            $data['last_name'] = $request->get_param('last_name');
        }

        // Datos de facturaciu00f3n
        $billing_fields = array(
            'billing_phone', 'billing_company', 'billing_address_1',
            'billing_address_2', 'billing_city', 'billing_state',
            'billing_postcode', 'billing_country'
        );

        foreach ($billing_fields as $field) {
            if ($request->has_param($field)) {
                $data[$field] = $request->get_param($field);
            }
        }

        // Grupo y notas
        if ($request->has_param('group_id')) {
            $data['group_id'] = $request->get_param('group_id');
        }

        if ($request->has_param('notes')) {
            $data['notes'] = $request->get_param('notes');
        }

        // Metadatos adicionales
        if ($request->has_param('meta_data') && is_array($request->get_param('meta_data'))) {
            $data['meta_data'] = $request->get_param('meta_data');
        }

        return $data;
    }

    /**
     * Obtener paru00e1metros para las colecciones
     *
     * @since 1.0.0
     * @return array Paru00e1metros aceptados
     */
    public function get_collection_params() {
        $params = array(
            'page' => array(
                'description'       => __('Pu00e1gina actual de la colecciu00f3n.', 'wp-pos'),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ),
            'per_page' => array(
                'description'       => __('Mu00e1ximo nu00famero de elementos a devolver.', 'wp-pos'),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'search' => array(
                'description'       => __('Tu00e9rmino a buscar.', 'wp-pos'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'group' => array(
                'description'       => __('Filtrar por ID de grupo.', 'wp-pos'),
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'orderby' => array(
                'description'       => __('Ordenar la colecciu00f3n por objeto.', 'wp-pos'),
                'type'              => 'string',
                'enum'              => array('name', 'email', 'registered', 'orders', 'spent'),
                'default'           => 'name',
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'order' => array(
                'description'       => __('Orden de los resultados.', 'wp-pos'),
                'type'              => 'string',
                'enum'              => array('asc', 'desc', 'ASC', 'DESC'),
                'default'           => 'ASC',
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );

        return $params;
    }
}
