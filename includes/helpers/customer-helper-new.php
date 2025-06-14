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
