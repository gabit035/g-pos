<?php
/**
 * Plantilla de reportes
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
    'title' => __('Reportes', 'wp-pos'),
    'active_menu' => 'reports'
));

// Obtener configuraciu00f3n
$options = wp_pos_get_option();

// Filtros por defecto
$report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : 'sales';
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

// Procesar datos del reporte si hay filtros aplicados
$has_applied_filters = isset($_GET['report_type']);
$report_data = array(
    'total_sales' => 0,
    'total_income' => 0,
    'avg_per_sale' => 0,
    'daily_data' => array()
);

// Obtener datos del reporte si se han aplicado filtros
if ($has_applied_filters) {
    // Usar el mu00f3dulo de reportes mejorado
    $reports_module = WP_POS_Reports_Module::get_instance();
    $report_data = $reports_module->process_report($report_type, $start_date, $end_date);
}
?>

<div class="wp-pos-admin-wrapper wp-pos-reports-wrapper">
    <?php if (false && WP_POS_WOOCOMMERCE_ACTIVE) : ?><!-- Desactivado a peticiu00f3n del usuario -->
    <!-- Aviso de WooCommerce -->
    <div class="wp-pos-woo-notice">
        <span class="dashicons dashicons-info"></span>
        <div>
            <?php _e('WP-POS se estu00e1 ejecutando en modo interoperable. Puedes utilizar la integraciu00f3n con WooCommerce, si lo deseas.', 'wp-pos'); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Panel de control -->
    <div class="wp-pos-control-panel">
        <div class="wp-pos-control-panel-primary">
            <h3><?php _e('Informes y Estadu00edsticas', 'wp-pos'); ?></h3>
            <p><?php _e('Genera informes detallados de ventas, productos y clientes para analizar el rendimiento de tu negocio.', 'wp-pos'); ?></p>
        </div>
        
        <div class="wp-pos-control-panel-secondary">
            <a href="<?php echo wp_pos_safe_esc_url(admin_url('admin.php?page=wp-pos-reports&export=csv&report_type=' . $report_type . '&start_date=' . $start_date . '&end_date=' . $end_date)); ?>" class="wp-pos-button wp-pos-button-secondary">
                <span class="dashicons dashicons-media-spreadsheet"></span>
                <?php esc_html_e('Exportar CSV', 'wp-pos'); ?>
            </a>
            <a href="<?php echo wp_pos_safe_esc_url(admin_url('admin.php?page=wp-pos-reports&export=pdf&report_type=' . $report_type . '&start_date=' . $start_date . '&end_date=' . $end_date)); ?>" class="wp-pos-button wp-pos-button-secondary">
                <span class="dashicons dashicons-pdf"></span>
                <?php esc_html_e('Exportar PDF', 'wp-pos'); ?>
            </a>
        </div>
    </div>
    
    <!-- Aviso de desarrollo -->
    <div class="wp-pos-notice-box info">
        <p><?php _e('Los reportes por tipo de cliente ya están disponibles. Selecciona "Ventas por cliente" en el selector de tipo de reporte para ver datos agrupados por cliente.', 'wp-pos'); ?></p>
    </div>
    
    <!-- Contenido principal -->
    <div class="wp-pos-content-panel">
        <div class="wp-pos-form-columns">
            <!-- Filtros de reporte -->
            <div class="wp-pos-form-column" style="flex: 0 0 300px;">
                <div class="wp-pos-form-card">
                    <div class="wp-pos-form-card-header">
                        <h3><?php _e('Filtros', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-form-card-body">
                        <form method="get" action="" id="wp-pos-report-filter-form">
                            <input type="hidden" name="page" value="wp-pos-reports">
                            
                            <div class="wp-pos-form-group">
                                <label for="wp-pos-report-type"><?php _e('Tipo de reporte', 'wp-pos'); ?></label>
                                <select id="wp-pos-report-type" name="report_type" class="wp-pos-select">
                                    <option value="sales" <?php selected($report_type, 'sales'); ?>><?php _e('Ventas por peru00edodo', 'wp-pos'); ?></option>
                                    <option value="products" <?php selected($report_type, 'products'); ?>><?php _e('Productos vendidos', 'wp-pos'); ?></option>
                                    <option value="customers" <?php selected($report_type, 'customers'); ?>><?php _e('Ventas por cliente', 'wp-pos'); ?></option>
                                    <option value="payment" <?php selected($report_type, 'payment'); ?>><?php _e('Ventas por mu00e9todo de pago', 'wp-pos'); ?></option>
                                </select>
                            </div>
                            
                            <div class="wp-pos-form-group">
                                <label for="wp-pos-start-date"><?php _e('Fecha de inicio', 'wp-pos'); ?></label>
                                <input type="date" id="wp-pos-start-date" name="start_date" value="<?php echo esc_attr($start_date); ?>" class="wp-pos-input">
                            </div>
                            
                            <div class="wp-pos-form-group">
                                <label for="wp-pos-end-date"><?php _e('Fecha de fin', 'wp-pos'); ?></label>
                                <input type="date" id="wp-pos-end-date" name="end_date" value="<?php echo esc_attr($end_date); ?>" class="wp-pos-input">
                            </div>
                            
                            <div class="wp-pos-form-actions" style="margin-top: 20px;">
                                <button type="submit" class="wp-pos-button wp-pos-button-primary wp-pos-button-block">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                    <?php _e('Generar reporte', 'wp-pos'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Acceso ru00e1pido a reportes predefinidos -->
                <div class="wp-pos-form-card">
                    <div class="wp-pos-form-card-header">
                        <h3><?php _e('Reportes ru00e1pidos', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-form-card-body">
                        <ul class="wp-pos-quick-reports">
                            <li>
                                <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('report_type' => 'sales', 'start_date' => date('Y-m-d', strtotime('-7 days')), 'end_date' => date('Y-m-d')), admin_url('admin.php?page=wp-pos-reports'))); ?>" class="wp-pos-quick-report-link">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php esc_html_e('Ventas de la u00faltima semana', 'wp-pos'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('report_type' => 'sales', 'start_date' => date('Y-m-d', strtotime('-30 days')), 'end_date' => date('Y-m-d')), admin_url('admin.php?page=wp-pos-reports'))); ?>" class="wp-pos-quick-report-link">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php esc_html_e('Ventas de los u00faltimos 30 du00edas', 'wp-pos'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('report_type' => 'sales', 'start_date' => date('Y-m-01'), 'end_date' => date('Y-m-t')), admin_url('admin.php?page=wp-pos-reports'))); ?>" class="wp-pos-quick-report-link">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php esc_html_e('Ventas del mes actual', 'wp-pos'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo wp_pos_safe_esc_url(wp_pos_safe_add_query_arg(array('report_type' => 'sales', 'start_date' => date('Y-01-01'), 'end_date' => date('Y-12-31')), admin_url('admin.php?page=wp-pos-reports'))); ?>" class="wp-pos-quick-report-link">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php esc_html_e('Ventas del au00f1o actual', 'wp-pos'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Resultados del reporte -->
            <div class="wp-pos-form-column">
                <div class="wp-pos-form-card">
                    <div class="wp-pos-form-card-header">
                        <h3><?php _e('Resultados del reporte', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-form-card-body">
                        <div class="wp-pos-chart-container">
                            <?php if ($has_applied_filters) : ?>
                                <!-- Gru00e1fico (versiu00f3n futura) -->
                                <div class="wp-pos-placeholder-chart">
                                    <div class="wp-pos-chart-icon">
                                        <span class="dashicons dashicons-chart-bar"></span>
                                    </div>
                                    <p><?php _e('Los gru00e1ficos estaru00e1n disponibles en la pru00f3xima versiu00f3n', 'wp-pos'); ?></p>
                                </div>
                            <?php else : ?>
                                <!-- Mensaje cuando no hay filtros aplicados -->
                                <div class="wp-pos-placeholder-chart">
                                    <div class="wp-pos-chart-icon">
                                        <span class="dashicons dashicons-chart-bar"></span>
                                    </div>
                                    <p><?php _e('Selecciona un tipo de reporte y fecha para generar gru00e1ficos', 'wp-pos'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="wp-pos-form-card">
                    <div class="wp-pos-form-card-header">
                        <h3><?php _e('Resumen', 'wp-pos'); ?></h3>
                    </div>
                    <div class="wp-pos-form-card-body">
                        <div class="wp-pos-summary-grid">
                             <div class="wp-pos-summary-item">
                                <div class="wp-pos-summary-title">
                                    <?php if ($report_type === 'customers') : ?>
                                        <?php _e('Total de ventas', 'wp-pos'); ?>
                                    <?php else : ?>
                                        <?php _e('Ventas totales', 'wp-pos'); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="wp-pos-summary-value"><?php echo intval($report_data['total_sales']); ?></div>
                            </div>
                            
                            <div class="wp-pos-summary-item">
                                <div class="wp-pos-summary-title">
                                    <?php if ($report_type === 'customers') : ?>
                                        <?php _e('Total de ingresos', 'wp-pos'); ?>
                                    <?php else : ?>
                                        <?php _e('Ingresos totales', 'wp-pos'); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="wp-pos-summary-value"><?php echo wp_pos_format_price($report_data['total_income']); ?></div>
                            </div>
                            
                            <div class="wp-pos-summary-item">
                                <div class="wp-pos-summary-title">
                                    <?php if ($report_type === 'customers') : ?>
                                        <?php _e('Clientes distintos', 'wp-pos'); ?>
                                    <?php else : ?>
                                        <?php _e('Promedio por venta', 'wp-pos'); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="wp-pos-summary-value">
                                    <?php if ($report_type === 'customers') : ?>
                                        <?php echo count($report_data['daily_data']); ?>
                                    <?php else : ?>
                                        <?php echo wp_pos_format_price($report_data['avg_per_sale']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="wp-pos-form-card">
                    <div class="wp-pos-form-card-header">
                        <h3>
                            <?php if ($report_type === 'customers') : ?>
                                <?php _e('Detalle por cliente', 'wp-pos'); ?>
                            <?php else : ?>
                                <?php _e('Detalles', 'wp-pos'); ?>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="wp-pos-form-card-body">
                        <div class="wp-pos-table-container">
                            <table class="wp-pos-table">
                                <thead>
                                    <tr>
                                        <?php if ($report_type === 'customers') : ?>
                                            <th><?php _e('Cliente', 'wp-pos'); ?></th>
                                            <th><?php _e('Compras', 'wp-pos'); ?></th>
                                            <th class="wp-pos-column-right"><?php _e('Total gastado', 'wp-pos'); ?></th>
                                        <?php elseif ($report_type === 'products') : ?>
                                            <th><?php _e('Producto', 'wp-pos'); ?></th>
                                            <th><?php _e('Cantidad', 'wp-pos'); ?></th>
                                            <th class="wp-pos-column-right"><?php _e('Total vendido', 'wp-pos'); ?></th>
                                        <?php elseif ($report_type === 'payment') : ?>
                                            <th><?php _e('Mu00e9todo de pago', 'wp-pos'); ?></th>
                                            <th><?php _e('Transacciones', 'wp-pos'); ?></th>
                                            <th class="wp-pos-column-right"><?php _e('Total', 'wp-pos'); ?></th>
                                        <?php else : ?>
                                            <th><?php _e('Fecha', 'wp-pos'); ?></th>
                                            <th><?php _e('Ventas', 'wp-pos'); ?></th>
                                            <th class="wp-pos-column-right"><?php _e('Ingresos', 'wp-pos'); ?></th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($has_applied_filters) : ?>
                                        <?php if ($report_type === 'sales' && !empty($report_data['daily_data'])) : ?>
                                            <?php foreach ($report_data['daily_data'] as $date => $day) : ?>
                                            <tr>
                                                <td><?php echo esc_html(date('d/m/Y', strtotime($date))); ?></td>
                                                <td><?php echo intval($day['count']); ?></td>
                                                <td class="wp-pos-column-right"><?php echo wp_pos_format_price($day['total']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php elseif ($report_type === 'products' && !empty($report_data['products'])) : ?>
                                            <tr>
                                                <th><?php _e('Producto', 'wp-pos'); ?></th>
                                                <th><?php _e('Cantidad', 'wp-pos'); ?></th>
                                                <th class="wp-pos-column-right"><?php _e('Total vendido', 'wp-pos'); ?></th>
                                            </tr>
                                            <?php foreach ($report_data['products'] as $product) : ?>
                                            <tr>
                                                <td><?php echo esc_html($product->name); ?></td>
                                                <td><?php echo intval($product->quantity_sold); ?></td>
                                                <td class="wp-pos-column-right"><?php echo wp_pos_format_price($product->total_sales); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php elseif ($report_type === 'customers' && !empty($report_data['customers'])) : ?>
                                            <tr>
                                                <th><?php _e('Cliente', 'wp-pos'); ?></th>
                                                <th><?php _e('Compras', 'wp-pos'); ?></th>
                                                <th class="wp-pos-column-right"><?php _e('Total gastado', 'wp-pos'); ?></th>
                                            </tr>
                                            <?php foreach ($report_data['customers'] as $customer) : ?>
                                            <tr>
                                                <td><?php echo isset($customer->customer_name) ? esc_html($customer->customer_name) : __('Cliente #', 'wp-pos') . $customer->customer_id; ?></td>
                                                <td><?php echo intval($customer->num_sales); ?></td>
                                                <td class="wp-pos-column-right"><?php echo wp_pos_format_price($customer->total_spent); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php elseif ($report_type === 'payment' && !empty($report_data['payment_methods'])) : ?>
                                            <tr>
                                                <th><?php _e('Mu00e9todo de pago', 'wp-pos'); ?></th>
                                                <th><?php _e('Transacciones', 'wp-pos'); ?></th>
                                                <th class="wp-pos-column-right"><?php _e('Total', 'wp-pos'); ?></th>
                                            </tr>
                                            <?php foreach ($report_data['payment_methods'] as $payment) : ?>
                                            <tr>
                                                <td><?php echo esc_html($payment->payment_method); ?></td>
                                                <td><?php echo intval($payment->num_payments); ?></td>
                                                <td class="wp-pos-column-right"><?php echo wp_pos_format_price($payment->total_amount); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <tr class="wp-pos-summary-row">
                                            <th><?php _e('Total', 'wp-pos'); ?></th>
                                            <th><?php echo intval($report_data['total_sales']); ?></th>
                                            <th class="wp-pos-column-right"><?php echo wp_pos_format_price($report_data['total_income']); ?></th>
                                        </tr>
                                    <?php else : ?>
                                        <tr class="wp-pos-empty-row">
                                            <td colspan="3" class="wp-pos-empty-message">
                                                <div class="wp-pos-empty-icon">
                                                    <span class="dashicons dashicons-media-spreadsheet"></span>
                                                </div>
                                                <p><?php _e('No hay datos disponibles para mostrar', 'wp-pos'); ?></p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Cargar footer
wp_pos_template_footer();
