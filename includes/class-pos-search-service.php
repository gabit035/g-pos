<?php
/**
 * G-POS Search Service
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Clase para optimizar y centralizar todas las bu00fasquedas del sistema
 * 
 * Implementa un servicio modular para manejar bu00fasquedas en productos, servicios,
 * clientes y ventas con un enfoque en rendimiento y escalabilidad.
 */
class WP_POS_Search_Service {
    
    /**
     * Inicializa el servicio de bu00fasqueda
     */
    public static function init() {
        // Registrar AJAX handlers para todas las bu00fasquedas
        add_action('wp_ajax_wp_pos_unified_search', [self::class, 'ajax_unified_search']);
        add_action('wp_ajax_wp_pos_search_products_optimized', [self::class, 'ajax_search_products']);
        add_action('wp_ajax_wp_pos_search_services_optimized', [self::class, 'ajax_search_services']);
        add_action('wp_ajax_wp_pos_search_customers_optimized', [self::class, 'ajax_search_customers']);
        
        // Generar u00edndices de bu00fasqueda una vez por du00eda
        if (!wp_next_scheduled('wp_pos_rebuild_search_index')) {
            wp_schedule_event(time(), 'daily', 'wp_pos_rebuild_search_index');
        }
        
        // Registrar hook para reconstruir u00edndices
        add_action('wp_pos_rebuild_search_index', [self::class, 'rebuild_search_index']);
    }
    
