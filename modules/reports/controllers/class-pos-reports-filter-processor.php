<?php
/**
 * Procesador de Filtros para Reportes WP-POS
 * 
 * Centraliza y procesa todos los filtros de reportes de forma consistente.
 * Corrige los problemas de aplicación de filtros de vendedor y método de pago.
 *
 * @package WP-POS
 * @subpackage Reports
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para procesar filtros de reportes de forma consistente
 */
class WP_POS_Reports_Filter_Processor {
    
    /**
     * Procesar y validar filtros de entrada
     * 
     * @param array $raw_filters Filtros sin procesar
     * @return array Filtros procesados y validados
     */
    public function process_filters($raw_filters) {
        error_log('=== INICIO process_filters ===');
        error_log('Filtros recibidos: ' . print_r($raw_filters, true));
        
        // Valores por defecto
        $defaults = [
            'date_from' => date('Y-m-d 00:00:00'),
            'date_to' => date('Y-m-d 23:59:59'),
            'seller_id' => 'all',
            'payment_method' => 'all',
            'status' => 'completed',
            'limit' => 10
        ];
        
        // Combinar con defaults
        $filters = wp_parse_args($raw_filters, $defaults);
        
        // Procesar fechas
        $filters = $this->process_dates($filters);
        
        // Procesar vendedor
        $filters = $this->process_seller($filters);
        
        // Procesar método de pago  
        $filters = $this->process_payment_method($filters);
        
        // Procesar estado
        $filters = $this->process_status($filters);
        
        // Validar límite
        $filters['limit'] = max(1, min(100, intval($filters['limit'])));
        
        error_log('Filtros procesados: ' . print_r($filters, true));
        error_log('=== FIN process_filters ===');
        
        return $filters;
    }
    
    /**
     * Procesar filtros de fecha
     */
    private function process_dates($filters) {
        // Asegurar formato correcto de fechas
        if (isset($filters['date_from'])) {
            $filters['date_from'] = $this->normalize_date($filters['date_from'], '00:00:00');
        }
        
        if (isset($filters['date_to'])) {
            $filters['date_to'] = $this->normalize_date($filters['date_to'], '23:59:59');
        }
        
        // Validar que date_from no sea mayor que date_to
        if (strtotime($filters['date_from']) > strtotime($filters['date_to'])) {
            error_log('Warning: date_from mayor que date_to, intercambiando');
            $temp = $filters['date_from'];
            $filters['date_from'] = $filters['date_to'];
            $filters['date_to'] = $temp;
        }
        
        return $filters;
    }
    
    /**
     * Procesar filtro de vendedor
     * CORREGIDO: Ahora maneja correctamente los filtros de vendedor
     */
    private function process_seller($filters) {
        $seller_id = $filters['seller_id'] ?? 'all';
        
        error_log("Procesando vendedor: '$seller_id'");
        
        // Si es 'all', no aplicar filtro
        if ($seller_id === 'all' || empty($seller_id)) {
            error_log('Vendedor: todos (sin filtro)');
            return $filters;
        }
        
        // Si es numérico, usar como ID directamente
        if (is_numeric($seller_id)) {
            $filters['seller_id'] = intval($seller_id);
            error_log("Vendedor: ID numérico = {$filters['seller_id']}");
            return $filters;
        }
        
        // Si es string, buscar por login
        $user = get_user_by('login', $seller_id);
        if ($user) {
            $filters['seller_id'] = $user->ID;
            error_log("Vendedor: '{$seller_id}' convertido a ID = {$filters['seller_id']}");
        } else {
            error_log("Warning: Usuario '{$seller_id}' no encontrado, usando 'all'");
            $filters['seller_id'] = 'all';
        }
        
        return $filters;
    }
    
    /**
     * Procesar filtro de método de pago
     * CORREGIDO: Ahora valida correctamente los métodos de pago
     * ACTUALIZADO: Usa las claves en español para consistencia
     */
    private function process_payment_method($filters) {
        $payment_method = $filters['payment_method'] ?? 'all';
        
        error_log("Procesando método de pago: '$payment_method'");
        
        // Si es 'all', no aplicar filtro
        if ($payment_method === 'all' || empty($payment_method)) {
            error_log('Método de pago: todos (sin filtro)');
            return $filters;
        }
        
        // Mapeo de métodos de pago (claves en español)
        $method_mapping = [
            'efectivo' => ['cash', 'efectivo', 'Efectivo', 'EFECTIVO', 'Cash', 'Efectivo en Caja', 'efectivo en caja', 'EFECTIVO EN CAJA'],
            'tarjeta' => ['card', 'tarjeta', 'Tarjeta', 'TARJETA', 'Card', 'credit', 'debit', 'credito', 'debito', 
                        'credit_card', 'debit_card', 'Credit Card', 'Debit Card', 'CREDITO', 'DEBITO', 'Tarjeta de Crédito', 'Tarjeta de Débito'],
            'transferencia' => ['transfer', 'transferencia', 'Transferencia', 'TRANSFERENCIA', 'Transfer', 'bank_transfer', 'bacs', 
                             'Transferencia Bancaria', 'transferencia bancaria', 'TRANSFERENCIA BANCARIA'],
            'cheque' => ['check', 'cheque', 'Cheque', 'CHEQUE', 'Check', 'Cheque de Gerencia', 'cheque de gerencia'],
            'otro' => ['other', 'otro', 'Otro', 'OTRO', 'Other', 'Otro Medio', 'otro medio', 'OTRO MEDIO']
        ];
        
        // Normalizar método de pago
        $normalized_method = strtolower(trim($payment_method));
        
        // Buscar el método en el mapeo
        foreach ($method_mapping as $key => $variations) {
            if (in_array($normalized_method, $variations)) {
                $filters['payment_method'] = $key;
                error_log("Método de pago: '{$normalized_method}' mapeado a '{$key}'");
                return $filters;
            }
        }
        
        // Si no se encontró en el mapeo, usar el valor original
        error_log("Método de pago '{$normalized_method}' no encontrado en el mapeo, usando valor original");
        $filters['payment_method'] = $normalized_method;
        
        return $filters;
    }
    
