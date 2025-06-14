<?php
/**
 * Funciones auxiliares para manejo de métodos de pago
 * Agregar a reports-functions.php o crear como archivo separado
 *
 * @package WP-POS
 * @subpackage Reports
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Normaliza un método de pago a un valor estándar
 *
 * @param string|null $payment_method Método de pago original
 * @return string Método de pago normalizado
 */
function wp_pos_normalize_payment_method($payment_method) {
    // Si es nulo, vacío o false, devolver 'no_especificado'
    if (empty($payment_method) || is_null($payment_method) || $payment_method === false) {
        return 'no_especificado';
    }
    
    // Convertir a minúsculas y quitar espacios
    $method = strtolower(trim($payment_method));
    
    // Mapeo de normalizaciones comunes
    $normalizations = [
        // Efectivo
        'cash' => 'efectivo',
        'efectivo' => 'efectivo',
        'dinero' => 'efectivo',
        'money' => 'efectivo',
        
        // Tarjeta
        'card' => 'tarjeta',
        'tarjeta' => 'tarjeta',
        'credit_card' => 'tarjeta',
        'debit_card' => 'tarjeta',
        'credito' => 'tarjeta',
        'debito' => 'tarjeta',
        
        // Transferencia
        'transfer' => 'transferencia',
        'transferencia' => 'transferencia',
        'bank_transfer' => 'transferencia',
        'wire_transfer' => 'transferencia',
        'bacs' => 'transferencia',
        
        // Cheque
        'check' => 'cheque',
        'cheque' => 'cheque',
        'cheque_bancario' => 'cheque',
        
        // Otros métodos conocidos
        'paypal' => 'paypal',
        'stripe' => 'stripe',
        'zelle' => 'zelle',
        'pago_movil' => 'pago_movil',
        'mobile_payment' => 'pago_movil',
        'other' => 'otro',
        'otro' => 'otro',
    ];
    
    // Si existe una normalización, usarla
    if (isset($normalizations[$method])) {
        return $normalizations[$method];
    }
    
    // Si no hay normalización específica, devolver el método limpio
    return $method;
}

/**
 * Obtiene el ícono apropiado para un método de pago
 *
 * @param string $payment_method Método de pago
 * @return string Ícono (emoji o clase CSS)
 */
function wp_pos_get_payment_method_icon($payment_method) {
    $method = wp_pos_normalize_payment_method($payment_method);
    
    $icons = [
        'efectivo' => '💵',
        'tarjeta' => '💳',
        'transferencia' => '🏦',
        'cheque' => '📄',
        'paypal' => '🅿️',
        'stripe' => '💳',
        'zelle' => '📱',
        'pago_movil' => '📱',
        'otro' => '💼',
        'no_especificado' => '❓',
    ];
    
    return $icons[$method] ?? '💼';
}

/**
 * Obtiene el color temático para un método de pago
 *
 * @param string $payment_method Método de pago
 * @return array Array con colores [text, background, border]
 */
function wp_pos_get_payment_method_colors($payment_method) {
    $method = wp_pos_normalize_payment_method($payment_method);
    
    $colors = [
        'efectivo' => [
            'text' => '#2e7d32',
            'background' => '#e8f5e9',
            'border' => '#4caf50'
        ],
        'tarjeta' => [
            'text' => '#1565c0',
            'background' => '#e3f2fd',
            'border' => '#2196f3'
        ],
        'transferencia' => [
            'text' => '#7b1fa2',
            'background' => '#f3e5f5',
            'border' => '#9c27b0'
        ],
        'cheque' => [
            'text' => '#ef6c00',
            'background' => '#fff3e0',
            'border' => '#ff9800'
        ],
        'paypal' => [
            'text' => '#003087',
            'background' => '#e3f2fd',
            'border' => '#0070ba'
        ],
        'zelle' => [
            'text' => '#00796b',
            'background' => '#e0f2f1',
            'border' => '#26a69a'
        ],
        'pago_movil' => [
            'text' => '#00796b',
            'background' => '#e0f2f1',
            'border' => '#26a69a'
        ],
        'otro' => [
            'text' => '#616161',
            'background' => '#f5f5f5',
            'border' => '#9e9e9e'
        ],
        'no_especificado' => [
            'text' => '#d32f2f',
            'background' => '#ffebee',
            'border' => '#f44336'
        ],
    ];
    
    return $colors[$method] ?? $colors['otro'];
}

/**
 * Genera HTML para badge de método de pago
 *
 * @param string $payment_method Método de pago
 * @param array $options Opciones adicionales [show_icon, custom_class, title]
 * @return string HTML del badge
 */
function wp_pos_render_payment_method_badge($payment_method, $options = []) {
    $defaults = [
        'show_icon' => true,
        'custom_class' => '',
        'title' => '',
    ];
    $options = wp_parse_args($options, $defaults);
    
    $normalized_method = wp_pos_normalize_payment_method($payment_method);
    $label = wp_pos_get_payment_method_label($payment_method);
    $icon = wp_pos_get_payment_method_icon($payment_method);
    $colors = wp_pos_get_payment_method_colors($payment_method);
    
    // Clases CSS
    $classes = ['payment-method-badge'];
    if (!empty($options['custom_class'])) {
        $classes[] = $options['custom_class'];
    }
    
    // Atributos
    $data_method = esc_attr($normalized_method);
    $title = !empty($options['title']) ? esc_attr($options['title']) : '';
    
    // Contenido del badge
    $content = '';
    if ($options['show_icon']) {
        $content .= '<span class="payment-icon">' . $icon . '</span> ';
    }
    $content .= esc_html(strip_tags($label));
    
    // Generar HTML
    $html = sprintf(
        '<span class="%s" data-method="%s" title="%s" style="color: %s; background: %s; border-color: %s;">%s</span>',
        implode(' ', $classes),
        $data_method,
        $title,
        $colors['text'],
        $colors['background'],
        $colors['border'],
        $content
    );
    
    return $html;
}

