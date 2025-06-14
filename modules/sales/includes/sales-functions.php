<?php
/**
 * Funciones auxiliares para el módulo de ventas
 *
 * @package WP-POS
 * @subpackage Sales
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener listado de ventas
 *
 * @since 1.0.0
 * @param array $args Argumentos para la consulta
 * @return array Array de ventas
 */
function wp_pos_get_sales($args = array()) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->get_sales($args);
}

/**
 * Obtener una venta por su ID
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @return WP_POS_Sale|false Objeto de venta o false si no existe
 */
function wp_pos_get_sale($sale_id) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->get_sale($sale_id);
}

/**
 * Crear una nueva venta
 *
 * @since 1.0.0
 * @param array $data Datos de la venta
 * @return WP_POS_Sale|false Objeto de venta o false en caso de error
 */
function wp_pos_create_sale($data) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->create_sale($data);
}

/**
 * Actualizar una venta existente
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @param array $data Datos a actualizar
 * @return WP_POS_Sale|false Objeto de venta o false en caso de error
 */
function wp_pos_update_sale($sale_id, $data) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->update_sale($sale_id, $data);
}

/**
 * Eliminar una venta
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @return bool Éxito de la operación
 */
function wp_pos_delete_sale($sale_id) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->delete_sale($sale_id);
}

/**
 * Procesar una venta completa
 *
 * @since 1.0.0
 * @param array $sale_data Datos de la venta
 * @return WP_POS_Sale|false Objeto de venta o false en caso de error
 */
function wp_pos_process_sale($sale_data) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->process_sale($sale_data);
}

/**
 * Añadir un producto a una venta
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @param array $item_data Datos del item (producto)
 * @return bool Éxito de la operación
 */
function wp_pos_add_item_to_sale($sale_id, $item_data) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->add_item_to_sale($sale_id, $item_data);
}

/**
 * Añadir un pago a una venta
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @param array $payment_data Datos del pago
 * @return bool Éxito de la operación
 */
function wp_pos_add_payment_to_sale($sale_id, $payment_data) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->add_payment_to_sale($sale_id, $payment_data);
}

/**
 * Obtener etiqueta de un estado de venta
 *
 * @since 1.0.0
 * @param string $status_key Clave del estado
 * @return string Etiqueta del estado
 */
function wp_pos_get_sale_status_label($status_key) {
    $statuses = wp_pos_get_sale_statuses();
    
    return isset($statuses[$status_key]) ? $statuses[$status_key] : $status_key;
}

/**
 * Formatear fecha con formato localizado
 *
 * @since 1.0.0
 * @param string $date Fecha en formato estándar
 * @return string Fecha formateada
 */
function wp_pos_format_date($date) {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }
    
    return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
}

/**
 * Formatear precio en moneda
 *
 * @since 1.0.0
 * @param float $price Precio
 * @return string Precio formateado
 */
function wp_pos_format_currency($price) {
    $options = wp_pos_get_option();
    $currency = isset($options['currency']) ? $options['currency'] : 'USD';
    $currency_symbol = isset($options['currency_symbol']) ? $options['currency_symbol'] : '$';
    
    $formatted = number_format((float)$price, 2, '.', ',');
    
    return $currency_symbol . $formatted;
}

/**
 * Contar ventas con los mismos filtros
 *
 * @since 1.0.0
 * @param array $args Argumentos de filtro
 * @return int Cantidad de ventas
 */
function wp_pos_count_sales($args = array()) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->count_sales($args);
}

/**
 * Actualizar el estado de una venta
 *
 * @since 1.0.0
 * @param int $sale_id ID de la venta
 * @param string $status Nuevo estado
 * @return bool True si se actualizó correctamente
 */
function wp_pos_update_sale_status($sale_id, $status) {
    $controller = WP_POS_Sales_Controller::get_instance();
    return $controller->update_sale_status($sale_id, $status);
}

/**
 * Registrar los manejadores de AJAX
 *
 * Esta función es llamada desde el módulo principal
 *
 * @since 1.0.0
 */
