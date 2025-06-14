<?php
/**
 * Página de impresión de recibos independiente
 * Esta página no depende del flujo normal de WordPress para evitar problemas de encabezados
 *
 * @package WP-POS
 * @subpackage Receipts
 */

// Cargar WordPress sin iniciar el tema
define('WP_USE_THEMES', false);

// Encontrar la ruta de WordPress de manera más robusta
$wp_load_file = false;

// Método 1: Buscar en directorios superiores
$path = dirname(__FILE__);
$max_levels = 10; // Evitar bucle infinito
$level = 0;

while (!$wp_load_file && $level < $max_levels) {
    $level++;
    $path = dirname($path);
    if (file_exists($path . '/wp-load.php')) {
        $wp_load_file = $path . '/wp-load.php';
    }
}

// Método 2: Usar la constante ABSPATH si ya está definida
if (!$wp_load_file && defined('ABSPATH')) {
    if (file_exists(ABSPATH . 'wp-load.php')) {
        $wp_load_file = ABSPATH . 'wp-load.php';
    }
}

// Si aún no encontramos el archivo, intentar con una ruta directa basada en la estructura común
if (!$wp_load_file) {
    $possible_paths = array(
        dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php',  // Desde plugins/mi-plugin/carpeta/subcarpeta
        dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php',  // Desde plugins/mi-plugin/carpeta
        dirname(dirname(dirname(__FILE__))) . '/wp-load.php',  // Desde plugins/mi-plugin
        dirname(dirname(__FILE__)) . '/wp-load.php',  // Desde plugins
    );
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $wp_load_file = $path;
            break;
        }
    }
}

// Si no podemos encontrar wp-load.php, mostrar un error
if (!$wp_load_file) {
    die('No se puede cargar WordPress. Por favor, contacte al administrador del sitio.');
}

// Cargar WordPress
require_once($wp_load_file);

// Verificar que el usuario tiene permisos
if (!current_user_can('access_pos') && !current_user_can('manage_options')) {
    wp_die(__('No tienes permiso para acceder a esta página.', 'wp-pos'));
}

// Verificar tablas necesarias
global $wpdb;
$required_tables = [
    $wpdb->prefix . 'pos_sales',
    $wpdb->prefix . 'pos_sale_items',
    $wpdb->prefix . 'pos_products'
];

foreach ($required_tables as $table) {
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        wp_die(sprintf(
            __('Error: La tabla %s no existe. Por favor, activa el plugin G-POS correctamente.', 'wp-pos'),
            $table
        ));
    }
}

// Verificar ID de venta
$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$sale_id) {
    wp_die(__('Error: Se requiere un ID de venta válido en la URL (ej: ?id=123)', 'wp-pos'));
}

// Verificar si la venta existe
$sale_exists = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}pos_sales WHERE id = %d",
    $sale_id
));

if (!$sale_exists) {
    wp_die(sprintf(
        __('Error: No se encontró la venta con ID %d', 'wp-pos'),
        $sale_id
    ));
}

// Añadir log para depuración
error_log("[WP-POS] Renderizando recibo independiente para venta ID: {$sale_id}");

// Obtener datos de la venta directamente sin depender del módulo
global $wpdb;
$table_name = $wpdb->prefix . 'pos_sales';

// Verificar si la tabla existe
if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    wp_die(__('Error en la base de datos: tabla de ventas no encontrada', 'wp-pos'));
}

// Obtener venta con todos los campos necesarios
$query = $wpdb->prepare(
    "SELECT s.*, 
    GROUP_CONCAT(si.product_id) as product_ids,
    GROUP_CONCAT(si.quantity) as quantities,
    GROUP_CONCAT(si.price) as prices,
    GROUP_CONCAT(si.total) as totals,
    GROUP_CONCAT(p.name) as product_names
    FROM $table_name s
    LEFT JOIN {$wpdb->prefix}pos_sale_items si ON s.id = si.sale_id
    LEFT JOIN {$wpdb->prefix}pos_products p ON si.product_id = p.id
    WHERE s.id = %d
    GROUP BY s.id", 
    $sale_id
);

