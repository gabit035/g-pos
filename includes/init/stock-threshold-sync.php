<?php
/**
 * Sincronizador de umbrales de stock bajo
 *
 * Este archivo garantiza que el umbral configurado en la interfaz de G-POS
 * sea el que realmente se utiliza para las notificaciones de stock bajo.
 *
 * @package WP-POS
 * @subpackage Stock
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sincroniza el umbral de stock bajo entre la configuración de G-POS y el sistema de notificaciones
 */
function wp_pos_sync_stock_threshold() {
    // Obtener opciones de G-POS
    $options = wp_pos_get_option();
    
    // Verificar que existe la configuración de umbral
    if (isset($options['low_stock_threshold'])) {
        // Obtener el umbral configurado en la interfaz
        $interface_threshold = intval($options['low_stock_threshold']);
        
        // Obtener el umbral actual usado por el sistema de notificaciones
        $current_threshold = intval(get_option('wp_pos_stock_threshold', 5));
        
        // Si son diferentes, actualizar el umbral del sistema de notificaciones
        if ($interface_threshold !== $current_threshold) {
            update_option('wp_pos_stock_threshold', $interface_threshold);
            error_log("G-POS: Umbral de stock bajo sincronizado de {$current_threshold} a {$interface_threshold}");
        }
    }
}

/**
 * Forzar la verificación de stock bajo para todos los productos
 */
function wp_pos_force_check_low_stock() {
    global $wpdb;
    
    // Obtener umbral sincronizado
    $threshold = intval(get_option('wp_pos_stock_threshold', 2));
    
    // Obtener productos con stock bajo
    $products_table = $wpdb->prefix . 'wc_products';
    
    $query = $wpdb->prepare(
        "SELECT * FROM {$products_table} WHERE stock_quantity > 0 AND stock_quantity <= %d",
        $threshold
    );
    
    $low_stock_products = $wpdb->get_results($query);
    
    if (empty($low_stock_products)) {
        return;
    }
    
    // Procesar cada producto con stock bajo
    foreach ($low_stock_products as $product) {
        // Conparar el producto al formato esperado por la función de notificación
        $product_data = array(
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'stock_quantity' => $product->stock_quantity
        );
        
        // Forzar borrado de transient para permitir nueva notificación
        delete_transient('wp_pos_low_stock_notification_' . $product->id);
        
        // Enviar notificación
        if (function_exists('wp_pos_maybe_send_low_stock_email')) {
            wp_pos_maybe_send_low_stock_email($product_data, $threshold);
        }
    }
}

// Sincronizar umbral después de que se guarden las opciones
add_action('update_option_wp_pos_options', 'wp_pos_sync_stock_threshold', 10);

// También sincronizar al inicializar para asegurar consistencia
add_action('init', 'wp_pos_sync_stock_threshold', 20);

// Añadir opción para verificar stock bajo manualmente
add_action('wp_ajax_wp_pos_check_low_stock', function() {
    check_admin_referer('wp_pos_check_low_stock', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permiso para realizar esta acción']);
    }
    
    wp_pos_force_check_low_stock();
    
    wp_send_json_success(['message' => 'Verificación de stock bajo completada']);
});
