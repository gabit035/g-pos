/**
 * JavaScript para el formulario de productos en WP-POS
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Clase ProductForm
     */
    var ProductForm = {
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
            // Navegación por pestañas
            $('.wp-pos-tabs-nav a').on('click', this.switchTab);
            
            // Selector de imagen para el producto
            $('#wp-pos-product-image-button').on('click', this.openMediaUploader);
            $('#wp-pos-product-image-remove').on('click', this.removeProductImage);
            
            // Gestionar visualización de opciones de stock
            $('#product_manage_stock').on('change', this.toggleStockOptions);
            
            // Validación del formulario
            $('#wp-pos-product-form').on('submit', this.validateForm);
        },

        /**
         * Cambiar de pestaña
         */
        switchTab: function(e) {
            e.preventDefault();
            
            var target = $(this).attr('href');
            
            // Desactivar todas las pestañas y ocultar todos los paneles
            $('.wp-pos-tabs-nav li').removeClass('active');
            $('.wp-pos-tab-pane').removeClass('active');
            
            // Activar la pestaña actual y mostrar su panel
            $(this).parent('li').addClass('active');
            $(target).addClass('active');
        },

        /**
         * Abrir selector de medios para la imagen del producto
         */
        openMediaUploader: function(e) {
            e.preventDefault();
            
            var mediaUploader;
            
            // Si ya existe el selector, abrirlo directamente
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Crear un nuevo selector de medios
            mediaUploader = wp.media({
                title: 'Seleccionar Imagen para el Producto',
                button: {
                    text: 'Usar esta imagen'
                },
                multiple: false
            });
            
            // Cuando se selecciona una imagen
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Establecer la imagen seleccionada
                $('#product_image_id').val(attachment.id);
                $('#product_image_url').val(attachment.url);
                
                // Actualizar vista previa
                var imgHTML = '<img src="' + attachment.url + '" alt="Vista previa">';
                $('#wp-pos-product-image-preview').html(imgHTML);
            });
            
            // Abrir el selector
            mediaUploader.open();
        },

        /**
         * Eliminar la imagen del producto
         */
        removeProductImage: function(e) {
            e.preventDefault();
            
            // Limpiar valores
            $('#product_image_id').val('');
            $('#product_image_url').val('');
            
            // Restaurar vista de imagen vacía
            var emptyImgHTML = '<div class="wp-pos-no-image"><span class="dashicons dashicons-format-image"></span></div>';
            $('#wp-pos-product-image-preview').html(emptyImgHTML);
        },

        /**
         * Mostrar u ocultar opciones de stock según el checkbox
         */
        toggleStockOptions: function() {
            if ($(this).is(':checked')) {
                $('.wp-pos-stock-options').show();
            } else {
                $('.wp-pos-stock-options').hide();
            }
        },

        /**
         * Validar formulario antes de enviar
         */
        validateForm: function(e) {
            // Obtener los valores usando trim para eliminar espacios en blanco
            var productName = $('#product_name').val().trim();
            var regularPrice = $('#product_regular_price').val().trim();
            
            console.log('Validando formulario:');
            console.log('Nombre del producto:', productName);
            console.log('Precio regular:', regularPrice);
            
            // Validar nombre del producto
            if (productName === '') {
                e.preventDefault();
                alert('Por favor, ingresa un nombre para el producto.');
                $('#product_name').focus();
                return false;
            }
            
            // Validar precio regular
            if (regularPrice === '' || isNaN(parseFloat(regularPrice)) || parseFloat(regularPrice) <= 0) {
                e.preventDefault();
                alert('Por favor, ingresa un precio regular válido mayor que cero.');
                $('#product_regular_price').focus();
                return false;
            }
            
            // Validar precio de oferta si se ingresa
            var salePrice = $('#product_sale_price').val().trim();
            if (salePrice !== '' && !isNaN(parseFloat(salePrice))) {
                if (parseFloat(salePrice) >= parseFloat(regularPrice)) {
                    e.preventDefault();
                    alert('El precio de oferta debe ser menor que el precio regular.');
                    $('#product_sale_price').focus();
                    return false;
                }
            }
            
            // Si todo está correcto, permitir el envío del formulario
            return true;
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        ProductForm.init();
        
        // Agregar console.log para depuración
        console.log('Formulario de producto inicializado');
        console.log('Campo de nombre:', $('#product_name').length > 0 ? 'Encontrado' : 'No encontrado');
    });

})(jQuery);