$sale_data = $wpdb->get_row($query);

if (!$sale_data) {
    wp_die(__('Venta no encontrada', 'wp-pos'));
}

// Procesar items
$sale_data->items = array();
if (!empty($sale_data->product_ids)) {
    $product_ids = explode(',', $sale_data->product_ids);
    $quantities = explode(',', $sale_data->quantities);
    $prices = explode(',', $sale_data->prices);
    $totals = explode(',', $sale_data->totals);
    
    foreach ($product_ids as $index => $product_id) {
        if (!empty($product_id)) {
            // Obtener el nombre del producto desde la tabla pos_products
            $product_name = $wpdb->get_var($wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}pos_products WHERE id = %d",
                $product_id
            ));
            
            // Si no se encuentra el nombre, usar un valor por defecto
            if (empty($product_name)) {
                $product_name = 'Producto ' . ($index + 1);
            }
            
            $sale_data->items[] = array(
                'product_id' => $product_id,
                'name' => $product_name,
                'quantity' => $quantities[$index] ?? 1,
                'price' => $prices[$index] ?? 0,
                'total' => $totals[$index] ?? 0
            );
        }
    }
}

// Procesar pagos
$sale_data->payments = array();
if (!empty($sale_data->total)) {
    $sale_data->payments[] = array(
        'method' => 'Efectivo',
        'amount' => $sale_data->total,
        'reference' => ''
    );
}

// Corregir fecha inválida
if (empty($sale_data->date) || $sale_data->date === '0000-00-00 00:00:00') {
    $sale_data->date = current_time('mysql');
}

// Depuración - Guardar estructura de los datos en el log
error_log('=== ESTRUCTURA DE LA VENTA ===');
error_log('ID: ' . $sale_data->id);
error_log('Número: ' . ($sale_data->sale_number ?? 'No definido'));
error_log('Fecha: ' . ($sale_data->date ?? 'No definida'));
error_log('Total: ' . ($sale_data->total ?? '0'));

// Guardar estructura de items
if (is_array($sale_data->items) || is_object($sale_data->items)) {
    error_log('=== ITEMS ===');
    foreach ($sale_data->items as $index => $item) {
        error_log('Item #' . $index . ':');
        if (is_object($item)) {
            foreach (get_object_vars($item) as $key => $value) {
                error_log('  ' . $key . ': ' . print_r($value, true));
            }
        } elseif (is_array($item)) {
            foreach ($item as $key => $value) {
                error_log('  ' . $key . ': ' . print_r($value, true));
            }
        }
    }
} else {
    error_log('No hay items o no es un array/objeto: ' . gettype($sale_data->items));
}

// Guardar estructura de pagos
if (is_array($sale_data->payments) || is_object($sale_data->payments)) {
    error_log('=== PAGOS ===');
    foreach ($sale_data->payments as $index => $payment) {
        error_log('Pago #' . $index . ':');
        if (is_object($payment)) {
            foreach (get_object_vars($payment) as $key => $value) {
                error_log('  ' . $key . ': ' . print_r($value, true));
            }
        } elseif (is_array($payment)) {
            foreach ($payment as $key => $value) {
                error_log('  ' . $key . ': ' . print_r($value, true));
            }
        }
    }
} else {
    error_log('No hay pagos o no es un array/objeto: ' . gettype($sale_data->payments));
}

// Validar datos de la venta
if (empty($sale_data)) {
    wp_die(__('Error: No se pudieron cargar los datos de la venta', 'wp-pos'));
}

// Asegurarse de que sean arrays
if (!is_array($sale_data->items)) {
    $sale_data->items = array();
}

if (!is_array($sale_data->payments)) {
    $sale_data->payments = array();
}

// Validar que haya al menos un ítem en la venta
if (empty($sale_data->items)) {
    wp_die(__('Error: La venta no contiene ningún producto', 'wp-pos'));
}