    /**
     * Bu00fasqueda unificada a travu00e9s de AJAX
     */
    public static function ajax_unified_search() {
        // Verificar nonce
        check_ajax_referer('wp_pos_search', 'security');
        
        // Obtener datos de la bu00fasqueda
        $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
        $scope = isset($_GET['scope']) ? sanitize_text_field($_GET['scope']) : 'all';
        
        if (empty($query)) {
            wp_send_json_error(['message' => 'Query vacu00edo']);
            return;
        }
        
        // Resultados por categoru00eda
        $results = [
            'query' => $query,
            'scope' => $scope,
            'results' => []
        ];
        
        // Realizar bu00fasqueda segu00fan el alcance
        switch ($scope) {
            case 'products':
                $results['results']['products'] = self::search_products($query);
                break;
                
            case 'services':
                $results['results']['services'] = self::search_services($query);
                break;
                
            case 'customers':
                $results['results']['customers'] = self::search_customers($query);
                break;
                
            case 'sales':
                $results['results']['sales'] = self::search_sales($query);
                break;
                
            case 'all':
            default:
                // Para bu00fasqueda general, limitar resultados por categoru00eda
                $results['results']['products'] = self::search_products($query, 5);
                $results['results']['services'] = self::search_services($query, 5);
                $results['results']['customers'] = self::search_customers($query, 5);
                $results['results']['sales'] = self::search_sales($query, 5);
                break;
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Bu00fasqueda optimizada de productos vu00eda AJAX
     */
    public static function ajax_search_products() {
        // Verificar nonce
        check_ajax_referer('wp_pos_search', 'security');
        
        // Obtener tu00e9rmino de bu00fasqueda
        $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        
        if (empty($query)) {
            wp_send_json_error(['message' => 'Query vacu00edo']);
            return;
        }
        
        // Resultados
        $results = self::search_products($query, $limit);
        
        wp_send_json_success([
            'query' => $query,
            'results' => $results
        ]);
    }
    
    /**
     * Bu00fasqueda optimizada de servicios vu00eda AJAX
     */
    public static function ajax_search_services() {
        // Verificar nonce
        check_ajax_referer('wp_pos_search', 'security');
        
        // Obtener tu00e9rmino de bu00fasqueda
        $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        
        if (empty($query)) {
            wp_send_json_error(['message' => 'Query vacu00edo']);
            return;
        }
        
        // Resultados
        $results = self::search_services($query, $limit);
        
        wp_send_json_success([
            'query' => $query,
            'results' => $results
        ]);
    }
    
    /**
     * Bu00fasqueda optimizada de clientes vu00eda AJAX
     */
    public static function ajax_search_customers() {
        // Verificar nonce
        check_ajax_referer('wp_pos_search', 'security');
        
        // Obtener tu00e9rmino de bu00fasqueda
        $query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        
        if (empty($query)) {
            wp_send_json_error(['message' => 'Query vacu00edo']);
            return;
        }
        
        // Resultados
        $results = self::search_customers($query, $limit);
        
        wp_send_json_success([
            'query' => $query,
            'results' => $results
        ]);
    }
    
    /**
     * Bu00fasqueda de productos optimizada
     * 
     * @param string $query Tu00e9rmino de bu00fasqueda
     * @param int $limit Lu00edmite de resultados
     * @return array Resultados de la bu00fasqueda
     */
    public static function search_products($query, $limit = 20) {
        global $wpdb;
        
        // Preparar tu00e9rmino de bu00fasqueda para LIKE
        $like_query = '%' . $wpdb->esc_like($query) . '%';
        
        // Consulta principal con JOIN optimizado
        $sql = $wpdb->prepare(
            "SELECT DISTINCT p.ID, p.post_title, p.post_excerpt, pm1.meta_value as sku, pm2.meta_value as price, pm3.meta_value as stock 
            FROM {$wpdb->posts} p 
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_sku' 
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_price' 
            LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_stock' 
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish' 
            AND (p.post_title LIKE %s OR pm1.meta_value LIKE %s) 
            ORDER BY p.post_title ASC 
            LIMIT %d",
            $like_query,
            $like_query,
            $limit
        );
        
        // Ejecutar la consulta
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Procesar resultados para formato estu00e1ndar
        $products = [];
        
        foreach ($results as $product) {
            // Obtener imagen destacada si estu00e1 disponible
            $thumbnail_id = get_post_thumbnail_id($product['ID']);
            $image_url = $thumbnail_id ? wp_get_attachment_thumb_url($thumbnail_id) : '';
            
            // Datos formateados
            $products[] = [
                'id' => $product['ID'],
                'title' => $product['post_title'],
                'sku' => $product['sku'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'image' => $image_url,
                'excerpt' => $product['post_excerpt']
            ];
        }
        
        return $products;
    }
    
    /**
     * Bu00fasqueda de servicios optimizada
     * 
     * @param string $query Tu00e9rmino de bu00fasqueda
     * @param int $limit Lu00edmite de resultados
     * @return array Resultados de la bu00fasqueda
     */
    public static function search_services($query, $limit = 20) {
        global $wpdb;
        
        // Preparar tu00e9rmino de bu00fasqueda para LIKE
        $like_query = '%' . $wpdb->esc_like($query) . '%';
        
        // Consulta principal con JOIN optimizado
        $sql = $wpdb->prepare(
            "SELECT s.ID, s.post_title, s.post_excerpt, pm1.meta_value as price, pm2.meta_value as duration 
            FROM {$wpdb->posts} s 
            LEFT JOIN {$wpdb->postmeta} pm1 ON s.ID = pm1.post_id AND pm1.meta_key = '_price' 
            LEFT JOIN {$wpdb->postmeta} pm2 ON s.ID = pm2.post_id AND pm2.meta_key = '_duration' 
            WHERE s.post_type = 'service' 
            AND s.post_status = 'publish' 
            AND s.post_title LIKE %s 
            ORDER BY s.post_title ASC 
            LIMIT %d",
            $like_query,
            $limit
        );
        
        // Ejecutar la consulta
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Procesar resultados para formato estu00e1ndar
        $services = [];
        
        foreach ($results as $service) {
            // Obtener imagen destacada si estu00e1 disponible
            $thumbnail_id = get_post_thumbnail_id($service['ID']);
            $image_url = $thumbnail_id ? wp_get_attachment_thumb_url($thumbnail_id) : '';
            
            // Datos formateados
            $services[] = [
                'id' => $service['ID'],
                'title' => $service['post_title'],
                'price' => $service['price'],
                'duration' => $service['duration'],
                'image' => $image_url,
                'excerpt' => $service['post_excerpt']
            ];
        }
        
        return $services;
    }
    
    /**
     * Bu00fasqueda de clientes optimizada
     * 
     * @param string $query Tu00e9rmino de bu00fasqueda
     * @param int $limit Lu00edmite de resultados
     * @return array Resultados de la bu00fasqueda
     */
    public static function search_customers($query, $limit = 20) {
        global $wpdb;
        
        // Preparar tu00e9rmino de bu00fasqueda para LIKE
        $like_query = '%' . $wpdb->esc_like($query) . '%';
        
        // Tabla de clientes personalizada
        $customers_table = $wpdb->prefix . 'wp_pos_customers';
        
        // Verificar si existe la tabla personalizada
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $customers_table
        ));
        
        if ($table_exists) {
            // Consulta en tabla personalizada (mu00e1s ru00e1pida)
            $sql = $wpdb->prepare(
                "SELECT * FROM {$customers_table} 
                WHERE name LIKE %s OR email LIKE %s OR phone LIKE %s 
                ORDER BY name ASC 
                LIMIT %d",
                $like_query,
                $like_query,
                $like_query,
                $limit
            );
            
            $results = $wpdb->get_results($sql, ARRAY_A);
            
            return $results;
        } else {
            // Fallback a usuarios de WordPress
            $sql = $wpdb->prepare(
                "SELECT ID, display_name, user_email 
                FROM {$wpdb->users} 
                WHERE display_name LIKE %s OR user_email LIKE %s OR user_login LIKE %s 
                ORDER BY display_name ASC 
                LIMIT %d",
                $like_query,
                $like_query,
                $like_query,
                $limit
            );
            
            $results = $wpdb->get_results($sql, ARRAY_A);
            
            // Adaptar formato
            $customers = [];
            
            foreach ($results as $user) {
                $customers[] = [
                    'id' => $user['ID'],
                    'name' => $user['display_name'],
                    'email' => $user['user_email'],
                    'phone' => get_user_meta($user['ID'], 'phone', true)
                ];
            }
            
            return $customers;
        }
    }
    
    /**
     * Bu00fasqueda de ventas optimizada
     * 
     * @param string $query Tu00e9rmino de bu00fasqueda
     * @param int $limit Lu00edmite de resultados
     * @return array Resultados de la bu00fasqueda
     */
    public static function search_sales($query, $limit = 20) {
        global $wpdb;
        
        // Preparar tu00e9rmino de bu00fasqueda para LIKE
        $like_query = '%' . $wpdb->esc_like($query) . '%';
        
        // Tabla de ventas
        $sales_table = $wpdb->prefix . 'wp_pos_sales';
        $customers_table = $wpdb->prefix . 'wp_pos_customers';
        
        // Verificar si existe la tabla
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $sales_table
        ));
        