    /**
     * Procesar filtro de estado
     */
    private function process_status($filters) {
        $status = $filters['status'] ?? 'completed';
        
        $valid_statuses = ['all', 'completed', 'pending', 'cancelled', 'refunded'];
        
        if (!in_array($status, $valid_statuses)) {
            error_log("Warning: Estado '{$status}' no válido, usando 'completed'");
            $filters['status'] = 'completed';
        }
        
        return $filters;
    }
    
    /**
     * Construir cláusula WHERE para consultas SQL
     * CORREGIDO: Ahora construye correctamente las condiciones WHERE
     */
    public function build_where_clause($filters, $table_alias = 's') {
        $where_conditions = [];
        $params = [];
        
        error_log('=== INICIO build_where_clause CORREGIDO ===');
        error_log('Filtros para WHERE: ' . print_r($filters, true));
        
        // Filtro de estado
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'completed') {
                $where_conditions[] = "({$table_alias}.status = 'completed' OR {$table_alias}.status = '' OR {$table_alias}.status IS NULL)";
            } else {
                $where_conditions[] = "{$table_alias}.status = %s";
                $params[] = $filters['status'];
            }
        }
        
        // Filtro de fechas
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $where_conditions[] = "DATE({$table_alias}.date_created) BETWEEN %s AND %s";
            $params[] = date('Y-m-d', strtotime($filters['date_from']));
            $params[] = date('Y-m-d', strtotime($filters['date_to']));
        }
        
        // CORREGIDO: Filtro de vendedor
        if (isset($filters['seller_id']) && $filters['seller_id'] !== 'all') {
            if (is_numeric($filters['seller_id'])) {
                $where_conditions[] = "{$table_alias}.user_id = %d";
                $params[] = intval($filters['seller_id']);
                error_log("WHERE vendedor: user_id = " . intval($filters['seller_id']));
            }
        }
        
        // CORREGIDO: Filtro de método de pago - USAR MAPEO COMPLEJO
        if (isset($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $payment_where = $this->build_payment_method_where_unified($filters['payment_method'], $table_alias);
            if (!empty($payment_where['clause'])) {
                $where_conditions[] = $payment_where['clause'];
                $params = array_merge($params, $payment_where['params']);
                error_log("WHERE método de pago aplicado: " . $payment_where['clause']);
                error_log("WHERE parámetros método pago: " . print_r($payment_where['params'], true));
            }
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        error_log('WHERE clause final: ' . $where_clause);
        error_log('Parámetros finales: ' . print_r($params, true));
        error_log('=== FIN build_where_clause CORREGIDO ===');
        
        return [
            'where' => $where_clause,
            'params' => $params
        ];
    }
    


    /**
     * NUEVA FUNCIÓN: Método unificado para construir WHERE de método de pago
     * Centraliza toda la lógica en un solo lugar
     */
    /**
     * Construye la cláusula WHERE para filtrar por método de pago
     * Busca en la tabla pos_payments que contiene los métodos de pago
     * 
     * @param string $payment_method El método de pago por el que filtrar
     * @param string $table_alias El alias de la tabla principal (ventas)
     * @return array La cláusula WHERE y sus parámetros
     */
    public function build_payment_method_where_unified($payment_method, $table_alias = 's') {
        global $wpdb;
        
        if (empty($payment_method) || $payment_method === 'all') {
            error_log('No se aplicó filtro de método de pago (vacío o "todos")');
            return ['clause' => '', 'params' => []];
        }
        
        // Normalizar el método de pago a minúsculas para comparación consistente
        $payment_method = strtolower(trim($payment_method));
        
        error_log("=== CONSTRUYENDO CLAUSULA WHERE PARA MÉTODO DE PAGO ===");
        error_log("Filtrando por método de pago: '{$payment_method}'");
        
        // Mapeo mejorado con todas las variaciones posibles
        $method_mapping = [
            'efectivo' => ['cash', 'efectivo', 'Efectivo', 'EFECTIVO', 'Cash', 'Efectivo en Caja', 'efectivo en caja', 'EFECTIVO EN CAJA'],
            'tarjeta' => ['card', 'tarjeta', 'Tarjeta', 'TARJETA', 'Card', 'credit', 'debit', 'credito', 'debito', 
                        'credit_card', 'debit_card', 'Credit Card', 'Debit Card', 'CREDITO', 'DEBITO', 'Tarjeta de Crédito', 'Tarjeta de Débito'],
            'transferencia' => ['transfer', 'transferencia', 'Transferencia', 'TRANSFERENCIA', 'Transfer', 'bank_transfer', 'bacs', 
                             'Transferencia Bancaria', 'transferencia bancaria', 'TRANSFERENCIA BANCARIA'],
            'cheque' => ['check', 'cheque', 'Cheque', 'CHEQUE', 'Check', 'Cheque de Gerencia', 'cheque de gerencia'],
            'otro' => ['other', 'otro', 'Otro', 'OTRO', 'Other', 'Otro Medio', 'otro medio', 'OTRO MEDIO']
        ];
        
        // Si el método no está en el mapeo, usarlo directamente
        if (!isset($method_mapping[$payment_method])) {
            error_log("Método de pago '{$payment_method}' no está en el mapeo, usando búsqueda directa");
            $possible_values = [$payment_method];
        } else {
            // Obtener todos los valores posibles para este método de pago
            $possible_values = array_unique($method_mapping[$payment_method]);
            error_log("Método de pago '{$payment_method}' mapeado a valores: " . implode(', ', $possible_values));
        }
        
        // Crear marcadores de posición para la consulta preparada
        $placeholders = [];
        $params = [];
        
        foreach ($possible_values as $value) {
            $placeholders[] = '%s';
            $params[] = $value;
        }
        
        // Construir la cláusula WHERE con JOIN a la tabla de pagos
        $payments_table = $wpdb->prefix . 'pos_payments';
        $where_clause = "EXISTS (";
        $where_clause .= " SELECT 1 FROM {$payments_table} p ";
        $where_clause .= " WHERE p.sale_id = {$table_alias}.id ";
        $where_clause .= " AND LOWER(p.payment_method) IN (" . implode(',', $placeholders) . ")";
        $where_clause .= ")";
        
        error_log("Cláusula WHERE generada: " . $where_clause);
        error_log("Parámetros: " . print_r($params, true));
        
        return [
            'clause' => $where_clause,
            'params' => $params
        ];
    }


    /**
     * Obtener métodos de pago válidos
     */
    private function get_valid_payment_methods() {
        return [
            'cash', 'efectivo',
            'card', 'tarjeta', 'credit_card', 'debit_card',
            'transfer', 'transferencia', 'bank_transfer',
            'check', 'cheque',
            'paypal', 'stripe',
            'zelle', 'pago_movil', 'mobile_payment',
            'other', 'otro'
        ];
    }
    
    /**
     * Normalizar fecha
     */
    private function normalize_date($date, $time = '00:00:00') {
        if (empty($date)) {
            return current_time('mysql');
        }
        
        // Si ya tiene hora, usarla
        if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $date)) {
            return $date;
        }
        
        // Si solo tiene fecha, agregar hora
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
            return $date . ' ' . $time;
        }
        
        // Fallback
        return current_time('mysql');
    }
    
    /**
     * Validar filtros antes de aplicar
     */
    public function validate_filters($filters) {
        $errors = [];
        
        // Validar fechas
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            if (strtotime($filters['date_from']) > strtotime($filters['date_to'])) {
                $errors[] = 'La fecha de inicio no puede ser mayor que la fecha de fin';
            }
        }
        
        // Validar vendedor
        if (isset($filters['seller_id']) && $filters['seller_id'] !== 'all') {
            if (is_string($filters['seller_id']) && !is_numeric($filters['seller_id'])) {
                $user = get_user_by('login', $filters['seller_id']);
                if (!$user) {
                    $errors[] = "Vendedor '{$filters['seller_id']}' no encontrado";
                }
            }
        }
        
        // Validar método de pago
        if (isset($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $valid_methods = $this->get_valid_payment_methods();
            if (!in_array(strtolower($filters['payment_method']), $valid_methods)) {
                $errors[] = "Método de pago '{$filters['payment_method']}' no válido";
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Obtener información de debug sobre filtros aplicados
     */
    public function get_filter_debug_info($filters) {
        $debug = [
            'original_filters' => $filters,
            'processed_at' => current_time('mysql'),
            'applied_filters' => []
        ];
        
        foreach ($filters as $key => $value) {
            if ($value !== 'all' && !empty($value)) {
                $debug['applied_filters'][$key] = $value;
            }
        }
        
        return $debug;
    }
}