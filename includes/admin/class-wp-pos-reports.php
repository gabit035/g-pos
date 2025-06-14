<?php
/**
 * Clase para gestionar reportes
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_POS_Reports')) :

/**
 * Clase WP_POS_Reports
 *
 * Maneja toda la funcionalidad de reportes y estadísticas
 */
class WP_POS_Reports {
    
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Inicializar hooks
        add_action('wp_pos_admin_menu', array($this, 'register_reports_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_reports_scripts'));
        
        // Manejadores de exportación
        add_action('admin_init', array($this, 'handle_export_request'));
    }
    
    /**
     * Registra la página de reportes en el menú
     */
    public function register_reports_page() {
        // Ya está registrada en bootstrap.php
    }
    
    /**
     * Cargar scripts y estilos específicos para reportes
     *
     * @param string $hook
     */
    public function enqueue_reports_scripts($hook) {
        if ('wp-pos_page_wp-pos-reports' !== $hook) {
            return;
        }
        
        // Cargar Chart.js para gráficos (se puede implementar posteriormente)
        // wp_enqueue_script('chartjs', WP_POS_ASSETS_URL . 'js/vendor/chart.min.js', array(), '3.9.1', true);
        
        // Cargar estilos
        wp_enqueue_style('wp-pos-admin', WP_POS_ASSETS_URL . 'css/admin.css', array(), WP_POS_VERSION);
    
        // Scripts específicos para reportes
        wp_enqueue_script('wp-pos-reports', WP_POS_ASSETS_URL . 'js/admin-reports.js', array('jquery'), WP_POS_VERSION, true);
        
        // Localizar scripts
        wp_localize_script('wp-pos-reports', 'wp_pos_reports', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_pos_reports_nonce'),
            'currency_symbol' => wp_pos_get_currency_symbol(),
            'price_format' => wp_pos_get_price_format(),
            'i18n' => array(
                'no_data' => __('No hay datos disponibles para mostrar', 'wp-pos'),
                'loading' => __('Cargando datos...', 'wp-pos'),
                'error' => __('Error al cargar datos', 'wp-pos'),
                'sales' => __('Ventas', 'wp-pos'),
                'income' => __('Ingresos', 'wp-pos'),
            )
        ));
    }
    
    /**
     * Maneja las solicitudes de exportación (CSV, PDF)
     */
    public function handle_export_request() {
        if (!isset($_GET['page']) || 'wp-pos-reports' !== $_GET['page']) {
            return;
        }
        
        if (!isset($_GET['export'])) {
            return;
        }
        
        // Verificar nonce y permisos (a implementar)
        
        $export_type = sanitize_text_field($_GET['export']);
        $report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'sales';
        
        switch ($export_type) {
            case 'csv':
                $this->export_csv($report_type);
                break;
                
            case 'pdf':
                $this->export_pdf($report_type);
                break;
        }
    }
    
    /**
     * Generar reportes de ventas por período
     *
     * @param string $start_date Fecha de inicio
     * @param string $end_date Fecha de fin
     * @param string $report_type Tipo de reporte (sales, products, customers, payment)
     * @return array Datos del reporte
     */
    public function get_sales_report($start_date, $end_date, $report_type = 'sales') {
        // Inicializar estructura de datos del reporte
        $data = array(
            'total_sales' => 0,
            'total_income' => 0,
            'avg_per_sale' => 0,
            'daily_data' => array()
        );
        
        // Si es reporte por cliente, usamos una estructura diferente
        if ($report_type === 'customers') {
            return $this->get_customer_sales_report($start_date, $end_date);
        }
        
        // Reportes estándar por período (ventas diarias)
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        $total_income = 0;
        $total_sales = 0;
        $avg_sale = 0;
        
        while ($current_date <= $end_timestamp) {
            // Generar datos aleatorios para este día
            $sales_count = rand(5, 20);
            $day_income = $sales_count * rand(15, 100);
            
            // Formato de fecha para mostrar
            $date = date('Y-m-d', $current_date);
            $formatted_date = date_i18n(get_option('date_format'), $current_date);
            
            // Añadir a los datos diarios
            $data['daily_data'][] = array(
                'date' => $date,
                'formatted_date' => $formatted_date,
                'sales' => $sales_count,
                'income' => $day_income
            );
            
            // Actualizar totales
            $total_sales += $sales_count;
            $total_income += $day_income;
            
            // Pasar al siguiente día
            $current_date = strtotime('+1 day', $current_date);
        }
        
        // Calcular promedio si hay ventas
        $avg_sale = $total_sales > 0 ? $total_income / $total_sales : 0;
        
        // Actualizar resumen
        $data['total_sales'] = $total_sales;
        $data['total_income'] = $total_income;
        $data['avg_per_sale'] = $avg_sale;
        
        return $data;
    }
    
    /**
     * Exportar reporte a CSV
     *
     * @param string $report_type Tipo de reporte
     */
    public function export_csv($report_type) {
        // Obtener fechas
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        // Obtener datos del reporte
        $report_data = $this->get_sales_report($start_date, $end_date, $report_type);
        
        // Preparar encabezados para descarga
        $filename = 'wp-pos-' . $report_type . '-report-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        // Crear salida CSV
        $output = fopen('php://output', 'w');
        
        // Encabezados CSV según tipo de reporte
        if ($report_type === 'customers') {
            fputcsv($output, array(
                __('Cliente', 'wp-pos'),
                __('Email', 'wp-pos'),
                __('Ventas', 'wp-pos'),
                __('Ingresos', 'wp-pos')
            ));
        } else {
            fputcsv($output, array(
                __('Fecha', 'wp-pos'),
                __('Ventas', 'wp-pos'),
                __('Ingresos', 'wp-pos')
            ));
        }
        
        // Datos de ventas diarias o por cliente según el tipo de reporte
        foreach ($report_data['daily_data'] as $day) {
            if ($report_type === 'customers') {
                fputcsv($output, array(
                    $day['formatted_date'], // Nombre del cliente
                    isset($day['email']) ? $day['email'] : '',
                    $day['sales'], // Número de ventas
                    wp_pos_format_price($day['income'], false) // Total gastado
                ));
            } else {
                fputcsv($output, array(
                    $day['formatted_date'],
                    $day['sales'],
                    wp_pos_format_price($day['income'], false)
                ));
            }
        }
        
        // Totales
        fputcsv($output, array('', '', ''));
        fputcsv($output, array(
            __('Total', 'wp-pos'),
            $report_data['total_sales'],
            wp_pos_format_price($report_data['total_income'], false)
        ));
        
        // Promedio
        fputcsv($output, array(
            __('Promedio por venta', 'wp-pos'),
            '',
            wp_pos_format_price($report_data['avg_per_sale'], false)
        ));
        
        fclose($output);
        exit;
    }
    
    /**
     * Exportar reporte a PDF
     * (Versión básica - en producción se usaría una librería PDF completa)
     *
     * @param string $report_type Tipo de reporte
     */
    public function export_pdf($report_type) {
        // Mostrar mensaje de que esta funcionalidad estará disponible en la próxima versión
        wp_die(
            __('La exportación a PDF estará disponible en la próxima versión del plugin.', 'wp-pos'),
            __('Funcionalidad en desarrollo', 'wp-pos'),
            array('back_link' => true)
        );
    }
    
    /**
     * Genera un reporte de ventas por cliente
     * 
     * @param string $start_date Fecha de inicio
     * @param string $end_date Fecha de fin
     * @return array Datos del reporte
     */
    public function get_customer_sales_report($start_date, $end_date) {
        global $wpdb;
        
        // Estructura del reporte por cliente
        $data = array(
            'total_sales' => 0,
            'total_income' => 0,
            'avg_per_sale' => 0,
            'daily_data' => array(), // Usaremos esto para mantener compatibilidad con la interfaz, pero serán ventas por cliente
            'customer_data' => array() // Nuevo campo específico para datos de clientes
        );
        
        // Consultar ventas en el rango de fechas
        $sales_table = $wpdb->prefix . 'pos_sales';
        
        // Obtener ventas con datos de cliente
        $sql = $wpdb->prepare(
            "SELECT s.*, u.display_name as customer_name, u.user_email as customer_email 
            FROM {$sales_table} s 
            LEFT JOIN {$wpdb->users} u ON s.customer_id = u.ID 
            WHERE s.sale_date BETWEEN %s AND %s 
            ORDER BY s.customer_id",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        $sales = $wpdb->get_results($sql);
        
        if (empty($sales)) {
            // No hay ventas en este período
            return $data;
        }
        
        // Agrupar ventas por cliente
        $customers = array();
        $total_income = 0;
        $total_sales = count($sales);
        
        foreach ($sales as $sale) {
            // Si no hay cliente, ponerlo como 'invitado'
            $customer_id = !empty($sale->customer_id) ? $sale->customer_id : 0;
            $customer_name = !empty($sale->customer_name) ? $sale->customer_name : 'Invitado';
            $customer_email = !empty($sale->customer_email) ? $sale->customer_email : '';
            
            if (!isset($customers[$customer_id])) {
                $customers[$customer_id] = array(
                    'id' => $customer_id,
                    'name' => $customer_name,
                    'email' => $customer_email,
                    'sales_count' => 0,
                    'total_spent' => 0
                );
            }
            
            // Sumar ventas y montos
            $customers[$customer_id]['sales_count']++;
            $customers[$customer_id]['total_spent'] += floatval($sale->total);
            $total_income += floatval($sale->total);
        }
        
        // Ordenar clientes por total gastado (descendente)
        usort($customers, function($a, $b) {
            return $b['total_spent'] <=> $a['total_spent'];
        });
        
        // Actualizar datos del reporte
        $data['total_sales'] = $total_sales;
        $data['total_income'] = $total_income;
        $data['avg_per_sale'] = $total_sales > 0 ? $total_income / $total_sales : 0;
        $data['customer_data'] = array_values($customers);
        
        // Mantener compatibilidad con la interfaz actual
        // Usamos daily_data para mostrar los datos de cliente en la tabla
        foreach ($customers as $customer) {
            $data['daily_data'][] = array(
                'date' => $customer['id'],  // Usamos este campo para el ID
                'formatted_date' => $customer['name'], // Nombre del cliente
                'sales' => $customer['sales_count'],  // Número de ventas
                'income' => $customer['total_spent'],  // Total gastado
                'email' => $customer['email']  // Email del cliente (no se muestra en la interfaz actual)
            );
        }
        
        return $data;
    }
    
    /**
     * Procesa el reporte para mostrar en la página admin
     */
    public static function process_report() {
        // Crear instancia de la clase
        $reports = new self();
        
        // Obtener datos de filtros
        $report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'sales';
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        // Generar datos del reporte
        $report_data = $reports->get_sales_report($start_date, $end_date, $report_type);
        
        return $report_data;
    }
}

// Inicializar la clase
new WP_POS_Reports();

endif; // class_exists check
