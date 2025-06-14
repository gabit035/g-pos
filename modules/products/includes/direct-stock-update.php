<?php
/**
 * Actualizador directo de stock para WP-POS
 * Este archivo implementa una soluciu00f3n que asegura que tanto
 * la base de datos como la interfaz de usuario reflejen
 * correctamente los cambios en el stock de productos.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Actualizar el stock de un producto directamente en la base de datos
 * y forzar una actualizaciu00f3n de la cachu00e9 para que los cambios
 * se reflejen inmediatamente en la interfaz.
 *
 * @param int $product_id ID del producto
 * @param int $quantity Cantidad a restar
 * @return bool u00c9xito de la operaciu00f3n
 */
function wp_pos_update_product_stock_direct($product_id, $quantity) {
    global $wpdb;
    
    // Variables para diagnu00f3stico
    $debug_info = array(
        'product_id' => $product_id,
        'quantity' => $quantity,
        'timestamp' => current_time('mysql'),
        'steps' => array()
    );
    
    // 1. Validar datos de entrada
    $product_id = absint($product_id);
    $quantity = absint($quantity);
    
    if (!$product_id || !$quantity) {
        $debug_info['steps'][] = 'ERROR: Datos de entrada invu00e1lidos';
        error_log('STOCK DIRECTO: ' . json_encode($debug_info));
        return false;
    }
    
    // 2. Obtener información de la tabla de productos
    $products_table = $wpdb->prefix . 'pos_products';
    $debug_info['steps'][] = "Tabla de productos: {$products_table}";
    
    // 3. Verificar si la tabla existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$products_table}'");
    if (!$table_exists) {
        $debug_info['steps'][] = 'ERROR: La tabla de productos no existe';
        error_log('STOCK DIRECTO: ' . json_encode($debug_info));
        return false;
    }
    
    // 4. Obtener stock actual
    $current_stock = $wpdb->get_var(
        $wpdb->prepare("SELECT stock_quantity FROM {$products_table} WHERE id = %d", $product_id)
    );
    
    if ($current_stock === null) {
        $debug_info['steps'][] = "ERROR: No se encontru00f3 el producto ID: {$product_id}";
        error_log('STOCK DIRECTO: ' . json_encode($debug_info));
        return false;
    }
    
    $debug_info['current_stock'] = $current_stock;
    $debug_info['steps'][] = "Stock actual: {$current_stock}";
    
    // 5. Calcular nuevo stock (prevenir stock negativo)
    $new_stock = max(0, intval($current_stock) - $quantity);
    $debug_info['new_stock'] = $new_stock;
    $debug_info['steps'][] = "Nuevo stock calculado: {$new_stock}";
    
    // 6. Actualizar stock en la base de datos con fecha de modificaciu00f3n
    $result = $wpdb->update(
        $products_table,
        array(
            'stock_quantity' => $new_stock,
            'date_modified' => current_time('mysql') // Actualizar la fecha de modificaciu00f3n
        ),
        array('id' => $product_id),
        array('%d', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        $debug_info['steps'][] = "ERROR: Fallo al actualizar el stock. Error: {$wpdb->last_error}";
        error_log('STOCK DIRECTO: ' . json_encode($debug_info));
        return false;
    }
    
    $debug_info['steps'][] = 'Stock actualizado correctamente en la base de datos';
    
    // 7. Verificar si se debe disparar hooks para notificaciones
    if (function_exists('do_action')) {
        do_action('wp_pos_product_stock_updated', $product_id, $new_stock, $current_stock);
        $debug_info['steps'][] = 'Hook wp_pos_product_stock_updated ejecutado';
    }
    
    // 8. Forzar limpieza de cachu00e9 del producto
    wp_cache_delete($product_id, 'pos_products');
    $debug_info['steps'][] = 'Cachu00e9 del producto limpiada';
    
    // Registrar u00e9xito
    $debug_info['success'] = true;
    error_log('STOCK DIRECTO: ' . json_encode($debug_info));
    
    return true;
}

/**
 * Aplicar actualizaciu00f3n de stock despuu00e9s de procesar una venta
 *
 * @param WP_POS_Sale|int $sale Objeto de venta o ID de venta
 * @return bool u00c9xito de la operaciu00f3n
 */
function wp_pos_apply_stock_update_after_sale($sale) {
    global $wpdb;
    
    // Si recibimos un ID en lugar de un objeto
    if (is_numeric($sale)) {
        $sale_id = absint($sale);
        // Obtener los datos de la venta desde la base de datos
        $sales_table = $wpdb->prefix . 'pos_sales';
        $sale_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$sales_table} WHERE id = %d", $sale_id)
        );
        
        if (!$sale_data) {
            error_log("ERROR STOCK UPDATE: No se encontru00f3 la venta ID: {$sale_id}");
            return false;
        }
        
        // Extraer items de la venta
        $items = maybe_unserialize($sale_data->items);
    } else {
        // Es un objeto de venta
        $sale_id = $sale->get_id();
        $items = $sale->get_items();
    }
    
    error_log("STOCK UPDATE: Procesando venta ID: {$sale_id}");
    
    // Verificar que items sea un array vu00e1lido
    if (!is_array($items) || empty($items)) {
        error_log("ERROR STOCK UPDATE: No hay items en la venta ID: {$sale_id}");
        return false;
    }
    
    $success = true;
    
    // Procesar cada item
    foreach ($items as $item) {
        // Extraer product_id y quantity
        if (is_object($item) && method_exists($item, 'get_product_id') && method_exists($item, 'get_quantity')) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
        } else if (is_array($item)) {
            // Buscar primero product_id (formato legacy) y si no existe, buscar id (nuevo formato V2)
            $product_id = isset($item['product_id']) ? absint($item['product_id']) : 0;
            
            // Si no se encontró product_id, intentar con id (usado en la interfaz V2)
            if ($product_id === 0 && isset($item['id'])) {
                $product_id = absint($item['id']);
            }
            
            $quantity = isset($item['quantity']) ? absint($item['quantity']) : 0;
            
            // Solo actualizar stock para productos (no para servicios)
            if (isset($item['type']) && $item['type'] === 'service') {
                // No actualizar stock para servicios
                continue;
            }
        } else {
            continue; // Saltar este item si no podemos obtener los datos necesarios
        }
        
        // Actualizar stock directamente
        if ($product_id > 0 && $quantity > 0) {
            $result = wp_pos_update_product_stock_direct($product_id, $quantity);
            if (!$result) {
                $success = false;
                error_log("ERROR STOCK UPDATE: Fallo al actualizar stock para producto ID: {$product_id} en venta ID: {$sale_id}");
            }
        }
    }
    
    return $success;
}

/**
 * Hook a wp_pos_sales_processed para actualizar stock
 */
add_action('wp_pos_after_process_sale', 'wp_pos_apply_stock_update_after_sale', 10, 1);
add_action('wp_pos_after_process_sale_direct', 'wp_pos_apply_stock_update_after_sale', 10, 1);

/**
 * Implementar un ajax handler para forzar la actualizaciu00f3n de stock
 */
function wp_pos_force_stock_update_ajax() {
    // Verificar nonce
    check_ajax_referer('wp_pos_admin', 'security');
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permisos insuficientes');
        return;
    }
    
    // Obtener ID del producto
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 0;
    
    if (!$product_id) {
        wp_send_json_error('Se requiere un ID de producto vu00e1lido');
        return;
    }
    
    // Forzar actualizaciu00f3n
    $result = wp_pos_update_product_stock_direct($product_id, $quantity);
    
    if ($result) {
        wp_send_json_success('Stock actualizado correctamente');
    } else {
        wp_send_json_error('Error al actualizar el stock');
    }
}

add_action('wp_ajax_wp_pos_force_stock_update', 'wp_pos_force_stock_update_ajax');
