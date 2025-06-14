/**
 * JavaScript para la administraciu00f3n de ventas en WP-POS
 *
 * @package WP-POS
 * @subpackage Sales
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Clase SalesAdmin
     */
    var SalesAdmin = {
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
            // Agregar manejadores de eventos aquu00ed cuando se desarrolle la funcionalidad de ventas
            console.log('Mu00f3dulo de ventas inicializado');
        }
    };

    // Inicializar cuando el documento estu00e9 listo
    $(document).ready(function() {
        SalesAdmin.init();
    });

})(jQuery);
