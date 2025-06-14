<?php
/**
 * Grupos de configuraciones predeterminados
 *
 * @package WP-POS
 * @subpackage Settings
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar grupos de configuraciones predeterminados
 *
 * @since 1.0.0
 * @return array Grupos de configuraciones
 */
function wp_pos_get_default_settings_groups() {
    $groups = array();
    
    // Grupo General
    $general_group = new WP_POS_Settings_Group(
        'general',
        __('General', 'wp-pos'),
        __('Configuraciones generales del punto de venta', 'wp-pos'),
        'dashicons-admin-generic'
    );
    
    // Agregar campos al grupo general
    $general_group->add_field(
        'enabled',
        new WP_POS_Settings_Field(
            __('Activar Punto de Venta', 'wp-pos'),
            'checkbox',
            array(
                'default' => 'yes',
                'description' => __('Habilitar el sistema de punto de venta', 'wp-pos'),
                'options' => array(
                    'checkbox_label' => __('Activar', 'wp-pos')
                )
            )
        )
    );
    
    $general_group->add_field(
        'store_name',
        new WP_POS_Settings_Field(
            __('Nombre de la Tienda', 'wp-pos'),
            'text',
            array(
                'default' => get_bloginfo('name'),
                'description' => __('Este nombre apareceru00e1 en los recibos y pantallas del punto de venta', 'wp-pos'),
                'options' => array(
                    'placeholder' => __('Ingrese el nombre de su tienda', 'wp-pos')
                )
            )
        )
    );
    
    $general_group->add_field(
        'store_address',
        new WP_POS_Settings_Field(
            __('Direcciu00f3n de la Tienda', 'wp-pos'),
            'textarea',
            array(
                'default' => '',
                'description' => __('Esta direcciu00f3n apareceru00e1 en los recibos', 'wp-pos'),
                'options' => array(
                    'placeholder' => __('Ingrese la direcciu00f3n de su tienda', 'wp-pos'),
                    'rows' => 3
                )
            )
        )
    );
    
    $general_group->add_field(
        'store_phone',
        new WP_POS_Settings_Field(
            __('Telu00e9fono de la Tienda', 'wp-pos'),
            'text',
            array(
                'default' => '',
                'description' => __('Este telu00e9fono apareceru00e1 en los recibos', 'wp-pos'),
                'options' => array(
                    'placeholder' => __('Ingrese el telu00e9fono de su tienda', 'wp-pos')
                )
            )
        )
    );
    
    $general_group->add_field(
        'store_email',
        new WP_POS_Settings_Field(
            __('Email de la Tienda', 'wp-pos'),
            'email',
            array(
                'default' => get_bloginfo('admin_email'),
                'description' => __('Este email se usaru00e1 para notificaciones y recibos', 'wp-pos'),
                'options' => array(
                    'placeholder' => __('correo@ejemplo.com', 'wp-pos')
                )
            )
        )
    );
    
    $general_group->add_field(
        'store_logo',
        new WP_POS_Settings_Field(
            __('Logo de la Tienda', 'wp-pos'),
            'image',
            array(
                'default' => '',
                'description' => __('Este logo apareceru00e1 en la pantalla del punto de venta y en los recibos', 'wp-pos'),
                'options' => array(
                    'button_text' => __('Seleccionar Logo', 'wp-pos'),
                    'preview_size' => 'medium'
                )
            )
        )
    );
    
    $groups['general'] = $general_group;
    
    // Grupo de Venta
    $sales_group = new WP_POS_Settings_Group(
        'sales',
        __('Ventas', 'wp-pos'),
        __('Configuraciones relacionadas con el proceso de venta', 'wp-pos'),
        'dashicons-cart'
    );
    
    $sales_group->add_field(
        'default_customer',
        new WP_POS_Settings_Field(
            __('Cliente Predeterminado', 'wp-pos'),
            'select',
            array(
                'default' => 'guest',
                'description' => __('Cliente que se utilizaru00e1 por defecto para nuevas ventas', 'wp-pos'),
                'options' => array(
                    'choices' => array(
                        'guest' => __('Cliente Invitado', 'wp-pos'),
                        'search' => __('Buscar Cliente', 'wp-pos')
                    )
                )
            )
        )
    );
    
    $sales_group->add_field(
        'tax_calculation',
        new WP_POS_Settings_Field(
            __('Cu00e1lculo de Impuestos', 'wp-pos'),
            'select',
            array(
                'default' => 'standard',
                'description' => __('Mu00e9todo de cu00e1lculo de impuestos para las ventas', 'wp-pos'),
                'options' => array(
                    'choices' => array(
                        'standard' => __('Estu00e1ndar (usar configuraciones de WooCommerce)', 'wp-pos'),
                        'inclusive' => __('Incluir impuestos en los precios', 'wp-pos'),
                        'exclusive' => __('Au00f1adir impuestos a los precios', 'wp-pos')
                    )
                )
            )
        )
    );
    
    $sales_group->add_field(
        'allow_discount',
        new WP_POS_Settings_Field(
            __('Permitir Descuentos', 'wp-pos'),
            'checkbox',
            array(
                'default' => 'yes',
                'description' => __('Permitir que los operadores apliquen descuentos manuales', 'wp-pos'),
                'options' => array(
                    'checkbox_label' => __('Permitir', 'wp-pos')
                )
            )
        )
    );
    
    $sales_group->add_field(
        'max_discount',
        new WP_POS_Settings_Field(
            __('Descuento Mu00e1ximo', 'wp-pos'),
            'number',
            array(
                'default' => 100,
                'description' => __('Porcentaje mu00e1ximo de descuento permitido (0-100)', 'wp-pos'),
                'options' => array(
                    'min' => 0,
                    'max' => 100,
                    'step' => 1
                ),
                'conditions' => array(
                    'sales_allow_discount' => array(
                        'operator' => '==',
                        'value' => 'yes'
                    )
                )
            )
        )
    );
    
    $sales_group->add_field(
        'receipt_template',
        new WP_POS_Settings_Field(
            __('Plantilla de Recibo', 'wp-pos'),
            'editor',
            array(
                'default' => wp_pos_get_default_receipt_template(),
                'description' => __('Plantilla HTML para los recibos. Usa las variables disponibles entre llaves dobles, ej: {{order_id}}', 'wp-pos'),
                'options' => array(
                    'editor_settings' => array(
                        'media_buttons' => false,
                        'textarea_rows' => 15
                    )
                )
            )
        )
    );
    
    $sales_group->add_field(
        'order_status',
        new WP_POS_Settings_Field(
            __('Estado de Pedido', 'wp-pos'),
            'order_statuses',
            array(
                'default' => 'wc-completed',
                'description' => __('Estado que se asignaru00e1 automU00E1ticamente a los pedidos completados', 'wp-pos')
            )
        )
    );
    
    $groups['sales'] = $sales_group;
    
    // Grupo de Pagos
    $payment_group = new WP_POS_Settings_Group(
        'payment',
        __('Pagos', 'wp-pos'),
        __('Configuraciones de mu00e9todos de pago', 'wp-pos'),
        'dashicons-money-alt'
    );
    
    $payment_group->add_field(
        'payment_methods',
        new WP_POS_Settings_Field(
            __('Mu00e9todos de Pago', 'wp-pos'),
            'select',
            array(
                'default' => array('cash', 'card'),
                'description' => __('Selecciona los mu00e9todos de pago disponibles en el punto de venta', 'wp-pos'),
                'options' => array(
                    'choices' => array(
                        'cash' => __('Efectivo', 'wp-pos'),
                        'card' => __('Tarjeta', 'wp-pos'),
                        'transfer' => __('Transferencia Bancaria', 'wp-pos'),
                        'cheque' => __('Cheque', 'wp-pos'),
                        'credit' => __('Cru00e9dito', 'wp-pos')
                    ),
                    'multiple' => true
                )
            )
        )
    );
    
    $payment_group->add_field(
        'default_payment',
        new WP_POS_Settings_Field(
            __('Mu00e9todo Predeterminado', 'wp-pos'),
            'select',
            array(
                'default' => 'cash',
                'description' => __('Mu00e9todo de pago predeterminado para nuevas ventas', 'wp-pos'),
                'options' => array(
                    'choices' => array(
                        'cash' => __('Efectivo', 'wp-pos'),
                        'card' => __('Tarjeta', 'wp-pos'),
                        'transfer' => __('Transferencia Bancaria', 'wp-pos'),
                        'cheque' => __('Cheque', 'wp-pos'),
                        'credit' => __('Cru00e9dito', 'wp-pos')
                    )
                )
            )
        )
    );
    
    $payment_group->add_field(
        'allow_partial',
        new WP_POS_Settings_Field(
            __('Pagos Parciales', 'wp-pos'),
            'checkbox',
            array(
                'default' => 'no',
                'description' => __('Permitir pagos parciales en ventas', 'wp-pos'),
                'options' => array(
                    'checkbox_label' => __('Permitir', 'wp-pos')
                )
            )
        )
    );
    
    $payment_group->add_field(
        'change_rounding',
        new WP_POS_Settings_Field(
            __('Redondeo de Cambio', 'wp-pos'),
            'select',
            array(
                'default' => 'none',
                'description' => __('Cu00f3mo redondear el cambio en pagos en efectivo', 'wp-pos'),
                'options' => array(
                    'choices' => array(
                        'none' => __('Sin redondeo', 'wp-pos'),
                        'up_5' => __('Hacia arriba al mu00faltiplo de 5', 'wp-pos'),
                        'up_10' => __('Hacia arriba al mu00faltiplo de 10', 'wp-pos'),
                        'down_5' => __('Hacia abajo al mu00faltiplo de 5', 'wp-pos'),
                        'down_10' => __('Hacia abajo al mu00faltiplo de 10', 'wp-pos')
                    )
                )
            )
        )
    );
    
    $groups['payment'] = $payment_group;
    
    // Grupo de Impresiu00f3n
    $printing_group = new WP_POS_Settings_Group(
        'printing',
        __('Impresiu00f3n', 'wp-pos'),
        __('Configuraciones de impresiu00f3n de recibos', 'wp-pos'),
        'dashicons-printer'
    );
    
    $printing_group->add_field(
        'receipt_printer',
        new WP_POS_Settings_Field(
            __('Impresora de Recibos', 'wp-pos'),
            'select',
            array(
                'default' => 'browser',
                'description' => __('Selecciona el tipo de impresiu00f3n para recibos', 'wp-pos'),
                'options' => array(
                    'choices' => array(
                        'browser' => __('Imprimir desde navegador', 'wp-pos'),
                        'thermal' => __('Impresora tu00e9rmica', 'wp-pos'),
                        'network' => __('Impresora de red', 'wp-pos'),
                        'none' => __('No imprimir recibos', 'wp-pos')
                    )
                )
            )
        )
    );
    
    $printing_group->add_field(
        'printer_ip',
        new WP_POS_Settings_Field(
            __('IP de Impresora', 'wp-pos'),
            'text',
            array(
                'default' => '192.168.1.100',
                'description' => __('Direcciu00f3n IP de la impresora de red', 'wp-pos'),
                'options' => array(
                    'placeholder' => __('Ej: 192.168.1.100', 'wp-pos')
                ),
                'conditions' => array(
                    'printing_receipt_printer' => array(
                        'operator' => '==',
                        'value' => 'network'
                    )
                )
            )
        )
    );
    
    $printing_group->add_field(
        'printer_port',
        new WP_POS_Settings_Field(
            __('Puerto de Impresora', 'wp-pos'),
            'number',
            array(
                'default' => 9100,
                'description' => __('Puerto de la impresora de red', 'wp-pos'),
                'options' => array(
                    'min' => 1,
                    'max' => 65535,
                    'step' => 1
                ),
                'conditions' => array(
                    'printing_receipt_printer' => array(
                        'operator' => '==',
                        'value' => 'network'
                    )
                )
            )
        )
    );
    
    $printing_group->add_field(
        'auto_print',
        new WP_POS_Settings_Field(
            __('Impresiu00f3n Automática', 'wp-pos'),
            'checkbox',
            array(
                'default' => 'yes',
                'description' => __('Imprimir recibos automáticamente al completar una venta', 'wp-pos'),
                'options' => array(
                    'checkbox_label' => __('Activar', 'wp-pos')
                ),
                'conditions' => array(
                    'printing_receipt_printer' => array(
                        'operator' => '!=',
                        'value' => 'none'
                    )
                )
            )
        )
    );
    
    $printing_group->add_field(
        'print_logo',
        new WP_POS_Settings_Field(
            __('Imprimir Logo', 'wp-pos'),
            'checkbox',
            array(
                'default' => 'yes',
                'description' => __('Incluir el logo de la tienda en los recibos impresos', 'wp-pos'),
                'options' => array(
                    'checkbox_label' => __('Incluir', 'wp-pos')
                ),
                'conditions' => array(
                    'printing_receipt_printer' => array(
                        'operator' => '!=',
                        'value' => 'none'
                    )
                )
            )
        )
    );
    
    $printing_group->add_field(
        'receipt_width',
        new WP_POS_Settings_Field(
            __('Ancho del Recibo', 'wp-pos'),
            'number',
            array(
                'default' => 80,
                'description' => __('Ancho del recibo en milímetros', 'wp-pos'),
                'options' => array(
                    'min' => 58,
                    'max' => 210,
                    'step' => 1
                ),
                'conditions' => array(
                    'printing_receipt_printer' => array(
                        'operator' => '!=',
                        'value' => 'none'
                    )
                )
            )
        )
    );
    
    $printing_group->add_field(
        'test_print',
        new WP_POS_Settings_Field(
            __('Prueba de Impresiu00f3n', 'wp-pos'),
            'html',
            array(
                'options' => array(
                    'html' => '<button type="button" class="button button-secondary wp-pos-test-print">' . __('Imprimir Recibo de Prueba', 'wp-pos') . '</button>'
                ),
                'conditions' => array(
                    'printing_receipt_printer' => array(
                        'operator' => '!=',
                        'value' => 'none'
                    )
                )
            )
        )
    );
    
    $groups['printing'] = $printing_group;
    
    // Aplicar filtro para permitir que otros complementos au00f1adan sus propios grupos
    return apply_filters('wp_pos_settings_groups', $groups);
}