// Validar total
if (!isset($sale_data->total) || $sale_data->total <= 0) {
    wp_die(__('Error: El total de la venta no es válido', 'wp-pos'));
}

// Obtener datos del cliente si existe
if (!empty($sale_data->customer_id) && $sale_data->customer_id > 0) {
    $customer = get_user_by('ID', $sale_data->customer_id);
    if ($customer) {
        $sale_data->customer_name = $customer->display_name;
    } else {
        // Si no se encuentra como usuario, intentar obtenerlo como post (para compatibilidad)
        $customer_post = get_post($sale_data->customer_id);
        if ($customer_post) {
            $sale_data->customer_name = $customer_post->post_title;
        }
    }
}

// Si no hay nombre de cliente o el ID es 0 (cliente anónimo), usar el valor por defecto
if (empty($sale_data->customer_name) || $sale_data->customer_id == 0) {
    $sale_data->customer_name = 'Cliente de mostrador';
}

// Obtener información de la tienda
$options = get_option('wp_pos_settings', array());
$store_name = isset($options['store_name']) ? $options['store_name'] : get_bloginfo('name');
$store_address = isset($options['store_address']) ? $options['store_address'] : '';
$store_phone = isset($options['store_phone']) ? $options['store_phone'] : '';
$receipt_footer = isset($options['receipt_footer']) ? $options['receipt_footer'] : '';

// Función de formato de precio
function receipt_format_price($price) {
    // Convertir a número flotante
    $price = floatval($price);
    
    // Formatear con separador de miles y decimales
    return '$' . number_format($price, 2, ',', '.');
}

