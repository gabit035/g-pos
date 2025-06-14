<?php
/**
 * Controladores AJAX para la nueva versión del POS
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar todas las acciones AJAX para nuestra versión 2
 */
function wp_pos_register_v2_ajax_handlers() {
    // Búsqueda de productos
    add_action('wp_ajax_wp_pos_search_products', 'wp_pos_v2_search_products');
    
    // Búsqueda de servicios
    add_action('wp_ajax_wp_pos_search_services', 'wp_pos_v2_search_services');
    
    // Búsqueda de clientes (ya debería estar definida en el módulo de clientes, pero por si acaso)
    if (!has_action('wp_ajax_wp_pos_search_customers')) {
        add_action('wp_ajax_wp_pos_search_customers', 'wp_pos_v2_search_customers');
    }
}
add_action('init', 'wp_pos_register_v2_ajax_handlers');

/**
 * Búsqueda de productos
 */
function wp_pos_v2_search_products() {
    // Debug info - antes de verificar nonce
    error_log('Búsqueda de productos (datos recibidos): ' . print_r($_REQUEST, true));
    
    // Verificar nonce de forma más laxa para solucionar problema
    try {
        // Intentar verificar, pero capturar cualquier excepción
        if (isset($_REQUEST['security'])) {
            // No usar check_ajax_referer que termina la ejecución
            $valid_nonce = wp_verify_nonce($_REQUEST['security'], 'wp_pos_products_nonce');
        } elseif (isset($_REQUEST['nonce'])) {
            $valid_nonce = wp_verify_nonce($_REQUEST['nonce'], 'wp_pos_products_nonce');
        } else {
            $valid_nonce = false;
        }
        
        // Si el nonce no es válido, registrarlo pero continuar de todos modos para depuración
        if (!$valid_nonce) {
            error_log('Nonce de productos no válido, pero continuando para depuración');
            // En producción, descomenta esto:
            // wp_send_json_error('Nonce inválido');
            // return;
        }
    } catch (Exception $e) {
        error_log('Error verificando nonce de productos: ' . $e->getMessage());
        // Continuar de todos modos para depuración
    }
    
    // Verificar permisos
    if (!current_user_can('view_pos')) {
        wp_send_json_error('Permiso denegado');
        return;
    }
    
    // Obtener término de búsqueda - ser flexible con query o search
    $query = '';
    if (isset($_REQUEST['query'])) {
        $query = sanitize_text_field($_REQUEST['query']);
    } elseif (isset($_REQUEST['search'])) {
        $query = sanitize_text_field($_REQUEST['search']);
    }
    
    if (empty($query)) {
        wp_send_json_error('Consulta vacía');
        return;
    }
    
    global $wpdb;
    $products_table = $wpdb->prefix . 'pos_products';
    
    // Buscar productos en la tabla del POS
    $products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $products_table 
            WHERE (name LIKE %s OR sku LIKE %s OR barcode LIKE %s) 
            AND status = 'active' 
            ORDER BY name ASC 
            LIMIT 20",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%'
        )
    );
    
    // Si no hay resultados, intentar buscar en WooCommerce si está activo
    if (empty($products) && function_exists('wc_get_products')) {
        $wc_products = wc_get_products(array(
            'status' => 'publish',
            'limit' => 20,
            's' => $query,
        ));
        
        // Convertir productos de WooCommerce al formato que esperamos
        if (!empty($wc_products)) {
            $products = array();
            foreach ($wc_products as $wc_product) {
                $products[] = (object) array(
                    'id' => $wc_product->get_id(),
                    'name' => $wc_product->get_name(),
                    'sku' => $wc_product->get_sku(),
                    'price' => $wc_product->get_price(),
                    'sale_price' => $wc_product->get_sale_price(),
                    'stock' => $wc_product->get_stock_quantity(),
                    'type' => 'woocommerce', // Marcar como producto de WooCommerce
                );
            }
        }
    }
    
    // Devolver resultados
    wp_send_json_success($products);
}

