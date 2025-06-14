<?php
// Prevenir acceso directo
if (!defined('ABSPATH')) exit;

// Configurar tipo de contenido para impresión
header('Content-Type: text/html; charset=utf-8');

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

// Asegurarse de que $payments sea un array
if (!isset($payments) || !is_array($payments)) {
    $payments = array();
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - <?php bloginfo('name'); ?></title>
    <style>
        @page {
            size: 80mm 297mm;
            margin: 0;
        }
        @media print {
            @page {
                size: 80mm 297mm;
                margin: 0;
            }
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
                font-size: 12px;
                line-height: 1.2;
                background: white;
                color: black;
            }
            .ticket {
                width: 100%;
                padding: 5mm;
                box-sizing: border-box;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0;
            padding: 5mm;
            font-size: 12px;
            line-height: 1.2;
            background: white;
            color: black;
        }
        .ticket {
            width: 100%;
            max-width: 80mm;
            margin: 0 auto;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo img {
            max-width: 150px;
            max-height: 80px;
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }
        .ticket-title {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }
        .ticket-info {
            margin: 5px 0;
            font-size: 12px;
        }
        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            font-size: 11px;
        }
        .ticket-table th {
            text-align: left;
            padding: 2px 0;
            border-bottom: 1px dashed #000;
        }
        .ticket-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .ticket-table .text-right {
            text-align: right;
        }
        .ticket-totals {
            width: 100%;
            margin: 10px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
        }
        .ticket-totals tr:last-child td {
            font-weight: bold;
            padding-top: 5px;
        }
        .ticket-footer {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px dashed #000;
            font-size: 10px;
            text-align: center;
        }
        .payment-method {
            margin: 5px 0;
            padding: 3px 5px;
            background: #f0f0f0;
            border-radius: 3px;
            display: inline-block;
            font-size: 10px;
        }
        .barcode {
            text-align: center;
            margin: 10px 0;
        }
        .btn-print {
            display: block;
            width: 100%;
            margin: 10px 0;
            padding: 8px;
            background: #2271b1;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-print:hover {
            background: #135e96;
        }
    </style>
    <script>
        // Auto-imprimir al cargar la página
        window.onload = function() {
            // Esperar un momento para que se carguen los estilos
            setTimeout(function() {
                window.print();
                // Cerrar la ventana después de imprimir
                // window.onafterprint = function() {
                //     setTimeout(function() { window.close(); }, 100);
                // };
            }, 200);
        };
    </script>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <div class="logo">
                <?php if (has_custom_logo()): ?>
                    <?php 
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                    if ($logo) {
                        echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '">';
                    }
                    ?>
                <?php else: ?>
                    <div class="ticket-title"><?php bloginfo('name'); ?></div>
                <?php endif; ?>
            </div>
            <div class="ticket-info">
                <?php 
                $sale_date = !empty($sale['date_created']) ? $sale['date_created'] : $sale['date'];
                echo date_i18n('d/m/Y H:i', strtotime($sale_date)); 
                ?>
            </div>
            <div class="ticket-info">
                Ticket #<?php echo esc_html($sale['id']); ?>
            </div>
        </div>

        <table class="ticket-table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php 
                        $product_name = isset($item['name']) ? $item['name'] : 'Producto';
                        if (isset($item['variation_name']) && !empty($item['variation_name'])) {
                            $product_name .= ' ' . $item['variation_name'];
                        }
                        echo esc_html($product_name); 
                        ?>
                    </td>
                    <td class="text-right"><?php echo esc_html($item['quantity'] ?? 1); ?></td>
                    <td class="text-right"><?php echo wc_price(isset($item['total']) ? $item['total'] : 0); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="ticket-totals">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">
                    <?php 
                    $subtotal = 0;
                    foreach ($items as $item) {
                        $subtotal += isset($item['total']) ? floatval($item['total']) : 0;
                    }
                    echo wc_price($subtotal); 
                    ?>
                </td>
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
                <td><strong>TOTAL:</strong></td>
                <td class="text-right"><strong><?php echo wc_price($sale['total'] ?? 0); ?></strong></td>
            </tr>
        </table>

        <?php if (!empty($payments)): ?>
        <div style="margin: 10px 0;">
            <div style="font-weight: bold; margin-bottom: 5px;">PAGOS:</div>
            <?php foreach ($payments as $payment): ?>
                <div style="display: flex; justify-content: space-between; margin: 3px 0;">
                    <span class="payment-method"><?php echo esc_html(ucfirst($payment['payment_method'])); ?></span>
                    <span><?php echo wc_price($payment['amount']); ?></span>
                </div>
                <?php if (!empty($payment['reference'])): ?>
                    <div style="font-size: 10px; margin-bottom: 5px;">Ref: <?php echo esc_html($payment['reference']); ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($sale['notes'])): ?>
        <div style="margin: 10px 0; padding: 5px; border: 1px dashed #000;">
            <div style="font-weight: bold;">Notas:</div>
            <div><?php echo nl2br(esc_html($sale['notes'])); ?></div>
        </div>
        <?php endif; ?>

        <div class="ticket-footer">
            <div>¡Gracias por su compra!</div>
            <div>Tel: <?php echo esc_html(get_option('wp_pos_phone', '')); ?></div>
            <div><?php echo esc_html(get_option('wp_pos_address', '')); ?></div>
            <div style="margin-top: 5px;"><?php echo date_i18n('d/m/Y H:i'); ?></div>
            
            <div class="barcode">
                <div style="font-family: 'Libre Barcode 128', cursive; font-size: 36px; line-height: 1;">
                    *<?php echo esc_html($sale['id']); ?>*
                </div>
                <div style="font-size: 10px; margin-top: -5px;"><?php echo esc_html($sale['id']); ?></div>
            </div>
            
            <div style="font-size: 8px; margin-top: 10px;">
                <?php echo nl2br(esc_html(get_option('wp_pos_footer_text', ''))); ?>
            </div>
        </div>

        <button onclick="window.print()" class="btn-print no-print">Imprimir de nuevo</button>
        <button onclick="window.close()" class="btn-print no-print" style="background: #a00;">Cerrar</button>
    </div>
</body>
</html>
