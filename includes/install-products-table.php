<?php
/**
 * Instalador de la tabla de productos para WP-POS
 * 
 * Este archivo garantiza que la tabla de productos se cree correctamente
 * con soporte para stock al activar el plugin.
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crear la tabla de productos si no existe
 */
function wp_pos_create_products_table() {
    global $wpdb;
    
    // Charset de la base de datos
    $charset_collate = $wpdb->get_charset_collate();
    
    // Nombres de tablas
    $products_table = $wpdb->prefix . 'pos_products';
    
    // Verificar si la tabla ya existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$products_table}'");
    
    if (!$table_exists) {
        error_log("Creando tabla de productos {$products_table}");
        
        // SQL para crear tabla de productos
        $products_sql = "CREATE TABLE {$products_table} (
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($products_sql);
        
        error_log("Resultado de crear tabla de productos: " . print_r($result, true));
        
        // Verificar si se creó correctamente
        $table_created = $wpdb->get_var("SHOW TABLES LIKE '{$products_table}'");
        if ($table_created) {
            error_log("Tabla de productos {$products_table} creada correctamente");
            
            // Crear producto de ejemplo si no hay productos
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$products_table}");
            if ($count == 0) {
                $wpdb->insert(
                    $products_table,
                    array(
                        'name' => 'Producto de Ejemplo',
                        'sku' => 'DEMO001',
                        'description' => 'Este es un producto de ejemplo creado automáticamente.',
                        'purchase_price' => 10.00,
                        'regular_price' => 20.00,
                        'sale_price' => 15.00,
                        'manage_stock' => 1,
                        'stock_quantity' => 10,
                        'stock_status' => 'instock',
                        'date_created' => current_time('mysql'),
                        'date_modified' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%s', '%s', '%s')
                );
                
                error_log("Producto de ejemplo creado en {$products_table}");
            }
        } else {
            error_log("ERROR: No se pudo crear la tabla de productos {$products_table}");
        }
    } else {
        // La tabla de productos ya existe (omitido log para evitar saturación de debug)
        
        // Verificar si existe la columna stock_quantity
        $columns = $wpdb->get_results("DESCRIBE {$products_table}");
        $has_stock_column = false;
        foreach ($columns as $column) {
            if ($column->Field === 'stock_quantity') {
                $has_stock_column = true;
                break;
            }
        }
        
        // Si no existe la columna, agregarla
        if (!$has_stock_column) {
            error_log("Agregando columna stock_quantity a la tabla {$products_table}");
            $wpdb->query("ALTER TABLE {$products_table} ADD COLUMN stock_quantity int DEFAULT 0 AFTER manage_stock");
        }
    }
}

// Ejecutar creación de tabla al activar el plugin
register_activation_hook(WP_POS_PLUGIN_FILE, 'wp_pos_create_products_table');

// También ejecutar en init para sitios existentes
add_action('admin_init', 'wp_pos_create_products_table');
