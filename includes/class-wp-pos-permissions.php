<?php
/**
 * Gestión centralizada de permisos para WP-POS
 *
 * @package WP-POS
 * @since 1.1.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejo centralizado de permisos en WP-POS
 * 
 * @since 1.1.0
 */
class WP_POS_Permissions {
    
    /**
     * Grupos de capacidades por área funcional
     *
     * @since 1.1.0
     * @var array
     */
    public static $capability_groups = array();
    
    /**
     * Inicializar el sistema de permisos
     *
     * @since 1.1.0
     */
    public static function init() {
        self::define_capability_groups();
        
        // Filtrar las capacidades de menú para usar el enfoque centralizado
        add_filter('wp_pos_menu_capability', array(__CLASS__, 'get_menu_capability'), 10, 2);
        
        // Página de administración de permisos
        add_action('wp_pos_admin_menu', array(__CLASS__, 'register_permissions_page'));
    }
    
    /**
     * Definir grupos de capacidades por área funcional
     *
     * @since 1.1.0
     */
    private static function define_capability_groups() {
        self::$capability_groups = array(
            // Permisos para Dashboard
            'dashboard' => array(
                'default' => 'view_pos',
                'title' => __('Dashboard', 'wp-pos'),
                'description' => __('Acceso al panel principal', 'wp-pos'),
            ),
            
            // Permisos para ventas
            'sales' => array(
                'default' => 'process_sales',
                'title' => __('Ventas', 'wp-pos'),
                'description' => __('Gestión de ventas', 'wp-pos'),
                'capabilities' => array(
                    'process_sales' => __('Procesar ventas', 'wp-pos'),
                    'view_sales' => __('Ver ventas', 'wp-pos'),
                    'create_sales' => __('Crear ventas', 'wp-pos'),
                    'edit_sales' => __('Editar ventas', 'wp-pos'),
                    'delete_sales' => __('Eliminar ventas', 'wp-pos'),
                ),
            ),
            
            // Permisos para productos
            'products' => array(
                'default' => 'view_products',
                'title' => __('Productos', 'wp-pos'),
                'description' => __('Gestión de productos', 'wp-pos'),
                'capabilities' => array(
                    'view_products' => __('Ver productos', 'wp-pos'),
                    'edit_products' => __('Editar productos', 'wp-pos'),
                    'delete_products' => __('Eliminar productos', 'wp-pos'),
                ),
            ),
            
            // Permisos para servicios
            'services' => array(
                'default' => 'view_services',
                'title' => __('Servicios', 'wp-pos'),
                'description' => __('Gestión de servicios', 'wp-pos'),
                'capabilities' => array(
                    'view_services' => __('Ver servicios', 'wp-pos'),
                    'edit_services' => __('Editar servicios', 'wp-pos'),
                    'delete_services' => __('Eliminar servicios', 'wp-pos'),
                ),
            ),
            
            // Permisos para clientes
            'customers' => array(
                'default' => 'view_customers',
                'title' => __('Clientes', 'wp-pos'),
                'description' => __('Gestión de clientes', 'wp-pos'),
                'capabilities' => array(
                    'view_customers' => __('Ver clientes', 'wp-pos'),
                    'edit_customers' => __('Editar clientes', 'wp-pos'),
                    'delete_customers' => __('Eliminar clientes', 'wp-pos'),
                ),
            ),
            
            // Permisos para cierres
            'closures' => array(
                'default' => 'view_closures',
                'title' => __('Cierres', 'wp-pos'),
                'description' => __('Gestión de cierres de caja', 'wp-pos'),
                'capabilities' => array(
                    'view_closures' => __('Ver cierres', 'wp-pos'),
                    'manage_pos_closures' => __('Gestionar cierres', 'wp-pos'),
                ),
            ),
            
            // Permisos para reportes
            'reports' => array(
                'default' => 'view_reports',
                'title' => __('Reportes', 'wp-pos'),
                'description' => __('Acceso a reportes', 'wp-pos'),
                'capabilities' => array(
                    'view_reports' => __('Ver reportes', 'wp-pos'),
                    'export_reports' => __('Exportar reportes', 'wp-pos'),
                ),
            ),
            
            // Permisos para configuraciones
            'settings' => array(
                'default' => 'manage_pos_settings',
                'title' => __('Configuración', 'wp-pos'),
                'description' => __('Gestión de configuraciones', 'wp-pos'),
                'capabilities' => array(
                    'manage_pos_settings' => __('Gestionar configuraciones', 'wp-pos'),
                ),
            ),
        );
    }
    
