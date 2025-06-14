/**
 * Módulo de búsqueda de productos
 * Dependencias: utils.js, cart.js
 */

const PosProductSearch = (function($) {
    'use strict';
    
    let selectedIndex = -1;

    // Inicializar módulo
    function init() {
        const $searchInput = $('#wp-pos-product-search');
        const $searchResults = $('#wp-pos-product-results');

        // Búsqueda de productos
        let searchTimeout;
        $searchInput.on('keyup', function(e) {
            // No buscar cuando se navega con teclas de flecha
            if (e.keyCode === 38 || e.keyCode === 40 || e.keyCode === 13 || e.keyCode === 27) {
                return;
            }
            
            const term = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            selectedIndex = -1; // Resetear índice seleccionado
            
            if (term.length < 2) {
                $searchResults.hide();
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchProducts(term);
            }, 300);
        });
        
        // Navegación por teclado en productos
        $searchInput.on('keydown', function(e) {
            const items = $('.wp-pos-search-item', $searchResults);
            
            // Si no hay resultados visibles, no hacemos nada
            if (!$searchResults.is(':visible') || items.length === 0) {
                return;
            }

            switch(e.keyCode) {
                case 40: // Flecha abajo
                    e.preventDefault();
                    selectedIndex = (selectedIndex < items.length - 1) ? selectedIndex + 1 : 0;
                    PosUtils.highlightItem(items, selectedIndex, $searchResults);
                    break;
                case 38: // Flecha arriba
                    e.preventDefault();
                    selectedIndex = (selectedIndex > 0) ? selectedIndex - 1 : items.length - 1;
                    PosUtils.highlightItem(items, selectedIndex, $searchResults);
                    break;
                case 13: // Enter
                    e.preventDefault();
                    if (selectedIndex >= 0 && selectedIndex < items.length) {
                        items.eq(selectedIndex).click();
                    }
                    break;
                case 27: // Escape
                    e.preventDefault();
                    $searchResults.hide();
                    break;
            }
        });

        // Seleccionar producto
        $(document).on('click', '.wp-pos-product-select', function() {
            const productId = $(this).data('id');
            const productName = $(this).data('name');
            const productPrice = $(this).data('price');
            const sku = $(this).data('sku');
            
            // Añadir al carrito como tipo 'product'
            PosCart.addItem({
                id: productId,
                name: productName,
                price: productPrice,
                sku: sku,
                type: 'product',
                quantity: 1
            });
            
            // Limpiar búsqueda - asegurarnos que se limpia completamente
            $searchInput.val('');
            $searchResults.empty().hide(); // Vaciar resultados y ocultar
            selectedIndex = -1; // Resetear el índice seleccionado
            
            // Dar un tiempo breve y luego enfocar en el campo de búsqueda
            setTimeout(() => {
                $searchInput.focus();
            }, 50);
            
            // Mostrar mensaje
            PosUtils.showMessage(POS.texts.product_added || 'Producto añadido');
        });
    }

    // Buscar productos
    function searchProducts(term) {
        const $searchResults = $('#wp-pos-product-results');
        
        $searchResults.html(`<div class="wp-pos-search-loading"><span class="dashicons dashicons-update-alt"></span> ${POS.texts.searching || 'Buscando...'}</div>`);
        $searchResults.show();

        // Usar la acción correcta del plugin original
        $.ajax({
            url: POS.ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_pos_search_products_direct', // Acción correcta del plugin original
                query: term,
                nonce: POS.nonce // El plugin verifica con wp_pos_nonce
            },
            success: function(response) {
                console.log("Respuesta productos:", response);
                
                if (response.success) {
                    let products = [];
                    
                    if (response.data && response.data.products) {
                        products = response.data.products;
                    } else if (Array.isArray(response.data)) {
                        products = response.data;
                    } else if (response.data && response.data.data) {
                        products = Array.isArray(response.data.data) ? response.data.data : [response.data.data];
                    }
                    
                    showProductResults(products);
                    selectedIndex = -1; // Resetear el índice seleccionado
                } else {
                    $searchResults.html(`<div class="wp-pos-search-error">${POS.texts.error || 'Error en la búsqueda'}</div>`);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error búsqueda productos:", error, xhr.responseText);
                $searchResults.html(`<div class="wp-pos-search-error">${POS.texts.error || 'Error en la búsqueda'}</div>`);
            }
        });
    }

    // Mostrar resultados
    function showProductResults(products) {
        const $searchResults = $('#wp-pos-product-results');
        
        if (!products || products.length === 0) {
            $searchResults.html(`<div class="wp-pos-search-empty">${POS.texts.no_results || 'No se encontraron resultados'}</div>`);
            return;
        }

        let html = '<div class="wp-pos-search-items">';
        
        products.forEach(function(product) {
            const productName = product.name || product.title || 'Producto sin nombre';
            const productPrice = product.sale_price || product.price || 0;
            const sku = product.sku || '';
            const stock = product.stock !== undefined ? product.stock : '';
            
            html += `<div class="wp-pos-search-item wp-pos-product-select" 
                data-id="${product.id}" 
                data-name="${productName}" 
                data-price="${productPrice}"
                data-sku="${sku}"
                data-stock="${stock}">
                <div class="wp-pos-search-item-title">${productName}</div>
                <div class="wp-pos-search-item-meta">
                    ${sku ? `SKU: ${sku} | ` : ''}${PosUtils.formatPrice(productPrice)}${stock !== '' ? ` | Stock: ${stock}` : ''}
                </div>
            </div>`;
        });
        
        html += '</div>';
        $searchResults.html(html);
    }

    return {
        init: init
    };
})(jQuery);