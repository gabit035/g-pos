/**
 * Scripts para la funcionalidad de impresión de recibos
 * 
 * @package WP-POS
 * @subpackage Receipts
 * @since 2.3.0
 */

(function($) {
    'use strict';
    
    // Objeto principal
    var PosReceipt = {
        
        /**
         * Inicializar funcionalidad
         */
        init: function() {
            // Auto-imprimir después de cargar
            $(window).on('load', function() {
                // Esperar un segundo para que los estilos se apliquen correctamente
                setTimeout(function() {
                    // Verificar si debe auto-imprimir
                    if (PosReceipt.getParameterByName('autoprint') === '1') {
                        window.print();
                    }
                }, 1000);
            });
            
            // Manejar eventos de botones
            $('.print-button').on('click', function(e) {
                e.preventDefault();
                window.print();
            });
            
            $('.back-button').on('click', function(e) {
                e.preventDefault();
                // Si se abrió en una nueva ventana, cerrarla
                // De lo contrario, volver a la página anterior
                if (window.opener && !window.opener.closed) {
                    window.close();
                } else {
                    window.history.back();
                }
            });
        },
        
        /**
         * Obtener parámetro de URL
         */
        getParameterByName: function(name) {
            var url = window.location.href;
            name = name.replace(/[\[\]]/g, '\\$&');
            var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }
    };
    
    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        PosReceipt.init();
    });
    
})(jQuery);
