<?php
/**
 * G-POS System Cleanup
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Clase para limpiar y optimizar el sistema G-POS
 * 
 * Esta clase implementa utilidades para mejorar el rendimiento y la seguridad del sistema,
 * siguiendo los principios de modularidad y escalabilidad.
 */
class WP_POS_System_Cleanup {
    
    /**
     * Inicializa las funcionalidades de limpieza
     */
    public static function init() {
        // Programar limpieza automática diaria
        if (!wp_next_scheduled('wp_pos_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'wp_pos_daily_cleanup');
        }
        
        // Registrar hook para la limpieza diaria
        add_action('wp_pos_daily_cleanup', [self::class, 'perform_daily_cleanup']);
        
        // Registrar hooks de limpieza
        add_action('admin_init', [self::class, 'maybe_clean_temporary_files']);
        
        // Hooks para mejorar rendimiento
        add_action('wp_ajax_wp_pos_clear_cache', [self::class, 'ajax_clear_cache']);
        
        // Hook para optimizar consultas 
        add_action('pre_get_posts', [self::class, 'optimize_admin_queries'], 10);
    }
    
    /**
     * Realiza todas las tareas de limpieza diaria
     */
    public static function perform_daily_cleanup() {
        self::clean_temporary_files();
        self::optimize_database_tables();
        self::clean_old_logs();
        
        // Registrar la última limpieza
        update_option('wp_pos_last_cleanup', time());
    }
    
    /**
     * Ejecuta limpieza de archivos temporales si es necesario
     */
    public static function maybe_clean_temporary_files() {
        // Verificar tiempo desde última limpieza
        $last_cleanup = get_option('wp_pos_temp_files_cleanup', 0);
        $day_in_seconds = 24 * 60 * 60;
        
        if ((time() - $last_cleanup) > $day_in_seconds) {
            self::clean_temporary_files();
            update_option('wp_pos_temp_files_cleanup', time());
        }
    }
    
    /**
     * Limpia archivos temporales del sistema
     */
    public static function clean_temporary_files() {
        $temp_dir = WP_POS_PLUGIN_DIR . 'temp/';
        
        // Si el directorio no existe, crearlo
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
            return;
        }
        
        // Abrir el directorio
        $handle = opendir($temp_dir);
        
        if (!$handle) {
            return;
        }
        
        // Obtener fecha límite (archivos más antiguos de 7 días)
        $time_threshold = time() - (7 * 24 * 60 * 60);
        
        // Recorrer archivos
        while (false !== ($file = readdir($handle))) {
            // Omitir . y ..
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            $file_path = $temp_dir . $file;
            
            // Verificar si es un archivo y su fecha
            if (is_file($file_path) && filemtime($file_path) < $time_threshold) {
                @unlink($file_path);
            }
        }
        
        closedir($handle);
    }
    
    /**
     * Optimiza las tablas de la base de datos
     */
    public static function optimize_database_tables() {
        global $wpdb;
        
        // Tablas específicas del plugin
        $tables = [
            $wpdb->prefix . 'wp_pos_sales',
            $wpdb->prefix . 'wp_pos_sale_items',
            $wpdb->prefix . 'wp_pos_customers',
            $wpdb->prefix . 'wp_pos_logs',
            $wpdb->prefix . 'wp_pos_closures',
        ];
        
        // Optimizar cada tabla
        foreach ($tables as $table) {
            // Verificar si la tabla existe
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if ($table_exists) {
                $wpdb->query("OPTIMIZE TABLE {$table}");
            }
        }
    }
    
    /**
     * Limpia registros de logs antiguos
     */
    public static function clean_old_logs() {
        global $wpdb;
        
        // Umbral de 30 días
        $date_threshold = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        // Tablas de logs con sus campos de fecha
        $log_tables = [
            $wpdb->prefix . 'wp_pos_logs' => 'created_at',
            $wpdb->prefix . 'wp_pos_activity' => 'date',
        ];
        
        // Limpiar cada tabla
        foreach ($log_tables as $table => $date_field) {
            // Verificar si la tabla existe
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if ($table_exists) {
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE {$date_field} < %s",
                    $date_threshold
                ));
            }
        }
    }
    
    /**
     * Manejador AJAX para limpiar caché
     */
    public static function ajax_clear_cache() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_pos_clear_cache')) {
            wp_send_json_error(['message' => 'Nonce inválido']);
            return;
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permisos insuficientes']);
            return;
        }
        
        // Limpiar transients
        self::clear_transients();
        
        // Limpiar archivos temporales
        self::clean_temporary_files();
        
        // Respuesta exitosa
        wp_send_json_success(['message' => 'Caché limpiada exitosamente']);
    }
    
    /**
     * Limpia los transients del sistema
     */
    public static function clear_transients() {
        global $wpdb;
        
        // Eliminar todos los transients relacionados con el plugin
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_wp_pos_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_wp_pos_%'");
    }
    
    /**
     * Optimiza consultas en la administración
     * 
     * @param WP_Query $query Objeto de consulta
     */
    public static function optimize_admin_queries($query) {
        // Solo aplicar en el admin
        if (!is_admin()) {
            return;
        }
        
        // Solo aplicar a consultas principales que no sean de autores
        if (!$query->is_main_query() || $query->is_author()) {
            return;
        }
        
        // Optimizar consultas en pantallas específicas
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        
        if (!$screen) {
            return;
        }
        
        // Optimizar consultas en pantallas específicas del plugin
        if (strpos($screen->id, 'wp-pos') !== false) {
            // Limitar campos seleccionados para mejorar rendimiento
            add_filter('posts_fields', [self::class, 'limit_post_fields'], 10, 2);
        }
    }
    
    /**
     * Limita los campos seleccionados en consultas
     * 
     * @param string $fields Campos de la consulta
     * @param WP_Query $query Objeto de consulta
     * @return string Campos modificados
     */
    public static function limit_post_fields($fields, $query) {
        // Quitar el filtro para no afectar otras consultas
        remove_filter('posts_fields', [self::class, 'limit_post_fields'], 10);
        
        // Si no es una consulta del plugin, devolver campos normales
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || strpos($screen->id, 'wp-pos') === false) {
            return $fields;
        }
        
        global $wpdb;
        
        // Seleccionar solo los campos necesarios
        return "{$wpdb->posts}.ID, {$wpdb->posts}.post_title, {$wpdb->posts}.post_status, {$wpdb->posts}.post_type, {$wpdb->posts}.post_date";
    }
}

// Inicializar la clase
add_action('plugins_loaded', ['WP_POS_System_Cleanup', 'init']);
