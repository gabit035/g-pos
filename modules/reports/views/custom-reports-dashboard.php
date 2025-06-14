<?php
/**
 * Vista del dashboard de reportes moderno mejorado
 *
 * @package WP-POS
 * @subpackage Reports
 * @since 1.0.0
 */

// Salir si se accede directamente
defined('ABSPATH') || exit;

// Cargar clase de datos de reportes
require_once dirname(__DIR__) . '/class-pos-reports-data.php';

// Cargar Chart.js para los gráficos - CORREGIDO
wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);

// Cargar estilos para los gráficos
wp_enqueue_style('wp-pos-charts-styles', plugin_dir_url(dirname(dirname(__DIR__))) . 'modules/reports/assets/css/charts-styles.css');

// --- SECCIÓN DE FILTROS MEJORADA ---
// 1) Definir opciones desplegables
$period_options = [
    'all'          => __('Todo el tiempo', 'wp-pos'),
    'today'        => __('Hoy', 'wp-pos'),
    'yesterday'    => __('Ayer', 'wp-pos'),
    'this_week'    => __('Esta Semana', 'wp-pos'),
    'last_week'    => __('Última Semana', 'wp-pos'),
    'this_month'   => __('Este Mes', 'wp-pos'),
    'last_month'   => __('Mes Pasado', 'wp-pos'),
    'last_30_days' => __('Últimos 30 Días', 'wp-pos'),
    'this_year'    => __('Este Año', 'wp-pos'),
    'custom'       => __('Personalizado', 'wp-pos'),
];

// Obtener vendedores (usuarios con rol de vendedor) - MEJORADO
$sellers = ['all' => __('Todos los vendedores', 'wp-pos')];

// Obtener el usuario actual
$current_user = wp_get_current_user();
$current_user_login = $current_user->user_login;

// Obtener usuarios con roles de POS (vendedor, administrador, cajero, gerente)
$seller_users = get_users(array(
    'role__in' => array('pos_seller', 'administrator', 'pos_manager', 'pos_cashier'),
    'orderby' => 'display_name',
    'order' => 'ASC',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'wp_capabilities',
            'value' => 'pos_',
            'compare' => 'LIKE'
        ),
        array(
            'key' => 'wp_capabilities',
            'value' => 'administrator',
            'compare' => 'LIKE'
        )
    )
));

// Agregar cada vendedor a la lista
foreach ($seller_users as $user) {
    $display_name = !empty($user->display_name) ? $user->display_name : $user->user_login;
    $sellers[$user->user_login] = $display_name . ' (' . $user->user_login . ')';
}

// Si no hay vendedores, agregar un mensaje
if (count($sellers) <= 1) {
    $sellers['no_sellers'] = __('No hay vendedores', 'wp-pos');
}

// Pre-seleccionar el vendedor actual si es un vendedor
if (in_array('pos_seller', $current_user->roles) && !isset($_GET['seller_id'])) {
    $default_seller = $current_user_login;
} else {
    $default_seller = isset($_GET['seller_id']) ? sanitize_text_field($_GET['seller_id']) : 'all';
}

// Obtener métodos de pago y agregar opción 'Todos' - MEJORADO
$payment_options = array_merge(
    ['all' => __('Todos métodos', 'wp-pos')],
    WP_POS_Reports_Data::get_payment_methods()
);

// 2) Recuperar valores actuales (GET) con saneamiento y valores por defecto - MEJORADO
$period         = sanitize_text_field($_GET['period'] ?? 'today');
$seller_id      = isset($_GET['seller_id']) ? sanitize_text_field($_GET['seller_id']) : $default_seller;
$payment_method = sanitize_text_field($_GET['payment_method'] ?? 'all');
$custom_from    = sanitize_text_field($_GET['date_from'] ?? '');
$custom_to      = sanitize_text_field($_GET['date_to'] ?? '');

