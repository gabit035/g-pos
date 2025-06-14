<?php
/**
 * Funciones auxiliares para manejo de clientes
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Cargar el controlador si no está cargado
if (!function_exists('wp_pos_get_customers_controller')) {
    /**
     * Obtiene una instancia del controlador de clientes
     * 
     * @return WP_POS_Customers_Controller Instancia del controlador
     */
    function wp_pos_get_customers_controller() {
        // Verificar si ya existe la clase
        if (!class_exists('WP_POS_Customers_Controller')) {
            require_once WP_POS_PLUGIN_DIR . '/modules/customers/controllers/class-pos-customers-controller.php';
        }
        
        return new WP_POS_Customers_Controller();
    }
}

/**
 * Guarda metadatos específicos del cliente desde el formulario
 * 
 * @param int $customer_id ID del cliente
 * @param array $data Datos del cliente
 * @return bool True si se actualizaron los datos
 */
function wp_pos_save_customer_metadata($customer_id, $data) {
    if (empty($customer_id)) {
        return false;
    }
    
    // Campos específicos
    $meta_fields = array(
        'dni' => 'dni',
        'birth_date' => 'birth_date',
        'phone' => 'billing_phone',
        'address' => 'billing_address_1',
        'notes' => '_wp_pos_customer_notes'
    );
    
    foreach ($meta_fields as $data_key => $meta_key) {
        if (isset($data[$data_key])) {
            $value = $data_key === 'notes' ? sanitize_textarea_field($data[$data_key]) : sanitize_text_field($data[$data_key]);
            update_user_meta($customer_id, $meta_key, $value);
        }
    }
    
    return true;
}

/**
 * Obtiene un campo específico de metadatos del cliente
 * 
 * @param int $customer_id ID del cliente
 * @param string $field Nombre del campo (dni, birth_date, phone, etc.)
 * @return string Valor del campo o cadena vacía si no existe
 */
function wp_pos_get_customer_field($customer_id, $field) {
    if (empty($customer_id)) {
        return '';
    }
    
    // Mapeo de campos a meta_keys
    $meta_keys = array(
        'dni' => 'dni',
        'birth_date' => 'birth_date',
        'phone' => 'billing_phone',
        'address' => 'billing_address_1',
        'notes' => '_wp_pos_customer_notes'
    );
    
    // Si el campo no está en nuestro mapeo, usar el campo directamente como meta_key
    $meta_key = isset($meta_keys[$field]) ? $meta_keys[$field] : $field;
    
    return get_user_meta($customer_id, $meta_key, true);
}

/**
 * Obtiene el nombre del cliente basado en su ID
 *
 * @param int $customer_id ID del cliente (usuario de WordPress)
 * @return string Nombre del cliente o 'Cliente anónimo' si no existe
 */
function wp_pos_get_customer_name($customer_id) {
    if (empty($customer_id) || $customer_id <= 0) {
        return __('Cliente anónimo', 'wp-pos');
    }
    
    // Obtener informaciu00f3n del cliente por ID
    $customer = get_user_by('id', $customer_id);
    if (!$customer) {
        return sprintf(__('Cliente #%d (no encontrado)', 'wp-pos'), $customer_id);
    }
    
    // Intentar obtener el nombre completo (nombre + apellido)
    $display_name = trim($customer->first_name . ' ' . $customer->last_name);
    if (!empty(trim($display_name))) {
        return $display_name;
    }
    
    // Si no hay nombre completo, usar el nombre de visualizaciu00f3n predeterminado
    return $customer->display_name;
}

/**
 * Preprocesa los datos de ventas para agregar el nombre del cliente a cada venta
 *
 * @param array|object $sales Lista de ventas o venta individual
 * @return array|object Datos de ventas con nombre de cliente agregado
 */
function wp_pos_prepare_sales_display($sales) {
    // Si es un solo objeto de venta, procesarlo directamente
    if (is_object($sales)) {
        if (isset($sales->customer_id)) {
            $sales->customer_name = wp_pos_get_customer_name($sales->customer_id);
        } else {
            $sales->customer_name = __('Cliente anónimo', 'wp-pos');
        }
        return $sales;
    }
    
    // Si es un array de ventas, procesar cada uno
    if (is_array($sales)) {
        foreach ($sales as &$sale) {
            if (is_object($sale) && isset($sale->customer_id)) {
                $sale->customer_name = wp_pos_get_customer_name($sale->customer_id);
            } elseif (is_object($sale)) {
                $sale->customer_name = __('Cliente anónimo', 'wp-pos');
            }
        }
    }
    
    return $sales;
}
