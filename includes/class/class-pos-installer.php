<?php
/**
 * Instalador del sistema WP-POS
 *
 * Gestiona la creación de tablas, ajustes iniciales y actualizaciones.
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase encargada de la instalación y actualización del plugin
 *
 * @since 1.0.0
 */
class WP_POS_Installer {

    /**
     * Versión actual del esquema de base de datos
     *
     * @since 1.0.0
     * @access private
     * @var string
     */
    private $db_version = '1.0.0';
    
    /**
     * Instancia singleton
     *
     * @since 1.0.0
     * @access private
     * @static
     * @var WP_POS_Installer
     */
    private static $instance = null;
    
    /**
     * Obtener instancia singleton
     *
     * @since 1.0.0
     * @static
     * @return WP_POS_Installer Instancia
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
        // Registrar hooks de activación y desactivación
        register_activation_hook(WP_POS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WP_POS_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Verificar si se necesita actualización de DB
        add_action('admin_init', array($this, 'check_version'));
        
        // Registrar capacidades y roles
        add_action('wp_pos_bootstrapped', array($this, 'register_roles'));
    }
    
    /**
     * Método que ejecuta todas las tareas de activación
     *
     * @since 1.0.0
     */
    public function activate() {
        $this->create_tables();
        $this->create_default_settings();
        $this->register_roles();
        // Quitamos add_capabilities() que no existe
        $this->create_sample_data();

        // Marcar instalación completada usando la versión de la clase
        update_option('wp_pos_db_version', $this->db_version);
        update_option('wp_pos_install_time', time());
        
        // Activar notificación de bienvenida
        update_option('wp_pos_show_welcome', true);
        update_option('wp_pos_needs_flush_rewrite', 'yes');
        flush_rewrite_rules();
        
        // Ejecutar acciones de activación personalizadas
        do_action('wp_pos_activated');
    }
    
    /**
     * Desactivar plugin
     *
     * @since 1.0.0
     */
    public function deactivate() {
        // Limpiar cachés al desactivar
        wp_cache_flush();
        update_option('wp_pos_needs_flush_rewrite', 'yes');
        flush_rewrite_rules();
        
        // Ejecutar acciones de desactivación personalizadas
        do_action('wp_pos_deactivated');
    }
    
    /**
     * Crear datos de muestra para una instalación nueva
     * 
     * @since 1.0.0
     */
    private function create_sample_data() {
        global $wpdb;
        
        // Verificar si ya existen productos
        $products_table = $wpdb->prefix . 'pos_products';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$products_table}'") === $products_table;
        
