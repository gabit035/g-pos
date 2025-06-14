/**
 * Script de depuraciu00f3n para el formulario de productos
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        console.log('Script de depuraciu00f3n cargado');
        
        // Manejar el clic en el botu00f3n de envu00edo directamente
        $('.wp-pos-button-primary[type="submit"]').on('click', function(e) {
            console.log('Botu00f3n de crear/actualizar producto clickeado');
            console.log('Nombre del formulario: ' + $('#wp-pos-product-form').attr('id'));
            console.log('Acciu00f3n del formulario: ' + $('#wp-pos-product-form').attr('action'));
            console.log('Mu00e9todo del formulario: ' + $('#wp-pos-product-form').attr('method'));
            
            // Valores de los campos principales
            console.log('Nombre del producto: ' + $('#product_name').val());
            console.log('Precio regular: ' + $('#product_regular_price').val());
            
            // Contar handlers asociados a este formulario
            var formEvents = $._data($('#wp-pos-product-form')[0], 'events');
            console.log('Eventos del formulario:', formEvents);
            
            // Verificar si hay alguna prevenciu00f3n del envu00edo
            var submitPrevented = false;
            
            // Intentamos enviar manualmente el formulario
            setTimeout(function() {
                // Si todavu00eda estamos en la misma pu00e1gina despuu00e9s de 1 segundo, el envu00edo no funcionu00f3
                console.log('Intentando enviar el formulario manualmente...');
                $('#wp-pos-product-form').submit();
            }, 1000);
        });
        
        // Manejar el evento submit del formulario
        $('#wp-pos-product-form').on('submit', function(e) {
            console.log('Evento submit del formulario activado');
            console.log('Formulario enviado?: ' + !e.isDefaultPrevented());
        });
    });

})(jQuery);
