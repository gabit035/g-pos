<?php
/**
 * Optimizador de base de datos para G-POS
 * 
 * Este archivo contiene funcionalidades para mantener la base de datos
 * optimizada y limpia, siguiendo los principios de modularidad y escalabilidad.
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Clase para optimizar la base de datos
 */
class WP_POS_Database_Optimizer {
    
    /**
     * Inicializar el optimizador
     */
    public static function init() {
        // Programar optimización periódica (una vez por semana)
        if (!wp_next_scheduled('wp_pos_optimize_database')) {
            wp_schedule_event(time(), 'weekly', 'wp_pos_optimize_database');
        }
        
        // Registrar gancho para la optimización
        add_action('wp_pos_optimize_database', [self::class, 'optimize_database']);
        
        // Agregar página de herramientas
        add_action('admin_menu', [self::class, 'add_tools_page']);
    }
    
    /**
     * Agregar página de herramientas
     */
    public static function add_tools_page() {
        add_submenu_page(
            'wp-pos',
            __('Mantenimiento', 'wp-pos'),
            __('Mantenimiento', 'wp-pos'),
            'manage_options',
            'wp-pos-maintenance',
            [self::class, 'render_tools_page']
        );
    }
    
    /**
     * Renderizar página de herramientas
     */
    public static function render_tools_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Manejar acciones
        if (isset($_POST['wp_pos_action']) && check_admin_referer('wp_pos_maintenance_tools')) {
            $action = sanitize_key($_POST['wp_pos_action']);
            
            switch ($action) {
                case 'optimize_db':
                    self::optimize_database();
                    echo '<div class="notice notice-success"><p>' . 
                         esc_html__('Base de datos optimizada correctamente.', 'wp-pos') . 
                         '</p></div>';
                    break;
                    
                case 'clean_logs':
                    self::clean_log_tables();
                    echo '<div class="notice notice-success"><p>' . 
                         esc_html__('Registros antiguos eliminados correctamente.', 'wp-pos') . 
                         '</p></div>';
                    break;
                    
                case 'update_indexes':
                    self::update_database_indexes();
                    echo '<div class="notice notice-success"><p>' . 
                         esc_html__('Índices de base de datos actualizados correctamente.', 'wp-pos') . 
                         '</p></div>';
                    break;
            }
        }
        
        // Mostrar interfaz
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Herramientas de mantenimiento de G-POS', 'wp-pos') . '</h1>';
        
        // Estadísticas generales
        self::display_stats();
        
        // Acciones disponibles
        echo '<div class="wp-pos-tools-container" style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 20px;">';
        
        // Herramienta 1: Optimizar tablas
        echo '<div class="wp-pos-tool-card" style="flex: 1; min-width: 300px; max-width: 400px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
        echo '<h2>' . esc_html__('Optimización de Base de Datos', 'wp-pos') . '</h2>';
        echo '<p>' . esc_html__('Esta herramienta optimizará todas las tablas relacionadas con G-POS, mejorando el rendimiento general.', 'wp-pos') . '</p>';
        
        echo '<form method="post">';
        wp_nonce_field('wp_pos_maintenance_tools');
        echo '<input type="hidden" name="wp_pos_action" value="optimize_db">';
        submit_button(__('Optimizar tablas', 'wp-pos'), 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';
        
        // Herramienta 2: Limpiar logs antiguos
        echo '<div class="wp-pos-tool-card" style="flex: 1; min-width: 300px; max-width: 400px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
        echo '<h2>' . esc_html__('Limpieza de Logs', 'wp-pos') . '</h2>';
        echo '<p>' . esc_html__('Elimina registros de logs y actividad más antiguos de 90 días para liberar espacio en la base de datos.', 'wp-pos') . '</p>';
        
        echo '<form method="post">';
        wp_nonce_field('wp_pos_maintenance_tools');
        echo '<input type="hidden" name="wp_pos_action" value="clean_logs">';
        submit_button(__('Limpiar logs antiguos', 'wp-pos'), 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';
        
        // Herramienta 3: Actualizar índices
        echo '<div class="wp-pos-tool-card" style="flex: 1; min-width: 300px; max-width: 400px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
        echo '<h2>' . esc_html__('Actualizar Índices', 'wp-pos') . '</h2>';
        echo '<p>' . esc_html__('Actualiza los índices de la base de datos para optimizar las búsquedas y mejorar el rendimiento de consultas.', 'wp-pos') . '</p>';
        
        echo '<form method="post">';
        wp_nonce_field('wp_pos_maintenance_tools');
        echo '<input type="hidden" name="wp_pos_action" value="update_indexes">';
        submit_button(__('Actualizar índices', 'wp-pos'), 'primary', 'submit', false);
        echo '</form>';
        echo '</div>';
        
        echo '</div>'; // Fin de contenedor de herramientas
        echo '</div>'; // Fin de wrap
    }
    
    /**
     * Mostrar estadísticas de la base de datos
     */
    private static function display_stats() {
        global $wpdb;
        
        // Tablas del plugin
        $tables = self::get_plugin_tables();
        
        // Tamaño total
        $total_size = 0;
        $table_stats = [];
        
        echo '<div class="wp-pos-stats-panel" style="margin-top: 20px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
        echo '<h2>' . esc_html__('Estadísticas de Base de Datos', 'wp-pos') . '</h2>';
        
        echo '<table class="widefat" style="margin-top: 10px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Tabla', 'wp-pos') . '</th>';
        echo '<th>' . esc_html__('Registros', 'wp-pos') . '</th>';
        echo '<th>' . esc_html__('Tamaño', 'wp-pos') . '</th>';
        echo '<th>' . esc_html__('Último mantenimiento', 'wp-pos') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($tables as $table) {
            // Obtener estadísticas
            $stats = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table}'")->Data_length;
            $rows = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
            $last_opt = get_option('wp_pos_last_optimization_' . $table, 'Nunca');
            
            if (!is_numeric($last_opt)) {
                $last_opt_display = $last_opt;
            } else {
                $last_opt_display = human_time_diff($last_opt, time()) . ' atrás';
            }
            
            // Convertir bytes a formato legible
            $size = self::format_size($stats);
            $total_size += $stats;
            
            echo '<tr>';
            echo '<td>' . esc_html($table) . '</td>';
            echo '<td>' . esc_html(number_format_i18n($rows)) . '</td>';
            echo '<td>' . esc_html($size) . '</td>';
            echo '<td>' . esc_html($last_opt_display) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '<tfoot><tr>';
        echo '<th>' . esc_html__('Total', 'wp-pos') . '</th>';
        echo '<th>-</th>';
        echo '<th>' . esc_html(self::format_size($total_size)) . '</th>';
        echo '<th>-</th>';
        echo '</tr></tfoot>';
        echo '</table>';
        
        echo '</div>';
    }
    
