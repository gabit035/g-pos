<?php
/**
 * Helper para mostrar notificaciones en el dashboard del plugin
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtiene los productos con stock bajo para mostrar en el dashboard
 *
 * @return array Productos con stock bajo
 */
function wp_pos_get_low_stock_products() {
    global $wpdb;
    
    // Obtener umbral configurado en las opciones de G-POS
    $options = wp_pos_get_option();
    $threshold = isset($options['low_stock_threshold']) ? intval($options['low_stock_threshold']) : 2;
    
    // Buscar en diferentes tablas de productos para mayor compatibilidad
    $result = array();
    
    // Intentar con la tabla de productos personalizada de WP-POS
    $pos_products_table = $wpdb->prefix . 'pos_products';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$pos_products_table}'")==$pos_products_table) {
        $query = $wpdb->prepare(
            "SELECT id, name, sku, stock_quantity FROM {$pos_products_table} " . 
            "WHERE stock_quantity > 0 AND stock_quantity <= %d AND type = 'product' " . 
            "ORDER BY stock_quantity ASC LIMIT 10",
            $threshold
        );
        $result = $wpdb->get_results($query);
    }
    
    // Si no hay resultados, intentar con la tabla de WooCommerce
    if (empty($result) && class_exists('WooCommerce')) {
        $query = "
            SELECT  
                p.ID as id,
                p.post_title as name,
                pm.meta_value as sku,
                pm2.meta_value as stock_quantity
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
            JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_stock'
            WHERE
                p.post_type = 'product'
                AND pm2.meta_value > 0
                AND pm2.meta_value <= {$threshold}
            ORDER BY pm2.meta_value ASC
            LIMIT 10
        ";
        
        $woo_products = $wpdb->get_results($query);
        if (!empty($woo_products)) {
            $result = $woo_products;
        }
    }
    
    // Si sigue vacío, intentar con la tabla wc_products
    if (empty($result)) {
        $wc_products_table = $wpdb->prefix . 'wc_products';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wc_products_table}'")==$wc_products_table) {
            $query = $wpdb->prepare(
                "SELECT id, name, sku, stock_quantity FROM {$wc_products_table} " . 
                "WHERE stock_quantity > 0 AND stock_quantity <= %d " . 
                "ORDER BY stock_quantity ASC LIMIT 10",
                $threshold
            );
            $result = $wpdb->get_results($query);
        }
    }
    
    // Registrar información para depuración
    error_log('WP-POS Dashboard: Encontrados ' . count($result) . ' productos con stock bajo (umbral: ' . $threshold . ')');
    
    return $result;
}

/**
 * Muestra la sección de notificaciones de stock bajo en el dashboard
 */
function wp_pos_display_dashboard_stock_notifications() {
    // Banner de depuración visible para ayudar al diagnóstico
    echo '<!-- Inicio del componente de notificaciones de stock bajo -->';
    
    // Obtener productos con stock bajo
    $low_stock_products = wp_pos_get_low_stock_products();
    
    // Obtener opciones para mostrar información de depuración
    $options = wp_pos_get_option();
    $threshold = isset($options['low_stock_threshold']) ? intval($options['low_stock_threshold']) : 2;
    
    // Mostrar información de depuración
    echo '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0; background-color: #f9f9f9;">';
    echo '<p><strong>Depuración de notificaciones:</strong> ';
    echo 'Umbral configurado: ' . $threshold . '. ';
    echo 'Productos encontrados con stock bajo: ' . count($low_stock_products) . '</p>';
    echo '</div>';
    
    // Si no hay productos con stock bajo, no mostrar la sección principal
    if (empty($low_stock_products)) {
        echo '<!-- No se encontraron productos con stock bajo -->';
        return;
    }
    
    // Obtener umbral de las opciones de G-POS
    $options = wp_pos_get_option();
    $threshold = isset($options['low_stock_threshold']) ? intval($options['low_stock_threshold']) : 2;
    
    // HTML para la sección de stock bajo
    ?>
    <div class="wp-pos-dashboard-stock-alerts" style="margin-top: 20px;">
        <div class="wp-pos-card" style="border-top: 3px solid #e74c3c;">
            <div class="wp-pos-card-header" style="display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0;">
                    <span class="dashicons dashicons-warning" style="color: #e74c3c; margin-right: 8px;"></span>
                    <?php 
                    $count = count($low_stock_products);
                    if ($count == 1) {
                        echo 'Producto con stock bajo (umbral: ' . $threshold . ')';
                    } else {
                        echo 'Productos con stock bajo (umbral: ' . $threshold . ')';
                    }
                    ?>
                </h3>
            </div>
            <div class="wp-pos-card-body">
                <table class="wp-pos-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'wp-pos'); ?></th>
                            <th><?php _e('Producto', 'wp-pos'); ?></th>
                            <th><?php _e('SKU', 'wp-pos'); ?></th>
                            <th><?php _e('Stock actual', 'wp-pos'); ?></th>
                            <th><?php _e('Acciones', 'wp-pos'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_products as $product) : ?>
                        <tr>
                            <td><?php echo esc_html($product->id); ?></td>
                            <td>
                                <strong><?php echo esc_html($product->name); ?></strong>
                            </td>
                            <td><?php echo esc_html($product->sku); ?></td>
                            <td>
                                <span style="color: #e74c3c; font-weight: bold;">
                                    <?php echo esc_html($product->stock_quantity); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-products&action=edit&product_id=' . $product->id); ?>" 
                                   class="button button-small">
                                   <span class="dashicons dashicons-edit" style="font-size: 16px; height: 16px; width: 16px; margin-right: 4px;"></span>
                                   <?php _e('Editar', 'wp-pos'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