/**
 * Obtener la plantilla predeterminada de recibos
 *
 * @since 1.0.0
 * @return string Plantilla HTML
 */
function wp_pos_get_default_receipt_template() {
    $template = '';
    $template .= '<div class="wp-pos-receipt">';
    $template .= '<div class="wp-pos-receipt-header">';
    $template .= '<h1>{{store_name}}</h1>';
    $template .= '<p>{{store_address}}</p>';
    $template .= '<p>{{store_phone}}</p>';
    $template .= '</div>';
    
    $template .= '<div class="wp-pos-receipt-info">';
    $template .= '<p><strong>' . __('Recibo #', 'wp-pos') . '</strong> {{order_id}}</p>';
    $template .= '<p><strong>' . __('Fecha:', 'wp-pos') . '</strong> {{order_date}}</p>';
    $template .= '<p><strong>' . __('Operador:', 'wp-pos') . '</strong> {{cashier_name}}</p>';
    $template .= '</div>';
    
    $template .= '<div class="wp-pos-receipt-items">';
    $template .= '<table width="100%">';
    $template .= '<thead>';
    $template .= '<tr>';
    $template .= '<th>' . __('Producto', 'wp-pos') . '</th>';
    $template .= '<th>' . __('Cant', 'wp-pos') . '</th>';
    $template .= '<th>' . __('Precio', 'wp-pos') . '</th>';
    $template .= '<th>' . __('Total', 'wp-pos') . '</th>';
    $template .= '</tr>';
    $template .= '</thead>';
    $template .= '<tbody>';
    $template .= '{{items}}';
    $template .= '</tbody>';
    $template .= '</table>';
    $template .= '</div>';
    
    $template .= '<div class="wp-pos-receipt-totals">';
    $template .= '<p><strong>' . __('Subtotal:', 'wp-pos') . '</strong> {{subtotal}}</p>';
    $template .= '<p><strong>' . __('Descuento:', 'wp-pos') . '</strong> {{discount}}</p>';
    $template .= '<p><strong>' . __('Impuestos:', 'wp-pos') . '</strong> {{tax}}</p>';
    $template .= '<p class="wp-pos-receipt-total"><strong>' . __('TOTAL:', 'wp-pos') . '</strong> {{total}}</p>';
    $template .= '</div>';
    
    $template .= '<div class="wp-pos-receipt-payment">';
    $template .= '<p><strong>' . __('Pago:', 'wp-pos') . '</strong> {{payment_method}}</p>';
    $template .= '<p><strong>' . __('Pagado:', 'wp-pos') . '</strong> {{amount_paid}}</p>';
    $template .= '<p><strong>' . __('Cambio:', 'wp-pos') . '</strong> {{change}}</p>';
    $template .= '</div>';
    
    $template .= '<div class="wp-pos-receipt-footer">';
    $template .= '<p>{{store_email}}</p>';
    $template .= '<p>' . __('Gracias por su compra', 'wp-pos') . '</p>';
    $template .= '</div>';
    $template .= '</div>';
    
    return $template;
}
