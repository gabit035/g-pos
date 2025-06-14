<?php
/**
 * Controlador base para las rutas de REST API del plugin WP-POS
 *
 * Proporciona funcionalidades comunes para todos los endpoints REST del sistema.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase base para controladores REST de WP-POS
 *
 * @since 1.0.0
 */
class WP_POS_REST_Controller extends WP_REST_Controller {

    /**
     * Espacio de nombres para la API
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
    protected $rest_base = '';

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Acciones y filtros comu00fanes para los controladores REST
    }

    /**
     * Registrar rutas
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // A implementar en clases hijas
    }

    /**
     * Comprobar permisos para lectura
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool|WP_Error True si tiene permiso, WP_Error si no
     */
    public function get_items_permissions_check($request) {
        if (!current_user_can('view_pos')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('No tienes permisos para ver este recurso.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Comprobar permisos para lectura de un item
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool|WP_Error True si tiene permiso, WP_Error si no
     */
    public function get_item_permissions_check($request) {
        if (!current_user_can('view_pos')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('No tienes permisos para ver este recurso.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Comprobar permisos para creacin de items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool|WP_Error True si tiene permiso, WP_Error si no
     */
    public function create_item_permissions_check($request) {
        if (!current_user_can('manage_pos')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('No tienes permisos para crear este recurso.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Comprobar permisos para actualizacin de items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool|WP_Error True si tiene permiso, WP_Error si no
     */
    public function update_item_permissions_check($request) {
        if (!current_user_can('manage_pos')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('No tienes permisos para actualizar este recurso.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Comprobar permisos para eliminacin de items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Datos de la solicitud
     * @return bool|WP_Error True si tiene permiso, WP_Error si no
     */
    public function delete_item_permissions_check($request) {
        if (!current_user_can('manage_pos')) {
            return new WP_Error(
                'wp_pos_rest_forbidden',
                __('No tienes permisos para eliminar este recurso.', 'wp-pos'),
                array('status' => rest_authorization_required_code())
            );
        }
        return true;
    }

    /**
     * Validar un parmetro de solicitud como entero positivo
     *
     * @since 1.0.0
     * @param mixed $value Valor a validar
     * @param WP_REST_Request $request Solicitud actual
     * @param string $param Nombre del parmetro
     * @return true|WP_Error True si es vlido, WP_Error si no
     */
    public function validate_positive_integer($value, $request, $param) {
        $value = (int) $value;
        
        if ($value <= 0) {
            return new WP_Error(
                'rest_invalid_param',
                sprintf(
                    __('%s debe ser un entero positivo.', 'wp-pos'),
                    $param
                ),
                array('status' => 400)
            );
        }
        
        return true;
    }

    /**
     * Sanitizar un parmetro a entero positivo
     *
     * @since 1.0.0
     * @param mixed $value Valor a sanitizar
     * @param WP_REST_Request $request Solicitud actual
     * @param string $param Nombre del parmetro
     * @return int Valor sanitizado
     */
    public function sanitize_positive_integer($value, $request, $param) {
        return (int) max(1, $value);
    }

    /**
     * Preparar un objeto para respuesta REST
     *
     * @since 1.0.0
     * @param mixed $item Objeto o array a preparar
     * @param WP_REST_Request $request Objeto de solicitud
     * @return WP_REST_Response Respuesta preparada
     */
    public function prepare_item_for_response($item, $request) {
        // A implementar en clases hijas
        return rest_ensure_response($item);
    }

    /**
     * Preparar enlaces para la respuesta
     *
     * @since 1.0.0
     * @param mixed $item Objeto o array cuyos enlaces se preparan
     * @return array Enlaces HATEOAS
     */
    protected function prepare_links($item) {
        // A implementar en clases hijas
        return array();
    }

    /**
     * Obtener el esquema del item
     *
     * @since 1.0.0
     * @return array Esquema del objeto
     */
    public function get_item_schema() {
        // A implementar en clases hijas
        return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'wp_pos_generic_item',
            'type'       => 'object',
            'properties' => array(
                'id' => array(
                    'description' => __('Identificador único del objeto.', 'wp-pos'),
                    'type'        => 'integer',
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
            ),
        );
    }

    /**
     * Obtener la coleccin de parmetros
     *
     * @since 1.0.0
     * @return array Definicin de parmetros de coleccin
     */
    public function get_collection_params() {
        return array(
            'page' => array(
                'description'       => __('Nmero de pgina actual.', 'wp-pos'),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
                'minimum'           => 1,
            ),
            'per_page' => array(
                'description'       => __('Mximo nmero de items a retornar por pgina.', 'wp-pos'),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'search' => array(
                'description'       => __('Limitar resultados a los que coincidan con el trmino de bsqueda.', 'wp-pos'),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'orderby' => array(
                'description'       => __('Campo por el cual ordenar los resultados.', 'wp-pos'),
                'type'              => 'string',
                'default'           => 'id',
                'enum'              => array('id', 'date', 'title', 'name', 'modified'),
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'order' => array(
                'description'       => __('Orden de los resultados.', 'wp-pos'),
                'type'              => 'string',
                'default'           => 'desc',
                'enum'              => array('asc', 'desc'),
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }

    /**
     * Normalizar parmetros de consulta
     *
     * @since 1.0.0
     * @param array $args Argumentos originales
     * @param WP_REST_Request $request Objeto de solicitud
     * @return array Argumentos normalizados
     */
    protected function prepare_query_args($args, $request) {
        // Pgina y por pgina
        if (!empty($request['page'])) {
            $args['page'] = $request['page'];
        }

        if (!empty($request['per_page'])) {
            $args['per_page'] = $request['per_page'];
        }

        // Bsqueda
        if (!empty($request['search'])) {
            $args['search'] = $request['search'];
        }

        // Ordenamiento
        if (!empty($request['orderby'])) {
            $args['orderby'] = $request['orderby'];
        }

        if (!empty($request['order'])) {
            $args['order'] = strtoupper($request['order']);
        }

        return $args;
    }

    /**
     * Preparar encabezados de paginacin
     *
     * @since 1.0.0
     * @param WP_REST_Response $response Objeto de respuesta
     * @param array $args Argumentos usados para generar los items
     * @param int $total_items Nmero total de items
     * @return WP_REST_Response Respuesta con headers de paginacin
     */
    protected function add_pagination_headers($response, $args, $total_items) {
        $per_page = isset($args['per_page']) ? (int) $args['per_page'] : 10;
        $page = isset($args['page']) ? (int) $args['page'] : 1;
        
        $total_pages = ceil($total_items / $per_page);
        
        if ($total_pages > 0) {
            $response->header('X-WP-Total', $total_items);
            $response->header('X-WP-TotalPages', $total_pages);
        }
        
        return $response;
    }
}
