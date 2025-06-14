<?php
/**
 * Pu00e1gina principal del Dashboard de WP-POS
 *
 * @package WP-POS
 * @subpackage Dashboard
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener estadu00edsticas Rápidas
global $wpdb;

$tabla_ventas = $wpdb->prefix . 'pos_sales';
$tabla_productos = $wpdb->prefix . 'pos_products';

// Total de ventas
$total_ventas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_ventas");

// Ingreso total
$total_ingresos = $wpdb->get_var("SELECT SUM(total) FROM $tabla_ventas");
$total_ingresos = $total_ingresos ? $total_ingresos : 0;

// Total de productos
$total_productos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_productos");

// Total de clientes (usuarios con rol pos_customer)
$args = array(
    'role__in' => array('pos_customer'),
    'count_total' => true,
    'fields' => 'ID',
);
$usuarios_query = new WP_User_Query($args);
$total_clientes = $usuarios_query->get_total();

// Ventas recientes (usando date_created en lugar de date)
$ventas_recientes = $wpdb->get_results("SELECT id, customer_id, date_created AS date, total FROM $tabla_ventas ORDER BY date_created DESC LIMIT 5");

// Obtener nombres de clientes
$clientes = array();
if (!empty($ventas_recientes)) {
    $cliente_ids = array_map(function($venta) {
        return $venta->customer_id;
    }, $ventas_recientes);
    
    $cliente_ids = array_unique($cliente_ids);
    
    if (!empty($cliente_ids)) {
        $args = array(
            'include' => $cliente_ids,
            'fields' => array('ID', 'display_name'),
        );
        $clientes_query = new WP_User_Query($args);
        $clientes_obtenidos = $clientes_query->get_results();
        
        foreach ($clientes_obtenidos as $cliente) {
            $clientes[$cliente->ID] = $cliente->display_name;
        }
    }
}

// Mostrar mensaje de bienvenida
$show_welcome = isset($_GET['welcome']);
?>

<div class="wrap">
    <!-- Header con degradado exactamente como en Nueva Venta -->
    <div class="wp-pos-header">
        <h1><span class="dashicons dashicons-dashboard"></span> <?php _e('Panel de Control', 'wp-pos'); ?></h1>
        <p><?php _e('Bienvenido al punto de venta. Accede rápidamente a todas las funciones con iconos o atajos de teclado.', 'wp-pos'); ?></p>
    </div>
    
    <div class="wp-pos-container">
        <?php if ($show_welcome): ?>
        <!-- Panel de bienvenida moderno -->
        <div class="wp-pos-welcome-panel">
            <div class="wp-pos-welcome-close"><span class="dashicons dashicons-no-alt"></span></div>
            <div class="wp-pos-welcome-content">
                <div class="wp-pos-welcome-image">
                    <span class="dashicons dashicons-store"></span>
                </div>
                <div class="wp-pos-welcome-info">
                    <h2><?php _e('¡Bienvenido a tu Punto de Venta!', 'wp-pos'); ?></h2>
                    <p><?php _e('Comienza a gestionar tu negocio de forma rápida y sencilla con estas opciones:', 'wp-pos'); ?></p>
                    
                    <div class="wp-pos-welcome-actions">
                        <a href="<?php echo admin_url('admin.php?page=wp-pos-products&action=new'); ?>" class="wp-pos-welcome-action">
                            <div class="wp-pos-welcome-action-icon wp-pos-products">
                                <span class="dashicons dashicons-plus"></span>
                            </div>
                            <div class="wp-pos-welcome-action-text">
                                <span class="wp-pos-welcome-action-title"><?php _e('Crear producto', 'wp-pos'); ?></span>
                                <span class="wp-pos-welcome-action-desc"><?php _e('Añade nuevos productos a tu inventario', 'wp-pos'); ?></span>
                            </div>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=wp-pos-customers&action=new'); ?>" class="wp-pos-welcome-action">
                            <div class="wp-pos-welcome-action-icon wp-pos-customers">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                            <div class="wp-pos-welcome-action-text">
                                <span class="wp-pos-welcome-action-title"><?php _e('Registrar cliente', 'wp-pos'); ?></span>
                                <span class="wp-pos-welcome-action-desc"><?php _e('Gestiona tu base de clientes', 'wp-pos'); ?></span>
                            </div>
                        </a>
                        
                        <a href="<?php echo admin_url('admin.php?page=wp-pos-new-sale-v2'); ?>" class="wp-pos-welcome-action">
                            <div class="wp-pos-welcome-action-icon wp-pos-sales">
                                <span class="dashicons dashicons-cart"></span>
                            </div>
                            <div class="wp-pos-welcome-action-text">
                                <span class="wp-pos-welcome-action-title"><?php _e('Realizar venta', 'wp-pos'); ?></span>
                                <span class="wp-pos-welcome-action-desc"><?php _e('Crea una nueva transacción', 'wp-pos'); ?></span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Estado general del sistema en tarjetas atractivas -->
        <div class="wp-pos-stats-cards">
            <div class="wp-pos-stat-card wp-pos-stat-sales">
                <div class="wp-pos-stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="wp-pos-stat-content">
                    <div class="wp-pos-stat-value"><?php echo number_format($total_ventas); ?></div>
                    <div class="wp-pos-stat-label"><?php _e('Ventas', 'wp-pos'); ?></div>
                </div>
            </div>
            
            <div class="wp-pos-stat-card wp-pos-stat-revenue">
                <div class="wp-pos-stat-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="wp-pos-stat-content">
                    <div class="wp-pos-stat-value">$<?php echo number_format($total_ingresos, 2); ?></div>
                    <div class="wp-pos-stat-label"><?php _e('Ingresos', 'wp-pos'); ?></div>
                </div>
            </div>
            
            <div class="wp-pos-stat-card wp-pos-stat-products">
                <div class="wp-pos-stat-icon">
                    <span class="dashicons dashicons-tag"></span>
                </div>
                <div class="wp-pos-stat-content">
                    <div class="wp-pos-stat-value"><?php echo number_format($total_productos); ?></div>
                    <div class="wp-pos-stat-label"><?php _e('Productos', 'wp-pos'); ?></div>
                </div>
            </div>
            
            <div class="wp-pos-stat-card wp-pos-stat-customers">
                <div class="wp-pos-stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="wp-pos-stat-content">
                    <div class="wp-pos-stat-value"><?php echo number_format($total_clientes); ?></div>
                    <div class="wp-pos-stat-label"><?php _e('Clientes', 'wp-pos'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Acceso rápido mediante iconos atractivos con atajos de teclado -->
        <div class="wp-pos-quick-actions">
            <h2 class="wp-pos-section-title"><?php _e('Acciones Rápidas', 'wp-pos'); ?> <span class="wp-pos-keyboard-hint"><?php _e('(Usa teclas numéricas 1-6 para acceso rápido)', 'wp-pos'); ?></span></h2>
            
            <div class="wp-pos-action-grid">
                <a href="<?php echo admin_url('admin.php?page=wp-pos-new-sale-v2'); ?>" class="wp-pos-action-button" data-shortcut="1">
                    <div class="wp-pos-action-icon">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <div class="wp-pos-action-label"><?php _e('Nueva Venta', 'wp-pos'); ?></div>
                    <div class="wp-pos-action-shortcut">1</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=wp-pos-products'); ?>" class="wp-pos-action-button" data-shortcut="2">
                    <div class="wp-pos-action-icon">
                        <span class="dashicons dashicons-tag"></span>
                    </div>
                    <div class="wp-pos-action-label"><?php _e('Productos', 'wp-pos'); ?></div>
                    <div class="wp-pos-action-shortcut">2</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=wp-pos-customers'); ?>" class="wp-pos-action-button" data-shortcut="3">
                    <div class="wp-pos-action-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="wp-pos-action-label"><?php _e('Clientes', 'wp-pos'); ?></div>
                    <div class="wp-pos-action-shortcut">3</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=wp-pos-products&action=new'); ?>" class="wp-pos-action-button" data-shortcut="4">
                    <div class="wp-pos-action-icon">
                        <span class="dashicons dashicons-plus-alt"></span>
                    </div>
                    <div class="wp-pos-action-label"><?php _e('Nuevo Producto', 'wp-pos'); ?></div>
                    <div class="wp-pos-action-shortcut">4</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=wp-pos-reports'); ?>" class="wp-pos-action-button" data-shortcut="5">
                    <div class="wp-pos-action-icon">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <div class="wp-pos-action-label"><?php _e('Reportes', 'wp-pos'); ?></div>
                    <div class="wp-pos-action-shortcut">5</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=wp-pos-settings'); ?>" class="wp-pos-action-button" data-shortcut="6">
                    <div class="wp-pos-action-icon">
                        <span class="dashicons dashicons-admin-settings"></span>
                    </div>
                    <div class="wp-pos-action-label"><?php _e('Configuración', 'wp-pos'); ?></div>
                    <div class="wp-pos-action-shortcut">6</div>
                </a>
            </div>
        </div>
        
        <!-- Panel de notificaciones de stock bajo -->
        <div class="wp-pos-stock-notifications">
            <h2 class="wp-pos-section-title"><?php _e('Notificaciones de Inventario', 'wp-pos'); ?></h2>
            
            <style>
            .wp-pos-alert {
                border-radius: 6px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                background-color: #fff;
                margin-bottom: 15px;
            }
            
            .wp-pos-alert-header {
                display: flex;
                align-items: center;
                padding: 12px 15px;
                color: #fff;
            }
            
            .wp-pos-alert-warning .wp-pos-alert-header {
                background: linear-gradient(135deg, #f39c12, #e67e22);
            }
            
            .wp-pos-alert-success .wp-pos-alert-header {
                background: linear-gradient(135deg, #2ecc71, #27ae60);
            }
            
            .wp-pos-alert-icon {
                margin-right: 12px;
            }
            
            .wp-pos-alert-icon .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
            }
            
            .wp-pos-alert-title {
                font-size: 16px;
                font-weight: bold;
                flex-grow: 1;
            }
            
            .wp-pos-alert-info {
                font-size: 13px;
                opacity: 0.9;
            }
            
            .wp-pos-alert-content {
                padding: 15px;
            }
            
            .wp-pos-stock-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .wp-pos-stock-table th {
                text-align: left;
                background-color: #f5f5f5;
                padding: 10px;
                border-bottom: 1px solid #ddd;
            }
            
            .wp-pos-stock-table td {
                padding: 8px 10px;
                border-bottom: 1px solid #eee;
            }
            
            .wp-pos-product-name {
                font-weight: 500;
            }
            
            .wp-pos-stock-level {
                color: #e74c3c;
                font-weight: bold;
            }
            
            .wp-pos-alert-actions {
                padding: 10px 15px;
                background-color: #f9f9f9;
                border-top: 1px solid #eee;
                text-align: right;
            }
            </style>
            
            <?php
            // Obtener umbral configurado
            $options = wp_pos_get_option();
            $threshold = isset($options['low_stock_threshold']) ? (int)$options['low_stock_threshold'] : 5;
            
            // Consulta mejorada para siempre capturar productos con stock bajo
            global $wpdb;
            $tabla_productos = $wpdb->prefix . 'pos_products';
            
            // Obtener todos los productos para verificar stock
            $todos_productos = $wpdb->get_results("SELECT * FROM $tabla_productos LIMIT 20");
            
            // Si tenemos productos, crear array con productos que deberían estar por debajo del umbral
            $low_stock_products = array();
            
            if (!empty($todos_productos)) {
                foreach ($todos_productos as $producto) {
                    // Revisamos cada producto

                    
                    // Convertimos explícitamente a entero
                    $stock = (int)$producto->stock_quantity;
                    
                    // Condición: stock mayor que 0 y menor o igual que umbral
                    // SOLO agregamos productos que realmente tienen stock bajo
                    if ($stock > 0 && $stock <= $threshold) {
                        $low_stock_products[] = $producto;
                    }
                    
                    // Quitamos la sección de forzado que causaba el problema
                }
            }
            
            // Verificar si tenemos productos con stock bajo
            if (!empty($low_stock_products)) {
                ?>
                <div class="wp-pos-alert wp-pos-alert-warning">
                    <div class="wp-pos-alert-header" style="background-color: #88263f;">
                        <div class="wp-pos-alert-icon">
                            <span class="dashicons dashicons-warning"></span>
                        </div>
                        <div class="wp-pos-alert-title">
                            <strong>Notificaciones de Inventario</strong>
                        </div>
                        <div class="wp-pos-alert-info">
                            <?php echo count($low_stock_products) . ' producto' . (count($low_stock_products) > 1 ? 's' : '') . ' con stock bajo'; ?> (Umbral: <?php echo $threshold; ?> unidades)
                        </div>
                    </div>
                    
                    <div class="wp-pos-alert-content">
                        <table class="wp-pos-table wp-pos-stock-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                <tr>
                                    <td><?php echo esc_html($product->id); ?></td>
                                    <td class="wp-pos-product-name"><?php echo esc_html($product->name); ?></td>
                                    <td><?php echo esc_html($product->sku); ?></td>
                                    <td class="wp-pos-stock-level"><?php echo esc_html($product->stock_quantity); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=wp-pos-products&action=edit&product_id=' . $product->id); ?>" class="button button-small">
                                            <span class="dashicons dashicons-edit"></span>
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="wp-pos-alert-actions">
                        <a href="<?php echo admin_url('admin.php?page=wp-pos-products'); ?>" class="button">
                            <span class="dashicons dashicons-products"></span>
                            Gestionar productos
                        </a>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <div class="wp-pos-alert wp-pos-alert-success">
                    <div class="wp-pos-alert-header">
                        <div class="wp-pos-alert-icon">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </div>
                        <div class="wp-pos-alert-title">
                            Inventario en buen estado
                        </div>
                        <div class="wp-pos-alert-info">
                            Todos los productos tienen stock por encima de <?php echo $threshold; ?> unidades
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        
        <!-- Sección para cumpleaños de clientes -->
<?php
    // Sección para cumpleaños de clientes
    global $wpdb;
    
    // Utilizamos el controlador de clientes existente
    if (!class_exists('WP_POS_Customers_Controller')) {
        require_once WP_POS_PLUGIN_DIR . 'modules/customers/controllers/class-pos-customers-controller.php';
    }
    
    // Inicializar el controlador de clientes
    $customers_controller = new WP_POS_Customers_Controller();
    
    // Buscamos todos los clientes 
    $todos_clientes = $customers_controller->search_customers([
        'per_page' => 50 // Obtenemos suficientes para buscar cumpleaños
    ]);
    
    // Array para almacenar clientes con cumpleaños hoy
    $clientes_con_cumple = array();
    
    // Campo correcto donde se almacena la fecha de nacimiento
    $campo_nacimiento = 'birth_date';
    
    // Iteramos por los clientes y verificamos sus fechas de nacimiento
    if (!empty($todos_clientes['customers'])) {
        foreach ($todos_clientes['customers'] as $cliente) {
            // Obtenemos la fecha de nacimiento del campo correcto
            $birthdate = get_user_meta($cliente['id'], $campo_nacimiento, true);
            
            // Si el cliente tiene fecha de nacimiento
            if (!empty($birthdate)) {
                // Procesamos la fecha para obtener mes y día
                $fecha_obj = date_create($birthdate);
                if ($fecha_obj) {
                    $cliente_mes = date_format($fecha_obj, 'm');
                    $cliente_dia = date_format($fecha_obj, 'd');
                    
                    // Obtenemos la fecha actual para comparar
                    $mes_actual = date('m');
                    $dia_actual = date('d');
                    
                    // Verificamos si el cliente cumple años hoy
                    if ($cliente_mes == $mes_actual && $cliente_dia == $dia_actual) {
                        // Agregamos a la lista de cumpleaños
                        $clientes_con_cumple[] = (object)[
                            'id' => $cliente['id'],
                            'name' => $cliente['full_name'],
                            'email' => $cliente['email'],
                            'phone' => isset($cliente['billing']['phone']) ? $cliente['billing']['phone'] : '',
                            'birthdate' => $birthdate
                        ];
                    }
                }
            }
        }
    }
    
    // Si hay clientes que cumplen años hoy, mostrar notificación
    if (!empty($clientes_con_cumple)) {
        ?>
        <div class="wp-pos-alert wp-pos-alert-info">
            <div class="wp-pos-alert-header" style="background-color: #64415f;">
                <div class="wp-pos-alert-icon">
                    <span class="dashicons dashicons-cake"></span>
                </div>
                <div class="wp-pos-alert-title">
                    <strong>Cumpleaños de Clientes</strong>
                </div>
                <div class="wp-pos-alert-info">
                    <?php echo count($clientes_con_cumple) . ' cliente' . (count($clientes_con_cumple) > 1 ? 's' : '') . ' cumplen años hoy'; ?>
                </div>
            </div>
            <div class="wp-pos-alert-content">
                <table class="wp-pos-table wp-pos-birthday-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes_con_cumple as $cliente): ?>
                        <tr>
                            <td><?php echo esc_html($cliente->id); ?></td>
                            <td class="wp-pos-customer-name"><?php echo esc_html($cliente->name); ?></td>
                            <td><?php echo esc_html($cliente->email); ?></td>
                            <td><?php echo esc_html($cliente->phone); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wp-pos-customers&action=edit&customer_id=' . $cliente->id); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit"></span>
                                    Editar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="wp-pos-alert-actions">
                <a href="<?php echo admin_url('admin.php?page=wp-pos-customers'); ?>" class="button">
                    <span class="dashicons dashicons-groups"></span>
                    Gestionar clientes
                </a>
            </div>
        </div>
        <?php
            }
        ?>
        
        <!-- Actividad reciente con diseño mejorado -->
        <div class="wp-pos-recent-activity">
            <h2 class="wp-pos-section-title"><?php _e('Actividad Reciente', 'wp-pos'); ?></h2>
            
            <?php if (empty($ventas_recientes)): ?>
                <div class="wp-pos-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php _e('No hay ventas recientes para mostrar', 'wp-pos'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=wp-pos-new-sale-v2'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-cart"></span> <?php _e('Realizar una venta', 'wp-pos'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="wp-pos-recent-sales">
                    <?php foreach ($ventas_recientes as $venta): ?>
                        <div class="wp-pos-recent-sale-item">
                            <div class="wp-pos-sale-info">
                                <div class="wp-pos-sale-id">#<?php echo $venta->id; ?></div>
                                <div class="wp-pos-sale-customer"><?php echo isset($clientes[$venta->customer_id]) ? esc_html($clientes[$venta->customer_id]) : __('Cliente', 'wp-pos'); ?></div>
                                <div class="wp-pos-sale-date"><?php echo date('d/m/Y H:i', strtotime($venta->date)); ?></div>
                            </div>
                            <div class="wp-pos-sale-amount">$<?php echo number_format($venta->total, 2); ?></div>
                            <a href="<?php echo admin_url('admin.php?page=wp-pos-sales&action=view&id=' . $venta->id); ?>" class="wp-pos-sale-view">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="wp-pos-view-all">
                    <a href="<?php echo admin_url('admin.php?page=wp-pos-sales'); ?>" class="wp-pos-view-all-button">
                        <span class="wp-pos-view-all-icon">
                            <span class="dashicons dashicons-list-view"></span>
                        </span>
                        <span class="wp-pos-view-all-text"><?php _e('Ver todas las ventas', 'wp-pos'); ?></span>
                        <span class="wp-pos-view-all-arrow">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
