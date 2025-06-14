<?php
/**
 * Plantilla del dashboard de administraciu00f3n
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Cargar header
wp_pos_template_header(array(
    'title' => __('Dashboard', 'wp-pos'),
    'active_menu' => 'dashboard'
));

// Cargar sistema de alertas de stock
require_once WP_POS_PLUGIN_DIR . 'includes/dashboard-stock-alerts.php';

// Obtener configuraciu00f3n
$options = wp_pos_get_option();

// Obtener datos para estadísticas y gráficos
$today = date('Y-m-d');
$sales_controller = class_exists('WP_POS_Sales_Controller') ? WP_POS_Sales_Controller::get_instance() : null;

// Inicializar estadísticas predeterminadas
$stats = array(
    'sales_today' => 0,
    'revenue_today' => 0,
    'products_sold' => 0,
    'customers_count' => 0
);

// Si tenemos el controlador de ventas, obtener datos reales
if ($sales_controller) {
    $stats['sales_today'] = $sales_controller->get_sales_count(array('date' => $today));
    $stats['revenue_today'] = $sales_controller->get_sales_revenue(array('date' => $today));
    $stats['products_sold'] = $sales_controller->get_products_sold_count(array('date' => $today));
}
?>

<div class="wp-pos-dashboard-wrapper">
    <!-- Header de bienvenida -->
    <div class="wp-pos-dashboard-welcome">
        <h2><?php _e('Bienvenido al Panel de Control de WP-POS', 'wp-pos'); ?></h2>
        <p><?php _e('Gestiona tu punto de venta completo desde esta interfaz. Supervisa las ventas, gestiona inventario y accede a funciones administrativas.', 'wp-pos'); ?></p>
    </div>
    
    <?php if (false && !class_exists('WooCommerce')) : ?>
    <!-- Aviso de WooCommerce (Desactivado) -->
    <!--
    <div class="wp-pos-woo-notice">
        <span class="dashicons dashicons-info"></span>
        <div>
            <?php _e('WP-POS se estu00e1 ejecutando en modo interoperable. Puedes utilizar la integraciu00f3n con WooCommerce para sincronizar productos y ventas.', 'wp-pos'); ?>
        </div>
    </div>
    -->
    <?php endif; ?>
    
    <!-- Estadísticas principales -->
    <div class="wp-pos-dashboard-stats">
        <div class="wp-pos-stats-row">
            <!-- Estadística: Ventas hoy -->
            <div class="wp-pos-stat-box" style="border-top: 3px solid #2271b1;">
                <div class="wp-pos-stat-title"><?php _e('Ventas hoy', 'wp-pos'); ?></div>
                <div class="wp-pos-stat-value"><?php echo esc_html($stats['sales_today']); ?></div>
                <div class="wp-pos-stat-footer">
                    <?php _e('Operaciones completas', 'wp-pos'); ?>
                </div>
                <div class="wp-pos-stat-icon dashicons dashicons-cart" style="color: #2271b1;"></div>
            </div>
            
            <!-- Estadística: Ingresos hoy -->
            <div class="wp-pos-stat-box" style="border-top: 3px solid #46b450;">
                <div class="wp-pos-stat-title"><?php _e('Ingresos hoy', 'wp-pos'); ?></div>
                <div class="wp-pos-stat-value"><?php echo wp_pos_format_price($stats['revenue_today']); ?></div>
                <div class="wp-pos-stat-footer">
                    <?php _e('Ventas totales', 'wp-pos'); ?>
                </div>
                <div class="wp-pos-stat-icon dashicons dashicons-money-alt" style="color: #46b450;"></div>
            </div>
            
            <!-- Estadística: Productos vendidos -->
            <div class="wp-pos-stat-box" style="border-top: 3px solid #f0c33c;">
                <div class="wp-pos-stat-title"><?php _e('Productos vendidos', 'wp-pos'); ?></div>
                <div class="wp-pos-stat-value"><?php echo esc_html($stats['products_sold']); ?></div>
                <div class="wp-pos-stat-footer">
                    <?php _e('Unidades hoy', 'wp-pos'); ?>
                </div>
                <div class="wp-pos-stat-icon dashicons dashicons-products" style="color: #f0c33c;"></div>
            </div>
        </div>
    </div>
    
    <!-- Accesos rápidos -->
    <div class="wp-pos-dashboard-quick-links">
        <h3><?php _e('Accesos rápidos', 'wp-pos'); ?></h3>
        <div class="wp-pos-quick-links-row">
            <!-- Acceso a Nueva venta -->
            <a href="<?php echo wp_pos_safe_esc_url(wp_pos_get_admin_url('new-sale')); ?>" class="wp-pos-quick-link">
                <span class="dashicons dashicons-cart"></span>
                <span class="wp-pos-quick-link-title"><?php esc_html_e('Nueva Venta', 'wp-pos'); ?></span>
            </a>
            
            <!-- Acceso a Ver ventas -->
            <a href="<?php echo wp_pos_safe_esc_url(wp_pos_get_admin_url('sales')); ?>" class="wp-pos-quick-link">
                <span class="dashicons dashicons-list-view"></span>
                <span class="wp-pos-quick-link-title"><?php esc_html_e('Historial de Ventas', 'wp-pos'); ?></span>
            </a>
            
            <!-- Acceso a Reportes -->
            <a href="<?php echo wp_pos_safe_esc_url(wp_pos_get_admin_url('reports')); ?>" class="wp-pos-quick-link">
                <span class="dashicons dashicons-chart-bar"></span>
                <span class="wp-pos-quick-link-title"><?php esc_html_e('Informes', 'wp-pos'); ?></span>
            </a>
            
            <!-- Acceso a Productos -->
            <a href="<?php echo wp_pos_safe_esc_url(admin_url('admin.php?page=wp-pos-products')); ?>" class="wp-pos-quick-link">
                <span class="dashicons dashicons-products"></span>
                <span class="wp-pos-quick-link-title"><?php esc_html_e('Productos', 'wp-pos'); ?></span>
            </a>
            
            <!-- Acceso a Configuración -->
            <a href="<?php echo wp_pos_safe_esc_url(admin_url('admin.php?page=wp-pos-settings')); ?>" class="wp-pos-quick-link">
                <span class="dashicons dashicons-admin-settings"></span>
                <span class="wp-pos-quick-link-title"><?php esc_html_e('Configuración', 'wp-pos'); ?></span>
            </a>
        </div>
    </div>
    
    <?php 
    // Punto de enganche para alertas de stock
    do_action('wp_pos_dashboard_after_stats'); 
    ?>
    
    <!-- Sección de actividad reciente -->
    <div class="wp-pos-dashboard-recent">
        <!-- Ventas recientes -->
        <div class="wp-pos-recent-sales">
            <h3><?php _e('Ventas recientes', 'wp-pos'); ?></h3>
            <?php
            // Acceso directo a la base de datos para obtener ventas recientes
            global $wpdb;
            $table_name = $wpdb->prefix . 'pos_sales';
            $recent_sales = $wpdb->get_results("SELECT * FROM $table_name WHERE (status != 'deleted' OR status IS NULL) ORDER BY date_created DESC LIMIT 5", ARRAY_A);  // ARRAY_A para obtener arrays asociativos y excluir ventas eliminadas
            
            if (!empty($recent_sales)) : ?>
                <table class="wp-pos-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'wp-pos'); ?></th>
                            <th><?php _e('Fecha', 'wp-pos'); ?></th>
                            <th><?php _e('Productos', 'wp-pos'); ?></th>
                            <th><?php _e('Total', 'wp-pos'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_sales as $sale) : 
                            // Deserializar items para contar productos
                            $items = maybe_unserialize($sale['items']);
                            $items_count = is_array($items) ? count($items) : 0;
                        ?>
                        <tr>
                            <td><a href="<?php echo admin_url('admin.php?page=wp-pos&tab=sales&action=view&id=' . $sale['id']); ?>"><?php echo esc_html($sale['id']); ?></a></td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sale['date'])); ?></td>
                            <td><?php echo $items_count; ?></td>
                            <td><?php echo wc_price($sale['total']); ?></td>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="wp-pos-view-all">
                    <a href="<?php echo wp_pos_safe_esc_url(admin_url('admin.php?page=wp-pos&tab=sales')); ?>" class="wp-pos-button wp-pos-button-secondary">
                        <?php esc_html_e('Ver todas las ventas', 'wp-pos'); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="wp-pos-empty-content">
                    <p><?php _e('No hay ventas recientes para mostrar.', 'wp-pos'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Panel de información y ayuda -->
        <div class="wp-pos-recent-activity">
            <h3><?php _e('Información y ayuda', 'wp-pos'); ?></h3>
            <ul>
                <li>
                    <a href="https://wppossystem.com/docs/" target="_blank">
                        <?php _e('Documentación del plugin', 'wp-pos'); ?>
                    </a>
                </li>
                <li>
                    <a href="https://wppossystem.com/soporte/" target="_blank">
                        <?php _e('Soporte técnico', 'wp-pos'); ?>
                    </a>
                </li>
                <li>
                    <a href="https://wppossystem.com/tutoriales/" target="_blank">
                        <?php _e('Tutoriales en video', 'wp-pos'); ?>
                    </a>
                </li>
            </ul>
            
            <h4><?php _e('Versión del plugin', 'wp-pos'); ?></h4>
            <p>
                <?php printf(__('Estás usando WP-POS versión %s', 'wp-pos'), WP_POS_VERSION); ?>
            </p>
        </div>
    </div>
</div>

<?php
// Cargar footer
wp_pos_template_footer();
