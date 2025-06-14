<?php
/**
 * G-POS Performance Loader
 * 
 * Este archivo centraliza la carga de las optimizaciones del sistema
 * siguiendo los principios de modularidad y escalabilidad.
 *
 * @package WP-POS
 * @subpackage Performance
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Asegurarse de que la constante del plugin esté definida
if (!defined('WP_POS_PLUGIN_DIR')) {
    define('WP_POS_PLUGIN_DIR', plugin_dir_path(dirname(dirname(dirname(__FILE__)))));
}

// Cargar dependencias si es necesario
if (!class_exists('WP_POS_Module_Abstract')) {
    $module_abstract_path = WP_POS_PLUGIN_DIR . 'includes/abstract-class-module.php';
    if (file_exists($module_abstract_path)) {
        require_once $module_abstract_path;
    } else {
        error_log('WP-POS: No se pudo cargar la clase abstracta del módulo: ' . $module_abstract_path);
        return;
    }
}

/**
 * Clase para cargar y gestionar todas las optimizaciones del sistema
 * 
 * @since 1.0.0
 */
class WP_POS_Performance_Loader {
    
    /**
     * Instancia de la clase
     *
     * @since 1.0.0
     * @var WP_POS_Performance_Loader
     */
    private static $instance = null;
    
    /**
     * Obtener instancia de la clase
     *
     * @since 1.0.0
     * @return WP_POS_Performance_Loader Instancia de la clase
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor privado para prevenir instanciación directa
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Inicializar el cargador cuando el módulo esté listo
        add_action('wp_pos_module_ready_performance', [$this, 'init']);
    }
    
    /**
     * Inicializar el cargador de rendimiento
     *
     * @since 1.0.0
     */
    public function init() {
        // Cargar optimizaciones
        $this->load_optimizations();
        
        // Agregar notificación de optimización disponible si es necesario
        add_action('admin_notices', [$this, 'maybe_show_optimization_notice']);
        
        // Registrar menús de administración
        add_action('admin_menu', [$this, 'register_admin_menus'], 20);
    }
    
