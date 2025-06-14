<?php
/**
 * Sistema de alertas directo para el dashboard de G-POS
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
function wp_pos_direct_alerts_enqueue_styles() {
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
add_action('admin_enqueue_scripts', 'wp_pos_direct_alerts_enqueue_styles');

/**
 * Obtiene productos con stock bajo directamente de la base de datos
 * 
 * @return array Productos con stock bajo
 */
function wp_pos_get_low_stock_products_direct() {
    global $wpdb;
    
    // Obtener configuraciu00f3n del umbral
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
 * Renderiza directamente un bloque HTML con notificaciones de stock
 */
function wp_pos_direct_stock_alert_html() {
    // Solo ejecutar en la pu00e1gina principal del plugin
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if ($page !== 'wp-pos') {
        return;
    }

    // Obtener productos con stock bajo
    $products = wp_pos_get_low_stock_products_direct();
    
    // Si no hay productos con stock bajo, no mostrar nada
    if (empty($products)) {
        return;
    }
    
    // Obtener umbral configurado
    $options = wp_pos_get_option();
    $threshold = isset($options['low_stock_threshold']) ? (int)$options['low_stock_threshold'] : 5;
    
    // Inyectar CSS directamente para asegurar que se aplique
    echo '<style>
    .wp-pos-stock-alert {
        border: 2px solid #e74c3c;
        border-radius: 6px;
        margin: 20px 0;
        background-color: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .wp-pos-stock-alert-header {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        padding: 10px 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .wp-pos-stock-alert-title {
        display: flex;
        align-items: center;
        font-size: 16px;
        font-weight: bold;
        margin: 0;
    }
    .wp-pos-stock-alert-title .dashicons {
        margin-right: 8px;
        font-size: 20px;
        width: 20px;
        height: 20px;
    }
    .wp-pos-stock-alert-body {
        padding: 15px;
    }
    .wp-pos-stock-products {
        margin-top: 10px;
        border-collapse: collapse;
        width: 100%;
    }
    .wp-pos-stock-products th {
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
        background-color: #f5f5f5;
    }
    .wp-pos-stock-products td {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }
    .wp-pos-stock-products .stock-count {
        color: #e74c3c;
        font-weight: bold;
    }
    .wp-pos-stock-actions {
        margin-top: 15px;
        text-align: right;
    }
    .wp-pos-all-products-btn {
        display: inline-block;
        padding: 8px 15px;
        background-color: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 14px;
    }
    .wp-pos-all-products-btn:hover {
        background-color: #2980b9;
        color: white;
    }
    </style>';
    
    // Generar HTML para las notificaciones
    $html = '<div class="wp-pos-stock-alert">
        <div class="wp-pos-stock-alert-header">
            <h3 class="wp-pos-stock-alert-title">
                <span class="dashicons dashicons-warning"></span>
                ' . sprintf(_n('%d producto con stock bajo', '%d productos con stock bajo', count($products), 'wp-pos'), count($products)) . '
            </h3>
            <span>Umbral: ' . $threshold . '</span>
        </div>
        
        <div class="wp-pos-stock-alert-body">
            <table class="wp-pos-stock-products">
                <thead>
                    <tr>
                        <th>' . esc_html__('ID', 'wp-pos') . '</th>
                        <th>' . esc_html__('Producto', 'wp-pos') . '</th>
                        <th>' . esc_html__('SKU', 'wp-pos') . '</th>
                        <th>' . esc_html__('Stock', 'wp-pos') . '</th>
                        <th>' . esc_html__('Acciones', 'wp-pos') . '</th>
                    </tr>
                </thead>
                <tbody>';
                
    foreach ($products as $product) {
        $html .= '<tr>
            <td>' . esc_html($product->id) . '</td>
            <td><strong>' . esc_html($product->name) . '</strong></td>
            <td>' . esc_html($product->sku) . '</td>
            <td class="stock-count">' . esc_html($product->stock_quantity) . '</td>
            <td>
                <a href="' . admin_url('admin.php?page=wp-pos-products&action=edit&product_id=' . $product->id) . '" 
                   class="button button-small">
                   <span class="dashicons dashicons-edit"></span>
                   ' . esc_html__('Editar', 'wp-pos') . '
                </a>
            </td>
        </tr>';
    }
    
    $html .= '</tbody>
            </table>
            
            <div class="wp-pos-stock-actions">
                <a href="' . admin_url('admin.php?page=wp-pos-products') . '" class="wp-pos-all-products-btn">
                    <span class="dashicons dashicons-products"></span>
                    ' . esc_html__('Ver todos los productos', 'wp-pos') . '
                </a>
            </div>
        </div>
    </div>';
    
    // Inyectar notificaciones directamente luego del dashboard-stats
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Buscar el contenedor de estadísticas en el dashboard
        var statsContainer = document.querySelector('.wp-pos-dashboard-stats');
        if (statsContainer) {
            // Crear un div para contener las notificaciones
            var alertsContainer = document.createElement('div');
            alertsContainer.className = 'wp-pos-dashboard-alerts';
            alertsContainer.innerHTML = <?php echo json_encode($html); ?>;
            
            // Insertar las notificaciones justo después de las estadísticas
            statsContainer.parentNode.insertBefore(alertsContainer, statsContainer.nextSibling);
        }
    });
    </script>
    <?php
}

// Este hook se ejecuta en el footer del admin, asegurando que el DOM ya esté cargado
add_action('admin_footer', 'wp_pos_direct_stock_alert_html');
