<?php
/**
 * Página simplificada para mostrar reportes
 * Esta implementación directa garantiza que el menú de reportes funcione correctamente
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Callback para mostrar la página de reportes
 * Esta función se registra directamente en el menú para garantizar su disponibilidad
 */
function wp_pos_direct_reports_page() {
    // Estilo visual consistente con el resto del plugin
    echo '<div class="wrap wp-pos-admin-wrapper">';
    echo '<h1>' . esc_html__('Reportes', 'wp-pos') . '</h1>';
    
    // Panel superior con el estilo visual que le gusta al usuario
    echo '<div class="wp-pos-control-panel" style="background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">';
    echo '<div class="wp-pos-control-panel-primary">';
    echo '<h3>' . esc_html__('Informes y Estadísticas', 'wp-pos') . '</h3>';
    echo '<p>' . esc_html__('Genera informes detallados de ventas, productos y clientes para analizar el rendimiento de tu negocio.', 'wp-pos') . '</p>';
    echo '</div>';
    echo '</div>';
    
    // Contenido principal
    echo '<div class="wp-pos-content-panel" style="margin-top: 20px;">';
    echo '<div class="wp-pos-notice-box info" style="background: #f8f9fa; border-left: 4px solid #6c5ce7; padding: 15px; margin-bottom: 20px;">';
    echo '<p>' . esc_html__('Estamos trabajando para mejorar la sección de reportes. Pronto tendrás disponibles más informes y estadísticas.', 'wp-pos') . '</p>';
    echo '</div>';
    
    // Contenedor de reportes
    echo '<div class="wp-pos-reports-container">';
    
    // Datos de ejemplo
    $total_ventas = 24;
    $total_ingresos = 1082.90;
    $total_productos = 2;
    $total_clientes = 2;
    
    // Mostrar tarjetas con datos
    echo '<div class="wp-pos-cards-container" style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">';
    
    // Tarjeta 1: Ventas
    echo '<div class="wp-pos-card" style="flex: 1; min-width: 200px; background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<div class="wp-pos-card-header" style="margin-bottom: 10px;">';
    echo '<h3 style="margin: 0; font-size: 16px; color: #666;">' . esc_html__('Total Ventas', 'wp-pos') . '</h3>';
    echo '</div>';
    echo '<div class="wp-pos-card-body">';
    echo '<div class="wp-pos-card-value" style="font-size: 32px; font-weight: 600; color: #3a6186;">' . esc_html($total_ventas) . '</div>';
    echo '</div>';
    echo '</div>';
    
    // Tarjeta 2: Ingresos
    echo '<div class="wp-pos-card" style="flex: 1; min-width: 200px; background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<div class="wp-pos-card-header" style="margin-bottom: 10px;">';
    echo '<h3 style="margin: 0; font-size: 16px; color: #666;">' . esc_html__('Total Ingresos', 'wp-pos') . '</h3>';
    echo '</div>';
    echo '<div class="wp-pos-card-body">';
    echo '<div class="wp-pos-card-value" style="font-size: 32px; font-weight: 600; color: #27ae60;">$' . esc_html(number_format($total_ingresos, 2)) . '</div>';
    echo '</div>';
    echo '</div>';
    
    // Tarjeta 3: Productos
    echo '<div class="wp-pos-card" style="flex: 1; min-width: 200px; background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<div class="wp-pos-card-header" style="margin-bottom: 10px;">';
    echo '<h3 style="margin: 0; font-size: 16px; color: #666;">' . esc_html__('Productos', 'wp-pos') . '</h3>';
    echo '</div>';
    echo '<div class="wp-pos-card-body">';
    echo '<div class="wp-pos-card-value" style="font-size: 32px; font-weight: 600; color: #3498db;">' . esc_html($total_productos) . '</div>';
    echo '</div>';
    echo '</div>';
    
    // Tarjeta 4: Clientes
    echo '<div class="wp-pos-card" style="flex: 1; min-width: 200px; background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<div class="wp-pos-card-header" style="margin-bottom: 10px;">';
    echo '<h3 style="margin: 0; font-size: 16px; color: #666;">' . esc_html__('Clientes', 'wp-pos') . '</h3>';
    echo '</div>';
    echo '<div class="wp-pos-card-body">';
    echo '<div class="wp-pos-card-value" style="font-size: 32px; font-weight: 600; color: #e84393;">' . esc_html($total_clientes) . '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // Fin de las tarjetas
    
    // Acciones
    echo '<div class="wp-pos-actions" style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">';
    
    // Acción 1: Ventas
    echo '<a href="#" class="wp-pos-action" style="flex: 1; display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; text-decoration: none; border: 1px solid #eee; transition: all 0.2s; min-width: 150px;">';
    echo '<span class="wp-pos-action-icon" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3a6186, #89253e); display: flex; align-items: center; justify-content: center; margin-right: 15px;"><span class="dashicons dashicons-chart-bar" style="color: #fff;"></span></span>';
    echo '<span class="wp-pos-action-text">';
    echo '<span style="display: block; font-weight: 500; color: #333; margin-bottom: 3px;">' . esc_html__('Ventas por día', 'wp-pos') . '</span>';
    echo '<span style="display: block; font-size: 12px; color: #666;">' . esc_html__('Visualiza tendencias diarias', 'wp-pos') . '</span>';
    echo '</span>';
    echo '</a>';
    
    // Acción 2: Productos
    echo '<a href="#" class="wp-pos-action" style="flex: 1; display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; text-decoration: none; border: 1px solid #eee; transition: all 0.2s; min-width: 150px;">';
    echo '<span class="wp-pos-action-icon" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #2ecc71, #27ae60); display: flex; align-items: center; justify-content: center; margin-right: 15px;"><span class="dashicons dashicons-products" style="color: #fff;"></span></span>';
    echo '<span class="wp-pos-action-text">';
    echo '<span style="display: block; font-weight: 500; color: #333; margin-bottom: 3px;">' . esc_html__('Productos populares', 'wp-pos') . '</span>';
    echo '<span style="display: block; font-size: 12px; color: #666;">' . esc_html__('Ver productos más vendidos', 'wp-pos') . '</span>';
    echo '</span>';
    echo '</a>';
    
    // Acción 3: Clientes
    echo '<a href="#" class="wp-pos-action" style="flex: 1; display: flex; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; text-decoration: none; border: 1px solid #eee; transition: all 0.2s; min-width: 150px;">';
    echo '<span class="wp-pos-action-icon" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3498db, #2980b9); display: flex; align-items: center; justify-content: center; margin-right: 15px;"><span class="dashicons dashicons-admin-users" style="color: #fff;"></span></span>';
    echo '<span class="wp-pos-action-text">';
    echo '<span style="display: block; font-weight: 500; color: #333; margin-bottom: 3px;">' . esc_html__('Clientes frecuentes', 'wp-pos') . '</span>';
    echo '<span style="display: block; font-size: 12px; color: #666;">' . esc_html__('Análisis de clientes frecuentes', 'wp-pos') . '</span>';
    echo '</span>';
    echo '</a>';
    
    echo '</div>'; // Fin de las acciones
    
    // Botón de exportar con el estilo visual que le gusta al usuario
    echo '<div class="wp-pos-view-all" style="text-align: center; margin-top: 25px; margin-bottom: 5px;">';
    echo '<a href="#" class="wp-pos-view-all-button" style="display: inline-flex; align-items: center; background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">';
    echo '<span class="wp-pos-view-all-icon" style="display: flex; align-items: center; justify-content: center; margin-right: 8px;"><span class="dashicons dashicons-media-spreadsheet" style="font-size: 18px; width: 18px; height: 18px;"></span></span>';
    echo '<span class="wp-pos-view-all-text" style="margin: 0 8px;">' . esc_html__('Exportar como CSV', 'wp-pos') . '</span>';
    echo '<span class="wp-pos-view-all-arrow" style="margin-left: auto; display: flex; align-items: center; justify-content: center;"><span class="dashicons dashicons-arrow-right-alt" style="font-size: 16px; width: 16px; height: 16px;"></span></span>';
    echo '</a>';
    echo '</div>';
    
    echo '</div>'; // Fin del contenedor de reportes
    echo '</div>'; // Fin del contenido principal
    echo '</div>'; // Fin del wrapper
}
