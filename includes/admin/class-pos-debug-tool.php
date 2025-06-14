<?php
/**
 * Debug Tool for WP-POS
 * 
 * @package WP-POS
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para la herramienta de depuración de WP-POS
 */
class WP_POS_Integrated_Debug_Tool {

    /**
     * Constructor de la clase
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_debug_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Añade el menú de depuración al menú de WP-POS
     */
    public function add_debug_menu() {
        add_submenu_page(
            'wp-pos', // Parent slug
            __('Herramienta de Depuración', 'wp-pos'), // Page title
            __('Depuración', 'wp-pos'), // Menu title
            'manage_options', // Capability
            'wp-pos-debug', // Menu slug
            array($this, 'render_debug_page') // Callback
        );
    }

    /**
     * Carga los estilos y scripts necesarios
     */
    public function enqueue_scripts($hook) {
        if ('wp-os_page_wp-pos-debug' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wp-pos-debug-style',
            WP_POS_PLUGIN_URL . 'assets/css/debug-tool.css',
            array(),
            WP_POS_VERSION
        );
    }

    /**
     * Renderiza la página de depuración
     */
    public function render_debug_page() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'wp-pos'));
        }

        // Incluir el archivo de depuración
        require_once WP_POS_PLUGIN_DIR . 'debug-tables-simple.php';
    }
}

// Inicializar la herramienta de depuración
function wp_pos_init_debug_tool() {
    new WP_POS_Integrated_Debug_Tool();
}
add_action('plugins_loaded', 'wp_pos_init_debug_tool');
