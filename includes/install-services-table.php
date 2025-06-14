<?php
/**
 * Instalador de la tabla de servicios para WP-POS
 *
 * Crea la tabla pos_services sin gestión de stock.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Crear la tabla de servicios si no existe
 */
function wp_pos_create_services_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'pos_services';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$exists) {
        $sql = "CREATE TABLE {$table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            purchase_price decimal(10,2) DEFAULT '0.00',
            sale_price decimal(10,2) NOT NULL DEFAULT '0.00',
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY name (name)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

register_activation_hook(WP_POS_PLUGIN_FILE, 'wp_pos_create_services_table');
add_action('admin_init', 'wp_pos_create_services_table');
