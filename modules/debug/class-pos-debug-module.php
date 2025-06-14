<?php
/**
 * Clase del módulo de depuración
 *
 * @package WP-POS
 * @subpackage Debug
 * @since 2.3.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo de depuración
 */
class WP_POS_Debug_Module {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Registrar hooks solo si el modo de depuración está activado
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('admin_footer', array($this, 'display_debug_info'));
            add_action('wp_ajax_wp_pos_get_system_info', array($this, 'get_system_info'));
        }
    }
    
    /**
     * Muestra la información de depuración en el pie de página de administración
     */
    public function display_debug_info() {
        // Solo mostrar en páginas del plugin
        if (!$this->is_pos_page()) {
            return;
        }
        
        // Log para confirmar que el módulo está funcionando
        error_log('[WP-POS] Módulo de depuración inicializado correctamente');
    }
    
    /**
     * Comprueba si la página actual pertenece al plugin
     *
     * @return bool
     */
    private function is_pos_page() {
        if (!function_exists('get_current_screen')) {
            return false;
        }
        
        $screen = get_current_screen();
        return !empty($screen) && strpos($screen->id, 'wp-pos') !== false;
    }
    
    /**
     * Obtiene información del sistema para depuración vía AJAX
     */
    public function get_system_info() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tiene permisos para realizar esta acción');
        }
        
        // Obtener información del sistema
        $system_info = array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'plugin_version' => defined('WP_POS_VERSION') ? WP_POS_VERSION : 'No definido',
            'memory_limit' => ini_get('memory_limit'),
            'server_software' => $_SERVER['SERVER_SOFTWARE']
        );
        
        wp_send_json_success($system_info);
    }
}
