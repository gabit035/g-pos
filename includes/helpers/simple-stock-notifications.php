<?php
/**
 * Sistema simplificado de notificaciones de stock bajo
 * 
 * @package WP-POS
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Muestra notificaciones de stock bajo directamente en el dashboard
 */
function wp_pos_show_stock_notifications() {
    global $wpdb;
    
    // 1. Obtener umbral desde opciones
    $options = wp_pos_get_option();
    $threshold = isset($options['low_stock_threshold']) ? intval($options['low_stock_threshold']) : 2;
    
    echo '<div style="border: 2px solid #e74c3c; padding: 15px; margin: 20px 0; background-color: #fef5f5; border-radius: 5px;">';
    echo '<h3 style="margin-top: 0; color: #e74c3c;"><span class="dashicons dashicons-warning"></span> Diagnóstico de notificaciones de stock</h3>';
    
    // Mostrar información de diagnóstico
    echo '<p><strong>Umbral configurado:</strong> ' . $threshold . '</p>';
    
    // 2. Intentar obtener productos con stock bajo de diferentes tablas
    $products = array();
    
    // Tabla POS_PRODUCTS
    $pos_table = $wpdb->prefix . 'pos_products';
    if ($wpdb->get_var("SHOW TABLES LIKE '$pos_table'") == $pos_table) {
        echo '<p><strong>Revisando tabla:</strong> ' . $pos_table . '</p>';
        
        $query = $wpdb->prepare(
            "SELECT id, name, stock_quantity FROM $pos_table WHERE stock_quantity > 0 AND stock_quantity <= %d LIMIT 5",
            $threshold
        );
        
        $pos_products = $wpdb->get_results($query);
        if (!empty($pos_products)) {
            echo '<p style="color:green;">✓ Encontrados ' . count($pos_products) . ' productos en tabla POS</p>';
            $products = array_merge($products, $pos_products);
        } else {
            echo '<p style="color:#888;">✗ No se encontraron productos con stock bajo en tabla POS</p>';
        }
    } else {
        echo '<p style="color:#888;">✗ Tabla POS_PRODUCTS no encontrada</p>';
    }
    
    // Tabla WC_PRODUCTS
    $wc_table = $wpdb->prefix . 'wc_products';
    if ($wpdb->get_var("SHOW TABLES LIKE '$wc_table'") == $wc_table) {
        echo '<p><strong>Revisando tabla:</strong> ' . $wc_table . '</p>';
        
        $query = $wpdb->prepare(
            "SELECT id, name, stock_quantity FROM $wc_table WHERE stock_quantity > 0 AND stock_quantity <= %d LIMIT 5",
            $threshold
        );
        
        $wc_products = $wpdb->get_results($query);
        if (!empty($wc_products)) {
            echo '<p style="color:green;">✓ Encontrados ' . count($wc_products) . ' productos en tabla WC</p>';
            $products = array_merge($products, $wc_products);
        } else {
            echo '<p style="color:#888;">✗ No se encontraron productos con stock bajo en tabla WC</p>';
        }
    } else {
        echo '<p style="color:#888;">✗ Tabla WC_PRODUCTS no encontrada</p>';
    }
    
    // 3. Mostrar productos encontrados
    if (!empty($products)) {
        echo '<h4>Productos con stock bajo:</h4>';
        echo '<ul style="border-top: 1px solid #ddd; padding-top: 10px;">';
        foreach ($products as $product) {
            echo '<li>';
            echo '<strong>' . esc_html($product->name) . '</strong>: ';
            echo '<span style="color:#e74c3c;">' . esc_html($product->stock_quantity) . ' unidades</span>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p style="font-weight:bold;">No se encontraron productos con stock bajo.</p>';
    }
    
    echo '<p><strong>Nota:</strong> Este es un panel de diagnóstico temporal para resolver el problema de notificaciones.</p>';
    echo '</div>';
}
