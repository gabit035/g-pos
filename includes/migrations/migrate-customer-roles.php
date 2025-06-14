<?php
/**
 * Migrar roles de cliente para WP-POS
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migrar clientes existentes al nuevo rol 'pos_customer'
 */
function wp_pos_migrate_customer_roles() {
    // Obtener todos los usuarios con rol 'customer' de WordPress/WooCommerce
    $customer_args = array(
        'role' => 'customer',
        'fields' => array('ID'),
        'number' => -1,
    );
    
    $customers = get_users($customer_args);
    
    // Actualizar cada cliente al nuevo rol
    foreach ($customers as $customer) {
        $user = new WP_User($customer->ID);
        
        // Remover el rol antiguo y asignar el nuevo
        $user->remove_role('customer');
        $user->add_role('pos_customer');
    }
    
    // Guardar la migraciu00f3n como completada
    update_option('wp_pos_customer_roles_migrated', 'yes');
}

/**
 * Verificar si es necesario ejecutar la migraciu00f3n
 */
function wp_pos_check_customer_roles_migration() {
    // Solo ejecutar si no se ha realizado antes
    if (get_option('wp_pos_customer_roles_migrated') !== 'yes') {
        // Verificar si existen clientes que migrar
        $customer_count = count(get_users(array('role' => 'customer', 'fields' => array('ID'), 'number' => 1)));
        
        if ($customer_count > 0) {
            // Ejecutar la migraciu00f3n
            wp_pos_migrate_customer_roles();
        } else {
            // No hay clientes para migrar, marcar como completado
            update_option('wp_pos_customer_roles_migrated', 'yes');
        }
    }
}

// Ejecutar la verificaciu00f3n durante la inicializaciu00f3n del plugin
add_action('admin_init', 'wp_pos_check_customer_roles_migration');

/**
 * Filtrar las etiquetas de rol para mostrar nombres mu00e1s amigables
 */
function wp_pos_format_user_role($role, $user_id = 0) {
    if ($role === 'pos_customer') {
        return __('Cliente', 'wp-pos');
    } elseif ($role === 'pos_seller') {
        return __('Vendedor', 'wp-pos');
    } elseif ($role === 'pos_manager') {
        return __('Gerente', 'wp-pos');
    }
    
    return $role;
}

// Filtrar las etiquetas de rol cuando se muestran
add_filter('wp_pos_format_user_role', 'wp_pos_format_user_role', 10, 2);