// 3) Calcular rango de fecha - MEJORADO CON MEJOR MANEJO DE ZONAS HORARIAS
$current_timestamp = current_time('timestamp');
switch ($period) {
    case 'all':
        $date_from = '1970-01-01';
        $date_to   = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'today':
        $date_from = $date_to = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'yesterday':
        $yesterday_ts = $current_timestamp - DAY_IN_SECONDS;
        $date_from = $date_to = date_i18n('Y-m-d', $yesterday_ts);
        break;
    case 'this_week':
        $date_from = date_i18n('Y-m-d', strtotime('monday this week', $current_timestamp));
        $date_to   = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'last_week':
        $date_from = date_i18n('Y-m-d', strtotime('monday last week', $current_timestamp));
        $date_to   = date_i18n('Y-m-d', strtotime('sunday last week', $current_timestamp));
        break;
    case 'this_month':
        $date_from = date_i18n('Y-m-01', $current_timestamp);
        $date_to   = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'last_month':
        $date_from = date_i18n('Y-m-01', strtotime('first day of last month', $current_timestamp));
        $date_to   = date_i18n('Y-m-t', strtotime('last day of last month', $current_timestamp));
        break;
    case 'last_30_days':
        $date_from = date_i18n('Y-m-d', $current_timestamp - (30 * DAY_IN_SECONDS));
        $date_to   = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'this_year':
        $date_from = date_i18n('Y-01-01', $current_timestamp);
        $date_to   = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'custom':
        // Validar fechas personalizadas con mejor manejo
        if (!empty($custom_from) && !empty($custom_to)) {
            $date_from = date('Y-m-d', strtotime($custom_from));
            $date_to   = date('Y-m-d', strtotime($custom_to));
            // Validar que la fecha from no sea mayor que date_to
            if (strtotime($date_from) > strtotime($date_to)) {
                $temp = $date_from;
                $date_from = $date_to;
                $date_to = $temp;
            }
        } else {
            // Fallback a hoy si las fechas custom no son válidas
            $date_from = $date_to = date_i18n('Y-m-d', $current_timestamp);
        }
        break;
    default:
        // Fallback a hoy
        $date_from = $date_to = date_i18n('Y-m-d', $current_timestamp);
}

// 4) Preparar argumentos para consulta de reportes - MEJORADO
$args = [
    'date_from'      => $date_from . ' 00:00:00',
    'date_to'        => $date_to . ' 23:59:59',
    'seller_id'      => $seller_id,
    'payment_method' => $payment_method,
    'status'         => 'completed'
];

// Argumentos para ventas recientes con límite
$recent_sales_args = array_merge($args, [
    'limit' => 10
]);

// Obtener datos de totales y ventas recientes
$report_data = WP_POS_Reports_Data::get_totals($args);
$report_data['recent_sales'] = WP_POS_Reports_Data::get_recent_sales($recent_sales_args);

// --- OBTENER DATOS PARA GRÁFICOS - NUEVO ---
$chart_data = get_chart_data_for_period($date_from, $date_to, $seller_id, $payment_method);

