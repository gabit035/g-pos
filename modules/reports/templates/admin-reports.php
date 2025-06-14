<?php
/**
 * Vista de administración de reportes mejorada
 * Template principal para la página de administración de reportes
 *
 * @package WP-POS
 * @subpackage Reports
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) exit;

// Verificar permisos
if (!current_user_can('wp_pos_view_reports')) {
    wp_die(__('No tienes permisos para acceder a esta página.', 'wp-pos'));
}

// --- Preparar variables y configuración mejorada ---
global $wpdb;

// Configuración de tablas
$sales_table = $wpdb->prefix . 'pos_sales';
$sales_items_table = $wpdb->prefix . 'pos_sale_items';
$payments_table = $wpdb->prefix . 'pos_payments';

// Crear nonce para seguridad
$wp_pos_nonce = wp_create_nonce('wp_pos_reports_nonce');

// Verificar si las tablas existen
$tables_exist = $wpdb->get_var("SHOW TABLES LIKE '$sales_table'") == $sales_table;
$items_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sales_items_table'") == $sales_items_table;
$payments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$payments_table'") == $payments_table;

// Obtener configuración del módulo
$module_config = array();
if (class_exists('WP_POS_Reports_Module')) {
    $module = WP_POS_Reports_Module::get_instance();
    $module_config = $module->get_config();
}

// Configuración de filtros mejorada
$period_options = array(
    'all' => __('Todo el tiempo', 'wp-pos'),
    'today' => __('Hoy', 'wp-pos'),
    'yesterday' => __('Ayer', 'wp-pos'),
    'this_week' => __('Esta semana', 'wp-pos'),
    'last_week' => __('Semana pasada', 'wp-pos'),
    'this_month' => __('Este mes', 'wp-pos'),
    'last_month' => __('Mes pasado', 'wp-pos'),
    'last_30_days' => __('Últimos 30 días', 'wp-pos'),
    'this_year' => __('Este año', 'wp-pos'),
    'custom' => __('Personalizado', 'wp-pos')
);

// Obtener roles disponibles dinámicamente
$role_options = array('all' => __('Todos los roles', 'wp-pos'));
$available_roles = wp_roles()->get_names();
foreach ($available_roles as $role_key => $role_name) {
    if (strpos($role_key, 'pos_') === 0 || $role_key === 'administrator') {
        $role_options[$role_key] = $role_name;
    }
}

// Agregar opción para vendedores individuales
$roles_busqueda = array('pos_manager', 'pos_seller', 'pos_cashier', 'administrador');

// Obtener TODOS los usuarios primero
$all_users = get_users(array(
    'fields' => array('ID', 'user_login', 'display_name'),
    'orderby' => 'display_name'
));

// Filtrar usuarios manualmente por roles
$filtered_sellers = array();
$users_with_roles = array();

foreach ($all_users as $user) {
    $user_data = get_userdata($user->ID);
    $user_roles = $user_data->roles;
    
    // Verificar si el usuario tiene alguno de los roles buscados
    $has_valid_role = false;
    foreach ($roles_busqueda as $role) {
        if (in_array($role, $user_roles)) {
            $has_valid_role = true;
            break;
        }
    }
    
    if ($has_valid_role) {
        $filtered_sellers[] = $user;
        $users_with_roles[] = array(
            'ID' => $user->ID,
            'user_login' => $user->user_login,
            'display_name' => $user->display_name,
            'roles' => $user_roles
        );
    }
}

// Usar los usuarios filtrados
$sellers = $filtered_sellers;

// Depuración
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('=== DEPURACIÓN DE USUARIOS POS ===');
    error_log('Roles buscados: ' . print_r($roles_busqueda, true));
    error_log('Usuarios encontrados con roles POS: ' . print_r($users_with_roles, true));
}

$seller_options = array('all' => __('Todos los vendedores', 'wp-pos'));
foreach ($sellers as $seller) {
    $display_name = !empty($seller->display_name) ? $seller->display_name : $seller->user_login;
    $seller_options[$seller->user_login] = $display_name . ' (' . $seller->user_login . ')';
}

// Opciones de tipo de transacción
$transaction_options = array(
    'all' => __('Todas las transacciones', 'wp-pos'),
    'sale' => __('Ventas', 'wp-pos'),
    'return' => __('Devoluciones', 'wp-pos'),
    'refund' => __('Reembolsos', 'wp-pos')
);

// Obtener valores actuales de filtros con saneamiento
$current_period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'today';
$current_role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : 'all';
$current_seller = isset($_GET['seller_id']) ? sanitize_text_field($_GET['seller_id']) : 'all';
$current_payment_method = isset($_GET['payment_method']) ? sanitize_text_field($_GET['payment_method']) : 'all';
$current_transaction = isset($_GET['transaction_type']) ? sanitize_text_field($_GET['transaction_type']) : 'all';
$custom_date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$custom_date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Calcular fechas basadas en el período seleccionado
$current_timestamp = current_time('timestamp');
switch ($current_period) {
    case 'all':
        $date_from = '1970-01-01';
        $date_to = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'yesterday':
        $yesterday = $current_timestamp - DAY_IN_SECONDS;
        $date_from = date_i18n('Y-m-d', $yesterday);
        $date_to = date_i18n('Y-m-d', $yesterday);
        break;
    case 'this_week':
        $date_from = date_i18n('Y-m-d', strtotime('monday this week', $current_timestamp));
        $date_to = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'last_week':
        $date_from = date_i18n('Y-m-d', strtotime('monday last week', $current_timestamp));
        $date_to = date_i18n('Y-m-d', strtotime('sunday last week', $current_timestamp));
        break;
    case 'this_month':
        $date_from = date_i18n('Y-m-01', $current_timestamp);
        $date_to = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'last_month':
        $date_from = date_i18n('Y-m-01', strtotime('first day of last month', $current_timestamp));
        $date_to = date_i18n('Y-m-t', strtotime('last day of last month', $current_timestamp));
        break;
    case 'last_30_days':
        $date_from = date_i18n('Y-m-d', $current_timestamp - (30 * DAY_IN_SECONDS));
        $date_to = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'this_year':
        $date_from = date_i18n('Y-01-01', $current_timestamp);
        $date_to = date_i18n('Y-m-d', $current_timestamp);
        break;
    case 'custom':
        $date_from = !empty($custom_date_from) ? $custom_date_from : date_i18n('Y-m-d', $current_timestamp);
        $date_to = !empty($custom_date_to) ? $custom_date_to : date_i18n('Y-m-d', $current_timestamp);
        break;
    default: // today
        $date_from = date_i18n('Y-m-d', $current_timestamp);
        $date_to = date_i18n('Y-m-d', $current_timestamp);
}

// Obtener tab actual
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// --- Renderizado de la página ---
?>

<div class="wrap wp-pos-reports-admin">
    
    <!-- Notificación dinámica mejorada -->
    <div class="wp-pos-notification" style="display:none;"></div>
    
    <!-- Overlay de carga mejorado -->
    <div class="wp-pos-loading-overlay" style="display:none;">
        <div class="wp-pos-spinner-container">
            <div class="wp-pos-spinner"></div>
            <p><?php _e('Cargando datos...', 'wp-pos'); ?></p>
        </div>
    </div>
    
    <!-- Cabecera principal con navegación por tabs -->
    <div class="wp-pos-reports-header">
        <div class="wp-pos-header-content">
            <h1><?php _e('Panel de Reportes WP-POS', 'wp-pos'); ?></h1>
            <div class="wp-pos-header-meta">
                <span class="wp-pos-current-datetime">
                    <?php echo date_i18n('l, d/m/Y H:i', $current_timestamp); ?>
                </span>
                <?php if ($tables_exist): ?>
                    <span class="wp-pos-status-indicator status-ok">
                        <i class="dashicons dashicons-yes-alt"></i>
                        <?php _e('Sistema operativo', 'wp-pos'); ?>
                    </span>
                <?php else: ?>
                    <span class="wp-pos-status-indicator status-warning">
                        <i class="dashicons dashicons-warning"></i>
                        <?php _e('Tablas no encontradas', 'wp-pos'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Navegación por tabs -->
        <nav class="wp-pos-nav-tabs">
            <a href="<?php echo admin_url('admin.php?page=wp-pos-reports&tab=dashboard'); ?>" 
               class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                <i class="dashicons dashicons-dashboard"></i>
                <?php _e('Dashboard', 'wp-pos'); ?>
            </a>
            
            <?php if (current_user_can('wp_pos_export_reports')): ?>
                <a href="<?php echo admin_url('admin.php?page=wp-pos-reports&tab=export'); ?>" 
                   class="nav-tab <?php echo $current_tab === 'export' ? 'nav-tab-active' : ''; ?>">
                    <i class="dashicons dashicons-download"></i>
                    <?php _e('Exportar', 'wp-pos'); ?>
                </a>
            <?php endif; ?>
            
            <?php if (current_user_can('wp_pos_manage_reports_config')): ?>
                <a href="<?php echo admin_url('admin.php?page=wp-pos-reports&tab=settings'); ?>" 
                   class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <i class="dashicons dashicons-admin-settings"></i>
                    <?php _e('Configuración', 'wp-pos'); ?>
                </a>
            <?php endif; ?>
            
            <button class="wp-pos-refresh-button" id="wp-pos-refresh-all">
                <i class="dashicons dashicons-update"></i>
                <?php _e('Actualizar', 'wp-pos'); ?>
            </button>
        </nav>
    </div>
    
    <!-- Contenido principal según el tab -->
    <div class="wp-pos-tab-content">
        
        <?php if ($current_tab === 'dashboard'): ?>
            
            <!-- Dashboard principal -->
            <?php if (!$tables_exist): ?>
                
                <!-- Mensaje de error para tablas faltantes -->
                <div class="wp-pos-setup-notice">
                    <div class="wp-pos-setup-content">
                        <i class="dashicons dashicons-database"></i>
                        <h2><?php _e('Configuración Inicial Requerida', 'wp-pos'); ?></h2>
                        <p><?php _e('Las tablas de reportes no están configuradas. Esto puede deberse a que:', 'wp-pos'); ?></p>
                        <ul>
                            <li><?php _e('El plugin WP-POS no está completamente instalado', 'wp-pos'); ?></li>
                            <li><?php _e('Las tablas de la base de datos no se crearon correctamente', 'wp-pos'); ?></li>
                            <li><?php _e('No se han registrado ventas aún', 'wp-pos'); ?></li>
                        </ul>
                        
                        <div class="wp-pos-setup-actions">
                            <a href="<?php echo admin_url('admin.php?page=wp-pos-settings'); ?>" class="button button-primary">
                                <i class="dashicons dashicons-admin-settings"></i>
                                <?php _e('Ir a Configuración', 'wp-pos'); ?>
                            </a>
                            <button class="button button-secondary wp-pos-create-tables" data-action="create-tables">
                                <i class="dashicons dashicons-database-add"></i>
                                <?php _e('Crear Tablas', 'wp-pos'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Datos de demostración -->
                    <div class="wp-pos-demo-preview">
                        <h3><?php _e('Vista Previa del Dashboard', 'wp-pos'); ?></h3>
                        <p><?php _e('Así se verá el dashboard una vez que tengas datos de ventas:', 'wp-pos'); ?></p>
                        
                        <!-- Incluir dashboard de demostración -->
                        <?php
                        // Datos ficticios para demostración
                        $demo_totals = [
                            'sales_count' => 25,
                            'total_revenue' => 15750.50,
                            'total_profit' => 4725.15,
                            'profit_margin' => 30,
                            'average_sale' => 630.02,
                            'debug_message' => __('Datos de demostración - Las tablas no existen', 'wp-pos')
                        ];
                        
                        $demo_recent_sales = [
                            [
                                'id' => 1001,
                                'display_name' => 'Juan Pérez',
                                'items_count' => 3,
                                'total' => 456.78,
                                'payment_method' => 'Efectivo',
                                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                            ],
                            [
                                'id' => 1002,
                                'display_name' => 'María García',
                                'items_count' => 2,
                                'total' => 289.99,
                                'payment_method' => 'Tarjeta',
                                'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
                            ]
                        ];
                        
                        // Establecer variables para las plantillas
                        $totals = $demo_totals;
                        $recent_sales = $demo_recent_sales;
                        ?>
                        
                        <div class="wp-pos-demo-dashboard">
                            <?php include dirname(__DIR__) . '/templates/summary-cards.php'; ?>
                            <?php include dirname(__DIR__) . '/templates/recent-sales-table.php'; ?>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                
                <!-- Dashboard funcional -->
                <div class="wp-pos-dashboard-container">
                    
                    <!-- Sección de Actividad Reciente -->
                    <div class="wp-pos-recent-activity-section">
                        <div class="wp-pos-section-header">
                            <h2 class="wp-pos-section-title">
                                <i class="dashicons dashicons-update"></i>
                                <?php _e('Actividad Reciente', 'wp-pos'); ?>
                            </h2>
                            <div class="wp-pos-section-actions">
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-sales'); ?>" class="button button-secondary">
                                    <i class="dashicons dashicons-list-view"></i>
                                    <?php _e('Ver todas las ventas', 'wp-pos'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="wp-pos-recent-activity-container">
                            <div class="wp-pos-activity-loading">
                                <span class="spinner is-active"></span>
                                <?php _e('Cargando actividad reciente...', 'wp-pos'); ?>
                            </div>
                            <div class="wp-pos-activity-content" style="display: none;">
                                <!-- Contenido cargado vía AJAX -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de filtros mejorada -->
                    <div class="wp-pos-filter-section">
                        <div class="wp-pos-filter-header">
                            <h3>
                                <i class="dashicons dashicons-filter"></i>
                                <?php _e('Filtros de Reportes', 'wp-pos'); ?>
                            </h3>
                            <div class="wp-pos-filter-actions">
                                <button class="wp-pos-reset-filters" title="<?php esc_attr_e('Resetear filtros', 'wp-pos'); ?>">
                                    <i class="dashicons dashicons-undo"></i>
                                </button>
                                <button class="wp-pos-save-filters" title="<?php esc_attr_e('Guardar filtros', 'wp-pos'); ?>">
                                    <i class="dashicons dashicons-heart"></i>
                                </button>
                            </div>
                        </div>
                        
                        <form class="wp-pos-filter-form" method="get" id="wp-pos-filter-form">
                            <input type="hidden" name="page" value="wp-pos-reports">
                            <input type="hidden" name="tab" value="dashboard">
                            
                            <div class="wp-pos-filter-row">
                                <div class="wp-pos-filter-group">
                                    <label><i class="dashicons dashicons-calendar-alt"></i> <?php _e('Período', 'wp-pos'); ?></label>
                                    <select id="wp-pos-periodo" name="period">
                                        <?php foreach ($period_options as $key => $name): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current_period, $key); ?>>
                                                <?php echo esc_html($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Campos de fecha personalizada -->
                                <div class="wp-pos-filter-group wp-pos-custom-dates" <?php echo $current_period !== 'custom' ? 'style="display:none;"' : ''; ?>>
                                    <label><i class="dashicons dashicons-calendar"></i> <?php _e('Desde', 'wp-pos'); ?></label>
                                    <input type="date" id="wp-pos-date-from" name="date_from" value="<?php echo esc_attr($custom_date_from); ?>" />
                                </div>
                                
                                <div class="wp-pos-filter-group wp-pos-custom-dates" <?php echo $current_period !== 'custom' ? 'style="display:none;"' : ''; ?>>
                                    <label><i class="dashicons dashicons-calendar"></i> <?php _e('Hasta', 'wp-pos'); ?></label>
                                    <input type="date" id="wp-pos-date-to" name="date_to" value="<?php echo esc_attr($custom_date_to); ?>" />
                                </div>
                                
                                <div class="wp-pos-filter-group">
                                    <label><i class="dashicons dashicons-admin-users"></i> <?php _e('Vendedor', 'wp-pos'); ?></label>
                                    <select id="wp-pos-vendedor" name="seller_id">
                                        <?php foreach ($seller_options as $key => $name): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current_seller, $key); ?>>
                                                <?php echo esc_html($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if (class_exists('WP_POS_Reports_Data')): ?>
                                    <div class="wp-pos-filter-group">
                                        <label><i class="dashicons dashicons-admin-generic"></i> <?php _e('Método de Pago', 'wp-pos'); ?></label>
                                        <select id="wp-pos-payment-method" name="payment_method">
                                            <option value="all" <?php selected($current_payment_method, 'all'); ?>>
                                                <?php _e('Todos los métodos', 'wp-pos'); ?>
                                            </option>
                                            <?php
                                            $payment_methods = WP_POS_Reports_Data::get_payment_methods();
                                            foreach ($payment_methods as $key => $name):
                                            ?>
                                                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_payment_method, $key); ?>>
                                                    <?php echo esc_html($name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="wp-pos-filter-group">
                                    <label><i class="dashicons dashicons-cart"></i> <?php _e('Tipo', 'wp-pos'); ?></label>
                                    <select id="wp-pos-tipo-transaccion" name="transaction_type">
                                        <?php foreach ($transaction_options as $key => $name): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current_transaction, $key); ?>>
                                                <?php echo esc_html($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="wp-pos-filter-row wp-pos-filter-actions-row">
                                <div class="wp-pos-filter-actions-left">
                                    <button type="button" class="wp-pos-apply-filters wp-pos-apply-filters-ajax" id="wp-pos-apply-filters-ajax">
                                        <i class="dashicons dashicons-search"></i>
                                        <span><?php _e('Aplicar Filtros (AJAX)', 'wp-pos'); ?></span>
                                        <i class="dashicons dashicons-arrow-right-alt"></i>
                                    </button>
                                    
                                    <button type="submit" class="wp-pos-apply-filters wp-pos-apply-filters-get">
                                        <i class="dashicons dashicons-admin-page"></i>
                                        <span><?php _e('Recargar Página', 'wp-pos'); ?></span>
                                    </button>
                                </div>
                                
                                <div class="wp-pos-filter-actions-right">
                                    <button type="button" class="wp-pos-reset-filters" title="<?php esc_attr_e('Resetear todos los filtros', 'wp-pos'); ?>">
                                        <i class="dashicons dashicons-undo"></i>
                                        <?php _e('Resetear', 'wp-pos'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Contenido del dashboard -->
                    <div class="wp-pos-dashboard-content">
                        <?php
                        // Cargar datos reales usando la clase de datos
                        if (class_exists('WP_POS_Reports_Data')) {
                            $args = [
                                'date_from' => $date_from . ' 00:00:00',
                                'date_to' => $date_to . ' 23:59:59',
                                'seller_id' => $current_seller,
                                'payment_method' => $current_payment_method,
                                'status' => 'completed'
                            ];
                            
                            $report_data = WP_POS_Reports_Data::get_totals($args);
                            $recent_sales_data = WP_POS_Reports_Data::get_recent_sales(array_merge($args, ['limit' => 10]));
                            
                            // Asignar a variables para plantillas
                            $totals = $report_data;
                            $recent_sales = $recent_sales_data;
                        } else {
                            // Fallback si la clase no está disponible
                            $totals = [
                                'success' => false,
                                'message' => __('Clase WP_POS_Reports_Data no disponible', 'wp-pos'),
                                'sales_count' => 0,
                                'total_revenue' => 0,
                                'total_profit' => 0,
                                'profit_margin' => 0,
                                'average_sale' => 0
                            ];
                            $recent_sales = [];
                        }
                        ?>
                        
                        <!-- Contenedores que se actualizarán dinámicamente -->
                        <div id="wp-pos-summary-cards-placeholder">
                            <?php include dirname(__DIR__) . '/templates/summary-cards.php'; ?>
                        </div>
                        
                        <div id="wp-pos-recent-sales-table-placeholder">
                            <?php include dirname(__DIR__) . '/templates/recent-sales-table.php'; ?>
                        </div>
                        
                        <!-- Gráficos si están disponibles -->
                        <?php if (file_exists(dirname(__DIR__) . '/templates/charts-section.php')): ?>
                            <div id="wp-pos-charts-section-placeholder">
                                <?php
                                // Datos simulados para gráficos
                                $chart_data = array(
                                    'chart_sales_trend' => array(
                                        'labels' => ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                                        'data' => [1200, 1900, 1500, 2500, 2200, 3000, 2800]
                                    )
                                );
                                
                                $payment_methods_data = array(
                                    'labels' => array('Efectivo', 'Tarjeta', 'Transferencia'),
                                    'data' => array(45, 35, 20)
                                );
                                
                                $top_products_data = array(
                                    'labels' => array('Producto A', 'Producto B', 'Producto C'),
                                    'data' => array(40, 35, 25)
                                );
                                
                                include dirname(__DIR__) . '/templates/charts-section.php';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php endif; ?>
            
        <?php elseif ($current_tab === 'export' && current_user_can('wp_pos_export_reports')): ?>
            
            <!-- Tab de exportación -->
            <div class="wp-pos-export-section">
                <h2><?php _e('Exportar Reportes', 'wp-pos'); ?></h2>
                <p><?php _e('Selecciona el formato y rango de datos para exportar.', 'wp-pos'); ?></p>
                
                <!-- Aquí incluirías el contenido del tab de exportación -->
                <div class="wp-pos-export-placeholder">
                    <p><?php _e('Funcionalidad de exportación en desarrollo...', 'wp-pos'); ?></p>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'settings' && current_user_can('wp_pos_manage_reports_config')): ?>
            
            <!-- Tab de configuración -->
            <div class="wp-pos-settings-section">
                <h2><?php _e('Configuración de Reportes', 'wp-pos'); ?></h2>
                <p><?php _e('Ajusta la configuración del módulo de reportes.', 'wp-pos'); ?></p>
                
                <!-- Aquí incluirías el contenido del tab de configuración -->
                <div class="wp-pos-settings-placeholder">
                    <p><?php _e('Configuración en desarrollo...', 'wp-pos'); ?></p>
                </div>
            </div>
            
        <?php else: ?>
            
            <!-- Mensaje de permisos insuficientes -->
            <div class="wp-pos-no-permission">
                <i class="dashicons dashicons-lock"></i>
                <h2><?php _e('Acceso Denegado', 'wp-pos'); ?></h2>
                <p><?php _e('No tienes permisos para acceder a esta sección.', 'wp-pos'); ?></p>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<!-- Scripts específicos para admin-reports -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Configuración específica para admin-reports
    window.wp_pos_admin_config = {
        nonce: '<?php echo $wp_pos_nonce; ?>',
        current_tab: '<?php echo esc_js($current_tab); ?>',
        tables_exist: <?php echo $tables_exist ? 'true' : 'false'; ?>,
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        current_filters: <?php echo json_encode(array(
            'period' => $current_period,
            'role' => $current_role,
            'seller_id' => $current_seller,
            'payment_method' => $current_payment_method,
            'transaction_type' => $current_transaction,
            'date_from' => $date_from,
            'date_to' => $date_to
        )); ?>
    };
    
    // Funcionalidad específica del admin
    
    // Crear tablas si no existen
    $('.wp-pos-create-tables').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('<?php echo esc_js(__('¿Estás seguro de que quieres crear las tablas de reportes?', 'wp-pos')); ?>')) {
            var $button = $(this);
            $button.prop('disabled', true).html('<i class="dashicons dashicons-update"></i> <?php echo esc_js(__('Creando...', 'wp-pos')); ?>');
            
            // Aquí harías la petición AJAX para crear las tablas
            $.post(ajaxurl, {
                action: 'wp_pos_create_tables',
                security: window.wp_pos_admin_config.nonce
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || '<?php echo esc_js(__('Error desconocido', 'wp-pos')); ?>'));
                    $button.prop('disabled', false).html('<i class="dashicons dashicons-database-add"></i> <?php echo esc_js(__('Crear Tablas', 'wp-pos')); ?>');
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('Error de conexión', 'wp-pos')); ?>');
                $button.prop('disabled', false).html('<i class="dashicons dashicons-database-add"></i> <?php echo esc_js(__('Crear Tablas', 'wp-pos')); ?>');
            });
        }
    });
    
    // Navegación por tabs sin recarga
    $('.wp-pos-nav-tabs .nav-tab:not(.nav-tab-active)').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var url = $this.attr('href');
        
        // Actualizar URL sin recargar
        if (history.pushState) {
            history.pushState(null, null, url);
        }
        
        // Actualizar tabs activos
        $('.nav-tab').removeClass('nav-tab-active');
        $this.addClass('nav-tab-active');
        
        // Cargar contenido del tab vía AJAX
        loadTabContent(url);
    });
    
    function loadTabContent(url) {
        // Mostrar loading
        $('.wp-pos-tab-content').html('<div class="wp-pos-loading"><div class="wp-pos-spinner"></div><p><?php echo esc_js(__('Cargando...', 'wp-pos')); ?></p></div>');
        
        // Hacer petición AJAX
        $.get(url + '&ajax=1', function(data) {
            $('.wp-pos-tab-content').html(data);
        }).fail(function() {
            $('.wp-pos-tab-content').html('<div class="notice notice-error"><p><?php echo esc_js(__('Error al cargar el contenido', 'wp-pos')); ?></p></div>');
        });
    }
    
    // Logs para debugging
    if (window.console && window.console.log) {
        console.log('WP-POS Admin Reports initialized', window.wp_pos_admin_config);
    }
});
</script>

<style>
/* Estilos específicos para admin-reports */
.wp-pos-reports-admin {
    margin: 20px 0;
}

