<?php
// Prevenir acceso directo
if (!defined('ABSPATH')) exit;

// Función de respaldo para formatear precios si WooCommerce no está activo
if (!function_exists('wc_price')) {
    function wc_price($price, $args = array()) {
        $negative = $price < 0;
        $price = $negative ? $price * -1 : $price;
        $price = number_format($price, 2, ',', '.');
        
        $formatted_price = ($negative ? '-' : '') . '$' . $price;
        return $formatted_price;
    }
}

// Verificar que las variables necesarias estén definidas
if (!isset($sale) || !is_array($sale)) {
    wp_die('Datos de venta no válidos', 'Error', array('response' => 500));
}

// Asegurarse de que $items sea un array
if (!isset($items) || !is_array($items)) {
    $items = array();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - <?php bloginfo('name'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .sale-details-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        .sale-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .sale-title {
            margin: 0;
            color: #23282d;
            font-size: 24px;
        }
        .sale-meta {
            color: #666;
            margin: 10px 0;
        }
        .sale-section {
            margin-bottom: 30px;
        }
        .sale-section-title {
            font-size: 18px;
            margin: 0 0 15px 0;
            color: #23282d;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #555;
        }
        .items-table tr:hover {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        .totals-table tr:last-child td {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
        }
        .payment-method {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }
        .btn-print {
            display: inline-block;
            padding: 8px 16px;
            background-color: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-print:hover {
            background-color: #135e96;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                background: #fff;
            }
            .sale-details-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sale-details-container">
        <div class="sale-header">
            <div>
                <h1 class="sale-title"><?php echo esc_html($page_title); ?></h1>
                <div class="sale-meta">
                    <?php 
                    $sale_date = !empty($sale['date_created']) ? $sale['date_created'] : $sale['date'];
                    echo 'Fecha: ' . date_i18n('d/m/Y H:i', strtotime($sale_date)); 
                    ?>
                </div>
            </div>
            <button onclick="window.print()" class="btn-print no-print">Imprimir</button>
        </div>

        <div class="sale-section">
            <h2 class="sale-section-title">Productos</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-right">Precio</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    foreach ($items as $item): 
                        $item_total = isset($item['total']) ? floatval($item['total']) : 0;
                        $subtotal += $item_total;
                    ?>
                    <tr>
                        <td>
                            <?php 
                            $product_name = isset($item['name']) ? $item['name'] : 'Producto desconocido';
                            if (isset($item['variation_name']) && !empty($item['variation_name'])) {
                                $product_name .= ' - ' . $item['variation_name'];
                            }
                            echo esc_html($product_name); 
                            ?>
                        </td>
                        <td class="text-right"><?php echo wc_price($item['price'] ?? 0); ?></td>
                        <td class="text-center"><?php echo esc_html($item['quantity'] ?? 1); ?></td>
                        <td class="text-right"><?php echo wc_price($item_total); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right"><?php echo wc_price($subtotal); ?></td>
                </tr>
                <?php if (isset($sale['discount']) && $sale['discount'] > 0): ?>
                <tr>
                    <td>Descuento:</td>
                    <td class="text-right">-<?php echo wc_price($sale['discount']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (isset($sale['tax']) && $sale['tax'] > 0): ?>
                <tr>
                    <td>Impuestos:</td>
                    <td class="text-right"><?php echo wc_price($sale['tax']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td class="text-right"><strong><?php echo wc_price($sale['total'] ?? 0); ?></strong></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($payments)): ?>
        <div class="sale-section">
            <h2 class="sale-section-title">Pagos</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Método de pago</th>
                        <th class="text-right">Monto</th>
                        <th>Referencia</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>
                            <span class="payment-method">
                                <?php echo esc_html(ucfirst($payment['payment_method'])); ?>
                            </span>
                        </td>
                        <td class="text-right"><?php echo wc_price($payment['amount']); ?></td>
                        <td><?php echo esc_html($payment['reference'] ?? '-'); ?></td>
                        <td><?php echo esc_html($payment['notes'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="sale-section no-print">
            <button onclick="window.close()" class="btn-print">Cerrar</button>
        </div>
    </div>
</body>
</html>