function wp_pos_register_sales_ajax_handlers() {
    add_action('wp_ajax_wp_pos_get_sales', 'wp_pos_ajax_get_sales');
    add_action('wp_ajax_wp_pos_get_sale', 'wp_pos_ajax_get_sale');
    add_action('wp_ajax_wp_pos_process_sale', 'wp_pos_ajax_process_sale');
    add_action('wp_ajax_wp_pos_test_sales_table', 'wp_pos_ajax_test_sales_table');
}

/**
 * Manejador AJAX para obtener ventas
 *
 * @since 1.0.0
 */
function wp_pos_ajax_get_sales() {
    // Verificar nonce de seguridad
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'wp_pos_ajax')) {
        wp_send_json_error('Nonce inválido');
    }
    
    // Obtener parámetros de búsqueda
    $args = isset($_REQUEST['args']) ? $_REQUEST['args'] : array();
    
    // Obtener ventas
    $sales = wp_pos_get_sales($args);
    
    wp_send_json_success($sales);
}

/**
 * Manejador AJAX para obtener una venta
 *
 * @since 1.0.0
 */
function wp_pos_ajax_get_sale() {
    // Verificar nonce de seguridad
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'wp_pos_ajax')) {
        wp_send_json_error('Nonce inválido');
    }
    
    // Obtener ID de venta
    $sale_id = isset($_REQUEST['sale_id']) ? intval($_REQUEST['sale_id']) : 0;
    
    if ($sale_id <= 0) {
        wp_send_json_error('ID de venta inválido');
    }
    
    // Obtener venta
    $sale = wp_pos_get_sale($sale_id);
    
    if (!$sale) {
        wp_send_json_error('Venta no encontrada');
    }
    
    // Formatear datos para respuesta AJAX
    $sale_data = array(
        'id'          => $sale->id,
        'sale_number' => $sale->sale_number,
        'date'        => $sale->date,
        'customer_id' => $sale->customer_id,
        'status'      => $sale->status,
        'subtotal'    => $sale->subtotal,
        'total'       => $sale->total,
        'items'       => maybe_unserialize($sale->items),
        'payments'    => maybe_unserialize($sale->payments),
        'notes'       => $sale->notes
    );
    
    wp_send_json_success($sale_data);
}

/**
 * Manejador AJAX para procesar una venta
 *
 * @since 1.0.0
 */
function wp_pos_ajax_process_sale() {
    // Verificar nonce de seguridad
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'wp_pos_ajax')) {
        wp_send_json_error('Nonce inválido');
    }
    
    // Obtener datos de la venta
    $sale_data = isset($_REQUEST['sale_data']) ? $_REQUEST['sale_data'] : array();
    
    if (empty($sale_data)) {
        wp_send_json_error('Datos de venta inválidos');
    }
    
    try {
        // Habilitar captura de errores PHP
        $previous_error_level = error_reporting(E_ALL);
        $previous_display_errors = ini_get('display_errors');
        ini_set('display_errors', 1);
        
        // Capturar salida de error
        ob_start();
        
        // Procesar venta
        $sale = wp_pos_process_sale($sale_data);
        
        // Capturar cualquier mensaje de error
        $error_output = ob_get_clean();
        
        // Restaurar configuraciones de error
        error_reporting($previous_error_level);
        ini_set('display_errors', $previous_display_errors);
        
        if (!$sale) {
            if (!empty($error_output)) {
                // Si hay salida de error, enviarla como parte del mensaje
                wp_send_json_error('Error al procesar la venta: ' . $error_output);
            } else {
                wp_send_json_error('Error desconocido al procesar la venta.');
            }
        }
        
        // Formatear datos para respuesta AJAX
        $sale_data = array(
            'id'          => $sale->id,
            'sale_number' => $sale->sale_number,
            'date'        => $sale->date
        );
        
        wp_send_json_success($sale_data);
    } catch (Exception $e) {
        // Capturar excepciones y devolverlas como errores
        wp_send_json_error('Error: ' . $e->getMessage());
    }
}

