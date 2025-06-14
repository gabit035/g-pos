<?php
/**
 * Controlador de Productos para WP-POS
 *
 * Maneja la lógica principal para la gestión de productos
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del controlador de productos
 *
 * @since 1.0.0
 */
class WP_POS_Products_Controller {

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Products_Controller
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Products_Controller Instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Crear tablas de productos si no existen
        $this->init_database_tables();
        
        // Inicializar controlador
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Crear tablas necesarias para el funcionamiento de productos
     *
     * @since 1.0.0
     */
    private function init_database_tables() {
        global $wpdb;
        
        // Verificar si estamos en una pantalla de administración
        if (!is_admin()) {
            return;
        }
        
        // Solo crear tablas si no existen
        $table_name = $wpdb->prefix . 'pos_products';
        $meta_table = $wpdb->prefix . 'pos_product_meta';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                sku VARCHAR(100) DEFAULT '',
                description TEXT,
                regular_price DECIMAL(10,2) NOT NULL DEFAULT 0,
                sale_price DECIMAL(10,2) DEFAULT 0,
                manage_stock TINYINT(1) DEFAULT 0,
                stock_quantity INT DEFAULT 0,
                stock_status VARCHAR(20) DEFAULT 'instock',
                date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") != $meta_table) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $meta_table (
                meta_id BIGINT(20) NOT NULL AUTO_INCREMENT,
                product_id BIGINT(20) NOT NULL,
                meta_key VARCHAR(255) DEFAULT NULL,
                meta_value LONGTEXT,
                PRIMARY KEY (meta_id),
                KEY product_id (product_id),
                KEY meta_key (meta_key(191))
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Inicializar controlador
     *
     * @since 1.0.0
     */
    public function init() {
        // Añadir acciones o inicialización adicional si es necesario
    }

    /**
     * Obtener productos con filtros opcionales
     *
     * @since 1.0.0
     * @param array $args Argumentos de búsqueda
     * @return array Productos encontrados
     */
    public function get_products($args = array()) {
        error_log('Iniciando get_products() con args: ' . print_r($args, true));
        
        $defaults = array(
            'post_type'      => 'product',
            'posts_per_page' => 20,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            's'              => '',
            'offset'         => 0,
        );

        $args = wp_parse_args($args, $defaults);
        
        // Si WooCommerce está activo, usamos su sistema de productos
        if (class_exists('WooCommerce')) {
            error_log('WooCommerce detectado, usando sistema WC para obtener productos');
            $query = new WP_Query($args);
            $products = array();
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product_id = get_the_ID();
                    $wc_product = wc_get_product($product_id);
                    
                    if ($wc_product) {
                        $products[] = array(
                            'id'          => $product_id,
                            'name'        => $wc_product->get_name(),
                            'sku'         => $wc_product->get_sku(),
                            'price'       => $wc_product->get_price(),
                            'regular_price' => $wc_product->get_regular_price(),
                            'sale_price'  => $wc_product->get_sale_price(),
                            'stock'       => $wc_product->get_stock_quantity(),
                            'type'        => $wc_product->get_type(),
                            'permalink'   => get_permalink($product_id),
                            'thumbnail'   => get_the_post_thumbnail_url($product_id, 'thumbnail'),
                        );
                    }
                }
                wp_reset_postdata();
            }
            
            return apply_filters('wp_pos_get_products', $products, $args);
        } else {
            // Si WooCommerce no está activo, usar nuestro sistema de productos
            error_log('WooCommerce no detectado, usando sistema propio para obtener productos');
            global $wpdb;
            $table_name = $wpdb->prefix . 'pos_products';
            
            // Verificar si la tabla existe
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                error_log('Error: tabla de productos no encontrada');
                return array();
            }
            
            // DEPURACIÓN: Mostrar todos los registros en la tabla
            $all_records = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
            error_log('Registros en tabla productos: ' . print_r($all_records, true));
            
            $limit = isset($args['posts_per_page']) ? absint($args['posts_per_page']) : 20;
            $offset = isset($args['offset']) ? absint($args['offset']) : 0;
            $order = (isset($args['order']) && strtoupper($args['order']) === 'DESC') ? 'DESC' : 'ASC';
            $orderby = isset($args['orderby']) ? sanitize_sql_orderby($args['orderby']) : 'name';
            $search = isset($args['s']) ? sanitize_text_field($args['s']) : '';
            
