<?php
/**
 * Controlador Principal de Reportes WP-POS (Refactorizado)
 * 
 * Controlador principal más pequeño y enfocado que delega responsabilidades
 * a clases especializadas para mejor mantenibilidad.
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
 * Clase controladora principal para el módulo de reportes
 * Ahora más pequeña y enfocada, delega a clases especializadas
 */
class WP_POS_Reports_Controller {
    
    /**
     * Instance
     * @var WP_POS_Reports_Controller
     */
    private static $instance = null;
    
    /**
     * Handlers especializados
     * @var array
     */
    private $handlers = [];
    
    /**
     * Get instance
     * @return WP_POS_Reports_Controller
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Inicializa handlers especializados
     */
    protected function __construct() {
        $this->load_handlers();
        $this->setup_hooks();
    }
    
    /**
     * Cargar handlers especializados
     */
    private function load_handlers() {
        $base_dir = dirname(dirname(__FILE__)); // Sube un nivel para llegar a la raíz del módulo
        
        // Primero cargar la clase de datos principal
        $data_class_file = $base_dir . '/class-pos-reports-data.php';
        if (file_exists($data_class_file)) {
            require_once $data_class_file;
            if (!class_exists('WP_POS_Reports_Data')) {
                error_log('WP-POS Reports: La clase WP_POS_Reports_Data no se pudo cargar desde: ' . $data_class_file);
            } else {
                error_log('WP-POS Reports: Clase WP_POS_Reports_Data cargada correctamente desde: ' . $data_class_file);
            }
        } else {
            error_log('WP-POS Reports: No se encontró el archivo de datos en: ' . $data_class_file);
        }
        
        // Cargar archivos de handlers
        $handler_files = [
            'ajax-handler' => '/controllers/class-pos-reports-ajax-handler.php',
            'chart-data' => '/controllers/class-pos-reports-chart-data.php', 
            'renderer' => '/controllers/class-pos-reports-renderer.php',
            'filter-processor' => '/controllers/class-pos-reports-filter-processor.php'
        ];
        
        foreach ($handler_files as $key => $file) {
            $file_path = $base_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("WP-POS Reports: Handler file not found - {$file_path}");
            }
        }
        
