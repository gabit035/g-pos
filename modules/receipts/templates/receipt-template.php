<?php
/**
 * Plantilla de recibos de venta
 *
 * @package WP-POS
 * @subpackage Receipts
 * @since 2.3.0
 */

// Prevenir el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar que tenemos datos de venta
if (!isset($sale_data) || empty($sale_data)) {
    wp_die(__('No se encontraron datos de la venta', 'wp-pos'));
    return;
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
    <title><?php printf(__('Recibo - %s', 'wp-pos'), isset($sale_data->sale_number) ? esc_html($sale_data->sale_number) : 'N/A'); ?></title>
    <?php
    // Depuración para verificar los datos
    error_log(print_r($sale_data, true));
    
    // Cargar estilos inline para garantizar que se muestren incluso sin conexión
    $css_file = plugin_dir_path(dirname(__FILE__)) . 'assets/css/receipt-styles.css';
    if (file_exists($css_file)) {
        echo '<style>' . file_get_contents($css_file) . '</style>';
    }
    
    // Permitir estilos adicionales
    do_action('wp_pos_receipt_head');
    ?>
</head>
<body class="wp-pos-receipt-body">
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
                <span><?php echo esc_html(date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($sale_data->date))); ?></span>
            </p>
            <p>
                <span><?php _e('Cliente:', 'wp-pos'); ?></span>
                <span>
                    <?php 
                    // Ya tenemos el customer_name en el objeto $sale_data gracias a nuestra mejora
                    echo esc_html(isset($sale_data->customer_name) ? $sale_data->customer_name : __('Cliente anónimo', 'wp-pos'));
                    ?>
                </span>
            </p>
            
            <?php 
            // Permitir información adicional en la cabecera
            do_action('wp_pos_receipt_after_header', $sale_data); 
            ?>
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
                        // Verificar si es un objeto o un array
                        $item_name = '';
                        $item_qty = 0;
                        $item_price = 0;
                        $item_total = 0;
                        
                        if (is_object($item)) {
                            $item_name = method_exists($item, 'get_name') ? $item->get_name() : '';
                            $item_qty = method_exists($item, 'get_quantity') ? $item->get_quantity() : 1;
                            $item_price = method_exists($item, 'get_price') ? $item->get_price() : 0;
                            $item_total = method_exists($item, 'get_total') ? $item->get_total() : ($item_price * $item_qty);
                        } elseif (is_array($item)) {
                            $item_name = isset($item['name']) ? $item['name'] : '';
                            $item_qty = isset($item['quantity']) ? $item['quantity'] : 1;
                            $item_price = isset($item['price']) ? $item['price'] : 0;
                            $item_total = isset($item['total']) ? $item['total'] : ($item_price * $item_qty);
                        }
                        
                        $subtotal += $item_total;
                ?>
                <tr>
                    <td><?php echo esc_html($item_name); ?></td>
                    <td class="center"><?php echo esc_html($item_qty); ?></td>
                    <td class="right"><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($item_price) : wp_pos_format_price($item_price); ?></td>
                    <td class="right"><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($item_total) : wp_pos_format_price($item_total); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="right"><?php _e('SUBTOTAL', 'wp-pos'); ?></td>
                    <td class="right"><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($subtotal) : wp_pos_format_price($subtotal); ?></td>
                </tr>
                
                <?php if (isset($sale_data->discount_total) && floatval($sale_data->discount_total) > 0): ?>
                <tr>
                    <td colspan="3" class="right"><?php _e('DESCUENTO', 'wp-pos'); ?></td>
                    <td class="right"><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($sale_data->discount_total) : wp_pos_format_price($sale_data->discount_total); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (isset($sale_data->discount) && floatval($sale_data->discount) > 0 && !isset($sale_data->discount_total)): ?>
                <tr>
                    <td colspan="3" class="right"><?php _e('DESCUENTO', 'wp-pos'); ?></td>
                    <td class="right"><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($sale_data->discount) : wp_pos_format_price($sale_data->discount); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if (isset($sale_data->tax_total) && floatval($sale_data->tax_total) > 0): ?>
                <tr>
                    <td colspan="3" class="right"><?php _e('IMPUESTOS', 'wp-pos'); ?></td>
                    <td class="right"><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($sale_data->tax_total) : wp_pos_format_price($sale_data->tax_total); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php
                // Permitir agregar filas personalizadas antes del total
                do_action('wp_pos_receipt_before_total', $sale_data);
                ?>
                <tr class="total-row">
                    <td colspan="3" class="right"><strong><?php _e('TOTAL', 'wp-pos'); ?></strong></td>
                    <td class="right"><strong><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($sale_data->total) : wp_pos_format_price($sale_data->total); ?></strong></td>
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
                ?>
                <tr>
                    <td><?php 
                        // Mejorar la presentación de los métodos de pago
                        $method_labels = array(
                            'cash' => __('Efectivo', 'wp-pos'),
                            'card' => __('Tarjeta', 'wp-pos'),
                            'transfer' => __('Transferencia', 'wp-pos'),
                            'mercadopago' => __('Mercado Pago', 'wp-pos')
                        );
                        $method_display = isset($method_labels[$payment_method]) ? $method_labels[$payment_method] : esc_html($payment_method);
                        echo $method_display;
                        echo !empty($payment_reference) ? ' (' . esc_html($payment_reference) . ')' : ''; 
                    ?></td>
                    <td class="right"><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($payment_amount) : wp_pos_format_price($payment_amount); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="right"><strong><?php _e('TOTAL PAGADO', 'wp-pos'); ?></strong></td>
                    <td class="right"><strong><?php echo function_exists('wp_pos_format_currency') ? wp_pos_format_currency($total_paid) : wp_pos_format_price($total_paid); ?></strong></td>
                </tr>
                <?php 
                $change = max(0, $total_paid - $sale_data->total);
                if ($change > 0): 
                ?>
                <tr>
                    <td class="right"><?php _e('CAMBIO', 'wp-pos'); ?></td>
                    <td class="right"><?php echo wp_pos_format_currency($change); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <?php
        // Permitir contenido adicional antes del pie de página
        do_action('wp_pos_receipt_before_footer', $sale_data);
        ?>
        
        <div class="receipt-thanks">
            <?php _e('¡GRACIAS POR SU COMPRA!', 'wp-pos'); ?>
        </div>
        
        <?php if (!empty($receipt_footer)): ?>
        <div class="receipt-footer">
            <?php echo nl2br(esc_html($receipt_footer)); ?>
        </div>
        <?php endif; ?>
        
        <?php
        // Permitir contenido adicional después del pie de página
        do_action('wp_pos_receipt_after_footer', $sale_data);
        ?>
    </div>
    
    <div class="action-buttons no-print">
        <button onclick="window.print();" class="print-button">
            <?php _e('Imprimir Recibo', 'wp-pos'); ?>
        </button>
        <button onclick="window.close();" class="back-button">
            <?php _e('Cerrar', 'wp-pos'); ?>
        </button>
    </div>
    
    <?php
    // Permitir scripts adicionales
    do_action('wp_pos_receipt_scripts', $sale_data);
    ?>
</body>
</html>