        if (!$table_exists) {
            return [];
        }
        
        // Consulta optimizada con JOIN basado en el tipo de informaciu00f3n de bu00fasqueda
        if (is_numeric($query)) {
            // Bu00fasqueda por ID o total
            $sql = $wpdb->prepare(
                "SELECT s.*, c.name as customer_name 
                FROM {$sales_table} s 
                LEFT JOIN {$customers_table} c ON s.customer_id = c.id 
                WHERE s.id = %d OR s.total = %f 
                ORDER BY s.sale_date DESC 
                LIMIT %d",
                intval($query),
                floatval($query),
                $limit
            );
        } else {
            // Bu00fasqueda por nombre de cliente o nu00famero de referencia
            $sql = $wpdb->prepare(
                "SELECT s.*, c.name as customer_name 
                FROM {$sales_table} s 
                LEFT JOIN {$customers_table} c ON s.customer_id = c.id 
                WHERE c.name LIKE %s OR s.reference LIKE %s 
                ORDER BY s.sale_date DESC 
                LIMIT %d",
                $like_query,
                $like_query,
                $limit
            );
        }
        
        // Ejecutar la consulta
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results;
    }
    
    /**
     * Reconstruir u00edndices de bu00fasqueda
     */
    public static function rebuild_search_index() {
        global $wpdb;
        
        // Optimizar u00edndices de tablas para bu00fasqueda
        $tables_fields = [
            // [tabla, campo]
            [$wpdb->posts, 'post_title'],
            [$wpdb->posts, 'post_name'],
            [$wpdb->postmeta, 'meta_value'],
            [$wpdb->prefix . 'wp_pos_customers', 'name'],
            [$wpdb->prefix . 'wp_pos_customers', 'email'],
            [$wpdb->prefix . 'wp_pos_sales', 'reference']
        ];
        
        foreach ($tables_fields as $table_field) {
            list($table, $field) = $table_field;
            
            // Verificar si la tabla existe
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if (!$table_exists) {
                continue;
            }
            
            // Nombre del u00edndice
            $index_name = "idx_{$field}";
            
            // Verificar si ya existe el u00edndice
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(1) FROM information_schema.statistics 
                WHERE table_schema = %s AND table_name = %s AND index_name = %s",
                DB_NAME,
                $table,
                $index_name
            ));
            
            // Crear u00edndice si no existe
            if (!$index_exists) {
                $wpdb->query("ALTER TABLE {$table} ADD INDEX {$index_name} ({$field}(191))");
            }
        }
        
        // Actualizar flag de u00faltima reconstrucciu00f3n
        update_option('wp_pos_last_search_index_rebuild', time());
    }
}

// Inicializar el servicio
add_action('plugins_loaded', ['WP_POS_Search_Service', 'init']);
