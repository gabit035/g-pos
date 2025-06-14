<?php
/**
 * Optimizador de recursos para G-POS
 * 
 * Este script minifica archivos JS y CSS para mejorar el rendimiento
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Clase para optimizar recursos del sistema
 */
class WP_POS_Resource_Optimizer {
    
    /**
     * Inicializar el optimizador
     */
    public static function init() {
        // Registrar gancho para ejecutar la optimización en admin_init
        add_action('admin_init', [self::class, 'maybe_optimize_resources']);
        
        // Agregar opción en el menú de configuraciones
        add_action('admin_menu', [self::class, 'add_optimizer_menu']);
    }
    
    /**
     * Agregar opción al menú de herramientas
     */
    public static function add_optimizer_menu() {
        add_management_page(
            __('Optimizar G-POS', 'wp-pos'),
            __('Optimizar G-POS', 'wp-pos'),
            'manage_options',
            'wp-pos-optimizer',
            [self::class, 'render_optimizer_page']
        );
    }
    
    /**
     * Renderizar página de optimización
     */
    public static function render_optimizer_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Manejar acción de optimización
        if (isset($_POST['wp_pos_run_optimization']) && check_admin_referer('wp_pos_optimize_resources')) {
            self::run_optimization();
            echo '<div class="notice notice-success"><p>Optimización completada.</p></div>';
        }
        
        // Mostrar formulario
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Optimizador de recursos de G-POS', 'wp-pos') . '</h1>';
        echo '<p>' . esc_html__('Esta herramienta optimizará los recursos JavaScript y CSS para mejorar el rendimiento.', 'wp-pos') . '</p>';
        
        echo '<form method="post">';
        wp_nonce_field('wp_pos_optimize_resources');
        submit_button(__('Ejecutar optimización', 'wp-pos'), 'primary', 'wp_pos_run_optimization');
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Verificar si se debe ejecutar la optimización
     */
    public static function maybe_optimize_resources() {
        // Solo optimizar si la versión cambió o si los archivos originales son más recientes que los optimizados
        $current_version = WP_POS_VERSION;
        $stored_version = get_option('wp_pos_optimized_version', '');
        
        if ($current_version !== $stored_version) {
            self::run_optimization();
            update_option('wp_pos_optimized_version', $current_version);
        }
    }
    
    /**
     * Ejecutar la optimización de recursos
     */
    public static function run_optimization() {
        // Crear directorios de caché si no existen
        $cache_dir = WP_POS_PLUGIN_DIR . 'assets/cache/';
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        // Optimizar archivos JS
        self::optimize_js_files();
        
        // Optimizar archivos CSS
        self::optimize_css_files();
        
        // Limpiar transients
        self::clean_transients();
        
        // Establecer marca de tiempo de la última optimización
        update_option('wp_pos_last_optimization', time());
    }
    
    /**
     * Optimizar archivos JavaScript
     */
    private static function optimize_js_files() {
        $js_files = [
            'admin-settings.js',
            'admin-sales.js',
            'register-sale-v2.js'
        ];
        
        foreach ($js_files as $file) {
            $input_file = WP_POS_PLUGIN_DIR . 'assets/js/' . $file;
            $output_file = WP_POS_PLUGIN_DIR . 'assets/cache/' . str_replace('.js', '.min.js', $file);
            
            if (file_exists($input_file)) {
                // Leer contenido
                $content = file_get_contents($input_file);
                
                // Proceso básico de minificación
                $minified = self::minify_js($content);
                
                // Guardar versión minificada
                file_put_contents($output_file, $minified);
            }
        }
    }
    
    /**
     * Optimizar archivos CSS
     */
    private static function optimize_css_files() {
        $css_files = [
            'admin-settings.css',
            'wp-pos-settings-enhanced.css',
            'register-sale-v2.css'
        ];
        
        foreach ($css_files as $file) {
            $input_file = WP_POS_PLUGIN_DIR . 'assets/css/' . $file;
            $output_file = WP_POS_PLUGIN_DIR . 'assets/cache/' . str_replace('.css', '.min.css', $file);
            
            if (file_exists($input_file)) {
                // Leer contenido
                $content = file_get_contents($input_file);
                
                // Proceso básico de minificación
                $minified = self::minify_css($content);
                
                // Guardar versión minificada
                file_put_contents($output_file, $minified);
            }
        }
    }
    
    /**
     * Minificar JavaScript (versión simple)
     */
    private static function minify_js($js) {
        // Eliminar comentarios
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        $js = preg_replace('!//.*!', '', $js);
        
        // Eliminar espacios innecesarios
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Eliminar espacios alrededor de operadores y puntuación
        $js = preg_replace('/\s*([{}:;,=\+\-\*\/])\s*/', '$1', $js);
        
        // Eliminar espacios después de paréntesis, corchetes
        $js = preg_replace('/\s*([\(\[])\s*/', '$1', $js);
        
        // Eliminar espacios antes de paréntesis, corchetes
        $js = preg_replace('/\s*([\)\]])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Minificar CSS (versión simple)
     */
    private static function minify_css($css) {
        // Eliminar comentarios
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Eliminar espacios innecesarios
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Eliminar espacios alrededor de operadores y puntuación
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        // Eliminar punto y coma final innecesario dentro de bloques
        $css = preg_replace('/;}/', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Limpieza de transients relacionados con el plugin
     */
    private static function clean_transients() {
        global $wpdb;
        
        // Eliminar transients relacionados con G-POS
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_wp_pos_%'" );
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_wp_pos_%'" );
    }
}

// Inicializar el optimizador
WP_POS_Resource_Optimizer::init();