// Función para obtener datos de gráficos
function get_chart_data_for_period($date_from, $date_to, $seller_id = 'all', $payment_method = 'all') {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'pos_sales';
    $where_conditions = ["s.status = 'completed'"];
    $params = [];
    
    // Filtros de fecha
    $where_conditions[] = "DATE(s.date_created) BETWEEN %s AND %s";
    $params[] = $date_from;
    $params[] = $date_to;
    
    // Filtro por vendedor
    if ($seller_id !== 'all') {
        if (is_numeric($seller_id)) {
            $where_conditions[] = "s.user_id = %d";
            $params[] = intval($seller_id);
        } else {
            $user = get_user_by('login', $seller_id);
            if ($user) {
                $where_conditions[] = "s.user_id = %d";
                $params[] = $user->ID;
            }
        }
    }
    
    // Filtro por método de pago
    if ($payment_method !== 'all') {
        $where_conditions[] = "s.payment_method = %s";
        $params[] = $payment_method;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $chart_data = [
        'sales_trend' => get_sales_trend_data($where_clause, $params, $date_from, $date_to),
        'payment_methods' => get_payment_methods_data($where_clause, $params),
        'sellers_performance' => get_sellers_data($where_clause, $params),
        'top_products' => get_top_products_data($where_clause, $params)
    ];
    
    return $chart_data;
}

function get_sales_trend_data($where_clause, $params, $date_from, $date_to) {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'pos_sales';
    
    // Determinar el agrupamiento basado en el rango de fechas
    $date_diff = (strtotime($date_to) - strtotime($date_from)) / (24 * 3600);
    
    if ($date_diff <= 1) {
        // Por horas para 1 día
        $group_format = "%Y-%m-%d %H:00:00";
        $date_format = "CONCAT(DATE_FORMAT(s.date_created, '%Y-%m-%d %H:00:00'))";
    } elseif ($date_diff <= 31) {
        // Por días para hasta 1 mes
        $group_format = "%Y-%m-%d";
        $date_format = "DATE(s.date_created)";
    } else {
        // Por semanas para períodos más largos
        $group_format = "%Y-%u";
        $date_format = "CONCAT(YEAR(s.date_created), '-', WEEK(s.date_created))";
    }
    
    $query = "SELECT 
                $date_format as period,
                COUNT(*) as sales_count,
                SUM(s.total) as total_amount
              FROM $sales_table s 
              WHERE $where_clause 
              GROUP BY period 
              ORDER BY period";
    
    if (!empty($params)) {
        $prepared_query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
    } else {
        $results = $wpdb->get_results($query, ARRAY_A);
    }
    
    $labels = [];
    $data = [];
    
    if (!empty($results)) {
        foreach ($results as $row) {
            if ($date_diff <= 1) {
                // Formato de hora
                $labels[] = date('H:i', strtotime($row['period']));
            } elseif ($date_diff <= 31) {
                // Formato de día
                $labels[] = date('d/m', strtotime($row['period']));
            } else {
                // Formato de semana
                $labels[] = 'Sem ' . substr($row['period'], -2);
            }
            $data[] = floatval($row['total_amount']);
        }
    } else {
        // Datos demo si no hay resultados
        $labels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $data = [1200, 1900, 1500, 2500, 2200, 3000, 2800];
    }
    
    return ['labels' => $labels, 'data' => $data];
}

function get_payment_methods_data($where_clause, $params) {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'pos_sales';
    
    $query = "SELECT 
                COALESCE(s.payment_method, 'efectivo') as method,
                COUNT(*) as count,
                SUM(s.total) as total
              FROM $sales_table s 
              WHERE $where_clause 
              GROUP BY s.payment_method 
              ORDER BY total DESC";
    
    if (!empty($params)) {
        $prepared_query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
    } else {
        $results = $wpdb->get_results($query, ARRAY_A);
    }
    
    $labels = [];
    $data = [];
    
    if (!empty($results)) {
        $payment_methods = WP_POS_Reports_Data::get_payment_methods();
        
        foreach ($results as $row) {
            $method_key = strtolower($row['method']);
            $method_label = isset($payment_methods[$method_key]) ? $payment_methods[$method_key] : ucfirst($row['method']);
            
            $labels[] = $method_label;
            $data[] = intval($row['count']);
        }
    } else {
        // Datos demo si no hay resultados
        $labels = ['Efectivo', 'Tarjeta', 'Transferencia'];
        $data = [45, 35, 20];
    }
    
    return ['labels' => $labels, 'data' => $data];
}

function get_sellers_data($where_clause, $params) {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'pos_sales';
    $users_table = $wpdb->users;
    
    $query = "SELECT 
                COALESCE(u.display_name, 'Sistema') as seller_name,
                COUNT(*) as sales_count,
                SUM(s.total) as total_amount
              FROM $sales_table s 
              LEFT JOIN $users_table u ON s.user_id = u.ID
              WHERE $where_clause 
              GROUP BY s.user_id 
              ORDER BY total_amount DESC 
              LIMIT 5";
    
    if (!empty($params)) {
        $prepared_query = $wpdb->prepare($query, $params);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);
    } else {
        $results = $wpdb->get_results($query, ARRAY_A);
    }
    
    $labels = [];
    $data = [];
    
    if (!empty($results)) {
        foreach ($results as $row) {
            $labels[] = $row['seller_name'];
            $data[] = intval($row['sales_count']);
        }
    } else {
        // Datos demo si no hay resultados
        $labels = ['Vendedor 1', 'Vendedor 2', 'Vendedor 3'];
        $data = [8, 5, 3];
    }
    
    return ['labels' => $labels, 'data' => $data];
}