            // Mapear los campos de ordenación de WP a nuestros campos
            if ($orderby === 'title') {
                $orderby = 'name';
            } elseif ($orderby === 'price') {
                $orderby = 'regular_price';
            }
            
            // Construir consulta SQL
            $sql = "SELECT * FROM $table_name";
            $params = array();
            
            // Añadir condiciones de búsqueda si es necesario
            if (!empty($search)) {
                $sql .= " WHERE name LIKE %s OR sku LIKE %s OR description LIKE %s";
                $search_param = '%' . $wpdb->esc_like($search) . '%';
                $params[] = $search_param;
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            // Añadir ordenación y límites
            $sql .= " ORDER BY $orderby $order";
            
            // Solo aplicar límites si no es -1 (todos los registros)
            if ($limit > 0) {
                $sql .= " LIMIT %d OFFSET %d";
                $params[] = $limit;
                $params[] = $offset;
            }
            
            // DEPURACIÓN: Mostrar la consulta SQL
            error_log('SQL Query: ' . $sql . ' con parámetros: ' . print_r($params, true));
            
            // Preparar consulta con parámetros
            if (!empty($params)) {
                $sql = $wpdb->prepare($sql, $params);
            }
            
            // DEPURACIÓN: Mostrar la consulta final
            error_log('SQL Query Final: ' . $sql);
            
            // Ejecutar consulta
            $results = $wpdb->get_results($sql, ARRAY_A);
            
            if (!$results) {
                error_log('No se encontraron productos. Error SQL: ' . $wpdb->last_error);
                return array();
            }
            
            error_log('Encontrados ' . count($results) . ' productos');
            
            $products = array();
            $meta_table = $wpdb->prefix . 'pos_product_meta';
            $meta_exists = $wpdb->get_var("SHOW TABLES LIKE '$meta_table'") == $meta_table;
            
            foreach ($results as $product) {
                // Obtener imagen si existe
                $thumbnail_id = 0;
                $thumbnail_url = '';
                
                if ($meta_exists) {
                    $thumbnail_id = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT meta_value FROM $meta_table WHERE product_id = %d AND meta_key = 'thumbnail_id'",
                            $product['id']
                        )
                    );
                    
                    if ($thumbnail_id) {
                        $thumbnail_url = wp_get_attachment_url($thumbnail_id);
                    }
                }
                
