<?php
/**
 * Renderizador de Plantillas para Reportes WP-POS
 * 
 * Se encarga de renderizar todas las plantillas HTML del módulo de reportes.
 * Centraliza la lógica de renderizado para mejor mantenibilidad.
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
 * Clase para renderizar plantillas de reportes
 */
class WP_POS_Reports_Renderer {
    
    /**
     * Directorio base de plantillas
     * @var string
     */
    private $templates_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Update path to point to the correct templates directory (one level up from controllers)
        $this->templates_dir = dirname(dirname(__FILE__)) . '/templates/';
        
        // Log the templates directory for debugging
        error_log('Templates directory set to: ' . $this->templates_dir);
    }
    
    /**
     * Renderizar dashboard principal de reportes
     */
    public function render_reports_dashboard() {
        $template_path = dirname(__FILE__) . '/views/custom-reports-dashboard.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_error('Dashboard template not found: ' . $template_path);
        }
    }
    
    /**
     * CORREGIDO: Renderizar tarjetas de resumen
     */
    public function render_summary_cards($report_data) {
        ob_start();
        
        error_log('=== INICIO render_summary_cards ===');
        error_log('Datos recibidos: ' . print_r([
            'has_data' => !empty($report_data),
            'success' => $report_data['success'] ?? 'not_set',
            'sales_count' => $report_data['sales_count'] ?? 'not_set'
        ], true));
        
        // Asegurar que tenemos datos válidos
        if (!is_array($report_data)) {
            $report_data = [];
        }
        
        // Variable para la plantilla
        $totals = $report_data;
        
        // Incluir plantilla
        $template_path = $this->templates_dir . 'summary-cards.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            error_log('Error: Plantilla summary-cards.php no encontrada en: ' . $template_path);
            echo '<div class="error">Error: No se pudo cargar la plantilla de tarjetas de resumen.</div>';
        }
        
        $output = ob_get_clean();
        
        error_log('=== FIN render_summary_cards ===');
        error_log('Output length: ' . strlen($output) . ' bytes');
        
        return $output;
    }
    
    /**
     * CORREGIDO: Renderizar tabla de ventas recientes
     */
    public function render_recent_sales_table($report_data) {
        ob_start();
        
        error_log('=== INICIO render_recent_sales_table ===');
        error_log('Datos recibidos: ' . print_r([
            'has_data' => !empty($report_data),
            'has_recent_sales' => isset($report_data['recent_sales']),
            'recent_sales_count' => isset($report_data['recent_sales']) ? count($report_data['recent_sales']) : 0
        ], true));
        
        // Asegurar que tenemos datos válidos
        if (!is_array($report_data)) {
            $report_data = [
                'recent_sales' => [],
                'success' => false,
                'message' => 'No hay datos disponibles'
            ];
        }
        
        // Variable para la plantilla
        $recent_sales = $report_data;
        
        // Incluir plantilla
        $template_path = $this->templates_dir . 'recent-sales-table.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            error_log('Error: Plantilla recent-sales-table.php no encontrada en: ' . $template_path);
            echo '<div class="error">Error: No se pudo cargar la plantilla de ventas recientes.</div>';
        }
        
        $output = ob_get_clean();
        
        error_log('=== FIN render_recent_sales_table ===');
        error_log('Output length: ' . strlen($output) . ' bytes');
        
        return $output;
    }
    
    /**
     * CORREGIDO: Renderizar sección de gráficos
     */
    public function render_charts_section($report_data) {
        ob_start();
        
        error_log('=== INICIO render_charts_section ===');
        
        // Extraer datos de gráficos
        $chart_data = isset($report_data['chart_data']) ? $report_data['chart_data'] : [];
        
        // Datos específicos para compatibilidad con la plantilla
        $payment_methods_data = isset($chart_data['payment_methods']) ? $chart_data['payment_methods'] : ['labels' => ['Efectivo'], 'data' => [100]];
        $top_products_data = isset($chart_data['top_products']) ? $chart_data['top_products'] : ['labels' => ['Producto Demo'], 'data' => [50]];
        
        error_log('Datos para gráficos: ' . print_r([
            'has_chart_data' => !empty($chart_data),
            'payment_methods_count' => count($payment_methods_data['labels']),
            'top_products_count' => count($top_products_data['labels'])
        ], true));
        
        // Incluir plantilla
        $template_path = $this->templates_dir . 'charts-section.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            error_log('Error: Plantilla charts-section.php no encontrada en: ' . $template_path);
            echo '<div class="error">Error: No se pudo cargar la plantilla de gráficos.</div>';
        }
        
        $output = ob_get_clean();
        
        error_log('=== FIN render_charts_section ===');
        error_log('Output length: ' . strlen($output) . ' bytes');
        
        return $output;
    }
    
    /**
     * Renderizar detalles de una venta
     */
    public function render_sale_details($sale_id, $sale_data, $items = [], $payments = []) {
        ob_start();
        
        // Variables para la plantilla
        $sale = $sale_data;
        $page_title = 'Detalles de Venta #' . $sale_id;
        
        // Incluir plantilla
        $template_path = $this->templates_dir . 'sale-details.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_error('Sale details template not found');
        }
        
        return ob_get_clean();
    }
    
    /**
     * Renderizar ticket de venta
     */
    public function render_sale_ticket($sale_id, $sale_data, $items = [], $payments = []) {
        ob_start();
        
        // Variables para la plantilla
        $sale = $sale_data;
        $page_title = 'Ticket de Venta #' . $sale_id;
        
        // Incluir plantilla
        $template_path = $this->templates_dir . 'sale-ticket.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_error('Sale ticket template not found');
        }
        
        return ob_get_clean();
    }
    
    /**
     * Renderizar formulario de exportación
     */
    public function render_export_form($available_formats = []) {
        ob_start();
        
        // Variables para la plantilla
        $export_formats = !empty($available_formats) ? $available_formats : [
            'csv' => 'CSV',
            'excel' => 'Excel',
            'pdf' => 'PDF'
        ];
        
        ?>
        <div class="wp-pos-export-form">
            <h3><?php _e('Exportar Reportes', 'wp-pos'); ?></h3>
            
            <form id="wp-pos-export-form" method="post">
                <?php wp_nonce_field('wp_pos_export_reports', 'export_nonce'); ?>
                
                <div class="wp-pos-form-group">
                    <label for="export_format"><?php _e('Formato de exportación:', 'wp-pos'); ?></label>
                    <select id="export_format" name="export_format" required>
                        <?php foreach ($export_formats as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="wp-pos-form-group">
                    <label for="export_date_from"><?php _e('Fecha desde:', 'wp-pos'); ?></label>
                    <input type="date" id="export_date_from" name="export_date_from" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                </div>
                
                <div class="wp-pos-form-group">
                    <label for="export_date_to"><?php _e('Fecha hasta:', 'wp-pos'); ?></label>
                    <input type="date" id="export_date_to" name="export_date_to" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="wp-pos-form-group">
                    <button type="submit" class="button button-primary">
                        <i class="dashicons dashicons-download"></i>
                        <?php _e('Exportar', 'wp-pos'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Renderizar configuración de reportes
     */
    public function render_reports_settings($current_settings = []) {
        ob_start();
        
        // Valores por defecto
        $defaults = [
            'auto_refresh' => false,
            'refresh_interval' => 30,
            'default_period' => 'today',
            'items_per_page' => 10,
            'enable_caching' => true,
            'cache_duration' => 5
        ];
        
        $settings = wp_parse_args($current_settings, $defaults);
        
        ?>
        <div class="wp-pos-settings-form">
            <h3><?php _e('Configuración de Reportes', 'wp-pos'); ?></h3>
            
            <form id="wp-pos-settings-form" method="post">
                <?php wp_nonce_field('wp_pos_save_settings', 'settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="auto_refresh"><?php _e('Auto-actualización', 'wp-pos'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_refresh" name="auto_refresh" value="1" <?php checked($settings['auto_refresh']); ?>>
                                <?php _e('Activar actualización automática de datos', 'wp-pos'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="refresh_interval"><?php _e('Intervalo de actualización', 'wp-pos'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="refresh_interval" name="refresh_interval" value="<?php echo esc_attr($settings['refresh_interval']); ?>" min="10" max="300" step="5">
                            <span class="description"><?php _e('Segundos entre actualizaciones automáticas', 'wp-pos'); ?></span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_period"><?php _e('Período por defecto', 'wp-pos'); ?></label>
                        </th>
                        <td>
                            <select id="default_period" name="default_period">
                                <option value="today" <?php selected($settings['default_period'], 'today'); ?>><?php _e('Hoy', 'wp-pos'); ?></option>
                                <option value="this_week" <?php selected($settings['default_period'], 'this_week'); ?>><?php _e('Esta semana', 'wp-pos'); ?></option>
                                <option value="this_month" <?php selected($settings['default_period'], 'this_month'); ?>><?php _e('Este mes', 'wp-pos'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_caching"><?php _e('Cache de datos', 'wp-pos'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="enable_caching" name="enable_caching" value="1" <?php checked($settings['enable_caching']); ?>>
                                <?php _e('Activar cache para mejorar rendimiento', 'wp-pos'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Guardar Configuración', 'wp-pos'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Renderizar mensaje de error
     */
    public function render_error($message, $type = 'error') {
        $class = $type === 'warning' ? 'notice-warning' : 'notice-error';
        
        echo '<div class="notice ' . esc_attr($class) . '">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }
    
    /**
     * Renderizar mensaje de éxito
     */
    public function render_success($message) {
        echo '<div class="notice notice-success">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }
    
    /**
     * Renderizar estado de carga
     */
    public function render_loading($message = '') {
        $message = $message ?: __('Cargando...', 'wp-pos');
        
        echo '<div class="wp-pos-loading-state">';
        echo '<div class="wp-pos-spinner"></div>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }
    
    /**
     * Renderizar estado vacío
     */
    public function render_empty_state($message = '', $icon = 'info') {
        $message = $message ?: __('No hay datos disponibles', 'wp-pos');
        $icon_class = 'dashicons-' . $icon;
        
        echo '<div class="wp-pos-empty-state">';
        echo '<span class="dashicons ' . esc_attr($icon_class) . '"></span>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }
    
    /**
     * Obtener ruta de plantilla
     */
    public function get_template_path($template_name) {
        $path = $this->templates_dir . $template_name . '.php';
        return file_exists($path) ? $path : false;
    }
    
    /**
     * Verificar si una plantilla existe
     */
    public function template_exists($template_name) {
        return $this->get_template_path($template_name) !== false;
    }
    
    /**
     * Incluir plantilla con variables
     */
    public function include_template($template_name, $variables = []) {
        $template_path = $this->get_template_path($template_name);
        
        if (!$template_path) {
            $this->render_error("Template '$template_name' not found");
            return false;
        }
        
        // Extraer variables para la plantilla
        if (!empty($variables)) {
            extract($variables, EXTR_SKIP);
        }
        
        include $template_path;
        return true;
    }
    
    /**
     * Renderizar plantilla y devolver como string
     */
    public function render_template($template_name, $variables = []) {
        ob_start();
        $success = $this->include_template($template_name, $variables);
        $output = ob_get_clean();
        
        return $success ? $output : '';
    }
}