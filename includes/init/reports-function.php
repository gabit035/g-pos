<?php
/**
 * Funciones para la pu00e1gina de reportes
 * 
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Callback para la pu00e1gina de informes
 * 
 * @since 1.0.0
 */
function wp_pos_reports_page() {
    // Cargar plantilla de informes
    if (defined('WP_POS_TEMPLATES_DIR') && file_exists(WP_POS_TEMPLATES_DIR . 'admin-reports.php')) {
        include_once WP_POS_TEMPLATES_DIR . 'admin-reports.php';
    } else {
        // Alternativa si la constante o archivo no existe
        echo '<div class="wrap"><h1>' . esc_html__('Informes', 'wp-pos') . '</h1>';
        echo '<p>' . esc_html__('Mu00f3dulo de informes no disponible.', 'wp-pos') . '</p></div>';
    }
}
