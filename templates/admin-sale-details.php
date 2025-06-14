<?php
/**
 * Plantilla de detalles de venta
 *
 * @package WP-POS
 * @since 2.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Incluir hojas de estilos
wp_enqueue_style('wp-pos-sales-enhanced', WP_POS_PLUGIN_URL . 'assets/css/wp-pos-sales-enhanced.css', array(), WP_POS_VERSION);
wp_enqueue_style('wp-pos-sale-details', WP_POS_PLUGIN_URL . 'assets/css/admin-sale-details.css', array(), WP_POS_VERSION);

// Incluir archivo auxiliar para manejo de clientes
require_once WP_POS_PLUGIN_DIR . 'includes/helpers/customer-helper.php';

// Cargar header con la opción 'ventas' activa en el menú
wp_pos_template_header(array(
    'title' => __('Detalles de Venta', 'wp-pos'),
    'active_menu' => 'sales'
));

// Obtener configuración
$options = wp_pos_get_option();

// Obtener ID de la venta
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0);

// DEBUG: Registrar información de la solicitud
wp_pos_debug(array(
    'sale_id' => $sale_id,
    'request' => $_GET,
    'server' => $_SERVER['REQUEST_URI']
), 'SALE_DETAILS_REQUEST');

// Acceso directo a la base de datos para obtener la venta
$sale = null;
$sale_items = array();
$sale_payments = array();

if ($sale_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pos_sales';
    
    // DEBUG: Verificar que la tabla existe
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
    wp_pos_debug($table_exists, 'SALE_DETAILS_TABLE_EXISTS_CHECK');
    
    // Primero verificar si la columna cashier_id existe en la tabla
    $cashier_column = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'cashier_id'");
    
    if ($cashier_column) {
        // Si la columna cashier_id existe, hacer el JOIN con users
        $sale = $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, u.display_name as cashier_name, u.user_email as cashier_email 
             FROM $table_name s 
             LEFT JOIN {$wpdb->users} u ON s.cashier_id = u.ID 
             WHERE s.id = %d", 
            $sale_id
        ));
    } else {
        // Si la columna no existe, obtener solo los datos de la venta
        $sale = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", 
            $sale_id
        ));
        
        // Añadir campos vacíos para mantener la compatibilidad
        if ($sale) {
            $sale->cashier_name = 'Administrador';
            $sale->cashier_email = '';
        }
    }
    
    // Inicializar arrays para items y pagos
    $sale_items = array();
    $sale_payments = array();
    
    if ($sale) {
        // 1. Obtener items de la venta desde la tabla pos_sale_items
        $items_table = $wpdb->prefix . 'pos_sale_items';
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE sale_id = %d ORDER BY id ASC", 
            $sale->id
        ), ARRAY_A);
        
        if ($items) {
            foreach ($items as $item) {
                $sale_items[] = array(
                    'id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => floatval($item['price']),
                    'quantity' => intval($item['quantity']),
                    'tax' => isset($item['tax']) ? floatval($item['tax']) : 0,
                    'total' => floatval($item['total'])
                );
            }
        }
        
        // 2. Obtener pagos de la venta
        $payments_table = $wpdb->prefix . 'pos_payments';
        
        // Primero verificar si la tabla de pagos existe
        $payments_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$payments_table'");
        
        if ($payments_table_exists) {
            // Si la tabla de pagos existe, intentar obtener los pagos
            $payments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $payments_table WHERE sale_id = %d ORDER BY id ASC", 
                $sale->id
            ), ARRAY_A);
            
            if ($payments) {
                $sale_payments = array();
                foreach ($payments as $payment) {
                    $method_name = $payment['payment_method'];
                    
                    // Si el payment_method es numérico, asumimos que es un ID de post
                    if (is_numeric($payment['payment_method'])) {
                        $method_name = get_the_title($payment['payment_method']);
                        if (empty($method_name)) {
                            $method_name = 'Método ' . $payment['payment_method'];
                        }
                    }
                    
                    $sale_payments[] = array(
                        'method' => $method_name,
                        'reference' => !empty($payment['transaction_id']) ? $payment['transaction_id'] : '-',
                        'amount' => floatval($payment['amount']),
                        'date' => !empty($payment['payment_date']) ? $payment['payment_date'] : $sale->date_created
                    );
                }
            } else if ($sale->status === 'completed' && $sale->total > 0) {
                // Si no hay pagos pero la venta está completada, asumir pago en efectivo
                $sale_payments = array(array(
                    'method' => 'Efectivo',
                    'reference' => 'N/A',
                    'amount' => floatval($sale->total),
                    'date' => $sale->date_created
                ));
            }
        } else if ($sale->status === 'completed' && $sale->total > 0) {
            // Si la tabla de pagos no existe pero la venta está completada, asumir pago en efectivo
            $sale_payments = array(array(
                'method' => 'Efectivo',
                'reference' => 'N/A',
                'amount' => floatval($sale->total),
                'date' => $sale->date_created
            ));
        }
        
        // DEBUG: Registrar datos de la venta
        error_log('=== DATOS DE VENTA ===');
        error_log('ID: ' . $sale->id);
        error_log('Fecha: ' . $sale->date);
        error_log('Total: ' . $sale->total);
        error_log('Cliente ID: ' . $sale->customer_id);
        error_log('Estado: ' . $sale->status);
        error_log('Items: ' . print_r($sale_items, true));
        error_log('Pagos: ' . print_r($sale_payments, true));
        
        // DEBUG: Registrar items y pagos
        wp_pos_debug($sale_items, 'SALE_DETAILS_ITEMS');
        wp_pos_debug($sale_payments, 'SALE_DETAILS_PAYMENTS');
    }
}

// Si no existe la venta, redirigir a la lista
if (!$sale) {
    wp_redirect(admin_url('admin.php?page=wp-pos-sales'));
    exit;
}

// Configurar URLs de acciones
$back_url = admin_url('admin.php?page=wp-pos-sales');
// Utilizar la página independiente para la impresión de recibos
$print_url = plugins_url('/modules/receipts/receipt-standalone.php?id=' . $sale_id, WP_POS_PLUGIN_FILE);
$cancel_url = wp_nonce_url(admin_url('admin.php?page=wp-pos-sales&action=cancel&sale_id=' . $sale_id), 'wp-pos-cancel-sale');
$delete_url = wp_nonce_url(admin_url('admin.php?page=wp-pos-sales&action=delete&sale_id=' . $sale_id), 'wp_pos_delete_sale_' . $sale_id);

// Loguear URLs para depuración
$action_urls = array(
    'back_url' => $back_url,
    'print_url' => $print_url,
    'cancel_url' => $cancel_url,
    'delete_url' => $delete_url
);
wp_pos_log('SALE_DETAILS_ACTION_URLS: ' . print_r($action_urls, true));
?>




<div class="wp-pos-admin-wrapper wp-pos-sale-details-wrapper">


    <!-- Cabecera personalizada con los colores del sistema -->
    <div class="wp-pos-page-header">
        <div class="wp-pos-page-title">
            <h1><?php printf(__('Detalles de Venta #%s', 'wp-pos'), esc_html($sale->id)); ?></h1>
        </div>
        <div class="wp-pos-page-actions">
            <a href="<?php echo esc_url($back_url); ?>" class="wp-pos-button wp-pos-button-secondary">
                <i class="dashicons dashicons-arrow-left-alt"></i>
                <?php _e('Volver a ventas', 'wp-pos'); ?>
            </a>
            <a href="<?php echo esc_url($print_url); ?>" class="wp-pos-button wp-pos-button-primary" target="_blank">
                <i class="dashicons dashicons-printer"></i>
                <?php _e('Imprimir recibo', 'wp-pos'); ?>
            </a>
        </div>
    </div>

    <!-- Panel con datos principales -->
    <div class="wp-pos-sale-header">
        <div class="wp-pos-sale-meta">
            <span class="wp-pos-sale-date">
                <i class="dashicons dashicons-calendar-alt"></i>
                <?php 
                // Usar date_created si date está vacío o es inválido
                $sale_date = (!empty($sale->date) && $sale->date !== '0000-00-00 00:00:00') 
                    ? $sale->date 
                    : $sale->date_created;
                
                // Formatear la fecha
                $formatted_date = date_i18n(
                    get_option('date_format') . ' ' . get_option('time_format'), 
                    strtotime($sale_date)
                );
                
                echo esc_html($formatted_date); 
                ?>
            </span>
            <span class="wp-pos-status wp-pos-status-<?php echo sanitize_html_class(strtolower($sale->status)); ?>">
                <?php echo esc_html(wp_pos_get_sale_status_label($sale->status)); ?>
            </span>
        </div>
    </div>
    
    <!-- Información rápida en tarjetas resumen -->
    <div class="wp-pos-sale-summary">
        <div class="wp-pos-card">
            <div class="wp-pos-card-title">
                <i class="dashicons dashicons-businessman"></i>
                <?php _e('Cliente', 'wp-pos'); ?>
            </div>
            <div class="wp-pos-card-content">
                <?php 
                $customer_name = '';
                $customer_phone = '';
                $customer_email = '';
                
                // Obtener datos del cliente si existe el ID
                if (!empty($sale->customer_id) && $sale->customer_id > 0) {
                    $customer = get_user_by('id', $sale->customer_id);
                    if ($customer) {
                        // Construir el nombre completo del cliente
                        $display_name = trim($customer->first_name . ' ' . $customer->last_name);
                        $customer_name = !empty(trim($display_name)) ? $display_name : $customer->display_name;
                        $customer_email = $customer->user_email;
                        $customer_phone = get_user_meta($sale->customer_id, 'billing_phone', true);
                    }
                } 
                
                // Si no hay nombre de cliente, usar el genérico
                if (empty($customer_name)) {
                    $customer_name = !empty($sale->customer_name) ? $sale->customer_name : __('Cliente no especificado', 'wp-pos');
                }
                
                echo esc_html($customer_name);
                ?>
                <div class="wp-pos-card-meta">
                    <?php if (!empty($customer_phone)): ?>
                    <div><i class="dashicons dashicons-phone"></i> <?php echo esc_html($customer_phone); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($customer_email)): ?>
                    <div><i class="dashicons dashicons-email-alt"></i> <?php echo esc_html($customer_email); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="wp-pos-card">
            <div class="wp-pos-card-title">
                <i class="dashicons dashicons-money-alt"></i>
                <?php _e('Total', 'wp-pos'); ?>
            </div>
            <div class="wp-pos-card-content wp-pos-total-amount">
                <?php echo wp_pos_format_price($sale->total); ?>
                <?php if (isset($sale->discount) && floatval($sale->discount) > 0): ?>
                <div class="wp-pos-card-meta">
                    <div><?php _e('Descuento aplicado:', 'wp-pos'); ?> <?php echo wp_pos_format_price($sale->discount); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="wp-pos-card">
            <div class="wp-pos-card-title">
                <i class="dashicons dashicons-id-alt"></i>
                <?php _e('Vendedor', 'wp-pos'); ?>
            </div>
            <div class="wp-pos-card-content">
                <?php echo esc_html(isset($sale->cashier_name) && $sale->cashier_name ? $sale->cashier_name : '-'); ?>
                <?php if (isset($sale->note) && !empty($sale->note)): ?>
                <div class="wp-pos-card-meta">
                    <div><i class="dashicons dashicons-edit"></i> <?php echo esc_html($sale->note); ?></div>
                </div>
                <?php elseif (isset($sale->notes) && !empty($sale->notes)): /* Compatibilidad con versiones anteriores que usaban 'notes' en lugar de 'note' */ ?>
                <div class="wp-pos-card-meta">
                    <div><i class="dashicons dashicons-edit"></i> <?php echo esc_html($sale->notes); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Productos vendidos -->
    <div class="wp-pos-sale-section">
        <div class="wp-pos-section-header">
            <h3>
                <i class="dashicons dashicons-products"></i>
                <?php _e('Productos vendidos', 'wp-pos'); ?>
            </h3>
        </div>
        
        <?php if (!empty($sale_items) && is_array($sale_items)): ?>
        <div class="wp-pos-table-container">
            <table class="wp-pos-sale-items-table">
                <thead>
                    <tr>
                        <th><?php _e('Producto', 'wp-pos'); ?></th>
                        <th><?php _e('Precio', 'wp-pos'); ?></th>
                        <th><?php _e('Cantidad', 'wp-pos'); ?></th>
                        <th><?php _e('Subtotal', 'wp-pos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach ($sale_items as $item): 
                        if (!is_array($item) || empty($item['price']) || empty($item['quantity'])) {
                            continue; // Saltar ítems inválidos
                        }
                        
                        $item_price = floatval($item['price']);
                        $item_quantity = intval($item['quantity']);
                        $item_subtotal = $item_price * $item_quantity;
                        $subtotal += $item_subtotal;
                        
                        $item_name = !empty($item['name']) ? $item['name'] : __('Producto sin nombre', 'wp-pos');
                    ?>
                    <tr>
                        <td><?php echo esc_html($item_name); ?></td>
                        <td class="text-right"><?php echo wp_pos_format_price($item_price); ?></td>
                        <td class="text-center"><?php echo $item_quantity; ?></td>
                        <td class="text-right"><?php echo wp_pos_format_price($item_subtotal); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="wp-pos-sale-totals">
            <div class="wp-pos-totals-row">
                <div class="wp-pos-totals-label"><?php _e('Subtotal', 'wp-pos'); ?></div>
                <div class="wp-pos-totals-value"><?php echo wp_pos_format_price($subtotal); ?></div>
            </div>
            
            <?php if (isset($sale->discount) && floatval($sale->discount) > 0): ?>
            <div class="wp-pos-totals-row">
                <div class="wp-pos-totals-label"><?php _e('Descuento', 'wp-pos'); ?></div>
                <div class="wp-pos-totals-value">-<?php echo wp_pos_format_price($sale->discount); ?></div>
            </div>
            <?php endif; ?>
            
            <?php 
            // Verificar impuestos, con compatibilidad para distintas propiedades
            $tax_amount = 0;
            if (isset($sale->tax)) {
                $tax_amount = floatval($sale->tax);
            } elseif (isset($sale->tax_total)) {
                $tax_amount = floatval($sale->tax_total);
            }
            
            if ($tax_amount > 0): 
            ?>
            <div class="wp-pos-totals-row">
                <div class="wp-pos-totals-label"><?php _e('Impuestos', 'wp-pos'); ?></div>
                <div class="wp-pos-totals-value"><?php echo wp_pos_format_price($tax_amount); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="wp-pos-totals-row wp-pos-totals-final">
                <div class="wp-pos-totals-label"><?php _e('Total', 'wp-pos'); ?></div>
                <div class="wp-pos-totals-value"><?php echo wp_pos_format_price($sale->total); ?></div>
            </div>
        </div>
        <?php else: ?>
        <div class="wp-pos-empty-state">
            <i class="dashicons dashicons-info-outline"></i>
            <p><?php _e('No hay productos en esta venta.', 'wp-pos'); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagos -->
    <div class="wp-pos-sale-section">
        <div class="wp-pos-section-header">
            <h3>
                <i class="dashicons dashicons-money"></i>
                <?php _e('Pagos', 'wp-pos'); ?>
            </h3>
        </div>
        
        <?php 
        // Si no hay pagos pero la venta está completada, mostrar el pago total
        if (empty($sale_payments) && $sale->status === 'completed' && $sale->total > 0) {
            $sale_payments = array(array(
                'method' => __('Efectivo', 'wp-pos'),
                'reference' => '',
                'amount' => $sale->total
            ));
        }
        
        if (!empty($sale_payments) && is_array($sale_payments)): 
        ?>
        <div class="wp-pos-table-container">
            <table class="wp-pos-sale-items-table">
                <thead>
                    <tr>
                        <th><?php _e('Método', 'wp-pos'); ?></th>
                        <th><?php _e('Referencia', 'wp-pos'); ?></th>
                        <th class="text-right"><?php _e('Monto', 'wp-pos'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_paid = 0;
                    foreach ($sale_payments as $payment): 
                        if (!is_array($payment)) continue;
                        
                        $method = !empty($payment['method']) ? $payment['method'] : __('No especificado', 'wp-pos');
                        $reference = !empty($payment['reference']) ? $payment['reference'] : '-';
                        $amount = !empty($payment['amount']) ? floatval($payment['amount']) : 0;
                        $total_paid += $amount;
                    ?>
                    <tr>
                        <td><?php echo esc_html($method); ?></td>
                        <td><?php echo esc_html($reference); ?></td>
                        <td class="text-right"><?php echo wp_pos_format_price($amount); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if ($total_paid < $sale->total): ?>
                    <tr>
                        <td colspan="2"><strong><?php _e('Pendiente de pago', 'wp-pos'); ?></strong></td>
                        <td class="text-right"><?php echo wp_pos_format_price($sale->total - $total_paid); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="wp-pos-empty-state">
            <i class="dashicons dashicons-info-outline"></i>
            <p><?php _e('No hay pagos registrados para esta venta.', 'wp-pos'); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Acciones -->
    <div class="wp-pos-sale-section">
        <div class="wp-pos-section-header">
            <h3>
                <i class="dashicons dashicons-admin-tools"></i>
                <?php _e('Acciones', 'wp-pos'); ?>
            </h3>
        </div>
        
        <div class="wp-pos-action-container">
            <div class="wp-pos-action-buttons">
                <?php if ($sale->status !== 'cancelled'): ?>
                <a href="<?php echo esc_url($cancel_url); ?>" class="wp-pos-button wp-pos-button-warning" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que deseas cancelar esta venta?', 'wp-pos'); ?>')">
                    <i class="dashicons dashicons-no-alt"></i>
                    <?php _e('Cancelar venta', 'wp-pos'); ?>
                </a>
                <?php endif; ?>
                
                <a href="<?php echo esc_url($delete_url); ?>" class="wp-pos-button wp-pos-button-danger" onclick="return confirm('<?php esc_attr_e('¿Estás seguro de que deseas eliminar esta venta? Esta acción no se puede deshacer.', 'wp-pos'); ?>')">
                    <i class="dashicons dashicons-trash"></i>
                    <?php _e('Eliminar', 'wp-pos'); ?>
                </a>
            </div>
        </div>
    </div>
        
        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <!-- Sección de depuración -->
        <div class="wp-pos-debug-container">
            <h3><?php _e('Información de depuración', 'wp-pos'); ?></h3>
            <div>
                <strong>ID de venta:</strong> <?php echo esc_html($sale_id); ?><br>
                <strong>Nonce cancelar:</strong> <?php echo esc_html(wp_create_nonce('wp_pos_cancel_sale_' . $sale_id)); ?><br>
                <strong>Nonce eliminar:</strong> <?php echo esc_html(wp_create_nonce('wp_pos_delete_sale_' . $sale_id)); ?><br>
                <strong>URL cancelar:</strong> <?php echo esc_html($cancel_url); ?><br>
                <strong>URL eliminar:</strong> <?php echo esc_html($delete_url); ?><br>
            </div>
        </div>
        <?php endif; ?>

</div>

<?php
// Cargar footer
wp_pos_template_footer();
