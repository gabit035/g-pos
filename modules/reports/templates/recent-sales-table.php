<?php
/**
 * Template mejorado para tabla de ventas recientes - CORREGIDO
 * 
 * @package WP-POS
 * @subpackage Reports
 * @since 1.0.0
 * 
 * Variables esperadas:
 * @var array $recent_sales Array con las ventas recientes
 * @var array $totals Array con totales (opcional, para contexto)
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Verificar si WooCommerce está activo para usar sus funciones de formato
$wc_active = function_exists('wc_price');

// Función para formatear fechas de manera amigable
function wp_pos_format_friendly_date($date_string) {
    // Validar la fecha de entrada
    if (empty($date_string) || $date_string === '0000-00-00 00:00:00') {
        return __('Fecha no disponible', 'wp-pos');
    }
    
    // Intentar convertir la fecha a timestamp
    $timestamp = false;
    if (is_numeric($date_string)) {
        // Si ya es un timestamp numérico
        $timestamp = intval($date_string);
    } else {
        // Intentar convertir string a timestamp
        $timestamp = strtotime($date_string);
    }
    
    // Verificar que el timestamp sea válido
    if ($timestamp === false || $timestamp <= 0) {
        return __('Fecha no disponible', 'wp-pos');
    }
    
    $current_time = current_time('timestamp');
    $diff = $current_time - $timestamp;
    
    // Hace menos de 1 hora
    if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return sprintf(_n('Hace %d minuto', 'Hace %d minutos', $minutes, 'wp-pos'), $minutes);
    }
    
    // Hace menos de 24 horas
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return sprintf(_n('Hace %d hora', 'Hace %d horas', $hours, 'wp-pos'), $hours);
    }
    
    // Hace menos de 7 días
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return sprintf(_n('Hace %d día', 'Hace %d días', $days, 'wp-pos'), $days);
    }
    
    // Fecha formateada normal
    return date_i18n('d/m/Y H:i', $timestamp);
}

// Función helper para determinar el color del estado de la venta
function wp_pos_get_sale_status_class($total, $items_count) {
    if ($total == 0) return 'status-cancelled';
    if ($total < 50) return 'status-small';
    if ($total > 500) return 'status-large';
    return 'status-normal';
}

// *** FUNCIÓN CORREGIDA PARA MÉTODO DE PAGO ***
function wp_pos_get_sale_payment_method_display($sale) {
    // Si ya viene formateado en los datos
    if (isset($sale['payment_method_display'])) {
        return $sale['payment_method_display'];
    }
    
    // Usar las funciones helper de la clase de datos
    if (class_exists('WP_POS_Reports_Data')) {
        $sale_id = $sale['id'] ?? 0;
        $payment_method = WP_POS_Reports_Data::get_payment_method_from_sale($sale_id, $sale);
        return WP_POS_Reports_Data::format_payment_method($payment_method);
    }
    
    // Fallback simple si no están disponibles las funciones helper
    $method = $sale['payment_method'] ?? $sale['payment_type'] ?? '';
    
    if (empty($method)) {
        return '<span class="no-payment-method">' . __('No especificado', 'wp-pos') . '</span>';
    }
    
    // Mapeo básico
    $methods = [
        'cash' => 'Efectivo',
        'efectivo' => 'Efectivo',
        'card' => 'Tarjeta',
        'tarjeta' => 'Tarjeta',
        'transfer' => 'Transferencia',
        'transferencia' => 'Transferencia',
        'check' => 'Cheque',
        'cheque' => 'Cheque',
        'other' => 'Otro',
        'otro' => 'Otro',
    ];
    
    $method_lower = strtolower($method);
    return isset($methods[$method_lower]) ? $methods[$method_lower] : ucfirst($method);
}

// Verificar que tenemos los datos necesarios
if (!isset($recent_sales)) {
    $recent_sales = [];
    error_log('No se recibieron datos de ventas recientes');
}

// Si recent_sales es un array asociativo con estructura de respuesta
if (isset($recent_sales['recent_sales']) && is_array($recent_sales['recent_sales'])) {
    $sales_data = $recent_sales['recent_sales'];
    $success = $recent_sales['success'] ?? true;
    $message = $recent_sales['message'] ?? '';
} elseif (isset($recent_sales['sales']) && is_array($recent_sales['sales'])) {
    $sales_data = $recent_sales['sales'];
    $success = $recent_sales['success'] ?? true;
    $message = $recent_sales['message'] ?? '';
} else {
    // Asumir que recent_sales es directamente el array de ventas
    $sales_data = is_array($recent_sales) ? $recent_sales : [];
    $success = true;
    $message = '';
}

// Verificar si hay ventas para mostrar
$has_sales = !empty($sales_data) && is_array($sales_data);
$sales_count = $has_sales ? count($sales_data) : 0;

// Inicializar totales si no están definidos
if (!isset($totals) || !is_array($totals)) {
    $totals = [
        'sales' => 0,
        'items' => 0,
        'tax' => 0
    ];
}

// Debug
error_log("Procesando ventas recientes: has_sales=$has_sales, sales_count=$sales_count");
if ($has_sales && !empty($sales_data[0])) {
    error_log("Primera venta: " . print_r($sales_data[0], true));
}

// Estadísticas rápidas de las ventas mostradas
$displayed_sales_total = 0;
$displayed_items_total = 0;
$payment_methods_summary = [];

if ($has_sales) {
    foreach ($sales_data as $sale) {
        $sale = (array)$sale;
        
        $sale_total = floatval(is_array($sale) ? ($sale['total'] ?? 0) : ($sale->total ?? 0));
        $items_count = intval(is_array($sale) ? ($sale['items_count'] ?? 0) : ($sale->items_count ?? 0));
        
        $displayed_sales_total += $sale_total;
        $displayed_items_total += $items_count;
        
        // USAR LA FUNCIÓN CORREGIDA
        $method_label = wp_pos_get_sale_payment_method_display($sale);
        if (!isset($payment_methods_summary[$method_label])) {
            $payment_methods_summary[$method_label] = 0;
        }
        $payment_methods_summary[$method_label]++;
    }
}

$average_sale = $sales_count > 0 ? $displayed_sales_total / $sales_count : 0;
?>

<div class="wp-pos-recent-sales">
    <div class="wp-pos-section-header">
        <div class="wp-pos-section-title">
            <i class="dashicons dashicons-list-view"></i> 
            <?php _e('Ventas Recientes', 'wp-pos'); ?>
            <?php if ($sales_count > 0): ?>
                <span class="wp-pos-sales-count-badge"><?php echo $sales_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="wp-pos-section-actions">
            <?php if ($sales_count > 0): ?>
                <div class="wp-pos-quick-stats">
                    <span class="wp-pos-quick-stat" title="<?php esc_attr_e('Total mostrado', 'wp-pos'); ?>">
                        <i class="dashicons dashicons-money-alt"></i>
                        $<?php echo number_format($displayed_sales_total, 2); ?>
                    </span>
                    <span class="wp-pos-quick-stat" title="<?php esc_attr_e('Promedio por venta', 'wp-pos'); ?>">
                        <i class="dashicons dashicons-calculator"></i>
                        $<?php echo number_format($average_sale, 2); ?>
                    </span>
                </div>
            <?php endif; ?>
            <a href="#" class="wp-pos-view-all" data-action="view-all-sales">
                <?php _e('Ver todas', 'wp-pos'); ?> <i class="dashicons dashicons-arrow-right-alt"></i>
            </a>
        </div>
    </div>

    <?php if (!$success): ?>
        <div class="wp-pos-error-message" style="padding: 20px; background: #fff; border-radius: 8px; border-left: 4px solid #e74c3c; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #e74c3c;">
                <i class="dashicons dashicons-warning"></i>
                <?php _e('Error al cargar datos', 'wp-pos'); ?>
            </h4>
            <p style="margin: 0; color: #666;">
                <?php echo esc_html($message ?: __('No se pudieron cargar los datos de ventas.', 'wp-pos')); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($sales_count > 0): ?>
        <!-- Resumen interactivo de métodos de pago -->
        <?php if (!empty($payment_methods_summary)): ?>
            <div class="wp-pos-payment-methods-summary">
                <small class="wp-pos-summary-title"><?php _e('Métodos de pago:', 'wp-pos'); ?></small>
                <span class="wp-pos-method-summary wp-pos-method-all" 
                      data-method="all" 
                      title="<?php esc_attr_e('Mostrar todos los métodos de pago', 'wp-pos'); ?>">
                    <?php _e('Todos', 'wp-pos'); ?> (<?php echo array_sum($payment_methods_summary); ?>)
                </span>
                <?php 
                foreach ($payment_methods_summary as $method => $count): 
                    $method_key = sanitize_title($method);
                    $method_display = ucfirst($method);
                ?>
                    <span class="wp-pos-method-summary wp-pos-method-<?php echo esc_attr($method_key); ?>" 
                          data-method="<?php echo esc_attr($method_key); ?>"
                          title="<?php printf(esc_attr__('Filtrar por %s', 'wp-pos'), esc_attr($method_display)); ?>">
                        <?php echo esc_html($method_display); ?> (<?php echo intval($count); ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="wp-pos-table-container">
            <table class="wp-pos-table wp-pos-recent-sales-table">
                <thead>
                    <tr>
                        <th class="col-id">
                            <span><?php _e('ID', 'wp-pos'); ?></span>
                            <i class="dashicons dashicons-sort sort-indicator" data-column="id"></i>
                        </th>
                        <th class="col-customer">
                            <span><?php _e('Cliente', 'wp-pos'); ?></span>
                            <i class="dashicons dashicons-sort sort-indicator" data-column="customer"></i>
                        </th>
                        <th class="col-items">
                            <span><?php _e('Productos', 'wp-pos'); ?></span>
                            <i class="dashicons dashicons-sort sort-indicator" data-column="items"></i>
                        </th>
                        <th class="col-total">
                            <span><?php _e('Total', 'wp-pos'); ?></span>
                            <i class="dashicons dashicons-sort sort-indicator" data-column="total"></i>
                        </th>
                        <th class="col-payment"><?php _e('Método de Pago', 'wp-pos'); ?></th>
                        <th class="col-date">
                            <span><?php _e('Fecha', 'wp-pos'); ?></span>
                            <i class="dashicons dashicons-sort sort-indicator" data-column="date"></i>
                        </th>
                        <th class="col-actions"><?php _e('Acciones', 'wp-pos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_data as $index => $sale): 
                        // Asegurar que $sale sea un array
                        $sale = (array)$sale;
                        
                        // Extraer datos con valores por defecto
                        $sale_id = isset($sale['id']) ? intval($sale['id']) : 0;
                        $sale_date = isset($sale['date']) ? $sale['date'] : (isset($sale['created_at']) ? $sale['created_at'] : current_time('mysql'));
                        $sale_total = isset($sale['total']) ? floatval($sale['total']) : 0;
                        $items_count = isset($sale['items_count']) ? intval($sale['items_count']) : 0;
                        
                        // *** USAR LA FUNCIÓN CORREGIDA PARA MÉTODO DE PAGO ***
                        $method_label = wp_pos_get_sale_payment_method_display($sale);
                        
                        $customer_name = isset($sale['display_name']) ? $sale['display_name'] : (isset($sale['customer_name']) ? $sale['customer_name'] : 'Cliente no registrado');
                        $status = isset($sale['status']) ? $sale['status'] : 'completed';
                        $seller_name = isset($sale['seller']) ? $sale['seller'] : (isset($sale['seller_name']) ? $sale['seller_name'] : 'Sistema');
                        $tax = isset($sale['tax']) ? floatval($sale['tax']) : 0;
                        $note = isset($sale['note']) ? $sale['note'] : '';
                        
                        // Formatear fecha
                        $formatted_date = wp_pos_format_friendly_date($sale_date);
                        
                        // Determinar clase de estado
                        $status_class = wp_pos_get_sale_status_class($sale_total, $items_count);
                        ?>
                        <tr class="wp-pos-sale-row <?php echo esc_attr($status_class); ?>" 
                            data-sale-id="<?php echo esc_attr($sale_id); ?>"
                            data-total="<?php echo esc_attr($sale_total); ?>"
                            data-date="<?php echo esc_attr($sale_date); ?>"
                            data-items="<?php echo esc_attr($items_count); ?>">
                            
                            <td class="col-id">
                                <span class="sale-number">#<?php echo esc_html($sale_id); ?></span>
                            </td>
                            <td class="col-customer">
                                <div class="customer-info">
                                    <div class="customer-name"><?php echo esc_html($customer_name); ?></div>
                                    <?php if (!empty($seller_name) && $seller_name !== 'Sistema'): ?>
                                        <small class="text-muted">Vendedor: <?php echo esc_html($seller_name); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="col-items">
                                <span class="badge bg-secondary"><?php echo esc_html($items_count); ?> <?php echo _n('ítem', 'ítems', $items_count, 'wp-pos'); ?></span>
                            </td>
                            <td class="col-total">
                                <span class="sale-total">$<?php echo number_format($sale_total, 2); ?></span>
                                <?php if ($tax > 0): ?>
                                    <small class="text-muted d-block">IVA: $<?php echo number_format($tax, 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="col-payment">
                                <span class="payment-method-badge" data-method="<?php echo esc_attr(strtolower($sale['payment_method'] ?? 'efectivo')); ?>">
                                    <?php echo $method_label; ?>
                                </span>
                            </td>
                            
                            <td class="col-date">
                                <div class="date-info">
                                    <?php 
                                    // Debug - Ver formato real de la fecha
                                    $date_debug = "Formato original: " . var_export($sale_date, true);
                                    
                                    // Intentar convertir cualquier formato de fecha válido
                                    $timestamp = false;
                                    if (!empty($sale_date) && $sale_date !== '0000-00-00 00:00:00') {
                                        // Intentar diferentes formatos de fecha
                                        if (is_numeric($sale_date)) {
                                            // Si es un timestamp
                                            $timestamp = intval($sale_date);
                                        } else {
                                            $timestamp = strtotime($sale_date);
                                        }
                                    }
                                    
                                    // Determinar si la fecha es válida
                                    $valid_date = ($timestamp !== false && $timestamp > 0);
                                    
                                    // Añadir información de debug
                                    if ($valid_date) {
                                        $date_debug .= " - Timestamp: " . $timestamp . " - Fecha: " . date('Y-m-d H:i:s', $timestamp);
                                    } else {
                                        $date_debug .= " - Inválido";
                                    }
                                    error_log("DEBUG FECHA: " . $date_debug);
                                    ?>
                                    <span class="sale-date friendly-date" 
                                          title="<?php echo $valid_date ? esc_attr(date_i18n('d/m/Y H:i:s', $timestamp)) : 'Fecha no disponible'; ?>">
                                        <?php echo wp_pos_format_friendly_date($sale_date); ?>
                                    </span>
                                    <small class="exact-date">
                                        <?php echo $valid_date ? date_i18n('H:i', $timestamp) : '--:--'; ?>
                                    </small>
                                    <?php if (!empty($note)): ?>
                                        <small class="text-muted d-block note-indicator" title="<?php echo esc_attr($note); ?>">
                                            <i class="dashicons dashicons-format-aside"></i> Nota
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td class="col-actions actions-column">
                                <?php 
                                $view_nonce = wp_create_nonce('wp_pos_view_sale_' . $sale_id);
                                $ticket_nonce = wp_create_nonce('wp_pos_print_ticket_' . $sale_id);
                                ?>
                                <div class="action-buttons">
                                    <a href="#" class="button button-small action-view" 
                                       data-sale-id="<?php echo esc_attr($sale_id); ?>"
                                       data-nonce="<?php echo esc_attr($view_nonce); ?>"
                                       title="<?php esc_attr_e('Ver detalles de la venta', 'wp-pos'); ?>"
                                       data-tooltip="<?php esc_attr_e('Ver detalles completos', 'wp-pos'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <span class="screen-reader-text"><?php _e('Ver', 'wp-pos'); ?></span>
                                    </a>
                                    
                                    <a href="#" class="button button-small action-ticket" 
                                       data-sale-id="<?php echo esc_attr($sale_id); ?>"
                                       data-nonce="<?php echo esc_attr($ticket_nonce); ?>"
                                       title="<?php esc_attr_e('Imprimir ticket', 'wp-pos'); ?>"
                                       data-tooltip="<?php esc_attr_e('Generar ticket de venta', 'wp-pos'); ?>">
                                        <span class="dashicons dashicons-tickets-alt"></span>
                                        <span class="screen-reader-text"><?php _e('Ticket', 'wp-pos'); ?></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                
                <?php if ($sales_count > 0): ?>
                    <tfoot>
                        <tr class="wp-pos-summary-row">
                            <td colspan="7" class="text-right">
                                <div class="wp-pos-summary-details">
                                    <div class="wp-pos-summary-item">
                                        <span class="wp-pos-summary-label"><?php _e('Ventas mostradas:', 'wp-pos'); ?></span>
                                        <span class="wp-pos-summary-value"><?php echo number_format_i18n($sales_count); ?></span>
                                    </div>
                                    <div class="wp-pos-summary-item">
                                        <span class="wp-pos-summary-label"><?php _e('Artículos:', 'wp-pos'); ?></span>
                                        <span class="wp-pos-summary-value"><?php echo number_format_i18n($displayed_items_total); ?></span>
                                    </div>
                                    <div class="wp-pos-summary-item total">
                                        <span class="wp-pos-summary-label"><?php _e('Total:', 'wp-pos'); ?></span>
                                        <span class="wp-pos-summary-value">$<?php echo number_format($displayed_sales_total, 2); ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($payment_methods_summary) && count($payment_methods_summary) > 1): ?>
                                <div class="wp-pos-payment-methods-summary">
                                    <div class="wp-pos-summary-label"><?php _e('Métodos de pago:', 'wp-pos'); ?></div>
                                    <div class="wp-pos-payment-methods">
                                        <?php foreach ($payment_methods_summary as $method => $count): ?>
                                            <div class="wp-pos-payment-method">
                                                <span class="wp-pos-payment-method-name"><?php echo esc_html($method); ?>:</span>
                                                <span class="wp-pos-payment-method-count"><?php echo $count; ?> ventas</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if ($sales_count >= 10): ?>
            <div class="wp-pos-table-footer">
                <div class="wp-pos-pagination-info">
                    <?php printf(__('Mostrando las %d ventas más recientes', 'wp-pos'), $sales_count); ?>
                </div>
                <div class="wp-pos-table-actions">
                    <button class="wp-pos-load-more" data-action="load-more">
                        <?php _e('Cargar más', 'wp-pos'); ?>
                    </button>
                    <button class="wp-pos-export-visible" data-action="export-visible">
                        <i class="dashicons dashicons-download"></i>
                        <?php _e('Exportar visibles', 'wp-pos'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    
    <?php else: ?>
        <div class="wp-pos-no-sales">
            <div class="wp-pos-no-sales-content">
                <i class="dashicons dashicons-cart"></i>
                <h3><?php _e('No hay ventas recientes', 'wp-pos'); ?></h3>
                <p>
                    <?php 
                    if (!empty($message)) {
                        echo esc_html($message);
                    } else {
                        _e('No se encontraron ventas en el período seleccionado. Intenta cambiar los filtros o el rango de fechas.', 'wp-pos');
                    }
                    ?>
                </p>
                <div class="wp-pos-no-sales-actions">
                    <button class="wp-pos-create-sale" data-action="create-sale">
                        <i class="dashicons dashicons-plus-alt"></i>
                        <?php _e('Nueva Venta', 'wp-pos'); ?>
                    </button>
                    <button class="wp-pos-adjust-filters" data-action="adjust-filters">
                        <i class="dashicons dashicons-filter"></i>
                        <?php _e('Ajustar Filtros', 'wp-pos'); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Estilos para métodos de pago no especificados */