        if (!$table_exists) {
            return; // Si la tabla no existe, no podemos continuar
        }
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$products_table}");
        
        // Solo crear productos de muestra si no hay ninguno
        if ($count == 0) {
            // Definimos productos de muestra directamente
            $sample_products = [
                [
                    'name' => 'Smartphone X10',
                    'sku' => 'PHONE001',
                    'barcode' => '1234567890123',
                    'price' => 299.99,
                    'cost_price' => 200.00,
                    'stock' => 50,
                    'category' => 'Electrónicos'
                ],
                [
                    'name' => 'Audífonos Inalámbricos',
                    'sku' => 'AUDIO001',
                    'barcode' => '9876543210987',
                    'price' => 59.99,
                    'cost_price' => 30.00,
                    'stock' => 100,
                    'category' => 'Accesorios'
                ],
                [
                    'name' => 'Cargador Universal',
                    'sku' => 'CHAR001',
                    'barcode' => '5678901234567',
                    'price' => 19.99,
                    'cost_price' => 8.00,
                    'stock' => 200,
                    'category' => 'Accesorios'
                ],
                [
                    'name' => 'Tablet Pro 2023',
                    'sku' => 'TAB001',
                    'barcode' => '1122334455667',
                    'price' => 399.99,
                    'cost_price' => 280.00,
                    'stock' => 30,
                    'category' => 'Electrónicos'
                ],
                [
                    'name' => 'Funda Protectora',
                    'sku' => 'CASE001',
                    'barcode' => '7788990011223',
                    'price' => 15.99,
                    'cost_price' => 5.00,
                    'stock' => 150,
                    'category' => 'Accesorios'
                ]
            ];
            
            // Insertar productos directamente en la base de datos
            foreach ($sample_products as $product) {
                $wpdb->insert(
                    $products_table,
                    array(
                        'name' => $product['name'],
                        'sku' => $product['sku'],
                        'barcode' => $product['barcode'],
                        'price' => $product['price'],
                        'cost_price' => $product['cost_price'],
                        'stock' => $product['stock'],
                        'category' => $product['category'],
                        'date_created' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s')
                );
            }
            
            // Registrar en log
            if (function_exists('wp_pos_log')) {
                wp_pos_log('Productos de muestra creados durante la instalación', 'info');
            }
        }
    }
    
    /**
     * Verificar versión y actualizar si es necesario
     *
     * @since 1.0.0
     */
    public function check_version() {
        if (get_option('wp_pos_db_version') !== $this->db_version) {
            $this->update();
        }
        
        // Verificar si se necesita actualizar enlaces permanentes
        if ('yes' === get_option('wp_pos_needs_flush_rewrite')) {
            delete_option('wp_pos_needs_flush_rewrite');
            flush_rewrite_rules();
        }
    }
    
    /**
     * Actualizar el plugin a la última versión
     *
     * @since 1.0.0
     */
    public function update() {
        $current_version = get_option('wp_pos_db_version', '0.0.0');
        
        // Flujo de actualización según versión
        if (version_compare($current_version, '0.9.0', '<')) {
            $this->update_to_0_9_0();
        }
        
        if (version_compare($current_version, '1.0.0', '<')) {
            $this->update_to_1_0_0();
        }
        
        // Actualizar versión en la base de datos
        update_option('wp_pos_db_version', $this->db_version);
        
        // Log de actualización
        wp_pos_log(
            sprintf(__('WP-POS actualizado de la versión %s a %s', 'wp-pos'), $current_version, $this->db_version),
            'info'
        );
    }
    
    /**
     * Crear tablas en la base de datos
     *
     * @since 1.0.0
     */
    public function create_tables() {
        global $wpdb;
        
        // Obtener el charset de la base de datos
        $charset_collate = $wpdb->get_charset_collate();
        
        // Definir nombres de tablas (corregir prefijos para evitar duplicación)
        $registers_table = $wpdb->prefix . 'pos_registers';
        $sales_table = $wpdb->prefix . 'pos_sales';
        $sale_items_table = $wpdb->prefix . 'pos_sale_items';
        $payments_table = $wpdb->prefix . 'pos_payments';
        $logs_table = $wpdb->prefix . 'pos_logs';
        $products_table = $wpdb->prefix . 'pos_products';
        
        // Verificar si la tabla de ventas ya existe y si tiene la clave unique sale_number
        $sales_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sales_table}'") === $sales_table;
        $has_sale_number_key = false;
        
        if ($sales_table_exists) {
            // Verificar si ya existe la clave sale_number
            $keys = $wpdb->get_results("SHOW KEYS FROM {$sales_table} WHERE Key_name = 'sale_number'");
            $has_sale_number_key = !empty($keys);
        }
        
        // SQL para crear tabla de cajas registradoras
        $registers_sql = "CREATE TABLE $registers_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            location varchar(200) DEFAULT '',
            status varchar(20) DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL,
            options longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY status (status)
        ) $charset_collate;";
        
        // SQL para crear tabla de ventas - Dividir la consulta para manejar la clave sale_number
        if ($sales_table_exists && $has_sale_number_key) {
            // Si la tabla ya existe y ya tiene la clave sale_number, no la agregamos
            $sales_sql = "CREATE TABLE $sales_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                sale_number varchar(50) NOT NULL,
                register_id bigint(20) unsigned NOT NULL,
                customer_id bigint(20) unsigned DEFAULT NULL,
                user_id bigint(20) unsigned NOT NULL,
                status varchar(20) DEFAULT 'pending',
                date_created datetime NOT NULL,
                date_completed datetime DEFAULT NULL,
                total decimal(19,4) NOT NULL DEFAULT 0,
                tax_total decimal(19,4) NOT NULL DEFAULT 0,
                discount_total decimal(19,4) NOT NULL DEFAULT 0,
                discount_type varchar(20) DEFAULT NULL,
                notes text DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY register_id (register_id),
                KEY customer_id (customer_id),
                KEY user_id (user_id),
                KEY status (status),
                KEY date_created (date_created),
                KEY date_completed (date_completed)
            ) $charset_collate;";
        } else {
            // Si la tabla no existe o no tiene la clave sale_number, la agregamos
            $sales_sql = "CREATE TABLE $sales_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                sale_number varchar(50) NOT NULL,
                register_id bigint(20) unsigned NOT NULL,
                customer_id bigint(20) unsigned DEFAULT NULL,
                user_id bigint(20) unsigned NOT NULL,
                status varchar(20) DEFAULT 'pending',
                date_created datetime NOT NULL,
                date_completed datetime DEFAULT NULL,
                total decimal(19,4) NOT NULL DEFAULT 0,
                tax_total decimal(19,4) NOT NULL DEFAULT 0,
                discount_total decimal(19,4) NOT NULL DEFAULT 0,
                discount_type varchar(20) DEFAULT NULL,
                notes text DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY sale_number (sale_number),
                KEY register_id (register_id),
                KEY customer_id (customer_id),
                KEY user_id (user_id),
                KEY status (status),
                KEY date_created (date_created),
                KEY date_completed (date_completed)
            ) $charset_collate;";
        };
        
        // SQL para crear tabla de items de venta
        $sale_items_sql = "CREATE TABLE $sale_items_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sale_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            variation_id bigint(20) unsigned DEFAULT NULL,
            name varchar(255) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(19,4) NOT NULL DEFAULT 0,
            tax decimal(19,4) NOT NULL DEFAULT 0,
            total decimal(19,4) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY sale_id (sale_id),
            KEY product_id (product_id),
            KEY variation_id (variation_id)
        ) $charset_collate;";
        
        // SQL para crear tabla de pagos
        $payments_sql = "CREATE TABLE $payments_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            sale_id bigint(20) unsigned NOT NULL,
            payment_method varchar(100) NOT NULL,
            amount decimal(19,4) NOT NULL DEFAULT 0,
            transaction_id varchar(200) DEFAULT NULL,
            date_created datetime NOT NULL,
            note text DEFAULT NULL,
            meta longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY sale_id (sale_id),
            KEY payment_method (payment_method),
            KEY date_created (date_created),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";
        
        // SQL para crear tabla de logs
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            log_time datetime NOT NULL,
            log_level varchar(20) NOT NULL DEFAULT 'info',
            log_message text NOT NULL,
            log_context longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY log_time (log_time),
            KEY log_level (log_level)
        ) $charset_collate;";
        
        // Asegurarse de que la biblioteca para ejecutar SQL está disponible
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // SQL para crear tabla de productos
        $products_sql = "CREATE TABLE $products_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            sku varchar(100) DEFAULT NULL,
            description text DEFAULT NULL,
            purchase_price decimal(10,2) DEFAULT '0.00',
            regular_price decimal(10,2) NOT NULL DEFAULT '0.00',
            sale_price decimal(10,2) DEFAULT '0.00',
            manage_stock tinyint(1) DEFAULT 0,
            stock_quantity int DEFAULT 0,
            stock_status varchar(20) DEFAULT 'instock',
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY name (name),
            KEY sku (sku),
            KEY stock_status (stock_status)
        ) $charset_collate;";
        
        // Crear tablas
        dbDelta($registers_sql);
        dbDelta($sales_sql);
        dbDelta($sale_items_sql);
        dbDelta($payments_sql);
        dbDelta($logs_sql);
        dbDelta($products_sql);
        
        // Las tablas de cierres ahora son manejadas por el nuevo sistema modular
        // y se crean cuando el mu00f3dulo se inicializa
        
        // Registrar que se crearon las tablas
        error_log('WP-POS: Tablas creadas/actualizadas');
    }
    
    /**
     * Crear ajustes predeterminados
     *
     * @since 1.0.0
     */
    public function create_default_settings() {
        // Verificar si ya existen ajustes
        $existing_options = get_option('wp_pos_options', array());
        if (!empty($existing_options)) {
            return;
        }
        
        // Determinar si WooCommerce está activo
        $woocommerce_active = function_exists('WC');
        
        // Opciones predeterminadas
        $default_options = array(
            // Información del negocio
            'business_name' => get_bloginfo('name'),
            'business_address' => '',
            'business_phone' => '',
            'business_email' => get_bloginfo('admin_email'),
            'business_logo' => '',
            
            // Configuración general
            'pos_page_id' => 0,
            'restrict_access' => 'yes',
            'enable_keyboard_shortcuts' => 'yes',
            'enable_barcode_scanner' => 'yes',
            
            // Opciones de venta
            'add_customer_to_sale' => 'optional',
            'default_tax_rate' => '0',
            'enable_discount' => 'yes',
            'default_payment_method' => 'cash',
            
            // Opciones de impresión
            'receipt_template' => 'default',
            'receipt_logo' => '',
            'receipt_store_name' => get_bloginfo('name'),
            'receipt_store_address' => '',
            'receipt_store_phone' => '',
            'receipt_footer' => __('Gracias por su compra', 'wp-pos'),
            'print_automatically' => 'no',
            
            // Opciones de moneda y formato
            'currency' => 'ARS', // Forzar moneda a Peso Argentino
            'currency_position' => 'left',
            'thousand_separator' => '.',
            'decimal_separator' => ',',
            'decimals' => 2,
            
            // Opciones de interfaz
            'products_per_page' => 20,
            'default_product_orderby' => 'title',
            'default_product_order' => 'ASC',
            'show_product_images' => 'yes',
            'show_categories_filter' => 'yes',
            
            // Opciones de stock
            'update_stock' => 'yes',
            'low_stock_threshold' => $woocommerce_active ? get_option('woocommerce_notify_low_stock_amount', 2) : 2,
            'show_out_of_stock' => 'yes',
        );
        
        // Guardar opciones
        update_option('wp_pos_options', $default_options);
    }
    
    /**
     * Registrar roles y capacidades
     *
     * @since 1.0.0
     */
    public function register_roles() {
        global $wp_roles;

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        // Crear el rol de cajero si no existe
        if (!$wp_roles->is_role('pos_cashier')) {
            add_role(
                'pos_cashier',
                'Cajero POS', // Texto directo en lugar de __() para evitar carga temprana
                array(
                    'read' => true,
                    'view_pos' => true,
                    'process_sales' => true,
                )
            );
        }

        // Crear el rol de gerente si no existe
        if (!$wp_roles->is_role('pos_manager')) {
            add_role(
                'pos_manager',
                'Gerente POS', // Texto directo en lugar de __() para evitar carga temprana
                array(
                    'read' => true,
                    'view_pos' => true,
                    'process_sales' => true,
                    'view_reports' => true,
                    'manage_pos' => true,
                )
            );
        }
    }
    
    /**
     * Actualizar a la versión 0.9.0
     *
     * @since 1.0.0
     * @access private
     */
    private function update_to_0_9_0() {
        global $wpdb;
        
        // Corregir nombre de tabla usando el prefijo consistente
        $table_name = $wpdb->prefix . 'pos_sales';
        
        // Verificar si la tabla existe antes de intentar modificarla
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        if ($table_exists) {
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'discount_type'");
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN discount_type varchar(20) DEFAULT NULL AFTER discount_total");
                wp_pos_log('Columna discount_type agregada a la tabla ' . $table_name, 'info');
            }
        } else {
            wp_pos_log('Tabla ' . $table_name . ' no encontrada durante actualización a 0.9.0', 'warning');
            // Intentar crear las tablas nuevamente
            $this->create_tables();
        }
    }
    
    /**
     * Actualizar a la versión 1.0.0
     *
     * @since 1.0.0
     * @access private
     */
    private function update_to_1_0_0() {
        global $wpdb;
        
        // Ejemplo de actualización: agregar una nueva tabla
        $charset_collate = $wpdb->get_charset_collate();
        $customer_cards_table = $wpdb->prefix . 'wp_pos_customer_cards';
        
        $sql = "CREATE TABLE IF NOT EXISTS $customer_cards_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            card_number varchar(50) NOT NULL,
            card_type varchar(50) NOT NULL,
            exp_date varchar(10) DEFAULT NULL,
            date_created datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        // Actualizar opciones con nuevos valores predeterminados
        $options = wp_pos_get_option();
        $options['enable_customer_cards'] = 'no';
        update_option('wp_pos_options', $options);
    }
}

// Inicializar instalador
WP_POS_Installer::get_instance();