function get_top_products_data($where_clause, $params) {
    global $wpdb;
    
    $sales_table = $wpdb->prefix . 'pos_sales';
    $sale_items_table = $wpdb->prefix . 'pos_sale_items';
    
    // Verificar si la tabla de items existe
    $items_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sale_items_table'") === $sale_items_table;
    
    if ($items_table_exists) {
        $query = "SELECT 
                    si.name as product_name,
                    SUM(si.quantity) as total_qty,
                    SUM(si.total) as total_amount
                  FROM $sales_table s 
                  JOIN $sale_items_table si ON s.id = si.sale_id
                  WHERE $where_clause 
                  GROUP BY si.name 
                  ORDER BY total_qty DESC 
                  LIMIT 5";
        
        if (!empty($params)) {
            $prepared_query = $wpdb->prepare($query, $params);
            $results = $wpdb->get_results($prepared_query, ARRAY_A);
        } else {
            $results = $wpdb->get_results($query, ARRAY_A);
        }
        
        $labels = [];
        $data = [];
        
        if (!empty($results)) {
            foreach ($results as $row) {
                $labels[] = strlen($row['product_name']) > 20 ? substr($row['product_name'], 0, 17) . '...' : $row['product_name'];
                $data[] = intval($row['total_qty']);
            }
        } else {
            // Datos demo si no hay resultados
            $labels = ['Producto A', 'Producto B', 'Producto C', 'Producto D', 'Producto E'];
            $data = [54, 45, 30, 25, 15];
        }
    } else {
        // Datos demo si no existe la tabla
        $labels = ['Producto A', 'Producto B', 'Producto C', 'Producto D', 'Producto E'];
        $data = [54, 45, 30, 25, 15];
    }
    
    return ['labels' => $labels, 'data' => $data];
}

// Crear nonce para AJAX
$ajax_nonce = wp_create_nonce('wp_pos_reports_nonce');

// --- FIN SECCIÓN DE FILTROS ---

// Asignar variables para las plantillas
$sales_count = $report_data['sales_count'];
$total_revenue = $report_data['total_revenue'];
$total_profit = $report_data['total_profit'];
$profit_margin = $report_data['profit_margin'];
$average_sale = $report_data['average_sale'];
$recent_sales = $report_data['recent_sales'];

?>

