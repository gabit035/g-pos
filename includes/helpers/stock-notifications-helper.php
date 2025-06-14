<?php
/**
 * Funciones auxiliares para notificaciones de stock
 *
 * @package WP-POS
 * @subpackage Stock
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Verificar productos con bajo stock
 *
 * @since 1.0.0
 * @param int $threshold Umbral de bajo stock (opcional, por defecto 5)
 * @return array Array de productos con bajo stock
 */
function wp_pos_check_low_stock($threshold = 5) {
    global $wpdb;
    
    // Tabla de productos
    $table = $wpdb->prefix . 'pos_products';
    
    // Realizar consulta SQL directa para productos con stock bajo
    $products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name, sku, stock_quantity 
            FROM {$table} 
            WHERE manage_stock = 1 
            AND stock_quantity <= %d 
            AND stock_quantity > 0
            ORDER BY stock_quantity ASC",
            $threshold
        ),
        ARRAY_A
    );
    
    return $products;
}

/**
 * Verificar productos sin stock (agotados)
 *
 * @since 1.0.0
 * @return array Array de productos sin stock
 */
function wp_pos_check_out_of_stock() {
    global $wpdb;
    
    // Tabla de productos
    $table = $wpdb->prefix . 'pos_products';
    
    // Realizar consulta SQL directa para productos sin stock
    $products = $wpdb->get_results(
        "SELECT id, name, sku, stock_quantity 
        FROM {$table} 
        WHERE manage_stock = 1 
        AND stock_quantity = 0
        ORDER BY name ASC",
        ARRAY_A
    );
    
    return $products;
}

/**
 * Obtener el número total de notificaciones de stock
 *
 * @since 1.0.0
 * @param int $threshold Umbral de bajo stock (opcional, por defecto 5)
 * @return int Número total de notificaciones
 */
function wp_pos_get_stock_notifications_count($threshold = 5) {
    global $wpdb;
    
    // Tabla de productos
    $table = $wpdb->prefix . 'pos_products';
    
    // Contar productos con stock bajo
    $low_stock_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$table} 
            WHERE manage_stock = 1 
            AND stock_quantity <= %d 
            AND stock_quantity > 0",
            $threshold
        )
    );
    
    // Contar productos sin stock
    $out_of_stock_count = (int) $wpdb->get_var(
        "SELECT COUNT(*) 
        FROM {$table} 
        WHERE manage_stock = 1 
        AND stock_quantity = 0"
    );
    
    return $low_stock_count + $out_of_stock_count;
}

/**
 * Mostrar indicador de notificaciones en el menú del plugin
 *
 * @since 1.0.0
 */
function wp_pos_display_stock_menu_notification() {
    $count = wp_pos_get_stock_notifications_count();
    
    if ($count > 0) {
        // Agregar script CSS para el indicador
        echo '<style>
            .wp-pos-stock-notification {
                display: inline-block;
                background-color: #6c5ce7;
                color: white;
                border-radius: 50%;
                min-width: 18px;
                height: 18px;
                text-align: center;
                line-height: 18px;
                font-size: 11px;
                font-weight: bold;
                margin-left: 5px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
        </style>';
        
        // Agregar script JavaScript para insertar el indicador en el menú
        echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                // Buscar el enlace del menú de productos
                $("#adminmenu a.menu-top:contains(\'Productos WP-POS\')").append("<span class=\'wp-pos-stock-notification\'>" . $count . "</span>");
            });
        </script>';
    }
}
add_action('admin_footer', 'wp_pos_display_stock_menu_notification');

/**
 * Registrar metabox para notificaciones de stock en el dashboard de WordPress
 *
 * @since 1.0.0
 */