/**
 * Procesar venta directamente sin AJAX
 * 
 * Esta función implementa una solución más directa para procesar ventas
 * cuando el sistema AJAX falla. Similar a las soluciones para
 * búsqueda de productos y eliminación de productos.
 * 
 * @since 1.0.0
 */
function wp_pos_process_sale_direct() {
    global $wpdb;
    
    // Verificar si es una solicitud de procesamiento de venta
    if (isset($_POST['wp_pos_process_sale_direct']) && isset($_POST['wp_pos_sale_data'])) {
        // Habilitar mostrar todos los errores para depuración
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        // Inicio del HTML de respuesta
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Procesando Venta</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 800px; margin: 0 auto; }
                h1 { color: #0073aa; }
                h2 { margin-top: 20px; }
                pre { background: #f5f5f5; padding: 10px; overflow: auto; }
                .success { color: green; }
                .error { color: red; }
                .button { display: inline-block; background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
            </style>
        </head>
        <body>
            <h1>Procesando Venta</h1>';
        
        // Verificar nonce
        if (!isset($_POST['wp_pos_sale_nonce']) || !wp_verify_nonce($_POST['wp_pos_sale_nonce'], 'wp_pos_process_sale_direct')) {
            echo '<p class="error">Error de seguridad: Nonce inválido.</p>';
            echo '</body></html>';
            exit;
        }
        
        // Obtener y decodificar los datos de la venta
        $sale_data_json = wp_unslash($_POST['wp_pos_sale_data']);
        
        // Procesamiento silencioso, sin mostrar datos sensibles
        
        $sale_data = json_decode($sale_data_json, true);
        
        if (!$sale_data || !is_array($sale_data)) {
            // Redireccionar a Nueva Venta con mensaje de error
            wp_redirect(admin_url('admin.php?page=wp-pos-new-sale&error=json&msg=' . urlencode('Datos de venta inválidos o mal formateados: ' . json_last_error_msg())));
            exit;
        }
        
        // Procesar los datos sin mostrarlos
        
        try {
            // Generar identificador único para la venta
            $sale_number = 'POS-' . date('YmdHis');
            $customer_id = isset($sale_data['customer_id']) ? absint($sale_data['customer_id']) : 0;
            
            // Usar fecha seleccionada desde el formulario si está presente
            $input_date = isset($_POST['sale_date']) ? sanitize_text_field($_POST['sale_date']) : '';
            if ($input_date) {
                // Combinar fecha seleccionada con hora actual
                $date = $input_date . ' ' . date('H:i:s', current_time('timestamp'));
            } else {
                // Usar timestamp actual de WP
                $date = date_i18n('Y-m-d H:i:s', current_time('timestamp'));
            }
            
            // Insertar directamente en la tabla de ventas
            
            // 1. Insertar en la tabla de ventas
            $sales_table = $wpdb->prefix . 'pos_sales';
            $wpdb->insert(
                $sales_table,
                array(
                    'sale_number' => $sale_number,
                    'customer_id' => $customer_id,
                    'date_created' => $date,
                    'date_completed' => $date,
                    'status' => 'completed',
                    'user_id' => get_current_user_id(),
                    'register_id' => isset($sale_data['register_id']) ? absint($sale_data['register_id']) : 1,
                    'total' => isset($sale_data['total']) ? floatval($sale_data['total']) : 0,
                    'tax_total' => isset($sale_data['tax_total']) ? floatval($sale_data['tax_total']) : 0,
                    'discount_total' => isset($sale_data['discount_total']) ? floatval($sale_data['discount_total']) : 0,
                    'discount_type' => isset($sale_data['discount_type']) ? sanitize_text_field($sale_data['discount_type']) : '',
                    'notes' => isset($sale_data['notes']) ? sanitize_textarea_field($sale_data['notes']) : ''
                ),
                array('%s', '%d', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s')
            );
            
            if ($wpdb->last_error) {
                throw new Exception('Error al insertar la venta: ' . $wpdb->last_error);
            }
            
            $sale_id = $wpdb->insert_id;
            echo '<p>Venta insertada con ID: ' . esc_html($sale_id) . '</p>';
            
            if (!$sale_id) {
                throw new Exception('No se pudo obtener el ID de la venta insertada');
            }
            
            // 1.1 Insertar el pago en la tabla de pagos
            $payments_table = $wpdb->prefix . 'pos_payments';
            $wpdb->insert(
                $payments_table,
                array(
                    'sale_id' => $sale_id,
                    'payment_method' => isset($sale_data['payment_method']) ? sanitize_text_field($sale_data['payment_method']) : 'cash',
                    'amount' => isset($sale_data['total']) ? floatval($sale_data['total']) : 0,
                    'date_created' => $date,
                    'transaction_id' => $sale_number,
                    'note' => 'Pago inicial de la venta',
                    'meta' => maybe_serialize(array())
                ),
                array('%d', '%s', '%f', '%s', '%s', '%s', '%s')
            );
            
            if ($wpdb->last_error) {
                throw new Exception('Error al insertar el pago: ' . $wpdb->last_error);
            }
            
            // 2. Insertar los items de la venta
            if (isset($sale_data['items']) && is_array($sale_data['items'])) {
                $items_table = $wpdb->prefix . 'pos_sale_items';
                $subtotal = 0;
                
                // Procesar ítems
                
                foreach ($sale_data['items'] as $item) {
                    // Buscar el ID del producto en 'product_id' o en 'id' (usado en la interfaz V2)
                    $product_id = isset($item['product_id']) ? absint($item['product_id']) : 0;
                    $variation_id = isset($item['variation_id']) ? absint($item['variation_id']) : 0;
                    
                    // Si no encontramos el ID en product_id, intentar con el campo 'id'
                    if ($product_id === 0 && isset($item['id'])) {
                        $product_id = absint($item['id']);
                    }
                    
                    $name = isset($item['name']) ? sanitize_text_field($item['name']) : '';
                    $sku = isset($item['sku']) ? sanitize_text_field($item['sku']) : '';
                    $quantity = isset($item['quantity']) ? absint($item['quantity']) : 1;
                    $price = isset($item['price']) ? floatval($item['price']) : 0;
                    $total = $price * $quantity;
                    $tax = isset($item['tax']) ? floatval($item['tax']) : 0;
                    
                    // Aumentar subtotal
                    $subtotal += $total;
                    
                    // Insertar ítem
                    $wpdb->insert(
                        $items_table,
                        array(
                            'sale_id' => $sale_id,
                            'product_id' => $product_id,
                            'variation_id' => $variation_id,
                            'name' => $name,
                            'quantity' => $quantity,
                            'price' => $price,
                            'tax' => $tax,
                            'total' => $total
                        ),
                        array('%d', '%d', '%d', '%s', '%d', '%f', '%f', '%f')
                    );
                    
                    if ($wpdb->last_error) {
                        throw new Exception('Error al insertar item: ' . $wpdb->last_error);
                    }
                    
                    // Actualizar el stock del producto (solo si no es un servicio)
                    // Verificar si es un servicio por el campo 'type'
                    $is_service = isset($item['type']) && $item['type'] === 'service';
                    
                    // Si es un servicio, no actualizamos el stock
                    if ($is_service) {
                        error_log(sprintf('DIAGNÓSTICO VENTA DIRECTA: Item ID %d es un servicio, no se actualiza stock', $product_id));
                        continue;
                    }
                    
                    // Asegurar que el ID sea un entero válido mayor que cero
                    $product_id = absint($product_id);
                    if ($product_id > 0) {
                        $products_table = $wpdb->prefix . 'pos_products';
                        
                        // Obtener stock actual
                        $current_stock = $wpdb->get_var(
                            $wpdb->prepare("SELECT stock_quantity FROM {$products_table} WHERE id = %d", $product_id)
                        );
                        
                        if ($current_stock !== null) {
                            // DIAGNÓSTICO: Registrar información detallada antes de actualizar stock
                            error_log(sprintf('DIAGNÓSTICO VENTA DIRECTA - ANTES: Producto ID: %d, Stock actual: %d, Cantidad vendida: %d', 
                                $product_id, $current_stock, $quantity));
                                
                            // Calcular nuevo stock (asegurar que no sea negativo)
                            $new_stock = max(0, intval($current_stock) - $quantity);
                            
                            // DIAGNÓSTICO: Mostrar nuevo stock calculado
                            error_log(sprintf('DIAGNÓSTICO VENTA DIRECTA - CÁLCULO: Producto ID: %d, Nuevo stock calculado: %d', 
                                $product_id, $new_stock));
                                
                            // Actualizar stock
                            $result = $wpdb->update(
                                $products_table,
                                array('stock_quantity' => $new_stock),
                                array('id' => $product_id),
                                array('%d'),
                                array('%d')
                            );
                            
                            // DIAGNÓSTICO: Verificar estructura de la tabla
                            $table_structure = $wpdb->get_results("DESCRIBE {$products_table}");
                            error_log('DIAGNÓSTICO - ESTRUCTURA TABLA PRODUCTOS: ' . print_r($table_structure, true));
                            
                            // Verificar si la actualización tuvo éxito
                            if ($result === false) {
                                // Error en la actualización del stock
                                error_log(sprintf('ERROR al actualizar stock para producto ID: %d. Error: %s', 
                                    $product_id, $wpdb->last_error));
                            } else {
                                // Registrar la actualización exitosa de stock en el log para depuración
                                error_log(sprintf('Actualizado stock del producto ID: %d. Stock anterior: %d, Nuevo stock: %d', 
                                    $product_id, $current_stock, $new_stock));
                            }
                        }
                    }
                }
                
                // Actualizar el total en la tabla de ventas con el subtotal calculado
                $wpdb->update(
                    $sales_table,
                    array('total' => $subtotal),
                    array('id' => $sale_id),
                    array('%f'),
                    array('%d')
                );
                
                if ($wpdb->last_error) {
                    throw new Exception('Error al actualizar totales: ' . $wpdb->last_error);
                }
                
                // Venta procesada correctamente
            } else {
                // Si no hay items, redireccionar con error
                wp_redirect(admin_url('admin.php?page=wp-pos-new-sale&error=items&msg=' . urlencode('La venta no contiene ítems.')));
                exit;
            }
            
            // Éxito! Redireccionar a la lista de ventas
            wp_redirect(admin_url('admin.php?page=wp-pos-sales&sale_processed=1&sale_id=' . $sale_id));
            exit;
            
        } catch (Exception $e) {
            // Capturar excepciones y devolverlas como errores
            wp_send_json_error('Error: ' . $e->getMessage());
        } catch (Error $e) {
            // Almacenar mensaje de error en variable de sesión
            $_SESSION['wp_pos_sale_error'] = $e->getMessage();
            wp_redirect(admin_url('admin.php?page=wp-pos-new-sale&error=1'));
            exit;
        }
        
        exit;
    }
}

// Registrar hook para procesar ventas directamente
add_action('admin_init', 'wp_pos_process_sale_direct');

/**
 * Redirigir a la lista de ventas tras procesar una venta
 * 
 * @since 1.0.0
 */
function wp_pos_sales_admin_notices() {
    // Verificar si hay mensaje de venta procesada
    if (isset($_GET['page']) && $_GET['page'] === 'wp-pos-sales' && isset($_GET['sale_processed']) && isset($_GET['sale_id'])) {
        $sale_id = intval($_GET['sale_id']);
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(esc_html__('Venta #%d procesada correctamente.', 'wp-pos'), $sale_id) . 
             '</p></div>';
    }
}

// Registrar hook para mostrar notificaciones
add_action('admin_notices', 'wp_pos_sales_admin_notices');

// Registrar los manejadores AJAX
wp_pos_register_sales_ajax_handlers();
