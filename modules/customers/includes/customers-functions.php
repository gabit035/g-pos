<?php
/**
 * Funciones auxiliares para el mu00f3dulo de clientes WP-POS
 *
 * Proporciona funciones de utilidad para trabajar con clientes
 * en cualquier parte del sistema.
 *
 * @package WP-POS
 * @subpackage Customers
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener un cliente por su ID
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @return array|false Datos del cliente o false si no existe
 */
function wp_pos_get_customer($customer_id) {
    $controller = new WP_POS_Customers_Controller();
    return $controller->get_customer($customer_id);
}

/**
 * Obtener listado de clientes
 *
 * @since 1.0.0
 * @param array $args Argumentos para la consulta
 * @return array Resultados de la bu00fasqueda
 */
function wp_pos_get_customers($args = array()) {
    $controller = new WP_POS_Customers_Controller();
    return $controller->search_customers($args);
}

/**
 * Crear un nuevo cliente
 *
 * @since 1.0.0
 * @param array $data Datos del cliente
 * @return int|WP_Error ID del cliente creado o error
 */
function wp_pos_create_customer($data) {
    $controller = new WP_POS_Customers_Controller();
    return $controller->create_customer($data);
}

/**
 * Actualizar un cliente existente
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @param array $data Nuevos datos
 * @return bool|WP_Error True si se actualizoF correctamente, WP_Error en caso de error
 */
function wp_pos_update_customer($customer_id, $data) {
    $controller = new WP_POS_Customers_Controller();
    return $controller->update_customer($customer_id, $data);
}

/**
 * Obtener cliente por defecto configurado en las opciones
 *
 * @since 1.0.0
 * @return array|false Datos del cliente por defecto o false si no hay
 */
function wp_pos_get_default_customer() {
    $default_customer_id = wp_pos_get_option('default_customer', 0);
    
    if (empty($default_customer_id)) {
        return false;
    }
    
    return wp_pos_get_customer($default_customer_id);
}

/**
 * Verificar si se requiere cliente para las ventas
 *
 * @since 1.0.0
 * @return bool True si se requiere cliente, false si es opcional
 */
function wp_pos_require_customer() {
    return 'yes' === wp_pos_get_option('require_customer', 'no');
}

/**
 * Obtener grupos de clientes
 *
 * @since 1.0.0
 * @return array Lista de grupos de clientes
 */
function wp_pos_get_customer_groups() {
    $controller = new WP_POS_Customers_Controller();
    return $controller->get_customer_groups();
}

/**
 * Crear un grupo de clientes
 *
 * @since 1.0.0
 * @param string $name Nombre del grupo
 * @param string $description Descripciu00f3n del grupo
 * @return int|WP_Error ID del grupo creado o error
 */
function wp_pos_create_customer_group($name, $description = '') {
    $controller = new WP_POS_Customers_Controller();
    return $controller->create_customer_group($name, $description);
}

/**
 * Obtener datos de facturacioFn de un cliente
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @return array Datos de facturacioFn
 */
function wp_pos_get_customer_billing($customer_id) {
    $customer = wp_pos_get_customer($customer_id);
    
    if (!$customer) {
        return array();
    }
    
    return isset($customer['billing']) ? $customer['billing'] : array();
}

/**
 * Obtener historial de compras de un cliente
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @param array $args Argumentos adicionales
 * @return array Historial de compras
 */
function wp_pos_get_customer_purchases($customer_id, $args = array()) {
    // Valores por defecto
    $defaults = array(
        'limit'      => 10,
        'offset'     => 0,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'return'     => 'objects', // 'objects' o 'ids'
        'status'     => array('completed', 'processing'),
    );
    
    // Fusionar con valores por defecto
    $args = wp_parse_args($args, $defaults);
    
    // Preparar argumentos para la consulta
    $query_args = array(
        'limit'      => $args['limit'],
        'offset'     => $args['offset'],
        'orderby'    => $args['orderby'],
        'order'      => $args['order'],
        'customer_id' => absint($customer_id),
        'status'     => $args['status'],
        'return'     => $args['return'],
    );
    
    // Obtener ventas del cliente
    if (function_exists('wp_pos_get_sales')) {
        return wp_pos_get_sales($query_args);
    }
    
    // Fallback si la funciu00f3n de ventas no estu00e1 disponible
    return array();
}

