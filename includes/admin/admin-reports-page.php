<?php
/**
 * Pu00e1gina de Reportes Simplificada
 *
 * Implementaciu00f3n directa de la pu00e1gina de reportes con estilo visual consistente
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Funciu00f3n para mostrar la pu00e1gina de reportes
 */
function wp_pos_simple_reports_page() {
    // Aseguramos que solo usuarios autorizados puedan ver esta pu00e1gina
    if (!current_user_can('edit_posts') && !current_user_can('manage_options')) {
        wp_die(__('No tenu00e9s permisos para acceder a esta pu00e1gina.', 'wp-pos'));
    }
    
    // Cargamos estilos necesarios
    wp_enqueue_style('dashicons');
    
    // Contenedor principal
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Informes y Estadu00edsticas', 'wp-pos') . '</h1>';
    
    // Panel superior con el estilo visual que le gusta al usuario
    echo '<div style="background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); margin-bottom: 20px;">';
    echo '<h3 style="margin-top: 0;">' . esc_html__('Resumen de Actividad', 'wp-pos') . '</h3>';
    echo '<p>' . esc_html__('Genera informes detallados de ventas, productos y clientes para analizar el rendimiento de tu negocio.', 'wp-pos') . '</p>';
    echo '</div>';
    
    // Contenido principal - Tarjetas
    echo '<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">';
    
    // Tarjeta 1: Ventas
    echo '<div style="flex: 1; min-width: 250px; background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<h3 style="margin-top: 0; color: #333;">' . esc_html__('Ventas', 'wp-pos') . '</h3>';
    echo '<p>' . esc_html__('Visualiza informes de ventas por periodo', 'wp-pos') . '</p>';
    echo '<a href="#" class="button" style="display: inline-flex; align-items: center; background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; border: none; margin-top: 10px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
    echo '<span class="dashicons dashicons-chart-bar" style="margin-right: 5px;"></span>';
    echo esc_html__('Ver informe', 'wp-pos');
    echo ' <span class="dashicons dashicons-arrow-right-alt" style="margin-left: 5px;"></span>';
    echo '</a>';
    echo '</div>';
    
    // Tarjeta 2: Productos
    echo '<div style="flex: 1; min-width: 250px; background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<h3 style="margin-top: 0; color: #333;">' . esc_html__('Productos', 'wp-pos') . '</h3>';
    echo '<p>' . esc_html__('Analiza el rendimiento de tus productos', 'wp-pos') . '</p>';
    echo '<a href="#" class="button" style="display: inline-flex; align-items: center; background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; border: none; margin-top: 10px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
    echo '<span class="dashicons dashicons-products" style="margin-right: 5px;"></span>';
    echo esc_html__('Ver informe', 'wp-pos');
    echo ' <span class="dashicons dashicons-arrow-right-alt" style="margin-left: 5px;"></span>';
    echo '</a>';
    echo '</div>';
    
    // Tarjeta 3: Clientes
    echo '<div style="flex: 1; min-width: 250px; background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<h3 style="margin-top: 0; color: #333;">' . esc_html__('Clientes', 'wp-pos') . '</h3>';
    echo '<p>' . esc_html__('Revisa el historial de compras por cliente', 'wp-pos') . '</p>';
    echo '<a href="#" class="button" style="display: inline-flex; align-items: center; background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; text-decoration: none; padding: 8px 12px; border-radius: 4px; border: none; margin-top: 10px; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
    echo '<span class="dashicons dashicons-admin-users" style="margin-right: 5px;"></span>';
    echo esc_html__('Ver informe', 'wp-pos');
    echo ' <span class="dashicons dashicons-arrow-right-alt" style="margin-left: 5px;"></span>';
    echo '</a>';
    echo '</div>';
    
    echo '</div>'; // Fin de tarjetas
    
    // Sección de filtros
    echo '<div style="background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px; margin-bottom: 20px;">';
    echo '<h3 style="margin-top: 0; color: #333;">' . esc_html__('Filtros', 'wp-pos') . '</h3>';
    
    echo '<div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">';
    
    // Selector de período
    echo '<div>';
    echo '<label for="wp-pos-date-range" style="display: block; margin-bottom: 5px; font-weight: 500;">' . esc_html__('Período:', 'wp-pos') . '</label>';
    echo '<select id="wp-pos-date-range" style="min-width: 150px; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">';
    echo '<option value="today">' . esc_html__('Hoy', 'wp-pos') . '</option>';
    echo '<option value="yesterday">' . esc_html__('Ayer', 'wp-pos') . '</option>';
    echo '<option value="week" selected>' . esc_html__('Esta semana', 'wp-pos') . '</option>';
    echo '<option value="month">' . esc_html__('Este mes', 'wp-pos') . '</option>';
    echo '<option value="year">' . esc_html__('Este año', 'wp-pos') . '</option>';
    echo '<option value="custom">' . esc_html__('Personalizado', 'wp-pos') . '</option>';
    echo '</select>';
    echo '</div>';
    
    // Botón de actualizar
    echo '<div style="margin-top: 19px;">';
    echo '<button type="button" class="button" style="display: inline-flex; align-items: center; background: linear-gradient(135deg, #3a6186, #89253e); color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; border: none; font-weight: 500; transition: all 0.2s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
    echo '<span class="dashicons dashicons-update" style="margin-right: 5px;"></span>';
    echo esc_html__('Actualizar', 'wp-pos');
    echo '</button>';
    echo '</div>';
    
    echo '</div>'; // Fin de filtros-contenido
    echo '</div>'; // Fin de filtros
    
    // Tabla de ejemplo
    echo '<div style="background: #fff; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); padding: 20px;">';
    echo '<h3 style="margin-top: 0; color: #333;">' . esc_html__('Últimas Ventas', 'wp-pos') . '</h3>';
    
    echo '<table class="wp-list-table widefat fixed striped" style="border-collapse: collapse; width: 100%; border-radius: 4px; overflow: hidden;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="padding: 12px 15px;">' . esc_html__('ID', 'wp-pos') . '</th>';
    echo '<th style="padding: 12px 15px;">' . esc_html__('Fecha', 'wp-pos') . '</th>';
    echo '<th style="padding: 12px 15px;">' . esc_html__('Cliente', 'wp-pos') . '</th>';
    echo '<th style="padding: 12px 15px;">' . esc_html__('Total', 'wp-pos') . '</th>';
    echo '<th style="padding: 12px 15px;">' . esc_html__('Estado', 'wp-pos') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    // Datos de ejemplo
    $example_data = array(
        array('id' => '1001', 'date' => '2025-04-17', 'customer' => 'Juan Pérez', 'total' => '$125.50', 'status' => 'Completada'),
        array('id' => '1002', 'date' => '2025-04-16', 'customer' => 'María García', 'total' => '$78.25', 'status' => 'Completada'),
        array('id' => '1003', 'date' => '2025-04-15', 'customer' => 'Roberto Sánchez', 'total' => '$245.00', 'status' => 'Completada'),
        array('id' => '1004', 'date' => '2025-04-15', 'customer' => 'Ana López', 'total' => '$56.75', 'status' => 'Completada'),
        array('id' => '1005', 'date' => '2025-04-14', 'customer' => 'Carlos Gómez', 'total' => '$182.30', 'status' => 'Completada'),
    );
    
    foreach ($example_data as $row) {
        echo '<tr>';
        echo '<td style="padding: 12px 15px;">' . esc_html($row['id']) . '</td>';
        echo '<td style="padding: 12px 15px;">' . esc_html($row['date']) . '</td>';
        echo '<td style="padding: 12px 15px;">' . esc_html($row['customer']) . '</td>';
        echo '<td style="padding: 12px 15px;">' . esc_html($row['total']) . '</td>';
        echo '<td style="padding: 12px 15px;">';
        echo '<span style="display: inline-block; padding: 4px 8px; border-radius: 4px; background-color: #e6f9e6; color: #1e7e1e; font-size: 12px;">';
        echo esc_html($row['status']);
        echo '</span>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '</div>'; // Fin de tabla
    
    echo '</div>'; // Fin del wrap
    
    // Script para efectos de hover en los botones
    echo '<script>\n';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '  var buttons = document.querySelectorAll(".button");';
    echo '  buttons.forEach(function(button) {';
    echo '    button.addEventListener("mouseover", function() {';
    echo '      this.style.transform = "translateY(-2px)";';
    echo '      this.style.boxShadow = "0 4px 15px rgba(0,0,0,0.2)";';
    echo '      this.querySelector(".dashicons-arrow-right-alt") && (this.querySelector(".dashicons-arrow-right-alt").style.transform = "translateX(3px)");';
    echo '    });';
    echo '    button.addEventListener("mouseout", function() {';
    echo '      this.style.transform = "translateY(0)";';
    echo '      this.style.boxShadow = "0 2px 5px rgba(0,0,0,0.1)";';
    echo '      this.querySelector(".dashicons-arrow-right-alt") && (this.querySelector(".dashicons-arrow-right-alt").style.transform = "translateX(0)");';
    echo '    });';
    echo '  });';
    echo '});\n';
    echo '</script>';
}
