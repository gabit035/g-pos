<?php
/**
 * Loader mejorado para el módulo de reportes WP-POS
 * SOLUCIONA: Problema de carga de clases y dependencias
 * 
 * Colocar en: modules/reports/reports-loader.php
 * 
 * @package WP-POS
 * @subpackage Reports
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para cargar todas las dependencias del módulo de reportes
 */
class WP_POS_Reports_Loader {
    
    /**
     * Directorio base del módulo
     * @var string
     */
    private static $base_dir = '';
    
    /**
     * Estado de carga
     * @var array
     */
    private static $loaded = array();
    
    /**
     * Inicializar el loader
     */
    public static function init() {
        self::$base_dir = dirname(__FILE__);
        
        // Registrar autoloader
        spl_autoload_register(array(__CLASS__, 'autoload'));
        
        // Cargar archivos principales
        self::load_core_files();
        
        // Cargar helpers
        self::load_helpers();
        
        // Verificar dependencias
        self::check_dependencies();
        
        error_log('WP-POS Reports Loader: Módulo cargado completamente');
    }
    
    /**
     * Autoloader para clases del módulo
     */
    public static function autoload($class_name) {
        // Solo manejar clases de WP_POS
        if (strpos($class_name, 'WP_POS_Reports') === false) {
            return;
        }
        
        // Mapeo de clases a archivos
        $class_map = array(
            'WP_POS_Reports_Data' => 'class-pos-reports-data.php',
            'WP_POS_Reports_Controller' => 'class-pos-reports-controller.php',
            'WP_POS_Reports_Module' => 'class-pos-reports-module.php',
            'WP_POS_Reports_Ajax_Handler' => 'class-pos-reports-ajax-handler.php',
            'WP_POS_Reports_Chart_Data' => 'class-pos-reports-chart-data.php',
            'WP_POS_Reports_Renderer' => 'class-pos-reports-renderer.php',
            'WP_POS_Reports_Filter_Processor' => 'class-pos-reports-filter-processor.php'
        );
        
        if (isset($class_map[$class_name])) {
            $file_path = self::$base_dir . '/' . $class_map[$class_name];
            
            if (file_exists($file_path)) {
                require_once $file_path;
                self::$loaded[$class_name] = true;
                error_log("WP-POS Autoloader: Cargada clase {$class_name}");
            } else {
                error_log("WP-POS Autoloader: No se encontró archivo para {$class_name}: {$file_path}");
            }
        }
    }
    
    /**
     * Cargar archivos principales
     */
    private static function load_core_files() {
        $core_files = array(
            'class-pos-reports-data.php',
            'class-pos-reports-controller.php',
            'class-pos-reports-ajax-handler.php',
            'class-pos-reports-chart-data.php',
            'class-pos-reports-renderer.php',
            'class-pos-reports-filter-processor.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = self::$base_dir . '/' . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                self::$loaded[$file] = true;
                error_log("WP-POS Loader: Cargado archivo {$file}");
            } else {
                error_log("WP-POS Loader: ADVERTENCIA - No se encontró archivo: {$file_path}");
            }
        }
    }
    
    /**
     * Cargar archivos helper
     */
    private static function load_helpers() {
        $helper_files = array(
            'includes/reports-functions.php',
            'includes/payment-methods-helpers.php'
        );
        
        foreach ($helper_files as $file) {
            $file_path = self::$base_dir . '/' . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                self::$loaded[$file] = true;
                error_log("WP-POS Loader: Cargado helper {$file}");
            } else {
                error_log("WP-POS Loader: ADVERTENCIA - No se encontró helper: {$file_path}");
            }
        }
    }
    
    /**
     * Verificar que las dependencias críticas estén disponibles
     */
    private static function check_dependencies() {
        $critical_classes = array(
            'WP_POS_Reports_Data',
            'WP_POS_Reports_Controller',
            'WP_POS_Reports_Ajax_Handler'
        );
        
        $missing = array();
        
        foreach ($critical_classes as $class) {
            if (!class_exists($class)) {
                $missing[] = $class;
            }
        }
        
        if (!empty($missing)) {
            $error_msg = 'WP-POS Reports: Clases críticas faltantes: ' . implode(', ', $missing);
            error_log($error_msg);
            
            // Intentar cargar manualmente
            foreach ($missing as $class) {
                self::autoload($class);
            }
        } else {
            error_log('WP-POS Reports: Todas las dependencias críticas están disponibles');
        }
    }
    
    /**
     * Verificar si una clase está cargada
     */
    public static function is_loaded($class_or_file) {
        if (class_exists($class_or_file)) {
            return true;
        }
        
        return isset(self::$loaded[$class_or_file]);
    }
    
    /**
     * Obtener estado de carga
     */
    public static function get_load_status() {
        return array(
            'loaded_files' => self::$loaded,
            'available_classes' => array(
                'WP_POS_Reports_Data' => class_exists('WP_POS_Reports_Data'),
                'WP_POS_Reports_Controller' => class_exists('WP_POS_Reports_Controller'),
                'WP_POS_Reports_Ajax_Handler' => class_exists('WP_POS_Reports_Ajax_Handler'),
                'WP_POS_Reports_Chart_Data' => class_exists('WP_POS_Reports_Chart_Data'),
                'WP_POS_Reports_Renderer' => class_exists('WP_POS_Reports_Renderer'),
                'WP_POS_Reports_Filter_Processor' => class_exists('WP_POS_Reports_Filter_Processor')
            ),
            'base_dir' => self::$base_dir
        );
    }
    
    /**
     * Forzar carga de una clase específica
     */
    public static function force_load_class($class_name) {
        if (class_exists($class_name)) {
            return true;
        }
        
        self::autoload($class_name);
        
        return class_exists($class_name);
    }
}

// Inicializar el loader
WP_POS_Reports_Loader::init();

// Función global de utilidad para debugging
function wp_pos_reports_debug_loader() {
    if (class_exists('WP_POS_Reports_Loader')) {
        $status = WP_POS_Reports_Loader::get_load_status();
        error_log('=== WP-POS Reports Loader Status ===');
        error_log(print_r($status, true));
        error_log('=====================================');
        return $status;
    }
    return false;
}

// Verificar clase crítica al final
if (!class_exists('WP_POS_Reports_Data')) {
    error_log('CRÍTICO: WP_POS_Reports_Data sigue sin estar disponible después del loader');
    
    // Intentar carga directa de emergencia
    $emergency_path = dirname(__FILE__) . '/class-pos-reports-data.php';
    if (file_exists($emergency_path)) {
        require_once $emergency_path;
        error_log('Carga de emergencia realizada para WP_POS_Reports_Data');
    }
}