.wp-pos-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.wp-pos-header-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 13px;
}

.wp-pos-status-indicator {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.wp-pos-status-indicator.status-ok {
    background: #e8f5e9;
    color: #2e7d32;
}

.wp-pos-status-indicator.status-warning {
    background: #fff3e0;
    color: #ef6c00;
}

.wp-pos-nav-tabs {
    display: flex;
    align-items: center;
    gap: 5px;
    border-bottom: 1px solid #ccd0d4;
    margin-bottom: 20px;
}

.wp-pos-nav-tabs .nav-tab {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: none;
    background: transparent;
}

.wp-pos-nav-tabs .nav-tab.nav-tab-active {
    background: #fff;
    border-color: #ccd0d4;
}

.wp-pos-refresh-button {
    margin-left: auto;
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    padding: 6px 10px;
    border-radius: 3px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.wp-pos-setup-notice {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.wp-pos-setup-content {
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.wp-pos-setup-content i {
    font-size: 48px;
    color: #999;
    margin-bottom: 15px;
}

.wp-pos-setup-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.wp-pos-demo-preview {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #ddd;
}

.wp-pos-demo-dashboard {
    opacity: 0.7;
    pointer-events: none;
}

.wp-pos-filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.wp-pos-filter-actions-row {
    border-top: 1px solid #f0f0f1;
    padding-top: 15px;
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.wp-pos-filter-actions-left {
    display: flex;
    gap: 10px;
}

.wp-pos-no-permission {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.wp-pos-no-permission i {
    font-size: 48px;
    margin-bottom: 15px;
}

.wp-pos-loading {
    text-align: center;
    padding: 40px;
}
</style>