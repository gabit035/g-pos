<?php
/**
 * Gestiu00f3n de roles para WP-POS
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar los roles y capacidades en WP-POS
 *
 * @since 1.0.0
 */
class WP_POS_Roles {
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Registrar roles al activar el plugin
        add_action('init', array($this, 'register_roles'));
    }
    
    /**
     * Registrar los roles necesarios para el plugin
     *
     * @since 1.0.0
     * @return void
     */
    public function register_roles() {
        global $wp_roles;
        
        if (!class_exists('WP_Roles')) {
            return;
        }
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        // Capacidades por defecto para clientes
        $customer_capabilities = array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => false,
        );
        
        // Crear rol de cliente si no existe
        if (!$wp_roles->is_role('pos_customer')) {
            add_role(
                'pos_customer',
                __('Cliente POS', 'wp-pos'),
                $customer_capabilities
            );
        }
        
        // Capacidades para vendedores
        $seller_capabilities = array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
            
            // Capacidades básicas de acceso
            'view_pos' => true,
            'access_pos' => true,
            
            // Capacidades de ventas
            'manage_pos_sales' => true,
            'view_pos_reports' => true,
            'create_pos_sales' => true,
            
            // Capacidades para acceder a las páginas
            'view_customers' => true,
            'view_products' => true,
            'view_services' => true,
            'view_closures' => true,
            'process_sales' => true,
        );
        
        // Crear rol de vendedor si no existe
        if (!$wp_roles->is_role('pos_seller')) {
            add_role(
                'pos_seller',
                __('Vendedor POS', 'wp-pos'),
                $seller_capabilities
            );
        } else {
            // Actualizar capacidades del rol de vendedor existente
            $seller_role = $wp_roles->get_role('pos_seller');
            foreach ($seller_capabilities as $cap => $grant) {
                $seller_role->add_cap($cap, $grant);
            }
        }
        
        // Capacidades para cajeros (similar a vendedores con algunas adicionales)
        $cashier_capabilities = array_merge($seller_capabilities, array(
            'manage_pos_closures' => true,
            'view_closures' => true,
        ));
        
        // Crear rol de cajero si no existe
        if (!$wp_roles->is_role('pos_cashier')) {
            add_role(
                'pos_cashier',
                __('Cajero POS', 'wp-pos'),
                $cashier_capabilities
            );
        } else {
            // Actualizar capacidades del rol de cajero existente
            $cashier_role = $wp_roles->get_role('pos_cashier');
            foreach ($cashier_capabilities as $cap => $grant) {
                $cashier_role->add_cap($cap, $grant);
            }
        }
        
        // Capacidades para gerentes
        $manager_capabilities = array_merge($seller_capabilities, array(
            'delete_posts' => true,
            'publish_posts' => true,
            'edit_others_posts' => true,
            'delete_others_posts' => true,
            'edit_published_posts' => true,
            'manage_pos_products' => true,
            'manage_pos_settings' => true,
            'edit_pos_sales' => true,
            'delete_pos_sales' => true,
        ));
        
        // Crear rol de gerente si no existe
        if (!$wp_roles->is_role('pos_manager')) {
            add_role(
                'pos_manager',
                __('Gerente POS', 'wp-pos'),
                $manager_capabilities
            );
        }
        
        // Actualizar capacidades de administrador
        $admin_role = $wp_roles->get_role('administrator');
        if ($admin_role) {
            // Agregar todas las capacidades para asegurar que los administradores tengan acceso completo
            // Primero, obtener todas las capacidades definidas para vendedores y cajeros
            $all_capabilities = array_merge(
                $seller_capabilities,
                $cashier_capabilities,
                $manager_capabilities,
                array(
                    // Capacidades básicas de acceso
                    'view_pos' => true,
                    'access_pos' => true,
                    
                    // Capacidades de gestión
                    'manage_pos_sales' => true,
                    'manage_pos_products' => true,
                    'manage_pos_settings' => true,
                    'view_pos_reports' => true,
                    'create_pos_sales' => true,
                    'edit_pos_sales' => true,
                    'delete_pos_sales' => true,
                    
                    // Capacidades específicas para acceso a páginas
                    'view_customers' => true,
                    'view_products' => true,
                    'view_services' => true,
                    'view_closures' => true,
                    'process_sales' => true,
                    'manage_pos_closures' => true,
                    'view_reports' => true,
                    'create_sales' => true,
                    'view_sales' => true
                )
            );
            
            // Eliminar duplicados
            $admin_capabilities = array_unique($all_capabilities);
            
            foreach ($admin_capabilities as $cap => $grant) {
                $admin_role->add_cap($cap, $grant);
            }
        }
    }
    
    /**
     * Comprobar si un usuario tiene capacidades para realizar una acciu00f3n especu00edfica
     *
     * @since 1.0.0
     * @param string $capability Capacidad a comprobar
     * @param int $user_id ID del usuario, por defecto el usuario actual
     * @return bool True si tiene la capacidad, false en caso contrario
     */
    public static function has_capability($capability, $user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return $user->has_cap($capability);
    }
}

// Inicializar la clase de roles
new WP_POS_Roles();