    /**
     * Cargar todas las optimizaciones del sistema
     */
    /**
     * Cargar todas las optimizaciones del sistema
     *
     * @since 1.0.0
     */
    public function load_optimizations() {
        // Ruta base de archivos de optimización
        $base_path = WP_POS_PLUGIN_DIR . 'modules/performance/includes/';
        
        // Listado de archivos de optimización
        $optimization_files = [
            'class-pos-cleanup.php',
            'class-pos-database-optimizer.php',
            'class-pos-search-service.php'
        ];
        
        // Cargar cada archivo si existe
        foreach ($optimization_files as $file) {
            $file_path = $base_path . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Cargar el optimizador de scripts si existe
        $script_optimizer = WP_POS_PLUGIN_DIR . 'assets/js/script-optimizer.php';
        if (file_exists($script_optimizer)) {
            require_once $script_optimizer;
        }
        
        // Actualizar flag de optimizaciones disponibles
        self::update_optimization_status();
    }
    
    
    /**
     * Renderizar pu00e1gina de rendimiento
     */
    public static function render_performance_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Cargar estilos de administraciu00f3n de G-POS para mantener coherencia visual
        wp_enqueue_style('wp-pos-admin', WP_POS_PLUGIN_URL . 'assets/css/admin-style.css');
        wp_enqueue_style('wp-pos-admin-settings', WP_POS_PLUGIN_URL . 'assets/css/admin-settings.css');
        wp_enqueue_style('wp-pos-performance', WP_POS_PLUGIN_URL . 'assets/css/performance-page.css', [], WP_POS_VERSION);
        
        // Cargar iconos de WordPress
        wp_enqueue_style('dashicons');
        
        // Procesar acciones
        if (isset($_POST['wp_pos_action']) && check_admin_referer('wp_pos_performance_tools')) {
            $action = sanitize_key($_POST['wp_pos_action']);
            
            switch ($action) {
                case 'run_all_optimizations':
                    self::run_all_optimizations();
                    echo '<div class="notice notice-success"><p>' . 
                         esc_html__('Todas las optimizaciones han sido ejecutadas con éxito.', 'wp-pos') . 
                         '</p></div>';
                    break;
                    
                case 'clear_cache':
                    self::clear_all_cache();
                    echo '<div class="notice notice-success"><p>' . 
                         esc_html__('Caché limpiada con éxito.', 'wp-pos') . 
                         '</p></div>';
                    break;
            }
            
            // Actualizar estado
            self::update_optimization_status();
        }
        
        // Inicio de la página con estilos coherentes a G-POS
        echo '<div class="wrap wp-pos-admin wp-pos-admin-settings" style="width:1240px; margin:20px auto;">';
        echo '<h1>' . esc_html__('Centro de Rendimiento G-POS', 'wp-pos') . '</h1>';
        echo '<p class="wp-pos-intro">' . esc_html__('Esta página le permite ejecutar diferentes optimizaciones y mejorar el rendimiento del sistema.', 'wp-pos') . '</p>';
        
        // Mostrar estado del sistema
        self::display_system_status();
        
        // Contenedor de herramientas con estilos coherentes a G-POS
        echo '<div class="wp-pos-cards-container">';
        
        // Tarjeta 1: Optimización completa
        echo '<div class="wp-pos-card">';
        echo '<h2>' . esc_html__('Optimización Completa', 'wp-pos') . '</h2>';
        echo '<div class="wp-pos-card-content">';
        echo '<p>' . esc_html__('Ejecuta todas las optimizaciones disponibles, incluyendo limpieza de base de datos, optimización de búsquedas y limpieza de archivos temporales.', 'wp-pos') . '</p>';
        
        echo '<form method="post">';
        wp_nonce_field('wp_pos_performance_tools');
        echo '<input type="hidden" name="wp_pos_action" value="run_all_optimizations">';
        echo '<div class="wp-pos-card-actions">';
        echo '<button type="submit" class="button button-primary">
               <span class="dashicons dashicons-performance"></span> ' . 
               esc_html__('Ejecutar todas las optimizaciones', 'wp-pos') . '
             </button>';
        echo '</div>';
        echo '</form>';
        echo '</div>'; // Fin del contenido
        echo '</div>'; // Fin de tarjeta
        
        // Tarjeta 2: Limpieza de caché
        echo '<div class="wp-pos-card">';
        echo '<h2>' . esc_html__('Limpieza de Caché', 'wp-pos') . '</h2>';
        echo '<div class="wp-pos-card-content">';
        echo '<p>' . esc_html__('Elimina archivos temporales, transients y caché del sistema para liberar espacio y mejorar el rendimiento.', 'wp-pos') . '</p>';
        
        echo '<form method="post">';
        wp_nonce_field('wp_pos_performance_tools');
        echo '<input type="hidden" name="wp_pos_action" value="clear_cache">';
        echo '<div class="wp-pos-card-actions">';
        echo '<button type="submit" class="button button-primary">
               <span class="dashicons dashicons-trash"></span> ' . 
               esc_html__('Limpiar caché', 'wp-pos') . '
             </button>';
        echo '</div>';
        echo '</form>';
        echo '</div>'; // Fin del contenido
        echo '</div>'; // Fin de tarjeta
        
        // Tarjeta 3: Acceso rápido a otras herramientas
        echo '<div class="wp-pos-card">';
        echo '<h2>' . esc_html__('Herramientas Adicionales', 'wp-pos') . '</h2>';
        echo '<div class="wp-pos-card-content">';
        echo '<p>' . esc_html__('Accede a otras herramientas específicas de mantenimiento y optimización del sistema.', 'wp-pos') . '</p>';
        
        echo '<div class="wp-pos-card-actions">';
        echo '<a href="' . admin_url('admin.php?page=wp-pos-maintenance') . '" class="button">
              <span class="dashicons dashicons-database"></span> ' . 
              esc_html__('Mantenimiento de Base de Datos', 'wp-pos') . '
            </a>';
        echo '<a href="' . admin_url('tools.php?page=wp-pos-optimizer') . '" class="button">
              <span class="dashicons dashicons-admin-tools"></span> ' . 
              esc_html__('Optimizador de Scripts', 'wp-pos') . '
            </a>';
        echo '</div>';
        echo '</div>'; // Fin del contenido
        echo '</div>'; // Fin de tarjeta
        
        echo '</div>'; // Fin del contenedor de tarjetas
        
        // Historial de optimizaciones
        self::display_optimization_history();
        
        echo '</div>'; // Fin del wrap
    }
    
