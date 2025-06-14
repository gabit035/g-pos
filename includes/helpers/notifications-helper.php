<?php
/**
 * Funciones auxiliares para notificaciones generales del sistema
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevención de acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar notificaciones del sistema
 * 
 * @since 1.0.0
 */
class WP_POS_Notifications {
    
    /**
     * Tipos de notificaciones disponibles
     */
    const TYPE_SUCCESS = 'success';
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    
    /**
     * Almacén temporal de notificaciones
     */
    private static $notifications = array();
    
    /**
     * ID único para cada notificación
     */
    private static $id_counter = 0;
    
    /**
     * Agregar una notificación
     *
     * @param string $message Mensaje de la notificación
     * @param string $type Tipo de notificación (success, info, warning, error)
     * @param array $args Argumentos adicionales (título, icono, persistente)
     * @return int ID de la notificación creada
     */
    public static function add($message, $type = self::TYPE_INFO, $args = array()) {
        // Incrementar contador de IDs
        self::$id_counter++;
        
        // Valores predeterminados para argumentos opcionales
        $defaults = array(
            'title' => '',
            'icon' => self::get_default_icon($type),
            'persistent' => false,
            'dismissible' => true,
            'timeout' => $type === self::TYPE_ERROR ? 0 : 5000, // 0 = sin timeout (permanente)
            'context' => 'global' // Para filtrar notificaciones por contexto
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Crear la notificación
        $notification = array(
            'id' => self::$id_counter,
            'message' => $message,
            'type' => $type,
            'title' => $args['title'],
            'icon' => $args['icon'],
            'persistent' => $args['persistent'],
            'dismissible' => $args['dismissible'],
            'timeout' => $args['timeout'],
            'context' => $args['context'],
            'timestamp' => current_time('timestamp')
        );
        
        // Almacenar la notificación
        self::$notifications[] = $notification;
        
        // Si es persistente, guardarla en opciones
        if ($args['persistent']) {
            self::save_persistent_notification($notification);
        }
        
        return self::$id_counter;
    }
    
    /**
     * Agregar notificación de éxito
     *
     * @param string $message Mensaje de la notificación
     * @param array $args Argumentos adicionales
     * @return int ID de la notificación
     */
    public static function success($message, $args = array()) {
        return self::add($message, self::TYPE_SUCCESS, $args);
    }
    
    /**
     * Agregar notificación informativa
     *
     * @param string $message Mensaje de la notificación
     * @param array $args Argumentos adicionales
     * @return int ID de la notificación
     */
    public static function info($message, $args = array()) {
        return self::add($message, self::TYPE_INFO, $args);
    }
    
    /**
     * Agregar notificación de advertencia
     *
     * @param string $message Mensaje de la notificación
     * @param array $args Argumentos adicionales
     * @return int ID de la notificación
     */
    public static function warning($message, $args = array()) {
        return self::add($message, self::TYPE_WARNING, $args);
    }
    
    /**
     * Agregar notificación de error
     *
     * @param string $message Mensaje de la notificación
     * @param array $args Argumentos adicionales
     * @return int ID de la notificación
     */
    public static function error($message, $args = array()) {
        return self::add($message, self::TYPE_ERROR, $args);
    }
    
    /**
     * Obtener todas las notificaciones
     *
     * @param string $context Filtrar por contexto específico (opcional)
     * @return array Lista de notificaciones
     */
    public static function get_all($context = null) {
        // Cargar notificaciones persistentes guardadas
        $persistent = self::get_persistent_notifications();
        
        // Combinar con las notificaciones de esta sesión
        $all_notifications = array_merge($persistent, self::$notifications);
        
        // Filtrar por contexto si se especifica
        if ($context !== null) {
            $all_notifications = array_filter($all_notifications, function($notification) use ($context) {
                return $notification['context'] === $context || $notification['context'] === 'global';
            });
        }
        
        return $all_notifications;
    }
    
    /**
     * Renderizar notificaciones HTML
     *
     * @param string $context Filtrar por contexto específico (opcional)
     * @return string HTML de las notificaciones
     */
    public static function render($context = null) {
        $notifications = self::get_all($context);
        
        if (empty($notifications)) {
            return '';
        }
        
        $html = '<div class="wp-pos-notifications-container">';
        
        foreach ($notifications as $notification) {
            $html .= self::render_notification($notification);
        }
        
        $html .= '</div>';
        
        // Agregar scripts y estilos necesarios
        self::enqueue_assets();
        
        return $html;
    }
    
    /**
     * Renderizar una notificación individual
     *
     * @param array $notification Datos de la notificación
     * @return string HTML de la notificación
     */
    private static function render_notification($notification) {
        $type_class = 'wp-pos-notification-' . $notification['type'];
        $dismissible_class = $notification['dismissible'] ? 'wp-pos-notification-dismissible' : '';
        
        $html = sprintf(
            '<div class="wp-pos-notification %s %s" data-notification-id="%d" data-timeout="%d">',
            esc_attr($type_class),
            esc_attr($dismissible_class),
            esc_attr($notification['id']),
            esc_attr($notification['timeout'])
        );
        
        // Contenido de la notificación
        $html .= '<div class="wp-pos-notification-content">';
        
        // Icono
        if (!empty($notification['icon'])) {
            $html .= sprintf('<div class="wp-pos-notification-icon"><span class="dashicons %s"></span></div>', esc_attr($notification['icon']));
        }
        
        // Mensaje
        $html .= '<div class="wp-pos-notification-message">';
        
        // Título (opcional)
        if (!empty($notification['title'])) {
            $html .= sprintf('<div class="wp-pos-notification-title">%s</div>', esc_html($notification['title']));
        }
        
        // Texto del mensaje
        $html .= sprintf('<div class="wp-pos-notification-text">%s</div>', wp_kses_post($notification['message']));
        $html .= '</div>'; // Cierre de wp-pos-notification-message
        $html .= '</div>'; // Cierre de wp-pos-notification-content
        
        // Botón para cerrar
        if ($notification['dismissible']) {
            $html .= '<button type="button" class="wp-pos-notification-dismiss" aria-label="Cerrar">';
            $html .= '<span class="dashicons dashicons-no-alt"></span>';
            $html .= '</button>';
        }
        
        $html .= '</div>'; // Cierre de wp-pos-notification
        
        return $html;
    }
    
    /**
     * Guardar notificación persistente
     *
     * @param array $notification Datos de la notificación
     */
    private static function save_persistent_notification($notification) {
        $persistent = get_option('wp_pos_persistent_notifications', array());
        $persistent[] = $notification;
        update_option('wp_pos_persistent_notifications', $persistent);
    }
    
    /**
     * Obtener notificaciones persistentes guardadas
     *
     * @return array Lista de notificaciones persistentes
     */
    private static function get_persistent_notifications() {
        return get_option('wp_pos_persistent_notifications', array());
    }
    
    /**
     * Eliminar una notificación por ID
     *
     * @param int $id ID de la notificación a eliminar
     * @return bool Éxito de la operación
     */
    public static function dismiss($id) {
        // Buscar en notificaciones actuales
        foreach (self::$notifications as $key => $notification) {
            if ($notification['id'] == $id) {
                unset(self::$notifications[$key]);
                return true;
            }
        }
        
        // Buscar en notificaciones persistentes
        $persistent = self::get_persistent_notifications();
        
        foreach ($persistent as $key => $notification) {
            if ($notification['id'] == $id) {
                unset($persistent[$key]);
                update_option('wp_pos_persistent_notifications', $persistent);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtener ícono predeterminado según tipo de notificación
     *
     * @param string $type Tipo de notificación
     * @return string Clase CSS del ícono
     */
    private static function get_default_icon($type) {
        switch ($type) {
            case self::TYPE_SUCCESS:
                return 'dashicons-yes-alt';
            case self::TYPE_WARNING:
                return 'dashicons-warning';
            case self::TYPE_ERROR:
                return 'dashicons-dismiss';
            case self::TYPE_INFO:
            default:
                return 'dashicons-info';
        }
    }
    
    /**
     * Cargar scripts y estilos necesarios
     */
    private static function enqueue_assets() {
        static $assets_loaded = false;
        
        if ($assets_loaded) {
            return;
        }
        
        $assets_loaded = true;
        
        // Agregar estilos inline
        add_action('admin_footer', function() {
            ?>
            <style>
                /* Contenedor principal de notificaciones */
                .wp-pos-notifications-container {
                    position: fixed;
                    top: 32px;
                    right: 20px;
                    z-index: 9999;
                    width: 350px;
                    max-width: 90vw;
                }
                
                /* Estilo base de notificación */
                .wp-pos-notification {
                    margin-bottom: 10px;
                    padding: 12px 15px;
                    border-radius: 8px;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
                    position: relative;
                    animation: wp-pos-notification-in 0.3s ease;
                    overflow: hidden;
                }
                
                /* Animación de entrada */
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
                
                /* Animación de salida */
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
                
                /* Botón para cerrar */
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
            </style>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Manejar cierre de notificaciones
                    $(document).on('click', '.wp-pos-notification-dismiss', function() {
                        var notification = $(this).closest('.wp-pos-notification');
                        var id = notification.data('notification-id');
                        
                        // Animar salida
                        notification.addClass('wp-pos-notification-removing');
                        
                        // Remover después de la animación
                        setTimeout(function() {
                            notification.remove();
                        }, 300);
                        
                        // Enviar petición AJAX para eliminar notificación persistente
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'wp_pos_dismiss_notification',
                                notification_id: id,
                                security: wp_pos_nonce
                            }
                        });
                    });
                    
                    // Auto-cierre para notificaciones con timeout
                    $('.wp-pos-notification').each(function() {
                        var notification = $(this);
                        var timeout = notification.data('timeout');
                        
                        if (timeout > 0) {
                            setTimeout(function() {
                                // Animar salida
                                notification.addClass('wp-pos-notification-removing');
                                
                                // Remover después de la animación
                                setTimeout(function() {
                                    notification.remove();
                                }, 300);
                            }, timeout);
                        }
                    });
                });
            </script>
            <?php
        });
    }
}