.no-payment-method {
    color: #ff6b6b;
    font-style: italic;
    opacity: 0.8;
}

/* Resaltar fila cuando se pasa el ratón sobre el método de pago no especificado */
.wp-pos-sale-row:hover .no-payment-method {
    opacity: 1;
    font-weight: 500;
}

/* Estilo para el tooltip del método de pago */
.payment-method-badge {
    position: relative;
    cursor: help;
}

/* Los estilos CSS permanecen igual que antes... */
.wp-pos-sales-count-badge {
    background: #6c5ce7;
    color: white;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
    font-weight: 600;
}

.wp-pos-quick-stats {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-right: 15px;
}

.wp-pos-quick-stat {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #666;
    background: #f5f5f5;
    padding: 4px 8px;
    border-radius: 12px;
}

.wp-pos-quick-stat i {
    font-size: 14px;
    color: #6c5ce7;
}

.wp-pos-error-message {
    margin: 20px 0;
}

.wp-pos-no-sales {
    padding: 60px 20px;
    text-align: center;
}

.wp-pos-no-sales-content {
    max-width: 400px;
    margin: 0 auto;
}

.wp-pos-no-sales i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}

.wp-pos-no-sales h3 {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 20px;
}

.wp-pos-no-sales p {
    color: #999;
    margin-bottom: 25px;
    line-height: 1.5;
}

