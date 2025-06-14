<?php
/**
 * Sistema de alertas de stock bajo para el dashboard
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cargar estilos CSS para las notificaciones de stock
 */
function wp_pos_stock_alerts_enqueue_styles() {
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if ($page === 'wp-pos') {
        wp_enqueue_style(
            'wp-pos-stock-alerts', 
            WP_POS_PLUGIN_URL . 'assets/css/stock-notifications.css',
            array(),
            WP_POS_VERSION
        );
    }
}
add_action('admin_enqueue_scripts', 'wp_pos_stock_alerts_enqueue_styles');

/**
 * Obtiene productos con stock bajo directamente de la base de datos
 * 
 * @return array Productos con stock bajo
 */
function wp_pos_get_low_stock_products_direct() {
    global $wpdb;
    
    // Obtener configuración del umbral
    $options = wp_pos_get_option();
    $threshold = isset($options['low_stock_threshold']) ? (int)$options['low_stock_threshold'] : 5;
    
    $products = array();
    
    // Comprobar cada tabla posible para mayor compatibilidad
    $tables_to_check = array(
        // Tabla de productos de WP-POS
        $wpdb->prefix . 'pos_products' => "
            SELECT 
                id, 
                name, 
                sku, 
                stock_quantity,
                regular_price
            FROM {table} 
            WHERE stock_quantity > 0 
            AND stock_quantity <= %d 
            ORDER BY stock_quantity ASC
            LIMIT 10
        ",
        
        // Tabla de productos de WooCommerce HPOS
        $wpdb->prefix . 'wc_products' => "
            SELECT 
                id, 
                name, 
                sku, 
                stock_quantity,
                regular_price
            FROM {table} 
            WHERE stock_quantity > 0 
            AND stock_quantity <= %d
            ORDER BY stock_quantity ASC
            LIMIT 10
        "
    );
    
    foreach ($tables_to_check as $table => $query_template) {
        // Verificar si la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $query = str_replace('{table}', $table, $query_template);
            $prepared_query = $wpdb->prepare($query, $threshold);
            $results = $wpdb->get_results($prepared_query);
            
            if (!empty($results)) {
                $products = $results;
                break;
            }
        }
    }
    
    return $products;
}

/**
 * Muestra las alertas de stock bajo en el dashboard
 */
function wp_pos_render_stock_alerts_panel() {
    // Obtener productos con stock bajo
    $products = wp_pos_get_low_stock_products_direct();
    
    // Obtener umbral configurado
    $options = wp_pos_get_option();
    $threshold = isset($options['low_stock_threshold']) ? (int)$options['low_stock_threshold'] : 5;
    
    // Iniciar panel de alertas
    ?>
    <div class="wp-pos-stock-alert">
        <div class="wp-pos-stock-alert-header">
            <h3 class="wp-pos-stock-alert-title">
                <span class="dashicons dashicons-warning"></span>
                <?php 
                if (count($products) == 1) {
                    echo 'Producto con stock bajo';
                } else {
                    echo 'Productos con stock bajo';
                }
                ?>
            </h3>
            <span>Umbral: <?php echo $threshold; ?></span>
        </div>
        
        <div class="wp-pos-stock-alert-body">
            <?php if (!empty($products)) : ?>
                <table class="wp-pos-stock-products">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'wp-pos'); ?></th>
                            <th><?php _e('Producto', 'wp-pos'); ?></th>
                            <th><?php _e('SKU', 'wp-pos'); ?></th>
                            <th><?php _e('Precio', 'wp-pos'); ?></th>
                            <th><?php _e('Stock', 'wp-pos'); ?></th>
                            <th><?php _e('Acciones', 'wp-pos'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product) : ?>
                        <tr>
                            <td><?php echo esc_html($product->id); ?></td>
                            <td><strong><?php echo esc_html($product->name); ?></strong></td>
                            <td><?php echo esc_html($product->sku); ?></td>
                            <td><?php echo wc_price($product->price); ?></td>
                            <td class="stock-count"><?php echo esc_html($product->stock_quantity); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-products&action=edit&product_id=' . $product->id); ?>" 
                                   class="button button-small">
                                   <span class="dashicons dashicons-edit"></span>
                                   <?php _e('Editar', 'wp-pos'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="wp-pos-stock-actions">
                    <a href="<?php echo admin_url('admin.php?page=wp-pos-products'); ?>" class="wp-pos-all-products-btn">
                        <span class="dashicons dashicons-products"></span>
                        <?php _e('Ver todos los productos', 'wp-pos'); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="wp-pos-no-stock-issues">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('No hay productos con stock bajo en este momento.', 'wp-pos'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Agrega el hook para mostrar las alertas después de las estadísticas
 */
function wp_pos_initialize_stock_alerts() {
    // Agregar acción para mostrar alertas después de las estadísticas pero antes de los accesos rápidos
    add_action('wp_pos_dashboard_after_stats', 'wp_pos_render_stock_alerts_panel');
    
    // También agregar un hook para depuración que muestre el mensaje directamente en el footer
    add_action('admin_footer', 'wp_pos_debug_stock_alerts');
}
add_action('init', 'wp_pos_initialize_stock_alerts', 20);

/**
 * Función de depuración para asegurar que las alertas se muestren sin importar el hook
 */
function wp_pos_debug_stock_alerts() {
    // Solo ejecutar en la página principal del plugin
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if ($page !== 'wp-pos') {
        return;
    }
    
    // Verificar si el panel de alertas existe ya en la página
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var existingAlert = document.querySelector('.wp-pos-stock-alert');
        if (!existingAlert) {
            // Si no existe, y estamos en la página correcta, intentar insertarlo
            var statsContainer = document.querySelector('.wp-pos-dashboard-stats');
            var quickLinks = document.querySelector('.wp-pos-dashboard-quick-links');
            
            if (statsContainer && quickLinks) {
                // Crear contenedor para las alertas entre stats y quick links
                console.log('Debugging G-POS Stock Alerts: Insertando alertas manualmente');
                var alertContainer = document.createElement('div');
                alertContainer.className = 'wp-pos-debug-alerts';
                alertContainer.style.margin = '20px 0';
                
                // Insertar entre ambos elementos
                statsContainer.parentNode.insertBefore(alertContainer, quickLinks);
                
                // Llamar a la función de renderizado via AJAX
                var xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxurl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        alertContainer.innerHTML = xhr.responseText;
                    }
                };
                xhr.send('action=wp_pos_get_stock_alerts');
            }
        }
    });
    </script>
    <?php
}

/**
 * Handler AJAX para obtener las alertas de stock
 */
function wp_pos_ajax_get_stock_alerts() {
    ob_start();
    wp_pos_render_stock_alerts_panel();
    $html = ob_get_clean();
    echo $html;
    wp_die();
}
add_action('wp_ajax_wp_pos_get_stock_alerts', 'wp_pos_ajax_get_stock_alerts');
