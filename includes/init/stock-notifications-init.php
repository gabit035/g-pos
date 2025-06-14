<?php
/**
 * Inicializador de notificaciones de stock
 *
 * @package WP-POS
 * @subpackage Stock
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Cargar helper de notificaciones de stock
require_once WP_POS_PLUGIN_DIR . 'includes/helpers/stock-notifications-helper.php';

/**
 * Inicializar sistema de notificaciones de stock
 *
 * @since 1.0.0
 */
function wp_pos_init_stock_notifications() {
    // Establecer valor por defecto para el umbral de stock bajo si no existe
    if (get_option('wp_pos_stock_threshold') === false) {
        update_option('wp_pos_stock_threshold', 5);
    }
    
    // Registrar hook para verificar stock tras actualizar productos
    add_action('wp_pos_after_update_product_stock', 'wp_pos_check_stock_after_update', 10, 3);
}
add_action('init', 'wp_pos_init_stock_notifications');

/**
 * Verificar stock despuu00e9s de una actualizaciu00f3n
 * 
 * @since 1.0.0
 * @param int $product_id ID del producto
 * @param int $quantity Cantidad nueva
 * @param string $operation Tipo de operaciu00f3n realizada
 */
function wp_pos_check_stock_after_update($product_id, $quantity, $operation) {
    // Obtener producto actualizado
    $product = wp_pos_get_product($product_id);
    
    if (!$product) {
        return;
    }
    
    // Obtener umbral configurado
    $threshold = intval(get_option('wp_pos_stock_threshold', 5));
    
    // Si el stock estu00e1 por debajo del umbral, generar notificaciu00f3n para administradores
    if ($product['stock_quantity'] <= $threshold && $product['stock_quantity'] > 0) {
        wp_pos_maybe_send_low_stock_email($product, $threshold);
    }
    
    // Si el producto se ha agotado, generar notificaciu00f3n aparte
    if ($product['stock_quantity'] == 0) {
        wp_pos_maybe_send_out_of_stock_email($product);
    }
}

/**
 * Envu00eda notificaciu00f3n por email de stock bajo si estu00e1 habilitado
 *
 * @since 1.0.0
 * @param array $product Datos del producto
 * @param int $threshold Umbral configurado
 */
function wp_pos_maybe_send_low_stock_email($product, $threshold) {
    // Solo enviar un email diario por producto para evitar spam
    $notification_record = get_transient('wp_pos_low_stock_notification_' . $product['id']);
    if ($notification_record) {
        return;
    }
    
    // Obtener el email del administrador
    $admin_email = get_option('admin_email');
    
    // Asunto del email
    $subject = sprintf('[%s] Alerta de Stock Bajo - %s', get_bloginfo('name'), $product['name']);
    
    // Construir mensaje con estilo consistente
    $message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">';
    $message .= '<div style="background: linear-gradient(135deg, #3a6186, #89253e); padding: 20px; border-radius: 8px 8px 0 0; color: white;">';
    $message .= '<h2 style="margin: 0; color: white;">Alerta de Stock Bajo</h2>';
    $message .= '</div>';
    $message .= '<div style="padding: 20px; background-color: #fff;">';
    $message .= '<p>El siguiente producto tiene un nivel de stock por debajo del umbral configurado:</p>';
    $message .= '<div style="padding: 15px; background-color: #f8f9fa; border-radius: 6px; margin: 15px 0;">';
    $message .= '<h3 style="margin-top: 0; color: #6c5ce7;">' . esc_html($product['name']) . '</h3>';
    
    if (!empty($product['sku'])) {
        $message .= '<p><strong>SKU:</strong> ' . esc_html($product['sku']) . '</p>';
    }
    
    $message .= '<p><strong>Stock Actual:</strong> <span style="color: #f39c12; font-weight: bold;">' . $product['stock_quantity'] . '</span></p>';
    $message .= '<p><strong>Umbral Configurado:</strong> ' . $threshold . '</p>';
    $message .= '</div>';
    $message .= '<p>Es recomendable que reponga el inventario pronto.</p>';
    $message .= '<p><a href="' . admin_url('admin.php?page=wp-pos-products&action=edit&id=' . $product['id']) . '" style="display: inline-block; background-color: #6c5ce7; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">Gestionar Producto</a></p>';
    $message .= '</div>';
    $message .= '<div style="padding: 15px; background-color: #f5f5f5; border-radius: 0 0 8px 8px; font-size: 12px; color: #666; text-align: center;">';
    $message .= '<p>Este es un mensaje automu00e1tico del sistema WP-POS de ' . get_bloginfo('name') . '</p>';
    $message .= '</div>';
    $message .= '</div>';
    
    // Cabeceras para enviar en formato HTML
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>'
    );
    
    // Enviar email
    wp_mail($admin_email, $subject, $message, $headers);
    
    // Establecer transient para evitar duplicados (24 horas)
    set_transient('wp_pos_low_stock_notification_' . $product['id'], true, 24 * HOUR_IN_SECONDS);
}