.wp-pos-no-sales-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.wp-pos-create-sale,
.wp-pos-adjust-filters {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 2px solid #6c5ce7;
    color: #6c5ce7;
    background: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
}

.wp-pos-create-sale:hover {
    background: #6c5ce7;
    color: white;
}

.wp-pos-adjust-filters {
    border-color: #95a5a6;
    color: #95a5a6;
}

.wp-pos-adjust-filters:hover {
    background: #95a5a6;
    color: white;
}

/* Resto de estilos igual que antes... */
</style>

<script>
// JavaScript para mejorar la funcionalidad de la tabla
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Funcionalidad de ordenamiento
        $('.wp-pos-recent-sales-table th .sort-indicator').click(function(e) {
            e.stopPropagation();
            var $th = $(this).closest('th');
            var column = $(this).data('column');
            var currentOrder = $th.data('order') || 'asc';
            var newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Limpiar otros indicadores
            $('.wp-pos-recent-sales-table th').removeClass('sorted').removeData('order');
            $('.sort-indicator').removeClass('dashicons-arrow-up dashicons-arrow-down');
            
            // Establecer nuevo orden
            $th.addClass('sorted').data('order', newOrder);
            $(this).addClass(newOrder === 'asc' ? 'dashicons-arrow-up' : 'dashicons-arrow-down');
            
            // Ordenar filas
            sortTableRows(column, newOrder);
        });
        
        function sortTableRows(column, order) {
            var $tbody = $('.wp-pos-recent-sales-table tbody');
            var $rows = $tbody.find('tr').toArray();
            
            $rows.sort(function(a, b) {
                var aVal, bVal;
                
                switch(column) {
                    case 'id':
                        aVal = parseInt($(a).data('sale-id'));
                        bVal = parseInt($(b).data('sale-id'));
                        break;
                    case 'total':
                        aVal = parseFloat($(a).data('total'));
                        bVal = parseFloat($(b).data('total'));
                        break;
                    case 'items':
                        aVal = parseInt($(a).data('items'));
                        bVal = parseInt($(b).data('items'));
                        break;
                    case 'date':
                        aVal = new Date($(a).data('date'));
                        bVal = new Date($(b).data('date'));
                        break;
                    case 'customer':
                        aVal = $(a).find('.customer-name').text().toLowerCase();
                        bVal = $(b).find('.customer-name').text().toLowerCase();
                        break;
                    default:
                        return 0;
                }
                
                if (order === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            $tbody.empty().append($rows);
        }
        
        // Acciones de botones adicionales
        $(document).on('click', '.wp-pos-create-sale', function(e) {
            e.preventDefault();
            alert('Funcionalidad de nueva venta - Por implementar');
        });
        
        $(document).on('click', '.wp-pos-adjust-filters', function(e) {
            e.preventDefault();
            // Scroll hacia los filtros
            $('html, body').animate({
                scrollTop: $('.wp-pos-filter-section').offset().top - 20
            }, 500);
        });
        
        $(document).on('click', '.wp-pos-load-more', function(e) {
            e.preventDefault();
            alert('Cargar más ventas - Por implementar');
        });
        
        $(document).on('click', '.wp-pos-export-visible', function(e) {
            e.preventDefault();
            exportVisibleSales();
        });
        
        function exportVisibleSales() {
            var csv = 'ID,Cliente,Productos,Total,Método de Pago,Fecha\n';
            
            $('.wp-pos-sale-row').each(function() {
                var $row = $(this);
                var saleData = [
                    $row.find('.sale-number').text(),
                    $row.find('.customer-name').text(),
                    $row.find('.badge').text(),
                    $row.find('.sale-total').text(),
                    $row.find('.payment-method-badge').text(),
                    $row.find('.friendly-date').attr('title')
                ];
                csv += saleData.map(function(field) {
                    return '"' + field.replace(/"/g, '""') + '"';
                }).join(',') + '\n';
            });
            
            // Crear y descargar archivo
            var blob = new Blob([csv], { type: 'text/csv' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'ventas_recientes_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    });
    
    // Manejar clics en los métodos de pago para filtrar
    $(document).on('click', '.wp-pos-method-summary', function(e) {
        e.preventDefault();
        
        // Obtener el método seleccionado
        var $this = $(this);
        var method = $this.data('method');
        
        // Remover clase activa de todos los métodos
        $('.wp-pos-method-summary').removeClass('active');
        
        // Si se hizo clic en "Todos" o en el método ya activo
        if (method === 'all' || $this.hasClass('active')) {
            // Mostrar todas las filas
            $('.wp-pos-sale-row').show();
            $('.wp-pos-method-all').addClass('active');
            
            // Actualizar el contador de resultados
            updateResultsCount($('.wp-pos-sale-row:visible').length);
            return;
        }
        
        // Marcar como activo
        $this.addClass('active');
        
        // Filtrar filas por método de pago
        $('.wp-pos-sale-row').each(function() {
            var $row = $(this);
            var rowMethod = $row.find('.payment-method-badge').text().trim().toLowerCase();
            var methodKey = rowMethod.replace(/\s+/g, '-');
            
            if (methodKey === method) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        // Actualizar el contador de resultados
        updateResultsCount($('.wp-pos-sale-row:visible').length);
    });
    
    // Función para actualizar el contador de resultados
    function updateResultsCount(count) {
        var $countElement = $('.wp-pos-results-count');
        if ($countElement.length) {
            $countElement.text(count);
        }
    }
    
    // Inicializar el contador de resultados
    $(document).ready(function() {
        updateResultsCount($('.wp-pos-sale-row:visible').length);
    });
    
})(jQuery);
</script>

<style>
/* Estilos para los filtros de métodos de pago */
.wp-pos-method-summary {
    display: inline-block;
    margin: 2px 5px 2px 0;
    padding: 2px 8px;
    background: #f0f0f1;
    border-radius: 12px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #dcdcde;
}

.wp-pos-method-summary:hover {
    background: #e2e2e2;
    border-color: #999;
}

.wp-pos-method-summary.active {
    background: #2271b1;
    color: white;
    border-color: #135e96;
}

.wp-pos-method-all {
    font-weight: 600;
    background: #f0f0f1;
}

.wp-pos-method-all.active {
    background: #1d2327;
    color: white;
}
</style>