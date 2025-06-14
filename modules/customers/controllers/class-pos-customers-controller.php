<?php
/**
 * Controlador de Clientes para WP-POS
 *
 * Gestiona la lógica de negocio relacionada con clientes,
 * incluyendo búsqueda, creación y actualización de datos.
 *
 * @package WP-POS
 * @subpackage Customers
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase controladora del módulo de clientes
 *
 * @since 1.0.0
 */
class WP_POS_Customers_Controller {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Constructor vacío, inicialización bajo demanda
    }

    /**
     * Buscar clientes según criterios
     *
     * @since 1.0.0
     * @param array $args Argumentos de búsqueda
     *     @type string $search Término de búsqueda (nombre, email, etc.)
     *     @type int    $group ID del grupo de clientes
     *     @type int    $page Número de página actual
     *     @type int    $per_page Resultados por página
     *     @type string $orderby Campo para ordenar
     *     @type string $order Dirección de ordenamiento (ASC/DESC)
     * @return array Resultados de la búsqueda
     */
    public function search_customers($args = array()) {
        // Valores por defecto
        $defaults = array(
            'search'   => '',
            'group'    => 0,
            'page'     => 1,
            'per_page' => 20,
            'orderby'  => 'name',
            'order'    => 'ASC',
        );
        
        // Fusionar con valores por defecto
        $args = wp_parse_args($args, $defaults);
        
        // Preparar argumentos para WP_User_Query
        $query_args = array(
            'number'    => $args['per_page'],
            'paged'     => $args['page'],
            'orderby'   => $args['orderby'],
            'order'     => $args['order'],
            'fields'    => 'all_with_meta',
            'meta_query' => array('relation' => 'AND'),
            // Filtrar para mostrar solo usuarios con rol de cliente
            'role__in' => array('pos_customer', 'customer'), // Incluir ambos roles para compatibilidad
            'role__not_in' => array('administrator', 'pos_manager', 'pos_seller', 'editor', 'author', 'contributor'),
        );
        
        // Agregar búsqueda si existe
        if (!empty($args['search'])) {
            $query_args['search'] = '*' . esc_attr($args['search']) . '*';
            $query_args['search_columns'] = array('user_login', 'user_email', 'user_nicename', 'display_name');
            
            // También buscar por meta
            $query_args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'first_name',
                    'value'   => $args['search'],
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => 'last_name',
                    'value'   => $args['search'],
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => 'billing_phone',
                    'value'   => $args['search'],
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => 'billing_company',
                    'value'   => $args['search'],
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => 'billing_address_1',
                    'value'   => $args['search'],
                    'compare' => 'LIKE',
                ),
            );
        }
        
        // Filtrar por grupo si se especifica
        if (!empty($args['group'])) {
            $query_args['meta_query'][] = array(
                'key'     => '_wp_pos_customer_group',
                'value'   => absint($args['group']),
                'compare' => '=',
            );
        }
        
        // Ejecutar la consulta
        $user_query = new WP_User_Query($query_args);
        
        // Obtener resultados
        $users = $user_query->get_results();
        $total = $user_query->get_total();
        
        // Dar formato a los resultados
        $customers = array();
        
        foreach ($users as $user) {
            $customers[] = $this->format_customer_data($user);
        }
        
        // Retornar resultados con metadatos
        return array(
            'customers' => $customers,
            'total'     => $total,
            'pages'     => ceil($total / $args['per_page']),
            'page'      => $args['page'],
            'per_page'  => $args['per_page'],
        );
    }

    /**
     * Obtener datos de un cliente específico
     *
     * @since 1.0.0
     * @param int $customer_id ID del cliente
     * @return array|false Datos del cliente o false si no existe
     */
    public function get_customer($customer_id) {
        // Validar ID
        $customer_id = absint($customer_id);
        if (empty($customer_id)) {
            return false;
        }
        
        // Obtener usuario
        $user = get_user_by('id', $customer_id);
        if (!$user) {
            return false;
        }
        
        // Formatear datos
        return $this->format_customer_data($user);
    }

    /**
     * Crear un nuevo cliente
     *
     * @since 1.0.0
     * @param array $data Datos del cliente
     * @return int|WP_Error ID del cliente creado o error
     */
    public function create_customer($data) {
        // Validar datos requeridos
        if (empty($data['email'])) {
            return new WP_Error('missing_email', __('El correo electrónico es obligatorio.', 'wp-pos'));
        }
        
        // Verificar si el email ya existe
        if (email_exists($data['email'])) {
            return new WP_Error('email_exists', __('Ya existe un cliente con este correo electrónico.', 'wp-pos'));
        }
        
        // Generar username si no se proporciona
        if (empty($data['username'])) {
            $username = sanitize_user(current(explode('@', $data['email'])), true);
            
            // Asegurar que el username sea único
            $counter = 1;
            $new_username = $username;
            
            while (username_exists($new_username)) {
                $new_username = $username . $counter;
                $counter++;
            }
            
            $username = $new_username;
        } else {
            $username = sanitize_user($data['username']);
            
            // Verificar si el username ya existe
            if (username_exists($username)) {
                return new WP_Error('username_exists', __('Ya existe un cliente con este nombre de usuario.', 'wp-pos'));
            }
        }
        
        // Generar contraseña aleatoria si no se proporciona
        $password = empty($data['password']) ? wp_generate_password(12, true, true) : $data['password'];
        
        // Datos para crear el usuario
        $user_data = array(
            'user_login' => $username,
            'user_email' => sanitize_email($data['email']),
            'user_pass'  => $password,
            'first_name' => isset($data['first_name']) ? sanitize_text_field($data['first_name']) : '',
            'last_name'  => isset($data['last_name']) ? sanitize_text_field($data['last_name']) : '',
            'role'       => 'customer',
        );
        
        // Crear el usuario
        $customer_id = wp_insert_user($user_data);
        
        // Verificar resultado
        if (is_wp_error($customer_id)) {
            return $customer_id;
        }
        
        // Guardar metadatos adicionales
        $this->save_customer_meta($customer_id, $data);
        
        // Notificar
        do_action('wp_pos_customer_created', $customer_id, $data);
        
        // Devolver ID del nuevo cliente
        return $customer_id;
    }

    /**
     * Actualizar datos de un cliente existente
     *
     * @since 1.0.0
     * @param int $customer_id ID del cliente
     * @param array $data Nuevos datos
     * @return bool|WP_Error True si se actualizó correctamente, WP_Error en caso de error
     */
    public function update_customer($customer_id, $data) {
        // Validar ID
        $customer_id = absint($customer_id);
        if (empty($customer_id)) {
            return new WP_Error('invalid_id', __('ID de cliente no válido.', 'wp-pos'));
        }
        
        // Verificar que el cliente exista
        $user = get_user_by('id', $customer_id);
        if (!$user) {
            return new WP_Error('customer_not_found', __('Cliente no encontrado.', 'wp-pos'));
        }
        
        // Datos para actualizar
        $user_data = array('ID' => $customer_id);
        
        // Actualizar email si se proporciona y es diferente
        if (!empty($data['email']) && $data['email'] !== $user->user_email) {
            // Verificar si el nuevo email ya existe
            if (email_exists($data['email']) && email_exists($data['email']) != $customer_id) {
                return new WP_Error('email_exists', __('Ya existe un cliente con este correo electrónico.', 'wp-pos'));
            }
            
            $user_data['user_email'] = sanitize_email($data['email']);
        }
        
        // Actualizar username si se proporciona y es diferente
        if (!empty($data['username']) && $data['username'] !== $user->user_login) {
            // Verificar si el nuevo username ya existe
            if (username_exists($data['username']) && username_exists($data['username']) != $customer_id) {
                return new WP_Error('username_exists', __('Ya existe un cliente con este nombre de usuario.', 'wp-pos'));
            }
            
            $user_data['user_login'] = sanitize_user($data['username']);
        }
        
        // Actualizar nombre y apellido si se proporcionan
        if (isset($data['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($data['last_name']);
        }
        
        // Actualizar contraseña si se proporciona
        if (!empty($data['password'])) {
            $user_data['user_pass'] = $data['password'];
        }
        
        // Actualizar usuario
        if (count($user_data) > 1) { // Si hay más datos además del ID
            $result = wp_update_user($user_data);
            
            if (is_wp_error($result)) {
                return $result;
            }
        }
        
        // Guardar metadatos adicionales
        $this->save_customer_meta($customer_id, $data);
        
        // Notificar
        do_action('wp_pos_customer_updated', $customer_id, $data);
        
        return true;
    }

    /**
     * Guardar metadatos del cliente
     *
     * @since 1.0.0
     * @param int $customer_id ID del cliente
     * @param array $data Datos del cliente
     */
    private function save_customer_meta($customer_id, $data) {
        // Datos de facturación
        $billing_fields = array(
            'billing_phone', 'billing_company', 'billing_address_1',
            'billing_address_2', 'billing_city', 'billing_state',
            'billing_postcode', 'billing_country'
        );
        
        foreach ($billing_fields as $field) {
            if (isset($data[$field])) {
                update_user_meta($customer_id, $field, sanitize_text_field($data[$field]));
            }
        }
        
        // Grupo de cliente
        if (isset($data['group_id'])) {
            update_user_meta($customer_id, '_wp_pos_customer_group', absint($data['group_id']));
        }
        
        // Notas
        if (isset($data['notes'])) {
            update_user_meta($customer_id, '_wp_pos_customer_notes', sanitize_textarea_field($data['notes']));
        }
        
        // Datos adicionales (metadatos personalizados)
        if (!empty($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta_key => $meta_value) {
                // Asegurar que las claves de meta empiecen con _wp_pos_
                if (strpos($meta_key, '_wp_pos_') !== 0) {
                    $meta_key = '_wp_pos_' . $meta_key;
                }
                
                update_user_meta($customer_id, $meta_key, $meta_value);
            }
        }
        
        // Permitir hooks personalizados
        do_action('wp_pos_save_customer_meta', $customer_id, $data);
    }

    /**
     * Formatear datos del cliente para uso en API y frontend
     *
     * @since 1.0.0
     * @param WP_User $user Objeto de usuario
     * @return array Datos formateados
     */
    private function format_customer_data($user) {
        // Datos básicos
        $customer = array(
            'id'         => $user->ID,
            'username'   => $user->user_login,
            'email'      => $user->user_email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'full_name'  => trim(sprintf('%s %s', $user->first_name, $user->last_name)),
            'avatar_url' => get_avatar_url($user->ID),
            'date_created' => $user->user_registered,
        );
        
        // Si el nombre completo está vacío, usar el email o username
        if (empty($customer['full_name'])) {
            $customer['full_name'] = $user->user_email;
        }
        
        // Datos de facturación
        $billing_fields = array(
            'billing_phone', 'billing_company', 'billing_address_1',
            'billing_address_2', 'billing_city', 'billing_state',
            'billing_postcode', 'billing_country'
        );
        
        $customer['billing'] = array();
        foreach ($billing_fields as $field) {
            $customer['billing'][str_replace('billing_', '', $field)] = get_user_meta($user->ID, $field, true);
        }
        
        // Estadísticas de compras
        $customer['stats'] = array(
            'total_spent'  => (float) get_user_meta($user->ID, '_wp_pos_total_spent', true),
            'order_count'  => (int) get_user_meta($user->ID, '_wp_pos_order_count', true),
            'last_order'   => get_user_meta($user->ID, '_wp_pos_last_order', true),
        );
        
        // Grupo de cliente
        $group_id = (int) get_user_meta($user->ID, '_wp_pos_customer_group', true);
        $customer['group'] = array(
            'id'   => $group_id,
            'name' => $group_id ? $this->get_group_name($group_id) : '',
        );
        
        // Notas
        $customer['notes'] = get_user_meta($user->ID, '_wp_pos_customer_notes', true);
        
        // Permitir filtrar datos
        return apply_filters('wp_pos_format_customer_data', $customer, $user);
    }

    /**
     * Obtener nombre de un grupo de clientes
     *
     * @since 1.0.0
     * @param int $group_id ID del grupo
     * @return string Nombre del grupo
     */
    private function get_group_name($group_id) {
        $group = get_term($group_id, 'wp_pos_customer_group');
        if ($group && !is_wp_error($group)) {
            return $group->name;
        }
        return '';
    }

    /**
     * Obtener grupos de clientes
     *
     * @since 1.0.0
     * @return array Lista de grupos
     */
    public function get_customer_groups() {
        $groups = array();
        
        // Obtener términos de taxonomía
        $terms = get_terms(array(
            'taxonomy'   => 'wp_pos_customer_group',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $groups[] = array(
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'count' => $term->count,
                    'description' => $term->description,
                );
            }
        }
        
        return $groups;
    }

    /**
     * Crear un grupo de clientes
     *
     * @since 1.0.0
     * @param string $name Nombre del grupo
     * @param string $description Descripción del grupo
     * @return int|WP_Error ID del grupo o error
     */
    public function create_customer_group($name, $description = '') {
        if (empty($name)) {
            return new WP_Error('missing_name', __('El nombre del grupo es obligatorio.', 'wp-pos'));
        }
        
        $result = wp_insert_term(
            sanitize_text_field($name),
            'wp_pos_customer_group',
            array('description' => sanitize_textarea_field($description))
        );
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return $result['term_id'];
    }

    /**
     * Actualizar un grupo de clientes
     *
     * @since 1.0.0
     * @param int $group_id ID del grupo
     * @param array $data Datos a actualizar
     * @return bool|WP_Error True si éxito, error en caso contrario
     */
    public function update_customer_group($group_id, $data) {
        $args = array();
        
        if (isset($data['name'])) {
            $args['name'] = sanitize_text_field($data['name']);
        }
        
        if (isset($data['description'])) {
            $args['description'] = sanitize_textarea_field($data['description']);
        }
        
        // Si no hay datos para actualizar
        if (empty($args)) {
            return true;
        }
        
        $result = wp_update_term(
            absint($group_id),
            'wp_pos_customer_group',
            $args
        );
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }

    /**
     * Eliminar un grupo de clientes
     *
     * @since 1.0.0
     * @param int $group_id ID del grupo
     * @return bool|WP_Error True si éxito, error en caso contrario
     */
    public function delete_customer_group($group_id) {
        $result = wp_delete_term(
            absint($group_id),
            'wp_pos_customer_group'
        );
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return true;
    }
}
