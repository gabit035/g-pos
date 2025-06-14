<?php
/**
 * Crea un enlace directo al Centro de Rendimiento en el menú principal de G-POS
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) exit;

/**
 * Registrar enlaces en el menú de WP-POS
 */
function wp_pos_register_performance_menu() {
    // Usar una prioridad muy alta para asegurar que se ejecute después de que
    // el menú principal se haya registrado completamente
    add_submenu_page(
        'wp-pos',
        __('Centro de Rendimiento', 'wp-pos'),
        __('Rendimiento', 'wp-pos'), // Sin icono para mantener coherencia con el resto del menú
        'manage_options',
        'wp-pos-performance',
        'wp_pos_render_performance_page'
    );
}
// Usar una prioridad alta (90) para que se ejecute después de que el menú principal esté registrado
add_action('admin_menu', 'wp_pos_register_performance_menu', 90);

/**
 * Redirige a la página de rendimiento
 */
function wp_pos_render_performance_page() {
    // Esta función ahora solo actúa como un proxy para mantener la estructura del menú
    // No necesitamos duplicar el contenido aquí ya que lo renderiza directamente la clase WP_POS_Performance_Loader
    static $rendered = false;
    
    // Verificar si ya se ha renderizado la página para evitar duplicación
    if ($rendered) {
        return;
    }
    
    // Marcar como renderizada
    $rendered = true;
    
    // Verificar si existe la clase de rendimiento
    if (class_exists('WP_POS_Performance_Loader')) {
        // Llamar al método de renderizado de la clase
        WP_POS_Performance_Loader::render_performance_page();
    } else {
        // Mensaje de error
        echo '<div class="wrap"><h1>' . esc_html__('Centro de Rendimiento G-POS', 'wp-pos') . '</h1>';
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('El módulo de rendimiento no está cargado correctamente. Por favor contacta al soporte.', 'wp-pos') .
             '</p></div></div>';
    }
}

/**
 * Añadir enlace directo en la página de plugins
 */
function wp_pos_add_performance_action_link($links) {
    $performance_link = '<a href="' . admin_url('admin.php?page=wp-pos-performance') . '">' . 
                        __('Centro de Rendimiento', 'wp-pos') . '</a>';
    
    // Insertar después del enlace de Configuración
    $position = array_search('settings', array_keys($links));
    
    if ($position !== false) {
        // Insertar después de Configuración
        $links = array_merge(
            array_slice($links, 0, $position + 1),
            ['performance' => $performance_link],
            array_slice($links, $position + 1)
        );
    } else {
        // Añadir al final si no encuentra "settings"
        $links['performance'] = $performance_link;
    }
    
    return $links;
}
add_filter('plugin_action_links_' . WP_POS_PLUGIN_BASENAME, 'wp_pos_add_performance_action_link');

/**
 * Añadir enlace en la barra de administración de WordPress
 */
function wp_pos_add_toolbar_performance_link($admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Añadir nodo principal G-POS si no existe
    if (!$admin_bar->get_node('wp-pos-menu')) {
        $admin_bar->add_node([
            'id'    => 'wp-pos-menu',
            'title' => 'G-POS',
            'href'  => admin_url('admin.php?page=wp-pos'),
        ]);
    }
    
    // Añadir enlace al Centro de Rendimiento
    $admin_bar->add_node([
        'id'     => 'wp-pos-performance',
        'parent' => 'wp-pos-menu',
        'title'  => '⚡ ' . __('Centro de Rendimiento', 'wp-pos'),
        'href'   => admin_url('admin.php?page=wp-pos-performance'),
    ]);
}
add_action('admin_bar_menu', 'wp_pos_add_toolbar_performance_link', 90);
