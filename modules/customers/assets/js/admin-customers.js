/**
 * JavaScript para la administraciu00f3n de clientes en WP-POS
 *
 * @package WP-POS
 * @subpackage Customers
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Clase CustomersAdmin
     */
    var CustomersAdmin = {
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Asociar eventos
         */
        bindEvents: function() {
            // Agregar manejadores de eventos aquu00ed cuando se desarrolle la funcionalidad de clientes
            console.log('Mu00f3dulo de clientes inicializado');
        }
    };

    // Inicializar cuando el documento estu00e9 listo
    $(document).ready(function() {
        CustomersAdmin.init();
    });

})(jQuery);
