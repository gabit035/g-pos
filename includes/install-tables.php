<?php
/**
 * Instalador de tablas para WP-POS
 * 
 * Este archivo crea las tablas faltantes en la base de datos
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir archivo de actualización de base de datos de WordPress
if (!function_exists('dbDelta')) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
}

/**
 * Crear tablas necesarias para el sistema POS
 */
function wp_pos_create_missing_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabla de ventas
    $sales_table = $wpdb->prefix . 'pos_sales';
    
    // Verificar si la tabla de ventas ya existe
    $sales_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sales_table}'");
    
    if (!$sales_table_exists) {
        error_log("Creando tabla {$sales_table}");
        
        $sql = "CREATE TABLE {$sales_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            customer_id bigint(20) DEFAULT NULL,
            customer_name varchar(100) DEFAULT NULL,
            customer_email varchar(100) DEFAULT NULL,
            customer_phone varchar(50) DEFAULT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0,
            tax decimal(10,2) NOT NULL DEFAULT 0,
            discount decimal(10,2) NOT NULL DEFAULT 0,
            total decimal(10,2) NOT NULL DEFAULT 0,
            payment_method varchar(50) DEFAULT 'efectivo',
            status varchar(20) NOT NULL DEFAULT 'completed',
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $result = dbDelta($sql);
        error_log("Resultado de crear tabla {$sales_table}: " . print_r($result, true));
    }
    
    // Tabla de items de venta
    $sale_items_table = $wpdb->prefix . 'pos_sale_items';
    
    // Verificar si la tabla ya existe
    $sale_items_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sale_items_table}'");
    
    if (!$sale_items_table_exists) {
        error_log("Creando tabla {$sale_items_table}");
        
        $sql = "CREATE TABLE {$sale_items_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sale_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            sku varchar(100),
            quantity int(11) NOT NULL DEFAULT 1,
            price decimal(10,2) NOT NULL DEFAULT 0,
            subtotal decimal(10,2) NOT NULL DEFAULT 0,
            tax decimal(10,2) NOT NULL DEFAULT 0,
            discount decimal(10,2) NOT NULL DEFAULT 0,
            total decimal(10,2) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY sale_id (sale_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        $result = dbDelta($sql);
        error_log("Resultado de crear tabla {$sale_items_table}: " . print_r($result, true));
    }
    
    // Tabla de pagos
    $payments_table = $wpdb->prefix . 'pos_payments';
    
    // Verificar si la tabla de pagos ya existe
    $payments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$payments_table}'");
    
    if (!$payments_table_exists) {
        error_log("Creando tabla {$payments_table}");
        
        $sql = "CREATE TABLE {$payments_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sale_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL DEFAULT 0,
            payment_method varchar(50) NOT NULL DEFAULT 'efectivo',
            transaction_id varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'completed',
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY sale_id (sale_id),
            KEY payment_method (payment_method)
        ) $charset_collate;";
        
        $result = dbDelta($sql);
        error_log("Resultado de crear tabla {$payments_table}: " . print_r($result, true));
    }
    
    // Las tablas de cierres ahora son manejadas por el sistema modular
    // El mu00f3dulo de cierres crea sus propias tablas cuando se inicializa
}

// Ejecutar la creación de tablas en admin_init y al activar el plugin
add_action('admin_init', 'wp_pos_create_missing_tables');
register_activation_hook(WP_POS_PLUGIN_FILE, 'wp_pos_create_missing_tables');