    /**
     * Obtener listado de tablas del plugin
     * 
     * @return array Lista de nombres de tablas
     */
    private static function get_plugin_tables() {
        global $wpdb;
        
        // Prefijos específicos para tablas del plugin
        $prefixes = [
            $wpdb->prefix . 'wp_pos_',
            $wpdb->prefix . 'pos_'
        ];
        
        // Obtener todas las tablas
        $all_tables = $wpdb->get_col("SHOW TABLES");
        $plugin_tables = [];
        
        // Filtrar solo las del plugin
        foreach ($all_tables as $table) {
            foreach ($prefixes as $prefix) {
                if (strpos($table, $prefix) === 0) {
                    $plugin_tables[] = $table;
                    break;
                }
            }
        }
        
        return $plugin_tables;
    }
    
    /**
     * Optimizar la base de datos
     */
    public static function optimize_database() {
        global $wpdb;
        
        // Obtener tablas del plugin
        $tables = self::get_plugin_tables();
        
        // Verificar y reparar cada tabla
        foreach ($tables as $table) {
            // Verificar
            $wpdb->query("CHECK TABLE {$table}");
            
            // Reparar si es necesario
            $wpdb->query("REPAIR TABLE {$table}");
            
            // Optimizar
            $wpdb->query("OPTIMIZE TABLE {$table}");
            
            // Registrar timestamp de optimización
            update_option('wp_pos_last_optimization_' . $table, time());
        }
        
        // Actualizaciones de campos en wp_options
        self::clean_options();
        
        // Actualizar timestamp general de optimización
        update_option('wp_pos_last_database_optimization', time());
        
        return true;
    }
    
    /**
     * Limpiar tablas de logs
     */
    public static function clean_log_tables() {
        global $wpdb;
        
        // Definir tablas de logs y sus campos de fecha
        $log_tables = [
            // Formato: [tabla, campo_fecha]
            [$wpdb->prefix . 'wp_pos_logs', 'created_at'],
            [$wpdb->prefix . 'wp_pos_activity_log', 'log_date']
        ];
        
        // Definir umbral (90 días)
        $threshold = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        // Limpiar cada tabla
        foreach ($log_tables as $table_data) {
            list($table, $date_field) = $table_data;
            
            // Verificar si la tabla existe
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if ($table_exists) {
                // Eliminar registros antiguos
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$table} WHERE {$date_field} < %s",
                    $threshold
                ));
            }
        }
        
        return true;
    }
    
    /**
     * Actualizar índices de la base de datos
     */
    public static function update_database_indexes() {
        global $wpdb;
        
        // Definir índices que deben existir
        $required_indexes = [
            // [tabla, nombre_índice, columnas]
            [$wpdb->prefix . 'wp_pos_sales', 'idx_date', 'sale_date'],
            [$wpdb->prefix . 'wp_pos_sales', 'idx_customer', 'customer_id'],
            [$wpdb->prefix . 'wp_pos_sales', 'idx_seller', 'seller_id'],
            [$wpdb->prefix . 'wp_pos_sale_items', 'idx_sale', 'sale_id'],
            [$wpdb->prefix . 'wp_pos_sale_items', 'idx_product', 'product_id']
        ];
        
        foreach ($required_indexes as $index_data) {
            list($table, $index_name, $columns) = $index_data;
            
            // Verificar si la tabla existe
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if (!$table_exists) {
                continue;
            }
            
            // Verificar si el índice ya existe
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.statistics 
                WHERE table_schema = %s AND table_name = %s AND index_name = %s",
                DB_NAME,
                $table,
                $index_name
            ));
            
            // Si no existe, crearlo
            if (!$index_exists) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX {$index_name} ({$columns})");
            }
        }
        
        return true;
    }
    
    /**
     * Limpiar opciones innecesarias
     */
    private static function clean_options() {
        global $wpdb;
        
        // Eliminar opciones temporales o de desarrollo
        $prefixes_to_clean = [
            'wp_pos_temp_',
            'wp_pos_dev_',
            'wp_pos_test_'
        ];
        
        foreach ($prefixes_to_clean as $prefix) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $prefix . '%'
                )
            );
        }
    }
    
    /**
     * Formatear tamaño en bytes a formato legible
     * 
     * @param int $bytes Tamaño en bytes
     * @return string Tamaño formateado
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

// Inicializar el optimizador
add_action('plugins_loaded', ['WP_POS_Database_Optimizer', 'init']);
