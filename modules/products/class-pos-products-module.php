<?php
/**
 * Módulo de Productos para WP-POS
 *
 * Gestiona todas las funcionalidades relacionadas con productos,
 * incluyendo búsqueda, listado y gestión del inventario.
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
 * Clase principal del módulo de productos
 *
 * @since 1.0.0
 */
class WP_POS_Products_Module {

    /**
     * ID único del módulo
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $module_id = 'products';

    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Products_Module
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Products_Module Instancia
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
    public function __construct() {
        $this->setup_actions();
        $this->load_dependencies();
        
        // Crear tablas de productos si no existen
        add_action('admin_init', array($this, 'create_product_tables'));
        
        // Ejecutar actualizaciones de la base de datos
        add_action('admin_init', array($this, 'run_db_updates'));
    }

    /**
     * Configurar acciones y filtros
     *
     * @since 1.0.0
     * @access private
     */
    private function setup_actions() {
        // Registro del módulo
        add_action('wp_pos_register_modules', array($this, 'register_module'));
        
        // Rutas REST
        add_action('wp_pos_register_rest_routes', array($this, 'register_rest_routes'));

        // Admin
        add_action('wp_pos_admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));

        // AJAX handlers
        add_action('wp_ajax_wp_pos_search_products', array($this, 'ajax_search_products'));
        add_action('wp_ajax_wp_pos_get_product', array($this, 'ajax_get_product'));
        add_action('wp_ajax_wp_pos_update_product_stock', array($this, 'ajax_update_product_stock'));
        add_action('wp_ajax_wp_pos_delete_product', array($this, 'ajax_delete_product'));
        add_action('wp_ajax_nopriv_wp_pos_delete_product', array($this, 'ajax_delete_product')); // Permitir llamadas AJAX sin autenticación
        
        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'load_public_scripts'));
        add_action('wp_pos_shortcode_products', array($this, 'render_products_interface'));

        // Procesamiento de productos en ventas
        add_action('wp_pos_after_sale_processed', array($this, 'update_stock_after_sale'), 10, 2);
        
