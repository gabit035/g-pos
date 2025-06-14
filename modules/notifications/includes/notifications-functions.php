<?php
/**
 * Funciones del mu00f3dulo de notificaciones
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevenciu00f3n de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tipos de notificaciones disponibles
 */
define('WP_POS_NOTIFICATION_SUCCESS', 'success');
define('WP_POS_NOTIFICATION_INFO', 'info');
define('WP_POS_NOTIFICATION_WARNING', 'warning');
define('WP_POS_NOTIFICATION_ERROR', 'error');

/**
 * Contextos de notificaciones
 */
define('WP_POS_NOTIFICATION_CONTEXT_GLOBAL', 'global');
define('WP_POS_NOTIFICATION_CONTEXT_SALES', 'sales');
define('WP_POS_NOTIFICATION_CONTEXT_PRODUCTS', 'products');
define('WP_POS_NOTIFICATION_CONTEXT_STOCK', 'stock');

/**
 * Agregar una notificaciu00f3n
 *
 * @param string $message Mensaje de la notificaciu00f3n
 * @param string $type Tipo de notificaciu00f3n (success, info, warning, error)
 * @param array $args Argumentos adicionales
 * @return int ID de la notificaciu00f3n o false si falla
 */
function wp_pos_add_notification($message, $type = WP_POS_NOTIFICATION_INFO, $args = array()) {
    // Valores predeterminados
    $defaults = array(
        'title' => '',
        'icon' => _wp_pos_get_notification_icon($type),
        'persistent' => false,
        'dismissible' => true,
        'timeout' => ($type === WP_POS_NOTIFICATION_ERROR) ? 0 : 5000, // 0 = sin timeout
        'context' => WP_POS_NOTIFICATION_CONTEXT_GLOBAL,
        'metadata' => array() // Datos adicionales para la notificaciu00f3n
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // ID u00fanico para la notificaciu00f3n
    $notification_id = uniqid('notification_');
    
    // Crear la notificaciu00f3n
    $notification = array(
        'id' => $notification_id,
        'message' => $message,
        'type' => $type,
        'title' => $args['title'],
        'icon' => $args['icon'],
        'persistent' => $args['persistent'],
        'dismissible' => $args['dismissible'],
        'timeout' => $args['timeout'],
        'context' => $args['context'],
        'metadata' => $args['metadata'],
        'timestamp' => current_time('timestamp')
    );
    
    // Guardar notificaciu00f3n
    return _wp_pos_save_notification($notification) ? $notification_id : false;
}

/**
 * Agregar notificaciu00f3n de u00e9xito
 *
 * @param string $message Mensaje de la notificaciu00f3n
 * @param array $args Argumentos adicionales
 * @return int ID de la notificaciu00f3n
 */
function wp_pos_add_success_notification($message, $args = array()) {
    return wp_pos_add_notification($message, WP_POS_NOTIFICATION_SUCCESS, $args);
}

/**
 * Agregar notificaciu00f3n informativa
 *
 * @param string $message Mensaje de la notificaciu00f3n
 * @param array $args Argumentos adicionales
 * @return int ID de la notificaciu00f3n
 */
function wp_pos_add_info_notification($message, $args = array()) {
    return wp_pos_add_notification($message, WP_POS_NOTIFICATION_INFO, $args);
}

/**
 * Agregar notificaciu00f3n de advertencia
 *
 * @param string $message Mensaje de la notificaciu00f3n
 * @param array $args Argumentos adicionales
 * @return int ID de la notificaciu00f3n
 */
function wp_pos_add_warning_notification($message, $args = array()) {
    return wp_pos_add_notification($message, WP_POS_NOTIFICATION_WARNING, $args);
}

/**
 * Agregar notificaciu00f3n de error
 *
 * @param string $message Mensaje de la notificaciu00f3n
 * @param array $args Argumentos adicionales
 * @return int ID de la notificaciu00f3n
 */
function wp_pos_add_error_notification($message, $args = array()) {
    return wp_pos_add_notification($message, WP_POS_NOTIFICATION_ERROR, $args);
}

/**
 * Agregar notificación de stock insuficiente
 *
 * @param int $product_id ID del producto
 * @param string $product_name Nombre del producto
 * @param int $requested_quantity Cantidad solicitada
 * @param int $current_stock Stock actual
 * @return int|bool ID de la notificación o false si falla
 */
function wp_pos_add_insufficient_stock_notification($product_id, $product_name, $requested_quantity, $current_stock) {
    $message = sprintf(
        __('Stock insuficiente para "%s". Se intentaron vender %d unidades pero solo hay %d disponibles.', 'wp-pos'),
        $product_name,
        $requested_quantity,
        $current_stock
    );
    
    return wp_pos_add_notification(
        $message,
        WP_POS_NOTIFICATION_WARNING,
        array(
            'title' => __('Stock insuficiente', 'wp-pos'),
            'persistent' => true,
            'context' => WP_POS_NOTIFICATION_CONTEXT_SALES,
            'metadata' => array(
                'product_id' => $product_id,
                'requested_quantity' => $requested_quantity,
                'current_stock' => $current_stock
            )
        )
    );
}

/**
 * Agregar notificación de cumpleaños de cliente
 *
 * @param int $customer_id ID del cliente
 * @param string $customer_name Nombre del cliente
 * @param string $birthdate Fecha de nacimiento (YYYY-MM-DD)
 * @return int|bool ID de la notificación o false si falla
 */
function wp_pos_add_birthday_notification($customer_id, $customer_name, $birthdate) {
    $today = date('Y-m-d');
    $today_month_day = date('m-d');
    $birth_month_day = date('m-d', strtotime($birthdate));
    
    // Solo crear notificación si hoy es el cumpleaños
    if ($today_month_day !== $birth_month_day) {
        return false;
    }
    
    // Año actual - año de nacimiento = edad
    $birth_year = date('Y', strtotime($birthdate));
    $current_year = date('Y');
    $age = $current_year - $birth_year;
    
    $message = sprintf(
        __('¡Hoy es el cumpleaños de %s! Cumple %d años.', 'wp-pos'),
        $customer_name,
        $age
    );
    
    return wp_pos_add_notification(
        $message,
        WP_POS_NOTIFICATION_INFO,
        array(
            'title' => __('Cumpleaños de cliente', 'wp-pos'),
            'icon' => 'dashicons-cake',
            'persistent' => true,
            'dismissible' => true,
            'context' => WP_POS_NOTIFICATION_CONTEXT_GLOBAL,
            'metadata' => array(
                'customer_id' => $customer_id,
                'birthdate' => $birthdate,
                'date_notified' => $today
            )
        )
    );
}

/**
 * Agregar notificación de verificación diaria
 *
 * @param string $message Mensaje de la notificación
 * @param array $args Argumentos adicionales
 * @return int ID de la notificación
 */
function wp_pos_add_daily_check_notification($message, $args = array()) {
    return wp_pos_add_notification($message, WP_POS_NOTIFICATION_INFO, $args);
}

/**
 * Agregar notificación de stock bajo
 * Agregar notificaciu00f3n de stock bajo
 *
 * @param int $product_id ID del producto
 * @param string $product_name Nombre del producto
 * @param int $stock Stock actual disponible
 * @param int $threshold Umbral de stock bajo
 * @return int ID de la notificaciu00f3n
 */
function wp_pos_add_low_stock_notification($product_id, $product_name, $stock, $threshold) {
    $message = sprintf(
        'El producto "<strong>%s</strong>" tiene un stock bajo (<strong>%d unidades</strong>). Considera reponer inventario pronto.',
        esc_html($product_name),
        $stock
    );
    
    return wp_pos_add_notification($message, WP_POS_NOTIFICATION_WARNING, array(
        'title' => 'Stock Bajo',
        'icon' => 'dashicons-warning',
        'context' => WP_POS_NOTIFICATION_CONTEXT_STOCK,
        'persistent' => true,
        'metadata' => array(
            'product_id' => $product_id,
            'stock' => $stock,
            'threshold' => $threshold
        )
    ));
}

/**
 * Agregar notificaciu00f3n de stock agotado
 *
 * @param int $product_id ID del producto
 * @param string $product_name Nombre del producto
 * @param int $stock Stock actual disponible
 * @return int ID de la notificaciu00f3n
 */
function wp_pos_add_out_of_stock_notification($product_id, $product_name, $stock) {
    $message = sprintf(
        'El producto "<strong>%s</strong>" se ha <strong>agotado</strong> (stock: %d). Repone inventario lo antes posible.',
        esc_html($product_name),
        $stock
    );
    
    return wp_pos_add_notification($message, WP_POS_NOTIFICATION_ERROR, array(
        'title' => 'Stock Agotado',
        'icon' => 'dashicons-dismiss',
        'context' => WP_POS_NOTIFICATION_CONTEXT_STOCK,
        'persistent' => true,
        'metadata' => array(
            'product_id' => $product_id,
            'stock' => $stock
        )
    ));
}

/**
 * Obtener todas las notificaciones
 *
 * @param string $context Filtrar por contexto especu00edfico (opcional)
 * @return array Lista de notificaciones
 */
function wp_pos_get_notifications($context = null) {
    // Obtener notificaciones persistentes de la base de datos
    $persistent_notifications = get_option('wp_pos_notifications', array());
    
    // Obtener notificaciones de sesión
    $session_notifications = isset($_SESSION['wp_pos_notifications']) ? $_SESSION['wp_pos_notifications'] : array();
    
    // Combinar ambos tipos de notificaciones
    $notifications = array_merge($persistent_notifications, $session_notifications);
    
    // Ordenar por timestamp, más recientes primero
    usort($notifications, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    // Filtrar por contexto si se especifica
    if ($context !== null) {
        $notifications = array_filter($notifications, function($notification) use ($context) {
            return $notification['context'] === $context || $notification['context'] === WP_POS_NOTIFICATION_CONTEXT_GLOBAL;
        });
    }
    
    return $notifications;
}

/**
 * Obtener nu00famero de notificaciones
 *
 * @param string $context Filtrar por contexto especu00edfico (opcional)
 * @return int Nu00famero de notificaciones
 */
function wp_pos_get_notifications_count($context = null) {
    return count(wp_pos_get_notifications($context));
}

/**
 * Descartar una notificaciu00f3n
 *
 * @param string $notification_id ID de la notificaciu00f3n
 * @return bool u00c9xito de la operaciu00f3n
 */
function wp_pos_dismiss_notification($notification_id) {
    $notifications = get_option('wp_pos_notifications', array());
    
    foreach ($notifications as $key => $notification) {
        if ($notification['id'] === $notification_id) {
            unset($notifications[$key]);
            update_option('wp_pos_notifications', array_values($notifications));
            return true;
        }
    }
    
    return false;
}

/**
 * Mostrar notificaciones en HTML
 *
 * @param string $context Filtrar por contexto especu00edfico (opcional)
 */
function wp_pos_display_notifications($context = null) {
    $notifications = wp_pos_get_notifications($context);
    
    if (empty($notifications)) {
        return;
    }
    
    echo '<div class="wp-pos-notifications-container">';
    
    foreach ($notifications as $notification) {
        echo _wp_pos_render_notification($notification);
    }
    
    echo '</div>';
    
    // Asegurar que los scripts y estilos estu00e1n encolados
    wp_enqueue_style('wp-pos-notifications');
    wp_enqueue_script('wp-pos-notifications');
    
    // En caso de que no estu00e9n registrados au00fan
    add_action('admin_footer', function() {
        if (!wp_script_is('wp-pos-notifications', 'registered')) {
            _wp_pos_inline_notification_assets();
        }
    });
}

/**
 * Guardar notificaciu00f3n
 *
 * @param array $notification Datos de la notificaciu00f3n
 * @return bool u00c9xito de la operaciu00f3n
 */
function _wp_pos_save_notification($notification) {
    $notifications = get_option('wp_pos_notifications', array());
    
    // Si no es persistente, solo guardar en sesiÃ³n
    if (!$notification['persistent']) {
        _wp_pos_add_session_notification($notification);
        return true;
    }
    
    // Verificar si existe una notificaciu00f3n similar (mismo producto y contexto)
    if (!empty($notification['metadata']['product_id'])) {
        foreach ($notifications as $key => $existing) {
            if (!empty($existing['metadata']['product_id']) &&
                $existing['metadata']['product_id'] === $notification['metadata']['product_id'] &&
                $existing['context'] === $notification['context'] &&
                $existing['type'] === $notification['type']) {
                    
                // Actualizar notificaciu00f3n existente
                $notifications[$key] = $notification;
                update_option('wp_pos_notifications', $notifications);
                return true;
            }
        }
    }
    
    // Agregar nueva notificaciu00f3n
    $notifications[] = $notification;
    return update_option('wp_pos_notifications', $notifications);
}

/**
 * Agregar notificaciu00f3n temporal de sesiÃ³n
 *
 * @param array $notification Datos de la notificaciu00f3n
 */
function _wp_pos_add_session_notification($notification) {
    if (!isset($_SESSION['wp_pos_notifications'])) {
        $_SESSION['wp_pos_notifications'] = array();
    }
    
    $_SESSION['wp_pos_notifications'][] = $notification;
}

/**
 * Renderizar una notificaciu00f3n en HTML
 *
 * @param array $notification Datos de la notificaciu00f3n
 * @return string HTML de la notificaciu00f3n
 */
function _wp_pos_render_notification($notification) {
    $type_class = 'wp-pos-notification-' . $notification['type'];
    $dismissible_class = $notification['dismissible'] ? 'wp-pos-notification-dismissible' : '';
    
    $html = sprintf(
        '<div class="wp-pos-notification %s %s" data-notification-id="%s" data-timeout="%d">',
        esc_attr($type_class),
        esc_attr($dismissible_class),
        esc_attr($notification['id']),
        esc_attr($notification['timeout'])
    );
    
    // Contenido de la notificaciu00f3n
    $html .= '<div class="wp-pos-notification-content">';
    
    // Icono
    if (!empty($notification['icon'])) {
        $html .= sprintf('<div class="wp-pos-notification-icon"><span class="dashicons %s"></span></div>', esc_attr($notification['icon']));
    }
    
    // Mensaje
    $html .= '<div class="wp-pos-notification-message">';
    
    // Tu00edtulo (opcional)
    if (!empty($notification['title'])) {
        $html .= sprintf('<div class="wp-pos-notification-title">%s</div>', esc_html($notification['title']));
    }
    
    // Texto del mensaje
    $html .= sprintf('<div class="wp-pos-notification-text">%s</div>', wp_kses_post($notification['message']));
    $html .= '</div>'; // Cierre de wp-pos-notification-message
    $html .= '</div>'; // Cierre de wp-pos-notification-content
    
    // Botu00f3n para cerrar
    if ($notification['dismissible']) {
        $html .= '<button type="button" class="wp-pos-notification-dismiss" aria-label="Cerrar">';
        $html .= '<span class="dashicons dashicons-no-alt"></span>';
        $html .= '</button>';
    }
    
    $html .= '</div>'; // Cierre de wp-pos-notification
    
    return $html;
}

/**
 * Obtener u00edcono predeterminado segu00fan tipo de notificaciu00f3n
 *
 * @param string $type Tipo de notificaciu00f3n
 * @return string Clase CSS del u00edcono
 */
function _wp_pos_get_notification_icon($type) {
    switch ($type) {
        case WP_POS_NOTIFICATION_SUCCESS:
            return 'dashicons-yes-alt';
        case WP_POS_NOTIFICATION_WARNING:
            return 'dashicons-warning';
        case WP_POS_NOTIFICATION_ERROR:
            return 'dashicons-dismiss';
        case WP_POS_NOTIFICATION_INFO:
        default:
            return 'dashicons-info';
    }
}

/**
 * Cargar estilos y scripts inline cuando no estu00e1n registrados
 */
function _wp_pos_inline_notification_assets() {
    echo '<style>
        /* Contenedor principal de notificaciones */
        .wp-pos-notifications-container {
            position: fixed;
            top: 32px;
            right: 20px;
            z-index: 9999;
            width: 350px;
            max-width: 90vw;
        }
        
        /* Estilo base de notificaciu00f3n */
        .wp-pos-notification {
            margin-bottom: 10px;
            padding: 12px 15px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            position: relative;
            animation: wp-pos-notification-in 0.3s ease;
            overflow: hidden;
        }
        
        /* Animaciu00f3n de entrada */
        @keyframes wp-pos-notification-in {
            from {
                opacity: 0;
                transform: translateX(40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Animaciu00f3n de salida */
        @keyframes wp-pos-notification-out {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(40px);
            }
        }
        
        .wp-pos-notification.wp-pos-notification-removing {
            animation: wp-pos-notification-out 0.3s ease forwards;
        }
        
        /* Estilos de contenido */
        .wp-pos-notification-content {
            display: flex;
            align-items: flex-start;
        }
        
        .wp-pos-notification-icon {
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .wp-pos-notification-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }
        
        .wp-pos-notification-message {
            flex-grow: 1;
        }
        
        .wp-pos-notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 14px;
        }
        
        .wp-pos-notification-text {
            font-size: 13px;
            line-height: 1.5;
        }
        
        /* Botu00f3n para cerrar */
        .wp-pos-notification-dismiss {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        
        .wp-pos-notification-dismiss:hover {
            opacity: 1;
        }
        
        .wp-pos-notification-dismiss .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        
        /* Tipos de notificaciones */
        .wp-pos-notification-success {
            background-color: #ecf9f0;
            border-left: 4px solid #2ecc71;
        }
        
        .wp-pos-notification-success .wp-pos-notification-icon .dashicons {
            color: #2ecc71;
        }
        
        .wp-pos-notification-info {
            background-color: #e6f3ff;
            border-left: 4px solid #3498db;
        }
        
        .wp-pos-notification-info .wp-pos-notification-icon .dashicons {
            color: #3498db;
        }
        
        .wp-pos-notification-warning {
            background-color: #fef7e9;
            border-left: 4px solid #f39c12;
        }
        
        .wp-pos-notification-warning .wp-pos-notification-icon .dashicons {
            color: #f39c12;
        }
        
        .wp-pos-notification-error {
            background-color: #feebe9;
            border-left: 4px solid #e74c3c;
        }
        
        .wp-pos-notification-error .wp-pos-notification-icon .dashicons {
            color: #e74c3c;
        }
        
        /* Estilos para listado de notificaciones */
        .wp-pos-notifications-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .wp-pos-notification-list-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
        }
        
        .wp-pos-notification-list-item:last-child {
            border-bottom: none;
        }
        
        .wp-pos-notification-list-item .wp-pos-notification-icon {
            margin-right: 15px;
        }
        
        .wp-pos-notification-list-item .wp-pos-notification-title {
            margin-bottom: 5px;
            font-size: 15px;
        }
        
        .wp-pos-notification-list-item .wp-pos-notification-text {
            margin-bottom: 10px;
        }
        
        .wp-pos-notification-list-item .wp-pos-notification-meta {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .wp-pos-no-notifications {
            text-align: center;
            padding: 40px 20px;
        }
        
        .wp-pos-no-notifications .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: #2ecc71;
            margin-bottom: 15px;
        }
    </style>';
    
    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            // Manejar cierre de notificaciones
            $(document).on("click", ".wp-pos-notification-dismiss", function() {
                var notification = $(this).closest(".wp-pos-notification");
                var id = notification.data("notification-id");
                
                // Animar salida
                notification.addClass("wp-pos-notification-removing");
                
                // Remover despuu00e9s de la animaciu00f3n
                setTimeout(function() {
                    notification.remove();
                }, 300);
                
                // Enviar petici\u00f3n AJAX para eliminar notificaciu00f3n persistente
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "wp_pos_dismiss_notification",
                        notification_id: id,
                        security: wp_pos_nonce
                    }
                });
            });
            
            // Auto-cierre para notificaciones con timeout
            $(".wp-pos-notification").each(function() {
                var notification = $(this);
                var timeout = notification.data("timeout");
                
                if (timeout > 0) {
                    setTimeout(function() {
                        // Animar salida
                        notification.addClass("wp-pos-notification-removing");
                        
                        // Remover despuu00e9s de la animaciu00f3n
                        setTimeout(function() {
                            notification.remove();
                        }, 300);
                    }, timeout);
                }
            });
        });
    </script>';
}