/**
 * Manejador AJAX para descartar notificaciones
 */
function wp_pos_ajax_dismiss_notification() {
    // Verificar nonce
    check_ajax_referer('wp_pos_nonce', 'security');
    
    // Obtener ID de notificación
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    
    if ($notification_id) {
        WP_POS_Notifications::dismiss($notification_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('ID de notificación inválido');
    }
}
add_action('wp_ajax_wp_pos_dismiss_notification', 'wp_pos_ajax_dismiss_notification');

/**
 * Manejador AJAX para crear notificaciones de stock insuficiente
 */
function wp_pos_ajax_create_stock_notification() {
    // Verificar nonce
    check_ajax_referer('wp_pos_nonce', 'security');
    
    // Obtener datos del producto
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $current_stock = isset($_POST['current_stock']) ? intval($_POST['current_stock']) : 0;
    
    if ($product_id && $product_name && $quantity > $current_stock) {
        // Crear notificación de stock insuficiente
        wp_pos_add_insufficient_stock_notification($product_id, $product_name, $quantity, $current_stock);
        
        // Devolver el HTML actualizado para las notificaciones
        $html = WP_POS_Notifications::render('sales');
        
        wp_send_json_success(array('html' => $html));
    } else {
        wp_send_json_error('Datos insuficientes para crear la notificación de stock');
    }
}
add_action('wp_ajax_wp_pos_create_stock_notification', 'wp_pos_ajax_create_stock_notification');

/**
 * Función simple para agregar notificación desde cualquier parte del plugin
 *
 * @param string $message Mensaje de la notificación
 * @param string $type Tipo de notificación (success, info, warning, error)
 * @param array $args Argumentos adicionales
 * @return int ID de la notificación
 */
function wp_pos_add_notification($message, $type = 'info', $args = array()) {
    return WP_POS_Notifications::add($message, $type, $args);
}

/**
 * Función para agregar una notificación de stock insuficiente
 *
 * @param int $product_id ID del producto
 * @param string $product_name Nombre del producto
 * @param int $quantity Cantidad solicitada
 * @param int $stock Stock actual disponible
 * @return int ID de la notificación
 */
function wp_pos_add_insufficient_stock_notification($product_id, $product_name, $quantity, $stock) {
    $message = sprintf(
        'Estás vendiendo <strong>%d unidades</strong> del producto "<strong>%s</strong>" pero solo hay <strong>%d en stock</strong>. El inventario quedará en <strong>%d</strong>.',
        $quantity,
        esc_html($product_name),
        $stock,
        $stock - $quantity
    );
    
    return WP_POS_Notifications::warning($message, array(
        'title' => 'Stock Insuficiente',
        'icon' => 'dashicons-warning',
        'context' => 'sales',
        'timeout' => 0 // No auto-cerrar
    ));
}

/**
 * Función para mostrar notificaciones en cualquier página del plugin
 *
 * @param string $context Contexto opcional para filtrar notificaciones
 */
function wp_pos_display_notifications($context = null) {
    echo WP_POS_Notifications::render($context);
}