<div class="wrap wp-pos-reports-dashboard">
    <!-- Notificación dinámica -->
    <div class="wp-pos-notification" style="display:none;"></div>
    
    <!-- Overlay de carga -->
    <div class="wp-pos-loading-overlay" style="display:none;">
        <div class="wp-pos-spinner-container">
            <div class="wp-pos-spinner"></div>
            <p><?php _e('Cargando datos...', 'wp-pos'); ?></p>
        </div>
    </div>
    
    <!-- Cabecera principal con gradiente -->
    <div class="wp-pos-reports-header">
        <h1><?php _e('Panel de Reportes', 'wp-pos'); ?></h1>
        <span class="wp-pos-current-datetime"><?php echo date_i18n('d/m/Y H:i', $current_timestamp); ?></span>
        <button class="wp-pos-refresh-button" id="wp-pos-refresh-data">
            <i class="dashicons dashicons-update"></i> <?php _e('Actualizar datos', 'wp-pos'); ?>
        </button>
    </div>
    
    <!-- Sección de filtros MEJORADA -->
    <div class="wp-pos-filter-section">
        <form method="GET" class="wp-pos-filter-form" id="wp-pos-filter-form">
            <input type="hidden" name="page" value="wp-pos-reports" />
            
            <div class="wp-pos-filter-row">
                <div class="wp-pos-filter-group">
                    <label><i class="dashicons dashicons-calendar-alt"></i> <?php _e('Periodo', 'wp-pos'); ?></label>
                    <select id="wp-pos-periodo" name="period">
                        <?php foreach ($period_options as $key => $name): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($period, $key); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Campos de fecha personalizada -->
                <div class="wp-pos-filter-group wp-pos-custom-dates" <?php echo $period !== 'custom' ? 'style="display:none;"' : ''; ?>>
                    <label><i class="dashicons dashicons-calendar"></i> <?php _e('Desde', 'wp-pos'); ?></label>
                    <input type="date" id="wp-pos-date-from" name="date_from" value="<?php echo esc_attr($custom_from); ?>" />
                </div>
                
                <div class="wp-pos-filter-group wp-pos-custom-dates" <?php echo $period !== 'custom' ? 'style="display:none;"' : ''; ?>>
                    <label><i class="dashicons dashicons-calendar"></i> <?php _e('Hasta', 'wp-pos'); ?></label>
                    <input type="date" id="wp-pos-date-to" name="date_to" value="<?php echo esc_attr($custom_to); ?>" />
                </div>
                
                <div class="wp-pos-filter-group">
                    <label><i class="dashicons dashicons-admin-users"></i> <?php _e('Vendedor', 'wp-pos'); ?></label>
                    <select id="wp-pos-vendedor" name="seller_id">
                        <?php foreach ($sellers as $key => $name): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($seller_id, $key); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="wp-pos-filter-group">
                    <label><i class="dashicons dashicons-admin-generic"></i> <?php _e('Método de Pago', 'wp-pos'); ?></label>
                    <select id="wp-pos-payment-method" name="payment_method">
                        <?php foreach ($payment_options as $key => $name): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($payment_method, $key); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="wp-pos-filter-row">
                <button type="button" class="wp-pos-apply-filters" id="wp-pos-apply-filters-ajax">
                    <i class="dashicons dashicons-search"></i> 
                    <span><?php _e('Aplicar Filtros', 'wp-pos'); ?></span> 
                    <i class="dashicons dashicons-arrow-right-alt"></i>
                </button>
                
                <button type="submit" class="wp-pos-apply-filters wp-pos-apply-filters-get" style="margin-left: 10px; background: #666;">
                    <i class="dashicons dashicons-admin-page"></i> 
                    <span><?php _e('Recargar Página', 'wp-pos'); ?></span>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Tarjetas de resumen -->
    <div id="wp-pos-summary-cards-placeholder">
        <?php
        // Preparar array de totales para plantilla
        $totals = [
            'sales_count' => $sales_count,
            'total_revenue' => $total_revenue,
            'total_profit' => $total_profit,
            'profit_margin' => $profit_margin,
            'average_sale' => $average_sale,
        ];
        include dirname(__DIR__) . '/templates/summary-cards.php';
        ?>
    </div>
    
    <!-- Ventas recientes -->
    <div id="wp-pos-recent-sales-table-placeholder">
        <?php
        include dirname(__DIR__) . '/templates/recent-sales-table.php';
        ?>
    </div>
    
    <!-- Sección de gráficos CORREGIDA -->
    <div id="wp-pos-charts-section-placeholder">
        <?php
        // Pasar los datos de gráficos a la plantilla
        $payment_methods_data = $chart_data['payment_methods'];
        $top_products_data = $chart_data['top_products'];
        
        include dirname(__DIR__) . '/templates/charts-section.php';
        ?>
    </div>
</div>

