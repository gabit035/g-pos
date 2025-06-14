<?php
/**
 * Plantilla del encabezado para el panel de administración
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener título de la página
$title = isset($args['title']) ? $args['title'] : __('Punto de Venta', 'wp-pos');
$show_menu = isset($args['show_menu']) ? $args['show_menu'] : true;

// Obtener configuración global
$options = wp_pos_get_option();
?>
<div class="wrap wp-pos-admin-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($title); ?></h1>
    
    <?php if ($show_menu): ?>
    <div class="wp-pos-admin-menu-wrap">
        <?php wp_pos_template_main_menu(isset($args['active_menu']) ? $args['active_menu'] : ''); ?>
    </div>
    <?php endif; ?>
    
    <div class="wp-pos-admin-content-wrap">
