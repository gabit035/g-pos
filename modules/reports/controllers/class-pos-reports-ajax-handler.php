<?php
/**
 * Handler de AJAX para Reportes WP-POS - CORREGIDO
 * 
 * Maneja todas las peticiones AJAX del módulo de reportes de forma centralizada.
 * CORRIGE: Problemas con error 400 Bad Request y manejo de nonce
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
 * Clase para manejar peticiones AJAX de reportes - CORREGIDA
 */
class WP_POS_Reports_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor vacío, hooks se configuran desde el controlador principal
    }
    
    /**
     * Configurar hooks AJAX
     */
    public function setup_ajax_hooks() {
        // CORREGIDO: Usar el nombre de acción correcto
        add_action('wp_ajax_get_pos_report_data', array($this, 'ajax_get_report_data'));
        add_action('wp_ajax_nopriv_get_pos_report_data', array($this, 'ajax_get_report_data'));
        
        // Ventas recientes
        add_action('wp_ajax_wp_pos_get_recent_sales', array($this, 'ajax_get_recent_sales'));
        
        // Acciones de ventas
        add_action('wp_ajax_wp_pos_view_sale', array($this, 'ajax_view_sale_details'));
        add_action('wp_ajax_wp_pos_print_ticket', array($this, 'ajax_print_ticket'));
        
        // Cierre de caja
        add_action('wp_ajax_save_pos_cierre', array($this, 'ajax_save_pos_cierre'));
        add_action('wp_ajax_nopriv_save_pos_cierre', array($this, 'ajax_save_pos_cierre'));
    }
    
    /**
     * CORREGIDO: Manejador AJAX principal para obtener datos de reportes
     */
    public function ajax_get_report_data() {
        error_log('=== INICIO AJAX get_report_data (CORREGIDO) ===');
        
        // CORREGIDO: Verificar múltiples posibles nombres de nonce
        $nonce_verified = false;
        $nonce_fields = ['security', 'nonce', '_wpnonce'];
        
        foreach ($nonce_fields as $field) {
            if (isset($_POST[$field])) {
                if (wp_verify_nonce($_POST[$field], 'wp_pos_reports_nonce')) {
                    $nonce_verified = true;
                    error_log("Nonce verificado exitosamente usando campo: $field");
                    break;
                }
            }
        }
        
        if (!$nonce_verified) {
            error_log('Error: Nonce inválido o no encontrado');
            error_log('POST data: ' . print_r($_POST, true));
            wp_send_json_error([
                'message' => 'Token de seguridad inválido o expirado',
                'debug' => [
                    'nonce_fields_checked' => $nonce_fields,
                    'post_keys' => array_keys($_POST)
                ]
            ]);
            return;
        }
        
        // CORREGIDO: Verificar permisos
        if (!current_user_can('read')) {
            error_log('Error: Usuario sin permisos suficientes');
            wp_send_json_error(['message' => 'Permisos insuficientes']);
            return;
        }
        
        // Log de parámetros recibidos
        error_log('Params POST recibidos: ' . print_r($_POST, true));
        
        // CORREGIDO: Validar y sanitizar parámetros con valores por defecto
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'today';
        $seller_id = isset($_POST['seller_id']) ? sanitize_text_field($_POST['seller_id']) : 'all';
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'all';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        error_log("Filtros extraídos: period=$period, seller_id=$seller_id, payment_method=$payment_method, date_from=$date_from, date_to=$date_to");
        
        // Validar período
        $allowed_periods = ['all', 'today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'last_30_days', 'this_year', 'custom'];
        if (!in_array($period, $allowed_periods)) {
            error_log('Error: Período no válido - ' . $period);
            wp_send_json_error(['message' => 'Período no válido: ' . $period]);
            return;
        }
        
        // CORREGIDO: Calcular fechas según el período o usar fechas personalizadas
        if ($period === 'custom') {
            if (empty($date_from) || empty($date_to)) {
                wp_send_json_error(['message' => 'Las fechas son requeridas para el período personalizado']);
                return;
            }
            $final_date_from = $date_from;
            $final_date_to = $date_to;
        } else {
            list($final_date_from, $final_date_to) = $this->calculate_date_range($period);
        }
        
        error_log('Fechas calculadas: ' . $final_date_from . ' - ' . $final_date_to);
        
        try {
            // Verificar que la clase de datos existe
            if (!class_exists('WP_POS_Reports_Data')) {
                throw new Exception('Clase WP_POS_Reports_Data no encontrada');
            }
            
            // CORREGIDO: Crear argumentos con todos los filtros
            $args = [
                'date_from' => $final_date_from . ' 00:00:00',
                'date_to' => $final_date_to . ' 23:59:59',
                'seller_id' => $seller_id,
                'payment_method' => $payment_method,
                'status' => 'completed'
            ];
            
            error_log('Argumentos para get_totals: ' . print_r($args, true));
            
            // Obtener datos principales con filtros aplicados
            $report_data = WP_POS_Reports_Data::get_totals($args);
            
            // Verificar si hay error en los datos principales
            if (isset($report_data['success']) && $report_data['success'] === false) {
                wp_send_json_error([
                    'message' => $report_data['message'] ?? 'Error al obtener datos principales',
                    'debug' => $report_data['debug'] ?? null
                ]);
                return;
            }
            
            // Obtener ventas recientes con los mismos filtros
            $recent_sales_args = array_merge($args, ['limit' => 10]);
            $recent_sales_data = WP_POS_Reports_Data::get_recent_sales($recent_sales_args);
            
            // CORREGIDO: Procesar ventas recientes
            $recent_sales = [];
            if (isset($recent_sales_data['success']) && $recent_sales_data['success'] === true) {
                $recent_sales = $recent_sales_data['recent_sales'] ?? $recent_sales_data['sales'] ?? [];
            } else {
                error_log('Error al obtener ventas recientes: ' . ($recent_sales_data['message'] ?? 'Error desconocido'));
            }
            
            // CORREGIDO: Obtener datos de gráficos si la clase existe
            $chart_data = [];
            if (class_exists('WP_POS_Reports_Chart_Data')) {
                $chart_handler = new WP_POS_Reports_Chart_Data();
                $chart_data = $chart_handler->get_chart_data_for_period(
                    $final_date_from, $final_date_to, $seller_id, $payment_method
                );
            }
            
            // CORREGIDO: Renderizar plantillas si la clase existe
            $html_summary_cards = '';
            $html_recent_sales_table = '';
            $html_charts_section = '';
            
            if (class_exists('WP_POS_Reports_Renderer')) {
                $renderer = new WP_POS_Reports_Renderer();
                
                // Renderizar tarjetas de resumen
                $html_summary_cards = $renderer->render_summary_cards($report_data);
                
                // Preparar datos para la tabla de ventas recientes
                $recent_sales_for_table = [
                    'recent_sales' => $recent_sales,
                    'success' => true,
                    'message' => ''
                ];
                $html_recent_sales_table = $renderer->render_recent_sales_table($recent_sales_for_table);
                
                // Preparar datos para gráficos
                $chart_data_for_render = array_merge($report_data, ['chart_data' => $chart_data]);
                $html_charts_section = $renderer->render_charts_section($chart_data_for_render);
            }
            
            // Respuesta exitosa con información de filtros aplicados
            wp_send_json_success([
                'html_summary_cards' => $html_summary_cards,
                'html_recent_sales_table' => $html_recent_sales_table,
                'html_charts_section' => $html_charts_section,
                'chart_data' => $chart_data,
                'report_data' => $report_data,
                'recent_sales' => $recent_sales,
                'filters_applied' => [
                    'period' => $period,
                    'date_from' => $final_date_from,
                    'date_to' => $final_date_to,
                    'seller_id' => $seller_id,
                    'payment_method' => $payment_method
                ],
                'timestamp' => current_time('mysql'),
                'debug' => [
                    'sales_count' => $report_data['sales_count'] ?? 0,
                    'recent_sales_count' => count($recent_sales),
                    'filters_processed' => $args,
                    'chart_data_available' => !empty($chart_data)
                ]
            ]);
            
            error_log('=== FIN AJAX get_report_data (ÉXITO) ===');
            
        } catch (Exception $e) {
            error_log('Error en ajax_get_report_data: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error([
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
    
    /**
     * AJAX para obtener ventas recientes
     */
    public function ajax_get_recent_sales() {
        error_log('=== INICIO ajax_get_recent_sales ===');
        
        // CORREGIDO: Verificar nonce de manera más flexible
        $nonce_verified = false;
        if (isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'wp_pos_reports_nonce')) {
            $nonce_verified = true;
        } elseif (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wp_pos_reports_nonce')) {
            $nonce_verified = true;
        } elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'wp_pos_reports_nonce')) {
            $nonce_verified = true;
        }
        
        if (!$nonce_verified) {
            error_log('Error: Nonce inválido en ajax_get_recent_sales');
            wp_send_json_error(['message' => __('Token de seguridad inválido', 'wp-pos')]);
            return;
        }
        
        if (!current_user_can('read')) {
            error_log('Error: Usuario sin permisos en ajax_get_recent_sales');
            wp_send_json_error(['message' => __('No tienes permisos para ver esta información', 'wp-pos')]);
            return;
        }
        
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        if ($limit <= 0 || $limit > 50) {
            $limit = 5;
        }
        
        // Verificar que la clase existe
        if (!class_exists('WP_POS_Reports_Data')) {
            wp_send_json_error(['message' => 'Clase de datos no disponible']);
            return;
        }
        
        // Usar argumentos básicos para ventas recientes generales
        $args = [
            'limit' => $limit,
            'status' => 'completed'
        ];
        
        $result = WP_POS_Reports_Data::get_recent_sales($args);
        
        if (isset($result['success']) && $result['success'] === true) {
            $sales = isset($result['recent_sales']) ? $result['recent_sales'] : (isset($result['sales']) ? $result['sales'] : []);
            
            wp_send_json_success([
                'sales' => $sales,
                'data' => $sales,
                'count' => count($sales),
                'message' => 'Ventas obtenidas correctamente',
                'debug' => [
                    'limit' => $limit,
                    'source' => 'database',
                    'timestamp' => current_time('mysql')
                ]
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'] ?? 'No se pudieron obtener las ventas',
                'debug' => $result['error'] ?? 'Unknown error'
            ]);
        }
    }
    
    /**
     * Ver detalles de venta
     */
    public function ajax_view_sale_details() {
        ob_start();
        
        $sale_id = intval($_GET['sale_id'] ?? 0);
        
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wp_pos_view_sale_' . $sale_id)) {
            wp_die('Acceso no autorizado. Nonce inválido.', 'Error', array('response' => 403));
        }
        
        ob_clean();
        
        if (!$sale_id) {
            wp_die('ID de venta no válido', 'Error', array('response' => 400));
        }
        
        global $wpdb;
        $sale = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pos_sales WHERE id = %d",
            $sale_id
        ), ARRAY_A);
        
        if (!$sale) {
            wp_die('Venta no encontrada', 'Error', array('response' => 404));
        }
        
        // Obtener ítems de la venta
        $items = [];
        $items_table = $wpdb->prefix . 'pos_sale_items';
        if ($wpdb->get_var("SHOW TABLES LIKE '$items_table'") === $items_table) {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $items_table WHERE sale_id = %d",
                $sale_id
            ), ARRAY_A);
        } elseif (!empty($sale['items'])) {
            $items = maybe_unserialize($sale['items']);
            if (!is_array($items)) {
                $items = [];
            }
        }
        
        // Obtener pagos
        $payments = [];
        $payments_table = $wpdb->prefix . 'pos_payments';
        if ($wpdb->get_var("SHOW TABLES LIKE '$payments_table'") === $payments_table) {
            $payments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $payments_table WHERE sale_id = %d",
                $sale_id
            ), ARRAY_A);
        }
        
        $page_title = 'Detalles de Venta #' . $sale_id;
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/sale-details.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            wp_die('Plantilla no encontrada', 'Error', array('response' => 500));
        }
        
        wp_die();
    }
    
    /**
     * Imprimir ticket de venta
     */
    public function ajax_print_ticket() {
        $sale_id = intval($_GET['sale_id'] ?? 0);
        
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wp_pos_print_ticket_' . $sale_id)) {
            wp_die('Acceso no autorizado.', 'Error', array('response' => 403));
        }
        
        if (!$sale_id) {
            wp_die('ID de venta no válido', 'Error', array('response' => 400));
        }
        
        global $wpdb;
        $sale = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pos_sales WHERE id = %d",
            $sale_id
        ), ARRAY_A);
        
        if (!$sale) {
            wp_die('Venta no encontrada', 'Error', array('response' => 404));
        }
        
        // Obtener ítems
        $items = [];
        $items_table = $wpdb->prefix . 'pos_sale_items';
        if ($wpdb->get_var("SHOW TABLES LIKE '$items_table'") === $items_table) {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $items_table WHERE sale_id = %d",
                $sale_id
            ), ARRAY_A);
        } elseif (!empty($sale['items'])) {
            $items = maybe_unserialize($sale['items']);
            if (!is_array($items)) {
                $items = [];
            }
        }
        
        $page_title = 'Ticket de Venta #' . $sale_id;
        include plugin_dir_path(dirname(__FILE__)) . 'templates/sale-ticket.php';
        
        wp_die();
    }
    
    /**
     * Guardar cierre de caja
     */
    public function ajax_save_pos_cierre() {
        // CORREGIDO: Verificar nonce de manera más flexible
        $nonce_verified = false;
        if (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'wp_pos_reports_nonce')) {
            $nonce_verified = true;
        } elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_pos_reports_nonce')) {
            $nonce_verified = true;
        }
        
        if (!$nonce_verified) {
            wp_send_json_error(['message' => 'Token de seguridad inválido']);
            return;
        }
        
        $inicial = isset($_POST['inicial']) ? floatval($_POST['inicial']) : 0;
        $contable = isset($_POST['contable']) ? floatval($_POST['contable']) : 0;
        $real = isset($_POST['real']) ? floatval($_POST['real']) : 0;
        $diferencia = isset($_POST['diferencia']) ? sanitize_text_field($_POST['diferencia']) : '';
        
        if ($real < 0) {
            wp_send_json_error(['message' => __('El saldo real no puede ser negativo.', 'wp-pos')]);
            return;
        }
        
        $cierre_data = compact('inicial', 'contable', 'real', 'diferencia');
        $cierre_data['timestamp'] = current_time('mysql');
        $cierre_data['user_id'] = get_current_user_id();
        
        update_option('wp_pos_last_cierre', $cierre_data);
        
        // Historial
        $historial = get_option('wp_pos_cierre_historial', []);
        $historial[] = $cierre_data;
        if (count($historial) > 50) {
            $historial = array_slice($historial, -50);
        }
        update_option('wp_pos_cierre_historial', $historial);
        
        wp_send_json_success(['message' => __('Cierre de caja guardado exitosamente.', 'wp-pos')]);
    }
    
    /**
     * CORREGIDO: Calcular rango de fechas según período
     */
    private function calculate_date_range($period) {
        $current_timestamp = current_time('timestamp');
        
        switch ($period) {
            case 'all':
                return ['1970-01-01', date_i18n('Y-m-d', $current_timestamp)];
            case 'yesterday':
                $yesterday = $current_timestamp - DAY_IN_SECONDS;
                return [date_i18n('Y-m-d', $yesterday), date_i18n('Y-m-d', $yesterday)];
            case 'this_week':
                return [date_i18n('Y-m-d', strtotime('monday this week', $current_timestamp)), date_i18n('Y-m-d', $current_timestamp)];
            case 'last_week':
                return [date_i18n('Y-m-d', strtotime('monday last week', $current_timestamp)), date_i18n('Y-m-d', strtotime('sunday last week', $current_timestamp))];
            case 'this_month':
                return [date_i18n('Y-m-01', $current_timestamp), date_i18n('Y-m-d', $current_timestamp)];
            case 'last_month':
                return [date_i18n('Y-m-01', strtotime('first day of last month', $current_timestamp)), 
                        date_i18n('Y-m-t', strtotime('last day of last month', $current_timestamp))];
            case 'last_30_days':
                return [date_i18n('Y-m-d', $current_timestamp - (30 * DAY_IN_SECONDS)), date_i18n('Y-m-d', $current_timestamp)];
            case 'this_year':
                return [date_i18n('Y-01-01', $current_timestamp), date_i18n('Y-m-d', $current_timestamp)];
            default: // today
                return [date_i18n('Y-m-d', $current_timestamp), date_i18n('Y-m-d', $current_timestamp)];
        }
    }
}