/**
 * Envu00eda notificaciu00f3n por email de producto agotado si estu00e1 habilitado
 *
 * @since 1.0.0
 * @param array $product Datos del producto
 */
function wp_pos_maybe_send_out_of_stock_email($product) {
    // Solo enviar un email diario por producto para evitar spam
    $notification_record = get_transient('wp_pos_out_of_stock_notification_' . $product['id']);
    if ($notification_record) {
        return;
    }
    
    // Obtener el email del administrador
    $admin_email = get_option('admin_email');
    
    // Asunto del email
    $subject = sprintf('[%s] URGENTE: Producto Agotado - %s', get_bloginfo('name'), $product['name']);
    
    // Construir mensaje con estilo consistente
    $message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">';
    $message .= '<div style="background: linear-gradient(135deg, #3a6186, #89253e); padding: 20px; border-radius: 8px 8px 0 0; color: white;">';
    $message .= '<h2 style="margin: 0; color: white;">¡Alerta! Producto Agotado</h2>';
    $message .= '</div>';
    $message .= '<div style="padding: 20px; background-color: #fff;">';
    $message .= '<p>El siguiente producto se ha <strong style="color: #e74c3c;">AGOTADO</strong> completamente:</p>';
    $message .= '<div style="padding: 15px; background-color: #f8f9fa; border-radius: 6px; margin: 15px 0;">';
    $message .= '<h3 style="margin-top: 0; color: #6c5ce7;">' . esc_html($product['name']) . '</h3>';
    
    if (!empty($product['sku'])) {
        $message .= '<p><strong>SKU:</strong> ' . esc_html($product['sku']) . '</p>';
    }
    
    $message .= '<p><strong>Stock Actual:</strong> <span style="color: #e74c3c; font-weight: bold;">0</span></p>';
    $message .= '</div>';
    $message .= '<p>Es necesario que reponga el inventario lo antes posible para evitar pérdidas en ventas.</p>';
    $message .= '<p><a href="' . admin_url('admin.php?page=wp-pos-products&action=edit&id=' . $product['id']) . '" style="display: inline-block; background-color: #6c5ce7; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">Gestionar Producto</a></p>';
    $message .= '</div>';
    $message .= '<div style="padding: 15px; background-color: #f5f5f5; border-radius: 0 0 8px 8px; font-size: 12px; color: #666; text-align: center;">';
    $message .= '<p>Este es un mensaje automático del sistema WP-POS de ' . get_bloginfo('name') . '</p>';
    $message .= '</div>';
    $message .= '</div>';
    
    // Cabeceras para enviar en formato HTML
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>'
    );
    
    // Enviar email
    wp_mail($admin_email, $subject, $message, $headers);
    
    // Establecer transient para evitar duplicados (24 horas)
    set_transient('wp_pos_out_of_stock_notification_' . $product['id'], true, 24 * HOUR_IN_SECONDS);
}