/**
 * Corrige métodos de pago nulos o vacíos en la base de datos
 *
 * @param string $default_method Método por defecto a usar
 * @return array Resultado de la operación [updated_count, errors]
 */
function wp_pos_fix_empty_payment_methods($default_method = 'no_especificado') {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'pos_sales';
    
    // Verificar que la tabla existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$sales_table'") !== $sales_table) {
        return [
            'updated_count' => 0,
            'errors' => ['La tabla de ventas no existe']
        ];
    }
    
    // Normalizar el método por defecto
    $default_method = wp_pos_normalize_payment_method($default_method);
    
    try {
        // Contar registros a actualizar
        $count_query = "SELECT COUNT(*) FROM $sales_table WHERE payment_method IS NULL OR payment_method = ''";
        $count = $wpdb->get_var($count_query);
        
        if ($count == 0) {
            return [
                'updated_count' => 0,
                'errors' => []
            ];
        }
        
        // Actualizar registros
        $update_query = $wpdb->prepare(
            "UPDATE $sales_table SET payment_method = %s WHERE payment_method IS NULL OR payment_method = ''",
            $default_method
        );
        
        $updated = $wpdb->query($update_query);
        
        if ($updated === false) {
            return [
                'updated_count' => 0,
                'errors' => [$wpdb->last_error]
            ];
        }
        
        return [
            'updated_count' => $updated,
            'errors' => []
        ];
        
    } catch (Exception $e) {
        return [
            'updated_count' => 0,
            'errors' => [$e->getMessage()]
        ];
    }
}

/**
 * Obtiene estadísticas de métodos de pago
 *
 * @param array $args Argumentos de filtro [date_from, date_to, status]
 * @return array Estadísticas por método de pago
 */
function wp_pos_get_payment_methods_stats($args = []) {
    global $wpdb;
    
    $defaults = [
        'date_from' => '',
        'date_to' => '',
        'status' => 'completed'
    ];
    $args = wp_parse_args($args, $defaults);
    
    $sales_table = $wpdb->prefix . 'pos_sales';
    
    $where_conditions = [];
    $params = [];
    
    // Filtro por estado
    if (!empty($args['status'])) {
        $where_conditions[] = 'status = %s';
        $params[] = $args['status'];
    }
    
    // Filtro por fechas
    if (!empty($args['date_from'])) {
        $where_conditions[] = 'DATE(date_created) >= %s';
        $params[] = $args['date_from'];
    }
    
    if (!empty($args['date_to'])) {
        $where_conditions[] = 'DATE(date_created) <= %s';
        $params[] = $args['date_to'];
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $query = "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total) as total_amount,
                AVG(total) as average_amount,
                MIN(total) as min_amount,
                MAX(total) as max_amount
              FROM $sales_table 
              $where_clause
              GROUP BY payment_method
              ORDER BY count DESC";
    
    if (!empty($params)) {
        $prepared_query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
    } else {
        $results = $wpdb->get_results($query, ARRAY_A);
    }
    
    // Procesar resultados
    $stats = [];
    foreach ($results as $row) {
        $method = wp_pos_normalize_payment_method($row['payment_method']);
        $label = wp_pos_get_payment_method_label($row['payment_method']);
        
        $stats[] = [
            'method' => $method,
            'original_method' => $row['payment_method'],
            'label' => strip_tags($label),
            'count' => intval($row['count']),
            'total_amount' => floatval($row['total_amount']),
            'average_amount' => floatval($row['average_amount']),
            'min_amount' => floatval($row['min_amount']),
            'max_amount' => floatval($row['max_amount']),
            'icon' => wp_pos_get_payment_method_icon($method),
            'colors' => wp_pos_get_payment_method_colors($method)
        ];
    }
    
    return $stats;
}

/**
 * Valida si un método de pago es válido
 *
 * @param string $payment_method Método de pago a validar
 * @return array [is_valid, message, suggestions]
 */
function wp_pos_validate_payment_method($payment_method) {
    $normalized = wp_pos_normalize_payment_method($payment_method);
    
    $valid_methods = [
        'efectivo', 'tarjeta', 'transferencia', 'cheque', 
        'paypal', 'stripe', 'zelle', 'pago_movil', 'otro'
    ];
    
    $is_valid = in_array($normalized, $valid_methods);
    
    $result = [
        'is_valid' => $is_valid,
        'normalized' => $normalized,
        'message' => '',
        'suggestions' => []
    ];
    
    if (!$is_valid) {
        if ($normalized === 'no_especificado') {
            $result['message'] = 'El método de pago no está especificado o es nulo.';
            $result['suggestions'] = ['efectivo', 'tarjeta', 'transferencia'];
        } else {
            $result['message'] = "El método '$payment_method' no es estándar.";
            
            // Sugerir métodos similares
            foreach ($valid_methods as $valid_method) {
                $similarity = similar_text(strtolower($payment_method), $valid_method, $percent);
                if ($percent > 60) {
                    $result['suggestions'][] = $valid_method;
                }
            }
            
            if (empty($result['suggestions'])) {
                $result['suggestions'] = ['efectivo', 'tarjeta', 'otro'];
            }
        }
    } else {
        $result['message'] = 'Método de pago válido.';
    }
    
    return $result;
}