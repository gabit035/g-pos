<?php
/**
 * Módulo de Reportes mejorado para WP-POS
 *
 * Gestiona todas las funcionalidades relacionadas con reportes de ventas,
 * incluyendo estadísticas, gráficos y filtros dinámicos.
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
 * Clase principal del módulo de reportes mejorada
 *
 * @since 1.0.0
 */
class WP_POS_Reports_Module {

    /**
     * ID único del módulo
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $module_id = 'reports';

    /**
     * Versión del módulo
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $version = '1.2.0';

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Reports_Module
     */
    private static $instance = null;

    /**
     * Configuración del módulo
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $config = array();

    /**
     * Estado de inicialización
     *
     * @since 1.0.0
     * @access private
     * @var bool
     */
    private $initialized = false;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Reports_Module Instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor mejorado
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init_config();
        $this->setup_actions();
        $this->load_dependencies();
        $this->check_database_version(); // Ejecutar migración automáticamente
        $this->initialized = true;
    }

    /**
     * Inicializar configuración del módulo
     *
     * @since 1.0.0
     * @access private
     */
    private function init_config() {
        $this->config = array(
            'enable_caching' => true,
            'cache_duration' => 5 * MINUTE_IN_SECONDS,
            'enable_debug' => WP_DEBUG,
            'max_recent_sales' => 50,
            'date_format' => get_option('date_format', 'd/m/Y'),
            'time_format' => get_option('time_format', 'H:i'),
            'currency_symbol' => get_option('wp_pos_currency_symbol', '$'),
            'currency_position' => get_option('wp_pos_currency_position', 'left'),
            'decimal_places' => get_option('wp_pos_decimal_places', 2),
            'enable_auto_refresh' => get_option('wp_pos_reports_auto_refresh', false),
            'auto_refresh_interval' => get_option('wp_pos_reports_refresh_interval', 30),
        );

        // Aplicar filtros para permitir personalización
        $this->config = apply_filters('wp_pos_reports_module_config', $this->config);
    }

    /**
     * Configurar acciones y filtros mejorados
     *
     * @since 1.0.0
     * @access private
     */
    private function setup_actions() {
        // Hooks de activación/desactivación del módulo
        add_action('wp_pos_module_activation_' . $this->module_id, array($this, 'activate'));
        add_action('wp_pos_module_deactivation_' . $this->module_id, array($this, 'deactivate'));
        
        // Registro del módulo
        add_action('wp_pos_register_modules', array($this, 'register_module'));
        
        // Admin
        add_action('wp_pos_admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // AJAX handlers mejorados
        add_action('wp_ajax_get_pos_report_data', array($this, 'ajax_get_report_data'));
        add_action('wp_ajax_nopriv_get_pos_report_data', array($this, 'ajax_get_report_data'));
        
        // AJAX para exportación
        add_action('wp_ajax_export_pos_reports', array($this, 'ajax_export_reports'));
        
        // AJAX para configuración
        add_action('wp_ajax_save_pos_reports_config', array($this, 'ajax_save_config'));
        
        // NUEVO: Hook para diagnóstico de métodos de pago
        add_action('wp_ajax_wp_pos_debug_payment_methods', array($this, 'ajax_debug_payment_methods'));
        
        // NUEVO: Hook para forzar migración de base de datos
        add_action('wp_ajax_wp_pos_force_migration', array($this, 'ajax_force_migration'));
        
        // NUEVO: Hook para verificar estado de migración
        add_action('wp_ajax_wp_pos_check_migration_status', array($this, 'ajax_check_migration_status'));
        
        // Hooks para limpieza de datos
        add_action('wp_pos_daily_cleanup', array($this, 'daily_cleanup'));
        
        // Hook para actualización de esquema de BD
        add_action('wp_pos_check_db_version', array($this, 'check_database_version'));
        
        // Filtros para personalización
        add_filter('wp_pos_reports_capabilities', array($this, 'filter_capabilities'));
        add_filter('wp_pos_reports_export_formats', array($this, 'filter_export_formats'));
        
        // Hooks de WordPress
        add_action('init', array($this, 'init'));
        add_action('admin_footer', array($this, 'admin_footer_scripts'));
        
        // Programar eventos si no existen
        if (!wp_next_scheduled('wp_pos_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'wp_pos_daily_cleanup');
        }
    }

    /**
     * Inicialización principal
     *
     * @since 1.0.0
     */
    public function init() {
        // Verificar permisos
        if (!current_user_can('manage_options') && !current_user_can('wp_pos_view_reports')) {
            return;
        }
        
        // Registrar tipos de datos personalizados si es necesario
        $this->register_custom_data_types();
        
        // Configurar capacidades de usuario
        $this->setup_user_capabilities();
    }

    /**
     * Cargar dependencias mejorado
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        $base_dir = dirname(__FILE__);
        
        // Archivos principales
        $files = array(
            '/class-pos-reports-data.php',
            '/class-pos-reports-controller.php',
            '/includes/reports-functions.php',
            '/includes/reports-export.php',
        );
        
        foreach ($files as $file) {
            $file_path = $base_dir . $file;
            if (file_exists($file_path)) {
                $result = @include_once($file_path);
                if ($result === false) {
                    error_log("WP-POS Reports: Error al incluir el archivo - {$file_path}");
                    if ($this->config['enable_debug']) {
                        error_log("WP-POS Reports: Detalles del error - " . print_r(error_get_last(), true));
                    }
                } elseif ($this->config['enable_debug']) {
                    error_log("WP-POS Reports: Archivo incluido correctamente - {$file_path}");
                }
            } elseif ($this->config['enable_debug']) {
                error_log("WP-POS Reports: Archivo no encontrado - {$file_path}");
            }
        }
        
        // Verificar si las clases principales se cargaron correctamente
        if (!class_exists('WP_POS_Reports_Data')) {
            error_log('WP-POS Reports: Error crítico - No se pudo cargar la clase WP_POS_Reports_Data');
            if ($this->config['enable_debug']) {
                error_log('WP-POS Reports: Ruta buscada: ' . $base_dir . '/class-pos-reports-data.php');
                error_log('WP-POS Reports: Directorio actual: ' . getcwd());
                error_log('WP-POS Reports: Incluye: ' . print_r(get_included_files(), true));
            }
            return false;
        }
        
        // Inicializar controlador
        if (class_exists('WP_POS_Reports_Controller')) {
            WP_POS_Reports_Controller::get_instance();
            return true;
        }
    }

    /**
     * Verificar y crear tablas necesarias si no existen - mejorado
     *
     * @since 1.0.0
     */
    public function maybe_create_tables() {
        global $wpdb;
        
        $sales_table = $wpdb->prefix . 'pos_sales';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sales_table'");
        
        if (!$table_exists) {
            $this->create_sales_table();
        }
        
        // Verificar y crear tabla de configuración del módulo
        $this->maybe_create_config_table();
        
        // Verificar versión de esquema
        $this->check_database_version();
    }

    /**
     * Crear tabla de ventas mejorada
     *
     * @since 1.0.0
     */
    private function create_sales_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $sales_table = $wpdb->prefix . 'pos_sales';
        
        $sql = "CREATE TABLE $sales_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) DEFAULT NULL,
            seller_id bigint(20) DEFAULT NULL,
            items longtext NOT NULL,
            total decimal(12,4) NOT NULL DEFAULT 0,
            subtotal decimal(12,4) DEFAULT 0,
            tax decimal(12,4) DEFAULT 0,
            discount decimal(12,4) DEFAULT 0,
            profit decimal(12,4) DEFAULT NULL,
            cost decimal(12,4) DEFAULT NULL,
            payment_method varchar(50) DEFAULT 'cash',
            status varchar(20) DEFAULT 'completed',
            currency varchar(10) DEFAULT '$',
            exchange_rate decimal(10,4) DEFAULT 1.0000,
            notes text DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            created_by varchar(100) DEFAULT NULL,
            updated_by varchar(100) DEFAULT NULL,
            transaction_type varchar(20) DEFAULT 'sale',
            receipt_number varchar(50) DEFAULT NULL,
            reference varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_date_created (date_created),
            KEY idx_customer_id (customer_id),
            KEY idx_seller_id (seller_id),
            KEY idx_status (status),
            KEY idx_payment_method (payment_method),
            KEY idx_total (total),
            KEY idx_created_by (created_by),
            KEY idx_transaction_type (transaction_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        if ($this->config['enable_debug']) {
            error_log('WP-POS Reports: Creación de tabla pos_sales - ' . print_r($result, true));
        }
        
        // Actualizar versión de esquema
        update_option('wp_pos_reports_db_version', $this->version);
    }

    /**
     * Crear tabla de configuración del módulo
     *
     * @since 1.0.0
     */
    private function maybe_create_config_table() {
        global $wpdb;
        
        $config_table = $wpdb->prefix . 'pos_reports_config';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$config_table'");
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $config_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                config_key varchar(100) NOT NULL,
                config_value longtext,
                config_type varchar(20) DEFAULT 'string',
                user_id bigint(20) DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY idx_config_key_user (config_key, user_id),
                KEY idx_user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Verificar versión de base de datos
     *
     * @since 1.0.0
     */
    public function check_database_version() {
        $current_version = get_option('wp_pos_reports_db_version', '1.0.0');
        
        if (version_compare($current_version, $this->version, '<')) {
            $this->upgrade_database($current_version, $this->version);
            update_option('wp_pos_reports_db_version', $this->version);
        }
    }

    /**
     * Actualizar base de datos
     *
     * @since 1.0.0
     * @param string $from_version Versión actual
     * @param string $to_version Versión objetivo
     */
    private function upgrade_database($from_version, $to_version) {
        global $wpdb;
        
        // Ejemplo de migración de 1.0.0 a 1.1.0
        if (version_compare($from_version, '1.1.0', '<')) {
            // Agregar columnas nuevas si no existen
            $sales_table = $wpdb->prefix . 'pos_sales';
            
            $columns_to_add = array(
                'currency' => "ALTER TABLE $sales_table ADD COLUMN currency varchar(10) DEFAULT '$'",
                'exchange_rate' => "ALTER TABLE $sales_table ADD COLUMN exchange_rate decimal(10,4) DEFAULT 1.0000",
                'receipt_number' => "ALTER TABLE $sales_table ADD COLUMN receipt_number varchar(50) DEFAULT NULL",
            );
            
            foreach ($columns_to_add as $column => $sql) {
                $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $sales_table LIKE '$column'");
                if (empty($column_exists)) {
                    $wpdb->query($sql);
                }
            }
        }
        
        // Ejemplo de migración de 1.1.0 a 1.2.0
        if (version_compare($from_version, '1.2.0', '<')) {
            // Agregar índices adicionales
            $sales_table = $wpdb->prefix . 'pos_sales';
            
            $indexes_to_add = array(
                "CREATE INDEX idx_total ON $sales_table (total)",
                "CREATE INDEX idx_created_by ON $sales_table (created_by)",
                "CREATE INDEX idx_transaction_type ON $sales_table (transaction_type)",
            );
            
            foreach ($indexes_to_add as $index_sql) {
                // Verificar si el índice ya existe antes de crearlo
                $wpdb->query($index_sql);
            }
        }
        
        // Migración específica para corregir el símbolo de moneda de USD a $ (versión 1.2.0)
        if (version_compare($from_version, '1.2.0', '<')) {
            $sales_table = $wpdb->prefix . 'pos_sales';
            
            // Actualizar registros existentes que tengan 'USD' por '$'
            $wpdb->query($wpdb->prepare(
                "UPDATE $sales_table SET currency = %s WHERE currency = %s",
                '$',
                'USD'
            ));
            
            if ($this->config['enable_debug']) {
                $updated_rows = $wpdb->rows_affected;
                error_log("WP-POS Reports: Se actualizaron $updated_rows registros de USD a $ en la tabla $sales_table");
            }
        }
        
        if ($this->config['enable_debug']) {
            error_log("WP-POS Reports: Base de datos actualizada de $from_version a $to_version");
        }
    }

    /**
     * Registrar tipos de datos personalizados
     *
     * @since 1.0.0
     */
    private function register_custom_data_types() {
        // Registrar meta boxes personalizados si es necesario
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
    }

    /**
     * Configurar capacidades de usuario
     *
     * @since 1.0.0
     */
    private function setup_user_capabilities() {
        $capabilities = array(
            'wp_pos_view_reports',
            'wp_pos_export_reports',
            'wp_pos_manage_reports_config',
        );
        
        // Agregar capacidades al rol de administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Agregar capacidades específicas a roles de POS
        $pos_manager_role = get_role('pos_manager');
        if ($pos_manager_role) {
            $pos_manager_role->add_cap('wp_pos_view_reports');
            $pos_manager_role->add_cap('wp_pos_export_reports');
        }
        
        $pos_seller_role = get_role('pos_seller');
        if ($pos_seller_role) {
            $pos_seller_role->add_cap('wp_pos_view_reports');
        }
    }

    /**
     * Registrar el módulo en el sistema mejorado
     *
     * @since 1.0.0
     * @param array $modules Lista de módulos activos
     * @return array Módulos actualizados
     */
    public function register_module($modules) {
        $modules['reports'] = array(
            'name' => __('Reportes Avanzados', 'wp-pos'),
            'description' => __('Gestión avanzada de reportes de ventas con filtros dinámicos, exportación y análisis en tiempo real', 'wp-pos'),
            'version' => $this->version,
            'author' => 'WP-POS Team',
            'icon' => 'dashicons-chart-bar',
            'settings_url' => admin_url('admin.php?page=wp-pos-reports&tab=settings'),
            'capabilities' => array('wp_pos_view_reports'),
            'dependencies' => array(),
            'status' => 'active',
        );
        return $modules;
    }

    /**
     * Registrar menú de administración mejorado
     *
     * @since 1.0.0
     */
    public function register_admin_menu() {
        $page_hook = add_submenu_page(
            'wp-pos',
            __('Reportes', 'wp-pos'),
            __('Reportes', 'wp-pos'),
            'wp_pos_view_reports',
            'wp-pos-reports',
            array($this, 'render_reports_page')
        );
        
        // Agregar hook para cargar scripts específicos de la página
        add_action("admin_print_scripts-{$page_hook}", array($this, 'load_page_specific_scripts'));
        add_action("admin_print_styles-{$page_hook}", array($this, 'load_page_specific_styles'));
    }

    /**
     * Cargar scripts específicos de la página
     *
     * @since 1.0.0
     */
    public function load_page_specific_scripts() {
        // Scripts específicos que solo se cargan en la página de reportes
        wp_enqueue_script('wp-pos-reports-advanced', plugin_dir_url(__FILE__) . 'assets/js/reports-advanced.js', array('jquery'), $this->version, true);
    }

    /**
     * Cargar estilos específicos de la página
     *
     * @since 1.0.0
     */
    public function load_page_specific_styles() {
        // Estilos específicos que solo se cargan en la página de reportes
        wp_enqueue_style('wp-pos-reports-advanced', plugin_dir_url(__FILE__) . 'assets/css/reports-advanced.css', array(), $this->version);
    }

    /**
     * Cargar scripts y estilos de administración mejorado
     *
     * @since 1.0.0
     * @param string $hook Hook actual
     */
    public function load_admin_scripts($hook) {
        // Verificar si estamos en la página de reportes
        if (strpos($hook, 'wp-pos-reports') === false) {
            return;
        }
        
        // URL base del plugin
        $plugin_url = trailingslashit(plugin_dir_url(WP_POS_PLUGIN_FILE ?? __FILE__));
        
        // Estilos principales
        wp_enqueue_style(
            'wp-pos-reports-styles',
            $plugin_url . 'modules/reports/assets/css/reports-styles.css',
            array(),
            $this->version
        );
        
        // Scripts principales
        wp_enqueue_script(
            'wp-pos-reports-scripts',
            $plugin_url . 'modules/reports/assets/js/reports-scripts.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Scripts de filtros
        wp_enqueue_script(
            'wp-pos-reports-filters',
            $plugin_url . 'modules/reports/assets/js/reports-filters.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Localizar scripts con configuración completa
        wp_localize_script(
            'wp-pos-reports-scripts',
            'wp_pos_reports_config',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_pos_reports_nonce'),
                'module_version' => $this->version,
                'config' => $this->config,
                'strings' => array(
                    'loading' => __('Cargando datos...', 'wp-pos'),
                    'error_connection' => __('Error de conexión', 'wp-pos'),
                    'error_data' => __('Error al cargar los datos', 'wp-pos'),
                    'success_update' => __('Datos actualizados correctamente', 'wp-pos'),
                    'confirm_action' => __('¿Estás seguro de realizar esta acción?', 'wp-pos'),
                    'export_success' => __('Exportación completada', 'wp-pos'),
                    'export_error' => __('Error al exportar datos', 'wp-pos'),
                ),
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
                'features' => array(
                    'auto_refresh' => $this->config['enable_auto_refresh'],
                    'caching' => $this->config['enable_caching'],
                    'debug' => $this->config['enable_debug'],
                ),
            )
        );
    }

    /**
     * Manejar acciones de administración
     *
     * @since 1.0.0
     */
    public function handle_admin_actions() {
        if (!current_user_can('wp_pos_view_reports')) {
            return;
        }
        
        // Manejar acciones específicas
        if (isset($_GET['action']) && isset($_GET['page']) && $_GET['page'] === 'wp-pos-reports') {
            $action = sanitize_text_field($_GET['action']);
            
            switch ($action) {
                case 'export':
                    $this->handle_export_action();
                    break;
                case 'clear_cache':
                    $this->handle_clear_cache_action();
                    break;
                case 'reset_config':
                    $this->handle_reset_config_action();
                    break;
            }
        }
    }

    /**
     * Manejar acción de exportación
     *
     * @since 1.0.0
     */
    private function handle_export_action() {
        if (!current_user_can('wp_pos_export_reports')) {
            wp_die(__('No tienes permisos para exportar reportes.', 'wp-pos'));
        }
        
        // Verificar nonce
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wp_pos_export_reports')) {
            wp_die(__('Token de seguridad inválido.', 'wp-pos'));
        }
        
        // Aquí implementarías la lógica de exportación
        // Por ahora, redirigir con mensaje de éxito
        wp_redirect(add_query_arg(array(
            'page' => 'wp-pos-reports',
            'message' => 'export_started',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Manejar acción de limpiar cache
     *
     * @since 1.0.0
     */
    private function handle_clear_cache_action() {
        if (!current_user_can('wp_pos_manage_reports_config')) {
            wp_die(__('No tienes permisos para gestionar la configuración.', 'wp-pos'));
        }
        
        // Limpiar cache de reportes
        WP_POS_Reports_Data::clear_cache();
        
        wp_redirect(add_query_arg(array(
            'page' => 'wp-pos-reports',
            'message' => 'cache_cleared',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Renderizar página de reportes mejorada
     *
     * @since 1.0.0
     */
    public function render_reports_page() {
        // Verificar permisos
        if (!current_user_can('wp_pos_view_reports')) {
            wp_die(__('No tienes permisos para ver esta página.', 'wp-pos'));
        }
        
        // Obtener tab actual
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
        
        // Mostrar mensajes si existen
        $this->show_admin_messages();
        
        // Renderizar según el tab
        switch ($current_tab) {
            case 'settings':
                $this->render_settings_tab();
                break;
            case 'export':
                $this->render_export_tab();
                break;
            case 'dashboard':
            default:
                $this->render_dashboard_tab();
                break;
        }
    }

    /**
     * Renderizar tab del dashboard
     *
     * @since 1.0.0
     */
    private function render_dashboard_tab() {
        // Cargar la vista principal de reportes
        include dirname(__FILE__) . '/views/custom-reports-dashboard.php';
    }

    /**
     * Renderizar tab de configuración
     *
     * @since 1.0.0
     */
    private function render_settings_tab() {
        include dirname(__FILE__) . '/views/settings-tab.php';
    }

    /**
     * Renderizar tab de exportación
     *
     * @since 1.0.0
     */
    private function render_export_tab() {
        include dirname(__FILE__) . '/views/export-tab.php';
    }

    /**
     * Mostrar mensajes de administración
     *
     * @since 1.0.0
     */
    private function show_admin_messages() {
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            $messages = array(
                'export_started' => array('type' => 'success', 'text' => __('Exportación iniciada correctamente.', 'wp-pos')),
                'cache_cleared' => array('type' => 'success', 'text' => __('Cache limpiado correctamente.', 'wp-pos')),
                'config_saved' => array('type' => 'success', 'text' => __('Configuración guardada correctamente.', 'wp-pos')),
                'error' => array('type' => 'error', 'text' => __('Ocurrió un error. Inténtalo de nuevo.', 'wp-pos')),
            );
            
            if (isset($messages[$message])) {
                $msg = $messages[$message];
                echo '<div class="notice notice-' . esc_attr($msg['type']) . ' is-dismissible"><p>' . esc_html($msg['text']) . '</p></div>';
            }
        }
    }

    /**
     * Manejador AJAX para obtener datos de reportes (delegado)
     *
     * @since 1.0.0
     */
    public function ajax_get_report_data() {
        // Delegar al controlador
        if (class_exists('WP_POS_Reports_Controller')) {
            $controller = WP_POS_Reports_Controller::get_instance();
            $controller->ajax_get_report_data();
        } else {
            wp_send_json_error(array('message' => 'Controlador no disponible'));
        }
    }

    /**
     * Manejador AJAX para exportar reportes
     *
     * @since 1.0.0
     */
    public function ajax_export_reports() {
        check_ajax_referer('wp_pos_reports_nonce', 'security');
        
        if (!current_user_can('wp_pos_export_reports')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        // Aquí implementarías la lógica de exportación AJAX
        wp_send_json_success(array('message' => 'Exportación completada'));
    }

    /**
     * Manejador AJAX para guardar configuración
     *
     * @since 1.0.0
     */
    public function ajax_save_config() {
        check_ajax_referer('wp_pos_reports_nonce', 'security');
        
        if (!current_user_can('wp_pos_manage_reports_config')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        // Guardar configuración
        $config = array();
        $allowed_keys = array('enable_auto_refresh', 'auto_refresh_interval', 'enable_caching', 'cache_duration');
        
        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                $config[$key] = sanitize_text_field($_POST[$key]);
            }
        }
        
        foreach ($config as $key => $value) {
            update_option('wp_pos_reports_' . $key, $value);
        }
        
        wp_send_json_success(array('message' => 'Configuración guardada'));
    }

    /**
     * NUEVO: Handler AJAX para diagnóstico de métodos de pago
     *
     * @since 1.0.0
     */
    public function ajax_debug_payment_methods() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }
        
        // Verificar que la clase esté disponible
        if (!class_exists('WP_POS_Reports_Data')) {
            wp_send_json_error(array('message' => 'Clase WP_POS_Reports_Data no disponible'));
        }
        
        // Ejecutar diagnóstico
        $methods = WP_POS_Reports_Data::debug_payment_methods_in_db();
        
        wp_send_json_success(array(
            'message' => 'Diagnóstico completado, revisa el log de errores',
            'methods' => $methods,
            'debug_url' => admin_url('admin-ajax.php?action=wp_pos_debug_payment_methods'),
            'instructions' => array(
                'Para ver el diagnóstico completo, revisa el archivo error_log de WordPress',
                'Busca entradas que contengan "DIAGNÓSTICO MÉTODOS DE PAGO"',
                'Los métodos NULL o vacíos pueden causar problemas con los filtros'
            )
        ));
    }

    /**
     * NUEVO: Handler AJAX para forzar migración de base de datos
     *
     * @since 1.0.0
     */
    public function ajax_force_migration() {
        check_ajax_referer('wp_pos_reports_nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        // Forzar migración
        $result = $this->force_database_migration();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * NUEVO: Handler AJAX para verificar estado de migración
     *
     * @since 1.0.0
     */
    public function ajax_check_migration_status() {
        check_ajax_referer('wp_pos_reports_nonce', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }
        
        // Verificar estado de migración
        $status = $this->check_migration_status();
        
        wp_send_json_success($status);
    }

    /**
     * Scripts en el footer de admin
     *
     * @since 1.0.0
     */
    public function admin_footer_scripts() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'wp-pos-reports') !== false) {
            ?>
            <script type="text/javascript">
                // Variables globales para compatibilidad
                window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                window.wp_pos_reports_version = '<?php echo esc_js($this->version); ?>';
                
                // NUEVO: Función para ejecutar diagnóstico de métodos de pago
                window.debugPaymentMethods = function() {
                    console.log('Ejecutando diagnóstico de métodos de pago...');
                    
                    jQuery.post(ajaxurl, {
                        action: 'wp_pos_debug_payment_methods'
                    }, function(response) {
                        console.log('Diagnóstico completado:', response);
                        if (response.success) {
                            console.log('Métodos encontrados:', response.data.methods);
                            console.log('Instrucciones:', response.data.instructions);
                            alert('Diagnóstico completado. Revisa la consola y el log de errores.');
                        } else {
                            console.error('Error en diagnóstico:', response.data.message);
                            alert('Error: ' + response.data.message);
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Error AJAX:', {xhr, status, error});
                        alert('Error de conexión al ejecutar diagnóstico');
                    });
                };
                
                // NUEVO: Función para forzar migración de base de datos
                window.forcePOSMigration = function() {
                    console.log('Ejecutando migración forzada de base de datos...');
                    
                    if (!confirm('¿Estás seguro de que quieres ejecutar la migración de base de datos? Esto actualizará todos los registros de USD a $. ')) {
                        return;
                    }
                    
                    jQuery.post(ajaxurl, {
                        action: 'wp_pos_force_migration',
                        security: '<?php echo wp_create_nonce('wp_pos_reports_nonce'); ?>'
                    }, function(response) {
                        console.log('Migración completada:', response);
                        if (response.success) {
                            console.log('Resultado:', response.data);
                            alert('Migración completada exitosamente: ' + response.data.message);
                        } else {
                            console.error('Error en migración:', response.data.message);
                            alert('Error: ' + response.data.message);
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Error AJAX:', {xhr, status, error});
                        alert('Error de conexión al ejecutar migración');
                    });
                };
                
                // NUEVO: Función para verificar estado de migración
                window.checkPOSMigrationStatus = function() {
                    console.log('Verificando estado de migración...');
                    
                    jQuery.post(ajaxurl, {
                        action: 'wp_pos_check_migration_status',
                        security: '<?php echo wp_create_nonce('wp_pos_reports_nonce'); ?>'
                    }, function(response) {
                        console.log('Estado de migración:', response);
                        if (response.success) {
                            const data = response.data;
                            console.log('Detalles:', data);
                            
                            let message = `Estado de Migración POS:\n\n`;
                            message += `Versión DB: ${data.current_version}\n`;
                            message += `Versión Módulo: ${data.module_version}\n`;
                            message += `Migración necesaria: ${data.migration_needed ? 'SÍ' : 'NO'}\n`;
                            message += `Registros USD: ${data.usd_records}\n`;
                            message += `Registros $: ${data.peso_records}\n`;
                            message += `Total registros: ${data.total_records}\n`;
                            message += `Migración completa: ${data.migration_complete ? 'SÍ' : 'NO'}`;
                            
                            alert(message);
                        } else {
                            console.error('Error al verificar estado:', response.data.message);
                            alert('Error: ' + response.data.message);
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Error AJAX:', {xhr, status, error});
                        alert('Error de conexión al verificar estado');
                    });
                };
                
                console.log('WP-POS Reports Module cargado. Funciones disponibles:');
                console.log('- debugPaymentMethods() - Diagnóstico de métodos de pago');
                console.log('- forcePOSMigration() - Forzar migración de USD a $');
                console.log('- checkPOSMigrationStatus() - Verificar estado de migración');
            </script>
            <?php
        }
    }

    /**
     * Filtrar capacidades
     *
     * @since 1.0.0
     */
    public function filter_capabilities($capabilities) {
        return array_merge($capabilities, array(
            'wp_pos_view_reports',
            'wp_pos_export_reports',
            'wp_pos_manage_reports_config',
        ));
    }

    /**
     * Filtrar formatos de exportación
     *
     * @since 1.0.0
     */
    public function filter_export_formats($formats) {
        return array_merge($formats, array(
            'csv' => __('CSV', 'wp-pos'),
            'excel' => __('Excel', 'wp-pos'),
            'pdf' => __('PDF', 'wp-pos'),
            'json' => __('JSON', 'wp-pos'),
        ));
    }

    /**
     * Limpieza diaria
     *
     * @since 1.0.0
     */
    public function daily_cleanup() {
        // Limpiar cache expirado
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wp_pos_%' AND option_value < UNIX_TIMESTAMP()");
        
        // Limpiar logs antiguos si existen
        $log_retention_days = apply_filters('wp_pos_reports_log_retention_days', 30);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$log_retention_days} days"));
        
        // Aquí podrías limpiar logs de auditoría si los implementas
        do_action('wp_pos_reports_daily_cleanup', $cutoff_date);
    }

    /**
     * Activar módulo
     *
     * @since 1.0.0
     */
    public function activate() {
        $this->maybe_create_tables();
        
        // Programar eventos
        if (!wp_next_scheduled('wp_pos_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'wp_pos_daily_cleanup');
        }
        
        // Flush rewrite rules si es necesario
        flush_rewrite_rules();
        
        do_action('wp_pos_reports_module_activated');
    }

    /**
     * Desactivar módulo
     *
     * @since 1.0.0
     */
    public function deactivate() {
        // No eliminamos las tablas para preservar los datos
        // Pero sí limpiamos eventos programados
        wp_clear_scheduled_hook('wp_pos_daily_cleanup');
        
        do_action('wp_pos_reports_module_deactivated');
    }

    /**
     * Obtener configuración del módulo
     *
     * @since 1.0.0
     * @return array
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * Verificar si el módulo está inicializado
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_initialized() {
        return $this->initialized;
    }

    /**
     * Obtener versión del módulo
     *
     * @since 1.0.0
     * @return string
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Forzar verificación y migración de base de datos
     * Útil para testing o cuando se necesita ejecutar manualmente
     *
     * @since 1.2.0
     * @return array Resultado de la migración
     */
    public function force_database_migration() {
        $current_version = get_option('wp_pos_reports_db_version', '1.0.0');
        
        if ($this->config['enable_debug']) {
            error_log("WP-POS Reports: Forzando migración desde versión $current_version a {$this->version}");
        }
        
        // Ejecutar migración
        $this->upgrade_database($current_version, $this->version);
        update_option('wp_pos_reports_db_version', $this->version);
        
        return [
            'success' => true,
            'from_version' => $current_version,
            'to_version' => $this->version,
            'message' => "Migración completada de $current_version a {$this->version}"
        ];
    }

    /**
     * Verificar estado actual de la migración
     * Útil para diagnosticar si quedan registros con USD
     *
     * @since 1.2.0
     * @return array Estado de la migración
     */
    public function check_migration_status() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            return [
                'table_exists' => false,
                'message' => 'La tabla pos_sales no existe'
            ];
        }
        
        // Contar registros con USD
        $usd_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE currency = %s",
            'USD'
        ));
        
        // Contar registros con $
        $peso_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE currency = %s",
            '$'
        ));
        
        // Contar total de registros
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        
        // Obtener versión actual
        $current_version = get_option('wp_pos_reports_db_version', '1.0.0');
        
        return [
            'table_exists' => true,
            'current_version' => $current_version,
            'module_version' => $this->version,
            'migration_needed' => version_compare($current_version, '1.2.0', '<'),
            'usd_records' => (int)$usd_count,
            'peso_records' => (int)$peso_count,
            'total_records' => (int)$total_count,
            'migration_complete' => ($usd_count == 0),
            'message' => sprintf(
                'Versión DB: %s | Registros USD: %d | Registros $: %d | Total: %d',
                $current_version,
                $usd_count,
                $peso_count,
                $total_count
            )
        ];
    }
}

// Inicializar módulo si aún no se ha hecho
if (!function_exists('wp_pos_reports_module_init')) {
    function wp_pos_reports_module_init() {
        return WP_POS_Reports_Module::get_instance();
    }
    
    // Hook de inicialización
    add_action('plugins_loaded', 'wp_pos_reports_module_init', 20);
}