// Silenciar todas las salidas anteriores
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php printf(__('Recibo - %s', 'wp-pos'), isset($sale_data->sale_number) ? esc_html($sale_data->sale_number) : 'N/A'); ?></title>
    <style>
    /* Estilos básicos para el recibo */
    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        line-height: 1.4;
        margin: 0;
        padding: 0;
        background-color: #f8f8f8;
    }

    .receipt-container {
        max-width: 80mm;
        margin: 0 auto;
        padding: 10px;
        background-color: white;
        box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    .receipt-header {
        text-align: center;
        margin-bottom: 15px;
    }

    .receipt-header h1 {
        font-size: 16px;
        margin: 0 0 5px;
    }

    .receipt-header p {
        margin: 3px 0;
    }

    .receipt-info {
        border-top: 1px dashed #ccc;
        border-bottom: 1px dashed #ccc;
        padding: 10px 0;
        margin-bottom: 10px;
    }

    .receipt-info p {
        display: flex;
        justify-content: space-between;
        margin: 3px 0;
    }

    table.receipt-items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    table.receipt-items th {
        text-align: left;
        border-bottom: 1px solid #ccc;
        padding: 3px 0;
    }

    table.receipt-items td {
        padding: 3px 0;
    }

    .center {
        text-align: center;
    }

    .right {
        text-align: right;
    }

    .receipt-footer {
        text-align: center;
        margin-top: 15px;
        border-top: 1px dashed #ccc;
        padding-top: 10px;
        font-size: 11px;
    }

    .total-row {
        border-top: 1px solid #ccc;
        font-weight: bold;
    }

    .receipt-payments {
        margin-top: 10px;
    }

    .receipt-payments h3 {
        font-size: 12px;
        margin: 5px 0;
    }

    .receipt-buttons {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 10px;
    }

    .receipt-button {
        padding: 8px 16px;
        background-color: #0073aa;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
    }

    @media print {
        body {
            background-color: white;
        }
        
        .receipt-container {
            box-shadow: none;
            max-width: 100%;
        }
        
        .receipt-buttons {
            display: none;
        }
    }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1><?php echo esc_html($store_name); ?></h1>
            <?php if (!empty($store_address)): ?>
            <p><?php echo esc_html($store_address); ?></p>
            <?php endif; ?>
            <?php if (!empty($store_phone)): ?>
            <p><?php echo esc_html($store_phone); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="receipt-info">
            <p>
                <span><?php _e('Recibo #:', 'wp-pos'); ?></span>
                <span><?php echo esc_html($sale_data->sale_number); ?></span>
            </p>
            <p>
                <span><?php _e('Fecha:', 'wp-pos'); ?></span>
                <span>
                    <?php 
                    $date = !empty($sale_data->date) ? $sale_data->date : current_time('mysql');
                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date))); 
                    ?>
                </span>
            </p>
            <p>
                <span><?php _e('Cliente:', 'wp-pos'); ?></span>
                <span><?php echo esc_html($sale_data->customer_name); ?></span>
            </p>
        </div>
        
        <table class="receipt-items">
            <thead>
                <tr>
                    <th><?php _e('PRODUCTO', 'wp-pos'); ?></th>
                    <th class="center"><?php _e('CANT', 'wp-pos'); ?></th>
                    <th class="right"><?php _e('PRECIO', 'wp-pos'); ?></th>
                    <th class="right"><?php _e('TOTAL', 'wp-pos'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                
                if (is_array($sale_data->items) && !empty($sale_data->items)):
                    foreach ($sale_data->items as $item):
                        // Inicializar variables
                        $item_name = '';
                        $item_qty = 0;
                        $item_price = 0;
                        $item_total = 0;
                        
                        // Verificar si hay datos en el item
                        if (!empty($item)) {
                            // Si es un objeto
                            if (is_object($item)) {
                                $item_name = property_exists($item, 'name') ? $item->name : '';
                                $item_qty = property_exists($item, 'quantity') ? floatval($item->quantity) : 1;
                                $item_price = property_exists($item, 'price') ? floatval($item->price) : 0;
                                $item_total = property_exists($item, 'total') ? floatval($item->total) : ($item_price * $item_qty);
                                
                                // Si no hay nombre pero hay ID de producto, intentar obtener el nombre
                                if (empty($item_name) && property_exists($item, 'product_id') && function_exists('wc_get_product')) {
                                    $product = wc_get_product($item->product_id);
                                    if ($product) {
                                        $item_name = $product->get_name();
                                    }
                                }
                            } 
                            // Si es un array
                            elseif (is_array($item)) {
                                $item_name = isset($item['name']) ? $item['name'] : (isset($item['title']) ? $item['title'] : '');
                                $item_qty = isset($item['quantity']) ? floatval($item['quantity']) : 1;
                                $item_price = isset($item['price']) ? floatval($item['price']) : 0;
                                $item_total = isset($item['total']) ? floatval($item['total']) : ($item_price * $item_qty);
                                
                                // Si no hay nombre pero hay ID de producto, intentar obtener el nombre
                                if (empty($item_name) && isset($item['product_id']) && function_exists('wc_get_product')) {
                                    $product = wc_get_product($item['product_id']);
                                    if ($product) {
                                        $item_name = $product->get_name();
                                    }
                                }
                            }
                            
                            // Si el nombre sigue vacío, usar un valor por defecto
                            if (empty($item_name)) {
                                $item_name = __('Producto sin nombre', 'wp-pos');
                            }
                        }
                        
                        $subtotal += $item_total;
                ?>
                <tr>
                    <td><?php echo esc_html($item_name); ?></td>
                    <td class="center"><?php echo esc_html($item_qty); ?></td>
                    <td class="right"><?php echo receipt_format_price($item_price); ?></td>
                    <td class="right"><?php echo receipt_format_price($item_total); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="right"><?php _e('SUBTOTAL', 'wp-pos'); ?></td>
                    <td class="right"><?php echo receipt_format_price($subtotal); ?></td>
                </tr>
                
                <?php if (isset($sale_data->discount_total) && floatval($sale_data->discount_total) > 0): ?>
                <tr>
                    <td colspan="3" class="right"><?php _e('DESCUENTO', 'wp-pos'); ?></td>
                    <td class="right"><?php echo receipt_format_price($sale_data->discount_total); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (isset($sale_data->discount) && floatval($sale_data->discount) > 0 && !isset($sale_data->discount_total)): ?>
                <tr>
                    <td colspan="3" class="right"><?php _e('DESCUENTO', 'wp-pos'); ?></td>
                    <td class="right"><?php echo receipt_format_price($sale_data->discount); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (isset($sale_data->tax_total) && floatval($sale_data->tax_total) > 0): ?>
                <tr>
                    <td colspan="3" class="right"><?php _e('IMPUESTOS', 'wp-pos'); ?></td>
                    <td class="right"><?php echo receipt_format_price($sale_data->tax_total); ?></td>
                </tr>
                <?php endif; ?>
                
                <tr class="total-row">
                    <td colspan="3" class="right"><strong><?php _e('TOTAL', 'wp-pos'); ?></strong></td>
                    <td class="right"><strong><?php echo receipt_format_price($sale_data->total); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if (is_array($sale_data->payments) && !empty($sale_data->payments)): ?>
        <div class="receipt-payments">
            <h3><?php _e('PAGOS', 'wp-pos'); ?></h3>
            <table class="receipt-items">
                <?php 
                $total_paid = 0;
                foreach ($sale_data->payments as $payment):
                    // Verificar si es un objeto o un array
                    $payment_method = '';
                    $payment_amount = 0;
                    $payment_reference = '';
                    
                    if (is_object($payment)) {
                        $payment_method = method_exists($payment, 'get_method') ? $payment->get_method() : '';
                        $payment_amount = method_exists($payment, 'get_amount') ? $payment->get_amount() : 0;
                        $payment_reference = method_exists($payment, 'get_reference') ? $payment->get_reference() : '';
                    } elseif (is_array($payment)) {
                        $payment_method = isset($payment['method']) ? $payment['method'] : '';
                        $payment_amount = isset($payment['amount']) ? $payment['amount'] : 0;
                        $payment_reference = isset($payment['reference']) ? $payment['reference'] : '';
                    }
                    
                    $total_paid += $payment_amount;
                    
                    // Métodos de pago más amigables
                    $method_labels = array(
                        'cash' => __('Efectivo', 'wp-pos'),
                        'card' => __('Tarjeta', 'wp-pos'),
                        'transfer' => __('Transferencia', 'wp-pos'),
                        'mercadopago' => __('Mercado Pago', 'wp-pos')
                    );
                    $method_display = isset($method_labels[$payment_method]) ? $method_labels[$payment_method] : esc_html($payment_method);
                ?>
                <tr>
                    <td><?php echo $method_display; ?><?php echo !empty($payment_reference) ? ' (' . esc_html($payment_reference) . ')' : ''; ?></td>
                    <td class="right"><?php echo receipt_format_price($payment_amount); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="right"><strong><?php _e('TOTAL PAGADO', 'wp-pos'); ?></strong></td>
                    <td class="right"><strong><?php echo receipt_format_price($total_paid); ?></strong></td>
                </tr>
                <?php 
                $change = max(0, $total_paid - $sale_data->total);
                if ($change > 0):
                ?>
                <tr>
                    <td class="right"><?php _e('CAMBIO', 'wp-pos'); ?></td>
                    <td class="right"><?php echo receipt_format_price($change); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($receipt_footer)): ?>
        <div class="receipt-footer">
            <?php echo wpautop(esc_html($receipt_footer)); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="receipt-buttons">
        <button onclick="window.print();" class="receipt-button"><?php _e('Imprimir', 'wp-pos'); ?></button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-sale-details&id=' . $sale_id)); ?>" class="receipt-button"><?php _e('Volver a detalles', 'wp-pos'); ?></a>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-print después de cargar completamente
        if (window.location.search.indexOf('autoprint=1') !== -1) {
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    });
    </script>
</body>
</html>
<?php exit; // Asegurar que no haya más salida después ?>