    /**
     * Ejecutar todas las optimizaciones disponibles
     */
    private static function run_all_optimizations() {
        // 1. Limpiar sistema
        if (class_exists('WP_POS_System_Cleanup')) {
            WP_POS_System_Cleanup::perform_daily_cleanup();
        }
        
        // 2. Optimizar base de datos
        if (class_exists('WP_POS_Database_Optimizer')) {
            WP_POS_Database_Optimizer::optimize_database();
        }
        
        // 3. Reconstruir u00edndices de bu00fasqueda
        if (class_exists('WP_POS_Search_Service')) {
            WP_POS_Search_Service::rebuild_search_index();
        }
        
        // 4. Limpiar cachu00e9
        self::clear_all_cache();
        
        // Registrar historial
        self::log_optimization([
            'type' => 'full_optimization',
            'timestamp' => time(),
            'details' => __('Optimizaciu00f3n completa ejecutada', 'wp-pos')
        ]);
    }
    
    /**
     * Limpiar toda la cachu00e9 del sistema
     */
    private static function clear_all_cache() {
        global $wpdb;
        
        // 1. Limpiar transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_wp_pos_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_wp_pos_%'");
        
        // 2. Limpiar archivos temporales
        $temp_dirs = [
            WP_POS_PLUGIN_DIR . 'temp/',
            WP_POS_PLUGIN_DIR . 'assets/cache/'
        ];
        
        foreach ($temp_dirs as $dir) {
            if (file_exists($dir) && is_dir($dir)) {
                $handle = opendir($dir);
                
                if ($handle) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file != '.' && $file != '..') {
                            $file_path = $dir . $file;
                            if (is_file($file_path)) {
                                @unlink($file_path);
                            }
                        }
                    }
                    
                    closedir($handle);
                }
            }
        }
        
        // 3. Actualizar marcas de tiempo
        update_option('wp_pos_last_cache_clear', time());
        
        // Registrar historial
        self::log_optimization([
            'type' => 'cache_clear',
            'timestamp' => time(),
            'details' => __('Limpieza de cache ejecutada', 'wp-pos')
        ]);
    }
    
    /**
     * Mostrar estado del sistema
     */
    private static function display_system_status() {
        // Obtener datos de estado
        $last_optimization = get_option('wp_pos_last_database_optimization', 0);
        $last_cache_clear = get_option('wp_pos_last_cache_clear', 0);
        $last_index_rebuild = get_option('wp_pos_last_search_index_rebuild', 0);
        
        // Formatear fechas
        $format_time = function($timestamp) {
            if (!$timestamp) return __('Nunca', 'wp-pos');
            return human_time_diff($timestamp, time()) . ' ' . __('atrás', 'wp-pos');
        };
        
        // Panel de estado con clases de G-POS
        echo '<div class="wp-pos-section">';
        echo '<h2 class="wp-pos-section-title">' . esc_html__('Estado del Sistema', 'wp-pos') . '</h2>';
        
        echo '<div class="wp-pos-status-grid">';
        
        // Estado 1: Última optimización
        echo '<div class="wp-pos-status-item">';
        echo '<h3>' . esc_html__('Última optimización completa', 'wp-pos') . '</h3>';
        echo '<p class="wp-pos-status-value">' . esc_html($format_time($last_optimization)) . '</p>';
        echo '</div>';
        
        // Estado 2: Última limpieza de caché
        echo '<div class="wp-pos-status-item">';
        echo '<h3>' . esc_html__('Última limpieza de caché', 'wp-pos') . '</h3>';
        echo '<p class="wp-pos-status-value">' . esc_html($format_time($last_cache_clear)) . '</p>';
        echo '</div>';
        
        // Estado 3: Última reconstrucción de índices
        echo '<div class="wp-pos-status-item">';
        echo '<h3>' . esc_html__('Última actualización de índices', 'wp-pos') . '</h3>';
        echo '<p class="wp-pos-status-value">' . esc_html($format_time($last_index_rebuild)) . '</p>';
        echo '</div>';
        
        // Estado 4: Tamaño de caché
        echo '<div class="wp-pos-status-item">';
        echo '<h3>' . esc_html__('Tamaño de caché', 'wp-pos') . '</h3>';
        echo '<p class="wp-pos-status-value">' . esc_html(self::get_cache_size()) . '</p>';
        echo '</div>';
        
        echo '</div>'; // Fin del grid
        echo '</div>'; // Fin del panel
    }
    
    /**
     * Mostrar historial de optimizaciones
     */
    private static function display_optimization_history() {
        // Obtener historial de optimizaciones
        $history = get_option('wp_pos_optimization_history', []);
        
        // Panel de historial con clases de G-POS
        echo '<div class="wp-pos-section">';
        echo '<h2 class="wp-pos-section-title">' . esc_html__('Historial de Optimizaciones', 'wp-pos') . '</h2>';
        
        if (empty($history)) {
            echo '<div class="wp-pos-section-content">';
            echo '<p class="wp-pos-empty-state">' . esc_html__('No hay registros de optimizaciones anteriores.', 'wp-pos') . '</p>';
            echo '</div>';
        } else {
            // Limitar a los últimos 10 registros
            $history = array_slice($history, 0, 10);
            
            echo '<div class="wp-pos-table-responsive">';
            echo '<table class="wp-pos-table">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Fecha y Hora', 'wp-pos') . '</th>';
            echo '<th>' . esc_html__('Tipo', 'wp-pos') . '</th>';
            echo '<th>' . esc_html__('Detalles', 'wp-pos') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($history as $entry) {
                // Formatear tipo de optimización
                $type_label = __('Desconocido', 'wp-pos');
                $type_icon = 'dashicons-admin-tools';
                
                switch ($entry['type']) {
                    case 'full_optimization':
                        $type_label = __('Optimización Completa', 'wp-pos');
                        $type_icon = 'dashicons-performance';
                        break;
                    case 'cache_clear':
                        $type_label = __('Limpieza de Caché', 'wp-pos');
                        $type_icon = 'dashicons-trash';
                        break;
                    case 'database_optimization':
                        $type_label = __('Optimización de Base de Datos', 'wp-pos');
                        $type_icon = 'dashicons-database';
                        break;
                    case 'index_rebuild':
                        $type_label = __('Reconstrucción de Índices', 'wp-pos');
                        $type_icon = 'dashicons-search';
                        break;
                }
                
                echo '<tr>';
                echo '<td>' . esc_html(date_i18n('Y-m-d H:i:s', $entry['timestamp'])) . '</td>';
                echo '<td><span class="dashicons ' . esc_attr($type_icon) . '"></span> ' . esc_html($type_label) . '</td>';
                echo '<td>' . esc_html($entry['details']) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>'; // Fin de tabla responsiva
        }
        
        echo '</div>'; // Fin del panel
    }
    
    /**
     * Obtener tamau00f1o de la cachu00e9
     * 
     * @return string Tamau00f1o formateado
     */
    private static function get_cache_size() {
        $temp_dirs = [
            WP_POS_PLUGIN_DIR . 'temp/',
            WP_POS_PLUGIN_DIR . 'assets/cache/'
        ];
        
        $total_size = 0;
        
        foreach ($temp_dirs as $dir) {
            if (file_exists($dir) && is_dir($dir)) {
                $handle = opendir($dir);
                
                if ($handle) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file != '.' && $file != '..') {
                            $file_path = $dir . $file;
                            if (is_file($file_path)) {
                                $total_size += filesize($file_path);
                            }
                        }
                    }
                    
                    closedir($handle);
                }
            }
        }
        
        return self::format_size($total_size);
    }
    
    /**
     * Mostrar notificaciu00f3n de optimizaciu00f3n si es necesario
     */
    public static function maybe_show_optimization_notice() {
        // Verificar si estamos en una pu00e1gina del plugin
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wp-pos') === false) {
            return;
        }
        
        // Verificar si se debe mostrar la notificaciu00f3n
        $optimizations_needed = get_option('wp_pos_optimizations_needed', false);
        
        if ($optimizations_needed) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p>' . 
                esc_html__('Se recomienda ejecutar optimizaciones para mejorar el rendimiento del sistema G-POS.', 'wp-pos') . 
                ' <a href="' . admin_url('admin.php?page=wp-pos-performance') . '">' . 
                esc_html__('Ir al Centro de Rendimiento', 'wp-pos') . '</a>' . 
                '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Actualizar estado de optimizaciones
     */
    private static function update_optimization_status() {
        // Verificar u00faltimas optimizaciones
        $last_optimization = get_option('wp_pos_last_database_optimization', 0);
        $week_ago = time() - (7 * 24 * 60 * 60);
        
        // Establecer flag si hace mu00e1s de una semana desde la u00faltima optimizaciu00f3n
        update_option('wp_pos_optimizations_needed', ($last_optimization < $week_ago));
    }
    
    /**
     * Registrar historial de optimizaciu00f3n
     * 
     * @param array $entry Datos de la entrada
     */
    private static function log_optimization($entry) {
        // Obtener historial actual
        $history = get_option('wp_pos_optimization_history', []);
        
        // Au00f1adir nueva entrada al inicio
        array_unshift($history, $entry);
        
        // Limitar a 50 entradas
        if (count($history) > 50) {
            $history = array_slice($history, 0, 50);
        }
        
        // Actualizar opciu00f3n
        update_option('wp_pos_optimization_history', $history);
    }
    
    /**
     * Formatear tamau00f1o en bytes a formato legible
     * 
     * @param int $bytes Tamau00f1o en bytes
     * @return string Tamau00f1o formateado
     */
    private static function format_size($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

/**
 * Inicializar el cargador de rendimiento
 * 
 * @since 1.0.0
 */
function wp_pos_init_performance_loader() {
    // Verificar si el módulo está activo
    if (!defined('WP_POS_PERFORMANCE_MODULE_ACTIVE') || !WP_POS_PERFORMANCE_MODULE_ACTIVE) {
        return null;
    }
    
    // Verificar si la clase del cargador existe
    if (!class_exists('WP_POS_Performance_Loader')) {
        error_log('WP-POS: La clase WP_POS_Performance_Loader no está disponible');
        return null;
    }
    
    try {
        // Obtener instancia del cargador
        $loader = WP_POS_Performance_Loader::get_instance();
        
        // Inicializar el cargador cuando el módulo esté listo
        if (did_action('wp_pos_module_ready_performance')) {
            $loader->init();
        } else {
            add_action('wp_pos_module_ready_performance', [$loader, 'init']);
        }
        
        // Permitir que otros módulos accedan al cargador
        do_action('wp_pos_performance_loader_ready', $loader);
        
        return $loader;
    } catch (Exception $e) {
        error_log('WP-POS: Error al inicializar el cargador de rendimiento: ' . $e->getMessage());
        return null;
    }
}

// Inicializar el cargador después de que los plugins estén cargados
add_action('plugins_loaded', 'wp_pos_init_performance_loader', 20);
