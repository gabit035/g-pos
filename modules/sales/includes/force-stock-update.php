<?php
/**
 * Actualizador forzado de stock para WP-POS
 * 
 * Este archivo implementa una soluciu00f3n directa para asegurar que el stock
 * se actualice correctamente despuu00e9s de cada venta.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook para forzar la actualizaciu00f3n de stock despuu00e9s de cada venta
 * 
 * @param int $sale_id ID de la venta procesada
 */
function wp_pos_force_stock_update_after_sale($sale_id) {
    global $wpdb;
    
    // Registrar inicio de la actualizaciu00f3n forzada
    error_log("STOCK FORCE: Iniciando actualizaciu00f3n forzada de stock para venta ID: {$sale_id}");
    
    // Obtener informaciu00f3n de la venta
    $sales_table = $wpdb->prefix . 'pos_sales';
    $sale = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$sales_table} WHERE id = %d", $sale_id));
    
    if (!$sale) {
        error_log("STOCK FORCE ERROR: No se encontru00f3 la venta ID: {$sale_id}");
        return;
    }
    
    // Obtener los items de la venta
    $items = maybe_unserialize($sale->items);
    if (!is_array($items)) {
        error_log("STOCK FORCE ERROR: No hay items vu00e1lidos en la venta ID: {$sale_id}");
        return;
    }
    
    // Procesar cada item
    foreach ($items as $item) {
        // Verificar que sea un item vu00e1lido con product_id y quantity
        if (!isset($item['product_id']) || !isset($item['quantity'])) {
            continue;
        }
        
        $product_id = absint($item['product_id']);
        $quantity = absint($item['quantity']);
        
        if ($product_id <= 0 || $quantity <= 0) {
            continue;
        }
        
        // Obtener stock actual directamente
        $products_table = $wpdb->prefix . 'pos_products';
        $current_stock = $wpdb->get_var($wpdb->prepare(
            "SELECT stock_quantity FROM {$products_table} WHERE id = %d",
            $product_id
        ));
        
        // Si no se puede obtener el stock actual, pasar al siguiente
        if ($current_stock === null) {
            error_log("STOCK FORCE ERROR: No se encontru00f3 el producto ID: {$product_id}");
            continue;
        }
        
        $current_stock = intval($current_stock);
        
        // Calcular nuevo stock (asegurar que no sea negativo)
        $new_stock = max(0, $current_stock - $quantity);
        
        // Actualizar directamente en la base de datos
        $result = $wpdb->update(
            $products_table,
            array('stock_quantity' => $new_stock),
            array('id' => $product_id),
            array('%d'),
            array('%d')
        );
        
        // Registrar resultado
        if ($result === false) {
            error_log(sprintf(
                'STOCK FORCE ERROR: Fallo al actualizar stock para producto ID: %d. Error: %s',
                $product_id,
                $wpdb->last_error
            ));
        } else {
            error_log(sprintf(
                'STOCK FORCE: Actualizado stock del producto ID: %d. Stock anterior: %d, Nuevo stock: %d', 
                $product_id,
                $current_stock,
                $new_stock
            ));
        }
    }
    
    error_log("STOCK FORCE: Finalizada actualizaciu00f3n forzada de stock para venta ID: {$sale_id}");
}

/**
 * Registrar los hooks necesarios
 */
function wp_pos_register_stock_force_hooks() {
    // Hook para cuando una venta se procese directamente (sin AJAX)
    add_action('wp_pos_after_process_sale_direct', 'wp_pos_force_stock_update_after_sale', 10, 1);
    
    // Hook para cuando una venta se procese mediante AJAX
    add_action('wp_pos_after_process_sale', 'wp_pos_force_stock_update_after_sale', 10, 1);
}

// Iniciar los hooks
wp_pos_register_stock_force_hooks();