/**
 * Obtener estadu00edsticas de un cliente
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @return array Estadu00edsticas del cliente
 */
function wp_pos_get_customer_stats($customer_id) {
    $customer = wp_pos_get_customer($customer_id);
    
    if (!$customer || !isset($customer['stats'])) {
        return array(
            'total_spent'  => 0,
            'order_count'  => 0,
            'last_order'   => '',
            'average_spent' => 0,
        );
    }
    
    $stats = $customer['stats'];
    
    // Calcular promedio de gasto por orden
    $stats['average_spent'] = ($stats['order_count'] > 0) 
        ? ($stats['total_spent'] / $stats['order_count']) 
        : 0;
    
    return $stats;
}

/**
 * Obtener lista de los mejores clientes
 *
 * @since 1.0.0
 * @param array $args Argumentos adicionales
 * @return array Lista de mejores clientes
 */
function wp_pos_get_top_customers($args = array()) {
    // Valores por defecto
    $defaults = array(
        'limit'      => 5,
        'orderby'    => 'spent', // 'spent' o 'orders'
        'period'     => 'all', // 'all', 'year', 'month', 'week'
    );
    
    // Fusionar con valores por defecto
    $args = wp_parse_args($args, $defaults);
    
    // Preparar meta_key segu00fan criterio de ordenamiento
    $meta_key = '_wp_pos_total_spent';
    if ($args['orderby'] === 'orders') {
        $meta_key = '_wp_pos_order_count';
    }
    
    // Preparar argumentos para WP_User_Query
    $query_args = array(
        'number'      => absint($args['limit']),
        'orderby'     => 'meta_value_num',
        'order'       => 'DESC',
        'meta_key'    => $meta_key,
        'meta_query'  => array(
            array(
                'key'     => $meta_key,
                'compare' => 'EXISTS',
            ),
        ),
    );
    
    // Filtrar por peru00edodo si no es 'all'
    if ($args['period'] !== 'all') {
        $date = new DateTime();
        
        switch ($args['period']) {
            case 'year':
                $date->modify('-1 year');
                break;
            case 'month':
                $date->modify('-1 month');
                break;
            case 'week':
                $date->modify('-1 week');
                break;
        }
        
        $query_args['meta_query'][] = array(
            'key'     => '_wp_pos_last_order',
            'value'   => $date->format('Y-m-d H:i:s'),
            'compare' => '>=',
            'type'    => 'DATETIME',
        );
    }
    
    // Ejecutar consulta
    $user_query = new WP_User_Query($query_args);
    $users = $user_query->get_results();
    
    // Formatear resultados
    $customers = array();
    $controller = new WP_POS_Customers_Controller();
    
    foreach ($users as $user) {
        $customers[] = $controller->format_customer_data($user);
    }
    
    return $customers;
}

/**
 * Renderizar selector de clientes para el POS
 *
 * @since 1.0.0
 * @param array $args Argumentos adicionales
 */
function wp_pos_customer_selector($args = array()) {
    // Valores por defecto
    $defaults = array(
        'selected'    => 0,
        'placeholder' => __('Buscar o crear cliente...', 'wp-pos'),
        'id'          => 'wp-pos-customer-selector',
        'name'        => 'customer_id',
        'class'       => 'wp-pos-customer-selector',
        'allow_create' => true,
    );
    
    // Fusionar con valores por defecto
    $args = wp_parse_args($args, $defaults);
    
    // Obtener cliente seleccionado si existe
    $selected_customer = null;
    if (!empty($args['selected'])) {
        $selected_customer = wp_pos_get_customer($args['selected']);
    }
    
    // Cargar plantilla
    wp_pos_load_template('customer-selector', array(
        'args'      => $args,
        'selected'  => $selected_customer,
    ));
}

/**
 * Verificar si un usuario es un cliente del POS
 *
 * @since 1.0.0
 * @param int $user_id ID del usuario
 * @return bool True si es cliente POS, false en caso contrario
 */
function wp_pos_is_pos_customer($user_id) {
    // Verificar si tiene al menos una compra en POS
    $order_count = get_user_meta($user_id, '_wp_pos_order_count', true);
    
    if (!empty($order_count) && intval($order_count) > 0) {
        return true;
    }
    
    // Verificar si estu00e1 marcado como cliente de POS
    $is_pos_customer = get_user_meta($user_id, '_wp_pos_customer', true);
    
    return !empty($is_pos_customer) && $is_pos_customer === 'yes';
}

