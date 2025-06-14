/**
 * JavaScript para el frontend de productos en WP-POS
 *
 * @package WP-POS
 * @subpackage Products
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Clase ProductsFrontend
     */
    var ProductsFrontend = {
        /**
         * Inicializar
         */
        init: function() {
            this.bindEvents();
            this.initFilters();
        },

        /**
         * Asociar eventos
         */
        bindEvents: function() {
            // Au00f1adir al carrito
            $('.wp-pos-add-to-cart').on('click', this.addToCart);
            
            // Filtro de categoru00edas
            $('.wp-pos-category-filter').on('change', this.filterByCategory);
            
            // Bu00fasqueda de productos
            $('.wp-pos-product-search').on('keyup', this.searchProducts);
            
            // Vista de producto individual
            $('.wp-pos-product-item').on('click', this.viewProductDetails);
        },

        /**
         * Inicializar filtros
         */
        initFilters: function() {
            // Si hay una categoru00eda activa en la URL, seleccionarla
            var urlParams = new URLSearchParams(window.location.search);
            var category = urlParams.get('category');
            
            if (category) {
                $('.wp-pos-category-filter').val(category);
                this.filterByCategory();
            }
        },

        /**
         * Au00f1adir producto al carrito
         */
        addToCart: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var productId = $(this).data('product-id');
            var quantity = 1;
            
            // Si hay un campo de cantidad, obtener su valor
            var quantityField = $('#wp-pos-quantity-' + productId);
            if (quantityField.length) {
                quantity = parseInt(quantityField.val(), 10) || 1;
            }
            
            // Si existe una funciu00f3n global para au00f1adir al carrito, usarla
            if (typeof wp_pos_add_to_cart === 'function') {
                wp_pos_add_to_cart(productId, quantity);
                return;
            }
            
            // De lo contrario, usar AJAX
            $.ajax({
                url: wp_pos_products_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_pos_add_to_cart',
                    product_id: productId,
                    quantity: quantity,
                    nonce: wp_pos_products_vars.nonce
                },
                beforeSend: function() {
                    // Mostrar indicador de carga
                    $('.wp-pos-product-item[data-product-id="' + productId + '"]').addClass('wp-pos-loading');
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar mensaje de u00e9xito
                        ProductsFrontend.showNotice('Producto au00f1adido al carrito', 'success');
                        
                        // Actualizar contador del carrito si existe
                        if (response.data && response.data.cart_count && $('.wp-pos-cart-count').length) {
                            $('.wp-pos-cart-count').text(response.data.cart_count);
                        }
                    } else {
                        // Mostrar error
                        ProductsFrontend.showNotice(response.data.message || 'Error al au00f1adir al carrito', 'error');
                    }
                },
                error: function() {
                    ProductsFrontend.showNotice('Error de conexiu00f3n', 'error');
                },
                complete: function() {
                    // Quitar indicador de carga
                    $('.wp-pos-product-item[data-product-id="' + productId + '"]').removeClass('wp-pos-loading');
                }
            });
        },

        /**
         * Filtrar por categoru00eda
         */
        filterByCategory: function() {
            var category = $(this).val() || $('.wp-pos-category-filter').val();
            
            if (!category || category === 'all') {
                // Mostrar todos los productos
                $('.wp-pos-product-item').show();
                return;
            }
            
            // Ocultar todos y mostrar solo los de la categoru00eda seleccionada
            $('.wp-pos-product-item').hide();
            $('.wp-pos-product-item[data-category="' + category + '"]').show();
        },

        /**
         * Buscar productos
         */
        searchProducts: function() {
            var searchTerm = $(this).val().toLowerCase();
            
            if (!searchTerm) {
                // Mostrar todos los productos
                $('.wp-pos-product-item').show();
                return;
            }
            
            // Filtrar productos por tu00e9rmino de bu00fasqueda
            $('.wp-pos-product-item').each(function() {
                var productName = $(this).find('.wp-pos-product-title').text().toLowerCase();
                var productDesc = $(this).find('.wp-pos-product-description').text().toLowerCase();
                
                if (productName.indexOf(searchTerm) > -1 || productDesc.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },

        /**
         * Ver detalles de producto
         */
        viewProductDetails: function(e) {
            // No ejecutar si se hizo clic en el botu00f3n de au00f1adir al carrito
            if ($(e.target).closest('.wp-pos-add-to-cart').length) {
                return;
            }
            
            var productId = $(this).data('product-id');
            var productUrl = $(this).data('product-url');
            
            // Si hay URL del producto, redirigir
            if (productUrl) {
                window.location.href = productUrl;
                return;
            }
            
            // Si no, mostrar un modal o expandir detalles in-line
            var $details = $(this).find('.wp-pos-product-details-expanded');
            
            if ($details.length) {
                // Alternar visibilidad
                $details.slideToggle();
            } else {
                // Cargar detalles vu00eda AJAX
                $.ajax({
                    url: wp_pos_products_vars.ajax_url,
                    type: 'GET',
                    data: {
                        action: 'wp_pos_get_product',
                        product_id: productId,
                        nonce: wp_pos_products_vars.nonce
                    },
                    beforeSend: function() {
                        $(this).addClass('wp-pos-loading');
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            // Crear y mostrar detalles
                            var detailsHtml = '<div class="wp-pos-product-details-expanded">';
                            detailsHtml += '<div class="wp-pos-product-description">' + response.data.description + '</div>';
                            detailsHtml += '</div>';
                            
                            $(this).append(detailsHtml);
                            $('.wp-pos-product-details-expanded').slideDown();
                        }
                    },
                    error: function() {
                        ProductsFrontend.showNotice('Error al cargar detalles', 'error');
                    },
                    complete: function() {
                        $(this).removeClass('wp-pos-loading');
                    }
                });
            }
        },

        /**
         * Mostrar mensaje de notificaciu00f3n
         */
        showNotice: function(message, type) {
            // Eliminar notificaciones anteriores
            $('.wp-pos-notice').remove();
            
            // Crear y mostrar nueva notificaciu00f3n
            var $notice = $('<div class="wp-pos-notice wp-pos-notice-' + type + '">' + message + '</div>');
            $('body').append($notice);
            
            // Posicionar en la parte superior
            $notice.css({
                'position': 'fixed',
                'top': '20px',
                'left': '50%',
                'transform': 'translateX(-50%)',
                'z-index': '9999'
            });
            
            // Auto-ocultar despuu00e9s de 3 segundos
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // Inicializar cuando el documento estu00e9 listo
    $(document).ready(function() {
        ProductsFrontend.init();
    });

})(jQuery);
