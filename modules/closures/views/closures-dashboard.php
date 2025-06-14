<?php
/**
 * Vista de Dashboard para Cierres de Caja
 *
 * @package WP-POS
 * @subpackage Closures
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) exit;

// Obtener fechas para filtros predeterminados
$current_date = current_time('Y-m-d');
$month_start = date('Y-m-01', strtotime($current_date));
$prev_month_start = date('Y-m-01', strtotime('-1 month', strtotime($current_date)));
$prev_month_end = date('Y-m-t', strtotime('-1 month', strtotime($current_date)));

// Variables para AJAX
$ajax_url = admin_url('admin-ajax.php');
$ajax_nonce = wp_create_nonce('wp_pos_closures_nonce');

// Cargar scripts necesarios
wp_enqueue_script('chart-js');
wp_enqueue_script('wp-pos-closures-dashboard-js');

// Pasar variables a JavaScript
wp_localize_script('wp-pos-closures-dashboard-js', 'ajax_object', array(
    'ajax_url' => $ajax_url,
    'nonce' => $ajax_nonce
));

wp_localize_script('wp-pos-closures-dashboard-js', 'closures_dashboard', array(
    'history_url' => admin_url('admin.php?page=wp-pos-module-closures&view=history&action=view&id='),
    'text' => array(
        'pending' => __('Pendiente', 'wp-pos'),
        'approved' => __('Aprobado', 'wp-pos'),
        'rejected' => __('Rechazado', 'wp-pos')
    )
));
?>

<div class="wrap wp-pos-dashboard-container">
    <?php include_once 'closures-header.php'; ?>
    
    <!-- Dashboard Content -->
    <div class="wp-pos-dashboard-content">
        <!-- Stats section removed -->
    </div>
    
    <hr class="wp-header-end">
    
    <!-- Tarjetas de resumen -->
    <div class="wp-pos-summary-section">
        <h2><span class="dashicons dashicons-money-alt"></span> <?php _e('Resumen', 'wp-pos'); ?></h2>
        
        <div class="wp-pos-dashboard-cards">
            <!-- Tarjeta: Total del mes actual -->
            <div class="wp-pos-card wp-pos-loading-target">
                <h3><span class="dashicons dashicons-calendar"></span> <?php _e('Mes Actual', 'wp-pos'); ?></h3>
                <div class="wp-pos-card-value" id="current-month-total">$0.00</div>
                <div class="wp-pos-card-subtitle"><?php echo date_i18n('F Y', strtotime($current_date)); ?></div>
            </div>
            
            <!-- Tarjeta: Total del mes anterior -->
            <div class="wp-pos-card wp-pos-loading-target">
                <h3><span class="dashicons dashicons-calendar-alt"></span> <?php _e('Mes Anterior', 'wp-pos'); ?></h3>
                <div class="wp-pos-card-value" id="prev-month-total">$0.00</div>
                <div class="wp-pos-card-subtitle"><?php echo date_i18n('F Y', strtotime('-1 month', strtotime($current_date))); ?></div>
            </div>
            
            <!-- Tarjeta: Cierres pendientes -->
            <div class="wp-pos-card wp-pos-loading-target">
                <h3><span class="dashicons dashicons-clock"></span> <?php _e('Pendientes', 'wp-pos'); ?></h3>
                <div class="wp-pos-card-value" id="pending-closures">0</div>
                <div class="wp-pos-card-subtitle"><?php _e('Cierres por revisar', 'wp-pos'); ?></div>
            </div>
            
            <!-- Tarjeta: Diferencia acumulada -->
            <div class="wp-pos-card wp-pos-loading-target">
                <h3><span class="dashicons dashicons-database"></span> <?php _e('Diferencia', 'wp-pos'); ?></h3>
                <div class="wp-pos-card-value" id="total-difference">$0.00</div>
                <div class="wp-pos-card-subtitle"><?php _e('Acumulada del mes', 'wp-pos'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos -->
    <div class="wp-pos-charts-section">
        <div class="wp-pos-charts-header">
            <h2><span class="dashicons dashicons-chart-area"></span> <?php _e('Tendencias', 'wp-pos'); ?></h2>
            
            <div class="wp-pos-chart-filters">
                <label for="chart-period"><?php _e('Período:', 'wp-pos'); ?></label>
                <select id="chart-period" class="regular-text">
                    <option value="week"><?php _e('Última Semana', 'wp-pos'); ?></option>
                    <option value="month" selected><?php _e('Mes Actual', 'wp-pos'); ?></option>
                    <option value="prev_month"><?php _e('Mes Anterior', 'wp-pos'); ?></option>
                    <option value="quarter"><?php _e('Último Trimestre', 'wp-pos'); ?></option>
                </select>
                
                <button id="refresh-chart" class="button">
                    <span class="dashicons dashicons-update"></span> <?php _e('Actualizar', 'wp-pos'); ?>
                </button>
            </div>
        </div>
        
        <div class="wp-pos-charts-grid">
            <!-- Gráfico: Montos diarios -->
            <div class="wp-pos-chart-container">
                <h3><?php _e('Montos Diarios', 'wp-pos'); ?></h3>
                <div class="wp-pos-chart-wrapper">
                    <canvas id="daily-amounts-chart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Gráfico: Distribución de estados -->
            <div class="wp-pos-chart-container">
                <h3><?php _e('Distribución de Estados', 'wp-pos'); ?></h3>
                <div class="wp-pos-chart-wrapper">
                    <canvas id="status-distribution-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de cierres recientes -->
    <div class="wp-pos-recent-closures">
        <div class="wp-pos-section-header">
            <h2><span class="dashicons dashicons-update"></span> <?php _e('Cierres Recientes', 'wp-pos'); ?></h2>
            <div class="wp-pos-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-pos-module-closures&view=history'); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span> <?php _e('Ver todos', 'wp-pos'); ?>
                </a>
            </div>
        </div>
        
        <div class="wp-pos-table-responsive">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'wp-pos'); ?></th>
                        <th><?php _e('Fecha', 'wp-pos'); ?></th>
                        <th><?php _e('Cajero', 'wp-pos'); ?></th>
                        <th><?php _e('Monto', 'wp-pos'); ?></th>
                        <th><?php _e('Diferencia', 'wp-pos'); ?></th>
                        <th><?php _e('Estado', 'wp-pos'); ?></th>
                        <th><?php _e('Acciones', 'wp-pos'); ?></th>
                    </tr>
                </thead>
                <tbody id="recent-closures-list">
                    <tr>
                        <td colspan="7" class="wp-pos-loading-text">
                            <span class="spinner is-active"></span>
                            <?php _e('Cargando cierres recientes...', 'wp-pos'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
