/**
 * Utilidades para la nueva interfaz de ventas
 */

const PosUtils = (function($) {
    'use strict';
    
    // Datos globales compartidos (dejamos referencia global para compatibilidad)
    window.POS = window.wp_pos_data || {
        ajaxurl: '',
        nonce: '',
        customer_nonce: '',
        product_nonce: '',
        service_nonce: '',
        texts: {}
    };

    return {
        /**
         * Formatear precios
         */
        formatPrice: function(amount) {
            return new Intl.NumberFormat('es-AR', {
                style: 'currency',
                currency: 'ARS',
                minimumFractionDigits: 2
            }).format(amount);
        },

        /**
         * Mostrar mensajes al usuario
         */
        showMessage: function(message, type = 'success') {
            const $message = $(
                `<div class="wp-pos-message wp-pos-message-${type}">
                    <div>${message}</div>
                    <button type="button" class="wp-pos-message-close">&times;</button>
                </div>`
            );
            
            $('#wp-pos-messages').append($message);
            
            if (type === 'success') {
                setTimeout(() => $message.fadeOut(300, function() { $(this).remove(); }), 5000);
            }
            
            $message.find('.wp-pos-message-close').on('click', function() {
                $message.fadeOut(300, function() { $(this).remove(); });
            });
        },
        
        /**
         * Destacar elemento seleccionado en la navegación por teclado
         */
        highlightItem: function(items, index, container) {
            items.removeClass('keyboard-selected');
            items.eq(index).addClass('keyboard-selected');
            
            // Scroll si es necesario
            const selectedItem = items.eq(index);
            
            const itemTop = selectedItem.position().top;
            const itemBottom = itemTop + selectedItem.outerHeight();
            const containerTop = 0;
            const containerBottom = container.height();
            
            if (itemTop < containerTop) {
                container.scrollTop(container.scrollTop() + itemTop - containerTop);
            } else if (itemBottom > containerBottom) {
                container.scrollTop(container.scrollTop() + itemBottom - containerBottom);
            }
        }
    };
})(jQuery);