<!-- Script mejorado para filtros dinámicos Y GRÁFICOS -->
<script>
jQuery(document).ready(function($) {
    // Configuración AJAX
    var wpPosReports = {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo $ajax_nonce; ?>',
        
        // Datos de gráficos desde PHP
        chartData: <?php echo json_encode($chart_data); ?>,
        
        // Mostrar/ocultar campos de fecha personalizada
        toggleCustomDates: function() {
            var period = $('#wp-pos-periodo').val();
            var $customDates = $('.wp-pos-custom-dates');
            
            if (period === 'custom') {
                $customDates.slideDown(300);
                // Si están vacíos, asignar fecha actual
                if (!$('#wp-pos-date-from').val()) {
                    $('#wp-pos-date-from').val('<?php echo date('Y-m-d'); ?>');
                }
                if (!$('#wp-pos-date-to').val()) {
                    $('#wp-pos-date-to').val('<?php echo date('Y-m-d'); ?>');
                }
            } else {
                $customDates.slideUp(300);
            }
        },
        
        // Aplicar filtros con AJAX
        applyFilters: function() {
            var $loading = $('.wp-pos-loading-overlay');
            var $notification = $('.wp-pos-notification');
            
            // Mostrar loading
            $loading.fadeIn(200);
            
            // Preparar datos
            var period = $('#wp-pos-periodo').val();
            var seller_id = $('#wp-pos-vendedor').val();
            var payment_method = $('#wp-pos-payment-method').val();
            var date_from = $('#wp-pos-date-from').val();
            var date_to = $('#wp-pos-date-to').val();
            
            var data = {
                action: 'get_pos_report_data',
                security: wpPosReports.nonce,
                period: period,
                seller_id: seller_id,
                payment_method: payment_method,
                date_from: date_from,
                date_to: date_to
            };
            
            // Hacer petición AJAX
            $.post(wpPosReports.ajaxUrl, data, function(response) {
                $loading.fadeOut(200);
                
                if (response.success && response.data) {
                    // Actualizar tarjetas de resumen
                    if (response.data.html_summary_cards) {
                        $('#wp-pos-summary-cards-placeholder').html(response.data.html_summary_cards);
                    }
                    
                    // Actualizar tabla de ventas recientes
                    if (response.data.html_recent_sales_table) {
                        $('#wp-pos-recent-sales-table-placeholder').html(response.data.html_recent_sales_table);
                    }
                    
                    // Actualizar gráficos si hay datos
                    if (response.data.chart_data) {
                        wpPosReports.updateCharts(response.data.chart_data);
                    }
                    
                    // Actualizar sección completa de gráficos si viene HTML
                    if (response.data.html_charts_section) {
                        $('#wp-pos-charts-section-placeholder').html(response.data.html_charts_section);
                    }
                    
                    // Mostrar notificación de éxito
                    $notification
                        .removeClass('error')
                        .addClass('success')
                        .text('<?php _e('Datos actualizados correctamente', 'wp-pos'); ?>')
                        .fadeIn(300);
                    
                    setTimeout(function() {
                        $notification.fadeOut(300);
                    }, 3000);
                    
                } else {
                    // Mostrar error
                    $notification
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data ? response.data.message : '<?php _e('Error al cargar los datos', 'wp-pos'); ?>')
                        .fadeIn(300);
                }
            }).fail(function() {
                $loading.fadeOut(200);
                $notification
                    .removeClass('success')
                    .addClass('error')
                    .text('<?php _e('Error de conexión', 'wp-pos'); ?>')
                    .fadeIn(300);
            });
        },
        
        // Actualizar gráficos con nuevos datos
        updateCharts: function(newChartData) {
            this.chartData = newChartData;
            this.initializeCharts(); // Re-inicializar con nuevos datos
        },
        
        // Actualizar datos (refrescar)
        refreshData: function() {
            // Resetear filtros y aplicar
            $('#wp-pos-periodo').val('today');
            $('#wp-pos-vendedor').val('all');
            $('#wp-pos-payment-method').val('all');
            wpPosReports.toggleCustomDates();
            wpPosReports.applyFilters();
        },
        
        // Inicializar gráficos
        initializeCharts: function() {
            // Esperar a que Chart.js esté disponible
            if (typeof Chart === 'undefined') {
                console.log('Chart.js no está disponible, esperando...');
                setTimeout(function() {
                    wpPosReports.initializeCharts();
                }, 1000);
                return;
            }
            
            console.log('Inicializando gráficos con datos:', this.chartData);
            
            // El código de inicialización de gráficos se ejecutará desde charts-section.php
            // Solo necesitamos asegurar que los datos estén disponibles
            window.wpPosChartData = this.chartData;
            
            // Disparar evento para que charts-section.php actualice los gráficos
            $(document).trigger('wppos:update-charts', [this.chartData]);
        }
    };
    
    // Event listeners
    $('#wp-pos-periodo').on('change', wpPosReports.toggleCustomDates);
    $('#wp-pos-apply-filters-ajax').on('click', wpPosReports.applyFilters);
    $('#wp-pos-refresh-data').on('click', wpPosReports.refreshData);
    
    // Inicializar
    wpPosReports.toggleCustomDates();
    
    // Auto-aplicar filtros al cambiar cualquier filtro (opcional)
    $('#wp-pos-vendedor, #wp-pos-payment-method').on('change', function() {
        // Aplicar filtros automáticamente después de 500ms
        clearTimeout(window.wpPosAutoFilter);
        window.wpPosAutoFilter = setTimeout(wpPosReports.applyFilters, 500);
    });
    
    // Para fechas personalizadas, aplicar al cambiar
    $('#wp-pos-date-from, #wp-pos-date-to').on('change', function() {
        if ($('#wp-pos-periodo').val() === 'custom') {
            clearTimeout(window.wpPosAutoFilter);
            window.wpPosAutoFilter = setTimeout(wpPosReports.applyFilters, 500);
        }
    });
    
    // Inicializar gráficos al cargar la página
    wpPosReports.initializeCharts();
    
    // Exponer globalmente
    window.wpPosReports = wpPosReports;
});
</script>