                $products[] = array(
                    'id'          => $product['id'],
                    'name'        => $product['name'],
                    'sku'         => $product['sku'],
                    'price'       => $product['sale_price'] > 0 ? $product['sale_price'] : $product['regular_price'],
                    'regular_price' => $product['regular_price'],
                    'sale_price'  => $product['sale_price'],
                    'stock'       => $product['stock_quantity'],
                    'type'        => 'simple',
                    'permalink'   => '',
                    'thumbnail'   => $thumbnail_url,
                );
            }
            
            return apply_filters('wp_pos_get_products', $products, $args);
        }
    }

    /**
     * Buscar productos por término
     *
     * @since 1.0.0
     * @param string $search_term Término de búsqueda
     * @param array $args Argumentos de búsqueda adicionales
     * @return array Productos encontrados
     */
    public function search_products($search_term, $args = array()) {
        $search_args = array(
            's' => sanitize_text_field($search_term),
        );
        
        $args = wp_parse_args($args, $search_args);
        
        return $this->get_products($args);
    }

    /**
     * Obtener un producto por su ID
     *
     * @since 1.0.0
     * @param int $product_id ID del producto
     * @return array|false Datos del producto o false si no existe
     */
    public function get_product($product_id) {
        $product_id = absint($product_id);
        
        if (!$product_id) {
            return false;
        }
        
        // Si WooCommerce está activo, usamos su sistema de productos
        if (class_exists('WooCommerce')) {
            $wc_product = wc_get_product($product_id);
            
            if ($wc_product) {
                return array(
                    'id'          => $product_id,
                    'name'        => $wc_product->get_name(),
                    'sku'         => $wc_product->get_sku(),
                    'price'       => $wc_product->get_price(),
                    'regular_price' => $wc_product->get_regular_price(),
                    'sale_price'  => $wc_product->get_sale_price(),
                    'description' => $wc_product->get_description(),
                    'stock'       => $wc_product->get_stock_quantity(),
                    'manage_stock' => $wc_product->get_manage_stock() ? 'yes' : 'no',
                    'stock_status' => $wc_product->get_stock_status(),
                    'thumbnail_id' => $wc_product->get_image_id(),
                    'thumbnail_url' => wp_get_attachment_url($wc_product->get_image_id()),
                    'type'        => $wc_product->get_type(),
                    'permalink'   => get_permalink($product_id),
                    'categories'  => wp_get_object_terms($product_id, 'product_cat', array('fields' => 'ids')),
                );
            }
        } else {
            // Si no, usar nuestro sistema de productos personalizado
            global $wpdb;
            $table_name = $wpdb->prefix . 'pos_products';
            
            // Verificar si la tabla existe
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                return false;
            }
            
            $product = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $product_id),
                ARRAY_A
            );
            
            if (!$product) {
                return false;
            }
            
            // Obtener imagen si existe
            $thumbnail_id = 0;
            $thumbnail_url = '';
            $meta_table = $wpdb->prefix . 'pos_product_meta';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") == $meta_table) {
                $thumbnail_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value FROM $meta_table WHERE product_id = %d AND meta_key = 'thumbnail_id'",
                        $product_id
                    )
                );
                
                if ($thumbnail_id) {
                    $thumbnail_url = wp_get_attachment_url($thumbnail_id);
                }
            }
            
            // Formatear datos del producto
            return array(
                'id'          => $product['id'],
                'name'        => $product['name'],
                'sku'         => $product['sku'],
                'price'       => $product['sale_price'] > 0 ? $product['sale_price'] : $product['regular_price'],
                'regular_price' => $product['regular_price'],
                'sale_price'  => $product['sale_price'],
                'description' => $product['description'],
                'stock'       => $product['stock_quantity'],
                'manage_stock' => $product['manage_stock'] ? 'yes' : 'no',
                'stock_status' => $product['stock_status'],
                'thumbnail_id' => $thumbnail_id,
                'thumbnail_url' => $thumbnail_url,
                'type'        => 'simple',
                'permalink'   => '',
                'categories'  => array(),
            );
        }
        
        return false;
    }

    /**
     * Actualizar stock de un producto
     *
     * @since 1.0.0
     * @param int $product_id ID del producto
    
    $product_id = absint($product_id);
    $quantity = intval($quantity);
     * @param array $product_data Datos del producto a guardar
     * @return int|bool ID del producto guardado o false en caso de error
     */
    public function save_product($product_data) {
        // Verificar permisos para editar productos
        if (!function_exists('wp_pos_can_edit_products')) {
            require_once(WP_POS_PLUGIN_DIR . 'includes/helpers/permissions-helper.php');
        }
        
        // Si es una edición (existe ID), verificar permisos
        if (isset($product_data['id']) && intval($product_data['id']) > 0) {
            if (!wp_pos_can_edit_products()) {
                return false;
            }
        }

        // Logs para debug
        error_log('Iniciando save_product() con datos: ' . print_r($product_data, true));
        
        // Verificar si los datos están completos
        if (empty($product_data['name'])) {
            error_log('Error: datos incompletos (falta nombre)');
            return false;
        }
        
        // SOLUCIÓN DIRECTA: Insertar en la tabla
        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_products';
        
        // Preparar datos
        $data = array(
            'name' => sanitize_text_field($product_data['name']),
            'sku' => isset($product_data['sku']) ? sanitize_text_field($product_data['sku']) : '',
            'description' => isset($product_data['description']) ? wp_kses_post($product_data['description']) : '',
            'regular_price' => isset($product_data['regular_price']) ? floatval($product_data['regular_price']) : 0,
            'sale_price' => isset($product_data['sale_price']) ? floatval($product_data['sale_price']) : 0,
            'manage_stock' => isset($product_data['manage_stock']) ? 1 : 0,
            'stock_quantity' => isset($product_data['stock_quantity']) ? absint($product_data['stock_quantity']) : 0,
            'stock_status' => isset($product_data['stock_status']) ? sanitize_text_field($product_data['stock_status']) : 'instock',
        );
        
        error_log('Datos a guardar: ' . print_r($data, true));
        
        // Actualizar o insertar
        $product_id = isset($product_data['id']) ? absint($product_data['id']) : 0;
        
        if ($product_id > 0) {
            // Actualizar registro existente
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $product_id)
            );
            error_log('Actualizar producto #' . $product_id . ' - Resultado: ' . print_r($result, true));
        } else {
            // Insertar nuevo registro
            $result = $wpdb->insert(
                $table_name,
                $data
            );
            if ($result) {
                $product_id = $wpdb->insert_id;
                error_log('Producto insertado exitosamente. ID: ' . $product_id);
            } else {
                error_log('Error al insertar producto: ' . $wpdb->last_error);
                error_log('Query: ' . $wpdb->last_query);
                return false;
            }
        }
        
        // Guardar metadatos (imagen si está presente)
        if (isset($product_data['thumbnail_id']) && absint($product_data['thumbnail_id']) > 0) {
            $meta_table = $wpdb->prefix . 'pos_product_meta';
            $thumbnail_id = absint($product_data['thumbnail_id']);
            
            // Verificar si ya existe este metadato
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_id FROM $meta_table WHERE product_id = %d AND meta_key = 'thumbnail_id'",
                    $product_id
                )
            );
            
            if ($exists) {
                // Actualizar metadato existente
                $wpdb->update(
                    $meta_table,
                    array('meta_value' => $thumbnail_id),
                    array(
                        'product_id' => $product_id,
                        'meta_key' => 'thumbnail_id'
                    )
                );
            } else {
                // Crear nuevo metadato
                $wpdb->insert(
                    $meta_table,
                    array(
                        'product_id' => $product_id,
                        'meta_key' => 'thumbnail_id',
                        'meta_value' => $thumbnail_id
                    )
                );
            }
        }
        
        return $product_id;
    }
    
    /**
     * Eliminar un producto por su ID
     *
     * @since 1.0.0
     * @param int $product_id ID del producto a eliminar
     * @return bool Éxito de la operación
     */
    public function delete_product($product_id) {
        // Verificar permisos para eliminar productos
        if (!function_exists('wp_pos_can_delete_products')) {
            require_once(WP_POS_PLUGIN_DIR . 'includes/helpers/permissions-helper.php');
        }
        
        if (!wp_pos_can_delete_products()) {
            error_log('Error al eliminar producto: El usuario no tiene permisos');
            return false;
        }
        
        // Validar ID del producto
        $product_id = absint($product_id);
        
        if (!$product_id) {
            error_log('Error al eliminar producto: ID de producto inválido');
            return false;
        }
        
        global $wpdb;
        $products_table = $wpdb->prefix . 'pos_products';
        $meta_table = $wpdb->prefix . 'pos_product_meta';
        
        // Verificar que el producto existe
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $products_table WHERE id = %d", $product_id)
        );
        
        if (!$exists) {
            error_log('Error al eliminar producto: El producto #' . $product_id . ' no existe');
            return false;
        }
        
        // Primero eliminar metadatos si existen
        $meta_exists = $wpdb->get_var("SHOW TABLES LIKE '$meta_table'") == $meta_table;
        
        if ($meta_exists) {
            error_log('Eliminando metadatos del producto #' . $product_id);
            $wpdb->delete($meta_table, array('product_id' => $product_id));
        }
        
        // Eliminar el producto
        error_log('Eliminando producto #' . $product_id);
        $result = $wpdb->delete($products_table, array('id' => $product_id));
        
        if ($result === false) {
            error_log('Error SQL al eliminar producto: ' . $wpdb->last_error);
            error_log('Query: ' . $wpdb->last_query);
            return false;
        }
        
        error_log('Producto #' . $product_id . ' eliminado exitosamente');
        return true;
    }
}

// Inicializar controlador
WP_POS_Products_Controller::get_instance();