    /**
     * Verificar si un usuario tiene permiso para una acción específica
     *
     * @since 1.1.0
     * @param string $capability Capacidad a verificar
     * @param int $user_id ID del usuario (opcional)
     * @return bool True si tiene permiso, false en caso contrario
     */
    public static function can($capability, $user_id = null) {
        // Si no se especificó usuario, usar el actual
        if (null === $user_id) {
            $user_id = get_current_user_id();
        }
        
        // Obtener datos del usuario
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Los administradores siempre tienen acceso completo
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // Verificar la capacidad específica
        return $user->has_cap($capability);
    }
    
    /**
     * Obtener la capacidad para un menú específico
     *
     * @since 1.1.0
     * @param string $capability Capacidad sugerida
     * @param string $menu_id ID del menú
     * @return string Capacidad a verificar
     */
    public static function get_menu_capability($capability, $menu_id) {
        // Si el grupo está definido, usar su capacidad predeterminada
        if (isset(self::$capability_groups[$menu_id])) {
            return self::$capability_groups[$menu_id]['default'];
        }
        
        // Si no está definido, devolver la capacidad original o 'read' como fallback
        return $capability ?: 'read';
    }
    
    /**
     * Registrar página de administración de permisos
     *
     * @since 1.1.0
     */
    public static function register_permissions_page() {
        add_submenu_page(
            'wp-pos-settings',
            __('Gestión de Permisos', 'wp-pos'),
            __('Permisos', 'wp-pos'),
            'manage_options',
            'wp-pos-permissions',
            array(__CLASS__, 'render_permissions_page')
        );
    }
    
    /**
     * Renderizar página de administración de permisos
     *
     * @since 1.1.0
     */
    public static function render_permissions_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'wp-pos'));
        }
        
        // Procesar formulario si se envió
        if (isset($_POST['wp_pos_permissions_nonce']) && wp_verify_nonce($_POST['wp_pos_permissions_nonce'], 'wp_pos_save_permissions')) {
            self::process_permissions_form();
        }
        
        // Obtener roles
        $roles = get_editable_roles();
        
        // Incluir plantilla
        include WP_POS_PLUGIN_DIR . 'templates/admin-permissions.php';
    }
    
    /**
     * Procesar formulario de permisos
     *
     * @since 1.1.0
     */
    private static function process_permissions_form() {
        // Verificar y procesar datos del formulario
        if (!isset($_POST['wp_pos_role_permissions'])) {
            return;
        }
        
        $role_permissions = $_POST['wp_pos_role_permissions'];
        $wp_roles = wp_roles();
        
        foreach ($role_permissions as $role_id => $capabilities) {
            // Verificar que el rol existe
            if (!$wp_roles->is_role($role_id)) {
                continue;
            }
            
            $role = $wp_roles->get_role($role_id);
            
            // Actualizar capacidades
            foreach (self::get_all_capabilities() as $cap) {
                // Si la capacidad está en el array enviado, activarla
                if (isset($capabilities[$cap]) && $capabilities[$cap] === 'on') {
                    $role->add_cap($cap, true);
                } else {
                    // Si no está en el array, quitarla (excepto para capacidades básicas de WP)
                    if (substr($cap, 0, 4) === 'pos_' || substr($cap, 0, 5) === 'view_') {
                        $role->remove_cap($cap);
                    }
                }
            }
        }
        
        // Mostrar mensaje de éxito
        add_settings_error(
            'wp_pos_permissions',
            'permissions_updated',
            __('Permisos actualizados correctamente.', 'wp-pos'),
            'updated'
        );
    }
    
    /**
     * Obtener todas las capacidades definidas en el sistema
     *
     * @since 1.1.0
     * @return array Lista de capacidades
     */
    public static function get_all_capabilities() {
        $capabilities = array();
        
        foreach (self::$capability_groups as $group) {
            // Agregar capacidad predeterminada del grupo
            $capabilities[] = $group['default'];
            
            // Agregar capacidades específicas si existen
            if (isset($group['capabilities'])) {
                foreach ($group['capabilities'] as $cap => $label) {
                    $capabilities[] = $cap;
                }
            }
        }
        
        return array_unique($capabilities);
    }
}

// Inicializar el sistema de permisos
WP_POS_Permissions::init();