/**
 * Registrar un usuario existente como cliente de POS
 *
 * @since 1.0.0
 * @param int $user_id ID del usuario
 * @return bool True si se registru00f3 correctamente
 */
function wp_pos_register_as_pos_customer($user_id) {
    $user_id = absint($user_id);
    
    if (empty($user_id) || !get_user_by('id', $user_id)) {
        return false;
    }
    
    // Marcar como cliente de POS
    update_user_meta($user_id, '_wp_pos_customer', 'yes');
    
    // Inicializar estadu00edsticas si no existen
    if ('' === get_user_meta($user_id, '_wp_pos_total_spent', true)) {
        update_user_meta($user_id, '_wp_pos_total_spent', '0');
    }
    
    if ('' === get_user_meta($user_id, '_wp_pos_order_count', true)) {
        update_user_meta($user_id, '_wp_pos_order_count', '0');
    }
    
    // Notificar
    do_action('wp_pos_user_registered_as_customer', $user_id);
    
    return true;
}

/**
 * Buscar clientes por tu00e9rmino de bu00fasqueda
 *
 * Versiu00f3n simplificada para usar en selectors, autocompletado, etc.
 *
 * @since 1.0.0
 * @param string $term Tu00e9rmino de bu00fasqueda
 * @param int $limit Lu00edmite de resultados
 * @return array Resultados de la bu00fasqueda
 */
function wp_pos_search_customers_simple($term, $limit = 10) {
    $controller = new WP_POS_Customers_Controller();
    $results = $controller->search_customers(array(
        'search'   => $term,
        'per_page' => $limit,
    ));
    
    // Dar formato simple para selectores
    $customers = array();
    
    foreach ($results['customers'] as $customer) {
        $customers[] = array(
            'id'    => $customer['id'],
            'text'  => sprintf('%s (%s)', $customer['full_name'], $customer['email']),
            'name'  => $customer['full_name'],
            'email' => $customer['email'],
        );
    }
    
    return $customers;
}

/**
 * Comprobar si un cliente debe pagar impuestos
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @return bool True si debe pagar impuestos
 */
function wp_pos_customer_pays_tax($customer_id) {
    // Verificar si los impuestos estu00e1n habilitados globalmente
    if (!wp_pos_tax_enabled()) {
        return false;
    }
    
    // Comprobar exenciu00f3n de impuestos para el cliente
    $tax_exempt = get_user_meta($customer_id, '_wp_pos_tax_exempt', true);
    
    return $tax_exempt !== 'yes';
}

/**
 * Obtener el descuento asignado a un cliente
 *
 * @since 1.0.0
 * @param int $customer_id ID del cliente
 * @return float Porcentaje de descuento
 */
function wp_pos_get_customer_discount($customer_id) {
    $discount = get_user_meta($customer_id, '_wp_pos_customer_discount', true);
    
    if ('' === $discount) {
        // Verificar descuento de grupo
        $group_id = get_user_meta($customer_id, '_wp_pos_customer_group', true);
        
        if (!empty($group_id)) {
            $discount = get_term_meta($group_id, '_wp_pos_group_discount', true);
        }
    }
    
    return !empty($discount) ? floatval($discount) : 0;
}

/**
 * Registrar la pu00e1gina de administraciu00f3n de clientes en el menu00fa de WordPress
 *
 * @since 1.0.0
 */
function wp_pos_register_customers_page() {
    add_submenu_page(
        'wp-pos', // Parent slug
        __('Clientes', 'wp-pos'), // Pu00e1gina tu00edtulo
        __('Clientes', 'wp-pos'), // Menu00fa tu00edtulo
        'view_pos', // Capacidad requerida (cambiado de manage_woocommerce)
        'wp-pos-customers', // Slug de la pu00e1gina
        'wp_pos_render_customers_page' // Funciu00f3n de callback
    );
}

/**
 * Renderizar la pu00e1gina de administraciu00f3n de clientes
 *
 * @since 1.0.0
 */
function wp_pos_render_customers_page() {
    // Cargar plantilla de clientes
    require_once WP_POS_PLUGIN_DIR . 'templates/admin-customers.php';
}

// Eliminamos este registro para evitar duplicación del menú
// Esta página ya está registrada en bootstrap.php
// add_action('admin_menu', 'wp_pos_register_customers_page', 20);