<script>
// CORRECCIÓN JAVASCRIPT PARA FILTRO DE MÉTODO DE PAGO
jQuery(document).ready(function($) {
    
    // Debug específico para método de pago
    $('#wp-pos-payment-method').on('change', function() {
        var selectedMethod = $(this).val();
        console.log('=== FILTRO MÉTODO DE PAGO CAMBIADO ===');
        console.log('Método seleccionado:', selectedMethod);
        
        // Verificar que no esté vacío
        if (!selectedMethod) {
            console.warn('Método de pago está vacío, forzando a "all"');
            $(this).val('all');
        }
        
        // Log para debug
        console.log('Valor final del filtro:', $(this).val());
    });
    
    // Intercepción del AJAX para verificar que se envíe el método de pago
    var originalApplyFilters = window.wpPosReports ? window.wpPosReports.applyFilters : null;
    
    if (originalApplyFilters && window.wpPosReports) {
        window.wpPosReports.applyFilters = function() {
            // Usar el método getFilterValues del objeto global WPPosReports
            var filters = window.WPPosReports ? window.WPPosReports.getFilterValues() : 
                          (window.wpPosReports.getFilterValues ? window.wpPosReports.getFilterValues() : 
                          { period: 'today', seller_id: 'all', payment_method: 'all', date_from: '', date_to: '' });
            
            console.log('=== VERIFICACIÓN FILTROS ANTES DE AJAX ===');
            console.log('Todos los filtros:', filters);
            console.log('Método de pago específico:', filters.payment_method);
            
            // Verificar que el método de pago se esté enviando correctamente
            if (filters.payment_method === null || filters.payment_method === undefined) {
                console.error('ERROR: Método de pago es null/undefined, corrigiendo...');
                filters.payment_method = 'all';
                $('#wp-pos-payment-method').val('all');
            }
            
            // Llamar a la función original con el contexto correcto
            return originalApplyFilters.call(window.WPPosReports || window.wpPosReports || this);
        };
    }
    
    // Test manual del filtro
    window.testPaymentFilter = function(method) {
        console.log('=== TEST MANUAL FILTRO MÉTODO DE PAGO ===');
        $('#wp-pos-payment-method').val(method || 'cash').trigger('change');
        
        setTimeout(function() {
            if (window.wpPosReports && window.wpPosReports.applyFilters) {
                window.wpPosReports.applyFilters();
            }
        }, 500);
    };
    
    console.log('Funciones de debug del filtro de método de pago cargadas.');
    console.log('Usa testPaymentFilter("cash") para probar manualmente.');
});
</script>