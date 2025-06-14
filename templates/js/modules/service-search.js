/**
 * Módulo de búsqueda de servicios
 * Dependencias: utils.js, cart.js
 */

const PosServiceSearch = (function($) {
    'use strict';
    
    let selectedIndex = -1;

    // Inicializar módulo
    function init() {
        const $searchInput = $('#wp-pos-service-search');
        const $searchResults = $('#wp-pos-service-results');

        // Búsqueda de servicios
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
                searchServices(term);
            }, 300);
        });
        
        // Navegación por teclado en servicios
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

        // Seleccionar servicio
        $(document).on('click', '.wp-pos-service-select', function() {
            const serviceId = $(this).data('id');
            const serviceName = $(this).data('name');
            const servicePrice = $(this).data('price');
            
            // Añadir al carrito como tipo 'service'
            PosCart.addItem({
                id: serviceId,
                name: serviceName,
                price: servicePrice,
                type: 'service',
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
            PosUtils.showMessage(POS.texts.service_added || 'Servicio añadido');
        });
    }

    // Buscar servicios
    function searchServices(term) {
        const $searchResults = $('#wp-pos-service-results');
        
        $searchResults.html(`<div class="wp-pos-search-loading"><span class="dashicons dashicons-update-alt"></span> ${POS.texts.searching || 'Buscando...'}</div>`);
        $searchResults.show();

        // Usar la acción correcta del plugin original
        $.ajax({
            url: POS.ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_pos_search_services', // La acción es correcta pero con parámetros correctos
                query: term,
                security: POS.nonce // El plugin verifica con wp_pos_nonce
            },
            success: function(response) {
                console.log("Respuesta servicios:", response);
                
                if (response.success) {
                    // Manejar diferentes formatos de respuesta posibles
                    let services = [];
                    
                    if (Array.isArray(response.data)) {
                        services = response.data;
                    } else if (response.data && response.data.services) {
                        services = response.data.services;
                    } else if (response.data && response.data.data) {
                        services = Array.isArray(response.data.data) ? response.data.data : [response.data.data];
                    }
                    
                    showServiceResults(services);
                    selectedIndex = -1; // Resetear el índice seleccionado
                } else {
                    $searchResults.html(`<div class="wp-pos-search-error">${POS.texts.error || 'Error en la búsqueda'}</div>`);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error búsqueda servicios:", error, xhr.responseText);
                $searchResults.html(`<div class="wp-pos-search-error">${POS.texts.error || 'Error en la búsqueda'}</div>`);
            }
        });
    }

    // Mostrar resultados
    function showServiceResults(services) {
        const $searchResults = $('#wp-pos-service-results');
        
        if (!services || services.length === 0) {
            $searchResults.html(`<div class="wp-pos-search-empty">${POS.texts.no_results || 'No se encontraron resultados'}</div>`);
            return;
        }

        let html = '<div class="wp-pos-search-items">';
        
        // Lista de servicios
        services.forEach(function(service) {
            const serviceName = service.name || service.title || 'Servicio sin nombre';
            const servicePrice = service.sale_price || service.price || 0;
            
            html += `<div class="wp-pos-search-item wp-pos-service-select" 
                data-id="${service.id}" 
                data-name="${serviceName}" 
                data-price="${servicePrice}">
                <div class="wp-pos-search-item-title">${serviceName}</div>
                <div class="wp-pos-search-item-meta">
                    ${PosUtils.formatPrice(servicePrice)}
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