        // Inicializar handlers
        $this->handlers = [
            'ajax' => new WP_POS_Reports_Ajax_Handler(),
            'charts' => new WP_POS_Reports_Chart_Data(),
            'renderer' => new WP_POS_Reports_Renderer(),
            'filters' => new WP_POS_Reports_Filter_Processor()
        ];
    }
    
    /**
     * Configurar hooks principales
     */
    private function setup_hooks() {
        // Assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_footer', array($this, 'add_admin_js_vars'));
        
        // Delegar hooks AJAX a handler especializado
        if (isset($this->handlers['ajax'])) {
            $this->handlers['ajax']->setup_ajax_hooks();
        }
    }
    
    /**
     * Cargar assets CORREGIDO - Con coordinador anti-conflictos
     */
    public function enqueue_assets($hook) {
        // Verificar si estamos en la página de reportes
        if (strpos($hook, 'wp-pos-reports') === false) {
            return;
        }
        
        $plugin_url = trailingslashit(plugins_url('', dirname(dirname(dirname(__FILE__)))));
        $version = defined('WP_POS_VERSION') ? WP_POS_VERSION : time(); // Usar versión del plugin o timestamp
        
        // Estilos principales
        wp_enqueue_style(
            'wp-pos-reports-styles',
            $plugin_url . 'modules/reports/assets/css/reports-styles.css',
            array(),
            $version
        );
        
        // ✅ NUEVO: Cargar coordinador primero (previene conflictos)
        wp_enqueue_script(
            'wp-pos-reports-coordinator',
            $plugin_url . 'modules/reports/assets/js/wp-pos-reports-coordinator.js',
            array('jquery'),
            $version,
            true
        );
        
        // ✅ Scripts principales (CORREGIDO - dependencia del coordinador)
        wp_enqueue_script(
            'wp-pos-reports-scripts',
            $plugin_url . 'modules/reports/assets/js/reports-scripts.js',
            array('jquery', 'wp-pos-reports-coordinator'), // ← Dependencia agregada
            $version,
            true
        );
        
        // ✅ Script de filtros (CORREGIDO - dependencia del coordinador)
        wp_enqueue_script(
            'wp-pos-reports-filters',
            $plugin_url . 'modules/reports/assets/js/reports-filters.js',
            array('jquery', 'wp-pos-reports-coordinator'), // ← Dependencia agregada
            $version,
            true
        );
        
        // ✅ Chart.js para gráficos (si es necesario)
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localizar configuración (mantener método existente)
        $this->localize_scripts();
    }
    
    /**
     * Localizar scripts con configuración mejorada
     */
    private function localize_scripts() {
        // Usar el símbolo de moneda personalizado de WP POS
        $currency_symbol = function_exists('wp_pos_get_currency_symbol') ? wp_pos_get_currency_symbol() : '$';
        
        // ✅ Configuración principal para reports-scripts.js
        wp_localize_script(
            'wp-pos-reports-scripts',
            'wp_pos_reports_config',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_pos_reports_nonce'),
                'recentSalesLimit' => 5,
                'debug' => defined('WP_DEBUG') && WP_DEBUG, // ← Habilitar debug si WP_DEBUG está activo
                'autoRefresh' => false, // ← Configuración de auto-refresh
                'refreshInterval' => 30000, // ← 30 segundos
                'currency_symbol' => $currency_symbol, // Asegurar que el símbolo de moneda se pase al JS
                'strings' => array(
                    'loading' => __('Cargando...', 'wp-pos'),
                    'noRecentSales' => __('No hay ventas recientes para mostrar.', 'wp-pos'),
                    'errorLoading' => __('Error al cargar las ventas recientes. Intenta de nuevo.', 'wp-pos'),
                    'error' => __('Ha ocurrido un error. Por favor, inténtalo de nuevo.', 'wp-pos'),
                    'success' => __('Operación completada con éxito.', 'wp-pos'),
                    'success_update' => __('Datos actualizados correctamente', 'wp-pos'),
                    'error_connection' => __('Error de conexión', 'wp-pos'),
                    'error_data' => __('Error al cargar los datos', 'wp-pos'),
                ),
                // ✅ Información del usuario actual para filtros
                'current_user' => array(
                    'ID' => get_current_user_id(),
                    'login' => wp_get_current_user()->user_login,
                    'roles' => wp_get_current_user()->roles,
                    'capabilities' => array(
                        'view_reports' => current_user_can('wp_pos_view_reports'),
                        'export_reports' => current_user_can('wp_pos_export_reports'),
                        'manage_config' => current_user_can('wp_pos_manage_reports_config'),
                    ),
                ),
                // ✅ Configuración de características
                'features' => array(
                    'auto_refresh' => get_option('wp_pos_reports_auto_refresh', false),
                    'caching' => get_option('wp_pos_reports_enable_caching', true),
                    'debug' => defined('WP_DEBUG') && WP_DEBUG,
                ),
            )
        );
        
        // ✅ Configuración de moneda
        wp_localize_script(
            'wp-pos-reports-scripts',
            'wp_pos_currency',
            array(
                'symbol' => $currency_symbol,
                'position' => 'right',
                'decimal' => function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.',
                'thousand' => function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ',',
                'decimals' => function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2,
                'format' => function_exists('get_woocommerce_price_format') ? get_woocommerce_price_format() : '%2$s%1$s'
            )
        );
        
        // ✅ NUEVO: Configuración específica para reports-filters.js
        wp_localize_script(
            'wp-pos-reports-filters',
            'WP_POS_Reports_Filters_Config',
            array(
                'baseUrl' => admin_url('admin.php'),
                'autoSubmit' => get_option('wp_pos_reports_auto_submit', true),
                'debounceDelay' => 800, // ms
                'queryParams' => array(
                    'page' => 'wp-pos-reports'
                )
            )
        );
        
        // ✅ NUEVO: Configuración para el coordinador
        wp_localize_script(
            'wp-pos-reports-coordinator',
            'WP_POS_Coordinator_Config',
            array(
                'version' => defined('WP_POS_VERSION') ? WP_POS_VERSION : '1.0.0',
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_pos_reports_nonce'),
                'features' => array(
                    'diagnostics' => true,
                    'fallback_methods' => true,
                    'auto_recovery' => true
                )
            )
        );
    }
        
    /**
     * Variables JavaScript globales
     */
    public function add_admin_js_vars() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'wp-pos-reports') !== false) {
            ?>
            <script type="text/javascript">
                window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            </script>
            <?php
        }
    }
    
    /**
     * Renderizar página de reportes (delegar a renderer)
     */
    public function render_reports_page() {
        if (isset($this->handlers['renderer'])) {
            $this->handlers['renderer']->render_reports_dashboard();
        } else {
            // Fallback
            include_once plugin_dir_path(dirname(__FILE__)) . 'views/custom-reports-dashboard.php';
        }
    }
    
    /**
     * Renderizar dashboard de reportes (delegar a renderer)
     */
    public function render_reports_dashboard() {
        $this->render_reports_page();
    }
    
    /**
     * Obtener datos de reportes (delegar a filter processor y data)
     */
    public function get_report_data($date_from, $date_to, $seller_id = 'all', $payment_method = 'all') {
        if (!isset($this->handlers['filters'])) {
            error_log('WP-POS Reports: Filter processor not available');
            return $this->get_fallback_data();
        }
        
        // Procesar filtros usando handler especializado
        $processed_filters = $this->handlers['filters']->process_filters([
            'date_from' => $date_from,
            'date_to' => $date_to,
            'seller_id' => $seller_id,
            'payment_method' => $payment_method,
            'status' => 'completed'
        ]);
        
        // Obtener datos usando filtros procesados
        $report_data = WP_POS_Reports_Data::get_totals($processed_filters);
        $recent_sales_data = WP_POS_Reports_Data::get_recent_sales(array_merge($processed_filters, ['limit' => 50]));
        
        // Agregar ventas recientes al reporte
        if (isset($recent_sales_data['success']) && $recent_sales_data['success'] === true) {
            $report_data['recent_sales'] = $recent_sales_data['recent_sales'] ?? $recent_sales_data['sales'] ?? [];
        } else {
            $report_data['recent_sales'] = [];
        }
        
        // Obtener datos de gráficos usando handler especializado
        if (isset($this->handlers['charts'])) {
            $report_data['chart_data'] = $this->handlers['charts']->get_chart_data_for_period(
                $date_from, $date_to, $seller_id, $payment_method
            );
        }
        
        // Información de debug
        $report_data['debug_info'] = [
            'filters_applied' => $processed_filters,
            'recent_sales_count' => is_array($report_data['recent_sales']) ? count($report_data['recent_sales']) : 0,
            'timestamp' => current_time('mysql')
        ];
        
        return $report_data;
    }
    
    /**
     * Datos de fallback en caso de error
     */
    private function get_fallback_data() {
        return [
            'success' => false,
            'message' => 'Error: Handlers no disponibles',
            'sales_count' => 0,
            'total_revenue' => 0,
            'total_profit' => 0,
            'profit_margin' => 0,
            'average_sale' => 0,
            'recent_sales' => [],
            'chart_data' => []
        ];
    }
    
    /**
     * Obtener handler específico
     */
    public function get_handler($type) {
        return isset($this->handlers[$type]) ? $this->handlers[$type] : null;
    }
    
    /**
     * Verificar si todos los handlers están disponibles
     */
    public function handlers_available() {
        $required_handlers = ['ajax', 'charts', 'renderer', 'filters'];
        foreach ($required_handlers as $handler) {
            if (!isset($this->handlers[$handler])) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Obtener información de estado
     */
    public function get_status() {
        return [
            'handlers_loaded' => array_keys($this->handlers),
            'handlers_available' => $this->handlers_available(),
            'version' => '1.2.0'
        ];
    }
}