function wp_pos_register_stock_dashboard_widget() {
    wp_add_dashboard_widget(
        'wp_pos_stock_notifications', 
        'Notificaciones de Stock WP-POS', 
        'wp_pos_render_stock_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'wp_pos_register_stock_dashboard_widget');

/**
 * Renderizar el widget de notificaciones de stock en el dashboard
 *
 * @since 1.0.0
 */
function wp_pos_render_stock_dashboard_widget() {
    $low_stock = wp_pos_check_low_stock();
    $out_of_stock = wp_pos_check_out_of_stock();
    
    echo '<div class="wp-pos-dashboard-widget" style="padding: 10px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">';
    
    // Mostrar encabezado con gradiente
    echo '<div style="background: linear-gradient(135deg, #3a6186, #89253e); padding: 15px; margin: -10px -10px 15px; border-radius: 8px 8px 0 0; color: white;">';
    echo '<h3 style="margin: 0; padding: 0; color: white;">Estado del Inventario</h3>';
    echo '</div>';
    
    // Sin notificaciones
    if (empty($low_stock) && empty($out_of_stock)) {
        echo '<p style="text-align: center; padding: 20px;">No hay notificaciones de stock pendientes.</p>';
    } else {
        // Mostrar productos sin stock
        if (!empty($out_of_stock)) {
            echo '<div style="margin-bottom: 20px;">';
            echo '<h4 style="color: #e74c3c; font-weight: bold;"><span class="dashicons dashicons-warning"></span> Productos Agotados (' . count($out_of_stock) . ')</h4>';
            echo '<ul style="margin-left: 20px;">';
            
            $count = 0;
            foreach ($out_of_stock as $product) {
                if ($count < 5) {
                    echo '<li style="margin-bottom: 5px;">';
                    echo '<strong>' . esc_html($product['name']) . '</strong>';
                    if (!empty($product['sku'])) {
                        echo ' <small>(SKU: ' . esc_html($product['sku']) . ')</small>';
                    }
                    echo ' - <span style="color: #e74c3c; font-weight: bold;">Sin stock</span>';
                    echo '</li>';
                }
                $count++;
            }
            
            if (count($out_of_stock) > 5) {
                echo '<li><em>Y ' . (count($out_of_stock) - 5) . ' productos más...</em></li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
        
        // Mostrar productos con stock bajo
        if (!empty($low_stock)) {
            echo '<div>';
            echo '<h4 style="color: #f39c12; font-weight: bold;"><span class="dashicons dashicons-flag"></span> Productos con Stock Bajo (' . count($low_stock) . ')</h4>';
            echo '<ul style="margin-left: 20px;">';
            
            $count = 0;
            foreach ($low_stock as $product) {
                if ($count < 5) {
                    echo '<li style="margin-bottom: 5px;">';
                    echo '<strong>' . esc_html($product['name']) . '</strong>';
                    if (!empty($product['sku'])) {
                        echo ' <small>(SKU: ' . esc_html($product['sku']) . ')</small>';
                    }
                    echo ' - Quedan <span style="color: #f39c12; font-weight: bold;">' . $product['stock_quantity'] . '</span> unidades';
                    echo '</li>';
                }
                $count++;
            }
            
            if (count($low_stock) > 5) {
                echo '<li><em>Y ' . (count($low_stock) - 5) . ' productos más...</em></li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
    }
    
    // Enlace a la página de productos
    echo '<div style="margin-top: 15px; text-align: right;">';
    echo '<a href="' . admin_url('admin.php?page=wp-pos-products') . '" style="display: inline-block; background-color: #6c5ce7; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold;">';
    echo '<span class="dashicons dashicons-cart" style="margin-right: 5px;"></span> Ir a Productos';
    echo '</a>';
    echo '</div>';
    
    echo '</div>';
}

/**
 * Agregar una página específica para notificaciones de stock
 *
 * @since 1.0.0
 */
function wp_pos_register_stock_notifications_page() {
    add_submenu_page(
        'wp-pos-products',               // Página padre
        'Notificaciones de Stock',       // Título de la página
        'Notificaciones <span class="update-plugins count-' . wp_pos_get_stock_notifications_count() . '"><span class="plugin-count">' . wp_pos_get_stock_notifications_count() . '</span></span>', // Título del menú con contador
        'manage_options',                // Capacidad requerida
        'wp-pos-stock-notifications',    // Slug de la página
        'wp_pos_render_stock_notifications_page' // Función de callback
    );
}
add_action('admin_menu', 'wp_pos_register_stock_notifications_page', 20);

/**
 * Renderizar página de notificaciones de stock
 *
 * @since 1.0.0
 */
function wp_pos_render_stock_notifications_page() {
    $low_stock = wp_pos_check_low_stock();
    $out_of_stock = wp_pos_check_out_of_stock();
    
    // Comprobar si se ha cambiado el umbral
    $threshold = 5; // Valor por defecto
    if (isset($_POST['wp_pos_threshold']) && check_admin_referer('wp_pos_update_threshold')) {
        $new_threshold = intval($_POST['wp_pos_threshold']);
        if ($new_threshold > 0) {
            update_option('wp_pos_stock_threshold', $new_threshold);
            $threshold = $new_threshold;
            $low_stock = wp_pos_check_low_stock($threshold);
            echo '<div class="notice notice-success"><p>Umbral de stock bajo actualizado correctamente.</p></div>';
        }
    } else {
        $saved_threshold = get_option('wp_pos_stock_threshold');
        if ($saved_threshold) {
            $threshold = intval($saved_threshold);
            $low_stock = wp_pos_check_low_stock($threshold);
        }
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Notificaciones de Stock', 'wp-pos'); ?></h1>
        
        <div class="wp-pos-notification-container" style="margin-top: 20px;">
            <!-- Panel de configuración -->
            <div class="wp-pos-config-panel" style="background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
                <h2 style="margin-top: 0; color: #3a6186;">Configuración</h2>
                
                <form method="post">
                    <?php wp_nonce_field('wp_pos_update_threshold'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wp_pos_threshold">Umbral de Stock Bajo</label>
                            </th>
                            <td>
                                <input type="number" id="wp_pos_threshold" name="wp_pos_threshold" value="<?php echo esc_attr($threshold); ?>" min="1" class="regular-text">
                                <p class="description">Se notificará cuando el stock de un producto sea igual o menor a este valor.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Guardar Cambios" style="background: #6c5ce7; border-color: #5549c6;">
                    </p>
                </form>
            </div>
            
            <!-- Resumen de notificaciones -->
            <div class="wp-pos-summary-panel" style="background: linear-gradient(135deg, #3a6186, #89253e); color: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
                <div style="display: flex; justify-content: space-around; text-align: center;">
                    <div>
                        <h3 style="font-size: 16px; margin: 0;">Productos Agotados</h3>
                        <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo count($out_of_stock); ?></p>
                    </div>
                    <div>
                        <h3 style="font-size: 16px; margin: 0;">Productos con Stock Bajo</h3>
                        <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo count($low_stock); ?></p>
                    </div>
                    <div>
                        <h3 style="font-size: 16px; margin: 0;">Total de Alertas</h3>
                        <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo count($out_of_stock) + count($low_stock); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Listado de productos agotados -->
            <?php if (!empty($out_of_stock)): ?>
            <div class="wp-pos-notification-panel" style="background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
                <h2 style="margin-top: 0; color: #e74c3c;">
                    <span class="dashicons dashicons-warning" style="margin-right: 5px;"></span>
                    Productos Agotados
                </h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th style="width: 100px;">Stock</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($out_of_stock as $product): ?>
                        <tr>
                            <td><?php echo esc_html($product['id']); ?></td>
                            <td><?php echo esc_html($product['name']); ?></td>
                            <td><?php echo esc_html($product['sku']); ?></td>
                            <td style="color: #e74c3c; font-weight: bold;">0</td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-products&action=edit&id=' . $product['id']); ?>" class="button button-small" style="background: #6c5ce7; color: white; border-color: #5549c6;">
                                    <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> Editar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Listado de productos con stock bajo -->
            <?php if (!empty($low_stock)): ?>
            <div class="wp-pos-notification-panel" style="background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
                <h2 style="margin-top: 0; color: #f39c12;">
                    <span class="dashicons dashicons-flag" style="margin-right: 5px;"></span>
                    Productos con Stock Bajo
                </h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th style="width: 100px;">Stock</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $product): ?>
                        <tr>
                            <td><?php echo esc_html($product['id']); ?></td>
                            <td><?php echo esc_html($product['name']); ?></td>
                            <td><?php echo esc_html($product['sku']); ?></td>
                            <td style="color: #f39c12; font-weight: bold;"><?php echo esc_html($product['stock_quantity']); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-products&action=edit&id=' . $product['id']); ?>" class="button button-small" style="background: #6c5ce7; color: white; border-color: #5549c6;">
                                    <span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> Editar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Mensaje cuando no hay notificaciones -->
            <?php if (empty($out_of_stock) && empty($low_stock)): ?>
            <div class="wp-pos-notification-panel" style="background: white; padding: 40px 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.15); text-align: center;">
                <span class="dashicons dashicons-yes-alt" style="font-size: 48px; width: 48px; height: 48px; color: #2ecc71; margin-bottom: 20px;"></span>
                <h2 style="margin-top: 0; color: #2ecc71;">¡Todo en orden!</h2>
                <p style="font-size: 16px;">No hay notificaciones de stock pendientes. Todos los productos tienen niveles de stock adecuados.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
