<?php
/**
 * Plantilla de impresión de recibo
 *
 * @package WP-POS
 * @since 1.0.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Obtener ID de la venta
$sale_id = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;

// Acceso directo a la base de datos para obtener la venta
$sale = null;
$sale_items = array();
$sale_payments = array();

if ($sale_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pos_sales';
    
    // Obtener venta directamente de la base de datos
    $sale = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $sale_id));
    
    if ($sale) {
        // Deserializar items y pagos
        $sale_items = maybe_unserialize($sale->items);
        $sale_payments = maybe_unserialize($sale->payments);
        
        if (!is_array($sale_items)) {
            $sale_items = array();
        }
        
        if (!is_array($sale_payments)) {
            $sale_payments = array();
        }
    }
}

// Si no existe la venta, mostrar mensaje de error y salir
if (!$sale) {
    // Si hay un ID pero no se encuentra la venta
    if ($sale_id > 0) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Error - Venta no encontrada', 'wp-pos'); ?></title>
            <style>
                body { font-family: sans-serif; background: #f0f0f1; color: #3c434a; margin: 0; padding: 40px; }
                .error-container { max-width: 500px; margin: 0 auto; padding: 30px; background: white; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.13); }
                h1 { font-size: 24px; margin-top: 0; color: #d63638; }
                .button { display: inline-block; padding: 8px 16px; background: #2271b1; color: white; text-decoration: none; border-radius: 3px; margin-top: 20px; }
                .button:hover { background: #135e96; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1><?php _e('Venta no encontrada', 'wp-pos'); ?></h1>
                <p><?php _e('Lo sentimos, la venta solicitada no existe o ha sido eliminada.', 'wp-pos'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wp-pos-sales')); ?>" class="button"><?php _e('Volver a ventas', 'wp-pos'); ?></a>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Si no hay ID, redirigir a la lista
        wp_redirect(admin_url('admin.php?page=wp-pos-sales'));
        exit;
    }
}

// Obtener información de la tienda
$options = wp_pos_get_option();
$store_name = isset($options['store_name']) ? $options['store_name'] : get_bloginfo('name');
$store_address = isset($options['store_address']) ? $options['store_address'] : '';
$store_phone = isset($options['store_phone']) ? $options['store_phone'] : '';
$receipt_footer = isset($options['receipt_footer']) ? $options['receipt_footer'] : '';

// No incluir el header de WordPress para una página limpia
?><!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php printf(__('Recibo - %s', 'wp-pos'), esc_html($sale->sale_number)); ?></title>
    <style>
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
        
        @media print {
            body {
                background-color: white;
            }
            
            .receipt-container {
                max-width: 100%;
                box-shadow: none;
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ccc;
        }
        
        .receipt-header h1 {
            font-size: 14px;
            margin: 0 0 5px;
        }
        
        .receipt-header p {
            margin: 2px 0;
            font-size: 10px;
        }
        
        .receipt-info {
            margin-bottom: 10px;
        }
        
        .receipt-info p {
            margin: 2px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .receipt-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .receipt-items th {
            text-align: left;
            font-size: 10px;
            padding: 3px 0;
            border-bottom: 1px solid #ccc;
        }
        
        .receipt-items td {
            padding: 3px 0;
            font-size: 10px;
        }
        
        .receipt-items .right {
            text-align: right;
        }
        
        .receipt-items .center {
            text-align: center;
        }
        
        .receipt-items tfoot td {
            padding-top: 5px;
            border-top: 1px solid #ccc;
        }
        
        .receipt-footer {
            margin-top: 10px;
            text-align: center;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
            font-size: 10px;
        }
        
        .receipt-thanks {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .action-buttons {
            margin: 20px 0;
            text-align: center;
        }
        
        .print-button {
            background-color: #0078d7;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .back-button {
            background-color: #555;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
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
            <p><?php _e('Tel:', 'wp-pos'); ?> <?php echo esc_html($store_phone); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="receipt-info">
            <p>
                <span><?php _e('Recibo:', 'wp-pos'); ?></span>
                <span><?php echo esc_html($sale->sale_number); ?></span>
            </p>
            <p>
                <span><?php _e('Fecha:', 'wp-pos'); ?></span>
                <span><?php echo esc_html(wp_pos_format_date($sale->date)); ?></span>
            </p>
            <?php if ($sale->customer_id > 0): ?>
            <p>
                <span><?php _e('Cliente:', 'wp-pos'); ?></span>
                <span>
                    <?php 
                    $user = get_user_by('id', $sale->customer_id);
                    echo $user ? esc_html($user->display_name) : __('Cliente #', 'wp-pos') . esc_html($sale->customer_id);
                    ?>
                </span>
            </p>
            <?php endif; ?>
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
                
                if (is_array($sale_items) && !empty($sale_items)):
                    foreach ($sale_items as $item):
                        // Verificar si es un objeto o un array
                        $item_name = '';
                        $item_price = 0;
                        $item_qty = 0;
                        $item_total = 0;
                        
                        if (is_object($item)) {
                            $item_name = method_exists($item, 'get_name') ? $item->get_name() : '';
                            $item_price = method_exists($item, 'get_price') ? $item->get_price() : 0;
                            $item_qty = method_exists($item, 'get_quantity') ? $item->get_quantity() : 0;
                            $item_total = method_exists($item, 'get_total') ? $item->get_total() : ($item_price * $item_qty);
                        } elseif (is_array($item)) {
                            $item_name = isset($item['name']) ? $item['name'] : '';
                            $item_price = isset($item['price']) ? $item['price'] : 0;
                            $item_qty = isset($item['quantity']) ? $item['quantity'] : 0;
                            $item_total = isset($item['total']) ? $item['total'] : ($item_price * $item_qty);
                        }
                        
                        $subtotal += $item_total;
                ?>
                <tr>
                    <td><?php echo esc_html($item_name); ?></td>
                    <td class="center"><?php echo esc_html($item_qty); ?></td>
                    <td class="right"><?php echo esc_html(wp_pos_format_currency($item_price)); ?></td>
                    <td class="right"><?php echo esc_html(wp_pos_format_currency($item_total)); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="right"><?php _e('SUBTOTAL', 'wp-pos'); ?></td>
                    <td class="right"><?php echo esc_html(wp_pos_format_currency($subtotal)); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="right"><strong><?php _e('TOTAL', 'wp-pos'); ?></strong></td>
                    <td class="right"><strong><?php echo esc_html(wp_pos_format_currency($sale->total)); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <?php if (is_array($sale_payments) && !empty($sale_payments)): ?>
        <div class="receipt-payments">
            <h3><?php _e('PAGOS', 'wp-pos'); ?></h3>
            <table class="receipt-items">
                <?php 
                $total_paid = 0;
                foreach ($sale_payments as $payment):
                    // Verificar si es un objeto o un array
                    $payment_method = '';
                    $payment_amount = 0;
                    
                    if (is_object($payment)) {
                        $payment_method = method_exists($payment, 'get_method') ? $payment->get_method() : '';
                        $payment_amount = method_exists($payment, 'get_amount') ? $payment->get_amount() : 0;
                    } elseif (is_array($payment)) {
                        $payment_method = isset($payment['method']) ? $payment['method'] : '';
                        $payment_amount = isset($payment['amount']) ? $payment['amount'] : 0;
                    }
                    
                    $total_paid += $payment_amount;
                ?>
                <tr>
                    <td><?php echo esc_html($payment_method); ?></td>
                    <td class="right"><?php echo esc_html(wp_pos_format_currency($payment_amount)); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="right"><strong><?php _e('TOTAL PAGADO', 'wp-pos'); ?></strong></td>
                    <td class="right"><strong><?php echo esc_html(wp_pos_format_currency($total_paid)); ?></strong></td>
                </tr>
                <?php 
                $change = max(0, $total_paid - $sale->total);
                if ($change > 0): 
                ?>
                <tr>
                    <td class="right"><?php _e('CAMBIO', 'wp-pos'); ?></td>
                    <td class="right"><?php echo esc_html(wp_pos_format_currency($change)); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="receipt-thanks">
            <?php _e('¡GRACIAS POR SU COMPRA!', 'wp-pos'); ?>
        </div>
        
        <?php if (!empty($receipt_footer)): ?>
        <div class="receipt-footer">
            <?php echo nl2br(esc_html($receipt_footer)); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="action-buttons no-print">
        <button onclick="window.print();" class="print-button">
            <?php _e('Imprimir Recibo', 'wp-pos'); ?>
        </button>
        <button onclick="window.close();" class="back-button">
            <?php _e('Cerrar', 'wp-pos'); ?>
        </button>
    </div>
</body>
</html>
