<?php
/**
 * Inicializador del mu00f3dulo de notificaciones
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cargar mu00f3dulo de notificaciones
 */
function wp_pos_load_notifications_module() {
    // Ruta al archivo principal del mu00f3dulo
    $module_file = WP_POS_PLUGIN_DIR . 'modules/notifications/class-pos-notifications-module.php';
    
    // Verificar que el archivo existe antes de incluirlo
    if (file_exists($module_file)) {
        require_once $module_file;
    }
}

// Cargar el mu00f3dulo en la inicializaciu00f3n del plugin
add_action('wp_pos_modules_loaded', 'wp_pos_load_notifications_module');

/**
 * Asegurar que el mu00f3dulo se cargue incluso si el hook no existe
 * (por compatibilidad con versiones anteriores)
 */
if (!did_action('wp_pos_modules_loaded')) {
    wp_pos_load_notifications_module();
}
