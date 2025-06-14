<?php
/**
 * Actualizaciones de la base de datos para productos
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Añade el campo de precio de compra a la tabla de productos si no existe
 *
 * @since 1.0.0
 * @return bool True si la columna se añadió o ya existía, false en caso contrario
 */
function wp_pos_add_purchase_price_column() {
    global $wpdb;
    $products_table = $wpdb->prefix . 'pos_products';
    
    // Verificar si la tabla existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$products_table'") != $products_table) {
        return false;
    }
    
    // Verificar si la columna ya existe
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $products_table LIKE 'purchase_price'");
    
    if (empty($columns)) {
        // La columna no existe, añadirla
        $result = $wpdb->query("ALTER TABLE $products_table ADD COLUMN purchase_price DECIMAL(10,2) DEFAULT 0 AFTER description");
        
        if ($result === false) {
            error_log('Error al añadir la columna purchase_price a la tabla ' . $products_table);
            return false;
        }
        
        error_log('Columna purchase_price añadida correctamente a la tabla ' . $products_table);
        return true;
    }
    
    // La columna ya existe
    return true;
}
