/**
 * Scripts para el mu00f3dulo de notificaciones
 *
 * @package WP-POS
 * @subpackage Notifications
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Objeto principal para gestionar notificaciones
     */
    var WP_POS_Notifications = {
        /**
         * Inicializar scripts
         */
        init: function() {
            // Manejar cierre de notificaciones
            $(document).on('click', '.wp-pos-notification-dismiss', this.handleDismiss);
            
            // Auto-cierre para notificaciones con timeout
            this.setupAutoClose();
        },
        
        /**
         * Manejar el cierre de una notificaciu00f3n
         */
        handleDismiss: function() {
            var notification = $(this).closest('.wp-pos-notification');
            var id = notification.data('notification-id');
            
            // Animar salida
            notification.addClass('wp-pos-notification-removing');
            
            // Remover despuu00e9s de la animaciu00f3n
            setTimeout(function() {
                notification.remove();
            }, 300);
            
            // Enviar peticiu00f3n AJAX para eliminar notificaciu00f3n persistente
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_pos_dismiss_notification',
                    notification_id: id,
                    security: wp_pos_nonce
                }
            });
        },
        
        /**
         * Configurar auto-cierre para notificaciones temporales
         */
        setupAutoClose: function() {
            $('.wp-pos-notification').each(function() {
                var notification = $(this);
                var timeout = notification.data('timeout');
                
                if (timeout > 0) {
                    setTimeout(function() {
                        // Animar salida
                        notification.addClass('wp-pos-notification-removing');
                        
                        // Remover despuu00e9s de la animaciu00f3n
                        setTimeout(function() {
                            notification.remove();
                        }, 300);
                    }, timeout);
                }
            });
        },
        
        /**
         * Crear una nueva notificaciu00f3n de stock insuficiente
         * 
         * @param {Object} data Datos del producto
         */
        createStockNotification: function(data) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_pos_create_stock_notification',
                    product_id: data.productId,
                    product_name: data.productName,
                    quantity: data.quantity,
                    current_stock: data.currentStock,
                    security: wp_pos_nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Actualizar contenedor de notificaciones
                        var container = document.getElementById('wp-pos-notifications-area');
                        if (container) {
                            container.innerHTML = response.data.html;
                            
                            // Reiniciar auto-cierre para las nuevas notificaciones
                            WP_POS_Notifications.setupAutoClose();
                        }
                    }
                }
            });
        }
    };
    
    // Inicializar cuando el DOM estu00e9 listo
    $(document).ready(function() {
        WP_POS_Notifications.init();
        
        // Exponer el objeto para uso global
        window.WP_POS_Notifications = WP_POS_Notifications;
    });
    
})(jQuery);