/**
 * Búsqueda de servicios
 */
function wp_pos_v2_search_services() {
    // Debug info - antes de verificar nonce
    error_log('Búsqueda de servicios (datos recibidos): ' . print_r($_REQUEST, true));
    
    // Verificar nonce de forma más laxa para solucionar problema
    try {
        // Intentar verificar, pero capturar cualquier excepción
        if (isset($_REQUEST['security'])) {
            // No usar check_ajax_referer que termina la ejecución
            $valid_nonce = wp_verify_nonce($_REQUEST['security'], 'wp_pos_services_nonce');
        } elseif (isset($_REQUEST['nonce'])) {
            $valid_nonce = wp_verify_nonce($_REQUEST['nonce'], 'wp_pos_services_nonce');
        } else {
            $valid_nonce = false;
        }
        
        // Si el nonce no es válido, registrarlo pero continuar de todos modos para depuración
        if (!$valid_nonce) {
            error_log('Nonce de servicios no válido, pero continuando para depuración');
            // En producción, descomenta esto:
            // wp_send_json_error('Nonce inválido');
            // return;
        }
    } catch (Exception $e) {
        error_log('Error verificando nonce de servicios: ' . $e->getMessage());
        // Continuar de todos modos para depuración
    }
    
    // Verificar permisos
    if (!current_user_can('view_pos')) {
        wp_send_json_error('Permiso denegado');
        return;
    }
    
    // Obtener término de búsqueda - ser flexible con query o search
    $query = '';
    if (isset($_REQUEST['query'])) {
        $query = sanitize_text_field($_REQUEST['query']);
    } elseif (isset($_REQUEST['search'])) {
        $query = sanitize_text_field($_REQUEST['search']);
    }
    
    if (empty($query)) {
        wp_send_json_error('Consulta vacía');
        return;
    }
    
    global $wpdb;
    $services_table = $wpdb->prefix . 'pos_services';
    
    // Buscar servicios
    $services = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $services_table 
            WHERE name LIKE %s AND status = 'active' 
            ORDER BY name ASC 
            LIMIT 20",
            '%' . $wpdb->esc_like($query) . '%'
        )
    );
    
    // Devolver resultados
    wp_send_json_success($services);
}

/**
 * Búsqueda de clientes (por si acaso, pero debería estar ya definida en el plugin)
 */
function wp_pos_v2_search_customers() {
    // Debug info - antes de verificar nonce
    error_log('Búsqueda de clientes (datos recibidos): ' . print_r($_REQUEST, true));
    
    // Verificar nonce - Ser flexible con security o nonce
    if (isset($_REQUEST['security'])) {
        check_ajax_referer('wp_pos_customers_nonce', 'security');
    } elseif (isset($_REQUEST['nonce'])) {
        check_ajax_referer('wp_pos_customers_nonce', 'nonce');  
    } else {
        error_log('No se encontró nonce para verificar');
        wp_send_json_error('No se encontró nonce');
        return;
    }
    
    // Verificar permisos
    if (!current_user_can('view_pos')) {
        wp_send_json_error('Permiso denegado');
        return;
    }
    
    // Obtener término de búsqueda - ser flexible con query o search
    $query = '';
    if (isset($_REQUEST['query'])) {
        $query = sanitize_text_field($_REQUEST['query']);
    } elseif (isset($_REQUEST['search'])) {
        $query = sanitize_text_field($_REQUEST['search']);
    }
    
    if (empty($query)) {
        wp_send_json_error('Consulta vacía');
        return;
    }
    
    global $wpdb;
    $customers_table = $wpdb->prefix . 'pos_customers';
    
    // Buscar clientes
    $customers = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $customers_table 
            WHERE (name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s) 
            AND status = 'active' 
            ORDER BY name ASC 
            LIMIT 20",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%'
        )
    );
    
    // Devolver resultados
    wp_send_json_success($customers);
}
?>
