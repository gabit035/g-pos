<?php
/**
 * Funciones de template para el plugin WP-POS
 *
 * Proporciona funciones para renderizar interfaces y elementos visuales.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderizar el header del panel POS
 *
 * @since 1.0.0
 * @param array $args Argumentos adicionales
 */
function wp_pos_template_header($args = array()) {
    $defaults = array(
        'title' => __('Punto de Venta', 'wp-pos'),
        'show_menu' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    wp_pos_load_template('header', $args);
}

/**
 * Renderizar el footer del panel POS
 *
 * @since 1.0.0
 * @param array $args Argumentos adicionales
 */
function wp_pos_template_footer($args = array()) {
    wp_pos_load_template('footer', $args);
}

/**
 * Renderizar el menú principal del POS
 *
 * @since 1.0.0
 * @param string $active_item Item actualmente activo
 */
function wp_pos_template_main_menu($active_item = '') {
    $menu_items = array(
        'dashboard' => array(
            'title' => __('Dashboard', 'wp-pos'),
            'icon' => 'dashicons-dashboard',
            'url' => wp_pos_get_admin_url(),
        ),
        'sales' => array(
            'title' => __('Ventas', 'wp-pos'),
            'icon' => 'dashicons-cart',
            'url' => wp_pos_get_admin_url('sales'),
        ),
        'products' => array(
            'title' => __('Productos', 'wp-pos'),
            'icon' => 'dashicons-products',
            'url' => wp_pos_get_admin_url('products'),
        ),
        'customers' => array(
            'title' => __('Clientes', 'wp-pos'),
            'icon' => 'dashicons-groups',
            'url' => wp_pos_get_admin_url('customers'),
        ),
        'reports' => array(
            'title' => __('Reportes', 'wp-pos'),
            'icon' => 'dashicons-chart-bar',
            'url' => wp_pos_get_admin_url('reports'),
        ),
        'settings' => array(
            'title' => __('Configuración', 'wp-pos'),
            'icon' => 'dashicons-admin-settings',
            'url' => wp_pos_get_admin_url('settings'),
        ),
    );
    
    // Permitir modificar menú
    $menu_items = apply_filters('wp_pos_admin_menu_items', $menu_items);
    
    wp_pos_load_template('menu', array(
        'menu_items' => $menu_items,
        'active_item' => $active_item,
    ));
}

/**
 * Renderizar un mensaje de aviso
 *
 * @since 1.0.0
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param bool $dismissible Si es descartable o no
 */
function wp_pos_template_notice($message, $type = 'info', $dismissible = true) {
    wp_pos_load_template('notice', array(
        'message' => $message,
        'type' => $type,
        'dismissible' => $dismissible,
    ));
}

/**
 * Renderizar un cuadro de estadísticas
 *
 * @since 1.0.0
 * @param array $args Configuración del cuadro
 */
function wp_pos_template_stat_box($args) {
    $defaults = array(
        'title' => '',
        'value' => '',
        'icon' => 'dashicons-chart-bar',
        'color' => 'blue',
        'link' => '',
        'link_text' => __('Ver detalles', 'wp-pos'),
    );
    
    $args = wp_parse_args($args, $defaults);
    
    wp_pos_load_template('stat-box', $args);
}

/**
 * Renderizar un panel de datos
 *
 * @since 1.0.0
 * @param array $args Configuración del panel
 */
function wp_pos_template_panel($args) {
    $defaults = array(
        'title' => '',
        'content' => '',
        'icon' => '',
        'class' => '',
        'footer' => '',
        'collapsible' => false,
    );
    
    $args = wp_parse_args($args, $defaults);
    
    wp_pos_load_template('panel', $args);
}

/**
 * Renderizar un formulario de venta
 *
 * @since 1.0.0
 * @param array $args Configuración del formulario
 */
function wp_pos_template_sale_form($args = array()) {
    $defaults = array(
        'products' => array(),
        'customers' => array(),
        'payment_methods' => wp_pos_get_payment_methods(),
    );
    
    $args = wp_parse_args($args, $defaults);
    
    wp_pos_load_template('sale-form', $args);
}

/**
 * Renderizar un recibo de venta
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @param array $args Argumentos adicionales
 */
function wp_pos_template_receipt($sale_id, $args = array()) {
    // Obtener detalles de la venta
    $sale = wp_pos_get_sale($sale_id);
    
    if (!$sale) {
        wp_pos_template_notice(
            __('Venta no encontrada.', 'wp-pos'),
            'error'
        );
        return;
    }
    
    $defaults = array(
        'show_header' => true,
        'show_footer' => true,
        'print_button' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    $args['sale'] = $sale;
    
    // Obtener ítems de la venta
    $args['items'] = wp_pos_get_sale_items($sale_id);
    
    // Obtener pagos de la venta
    $args['payments'] = wp_pos_get_sale_payments($sale_id);
    
    // Cargar template según configuración
    $receipt_template = wp_pos_get_option('receipt_template', 'default');
    $template_name = 'receipt-' . $receipt_template;
    
    wp_pos_load_template($template_name, $args);
}

/**
 * Renderizar tabla de productos
 *
 * @since 1.0.0
 * @param array $products Lista de productos
 * @param array $args Argumentos adicionales
 */
function wp_pos_template_products_table($products, $args = array()) {
    $defaults = array(
        'show_actions' => true,
        'show_stock' => true,
        'show_price' => true,
        'show_category' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    $args['products'] = $products;
    
    wp_pos_load_template('products-table', $args);
}

/**
 * Renderizar tabla de ventas
 *
 * @since 1.0.0
 * @param array $sales Lista de ventas
 * @param array $args Argumentos adicionales
 */
function wp_pos_template_sales_table($sales, $args = array()) {
    $defaults = array(
        'show_actions' => true,
        'show_status' => true,
        'show_customer' => true,
        'show_payment' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    $args['sales'] = $sales;
    
    wp_pos_load_template('sales-table', $args);
}

/**
 * Renderizar gráfico de datos
 *
 * @since 1.0.0
 * @param array $data Datos para el gráfico
 * @param string $type Tipo de gráfico (bar, line, pie)
 * @param array $args Argumentos adicionales
 */
function wp_pos_template_chart($data, $type = 'bar', $args = array()) {
    $defaults = array(
        'title' => '',
        'height' => 300,
        'width' => '100%',
        'colors' => array('#3366cc', '#dc3912', '#ff9900', '#109618', '#990099'),
        'legend' => true,
    );
    
    $args = wp_parse_args($args, $defaults);
    $args['data'] = $data;
    $args['type'] = $type;
    
    // Asignar ID único para el gráfico
    if (!isset($args['id'])) {
        $args['id'] = 'wp-pos-chart-' . wp_rand();
    }
    
    wp_pos_load_template('chart', $args);
}

/**
 * Renderizar una lista de paginación
 *
 * @since 1.0.0
 * @param int $current_page Página actual
 * @param int $total_pages Total de páginas
 * @param string $base_url URL base
 * @param array $args Argumentos adicionales
 */
function wp_pos_template_pagination($current_page, $total_pages, $base_url, $args = array()) {
    $defaults = array(
        'prev_text' => __('&laquo; Anterior', 'wp-pos'),
        'next_text' => __('Siguiente &raquo;', 'wp-pos'),
        'mid_size' => 2,
        'end_size' => 1,
        'add_fragment' => '',
    );
    
    $args = wp_parse_args($args, $defaults);
    $args['current_page'] = $current_page;
    $args['total_pages'] = $total_pages;
    $args['base_url'] = $base_url;
    
    wp_pos_load_template('pagination', $args);
}

/**
 * Renderizar estado de venta con formato
 *
 * @since 1.0.0
 * @param string $status Código de estado
 * @return string HTML del estado formateado
 */
function wp_pos_format_sale_status($status) {
    $statuses = wp_pos_get_sale_statuses();
    $label = isset($statuses[$status]) ? $statuses[$status] : $status;
    
    $class = 'wp-pos-status wp-pos-status-' . sanitize_html_class($status);
    
    return sprintf(
        '<span class="%s">%s</span>',
        esc_attr($class),
        esc_html($label)
    );
}

/**
 * Renderizar método de pago con formato
 *
 * @since 1.0.0
 * @param string $method Código del método
 * @return string HTML del método formateado
 */
function wp_pos_format_payment_method($method) {
    $methods = wp_pos_get_payment_methods();
    $label = isset($methods[$method]) ? $methods[$method] : $method;
    
    $class = 'wp-pos-payment-method wp-pos-payment-' . sanitize_html_class($method);
    
    return sprintf(
        '<span class="%s">%s</span>',
        esc_attr($class),
        esc_html($label)
    );
}