        // Filtrar precio
        add_filter('wp_pos_product_price', array($this, 'filter_product_price'), 10, 3);
        add_filter('wp_pos_admin_menu_items', array($this, 'filter_admin_menu'));
    }

    /**
     * Cargar dependencias del módulo
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies() {
        // Cargar clase Registry
        require_once WP_POS_PLUGIN_DIR . 'includes/class/class-pos-registry.php';
        
        // Cargar controladores y modelos
        require_once dirname(__FILE__) . '/controllers/class-pos-products-controller.php';
        
        // Cargar clase base para REST API
        require_once WP_POS_PLUGIN_DIR . 'includes/api/class-pos-rest-controller.php';
        
        // Cargar API REST
        require_once dirname(__FILE__) . '/api/class-pos-products-rest-controller.php';
        
        // Cargar funciones auxiliares
        require_once dirname(__FILE__) . '/includes/products-functions.php';
        
        // Cargar funciones de actualización directa de stock
        require_once dirname(__FILE__) . '/includes/direct-stock-update.php';
        
        // Cargar funciones de actualización de la base de datos
        require_once dirname(__FILE__) . '/includes/db-updates.php';
    }

    /**
     * Obtener URL base del módulo
     *
     * @since 1.0.0
     * @return string URL base del módulo
     */
    public function get_base_url() {
        return plugin_dir_url( __FILE__ );
    }

    /**
     * Registrar el módulo en el sistema
     *
     * @since 1.0.0
     */
    public function register_module() {
        $registry = WP_POS_Registry::get_instance();
        
        $module = array(
            'id' => $this->module_id,
            'name' => __('Productos', 'wp-pos'),
            'description' => __('Gestiona productos, inventario y precios.', 'wp-pos'),
            'version' => '1.0.0',
            'author' => 'WP-POS Team',
            'dependencies' => array('core'),
            'instance' => $this,
        );
        
        $registry->register_module($this->module_id, $module);
    }

    /**
     * Registrar rutas REST API
     *
     * @since 1.0.0
     */
    public function register_rest_routes() {
        $controller = new WP_POS_Products_REST_Controller();
        $controller->register_routes();
    }

    /**
     * Registrar menús administrativos adicionales
     *
     * @since 1.0.0
     */
    public function register_admin_menu() {
        // Añadir página principal de productos
        add_submenu_page(
            'wp-pos',
            __('Productos', 'wp-pos'),
            __('Productos', 'wp-pos'),
            apply_filters('wp_pos_menu_capability', 'view_products', 'products'),
            'wp-pos-products',
            array($this, 'render_admin_page')
        );
        
        // Añadir página de categorías
        add_submenu_page(
            'wp-pos',
            __('Categorías de Productos', 'wp-pos'),
            __('Categorías', 'wp-pos'),
            'read', // 'read' es una capacidad que todos los usuarios tienen, incluidos administradores
            'edit-tags.php?taxonomy=product_cat&post_type=product',
            null
        );
    }

    /**
     * Filtrar elementos del menú administrativo
     *
     * @since 1.0.0
     * @param array $menu_items Items actuales del menú
     * @return array Items modificados
     */
    public function filter_admin_menu($menu_items) {
        // Personalizar items del menú relacionados con productos
        if (isset($menu_items['products'])) {
            $menu_items['products']['submenu'] = array(
                'products_list' => array(
                    'title' => __('Todos los Productos', 'wp-pos'),
                    'url' => admin_url('admin.php?page=wp-pos-products'),
                ),
                'inventory' => array(
                    'title' => __('Inventario', 'wp-pos'),
                    'url' => admin_url('admin.php?page=wp-pos-inventory'),
                ),
                'categories' => array(
                    'title' => __('Categorías', 'wp-pos'),
                    'url' => admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'),
                ),
                'add_new' => array(
                    'title' => __('Añadir Nuevo', 'wp-pos'),
                    'url' => admin_url('admin.php?page=wp-pos-products&action=add'),
                ),
            );
        }
        
        return $menu_items;
    }

    /**
     * Cargar scripts solo para admin
     *
     * @since 1.0.0
     */
    public function load_admin_scripts() {
        $screen = get_current_screen();
        $module_url = $this->get_base_url();
        
        // Archivos base para todas las pantallas del módulo
        if (is_object($screen) && isset($screen->id) && is_string($screen->id) && wp_pos_safe_strpos($screen->id, 'wp-pos-products') !== false) {
            // Estilos base
            wp_enqueue_style( 'wp-pos-admin', WP_POS_PLUGIN_URL . 'assets/css/admin.css', array(), WP_POS_VERSION );
            wp_enqueue_style( 'wp-pos-products-admin', $module_url . 'assets/css/products-admin.css', array(), WP_POS_VERSION );
            
            // Scripts generales
            wp_enqueue_script( 'wp-pos-admin', WP_POS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), WP_POS_VERSION, true );
            wp_enqueue_script( 'wp-pos-products-admin', $module_url . 'assets/js/products-admin.js', array( 'jquery' ), WP_POS_VERSION, true );
            
            // Localización
            wp_localize_script( 'wp-pos-products-admin', 'wpPosProducts', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wp_pos_products_nonce' ),
                'i18n' => array(
                    'confirmDelete' => __( '¿Estás seguro de que deseas eliminar este producto?', 'wp-pos' ),
                    'errorLoading' => __( 'Error al cargar los datos', 'wp-pos' ),
                    'saved' => __( 'Guardado exitosamente', 'wp-pos' ),
                    'error' => __( 'Ha ocurrido un error', 'wp-pos' ),
                )
            ));
        }
        
        // Scripts y estilos específicos para el formulario de productos
        if ( $screen->id === 'wp-pos_page_wp-pos-products-add' || 
             ( $screen->id === 'wp-pos_page_wp-pos-products' && isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) ) {
            
            // Estilos específicos para el formulario
            wp_enqueue_style( 'wp-pos-product-form', $module_url . 'assets/css/product-form.css', array(), WP_POS_VERSION );
            
            // Scripts para el formulario
            wp_enqueue_script( 'wp-pos-product-form', $module_url . 'assets/js/product-form.js', array( 'jquery' ), WP_POS_VERSION, true );
            
            // Media Uploader
            wp_enqueue_media();
            
            // jQuery UI para elementos interactivos
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_script( 'jquery-ui-dialog' );
            
            // Script de depuración - cargado al final para asegurar que tenga acceso a todas las demás dependencias
            wp_enqueue_script( 'wp-pos-debug-form', $module_url . 'assets/js/debug-form.js', array( 'jquery', 'wp-pos-product-form' ), time(), true );
        }
    }

    /**
     * Cargar scripts para frontend
     *
     * @since 1.0.0
     */
    public function load_public_scripts() {
        $module_url = $this->get_base_url();
        
        wp_enqueue_style( 'wp-pos-products', $module_url . 'assets/css/products.css', array(), WP_POS_VERSION );
        wp_enqueue_script( 'wp-pos-products', $module_url . 'assets/js/products.js', array( 'jquery' ), WP_POS_VERSION, true );
        
        wp_localize_script( 'wp-pos-products', 'wpPosProducts', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wp_pos_products_nonce' ),
        ));
    }

    /**
     * Renderizar página de administración
     *
     * @since 1.0.0
     */
    public function render_admin_page() {
        // Asegurarse de que las tablas de productos existan
        $this->create_product_tables();
        
        // Verificar si es una acción para añadir o editar producto
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        if ($action === 'add' || $action === 'edit') {
            // SOLUCIÓN DEFINITIVA: Usar siempre nuestro formulario mejorado para añadir y editar
            include dirname(__FILE__) . '/templates/admin-product-form.php';
        } else {
            // Cargar la plantilla de listado (predeterminada)
            include dirname(__FILE__) . '/templates/admin-products.php';
        }
    }

    /**
     * Crear tablas de productos si no existen
     *
     * @since 1.0.0
     */
    public function create_product_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pos_products';
        $meta_table = $wpdb->prefix . 'pos_product_meta';
        
        // Solo crear tablas si no existen
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            error_log('Creando tabla de productos para WP-POS');
            
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
            
            // Verificar si la tabla se creó correctamente
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                error_log('Error: No se pudo crear la tabla de productos');
            } else {
                error_log('Tabla de productos creada correctamente');
            }
        }
        
        // Crear tabla de metadatos si no existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") != $meta_table) {
            error_log('Creando tabla de metadatos de productos para WP-POS');
            
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
            
            // Verificar si la tabla se creó correctamente
            if ($wpdb->get_var("SHOW TABLES LIKE '$meta_table'") != $meta_table) {
                error_log('Error: No se pudo crear la tabla de metadatos de productos');
            } else {
                error_log('Tabla de metadatos de productos creada correctamente');
            }
        }
    }

    /**
     * Registrar scripts y estilos para frontend
     *
     * @since 1.0.0
     * @param string $hook_suffix Sufijo de página actual
     */
    public function register_frontend_scripts($hook_suffix) {
        // Solo cargar en páginas que usen los shortcodes de productos
        if (is_a($GLOBALS['post'], 'WP_Post') && has_shortcode($GLOBALS['post']->post_content, 'wp_pos_products')) {
            wp_enqueue_script(
                'wp-pos-products',
                plugins_url('assets/js/products.js', dirname(__FILE__)),
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_enqueue_style(
                'wp-pos-products',
                plugins_url('assets/css/products.css', dirname(__FILE__)),
                array(),
                '1.0.0'
            );
            
            // Localización para el frontend
            wp_localize_script('wp-pos-products', 'wp_pos_products_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_pos_frontend_nonce'),
            ));
        }
    }

    /**
     * Renderizar interfaz de productos en frontend
     *
     * @since 1.0.0
     * @param array $atts Atributos del shortcode
     * @return string Contenido HTML
     */
    public function render_products_interface($atts) {
        return wp_pos_render_products_interface($atts);
    }

    /**
     * Manejador AJAX para búsqueda de productos
     *
     * @since 1.0.0
     */
    public function ajax_search_products() {
        // Usar la función definida en products-functions.php
        wp_pos_ajax_search_products();
    }

    /**
     * Manejador AJAX para obtener un producto
     *
     * @since 1.0.0
     */
    public function ajax_get_product() {
        // Usar la función definida en products-functions.php
        wp_pos_ajax_get_product();
    }

    /**
     * Manejador AJAX para actualizar stock
     *
     * @since 1.0.0
     */
    public function ajax_update_product_stock() {
        // Verificar nonce de seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp-pos-ajax-nonce')) {
            wp_send_json_error(__('Error de seguridad. Por favor, recarga la página.', 'wp-pos'));
        }
        
        // Comprobar datos necesarios
        if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
            wp_send_json_error(__('Faltan datos requeridos.', 'wp-pos'));
        }
        
        $product_id = absint($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        // Obtener controlador de productos
        $controller = WP_POS_Products_Controller::get_instance();
        
        // Actualizar stock
        $result = $controller->update_product_stock($product_id, $quantity);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Stock actualizado correctamente.', 'wp-pos'),
                'quantity' => $quantity
            ));
        } else {
            wp_send_json_error(__('Error al actualizar el stock.', 'wp-pos'));
        }
    }

    /**
     * Manejador AJAX para eliminar un producto
     *
     * @since 1.0.0
     */
    public function ajax_delete_product() {
        // Verificar nonce de seguridad
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp-pos-ajax-nonce')) {
            wp_send_json_error('Error de seguridad. Por favor, recarga la página.');
            exit;
        }
        
        // Comprobar datos necesarios
        if (!isset($_POST['product_id'])) {
            wp_send_json_error('ID de producto no especificado.');
            exit;
        }
        
        $product_id = absint($_POST['product_id']);
        
        // Eliminar el producto directamente
        global $wpdb;
        $products_table = $wpdb->prefix . 'pos_products';
        $meta_table = $wpdb->prefix . 'pos_product_meta';
        
        // Primero eliminar metadatos si existen
        $wpdb->delete($meta_table, array('product_id' => $product_id));
        
        // Eliminar el producto
        $result = $wpdb->delete($products_table, array('id' => $product_id));
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Producto eliminado correctamente.',
                'product_id' => $product_id
            ));
        } else {
            wp_send_json_error('Error al eliminar el producto: ' . $wpdb->last_error);
        }
        
        exit;
    }

    /**
     * Actualizar stock después de procesar una venta
     *
     * @since 1.0.0
     * @param int $sale_id ID de la venta
     * @param array $sale_data Datos de la venta
     */
    public function update_stock_after_sale($sale_id, $sale_data) {
        // Si no hay productos, salir
        if (empty($sale_data['products'])) {
            return;
        }
        
        $products_controller = WP_POS_Products_Controller::get_instance();
        
        // Procesar cada producto
        foreach ($sale_data['products'] as $product) {
            if (!empty($product['id']) && !empty($product['quantity'])) {
                // Restar del stock
                $products_controller->update_product_stock(
                    $product['id'],
                    $product['quantity'],
                    'subtract'
                );
            }
        }
    }

    /**
     * Filtrar precio de producto
     *
     * @since 1.0.0
     * @param float $price Precio actual
     * @param int $product_id ID del producto
     * @param array $args Argumentos adicionales
     * @return float Precio filtrado
     */
    public function filter_product_price($price, $product_id, $args = array()) {
        // Por ahora simplemente devolver el precio sin cambios
        // Se puede extender para aplicar descuentos, impuestos, etc.
        return $price;
    }
    
    /**
     * Ejecutar actualizaciones de la base de datos
     *
     * @since 1.0.0
     */
    public function run_db_updates() {
        // Añadir columna de precio de compra si no existe
        if (function_exists('wp_pos_add_purchase_price_column')) {
            wp_pos_add_purchase_price_column();
        }
    }
}

// Inicializar módulo
WP_POS_Products_Module::get_instance();
