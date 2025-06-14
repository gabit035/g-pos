/**
 * JavaScript para la administración de productos en WP-POS
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Clase ProductsAdmin
     */
    var ProductsAdmin = {
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
            this.initDataTable();
            this.initStockModal();
        },

        /**
         * Asociar eventos
         */
        bindEvents: function() {
            // Búsqueda de productos
            $('#wp-pos-search-products').on('keyup', this.searchProducts);
            
            // Filtrado por categoría
            $('#wp-pos-category-filter').on('change', this.filterByCategory);
            
            // Botón de actualizar stock
            $('.wp-pos-update-stock').on('click', this.openStockModal);
            
            // Formulario de productos
            $('#wp-pos-product-form').on('submit', this.validateProductForm);
            
            // Selector de imagen para producto
            $('#wp-pos-product-image-button').on('click', this.openMediaUploader);
            
            // Eliminar producto
            $('.wp-pos-delete-product').on('click', this.confirmDeleteProduct);
        },

        /**
         * Inicializar DataTable para productos
         */
        initDataTable: function() {
            if ($.fn.DataTable && $('#wp-pos-products-table').length) {
                $('#wp-pos-products-table').DataTable({
                    pageLength: 25,
                    responsive: true,
                    language: {
                        search: "Buscar:",
                        lengthMenu: "Mostrar _MENU_ productos por página",
                        info: "Mostrando _START_ a _END_ de _TOTAL_ productos",
                        infoEmpty: "No hay productos disponibles",
                        paginate: {
                            first: "Primera",
                            last: "Última",
                            next: "Siguiente",
                            previous: "Anterior"
                        }
                    }
                });
            }
        },

        /**
         * Inicializar modal para actualización de stock
         */
        initStockModal: function() {
            // Si existe el modal y está disponible jQuery UI Dialog
            if ($('#wp-pos-stock-modal').length && $.fn.dialog) {
                $('#wp-pos-stock-modal').dialog({
                    autoOpen: false,
                    width: 400,
                    modal: true,
                    resizable: false,
                    buttons: {
                        "Actualizar": function() {
                            ProductsAdmin.updateProductStock();
                        },
                        "Cancelar": function() {
                            $(this).dialog("close");
                        }
                    }
                });
            }
        },

        /**
         * Buscar productos
         */
        searchProducts: function(e) {
            var searchTerm = $(this).val();
            
            // Si la búsqueda está vacía, resetear la tabla
            if (searchTerm === '') {
                $('#wp-pos-products-table').DataTable().search('').draw();
                return;
            }
            
            // Aplicar búsqueda a la tabla
            $('#wp-pos-products-table').DataTable().search(searchTerm).draw();
        },

        /**
         * Filtrar por categoría
         */
        filterByCategory: function() {
            var category = $(this).val();
            
            // Si la categoría es 'all', mostrar todos
            if (category === 'all') {
                $('#wp-pos-products-table').DataTable().column(3).search('').draw();
                return;
            }
            
            // Filtrar por la categoría seleccionada
            $('#wp-pos-products-table').DataTable().column(3).search(category).draw();
        },

        /**
         * Abrir modal para actualizar stock
         */
        openStockModal: function(e) {
            e.preventDefault();
            
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            var currentStock = $(this).data('current-stock');
            
            // Actualizar información en el modal
            $('#wp-pos-stock-product-id').val(productId);
            $('#wp-pos-stock-product-name').text(productName);
            $('#wp-pos-stock-current').text(currentStock);
            $('#wp-pos-stock-quantity').val(currentStock).focus();
            
            // Abrir modal
            $('#wp-pos-stock-modal').dialog('open');
        },

        /**
         * Actualizar stock de producto
         */
        updateProductStock: function() {
            var productId = $('#wp-pos-stock-product-id').val();
            var quantity = $('#wp-pos-stock-quantity').val();
            var operation = $('#wp-pos-stock-operation').val();
            var note = $('#wp-pos-stock-note').val();
            
            // Validar datos
            if (!productId || isNaN(quantity)) {
                alert('Por favor, introduce una cantidad válida.');
                return;
            }
            
            // Enviar solicitud AJAX
            $.ajax({
                url: wp_pos_products.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_pos_update_product_stock',
                    nonce: wp_pos_products.nonce,
                    product_id: productId,
                    quantity: quantity,
                    operation: operation,
                    note: note
                },
                beforeSend: function() {
                    // Mostrar indicador de carga
                    $('#wp-pos-stock-modal').append('<div class="wp-pos-loading">Actualizando...</div>');
                },
                success: function(response) {
                    if (response.success) {
                        // Cerrar modal
                        $('#wp-pos-stock-modal').dialog('close');
                        
                        // Actualizar valor en la tabla
                        var cell = $('#wp-pos-product-' + productId + ' .wp-pos-stock-column');
                        cell.text(response.data.new_stock);
                        
                        // Actualizar data-current-stock
                        $('#wp-pos-product-' + productId + ' .wp-pos-update-stock').data('current-stock', response.data.new_stock);
                        
                        // Mostrar mensaje de éxito
                        alert(wp_pos_products.i18n.stock_updated);
                        
                        // Recargar página después de un breve retraso
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert(response.data.message || wp_pos_products.i18n.error);
                    }
                },
                error: function() {
                    alert(wp_pos_products.i18n.error);
                },
                complete: function() {
                    // Quitar indicador de carga
                    $('.wp-pos-loading').remove();
                }
            });
        },

        /**
         * Validar formulario de productos
         */
        validateProductForm: function(e) {
            var productName = $('#wp-pos-product-name').val();
            var productPrice = $('#wp-pos-product-price').val();
            
            if (!productName) {
                e.preventDefault();
                alert('Por favor, introduce un nombre para el producto.');
                $('#wp-pos-product-name').focus();
                return false;
            }
            
            if (!productPrice || isNaN(productPrice) || productPrice <= 0) {
                e.preventDefault();
                alert('Por favor, introduce un precio válido para el producto.');
                $('#wp-pos-product-price').focus();
                return false;
            }
            
            return true;
        },

        /**
         * Abrir selector de medios para imagen de producto
         */
        openMediaUploader: function(e) {
            e.preventDefault();
            
            var button = $(this);
            var imageContainer = $('#wp-pos-product-image-preview');
            var hiddenField = $('#wp-pos-product-image-id');
            
            // Si ya existe un marco de medios, abrirlo
            if (wp.media.frames.productImage) {
                wp.media.frames.productImage.open();
                return;
            }
            
            // Crear un nuevo marco de medios
            wp.media.frames.productImage = wp.media({
                title: 'Seleccionar imagen para el producto',
                button: {
                    text: 'Usar esta imagen'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // Cuando se selecciona una imagen
            wp.media.frames.productImage.on('select', function() {
                var attachment = wp.media.frames.productImage.state().get('selection').first().toJSON();
                
                // Actualizar vista previa de la imagen
                imageContainer.html('<img src="' + attachment.url + '" alt="Vista previa" />');
                
                // Actualizar valor del campo oculto
                hiddenField.val(attachment.id);
                
                // Mostrar botón para eliminar la imagen
                $('#wp-pos-product-image-remove').show();
            });
            
            // Abrir el selector de medios
            wp.media.frames.productImage.open();
        },

        /**
         * Confirmar eliminación de producto
         */
        confirmDeleteProduct: function(e) {
            if (!confirm(wp_pos_products.i18n.confirm_delete)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function() {
        ProductsAdmin.init();
    });

})(jQuery);
